<?php

use App\Actions\Payments\RecordWalkInPayment;
use App\Models\AuditEvent;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->travelTo(
        CarbonImmutable::parse('2026-10-02 12:00:00', 'Asia/Bangkok')
    );

    Storage::fake('payment_proofs');

    $this->admin = User::factory()->create([
        'u_role' => 'admin',
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $tenant = Tenant::create([
        't_Fname' => 'Proof',
        't_Lname' => 'Owner',
        't_tel' => '0812345678',
    ]);

    $otherTenant = Tenant::create([
        't_Fname' => 'Other',
        't_Lname' => 'Tenant',
        't_tel' => '0899999999',
    ]);

    $this->owner = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => $tenant->getKey(),
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->otherUser = User::factory()->create([
        'u_role' => 'tenant',
        'tenants_t_id' => $otherTenant->getKey(),
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $room = Room::create([
        'r_name' => 'PROOF-101',
        'r_floor' => 1,
        'r_type' => 'เตียงเดี่ยว',
        'r_rent' => '3000.00',
        'r_status' => 'VACANT',
    ]);

    // ผู้เช่าย้ายออกแล้ว แต่ยังชำระบิลเดิมได้
    $rental = Rental::create([
        'tenants_t_id' => $tenant->getKey(),
        'rooms_r_id' => $room->getKey(),
        'rt_movein' => '2026-09-01',
        'rt_moveout' => '2026-10-01',
        'rt_status' => 'ENDED',
    ]);

    $startMeter = Meter::create([
        'rooms_r_id' => $room->getKey(),
        'm_date' => '2026-09-01',
        'm_water' => '100.00',
        'm_elec' => '1000.00',
    ]);

    $endMeter = Meter::create([
        'rooms_r_id' => $room->getKey(),
        'm_date' => '2026-10-01',
        'm_water' => '108.00',
        'm_elec' => '1050.00',
    ]);

    $this->invoice = Invoice::create([
        'rentals_rt_id' => $rental->getKey(),
        'period_start' => '2026-09-01',
        'period_end' => '2026-10-01',
        'start_meter_id' => $startMeter->getKey(),
        'end_meter_id' => $endMeter->getKey(),
        'i_date' => '2026-10-01',
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

    $this->payment = $this->invoice->payment()->create([
        'p_amount' => '3494.00',
        'p_status' => 'UNPAID',
    ]);

    $base = '/api/v1/invoices/'.$this->invoice->getKey().'/payment';
    $this->submitUrl = $base.'/submit';
    $this->proofUrl = $base.'/proof';

    $this->reviewUrl = $base.'/review';
    $this->walkInUrl = $base.'/walk-in';
    $this->walkInPayload = [
        'amount' => '3494.00',
        'payment_date' => '2026-10-02',
        'method' => 'CASH',
        'note' => 'รับชำระที่สำนักงาน',
        'expected_event_id' => null,
    ];

    $this->payload = [
        'amount' => '3494.00',
        'payment_date' => '2026-10-02',
    ];
});

afterEach(function () {
    $this->travelBack();
});

test('admin receives full walk in payment with trusted actor and event', function (string $method) {
    $this->actingAs($this->admin)->postJson($this->walkInUrl, [
        ...$this->walkInPayload, 'method' => $method,
        'actor_user_id' => $this->owner->getKey(), 'p_status' => 'UNPAID',
    ])->assertOk()->assertJsonPath('data.p_status', 'PAID')
        ->assertJsonPath('data.p_amount', '3494.00')->assertJsonPath('data.has_proof', false);
    $payment = $this->payment->fresh();
    expect($payment->p_type)->toBe($method);
    expect($payment->p_date->format('Y-m-d'))->toBe('2026-10-02');
    $event = $payment->events()->sole();
    expect($event->actor_user_id)->toBe($this->admin->getKey());
    expect($event->event_type)->toBe('WALK_IN_RECORDED');
    expect($event->from_status)->toBe('UNPAID');
    expect($event->to_status)->toBe('PAID');
    expect($event->method)->toBe($method);
    expect($event->note)->toBe('รับชำระที่สำนักงาน');
    expect($event->proof_path)->toBeNull();
    expect(AuditEvent::count())->toBe(1);
    expect(Storage::disk('payment_proofs')->allFiles())->toBe([]);
})->with(['CASH', 'TRANSFER']);

test('guest tenant and restricted admin cannot receive walk in', function () {
    $this->postJson($this->walkInUrl, $this->walkInPayload)->assertUnauthorized();
    $this->actingAs($this->owner)->postJson($this->walkInUrl, $this->walkInPayload)->assertForbidden();
    foreach ([['is_active' => false], ['is_active' => true, 'must_change_password' => true]] as $changes) {
        $this->admin->forceFill($changes)->save();
        $this->actingAs($this->admin)->postJson($this->walkInUrl, $this->walkInPayload)->assertForbidden();
    }
    expect($this->payment->fresh()->p_status)->toBe('UNPAID');
    expect(AuditEvent::count())->toBe(0);
});

test('walk in action independently rejects restricted actor', function (string $field, mixed $value) {
    $this->admin->forceFill([$field => $value])->save();
    expect($this->admin->fresh()->getAttribute($field))->toBe($value);
    try {
        app(RecordWalkInPayment::class)->handle($this->admin, $this->invoice, $this->walkInPayload);
        $this->fail('Restricted actor was allowed');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(403);
    }
    expect($this->payment->fresh()->p_status)->toBe('UNPAID');
})->with([['is_active', false], ['must_change_password', true], ['u_role', 'tenant']]);

test('invalid walk in leaves database unchanged', function (array $change, string $field) {
    $this->actingAs($this->admin)->postJson($this->walkInUrl, [...$this->walkInPayload, ...$change])
        ->assertUnprocessable()->assertJsonValidationErrors($field);
    expect($this->payment->fresh()->p_status)->toBe('UNPAID');
    expect($this->payment->events()->count())->toBe(0);
    expect(AuditEvent::count())->toBe(0);
})->with([
    [['amount' => '3493.99'], 'amount'],
    [['amount' => '3494.01'], 'amount'],
    [['amount' => '3494.001'], 'amount'],
    [['payment_date' => '2026-09-30'], 'payment_date'],
    [['payment_date' => '2026-10-03'], 'payment_date'],
    [['method' => 'CARD'], 'method'],
    [['note' => '   '], 'note'],
    [['note' => str_repeat('a', 1001)], 'note'],
    [['proof' => 'untrusted/path.pdf'], 'proof'],
]);

test('walk in requires explicit event version even when null', function () {
    $payload = $this->walkInPayload;
    unset($payload['expected_event_id']);
    $this->actingAs($this->admin)->postJson($this->walkInUrl, $payload)
        ->assertUnprocessable()->assertJsonValidationErrors('expected_event_id');
});

test('pending and paid cannot receive walk in', function (string $status) {
    $this->payment->update(['p_status' => $status]);
    $this->actingAs($this->admin)->postJson($this->walkInUrl, $this->walkInPayload)->assertConflict();
    expect($this->payment->fresh()->p_status)->toBe($status);
    expect(AuditEvent::count())->toBe(0);
})->with(['PENDING', 'PAID']);

test('rejected proof survives walk in and stale form is rejected', function () {
    $this->actingAs($this->owner)->post($this->submitUrl, [
        ...$this->payload, 'proof' => UploadedFile::fake()->image('old.png'),
    ], ['Accept' => 'application/json'])->assertOk();
    $path = $this->payment->fresh()->p_proof;
    $submittedId = $this->payment->events()->reorder()->orderByDesc('pe_id')->value('pe_id');
    $this->actingAs($this->admin)->postJson($this->reviewUrl, [
        'decision' => 'reject', 'expected_event_id' => $submittedId, 'reason' => 'ยอดในหลักฐานไม่ตรง',
    ])->assertOk();
    $rejectedId = $this->payment->events()->reorder()->orderByDesc('pe_id')->value('pe_id');
    $this->postJson($this->walkInUrl, $this->walkInPayload)->assertConflict();
    $this->postJson($this->walkInUrl, [...$this->walkInPayload, 'expected_event_id' => $rejectedId])
        ->assertOk()->assertJsonPath('data.has_proof', false);
    expect($this->payment->fresh()->p_proof)->toBeNull();
    expect($this->payment->fresh()->p_reject_reason)->toBeNull();
    Storage::disk('payment_proofs')->assertExists($path);
    expect($this->payment->events()->count())->toBe(3);
    expect($this->payment->events()->where('pe_id', $rejectedId)->first()->proof_path)->toBe($path);
    expect($this->payment->events()->where('event_type', 'WALK_IN_RECORDED')->sole()->from_status)->toBe('REJECTED');
    expect(AuditEvent::count())->toBe(3);
});

test('paid walk in cannot be paid again submitted or reviewed', function () {
    $this->actingAs($this->admin)->postJson($this->walkInUrl, $this->walkInPayload)->assertOk();
    $this->postJson($this->walkInUrl, $this->walkInPayload)->assertConflict();
    $id = $this->payment->events()->sole()->getKey();
    $this->postJson($this->reviewUrl, ['decision' => 'approve', 'expected_event_id' => $id])->assertConflict();
    $this->actingAs($this->owner)->post($this->submitUrl, [
        ...$this->payload, 'proof' => UploadedFile::fake()->image('late.png'),
    ], ['Accept' => 'application/json'])->assertConflict();
    expect($this->payment->events()->count())->toBe(1);
    expect(AuditEvent::count())->toBe(1);
    expect(Storage::disk('payment_proofs')->allFiles())->toBe([]);
});

test('walk in audit failure rolls back payment and event', function () {
    Event::listen('eloquent.creating: '.AuditEvent::class, function () {
        throw new RuntimeException('walk in audit failure');
    });
    try {
        app(RecordWalkInPayment::class)->handle($this->admin, $this->invoice, $this->walkInPayload);
        $this->fail('Expected audit failure');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('walk in audit failure');
    } finally {
        Event::forget('eloquent.creating: '.AuditEvent::class);
    }
    $payment = $this->payment->fresh();
    expect($payment->p_status)->toBe('UNPAID');
    expect($payment->p_date)->toBeNull();
    expect($payment->events()->count())->toBe(0);
    expect(AuditEvent::count())->toBe(0);
});
