<?php

namespace App\Http\Controllers;

use App\Models\ChurchService;
use App\Services\AccessScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChurchServiceController extends Controller
{
    public function index(Request $request, AccessScope $scope): Response
    {
        return Inertia::render('Services/Index', [
            'churches' => $scope->churches($request->user()),
            'services' => $scope->scopeChurchOwned(ChurchService::with('church:id,designation'), $request->user())->latest('starts_at')->paginate(15)->withQueryString(),
        ]);
    }

    public function store(Request $request, AccessScope $scope): RedirectResponse
    {
        $data = $request->validate([
            'church_id' => ['required', 'exists:churches,id'],
            'title' => ['required', 'string', 'max:255'],
            'service_type' => ['required', 'string', 'max:120'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'preacher' => ['nullable', 'string', 'max:255'],
            'worship_leader' => ['nullable', 'string', 'max:255'],
            'attendance_count' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);
        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);
        ChurchService::create($data);

        return back()->with('success', 'Culte/service cree.');
    }
}
