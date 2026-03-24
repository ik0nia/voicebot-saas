<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Services\KnowledgeSearchService;
use App\Services\OrderLookupService;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TestVocalController extends Controller
{
    public function handle(Request $request, Bot $bot): JsonResponse
    {
        $userMessage = $request->input('message', '');
        $history = $request->input('history', []);
        $isGreeting = $userMessage === '__greeting__';

        $openai = new OpenAIService();

        if ($isGreeting) {
            $greetingPrompt = 'Generează un salut scurt și prietenos de maxim 2 propoziții pentru un apelant. Prezintă-te pe scurt.';
            $botResponse = $openai->chat($bot, [], $greetingPrompt);
        } else {
            if (empty($userMessage)) {
                $userMessage = 'Bună ziua!';
            }

            // Check for order query
            $orderLookup = app(OrderLookupService::class);
            $orderParams = $orderLookup->detectOrderQuery($userMessage);
            $extraContext = '';

            if ($orderParams !== null) {
                $orderResult = $orderLookup->lookup($bot->id, $orderParams);
                if ($orderResult['found']) {
                    $extraContext = "\n\n[INFORMAȚII COMANDĂ]\n";
                    foreach ($orderResult['orders'] as $o) {
                        $extraContext .= "Comanda #{$o['number']} | Status: {$o['status']} | Data: {$o['date']} | Total: {$o['total']}";
                        $extraContext .= " | Plata: {$o['payment_method']} | Livrare: {$o['shipping_method']}";
                        if ($o['tracking']) $extraContext .= " | AWB: {$o['tracking']}";
                        $extraContext .= " | Produse: " . collect($o['items'])->map(fn($i) => "{$i['name']} x{$i['quantity']}")->implode(', ');
                        $extraContext .= "\n";
                    }
                } elseif (empty($orderParams['order_number']) && empty($orderParams['email']) && empty($orderParams['phone'])) {
                    $extraContext = "\n\n[Clientul întreabă de o comandă. Cere-i numărul comenzii sau emailul cu care a comandat.]";
                } else {
                    $extraContext = "\n\n[{$orderResult['message']}]";
                }
            }

            $botResponse = $openai->chat($bot, $history, $userMessage, $extraContext);
        }

        // Generate TTS audio
        $voiceMap = [
            'masculin' => 'onyx', 'feminin' => 'nova', 'nova' => 'nova',
            'alloy' => 'alloy', 'echo' => 'echo', 'fable' => 'fable',
            'onyx' => 'onyx', 'shimmer' => 'shimmer',
        ];
        $voice = $voiceMap[$bot->voice ?? 'nova'] ?? 'nova';
        $audioBase64 = $openai->textToSpeech($botResponse, $voice);

        return response()->json([
            'response' => $botResponse,
            'transcript' => $userMessage,
            'audio' => $audioBase64,
            'bot_name' => $bot->name,
            'voice' => $voice,
        ]);
    }
}
