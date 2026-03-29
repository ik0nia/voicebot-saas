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
    private const SUMMARIZE_AFTER = 8;

    /**
     * Build the messages array for a conversation, using summarization for long histories.
     *
     * Returns messages with the system prompt, summarized older history (if applicable),
     * and the recent messages including the current user message.
     */
    public function buildMessages(string $systemPrompt, Conversation $conversation, string $currentUserMessage): array
    {
        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        $history = Message::where('conversation_id', $conversation->id)
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->reverse()
            ->values();

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

        return Cache::remember($cacheKey, now()->addHours(2), function () use ($olderMessages) {
            return $this->generateSummary($olderMessages);
        });
    }

    /**
     * Call LLM to generate a concise summary of conversation messages.
     */
    private function generateSummary($messages): ?string
    {
        if ($messages->isEmpty()) {
            return null;
        }

        $transcript = '';
        foreach ($messages as $msg) {
            $role = $msg->direction === 'inbound' ? 'Client' : 'Asistent';
            $transcript .= "{$role}: {$msg->content}\n";
        }

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

            $summary = trim($response->choices[0]->message->content ?? '');
            return !empty($summary) ? $summary : null;
        } catch (\Throwable $e) {
            Log::warning('ConversationSummaryService: summary generation failed', [
                'conversation_messages' => $messages->count(),
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
