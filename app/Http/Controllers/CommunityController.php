<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Services\AccessScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommunityController extends Controller
{
    public function index(Request $request, AccessScope $scope): Response
    {
        return Inertia::render('Communities/Index', [
            'communities' => $scope->scopeCommunityOwned(Community::withCount('churches'), $request->user())->latest()->paginate(12),
        ]);
    }

    public function store(Request $request, AccessScope $scope): RedirectResponse
    {
        $data = $request->validate([
            'designation' => ['required', 'string', 'max:255'],
            'headquarters_number' => ['nullable', 'string', 'max:80'],
            'headquarters_avenue' => ['nullable', 'string', 'max:255'],
            'headquarters_district' => ['nullable', 'string', 'max:255'],
            'headquarters_city' => ['required', 'string', 'max:255'],
            'headquarters_province' => ['required', 'string', 'max:255'],
            'headquarters_country' => ['required', 'string', 'max:255'],
            'authorization_number' => ['required', 'string', 'max:255', 'unique:communities,authorization_number'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
        ]);
        if ($request->user()?->level === 'coordination' && $request->user()?->community_id) {
            $scope->ensureCommunityAllowed($request->user(), $request->user()->community_id);
        }

        Community::create($data);

        return back()->with('success', 'Communaute creee.');
    }
}
