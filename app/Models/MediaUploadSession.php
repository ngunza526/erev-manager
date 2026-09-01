<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaUploadSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'church_id',
        'user_id',
        'upload_id',
        'title',
        'media_type',
        'category',
        'original_filename',
        'total_chunks',
        'received_chunks',
        'status',
        'storage_path',
        'storage_url',
    ];

    protected $casts = [
        'received_chunks' => 'array',
    ];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
