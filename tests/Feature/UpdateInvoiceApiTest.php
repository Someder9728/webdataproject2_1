<?php

use App\Actions\Billing\IssueInvoice;
use App\Actions\Billing\UpdateInvoice;
use App\Models\AuditEvent;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

beforeEach(function () {
    $this->travelTo(
        CarbonImmutable::parse('2026-10-02 12:00:00', 'Asia/Bangkok')
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
        't_Fname' => 'Invoice',
        't_Lname' => 'Edit',
        't_tel' => '0812345678',
    ]);

    $this->room = Room::create([
        'r_name' => 'EDIT-101',
        'r_floor' => 1,
        'r_type' => 'เตียงเดี่ยว',
        'r_rent' => '3000.00',
        'r_status' => 'OCCUPIED',
    ]);

    $this->rental = Rental::create([
        'tenants_t_id' => $tenant->getKey(),
        'rooms_r_id' => $this->room->getKey(),
        'rt_movein' => '2026-09-01',
        'rt_status' => 'ACTIVE',
    ]);

    $this->contract = $this->rental->contract()->create([
        'c_number' => 'EDIT-CONTRACT',
        'c_start' => '2026-09-01',
        'c_end' => null,
        'c_rent' => '3000.00',
        'c_deposit' => '6000.00',
        'c_status' => 'ACTIVE',
    ]);

    foreach ([
        ['2026-09-01', '90.00', '900.00'],
        ['2026-09-16', '100.00', '1000.00'],
        ['2026-09-20', '102.00', '1010.00'],
        ['2026-10-01', '108.00', '1050.00'],
    ] as [$date, $water, $electricity]) {
        Meter::create([
            'rooms_r_id' => $this->room->getKey(),
            'm_date' => $date,
            'm_water' => $water,
            'm_elec' => $electricity,
        ]);
    }

    $result = app(IssueInvoice::class)->handle(
        $this->admin,
        [
            'rentals_rt_id' => $this->rental->getKey(),
            'period_start' => '2026-09-16',
            'period_end' => '2026-10-01',
        ],
        persist: true
    );

    $this->invoice = Invoice::findOrFail($result['i_id']);
    $this->payment = $this->invoice->payment;
    $this->url = "/api/v1/invoices/{$this->invoice->getKey()}";

    $this->payload = [
        'period_start' => '2026-09-20',
        'period_end' => '2026-10-01',
        'reason' => 'แก้ช่วงคิดเงินที่ระบุผิด',
    ];

    $this->assertUnchanged = function (
        array $invoiceBefore,
        array $paymentBefore,
        int $auditCount
    ): void {
        expect($this->invoice->fresh()->getAttributes())
            ->toBe($invoiceBefore);

        expect($this->payment->fresh()->getAttributes())
            ->toBe($paymentBefore);

        expect(AuditEvent::count())->toBe($auditCount);
    };
});

afterEach(function () {
    $this->travelBack();
});

test('edit preserves rates and dates while updating amounts and audit', function () {
    $originalDate = $this->invoice->i_date->toDateString();
    $originalDue = $this->invoice->i_due->toDateString();

    // อัตราปัจจุบันเปลี่ยน แต่บิลเดิมต้องใช้ snapshot เดิม
    config([
        'dormitory.water_rate' => '99.00',
        'dormitory.elec_rate' => '88.00',
    ]);

    $this->contract->update(['c_rent' => '9000.00']);

    $this->travelTo(
        CarbonImmutable::parse('2026-10-05 12:00:00', 'Asia/Bangkok')
    );

    $this->actingAs($this->admin)
        ->patchJson($this->url, [
            ...$this->payload,
            'rentals_rt_id' => 999999,
            'water_rate' => '0.01',
            'elec_rate' => '0.01',
            'rent_rate' => '0.01',
            'i_total' => '0.01',
            'i_date' => '2000-01-01',
            'p_status' => 'PAID',
        ])
        ->assertOk()
        ->assertJsonPath('data.rentals_rt_id', $this->rental->getKey())
        ->assertJsonPath('data.water_rate', '18.00')
        ->assertJsonPath('data.elec_rate', '7.00')
        ->assertJsonPath('data.rent_rate', '3000.00')
        ->assertJsonPath('data.water_usage', '6.00')
        ->assertJsonPath('data.elec_usage', '40.00')
        ->assertJsonPath('data.i_rent', '1100.00')
        ->assertJsonPath('data.i_water', '108.00')
        ->assertJsonPath('data.i_elec', '280.00')
        ->assertJsonPath('data.i_total', '1488.00')
        ->assertJsonPath('data.i_date', $originalDate)
        ->assertJsonPath('data.i_due', $originalDue)
        ->assertJsonPath('data.payment.p_id', $this->payment->getKey())
        ->assertJsonPath('data.payment.p_amount', '1488.00')
        ->assertJsonPath('data.payment.p_status', 'UNPAID');

    $audit = AuditEvent::where('action', 'invoice_updated')->sole();

    expect((int) $audit->actor_user_id)->toBe($this->admin->getKey());
    expect($audit->reason)->toBe($this->payload['reason']);
    expect($audit->old_values['i_total'])->toBe('1994.00');
    expect($audit->new_values['i_total'])->toBe('1488.00');
    expect($audit->new_values['payment']['p_amount'])->toBe('1488.00');

    $this->assertDatabaseCount('invoices', 1);
    $this->assertDatabaseCount('payments', 1);
    $this->assertDatabaseCount('payment_events', 0);
});

test('due only edit preserves billing snapshot without rereading meters', function () {
    $before = $this->invoice->fresh()->getAttributes();

    // จำลอง source เปลี่ยน เพื่อพิสูจน์ว่าแก้ due ไม่คำนวณยอดใหม่
    Meter::whereKey($this->invoice->end_meter_id)->update([
        'm_water' => '999.00',
        'm_elec' => '9999.00',
    ]);

    $this->actingAs($this->admin)
        ->patchJson($this->url, [
            'i_due' => '2026-10-15',
            'reason' => 'แก้วันครบกำหนด',
        ])
        ->assertOk()
        ->assertJsonPath('data.i_due', '2026-10-15')
        ->assertJsonPath('data.i_total', '1994.00');

    $after = $this->invoice->fresh()->getAttributes();

    unset($before['i_due'], $before['updated_at']);
    unset($after['i_due'], $after['updated_at']);

    expect($after)->toBe($before);
    expect($this->payment->fresh()->p_amount)->toBe('1994.00');
});

test('payment status blocks invoice edit without changes', function (string $status) {
    $this->payment->update(['p_status' => $status]);

    $invoiceBefore = $this->invoice->fresh()->getAttributes();
    $paymentBefore = $this->payment->fresh()->getAttributes();
    $auditCount = AuditEvent::count();

    $this->actingAs($this->admin)
        ->patchJson($this->url, $this->payload)
        ->assertConflict();

    ($this->assertUnchanged)($invoiceBefore, $paymentBefore, $auditCount);
})->with(['PENDING', 'PAID', 'REJECTED']);

test('historical payment event blocks even an unpaid invoice', function () {
    $this->payment->events()->create([
        'actor_user_id' => $this->admin->getKey(),
        'event_type' => 'PROOF_SUBMITTED',
        'from_status' => 'UNPAID',
        'to_status' => 'PENDING',
        'amount' => $this->invoice->i_total,
        'payment_date' => '2026-10-02',
        'method' => 'TRANSFER',
        'proof_path' => 'historical/proof.pdf',
    ]);

    // จงใจให้ status ยัง UNPAID เพื่อทดสอบ history guard โดยเฉพาะ
    $invoiceBefore = $this->invoice->fresh()->getAttributes();
    $paymentBefore = $this->payment->fresh()->getAttributes();
    $auditCount = AuditEvent::count();

    $this->actingAs($this->admin)
        ->patchJson($this->url, $this->payload)
        ->assertConflict();

    ($this->assertUnchanged)($invoiceBefore, $paymentBefore, $auditCount);
    $this->assertDatabaseCount('payment_events', 1);
});

test('payment details without events also block edit', function () {
    $this->payment->update(['p_proof' => 'legacy/proof.pdf']);

    $this->actingAs($this->admin)
        ->patchJson($this->url, $this->payload)
        ->assertConflict();

    expect($this->invoice->fresh()->i_total)->toBe('1994.00');
    expect(AuditEvent::where('action', 'invoice_updated')->count())->toBe(0);
});

test('other invoices still block overlapping edit including soft deleted', function (
    bool $deleted
) {
    $result = app(IssueInvoice::class)->handle(
        $this->admin,
        [
            'rentals_rt_id' => $this->rental->getKey(),
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-16',
        ],
        persist: true
    );

    $other = Invoice::findOrFail($result['i_id']);

    if ($deleted) {
        $other->delete();
    }

    $invoiceBefore = $this->invoice->fresh()->getAttributes();
    $paymentBefore = $this->payment->fresh()->getAttributes();
    $auditCount = AuditEvent::count();

    $this->actingAs($this->admin)
        ->patchJson($this->url, [
            ...$this->payload,
            'period_start' => '2026-09-01',
        ])
        ->assertConflict();

    ($this->assertUnchanged)($invoiceBefore, $paymentBefore, $auditCount);
})->with([
    'active invoice' => [false],
    'soft deleted invoice' => [true],
]);

test('invalid edit does not change invoice payment or audit', function (
    array $changes,
    string $field
) {
    $invoiceBefore = $this->invoice->fresh()->getAttributes();
    $paymentBefore = $this->payment->fresh()->getAttributes();
    $auditCount = AuditEvent::count();

    $this->actingAs($this->admin)
        ->patchJson($this->url, array_replace($this->payload, $changes))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);

    ($this->assertUnchanged)($invoiceBefore, $paymentBefore, $auditCount);
})->with([
    'blank reason' => [['reason' => '   '], 'reason'],
    'missing boundary meter' => [
        ['period_start' => '2026-09-21'],
        'period_start',
    ],
    'invalid date' => [
        ['period_start' => '2026-09-31'],
        'period_start',
    ],
    'end before start' => [
        ['period_end' => '2026-09-19'],
        'period_end',
    ],
    'due before issue date' => [
        ['i_due' => '2026-10-01'],
        'i_due',
    ],
    'unchanged invoice' => [
        ['period_start' => '2026-09-16'],
        'invoice',
    ],
]);

test('guest cannot edit invoice', function () {
    $this->patchJson($this->url, $this->payload)
        ->assertUnauthorized();
});

test('restricted actors cannot edit through api', function (array $changes) {
    $this->admin->forceFill($changes)->save();

    $invoiceBefore = $this->invoice->fresh()->getAttributes();
    $paymentBefore = $this->payment->fresh()->getAttributes();
    $auditCount = AuditEvent::count();

    $this->actingAs($this->admin->fresh())
        ->patchJson($this->url, $this->payload)
        ->assertForbidden();

    ($this->assertUnchanged)($invoiceBefore, $paymentBefore, $auditCount);
})->with([
    'tenant' => [['u_role' => 'tenant']],
    'inactive' => [['is_active' => false]],
    'password required' => [['must_change_password' => true]],
]);

test('action rechecks actor when given stale model', function () {
    User::whereKey($this->admin->getKey())->update(['is_active' => false]);

    try {
        app(UpdateInvoice::class)->handle(
            $this->admin,
            $this->invoice,
            $this->payload
        );

        $this->fail('Inactive actor was allowed');
    } catch (AuthorizationException $exception) {
        expect($exception->status() ?? 403)->toBe(403);
    } catch (HttpExceptionInterface $exception) {
        expect($exception->getStatusCode())->toBe(403);
    }

    expect($this->invoice->fresh()->i_total)->toBe('1994.00');
    expect(AuditEvent::where('action', 'invoice_updated')->count())->toBe(0);
});

test('action rechecks payment when given stale invoice', function () {
    $this->invoice->load('payment');
    $this->payment->update(['p_status' => 'PENDING']);

    try {
        app(UpdateInvoice::class)->handle(
            $this->admin,
            $this->invoice,
            $this->payload
        );

        $this->fail('Pending payment was allowed');
    } catch (HttpExceptionInterface $exception) {
        expect($exception->getStatusCode())->toBe(409);
    }

    expect($this->invoice->fresh()->i_total)->toBe('1994.00');
    expect($this->payment->fresh()->p_status)->toBe('PENDING');
});

test('audit failure rolls back both invoice and payment', function () {
    $invoiceBefore = $this->invoice->fresh()->getAttributes();
    $paymentBefore = $this->payment->fresh()->getAttributes();
    $auditCount = AuditEvent::count();

    $eventName = 'eloquent.creating: '.AuditEvent::class;
    $dispatcher = AuditEvent::getEventDispatcher();
    $listeners = $dispatcher->getRawListeners()[$eventName] ?? [];

    $dispatcher->listen($eventName, function (AuditEvent $audit): void {
        if ($audit->action === 'invoice_updated') {
            throw new RuntimeException('Invoice edit audit failed');
        }
    });

    try {
        app(UpdateInvoice::class)->handle(
            $this->admin,
            $this->invoice,
            $this->payload
        );

        $this->fail('Expected audit failure');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Invoice edit audit failed');
    } finally {
        $dispatcher->forget($eventName);

        foreach ($listeners as $listener) {
            $dispatcher->listen($eventName, $listener);
        }
    }

    ($this->assertUnchanged)($invoiceBefore, $paymentBefore, $auditCount);
});

test('deleted invoice cannot be edited', function () {
    $this->invoice->delete();

    $this->actingAs($this->admin)
        ->patchJson($this->url, $this->payload)
        ->assertNotFound();
});

function enableInvoiceEditCsrf($app): void
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

test('invoice edit rejects missing or invalid csrf evidence', function (
    string $scenario
) {
    enableInvoiceEditCsrf($this->app);

    $invoiceBefore = $this->invoice->fresh()->getAttributes();
    $paymentBefore = $this->payment->fresh()->getAttributes();
    $auditCount = AuditEvent::count();

    $headers = match ($scenario) {
        'missing' => [],
        'wrong' => ['X-CSRF-TOKEN' => str_repeat('b', 40)],
        'cross-site' => ['Sec-Fetch-Site' => 'cross-site'],
    };

    $this->actingAs($this->admin, 'web')
        ->withSession(['_token' => str_repeat('a', 40)])
        ->patchJson($this->url, $this->payload, $headers)
        ->assertStatus(419)
        ->assertJsonPath('code', 'CSRF_TOKEN_MISMATCH');

    ($this->assertUnchanged)($invoiceBefore, $paymentBefore, $auditCount);
})->with(['missing', 'wrong', 'cross-site']);

test('invoice edit succeeds with matching session csrf token', function () {
    enableInvoiceEditCsrf($this->app);

    $token = str_repeat('a', 40);

    $this->actingAs($this->admin, 'web')
        ->withSession(['_token' => $token])
        ->patchJson($this->url, $this->payload, [
            'X-CSRF-TOKEN' => $token,
        ])
        ->assertOk()
        ->assertJsonPath('data.i_total', '1488.00')
        ->assertJsonPath('data.payment.p_amount', '1488.00');

    expect(AuditEvent::where('action', 'invoice_updated')->count())->toBe(1);
});
