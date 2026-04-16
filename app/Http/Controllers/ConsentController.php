<?php

namespace App\Http\Controllers;

use App\Models\ConsentLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Logs every consent decision to consent_logs for GDPR proof.
 * Does NOT set server-side cookies — the actual storage is
 * localStorage in the widget, mirrored into Google Consent Mode
 * via gtag('consent','update'). This endpoint is audit-only.
 */
class ConsentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source'  => 'nullable|string|max:40',
            'consent' => 'required|array',
            'page'    => 'nullable|string|max:500',
        ]);

        // Only keep the known Consent Mode v2 signals — drop anything
        // else a client might try to stuff in.
        $allowed = array_keys(config('analytics.consent_defaults', []));
        $consent = array_intersect_key($validated['consent'], array_flip($allowed));

        ConsentLog::create([
            'user_id'    => $request->user()?->id,
            'tenant_id'  => $request->user()?->tenant_id ?? null,
            'session_id' => $request->session()->getId(),
            'source'     => $validated['source'] ?? 'settings_modal',
            'consent'    => $consent,
            'ip'         => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
            'page'       => $validated['page'] ?? null,
        ]);

        return response()->json(['ok' => true]);
    }
}
