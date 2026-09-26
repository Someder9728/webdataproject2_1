<?php

use App\Models\AuditEvent;
use App\Models\Meter;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'u_role' => 'admin',
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->tenant = Tenant::create([
        't_Fname' => 'Move',
        't_Lname' => 'In',
        't_tel' => '0812345678',
    ]);

    $this->room = Room::create([
        'r_name' => 'MOVE-101',
        'r_floor' => 1,
        'r_type' => 'เตียงเดี่ยว',
        'r_rent' => '3000.00',
        'r_status' => 'VACANT',
    ]);

    $this->meter = Meter::create([
        'rooms_r_id' => $this->room->getKey(),
        'm_date' => now('Asia/Bangkok')->toDateString(),
        'm_water' => '100.00',
        'm_elec' => '200.00',
    ]);

    $this->payload = [
        'tenants_t_id' => $this->tenant->getKey(),
        'rooms_r_id' => $this->room->getKey(),
        'c_end' => null,
        'c_rent' => '3200.50',
        'c_deposit' => '6000.00',
    ];
});

test('admin creates rental contract room status and audit together', function () {
    $response = $this->actingAs($this->admin)
        ->postJson('/api/v1/rentals', [
            ...$this->payload,
            'rt_movein' => '2000-01-01',
            'rt_status' => 'ENDED',
            'c_number' => 'FORGED',
        ])
        ->assertCreated()
        ->assertJsonPath('data.rt_status', 'ACTIVE')
        ->assertJsonPath(
            'data.rt_movein',
            now('Asia/Bangkok')->toDateString()
        );

    $rental = Rental::findOrFail($response->json('data.rt_id'));
    $contract = $rental->contract;

    expect($contract->c_number)->toBe(
        'CNT-'.str_pad((string) $rental->getKey(), 6, '0', STR_PAD_LEFT)
    );

    expect($contract->c_start->toDateString())
        ->toBe($rental->rt_movein->toDateString());

    expect($contract->c_rent)->toBe('3200.50');
    expect($contract->c_deposit)->toBe('6000.00');
    expect($this->room->fresh()->r_status)->toBe('OCCUPIED');

    $this->assertDatabaseCount('rentals', 1);
    $this->assertDatabaseCount('contracts', 1);
    $this->assertDatabaseCount('audit_events', 2);

    $this->assertDatabaseHas('audit_events', [
        'actor_user_id' => $this->admin->getKey(),
        'entity_type' => 'rentals',
        'entity_id' => $rental->getKey(),
        'action' => 'rental_created',
    ]);
});

test('repeating move in creates no extra records', function () {
    $this->actingAs($this->admin);

    $this->postJson('/api/v1/rentals', $this->payload)
        ->assertCreated();

    $this->postJson('/api/v1/rentals', $this->payload)
        ->assertConflict();

    $this->assertDatabaseCount('rentals', 1);
    $this->assertDatabaseCount('contracts', 1);
    $this->assertDatabaseCount('audit_events', 2);
});

test('active rental blocks tenant or room even when room says vacant', function ($sameTenant) {
    $otherTenant = Tenant::create([
        't_Fname' => 'Other',
        't_Lname' => 'Tenant',
        't_tel' => '0899999999',
    ]);

    $otherRoom = Room::create([
        'r_name' => 'OTHER-202',
        'r_floor' => 2,
        'r_type' => 'เตียงคู่',
        'r_rent' => '4000.00',
        'r_status' => 'OCCUPIED',
    ]);

    Rental::create([
        'tenants_t_id' => $sameTenant
            ? $this->tenant->getKey()
            : $otherTenant->getKey(),
        'rooms_r_id' => $sameTenant
            ? $otherRoom->getKey()
            : $this->room->getKey(),
        'rt_movein' => '2026-01-01',
        'rt_status' => 'ACTIVE',
    ]);

    $this->actingAs($this->admin)
        ->postJson('/api/v1/rentals', $this->payload)
        ->assertConflict();

    $this->assertDatabaseCount('rentals', 1);
    $this->assertDatabaseCount('contracts', 0);
    $this->assertDatabaseCount('audit_events', 0);
})->with([
    'same tenant' => [true],
    'same room' => [false],
]);

test('move in requires meter on exact move in date', function () {
    $this->meter->m_date = now('Asia/Bangkok')
        ->subDay()->toDateString();
    $this->meter->save();

    $this->actingAs($this->admin)
        ->postJson('/api/v1/rentals', $this->payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('rooms_r_id');

    $this->assertDatabaseCount('rentals', 0);
    $this->assertDatabaseCount('contracts', 0);
    $this->assertDatabaseCount('audit_events', 0);

    expect($this->room->fresh()->r_status)->toBe('VACANT');
});

test('invalid money creates no rental or contract', function ($amount) {
    $this->actingAs($this->admin)
        ->postJson('/api/v1/rentals', [
            ...$this->payload,
            'c_rent' => $amount,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('c_rent');

    $this->assertDatabaseCount('rentals', 0);
    $this->assertDatabaseCount('contracts', 0);
    $this->assertDatabaseCount('audit_events', 0);
})->with([
    'negative' => ['-1'],
    'excess precision' => ['3000.001'],
    'overflow' => ['100000000'],
]);

test('tenant cannot create rental', function () {
    $user = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => $this->tenant->getKey(),
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->actingAs($user)
        ->postJson('/api/v1/rentals', $this->payload)
        ->assertForbidden();

    $this->assertDatabaseCount('rentals', 0);
    $this->assertDatabaseCount('contracts', 0);
});

test('audit failure rolls back rental contract and room changes', function () {
    $eventName = 'eloquent.creating: '.AuditEvent::class;
    $attempted = false;

    Event::listen($eventName, function () use (&$attempted) {
        $attempted = true;
        throw new RuntimeException('Simulated audit failure');
    });

    try {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/rentals', $this->payload)
            ->assertStatus(500);
    } finally {
        Event::forget($eventName);
    }

    expect($attempted)->toBeTrue();
    expect($this->room->fresh()->r_status)->toBe('VACANT');

    $this->assertDatabaseCount('rentals', 0);
    $this->assertDatabaseCount('contracts', 0);
    $this->assertDatabaseCount('audit_events', 0);
});