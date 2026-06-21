<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'profile');
        $tenant = auth()->user()->tenant;

        return view('dashboard.settings.index', compact('tab', 'tenant'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'timezone' => 'required|string',
        ]);

        $user->update($validated);
        return back()->with('success', 'Profilul a fost actualizat.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);

        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Parola a fost schimbată.');
    }

    public function updateCompany(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'settings.website' => 'nullable|url',
            'settings.industry' => 'nullable|string',
        ]);

        $previousName = $tenant->name;
        $tenant->update([
            'name' => $validated['name'],
            'settings' => array_merge($tenant->settings ?? [], $validated['settings'] ?? []),
        ]);

        AdminAuditLog::record('tenant.update_company', $tenant, [
            'name' => [$previousName, $validated['name']],
        ]);

        return back()->with('success', 'Datele companiei au fost actualizate.');
    }

    /**
     * Export GDPR — vizitator/utilizator solicită toate datele despre el
     * (email/phone). Returnează JSON streaming cu conversații + mesaje + leads
     * + callbacks. Endpoint operator-only (auth necesar).
     */
    public function exportUserData(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $validated = $request->validate([
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
        ]);
        if (empty($validated['email']) && empty($validated['phone'])) {
            abort(422, 'email sau phone obligatoriu');
        }
        $tenantId = auth()->user()->tenant_id;
        $needles = array_filter([$validated['email'] ?? null, $validated['phone'] ?? null]);

        return response()->streamDownload(function () use ($tenantId, $needles) {
            $convs = \App\Models\Conversation::query()
                ->where('tenant_id', $tenantId)
                ->where(function ($q) use ($needles) {
                    foreach ($needles as $n) {
                        $q->orWhere('contact_identifier', $n)
                            ->orWhere('contact_name', $n);
                    }
                })
                ->limit(500)
                ->get(['id', 'contact_name', 'contact_identifier', 'started_at', 'ended_at', 'metadata']);

            $leads = \App\Models\Lead::query()
                ->where('tenant_id', $tenantId)
                ->where(function ($q) use ($validated) {
                    if (!empty($validated['email'])) {
                        $q->orWhere('email', $validated['email']);
                    }
                    if (!empty($validated['phone'])) {
                        $q->orWhere('phone', $validated['phone']);
                    }
                })
                ->get();

            $convIds = $convs->pluck('id');
            $messages = \App\Models\Message::whereIn('conversation_id', $convIds)
                ->orderBy('id')
                ->get(['id', 'conversation_id', 'direction', 'content', 'created_at']);

            echo json_encode([
                'export_at' => now()->toIso8601String(),
                'query' => $needles,
                'conversations' => $convs,
                'messages' => $messages,
                'leads' => $leads,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, 'gdpr-export-' . now()->format('Ymd-His') . '.json', [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Update retention policy per tenant (GDPR window-uri).
     * Vezi Tenant::retentionSettings() pentru clamp + default fallback.
     */
    public function updateRetention(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $validated = $request->validate([
            'retention.conversations_days' => 'nullable|integer|min:7|max:3650',
            'retention.call_anonymise_days' => 'nullable|integer|min:7|max:3650',
            'retention.recording_purge_days' => 'nullable|integer|min:1|max:3650',
        ]);

        $existing = $tenant->settings ?? [];
        $existing['retention'] = array_merge($existing['retention'] ?? [], $validated['retention'] ?? []);
        $existing['retention'] = array_filter($existing['retention'], fn($v) => $v !== null && $v !== '');
        $tenant->update(['settings' => $existing]);

        return back()->with('success', 'Politicile de retenție au fost salvate.');
    }

    /**
     * Update webhook URL + secret pentru lead notifications externe (CRM).
     */
    public function updateWebhooks(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $validated = $request->validate([
            'webhooks.lead_captured_url' => 'nullable|url|max:500',
            'webhooks.lead_captured_secret' => 'nullable|string|min:8|max:200',
        ]);

        $existing = $tenant->settings ?? [];
        $existing['webhooks'] = array_merge($existing['webhooks'] ?? [], $validated['webhooks'] ?? []);
        $existing['webhooks'] = array_filter($existing['webhooks'], fn($v) => $v !== null && $v !== '');
        $tenant->update(['settings' => $existing]);

        return back()->with('success', 'Webhook-urile au fost salvate.');
    }

    /**
     * Update canned responses (snippet-uri operator) la nivel de tenant.
     */
    public function updateCannedResponses(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $validated = $request->validate([
            'canned_responses' => 'nullable|array|max:30',
            'canned_responses.*.label' => 'required_with:canned_responses.*.body|string|max:100',
            'canned_responses.*.body' => 'required_with:canned_responses.*.label|string|max:1000',
        ]);

        $list = collect($validated['canned_responses'] ?? [])
            ->filter(fn($r) => !empty($r['label']) && !empty($r['body']))
            ->values()
            ->all();

        $existing = $tenant->settings ?? [];
        $existing['canned_responses'] = $list;
        $tenant->update(['settings' => $existing]);

        return back()->with('success', 'Răspunsurile pre-definite au fost salvate.');
    }

    public function updateNotifications(Request $request)
    {
        $user = auth()->user();
        $user->update([
            'notification_preferences' => [
                'call_failed' => $request->boolean('call_failed'),
                'usage_80' => $request->boolean('usage_80'),
                'usage_100' => $request->boolean('usage_100'),
                'invoice_issued' => $request->boolean('invoice_issued'),
                'weekly_report' => $request->boolean('weekly_report'),
                'each_call_completed' => $request->boolean('each_call_completed'),
            ],
        ]);

        return back()->with('success', 'Preferințele de notificare au fost salvate.');
    }

    public function generateApiKey(Request $request)
    {
        $allowed = \App\Support\ApiScopes::all();

        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'scopes' => 'nullable|array',
            'scopes.*' => ['string', 'in:' . implode(',', $allowed)],
        ]);

        $requested = $validated['scopes'] ?? [];
        // Intersect with allowlist defensively; validation already restricts
        // to ApiScopes::all() but this keeps the invariant explicit at the
        // boundary where the token is actually minted.
        $scopes = array_values(array_intersect($requested, $allowed));
        if (empty($scopes)) {
            $scopes = [\App\Support\ApiScopes::BOTS_READ];
        }

        $token = auth()->user()->createToken($validated['name'], $scopes);

        AdminAuditLog::record('api_key.created', auth()->user(), [
            'name' => $validated['name'],
            'scopes' => $scopes,
        ]);

        return back()->with('success', 'Cheia API a fost creată. Copiază-o acum — nu va mai fi afișată.')
                    ->with('new_api_key', $token->plainTextToken);
    }

    public function revokeApiKey(Request $request, $tokenId)
    {
        $token = auth()->user()->tokens()->where('id', $tokenId)->first();
        auth()->user()->tokens()->where('id', $tokenId)->delete();
        if ($token) {
            AdminAuditLog::record('api_key.revoked', auth()->user(), [
                'token_id' => $tokenId,
                'name' => $token->name,
            ]);
        }
        return back()->with('success', 'Cheia API a fost revocată.');
    }

    public function destroyAccount(Request $request)
    {
        $request->validate([
            'confirmation' => 'required|in:STERGE',
        ]);

        $tenant = auth()->user()->tenant;
        // Record before delete — the audit row outlives the tenant
        // (admin_audit_log has user_id nullOnDelete but keeps the row).
        AdminAuditLog::record('tenant.destroy', $tenant, [
            'name' => $tenant->name,
            'by_user' => auth()->user()?->email,
        ]);

        auth()->logout();
        $tenant->delete(); // Cascade deletes users, bots, etc.

        return redirect('/')->with('success', 'Contul a fost șters.');
    }
}
