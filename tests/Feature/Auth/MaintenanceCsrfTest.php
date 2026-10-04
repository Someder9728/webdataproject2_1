<?php

use App\Models\Room;
use App\Models\User;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

test('maintenance writes require csrf and accept valid session token', function () {
    $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app->make(Encrypter::class)) extends PreventRequestForgery
    {
        protected function runningUnitTests()
        {
            return false;
        }
    });
    $admin = User::factory()->create(['u_role' => 'admin', 'is_active' => true, 'must_change_password' => false]);
    $room = Room::create(['r_name' => 'CSRF', 'r_floor' => 1, 'r_type' => 'STANDARD', 'r_rent' => 3000, 'r_status' => 'VACANT']);
    $token = str_repeat('a', 40);
    $this->actingAs($admin)->withSession(['_token' => $token]);
    $payload = ['rp_type' => 'COMMON', 'rp_name' => 'Broken light'];
    $this->postJson('/api/v1/repairs', $payload)->assertStatus(419);
    $created = $this->postJson('/api/v1/repairs', $payload, ['X-CSRF-TOKEN' => $token])->assertCreated();
    $url = '/api/v1/repairs/'.$created->json('data.rp_id').'/status';
    $next = ['expected_status' => 'REPORTED', 'rp_status' => 'IN_PROGRESS'];
    $this->patchJson($url, $next)->assertStatus(419);
    $this->patchJson($url, $next, ['X-CSRF-TOKEN' => $token])->assertOk();
    $url = '/api/v1/rooms/'.$room->getKey().'/meters';
    $meter = ['m_date' => '2026-09-30', 'm_water' => '100.20', 'm_elec' => '200.10'];
    $this->postJson($url, $meter)->assertStatus(419);
    $this->postJson($url, $meter, ['X-CSRF-TOKEN' => $token])->assertCreated();
});
