<?php

namespace App\Http\Controllers\NewSite;

use App\Http\Controllers\Controller;
use App\Models\Niche;
use App\Models\Plan;
use Illuminate\View\View;

/**
 * Parallel "warm redesign" site served under /new/*.
 *
 * Strictly additive: does not touch any existing public controller or
 * view. Pulls the same data as the live marketing site (Niche, Plan)
 * but renders it through the new.* view namespace. When the redesign
 * is promoted, this controller can be deleted and the routes pointed
 * at the canonical site.
 */
class NewSiteController extends Controller
{
    public function home(): View
    {
        return view('new.home', [
            'niches'       => $this->activeNiches(),
            'footerNiches' => $this->footerNiches(),
            'nicheTheme'   => '',
        ]);
    }

    public function niche(Niche $niche): View
    {
        abort_unless($niche->is_active, 404);

        return view('new.niche', [
            'niche'        => $niche,
            'relatedNiches'=> $this->activeNiches()->reject(fn ($n) => $n->id === $niche->id)->take(6),
            'footerNiches' => $this->footerNiches(),
            'nicheTheme'   => $this->resolveThemeKey($niche->color_theme),
        ]);
    }

    public function functionalitati(): View
    {
        return view('new.functionalitati', [
            'footerNiches' => $this->footerNiches(),
            'niches'       => $this->activeNiches(),
            'nicheTheme'   => '',
        ]);
    }

    public function preturi(): View
    {
        $webchatPlans = collect();
        $voicePlans   = collect();
        try {
            // ->public() filtrează cheile: tenant_id NULL + is_public=true,
            // ca planurile custom per-tenant (definite din admin pentru un
            // client anume) să nu se scurgă pe pagina publică.
            $webchatPlans = Plan::active()->public()->webchat()->orderBy('sort_order')->get();
            $voicePlans   = Plan::active()->public()->voice()->orderBy('sort_order')->get();
        } catch (\Throwable $e) {
            // Fall through with empty collections so the page still
            // renders if the plans table is unavailable during a deploy.
        }

        return view('new.preturi', [
            'webchatPlans' => $webchatPlans,
            'voicePlans'   => $voicePlans,
            'footerNiches' => $this->footerNiches(),
            'nicheTheme'   => '',
        ]);
    }

    public function despre(): View
    {
        return view('new.despre', [
            'footerNiches' => $this->footerNiches(),
            'nicheTheme'   => '',
        ]);
    }

    public function contact(): View
    {
        return view('new.contact', [
            'footerNiches' => $this->footerNiches(),
            'nicheTheme'   => '',
        ]);
    }

    public function deCeSambla(): View
    {
        return view('new.de-ce-sambla', [
            'footerNiches' => $this->footerNiches(),
            'nicheTheme'   => '',
        ]);
    }

    public function blog(): View
    {
        return view('new.blog', [
            'footerNiches' => $this->footerNiches(),
            'nicheTheme'   => '',
        ]);
    }

    public function industrii(): View
    {
        return view('new.industrii', [
            'niches'       => $this->activeNiches(),
            'footerNiches' => $this->footerNiches(),
            'nicheTheme'   => '',
        ]);
    }

    public function legal(string $slug): View
    {
        $map = [
            'termeni'            => 'new.legal.termeni',
            'confidentialitate'  => 'new.legal.confidentialitate',
            'cookie-uri'         => 'new.legal.cookie-uri',
        ];
        abort_unless(isset($map[$slug]), 404);

        return view($map[$slug], [
            'footerNiches' => $this->footerNiches(),
            'nicheTheme'   => '',
        ]);
    }

    private function activeNiches()
    {
        try {
            return cache()->remember('new.niches.active', 60, function () {
                return Niche::where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get();
            });
        } catch (\Throwable $e) {
            return collect();
        }
    }

    private function footerNiches()
    {
        try {
            return cache()->remember('new.niches.footer', 60, function () {
                return Niche::where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->take(6)
                    ->get(['slug', 'name']);
            });
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /**
     * Use the niche's own color_theme as the data-niche value. The
     * /new layout has CSS-variable blocks for every Tailwind palette
     * stored in niches.color_theme (red, emerald, blue, amber, rose,
     * purple, indigo, teal, cyan, orange) so the swap is 1:1.
     */
    private function resolveThemeKey(?string $dbTheme): string
    {
        return $dbTheme ?: '';
    }
}
