<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocialPost;
use App\Models\SocialAccount;
use App\Models\SocialSchedule;
use App\Models\SocialStylePreference;
use App\Models\SocialRejection;
use App\Services\Social\GeminiContentService;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;

class AdminSocialController extends Controller
{
    // Dashboard with overview
    public function index(Request $request)
    {
        $query = SocialPost::query();
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($platform = $request->get('platform')) {
            $query->where('platform', $platform);
        }
        $posts = $query->orderByRaw("CASE WHEN status='scheduled' THEN 0 WHEN status='draft' THEN 1 WHEN status='failed' THEN 2 ELSE 3 END")
            ->orderBy('scheduled_at', 'asc')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();
        $accounts = SocialAccount::all();
        $schedules = SocialSchedule::all();
        $stats = [
            'total_posts' => SocialPost::count(),
            'published' => SocialPost::where('status', 'published')->count(),
            'scheduled' => SocialPost::where('status', 'scheduled')->count(),
            'failed' => SocialPost::where('status', 'failed')->count(),
            'today' => SocialPost::whereDate('published_at', today())->count(),
        ];

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

        return redirect()->route('admin.social.edit', $post)->with('success', 'Post generat cu succes!');
    }

    // Edit a post before publishing
    public function edit(SocialPost $post)
    {
        return view('admin.social.edit', compact('post'));
    }

    // Return single post as JSON for the modal viewer
    public function show(SocialPost $post)
    {
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
            'scheduled_at' => $post->scheduled_at?->format('Y-m-d H:i'),
            'published_at' => $post->published_at?->format('Y-m-d H:i'),
            'external_url' => $post->external_url,
            'error_message' => $post->error_message,
            'is_editable' => in_array($post->status, ['draft', 'scheduled']),
        ]);
    }

    // Regenerate the image for a post (different style/seed). Optional ?prompt= override.
    public function regenerateImage(Request $request, SocialPost $post)
    {
        if (!in_array($post->status, ['draft', 'scheduled'])) {
            return response()->json(['error' => 'Postarea nu mai poate fi modificată.'], 422);
        }

        $promptOverride = trim((string) $request->input('prompt', ''));
        $prompt = $promptOverride !== '' ? $promptOverride : ($post->image_prompt ?? ($post->metadata['topic'] ?? 'Sambla AI assistant'));

        $gemini = app(GeminiContentService::class);
        $image = $gemini->generateImage($prompt, '3:4');

        if (!$image || empty($image['url'])) {
            return response()->json(['error' => 'Generarea imaginii a eșuat.'], 500);
        }

        $post->update([
            'image_url' => $image['url'],
            'image_prompt' => $prompt,
        ]);

        return response()->json(['image_url' => $post->image_url, 'image_prompt' => $post->image_prompt]);
    }

    // Regenerate the text content for a post, keeping topic + tone, learning from rejections
    public function regenerateText(Request $request, SocialPost $post)
    {
        if (!in_array($post->status, ['draft', 'scheduled'])) {
            return response()->json(['error' => 'Postarea nu mai poate fi modificată.'], 422);
        }

        $topic = $post->metadata['topic'] ?? $request->input('topic', 'Sambla AI assistant');
        $cta = $post->metadata['cta'] ?? 'Află mai multe → sambla.ro';
        $avoidance = SocialRejection::buildAvoidancePrompt($post->platform);

        $prompt = "Generează un post social media SCURT și PUTERNIC pentru {$post->platform}.\n\n"
            . "SUBIECT: {$topic}\n"
            . "CALL TO ACTION: {$cta}\n\n"
            . "REGULI:\n- Max 150 cuvinte\n- Primul rând: hook puternic\n- Ton: profesional dar accesibil\n- Limba: română\n- Emoji-uri moderate (2-4)\n- Termină cu CTA clar\n\n"
            . ($avoidance ? "\n{$avoidance}\n\n" : '')
            . 'Returnează JSON: {"content": "textul postării", "hashtags": ["tag1","tag2","tag3"]}';

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
            $post->update([
                'content' => $parsed['content'],
                'hashtags' => $parsed['hashtags'] ?? $post->hashtags,
            ]);

            return response()->json(['content' => $post->content, 'hashtags' => $post->hashtags]);
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

    // Publish immediately
    public function publish(SocialPost $post)
    {
        $post->update(['status' => 'scheduled', 'scheduled_at' => now()]);
        dispatch(new \App\Jobs\AutoPublishSocialPost($post->id));

        return back()->with('success', 'Post trimis la publicare!');
    }

    // Delete post
    public function destroy(SocialPost $post)
    {
        $post->delete();

        return back()->with('success', 'Post sters.');
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
