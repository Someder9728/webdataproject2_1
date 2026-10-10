<?php

use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('payment_proofs');

    $this->admin = User::factory()->create([
        'u_role' => 'admin',
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $tenant = Tenant::create([
        't_Fname' => 'History',
        't_Lname' => 'Owner',
        't_tel' => '0812345678',
    ]);

    $nextTenant = Tenant::create([
        't_Fname' => 'Next',
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
        'tenants_t_id' => $nextTenant->getKey(),
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $room = Room::create([
        'r_name' => 'HISTORY-101',
        'r_floor' => 1,
        'r_type' => 'Test',
        'r_rent' => '3000.00',
        'r_status' => 'VACANT',
    ]);

    $rental = Rental::create([
        'tenants_t_id' => $tenant->getKey(),
        'rooms_r_id' => $room->getKey(),
        'rt_movein' => '2026-09-01',
        'rt_moveout' => '2026-10-01',
        'rt_status' => 'ENDED',
    ]);

    Rental::create([
        'tenants_t_id' => $nextTenant->getKey(),
        'rooms_r_id' => $room->getKey(),
        'rt_movein' => '2026-10-01',
        'rt_status' => 'ACTIVE',
    ]);

    $meters = [];

    foreach ([
        ['2026-09-01', '100.00', '1000.00'],
        ['2026-10-01', '108.00', '1050.00'],
    ] as [$date, $water, $elec]) {
        $meters[] = Meter::create([
            'rooms_r_id' => $room->getKey(),
            'm_date' => $date,
            'm_water' => $water,
            'm_elec' => $elec,
        ]);
    }

    $this->invoice = Invoice::create([
        'rentals_rt_id' => $rental->getKey(),
        'period_start' => '2026-09-01',
        'period_end' => '2026-10-01',
        'start_meter_id' => $meters[0]->getKey(),
        'end_meter_id' => $meters[1]->getKey(),
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
        'p_status' => 'PAID',
        'p_amount' => '3494.00',
        'p_date' => '2026-10-02',
        'p_type' => 'CASH',
        'p_proof' => null,
    ]);

    $this->proofPath = 'invoices/'.$this->invoice->getKey().'/old.png';
    $this->proofBytes = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aRZkAAAAASUVORK5CYII='
    );

    Storage::disk('payment_proofs')->put(
        $this->proofPath,
        $this->proofBytes
    );

    // ใช้เวลาเดียวกันทุก event เพื่อทดสอบลำดับจาก pe_id
    $snapshot = [
        'amount' => '3494.00',
        'payment_date' => '2026-10-02',
        'method' => 'TRANSFER',
        'proof_path' => $this->proofPath,
        'created_at' => '2026-10-02 05:00:00',
    ];

    $this->submitted = $this->payment->events()->create([
        ...$snapshot,
        'actor_user_id' => $this->owner->getKey(),
        'event_type' => 'PROOF_SUBMITTED',
        'from_status' => 'UNPAID',
        'to_status' => 'PENDING',
    ]);

    $this->rejected = $this->payment->events()->create([
        ...$snapshot,
        'actor_user_id' => $this->admin->getKey(),
        'event_type' => 'PAYMENT_REJECTED',
        'from_status' => 'PENDING',
        'to_status' => 'REJECTED',
        'reason' => 'หลักฐานไม่ตรงรายการ',
    ]);

    $this->walkIn = $this->payment->events()->create([
        ...$snapshot,
        'actor_user_id' => $this->admin->getKey(),
        'event_type' => 'WALK_IN_RECORDED',
        'from_status' => 'REJECTED',
        'to_status' => 'PAID',
        'method' => 'CASH',
        'proof_path' => null,
        'note' => 'รับชำระที่สำนักงาน',
    ]);

    $this->historyUrl = '/api/v1/invoices/'
        .$this->invoice->getKey().'/payment/events';

    $this->oldProofUrl = '/api/v1/payment-events/'
        .$this->submitted->getKey().'/proof';
});

test('admin reads history in stable descending order with pagination', function () {
    $response = $this->actingAs($this->admin)
        ->getJson($this->historyUrl.'?per_page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.pe_id', $this->walkIn->getKey())
        ->assertJsonPath('data.1.pe_id', $this->rejected->getKey())
        ->assertJsonPath('data.0.amount', '3494.00')
        ->assertJsonPath('data.0.actor.u_id', $this->admin->getKey())
        ->assertJsonPath('data.0.actor.u_username', $this->admin->u_username)
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.last_page', 2);

    $this->getJson($this->historyUrl.'?per_page=2&page=2')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.pe_id', $this->submitted->getKey());

    expect($response->getContent())->not->toContain($this->proofPath);

    foreach ($response->json('data') as $row) {
        expect(array_key_exists('proof_path', $row))->toBeFalse();
        expect(array_key_exists('u_password', $row['actor']))->toBeFalse();
        expect(array_key_exists('remember_token', $row['actor']))->toBeFalse();
    }
});

test('former tenant sees own history without admin account details', function () {
    $response = $this->actingAs($this->owner)
        ->getJson($this->historyUrl)
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('meta.per_page', 20)
        ->assertJsonPath('data.0.actor.is_self', false)
        ->assertJsonPath('data.2.actor.is_self', true)
        ->assertJsonPath('data.1.reason', 'หลักฐานไม่ตรงรายการ')
        ->assertJsonPath('data.0.note', 'รับชำระที่สำนักงาน');

    foreach ($response->json('data') as $row) {
        expect(array_keys($row['actor']))->toBe(['is_self']);
        expect(array_key_exists('actor_user_id', $row))->toBeFalse();
        expect(array_key_exists('proof_path', $row))->toBeFalse();
    }

    expect($response->getContent())->not->toContain($this->proofPath);
});

test('new tenant of same room cannot access previous tenant history or proof', function () {
    $this->actingAs($this->otherUser)
        ->getJson($this->historyUrl.'?tenants_t_id='.$this->owner->tenants_t_id)
        ->assertNotFound();

    $this->getJson(
        $this->oldProofUrl.'?actor_user_id='.$this->admin->getKey()
    )->assertNotFound();
});

test('guest cannot access history or historical proof', function () {
    $this->get($this->historyUrl)
        ->assertUnauthorized()
        ->assertJsonPath('code', 'UNAUTHENTICATED');

    $this->get($this->oldProofUrl)
        ->assertUnauthorized()
        ->assertJsonPath('code', 'UNAUTHENTICATED');
});

test('restricted account cannot access either endpoint', function (
    string $field,
    mixed $value,
    string $code
) {
    $this->owner->forceFill([$field => $value])->save();

    expect($this->owner->fresh()->getAttribute($field))->toBe($value);

    foreach ([$this->historyUrl, $this->oldProofUrl] as $url) {
        $this->actingAs($this->owner->fresh())
            ->getJson($url)
            ->assertForbidden()
            ->assertJsonPath('code', $code);
    }
})->with([
    ['is_active', false, 'ACCOUNT_INACTIVE'],
    ['must_change_password', true, 'PASSWORD_CHANGE_REQUIRED'],
]);

test('admin and former tenant download old proof after walk in', function () {
    expect($this->payment->fresh()->p_proof)->toBeNull();

    foreach ([$this->admin, $this->owner] as $user) {
        $response = $this->actingAs($user)
            ->get($this->oldProofUrl)
            ->assertOk()
            ->assertDownload(
                'payment-event-'.$this->submitted->getKey().'.png'
            )
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        expect($response->headers->get('Cache-Control'))
            ->toContain('private')
            ->toContain('no-store');

        expect($response->streamedContent())->toBe($this->proofBytes);
    }
});

test('proofless event and missing file return 404', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/v1/payment-events/'.$this->walkIn->getKey().'/proof')
        ->assertNotFound();

    Storage::disk('payment_proofs')->delete($this->proofPath);

    $this->getJson($this->oldProofUrl)->assertNotFound();
});

test('unsupported historical file content is not downloaded', function () {
    Storage::disk('payment_proofs')->put(
        $this->proofPath,
        'This is plain text, not an image.'
    );

    $this->actingAs($this->admin)
        ->getJson($this->oldProofUrl)
        ->assertNotFound();
});

test('invalid pagination is rejected', function (string $query, string $field) {
    $this->actingAs($this->admin)
        ->getJson($this->historyUrl.'?'.$query)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    ['page=0', 'page'],
    ['page=abc', 'page'],
    ['per_page=0', 'per_page'],
    ['per_page=101', 'per_page'],
    ['per_page=abc', 'per_page'],
]);

test('deleted invoice or payment cannot expose history or proof', function (
    string $record
) {
    if ($record === 'invoice') {
        $this->invoice->delete();
    } else {
        $this->payment->delete();
    }

    $this->actingAs($this->admin)
        ->getJson($this->historyUrl)
        ->assertNotFound();

    $this->getJson($this->oldProofUrl)->assertNotFound();
})->with(['invoice', 'payment']);

test('admin can identify a soft deleted historical actor', function () {
    $username = $this->owner->u_username;
    $this->owner->delete();

    $this->actingAs($this->admin)
        ->getJson($this->historyUrl)
        ->assertOk()
        ->assertJsonPath('data.2.actor.u_id', $this->owner->getKey())
        ->assertJsonPath('data.2.actor.u_username', $username);
});

test('history and download do not modify payment or events', function () {
    $paymentBefore = $this->payment->fresh()->getAttributes();
    $eventsBefore = $this->payment->events()->get()
        ->map(fn ($event) => $event->getAttributes())
        ->all();

    $this->actingAs($this->admin)
        ->getJson($this->historyUrl)
        ->assertOk();

    $response = $this->get($this->oldProofUrl)->assertOk();
    expect($response->streamedContent())->toBe($this->proofBytes);

    expect($this->payment->fresh()->getAttributes())->toBe($paymentBefore);
    expect(
        $this->payment->events()->get()
            ->map(fn ($event) => $event->getAttributes())
            ->all()
    )->toBe($eventsBefore);
});
