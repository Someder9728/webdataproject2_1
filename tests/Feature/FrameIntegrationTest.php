<?php

use App\Models\AuditEvent;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->admin = User::factory()->create(['must_change_password' => false]);
});

test('integrated admin pages render with shared layout and retained Big routes', function (string $path) {
    $this->actingAs($this->admin)->get($path)->assertOk();
})->with(['/dashboard', '/rooms', '/rooms/create', '/tenants', '/tenants/create', '/admin/accounts', '/contracts', '/rentals', '/rentals/create', '/invoices', '/invoices/history']);

test('tenant cannot use admin pages or account form actions', function () {
    $tenantUser = User::factory()->create(['u_role' => 'tenant', 'must_change_password' => false]);
    foreach (['/rooms', '/tenants', '/contracts', '/admin/accounts'] as $path) {
        $this->actingAs($tenantUser)->get($path)->assertForbidden();
    }
    $this->actingAs($tenantUser)->post('/admin/accounts/create', [])->assertForbidden();
    $this->actingAs($tenantUser)->get('/my/rentals')->assertOk();
    $this->actingAs($tenantUser)->get('/my/contracts')->assertOk();
    $this->actingAs($tenantUser)->get('/invoices')->assertOk();
});

test('account forms use audited actions and remove sessions on password reset and suspension', function () {
    $tenant = Tenant::create(['t_Fname' => 'Form', 't_Lname' => 'Tenant', 't_tel' => '0812345678']);
    $this->actingAs($this->admin)->post('/admin/accounts/create', [
        'selectedTenantId' => $tenant->getKey(), 'username' => 'form.tenant',
        'password' => 'Temporary-Password-123', 'password_confirmation' => 'Temporary-Password-123',
    ])->assertRedirect(route('admin.accounts'));
    $account = User::where('tenants_t_id', $tenant->getKey())->sole();
    expect($account->must_change_password)->toBeTrue();
    expect(Hash::check('Temporary-Password-123', $account->u_password))->toBeTrue();
    expect(AuditEvent::where('action', 'tenant_account_created')->count())->toBe(1);

    $this->actingAs($this->admin)->post('/admin/accounts/'.$account->getKey().'/password', [
        'password' => 'New-Temporary-Password-123', 'password_confirmation' => 'New-Temporary-Password-123', 'reason' => 'Reset requested',
    ])->assertRedirect(route('admin.accounts'));
    expect(Hash::check('New-Temporary-Password-123', $account->fresh()->u_password))->toBeTrue();
    expect(AuditEvent::where('action', 'account_password_reset')->sole()->reason)->toBe('Reset requested');
    $this->actingAs($this->admin)->post('/admin/accounts/'.$account->getKey().'/suspend', ['reason' => 'Closed account'])->assertRedirect(route('admin.accounts'));
    expect($account->fresh()->is_active)->toBeFalse();
    expect(AuditEvent::where('action', 'account_suspended')->sole()->reason)->toBe('Closed account');
});

test('stale suspended admin cannot submit account forms', function () {
    $this->actingAs($this->admin)->get('/admin/accounts')->assertOk();
    User::whereKey($this->admin->getKey())->update(['is_active' => false]);
    $this->post('/admin/accounts/create', [])->assertStatus(403);
    expect(AuditEvent::count())->toBe(0);
});

test('room and tenant form writes retain audit and reference deletion protections', function () {
    $this->actingAs($this->admin)->post('/rooms', ['r_name' => 'FORM-1', 'r_floor' => 1, 'r_type' => 'เตียงเดี่ยว', 'r_rent' => '3000.00', 'r_status' => 'มีผู้พัก'])->assertRedirect(route('rooms.index'));
    $room = Room::where('r_name', 'FORM-1')->sole();
    expect($room->r_status)->toBe('VACANT');
    expect(AuditEvent::where('action', 'room_created')->count())->toBe(1);
    $this->post('/tenants', ['t_Fname' => 'Form', 't_Lname' => 'Owner', 't_tel' => '0812345678'])->assertRedirect(route('tenants.index'));
    $tenant = Tenant::sole();
    expect(AuditEvent::where('action', 'tenant_created')->count())->toBe(1);
    Rental::create(['tenants_t_id' => $tenant->getKey(), 'rooms_r_id' => $room->getKey(), 'rt_movein' => '2026-09-01', 'rt_status' => 'ACTIVE']);
    $this->deleteJson('/rooms/'.$room->getKey())->assertConflict();
    $this->deleteJson('/tenants/'.$tenant->getKey())->assertConflict();
    expect($room->fresh()->trashed())->toBeFalse();
    expect($tenant->fresh()->trashed())->toBeFalse();
});

test('dashboard occupancy excludes soft deleted active rental', function () {
    $room = Room::create(['r_name' => 'COUNT-1', 'r_floor' => 1, 'r_type' => 'เตียงเดี่ยว', 'r_rent' => '3000.00', 'r_status' => 'VACANT']);
    $tenant = Tenant::create(['t_Fname' => 'Deleted', 't_Lname' => 'Rental', 't_tel' => '0812345678']);
    $rental = Rental::create(['tenants_t_id' => $tenant->getKey(), 'rooms_r_id' => $room->getKey(), 'rt_movein' => '2026-09-01', 'rt_status' => 'ACTIVE']);
    $rental->delete();
    $this->actingAs($this->admin)->get('/dashboard')->assertOk()->assertViewHas('currentTenants', 0);
});

test('suspended account message is only exposed after correct password', function () {
    $user = User::factory()->create(['is_active' => false]);
    $this->post('/login', ['u_username' => $user->u_username, 'password' => 'Wrong-Password-123'])->assertSessionHasErrors('u_username');
    expect(str_contains(session('errors')->first('u_username'), 'ระงับ'))->toBeFalse();
    $this->post('/login', ['u_username' => $user->u_username, 'password' => 'Test-Password-1234'])->assertSessionHasErrors('u_username');
    expect(session('errors')->first('u_username'))->toContain('ระงับ');
});

test('account form requires csrf and accepts matching token', function () {
    $this->app->bind(PreventRequestForgery::class, function ($app) {
        return new class($app, $app->make(Encrypter::class)) extends PreventRequestForgery
        {
            protected function runningUnitTests()
            {
                return false;
            }
        };
    });
    $tenant = Tenant::create(['t_Fname' => 'CSRF', 't_Lname' => 'Form', 't_tel' => '0812345678']);
    $data = ['selectedTenantId' => $tenant->getKey(), 'username' => 'csrf.tenant', 'password' => 'Temporary-Password-123', 'password_confirmation' => 'Temporary-Password-123'];
    $token = str_repeat('a', 40);
    $this->actingAs($this->admin)->withSession(['_token' => $token])->post('/admin/accounts/create', $data)->assertStatus(419);
    expect(User::where('tenants_t_id', $tenant->getKey())->exists())->toBeFalse();
    $this->actingAs($this->admin)->withSession(['_token' => $token])->post('/admin/accounts/create', [...$data, '_token' => $token])->assertRedirect(route('admin.accounts'));
});
