<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Services\AccessScope;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CommunityController extends Controller
{
    public function index(Request $request, AccessScope $scope): Response
    {
        return Inertia::render('Communities/Index', [
            'communities' => $scope->scopeCommunityOwned(Community::withCount('churches'), $request->user())->latest()->paginate(12)->withQueryString(),
        ]);
    }

    public function store(Request $request, AccessScope $scope): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->user()?->level === 'coordination' && $request->user()?->community_id) {
            $scope->ensureCommunityAllowed($request->user(), $request->user()->community_id);
        }

        $community = Community::create($data);

        Audit::record('reference.community.created', $community, [
            'designation' => $community->designation,
            'authorization_number' => $community->authorization_number,
        ]);

        return back()->with('success', 'Communaute creee.');
    }

    public function update(Request $request, Community $communaute, AccessScope $scope): RedirectResponse
    {
        if ($request->user()?->level === 'coordination') {
            $scope->ensureCommunityAllowed($request->user(), $communaute->id);
        }

        $data = $this->validated($request, $communaute);
        $before = $communaute->only(array_keys($data));
        $communaute->update($data);

        Audit::record('reference.community.updated', $communaute, ['from' => $before, 'to' => $data]);

        return back()->with('success', 'Communaute mise a jour.');
    }

    private function validated(Request $request, ?Community $community = null): array
    {
        return $request->validate([
            'designation' => ['required', 'string', 'max:255'],
            'headquarters_number' => ['nullable', 'string', 'max:80'],
            'headquarters_avenue' => ['nullable', 'string', 'max:255'],
            'headquarters_district' => ['nullable', 'string', 'max:255'],
            'headquarters_city' => ['required', 'string', 'max:255'],
            'headquarters_province' => ['required', 'string', 'max:255'],
            'headquarters_country' => ['required', 'string', 'max:255'],
            'authorization_number' => ['required', 'string', 'max:255', Rule::unique('communities', 'authorization_number')->ignore($community?->id)],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
        ]);
    }
}
