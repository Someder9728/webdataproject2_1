<?php

use App\Actions\Accounts\CreateTenantAccount;
use App\Models\AuditEvent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'must_change_password' => false,
    ]);

    $this->tenant = Tenant::create([
        't_Fname' => 'ผู้เช่า',
        't_Lname' => 'ทดสอบ',
        't_tel' => '0811111111',
    ]);

    $this->input = [
        'u_username' => '  Tenant.Test  ',
        'password' => 'Temporary-Password-1234',
        'password_confirmation' => 'Temporary-Password-1234',
        'u_role' => 'admin',
        'is_active' => false,
        'must_change_password' => false,
    ];
});

test('admin creates tenant account with controlled fields and safe audit', function () {
    $user = app(CreateTenantAccount::class)->handle(
        $this->admin,
        $this->tenant,
        $this->input
    );

    expect($user->u_username)->toBe('tenant.test')
        ->and($user->u_role)->toBe('tenant')
        ->and($user->tenants_t_id)->toBe($this->tenant->getKey())
        ->and($user->is_active)->toBeTrue()
        ->and($user->must_change_password)->toBeTrue()
        ->and(Hash::check(
            'Temporary-Password-1234',
            $user->u_password
        ))->toBeTrue();

    $event = AuditEvent::sole();

    expect($event->action)->toBe('tenant_account_created')
        ->and($event->actor_user_id)->toBe($this->admin->getKey())
        ->and($event->entity_id)->toBe($user->getKey())
        ->and($event->new_values)->toBe([
            'u_username' => 'tenant.test',
            'u_role' => 'tenant',
            'tenants_t_id' => $this->tenant->getKey(),
            'is_active' => true,
            'must_change_password' => true,
        ]);
});

test('tenant cannot create an account', function () {
    $tenantUser = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => $this->tenant->getKey(),
        'must_change_password' => false,
    ]);

    try {
        app(CreateTenantAccount::class)->handle(
            $tenantUser,
            $this->tenant,
            $this->input
        );

        $this->fail('ต้องปฏิเสธด้วย HTTP 403');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(403);
    }

    $this->assertDatabaseCount('users', 2);
    $this->assertDatabaseCount('audit_events', 0);
});

test('one tenant cannot receive a second account', function () {
    app(CreateTenantAccount::class)->handle(
        $this->admin,
        $this->tenant,
        $this->input
    );

    $this->input['u_username'] = 'another.username';

    try {
        app(CreateTenantAccount::class)->handle(
            $this->admin,
            $this->tenant,
            $this->input
        );

        $this->fail('ต้องปฏิเสธบัญชีซ้ำ');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('tenants_t_id');
    }

    $this->assertDatabaseCount('users', 2);
    $this->assertDatabaseCount('audit_events', 1);
});

test('invalid password creates neither account nor audit', function () {
    $this->input['password'] = 'short';
    $this->input['password_confirmation'] = 'short';

    try {
        app(CreateTenantAccount::class)->handle(
            $this->admin,
            $this->tenant,
            $this->input
        );

        $this->fail('ต้องปฏิเสธรหัสผ่านสั้น');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('password');
    }

    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('audit_events', 0);
});