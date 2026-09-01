<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Community extends Model
{
    use HasFactory;

    protected $fillable = [
        'designation',
        'headquarters_number',
        'headquarters_avenue',
        'headquarters_district',
        'headquarters_city',
        'headquarters_province',
        'headquarters_country',
        'authorization_number',
        'email',
        'website',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function churches(): HasMany
    {
        return $this->hasMany(Church::class);
    }
}
