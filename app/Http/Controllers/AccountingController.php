<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Services\AccessScope;
use App\Services\Accounting\AccountingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AccountingController extends Controller
{
    /**
     * Alias historique de /comptabilite : redirige vers le journal, ecran
     * par defaut du sous-menu Comptabilite.
     */
    public function index(Request $request, AccessScope $scope): Response
    {
        return $this->journal($request, $scope);
    }

    public function collecte(Request $request, AccessScope $scope): Response
    {
        return Inertia::render('Accounting/Collecte', [
            'churches' => $scope->churches($request->user()),
            'cashAccounts' => ChartOfAccount::where('class', 5)->orderBy('code')->get(),
            'collectionTypes' => $this->collectionTypesForFrontend(),
            'entries' => $scope->scopeChurchOwned(
                JournalEntry::with('church:id,designation', 'lines.account')
                    ->whereIn('type', array_keys(AccountingService::COLLECTION_TYPES)),
                $request->user()
            )->latest()->paginate(12)->withQueryString(),
        ]);
    }

    public function saisie(Request $request, AccessScope $scope): Response
    {
        return Inertia::render('Accounting/Saisie', [
            'churches' => $scope->churches($request->user()),
            'accounts' => ChartOfAccount::orderBy('code')->get(),
            'entries' => $scope->scopeChurchOwned(
                JournalEntry::with('church:id,designation', 'lines.account')->where('type', 'manual'),
                $request->user()
            )->latest()->paginate(12)->withQueryString(),
        ]);
    }

    public function journal(Request $request, AccessScope $scope): Response
    {
        return Inertia::render('Accounting/Journal', [
            'entries' => $scope->scopeChurchOwned(JournalEntry::with('church:id,designation', 'lines.account'), $request->user())->latest()->paginate(12)->withQueryString(),
        ]);
    }

    public function collection(Request $request, AccountingService $accounting, AccessScope $scope): RedirectResponse
    {
        $data = $request->validate([
            'church_id' => ['required', 'exists:churches,id'],
            'type' => ['required', Rule::in(array_keys(AccountingService::COLLECTION_TYPES))],
            'cash_account_code' => ['nullable', 'exists:chart_of_accounts,code'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', Rule::in(['USD', 'CDF'])],
            'exchange_rate' => ['required', 'numeric', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);
        $data['created_by'] = $request->user()?->id;
        $accounting->recordCollection($data);

        return back()->with('success', 'Collecte comptabilisee en partie double.');
    }

    public function manualEntry(Request $request, AccountingService $accounting, AccessScope $scope): RedirectResponse
    {
        $data = $request->validate([
            'church_id' => ['required', 'exists:churches,id'],
            'entry_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'currency' => ['required', Rule::in(['USD', 'CDF'])],
            'exchange_rate' => ['required', 'numeric', 'min:1'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_code' => ['required', 'exists:chart_of_accounts,code'],
            'lines.*.label' => ['required', 'string', 'max:255'],
            'lines.*.debit' => ['required', 'numeric', 'min:0'],
            'lines.*.credit' => ['required', 'numeric', 'min:0'],
        ]);

        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);
        $data['type'] = 'manual';
        $data['created_by'] = $request->user()?->id;
        $accounting->recordBalancedEntry($data);

        return back()->with('success', 'Ecriture manuelle debit-credit validee.');
    }

    /**
     * Types de collecte + libelle + caisse/compte par defaut, au format
     * attendu par le formulaire Vue (une entree par type, code inclus).
     */
    private function collectionTypesForFrontend(): array
    {
        return collect(AccountingService::COLLECTION_TYPES)
            ->map(fn (array $type, string $code) => [
                'code' => $code,
                'label' => $type['label'],
                'default_cash_account_code' => $type['default_cash_account_code'],
            ])
            ->values()
            ->all();
    }
}
