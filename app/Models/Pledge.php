<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pledge extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'donor_name', 'campaign', 'currency', 'pledged_amount', 'received_amount', 'due_date', 'status'];

    protected $casts = [
        'pledged_amount' => 'decimal:2',
        'received_amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
