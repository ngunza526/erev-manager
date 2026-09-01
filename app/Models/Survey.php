<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'title', 'audience', 'opens_at', 'closes_at', 'responses_count', 'status'];

    protected $casts = [
        'opens_at' => 'date',
        'closes_at' => 'date',
    ];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
