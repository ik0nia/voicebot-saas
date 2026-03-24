<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;

class AdminConversationController extends Controller
{
    public function index(Request $request)
    {
        $query = Conversation::withoutGlobalScopes()
            ->with(['bot', 'tenant'])
            ->withSum('messages as real_cost_cents', 'cost_cents')
            ->latest();

        if ($tenantId = $request->get('tenant')) {
            $query->where('tenant_id', $tenantId);
        }
        if ($botId = $request->get('bot')) {
            $query->where('bot_id', $botId);
        }

        $conversations = $query->paginate(20)->withQueryString();
        $tenants = \App\Models\Tenant::orderBy('name')->get();
        return view('admin.conversations.index', compact('conversations', 'tenants'));
    }

    public function show($conversationId)
    {
        $conversation = Conversation::withoutGlobalScopes()
            ->with(['bot', 'tenant'])
            ->withSum('messages as real_cost_cents', 'cost_cents')
            ->findOrFail($conversationId);
        $messages = Message::where('conversation_id', $conversation->id)->orderBy('created_at')->get();
        return view('admin.conversations.show', compact('conversation', 'messages'));
    }
}
