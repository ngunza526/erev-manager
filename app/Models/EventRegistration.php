<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRegistration extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'church_event_id', 'journal_entry_id', 'attendee_name', 'phone', 'ticket_code', 'currency', 'amount_paid', 'exchange_rate', 'payment_method', 'check_in_status', 'checked_in_at'];

    protected $casts = ['amount_paid' => 'decimal:2', 'exchange_rate' => 'decimal:6', 'checked_in_at' => 'datetime'];

    public function church(): BelongsTo { return $this->belongsTo(Church::class); }
    public function event(): BelongsTo { return $this->belongsTo(ChurchEvent::class, 'church_event_id'); }
    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class); }
}
