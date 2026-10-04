<?php

declare(strict_types=1);

use App\Actions\Billing\IssueInvoice;
use App\Actions\Billing\UpdateInvoice;
use App\Actions\Rentals\MoveOutRental;
use App\Models\AuditEvent;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Process\Process;

$root = dirname(__DIR__, 2);

require $root.'/vendor/autoload.php';

function moveRaceCheck(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function moveRaceWait(string $path): void
{
    $deadline = microtime(true) + 20;

    while (true) {
        clearstatcache(true, $path);

        if (is_file($path)) {
            return;
        }

        moveRaceCheck(
            microtime(true) < $deadline,
            'Synchronization timeout: '.basename($path)
        );

        usleep(10_000);
    }
}

function bootMoveRace(string $root, string $directory): void
{
    $database = $directory.'/test.sqlite';

    moveRaceCheck(is_file($database), 'Temporary database missing.');

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
        'dormitory.water_rate' => '18.00',
        'dormitory.elec_rate' => '7.00',
    ]);

    DB::purge('sqlite');

    moveRaceCheck(
        DB::connection()->getDatabaseName() === $database,
        'Unexpected database connection.'
    );

    $now = CarbonImmutable::parse(
        '2026-10-05 12:00:00',
        'Asia/Bangkok'
    );

    Carbon::setTestNow($now);
    CarbonImmutable::setTestNow($now);
}

if (($argv[1] ?? '') === 'worker') {
    try {
        moveRaceCheck(count($argv) === 4, 'Invalid worker arguments.');

        [, , $directory, $label] = $argv;

        $directory = realpath($directory);

        moveRaceCheck(
            $directory !== false
                && dirname($directory) === realpath(sys_get_temp_dir())
                && str_starts_with(
                    basename($directory),
                    'dorm-move-out-race-'
                ),
            'Invalid temporary directory.'
        );

        moveRaceCheck(
            in_array($label, ['a', 'b'], true),
            'Invalid worker label.'
        );

        $settings = json_decode(
            file_get_contents($directory.'/settings.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        bootMoveRace($root, $directory);

        $operation = $settings['operations'][$label];
        $firstWriter = $settings['first_writer'];

        $actor = User::findOrFail($settings['admin_id']);
        $rental = Rental::findOrFail($settings['rental_id']);

        DB::connection()->beforeStartingTransaction(
            function () use ($directory, $label, $firstWriter): void {
                touch($directory.'/ready-'.$label);
                moveRaceWait($directory.'/go');

                if ($label !== $firstWriter) {
                    moveRaceWait($directory.'/first-writing');
                    touch($directory.'/second-starting');
                }
            }
        );

        $heldFirstWrite = false;

        Event::listen(
            'eloquent.creating: '.AuditEvent::class,
            function () use (
                $directory,
                $label,
                $firstWriter,
                &$heldFirstWrite
            ): void {
                if ($label !== $firstWriter || $heldFirstWrite) {
                    return;
                }

                // หน่วงครั้งเดียวต่อ worker ไม่ใช่ทุก Audit
                $heldFirstWrite = true;

                touch($directory.'/first-writing');
                moveRaceWait($directory.'/second-starting');

                usleep(500_000);
            }
        );

        $started = microtime(true);
        $status = 200;

        try {
            if ($operation === 'move-out') {
                app(MoveOutRental::class)->handle(
                    $actor,
                    $rental,
                    [
                        'rt_moveout' => '2026-09-20',
                        'reason' => 'Concurrent move-out test',
                    ]
                );
            } elseif ($operation === 'issue') {
                app(IssueInvoice::class)->handle(
                    $actor,
                    [
                        'rentals_rt_id' => $rental->getKey(),
                        'period_start' => '2026-09-01',
                        'period_end' => '2026-10-01',
                    ],
                    persist: true
                );
            } elseif ($operation === 'edit') {
                app(UpdateInvoice::class)->handle(
                    $actor,
                    Invoice::findOrFail($settings['invoice_id']),
                    [
                        'period_end' => '2026-10-01',
                        'reason' => 'Concurrent invoice extension',
                    ]
                );
            } else {
                throw new RuntimeException('Unknown operation.');
            }
        } catch (ValidationException $exception) {
            $status = 422;
        } catch (HttpExceptionInterface $exception) {
            $status = $exception->getStatusCode();
        }

        echo json_encode([
            'label' => $label,
            'operation' => $operation,
            'status' => $status,
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
    ['move-out vs move-out', 'move-out', 'a'],
    ['move-out before issue', 'issue', 'a'],
    ['issue before move-out', 'issue', 'b'],
    ['move-out before edit', 'edit', 'a'],
    ['edit before move-out', 'edit', 'b'],
];

$failed = false;

foreach ($cases as [$caseName, $otherOperation, $firstWriter]) {
    $directory = sys_get_temp_dir()
        .DIRECTORY_SEPARATOR
        .'dorm-move-out-race-'.bin2hex(random_bytes(8));

    moveRaceCheck(
        mkdir($directory, 0700),
        'Cannot create temporary directory.'
    );

    $directory = realpath($directory);

    moveRaceCheck($directory !== false, 'Cannot resolve directory.');
    moveRaceCheck(
        touch($directory.'/test.sqlite'),
        'Cannot create temporary database.'
    );

    $workers = [];

    try {
        bootMoveRace($root, $directory);

        $migrationStatus = Artisan::call('migrate', [
            '--database' => 'sqlite',
            '--force' => true,
        ]);

        moveRaceCheck($migrationStatus === 0, Artisan::output());

        $admin = User::factory()->create([
            'u_role' => 'admin',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $tenant = Tenant::create([
            't_Fname' => 'Move',
            't_Lname' => 'Race',
            't_tel' => '0812345678',
        ]);

        $room = Room::create([
            'r_name' => 'OUT-RACE-101',
            'r_floor' => 1,
            'r_type' => 'Test',
            'r_rent' => '3000.00',
            'r_status' => 'OCCUPIED',
        ]);

        $rental = Rental::create([
            'tenants_t_id' => $tenant->getKey(),
            'rooms_r_id' => $room->getKey(),
            'rt_movein' => '2026-09-01',
            'rt_moveout' => null,
            'rt_status' => 'ACTIVE',
        ]);

        $contract = $rental->contract()->create([
            'c_number' => 'OUT-RACE-CONTRACT',
            'c_start' => '2026-09-01',
            'c_end' => '2027-08-31',
            'c_rent' => '3000.00',
            'c_deposit' => '6000.00',
            'c_status' => 'ACTIVE',
        ]);

        foreach ([
            ['2026-09-01', '100.00', '1000.00'],
            ['2026-09-16', '104.00', '1020.00'],
            ['2026-09-20', '106.00', '1030.00'],
            ['2026-10-01', '108.00', '1050.00'],
        ] as [$date, $water, $electricity]) {
            Meter::create([
                'rooms_r_id' => $room->getKey(),
                'm_date' => $date,
                'm_water' => $water,
                'm_elec' => $electricity,
            ]);
        }

        $invoiceId = null;

        if ($otherOperation === 'edit') {
            $initial = app(IssueInvoice::class)->handle(
                $admin,
                [
                    'rentals_rt_id' => $rental->getKey(),
                    'period_start' => '2026-09-01',
                    'period_end' => '2026-09-16',
                ],
                persist: true
            );

            $invoiceId = $initial['i_id'];
        }

        $baselineAudits = AuditEvent::count();

        moveRaceCheck(
            file_put_contents(
                $directory.'/settings.json',
                json_encode([
                    'admin_id' => $admin->getKey(),
                    'rental_id' => $rental->getKey(),
                    'invoice_id' => $invoiceId,
                    'operations' => [
                        'a' => 'move-out',
                        'b' => $otherOperation,
                    ],
                    'first_writer' => $firstWriter,
                ], JSON_THROW_ON_ERROR)
            ) !== false,
            'Cannot write worker settings.'
        );

        DB::disconnect('sqlite');

        foreach (['a', 'b'] as $label) {
            $process = new Process([
                PHP_BINARY,
                __FILE__,
                'worker',
                $directory,
                $label,
            ], $root);

            $process->setTimeout(45);
            $process->start();

            $workers[$label] = $process;
        }

        $deadline = microtime(true) + 20;

        while (true) {
            clearstatcache();

            foreach ($workers as $label => $process) {
                if (! $process->isRunning()) {
                    throw new RuntimeException(
                        'Worker '.$label.' exited before start: '
                        .$process->getErrorOutput().' '.$process->getOutput()
                    );
                }

                $process->checkTimeout();
            }

            if (
                is_file($directory.'/ready-a')
                && is_file($directory.'/ready-b')
            ) {
                break;
            }

            moveRaceCheck(
                microtime(true) < $deadline,
                'Workers did not become ready.'
            );

            usleep(10_000);
        }

        touch($directory.'/go');

        $results = [];

        foreach ($workers as $label => $process) {
            $exitCode = $process->wait();

            moveRaceCheck(
                $exitCode === 0,
                $process->getErrorOutput().' '.$process->getOutput()
            );

            $results[$label] = json_decode(
                $process->getOutput(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        }

        $moveOutWon = $firstWriter === 'a';

        $expectedA = $moveOutWon ? 200 : 409;
        $expectedB = $moveOutWon
            ? ($otherOperation === 'move-out' ? 409 : 422)
            : 200;

        moveRaceCheck(
            $results['a']['status'] === $expectedA
                && $results['b']['status'] === $expectedB,
            'Unexpected outcomes: '.json_encode($results)
        );

        moveRaceCheck(
            max(array_column($results, 'started'))
                < min(array_column($results, 'finished')),
            'Worker executions did not overlap.'
        );

        $rental = $rental->fresh();
        $room = $room->fresh();
        $contract = $contract->fresh();

        moveRaceCheck(
            $rental->rt_status === ($moveOutWon ? 'ENDED' : 'ACTIVE')
                && $rental->rt_moveout?->toDateString()
                    === ($moveOutWon ? '2026-09-20' : null)
                && $room->r_status === ($moveOutWon ? 'VACANT' : 'OCCUPIED')
                && $contract->c_status === ($moveOutWon ? 'ENDED' : 'ACTIVE')
                && $contract->c_end->toDateString() === '2027-08-31',
            'Rental, room or contract state mismatch.'
        );

        $expectedBillCount = $moveOutWon && $otherOperation === 'edit'
            ? 2
            : 1;

        moveRaceCheck(
            Invoice::count() === $expectedBillCount
                && Payment::count() === $expectedBillCount
                && PaymentEvent::count() === 0,
            'Unexpected invoice, payment or payment-event count.'
        );

        $bills = Invoice::with('payment')->orderBy('period_start')->get();
        $cursor = '2026-09-01';
        $total = BigDecimal::of('0.00');

        foreach ($bills as $bill) {
            moveRaceCheck(
                $bill->period_start->toDateString() === $cursor,
                'Invoice coverage has a gap or overlap.'
            );

            $cursor = $bill->period_end->toDateString();

            moveRaceCheck(
                $bill->payment !== null
                    && $bill->payment->p_status === 'UNPAID'
                    && $bill->payment->p_amount === $bill->i_total,
                'Invoice and payment mismatch.'
            );

            $total = $total->plus($bill->i_total);
        }

        moveRaceCheck(
            $cursor === ($moveOutWon ? '2026-09-20' : '2026-10-01'),
            'Unexpected final billing boundary.'
        );

        moveRaceCheck(
            $total->isEqualTo($moveOutWon ? '2218.00' : '3494.00'),
            'Unexpected billing total: '.$total
        );

        moveRaceCheck(
            AuditEvent::count() === $baselineAudits + ($moveOutWon ? 4 : 1),
            'Unexpected audit count.'
        );

        moveRaceCheck(
            AuditEvent::where('action', 'rental_moved_out')->count()
                === ($moveOutWon ? 1 : 0)
                && AuditEvent::where('action', 'contract_ended')->count()
                    === ($moveOutWon ? 1 : 0),
            'Unexpected move-out audit records.'
        );

        if ($moveOutWon) {
            $audit = AuditEvent::where('action', 'rental_moved_out')->sole();

            moveRaceCheck(
                (int) $audit->actor_user_id === (int) $admin->getKey()
                    && (int) $audit->entity_id === (int) $rental->getKey()
                    && $audit->new_values['rt_moveout'] === '2026-09-20'
                    && count($audit->new_values['created_invoice_ids']) === 1,
                'Move-out audit snapshot mismatch.'
            );
        }

        echo 'PASS: '.$caseName
            .' - move-out '.$results['a']['status']
            .', '.$otherOperation.' '.$results['b']['status']
            .'; rental/bills/payments/audits consistent'
            .PHP_EOL;
    } catch (Throwable $exception) {
        $failed = true;

        fwrite(
            STDERR,
            'FAIL: '.$caseName.' - '
                .get_class($exception).': '
                .$exception->getMessage().PHP_EOL
        );
    } finally {
        foreach ($workers as $process) {
            if ($process->isRunning()) {
                $process->stop(1);
            }
        }

        DB::disconnect('sqlite');
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();

        $safeRoot = realpath($directory);

        moveRaceCheck(
            $safeRoot !== false
                && dirname($safeRoot) === realpath(sys_get_temp_dir())
                && str_starts_with(
                    basename($safeRoot),
                    'dorm-move-out-race-'
                ),
            'Unsafe cleanup directory.'
        );

        foreach ([
            'test.sqlite',
            'test.sqlite-journal',
            'test.sqlite-wal',
            'test.sqlite-shm',
            'settings.json',
            'ready-a',
            'ready-b',
            'go',
            'first-writing',
            'second-starting',
        ] as $name) {
            $path = $safeRoot.DIRECTORY_SEPARATOR.$name;

            if (is_link($path)) {
                throw new RuntimeException('Unsafe cleanup link.');
            }

            if (is_file($path)) {
                unlink($path);
            }
        }

        rmdir($safeRoot);
    }
}

exit($failed ? 1 : 0);
