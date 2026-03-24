<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\ClonedVoice;
use App\Models\KnowledgeConnector;
use Illuminate\Http\Request;

class AdminBotController extends Controller
{
    public function index(Request $request)
    {
        $query = Bot::withoutGlobalScopes()->with(['tenant', 'site'])->withCount('calls');

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $bots = $query->latest()->paginate(20);
        return view('admin.bots.index', compact('bots'));
    }

    public function show($botId)
    {
        $bot = Bot::withoutGlobalScopes()->with(['channels', 'phoneNumbers', 'site', 'tenant', 'clonedVoice'])->findOrFail($botId);
        $bot->loadCount('calls');

        $recentCalls = $bot->calls()->withoutGlobalScopes()->latest()->take(5)->get();
        $callsThisMonth = $bot->calls()->withoutGlobalScopes()->whereMonth('created_at', now()->month)->count();
        $avgDuration = $bot->calls()->withoutGlobalScopes()->where('status', 'completed')->avg('duration_seconds');

        $clonedVoice = ClonedVoice::withoutGlobalScopes()->where('tenant_id', $bot->tenant_id)->latest()->first();
        $apiTokens = collect(); // No API tokens in admin view
        $wcConnector = KnowledgeConnector::withoutGlobalScopes()->where('bot_id', $bot->id)->where('type', 'woocommerce')->first();
        $recentKnowledge = $bot->knowledge()->where('status', 'ready')->latest()->take(5)->get();

        // KB stats
        $knowledgeQuery = $bot->knowledge()->where('status', 'ready');
        $totalDocuments = (clone $knowledgeQuery)->distinct('title')->count('title');
        $totalChunks = (clone $knowledgeQuery)->count();
        $kbStats = [
            'total_documents' => $totalDocuments,
            'total_chunks' => $totalChunks,
            'has_faq' => (clone $knowledgeQuery)->where('title', 'like', '%FAQ%')->exists(),
            'has_products' => false,
            'has_policies' => false,
            'has_scan' => (clone $knowledgeQuery)->where('source_type', 'scan')->exists(),
            'has_connector' => (clone $knowledgeQuery)->where('source_type', 'connector')->exists(),
            'has_agent' => (clone $knowledgeQuery)->where('source_type', 'agent')->exists(),
            'has_five_documents' => $totalDocuments >= 5,
            'score' => count(array_filter([$totalDocuments > 0, (clone $knowledgeQuery)->where('source_type', 'agent')->exists(), (clone $knowledgeQuery)->where('title', 'like', '%FAQ%')->exists(), (clone $knowledgeQuery)->where('source_type', 'scan')->exists(), $totalDocuments >= 5])) * 20,
        ];

        return view('admin.bots.show', compact('bot', 'recentCalls', 'callsThisMonth', 'avgDuration', 'kbStats', 'clonedVoice', 'apiTokens', 'wcConnector', 'recentKnowledge'));
    }
}
