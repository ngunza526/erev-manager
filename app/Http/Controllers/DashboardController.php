<?php

namespace App\Http\Controllers;

use App\Models\Church;
use App\Models\Community;
use App\Models\JournalEntry;
use App\Models\Member;
use App\Models\User;
use App\Services\AccessScope;
use App\Services\WorkspaceContext;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function __invoke(AccessScope $scope, WorkspaceContext $workspace): Response
    {
        $user = request()->user();
        $churchIds = $scope->churchIds($user);
        $communityIds = $scope->communityIds($user);
        $isCommunitySpace = $workspace->space($user, request()) === 'communaute';

        if ($isCommunitySpace) {
            return Inertia::render('Dashboard', [
                'workspace' => 'communaute',
                'metrics' => [
                    'communities' => Community::query()->when(is_array($communityIds), fn ($query) => $query->whereIn('id', $communityIds))->count(),
                    'churches' => Church::query()->when(is_array($churchIds), fn ($query) => $query->whereIn('id', $churchIds))->count(),
                    'users' => User::query()
                        ->when(is_array($communityIds), fn ($query) => $query->whereIn('community_id', $communityIds))
                        ->count(),
                    'roles' => Role::query()->count(),
                ],
                'recentEntries' => [],
            ]);
        }

        return Inertia::render('Dashboard', [
            'workspace' => 'eglise',
            'metrics' => [
                'churches' => Church::query()->when(is_array($churchIds), fn ($query) => $query->whereIn('id', $churchIds))->count(),
                'members' => Member::query()->when(is_array($churchIds), fn ($query) => $query->whereIn('church_id', $churchIds))->count(),
                'effectives' => Member::query()->where('status', 'effectif')->when(is_array($churchIds), fn ($query) => $query->whereIn('church_id', $churchIds))->count(),
                'entries' => JournalEntry::query()->when(is_array($churchIds), fn ($query) => $query->whereIn('church_id', $churchIds))->count(),
            ],
            'recentEntries' => JournalEntry::with('church:id,designation')->when(is_array($churchIds), fn ($query) => $query->whereIn('church_id', $churchIds))->latest()->take(8)->get(),
        ]);
    }
}
