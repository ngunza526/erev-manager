<?php

namespace App\Http\Controllers;

use App\Models\MinistryGroup;
use App\Services\AccessScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MinistryGroupController extends Controller
{
    public function index(Request $request, AccessScope $scope): Response
    {
        return Inertia::render('Groups/Index', [
            'churches' => $scope->churches($request->user()),
            'groups' => $scope->scopeChurchOwned(MinistryGroup::with('church:id,designation'), $request->user())->latest()->paginate(15)->withQueryString(),
        ]);
    }

    public function store(Request $request, AccessScope $scope): RedirectResponse
    {
        $data = $request->validate([
            'church_id' => ['required', 'exists:churches,id'],
            'name' => ['required', 'string', 'max:255'],
            'group_type' => ['required', 'string', 'max:120'],
            'leader_name' => ['required', 'string', 'max:255'],
            'meeting_day' => ['nullable', 'string', 'max:80'],
            'district' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'members_count' => ['nullable', 'integer', 'min:0'],
        ]);
        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);
        MinistryGroup::create($data);

        return back()->with('success', 'Groupe ministeriel cree.');
    }
}
