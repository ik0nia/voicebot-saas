<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    use BelongsToTenant, HasFactory;

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
        'tenant_id',
        'bot_id',
        'type',
        'name',
        'external_id',
        'config',
        'credentials',
        'webhook_secret',
        'is_active',
        'status',
        'last_activity_at',
    ];

    protected $hidden = [
        'credentials',
        'webhook_secret',
    ];

    protected static function booted(): void
    {
        // Derive tenant_id from bot when not explicitly set and BelongsToTenant
        // didn't pick it up from auth (webhooks, jobs, factories).
        static::creating(function (Channel $channel) {
            if (!$channel->tenant_id && $channel->bot_id) {
                $bot = Bot::find($channel->bot_id);
                if ($bot) {
                    $channel->tenant_id = $bot->tenant_id;
                }
            }
        });
    }

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'credentials' => 'encrypted:array',
            'is_active' => 'boolean',
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * Read a per-channel credential by dotted key (e.g. 'access_token').
     */
    public function getCredential(string $key, mixed $default = null): mixed
    {
        return data_get($this->credentials ?? [], $key, $default);
    }

    /**
     * Stage a credential value. Caller must save() to persist.
     */
    public function setCredential(string $key, mixed $value): self
    {
        $creds = $this->credentials ?? [];
        data_set($creds, $key, $value);
        $this->credentials = $creds;

        return $this;
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
