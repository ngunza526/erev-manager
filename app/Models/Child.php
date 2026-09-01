<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Child extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'guardian_member_id', 'full_name', 'birth_date', 'guardian_name', 'guardian_phone', 'classroom', 'check_in_code', 'checked_in', 'checked_in_at', 'checked_out_at', 'released_to'];

    protected $casts = [
        'birth_date' => 'date',
        'checked_in' => 'boolean',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
