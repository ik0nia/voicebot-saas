<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\KnowledgeConnector;
use App\Models\WcMetaMapping;
use Illuminate\Http\Request;

/**
 * Per-bot UI for mapping raw WooCommerce meta_data keys onto
 * standardized fields the bot prompt can consume. Accessible from
 * the bot edit page once a WooCommerce connector is attached.
 *
 * The page is entirely tenant-admin facing — non-technical language,
 * sample values inline, suggested mappings auto-populated where
 * obvious, explicit "ignore" button to hide keys that never matter.
 */
class WcMetaMappingController extends Controller
{
    /**
     * Render the mapping UI for a single bot.
     */
    public function index(Bot $bot)
    {
        $this->authorize('view', $bot);

        $connector = KnowledgeConnector::where('bot_id', $bot->id)
            ->where('type', 'woocommerce')
            ->where('status', 'connected')
            ->first();

        if (!$connector) {
            return redirect()->route('dashboard.bots.show', $bot)
                ->with('info', 'Conectează mai întâi magazinul WooCommerce din secțiunea Cunoștințe.');
        }

        // Show mappings ordered by coverage (most widely-used keys
        // first — the ones with >100 products tend to be the
        // platform-level ones worth mapping) then alphabetically.
        $mappings = WcMetaMapping::where('connector_id', $connector->id)
            ->orderByDesc('product_count')
            ->orderBy('meta_key')
            ->get();

        return view('dashboard.bots.wc_meta_mappings', [
            'bot' => $bot,
            'connector' => $connector,
            'mappings' => $mappings,
            'standardFields' => WcMetaMapping::STANDARD_FIELDS,
        ]);
    }

    /**
     * Persist the operator's choices. One POST carries the full set —
     * avoids race conditions with concurrent edits and keeps the UX
     * simple (one Save button at the bottom).
     */
    public function update(Request $request, Bot $bot)
    {
        $this->authorize('update', $bot);

        $validated = $request->validate([
            'mappings' => 'required|array',
            'mappings.*.id' => 'required|integer',
            'mappings.*.standard_field' => 'nullable|string|max:80',
            'mappings.*.label' => 'nullable|string|max:120',
        ]);

        $connector = KnowledgeConnector::where('bot_id', $bot->id)
            ->where('type', 'woocommerce')
            ->firstOrFail();

        $standardCatalog = array_keys(WcMetaMapping::STANDARD_FIELDS);
        $updated = 0;

        foreach ($validated['mappings'] as $m) {
            $mapping = WcMetaMapping::where('connector_id', $connector->id)
                ->where('id', $m['id'])
                ->first();
            if (!$mapping) continue;

            $stdField = $m['standard_field'] ?? null;
            $label = $m['label'] ?? null;

            // Accept: null (ignore), catalog name, or "custom:<slug>"
            if ($stdField !== null && $stdField !== '') {
                $isCustom = str_starts_with($stdField, 'custom:');
                $isCatalog = in_array($stdField, $standardCatalog, true);
                if (!$isCustom && !$isCatalog) {
                    // Invalid → ignore this one rather than 422 the
                    // whole form. Keeps UX forgiving.
                    continue;
                }
                // Default label from catalog if the tenant didn't set one
                if (!$label && $isCatalog) {
                    $label = WcMetaMapping::STANDARD_FIELDS[$stdField]['label'] ?? null;
                }
            } else {
                $stdField = null;
                $label = null;
            }

            $mapping->update([
                'standard_field' => $stdField,
                'label' => $label,
            ]);
            $updated++;
        }

        return back()->with('success', "Am salvat {$updated} mapări. Se aplică de la următoarea sincronizare.");
    }
}
