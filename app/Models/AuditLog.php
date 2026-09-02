<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Journal d'audit append-only : trace les actions sensibles des utilisateurs
 * (connexions, gestion des comptes et roles, ecritures comptables, paiements,
 * modifications de referentiel). N'est jamais modifie ni supprime par l'appli.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'actor_label',
        'action',
        'auditable_type',
        'auditable_id',
        'church_id',
        'community_id',
        'context',
        'ip_address',
    ];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
