<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\AccessScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Consultation du journal d'audit (SEC-01, permission audit.view).
 *
 * Le journal est append-only : cette surface est strictement en lecture.
 * Le perimetre suit AccessScope :
 *   - role plateforme  -> tous les evenements ;
 *   - Administrateur    -> evenements rattaches aux eglises OU a la communaute
 *                          de son perimetre (church_id ou community_id).
 * Les evenements sans church_id ni community_id (echec de connexion sur un
 * email inconnu, actions purement plateforme) ne sont visibles que par le
 * role plateforme, ce qui evite toute fuite inter-locataires.
 */
class AuditLogController extends Controller
{
    private const PER_PAGE = 25;

    public function index(Request $request, AccessScope $scope): Response
    {
        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:255'],
            'church_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $user = $request->user();
        $churchIds = $scope->churchIds($user);       // null => aucune restriction (plateforme)
        $communityIds = $scope->communityIds($user); // null => aucune restriction (plateforme)

        $base = fn () => AuditLog::query()->where(function (Builder $query) use ($churchIds, $communityIds) {
            if (is_array($churchIds)) {
                $query->whereIn('church_id', $churchIds);
            }
            if (is_array($communityIds)) {
                $query->orWhereIn('community_id', $communityIds);
            }
        });

        $logs = $base()
            ->with(['user:id,name,email', 'church:id,designation', 'community:id,designation'])
            ->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', 'like', $action.'%'))
            ->when($filters['church_id'] ?? null, fn ($query, $churchId) => $query->where('church_id', $churchId))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('AuditLogs/Index', [
            'logs' => $logs,
            'filters' => $filters,
            'actions' => $base()->distinct()->orderBy('action')->pluck('action'),
            'churches' => $scope->churches($user),
        ]);
    }
}
