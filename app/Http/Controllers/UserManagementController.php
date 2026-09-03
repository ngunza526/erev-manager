<?php

namespace App\Http\Controllers;

use App\Enums\MemberStatus;
use App\Models\Church;
use App\Models\Member;
use App\Models\User;
use App\Services\AccessScope;
use App\Services\WorkspaceContext;
use App\Support\Audit;
use App\Support\Rbac;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(Request $request, AccessScope $scope, WorkspaceContext $workspace): Response
    {
        $churchIds = $scope->churchIds($request->user());
        $communityIds = $scope->communityIds($request->user());

        return Inertia::render('Users/Index', [
            'workspace' => $workspace->space($request->user(), $request),
            'users' => User::with('member:id,last_name,middle_name,first_name,church_id', 'church:id,designation', 'community:id,designation', 'roles:id,name')
                ->when(is_array($churchIds), fn ($query) => $query->where(function ($inner) use ($churchIds, $communityIds) {
                    $inner->whereIn('church_id', $churchIds);
                    if (is_array($communityIds)) {
                        $inner->orWhereIn('community_id', $communityIds);
                    }
                }))
                ->latest()
                ->paginate(12)
                ->withQueryString(),
            'members' => Member::where('status', MemberStatus::Effectif->value)
                ->when(is_array($churchIds), fn ($query) => $query->whereIn('church_id', $churchIds))
                ->with('church:id,designation,community_id')
                ->orderBy('last_name')
                ->get(),
            'churches' => $scope->churches($request->user()),
            'communities' => $scope->communities($request->user()),
            'roles' => [
                'coordination' => $this->rolesForLevel('coordination'),
                'eglise' => $this->rolesForLevel('eglise'),
            ],
        ]);
    }

    public function store(Request $request, AccessScope $scope): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'member_id' => ['nullable', 'exists:members,id'],
            'church_id' => ['nullable', 'exists:churches,id'],
            'community_id' => ['nullable', 'exists:communities,id'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults()],
            'level' => ['required', Rule::in(['coordination', 'eglise'])],
            'role' => ['required', 'string'],
        ]);

        abort_unless(in_array($data['role'], $this->rolesForLevel($data['level']), true), 422, 'Role incompatible avec cet espace.');

        $member = ! empty($data['member_id']) ? Member::with('church')->findOrFail($data['member_id']) : null;
        $church = null;
        $communityId = $request->user()?->community_id;

        if ($data['level'] === 'eglise') {
            $churchId = (int) ($data['church_id'] ?: $member?->church_id);
            abort_if($churchId === 0, 422, 'Une eglise est obligatoire pour creer un utilisateur eglise.');
            $scope->ensureChurchAllowed($request->user(), $churchId);
            $church = Church::findOrFail($churchId);
            abort_if($member && (int) $member->church_id !== $churchId, 422, 'Le membre choisi ne correspond pas a cette eglise.');
            abort_if($member && $member->status->value !== MemberStatus::Effectif->value, 422, 'Un utilisateur eglise lie a un membre doit etre un membre effectif.');
            $communityId = $church->community_id;
        }

        if ($data['level'] === 'coordination') {
            $communityId = (int) ($data['community_id'] ?: $communityId);
            abort_if($communityId === 0, 422, 'Une communaute est obligatoire pour creer un utilisateur communaute.');
            $scope->ensureCommunityAllowed($request->user(), $communityId);
        }
        abort_if(! $member && empty($data['name']), 422, 'Le nom est obligatoire pour un utilisateur non lie a un membre.');

        $user = User::create([
            'name' => $member?->full_name ?: $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'member_id' => $member?->id,
            'church_id' => $data['level'] === 'eglise' ? $church?->id : null,
            'community_id' => $communityId,
            'level' => $data['level'],
            'status' => 'actif',
        ]);

        $user->syncRoles([$data['role']]);

        Audit::record('user.created', $user, [
            'email' => $user->email,
            'level' => $data['level'],
            'role' => $data['role'],
            'church_id' => $user->church_id,
            'community_id' => $user->community_id,
        ], $user->church_id ? (int) $user->church_id : null);

        return back()->with('success', 'Utilisateur cree avec role et OTP requis.');
    }

    public function update(Request $request, User $utilisateur, AccessScope $scope): RedirectResponse
    {
        // Perimetre : l'admin doit pouvoir gerer le compte dans son etat actuel.
        if ($utilisateur->church_id) {
            $scope->ensureChurchAllowed($request->user(), (int) $utilisateur->church_id);
        } elseif ($utilisateur->community_id) {
            $scope->ensureCommunityAllowed($request->user(), (int) $utilisateur->community_id);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($utilisateur->id)],
            'level' => ['required', Rule::in(['coordination', 'eglise'])],
            'role' => ['required', 'string'],
            'status' => ['required', Rule::in(['actif', 'suspendu'])],
            'church_id' => ['nullable', 'exists:churches,id'],
            'community_id' => ['nullable', 'exists:communities,id'],
        ]);

        abort_unless(in_array($data['role'], $this->rolesForLevel($data['level']), true), 422, 'Role incompatible avec cet espace.');
        abort_if($utilisateur->id === $request->user()->id && $data['status'] !== 'actif', 422, 'Vous ne pouvez pas suspendre votre propre compte.');

        $church = null;
        $communityId = null;

        if ($data['level'] === 'eglise') {
            $churchId = (int) ($data['church_id'] ?: 0);
            abort_if($churchId === 0, 422, 'Une eglise est obligatoire pour un compte eglise.');
            $scope->ensureChurchAllowed($request->user(), $churchId);
            $church = Church::findOrFail($churchId);
            $communityId = (int) $church->community_id;
        } else {
            $communityId = (int) ($data['community_id'] ?: 0);
            abort_if($communityId === 0, 422, 'Une communaute est obligatoire pour un compte communaute.');
            $scope->ensureCommunityAllowed($request->user(), $communityId);
        }

        $utilisateur->update([
            'name' => $utilisateur->member_id ? $utilisateur->name : $data['name'],
            'email' => $data['email'],
            'church_id' => $data['level'] === 'eglise' ? $church?->id : null,
            'community_id' => $communityId,
            'level' => $data['level'],
            'status' => $data['status'],
        ]);

        $utilisateur->syncRoles([$data['role']]);

        Audit::record('user.updated', $utilisateur, [
            'email' => $utilisateur->email,
            'level' => $data['level'],
            'role' => $data['role'],
            'status' => $data['status'],
            'church_id' => $utilisateur->church_id,
            'community_id' => $utilisateur->community_id,
        ], $utilisateur->church_id ? (int) $utilisateur->church_id : null);

        return back()->with('success', 'Utilisateur mis a jour.');
    }

    /**
     * Roles attribuables via l'interface, par niveau de compte.
     * Le role technique "SuperAdmin plateforme" n'est jamais propose ici.
     */
    private function rolesForLevel(string $level): array
    {
        return match ($level) {
            'coordination' => [Rbac::ADMINISTRATEUR],
            'eglise' => [Rbac::ADMIN_FIN, Rbac::CAISSIER, Rbac::AUDITEUR, Rbac::SECRETAIRE],
            default => [],
        };
    }
}
