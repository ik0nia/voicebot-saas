<?php

namespace App\Services\Transfer;

use App\Models\Transcript;
use Illuminate\Support\Facades\Log;

/**
 * Generates a short operator hand-off summary from the call transcripts.
 *
 * The summary is played to the human operator via TTS before they are
 * bridged into the conference, so strict constraints apply: 2-3
 * sentences, under 40 words, Romanian, speakable. A runaway model
 * producing a paragraph makes operators sit through 30+ seconds of TTS
 * and blows the warm-transfer UX — better to return a dumb one-liner
 * from the fallback than a clever but long summary.
 */
class TransferSummaryService
{
    public const MODEL   = 'claude-haiku-4-5-20251001';
    public const MAX_TRANSCRIPT_CHARS = 6000;
    public const MAX_SUMMARY_CHARS    = 240;

    public function buildForCall(int $callId, ?string $reason = null): string
    {
        $lines = Transcript::where('call_id', $callId)
            ->orderBy('timestamp_ms')
            ->limit(30)
            ->get(['role', 'content'])
            ->map(fn ($t) => strtoupper($t->role) . ': ' . $t->content)
            ->implode("\n");

        if ($lines === '') {
            return $this->fallback($reason);
        }

        if (mb_strlen($lines) > self::MAX_TRANSCRIPT_CHARS) {
            $lines = mb_substr($lines, -self::MAX_TRANSCRIPT_CHARS);
        }

        try {
            $client = app()->make(\Anthropic\Client::class);
        } catch (\Throwable $e) {
            Log::warning('TransferSummary: Anthropic client unavailable, using fallback', [
                'call_id' => $callId,
                'err' => $e->getMessage(),
            ]);
            return $this->fallback($reason);
        }

        $system = 'Generezi un rezumat FOARTE SCURT pentru un operator uman care preia un apel de la un agent AI. '
            . 'Constrângeri STRICTE: maxim 3 propoziții, maxim 40 de cuvinte, limbă română, ton neutru-profesional. '
            . 'Include (dacă au fost menționate): numele clientului, motivul apelului, ce așteaptă clientul. '
            . 'NU include salut, NU include „Bună ziua", NU include „apasă 1". '
            . 'Răspunzi DOAR cu textul rezumatului, fără comentarii sau formatare markdown.';

        $user = "REASON semnalat de agent: " . ($reason ?: '(nespecificat)')
            . "\n\nTRANSCRIPT (ultimele replici):\n" . $lines
            . "\n\nGenerează rezumatul.";

        try {
            $message = $client->messages->create(
                maxTokens: 180,
                messages: [['role' => 'user', 'content' => $user]],
                model: self::MODEL,
                system: $system,
                temperature: 0.3,
            );

            $text = $this->extractText($message);
            $text = trim(preg_replace('/\s+/u', ' ', $text));

            if ($text === '') {
                return $this->fallback($reason);
            }

            if (mb_strlen($text) > self::MAX_SUMMARY_CHARS) {
                $text = mb_substr($text, 0, self::MAX_SUMMARY_CHARS) . '…';
            }

            return $text;
        } catch (\Throwable $e) {
            Log::warning('TransferSummary: generation failed, using fallback', [
                'call_id' => $callId,
                'err' => $e->getMessage(),
            ]);
            return $this->fallback($reason);
        }
    }

    private function extractText(object $message): string
    {
        $out = '';
        foreach ($message->content ?? [] as $block) {
            if (is_object($block) && property_exists($block, 'text')) {
                $out .= (string) $block->text;
            } elseif (is_array($block) && isset($block['text'])) {
                $out .= (string) $block['text'];
            }
        }
        return trim($out);
    }

    private function fallback(?string $reason): string
    {
        $base = 'Transfer de la agentul virtual. Clientul cere să vorbească cu un operator.';
        if ($reason) {
            $base .= ' Motiv: ' . mb_substr(trim($reason), 0, 120) . '.';
        }
        return $base;
    }
}
