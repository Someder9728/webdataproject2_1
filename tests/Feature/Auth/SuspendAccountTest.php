<?php

use App\Actions\Accounts\SuspendAccount;
use App\Models\AuditEvent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'must_change_password' => false,
    ]);

    $tenant = Tenant::create([
        't_Fname' => 'ผู้เช่า',
        't_Lname' => 'ทดสอบ',
        't_tel' => '0811111111',
    ]);

    $this->tenantUser = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => $tenant->getKey(),
        'must_change_password' => false,
        'remember_token' => 'old-token',
    ]);
});

test('admin suspends tenant and revokes access records', function () {
    DB::table('sessions')->insert([
        [
            'id' => 'tenant-session',
            'user_id' => $this->tenantUser->getKey(),
            'payload' => base64_encode(serialize([])),
            'last_activity' => time(),
        ],
        [
            'id' => 'admin-session',
            'user_id' => $this->admin->getKey(),
            'payload' => base64_encode(serialize([])),
            'last_activity' => time(),
        ],
    ]);

    app(SuspendAccount::class)->handle(
        $this->admin,
        $this->tenantUser,
        'ระงับเพื่อทดสอบ'
    );

    $target = $this->tenantUser->fresh();

    expect($target->is_active)->toBeFalse()
        ->and($target->deleted_at)->toBeNull()
        ->and($target->remember_token)->not->toBe('old-token');

    $this->assertDatabaseMissing('sessions', ['id' => 'tenant-session']);
    $this->assertDatabaseHas('sessions', ['id' => 'admin-session']);

    $event = AuditEvent::sole();

    expect($event->action)->toBe('account_suspended')
        ->and($event->actor_user_id)->toBe($this->admin->getKey())
        ->and($event->entity_id)->toBe($target->getKey())
        ->and($event->old_values)->toBe(['is_active' => true])
        ->and($event->new_values)->toBe(['is_active' => false]);
});

test('last active admin cannot be suspended', function () {
    try {
        app(SuspendAccount::class)->handle(
            $this->admin,
            $this->admin
        );

        $this->fail('ต้องปฏิเสธการระงับ Admin คนสุดท้าย');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(409);
    }

    expect($this->admin->fresh()->is_active)->toBeTrue();
    $this->assertDatabaseCount('audit_events', 0);
});

test('admin can suspend another admin while one remains active', function () {
    $anotherAdmin = User::factory()->create([
        'must_change_password' => false,
    ]);

    app(SuspendAccount::class)->handle(
        $this->admin,
        $anotherAdmin
    );

    expect($anotherAdmin->fresh()->is_active)->toBeFalse()
        ->and($this->admin->fresh()->is_active)->toBeTrue();

    $this->assertDatabaseCount('audit_events', 1);
});

test('tenant cannot suspend an account', function () {
    try {
        app(SuspendAccount::class)->handle(
            $this->tenantUser,
            $this->admin
        );

        $this->fail('ต้องปฏิเสธด้วย HTTP 403');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(403);
    }

    expect($this->admin->fresh()->is_active)->toBeTrue();
    $this->assertDatabaseCount('audit_events', 0);
});

test('repeated suspension does not create another audit event', function () {
    app(SuspendAccount::class)->handle(
        $this->admin,
        $this->tenantUser
    );

    try {
        app(SuspendAccount::class)->handle(
            $this->admin,
            $this->tenantUser
        );

        $this->fail('ต้องปฏิเสธการระงับซ้ำ');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(409);
    }

    $this->assertDatabaseCount('audit_events', 1);
});