<?php

use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'u_role' => 'admin',
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->owner = Tenant::create([
        't_Fname' => 'Somchai',
        't_Lname' => 'Owner',
        't_tel' => '0811111111',
    ]);

    $this->other = Tenant::create([
        't_Fname' => 'Somsri',
        't_Lname' => 'Other',
        't_tel' => '0822222222',
    ]);

    $this->tenantUser = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => $this->owner->getKey(),
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->room = Room::create([
        'r_name' => 'READ-101',
        'r_floor' => 1,
        'r_type' => 'เตียงเดี่ยว',
        'r_rent' => '9999.00',
        'r_status' => 'OCCUPIED',
    ]);

    // ผู้เช่าเดิมย้ายออกแล้ว แต่ยังมีบิลค้าง
    $oldRental = Rental::create([
        'tenants_t_id' => $this->owner->getKey(),
        'rooms_r_id' => $this->room->getKey(),
        'rt_movein' => '2026-08-01',
        'rt_moveout' => '2026-09-01',
        'rt_status' => 'ENDED',
    ]);

    // ผู้เช่าใหม่อยู่ห้องเดียวกัน
    $newRental = Rental::create([
        'tenants_t_id' => $this->other->getKey(),
        'rooms_r_id' => $this->room->getKey(),
        'rt_movein' => '2026-09-01',
        'rt_status' => 'ACTIVE',
    ]);

    $meters = [];

    foreach ([
        ['2026-08-01', '100.00', '1000.00'],
        ['2026-09-01', '108.00', '1050.00'],
        ['2026-10-01', '116.00', '1100.00'],
    ] as [$date, $water, $elec]) {
        $meters[] = Meter::create([
            'rooms_r_id' => $this->room->getKey(),
            'm_date' => $date,
            'm_water' => $water,
            'm_elec' => $elec,
        ]);
    }

    $makeInvoice = function (
        Rental $rental,
        string $start,
        string $end,
        Meter $startMeter,
        Meter $endMeter,
        string $status
    ) {
        $invoice = Invoice::create([
            'rentals_rt_id' => $rental->getKey(),
            'period_start' => $start,
            'period_end' => $end,
            'start_meter_id' => $startMeter->getKey(),
            'end_meter_id' => $endMeter->getKey(),
            'i_date' => $end,
            'i_due' => '2026-10-08',
            'water_usage' => '8.00',
            'elec_usage' => '50.00',
            'water_rate' => '18.00',
            'elec_rate' => '7.00',
            'rent_rate' => '3000.00',
            'i_rent' => '3000.00',
            'i_water' => '144.00',
            'i_elec' => '350.00',
            'i_total' => '3494.00',
        ]);

        $invoice->payment()->create([
            'p_amount' => '3494.00',
            'p_status' => $status,
            'p_date' => null,
            'p_type' => $status === 'PENDING' ? 'TRANSFER' : null,
            'p_proof' => $status === 'PENDING'
                ? 'private/proofs/secret-proof.jpg'
                : null,
        ]);

        $invoice->forceFill([
            'created_at' => '2026-10-01 10:00:00',
        ])->save();

        return $invoice;
    };

    $this->ownInvoice = $makeInvoice(
        $oldRental,
        '2026-08-01',
        '2026-09-01',
        $meters[0],
        $meters[1],
        'UNPAID'
    );

    $this->otherInvoice = $makeInvoice(
        $newRental,
        '2026-09-01',
        '2026-10-01',
        $meters[1],
        $meters[2],
        'PENDING'
    );
});

test('guest cannot read invoice endpoints without accept header', function () {
    foreach ([
        '/api/v1/invoices',
        '/api/v1/invoices/'.$this->ownInvoice->getKey(),
        '/api/v1/invoices/'.$this->ownInvoice->getKey().'/payment',
    ] as $url) {
        $this->get($url)
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }
});

test('admin can paginate all invoices in stable order', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/v1/invoices?per_page=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.i_id', $this->otherInvoice->getKey())
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('meta.last_page', 2);

    $this->getJson('/api/v1/invoices?per_page=1&page=2')
        ->assertOk()
        ->assertJsonPath('data.0.i_id', $this->ownInvoice->getKey());
});

test('former tenant can read own unpaid invoice and payment', function () {
    $this->actingAs($this->tenantUser)
        ->getJson('/api/v1/invoices')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.i_id', $this->ownInvoice->getKey())
        ->assertJsonPath('meta.total', 1);

    $this->getJson('/api/v1/invoices/'.$this->ownInvoice->getKey())
        ->assertOk()
        ->assertJsonPath('data.i_total', '3494.00')
        ->assertJsonPath('data.period_start', '2026-08-01')
        ->assertJsonPath('data.payment.p_status', 'UNPAID');

    $this->getJson('/api/v1/invoices/'.$this->ownInvoice->getKey().'/payment')
        ->assertOk()
        ->assertJsonPath('data.p_amount', '3494.00')
        ->assertJsonPath('data.p_status', 'UNPAID')
        ->assertJsonPath('data.has_proof', false);
});

test('tenant cannot read new occupants invoice or payment of same room', function () {
    $this->actingAs($this->tenantUser);

    foreach (['', '/payment'] as $suffix) {
        $this->getJson(
            '/api/v1/invoices/'.$this->otherInvoice->getKey().$suffix
        )
            ->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }
});

test('search and forged parameters cannot bypass invoice ownership', function () {
    $this->actingAs($this->tenantUser)
        ->getJson('/api/v1/invoices?'.http_build_query([
            'search' => 'Somsri',
            'tenants_t_id' => $this->other->getKey(),
            'u_role' => 'admin',
        ]))
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0);

    // ค้นหาห้องร่วมกัน ต้องยังเห็นเฉพาะบิลตัวเอง
    $this->getJson('/api/v1/invoices?search=READ-101')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.i_id', $this->ownInvoice->getKey());
});

test('tenant without linked record sees no invoices', function () {
    $user = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => null,
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/invoices')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0);
});

test('admin filters invoices by tenant room period month and payment status', function (
    $query,
    $expected
) {
    $this->actingAs($this->admin)
        ->getJson('/api/v1/invoices?'.http_build_query($query))
        ->assertOk()
        ->assertJsonCount($expected, 'data')
        ->assertJsonPath('meta.total', $expected);
})->with([
    'full name' => [['search' => 'Somchai Owner'], 1],
    'room' => [['search' => 'READ-101'], 2],
    'August billing period' => [['month' => '2026-08'], 1],
    'not October issue date' => [['month' => '2026-10'], 0],
    'pending' => [['status' => 'PENDING'], 1],
    'combined' => [['month' => '2026-09', 'status' => 'UNPAID'], 0],
]);

test('invoice reads retain snapshots when room price and config change', function () {
    $this->room->update(['r_rent' => '8000.00']);

    config([
        'dormitory.water_rate' => '99.00',
        'dormitory.elec_rate' => '99.00',
    ]);

    $this->actingAs($this->admin)
        ->getJson('/api/v1/invoices/'.$this->ownInvoice->getKey())
        ->assertOk()
        ->assertJsonPath('data.rent_rate', '3000.00')
        ->assertJsonPath('data.water_rate', '18.00')
        ->assertJsonPath('data.elec_rate', '7.00')
        ->assertJsonPath('data.i_total', '3494.00');
});

test('invoice and payment responses never expose proof path', function () {
    $this->actingAs($this->admin);

    foreach ([
        '/api/v1/invoices',
        '/api/v1/invoices/'.$this->otherInvoice->getKey(),
        '/api/v1/invoices/'.$this->otherInvoice->getKey().'/payment',
    ] as $url) {
        $response = $this->getJson($url)->assertOk();

        expect($response->getContent())
            ->not->toContain('private/proofs/secret-proof.jpg')
            ->not->toContain('"p_proof"');
    }

    $this->getJson(
        '/api/v1/invoices/'.$this->otherInvoice->getKey().'/payment'
    )->assertJsonPath('data.has_proof', true);
});

test('deleted invoice is excluded from list detail and payment', function () {
    $this->ownInvoice->delete();

    $this->actingAs($this->admin)
        ->getJson('/api/v1/invoices')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.i_id', $this->otherInvoice->getKey());

    foreach (['', '/payment'] as $suffix) {
        $this->getJson(
            '/api/v1/invoices/'.$this->ownInvoice->getKey().$suffix
        )->assertNotFound();
    }
});

test('missing payment returns 404 from payment endpoint', function () {
    $this->ownInvoice->payment->delete();

    $this->actingAs($this->admin)
        ->getJson('/api/v1/invoices/'.$this->ownInvoice->getKey())
        ->assertOk()
        ->assertJsonPath('data.payment', null);

    $this->getJson(
        '/api/v1/invoices/'.$this->ownInvoice->getKey().'/payment'
    )->assertNotFound();
});

test('invoice list validates query parameters', function ($query, $field) {
    $this->actingAs($this->admin)
        ->getJson('/api/v1/invoices?'.http_build_query($query))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'invalid month' => [['month' => '2026-13'], 'month'],
    'invalid status' => [['status' => 'DONE'], 'status'],
    'zero page' => [['page' => 0], 'page'],
    'too many rows' => [['per_page' => 101], 'per_page'],
    'array search' => [['search' => ['test']], 'search'],
    'long search' => [['search' => str_repeat('a', 101)], 'search'],
]);

test('account restrictions protect all invoice read endpoints', function ($flag, $value, $code) {
    $this->admin->forceFill([$flag => $value])->save();

    foreach ([
        '/api/v1/invoices',
        '/api/v1/invoices/'.$this->ownInvoice->getKey(),
        '/api/v1/invoices/'.$this->ownInvoice->getKey().'/payment',
    ] as $url) {
        $this->actingAs($this->admin->fresh())
            ->getJson($url)
            ->assertForbidden()
            ->assertJsonPath('code', $code);
    }
})->with([
    'inactive' => ['is_active', false, 'ACCOUNT_INACTIVE'],
    'password change' => [
        'must_change_password',
        true,
        'PASSWORD_CHANGE_REQUIRED',
    ],
]);
