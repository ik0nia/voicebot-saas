<?php

namespace App\Providers;

use App\Console\Commands\SafeguardDatabase;
use App\View\Composers\TranscriptSidebarComposer;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.dashboard', TranscriptSidebarComposer::class);

        // ============================================================
        // DATABASE PROTECTION — 3 LAYERS (BULLETPROOF)
        // ============================================================

        // Layer 1: Laravel built-in — blocks Schema::dropAllTables(), Schema::drop(), etc.
        // Works at the DB driver level, cannot be bypassed by artisan commands.
        DB::prohibitDestructiveCommands(!app()->environment('local'));

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
