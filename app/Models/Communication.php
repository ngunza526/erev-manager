<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Communication extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'channel', 'audience', 'subject', 'body', 'scheduled_at', 'status'];

    protected $casts = ['scheduled_at' => 'datetime'];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
