<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscipleshipPath extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'member_id', 'participant_name', 'track_name', 'current_step', 'progress_percent', 'mentor_name', 'next_meeting_at', 'status'];

    protected $casts = ['next_meeting_at' => 'date'];

    public function church(): BelongsTo { return $this->belongsTo(Church::class); }
    public function member(): BelongsTo { return $this->belongsTo(Member::class); }
}
