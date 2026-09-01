<?php

namespace App\Models;

use App\Enums\MemberStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'church_id',
        'last_name',
        'middle_name',
        'first_name',
        'sex',
        'birth_date',
        'birth_place',
        'profession',
        'marital_status',
        'spouse',
        'baptism_date',
        'baptism_place',
        'baptism_church',
        'identity_type',
        'identity_number',
        'identity_issued_at',
        'identity_issuer',
        'status',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'baptism_date' => 'date',
        'identity_issued_at' => 'date',
        'status' => MemberStatus::class,
    ];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->last_name} {$this->middle_name} {$this->first_name}");
    }
}
