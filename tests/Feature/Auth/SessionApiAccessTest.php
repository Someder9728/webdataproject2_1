<?php

use App\Models\User;

test('api guest receives json 401 without accept header', function () {
    $this->get('/api/v1/me')
        ->assertUnauthorized()
        ->assertJsonPath('code', 'UNAUTHENTICATED')
        ->assertJsonStructure(['message', 'errors', 'code']);
});

test('active account can read its own account through session auth', function () {
    $user = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $response = $this->actingAs($user, 'web')
        ->get('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.u_id', $user->getKey())
        ->assertJsonPath('data.u_username', $user->u_username)
        ->assertJsonPath('data.u_role', $user->u_role)
        ->assertJsonPath('data.is_active', true)
        ->assertJsonPath('data.must_change_password', false);

    
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
});

test('api rejects inactive account without accept header', function () {
    $user = User::factory()->create([
        'is_active' => false,
        'must_change_password' => false,
    ]);

    $this->actingAs($user, 'web')
        ->get('/api/v1/me')
        ->assertForbidden()
        ->assertJsonPath('code', 'ACCOUNT_INACTIVE');

    $this->assertGuest('web');
});

test('api requires password change without accept header', function () {
    $user = User::factory()->create([
        'is_active' => true,
        'must_change_password' => true,
    ]);

    $this->actingAs($user, 'web')
        ->get('/api/v1/me')
        ->assertForbidden()
        ->assertJsonPath('code', 'PASSWORD_CHANGE_REQUIRED');
});

test('api cannot select another account through request parameters', function () {
    $user = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $other = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->actingAs($user, 'web')
        ->get('/api/v1/me?u_id='.$other->getKey())
        ->assertOk()
        ->assertJsonPath('data.u_id', $user->getKey())
        ->assertJsonPath('data.u_username', $user->u_username);
});