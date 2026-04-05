<?php

namespace App\Services;

use App\Models\Bot;
use Illuminate\Support\Facades\Cache;

/**
 * Generates contextual "filling" messages to reduce perceived latency.
 *
 * When the AI response takes >1500ms, a filling message is spoken to keep
 * the conversation natural (e.g., "O clipă să verific..."). Messages adapt
 * to the bot's language, tone, and the type of query being processed.
 *
 * The style is dynamically derived from the bot's system_prompt to ensure
 * filling messages match the bot's personality (formal/informal, friendly/professional).
 */
class FillingMessageService
{
    // Intent categories for filling messages
    public const INTENT_PRODUCT_SEARCH = 'product_search';
    public const INTENT_BRAND_LOOKUP = 'brand_lookup';
    public const INTENT_CATEGORY_BROWSE = 'category_browse';
    public const INTENT_PRICE_CHECK = 'price_check';
    public const INTENT_STOCK_CHECK = 'stock_check';
    public const INTENT_ORDER_STATUS = 'order_status';
    public const INTENT_GENERAL = 'general';
    public const INTENT_TECHNICAL = 'technical';

    /**
     * Messages grouped by intent category.
     * Each category has formal and informal variants.
     * These are templates — tone adaptation is applied at runtime.
     *
     * @var array<string, array{formal: string[], informal: string[]}>
     */
    private const MESSAGES = [
        self::INTENT_PRODUCT_SEARCH => [
            'formal' => [
                'O clipă, vă rog, verific în catalogul nostru.',
                'Un moment, caut produsul în baza noastră de date.',
                'Vă rog să așteptați un moment, caut cele mai bune opțiuni pentru dumneavoastră.',
                'Verific imediat disponibilitatea. O secundă, vă rog.',
                'Caut în catalog pentru a vă oferi informații precise.',
                'Un moment, mă asigur că vă dau cele mai actuale informații.',
                'Să vedem ce avem disponibil. O clipă, vă rog.',
                'Verific chiar acum pentru dumneavoastră.',
                'O clipă, mă asigur că vă ofer cele mai bune recomandări.',
                'Un moment, caut exact ce aveți nevoie.',
                'Vă rog o secundă, verific în sistem.',
                'Caut cele mai potrivite produse pentru cererea dumneavoastră.',
                'Un moment, vă rog, mă uit în catalog.',
                'Verific informațiile pentru a vă da un răspuns corect.',
                'O clipă, caut produsele cele mai relevante.',
                'Mă uit chiar acum ce avem disponibil pentru dumneavoastră.',
                'Un moment, verific opțiunile disponibile.',
                'Vă rog să așteptați o clipă, caut în baza de produse.',
                'Verific imediat ce vă pot recomanda.',
                'O secundă, caut cele mai bune variante.',
            ],
            'informal' => [
                'Stai o clipă, caut în catalog.',
                'Un moment, mă uit ce avem.',
                'Stai puțin, verific ce avem disponibil.',
                'O secundă, caut pentru tine.',
                'Mă uit chiar acum, stai o clipă.',
                'Dă-mi o secundă, verific în stoc.',
                'Un moment, caut cele mai bune opțiuni.',
                'Stai puțin, mă uit ce pot să îți recomand.',
                'O clipă, verific imediat.',
                'Stai o secundă, caut ce trebuie.',
                'Mă uit repede ce avem, un moment.',
                'Stai puțin, caut în baza de date.',
                'O clipă, verific pentru tine.',
                'Un moment, mă asigur că îți dau informații corecte.',
                'Stai o clipă, caut cele mai bune produse.',
                'Mă uit acum, dă-mi o secundă.',
                'Un moment, verific opțiunile.',
                'Stai puțin, caut exact ce ai nevoie.',
                'O secundă, mă uit în catalog.',
                'Dă-mi o clipă, verific disponibilitatea.',
            ],
        ],

        self::INTENT_BRAND_LOOKUP => [
            'formal' => [
                'Un moment, verific ce avem de la acest brand.',
                'O clipă, caut produsele acestui producător.',
                'Vă rog să așteptați, verific gama de la acest brand.',
                'Un moment, mă uit ce produse avem de la acest producător.',
                'Verific imediat oferta de la acest brand.',
                'O clipă, caut în catalogul acestui producător.',
                'Un moment, verific toate produsele disponibile de la acest brand.',
                'Vă rog o secundă, mă uit ce gamă avem.',
                'Verific gama completă. Un moment, vă rog.',
                'O clipă, caut toate opțiunile de la acest brand.',
            ],
            'informal' => [
                'Stai o clipă, mă uit ce avem de la brandul ăsta.',
                'Un moment, verific ce produse avem de la ei.',
                'Stai puțin, caut în gama lor.',
                'O secundă, mă uit ce avem de la producătorul ăsta.',
                'Dă-mi o clipă, verific oferta lor.',
                'Un moment, mă uit ce avem disponibil de la ei.',
                'Stai puțin, caut gama completă.',
                'O clipă, verific ce produse au.',
                'Stai o secundă, caut de la brandul ăsta.',
                'Mă uit repede ce avem de la ei, un moment.',
            ],
        ],

        self::INTENT_CATEGORY_BROWSE => [
            'formal' => [
                'Un moment, verific categoriile disponibile.',
                'O clipă, mă uit ce tipuri de produse avem.',
                'Vă rog să așteptați, verific ce opțiuni avem în această categorie.',
                'Un moment, caut în această categorie de produse.',
                'Verific imediat ce avem disponibil în această gamă.',
                'O clipă, mă uit la produsele din această categorie.',
                'Un moment, verific sortimentul disponibil.',
                'Vă rog o secundă, caut în această categorie.',
                'O clipă, verific gama de produse din această categorie.',
                'Un moment, mă asigur că vă prezint toate opțiunile.',
            ],
            'informal' => [
                'Stai o clipă, mă uit ce categorii avem.',
                'Un moment, verific ce tipuri de produse sunt.',
                'Stai puțin, caut în categoria asta.',
                'O secundă, mă uit ce avem în zona asta.',
                'Dă-mi o clipă, verific ce opțiuni sunt.',
                'Un moment, mă uit la sortiment.',
                'Stai puțin, verific gama disponibilă.',
                'O clipă, caut în categoria asta.',
                'Stai o secundă, mă uit ce produse avem aici.',
                'Mă uit repede, un moment.',
            ],
        ],

        self::INTENT_PRICE_CHECK => [
            'formal' => [
                'Un moment, verific prețul exact pentru dumneavoastră.',
                'O clipă, mă asigur că vă dau prețul corect.',
                'Vă rog să așteptați, verific prețul actualizat.',
                'Un moment, caut prețul exact în sistem.',
                'Verific imediat prețul. O secundă, vă rog.',
                'O clipă, mă uit la prețul curent.',
                'Un moment, verific dacă avem și vreo ofertă specială.',
                'Vă rog o secundă, verific cel mai bun preț.',
                'O clipă, caut prețul actualizat pentru acest produs.',
                'Un moment, verific prețul și eventualele reduceri.',
            ],
            'informal' => [
                'Stai o clipă, verific prețul.',
                'Un moment, mă uit la preț.',
                'Stai puțin, verific cât costă.',
                'O secundă, caut prețul exact.',
                'Dă-mi o clipă, mă uit la preț.',
                'Un moment, verific dacă avem și vreo reducere.',
                'Stai puțin, caut prețul actualizat.',
                'O clipă, mă uit cât e.',
                'Stai o secundă, verific cel mai bun preț.',
                'Mă uit repede la preț, un moment.',
            ],
        ],

        self::INTENT_STOCK_CHECK => [
            'formal' => [
                'Un moment, verific disponibilitatea în stoc.',
                'O clipă, mă uit dacă avem în stoc.',
                'Vă rog să așteptați, verific stocul actual.',
                'Un moment, mă asigur că produsul e disponibil.',
                'Verific imediat stocul. O secundă, vă rog.',
                'O clipă, verific disponibilitatea pentru dumneavoastră.',
                'Un moment, mă uit la stocul curent.',
                'Vă rog o secundă, verific dacă avem pe stoc.',
                'O clipă, verific cantitatea disponibilă.',
                'Un moment, mă asigur că e în stoc.',
            ],
            'informal' => [
                'Stai o clipă, verific dacă avem pe stoc.',
                'Un moment, mă uit la stoc.',
                'Stai puțin, verific disponibilitatea.',
                'O secundă, mă uit dacă avem.',
                'Dă-mi o clipă, verific stocul.',
                'Un moment, mă asigur că e disponibil.',
                'Stai puțin, verific cantitatea.',
                'O clipă, mă uit dacă mai avem.',
                'Stai o secundă, verific în stoc.',
                'Mă uit repede la stoc, un moment.',
            ],
        ],

        self::INTENT_ORDER_STATUS => [
            'formal' => [
                'Un moment, verific statusul comenzii dumneavoastră.',
                'O clipă, caut informațiile despre comandă.',
                'Vă rog să așteptați, verific comanda în sistem.',
                'Un moment, mă uit la detaliile comenzii.',
                'Verific imediat comanda. O secundă, vă rog.',
                'O clipă, caut statusul comenzii.',
                'Un moment, verific unde se află comanda dumneavoastră.',
                'Vă rog o secundă, verific în sistemul de comenzi.',
                'O clipă, caut detaliile livrării.',
                'Un moment, mă asigur că vă dau informații actualizate despre comandă.',
            ],
            'informal' => [
                'Stai o clipă, verific comanda.',
                'Un moment, mă uit la statusul comenzii.',
                'Stai puțin, caut comanda în sistem.',
                'O secundă, verific unde e comanda.',
                'Dă-mi o clipă, mă uit la comandă.',
                'Un moment, verific detaliile.',
                'Stai puțin, caut informațiile despre livrare.',
                'O clipă, mă uit la status.',
                'Stai o secundă, verific comanda ta.',
                'Mă uit repede la comandă, un moment.',
            ],
        ],

        self::INTENT_GENERAL => [
            'formal' => [
                'Un moment, vă rog.',
                'O clipă, verific informațiile.',
                'Vă rog să așteptați un moment, mă asigur că vă dau un răspuns complet.',
                'Un moment, caut informațiile necesare.',
                'O secundă, vă rog, verific pentru dumneavoastră.',
                'Un moment, mă asigur că vă ofer cele mai bune informații.',
                'O clipă, verific acest lucru.',
                'Vă rog o secundă, caut răspunsul potrivit.',
                'Un moment, verific pentru a vă da un răspuns precis.',
                'O clipă, mă documentez pentru dumneavoastră.',
                'Un moment, vă rog, verific imediat.',
                'O secundă, mă asigur de acuratețea informațiilor.',
                'Un moment, caut cele mai bune informații pentru dumneavoastră.',
                'O clipă, verific în baza noastră de cunoștințe.',
                'Vă rog să așteptați o clipă.',
                'Un moment, verific acest aspect.',
                'O secundă, mă uit la detalii.',
                'Un moment, mă asigur că am informații corecte.',
                'O clipă, caut un răspuns cât mai util.',
                'Vă rog un moment, verific pentru dumneavoastră.',
            ],
            'informal' => [
                'Stai o clipă.',
                'Un moment, verific.',
                'Stai puțin, mă uit.',
                'O secundă, verific pentru tine.',
                'Dă-mi o clipă, mă documentez.',
                'Un moment, caut informațiile.',
                'Stai puțin, verific imediat.',
                'O clipă, mă asigur că îți dau un răspuns bun.',
                'Stai o secundă, caut răspunsul.',
                'Un moment, mă uit la detalii.',
                'Stai puțin, verific ce pot să îți spun.',
                'O secundă, caut informațiile necesare.',
                'Dă-mi un moment, verific.',
                'Stai o clipă, mă uit.',
                'Un moment, caut cele mai bune informații.',
                'Stai puțin, mă documentez.',
                'O clipă, verific imediat.',
                'Stai o secundă.',
                'Un moment, mă uit repede.',
                'Dă-mi o secundă, verific.',
            ],
        ],

        self::INTENT_TECHNICAL => [
            'formal' => [
                'Un moment, verific specificațiile tehnice.',
                'O clipă, caut detaliile tehnice ale produsului.',
                'Vă rog să așteptați, verific informațiile tehnice.',
                'Un moment, mă uit la caracteristicile produsului.',
                'Verific imediat specificațiile. O secundă, vă rog.',
                'O clipă, caut detaliile exacte.',
                'Un moment, verific fișa tehnică.',
                'Vă rog o secundă, caut informațiile tehnice precise.',
                'O clipă, mă asigur că vă dau specificațiile corecte.',
                'Un moment, verific caracteristicile tehnice.',
            ],
            'informal' => [
                'Stai o clipă, mă uit la specificații.',
                'Un moment, verific detaliile tehnice.',
                'Stai puțin, caut informațiile tehnice.',
                'O secundă, mă uit la caracteristici.',
                'Dă-mi o clipă, verific specificațiile.',
                'Un moment, caut fișa tehnică.',
                'Stai puțin, mă uit la detalii.',
                'O clipă, verific specificațiile exacte.',
                'Stai o secundă, caut detaliile.',
                'Mă uit repede la specificații, un moment.',
            ],
        ],
    ];

    /**
     * Keywords that help detect query intent for choosing the right filling category.
     */
    private const INTENT_KEYWORDS = [
        self::INTENT_BRAND_LOOKUP => [
            'brand', 'marca', 'producator', 'producător', 'fabricant',
            'de la', 'firma', 'compania',
        ],
        self::INTENT_CATEGORY_BROWSE => [
            'categori', 'tip', 'tipuri', 'gama', 'sortiment', 'fel',
            'varietat', 'optiuni', 'opțiuni', 'ce aveti', 'ce aveți',
        ],
        self::INTENT_PRICE_CHECK => [
            'pret', 'preț', 'costa', 'costă', 'cat costa', 'cât costă',
            'ieftin', 'scump', 'reducere', 'oferta', 'ofertă', 'promotie', 'promoție',
        ],
        self::INTENT_STOCK_CHECK => [
            'stoc', 'disponibil', 'pe stoc', 'in stoc', 'în stoc',
            'mai aveti', 'mai aveți', 'cantitate', 'disponibilitate',
        ],
        self::INTENT_ORDER_STATUS => [
            'comanda', 'comandă', 'colet', 'livrare', 'awb', 'tracking',
            'unde e', 'status', 'expedi',
        ],
        self::INTENT_TECHNICAL => [
            'specificat', 'tehnic', 'dimensiun', 'greutate', 'material',
            'compozit', 'rezistent', 'capacitat', 'putere', 'consum',
        ],
    ];

    /**
     * Tracks which messages were recently used per call to avoid repetition.
     *
     * @var array<string, string[]> callId => [messageHashes]
     */
    private array $usedMessages = [];

    /**
     * Get a filling message appropriate for the given context.
     *
     * @param Bot    $bot       The bot whose style to match
     * @param string $intent    Intent category (use constants or auto-detect from transcript)
     * @param string $callId    Call ID for deduplication
     * @return string The filling message text
     */
    public function getMessage(Bot $bot, string $intent = self::INTENT_GENERAL, string $callId = ''): string
    {
        $tone = $this->detectTone($bot);
        $messages = self::MESSAGES[$intent][$tone] ?? self::MESSAGES[self::INTENT_GENERAL][$tone];

        // Filter out recently used messages for this call
        $usedHashes = $this->usedMessages[$callId] ?? [];
        $available = array_filter($messages, fn($m) => !in_array(md5($m), $usedHashes, true));

        // If all exhausted, reset and use full pool
        if (empty($available)) {
            $this->usedMessages[$callId] = [];
            $available = $messages;
        }

        $message = $available[array_rand($available)];

        // Track usage
        $this->usedMessages[$callId][] = md5($message);

        // Keep history bounded (last 10)
        if (count($this->usedMessages[$callId] ?? []) > 10) {
            $this->usedMessages[$callId] = array_slice($this->usedMessages[$callId], -10);
        }

        return $message;
    }

    /**
     * Detect the most likely intent from user transcript text.
     *
     * @param string $transcript The user's spoken text
     * @param bool   $hasProducts Whether the bot has product catalog
     * @return string Intent constant
     */
    public function detectIntent(string $transcript, bool $hasProducts = false): string
    {
        $lower = mb_strtolower($transcript);
        $normalized = str_replace(
            ['ă', 'â', 'î', 'ș', 'ț'],
            ['a', 'a', 'i', 's', 't'],
            $lower
        );

        // Check each intent's keywords
        $scores = [];
        foreach (self::INTENT_KEYWORDS as $intent => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                $keyNorm = str_replace(
                    ['ă', 'â', 'î', 'ș', 'ț'],
                    ['a', 'a', 'i', 's', 't'],
                    $keyword
                );
                if (str_contains($normalized, $keyNorm) || str_contains($lower, $keyword)) {
                    $score += mb_strlen($keyword); // Longer matches = higher confidence
                }
            }
            if ($score > 0) {
                $scores[$intent] = $score;
            }
        }

        if (!empty($scores)) {
            arsort($scores);
            return array_key_first($scores);
        }

        // Default: if bot has products and transcript looks like a product query
        if ($hasProducts && mb_strlen($transcript) > 3) {
            return self::INTENT_PRODUCT_SEARCH;
        }

        return self::INTENT_GENERAL;
    }

    /**
     * Detect the bot's tone from its system_prompt.
     *
     * @return string 'formal' or 'informal'
     */
    private function detectTone(Bot $bot): string
    {
        $cacheKey = "bot_tone_{$bot->id}";

        try {
            return Cache::remember($cacheKey, now()->addHours(6), fn() => $this->analyzeTone($bot));
        } catch (\Throwable $e) {
            // Fallback without cache (e.g. in tests or if Redis is down)
            return $this->analyzeTone($bot);
        }
    }

    /**
     * Analyze the bot's system_prompt and greeting to determine formal vs informal tone.
     */
    private function analyzeTone(Bot $bot): string
    {
        $prompt = mb_strtolower($bot->system_prompt ?? '');
        $greeting = mb_strtolower($bot->greeting_message ?? '');
        $combined = $prompt . ' ' . $greeting;

        // Informal indicators
        $informalSignals = 0;
        $informalPatterns = [
            'tu ', 'tutui', 'informal', 'prietenos', 'relaxat', 'casual',
            'stai', 'dă-mi', 'fii', 'hai', 'super', 'cool', 'mișto',
            'vorbește pe tu', 'tonul informal', 'limbaj informal',
            'prieten', 'colocvial', 'familiar',
        ];
        foreach ($informalPatterns as $pattern) {
            if (str_contains($combined, $pattern)) {
                $informalSignals++;
            }
        }

        // Formal indicators
        $formalSignals = 0;
        $formalPatterns = [
            'dumneavoastră', 'dvs', 'formal', 'profesional', 'politicos',
            'respectuos', 'serios', 'corporat', 'oficial', 'instituțional',
            'vă rog', 'cu stimă', 'cu respect',
        ];
        foreach ($formalPatterns as $pattern) {
            if (str_contains($combined, $pattern)) {
                $formalSignals++;
            }
        }

        return $informalSignals > $formalSignals ? 'informal' : 'formal';
    }

    /**
     * Build the response.create payload for OpenAI Realtime to speak a filling message.
     *
     * @param Bot    $bot        The bot
     * @param string $transcript The user's last transcript (for intent detection)
     * @param string $callId     Call ID for deduplication
     * @param bool   $hasProducts Whether bot has products
     * @return array OpenAI Realtime response.create event
     */
    public function buildFillingResponse(Bot $bot, string $transcript, string $callId, bool $hasProducts = false): array
    {
        $intent = $this->detectIntent($transcript, $hasProducts);
        $message = $this->getMessage($bot, $intent, $callId);

        return [
            'type' => 'response.create',
            'response' => [
                'modalities' => ['text', 'audio'],
                'instructions' => 'Spune exact următorul text, natural și calm, fără să adaugi nimic: "' . str_replace('"', '\\"', $message) . '"',
            ],
        ];
    }

    /**
     * Reset usage tracking for a call (call ended).
     */
    public function resetCall(string $callId): void
    {
        unset($this->usedMessages[$callId]);
    }
}
