<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Family extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'household_name', 'primary_contact_name', 'phone', 'district', 'city', 'members_count', 'status', 'notes'];

    public function church(): BelongsTo { return $this->belongsTo(Church::class); }
}
