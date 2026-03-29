<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\HandoffRequest;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class HandoffService
{
    public function shouldOffer(Conversation $conversation, array $intents): bool
    {
        foreach ($intents as $i) {
            $name = is_array($i) ? ($i['name'] ?? '') : ($i->name ?? '');
            if ($name === 'handoff_intent') return true;
        }
        return false;
    }

    public function createHandoff(Conversation $conversation, string $reason, array $intents = []): HandoffRequest
    {
        $messages = Message::where('conversation_id', $conversation->id)->orderBy('id')->get();
        $summary = $this->buildSummary($messages);

        $productsShown = [];
        foreach ($messages as $msg) {
            if ($msg->metadata && isset($msg->metadata['products'])) {
                foreach ($msg->metadata['products'] as $p) {
                    $productsShown[] = ['name' => $p['name'] ?? '', 'price' => $p['price'] ?? ''];
                }
            }
        }

        return HandoffRequest::create([
            'tenant_id' => $conversation->tenant_id,
            'bot_id' => $conversation->bot_id,
            'conversation_id' => $conversation->id,
            'status' => 'pending',
            'trigger_reason' => $reason,
            'conversation_summary' => $summary,
            'detected_intents' => array_map(fn($i) => is_array($i) ? $i : $i->toArray(), $intents),
            'products_shown' => array_slice($productsShown, 0, 10),
            'recommended_action' => 'Contact customer regarding: ' . $reason,
        ]);
    }

    private function buildSummary(mixed $messages): string
    {
        $lines = [];
        foreach ($messages->take(20) as $msg) {
            $role = $msg->direction === 'inbound' ? 'Client' : 'Bot';
            $lines[] = "{$role}: " . mb_substr($msg->content, 0, 200);
        }
        return implode("\n", $lines);
    }
}
