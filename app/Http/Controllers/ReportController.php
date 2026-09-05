<?php

namespace App\Http\Controllers;

use App\Exports\FinancialStatementExport;
use App\Exports\TrialBalanceExport;
use App\Models\ChartOfAccount;
use App\Models\JournalEntryLine;
use App\Services\AccessScope;
use App\Services\Accounting\FinancialStatementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function trialBalancePdf(Request $request, AccessScope $scope): Response
    {
        $accounts = $this->trialBalanceData($request, $scope);

        return Pdf::loadView('reports.trial-balance', ['accounts' => $accounts])
            ->download('balance-sycebnl.pdf');
    }

    public function trialBalanceExcel(Request $request, AccessScope $scope): BinaryFileResponse
    {
        return Excel::download(new TrialBalanceExport($this->trialBalanceData($request, $scope)), 'balance-sycebnl.xlsx');
    }

    public function financialStatementsPdf(Request $request, FinancialStatementService $statements): Response
    {
        return Pdf::loadView('reports.financial-statements', [
            'statements' => $statements->statements($request->user()),
        ])->download('bilan-ohada-formation-resultat.pdf');
    }

    public function financialStatementsExcel(Request $request, FinancialStatementService $statements): BinaryFileResponse
    {
        return Excel::download(
            new FinancialStatementExport($statements->statements($request->user())),
            'bilan-ohada-formation-resultat.xlsx'
        );
    }

    /**
     * Balance generale a l'ecran (tous les comptes), avec controle
     * debit = credit et lien vers le grand livre de chaque compte.
     */
    public function trialBalance(Request $request, AccessScope $scope): InertiaResponse
    {
        $accounts = $this->trialBalanceData($request, $scope);

        return Inertia::render('Reports/TrialBalance', [
            'accounts' => $accounts,
            'totals' => [
                'debit' => round($accounts->sum('debit'), 2),
                'credit' => round($accounts->sum('credit'), 2),
            ],
            'generatedAt' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Etats financiers a l'ecran (bilan, compte de resultat, annexes OHADA).
     */
    public function financialStatements(Request $request, FinancialStatementService $statements): InertiaResponse
    {
        return Inertia::render('Reports/FinancialStatements', [
            'statements' => $statements->statements($request->user()),
        ]);
    }

    /**
     * Grand livre d'un compte particulier : toutes ses lignes d'ecriture,
     * dans le perimetre de l'utilisateur, avec solde cumule.
     */
    public function accountLedger(Request $request, ChartOfAccount $compte, AccessScope $scope): InertiaResponse
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $churchIds = $scope->churchIds($request->user());

        $lines = JournalEntryLine::query()
            ->where('chart_of_account_id', $compte->id)
            ->whereHas('entry', function ($query) use ($churchIds, $filters) {
                $query->when(is_array($churchIds), fn ($q) => $q->whereIn('church_id', $churchIds));
                $query->when($filters['from'] ?? null, fn ($q, $from) => $q->where('entry_date', '>=', $from));
                $query->when($filters['to'] ?? null, fn ($q, $to) => $q->where('entry_date', '<=', $to));
            })
            ->with('entry.church:id,designation')
            ->get()
            ->sortBy(fn (JournalEntryLine $line) => $line->entry?->entry_date?->format('Y-m-d').'-'.str_pad((string) $line->id, 10, '0', STR_PAD_LEFT))
            ->values();

        $running = 0;
        $rows = $lines->map(function (JournalEntryLine $line) use (&$running, $compte) {
            $movement = $compte->normal_side === 'debit'
                ? (float) $line->debit - (float) $line->credit
                : (float) $line->credit - (float) $line->debit;
            $running += $movement;

            return [
                'date' => optional($line->entry?->entry_date)->toDateString(),
                'reference' => $line->entry?->reference,
                'church' => $line->entry?->church?->designation,
                'label' => $line->label,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'balance' => round($running, 2),
            ];
        });

        return Inertia::render('Reports/AccountLedger', [
            'account' => $compte->only(['id', 'code', 'label', 'class', 'normal_side']),
            'accounts' => ChartOfAccount::orderBy('code')->get(['id', 'code', 'label']),
            'filters' => $filters,
            'rows' => $rows,
            'totals' => [
                'debit' => round($lines->sum(fn ($l) => (float) $l->debit), 2),
                'credit' => round($lines->sum(fn ($l) => (float) $l->credit), 2),
                'balance' => round($running, 2),
            ],
        ]);
    }

    private function trialBalanceData(Request $request, AccessScope $scope)
    {
        $churchIds = $scope->churchIds($request->user());

        return ChartOfAccount::query()
            ->withSum(['journalEntryLines as debit_total' => fn ($query) => $query
                ->when(is_array($churchIds), fn ($lineQuery) => $lineQuery->whereHas('entry', fn ($entryQuery) => $entryQuery->whereIn('church_id', $churchIds)))], 'debit')
            ->withSum(['journalEntryLines as credit_total' => fn ($query) => $query
                ->when(is_array($churchIds), fn ($lineQuery) => $lineQuery->whereHas('entry', fn ($entryQuery) => $entryQuery->whereIn('church_id', $churchIds)))], 'credit')
            ->orderBy('code')
            ->get()
            ->map(fn (ChartOfAccount $account) => [
                'id' => $account->id,
                'code' => $account->code,
                'label' => $account->label,
                'class' => (int) $account->class,
                'normal_side' => $account->normal_side,
                'debit' => (float) ($account->debit_total ?? 0),
                'credit' => (float) ($account->credit_total ?? 0),
                'balance' => round(
                    $account->normal_side === 'debit'
                        ? (float) ($account->debit_total ?? 0) - (float) ($account->credit_total ?? 0)
                        : (float) ($account->credit_total ?? 0) - (float) ($account->debit_total ?? 0),
                    2
                ),
            ]);
    }
}
