<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Church extends Model
{
    use HasFactory;

    protected $fillable = [
        'community_id',
        'designation',
        'address_number',
        'address_avenue',
        'address_district',
        'address_city',
        'address_province',
        'address_country',
        'email',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }
}
