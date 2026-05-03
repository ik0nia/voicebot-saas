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
