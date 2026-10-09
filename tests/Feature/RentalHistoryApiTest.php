<?php

use App\Actions\Rentals\UpdateContract;
use App\Models\AuditEvent;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['must_change_password' => false]);
    $this->tenant = Tenant::create(['t_Fname' => 'History', 't_Lname' => 'Owner', 't_tel' => '0812345678']);
    $room = Room::create(['r_name' => 'HISTORY-1', 'r_floor' => 1, 'r_type' => 'เตียงเดี่ยว', 'r_rent' => '3000.00', 'r_status' => 'OCCUPIED']);
    $this->rental = Rental::create(['tenants_t_id' => $this->tenant->getKey(), 'rooms_r_id' => $room->getKey(), 'rt_movein' => '2026-09-01', 'rt_status' => 'ACTIVE']);
    $this->contract = $this->rental->contract()->create(['c_number' => 'HISTORY-CONTRACT', 'c_start' => '2026-09-01', 'c_end' => null, 'c_rent' => '3000.00', 'c_deposit' => '6000.00', 'c_status' => 'ACTIVE']);
    $this->url = '/api/v1/rentals/'.$this->rental->getKey().'/history';
});

test('history reads persisted contract edits with actor reason and snapshots', function () {
    app(UpdateContract::class)->handle($this->admin, $this->rental, ['c_end' => '2027-01-01', 'reason' => 'Extend six months']);
    $this->actingAs($this->admin)->getJson($this->url)->assertOk()
        ->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.actor.u_id', $this->admin->getKey())
        ->assertJsonPath('data.0.actor.u_username', $this->admin->u_username)
        ->assertJsonPath('data.0.reason', 'Extend six months')
        ->assertJsonPath('data.0.old_values.c_end', null)
        ->assertJsonPath('data.0.new_values.c_end', '2027-01-01')
        ->assertJsonPath('data.0.action', 'contract_updated');
});

test('history scopes contract and action even if client asks for another entity', function () {
    foreach ([
        ['contracts', $this->contract->getKey(), 'contract_updated'],
        ['contract', $this->contract->getKey(), 'CONTRACT_EXTENDED'],
        ['contracts', $this->contract->getKey() + 100, 'contract_updated'],
        ['users', $this->contract->getKey(), 'contract_updated'],
        ['contracts', $this->contract->getKey(), 'account_password_reset'],
    ] as [$type, $id, $action]) {
        AuditEvent::create(['actor_user_id' => $this->admin->getKey(), 'entity_type' => $type, 'entity_id' => $id, 'action' => $action, 'new_values' => ['c_end' => '2027-01-01', 'password' => 'must-not-leak'], 'reason' => 'Edit']);
    }
    $response = $this->actingAs($this->admin)->getJson($this->url.'?entity_type=users&entity_id=999')
        ->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 2);
    expect($response->json('data.0.new_values'))->toBe(['c_end' => '2027-01-01']);
});

test('history works for ended rentals and soft deleted contracts', function () {
    app(UpdateContract::class)->handle($this->admin, $this->rental, ['c_end' => '2027-01-01', 'reason' => 'Extend']);
    $this->rental->update(['rt_status' => 'ENDED', 'rt_moveout' => '2026-10-01']);
    $this->contract->delete();
    $this->actingAs($this->admin)->getJson($this->url)->assertOk()->assertJsonCount(1, 'data');
});

test('history has stable pagination and tolerates an unavailable actor', function () {
    $ids = [];
    foreach (['First', 'Second', 'Third'] as $reason) {
        $event = AuditEvent::create(['actor_user_id' => null, 'entity_type' => 'contracts', 'entity_id' => $this->contract->getKey(), 'action' => 'contract_updated', 'reason' => $reason, 'created_at' => '2026-10-01 00:00:00']);
        $ids[] = $event->getKey();
    }
    $response = $this->actingAs($this->admin)->getJson($this->url.'?per_page=1&page=2')
        ->assertOk()->assertJsonPath('meta.total', 3)->assertJsonPath('meta.last_page', 3)
        ->assertJsonPath('data.0.ae_id', $ids[1])->assertJsonPath('data.0.actor', null);
    expect($response->json('data.0.created_at'))->toEndWith('+07:00');
});

test('tenant including owner cannot read contract audit', function (bool $owner) {
    $user = User::factory()->create(['u_role' => 'tenant', 'must_change_password' => false, 'tenants_t_id' => $owner ? $this->tenant->getKey() : null]);
    $this->actingAs($user)->getJson($this->url)->assertForbidden();
})->with([true, false]);

test('history denies guests and restricted admins', function () {
    $this->getJson($this->url)->assertUnauthorized();
    $this->admin->forceFill(['must_change_password' => true])->save();
    $this->actingAs($this->admin->fresh())->getJson($this->url)->assertForbidden();
    $this->admin->forceFill(['must_change_password' => false, 'is_active' => false])->save();
    $this->actingAs($this->admin->fresh())->getJson($this->url)->assertForbidden();
});

test('history requires an existing rental and validates page limits', function () {
    $this->actingAs($this->admin)->getJson('/api/v1/rentals/99999/history')->assertNotFound();
    $this->actingAs($this->admin)->getJson($this->url.'?per_page=101&page=0')
        ->assertUnprocessable()->assertJsonValidationErrors(['per_page', 'page']);
    $this->actingAs($this->admin)->getJson($this->url)->assertOk()->assertJsonCount(0, 'data');
    $this->rental->delete();
    $this->actingAs($this->admin)->getJson($this->url)->assertNotFound();
});
