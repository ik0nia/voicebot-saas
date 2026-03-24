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

        // Latest version available - bump this when releasing a new plugin version
        $latestVersion = config('sambla.plugin_version', '1.0.0');
        $downloadUrl = url('/downloads/sambla-woocommerce-' . $latestVersion . '.zip');

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

    private function getChangelog(string $version): string
    {
        return "<h4>{$version}</h4><ul>"
            . '<li>Sincronizare paginată pentru magazine mari (5000+ produse)</li>'
            . '<li>Îmbunătățiri la stabilitatea conexiunii</li>'
            . '</ul>';
    }
}
