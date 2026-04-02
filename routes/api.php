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

// Logo upload endpoints (simple, no auth — protected by obscurity + rate limit)
Route::post('/upload-logo', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'logo' => 'required|file|max:2048|mimes:png,jpg,jpeg,svg,webp',
        'type' => 'required|in:light,dark',
    ]);
    $file = $request->file('logo');
    $ext = $file->getClientOriginalExtension() ?: 'png';
    $name = $request->type === 'light' ? 'logo-light.' . $ext : 'logo-dark.' . $ext;
    $file->move(public_path('images'), $name);
    return response()->json(['success' => true, 'path' => '/images/' . $name, 'filename' => $name]);
})->middleware('throttle:10,1');

Route::post('/upload-logo-url', function (\Illuminate\Http\Request $request) {
    $request->validate(['url' => 'required|url|max:2000', 'type' => 'required|in:light,dark']);
    $url = $request->url;
    $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'png';
    if (!in_array($ext, ['png','jpg','jpeg','svg','webp'])) $ext = 'png';
    $name = $request->type === 'light' ? 'logo-light.' . $ext : 'logo-dark.' . $ext;
    $contents = @file_get_contents($url);
    if ($contents === false) return response()->json(['error' => 'Nu am putut descărca fișierul'], 422);
    file_put_contents(public_path('images/' . $name), $contents);
    return response()->json(['success' => true, 'path' => '/images/' . $name, 'filename' => $name]);
})->middleware('throttle:10,1');

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

// Public chatbot widget API (no auth required)
Route::post('/v1/chatbot/{channel}/message', [ChatbotApiController::class, 'message']);
Route::get('/v1/chatbot/{channel}/config', [ChatbotApiController::class, 'config']);
Route::get('/v1/chatbot/{channel}/products', [ChatbotApiController::class, 'searchProducts']);
Route::post('/v1/chatbot/{channel}/feedback', [ChatbotApiController::class, 'feedback'])->middleware('throttle:30,1');

// V2 Analytics, Capabilities & Lead capture (public, widget-facing)
Route::post('/v1/chatbot/{channel}/events', [\App\Http\Controllers\Api\EventTrackingController::class, 'trackBatch']);
Route::get('/v1/chatbot/{channel}/capabilities', [\App\Http\Controllers\Api\EventTrackingController::class, 'capabilities']);
Route::post('/v1/chatbot/{channel}/lead', [\App\Http\Controllers\Api\EventTrackingController::class, 'captureLead']);
Route::post('/v1/chatbot/{channel}/callback', [\App\Http\Controllers\Api\CallbackController::class, 'store']);
Route::get('/v1/chatbot/{channel}/callback/services', [\App\Http\Controllers\Api\CallbackController::class, 'services']);

// V2 Purchase webhook from WordPress companion plugin (signed, no auth)
Route::post('/v1/webhooks/woocommerce/{bot}/purchase', [\App\Http\Controllers\Api\PurchaseWebhookController::class, 'handle']);

// Plugin update check (public, no auth - called by WordPress updater)
Route::get('v1/plugin/update-check', [\App\Http\Controllers\Api\V1\PluginUpdateController::class, 'check']);

// API v1 - requires Sanctum auth
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Bots
    Route::apiResource('bots', BotApiController::class);

    // Calls
    Route::get('calls', [CallApiController::class, 'index']);
    Route::get('calls/{call}', [CallApiController::class, 'show']);
    Route::get('calls/{call}/transcript', [CallApiController::class, 'transcript']);
    Route::post('calls/outbound', [CallApiController::class, 'outbound']);

    // Analytics
    Route::get('analytics/overview', [AnalyticsApiController::class, 'overview']);

    // V2: Bot analytics (conversion funnel, attribution, outcomes)
    Route::get('bots/{bot}/analytics', [\App\Http\Controllers\Api\BotAnalyticsController::class, 'overview']);
    Route::get('usage', [AnalyticsApiController::class, 'usage']);

    // WooCommerce integration
    Route::post('integrations/connect', [\App\Http\Controllers\Api\V1\IntegrationApiController::class, 'connect']);
    Route::post('integrations/disconnect', [\App\Http\Controllers\Api\V1\IntegrationApiController::class, 'disconnect']);
    Route::post('integrations/sync-products', [\App\Http\Controllers\Api\V1\IntegrationApiController::class, 'syncProducts']);
    Route::post('integrations/sync-categories', [\App\Http\Controllers\Api\V1\IntegrationApiController::class, 'syncCategories']);
    Route::post('integrations/sync-pages', [\App\Http\Controllers\Api\V1\IntegrationApiController::class, 'syncPages']);
    Route::put('integrations/widget-config', [\App\Http\Controllers\Api\V1\IntegrationApiController::class, 'widgetConfig']);
    Route::get('integrations/status', [\App\Http\Controllers\Api\V1\IntegrationApiController::class, 'status']);
    Route::post('integrations/order-lookup', [\App\Http\Controllers\Api\V1\IntegrationApiController::class, 'orderLookup']);
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
