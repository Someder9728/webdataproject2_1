<?php

namespace App\Console\Commands;

use App\Support\SqliteQuery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PDO;
use RuntimeException;
use Throwable;

class CreateDormBackup extends Command
{
    protected $signature = 'dorm:backup';

    protected $description = 'Back up SQLite and referenced payment proofs';

    public function handle(): int
    {
        $lock = null;
        $snapshot = null;

        try {
            $connection = DB::connection();

            if ($connection->getDriverName() !== 'sqlite') {
                throw new RuntimeException('This backup supports SQLite only.');
            }

            if ($connection->transactionLevel() !== 0) {
                throw new RuntimeException('Cannot back up inside a transaction.');
            }

            if (config('filesystems.disks.payment_proofs.driver') !== 'local') {
                throw new RuntimeException('Payment proofs must use local storage.');
            }

            $root = (string) config('backup.directory');

            if (! is_dir($root) && ! mkdir($root, 0700, true)) {
                throw new RuntimeException('Cannot create backup directory.');
            }

            $root = realpath($root);

            if ($root === false) {
                throw new RuntimeException('Cannot resolve backup directory.');
            }

            $lock = fopen($root.'/backup.lock', 'c');

            if ($lock === false || ! flock($lock, LOCK_EX | LOCK_NB)) {
                throw new RuntimeException('Another backup is already running.');
            }

            $name = now('UTC')->format('Ymd\THis\Z')
                .'-'.bin2hex(random_bytes(4));

            $staging = $root.'/.partial-'.$name;
            $destination = $root.'/'.$name;

            if (! mkdir($staging, 0700)) {
                throw new RuntimeException('Cannot create staging directory.');
            }

            $databaseFile = $staging.'/database.sqlite';

            // SQLite creates a consistent snapshot, including committed WAL data.
            $pdo = $connection->getPdo();
            $pdo->exec('VACUUM INTO '.$pdo->quote($databaseFile));

            $snapshot = new PDO('sqlite:'.$databaseFile);
            $snapshot->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $snapshot->exec('PRAGMA query_only = ON');

            $integrity = SqliteQuery::run($snapshot, 'PRAGMA integrity_check')
                ->fetchAll(PDO::FETCH_COLUMN);

            if ($integrity !== ['ok']) {
                throw new RuntimeException('Snapshot integrity check failed.');
            }

            if (SqliteQuery::run($snapshot, 'PRAGMA foreign_key_check')->fetchAll() !== []) {
                throw new RuntimeException('Snapshot foreign key check failed.');
            }

            // Read references from the snapshot, not the changing live database.
            // Include soft-deleted rows and historical events.
            $paths = SqliteQuery::run($snapshot, "
                SELECT p_proof AS path
                FROM payments
                WHERE p_proof IS NOT NULL AND p_proof <> ''
                UNION
                SELECT proof_path AS path
                FROM payment_events
                WHERE proof_path IS NOT NULL AND proof_path <> ''
                ORDER BY path
            ")->fetchAll(PDO::FETCH_COLUMN);

            $disk = Storage::disk('payment_proofs');
            $proofRoot = realpath($disk->path(''));
            $files = [];

            foreach ($paths as $path) {
                if (
                    ! is_string($path)
                    || str_contains($path, '\\')
                    || str_contains($path, ':')
                    || str_contains($path, "\0")
                    || str_starts_with($path, '/')
                    || preg_match('~(^|/)\.\.?(/|$)~', $path)
                ) {
                    throw new RuntimeException('Invalid proof path in snapshot.');
                }

                $source = realpath($disk->path($path));

                if (
                    $proofRoot === false
                    || $source === false
                    || ! is_file($source)
                    || ! str_starts_with(
                        $source,
                        $proofRoot.DIRECTORY_SEPARATOR
                    )
                ) {
                    throw new RuntimeException(
                        'Referenced proof missing or outside storage: '.$path
                    );
                }

                $relative = 'proofs/'.$path;
                $target = $staging.'/'.$relative;
                $parent = dirname($target);

                if (! is_dir($parent) && ! mkdir($parent, 0700, true)) {
                    throw new RuntimeException('Cannot create proof directory.');
                }

                $before = hash_file('sha256', $source);

                if ($before === false || ! copy($source, $target)) {
                    throw new RuntimeException('Cannot copy proof: '.$path);
                }

                $copied = hash_file('sha256', $target);
                $after = hash_file('sha256', $source);

                if ($copied !== $before || $after !== $before) {
                    throw new RuntimeException(
                        'Proof changed during backup: '.$path
                    );
                }

                $files[$relative] = [
                    'sha256' => $copied,
                    'bytes' => filesize($target),
                ];
            }

            // Close snapshot connection before hashing and publishing.
            $snapshot = null;

            $databaseHash = hash_file('sha256', $databaseFile);

            if ($databaseHash === false) {
                throw new RuntimeException('Cannot hash database snapshot.');
            }

            $files['database.sqlite'] = [
                'sha256' => $databaseHash,
                'bytes' => filesize($databaseFile),
            ];

            ksort($files);

            $manifest = [
                'format_version' => 1,
                'backup_id' => $name,
                'created_at' => now('UTC')->toIso8601String(),
                'proof_count' => count($paths),
                'files' => $files,
            ];

            $json = json_encode(
                $manifest,
                JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                    | JSON_THROW_ON_ERROR
            );

            $written = file_put_contents(
                $staging.'/manifest.json',
                $json,
                LOCK_EX
            );

            if ($written !== strlen($json)) {
                throw new RuntimeException('Cannot write complete manifest.');
            }

            if (! rename($staging, $destination)) {
                throw new RuntimeException('Cannot publish completed backup.');
            }

            $this->info('Backup completed: '.$destination);
            $this->line('Referenced proofs: '.count($paths));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            $this->warn('Any .partial directory is incomplete, not a usable backup.');

            return self::FAILURE;
        } finally {
            $snapshot = null;

            if (is_resource($lock)) {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
    }
}
