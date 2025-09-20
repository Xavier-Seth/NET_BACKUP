<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use ZipArchive;

use App\Models\Document;
use App\Models\SchoolPropertyDocument;
use App\Services\DocumentUploadService;

class BackupService
{
    /**
     * Create BOTH archives and return their names as [encrypted, decrypted].
     *
     * - encrypted file name:   backup_YYYYmmdd_HHMMSS.zip        (as-is)
     * - decrypted file name:   backupdecrypt_YYYYmmdd_HHMMSS.zip (teacher docs decrypted)
     */
    public function makeBoth(): array
    {
        @set_time_limit(0);

        $ts = now()->format('Ymd_His');

        $encName = "backup_{$ts}.zip";
        $decName = "backupdecrypt_{$ts}.zip";

        $workDir = storage_path("app/backups/tmp_{$ts}");
        if (!is_dir(dirname($workDir)))
            @mkdir(dirname($workDir), 0755, true);
        if (!is_dir($workDir))
            @mkdir($workDir, 0755, true);

        // dump DB once and reuse in both zips
        $dbFile = "{$workDir}/database.sql";
        $this->dumpDatabase($dbFile);

        // build encrypted/as-is archive
        $this->buildZip(
            zipPath: storage_path("app/backups/{$encName}"),
            dbFile: $dbFile,
            addPublic: function (ZipArchive $zip) {
                $this->addPublicDirAsIs($zip, 'storage_public');
            }
        );

        // build decrypted archive
        $this->buildZip(
            zipPath: storage_path("app/backups/{$decName}"),
            dbFile: $dbFile,
            addPublic: function (ZipArchive $zip) {
                $this->addPublicDirDecrypted($zip, 'storage_public');
            }
        );

        // cleanup
        $this->rrmdir($workDir);

        return [$encName, $decName];
    }

    /* ------------------------------------------------------------------ */
    /*                     ZIP BUILDERS / ADDERS                           */
    /* ------------------------------------------------------------------ */

    protected function buildZip(string $zipPath, string $dbFile, callable $addPublic): void
    {
        if (!is_dir(dirname($zipPath))) {
            @mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Cannot create backup zip: ' . basename($zipPath));
        }

        // add DB
        $zip->addFile($dbFile, 'database.sql');

        // add storage/app/public
        $addPublic($zip);

        $zip->close();
    }

    /**
     * AS-IS copy of everything under storage/app/public.
     * Teacher docs stay encrypted (exact bytes copied).
     */
    protected function addPublicDirAsIs(ZipArchive $zip, string $insideRoot): void
    {
        foreach (Storage::disk('public')->allFiles() as $relPath) {
            $zipPath = $insideRoot . '/' . str_replace('\\', '/', $relPath);
            $full = storage_path('app/public/' . $relPath);
            if (is_file($full)) {
                $zip->addFile($full, $zipPath);
            }
        }
    }

    /**
     * Decrypt teacher documents into the archive; others copied as-is.
     */
    protected function addPublicDirDecrypted(ZipArchive $zip, string $insideRoot): void
    {
        // "path" => "original uploaded name"
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

        foreach (Storage::disk('public')->allFiles() as $relPath) {
            $zipPath = $insideRoot . '/' . str_replace('\\', '/', $relPath);

            if (str_starts_with($relPath, 'documents/')) {
                // encrypted blob → decrypt into original name
                $encBase64 = Storage::disk('public')->get($relPath);
                $plain = $crypto->decryptFileContents($encBase64); // raw bytes or null

                $orig = $nameMap[$relPath] ?? basename($relPath);
                $orig = $this->safeFilename($orig);

                // Place under storage_public/documents/<original_name>
                $zip->addFromString($insideRoot . '/documents/' . $orig, $plain ?? $encBase64);
            } else {
                // others as-is
                $full = storage_path('app/public/' . $relPath);
                if (is_file($full)) {
                    $zip->addFile($full, $zipPath);
                }
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /*                              DB DUMP                                */
    /* ------------------------------------------------------------------ */

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
            $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
            $createSql = $createStmt['Create Table'] ?? '';
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n{$createSql};\n\n";

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

    /* ------------------------------------------------------------------ */
    /*                               HELPERS                               */
    /* ------------------------------------------------------------------ */

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
