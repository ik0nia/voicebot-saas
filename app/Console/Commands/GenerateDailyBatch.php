<?php

namespace App\Console\Commands;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\SocialPostGroup;
use App\Services\Social\GeminiContentService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateDailyBatch extends Command
{
    protected $signature = 'social:generate-batch
                            {count=10 : Number of posts to generate}
                            {--date= : Date YYYY-MM-DD (default today)}
                            {--from=09:00 : Earliest posting time HH:MM (only enforced for future dates)}
                            {--until=20:00 : Schedule posts until this time (HH:MM)}
                            {--platform=both : facebook, instagram, or both}
                            {--drafts : Create posts as drafts with no scheduled_at (for review queue)}
                            {--dry-run : Preview without creating}';

    protected $description = 'Generate a batch of scheduled social media posts with CTA-focused images';

    /**
     * CTA-focused post topics — each one promotes Sambla with a clear call-to-action
     */
    private array $ctaTopics = [
        [
            'topic' => 'Chatbot-ul care nu doarme niciodată. Clienții tăi primesc răspunsuri instant, 24/7, chiar și la 3 dimineața. Zero timpi de așteptare.',
            'cta' => 'Încearcă gratuit',
            'image_concept' => 'A glowing chat bubble icon floating above a sleeping city at night, warm ambient light from the chat bubble, clean minimal composition',
            'visual_text' => 'Sambla — 24/7',
        ],
        [
            'topic' => 'Setup în 10 minute, fără o linie de cod. Încarci documentele, personalizezi răspunsurile, și ești live. Atât de simplu.',
            'cta' => 'Începe acum — 10 minute',
            'image_concept' => 'A minimal timer/stopwatch showing 10 minutes with a green checkmark, clean white background, one red accent element',
            'visual_text' => '10 min setup',
        ],
        [
            'topic' => 'Voicebot-ul Sambla răspunde la telefon exact ca un angajat real. Voce naturală, înțelege context, vorbește română nativă.',
            'cta' => 'Ascultă demo-ul',
            'image_concept' => 'A modern smartphone with sound waves emanating from it in red gradient, minimal clean background, professional',
            'visual_text' => 'Sambla Voice',
        ],
        [
            'topic' => 'Transformă vizitatorii site-ului în clienți. Chatbot-ul Sambla califică lead-uri automat și le trimite echipei tale de vânzări.',
            'cta' => 'Crește-ți vânzările',
            'image_concept' => 'An upward arrow made of small chat bubbles, showing growth/conversion, red and white color scheme, minimal',
            'visual_text' => '+42% conversii',
        ],
        [
            'topic' => 'Anti-halucinare: AI-ul Sambla răspunde DOAR din datele tale. Nu inventează prețuri, nu promite ce nu poate livra.',
            'cta' => 'Vezi cum funcționează',
            'image_concept' => 'A shield icon with a checkmark inside, representing trust and accuracy, clean minimal design with red accent',
            'visual_text' => '100% acurat',
        ],
        [
            'topic' => 'Integrare WooCommerce: chatbot-ul verifică stocuri, recomandă produse și ajută la checkout. Vânzări 24/7 pe pilot automat.',
            'cta' => 'Conectează magazinul',
            'image_concept' => 'A shopping cart icon connected to a chat bubble with a subtle link/chain, ecommerce meets AI, clean modern',
            'visual_text' => 'Shop + AI',
        ],
        [
            'topic' => 'GDPR compliant din prima zi. Date izolate per client, hosting 100% în România, fără transfer de date în afara UE.',
            'cta' => 'Află mai multe',
            'image_concept' => 'A padlock icon with EU stars and Romanian flag colors subtly integrated, trust and security theme, minimal clean',
            'visual_text' => 'GDPR ready',
        ],
        [
            'topic' => 'Reduce costurile de suport cu 40%. Un singur chatbot Sambla face treaba a 3 agenți. Fără concedii, fără pauze.',
            'cta' => 'Calculează economiile',
            'image_concept' => 'A simple downward cost arrow next to an upward quality arrow, showing cost reduction with quality increase, red accents on white',
            'visual_text' => '-40% costuri',
        ],
        [
            'topic' => 'Analytics în timp real: vezi ce întreabă clienții, ce îi frustrează, unde pierzi vânzări. Transformă conversațiile în insight-uri.',
            'cta' => 'Descoperă insight-urile',
            'image_concept' => 'A clean dashboard mockup showing simple bar charts and a magnifying glass, data analytics theme, modern minimal',
            'visual_text' => 'Smart analytics',
        ],
        [
            'topic' => 'Planuri de la 99€/lună. Fără contracte pe termen lung, fără costuri ascunse. Anulezi oricând. Începi cu trial gratuit.',
            'cta' => 'Începe gratuit',
            'image_concept' => 'A price tag showing 99€ with a "start free" badge, inviting and clean, red and white, minimal premium feel',
            'visual_text' => 'De la 99€/lună',
        ],
        [
            'topic' => 'Sambla învață din fiecare conversație. Cu cât vorbește mai mult cu clienții tăi, cu atât devine mai bun. Auto-îmbunătățire continuă.',
            'cta' => 'Vezi evoluția AI',
            'image_concept' => 'A brain icon connected to ascending dots/nodes showing learning progression, AI growth theme, clean red accents',
            'visual_text' => 'AI care învață',
        ],
        [
            'topic' => 'Migrezi de la alt chatbot? Sambla importă baza de cunoștințe existentă în câteva minute. Tranziție fără downtime.',
            'cta' => 'Migrează acum',
            'image_concept' => 'Two chat bubbles with an arrow between them showing migration/transition, seamless and simple, clean design',
            'visual_text' => 'Easy switch',
        ],
    ];

    public function handle(): int
    {
        $count = (int) $this->argument('count');
        $untilTime = $this->option('until');
        $platformOption = $this->option('platform');
        $dryRun = $this->option('dry-run');
        $draftsOnly = (bool) $this->option('drafts');

        $fbAccount = SocialAccount::where('platform', 'facebook')->where('is_active', true)->first();
        $igAccount = SocialAccount::where('platform', 'instagram')->where('is_active', true)->first();

        if ($platformOption === 'both' && (!$fbAccount || !$igAccount)) {
            $this->warn('Not all accounts configured. Using available ones.');
        }

        $dateOpt = $this->option('date');
        $fromTime = $this->option('from');
        $targetDate = $dateOpt ? Carbon::parse($dateOpt)->startOfDay() : Carbon::today();
        $endTime = $targetDate->copy()->setTimeFromTimeString($untilTime);
        $startTime = $targetDate->copy()->setTimeFromTimeString($fromTime);
        $now = Carbon::now();

        // For today, never schedule in the past
        if ($targetDate->isToday() && $startTime->lt($now)) {
            $startTime = $now;
        }

        if ($endTime->lte($startTime)) {
            $this->error("End time {$untilTime} is before start time {$startTime->format('H:i')} on {$targetDate->toDateString()}.");
            return self::FAILURE;
        }

        $minutesAvailable = $startTime->diffInMinutes($endTime);
        $interval = (int) floor($minutesAvailable / $count);

        $this->info("Generating {$count} posts on {$targetDate->toDateString()}, every ~{$interval} min from {$startTime->format('H:i')} to {$endTime->format('H:i')}");
        $this->newLine();

        $gemini = app(GeminiContentService::class);
        // Cycle through topics if count > topic count (with re-shuffle each cycle).
        $allTopics = collect($this->ctaTopics);
        $topics = collect();
        while ($topics->count() < $count) {
            $topics = $topics->concat($allTopics->shuffle());
        }
        $topics = $topics->take($count)->values();

        $created = 0;
        foreach ($topics as $i => $topicData) {
            $scheduledAt = $startTime->copy()->addMinutes($interval * ($i + 1));
            $this->components->task(
                "Post " . ($i + 1) . "/{$count} @ {$scheduledAt->format('H:i')} — {$topicData['visual_text']}",
                function () use ($topicData, $scheduledAt, $gemini, $fbAccount, $igAccount, $platformOption, $dryRun, &$created) {
                    // Generate text content
                    $textResult = $this->generateText($gemini, $topicData);
                    if (!$textResult) return false;

                    // Generate CTA-focused image with minimal text
                    $image = $this->generateCtaImage($gemini, $topicData);

                    if ($dryRun) {
                        $this->line("    Text: " . mb_substr($textResult['content'], 0, 80) . "...");
                        if ($image) $this->line("    Image: {$image['url']}");
                        return true;
                    }

                    $postStatus = $draftsOnly ? 'draft' : 'scheduled';
                    $fbScheduled = $draftsOnly ? null : $scheduledAt;
                    $igScheduled = $draftsOnly ? null : $scheduledAt->copy()->addMinutes(5);

                    // Every 3rd post idea also gets a Story (9:16) child.
                    // Uses a simple modulo on the iteration index for deterministic cadence.
                    $includeStory = ($i % 3 === 0);
                    $storyImage = null;
                    if ($includeStory) {
                        $storyImage = $this->generateStoryImage($gemini, $topicData);
                    }

                    // Create the group that binds all children together.
                    $group = SocialPostGroup::create([
                        'topic' => mb_substr($topicData['topic'], 0, 240),
                        'cta' => $topicData['cta'] ?? null,
                        'status' => $postStatus === 'scheduled' ? 'scheduled' : 'draft',
                        'has_story' => $includeStory && $storyImage,
                        'metadata' => [
                            'visual_text' => $topicData['visual_text'] ?? null,
                            'image_concept' => $topicData['image_concept'] ?? null,
                        ],
                    ]);

                    // Create Facebook post
                    if (in_array($platformOption, ['both', 'facebook']) && $fbAccount) {
                        SocialPost::create([
                            'group_id' => $group->id,
                            'social_account_id' => $fbAccount->id,
                            'platform' => 'facebook',
                            'status' => $postStatus,
                            'post_type' => 'post',
                            'content' => $textResult['content'],
                            'hashtags' => $textResult['hashtags'] ?? [],
                            'image_url' => $image['url'] ?? null,
                            'image_prompt' => $topicData['image_concept'],
                            'metadata' => ['topic' => $topicData['topic'], 'cta' => $topicData['cta']],
                            'scheduled_at' => $fbScheduled,
                            'ai_tokens_used' => $textResult['tokens_used'] ?? 0,
                        ]);
                        $created++;
                    }

                    // Create Instagram post (only if we have an image)
                    if (in_array($platformOption, ['both', 'instagram']) && $igAccount && $image) {
                        SocialPost::create([
                            'group_id' => $group->id,
                            'social_account_id' => $igAccount->id,
                            'platform' => 'instagram',
                            'status' => $postStatus,
                            'post_type' => 'post',
                            'content' => $textResult['content'],
                            'hashtags' => $textResult['hashtags'] ?? [],
                            'image_url' => $image['url'] ?? null,
                            'image_prompt' => $topicData['image_concept'],
                            'metadata' => ['topic' => $topicData['topic'], 'cta' => $topicData['cta']],
                            'scheduled_at' => $igScheduled,
                            'ai_tokens_used' => 0,
                        ]);
                        $created++;
                    }

                    // Story child (IG only, 9:16). Scheduled slightly later to
                    // avoid hammering Meta's API; draft if we're buffering.
                    if ($includeStory && $storyImage && $igAccount && in_array($platformOption, ['both', 'instagram'])) {
                        $storyScheduled = $draftsOnly ? null : $scheduledAt->copy()->addMinutes(10);
                        SocialPost::create([
                            'group_id' => $group->id,
                            'social_account_id' => $igAccount->id,
                            'platform' => 'instagram',
                            'status' => $postStatus,
                            'post_type' => 'story',
                            'content' => $textResult['content'],
                            'hashtags' => [],
                            'image_url' => $storyImage['url'] ?? null,
                            'image_prompt' => $this->storyPrompt($topicData),
                            'metadata' => ['topic' => $topicData['topic'], 'cta' => $topicData['cta'], 'story' => true],
                            'scheduled_at' => $storyScheduled,
                            'ai_tokens_used' => 0,
                        ]);
                        $created++;
                    }

                    return true;
                }
            );
        }

        $this->newLine();
        $this->info("Created {$created} posts total.");

        return self::SUCCESS;
    }

    /**
     * Five distinct hook patterns. Picked randomly per post so the feed
     * doesn't feel like the same template on repeat. Each pattern is
     * spelled out so GPT stays on rails instead of defaulting to vague
     * "punchy opening" prose.
     */
    private array $hookPatterns = [
        'question' => "Începe cu o întrebare directă care lovește o frustrare reală a owner-ului/managerului (ex: 'Cât te costă un client care sună la 22:00 și nu răspunde nimeni?'). Nu întrebări retorice generice.",
        'stat' => "Începe cu o cifră surprinzătoare sau contrainutitivă (procent, sumă în lei/euro, timp). Cifra trebuie să fie plauzibilă, nu inventată grotesc.",
        'story' => "Începe cu un micro-scenariu concret, 1-2 propoziții: 'Vineri seara. Client pe site-ul tău de 4 minute. Niciun răspuns. Pleacă.' Ton cinematic, prezent.",
        'contrarian' => "Începe cu o afirmație care contrazice un clișeu popular ('Nu, AI-ul NU îți va înlocui echipa de suport. Dar...'). Construiește tensiune, apoi reașază.",
        'insight' => "Începe cu un adevăr nespus pe care doar cineva care a trăit problema îl știe ('Cei mai mulți clienți pierduți nu se plâng niciodată — pleacă în tăcere.'). Empatic, observațional.",
    ];

    private function generateText(GeminiContentService $gemini, array $topicData): ?array
    {
        $avoidance = \App\Models\SocialRejection::buildAvoidancePrompt('facebook');
        $hookKey = array_rand($this->hookPatterns);
        $hookInstruction = $this->hookPatterns[$hookKey];

        $prompt = ($avoidance ? $avoidance . "\n\n" : '')
            . "Scrii un post de social media pentru Sambla, pe Facebook/Instagram. Publicul e antreprenori și manageri români (IMM, e-commerce, servicii). Vorbesc românește zilnic, nu consumă conținut de marketing în engleză.\n\n"
            . "SUBIECT: {$topicData['topic']}\n"
            . "CALL TO ACTION: {$topicData['cta']}\n\n"
            . "PATTERN DE HOOK ({$hookKey}): {$hookInstruction}\n\n"
            . "STRUCTURĂ:\n"
            . "1. Hook (1-2 rânduri, după pattern-ul de mai sus).\n"
            . "2. Tensiune — expune problema concret, nu abstract. Dă un exemplu palpabil din viața unui business românesc.\n"
            . "3. Rezolvare — cum intră Sambla în scenă. O propoziție, nu un pitch.\n"
            . "4. CTA-ul clar: {$topicData['cta']} → sambla.ro\n\n"
            . "TON:\n"
            . "- Scrii ca un om, nu ca un copywriter. Propoziții scurte, naturale.\n"
            . "- EVITĂ absolut: 'revoluționar', 'inovator', 'game-changer', 'soluție completă', 'scalabilă', 'next-level', 'transformă modul în care', 'puterea AI-ului', expresii din decks corporate.\n"
            . "- EVITĂ anglicismele gratuite (game-changer, must-have, insights, engagement) când există echivalent românesc.\n"
            . "- Nu promite minuni. Subpromite, nu suprapromite.\n"
            . "- Dacă menționezi prețuri/procente, să fie plauzibile.\n\n"
            . "FORMAT:\n"
            . "- Max 120 cuvinte total.\n"
            . "- Emoji: 0-2, folosite doar dacă adaugă sens (nu decorativ).\n"
            . "- Fără hashtag-uri.\n"
            . "- Paragrafe scurte, cu spațiu între ele (citeste-se pe mobil).\n\n"
            . "BRAND SAMBLA:\n"
            . "- Platformă românească (hosting RO, GDPR, echipă din RO) de AI conversațional: chatbot + voicebot.\n"
            . "- Personalitate: direct, practic, anti-BS, ușor contrar. Nu 'silicon-valley hype'.\n"
            . "- Vorbește ca un fondator care construiește, nu ca un agent de vânzări.\n\n"
            . 'Returnează JSON: {"content": "textul postării"}';

        try {
            $response = \OpenAI\Laravel\Facades\OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'Ești expert în social media marketing pentru branduri tech/SaaS. Generezi conținut concis, orientat spre conversii. Răspunzi în JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 500,
                'temperature' => 0.85,
                'response_format' => ['type' => 'json_object'],
            ]);

            $text = $response->choices[0]->message->content ?? '';
            $parsed = json_decode($text, true) ?: [];
            $tokens = ($response->usage->promptTokens ?? 0) + ($response->usage->completionTokens ?? 0);

            return [
                'content' => $parsed['content'] ?? $text,
                'hashtags' => [],
                'tokens_used' => $tokens,
            ];
        } catch (\Throwable $e) {
            $this->error("  Text generation failed: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Five distinct visual aesthetics. Picked randomly so the grid doesn't
     * look like the same minimal-white-icon template on repeat. Each one
     * is a complete stylistic brief — not just a keyword.
     */
    private array $visualStyles = [
        'editorial_photo' => "Editorial photography style. Real scene, cinematic lighting, shallow depth of field. Think high-end business magazine cover (Fast Company, Wired). Natural colors, one red accent object in frame. Human element if possible (hands, silhouette, workspace). NO illustrations, NO icons. Photo must look believable.",
        'flat_illustration' => "Flat vector illustration, thick lines, 2-color palette (white + Sambla red #dc2626, maybe one muted grey). Large shapes, no gradients, playful but premium. Think Stripe/Linear marketing site illustrations. Generous negative space.",
        'isometric_3d' => "Isometric 3D render, soft ambient occlusion, pastel tones with red (#dc2626) as the single saturated accent. Clean geometric objects arranged in a small diorama. Think Apple keynote slides. Soft shadows, no harsh lighting.",
        'abstract_geometric' => "Abstract geometric composition. Bold shapes (circles, rectangles, arrows) in a rhythmic layout. Limited palette: off-white background, black, Sambla red. Swiss design / Bauhaus feel. Strong hierarchy, mathematical precision.",
        'product_mockup' => "Realistic device mockup (phone OR laptop screen) showing a simple clean chat/voice interface. Soft studio lighting, neutral background, subtle reflections. One red accent element. Think Apple product photography. No visible text on screen beyond 1-2 short UI labels.",
    ];

    private function generateCtaImage(GeminiContentService $gemini, array $topicData): ?array
    {
        $imageRejections = \App\Models\SocialRejection::query()
            ->whereIn('reason_category', ['image', 'visual', 'design'])
            ->latest()->limit(10)->pluck('feedback')->filter()->unique()->take(5)->implode(' | ');
        $avoidLine = $imageRejections ? "CRITICAL - AVOID what user rejected before: {$imageRejections}. " : '';

        $styleKey = array_rand($this->visualStyles);
        $styleBrief = $this->visualStyles[$styleKey];

        $prompt = $avoidLine
            . "Create a premium social media graphic for Sambla (Romanian AI platform). "
            . "STYLE ({$styleKey}): {$styleBrief} "
            . "SUBJECT METAPHOR: {$topicData['image_concept']} "
            . "TEXT ON IMAGE: Maximum 3 words total, displayed cleanly as a headline: '{$topicData['visual_text']}'. "
            . "STRICT TEXT RULES: "
            . "- DO NOT write sentences, paragraphs, CTAs, URLs, or descriptions on the image. "
            . "- DO NOT write the topic explanation on the image. "
            . "- If text appears, it's only the 3-word headline above. "
            . "BRAND LOGO: Place the Sambla logo (attached reference) in a top corner, sized small, with a subtle dark backing so it's readable on any background. "
            . "COMPOSITION: Strong focal point, clear visual hierarchy, feels designed (not AI-slop). Generous whitespace. Premium, not busy. "
            . "FORBIDDEN: stock photo clichés (handshakes, smiling people pointing at laptops), generic chat bubbles floating in space, cluttered 'infographic' layouts, gradient rainbows, fake testimonials, random startup buzzwords on screen. "
            . "ASPECT: 3:4 portrait for social feed.";

        return $gemini->generateImage($prompt, '3:4');
    }

    private function storyPrompt(array $topicData): string
    {
        return "Create a MINIMAL Instagram STORY graphic (9:16 portrait). "
            . "HEADLINE (max 3 words): '{$topicData['visual_text']}' "
            . "VISUAL: {$topicData['image_concept']} "
            . "- Full-bleed vertical composition, large central visual element, generous top/bottom safe zones "
            . "- Sambla logo (attached) in top-left with dark backing "
            . "- White/light background, red (#dc2626) accents only "
            . "- Premium, Apple-level minimalism, no long text blocks";
    }

    private function generateStoryImage(GeminiContentService $gemini, array $topicData): ?array
    {
        return $gemini->generateImage($this->storyPrompt($topicData), '9:16');
    }
}
