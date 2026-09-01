<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChurchMediaItem extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'title', 'media_type', 'category', 'storage_url', 'copyright_status', 'offline_available', 'status', 'notes'];

    protected $casts = ['offline_available' => 'boolean'];

    public function church(): BelongsTo { return $this->belongsTo(Church::class); }
}
