<?php

namespace App\Http\Controllers;

use App\Exports\TrialBalanceExport;
use App\Exports\FinancialStatementExport;
use App\Models\ChartOfAccount;
use App\Services\Accounting\FinancialStatementService;
use App\Services\AccessScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
                'code' => $account->code,
                'label' => $account->label,
                'debit' => (float) ($account->debit_total ?? 0),
                'credit' => (float) ($account->credit_total ?? 0),
            ]);
    }
}
