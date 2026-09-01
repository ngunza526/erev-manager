<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingCourse extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'title', 'category', 'instructor_name', 'starts_at', 'ends_at', 'enrollments_count', 'certificate_enabled'];

    protected $casts = ['starts_at' => 'date', 'ends_at' => 'date', 'certificate_enabled' => 'boolean'];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
