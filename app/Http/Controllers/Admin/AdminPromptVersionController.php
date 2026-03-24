<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\BotPromptVersion;
use Illuminate\Http\Request;

class AdminPromptVersionController extends Controller
{
    public function index($botId)
    {
        $bot = Bot::withoutGlobalScopes()->findOrFail($botId);
        $versions = BotPromptVersion::where('bot_id', $botId)
            ->orderByDesc('is_active')
            ->orderByDesc('weight')
            ->get();

        $totalWeight = $versions->where('is_active', true)->sum('weight');

        return view('admin.prompt-versions.index', compact('bot', 'versions', 'totalWeight'));
    }

    public function store(Request $request, $botId)
    {
        $bot = Bot::withoutGlobalScopes()->findOrFail($botId);

        $validated = $request->validate([
            'version' => 'required|string|max:100',
            'system_prompt' => 'required|string|max:10000',
            'personality' => 'nullable|string|max:500',
            'weight' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean',
        ]);

        $validated['bot_id'] = $bot->id;
        $validated['is_active'] = $request->boolean('is_active', true);

        BotPromptVersion::create($validated);

        return back()->with('success', "Versiunea \"{$validated['version']}\" a fost creată.");
    }

    public function update(Request $request, BotPromptVersion $version)
    {
        $validated = $request->validate([
            'version' => 'sometimes|string|max:100',
            'system_prompt' => 'sometimes|string|max:10000',
            'personality' => 'nullable|string|max:500',
            'weight' => 'sometimes|integer|min:1|max:100',
            'is_active' => 'boolean',
        ]);

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $version->update($validated);

        return back()->with('success', "Versiunea \"{$version->version}\" a fost actualizată.");
    }

    public function destroy(BotPromptVersion $version)
    {
        $name = $version->version;
        $botId = $version->bot_id;
        $version->delete();

        return redirect()->route('admin.prompt-versions.index', $botId)
            ->with('success', "Versiunea \"{$name}\" a fost ștearsă.");
    }
}
