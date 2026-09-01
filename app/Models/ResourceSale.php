<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceSale extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'journal_entry_id', 'item_name', 'buyer_name', 'quantity', 'currency', 'unit_price', 'total_amount', 'exchange_rate', 'payment_method', 'status', 'sold_at'];

    protected $casts = ['sold_at' => 'date', 'unit_price' => 'decimal:2', 'total_amount' => 'decimal:2', 'exchange_rate' => 'decimal:6'];

    public function church(): BelongsTo { return $this->belongsTo(Church::class); }
    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class); }
}
