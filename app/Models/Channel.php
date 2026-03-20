<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    use HasFactory;

    const TYPE_VOICE = 'voice';
    const TYPE_WHATSAPP = 'whatsapp';
    const TYPE_FACEBOOK_MESSENGER = 'facebook_messenger';
    const TYPE_INSTAGRAM_DM = 'instagram_dm';
    const TYPE_WEB_CHATBOT = 'web_chatbot';

    const TYPES = [
        self::TYPE_VOICE,
        self::TYPE_WHATSAPP,
        self::TYPE_FACEBOOK_MESSENGER,
        self::TYPE_INSTAGRAM_DM,
        self::TYPE_WEB_CHATBOT,
    ];

    protected $fillable = [
        'bot_id',
        'type',
        'name',
        'external_id',
        'config',
        'webhook_secret',
        'is_active',
        'status',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_active' => 'boolean',
            'last_activity_at' => 'datetime',
        ];
    }

    // Relationships

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    // Helpers

    public function isVoice(): bool
    {
        return $this->type === self::TYPE_VOICE;
    }

    public function isTextBased(): bool
    {
        return in_array($this->type, [
            self::TYPE_WHATSAPP,
            self::TYPE_FACEBOOK_MESSENGER,
            self::TYPE_INSTAGRAM_DM,
            self::TYPE_WEB_CHATBOT,
        ]);
    }

    public function getDisplayName(): string
    {
        if ($this->name) {
            return $this->name;
        }

        return match ($this->type) {
            self::TYPE_VOICE => 'Voice',
            self::TYPE_WHATSAPP => 'WhatsApp',
            self::TYPE_FACEBOOK_MESSENGER => 'Facebook Messenger',
            self::TYPE_INSTAGRAM_DM => 'Instagram DM',
            self::TYPE_WEB_CHATBOT => 'Web Chatbot',
            default => ucfirst($this->type),
        };
    }

    public function getChannelIcon(): string
    {
        return match ($this->type) {
            self::TYPE_VOICE => 'phone',
            self::TYPE_WHATSAPP => 'message-circle',
            self::TYPE_FACEBOOK_MESSENGER => 'facebook',
            self::TYPE_INSTAGRAM_DM => 'instagram',
            self::TYPE_WEB_CHATBOT => 'globe',
            default => 'message-square',
        };
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
