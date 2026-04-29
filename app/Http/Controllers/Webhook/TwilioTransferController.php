<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\TransferAttempt;
use App\Services\Transfer\TransferService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Public Twilio webhooks for the warm-transfer flow.
 *
 * All routes are signature-verified by the `twilio.verify` middleware
 * applied in routes/web.php, and return TwiML XML bodies Twilio
 * interprets on the operator leg (whisper/join/noAnswer) or status
 * callbacks on the outbound leg lifecycle.
 *
 * The `{callSid}` path parameter is the INBOUND caller's CallSid (not
 * the operator's) — it's the stable key that lets us name the
 * conference and look up the summary across legs.
 */
class TwilioTransferController extends Controller
{
    public function __construct(private TransferService $service) {}

    /**
     * Operator leg hits this when Twilio places the call (status
     * `in-progress`). We play the cached summary via TTS, then ask for
     * DTMF 1 to accept. No input within 6s → redirect to noAnswer.
     */
    public function whisper(Request $request, string $callSid): Response
    {
        $summary = (string) (Cache::get($this->service->summaryCacheKey($callSid))
            ?? 'Transfer de la agentul virtual. Clientul așteaptă.');

        // Guard against Twilio's answering-machine detection: if the
        // outbound leg was answered by a machine, we don't want to
        // play the whisper to voicemail — the conference bridging
        // step would then connect the caller to nothing. AMD result
        // arrives as AnsweredBy=machine_end_* or AnsweredBy=fax.
        $answeredBy = (string) $request->input('AnsweredBy', '');
        if (str_starts_with($answeredBy, 'machine_') || $answeredBy === 'fax') {
            $this->markAttempt($callSid, TransferAttempt::STATUS_FAILED, 'operator_voicemail');
            return $this->twimlResponse(
                '<Response><Hangup/></Response>'
            );
        }

        $this->markAttempt($callSid, TransferAttempt::STATUS_OPERATOR_ANSWERED, operatorAnsweredAt: true);

        $joinUrl     = route('webhook.twilio.transfer.join',     ['callSid' => $callSid], false);
        $noAnswerUrl = route('webhook.twilio.transfer.noAnswer', ['callSid' => $callSid], false);

        $twiml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Response>'
            . '<Say voice="Polly.Carmen-Neural" language="ro-RO">'
            .   htmlspecialchars($summary, ENT_XML1)
            . '</Say>'
            . '<Gather numDigits="1" timeout="6" action="' . htmlspecialchars($joinUrl, ENT_XML1) . '" method="POST">'
            .   '<Say voice="Polly.Carmen-Neural" language="ro-RO">'
            .     'Apăsați 1 pentru a prelua apelul. Orice altă tastă refuză transferul.'
            .   '</Say>'
            . '</Gather>'
            . '<Redirect method="POST">' . htmlspecialchars($noAnswerUrl, ENT_XML1) . '</Redirect>'
            . '</Response>';

        return $this->twimlResponse($twiml);
    }

    /**
     * Operator pressed a DTMF digit. Only "1" joins the conference;
     * anything else counts as a rejection and hangs up the operator
     * leg (the caller fallback is triggered separately via the status
     * callback transitioning to `completed`).
     */
    public function join(Request $request, string $callSid): Response
    {
        $digits = (string) $request->input('Digits', '');

        if ($digits !== '1') {
            $this->markAttempt($callSid, TransferAttempt::STATUS_REJECTED, failureReason: 'operator_declined');
            return $this->twimlResponse(
                '<?xml version="1.0" encoding="UTF-8"?>'
                . '<Response>'
                . '<Say voice="Polly.Carmen-Neural" language="ro-RO">Am înțeles. La revedere.</Say>'
                . '<Hangup/>'
                . '</Response>'
            );
        }

        $conference = $this->service->conferenceName($callSid);
        $this->markAttempt($callSid, TransferAttempt::STATUS_BRIDGED, bridgedAt: true);

        $twiml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Response>'
            . '<Dial>'
            .   '<Conference '
            .     'startConferenceOnEnter="true" '
            .     'endConferenceOnExit="true" '
            .     'beep="false">'
            .     htmlspecialchars($conference, ENT_XML1)
            .   '</Conference>'
            . '</Dial>'
            . '</Response>';

        return $this->twimlResponse($twiml);
    }

    /**
     * Operator didn't pick up, or DTMF timed out past the whisper
     * prompt. Close the operator leg; the parent caller still sits in
     * the hold conference until the `status` callback fires and we
     * reconnect them to an apology + hangup.
     */
    public function noAnswer(Request $request, string $callSid): Response
    {
        $this->markAttempt($callSid, TransferAttempt::STATUS_NO_ANSWER, failureReason: 'operator_no_answer');

        return $this->twimlResponse(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Response><Hangup/></Response>'
        );
    }

    /**
     * Twilio posts status transitions on the OUTBOUND operator leg
     * here (initiated → ringing → answered → completed). When the leg
     * ends before the operator was bridged, we rescue the still-held
     * caller by swapping their TwiML to an apology + hangup —
     * otherwise they'd loop hold music forever.
     */
    public function status(Request $request, string $callSid): Response
    {
        $status     = (string) $request->input('CallStatus', '');
        $sipStatus  = (string) $request->input('SipResponseCode', '');
        $answeredBy = (string) $request->input('AnsweredBy', '');

        $attempt = TransferAttempt::withoutGlobalScopes()
            ->where('inbound_call_sid', $callSid)
            ->latest('id')
            ->first();
        if (!$attempt) {
            return $this->twimlResponse('<Response/>');
        }

        $terminal = in_array($status, ['completed', 'busy', 'no-answer', 'failed', 'canceled'], true);

        if ($terminal && $attempt->status !== TransferAttempt::STATUS_BRIDGED) {
            // Operator leg ended without a successful bridge — common
            // paths: no_answer, busy, or the operator hit a non-1 key
            // and was hung up in `join`. Rescue the caller who is
            // still in the hold conference.
            $this->rescueCallerFromHold($attempt);
            $attempt->update([
                'status'         => $attempt->status === TransferAttempt::STATUS_RINGING
                    ? TransferAttempt::STATUS_NO_ANSWER
                    : $attempt->status,
                'failure_reason' => $attempt->failure_reason
                    ?? ($answeredBy !== '' ? "amd:{$answeredBy}" : "call:{$status}:{$sipStatus}"),
                'ended_at'       => now(),
            ]);
        }

        if ($terminal && $attempt->status === TransferAttempt::STATUS_BRIDGED) {
            $bridgedFor = $attempt->bridged_at ? now()->diffInSeconds($attempt->bridged_at) : null;
            $attempt->update([
                'status'          => TransferAttempt::STATUS_COMPLETED,
                'ended_at'        => now(),
                'bridged_seconds' => $bridgedFor,
            ]);
        }

        return $this->twimlResponse('<Response/>');
    }

    /**
     * Move the caller from hold music back to an apology + hangup when
     * the operator leg fails. Twilio's TwiML update runs immediately,
     * replacing the active <Conference>.
     */
    private function rescueCallerFromHold(TransferAttempt $attempt): void
    {
        $twiml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Response>'
            . '<Say voice="Polly.Carmen-Neural" language="ro-RO">'
            .   'Îmi pare rău, colegul nostru nu este disponibil în acest moment. '
            .   'Vă rugăm să încercați mai târziu sau să folosiți formularul de contact de pe site. '
            .   'O zi bună.'
            . '</Say>'
            . '<Hangup/>'
            . '</Response>';

        try {
            app(\App\Services\TwilioService::class)->updateCallTwiml($attempt->inbound_call_sid, $twiml);
        } catch (\Throwable $e) {
            Log::warning('TwilioTransferController: rescue TwiML update failed', [
                'inbound_call_sid' => $attempt->inbound_call_sid,
                'err' => $e->getMessage(),
            ]);
        }
    }

    private function markAttempt(
        string $inboundCallSid,
        string $status,
        bool $operatorAnsweredAt = false,
        bool $bridgedAt = false,
        ?string $failureReason = null,
    ): void {
        $attempt = TransferAttempt::withoutGlobalScopes()
            ->where('inbound_call_sid', $inboundCallSid)
            ->latest('id')
            ->first();
        if (!$attempt) return;

        $data = ['status' => $status];
        if ($operatorAnsweredAt) $data['operator_answered_at'] = now();
        if ($bridgedAt)          $data['bridged_at'] = now();
        if ($failureReason)      $data['failure_reason'] = $failureReason;

        $attempt->update($data);
    }

    private function twimlResponse(string $xml): Response
    {
        return response($xml, 200, ['Content-Type' => 'text/xml']);
    }
}
