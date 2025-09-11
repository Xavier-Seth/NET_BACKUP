<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use ZipArchive;

// your models + decryptor
use App\Models\Document;
use App\Models\SchoolPropertyDocument;
use App\Services\DocumentUploadService;

class BackupService
{
    /**
     * Create a full backup ZIP:
     *  - database.sql
     *  - storage_public/documents/*  (DECRYPTED, with original uploaded names)
     *  - storage_public/* (other folders copied as-is)
     *
     * Returns the stored filename (e.g., backup_20250909_151230.zip)
     */
    public function make(): string
    {
        @set_time_limit(0);

        // 1) Paths
        $ts = now()->format('Ymd_His');
        $zipName = "backup_{$ts}.zip";
        $zipPath = storage_path("app/backups/{$zipName}");
        $workDir = storage_path("app/backups/tmp_{$ts}");
        $dbFile = "{$workDir}/database.sql";

        if (!is_dir(dirname($zipPath))) {
            @mkdir(dirname($zipPath), 0755, true);
        }
        if (!is_dir($workDir)) {
            @mkdir($workDir, 0755, true);
        }

        // 2) Dump DB
        $this->dumpDatabase($dbFile);

        // 3) Create ZIP
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Cannot create backup zip');
        }

        // database.sql
        $zip->addFile($dbFile, 'database.sql');

        // 4) Add storage/app/public — but decrypt files under documents/*
        $this->addPublicDirDecrypted($zip, 'storage_public');

        $zip->close();

        // 5) Cleanup
        $this->rrmdir($workDir);

        return $zipName;
    }

    /* ---------------------------------------------------------------------- */
    /*                          STORAGE ADDER (DECRYPT)                       */
    /* ---------------------------------------------------------------------- */

    /**
     * Walk the public disk and add files to ZIP.
     * - For paths starting with "documents/", read base64 blob, decrypt via DocumentUploadService,
     *   and add using the original uploaded filename from DB (fallback to basename).
     * - For all other paths, copy bytes as-is.
     */
    protected function addPublicDirDecrypted(ZipArchive $zip, string $insideRoot): void
    {
        // Map "path" => "original uploaded name"
        $nameMap = [];

        foreach (Document::query()->get(['path', 'name']) as $row) {
            if ($row->path)
                $nameMap[$row->path] = $row->name ?: basename($row->path);
        }
        foreach (SchoolPropertyDocument::query()->get(['path', 'name']) as $row) {
            if ($row->path)
                $nameMap[$row->path] = $row->name ?: basename($row->path);
        }

        $crypto = app(DocumentUploadService::class);

        // Iterate everything under storage/app/public via the disk
        foreach (Storage::disk('public')->allFiles() as $relPath) {
            $zipPath = $insideRoot . '/' . str_replace('\\', '/', $relPath);

            if (str_starts_with($relPath, 'documents/')) {
                // Encrypted blob → decrypt
                $encBase64 = Storage::disk('public')->get($relPath);        // base64( IV || base64(cipher) ) per your code
                $plain = $crypto->decryptFileContents($encBase64);       // raw bytes (string) or false/null on error

                // Use original uploaded filename if known
                $orig = $nameMap[$relPath] ?? basename($relPath);
                $orig = $this->safeFilename($orig);

                // Put decrypted bytes (fallback: encrypted) under storage_public/documents/<original_name>
                $zip->addFromString($insideRoot . '/documents/' . $orig, $plain ?? $encBase64);
            } else {
                // Non-encrypted folders (previews/, converted/, etc.) → copy as-is
                $full = storage_path('app/public/' . $relPath);
                if (is_file($full)) {
                    $zip->addFile($full, $zipPath);
                }
            }
        }
    }

    /* ---------------------------------------------------------------------- */
    /*                              DB DUMPER                                 */
    /* ---------------------------------------------------------------------- */

    /**
     * Simple SQL dump (schema + data) using PDO (no external binaries).
     */
    protected function dumpDatabase(string $outputFile): void
    {
        $pdo = DB::connection()->getPdo();
        $dbName = DB::getDatabaseName();

        $tables = [];
        $stmt = $pdo->query('SHOW TABLES');
        while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $sql = "-- DocuNet backup\n-- Database: `{$dbName}`\n-- Generated: " . now()->toDateTimeString() . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            // Drop + Create
            $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
            $createSql = $createStmt['Create Table'] ?? '';
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n{$createSql};\n\n";

            // Data
            $rows = $pdo->query("SELECT * FROM `{$table}`");
            $first = $rows->fetch(\PDO::FETCH_ASSOC);
            $cols = array_keys($first ?: []);
            if (!empty($cols)) {
                $rows = $pdo->query("SELECT * FROM `{$table}`", \PDO::FETCH_ASSOC);
                $sql .= "LOCK TABLES `{$table}` WRITE;\n";
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($cols as $c) {
                        $v = $row[$c];
                        $values[] = is_null($v) ? 'NULL' : $pdo->quote($v);
                    }
                    $sql .= "INSERT INTO `{$table}` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $values) . ");\n";
                }
                $sql .= "UNLOCK TABLES;\n\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($outputFile, $sql);
    }

    /* ---------------------------------------------------------------------- */
    /*                               HELPERS                                  */
    /* ---------------------------------------------------------------------- */

    protected function safeFilename(string $name): string
    {
        $name = basename(trim($name) ?: 'file');
        return preg_replace('/[\/\\\\:*?"<>|]/', '_', $name);
    }

    protected function rrmdir(string $dir): void
    {
        if (!is_dir($dir))
            return;
        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iter as $file) {
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }
        @rmdir($dir);
    }
}
