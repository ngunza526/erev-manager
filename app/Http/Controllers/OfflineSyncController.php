<?php

namespace App\Http\Controllers;

use App\Services\OfflineSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OfflineSyncController extends Controller
{
    public function store(Request $request, OfflineSyncService $sync): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            throw ValidationException::withMessages(['auth' => 'Authentification requise pour synchroniser.']);
        }

        $data = $request->validate([
            'device_id' => ['required', 'string', 'max:120'],
            'client_batch_id' => ['required', 'string', 'max:120'],
            'church_id' => ['required', 'integer', 'exists:churches,id'],
            'records' => ['required', 'array', 'min:1', 'max:200'],
            'records.*.client_id' => ['required', 'string', 'max:120'],
            'records.*.type' => ['required', 'string', 'max:80'],
            'records.*.payload' => ['required', 'array'],
        ]);

        $batch = $sync->sync($user, $data);

        return response()->json([
            'batch_id' => $batch->id,
            'client_batch_id' => $batch->client_batch_id,
            'status' => $batch->status,
            'processed_count' => $batch->processed_count,
            'results' => $batch->payload['results'] ?? [],
            'conflicts' => $batch->conflicts ?? [],
        ], $batch->wasRecentlyCreated ? 201 : 200);
    }
}
