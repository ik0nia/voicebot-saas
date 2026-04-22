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
        $posts = SocialPost::where('status', 'draft')
            ->orderBy('created_at')
            ->limit(15)
            ->get()
            ->map(function (SocialPost $p) {
                return [
                    'id' => $p->id,
                    'platform' => $p->platform,
                    'post_type' => $p->post_type,
                    'content' => $p->content,
                    'image_url' => $p->image_url,
                    'hashtags' => (array) ($p->hashtags ?? []),
                    'created_at' => optional($p->created_at)->toIso8601String(),
                    'group_id' => $p->group_id,
                    'siblings_count' => $p->group_id
                        ? SocialPost::where('group_id', $p->group_id)->where('id', '!=', $p->id)->count()
                        : 0,
                    'edit_url' => route('admin.social.edit', $p),
                ];
            });

        return response()->json([
            'posts' => $posts,
            'total' => SocialPost::where('status', 'draft')->count(),
        ]);
    }

    public function approve(SocialPost $post)
    {
        $targets = collect([$post])->concat($this->siblings($post));
        foreach ($targets as $t) {
            $t->update([
                'status' => 'scheduled',
                'scheduled_at' => $t->scheduled_at ?: now()->addMinutes(5),
            ]);
            dispatch(new \App\Jobs\AutoPublishSocialPost($t->id));
        }
        return response()->json(['ok' => true, 'scheduled' => $targets->count()]);
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
