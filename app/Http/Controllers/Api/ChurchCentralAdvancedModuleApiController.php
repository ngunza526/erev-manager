<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\AdvancedChurchModuleController;
use App\Http\Controllers\Controller;
use App\Models\ChurchEvent;
use App\Models\Fund;
use App\Services\Accounting\AccountingService;
use App\Services\AccessScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ChurchCentralAdvancedModuleApiController extends Controller
{
    public function index(Request $request, AccessScope $scope, string $module): JsonResponse
    {
        $config = $this->config($module);
        $model = $config['model'];

        return response()->json([
            'module' => $module,
            'family' => 'advanced',
            'data' => $scope->scopeChurchOwned($model::with('church:id,designation'), $request->user())
                ->latest()
                ->limit(100)
                ->get(),
        ]);
    }

    public function store(
        Request $request,
        AccessScope $scope,
        AccountingService $accounting,
        AdvancedChurchModuleController $advanced,
        string $module
    ): JsonResponse {
        $config = $this->config($module);
        $data = $request->validate($config['rules']);
        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);
        $this->ensureLinkedResourceAllowed($module, $data);

        $data = $advanced->prepareData($module, $data);
        $entry = $advanced->maybeCreateJournalEntry($module, $data, $request, $accounting);
        if ($entry) {
            $data['journal_entry_id'] = $entry->id;
        }

        return response()->json([
            'module' => $module,
            'family' => 'advanced',
            'data' => $config['model']::create($data)->load('church:id,designation'),
        ], 201);
    }

    public function update(Request $request, AccessScope $scope, string $module, int $id): JsonResponse
    {
        $config = $this->config($module);
        $model = $config['model'];
        $item = $model::findOrFail($id);
        $scope->ensureChurchAllowed($request->user(), (int) $item->church_id);

        $data = $request->validate($config['rules']);
        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);
        $this->ensureLinkedResourceAllowed($module, $data);
        $item->update($data);

        return response()->json([
            'module' => $module,
            'family' => 'advanced',
            'data' => $item->fresh('church:id,designation'),
        ]);
    }

    private function config(string $module): array
    {
        abort_unless(isset(AdvancedChurchModuleController::MODULES[$module]), 404);

        return AdvancedChurchModuleController::MODULES[$module];
    }

    private function ensureLinkedResourceAllowed(string $module, array $data): void
    {
        if ($module === 'mouvements-fonds') {
            $fundChurchId = Fund::whereKey($data['fund_id'])->value('church_id');
            if ((int) $fundChurchId !== (int) $data['church_id']) {
                throw ValidationException::withMessages(['fund_id' => 'Ce fonds est hors de cette eglise.']);
            }
        }

        if ($module === 'inscriptions-evenements' && ! empty($data['church_event_id'])) {
            $eventChurchId = ChurchEvent::whereKey($data['church_event_id'])->value('church_id');
            if ((int) $eventChurchId !== (int) $data['church_id']) {
                throw ValidationException::withMessages(['church_event_id' => 'Cet evenement est hors de cette eglise.']);
            }
        }
    }
}
