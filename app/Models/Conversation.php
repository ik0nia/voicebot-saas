<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'bot_id',
        'channel_id',
        'external_conversation_id',
        'contact_identifier',
        'contact_name',
        'visitor_id',
        'status',
        'messages_count',
        'cost_cents',
        'metadata',
        'contact_id',
        'contact_inbox_id',
        'assignee_user_id',
        'assignee_bot_id',
        'assigned_at',
        'assigned_by_user_id',
        'last_activity_at',
        'started_at',
        'ended_at',
        'outcomes_summary',
        'primary_intent',
        'lead_score',
        'opportunity_score',
        'is_opportunity',
        'opportunity_reasons',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'outcomes_summary' => 'array',
            'opportunity_reasons' => 'array',
            'is_opportunity' => 'boolean',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'messages_count' => 'integer',
            'cost_cents' => 'integer',
            'last_activity_at' => 'datetime',
        ];
    }

    // Relationships

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function contactInbox(): BelongsTo
    {
        return $this->belongsTo(ContactInbox::class);
    }

    public function assigneeUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assignee_user_id');
    }

    public function assigneeBot(): BelongsTo
    {
        return $this->belongsTo(Bot::class, 'assignee_bot_id');
    }

    public function isHumanAssigned(): bool
    {
        return $this->assignee_user_id !== null;
    }

    /**
     * Durata conversației în secunde. Folosește ended_at dacă există,
     * altfel last_activity_at, altfel updated_at. Returnează 0 pentru
     * conv fără timestamp-uri valide.
     */
    public function durationSeconds(): int
    {
        if (!$this->started_at) {
            return 0;
        }
        $end = $this->ended_at ?? $this->last_activity_at ?? $this->updated_at;
        if (!$end) {
            return 0;
        }
        try {
            return max(0, $end->diffInSeconds($this->started_at));
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Număr distinct de participanți (operator + visitor + bot dacă activ).
     */
    public function participantsCount(): int
    {
        $count = 1; // visitor
        if ($this->assignee_user_id) $count++;
        if ($this->assignee_bot_id) $count++;
        return $count;
    }

    public function isBotAssigned(): bool
    {
        return $this->assignee_bot_id !== null;
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Chronological alias used by views that iterate the relation directly
     * (e.g. `@foreach($conv->orderedMessages as $m)`). Without this,
     * `$conversation->messages` returned rows in Postgres storage order,
     * which on production clustered them by direction (all inbound first,
     * then all outbound) — confusing in lead views and operator inbox.
     * Use this in views; use `messages()` in services that need raw control.
     */
    public function orderedMessages(): HasMany
    {
        return $this->hasMany(Message::class)
            ->orderBy('created_at')
            ->orderBy('id');
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }
}
