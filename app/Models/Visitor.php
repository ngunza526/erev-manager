<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visitor extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'full_name', 'phone', 'email', 'visit_source', 'visited_at', 'follow_up_status', 'notes'];

    protected $casts = ['visited_at' => 'date'];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
