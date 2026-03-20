<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class ChannelMessageService
{
    /**
     * Process an incoming message from any channel (WhatsApp, Facebook, Instagram, etc.)
     *
     * @param Channel $channel
     * @param string $contactId External identifier for the contact (phone number, PSID, etc.)
     * @param string $contactName Display name for the contact
     * @param string $messageText The incoming message text
     * @return array{response: string, conversation: Conversation}
     */
    public function processIncomingMessage(Channel $channel, string $contactId, string $contactName, string $messageText): array
    {
        $bot = $channel->bot;

        // Find or create conversation
        $conversation = Conversation::firstOrCreate(
            [
                'channel_id' => $channel->id,
                'contact_identifier' => $contactId,
                'status' => 'active',
            ],
            [
                'tenant_id' => $bot->tenant_id,
                'bot_id' => $bot->id,
                'contact_name' => $contactName,
                'external_conversation_id' => $channel->type . '_' . $contactId,
                'messages_count' => 0,
                'metadata' => [
                    'channel_type' => $channel->type,
                    'contact_id' => $contactId,
                ],
                'started_at' => now(),
            ]
        );

        // Update contact name if it was previously unknown
        if ($conversation->contact_name !== $contactName && $contactName !== 'Unknown') {
            $conversation->update(['contact_name' => $contactName]);
        }

        // Save inbound message
        $inboundMessage = Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'content' => $messageText,
            'content_type' => 'text',
            'sent_at' => now(),
        ]);

        // Build AI prompt context
        $systemPrompt = $bot->buildSystemPrompt();

        // Get conversation history (last 10 messages)
        $history = $conversation->messages()
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->reverse()
            ->map(function (Message $msg) {
                $role = $msg->direction === 'inbound' ? 'User' : 'Assistant';
                return "{$role}: {$msg->content}";
            })
            ->implode("\n");

        // Generate mock AI response (to be replaced with actual AI integration)
        $response = $this->generateMockResponse($bot->name, $messageText, $systemPrompt);

        // Save outbound message
        $outboundMessage = Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'content' => $response,
            'content_type' => 'text',
            'sent_at' => now(),
        ]);

        // Update conversation messages count
        $conversation->update([
            'messages_count' => $conversation->messages()->count(),
        ]);

        // Update channel last activity
        $channel->update([
            'last_activity_at' => now(),
        ]);

        Log::info("Processed incoming message on channel [{$channel->type}]", [
            'channel_id' => $channel->id,
            'conversation_id' => $conversation->id,
            'contact_id' => $contactId,
        ]);

        return [
            'response' => $response,
            'conversation' => $conversation,
        ];
    }

    /**
     * Generate a mock AI response (placeholder until real AI integration).
     */
    private function generateMockResponse(string $botName, string $messageText, string $systemPrompt): string
    {
        $greeting = "Buna! Sunt {$botName}, asistentul tau virtual.";

        $lowerMessage = mb_strtolower($messageText);

        if (str_contains($lowerMessage, 'salut') || str_contains($lowerMessage, 'buna') || str_contains($lowerMessage, 'hello') || str_contains($lowerMessage, 'hi')) {
            return "{$greeting} Cu ce te pot ajuta astazi?";
        }

        if (str_contains($lowerMessage, 'pret') || str_contains($lowerMessage, 'cost') || str_contains($lowerMessage, 'tarif')) {
            return "Multumesc pentru interes! Un coleg te va contacta in curand cu detaliile de pret. Pot sa te ajut cu altceva intre timp?";
        }

        if (str_contains($lowerMessage, 'multumesc') || str_contains($lowerMessage, 'mersi') || str_contains($lowerMessage, 'thanks')) {
            return "Cu placere! Daca mai ai intrebari, nu ezita sa ma contactezi. O zi frumoasa!";
        }

        return "{$greeting} Am primit mesajul tau. Un coleg din echipa noastra te va contacta in curand pentru a te ajuta. Multumesc pentru rabdare!";
    }
}
