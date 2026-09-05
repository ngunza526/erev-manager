<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ChartOfAccountController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('ChartOfAccounts/Index', [
            'accounts' => ChartOfAccount::orderBy('code')->paginate(30)->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $account = ChartOfAccount::create($this->validated($request));

        Audit::record('reference.chart_of_account.created', $account, [
            'code' => $account->code,
            'label' => $account->label,
        ]);

        return back()->with('success', 'Compte comptable cree.');
    }

    public function update(Request $request, ChartOfAccount $planComptable): RedirectResponse
    {
        $before = $planComptable->only(['code', 'label', 'class', 'normal_side', 'is_active']);
        $planComptable->update($this->validated($request, $planComptable));

        Audit::record('reference.chart_of_account.updated', $planComptable, [
            'from' => $before,
            'to' => $planComptable->only(['code', 'label', 'class', 'normal_side', 'is_active']),
        ]);

        return back()->with('success', 'Compte comptable mis a jour.');
    }

    public function destroy(ChartOfAccount $planComptable): RedirectResponse
    {
        abort_if($planComptable->is_system, 422, 'Un compte systeme ne peut pas etre supprime.');

        $snapshot = ['code' => $planComptable->code, 'label' => $planComptable->label];
        $planComptable->delete();

        Audit::record('reference.chart_of_account.deleted', $planComptable, $snapshot);

        return back()->with('success', 'Compte comptable supprime.');
    }

    private function validated(Request $request, ?ChartOfAccount $account = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('chart_of_accounts', 'code')->ignore($account)],
            'label' => ['required', 'string', 'max:255'],
            'class' => ['required', 'integer', 'between:1,7'],
            'normal_side' => ['required', Rule::in(['debit', 'credit'])],
            'is_active' => ['boolean'],
        ]);
    }
}
