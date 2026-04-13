<?php

namespace App\Console\Commands;

use App\Models\SocialPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;

class RewriteDraftCopyFromImage extends Command
{
    protected $signature = 'social:rewrite-from-image
        {--status=draft : Statuses to process}
        {--limit=0 : Max groups to process (0 = all)}
        {--dry-run : Show what would change without writing}';

    protected $description = 'Rewrite social post copy so it actually matches the generated image (uses GPT-4o vision)';

    public function handle(): int
    {
        $statuses = (array) $this->option('status');
        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');

        // Process one post per group_id (the FB post is canonical) and then
        // cascade the new copy to all siblings in the same group.
        $query = SocialPost::query()
            ->whereIn('status', $statuses)
            ->whereNotNull('image_url')
            ->whereNotNull('group_id')
            ->where('platform', 'facebook')
            ->where('post_type', 'post')
            ->orderBy('id');

        if ($limit > 0) $query->limit($limit);

        $posts = $query->get();
        $this->info("Processing {$posts->count()} draft groups");

        $ok = 0; $fail = 0;
        foreach ($posts as $post) {
            $label = "#{$post->id} g={$post->group_id}";
            $imgBytes = $this->fetchImage($post->image_url);
            if (!$imgBytes) {
                $this->warn("  {$label} could not fetch image");
                $fail++;
                continue;
            }

            $topic = $post->metadata['topic'] ?? null;
            $cta = $post->metadata['cta'] ?? null;

            $copy = $this->rewrite($imgBytes, $topic, $cta);
            if (!$copy) {
                $this->error("  {$label} rewrite failed");
                $fail++;
                continue;
            }

            $this->line("  {$label} -> " . mb_substr($copy['content'], 0, 80) . '...');

            if ($dryRun) { $ok++; continue; }

            // Cascade to all siblings (FB + IG, including stories) so the
            // whole group reads consistently.
            $siblings = SocialPost::where('group_id', $post->group_id)->get();
            foreach ($siblings as $s) {
                $s->update([
                    'content' => $copy['content'],
                    'hashtags' => $copy['hashtags'],
                ]);
            }
            $ok++;
        }

        $this->newLine();
        $this->info("Done. ok={$ok} failed={$fail}");
        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function fetchImage(string $url): ?string
    {
        try {
            $r = Http::timeout(30)->get($url);
            return $r->successful() ? $r->body() : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function rewrite(string $imgBytes, ?string $topic, ?string $cta): ?array
    {
        $b64 = base64_encode($imgBytes);
        $dataUrl = "data:image/png;base64,{$b64}";

        // Pull recent openers from DB so GPT doesn't fall in a "Visezi..."
        // / "Te-ai săturat..." rut. Same trick GenerateDailyBatch uses.
        $recentOpeners = SocialPost::query()
            ->whereIn('status', ['draft', 'scheduled', 'published'])
            ->orderByDesc('id')->limit(40)->pluck('content')
            ->map(function ($c) {
                $first = preg_split('/[.!?\n]/', (string) $c)[0] ?? '';
                return trim(mb_substr($first, 0, 80));
            })->filter()->unique()->take(20)->values()->all();
        $avoidBlock = $recentOpeners ? ("- " . implode("\n- ", $recentOpeners)) : '(niciuna)';

        // Force structural diversity by picking a hook pattern at random.
        $hookPatterns = [
            'question'   => 'Începe cu o întrebare directă despre o frustrare reală a unui owner de IMM (NU retorică generică, NU «Visezi...», NU «Te-ai săturat...»).',
            'stat'       => 'Începe cu o cifră surprinzătoare plauzibilă (procent / lei / ore).',
            'story'      => 'Începe cu un micro-scenariu concret în 1-2 propoziții, prezent cinematic.',
            'contrarian' => 'Începe cu o afirmație care contrazice un clișeu popular despre AI.',
            'insight'    => 'Începe cu un adevăr observațional pe care doar cineva care a trăit problema îl știe.',
            'object'     => 'Începe descriind direct un obiect/element vizibil în imagine, apoi conectează-l la beneficiul Sambla.',
        ];
        $hookKey = array_rand($hookPatterns);
        $hookInstruction = $hookPatterns[$hookKey];

        $sys = 'Ești copywriter pentru Sambla, platformă românească de agenți AI pentru afaceri mici și mijlocii. Scrii postări social media în română, cald și direct, fără jargon corporate. Răspunzi DOAR cu JSON valid.';

        $userText = "Privește imaginea atașată și scrie o postare Facebook/Instagram pentru Sambla care să fie RELEVANTĂ pentru ce se vede în imagine. "
            . "Sambla = agenți AI pentru afaceri mici din România (preia mesaje, răspunde la întrebări, programări, suport clienți 24/7). "
            . ($topic ? "Unghi inițial al postării (poți să te abați dacă imaginea spune altceva): {$topic}. " : '')
            . ($cta ? "CTA preferat: {$cta}. " : '')
            . "\n\nPATTERN DE HOOK ({$hookKey}): {$hookInstruction}\n\n"
            . "INTERZIS — aceste deschideri sunt SUPRA-FOLOSITE recent. NU începe cu vreuna și NU varia pe ele:\n{$avoidBlock}\n\n"
            . "REGULI:\n"
            . "- Postarea TREBUIE să aibă legătură vizibilă cu scena/obiectele din imagine.\n"
            . "- 60-180 cuvinte, 2-4 paragrafe scurte separate prin linie goală.\n"
            . "- Nu menționa cuvinte ca 'imagine', 'fotografie', 'în poză' — vorbește despre subiect direct.\n"
            . "- INTERZIS deschideri șablon: «Visezi...», «Te-ai săturat...», «Imaginează-ți...», «Vrei să...», «Stresat de...», «Vineri seara...».\n"
            . "- INTERZIS subiecte: credite, împrumuturi, asigurări, investiții, servicii bancare, leasing, IFN. Sambla NU oferă așa ceva.\n"
            . "- INTERZIS jargon: «revoluționar», «soluție completă», «scalabil», «transformă», «empowering», «next-level».\n"
            . "- 4-7 emoji distribuite natural, NU înghesuite la final.\n"
            . "- Termină cu un CTA scurt → sambla.ro.\n"
            . "- Hashtag-uri: 5-8, relevante imaginii și temei.\n\n"
            . 'Returnează DOAR JSON: {"content":"textul postării cu \\n\\n între paragrafe","hashtags":["#tag1","#tag2"]}';

        try {
            $resp = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $sys],
                    ['role' => 'user', 'content' => [
                        ['type' => 'text', 'text' => $userText],
                        ['type' => 'image_url', 'image_url' => ['url' => $dataUrl]],
                    ]],
                ],
                'max_tokens' => 700,
                'temperature' => 0.85,
                'response_format' => ['type' => 'json_object'],
            ]);
            $raw = $resp->choices[0]->message->content ?? '';
            $parsed = json_decode($raw, true) ?: [];
            if (empty($parsed['content'])) return null;
            $tags = $parsed['hashtags'] ?? [];
            if (!is_array($tags)) $tags = [];
            return ['content' => trim($parsed['content']), 'hashtags' => array_values($tags)];
        } catch (\Throwable $e) {
            $this->warn("    OpenAI vision error: {$e->getMessage()}");
            return null;
        }
    }
}
