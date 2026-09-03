<?php

namespace App\Services;

use App\Models\Church;
use App\Models\Community;
use App\Models\User;
use App\Support\Rbac;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class WorkspaceContext
{
    public function canSwitch(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasRole(Rbac::SUPERADMIN_PLATEFORME)
            || $user->can(Rbac::WORKSPACE_SWITCH);
    }

    public function space(?User $user, ?Request $request = null): ?string
    {
        if (! $user) {
            return null;
        }

        if ($this->canSwitch($user)) {
            $sessionSpace = $this->sessionValue($request, 'space');

            if (in_array($sessionSpace, ['communaute', 'eglise'], true)) {
                return $sessionSpace;
            }
        }

        return in_array($user->level, [Rbac::LEVEL_COORDINATION, Rbac::LEVEL_PLATFORM], true)
            ? 'communaute'
            : 'eglise';
    }

    public function spaceLabel(?User $user, ?Request $request = null): ?string
    {
        return match ($this->space($user, $request)) {
            'communaute' => 'Communaute',
            'eglise' => 'Eglise',
            default => null,
        };
    }

    public function communityId(?User $user, ?Request $request = null): ?int
    {
        if (! $user) {
            return null;
        }

        if ($user->community_id) {
            return (int) $user->community_id;
        }

        if ($user->church_id) {
            return Church::whereKey($user->church_id)->value('community_id');
        }

        return null;
    }

    public function churchId(?User $user, ?Request $request = null): ?int
    {
        if (! $user) {
            return null;
        }

        if (! $this->canSwitch($user)) {
            return $user->level === 'eglise' ? $user->church_id : null;
        }

        if ($this->space($user, $request) !== 'eglise') {
            return null;
        }

        $requestedChurchId = $this->sessionValue($request, 'church_id') ?: $user->church_id;
        $allowedChurchIds = $this->switchableChurchIds($user);

        if ($requestedChurchId && in_array((int) $requestedChurchId, $allowedChurchIds, true)) {
            return (int) $requestedChurchId;
        }

        return $allowedChurchIds[0] ?? null;
    }

    public function churchIds(?User $user, ?Request $request = null): ?array
    {
        if (! $user) {
            return [];
        }

        // Le role technique plateforme opere sans restriction de perimetre.
        if ($user->level === Rbac::LEVEL_PLATFORM) {
            return null;
        }

        if ($this->canSwitch($user)) {
            if ($this->space($user, $request) === 'communaute') {
                return $this->switchableChurchIds($user);
            }

            $churchId = $this->churchId($user, $request);

            return $churchId ? [$churchId] : [];
        }

        if ($user->level === 'eglise') {
            return $user->church_id ? [(int) $user->church_id] : [];
        }

        if ($user->level === 'coordination' && $user->community_id) {
            return Church::where('community_id', $user->community_id)->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        return null;
    }

    public function communityIds(?User $user, ?Request $request = null): ?array
    {
        if (! $user) {
            return [];
        }

        if ($user->level === Rbac::LEVEL_PLATFORM) {
            return null;
        }

        $communityId = $this->communityId($user, $request);

        if ($communityId) {
            return [$communityId];
        }

        return $user->level === 'coordination' ? null : [];
    }

    public function switchableChurches(?User $user): Collection
    {
        $communityId = $this->communityId($user);

        return Church::select('id', 'designation', 'community_id')
            ->when($communityId, fn ($query) => $query->where('community_id', $communityId))
            ->orderBy('designation')
            ->get();
    }

    public function switcher(?User $user, ?Request $request = null): array
    {
        if (! $this->canSwitch($user)) {
            return ['can_switch' => false];
        }

        $ownCommunityId = $this->communityId($user, $request);
        $space = $this->space($user, $request);
        $churchId = $this->churchId($user, $request);

        // Communautes selectionnables : celle d'attache si fixee, sinon toutes
        // (SuperAdmin plateforme).
        $communities = Community::select('id', 'designation')
            ->when($ownCommunityId, fn ($query) => $query->whereKey($ownCommunityId))
            ->orderBy('designation')
            ->get();

        // Communaute active : fixee, sinon celle memorisee en session.
        $activeCommunityId = $ownCommunityId
            ?: ((int) $this->sessionValue($request, 'community_id') ?: null);

        return [
            'can_switch' => true,
            'active_space' => $space,
            'active_church_id' => $churchId,
            'active_community_id' => $activeCommunityId,
            'active_value' => $space === 'communaute'
                ? "communaute:{$activeCommunityId}"
                : "eglise:{$churchId}",
            'community' => $communities->firstWhere('id', $activeCommunityId),
            'communities' => $communities,
            'churches' => $this->switchableChurches($user),
        ];
    }

    public function switchableChurchIds(?User $user): array
    {
        return $this->switchableChurches($user)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function sessionValue(?Request $request, string $key): mixed
    {
        $request ??= request();

        if (! $request->hasSession()) {
            return null;
        }

        return $request->session()->get("workspace.{$key}");
    }
}
