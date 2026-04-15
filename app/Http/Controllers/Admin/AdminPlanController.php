<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncPlanToStripe;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminPlanController extends Controller
{
    public function index()
    {
        $plans = Plan::orderBy('sort_order')->orderBy('name')->get();

        $grouped = $plans->groupBy('type');

        return view('admin.plans.index', compact('plans', 'grouped'));
    }

    public function create()
    {
        return view('admin.plans.form', [
            'plan' => null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePlan($request);

        $validated['limits'] = $this->parseLimits($request);
        $validated['overage'] = $this->parseOverage($request);
        $validated['features'] = $this->parseFeatures($request);
        $validated['topups'] = $this->parseTopups($request);
        $validated['is_popular'] = $request->boolean('is_popular');
        $validated['is_active'] = $request->boolean('is_active', true);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $plan = Plan::create($validated);

        SyncPlanToStripe::dispatch($plan->id);

        return redirect()->route('admin.plans.index')->with('success', 'Pachetul a fost creat. Sincronizarea cu Stripe rulează în background.');
    }

    public function edit(Plan $pachete)
    {
        return view('admin.plans.form', [
            'plan' => $pachete,
        ]);
    }

    public function update(Request $request, Plan $pachete)
    {
        $validated = $this->validatePlan($request, $pachete);

        $validated['limits'] = $this->parseLimits($request);
        $validated['overage'] = $this->parseOverage($request);
        $validated['features'] = $this->parseFeatures($request);
        $validated['topups'] = $this->parseTopups($request);
        $validated['is_popular'] = $request->boolean('is_popular');
        $validated['is_active'] = $request->boolean('is_active');

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $pachete->update($validated);

        SyncPlanToStripe::dispatch($pachete->id);

        return redirect()->route('admin.plans.index')->with('success', "Pachetul \"{$pachete->name}\" a fost actualizat. Sincronizarea cu Stripe rulează în background.");
    }

    public function destroy(Plan $pachete)
    {
        $name = $pachete->name;
        $pachete->delete();

        return redirect()->route('admin.plans.index')->with('success', "Pachetul \"{$name}\" a fost șters.");
    }

    private function validatePlan(Request $request, ?Plan $plan = null): array
    {
        $slugRule = 'nullable|string|max:100|unique:plans,slug';
        if ($plan) {
            $slugRule .= ',' . $plan->id;
        }

        return $request->validate([
            'name' => 'required|string|max:100',
            'slug' => $slugRule,
            'type' => 'required|string|in:webchat,voice,bundle',
            'description' => 'nullable|string|max:500',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'is_popular' => 'nullable',
            'is_active' => 'nullable',
        ]);
    }

    private function parseLimits(Request $request): array
    {
        $limits = [];

        $limitKeys = ['bots', 'messages_per_month', 'knowledge_entries', 'products', 'minutes_per_month'];
        foreach ($limitKeys as $key) {
            $value = $request->input("limits.{$key}");
            if ($value !== null && $value !== '') {
                $limits[$key] = (int) $value;
            }
        }

        // Channels as array
        $channels = $request->input('limits.channels', []);
        if (!empty($channels)) {
            $limits['channels'] = array_values(array_filter($channels));
        }

        return $limits;
    }

    private function parseOverage(Request $request): array
    {
        $overage = [];

        $overageKeys = ['cost_per_message', 'cost_per_word', 'cost_per_minute'];
        foreach ($overageKeys as $key) {
            $value = $request->input("overage.{$key}");
            if ($value !== null && $value !== '') {
                $overage[$key] = (float) $value;
            }
        }

        return $overage;
    }

    private function parseFeatures(Request $request): array
    {
        $raw = $request->input('features_text', '');
        if (empty($raw)) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode("\n", $raw)),
            fn($line) => $line !== ''
        ));
    }

    /**
     * Repeater rows from form: topups[N][name|unit|quantity|price|is_active].
     * Drop empty rows; keep order.
     */
    private function parseTopups(Request $request): array
    {
        $rows = $request->input('topups', []);
        if (! is_array($rows)) {
            return [];
        }

        $allowedUnits = ['messages', 'minutes'];
        $out = [];

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $quantity = (int) ($row['quantity'] ?? 0);
            $price = (float) ($row['price'] ?? 0);
            $unit = (string) ($row['unit'] ?? 'messages');
            if (! in_array($unit, $allowedUnits, true)) {
                $unit = 'messages';
            }
            // Skip blank rows so admins can leave trailing empties.
            if ($name === '' || $quantity <= 0 || $price <= 0) {
                continue;
            }
            $out[] = [
                'name' => $name,
                'unit' => $unit,
                'quantity' => $quantity,
                'price' => round($price, 2),
                'is_active' => isset($row['is_active']) && $row['is_active'] !== '0',
            ];
        }

        return $out;
    }
}
