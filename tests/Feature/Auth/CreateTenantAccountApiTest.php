<?php

use App\Models\AuditEvent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

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
    ]);

    $this->url = '/api/v1/tenants/'
        .$this->tenant->getKey().'/account';

    $this->payload = [
        'u_username' => ' Tenant.Test ',
        'password' => 'Temporary-Pass-1234',
        'password_confirmation' => 'Temporary-Pass-1234',
    ];
});

test('admin creates tenant account with controlled response and audit', function () {
    $response = $this->actingAs($this->admin)
        ->postJson($this->url, array_replace($this->payload, [
            'u_role' => 'admin',
            'is_active' => false,
            'must_change_password' => false,
            'tenants_t_id' => 999999,
        ]))
        ->assertCreated()
        ->assertJsonPath('data.u_username', 'tenant.test')
        ->assertJsonPath('data.u_role', 'tenant')
        ->assertJsonPath('data.tenants_t_id', $this->tenant->getKey())
        ->assertJsonPath('data.is_active', true)
        ->assertJsonPath('data.must_change_password', true);

    $keys = array_keys($response->json('data'));
    sort($keys);

    expect($keys)->toBe([
        'is_active',
        'must_change_password',
        'tenants_t_id',
        'u_id',
        'u_role',
        'u_username',
    ]);

    $user = User::findOrFail($response->json('data.u_id'));

    expect(Hash::check($this->payload['password'], $user->u_password))
        ->toBeTrue();

    $audit = AuditEvent::sole();

    expect($audit->actor_user_id)->toBe($this->admin->getKey())
        ->and($audit->action)->toBe('tenant_account_created')
        ->and((string) $audit->entity_id)->toBe((string) $user->getKey());

    expect(json_encode($audit->new_values))
        ->not->toContain($this->payload['password'])
        ->not->toContain($user->u_password);

    $this->assertDatabaseCount('users', 2);
});

test('guest cannot create tenant account', function () {
    $this->postJson($this->url, $this->payload)
        ->assertUnauthorized();

    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('audit_events', 0);
});

test('tenant cannot create an account', function () {
    $user = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => $this->tenant->getKey(),
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->actingAs($user)
        ->postJson($this->url, $this->payload)
        ->assertForbidden();

    $this->assertDatabaseCount('users', 2);
    $this->assertDatabaseCount('audit_events', 0);
});

test('second account for the same tenant is rejected', function () {
    $this->actingAs($this->admin)
        ->postJson($this->url, $this->payload)
        ->assertCreated();

    $this->postJson($this->url, array_replace($this->payload, [
        'u_username' => 'another.username',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('tenants_t_id');

    $this->assertDatabaseCount('users', 2);
    $this->assertDatabaseCount('audit_events', 1);
});

test('invalid account input changes neither users nor audit', function (
    array $changes,
    string $field
) {
    $this->actingAs($this->admin)
        ->postJson($this->url, array_replace($this->payload, $changes))
        ->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_FAILED')
        ->assertJsonValidationErrors($field);

    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('audit_events', 0);
})->with([
    'username array' => [
        ['u_username' => ['invalid']],
        'u_username',
    ],
    'invalid username' => [
        ['u_username' => 'has spaces'],
        'u_username',
    ],
    'short password' => [
        ['password' => 'short', 'password_confirmation' => 'short'],
        'password',
    ],
    'confirmation mismatch' => [
        ['password_confirmation' => 'Different-Pass-1234'],
        'password',
    ],
]);

test('deleted tenant cannot receive an account', function () {
    $this->tenant->delete();

    $this->actingAs($this->admin)
        ->postJson($this->url, $this->payload)
        ->assertNotFound();

    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('audit_events', 0);
});