<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\RateLimiter;

class ChatbotApiController extends Controller
{
    /**
     * Return public configuration for a chatbot channel.
     */
    public function config(Channel $channel): JsonResponse
    {
        if ($channel->type !== Channel::TYPE_WEB_CHATBOT) {
            return response()->json(['error' => 'Canal invalid.'], 404);
        }

        if (!$channel->is_active) {
            return response()->json(['error' => 'Canalul nu este activ.'], 403);
        }

        $channelConfig = $channel->config ?? [];
        $bot = $channel->bot;

        return response()->json([
            'bot_name' => $bot->name ?? 'Sambla Bot',
            'greeting' => $channelConfig['greeting'] ?? 'Bună! Cu ce te pot ajuta?',
            'color' => $channelConfig['color'] ?? '#991b1b',
            'language' => $bot->language ?? 'ro',
        ]);
    }

    /**
     * Handle an incoming chatbot message.
     */
    public function message(Request $request, Channel $channel): JsonResponse
    {
        // Validate channel type and status
        if ($channel->type !== Channel::TYPE_WEB_CHATBOT) {
            return response()->json(['error' => 'Canal invalid.'], 404);
        }

        if (!$channel->is_active) {
            return response()->json(['error' => 'Canalul nu este activ.'], 403);
        }

        // Validate input
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'session_id' => 'nullable|string|max:100',
        ]);

        $sessionId = $validated['session_id'] ?? '';
        $userMessage = $validated['message'];

        // Rate limiting: 30 messages per minute per session
        $rateLimitKey = 'chatbot:' . ($sessionId ?: $request->ip());
        if (RateLimiter::tooManyAttempts($rateLimitKey, 30)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return response()->json([
                'error' => 'Prea multe mesaje. Vă rugăm așteptați ' . $seconds . ' secunde.',
            ], 429);
        }
        RateLimiter::hit($rateLimitKey, 60);

        $bot = $channel->bot;

        // Find or create conversation
        $conversation = null;
        if ($sessionId) {
            $conversation = Conversation::where('channel_id', $channel->id)
                ->where('external_conversation_id', $sessionId)
                ->where('status', 'active')
                ->first();
        }

        if (!$conversation) {
            $sessionId = Str::uuid()->toString();
            $conversation = Conversation::create([
                'tenant_id' => $bot->tenant_id,
                'bot_id' => $bot->id,
                'channel_id' => $channel->id,
                'external_conversation_id' => $sessionId,
                'contact_identifier' => $request->ip(),
                'status' => 'active',
                'metadata' => [
                    'user_agent' => $request->userAgent(),
                    'origin' => $request->header('Origin', ''),
                ],
                'started_at' => now(),
            ]);
        }

        // Save user message
        Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'content' => $userMessage,
            'content_type' => 'text',
            'sent_at' => now(),
        ]);

        // Increment message count
        $conversation->increment('messages_count');

        // Generate mock AI response based on system prompt
        $botResponse = $this->generateMockResponse($bot, $userMessage);

        // Save bot response
        Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'content' => $botResponse,
            'content_type' => 'text',
            'sent_at' => now(),
        ]);

        $conversation->increment('messages_count');

        // Update channel last activity
        $channel->update(['last_activity_at' => now()]);

        return response()->json([
            'response' => $botResponse,
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Generate a mock AI response.
     * This will be replaced with actual AI integration.
     */
    private function generateMockResponse($bot, string $userMessage): string
    {
        $systemPrompt = $bot->system_prompt ?? '';
        $botName = $bot->name ?? 'Sambla Bot';

        // Simple keyword-based mock responses in Romanian
        $lower = mb_strtolower($userMessage);

        if (Str::contains($lower, ['salut', 'buna', 'bună', 'hello', 'hey', 'alo'])) {
            return 'Bună! Sunt ' . $botName . '. Cu ce te pot ajuta astăzi?';
        }

        if (Str::contains($lower, ['pret', 'preț', 'cost', 'tarif', 'cât costă'])) {
            return 'Pentru informații despre prețuri, vă rugăm să vizitați pagina noastră de prețuri sau să ne contactați direct. Vă pot ajuta cu alte întrebări?';
        }

        if (Str::contains($lower, ['multumesc', 'mulțumesc', 'mersi', 'thanks'])) {
            return 'Cu plăcere! Dacă mai ai nevoie de ajutor, nu ezita să întrebi.';
        }

        if (Str::contains($lower, ['ajutor', 'help', 'cum'])) {
            return 'Sunt aici să te ajut! Poți să-mi spui mai multe detalii despre ce ai nevoie?';
        }

        if (Str::contains($lower, ['contact', 'telefon', 'email'])) {
            return 'Ne poți contacta prin formularul de contact de pe site sau prin email. Pot să te ajut cu altceva?';
        }

        if (Str::contains($lower, ['pa', 'la revedere', 'bye', 'adio'])) {
            return 'La revedere! Sper că am fost de ajutor. O zi frumoasă!';
        }

        // Default response
        $defaults = [
            'Mulțumesc pentru mesaj. Am înregistrat cererea ta și voi reveni cu un răspuns detaliat.',
            'Înțeleg întrebarea ta. Lasă-mă să verific și revin imediat cu informațiile necesare.',
            'Bună întrebare! Voi căuta cele mai bune informații pentru tine.',
            'Apreciez interesul tău. Pot să te ajut cu mai multe detalii dacă îmi spui exact ce cauți.',
        ];

        return $defaults[array_rand($defaults)];
    }
}
