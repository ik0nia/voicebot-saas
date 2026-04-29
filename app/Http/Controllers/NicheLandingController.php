<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Niche;
use App\Models\NicheLead;
use App\Models\PlatformSetting;
use App\Notifications\ContactMessageReceived;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NicheLandingController extends Controller
{
    public function storeLead(Request $request, Niche $niche): RedirectResponse
    {
        abort_unless($niche->is_active, 404);

        // Honeypot — silently succeed if filled.
        if (filled($request->input('website'))) {
            return back()->with('niche_lead_success', 'Mulțumim! Îți răspundem în curând.');
        }

        $data = $request->validate([
            'name'         => ['required', 'string', 'max:120'],
            'website_url'  => ['nullable', 'string', 'max:500'],
            'email'        => ['required', 'email', 'max:180'],
            'phone'        => ['required', 'string', 'max:40'],
            'message'      => ['nullable', 'string', 'max:2000'],
            'source_url'   => ['nullable', 'string', 'max:500'],
            // Server-side GDPR enforcement — checkbox required client-side,
            // but a bot or no-JS client could POST without it. The DPA-RO
            // expects an audit trail of explicit consent per submission.
            'gdpr_consent' => ['required', 'accepted'],
        ]);

        $lead = NicheLead::create([
            'niche_id' => $niche->id,
            'name' => $data['name'],
            'business_name' => $data['website_url'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'],
            'message' => $data['message'] ?? null,
            'source_url' => $data['source_url'] ?? $request->fullUrl(),
            'ip' => $request->ip(),
        ]);

        // Mirror into the unified contact_messages inbox so the
        // admin sees every SaaS lead (contact + niche) in one
        // place + the support email fires regardless of source.
        $attribution = $request->session()->get('attribution_last', $request->session()->get('attribution_first', []));
        $mirror = ContactMessage::create([
            'name'       => $data['name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'],
            'company'    => $data['website_url'] ?? null,
            'subject'    => 'Cerere demo — ' . $niche->name,
            'message'    => $data['message'] ?? ('Cerere demo pentru nișa: ' . $niche->name),
            'source'     => 'niche_landing',
            'source_url' => $data['source_url'] ?? $request->fullUrl(),
            'utm_source'   => $attribution['utm_source']   ?? null,
            'utm_medium'   => $attribution['utm_medium']   ?? null,
            'utm_campaign' => $attribution['utm_campaign'] ?? null,
            'gclid'        => $attribution['gclid']        ?? null,
            'fbclid'       => $attribution['fbclid']       ?? null,
            'ip'         => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
        ]);

        $supportEmail = PlatformSetting::get('support_email') ?: 'servus@sambla.ro';
        try {
            Notification::route('mail', $supportEmail)->notify(new ContactMessageReceived($mirror));
        } catch (\Throwable $e) {
            Log::warning('NicheLandingController: notification failed', ['err' => $e->getMessage()]);
        }

        app(\App\Services\Analytics\AnalyticsTracker::class)->flash('generate_lead', [
            'lead_source' => 'niche_landing',
            'niche'       => $niche->slug,
            'value'       => 80,
            'currency'    => 'EUR',
        ]);

        return back()
            ->with('niche_lead_success', 'Mulțumim! Te contactăm în 24h cu un demo personalizat.')
            ->withFragment('contact');
    }
}
