<?php

use App\Models\User;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    
    $this->app->bind(
        PreventRequestForgery::class,
        function ($app) {
            return new class(
                $app,
                $app->make(Encrypter::class)
            ) extends PreventRequestForgery {
                protected function runningUnitTests()
                {
                    return false;
                }
            };
        }
    );

    // Route นี้มีเฉพาะในชุดทดสอบ ไม่ต้องเพิ่มใน routes จริง
    Route::middleware(['web', 'auth', 'password.changed'])
        ->post('/api/v1/_test/csrf', function () {
            return response()->json([
                'data' => ['accepted' => true],
                'message' => 'Request accepted',
            ]);
        });

    $user = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->actingAs($user, 'web');

    $this->csrfToken = str_repeat('a', 40);

    $this->withSession([
        '_token' => $this->csrfToken,
    ]);
});

test('post without token or origin evidence is rejected', function () {
    $this->postJson('/api/v1/_test/csrf')
        ->assertStatus(419)
        ->assertJsonPath('code', 'CSRF_TOKEN_MISMATCH');
});

test('post with incorrect csrf token is rejected', function () {
    $this->postJson('/api/v1/_test/csrf', [], [
        'X-CSRF-TOKEN' => str_repeat('b', 40),
    ])->assertStatus(419)
    ->assertJsonPath('code', 'CSRF_TOKEN_MISMATCH');
});

test('post with matching session csrf token is accepted', function () {
    $this->postJson('/api/v1/_test/csrf', [], [
        'X-CSRF-TOKEN' => $this->csrfToken,
    ])
        ->assertOk()
        ->assertJsonPath('data.accepted', true);
});

test('cross-site post without csrf token is rejected', function () {
    $this->postJson('/api/v1/_test/csrf', [], [
        'Sec-Fetch-Site' => 'cross-site',
    ])->assertStatus(419)
    ->assertJsonPath('code', 'CSRF_TOKEN_MISMATCH');
});

test('same-origin post is accepted by framework origin verification', function () {
    $this->postJson('/api/v1/_test/csrf', [], [
        'Sec-Fetch-Site' => 'same-origin',
    ])
        ->assertOk()
        ->assertJsonPath('data.accepted', true);
});