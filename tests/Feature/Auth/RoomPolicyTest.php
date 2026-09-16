<?php

use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'auth', 'password.changed'])
        ->get('/_test/rooms/{room}', function (Room $room) {
            Gate::authorize('view', $room);

            return response()->json([
                'data' => [
                    'r_id' => $room->getKey(),
                    'r_name' => $room->r_name,
                ],
            ]);
        });

    $tenant = Tenant::create([
        't_Fname' => 'ผู้เช่า',
        't_Lname' => 'ทดสอบ',
        't_tel' => '0811111111',
    ]);

    $this->tenantUser = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => $tenant->getKey(),
        'must_change_password' => false,
    ]);

    $this->admin = User::factory()->create([
        'must_change_password' => false,
    ]);

    $this->room = Room::create([
        'r_name' => 'TEST-101',
        'r_floor' => 1,
        'r_type' => 'STANDARD',
        'r_rent' => 3000,
        'r_status' => 'OCCUPIED',
    ]);

    $this->otherRoom = Room::create([
        'r_name' => 'TEST-102',
        'r_floor' => 1,
        'r_type' => 'STANDARD',
        'r_rent' => 3000,
        'r_status' => 'VACANT',
    ]);

    $this->rental = Rental::create([
        'tenants_t_id' => $tenant->getKey(),
        'rooms_r_id' => $this->room->getKey(),
        'rt_movein' => '2026-08-01',
        'rt_status' => 'ACTIVE',
    ]);
});

test('tenant can view their currently rented room', function () {
    $this->actingAs($this->tenantUser)
        ->getJson('/_test/rooms/'.$this->room->getKey())
        ->assertOk()
        ->assertJsonPath('data.r_id', $this->room->getKey());
});

test('tenant cannot view another room', function () {
    $this->actingAs($this->tenantUser)
        ->getJson('/_test/rooms/'.$this->otherRoom->getKey())
        ->assertNotFound();
});

test('ended rental does not grant access to current room details', function () {
    $this->rental->rt_status = 'ENDED';
    $this->rental->rt_moveout = '2026-09-01';
    $this->rental->save();

    $this->actingAs($this->tenantUser)
        ->getJson('/_test/rooms/'.$this->room->getKey())
        ->assertNotFound();
});

test('admin can view any room', function () {
    $this->actingAs($this->admin);

    $this->getJson('/_test/rooms/'.$this->room->getKey())->assertOk();
    $this->getJson('/_test/rooms/'.$this->otherRoom->getKey())->assertOk();
});

test('tenant cannot list create or update rooms', function () {
    $gate = Gate::forUser($this->tenantUser);

    expect($gate->allows('viewAny', Room::class))->toBeFalse()
        ->and($gate->allows('create', Room::class))->toBeFalse()
        ->and($gate->allows('update', $this->room))->toBeFalse();
});

test('only active admin can manage rooms', function () {
    $gate = Gate::forUser($this->admin);

    expect($gate->allows('viewAny', Room::class))->toBeTrue()
        ->and($gate->allows('create', Room::class))->toBeTrue()
        ->and($gate->allows('update', $this->room))->toBeTrue();

    $this->admin->is_active = false;
    $this->admin->save();

    expect($gate->allows('viewAny', Room::class))->toBeFalse()
        ->and($gate->allows('create', Room::class))->toBeFalse()
        ->and($gate->allows('update', $this->room))->toBeFalse();
});