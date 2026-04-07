<?php

namespace App\Http\Controllers;

use App\Models\Niche;
use App\Models\NicheLead;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

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
            'name' => ['required', 'string', 'max:120'],
            'business_name' => ['nullable', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['required', 'string', 'max:40'],
            'message' => ['nullable', 'string', 'max:2000'],
            'source_url' => ['nullable', 'string', 'max:500'],
        ]);

        NicheLead::create([
            'niche_id' => $niche->id,
            'name' => $data['name'],
            'business_name' => $data['business_name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'],
            'message' => $data['message'] ?? null,
            'source_url' => $data['source_url'] ?? $request->fullUrl(),
            'ip' => $request->ip(),
        ]);

        return back()
            ->with('niche_lead_success', 'Mulțumim! Te contactăm în 24h cu un demo personalizat.')
            ->withFragment('contact');
    }
}
