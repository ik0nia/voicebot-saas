<?php

namespace App\Console\Commands;

use App\Models\SocialPost;
use App\Services\Social\GeminiContentService;
use Illuminate\Console\Command;

class RegenerateSocialImages extends Command
{
    protected $signature = 'social:regenerate-images
        {--status=* : Filter by status (draft, scheduled). Defaults to both}
        {--limit=0 : Max posts to process (0 = all)}
        {--only-openai : Only regenerate images from OpenAI (filename starts with openai_)}
        {--dry-run : Show what would be regenerated without doing it}
        {--sleep=5 : Seconds between API calls to avoid rate limits}';

    protected $description = 'Regenerate images for existing social posts using current style presets and model';

    public function handle(): int
    {
        $statuses = $this->option('status') ?: ['draft', 'scheduled'];
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');
        $sleep = (int) $this->option('sleep');

        $query = SocialPost::whereIn('status', $statuses)
            ->whereIn('post_type', ['post', 'story'])
            ->orderBy('scheduled_at')
            ->orderBy('id');

        if ($this->option('only-openai')) {
            // OpenAI images + posts without any image
            $query->where(function ($q) {
                $q->where('image_url', 'LIKE', '%openai_%')
                  ->orWhereNull('image_url')
                  ->orWhere('image_url', '');
            });
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $posts = $query->get();
        $this->info("Found {$posts->count()} posts to regenerate.");

        if ($dryRun) {
            foreach ($posts as $post) {
                $this->line("  [{$post->id}] {$post->platform}/{$post->post_type} — {$post->status} — " . mb_substr($post->content, 0, 60));
            }
            return self::SUCCESS;
        }

        $gemini = new GeminiContentService();
        $processed = 0;
        $failed = 0;
        $grouped = [];

        // Group posts by group_id so siblings share the same image
        foreach ($posts as $post) {
            $key = $post->group_id ?? "solo_{$post->id}";
            $grouped[$key][] = $post;
        }

        $this->info("Processing " . count($grouped) . " groups...");

        foreach ($grouped as $groupKey => $groupPosts) {
            $feedImage = null;
            $storyImage = null;

            foreach ($groupPosts as $post) {
                $isStory = $post->post_type === 'story';
                $aspectRatio = $isStory ? '9:16' : '4:5';

                // Reuse image within the same group for same aspect ratio
                if (!$isStory && $feedImage) {
                    $post->update(['image_url' => $feedImage]);
                    $this->line("  [{$post->id}] {$post->platform}/{$post->post_type} — reused sibling image");
                    $processed++;
                    continue;
                }
                if ($isStory && $storyImage) {
                    $post->update(['image_url' => $storyImage]);
                    $this->line("  [{$post->id}] {$post->platform}/story — reused sibling image");
                    $processed++;
                    continue;
                }

                // Build prompt from post content/metadata
                $metadata = $post->metadata ?? [];
                $topic = $metadata['topic'] ?? '';
                $concept = $post->image_prompt ?? $topic;

                if (!$concept) {
                    // Fallback: derive concept from post content
                    $concept = mb_substr(strip_tags($post->content), 0, 200);
                }

                $styles = config('social-image-styles');
                $styleKey = array_rand($styles);
                $preset = $styles[$styleKey];

                $category = $metadata['category'] ?? null;
                $isVerticale = $category === 'verticale';

                $prompt = $this->buildDetailedPrompt($category, $metadata, $topic, $concept, $aspectRatio, $preset);

                $this->line("  [{$post->id}] {$post->platform}/{$post->post_type} — generating ({$preset['name']})...");

                $result = $gemini->generateImage($prompt, $aspectRatio);

                if ($result && !empty($result['url'])) {
                    $post->update(['image_url' => $result['url']]);

                    if ($isStory) {
                        $storyImage = $result['url'];
                    } else {
                        $feedImage = $result['url'];
                    }

                    $processed++;
                    $this->info("    ✓ saved: {$result['url']}");
                } else {
                    $failed++;
                    $this->error("    ✗ generation failed");
                }

                // Rate limit
                if ($sleep > 0) {
                    sleep($sleep);
                }
            }
        }

        $this->newLine();
        $this->info("Done. Processed: {$processed}, Failed: {$failed}");

        return self::SUCCESS;
    }

    /**
     * Build a rich, detailed image prompt based on category and topic.
     * Pro model produces dramatically better results with specific scene
     * descriptions, lighting, color palettes, and composition direction.
     */
    private function buildDetailedPrompt(
        ?string $category,
        array $metadata,
        string $topic,
        string $concept,
        string $aspectRatio,
        array $preset
    ): string {
        $seed = $metadata['seed'] ?? '';

        // Extract niche name for verticale
        $niche = 'business';
        if ($category === 'verticale' && preg_match('/^([A-ZĂÂÎȘȚ\s\/]+):/u', $seed, $m)) {
            $niche = mb_strtolower(trim($m[1]));
        }

        // Niche-specific scene descriptions (concrete, visual, detailed)
        $nicheScenes = [
            'contabilitate' => "Un birou modern de contabilitate — monitor elegant cu dashboard financiar, dosare colorate aranjate estetic, un calculator premium, o cană de cafea. O tabletă pe birou arată interfața AI: chat cu client despre termen fiscal, status declarații cu checkmarks verzi. Floating holographic: calendar fiscal cu deadline-uri evidențiate, notificări automate. Lumină caldă de birou, tons de navy, gold și cream.",
            'cabinet avocatură' => "Un birou de avocat elegant — cărți de drept cu coperte din piele pe raft, un laptop premium, dosare sigilate pe blat de lemn închis. O tabletă arată AI chat: programare consultație, documente necesare listate automat. Floating: balanța justiției stilizată holografic, status dosare cu progress bars. Lumină ambientală caldă, tons burgundy, gold, navy. Seriozitate + modernitate.",
            'cabinet medical / stomatologic' => "Un cabinet medical modern, curat — un scaun stomatologic stilizat, instrumente medicale premium aranjate, ecran de monitorizare. O tabletă montată pe perete arată AI booking: slot-uri de programare cu pacienți, reminder-uri automate, instrucțiuni post-procedură trimise. Floating: calendar de programări plin, notificare 'Programare confirmată ✓'. Tons de alb, teal, mentă. Curat, profesional, încredere.",
            'service auto' => "Un atelier auto modern, premium — o cheie mecanică stilizată, piese auto aranjate artistic, un diagnostic digital pe ecran. O tabletă pe banc arată AI: status reparație comunicat clientului, programare ITP auto-confirmată, notificări piese în stoc. Floating: gauge-uri digitale, timeline reparație cu etape. Tons de slate, portocaliu industrial, electric blue. Robust dar high-tech.",
            'salon beauty / frizerie' => "Un salon de beauty glamour — oglindă rotundă cu ring light, produse premium pe blat de marmură, foarfece gold, pensule de machiaj aranjate artistic. O tabletă rose-gold arată interfața AI: programări cu checkmarks, chat 'Vreau o programare la 14:00', reminder trimis automat. Floating: calendar salon auto-completat, notificare 'Rezervare nouă!', analytics 'No-show -60%'. Tons rose gold, burgundy, cream, soft gold. Glamour meets tech.",
            'agenție imobiliară' => "Un showroom imobiliar modern — machete de clădiri, planuri de apartament pe masă, o lupă stilizată peste o hartă. O tabletă arată AI: conversație cu prospect despre zonă și buget, lead qualification auto, vizionare programată. Floating: pin-uri de locație holografice, carduri de proprietăți cu prețuri. Tons de emerald, gold, cream. Profesional, aspirațional.",
            'restaurant / delivery' => "O masă de restaurant fotografiată overhead — farfurii apetisante cu mâncare colorată (paste, salată, vin), blat de lemn cald. Între farfurii: UI holographic — chat bubble 'Vreau să comand', card confirmare cu checkmarks, timer livrare '15 min'. Tabletă pe margine cu AI chat meniu. Food photography caldă + tech cool teal. Tons warm wood, golden food tones, electric teal accents.",
            'cabinet psihologie / psihoterapie' => "Un cabinet de terapie serene — un fotoliu confortabil, o plantă verde, lumânări, cărți pe raft. O tabletă arată AI: programare sesiune, chat despre modalități (online/fizic), reminder automat. Floating: calendar cu sesiuni colorate, notificări line. Tons sage green, lavender, warm cream. Calm, safe space + discrete tech.",
            'școală de limbi / cursuri' => "O clasă modernă de limbi — steaguri colorate pe perete, cărți multilingve, litere floating decorative, un whiteboard digital. Tabletă cu AI: înscriere automată, testare nivel, status curs. Floating: badges de nivel (A1→C2), calendar cursuri, notificări. Tons vibrant blue, yellow, coral. Educațional, energic, fun.",
            'agenție de turism' => "Un birou de travel — valize colorate, bilete de avion stilizate, un glob pământesc, broșuri cu destinații. Tabletă cu AI: chat despre pachete, disponibilitate instant, documente necesare generate auto. Floating: avion stilizat, carduri destinații cu prețuri, calendar booking. Tons sky blue, coral, sand. Aventură + smart booking.",
            'clinică veterinară' => "Recepția unei clinici veterinare moderne, cozy — blat curat cu tabletă AI booking, silueta stilizată a unui pisoi pe tejghea, lăbuțe colorate decorative, jucării de animale. Pe tabletă: programări auto, chat 'Vreau programare vaccinare', instrucțiuni post-operatorii trimise automat. Floating: calendar cu slot-uri verzi, reminder vaccin, badge 'Programare confirmată'. Tons warm coral, soft teal, cream. Friendly, cald, profesional dar accesibil.",
            'pensiune / hotel mic' => "Recepția unei pensiuni charmante — cheie de cameră vintage pe blat de lemn, un aranjament floral, o carte de oaspeți. Tabletă cu AI: disponibilitate camere real-time, rezervare 24/7, chat despre facilități. Floating: calendar ocupare cu verde/roșu, notificare 'Rezervare nouă din chat'. Tons warm wood, forest green, cream. Ospitalier, rustic-modern.",
            'optică medicală' => "Un showroom de optică elegant — ochelari premium pe display, lentile stilizate, un test de vedere pe ecran. Tabletă cu AI: programare consultație, chat despre asigurări, recomandări personalizate. Floating: card-uri rame cu prețuri, calendar programări. Tons electric blue, gold, white. Precis, premium, profesional.",
            'firmă de curățenie / servicii la domiciliu' => "Un spațiu imaculat — produse de curățenie premium aranjate estetic, bule de săpun stilizate, o casă strălucitoare miniaturală. Tabletă cu AI: programare serviciu, chat zone deservite, confirmare automată. Floating: calendar cu slot-uri, notificare 'Comandă nouă!'. Tons fresh mint, electric blue, white. Curat, fresh, eficient.",
            'birou notarial' => "Un birou notarial solemn dar modern — sigiliu stilizat, ștampile, documente oficiale pe blat de lemn nobil. Tabletă cu AI: programare, acte necesare listate automat, tarife orientative. Floating: checklist documente, status dosar. Tons deep navy, gold, ivory. Autoritate + accesibilitate.",
        ];

        // Category-specific scene descriptions for non-verticale
        $categoryScenes = [
            'tehnologie' => "Un command center futuristic — monitoare holografice cu dashboard-uri AI, flux de date luminoase, noduri de rețea conectate. Accent pe: {$topic}. Stil: dark mode UI + Cinema 4D render. Background deep navy (#0f172a), accente red (#dc2626) și electric blue (#3b82f6). Glassmorphism panels, particule luminoase, lumini volumetrice.",
            'tehnologie_explicativ' => "O vizualizare abstractă dar elegantă a unui concept tech — diagrame interactive floating, conexiuni de date luminoase, UI mockup-uri transparente. Focus pe: {$topic}. Stil: data viz meets sci-fi concept art. Gradient purple-to-blue background, noduri luminoase, bloom effects. Educativ dar vizual impresionant.",
            'voce' => "Un telefon stilizat central cu unde sonore care emană — wave patterns colorate (verde calm → roșu emoție), gauge de sentiment, vizualizare audio real-time. Focus pe: {$topic}. Stil: Apple product shot meets Spotify Wrapped. Dark background, split lighting cald/rece. Volumetric light rays, motion blur pe unde. Cinematic, empatic.",
            'antihalucinare' => "Un scut digital protector — straturi concentrice de securitate în jurul unui core de date, checkmarks verzi, bariere luminoase care blochează erori roșii. Focus pe: {$topic}. Stil: cybersecurity meets clean SaaS. Dark gradient, accente de emerald green și amber warning. Shield glowing effect, particule.",
            'securitate' => "O fortăreață digitală — layers de protecție, lacăte holografice, GDPR badge stilizat, flux de date criptate. Focus pe: {$topic}. Stil: tech fortress aesthetic. Deep slate background, accente de electric green și gold. Straturi de glass panels, reflecții subtile.",
            'ecommerce' => "Un magazin digital premium — coș de cumpărături stilizat 3D, carduri de produse floating cu prețuri, un chat AI care recomandă produse, notificări de comandă nouă. Focus pe: {$topic}. Stil: Shopify showcase meets 3D render. Gradient warm-to-cool, accente purple și coral. Produse colorate, UI elements elegante.",
            'baza_cunostinte' => "O bibliotecă digitală futuristică — cărți și documente care se transformă în noduri de cunoaștere conectate, un search bar luminos în centru, vectori și embeddings vizualizate ca galaxie. Focus pe: {$topic}. Stil: knowledge graph meets interstellar. Deep space background, electric blue queries, warm amber results.",
            'servicii' => "Un dashboard de servicii clienți — queue management vizual, chat conversations active, satisfaction meters, response time gauges. Focus pe: {$topic}. Stil: modern SaaS dashboard, clean și funcțional dar frumos. Light theme cu accente de teal și coral. Cards cu shadow-uri soft, UI curat.",
            'platforma' => "Setup și configurare — un laptop premium cu interfața Sambla deschisă, indicatori de performanță, integrări vizualizate ca puzzle pieces care se conectează. Focus pe: {$topic}. Stil: product screenshot meets 3D scene. Gradient subtil, accente brand red și blue. Clean, inviting, 'easy to start'.",
            'caz_real' => "O scenă de success story — before/after vizualizare (stânga gri/stresant, dreapta colorat/organizat), metrici care cresc (grafice verzi), un telefon cu conversație AI reușită. Focus pe: {$topic}. Stil: editorial infographic meets 3D illustration. Contrastul vechi/nou e evident. Tons warm emerald pentru succes, soft grey pentru problema inițială.",
        ];

        if ($category === 'verticale' && isset($nicheScenes[$niche])) {
            $scene = $nicheScenes[$niche];
        } elseif ($category && isset($categoryScenes[$category])) {
            $scene = $categoryScenes[$category];
        } else {
            $scene = "Un vizual modern tech/SaaS — dashboard premium, elemente UI floating, glassmorphism, gradient bold. "
                . ($topic ? "Vizualizare concretă a: {$topic}. " : '')
                . "Stil: Stripe/Linear/Vercel aesthetic. Dark gradient cu accente red (#dc2626) și blue (#3b82f6).";
        }

        $orientationDesc = $aspectRatio === '9:16'
            ? 'VERTICAL 9:16 portrait (Instagram Story). Compoziție verticală full-bleed, element central bold, spațiu generos sus/jos pentru UI overlays.'
            : '4:5 portrait (Instagram feed optimal). Compoziție cu focal point central, negative space generos.';

        return "Create a scroll-stopping {$aspectRatio} social media image for Sambla (Romanian AI agents platform).\n\n"
            . "SCENE: {$scene}\n\n"
            . "COMPOSITION: {$orientationDesc} Shallow depth of field — sharp center, soft edges. Premium Dribbble/Behance quality. The image must make someone STOP scrolling.\n\n"
            . "TEXT IN IMAGE: Include a short bold headline in ROMANIAN (max 5 words) designed as part of the layout — on a frosted glass card, translucent banner, or pill-shaped element. White or dark text with strong contrast. If it doesn't fit beautifully, skip the text entirely.\n\n"
            . "CTA BUTTON (optional): A small styled button ('Află mai mult →' or 'Testează gratuit →') as a design element near the bottom.\n\n"
            . "RULES: NO people, NO faces, NO hands, NO logos, NO brand marks. Objects, tech, and scene elements ONLY. Magazine-cover quality.\n\n"
            . "ASPECT: EXACTLY {$aspectRatio}.";
    }
}
