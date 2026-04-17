<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\ServiceType;
use Illuminate\Http\Request;

/**
 * Tenant-facing admin for opt-in automation flags + niche preview.
 * Sits next to the WorkspaceController (doesn't touch it) and
 * writes only the automations sub-key of bot.settings via merge,
 * so the rest of settings JSON stays intact.
 */
class WorkspaceAutomationController extends Controller
{
    private const FLAGS = [
        'appointment_reminders_enabled' => [
            'label' => 'Reminder programări prin SMS',
            'desc'  => 'Trimite automat SMS cu 24h și 2h înainte de programare. Folosește numărul Twilio al botului.',
            'requires' => 'booking',
        ],
        'missed_call_recovery_enabled' => [
            'label' => 'Recuperare apeluri ratate',
            'desc'  => 'Dacă cineva sună dar nu răspunde nimeni, agentul trimite automat un SMS de urmărire.',
            'requires' => null,
        ],
        'abandoned_cart_recovery_enabled' => [
            'label' => 'Recuperare coș abandonat (în curând)',
            'desc'  => 'Se va activa după integrarea cu WooCommerce pentru atribuirea comenzilor.',
            'requires' => 'ecommerce',
            'disabled' => true,
        ],
    ];

    public function show(Request $request, Bot $bot)
    {
        abort_unless($bot->tenant_id === auth()->user()->tenant_id || auth()->user()->isSuperAdmin(), 404);

        $nichConfig = $bot->niche_slug ? config('niches.' . $bot->niche_slug, []) : [];
        $serviceTypes = [];
        if (in_array($bot->engine_type, ['booking', 'hybrid'], true)) {
            $serviceTypes = ServiceType::withoutGlobalScopes()
                ->where('bot_id', $bot->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        }

        return view('dashboard.workspace.automations', [
            'bot'            => $bot,
            'engine'         => $bot->engine(),
            'nichConfig'     => $nichConfig,
            'serviceTypes'   => $serviceTypes,
            'flags'          => self::FLAGS,
            'currentValues'  => $bot->settings['automations'] ?? [],
        ]);
    }

    public function update(Request $request, Bot $bot)
    {
        abort_unless($bot->tenant_id === auth()->user()->tenant_id || auth()->user()->isSuperAdmin(), 404);

        $input = $request->all();
        $settings = $bot->settings ?? [];
        $automations = $settings['automations'] ?? [];

        foreach (self::FLAGS as $key => $meta) {
            // Disabled flags can't be flipped on via form — drop
            // any tampered value silently.
            if (!empty($meta['disabled'])) {
                $automations[$key] = false;
                continue;
            }
            $automations[$key] = !empty($input[$key]);
        }

        $settings['automations'] = $automations;
        $bot->settings = $settings;
        $bot->save();

        return redirect()
            ->route('dashboard.workspace.automations', $bot)
            ->with('success', 'Automatizările au fost salvate.');
    }
}
