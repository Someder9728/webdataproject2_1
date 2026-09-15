<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    config(['session.driver' => 'database']);

    $this->account = new User();
    $this->account->u_username = 'livewire_test_admin';
    $this->account->u_password = 'Test-Password-1234';
    $this->account->u_role = 'admin';
    $this->account->is_active = true;
    $this->account->must_change_password = false;
    $this->account->save();

    // Login ผ่าน HTTP เพื่อสร้างข้อมูลเข้าสู่ระบบใน Session
    $this->post(route('login.store'), [
        'u_username' => 'livewire_test_admin',
        'password' => 'Test-Password-1234',
    ])->assertRedirect();

    // จำลองผู้ใช้เปิดหน้า Profile ค้างไว้
    $response = $this->get(route('profile.edit'));
    $response->assertOk();

    $matched = preg_match(
        '/wire:snapshot="([^"]+)"/',
        $response->getContent(),
        $matches
    );

    expect($matched)->toBe(1);

    $this->snapshot = html_entity_decode(
        $matches[1],
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );
});

test('livewire request rejects an account suspended after page load', function () {
    $this->account->is_active = false;
    $this->account->save();

    // ให้คำขอถัดไปอ่าน User ใหม่จาก Session/Database
    Auth::forgetGuards();

    $this->postJson(
        app('livewire')->getUpdateUri(),
        [
            'components' => [[
                'snapshot' => $this->snapshot,
                'updates' => (object) [],
                'calls' => [[
                    'path' => '',
                    'method' => '$refresh',
                    'params' => [],
                ]],
            ]],
        ],
        ['X-Livewire' => 'true']
    )
        ->assertForbidden()
        ->assertJsonPath('code', 'ACCOUNT_INACTIVE');

    $this->assertGuest();
});

test('livewire request rechecks required password change after page load', function () {
    $this->account->must_change_password = true;
    $this->account->save();

    Auth::forgetGuards();

    $this->postJson(
        app('livewire')->getUpdateUri(),
        [
            'components' => [[
                'snapshot' => $this->snapshot,
                'updates' => (object) [],
                'calls' => [[
                    'path' => '',
                    'method' => '$refresh',
                    'params' => [],
                ]],
            ]],
        ],
        ['X-Livewire' => 'true']
    )
        ->assertForbidden()
        ->assertJsonPath('code', 'PASSWORD_CHANGE_REQUIRED');
});