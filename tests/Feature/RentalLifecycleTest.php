<?php

use App\Actions\Billing\NextBillingPeriod;
use App\Actions\Rentals\CreateRental;
use App\Actions\Rentals\ScheduleMoveOut;
use App\Models\AuditEvent;
use App\Models\Meter;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->travelTo('2026-10-15 05:00:00');
    config(['dormitory.water_rate' => '10.20', 'dormitory.elec_rate' => '4.42']);
    $this->admin = User::factory()->create(['u_role' => 'admin', 'is_active' => true, 'must_change_password' => false]);
    $this->tenant = Tenant::create(['t_Fname' => 'กานต์', 't_Lname' => 'ใจดี', 't_tel' => '0800000011']);
    $this->user = User::factory()->create(['u_role' => 'tenant', 'tenants_t_id' => $this->tenant->getKey(), 'must_change_password' => false]);
    $this->room = Room::create(['r_name' => 'LC101', 'r_floor' => 1, 'r_type' => 'แอร์', 'r_rent' => '3000.00', 'r_status' => 'VACANT']);
    $this->payload = ['tenants_t_id' => $this->tenant->getKey(), 'rooms_r_id' => $this->room->getKey(), 'rt_movein' => '2026-11-01', 'c_end' => '2027-10-31', 'c_rent' => '3000.00', 'c_deposit' => '6000.00'];
    $this->scheduled = fn () => app(CreateRental::class)->handle($this->admin, $this->payload);
});
afterEach(fn () => $this->travelBack());

test('future reservation has no actual movein or meter requirement and no room occupancy or invoice', function () {
    $rental = ($this->scheduled)();
    expect($rental->rt_status)->toBe('SCHEDULED')->and($rental->rt_movein)->toBeNull()
        ->and($rental->scheduled_movein_at->toDateString())->toBe('2026-11-01')
        ->and($rental->contract->c_status)->toBe('SCHEDULED')->and($this->room->fresh()->r_status)->toBe('VACANT');
    $this->actingAs($this->admin)->postJson('/api/v1/invoices/preview', ['rentals_rt_id' => $rental->getKey()])->assertConflict();
    $this->assertDatabaseCount('invoices', 0);
    $this->actingAs($this->admin)->get('/rentals/'.$rental->getKey())->assertOk()->assertSee('ยืนยันเข้าอยู่วันนี้');
    $this->actingAs($this->user)->get('/my/rentals')->assertOk()->assertSee('รอเข้าอยู่');
    $this->actingAs($this->admin)->getJson('/api/v1/dashboard')->assertOk()->assertJsonPath('data.scheduled_rentals', 1);
});

test('rejects missing historical opening meter invalid contract end and overlapping pending reservation', function () {
    $this->actingAs($this->admin)->postJson('/api/v1/rentals', [...$this->payload, 'rt_movein' => '2026-10-14'])->assertUnprocessable()->assertJsonValidationErrors('rooms_r_id');
    $this->postJson('/api/v1/rentals', [...$this->payload, 'c_end' => '2026-10-31'])->assertUnprocessable()->assertJsonValidationErrors('c_end');
    ($this->scheduled)();
    $this->postJson('/api/v1/rentals', $this->payload)->assertConflict();
    $this->assertDatabaseCount('rentals', 1);
});

test('activation rejects early date and missing meter then uses late actual date for billing', function () {
    $rental = ($this->scheduled)();
    $url = '/api/v1/rentals/'.$rental->getKey().'/activate';
    $this->actingAs($this->admin)->postJson($url)->assertConflict();
    $this->travelTo('2026-11-03 05:00:00');
    $this->postJson($url)->assertUnprocessable()->assertJsonValidationErrors('rt_movein');
    Meter::create(['rooms_r_id' => $this->room->getKey(), 'm_date' => '2026-11-03', 'm_water' => '100', 'm_elec' => '1000']);
    $this->postJson($url)->assertOk()->assertJsonPath('data.rt_status', 'ACTIVE')->assertJsonPath('data.rt_movein', '2026-11-03');
    expect($rental->fresh()->contract->c_start->toDateString())->toBe('2026-11-03')->and($this->room->fresh()->r_status)->toBe('OCCUPIED');
    $this->postJson($url)->assertConflict();
    $this->postJson('/api/v1/invoices/preview', ['rentals_rt_id' => $rental->getKey(), 'period_start' => '2026-11-01', 'period_end' => '2026-11-03'])->assertUnprocessable()->assertJsonValidationErrors('period_start');
});

test('scheduled moveout allows booking next tenant but cancellation and extension cannot break reservation', function () {
    Meter::create(['rooms_r_id' => $this->room->getKey(), 'm_date' => '2026-10-15', 'm_water' => '100', 'm_elec' => '1000']);
    $current = app(CreateRental::class)->handle($this->admin, [...$this->payload, 'rt_movein' => '2026-10-15']);
    $url = '/api/v1/rentals/'.$current->getKey().'/schedule-moveout';
    $this->actingAs($this->admin)->postJson($url, ['scheduled_moveout_at' => '2026-11-01', 'reason' => 'แจ้งล่วงหน้า'])->assertOk();
    expect($current->fresh()->rt_status)->toBe('ACTIVE')->and($this->room->fresh()->r_status)->toBe('OCCUPIED');
    $next = Tenant::create(['t_Fname' => 'คน', 't_Lname' => 'ถัดไป', 't_tel' => '0800000012']);
    $this->postJson('/api/v1/rentals', [...$this->payload, 'tenants_t_id' => $next->getKey()])->assertCreated();
    $this->patchJson($url, ['scheduled_moveout_at' => '2026-11-02', 'reason' => 'เลื่อน'])->assertConflict();
    $this->deleteJson($url, ['reason' => 'ยกเลิกกำหนด'])->assertConflict();
    expect($current->fresh()->scheduled_moveout_at->toDateString())->toBe('2026-11-01');
    $pending = Rental::where('rt_status', 'SCHEDULED')->firstOrFail();
    $this->travelTo('2026-11-01 05:00:00');
    $this->postJson('/api/v1/rentals/'.$pending->getKey().'/activate')->assertConflict();
});

test('cancelling a reservation preserves history and releases availability', function () {
    $rental = ($this->scheduled)();
    $this->actingAs($this->admin)->postJson('/api/v1/rentals/'.$rental->getKey().'/cancel', ['reason' => 'เปลี่ยนแผน'])->assertOk()->assertJsonPath('data.rt_status', 'CANCELLED');
    expect($rental->fresh()->contract->c_status)->toBe('CANCELLED');
    $this->postJson('/api/v1/rentals', $this->payload)->assertCreated();
    $this->assertDatabaseCount('rentals', 2);
});

test('tenant cannot activate cancel or schedule and stale suspended admin is rejected', function () {
    $rental = ($this->scheduled)();
    $this->actingAs($this->user)->postJson('/api/v1/rentals/'.$rental->getKey().'/activate')->assertForbidden();
    $this->postJson('/api/v1/rentals/'.$rental->getKey().'/cancel', ['reason' => 'test'])->assertForbidden();
    $this->postJson('/api/v1/rentals/'.$rental->getKey().'/schedule-moveout', ['scheduled_moveout_at' => '2026-12-01', 'reason' => 'test'])->assertForbidden();
    $this->admin->is_active = false;
    $this->admin->save();
    $this->actingAs($this->admin)->postJson('/api/v1/rentals/'.$rental->getKey().'/activate')->assertForbidden();
});

test('activation audit failure rolls back room contract and rental', function () {
    $rental = ($this->scheduled)();
    $this->travelTo('2026-11-01 05:00:00');
    Meter::create(['rooms_r_id' => $this->room->getKey(), 'm_date' => '2026-11-01', 'm_water' => '100', 'm_elec' => '1000']);
    $event = 'eloquent.creating: '.AuditEvent::class;
    Event::listen($event, fn () => throw new RuntimeException('audit unavailable'));
    try {
        $this->actingAs($this->admin)->postJson('/api/v1/rentals/'.$rental->getKey().'/activate')->assertStatus(500);
    } finally {
        Event::forget($event);
    }
    expect($rental->fresh()->rt_status)->toBe('SCHEDULED')->and($rental->fresh()->rt_movein)->toBeNull()
        ->and($rental->fresh()->contract->c_status)->toBe('SCHEDULED')->and($this->room->fresh()->r_status)->toBe('VACANT');
});

test('moveout cancellation without dependent booking keeps active rental', function () {
    Meter::create(['rooms_r_id' => $this->room->getKey(), 'm_date' => '2026-10-15', 'm_water' => '100', 'm_elec' => '1000']);
    $rental = app(CreateRental::class)->handle($this->admin, [...$this->payload, 'rt_movein' => '2026-10-15']);
    $url = '/api/v1/rentals/'.$rental->getKey().'/schedule-moveout';
    $this->actingAs($this->admin)->postJson($url, ['scheduled_moveout_at' => '2026-10-31', 'reason' => 'นัดวัน'])->assertOk();
    $this->deleteJson($url, ['reason' => 'อยู่ต่อ'])->assertOk()->assertJsonPath('data.scheduled_moveout_at', null);
    expect($rental->fresh()->rt_status)->toBe('ACTIVE');
});

test('pending tenant cannot see former occupant meter history', function () {
    $rental = ($this->scheduled)();
    Meter::create(['rooms_r_id' => $this->room->getKey(), 'm_date' => '2026-10-01', 'm_water' => '12345', 'm_elec' => '67890']);
    $this->actingAs($this->user)->get('/my/meters')->assertOk()->assertDontSee('12345')->assertDontSee('67890');
});

test('actual moveout with pending next rental clears schedule and bills to actual date while retaining unpaid balance', function () {
    Meter::create(['rooms_r_id' => $this->room->getKey(), 'm_date' => '2026-10-15', 'm_water' => '100', 'm_elec' => '1000']);
    $current = app(CreateRental::class)->handle($this->admin, [...$this->payload, 'rt_movein' => '2026-10-15']);
    app(ScheduleMoveOut::class)->handle($this->admin, $current, ['scheduled_moveout_at' => '2026-11-01', 'reason' => 'นัดส่งคืนห้อง']);
    $next = Tenant::create(['t_Fname' => 'ราย', 't_Lname' => 'ใหม่', 't_tel' => '0800000012']);
    $pending = app(CreateRental::class)->handle($this->admin, [...$this->payload, 'tenants_t_id' => $next->getKey()]);
    $this->travelTo('2026-10-20 05:00:00');
    Meter::create(['rooms_r_id' => $this->room->getKey(), 'm_date' => '2026-10-20', 'm_water' => '102', 'm_elec' => '1020']);
    $this->actingAs($this->admin)->postJson('/api/v1/rentals/'.$current->getKey().'/move-out', ['rt_moveout' => '2026-10-20', 'reason' => 'คืนห้องก่อนกำหนด'])->assertOk();
    expect($current->fresh()->scheduled_moveout_at)->toBeNull()->and($current->fresh()->rt_status)->toBe('ENDED')
        ->and($this->room->fresh()->r_status)->toBe('VACANT')->and($pending->fresh()->rt_status)->toBe('SCHEDULED');
    $invoice = $current->invoices()->sole();
    expect($invoice->period_end->toDateString())->toBe('2026-10-20')->and($invoice->payment->p_status)->toBe('UNPAID');
    $this->travelTo('2026-11-01 05:00:00');
    Meter::create(['rooms_r_id' => $this->room->getKey(), 'm_date' => '2026-11-01', 'm_water' => '102', 'm_elec' => '1020']);
    $this->postJson('/api/v1/rentals/'.$pending->getKey().'/activate')->assertOk();
});

test('backdated occupied rental retains actual start current creation time and historical billing boundary', function () {
    Meter::create(['rooms_r_id' => $this->room->getKey(), 'm_date' => '2026-09-15', 'm_water' => '100', 'm_elec' => '1000']);
    $this->actingAs($this->admin)->postJson('/api/v1/rentals', [...$this->payload, 'rt_movein' => '2026-09-15'])->assertCreated();
    $rental = Rental::sole();
    expect($rental->rt_status)->toBe('ACTIVE')->and($rental->rt_movein->toDateString())->toBe('2026-09-15')
        ->and($rental->contract->c_start->toDateString())->toBe('2026-09-15')
        ->and($rental->created_at->toDateString())->toBe('2026-10-15')
        ->and($this->room->fresh()->r_status)->toBe('OCCUPIED');
    $audit = AuditEvent::where('action', 'rental_created')->sole();
    expect($audit->actor_user_id)->toBe($this->admin->getKey())->and($audit->new_values['is_backdated'])->toBeTrue()
        ->and(app(NextBillingPeriod::class)->handle($rental))->toBe(['period_start' => '2026-09-15', 'period_end' => '2026-10-01']);
    $this->assertDatabaseCount('invoices', 0);
    $this->postJson('/api/v1/invoices/preview', ['rentals_rt_id' => $rental->getKey(), 'period_start' => '2026-09-15', 'period_end' => '2026-10-01'])->assertUnprocessable();
    Meter::create(['rooms_r_id' => $this->room->getKey(), 'm_date' => '2026-10-01', 'm_water' => '110', 'm_elec' => '1020']);
    $this->postJson('/api/v1/invoices/preview', ['rentals_rt_id' => $rental->getKey(), 'period_start' => '2026-09-15', 'period_end' => '2026-10-01'])->assertOk();
});

test('backdated rental cannot overlap an ended historical rental even when room is vacant', function () {
    Rental::create(['tenants_t_id' => $this->tenant->getKey(), 'rooms_r_id' => $this->room->getKey(), 'rt_movein' => '2026-09-01', 'rt_moveout' => '2026-09-20', 'rt_status' => 'ENDED']);
    Meter::create(['rooms_r_id' => $this->room->getKey(), 'm_date' => '2026-09-15', 'm_water' => '100', 'm_elec' => '1000']);
    $this->actingAs($this->admin)->postJson('/api/v1/rentals', [...$this->payload, 'rt_movein' => '2026-09-15'])->assertConflict();
    $this->assertDatabaseCount('rentals', 1);
    $this->assertDatabaseCount('contracts', 0);
});
