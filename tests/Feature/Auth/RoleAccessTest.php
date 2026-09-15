<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'auth', 'password.changed'])
        ->group(function () {
            Route::get('/_test/admin-only', fn () => response()->json([
                'data' => ['allowed' => true],
            ]))->middleware('role:admin');

            Route::get('/_test/tenant-only', fn () => response()->json([
                'data' => ['allowed' => true],
            ]))->middleware('role:tenant');
        });

    $this->admin = User::factory()->create([
        'must_change_password' => false,
    ]);

    $tenant = Tenant::create([
        't_Fname' => 'ผู้เช่า',
        't_Lname' => 'ทดสอบ',
        't_tel' => '0812345678',
    ]);

    $this->tenantUser = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => $tenant->getKey(),
        'must_change_password' => false,
    ]);
});

test('admin can access admin route', function () {
    $this->actingAs($this->admin)
        ->getJson('/_test/admin-only')
        ->assertOk();
});

test('tenant cannot access admin route', function () {
    $this->actingAs($this->tenantUser)
        ->getJson('/_test/admin-only')
        ->assertForbidden()
        ->assertJsonPath('code', 'FORBIDDEN');
});

test('tenant can access tenant route', function () {
    $this->actingAs($this->tenantUser)
        ->getJson('/_test/tenant-only')
        ->assertOk();
});

test('admin cannot access tenant-only route', function () {
    $this->actingAs($this->admin)
        ->getJson('/_test/tenant-only')
        ->assertForbidden();
});

test('guest cannot access protected route', function () {
    $this->getJson('/_test/admin-only')
        ->assertUnauthorized();
});

test('client cannot claim admin role through request data', function () {
    $this->actingAs($this->tenantUser)
        ->getJson('/_test/admin-only?u_role=admin&role=admin')
        ->assertForbidden();
});