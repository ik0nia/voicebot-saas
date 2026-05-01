<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Channel;
use App\Models\Conversation;
use App\Services\Channels\AgentAssignmentService;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function __construct(private ?AgentAssignmentService $assignments = null) {}

    public function index(Request $request, string $channelType)
    {
        $validTypes = [
            Channel::TYPE_WEB_CHATBOT,
            Channel::TYPE_WHATSAPP,
            Channel::TYPE_FACEBOOK_MESSENGER,
            Channel::TYPE_INSTAGRAM_DM,
        ];

        if (!in_array($channelType, $validTypes)) {
            abort(404);
        }

        $channelIds = Channel::where('type', $channelType)->pluck('id');

        $query = Conversation::with('bot', 'channel')
            ->whereIn('channel_id', $channelIds)
            ->latest();

        if ($botId = $request->get('bot')) {
            $query->where('bot_id', $botId);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('contact_name', 'like', "%{$search}%")
                  ->orWhere('contact_identifier', 'like', "%{$search}%");
            });
        }
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $conversations = $query->paginate(20)->withQueryString();
        $bots = Bot::orderBy('name')->get();

        $channelLabel = match ($channelType) {
            Channel::TYPE_WEB_CHATBOT => 'Web Chatbot',
            Channel::TYPE_WHATSAPP => 'WhatsApp',
            Channel::TYPE_FACEBOOK_MESSENGER => 'Facebook Messenger',
            Channel::TYPE_INSTAGRAM_DM => 'Instagram DM',
            default => ucfirst($channelType),
        };

        return view('dashboard.conversations.index', compact(
            'conversations', 'bots', 'channelType', 'channelLabel'
        ));
    }

    public function show(Conversation $conversation)
    {
        $conversation->load('bot', 'channel', 'messages');
        $conversation->loadSum('messages as real_cost_cents', 'cost_cents');
        $messages = $conversation->messages()->orderBy('created_at')->get();

        return view('dashboard.conversations.show', compact('conversation', 'messages'));
    }

    public function destroy(Conversation $conversation)
    {
        $conversation->delete();
        $channelType = $conversation->channel?->type ?? Channel::TYPE_WEB_CHATBOT;

        return redirect()->route('dashboard.conversations.index', ['channelType' => $channelType])
            ->with('success', 'Conversația a fost ștearsă.');
    }

    /**
     * Take over the conversation from the bot — switches assignment to the
     * authenticated user. Subsequent inbound messages will NOT be answered
     * by the bot until handBack() runs.
     */
    public function takeOver(Conversation $conversation)
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['tenant_admin', 'tenant_manager'])) {
            abort(403, 'Doar tenant_admin / tenant_manager pot prelua conversații.');
        }

        try {
            $this->assignments()->takeOver($conversation, $user);
        } catch (\DomainException $e) {
            return back()->withErrors(['assignment' => $e->getMessage()]);
        }

        return back()->with('success', 'Ai preluat conversația. Botul nu mai răspunde până apeși „Înapoi la bot".');
    }

    /**
     * Hand the conversation back to the channel's bot. Restores the default
     * bot from the channel, NOT necessarily the original assignee_bot_id —
     * the channel may have been re-bound to a different bot since.
     */
    public function handBack(Conversation $conversation)
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['tenant_admin', 'tenant_manager'])) {
            abort(403);
        }

        try {
            $this->assignments()->handBack($conversation, $user);
        } catch (\DomainException $e) {
            return back()->withErrors(['assignment' => $e->getMessage()]);
        }

        return back()->with('success', 'Conversația e din nou la bot.');
    }

    private function assignments(): AgentAssignmentService
    {
        return $this->assignments ??= app(AgentAssignmentService::class);
    }
}
