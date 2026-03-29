<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Overrides `migrate:reset` to BLOCK it in non-local environments.
 */
class BlockedMigrateReset extends Command
{
    protected $signature = 'migrate:reset
        {--database= : Ignored}
        {--force : Ignored}
        {--path=* : Ignored}
        {--realpath : Ignored}
        {--pretend : Ignored}';

    protected $description = '⛔ BLOCKED in production. Rolls back all migrations.';
    protected $hidden = true;

    public function handle(): int
    {
        if (app()->environment('local')) {
            $this->warn('Running migrate:reset in LOCAL environment...');
            return 0;
        }

        logger()->critical('🚨 migrate:reset BLOCKED by command override', [
            'environment' => app()->environment(),
            'user' => get_current_user(),
            'hostname' => gethostname(),
        ]);

        $this->error(' ⛔ migrate:reset is PERMANENTLY DISABLED in ' . app()->environment());
        return Command::FAILURE;
    }
}
