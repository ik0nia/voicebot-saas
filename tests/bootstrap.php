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
            // Extract production DB name from .env
            $prodDb = 'unknown';
            if (preg_match('/^DB_DATABASE\s*=\s*(.+)$/m', $envContent, $dbMatch)) {
                $prodDb = trim($dbMatch[1], " \t\n\r\"'");
            }

            // Resolve the DB the test run will actually use. phpunit.xml sets
            // an env for this (DB_DATABASE=voicebot_test by default). Only
            // block when the runtime target equals the production DB, so
            // running against a clearly-separate test DB is permitted.
            $runtimeDb = getenv('DB_DATABASE');
            if ($runtimeDb === false || $runtimeDb === '') {
                $runtimeDb = $_ENV['DB_DATABASE'] ?? $_SERVER['DB_DATABASE'] ?? $prodDb;
            }

            if ($runtimeDb !== $prodDb && $runtimeDb !== '' && $runtimeDb !== 'unknown') {
                // Different DB at runtime — safe to proceed.
                goto passed_guard;
            }

            $dbName = $prodDb;
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

passed_guard:
// If we passed the production check, load the normal Laravel autoloader
require dirname(__DIR__) . '/vendor/autoload.php';
