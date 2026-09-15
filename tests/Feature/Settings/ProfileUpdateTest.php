<?php

use App\Models\User;
use Livewire\Livewire;

test('profile displays current account information', function () {
    $user = User::factory()->create([
        'must_change_password' => false,
    ]);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee($user->u_username)
        ->assertSee($user->u_role)
        ->assertDontSee('wire:submit="updateProfileInformation"', false)
        ->assertDontSee('wire:submit="deleteUser"', false);
});

test('profile requires initial password change', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertRedirect(route('security.edit'));
});

test('self deletion is denied and account remains unchanged', function () {
    $user = User::factory()->create([
        'must_change_password' => false,
    ]);

    Livewire::actingAs($user)
        ->test('pages::settings.delete-user-modal')
        ->call('deleteUser')
        ->assertForbidden();

    $this->assertDatabaseHas('users', [
        'u_id' => $user->getKey(),
        'deleted_at' => null,
        'is_active' => true,
    ]);

    $this->assertAuthenticatedAs($user);
});