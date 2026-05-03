<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * A/B prompt comparison playground — testează rapid 2 versiuni de prompt
 * pe ACELAȘI input. NU persistă conversații, NU afectează agentul real.
 *
 * UI: 2 panouri side-by-side cu textarea pentru system_prompt + chat
 * tester comun (un singur input → 2 răspunsuri paralele).
 *
 * Backend: bypass-ăm pipeline-ul RAG/tool-uri și apelăm direct
 * OpenAI Chat Completions ca să comparăm DOAR diferențele de prompt
 * (nu impactul retrieval-ului).
 */
class AbPromptController extends Controller
{
    public function show(Bot $bot)
    {
        abort_unless(
            $bot->tenant_id === auth()->user()->tenant_id || auth()->user()->isSuperAdmin(),
            404,
        );

        return view('dashboard.ab-prompt.show', [
            'bot' => $bot,
            'currentPrompt' => $bot->system_prompt ?: 'Ești asistentul ' . $bot->name . '. Răspunde scurt și prietenos.',
        ]);
    }

    /**
     * POST endpoint: primește (prompt_a, prompt_b, message, history?) și
     * întoarce {a: 'reply', b: 'reply', tokens: {...}, cost_ron: N}.
     */
    public function compare(Request $request, Bot $bot): JsonResponse
    {
        abort_unless(
            $bot->tenant_id === auth()->user()->tenant_id || auth()->user()->isSuperAdmin(),
            404,
        );

        $validated = $request->validate([
            'prompt_a' => 'required|string|max:10000',
            'prompt_b' => 'required|string|max:10000',
            'message'  => 'required|string|max:2000',
            'history_a' => 'nullable|array',
            'history_b' => 'nullable|array',
        ]);

        // Throttle per tenant — A/B costă 2x un chat normal
        $key = 'ab-prompt:tenant:' . (int) auth()->user()->tenant_id;
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 30)) {
            return response()->json([
                'error' => 'Prea multe comparări A/B — încearcă peste un minut.',
            ], 429);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($key, 60);

        $model = config('openai.chat_model', 'gpt-4o-mini');

        try {
            // Run both calls in parallel via OpenAI's batch (sync — small)
            // Could use Http::pool but simpler sequentially since both
            // hit same endpoint and PHP is single-threaded.
            $resA = $this->callOpenAi($model, $validated['prompt_a'], $validated['history_a'] ?? [], $validated['message']);
            $resB = $this->callOpenAi($model, $validated['prompt_b'], $validated['history_b'] ?? [], $validated['message']);

            return response()->json([
                'a' => [
                    'content' => $resA['content'],
                    'tokens_in' => $resA['tokens_in'],
                    'tokens_out' => $resA['tokens_out'],
                ],
                'b' => [
                    'content' => $resB['content'],
                    'tokens_in' => $resB['tokens_in'],
                    'tokens_out' => $resB['tokens_out'],
                ],
                'model' => $model,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('AbPromptController failed', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Eroare la generare: ' . $e->getMessage(),
            ], 502);
        }
    }

    private function callOpenAi(string $model, string $systemPrompt, array $history, string $userMessage): array
    {
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];
        foreach ($history as $msg) {
            if (!isset($msg['role'], $msg['content'])) continue;
            if (!in_array($msg['role'], ['user', 'assistant'])) continue;
            $messages[] = [
                'role' => $msg['role'],
                'content' => mb_substr((string) $msg['content'], 0, 2000),
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $response = OpenAI::chat()->create([
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 500,
        ]);

        return [
            'content' => $response->choices[0]->message->content ?? '',
            'tokens_in' => $response->usage->promptTokens ?? 0,
            'tokens_out' => $response->usage->completionTokens ?? 0,
        ];
    }
}
