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
        $query->orderByRaw("CASE WHEN status IN ('scheduled','draft','failed','publishing') THEN 0 ELSE 1 END")
              ->orderByRaw('COALESCE(scheduled_at, published_at, created_at) ASC NULLS LAST')
              ->orderByDesc('id');

        $posts = $query->paginate(50)->withQueryString();

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

        return view('admin.social.index', compact('posts', 'accounts', 'schedules', 'stats'));
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
    public function show(SocialPost $post)
    {
        $post->load(['variants' => fn($q) => $q->orderByDesc('id')->limit(10), 'rejections' => fn($q) => $q->orderByDesc('id')->limit(5)]);

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
        $prompt = $promptOverride !== '' ? $promptOverride : ($post->image_prompt ?? ($post->metadata['topic'] ?? 'Sambla AI assistant'));

        $gemini = app(GeminiContentService::class);
        $aspect = $post->post_type === 'story' ? '9:16' : '3:4';
        $image = $gemini->generateImage($prompt, $aspect);

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

        $prompt = "Generează un post social media SCURT și PUTERNIC pentru {$post->platform}.\n\n"
            . "SUBIECT: {$topic}\n"
            . "CALL TO ACTION: {$cta}\n\n"
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

        SocialRejection::create([
            'social_post_id' => $post->id,
            'platform' => $post->platform,
            'reason_category' => $validated['reason_category'] ?? 'other',
            'feedback' => $validated['feedback'] ?? null,
            'content_snapshot' => $post->content,
            'image_url' => $post->image_url,
            'image_prompt' => $post->image_prompt,
            'topic' => $post->metadata['topic'] ?? null,
            'hashtags' => $post->hashtags,
        ]);

        $post->delete();

        return response()->json(['ok' => true]);
    }

    // Update post content
    public function update(Request $request, SocialPost $post)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'hashtags' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
        ]);

        $post->update([
            'content' => $validated['content'],
            'hashtags' => $validated['hashtags'] ? array_map('trim', explode(',', $validated['hashtags'])) : $post->hashtags,
            'scheduled_at' => $validated['scheduled_at'] ?? $post->scheduled_at,
            'status' => $request->input('action') === 'schedule' ? 'scheduled' : 'draft',
        ]);

        return redirect()->route('admin.social.index')->with('success', 'Post actualizat!');
    }

    // Publish immediately (JSON-aware)
    public function publish(Request $request, SocialPost $post)
    {
        $post->update(['status' => 'scheduled', 'scheduled_at' => now()]);
        dispatch(new \App\Jobs\AutoPublishSocialPost($post->id));

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['ok' => true]);
        }
        return back()->with('success', 'Post trimis la publicare!');
    }

    // Soft-delete post (JSON-aware). Restorable via POST /restore within the retention window.
    public function destroy(Request $request, SocialPost $post)
    {
        $post->delete(); // soft delete
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
            'platform' => 'required|in:facebook,instagram,blog',
            'is_active' => 'boolean',
            'posts_per_day' => 'integer|min:1|max:5',
            'posting_times' => 'nullable|string',
            'topics' => 'nullable|string',
            'auto_blog' => 'boolean',
            'blog_frequency_days' => 'integer|min:1|max:30',
        ]);

        SocialSchedule::updateOrCreate(
            ['platform' => $validated['platform']],
            [
                'is_active' => $request->boolean('is_active'),
                'posts_per_day' => $validated['posts_per_day'] ?? 1,
                'posting_times' => $validated['posting_times'] ? array_map('trim', explode(',', $validated['posting_times'])) : ['10:00'],
                'topics' => $validated['topics'] ? array_map('trim', explode(',', $validated['topics'])) : [],
                'auto_blog' => $request->boolean('auto_blog'),
                'blog_frequency_days' => $validated['blog_frequency_days'] ?? 3,
            ]
        );

        return back()->with('success', 'Programare actualizata.');
    }
}
