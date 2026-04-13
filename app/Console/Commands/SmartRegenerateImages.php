<?php

namespace App\Console\Commands;

use App\Models\SocialPost;
use App\Services\Social\GeminiContentService;
use Illuminate\Console\Command;

/**
 * Tries to regenerate images hourly. If Vertex quota is exhausted
 * (all attempts fail), stops and waits for the next cron tick.
 * If it succeeds, keeps going until quota runs out or all done.
 */
class SmartRegenerateImages extends Command
{
    protected $signature = 'social:smart-regenerate
                            {--sleep=35 : Seconds between images}
                            {--batch=20 : Max images per run}
                            {--notify=codrut@ikonia.ro : Email after each batch}';

    protected $description = 'Regenerate OpenAI/missing images with quota-aware retry — runs from cron hourly';

    public function handle(): int
    {
        $sleep = (int) $this->option('sleep');
        $batch = (int) $this->option('batch');
        $email = $this->option('notify');

        // Quick quota check: try one image first
        $gemini = new GeminiContentService();
        $testResult = $gemini->generateImageDirect(
            'Create a simple 1:1 test image: a glowing blue sphere on dark background. Minimal, clean.',
            '1:1'
        );

        if (!$testResult) {
            $this->warn('Vertex quota still exhausted. Stopping — will retry next hour.');
            return self::SUCCESS;
        }

        $this->info('Quota OK. Starting regeneration...');

        // Get posts needing regeneration (OpenAI or missing images)
        $query = SocialPost::whereIn('status', ['draft', 'scheduled'])
            ->whereIn('post_type', ['post', 'story'])
            ->where(function ($q) {
                $q->where('image_url', 'LIKE', '%openai_%')
                  ->orWhereNull('image_url')
                  ->orWhere('image_url', '');
            })
            ->orderBy('scheduled_at')
            ->orderBy('id');

        $posts = $query->get();
        $this->info("Found {$posts->count()} posts needing regeneration.");

        if ($posts->isEmpty()) {
            $this->info('Nothing to regenerate. All done!');
            $this->notifyComplete($email);
            return self::SUCCESS;
        }

        // Group by group_id
        $grouped = [];
        foreach ($posts as $post) {
            $key = $post->group_id ?? "solo_{$post->id}";
            $grouped[$key][] = $post;
        }

        $this->info("Processing " . count($grouped) . " groups (batch limit: {$batch})...");

        $styles = config('social-image-styles');
        $processed = 0;
        $failed = 0;
        $consecutiveFails = 0;

        foreach ($grouped as $groupKey => $groupPosts) {
            if ($processed >= $batch) {
                $this->info("Batch limit reached ({$batch}). Will continue next run.");
                break;
            }

            $feedImage = null;
            $storyImage = null;

            foreach ($groupPosts as $post) {
                $isStory = $post->post_type === 'story';
                $aspectRatio = $isStory ? '9:16' : '4:5';

                // Reuse within group
                if (!$isStory && $feedImage) {
                    $post->update(['image_url' => $feedImage]);
                    continue;
                }
                if ($isStory && $storyImage) {
                    $post->update(['image_url' => $storyImage]);
                    continue;
                }

                $metadata = $post->metadata ?? [];
                $topic = $metadata['topic'] ?? '';
                $concept = $post->image_prompt ?? $topic;
                if (!$concept) {
                    $concept = mb_substr(strip_tags($post->content), 0, 200);
                }

                $styleKey = array_rand($styles);
                $preset = $styles[$styleKey];
                $category = $metadata['category'] ?? null;

                $prompt = $this->buildPrompt($category, $metadata, $topic, $concept, $aspectRatio, $preset);

                $this->line("  [{$post->id}] {$post->platform}/{$post->post_type} [{$category}]...");

                $result = $gemini->generateImageDirect($prompt, $aspectRatio);

                if ($result && !empty($result['url'])) {
                    $post->update(['image_url' => $result['url']]);
                    if ($isStory) $storyImage = $result['url'];
                    else $feedImage = $result['url'];

                    $processed++;
                    $consecutiveFails = 0;
                    $this->info("    ✅ {$result['url']}");
                } else {
                    $failed++;
                    $consecutiveFails++;
                    $this->error("    ❌ failed");

                    // 3 consecutive fails = quota exhausted, stop
                    if ($consecutiveFails >= 3) {
                        $this->warn("3 consecutive failures — quota likely exhausted. Stopping.");
                        break 2;
                    }
                }

                if ($sleep > 0) sleep($sleep);
            }
        }

        $this->newLine();
        $this->info("Done. Generated: {$processed}, Failed: {$failed}");

        // Remaining count
        $remaining = SocialPost::whereIn('status', ['draft', 'scheduled'])
            ->where(function ($q) {
                $q->where('image_url', 'LIKE', '%openai_%')
                  ->orWhereNull('image_url')
                  ->orWhere('image_url', '');
            })
            ->count();
        $this->info("Remaining to regenerate: {$remaining}");

        if ($processed > 0 && $email) {
            $this->notifyBatch($email, $processed, $remaining);
        }

        if ($remaining === 0) {
            $this->notifyComplete($email);
        }

        return self::SUCCESS;
    }

    private function notifyBatch(string $email, int $count, int $remaining): void
    {
        try {
            $posts = \DB::table('social_posts')
                ->whereIn('status', ['draft', 'scheduled'])
                ->where('platform', 'facebook')
                ->where('post_type', 'post')
                ->where('image_url', 'like', '%img_%')
                ->where('updated_at', '>', now()->subHours(2))
                ->orderByDesc('updated_at')
                ->limit($count)
                ->get(['id', 'content', 'image_url', 'metadata']);

            $html = "<h2>Sambla Social — {$count} imagini noi cu Gemini 3 Pro</h2>";
            $html .= "<p>Mai rămân de regenerat: <strong>{$remaining}</strong></p>";
            foreach ($posts as $p) {
                $meta = json_decode($p->metadata, true);
                $cat = $meta['category'] ?? '—';
                $html .= "<div style='margin:15px 0;padding:12px;border:1px solid #e2e8f0;border-radius:8px;'>";
                $html .= "<p style='font-size:12px;color:#64748b;font-weight:bold;'>#{$p->id} | {$cat}</p>";
                $html .= "<a href='{$p->image_url}'><img src='{$p->image_url}' style='max-width:400px;border-radius:8px;margin:8px 0;' /></a>";
                $html .= "<p style='font-size:13px;line-height:1.5;'>" . htmlspecialchars(mb_substr($p->content, 0, 200)) . "</p>";
                $html .= "</div>";
            }

            $this->sendMail($email, "Sambla: {$count} imagini noi Pro ({$remaining} rămase)", $html);
        } catch (\Throwable $e) {
            $this->error("Email failed: " . $e->getMessage());
        }
    }

    private function notifyComplete(string $email): void
    {
        $this->sendMail($email, 'Sambla Social: Toate imaginile au fost regenerate! ✅',
            '<h2>Toate imaginile au fost regenerate cu Gemini 3 Pro</h2><p>Nu mai sunt postări cu imagini OpenAI sau lipsă.</p>');
    }

    private function sendMail(string $to, string $subject, string $html): void
    {
        try {
            $msg = new \Symfony\Component\Mime\Email();
            $msg->from('noreply@sambla.ro')
                ->to($to)
                ->replyTo('servus@sambla.ro')
                ->subject($subject)
                ->html($html);

            $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
                'mail.sambla.ro', 587, true
            );
            $transport->setUsername('noreply@sambla.ro');
            $transport->setPassword('w3IAZCLSeNhOF7FmRNO4');

            $mailer = new \Symfony\Component\Mailer\Mailer($transport);
            $mailer->send($msg);
            $this->info("Email sent to {$to}");
        } catch (\Throwable $e) {
            $this->error("Send mail error: " . $e->getMessage());
        }
    }

    private function buildPrompt(?string $category, array $metadata, string $topic, string $concept, string $aspectRatio, array $preset): string
    {
        $seed = $metadata['seed'] ?? '';
        $niche = 'business';
        if ($category === 'verticale' && preg_match('/^([A-ZĂÂÎȘȚ\s\/]+):/u', $seed, $m)) {
            $niche = mb_strtolower(trim($m[1]));
        }

        $nicheScenes = [
            'contabilitate' => "Un birou modern de contabilitate — monitor elegant cu dashboard financiar, dosare colorate aranjate estetic, un calculator premium, o cană de cafea. O tabletă pe birou arată interfața AI: chat cu client despre termen fiscal, status declarații cu checkmarks verzi. Floating holographic: calendar fiscal cu deadline-uri evidențiate, notificări automate. Lumină caldă de birou, tons de navy, gold și cream.",
            'cabinet avocatură' => "Un birou de avocat elegant — cărți de drept cu coperte din piele pe raft, un laptop premium, dosare sigilate pe blat de lemn închis. O tabletă arată AI chat: programare consultație, documente necesare listate automat. Floating: balanța justiției stilizată holografic, status dosare cu progress bars. Lumină ambientală caldă, tons burgundy, gold, navy.",
            'cabinet medical / stomatologic' => "Un cabinet medical modern — scaun stomatologic stilizat, instrumente medicale premium, ecran de monitorizare. O tabletă pe perete arată AI booking: slot-uri programare, reminder-uri automate, instrucțiuni post-procedură. Floating: calendar programări, notificare 'Programare confirmată ✓'. Tons alb, teal, mentă. Curat, profesional.",
            'service auto' => "Un atelier auto modern — cheie mecanică stilizată, piese auto aranjate artistic, diagnostic digital pe ecran. Tabletă cu AI: status reparație, programare ITP auto-confirmată, notificări piese în stoc. Floating: gauge-uri digitale, timeline reparație. Tons slate, portocaliu industrial, electric blue.",
            'salon beauty / frizerie' => "Un salon de beauty glamour — oglindă rotundă cu ring light, produse premium pe marmură, foarfece gold, pensule aranjate artistic. Tabletă rose-gold cu AI: programări cu checkmarks, chat 'Vreau programare la 14:00', reminder automat. Floating: calendar auto-completat, 'Rezervare nouă!', 'No-show -60%'. Tons rose gold, burgundy, cream, soft gold.",
            'agenție imobiliară' => "Showroom imobiliar modern — machete clădiri, planuri apartament, lupă pe hartă. Tabletă cu AI: conversație prospect zonă/buget, lead qualification, vizionare programată. Floating: pin-uri locație holografice, carduri proprietăți. Tons emerald, gold, cream.",
            'restaurant / delivery' => "Masă restaurant overhead — farfurii apetisante (paste, salată, vin), blat lemn cald. UI holographic între farfurii: chat 'Vreau să comand', confirmare cu checkmarks, timer '15 min'. Tabletă cu AI chat meniu. Food photography caldă + tech teal. Tons warm wood, golden food, electric teal.",
            'cabinet psihologie / psihoterapie' => "Cabinet terapie serene — fotoliu confortabil, plantă verde, lumânări, cărți pe raft. Tabletă cu AI: programare sesiune, modalități online/fizic, reminder automat. Floating: calendar sesiuni colorate. Tons sage green, lavender, warm cream. Calm, safe space.",
            'școală de limbi / cursuri' => "Clasă modernă limbi — steaguri colorate pe perete, cărți multilingve, litere floating, whiteboard digital. Tabletă cu AI: înscriere automată, testare nivel, status curs. Floating: badges nivel A1→C2, calendar cursuri. Tons vibrant blue, yellow, coral.",
            'agenție de turism' => "Birou travel — valize colorate, bilete avion stilizate, glob pământesc, broșuri destinații. Tabletă cu AI: chat pachete, disponibilitate instant, documente auto. Floating: avion stilizat, carduri destinații. Tons sky blue, coral, sand.",
            'clinică veterinară' => "Recepție clinică veterinară modernă, cozy — blat curat cu tabletă AI booking, silueta pisoi pe tejghea, lăbuțe colorate, jucării animale. Pe tabletă: programări auto, chat 'Programare vaccinare', instrucțiuni post-op. Floating: calendar slot-uri verzi, reminder vaccin. Tons warm coral, soft teal, cream. Friendly, cald.",
            'pensiune / hotel mic' => "Recepție pensiune charmantă — cheie cameră vintage pe lemn, aranjament floral, carte oaspeți. Tabletă AI: disponibilitate real-time, rezervare 24/7, chat facilități. Floating: calendar ocupare verde/roșu, 'Rezervare nouă din chat'. Tons warm wood, forest green, cream.",
            'optică medicală' => "Showroom optică elegant — ochelari premium pe display, lentile stilizate, test vedere pe ecran. Tabletă AI: programare consultație, chat asigurări, recomandări. Floating: carduri rame cu prețuri, calendar programări. Tons electric blue, gold, white.",
            'firmă de curățenie / servicii la domiciliu' => "Spațiu imaculat — produse curățenie premium aranjate estetic, bule săpun stilizate, casă strălucitoare miniaturală. Tabletă AI: programare serviciu, zone deservite, confirmare automată. Floating: calendar slot-uri, 'Comandă nouă!'. Tons fresh mint, electric blue, white.",
            'birou notarial' => "Birou notarial solemn dar modern — sigiliu stilizat, ștampile, documente oficiale pe lemn nobil. Tabletă AI: programare, acte necesare listate auto, tarife orientative. Floating: checklist documente, status dosar. Tons deep navy, gold, ivory.",
        ];

        $categoryScenes = [
            'tehnologie' => "Command center futuristic — monitoare holografice cu dashboard-uri AI, flux date luminoase, noduri rețea conectate. Focus: {$topic}. Dark mode UI + Cinema 4D. Background deep navy (#0f172a), accente red (#dc2626) și electric blue (#3b82f6). Glassmorphism, particule luminoase.",
            'tehnologie_explicativ' => "Vizualizare abstractă elegantă concept tech — diagrame interactive floating, conexiuni date luminoase, UI mockup-uri transparente. Focus: {$topic}. Data viz meets sci-fi. Gradient purple-to-blue, noduri luminoase, bloom. Educativ dar impresionant.",
            'voce' => "Telefon stilizat central cu unde sonore colorate (verde calm → roșu emoție), gauge sentiment, vizualizare audio real-time. Focus: {$topic}. Apple product shot meets Spotify Wrapped. Dark background, split lighting. Volumetric rays, motion blur. Cinematic.",
            'antihalucinare' => "Scut digital protector — straturi concentrice securitate, checkmarks verzi, bariere luminoase blochează erori roșii. Focus: {$topic}. Cybersecurity meets clean SaaS. Dark gradient, emerald green și amber. Shield effect, particule.",
            'securitate' => "Fortăreață digitală — layers protecție, lacăte holografice, GDPR badge stilizat, date criptate. Focus: {$topic}. Deep slate, electric green și gold. Glass panels, reflecții.",
            'ecommerce' => "Magazin digital premium — coș cumpărături 3D, carduri produse floating cu prețuri, chat AI recomandă produse, notificări comandă. Focus: {$topic}. Shopify showcase meets 3D. Warm-to-cool gradient, purple și coral.",
            'baza_cunostinte' => "Bibliotecă digitală futuristică — cărți/documente devin noduri cunoaștere conectate, search bar luminos central, embeddings ca galaxie. Focus: {$topic}. Knowledge graph meets interstellar. Deep space, electric blue queries, warm amber results.",
            'servicii' => "Dashboard servicii clienți — queue management, chat active, satisfaction meters, response time gauges. Focus: {$topic}. Modern SaaS dashboard, clean și funcțional. Light theme, teal și coral. Cards soft shadow.",
            'platforma' => "Setup configurare — laptop premium cu interfața Sambla, indicatori performanță, integrări puzzle pieces. Focus: {$topic}. Product screenshot meets 3D. Gradient subtil, brand red și blue. Clean, inviting.",
            'caz_real' => "Success story — before/after (stânga gri/stresant, dreapta colorat/organizat), metrici care cresc, telefon cu conversație AI reușită. Focus: {$topic}. Editorial infographic meets 3D. Emerald succes, soft grey problemă.",
        ];

        if ($category === 'verticale' && isset($nicheScenes[$niche])) {
            $scene = $nicheScenes[$niche];
        } elseif ($category && isset($categoryScenes[$category])) {
            $scene = $categoryScenes[$category];
        } else {
            $scene = "Vizual modern tech/SaaS — dashboard premium, elemente UI floating, glassmorphism, gradient bold. "
                . ($topic ? "Vizualizare concretă a: {$topic}. " : '')
                . "Stripe/Linear/Vercel aesthetic. Dark gradient cu accente red (#dc2626) și blue (#3b82f6).";
        }

        $orientationDesc = $aspectRatio === '9:16'
            ? 'VERTICAL 9:16 portrait (Instagram Story). Compoziție verticală, element central bold, spațiu generos sus/jos.'
            : '4:5 portrait (Instagram feed). Focal point central, negative space generos, safe zones 10% pe margini.';

        return "Create a scroll-stopping {$aspectRatio} social media image for Sambla (Romanian AI agents platform).\n\n"
            . "SCENE: {$scene}\n\n"
            . "COMPOSITION: {$orientationDesc} Shallow depth of field. Premium Dribbble/Behance quality. Must make someone STOP scrolling.\n\n"
            . "TEXT IN IMAGE: Include a short bold headline in ROMANIAN (max 5 words) designed as part of the layout — frosted glass card, translucent banner, or pill element. Strong contrast. If it doesn't fit beautifully, skip text entirely.\n\n"
            . "CTA BUTTON (optional): Small styled button ('Află mai mult →' or 'Testează gratuit →') as design element near bottom.\n\n"
            . "RULES: NO people, NO faces, NO hands, NO logos, NO brand marks. Objects and tech ONLY. Magazine-cover quality.\n\n"
            . "ASPECT: EXACTLY {$aspectRatio}.";
    }
}
