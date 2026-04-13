<?php

namespace App\Console\Commands;

use App\Models\SocialPost;
use Illuminate\Console\Command;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * Polish existing social post texts: fix grammar, replace old terminology
 * (chatbot/voicebot/bot → agenți AI), improve Romanian quality, add emojis.
 * Cascades changes to feed siblings (FB↔IG share text).
 */
class PolishSocialTexts extends Command
{
    protected $signature = 'social:polish-texts
                            {--limit=0 : Max groups to process (0 = all)}
                            {--sleep=2 : Seconds between API calls}
                            {--dry-run : Preview without saving}';

    protected $description = 'Polish and correct Romanian text in draft/scheduled social posts';

    public function handle(): int
    {
        $statuses = ['draft', 'scheduled'];

        // Get FB feed posts as group representatives
        $query = SocialPost::query()
            ->whereIn('status', $statuses)
            ->where('platform', 'facebook')
            ->where('post_type', 'post')
            ->orderBy('scheduled_at')
            ->orderBy('id');

        $limit = (int) $this->option('limit');
        if ($limit > 0) $query->limit($limit);

        $posts = $query->get();
        $this->info("Found {$posts->count()} FB feed posts to polish.");

        $dryRun = $this->option('dry-run');
        $sleep = (int) $this->option('sleep');
        $updated = 0;
        $skipped = 0;

        foreach ($posts as $post) {
            $content = (string) $post->content;

            // Skip if already clean (no old terms, reasonable quality)
            $needsFix = preg_match('/\bchatbot\b|\bvoicebot\b|\bbot-ul\b|\bbotul\b|\bbot\b/iu', $content)
                || preg_match('/\basistent virtual\b/iu', $content)
                || preg_match('/Vineri seara\.\s*Client/i', $content)
                || preg_match('/Cât te costă un client care sună la 22/i', $content)
                || preg_match('/Cei mai mulți clienți pierduți/i', $content)
                || preg_match('/economisindu/i', $content)
                || preg_match('/concentreze\b/i', $content)
                || mb_strlen($content) < 50;

            if (!$needsFix) {
                $skipped++;
                continue;
            }

            $this->line("• #{$post->id} — polishing...");

            $newContent = $this->polishText($content, $post->metadata ?? []);
            if (!$newContent) {
                $this->warn("  ↳ failed, skip");
                continue;
            }

            $old = mb_substr(preg_replace('/\s+/', ' ', $content), 0, 80);
            $new = mb_substr(preg_replace('/\s+/', ' ', $newContent), 0, 80);
            $this->line("  OLD: {$old}…");
            $this->line("  NEW: {$new}…");

            if (!$dryRun) {
                $post->update(['content' => $newContent]);
                foreach ($post->feedSiblings() as $sib) {
                    $sib->update(['content' => $newContent]);
                }
            }

            $updated++;
            if ($sleep > 0) sleep($sleep);
        }

        $this->newLine();
        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Polished: {$updated}, Skipped (already OK): {$skipped}");
        return self::SUCCESS;
    }

    private function polishText(string $content, array $metadata): ?string
    {
        $category = $metadata['category'] ?? '';
        $topic = $metadata['topic'] ?? '';

        $prompt = "Ai mai jos o postare de social media pentru Sambla (platformă românească de agenți AI pentru afaceri). "
            . "Corectează și îmbunătățește textul respectând TOATE regulile de mai jos.\n\n"
            . "TEXTUL ORIGINAL:\n\"\"\"\n{$content}\n\"\"\"\n\n"
            . ($topic ? "SUBIECTUL POSTĂRII: {$topic}\n\n" : '')
            . "REGULI DE CORECȚIE:\n"
            . "1. TERMINOLOGIE: Înlocuiește ORICE referință la 'chatbot', 'voicebot', 'bot', 'bot-ul', 'botul', 'asistent virtual' cu 'agentul AI' sau 'Sambla'. NU folosi NICIODATĂ cuvântul 'bot'.\n"
            . "2. ROMÂNĂ CORECTĂ: Fixează orice greșeală gramaticală, de ortografie sau de exprimare. Folosește diacritice corecte (ă, â, î, ș, ț). Consistent tu/voi (preferă 'tu'). Nu amesteca stiluri.\n"
            . "3. HOOK-URI INTERZISE — dacă textul începe cu vreuna din acestea, SCHIMBĂ complet hook-ul:\n"
            . "   - 'Vineri seara. Client pe site-ul tău...'\n"
            . "   - 'Cât te costă un client care sună la 22:00...'\n"
            . "   - 'Cei mai mulți clienți pierduți nu se plâng...'\n"
            . "   - 'Știai că X% din clienți...'\n"
            . "   Înlocuiește cu un hook original, relevant cu subiectul postării.\n"
            . "4. EMOJIS: Asigură-te că sunt 5-8 emoji-uri distribuite natural prin text (nu toate la final). Folosește emoji relevante: 📞 💬 💼 🛒 ⏰ ✅ 🧠 🚀 🎯 ⚡ 💡 🇷🇴 🤝 🌙 🧘 💰\n"
            . "5. TON: Cald, prietenos, conversațional — ca și cum i-ai povesti unui prieten antreprenor. Propoziții scurte. Fără jargon corporate ('revoluționar', 'inovator', 'soluție completă', 'next-level', 'transformă modul').\n"
            . "6. STRUCTURĂ: Păstrează structura generală (hook → problemă → soluție → beneficii → CTA). Paragrafe scurte cu linie goală între ele. Max 110 cuvinte.\n"
            . "7. CTA: Termină cu un CTA prietenos → sambla.ro\n"
            . "8. FĂRĂ hashtag-uri, FĂRĂ link-uri brute.\n"
            . "9. NU schimba subiectul postării — doar îmbunătățește cum e scris.\n\n"
            . "INTERZIS DOMENII: NU pomeni credite, împrumuturi, finanțări, banking, asigurări. Sambla NU oferă servicii financiare.\n\n"
            . 'Returnează DOAR JSON: {"content": "textul corectat cu \\n\\n între paragrafe"}';

        try {
            $res = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'Ești editor de conținut social media, expert în limba română. Corectezi și îmbunătățești texte fără să le schimbi sensul. Răspunzi DOAR cu JSON valid.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 600,
                'temperature' => 0.5,
                'response_format' => ['type' => 'json_object'],
            ]);

            $parsed = json_decode($res->choices[0]->message->content ?? '{}', true) ?: [];
            $result = trim((string) ($parsed['content'] ?? ''));
            return $result !== '' ? $result : null;
        } catch (\Throwable $e) {
            $this->error("  OpenAI: {$e->getMessage()}");
            return null;
        }
    }
}
