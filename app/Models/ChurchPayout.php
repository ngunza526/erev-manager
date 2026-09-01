<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChurchPayout extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'journal_entry_id', 'beneficiary', 'purpose', 'currency', 'amount', 'exchange_rate', 'payout_date', 'payment_method', 'status'];

    protected $casts = ['payout_date' => 'date', 'amount' => 'decimal:2', 'exchange_rate' => 'decimal:6'];

    public function church(): BelongsTo { return $this->belongsTo(Church::class); }
    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class); }
}
