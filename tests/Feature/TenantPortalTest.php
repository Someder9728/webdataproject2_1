<?php

use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->tenant = Tenant::create(['t_Fname' => 'PortalOwner', 't_Lname' => 'Tenant', 't_tel' => '0800000001', 't_mail' => 'owner@example.test']);
    $this->other = Tenant::create(['t_Fname' => 'PrivateOther', 't_Lname' => 'Tenant', 't_tel' => '0800000002', 't_mail' => 'private@example.test']);
    $this->user = User::factory()->create(['u_role' => 'tenant', 'tenants_t_id' => $this->tenant->getKey(), 'must_change_password' => false]);
    foreach ([$this->tenant, $this->other] as $index => $tenant) {
        $room = Room::create(['r_name' => 'PORTAL-'.($index + 1), 'r_floor' => 1, 'r_type' => 'เตียงเดี่ยว', 'r_rent' => '3000.00', 'r_status' => 'OCCUPIED']);
        $rental = Rental::create(['tenants_t_id' => $tenant->getKey(), 'rooms_r_id' => $room->getKey(), 'rt_movein' => '2026-09-15', 'rt_status' => 'ACTIVE']);
        $rental->contract()->create(['c_number' => 'PORTAL-C'.($index + 1), 'c_start' => '2026-09-15', 'c_end' => '2027-09-15', 'c_rent' => '3000.00', 'c_deposit' => '6000.00', 'c_status' => 'ACTIVE']);
        $this->rentals[$index] = $rental;
    }
});

test('tenant portal renders only owned rentals contracts and contact data', function () {
    $this->actingAs($this->user)->get('/my/rentals')->assertOk()->assertSee('PORTAL-1')->assertDontSee('PORTAL-2');
    $this->get('/my/contracts')->assertOk()->assertSee('PORTAL-C1')->assertSee('2027-09-15')->assertDontSee('PORTAL-C2');
    $this->get('/rentals/'.$this->rentals[0]->getKey())->assertOk()->assertSee('owner@example.test')->assertDontSee('private@example.test');
    $this->get('/rentals/'.$this->rentals[1]->getKey())->assertNotFound();
    $this->get('/my/profile')->assertOk()->assertSee('owner@example.test')->assertDontSee('private@example.test');
});

test('tenant menu has unique working routes and admin operations remain forbidden', function () {
    $response = $this->actingAs($this->user)->get('/my/rentals')->assertOk();
    preg_match('/<div class="sidebar-menu">(.*?)<\/div>/s', $response->getContent(), $match);
    expect($match[1])->not->toContain('href="#"');
    foreach (['/my/rentals', '/my/contracts', '/invoices', '/invoices/history', '/repairs', '/my/profile'] as $path) {
        expect(substr_count($match[1], 'href="'.url($path).'"'))->toBe(1);
        $this->get($path)->assertOk();
    }
    $this->get('/meters')->assertForbidden();
    $this->get('/rooms')->assertForbidden();
    $this->patchJson('/api/v1/rentals/'.$this->rentals[0]->getKey().'/contract', ['c_end' => '2028-09-15', 'reason' => 'No tenant editing'])->assertForbidden();
});

test('tenant password page uses shared styling without duplicate settings headings', function () {
    $this->actingAs($this->user)->get('/settings/security')->assertOk()->assertSee('tenant-current-password', false)->assertDontSee('Manage your profile and account settings');
    $this->user->forceFill(['must_change_password' => true])->save();
    $this->get('/settings/security')->assertOk()->assertSee('กรุณาเปลี่ยนรหัสผ่านชั่วคราวก่อนเข้าใช้งานระบบ');
});

test('tenant history paginates instead of silently truncating and users without tenants see empty states', function () {
    $roomId = $this->rentals[0]->rooms_r_id;
    for ($i = 1; $i <= 21; $i++) {
        Rental::create(['tenants_t_id' => $this->tenant->getKey(), 'rooms_r_id' => $roomId,
            'rt_movein' => '2020-01-01', 'rt_moveout' => '2020-01-02', 'rt_status' => 'ENDED']);
    }
    $this->actingAs($this->user)->get('/my/rentals?page=2')->assertOk()->assertSee('PORTAL-1');
    $unlinked = User::factory()->create(['u_role' => 'tenant', 'tenants_t_id' => null, 'must_change_password' => false]);
    $this->actingAs($unlinked)->get('/my/rentals')->assertOk()->assertSee('ตอนนี้คุณไม่มีการเช่าที่กำลังใช้งาน')->assertDontSee('PORTAL-1');
    $this->get('/my/contracts')->assertOk()->assertSee('ยังไม่มีสัญญาเช่า');
});
