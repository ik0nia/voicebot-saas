<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Channel;
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
        'site_id',
        'name',
        'slug',
        'system_prompt',
        'greeting_message',
        'voice',
        'cloned_voice_id',
        'language',
        'settings',
        'is_active',
        'calls_count',
        'knowledge_search_limit',
        'max_call_duration_seconds',
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

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function clonedVoice(): BelongsTo
    {
        return $this->belongsTo(ClonedVoice::class);
    }

    public function usesClonedVoice(): bool
    {
        return $this->cloned_voice_id !== null && $this->clonedVoice?->isReady();
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    public function knowledge(): HasMany
    {
        return $this->hasMany(BotKnowledge::class);
    }

    public function channels(): HasMany
    {
        return $this->hasMany(Channel::class);
    }

    public function promptVersions(): HasMany
    {
        return $this->hasMany(BotPromptVersion::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function phoneNumbers(): HasMany
    {
        return $this->hasMany(PhoneNumber::class);
    }

    public function websiteScans(): HasMany
    {
        return $this->hasMany(WebsiteScan::class);
    }

    public function knowledgeConnectors(): HasMany
    {
        return $this->hasMany(KnowledgeConnector::class);
    }

    public function agentRuns(): HasMany
    {
        return $this->hasMany(KnowledgeAgentRun::class);
    }

    // Methods

    public function buildSystemPrompt(): string
    {
        $base = $this->system_prompt ?? '';

        $hasKnowledge = $this->knowledge()->where('status', 'ready')->exists();

        if ($hasKnowledge) {
            $base .= "\n\n[Ai acces la o baza de cunostinte. Informatiile relevante vor fi furnizate automat pentru fiecare intrebare.]";
        }

        return $base;
    }

    public function getKnowledgeContext(string $query): string
    {
        return app(\App\Services\KnowledgeSearchService::class)->buildContext($this->id, $query);
    }

    public function knowledgeStats(): array
    {
        return [
            'total_documents' => $this->knowledge()->distinct()->count('title'),
            'total_chunks' => $this->knowledge()->where('status', 'ready')->count(),
            'has_embeddings' => $this->knowledge()->where('status', 'ready')->whereNotNull('embedding')->exists(),
        ];
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

    /**
     * Generate the HTML embed code for the web chatbot widget.
     * The embed will only work on verified domains for this tenant.
     */
    public function getEmbedCode(): string
    {
        $channel = $this->channels()
            ->where('type', Channel::TYPE_WEB_CHATBOT)
            ->where('is_active', true)
            ->first();

        if (!$channel) {
            return '';
        }

        $appUrl = rtrim(config('app.url'), '/');
        $channelId = e($channel->id);

        return '<script src="' . $appUrl . '/chatbot/embed.js" data-channel-id="' . $channelId . '" async defer></script>';
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
