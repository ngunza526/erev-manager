<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'requester_name', 'request_type', 'priority', 'assigned_to', 'due_at', 'status', 'description'];

    protected $casts = ['due_at' => 'date'];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
