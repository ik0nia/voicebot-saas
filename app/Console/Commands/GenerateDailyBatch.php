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
     * Tiny seed list of REAL Sambla features (mirrors home.blade.php).
     * Used only as raw material — every topic, CTA and visual concept
     * is generated FRESH by the AI on each call so the feed never repeats.
     */
    private array $featureSeeds = [
        'Bază de cunoștințe inteligentă: încarci PDF, DOCX, CSV sau link de site, AI-ul citește și organizează singur.',
        'Voicebot cu voce naturală în română, numere RO, transcriere live, sentiment analizat în timpul apelului.',
        'Chat widget premium: dark mode, carduri produse, link preview, asistență proactivă pe pagini de produs.',
        'Integrare WooCommerce nativă: căutare semantică, verificare stoc live, add-to-cart din chat, tracking AWB.',
        'Pipeline de lead-uri: captare automată, scoring, stadii nou → contactat → calificat → programat → câștigat.',
        'Programări și callback-uri pe pilot automat: verifică agenda, confirmă, trimite reminder.',
        'Anti-halucinare: răspunde DOAR din datele tale, nu inventează prețuri sau termene.',
        'Detectează întrebările fără răspuns și generează FAQ-uri automat ca să închidă gap-urile.',
        'Sentiment analizat live: alertă instant când un client e supărat sau foarte mulțumit.',
        'Escaladare inteligentă la operator uman când conversația devine prea complexă sau sensibilă.',
        'Voce + Chat, același creier: clientul începe pe site, continuă la telefon, contextul se păstrează.',
        'Dashboard live cu health score per bot, gap analysis, recomandări automate de conținut.',
        'Hosting 100% în România, GDPR by default, date izolate per cont, fără transfer în afara UE.',
        'Setup în minute, fără cod: încarci documentele, personalizezi tonul, ești live.',
        'Funcționează 24/7, zero pauze, zero clienți pierduți pentru că „nu a răspuns nimeni".',
        'Migrare ușoară de pe alt chatbot: importăm baza de cunoștințe existentă fără downtime.',
        'Reduce munca repetitivă a echipei: status comandă, retururi, FAQ-uri — toate automate.',
        'Răspunsuri cu surse: arată din ce document provine fiecare informație. Verificabil, nu black-box.',
    ];

    /**
     * Ask the AI for ONE fresh post idea built around two random feature
     * seeds. Returns the same shape the rest of the pipeline expects.
     */
    private function generateTopicIdea(): ?array
    {
        $seeds = collect($this->featureSeeds)->shuffle()->take(2)->values()->all();
        $seedsBlock = "- " . implode("\n- ", $seeds);

        $prompt = "Ești copywriter pentru Sambla, platformă românească de chatbot și voicebot AI pentru afaceri mici și mijlocii (e-commerce, servicii). Audiența: antreprenori și manageri români care nu sunt tehnici. Tonul: prietenos, cald, direct, fără jargon corporate.\n\n"
            . "Inspiră-te din UNA dintre aceste funcționalități reale (poți combina două dacă se leagă natural):\n{$seedsBlock}\n\n"
            . "Generează o IDEE NOUĂ și concretă de postare. Pune accent pe BENEFICIUL pentru proprietarul afacerii (timp câștigat, clienți câștigați, bani economisiți, liniște), nu pe descrieri tehnice. Fără clișee.\n\n"
            . "Returnează DOAR JSON valid, exact în acest format:\n"
            . '{"topic":"o propoziție-două care descriu unghiul postării — concret, nu generic","cta":"un îndemn scurt în română (2-4 cuvinte)","visual_text":"1-3 cuvinte SCURTE și UZUALE în română (fără cratimă, fără cifre lungi, fără termeni tehnici); ceva ce orice om înțelege instant — ex: «mai mulți clienți», «zero stres», «răspunde mereu»","image_concept":"o scenă vizuală bogată (în engleză, pentru generatorul de imagini): mediu real, mockup de device, diorama 3D sau ilustrație flat — descrie mediul, lumina, obiectele. NICIODATĂ «simple icon on white background», «minimal flat icon», «clean white with one icon». Vrem scene cu profunzime și caracter."}';

        try {
            $response = \OpenAI\Laravel\Facades\OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'Generezi idei de postări social media pentru un brand românesc de AI conversațional. Răspunzi exclusiv în JSON valid, în limba română pentru text și engleză pentru image_concept.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 500,
                'temperature' => 0.95,
                'response_format' => ['type' => 'json_object'],
            ]);

            $raw = $response->choices[0]->message->content ?? '';
            $parsed = json_decode($raw, true) ?: [];

            if (empty($parsed['topic']) || empty($parsed['cta'])) {
                return null;
            }

            // Hard cap visual_text to 3 short words — graphics fail with
            // longer strings and we want catchy, not paragraphs.
            $visualText = trim((string) ($parsed['visual_text'] ?? ''));
            $words = preg_split('/\s+/', $visualText) ?: [];
            if (count($words) > 3) {
                $visualText = implode(' ', array_slice($words, 0, 3));
            }

            return [
                'topic' => $parsed['topic'],
                'cta' => $parsed['cta'],
                'visual_text' => $visualText ?: 'mai mulți clienți',
                'image_concept' => $parsed['image_concept'] ?? 'A warm cinematic editorial photo of a Romanian small business owner happy at their desk, soft window light, depth of field.',
            ];
        } catch (\Throwable $e) {
            $this->warn("  Topic idea generation failed: {$e->getMessage()}");
            return null;
        }
    }

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

        // For draft buffer generation we don't actually schedule anything,
        // so the time window is irrelevant — just give it a sane fake span
        // so the rest of the loop doesn't divide by zero. This unblocks
        // generation in the evening / night when the default 09:00-20:00
        // window is already in the past.
        if ($draftsOnly && $endTime->lte($startTime)) {
            $endTime = $startTime->copy()->addMinutes(max(60, $count * 5));
        }

        if ($endTime->lte($startTime)) {
            $this->error("End time {$untilTime} is before start time {$startTime->format('H:i')} on {$targetDate->toDateString()}.");
            return self::FAILURE;
        }

        $minutesAvailable = $startTime->diffInMinutes($endTime);
        $interval = (int) floor($minutesAvailable / max(1, $count));

        $this->info("Generating {$count} posts on {$targetDate->toDateString()}, every ~{$interval} min from {$startTime->format('H:i')} to {$endTime->format('H:i')}");
        $this->newLine();

        $gemini = app(GeminiContentService::class);

        $created = 0;
        for ($i = 0; $i < $count; $i++) {
            $topicData = $this->generateTopicIdea();
            if (!$topicData) {
                $this->warn("Skipping post " . ($i + 1) . ": topic idea generation failed");
                continue;
            }

            $scheduledAt = $startTime->copy()->addMinutes($interval * ($i + 1));
            $this->components->task(
                "Post " . ($i + 1) . "/{$count} @ {$scheduledAt->format('H:i')} — {$topicData['visual_text']}",
                function () use ($i, $topicData, $scheduledAt, $gemini, $fbAccount, $igAccount, $platformOption, $dryRun, $draftsOnly, &$created) {
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
            . "Scrii un post de social media pentru Sambla, pe Facebook/Instagram. Publicul: antreprenori și manageri români din IMM, e-commerce, servicii — oameni ocupați, NU tehnici. Vorbesc românește zilnic.\n\n"
            . "SUBIECT: {$topicData['topic']}\n"
            . "CALL TO ACTION: {$topicData['cta']}\n\n"
            . "PATTERN DE HOOK ({$hookKey}): {$hookInstruction}\n\n"
            . "STRUCTURĂ (fiecare element pe RÂNDUL LUI, cu LINIE GOALĂ între blocuri ca să respire pe mobil):\n"
            . "1. Hook scurt (1-2 rânduri).\n"
            . "2. Problema reală: un exemplu palpabil din viața unui business românesc, în 2-3 rânduri scurte.\n"
            . "3. Cum ajută Sambla — DOAR în limbaj prietenos, NU tehnic. Spune CE CÂȘTIGĂ omul (timp, clienți, liniște, bani), nu CUM funcționează tehnologia. Maxim 2 rânduri.\n"
            . "4. (Opțional) O listă scurtă de 2-3 beneficii concrete, fiecare pe rândul lui, cu un emoji la început (ex: ✅ răspunde și noaptea / 🧘 echipa ta respiră / 💰 lead-uri captate singur).\n"
            . "5. CTA prietenos: {$topicData['cta']} → sambla.ro\n\n"
            . "TON:\n"
            . "- Cald, prietenos, conversațional. Ca și cum i-ai povesti unui prieten antreprenor.\n"
            . "- Propoziții scurte. Cuvinte UZUALE în română — orice cuvânt rar sau tehnic îl înlocuiești cu unul simplu.\n"
            . "- Pune BENEFICIUL în prim-plan, nu funcționalitatea.\n"
            . "- INTERZIS: «revoluționar», «inovator», «game-changer», «soluție completă», «scalabil», «next-level», «transformă modul în care», «puterea AI-ului», «empowering», «insights», «engagement», «leverage», «seamless», anglicisme gratuite, jargon corporate.\n"
            . "- Subpromite, nu suprapromite. Plauzibil.\n\n"
            . "FORMAT VIZUAL (foarte important — postul trebuie să arate ‘airy’ pe mobil):\n"
            . "- Max 110 cuvinte total.\n"
            . "- Emoji: 4-7 emoji-uri, distribuite natural prin text — NU înghesuite la final. Folosește emoji care au sens (📞 ☎️ 💬 💼 🛒 ⏰ 🌙 ✅ 🧠 🤝 🚀 🎯 ⚡ 💡 🇷🇴). Nu decorativ pur.\n"
            . "- Paragrafe foarte scurte (1-3 rânduri), separate prin LINIE GOALĂ (\\n\\n).\n"
            . "- Lista de beneficii cu emoji la început, fiecare pe linie nouă.\n"
            . "- FĂRĂ hashtag-uri (zero, nici măcar la final).\n"
            . "- FĂRĂ link-uri brute, doar mențiunea «sambla.ro» în CTA.\n\n"
            . "BRAND SAMBLA (folosește, nu cita textual):\n"
            . "- Platformă românească (hosting în România, GDPR, echipă RO) de chatbot și voicebot AI.\n"
            . "- Funcționalități reale pe care le poți menționa NETEHNIC: răspunde 24/7, învață din documentele tale (PDF, contracte, FAQ-uri), preia apeluri telefonice cu voce naturală, captează lead-uri, programări automate, integrare cu magazinul WooCommerce, alertă când un client e supărat, escaladare la om când e cazul.\n"
            . "- Vorbește ca un fondator prietenos, nu ca un agent de vânzări.\n\n"
            . 'Returnează DOAR JSON: {"content": "textul postării cu \\n\\n între paragrafe"}';

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
        'editorial_photo' => "Editorial photography, magazine-cover quality. Real scene with depth — workspace, café, shop, home office. Cinematic lighting, golden hour or soft window light, shallow depth of field, natural colors with ONE red accent object (a phone, a notebook, a chair). Human presence implied through hands, silhouette, half-frame face. Believable, lived-in. NEVER icons or vectors.",
        'flat_illustration' => "Bold flat vector illustration, thick confident lines, 3-color palette (off-white + Sambla red #dc2626 + slate #1e293b). Characters with personality, real props (laptops, phones, packages, calendars). Stripe/Notion marketing-illustration quality. Generous negative space. Playful but premium. NEVER simple icons floating.",
        'isometric_3d' => "Isometric 3D render of a small inhabited diorama — a tiny shop interior, a desk with monitors, a mini call-center, a cozy office. Soft ambient occlusion, pastel base tones with red (#dc2626) as the single saturated accent on one hero object. Multiple props, depth, perspective. Apple-keynote / Vectary quality. NEVER a single icon on empty background.",
        'abstract_geometric' => "Bauhaus-style abstract geometric composition. Layered overlapping shapes with rhythm — bold circles, arcs, rectangles, diagonal lines. Off-white background, deep slate, Sambla red. Swiss-design hierarchy with one strong focal point. Designed, not random. Mathematical precision. NEVER a centered icon.",
        'product_mockup' => "Photorealistic device mockup in a real environment — a phone in a hand at a café, a laptop on a wooden desk with coffee and plant, a desk phone in a warm office. Screen shows a subtle clean Sambla chat or voice UI with red accent. Soft studio + ambient lighting, depth of field, lifestyle product photography, not floating-on-white.",
        'cinematic_workspace' => "Cinematic over-shoulder workspace shot. Real Romanian small-business setting — a flower shop, a small clinic, a boutique, a workshop. Owner working on a laptop or phone, soft warm light, shallow focus, one red accent in frame (a mug, a notebook). Documentary, lifestyle, believable. NEVER staged stock photo.",
    ];

    private function generateCtaImage(GeminiContentService $gemini, array $topicData): ?array
    {
        $imageRejections = \App\Models\SocialRejection::query()
            ->whereIn('reason_category', ['image', 'visual', 'design'])
            ->latest()->limit(10)->pluck('feedback')->filter()->unique()->take(5)->implode(' | ');
        $avoidLine = $imageRejections ? "CRITICAL — AVOID what user rejected before: {$imageRejections}. " : '';

        $styleKey = array_rand($this->visualStyles);
        $styleBrief = $this->visualStyles[$styleKey];

        $prompt = $avoidLine
            . "Create a premium 3:4 social media graphic for Sambla (Romanian AI chat & voice bot platform). "
            . "STYLE ({$styleKey}): {$styleBrief} "
            . "SUBJECT: {$topicData['image_concept']} "
            . "TEXT ON IMAGE — VERY STRICT: Render EXACTLY this short Romanian phrase, MAX 3 words: '{$topicData['visual_text']}'. "
            . "Display it clean and large as a single headline. NO other text, NO sentences, NO URLs, NO CTAs, NO topic explanation, NO captions. If you can't render Romanian diacritics cleanly, SKIP TEXT ENTIRELY rather than show garbled letters. "
            . "BRAND LOGO: Place the Sambla logo (attached reference) in a top corner, sized small, with a subtle backing so it stays readable. "
            . "COMPOSITION: Strong focal point, clear hierarchy, feels designed (not AI-slop). Generous whitespace. Premium, not busy. "
            . "ABSOLUTELY FORBIDDEN: a single simple icon centered on a white/empty background; minimalist clip-art; cliché stock photos (handshakes, people in suits pointing at laptops, smiling diverse team); generic floating chat bubbles in empty space; cluttered 'infographic' layouts; gradient rainbows; fake testimonials; random startup buzzwords on screen. "
            . "ASPECT: 3:4 portrait for social feed.";

        return $gemini->generateImage($prompt, '3:4');
    }

    private function storyPrompt(array $topicData): string
    {
        $styleKey = array_rand($this->visualStyles);
        $styleBrief = $this->visualStyles[$styleKey];

        return "Create a 9:16 vertical Instagram STORY graphic for Sambla (Romanian AI chat & voice bot platform). "
            . "STYLE ({$styleKey}): {$styleBrief} "
            . "SUBJECT: {$topicData['image_concept']} "
            . "TEXT — VERY STRICT: Render EXACTLY this short Romanian phrase, MAX 3 words: '{$topicData['visual_text']}'. "
            . "One clean headline, NO other text, NO captions, NO URLs. If diacritics can't be rendered cleanly, SKIP TEXT entirely. "
            . "Full-bleed vertical composition, large hero element, generous top/bottom safe zones (Instagram UI overlays). "
            . "Sambla logo (attached) in top corner with subtle backing. "
            . "ABSOLUTELY FORBIDDEN: a single icon on white background, clip-art minimalism, stock-photo clichés, garbled text. "
            . "ASPECT: 9:16 portrait, premium editorial quality.";
    }

    private function generateStoryImage(GeminiContentService $gemini, array $topicData): ?array
    {
        return $gemini->generateImage($this->storyPrompt($topicData), '9:16');
    }
}
