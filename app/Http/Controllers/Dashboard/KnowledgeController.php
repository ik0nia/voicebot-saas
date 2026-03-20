<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\BotKnowledge;
use App\Jobs\ProcessKnowledgeDocument;
use Illuminate\Http\Request;

class KnowledgeController extends Controller
{
    public function index(Bot $bot)
    {
        $documents = $bot->knowledge()
            ->selectRaw('title, type, status, MIN(id) as id, COUNT(*) as chunks_count, MIN(created_at) as created_at')
            ->groupBy('title', 'type', 'status')
            ->orderByDesc('created_at')
            ->get();

        return view('dashboard.bots.knowledge.index', compact('bot', 'documents'));
    }

    public function store(Request $request, Bot $bot)
    {
        $validated = $request->validate([
            'type' => 'required|in:pdf,url,text',
            'title' => 'required|string|max:255',
            'content' => 'required_if:type,text|nullable|string',
            'url' => 'required_if:type,url|nullable|url',
            'file' => 'required_if:type,pdf|nullable|file|mimes:pdf|max:10240',
        ]);

        $content = $validated['content'] ?? '';

        if ($validated['type'] === 'pdf' && $request->hasFile('file')) {
            $content = $request->file('file')->store('knowledge', 'local');
        } elseif ($validated['type'] === 'url') {
            $content = $validated['url'];
        }

        $knowledge = BotKnowledge::create([
            'bot_id' => $bot->id,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'content' => $content,
            'status' => 'pending',
        ]);

        ProcessKnowledgeDocument::dispatch($knowledge);

        return back()->with('success', 'Documentul a fost adăugat și se procesează.');
    }

    public function destroy(Bot $bot, $title)
    {
        $bot->knowledge()->where('title', $title)->delete();
        return back()->with('success', 'Documentul a fost șters.');
    }
}
