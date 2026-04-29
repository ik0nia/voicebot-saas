<?php

namespace App\Services\Transfer;

use App\Models\Bot;
use App\Models\Call;
use App\Models\TransferAttempt;
use App\Services\TwilioService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * Orchestrates warm transfer of a live AI-agent call to a human
 * operator on a separate phone leg.
 *
 * Lifecycle (happy path):
 *   1. initiate()      — Realtime tool-call arrives via the Node bridge.
 *                        We generate a summary, cache it, create a
 *                        TransferAttempt row, and place the outbound
 *                        leg to the operator's number.
 *   2. bridgeCaller()  — Node bridge calls back after the agent has
 *                        finished speaking the "one moment please"
 *                        confirmation. We swap the inbound call's
 *                        TwiML to a <Conference>, which closes the AI
 *                        media stream cleanly and puts the caller into
 *                        hold music while the operator leg rings.
 *   3. Twilio hits the public whisper webhook when the operator
 *      answers → the summary is played via Polly → on DTMF 1 the
 *      operator joins the same conference → bridged.
 *
 * Hold-music URL: Twilio's S3-hosted royalty-free music. Replace via
 * PlatformSetting `transfer_hold_music_url` without code change.
 */
class TransferService
{
    public const SUMMARY_CACHE_TTL = 600;              // 10 min — covers worst-case ring + whisper
    public const DEFAULT_RING_SECONDS = 25;
    public const DEFAULT_HOLD_MUSIC_URL = 'http://com.twilio.sounds.music.s3.amazonaws.com/MARKOVICHAMP-Borghestral.mp3';

    public function __construct(
        private TransferSummaryService $summaries,
        private TwilioService $twilio,
    ) {}

    /**
     * Kick off the outbound leg to the operator. Called from the Node
     * bridge's tool-call handler. Returns the text the AI agent should
     * speak to the caller as confirmation — the agent speaks this, and
     * only after it finishes does the Node bridge signal us to move
     * the caller into hold via bridgeCaller().
     *
     * Returns null on misconfiguration — the Node bridge reads that
     * and tells the AI to apologise instead of making a fake promise.
     *
     * @return array{speak: string, attempt_id: int}|null
     */
    public function initiate(Call $call, Bot $bot, ?string $reason): ?array
    {
        $config = $bot->settings['transfer_config'] ?? null;
        if (!is_array($config) || empty($config['enabled']) || empty($config['operator_number'])) {
            Log::info('TransferService: initiate aborted, feature disabled on bot', ['bot_id' => $bot->id]);
            return null;
        }

        $operator = $this->normalizePhone((string) $config['operator_number']);
        if (!$operator) {
            Log::warning('TransferService: invalid operator number', [
                'bot_id' => $bot->id,
                'raw' => $config['operator_number'],
            ]);
            return null;
        }

        $inboundSid = (string) ($call->metadata['provider_call_id'] ?? '');
        if ($inboundSid === '') {
            Log::warning('TransferService: missing inbound call sid in metadata', ['call_id' => $call->id]);
            return null;
        }

        // Outbound-leg From must be a Twilio-owned number on the master
        // account. We use the bot number the caller dialled in on —
        // that's guaranteed provisioned and keeps the operator caller-ID
        // recognisable (same as the business main line).
        $phoneNumber = $call->phone_number_id
            ? \App\Models\PhoneNumber::withoutGlobalScopes()->find($call->phone_number_id)
            : null;
        $fromNumber = $phoneNumber?->number;
        if (!$fromNumber) {
            Log::warning('TransferService: cannot resolve outbound-from number for call', ['call_id' => $call->id]);
            return null;
        }

        $summary = $this->summaries->buildForCall($call->id, $reason);
        Cache::put($this->summaryCacheKey($inboundSid), $summary, self::SUMMARY_CACHE_TTL);

        $attempt = TransferAttempt::create([
            'tenant_id'        => $call->tenant_id,
            'bot_id'           => $bot->id,
            'call_id'          => $call->id,
            'inbound_call_sid' => $inboundSid,
            'operator_number'  => $operator,
            'requested_reason' => $reason,
            'summary'          => $summary,
            'status'           => TransferAttempt::STATUS_INITIATING,
            'initiated_at'     => now(),
        ]);

        $ringSeconds = (int) ($config['max_ring_seconds'] ?? self::DEFAULT_RING_SECONDS);
        $ringSeconds = max(10, min($ringSeconds, 60));

        $whisperUrl = URL::to(route('webhook.twilio.transfer.whisper', ['callSid' => $inboundSid], false));
        $statusUrl  = URL::to(route('webhook.twilio.transfer.status',  ['callSid' => $inboundSid], false));

        $operatorSid = $this->twilio->createOutboundCall(
            $operator,
            $fromNumber,
            [
                'url'                     => $whisperUrl,
                'status_callback'         => $statusUrl,
                'status_callback_events'  => ['initiated', 'ringing', 'answered', 'completed'],
                'timeout'                 => $ringSeconds,
                'machine_detection'       => 'DetectMessageEnd',
            ],
        );

        if (!$operatorSid) {
            $attempt->update([
                'status' => TransferAttempt::STATUS_FAILED,
                'failure_reason' => 'outbound_create_failed',
                'ended_at' => now(),
            ]);
            return null;
        }

        $attempt->update([
            'operator_call_sid' => $operatorSid,
            'status' => TransferAttempt::STATUS_RINGING,
        ]);

        return [
            'speak' => 'Vă fac legătura cu un coleg. Rămâneți pe linie, vă rog.',
            'attempt_id' => $attempt->id,
        ];
    }

    /**
     * Move the caller from the AI media stream into a hold conference.
     * Called from the Node bridge once the AI agent has finished
     * speaking the "one moment" confirmation — delaying this swap until
     * after response.done avoids cutting the caller off mid-sentence.
     */
    public function bridgeCaller(TransferAttempt $attempt): bool
    {
        $conferenceName = $this->conferenceName($attempt->inbound_call_sid);
        $holdMusicUrl = (string) \App\Models\PlatformSetting::get(
            'transfer_hold_music_url',
            self::DEFAULT_HOLD_MUSIC_URL,
        );

        $twiml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Response>'
            . '<Dial timeLimit="1800">'
            . '<Conference '
            .   'startConferenceOnEnter="false" '
            .   'endConferenceOnExit="true" '
            .   'waitUrl="' . htmlspecialchars($holdMusicUrl, ENT_XML1) . '" '
            .   'waitMethod="GET" '
            .   'beep="false">'
            .   htmlspecialchars($conferenceName, ENT_XML1)
            . '</Conference>'
            . '</Dial>'
            . '</Response>';

        $ok = $this->twilio->updateCallTwiml($attempt->inbound_call_sid, $twiml);
        if (!$ok) {
            $attempt->update([
                'status' => TransferAttempt::STATUS_FAILED,
                'failure_reason' => 'twiml_update_failed',
                'ended_at' => now(),
            ]);
        }
        return $ok;
    }

    public function summaryCacheKey(string $inboundCallSid): string
    {
        return "transfer:summary:{$inboundCallSid}";
    }

    public function conferenceName(string $inboundCallSid): string
    {
        return 'xfer-' . $inboundCallSid;
    }

    /**
     * Normalize a human-entered operator number to E.164. Accepts:
     *   - "+40741234567" (pass-through)
     *   - "0741234567"   (strip leading 0, prefix +40 — Romanian default)
     *   - "40741234567"  (prefix +)
     * Returns null on anything non-numeric that can't be coerced.
     */
    private function normalizePhone(string $raw): ?string
    {
        $raw = trim($raw);
        if (preg_match('/^\+[1-9]\d{7,14}$/', $raw)) {
            return $raw;
        }
        $digits = preg_replace('/\D/', '', $raw);
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            $digits = '40' . substr($digits, 1);
        }
        $candidate = '+' . $digits;
        return preg_match('/^\+[1-9]\d{7,14}$/', $candidate) ? $candidate : null;
    }
}
