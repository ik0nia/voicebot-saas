<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChannelController extends Controller
{
    public function index(Bot $bot)
    {
        $channels = $bot->channels()->latest()->get();

        return view('dashboard.bots.channels.index', compact('bot', 'channels'));
    }

    public function store(Request $request, Bot $bot)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:' . implode(',', Channel::TYPES),
            'name' => 'nullable|string|max:255',
            'external_id' => 'nullable|string|max:255',
            'config' => 'nullable|array',
        ]);

        // Check plan limits for allowed channels
        $tenant = auth()->user()->tenant;
        $allowedChannels = $tenant->settings['allowed_channels'] ?? ['voice'];

        if (!in_array($validated['type'], $allowedChannels)) {
            return back()->withErrors(['type' => 'Planul tău nu include acest tip de canal. Fă upgrade pentru acces.']);
        }

        // Check if this channel type + external_id combo already exists
        $exists = $bot->channels()
            ->where('type', $validated['type'])
            ->where('external_id', $validated['external_id'] ?? null)
            ->exists();

        if ($exists) {
            return back()->withErrors(['type' => 'Acest canal există deja pentru acest bot.']);
        }

        $validated['webhook_secret'] = Str::random(32);
        $validated['status'] = 'pending';

        $channel = $bot->channels()->create($validated);

        return redirect()->route('dashboard.bots.channels.index', $bot)
            ->with('success', 'Canalul a fost adăugat cu succes!');
    }

    public function update(Request $request, Bot $bot, Channel $channel)
    {
        $this->ensureChannelBelongsToBot($bot, $channel);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'external_id' => 'nullable|string|max:255',
            'config' => 'nullable|array',
        ]);

        $channel->update($validated);

        return redirect()->route('dashboard.bots.channels.index', $bot)
            ->with('success', 'Canalul a fost actualizat!');
    }

    public function destroy(Bot $bot, Channel $channel)
    {
        $this->ensureChannelBelongsToBot($bot, $channel);

        $channel->delete();

        return redirect()->route('dashboard.bots.channels.index', $bot)
            ->with('success', 'Canalul a fost șters.');
    }

    public function toggleActive(Bot $bot, Channel $channel)
    {
        $this->ensureChannelBelongsToBot($bot, $channel);

        $channel->update(['is_active' => !$channel->is_active]);

        return back()->with('success', $channel->is_active ? 'Canal activat.' : 'Canal dezactivat.');
    }

    private function ensureChannelBelongsToBot(Bot $bot, Channel $channel): void
    {
        if ($channel->bot_id !== $bot->id) {
            abort(404);
        }
    }
}
