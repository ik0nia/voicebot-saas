<?php

declare(strict_types=1);

namespace App\Services\Chat;

/**
 * Post-response gate that decides whether the product cards attached
 * to this turn should be kept, dropped, or replaced with a template
 * intro text. The AI *text* is the ground truth the user actually
 * reads — if the text says "I don't know" / "I don't have details"
 * we must never leave contradictory product cards rendered next to
 * it. Likewise for clarification requests and non-product chit-chat.
 *
 * Four rules, ordered:
 *   1. Negative mention — text explicitly says "nu am" / "n-am găsit"
 *      / "contacteaz[aă]ne" etc. → drop cards, always.
 *   2. Clarification request — text says "spune-mi ce tip" / "ce
 *      buget" etc. and didn't also affirm products → drop cards so
 *      the user isn't asked to narrow down while 4 cards sit next to
 *      the prompt.
 *   3. Non-explicit intent + no positive mention — accidental card
 *      hits on knowledge / info queries → drop.
 *   4. Explicit intent + no positive mention + empty-ish text
 *      (< 25 chars) → keep cards but rewrite the response to an
 *      intro template so cards don't look orphaned.
 *
 * Returns both the filtered products and the (possibly rewritten)
 * response. Stream callers get a callback fired whenever cards go
 * from non-empty to empty so they can emit `products: []` on SSE and
 * the widget clears the already-streamed row.
 */
final class ProductCardRelevanceGate
{
    private const EXPLICIT_INTENT_TYPES = [
        'transactional',
        'product_search',
        'category_recommendation',
        'comparison',
        'exploratory',
    ];

    private const POSITIVE_REGEX = '/(?:recoman|suger[aă]m|am găsit|avem\s+(?:câteva|mai multe|urm[aă]toarele|aceste)|iată|uite\s+(?:ce|câteva|produsele)|produse?\s+(?:potrivit|relevant|disponibil)|po[țt]i\s+comanda|adaug[aă]\s+în\s+co[sș]|în\s+stoc|cel\s+mai\s+(?:ieftin|scump|bun|potrivit)|(?:varianta|optiunea|opțiunea)\s+de\s+\d|(?:primul|al\s+doilea|al\s+treilea|al\s+patrulea)\s+(?:produs|card|este))/iu';

    private const CLARIFICATION_REGEX = '/(?:spune[-\s]?mi\s+(?:ce|mai\s+multe|exact)|po[țt]i\s+(?:s[aă]\s+)?(?:îmi\s+)?spui|ce\s+(?:anume|tip|fel|model|dimensiune|marc[aă]|produs)|pentru\s+ce\s+(?:folose[șs]ti|ai\s+nevoie)|ce\s+cau[țt]i\s+(?:exact|mai)|dore[sș]ti\s+(?:ceva\s+)?anume|ai\s+(?:vreo\s+)?preferin[țt]|ce\s+buget)/iu';

    private const NEGATIVE_REGEX = '/(?:'
        . '(?:nu\s+am|n-am)\s+(?:g[aă]sit|detalii|informa[tț]ii|date|suficiente?|acces|cum|exact)'
        . '|(?:nu\s+avem|n-avem)\s+(?:g[aă]sit|informa[tț]ii|detalii|în\s+stoc|aceast[aă]|acest|exact)'
        . '|nu\s+dispun(?:em)?\s+de'
        . '|nu\s+(?:s[tț]iu|sunt\s+sigur)\s+(?:exact|sigur|momentan|dac[aă])?'
        . '|(?:nu\s+pot|n-pot)\s+(?:g[aă]si|s[aă]\s+(?:g[aă]sesc|te\s+ajut|îți\s+spun|confirm)|oferi)'
        . '|(?:momentan|din\s+p[aă]cate),?\s*(?:nu|n-)\s*(?:am|avem|dispun|g[aă]sesc|pot)'
        . '|îmi\s+pare\s+r[aă]u,?\s*(?:dar\s+)?nu'
        . '|indisponibil'
        . '|n-?am\s+g[aă]sit\s+(?:nimic|exact|produse)'
        . '|contacteaz[aă]\s+(?:magazinul|suportul|support|echipa)'
        . '|(?:recomand|sugerez|î[tț]i\s+recomand)\s+s[aă]\s+contactezi'
        . ')/iu';

    /**
     * @param array<int, array<string, mixed>> $products
     * @param list<array<string, mixed>>|null  $detectedIntents
     * @param array<string, mixed>             $queryIntel
     * @param callable|null                    $onIntroTemplate Invoked
     *     with the computed intro text when the gate decides to rewrite
     *     an empty bot response. Callers that have a fallback text
     *     generator pass it in; others (stream path) omit.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: string, 2: bool}
     *         Filtered products, (possibly rewritten) response,
     *         `true` if products were suppressed and the caller should
     *         emit a "cards cleared" event on its transport.
     */
    public function apply(
        array $products,
        string $botResponse,
        array $queryIntel,
        ?array $detectedIntents,
        ?callable $onIntroTemplate = null,
    ): array {
        if (empty($products)) {
            return [$products, $botResponse, false];
        }

        $hasPositive = (bool) preg_match(self::POSITIVE_REGEX, $botResponse);
        if (!$hasPositive) {
            $hasPositive = $this->mentionsProductByNameNeedle($products, $botResponse);
        }

        $hasClarification = (bool) preg_match(self::CLARIFICATION_REGEX, $botResponse);
        $hasNegative = (bool) preg_match(self::NEGATIVE_REGEX, $botResponse);

        $effectiveQueryType = $queryIntel['type']
            ?? (is_array($detectedIntents) && isset($detectedIntents[0]['name']) ? $detectedIntents[0]['name'] : null)
            ?? 'unknown';
        $isExplicitProductIntent = in_array($effectiveQueryType, self::EXPLICIT_INTENT_TYPES, true);

        if ($hasNegative) {
            return [[], $botResponse, true];
        }

        if ($hasClarification && !$hasPositive) {
            return [[], $botResponse, true];
        }

        if (!$isExplicitProductIntent && !$hasPositive) {
            return [[], $botResponse, true];
        }

        if (
            $isExplicitProductIntent
            && !$hasPositive
            && mb_strlen(trim($botResponse)) < 25
            && $onIntroTemplate !== null
        ) {
            $botResponse = (string) $onIntroTemplate();
        }

        return [$products, $botResponse, false];
    }

    /**
     * With grounded context, the LLM sometimes answers by naming a
     * product (or its category head — "Polistiren" / "Vopsea" /
     * "Adeziv") without matching the positive regex. Count that as
     * a positive grounded reference so we don't rewrite a correct
     * answer.
     *
     * @param array<int, array<string, mixed>> $products
     */
    private function mentionsProductByNameNeedle(array $products, string $botResponse): bool
    {
        $lowerResponse = mb_strtolower($botResponse);
        $seen = [];
        foreach ($products as $p) {
            $name = trim((string) ($p['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $first = (string) (preg_split('/\s+/u', $name)[0] ?? '');
            if (mb_strlen($first) < 4) {
                continue;
            }
            $needle = mb_strtolower($first);
            if (isset($seen[$needle])) {
                continue;
            }
            $seen[$needle] = true;
            if (mb_stripos($lowerResponse, $needle) !== false) {
                return true;
            }
        }
        return false;
    }
}
