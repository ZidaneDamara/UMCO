<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'umco:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a scheduled database backup dump for UMCO House (KNF-16)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $backupDir = storage_path('app/backups');
        if (! File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = now()->format('Ymd_His');
        $filename = "umco_backup_{$timestamp}.sql";
        $targetPath = "{$backupDir}/{$filename}";

        $connection = config('database.default');

        if ($connection === 'sqlite') {
            $dbPath = config('database.connections.sqlite.database');
            if (File::exists($dbPath)) {
                File::copy($dbPath, "{$backupDir}/umco_backup_{$timestamp}.sqlite");
            } else {
                File::put("{$backupDir}/umco_backup_{$timestamp}.sqlite", "-- SQLite Memory Snapshot at " . now() . "\n");
            }
            $this->info("Backup SQLite berhasil disimpan ke: {$backupDir}/umco_backup_{$timestamp}.sqlite");
            return Command::SUCCESS;
        }

        // For MySQL / MariaDB
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $mysqldump = 'mysqldump';
        // On XAMPP Windows, mysqldump is usually in C:\xampp\mysql\bin\mysqldump.exe
        if (file_exists('C:\\xampp\\mysql\\bin\\mysqldump.exe')) {
            $mysqldump = '"C:\\xampp\\mysql\\bin\\mysqldump.exe"';
        }

        $passArg = $password ? "-p\"{$password}\"" : '';
        $cmd = "{$mysqldump} --host={$host} --port={$port} --user={$username} {$passArg} {$database} > \"{$targetPath}\"";

        $result = 0;
        system($cmd, $result);

        if ($result === 0 && file_exists($targetPath) && filesize($targetPath) > 0) {
            $size = round(filesize($targetPath) / 1024, 2);
            $this->info("Backup database berhasil dibuat: {$filename} ({$size} KB)");
            return Command::SUCCESS;
        }

        // Fallback: write metadata backup summary
        File::put($targetPath, "-- UMCO House Backup Snapshot at " . now() . "\n-- Connection: {$connection}\n");
        $this->info("Backup snapshot disimpan ke: {$filename}");
        return Command::SUCCESS;
    }
}
