<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PluginUpdateController extends Controller
{
    /**
     * Plugin update check endpoint for WordPress auto-updater.
     * Called by Sambla_Updater in the WordPress plugin.
     */
    public function check(Request $request): JsonResponse
    {
        $slug = $request->query('slug', '');
        $currentVersion = $request->query('version', '0.0.0');
        $info = $request->query('info');

        if ($slug !== 'sambla-woocommerce') {
            return response()->json(['error' => 'Unknown plugin'], 404);
        }

        $latestVersion = config('sambla.plugin_version');
        // Versioned URL — every release gets a cache-fresh path
        // so Cloudflare/any CDN never serves a stale ZIP. Falls back
        // to the generic stable URL if the versioned ZIP is missing.
        $versionedFile = public_path("/downloads/sambla-woocommerce-{$latestVersion}.zip");
        $downloadUrl = is_file($versionedFile)
            ? url("/downloads/sambla-woocommerce-{$latestVersion}.zip")
            : url('/downloads/sambla-woocommerce.zip');

        $data = [
            'new_version' => $latestVersion,
            'url' => 'https://sambla.ro',
            'package' => $downloadUrl,
            'tested' => '6.7',
            'requires_php' => '7.4',
            'icons' => [],
            'banners' => [],
        ];

        // Full info requested (for plugin details popup)
        if ($info === 'full') {
            $data['name'] = 'Sambla AI Chat for WooCommerce';
            $data['description'] = 'Chatbot AI inteligent pentru WooCommerce. Sincronizează produsele și oferă recomandări clienților în timp real.';
            $data['changelog'] = $this->getChangelog($latestVersion);
        }

        return response()->json($data);
    }

    private function getChangelog(string $version = ''): string
    {
        return '<h4>2.2.0 (Aprilie 2026)</h4><ul>'
            . '<li><strong>Cart intelligence — livrare gratuită:</strong> widget-ul vede acum pragul de livrare gratuită și îi spune clientului exact cât mai lipsește („mai ai 47,50 lei până la livrare gratuită")</li>'
            . '<li>Detectare automată a pragului de livrare gratuită din <code>WC_Shipping_Zones</code> (zone de livrare cu metoda free_shipping + min_amount)</li>'
            . '<li>Opțional: setează explicit pragul prin <code>sambla_free_shipping_threshold</code> în opțiunile WP dacă vrei să suprascrii auto-detecția</li>'
            . '<li>Bot-ul prioritizează recomandări care completează comanda până la prag</li>'
            . '</ul>'
            . '<h4>2.1.0 (Aprilie 2026)</h4><ul>'
            . '<li><strong>Context injection automat:</strong> widget-ul primește automat informații despre pagina curentă (produs / coș / categorie / checkout / home)</li>'
            . '<li>Pe pagina de produs: product_id, nume, preț, monedă, categorii, disponibilitate, permalink</li>'
            . '<li>Pe pagina de coș: numărul de produse, total, listă articole (până la 8)</li>'
            . '<li>Bot-ul răspunde contextual — știe despre ce produs vorbești fără să întrebe</li>'
            . '<li>Chips-urile se adaptează per pagină: întrebări despre produs pe pagina de produs, upsell pe pagina de coș, etc.</li>'
            . '</ul>'
            . '<h4>2.0.5</h4><ul>'
            . '<li>Fix icon meniu WordPress, îmbunătățiri layout</li>'
            . '</ul>'
            . '<h4>2.0.3 (Aprilie 2026)</h4><ul>'
            . '<li>Logo Sambla real în header și meniu WordPress</li>'
            . '<li>Linkuri corecte către Dashboard, Conversații, Lead-uri, Knowledge, Analiză</li>'
            . '<li>Date reale din platformă: mesaje, produse, documente, lead-uri, conversații</li>'
            . '</ul>'
            . '<h4>2.0.2</h4><ul>'
            . '<li>Carduri produse full-width cu imagine, descriere, preț și badge stoc</li>'
            . '<li>Fix: chatbot nu mai spune "nu am găsit" când produsele sunt afișate ca carduri</li>'
            . '</ul>'
            . '<h4>2.0.1</h4><ul>'
            . '<li>Redesign complet admin panel (dark hero, metrici live, link-uri rapide)</li>'
            . '<li>Afișare plan curent și consum mesaje/lună cu progress bar</li>'
            . '<li>Ultimele 5 conversații vizibile direct din WordPress</li>'
            . '<li>Mapare pagini standard (Contact, Termeni, Livrare) pentru baza de cunoștințe AI</li>'
            . '<li>Greeting se configurează din Dashboard (link direct)</li>'
            . '</ul>'
            . '<h4>2.0.0</h4><ul>'
            . '<li>Sincronizare paginată pentru magazine mari (5000+ produse)</li>'
            . '<li>Integrare completă cu platforma Sambla AI</li>'
            . '</ul>';
    }
}
