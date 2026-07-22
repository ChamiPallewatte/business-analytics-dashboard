<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use PDO;
use Exception;

class BackupCommand extends Command
{
    protected $signature = 'db:backup';
    protected $description = 'Perform a daily backup of the database to storage/app/backups';

    public function handle()
    {
        $this->info('Starting database backup...');

        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', '3306');

        $filename = "backup-{$database}-" . now()->format('Y-m-d_H-i-s') . ".sql";
        $backupDir = storage_path('app/backups');

        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filePath = "{$backupDir}/{$filename}";

        // Method 1: Try mysqldump
        try {
            $command = sprintf(
                'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s 2>&1',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($filePath)
            );

            $output = [];
            $returnVar = -1;
            exec($command, $output, $returnVar);

            if ($returnVar === 0 && file_exists($filePath) && filesize($filePath) > 0) {
                $this->info("Backup created successfully using mysqldump: {$filename}");
                $this->cleanupOldBackups($backupDir);
                return 0;
            }
        } catch (Exception $e) {
            $this->warn('mysqldump failed: ' . $e->getMessage());
        }

        // Method 2: Fallback to pure PHP PDO dump (extremely robust for shared hosting)
        $this->info('Falling back to PHP PDO dumper...');
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $sql = "-- Database Backup for {$database}\n";
            $sql .= "-- Generated at: " . now()->toDateTimeString() . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            // Get tables
            $tables = [];
            $stmt = $pdo->query('SHOW TABLES');
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }

            foreach ($tables as $table) {
                // Table structure
                $sql .= "-- Structure for table `{$table}`\n";
                $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
                $createRow = $createStmt->fetch();
                $sql .= $createRow['Create Table'] . ";\n\n";

                // Table data
                $sql .= "-- Dumping data for table `{$table}`\n";
                $dataStmt = $pdo->query("SELECT * FROM `{$table}`");
                while ($dataRow = $dataStmt->fetch()) {
                    $keys = array_map(fn($k) => "`{$k}`", array_keys($dataRow));
                    $values = array_map(function($v) use ($pdo) {
                        if ($v === null) return 'NULL';
                        return $pdo->quote($v);
                    }, array_values($dataRow));

                    $sql .= "INSERT INTO `{$table}` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql .= "\n\n";
            }

            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            file_put_contents($filePath, $sql);
            $this->info("Backup created successfully using PDO fallback: {$filename}");

            // Clean up backups older than 7 days
            $this->cleanupOldBackups($backupDir);

            return 0;
        } catch (Exception $e) {
            $this->error('Database backup failed completely: ' . $e->getMessage());
            return 1;
        }
    }

    private function cleanupOldBackups(string $backupDir)
    {
        $files = glob("{$backupDir}/*.sql");
        $now = time();
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= 7 * 24 * 60 * 60) {
                    unlink($file);
                    $this->info('Deleted old backup file: ' . basename($file));
                }
            }
        }
    }
}
