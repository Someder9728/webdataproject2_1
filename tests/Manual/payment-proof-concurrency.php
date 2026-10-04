<?php

declare(strict_types=1);

use App\Actions\Payments\SubmitPaymentProof;
use App\Models\AuditEvent;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Process\Process;

$root = dirname(__DIR__, 2);
if (! is_file($root.'/vendor/autoload.php')) {
    fwrite(STDERR, "Place this file at tests/Manual/payment-proof-concurrency.php\n");
    exit(1);
}
require $root.'/vendor/autoload.php';

function bootProofRace(string $root, string $dir): void
{
    $app = require $root.'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    config([
        'app.env' => 'testing',
        'database.default' => 'sqlite',
        'database.connections.sqlite.url' => null,
        'database.connections.sqlite.database' => $dir.'/test.sqlite',
        'database.connections.sqlite.foreign_key_constraints' => true,
        'database.connections.sqlite.busy_timeout' => 5000,
        'database.connections.sqlite.transaction_mode' => 'IMMEDIATE',
        'cache.default' => 'array',
        'session.driver' => 'array',
        'queue.default' => 'sync',
        'filesystems.disks.payment_proofs' => [
            'driver' => 'local', 'root' => $dir.'/proofs',
            'visibility' => 'private', 'serve' => false, 'throw' => true,
        ],
    ]);
    DB::purge('sqlite');
    Storage::forgetDisk('payment_proofs');
    if (DB::connection()->getDatabaseName() !== $dir.'/test.sqlite') {
        throw new RuntimeException('Temporary database configuration failed.');
    }
    Carbon::setTestNow(Carbon::parse('2026-10-02 12:00:00', 'Asia/Bangkok'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-02 12:00:00', 'Asia/Bangkok'));
}

function proofRaceWait(string $path): void
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

function proofRaceCheck(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

if (($argv[1] ?? '') === 'worker') {
    try {
        [$script, $mode, $dir, $label, $actorId, $invoiceId] = $argv;
        $dir = realpath($dir);
        proofRaceCheck($dir !== false && str_starts_with(basename($dir), 'dorm-proof-race-'), 'Invalid temporary directory.');
        proofRaceCheck(in_array($label, ['a', 'b'], true) && is_file($dir.'/test.sqlite'), 'Invalid worker arguments.');
        bootProofRace($root, $dir);
        // This barrier is reached AFTER each Action has stored its upload,
        // but BEFORE either Action starts its database transaction.
        DB::connection()->beforeStartingTransaction(function () use ($dir, $label) {
            touch($dir.'/uploaded-'.$label);
            proofRaceWait($dir.'/go');
        });
        Event::listen('eloquent.creating: '.AuditEvent::class, function () {
            usleep(500000);
        });
        $file = new UploadedFile($dir.'/input.png', 'receipt-'.$label.'.png', 'image/png', UPLOAD_ERR_OK, true);
        $started = microtime(true);
        $status = 200;
        try {
            app(SubmitPaymentProof::class)->handle(
                User::findOrFail((int) $actorId),
                Invoice::findOrFail((int) $invoiceId),
                ['amount' => '3494.00', 'payment_date' => '2026-10-02', 'proof' => $file]
            );
        } catch (HttpExceptionInterface $exception) {
            $status = $exception->getStatusCode();
        }
        echo json_encode(['status' => $status, 'started' => $started, 'finished' => microtime(true)], JSON_THROW_ON_ERROR);
        exit(0);
    } catch (Throwable $exception) {
        fwrite(STDERR, get_class($exception).': '.$exception->getMessage()."\n");
        exit(1);
    }
}

$failed = false;
foreach (['UNPAID', 'REJECTED'] as $initialStatus) {
    $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'dorm-proof-race-'.bin2hex(random_bytes(8));
    mkdir($dir, 0700);
    $dir = realpath($dir);
    touch($dir.'/test.sqlite');
    file_put_contents($dir.'/input.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aRZkAAAAASUVORK5CYII='));
    $workers = [];
    try {
        bootProofRace($root, $dir);
        proofRaceCheck(Artisan::call('migrate', ['--database' => 'sqlite', '--force' => true]) === 0, Artisan::output());
        $tenant = Tenant::create(['t_Fname' => 'Race', 't_Lname' => 'Owner', 't_tel' => '0812345678']);
        $actor = User::factory()->create([
            'u_role' => 'tenant', 'tenants_t_id' => $tenant->getKey(),
            'is_active' => true, 'must_change_password' => false,
        ]);
        $room = Room::create([
            'r_name' => 'RACE-101', 'r_floor' => 1, 'r_type' => 'Test',
            'r_rent' => '3000.00', 'r_status' => 'VACANT',
        ]);
        $rental = Rental::create([
            'tenants_t_id' => $tenant->getKey(), 'rooms_r_id' => $room->getKey(),
            'rt_movein' => '2026-09-01', 'rt_moveout' => '2026-10-01', 'rt_status' => 'ENDED',
        ]);
        $meters = [];
        foreach ([['2026-09-01', '100', '1000'], ['2026-10-01', '108', '1050']] as [$date, $water, $elec]) {
            $meters[] = Meter::create(['rooms_r_id' => $room->getKey(), 'm_date' => $date, 'm_water' => $water, 'm_elec' => $elec]);
        }
        $invoice = Invoice::create([
            'rentals_rt_id' => $rental->getKey(), 'period_start' => '2026-09-01', 'period_end' => '2026-10-01',
            'start_meter_id' => $meters[0]->getKey(), 'end_meter_id' => $meters[1]->getKey(),
            'i_date' => '2026-10-01', 'i_due' => '2026-10-08',
            'water_usage' => '8.00', 'elec_usage' => '50.00', 'water_rate' => '18.00', 'elec_rate' => '7.00',
            'rent_rate' => '3000.00', 'i_rent' => '3000.00', 'i_water' => '144.00', 'i_elec' => '350.00', 'i_total' => '3494.00',
        ]);
        $payment = $invoice->payment()->create(['p_amount' => '3494.00', 'p_status' => $initialStatus]);
        $oldPath = null;
        $oldEvent = null;
        if ($initialStatus === 'REJECTED') {
            $oldPath = 'invoices/'.$invoice->getKey().'/old.png';
            Storage::disk('payment_proofs')->put($oldPath, file_get_contents($dir.'/input.png'));
            $payment->update(['p_proof' => $oldPath, 'p_reject_reason' => 'Test rejection']);
            $oldEvent = $payment->events()->create([
                'actor_user_id' => $actor->getKey(), 'event_type' => 'PROOF_SUBMITTED',
                'from_status' => 'UNPAID', 'to_status' => 'PENDING', 'amount' => '3494.00',
                'payment_date' => '2026-10-01', 'method' => 'TRANSFER', 'proof_path' => $oldPath,
            ]);
        }
        $baselineEvents = PaymentEvent::count();
        DB::disconnect('sqlite');
        foreach (['a', 'b'] as $label) {
            $process = new Process([PHP_BINARY, __FILE__, 'worker', $dir, $label, (string) $actor->getKey(), (string) $invoice->getKey()], $root);
            $process->setTimeout(30);
            $process->start();
            $workers[] = $process;
        }
        proofRaceWait($dir.'/uploaded-a');
        proofRaceWait($dir.'/uploaded-b');
        proofRaceCheck(count(Storage::disk('payment_proofs')->allFiles()) === ($oldPath ? 3 : 2), 'Both uploads must exist before releasing the transaction barrier.');
        touch($dir.'/go');
        $results = [];
        foreach ($workers as $process) {
            proofRaceCheck($process->wait() === 0, $process->getErrorOutput().' '.$process->getOutput());
            $results[] = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        }
        $statuses = array_column($results, 'status');
        sort($statuses);
        proofRaceCheck($statuses === [200, 409], 'Expected [200,409], got '.json_encode($statuses));
        proofRaceCheck(max(array_column($results, 'started')) < min(array_column($results, 'finished')), 'Worker executions did not overlap.');
        proofRaceCheck(Payment::count() === 1 && Invoice::count() === 1, 'Unexpected extra payment or invoice.');
        proofRaceCheck(PaymentEvent::count() === $baselineEvents + 1 && AuditEvent::count() === 1, 'Expected one new payment event and audit.');
        $payment = $payment->fresh();
        proofRaceCheck($payment->p_status === 'PENDING' && $payment->p_reject_reason === null, 'Unexpected final payment state.');
        proofRaceCheck($payment->p_amount === '3494.00' && $payment->p_type === 'TRANSFER', 'Unexpected payment amount or method.');
        $event = PaymentEvent::orderByDesc('pe_id')->firstOrFail();
        proofRaceCheck($event->proof_path === $payment->p_proof && $event->from_status === $initialStatus && $event->to_status === 'PENDING', 'Event snapshot does not match payment.');
        $expectedFiles = [$payment->p_proof];
        if ($oldPath) {
            $expectedFiles[] = $oldPath;
            proofRaceCheck($oldEvent->fresh()->proof_path === $oldPath, 'Historical event was changed.');
            proofRaceCheck(Storage::disk('payment_proofs')->get($oldPath) === file_get_contents($dir.'/input.png'), 'Historical file was changed.');
        }
        $actualFiles = Storage::disk('payment_proofs')->allFiles();
        sort($actualFiles);
        sort($expectedFiles);
        proofRaceCheck($actualFiles === $expectedFiles, 'Losing upload was not cleaned up, or winning/historical proof is missing.');
        echo "PASS: {$initialStatus} - two stored uploads raced; 200/409; one new event/audit; losing file removed; history preserved\n";
    } catch (Throwable $exception) {
        $failed = true;
        fwrite(STDERR, "FAIL: {$initialStatus} - ".$exception->getMessage()."\n");
    } finally {
        foreach ($workers as $process) {
            if ($process->isRunning()) {
                $process->stop(1);
            }
        }
        DB::disconnect('sqlite');
        Storage::forgetDisk('payment_proofs');
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        // Remove only files below the random temporary directory created by this run.
        $safeRoot = realpath($dir);
        if ($safeRoot !== false && dirname($safeRoot) === realpath(sys_get_temp_dir()) && str_starts_with(basename($safeRoot), 'dorm-proof-race-')) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($safeRoot, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($iterator as $entry) {
                $resolved = $entry->getRealPath();
                if ($entry->isLink() || $resolved === false || ! str_starts_with($resolved, $safeRoot.DIRECTORY_SEPARATOR)) {
                    throw new RuntimeException('Refusing unexpected temporary cleanup path.');
                }
                $entry->isDir() ? rmdir($resolved) : unlink($resolved);
            }
            rmdir($safeRoot);
        }
    }
}
exit($failed ? 1 : 0);
