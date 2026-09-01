<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicQrCode extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'label', 'target_type', 'target_url', 'short_code', 'scan_count', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function church(): BelongsTo { return $this->belongsTo(Church::class); }
}
