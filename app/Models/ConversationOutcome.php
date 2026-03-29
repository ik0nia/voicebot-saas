<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationOutcome extends Model
{
    use HasFactory;

    protected $table = 'conversation_outcomes';

    protected $fillable = [
        'tenant_id',
        'bot_id',
        'conversation_id',
        'session_id',
        'outcome_type',
        'confidence',
        'attribution_reason',
        'metadata',
        'revenue_cents',
        'product_id',
        'wc_order_id',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'revenue_cents' => 'decimal:0',
        ];
    }

    // ─── Relationships ───

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
