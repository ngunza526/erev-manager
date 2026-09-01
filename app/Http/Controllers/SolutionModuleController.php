<?php

namespace App\Http\Controllers;

use App\Models\SolutionModule;
use App\Support\SolutionImplementationMap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SolutionModuleController extends Controller
{
    public function index(): Response
    {
        $modules = SolutionModule::orderBy('category')->orderBy('name')->get()->map(function (SolutionModule $module) {
            $module->implementation = SolutionImplementationMap::for($module->code);

            return $module;
        });
        $implemented = $modules->filter(fn (SolutionModule $module) => in_array($module->implementation['level'], ['implemented', 'covered'], true))->count();

        return Inertia::render('Solutions/Index', [
            'modules' => $modules->groupBy('category'),
            'stats' => [
                'total' => $modules->count(),
                'core' => $modules->where('is_core', true)->count(),
                'active' => $modules->where('status', 'active')->count(),
                'implemented' => $implemented,
                'coverage' => $modules->count() > 0 ? round(($implemented / $modules->count()) * 100) : 0,
            ],
        ]);
    }

    public function update(Request $request, SolutionModule $solution): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'planned', 'paused'])],
        ]);

        $solution->update($data);

        return back()->with('success', 'Statut du module mis a jour.');
    }
}
