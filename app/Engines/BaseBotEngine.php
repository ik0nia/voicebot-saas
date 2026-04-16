<?php

namespace App\Engines;

use App\Engines\Contracts\BotEngine;
use App\Models\Bot;

/**
 * Default implementation plumbing shared by every engine. Subclasses
 * override what's specific to their archetype.
 *
 * The common path reads the niche config once and serves the niche's
 * prompt addon / tool manifest / KPI list by default — so a new niche
 * can be added as config-only, no engine class changes required.
 */
abstract class BaseBotEngine implements BotEngine
{
    public function displayName(): string
    {
        return ucfirst($this->type());
    }

    public function systemPromptAddon(Bot $bot): string
    {
        $niche = $this->niche($bot);
        return $niche['prompt_addon'] ?? '';
    }

    public function chatTools(Bot $bot): array
    {
        $niche = $this->niche($bot);
        $toolNames = $niche['chat_tools'] ?? [];
        $manifest = [];
        foreach ($toolNames as $name) {
            $def = $this->toolDefinition($name);
            if ($def) $manifest[] = $def;
        }
        return $manifest;
    }

    public function capabilities(Bot $bot): array
    {
        $niche = $this->niche($bot);
        return array_values(array_unique(array_merge(
            $this->defaultCapabilities(),
            $niche['capabilities'] ?? []
        )));
    }

    public function kpiKeys(Bot $bot): array
    {
        $niche = $this->niche($bot);
        return $niche['kpis'] ?? $this->defaultKpiKeys();
    }

    /**
     * Resolve this bot's niche entry from config. Returns [] if the
     * bot has no niche_slug or the slug isn't registered.
     */
    protected function niche(Bot $bot): array
    {
        $slug = $bot->niche_slug;
        if (!$slug) return [];
        return config('niches.' . $slug, []);
    }

    /**
     * Engines override with the tools they physically implement.
     * Unknown names return null so the niche can list aspirational
     * tools without crashing the manifest.
     */
    protected function toolDefinition(string $name): ?array
    {
        // Registered tools are implemented in later iterations. Until
        // each tool has a schema, we advertise nothing by default.
        return null;
    }

    /** @return string[] */
    protected function defaultCapabilities(): array
    {
        return [];
    }

    /** @return string[] */
    protected function defaultKpiKeys(): array
    {
        return [];
    }
}
