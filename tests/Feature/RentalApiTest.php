<?php

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

    $this->owner = Tenant::create([
        't_Fname' => 'Somchai',
        't_Lname' => 'Owner',
        't_tel' => '0811111111',
    ]);

    $this->other = Tenant::create([
        't_Fname' => 'Somsri',
        't_Lname' => 'Other',
        't_tel' => '0822222222',
    ]);

    $this->tenantUser = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => $this->owner->getKey(),
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $oldRoom = Room::create([
        'r_name' => 'OLD-101',
        'r_floor' => 1,
        'r_type' => 'เตียงเดี่ยว',
        'r_rent' => 3000,
        'r_status' => 'OCCUPIED',
    ]);

    $newRoom = Room::create([
        'r_name' => 'NEW-202',
        'r_floor' => 2,
        'r_type' => 'เตียงคู่',
        'r_rent' => 4000,
        'r_status' => 'OCCUPIED',
    ]);

    $this->ended = Rental::create([
        'tenants_t_id' => $this->owner->getKey(),
        'rooms_r_id' => $oldRoom->getKey(),
        'rt_movein' => '2026-07-01',
        'rt_moveout' => '2026-08-01',
        'rt_status' => 'ENDED',
    ]);

    // ผู้เช่าคนใหม่อยู่ห้องเดิมของ owner
    $this->otherRental = Rental::create([
        'tenants_t_id' => $this->other->getKey(),
        'rooms_r_id' => $oldRoom->getKey(),
        'rt_movein' => '2026-08-01',
        'rt_status' => 'ACTIVE',
    ]);

    $this->active = Rental::create([
        'tenants_t_id' => $this->owner->getKey(),
        'rooms_r_id' => $newRoom->getKey(),
        'rt_movein' => '2026-08-01',
        'rt_status' => 'ACTIVE',
    ]);

    foreach ([$this->ended, $this->otherRental, $this->active] as $rental) {
        $rental->forceFill([
            'created_at' => '2026-09-01 10:00:00',
        ])->save();
    }
});

test('rental api guest receives json 401 without accept header', function () {
    $this->get('/api/v1/rentals')
        ->assertUnauthorized()
        ->assertJsonPath('code', 'UNAUTHENTICATED');
});

test('admin rental list has stable pagination and formatted dates', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/v1/rentals?per_page=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.rt_id', $this->active->getKey())
        ->assertJsonPath('data.0.rt_movein', '2026-08-01')
        ->assertJsonPath('data.0.rt_moveout', null)
        ->assertJsonPath('data.0.tenant.t_id', $this->owner->getKey())
        ->assertJsonPath('data.0.room.r_name', 'NEW-202')
        ->assertJsonPath('meta.page', 1)
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.last_page', 3)
        ->assertJsonStructure([
            'message',
            'links' => ['first', 'last', 'next', 'prev'],
        ]);

    $this->getJson('/api/v1/rentals?per_page=1&page=2')
        ->assertOk()
        ->assertJsonPath('data.0.rt_id', $this->otherRental->getKey());

    $this->getJson('/api/v1/rentals?per_page=1&page=3')
        ->assertOk()
        ->assertJsonPath('data.0.rt_id', $this->ended->getKey())
        ->assertJsonPath('data.0.rt_moveout', '2026-08-01');
});

test('tenant sees only own active and ended rentals', function () {
    $response = $this->actingAs($this->tenantUser)
        ->getJson('/api/v1/rentals')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2);

    expect(array_column($response->json('data'), 'rt_id'))
        ->toBe([$this->active->getKey(), $this->ended->getKey()]);
});

test('rental search cannot escape tenant ownership', function () {
    $this->actingAs($this->tenantUser);

    // ค้นหาห้องเดิม ต้องเห็นเฉพาะประวัติของตนเอง
    $this->getJson('/api/v1/rentals?search=OLD-101')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.rt_id', $this->ended->getKey())
        ->assertJsonPath('meta.total', 1);

    // ทั้งชื่อผู้เช่าคนอื่นและ parameter ปลอมต้องไม่เปลี่ยนเจ้าของข้อมูล
    $this->getJson('/api/v1/rentals?'.http_build_query([
        'search' => 'Somsri',
        'tenants_t_id' => $this->other->getKey(),
        'u_role' => 'admin',
    ]))
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0);
});

test('tenant without linked record receives empty rental list', function () {
    $user = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => null,
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/rentals')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0);
});

test('admin can search rental tenant and room', function ($term, $expected) {
    $this->actingAs($this->admin)
        ->getJson('/api/v1/rentals?'.http_build_query(['search' => $term]))
        ->assertOk()
        ->assertJsonCount($expected, 'data')
        ->assertJsonPath('meta.total', $expected);
})->with([
    'tenant first name' => ['Somchai', 2],
    'tenant last name' => ['Other', 1],
    'tenant phone' => ['082222', 1],
    'room name' => ['NEW-202', 1],
    'no matches' => ['NOT-FOUND', 0],
]);

test('rental list excludes soft deleted records', function () {
    $this->ended->delete();

    $response = $this->actingAs($this->admin)
        ->getJson('/api/v1/rentals')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    expect(array_column($response->json('data'), 'rt_id'))
        ->not->toContain($this->ended->getKey());
});

test('rental list rejects invalid query input', function ($query, $field) {
    $this->actingAs($this->admin)
        ->getJson('/api/v1/rentals?'.http_build_query($query))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'zero page' => [['page' => 0], 'page'],
    'invalid page' => [['page' => 'abc'], 'page'],
    'zero page size' => [['per_page' => 0], 'per_page'],
    'oversized page' => [['per_page' => 101], 'per_page'],
    'long search' => [['search' => str_repeat('a', 101)], 'search'],
    'array search' => [['search' => ['test']], 'search'],
]);

test('inactive account cannot list rentals', function () {
    $this->tenantUser->forceFill(['is_active' => false])->save();
    $this->tenantUser->refresh();

    expect($this->tenantUser->is_active)->toBeFalse();

    $this->actingAs($this->tenantUser)
        ->getJson('/api/v1/rentals')
        ->assertForbidden()
        ->assertJsonPath('code', 'ACCOUNT_INACTIVE');
});

test('required password change blocks rental api and page', function () {
    $this->admin->forceFill(['must_change_password' => true])->save();
    $this->admin->refresh();

    expect($this->admin->must_change_password)->toBeTrue();

    $this->actingAs($this->admin)
        ->getJson('/api/v1/rentals')
        ->assertForbidden()
        ->assertJsonPath('code', 'PASSWORD_CHANGE_REQUIRED');

    $this->getJson('/rentals')
        ->assertForbidden()
        ->assertJsonPath('code', 'PASSWORD_CHANGE_REQUIRED');
});

test('rental admin page rejects guests and tenants but renders for admin', function () {
    $this->get('/rentals')->assertRedirect(route('login'));

    $this->actingAs($this->tenantUser)
        ->get('/rentals')
        ->assertForbidden();

    $this->actingAs($this->admin)
        ->get('/rentals')
        ->assertOk()
        ->assertSee('rental-table', false);
});

test('owner and admin can read active and ended rental details', function () {
    foreach ([$this->tenantUser, $this->admin] as $user) {
        $this->actingAs($user);

        foreach ([$this->active, $this->ended] as $rental) {
            $this->getJson('/api/v1/rentals/'.$rental->getKey())
                ->assertOk()
                ->assertJsonPath('data.rt_id', $rental->getKey())
                ->assertJsonPath(
                    'data.tenant.t_id',
                    $this->owner->getKey()
                );
        }
    }
});

test('owner and admin can read contract of active and ended rental', function () {
    foreach ([$this->active, $this->ended] as $rental) {
        $contract = $rental->contract()->create([
            'c_number' => 'TEST-'.$rental->getKey(),
            'c_start' => '2026-07-01',
            'c_end' => null,
            'c_rent' => '3000.50',
            'c_deposit' => '6000.00',
            'c_status' => 'ACTIVE',
        ]);

        foreach ([$this->tenantUser, $this->admin] as $user) {
            $this->actingAs($user)
                ->getJson(
                    '/api/v1/rentals/'.$rental->getKey().'/contract'
                )
                ->assertOk()
                ->assertJsonPath('data.c_id', $contract->getKey())
                ->assertJsonPath(
                    'data.rentals_rt_id',
                    $rental->getKey()
                )
                ->assertJsonPath('data.c_start', '2026-07-01')
                ->assertJsonPath('data.c_end', null)
                ->assertJsonPath('data.c_rent', '3000.50')
                ->assertJsonPath('data.c_deposit', '6000.00');
        }
    }
});

test('tenant cannot read another rental or its existing contract', function () {
    $this->otherRental->contract()->create([
        'c_number' => 'OTHER-CONTRACT',
        'c_start' => '2026-08-01',
        'c_end' => null,
        'c_rent' => '3000.00',
        'c_deposit' => '6000.00',
        'c_status' => 'ACTIVE',
    ]);

    $this->actingAs($this->tenantUser);

    foreach (['', '/contract'] as $suffix) {
        $this->getJson(
            '/api/v1/rentals/'.$this->otherRental->getKey().$suffix
        )
            ->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }
});

test('rental contract endpoint returns 404 when contract is missing or deleted', function () {
    $url = '/api/v1/rentals/'.$this->active->getKey().'/contract';

    $this->actingAs($this->tenantUser)
        ->getJson($url)
        ->assertNotFound();

    $contract = $this->active->contract()->create([
        'c_number' => 'DELETED-CONTRACT',
        'c_start' => '2026-08-01',
        'c_end' => null,
        'c_rent' => '4000.00',
        'c_deposit' => '8000.00',
        'c_status' => 'ACTIVE',
    ]);

    $contract->delete();

    $this->getJson($url)->assertNotFound();
});

test('deleted rental cannot be read through detail or contract endpoint', function () {
    $this->ended->delete();

    $this->actingAs($this->admin);

    foreach (['', '/contract'] as $suffix) {
        $this->getJson(
            '/api/v1/rentals/'.$this->ended->getKey().$suffix
        )->assertNotFound();
    }
});

test('rental detail and contract require authentication', function () {
    foreach (['', '/contract'] as $suffix) {
        $this->get(
            '/api/v1/rentals/'.$this->active->getKey().$suffix
        )
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }
});