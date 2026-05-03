<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Jobs\DeliverWebhook;
use App\Models\WebhookEndpoint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tenant CRUD pentru webhook outbound endpoints.
 *
 * Acces: tenant_admin, tenant_manager.
 */
class WebhookEndpointController extends Controller
{
    public function index(): View
    {
        $endpoints = WebhookEndpoint::with(['deliveries' => function ($q) {
            $q->latest('id')->limit(1);
        }])->latest('id')->get();

        return view('dashboard.webhooks.index', [
            'endpoints' => $endpoints,
        ]);
    }

    public function create(): View
    {
        return view('dashboard.webhooks.form', [
            'endpoint' => new WebhookEndpoint(['events' => array_keys(WebhookEndpoint::AVAILABLE_EVENTS)]),
            'isCreate' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);
        $validated['secret'] = WebhookEndpoint::generateSecret();
        $validated['tenant_id'] = auth()->user()->tenant_id;

        $endpoint = WebhookEndpoint::create($validated);

        return redirect()->route('dashboard.webhooks.show', $endpoint)
            ->with('success', 'Webhook configurat. Ține secretul în siguranță — nu mai apare după ce reîncarci pagina.')
            ->with('webhook_secret', $endpoint->secret);
    }

    public function show(WebhookEndpoint $endpoint): View
    {
        $this->ensureOwnership($endpoint);

        $deliveries = $endpoint->deliveries()->latest('id')->paginate(30);

        return view('dashboard.webhooks.show', [
            'endpoint' => $endpoint,
            'deliveries' => $deliveries,
        ]);
    }

    public function edit(WebhookEndpoint $endpoint): View
    {
        $this->ensureOwnership($endpoint);

        return view('dashboard.webhooks.form', [
            'endpoint' => $endpoint,
            'isCreate' => false,
        ]);
    }

    public function update(Request $request, WebhookEndpoint $endpoint): RedirectResponse
    {
        $this->ensureOwnership($endpoint);

        $validated = $this->validatePayload($request);
        $endpoint->update($validated);

        return redirect()->route('dashboard.webhooks.show', $endpoint)
            ->with('success', 'Webhook actualizat.');
    }

    public function destroy(WebhookEndpoint $endpoint): RedirectResponse
    {
        $this->ensureOwnership($endpoint);

        $endpoint->delete();

        return redirect()->route('dashboard.webhooks.index')
            ->with('success', 'Webhook eliminat.');
    }

    /**
     * Trimite un payload de test pe endpoint — util pentru tenant să verifice
     * că serverul lor primește semnătura corect.
     */
    public function testFire(WebhookEndpoint $endpoint): RedirectResponse
    {
        $this->ensureOwnership($endpoint);

        DeliverWebhook::dispatch(
            $endpoint->id,
            'lead.created',
            [
                'test' => true,
                'message' => 'Test ping din dashboard Sambla.',
                'sent_at' => now()->toIso8601String(),
            ],
        );

        return redirect()->route('dashboard.webhooks.show', $endpoint)
            ->with('success', 'Ping de test trimis. Vezi în istoric peste câteva secunde.');
    }

    /**
     * Playground UI — UI pentru a edita JSON payload + event type și a trimite
     * la endpoint-ul ales. Mock-uri pentru fiecare event predefined.
     */
    public function playground(WebhookEndpoint $endpoint)
    {
        $this->ensureOwnership($endpoint);

        $mockPayloads = [
            'lead.created' => [
                'lead_id' => 12345,
                'name' => 'Andrei Popescu',
                'email' => 'andrei@exemplu.ro',
                'phone' => '+40721234567',
                'source' => 'web_chatbot',
                'pipeline_stage' => 'new',
                'bot_id' => 1,
                'created_at' => now()->toIso8601String(),
            ],
            'callback.requested' => [
                'callback_id' => 67890,
                'name' => 'Maria Ionescu',
                'phone' => '+40731234567',
                'preferred_window' => 'azi 14:00-16:00',
                'bot_id' => 1,
                'created_at' => now()->toIso8601String(),
            ],
            'conversation.completed' => [
                'conversation_id' => 4567,
                'contact_name' => 'Vizitator anonim',
                'contact_identifier' => 'web-session-abc123',
                'messages_count' => 8,
                'lead_score' => 75,
                'primary_intent' => 'programare_consultatie',
                'channel_id' => 1,
                'bot_id' => 1,
            ],
            'call.ended' => [
                'call_id' => 9876,
                'duration_seconds' => 187,
                'status' => 'completed',
                'caller_number' => '+40741234567',
                'direction' => 'inbound',
                'bot_id' => 1,
                'started_at' => now()->subMinutes(3)->toIso8601String(),
                'ended_at' => now()->toIso8601String(),
            ],
            'appointment.created' => [
                'appointment_id' => 5432,
                'name' => 'Cristian Vasilescu',
                'phone' => '+40751234567',
                'service' => 'Consultație inițială',
                'starts_at' => now()->addDay()->setTime(14, 0)->toIso8601String(),
                'bot_id' => 1,
                'status' => 'pending_confirmation',
            ],
        ];

        return view('dashboard.webhooks.playground', [
            'endpoint' => $endpoint,
            'mockPayloads' => $mockPayloads,
        ]);
    }

    /**
     * POST endpoint pentru a trimite un payload custom la URL-ul endpoint-ului.
     * Returnează raw response (status, headers, body, latency) pentru debugging.
     */
    public function playgroundFire(Request $request, WebhookEndpoint $endpoint): \Illuminate\Http\JsonResponse
    {
        $this->ensureOwnership($endpoint);

        $validated = $request->validate([
            'event' => 'required|string|max:64',
            'payload' => 'required|array',
        ]);

        // Throttle 20 fires/min/tenant
        $key = 'webhook-playground:' . (auth()->user()->tenant_id ?? 0);
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 20)) {
            return response()->json(['error' => 'Prea multe trimiteri — așteaptă 1 min'], 429);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($key, 60);

        $payload = [
            'event' => $validated['event'],
            'tenant_id' => $endpoint->tenant_id,
            'timestamp' => now()->toIso8601String(),
            'data' => $validated['payload'],
        ];

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $timestamp = (string) time();
        $signed = $timestamp . '.' . $body;
        $signature = hash_hmac('sha256', $signed, $endpoint->secret);

        $start = microtime(true);
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(15)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Sambla/1.0 (playground)',
                    'X-Sambla-Event' => $validated['event'],
                    'X-Sambla-Timestamp' => $timestamp,
                    'X-Sambla-Signature' => 'sha256=' . $signature,
                ])
                ->withBody($body, 'application/json')
                ->post($endpoint->url);

            $duration = (int) ((microtime(true) - $start) * 1000);

            return response()->json([
                'success' => $response->successful(),
                'status' => $response->status(),
                'latency_ms' => $duration,
                'response_headers' => $response->headers(),
                'response_body' => mb_substr((string) $response->body(), 0, 4000),
                'request_body' => $payload,
                'request_headers' => [
                    'X-Sambla-Event' => $validated['event'],
                    'X-Sambla-Timestamp' => $timestamp,
                    'X-Sambla-Signature' => 'sha256=' . $signature,
                ],
            ]);
        } catch (\Throwable $e) {
            $duration = (int) ((microtime(true) - $start) * 1000);
            return response()->json([
                'success' => false,
                'status' => 0,
                'error' => $e->getMessage(),
                'latency_ms' => $duration,
            ], 200); // 200 ca să UI-ul interpreteze; eroarea e în payload
        }
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:100',
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'required|string|in:' . implode(',', array_keys(WebhookEndpoint::AVAILABLE_EVENTS)),
            'is_active' => 'nullable|boolean',
        ]);
    }

    /**
     * Verifică că endpoint-ul aparține tenantului curent (sau super_admin).
     * Numit ensureOwnership ca să nu coincidă cu Controller::authorize()
     * moștenit din AuthorizesRequests trait.
     */
    private function ensureOwnership(WebhookEndpoint $endpoint): void
    {
        abort_unless(
            $endpoint->tenant_id === auth()->user()->tenant_id || auth()->user()->isSuperAdmin(),
            404,
        );
    }
}
