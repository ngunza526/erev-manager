<?php

namespace App\Services;

use App\Enums\MemberStatus;
use App\Models\ChurchEvent;
use App\Models\EventRegistration;
use App\Models\Member;
use App\Models\OfflineSyncBatch;
use App\Models\User;
use App\Models\Visitor;
use App\Services\Accounting\AccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class OfflineSyncService
{
    public function __construct(
        private readonly AccessScope $scope,
        private readonly AccountingService $accounting,
    ) {
    }

    public function sync(User $user, array $data): OfflineSyncBatch
    {
        $this->scope->ensureChurchAllowed($user, (int) $data['church_id']);

        $existing = OfflineSyncBatch::where('device_id', $data['device_id'])
            ->where('client_batch_id', $data['client_batch_id'])
            ->where('church_id', $data['church_id'])
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($user, $data) {
            $results = [];
            $conflicts = [];

            foreach ($data['records'] as $record) {
                try {
                    $results[] = $this->processRecord($user, (int) $data['church_id'], $record);
                } catch (ValidationException $exception) {
                    $conflicts[] = [
                        'client_id' => $record['client_id'] ?? null,
                        'type' => $record['type'] ?? null,
                        'errors' => $exception->errors(),
                    ];
                } catch (Throwable $exception) {
                    $conflicts[] = [
                        'client_id' => $record['client_id'] ?? null,
                        'type' => $record['type'] ?? null,
                        'errors' => ['record' => [$exception->getMessage()]],
                    ];
                }
            }

            return OfflineSyncBatch::create([
                'church_id' => $data['church_id'],
                'user_id' => $user->id,
                'device_id' => $data['device_id'],
                'client_batch_id' => $data['client_batch_id'],
                'payload' => [
                    'submitted' => $data['records'],
                    'results' => $results,
                ],
                'status' => count($conflicts) === 0 ? 'synced' : 'partial_failed',
                'processed_count' => count($results),
                'synced_at' => now(),
                'conflicts' => $conflicts ?: null,
            ]);
        });
    }

    private function processRecord(User $user, int $churchId, array $record): array
    {
        Validator::make($record, [
            'client_id' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(['member', 'visitor', 'donation', 'event_registration', 'manual_journal_entry'])],
            'payload' => ['required', 'array'],
        ])->validate();

        return match ($record['type']) {
            'member' => $this->syncMember($churchId, $record),
            'visitor' => $this->syncVisitor($churchId, $record),
            'donation' => $this->syncDonation($user, $churchId, $record),
            'event_registration' => $this->syncEventRegistration($user, $churchId, $record),
            'manual_journal_entry' => $this->syncManualJournalEntry($user, $churchId, $record),
        };
    }

    private function syncMember(int $churchId, array $record): array
    {
        $payload = Validator::make($record['payload'], [
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'sex' => ['required', 'string', 'max:40'],
            'birth_date' => ['required', 'date'],
            'birth_place' => ['required', 'string', 'max:255'],
            'profession' => ['required', 'string', 'max:255'],
            'marital_status' => ['required', 'string', 'max:80'],
            'spouse' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $member = Member::create([
            ...$payload,
            'church_id' => $churchId,
            'status' => MemberStatus::Sympathisant->value,
        ]);

        return $this->result($record, 'members', $member->id);
    }

    private function syncVisitor(int $churchId, array $record): array
    {
        $payload = Validator::make($record['payload'], [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:255'],
            'visit_source' => ['required', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $visitor = Visitor::create([
            ...$payload,
            'church_id' => $churchId,
            'visited_at' => now()->toDateString(),
            'follow_up_status' => 'a_relancer',
        ]);

        return $this->result($record, 'visitors', $visitor->id);
    }

    private function syncDonation(User $user, int $churchId, array $record): array
    {
        $payload = Validator::make($record['payload'], [
            'giver_name' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['dime', 'offrande', 'don'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', Rule::in(['USD', 'CDF'])],
            'exchange_rate' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', Rule::in(['cash', 'bank', 'card', 'mobile_money', 'mpesa', 'airtel_money', 'orange_money'])],
        ])->validate();

        $entry = $this->accounting->recordCollection([
            'church_id' => $churchId,
            'type' => $payload['type'],
            'amount' => $payload['amount'],
            'currency' => $payload['currency'],
            'exchange_rate' => $payload['exchange_rate'],
            'cash_account_code' => $this->cashAccount($payload['payment_method']),
            'description' => 'Don offline '.($payload['giver_name'] ?: 'anonyme'),
            'created_by' => $user->id,
        ]);

        return $this->result($record, 'journal_entries', $entry->id, ['reference' => $entry->reference]);
    }

    private function syncEventRegistration(User $user, int $churchId, array $record): array
    {
        $payload = Validator::make($record['payload'], [
            'church_event_id' => ['required', 'integer', 'exists:church_events,id'],
            'attendee_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', Rule::in(['USD', 'CDF'])],
            'exchange_rate' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', Rule::in(['cash', 'bank', 'card', 'mobile_money', 'mpesa', 'airtel_money', 'orange_money'])],
        ])->validate();

        $event = ChurchEvent::whereKey($payload['church_event_id'])->where('church_id', $churchId)->firstOrFail();
        $entry = null;

        if ((float) ($payload['amount_paid'] ?? 0) > 0) {
            $entry = $this->accounting->recordBalancedEntry([
                'church_id' => $churchId,
                'type' => 'event_registration',
                'entry_date' => now()->toDateString(),
                'description' => "Inscription offline {$event->title}",
                'currency' => $payload['currency'],
                'exchange_rate' => $payload['exchange_rate'],
                'created_by' => $user->id,
                'lines' => [
                    ['account_code' => $this->cashAccount($payload['payment_method']), 'label' => 'Encaissement inscription offline', 'debit' => $payload['amount_paid'], 'credit' => 0],
                    ['account_code' => '704', 'label' => 'Revenu evenement', 'debit' => 0, 'credit' => $payload['amount_paid']],
                ],
            ]);
        }

        $registration = EventRegistration::create([
            'church_id' => $churchId,
            'church_event_id' => $event->id,
            'journal_entry_id' => $entry?->id,
            'attendee_name' => $payload['attendee_name'],
            'phone' => $payload['phone'] ?? null,
            'ticket_code' => 'OFF-'.strtoupper(Str::random(8)),
            'currency' => $payload['currency'],
            'amount_paid' => $payload['amount_paid'] ?? 0,
            'exchange_rate' => $payload['exchange_rate'],
            'payment_method' => $payload['payment_method'],
            'check_in_status' => 'registered',
        ]);
        $event->increment('registrations_count');

        return $this->result($record, 'event_registrations', $registration->id, ['journal_entry_id' => $entry?->id]);
    }

    private function syncManualJournalEntry(User $user, int $churchId, array $record): array
    {
        $payload = Validator::make($record['payload'], [
            'entry_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'currency' => ['required', Rule::in(['USD', 'CDF'])],
            'exchange_rate' => ['required', 'numeric', 'min:1'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_code' => ['required', 'string', 'exists:chart_of_accounts,code'],
            'lines.*.label' => ['required', 'string', 'max:255'],
            'lines.*.debit' => ['required', 'numeric', 'min:0'],
            'lines.*.credit' => ['required', 'numeric', 'min:0'],
        ])->validate();

        $entry = $this->accounting->recordBalancedEntry([
            ...$payload,
            'church_id' => $churchId,
            'type' => 'manual_offline',
            'created_by' => $user->id,
        ]);

        return $this->result($record, 'journal_entries', $entry->id, ['reference' => $entry->reference]);
    }

    private function result(array $record, string $resource, int $serverId, array $extra = []): array
    {
        return [
            'client_id' => $record['client_id'],
            'type' => $record['type'],
            'resource' => $resource,
            'server_id' => $serverId,
            ...$extra,
        ];
    }

    private function cashAccount(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'bank', 'card' => '501',
            'mobile_money', 'mpesa', 'airtel_money', 'orange_money' => '515',
            default => '511',
        };
    }
}
