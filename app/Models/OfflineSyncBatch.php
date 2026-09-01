<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfflineSyncBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'church_id',
        'user_id',
        'device_id',
        'client_batch_id',
        'payload',
        'status',
        'processed_count',
        'synced_at',
        'conflicts',
    ];

    protected $casts = [
        'payload' => 'array',
        'conflicts' => 'array',
        'synced_at' => 'datetime',
    ];
}
