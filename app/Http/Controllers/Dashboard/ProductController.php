<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\WooCommerceProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Dashboard CRUD pentru produse/servicii adăugate manual din UI.
 *
 * Folosim tabela `woocommerce_products` chiar și pentru intrări non-WooCommerce
 * pentru că restul stivei (chat retrieval, recommendations, voice) e tunat pe
 * acel schema. Produsele manuale au `wc_product_id` negativ și `site_url`
 * generic („manual:bot-{id}") ca să nu se ciocnească cu cele sync-uate din WP.
 */
class ProductController extends Controller
{
    /**
     * Lista produselor pentru un bot — manuale + sync.
     */
    public function index(Request $request, Bot $bot)
    {
        $this->authorize('view', $bot);

        $q = WooCommerceProduct::where('bot_id', $bot->id);
        if ($request->filled('search')) {
            $needle = trim((string) $request->input('search'));
            $q->where(function ($w) use ($needle) {
                $w->where('name', 'ILIKE', '%' . $needle . '%')
                  ->orWhere('sku', 'ILIKE', '%' . $needle . '%');
            });
        }
        $source = $request->input('source', 'all');
        if ($source === 'manual') {
            $q->where('site_url', 'LIKE', 'manual:%');
        } elseif ($source === 'sync') {
            $q->where('site_url', 'NOT LIKE', 'manual:%');
        }

        $products = $q->orderBy('name')->paginate(40)->withQueryString();

        $counts = [
            'total' => WooCommerceProduct::where('bot_id', $bot->id)->count(),
            'manual' => WooCommerceProduct::where('bot_id', $bot->id)->where('site_url', 'LIKE', 'manual:%')->count(),
        ];
        $counts['sync'] = $counts['total'] - $counts['manual'];

        return view('dashboard.bots.products.index', compact('bot', 'products', 'source', 'counts'));
    }

    public function create(Bot $bot)
    {
        $this->authorize('update', $bot);
        return view('dashboard.bots.products.form', ['bot' => $bot, 'product' => null]);
    }

    public function store(Request $request, Bot $bot)
    {
        $this->authorize('update', $bot);
        $data = $this->validateForm($request);

        // wc_product_id pe produsele manuale: numere negative incrementale
        // pentru bot-ul respectiv. Evită ciocnirea cu PK-urile reale WP.
        $minWcId = WooCommerceProduct::where('bot_id', $bot->id)->min('wc_product_id');
        $nextWcId = min((int) ($minWcId ?? 0), 0) - 1;

        WooCommerceProduct::create(array_merge($data, [
            'bot_id' => $bot->id,
            'wc_product_id' => $nextWcId,
            'site_url' => 'manual:bot-' . $bot->id,
            'stock_status' => $data['stock_status'] ?? 'instock',
            'currency' => $data['currency'] ?? 'RON',
        ]));

        return redirect()
            ->route('dashboard.bots.products.index', $bot)
            ->with('success', 'Produs adăugat.');
    }

    public function edit(Bot $bot, WooCommerceProduct $product)
    {
        $this->authorize('update', $bot);
        abort_unless($product->bot_id === $bot->id, 404);
        return view('dashboard.bots.products.form', compact('bot', 'product'));
    }

    public function update(Request $request, Bot $bot, WooCommerceProduct $product)
    {
        $this->authorize('update', $bot);
        abort_unless($product->bot_id === $bot->id, 404);

        $data = $this->validateForm($request);
        $product->update($data);

        return redirect()
            ->route('dashboard.bots.products.index', $bot)
            ->with('success', 'Produs actualizat.');
    }

    public function destroy(Bot $bot, WooCommerceProduct $product)
    {
        $this->authorize('update', $bot);
        abort_unless($product->bot_id === $bot->id, 404);
        $product->delete();
        return redirect()
            ->route('dashboard.bots.products.index', $bot)
            ->with('success', 'Produs șters.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateForm(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_description' => 'nullable|string|max:5000',
            'price' => 'nullable|numeric|min:0|max:9999999',
            'regular_price' => 'nullable|numeric|min:0|max:9999999',
            'sale_price' => 'nullable|numeric|min:0|max:9999999',
            'currency' => 'nullable|string|max:8',
            'price_unit' => 'nullable|string|max:32',
            'sku' => 'nullable|string|max:64',
            'stock_status' => 'nullable|string|in:instock,outofstock,onbackorder',
            'image_url' => 'nullable|url|max:500',
            'permalink' => 'nullable|url|max:500',
            'categories_csv' => 'nullable|string|max:500',
        ]);

        $categories = isset($validated['categories_csv'])
            ? array_values(array_filter(array_map('trim', explode(',', $validated['categories_csv']))))
            : [];

        unset($validated['categories_csv']);
        $validated['categories'] = $categories;

        // semantic_text combină name + short_description pentru retrieval
        // pe ramura de produse din IntentOrchestrator.
        $validated['semantic_text'] = Str::limit(
            ($validated['name'] ?? '') . "\n" . ($validated['short_description'] ?? ''),
            2000,
            ''
        );

        return $validated;
    }
}
