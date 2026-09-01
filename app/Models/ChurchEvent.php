<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChurchEvent extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'title', 'event_type', 'starts_at', 'ends_at', 'venue', 'currency', 'ticket_price', 'capacity', 'registrations_count', 'is_public'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_public' => 'boolean',
    ];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
