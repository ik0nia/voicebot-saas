<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * Smart reply — propune 3 răspunsuri scurte pentru operatorul uman care
 * preluat o conversație de la bot. Bazat pe contextul ultimelor 6 mesaje.
 *
 * Variantele:
 *   1. Scurt și prietenos
 *   2. Detaliat și informativ
 *   3. Cere clarificare / pune întrebare
 */
class SmartReplyController extends Controller
{
    public function suggest(Conversation $conversation): JsonResponse
    {
        abort_unless(
            $conversation->tenant_id === auth()->user()->tenant_id || auth()->user()->isSuperAdmin(),
            404,
        );

        // Throttle 60/min/tenant — operatorul poate cere multe sugestii
        $key = 'smart-reply:tenant:' . (int) auth()->user()->tenant_id;
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 60)) {
            return response()->json(['error' => 'Prea multe cereri'], 429);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($key, 60);

        // Context: ultimele 6 mesaje + bot prompt scurt
        $recent = $conversation->messages()
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->reverse()
            ->values();

        if ($recent->isEmpty()) {
            return response()->json([
                'replies' => [
                    ['style' => 'short',     'text' => 'Bună! Cu ce te pot ajuta?'],
                    ['style' => 'detailed',  'text' => 'Bună ziua! Sunt aici să răspund la întrebările dvs. Cu ce vă pot ajuta?'],
                    ['style' => 'question',  'text' => 'Bună! Ai vreo întrebare specifică despre serviciile noastre?'],
                ],
            ]);
        }

        $bot = $conversation->bot;
        $contextLines = [];
        foreach ($recent as $msg) {
            $role = ($msg->role ?? 'user') === 'user' ? 'CLIENT' : 'AGENT';
            $contextLines[] = "[{$role}] " . mb_substr((string) $msg->content, 0, 300);
        }
        $contextText = implode("\n\n", $contextLines);
        $botContext = $bot ? mb_substr((string) ($bot->system_prompt ?? ''), 0, 800) : '';

        $system = <<<PROMPT
Ești asistent pentru un OPERATOR UMAN care a preluat o conversație de la un agent AI.
Operatorul vede ultimele 6 mesaje și vrea 3 sugestii de RĂSPUNS pe care să le aleagă cu un click.

Generează 3 variante de răspuns adresate clientului, în limba română:
  1. SCURT (1 propoziție, prietenos, direct)
  2. DETALIAT (2-3 propoziții, informativ, profesional)
  3. CLARIFICARE (pune o întrebare de clarificare ca să afli mai mult)

Reguli:
  - Adresare în „tu" sau „dvs." conform contextului anterior
  - NU inventa date specifice (prețuri, ore, nume) — folosește placeholder cu [...]
    DOAR dacă neapărat. Preferă răspunsuri generice, naturale.
  - Răspunsuri SCURTE — operatorul le va edita oricum
  - Tonul: continuă tonul existent al agentului

Răspunde DOAR cu JSON:
{
  "replies": [
    {"style": "short", "text": "..."},
    {"style": "detailed", "text": "..."},
    {"style": "question", "text": "..."}
  ]
}
PROMPT;

        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => "Context agent:\n{$botContext}\n\nUltimele mesaje:\n{$contextText}\n\nGenerează cele 3 sugestii."],
                ],
                'temperature' => 0.5,
                'response_format' => ['type' => 'json_object'],
                'max_tokens' => 400,
            ]);

            $raw = $response->choices[0]->message->content ?? '{}';
            $parsed = json_decode($raw, true) ?: [];

            $replies = $parsed['replies'] ?? [];
            $clean = [];
            foreach (array_slice($replies, 0, 3) as $r) {
                if (!is_array($r)) continue;
                $clean[] = [
                    'style' => in_array($r['style'] ?? 'short', ['short', 'detailed', 'question'], true) ? $r['style'] : 'short',
                    'text'  => mb_substr((string) ($r['text'] ?? ''), 0, 500),
                ];
            }

            return response()->json([
                'success' => true,
                'replies' => $clean,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('SmartReply failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Generare eșuată: ' . $e->getMessage()], 502);
        }
    }
}
