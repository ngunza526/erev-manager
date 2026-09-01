<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'church_id',
        'reference',
        'entry_date',
        'type',
        'description',
        'currency',
        'exchange_rate',
        'status',
        'validated_at',
        'created_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'exchange_rate' => 'decimal:6',
        'validated_at' => 'datetime',
    ];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }
}
