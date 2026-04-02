<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToTenant;

class HandoffRequest extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'tenant_id', 'bot_id', 'conversation_id', 'lead_id',
        'status', 'trigger_reason', 'conversation_summary',
        'detected_intents', 'products_shown', 'lead_data',
        'unresolved_issue', 'recommended_action', 'delivery_method',
        'sent_at', 'resolved_at',
    ];

    protected $casts = [
        'detected_intents' => 'array',
        'products_shown' => 'array',
        'lead_data' => 'array',
        'sent_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function bot(): BelongsTo { return $this->belongsTo(Bot::class); }
    public function conversation(): BelongsTo { return $this->belongsTo(Conversation::class); }
    public function lead(): BelongsTo { return $this->belongsTo(Lead::class); }

    public function scopePending($q) { return $q->where('status', 'pending'); }
    public function scopeForTenant($q, int $tenantId) { return $q->where('tenant_id', $tenantId); }
}
