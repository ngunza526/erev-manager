<?php

namespace App\Services;

use App\Models\Church;
use App\Models\Community;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AccessScope
{
    public function __construct(private WorkspaceContext $context)
    {
    }

    public function churchIds(?User $user): ?array
    {
        return $this->context->churchIds($user, request());
    }

    public function communityIds(?User $user): ?array
    {
        return $this->context->communityIds($user, request());
    }

    public function churches(?User $user): Collection
    {
        $ids = $this->churchIds($user);

        return Church::select('id', 'designation', 'community_id')
            ->when(is_array($ids), fn (Builder $query) => $query->whereIn('id', $ids))
            ->orderBy('designation')
            ->get();
    }

    public function communities(?User $user): Collection
    {
        $ids = $this->communityIds($user);

        return Community::select('id', 'designation')
            ->when(is_array($ids), fn (Builder $query) => $query->whereIn('id', $ids))
            ->orderBy('designation')
            ->get();
    }

    public function scopeChurchOwned(Builder $query, ?User $user, string $column = 'church_id'): Builder
    {
        $ids = $this->churchIds($user);

        return is_array($ids) ? $query->whereIn($column, $ids) : $query;
    }

    public function scopeCommunityOwned(Builder $query, ?User $user, string $column = 'community_id'): Builder
    {
        $ids = $this->communityIds($user);

        return is_array($ids) ? $query->whereIn($column, $ids) : $query;
    }

    public function ensureChurchAllowed(?User $user, int $churchId): void
    {
        $ids = $this->churchIds($user);

        if (is_array($ids) && ! in_array($churchId, $ids, true)) {
            throw ValidationException::withMessages(['church_id' => 'Cette eglise est hors de votre perimetre.']);
        }
    }

    public function ensureCommunityAllowed(?User $user, int $communityId): void
    {
        $ids = $this->communityIds($user);

        if (is_array($ids) && ! in_array($communityId, $ids, true)) {
            throw ValidationException::withMessages(['community_id' => 'Cette communaute est hors de votre perimetre.']);
        }
    }
}
