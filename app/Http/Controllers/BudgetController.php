<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Services\AccessScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function index(Request $request, AccessScope $scope): Response
    {
        return Inertia::render('Budgets/Index', [
            'churches' => $scope->churches($request->user()),
            'budgets' => $scope->scopeChurchOwned(Budget::with('church:id,designation')->withSum('expenses as spent_amount', 'amount'), $request->user())->latest()->paginate(15)->withQueryString(),
        ]);
    }

    public function store(Request $request, AccessScope $scope): RedirectResponse
    {
        $data = $request->validate([
            'church_id' => ['required', 'exists:churches,id'],
            'name' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', Rule::in(['USD', 'CDF'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'period_starts_at' => ['required', 'date'],
            'period_ends_at' => ['required', 'date', 'after_or_equal:period_starts_at'],
            'status' => ['required', Rule::in(['draft', 'active', 'closed'])],
        ]);
        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);
        Budget::create($data);

        return back()->with('success', 'Budget cree.');
    }
}
