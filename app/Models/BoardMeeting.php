<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardMeeting extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'title', 'meeting_date', 'chairperson', 'quorum_count', 'decisions', 'status'];

    protected $casts = ['meeting_date' => 'date'];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
