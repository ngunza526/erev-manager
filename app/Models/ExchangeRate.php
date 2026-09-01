<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = ['from_currency', 'to_currency', 'rate', 'rated_at', 'source'];

    protected $casts = [
        'rate' => 'decimal:6',
        'rated_at' => 'date',
    ];
}
