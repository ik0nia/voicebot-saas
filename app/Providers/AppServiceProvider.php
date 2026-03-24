<?php

namespace App\Providers;

use App\View\Composers\TranscriptSidebarComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Anthropic client singleton
        $this->app->singleton(\Anthropic\Contracts\ClientContract::class, function () {
            $apiKey = config('services.anthropic.api_key', env('ANTHROPIC_API_KEY'));
            if (empty($apiKey)) {
                return null;
            }
            return \Anthropic::factory()
                ->withApiKey($apiKey)
                ->make();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.dashboard', TranscriptSidebarComposer::class);
    }
}
