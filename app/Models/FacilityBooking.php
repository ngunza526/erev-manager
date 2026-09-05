<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityBooking extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'requester_name', 'facility_name', 'starts_at', 'ends_at', 'fee_currency', 'fee_amount', 'payment_method', 'payment_status', 'journal_entry_id', 'notes'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'fee_amount' => 'decimal:2',
    ];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
