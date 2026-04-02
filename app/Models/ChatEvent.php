<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToTenant;

class ChatEvent extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'chat_events';

    protected $fillable = [
        'tenant_id',
        'bot_id',
        'channel_id',
        'conversation_id',
        'session_id',
        'visitor_id',
        'event_name',
        'event_source',
        'properties',
        'idempotency_key',
        'product_id',
        'wc_order_id',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'occurred_at' => 'datetime',
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

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    // ─── Scopes ───

    public function scopeForBot(Builder $query, int $botId): Builder
    {
        return $query->where('bot_id', $botId);
    }

    public function scopeForConversation(Builder $query, int $convId): Builder
    {
        return $query->where('conversation_id', $convId);
    }

    public function scopeOfType(Builder $query, string $eventName): Builder
    {
        return $query->where('event_name', $eventName);
    }

    public function scopeBetween(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('occurred_at', [$from, $to]);
    }
}
