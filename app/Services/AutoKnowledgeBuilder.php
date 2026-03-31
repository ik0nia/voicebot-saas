<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\BotKnowledge;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * Automatically generates KB content drafts from knowledge gaps.
 * Uses AI to create FAQ entries, policy documents, and info pages
 * based on what users are asking but the bot can't answer.
 *
 * Flow:
 *   KnowledgeGapService.analyze() → gaps
 *   AutoKnowledgeBuilder.generateDrafts(gaps) → draft content
 *   Admin reviews + approves → ProcessKnowledgeDocument job
 */
class AutoKnowledgeBuilder
{
    /**
     * Generate draft KB content for a category of gaps.
     *
     * @param string $category Gap category (pricing, shipping, returns, etc.)
     * @param array $sampleQueries Real user queries that failed
     * @param Bot $bot The bot to generate content for
     * @return array{title: string, content: string, source_type: string, category: string, confidence: string}
     */
    public function generateDraft(string $category, array $sampleQueries, Bot $bot): array
    {
        $template = $this->getTemplate($category);
        $botContext = $this->getBotContext($bot);

        $prompt = "Ești un expert în crearea de conținut pentru baze de cunoștințe AI.\n\n"
            . "CONTEXT BUSINESS:\n{$botContext}\n\n"
            . "PROBLEMĂ: Clienții pun aceste întrebări dar bot-ul nu poate răspunde:\n"
            . implode("\n", array_map(fn($q) => "- \"{$q}\"", array_slice($sampleQueries, 0, 5)))
            . "\n\n"
            . "TEMPLATE DE CONȚINUT ({$category}):\n{$template}\n\n"
            . "INSTRUCȚIUNI:\n"
            . "1. Generează un document KB care răspunde la aceste întrebări.\n"
            . "2. Folosește template-ul ca ghid de structură.\n"
            . "3. Unde nu ai informații specifice, pune [DE COMPLETAT] ca placeholder.\n"
            . "4. Scrie în română, clar și concis.\n"
            . "5. Fiecare secțiune trebuie să răspundă direct la cel puțin o întrebare din lista de mai sus.\n"
            . "6. Format: titlu clar + secțiuni cu întrebări și răspunsuri.\n\n"
            . "Generează DOAR documentul, fără explicații suplimentare.";

        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'temperature' => 0.3,
                'max_tokens' => 1000,
                'messages' => [
                    ['role' => 'system', 'content' => 'Generezi conținut KB structurat în română. Practic, util, fără fluff.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            $content = trim($response->choices[0]->message->content ?? '');

            if (empty($content)) {
                return $this->getStaticDraft($category, $sampleQueries);
            }

            return [
                'title' => $this->getCategoryTitle($category),
                'content' => $content,
                'source_type' => 'agent',
                'category' => $category,
                'confidence' => 'draft', // needs human review
                'sample_queries' => $sampleQueries,
            ];
        } catch (\Throwable $e) {
            Log::warning('AutoKnowledgeBuilder: AI generation failed', ['error' => $e->getMessage()]);
            return $this->getStaticDraft($category, $sampleQueries);
        }
    }

    /**
     * Generate drafts for all gap categories at once.
     */
    public function generateAllDrafts(Bot $bot): array
    {
        $gapService = app(KnowledgeGapService::class);
        $analysis = $gapService->analyze($bot->id);

        $drafts = [];
        foreach ($analysis['suggestions'] as $suggestion) {
            $category = $suggestion['template'] ?? null;
            if (!$category) continue;

            $sampleQueries = $suggestion['sample_queries'] ?? [];
            if (empty($sampleQueries)) continue;

            // Check if this category already has content
            $existing = BotKnowledge::where('bot_id', $bot->id)
                ->where('status', 'ready')
                ->where('category', $category)
                ->exists();

            if ($existing) continue; // Don't regenerate for existing categories

            $drafts[] = $this->generateDraft($category, $sampleQueries, $bot);
        }

        return $drafts;
    }

    /**
     * Save a draft as pending knowledge (needs admin approval).
     */
    public function saveDraft(Bot $bot, array $draft): BotKnowledge
    {
        return BotKnowledge::create([
            'bot_id' => $bot->id,
            'type' => 'text',
            'source_type' => 'agent',
            'title' => $draft['title'],
            'content' => $draft['content'],
            'status' => 'pending', // NOT ready — needs review
            'category' => $draft['category'] ?? null,
            'metadata' => [
                'auto_generated' => true,
                'sample_queries' => $draft['sample_queries'] ?? [],
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function getBotContext(Bot $bot): string
    {
        $context = "Bot: {$bot->name}";
        if ($bot->system_prompt) {
            // Extract first 200 chars of system prompt for context
            $context .= "\nDescriere: " . mb_substr($bot->system_prompt, 0, 200);
        }
        $hasProducts = \App\Models\WooCommerceProduct::where('bot_id', $bot->id)->exists();
        $context .= "\nTip: " . ($hasProducts ? 'E-commerce' : 'Servicii');
        return $context;
    }

    private function getTemplate(string $category): string
    {
        return match($category) {
            'pricing' => "PREȚURI ȘI TARIFE\n\n"
                . "1. Prețurile produselor/serviciilor principale\n"
                . "2. Există reduceri sau oferte speciale?\n"
                . "3. Metode de calcul preț (pe bucată, pe metru, pe oră)\n"
                . "4. TVA inclus sau separat?\n"
                . "5. Prețuri pentru cantități mari / en-gros",

            'shipping' => "LIVRARE ȘI TRANSPORT\n\n"
                . "1. Metode de livrare disponibile\n"
                . "2. Costuri de transport (gratuit de la X lei?)\n"
                . "3. Termene de livrare pe zone\n"
                . "4. Curieri parteneri\n"
                . "5. Livrare în weekend sau zile lucrătoare?\n"
                . "6. Tracking disponibil?",

            'returns' => "POLITICA DE RETUR ȘI SCHIMB\n\n"
                . "1. Termenul de retur (14/30 zile?)\n"
                . "2. Condiții (produs sigilat, nefolosit?)\n"
                . "3. Procesul de retur pas cu pas\n"
                . "4. Cine suportă costul transportului la retur?\n"
                . "5. Termenul de rambursare\n"
                . "6. Excepții (produse personalizate, etc.)",

            'payment' => "METODE DE PLATĂ\n\n"
                . "1. Metode acceptate (card, numerar, transfer)\n"
                . "2. Plata în rate disponibilă?\n"
                . "3. Facturare (persoane fizice și juridice)\n"
                . "4. Securitatea plăților online\n"
                . "5. Ramburs la livrare?",

            'contact' => "INFORMAȚII DE CONTACT\n\n"
                . "1. Telefon\n"
                . "2. Email\n"
                . "3. Adresa fizică / sediu\n"
                . "4. Program de lucru (zile, ore)\n"
                . "5. Rețele sociale\n"
                . "6. Departamente specifice (suport, vânzări, service)",

            'warranty' => "GARANȚIE ȘI SERVICE\n\n"
                . "1. Perioada de garanție\n"
                . "2. Ce acoperă garanția?\n"
                . "3. Procesul de reclamație\n"
                . "4. Service autorizat / centre de reparații\n"
                . "5. Extensie garanție disponibilă?",

            'promotions' => "PROMOȚII ȘI OFERTE\n\n"
                . "1. Promoții active acum\n"
                . "2. Coduri de cupon disponibile\n"
                . "3. Program de fidelitate\n"
                . "4. Oferte pentru comenzi mari\n"
                . "5. Promoții sezoniere",

            default => "INFORMAȚII GENERALE\n\n"
                . "1. Răspunsuri la întrebările frecvente\n"
                . "2. Informații despre produse/servicii\n"
                . "3. Politici și proceduri",
        };
    }

    private function getCategoryTitle(string $category): string
    {
        return match($category) {
            'pricing' => 'Prețuri și Tarife',
            'shipping' => 'Livrare și Transport',
            'returns' => 'Politica de Retur și Schimb',
            'payment' => 'Metode de Plată',
            'contact' => 'Informații de Contact',
            'warranty' => 'Garanție și Service',
            'promotions' => 'Promoții și Oferte Speciale',
            default => 'Informații Generale',
        };
    }

    /**
     * Fallback: static draft template when AI generation fails.
     */
    private function getStaticDraft(string $category, array $sampleQueries): array
    {
        $template = $this->getTemplate($category);
        $questionsSection = "\n\nÎntrebări frecvente de la clienți:\n"
            . implode("\n", array_map(fn($q) => "- {$q}: [DE COMPLETAT]", array_slice($sampleQueries, 0, 5)));

        return [
            'title' => $this->getCategoryTitle($category),
            'content' => $template . $questionsSection,
            'source_type' => 'manual',
            'category' => $category,
            'confidence' => 'template',
            'sample_queries' => $sampleQueries,
        ];
    }
}
