<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundMovement extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'fund_id', 'journal_entry_id', 'movement_type', 'source_name', 'currency', 'amount', 'exchange_rate', 'movement_date', 'payment_method', 'status', 'description'];

    protected $casts = ['movement_date' => 'date', 'amount' => 'decimal:2', 'exchange_rate' => 'decimal:6'];

    public function church(): BelongsTo { return $this->belongsTo(Church::class); }
    public function fund(): BelongsTo { return $this->belongsTo(Fund::class); }
    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class); }
}
