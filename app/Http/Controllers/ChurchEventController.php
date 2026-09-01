<?php

namespace App\Http\Controllers;

use App\Models\Church;
use App\Models\ChurchEvent;
use App\Services\AccessScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ChurchEventController extends Controller
{
    public function index(Request $request, AccessScope $scope): Response
    {
        return Inertia::render('Events/Index', [
            'churches' => $scope->churches($request->user()),
            'events' => $scope->scopeChurchOwned(ChurchEvent::with('church:id,designation'), $request->user())->latest('starts_at')->paginate(15),
        ]);
    }

    public function store(Request $request, AccessScope $scope): RedirectResponse
    {
        $data = $request->validate([
            'church_id' => ['required', 'exists:churches,id'],
            'title' => ['required', 'string', 'max:255'],
            'event_type' => ['required', 'string', 'max:120'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'venue' => ['required', 'string', 'max:255'],
            'currency' => ['required', Rule::in(['USD', 'CDF'])],
            'ticket_price' => ['nullable', 'numeric', 'min:0'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'registrations_count' => ['nullable', 'integer', 'min:0'],
            'is_public' => ['boolean'],
        ]);
        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);
        ChurchEvent::create($data);

        return back()->with('success', 'Evenement cree.');
    }
}
