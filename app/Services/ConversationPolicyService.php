<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\Channel;
use App\Models\ConversationPolicy;
use Illuminate\Support\Facades\Cache;

class ConversationPolicyService
{
    private const DEFAULTS = [
        'tone' => 'professional', 'verbosity' => 'concise', 'emoji_allowed' => false,
        'cta_aggressiveness' => 'moderate', 'lead_aggressiveness' => 'soft',
        'fallback_message' => null, 'escalation_message' => null,
        'prohibited_phrases' => [], 'required_phrases' => [], 'brand_vocabulary' => [],
        'custom_greeting' => null, 'custom_handoff_message' => null,
        'custom_lead_prompt' => null, 'custom_out_of_stock' => null,
        'business_rules' => [], 'snippets' => [],
    ];

    private const TONE_INSTRUCTIONS = [
        'professional' => 'Fii profesional, concis și obiectiv.',
        'warm' => 'Fii cald, empatic și prietenos. Arată că îți pasă.',
        'premium' => 'Folosește un limbaj elegant. Tratează clientul ca pe un VIP.',
        'friendly' => 'Fii prietenos și casual, ca un coleg de încredere.',
        'consultative' => 'Fii un consultant expert. Oferă sfaturi fundamentate.',
        'technical' => 'Fii precis tehnic. Folosește terminologie de specialitate.',
    ];

    public function getPolicy(Bot $bot, ?Channel $channel = null): array
    {
        $merged = self::DEFAULTS;

        try {
            $tenantPolicy = Cache::remember("policy_tenant_{$bot->tenant_id}", 1800, function() use ($bot) {
                return ConversationPolicy::withoutGlobalScopes()->where('tenant_id', $bot->tenant_id)->whereNull('bot_id')->first();
            });
        } catch (\Throwable $e) {
            $tenantPolicy = ConversationPolicy::where('tenant_id', $bot->tenant_id)->whereNull('bot_id')->first();
        }
        if ($tenantPolicy) $merged = $this->merge($merged, $tenantPolicy->toArray());

        try {
            $botPolicy = Cache::remember("policy_bot_{$bot->id}", 1800, function() use ($bot) {
                return ConversationPolicy::withoutGlobalScopes()->where('tenant_id', $bot->tenant_id)->where('bot_id', $bot->id)->first();
            });
        } catch (\Throwable $e) {
            $botPolicy = ConversationPolicy::where('tenant_id', $bot->tenant_id)->where('bot_id', $bot->id)->first();
        }
        if ($botPolicy) $merged = $this->merge($merged, $botPolicy->toArray());

        if ($channel && !empty($channel->config['policy_overrides'])) {
            $merged = $this->merge($merged, $channel->config['policy_overrides']);
        }

        return $merged;
    }

    public function toPromptInstructions(array $policy): string
    {
        $lines = ['=== STILUL CONVERSAȚIEI ==='];
        $lines[] = '- Ton: ' . (self::TONE_INSTRUCTIONS[$policy['tone']] ?? self::TONE_INSTRUCTIONS['professional']);
        $lines[] = $policy['emoji_allowed'] ? '- Poți folosi emoji-uri ocazional.' : '- NU folosi emoji-uri.';

        $verbMap = ['concise' => 'maxim 2-3 propoziții', 'moderate' => '3-5 propoziții', 'detailed' => 'răspunsuri detaliate'];
        $lines[] = '- Răspunde ' . ($verbMap[$policy['verbosity']] ?? $verbMap['concise']) . '.';

        foreach ($policy['prohibited_phrases'] ?? [] as $p) { $lines[] = "- NU spune niciodată: \"{$p}\""; }
        foreach ($policy['required_phrases'] ?? [] as $p) { $lines[] = "- Menționează când e relevant: \"{$p}\""; }
        foreach ($policy['business_rules'] ?? [] as $r) { $lines[] = "- REGULĂ: {$r}"; }

        $lines[] = '=== SFÂRȘIT STIL ===';
        return implode("\n", $lines);
    }

    private function merge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if ($value === null || $value === '' || $value === []) continue;
            if (!array_key_exists($key, $base)) continue;
            $base[$key] = $value;
        }
        return $base;
    }
}
