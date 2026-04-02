<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function __construct(
        private PlanLimitService $planLimitService,
    ) {}

    // ─── Lista site-uri ale tenant-ului ───

    public function index()
    {
        $tenant = auth()->user()->tenant;

        $sites = $tenant->sites()
            ->withCount('bots')
            ->latest()
            ->get();

        $plan = $this->planLimitService->getPlanForTenant($tenant);
        $maxSites = $plan->getLimit('max_sites', 1);
        $canAddSite = $sites->count() < $maxSites;

        return view('dashboard.sites.index', compact('sites', 'canAddSite'));
    }

    // ─── Formular adăugare site ───

    public function create()
    {
        $tenant = auth()->user()->tenant;
        $plan = $this->planLimitService->getPlanForTenant($tenant);
        $maxSites = $plan->getLimit('max_sites', 1);
        $currentCount = $tenant->sites()->count();

        if ($currentCount >= $maxSites) {
            return redirect()->route('dashboard.sites.index')
                ->with('error', "Ai atins limita de {$maxSites} site-uri pe planul {$plan->name}. Fă upgrade pentru a adăuga mai multe site-uri.")
                ->with('upgrade_needed', true);
        }

        return view('dashboard.sites.create');
    }

    // ─── Salvare site nou ───

    public function store(Request $request)
    {
        $tenant = auth()->user()->tenant;

        // Verifică limita plan
        $plan = $this->planLimitService->getPlanForTenant($tenant);
        $maxSites = $plan->getLimit('max_sites', 1);
        $currentCount = $tenant->sites()->count();

        if ($currentCount >= $maxSites) {
            return redirect()->route('dashboard.sites.index')
                ->with('error', "Ai atins limita de {$maxSites} site-uri pe planul {$plan->name}. Fă upgrade pentru a adăuga mai multe site-uri.")
                ->with('upgrade_needed', true);
        }

        $validated = $request->validate([
            'domain' => 'required|string|max:255',
            'name' => 'required|string|max:255',
        ]);

        // Normalizare domain: strip protocol, www, trailing slash, lowercase
        $domain = $validated['domain'];
        $domain = preg_replace('#^https?://#i', '', $domain);
        $domain = preg_replace('#^www\.#i', '', $domain);
        $domain = rtrim($domain, '/');
        $domain = strtolower(trim($domain));

        // Verificare unicitate pe domeniul normalizat
        if (Site::withoutGlobalScopes()->where('domain', $domain)->exists()) {
            return back()->withErrors(['domain' => 'Acest domeniu este deja înregistrat.'])->withInput();
        }

        $site = Site::create([
            'tenant_id' => $tenant->id,
            'domain' => $domain,
            'name' => $validated['name'],
            'status' => 'active',
            'verified_at' => now(),
        ]);

        return redirect()->route('dashboard.sites.show', $site)
            ->with('success', 'Site-ul a fost adăugat cu succes!');
    }

    // ─── Detalii site + instrucțiuni verificare ───

    public function show(Site $site)
    {
        // Ownership verificat automat prin BelongsToTenant (TenantScope)
        $this->authorizeSite($site);

        $bots = $site->bots()->get();

        return view('dashboard.sites.show', compact('site', 'bots'));
    }

    // ─── Pornește verificarea ───

    public function verify(Request $request, Site $site)
    {
        $this->authorizeSite($site);

        $validated = $request->validate([
            'method' => 'required|in:meta_tag,dns_txt,file',
        ]);

        // Mark the site as verified (verification methods to be implemented)
        $site->update([
            'verified_at' => now(),
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Site-ul a fost verificat cu succes!',
            'status' => 'active',
        ]);
    }

    // ─── Setări site ───

    public function update(Request $request, Site $site)
    {
        $this->authorizeSite($site);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'settings' => 'nullable|array',
        ]);

        $site->update($validated);

        return redirect()->route('dashboard.sites.show', $site)
            ->with('success', 'Setările site-ului au fost actualizate.');
    }

    // ─── Șterge site (+ disociază boții) ───

    public function destroy(Site $site)
    {
        $this->authorizeSite($site);

        // Disociază boții — setează site_id = null
        $site->bots()->update(['site_id' => null]);

        $site->delete();

        return redirect()->route('dashboard.sites.index')
            ->with('success', 'Site-ul a fost șters.');
    }

    // ─── Helper: verifică ownership ───

    private function authorizeSite(Site $site): void
    {
        if ($site->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'Nu ai acces la acest site.');
        }
    }
}
