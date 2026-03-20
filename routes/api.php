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

// Test vocal endpoint (uses web session auth via CSRF, no Sanctum needed)
Route::post('/v1/bots/{bot}/test-vocal', [TestVocalController::class, 'handle']);

// Public chatbot widget API (no auth required, CORS enabled)
Route::post('/v1/chatbot/{channel}/message', [ChatbotApiController::class, 'message']);
Route::get('/v1/chatbot/{channel}/config', [ChatbotApiController::class, 'config']);

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
    Route::get('usage', [AnalyticsApiController::class, 'usage']);
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
