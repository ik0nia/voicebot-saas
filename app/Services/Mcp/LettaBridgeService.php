<?php

declare(strict_types=1);

namespace App\Services\Mcp;

use App\Models\Contact;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Bridge to a Letta (formerly MemGPT) sidecar for cross-channel memory.
 *
 * If Letta is deployed (services.letta.url set), every Contact gets a
 * letta_agent_id on first inbound message. Subsequent messages — across
 * any channel — go through Letta, which maintains a tool-controlled
 * memory block (human + persona) that persists between sessions.
 *
 * If Letta is not deployed, this service short-circuits gracefully:
 * isAvailable() returns false and the orchestrator falls through to its
 * existing direct-LLM path. Adding/removing Letta is a deploy-time
 * decision, not a code change.
 */
class LettaBridgeService
{
    public function isAvailable(): bool
    {
        return !empty(config('services.letta.url'));
    }

    /**
     * Ensure the Contact has a Letta agent. Creates one if absent.
     * Returns the agent_id stamped on the Contact, or null if Letta
     * is unavailable / errored (caller should fall through to direct LLM).
     */
    public function ensureAgentFor(Contact $contact): ?string
    {
        if (!$this->isAvailable()) {
            return null;
        }
        if ($contact->letta_agent_id) {
            return $contact->letta_agent_id;
        }

        try {
            $response = Http::timeout(20)
                ->withToken(config('services.letta.token'))
                ->post(config('services.letta.url') . '/v1/agents', [
                    'name' => "sambla_contact_{$contact->id}",
                    'memory_blocks' => [
                        [
                            'label' => 'human',
                            'value' => $this->humanMemoryFor($contact),
                            'limit' => 2000,
                        ],
                        [
                            'label' => 'persona',
                            'value' => 'Sunt un agent AI Sambla — ajut clientul cu programări, întrebări și suport. Vorbesc românește, ton cald dar profesional.',
                            'limit' => 2000,
                        ],
                    ],
                    'tools' => ['core_memory_replace', 'core_memory_append', 'send_message'],
                ]);

            if (!$response->successful()) {
                Log::warning('LettaBridgeService: agent create failed', [
                    'contact_id' => $contact->id,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $agentId = $response->json('id');
            if ($agentId) {
                $contact->update(['letta_agent_id' => $agentId]);
                return $agentId;
            }
        } catch (\Throwable $e) {
            Log::error('LettaBridgeService: agent create transport failure', [
                'contact_id' => $contact->id,
                'exception' => $e::class,
            ]);
        }
        return null;
    }

    /**
     * Send a user message to the Letta agent and get back the assistant's
     * reply. Letta handles memory updates internally via its tool-calling
     * mechanism — we just see the final assistant_message.
     *
     * Returns null on failure so callers can fall back to direct LLM.
     */
    public function send(Contact $contact, string $message): ?string
    {
        $agentId = $this->ensureAgentFor($contact);
        if (!$agentId) {
            return null;
        }

        try {
            $response = Http::timeout(45)
                ->withToken(config('services.letta.token'))
                ->post(config('services.letta.url') . "/v1/agents/{$agentId}/messages", [
                    'messages' => [[
                        'role' => 'user',
                        'text' => $message,
                    ]],
                ]);

            if (!$response->successful()) {
                Log::warning('LettaBridgeService: send failed', [
                    'agent_id' => $agentId,
                    'status' => $response->status(),
                ]);
                return null;
            }

            // Letta returns a stream of message types — find the
            // assistant_message and return its text.
            foreach ($response->json('messages', []) as $msg) {
                if (($msg['message_type'] ?? '') === 'assistant_message') {
                    return $msg['content'] ?? null;
                }
            }
        } catch (\Throwable $e) {
            Log::error('LettaBridgeService: send transport failure', [
                'agent_id' => $agentId,
                'exception' => $e::class,
            ]);
        }
        return null;
    }

    private function humanMemoryFor(Contact $contact): string
    {
        $bits = [];
        if ($contact->name) $bits[] = "Numele: {$contact->name}";
        if ($contact->phone) $bits[] = "Telefon: {$contact->phone}";
        if ($contact->email) $bits[] = "Email: {$contact->email}";
        return implode("\n", $bits) ?: 'Nu cunosc încă utilizatorul.';
    }
}
