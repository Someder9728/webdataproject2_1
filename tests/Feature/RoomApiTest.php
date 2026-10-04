<?php

use App\Models\AuditEvent;
use App\Models\Rental;
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
        'r_name' => '101',
        'r_floor' => 1,
        'r_type' => 'เตียงเดี่ยว',
        'r_rent' => '3000.00',
        'r_status' => 'VACANT',
    ]);
});

test('admin creates custom room type with controlled status and audit', function () {
    $response = $this->actingAs($this->admin)
        ->postJson('/api/v1/rooms', [
            'r_name' => ' 102 ',
            'r_floor' => 1,
            'r_type' => ' เตียงคู่พร้อมระเบียง ',
            'r_rent' => '4500.50',
            'r_status' => 'OCCUPIED',
            'r_id' => 999999,
        ])
        ->assertCreated()
        ->assertJsonPath('data.r_name', '102')
        ->assertJsonPath('data.r_type', 'เตียงคู่พร้อมระเบียง')
        ->assertJsonPath('data.r_rent', '4500.50')
        ->assertJsonPath('data.r_status', 'VACANT');

    expect($response->json('data.r_id'))->not->toBe(999999)
        ->and(AuditEvent::sole()->action)->toBe('room_created');

    $this->assertDatabaseCount('rooms', 2);
});

test('admin patches room without changing occupancy and skips duplicate audit', function () {
    $this->room->update(['r_status' => 'OCCUPIED']);
    $url = '/api/v1/rooms/'.$this->room->getKey();

    $this->actingAs($this->admin)
        ->patchJson($url, [
            'r_rent' => '3500.00',
            'r_status' => 'VACANT',
        ])
        ->assertOk()
        ->assertJsonPath('data.r_rent', '3500.00')
        ->assertJsonPath('data.r_status', 'OCCUPIED')
        ->assertJsonPath('data.r_type', 'เตียงเดี่ยว');

    expect(AuditEvent::sole()->old_values)
        ->toBe(['r_rent' => '3000.00'])
        ->and(AuditEvent::sole()->new_values)
        ->toBe(['r_rent' => '3500.00']);

    $this->patchJson($url, ['r_rent' => '3500.00'])->assertOk();

    $this->assertDatabaseCount('audit_events', 1);
});

test('room list supports search filters pagination and excludes deleted rooms', function () {
    $second = Room::create([
        'r_name' => '201',
        'r_floor' => 2,
        'r_type' => 'เตียงคู่',
        'r_rent' => '4000.00',
        'r_status' => 'OCCUPIED',
    ]);

    $deleted = Room::create([
        'r_name' => '202',
        'r_floor' => 2,
        'r_type' => 'เตียงคู่',
        'r_rent' => '4000.00',
        'r_status' => 'VACANT',
    ]);
    $deleted->delete();

    $this->actingAs($this->admin)
        ->getJson('/api/v1/rooms?per_page=1')
        ->assertOk()
        ->assertJsonPath('data.0.r_id', $this->room->getKey())
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('meta.last_page', 2);

    $this->getJson('/api/v1/rooms?per_page=1&page=2')
        ->assertOk()
        ->assertJsonPath('data.0.r_id', $second->getKey());

    $this->getJson('/api/v1/rooms?search=20&r_floor=2&r_status=OCCUPIED')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.r_id', $second->getKey());

    $this->getJson('/api/v1/rooms/'.$deleted->getKey())
        ->assertNotFound();

    $this->getJson('/api/v1/rooms?per_page=101')
        ->assertUnprocessable();
});

test('tenant can only read current room and cannot manage rooms', function () {
    $tenant = Tenant::create([
        't_Fname' => 'Room',
        't_Lname' => 'Tenant',
        't_tel' => '0812345678',
    ]);

    $user = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => $tenant->getKey(),
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $rental = Rental::create([
        'tenants_t_id' => $tenant->getKey(),
        'rooms_r_id' => $this->room->getKey(),
        'rt_movein' => '2026-09-01',
        'rt_status' => 'ACTIVE',
    ]);

    $url = '/api/v1/rooms/'.$this->room->getKey();

    $this->actingAs($user)->getJson($url)->assertOk();
    $this->getJson('/api/v1/rooms')->assertForbidden();
    $this->postJson('/api/v1/rooms', [])->assertForbidden();
    $this->patchJson($url, ['r_type' => 'Changed'])->assertForbidden();

    $rental->update([
        'rt_status' => 'ENDED',
        'rt_moveout' => '2026-09-24',
    ]);

    $this->getJson($url)->assertNotFound();

    expect($this->room->fresh()->r_type)->toBe('เตียงเดี่ยว');
    $this->assertDatabaseCount('audit_events', 0);
});

test('guest cannot access room endpoints', function () {
    $url = '/api/v1/rooms/'.$this->room->getKey();

    $this->getJson('/api/v1/rooms')->assertUnauthorized();
    $this->getJson($url)->assertUnauthorized();
    $this->postJson('/api/v1/rooms', [])->assertUnauthorized();
    $this->patchJson($url, [])->assertUnauthorized();
});

test('invalid room input creates neither room nor audit', function (
    string $field,
    mixed $value
) {
    $this->actingAs($this->admin)
        ->postJson('/api/v1/rooms', array_replace([
            'r_name' => '102',
            'r_floor' => 1,
            'r_type' => 'Custom',
            'r_rent' => '3000.00',
        ], [$field => $value]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);

    $this->assertDatabaseCount('rooms', 1);
    $this->assertDatabaseCount('audit_events', 0);
})->with([
    'duplicate name' => ['r_name', '101'],
    'empty type' => ['r_type', ' '],
    'long type' => ['r_type', str_repeat('a', 256)],
    'fractional floor' => ['r_floor', 1.5],
    'negative rent' => ['r_rent', '-1'],
    'rent precision' => ['r_rent', '3000.123'],
    'rent overflow' => ['r_rent', '100000000.00'],
]);

test('invalid patch leaves room unchanged', function () {
    $before = $this->room->fresh()->getAttributes();

    $this->actingAs($this->admin)
        ->patchJson('/api/v1/rooms/'.$this->room->getKey(), [
            'r_name' => 'Changed',
            'r_rent' => '-1',
        ])
        ->assertUnprocessable();

    expect($this->room->fresh()->getAttributes())->toBe($before);
    $this->assertDatabaseCount('audit_events', 0);
});

test('audit failure rolls back room write', function (string $operation) {
    $before = $this->room->fresh()->getAttributes();

    AuditEvent::creating(function () {
        throw new RuntimeException('Simulated audit failure');
    });

    try {
        $this->actingAs($this->admin);

        if ($operation === 'create') {
            $this->postJson('/api/v1/rooms', [
                'r_name' => '102',
                'r_floor' => 1,
                'r_type' => 'Custom',
                'r_rent' => '3000.00',
            ])->assertStatus(500);
        } else {
            $this->patchJson('/api/v1/rooms/'.$this->room->getKey(), [
                'r_type' => 'Changed',
            ])->assertStatus(500);
        }

        expect($this->room->fresh()->getAttributes())->toBe($before);
        $this->assertDatabaseCount('rooms', 1);
        $this->assertDatabaseCount('audit_events', 0);
    } finally {
        AuditEvent::flushEventListeners();
    }
})->with(['create', 'update']);