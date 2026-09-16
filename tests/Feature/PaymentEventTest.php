<?php

use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->actor = User::factory()->create();

    $tenant = Tenant::create([
        't_Fname' => 'Payment',
        't_Lname' => 'Test',
        't_tel' => '0811111111',
    ]);

    $room = Room::create([
        'r_name' => 'PAY-101',
        'r_floor' => 1,
        'r_type' => 'STANDARD',
        'r_rent' => 3000,
        'r_status' => 'OCCUPIED',
    ]);

    $rental = Rental::create([
        'tenants_t_id' => $tenant->getKey(),
        'rooms_r_id' => $room->getKey(),
        'rt_movein' => '2026-09-01',
        'rt_status' => 'ACTIVE',
    ]);

    $startMeter = Meter::create([
        'rooms_r_id' => $room->getKey(),
        'm_date' => '2026-09-01',
        'm_water' => 100,
        'm_elec' => 1000,
    ]);

    $endMeter = Meter::create([
        'rooms_r_id' => $room->getKey(),
        'm_date' => '2026-10-01',
        'm_water' => 108,
        'm_elec' => 1050,
    ]);

    $invoice = Invoice::create([
        'rentals_rt_id' => $rental->getKey(),
        'i_date' => '2026-10-01',
        'i_due' => '2026-10-08',
        'period_start' => '2026-09-01',
        'period_end' => '2026-10-01',
        'start_meter_id' => $startMeter->getKey(),
        'end_meter_id' => $endMeter->getKey(),
        'water_usage' => 8,
        'elec_usage' => 50,
        'water_rate' => 18,
        'elec_rate' => 7,
        'rent_rate' => 3000,
        'i_rent' => 3000,
        'i_water' => 144,
        'i_elec' => 350,
        'i_total' => 3494,
    ]);

    $this->payment = Payment::create([
        'invoices_i_id' => $invoice->getKey(),
        'p_amount' => 3494,
        'p_status' => 'UNPAID',
    ]);

    $this->eventData = [
        'payments_p_id' => $this->payment->getKey(),
        'actor_user_id' => $this->actor->getKey(),
        'event_type' => 'SUBMITTED',
        'from_status' => 'UNPAID',
        'to_status' => 'PENDING',
        'amount' => 3494,
        'payment_date' => '2026-10-02',
        'method' => 'TRANSFER',
        'proof_path' => 'payment-proofs/test-proof.pdf',
        'reason' => null,
        'note' => null,
    ];
});

test('payment event stores snapshot and resolves relationships', function () {
    $event = PaymentEvent::create($this->eventData)->fresh();

    expect($event->amount)->toBe('3494.00')
        ->and($event->payment_date->toDateString())->toBe('2026-10-02')
        ->and($event->proof_path)->toBe('payment-proofs/test-proof.pdf')
        ->and($event->created_at)->not->toBeNull()
        ->and($event->payment->getKey())->toBe($this->payment->getKey())
        ->and($event->actor->getKey())->toBe($this->actor->getKey());

    $this->assertDatabaseCount('payment_events', 1);
});

test('payment can retain multiple events in stable order', function () {
    $this->freezeTime();

    $submitted = PaymentEvent::create($this->eventData);

    $rejected = PaymentEvent::create(array_replace($this->eventData, [
        'event_type' => 'REJECTED',
        'from_status' => 'PENDING',
        'to_status' => 'REJECTED',
        'reason' => 'Proof does not match the payment.',
    ]));

    expect(
        $this->payment->events()->pluck('pe_id')->all()
    )->toBe([
        $submitted->getKey(),
        $rejected->getKey(),
    ]);

    expect($submitted->fresh()->to_status)->toBe('PENDING')
        ->and($submitted->fresh()->reason)->toBeNull();

    $this->assertDatabaseCount('payments', 1);
    $this->assertDatabaseCount('payment_events', 2);
});

test('database rejects payment event updates', function () {
    $event = PaymentEvent::create($this->eventData);

    expect(fn () => DB::table('payment_events')
        ->where('pe_id', $event->getKey())
        ->update(['amount' => 1])
    )->toThrow(
        QueryException::class,
        'Payment events cannot be updated'
    );

    expect($event->fresh()->amount)->toBe('3494.00');
});

test('database rejects payment event deletion', function () {
    $event = PaymentEvent::create($this->eventData);

    expect(fn () => DB::table('payment_events')
        ->where('pe_id', $event->getKey())
        ->delete()
    )->toThrow(
        QueryException::class,
        'Payment events cannot be deleted'
    );

    $this->assertDatabaseHas('payment_events', [
        'pe_id' => $event->getKey(),
    ]);
});

test('payment event rejects nonexistent references', function (
    string $field,
    string $table,
    string $key
) {
    $missingId = ((int) DB::table($table)->max($key)) + 1;

    expect(fn () => PaymentEvent::create(array_replace(
        $this->eventData,
        [$field => $missingId]
    )))->toThrow(QueryException::class, 'FOREIGN KEY constraint failed');
})->with([
    'payment' => ['payments_p_id', 'payments', 'p_id'],
    'actor' => ['actor_user_id', 'users', 'u_id'],
]);

test('payment event requires an actor', function () {
    $data = $this->eventData;
    unset($data['actor_user_id']);

    expect(fn () => PaymentEvent::create($data))
        ->toThrow(QueryException::class, 'NOT NULL constraint failed');
});

test('event prevents physical deletion of referenced records', function (
    string $table,
    string $key,
    string $eventField
) {
    PaymentEvent::create($this->eventData);

    expect(fn () => DB::table($table)
        ->where($key, $this->eventData[$eventField])
        ->delete()
    )->toThrow(QueryException::class, 'FOREIGN KEY constraint failed');
})->with([
    'payment' => ['payments', 'p_id', 'payments_p_id'],
    'actor' => ['users', 'u_id', 'actor_user_id'],
]);