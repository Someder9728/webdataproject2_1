<?php

// Copy to tests/Manual/billing-concurrency.php, then run with PHP CLI.
declare(strict_types=1);

use App\Actions\Billing\IssueInvoice;
use App\Models\AuditEvent;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Process\Process;

$root = dirname(__DIR__, 2);
if (! is_file($root.'/vendor/autoload.php')) {
    fwrite(STDERR, "Place this file at tests/Manual/billing-concurrency.php\n");
    exit(1);
}
require $root.'/vendor/autoload.php';

function bootBillingTest(string $root, string $database): void
{
    $app = require $root.'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    config([
        'app.env' => 'testing',
        'database.default' => 'sqlite',
        'database.connections.sqlite.url' => null,
        'database.connections.sqlite.database' => $database,
        'database.connections.sqlite.foreign_key_constraints' => true,
        'database.connections.sqlite.busy_timeout' => 5000,
        'database.connections.sqlite.transaction_mode' => 'IMMEDIATE',
        'cache.default' => 'array',
        'session.driver' => 'array',
        'queue.default' => 'sync',
        'dormitory.water_rate' => '18.00',
        'dormitory.elec_rate' => '7.00',
    ]);
    DB::purge('sqlite');
    if (DB::connection()->getDatabaseName() !== $database) {
        throw new RuntimeException('Temporary database configuration failed.');
    }
    Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00', 'Asia/Bangkok'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-01 12:00:00', 'Asia/Bangkok'));
}

function awaitBillingFile(string $path): void
{
    $deadline = microtime(true) + 15;
    while (! is_file($path)) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Synchronization timeout: '.basename($path));
        }
        usleep(10000);
        clearstatcache(true, $path);
    }
}

if (($argv[1] ?? '') === 'worker') {
    try {
        [$script, $mode, $dir, $label, $actorId, $rentalId, $start] = $argv;
        $dir = realpath($dir);
        if ($dir === false || ! str_starts_with(basename($dir), 'dorm-billing-race-')) {
            throw new RuntimeException('Invalid temporary directory.');
        }
        if (! in_array($label, ['a', 'b'], true) || ! is_file($dir.'/test.sqlite')) {
            throw new RuntimeException('Invalid worker configuration.');
        }
        bootBillingTest($root, $dir.'/test.sqlite');
        $actor = User::findOrFail((int) $actorId);
        // Keep the successful transaction open briefly to exercise lock contention.
        Event::listen('eloquent.creating: '.AuditEvent::class, function () {
            usleep(500000);
        });
        touch($dir.'/ready-'.$label);
        awaitBillingFile($dir.'/go');
        $began = microtime(true);
        $status = 201;
        try {
            app(IssueInvoice::class)->handle($actor, [
                'rentals_rt_id' => (int) $rentalId,
                'period_start' => $start,
                'period_end' => '2026-10-01',
            ], persist: true);
        } catch (HttpExceptionInterface $exception) {
            $status = $exception->getStatusCode();
        }
        echo json_encode([
            'status' => $status,
            'started' => $began,
            'finished' => microtime(true),
        ], JSON_THROW_ON_ERROR);
        exit(0);
    } catch (Throwable $exception) {
        fwrite(STDERR, get_class($exception).': '.$exception->getMessage()."\n");
        exit(1);
    }
}

$failed = false;
foreach (['same period' => '2026-09-16', 'overlapping periods' => '2026-09-20'] as $case => $secondStart) {
    $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'dorm-billing-race-'.bin2hex(random_bytes(8));
    mkdir($dir, 0700);
    touch($dir.'/test.sqlite');
    $workers = [];
    try {
        bootBillingTest($root, $dir.'/test.sqlite');
        if (Artisan::call('migrate', ['--database' => 'sqlite', '--force' => true]) !== 0) {
            throw new RuntimeException(Artisan::output());
        }
        $admin = User::factory()->create([
            'u_role' => 'admin', 'is_active' => true, 'must_change_password' => false,
        ]);
        $tenant = Tenant::create([
            't_Fname' => 'Race', 't_Lname' => 'Test', 't_tel' => '0812345678',
        ]);
        $room = Room::create([
            'r_name' => 'RACE-101', 'r_floor' => 1, 'r_type' => 'Test',
            'r_rent' => '3000.00', 'r_status' => 'OCCUPIED',
        ]);
        $rental = Rental::create([
            'tenants_t_id' => $tenant->getKey(), 'rooms_r_id' => $room->getKey(),
            'rt_movein' => '2026-09-16', 'rt_status' => 'ACTIVE',
        ]);
        $rental->contract()->create([
            'c_number' => 'RACE-001', 'c_start' => '2026-09-16',
            'c_rent' => '3000.00', 'c_deposit' => '6000.00', 'c_status' => 'ACTIVE',
        ]);
        foreach ([['2026-09-16', '100', '1000'], ['2026-09-20', '102', '1010'], ['2026-10-01', '108', '1050']] as [$date, $water, $elec]) {
            Meter::create([
                'rooms_r_id' => $room->getKey(), 'm_date' => $date,
                'm_water' => $water, 'm_elec' => $elec,
            ]);
        }
        DB::disconnect('sqlite');
        foreach (['a' => '2026-09-16', 'b' => $secondStart] as $label => $start) {
            $process = new Process([
                PHP_BINARY, __FILE__, 'worker', $dir, $label,
                (string) $admin->getKey(), (string) $rental->getKey(), $start,
            ], $root);
            $process->setTimeout(25);
            $process->start();
            $workers[] = $process;
        }
        awaitBillingFile($dir.'/ready-a');
        awaitBillingFile($dir.'/ready-b');
        touch($dir.'/go');
        $results = [];
        foreach ($workers as $process) {
            if ($process->wait() !== 0) {
                throw new RuntimeException($process->getErrorOutput().' '.$process->getOutput());
            }
            $results[] = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        }
        $statuses = array_column($results, 'status');
        sort($statuses);
        if ($statuses !== [201, 409]) {
            throw new RuntimeException('Expected [201,409], got '.json_encode($statuses));
        }
        if (max(array_column($results, 'started')) >= min(array_column($results, 'finished'))) {
            throw new RuntimeException('Worker execution did not overlap; concurrency was not exercised.');
        }
        if (Invoice::count() !== 1 || Payment::count() !== 1 || AuditEvent::count() !== 1) {
            throw new RuntimeException('Expected exactly one Invoice, Payment and Audit.');
        }
        $invoice = Invoice::firstOrFail();
        $payment = $invoice->payment;
        if (! $payment || $payment->p_status !== 'UNPAID' ||
            (string) \Brick\Math\BigDecimal::of((string) $payment->p_amount)->toScale(2) !== $invoice->i_total) {
            throw new RuntimeException('Payment does not match the committed invoice.');
        }
        echo "PASS: {$case} — overlapping workers, outcomes 201/409, one Invoice/Payment/Audit\n";
    } catch (Throwable $exception) {
        $failed = true;
        fwrite(STDERR, "FAIL: {$case} — ".$exception->getMessage()."\n");
    } finally {
        foreach ($workers as $process) {
            if ($process->isRunning()) {
                $process->stop(1);
            }
        }
        DB::disconnect('sqlite');
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        // Only remove known files in this run's randomly created directory.
        foreach (['test.sqlite', 'test.sqlite-journal', 'test.sqlite-wal', 'test.sqlite-shm', 'ready-a', 'ready-b', 'go'] as $name) {
            if (is_file($dir.'/'.$name)) {
                unlink($dir.'/'.$name);
            }
        }
        rmdir($dir);
    }
}
exit($failed ? 1 : 0);
