<?php

use App\Actions\Rentals\UpdateContract;
use App\Models\AuditEvent;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Rental;
use App\Models\Repair;
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

test('invoice index renders initial rows and isolates tenants before javascript', function () {
    ($this->makeInvoice)($this->ownRental, '2026-08', 'UNPAID');
    ($this->makeInvoice)($this->otherRental, '2026-09', 'PAID', '2026-10-02');
    $this->actingAs($this->ownerUser)->get('/invoices')->assertOk()->assertSee('A101')->assertDontSee('B202')->assertDontSee("IV.fetchAll('/api/v1/invoices'", false);
    $this->actingAs($this->admin)->get('/invoices?status=paid&month=2026-09')->assertOk()->assertSee('B202')->assertDontSee('A101');
    $this->actingAs($this->admin)->get('/invoices?q=zzz')->assertSee('ไม่พบใบแจ้งหนี้ที่ตรงกับเงื่อนไข');
    $this->actingAs($this->admin)->get('/invoices?status=invalid')->assertSessionHasErrors('status');
});

test('tenant dashboard renders contract monthly rate rather than partial invoice rent', function () {
    $invoice = ($this->makeInvoice)($this->ownRental, '2026-09', 'UNPAID');
    $invoice->update(['i_rent' => '1750.00']);
    ($this->makeInvoice)($this->otherRental, '2026-08', 'UNPAID');
    $this->actingAs($this->ownerUser)->get('/user/dashboard')->assertOk()->assertSee('฿3,500.00/เดือน')->assertDontSee('฿1,750.00/เดือน')->assertDontSee('B202')->assertDontSee('getAll(', false);
    $empty = User::factory()->create(['u_role' => 'tenant', 'must_change_password' => false]);
    $this->actingAs($empty)->get('/user/dashboard')->assertOk()->assertSee('ยังไม่มีห้องเช่า')->assertDontSee('A101');
});

test('admin dashboard renders server totals and previews without initial api calls', function () {
    ($this->makeInvoice)($this->ownRental, '2026-09', 'UNPAID');
    $this->actingAs($this->admin)->get('/dashboard')->assertOk()->assertSee('฿3,994.00')->assertSee('สมชาย')->assertDontSee('window.dashboardApi.get()', false);
    $this->actingAs($this->admin)->get('/dashboard?month=bad')->assertSessionHasErrors('month');
});

test('contract index renders exact end date filters and validates pagination', function () {
    $this->actingAs($this->admin)->get('/contracts')->assertOk()->assertSee('CT-0001')->assertSee('2026-12-31')->assertDontSee('api/v1/rentals?${params}', false);
    $this->actingAs($this->admin)->get('/contracts?search=zzz')->assertSee('ยังไม่มีสัญญาที่ตรงกับการค้นหา');
    $this->actingAs($this->admin)->get('/contracts?status=ENDED')->assertDontSee('CT-0001');
    $this->actingAs($this->admin)->get('/contracts?page=0')->assertSessionHasErrors('page');
    $this->actingAs($this->ownerUser)->get('/contracts')->assertForbidden();
});

test('rental create lists occupied rooms for date based reservations with server rendered options', function () {
    $available = Room::create(['r_name' => 'FREE303', 'r_floor' => 3, 'r_type' => 'single', 'r_rent' => '3000', 'r_status' => 'VACANT']);
    $tenant = Tenant::create(['t_Fname' => 'ว่างพร้อมเข้า', 't_Lname' => 'ทดสอบ', 't_tel' => '0833333333']);
    User::factory()->create(['u_role' => 'tenant', 'tenants_t_id' => $tenant->getKey()]);
    $this->actingAs($this->admin)->get('/rentals/create')->assertOk()->assertSee('FREE303')->assertSee('ว่างพร้อมเข้า')->assertSee('A101')->assertSee('สมชาย')->assertDontSee('fetch(API_TENANTS', false);
});

test('meter initial history shows cancellation and invoice lock with room pagination', function () {
    $invoice = ($this->makeInvoice)($this->ownRental, '2026-08', 'UNPAID');
    $cancelled = Meter::create(['rooms_r_id' => $this->roomA->getKey(), 'm_date' => '2026-09-01', 'm_water' => '109', 'm_elec' => '1051']);
    $cancelled->delete();
    $this->actingAs($this->admin)->get('/meters?room='.$this->roomA->getKey())->assertOk()->assertSee('2026-09-01 · ยกเลิก')->assertSee('ล็อก: ใช้ในบิลแล้วหรือรายการยกเลิก')->assertSee('meter-initial', false);
    $this->actingAs($this->admin)->get('/meters?room=99999')->assertNotFound();
    $this->actingAs($this->ownerUser)->get('/meters')->assertForbidden();
});

test('repair list renders room names and isolates tenant rows', function () {
    foreach ([[$this->ownRental, 'OWN-REPAIR'], [$this->otherRental, 'OTHER-REPAIR']] as [$rental, $name]) {
        Repair::create(['rp_name' => $name, 'rp_description' => 'รายละเอียด', 'rp_type' => 'ROOM', 'rp_status' => 'REPORTED', 'rooms_r_id' => $rental->rooms_r_id, 'tenants_t_id' => $rental->tenants_t_id, 'reported_by_user_id' => $rental->tenants_t_id === $this->owner->getKey() ? $this->ownerUser->getKey() : $this->admin->getKey()]);
    }
    $this->actingAs($this->ownerUser)->get('/repairs')->assertOk()->assertSee('OWN-REPAIR')->assertSee('A101')->assertDontSee('OTHER-REPAIR')->assertDontSee('B202');
    $this->actingAs($this->admin)->get('/repairs?rp_status=COMPLETED')->assertDontSee('OWN-REPAIR');
    $this->actingAs($this->admin)->get('/repairs?rp_status=bad')->assertSessionHasErrors('rp_status');
});

test('room and tenant lists render on server and room history matches exact room id', function () {
    $this->actingAs($this->admin)->get('/rooms')->assertOk()->assertSee('A101')->assertSee('B202')->assertDontSee('window.roomApi.getAll', false);
    $this->actingAs($this->admin)->get('/rooms?floor=2')->assertOk()->assertSee('B202')->assertDontSee('A101');
    $this->actingAs($this->admin)->get('/tenants?search=สมชาย')->assertOk()->assertSee('สมชาย')->assertDontSee('สมศรี')->assertDontSee('window.tenantApi.getAll', false);
    $this->actingAs($this->admin)->get('/rooms/'.$this->roomA->getKey().'/history')->assertOk()->assertJsonFragment(['detail' => 'สมชาย เจ้าของ'])->assertJsonMissing(['detail' => 'สมศรี คนอื่น']);
    $this->actingAs($this->ownerUser)->get('/rooms/'.$this->roomA->getKey().'/history')->assertForbidden();
});

test('invoice pagination preserves filters and renders only one database page', function () {
    $original = ($this->makeInvoice)($this->ownRental, '2026-08', 'UNPAID');
    for ($i = 1; $i <= 21; $i++) {
        $copy = $original->replicate();
        $copy->period_start = '2026-09-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
        $copy->period_end = $copy->period_start->copy()->addDay();
        $copy->save();
        $copy->payment()->create(['p_amount' => $copy->i_total, 'p_status' => 'UNPAID']);
    }
    $this->actingAs($this->admin)->get('/invoices?q=A101&month=2026-09&status=overdue')->assertOk()->assertViewHas('invoices', fn ($items) => $items->total() === 21 && $items->count() === 20)->assertSee('page=2', false)->assertSee('month=2026-09', false);
    $this->actingAs($this->admin)->get('/invoices?q=A101&month=2026-09&status=overdue&page=2')->assertOk()->assertViewHas('invoices', fn ($items) => $items->count() === 1);
});

test('ssr pages escape stored text and invoice search accepts display numbers', function () {
    $invoice = ($this->makeInvoice)($this->ownRental, '2026-09', 'UNPAID');
    $this->roomA->update(['r_name' => '<script>alert(1)</script>']);
    $this->actingAs($this->admin)->get('/invoices?q='.$invoice->number())->assertOk()->assertViewHas('invoices', fn ($items) => $items->total() === 1)->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
});

test('guest cannot load new ssr business pages or room history', function () {
    foreach (['/invoices', '/contracts', '/meters', '/repairs', '/user/dashboard', '/rentals/create', '/rooms/'.$this->roomA->getKey().'/history'] as $url) {
        $this->get($url)->assertRedirect(route('login'));
    }
});

test('ssr meter flags keep an unbilled opening boundary editable but not cancellable', function () {
    $meter = Meter::create(['rooms_r_id' => $this->roomA->getKey(), 'm_date' => '2026-08-01', 'm_water' => '100', 'm_elec' => '1000']);
    $this->actingAs($this->admin)->get('/meters?room='.$this->roomA->getKey())->assertOk()
        ->assertViewHas('meterRows', fn ($rows) => $rows[$meter->getKey()]['can_edit'] === true && $rows[$meter->getKey()]['can_delete'] === false && $rows[$meter->getKey()]['date_locked'] === true);
});

test('meter autofill uses latest active reading on or before selected date and supports an empty room', function () {
    Meter::create(['rooms_r_id' => $this->roomA->getKey(), 'm_date' => '2026-09-01', 'm_water' => '100', 'm_elec' => '1000']);
    Meter::create(['rooms_r_id' => $this->roomA->getKey(), 'm_date' => '2026-10-01', 'm_water' => '110', 'm_elec' => '1100']);
    $cancelled = Meter::create(['rooms_r_id' => $this->roomA->getKey(), 'm_date' => '2026-10-05', 'm_water' => '115', 'm_elec' => '1150']);
    $cancelled->delete();
    $url = '/meters?room='.$this->roomA->getKey();
    $this->actingAs($this->admin)->get($url.'&m_date=2026-09-15')->assertOk()
        ->assertViewHas('latestMeter', fn ($m) => $m->m_water === '100.00')->assertViewHas('readingDate', '2026-09-15');
    $this->get($url.'&m_date=2026-10-10')->assertOk()->assertViewHas('latestMeter', fn ($m) => $m->m_water === '110.00');
    $this->get('/meters?room='.$this->roomB->getKey())->assertOk()->assertViewHas('latestMeter', null);
    $this->get($url.'&m_date=wrong')->assertSessionHasErrors('m_date');
});
