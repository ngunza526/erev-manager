<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChurchMediaItem;
use App\Models\JournalEntry;
use App\Models\Member;
use App\Models\SolutionModule;
use App\Services\AccessScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChurchCentralApiController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->only('id', 'name', 'email', 'level', 'church_id', 'community_id'),
        ]);
    }

    public function churches(Request $request, AccessScope $scope): JsonResponse
    {
        return response()->json([
            'data' => $scope->churches($request->user()),
        ]);
    }

    public function members(Request $request, AccessScope $scope): JsonResponse
    {
        return response()->json([
            'data' => $scope->scopeChurchOwned(Member::with('church:id,designation'), $request->user())
                ->latest()
                ->limit(100)
                ->get(),
        ]);
    }

    public function journalEntries(Request $request, AccessScope $scope): JsonResponse
    {
        return response()->json([
            'data' => $scope->scopeChurchOwned(JournalEntry::with('church:id,designation', 'lines.account'), $request->user())
                ->latest()
                ->limit(100)
                ->get(),
        ]);
    }

    public function solutions(): JsonResponse
    {
        return response()->json([
            'data' => SolutionModule::orderBy('category')->orderBy('name')->get(),
        ]);
    }

    public function offlineMediaManifest(Request $request, AccessScope $scope): JsonResponse
    {
        return response()->json([
            'version' => now()->format('YmdHi'),
            'data' => $scope->scopeChurchOwned(ChurchMediaItem::query(), $request->user())
                ->where('offline_available', true)
                ->where('status', 'published')
                ->whereNotNull('storage_url')
                ->orderBy('updated_at')
                ->limit(200)
                ->get(['id', 'church_id', 'title', 'media_type', 'category', 'storage_url', 'updated_at'])
                ->map(fn (ChurchMediaItem $item) => [
                    'id' => $item->id,
                    'church_id' => $item->church_id,
                    'title' => $item->title,
                    'media_type' => $item->media_type,
                    'category' => $item->category,
                    'url' => $item->storage_url,
                    'cache_key' => "media-{$item->id}-{$item->updated_at?->timestamp}",
                ]),
        ]);
    }
}
