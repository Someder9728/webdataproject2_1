<?php

declare(strict_types=1);

use App\Actions\Meters\RecordMeterReading;
use App\Models\AuditEvent;
use App\Models\Meter;
use App\Models\Room;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Process\Process;

$root = dirname(__DIR__, 2);

require $root.'/vendor/autoload.php';

function meterCheck(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function meterWait(string $path): void
{
    $deadline = microtime(true) + 20;

    while (true) {
        clearstatcache(true, $path);

        if (is_file($path)) {
            return;
        }

        meterCheck(
            microtime(true) < $deadline,
            'Synchronization timeout: '.basename($path)
        );

        usleep(10_000);
    }
}

function bootMeterRace(string $root, string $directory): void
{
    $database = $directory.'/test.sqlite';

    meterCheck(is_file($database), 'Temporary database missing.');

    $app = require $root.'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();

    config([
        'app.env' => 'testing',
        'database.default' => 'sqlite',
        'database.connections.sqlite.url' => null,
        'database.connections.sqlite.database' => $database,
        'database.connections.sqlite.foreign_key_constraints' => true,
        'database.connections.sqlite.busy_timeout' => 5000,
        'cache.default' => 'array',
        'session.driver' => 'array',
        'queue.default' => 'sync',
    ]);

    DB::purge('sqlite');

    meterCheck(
        DB::connection()->getDatabaseName() === $database,
        'Unexpected database connection.'
    );
}

if (($argv[1] ?? '') === 'worker') {
    try {
        meterCheck(count($argv) === 10, 'Invalid worker arguments.');

        [
            , , $directory, $label, $actorId, $roomId,
            $date, $water, $electricity, $unused,
        ] = $argv;

        $directory = realpath($directory);
        $temporaryRoot = realpath(sys_get_temp_dir());

        meterCheck(
            $directory !== false &&
            $temporaryRoot !== false &&
            dirname($directory) === $temporaryRoot &&
            str_starts_with(basename($directory), 'dorm-meter-race-'),
            'Invalid temporary directory.'
        );

        meterCheck(
            in_array($label, ['a', 'b'], true),
            'Invalid worker label.'
        );

        bootMeterRace($root, $directory);

        $actor = User::findOrFail((int) $actorId);
        $room = Room::findOrFail((int) $roomId);

        // Hold the successful write briefly to exercise contention.
        Event::listen(
            'eloquent.creating: '.AuditEvent::class,
            function (): void {
                usleep(500_000);
            }
        );

        touch($directory.'/ready-'.$label);
        meterWait($directory.'/go');

        $started = microtime(true);
        $status = 201;
        $meterId = null;

        try {
            $meter = app(RecordMeterReading::class)->handle(
                $actor,
                $room,
                [
                    'm_date' => $date,
                    'm_water' => $water,
                    'm_elec' => $electricity,
                ]
            );

            $meterId = $meter->getKey();
        } catch (ValidationException $exception) {
            $status = 422;
        } catch (HttpExceptionInterface $exception) {
            $status = $exception->getStatusCode();
        }

        echo json_encode([
            'status' => $status,
            'meter_id' => $meterId,
            'started' => $started,
            'finished' => microtime(true),
        ], JSON_THROW_ON_ERROR);

        exit(0);
    } catch (Throwable $exception) {
        fwrite(
            STDERR,
            get_class($exception).': '.$exception->getMessage().PHP_EOL
        );

        exit(1);
    }
}

$cases = [
    'same date' => [
        'a' => ['2026-09-15', '110.00', '220.00'],
        'b' => ['2026-09-15', '110.00', '220.00'],
        'statuses' => [201, 409],
    ],
    'different dates with reversed readings' => [
        'a' => ['2026-09-15', '115.00', '230.00'],
        'b' => ['2026-09-16', '110.00', '220.00'],
        'statuses' => [201, 422],
    ],
    'different dates with valid readings' => [
        'a' => ['2026-09-15', '110.00', '220.00'],
        'b' => ['2026-09-16', '115.00', '230.00'],
        'statuses' => [201, 201],
    ],
];

$failed = false;

foreach ($cases as $name => $case) {
    $directory = sys_get_temp_dir()
        .DIRECTORY_SEPARATOR
        .'dorm-meter-race-'.bin2hex(random_bytes(8));

    meterCheck(mkdir($directory, 0700), 'Cannot create temporary directory.');
    $directory = realpath($directory);

    touch($directory.'/test.sqlite');
    $workers = [];

    try {
        bootMeterRace($root, $directory);

        meterCheck(
            Artisan::call('migrate', [
                '--database' => 'sqlite',
                '--force' => true,
            ]) === 0,
            'Migration failed: '.Artisan::output()
        );

        $admin = User::factory()->create([
            'u_role' => 'admin',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $room = Room::create([
            'r_name' => 'RACE-101',
            'r_floor' => 1,
            'r_type' => 'Test',
            'r_rent' => '3000.00',
            'r_status' => 'VACANT',
        ]);

        foreach ([
            ['2026-09-01', '100.00', '200.00'],
            ['2026-10-01', '120.00', '240.00'],
        ] as [$date, $water, $electricity]) {
            Meter::create([
                'rooms_r_id' => $room->getKey(),
                'm_date' => $date,
                'm_water' => $water,
                'm_elec' => $electricity,
            ]);
        }

        $baselineAudits = AuditEvent::count();

        DB::disconnect('sqlite');

        foreach (['a', 'b'] as $label) {
            [$date, $water, $electricity] = $case[$label];

            $process = new Process([
                PHP_BINARY,
                __FILE__,
                'worker',
                $directory,
                $label,
                (string) $admin->getKey(),
                (string) $room->getKey(),
                $date,
                $water,
                $electricity,
                'meter-race',
            ], $root);

            $process->setTimeout(30);
            $process->start();

            $workers[] = $process;
        }

        meterWait($directory.'/ready-a');
        meterWait($directory.'/ready-b');
        touch($directory.'/go');

        $results = [];

        foreach ($workers as $process) {
            meterCheck(
                $process->wait() === 0,
                $process->getErrorOutput().' '.$process->getOutput()
            );

            $results[] = json_decode(
                $process->getOutput(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        }

        $statuses = array_column($results, 'status');
        sort($statuses);

        meterCheck(
            $statuses === $case['statuses'],
            'Unexpected statuses: '.json_encode($statuses)
        );

        meterCheck(
            max(array_column($results, 'started'))
                < min(array_column($results, 'finished')),
            'Workers did not overlap; rerun this test.'
        );

        $successfulIds = array_values(array_filter(
            array_column($results, 'meter_id'),
            fn ($id) => $id !== null
        ));

        meterCheck(
            Meter::count() === 2 + count($successfulIds),
            'Unexpected meter count.'
        );

        meterCheck(
            AuditEvent::count() === $baselineAudits + count($successfulIds),
            'Unexpected audit count.'
        );

        foreach ($successfulIds as $meterId) {
            $meter = Meter::findOrFail($meterId);

            $audit = AuditEvent::query()
                ->where('entity_type', 'meters')
                ->where('entity_id', $meterId)
                ->where('action', 'meter_reading_recorded')
                ->sole();

            meterCheck(
                (int) $audit->actor_user_id === (int) $admin->getKey(),
                'Unexpected audit actor.'
            );

            meterCheck(
                $audit->new_values['m_date'] === $meter->m_date->toDateString() &&
                $audit->new_values['m_water'] === $meter->m_water &&
                $audit->new_values['m_elec'] === $meter->m_elec,
                'Audit snapshot does not match meter.'
            );
        }

        $readings = Meter::orderBy('m_date')->get();

        foreach ($readings as $index => $reading) {
            if ($index === 0) {
                continue;
            }

            foreach (['m_water', 'm_elec'] as $field) {
                meterCheck(
                    BigDecimal::of($reading->{$field})
                        ->isGreaterThanOrEqualTo(
                            $readings[$index - 1]->{$field}
                        ),
                    'Committed readings are not monotonic.'
                );
            }
        }

        echo 'PASS: '.$name
            .' — outcomes '.implode('/', $statuses)
            .', meters and audits consistent'.PHP_EOL;
    } catch (Throwable $exception) {
        $failed = true;
        fwrite(STDERR, 'FAIL: '.$name.' — '.$exception->getMessage().PHP_EOL);
    } finally {
        foreach ($workers as $process) {
            if ($process->isRunning()) {
                $process->stop(1);
            }
        }

        DB::disconnect('sqlite');

        // Remove only known files inside this run's temporary directory.
        foreach ([
            'test.sqlite',
            'test.sqlite-journal',
            'test.sqlite-wal',
            'test.sqlite-shm',
            'ready-a',
            'ready-b',
            'go',
        ] as $file) {
            $path = $directory.'/'.$file;

            if (is_file($path)) {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}

exit($failed ? 1 : 0);
