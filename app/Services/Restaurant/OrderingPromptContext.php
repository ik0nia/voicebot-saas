<?php

declare(strict_types=1);

namespace App\Services\Restaurant;

use App\Models\Bot;
use App\Models\MenuItem;
use App\Models\RestaurantSetting;

/**
 * The ordering block appended to a restaurant bot's system prompt.
 *
 * Two things belong here rather than in `bots.system_prompt`, and both for
 * the same reason: they change without anyone editing a prompt.
 *
 * The suggestions are the obvious one. "Ce aveți?" is the most common opening
 * on a food call and the bot needs two or three concrete answers, but a dish
 * named in a static prompt keeps being offered after the venue stops making
 * it and keeps being quoted at last month's price. Reading them from the menu
 * at call time means switching a dish off in the dashboard removes it from
 * the bot's mouth on the next call, with nothing else to remember.
 *
 * The prices are the subtle one. They are included so the bot can name a
 * suggestion and its price in one breath — "avem doner kebab la 24,99" —
 * without a search_menu round-trip first, which on a phone call is a second
 * of dead air at exactly the moment the caller is deciding. These prices are
 * still venue data read from the database, not model arithmetic; the rule
 * this vertical is built on is that the model never *computes* a number, and
 * reading one back is what it is for.
 */
class OrderingPromptContext
{
    private const MAX_SUGGESTIONS = 3;

    /**
     * Returns null when there is nothing to say — the bot then just asks what
     * the caller would like, which is correct. An empty block is better than
     * an invented one.
     */
    public function for(Bot $bot): ?string
    {
        if (($bot->engine_type ?? null) !== 'hospitality') {
            return null;
        }

        $settings = RestaurantSetting::forBot($bot->id);

        if ($settings === null || !$settings->ordering_enabled) {
            return null;
        }

        $lines = [];
        $suggestions = $this->suggestions($bot, $settings);

        if ($suggestions !== []) {
            $lines[] = 'CE SUGEREZI CÂND CLIENTUL NU ȘTIE CE VREA';
            $lines[] = 'Dacă întreabă „ce aveți?" sau ezită, numește exact aceste preparate, cu prețul lor:';
            foreach ($suggestions as $suggestion) {
                $lines[] = '- ' . $suggestion;
            }
            // The count comes from the list, not from a hardcoded "trei" — a
            // venue that picked two dishes, or whose third went unavailable
            // this morning, would otherwise have the bot announce three and
            // name two.
            $lines[] = (count($suggestions) === 1
                    ? 'Spune-l într-o propoziție scurtă'
                    : 'Spune-le pe toate ' . $this->numberWord(count($suggestions)) . ' într-o singură propoziție scurtă')
                . ', apoi întreabă ce preferă. '
                . 'Nu inventa alte recomandări și nu adăuga preparate care nu sunt în lista de mai sus. '
                . 'Pentru orice altceva din meniu folosește search_menu.';
            $lines[] = '';
        }

        /*
         * The operating rules for taking an order.
         *
         * They live here, not in the niche's `prompt_addon`, because that text
         * is copied into `bots.system_prompt` when a bot is created and never
         * again: bot 79 — the one that took both real orders — has none of it,
         * not even the rule against doing arithmetic. Anything that must hold
         * on every order has to be injected at turn time, on both channels,
         * exactly like the suggestions above.
         *
         * Kept short on purpose. This block is paid for on every single turn,
         * and the tools enforce all of it anyway; the text is here to save a
         * round-trip, not to be the guarantee.
         */
        $lines[] = 'CUM IEI O COMANDĂ';
        $lines[] = '- NU CALCULA NICIODATĂ SUME. Fiecare preț sau total pe care îl spui e copiat exact dintr-un răspuns de tool. '
            . 'Dacă n-ai primit o sumă, cere-o cu review_order — nu o deduce.';
        $lines[] = '- Ordinea: search_menu → add_to_order → review_order → îi citești comanda și totalul → place_order, '
            . 'doar după un „da" explicit. place_order refuză o comandă necitită.';
        $lines[] = '- Numele se cere, nu se presupune: „Pe ce nume notez comanda?". Nu trimite „Client" sau alt substitut — sunt respinse.';
        $lines[] = '- Numărul de telefon îl ai deja din apel. Nu-l cere de la zero: citește-l înapoi cifră cu cifră, '
            . 'în aceeași replică în care ceri numele. Alt număr dorit de client îl trimiți ca customer_phone.';
        $lines[] = '- Un rând per variantă, nu per bucată: trei cola identice = un rând cu quantity 3, '
            . 'dar două shaorma cu sosuri diferite = două rânduri cu quantity 1, fiecare cu notes-ul ei.';
        $lines[] = '- În „notes" scrii doar ce face bucătăria („fără ceapă", „sos de usturoi"). '
            . 'Nimic despre tine sau despre cât de sigur ești — nota se tipărește pe bon.';
        $lines[] = '- Când un tool întoarce câmpul „ask", pui exact întrebarea de acolo, apoi reiei apelul.';
        $lines[] = '';

        /*
         * The upsell prompt is deliberately conditional on the basket already
         * having something in it. Asking "doriți altceva?" before anything has
         * been ordered is just the greeting again, and asking it after every
         * single line turns a two-item order into an interrogation.
         */
        $lines[] = 'DUPĂ CE ADAUGI PREPARATE ÎN COMANDĂ';
        $lines[] = 'Confirmă scurt ce ai adăugat, apoi întreabă o singură dată dacă mai dorește altceva din meniu — '
            . 'o băutură sau o garnitură, dacă nu are deja. Dacă spune că nu, treci mai departe la livrare; '
            . 'nu insista și nu repeta întrebarea la fiecare preparat.';

        return implode("\n", $lines);
    }

    /** Capped at MAX_SUGGESTIONS, so only these two ever occur. */
    private function numberWord(int $n): string
    {
        return $n === 2 ? 'două' : 'trei';
    }

    /**
     * The venue's chosen dishes, filtered to what is actually orderable now.
     *
     * Order follows `featured_item_ids`, not the database's — the venue put
     * its best dish first and the bot should say it first. Unavailable and
     * deleted dishes fall out silently, which is the whole point of storing
     * ids instead of text.
     *
     * @return list<string>
     */
    private function suggestions(Bot $bot, RestaurantSetting $settings): array
    {
        $ids = array_values(array_filter(array_map(
            'intval',
            (array) ($settings->featured_item_ids ?? []),
        )));

        if ($ids === []) {
            return [];
        }

        $items = MenuItem::withoutGlobalScopes()
            ->where('bot_id', $bot->id)
            ->whereIn('id', $ids)
            ->where('is_available', true)
            ->get()
            ->keyBy('id');

        $out = [];
        foreach ($ids as $id) {
            $item = $items->get($id);
            if ($item === null) {
                continue;
            }

            $line = $item->name . ' — ' . $item->formattedPrice();
            if ($item->portion) {
                $line .= ' (' . $item->portion . ')';
            }
            $out[] = $line;

            if (count($out) >= self::MAX_SUGGESTIONS) {
                break;
            }
        }

        return $out;
    }
}
