<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Ifsnop\Mysqldump\Mysqldump;

class Backup extends Command
{
    protected $signature = 'backup {--tenant= : Backup specific tenant} {--all : Backup all tenants} {--clean : Clean old backups} {--retention=7 : Number of days to keep backups}';

    protected $description = 'Create backup for tenant databases and upload to S3';

    public function handle()
    {
        $specificTenant = $this->option('tenant');
        $backupAll = $this->option('all');
        $cleanOnly = $this->option('clean');
        $retentionDays = (int) $this->option('retention');

        if (!$specificTenant && !$backupAll && !$cleanOnly) {
            $this->error('Vous devez spécifier --tenant=ID, --all, ou --clean');
            return 1;
        }

        // Nettoyage des anciens backups S3 si demandé
        if ($cleanOnly || $backupAll) {
            $this->cleanOldS3Backups($retentionDays);
        }

        // Si c'est seulement un nettoyage, on s'arrête ici
        if ($cleanOnly) {
            return 0;
        }

        $timestamp = now()->format('Y-m-d_H-i-s');

        if ($specificTenant) {
            $tenant = Tenant::find($specificTenant);

            if (!$tenant) {
                $this->error("Tenant '{$specificTenant}' non trouvé");
                return 1;
            }

            $this->backupTenant($tenant, $timestamp);
        } else {
            // Backup de la base centrale
            $this->backupCentralDatabase($timestamp);

            // Backup de tous les tenants
            $tenants = Tenant::all();

            foreach ($tenants as $tenant) {
                $this->backupTenant($tenant, $timestamp);
            }
        }

        return 0;
    }

    private function backupTenant(Tenant $tenant, string $timestamp): void
    {
        $databaseName = $this->getTenantDatabaseName($tenant);
        $filename = "tenant_{$tenant->id}.sql";

        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s',
                config('database.connections.mysql.host'),
                config('database.connections.mysql.port'),
                $databaseName
            );

            $dump = new Mysqldump(
                $dsn,
                config('database.connections.mysql.username'),
                config('database.connections.mysql.password'),
                [
                    'single-transaction' => true,
                    'routines' => true,
                    'add-drop-table' => true,
                    'no-data' => false,
                    'lock-tables' => false,
                    'add-locks' => true,
                    'extended-insert' => true
                ]
            );

            $tempFile = tempnam(sys_get_temp_dir(), 'backup_');
            $dump->start($tempFile);

            $this->uploadFileToS3($tempFile, $filename, $timestamp);

            unlink($tempFile);
        } catch (\Exception $e) {
            $this->error("❌ Erreur backup tenant {$tenant->id}: " . $e->getMessage());
        }
    }

    private function backupCentralDatabase(string $timestamp): void
    {
        $centralDbName = config('database.connections.mysql.database');
        $filename = "central_{$centralDbName}.sql";

        $this->info("📦 Backup base centrale: {$centralDbName}");

        try {
            // Créer le dump avec Mysqldump-php
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s',
                config('database.connections.mysql.host'),
                config('database.connections.mysql.port'),
                $centralDbName
            );

            $dump = new Mysqldump(
                $dsn,
                config('database.connections.mysql.username'),
                config('database.connections.mysql.password'),
                [
                    'single-transaction' => true,
                    'routines' => true,
                    'add-drop-table' => true,
                    'no-data' => false,
                    'lock-tables' => false,
                    'add-locks' => true,
                    'extended-insert' => true
                ]
            );

            // Créer le fichier temporaire
            $tempFile = tempnam(sys_get_temp_dir(), 'backup_');
            $dump->start($tempFile);

            // Upload vers S3
            $this->uploadFileToS3($tempFile, $filename, $timestamp);

            // Nettoyer le fichier temporaire
            unlink($tempFile);
        } catch (\Exception $e) {
            $this->error("❌ Erreur backup base centrale: " . $e->getMessage());
        }
    }

    private function uploadFileToS3(string $filePath, string $filename, string $timestamp): void
    {
        try {
            $s3Disk = Storage::disk('s3');
            $s3Path = "backups/{$timestamp}/{$filename}";

            $s3Disk->put($s3Path, file_get_contents($filePath));
            $fileSize = $this->formatBytes(filesize($filePath));

            $this->info("☁️ Uploadé sur S3: {$filename} ({$fileSize})");
        } catch (\Exception $e) {
            $this->error("❌ Erreur upload S3 {$filename}: " . $e->getMessage());
        }
    }

    private function cleanOldS3Backups(int $retentionDays): void
    {
        try {
            $s3Disk = Storage::disk('s3');
            $cutoffDate = now()->subDays($retentionDays);
            $deletedCount = 0;
            $totalSize = 0;

            // Lister tous les dossiers dans backups/
            $directories = $s3Disk->directories('backups');

            foreach ($directories as $directory) {
                // Extraire la date du nom du dossier (format: backups/2024-01-15_14-30-00)
                $dirName = basename($directory);

                if (preg_match('/(\d{4}-\d{2}-\d{2})_(\d{2}-\d{2}-\d{2})/', $dirName, $matches)) {
                    $backupDate = \Carbon\Carbon::createFromFormat('Y-m-d_H-i-s', $dirName);

                    if ($backupDate->lt($cutoffDate)) {
                        // Calculer la taille avant suppression
                        $files = $s3Disk->files($directory);
                        $dirSize = 0;

                        foreach ($files as $file) {
                            $dirSize += $s3Disk->size($file);
                            $s3Disk->delete($file);
                        }

                        $totalSize += $dirSize;
                        $deletedCount++;
                        $this->info("🗑️ S3: Supprimé {$dirName} (" . $this->formatBytes($dirSize) . ")");
                    }
                }
            }

            if ($deletedCount > 0) {
                $this->info("✅ S3: {$deletedCount} backup(s) supprimé(s), " . $this->formatBytes($totalSize) . " libéré(s)");
            } else {
                $this->info("✅ S3: Aucun backup ancien à supprimer");
            }
        } catch (\Exception $e) {
            $this->error("❌ Erreur nettoyage S3: " . $e->getMessage());
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }

    private function getTenantDatabaseName(Tenant $tenant): string
    {
        $prefix = config('tenancy.database.prefix', '');
        $suffix = config('tenancy.database.suffix', '');

        return $prefix . $tenant->getTenantKey() . $suffix;
    }
}
