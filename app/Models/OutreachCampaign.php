<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutreachCampaign extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'title', 'location', 'starts_at', 'ends_at', 'volunteers_count', 'contacts_count', 'conversions_count', 'status', 'notes'];

    protected $casts = ['starts_at' => 'date', 'ends_at' => 'date'];

    public function church(): BelongsTo { return $this->belongsTo(Church::class); }
}
