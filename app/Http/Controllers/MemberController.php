<?php

namespace App\Http\Controllers;

use App\Enums\MemberStatus;
use App\Models\Church;
use App\Models\Member;
use App\Services\AccessScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function index(Request $request, AccessScope $scope): Response
    {
        return Inertia::render('Members/Index', [
            'members' => $scope->scopeChurchOwned(Member::with('church:id,designation'), $request->user())->latest()->paginate(15),
            'churches' => $scope->churches($request->user()),
            'statuses' => array_column(MemberStatus::cases(), 'value'),
        ]);
    }

    public function store(Request $request, AccessScope $scope): RedirectResponse
    {
        $data = $request->validate([
            'church_id' => ['required', 'exists:churches,id'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'sex' => ['required', Rule::in(['Masculin', 'Feminin'])],
            'birth_date' => ['required', 'date'],
            'birth_place' => ['required', 'string', 'max:255'],
            'profession' => ['required', 'string', 'max:255'],
            'marital_status' => ['required', 'string', 'max:80'],
            'spouse' => ['required_if:marital_status,Marie(e)', 'nullable', 'string', 'max:255'],
            'baptism_date' => ['nullable', 'date'],
            'baptism_place' => ['nullable', 'string', 'max:255'],
            'baptism_church' => ['nullable', 'string', 'max:255'],
            'identity_type' => ['nullable', 'string', 'max:120'],
            'identity_number' => ['nullable', 'string', 'max:120'],
            'identity_issued_at' => ['nullable', 'date'],
            'identity_issuer' => ['nullable', 'string', 'max:255'],
        ]);

        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);
        $data['status'] = MemberStatus::Sympathisant->value;
        Member::create($data);

        return back()->with('success', 'Membre cree avec le statut sympathisant.');
    }

    public function promote(Request $request, Member $member, AccessScope $scope): RedirectResponse
    {
        $scope->ensureChurchAllowed($request->user(), (int) $member->church_id);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_column(MemberStatus::cases(), 'value'))],
        ]);

        $member->update(['status' => $data['status']]);

        return back()->with('success', 'Statut membre mis a jour.');
    }
}
