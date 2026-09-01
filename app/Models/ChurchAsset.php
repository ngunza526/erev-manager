<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChurchAsset extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'asset_code', 'name', 'category', 'location', 'purchase_date', 'value_currency', 'value_amount', 'condition_status', 'custodian'];

    protected $casts = [
        'purchase_date' => 'date',
        'value_amount' => 'decimal:2',
    ];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
