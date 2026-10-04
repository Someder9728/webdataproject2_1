<?php

use App\Support\VerifyDormBackup;
use Carbon\CarbonImmutable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

pest()->extend(TestCase::class);

function completedDormTestBackups(string $root): array
{
    $names = [];

    foreach (glob($root.'/*', GLOB_ONLYDIR) ?: [] as $directory) {
        $name = basename($directory);

        if (preg_match('/\A\d{8}T\d{6}Z-[a-f0-9]{8}\z/', $name)) {
            $names[] = $name;
        }
    }

    sort($names);

    return $names;
}

beforeEach(function () {
    $directory = sys_get_temp_dir()
        .DIRECTORY_SEPARATOR
        .'dorm-backup-test-'
        .bin2hex(random_bytes(8));

    mkdir($directory, 0700);

    $this->testRoot = realpath($directory);
    $this->originalStoragePath = storage_path();
    $this->originalConnection = config('database.default');

    $this->app->useStoragePath($this->testRoot.'/storage');

    $database = $this->testRoot.'/live.sqlite';
    touch($database);

    config([
        'database.default' => 'backup_test',
        'database.connections.backup_test' => [
            'driver' => 'sqlite',
            'url' => null,
            'database' => $database,
            'prefix' => '',
            'foreign_key_constraints' => true,
            'busy_timeout' => 5000,
        ],
        'backup.directory' => $this->testRoot.'/backups',
        'backup.keep' => 7,
        'filesystems.disks.payment_proofs' => [
            'driver' => 'local',
            'root' => $this->testRoot.'/proofs',
            'visibility' => 'private',
            'serve' => false,
            'throw' => true,
        ],
    ]);

    DB::purge('backup_test');
    Storage::forgetDisk('payment_proofs');

    // Fixture เฉพาะโครงสร้างที่เครื่องมือ backup ใช้อ่าน
    DB::statement('
        CREATE TABLE payments (
            p_id INTEGER PRIMARY KEY,
            p_proof TEXT
        )
    ');

    DB::statement('
        CREATE TABLE payment_events (
            pe_id INTEGER PRIMARY KEY,
            payments_p_id INTEGER NOT NULL,
            proof_path TEXT,
            FOREIGN KEY (payments_p_id) REFERENCES payments(p_id)
        )
    ');

    DB::table('payments')->insert([
        'p_id' => 1,
        'p_proof' => 'invoices/1/current.pdf',
    ]);

    DB::table('payment_events')->insert([
        'pe_id' => 1,
        'payments_p_id' => 1,
        'proof_path' => 'invoices/1/old.pdf',
    ]);

    Storage::disk('payment_proofs')->put(
        'invoices/1/current.pdf',
        "%PDF-1.4\nCurrent proof\n"
    );

    Storage::disk('payment_proofs')->put(
        'invoices/1/old.pdf',
        "%PDF-1.4\nHistorical proof\n"
    );

    Storage::disk('payment_proofs')->put(
        'unused.pdf',
        'Unreferenced file'
    );

    $this->backupRoot = config('backup.directory');

    $this->createBackup = function (): string {
        $before = completedDormTestBackups($this->backupRoot);

        $status = Artisan::call('dorm:backup');

        expect($status)->toBe(0, Artisan::output());

        $new = array_values(array_diff(
            completedDormTestBackups($this->backupRoot),
            $before
        ));

        expect($new)->toHaveCount(1);

        return $new[0];
    };
});

afterEach(function () {
    DB::purge('backup_test');
    config(['database.default' => $this->originalConnection]);

    Storage::forgetDisk('payment_proofs');

    $this->app->useStoragePath($this->originalStoragePath);
    $this->travelBack();

    $root = realpath($this->testRoot);

    if (
        $root !== false
        && dirname($root) === realpath(sys_get_temp_dir())
        && str_starts_with(basename($root), 'dorm-backup-test-')
    ) {
        (new Filesystem)->deleteDirectory($root);
    }
});

test('backup and isolated restore preserve database and historical proofs', function () {
    $id = ($this->createBackup)();

    $manifest = app(VerifyDormBackup::class)->check(
        $this->backupRoot.'/'.$id,
        $id
    );

    expect($manifest['proof_count'])->toBe(2);
    expect(array_keys($manifest['files']))->toBe([
        'database.sqlite',
        'proofs/invoices/1/current.pdf',
        'proofs/invoices/1/old.pdf',
    ]);

    $liveBefore = DB::table('payments')->get()->toJson();

    $status = Artisan::call('dorm:restore-check', ['backup' => $id]);

    expect($status)->toBe(0, Artisan::output());

    $restores = glob(
        storage_path('backup-restores').'/'.$id.'-*',
        GLOB_ONLYDIR
    );

    expect($restores)->toHaveCount(1);

    app(VerifyDormBackup::class)->check($restores[0], $id);

    expect(file_get_contents(
        $restores[0].'/proofs/invoices/1/old.pdf'
    ))->toBe("%PDF-1.4\nHistorical proof\n");

    expect(DB::table('payments')->get()->toJson())->toBe($liveBefore);

    Storage::disk('payment_proofs')->assertExists('unused.pdf');

    $restored = new PDO('sqlite:'.$restores[0].'/database.sqlite');
    $restored->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    expect((int) $restored->query(
        'SELECT COUNT(*) FROM payment_events'
    )->fetchColumn())->toBe(1);

    $restored = null;
});

test('dry run preserves backups and pruning keeps latest seven', function () {
    $ids = [];

    for ($day = 1; $day <= 8; $day++) {
        $this->travelTo(
            CarbonImmutable::create(
                2026, 10, $day, 2, 0, 0, 'Asia/Bangkok'
            )
        );

        $ids[] = ($this->createBackup)();
    }

    $partial = $this->backupRoot.'/.partial-unfinished';
    mkdir($partial);
    file_put_contents($partial.'/sentinel.txt', 'Keep incomplete backup');

    expect(Artisan::call('dorm:backup-prune', ['--dry-run' => true]))
        ->toBe(0, Artisan::output());

    expect(completedDormTestBackups($this->backupRoot))->toHaveCount(8);

    expect(Artisan::call('dorm:backup-prune'))
        ->toBe(0, Artisan::output());

    expect(completedDormTestBackups($this->backupRoot))
        ->toBe(array_slice($ids, 1));

    expect(is_file($partial.'/sentinel.txt'))->toBeTrue();

    foreach (array_slice($ids, 1) as $id) {
        app(VerifyDormBackup::class)->check(
            $this->backupRoot.'/'.$id,
            $id
        );
    }
});

test('corrupted proof prevents restore and pruning', function () {
    $ids = [];

    for ($day = 1; $day <= 8; $day++) {
        $this->travelTo(
            CarbonImmutable::create(
                2026, 10, $day, 2, 0, 0, 'Asia/Bangkok'
            )
        );

        $ids[] = ($this->createBackup)();
    }

    $latest = $ids[7];

    file_put_contents(
        $this->backupRoot.'/'.$latest.'/proofs/invoices/1/old.pdf',
        'Corrupted backup'
    );

    expect(Artisan::call('dorm:restore-check', ['backup' => $latest]))
        ->toBe(1);

    expect(Artisan::call('dorm:backup-prune'))->toBe(1);

    // ต้องไม่ลบชุดเก่าก่อนพบว่าชุดล่าสุดเสีย
    expect(completedDormTestBackups($this->backupRoot))->toBe($ids);
});

test('failed daily backup does not prune existing sets', function () {
    $id = ($this->createBackup)();

    Storage::disk('payment_proofs')->delete('invoices/1/old.pdf');

    expect(Artisan::call('dorm:backup-daily'))->toBe(1);

    expect(completedDormTestBackups($this->backupRoot))->toBe([$id]);

    app(VerifyDormBackup::class)->check(
        $this->backupRoot.'/'.$id,
        $id
    );
});

test('restore rejects path traversal in manifest', function () {
    $id = ($this->createBackup)();
    $path = $this->backupRoot.'/'.$id.'/manifest.json';

    $manifest = json_decode(
        file_get_contents($path),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    $manifest['files']['proofs/../../outside.txt'] = [
        'sha256' => str_repeat('a', 64),
        'bytes' => 1,
    ];

    file_put_contents($path, json_encode($manifest, JSON_THROW_ON_ERROR));

    expect(Artisan::call('dorm:restore-check', ['backup' => $id]))
        ->toBe(1);

    expect(is_dir(storage_path('backup-restores')))->toBeFalse();
});

test('backup lock prevents overlapping operations', function () {
    $id = ($this->createBackup)();

    $lock = fopen($this->backupRoot.'/backup.lock', 'c');
    expect(flock($lock, LOCK_EX | LOCK_NB))->toBeTrue();

    try {
        expect(Artisan::call('dorm:backup'))->toBe(1);
        expect(Artisan::call('dorm:backup-prune'))->toBe(1);
        expect(Artisan::call('dorm:restore-check', ['backup' => $id]))
            ->toBe(1);
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }

    expect(completedDormTestBackups($this->backupRoot))->toBe([$id]);
});
