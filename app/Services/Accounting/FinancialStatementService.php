<?php

namespace App\Services\Accounting;

use App\Models\ChartOfAccount;
use App\Models\User;
use App\Services\AccessScope;
use Illuminate\Support\Collection;

class FinancialStatementService
{
    public function __construct(private readonly AccessScope $scope)
    {
    }

    public function statements(User $user): array
    {
        $accounts = $this->accounts($user);
        $expenses = $this->section($accounts, fn (array $account) => $account['class'] === 6, 'debit');
        $revenues = $this->section($accounts, fn (array $account) => $account['class'] === 7, 'credit');
        $netResult = round($revenues['total'] - $expenses['total'], 2);

        $assets = [
            'immobilisations' => $this->section($accounts, fn (array $account) => $account['class'] === 2, 'debit'),
            'stocks_creances' => $this->section($accounts, fn (array $account) => $account['class'] === 3 || str_starts_with($account['code'], '41'), 'debit'),
            'tresorerie' => $this->section($accounts, fn (array $account) => $account['class'] === 5, 'debit'),
        ];
        $liabilities = [
            'fonds_propres' => $this->section($accounts, fn (array $account) => $account['class'] === 1, 'credit'),
            'dettes_tiers' => $this->section($accounts, fn (array $account) => $account['class'] === 4 && ! str_starts_with($account['code'], '41'), 'credit'),
        ];

        $assetsTotal = collect($assets)->sum('total');
        $liabilitiesSubtotal = collect($liabilities)->sum('total');

        return [
            'generated_at' => now(),
            'balance_sheet' => [
                'assets' => $assets,
                'liabilities' => $liabilities,
                'net_result' => $netResult,
                'assets_total' => round($assetsTotal, 2),
                'liabilities_total' => round($liabilitiesSubtotal + $netResult, 2),
                'control_gap' => round($assetsTotal - ($liabilitiesSubtotal + $netResult), 2),
            ],
            'income_statement' => [
                'revenues' => $revenues,
                'expenses' => $expenses,
                'revenues_total' => $revenues['total'],
                'expenses_total' => $expenses['total'],
                'net_result' => $netResult,
            ],
            'annexes' => [
                'cash_and_bank' => $this->section($accounts, fn (array $account) => in_array($account['code'], ['501', '502', '511', '515'], true), 'debit'),
                'restricted_funds' => $this->section($accounts, fn (array $account) => str_starts_with($account['code'], '106'), 'credit'),
                'receivables' => $this->section($accounts, fn (array $account) => str_starts_with($account['code'], '411'), 'debit'),
                'payables' => $this->section($accounts, fn (array $account) => in_array($account['code'], ['401', '421', '431', '445', '465'], true), 'credit'),
                'control' => [
                    'assets_total' => round($assetsTotal, 2),
                    'liabilities_total' => round($liabilitiesSubtotal + $netResult, 2),
                    'control_gap' => round($assetsTotal - ($liabilitiesSubtotal + $netResult), 2),
                ],
            ],
        ];
    }

    private function accounts(User $user): Collection
    {
        $churchIds = $this->scope->churchIds($user);

        return ChartOfAccount::query()
            ->withSum(['journalEntryLines as debit_total' => fn ($query) => $query
                ->when(is_array($churchIds), fn ($lineQuery) => $lineQuery->whereHas('entry', fn ($entryQuery) => $entryQuery->whereIn('church_id', $churchIds)))], 'debit')
            ->withSum(['journalEntryLines as credit_total' => fn ($query) => $query
                ->when(is_array($churchIds), fn ($lineQuery) => $lineQuery->whereHas('entry', fn ($entryQuery) => $entryQuery->whereIn('church_id', $churchIds)))], 'credit')
            ->orderBy('code')
            ->get()
            ->map(fn (ChartOfAccount $account) => [
                'code' => $account->code,
                'label' => $account->label,
                'class' => (int) $account->class,
                'normal_side' => $account->normal_side,
                'debit' => (float) ($account->debit_total ?? 0),
                'credit' => (float) ($account->credit_total ?? 0),
            ]);
    }

    private function section(Collection $accounts, callable $filter, string $side): array
    {
        $rows = $accounts
            ->filter($filter)
            ->map(function (array $account) use ($side) {
                $amount = $side === 'debit'
                    ? $account['debit'] - $account['credit']
                    : $account['credit'] - $account['debit'];

                return [
                    'code' => $account['code'],
                    'label' => $account['label'],
                    'amount' => round($amount, 2),
                ];
            })
            ->filter(fn (array $account) => abs($account['amount']) > 0)
            ->values();

        return [
            'rows' => $rows,
            'total' => round($rows->sum('amount'), 2),
        ];
    }
}
