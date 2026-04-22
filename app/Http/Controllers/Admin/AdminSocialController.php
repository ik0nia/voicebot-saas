<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocialPost;
use App\Models\SocialPostVariant;
use App\Models\SocialAccount;
use App\Models\SocialSchedule;
use App\Models\SocialStylePreference;
use App\Models\SocialRejection;
use App\Services\Social\GeminiContentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use OpenAI\Laravel\Facades\OpenAI;

class AdminSocialController extends Controller
{
    // Dashboard with overview
    public function index(Request $request)
    {
        $query = SocialPost::query()->with('socialAccount');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($platform = $request->get('platform')) {
            $query->where('platform', $platform);
        }
        if ($q = trim((string) $request->get('q', ''))) {
            $query->where(function ($w) use ($q) {
                $w->where('content', 'ILIKE', "%{$q}%")
                  ->orWhere('image_prompt', 'ILIKE', "%{$q}%");
            });
        }
        if ($from = $request->get('from')) {
            $query->where(function ($w) use ($from) {
                $w->where('scheduled_at', '>=', $from)
                  ->orWhere('published_at', '>=', $from);
            });
        }
        if ($to = $request->get('to')) {
            $query->where(function ($w) use ($to) {
                $w->where('scheduled_at', '<=', $to)
                  ->orWhere('published_at', '<=', $to);
            });
        }

        // Deterministic ordering: "active" bucket (draft, scheduled, failed) first
        // with nearest scheduled_at at top, then the rest by newest published/created.
        $activeStatuses = ['scheduled', 'draft', 'failed', 'publishing'];

        // Show 1 row per GROUP (not per post). For each group_id pick the FB
        // feed post as representative; for legacy ungrouped posts pick themselves.
        $query->whereRaw("social_posts.id IN (
            SELECT DISTINCT ON (COALESCE(group_id, id)) id
            FROM social_posts
            ORDER BY COALESCE(group_id, id),
                     CASE WHEN platform = 'facebook' AND post_type = 'post' THEN 0
                          WHEN post_type = 'post' THEN 1
                          ELSE 2 END,
                     id
        )");

        $query->orderByRaw("CASE WHEN status IN ('scheduled','draft','failed','publishing') THEN 0 ELSE 1 END")
              ->orderByRaw('COALESCE(scheduled_at, published_at, created_at) ASC NULLS LAST')
              ->orderByDesc('id');

        $posts = $query->paginate(50)->withQueryString();

        // Attach group siblings info to each representative post for the view
        $groupIds = $posts->getCollection()->pluck('group_id')->filter()->unique()->all();
        $siblingInfo = $groupIds
            ? SocialPost::whereIn('group_id', $groupIds)
                ->get()
                ->groupBy('group_id')
                ->map(function ($siblings) {
                    $platforms = $siblings->pluck('platform')->unique()->all();
                    $hasStory = $siblings->contains(fn($p) => $p->post_type === 'story');
                    $parts = [];
                    if (in_array('facebook', $platforms)) $parts[] = 'FB';
                    if (in_array('instagram', $platforms)) $parts[] = 'IG';
                    if ($hasStory) $parts[] = 'Story';
                    return implode('+', $parts) ?: 'FB';
                })
            : collect();
        $posts->getCollection()->transform(function ($post) use ($siblingInfo) {
            $post->fanout_label = $post->group_id
                ? ($siblingInfo[$post->group_id] ?? strtoupper(substr($post->platform, 0, 2)))
                : strtoupper(substr($post->platform, 0, 2));
            return $post;
        });

        // Single-query stats via GROUP BY
        $counts = SocialPost::query()
            ->select('status', DB::raw('count(*) as c'))
            ->groupBy('status')
            ->pluck('c', 'status');
        $stats = [
            'total_posts' => (int) $counts->sum(),
            'draft' => (int) ($counts['draft'] ?? 0),
            'scheduled' => (int) ($counts['scheduled'] ?? 0),
            'published' => (int) ($counts['published'] ?? 0),
            'failed' => (int) ($counts['failed'] ?? 0),
            'today' => (int) SocialPost::whereDate('published_at', today())->count(),
        ];

        $accounts = SocialAccount::all();
        $schedules = SocialSchedule::all();

        // Buffer horizon: how far ahead are scheduled posts queued up?
        $lastScheduled = SocialPost::where('status', 'scheduled')->max('scheduled_at');
        $bufferUntil = $lastScheduled ? \Carbon\Carbon::parse($lastScheduled) : null;
        $bufferDaysLeft = $bufferUntil ? max(0, now()->startOfDay()->diffInDays($bufferUntil->startOfDay(), false)) : 0;

        // Card aggregations — counted as GROUPS (one idea = one post regardless
        // of how many platforms it fans out to). Posts with NULL group_id are
        // legacy and each counts as its own group.
        $countAsGroups = function ($baseQuery) {
            $base = clone $baseQuery;
            $grouped = (int) (clone $base)->whereNotNull('group_id')->distinct('group_id')->count('group_id');
            $solo = (int) (clone $base)->whereNull('group_id')->count();
            return $grouped + $solo;
        };

        $scheduledGroups = $countAsGroups(SocialPost::query()->where('status', 'scheduled'));
        $draftGroups = $countAsGroups(SocialPost::query()->where('status', 'draft'));
        $draftBufferTarget = 10;

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $publishedThisMonth = $countAsGroups(SocialPost::query()
            ->where('status', 'published')
            ->whereBetween('published_at', [$monthStart, $monthEnd]));
        $scheduledThisMonth = $countAsGroups(SocialPost::query()
            ->where('status', 'scheduled')
            ->whereBetween('scheduled_at', [now(), $monthEnd]));

        // Mobile swipe deck: 1 card per group (feed post, FB preferred).
        // We trust image_url and don't check is_file() because the queue
        // worker container writes images to its own public/ folder which
        // the app container can't see. Filtering by is_file() here would
        // empty the deck even when images are perfectly valid.
        // Only show posts with Vertex-generated images (img_ prefix) in the
        // review deck. OpenAI fallback images are lower quality and will be
        // regenerated — hide them until Vertex version is ready.
        $groupedDeck = SocialPost::query()
            ->where('status', 'draft')
            ->where('post_type', 'post')
            ->whereNotNull('image_url')
            ->where('image_url', 'NOT LIKE', '%openai_%')
            ->whereNotNull('group_id')
            ->orderByRaw("CASE WHEN platform = 'facebook' THEN 0 ELSE 1 END")
            ->orderBy('id', 'asc')
            ->get()
            ->unique('group_id')
            ->take(12);

        $legacyDeck = SocialPost::query()
            ->where('status', 'draft')
            ->whereNull('group_id')
            ->whereNotNull('image_url')
            ->where('image_url', 'NOT LIKE', '%openai_%')
            ->orderBy('id', 'asc')
            ->limit(12 - $groupedDeck->count())
            ->get();

        $deck = $groupedDeck->concat($legacyDeck)->values();

        // Attach a `fanout_label` attribute for the view ("FB+IG" or "FB+IG+Story")
        $groupIds = $deck->pluck('group_id')->filter()->unique()->all();
        $siblings = $groupIds
            ? SocialPost::whereIn('group_id', $groupIds)->get()->groupBy('group_id')
            : collect();
        $deck = $deck->map(function ($p) use ($siblings) {
            if (!$p->group_id) {
                $p->fanout_label = strtoupper(substr($p->platform, 0, 2));
                return $p;
            }
            $group = $siblings[$p->group_id] ?? collect();
            $platforms = $group->pluck('platform')->unique()->all();
            $hasStory = $group->contains(fn($q) => $q->post_type === 'story');
            $parts = [];
            if (in_array('facebook', $platforms)) $parts[] = 'FB';
            if (in_array('instagram', $platforms)) $parts[] = 'IG';
            if ($hasStory) $parts[] = 'Story';
            $p->fanout_label = implode('+', $parts) ?: 'FB';
            return $p;
        });

        return view('admin.social.index', compact(
            'posts', 'accounts', 'schedules', 'stats', 'deck',
            'bufferUntil', 'bufferDaysLeft',
            'scheduledGroups', 'publishedThisMonth', 'scheduledThisMonth',
            'draftGroups', 'draftBufferTarget'
        ));
    }

    // Generate a new post with Gemini
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'platform' => 'required|in:facebook,instagram,blog',
            'topic' => 'required|string|max:500',
        ]);

        $gemini = app(GeminiContentService::class);
        $schedule = SocialSchedule::where('platform', $validated['platform'])->first();

        if ($validated['platform'] === 'blog') {
            $result = $gemini->generateBlogArticle($validated['topic'], $schedule?->style_guidelines ?? []);
            $content = $result['content'] ?? '';
            $metadata = ['title' => $result['title'] ?? '', 'meta_description' => $result['meta_description'] ?? ''];
        } else {
            $result = $gemini->generatePostWithImage($validated['platform'], $validated['topic'], $schedule?->style_guidelines ?? []);
            $content = $result['content'] ?? '';
            $metadata = ['topic' => $validated['topic'], 'image_path' => $result['image_path'] ?? null];
        }

        $post = SocialPost::create([
            'social_account_id' => SocialAccount::where('platform', $validated['platform'])->first()?->id,
            'platform' => $validated['platform'],
            'status' => 'draft',
            'post_type' => $validated['platform'] === 'blog' ? 'blog_article' : 'post',
            'content' => $content,
            'hashtags' => $result['hashtags'] ?? $result['tags'] ?? [],
            'image_url' => $result['image_url'] ?? null,
            'image_prompt' => $result['image_prompt'] ?? null,
            'metadata' => $metadata + ['model' => $result['model'] ?? 'gemini'],
            'ai_tokens_used' => $result['tokens_used'] ?? 0,
        ]);

        return redirect()->route('admin.social.index', ['post' => $post->id])->with('success', 'Post generat cu succes!');
    }

    // Return single post as JSON for the side panel viewer
    public function edit(SocialPost $post)
    {
        $post->load([
            'variants' => fn($q) => $q->orderByDesc('id')->limit(10),
            'rejections' => fn($q) => $q->orderByDesc('id')->limit(5),
        ]);

        $fanout = [];
        if ($post->group_id) {
            $fanout = SocialPost::where('group_id', $post->group_id)
                ->orderBy('id')
                ->get(['id', 'platform', 'post_type', 'status']);
        }
        return view('admin.social.edit', compact('post', 'fanout'));
    }

    public function show(SocialPost $post)
    {
        $post->load(['variants' => fn($q) => $q->orderByDesc('id')->limit(10), 'rejections' => fn($q) => $q->orderByDesc('id')->limit(5)]);

        $fanout = [];
        if ($post->group_id) {
            $fanout = SocialPost::where('group_id', $post->group_id)
                ->orderBy('id')
                ->get()
                ->map(fn($s) => [
                    'id' => $s->id,
                    'platform' => $s->platform,
                    'post_type' => $s->post_type,
                    'status' => $s->status,
                    'is_self' => $s->id === $post->id,
                ])->values()->all();
        }

        return response()->json([
            'id' => $post->id,
            'platform' => $post->platform,
            'post_type' => $post->post_type,
            'status' => $post->status,
            'content' => $post->content,
            'hashtags' => $post->hashtags ?? [],
            'image_url' => $post->image_url,
            'image_prompt' => $post->image_prompt,
            'metadata' => $post->metadata ?? [],
            'scheduled_at' => $post->scheduled_at?->format('Y-m-d\TH:i'),
            'published_at' => $post->published_at?->format('Y-m-d\TH:i'),
            'external_url' => $post->external_url,
            'error_message' => $post->error_message,
            'regen_count' => (int) ($post->regen_count ?? 0),
            'updated_at' => $post->updated_at?->toIso8601String(),
            'is_editable' => in_array($post->status, ['draft', 'scheduled', 'failed']),
            'group_id' => $post->group_id,
            'fanout' => $fanout,
            'variants' => $post->variants->map(fn($v) => [
                'id' => $v->id,
                'kind' => $v->kind,
                'content' => $v->content,
                'image_url' => $v->image_url,
                'image_prompt' => $v->image_prompt,
                'created_at' => $v->created_at?->diffForHumans(),
            ])->values(),
            'rejections' => $post->rejections->map(fn($r) => [
                'reason_category' => $r->reason_category,
                'feedback' => $r->feedback,
                'created_at' => $r->created_at?->diffForHumans(),
            ])->values(),
        ]);
    }

    /**
     * Find next free slot for a given platform, starting from `$from`.
     * Walks posting_times day-by-day and avoids 30-min collisions with
     * other scheduled/publishing posts on the same platform.
     */
    private function findNextSlotForPlatform(string $platform, ?\Carbon\Carbon $from = null): ?\Carbon\Carbon
    {
        $maxPerDay = (int) (SocialSchedule::where('platform', 'facebook')->value('posts_per_day') ?: 3);
        $hours = range(9, 18);
        $start = $from ?? now();

        for ($day = 0; $day < 180; $day++) {
            $date = $start->copy()->addDays($day);
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();

            // Count how many groups are already scheduled on this day
            $groupsOnDay = SocialPost::whereIn('status', ['scheduled', 'publishing'])
                ->whereBetween('scheduled_at', [$dayStart, $dayEnd])
                ->whereNotNull('group_id')
                ->distinct('group_id')
                ->count('group_id');
            $soloOnDay = SocialPost::whereIn('status', ['scheduled', 'publishing'])
                ->whereBetween('scheduled_at', [$dayStart, $dayEnd])
                ->whereNull('group_id')
                ->count();
            $totalGroupsOnDay = $groupsOnDay + $soloOnDay;

            // Skip this day if already at the limit
            if ($totalGroupsOnDay >= $maxPerDay) continue;

            $shuffled = $hours;
            shuffle($shuffled);
            foreach ($shuffled as $h) {
                $slot = $date->copy()->setTime((int) $h, 0, 0);
                if ($slot->lte($start)) continue;
                $collision = SocialPost::where('platform', $platform)
                    ->whereIn('status', ['scheduled', 'publishing'])
                    ->whereBetween('scheduled_at', [$slot->copy()->subMinutes(30), $slot->copy()->addMinutes(30)])
                    ->exists();
                if (!$collision) return $slot;
            }
        }
        return null;
    }

    // Partial JSON update with optimistic concurrency via updated_at.
    public function patch(Request $request, SocialPost $post)
    {
        $validated = $request->validate([
            'content' => 'nullable|string|max:10000',
            'scheduled_at' => 'nullable|date',
            'status' => 'nullable|in:draft,scheduled',
            'post_type' => 'nullable|in:post,story,reel,blog_article',
            'updated_at' => 'nullable|string',
        ]);

        if (!empty($validated['updated_at']) && $post->updated_at?->toIso8601String() !== $validated['updated_at']) {
            return response()->json([
                'error' => 'Postarea a fost modificată între timp. Reîncarcă.',
                'current_updated_at' => $post->updated_at?->toIso8601String(),
            ], 409);
        }

        $dirty = [];
        foreach (['content', 'scheduled_at', 'status', 'post_type'] as $k) {
            if (array_key_exists($k, $validated) && $validated[$k] !== null) {
                $dirty[$k] = $validated[$k];
            }
        }
        if ($dirty) {
            $post->update($dirty);

            // Cascade text edits to feed siblings (FB<->IG keep in sync).
            // We intentionally DO NOT cascade scheduled_at (each platform has
            // its own slot) or post_type (story stays story).
            if (array_key_exists('content', $dirty)) {
                foreach ($post->feedSiblings() as $sibling) {
                    $sibling->update(['content' => $dirty['content']]);
                }
            }
        }

        return response()->json([
            'ok' => true,
            'updated_at' => $post->fresh()->updated_at->toIso8601String(),
        ]);
    }

    // Regenerate the image for a post. Stores result as a variant AND promotes it to active
    // so the UI can also show recent variants and let the reviewer switch back.
    public function regenerateImage(Request $request, SocialPost $post)
    {
        if (!in_array($post->status, ['draft', 'scheduled', 'failed'])) {
            return response()->json(['error' => 'Postarea nu mai poate fi modificată.'], 422);
        }

        $promptOverride = trim((string) $request->input('prompt', ''));
        $patternOverride = trim((string) $request->input('pattern', ''));
        $aspect = $post->post_type === 'story' ? '9:16' : '4:5';

        $catalog = app(\App\Services\Social\Patterns\PatternCatalog::class);
        $resolver = app(\App\Services\Social\NicheResolver::class);
        $orchestrator = app(\App\Services\Social\SocialImageOrchestrator::class);

        $pattern = $patternOverride !== '' && $catalog->exists($patternOverride)
            ? $patternOverride
            : ($catalog->pickWeighted() ?: 'flat_illustration_icons');

        $resolved = $resolver->resolve($post->metadata ?? []);
        $keyMessage = $promptOverride !== ''
            ? $promptOverride
            : ($resolved['key_message'] ?? $post->image_prompt ?? ($post->metadata['topic'] ?? null));

        $image = $orchestrator->generate($pattern, $resolved['niche'], [
            'key_message' => $keyMessage,
            'aspect_override' => $aspect,
        ]);
        $prompt = 'pattern:' . $pattern . '|niche:' . $resolved['niche'] . '|msg:' . mb_substr((string) $keyMessage, 0, 180);

        if (!$image || empty($image['url'])) {
            return response()->json(['error' => 'Generarea imaginii a eșuat.'], 500);
        }

        // Snapshot previous image as an inactive variant before overwriting.
        if ($post->image_url) {
            SocialPostVariant::create([
                'social_post_id' => $post->id,
                'kind' => 'image',
                'image_url' => $post->image_url,
                'image_prompt' => $post->image_prompt,
                'is_active' => false,
            ]);
        }

        $post->update([
            'image_url' => $image['url'],
            'image_prompt' => $prompt,
            'regen_count' => ($post->regen_count ?? 0) + 1,
        ]);

        // Cascade to feed siblings (FB<->IG same image). Story children keep
        // their own 9:16 graphic and are NOT touched here.
        foreach ($post->feedSiblings() as $sibling) {
            if ($sibling->image_url) {
                SocialPostVariant::create([
                    'social_post_id' => $sibling->id,
                    'kind' => 'image',
                    'image_url' => $sibling->image_url,
                    'image_prompt' => $sibling->image_prompt,
                    'is_active' => false,
                ]);
            }
            $sibling->update([
                'image_url' => $image['url'],
                'image_prompt' => $prompt,
                'regen_count' => ($sibling->regen_count ?? 0) + 1,
            ]);
        }

        return response()->json([
            'image_url' => $post->image_url,
            'image_prompt' => $post->image_prompt,
            'regen_count' => $post->regen_count,
        ]);
    }

    // Regenerate the text content for a post, keeping topic + tone, learning from rejections
    public function regenerateText(Request $request, SocialPost $post)
    {
        if (!in_array($post->status, ['draft', 'scheduled', 'failed'])) {
            return response()->json(['error' => 'Postarea nu mai poate fi modificată.'], 422);
        }

        $topic = $post->metadata['topic'] ?? $request->input('topic', 'Sambla AI assistant');
        $cta = $post->metadata['cta'] ?? 'Află mai multe → sambla.ro';
        $avoidance = SocialRejection::buildAvoidancePrompt($post->platform);
        $instructions = trim((string) $request->input('instructions', ''));

        $prompt = "Generează un post social media SCURT și PUTERNIC pentru {$post->platform}.\n\n"
            . "SUBIECT: {$topic}\n"
            . "CALL TO ACTION: {$cta}\n\n"
            . "TEXT CURENT (de îmbunătățit):\n{$post->content}\n\n"
            . ($instructions !== '' ? "INSTRUCȚIUNI SPECIFICE DE LA USER (prioritate maximă):\n{$instructions}\n\n" : '')
            . "REGULI:\n- Max 150 cuvinte\n- Primul rând: hook puternic\n- Ton: profesional dar accesibil\n- Limba: română\n- Emoji-uri moderate (2-4)\n- Termină cu CTA clar\n- NU folosi hashtag-uri\n\n"
            . ($avoidance ? "\n{$avoidance}\n\n" : '')
            . 'Returnează JSON: {"content": "textul postării"}';

        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'Ești expert în social media marketing pentru SaaS B2B. Răspunzi în JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 500,
                'temperature' => 0.9,
                'response_format' => ['type' => 'json_object'],
            ]);
            $parsed = json_decode($response->choices[0]->message->content ?? '{}', true) ?: [];
            if (empty($parsed['content'])) {
                return response()->json(['error' => 'Generarea textului a eșuat.'], 500);
            }
            // Snapshot previous text as an inactive variant.
            if ($post->content) {
                SocialPostVariant::create([
                    'social_post_id' => $post->id,
                    'kind' => 'text',
                    'content' => $post->content,
                    'hashtags' => $post->hashtags ?? [],
                    'is_active' => false,
                ]);
            }

            $post->update([
                'content' => $parsed['content'],
                'hashtags' => [],
                'regen_count' => ($post->regen_count ?? 0) + 1,
            ]);

            // Cascade new text to ALL siblings in group (feed + story share the copy).
            foreach ($post->siblings() as $sibling) {
                if ($sibling->content) {
                    SocialPostVariant::create([
                        'social_post_id' => $sibling->id,
                        'kind' => 'text',
                        'content' => $sibling->content,
                        'hashtags' => $sibling->hashtags ?? [],
                        'is_active' => false,
                    ]);
                }
                $sibling->update([
                    'content' => $parsed['content'],
                    'hashtags' => [],
                ]);
            }

            return response()->json([
                'content' => $post->content,
                'hashtags' => [],
                'regen_count' => $post->regen_count,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'OpenAI: ' . $e->getMessage()], 500);
        }
    }

    // Reject a post: store feedback in social_rejections, then delete the post
    public function reject(Request $request, SocialPost $post)
    {
        $validated = $request->validate([
            'reason_category' => 'nullable|string|max:50',
            'feedback' => 'nullable|string|max:1000',
        ]);

        // Reject cascades to every sibling so one refusal kills the whole idea.
        // We store a rejection record against each so the avoidance prompt
        // gets the full picture per platform.
        $targets = collect([$post])->concat($post->siblings());
        foreach ($targets as $t) {
            SocialRejection::create([
                'social_post_id' => $t->id,
                'platform' => $t->platform,
                'reason_category' => $validated['reason_category'] ?? 'other',
                'feedback' => $validated['feedback'] ?? null,
                'content_snapshot' => $t->content,
                'image_url' => $t->image_url,
                'image_prompt' => $t->image_prompt,
                'topic' => $t->metadata['topic'] ?? null,
                'hashtags' => $t->hashtags,
            ]);
            $t->delete();
        }

        if ($post->group_id) {
            $post->group?->update(['status' => 'rejected']);
            $post->group?->delete();
        }

        $this->refillDraftBuffer();

        return response()->json(['ok' => true]);
    }

    // Update post content. Cascades text + hashtags to feed siblings (FB↔IG)
    // so editing one half of a group keeps both platforms in sync.
    // scheduled_at and status are NOT cascaded — each platform owns its slot.
    public function update(Request $request, SocialPost $post)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'hashtags' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
        ]);

        $hashtags = $validated['hashtags']
            ? array_map('trim', explode(',', $validated['hashtags']))
            : $post->hashtags;

        $post->update([
            'content' => $validated['content'],
            'hashtags' => $hashtags,
            'scheduled_at' => $validated['scheduled_at'] ?? $post->scheduled_at,
            'status' => $request->input('action') === 'schedule' ? 'scheduled' : 'draft',
        ]);

        // Cascade text + hashtags to feed siblings (FB<->IG kept in sync).
        foreach ($post->feedSiblings() as $sibling) {
            $sibling->update([
                'content' => $validated['content'],
                'hashtags' => $hashtags,
            ]);
        }

        return redirect()->route('admin.social.index')->with('success', 'Post actualizat (text sincronizat cu siblings).');
    }

    /**
     * Approve a draft: assign it to the next free slot in the schedule config
     * for its platform. Spreads posts across days based on posting_times and
     * posts_per_day from SocialSchedule.
     */
    public function approve(SocialPost $post)
    {
        // Collect everything we need to schedule: this post + all group siblings
        // (if any). Legacy ungrouped posts = single-member list.
        $targets = collect([$post])->concat($post->siblings());

        $scheduled = [];
        foreach ($targets as $t) {
            // Each platform gets its own slot. Stories are pushed a bit after
            // the feed post on the same day by starting their search +15min later.
            $from = $t->post_type === 'story' ? now()->addMinutes(15) : now();
            $slot = $this->findNextSlotForPlatform($t->platform, $from);
            if (!$slot) {
                return response()->json([
                    'error' => "Nu am găsit slot liber pentru {$t->platform} ({$t->post_type}).",
                ], 409);
            }
            $t->update(['status' => 'scheduled', 'scheduled_at' => $slot]);
            $scheduled[] = [
                'platform' => $t->platform,
                'post_type' => $t->post_type,
                'at' => $slot->format('Y-m-d\TH:i'),
            ];
        }

        if ($post->group_id) {
            $post->group->update(['status' => 'scheduled']);
        }

        // Refill the draft buffer in the background — approving consumed
        // one slot, dispatch a job to keep the queue at the target.
        $this->refillDraftBuffer();

        // Return the primary post's slot for UI continuity, plus the full list.
        $primary = collect($scheduled)->firstWhere('platform', $post->platform) ?? $scheduled[0];
        return response()->json([
            'ok' => true,
            'scheduled_at' => $primary['at'],
            'scheduled_human' => \Carbon\Carbon::parse($primary['at'])->translatedFormat('l d M, H:i'),
            'fanout' => $scheduled,
        ]);
    }

    /**
     * Maintenance endpoint — runs one of a whitelisted set of artisan commands
     * on the live app container. Used by the admin-only maintenance panel to
     * curate / regenerate / cleanup drafts without SSH access to the server.
     *
     * Protected by the same `super_admin` middleware as the rest of this
     * controller's routes (see routes/web.php).
     */
    /**
     * Stripped-down review queue. Shows the 10 oldest drafts (platform-agnostic,
     * grouped naturally by group_id in the UI) so the reviewer can approve /
     * edit / regen / reject without noise from scheduled/published posts.
     */
    public function review(Request $request)
    {
        // Only show drafts that already have an image — no point reviewing
        // cards that haven't finished generating.
        $posts = SocialPost::where('status', 'draft')
            ->whereNotNull('image_url')
            ->where('image_url', '!=', '')
            ->orderBy('created_at')
            ->limit(10)
            ->get();
        $total = SocialPost::where('status', 'draft')
            ->whereNotNull('image_url')
            ->where('image_url', '!=', '')
            ->count();
        return view('admin.social.review', compact('posts', 'total'));
    }

    public function maintenance(Request $request)
    {
        // Dual auth path:
        //  - super_admin session (admin UI) OR
        //  - Authorization: Bearer MAINTENANCE_TOKEN (env var set via Coolify for remote cleanup).
        $bearer = $request->bearerToken();
        $expected = (string) env('MAINTENANCE_TOKEN', '');
        $sessionAuthed = $request->user() && method_exists($request->user(), 'hasRole') && $request->user()->hasRole('super_admin');
        $tokenAuthed = $expected !== '' && $bearer !== null && hash_equals($expected, (string) $bearer);
        if (!$sessionAuthed && !$tokenAuthed) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $action = (string) $request->input('action', '');

        $badTextHits = function (\App\Models\SocialPost $p): ?string {
            $content = trim((string) $p->content);
            if (mb_strlen($content) < 100) return 'short';
            $lower = mb_strtolower($content);
            foreach (['chatbot', 'voicebot', 'imaginați-vă', 'imaginati-va'] as $t) {
                if (str_contains($lower, $t)) return "term:{$t}";
            }
            if (preg_match('/(^|\W)bot(\W|$)/u', $lower)) return 'term:bot';
            if (empty($p->image_url)) return 'no-image';
            return null;
        };

        // Inline actions — run Eloquent directly, not through Artisan.
        $inline = [
            'stats' => function () {
                $counts = \App\Models\SocialPost::selectRaw('status, count(*) as c')
                    ->groupBy('status')->pluck('c', 'status');
                $orphanGroups = \App\Models\SocialPostGroup::doesntHave('posts')->count();
                return ['counts_by_status' => $counts, 'orphan_groups' => $orphanGroups];
            },

            'scheduled-audit' => function () use ($badTextHits) {
                $posts = \App\Models\SocialPost::where('status', 'scheduled')->get();
                $good = [];
                $bad = [];
                foreach ($posts as $p) {
                    $reason = $badTextHits($p);
                    $reason === null ? $good[] = $p->id : $bad[$reason] = ($bad[$reason] ?? 0) + 1;
                }
                return [
                    'total' => $posts->count(),
                    'good_text' => count($good),
                    'bad_text' => array_sum($bad),
                    'bad_reasons' => $bad,
                ];
            },

            'scheduled-wipe-bad' => function () use ($badTextHits) {
                $posts = \App\Models\SocialPost::where('status', 'scheduled')->get();
                $idsToDelete = [];
                $groupIds = [];
                foreach ($posts as $p) {
                    if ($badTextHits($p) !== null) {
                        $idsToDelete[] = $p->id;
                        if ($p->group_id) $groupIds[] = $p->group_id;
                    }
                }
                $deleted = \App\Models\SocialPost::whereIn('id', $idsToDelete)->delete();
                $orphans = \App\Models\SocialPostGroup::whereIn('id', array_unique($groupIds))
                    ->doesntHave('posts')->delete();
                return ['deleted' => $deleted, 'orphan_groups' => $orphans];
            },

            'regen-scheduled-async' => function () use ($request) {
                $limit = (int) $request->input('limit', 0);
                $email = (string) $request->input('email', 'codrut@ikonia.ro');
                $count = \App\Models\SocialPost::where('status', 'scheduled')->whereNotNull('image_url')->count();
                $batch = $limit > 0 ? min($limit, $count) : $count;
                \App\Jobs\RegenerateScheduledImagesBulkJob::dispatch(
                    $limit > 0 ? $limit : null,
                    $email ?: null
                );
                return [
                    'dispatched' => true,
                    'queued_posts' => $batch,
                    'scope' => $limit > 0 ? "first {$limit}" : 'all scheduled',
                    'estimated_minutes' => round($batch * 103 / 60, 1),
                    'notify_email' => $email ?: null,
                    'note' => 'Running on Horizon queue; email report sent on completion.',
                ];
            },

            'kill-regen-jobs' => function () {
                // Clear any pending/delayed regeneration jobs from the default Horizon queue.
                $cleared = 0;
                try {
                    $pending = \Illuminate\Support\Facades\Redis::lrange('queues:default', 0, -1);
                    foreach ($pending as $payload) {
                        if (str_contains((string) $payload, 'RegenerateScheduledImagesBulkJob')) {
                            \Illuminate\Support\Facades\Redis::lrem('queues:default', 1, $payload);
                            $cleared++;
                        }
                    }
                } catch (\Throwable $e) {
                    return ['error' => $e->getMessage()];
                }
                return ['cleared_pending' => $cleared, 'note' => 'Running jobs NOT killed; they finish the current post then exit cleanly if queue is empty.'];
            },

            'reorganize-scheduled' => function () {
                // 1 GROUP per day. FB + IG sibling cross-post the SAME content on the SAME day
                // (FB at the hero slot, IG +5 min). Story child goes in the afternoon/evening.
                // Group order preserved from existing min(scheduled_at). The calendar then
                // shows one entry per day, which is what the reviewer expects.
                $groupOrder = \App\Models\SocialPost::where('status', 'scheduled')
                    ->whereNotNull('group_id')
                    ->selectRaw('group_id, min(id) as first_id')
                    ->groupBy('group_id')
                    ->orderBy('first_id')
                    ->pluck('group_id')
                    ->values();

                $dayOffset = 1;
                $touched = 0;
                foreach ($groupOrder as $gid) {
                    // Hero slot = a random minute within 10:00–19:00 (so Story can still go 2h after before 21:00).
                    $heroOffset = mt_rand(0, 540); // 0..9h → 10:00..19:00
                    $heroSlot = now()->addDays($dayOffset)->setTime(10, 0, 0)->addMinutes($heroOffset);
                    $posts = \App\Models\SocialPost::where('group_id', $gid)
                        ->where('status', 'scheduled')
                        ->orderBy('id')
                        ->get();
                    foreach ($posts as $p) {
                        if ($p->post_type === 'story') {
                            $time = $heroSlot->copy()->addHours(2); // Story 2h after hero
                        } elseif ($p->platform === 'instagram') {
                            $time = $heroSlot->copy()->addMinutes(5);
                        } else {
                            $time = $heroSlot->copy();
                        }
                        $p->update(['scheduled_at' => $time]);
                        $touched++;
                    }
                    $dayOffset++;
                }

                // Any solo scheduled post without a group goes at the end.
                $solo = \App\Models\SocialPost::where('status', 'scheduled')
                    ->whereNull('group_id')
                    ->orderBy('id')->get();
                foreach ($solo as $p) {
                    $slot = now()->addDays($dayOffset)->setTime(10, 0, 0)->addMinutes(mt_rand(0, 540));
                    $p->update(['scheduled_at' => $slot]);
                    $dayOffset++;
                    $touched++;
                }

                return [
                    'groups' => $groupOrder->count(),
                    'solo_posts' => $solo->count(),
                    'posts_updated' => $touched,
                    'span_days' => $dayOffset - 1,
                    'first_slot' => now()->addDay()->setTime(10, 0, 0)->toDateTimeString(),
                    'last_slot' => now()->addDays($dayOffset - 1)->setTime(10, 0, 0)->toDateTimeString(),
                    'cadence' => '1 group/day — FB at hero slot (10:00–19:00 random), IG +5min, Story +2h',
                ];
            },

            'wipe-failed' => function () {
                $deleted = \App\Models\SocialPost::where('status', 'failed')->delete();
                return ['deleted' => $deleted];
            },

            'drafts-peek' => function () {
                $drafts = \App\Models\SocialPost::where('status', 'draft')
                    ->orderBy('created_at')
                    ->limit(10)
                    ->get(['id', 'platform', 'post_type', 'image_url', 'image_prompt', 'created_at', 'group_id']);
                return [
                    'drafts' => $drafts->map(fn($p) => [
                        'id' => $p->id,
                        'platform' => $p->platform,
                        'post_type' => $p->post_type,
                        'image_url' => $p->image_url,
                        'has_image' => !empty($p->image_url),
                        'prompt_prefix' => mb_substr((string) $p->image_prompt, 0, 60),
                        'created' => (string) $p->created_at,
                        'group' => $p->group_id,
                    ]),
                ];
            },

            'scheduled-peek' => function () {
                $first = \App\Models\SocialPost::where('status', 'scheduled')
                    ->orderBy('scheduled_at')->limit(10)->get(['id', 'platform', 'post_type', 'scheduled_at']);
                $last = \App\Models\SocialPost::where('status', 'scheduled')
                    ->orderByDesc('scheduled_at')->limit(3)->get(['id', 'platform', 'post_type', 'scheduled_at']);
                return [
                    'first_10' => $first->map(fn($p) => ['id' => $p->id, 'platform' => $p->platform, 'post_type' => $p->post_type, 'at' => (string) $p->scheduled_at]),
                    'last_3' => $last->map(fn($p) => ['id' => $p->id, 'platform' => $p->platform, 'post_type' => $p->post_type, 'at' => (string) $p->scheduled_at]),
                    'now' => now()->toDateTimeString(),
                ];
            },

            'clear-caches' => function () {
                $out = [];
                \Illuminate\Support\Facades\Artisan::call('config:clear');
                $out['config'] = \Illuminate\Support\Facades\Artisan::output();
                \Illuminate\Support\Facades\Artisan::call('cache:clear');
                $out['cache'] = \Illuminate\Support\Facades\Artisan::output();
                \Illuminate\Support\Facades\Artisan::call('view:clear');
                $out['view'] = \Illuminate\Support\Facades\Artisan::output();
                \Illuminate\Support\Facades\Artisan::call('route:clear');
                $out['route'] = \Illuminate\Support\Facades\Artisan::output();
                return $out;
            },

            'debug-openai-key' => function () {
                $platformKey = (string) \App\Models\PlatformSetting::get('openai_api_key', '');
                $envKey = (string) env('OPENAI_API_KEY', '');
                $configKey = (string) config('services.openai.api_key', '');
                return [
                    'platform_setting' => $platformKey ? ('len='.strlen($platformKey).' prefix='.substr($platformKey, 0, 12)) : 'EMPTY',
                    'env_key' => $envKey ? ('len='.strlen($envKey).' prefix='.substr($envKey, 0, 12)) : 'EMPTY',
                    'config_key' => $configKey ? ('len='.strlen($configKey).' prefix='.substr($configKey, 0, 12)) : 'EMPTY',
                    'which_wins' => $platformKey ? 'platform_setting' : ($configKey ? 'config/env' : 'none'),
                ];
            },

            'fix-openai-key-from-env' => function () {
                // Nuke any stale platform_setting override so the fresh env var wins.
                \App\Models\PlatformSetting::where('key', 'openai_api_key')->delete();
                return ['deleted' => true, 'note' => 'GptImage2Generator now falls through to env OPENAI_API_KEY.'];
            },

            'fill-missing-images' => function () {
                $drafts_no_image = \App\Models\SocialPost::where('status', 'draft')
                    ->where(function ($q) { $q->whereNull('image_url')->orWhere('image_url', ''); })
                    ->count();
                \App\Jobs\BackfillDraftImagesJob::dispatch();
                return [
                    'dispatched' => true,
                    'drafts_without_image' => $drafts_no_image,
                    'estimated_minutes' => round($drafts_no_image * 103 / 60, 1),
                    'queue' => 'knowledge',
                    'note' => 'Routed to long-timeout worker (600s per job). Backfill runs backfill-images command.',
                ];
            },

            'ensure-drafts-now' => function () use ($request) {
                $target = (int) ($request->input('target', 10));
                $perTick = (int) ($request->input('per_tick', 10));
                \Illuminate\Support\Facades\Artisan::call('social:ensure-drafts', [
                    '--target' => $target,
                    '--per-tick' => $perTick,
                    '--spacing' => 2,
                ]);
                $output = \Illuminate\Support\Facades\Artisan::output();
                return [
                    'target' => $target,
                    'per_tick' => $perTick,
                    'output' => mb_substr($output, -1500),
                ];
            },
            'wipe-scheduled-half' => function () {
                $total = \App\Models\SocialPost::where('status', 'scheduled')->count();
                $toDelete = intdiv($total, 2);
                if ($toDelete === 0) {
                    return ['total_before' => $total, 'deleted' => 0, 'note' => 'nothing to halve'];
                }
                $ids = \App\Models\SocialPost::where('status', 'scheduled')
                    ->orderBy('scheduled_at')->orderBy('id')
                    ->limit($toDelete)->pluck('id')->all();
                $deleted = \App\Models\SocialPost::whereIn('id', $ids)->delete();
                $orphans = \App\Models\SocialPostGroup::doesntHave('posts')->delete();
                return compact('total', 'deleted', 'orphans') + ['remaining' => $total - $deleted];
            },
            'wipe-scheduled-all' => function () {
                $total = \App\Models\SocialPost::where('status', 'scheduled')->count();
                $deleted = \App\Models\SocialPost::where('status', 'scheduled')->delete();
                $orphans = \App\Models\SocialPostGroup::doesntHave('posts')->delete();
                return compact('total', 'deleted', 'orphans');
            },
            'wipe-everything' => function () {
                // Nuclear: deletes ALL posts and groups. Use when starting from scratch.
                $total = \App\Models\SocialPost::count();
                $deleted = \App\Models\SocialPost::query()->delete();
                $orphans = \App\Models\SocialPostGroup::doesntHave('posts')->delete();
                return compact('total', 'deleted', 'orphans');
            },
        ];

        if (isset($inline[$action])) {
            try {
                return response()->json([
                    'ok' => true,
                    'action' => $action,
                    'result' => $inline[$action](),
                ]);
            } catch (\Throwable $e) {
                \Log::error('admin maintenance inline failed', ['action' => $action, 'error' => $e->getMessage()]);
                return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
            }
        }

        $whitelist = [
            'curate-dry'        => ['social:curate-drafts',    ['--dry-run' => true]],
            'curate-soft'       => ['social:curate-drafts',    ['--force' => true]],
            'curate-hard'       => ['social:curate-drafts',    ['--force' => true, '--hard' => true]],
            'cleanup-dry'       => ['social:cleanup-drafts',   ['--dry-run' => true, '--days' => 7]],
            'cleanup-7d'        => ['social:cleanup-drafts',   ['--force' => true, '--days' => 7]],
            'cleanup-3d'        => ['social:cleanup-drafts',   ['--force' => true, '--days' => 3]],
            'regen-scheduled'   => ['social:regenerate-images', ['--status' => ['scheduled'], '--backup' => true, '--sleep' => 3]],
            'regen-drafts-all'  => ['social:regenerate-images', ['--status' => ['draft'], '--backup' => true, '--sleep' => 3]],
            'regen-drafts-10'   => ['social:regenerate-images', ['--status' => ['draft'], '--backup' => true, '--sleep' => 3, '--limit' => 10]],
            'backfill-missing'  => ['social:backfill-images',   ['--backup' => true]],
            'generate-one'      => ['social:generate-batch',    [1, '--drafts' => true]],
        ];

        if (!isset($whitelist[$action])) {
            return response()->json([
                'error' => 'Unknown action.',
                'available' => array_merge(array_keys($inline), array_keys($whitelist)),
            ], 422);
        }

        [$cmd, $args] = $whitelist[$action];

        $startedAt = microtime(true);
        try {
            Artisan::call($cmd, $args);
            $output = Artisan::output();
            $elapsed = round(microtime(true) - $startedAt, 1);

            return response()->json([
                'ok' => true,
                'action' => $action,
                'command' => $cmd,
                'args' => $args,
                'elapsed_seconds' => $elapsed,
                'output' => $output,
            ]);
        } catch (\Throwable $e) {
            \Log::error('admin maintenance action failed', [
                'action' => $action,
                'command' => $cmd,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'ok' => false,
                'action' => $action,
                'command' => $cmd,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fire-and-forget call to top up the draft buffer to its configured
     * target. Used after every action that consumes a draft (approve,
     * reject, destroy). The job is queued, not run inline, so the user
     * action stays fast.
     */
    private function refillDraftBuffer(): void
    {
        try {
            Artisan::call('social:ensure-drafts', [
                '--target' => 10,
                '--per-tick' => 2,
                '--spacing' => 15,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('refillDraftBuffer failed', ['error' => $e->getMessage()]);
        }
    }

    // Publish immediately (JSON-aware). Cascades to all group siblings (FB+IG)
    // so a "Publish now" from any one post fans out to the whole group.
    public function publish(Request $request, SocialPost $post)
    {
        $targets = collect([$post])->concat($post->siblings());
        foreach ($targets as $t) {
            $t->update(['status' => 'scheduled', 'scheduled_at' => now()]);
            dispatch(new \App\Jobs\AutoPublishSocialPost($t->id));
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['ok' => true, 'count' => $targets->count()]);
        }
        return back()->with('success', 'Post trimis la publicare!');
    }

    // Soft-delete post (JSON-aware). Restorable via POST /restore within the retention window.
    public function destroy(Request $request, SocialPost $post)
    {
        $wasDraft = $post->status === 'draft';
        $post->delete(); // soft delete
        if ($wasDraft) {
            $this->refillDraftBuffer();
        }
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['ok' => true, 'id' => $post->id]);
        }
        return back()->with('success', 'Post sters.');
    }

    // Restore a soft-deleted post (undo button in toast).
    public function restore(int $id)
    {
        $post = SocialPost::onlyTrashed()->findOrFail($id);
        $post->restore();
        return response()->json(['ok' => true, 'id' => $post->id]);
    }

    // Duplicate a post as a new draft
    public function duplicate(SocialPost $post)
    {
        $new = $post->replicate(['external_post_id', 'external_url', 'published_at', 'error_message']);
        $new->status = 'draft';
        $new->scheduled_at = null;
        $new->regen_count = 0;
        $new->save();

        return response()->json(['id' => $new->id]);
    }

    // Promote a stored variant back to the active content of the post.
    public function useVariant(Request $request, SocialPost $post, SocialPostVariant $variant)
    {
        abort_unless($variant->social_post_id === $post->id, 404);

        if ($variant->kind === 'text' || $variant->kind === 'both') {
            // Snapshot current text before swap
            SocialPostVariant::create([
                'social_post_id' => $post->id,
                'kind' => 'text',
                'content' => $post->content,
                'hashtags' => $post->hashtags ?? [],
                'is_active' => false,
            ]);
            $post->content = $variant->content;
            $post->hashtags = $variant->hashtags ?? [];
        }
        if ($variant->kind === 'image' || $variant->kind === 'both') {
            SocialPostVariant::create([
                'social_post_id' => $post->id,
                'kind' => 'image',
                'image_url' => $post->image_url,
                'image_prompt' => $post->image_prompt,
                'is_active' => false,
            ]);
            $post->image_url = $variant->image_url;
            $post->image_prompt = $variant->image_prompt;
        }
        $post->save();

        return response()->json(['ok' => true]);
    }

    // Bulk actions: delete, reschedule, publish
    public function bulk(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:delete,reschedule,publish',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'scheduled_at' => 'nullable|date',
        ]);

        $posts = SocialPost::whereIn('id', $validated['ids']);
        $count = 0;

        switch ($validated['action']) {
            case 'delete':
                $count = $posts->count();
                $posts->delete();
                break;
            case 'reschedule':
                abort_unless(!empty($validated['scheduled_at']), 422, 'scheduled_at required');
                $count = $posts->update([
                    'scheduled_at' => $validated['scheduled_at'],
                    'status' => 'scheduled',
                ]);
                break;
            case 'publish':
                foreach ($posts->get() as $p) {
                    $p->update(['status' => 'scheduled', 'scheduled_at' => now()]);
                    dispatch(new \App\Jobs\AutoPublishSocialPost($p->id));
                    $count++;
                }
                break;
        }

        return response()->json(['ok' => true, 'count' => $count]);
    }

    // Generate bio
    public function generateBio(Request $request)
    {
        $platform = $request->input('platform', 'facebook');
        $gemini = app(GeminiContentService::class);
        $result = $gemini->generateBio($platform);

        return response()->json($result);
    }

    // === STYLE TRAINING ===

    public function styleTraining()
    {
        $unreviewed = SocialStylePreference::whereNull('approved')->paginate(20);
        $approved = SocialStylePreference::where('approved', true)->count();
        $rejected = SocialStylePreference::where('approved', false)->count();

        return view('admin.social.style', compact('unreviewed', 'approved', 'rejected'));
    }

    // Add example for training
    public function addStyleExample(Request $request)
    {
        $validated = $request->validate([
            'platform' => 'required|in:facebook,instagram,blog',
            'content' => 'required|string|max:5000',
            'source' => 'nullable|string|max:500',
        ]);

        SocialStylePreference::create([
            'platform' => $validated['platform'],
            'example_content' => $validated['content'],
            'example_source' => $validated['source'],
            'approved' => null,
        ]);

        return back()->with('success', 'Exemplu adaugat pentru review.');
    }

    // Approve/reject style example
    public function reviewStyle(Request $request, SocialStylePreference $preference)
    {
        $preference->update([
            'approved' => $request->input('approved') === 'true',
            'notes' => $request->input('notes'),
        ]);

        // If we have 10+ approved examples, auto-generate style guidelines
        $approvedCount = SocialStylePreference::where('platform', $preference->platform)
            ->where('approved', true)
            ->count();

        if ($approvedCount >= 10) {
            $this->regenerateStyleGuidelines($preference->platform);
        }

        return back()->with('success', 'Exemplu evaluat.');
    }

    // Regenerate style guidelines from approved examples
    private function regenerateStyleGuidelines(string $platform): void
    {
        $approved = SocialStylePreference::where('platform', $platform)
            ->where('approved', true)
            ->pluck('example_content')
            ->toArray();

        if (count($approved) < 5) {
            return;
        }

        $gemini = app(GeminiContentService::class);
        $guidelines = $gemini->analyzeStyle($approved, $platform);

        SocialSchedule::updateOrCreate(
            ['platform' => $platform],
            ['style_guidelines' => $guidelines]
        );
    }

    // === API KEYS ===

    public function saveApiKeys(Request $request)
    {
        $validated = $request->validate([
            'gemini_api_key' => 'nullable|string|max:255',
            'gemini_image_model' => 'nullable|string|max:100',
        ]);

        if (!empty($validated['gemini_api_key']) && $validated['gemini_api_key'] !== '••••••••••') {
            \DB::table('settings')->updateOrInsert(
                ['key' => 'gemini_api_key'],
                ['value' => encrypt($validated['gemini_api_key'])]
            );
        }

        if (!empty($validated['gemini_image_model'])) {
            \DB::table('settings')->updateOrInsert(
                ['key' => 'gemini_image_model'],
                ['value' => $validated['gemini_image_model']]
            );
        }

        return back()->with('success', 'API Keys salvate.');
    }

    // === ACCOUNTS ===

    public function accounts()
    {
        $accounts = SocialAccount::all();

        return view('admin.social.accounts', compact('accounts'));
    }

    public function saveAccount(Request $request)
    {
        $validated = $request->validate([
            'platform' => 'required|in:facebook,instagram,blog',
            'name' => 'required|string|max:255',
            'platform_id' => 'nullable|string|max:255',
            'access_token' => 'nullable|string|max:1000',
        ]);

        SocialAccount::updateOrCreate(
            ['platform' => $validated['platform']],
            $validated
        );

        return back()->with('success', 'Cont actualizat.');
    }

    // === SCHEDULE ===

    public function schedule()
    {
        $schedules = SocialSchedule::all()->keyBy('platform');

        // Ensure all platforms have a schedule record
        foreach (['facebook', 'instagram', 'blog'] as $p) {
            if (!isset($schedules[$p])) {
                $schedules[$p] = SocialSchedule::create(['platform' => $p]);
            }
        }

        return view('admin.social.schedule', compact('schedules'));
    }

    public function saveSchedule(Request $request)
    {
        $validated = $request->validate([
            'platform' => 'required|in:facebook,instagram,blog,social',
            'is_active' => 'boolean',
            'posts_per_day' => 'integer|min:1|max:20',
            'posting_times' => 'nullable|string',
            'topics' => 'nullable|string',
            'auto_blog' => 'boolean',
            'blog_frequency_days' => 'integer|min:1|max:30',
        ]);

        // 'social' is a synthetic UI key meaning "Facebook + Instagram"
        // — every social post group is fanned out to BOTH platforms, so
        // their schedules must stay in sync. Until we ever decouple them
        // we just write the same row twice. (TODO: migrate to a single
        // shared schedule row when product needs diverge.)
        $targetPlatforms = $validated['platform'] === 'social'
            ? ['facebook', 'instagram']
            : [$validated['platform']];

        // Detect whether posts_per_day actually changed for any social
        // platform. If yes, we need to reshuffle scheduled posts after
        // saving so the calendar respects the new max-per-day.
        $newPostsPerDay = (int) ($validated['posts_per_day'] ?? 1);
        $changedPostsPerDay = false;
        foreach ($targetPlatforms as $p) {
            if (in_array($p, ['facebook', 'instagram'], true)) {
                $existing = (int) (SocialSchedule::where('platform', $p)->value('posts_per_day') ?? 0);
                if ($existing !== $newPostsPerDay) {
                    $changedPostsPerDay = true;
                }
            }
        }

        $payload = [
            'is_active' => $request->boolean('is_active'),
            'posts_per_day' => $newPostsPerDay,
            'posting_times' => $validated['posting_times'] ? array_map('trim', explode(',', $validated['posting_times'])) : ['10:00'],
            'topics' => $validated['topics'] ? array_map('trim', explode(',', $validated['topics'])) : [],
            'auto_blog' => $request->boolean('auto_blog'),
            'blog_frequency_days' => $validated['blog_frequency_days'] ?? 3,
        ];

        foreach ($targetPlatforms as $p) {
            SocialSchedule::updateOrCreate(['platform' => $p], $payload);
        }

        $message = 'Programare actualizata.';
        if ($changedPostsPerDay) {
            $count = $this->reshuffleScheduledPosts();
            $message .= " Reschedule: $count grupuri redistribuite cu noua formulă.";
        }

        return back()->with('success', $message);
    }

    /**
     * Redistribute all scheduled (future, unpublished) post groups across
     * upcoming days using the current `posts_per_day` setting from the
     * facebook schedule. Each day gets a random count between half and max,
     * within the 08:00-21:00 window, with anti-clustering on category so
     * the same theme doesn't show up back-to-back.
     *
     * Returns the number of groups that were re-slotted.
     */
    public function reshuffleScheduledPosts(): int
    {
        $maxPerDay = (int) (SocialSchedule::where('platform', 'facebook')->value('posts_per_day') ?: 5);
        $minPerDay = max(1, (int) ceil($maxPerDay / 2));
        $startHour = 8;
        $endHour = 20; // last slot 20:59 — keeps everything ≤ 21:00

        $scheduled = SocialPost::where('status', 'scheduled')->orderBy('id')->get();
        if ($scheduled->isEmpty()) return 0;

        $groups = $scheduled->groupBy(fn($p) => $p->group_id ?? 'solo_' . $p->id);

        // Bucket each group by category (or first 4 words of content as fallback)
        // for anti-clustering.
        $bucketKey = function ($g) {
            $first = $g->sortBy('id')->first();
            $cat = $first->metadata['category'] ?? null;
            if ($cat) return $cat;
            $words = preg_split('/\s+/', mb_strtolower(preg_replace('/\s+/', ' ', (string) $first->content)));
            return implode(' ', array_slice($words, 0, 4));
        };

        $byBucket = [];
        foreach ($groups as $gid => $g) {
            $byBucket[$bucketKey($g)][] = $gid;
        }

        // Round-robin: pick from largest bucket each step, but never reuse
        // the previous bucket twice in a row.
        $ordered = [];
        $lastBucket = null;
        while (!empty($byBucket)) {
            uasort($byBucket, fn($a, $b) => count($b) - count($a));
            $bk = array_key_first($byBucket);
            if ($bk === $lastBucket && count($byBucket) > 1) {
                $bk = array_keys($byBucket)[1];
            }
            $ordered[] = array_shift($byBucket[$bk]);
            $lastBucket = $bk;
            if (empty($byBucket[$bk])) unset($byBucket[$bk]);
        }

        // Build slot calendar: EXACTLY maxPerDay groups per day (strict).
        // Distribute evenly with random hours in the window.
        $slots = [];
        $day = \Carbon\Carbon::today();
        $needed = count($ordered);
        $safety = 0;
        while (count($slots) < $needed && $safety++ < 365) {
            $countToday = $maxPerDay; // strict limit, always fill to max
            $hours = range($startHour, $endHour);
            shuffle($hours);
            $hours = array_slice($hours, 0, min($countToday, count($hours)));
            sort($hours);
            foreach ($hours as $h) {
                $slot = $day->copy()->setTime($h, random_int(0, 59), 0);
                if ($slot->isPast()) continue;
                $slots[] = $slot;
            }
            $day->addDay();
        }
        $slots = array_slice($slots, 0, $needed);

        foreach ($ordered as $i => $gid) {
            $slot = $slots[$i] ?? null;
            if (!$slot) break;
            foreach ($groups[$gid] as $p) {
                $newAt = $p->post_type === 'story' ? $slot->copy()->addMinutes(15) : $slot;
                $p->update(['scheduled_at' => $newAt]);
            }
        }

        return count($ordered);
    }
}
