<?php

use App\Models\AuditEvent;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'u_role' => 'admin',
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->payload = [
        't_Fname' => ' Somchai ',
        't_Lname' => ' Jaidee ',
        't_tel' => '0812345678',
        't_mail' => '',
        't_address' => ' Test address ',
    ];
});

test('admin creates tenant through api with audit', function () {
    $userCount = User::count();

    $response = $this->actingAs($this->admin)
        ->postJson('/api/v1/tenants', $this->payload)
        ->assertCreated()
        ->assertJsonPath('data.t_Fname', 'Somchai')
        ->assertJsonPath('data.t_Lname', 'Jaidee')
        ->assertJsonPath('data.t_mail', null)
        ->assertJsonPath('data.t_address', 'Test address');

    $id = $response->json('data.t_id');

    $this->assertDatabaseHas('tenants', [
        't_id' => $id,
        't_tel' => '0812345678',
    ]);

    $audit = AuditEvent::sole();

    expect((string) $audit->entity_id)->toBe((string) $id)
        ->and($audit->actor_user_id)->toBe($this->admin->getKey())
        ->and($audit->action)->toBe('tenant_created');

    $this->assertDatabaseCount('tenants', 1);
    $this->assertDatabaseCount('users', $userCount);

    // รายการที่สร้างต้องอ่านผ่าน API เดิมได้
    $this->getJson('/api/v1/tenants/'.$id)
        ->assertOk()
        ->assertJsonPath('data.t_id', $id);
});

test('guest cannot create tenant', function () {
    $this->postJson('/api/v1/tenants', $this->payload)
        ->assertUnauthorized()
        ->assertJsonPath('code', 'UNAUTHENTICATED');

    $this->assertDatabaseCount('tenants', 0);
    $this->assertDatabaseCount('audit_events', 0);
});

test('tenant account cannot create tenant', function () {
    $tenant = Tenant::create([
        't_Fname' => 'Existing',
        't_Lname' => 'Tenant',
        't_tel' => '0899999999',
    ]);

    $user = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => $tenant->getKey(),
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->actingAs($user)
        ->postJson('/api/v1/tenants', $this->payload)
        ->assertForbidden()
        ->assertJsonPath('code', 'FORBIDDEN');

    $this->assertDatabaseCount('tenants', 1);
    $this->assertDatabaseCount('audit_events', 0);
});

test('restricted admin cannot create tenant', function (
    array $attributes,
    string $code
) {
    $this->admin->forceFill($attributes)->save();

    $this->actingAs($this->admin)
        ->postJson('/api/v1/tenants', $this->payload)
        ->assertForbidden()
        ->assertJsonPath('code', $code);

    $this->assertDatabaseCount('tenants', 0);
    $this->assertDatabaseCount('audit_events', 0);
})->with([
    'inactive' => [
        ['is_active' => false],
        'ACCOUNT_INACTIVE',
    ],
    'password change required' => [
        ['must_change_password' => true],
        'PASSWORD_CHANGE_REQUIRED',
    ],
]);

test('invalid input returns validation errors without saving', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/v1/tenants', array_replace($this->payload, [
            't_Fname' => '',
            't_tel' => '123',
            't_mail' => 'invalid-email',
        ]))
        ->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_FAILED')
        ->assertJsonValidationErrors([
            't_Fname',
            't_tel',
            't_mail',
        ]);

    $this->assertDatabaseCount('tenants', 0);
    $this->assertDatabaseCount('audit_events', 0);
});

test('api ignores injected identity and account fields', function () {
    $response = $this->actingAs($this->admin)
        ->postJson('/api/v1/tenants', array_replace($this->payload, [
            't_id' => 999999,
            'u_role' => 'admin',
            'u_username' => 'injected-account',
            'actor_user_id' => 999999,
        ]))
        ->assertCreated()
        ->assertJsonMissingPath('data.u_role')
        ->assertJsonMissingPath('data.u_username');

    expect($response->json('data.t_id'))->not->toBe(999999)
        ->and(AuditEvent::sole()->actor_user_id)
        ->toBe($this->admin->getKey());

    $this->assertDatabaseMissing('users', [
        'u_username' => 'injected-account',
    ]);
});