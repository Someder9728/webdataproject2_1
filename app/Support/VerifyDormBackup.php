<?php

namespace App\Support;

use PDO;
use RuntimeException;

class VerifyDormBackup
{
    public function check(string $directory, string $backupId): array
    {
        $root = realpath($directory);

        if ($root === false || ! is_dir($root)) {
            throw new RuntimeException('Backup directory not found.');
        }

        $manifestPath = $root.'/manifest.json';

        if (! is_file($manifestPath) || is_link($manifestPath)) {
            throw new RuntimeException('Backup manifest not found or invalid.');
        }

        $manifest = json_decode(
            file_get_contents($manifestPath),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (
            ! is_array($manifest)
            || ($manifest['format_version'] ?? null) !== 1
            || ($manifest['backup_id'] ?? null) !== $backupId
            || ! is_array($manifest['files'] ?? null)
            || ! isset($manifest['files']['database.sqlite'])
            || ! is_int($manifest['proof_count'] ?? null)
        ) {
            throw new RuntimeException('Invalid backup manifest.');
        }

        foreach ($manifest['files'] as $relative => $metadata) {
            if (
                ! is_string($relative)
                || (
                    $relative !== 'database.sqlite'
                    && ! str_starts_with($relative, 'proofs/')
                )
                || str_contains($relative, '\\')
                || str_contains($relative, ':')
                || str_contains($relative, "\0")
                || preg_match('~(^|/)\.\.?(/|$)~', $relative)
                || ! is_array($metadata)
                || ! is_string($metadata['sha256'] ?? null)
                || ! preg_match('/\A[a-f0-9]{64}\z/', $metadata['sha256'])
                || ! is_int($metadata['bytes'] ?? null)
                || $metadata['bytes'] < 0
            ) {
                throw new RuntimeException('Invalid backup file entry.');
            }

            $file = realpath($root.'/'.$relative);

            if (
                $file === false
                || ! is_file($file)
                || is_link($root.'/'.$relative)
                || ! str_starts_with($file, $root.DIRECTORY_SEPARATOR)
            ) {
                throw new RuntimeException('Missing or unsafe file: '.$relative);
            }

            clearstatcache(true, $file);

            if (
                filesize($file) !== $metadata['bytes']
                || hash_file('sha256', $file) !== $metadata['sha256']
            ) {
                throw new RuntimeException('Checksum mismatch: '.$relative);
            }
        }

        $database = new PDO('sqlite:'.$root.'/database.sqlite');
        $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $database->exec('PRAGMA query_only = ON');

        if (
            $database->query('PRAGMA integrity_check')
                ->fetchAll(PDO::FETCH_COLUMN) !== ['ok']
        ) {
            throw new RuntimeException('SQLite integrity check failed.');
        }

        if ($database->query('PRAGMA foreign_key_check')->fetchAll() !== []) {
            throw new RuntimeException('SQLite foreign key check failed.');
        }

        $references = $database->query("
            SELECT p_proof AS path
            FROM payments
            WHERE p_proof IS NOT NULL AND p_proof <> ''
            UNION
            SELECT proof_path AS path
            FROM payment_events
            WHERE proof_path IS NOT NULL AND proof_path <> ''
            ORDER BY path
        ")->fetchAll(PDO::FETCH_COLUMN);

        $expectedFiles = ['database.sqlite'];

        foreach ($references as $path) {
            $expectedFiles[] = 'proofs/'.$path;
        }

        $recordedFiles = array_keys($manifest['files']);

        sort($expectedFiles);
        sort($recordedFiles);

        if (
            $expectedFiles !== $recordedFiles
            || count($references) !== $manifest['proof_count']
        ) {
            throw new RuntimeException(
                'Manifest does not match database proof references.'
            );
        }

        return $manifest;
    }
}