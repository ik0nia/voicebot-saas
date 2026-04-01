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

        $latestVersion = '2.0.4';
        $downloadUrl = url('/downloads/sambla-woocommerce.zip');

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

    private function getChangelog(): string
    {
        return '<h4>2.0.3 (Aprilie 2026)</h4><ul>'
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
