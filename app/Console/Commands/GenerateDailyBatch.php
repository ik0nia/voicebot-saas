<?php

namespace App\Console\Commands;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\Social\GeminiContentService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateDailyBatch extends Command
{
    protected $signature = 'social:generate-batch
                            {count=10 : Number of posts to generate}
                            {--until=20:00 : Schedule posts until this time (HH:MM)}
                            {--platform=both : facebook, instagram, or both}
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

        $fbAccount = SocialAccount::where('platform', 'facebook')->where('is_active', true)->first();
        $igAccount = SocialAccount::where('platform', 'instagram')->where('is_active', true)->first();

        if ($platformOption === 'both' && (!$fbAccount || !$igAccount)) {
            $this->warn('Not all accounts configured. Using available ones.');
        }

        $now = Carbon::now();
        $endTime = Carbon::today()->setTimeFromTimeString($untilTime);
        if ($endTime->lte($now)) {
            $this->error("End time {$untilTime} is in the past.");
            return self::FAILURE;
        }

        $minutesAvailable = $now->diffInMinutes($endTime);
        $interval = (int) floor($minutesAvailable / $count);

        $this->info("Generating {$count} posts, scheduled every ~{$interval} min until {$untilTime}");
        $this->newLine();

        $gemini = app(GeminiContentService::class);
        $topics = collect($this->ctaTopics)->shuffle()->take($count)->values();

        $created = 0;
        foreach ($topics as $i => $topicData) {
            $scheduledAt = $now->copy()->addMinutes($interval * ($i + 1));
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

                    // Create Facebook post
                    if (in_array($platformOption, ['both', 'facebook']) && $fbAccount) {
                        SocialPost::create([
                            'social_account_id' => $fbAccount->id,
                            'platform' => 'facebook',
                            'status' => 'scheduled',
                            'post_type' => 'post',
                            'content' => $textResult['content'],
                            'hashtags' => $textResult['hashtags'] ?? [],
                            'image_url' => $image['url'] ?? null,
                            'image_prompt' => $topicData['image_concept'],
                            'metadata' => ['topic' => $topicData['topic'], 'cta' => $topicData['cta']],
                            'scheduled_at' => $scheduledAt,
                            'ai_tokens_used' => $textResult['tokens_used'] ?? 0,
                        ]);
                        $created++;
                    }

                    // Create Instagram post (only if we have an image)
                    if (in_array($platformOption, ['both', 'instagram']) && $igAccount && $image) {
                        SocialPost::create([
                            'social_account_id' => $igAccount->id,
                            'platform' => 'instagram',
                            'status' => 'scheduled',
                            'post_type' => 'post',
                            'content' => $textResult['content'],
                            'hashtags' => $textResult['hashtags'] ?? [],
                            'image_url' => $image['url'] ?? null,
                            'image_prompt' => $topicData['image_concept'],
                            'metadata' => ['topic' => $topicData['topic'], 'cta' => $topicData['cta']],
                            'scheduled_at' => $scheduledAt->copy()->addMinutes(5), // 5 min after FB
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

    private function generateText(GeminiContentService $gemini, array $topicData): ?array
    {
        $prompt = "Generează un post social media SCURT și PUTERNIC pentru Facebook/Instagram.\n\n"
            . "SUBIECT: {$topicData['topic']}\n\n"
            . "CALL TO ACTION: {$topicData['cta']}\n\n"
            . "REGULI:\n"
            . "- Max 150 cuvinte\n"
            . "- Primul rând: hook puternic (întrebare sau statistică)\n"
            . "- Ton: profesional dar accesibil, direct\n"
            . "- Termină cu CTA clar: {$topicData['cta']} → sambla.ro\n"
            . "- Limba: română\n"
            . "- Emoji-uri: moderate (2-4 per post)\n\n"
            . "BRAND: Sambla — platformă românească de AI conversațional (chatbot + voicebot) pentru business-uri.\n\n"
            . 'Returnează JSON: {"content": "textul postării", "hashtags": ["tag1", "tag2", "tag3"]}';

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
                'hashtags' => $parsed['hashtags'] ?? [],
                'tokens_used' => $tokens,
            ];
        } catch (\Throwable $e) {
            $this->error("  Text generation failed: {$e->getMessage()}");
            return null;
        }
    }

    private function generateCtaImage(GeminiContentService $gemini, array $topicData): ?array
    {
        $prompt = "Create a MINIMAL social media graphic. "
            . "CRITICAL RULES: "
            . "- MAXIMUM 3-5 words of text on the ENTIRE image. Only show: '{$topicData['visual_text']}' "
            . "- DO NOT add paragraphs, sentences, or descriptions as text on the image "
            . "- DO NOT write the CTA text on the image "
            . "- The image should be 90% VISUAL, 10% text "
            . "- Use ONE strong visual metaphor/icon as the hero element "
            . "VISUAL: {$topicData['image_concept']} "
            . "BRAND: Include the Sambla logo (attached) in top-left corner with dark backing "
            . "COLORS: White/light background, red (#dc2626) accents only "
            . "STYLE: Apple-level minimalism, premium, clean, lots of whitespace "
            . "ASPECT: Portrait format for social media feed";

        return $gemini->generateImage($prompt, '3:4');
    }
}
