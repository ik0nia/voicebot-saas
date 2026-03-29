<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Overrides `migrate:refresh` to BLOCK it in non-local environments.
 */
class BlockedMigrateRefresh extends Command
{
    protected $signature = 'migrate:refresh
        {--database= : Ignored}
        {--force : Ignored}
        {--path=* : Ignored}
        {--realpath : Ignored}
        {--seed : Ignored}
        {--seeder= : Ignored}
        {--step= : Ignored}';

    protected $description = '⛔ BLOCKED in production. Rolls back and re-runs all migrations.';
    protected $hidden = true;

    public function handle(): int
    {
        if (app()->environment('local')) {
            $this->warn('Running migrate:refresh in LOCAL environment...');
            return $this->call('migrate', ['--refresh' => true]);
        }

        logger()->critical('🚨 migrate:refresh BLOCKED by command override', [
            'environment' => app()->environment(),
            'user' => get_current_user(),
            'hostname' => gethostname(),
        ]);

        $this->error(' ⛔ migrate:refresh is PERMANENTLY DISABLED in ' . app()->environment());
        $this->line(' Use <info>php artisan migrate</info> for safe migrations.');

        return Command::FAILURE;
    }
}
