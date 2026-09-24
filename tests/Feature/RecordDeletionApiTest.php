<?php

use App\Models\AuditEvent;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'u_role' => 'admin',
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->tenant = Tenant::create([
        't_Fname' => 'Delete',
        't_Lname' => 'Test',
        't_tel' => '0812345678',
    ]);

    $this->room = Room::create([
        'r_name' => 'DEL-101',
        'r_floor' => 1,
        'r_type' => 'Custom',
        'r_rent' => '3000.00',
        'r_status' => 'VACANT',
    ]);
});

test('admin can soft delete unused record once', function (string $kind) {
    $record = $kind === 'tenants' ? $this->tenant : $this->room;
    $url = '/api/v1/'.$kind.'/'.$record->getKey();

    $this->actingAs($this->admin)->deleteJson($url)->assertOk();

    $this->assertSoftDeleted($kind, [
        $record->getKeyName() => $record->getKey(),
    ]);

    expect(AuditEvent::sole()->entity_type)->toBe($kind);

    $this->deleteJson($url)->assertNotFound();
    $this->assertDatabaseCount('audit_events', 1);
})->with(['tenants', 'rooms']);

test('linked account always prevents tenant deletion', function (string $state) {
    $account = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => $this->tenant->getKey(),
        'is_active' => $state !== 'suspended',
        'must_change_password' => false,
    ]);

    if ($state === 'deleted') {
        $account->delete();
    }

    $this->actingAs($this->admin)
        ->deleteJson('/api/v1/tenants/'.$this->tenant->getKey())
        ->assertConflict()
        ->assertJsonPath('code', 'CONFLICT');

    expect($this->tenant->fresh()->deleted_at)->toBeNull();
    $this->assertDatabaseCount('audit_events', 0);
})->with(['active', 'suspended', 'deleted']);

test('business references prevent deletion including soft deleted history', function (
    string $kind,
    string $reference,
    bool $deleted
) {
    $deletedAt = $deleted ? now() : null;

    if ($reference === 'rentals') {
        DB::table('rentals')->insert([
            'tenants_t_id' => $this->tenant->getKey(),
            'rooms_r_id' => $this->room->getKey(),
            'rt_movein' => '2026-09-01',
            'rt_moveout' => '2026-09-02',
            'rt_status' => 'ENDED',
            'deleted_at' => $deletedAt,
        ]);
    } elseif ($reference === 'meters') {
        DB::table('meters')->insert([
            'rooms_r_id' => $this->room->getKey(),
            'm_date' => '2026-09-01',
            'm_water' => 100,
            'm_elec' => 1000,
            'deleted_at' => $deletedAt,
        ]);
    } else {
        DB::table('repairs')->insert([
            'rp_name' => 'Test repair',
            'rp_status' => 'REPORTED',
            'rp_type' => 'ROOM',
            'tenants_t_id' => $this->tenant->getKey(),
            'rooms_r_id' => $this->room->getKey(),
            'reported_by_user_id' => $this->admin->getKey(),
            'deleted_at' => $deletedAt,
        ]);
    }

    $record = $kind === 'tenants' ? $this->tenant : $this->room;

    $this->actingAs($this->admin)
        ->deleteJson('/api/v1/'.$kind.'/'.$record->getKey())
        ->assertConflict();

    expect($record->fresh()->deleted_at)->toBeNull();
    $this->assertDatabaseCount('audit_events', 0);
})->with([
    ['tenants', 'rentals', false],
    ['tenants', 'rentals', true],
    ['tenants', 'repairs', false],
    ['tenants', 'repairs', true],
    ['rooms', 'rentals', false],
    ['rooms', 'rentals', true],
    ['rooms', 'meters', false],
    ['rooms', 'meters', true],
    ['rooms', 'repairs', false],
    ['rooms', 'repairs', true],
]);

test('admin can inspect missing and deleted tenant account safely', function () {
    $url = '/api/v1/tenants/'.$this->tenant->getKey().'/account';

    $this->actingAs($this->admin)->getJson($url)
        ->assertOk()
        ->assertJsonPath('data.has_account', false)
        ->assertJsonPath('data.account', null);

    $account = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => $this->tenant->getKey(),
        'is_active' => false,
    ]);
    $account->delete();

    $response = $this->getJson($url)
        ->assertOk()
        ->assertJsonPath('data.has_account', true)
        ->assertJsonPath('data.account.is_deleted', true)
        ->assertJsonPath('data.account.is_active', false);

    $keys = array_keys($response->json('data.account'));
    sort($keys);

    expect($keys)->toBe([
        'is_active', 'is_deleted', 'must_change_password',
        'u_id', 'u_role', 'u_username',
    ]);
});

test('guest and tenant cannot access admin operations', function () {
    $tenantUrl = '/api/v1/tenants/'.$this->tenant->getKey();
    $roomUrl = '/api/v1/rooms/'.$this->room->getKey();

    $this->getJson($tenantUrl.'/account')->assertUnauthorized();
    $this->deleteJson($tenantUrl)->assertUnauthorized();
    $this->deleteJson($roomUrl)->assertUnauthorized();

    $user = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => $this->tenant->getKey(),
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->actingAs($user);
    $this->getJson($tenantUrl.'/account')->assertForbidden();
    $this->deleteJson($tenantUrl)->assertForbidden();
    $this->deleteJson($roomUrl)->assertForbidden();

    expect($this->tenant->fresh()->deleted_at)->toBeNull()
        ->and($this->room->fresh()->deleted_at)->toBeNull();

    $this->assertDatabaseCount('audit_events', 0);
});

test('audit failure rolls back deletion', function (string $kind) {
    $record = $kind === 'tenants' ? $this->tenant : $this->room;
    $attempted = false;

    AuditEvent::creating(function () use (&$attempted) {
        $attempted = true;
        throw new RuntimeException('Simulated audit failure');
    });

    try {
        $this->actingAs($this->admin)
            ->deleteJson('/api/v1/'.$kind.'/'.$record->getKey())
            ->assertStatus(500);

        expect($attempted)->toBeTrue()
            ->and($record->fresh()->deleted_at)->toBeNull();

        $this->assertDatabaseCount('audit_events', 0);
    } finally {
        AuditEvent::flushEventListeners();
    }
})->with(['tenants', 'rooms']);