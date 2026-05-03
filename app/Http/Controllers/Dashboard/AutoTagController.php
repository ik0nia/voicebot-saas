<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * Auto-tag conversații cu LLM — analizează mesajele și returnează:
 *   - intent buckets (1-3 din taxonomie predefinită)
 *   - sentiment (positive / neutral / negative / frustrated)
 *   - urgency (low / medium / high / critical)
 *   - top topics (1-3 keywords scurte)
 *   - lead potential (low / medium / high)
 *
 * Persistat în conversation.metadata['auto_tags'] pentru filter pe inbox.
 *
 * Endpoint pe demand din UI; cron-ul nightly poate apela bulk pentru noi.
 */
class AutoTagController extends Controller
{
    /** Taxonomie intent canonică, partajată cu UI pentru filtre. */
    public const INTENTS = [
        'info_request', 'pricing_question', 'booking_request', 'product_inquiry',
        'support_issue', 'complaint', 'feedback', 'compare_options',
        'cancel_or_modify', 'small_talk', 'spam_or_test', 'other',
    ];

    public function tag(Conversation $conversation): JsonResponse
    {
        abort_unless(
            $conversation->tenant_id === auth()->user()->tenant_id || auth()->user()->isSuperAdmin(),
            404,
        );

        // Throttle 60/min/tenant
        $key = 'auto-tag:tenant:' . (int) auth()->user()->tenant_id;
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 60)) {
            return response()->json(['error' => 'Prea multe cereri'], 429);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($key, 60);

        // Cache pe metadata — dacă deja avem tag-uri proaspete (<24h), returnăm
        $existing = $conversation->metadata['auto_tags'] ?? null;
        if ($existing && isset($existing['tagged_at'])) {
            $taggedAge = now()->diffInHours(\Carbon\Carbon::parse($existing['tagged_at']));
            if ($taggedAge < 24) {
                return response()->json(array_merge(['cached' => true], $existing));
            }
        }

        // Construiește contextul: ultimele 12 mesaje
        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->limit(12)
            ->get();

        if ($messages->isEmpty()) {
            return response()->json(['error' => 'conversație fără mesaje'], 422);
        }

        $transcript = '';
        foreach ($messages as $msg) {
            $role = ($msg->role ?? 'user') === 'user' ? 'CLIENT' : 'AGENT';
            $transcript .= "[{$role}] " . mb_substr((string) $msg->content, 0, 300) . "\n";
        }

        $intentList = implode(', ', self::INTENTS);
        $system = <<<PROMPT
Ești analist conversații. Primești un transcript scurt și clasifici conversația cu tag-uri structurate.

Returnează JSON cu:
  - intents: array cu 1-3 valori DOAR din lista: [{$intentList}]
  - sentiment: "positive" | "neutral" | "negative" | "frustrated"
  - urgency: "low" | "medium" | "high" | "critical"
  - topics: array cu 1-3 keywords scurte (1-3 cuvinte fiecare, în RO)
  - lead_potential: "low" | "medium" | "high"
  - summary: o propoziție SCURTĂ în RO ce s-a întâmplat (max 100 char)

NU adăuga alte câmpuri. Răspunde DOAR cu JSON.
PROMPT;

        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => "TRANSCRIPT:\n{$transcript}"],
                ],
                'temperature' => 0.2,
                'response_format' => ['type' => 'json_object'],
                'max_tokens' => 300,
            ]);

            $raw = $response->choices[0]->message->content ?? '{}';
            $parsed = json_decode($raw, true) ?: [];

            $cleaned = [
                'intents' => array_values(array_intersect(
                    is_array($parsed['intents'] ?? null) ? $parsed['intents'] : [],
                    self::INTENTS,
                )),
                'sentiment' => in_array($parsed['sentiment'] ?? '', ['positive', 'neutral', 'negative', 'frustrated'], true)
                    ? $parsed['sentiment'] : 'neutral',
                'urgency' => in_array($parsed['urgency'] ?? '', ['low', 'medium', 'high', 'critical'], true)
                    ? $parsed['urgency'] : 'low',
                'topics' => array_slice(array_filter(
                    is_array($parsed['topics'] ?? null) ? $parsed['topics'] : [],
                    fn ($t) => is_string($t) && trim($t) !== '',
                ), 0, 3),
                'lead_potential' => in_array($parsed['lead_potential'] ?? '', ['low', 'medium', 'high'], true)
                    ? $parsed['lead_potential'] : 'low',
                'summary' => mb_substr((string) ($parsed['summary'] ?? ''), 0, 100),
                'tagged_at' => now()->toIso8601String(),
                'model' => 'gpt-4o-mini',
            ];

            // Persistă în metadata
            $metadata = $conversation->metadata ?? [];
            $metadata['auto_tags'] = $cleaned;
            $conversation->metadata = $metadata;
            $conversation->save();

            return response()->json(array_merge(['cached' => false], $cleaned));
        } catch (\Throwable $e) {
            \Log::warning('AutoTag failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Tagging eșuat: ' . $e->getMessage()], 502);
        }
    }
}
