<?php

use App\Models\Meter;
use App\Models\Room;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['u_role' => 'admin', 'is_active' => true, 'must_change_password' => false]);
    $this->room = Room::create(['r_name' => 'U101', 'r_floor' => 1, 'r_type' => 'STANDARD', 'r_rent' => 3000, 'r_status' => 'VACANT']);
    $this->start = Meter::create(['rooms_r_id' => $this->room->getKey(), 'm_date' => '2026-09-01', 'm_water' => '100.10', 'm_elec' => '99999998.98']);
    $this->end = Meter::create(['rooms_r_id' => $this->room->getKey(), 'm_date' => '2026-09-30', 'm_water' => '100.30', 'm_elec' => '99999999.99']);
    $this->url = '/api/v1/rooms/'.$this->room->getKey().'/meter-usage';
});

test('usage returns exact decimals and rejects reverse interval', function () {
    $query = '?start_meter_id='.$this->start->getKey().'&end_meter_id='.$this->end->getKey();
    $this->actingAs($this->admin)->getJson($this->url.$query)->assertOk()->assertJsonPath('data.water_usage', '0.20')->assertJsonPath('data.elec_usage', '1.01');
    $this->getJson($this->url.'?start_meter_id='.$this->end->getKey().'&end_meter_id='.$this->start->getKey())->assertUnprocessable();
    $this->end->update(['m_water' => '99.99']);
    $this->getJson($this->url.$query)->assertUnprocessable();
});

test('usage scopes both meters to requested room and forbids tenant', function () {
    $room = Room::create(['r_name' => 'U102', 'r_floor' => 1, 'r_type' => 'STANDARD', 'r_rent' => 3000, 'r_status' => 'VACANT']);
    $query = '?start_meter_id='.$this->start->getKey().'&end_meter_id='.$this->end->getKey();
    $this->actingAs($this->admin)->getJson('/api/v1/rooms/'.$room->getKey().'/meter-usage'.$query)->assertNotFound();
    $tenant = User::factory()->create(['u_role' => 'tenant', 'is_active' => true, 'must_change_password' => false]);
    $this->actingAs($tenant)->getJson($this->url.$query)->assertForbidden();
});
