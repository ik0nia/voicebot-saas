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
        \Laravel\Cashier\Cashier::useCustomerModel(\App\Models\Tenant::class);

        \App\Models\Plan::observe(\App\Observers\PlanObserver::class);
        // QA-M4: instant widget-config cache invalidation when a
        // channel or its owning bot toggles active/inactive.
        \App\Models\Channel::observe(\App\Observers\ChannelCacheObserver::class);
        \App\Models\Bot::observe(\App\Observers\BotChannelCacheObserver::class);

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
                    'icon'  => '🩺',
                    'slugs' => ['medical', 'stomatologic', 'optica', 'veterinar', 'psihologie', 'salon-beauty'],
                ],
                'profesional' => [
                    'label' => 'Servicii profesionale',
                    'icon'  => '⚖️',
                    'slugs' => ['avocatura', 'contabilitate', 'notariat'],
                ],
                'comert' => [
                    'label' => 'Comerț & Auto',
                    'icon'  => '🛒',
                    'slugs' => ['ecommerce', 'service-auto'],
                ],
                'horeca' => [
                    'label' => 'HoReCa & Turism',
                    'icon'  => '🍽️',
                    'slugs' => ['restaurant', 'pensiune', 'turism'],
                ],
                'altele' => [
                    'label' => 'Imobiliare · Educație · Servicii',
                    'icon'  => '🏠',
                    'slugs' => ['imobiliare', 'scoli-limbi', 'curatenie'],
                ],
            ];

            $grouped = cache()->remember('new.megamenu.niches', 300, function () use ($categories) {
                try {
                    $all = \App\Models\Niche::where('is_active', true)
                        ->get(['slug', 'name', 'vertical_label', 'color_theme'])
                        ->keyBy('slug');
                } catch (\Throwable $e) {
                    return [];
                }

                $out = [];
                foreach ($categories as $key => $cat) {
                    $items = [];
                    foreach ($cat['slugs'] as $slug) {
                        if (isset($all[$slug])) $items[] = $all[$slug];
                    }
                    if (!empty($items)) {
                        $out[$key] = ['label' => $cat['label'], 'icon' => $cat['icon'], 'items' => $items];
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
