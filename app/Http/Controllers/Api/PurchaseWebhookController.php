<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Services\AttributionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives purchase webhooks from the WordPress companion plugin.
 * Validates HMAC signature and processes attribution.
 */
class PurchaseWebhookController extends Controller
{
    public function handle(Request $request, Bot $bot): JsonResponse
    {
        $bot = Bot::withoutGlobalScopes()->findOrFail($bot->id);

        // Verify HMAC signature
        $signature = $request->header('X-Sambla-Signature', '');
        $payload = $request->getContent();

        $connector = \App\Models\KnowledgeConnector::where('bot_id', $bot->id)
            ->where('type', 'woocommerce')->first();

        $secret = $connector?->credentials['consumer_key'] ?? '';
        if (empty($secret)) {
            // Fallback: use API key from platform settings
            $secret = \App\Models\PlatformSetting::get('api_key', config('app.key'));
        }

        $expected = hash_hmac('sha256', $payload, $secret);
        if (!hash_equals($expected, $signature)) {
            Log::warning('PurchaseWebhook: invalid signature', ['bot_id' => $bot->id]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $data = $request->validate([
            'wc_order_id' => 'required|string',
            'total' => 'required|numeric',
            'currency' => 'nullable|string',
            'status' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.product_id' => 'nullable|integer',
            'items.*.name' => 'nullable|string',
            'items.*.quantity' => 'nullable|integer',
            'items.*.total' => 'nullable|numeric',
            'customer_email' => 'nullable|email',
            'session_id' => 'nullable|string',
            'conversation_id' => 'nullable|integer',
            'bot_id' => 'nullable|integer',
            'channel_id' => 'nullable|integer',
            'visitor_id' => 'nullable|string',
        ]);

        $attribution = app(AttributionService::class)->attributePurchase($data, $bot->id, $bot->tenant_id);

        return response()->json([
            'attributed' => $attribution !== null,
            'attribution_mode' => $attribution?->attribution_mode,
        ]);
    }
}
