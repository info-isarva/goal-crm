<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BackupController extends Controller
{
    /**
     * Create a downloadable mysqldump backup of the configured database.
     * Requires mysqldump available on the server PATH.
     */
    public function backup(Request $request)
    {
        $user = Auth::user();
        if (! $user || ! in_array($user->crm_role_type, [0, 1])) {
            abort(403, 'Unauthorized');
        }

        try {
            $gzPath = $this->createStoredBackup();
        } catch (\Throwable $e) {
            Log::error('Database backup failed', ['error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'Database backup failed. Check the application log for details.');
        }

        return response()->download($gzPath)->deleteFileAfterSend(true);
    }

    /**
     * Create a stored compressed backup in storage/app/backups and return the path.
     * This does not stream the file to the user.
     * Throws exception on failure.
     */
    public function createStoredBackup()
    {
        $db = config("database.connections.mysql", []);

        $database = $db['database'] ?? env('DB_DATABASE');

        $backupDir = storage_path('app/backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $fileName = 'db_backup_' . date('Ymd_His') . '.sql';
        $fullPath = $backupDir . DIRECTORY_SEPARATOR . $fileName;
        $gzPath = $fullPath . '.gz';

        if (! $this->runMysqldump($db, $database, $fullPath)) {
            $this->generatePhpSqlFile($database, $fullPath);
        }

        // Compress to gz using PHP so it works on Windows without gzip in PATH
        $fpIn = fopen($fullPath, 'rb');
        if ($fpIn === false) {
            throw new \Exception('Failed to open generated SQL file for compression');
        }
        $gz = gzopen($gzPath, 'wb9');
        if ($gz === false) {
            fclose($fpIn);
            throw new \Exception('Failed to create gz file');
        }
        while (!feof($fpIn)) {
            $chunk = fread($fpIn, 1024 * 1024);
            gzwrite($gz, $chunk);
        }
        gzclose($gz);
        fclose($fpIn);
        @unlink($fullPath);

        if (!file_exists($gzPath) || filesize($gzPath) === 0) {
            throw new \Exception('Compressed backup missing or empty');
        }

        return $gzPath;
    }

    /**
     * Dump the database with mysqldump. Credentials are passed through a temporary
     * option file so they never appear on the command line.
     */
    protected function runMysqldump(array $db, ?string $database, string $outPath): bool
    {
        $dumpBin = $this->findMysqldump();
        if (! $dumpBin || ! $database || ! function_exists('exec')) {
            return false;
        }

        $optionFile = tempnam(storage_path('app'), 'mydump');
        if ($optionFile === false) {
            return false;
        }

        $quote = fn ($value) => '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], (string) $value) . '"';
        $options = "[client]\n"
            . 'user=' . $quote($db['username'] ?? env('DB_USERNAME')) . "\n"
            . 'password=' . $quote($db['password'] ?? env('DB_PASSWORD')) . "\n"
            . 'host=' . $quote($db['host'] ?? env('DB_HOST', '127.0.0.1')) . "\n"
            . 'port=' . (int) ($db['port'] ?? env('DB_PORT', 3306)) . "\n";

        try {
            file_put_contents($optionFile, $options);
            @chmod($optionFile, 0600);

            $cmd = sprintf(
                '%s --defaults-extra-file=%s --result-file=%s %s 2>&1',
                escapeshellarg($dumpBin),
                escapeshellarg($optionFile),
                escapeshellarg($outPath),
                escapeshellarg($database)
            );

            $output = [];
            $result = null;
            @exec($cmd, $output, $result);

            if ($result !== 0 || ! file_exists($outPath) || filesize($outPath) === 0) {
                Log::warning('mysqldump failed, using PHP fallback', [
                    'exit_code' => $result,
                    'output' => implode("\n", array_slice($output, 0, 20)),
                ]);
                @unlink($outPath);

                return false;
            }

            return true;
        } finally {
            @unlink($optionFile);
        }
    }

    protected function findMysqldump(): ?string
    {
        if (! function_exists('exec')) {
            return null;
        }

        $isWindows = stripos(PHP_OS, 'WIN') === 0;
        $whichOutput = [];
        @exec($isWindows ? 'where mysqldump 2>NUL' : 'which mysqldump 2>/dev/null', $whichOutput);
        if (! empty($whichOutput[0]) && is_file(trim($whichOutput[0]))) {
            return trim($whichOutput[0]);
        }

        $possible = $isWindows
            ? [
                'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
                'C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin\\mysqldump.exe',
                'C:\\xampp\\mysql\\bin\\mysqldump.exe',
                'C:\\wamp64\\bin\\mysql\\mysql8.0.0\\bin\\mysqldump.exe',
            ]
            : ['/usr/bin/mysqldump', '/usr/local/bin/mysqldump'];

        foreach ($possible as $p) {
            if (file_exists($p) && is_executable($p)) {
                return $p;
            }
        }

        return null;
    }

    /**
     * Generate an SQL file using PHP (non-streaming) for fallback.
     */
    protected function generatePhpSqlFile($database, $outPath)
    {
        $pdo = \DB::connection()->getPdo();
        $ident = fn ($name) => '`' . str_replace('`', '``', (string) $name) . '`';

        $fp = fopen($outPath, 'wb');
        if ($fp === false) throw new \Exception('Unable to create SQL file');
        fwrite($fp, "-- PHP generated dump for database: " . str_replace(["\r", "\n"], ' ', (string) $database) . "\n");
        fwrite($fp, "-- Generated: " . date('Y-m-d H:i:s') . "\n\n");
        fwrite($fp, "SET FOREIGN_KEY_CHECKS=0;\n");
        fwrite($fp, "CREATE DATABASE IF NOT EXISTS " . $ident($database) . ";\n");
        fwrite($fp, "USE " . $ident($database) . ";\n\n");

        $stmt = $pdo->query("SHOW TABLES");
        while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            $table = $ident($row[0]);
            fwrite($fp, "DROP TABLE IF EXISTS {$table};\n");
            $row2 = $pdo->query("SHOW CREATE TABLE {$table}")->fetch(\PDO::FETCH_ASSOC);
            $create = $row2['Create Table'] ?? $row2['Create View'] ?? null;
            if ($create) {
                fwrite($fp, $create . ";\n\n");
            }
            $colStmt = $pdo->query("DESCRIBE {$table}");
            $cols = [];
            while ($c = $colStmt->fetch(\PDO::FETCH_ASSOC)) {
                $cols[] = $ident($c['Field']);
            }
            if (empty($cols)) continue;
            $colList = implode(', ', $cols);
            $offset = 0;
            $limit = 500;
            do {
                $dataStmt = $pdo->query("SELECT * FROM {$table} LIMIT {$offset}, {$limit}");
                $rows = $dataStmt->fetchAll(\PDO::FETCH_NUM);
                if (empty($rows)) break;
                foreach ($rows as $r) {
                    $vals = array_map(function ($v) use ($pdo) {
                        if ($v === null) return 'NULL';
                        return $pdo->quote((string) $v);
                    }, $r);
                    fwrite($fp, "INSERT INTO {$table} ({$colList}) VALUES (" . implode(', ', $vals) . ");\n");
                }
                $offset += $limit;
            } while (count($rows) === $limit);
            fwrite($fp, "\n");
        }
        fwrite($fp, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fp);
    }
}
