<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\PhoneNumber;
use App\Models\Bot;
use Illuminate\Http\Request;

class PhoneNumberController extends Controller
{
    public function index()
    {
        $numbers = PhoneNumber::with('bot')->latest()->paginate(20);
        $bots = Bot::where('is_active', true)->orderBy('name')->get();

        return view('dashboard.numbers.index', compact('numbers', 'bots'));
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
        $validated['is_active'] = true;
        $validated['monthly_cost_cents'] = $request->get('monthly_cost_cents', 100); // 1 EUR default

        PhoneNumber::create($validated);

        return back()->with('success', 'Numărul a fost adăugat.');
    }

    public function update(Request $request, PhoneNumber $phoneNumber)
    {
        $validated = $request->validate([
            'bot_id' => 'nullable|exists:bots,id',
            'friendly_name' => 'nullable|string|max:255',
        ]);

        $phoneNumber->update($validated);

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
}
