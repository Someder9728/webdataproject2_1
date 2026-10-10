<?php

namespace App\Console\Commands;

use App\Support\VerifyDormBackup;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

class RestoreDormBackup extends Command
{
    protected $signature = 'dorm:restore-check {backup : Backup directory name}';

    protected $description = 'Restore and verify a backup in an isolated directory';

    public function handle(VerifyDormBackup $verifier): int
    {
        $lock = null;

        try {
            $backupId = (string) $this->argument('backup');

            if (! preg_match('/\A\d{8}T\d{6}Z-[a-f0-9]{8}\z/', $backupId)) {
                throw new RuntimeException('Invalid backup directory name.');
            }

            $root = realpath((string) config('backup.directory'));

            if ($root === false) {
                throw new RuntimeException('Backup directory not found.');
            }

            $lock = fopen($root.'/backup.lock', 'c');

            if ($lock === false || ! flock($lock, LOCK_EX | LOCK_NB)) {
                throw new RuntimeException('Another backup operation is running.');
            }

            $source = realpath($root.'/'.$backupId);

            if (
                $source === false
                || dirname($source) !== $root
                || is_link($root.'/'.$backupId)
            ) {
                throw new RuntimeException('Invalid backup source.');
            }

            $manifest = $verifier->check($source, $backupId);

            $restoreRoot = storage_path('backup-restores');

            if (! is_dir($restoreRoot) && ! mkdir($restoreRoot, 0700, true)) {
                throw new RuntimeException('Cannot create restore directory.');
            }

            $restoreRoot = realpath($restoreRoot);

            if ($restoreRoot === false) {
                throw new RuntimeException('Cannot resolve restore directory.');
            }

            $name = $backupId.'-'.bin2hex(random_bytes(4));
            $staging = $restoreRoot.'/.partial-'.$name;
            $destination = $restoreRoot.'/'.$name;

            if (! mkdir($staging, 0700)) {
                throw new RuntimeException('Cannot create restore staging.');
            }

            $files = array_keys($manifest['files']);
            $files[] = 'manifest.json';

            foreach ($files as $relative) {
                $target = $staging.'/'.$relative;
                $parent = dirname($target);

                if (! is_dir($parent) && ! mkdir($parent, 0700, true)) {
                    throw new RuntimeException('Cannot create restore subdirectory.');
                }

                if (! copy($source.'/'.$relative, $target)) {
                    throw new RuntimeException('Cannot restore: '.$relative);
                }
            }

            // Verify the actual restored copy, not only the source backup.
            $verifier->check($staging, $backupId);

            if (! rename($staging, $destination)) {
                throw new RuntimeException('Cannot publish verified restore.');
            }

            $this->info('Restore verified: '.$destination);
            $this->line('SQLite integrity: OK');
            $this->line('Foreign keys: OK');
            $this->line('File checksums: OK');
            $this->line('Referenced proofs: '.$manifest['proof_count']);
            $this->line('Live database and storage were not changed.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            $this->warn('Any .partial restore is incomplete.');

            return self::FAILURE;
        } finally {
            if (is_resource($lock)) {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
    }
}
