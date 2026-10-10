<?php

use App\Actions\Billing\IssueInvoice;
use App\Actions\Rentals\MoveOutRental;
use App\Models\AuditEvent;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

beforeEach(function () {
    $this->travelTo(
        CarbonImmutable::parse('2026-11-05 12:00:00', 'Asia/Bangkok')
    );

    config([
        'dormitory.water_rate' => '18.00',
        'dormitory.elec_rate' => '7.00',
    ]);

    $this->admin = User::factory()->create([
        'u_role' => 'admin',
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $tenant = Tenant::create([
        't_Fname' => 'Move',
        't_Lname' => 'Out',
        't_tel' => '0812345678',
    ]);

    $this->owner = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => $tenant->getKey(),
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->room = Room::create([
        'r_name' => 'OUT-101',
        'r_floor' => 1,
        'r_type' => 'เตียงเดี่ยว',
        'r_rent' => '3000.00',
        'r_status' => 'OCCUPIED',
    ]);

    $this->rental = Rental::create([
        'tenants_t_id' => $tenant->getKey(),
        'rooms_r_id' => $this->room->getKey(),
        'rt_movein' => '2026-09-16',
        'rt_moveout' => null,
        'rt_status' => 'ACTIVE',
    ]);

    $this->contract = $this->rental->contract()->create([
        'c_number' => 'OUT-CONTRACT',
        'c_start' => '2026-09-16',
        'c_end' => '2027-09-15',
        'c_rent' => '3000.00',
        'c_deposit' => '6000.00',
        'c_status' => 'ACTIVE',
    ]);

    $this->meters = [];

    foreach ([
        ['2026-09-16', '100.00', '1000.00'],
        ['2026-09-20', '102.00', '1010.00'],
        ['2026-10-01', '108.00', '1050.00'],
        ['2026-10-15', '118.00', '1110.00'],
        ['2026-11-01', '130.00', '1200.00'],
    ] as [$date, $water, $electricity]) {
        $this->meters[$date] = Meter::create([
            'rooms_r_id' => $this->room->getKey(),
            'm_date' => $date,
            'm_water' => $water,
            'm_elec' => $electricity,
        ]);
    }

    $this->url = "/api/v1/rentals/{$this->rental->getKey()}/move-out";

    $this->payload = [
        'rt_moveout' => '2026-10-15',
        'reason' => 'ย้ายออกวันที่ 15 ตุลาคม บันทึกย้อนหลัง',
    ];

    $this->issue = function (string $start, string $end): Invoice {
        $result = app(IssueInvoice::class)->handle(
            $this->admin,
            [
                'rentals_rt_id' => $this->rental->getKey(),
                'period_start' => $start,
                'period_end' => $end,
            ],
            persist: true
        );

        return Invoice::findOrFail($result['i_id']);
    };

    $this->state = function (): array {
        return [
            'rental' => $this->rental->fresh()->getAttributes(),
            'contract' => $this->contract->fresh()->getAttributes(),
            'room' => $this->room->fresh()->getAttributes(),
            'invoices' => Invoice::withTrashed()->orderBy('i_id')->get()
                ->map(fn ($row) => $row->getAttributes())->all(),
            'payments' => Payment::withTrashed()->orderBy('p_id')->get()
                ->map(fn ($row) => $row->getAttributes())->all(),
            'audits' => AuditEvent::count(),
        ];
    };
});

afterEach(function () {
    $this->travelBack();
});

test('backdated move out creates monthly bills and closes rental atomically', function () {
    $response = $this->actingAs($this->admin)
        ->postJson($this->url, [
            ...$this->payload,
            'rt_status' => 'ACTIVE',
            'rooms_r_id' => 999999,
            'i_total' => '0.01',
        ])
        ->assertOk()
        ->assertJsonPath('data.rental.rt_status', 'ENDED')
        ->assertJsonPath('data.rental.rt_moveout', '2026-10-15')
        ->assertJsonPath('data.rental.room.r_status', 'VACANT')
        ->assertJsonCount(2, 'data.created_invoices');

    $bills = Invoice::orderBy('period_start')->get();

    expect($bills[0]->period_start->toDateString())->toBe('2026-09-16');
    expect($bills[0]->period_end->toDateString())->toBe('2026-10-01');
    expect($bills[0]->i_total)->toBe('1994.00');

    expect($bills[1]->period_start->toDateString())->toBe('2026-10-01');
    expect($bills[1]->period_end->toDateString())->toBe('2026-10-15');
    expect($bills[1]->i_rent)->toBe('1354.84');
    expect($bills[1]->i_water)->toBe('180.00');
    expect($bills[1]->i_elec)->toBe('420.00');
    expect($bills[1]->i_total)->toBe('1954.84');

    foreach ($bills as $bill) {
        expect($bill->i_date->toDateString())->toBe('2026-11-05');
        expect($bill->i_due->toDateString())->toBe('2026-11-12');
        expect($bill->payment->p_status)->toBe('UNPAID');
        expect($bill->payment->p_amount)->toBe($bill->i_total);
    }

    expect($this->contract->fresh()->c_status)->toBe('ENDED');
    expect($this->contract->fresh()->c_end->toDateString())
        ->toBe('2027-09-15');

    $audit = AuditEvent::where('action', 'rental_moved_out')->sole();

    expect($audit->reason)->toBe($this->payload['reason']);
    expect($audit->new_values['rt_moveout'])->toBe('2026-10-15');
    expect((int) $audit->new_values['closing_meter_id'])
        ->toBe($this->meters['2026-10-15']->getKey());
    expect(count($audit->new_values['created_invoice_ids']))->toBe(2);

    $this->assertDatabaseCount('invoices', 2);
    $this->assertDatabaseCount('payments', 2);
    $this->assertDatabaseCount('payment_events', 0);
    $this->assertDatabaseCount('audit_events', 5);

    // ผู้เช่าเดิมยังอ่านบิลหลังย้ายออกได้
    $this->actingAs($this->owner)
        ->getJson('/api/v1/invoices/'.$bills[0]->getKey())
        ->assertOk();
});

test('move out fills gaps before and after existing invoice without changing it', function () {
    $existing = ($this->issue)('2026-09-20', '2026-10-01');

    $invoiceBefore = $existing->getAttributes();
    $paymentBefore = $existing->payment->getAttributes();

    $this->actingAs($this->admin)
        ->postJson($this->url, $this->payload)
        ->assertOk()
        ->assertJsonCount(2, 'data.created_invoices');

    expect($existing->fresh()->getAttributes())->toBe($invoiceBefore);
    expect($existing->payment()->firstOrFail()->getAttributes())
        ->toBe($paymentBefore);

    $bills = Invoice::orderBy('period_start')->get();

    expect($bills->count())->toBe(3);
    expect($bills[0]->period_start->toDateString())->toBe('2026-09-16');
    expect($bills[0]->period_end->toDateString())->toBe('2026-09-20');
    expect($bills[0]->i_total)->toBe('506.00');
    expect($bills[1]->getKey())->toBe($existing->getKey());
    expect($bills[2]->period_start->toDateString())->toBe('2026-10-01');
    expect($bills[2]->period_end->toDateString())->toBe('2026-10-15');
});

test('fully billed rental can move out with unpaid debt and no new invoice', function () {
    ($this->issue)('2026-09-16', '2026-10-01');
    ($this->issue)('2026-10-01', '2026-10-15');

    $this->actingAs($this->admin)
        ->postJson($this->url, $this->payload)
        ->assertOk()
        ->assertJsonCount(0, 'data.created_invoices');

    $this->assertDatabaseCount('invoices', 2);
    $this->assertDatabaseCount('payments', 2);

    expect(Payment::where('p_status', 'UNPAID')->count())->toBe(2);
    expect($this->rental->fresh()->rt_status)->toBe('ENDED');
});

test('missing boundary meter rolls back earlier bill created in same move out', function () {
    // ทำให้บิลกันยายนสร้างได้ แต่ช่วงตุลาคมล้มเหลว
    // การลบมิเตอร์ขอบเดือนนี้จะทำให้ช่วงแรกพังด้วย จึงใช้ audit listener
    // จำลองมิเตอร์วันออกหายหลังสร้างบิลแรก ภายใน transaction
    $eventName = 'eloquent.created: '.Invoice::class;
    $dispatcher = Invoice::getEventDispatcher();
    $listeners = $dispatcher->getRawListeners()[$eventName] ?? [];
    $closingId = $this->meters['2026-10-15']->getKey();

    $dispatcher->listen($eventName, function () use ($closingId): void {
        Meter::whereKey($closingId)->update(['deleted_at' => now()]);
    });

    try {
        $this->actingAs($this->admin)
            ->postJson($this->url, $this->payload)
            ->assertUnprocessable();
    } finally {
        $dispatcher->forget($eventName);

        foreach ($listeners as $listener) {
            $dispatcher->listen($eventName, $listener);
        }
    }

    expect($this->rental->fresh()->rt_status)->toBe('ACTIVE');
    expect($this->rental->fresh()->rt_moveout)->toBeNull();
    expect($this->contract->fresh()->c_status)->toBe('ACTIVE');
    expect($this->room->fresh()->r_status)->toBe('OCCUPIED');
    expect(Meter::find($closingId))->not->toBeNull();

    $this->assertDatabaseCount('invoices', 0);
    $this->assertDatabaseCount('payments', 0);
    $this->assertDatabaseCount('audit_events', 0);
});

test('missing required meter prevents move out', function (string $date) {
    $this->meters[$date]->delete();
    $before = ($this->state)();

    $this->actingAs($this->admin)
        ->postJson($this->url, $this->payload)
        ->assertUnprocessable();

    expect(($this->state)())->toBe($before);
})->with([
    'opening' => ['2026-09-16'],
    'month boundary' => ['2026-10-01'],
    'closing' => ['2026-10-15'],
]);

test('invoice beyond move out date prevents changes', function () {
    ($this->issue)('2026-10-01', '2026-11-01');
    $before = ($this->state)();

    $this->actingAs($this->admin)
        ->postJson($this->url, $this->payload)
        ->assertConflict();

    expect(($this->state)())->toBe($before);
});

test('soft deleted invoice requires resolution before move out', function () {
    ($this->issue)('2026-09-16', '2026-10-01')->delete();
    $before = ($this->state)();

    $this->actingAs($this->admin)
        ->postJson($this->url, $this->payload)
        ->assertConflict();

    expect(($this->state)())->toBe($before);
});

test('invalid move out request leaves all records unchanged', function (
    array $changes,
    string $field
) {
    $before = ($this->state)();

    $this->actingAs($this->admin)
        ->postJson($this->url, array_replace($this->payload, $changes))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);

    expect(($this->state)())->toBe($before);
})->with([
    'future' => [['rt_moveout' => '2026-11-06'], 'rt_moveout'],
    'before move in' => [['rt_moveout' => '2026-09-15'], 'rt_moveout'],
    'invalid date' => [['rt_moveout' => '2026-09-31'], 'rt_moveout'],
    'blank reason' => [['reason' => '   '], 'reason'],
]);

test('repeated move out is rejected without extra records', function () {
    $this->actingAs($this->admin)
        ->postJson($this->url, $this->payload)
        ->assertOk();

    $before = ($this->state)();

    $this->postJson($this->url, $this->payload)
        ->assertConflict();

    expect(($this->state)())->toBe($before);
});

test('expired contract may end without changing its original end date', function () {
    $this->contract->update([
        'c_status' => 'EXPIRED',
        'c_end' => '2026-09-30',
    ]);

    $this->actingAs($this->admin)
        ->postJson($this->url, $this->payload)
        ->assertOk();

    expect($this->contract->fresh()->c_status)->toBe('ENDED');
    expect($this->contract->fresh()->c_end->toDateString())
        ->toBe('2026-09-30');
});

test('restricted actor cannot move out rental', function (array $changes) {
    $this->admin->forceFill($changes)->save();
    $before = ($this->state)();

    $this->actingAs($this->admin->fresh())
        ->postJson($this->url, $this->payload)
        ->assertForbidden();

    expect(($this->state)())->toBe($before);
})->with([
    'tenant' => [['u_role' => 'tenant']],
    'inactive' => [['is_active' => false]],
    'password required' => [['must_change_password' => true]],
]);

test('guest cannot move out rental', function () {
    $this->postJson($this->url, $this->payload)->assertUnauthorized();
});

test('final audit failure rolls back bills and all status changes', function () {
    $before = ($this->state)();

    $eventName = 'eloquent.creating: '.AuditEvent::class;
    $dispatcher = AuditEvent::getEventDispatcher();
    $listeners = $dispatcher->getRawListeners()[$eventName] ?? [];

    $dispatcher->listen($eventName, function (AuditEvent $audit): void {
        if ($audit->action === 'rental_moved_out') {
            throw new RuntimeException('Move out audit failed');
        }
    });

    try {
        app(MoveOutRental::class)->handle(
            $this->admin,
            $this->rental,
            $this->payload
        );

        $this->fail('Expected audit failure');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Move out audit failed');
    } finally {
        $dispatcher->forget($eventName);

        foreach ($listeners as $listener) {
            $dispatcher->listen($eventName, $listener);
        }
    }

    expect(($this->state)())->toBe($before);
});

function enableMoveOutCsrf($app): void
{
    $app->bind(
        PreventRequestForgery::class,
        function ($app) {
            return new class($app, $app->make(Encrypter::class)) extends PreventRequestForgery
            {
                protected function runningUnitTests()
                {
                    return false;
                }
            };
        }
    );
}

test('move out rejects requests without valid csrf evidence', function (
    string $scenario
) {
    enableMoveOutCsrf($this->app);

    $before = ($this->state)();

    $headers = match ($scenario) {
        'missing' => [],
        'wrong' => [
            'X-CSRF-TOKEN' => str_repeat('b', 40),
        ],
        'cross-site' => [
            'Sec-Fetch-Site' => 'cross-site',
        ],
    };

    $this->actingAs($this->admin, 'web')
        ->withSession(['_token' => str_repeat('a', 40)])
        ->postJson($this->url, $this->payload, $headers)
        ->assertStatus(419)
        ->assertJsonPath('code', 'CSRF_TOKEN_MISMATCH');

    expect(($this->state)())->toBe($before);
})->with(['missing', 'wrong', 'cross-site']);

test('move out succeeds with matching session csrf token', function () {
    enableMoveOutCsrf($this->app);

    $token = str_repeat('a', 40);

    $this->actingAs($this->admin, 'web')
        ->withSession(['_token' => $token])
        ->postJson($this->url, $this->payload, [
            'X-CSRF-TOKEN' => $token,
        ])
        ->assertOk()
        ->assertJsonPath('data.rental.rt_status', 'ENDED')
        ->assertJsonPath('data.rental.room.r_status', 'VACANT')
        ->assertJsonCount(2, 'data.created_invoices');

    $this->assertDatabaseCount('invoices', 2);
    $this->assertDatabaseCount('payments', 2);

    expect(AuditEvent::where('action', 'rental_moved_out')->count())
        ->toBe(1);
});
