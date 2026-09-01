<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolunteerAssignment extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'member_id', 'volunteer_name', 'team', 'role', 'service_date', 'availability_status', 'notes'];

    protected $casts = ['service_date' => 'date'];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
