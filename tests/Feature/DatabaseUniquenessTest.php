<?php

use App\Models\Meter;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->tenantOne = Tenant::create([
        't_Fname' => 'ผู้เช่า',
        't_Lname' => 'หนึ่ง',
        't_tel' => '0811111111',
    ]);

    $this->tenantTwo = Tenant::create([
        't_Fname' => 'ผู้เช่า',
        't_Lname' => 'สอง',
        't_tel' => '0822222222',
    ]);

    $this->roomOne = Room::create([
        'r_name' => 'TEST-101',
        'r_floor' => 1,
        'r_type' => 'STANDARD',
        'r_rent' => 3000,
        'r_status' => 'VACANT',
    ]);

    $this->roomTwo = Room::create([
        'r_name' => 'TEST-102',
        'r_floor' => 1,
        'r_type' => 'STANDARD',
        'r_rent' => 3000,
        'r_status' => 'VACANT',
    ]);
});

test('duplicate room number is rejected', function () {
    $duplicate = $this->roomOne->replicate();

    expect(fn () => $duplicate->save())
        ->toThrow(QueryException::class, 'UNIQUE constraint failed');
});

test('tenant cannot have two active rentals', function () {
    Rental::create([
        'tenants_t_id' => $this->tenantOne->getKey(),
        'rooms_r_id' => $this->roomOne->getKey(),
        'rt_movein' => '2026-09-01',
        'rt_status' => 'ACTIVE',
    ]);

    expect(fn () => Rental::create([
        'tenants_t_id' => $this->tenantOne->getKey(),
        'rooms_r_id' => $this->roomTwo->getKey(),
        'rt_movein' => '2026-09-02',
        'rt_status' => 'ACTIVE',
    ]))->toThrow(QueryException::class, 'UNIQUE constraint failed');
});

test('room cannot have two active rentals', function () {
    Rental::create([
        'tenants_t_id' => $this->tenantOne->getKey(),
        'rooms_r_id' => $this->roomOne->getKey(),
        'rt_movein' => '2026-09-01',
        'rt_status' => 'ACTIVE',
    ]);

    expect(fn () => Rental::create([
        'tenants_t_id' => $this->tenantTwo->getKey(),
        'rooms_r_id' => $this->roomOne->getKey(),
        'rt_movein' => '2026-09-02',
        'rt_status' => 'ACTIVE',
    ]))->toThrow(QueryException::class, 'UNIQUE constraint failed');
});

test('ended rental does not prevent a new active rental', function () {
    Rental::create([
        'tenants_t_id' => $this->tenantOne->getKey(),
        'rooms_r_id' => $this->roomOne->getKey(),
        'rt_movein' => '2026-08-01',
        'rt_moveout' => '2026-09-01',
        'rt_status' => 'ENDED',
    ]);

    $active = Rental::create([
        'tenants_t_id' => $this->tenantOne->getKey(),
        'rooms_r_id' => $this->roomOne->getKey(),
        'rt_movein' => '2026-09-01',
        'rt_status' => 'ACTIVE',
    ]);

    expect($active->exists)->toBeTrue();

    $this->assertDatabaseCount('rentals', 2);
});

test('duplicate meter for same room and date is rejected', function () {
    Meter::create([
        'rooms_r_id' => $this->roomOne->getKey(),
        'm_date' => '2026-09-01',
        'm_water' => 100,
        'm_elec' => 1000,
    ]);

    expect(fn () => Meter::create([
        'rooms_r_id' => $this->roomOne->getKey(),
        'm_date' => '2026-09-01',
        'm_water' => 101,
        'm_elec' => 1001,
    ]))->toThrow(QueryException::class, 'UNIQUE constraint failed');
});

test('meters allow another date or another room', function () {
    foreach ([
        [$this->roomOne->getKey(), '2026-09-01'],
        [$this->roomOne->getKey(), '2026-09-02'],
        [$this->roomTwo->getKey(), '2026-09-01'],
    ] as [$roomId, $date]) {
        Meter::create([
            'rooms_r_id' => $roomId,
            'm_date' => $date,
            'm_water' => 100,
            'm_elec' => 1000,
        ]);
    }

    $this->assertDatabaseCount('meters', 3);
});