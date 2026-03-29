<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SystemGuardStatus extends Command
{
    protected $signature = 'system:guard-status';
    protected $description = 'Show database protection status, test isolation, and production safety checks.';

    public function handle(): int
    {
        $this->newLine();
        $this->info('=== DATABASE PROTECTION STATUS ===');
        $this->newLine();

        $checks = [];
        $hasData = false;

        // 1. Environment
        $env = app()->environment();
        $isProduction = app()->isProduction();
        $checks[] = [
            'Environment',
            $env,
            $isProduction ? '✅ Production mode active' : '⚠️  Not production',
        ];

        // 2. DB::prohibitDestructiveCommands
        $prohibitActive = !app()->environment('local');
        $checks[] = [
            'DB::prohibitDestructiveCommands',
            $prohibitActive ? 'ACTIVE' : 'INACTIVE',
            $prohibitActive ? '✅ Schema::drop* blocked' : '⚠️  Schema::drop* allowed',
        ];

        // 3. SafeguardDatabase event listener
        $checks[] = [
            'SafeguardDatabase listener',
            'ACTIVE',
            '✅ Blocks: ' . implode(', ', SafeguardDatabase::BLOCKED_COMMANDS),
        ];

        // 4. Command overrides
        $overrides = [
            'migrate:fresh' => BlockedMigrateFresh::class,
            'migrate:refresh' => BlockedMigrateRefresh::class,
            'migrate:reset' => BlockedMigrateReset::class,
            'db:wipe' => BlockedDbWipe::class,
            'test' => BlockedTest::class,
        ];
        foreach ($overrides as $cmd => $class) {
            $exists = class_exists($class);
            $checks[] = [
                "Override: {$cmd}",
                $exists ? 'ACTIVE' : 'MISSING',
                $exists ? '✅ Blocked in production' : '❌ Original command active!',
            ];
        }

        // 5. Test database isolation
        $phpunitXml = base_path('phpunit.xml');
        $testDbOk = false;
        if (file_exists($phpunitXml)) {
            $xml = file_get_contents($phpunitXml);
            $hasTestDb = str_contains($xml, 'voicebot_test');
            $hasCommentedSqlite = str_contains($xml, '<!-- <env name="DB_DATABASE"');
            $testDbOk = $hasTestDb && !$hasCommentedSqlite;
            $checks[] = [
                'Test DB isolation (phpunit.xml)',
                $hasTestDb ? 'voicebot_test' : 'NOT CONFIGURED',
                $testDbOk ? '✅ Tests use separate database' : '❌ Tests may hit production DB!',
            ];
        } else {
            $checks[] = ['Test DB isolation', 'NO phpunit.xml', '⚠️  Cannot verify'];
        }

        // 6. TestCase safety check
        $testCasePath = base_path('tests/TestCase.php');
        $testCaseSafe = false;
        if (file_exists($testCasePath)) {
            $content = file_get_contents($testCasePath);
            $testCaseSafe = str_contains($content, "=== 'voicebot'") || str_contains($content, "database === 'voicebot'");
            $checks[] = [
                'TestCase safety check',
                $testCaseSafe ? 'ACTIVE' : 'MISSING',
                $testCaseSafe ? '✅ Blocks tests on production DB' : '❌ No runtime DB check!',
            ];
        }

        // 7. PostgreSQL connection
        try {
            $pgVersion = DB::selectOne("SELECT version()")->version;
            $shortVersion = explode(' ', $pgVersion)[1] ?? $pgVersion;
            $currentDb = DB::selectOne("SELECT current_database()")->current_database;
            $checks[] = ['PostgreSQL', "{$shortVersion} | DB: {$currentDb}", '✅ Connected'];
        } catch (\Throwable $e) {
            $checks[] = ['PostgreSQL', 'ERROR', '❌ ' . $e->getMessage()];
        }

        // 8. Test database exists
        try {
            $testDbExists = DB::selectOne("SELECT 1 FROM pg_database WHERE datname = 'voicebot_test'");
            $checks[] = [
                'Test database (voicebot_test)',
                $testDbExists ? 'EXISTS' : 'MISSING',
                $testDbExists ? '✅ Separate test DB available' : '❌ Create with: CREATE DATABASE voicebot_test',
            ];
        } catch (\Throwable $e) {
            $checks[] = ['Test database', 'ERROR', '⚠️  Cannot check'];
        }

        // 9. Database has data
        try {
            $userCount = DB::table('users')->count();
            $tenantCount = DB::table('tenants')->count();
            $botCount = DB::table('bots')->count();
            $hasData = ($userCount + $tenantCount + $botCount) > 0;
            $checks[] = [
                'Database data',
                "{$userCount} users, {$tenantCount} tenants, {$botCount} bots",
                $hasData ? '✅ Has data' : '⚠️  DATABASE IS EMPTY',
            ];
        } catch (\Throwable $e) {
            $checks[] = ['Database data', 'ERROR', '❌ Cannot query tables'];
        }

        // 10. Backup system
        $backupDir = '/home/sambla/backups/postgres';
        if (is_dir($backupDir)) {
            $files = glob($backupDir . '/voicebot_*.sql.gz');
            $latest = !empty($files) ? basename(end($files)) : 'none';
            $checks[] = [
                'Backups',
                count($files) . ' files, latest: ' . $latest,
                count($files) > 0 ? '✅ Backups exist' : '⚠️  No backups found',
            ];
        } else {
            $checks[] = ['Backups', 'NO DIRECTORY', '❌ Backup directory missing'];
        }

        // 11. Migration history
        try {
            $allBatch1 = DB::table('migrations')->max('batch') === 1;
            $totalMigrations = DB::table('migrations')->count();
            $checks[] = [
                'Migrations',
                "{$totalMigrations} total, max batch: " . DB::table('migrations')->max('batch'),
                $allBatch1 ? '⚠️  All batch 1 (DB was recreated at some point)' : '✅ Normal migration history',
            ];
        } catch (\Throwable $e) {
            $checks[] = ['Migrations', 'ERROR', '❌ ' . $e->getMessage()];
        }

        $this->table(['Check', 'Value', 'Status'], $checks);

        // Blocked commands test
        $this->newLine();
        $this->info('=== BLOCKED COMMANDS ===');

        foreach (SafeguardDatabase::BLOCKED_COMMANDS as $cmd) {
            $isBlocked = !app()->environment('local');
            $this->line("  <fg=red>✖</> <comment>{$cmd}</comment> → " . ($isBlocked ? 'BLOCKED ✅' : 'allowed (local)'));
        }

        $this->newLine();

        // Summary
        if (!$isProduction) {
            $this->warn('⚠️  APP_ENV is not "production" — some protections may not be active.');
        }
        if (!$testDbOk) {
            $this->error('❌ CRITICAL: Test database isolation is NOT configured correctly!');
            $this->error('   Tests could TRUNCATE the production database.');
        }
        if (isset($hasData) && !$hasData) {
            $this->error('🚨 DATABASE IS EMPTY — possible data loss incident.');
        }

        $this->newLine();
        return self::SUCCESS;
    }
}
