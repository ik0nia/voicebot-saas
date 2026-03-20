<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Bot extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'system_prompt',
        'voice',
        'language',
        'settings',
        'is_active',
        'calls_count',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
            'calls_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Bot $bot) {
            if (empty($bot->slug)) {
                $bot->slug = Str::slug($bot->name);
            }
        });
    }

    // Relationships

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    public function botKnowledge(): HasMany
    {
        return $this->hasMany(BotKnowledge::class);
    }

    public function knowledge(): HasMany
    {
        return $this->hasMany(BotKnowledge::class);
    }

    public function channels(): HasMany
    {
        return $this->hasMany(Channel::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function phoneNumbers(): HasMany
    {
        return $this->hasMany(PhoneNumber::class);
    }

    // Methods

    public function buildSystemPrompt(): string
    {
        $knowledge = $this->botKnowledge()
            ->where('status', 'ready')
            ->pluck('content')
            ->implode("\n\n");

        if (empty($knowledge)) {
            return $this->system_prompt ?? '';
        }

        return $this->system_prompt . "\n\n--- Knowledge Base ---\n\n" . $knowledge;
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function incrementCallsCount(): void
    {
        $this->increment('calls_count');
    }

    public function activeChannels(): HasMany
    {
        return $this->channels()->where('is_active', true);
    }

    public function hasChannel(string $type): bool
    {
        return $this->channels()->where('type', $type)->exists();
    }

    public function getConnectedChannelTypes(): array
    {
        return $this->channels()
            ->where('status', 'connected')
            ->pluck('type')
            ->unique()
            ->values()
            ->toArray();
    }
}
