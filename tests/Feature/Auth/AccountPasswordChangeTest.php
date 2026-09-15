<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->account = new User();
    $this->account->u_username = 'password_test_admin';
    $this->account->u_password = 'Temporary-Password-1234';
    $this->account->u_role = 'admin';
    $this->account->is_active = true;
    $this->account->must_change_password = true;
    $this->account->remember_token = 'original-remember-token';
    $this->account->save();
});

test('password change rejects invalid input without changing account', function (
    string $current,
    string $new,
    string $confirmation,
    string $errorField
) {
    $originalHash = $this->account->u_password;

    Livewire::actingAs($this->account)
        ->test('pages::settings.security')
        ->set('current_password', $current)
        ->set('password', $new)
        ->set('password_confirmation', $confirmation)
        ->call('updatePassword')
        ->assertHasErrors([$errorField]);

    $this->account->refresh();

    expect($this->account->u_password)->toBe($originalHash)
        ->and($this->account->must_change_password)->toBeTrue()
        ->and($this->account->remember_token)
        ->toBe('original-remember-token');
})->with([
    'wrong current password' => [
        'Wrong-Password-1234',
        'New-Password-5678',
        'New-Password-5678',
        'current_password',
    ],
    'new password too short' => [
        'Temporary-Password-1234',
        'short',
        'short',
        'password',
    ],
    'confirmation does not match' => [
        'Temporary-Password-1234',
        'New-Password-5678',
        'Different-Password-5678',
        'password',
    ],
    'new password equals old password' => [
        'Temporary-Password-1234',
        'Temporary-Password-1234',
        'Temporary-Password-1234',
        'password',
    ],
]);

test('password change updates account and clears its sessions', function () {
    foreach (['test-device-one', 'test-device-two'] as $sessionId) {
        DB::table('sessions')->insert([
            'id' => $sessionId,
            'user_id' => $this->account->getKey(),
            'payload' => base64_encode(serialize([])),
            'last_activity' => time(),
        ]);
    }

    // Session ที่ไม่ใช่ของบัญชีนี้ต้องยังอยู่
    DB::table('sessions')->insert([
        'id' => 'unrelated-guest-session',
        'user_id' => null,
        'payload' => base64_encode(serialize([])),
        'last_activity' => time(),
    ]);

    Livewire::actingAs($this->account)
        ->test('pages::settings.security')
        ->set('current_password', 'Temporary-Password-1234')
        ->set('password', 'New-Password-5678')
        ->set('password_confirmation', 'New-Password-5678')
        ->call('updatePassword')
        ->assertHasNoErrors()
        ->assertRedirect(route('login'));

    $this->assertGuest();

    $this->account->refresh();

    expect(Hash::check(
        'New-Password-5678',
        $this->account->u_password
    ))->toBeTrue();

    expect(Hash::check(
        'Temporary-Password-1234',
        $this->account->u_password
    ))->toBeFalse();

    expect($this->account->must_change_password)->toBeFalse()
        ->and($this->account->remember_token)
        ->not->toBe('original-remember-token')
        ->and($this->account->remember_token)->not->toBeEmpty();

    $this->assertDatabaseMissing('sessions', [
        'user_id' => $this->account->getKey(),
    ]);

    $this->assertDatabaseHas('sessions', [
        'id' => 'unrelated-guest-session',
    ]);

    // รหัสเดิมต้อง Login ไม่ได้
    $this->post(route('login.store'), [
        'u_username' => 'password_test_admin',
        'password' => 'Temporary-Password-1234',
    ])->assertSessionHasErrors('u_username');

    $this->assertGuest();

    // รหัสใหม่ต้อง Login และเข้า Dashboard ได้
    $this->post(route('login.store'), [
        'u_username' => 'password_test_admin',
        'password' => 'New-Password-5678',
    ])->assertRedirect();

    $this->assertAuthenticatedAs($this->account);

    $this->get('/dashboard')->assertOk();
});