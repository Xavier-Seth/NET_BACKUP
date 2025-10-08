<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use ZipArchive;

use App\Models\Document;
use App\Models\SchoolPropertyDocument;
use App\Models\Category;
use App\Models\Teacher;
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

        // encrypted/as-is archive (organize folders but keep encrypted bytes for teacher docs)
        $this->buildZip(
            zipPath: storage_path("app/backups/{$encName}"),
            dbFile: $dbFile,
            addPublic: function (ZipArchive $zip) use ($index) {
                $this->addPublicOrganizedAsIs($zip, 'storage_public', $index);
            }
        );

        // decrypted archive (teacher docs decrypted into original filenames; others as-is)
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
     * Folder layout:
     *   storage_public/
     *     teachers/<Teacher>/<Category>/<OriginalName>
     *     property/<Category>/<OriginalName>
     *     misc/<original-relative-path>
     */
    protected function addPublicOrganizedAsIs(ZipArchive $zip, string $insideRoot, array $index): void
    {
        $disk = Storage::disk('public');

        foreach ($disk->allFiles() as $relPath) {
            $meta = $index[$relPath] ?? null;

            if ($meta && $meta['type'] === 'teacher') {
                // Encrypted blob stays encrypted, but moved into organized folders
                $orig = $this->safeFilename($meta['orig'] ?: basename($relPath));
                $teacher = $this->safePathSegment($meta['teacher'] ?? 'Unknown Teacher');
                $category = $this->safePathSegment($meta['category'] ?? 'Uncategorized');

                $zipPath = $insideRoot . '/teachers/' . $teacher . '/' . $category . '/' . $orig;

                // copy exact bytes
                $full = storage_path('app/public/' . $relPath);
                if (is_file($full)) {
                    $zip->addFile($full, $zipPath);
                }
                continue;
            }

            if ($meta && $meta['type'] === 'property') {
                $orig = $this->safeFilename($meta['orig'] ?: basename($relPath));
                $category = $this->safePathSegment($meta['category'] ?? 'Uncategorized');

                $zipPath = $insideRoot . '/property/' . $category . '/' . $orig;

                $full = storage_path('app/public/' . $relPath);
                if (is_file($full)) {
                    $zip->addFile($full, $zipPath);
                }
                continue;
            }

            // Everything else → misc
            $zipPath = $insideRoot . '/misc/' . str_replace('\\', '/', $relPath);
            $full = storage_path('app/public/' . $relPath);
            if (is_file($full)) {
                $zip->addFile($full, $zipPath);
            }
        }
    }

    /**
     * Organized copy of storage/app/public with TEACHER documents decrypted
     * into their original filenames.
     * Folder layout:
     *   storage_public/
     *     teachers/<Teacher>/<Category>/<OriginalName>   (decrypted bytes)
     *     property/<Category>/<OriginalName>             (as-is)
     *     misc/<original-relative-path>                  (as-is)
     */
    protected function addPublicOrganizedDecrypted(ZipArchive $zip, string $insideRoot, array $index): void
    {
        $disk = Storage::disk('public');
        $crypto = app(DocumentUploadService::class);

        foreach ($disk->allFiles() as $relPath) {
            $meta = $index[$relPath] ?? null;

            if ($meta && $meta['type'] === 'teacher') {
                $encBase64 = $disk->get($relPath); // stored encrypted blob (base64 or raw—kept from your original)
                $plain = $crypto->decryptFileContents($encBase64); // raw bytes or null fallback

                $orig = $this->safeFilename($meta['orig'] ?: basename($relPath));
                $teacher = $this->safePathSegment($meta['teacher'] ?? 'Unknown Teacher');
                $category = $this->safePathSegment($meta['category'] ?? 'Uncategorized');

                $zipPath = $insideRoot . '/teachers/' . $teacher . '/' . $category . '/' . $orig;

                // add decrypted bytes; if decrypt fails, include as-is so backup still complete
                $zip->addFromString($zipPath, $plain ?? $encBase64);
                continue;
            }

            if ($meta && $meta['type'] === 'property') {
                $orig = $this->safeFilename($meta['orig'] ?: basename($relPath));
                $category = $this->safePathSegment($meta['category'] ?? 'Uncategorized');

                $zipPath = $insideRoot . '/property/' . $category . '/' . $orig;

                $full = storage_path('app/public/' . $relPath);
                if (is_file($full)) {
                    $zip->addFile($full, $zipPath);
                }
                continue;
            }

            // Others → misc as-is
            $zipPath = $insideRoot . '/misc/' . str_replace('\\', '/', $relPath);
            $full = storage_path('app/public/' . $relPath);
            if (is_file($full)) {
                $zip->addFile($full, $zipPath);
            }
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

    /**
     * Build a map of storage/app/public relative paths to:
     *   [
     *     'orig'     => original filename (from DB.name or basename),
     *     'teacher'  => teacher full name (if any),
     *     'category' => category name (if any),
     *     'type'     => 'teacher' | 'property'
     *   ]
     */
    protected function buildMetadataIndex(): array
    {
        $index = [];

        // Teacher documents (documents table uses teacher_id & category_id)
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

        // School property documents (no teacher; group by category)
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
        // Avoid weird dot segments
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
