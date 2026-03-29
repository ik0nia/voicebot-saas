<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Overrides `test` command to BLOCK it in non-local environments.
 *
 * This is the #1 cause of production data loss — RefreshDatabase
 * trait truncates all tables when tests run against production DB.
 */
class BlockedTest extends Command
{
    protected $signature = 'test
        {--without-tty : Ignored}
        {--compact : Ignored}
        {--coverage : Ignored}
        {--min= : Ignored}
        {--p|parallel : Ignored}
        {--profile : Ignored}
        {--recreate-databases : Ignored}
        {--drop-databases : Ignored}
        {--ansi : Ignored}
        {--no-ansi : Ignored}
        {--filter= : Ignored}
        {--testsuite= : Ignored}
        {--group= : Ignored}
        {--exclude-group= : Ignored}';

    protected $description = 'Run tests (BLOCKED in production to prevent data loss)';

    public function handle(): int
    {
        if (app()->environment('local', 'testing')) {
            // In local/testing, delegate to PHPUnit normally
            $args = array_merge(['vendor/bin/phpunit'], array_slice($_SERVER['argv'] ?? [], 2));
            $process = new \Symfony\Component\Process\Process($args, base_path());
            $process->setTimeout(null);
            $process->setTty(\Symfony\Component\Process\Process::isTtySupported());
            $process->run(fn($type, $buffer) => $this->output->write($buffer));
            return $process->getExitCode();
        }

        // BLOCK in any non-local environment
        Log::critical('BLOCKED: php artisan test attempted in production', [
            'environment' => app()->environment(),
            'user' => get_current_user(),
            'pid' => getmypid(),
            'hostname' => gethostname(),
            'argv' => $_SERVER['argv'] ?? [],
        ]);

        $this->newLine();
        $this->error('  BLOCKED: "php artisan test" is DISABLED in production  ');
        $this->error('  Running tests in production DESTROYS ALL DATA.         ');
        $this->newLine();
        $this->line('  <comment>Why:</comment> Tests use RefreshDatabase which TRUNCATES all tables.');
        $this->line('  <comment>Fix:</comment> Run tests only in CI/CD or local development.');
        $this->newLine();
        $this->line('  <info>Safe alternatives:</info>');
        $this->line('    php artisan system:guard-status   — Check protection status');
        $this->line('    php artisan migrate:status         — Check pending migrations');
        $this->newLine();

        return Command::FAILURE;
    }
}
