<?php

use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $tenant = Tenant::create([
        't_Fname' => 'Invoice',
        't_Lname' => 'Test',
        't_tel' => '0811111111',
    ]);

    $room = Room::create([
        'r_name' => 'INV-101',
        'r_floor' => 1,
        'r_type' => 'STANDARD',
        'r_rent' => 3000,
        'r_status' => 'OCCUPIED',
    ]);

    $this->rental = Rental::create([
        'tenants_t_id' => $tenant->getKey(),
        'rooms_r_id' => $room->getKey(),
        'rt_movein' => '2026-09-01',
        'rt_status' => 'ACTIVE',
    ]);

    $this->startMeter = Meter::create([
        'rooms_r_id' => $room->getKey(),
        'm_date' => '2026-09-01',
        'm_water' => 100,
        'm_elec' => 1000,
    ]);

    $this->endMeter = Meter::create([
        'rooms_r_id' => $room->getKey(),
        'm_date' => '2026-10-01',
        'm_water' => 108,
        'm_elec' => 1050,
    ]);

    $this->invoiceData = [
        'rentals_rt_id' => $this->rental->getKey(),
        'i_date' => '2026-10-01',
        'i_due' => '2026-10-08',
        'period_start' => '2026-09-01',
        'period_end' => '2026-10-01',
        'start_meter_id' => $this->startMeter->getKey(),
        'end_meter_id' => $this->endMeter->getKey(),
        'water_usage' => 8,
        'elec_usage' => 50,
        'water_rate' => 18,
        'elec_rate' => 7,
        'rent_rate' => 3000,
        'i_rent' => 3000,
        'i_water' => 144,
        'i_elec' => 350,
        'i_total' => 3494,
    ];
});

test('invoice stores billing snapshots and resolves relationships', function () {
    $invoice = Invoice::create($this->invoiceData)->fresh();

    expect($invoice->period_start->toDateString())->toBe('2026-09-01')
        ->and($invoice->period_end->toDateString())->toBe('2026-10-01')
        ->and($invoice->water_usage)->toBe('8.00')
        ->and($invoice->elec_usage)->toBe('50.00')
        ->and($invoice->water_rate)->toBe('18.00')
        ->and($invoice->elec_rate)->toBe('7.00')
        ->and($invoice->rent_rate)->toBe('3000.00')
        ->and($invoice->i_total)->toBe('3494.00')
        ->and($invoice->rental->getKey())->toBe($this->rental->getKey())
        ->and($invoice->startMeter->getKey())->toBe($this->startMeter->getKey())
        ->and($invoice->endMeter->getKey())->toBe($this->endMeter->getKey());
});

test('duplicate billing period for the same rental is rejected', function () {
    Invoice::create($this->invoiceData);

    expect(fn () => Invoice::create($this->invoiceData))
        ->toThrow(QueryException::class, 'UNIQUE constraint failed');
});

test('another billing period for the same rental is allowed', function () {
    Invoice::create($this->invoiceData);

    $nextMeter = Meter::create([
        'rooms_r_id' => $this->endMeter->rooms_r_id,
        'm_date' => '2026-11-01',
        'm_water' => 116,
        'm_elec' => 1100,
    ]);

    Invoice::create(array_replace($this->invoiceData, [
        'period_start' => '2026-10-01',
        'period_end' => '2026-11-01',
        'i_date' => '2026-11-01',
        'i_due' => '2026-11-08',
        'start_meter_id' => $this->endMeter->getKey(),
        'end_meter_id' => $nextMeter->getKey(),
    ]));

    $this->assertDatabaseCount('invoices', 2);
});

test('invoice rejects a nonexistent meter', function (string $field) {
    $missingId = ((int) Meter::max('m_id')) + 1;

    expect(fn () => Invoice::create(array_replace(
        $this->invoiceData,
        [$field => $missingId]
    )))->toThrow(QueryException::class, 'FOREIGN KEY constraint failed');
})->with(['start_meter_id', 'end_meter_id']);

test('referenced meter cannot be physically deleted', function (string $field) {
    Invoice::create($this->invoiceData);

    // ลบจริงผ่าน DB เพื่อทดสอบ FK; Soft Delete ไม่ใช่การลบแถว
    expect(fn () => DB::table('meters')
        ->where('m_id', $this->invoiceData[$field])
        ->delete()
    )->toThrow(QueryException::class, 'FOREIGN KEY constraint failed');
})->with(['start_meter_id', 'end_meter_id']);