<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiToolRequest extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'tool_type', 'requested_by', 'prompt_title', 'prompt_context', 'human_review_status', 'output_summary'];

    public function church(): BelongsTo { return $this->belongsTo(Church::class); }
}
