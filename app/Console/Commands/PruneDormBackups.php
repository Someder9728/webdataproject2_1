<?php

namespace App\Console\Commands;

use App\Support\VerifyDormBackup;
use FilesystemIterator;
use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;

class PruneDormBackups extends Command
{
    protected $signature = 'dorm:backup-prune {--dry-run}';

    protected $description = 'Keep the latest verified backup sets';

    public function handle(VerifyDormBackup $verifier): int
    {
        $lock = null;

        try {
            $keep = filter_var(
                config('backup.keep'),
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($keep === false) {
                throw new RuntimeException('backup.keep must be at least 1.');
            }

            $root = realpath((string) config('backup.directory'));

            if ($root === false || ! is_dir($root)) {
                throw new RuntimeException('Backup directory not found.');
            }

            $lock = fopen($root.'/backup.lock', 'c');

            if ($lock === false || ! flock($lock, LOCK_EX | LOCK_NB)) {
                throw new RuntimeException('Another backup operation is running.');
            }

            $backups = [];

            foreach (new FilesystemIterator($root) as $entry) {
                $name = $entry->getFilename();

                if (! preg_match('/\A\d{8}T\d{6}Z-[a-f0-9]{8}\z/', $name)) {
                    continue;
                }

                $directory = $entry->getRealPath();

                if (
                    $entry->isLink()
                    || ! $entry->isDir()
                    || $directory === false
                    || dirname($directory) !== $root
                ) {
                    throw new RuntimeException('Unsafe backup directory: '.$name);
                }

                $manifest = $verifier->check($directory, $name);

                // Validate every deletion target before deleting any backup.
                $allowed = array_fill_keys(
                    [...array_keys($manifest['files']), 'manifest.json'],
                    true
                );

                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(
                        $directory,
                        FilesystemIterator::SKIP_DOTS
                    ),
                    RecursiveIteratorIterator::CHILD_FIRST
                );

                $targets = [];

                foreach ($iterator as $child) {
                    $resolved = $child->getRealPath();

                    if (
                        $child->isLink()
                        || $resolved === false
                        || ! str_starts_with(
                            $resolved,
                            $directory.DIRECTORY_SEPARATOR
                        )
                    ) {
                        throw new RuntimeException('Unsafe file in backup: '.$name);
                    }

                    if (! $child->isDir()) {
                        $relative = str_replace(
                            DIRECTORY_SEPARATOR,
                            '/',
                            substr($resolved, strlen($directory) + 1)
                        );

                        if (! $child->isFile() || ! isset($allowed[$relative])) {
                            throw new RuntimeException(
                                'Unexpected file in backup: '.$relative
                            );
                        }
                    }

                    $targets[] = [
                        'path' => $resolved,
                        'directory' => $child->isDir(),
                    ];
                }

                $backups[$name] = [
                    'directory' => $directory,
                    'targets' => $targets,
                ];
            }

            // Names start with the UTC creation timestamp.
            krsort($backups, SORT_STRING);
            $expired = array_slice($backups, $keep, null, true);

            $this->line('Verified backups: '.count($backups));
            $this->line('Retention: '.$keep.' latest sets');

            foreach ($expired as $name => $backup) {
                if ($this->option('dry-run')) {
                    $this->line('Would remove: '.$name);
                    continue;
                }

                foreach ($backup['targets'] as $target) {
                    $removed = $target['directory']
                        ? rmdir($target['path'])
                        : unlink($target['path']);

                    if (! $removed) {
                        throw new RuntimeException(
                            'Cannot remove: '.$target['path']
                        );
                    }
                }

                if (! rmdir($backup['directory'])) {
                    throw new RuntimeException('Cannot remove backup: '.$name);
                }

                $this->line('Removed: '.$name);
            }

            $this->info(
                $this->option('dry-run')
                    ? 'Dry run completed. No backups were deleted.'
                    : 'Backup retention completed.'
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            if (is_resource($lock)) {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
    }
}