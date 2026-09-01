<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolutionModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'church_central_reference',
        'rdc_adaptation',
        'status',
        'is_core',
    ];

    protected $casts = [
        'is_core' => 'boolean',
    ];
}
