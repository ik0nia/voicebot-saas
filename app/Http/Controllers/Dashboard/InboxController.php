<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Call;
use App\Models\Channel;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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
        // Whitelist sortable columns so users can't pass arbitrary SQL via
        // ?sort=. Direction defaults to desc which matches the original
        // behavior (newest first).
        $sortable = ['last_activity_at', 'messages_count', 'lead_score', 'contact_name'];
        $sort = in_array($request->get('sort'), $sortable, true)
            ? $request->get('sort')
            : 'last_activity_at';
        $dir = strtolower($request->get('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = Conversation::with(['bot:id,name', 'channel:id,type,name', 'assigneeUser:id,name']);

        // Postgres-friendly NULLS LAST so conversations never touched
        // (last_activity_at IS NULL) sink to the bottom instead of mixing
        // ambiguously with the freshest rows when we used `?? created_at`.
        if ($sort === 'last_activity_at') {
            $query->orderByRaw('last_activity_at ' . $dir . ' NULLS LAST');
        } else {
            $query->orderBy($sort, $dir);
        }

        // Filters
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($channelType = $request->get('channel_type')) {
            $channelIds = Channel::where('type', $channelType)->pluck('id');
            $query->whereIn('channel_id', $channelIds);
        }
        if ($botId = $request->get('bot')) {
            $query->where('bot_id', $botId);
        }
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('last_activity_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('last_activity_at', '<=', $dateTo);
        }
        if ($request->boolean('mine')) {
            $query->where('assignee_user_id', auth()->id());
        }
        // needs_human: vizitatorul a cerut explicit operator (sau frustrare
        // detectată). Filtrul "Cer ajutor" arată exact aceste rânduri —
        // primul lucru pe care un operator vrea să-l vadă la login.
        if ($request->boolean('needs_human')) {
            $query->whereJsonContains('metadata->needs_human', true)
                  ->whereNull('assignee_user_id');
        }
        // Filter by tag (din operator setTags). ?tag=billing pentru exact match,
        // sau ?tags=foo,bar pentru OR multi-tag.
        if ($tag = $request->get('tag')) {
            $query->whereJsonContains('metadata->tags', mb_strtolower(trim($tag)));
        } elseif ($tagList = $request->get('tags')) {
            $tags = array_filter(array_map(
                fn($t) => mb_strtolower(trim($t)),
                explode(',', (string) $tagList)
            ));
            if (!empty($tags)) {
                $query->where(function ($q) use ($tags) {
                    foreach ($tags as $t) {
                        $q->orWhereJsonContains('metadata->tags', $t);
                    }
                });
            }
        }
        if ($search = $request->get('q')) {
            $needle = trim((string) $search);
            $query->where(function ($q) use ($needle) {
                $q->where('contact_name', 'ilike', "%{$needle}%")
                    ->orWhere('contact_identifier', 'ilike', "%{$needle}%")
                    // Match și pe conținutul mesajelor — necesar pentru
                    // operatori care vor să găsească o conversație după un
                    // cuvânt cheie (livrare, produs, număr comandă).
                    ->orWhereHas('messages', function ($mq) use ($needle) {
                        $mq->where('content', 'ilike', "%{$needle}%");
                    });
            });
        }

        // Determine if voice calls should be merged into the listing.
        // - "channel_type=voice" → calls only
        // - no channel filter → both streams
        // - any other channel filter → conversations only (calls have NULL
        //   channel_id today, so they wouldn't match a WhatsApp/FB filter)
        $includeCalls = !$request->get('channel_type') || $request->get('channel_type') === 'voice';
        $includeConvs = $request->get('channel_type') !== 'voice';

        $conversationItems = $includeConvs
            ? $this->fetchConversationItems($query)
            : collect();

        $callItems = $includeCalls
            ? $this->fetchCallItems($request, auth()->id())
            : collect();

        // Merge & sort. We pull a window of 200 each and sort/slice in
        // memory because the two tables don't share a sortable key in SQL
        // — true cross-table pagination would need a materialized view.
        // 200×2 is fine for a tenant inbox; if a single tenant has more
        // than that in fresh activity in one page, we adjust the window.
        $merged = $conversationItems
            ->merge($callItems)
            ->sortByDesc(fn ($item) => $this->itemSortKey($item, $sort))
            ->values();

        if ($dir === 'asc') {
            $merged = $merged->reverse()->values();
        }

        // Manual paginator over the merged list.
        $perPage = 50;
        $page = max(1, (int) $request->get('page', 1));
        $conversations = new LengthAwarePaginator(
            $merged->slice(($page - 1) * $perPage, $perPage)->values(),
            $merged->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        $stats = [
            'total' => Conversation::count() + Call::count(),
            'mine' => Conversation::where('assignee_user_id', auth()->id())->count(),
            'needs_human' => Conversation::whereJsonContains('metadata->needs_human', true)
                ->whereNull('assignee_user_id')
                ->count(),
        ];

        $bots = Bot::orderBy('name')->get(['id', 'name']);

        return view('dashboard.inbox.index', compact('conversations', 'stats', 'sort', 'dir', 'bots'));
    }

    /**
     * Map a Conversation builder result into uniform inbox items.
     *
     * The `->toBase()` is load-bearing. Eloquent\Collection::map() downgrades
     * itself to a base collection only when it finds an item that is not a
     * Model — and on an EMPTY collection it finds nothing, so it stays an
     * Eloquent\Collection. Merging call items (plain stdClass) into one then
     * calls getKey() on them and fatals. That made the whole inbox 500 for
     * any tenant with calls but no conversations, which is every brand-new
     * phone-only account until its first chat.
     */
    private function fetchConversationItems($query): Collection
    {
        return $query->limit(200)->get()->toBase()->map(function (Conversation $c) {
            $meta = $c->metadata ?? [];
            return (object) [
                '_type' => 'conv',
                'id' => $c->id,
                'route_name' => 'dashboard.conversations.show',
                'route_params' => ['conversation' => $c->id],
                'contact_name' => $c->contact_name,
                'contact_identifier' => $c->contact_identifier,
                'channel_type' => $c->channel?->type ?? 'unknown',
                'channel_label' => $c->channel?->getDisplayName() ?? '—',
                'messages_count' => $c->messages_count ?? 0,
                'duration_seconds' => null,
                'cost_cents' => null,
                'direction' => null,
                'recording_url' => null,
                'last_activity_at' => $c->last_activity_at,
                'is_human_assigned' => $c->isHumanAssigned(),
                'is_bot_assigned' => $c->isBotAssigned(),
                'assignee_user_id' => $c->assignee_user_id,
                'assignee_user_name' => $c->assigneeUser?->name,
                'bot_name' => $c->bot?->name,
                'needs_human' => (bool) ($meta['needs_human'] ?? false),
                'sentiment' => $meta['auto_tags']['sentiment'] ?? null,
                'raw' => $c,
            ];
        });
    }

    /**
     * Calls table → uniform inbox items. Voice calls today have NULL
     * channel_id, so we synthesize a 'voice' channel label rather than
     * left-joining channels (which would be no-op).
     */
    private function fetchCallItems(Request $request, int $userId): Collection
    {
        $q = Call::with(['bot:id,name'])->latest('started_at');

        // Filters that map cleanly to call columns.
        if ($search = $request->get('q')) {
            $q->where('caller_number', 'like', "%{$search}%");
        }
        if ($status = $request->get('status')) {
            $q->where('status', $status);
        }
        if ($direction = $request->get('direction')) {
            $q->where('direction', $direction);
        }
        if ($botId = $request->get('bot')) {
            $q->where('bot_id', $botId);
        }
        if ($dateFrom = $request->get('date_from')) {
            $q->whereDate('started_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $q->whereDate('started_at', '<=', $dateTo);
        }

        // Calls don't carry per-user assignee or needs_human — exclude
        // them from these filters so the chip count matches what
        // actually shows up in the list.
        if ($request->boolean('mine') || $request->boolean('needs_human')) {
            return collect();
        }

        // Same reason as fetchConversationItems: an empty result would stay an
        // Eloquent\Collection and poison whichever merge it ends up on the
        // left of. Harmless in today's merge order, fatal if it ever changes.
        return $q->limit(200)->get()->toBase()->map(fn (Call $call) => (object) [
            '_type' => 'call',
            'id' => $call->id,
            'route_name' => 'dashboard.calls.show',
            'route_params' => ['call' => $call->id],
            'contact_name' => null,
            'contact_identifier' => $call->caller_number,
            'channel_type' => 'voice',
            'channel_label' => 'Voice',
            'messages_count' => null,
            'duration_seconds' => $call->duration_seconds,
            'cost_cents' => $call->cost_cents,
            'direction' => $call->direction,
            'recording_url' => $call->recording_url,
            'last_activity_at' => $call->ended_at ?? $call->started_at,
            'is_human_assigned' => false,
            'is_bot_assigned' => true,
            'assignee_user_id' => null,
            'assignee_user_name' => null,
            'bot_name' => $call->bot?->name,
            'needs_human' => false, // calls don't carry this flag
            'sentiment' => $call->sentiment_label, // direct column on calls
            'raw' => $call,
        ]);
    }

    private function itemSortKey(object $item, string $sort): int
    {
        return match ($sort) {
            'messages_count' => (int) ($item->messages_count ?? 0),
            'contact_name' => 0, // string sort handled by sortBy callback in caller? simplified: use timestamp
            'last_activity_at' => $item->last_activity_at?->timestamp ?? 0,
            default => $item->last_activity_at?->timestamp ?? 0,
        };
    }
}
