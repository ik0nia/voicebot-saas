<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\CreditConsumption;
use App\Models\Transcript;
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'events' => 'required|array|min:1|max:200',
            'events.*.type' => 'required|string|in:transcript,usage',
            'events.*.call_id' => 'required|integer',
        ]);

        $accepted = 0;
        $skipped = 0;

        foreach ($validated['events'] as $event) {
            $call = Call::find($event['call_id']);
            if (!$call) {
                $skipped++;
                continue;
            }

            try {
                match ($event['type']) {
                    'transcript' => $this->recordTranscript($call, $request, $event),
                    'usage' => $this->recordUsage($call, $request, $event),
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
        $audioInputTokens = (int) ($usage['input_token_details']['audio_tokens'] ?? 0);
        $audioOutputTokens = (int) ($usage['output_token_details']['audio_tokens'] ?? 0);
        $estimatedSeconds = (int) round(($audioInputTokens + $audioOutputTokens) / 100);

        if ($estimatedSeconds <= 0) {
            return;
        }

        CreditConsumption::create([
            'tenant_id' => $call->tenant_id,
            'unit' => 'call_seconds',
            'quantity' => $estimatedSeconds,
            'source' => 'media-stream',
            'reference_id' => (string) $call->id,
        ]);

        // Compute cents for this response.done batch and accumulate
        // onto calls.cost_cents. Multiple response.done events per call
        // are normal (multi-turn conversation) — each adds its own
        // delta so the final cost is the sum.
        $centsPerSecond = $this->centsPerSecondFor($call);
        $deltaCents = (int) round($estimatedSeconds * $centsPerSecond);
        if ($deltaCents > 0) {
            DB::transaction(function () use ($call, $deltaCents) {
                $call->refresh();
                $call->update([
                    'cost_cents' => ((int) $call->cost_cents) + $deltaCents,
                ]);
            });
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
}
