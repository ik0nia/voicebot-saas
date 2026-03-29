<?php

/**
 * PHPUnit bootstrap — runs BEFORE any test, before Laravel boots.
 *
 * This is the earliest possible interception point for vendor/bin/phpunit.
 * It checks the REAL environment (.env) to detect if we're on a production server,
 * regardless of what phpunit.xml sets APP_ENV to.
 */

// Read the real .env file to check actual server environment
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);

    // Extract the REAL APP_ENV from .env (not phpunit.xml override)
    if (preg_match('/^APP_ENV\s*=\s*(.+)$/m', $envContent, $matches)) {
        $realEnv = trim($matches[1], " \t\n\r\"'");

        if (in_array($realEnv, ['production', 'prod'], true)) {
            // Extract DB name to give clear error
            $dbName = 'unknown';
            if (preg_match('/^DB_DATABASE\s*=\s*(.+)$/m', $envContent, $dbMatch)) {
                $dbName = trim($dbMatch[1], " \t\n\r\"'");
            }

            $hostname = gethostname();
            $msg = "\n\033[41;37;1m FATAL: PHPUnit running on PRODUCTION server! \033[0m\n"
                . "\033[33m Server: {$hostname}\033[0m\n"
                . "\033[33m .env APP_ENV: {$realEnv}\033[0m\n"
                . "\033[33m .env DB_DATABASE: {$dbName}\033[0m\n"
                . "\033[33m phpunit.xml overrides APP_ENV to 'testing' but the SERVER is production.\033[0m\n"
                . "\033[33m RefreshDatabase would TRUNCATE all tables in production.\033[0m\n\n"
                . "\033[31m Tests are PERMANENTLY BLOCKED on production servers.\033[0m\n"
                . "\033[31m Run tests only in CI/CD or local development.\033[0m\n\n";

            fwrite(STDERR, $msg);

            // Log to file directly (Laravel isn't booted yet)
            $logFile = dirname(__DIR__) . '/storage/logs/laravel.log';
            $logEntry = '[' . date('Y-m-d H:i:s') . '] production.CRITICAL: '
                . 'PHPUnit execution BLOCKED on production server '
                . json_encode([
                    'hostname' => $hostname,
                    'real_env' => $realEnv,
                    'db' => $dbName,
                    'user' => get_current_user(),
                    'argv' => $argv ?? [],
                ]) . "\n";
            @file_put_contents($logFile, $logEntry, FILE_APPEND);

            exit(1);
        }
    }
}

// If we passed the production check, load the normal Laravel autoloader
require dirname(__DIR__) . '/vendor/autoload.php';
