<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackupBeforeMigrate extends Command
{
    protected $signature = 'db:backup {--tag=manual : Tag for the backup file}';
    protected $description = 'Create a PostgreSQL backup (runs automatically before migrations)';

    public function handle(): int
    {
        $tag = $this->option('tag');
        $timestamp = now()->format('Y-m-d_His');
        $filename = "voicebot_{$tag}_{$timestamp}.sql.gz";
        $backupDir = '/home/sambla/backups/postgres';
        $backupPath = "{$backupDir}/{$filename}";

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $host = config('database.connections.pgsql.host');
        $port = config('database.connections.pgsql.port');
        $database = config('database.connections.pgsql.database');
        $username = config('database.connections.pgsql.username');
        $password = config('database.connections.pgsql.password');

        $this->info("Backing up database to {$filename}...");

        $command = sprintf(
            'PGPASSWORD=%s pg_dump -h %s -p %s -U %s -d %s --no-owner --no-privileges | gzip > %s 2>&1',
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($backupPath)
        );

        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            $this->error('Backup FAILED: ' . implode("\n", $output));
            return Command::FAILURE;
        }

        $size = round(filesize($backupPath) / 1024, 1);
        $this->info("Backup completed: {$filename} ({$size} KB)");

        return Command::SUCCESS;
    }
}
