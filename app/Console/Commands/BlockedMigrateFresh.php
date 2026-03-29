<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Overrides `migrate:fresh` to BLOCK it in non-local environments.
 * This is Layer 2 protection — even if the event listener fails,
 * this command replaces the original and refuses to execute.
 */
class BlockedMigrateFresh extends Command
{
    protected $signature = 'migrate:fresh
        {--database= : Ignored}
        {--drop-views : Ignored}
        {--drop-types : Ignored}
        {--force : Ignored}
        {--path=* : Ignored}
        {--realpath : Ignored}
        {--schema-path= : Ignored}
        {--seed : Ignored}
        {--seeder= : Ignored}
        {--step : Ignored}';

    protected $description = '⛔ BLOCKED in production. Drops all tables and re-runs migrations.';
    protected $hidden = true;

    public function handle(): int
    {
        if (app()->environment('local')) {
            // In local, delegate to the real command
            $this->warn('Running migrate:fresh in LOCAL environment...');
            return $this->call('migrate', ['--fresh' => true]);
        }

        logger()->critical('🚨 migrate:fresh BLOCKED by command override', [
            'environment' => app()->environment(),
            'user' => get_current_user(),
            'hostname' => gethostname(),
        ]);

        $this->error('');
        $this->error(' ⛔ migrate:fresh is PERMANENTLY DISABLED in ' . app()->environment() . ' ');
        $this->error(' This command would DELETE ALL DATA. ');
        $this->error('');
        $this->line(' Use <info>php artisan migrate</info> for safe migrations.');
        $this->line(' Use <info>php artisan migrate:status</info> to check pending migrations.');
        $this->line('');

        return Command::FAILURE;
    }
}
