<?php

use App\Http\Controllers\Api\ChatbotApiController;
use App\Http\Controllers\Api\TestVocalController;
use App\Http\Controllers\Api\V1\AnalyticsApiController;
use App\Http\Controllers\Api\V1\BotApiController;
use App\Http\Controllers\Api\V1\CallApiController;
use Illuminate\Support\Facades\Route;

// Public health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()->toIso8601String()]);
});

// Internal service endpoints (Bearer INTERNAL_SERVICE_TOKEN).
// Used by services/media-stream to persist transcripts + OpenAI usage
// without bypassing validation/audit paths by writing straight to
// Postgres. Not exposed publicly — traffic should be loopback or VPC.
Route::middleware('internal.service')->prefix('internal')->group(function () {
    Route::post('/media-stream/events', [
        \App\Http\Controllers\Internal\MediaStreamEventController::class, 'store'
    ])->name('internal.mediaStream.events');
    Route::post('/media-stream/session-config', [
        \App\Http\Controllers\Internal\MediaStreamEventController::class, 'sessionConfig'
    ])->name('internal.mediaStream.sessionConfig');
});

// Platform logo upload endpoints. Super-admin only, rate-limited, and the
// URL variant runs through SsrfGuard before any network I/O. See
// LogoUploadController for the history of the vulnerabilities this replaces.
Route::middleware(['auth:sanctum,web', 'throttle:10,1'])->group(function () {
    Route::post('/upload-logo', [\App\Http\Controllers\Admin\LogoUploadController::class, 'uploadFile'])
        ->name('admin.logo.upload');
    Route::post('/upload-logo-url', [\App\Http\Controllers\Admin\LogoUploadController::class, 'uploadFromUrl'])
        ->name('admin.logo.uploadFromUrl');
});

// Test vocal endpoint (uses web session auth via CSRF, no Sanctum needed)
Route::post('/v1/bots/{bot}/test-vocal', [TestVocalController::class, 'handle']);

// OpenAI Realtime voice session (WebRTC) — rate-limited to prevent abuse
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/v1/bots/{bot}/realtime-session', [\App\Http\Controllers\Api\RealtimeSessionController::class, 'create']);
    Route::post('/v1/bots/{bot}/synthesize', [\App\Http\Controllers\Api\RealtimeSessionController::class, 'synthesize']);
    Route::post('/v1/bots/{bot}/search-products', [\App\Http\Controllers\Api\RealtimeSessionController::class, 'searchProducts']);
});
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/v1/calls/{call}/transcript', [\App\Http\Controllers\Api\RealtimeSessionController::class, 'saveTranscript']);
    Route::post('/v1/calls/{call}/end', [\App\Http\Controllers\Api\RealtimeSessionController::class, 'endCall']);
});

// Chatbot embed routes (public, no auth)
Route::get('/v1/chatbot/embed', [\App\Http\Controllers\Api\ChatbotEmbedController::class, 'embedScript'])->name('chatbot.embed');
Route::get('/v1/chatbot/check-domain', [\App\Http\Controllers\Api\ChatbotEmbedController::class, 'checkDomain'])->name('chatbot.check-domain');
Route::get('/v1/chatbot/{channel}/frame', [\App\Http\Controllers\Api\ChatbotEmbedController::class, 'frame'])->name('chatbot.frame');

// Public chatbot widget API (no auth required).
//
// Every message / stream call spawns an LLM request (OpenAI or Anthropic)
// on the tenant's quota. Before throttling, the bare routes let a remote
// caller burn a tenant's entire monthly LLM budget in seconds — just hit
// /message in a tight loop. Throttles below are per-IP:
//   - message / stream: 30/min (well above any legitimate widget user,
//     conversations average ~5 msgs/min)
//   - products: 30/min (embedding search is expensive too)
//   - config: 60/min (cheap but enumerable by channel_id)
//
// If legitimate multi-user traffic from a single NAT starts hitting
// these, revisit with per-(channel, visitor_id) limits instead.
Route::post('/v1/chatbot/{channel}/message', [ChatbotApiController::class, 'message'])->middleware('throttle:30,1');
Route::post('/v1/chatbot/{channel}/message-stream', [ChatbotApiController::class, 'messageStream'])->middleware('throttle:30,1');
Route::get('/v1/chatbot/{channel}/config', [ChatbotApiController::class, 'config'])->middleware('throttle:60,1');
Route::get('/v1/chatbot/{channel}/products', [ChatbotApiController::class, 'searchProducts'])->middleware('throttle:30,1');
Route::post('/v1/chatbot/{channel}/feedback', [ChatbotApiController::class, 'feedback'])->middleware('throttle:30,1');
Route::post('/v1/chatbot/{channel}/rate', [ChatbotApiController::class, 'rateConversation'])->middleware('throttle:10,1');

// V2 Analytics, Capabilities & Lead capture (public, widget-facing).
// Event batches accept up to 50 rows each; keep the batch endpoint tight
// so a bot can't flood chat_events. Lead / callback endpoints write to
// leads + send notifications (email/SMS cost) so cap those aggressively.
Route::post('/v1/chatbot/{channel}/events', [\App\Http\Controllers\Api\EventTrackingController::class, 'trackBatch'])->middleware('throttle:120,1');
Route::get('/v1/chatbot/{channel}/capabilities', [\App\Http\Controllers\Api\EventTrackingController::class, 'capabilities'])->middleware('throttle:60,1');
Route::post('/v1/chatbot/{channel}/lead', [\App\Http\Controllers\Api\EventTrackingController::class, 'captureLead'])->middleware('throttle:5,1');
Route::post('/v1/chatbot/{channel}/callback', [\App\Http\Controllers\Api\CallbackController::class, 'store'])->middleware('throttle:5,1');
Route::get('/v1/chatbot/{channel}/callback/services', [\App\Http\Controllers\Api\CallbackController::class, 'services'])->middleware('throttle:60,1');

// V2 Purchase webhook from WordPress companion plugin (signed, no auth)
Route::post('/v1/webhooks/woocommerce/{bot}/purchase', [\App\Http\Controllers\Api\PurchaseWebhookController::class, 'handle']);

// Plugin update check (public, no auth - called by WordPress updater)
Route::get('v1/plugin/update-check', [\App\Http\Controllers\Api\V1\PluginUpdateController::class, 'check']);

// API v1 — Sanctum-authed. Every route below carries an `abilities:`
// middleware so the token's stored scopes are actually enforced at
// request time. Without this the scope allowlist at mint time was
// theater — a token with `bots:read` could still POST to
// /integrations/sync-products. Legacy tokens that were minted with
// `*` continue to satisfy any abilities: check (Sanctum treats `*`
// as all abilities), so this is backward-compatible.
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Bots
    Route::get('bots', [BotApiController::class, 'index'])->middleware('abilities:bots:read');
    Route::get('bots/{bot}', [BotApiController::class, 'show'])->middleware('abilities:bots:read');
    Route::get('bots/{bot}/prompt-preview', [BotApiController::class, 'promptPreview'])->middleware('abilities:bots:read');
    Route::post('bots', [BotApiController::class, 'store'])->middleware('abilities:bots:write');
    Route::put('bots/{bot}', [BotApiController::class, 'update'])->middleware('abilities:bots:write');
    Route::patch('bots/{bot}', [BotApiController::class, 'update'])->middleware('abilities:bots:write');
    Route::delete('bots/{bot}', [BotApiController::class, 'destroy'])->middleware('abilities:bots:write');

    // Universal AI generation endpoint for bot setup ("✨ generate" buttons).
    // Tenant-scoped (controller enforces bot.tenant_id === user.tenant_id),
    // per-tenant rate-limited (60/min + 10/min for full_profile), every
    // call logs to ai_api_metrics with purpose=agent_setup_ai so spend
    // shows up in existing cost dashboards.
    Route::post('bots/{bot}/ai-generate', \App\Http\Controllers\Api\V1\BotAiGenerateController::class)
        ->middleware('abilities:bots:write');

    // Calls
    Route::get('calls', [CallApiController::class, 'index'])->middleware('abilities:calls:read');
    Route::get('calls/{call}', [CallApiController::class, 'show'])->middleware('abilities:calls:read');
    Route::get('calls/{call}/transcript', [CallApiController::class, 'transcript'])->middleware('abilities:calls:read');
    Route::post('calls/outbound', [CallApiController::class, 'outbound'])->middleware('abilities:calls:write');

    // Analytics
    Route::get('analytics/overview', [AnalyticsApiController::class, 'overview'])->middleware('abilities:analytics:read');
    Route::get('bots/{bot}/analytics', [\App\Http\Controllers\Api\BotAnalyticsController::class, 'overview'])->middleware('abilities:analytics:read');
    Route::get('usage', [AnalyticsApiController::class, 'usage'])->middleware('abilities:analytics:read');

    // WooCommerce integration
    Route::post('integrations/connect', [\App\Http\Controllers\Api\V1\IntegrationApiController::class, 'connect'])->middleware('abilities:integrations:write');
    Route::post('integrations/disconnect', [\App\Http\Controllers\Api\V1\IntegrationApiController::class, 'disconnect'])->middleware('abilities:integrations:write');
    Route::post('integrations/sync-products', [\App\Http\Controllers\Api\V1\IntegrationApiController::class, 'syncProducts'])->middleware('abilities:integrations:write');
    Route::post('integrations/sync-categories', [\App\Http\Controllers\Api\V1\IntegrationApiController::class, 'syncCategories'])->middleware('abilities:integrations:write');
    Route::post('integrations/sync-pages', [\App\Http\Controllers\Api\V1\IntegrationApiController::class, 'syncPages'])->middleware('abilities:integrations:write');
    Route::put('integrations/widget-config', [\App\Http\Controllers\Api\V1\IntegrationApiController::class, 'widgetConfig'])->middleware('abilities:integrations:write');
    Route::get('integrations/status', [\App\Http\Controllers\Api\V1\IntegrationApiController::class, 'status'])->middleware('abilities:integrations:read');
    Route::post('integrations/order-lookup', [\App\Http\Controllers\Api\V1\IntegrationApiController::class, 'orderLookup'])->middleware('abilities:integrations:read');
});

// API docs placeholder
Route::get('/docs', function () {
    return response()->json([
        'name' => 'Sambla API',
        'version' => 'v1',
        'base_url' => url('/api/v1'),
        'auth' => 'Bearer token (Sanctum)',
        'endpoints' => [
            'GET /api/health' => 'Health check',
            'GET /api/v1/bots' => 'List bots',
            'POST /api/v1/bots' => 'Create bot',
            'GET /api/v1/bots/{id}' => 'Get bot',
            'PUT /api/v1/bots/{id}' => 'Update bot',
            'DELETE /api/v1/bots/{id}' => 'Delete bot',
            'GET /api/v1/calls' => 'List calls',
            'GET /api/v1/calls/{id}' => 'Get call details',
            'GET /api/v1/calls/{id}/transcript' => 'Get call transcript',
            'POST /api/v1/calls/outbound' => 'Initiate outbound call',
            'GET /api/v1/analytics/overview' => 'Analytics overview',
            'GET /api/v1/usage' => 'Current usage',
        ],
    ]);
});
