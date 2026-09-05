<?php

namespace App\Services\Accounting;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingService
{
    /**
     * Types de collecte disponibles : compte de produit credite et
     * caisse/compte de tresorerie debite par defaut pour chacun.
     * Le formulaire de saisie permet de choisir une autre caisse/compte,
     * mais chaque type de collecte a bien sa propre affectation par defaut.
     */
    public const COLLECTION_TYPES = [
        'dime' => ['label' => 'Dime', 'revenue_account_code' => '701', 'default_cash_account_code' => '511'],
        'offrande' => ['label' => 'Offrande', 'revenue_account_code' => '702', 'default_cash_account_code' => '511'],
        'don' => ['label' => 'Don', 'revenue_account_code' => '703', 'default_cash_account_code' => '511'],
        'action_grace' => ['label' => 'Action de grace', 'revenue_account_code' => '706', 'default_cash_account_code' => '511'],
        'offrande_speciale' => ['label' => 'Offrande speciale', 'revenue_account_code' => '707', 'default_cash_account_code' => '511'],
    ];

    public function recordCollection(array $data): JournalEntry
    {
        $type = self::COLLECTION_TYPES[$data['type']] ?? null;

        if (! $type) {
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
                ['account_code' => $data['cash_account_code'] ?? $type['default_cash_account_code'], 'label' => 'Encaissement', 'debit' => $data['amount'], 'credit' => 0],
                ['account_code' => $type['revenue_account_code'], 'label' => $data['description'] ?? $type['label'], 'debit' => 0, 'credit' => $data['amount']],
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

        return DB::transaction(function () use ($data, $debit) {
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

            Audit::record('accounting.entry.posted', $entry, [
                'reference' => $entry->reference,
                'type' => $entry->type,
                'amount' => round($debit, 2),
                'currency' => $entry->currency,
            ], (int) $data['church_id']);

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
