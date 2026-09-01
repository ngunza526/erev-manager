<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliation extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'account_name', 'currency', 'statement_date', 'book_balance', 'statement_balance', 'difference_amount', 'status', 'notes'];

    protected $casts = ['statement_date' => 'date', 'book_balance' => 'decimal:2', 'statement_balance' => 'decimal:2', 'difference_amount' => 'decimal:2'];

    public function church(): BelongsTo { return $this->belongsTo(Church::class); }
}
