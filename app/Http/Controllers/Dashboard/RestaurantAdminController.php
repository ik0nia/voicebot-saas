<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\RestaurantOrder;
use App\Models\RestaurantSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Dashboard for the restaurant ordering vertical: incoming orders, the menu,
 * and the delivery policy.
 *
 * Until this controller existed the whole vertical was headless — orders
 * landed in the database, the menu could only be changed by re-importing a
 * JSON file, and ordering could only be switched on from an artisan command.
 * A venue could take a phone order and never find out.
 *
 * Every action is gated on the bot being on the hospitality engine, because
 * the ordering tools are, and a menu attached to a bot that can never offer
 * it is just confusing. Tenancy comes from BotPolicy plus the BelongsToTenant
 * global scope on every model here; the explicit bot_id checks below are the
 * second lock, for the case where a row belongs to the right tenant but the
 * wrong bot.
 */
class RestaurantAdminController extends Controller
{
    /** Kitchen lifecycle, in the order an operator walks through it. */
    private const STATUS_FLOW = [
        RestaurantOrder::STATUS_PLACED           => 'Primită',
        RestaurantOrder::STATUS_CONFIRMED        => 'Confirmată',
        RestaurantOrder::STATUS_PREPARING        => 'În preparare',
        RestaurantOrder::STATUS_OUT_FOR_DELIVERY => 'La livrare',
        RestaurantOrder::STATUS_COMPLETED        => 'Finalizată',
        RestaurantOrder::STATUS_CANCELED         => 'Anulată',
    ];

    /** Statuses that mean the venue still owes the customer food. */
    private const ACTIVE_STATUSES = [
        RestaurantOrder::STATUS_PLACED,
        RestaurantOrder::STATUS_CONFIRMED,
        RestaurantOrder::STATUS_PREPARING,
        RestaurantOrder::STATUS_OUT_FOR_DELIVERY,
    ];

    // -------------------------------------------------- orders (whole account)

    /**
     * Every order across the account, for the sidebar entry.
     *
     * The per-bot page below is reached from the agent's own screen and keeps
     * its bot context; this one is the operational view a venue actually
     * lives in during service, where "which agent took it" matters less than
     * "what has to go out now". Multi-location accounts filter by bot.
     */
    public function allOrders(Request $request)
    {
        $this->authorize('viewAny', Bot::class);

        /*
         * Tenancy is left to TenantScope rather than filtered by hand on
         * user()->tenant_id. The scope already resolves all four cases — a
         * normal user's own tenant, a super-admin impersonating a tenant
         * (`admin_as_tenant_id`), a super-admin in aggregate mode
         * (`admin_view_all`), and a super-admin who picked neither. Reading
         * the column directly would show a super-admin an empty page while
         * they are impersonating the very venue they are looking at.
         */
        $bots = Bot::where('engine_type', 'hospitality')
            ->orderBy('name')
            ->get();

        // Nothing on the hospitality engine means nothing can ever have taken
        // an order. Say so plainly instead of rendering an empty table that
        // looks like a bug.
        if ($bots->isEmpty()) {
            return view('dashboard.restaurant.orders-empty');
        }

        $status = (string) $request->input('status', 'active');
        $botId  = $request->integer('bot') ?: null;

        $query = RestaurantOrder::whereIn('bot_id', $bots->pluck('id'))
            ->with(['items', 'bot']);

        if ($botId && $bots->contains('id', $botId)) {
            $query->where('bot_id', $botId);
        }

        $this->applyStatusFilter($query, $status);
        $this->applySearch($query, $request);

        return view('dashboard.restaurant.orders', [
            'orders'   => $query->latest('id')->paginate(30)->withQueryString(),
            'status'   => $status,
            'bots'     => $bots,
            'botId'    => $botId,
            'counts'   => $this->statusCounts(
                RestaurantOrder::whereIn('bot_id', $bots->pluck('id')),
            ),
        ]);
    }

    // ---------------------------------------------------------------- orders

    public function orders(Request $request, Bot $bot)
    {
        $this->authorize('view', $bot);
        $this->assertHospitality($bot);

        $status = (string) $request->input('status', 'active');

        $query = RestaurantOrder::where('bot_id', $bot->id)->with('items');

        $this->applyStatusFilter($query, $status);
        $this->applySearch($query, $request);

        return view('dashboard.bots.restaurant.orders', [
            'bot'      => $bot,
            'orders'   => $query->latest('id')->paginate(30)->withQueryString(),
            'status'   => $status,
            'counts'   => $this->statusCounts(RestaurantOrder::where('bot_id', $bot->id)),
            'statuses' => self::STATUS_FLOW,
            'settings' => RestaurantSetting::forBot($bot->id),
        ]);
    }

    /**
     * "Active" is the default everywhere because it is the only view an
     * operator needs during service: everything owed to a customer right now.
     * Drafts are excluded from it — a basket abandoned mid-call is not work,
     * it is a lead — but they get their own tab rather than being hidden,
     * since ringing back an abandoned order is the single most valuable thing
     * these tables enable.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<RestaurantOrder>  $query
     */
    private function applyStatusFilter($query, string $status): void
    {
        match ($status) {
            'active'    => $query->whereIn('status', self::ACTIVE_STATUSES),
            'draft'     => $query->where('status', RestaurantOrder::STATUS_DRAFT),
            'completed' => $query->where('status', RestaurantOrder::STATUS_COMPLETED),
            'canceled'  => $query->where('status', RestaurantOrder::STATUS_CANCELED),
            default     => null,
        };
    }

    /** @param  \Illuminate\Database\Eloquent\Builder<RestaurantOrder>  $query */
    private function applySearch($query, Request $request): void
    {
        if (!$request->filled('search')) {
            return;
        }

        $needle = trim((string) $request->input('search'));

        $query->where(function ($w) use ($needle) {
            $w->where('customer_name', 'ILIKE', '%' . $needle . '%')
              ->orWhere('customer_phone', 'ILIKE', '%' . $needle . '%')
              ->orWhere('delivery_address', 'ILIKE', '%' . $needle . '%');

            // Order references are zero-padded ids, so "00014" and "14" have
            // to find the same order — an operator reads whichever the
            // customer wrote down.
            $digits = ltrim($needle, '0');
            if ($digits !== '' && ctype_digit($digits)) {
                $w->orWhere('id', (int) $digits);
            }
        });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<RestaurantOrder>  $base
     * @return array<string, int>
     */
    private function statusCounts($base): array
    {
        return [
            'all'       => (clone $base)->count(),
            'active'    => (clone $base)->whereIn('status', self::ACTIVE_STATUSES)->count(),
            'draft'     => (clone $base)->where('status', RestaurantOrder::STATUS_DRAFT)->count(),
            'completed' => (clone $base)->where('status', RestaurantOrder::STATUS_COMPLETED)->count(),
            'canceled'  => (clone $base)->where('status', RestaurantOrder::STATUS_CANCELED)->count(),
        ];
    }

    public function order(Bot $bot, RestaurantOrder $order)
    {
        $this->authorize('view', $bot);
        $this->assertHospitality($bot);
        abort_unless($order->bot_id === $bot->id, 404);

        $order->load(['items', 'call', 'conversation']);

        return view('dashboard.bots.restaurant.order', [
            'bot'      => $bot,
            'order'    => $order,
            'statuses' => self::STATUS_FLOW,
        ]);
    }

    public function updateOrderStatus(Request $request, Bot $bot, RestaurantOrder $order)
    {
        $this->authorize('update', $bot);
        $this->assertHospitality($bot);
        abort_unless($order->bot_id === $bot->id, 404);

        $data = $request->validate([
            'status'        => ['required', Rule::in(array_keys(self::STATUS_FLOW))],
            'cancel_reason' => 'nullable|string|max:255',
        ]);

        $update = ['status' => $data['status']];

        if ($data['status'] === RestaurantOrder::STATUS_CANCELED) {
            $update['canceled_at']   = now();
            $update['cancel_reason'] = $data['cancel_reason'] ?? null;
        }

        /*
         * A draft promoted by hand still needs a placed_at, otherwise it reads
         * as an order nobody ever confirmed. This happens when an operator
         * rings back an abandoned basket and takes the order themselves.
         */
        if ($order->placed_at === null && $data['status'] !== RestaurantOrder::STATUS_CANCELED) {
            $update['placed_at'] = now();
        }

        $order->update($update);

        return back()->with('success', 'Comanda ' . $order->reference() . ' → ' . self::STATUS_FLOW[$data['status']] . '.');
    }

    // ------------------------------------------------------------------ menu

    public function menu(Request $request, Bot $bot)
    {
        $this->authorize('view', $bot);
        $this->assertHospitality($bot);

        $categories = MenuCategory::where('bot_id', $bot->id)
            ->with(['items' => fn ($q) => $q->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Dishes whose category was deleted out from under them would
        // otherwise be invisible here while still being orderable by phone.
        $orphans = MenuItem::where('bot_id', $bot->id)
            ->whereNull('menu_category_id')
            ->orderBy('name')
            ->get();

        return view('dashboard.bots.restaurant.menu', [
            'bot'        => $bot,
            'categories' => $categories,
            'orphans'    => $orphans,
            'settings'   => RestaurantSetting::forBot($bot->id),
        ]);
    }

    public function storeCategory(Request $request, Bot $bot)
    {
        $this->authorize('update', $bot);
        $this->assertHospitality($bot);

        $data = $request->validate([
            'name'        => 'required|string|max:120',
            'description' => 'nullable|string|max:500',
            'sort_order'  => 'nullable|integer|min:0|max:9999',
        ]);

        MenuCategory::create([
            'tenant_id'   => $bot->tenant_id,
            'bot_id'      => $bot->id,
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'sort_order'  => $data['sort_order'] ?? 0,
            'is_active'   => true,
        ]);

        return back()->with('success', 'Categorie adăugată.');
    }

    public function updateCategory(Request $request, Bot $bot, MenuCategory $category)
    {
        $this->authorize('update', $bot);
        $this->assertHospitality($bot);
        abort_unless($category->bot_id === $bot->id, 404);

        $data = $request->validate([
            'name'        => 'required|string|max:120',
            'description' => 'nullable|string|max:500',
            'sort_order'  => 'nullable|integer|min:0|max:9999',
            'is_active'   => 'nullable|boolean',
        ]);

        $category->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'sort_order'  => $data['sort_order'] ?? 0,
            'is_active'   => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Categorie actualizată.');
    }

    public function destroyCategory(Bot $bot, MenuCategory $category)
    {
        $this->authorize('update', $bot);
        $this->assertHospitality($bot);
        abort_unless($category->bot_id === $bot->id, 404);

        // Detach rather than cascade: deleting a section must not silently
        // delete the dishes in it, which an operator reorganising the menu
        // would not expect and could not undo.
        MenuItem::where('menu_category_id', $category->id)->update(['menu_category_id' => null]);
        $category->delete();

        return back()->with('success', 'Categorie ștearsă. Preparatele au rămas, fără categorie.');
    }

    public function createItem(Request $request, Bot $bot)
    {
        $this->authorize('update', $bot);
        $this->assertHospitality($bot);

        return view('dashboard.bots.restaurant.item-form', [
            'bot'        => $bot,
            'item'       => null,
            'categories' => MenuCategory::where('bot_id', $bot->id)->orderBy('sort_order')->orderBy('name')->get(),
            'presetCategoryId' => $request->integer('category'),
        ]);
    }

    public function storeItem(Request $request, Bot $bot)
    {
        $this->authorize('update', $bot);
        $this->assertHospitality($bot);

        $data = $this->validateItem($request, $bot);
        $data['tenant_id'] = $bot->tenant_id;
        $data['bot_id']    = $bot->id;

        MenuItem::create($data);

        return redirect()
            ->route('dashboard.bots.restaurant.menu', $bot)
            ->with('success', 'Preparat adăugat.');
    }

    public function editItem(Bot $bot, MenuItem $item)
    {
        $this->authorize('update', $bot);
        $this->assertHospitality($bot);
        abort_unless($item->bot_id === $bot->id, 404);

        return view('dashboard.bots.restaurant.item-form', [
            'bot'        => $bot,
            'item'       => $item,
            'categories' => MenuCategory::where('bot_id', $bot->id)->orderBy('sort_order')->orderBy('name')->get(),
            'presetCategoryId' => null,
        ]);
    }

    public function updateItem(Request $request, Bot $bot, MenuItem $item)
    {
        $this->authorize('update', $bot);
        $this->assertHospitality($bot);
        abort_unless($item->bot_id === $bot->id, 404);

        $item->update($this->validateItem($request, $bot));

        return redirect()
            ->route('dashboard.bots.restaurant.menu', $bot)
            ->with('success', 'Preparat actualizat.');
    }

    public function destroyItem(Bot $bot, MenuItem $item)
    {
        $this->authorize('update', $bot);
        $this->assertHospitality($bot);
        abort_unless($item->bot_id === $bot->id, 404);

        $name = $item->name;
        $item->delete();

        return back()->with('success', '„' . $name . '" a fost șters din meniu.');
    }

    /**
     * Flip a dish on or off without opening the form.
     *
     * This is the action a venue takes mid-service ("s-a terminat ceafa"), so
     * it has to be one click from the menu list. Marking unavailable rather
     * than deleting keeps the dish and its price for tomorrow, and keeps past
     * orders readable.
     */
    public function toggleItem(Bot $bot, MenuItem $item)
    {
        $this->authorize('update', $bot);
        $this->assertHospitality($bot);
        abort_unless($item->bot_id === $bot->id, 404);

        $item->update(['is_available' => !$item->is_available]);

        return back()->with('success', '„' . $item->name . '" este acum '
            . ($item->is_available ? 'disponibil' : 'indisponibil') . '.');
    }

    // -------------------------------------------------------------- settings

    public function settings(Bot $bot)
    {
        $this->authorize('view', $bot);
        $this->assertHospitality($bot);

        return view('dashboard.bots.restaurant.settings', [
            'bot'      => $bot,
            // A bot that has never been configured gets an unsaved model, not
            // null, so the form renders with the same defaults the tools would
            // apply once it is saved.
            'settings' => RestaurantSetting::forBot($bot->id) ?? new RestaurantSetting(),
            'configured' => RestaurantSetting::forBot($bot->id) !== null,
            // Only available dishes can be suggested — offering the operator
            // a dish they have switched off would produce a recommendation
            // the bot then silently drops.
            'menuItems' => MenuItem::where('bot_id', $bot->id)
                ->where('is_available', true)
                ->with('category')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function updateSettings(Request $request, Bot $bot)
    {
        $this->authorize('update', $bot);
        $this->assertHospitality($bot);

        $data = $request->validate([
            'ordering_enabled'    => 'nullable|boolean',
            'delivery_enabled'    => 'nullable|boolean',
            'pickup_enabled'      => 'nullable|boolean',
            'delivery_zones_only' => 'nullable|boolean',
            'delivery_fee'        => 'nullable|string|max:16',
            'free_delivery_threshold' => 'nullable|string|max:16',
            'min_order'           => 'nullable|string|max:16',
            'delivery_minutes'    => 'nullable|integer|min:1|max:600',
            'pickup_minutes'      => 'nullable|integer|min:1|max:600',
            'currency'            => 'nullable|string|size:3',
            'order_notice'        => 'nullable|string|max:500',
            'payment_methods'     => 'nullable|array',
            'payment_methods.*'   => ['string', Rule::in([
                RestaurantSetting::PAYMENT_CASH,
                RestaurantSetting::PAYMENT_CARD_ON_DELIVERY,
            ])],
            'zone_name'      => 'nullable|array',
            'zone_name.*'    => 'nullable|string|max:120',
            'zone_fee'       => 'nullable|array',
            'zone_fee.*'     => 'nullable|string|max:16',
            'zone_min'       => 'nullable|array',
            'zone_min.*'     => 'nullable|string|max:16',
            'featured_item_ids'   => 'nullable|array|max:3',
            'featured_item_ids.*' => 'integer',
        ]);

        $zones = [];
        foreach ((array) ($data['zone_name'] ?? []) as $i => $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }

            $zone = [
                'name'      => $name,
                'fee_cents' => $this->toCents($data['zone_fee'][$i] ?? null) ?? 0,
            ];

            // A per-zone minimum is genuinely optional and null means "use the
            // venue-wide one" downstream, so an empty box must not become a 0
            // that overrides it.
            $min = $this->toCents($data['zone_min'][$i] ?? null);
            if ($min !== null) {
                $zone['min_order_cents'] = $min;
            }

            $zones[] = $zone;
        }

        $payload = [
            'tenant_id'           => $bot->tenant_id,
            'bot_id'              => $bot->id,
            'ordering_enabled'    => $request->boolean('ordering_enabled'),
            'delivery_enabled'    => $request->boolean('delivery_enabled'),
            'pickup_enabled'      => $request->boolean('pickup_enabled'),
            'delivery_zones_only' => $request->boolean('delivery_zones_only'),
            'delivery_fee_cents'  => $this->toCents($data['delivery_fee'] ?? null) ?? 0,
            'free_delivery_threshold_cents' => $this->toCents($data['free_delivery_threshold'] ?? null),
            'min_order_cents'     => $this->toCents($data['min_order'] ?? null) ?? 0,
            'delivery_minutes'    => $data['delivery_minutes'] ?? 45,
            'pickup_minutes'      => $data['pickup_minutes'] ?? 20,
            'delivery_zones'      => $zones,
            'payment_methods'     => array_values($data['payment_methods'] ?? [RestaurantSetting::PAYMENT_CASH]),
            'currency'            => strtoupper($data['currency'] ?? 'RON'),
            'order_notice'        => $data['order_notice'] ?? null,
            // Filtered against this bot's own menu: an id from another
            // tenant's menu would otherwise be read back at call time.
            'featured_item_ids'   => MenuItem::where('bot_id', $bot->id)
                ->whereIn('id', $data['featured_item_ids'] ?? [])
                ->pluck('id')
                // The operator's order is the speaking order, so restore the
                // sequence they submitted rather than the database's.
                ->sortBy(fn ($id) => array_search($id, $data['featured_item_ids'] ?? [], false))
                ->values()
                ->all(),
        ];

        RestaurantSetting::withoutGlobalScopes()->updateOrCreate(
            ['bot_id' => $bot->id],
            $payload,
        );

        return back()->with('success', 'Setările de comandă au fost salvate.');
    }

    // ----------------------------------------------------------------- utils

    /**
     * @return array<string, mixed>
     */
    private function validateItem(Request $request, Bot $bot): array
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:180',
            'menu_category_id' => 'nullable|integer',
            'description'      => 'nullable|string|max:1000',
            'price'            => 'required|string|max:16',
            'portion'          => 'nullable|string|max:60',
            'prep_time_minutes'=> 'nullable|integer|min:0|max:600',
            'sort_order'       => 'nullable|integer|min:0|max:9999',
            'aliases_csv'      => 'nullable|string|max:500',
            'ingredients_csv'  => 'nullable|string|max:1000',
            'allergens_csv'    => 'nullable|string|max:500',
            'is_available'     => 'nullable|boolean',
            'is_vegetarian'    => 'nullable|boolean',
            'is_vegan'         => 'nullable|boolean',
            'is_gluten_free'   => 'nullable|boolean',
            'is_spicy'         => 'nullable|boolean',
        ]);

        $categoryId = $validated['menu_category_id'] ?? null;
        if ($categoryId) {
            // Reassigning a dish into another tenant's category would leak the
            // dish into their menu listing.
            abort_unless(
                MenuCategory::where('bot_id', $bot->id)->whereKey($categoryId)->exists(),
                404,
            );
        }

        $price = $this->toCents($validated['price']);
        if ($price === null) {
            abort(422, 'Preț invalid.');
        }

        return [
            'menu_category_id' => $categoryId ?: null,
            'name'             => $validated['name'],
            'description'      => $validated['description'] ?? null,
            'price_cents'      => $price,
            'currency'         => RestaurantSetting::forBot($bot->id)?->currency ?: 'RON',
            'portion'          => $validated['portion'] ?? null,
            'prep_time_minutes'=> $validated['prep_time_minutes'] ?? null,
            'sort_order'       => $validated['sort_order'] ?? 0,
            'aliases'          => $this->csvToList($validated['aliases_csv'] ?? null),
            /*
             * Ingredients and allergens stay empty unless someone typed them.
             * Inferring "feta" from "Șnițel feta" is exactly the guess the
             * voice prompt refuses to make, and a wrong allergen list is a
             * safety problem, not a cosmetic one.
             */
            'ingredients'      => $this->csvToList($validated['ingredients_csv'] ?? null),
            'allergens'        => $this->csvToList($validated['allergens_csv'] ?? null),
            'is_available'     => $request->boolean('is_available'),
            'is_vegetarian'    => $request->boolean('is_vegetarian'),
            'is_vegan'         => $request->boolean('is_vegan'),
            'is_gluten_free'   => $request->boolean('is_gluten_free'),
            'is_spicy'         => $request->boolean('is_spicy'),
        ];
    }

    /**
     * "24,99" / "24.99" / "25" → cents. Null for an empty box.
     *
     * Romanian keyboards and Romanian habit both produce the comma, and a
     * naive (float) cast on "24,99" yields 24.0 — a 99-bani error that nobody
     * notices until the totals stop matching.
     *
     * @return int|null
     */
    private function toCents(?string $value): ?int
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalised = str_replace([' ', ','], ['', '.'], trim($value));

        if (!is_numeric($normalised)) {
            return null;
        }

        return (int) round(((float) $normalised) * 100);
    }

    /** @return list<string> */
    private function csvToList(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    private function assertHospitality(Bot $bot): void
    {
        abort_unless($bot->engine_type === 'hospitality', 404);
    }
}
