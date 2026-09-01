<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChurchService extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'title', 'service_type', 'starts_at', 'ends_at', 'preacher', 'worship_leader', 'attendance_count', 'notes'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
