<?php

use App\Actions\Tenants\CreateTenant;
use App\Models\AuditEvent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'u_role' => 'admin',
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->input = [
        't_Fname' => ' Somchai ',
        't_Lname' => ' Jaidee ',
        't_tel' => '0812345678',
        't_mail' => '',
        't_address' => ' Test address ',
    ];
});

test('admin creates tenant with normalized data and audit', function () {
    $userCount = User::count();

    $tenant = app(CreateTenant::class)->handle(
        $this->admin,
        $this->input
    );

    expect($tenant->t_Fname)->toBe('Somchai')
        ->and($tenant->t_Lname)->toBe('Jaidee')
        ->and($tenant->t_mail)->toBeNull()
        ->and($tenant->t_address)->toBe('Test address');

    $audit = AuditEvent::sole();

    expect($audit->actor_user_id)->toBe($this->admin->getKey())
        ->and($audit->entity_type)->toBe('tenants')
        ->and((string) $audit->entity_id)->toBe((string) $tenant->getKey())
        ->and($audit->action)->toBe('tenant_created')
        ->and($audit->new_values['t_tel'])->toBe('0812345678');

    $this->assertDatabaseCount('tenants', 1);
    $this->assertDatabaseCount('users', $userCount);
});

test('client cannot assign tenant id or create account fields', function () {
    $tenant = app(CreateTenant::class)->handle(
        $this->admin,
        array_replace($this->input, [
            't_id' => 999999,
            'u_role' => 'admin',
            'u_username' => 'injected-account',
            'reported_by_user_id' => 999999,
        ])
    );

    expect($tenant->getKey())->not->toBe(999999);

    $this->assertDatabaseMissing('users', [
        'u_username' => 'injected-account',
    ]);

    $audit = AuditEvent::sole();

    expect(array_key_exists('u_role', $audit->new_values))->toBeFalse()
        ->and(array_key_exists('t_id', $audit->new_values))->toBeFalse();
});

test('invalid data creates neither tenant nor audit', function (
    string $field,
    mixed $value
) {
    try {
        app(CreateTenant::class)->handle(
            $this->admin,
            array_replace($this->input, [$field => $value])
        );

        $this->fail('Expected validation failure.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey($field);
    }

    $this->assertDatabaseCount('tenants', 0);
    $this->assertDatabaseCount('audit_events', 0);
})->with([
    'blank name' => ['t_Fname', '   '],
    'long surname' => ['t_Lname', str_repeat('a', 256)],
    'short phone' => ['t_tel', '08123'],
    'numeric phone' => ['t_tel', 812345678],
    'phone contains letters' => ['t_tel', '081234567x'],
    'invalid email' => ['t_mail', 'not-an-email'],
    'long address' => ['t_address', str_repeat('a', 2001)],
]);

test('action rechecks current account restrictions', function (array $changes) {
    // เปลี่ยนข้อมูลใน DB แต่ยังส่ง Model เดิมเข้า Action
    User::whereKey($this->admin->getKey())->update($changes);

    try {
        app(CreateTenant::class)->handle($this->admin, $this->input);

        $this->fail('Expected authorization failure.');
    } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
        expect($exception->status() ?? 403)->toBe(403);
    } catch (HttpExceptionInterface $exception) {
        expect($exception->getStatusCode())->toBe(403);
    }

    $this->assertDatabaseCount('tenants', 0);
    $this->assertDatabaseCount('audit_events', 0);
})->with([
    'tenant role' => [['u_role' => 'tenant']],
    'suspended admin' => [['is_active' => false]],
    'password change required' => [['must_change_password' => true]],
]);

test('audit failure rolls back tenant creation', function () {
    AuditEvent::creating(function () {
        throw new RuntimeException('Simulated audit failure');
    });

    try {
        expect(fn () => app(CreateTenant::class)->handle(
            $this->admin,
            $this->input
        ))->toThrow(RuntimeException::class, 'Simulated audit failure');

        $this->assertDatabaseCount('tenants', 0);
        $this->assertDatabaseCount('audit_events', 0);
    } finally {
        AuditEvent::flushEventListeners();
    }
});