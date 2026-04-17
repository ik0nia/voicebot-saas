<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Models\Bot;
use App\Models\Conversation;
use App\Services\Widget\UserStateResolver;

/**
 * Builds the follow-up quick-reply chip strip that the widget appends
 * under each bot response. Chips are per-page-type defaults
 * (product / cart / category / booking / hospitality) with three
 * overlays:
 *
 *   - "bail signal" — if the bot response contains a dead-end phrase
 *     ("nu am acea informație" / "contactați-ne" / "nu pot răspunde"
 *     / "voi reveni"), the strip is replaced with a single lead-
 *     capture chip so the user isn't stranded.
 *   - user state (P5.2) — HIGH_INTENT / STUCK / PRICE_SENSITIVE /
 *     COMPARING prepend targeted chips on top of the defaults.
 *   - memory (G7) — a remembered product from a prior turn can
 *     seed a "Reia X" chip on non-product pages.
 *
 * At the tail we also add a one-shot "conversion closure" chip when
 * the bot's response has no call-to-action phrase of its own (X3
 * closure). Everything stays capped at four chips with label <= 40
 * chars and text <= 500 chars, matching the widget's rendering
 * contract.
 */
final class FollowupChipBuilder
{
    private const MAX_CHIPS = 4;
    private const MAX_LABEL_LEN = 40;
    private const MAX_TEXT_LEN = 500;

    private const BAIL_SIGNALS = [
        'nu am acea informați',
        'contactați-ne',
        'nu pot răspunde',
        'voi reveni',
    ];

    private const CTA_PATTERNS = [
        'vrei să', 'vrei sa', 'pot să', 'pot sa',
        'te ajut cu', 'te ghidez', 'continuăm',
        'finalizez', 'confirmi', 'rezerv',
        'adaug în coș', 'adaug in cos',
    ];

    public function __construct(
        private readonly UserStateResolver $userStateResolver,
    ) {}

    /**
     * @param array<string, mixed>             $pageContext
     * @param array<int, array<string, mixed>> $products
     * @return list<array{label: string, text: string, action?: string, payload?: array}>
     */
    public function build(
        Bot $bot,
        array $pageContext,
        array $products,
        string $response,
        ?Conversation $conversation = null,
        ?string $userMessage = null,
    ): array {
        $pageType = (string) ($pageContext['page_type'] ?? '');
        if ($pageType === '' || $pageType === 'home') {
            return [];
        }

        if ($this->responseIsBailSignal($response)) {
            return [[
                'label' => 'Lasă-mi datele',
                'text' => 'Vreau să mă contactați voi — iau în jos datele mele.',
            ]];
        }

        $stateInfo = $this->userStateResolver->resolve($conversation, (string) $userMessage, $pageContext);
        $userState = $stateInfo['state'];

        $replies = $this->defaultsForPageType($pageType, $pageContext, $products, $bot);
        $replies = $this->applyStateOverlay($replies, $userState);
        $replies = $this->applyConversionClosure($replies, $response, $pageType);
        $replies = $this->applyMemoryContinuity($replies, $pageType, $conversation);

        return $this->cap($replies);
    }

    /**
     * @param array<int, array<string, mixed>> $products
     * @return list<array{label: string, text: string, action?: string, payload?: array}>
     */
    private function defaultsForPageType(string $pageType, array $pageContext, array $products, Bot $bot): array
    {
        return match (true) {
            $pageType === 'product' && !empty($products) => $this->productDefaults($pageContext),
            $pageType === 'cart' => $this->cartDefaults($pageContext),
            $pageType === 'category' && !empty($products) => [
                ['label' => 'Alege tu pentru mine', 'text' => 'Alege tu varianta cea mai potrivită pentru mine.'],
                ['label' => 'Filtrează pe buget', 'text' => 'Filtrează în funcție de buget.'],
                ['label' => 'Cele mai bine cotate', 'text' => 'Care sunt cele mai bine cotate?'],
            ],
            $pageType === 'booking' && in_array($bot->engine_type, ['booking', 'hybrid'], true) => [
                ['label' => 'Primul loc liber', 'text' => 'Vreau primul loc disponibil.'],
                ['label' => 'Mâine dimineață', 'text' => 'Vreau mâine dimineață.'],
                ['label' => 'După ora 17:00', 'text' => 'Vreau o programare după ora 17:00.'],
                ['label' => 'Am o urgență', 'text' => 'Am o urgență, când pot veni?'],
            ],
            $pageType === 'hospitality' && $bot->engine_type === 'hospitality' => [
                ['label' => 'În weekend', 'text' => 'Vreau pentru weekend-ul acesta.'],
                ['label' => 'Opțiuni premium', 'text' => 'Arată-mi opțiuni premium.'],
                ['label' => 'Pe buget', 'text' => 'Caut o variantă pe buget.'],
                ['label' => 'Pentru 2 adulți', 'text' => 'Vreau pentru 2 adulți.'],
            ],
            default => [],
        };
    }

    /**
     * @return list<array{label: string, text: string, action?: string, payload?: array}>
     */
    private function productDefaults(array $pageContext): array
    {
        $replies = [
            ['label' => 'Vreau să comand', 'text' => 'Vreau să comand produsul discutat.'],
            ['label' => 'Compară cu altele', 'text' => 'Compară-mi acest produs cu 1-2 variante asemănătoare.'],
            ['label' => 'E potrivit pentru mine?', 'text' => 'Cum știu dacă e potrivit pentru mine?'],
        ];

        // Z1: one-click add-to-cart when the page reports which
        // product. Widget bridges to WC via sambla_add_to_cart.
        $pc = is_array($pageContext['product_context'] ?? null) ? $pageContext['product_context'] : null;
        $productId = $pc['product_id'] ?? null;
        if ($productId) {
            array_unshift($replies, [
                'label' => 'Adaugă în coș',
                'text' => 'Adaugă acest produs în coș.',
                'action' => 'add_to_cart',
                'payload' => [
                    'product_id' => (int) $productId,
                    'product_name' => (string) ($pc['name'] ?? ''),
                    'quantity' => 1,
                ],
            ]);
        }

        return $replies;
    }

    /**
     * @return list<array{label: string, text: string}>
     */
    private function cartDefaults(array $pageContext): array
    {
        $cart = $pageContext['cart_context'] ?? null;
        $missing = is_array($cart) ? (float) ($cart['missing_amount_for_free_shipping'] ?? 0) : 0;
        $threshold = is_array($cart) ? (float) ($cart['shipping_threshold'] ?? 0) : 0;
        $currency = is_array($cart) ? (string) ($cart['currency'] ?? 'RON') : 'RON';
        $currLabel = strtoupper($currency) === 'RON' ? 'lei' : $currency;

        $defaults = [
            ['label' => 'Livrare gratuită?', 'text' => 'Ajung la pragul de livrare gratuită? Cât mai lipsește?'],
            ['label' => 'Accesorii compatibile', 'text' => 'Ce accesorii recomanzi să mai adaug?'],
            ['label' => 'Cod promo activ?', 'text' => 'Există un cod promo pe care îl pot aplica?'],
            ['label' => 'Finalizează comanda', 'text' => 'Ghidează-mă să finalizez comanda.'],
        ];

        if ($missing > 0 && $missing < $threshold) {
            $missingFmt = number_format($missing, 2, ',', '.');
            $defaults = [
                ['label' => 'Până la livrare gratuită', 'text' => "Îmi lipsesc {$missingFmt} {$currLabel} până la livrare gratuită. Ce îmi recomanzi să adaug?"],
                ['label' => 'Ceva ieftin ca top-up', 'text' => 'Recomandă-mi 2 produse ieftine care să îmi completeze comanda până la pragul de livrare gratuită.'],
                ['label' => 'Cod promo activ?', 'text' => 'Există un cod promo pe care îl pot aplica?'],
                ['label' => 'Finalizează oricum', 'text' => 'Finalizez oricum — ghidează-mă.'],
            ];
        }

        if (is_array($cart) && (int) ($cart['items_count'] ?? 0) === 0) {
            $defaults = [
                ['label' => 'Recomandă-mi ceva', 'text' => 'Recomandă-mi 3 produse bune acum.'],
                ['label' => 'Cele mai populare', 'text' => 'Care sunt cele mai populare produse?'],
            ];
        }

        return $defaults;
    }

    /**
     * @param list<array<string, mixed>> $replies
     * @return list<array<string, mixed>>
     */
    private function applyStateOverlay(array $replies, string $userState): array
    {
        return match ($userState) {
            UserStateResolver::HIGH_INTENT => array_merge([
                ['label' => 'Vreau să comand', 'text' => 'Vreau să comand — ghidează-mă.'],
                ['label' => 'Finalizează comanda', 'text' => 'Ghidează-mă să finalizez comanda acum.'],
            ], $replies),
            UserStateResolver::STUCK => array_merge([
                ['label' => 'Alege tu pentru mine', 'text' => 'Alege tu varianta cea mai potrivită pentru mine și explică de ce.'],
                ['label' => 'Explică-mi pe scurt', 'text' => 'Rezumă-mi în 3 rânduri ce e mai important de știut.'],
            ], $replies),
            UserStateResolver::PRICE_SENSITIVE => array_merge([
                ['label' => 'Mai ieftin', 'text' => 'Ai ceva mai ieftin dar de calitate bună?'],
                ['label' => 'Reduceri active', 'text' => 'Ce reduceri active aveți acum?'],
            ], $replies),
            UserStateResolver::COMPARING => array_merge([
                ['label' => 'Ce îmi recomanzi', 'text' => 'Dintre opțiuni, care îmi recomanzi tu și de ce?'],
                ['label' => 'Compară tabelar', 'text' => 'Compară-mi cele 2 opțiuni tabelar — avantaje și dezavantaje.'],
            ], $replies),
            default => $replies,
        };
    }

    /**
     * X3: if the bot didn't ask a follow-up itself, append one neutral
     * closure chip so the user has a "what now" path.
     *
     * @param list<array<string, mixed>> $replies
     * @return list<array<string, mixed>>
     */
    private function applyConversionClosure(array $replies, string $response, string $pageType): array
    {
        if (count($replies) === 0 || count($replies) >= self::MAX_CHIPS || trim($response) === '') {
            return $replies;
        }

        if ($this->responseContainsCta($response)) {
            return $replies;
        }

        $closureByPage = [
            'product' => ['label' => 'Vrei să continuăm?', 'text' => 'Da, vreau să continuăm discuția despre acest produs.'],
            'category' => ['label' => 'Alege tu pentru mine', 'text' => 'Alege tu varianta cea mai potrivită pentru mine.'],
            'cart' => ['label' => 'Finalizează comanda', 'text' => 'Ghidează-mă să finalizez comanda acum.'],
            'booking' => ['label' => 'Primul loc liber', 'text' => 'Vreau primul loc disponibil.'],
            'hospitality' => ['label' => 'Vezi disponibilitate', 'text' => 'Arată-mi disponibilitatea pentru mine.'],
        ];

        if (isset($closureByPage[$pageType])) {
            $replies[] = $closureByPage[$pageType];
        }

        return $replies;
    }

    /**
     * G7: continuity chip — if we remember a product from a prior
     * turn and we're not on that product's page, prepend a
     * "Reia X" chip so the conversation feels threaded.
     *
     * @param list<array<string, mixed>> $replies
     * @return list<array<string, mixed>>
     */
    private function applyMemoryContinuity(array $replies, string $pageType, ?Conversation $conversation): array
    {
        if ($pageType === 'product' || $conversation === null || $replies === []) {
            return $replies;
        }

        $lastProduct = ($conversation->metadata ?? [])['last_product_context'] ?? null;
        if (!is_array($lastProduct) || empty($lastProduct['name'])) {
            return $replies;
        }

        $productName = trim((string) $lastProduct['name']);
        if ($productName === '') {
            return $replies;
        }

        $short = mb_strlen($productName) > 18
            ? mb_substr($productName, 0, 16) . '…'
            : $productName;

        array_unshift($replies, [
            'label' => 'Reia „' . $short . '"',
            'text' => 'Vreau să continuăm discuția despre ' . $productName . '.',
        ]);

        return $replies;
    }

    private function responseIsBailSignal(string $response): bool
    {
        $responseLower = mb_strtolower($response);
        foreach (self::BAIL_SIGNALS as $needle) {
            if ($needle !== '' && str_contains($responseLower, $needle)) {
                return true;
            }
        }
        return false;
    }

    private function responseContainsCta(string $response): bool
    {
        $folded = strtr(mb_strtolower($response), ['ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ț' => 't']);
        foreach (self::CTA_PATTERNS as $pattern) {
            $patternFolded = strtr($pattern, ['ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ț' => 't']);
            if (str_contains($folded, $patternFolded)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param list<array<string, mixed>> $replies
     * @return list<array{label: string, text: string, action?: string, payload?: array}>
     */
    private function cap(array $replies): array
    {
        return array_slice(array_map(function (array $r): array {
            $out = [
                'label' => mb_substr((string) $r['label'], 0, self::MAX_LABEL_LEN),
                'text' => mb_substr((string) $r['text'], 0, self::MAX_TEXT_LEN),
            ];
            if (!empty($r['action']) && is_string($r['action'])) {
                $out['action'] = $r['action'];
            }
            if (!empty($r['payload']) && is_array($r['payload'])) {
                $out['payload'] = $r['payload'];
            }
            return $out;
        }, $replies), 0, self::MAX_CHIPS);
    }
}
