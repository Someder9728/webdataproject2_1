<?php

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
    $path = 'invoices/'.$this->invoice->getKey().'/original.pdf';
    Storage::disk('payment_proofs')->put($path, '%PDF-1.4 review fixture');
    $this->payment->update([
        'p_status' => 'PENDING', 'p_type' => 'TRANSFER',
        'p_date' => '2026-10-02', 'p_proof' => $path,
    ]);
    $this->submittedEvent = $this->payment->events()->create([
        'actor_user_id' => $this->owner->getKey(),
        'event_type' => 'PROOF_SUBMITTED',
        'from_status' => 'UNPAID', 'to_status' => 'PENDING',
        'amount' => '3494.00', 'payment_date' => '2026-10-02',
        'method' => 'TRANSFER', 'proof_path' => $path,
    ]);

    $this->payload = [
        'amount' => '3494.00',
        'payment_date' => '2026-10-02',
    ];
});

afterEach(function () {
    $this->travelBack();
});


test('admin reviews proof and preserves payment snapshot', function (string $decision, string $status) {
    $path = $this->payment->p_proof;
    $this->actingAs($this->admin)->postJson($this->reviewUrl, [
        'decision' => $decision,
        'expected_event_id' => $this->submittedEvent->getKey(),
        'reason' => 'หลักฐานไม่ตรงรายการ',
        'p_amount' => '1.00',
        'actor_user_id' => $this->owner->getKey(),
    ])->assertOk()->assertJsonPath('data.p_status', $status)
        ->assertJsonPath('data.p_amount', '3494.00');

    $payment = $this->payment->fresh();
    expect($payment->p_proof)->toBe($path);
    expect($payment->p_date->format('Y-m-d'))->toBe('2026-10-02');
    expect($payment->p_reject_reason)->toBe($decision === 'reject' ? 'หลักฐานไม่ตรงรายการ' : null);
    Storage::disk('payment_proofs')->assertExists($path);
    expect($payment->events()->count())->toBe(2);
    $event = $payment->events()->reorder()->orderByDesc('pe_id')->first();
    expect($event->actor_user_id)->toBe($this->admin->getKey());
    expect($event->from_status)->toBe('PENDING');
    expect($event->to_status)->toBe($status);
    expect($event->proof_path)->toBe($path);
    expect(AuditEvent::count())->toBe(1);
    expect(json_encode(AuditEvent::first()->new_values))->not->toContain($path);
})->with([['approve', 'PAID'], ['reject', 'REJECTED']]);

test('guest and tenant cannot review proof', function () {
    $payload = ['decision' => 'approve', 'expected_event_id' => $this->submittedEvent->getKey()];
    $this->postJson($this->reviewUrl, $payload)->assertUnauthorized();
    $this->actingAs($this->owner)->postJson($this->reviewUrl, $payload)->assertForbidden();
    expect($this->payment->fresh()->p_status)->toBe('PENDING');
    expect(AuditEvent::count())->toBe(0);
});

test('action independently rejects restricted actors', function (string $restriction) {
    $actor = $this->admin;

    $changes = match ($restriction) {
        'inactive' => ['is_active' => false],
        'password' => ['must_change_password' => true],
        'tenant' => ['u_role' => 'tenant'],
    };

    $actor->forceFill($changes)->save();

    // ยืนยันว่าฐานข้อมูลอยู่ในสถานะที่ต้องการทดสอบจริง
    foreach ($changes as $field => $value) {
        expect($actor->fresh()->getAttribute($field))->toBe($value);
    }
    try {
        app(\App\Actions\Payments\ReviewPaymentProof::class)->handle($actor, $this->invoice, [
            'decision' => 'approve', 'expected_event_id' => $this->submittedEvent->getKey(),
        ]);
        $this->fail('Restricted actor was allowed');
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        expect($e->getStatusCode())->toBe(403);
    }
    expect($this->payment->fresh()->p_status)->toBe('PENDING');
})->with(['inactive', 'password', 'tenant']);

test('review validates decision version and rejection reason', function (array $payload, string $field) {
    $this->actingAs($this->admin)->postJson($this->reviewUrl, $payload)
        ->assertUnprocessable()->assertJsonValidationErrors($field);
    expect($this->payment->fresh()->p_status)->toBe('PENDING');
    expect(AuditEvent::count())->toBe(0);
})->with([
    [['decision' => 'invalid', 'expected_event_id' => 1], 'decision'],
    [['decision' => 'approve'], 'expected_event_id'],
    [['decision' => 'reject', 'expected_event_id' => 1], 'reason'],
    [['decision' => 'reject', 'expected_event_id' => 1, 'reason' => '   '], 'reason'],
]);

test('non pending payments cannot be reviewed', function (string $status) {
    $this->payment->update(['p_status' => $status]);
    $this->actingAs($this->admin)->postJson($this->reviewUrl, [
        'decision' => 'approve', 'expected_event_id' => $this->submittedEvent->getKey(),
    ])->assertConflict();
    expect($this->payment->fresh()->p_status)->toBe($status);
    expect(AuditEvent::count())->toBe(0);
})->with(['UNPAID', 'PAID', 'REJECTED']);

test('duplicate decision creates only one review event and audit', function () {
    $payload = ['decision' => 'approve', 'expected_event_id' => $this->submittedEvent->getKey()];
    $this->actingAs($this->admin)->postJson($this->reviewUrl, $payload)->assertOk();
    $this->postJson($this->reviewUrl, $payload)->assertConflict();
    expect($this->payment->events()->count())->toBe(2);
    expect(AuditEvent::count())->toBe(1);
});

test('old review cannot approve a resubmitted proof', function () {
    $oldId = $this->submittedEvent->getKey();
    $this->actingAs($this->admin)->postJson($this->reviewUrl, [
        'decision' => 'reject', 'expected_event_id' => $oldId, 'reason' => 'ส่งใหม่',
    ])->assertOk();
    $this->actingAs($this->owner)->post($this->submitUrl, [
        ...$this->payload, 'proof' => UploadedFile::fake()->image('new.png'),
    ], ['Accept' => 'application/json'])->assertOk();
    $this->actingAs($this->admin)->postJson($this->reviewUrl, [
        'decision' => 'approve', 'expected_event_id' => $oldId,
    ])->assertConflict();
    $newId = $this->payment->events()->reorder()->orderByDesc('pe_id')->value('pe_id');
    $this->getJson('/api/v1/invoices/'.$this->invoice->getKey().'/payment')
        ->assertOk()->assertJsonPath('data.latest_event_id', $newId);
    $this->postJson($this->reviewUrl, [
        'decision' => 'approve', 'expected_event_id' => $newId,
    ])->assertOk()->assertJsonPath('data.p_status', 'PAID');
});

test('approval refuses missing proof or mismatched amount', function (string $problem) {
    if ($problem === 'file') {
        Storage::disk('payment_proofs')->delete($this->payment->p_proof);
    } else {
        $this->payment->update(['p_amount' => '1.00']);
    }
    $this->actingAs($this->admin)->postJson($this->reviewUrl, [
        'decision' => 'approve', 'expected_event_id' => $this->submittedEvent->getKey(),
    ])->assertConflict();
    expect($this->payment->fresh()->p_status)->toBe('PENDING');
    expect(AuditEvent::count())->toBe(0);
})->with(['file', 'amount']);

test('audit failure rolls back decision and review event without removing proof', function () {
    Event::listen('eloquent.creating: '.AuditEvent::class, function () {
        throw new \RuntimeException('review audit failure');
    });
    try {
        app(\App\Actions\Payments\ReviewPaymentProof::class)->handle($this->admin, $this->invoice, [
            'decision' => 'approve', 'expected_event_id' => $this->submittedEvent->getKey(),
        ]);
        $this->fail('Expected audit failure');
    } catch (\RuntimeException $e) {
        expect($e->getMessage())->toBe('review audit failure');
    } finally {
        Event::forget('eloquent.creating: '.AuditEvent::class);
    }
    expect($this->payment->fresh()->p_status)->toBe('PENDING');
    expect($this->payment->events()->count())->toBe(1);
    expect(AuditEvent::count())->toBe(0);
    Storage::disk('payment_proofs')->assertExists($this->payment->p_proof);
});
