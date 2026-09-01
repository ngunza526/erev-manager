<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRun extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'journal_entry_id', 'period_label', 'staff_name', 'role', 'currency', 'gross_amount', 'social_charges', 'net_amount', 'exchange_rate', 'payment_method', 'status', 'paid_at'];

    protected $casts = ['paid_at' => 'date', 'gross_amount' => 'decimal:2', 'social_charges' => 'decimal:2', 'net_amount' => 'decimal:2', 'exchange_rate' => 'decimal:6'];

    public function church(): BelongsTo { return $this->belongsTo(Church::class); }
    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class); }
}
