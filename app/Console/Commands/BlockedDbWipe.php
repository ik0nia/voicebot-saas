<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Overrides `db:wipe` to BLOCK it in non-local environments.
 */
class BlockedDbWipe extends Command
{
    protected $signature = 'db:wipe
        {--database= : Ignored}
        {--drop-views : Ignored}
        {--drop-types : Ignored}
        {--force : Ignored}';

    protected $description = '⛔ BLOCKED in production. Drops all tables, views, and types.';
    protected $hidden = true;

    public function handle(): int
    {
        if (app()->environment('local')) {
            $this->warn('Running db:wipe in LOCAL environment...');
            return 0;
        }

        logger()->critical('🚨 db:wipe BLOCKED by command override', [
            'environment' => app()->environment(),
            'user' => get_current_user(),
            'hostname' => gethostname(),
        ]);

        $this->error(' ⛔ db:wipe is PERMANENTLY DISABLED in ' . app()->environment());
        return Command::FAILURE;
    }
}
