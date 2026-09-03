<?php

namespace App\Http\Controllers;

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Services\AccessScope;
use App\Support\Audit;
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
            'members' => $scope->scopeChurchOwned(Member::with('church:id,designation'), $request->user())->latest()->paginate(15)->withQueryString(),
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

    public function update(Request $request, Member $membre, AccessScope $scope): RedirectResponse
    {
        $scope->ensureChurchAllowed($request->user(), (int) $membre->church_id);

        // Le statut n'est pas modifie ici : il passe par la promotion dediee.
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
        $membre->update($data);

        Audit::record('member.updated', $membre, ['church_id' => (int) $membre->church_id]);

        return back()->with('success', 'Membre mis a jour.');
    }

    public function destroy(Request $request, Member $membre, AccessScope $scope): RedirectResponse
    {
        $scope->ensureChurchAllowed($request->user(), (int) $membre->church_id);

        $snapshot = [
            'name' => trim("{$membre->last_name} {$membre->middle_name} {$membre->first_name}"),
            'church_id' => (int) $membre->church_id,
        ];
        $membre->delete();

        Audit::record('member.deleted', $membre, $snapshot);

        return back()->with('success', 'Membre supprime.');
    }

    public function promote(Request $request, Member $member, AccessScope $scope): RedirectResponse
    {
        $scope->ensureChurchAllowed($request->user(), (int) $member->church_id);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_column(MemberStatus::cases(), 'value'))],
        ]);

        $previous = $member->status->value;
        $member->update(['status' => $data['status']]);

        Audit::record('member.promoted', $member, [
            'from' => $previous,
            'to' => $data['status'],
        ]);

        return back()->with('success', 'Statut membre mis a jour.');
    }
}
