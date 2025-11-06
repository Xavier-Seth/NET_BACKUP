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
     * - encrypted file name:   backup_YYYYmmdd_HHMMSS.zip        (as-is bytes)
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

        // Build a single index for all known document paths → (orig name, teacher/category labels, type)
        $index = $this->buildMetadataIndex();

        // encrypted/as-is archive
        $this->buildZip(
            zipPath: storage_path("app/backups/{$encName}"),
            dbFile: $dbFile,
            addPublic: function (ZipArchive $zip) use ($index) {
                $this->addPublicOrganizedAsIs($zip, 'storage_public', $index);
            }
        );

        // decrypted archive
        $this->buildZip(
            zipPath: storage_path("app/backups/{$decName}"),
            dbFile: $dbFile,
            addPublic: function (ZipArchive $zip) use ($index) {
                $this->addPublicOrganizedDecrypted($zip, 'storage_public', $index);
            }
        );

        // cleanup
        $this->rrmdir($workDir);

        return [$encName, $decName];
    }

    /* ------------------------------------------------------------------ */
    /*                           RESTORE API                               */
    /* ------------------------------------------------------------------ */

    /**
     * Restore from a backup ZIP.
     *
     * @param string $fullZip Absolute path to the .zip file
     * @param array  $opts    ['encrypted' => bool, 'mode' => 'replace'|'merge']
     *
     * @return array [bool $ok, string $message, array $report]
     */
    public function restoreFromArchive(string $fullZip, array $opts = []): array
    {
        $opts = array_merge([
            'encrypted' => true,      // true = teacher docs inside ZIP are encrypted blobs
            'mode' => 'replace',      // replace = drop/recreate tables from SQL; merge = run inserts
        ], $opts);

        if (!is_file($fullZip)) {
            return [false, 'Backup archive not found.', ['zip' => $fullZip]];
        }

        @set_time_limit(0);

        $report = [
            'zip' => $fullZip,
            'db_restored' => false,
            'files' => ['teacher' => ['ok' => 0, 'miss' => 0], 'property' => ['ok' => 0, 'miss' => 0]],
            'warnings' => [],
        ];

        $tmp = storage_path('app/backups/restore_' . uniqid());
        if (!is_dir($tmp))
            @mkdir($tmp, 0755, true);

        $zip = new ZipArchive();
        if ($zip->open($fullZip) !== true) {
            return [false, 'Cannot open ZIP archive.', ['zip' => $fullZip]];
        }

        // 1) Extract everything
        $zip->extractTo($tmp);

        // 2) Find database.sql
        $dbFile = $this->findFileCaseInsensitive($tmp, 'database.sql');
        if (!$dbFile) {
            $zip->close();
            $this->rrmdir($tmp);
            return [false, 'database.sql not found inside archive.', $report];
        }

        // 3) Import SQL (replace or merge)
        try {
            $sql = file_get_contents($dbFile);
            if ($opts['mode'] === 'replace') {
                DB::unprepared($sql);
            } else {
                DB::unprepared("SET FOREIGN_KEY_CHECKS=0;");
                DB::unprepared($sql);
                DB::unprepared("SET FOREIGN_KEY_CHECKS=1;");
            }
            $report['db_restored'] = true;
        } catch (\Throwable $e) {
            $zip->close();
            $this->rrmdir($tmp);
            return [false, 'Database restore failed: ' . $e->getMessage(), $report];
        }

        // 4) Restore files based on fresh DB
        $publicRoot = storage_path('app/public');
        if (!is_dir($publicRoot))
            @mkdir($publicRoot, 0755, true);

        $rootInZip = $this->findDirCaseInsensitive($tmp, 'storage_public');

        $teacherDocs = Document::query()
            ->with(['teacher:id,full_name', 'category:id,name'])
            ->get(['id', 'path', 'name', 'teacher_id', 'category_id']);

        $propDocs = SchoolPropertyDocument::query()
            ->with(['category:id,name'])
            ->get(['id', 'path', 'name', 'category_id']);

        $crypto = app(DocumentUploadService::class);

        $putPublic = function (string $relativePath, string $bytes) use ($publicRoot) {
            $dest = $publicRoot . '/' . ltrim($relativePath, '/');
            if (!is_dir(dirname($dest)))
                @mkdir(dirname($dest), 0755, true);
            file_put_contents($dest, $bytes);
        };

        // Teacher docs
        foreach ($teacherDocs as $doc) {
            $teacher = optional($doc->teacher)->full_name ?: 'Unknown Teacher';
            $category = optional($doc->category)->name ?: 'Uncategorized';
            $origName = $doc->name ?: basename($doc->path);

            $organized = $this->buildTeacherOrganizedPath($rootInZip, $teacher, $category, $origName);
            if (!$organized || !is_file($organized)) {
                $report['files']['teacher']['miss']++;
                continue;
            }

            $bytes = file_get_contents($organized);

            // If ZIP is decrypted, re-encrypt before saving to public storage
            if (!$opts['encrypted']) {
                if (method_exists($crypto, 'encryptBytes')) {
                    try {
                        $bytes = $crypto->encryptBytes($bytes); // raw -> base64(iv+cipher)
                    } catch (\Throwable $e) {
                        $report['warnings'][] = "Encrypt failed for {$doc->path}: {$e->getMessage()} (storing raw bytes)";
                    }
                } else {
                    $report['warnings'][] = "encryptBytes() not found; storing raw bytes for {$doc->path}.";
                }
            }

            $putPublic($doc->path, $bytes);
            $report['files']['teacher']['ok']++;
        }

        // Property docs (never encrypted in storage, just copy)
        foreach ($propDocs as $doc) {
            $category = optional($doc->category)->name ?: 'Uncategorized';
            $origName = $doc->name ?: basename($doc->path);

            $organized = $this->buildPropertyOrganizedPath($rootInZip, $category, $origName);
            if (!$organized || !is_file($organized)) {
                $report['files']['property']['miss']++;
                continue;
            }

            $bytes = file_get_contents($organized);
            $putPublic($doc->path, $bytes);
            $report['files']['property']['ok']++;
        }

        // ✅ 5) Restore any "misc" public files (previews/, converted/, branding/, etc.)
        // These were added under "storage_public/misc/{original-relative-path}"
        $miscRoot = $this->findDirCaseInsensitive($tmp, 'storage_public/misc');
        if ($miscRoot) {
            $copyTree = function (string $src, string $dstBase) {
                $it = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );
                foreach ($it as $node) {
                    $rel = $it->getSubPathName(); // path relative to $src
                    $to = rtrim($dstBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $rel;
                    if ($node->isDir()) {
                        if (!is_dir($to))
                            @mkdir($to, 0755, true);
                    } else {
                        if (!is_dir(dirname($to)))
                            @mkdir(dirname($to), 0755, true);
                        @copy($node->getPathname(), $to);
                    }
                }
            };

            // Copy everything under misc/ back to storage/app/public preserving paths
            $copyTree($miscRoot, $publicRoot);
        }

        $zip->close();
        $this->rrmdir($tmp);

        $msg = 'Restore completed.';
        if ($report['files']['teacher']['miss'] || $report['files']['property']['miss']) {
            $msg .= ' Some files were not found in the archive.';
        }

        return [true, $msg, $report];
    }

    /* ------------------------ restore helpers ------------------------ */

    protected function findFileCaseInsensitive(string $baseDir, string $fileName): ?string
    {
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($baseDir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            if (strtolower($file->getFilename()) === strtolower($fileName)) {
                return $file->getPathname();
            }
        }
        return null;
    }

    protected function findDirCaseInsensitive(string $baseDir, string $dirName): ?string
    {
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($baseDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($it as $file) {
            if ($file->isDir() && strtolower($file->getFilename()) === strtolower($dirName)) {
                return $file->getPathname();
            }
        }
        return null;
    }

    protected function buildTeacherOrganizedPath(?string $root, string $teacher, string $category, string $origName): ?string
    {
        if (!$root)
            return null;
        $teacher = $this->safePathSegment($teacher);
        $category = $this->safePathSegment($category);
        $origName = $this->safeFilename($origName);
        return rtrim($root, '/\\') . "/teachers/{$teacher}/{$category}/{$origName}";
    }

    protected function buildPropertyOrganizedPath(?string $root, string $category, string $origName): ?string
    {
        if (!$root)
            return null;
        $category = $this->safePathSegment($category);
        $origName = $this->safeFilename($origName);
        return rtrim($root, '/\\') . "/property/{$category}/{$origName}";
    }

    /* ------------------------------------------------------------------ */
    /*                          ZIP BUILDERS                               */
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

        // add DB dump
        $zip->addFile($dbFile, 'database.sql');

        // add storage/app/public (organized)
        $addPublic($zip);

        $zip->close();
    }

    /**
     * Organized copy of storage/app/public with encrypted teacher docs as-is.
     */
    protected function addPublicOrganizedAsIs(ZipArchive $zip, string $insideRoot, array $index): void
    {
        $disk = Storage::disk('public');

        foreach ($disk->allFiles() as $relPath) {
            $meta = $index[$relPath] ?? null;

            if ($meta && $meta['type'] === 'teacher') {
                $orig = $this->safeFilename($meta['orig'] ?: basename($relPath));
                $teacher = $this->safePathSegment($meta['teacher'] ?? 'Unknown Teacher');
                $category = $this->safePathSegment($meta['category'] ?? 'Uncategorized');

                $zipPath = $insideRoot . '/teachers/' . $teacher . '/' . $category . '/' . $orig;
                $full = storage_path('app/public/' . $relPath);
                if (is_file($full))
                    $zip->addFile($full, $zipPath);
                continue;
            }

            if ($meta && $meta['type'] === 'property') {
                $orig = $this->safeFilename($meta['orig'] ?: basename($relPath));
                $category = $this->safePathSegment($meta['category'] ?? 'Uncategorized');

                $zipPath = $insideRoot . '/property/' . $category . '/' . $orig;
                $full = storage_path('app/public/' . $relPath);
                if (is_file($full))
                    $zip->addFile($full, $zipPath);
                continue;
            }

            // Everything else (previews/, converted/, branding/, tmp_previews/, etc.)
            $zipPath = $insideRoot . '/misc/' . str_replace('\\', '/', $relPath);
            $full = storage_path('app/public/' . $relPath);
            if (is_file($full))
                $zip->addFile($full, $zipPath);
        }
    }

    /**
     * Organized copy of storage/app/public with TEACHER documents decrypted.
     */
    protected function addPublicOrganizedDecrypted(ZipArchive $zip, string $insideRoot, array $index): void
    {
        $disk = Storage::disk('public');
        $crypto = app(DocumentUploadService::class);

        foreach ($disk->allFiles() as $relPath) {
            $meta = $index[$relPath] ?? null;

            if ($meta && $meta['type'] === 'teacher') {
                $encBase64 = $disk->get($relPath);
                $plain = $crypto->decryptFileContents($encBase64);

                $orig = $this->safeFilename($meta['orig'] ?: basename($relPath));
                $teacher = $this->safePathSegment($meta['teacher'] ?? 'Unknown Teacher');
                $category = $this->safePathSegment($meta['category'] ?? 'Uncategorized');

                $zipPath = $insideRoot . '/teachers/' . $teacher . '/' . $category . '/' . $orig;
                $zip->addFromString($zipPath, $plain ?? $encBase64);
                continue;
            }

            if ($meta && $meta['type'] === 'property') {
                $orig = $this->safeFilename($meta['orig'] ?: basename($relPath));
                $category = $this->safePathSegment($meta['category'] ?? 'Uncategorized');

                $zipPath = $insideRoot . '/property/' . $category . '/' . $orig;
                $full = storage_path('app/public/' . $relPath);
                if (is_file($full))
                    $zip->addFile($full, $zipPath);
                continue;
            }

            // Everything else (previews/, converted/, branding/, tmp_previews/, etc.)
            $zipPath = $insideRoot . '/misc/' . str_replace('\\', '/', $relPath);
            $full = storage_path('app/public/' . $relPath);
            if (is_file($full))
                $zip->addFile($full, $zipPath);
        }
    }

    /* ------------------------------------------------------------------ */
    /*                           DB DUMP                                  */
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
    /*                         METADATA INDEX                              */
    /* ------------------------------------------------------------------ */

    protected function buildMetadataIndex(): array
    {
        $index = [];

        $teacherDocs = Document::query()
            ->with(['teacher:id,full_name', 'category:id,name'])
            ->get(['path', 'name', 'teacher_id', 'category_id']);

        foreach ($teacherDocs as $doc) {
            if (!$doc->path)
                continue;

            $index[$doc->path] = [
                'orig' => $doc->name ?: basename($doc->path),
                'teacher' => optional($doc->teacher)->full_name ?: 'Unknown Teacher',
                'category' => optional($doc->category)->name ?: 'Uncategorized',
                'type' => 'teacher',
            ];
        }

        $propDocs = SchoolPropertyDocument::query()
            ->with(['category:id,name'])
            ->get(['path', 'name', 'category_id']);

        foreach ($propDocs as $doc) {
            if (!$doc->path)
                continue;

            $index[$doc->path] = [
                'orig' => $doc->name ?: basename($doc->path),
                'teacher' => null,
                'category' => optional($doc->category)->name ?: 'Uncategorized',
                'type' => 'property',
            ];
        }

        return $index;
    }

    /* ------------------------------------------------------------------ */
    /*                             HELPERS                                 */
    /* ------------------------------------------------------------------ */

    protected function safeFilename(string $name): string
    {
        $name = basename(trim($name) ?: 'file');
        return preg_replace('/[\/\\\\:*?"<>|]/', '_', $name);
    }

    protected function safePathSegment(string $segment): string
    {
        $segment = trim($segment) ?: 'Untitled';
        $segment = preg_replace('/[\/\\\\:*?"<>|]/', '_', $segment);
        $segment = ltrim($segment, '.');
        return $segment ?: 'Untitled';
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
