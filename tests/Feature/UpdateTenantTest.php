<?php

use App\Actions\Tenants\UpdateTenant;
use App\Models\AuditEvent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'u_role' => 'admin',
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->tenant = Tenant::create([
        't_Fname' => 'Somchai',
        't_Lname' => 'Jaidee',
        't_tel' => '0812345678',
        't_mail' => 'old@example.test',
        't_address' => 'Old address',
    ]);
});

test('admin updates only supplied fields and records changes', function () {
    $tenant = app(UpdateTenant::class)->handle(
        $this->admin,
        $this->tenant,
        ['t_tel' => ' 0899999999 ']
    );

    expect($tenant->t_tel)->toBe('0899999999')
        ->and($tenant->t_Fname)->toBe('Somchai')
        ->and($tenant->t_mail)->toBe('old@example.test');

    $audit = AuditEvent::sole();

    expect($audit->actor_user_id)->toBe($this->admin->getKey())
        ->and($audit->action)->toBe('tenant_updated')
        ->and($audit->old_values)->toBe(['t_tel' => '0812345678'])
        ->and($audit->new_values)->toBe(['t_tel' => '0899999999']);

    $this->assertDatabaseCount('tenants', 1);
});

test('optional fields can be cleared', function () {
    $tenant = app(UpdateTenant::class)->handle(
        $this->admin,
        $this->tenant,
        ['t_mail' => '', 't_address' => null]
    );

    expect($tenant->t_mail)->toBeNull()
        ->and($tenant->t_address)->toBeNull();

    $this->assertDatabaseCount('audit_events', 1);
});

test('unchanged or ignored input does not create audit', function (array $input) {
    $before = $this->tenant->fresh()->getAttributes();

    app(UpdateTenant::class)->handle(
        $this->admin,
        $this->tenant,
        $input
    );

    expect($this->tenant->fresh()->getAttributes())->toBe($before);

    $this->assertDatabaseCount('audit_events', 0);
})->with([
    'empty input' => [[]],
    'same normalized value' => [['t_Fname' => ' Somchai ']],
    'protected fields' => [[
        't_id' => 999999,
        'u_role' => 'admin',
        'deleted_at' => '2026-09-17 10:00:00',
    ]],
]);

test('invalid update leaves tenant and audit unchanged', function (
    string $field,
    mixed $value
) {
    $before = $this->tenant->fresh()->getAttributes();

    try {
        app(UpdateTenant::class)->handle(
            $this->admin,
            $this->tenant,
            [$field => $value]
        );

        $this->fail('Expected validation failure.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey($field);
    }

    expect($this->tenant->fresh()->getAttributes())->toBe($before);

    $this->assertDatabaseCount('audit_events', 0);
})->with([
    'blank name' => ['t_Fname', ' '],
    'null surname' => ['t_Lname', null],
    'invalid phone' => ['t_tel', '123'],
    'invalid email' => ['t_mail', 'invalid-email'],
    'long address' => ['t_address', str_repeat('a', 2001)],
]);

test('update rechecks current actor restrictions', function (array $changes) {
    User::whereKey($this->admin->getKey())->update($changes);

    try {
        app(UpdateTenant::class)->handle(
            $this->admin,
            $this->tenant,
            ['t_Fname' => 'Changed']
        );

        $this->fail('Expected authorization failure.');
    } catch (AuthorizationException $exception) {
        expect($exception->status() ?? 403)->toBe(403);
    } catch (HttpExceptionInterface $exception) {
        expect($exception->getStatusCode())->toBe(403);
    }

    expect($this->tenant->fresh()->t_Fname)->toBe('Somchai');

    $this->assertDatabaseCount('audit_events', 0);
})->with([
    'tenant role' => [['u_role' => 'tenant']],
    'inactive admin' => [['is_active' => false]],
    'password change required' => [['must_change_password' => true]],
]);

test('deleted tenant cannot be updated through a stale model', function () {
    Tenant::whereKey($this->tenant->getKey())->delete();

    expect(fn () => app(UpdateTenant::class)->handle(
        $this->admin,
        $this->tenant,
        ['t_Fname' => 'Changed']
    ))->toThrow(ModelNotFoundException::class);

    $this->assertDatabaseCount('audit_events', 0);
});

test('audit failure rolls back tenant update', function () {
    AuditEvent::creating(function () {
        throw new RuntimeException('Simulated audit failure');
    });

    try {
        expect(fn () => app(UpdateTenant::class)->handle(
            $this->admin,
            $this->tenant,
            ['t_tel' => '0899999999']
        ))->toThrow(RuntimeException::class, 'Simulated audit failure');

        expect($this->tenant->fresh()->t_tel)->toBe('0812345678');

        $this->assertDatabaseCount('audit_events', 0);
    } finally {
        AuditEvent::flushEventListeners();
    }
});