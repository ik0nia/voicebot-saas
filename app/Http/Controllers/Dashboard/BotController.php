<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BotController extends Controller
{
    public function index(Request $request)
    {
        $query = Bot::query()->withCount('calls');

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->get('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->get('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $bots = $query->latest()->paginate(12);

        return view('dashboard.bots.index', compact('bots'));
    }

    public function create()
    {
        return view('dashboard.bots.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'language' => 'required|string|in:ro,en,de,fr,es',
            'voice' => 'required|string|in:alloy,echo,fable,onyx,nova,shimmer',
            'system_prompt' => 'nullable|string|max:10000',
            'settings' => 'nullable|array',
        ]);

        $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(6);
        $validated['tenant_id'] = auth()->user()->tenant_id;
        $validated['settings'] = array_merge([
            'vad_threshold' => 0.5,
            'silence_duration_ms' => 500,
            'temperature' => 0.7,
            'max_tokens' => 1024,
        ], $validated['settings'] ?? []);

        $bot = Bot::create($validated);

        return redirect()->route('dashboard.bots.show', $bot)
            ->with('success', 'Botul a fost creat cu succes!');
    }

    public function show(Bot $bot)
    {
        $bot->loadCount('calls');
        $bot->load('channels', 'phoneNumbers');

        $recentCalls = $bot->calls()->latest()->take(5)->get();
        $callsThisMonth = $bot->calls()->whereMonth('created_at', now()->month)->count();
        $avgDuration = $bot->calls()->where('status', 'completed')->avg('duration_seconds');

        return view('dashboard.bots.show', compact('bot', 'recentCalls', 'callsThisMonth', 'avgDuration'));
    }

    public function edit(Bot $bot)
    {
        return view('dashboard.bots.edit', compact('bot'));
    }

    public function update(Request $request, Bot $bot)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'language' => 'required|string',
            'voice' => 'required|string',
            'system_prompt' => 'nullable|string|max:10000',
            'settings' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $bot->update($validated);

        return redirect()->route('dashboard.bots.show', $bot)
            ->with('success', 'Botul a fost actualizat!');
    }

    public function destroy(Bot $bot)
    {
        $bot->delete();
        return redirect()->route('dashboard.bots.index')
            ->with('success', 'Botul a fost șters.');
    }

    public function toggleActive(Bot $bot)
    {
        $bot->update(['is_active' => !$bot->is_active]);
        return back()->with('success', $bot->is_active ? 'Bot activat.' : 'Bot dezactivat.');
    }

    public function testVocal(Bot $bot)
    {
        return view('dashboard.bots.test-vocal', compact('bot'));
    }
}
