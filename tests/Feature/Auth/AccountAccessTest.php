<?php

use App\Models\User;

beforeEach(function () {
    $this->account = new User();
    $this->account->u_username = 'auth_test_admin';
    $this->account->u_password = 'Test-Password-1234';
    $this->account->u_role = 'admin';
    $this->account->tenants_t_id = null;
    $this->account->is_active = true;
    $this->account->must_change_password = false;
    $this->account->save();
});

test('guest cannot access dashboard', function () {
    $this->get('/dashboard')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('active account can login with username', function () {
    $this->post(route('login.store'), [
        'u_username' => 'auth_test_admin',
        'password' => 'Test-Password-1234',
    ])->assertRedirect();

    $this->assertAuthenticatedAs($this->account);

    $this->get('/dashboard')->assertOk();
});

test('wrong password cannot login', function () {
    $this->post(route('login.store'), [
        'u_username' => 'auth_test_admin',
        'password' => 'Wrong-Password-1234',
    ])->assertSessionHasErrors('u_username');

    $this->assertGuest();
});

test('inactive account cannot login', function () {
    $this->account->is_active = false;
    $this->account->save();

    $this->post(route('login.store'), [
        'u_username' => 'auth_test_admin',
        'password' => 'Test-Password-1234',
    ])->assertSessionHasErrors('u_username');

    $this->assertGuest();
});

test('account requiring password change cannot access dashboard', function () {
    $this->account->must_change_password = true;
    $this->account->save();

    $this->actingAs($this->account)
        ->get('/dashboard')
        ->assertRedirect(route('security.edit'));

    $this->get(route('security.edit'))->assertOk();
});

test('inactive authenticated account is logged out', function () {
    $this->actingAs($this->account);

    $this->account->is_active = false;
    $this->account->save();

    $this->get('/dashboard')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('json request cannot bypass required password change', function () {
    $this->account->must_change_password = true;
    $this->account->save();

    $this->actingAs($this->account)
        ->getJson('/dashboard')
        ->assertForbidden()
        ->assertJsonPath('code', 'PASSWORD_CHANGE_REQUIRED');
});