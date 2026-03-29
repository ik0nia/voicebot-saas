<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ChatEvent;
use App\Models\Conversation;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    public function index(Request $request)
    {
        $tenant = auth()->user()->currentTenant();
        $query = Conversation::where('tenant_id', $tenant->id)
            ->where('is_opportunity', true)
            ->with('bot')
            ->orderByDesc('opportunity_score');

        if ($botId = $request->input('bot_id')) $query->where('bot_id', $botId);
        if ($from = $request->input('from')) $query->where('created_at', '>=', $from);
        if ($to = $request->input('to')) $query->where('created_at', '<=', $to . ' 23:59:59');
        if ($minScore = $request->input('min_score')) $query->where('opportunity_score', '>=', (int) $minScore);

        $opportunities = $query->paginate(25);
        $bots = $tenant->bots()->select('id', 'name')->get();
        $stats = [
            'total' => Conversation::where('tenant_id', $tenant->id)->where('is_opportunity', true)->count(),
            'avg_score' => (int) Conversation::where('tenant_id', $tenant->id)->where('is_opportunity', true)->avg('opportunity_score'),
            'with_clicks' => ChatEvent::whereIn('conversation_id',
                Conversation::where('tenant_id', $tenant->id)->where('is_opportunity', true)->pluck('id')
            )->where('event_name', 'product_click')->distinct('conversation_id')->count('conversation_id'),
        ];

        return view('dashboard.opportunities.index', compact('opportunities', 'bots', 'stats'));
    }

    public function show(Conversation $conversation)
    {
        if (!auth()->user()->hasRole('super_admin') && $conversation->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $conversation->load('bot', 'messages');
        $events = ChatEvent::where('conversation_id', $conversation->id)->orderBy('occurred_at')->get();

        $productEvents = $events->whereIn('event_name', ['product_impression', 'product_click', 'add_to_cart_success', 'add_to_cart_failure']);
        $dropOffEvent = $events->last();

        return view('dashboard.opportunities.show', compact('conversation', 'events', 'productEvents', 'dropOffEvent'));
    }
}
