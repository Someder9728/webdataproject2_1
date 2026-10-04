<?php

use App\Actions\Meters\RecordMeterReading;
use App\Models\AuditEvent;
use App\Models\Meter;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'u_role' => 'admin',
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->room = Room::create([
        'r_name' => 'METER-101',
        'r_floor' => 1,
        'r_type' => 'เตียงเดี่ยว',
        'r_rent' => '3000.00',
        'r_status' => 'VACANT',
    ]);

    $this->url = "/api/v1/rooms/{$this->room->getKey()}/meters";

    $this->payload = [
        'm_date' => now('Asia/Bangkok')->toDateString(),
        'm_water' => '100.25',
        'm_elec' => '200.50',
    ];
});

test('admin records opening meter before rental with controlled fields', function () {
    $this->actingAs($this->admin)
        ->postJson($this->url, [
            ...$this->payload,
            'rooms_r_id' => 999999,
            'm_id' => 999999,
        ])
        ->assertCreated()
        ->assertJsonPath('data.rooms_r_id', $this->room->getKey())
        ->assertJsonPath('data.m_date', $this->payload['m_date'])
        ->assertJsonPath('data.m_water', '100.25')
        ->assertJsonPath('data.m_elec', '200.50');

    $this->assertDatabaseCount('rentals', 0);
    $this->assertDatabaseCount('meters', 1);
    $this->assertDatabaseCount('audit_events', 1);

    $this->assertDatabaseHas('audit_events', [
        'actor_user_id' => $this->admin->getKey(),
        'entity_type' => 'meters',
        'entity_id' => Meter::firstOrFail()->getKey(),
        'action' => 'meter_reading_recorded',
    ]);
});

test('opening meter from api allows rental creation', function () {
    $tenant = Tenant::create([
        't_Fname' => 'Meter',
        't_Lname' => 'Tenant',
        't_tel' => '0812345678',
    ]);

    $this->actingAs($this->admin)
        ->postJson($this->url, $this->payload)
        ->assertCreated();

    $this->postJson('/api/v1/rentals', [
        'tenants_t_id' => $tenant->getKey(),
        'rooms_r_id' => $this->room->getKey(),
        'c_end' => null,
        'c_rent' => '3000.00',
        'c_deposit' => '6000.00',
    ])
        ->assertCreated()
        ->assertJsonPath('data.rt_status', 'ACTIVE');

    expect($this->room->fresh()->r_status)->toBe('OCCUPIED');

    $this->assertDatabaseCount('meters', 1);
    $this->assertDatabaseCount('contracts', 1);
});

test('same room and date cannot be recorded twice', function () {
    $this->actingAs($this->admin)
        ->postJson($this->url, $this->payload)
        ->assertCreated();

    $this->postJson($this->url, $this->payload)
        ->assertConflict();

    $this->assertDatabaseCount('meters', 1);
    $this->assertDatabaseCount('audit_events', 1);
});

test('soft deleted reading still reserves its room and date', function () {
    Meter::create([
        ...$this->payload,
        'rooms_r_id' => $this->room->getKey(),
    ])->delete();

    $this->actingAs($this->admin)
        ->postJson($this->url, $this->payload)
        ->assertConflict();

    $this->assertDatabaseCount('meters', 1);
    $this->assertDatabaseCount('audit_events', 0);
});

test('backdated reading is checked against both neighbours', function (
    string $water,
    string $electricity,
    ?string $errorField
) {
    foreach ([
        ['2026-09-01', '100.00', '200.00'],
        ['2026-10-01', '120.00', '240.00'],
    ] as [$date, $waterValue, $electricityValue]) {
        Meter::create([
            'rooms_r_id' => $this->room->getKey(),
            'm_date' => $date,
            'm_water' => $waterValue,
            'm_elec' => $electricityValue,
        ]);
    }

    $response = $this->actingAs($this->admin)
        ->postJson($this->url, [
            'm_date' => '2026-09-15',
            'm_water' => $water,
            'm_elec' => $electricity,
        ]);

    if ($errorField !== null) {
        $response->assertUnprocessable()
            ->assertJsonValidationErrors($errorField);

        $this->assertDatabaseCount('meters', 2);
        $this->assertDatabaseCount('audit_events', 0);
    } else {
        $response->assertCreated();

        $this->assertDatabaseCount('meters', 3);
        $this->assertDatabaseCount('audit_events', 1);
    }
})->with([
    'water below previous' => ['99.99', '220.00', 'm_water'],
    'water above next' => ['120.01', '220.00', 'm_water'],
    'electricity below previous' => ['110.00', '199.99', 'm_elec'],
    'electricity above next' => ['110.00', '240.01', 'm_elec'],
    'between neighbours' => ['110.25', '220.50', null],
    'equal readings allowed' => ['100.00', '240.00', null],
]);

test('invalid reading creates neither meter nor audit', function (
    array $changes,
    string $field
) {
    $this->actingAs($this->admin)
        ->postJson($this->url, array_replace($this->payload, $changes))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);

    $this->assertDatabaseCount('meters', 0);
    $this->assertDatabaseCount('audit_events', 0);
})->with([
    'negative' => [['m_water' => '-1'], 'm_water'],
    'too precise' => [['m_elec' => '1.234'], 'm_elec'],
    'too large' => [['m_water' => '100000000'], 'm_water'],
    'invalid date' => [['m_date' => '2026-02-30'], 'm_date'],
]);

test('guest cannot read or record meters', function () {
    $this->getJson($this->url)->assertUnauthorized();
    $this->postJson($this->url, $this->payload)->assertUnauthorized();
});

test('restricted accounts cannot read or record meters', function (array $changes) {
    $this->admin->forceFill($changes)->save();

        foreach ($changes as $field => $value) {
            expect($this->admin->fresh()->getAttribute($field))->toBe($value);
        }

    $this->actingAs($this->admin->fresh())
        ->getJson($this->url)
        ->assertForbidden();

    $this->actingAs($this->admin->fresh())
        ->postJson($this->url, $this->payload)
        ->assertForbidden();

    $this->assertDatabaseCount('meters', 0);
})->with([
    'tenant' => [['u_role' => 'tenant']],
    'inactive' => [['is_active' => false]],
    'password required' => [['must_change_password' => true]],
]);

test('action rechecks actor even when caller holds stale model', function () {
    User::whereKey($this->admin->getKey())
        ->update(['is_active' => false]);

    try {
        app(RecordMeterReading::class)->handle(
            $this->admin,
            $this->room,
            $this->payload
        );

        $this->fail('Inactive actor was allowed');
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
        expect($exception->getStatusCode())->toBe(403);
    }

    $this->assertDatabaseCount('meters', 0);
    $this->assertDatabaseCount('audit_events', 0);
});

test('meter creation rolls back when audit fails', function () {
    $eventName = 'eloquent.creating: '.AuditEvent::class;
    $dispatcher = AuditEvent::getEventDispatcher();
    $originalListeners = $dispatcher->getRawListeners()[$eventName] ?? [];

    $dispatcher->listen($eventName, function () {
        throw new RuntimeException('Meter audit failure');
    });

    try {
        app(RecordMeterReading::class)->handle(
            $this->admin,
            $this->room,
            $this->payload
        );

        $this->fail('Expected audit failure');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Meter audit failure');
    } finally {
        $dispatcher->forget($eventName);

        foreach ($originalListeners as $listener) {
            $dispatcher->listen($eventName, $listener);
        }
    }

    $this->assertDatabaseCount('meters', 0);
    $this->assertDatabaseCount('audit_events', 0);
});

test('list returns only selected room in date order with pagination', function () {
    foreach (['2026-09-01', '2026-09-02', '2026-09-03'] as $date) {
        Meter::create([
            'rooms_r_id' => $this->room->getKey(),
            'm_date' => $date,
            'm_water' => '100.00',
            'm_elec' => '200.00',
        ]);
    }

    $otherRoom = Room::create([
        'r_name' => 'METER-102',
        'r_floor' => 1,
        'r_type' => 'เตียงคู่',
        'r_rent' => '4000.00',
        'r_status' => 'VACANT',
    ]);

    Meter::create([
        'rooms_r_id' => $otherRoom->getKey(),
        'm_date' => '2026-09-04',
        'm_water' => '900.00',
        'm_elec' => '900.00',
    ]);

    $this->actingAs($this->admin)
        ->getJson($this->url.'?per_page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.m_date', '2026-09-03')
        ->assertJsonPath('data.1.m_date', '2026-09-02')
        ->assertJsonPath('meta.total', 3);

    $this->getJson($this->url.'?per_page=2&page=2')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.m_date', '2026-09-01');

    $this->getJson($this->url.'?per_page=101')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('per_page');
});

test('deleted room cannot receive readings', function () {
    $this->room->delete();

    $this->actingAs($this->admin)
        ->postJson($this->url, $this->payload)
        ->assertNotFound();

    $this->assertDatabaseCount('meters', 0);
});