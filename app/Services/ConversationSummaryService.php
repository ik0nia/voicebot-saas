<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class ConversationSummaryService
{
    /**
     * After this many messages, older messages get summarized instead of sent in full.
     */
    private const SUMMARIZE_AFTER = 12;

    /**
     * Build the messages array for a conversation, using summarization for long histories.
     *
     * Returns messages with the system prompt, summarized older history (if applicable),
     * and the recent messages including the current user message.
     */
    /**
     * @param \Illuminate\Support\Collection|null $preloadedHistory Pre-loaded messages (desc order) to avoid redundant query
     */
    public function buildMessages(string $systemPrompt, Conversation $conversation, string $currentUserMessage, ?\Illuminate\Support\Collection $preloadedHistory = null): array
    {
        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        if ($preloadedHistory !== null) {
            // Use pre-loaded messages — they come in desc order, take 30, reverse to chronological
            $history = $preloadedHistory->take(30)->reverse()->values();
        } else {
            $history = Message::where('conversation_id', $conversation->id)
                ->orderByDesc('id')
                ->limit(30)
                ->get()
                ->reverse()
                ->values();
        }

        // Exclude the current user message if it was already saved (last inbound)
        if ($history->isNotEmpty() && $history->last()->direction === 'inbound' && $history->last()->content === $currentUserMessage) {
            $history = $history->slice(0, -1)->values();
        }

        $totalMessages = $history->count();

        if ($totalMessages > self::SUMMARIZE_AFTER) {
            // Split: older messages get summarized, recent 6 stay as-is
            $keepRecent = 6;
            $olderMessages = $history->slice(0, $totalMessages - $keepRecent)->values();
            $recentMessages = $history->slice($totalMessages - $keepRecent)->values();

            $summary = $this->getSummary($conversation, $olderMessages);

            if ($summary) {
                $messages[] = [
                    'role' => 'system',
                    'content' => "Rezumatul conversației anterioare:\n{$summary}",
                ];
            }

            foreach ($recentMessages as $msg) {
                $messages[] = [
                    'role' => $msg->direction === 'inbound' ? 'user' : 'assistant',
                    'content' => $msg->content,
                ];
            }
        } else {
            foreach ($history as $msg) {
                $messages[] = [
                    'role' => $msg->direction === 'inbound' ? 'user' : 'assistant',
                    'content' => $msg->content,
                ];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $currentUserMessage];

        return $messages;
    }

    /**
     * Get or generate a summary for older messages in a conversation.
     * Cached per conversation, invalidated when message count changes.
     */
    private function getSummary(Conversation $conversation, $olderMessages): ?string
    {
        $messageIds = $olderMessages->pluck('id')->toArray();
        $cacheKey = "conv_summary_{$conversation->id}_" . md5(implode(',', $messageIds));

        return Cache::remember($cacheKey, now()->addHours(2), function () use ($olderMessages, $conversation) {
            return $this->generateSummary($olderMessages, $conversation->bot_id ?? null, $conversation->tenant_id ?? null);
        });
    }

    /**
     * Call LLM to generate a concise summary of conversation messages.
     */
    private function generateSummary($messages, ?int $botId = null, ?int $tenantId = null): ?string
    {
        if ($messages->isEmpty()) {
            return null;
        }

        $transcript = '';
        foreach ($messages as $msg) {
            $role = $msg->direction === 'inbound' ? 'Client' : 'Asistent';
            $transcript .= "{$role}: {$msg->content}\n";
        }

        $startTime = microtime(true);

        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'temperature' => 0,
                'max_tokens' => 250,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Fă un rezumat SCURT (max 150 cuvinte) al conversației de mai jos. '
                            . 'Include: ce a cerut clientul, ce informații/produse au fost discutate, orice date colectate (nume, telefon, email). '
                            . 'Rezumatul trebuie să fie util ca context pentru continuarea conversației. '
                            . 'Scrie DOAR rezumatul, fără preambul.',
                    ],
                    ['role' => 'user', 'content' => $transcript],
                ],
            ]);

            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            $inputTokens = $response->usage->promptTokens ?? 0;
            $outputTokens = $response->usage->completionTokens ?? 0;
            // gpt-4o-mini: input $0.15/1M tokens, output $0.60/1M tokens
            $costCents = ($inputTokens * 0.015 / 1000) + ($outputTokens * 0.06 / 1000);

            try {
                \App\Models\AiApiMetric::create([
                    'provider' => 'openai',
                    'model' => 'gpt-4o-mini',
                    'input_tokens' => $inputTokens,
                    'output_tokens' => $outputTokens,
                    'cost_cents' => $costCents,
                    'response_time_ms' => $responseTimeMs,
                    'status' => 'success',
                    'error_type' => null,
                    'bot_id' => $botId,
                    'tenant_id' => $tenantId,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to record API metric', ['error' => $e->getMessage()]);
            }

            $summary = trim($response->choices[0]->message->content ?? '');
            return !empty($summary) ? $summary : null;
        } catch (\Throwable $e) {
            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            try {
                \App\Models\AiApiMetric::create([
                    'provider' => 'openai',
                    'model' => 'gpt-4o-mini',
                    'input_tokens' => 0,
                    'output_tokens' => 0,
                    'cost_cents' => 0,
                    'response_time_ms' => $responseTimeMs,
                    'status' => 'error',
                    'error_type' => get_class($e),
                    'bot_id' => $botId,
                    'tenant_id' => $tenantId,
                ]);
            } catch (\Exception $metricEx) {
                Log::warning('Failed to record API metric', ['error' => $metricEx->getMessage()]);
            }

            Log::warning('ConversationSummaryService: summary generation failed', [
                'conversation_messages' => $messages->count(),
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
