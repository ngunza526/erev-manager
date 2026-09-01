<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SermonMedia extends Model
{
    use HasFactory;

    protected $table = 'sermon_media';

    protected $fillable = ['church_id', 'title', 'preacher', 'preached_at', 'bible_reference', 'media_type', 'public_url', 'is_public', 'notes'];

    protected $casts = ['preached_at' => 'date', 'is_public' => 'boolean'];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
