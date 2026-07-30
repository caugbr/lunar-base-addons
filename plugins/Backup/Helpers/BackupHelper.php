<?php

namespace Plugins\Backup\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use ZipArchive;
use Exception;

class BackupHelper
{
    protected static string $backupDir = 'backups';

    /**
     * Retorna a lista de backups existentes com metadados
     */
    public static function getBackupsList(): array
    {
        $path = storage_path('app/' . self::$backupDir);
        File::ensureDirectoryExists($path);

        $files = File::files($path);
        $backups = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'zip') continue;

            $filename = $file->getFilename();
            $size = $file->getSize();
            $createdAt = \Carbon\Carbon::createFromTimestamp($file->getMTime());

            $backups[] = [
                'filename'   => $filename,
                'size'       => $size,
                'size_formatted' => self::formatBytes($size),
                'created_at' => $createdAt->format('d/m/Y H:i:s'),
                'timestamp'  => $file->getMTime(),
            ];
        }

        // Ordena do mais recente para o mais antigo
        usort($backups, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return $backups;
    }

    /**
     * Gera um novo arquivo de backup em .zip
     */
    public static function createBackup(array $options = []): string
    {
        $includeDb      = $options['include_db'] ?? true;
        $includeMedia   = $options['include_media'] ?? true;
        $includePlugins = $options['include_plugins'] ?? true;
        $includeThemes  = $options['include_themes'] ?? true;

        $timestamp = date('Y-m-d_H-i-s');
        $filename  = "backup-{$timestamp}.zip";

        $backupFolderPath = storage_path('app/' . self::$backupDir);
        File::ensureDirectoryExists($backupFolderPath);

        $zipPath = $backupFolderPath . '/' . $filename;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Não foi possível criar o arquivo ZIP de backup.");
        }

        // 1. DUMP DO BANCO DE DADOS
        if ($includeDb) {
            $dbDriver = config('database.default');

            if ($dbDriver === 'sqlite') {
                $sqlitePath = database_path('database.sqlite');
                if (File::exists($sqlitePath)) {
                    $zip->addFile($sqlitePath, 'database/database.sqlite');
                }
            } else {
                // Dump genérico MySQL via PDO
                $sqlContent = self::dumpMysqlDatabase();
                $zip->addFromString('database/database.sql', $sqlContent);
            }
        }

        // 2. PASTA DE MÍDIAS (storage/app/public)
        if ($includeMedia) {
            $mediaPath = storage_path('app/public');
            if (File::exists($mediaPath)) {
                self::addDirectoryToZip($zip, $mediaPath, 'uploads');
            }
        }

        // 3. PLUGINS (plugins/)
        if ($includePlugins) {
            $pluginsPath = base_path('plugins');
            if (File::exists($pluginsPath)) {
                self::addDirectoryToZip($zip, $pluginsPath, 'plugins');
            }
        }

        // 4. TEMAS (themes/)
        if ($includeThemes) {
            $themesPath = base_path('themes');
            if (File::exists($themesPath)) {
                self::addDirectoryToZip($zip, $themesPath, 'themes');
            }
        }

        // 5. MANIFESTO
        $manifest = [
            'app_version' => config('app.version', '1.2.0'),
            'created_at'  => date('Y-m-d H:i:s'),
            'options'     => $options,
            'db_driver'   => config('database.default'),
        ];
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

        $zip->close();

        return $filename;
    }

    /**
     * Restaura um arquivo de backup
     */
    public static function restoreBackup(string $filename): bool
    {
        $zipPath = storage_path('app/' . self::$backupDir . '/' . $filename);

        if (!File::exists($zipPath)) {
            throw new Exception("Arquivo de backup '{$filename}' não encontrado.");
        }

        $tempPath = storage_path('app/temp/restore_' . uniqid());
        File::ensureDirectoryExists($tempPath);

        try {
            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new Exception("Não foi possível abrir o arquivo ZIP.");
            }
            $zip->extractTo($tempPath);
            $zip->close();

            // 1. RESTAURA O BANCO DE DADOS
            if (File::exists($tempPath . '/database/database.sqlite')) {
                File::copy($tempPath . '/database/database.sqlite', database_path('database.sqlite'));
            } elseif (File::exists($tempPath . '/database/database.sql')) {
                $sql = File::get($tempPath . '/database/database.sql');
                DB::unprepared($sql);
            }

            // 2. RESTAURA UPLOADS
            if (File::exists($tempPath . '/uploads')) {
                File::copyDirectory($tempPath . '/uploads', storage_path('app/public'));
            }

            // 3. RESTAURA PLUGINS
            if (File::exists($tempPath . '/plugins')) {
                File::copyDirectory($tempPath . '/plugins', base_path('plugins'));
            }

            // 4. RESTAURA TEMAS
            if (File::exists($tempPath . '/themes')) {
                File::copyDirectory($tempPath . '/themes', base_path('themes'));
            }

            // 5. ATUALIZA LINKS SIMBÓLICOS E CACHE
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('storage:link');
            Artisan::call('optimize:clear');

            return true;
        } catch (Exception $e) {
            logger()->error("Erro na restauração do backup: " . $e->getMessage());
            throw $e;
        } finally {
            File::deleteDirectory($tempPath);
        }
    }

    /**
     * Exporta as tabelas do MySQL para uma string SQL via PDO puro
     */
    protected static function dumpMysqlDatabase(): string
    {
        $tables = DB::select('SHOW TABLES');
        $dbName = config('database.connections.mysql.database');
        $keyName = "Tables_in_{$dbName}";

        $sql  = "-- Lunar Base Backup Dump\n";
        $sql .= "-- Data: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $tableObj) {
            $table = $tableObj->$keyName ?? array_values((array)$tableObj)[0];

            $createTable = DB::select("SHOW CREATE TABLE `{$table}`");
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $createTable[0]->{'Create Table'} . ";\n\n";

            $rows = DB::table($table)->get();
            foreach ($rows as $row) {
                $rowArray = (array)$row;
                $values = array_map(function ($val) {
                    if (is_null($val)) return 'NULL';
                    return DB::getPdo()->quote($val);
                }, $rowArray);

                $sql .= "INSERT INTO `{$table}` (`" . implode('`, `', array_keys($rowArray)) . "`) VALUES (" . implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $sql;
    }

    /**
     * Auxiliar para adicionar uma pasta recursivamente no ZIP
     */
    protected static function addDirectoryToZip(ZipArchive $zip, string $dirPath, string $zipDir): void
    {
        $files = File::allFiles($dirPath);

        foreach ($files as $file) {
            $relativePath = $zipDir . '/' . $file->getRelativePathname();
            $zip->addFile($file->getRealPath(), $relativePath);
        }
    }

    protected static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
