<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveStreamSession extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'title', 'starts_at', 'platform', 'stream_url', 'fallback_mode', 'status', 'notes'];

    protected $casts = ['starts_at' => 'datetime'];

    public function church(): BelongsTo { return $this->belongsTo(Church::class); }
}
