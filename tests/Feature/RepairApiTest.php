<?php

use App\Actions\Repairs\CreateRepair;
use App\Actions\Repairs\UpdateRepairStatus;
use App\Models\AuditEvent;
use App\Models\Rental;
use App\Models\Repair;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->admin = User::factory()->create(['u_role' => 'admin', 'is_active' => true, 'must_change_password' => false]);
    $this->tenant = Tenant::create(['t_Fname' => 'Repair', 't_Lname' => 'Tenant', 't_tel' => '0812345678']);
    $this->user = User::factory()->create(['u_role' => 'tenant', 'tenants_t_id' => $this->tenant->getKey(), 'is_active' => true, 'must_change_password' => false]);
    $this->room = Room::create(['r_name' => 'R101', 'r_floor' => 1, 'r_type' => 'STANDARD', 'r_rent' => 3000, 'r_status' => 'OCCUPIED']);
    $this->rental = Rental::create(['rooms_r_id' => $this->room->getKey(), 'tenants_t_id' => $this->tenant->getKey(), 'rt_movein' => '2026-09-01', 'rt_status' => 'ACTIVE']);
    $this->payload = ['rp_name' => 'Hallway light', 'rp_description' => 'Second floor', 'rp_type' => 'COMMON'];
});

test('tenant common repair retains reporter and tenant without room and records history audit', function () {
    $this->actingAs($this->user)->postJson('/api/v1/repairs', [...$this->payload, 'tenants_t_id' => 999999, 'reported_by_user_id' => $this->admin->getKey(), 'rp_status' => 'COMPLETED'])
        ->assertCreated()->assertJsonPath('data.rooms_r_id', null)
        ->assertJsonPath('data.tenants_t_id', $this->tenant->getKey())
        ->assertJsonPath('data.reported_by_user_id', $this->user->getKey())
        ->assertJsonPath('data.rp_status', 'REPORTED');
    $this->assertDatabaseCount('repair_histories', 1);
    $this->assertDatabaseCount('audit_events', 1);
    $id = Repair::firstOrFail()->getKey();
    $this->getJson("/api/v1/repairs/$id")->assertOk()->assertJsonCount(1, 'data.histories');
});

test('tenant requires active rental and can only report their room', function () {
    $other = Room::create(['r_name' => 'R102', 'r_floor' => 1, 'r_type' => 'STANDARD', 'r_rent' => 3000, 'r_status' => 'VACANT']);
    $this->actingAs($this->user)->postJson('/api/v1/repairs', [...$this->payload, 'rp_type' => 'ROOM', 'rooms_r_id' => $other->getKey()])->assertForbidden();
    $this->postJson('/api/v1/repairs', [...$this->payload, 'rp_type' => 'ROOM', 'rooms_r_id' => $this->room->getKey()])->assertCreated();
    $this->rental->update(['rt_status' => 'ENDED', 'rt_moveout' => '2026-09-30']);
    $this->postJson('/api/v1/repairs', $this->payload)->assertForbidden();
    $this->getJson('/api/v1/repairs')->assertOk()->assertJsonCount(1, 'data');
});

test('common forbids room and room requires existing nondeleted room', function () {
    $this->actingAs($this->admin)->postJson('/api/v1/repairs', [...$this->payload, 'rooms_r_id' => $this->room->getKey()])->assertUnprocessable();
    $this->postJson('/api/v1/repairs', [...$this->payload, 'rp_type' => 'ROOM'])->assertUnprocessable();
    $this->room->delete();
    $this->postJson('/api/v1/repairs', [...$this->payload, 'rp_type' => 'ROOM', 'rooms_r_id' => $this->room->getKey()])->assertUnprocessable();
    $this->assertDatabaseCount('repairs', 0);
});

test('unrelated tenant cannot list or read repair history including unassigned common', function () {
    $own = app(CreateRepair::class)->handle($this->user, $this->payload);
    $common = app(CreateRepair::class)->handle($this->admin, $this->payload);
    $unlinked = User::factory()->create(['u_role' => 'tenant', 'tenants_t_id' => null, 'is_active' => true, 'must_change_password' => false]);
    $this->actingAs($unlinked)->getJson('/api/v1/repairs')->assertOk()->assertJsonCount(0, 'data');
    foreach ([$own, $common] as $repair) {
        $this->getJson('/api/v1/repairs/'.$repair->getKey())->assertNotFound();
    }
    $this->postJson('/api/v1/repairs', $this->payload)->assertForbidden();
});

test('admin transitions sequentially with duplicate stale and tenant requests rejected', function () {
    $repair = app(CreateRepair::class)->handle($this->user, $this->payload);
    $url = '/api/v1/repairs/'.$repair->getKey().'/status';
    $next = ['expected_status' => 'REPORTED', 'rp_status' => 'IN_PROGRESS'];
    $this->actingAs($this->user)->patchJson($url, $next)->assertForbidden();
    $this->actingAs($this->admin)->patchJson($url, ['expected_status' => 'REPORTED', 'rp_status' => 'COMPLETED'])->assertUnprocessable();
    $this->patchJson($url, $next)->assertOk();
    $this->patchJson($url, $next)->assertConflict();
    $this->patchJson($url, ['rp_status' => 'COMPLETED'])->assertUnprocessable();
    $this->patchJson($url, ['expected_status' => 'IN_PROGRESS', 'rp_status' => 'COMPLETED'])->assertOk();
    $this->patchJson($url, ['expected_status' => 'COMPLETED', 'rp_status' => 'IN_PROGRESS'])->assertUnprocessable();
    $this->assertDatabaseCount('repair_histories', 3);
    $this->assertDatabaseCount('audit_events', 3);
    $this->getJson('/api/v1/repairs?rp_status=COMPLETED&per_page=1')->assertOk()->assertJsonPath('meta.total', 1);
});

test('repair create and transition roll back when audit fails', function () {
    $repair = app(CreateRepair::class)->handle($this->user, $this->payload);
    Event::listen('eloquent.creating: '.AuditEvent::class, fn () => throw new RuntimeException('audit unavailable'));
    try {
        expect(fn () => app(CreateRepair::class)->handle($this->user, $this->payload))->toThrow(RuntimeException::class);
        expect(fn () => app(UpdateRepairStatus::class)->handle($this->admin, $repair, ['expected_status' => 'REPORTED', 'rp_status' => 'IN_PROGRESS']))->toThrow(RuntimeException::class);
    } finally {
        Event::forget('eloquent.creating: '.AuditEvent::class);
    }
    expect($repair->fresh()->rp_status)->toBe('REPORTED');
    $this->assertDatabaseCount('repairs', 1);
    $this->assertDatabaseCount('repair_histories', 1);
    $this->assertDatabaseCount('audit_events', 1);
});

test('repair actions recheck account after caller loaded it', function (string $flag) {
    $repair = app(CreateRepair::class)->handle($this->admin, $this->payload);
    User::whereKey($this->admin->getKey())->update([$flag => $flag === 'must_change_password']);
    expect(fn () => app(UpdateRepairStatus::class)->handle($this->admin, $repair, ['expected_status' => 'REPORTED', 'rp_status' => 'IN_PROGRESS']))->toThrow(AuthorizationException::class);
    expect(fn () => app(CreateRepair::class)->handle($this->admin, $this->payload))->toThrow(AuthorizationException::class);
})->with(['is_active', 'must_change_password']);

test('maintenance pages obey roles and render', function () {
    $this->withoutVite();
    $this->actingAs($this->admin)->get('/meters')->assertOk()->assertSee('meter-form');
    $this->get('/repairs')->assertOk()->assertSee('repair-form');
    $this->actingAs($this->user)->get('/meters')->assertForbidden();
    $this->get('/repairs')->assertOk()->assertSee('repair-form');
});

test('guests cannot read or write repairs', function () {
    $this->getJson('/api/v1/repairs')->assertUnauthorized();
    $this->postJson('/api/v1/repairs', $this->payload)->assertUnauthorized();
});
