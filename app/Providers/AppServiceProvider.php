<?php

namespace App\Providers;

use App\Console\Commands\SafeguardDatabase;
use App\View\Composers\TranscriptSidebarComposer;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // AnalyticsConfig — per-request cache of marketing IDs read
        // from PlatformSetting. Singleton so the head partial, body
        // partial, and widget all hit the same resolved values.
        $this->app->singleton(\App\Services\Analytics\AnalyticsConfig::class);

        // Anthropic client singleton — uses anthropic-ai/sdk directly
        $this->app->singleton(\Anthropic\Client::class, function () {
            $apiKey = config('services.anthropic.api_key', env('ANTHROPIC_API_KEY'));
            if (empty($apiKey)) {
                return null;
            }
            return new \Anthropic\Client($apiKey);
        });

        // TokenizerService — singleton to avoid re-loading encoding on every call
        $this->app->singleton(\App\Services\TokenizerService::class);

        // IntentDetectionService — singleton ca memoization per-request să
        // funcționeze cross-services (orchestrator + chat assembler).
        // detect() e apelat de 3 ori în același turn pe același mesaj.
        $this->app->singleton(\App\Services\IntentDetectionService::class);

        // Bind the ChatResponder contract to the concrete
        // implementation so tests can swap in a double without the
        // concrete class needing to drop `final`.
        $this->app->bind(
            \App\Services\Chat\ChatResponderInterface::class,
            \App\Services\Chat\ChatResponder::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Forțează Carbon să returneze RO peste tot — diffForHumans pe
        // orice timestamp dă „acum 5 minute" în loc de „5 minutes ago".
        // Singura sursă de adevăr; fără asta, fiecare view trebuia să
        // facă ->locale('ro') explicit, ceea ce sărea peste fișiere
        // moștenite de la versiuni anterioare.
        \Carbon\Carbon::setLocale('ro');
        setlocale(LC_TIME, 'ro_RO.UTF-8', 'ro_RO', 'ro');

        // MessageObserver: broadcast pe Reverb la fiecare mesaj nou (inbound +
        // outbound) pentru ca operator console să nu mai trebuiască polling 5s.
        \App\Models\Message::observe(\App\Observers\MessageObserver::class);
        // ConversationObserver: broadcast la schimbare status (closed, etc.).
        \App\Models\Conversation::observe(\App\Observers\ConversationObserver::class);

        // AppointmentObserver: sync transparent la Google Calendar (per-staff).
        // Best-effort, nu blochează salvarea locală dacă Google nu răspunde.
        \App\Models\Appointment::observe(\App\Observers\AppointmentObserver::class);

        // LeadObserver: normalizează telefon la E.164 + flag invalid prin
        // Twilio Lookup v2. Acoperă toate căile de creare lead.
        \App\Models\Lead::observe(\App\Observers\LeadObserver::class);

        // @samblaWidgetUrl — URL canonic pentru widget cu cache-bust pe
        // ultimele 6 cifre din filemtime. Fără asta, browserele care au
        // descărcat widget-ul cu Cache-Control max-age=14400 (din nginx
        // vechi) păstrează versiunea veche până la expirare → hotfix-uri
        // nu ajung pe site-urile clientilor. Folosit în toate blade-urile
        // care embed-uiesc widget-ul.
        \Illuminate\Support\Facades\Blade::directive('samblaWidgetUrl', function () {
            return "<?php echo rtrim(config('app.cdn_url') ?: config('app.url'), '/') . '/widget/sambla-chat.min.js?v=' . substr((string) (@filemtime(public_path('widget/sambla-chat.min.js')) ?: time()), -6); ?>";
        });

        \Laravel\Cashier\Cashier::useCustomerModel(\App\Models\Tenant::class);

        \App\Models\Plan::observe(\App\Observers\PlanObserver::class);
        // QA-M4: instant widget-config cache invalidation when a
        // channel or its owning bot toggles active/inactive.
        \App\Models\Channel::observe(\App\Observers\ChannelCacheObserver::class);
        \App\Models\Bot::observe(\App\Observers\BotChannelCacheObserver::class);

        // Outbound webhooks pentru tenants — un singur observer fan-out la
        // 5 modele. Toate dispatch-uri merg pe queue (sync-safe).
        $webhookObserver = \App\Observers\WebhookEventObserver::class;
        \App\Models\Lead::observe($webhookObserver);
        \App\Models\CallbackRequest::observe($webhookObserver);
        \App\Models\Appointment::observe($webhookObserver);
        \App\Models\Call::observe($webhookObserver);
        \App\Models\Conversation::observe($webhookObserver);

        // Super-admin bypasses every policy. Matches the dashboard behaviour
        // where super_admin already gets withoutGlobalScopes on tenant-scoped
        // queries (see BotController::resolveBot) — without this bypass, the
        // per-model policies added in iter 12 would 403 a super_admin
        // inspecting another tenant's bot.
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        View::composer('layouts.dashboard', TranscriptSidebarComposer::class);

        View::composer('home', function ($view) {
            $view->with('niches', \App\Models\Niche::where('is_active', true)->orderBy('sort_order')->get());
        });

        /*
         * Mega menu for /new/* layout — groups active niches by vertical
         * category (slug → category). Cached for 5 min to avoid hitting
         * the DB on every page load. Each category is rendered as a
         * column in the „Industrii" dropdown.
         */
        View::composer('layouts.new', function ($view) {
            $categories = [
                'sanatate' => [
                    'label' => 'Sănătate & Beauty',
                    'slugs' => ['cabinete-medicale', 'cabinete-stomatologice', 'optica-medicala', 'clinici-veterinare', 'psihologie-psihoterapie', 'salon-beauty'],
                ],
                'profesional' => [
                    'label' => 'Servicii profesionale',
                    'slugs' => ['birouri-avocatura', 'firme-contabilitate', 'birouri-notariale'],
                ],
                'comert' => [
                    'label' => 'Comerț & Auto',
                    'slugs' => ['magazine-online', 'service-auto'],
                ],
                'horeca' => [
                    'label' => 'HoReCa & Turism',
                    'slugs' => ['restaurante-delivery', 'pensiuni-hoteluri-mici', 'agentii-turism'],
                ],
                'altele' => [
                    'label' => 'Imobiliare · Educație · Servicii',
                    'slugs' => ['agentii-imobiliare', 'scoli-limbi-straine', 'firme-curatenie'],
                ],
            ];

            $grouped = cache()->remember('new.megamenu.niches.v3', 300, function () use ($categories) {
                try {
                    $all = \App\Models\Niche::where('is_active', true)
                        ->get(['slug', 'name', 'vertical_label', 'color_theme'])
                        ->keyBy('slug');
                } catch (\Throwable $e) {
                    return [];
                }

                // Attach first icon SVG path from niche-icons config to each row.
                // Renderer uses it as the small icon next to the niche name.
                $icons = config('niche-icons', []);
                $decorate = function ($niche) use ($icons) {
                    $slug = $niche->slug;
                    $iconSvg = isset($icons[$slug][0]) ? $icons[$slug][0] : null;
                    return [
                        'slug'   => $slug,
                        'name'   => $niche->vertical_label ?: $niche->name,
                        'theme'  => $niche->color_theme ?: 'red',
                        'icon'   => $iconSvg,
                    ];
                };

                $out = [];
                $placed = [];
                foreach ($categories as $key => $cat) {
                    $items = [];
                    foreach ($cat['slugs'] as $slug) {
                        if (isset($all[$slug])) {
                            $items[] = $decorate($all[$slug]);
                            $placed[$slug] = true;
                        }
                    }
                    if (!empty($items)) {
                        $out[$key] = ['label' => $cat['label'], 'items' => $items];
                    }
                }

                // Orphan bucket — orice nișă activă care nu e în categoriile de mai sus
                // ajunge aici, ca mega menu-ul să reflecte baza de date, nu o listă hardcoded.
                $orphans = $all->reject(fn ($n) => isset($placed[$n->slug]))->values();
                if ($orphans->isNotEmpty()) {
                    $decorated = $orphans->map($decorate)->all();
                    if (isset($out['altele'])) {
                        foreach ($decorated as $it) $out['altele']['items'][] = $it;
                    } else {
                        $out['altele'] = ['label' => 'Altele', 'items' => $decorated];
                    }
                }
                return $out;
            });

            $view->with('megaMenuNiches', $grouped);
        });

        // ============================================================
        // DATABASE PROTECTION — 3 LAYERS (BULLETPROOF)
        // ============================================================

        // Layer 1: Laravel built-in — blocks Schema::dropAllTables(), Schema::drop(), etc.
        // Works at the DB driver level, cannot be bypassed by artisan commands.
        DB::prohibitDestructiveCommands(!app()->environment('local', 'testing'));

        // Layer 2: Event listener — intercepts artisan commands BEFORE they execute.
        // Catches: migrate:fresh, migrate:refresh, migrate:reset, db:wipe
        SafeguardDatabase::register();

        // Layer 3: Command overrides (BlockedMigrateFresh, etc.)
        // Even if Layers 1-2 fail, the commands themselves are replaced
        // with versions that refuse to run. Registered automatically
        // via Laravel command discovery in app/Console/Commands/.

        // ============================================================

        // Layer 4: Runtime database health check (detect empty DB after incidents)
        if (app()->isProduction() && !app()->runningInConsole()) {
            try {
                $userCount = \DB::table('users')->count();
                if ($userCount === 0) {
                    Log::critical('🚨 PRODUCTION DATABASE IS EMPTY — possible data loss incident', [
                        'hostname' => gethostname(),
                        'url' => request()->fullUrl(),
                    ]);
                }
            } catch (\Throwable $e) {
                // DB not ready yet — skip
            }
        }

        // Auto-backup before migrations (safety net)
        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            if ($event->command === 'migrate' && !app()->environment('local')) {
                try {
                    Artisan::call('db:backup', ['--tag' => 'pre-migrate']);
                } catch (\Throwable $e) {
                    logger()->warning('Pre-migrate backup failed', ['error' => $e->getMessage()]);
                }
            }
        });
    }
}
