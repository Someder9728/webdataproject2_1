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

    $this->tenant = Tenant::create([
        't_Fname' => 'Somchai',
        't_Lname' => 'Jaidee',
        't_tel' => '0812345678',
        't_mail' => 'old@example.test',
        't_address' => 'Old address',
    ]);

    $this->url = '/api/v1/tenants/'.$this->tenant->getKey();
});

test('admin patches selected fields and records audit', function () {
    $this->actingAs($this->admin)
        ->patchJson($this->url, [
            't_tel' => ' 0899999999 ',
        ])
        ->assertOk()
        ->assertJsonPath('data.t_tel', '0899999999')
        ->assertJsonPath('data.t_Fname', 'Somchai')
        ->assertJsonPath('data.t_mail', 'old@example.test');

    expect($this->tenant->fresh()->t_tel)->toBe('0899999999');

    $audit = AuditEvent::sole();

    expect($audit->actor_user_id)->toBe($this->admin->getKey())
        ->and((string) $audit->entity_id)
        ->toBe((string) $this->tenant->getKey())
        ->and($audit->action)->toBe('tenant_updated')
        ->and($audit->old_values)->toBe(['t_tel' => '0812345678'])
        ->and($audit->new_values)->toBe(['t_tel' => '0899999999']);

    $this->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.t_tel', '0899999999');
});

test('guest cannot patch tenant', function () {
    $this->patchJson($this->url, ['t_Fname' => 'Changed'])
        ->assertUnauthorized()
        ->assertJsonPath('code', 'UNAUTHENTICATED');

    expect($this->tenant->fresh()->t_Fname)->toBe('Somchai');

    $this->assertDatabaseCount('audit_events', 0);
});

test('tenant cannot patch their own details', function () {
    $user = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => $this->tenant->getKey(),
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->actingAs($user)
        ->patchJson($this->url, ['t_Fname' => 'Changed'])
        ->assertForbidden()
        ->assertJsonPath('code', 'FORBIDDEN');

    expect($this->tenant->fresh()->t_Fname)->toBe('Somchai');

    $this->assertDatabaseCount('audit_events', 0);
});

test('restricted admin cannot patch tenant', function (
    array $attributes,
    string $code
) {
    $this->admin->forceFill($attributes)->save();

    $this->actingAs($this->admin)
        ->patchJson($this->url, ['t_Fname' => 'Changed'])
        ->assertForbidden()
        ->assertJsonPath('code', $code);

    expect($this->tenant->fresh()->t_Fname)->toBe('Somchai');

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

test('invalid patch changes neither tenant nor audit', function () {
    $before = $this->tenant->fresh()->getAttributes();

    $this->actingAs($this->admin)
        ->patchJson($this->url, [
            't_Fname' => 'Changed',
            't_tel' => '123',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_FAILED')
        ->assertJsonValidationErrors('t_tel');

    expect($this->tenant->fresh()->getAttributes())->toBe($before);

    $this->assertDatabaseCount('audit_events', 0);
});

test('patch can clear optional fields', function () {
    $this->actingAs($this->admin)
        ->patchJson($this->url, [
            't_mail' => '',
            't_address' => null,
        ])
        ->assertOk()
        ->assertJsonPath('data.t_mail', null)
        ->assertJsonPath('data.t_address', null);

    expect($this->tenant->fresh()->t_mail)->toBeNull()
        ->and($this->tenant->fresh()->t_address)->toBeNull();

    $this->assertDatabaseCount('audit_events', 1);
});

test('protected fields are ignored while allowed changes are saved', function () {
    $id = $this->tenant->getKey();

    $this->actingAs($this->admin)
        ->patchJson($this->url, [
            't_tel' => '0899999999',
            't_id' => 999999,
            'u_role' => 'tenant',
            'actor_user_id' => 999999,
            'deleted_at' => '2026-09-17 10:00:00',
        ])
        ->assertOk()
        ->assertJsonPath('data.t_id', $id)
        ->assertJsonPath('data.t_tel', '0899999999')
        ->assertJsonMissingPath('data.u_role');

    expect($this->tenant->fresh()->deleted_at)->toBeNull()
        ->and($this->admin->fresh()->u_role)->toBe('admin')
        ->and(AuditEvent::sole()->actor_user_id)
        ->toBe($this->admin->getKey())
        ->and(AuditEvent::sole()->new_values)
        ->toBe(['t_tel' => '0899999999']);
});

test('repeating an unchanged patch does not duplicate audit', function () {
    $this->actingAs($this->admin);

    $this->patchJson($this->url, ['t_tel' => '0899999999'])
        ->assertOk();

    $this->patchJson($this->url, ['t_tel' => '0899999999'])
        ->assertOk();

    $this->assertDatabaseCount('audit_events', 1);
});

test('deleted tenant cannot be patched', function () {
    $this->tenant->delete();

    $this->actingAs($this->admin)
        ->patchJson($this->url, ['t_Fname' => 'Changed'])
        ->assertNotFound()
        ->assertJsonPath('code', 'NOT_FOUND');

    $stored = Tenant::withTrashed()->findOrFail($this->tenant->getKey());

    expect($stored->t_Fname)->toBe('Somchai')
        ->and($stored->deleted_at)->not->toBeNull();

    $this->assertDatabaseCount('audit_events', 0);
});

test('missing tenant cannot be patched', function () {
    $missingId = ((int) Tenant::withTrashed()->max('t_id')) + 1;

    $this->actingAs($this->admin)
        ->patchJson('/api/v1/tenants/'.$missingId, [
            't_Fname' => 'Changed',
        ])
        ->assertNotFound()
        ->assertJsonPath('code', 'NOT_FOUND');

    $this->assertDatabaseCount('audit_events', 0);
});