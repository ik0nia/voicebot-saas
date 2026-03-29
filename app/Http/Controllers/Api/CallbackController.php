<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\CallbackRequest;
use App\Models\Channel;
use App\Models\Lead;
use App\Services\ConversationEventService;
use App\Services\EventTaxonomy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class CallbackController extends Controller
{
    /**
     * POST /api/v1/chatbot/{channel}/callback
     * Submit a callback request from widget or service page.
     */
    public function store(Request $request, $channelId): JsonResponse
    {
        $channel = Channel::withoutGlobalScopes()->where('id', $channelId)->where('is_active', true)->first();
        if (!$channel) return response()->json(['error' => 'Canal invalid'], 404);

        $bot = Bot::withoutGlobalScopes()->find($channel->bot_id);
        if (!$bot) return response()->json(['error' => 'Bot invalid'], 404);

        // Rate limit: 5 callbacks per hour per IP
        $key = 'callback:' . $request->ip() . ':' . $channelId;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['error' => 'Prea multe cereri. Încercați mai târziu.'], 429);
        }
        RateLimiter::hit($key, 3600);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'service_type' => 'nullable|string|max:100',
            'preferred_date' => 'nullable|date|after_or_equal:today',
            'preferred_time_slot' => 'nullable|string|in:dimineata,dupa-amiaza,seara',
            'notes' => 'nullable|string|max:1000',
            'source' => 'nullable|string|max:30',
            'source_page_url' => 'nullable|string|max:500',
            'session_id' => 'nullable|string|max:100',
            'visitor_id' => 'nullable|string|max:100',
            'conversation_id' => 'nullable|integer',
        ]);

        // Create or find lead
        $lead = Lead::updateOrCreate(
            ['tenant_id' => $bot->tenant_id, 'phone' => $validated['phone']],
            [
                'bot_id' => $bot->id,
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'conversation_id' => $validated['conversation_id'] ?? null,
                'session_id' => $validated['session_id'] ?? null,
                'status' => 'qualified',
                'qualification_score' => 60,
                'capture_source' => $validated['source'] ?? 'service_page',
                'capture_reason' => 'callback_request',
                'gdpr_consent' => true,
            ]
        );

        // Create callback request
        $callback = CallbackRequest::create([
            'tenant_id' => $bot->tenant_id,
            'bot_id' => $bot->id,
            'lead_id' => $lead->id,
            'conversation_id' => $validated['conversation_id'] ?? null,
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'service_type' => $validated['service_type'] ?? null,
            'preferred_date' => $validated['preferred_date'] ?? null,
            'preferred_time_slot' => $validated['preferred_time_slot'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'source' => $validated['source'] ?? 'service_page',
            'source_page_url' => $validated['source_page_url'] ?? null,
            'session_id' => $validated['session_id'] ?? null,
            'visitor_id' => $validated['visitor_id'] ?? null,
            'status' => 'pending',
        ]);

        // Send notification email to tenant
        $this->notifyTenant($bot, $callback);

        // Track event
        app(ConversationEventService::class)->track(
            EventTaxonomy::LEAD_COMPLETED,
            ['lead_id' => $lead->id, 'callback_id' => $callback->id, 'source' => $callback->source, 'service' => $callback->service_type],
            [
                'tenant_id' => $bot->tenant_id,
                'bot_id' => $bot->id,
                'conversation_id' => $validated['conversation_id'] ?? null,
                'session_id' => $validated['session_id'] ?? null,
                'event_source' => EventTaxonomy::SOURCE_WIDGET,
                'idempotency_key' => "callback:{$callback->id}",
            ]
        );

        return response()->json([
            'success' => true,
            'callback_id' => $callback->id,
            'message' => 'Mulțumim! Veți fi contactat în curând.',
        ]);
    }

    /**
     * GET /api/v1/chatbot/{channel}/callback/services
     * Returns available services for the callback form.
     */
    public function services($channelId): JsonResponse
    {
        $channel = Channel::withoutGlobalScopes()->where('id', $channelId)->where('is_active', true)->first();
        if (!$channel) return response()->json(['error' => 'Canal invalid'], 404);

        $bot = Bot::withoutGlobalScopes()->find($channel->bot_id);
        if (!$bot) return response()->json([]);

        // Services from bot settings or defaults
        $services = $bot->settings['callback_services'] ?? [
            ['value' => 'instalare', 'label' => 'Instalare / Montaj'],
            ['value' => 'masuratori', 'label' => 'Măsurători la domiciliu'],
            ['value' => 'consultanta', 'label' => 'Consultanță tehnică'],
            ['value' => 'oferta', 'label' => 'Ofertă personalizată'],
            ['value' => 'altele', 'label' => 'Altele'],
        ];

        $timeSlots = [
            ['value' => 'dimineata', 'label' => 'Dimineața (08:00 - 12:00)'],
            ['value' => 'dupa-amiaza', 'label' => 'După-amiaza (12:00 - 17:00)'],
            ['value' => 'seara', 'label' => 'Seara (17:00 - 20:00)'],
        ];

        return response()->json([
            'services' => $services,
            'time_slots' => $timeSlots,
            'bot_name' => $bot->name,
        ]);
    }

    private function notifyTenant(Bot $bot, CallbackRequest $callback): void
    {
        try {
            $tenant = $bot->tenant;
            $email = $tenant->company_email ?? $tenant->users()->first()?->email;
            if (!$email) return;

            $subject = "📞 Programare nouă: {$callback->name} — {$callback->service_type}";
            $body = "O nouă programare a fost solicitată:\n\n"
                . "Nume: {$callback->name}\n"
                . "Telefon: {$callback->phone}\n"
                . ($callback->email ? "Email: {$callback->email}\n" : '')
                . ($callback->service_type ? "Serviciu: {$callback->service_type}\n" : '')
                . ($callback->preferred_date ? "Data preferată: {$callback->preferred_date->format('d.m.Y')}\n" : '')
                . ($callback->preferred_time_slot ? "Interval orar: {$callback->time_slot_label}\n" : '')
                . ($callback->notes ? "Note: {$callback->notes}\n" : '')
                . "\nSursă: {$callback->source}"
                . ($callback->source_page_url ? "\nPagina: {$callback->source_page_url}" : '')
                . "\n\nVezi în dashboard: " . url("/dashboard/callbacks");

            Mail::raw($body, function ($msg) use ($email, $subject) {
                $msg->to($email)->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::debug('Callback email notification failed', ['error' => $e->getMessage()]);
        }
    }
}
