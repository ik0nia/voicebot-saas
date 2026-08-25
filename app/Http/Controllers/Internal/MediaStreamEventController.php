<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\CreditConsumption;
use App\Models\Transcript;
use App\Services\WhisperHallucinationFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Accepts events from the media-stream Node service:
 *
 *   - transcript  — user ASR chunks + assistant spoken text deltas
 *   - usage       — OpenAI Realtime token counts on response.done
 *
 * Auth: Bearer token (VerifyInternalServiceToken middleware). Tenant
 * scoping is derived from the call_id — the service has no tenant
 * context of its own; we re-resolve here so a buggy / compromised
 * bridge can't write across tenants.
 *
 * Batching: the bridge collects ~20 events before posting, so a single
 * POST carries an array. Malformed items in the batch are skipped with
 * a warning rather than failing the whole batch — observability > data
 * integrity for these events (the DB is recoverable; missed alerts are
 * not).
 */
class MediaStreamEventController extends Controller
{
    /**
     * Return the OpenAI session.update payload for a specific call.
     * The bridge calls this on stream `start` so the session config
     * (instructions, knowledge-base context, tool list, voice, VAD
     * settings) comes from the same builder as the web demo — no
     * drift between voice and phone calls.
     */
    public function sessionConfig(Request $request)
    {
        $validated = $request->validate([
            'call_id' => 'required|integer',
        ]);

        $call = \App\Models\Call::withoutGlobalScopes()->find($validated['call_id']);
        if (!$call || !$call->bot) {
            return response()->json(['error' => 'call not found'], 404);
        }

        // Reuse RealtimeSession's exact session config so telephone
        // and browser calls behave identically. Null ttsStrategy →
        // RealtimeSession picks the default (OpenAI integrated voice),
        // matching the web demo's standard path.
        $session = new \App\Services\RealtimeSession($call->bot, $call, null);
        $payload = $session->getSessionConfig();

        // bots.settings is jsonb and numeric values (temperature,
        // max_tokens) can come back as strings depending on the pg
        // driver. OpenAI Realtime's validator rejects the entire
        // session.update if any type is wrong. Coerce every numeric
        // field defensively before we ship it out.
        if (isset($payload['session'])) {
            $s = &$payload['session'];
            // GA API: nested audio.input/output structure (post 2026-05-12).
            // Coerce numeric values to correct types so OpenAI's validator
            // doesn't reject the session.update on Postgres jsonb string casts.
            if (isset($s['audio']['input']['turn_detection']['threshold'])) {
                $s['audio']['input']['turn_detection']['threshold'] = (float) $s['audio']['input']['turn_detection']['threshold'];
            }
            if (isset($s['audio']['input']['turn_detection']['prefix_padding_ms'])) {
                $s['audio']['input']['turn_detection']['prefix_padding_ms'] = (int) $s['audio']['input']['turn_detection']['prefix_padding_ms'];
            }
            if (isset($s['audio']['input']['turn_detection']['silence_duration_ms'])) {
                $s['audio']['input']['turn_detection']['silence_duration_ms'] = (int) $s['audio']['input']['turn_detection']['silence_duration_ms'];
            }

            // Force the ASR model + language for the phone path (GA format).
            // Per-bot voice_language overrides the primary language — bots
            // serving mixed chat+phone can have chat EN but calls locked RO.
            $voiceLang = $call->bot->settings['voice_language']
                ?? $call->bot->language
                ?? 'ro';
            $langLabelMap = ['ro' => 'română', 'en' => 'engleză', 'de' => 'germană', 'fr' => 'franceză', 'es' => 'spaniolă'];
            $langLabel = $langLabelMap[$voiceLang] ?? 'română';
            $s['audio'] = $s['audio'] ?? ['input' => [], 'output' => []];
            $s['audio']['input'] = $s['audio']['input'] ?? [];
            /*
             * `gpt-4o-transcribe`, not the -mini variant. Measured 2026-08-25
             * on synthetic Romanian pushed through 8 kHz μ-law to match what
             * Twilio delivers: on "Aș mai vrea încă doi burgeri de pui și trei
             * cola", mini returned "Ach my vra un ke daou burger depui ha tri
             * cola" while the full model returned the sentence verbatim. Real
             * calls showed the same signature ("Să porgărotors" for a burger).
             *
             * This is the logging channel, not the model's input — the
             * Realtime model consumes the audio itself and is unaffected. It
             * still matters: the transcript is what an operator reads when a
             * customer disputes an order, and what every downstream summary
             * and lead score is computed from.
             */
            $s['audio']['input']['transcription'] = [
                'model' => 'gpt-4o-transcribe',
                'language' => $voiceLang,
                'prompt' => "Conversație telefonică în limba {$langLabel} despre produse și servicii. Nume proprii de produse pot apărea.",
            ];
            unset($s);
        }

        return response()->json([
            'session_update' => $payload,
            'greeting' => $call->bot->greeting_message,
            'language' => $call->bot->language ?: 'ro',
        ]);
    }

    /**
     * Handle a function-tool invocation from the OpenAI Realtime session.
     * The Node bridge forwards `response.function_call_arguments.done`
     * events here so the business logic stays in PHP (tenant scoping,
     * Twilio credentials, DB writes) rather than duplicated in Node.
     *
     * `request_human_transfer` keeps a bespoke path (it orchestrates Twilio
     * and must speak a fixed line first). Everything else is dispatched
     * through ToolRegistry — the same registry the chat channel uses — so a
     * tool written once serves both channels. Before this, voice was limited
     * to the transfer tool alone, which meant a restaurant bot could book a
     * table from the web widget but not over the phone.
     *
     * Tools outside the bot's own manifest are refused out loud rather than
     * silently accepted; see dispatchEngineTool.
     */
    public function toolCall(Request $request)
    {
        $validated = $request->validate([
            'call_id'        => 'required|integer',
            'tool_name'      => 'required|string|max:64',
            'arguments'      => 'nullable|array',
        ]);

        $call = \App\Models\Call::withoutGlobalScopes()->find($validated['call_id']);
        if (!$call || !$call->bot) {
            return response()->json(['error' => 'call not found'], 404);
        }

        // Everything except the transfer tool is dispatched through the same
        // ToolRegistry the chat channel uses, so a tool written once works on
        // both. Transfer keeps its own path because it is not a data lookup —
        // it orchestrates Twilio and must speak a fixed line before the
        // caller is moved.
        if ($validated['tool_name'] !== 'request_human_transfer') {
            return $this->dispatchEngineTool($call, $validated['tool_name'], $validated['arguments'] ?? []);
        }

        $reason = (string) ($validated['arguments']['reason'] ?? '');
        $result = app(\App\Services\Transfer\TransferService::class)
            ->initiate($call, $call->bot, $reason !== '' ? $reason : null);

        if (!$result) {
            return response()->json([
                'success' => false,
                'speak'   => 'Îmi pare rău, nu pot iniția transferul în acest moment. Vă pot ajuta eu cu altceva?',
            ]);
        }

        return response()->json([
            'success'    => true,
            'speak'      => $result['speak'],
            'attempt_id' => $result['attempt_id'],
        ]);
    }

    /**
     * Execute a non-transfer tool for a voice call.
     *
     * The tool name is checked against the bot's own engine manifest rather
     * than against the registry as a whole. The registry is global — it holds
     * every tool any bot might use — so dispatching straight into it would
     * let a hallucinated name reach a capability this bot was never granted.
     * The manifest is the same list the model was offered, so anything else
     * is by definition not something we advertised.
     *
     * Returns `data` (not `speak`): these are lookups and writes whose result
     * the model should phrase itself. Forcing a verbatim line — the way the
     * transfer path does — would have the agent read raw JSON aloud.
     *
     * @param  array<string, mixed>  $arguments
     */
    private function dispatchEngineTool(\App\Models\Call $call, string $toolName, array $arguments)
    {
        $bot = $call->bot;

        try {
            $manifest = $bot->engine()->chatTools($bot);
        } catch (\Throwable $e) {
            Log::error('MediaStream tool-call: engine manifest failed', [
                'bot_id' => $bot->id,
                'tool'   => $toolName,
                'error'  => $e->getMessage(),
            ]);
            return response()->json(['error' => 'engine_unavailable'], 500);
        }

        $allowed = array_column(is_array($manifest) ? $manifest : [], 'name');
        if (!in_array($toolName, $allowed, true)) {
            // Logged loudly: on a live call this means the model invented a
            // capability, which is a prompt or manifest problem worth seeing.
            Log::warning('MediaStream tool-call: tool not in bot manifest', [
                'bot_id'  => $bot->id,
                'call_id' => $call->id,
                'tool'    => $toolName,
                'allowed' => $allowed,
            ]);

            // Answered as a spoken refusal rather than an HTTP error. The
            // caller is mid-conversation: an error status makes the bridge
            // fall back to generic dead-air handling, whereas feeding the
            // refusal back into the session lets the agent say it plainly and
            // offer something it can actually do. This is still not silent
            // success — the model is told "no", explicitly.
            return response()->json([
                'success' => false,
                'speak'   => 'Asta nu pot face din apel. Vă pot ajuta cu altceva?',
            ]);
        }

        /*
         * Bind who is calling before dispatching. Handlers that span turns —
         * the food basket above all — key their state on this, and the
         * caller's number comes from telephony rather than from the
         * transcript, so it is worth far more than a number the model heard
         * and repeated back.
         *
         * No conversation id: `calls` has no link to `conversations` — a
         * phone call's transcript lives on the call itself. The session key
         * is the call, which is what continuity needs.
         */
        app(\App\Services\ToolContext::class)->forVoice($call->id, $call->caller_number);

        $result = app(\App\Services\ToolRegistry::class)->execute($toolName, $bot->id, $arguments);

        // ToolRegistry reports handler failures as an `error` key rather than
        // throwing. Surface it as a spoken apology so the caller hears
        // something human instead of dead air while the model waits.
        if (isset($result['error'])) {
            Log::warning('MediaStream tool-call: tool returned error', [
                'bot_id' => $bot->id,
                'tool'   => $toolName,
                'error'  => $result['error'],
            ]);
            return response()->json([
                'success' => false,
                'speak'   => 'Îmi pare rău, nu am putut verifica asta acum. Puteți repeta sau vă pot ajuta cu altceva?',
            ]);
        }

        Log::info('MediaStream tool-call executed', [
            'bot_id'  => $bot->id,
            'call_id' => $call->id,
            'tool'    => $toolName,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }

    /**
     * After the AI agent has finished speaking the "one moment please"
     * confirmation, the Node bridge calls this endpoint to swap the
     * caller's TwiML into a conference. Doing the swap here (rather
     * than immediately on tool-call) avoids cutting the caller off
     * mid-sentence — Twilio interrupts whatever audio is playing the
     * instant we `Calls(sid).update()`.
     */
    public function transferBridge(Request $request)
    {
        $validated = $request->validate([
            'attempt_id' => 'required|integer',
        ]);

        $attempt = \App\Models\TransferAttempt::withoutGlobalScopes()->find($validated['attempt_id']);
        if (!$attempt) {
            return response()->json(['error' => 'attempt not found'], 404);
        }

        $ok = app(\App\Services\Transfer\TransferService::class)->bridgeCaller($attempt);
        return response()->json(['success' => $ok]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'events' => 'required|array|min:1|max:200',
            'events.*.type' => 'required|string|in:transcript,usage,dtmf',
            'events.*.call_id' => 'required|integer',
            // Declared (optional) so $request->validate returns them
            // in the validated array. Without these keys listed,
            // Laravel strips unrecognised fields and the loop below
            // sees only {type, call_id} — which fails the per-type
            // guard in recordTranscript / recordUsage.
            'events.*.role' => 'nullable|string',
            'events.*.content' => 'nullable|string',
            'events.*.timestamp_ms' => 'nullable|integer',
            'events.*.usage' => 'nullable|array',
            'events.*.digit' => 'nullable|string|max:8',
            'events.*.buffer' => 'nullable|string|max:32',
        ]);

        $accepted = 0;
        $skipped = 0;

        foreach ($validated['events'] as $event) {
            $call = Call::withoutGlobalScopes()->find($event['call_id']);
            if (!$call) {
                $skipped++;
                continue;
            }

            try {
                match ($event['type']) {
                    'transcript' => $this->recordTranscript($call, $request, $event),
                    'usage' => $this->recordUsage($call, $request, $event),
                    'dtmf' => $this->recordDtmf($call, $event),
                };
                $accepted++;
            } catch (\Throwable $e) {
                Log::warning('MediaStreamEvent: event write failed', [
                    'type' => $event['type'],
                    'call_id' => $call->id,
                    'err' => $e->getMessage(),
                ]);
                $skipped++;
            }
        }

        return response()->json(['accepted' => $accepted, 'skipped' => $skipped]);
    }

    private function recordTranscript(Call $call, Request $request, array $event): void
    {
        $role = $event['role'] ?? null;
        $content = $event['content'] ?? null;
        if (!in_array($role, ['user', 'assistant'], true) || !is_string($content) || $content === '') {
            throw new \InvalidArgumentException('transcript event: role and content required');
        }
        if (mb_strlen($content) > 20000) {
            $content = mb_substr($content, 0, 20000);
        }

        /*
         * Our own transcription hint, echoed back as if the caller had said
         * it — seen on call 139, opening with "Conversație telefonică în limba
         * română despre produse și servicii…". Only the user side is checked:
         * the assistant's text comes from the model, not from ASR.
         *
         * Dropped rather than stored, because everything downstream — the
         * operator's read of a disputed order, the summary, the lead score —
         * treats a transcript row as something the customer actually said.
         */
        if ($role === 'user' && WhisperHallucinationFilter::isPromptEcho($content)) {
            Log::info('MediaStreamEvent: dropped transcription-prompt echo', [
                'call_id' => $call->id,
                'content' => mb_substr($content, 0, 120),
            ]);

            return;
        }

        Transcript::create([
            'call_id' => $call->id,
            'role' => $role,
            'content' => $content,
            'timestamp_ms' => (int) ($event['timestamp_ms'] ?? 0),
        ]);
    }

    private function recordUsage(Call $call, Request $request, array $event): void
    {
        // Realtime usage shape from OpenAI:
        //   {
        //     total_tokens, input_tokens, output_tokens,
        //     input_token_details: { cached_tokens, text_tokens, audio_tokens },
        //     output_token_details: { text_tokens, audio_tokens }
        //   }
        $usage = $event['usage'] ?? [];
        if (!is_array($usage) || empty($usage)) {
            return;
        }

        // Stash the raw usage on the call so analytics can aggregate
        // without re-querying OpenAI. Merge with any existing usage
        // (multiple response.done events per call are normal).
        DB::transaction(function () use ($call, $usage) {
            $metadata = $call->metadata ?? [];
            $metadata['openai_usage'] = $this->mergeUsage($metadata['openai_usage'] ?? [], $usage);
            $call->update(['metadata' => $metadata]);
        });

        // Cost + consumption. Two outputs:
        //
        //   1. CreditConsumption (unit=call_seconds) — the granular
        //      event stream the billing layer consumes to compute
        //      period totals.
        //   2. calls.cost_cents — the legacy flat sum per call row,
        //      used by admin reports + per-call UI display.
        //
        // Cost formula: audio seconds ≈
        //   (input_audio_tokens + output_audio_tokens) / 100
        // (OpenAI Realtime encodes ~100 audio tokens per second at
        // 24kHz PCM16), then seconds × platform-configured cents-per-
        // second. ElevenLabs-voiced calls cost more than OpenAI
        // default voices; the per-voice rate lookup matches the pre-
        // Twilio flow so existing per-bot pricing overrides still
        // apply.
        // CallCostCalculator computes OpenAI cents from token counts
        // using the actual Realtime rates. Replaces the old naive
        // "audio_seconds × 1c" approximation.
        $calculator = app(\App\Services\Cost\CallCostCalculator::class);
        $openaiDelta = $calculator->openaiFromUsage($usage);

        $audioInputTokens = (int) ($usage['input_token_details']['audio_tokens'] ?? 0);
        $audioOutputTokens = (int) ($usage['output_token_details']['audio_tokens'] ?? 0);
        $estimatedSeconds = (int) round(($audioInputTokens + $audioOutputTokens) / 100);

        if ($estimatedSeconds > 0) {
            CreditConsumption::create([
                'tenant_id' => $call->tenant_id,
                'unit' => 'call_seconds',
                'quantity' => $estimatedSeconds,
                'source' => 'media-stream',
                'reference_id' => (string) $call->id,
            ]);
        }

        if ($openaiDelta > 0) {
            // openai_cost_cents accumulates per response.done; cost_cents
            // is the grand total (OpenAI + Twilio + embeddings). Twilio
            // lands on the status webhook; embeddings (tiny) are added
            // at knowledge-context fetch time.
            DB::transaction(function () use ($call, $openaiDelta) {
                $call->refresh();
                $call->update([
                    'openai_cost_cents' => (float) $call->openai_cost_cents + $openaiDelta,
                    'cost_cents'        => (float) $call->cost_cents + $openaiDelta,
                ]);
            });
        }

        // Lead extraction — runs on every response.done. The method
        // is idempotent (cache lock + duplicate check on tenant +
        // call_id), so firing per-turn just refines the existing
        // lead rather than creating dupes. Wrap in try/catch so an
        // extraction failure never tanks usage ingest.
        try {
            $session = new \App\Services\RealtimeSession($call->bot, $call, null);
            $session->tryExtractVoiceLead();
        } catch (\Throwable $e) {
            Log::warning('MediaStreamEvent: voice lead extraction failed', [
                'call_id' => $call->id,
                'err' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cost per audio-second in cents, driven by PlatformSetting with
     * a 1¢ default so an unconfigured platform still emits non-zero
     * costs (helpful for early telemetry). ElevenLabs voices have a
     * higher rate; we infer that from the bot's cloned_voice
     * attachment — bot-level setting beats platform default.
     */
    private function centsPerSecondFor(Call $call): float
    {
        $defaultCents = (float) \App\Models\PlatformSetting::get('voice_cost_per_second_cents', 1);

        if (!$call->bot_id) {
            return $defaultCents;
        }

        $bot = \App\Models\Bot::withoutGlobalScopes()->find($call->bot_id);
        if ($bot && $bot->cloned_voice_id) {
            return (float) \App\Models\PlatformSetting::get(
                'voice_cost_per_second_cents_elevenlabs',
                $defaultCents * 1.35,
            );
        }
        return $defaultCents;
    }

    /**
     * Additively merge two OpenAI usage payloads. Same shape on both
     * sides; missing keys treated as 0.
     */
    private function mergeUsage(array $a, array $b): array
    {
        $out = [];
        foreach (array_keys($a + $b) as $key) {
            $av = $a[$key] ?? null;
            $bv = $b[$key] ?? null;
            if (is_array($av) || is_array($bv)) {
                $out[$key] = $this->mergeUsage(is_array($av) ? $av : [], is_array($bv) ? $bv : []);
            } else {
                $out[$key] = (int) $av + (int) $bv;
            }
        }
        return $out;
    }

    /**
     * DTMF event de la twilioBridge — log în CallEvent + actualizează
     * `call.metadata.dtmf_buffer` cu ultimele cifre primite.
     */
    private function recordDtmf(Call $call, array $event): void
    {
        $digit = (string) ($event['digit'] ?? '');
        $buffer = (string) ($event['buffer'] ?? '');
        if ($digit === '') {
            return;
        }

        \App\Models\CallEvent::create([
            'call_id' => $call->id,
            'type' => 'dtmf',
            'metadata' => ['digit' => $digit, 'buffer' => $buffer],
            'occurred_at' => now(),
        ]);

        $meta = $call->metadata ?? [];
        $meta['dtmf_buffer'] = mb_substr($buffer, -16);
        $call->metadata = $meta;
        $call->save();
    }
}
