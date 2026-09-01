<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\EngagementAdminModuleController;
use App\Http\Controllers\PastoralModuleController;
use App\Services\AccessScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChurchCentralGenericModuleApiController extends Controller
{
    public function index(Request $request, AccessScope $scope, string $family, string $module): JsonResponse
    {
        $config = $this->config($family, $module);
        $model = $config['model'];
        $relation = $config['relation'] ?? 'church:id,designation';

        return response()->json([
            'module' => $module,
            'family' => $family,
            'data' => $scope->scopeChurchOwned($model::with($relation), $request->user())
                ->latest()
                ->limit(100)
                ->get(),
        ]);
    }

    public function store(Request $request, AccessScope $scope, string $family, string $module): JsonResponse
    {
        $config = $this->config($family, $module);
        $data = $request->validate($config['rules']);
        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);

        return response()->json([
            'module' => $module,
            'family' => $family,
            'data' => $config['model']::create($data)->load($config['relation'] ?? 'church:id,designation'),
        ], 201);
    }

    public function update(Request $request, AccessScope $scope, string $family, string $module, int $id): JsonResponse
    {
        $config = $this->config($family, $module);
        $model = $config['model'];
        $item = $model::findOrFail($id);
        $scope->ensureChurchAllowed($request->user(), (int) $item->church_id);

        $data = $request->validate($config['rules']);
        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);
        $item->update($data);

        return response()->json([
            'module' => $module,
            'family' => $family,
            'data' => $item->fresh($config['relation'] ?? 'church:id,designation'),
        ]);
    }

    private function config(string $family, string $module): array
    {
        $modules = match ($family) {
            'pastoral' => PastoralModuleController::MODULES,
            'administration' => EngagementAdminModuleController::MODULES,
            default => abort(404),
        };

        abort_unless(isset($modules[$module]), 404);

        return $modules[$module];
    }
}
