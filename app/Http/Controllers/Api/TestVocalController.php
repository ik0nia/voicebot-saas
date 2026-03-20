<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TestVocalController extends Controller
{
    /**
     * Mock responses for vocal test simulator.
     * Accepts POST with text message or audio blob and returns a mock AI response.
     */
    public function handle(Request $request, Bot $bot): JsonResponse
    {
        $userMessage = $request->input('message', '');

        // If audio was sent, simulate a transcription
        if ($request->hasFile('audio') || $request->has('audio_blob')) {
            $userMessage = $this->mockTranscribe();
        }

        if (empty($userMessage)) {
            $userMessage = 'Bună ziua!';
        }

        // Generate a mock response based on the bot's system_prompt
        $botResponse = $this->generateMockResponse($bot, $userMessage);

        return response()->json([
            'response' => $botResponse,
            'transcript' => $userMessage,
            'bot_name' => $bot->name,
            'voice' => $bot->voice ?? 'nova',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Simulate audio transcription with mock phrases.
     */
    private function mockTranscribe(): string
    {
        $phrases = [
            'Bună ziua, aș dori mai multe informații.',
            'Care sunt opțiunile disponibile?',
            'Puteți să-mi spuneți mai multe despre serviciile voastre?',
            'Aș vrea să fac o programare.',
            'Mulțumesc pentru informații.',
            'Cât costă acest serviciu?',
            'Aveți disponibilitate săptămâna viitoare?',
            'Pot să vorbesc cu un operator uman?',
        ];

        return $phrases[array_rand($phrases)];
    }

    /**
     * Generate a mock bot response based on the system prompt context.
     */
    private function generateMockResponse(Bot $bot, string $userMessage): string
    {
        $botName = $bot->name;
        $hasPrompt = !empty($bot->system_prompt);

        // Context-aware mock responses
        $lowerMessage = mb_strtolower($userMessage);

        if (str_contains($lowerMessage, 'bună') || str_contains($lowerMessage, 'salut')) {
            return "Bună ziua! Sunt {$botName}, asistentul vocal. Cu ce vă pot ajuta astăzi?";
        }

        if (str_contains($lowerMessage, 'informații') || str_contains($lowerMessage, 'detalii')) {
            return "Desigur! Vă pot oferi informații detaliate. Ce anume vă interesează?";
        }

        if (str_contains($lowerMessage, 'programare') || str_contains($lowerMessage, 'rezervare')) {
            return "Cu plăcere! Pot să vă ajut cu o programare. Ce dată și oră preferați?";
        }

        if (str_contains($lowerMessage, 'cost') || str_contains($lowerMessage, 'preț') || str_contains($lowerMessage, 'pret')) {
            return "Prețurile noastre variază în funcție de serviciul ales. Pot să vă ofer o ofertă personalizată. Ce serviciu vă interesează?";
        }

        if (str_contains($lowerMessage, 'mulțumesc') || str_contains($lowerMessage, 'multumesc')) {
            return "Cu plăcere! Dacă mai aveți întrebări, nu ezitați să mă contactați. O zi bună!";
        }

        if (str_contains($lowerMessage, 'operator') || str_contains($lowerMessage, 'uman')) {
            return "Înțeleg. Vă pot transfera către un operator uman. Vă rog să rămâneți pe linie.";
        }

        if (str_contains($lowerMessage, 'opțiuni') || str_contains($lowerMessage, 'servicii')) {
            return "Avem mai multe opțiuni disponibile. Permiteți-mi să vă prezint cele mai populare variante.";
        }

        if (str_contains($lowerMessage, 'disponibil')) {
            return "Da, avem disponibilitate. Preferați o anumită zi sau interval orar?";
        }

        // Default response
        $defaults = [
            "Am înțeles. Permiteți-mi să verific acest lucru pentru dumneavoastră.",
            "Vă mulțumesc pentru întrebare. Iată ce vă pot spune...",
            "Este o întrebare bună! Permiteți-mi să vă ofer mai multe detalii.",
            "Desigur, vă pot ajuta cu aceasta. Ce alte informații aveți nevoie?",
        ];

        return $defaults[array_rand($defaults)];
    }
}
