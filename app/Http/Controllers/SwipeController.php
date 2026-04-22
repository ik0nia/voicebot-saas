<?php

namespace App\Http\Controllers;

use App\Models\SocialPost;
use Illuminate\Http\Request;

/**
 * Tinder-style PWA for social-post review.
 *
 * One-card-at-a-time swipe UI over the existing draft queue. Swipe right
 * approves (cascades to the whole group), swipe left rejects, tap opens the
 * full admin edit screen. Separate from the admin index so it can be
 * installed as its own PWA icon ("Add to Home Screen").
 */
class SwipeController extends Controller
{
    public function home()
    {
        return view('swipe.app');
    }

    public function queue()
    {
        // One CARD per group (one idea cross-posted to FB + IG + Story). Picks the
        // FB post as the representative — approve/reject cascade to siblings anyway.
        // Skip groups whose representative doesn't have an image yet.
        $drafts = SocialPost::where('status', 'draft')
            ->whereNotNull('image_url')
            ->where('image_url', '!=', '')
            ->orderBy('created_at')
            ->get();

        $byGroup = $drafts->groupBy(fn (SocialPost $p) => $p->group_id ?? 'solo_' . $p->id);

        $cards = $byGroup->take(15)->map(function ($posts) {
            $rep = $posts->firstWhere(fn (SocialPost $p) => $p->platform === 'facebook' && $p->post_type === 'post')
                ?? $posts->first();
            $platforms = $posts
                ->map(fn (SocialPost $p) => $p->platform . '/' . $p->post_type)
                ->unique()
                ->values();
            return [
                'id' => $rep->id,
                'platforms' => $platforms,
                'group_size' => $posts->count(),
                'content' => $rep->content,
                'image_url' => $rep->image_url,
                'hashtags' => (array) ($rep->hashtags ?? []),
                'created_at' => optional($rep->created_at)->toIso8601String(),
                'group_id' => $rep->group_id,
                'post_ids' => $posts->pluck('id')->values(),
                'edit_url' => route('admin.social.edit', $rep),
            ];
        })->values();

        // Total = draft GROUPS with image (matches what the queue surfaces).
        $totalGroupsReady = SocialPost::where('status', 'draft')
            ->whereNotNull('image_url')
            ->where('image_url', '!=', '')
            ->distinct('group_id')
            ->count('group_id');

        $totalDraftGroups = SocialPost::where('status', 'draft')
            ->distinct('group_id')
            ->count('group_id');

        return response()->json([
            'posts' => $cards,
            'total' => $totalGroupsReady,
            'total_all_drafts' => $totalDraftGroups,
        ]);
    }

    public function approve(SocialPost $post)
    {
        // Approve = queue at the END of the existing schedule, NOT publish now.
        // Reviewer approved the idea, so we drop it at the next free day after
        // the last scheduled group. FB at 10:00+rand, IG +5 min, Story +2h.
        $targets = collect([$post])->concat($this->siblings($post));

        $lastScheduled = SocialPost::where('status', 'scheduled')->max('scheduled_at');
        $nextDay = $lastScheduled
            ? \Carbon\Carbon::parse($lastScheduled)->addDay()->setTime(10, 0, 0)
            : now()->addDay()->setTime(10, 0, 0);
        $heroSlot = $nextDay->copy()->addMinutes(mt_rand(0, 540)); // 10:00-19:00 random

        foreach ($targets as $t) {
            $slot = $heroSlot->copy();
            if ($t->post_type === 'story') {
                $slot = $heroSlot->copy()->addHours(2);
            } elseif ($t->platform === 'instagram') {
                $slot = $heroSlot->copy()->addMinutes(5);
            }
            $t->update([
                'status' => 'scheduled',
                'scheduled_at' => $slot,
            ]);
        }
        return response()->json([
            'ok' => true,
            'scheduled' => $targets->count(),
            'publish_at' => $heroSlot->toDateTimeString(),
        ]);
    }

    public function reject(SocialPost $post)
    {
        $targets = collect([$post])->concat($this->siblings($post));
        $deleted = 0;
        foreach ($targets as $t) {
            $t->delete();
            $deleted++;
        }
        return response()->json(['ok' => true, 'deleted' => $deleted]);
    }

    private function siblings(SocialPost $post)
    {
        if (!$post->group_id) return collect();
        return SocialPost::where('group_id', $post->group_id)
            ->where('id', '!=', $post->id)
            ->get();
    }
}
