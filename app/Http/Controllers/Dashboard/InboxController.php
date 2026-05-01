<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Conversation;
use Illuminate\Http\Request;

/**
 * Unified inbox across all channels (Etapa 6 of the omnichannel roadmap).
 *
 * Single page that shows every conversation a tenant has — voice + WA +
 * FB + IG + web widget — with assignee badge, status filter, and inline
 * take-over / hand-back actions. The previous per-channel-type
 * /dashboard/conversatii/{type} pages still exist for the channel-narrow
 * views; this is the collapsed all-in-one for operators triaging across
 * channels.
 */
class InboxController extends Controller
{
    public function index(Request $request)
    {
        $query = Conversation::with(['bot:id,name', 'channel:id,type,name', 'assigneeUser:id,name'])
            ->latest('last_activity_at');

        // Filters
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($channelType = $request->get('channel_type')) {
            $channelIds = Channel::where('type', $channelType)->pluck('id');
            $query->whereIn('channel_id', $channelIds);
        }
        if ($request->boolean('mine')) {
            $query->where('assignee_user_id', auth()->id());
        }
        if ($request->boolean('bot_only')) {
            $query->whereNotNull('assignee_bot_id');
        }
        if ($request->boolean('unassigned')) {
            $query->whereNull('assignee_user_id')->whereNull('assignee_bot_id');
        }
        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('contact_name', 'like', "%{$search}%")
                    ->orWhere('contact_identifier', 'like', "%{$search}%");
            });
        }

        $conversations = $query->paginate(50)->withQueryString();

        $stats = [
            'total' => Conversation::count(),
            'mine' => Conversation::where('assignee_user_id', auth()->id())->count(),
            'unassigned' => Conversation::whereNull('assignee_user_id')->whereNull('assignee_bot_id')->count(),
            'bot' => Conversation::whereNotNull('assignee_bot_id')->count(),
        ];

        return view('dashboard.inbox.index', compact('conversations', 'stats'));
    }
}
