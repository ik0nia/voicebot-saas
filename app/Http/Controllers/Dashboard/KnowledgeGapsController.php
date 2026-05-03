<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\KnowledgeSearchLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * Knowledge gap auto-suggester — listă de query-uri pe care agentul nu
 * știa să le răspundă (zero results), grupate, plus draft de FAQ generat
 * de LLM pentru top N gap-uri.
 *
 * URL: /dashboard/agenti/{bot}/knowledge-gaps
 */
class KnowledgeGapsController extends Controller
{
    public function show(Bot $bot)
    {
        abort_unless(
            $bot->tenant_id === auth()->user()->tenant_id || auth()->user()->isSuperAdmin(),
            404,
        );

        // Top 30 zero-result queries în ultimele 30 zile
        $gaps = KnowledgeSearchLog::where('bot_id', $bot->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->where('results_count', 0)
            ->select('query', \DB::raw('COUNT(*) as occurrences'), \DB::raw('MAX(created_at) as last_seen'))
            ->groupBy('query')
            ->orderByDesc('occurrences')
            ->limit(30)
            ->get();

        // Total volum search 30d
        $totalSearches = KnowledgeSearchLog::where('bot_id', $bot->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        $zeroSearches = KnowledgeSearchLog::where('bot_id', $bot->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->where('results_count', 0)
            ->count();
        $zeroPct = $totalSearches > 0 ? round(($zeroSearches / $totalSearches) * 100, 1) : 0;

        return view('dashboard.knowledge-gaps.show', compact(
            'bot', 'gaps', 'totalSearches', 'zeroSearches', 'zeroPct',
        ));
    }

    /**
     * POST endpoint: primește un query → returnează draft FAQ (Q+A) generat de LLM.
     * Frontend afișează în modal cu copy button + opțiune „adaugă în KB".
     */
    public function suggestFaq(Request $request, Bot $bot): JsonResponse
    {
        abort_unless(
            $bot->tenant_id === auth()->user()->tenant_id || auth()->user()->isSuperAdmin(),
            404,
        );

        $validated = $request->validate([
            'query' => 'required|string|max:300',
        ]);

        $key = 'kb-gaps:tenant:' . (int) auth()->user()->tenant_id;
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 30)) {
            return response()->json(['error' => 'Prea multe sugestii — încearcă peste 1 min'], 429);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($key, 60);

        $context = $bot->system_prompt ? mb_substr($bot->system_prompt, 0, 2000) : '';

        $system = <<<PROMPT
Ești asistent care generează FAQ-uri pentru o bază de cunoștințe a unui agent AI conversațional.

Primești:
  - context (rolul + domeniul agentului, primii 2000 char din prompt)
  - un query care a returnat ZERO rezultate (gap KB)

Generează:
  - O întrebare CLARIFICATĂ (cum ar pune-o un client real, în RO)
  - Un răspuns SCURT (50-150 cuvinte) care ar acoperi acest gap

Răspunsul trebuie să:
  - Folosească tonul din context (formal vs prietenos)
  - NU inventeze informații specifice (prețuri, ore, nume) — folosește placeholder
    între acolade { } unde e nevoie (ex: „programul nostru este {luni-vineri 9-18}")
  - Fie acționabil (clientul să știe ce să facă next)

Răspunde DOAR cu JSON: { "question": "...", "answer": "..." }
PROMPT;

        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => "Context agent:\n{$context}\n\nQuery fără răspuns: \"{$validated['query']}\""],
                ],
                'temperature' => 0.3,
                'response_format' => ['type' => 'json_object'],
                'max_tokens' => 400,
            ]);

            $raw = $response->choices[0]->message->content ?? '{}';
            $parsed = json_decode($raw, true);

            return response()->json([
                'success' => true,
                'question' => mb_substr((string) ($parsed['question'] ?? $validated['query']), 0, 300),
                'answer' => mb_substr((string) ($parsed['answer'] ?? ''), 0, 2000),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('KnowledgeGapsController suggestFaq failed', [
                'bot_id' => $bot->id,
                'query' => $validated['query'],
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Generare eșuată: ' . $e->getMessage()], 502);
        }
    }
}
