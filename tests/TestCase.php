<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // ================================================================
        // PRODUCTION DATABASE PROTECTION — 3 CHECKS
        // If ANY of these fail, tests abort immediately.
        // This prevents RefreshDatabase from TRUNCATING production data.
        // ================================================================

        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");
        $host = config("database.connections.{$connection}.host", 'localhost');

        // Check 1: Database name must NOT be production
        if ($database === 'voicebot') {
            $this->fail(
                "FATAL: Tests connected to PRODUCTION database 'voicebot'! "
                . "phpunit.xml must set DB_DATABASE=voicebot_test."
            );
        }

        // Check 2: Must be on test database
        if ($connection === 'pgsql' && !str_ends_with($database, '_test')) {
            $this->fail(
                "FATAL: PostgreSQL database '{$database}' does not end with '_test'. "
                . "Tests must run on a dedicated test database."
            );
        }

        // Check 3: APP_ENV must be 'testing' during test runs
        if (app()->environment('production', 'prod')) {
            $this->fail(
                "FATAL: APP_ENV is '" . app()->environment() . "'. "
                . "Tests must not run in production environment."
            );
        }
    }
}
