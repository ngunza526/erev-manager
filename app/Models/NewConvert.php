<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewConvert extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'member_id', 'full_name', 'conversion_date', 'discipleship_stage', 'mentor_name', 'baptism_target_date', 'status', 'notes'];

    protected $casts = ['conversion_date' => 'date', 'baptism_target_date' => 'date'];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
