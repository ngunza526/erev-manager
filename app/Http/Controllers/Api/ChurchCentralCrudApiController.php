<?php

namespace App\Http\Controllers\Api;

use App\Enums\MemberStatus;
use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\ChurchEvent;
use App\Models\ChurchService;
use App\Models\Expense;
use App\Models\Member;
use App\Models\MinistryGroup;
use App\Services\Accounting\AccountingService;
use App\Services\AccessScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChurchCentralCrudApiController extends Controller
{
    public function storeMember(Request $request, AccessScope $scope): JsonResponse
    {
        $data = $request->validate($this->memberRules());
        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);
        $data['status'] = MemberStatus::Sympathisant->value;

        return response()->json(['data' => Member::create($data)->load('church:id,designation')], 201);
    }

    public function updateMember(Request $request, Member $member, AccessScope $scope): JsonResponse
    {
        $scope->ensureChurchAllowed($request->user(), (int) $member->church_id);
        $data = $request->validate($this->memberRules());
        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);
        $member->update($data);

        return response()->json(['data' => $member->fresh('church:id,designation')]);
    }

    public function storeService(Request $request, AccessScope $scope): JsonResponse
    {
        $data = $request->validate($this->serviceRules());
        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);

        return response()->json(['data' => ChurchService::create($data)->load('church:id,designation')], 201);
    }

    public function updateService(Request $request, ChurchService $service, AccessScope $scope): JsonResponse
    {
        $scope->ensureChurchAllowed($request->user(), (int) $service->church_id);
        $data = $request->validate($this->serviceRules());
        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);
        $service->update($data);

        return response()->json(['data' => $service->fresh('church:id,designation')]);
    }

    public function storeGroup(Request $request, AccessScope $scope): JsonResponse
    {
        $data = $request->validate($this->groupRules());
        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);

        return response()->json(['data' => MinistryGroup::create($data)->load('church:id,designation')], 201);
    }

    public function updateGroup(Request $request, MinistryGroup $group, AccessScope $scope): JsonResponse
    {
        $scope->ensureChurchAllowed($request->user(), (int) $group->church_id);
        $data = $request->validate($this->groupRules());
        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);
        $group->update($data);

        return response()->json(['data' => $group->fresh('church:id,designation')]);
    }

    public function storeEvent(Request $request, AccessScope $scope): JsonResponse
    {
        $data = $request->validate($this->eventRules());
        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);

        return response()->json(['data' => ChurchEvent::create($data)->load('church:id,designation')], 201);
    }

    public function updateEvent(Request $request, ChurchEvent $event, AccessScope $scope): JsonResponse
    {
        $scope->ensureChurchAllowed($request->user(), (int) $event->church_id);
        $data = $request->validate($this->eventRules());
        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);
        $event->update($data);

        return response()->json(['data' => $event->fresh('church:id,designation')]);
    }

    public function storeBudget(Request $request, AccessScope $scope): JsonResponse
    {
        $data = $request->validate($this->budgetRules());
        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);

        return response()->json(['data' => Budget::create($data)->load('church:id,designation')], 201);
    }

    public function updateBudget(Request $request, Budget $budget, AccessScope $scope): JsonResponse
    {
        $scope->ensureChurchAllowed($request->user(), (int) $budget->church_id);
        $data = $request->validate($this->budgetRules());
        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);
        $budget->update($data);

        return response()->json(['data' => $budget->fresh('church:id,designation')]);
    }

    public function storeExpense(Request $request, AccessScope $scope, AccountingService $accounting): JsonResponse
    {
        $data = $request->validate($this->expenseRules());
        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);
        $entry = $this->expenseEntry($data, $request, $accounting);

        return response()->json(['data' => Expense::create([...$data, 'journal_entry_id' => $entry?->id])->load('church:id,designation', 'journalEntry:id,reference')], 201);
    }

    public function updateExpense(Request $request, Expense $expense, AccessScope $scope): JsonResponse
    {
        $scope->ensureChurchAllowed($request->user(), (int) $expense->church_id);
        $data = $request->validate($this->expenseRules());
        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);
        $expense->update($data);

        return response()->json(['data' => $expense->fresh('church:id,designation', 'journalEntry:id,reference')]);
    }

    private function memberRules(): array
    {
        return [
            'church_id' => ['required', 'exists:churches,id'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'sex' => ['required', 'string', 'max:40'],
            'birth_date' => ['required', 'date'],
            'birth_place' => ['required', 'string', 'max:255'],
            'profession' => ['required', 'string', 'max:255'],
            'marital_status' => ['required', 'string', 'max:80'],
            'spouse' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_column(MemberStatus::cases(), 'value'))],
        ];
    }

    private function serviceRules(): array
    {
        return [
            'church_id' => ['required', 'exists:churches,id'],
            'title' => ['required', 'string', 'max:255'],
            'service_type' => ['required', 'string', 'max:120'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'preacher' => ['nullable', 'string', 'max:255'],
            'worship_leader' => ['nullable', 'string', 'max:255'],
            'attendance_count' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    private function groupRules(): array
    {
        return [
            'church_id' => ['required', 'exists:churches,id'],
            'name' => ['required', 'string', 'max:255'],
            'group_type' => ['required', 'string', 'max:120'],
            'leader_name' => ['required', 'string', 'max:255'],
            'meeting_day' => ['nullable', 'string', 'max:80'],
            'district' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'members_count' => ['nullable', 'integer', 'min:0'],
        ];
    }

    private function eventRules(): array
    {
        return [
            'church_id' => ['required', 'exists:churches,id'],
            'title' => ['required', 'string', 'max:255'],
            'event_type' => ['required', 'string', 'max:120'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'venue' => ['required', 'string', 'max:255'],
            'currency' => ['required', Rule::in(['USD', 'CDF'])],
            'ticket_price' => ['nullable', 'numeric', 'min:0'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'registrations_count' => ['nullable', 'integer', 'min:0'],
            'is_public' => ['boolean'],
        ];
    }

    private function budgetRules(): array
    {
        return [
            'church_id' => ['required', 'exists:churches,id'],
            'name' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', Rule::in(['USD', 'CDF'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'period_starts_at' => ['required', 'date'],
            'period_ends_at' => ['required', 'date', 'after_or_equal:period_starts_at'],
            'status' => ['required', Rule::in(['draft', 'active', 'closed'])],
        ];
    }

    private function expenseRules(): array
    {
        return [
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
        ];
    }

    private function expenseEntry(array $data, Request $request, AccountingService $accounting)
    {
        if ($data['status'] !== 'paid') {
            return null;
        }

        $creditAccount = match ($data['payment_method']) {
            'bank', 'card' => '501',
            'mobile_money' => '515',
            default => '511',
        };

        return $accounting->recordBalancedEntry([
            'church_id' => $data['church_id'],
            'type' => 'expense_api',
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
}
