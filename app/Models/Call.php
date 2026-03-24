<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Call extends Model
{
    use HasFactory, BelongsToTenant;

    // Status constants
    public const STATUS_INITIATED = 'initiated';
    public const STATUS_RINGING = 'ringing';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_BUSY = 'busy';
    public const STATUS_NO_ANSWER = 'no_answer';
    public const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'tenant_id',
        'bot_id',
        'channel_id',
        'phone_number_id',
        'caller_number',
        'direction',
        'status',
        'duration_seconds',
        'cost_cents',
        'sentiment_score',
        'sentiment_label',
        'recording_url',
        'metadata',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'duration_seconds' => 'integer',
            'cost_cents' => 'integer',
            'sentiment_score' => 'decimal:3',
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

    public function phoneNumber(): BelongsTo
    {
        return $this->belongsTo(PhoneNumber::class);
    }

    public function transcripts(): HasMany
    {
        return $this->hasMany(Transcript::class);
    }

    public function callEvents(): HasMany
    {
        return $this->hasMany(CallEvent::class);
    }

    // Methods

    public function duration(): string
    {
        $seconds = $this->duration_seconds ?? 0;
        $minutes = intdiv($seconds, 60);
        $remaining = $seconds % 60;

        if ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $remaining);
        }

        return sprintf('%ds', $remaining);
    }

    public function formattedCost(): string
    {
        return number_format($this->cost_cents / 100, 2, ',', '.') . ' EUR';
    }

    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_INITIATED,
            self::STATUS_RINGING,
            self::STATUS_IN_PROGRESS,
        ], true);
    }

    public function sentimentEmoji(): string
    {
        return match ($this->sentiment_label) {
            'positive' => '😊',
            'negative' => '😟',
            'neutral' => '😐',
            default => '—',
        };
    }

    public function sentimentLabelRo(): string
    {
        return match ($this->sentiment_label) {
            'positive' => 'Pozitiv',
            'negative' => 'Negativ',
            'neutral' => 'Neutru',
            default => '—',
        };
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_INITIATED,
            self::STATUS_RINGING,
            self::STATUS_IN_PROGRESS,
        ]);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('started_at', today());
    }
}
