<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityIncident extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'title', 'incident_type', 'severity', 'occurred_at', 'reported_by', 'status', 'description'];

    protected $casts = ['occurred_at' => 'datetime'];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
