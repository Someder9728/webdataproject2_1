<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

$root = dirname(__DIR__, 2);

require $root.'/vendor/autoload.php';

function bootSettlementRace(string $root, string $directory): void
{
    $database = $directory.'/test.sqlite';

    if (! is_file($database)) {
        throw new RuntimeException('Temporary database does not exist.');
    }

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

        'filesystems.disks.payment_proofs' => [
            'driver' => 'local',
            'root' => $directory.'/proofs',
            'visibility' => 'private',
            'serve' => false,
            'throw' => true,
        ],
    ]);

    DB::purge('sqlite');
    Storage::forgetDisk('payment_proofs');

    if (DB::connection()->getDatabaseName() !== $database) {
        throw new RuntimeException('Unexpected database connection.');
    }

    $now = CarbonImmutable::parse(
        '2026-10-02 12:00:00',
        'Asia/Bangkok'
    );

    \Illuminate\Support\Carbon::setTestNow($now);
    CarbonImmutable::setTestNow($now);
}

function settlementRaceWait(string $path): void
{
    $deadline = microtime(true) + 20;

    while (true) {
        clearstatcache(true, $path);

        if (is_file($path)) {
            return;
        }

        if (microtime(true) >= $deadline) {
            throw new RuntimeException(
                'Synchronization timeout: '.basename($path)
            );
        }

        usleep(10_000);
    }
}

function settlementRaceCheck(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

if (($argv[1] ?? '') === 'worker') {
    try {
        settlementRaceCheck(count($argv) === 8, 'Invalid worker arguments.');

        [, , $directory, $label, $operation, $actorId, $invoiceId, $eventId] = $argv;

        $directory = realpath($directory);
        $temporaryRoot = realpath(sys_get_temp_dir());

        settlementRaceCheck(
            $directory !== false
            && $temporaryRoot !== false
            && dirname($directory) === $temporaryRoot
            && str_starts_with(
                basename($directory),
                'dorm-settlement-race-'
            ),
            'Invalid temporary directory.'
        );

        settlementRaceCheck(
            in_array($label, ['a', 'b'], true)
            && in_array($operation, ['approve', 'reject', 'walk-in'], true),
            'Invalid worker operation.'
        );

        bootSettlementRace($root, $directory);

        DB::connection()->beforeStartingTransaction(
            function () use ($directory, $label): void {
                touch($directory.'/ready-'.$label);
                settlementRaceWait($directory.'/go');
            }
        );

        \Illuminate\Support\Facades\Event::listen(
            'eloquent.creating: '.\App\Models\AuditEvent::class,
            function (): void {
                usleep(500_000);
            }
        );

        $actor = \App\Models\User::findOrFail((int) $actorId);
        $invoice = \App\Models\Invoice::findOrFail((int) $invoiceId);
        $expectedEventId = $eventId === 'null' ? null : (int) $eventId;

        $started = microtime(true);
        $status = 200;

        try {
            if ($operation === 'walk-in') {
                app(\App\Actions\Payments\RecordWalkInPayment::class)
                    ->handle($actor, $invoice, [
                        'amount' => '3494.00',
                        'payment_date' => '2026-10-02',
                        'method' => 'CASH',
                        'note' => 'Concurrent walk-in test '.$label,
                        'expected_event_id' => $expectedEventId,
                    ]);
            } else {
                app(\App\Actions\Payments\ReviewPaymentProof::class)
                    ->handle($actor, $invoice, [
                        'decision' => $operation,
                        'expected_event_id' => $expectedEventId,
                        'reason' => $operation === 'reject'
                            ? 'Concurrent rejection test'
                            : null,
                    ]);
            }
        } catch (
            \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception
        ) {
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

$failed = false;

$cases = [
    ['approve vs approve', 'PENDING', 'approve', 'approve'],
    ['approve vs reject', 'PENDING', 'approve', 'reject'],
    ['walk-in unpaid', 'UNPAID', 'walk-in', 'walk-in'],
    ['walk-in rejected', 'REJECTED', 'walk-in', 'walk-in'],
];

foreach ($cases as [$caseName, $initialStatus, $operationA, $operationB]) {
    $directory = sys_get_temp_dir()
        .DIRECTORY_SEPARATOR
        .'dorm-settlement-race-'
        .bin2hex(random_bytes(8));

    settlementRaceCheck(mkdir($directory, 0700), 'Cannot create test directory.');

    $directory = realpath($directory);
    settlementRaceCheck($directory !== false, 'Cannot resolve test directory.');
    settlementRaceCheck(touch($directory.'/test.sqlite'), 'Cannot create test database.');

    $workers = [];

    try {
        bootSettlementRace($root, $directory);

        $migrationStatus = \Illuminate\Support\Facades\Artisan::call(
            'migrate',
            ['--database' => 'sqlite', '--force' => true]
        );

        settlementRaceCheck(
            $migrationStatus === 0,
            \Illuminate\Support\Facades\Artisan::output()
        );

        $admins = [];

        foreach (['a', 'b'] as $label) {
            $admins[$label] = \App\Models\User::factory()->create([
                'u_role' => 'admin',
                'is_active' => true,
                'must_change_password' => false,
            ]);
        }

        $tenant = \App\Models\Tenant::create([
            't_Fname' => 'Settlement',
            't_Lname' => 'Race',
            't_tel' => '0812345678',
        ]);

        $owner = \App\Models\User::factory()->create([
            'u_role' => 'tenant',
            'tenants_t_id' => $tenant->getKey(),
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $room = \App\Models\Room::create([
            'r_name' => 'RACE-101',
            'r_floor' => 1,
            'r_type' => 'Test',
            'r_rent' => '3000.00',
            'r_status' => 'VACANT',
        ]);

        $rental = \App\Models\Rental::create([
            'tenants_t_id' => $tenant->getKey(),
            'rooms_r_id' => $room->getKey(),
            'rt_movein' => '2026-09-01',
            'rt_moveout' => '2026-10-01',
            'rt_status' => 'ENDED',
        ]);

        $meters = [];

        foreach ([
            ['2026-09-01', '100.00', '1000.00'],
            ['2026-10-01', '108.00', '1050.00'],
        ] as [$date, $water, $elec]) {
            $meters[] = \App\Models\Meter::create([
                'rooms_r_id' => $room->getKey(),
                'm_date' => $date,
                'm_water' => $water,
                'm_elec' => $elec,
            ]);
        }

        $invoice = \App\Models\Invoice::create([
            'rentals_rt_id' => $rental->getKey(),
            'period_start' => '2026-09-01',
            'period_end' => '2026-10-01',
            'start_meter_id' => $meters[0]->getKey(),
            'end_meter_id' => $meters[1]->getKey(),
            'i_date' => '2026-10-01',
            'i_due' => '2026-10-08',
            'water_usage' => '8.00',
            'elec_usage' => '50.00',
            'water_rate' => '18.00',
            'elec_rate' => '7.00',
            'rent_rate' => '3000.00',
            'i_rent' => '3000.00',
            'i_water' => '144.00',
            'i_elec' => '350.00',
            'i_total' => '3494.00',
        ]);

        $payment = $invoice->payment()->create([
            'p_amount' => '3494.00',
            'p_status' => $initialStatus,
        ]);

        $proofPath = null;
        $proofContents = "%PDF-1.4\nSettlement race fixture\n";
        $expectedEventId = null;

        if ($initialStatus !== 'UNPAID') {
            $proofPath = 'invoices/'.$invoice->getKey().'/original.pdf';

            Storage::disk('payment_proofs')->put($proofPath, $proofContents);

            $payment->update([
                'p_date' => '2026-10-02',
                'p_type' => 'TRANSFER',
                'p_proof' => $proofPath,
                'p_reject_reason' => $initialStatus === 'REJECTED'
                    ? 'Previous rejection'
                    : null,
            ]);

            $submitted = $payment->events()->create([
                'actor_user_id' => $owner->getKey(),
                'event_type' => 'PROOF_SUBMITTED',
                'from_status' => 'UNPAID',
                'to_status' => 'PENDING',
                'amount' => '3494.00',
                'payment_date' => '2026-10-02',
                'method' => 'TRANSFER',
                'proof_path' => $proofPath,
            ]);

            $expectedEventId = $submitted->getKey();

            if ($initialStatus === 'REJECTED') {
                $rejected = $payment->events()->create([
                    'actor_user_id' => $admins['a']->getKey(),
                    'event_type' => 'PAYMENT_REJECTED',
                    'from_status' => 'PENDING',
                    'to_status' => 'REJECTED',
                    'amount' => '3494.00',
                    'payment_date' => '2026-10-02',
                    'method' => 'TRANSFER',
                    'proof_path' => $proofPath,
                    'reason' => 'Previous rejection',
                ]);

                $expectedEventId = $rejected->getKey();
            }
        }

        $historicalEvents = $payment->events()->get()
            ->mapWithKeys(fn ($event) => [
                $event->getKey() => $event->getAttributes(),
            ])->all();

        $baselineEvents = \App\Models\PaymentEvent::count();
        $baselineAudits = \App\Models\AuditEvent::count();

        DB::disconnect('sqlite');

        foreach (['a' => $operationA, 'b' => $operationB] as $label => $operation) {
            $process = new \Symfony\Component\Process\Process([
                PHP_BINARY,
                __FILE__,
                'worker',
                $directory,
                $label,
                $operation,
                (string) $admins[$label]->getKey(),
                (string) $invoice->getKey(),
                $expectedEventId === null ? 'null' : (string) $expectedEventId,
            ], $root);

            $process->setTimeout(40);
            $process->start();
            $workers[$label] = $process;
        }

        settlementRaceWait($directory.'/ready-a');
        settlementRaceWait($directory.'/ready-b');

        touch($directory.'/go');

        $results = [];

        foreach ($workers as $process) {
            $exitCode = $process->wait();

            settlementRaceCheck(
                $exitCode === 0,
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

        settlementRaceCheck(
            $statuses === [200, 409],
            'Expected [200,409], got '.json_encode($statuses)
        );

        settlementRaceCheck(
            max(array_column($results, 'started'))
                < min(array_column($results, 'finished')),
            'Worker executions did not overlap.'
        );

        $winner = array_values(array_filter(
            $results,
            fn (array $result) => $result['status'] === 200
        ))[0];

        $expectedStatus = $winner['operation'] === 'reject'
            ? 'REJECTED'
            : 'PAID';

        $expectedType = match ($winner['operation']) {
            'approve' => 'PAYMENT_APPROVED',
            'reject' => 'PAYMENT_REJECTED',
            'walk-in' => 'WALK_IN_RECORDED',
        };

        $expectedAction = match ($winner['operation']) {
            'approve' => 'payment_approved',
            'reject' => 'payment_rejected',
            'walk-in' => 'walk_in_payment_recorded',
        };

        $payment = $payment->fresh();
        $event = $payment->events()->reorder()
            ->orderByDesc('pe_id')->firstOrFail();
        $audit = \App\Models\AuditEvent::query()->sole();

        settlementRaceCheck(
            \App\Models\Payment::count() === 1
                && \App\Models\Invoice::count() === 1,
            'Unexpected extra invoice or payment.'
        );

        settlementRaceCheck(
            \App\Models\PaymentEvent::count() === $baselineEvents + 1
                && \App\Models\AuditEvent::count() === $baselineAudits + 1,
            'Expected exactly one new event and audit.'
        );

        settlementRaceCheck(
            $payment->p_status === $expectedStatus
                && $payment->p_amount === '3494.00'
                && $payment->p_date?->format('Y-m-d') === '2026-10-02',
            'Unexpected final payment snapshot.'
        );

        settlementRaceCheck(
            $event->event_type === $expectedType
                && $event->from_status === $initialStatus
                && $event->to_status === $expectedStatus
                && (string) $event->actor_user_id
                    === (string) $admins[$winner['label']]->getKey(),
            'Event does not match the winning operation.'
        );

        settlementRaceCheck(
            $audit->action === $expectedAction
                && (string) $audit->actor_user_id
                    === (string) $admins[$winner['label']]->getKey()
                && (string) $audit->entity_id === (string) $payment->getKey(),
            'Audit does not match the winning operation.'
        );

        $walkIn = $winner['operation'] === 'walk-in';

        settlementRaceCheck(
            $payment->p_type === ($walkIn ? 'CASH' : 'TRANSFER')
                && $event->method === $payment->p_type
                && $event->amount === $payment->p_amount
                && $payment->p_proof === ($walkIn ? null : $proofPath)
                && $event->proof_path === $payment->p_proof,
            'Payment method or proof snapshot mismatch.'
        );

        settlementRaceCheck(
            $payment->p_reject_reason === (
                $winner['operation'] === 'reject'
                    ? 'Concurrent rejection test'
                    : null
            ),
            'Unexpected rejection reason.'
        );

        foreach ($historicalEvents as $id => $attributes) {
            settlementRaceCheck(
                \App\Models\PaymentEvent::findOrFail($id)->getAttributes()
                    === $attributes,
                'Historical event was modified.'
            );
        }

        if ($proofPath !== null) {
            settlementRaceCheck(
                Storage::disk('payment_proofs')->get($proofPath)
                    === $proofContents,
                'Historical proof was changed or deleted.'
            );
        }

        $actualFiles = Storage::disk('payment_proofs')->allFiles();

        settlementRaceCheck(
            $actualFiles === ($proofPath === null ? [] : [$proofPath]),
            'Unexpected proof files.'
        );

        echo "PASS: {$caseName} - 200/409; one event/audit; history preserved"
            .PHP_EOL;
    } catch (Throwable $exception) {
        $failed = true;

        fwrite(
            STDERR,
            "FAIL: {$caseName} - "
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
        Storage::forgetDisk('payment_proofs');

        \Illuminate\Support\Carbon::setTestNow();
        CarbonImmutable::setTestNow();

        $safeRoot = realpath($directory);

        if (
            $safeRoot !== false
            && dirname($safeRoot) === realpath(sys_get_temp_dir())
            && str_starts_with(basename($safeRoot), 'dorm-settlement-race-')
        ) {
            $entries = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $safeRoot,
                    FilesystemIterator::SKIP_DOTS
                ),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($entries as $entry) {
                $resolved = $entry->getRealPath();

                if (
                    $entry->isLink()
                    || $resolved === false
                    || ! str_starts_with(
                        $resolved,
                        $safeRoot.DIRECTORY_SEPARATOR
                    )
                ) {
                    throw new RuntimeException('Unsafe cleanup path.');
                }

                $entry->isDir() ? rmdir($resolved) : unlink($resolved);
            }

            rmdir($safeRoot);
        }
    }
}

exit($failed ? 1 : 0);