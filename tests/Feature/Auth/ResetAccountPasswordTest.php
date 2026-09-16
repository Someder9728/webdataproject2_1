<?php

use App\Actions\Accounts\ResetAccountPassword;
use App\Models\AuditEvent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
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

    $this->target = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => $tenant->getKey(),
        'must_change_password' => false,
        'remember_token' => 'old-remember-token',
    ]);

    $this->input = [
        'password' => 'Temporary-New-Password-5678',
        'password_confirmation' => 'Temporary-New-Password-5678',
        'reason' => 'ผู้ใช้ขอรีเซ็ตรหัสผ่าน',
    ];
});

test('admin resets password revokes sessions and records safe audit', function () {
    DB::table('sessions')->insert([
        [
            'id' => 'target-session',
            'user_id' => $this->target->getKey(),
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

    app(ResetAccountPassword::class)->handle(
        $this->admin,
        $this->target,
        $this->input
    );

    $this->target->refresh();

    expect(Hash::check(
        'Temporary-New-Password-5678',
        $this->target->u_password
    ))->toBeTrue();

    expect(Hash::check(
        'Test-Password-1234',
        $this->target->u_password
    ))->toBeFalse();

    expect($this->target->must_change_password)->toBeTrue()
        ->and($this->target->remember_token)
        ->not->toBe('old-remember-token');

    $this->assertDatabaseMissing('sessions', ['id' => 'target-session']);
    $this->assertDatabaseHas('sessions', ['id' => 'admin-session']);

    $event = AuditEvent::sole();

    expect($event->actor_user_id)->toBe($this->admin->getKey())
        ->and($event->entity_id)->toBe($this->target->getKey())
        ->and($event->action)->toBe('account_password_reset')
        ->and($event->old_values)->toBe([
            'must_change_password' => false,
        ])
        ->and($event->new_values)->toBe([
            'must_change_password' => true,
        ]);
});

test('password reset does not reactivate suspended account', function () {
    $this->target->is_active = false;
    $this->target->save();

    app(ResetAccountPassword::class)->handle(
        $this->admin,
        $this->target,
        $this->input
    );

    expect($this->target->fresh()->is_active)->toBeFalse();
});

test('tenant and inactive admin cannot reset passwords', function () {
    $originalHash = $this->target->u_password;

    $this->admin->is_active = false;
    $this->admin->save();

    foreach ([$this->target, $this->admin] as $actor) {
        try {
            app(ResetAccountPassword::class)->handle(
                $actor,
                $this->target,
                $this->input
            );

            $this->fail('ต้องปฏิเสธด้วย HTTP 403');
        } catch (HttpException $exception) {
            expect($exception->getStatusCode())->toBe(403);
        }
    }

    expect($this->target->fresh()->u_password)->toBe($originalHash);
    $this->assertDatabaseCount('audit_events', 0);
});

test('invalid reset leaves password token and flag unchanged', function () {
    $originalHash = $this->target->u_password;

    $this->input['password'] = 'short';
    $this->input['password_confirmation'] = 'short';

    try {
        app(ResetAccountPassword::class)->handle(
            $this->admin,
            $this->target,
            $this->input
        );

        $this->fail('ต้องปฏิเสธรหัสผ่านสั้น');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('password');
    }

    $this->target->refresh();

    expect($this->target->u_password)->toBe($originalHash)
        ->and($this->target->remember_token)->toBe('old-remember-token')
        ->and($this->target->must_change_password)->toBeFalse();

    $this->assertDatabaseCount('audit_events', 0);
});