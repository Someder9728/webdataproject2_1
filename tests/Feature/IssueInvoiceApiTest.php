<?php

use App\Models\AuditEvent;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    // ตรงกับวันที่ 1 ตุลาคม เวลา 01:00 ในไทย
    $this->travelTo(CarbonImmutable::parse('2026-09-30 18:00:00', 'UTC'));

    config([
        'dormitory.water_rate' => '18.00',
        'dormitory.elec_rate' => '7.00',
    ]);

    $this->admin = User::factory()->create([
        'u_role' => 'admin',
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->tenant = Tenant::create([
        't_Fname' => 'Billing',
        't_Lname' => 'Test',
        't_tel' => '0812345678',
    ]);

    $this->room = Room::create([
        'r_name' => 'BILL-101',
        'r_floor' => 1,
        'r_type' => 'เตียงเดี่ยว',
        'r_rent' => '9999.00',
        'r_status' => 'OCCUPIED',
    ]);

    $this->rental = Rental::create([
        'tenants_t_id' => $this->tenant->getKey(),
        'rooms_r_id' => $this->room->getKey(),
        'rt_movein' => '2026-09-16',
        'rt_status' => 'ACTIVE',
    ]);

    $this->contract = $this->rental->contract()->create([
        'c_number' => 'BILL-CONTRACT',
        'c_start' => '2026-09-16',
        'c_end' => null,
        'c_rent' => '3000.00',
        'c_deposit' => '6000.00',
        'c_status' => 'ACTIVE',
    ]);

    $this->startMeter = Meter::create([
        'rooms_r_id' => $this->room->getKey(),
        'm_date' => '2026-09-16',
        'm_water' => '100.00',
        'm_elec' => '1000.00',
    ]);

    $this->endMeter = Meter::create([
        'rooms_r_id' => $this->room->getKey(),
        'm_date' => '2026-10-01',
        'm_water' => '108.00',
        'm_elec' => '1050.00',
    ]);

    $this->payload = [
        'rentals_rt_id' => $this->rental->getKey(),
        'period_start' => '2026-09-16',
        'period_end' => '2026-10-01',
    ];
});

afterEach(function () {
    $this->travelBack();
});

test('preview calculates partial rent using contract and writes nothing', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/v1/invoices/preview', $this->payload)
        ->assertOk()
        ->assertJsonPath('data.i_rent', '1500.00')
        ->assertJsonPath('data.i_water', '144.00')
        ->assertJsonPath('data.i_elec', '350.00')
        ->assertJsonPath('data.i_total', '1994.00')
        ->assertJsonPath('data.i_date', '2026-10-01')
        ->assertJsonPath('data.i_due', '2026-10-08')
        ->assertJsonPath('data.rent_rate', '3000.00')
        ->assertJsonPath('data.start_meter_id', $this->startMeter->getKey());

    $this->assertDatabaseCount('invoices', 0);
    $this->assertDatabaseCount('payments', 0);
    $this->assertDatabaseCount('audit_events', 0);
});

test('issue creates snapshot unpaid payment and audit ignoring client totals', function () {
    $response = $this->actingAs($this->admin)
        ->postJson('/api/v1/invoices', [
            ...$this->payload,
            'i_total' => '0.01',
            'water_rate' => '0.00',
            'p_status' => 'PAID',
            'actor_user_id' => 999999,
        ])
        ->assertCreated()
        ->assertJsonPath('data.i_total', '1994.00')
        ->assertJsonPath('data.payment.p_amount', '1994.00')
        ->assertJsonPath('data.payment.p_status', 'UNPAID');

    $invoice = Invoice::findOrFail($response->json('data.i_id'));

    expect($invoice->water_rate)->toBe('18.00');
    expect($invoice->payment->p_date)->toBeNull();
    expect($invoice->payment->p_type)->toBeNull();
    expect($invoice->payment->p_proof)->toBeNull();

    $this->assertDatabaseCount('invoices', 1);
    $this->assertDatabaseCount('payments', 1);
    $this->assertDatabaseCount('audit_events', 1);

    $this->assertDatabaseHas('audit_events', [
        'actor_user_id' => $this->admin->getKey(),
        'entity_type' => 'invoices',
        'entity_id' => $invoice->getKey(),
        'action' => 'invoice_created',
    ]);
});

test('issue recalculates after preview and old snapshot survives config changes', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/v1/invoices/preview', $this->payload)
        ->assertOk()
        ->assertJsonPath('data.i_total', '1994.00');

    config(['dormitory.water_rate' => '20.00']);

    $response = $this->postJson('/api/v1/invoices', $this->payload)
        ->assertCreated()
        ->assertJsonPath('data.i_total', '2010.00');

    config(['dormitory.water_rate' => '99.00']);

    $invoice = Invoice::findOrFail($response->json('data.i_id'));

    expect($invoice->water_rate)->toBe('20.00');
    expect($invoice->i_total)->toBe('2010.00');
});

test('full calendar month uses full contract rent', function () {
    $this->rental->update(['rt_movein' => '2026-09-01']);
    $this->contract->update(['c_start' => '2026-09-01']);
    $this->startMeter->update(['m_date' => '2026-09-01']);

    $this->actingAs($this->admin)
        ->postJson('/api/v1/invoices/preview', [
            ...$this->payload,
            'period_start' => '2026-09-01',
        ])
        ->assertOk()
        ->assertJsonPath('data.i_rent', '3000.00')
        ->assertJsonPath('data.i_total', '3494.00');
});

test('billing rounds each component half up before summing', function () {
    $this->contract->update(['c_rent' => '0.01']);
    $this->endMeter->update([
        'm_water' => '100.01',
        'm_elec' => '1000.01',
    ]);

    config([
        'dormitory.water_rate' => '0.50',
        'dormitory.elec_rate' => '0.50',
    ]);

    $this->actingAs($this->admin)
        ->postJson('/api/v1/invoices/preview', $this->payload)
        ->assertOk()
        ->assertJsonPath('data.i_rent', '0.01')
        ->assertJsonPath('data.i_water', '0.01')
        ->assertJsonPath('data.i_elec', '0.01')
        ->assertJsonPath('data.i_total', '0.03');
});

test('duplicate or overlapping period creates no additional billing records', function ($start) {
    $this->actingAs($this->admin)
        ->postJson('/api/v1/invoices', $this->payload)
        ->assertCreated();

    $this->postJson('/api/v1/invoices', [
        ...$this->payload,
        'period_start' => $start,
    ])->assertConflict();

    $this->assertDatabaseCount('invoices', 1);
    $this->assertDatabaseCount('payments', 1);
    $this->assertDatabaseCount('audit_events', 1);
})->with([
    'same period' => ['2026-09-16'],
    'overlapping period' => ['2026-09-20'],
]);

test('missing configured rate blocks billing without writes', function ($key, $value) {
    config([$key => $value]);

    $this->actingAs($this->admin)
        ->postJson('/api/v1/invoices', $this->payload)
        ->assertUnprocessable();

    $this->assertDatabaseCount('invoices', 0);
    $this->assertDatabaseCount('payments', 0);
    $this->assertDatabaseCount('audit_events', 0);
})->with([
    'missing water' => ['dormitory.water_rate', null],
    'empty electricity' => ['dormitory.elec_rate', ''],
    'negative rate' => ['dormitory.water_rate', '-1'],
]);

test('billing requires exact boundary meters and nondecreasing readings', function ($change) {
    if ($change === 'wrong date') {
        $this->startMeter->update(['m_date' => '2026-09-15']);
    } elseif ($change === 'missing end') {
        $this->endMeter->delete();
    } else {
        $this->endMeter->update(['m_water' => '99.00']);
    }

    $this->actingAs($this->admin)
        ->postJson('/api/v1/invoices', $this->payload)
        ->assertUnprocessable();

    $this->assertDatabaseCount('invoices', 0);
    $this->assertDatabaseCount('payments', 0);
})->with(['wrong date', 'missing end', 'decreasing reading']);

test('invalid period is rejected', function ($start, $end) {
    $this->actingAs($this->admin)
        ->postJson('/api/v1/invoices', [
            ...$this->payload,
            'period_start' => $start,
            'period_end' => $end,
        ])
        ->assertUnprocessable();

    $this->assertDatabaseCount('invoices', 0);
})->with([
    'before move in' => ['2026-09-15', '2026-10-01'],
    'empty period' => ['2026-09-16', '2026-09-16'],
    'reversed' => ['2026-10-01', '2026-09-16'],
    'future end' => ['2026-09-16', '2026-10-02'],
    'invalid date' => ['2026-09-31', '2026-10-01'],
]);

test('past cross month period must be split', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-02', 'Asia/Bangkok'));

    $this->actingAs($this->admin)
        ->postJson('/api/v1/invoices', [
            ...$this->payload,
            'period_end' => '2026-11-01',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('period_end');
});

test('period cannot extend beyond recorded move out', function () {
    $this->rental->update([
        'rt_status' => 'ENDED',
        'rt_moveout' => '2026-09-25',
    ]);

    $this->actingAs($this->admin)
        ->postJson('/api/v1/invoices', $this->payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('period_end');
});

test('guest and tenant cannot preview or issue invoices', function () {
    $tenantUser = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => $this->tenant->getKey(),
        'is_active' => true,
        'must_change_password' => false,
    ]);

    foreach (['/api/v1/invoices/preview', '/api/v1/invoices'] as $url) {
        $this->postJson($url, $this->payload)->assertUnauthorized();
    }

    $this->actingAs($tenantUser);

    foreach (['/api/v1/invoices/preview', '/api/v1/invoices'] as $url) {
        $this->postJson($url, $this->payload)->assertForbidden();
    }

    $this->assertDatabaseCount('invoices', 0);
});

test('audit failure rolls back invoice and payment', function () {
    $attempted = false;
    $event = 'eloquent.creating: '.AuditEvent::class;

    Event::listen($event, function () use (&$attempted) {
        $attempted = true;
        throw new RuntimeException('Simulated invoice audit failure');
    });

    try {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/invoices', $this->payload)
            ->assertStatus(500);
    } finally {
        Event::forget($event);
    }

    expect($attempted)->toBeTrue();

    $this->assertDatabaseCount('invoices', 0);
    $this->assertDatabaseCount('payments', 0);
    $this->assertDatabaseCount('audit_events', 0);
});

test('february prorated rent uses actual days in the year', function (
    $start,
    $end,
    $expectedRent
) {
    $this->travelTo(CarbonImmutable::parse($end, 'Asia/Bangkok'));

    $this->rental->update(['rt_movein' => $start]);
    $this->contract->update(['c_start' => $start]);
    $this->startMeter->update(['m_date' => $start]);
    $this->endMeter->update(['m_date' => $end]);

    $this->actingAs($this->admin)
        ->postJson('/api/v1/invoices/preview', [
            ...$this->payload,
            'period_start' => $start,
            'period_end' => $end,
        ])
        ->assertOk()
        ->assertJsonPath('data.i_rent', $expectedRent);

    $this->assertDatabaseCount('invoices', 0);
})->with([
    // 14 วัน จากเดือนที่มี 28 วัน
    'normal February' => ['2026-02-15', '2026-03-01', '1500.00'],

    // 15 วัน จากเดือนที่มี 29 วัน
    'leap February' => ['2028-02-15', '2028-03-01', '1551.72'],
]);

test('adjacent monthly invoices share boundary meter without overlapping', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/v1/invoices', $this->payload)
        ->assertCreated();

    $this->travelTo(
        CarbonImmutable::parse('2026-11-01', 'Asia/Bangkok')
    );

    $nextMeter = Meter::create([
        'rooms_r_id' => $this->room->getKey(),
        'm_date' => '2026-11-01',
        'm_water' => '118.00',
        'm_elec' => '1110.00',
    ]);

    $this->postJson('/api/v1/invoices', [
        ...$this->payload,
        'period_start' => '2026-10-01',
        'period_end' => '2026-11-01',
    ])
        ->assertCreated()
        ->assertJsonPath('data.start_meter_id', $this->endMeter->getKey())
        ->assertJsonPath('data.end_meter_id', $nextMeter->getKey())
        ->assertJsonPath('data.water_usage', '10.00')
        ->assertJsonPath('data.elec_usage', '60.00')
        ->assertJsonPath('data.i_rent', '3000.00')
        ->assertJsonPath('data.i_total', '3600.00');

    $this->assertDatabaseCount('invoices', 2);
    $this->assertDatabaseCount('payments', 2);
    $this->assertDatabaseCount('audit_events', 2);
});

test('meter from another room cannot supply billing boundary', function () {
    $otherRoom = Room::create([
        'r_name' => 'BILL-OTHER',
        'r_floor' => 2,
        'r_type' => 'เตียงคู่',
        'r_rent' => '4000.00',
        'r_status' => 'VACANT',
    ]);

    // วันตรงกัน แต่เป็นมิเตอร์ของห้องอื่น
    $this->endMeter->update([
        'rooms_r_id' => $otherRoom->getKey(),
    ]);

    $this->actingAs($this->admin)
        ->postJson('/api/v1/invoices', [
            ...$this->payload,
            'end_meter_id' => $this->endMeter->getKey(),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('period_end');

    $this->assertDatabaseCount('invoices', 0);
    $this->assertDatabaseCount('payments', 0);
    $this->assertDatabaseCount('audit_events', 0);
});

test('intermediate reading does not replace invoice start boundary', function () {
    Meter::create([
        'rooms_r_id' => $this->room->getKey(),
        'm_date' => '2026-09-25',
        'm_water' => '107.00',
        'm_elec' => '1040.00',
    ]);

    $this->actingAs($this->admin)
        ->postJson('/api/v1/invoices/preview', $this->payload)
        ->assertOk()
        ->assertJsonPath('data.start_meter_id', $this->startMeter->getKey())
        ->assertJsonPath('data.water_usage', '8.00')
        ->assertJsonPath('data.elec_usage', '50.00')
        ->assertJsonPath('data.i_total', '1994.00');
});

test('oversized calculated amount is rejected before saving', function () {
    config(['dormitory.water_rate' => '99999999.99']);

    $this->actingAs($this->admin)
        ->postJson('/api/v1/invoices', $this->payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('i_water');

    $this->assertDatabaseCount('invoices', 0);
    $this->assertDatabaseCount('payments', 0);
    $this->assertDatabaseCount('audit_events', 0);
});

test('account restrictions protect both invoice endpoints', function ($flag, $value, $code) {
    $this->admin->forceFill([$flag => $value])->save();

    foreach (['/api/v1/invoices/preview', '/api/v1/invoices'] as $url) {
        // บัญชีถูกระงับจะถูก logout จึงตั้งผู้ใช้ใหม่ก่อนแต่ละคำขอ
        $this->actingAs($this->admin->fresh())
            ->postJson($url, $this->payload)
            ->assertForbidden()
            ->assertJsonPath('code', $code);
    }

    $this->assertDatabaseCount('invoices', 0);
    $this->assertDatabaseCount('payments', 0);
})->with([
    'suspended admin' => ['is_active', false, 'ACCOUNT_INACTIVE'],
    'password change required' => [
        'must_change_password',
        true,
        'PASSWORD_CHANGE_REQUIRED',
    ],
]);