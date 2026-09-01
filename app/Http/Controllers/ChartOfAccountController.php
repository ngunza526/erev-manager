<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
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
            'accounts' => ChartOfAccount::orderBy('code')->paginate(30),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ChartOfAccount::create($this->validated($request));

        return back()->with('success', 'Compte comptable cree.');
    }

    public function update(Request $request, ChartOfAccount $planComptable): RedirectResponse
    {
        $planComptable->update($this->validated($request, $planComptable));

        return back()->with('success', 'Compte comptable mis a jour.');
    }

    public function destroy(ChartOfAccount $planComptable): RedirectResponse
    {
        abort_if($planComptable->is_system, 422, 'Un compte systeme ne peut pas etre supprime.');
        $planComptable->delete();

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
