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
    public function index(Request $request, AccessScope $scope): Response
    {
        return Inertia::render('Accounting/Index', [
            'churches' => $scope->churches($request->user()),
            'accounts' => ChartOfAccount::orderBy('code')->get(),
            'entries' => $scope->scopeChurchOwned(JournalEntry::with('church:id,designation', 'lines.account'), $request->user())->latest()->paginate(12)->withQueryString(),
        ]);
    }

    public function collection(Request $request, AccountingService $accounting, AccessScope $scope): RedirectResponse
    {
        $data = $request->validate([
            'church_id' => ['required', 'exists:churches,id'],
            'type' => ['required', Rule::in(['dime', 'offrande', 'don'])],
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
}
