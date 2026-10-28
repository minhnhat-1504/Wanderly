<?php

namespace App\Services\Admin;

use App\Models\Backup;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use ZipArchive;

class AdminBackupService
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Default number of completed backups to retain.
     */
    public const RETENTION_LIMIT = 7;

    /**
     * Retrieve paginated backups with admin relationship.
     */
    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return Backup::with('admin')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    /**
     * Retrieve summary statistics for the backup dashboard.
     */
    public function getStats(): array
    {
        $totalBackups = Backup::count();
        $completedBackups = Backup::where('status', 'completed')->get();
        $totalSizeBytes = $completedBackups->sum('size_bytes');

        $lastBackup = Backup::where('status', 'completed')
            ->orderBy('id', 'desc')
            ->first();

        $dbCount = Backup::where('type', 'database')->count();
        $fullCount = Backup::where('type', 'full')->count();

        return [
            'total_backups' => $totalBackups,
            'completed_count' => $completedBackups->count(),
            'total_size_bytes' => $totalSizeBytes,
            'formatted_total_size' => $this->formatBytes($totalSizeBytes),
            'last_backup' => $lastBackup,
            'db_backups_count' => $dbCount,
            'full_backups_count' => $fullCount,
            'retention_limit' => self::RETENTION_LIMIT,
        ];
    }

    /**
     * Create and execute a backup (database or full).
     */
    public function createBackup(User $admin, string $type = 'database'): Backup
    {
        if (! in_array($type, ['database', 'full'], true)) {
            $type = 'database';
        }

        $timestamp = now()->format('Y-m-d_His');
        $filename = "wanderly_{$type}_{$timestamp}.zip";
        $diskName = 'backups';

        // Ensure disk directory exists
        $storageDir = storage_path('app/backups');
        if (! File::exists($storageDir)) {
            File::makeDirectory($storageDir, 0755, true);
        }

        $backup = Backup::create([
            'admin_id' => $admin->id,
            'type' => $type,
            'filename' => $filename,
            'disk' => $diskName,
            'size_bytes' => 0,
            'status' => 'pending',
            'error_message' => null,
            'completed_at' => null,
        ]);

        $tmpDir = $storageDir.'/tmp_'.$timestamp.'_'.uniqid();
        File::makeDirectory($tmpDir, 0755, true);

        try {
            // 1. Dump database to SQL file
            $sqlFilePath = $tmpDir.'/database.sql';
            $this->dumpDatabaseToSqlFile($sqlFilePath);

            // 2. Build ZIP archive
            $zipPath = $storageDir.'/'.$filename;
            $this->buildZipArchive($zipPath, $sqlFilePath, $type);

            $sizeBytes = File::size($zipPath);

            $backup->update([
                'size_bytes' => $sizeBytes,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Auto clean older backups past retention limit
            $this->cleanOldBackups(self::RETENTION_LIMIT);

            $this->auditLogService->record(
                $admin,
                'backup.create',
                $backup,
                [],
                [
                    'filename' => $backup->filename,
                    'type' => $type,
                    'size_bytes' => $sizeBytes,
                    'formatted_size' => $backup->formatted_size,
                ]
            );

            return $backup;
        } catch (Exception $e) {
            Log::error('Admin backup creation failed: '.$e->getMessage(), [
                'exception' => $e,
                'backup_id' => $backup->id,
            ]);

            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $this->auditLogService->record(
                $admin,
                'backup.failed',
                $backup,
                [],
                [
                    'error' => $e->getMessage(),
                    'type' => $type,
                ]
            );

            throw $e;
        } finally {
            // Cleanup temp directory
            if (File::exists($tmpDir)) {
                File::deleteDirectory($tmpDir);
            }
        }
    }

    /**
     * Download a completed backup file.
     */
    public function download(User $admin, Backup $backup): StreamedResponse|BinaryFileResponse
    {
        if (! $backup->isCompleted() || ! $backup->fileExists()) {
            throw new NotFoundHttpException(__('Backup file not found or incomplete.'));
        }

        $this->auditLogService->record(
            $admin,
            'backup.download',
            $backup,
            [],
            [
                'filename' => $backup->filename,
                'size_bytes' => $backup->size_bytes,
            ]
        );

        return Storage::disk($backup->disk)->download($backup->filename);
    }

    /**
     * Delete a backup record and its physical archive file.
     */
    public function delete(User $admin, Backup $backup): bool
    {
        if ($backup->fileExists()) {
            Storage::disk($backup->disk)->delete($backup->filename);
        }

        $this->auditLogService->record(
            $admin,
            'backup.delete',
            $backup,
            [
                'filename' => $backup->filename,
                'size_bytes' => $backup->size_bytes,
                'type' => $backup->type,
            ],
            []
        );

        return (bool) $backup->delete();
    }

    /**
     * Automatically prune older backups exceeding the retention limit.
     */
    public function cleanOldBackups(int $keepCount = self::RETENTION_LIMIT): int
    {
        $excessBackups = Backup::where('status', 'completed')
            ->orderBy('id', 'desc')
            ->skip($keepCount)
            ->take(100)
            ->get();

        $deletedCount = 0;
        foreach ($excessBackups as $oldBackup) {
            if ($oldBackup->fileExists()) {
                Storage::disk($oldBackup->disk)->delete($oldBackup->filename);
            }
            $oldBackup->delete();
            $deletedCount++;
        }

        return $deletedCount;
    }

    /**
     * Provide comprehensive manual disaster recovery instructions for sysadmins.
     */
    public function getRestoreInstructions(Backup $backup): array
    {
        $dbConfig = config('database.connections.'.config('database.default'));
        $dbHost = $dbConfig['host'] ?? '127.0.0.1';
        $dbPort = $dbConfig['port'] ?? '3306';
        $dbName = $dbConfig['database'] ?? 'wanderly';
        $dbUser = $dbConfig['username'] ?? 'root';

        return [
            'backup' => $backup,
            'filename' => $backup->filename,
            'type' => $backup->type,
            'steps' => [
                [
                    'title' => __('1. Enable Maintenance Mode'),
                    'command' => 'php artisan down --secret="wanderly-recovery" --render="errors::503"',
                    'note' => __('Prevents incoming traffic while the database is being restored.'),
                ],
                [
                    'title' => __('2. Unzip Archive to Temp Folder'),
                    'command' => "unzip storage/app/backups/{$backup->filename} -d storage/app/backups/restore_tmp",
                    'note' => __('Extracts database.sql and optional public media uploads.'),
                ],
                [
                    'title' => __('3. Restore MySQL Database'),
                    'command' => "mysql -h {$dbHost} -P {$dbPort} -u {$dbUser} -p {$dbName} < storage/app/backups/restore_tmp/database.sql",
                    'note' => __('Recreates all tables and imports dataset with foreign key checks temporarily disabled.'),
                ],
                [
                    'title' => __('4. Restore User Uploads (If Full Backup)'),
                    'command' => 'cp -r storage/app/backups/restore_tmp/uploads/* storage/app/public/',
                    'note' => __('Restores avatars, covers, and destination photos back to public storage.'),
                ],
                [
                    'title' => __('5. Clear Caches & Verify'),
                    'command' => "php artisan optimize:clear\nphp artisan migrate --force",
                    'note' => __('Clears stale application cache and verifies schema integrity.'),
                ],
                [
                    'title' => __('6. Bring Application Online'),
                    'command' => 'php artisan up',
                    'note' => __('Resumes normal service for all users.'),
                ],
            ],
        ];
    }

    /**
     * Dump database tables and rows to a self-contained SQL file.
     */
    protected function dumpDatabaseToSqlFile(string $targetSqlPath): void
    {
        $handle = fopen($targetSqlPath, 'w');
        fwrite($handle, "-- Wanderly Automated Backup\n");
        fwrite($handle, '-- Generated at: '.now()->toIso8601String()."\n");
        fwrite($handle, '-- Environment: '.config('app.env')."\n");
        fwrite($handle, '-- Connection: '.config('database.default')."\n\n");

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->dumpSqlite($handle);
        } else {
            $this->dumpMysql($handle);
        }

        fclose($handle);
    }

    /**
     * Dump SQLite schema and data.
     *
     * @param  resource  $handle
     */
    protected function dumpSqlite($handle): void
    {
        $tables = DB::select("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

        foreach ($tables as $table) {
            $tableName = $table->name;
            if (empty($table->sql)) {
                continue;
            }

            fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");
            fwrite($handle, $table->sql.";\n\n");

            $this->dumpTableRows($handle, $tableName);
        }
    }

    /**
     * Dump MySQL schema and data.
     *
     * @param  resource  $handle
     */
    protected function dumpMysql($handle): void
    {
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");

        $tables = DB::select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');

        foreach ($tables as $table) {
            $vars = (array) $table;
            $tableName = reset($vars);

            fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");

            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
            $createSql = ((array) $createTable[0])['Create Table'] ?? reset((array) $createTable[0]);
            fwrite($handle, $createSql.";\n\n");

            $this->dumpTableRows($handle, $tableName);
        }

        fwrite($handle, "\nSET FOREIGN_KEY_CHECKS=1;\n");
    }

    /**
     * Stream rows from a table and write SQL INSERT statements.
     *
     * @param  resource  $handle
     */
    protected function dumpTableRows($handle, string $tableName): void
    {
        $pdo = DB::getPdo();
        $batch = [];
        $batchSize = 100;

        foreach (DB::table($tableName)->cursor() as $row) {
            $rowArray = (array) $row;
            if (empty($rowArray)) {
                continue;
            }

            $batch[] = $rowArray;

            if (count($batch) >= $batchSize) {
                $this->writeInsertBatch($handle, $tableName, $batch, $pdo);
                $batch = [];
            }
        }

        if (! empty($batch)) {
            $this->writeInsertBatch($handle, $tableName, $batch, $pdo);
        }

        fwrite($handle, "\n");
    }

    /**
     * Write a batch of INSERT rows to file.
     *
     * @param  resource  $handle
     * @param  \PDO  $pdo
     */
    protected function writeInsertBatch($handle, string $tableName, array $batch, $pdo): void
    {
        if (empty($batch)) {
            return;
        }

        $cols = array_map(fn ($c) => "`{$c}`", array_keys($batch[0]));
        $colsStr = implode(', ', $cols);

        $valuesList = [];
        foreach ($batch as $row) {
            $escaped = array_map(function ($val) use ($pdo) {
                if (is_null($val)) {
                    return 'NULL';
                }

                return $pdo->quote((string) $val);
            }, array_values($row));

            $valuesList[] = '('.implode(', ', $escaped).')';
        }

        fwrite($handle, "INSERT INTO `{$tableName}` ({$colsStr}) VALUES\n".implode(",\n", $valuesList).";\n");
    }

    /**
     * Build ZIP archive containing database.sql and optional media uploads.
     */
    protected function buildZipArchive(string $zipPath, string $sqlFilePath, string $type): void
    {
        if (class_exists(ZipArchive::class)) {
            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception("Unable to create zip file at {$zipPath}");
            }

            // Add database dump
            $zip->addFile($sqlFilePath, 'database.sql');

            // If full backup, add public uploads directory
            if ($type === 'full') {
                $publicPath = storage_path('app/public');
                if (File::exists($publicPath)) {
                    $this->addDirectoryToZip($zip, $publicPath, 'uploads');
                }
            }

            $zip->close();
        } else {
            // Fallback: create .gz archive for database.sql
            $sqlContent = File::get($sqlFilePath);
            $gzContent = gzencode($sqlContent, 9);
            File::put($zipPath, $gzContent);
        }
    }

    /**
     * Recursively add a directory to a ZipArchive.
     */
    protected function addDirectoryToZip(ZipArchive $zip, string $dirPath, string $zipDir = ''): void
    {
        $files = File::allFiles($dirPath);
        foreach ($files as $file) {
            $relativePath = $zipDir.'/'.$file->getRelativePathname();
            $zip->addFile($file->getRealPath(), $relativePath);
        }
    }

    /**
     * Format bytes into human readable string.
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $power), 2).' '.$units[$power];
    }
}
