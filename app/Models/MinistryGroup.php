<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MinistryGroup extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'name', 'group_type', 'leader_name', 'meeting_day', 'district', 'city', 'members_count', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
