<?php

namespace App\Http\Controllers;

use App\Models\Church;
use App\Services\AccessScope;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChurchController extends Controller
{
    public function index(Request $request, AccessScope $scope): Response
    {
        return Inertia::render('Churches/Index', [
            'churches' => $scope->scopeChurchOwned(Church::with('community:id,designation')->withCount('members'), $request->user(), 'id')->latest()->paginate(12)->withQueryString(),
            'communities' => $scope->communities($request->user()),
        ]);
    }

    public function store(Request $request, AccessScope $scope): RedirectResponse
    {
        $data = $request->validate([
            'community_id' => ['required', 'exists:communities,id'],
            'designation' => ['required', 'string', 'max:255'],
            'address_number' => ['nullable', 'string', 'max:80'],
            'address_avenue' => ['nullable', 'string', 'max:255'],
            'address_district' => ['required', 'string', 'max:255'],
            'address_city' => ['required', 'string', 'max:255'],
            'address_province' => ['required', 'string', 'max:255'],
            'address_country' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
        ]);

        $scope->ensureCommunityAllowed($request->user(), (int) $data['community_id']);
        $church = Church::create($data);

        Audit::record('reference.church.created', $church, [
            'designation' => $church->designation,
            'community_id' => (int) $church->community_id,
        ]);

        return back()->with('success', 'Eglise rattachee a la communaute.');
    }

    public function update(Request $request, Church $eglise, AccessScope $scope): RedirectResponse
    {
        $scope->ensureChurchAllowed($request->user(), (int) $eglise->id);

        // Le rattachement a la communaute n'est pas modifiable ici.
        $data = $request->validate([
            'designation' => ['required', 'string', 'max:255'],
            'address_number' => ['nullable', 'string', 'max:80'],
            'address_avenue' => ['nullable', 'string', 'max:255'],
            'address_district' => ['required', 'string', 'max:255'],
            'address_city' => ['required', 'string', 'max:255'],
            'address_province' => ['required', 'string', 'max:255'],
            'address_country' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
        ]);

        $before = $eglise->only(array_keys($data));
        $eglise->update($data);

        Audit::record('reference.church.updated', $eglise, ['from' => $before, 'to' => $data]);

        return back()->with('success', 'Eglise mise a jour.');
    }
}
