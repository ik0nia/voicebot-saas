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

        // Credit consumption — rough per-call accounting. Refined
        // per-minute math lands once we can reconcile against OpenAI's
        // live invoice pricing. For now: audio seconds ≈
        // (input_audio_tokens + output_audio_tokens) / 100, because
        // OpenAI Realtime encodes ~100 audio tokens per second at
        // 24kHz. Emits a CreditConsumption row keyed on the call id.
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
