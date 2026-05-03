<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Channel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * Mock customer simulator — un LLM (gpt-4o-mini) joacă rolul unui client cu
 * o intenție (programare, info preț, complaint, etc) și are 5-10 ture cu
 * agentul real al tenantului. La final, generează un raport de calitate:
 *   - Atinsese intenția? (yes/no/partial)
 *   - Ce a mers bine?
 *   - Ce a mers prost?
 *   - Sugestii de îmbunătățire prompt
 *
 * URL: /dashboard/agenti/{bot}/mock-customer
 */
class MockCustomerController extends Controller
{
    public function show(Bot $bot)
    {
        abort_unless(
            $bot->tenant_id === auth()->user()->tenant_id || auth()->user()->isSuperAdmin(),
            404,
        );

        $personas = [
            ['id' => 'programare',  'name' => 'Vrea programare urgentă', 'desc' => 'Cere o programare cât mai curând, e grăbit, vrea preț + ora.'],
            ['id' => 'info_pret',   'name' => 'Întreabă despre preț',    'desc' => 'Curios despre prețuri, vrea să compare opțiuni.'],
            ['id' => 'complaint',   'name' => 'Are o nemulțumire',       'desc' => 'Plângere la programarea anterioară, vrea soluție.'],
            ['id' => 'shopper',     'name' => 'Cumpărător indecis',      'desc' => 'Caută informații, nu sigur ce vrea, are obiecții.'],
            ['id' => 'first_time',  'name' => 'Client nou, întrebări de bază', 'desc' => 'Prima oară când contactează, întreabă „cum funcționează".'],
        ];

        $webChannel = $bot->channels()->where('type', Channel::TYPE_WEB_CHATBOT)->first();
        if (!$webChannel) {
            $webChannel = $bot->channels()->create([
                'type' => Channel::TYPE_WEB_CHATBOT,
                'name' => 'Web Chatbot',
                'is_active' => true,
                'config' => ['greeting' => $bot->greeting_message ?: 'Bună!'],
            ]);
        }

        return view('dashboard.mock-customer.show', compact('bot', 'personas', 'webChannel'));
    }

    /**
     * POST: rulează simularea în background sync (max 30s).
     * Returnează transcript + raport de evaluare.
     */
    public function run(Request $request, Bot $bot): JsonResponse
    {
        abort_unless(
            $bot->tenant_id === auth()->user()->tenant_id || auth()->user()->isSuperAdmin(),
            404,
        );

        $validated = $request->validate([
            'persona' => 'required|string|max:50',
            'turns'   => 'required|integer|min:3|max:8',
            'channel_id' => 'required|integer|exists:channels,id',
        ]);

        // Throttle 5 simulări/min/tenant (each = ~10 LLM calls)
        $key = 'mock-customer:' . (auth()->user()->tenant_id ?? 0);
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['error' => 'Prea multe simulări — așteaptă 1 min'], 429);
        }
        RateLimiter::hit($key, 60);

        $personaPrompts = [
            'programare' => 'Joci rolul unui CLIENT REAL care vrea să-și facă o programare URGENTĂ (azi sau mâine) la firma asta. Ești ușor grăbit, întrebi despre disponibilitate, preț. Vorbești natural în română, scurt (1-2 propoziții/replică). Dacă agentul îți cere date (nume, telefon), oferă valori plauzibile: „Andrei Popescu, 0721234567". NU rupe rolul. NU spune că ești AI.',
            'info_pret'  => 'Joci rolul unui CLIENT care compară prețuri. Întrebi despre prețuri, opțiuni, ce e inclus. Ești sceptic, vrei detalii. Replică scurtă, natural, în română. NU rupe rolul.',
            'complaint'  => 'Joci rolul unui CLIENT cu o NEMULȚUMIRE. „Acum 2 zile am venit la programarea X și ceva n-a fost OK". Ești civil dar ferm. Vrei o soluție concretă. Replică scurtă, natural. NU rupe rolul.',
            'shopper'    => 'Joci rolul unui CLIENT INDECIS care nu știe exact ce vrea. Faci întrebări vagi, ai obiecții („e cam scump", „nu sunt sigur"), agentul trebuie să te convingă. Replică scurtă, natural. NU rupe rolul.',
            'first_time' => 'Joci rolul unui CLIENT NOU care n-a mai folosit serviciul. Întrebări de bază: „cum funcționează?", „unde sunteți?", „pot veni fără programare?". Replică scurtă, natural. NU rupe rolul.',
        ];

        $personaPrompt = $personaPrompts[$validated['persona']] ?? $personaPrompts['programare'];
        $sessionId = 'mock-' . uniqid();
        $transcript = [];
        $turns = $validated['turns'];

        try {
            // Turn 0: customer initiates
            $customerMessage = $this->generateCustomerMessage($personaPrompt, $transcript, true);
            $transcript[] = ['role' => 'customer', 'content' => $customerMessage];

            for ($i = 0; $i < $turns; $i++) {
                // Send to bot via internal HTTP (uses real chat pipeline)
                $botReply = $this->callBot($validated['channel_id'], $customerMessage, $sessionId);
                $transcript[] = ['role' => 'bot', 'content' => $botReply];

                // Generate next customer turn (unless last)
                if ($i < $turns - 1) {
                    $customerMessage = $this->generateCustomerMessage($personaPrompt, $transcript, false);
                    if (str_contains(strtolower($customerMessage), '[end_conversation]')) {
                        break;
                    }
                    $transcript[] = ['role' => 'customer', 'content' => $customerMessage];
                }
            }

            // Generate evaluation report
            $report = $this->evaluate($personaPrompt, $transcript);

            return response()->json([
                'success' => true,
                'transcript' => $transcript,
                'report' => $report,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('MockCustomer simulation failed', [
                'bot_id' => $bot->id,
                'persona' => $validated['persona'],
                'error' => $e->getMessage(),
                'partial_transcript' => count($transcript),
            ]);
            return response()->json([
                'error' => 'Simulare eșuată: ' . $e->getMessage(),
                'partial_transcript' => $transcript,
            ], 502);
        }
    }

    private function generateCustomerMessage(string $personaPrompt, array $transcript, bool $isFirst): string
    {
        $hint = $isFirst
            ? "\nÎNCEPE conversația cu primul tău mesaj — ca și cum ai deschide chat-ul pe site."
            : "\nDă următorul tău mesaj. Dacă scopul a fost atins SAU agentul nu poate ajuta, scrie EXACT [END_CONVERSATION] pentru a termina politicos.";

        $messages = [['role' => 'system', 'content' => $personaPrompt . $hint]];

        // Convert transcript to chat format (customer = user, bot = assistant from MOCK perspective)
        foreach ($transcript as $msg) {
            $messages[] = [
                'role' => $msg['role'] === 'customer' ? 'assistant' : 'user',
                'content' => $msg['content'],
            ];
        }

        $resp = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => $messages,
            'temperature' => 0.8,
            'max_tokens' => 150,
        ]);

        return trim($resp->choices[0]->message->content ?? '...');
    }

    private function callBot(int $channelId, string $message, string $sessionId): string
    {
        // Apel intern către widget API — folosește pipeline-ul real RAG/tools
        $url = url("/api/v1/chatbot/{$channelId}/message");
        $resp = Http::timeout(30)
            ->withHeaders(['Accept' => 'application/json', 'Content-Type' => 'application/json'])
            ->post($url, [
                'message' => $message,
                'session_id' => $sessionId,
                'contact_identifier' => 'mock-customer@sambla.ro',
            ]);

        $data = $resp->json();
        return (string) ($data['reply'] ?? $data['message'] ?? $data['content'] ?? '(răspuns gol)');
    }

    private function evaluate(string $personaPrompt, array $transcript): array
    {
        $transcriptText = '';
        foreach ($transcript as $msg) {
            $label = $msg['role'] === 'customer' ? 'CLIENT' : 'AGENT';
            $transcriptText .= "[{$label}] {$msg['content']}\n\n";
        }

        $system = <<<PROMPT
Ești evaluator pentru un agent AI conversațional. Primești un transcript dintre un client (jucat de un alt LLM) și agentul AI real.

Evaluează agentul pe 4 dimensiuni:
  1. ATINGEREA SCOPULUI (clientul a obținut ce voia? scor 0-100)
  2. NATURALEȚE (sună uman? prea robotic? scor 0-100)
  3. EFICIENȚĂ (a rezolvat în puține mesaje? scor 0-100)
  4. CALITATE INFORMAȚIE (a dat date concrete sau evazive? scor 0-100)

Apoi:
  - 2-3 lucruri care AU MERS BINE (bullets scurte)
  - 2-3 lucruri DE ÎMBUNĂTĂȚIT (bullets scurte, acționabile pentru prompt)
  - O recomandare SPECIFICĂ pentru prompt (1 propoziție)

Răspunde DOAR cu JSON:
{
  "scores": { "goal": N, "natural": N, "efficient": N, "information": N, "overall": N },
  "verdict": "good" | "ok" | "needs_work",
  "wins": ["..."],
  "issues": ["..."],
  "recommendation": "..."
}
PROMPT;

        $resp = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => "Persona client: {$personaPrompt}\n\nTRANSCRIPT:\n{$transcriptText}"],
            ],
            'temperature' => 0.3,
            'response_format' => ['type' => 'json_object'],
            'max_tokens' => 600,
        ]);

        $raw = $resp->choices[0]->message->content ?? '{}';
        $parsed = json_decode($raw, true) ?: [];

        // Defensive shape
        return [
            'scores' => array_merge([
                'goal' => 0, 'natural' => 0, 'efficient' => 0, 'information' => 0, 'overall' => 0,
            ], $parsed['scores'] ?? []),
            'verdict' => in_array($parsed['verdict'] ?? '', ['good', 'ok', 'needs_work'], true) ? $parsed['verdict'] : 'ok',
            'wins' => array_slice($parsed['wins'] ?? [], 0, 5),
            'issues' => array_slice($parsed['issues'] ?? [], 0, 5),
            'recommendation' => mb_substr((string) ($parsed['recommendation'] ?? ''), 0, 500),
        ];
    }
}
