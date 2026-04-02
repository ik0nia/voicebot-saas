<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToTenant;

class CallbackRequest extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'tenant_id', 'bot_id', 'lead_id', 'conversation_id',
        'name', 'phone', 'email', 'service_type',
        'preferred_date', 'preferred_time_slot', 'notes',
        'source', 'source_page_url', 'session_id', 'visitor_id',
        'status', 'confirmed_at', 'completed_at',
        'assigned_to', 'internal_notes', 'outcome',
    ];

    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function bot(): BelongsTo { return $this->belongsTo(Bot::class); }
    public function lead(): BelongsTo { return $this->belongsTo(Lead::class); }
    public function conversation(): BelongsTo { return $this->belongsTo(Conversation::class); }

    public function scopePending($q) { return $q->where('status', 'pending'); }
    public function scopeUpcoming($q) { return $q->where('preferred_date', '>=', today())->orderBy('preferred_date')->orderByRaw("CASE preferred_time_slot WHEN 'dimineata' THEN 1 WHEN 'dupa-amiaza' THEN 2 WHEN 'seara' THEN 3 ELSE 4 END"); }
    public function scopeForTenant($q, int $id) { return $q->where('tenant_id', $id); }

    public function getTimeSlotLabelAttribute(): string
    {
        return match($this->preferred_time_slot) {
            'dimineata' => '08:00 - 12:00',
            'dupa-amiaza' => '12:00 - 17:00',
            'seara' => '17:00 - 20:00',
            default => $this->preferred_time_slot ?? 'Oricând',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'În așteptare',
            'confirmed' => 'Confirmat',
            'completed' => 'Finalizat',
            'cancelled' => 'Anulat',
            'no_answer' => 'Fără răspuns',
            default => $this->status,
        };
    }
}
