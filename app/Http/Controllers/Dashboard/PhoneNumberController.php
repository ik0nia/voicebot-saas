<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\PhoneNumber;
use App\Models\Bot;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Services\TelnyxService;
use Illuminate\Http\Request;

class PhoneNumberController extends Controller
{
    public function index()
    {
        // Auto-sync pending numbers with Telnyx on page load
        $pendingNumbers = PhoneNumber::where('status', PhoneNumber::STATUS_PENDING)
            ->where('provider', 'telnyx')
            ->get();

        if ($pendingNumbers->isNotEmpty()) {
            $service = app(TelnyxService::class);
            foreach ($pendingNumbers as $number) {
                try {
                    $telnyxStatus = $service->getNumberStatus($number->number);
                    if ($telnyxStatus === 'active') {
                        $number->update(['status' => PhoneNumber::STATUS_ACTIVE, 'is_active' => true]);
                    }
                } catch (\Exception $e) {
                    // Silently skip - don't block page load
                }
            }
        }

        $numbers = PhoneNumber::with('bot')->latest()->paginate(20);
        $bots = Bot::where('is_active', true)->orderBy('name')->get();

        return view('dashboard.numbers.index', compact('numbers', 'bots'));
    }

    public function availableNumbers(Request $request)
    {
        try {
            $service = app(TelnyxService::class);
            $numbers = $service->getAvailableNumbers(
                $request->get('country', 'RO'),
                'local',
                5
            );

            return response()->json(['numbers' => $numbers]);
        } catch (\Exception $e) {
            return response()->json(['numbers' => [], 'error' => 'Nu s-au putut încărca numerele disponibile.'], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'number' => 'required|string|unique:phone_numbers,number',
            'friendly_name' => 'nullable|string|max:255',
            'bot_id' => 'nullable|exists:bots,id',
            'provider' => 'string|in:telnyx,manual',
        ]);

        $validated['tenant_id'] = auth()->user()->tenant_id;

        // Cost: tenant override > platform setting > 27 lei default
        $tenant = Tenant::find($validated['tenant_id']);
        $tenantCostLei = $tenant?->plan_overrides['phone_number_monthly_cost_lei'] ?? null;
        $platformCostLei = $tenantCostLei ?? PlatformSetting::get('phone_number_monthly_cost_lei', 27);
        $validated['monthly_cost_cents'] = (int) round($platformCostLei * 100);

        // If provider is telnyx, purchase the number
        if (($validated['provider'] ?? 'telnyx') === 'telnyx') {
            try {
                $service = app(TelnyxService::class);
                $result = $service->purchaseNumber($validated['number']);

                if (!$result) {
                    return back()->with('error', 'Nu s-a putut achiziționa numărul. Încearcă alt număr.')->withInput();
                }

                $validated['status'] = PhoneNumber::STATUS_PENDING;
                $validated['is_active'] = false;
                $validated['telnyx_order_id'] = $result->id ?? null;
            } catch (\Exception $e) {
                return back()->with('error', 'Eroare la achiziția numărului: ' . $e->getMessage())->withInput();
            }
        } else {
            $validated['status'] = PhoneNumber::STATUS_ACTIVE;
            $validated['is_active'] = true;
        }

        $phoneNumber = PhoneNumber::create($validated);

        // Set tags in Telnyx with bot name
        if ($phoneNumber->bot_id && $phoneNumber->provider === 'telnyx') {
            $bot = Bot::find($phoneNumber->bot_id);
            if ($bot) {
                app(TelnyxService::class)->updateNumberTags($phoneNumber->number, [
                    'bot' => $bot->name,
                    'tenant_id' => (string) $phoneNumber->tenant_id,
                ]);
            }
        }

        $message = $validated['status'] === PhoneNumber::STATUS_PENDING
            ? 'Numărul a fost comandat! Este în curs de activare — verificarea documentelor poate dura 1-2 zile lucrătoare.'
            : 'Numărul a fost adăugat cu succes!';

        return back()->with('success', $message);
    }

    public function update(Request $request, PhoneNumber $phoneNumber)
    {
        $validated = $request->validate([
            'bot_id' => 'nullable|exists:bots,id',
            'friendly_name' => 'nullable|string|max:255',
        ]);

        $phoneNumber->update($validated);

        // Update tags in Telnyx when bot association changes
        if ($phoneNumber->provider === 'telnyx' && array_key_exists('bot_id', $validated)) {
            $tags = ['tenant_id' => (string) $phoneNumber->tenant_id];
            if ($phoneNumber->bot_id) {
                $bot = Bot::find($phoneNumber->bot_id);
                if ($bot) {
                    $tags['bot'] = $bot->name;
                }
            }
            app(TelnyxService::class)->updateNumberTags($phoneNumber->number, $tags);
        }

        return back()->with('success', 'Numărul a fost actualizat.');
    }

    public function destroy(PhoneNumber $phoneNumber)
    {
        $phoneNumber->delete();
        return back()->with('success', 'Numărul a fost eliberat.');
    }

    public function toggleActive(PhoneNumber $phoneNumber)
    {
        $phoneNumber->update(['is_active' => !$phoneNumber->is_active]);
        return back()->with('success', $phoneNumber->is_active ? 'Număr activat.' : 'Număr dezactivat.');
    }

    public function syncStatuses()
    {
        $service = app(TelnyxService::class);
        $numbers = PhoneNumber::where('provider', 'telnyx')->get();
        $synced = 0;

        foreach ($numbers as $number) {
            $telnyxStatus = $service->getNumberStatus($number->number);

            if ($telnyxStatus === 'active' && $number->status !== PhoneNumber::STATUS_ACTIVE) {
                $number->update(['status' => PhoneNumber::STATUS_ACTIVE, 'is_active' => true]);
                $synced++;
            } elseif ($telnyxStatus === 'pending' && $number->status === PhoneNumber::STATUS_ACTIVE) {
                $number->update(['status' => PhoneNumber::STATUS_PENDING, 'is_active' => false]);
                $synced++;
            }
        }

        $message = $synced > 0
            ? "Statusul a fost sincronizat pentru {$synced} numere."
            : 'Toate numerele sunt deja sincronizate.';

        return back()->with('success', $message);
    }
}
