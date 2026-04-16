<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\PhoneNumber;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Services\Telephony\TelephonyManager;
use Illuminate\Http\Request;

class PhoneNumberController extends Controller
{
    public function __construct(
        private TelephonyManager $telephony,
    ) {}

    public function index()
    {
        // Auto-sync pending numbers on page load. Routes to the owning
        // provider per number so the page works while Telnyx and Twilio
        // co-exist during the migration.
        $pendingNumbers = PhoneNumber::where('status', PhoneNumber::STATUS_PENDING)
            ->whereIn('provider', ['telnyx', 'twilio'])
            ->get();

        foreach ($pendingNumbers as $number) {
            try {
                $providerStatus = $this->telephony->forNumber($number)->getNumberStatus($number->number);
                if ($providerStatus === 'active') {
                    $number->update(['status' => PhoneNumber::STATUS_ACTIVE, 'is_active' => true]);
                }
            } catch (\Exception $e) {
                // Silently skip — don't block page load on a provider hiccup.
            }
        }

        $numbers = PhoneNumber::with('bot')->latest()->paginate(20);
        $bots = Bot::where('is_active', true)->orderBy('name')->get();

        return view('dashboard.numbers.index', compact('numbers', 'bots'));
    }

    public function availableNumbers(Request $request)
    {
        // New numbers always go through the default provider — the
        // migration target. Existing Telnyx numbers keep working via
        // forNumber() on the individual resources.
        try {
            $numbers = $this->telephony->default()->getAvailableNumbers(
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
        $defaultProvider = config('telephony.default', 'twilio');

        $validated = $request->validate([
            'number' => 'required|string|unique:phone_numbers,number',
            'friendly_name' => 'nullable|string|max:255',
            'bot_id' => 'nullable|exists:bots,id',
            'provider' => 'string|in:telnyx,twilio,manual',
        ]);

        if (!empty($validated['bot_id'])) {
            $ownsBot = Bot::where('id', $validated['bot_id'])->exists();
            if (!$ownsBot) {
                return back()->withErrors(['bot_id' => 'Agentul AI selectat nu aparține contului tău.'])->withInput();
            }
        }

        $validated['tenant_id'] = auth()->user()->tenant_id;
        $validated['provider'] = $validated['provider'] ?? $defaultProvider;

        $tenant = Tenant::find($validated['tenant_id']);
        $tenantCostLei = $tenant?->plan_overrides['phone_number_monthly_cost_lei'] ?? null;
        $platformCostLei = $tenantCostLei ?? PlatformSetting::get('phone_number_monthly_cost_lei', 27);
        $validated['monthly_cost_cents'] = (int) round($platformCostLei * 100);

        if ($validated['provider'] !== 'manual') {
            try {
                $provider = $this->telephony->for($validated['provider']);
                $result = $provider->purchaseNumber($validated['number']);

                if (!$result) {
                    return back()->with('error', 'Nu s-a putut achiziționa numărul. Încearcă alt număr.')->withInput();
                }

                // Twilio provisioning is synchronous → active immediately.
                // Telnyx provisioning opens a number_order that may sit in
                // 'pending' for 1-2 days while regulatory verification runs.
                if ($validated['provider'] === 'telnyx') {
                    $validated['status'] = PhoneNumber::STATUS_PENDING;
                    $validated['is_active'] = false;
                    $validated['telnyx_order_id'] = $result->id ?? null;
                } else {
                    $validated['status'] = PhoneNumber::STATUS_ACTIVE;
                    $validated['is_active'] = true;
                }
            } catch (\Exception $e) {
                return back()->with('error', 'Eroare la achiziția numărului: ' . $e->getMessage())->withInput();
            }
        } else {
            $validated['status'] = PhoneNumber::STATUS_ACTIVE;
            $validated['is_active'] = true;
        }

        $phoneNumber = PhoneNumber::create($validated);

        if ($phoneNumber->bot_id && $phoneNumber->provider !== 'manual') {
            $bot = Bot::find($phoneNumber->bot_id);
            if ($bot) {
                $this->telephony->forNumber($phoneNumber)->updateNumberTags($phoneNumber->number, [
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

        if (!empty($validated['bot_id'])) {
            $ownsBot = Bot::where('id', $validated['bot_id'])->exists();
            if (!$ownsBot) {
                return back()->withErrors(['bot_id' => 'Agentul AI selectat nu aparține contului tău.'])->withInput();
            }
        }

        $phoneNumber->update($validated);

        if ($phoneNumber->provider !== 'manual' && array_key_exists('bot_id', $validated)) {
            $tags = ['tenant_id' => (string) $phoneNumber->tenant_id];
            if ($phoneNumber->bot_id) {
                $bot = Bot::find($phoneNumber->bot_id);
                if ($bot) {
                    $tags['bot'] = $bot->name;
                }
            }
            $this->telephony->forNumber($phoneNumber)->updateNumberTags($phoneNumber->number, $tags);
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
        $numbers = PhoneNumber::whereIn('provider', ['telnyx', 'twilio'])->get();
        $synced = 0;

        foreach ($numbers as $number) {
            try {
                $providerStatus = $this->telephony->forNumber($number)->getNumberStatus($number->number);
            } catch (\Exception $e) {
                continue;
            }

            if ($providerStatus === 'active' && $number->status !== PhoneNumber::STATUS_ACTIVE) {
                $number->update(['status' => PhoneNumber::STATUS_ACTIVE, 'is_active' => true]);
                $synced++;
            } elseif ($providerStatus === 'pending' && $number->status === PhoneNumber::STATUS_ACTIVE) {
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
