<?php

use App\Models\AuditEvent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->admin = User::factory()->create(['u_role' => 'admin', 'is_active' => true, 'must_change_password' => false]);
    $this->payload = ['t_Fname' => 'ณัฐ', 't_Lname' => 'ใจดี', 't_tel' => '0800000020',
        'account' => ['u_username' => 'newtenant', 'password' => 'Temporary2026!', 'password_confirmation' => 'Temporary2026!']];
});
test('tenant and account are created atomically without exposing passwords', function () {
    $this->actingAs($this->admin)->postJson('/api/v1/tenants', $this->payload)->assertCreated()->assertJsonPath('data.account.u_username', 'newtenant')->assertJsonMissingPath('data.account.password');
    $tenant = Tenant::sole();
    $user = $tenant->user;
    expect($user->u_role)->toBe('tenant')->and($user->must_change_password)->toBeTrue()->and(Hash::check('Temporary2026!', $user->u_password))->toBeTrue()
        ->and($this->admin->tenant)->toBeNull();
    expect(AuditEvent::all()->toJson())->not->toContain('Temporary2026!');
});
test('duplicate username rolls back tenant and audit writes', function () {
    User::factory()->create(['u_username' => 'newtenant']);
    $this->actingAs($this->admin)->postJson('/api/v1/tenants', $this->payload)->assertUnprocessable()->assertJsonValidationErrors('u_username');
    $this->assertDatabaseCount('tenants', 0);
    $this->assertDatabaseCount('audit_events', 0);
});
test('web form creates both records and invalid credentials leave no tenant', function () {
    $this->actingAs($this->admin)->post('/tenants', $this->payload)->assertRedirect(route('tenants.index'));
    expect(Tenant::sole()->user->u_username)->toBe('newtenant');
    $this->post('/tenants', [...$this->payload, 'account' => [...$this->payload['account'], 'password_confirmation' => 'wrong']])->assertSessionHasErrors();
    $this->assertDatabaseCount('tenants', 1);
});
test('legacy client still creates an associated account requiring admin password reset', function () {
    unset($this->payload['account']);
    $this->actingAs($this->admin)->postJson('/api/v1/tenants', $this->payload)->assertCreated();
    expect(Tenant::sole()->user->u_role)->toBe('tenant')->and(Tenant::sole()->user->must_change_password)->toBeTrue();
});

test('legacy provisioning creates missing accounts once and preserves existing credentials', function () {
    $orphan = Tenant::create(['t_Fname' => 'ราย', 't_Lname' => 'เดิม', 't_tel' => '0800000021']);
    $migration = require database_path('migrations/2026_10_15_110000_provision_accounts_for_existing_tenants.php');
    $migration->up();
    $user = $orphan->fresh()->user;
    expect($user)->not->toBeNull()->and($user->u_role)->toBe('tenant')->and($user->must_change_password)->toBeTrue();
    $hash = $user->u_password;
    $migration->up();
    expect($orphan->fresh()->user->u_password)->toBe($hash);
    $this->assertDatabaseCount('users', 2);
    expect(AuditEvent::where('action', 'tenant_account_backfilled')->count())->toBe(1);
});
