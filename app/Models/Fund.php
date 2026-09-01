<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fund extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'code', 'name', 'restriction_type', 'currency', 'opening_balance', 'current_balance', 'status', 'notes'];

    protected $casts = ['opening_balance' => 'decimal:2', 'current_balance' => 'decimal:2'];

    public function church(): BelongsTo { return $this->belongsTo(Church::class); }
    public function movements(): HasMany { return $this->hasMany(FundMovement::class); }
}
