<?php

namespace App\Jobs;

use App\Events\AgentRunCompleted;
use App\Jobs\Middleware\OpenAiRateLimiter;
use App\Models\KnowledgeAgentRun;
use App\Services\KnowledgeAgentService;
use App\Services\PlanLimitService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class RunKnowledgeAgent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [15, 60, 180];

    public function __construct(public KnowledgeAgentRun $run)
    {
        $this->onQueue('agents');
    }

    public function middleware(): array
    {
        return [new OpenAiRateLimiter(maxPerMinute: 30)];
    }

    public function handle(KnowledgeAgentService $agentService): void
    {
        $this->run->update(['status' => 'running']);

        Log::info('RunKnowledgeAgent: started', [
            'run_id' => $this->run->id,
            'agent_slug' => $this->run->agent_slug,
            'bot_id' => $this->run->bot_id,
        ]);

        try {
            $agent = $agentService->getAgent($this->run->agent_slug);

            if (!$agent) {
                Log::error('Knowledge agent not found', [
                    'run_id' => $this->run->id,
                    'agent_slug' => $this->run->agent_slug,
                ]);
                $this->run->update([
                    'status' => 'failed',
                    'generated_content' => 'Agentul solicitat nu este disponibil. Vă rugăm să încercați din nou.',
                ]);
                return;
            }

            $prompt = $agentService->buildPrompt(
                $agent,
                $this->run->user_input,
                $this->run->custom_prompt
            );

            $modelToUse = $agent->model ?? 'gpt-4o-mini';
            $systemMessage = $agent->system_prompt ?? $agent->role;
            $temperature = $agent->temperature ?? 0.7;
            $maxTokens = $agent->max_tokens ?? 4000;

            $response = OpenAI::chat()->create([
                'model' => $modelToUse,
                'messages' => [
                    ['role' => 'system', 'content' => $systemMessage],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ]);

            $content = $response->choices[0]->message->content;
            $finishReason = $response->choices[0]->finishReason ?? null;
            $tokensUsed = $response->usage->totalTokens ?? 0;

            $metadata = $this->run->metadata ?? [];
            if ($finishReason === 'length') {
                $metadata['truncated'] = true;
            }
            $metadata['finish_reason'] = $finishReason;

            $this->run->update([
                'status' => 'completed',
                'generated_content' => $content,
                'tokens_used' => $tokensUsed,
                'model_used' => $modelToUse,
                'metadata' => $metadata,
            ]);

            Log::info('RunKnowledgeAgent: completed', [
                'run_id' => $this->run->id,
                'agent_slug' => $this->run->agent_slug,
                'bot_id' => $this->run->bot_id,
                'tokens_used' => $tokensUsed,
                'model' => $modelToUse,
                'finish_reason' => $finishReason,
            ]);

            event(new AgentRunCompleted($this->run));

            // ── Inregistreaza tokenii consumati in usage tracking ──
            $tenant = $this->run->bot?->tenant;
            if ($tenant && $tokensUsed > 0) {
                app(PlanLimitService::class)->recordTokensUsed($tenant, $tokensUsed);
            }
        } catch (\Exception $e) {
            Log::error('Knowledge agent run failed', [
                'run_id' => $this->run->id,
                'agent_slug' => $this->run->agent_slug,
                'bot_id' => $this->run->bot_id,
                'error' => $e->getMessage(),
            ]);

            $this->run->update([
                'status' => 'failed',
                'generated_content' => 'A apărut o eroare la generarea conținutului. Vă rugăm să încercați din nou.',
                'model_used' => $modelToUse ?? (isset($agent) ? ($agent->model ?? 'gpt-4o-mini') : 'gpt-4o-mini'),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure — cleanup run status after all retries exhausted.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('RunKnowledgeAgent permanently failed', [
            'run_id' => $this->run->id,
            'agent_slug' => $this->run->agent_slug,
            'error' => $exception?->getMessage(),
        ]);

        $this->run->update([
            'status' => 'failed',
            'generated_content' => 'Procesarea a eșuat după mai multe încercări. Vă rugăm să contactați suportul.',
            'metadata' => array_merge($this->run->metadata ?? [], [
                'permanently_failed' => true,
                'failed_at' => now()->toIso8601String(),
            ]),
        ]);
    }
}
