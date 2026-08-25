<?php

namespace App\Engines;

use App\Models\Bot;

/**
 * Reservations over a finite resource pool (mese, camere, săli).
 * Distinct from Booking (staff-based) because capacity is managed
 * at resource level, not staff availability.
 *
 * Runtime is live: the actions in app/Actions/Hospitality are wired onto
 * ToolRegistry by HospitalityServiceProvider, and chatTools() serves the
 * manifest narrowed to the bot's niche. Available on chat and — since voice
 * was connected to the same registry — on phone calls too.
 */
class HospitalityEngine extends BaseBotEngine
{
    public function type(): string { return 'hospitality'; }
    public function displayName(): string { return 'Rezervări'; }

    protected function defaultCapabilities(): array
    {
        return ['reservations', 'resource_inventory', 'prepayment'];
    }

    protected function defaultKpiKeys(): array
    {
        return ['reservations_today', 'occupancy_rate'];
    }

    /**
     * OpenAI function-calling manifest for hospitality bots.
     * Mirrors the BookingEngine shape — runtime actions live in
     * app/Actions/Hospitality + app/Services/Hospitality, wired
     * onto ToolRegistry by HospitalityServiceProvider. Returned
     * only when the merging call-site in ChatbotApiController
     * detects engine_type === 'hospitality'.
     */
    /**
     * Tools this bot may use, narrowed to what its niche declares.
     *
     * Previously every hospitality bot was handed all four tools, so a
     * restaurant was offered `check_room_availability` / `reserve_room` and a
     * guesthouse was offered table booking. On voice especially that invites
     * the model to offer a capability the business does not have — a caller
     * asking about a room at a restaurant would get a confident answer.
     *
     * `niches.*.chat_tools` is the allow-list. Names in it that have no
     * definition yet (aspirational entries) are skipped rather than faked,
     * matching BaseBotEngine's contract — advertising a tool with no handler
     * is what makes a model hallucinate calls.
     */
    public function chatTools(Bot $bot): array
    {
        $catalog = $this->toolCatalog();
        $declared = $this->niche($bot)['chat_tools'] ?? null;

        if (!is_array($declared) || $declared === []) {
            // Untemplated bot — keep the previous behaviour rather than
            // silently stripping tools from something already in production.
            return array_values($catalog);
        }

        $manifest = [];
        foreach ($declared as $name) {
            if (isset($catalog[$name])) {
                $manifest[] = $catalog[$name];
            }
        }

        return $manifest;
    }

    /**
     * Every hospitality tool that has a registered handler, keyed by name.
     *
     * @return array<string, array<string, mixed>>
     */
    private function toolCatalog(): array
    {
        return [
            'check_table_availability' => [
                'type' => 'function',
                'name' => 'check_table_availability',
                'description' => 'Verifică ce mese sunt libere într-un interval orar, opțional filtrate pe zonă (terasă / salon) sau număr persoane.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'starts_at'  => ['type' => 'string', 'description' => 'ISO 8601, ex 2026-04-18T19:30:00'],
                        'ends_at'    => ['type' => 'string', 'description' => 'ISO 8601, ex 2026-04-18T21:30:00'],
                        'party_size' => ['type' => 'integer', 'description' => 'Număr persoane (minim capacitate masă)'],
                        'zone'       => ['type' => 'string',  'description' => 'ex "terasa", "salon_1"'],
                    ],
                    'required' => ['starts_at', 'ends_at'],
                ],
            ],
            'reserve_table' => [
                'type' => 'function',
                'name' => 'reserve_table',
                'description' => 'Rezervă o masă returnată de check_table_availability. Confirmă cu clientul mesa + numele înainte.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'resource_id'    => ['type' => 'integer'],
                        'starts_at'      => ['type' => 'string'],
                        'ends_at'        => ['type' => 'string'],
                        'customer_name'  => ['type' => 'string'],
                        'customer_phone' => ['type' => 'string'],
                        'party_size'     => ['type' => 'integer'],
                        'occasion'       => ['type' => 'string', 'description' => 'aniversare / business / etc.'],
                    ],
                    'required' => ['resource_id','starts_at','ends_at','customer_name'],
                ],
            ],
            'check_room_availability' => [
                'type' => 'function',
                'name' => 'check_room_availability',
                'description' => 'Verifică ce camere sunt libere pentru un interval de date (check-in → check-out).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'starts_at'  => ['type' => 'string', 'description' => 'Data check-in, ISO 8601'],
                        'ends_at'    => ['type' => 'string', 'description' => 'Data check-out, ISO 8601'],
                        'party_size' => ['type' => 'integer'],
                    ],
                    'required' => ['starts_at', 'ends_at'],
                ],
            ],
            'search_menu' => [
                'type' => 'function',
                'name' => 'search_menu',
                'description' => 'Caută preparate în meniu. Folosește-o ÎNTOTDEAUNA înainte de a vorbi despre preparate, prețuri, ingrediente sau alergeni — nu răspunde din memorie și nu inventa preparate. Fără `query` întoarce categoriile disponibile.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query'             => ['type' => 'string', 'description' => 'Ce caută clientul — nume de preparat SAU ingredient, ex "ciorbă de burtă", "ceva cu ciuperci"'],
                        'category'          => ['type' => 'string', 'description' => 'Restrânge la o categorie, ex "Supe", "Deserturi"'],
                        'dietary'           => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Filtre: "vegan", "vegetarian", "fără gluten"'],
                        'exclude_allergens' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'ALERGII — chestiune de siguranță, ex ["lactoză","arahide"]'],
                        'exclude_ingredients' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Preferințe, nu alergii, ex ["ceapă"] pentru „nu-mi place ceapa". Pentru alergii folosește exclude_allergens.'],
                        'limit'             => ['type' => 'integer', 'description' => 'Câte preparate să întoarcă. Default 8 — pe telefon nu enumera mai mult de 3-4 deodată.'],
                    ],
                    'required' => [],
                ],
            ],
            /*
             * Ordering. Descriptions carry the operating rules rather than
             * leaving them to the prompt, because the prompt is per-niche and
             * editable by the operator while these ship with the handler. The
             * rule that matters most — never state a total you were not given
             * — is repeated on every tool that returns money, since a model
             * that invents a sum is the failure mode this vertical is built
             * to prevent.
             */
            'add_to_order' => [
                'type' => 'function',
                'name' => 'add_to_order',
                'description' => 'Adaugă preparate în comanda clientului. Trimite TOATE preparatele cerute într-un singur apel. Folosește doar menu_item_id obținut din search_menu — nu ghici id-uri. Întoarce comanda cu totalul recalculat; citește totalul de acolo, nu îl calcula tu.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'items' => [
                            'type'  => 'array',
                            'description' => 'Preparatele de adăugat. Câte un rând pentru fiecare VARIANTĂ, nu pentru fiecare bucată: '
                                . 'trei cola identice = un rând cu quantity 3, dar două shaorma cu sosuri diferite = două rânduri cu quantity 1, '
                                . 'fiecare cu notes-ul lui. O singură notă nu poate descrie două bucăți diferite.',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'menu_item_id' => ['type' => 'integer', 'description' => 'id-ul din search_menu'],
                                    'quantity'     => ['type' => 'integer', 'description' => 'Câte bucăți. Default 1.'],
                                    'options'      => [
                                        'type' => 'array',
                                        'description' => 'Opțiuni cu preț din câmpul `options` al preparatului, ex [{"group":"Mărime","choice":"Mare"}]. Doar valori existente acolo.',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'group'  => ['type' => 'string'],
                                                'choice' => ['type' => 'string'],
                                            ],
                                        ],
                                    ],
                                    'notes' => ['type' => 'string', 'description' => 'Cerințe fără preț, ex "fără ceapă", "bine făcut".'],
                                ],
                                'required' => ['menu_item_id'],
                            ],
                        ],
                    ],
                    'required' => ['items'],
                ],
            ],
            'remove_from_order' => [
                'type' => 'function',
                'name' => 'remove_from_order',
                'description' => 'Șterge o poziție din comandă sau reduce cantitatea. Folosește line_id din review_order sau din răspunsul lui add_to_order.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'line_id'  => ['type' => 'integer', 'description' => 'Poziția de modificat.'],
                        'quantity' => ['type' => 'integer', 'description' => 'Câte bucăți să scadă. Omis = șterge toată poziția.'],
                    ],
                    'required' => ['line_id'],
                ],
            ],
            'review_order' => [
                'type' => 'function',
                'name' => 'review_order',
                'description' => 'Citește comanda curentă cu subtotal, taxă de livrare și total deja calculate. Apeleaz-o înainte de a spune orice sumă clientului și înainte de place_order. NU calcula tu nicio sumă — citește exact valorile primite.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'fulfilment'    => ['type' => 'string', 'description' => '"delivery" sau "pickup" — schimbă taxa de livrare și timpul estimat.'],
                        'delivery_zone' => ['type' => 'string', 'description' => 'Cartierul/zona spusă de client, dacă localul are zone cu taxe diferite.'],
                    ],
                    'required' => [],
                ],
            ],
            'place_order' => [
                'type' => 'function',
                'name' => 'place_order',
                'description' => 'Plasează comanda. Apeleaz-o DOAR după ce ai citit comanda cu review_order, i-ai spus-o clientului cu voce tare și el a confirmat explicit — altfel tool-ul o refuză. Dacă lipsesc date, îți spune în „ask" exact ce să întrebi; pune întrebarea aceea, apoi reia apelul.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'fulfilment'       => ['type' => 'string', 'description' => '"delivery" sau "pickup".'],
                        'customer_name'    => ['type' => 'string', 'description' => 'Numele spus de client, cerut explicit („Pe ce nume notez comanda?"). NU completa cu „Client", „client nou" sau orice alt substitut — sunt respinse și comanda rămâne neplasată.'],
                        'customer_phone'   => ['type' => 'string', 'description' => 'La telefon se completează automat din numărul apelantului. Trimite-l doar dacă clientul cere expres să fie sunat pe ALT număr decât cel de pe care sună.'],
                        'phone_confirmed'  => ['type' => 'boolean', 'description' => 'true dacă i-ai citit clientului numărul de contact și l-a confirmat.'],
                        'delivery_address' => ['type' => 'string', 'description' => 'Obligatoriu la livrare: stradă, număr, bloc/scară/apartament.'],
                        'delivery_zone'    => ['type' => 'string'],
                        'delivery_notes'   => ['type' => 'string', 'description' => 'Ex "interfonul nu merge, sunați la telefon".'],
                        'payment_method'   => ['type' => 'string', 'description' => '"cash" sau "card_on_delivery" — doar din lista acceptată de local.'],
                    ],
                    // Only the fulfilment method is required. customer_name used to be,
                    // and a required field the bot never asked about is one it fills in
                    // itself: both real orders came in under the name "Client". It is
                    // still mandatory to place — enforced by the handler, which answers
                    // with the question to ask instead of a validation error.
                    'required' => ['fulfilment'],
                ],
            ],
            'reserve_room' => [
                'type' => 'function',
                'name' => 'reserve_room',
                'description' => 'Rezervă o cameră returnată de check_room_availability.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'resource_id'    => ['type' => 'integer'],
                        'starts_at'      => ['type' => 'string'],
                        'ends_at'        => ['type' => 'string'],
                        'customer_name'  => ['type' => 'string'],
                        'customer_phone' => ['type' => 'string'],
                        'customer_email' => ['type' => 'string'],
                        'party_size'     => ['type' => 'integer'],
                    ],
                    'required' => ['resource_id','starts_at','ends_at','customer_name'],
                ],
            ],
        ];
    }
}
