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
                'dorm-invoice-edit-race-'
            ),
            'Invalid temporary directory.'
        );

        settlementRaceCheck(
            in_array($label, ['a', 'b'], true)
                    && in_array($operation, ['edit', 'walk-in', 'submit'], true),
            'Invalid worker operation.'
        );

        bootSettlementRace($root, $directory);

        $firstWriter = trim(
            (string) file_get_contents($directory.'/first-writer')
        );

        settlementRaceCheck(
            in_array($firstWriter, ['a', 'b'], true),
            'Invalid first writer.'
        );

        DB::connection()->beforeStartingTransaction(
            function () use ($directory, $label, $firstWriter): void {
                touch($directory.'/ready-'.$label);
                settlementRaceWait($directory.'/go');

                if ($label !== $firstWriter) {
                    // รอให้ฝั่งแรกเขียนข้อมูลแล้ว แต่ยังไม่ commit
                    settlementRaceWait($directory.'/first-writing');
                    touch($directory.'/second-starting');
                }
            }
        );

        \Illuminate\Support\Facades\Event::listen(
            'eloquent.creating: '.\App\Models\AuditEvent::class,
            function () use ($directory, $label, $firstWriter): void {
                if ($label === $firstWriter) {
                    // Invoice/Payment ถูกเขียนแล้วใน transaction นี้
                    touch($directory.'/first-writing');
                    settlementRaceWait($directory.'/second-starting');

                    // เปิดโอกาสให้ฝั่งที่สองเข้ามาขณะ transaction ยังเปิด
                    usleep(500_000);
                }
            }
        );

        $actor = \App\Models\User::findOrFail((int) $actorId);
        $invoice = \App\Models\Invoice::findOrFail((int) $invoiceId);
        $expectedEventId = $eventId === 'null' ? null : (int) $eventId;

        $started = microtime(true);
        $status = 200;

        try {
            if ($operation === 'edit') {
                app(\App\Actions\Billing\UpdateInvoice::class)->handle(
                    $actor,
                    $invoice,
                    [
                        'period_start' => '2026-09-16',
                        'period_end' => '2026-10-01',
                        'reason' => 'Concurrent invoice edit',
                    ]
                );
            } elseif ($operation === 'submit') {
                $upload = new \Illuminate\Http\UploadedFile(
                    $directory.'/input.png',
                    'receipt.png',
                    'image/png',
                    UPLOAD_ERR_OK,
                    true
                );

                app(\App\Actions\Payments\SubmitPaymentProof::class)
                    ->handle($actor, $invoice, [
                        'amount' => '3494.00',
                        'payment_date' => '2026-10-02',
                        'proof' => $upload,
                    ]);
            } elseif ($operation === 'walk-in') {
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
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $status = 422;
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
    ['edit first vs submit', 'UNPAID', 'edit', 'submit', 'a'],
    ['submit first vs edit', 'UNPAID', 'edit', 'submit', 'b'],
    ['edit first vs walk-in', 'UNPAID', 'edit', 'walk-in', 'a'],
    ['walk-in first vs edit', 'UNPAID', 'edit', 'walk-in', 'b'],
];

foreach ($cases as [
    $caseName,
    $initialStatus,
    $operationA,
    $operationB,
    $firstWriter,
]) {
    $directory = sys_get_temp_dir()
        .DIRECTORY_SEPARATOR
        .'dorm-invoice-edit-race-'
        .bin2hex(random_bytes(8));

    settlementRaceCheck(mkdir($directory, 0700), 'Cannot create test directory.');

    $directory = realpath($directory);
    settlementRaceCheck($directory !== false, 'Cannot resolve test directory.');
    settlementRaceCheck(touch($directory.'/test.sqlite'), 'Cannot create test database.');

    settlementRaceCheck(
        file_put_contents($directory.'/first-writer', $firstWriter) !== false,
        'Cannot write synchronization configuration.'
    );

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



$rental->contract()->create([
    'c_number' => 'EDIT-RACE-CONTRACT',
    'c_start' => '2026-09-01',
    'c_end' => '2026-10-01',
    'c_rent' => '3000.00',
    'c_deposit' => '6000.00',
    'c_status' => 'ENDED',
]);

\App\Models\Meter::create([
    'rooms_r_id' => $room->getKey(),
    'm_date' => '2026-09-16',
    'm_water' => '102.00',
    'm_elec' => '1010.00',
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

        $uploadBytes = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aRZkAAAAASUVORK5CYII='
        );

        settlementRaceCheck(
            file_put_contents($directory.'/input.png', $uploadBytes) !== false,
            'Cannot create upload fixture.'
        );

        DB::disconnect('sqlite');

        foreach (['a' => $operationA, 'b' => $operationB] as $label => $operation) {
            $process = new \Symfony\Component\Process\Process([
                PHP_BINARY,
                __FILE__,
                'worker',
                $directory,
                $label,
                $operation,
                (string) ($operation === 'submit'
                    ? $owner->getKey()
                    : $admins[$label]->getKey()),
                (string) $invoice->getKey(),
                $expectedEventId === null ? 'null' : (string) $expectedEventId,
            ], $root);

            $process->setTimeout(40);
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

            settlementRaceCheck(
                microtime(true) < $deadline,
                'Workers did not become ready.'
            );

            usleep(10_000);
        }

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

        settlementRaceCheck(
            max(array_column($results, 'started'))
                < min(array_column($results, 'finished')),
            'Worker executions did not overlap.'
        );

        $byOperation = [];

        foreach ($results as $result) {
            $byOperation[$result['operation']] = $result;
        }

        $editResult = $byOperation['edit'];
        $paymentResult = $byOperation[$operationB];

        $editWon = $editResult['status'] === 200;


        settlementRaceCheck(
            $editWon === ($firstWriter === 'a'),
            'Unexpected winner for '.$caseName.': '.json_encode($byOperation)
        );

        if ($editWon) {
            settlementRaceCheck(
                $paymentResult['status'] === 422,
                'Payment with stale amount must return 422.'
            );
        } else {
            settlementRaceCheck(
                $editResult['status'] === 409
                    && $paymentResult['status'] === 200,
                'Expected successful payment and rejected edit; got '
                    .json_encode($byOperation)
            );
        }

        $invoice = $invoice->fresh();
        $payment = $payment->fresh();

        $expectedTotal = $editWon ? '1888.00' : '3494.00';
        $expectedStatus = $editWon
            ? 'UNPAID'
            : ($operationB === 'submit' ? 'PENDING' : 'PAID');

        settlementRaceCheck(
            \App\Models\Invoice::count() === 1
                && \App\Models\Payment::count() === 1,
            'Unexpected invoice or payment count.'
        );

        settlementRaceCheck(
            $invoice->i_total === $expectedTotal
                && $payment->p_amount === $expectedTotal
                && $payment->p_status === $expectedStatus,
            'Invoice and payment are inconsistent.'
        );

        settlementRaceCheck(
            $invoice->period_start->toDateString() === (
                $editWon ? '2026-09-16' : '2026-09-01'
            )
                && $invoice->period_end->toDateString() === '2026-10-01'
                && $invoice->i_date->toDateString() === '2026-10-01'
                && $invoice->i_due->toDateString() === '2026-10-08',
            'Unexpected invoice dates.'
        );

        settlementRaceCheck(
            $invoice->water_rate === '18.00'
                && $invoice->elec_rate === '7.00'
                && $invoice->rent_rate === '3000.00',
            'Snapshot rates changed.'
        );

        settlementRaceCheck(
            \App\Models\AuditEvent::count() === $baselineAudits + 1
                && \App\Models\PaymentEvent::count()
                    === $baselineEvents + ($editWon ? 0 : 1),
            'Unexpected audit or payment event count.'
        );

        $audit = \App\Models\AuditEvent::sole();
        $expectedFiles = [];

        if ($editWon) {
            settlementRaceCheck(
                $audit->action === 'invoice_updated'
                    && (int) $audit->entity_id === (int) $invoice->getKey()
                    && (int) $audit->actor_user_id
                        === (int) $admins['a']->getKey()
                    && $audit->old_values['i_total'] === '3494.00'
                    && $audit->new_values['i_total'] === '1888.00',
                'Invoice edit audit mismatch.'
            );

            settlementRaceCheck(
                $payment->p_date === null
                    && $payment->p_type === null
                    && $payment->p_proof === null
                    && $payment->p_reject_reason === null,
                'Losing payment operation left payment details.'
            );
        } else {
            $submitted = $operationB === 'submit';

            $expectedActor = $submitted
                ? $owner->getKey()
                : $admins['b']->getKey();

            $expectedAction = $submitted
                ? 'payment_proof_submitted'
                : 'walk_in_payment_recorded';

            $expectedEvent = $submitted
                ? 'PROOF_SUBMITTED'
                : 'WALK_IN_RECORDED';

            $expectedMethod = $submitted ? 'TRANSFER' : 'CASH';

            $event = $payment->events()->sole();

            settlementRaceCheck(
                $audit->action === $expectedAction
                    && (int) $audit->entity_id === (int) $payment->getKey()
                    && (int) $audit->actor_user_id === (int) $expectedActor,
                'Payment audit mismatch.'
            );

            settlementRaceCheck(
                $event->event_type === $expectedEvent
                    && $event->from_status === 'UNPAID'
                    && $event->to_status === $expectedStatus
                    && $event->amount === $expectedTotal
                    && (int) $event->actor_user_id === (int) $expectedActor
                    && $event->method === $expectedMethod
                    && $event->proof_path === $payment->p_proof,
                'Payment event mismatch.'
            );

            settlementRaceCheck(
                $payment->p_type === $expectedMethod
                    && $payment->p_date?->toDateString() === '2026-10-02'
                    && $payment->p_reject_reason === null,
                'Payment details mismatch.'
            );

            if ($submitted) {
                settlementRaceCheck(
                    filled($payment->p_proof),
                    'Winning submission has no proof.'
                );

                settlementRaceCheck(
                    Storage::disk('payment_proofs')->get($payment->p_proof)
                        === $uploadBytes,
                    'Winning proof content mismatch.'
                );

                $expectedFiles[] = $payment->p_proof;
            } else {
                settlementRaceCheck(
                    $payment->p_proof === null,
                    'Walk-in must not have proof.'
                );
            }
        }

        $actualFiles = Storage::disk('payment_proofs')->allFiles();

        sort($expectedFiles);
        sort($actualFiles);

        settlementRaceCheck(
            $actualFiles === $expectedFiles,
            'Missing proof or orphan file from losing submission.'
        );

        echo 'PASS: '.$caseName
            .' - edit '.$editResult['status']
            .', payment '.$paymentResult['status']
            .'; invoice/payment/audit/proof consistent'
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
            && str_starts_with(basename($safeRoot), 'dorm-invoice-edit-race-')
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