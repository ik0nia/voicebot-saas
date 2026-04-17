<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Services\BotAiGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

/**
 * Universal "✨ generate" endpoint for bot setup.
 *
 * Scopes:
 *   - Sanctum auth with `bots:write` ability (route-level)
 *   - tenant enforcement: caller's user.tenant_id must equal bot.tenant_id
 *     (Laravel's route-model binding hits the global BelongsToTenant
 *     scope for the normal case; the explicit check covers super_admin
 *     tokens that bypass the scope).
 *
 * Rate limits (per tenant):
 *   - 60 req/min for all targets combined
 *   - 10 req/min for `full_profile` (much more expensive single call)
 *
 * 429 response carries `retry_after` seconds so the UI can disable the
 * button with a countdown instead of silently failing.
 */
class BotAiGenerateController extends Controller
{
    private const TARGETS = [
        'faq_question',
        'faq_answer',
        'faq_pair',
        'faq_bulk',
        'rules_suggest',
        'tone_suggest',
        'extras_suggest',
        'full_profile',
        'rephrase',
    ];

    public function __invoke(Request $request, Bot $bot, BotAiGenerationService $service): JsonResponse
    {
        $user = $request->user();

        // Explicit tenant check — defense in depth against tokens that
        // slipped the global scope (super_admin, or legacy `*`-ability
        // tokens minted before iter 11's scope tightening).
        if (!$user || $bot->tenant_id !== $user->tenant_id) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $validated = $request->validate([
            'target'  => ['required', 'string', Rule::in(self::TARGETS)],
            'hint'    => ['nullable', 'string', 'max:500'],
            'context' => ['nullable', 'array'],
            'count'   => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $target = $validated['target'];
        $tenantId = (int) $user->tenant_id;

        // Per-tenant composite limit. We hit the full-profile bucket
        // FIRST (so it counts toward the 10/min cap) then the global
        // 60/min bucket. Both failures produce the same 429 shape.
        if ($target === 'full_profile') {
            $fpKey = "bot-ai-generate:full_profile:tenant:{$tenantId}";
            if (RateLimiter::tooManyAttempts($fpKey, 10)) {
                return $this->rateLimited($fpKey);
            }
            RateLimiter::hit($fpKey, 60);
        }

        $globalKey = "bot-ai-generate:tenant:{$tenantId}";
        if (RateLimiter::tooManyAttempts($globalKey, 60)) {
            return $this->rateLimited($globalKey);
        }
        RateLimiter::hit($globalKey, 60);

        try {
            $result = $service->generate(
                bot: $bot,
                target: $target,
                hint: $validated['hint'] ?? null,
                context: $validated['context'] ?? [],
                count: (int) ($validated['count'] ?? 1),
                userId: (int) $user->id,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\App\Exceptions\FullProfileDailyCapException $e) {
            return response()->json([
                'error' => 'Daily profile generation limit reached for this bot',
                'cap' => $e->cap,
                'used_today' => $e->usedToday,
            ], 429);
        } catch (\Throwable $e) {
            Log::error('BotAiGenerateController: generation failed', [
                'bot_id' => $bot->id,
                'tenant_id' => $bot->tenant_id,
                'target' => $target,
                'error' => $e->getMessage(),
            ]);
            if (app()->bound('sentry')) {
                try { app('sentry')->captureException($e); } catch (\Throwable $ignored) {}
            }
            return response()->json(['error' => 'generation_failed'], 502);
        }

        return response()->json([
            'generated' => $result['generated'],
            'cost_ron'  => $result['cost_ron'],
            'tokens'    => [
                'in'  => $result['tokens_in'],
                'out' => $result['tokens_out'],
            ],
        ]);
    }

    private function rateLimited(string $key): JsonResponse
    {
        $retry = RateLimiter::availableIn($key);
        return response()->json([
            'error' => 'rate_limited',
            'retry_after' => $retry,
        ], 429)->header('Retry-After', (string) $retry);
    }
}
