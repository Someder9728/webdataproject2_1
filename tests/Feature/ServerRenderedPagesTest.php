<?php

use App\Actions\Rentals\UpdateContract;
use App\Models\AuditEvent;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Support\RentalTimeline;

beforeEach(function () {
    $this->travelTo('2026-10-11 12:00:00');

    $this->admin = User::factory()->create(['u_role' => 'admin', 'must_change_password' => false]);
    $this->owner = Tenant::create(['t_Fname' => 'สมชาย', 't_Lname' => 'เจ้าของ', 't_tel' => '0811111111', 't_mail' => 'owner@example.com']);
    $this->other = Tenant::create(['t_Fname' => 'สมศรี', 't_Lname' => 'คนอื่น', 't_tel' => '0822222222']);
    $this->ownerUser = User::factory()->create(['u_role' => 'tenant', 'tenants_t_id' => $this->owner->getKey(), 'must_change_password' => false]);

    $this->roomA = Room::create(['r_name' => 'A101', 'r_floor' => 1, 'r_type' => 'เตียงเดี่ยว', 'r_rent' => '3500.00', 'r_status' => 'OCCUPIED']);
    $this->roomB = Room::create(['r_name' => 'B202', 'r_floor' => 2, 'r_type' => 'เตียงคู่', 'r_rent' => '4200.00', 'r_status' => 'OCCUPIED']);

    $this->ownRental = Rental::create(['tenants_t_id' => $this->owner->getKey(), 'rooms_r_id' => $this->roomA->getKey(), 'rt_movein' => '2026-08-01', 'rt_status' => 'ACTIVE']);
    $this->otherRental = Rental::create(['tenants_t_id' => $this->other->getKey(), 'rooms_r_id' => $this->roomB->getKey(), 'rt_movein' => '2026-08-01', 'rt_status' => 'ACTIVE']);
    $this->contract = $this->ownRental->contract()->create(['c_number' => 'CT-0001', 'c_start' => '2026-08-01', 'c_end' => '2026-12-31', 'c_rent' => '3500.00', 'c_deposit' => '7000.00', 'c_status' => 'ACTIVE']);

    $this->makeInvoice = function (Rental $rental, string $month, string $status, ?string $paidOn = null): Invoice {
        $start = Meter::create(['rooms_r_id' => $rental->rooms_r_id, 'm_date' => "{$month}-01", 'm_water' => '100.00', 'm_elec' => '1000.00']);
        $end = Meter::create(['rooms_r_id' => $rental->rooms_r_id, 'm_date' => "{$month}-28", 'm_water' => '108.00', 'm_elec' => '1050.00']);
        $invoice = Invoice::create([
            'rentals_rt_id' => $rental->getKey(), 'period_start' => "{$month}-01", 'period_end' => "{$month}-28",
            'start_meter_id' => $start->getKey(), 'end_meter_id' => $end->getKey(), 'i_date' => "{$month}-28", 'i_due' => '2026-10-05',
            'water_usage' => '8.00', 'elec_usage' => '50.00', 'water_rate' => '18.00', 'elec_rate' => '7.00', 'rent_rate' => '3500.00',
            'i_rent' => '3500.00', 'i_water' => '144.00', 'i_elec' => '350.00', 'i_total' => '3994.00',
        ]);
        $invoice->payment()->create([
            'p_amount' => '3994.00', 'p_status' => $status, 'p_date' => $paidOn,
            'p_type' => $status === 'PAID' ? 'CASH' : null,
        ]);

        return $invoice;
    };
});

test('invoice history shows only paid invoices with total and filters', function () {
    ($this->makeInvoice)($this->ownRental, '2026-08', 'PAID', '2026-09-02');
    ($this->makeInvoice)($this->otherRental, '2026-09', 'PAID', '2026-10-02');
    ($this->makeInvoice)($this->ownRental, '2026-09', 'UNPAID');

    $this->actingAs($this->admin)->get('/invoices/history')->assertOk()
        ->assertSeeInOrder(['B202', 'A101'])
        ->assertSee('2 รายการ · รวม ฿7,988')
        ->assertSee('เงินสด');

    $this->actingAs($this->admin)->get('/invoices/history?month=2026-09')->assertOk()
        ->assertSee('B202')->assertDontSee('ห้อง A101');

    $this->actingAs($this->admin)->get('/invoices/history?q=A101')->assertOk()
        ->assertSee('ห้อง A101')->assertDontSee('ห้อง B202');

    $this->actingAs($this->admin)->get('/invoices/history?q=zzz')->assertOk()
        ->assertSee('ไม่พบรายการที่ตรงกับคำค้นหา');
});

test('tenant invoice history only lists own invoices', function () {
    ($this->makeInvoice)($this->ownRental, '2026-08', 'PAID', '2026-09-02');
    ($this->makeInvoice)($this->otherRental, '2026-09', 'PAID', '2026-10-02');

    $this->actingAs($this->ownerUser)->get('/invoices/history')->assertOk()
        ->assertSee('ห้อง A101')->assertDontSee('ห้อง B202')->assertDontSee('สมศรี')
        ->assertSee('1 รายการ · รวม ฿3,994');
});

test('invoice history validates filters', function () {
    $this->actingAs($this->admin)->get('/invoices/history?month=bad')->assertSessionHasErrors('month');
});

test('invoice detail renders bill payment box and admin actions', function () {
    $invoice = ($this->makeInvoice)($this->ownRental, '2026-09', 'UNPAID');

    $this->actingAs($this->admin)->get("/invoices/{$invoice->getKey()}")->assertOk()
        ->assertSee($invoice->number())
        ->assertSee('ค้างชำระ — เกินกำหนด 6 วัน (ครบกำหนด 2026-10-05)')
        ->assertSee('฿3,994')
        ->assertSee('8 หน่วย × ฿18')
        ->assertSee('id="btn-edit"', false)
        ->assertSee('id="btn-walkin"', false)
        ->assertDontSee('id="btn-submit"', false)
        ->assertSee('ยังไม่มีประวัติการชำระ');
});

test('invoice detail shows payment events with role specific actor labels', function () {
    $invoice = ($this->makeInvoice)($this->ownRental, '2026-09', 'REJECTED');
    $invoice->payment->update(['p_reject_reason' => 'ยอดไม่ตรง']);
    $invoice->payment->events()->create([
        'actor_user_id' => $this->admin->getKey(), 'event_type' => 'PAYMENT_REJECTED',
        'from_status' => 'PENDING', 'to_status' => 'REJECTED', 'reason' => 'ยอดไม่ตรง', 'amount' => '3994.00', 'payment_date' => '2026-10-01', 'method' => 'TRANSFER',
    ]);

    $this->actingAs($this->admin)->get("/invoices/{$invoice->getKey()}")->assertOk()
        ->assertSee('ปฏิเสธหลักฐานการชำระ')->assertSee('฿3,994 · โอนเงิน · วันที่ชำระ 2026-10-01')->assertSee('โดย '.$this->admin->u_username)
        ->assertDontSee('id="btn-edit"', false);

    $this->actingAs($this->ownerUser)->get("/invoices/{$invoice->getKey()}")->assertOk()
        ->assertSee('โดยผู้ดูแลระบบ')->assertDontSee($this->admin->u_username)
        ->assertSee('กรุณาส่งหลักฐานใหม่')->assertSee('id="btn-submit"', false)
        ->assertDontSee('id="btn-walkin"', false);
});

test('tenant cannot open another tenant invoice and missing invoice is 404', function () {
    $invoice = ($this->makeInvoice)($this->otherRental, '2026-09', 'UNPAID');

    $this->actingAs($this->ownerUser)->get("/invoices/{$invoice->getKey()}")->assertNotFound();
    $this->actingAs($this->admin)->get('/invoices/99999')->assertNotFound();
});

test('rental list splits active and ended rentals and searches', function () {
    $this->otherRental->update(['rt_status' => 'ENDED', 'rt_moveout' => '2026-09-30']);

    $this->actingAs($this->admin)->get('/rentals')->assertOk()
        ->assertSee('กำลังเช่า 1 รายการ · สิ้นสุดแล้ว 1 รายการ')
        ->assertSeeInOrder(['การเช่าปัจจุบัน', 'สมชาย เจ้าของ', 'ประวัติการเช่า', 'สมศรี คนอื่น'])
        ->assertSee('data-action="move-out"', false);

    $this->actingAs($this->admin)->get('/rentals?q=B202')->assertOk()
        ->assertDontSee('สมชาย เจ้าของ')->assertSee('สมศรี คนอื่น');

    $this->actingAs($this->admin)->get('/rentals?created=1')->assertSee('สร้างการเช่าสำเร็จ');
});

test('rental detail renders tenant room contract and timeline', function () {
    ($this->makeInvoice)($this->ownRental, '2026-08', 'UNPAID');
    app(UpdateContract::class)->handle($this->admin, $this->ownRental, ['c_end' => '2027-06-30', 'reason' => 'ขอต่ออีกครึ่งปี']);

    $this->actingAs($this->admin)->get("/rentals/{$this->ownRental->getKey()}")->assertOk()
        ->assertSee('owner@example.com')
        ->assertSee('฿3,500/เดือน')
        ->assertSee('CT-0001')
        ->assertSee('ล็อกแล้ว — ออกใบแจ้งหนี้ไปแล้ว')
        ->assertSee('id="contract-btn-edit"', false)
        ->assertSee('id="btn-move-out"', false)
        ->assertSeeInOrder(['ย้ายเข้า', 'สร้างสัญญาเช่า', 'CT-0001 · สิ้นสุด 2026-12-31', 'ต่อสัญญา', 'วันสิ้นสุด 2026-12-31 → 2027-06-30', 'เหตุผล: ขอต่ออีกครึ่งปี']);
});

test('ended rental detail locks contract editing and hides move out', function () {
    $this->ownRental->update(['rt_status' => 'ENDED', 'rt_moveout' => '2026-10-01']);
    $this->contract->update(['c_status' => 'ENDED']);

    $this->actingAs($this->admin)->get("/rentals/{$this->ownRental->getKey()}")->assertOk()
        ->assertSee('สัญญานี้สิ้นสุดแล้ว — แก้ไขหรือต่อสัญญาไม่ได้')
        ->assertDontSee('id="contract-btn-edit"', false)
        ->assertDontSee('id="btn-move-out"', false)
        ->assertSeeInOrder(['ย้ายออก', 'สัญญาสิ้นสุด']);

    $this->actingAs($this->admin)->get('/rentals/99999')->assertNotFound();
});

test('rental detail shows saved and conflict notes after reload', function () {
    $url = "/rentals/{$this->ownRental->getKey()}";
    $this->actingAs($this->admin)->get($url.'?contract_saved=1')->assertSee('บันทึกการแก้ไขสัญญาเรียบร้อย');
    $this->actingAs($this->admin)->get($url.'?contract_conflict=1')
        ->assertSee('แก้ไขสัญญาไม่ได้')->assertDontSee('id="contract-btn-edit"', false);
});

test('timeline titles match contract edit direction', function () {
    $rental = $this->ownRental->load('room');
    $event = fn (array $old, array $new, string $action = 'contract_updated') => new AuditEvent([
        'action' => $action, 'old_values' => $old, 'new_values' => $new, 'reason' => null,
    ]);
    $title = function ($e) use ($rental) {
        $e->created_at = now();

        return collect(RentalTimeline::build($rental, null, collect([$e])))->firstWhere('kind', 'edit')['title'];
    };

    expect($title($event(['c_end' => '2026-12-31'], ['c_end' => '2027-01-31'])))->toBe('ต่อสัญญา')
        ->and($title($event(['c_end' => '2026-12-31'], ['c_end' => '2026-11-30'])))->toBe('แก้วันสิ้นสุดสัญญา')
        ->and($title($event(['c_end' => '2026-12-31'], ['c_end' => null])))->toBe('เปลี่ยนเป็นไม่กำหนดวันสิ้นสุด')
        ->and($title($event(['c_end' => null], ['c_end' => '2026-12-31'])))->toBe('กำหนดวันสิ้นสุดสัญญา')
        ->and($title($event([], [], 'CONTRACT_EXTENDED')))->toBe('ต่อสัญญา')
        ->and($title($event(['c_status' => 'ACTIVE'], ['c_status' => 'EXPIRED'])))->toBe('แก้ไขสัญญา');
});
