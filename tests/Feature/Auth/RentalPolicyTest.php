<?php

use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'auth', 'password.changed'])
        ->get('/_test/rentals/{rental}', function (Rental $rental) {
            Gate::authorize('view', $rental);

            return response()->json([
                'data' => ['rt_id' => $rental->getKey()],
            ]);
        });

    $this->formerTenant = Tenant::create([
        't_Fname' => 'ผู้เช่าเดิม',
        't_Lname' => 'ทดสอบ',
        't_tel' => '0811111111',
    ]);

    $this->currentTenant = Tenant::create([
        't_Fname' => 'ผู้เช่าใหม่',
        't_Lname' => 'ทดสอบ',
        't_tel' => '0822222222',
    ]);

    $room = Room::create([
        'r_name' => 'TEST-101',
        'r_floor' => 1,
        'r_type' => 'STANDARD',
        'r_rent' => 3000,
        'r_status' => 'OCCUPIED',
    ]);

    $this->endedRental = Rental::create([
        'tenants_t_id' => $this->formerTenant->getKey(),
        'rooms_r_id' => $room->getKey(),
        'rt_movein' => '2026-07-01',
        'rt_moveout' => '2026-08-01',
        'rt_status' => 'ENDED',
    ]);

    $this->activeRental = Rental::create([
        'tenants_t_id' => $this->currentTenant->getKey(),
        'rooms_r_id' => $room->getKey(),
        'rt_movein' => '2026-08-01',
        'rt_status' => 'ACTIVE',
    ]);

    $this->formerUser = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => $this->formerTenant->getKey(),
        'must_change_password' => false,
    ]);

    $this->currentUser = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => $this->currentTenant->getKey(),
        'must_change_password' => false,
    ]);

    $this->admin = User::factory()->create([
        'must_change_password' => false,
    ]);
});

test('tenant can view their active rental', function () {
    $this->actingAs($this->currentUser)
        ->getJson('/_test/rentals/'.$this->activeRental->getKey())
        ->assertOk()
        ->assertJsonPath('data.rt_id', $this->activeRental->getKey());
});

test('former tenant can still view their ended rental', function () {
    $this->actingAs($this->formerUser)
        ->getJson('/_test/rentals/'.$this->endedRental->getKey())
        ->assertOk();
});

test('new tenant cannot view previous rental of the same room', function () {
    $this->actingAs($this->currentUser)
        ->getJson('/_test/rentals/'.$this->endedRental->getKey())
        ->assertNotFound();
});

test('former tenant cannot view current rental of the same room', function () {
    $this->actingAs($this->formerUser)
        ->getJson('/_test/rentals/'.$this->activeRental->getKey())
        ->assertNotFound();
});

test('admin can view rentals of both tenants', function () {
    $this->actingAs($this->admin);

    $this->getJson('/_test/rentals/'.$this->endedRental->getKey())
        ->assertOk();

    $this->getJson('/_test/rentals/'.$this->activeRental->getKey())
        ->assertOk();
});

test('only active admin can create rental and perform move out', function () {
    expect(Gate::forUser($this->admin)->allows('create', Rental::class))
        ->toBeTrue();

    expect(Gate::forUser($this->admin)->allows('moveOut', $this->activeRental))
        ->toBeTrue();

    expect(Gate::forUser($this->currentUser)->allows('create', Rental::class))
        ->toBeFalse();

    expect(Gate::forUser($this->currentUser)->allows('moveOut', $this->activeRental))
        ->toBeFalse();

    $this->admin->is_active = false;
    $this->admin->save();

    expect(Gate::forUser($this->admin)->allows('moveOut', $this->activeRental))
        ->toBeFalse();
});