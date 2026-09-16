<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'auth', 'password.changed'])
        ->group(function () {
            Route::get('/_test/tenants', function () {
                Gate::authorize('viewAny', Tenant::class);

                return response()->json(['data' => []]);
            });

            Route::get('/_test/tenants/{tenant}', function (Tenant $tenant) {
                Gate::authorize('view', $tenant);

                return response()->json([
                    'data' => ['t_id' => $tenant->getKey()],
                ]);
            });

            Route::patch('/_test/tenants/{tenant}', function (Tenant $tenant) {
                Gate::authorize('update', $tenant);

                // ทดสอบสิทธิ์เท่านั้น ยังไม่แก้ข้อมูลจริง
                return response()->json(['data' => ['allowed' => true]]);
            });
        });

    $this->owner = Tenant::create([
        't_Fname' => 'ผู้เช่า',
        't_Lname' => 'คนแรก',
        't_tel' => '0811111111',
    ]);

    $this->other = Tenant::create([
        't_Fname' => 'ผู้เช่า',
        't_Lname' => 'คนที่สอง',
        't_tel' => '0822222222',
    ]);

    $this->tenantUser = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => $this->owner->getKey(),
        'must_change_password' => false,
    ]);

    $this->admin = User::factory()->create([
        'must_change_password' => false,
    ]);
});

test('tenant can view their own record', function () {
    $this->actingAs($this->tenantUser)
        ->getJson('/_test/tenants/'.$this->owner->getKey())
        ->assertOk()
        ->assertJsonPath('data.t_id', $this->owner->getKey());
});

test('tenant cannot view another record by changing url id', function () {
    $this->actingAs($this->tenantUser)
        ->getJson('/_test/tenants/'.$this->other->getKey())
        ->assertNotFound();
});

test('tenant cannot list all tenants', function () {
    $this->actingAs($this->tenantUser)
        ->getJson('/_test/tenants')
        ->assertForbidden();
});

test('tenant cannot update their own tenant details', function () {
    $this->actingAs($this->tenantUser)
        ->patchJson('/_test/tenants/'.$this->owner->getKey(), [
            't_Fname' => 'ชื่อที่พยายามแก้',
        ])
        ->assertForbidden();

    expect($this->owner->fresh()->t_Fname)->toBe('ผู้เช่า');
});

test('admin can list view and update tenant records', function () {
    $this->actingAs($this->admin);

    $this->getJson('/_test/tenants')->assertOk();

    $this->getJson('/_test/tenants/'.$this->other->getKey())
        ->assertOk();

    $this->patchJson('/_test/tenants/'.$this->other->getKey())
        ->assertOk();
});

test('only active admin can create tenant records', function () {
    expect(
        Gate::forUser($this->admin)->allows('create', Tenant::class)
    )->toBeTrue();

    expect(
        Gate::forUser($this->tenantUser)->allows('create', Tenant::class)
    )->toBeFalse();

    $this->admin->is_active = false;
    $this->admin->save();

    expect(
        Gate::forUser($this->admin)->allows('create', Tenant::class)
    )->toBeFalse();
});