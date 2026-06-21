<?php

namespace App\Models;

use App\Models\Traits\Auditable;
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
    use HasFactory, BelongsToTenant, Auditable;

    /**
     * Câmpuri excluse din audit (counters/timestamps care se updatează
     * des fără semnificație semantică pentru tenant).
     */
    public function auditExcludedAttributes(): array
    {
        return ['last_call_at', 'last_conversation_at'];
    }

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
        'recording_enabled',
        'engine_type',
        'niche_slug',
        'archetype_config',
        'settings',
        'woocommerce_capabilities',
        'is_active',
        'calls_count',
        'knowledge_search_limit',
        'max_call_duration_seconds',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'woocommerce_capabilities' => 'array',
            'archetype_config' => 'array',
            'is_active' => 'boolean',
            'recording_enabled' => 'boolean',
            'calls_count' => 'integer',
        ];
    }

    /**
     * Concrete BotEngine instance for this bot. Never returns null —
     * legacy bots get a NullEngine with empty defaults, so call sites
     * can always depend on a concrete object.
     */
    public function engine(): \App\Engines\Contracts\BotEngine
    {
        return app(\App\Engines\EngineResolver::class)->resolve($this);
    }

    /**
     * Opt-in automation feature flag. Stored under
     * bot.settings.automations as flat bool keys. Defaults to
     * false when absent, so a misread of the settings JSON still
     * resolves to "off" — no silent SMS sends possible.
     */
    public function hasAutomation(string $key): bool
    {
        $automations = $this->settings['automations'] ?? [];
        return !empty($automations[$key]);
    }

    /**
     * Feature flag for the structured prompt composition path
     * (PromptBuilder). Defaults to false so bots keep using the
     * freeform system_prompt until an operator flips the key on a
     * per-bot basis — rollout is intentionally opt-in.
     */
    public function usesStructuredPrompt(): bool
    {
        return (bool) ($this->settings['use_structured_prompt'] ?? false);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  PER-BOT SETTINGS ACCESSORS
    //
    //  Toate setting-urile editabile sunt grupate în categorii predictibile în
    //  `bot.settings` JSON. Accesatorii de mai jos returnează valori/arrays
    //  tipate cu default-uri coerente — ca să nu mai avem `temperature=0.7`
    //  într-un service și `0.6` în altul (problemă reală observată în audit).
    //  Servicii noi trebuie să le folosească pe acestea în loc de
    //  `$bot->settings['x']` direct.
    //
    //  Schema canonică:
    //    settings.temperature        (float, 0.0..2.0, default 0.7)
    //    settings.max_tokens         (int,  64..4096,  default 1024)
    //    settings.vad_threshold      (float, 0.1..1.0, default 0.9)
    //    settings.silence_duration_ms (int, 100..3000, default 1000)
    //    settings.prefix_padding_ms  (int, 0..1500,    default 500)
    //    settings.reasoning_effort   ('minimal'|'low'|'medium'|'high'|'xhigh', default 'low')
    //    settings.timezone           (IANA, default 'Europe/Bucharest')
    //
    //    settings.business_info.{address,phone,email,website,...}
    //    settings.faqs[], settings.dont_rules[], settings.tone_guide.{...}
    //    settings.transfer_config.{enabled,operator_number,max_ring_seconds}
    //    settings.automations.{appointment_reminders_enabled,missed_call_recovery_enabled}
    //
    //    settings.rag.{similarity_threshold,fts_weight,brand_aware_enabled,query_expansion_enabled}
    //    settings.lead_capture.{threshold,prompt_text,max_prompts_per_conv,require_fields}
    //    settings.behavior.{dedup_threshold}
    //    settings.compliance.{ai_disclosure_enabled,ai_disclosure_text,ai_voice_disclosure_text}
    //    settings.escalation_sla_notify_minutes / escalation_sla_resume_minutes
    // ─────────────────────────────────────────────────────────────────────────

    public function temperature(): float
    {
        $v = $this->settings['temperature'] ?? null;
        return is_numeric($v) ? max(0.0, min(2.0, (float) $v)) : 0.7;
    }

    public function maxTokens(): int
    {
        $v = $this->settings['max_tokens'] ?? null;
        return is_numeric($v) ? max(64, min(4096, (int) $v)) : 1024;
    }

    public function vadThreshold(): float
    {
        $v = $this->settings['vad_threshold'] ?? null;
        return is_numeric($v) ? max(0.1, min(1.0, (float) $v)) : 0.9;
    }

    public function silenceDurationMs(): int
    {
        $v = $this->settings['silence_duration_ms'] ?? null;
        return is_numeric($v) ? max(100, min(3000, (int) $v)) : 1000;
    }

    public function prefixPaddingMs(): int
    {
        $v = $this->settings['prefix_padding_ms'] ?? null;
        return is_numeric($v) ? max(0, min(1500, (int) $v)) : 500;
    }

    public function reasoningEffort(): string
    {
        $v = $this->settings['reasoning_effort'] ?? null;
        $allowed = ['minimal', 'low', 'medium', 'high', 'xhigh'];
        return in_array($v, $allowed, true) ? $v : 'low';
    }

    public function timezone(): string
    {
        $v = $this->settings['timezone'] ?? null;
        return is_string($v) && $v !== '' ? $v : 'Europe/Bucharest';
    }

    /**
     * @return array{similarity_threshold:float,fts_weight:float,brand_aware_enabled:bool,query_expansion_enabled:bool}
     */
    public function ragSettings(): array
    {
        $rag = is_array($this->settings['rag'] ?? null) ? $this->settings['rag'] : [];
        return [
            'similarity_threshold' => is_numeric($rag['similarity_threshold'] ?? null)
                ? max(0.0, min(1.0, (float) $rag['similarity_threshold']))
                : (float) config('knowledge.similarity_threshold', 0.55),
            'fts_weight' => is_numeric($rag['fts_weight'] ?? null)
                ? max(0.1, min(10.0, (float) $rag['fts_weight']))
                : (float) config('knowledge.fts_weight', 1.5),
            'brand_aware_enabled' => array_key_exists('brand_aware_enabled', $rag)
                ? (bool) $rag['brand_aware_enabled'] : true,
            'query_expansion_enabled' => array_key_exists('query_expansion_enabled', $rag)
                ? (bool) $rag['query_expansion_enabled']
                : (bool) config('knowledge.query_expansion.enabled', true),
        ];
    }

    /**
     * @return array{threshold:int,prompt_text:?string,max_prompts_per_conv:int,require_fields:array}
     */
    public function leadCaptureSettings(): array
    {
        $lc = is_array($this->settings['lead_capture'] ?? null) ? $this->settings['lead_capture'] : [];
        return [
            'threshold' => is_numeric($lc['threshold'] ?? null)
                ? max(5, min(95, (int) $lc['threshold'])) : 30,
            'prompt_text' => isset($lc['prompt_text']) && is_string($lc['prompt_text'])
                && trim($lc['prompt_text']) !== '' ? $lc['prompt_text'] : null,
            'max_prompts_per_conv' => is_numeric($lc['max_prompts_per_conv'] ?? null)
                ? max(1, min(10, (int) $lc['max_prompts_per_conv'])) : 2,
            'require_fields' => is_array($lc['require_fields'] ?? null) ? $lc['require_fields'] : ['email_or_phone'],
        ];
    }

    /**
     * @return array{dedup_threshold:float}
     */
    public function behaviorSettings(): array
    {
        $b = is_array($this->settings['behavior'] ?? null) ? $this->settings['behavior'] : [];
        return [
            'dedup_threshold' => is_numeric($b['dedup_threshold'] ?? null)
                ? max(0.5, min(1.0, (float) $b['dedup_threshold'])) : 0.85,
        ];
    }

    /**
     * @return array{ai_disclosure_enabled:bool,ai_disclosure_text:?string,ai_voice_disclosure_text:?string}
     */
    public function complianceSettings(): array
    {
        $c = is_array($this->settings['compliance'] ?? null) ? $this->settings['compliance'] : [];
        return [
            'ai_disclosure_enabled' => array_key_exists('ai_disclosure_enabled', $c)
                ? (bool) $c['ai_disclosure_enabled'] : true,
            'ai_disclosure_text' => isset($c['ai_disclosure_text']) && is_string($c['ai_disclosure_text'])
                && trim($c['ai_disclosure_text']) !== '' ? $c['ai_disclosure_text'] : null,
            'ai_voice_disclosure_text' => isset($c['ai_voice_disclosure_text']) && is_string($c['ai_voice_disclosure_text'])
                && trim($c['ai_voice_disclosure_text']) !== '' ? $c['ai_voice_disclosure_text'] : null,
        ];
    }

    /**
     * @return array{notify_minutes:?int,resume_minutes:?int}
     */
    public function escalationSettings(): array
    {
        $s = $this->settings ?? [];
        $notify = $s['escalation_sla_notify_minutes'] ?? null;
        $resume = $s['escalation_sla_resume_minutes'] ?? null;
        return [
            'notify_minutes' => is_numeric($notify) ? max(1, min(1440, (int) $notify)) : null,
            'resume_minutes' => is_numeric($resume) ? max(1, min(1440, (int) $resume)) : null,
        ];
    }

    /**
     * Mesajele system pentru flow-ul de handoff la operator. Acceptăm override
     * per bot prin `bot.settings.handoff.{escalated,reminded,timed_out}` —
     * util pentru tenanți care vor copy adaptat (alt brand voice, alt canal
     * de fallback).
     *
     * @return array{escalated:string,reminded:string,timed_out:string}
     */
    public function handoffMessages(): array
    {
        $h = is_array($this->settings['handoff'] ?? null) ? $this->settings['handoff'] : [];
        $get = static function (array $h, string $key, string $default): string {
            $v = $h[$key] ?? null;
            return is_string($v) && trim($v) !== '' ? $v : $default;
        };
        return [
            'escalated' => $get($h, 'escalated', 'Am chemat un coleg, ajunge în câteva momente.'),
            'reminded'  => $get($h, 'reminded',  'Un coleg a fost deja notificat și revine cu informații cât mai curând.'),
            'timed_out' => $get($h, 'timed_out', 'Operatorii sunt ocupați acum. Putem continua aici, sau dacă preferi, lasă-ne un mesaj cu datele tale de contact.'),
        ];
    }

    /**
     * Lista de topicuri pentru care bot-ul NU răspunde. Util pentru compliance
     * (politică, religie, sănătate, advocacy) — bot redirectionează la operator
     * uman sau oferă răspuns generic. Configurabil per bot prin
     * settings.compliance.topic_opt_out (array de string-uri).
     */
    public function topicOptOut(): array
    {
        $c = is_array($this->settings['compliance'] ?? null) ? $this->settings['compliance'] : [];
        $list = $c['topic_opt_out'] ?? [];
        if (!is_array($list)) return [];
        return array_values(array_filter(array_map(
            fn($t) => is_string($t) ? mb_strtolower(trim($t)) : null,
            $list
        )));
    }

    /**
     * Voice-specific settings (fallback message la error, retention recording,
     * sentiment toggle, keyword alerts).
     *
     * @return array{
     *   fallback_message:?string,
     *   recording_retention_days:int,
     *   recording_enabled_override:?bool,
     *   sentiment_enabled:bool,
     *   keyword_alerts:array
     * }
     */
    public function voiceSettings(): array
    {
        $v = is_array($this->settings['voice'] ?? null) ? $this->settings['voice'] : [];
        return [
            'fallback_message' => isset($v['fallback_message']) && is_string($v['fallback_message'])
                && trim($v['fallback_message']) !== '' ? $v['fallback_message'] : null,
            'recording_retention_days' => is_numeric($v['recording_retention_days'] ?? null)
                ? max(1, min(3650, (int) $v['recording_retention_days']))
                : (int) env('RETENTION_RECORDING_PURGE_DAYS', 30),
            'recording_enabled_override' => array_key_exists('recording_enabled_override', $v)
                ? (bool) $v['recording_enabled_override'] : null,
            'sentiment_enabled' => (bool) ($v['sentiment_enabled'] ?? false),
            'keyword_alerts' => is_array($v['keyword_alerts'] ?? null)
                ? array_values(array_filter(array_map(
                    fn($k) => is_string($k) ? mb_strtolower(trim($k)) : null,
                    $v['keyword_alerts']
                )))
                : [],
        ];
    }

    /**
     * @return array{enabled:bool,operator_number:?string,max_ring_seconds:int}
     */
    public function transferSettings(): array
    {
        $t = is_array($this->settings['transfer_config'] ?? null) ? $this->settings['transfer_config'] : [];
        return [
            'enabled' => (bool) ($t['enabled'] ?? false),
            'operator_number' => isset($t['operator_number']) && is_string($t['operator_number'])
                && trim($t['operator_number']) !== '' ? $t['operator_number'] : null,
            'max_ring_seconds' => is_numeric($t['max_ring_seconds'] ?? null)
                ? max(10, min(60, (int) $t['max_ring_seconds'])) : 30,
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
