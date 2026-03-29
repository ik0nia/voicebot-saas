<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * PRODUCTION DATABASE PROTECTION — MULTI-LAYER
 *
 * Layer 1: Event listener on CommandStarting (catches artisan calls)
 * Layer 2: Custom command overrides (replace the actual commands)
 * Layer 3: DB::prohibitDestructiveCommands (catches Schema::drop*)
 * Layer 4: PostgreSQL-level trigger (optional, see docs below)
 *
 * This class handles Layer 1. Layers 2-3 are in AppServiceProvider.
 * Layer 4 is a SQL trigger you can add manually.
 */
class SafeguardDatabase
{
    /**
     * ALL commands that can destroy data. Comprehensive list.
     */
    public const BLOCKED_COMMANDS = [
        'migrate:fresh',      // Drops ALL tables, re-runs all migrations
        'migrate:refresh',    // Rolls back all migrations, re-runs them
        'migrate:reset',      // Rolls back all migrations (drops tables)
        'db:wipe',            // Drops all tables, views, and types
        'schema:dump',        // Can be combined with --prune to drop migrations
        'test',               // RefreshDatabase TRUNCATES all tables — #1 cause of data loss
    ];

    /**
     * Register the event-based protection layer.
     * Called from AppServiceProvider::boot()
     */
    public static function register(): void
    {
        // Layer 1: Intercept at the event level BEFORE command executes
        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            $command = $event->command;

            // Normalize: artisan sometimes passes null command
            if (empty($command)) {
                return;
            }

            if (!in_array($command, self::BLOCKED_COMMANDS, true)) {
                return;
            }

            // Block in ANY non-local environment (not just production)
            // This catches staging, testing on server, etc.
            if (!app()->environment('local')) {
                // Log with maximum context
                Log::critical('🚨 BLOCKED destructive database command', [
                    'command' => $command,
                    'environment' => app()->environment(),
                    'user' => get_current_user(),
                    'pid' => getmypid(),
                    'argv' => $_SERVER['argv'] ?? [],
                    'working_dir' => getcwd(),
                    'hostname' => gethostname(),
                    'timestamp' => now()->toIso8601String(),
                ]);

                // Write to stderr directly (works in all contexts)
                $msg = "\n\033[41;37;1m FATAL: \"{$command}\" BLOCKED — would destroy production database! \033[0m\n";
                $msg .= "\033[33m Environment: " . app()->environment() . "\033[0m\n";
                $msg .= "\033[33m Use \"php artisan migrate\" for safe migrations.\033[0m\n\n";

                if (defined('STDERR')) {
                    fwrite(STDERR, $msg);
                }

                if ($event->output) {
                    $event->output->writeln('');
                    $event->output->writeln('<error> FATAL: "' . $command . '" BLOCKED — would destroy production database! </error>');
                    $event->output->writeln('<comment> This command is permanently disabled outside local environment. </comment>');
                    $event->output->writeln('');
                }

                // Hard exit — do NOT use return, the command must NOT proceed
                exit(1);
            }
        });
    }
}
