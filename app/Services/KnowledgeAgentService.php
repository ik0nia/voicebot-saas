<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\BotKnowledge;
use App\Models\KnowledgeAgent;
use App\Models\KnowledgeAgentRun;
use App\Jobs\ProcessKnowledgeDocument;

class KnowledgeAgentService
{
    public function getAgentsForBot(): array
    {
        $agents = KnowledgeAgent::active()
            ->orderBy('sort_order')
            ->get();

        return $agents->groupBy('category')->toArray();
    }

    public function getAllAgents()
    {
        return KnowledgeAgent::active()->orderBy('sort_order')->get();
    }

    public function getAgent(string $slug): ?KnowledgeAgent
    {
        return KnowledgeAgent::where('slug', $slug)->active()->first();
    }

    public function createRun(Bot $bot, string $agentSlug, string $userInput, ?string $customPrompt = null): KnowledgeAgentRun
    {
        return KnowledgeAgentRun::create([
            'bot_id' => $bot->id,
            'agent_slug' => $agentSlug,
            'user_input' => $userInput,
            'custom_prompt' => $customPrompt,
            'status' => 'pending',
        ]);
    }

    public function buildPrompt(KnowledgeAgent $agent, string $userInput, ?string $customPrompt = null): string
    {
        $prompt = $customPrompt ?: $agent->default_prompt;
        return str_replace('{input}', $userInput, $prompt);
    }

    public function saveAsKnowledge(KnowledgeAgentRun $run): BotKnowledge
    {
        $agent = $this->getAgent($run->agent_slug);
        $agentName = $agent ? $agent->name : $run->agent_slug;

        $knowledge = BotKnowledge::create([
            'bot_id' => $run->bot_id,
            'type' => 'text',
            'source_type' => 'agent',
            'source_id' => $run->id,
            'title' => $agentName . ' — ' . \Illuminate\Support\Str::limit($run->user_input, 50),
            'content' => $run->generated_content,
            'status' => 'pending',
            'metadata' => [
                'agent_slug' => $run->agent_slug,
                'agent_name' => $agentName,
                'tokens_used' => $run->tokens_used,
            ],
        ]);

        $run->update(['knowledge_id' => $knowledge->id]);

        ProcessKnowledgeDocument::dispatch($knowledge);

        return $knowledge;
    }

    public function customizeAgentPrompt(Bot $bot, string $agentSlug, string $customPrompt): void
    {
        // Store custom prompt per bot in bot settings
        $settings = $bot->settings ?? [];
        $settings['agent_prompts'] = $settings['agent_prompts'] ?? [];
        $settings['agent_prompts'][$agentSlug] = $customPrompt;
        $bot->update(['settings' => $settings]);
    }

    public function getCustomPrompt(Bot $bot, string $agentSlug): ?string
    {
        $settings = $bot->settings ?? [];
        return $settings['agent_prompts'][$agentSlug] ?? null;
    }
}
