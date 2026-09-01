<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimony extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'author_name', 'testimony_date', 'category', 'moderation_status', 'is_public', 'content'];

    protected $casts = [
        'testimony_date' => 'date',
        'is_public' => 'boolean',
    ];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
