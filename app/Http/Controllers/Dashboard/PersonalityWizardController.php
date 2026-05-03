<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Personalitate wizard — sliders interactive care editează tone_guide
 * și permit preview live al promptului final înainte de save.
 *
 * Sliders:
 *   - Lungime răspuns: 1=foarte scurt, 5=foarte detaliat
 *   - Formal vs prietenos: 1=foarte formal, 5=super prietenos
 *   - Emoji: 0=fără, 1=ocazional, 2=frecvent
 *   - Pro-activitate: 1=așteaptă întrebări, 5=ghidează utilizatorul
 *   - Empatie: 1=factual, 5=empatic
 *
 * UI: 5 sliders + preview tone hints generate; save → bot.settings.tone_guide
 */
class PersonalityWizardController extends Controller
{
    public function show(Bot $bot)
    {
        abort_unless(
            $bot->tenant_id === auth()->user()->tenant_id || auth()->user()->isSuperAdmin(),
            404,
        );

        $tone = $bot->settings['tone_guide'] ?? [];

        // Map persisted tone_guide to slider values
        $sliders = [
            'length' => match($tone['length'] ?? 'medium') {
                'short' => 2, 'medium' => 3, 'long' => 4, default => 3,
            },
            'register' => match($tone['register'] ?? 'tu') {
                'dvs' => 1, 'tu' => 4, default => 4,
            },
            'emoji' => (bool) ($tone['emoji_ok'] ?? false) ? 1 : 0,
            'proactivity' => (int) ($tone['proactivity'] ?? 3),
            'empathy' => (int) ($tone['empathy'] ?? 3),
        ];

        return view('dashboard.personality-wizard.show', compact('bot', 'sliders'));
    }

    public function update(Request $request, Bot $bot): RedirectResponse
    {
        abort_unless(
            $bot->tenant_id === auth()->user()->tenant_id || auth()->user()->isSuperAdmin(),
            404,
        );

        $validated = $request->validate([
            'length'      => 'required|integer|min:1|max:5',
            'register'    => 'required|integer|min:1|max:5',
            'emoji'       => 'required|integer|min:0|max:2',
            'proactivity' => 'required|integer|min:1|max:5',
            'empathy'     => 'required|integer|min:1|max:5',
        ]);

        // Translate sliders → tone_guide canonical values
        $toneGuide = [
            'length'    => $validated['length'] <= 2 ? 'short' : ($validated['length'] >= 4 ? 'long' : 'medium'),
            'register'  => $validated['register'] <= 2 ? 'dvs' : 'tu',
            'emoji_ok'  => $validated['emoji'] >= 1,
            'proactivity' => $validated['proactivity'],
            'empathy'   => $validated['empathy'],
        ];

        $settings = $bot->settings ?? [];
        $settings['tone_guide'] = array_merge($settings['tone_guide'] ?? [], $toneGuide);
        $bot->settings = $settings;
        $bot->save();

        return redirect()->route('dashboard.personality-wizard.show', $bot)
            ->with('success', 'Personalitatea agentului a fost actualizată.');
    }
}
