<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'budget_id', 'journal_entry_id', 'description', 'vendor', 'category', 'currency', 'amount', 'exchange_rate', 'expense_date', 'payment_method', 'status'];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
    ];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
