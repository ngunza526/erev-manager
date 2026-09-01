<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorBill extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'journal_entry_id', 'vendor_name', 'bill_number', 'category', 'currency', 'amount', 'exchange_rate', 'bill_date', 'due_date', 'payment_method', 'status'];

    protected $casts = ['bill_date' => 'date', 'due_date' => 'date', 'amount' => 'decimal:2', 'exchange_rate' => 'decimal:6'];

    public function church(): BelongsTo { return $this->belongsTo(Church::class); }
    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class); }
}
