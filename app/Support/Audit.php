<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Church;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Point d'entree unique pour ecrire dans le journal d'audit.
 *
 *   Audit::record('member.promoted', $member, ['from' => 'sympathisant', 'to' => 'actif']);
 *
 * Le perimetre (church_id / community_id) est resolu automatiquement a partir de
 * l'entite auditee puis, a defaut, de l'utilisateur connecte. Le church_id peut
 * etre force via le 4e argument (utile hors contexte HTTP, ex. AccountingService).
 */
final class Audit
{
    public static function record(
        string $action,
        ?Model $auditable = null,
        array $context = [],
        ?int $churchId = null,
    ): AuditLog {
        $user = Auth::user();

        $churchId ??= self::resolveChurchId($auditable, $user);
        $communityId = self::resolveCommunityId($auditable, $user, $churchId);

        $request = request();

        return AuditLog::create([
            'user_id' => $user?->getKey(),
            'actor_label' => $user ? trim(($user->name ?? '').' <'.($user->email ?? '').'>') : 'systeme',
            'action' => $action,
            'auditable_type' => $auditable ? $auditable->getMorphClass() : null,
            'auditable_id' => $auditable?->getKey(),
            'church_id' => $churchId,
            'community_id' => $communityId,
            'context' => $context ?: null,
            'ip_address' => $request?->ip(),
        ]);
    }

    private static function resolveChurchId(?Model $auditable, ?object $user): ?int
    {
        if ($auditable && isset($auditable->church_id)) {
            return (int) $auditable->church_id;
        }

        return $user?->church_id ? (int) $user->church_id : null;
    }

    private static function resolveCommunityId(?Model $auditable, ?object $user, ?int $churchId): ?int
    {
        if ($auditable && isset($auditable->community_id)) {
            return (int) $auditable->community_id;
        }

        if ($churchId && $communityId = Church::whereKey($churchId)->value('community_id')) {
            return (int) $communityId;
        }

        return $user?->community_id ? (int) $user->community_id : null;
    }
}
