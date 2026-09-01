<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CounselingCase extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'case_code', 'requester_name', 'care_type', 'assigned_to', 'appointment_date', 'next_follow_up_at', 'confidentiality_level', 'status', 'closed_at', 'summary', 'last_follow_up_note'];

    protected $casts = [
        'appointment_date' => 'date',
        'next_follow_up_at' => 'date',
        'closed_at' => 'datetime',
    ];

    public function church(): BelongsTo { return $this->belongsTo(Church::class); }
}
