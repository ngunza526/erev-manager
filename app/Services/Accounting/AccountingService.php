<?php

namespace App\Services\Accounting;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingService
{
    public function recordCollection(array $data): JournalEntry
    {
        $map = [
            'dime' => '701',
            'offrande' => '702',
            'don' => '703',
        ];

        if (! isset($map[$data['type']])) {
            throw ValidationException::withMessages(['type' => 'Type de collecte invalide.']);
        }

        return $this->recordBalancedEntry([
            'church_id' => $data['church_id'],
            'type' => $data['type'],
            'description' => $data['description'] ?? "Reception {$data['type']}",
            'currency' => $data['currency'],
            'exchange_rate' => $data['exchange_rate'],
            'created_by' => $data['created_by'] ?? null,
            'lines' => [
                ['account_code' => $data['cash_account_code'] ?? '511', 'label' => 'Encaissement', 'debit' => $data['amount'], 'credit' => 0],
                ['account_code' => $map[$data['type']], 'label' => $data['description'] ?? ucfirst($data['type']), 'debit' => 0, 'credit' => $data['amount']],
            ],
        ]);
    }

    public function recordBalancedEntry(array $data): JournalEntry
    {
        $debit = collect($data['lines'])->sum(fn ($line) => (float) $line['debit']);
        $credit = collect($data['lines'])->sum(fn ($line) => (float) $line['credit']);

        if (round($debit, 2) !== round($credit, 2)) {
            throw ValidationException::withMessages(['lines' => 'Une ecriture comptable doit equilibrer debit et credit.']);
        }

        return DB::transaction(function () use ($data) {
            $entry = JournalEntry::create([
                'church_id' => $data['church_id'],
                'reference' => $data['reference'] ?? $this->nextReference(),
                'entry_date' => $data['entry_date'] ?? now()->toDateString(),
                'type' => $data['type'],
                'description' => $data['description'],
                'currency' => $data['currency'],
                'exchange_rate' => $data['exchange_rate'],
                'status' => 'validee',
                'validated_at' => now(),
                'created_by' => $data['created_by'] ?? null,
            ]);

            foreach ($data['lines'] as $line) {
                $account = ChartOfAccount::where('code', $line['account_code'])->firstOrFail();
                $entry->lines()->create([
                    'chart_of_account_id' => $account->id,
                    'label' => $line['label'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                ]);
            }

            return $entry->load('lines.account', 'church');
        });
    }

    private function nextReference(): string
    {
        do {
            $reference = 'JRN-'.now()->format('YmdHis').'-'.strtoupper(bin2hex(random_bytes(4)));
        } while (JournalEntry::where('reference', $reference)->exists());

        return $reference;
    }
}
