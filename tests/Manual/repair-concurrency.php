<?php

// Isolated SQLite database; never reads or modifies the application's database.
use App\Actions\Repairs\CreateRepair;
use App\Actions\Repairs\UpdateRepairStatus;
use App\Models\AuditEvent;
use App\Models\Repair;
use App\Models\RepairHistory;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Process\Process;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$worker = ($argv[1] ?? '') === 'worker';
$directory = $worker ? ($argv[2] ?? '') : sys_get_temp_dir().'/repair-race-'.bin2hex(random_bytes(8));
if (! $worker) {
    mkdir($directory);
    touch($directory.'/test.sqlite');
}
$directory = realpath($directory);
if (! $directory || dirname($directory) !== realpath(sys_get_temp_dir()) || ! str_starts_with(basename($directory), 'repair-race-')) {
    throw new RuntimeException('Invalid test directory');
}
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['app.env' => 'testing', 'database.default' => 'sqlite', 'database.connections.sqlite.url' => null,
    'database.connections.sqlite.database' => $directory.'/test.sqlite', 'database.connections.sqlite.busy_timeout' => 5000]);
DB::purge('sqlite');

if ($worker) {
    $label = $argv[3];
    $actor = User::findOrFail((int) $argv[4]);
    $repair = Repair::findOrFail((int) $argv[5]);
    // Synchronize before BEGIN IMMEDIATE, which serializes writers itself.
    touch($directory.'/'.$label.'.ready');
    $deadline = microtime(true) + 15;
    while (! is_file($directory.'/a.ready') || ! is_file($directory.'/b.ready')) {
        clearstatcache();
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Synchronization timeout');
        }
        usleep(10000);
    }
    try {
        app(UpdateRepairStatus::class)->handle($actor, $repair, ['expected_status' => 'REPORTED', 'rp_status' => 'IN_PROGRESS']);
        echo '200';
    } catch (HttpExceptionInterface $error) {
        echo $error->getStatusCode();
    }
    exit;
}

try {
    Artisan::call('migrate', ['--force' => true]);
    $actor = User::factory()->create(['u_role' => 'admin', 'is_active' => true, 'must_change_password' => false]);
    $repair = app(CreateRepair::class)->handle($actor, ['rp_type' => 'COMMON', 'rp_name' => 'Race test']);
    $processes = [];
    foreach (['a', 'b'] as $label) {
        $process = new Process([PHP_BINARY, __FILE__, 'worker', $directory, $label, (string) $actor->getKey(), (string) $repair->getKey()], $root);
        $process->setTimeout(40);
        $process->start();
        $processes[] = $process;
    }
    $statuses = [];
    foreach ($processes as $process) {
        $process->wait();
        if (! $process->isSuccessful()) {
            throw new RuntimeException($process->getErrorOutput().$process->getOutput());
        }
        $statuses[] = trim($process->getOutput());
    }
    sort($statuses);
    if ($statuses !== ['200', '409'] || $repair->fresh()->rp_status !== 'IN_PROGRESS' || RepairHistory::count() !== 2 || AuditEvent::count() !== 2) {
        throw new RuntimeException('Unexpected race result: '.json_encode($statuses));
    }
    echo "PASS: concurrent repair transitions return 200/409; one transition, one history and one audit.\n";
} finally {
    foreach ($processes ?? [] as $process) {
        if ($process->isRunning()) {
            $process->stop();
        }
    }
    DB::disconnect();
    foreach (glob($directory.'/*') as $file) {
        unlink($file);
    }
    rmdir($directory);
}
