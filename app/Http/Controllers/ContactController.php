<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\PlatformSetting;
use App\Notifications\ContactMessageReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Public contact form handler. Saves the message, notifies
 * support on email, flashes a generate_lead event so the
 * marketing stack (GA4 + Meta + Google Ads) can credit the
 * acquisition channel, and returns the user to the form with a
 * success flash.
 */
class ContactController extends Controller
{
    public function store(Request $request)
    {
        // Honeypot — any bot that fills a hidden field gets a
        // silent "success" so it doesn't retry from a different IP.
        if (filled($request->input('website'))) {
            return back()->with('contact_success', true)->withFragment('contact-form');
        }

        $data = $request->validate([
            'name'         => ['required', 'string', 'max:180'],
            'email'        => ['required', 'email', 'max:180'],
            'phone'        => ['nullable', 'string', 'max:40'],
            'company'      => ['nullable', 'string', 'max:180'],
            'subject'      => ['nullable', 'string', 'max:240'],
            'message'      => ['required', 'string', 'max:4000'],
            // Server-side GDPR enforcement — the checkbox is `required`
            // in the view, but a bot or JS-disabled client could POST
            // without it. accepted = "1", "true", "on", "yes".
            'gdpr_consent' => ['required', 'accepted'],
        ]);

        // Marketing attribution snapshot — read from session so the
        // lead row carries the campaign that brought them here.
        $attribution = $request->session()->get('attribution_last', $request->session()->get('attribution_first', []));

        $msg = ContactMessage::create([
            'name'       => $data['name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'company'    => $data['company'] ?? null,
            'subject'    => $data['subject'] ?? null,
            'message'    => $data['message'],
            'source'     => $request->input('source', 'contact_form'),
            'source_url' => mb_substr((string) $request->headers->get('referer', ''), 0, 500) ?: url('/contact'),
            'utm_source'   => $attribution['utm_source']   ?? null,
            'utm_medium'   => $attribution['utm_medium']   ?? null,
            'utm_campaign' => $attribution['utm_campaign'] ?? null,
            'gclid'        => $attribution['gclid']        ?? null,
            'fbclid'       => $attribution['fbclid']       ?? null,
            'ip'         => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
        ]);

        // Notify the operator — use the platform support email setting
        // (set in Admin → Setări → Email), fall back to servus@sambla.ro
        // if the operator never configured one.
        $supportEmail = PlatformSetting::get('support_email') ?: 'servus@sambla.ro';
        try {
            Notification::route('mail', $supportEmail)->notify(new ContactMessageReceived($msg));
        } catch (\Throwable $e) {
            Log::warning('ContactController: notification failed', ['err' => $e->getMessage()]);
        }

        // Analytics: generate_lead for the marketing stack. lead_source
        // distinguishes contact vs niche landing at the GA4 / CAPI
        // layer; value is a placeholder LTV so bidders have a signal.
        app(\App\Services\Analytics\AnalyticsTracker::class)->flash('generate_lead', [
            'lead_source' => 'contact_form',
            'value'       => 50,
            'currency'    => 'EUR',
        ]);

        return back()->with('contact_success', true)->withFragment('contact-form');
    }
}
