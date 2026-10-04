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

    $tenant = Tenant::create([
        't_Fname' => 'Account',
        't_Lname' => 'Test',
        't_tel' => '0812345678',
    ]);

    $this->account = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => $tenant->getKey(),
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->url = '/api/v1/accounts/'.$this->account->getKey();

    $this->resetInput = [
        'password' => 'New-Temporary-Pass-1234',
        'password_confirmation' => 'New-Temporary-Pass-1234',
        'reason' => 'Requested by tenant',
    ];
});

test('admin resets password without reactivating suspended account', function () {
    $this->account->forceFill(['is_active' => false])->save();
    $oldToken = $this->account->remember_token;

    $response = $this->actingAs($this->admin)
        ->postJson($this->url.'/reset-password', $this->resetInput)
        ->assertOk()
        ->assertJsonPath('data.u_id', $this->account->getKey())
        ->assertJsonPath('data.is_active', false)
        ->assertJsonPath('data.must_change_password', true);

    $account = $this->account->fresh();

    expect(Hash::check($this->resetInput['password'], $account->u_password))
        ->toBeTrue()
        ->and($account->remember_token)->not->toBe($oldToken);

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

    $audit = AuditEvent::sole();

    expect($audit->action)->toBe('account_password_reset')
        ->and($audit->actor_user_id)->toBe($this->admin->getKey())
        ->and(json_encode($audit->new_values))
        ->not->toContain($this->resetInput['password'])
        ->not->toContain($account->u_password);
});

test('admin suspends account and repeated suspension returns conflict', function () {
    $this->actingAs($this->admin)
        ->postJson($this->url.'/suspend', ['reason' => 'Account closed'])
        ->assertOk()
        ->assertJsonPath('data.is_active', false)
        ->assertJsonMissingPath('data.u_password')
        ->assertJsonMissingPath('data.remember_token');

    expect($this->account->fresh()->is_active)->toBeFalse()
        ->and(AuditEvent::sole()->action)->toBe('account_suspended');

    $this->postJson($this->url.'/suspend')
        ->assertConflict()
        ->assertJsonPath('code', 'CONFLICT');

    $this->assertDatabaseCount('audit_events', 1);
});

test('last active admin cannot be suspended through api', function () {
    $this->actingAs($this->admin)
        ->postJson(
            '/api/v1/accounts/'.$this->admin->getKey().'/suspend'
        )
        ->assertConflict()
        ->assertJsonPath('code', 'CONFLICT');

    expect($this->admin->fresh()->is_active)->toBeTrue();

    $this->assertDatabaseCount('audit_events', 0);
});

test('guest cannot manage accounts', function (string $operation) {
    $before = $this->account->fresh()->getAttributes();

    $this->postJson($this->url.'/'.$operation, $this->resetInput)
        ->assertUnauthorized();

    expect($this->account->fresh()->getAttributes())->toBe($before);

    $this->assertDatabaseCount('audit_events', 0);
})->with(['reset-password', 'suspend']);

test('tenant cannot manage accounts', function (string $operation) {
    $before = $this->account->fresh()->getAttributes();

    $this->actingAs($this->account)
        ->postJson($this->url.'/'.$operation, $this->resetInput)
        ->assertForbidden();

    expect($this->account->fresh()->getAttributes())->toBe($before);

    $this->assertDatabaseCount('audit_events', 0);
})->with(['reset-password', 'suspend']);

test('invalid request cannot change account', function (
    string $operation,
    array $input,
    string $field
) {
    $before = $this->account->fresh()->getAttributes();

    $this->actingAs($this->admin)
        ->postJson($this->url.'/'.$operation, $input)
        ->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_FAILED')
        ->assertJsonValidationErrors($field);

    expect($this->account->fresh()->getAttributes())->toBe($before);

    $this->assertDatabaseCount('audit_events', 0);
})->with([
    'short password' => [
        'reset-password',
        ['password' => 'short', 'password_confirmation' => 'short'],
        'password',
    ],
    'reason array' => [
        'suspend',
        ['reason' => ['invalid']],
        'reason',
    ],
]);

test('missing account returns not found', function (string $operation) {
    $missingId = ((int) User::withTrashed()->max('u_id')) + 1;

    $this->actingAs($this->admin)
        ->postJson(
            '/api/v1/accounts/'.$missingId.'/'.$operation,
            $this->resetInput
        )
        ->assertNotFound()
        ->assertJsonPath('code', 'NOT_FOUND');

    $this->assertDatabaseCount('audit_events', 0);
})->with(['reset-password', 'suspend']);