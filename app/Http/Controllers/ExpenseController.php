<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Expense;
use App\Services\AccessScope;
use App\Services\Accounting\AccountingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(Request $request, AccessScope $scope): Response
    {
        $churchIds = $scope->churchIds($request->user());

        return Inertia::render('Expenses/Index', [
            'churches' => $scope->churches($request->user()),
            'budgets' => Budget::select('id', 'church_id', 'name', 'currency')->when(is_array($churchIds), fn ($query) => $query->whereIn('church_id', $churchIds))->orderBy('name')->get(),
            'expenses' => $scope->scopeChurchOwned(Expense::with('church:id,designation', 'budget:id,name', 'journalEntry:id,reference'), $request->user())->latest('expense_date')->paginate(15)->withQueryString(),
        ]);
    }

    public function store(Request $request, AccountingService $accounting, AccessScope $scope): RedirectResponse
    {
        $data = $request->validate([
            'church_id' => ['required', 'exists:churches,id'],
            'budget_id' => ['nullable', 'exists:budgets,id'],
            'description' => ['required', 'string', 'max:255'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:120'],
            'currency' => ['required', Rule::in(['USD', 'CDF'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'exchange_rate' => ['required', 'numeric', 'min:1'],
            'expense_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(['cash', 'bank', 'card', 'mobile_money'])],
            'status' => ['required', Rule::in(['draft', 'approved', 'paid'])],
        ]);

        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);
        $entry = null;
        if ($data['status'] === 'paid') {
            $creditAccount = match ($data['payment_method']) {
                'bank', 'card' => '501',
                'mobile_money' => '515',
                default => '511',
            };
            $entry = $accounting->recordBalancedEntry([
                'church_id' => $data['church_id'],
                'type' => 'expense',
                'entry_date' => $data['expense_date'],
                'description' => $data['description'],
                'currency' => $data['currency'],
                'exchange_rate' => $data['exchange_rate'],
                'created_by' => $request->user()?->id,
                'lines' => [
                    ['account_code' => '601', 'label' => $data['category'], 'debit' => $data['amount'], 'credit' => 0],
                    ['account_code' => $creditAccount, 'label' => $data['payment_method'], 'debit' => 0, 'credit' => $data['amount']],
                ],
            ]);
        }

        Expense::create([...$data, 'journal_entry_id' => $entry?->id]);

        return back()->with('success', $entry ? 'Depense payee avec ecriture comptable.' : 'Depense enregistree sans decaissement.');
    }
}
