<?php

use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'u_role' => 'admin',
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->tenantOne = Tenant::create([
        't_Fname' => 'Somchai',
        't_Lname' => 'Jaidee',
        't_tel' => '0811111111',
        't_mail' => 'somchai@example.test',
        't_address' => 'Address One',
    ]);

    $this->tenantTwo = Tenant::create([
        't_Fname' => 'Somsri',
        't_Lname' => 'Deejai',
        't_tel' => '0922222222',
        't_mail' => 'somsri@example.test',
        't_address' => 'Address Two',
    ]);

    $this->tenantUser = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => $this->tenantOne->getKey(),
        'is_active' => true,
        'must_change_password' => false,
    ]);
});

test('guest cannot access tenant endpoints', function () {
    $this->getJson('/api/v1/tenants')
        ->assertUnauthorized();

    $this->getJson('/api/v1/tenants/'.$this->tenantOne->getKey())
        ->assertUnauthorized();
});

test('admin can list tenants with pagination', function () {
    // กำหนดเวลาเท่ากันเพื่อทดสอบการเรียงด้วย Primary Key ต่อ
    $timestamp = '2026-09-16 10:00:00';

    $this->tenantOne->forceFill(['created_at' => $timestamp])->save();
    $this->tenantTwo->forceFill(['created_at' => $timestamp])->save();

    $this->actingAs($this->admin)
        ->getJson('/api/v1/tenants?per_page=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.t_id', $this->tenantTwo->getKey())
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('meta.last_page', 2);

    $this->getJson('/api/v1/tenants?per_page=1&page=2')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.t_id', $this->tenantOne->getKey())
        ->assertJsonPath('meta.current_page', 2);
});

test('admin can search tenants', function (string $search) {
    $this->actingAs($this->admin)
        ->getJson('/api/v1/tenants?'.http_build_query([
            'search' => $search,
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.t_id', $this->tenantOne->getKey())
        ->assertJsonPath('meta.total', 1);
})->with([
    'first name' => ['Somchai'],
    'last name' => ['Jaidee'],
    'full name' => ['Somchai Jaidee'],
    'phone fragment' => ['081111'],
]);

test('admin can search by exact tenant id', function () {
    
    $this->tenantOne->update(['t_tel' => '0888888888']);
    $this->tenantTwo->update(['t_tel' => '0999999999']);

    $this->actingAs($this->admin)
        ->getJson('/api/v1/tenants?'.http_build_query([
            'search' => (string) $this->tenantOne->getKey(),
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.t_id', $this->tenantOne->getKey());
});

test('search input is treated as literal text', function (string $search) {
    $this->actingAs($this->admin)
        ->getJson('/api/v1/tenants?'.http_build_query([
            'search' => $search,
        ]))
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0);
})->with([
    'percent' => ['%'],
    'underscore' => ['_'],
    'sql fragment' => ["' OR 1=1 --"],
]);

test('tenant cannot list all tenants', function () {
    $this->actingAs($this->tenantUser)
        ->getJson('/api/v1/tenants')
        ->assertForbidden()
        ->assertJsonPath('code', 'FORBIDDEN')
        ->assertJsonStructure(['message', 'errors', 'code']);
});

test('tenant can read their own details with controlled fields', function () {
    $response = $this->actingAs($this->tenantUser)
        ->getJson('/api/v1/tenants/'.$this->tenantOne->getKey())
        ->assertOk()
        ->assertJsonPath('data.t_id', $this->tenantOne->getKey())
        ->assertJsonPath('data.t_Fname', 'Somchai');

    $keys = array_keys($response->json('data'));
    sort($keys);

    expect($keys)->toBe([
        't_Fname',
        't_Lname',
        't_address',
        't_id',
        't_mail',
        't_tel',
    ]);
});

test('tenant cannot access another tenant by changing url id', function () {
    $this->actingAs($this->tenantUser)
        ->getJson('/api/v1/tenants/'.$this->tenantTwo->getKey())
        ->assertNotFound()
        ->assertJsonMissingPath('data');
});

test('admin can view another tenant', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/v1/tenants/'.$this->tenantTwo->getKey())
        ->assertOk()
        ->assertJsonPath('data.t_id', $this->tenantTwo->getKey());
});

test('soft deleted tenants are excluded from list and detail', function () {
    $id = $this->tenantTwo->getKey();
    $this->tenantTwo->delete();

    $this->actingAs($this->admin)
        ->getJson('/api/v1/tenants')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.t_id', $this->tenantOne->getKey())
        ->assertJsonPath('meta.total', 1);

    $this->getJson('/api/v1/tenants/'.$id)
        ->assertNotFound();
});

test('nonexistent tenant returns 404', function () {
    $missingId = ((int) Tenant::max('t_id')) + 1;

    $this->actingAs($this->admin)
        ->getJson('/api/v1/tenants/'.$missingId)
        ->assertNotFound()
        ->assertJsonPath('code', 'NOT_FOUND');
});

test('invalid list parameters are rejected', function (
    array $parameters,
    string $field
) {
    $this->actingAs($this->admin)
        ->getJson('/api/v1/tenants?'.http_build_query($parameters))
        ->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_FAILED')
        ->assertJsonValidationErrors($field);
})->with([
    'page zero' => [['page' => 0], 'page'],
    'page text' => [['page' => 'abc'], 'page'],
    'per page zero' => [['per_page' => 0], 'per_page'],
    'per page above limit' => [['per_page' => 101], 'per_page'],
    'search array' => [['search' => ['Somchai']], 'search'],
    'search too long' => [['search' => str_repeat('a', 101)], 'search'],
]);