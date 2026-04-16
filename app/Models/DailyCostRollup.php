<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyCostRollup extends Model
{
    protected $table = 'daily_cost_rollups';

    protected $fillable = [
        'tenant_id', 'bot_id', 'date',
        'voice_calls_count', 'voice_seconds',
        'voice_openai_cents', 'voice_twilio_cents', 'voice_embedding_cents', 'voice_total_cents',
        'chat_conversations_count', 'chat_messages_count', 'chat_cost_cents',
        'total_cents', 'usd_ron_rate',
    ];

    protected $casts = [
        'date' => 'date',
        'voice_calls_count' => 'integer',
        'voice_seconds' => 'integer',
        'voice_openai_cents' => 'integer',
        'voice_twilio_cents' => 'integer',
        'voice_embedding_cents' => 'integer',
        'voice_total_cents' => 'integer',
        'chat_conversations_count' => 'integer',
        'chat_messages_count' => 'integer',
        'chat_cost_cents' => 'integer',
        'total_cents' => 'integer',
        'usd_ron_rate' => 'decimal:4',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }
}
