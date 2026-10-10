<?php

use App\Models\AuditEvent;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Filesystem\FilesystemAdapter;
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

    $this->payload = [
        'amount' => '3494.00',
        'payment_date' => '2026-10-02',
    ];
});

afterEach(function () {
    $this->travelBack();
});

test('former tenant submits proof with payment event and audit', function () {
    $response = $this->actingAs($this->owner)
        ->post($this->submitUrl, [
            ...$this->payload,
            'proof' => UploadedFile::fake()->image('receipt.png'),
            'p_status' => 'PAID',
            'actor_user_id' => $this->admin->getKey(),
        ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('data.p_status', 'PENDING')
        ->assertJsonPath('data.p_type', 'TRANSFER')
        ->assertJsonPath('data.p_amount', '3494.00')
        ->assertJsonPath('data.has_proof', true);

    $payment = $this->payment->fresh();

    Storage::disk('payment_proofs')->assertExists($payment->p_proof);

    expect($response->getContent())
        ->not->toContain($payment->p_proof)
        ->not->toContain('"p_proof"');

    $this->assertDatabaseHas('payment_events', [
        'payments_p_id' => $payment->getKey(),
        'actor_user_id' => $this->owner->getKey(),
        'event_type' => 'PROOF_SUBMITTED',
        'from_status' => 'UNPAID',
        'to_status' => 'PENDING',
        'proof_path' => $payment->p_proof,
    ]);

    $this->assertDatabaseHas('audit_events', [
        'actor_user_id' => $this->owner->getKey(),
        'entity_type' => 'payments',
        'entity_id' => $payment->getKey(),
        'action' => 'payment_proof_submitted',
    ]);

    $this->assertDatabaseCount('payment_events', 1);
    $this->assertDatabaseCount('audit_events', 1);
});

test('guest admin and another tenant cannot submit owner proof', function () {
    $makePayload = fn () => [
        ...$this->payload,
        'proof' => UploadedFile::fake()->image('receipt.png'),
    ];

    $this->post($this->submitUrl, $makePayload(), [
        'Accept' => 'application/json',
    ])->assertUnauthorized();

    $this->actingAs($this->admin)
        ->post($this->submitUrl, $makePayload(), [
            'Accept' => 'application/json',
        ])->assertForbidden();

    $this->actingAs($this->otherUser)
        ->post($this->submitUrl, $makePayload(), [
            'Accept' => 'application/json',
        ])->assertNotFound();

    expect(Storage::disk('payment_proofs')->allFiles())->toBe([]);
    expect($this->payment->fresh()->p_status)->toBe('UNPAID');

    $this->assertDatabaseCount('payment_events', 0);
});

test('invalid amount or date leaves payment and storage unchanged', function ($changes, $field) {
    $this->actingAs($this->owner)
        ->post($this->submitUrl, [
            ...$this->payload,
            ...$changes,
            'proof' => UploadedFile::fake()->image('receipt.png'),
        ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);

    expect($this->payment->fresh()->p_status)->toBe('UNPAID');
    expect(Storage::disk('payment_proofs')->allFiles())->toBe([]);

    $this->assertDatabaseCount('payment_events', 0);
    $this->assertDatabaseCount('audit_events', 0);
})->with([
    'underpayment' => [['amount' => '3493.00'], 'amount'],
    'overpayment' => [['amount' => '3495.00'], 'amount'],
    'future date' => [['payment_date' => '2026-10-03'], 'payment_date'],
    'before invoice' => [['payment_date' => '2026-09-30'], 'payment_date'],
]);

test('invalid proof is rejected without stored files', function ($kind) {
    $file = match ($kind) {
        'oversized' => UploadedFile::fake()->image('large.png')->size(5121),
        'wrong content' => UploadedFile::fake()->createWithContent(
            'pretend.jpg',
            'This is plain text, not an image.'
        ),
        'wrong extension' => UploadedFile::fake()->image('image.gif'),
    };

    $this->actingAs($this->owner)
        ->post($this->submitUrl, [
            ...$this->payload,
            'proof' => $file,
        ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('proof');

    expect(Storage::disk('payment_proofs')->allFiles())->toBe([]);
    expect($this->payment->fresh()->p_status)->toBe('UNPAID');
})->with(['oversized', 'wrong extension']);

test('pending or paid payment rejects another upload', function ($status) {
    $this->payment->update(['p_status' => $status]);

    $this->actingAs($this->owner)
        ->post($this->submitUrl, [
            ...$this->payload,
            'proof' => UploadedFile::fake()->image('receipt.png'),
        ], ['Accept' => 'application/json'])
        ->assertConflict();

    expect($this->payment->fresh()->p_status)->toBe($status);
    expect(Storage::disk('payment_proofs')->allFiles())->toBe([]);

    $this->assertDatabaseCount('payment_events', 0);
})->with(['PENDING', 'PAID']);

test('resubmission retains rejected proof and its historical reference', function () {
    $oldPath = 'invoices/'.$this->invoice->getKey().'/old-proof.png';

    Storage::disk('payment_proofs')->put($oldPath, 'old proof fixture');

    $this->payment->update([
        'p_status' => 'REJECTED',
        'p_proof' => $oldPath,
        'p_reject_reason' => 'หลักฐานไม่ชัดเจน',
    ]);

    $oldEvent = $this->payment->events()->create([
        'actor_user_id' => $this->admin->getKey(),
        'event_type' => 'REJECTED',
        'from_status' => 'PENDING',
        'to_status' => 'REJECTED',
        'amount' => '3494.00',
        'proof_path' => $oldPath,
        'reason' => 'หลักฐานไม่ชัดเจน',
    ]);

    $this->actingAs($this->owner)
        ->post($this->submitUrl, [
            ...$this->payload,
            'proof' => UploadedFile::fake()->image('new-proof.png'),
        ], ['Accept' => 'application/json'])
        ->assertOk();

    $payment = $this->payment->fresh();

    expect($payment->p_status)->toBe('PENDING');
    expect($payment->p_reject_reason)->toBeNull();
    expect($payment->p_proof)->not->toBe($oldPath);
    expect($oldEvent->fresh()->proof_path)->toBe($oldPath);

    Storage::disk('payment_proofs')->assertExists($oldPath);
    Storage::disk('payment_proofs')->assertExists($payment->p_proof);

    $this->assertDatabaseCount('payment_events', 2);
});

test('only owner and admin can download proof', function () {
    $this->actingAs($this->owner)
        ->post($this->submitUrl, [
            ...$this->payload,
            'proof' => UploadedFile::fake()->image('receipt.png'),
        ], ['Accept' => 'application/json'])
        ->assertOk();

    foreach ([$this->owner, $this->admin] as $user) {
        $response = $this->actingAs($user)
            ->get($this->proofUrl)
            ->assertOk()
            ->assertDownload(
                'payment-proof-'.$this->payment->getKey().'.png'
            )
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        expect($response->headers->get('Cache-Control'))
            ->toContain('no-store');
    }

    $this->actingAs($this->otherUser)
        ->getJson($this->proofUrl)
        ->assertNotFound();
});

test('missing proof returns 404', function () {
    $this->actingAs($this->owner)
        ->getJson($this->proofUrl)
        ->assertNotFound();

    $this->payment->update(['p_proof' => 'missing/file.png']);

    $this->getJson($this->proofUrl)->assertNotFound();
});

test('audit failure rolls back payment and event and removes new file', function () {
    $attempted = false;
    $event = 'eloquent.creating: '.AuditEvent::class;

    Event::listen($event, function () use (&$attempted) {
        $attempted = true;
        throw new RuntimeException('Simulated proof audit failure');
    });

    try {
        $this->actingAs($this->owner)
            ->post($this->submitUrl, [
                ...$this->payload,
                'proof' => UploadedFile::fake()->image('receipt.png'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(500);
    } finally {
        Event::forget($event);
    }

    expect($attempted)->toBeTrue();
    expect($this->payment->fresh()->p_status)->toBe('UNPAID');
    expect($this->payment->fresh()->p_proof)->toBeNull();
    expect(Storage::disk('payment_proofs')->allFiles())->toBe([]);

    $this->assertDatabaseCount('payment_events', 0);
    $this->assertDatabaseCount('audit_events', 0);
});

test('restricted owner cannot submit or download proof', function ($flag, $value, $code) {
    $this->owner->forceFill([$flag => $value])->save();

    $this->actingAs($this->owner->fresh())
        ->post($this->submitUrl, [
            ...$this->payload,
            'proof' => UploadedFile::fake()->image('receipt.png'),
        ], ['Accept' => 'application/json'])
        ->assertForbidden()
        ->assertJsonPath('code', $code);

    $this->actingAs($this->owner->fresh())
        ->getJson($this->proofUrl)
        ->assertForbidden()
        ->assertJsonPath('code', $code);

    expect(Storage::disk('payment_proofs')->allFiles())->toBe([]);
})->with([
    'inactive' => ['is_active', false, 'ACCOUNT_INACTIVE'],
    'password change' => [
        'must_change_password',
        true,
        'PASSWORD_CHANGE_REQUIRED',
    ],
]);

test('text disguised as jpg is rejected using actual file content', function () {
    $path = tempnam(sys_get_temp_dir(), 'proof-test-');

    if ($path === false) {
        throw new RuntimeException('Cannot create temporary test file.');
    }

    try {
        file_put_contents($path, 'This is plain text, not an image.');

        $file = new UploadedFile(
            $path,
            'pretend.jpg',
            'image/jpeg', // แม้ client อ้างว่าเป็น JPEG
            UPLOAD_ERR_OK,
            true
        );

        expect($file->getMimeType())->not->toBe('image/jpeg');

        $this->actingAs($this->owner)
            ->post($this->submitUrl, [
                ...$this->payload,
                'proof' => $file,
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('proof');

        expect(Storage::disk('payment_proofs')->allFiles())->toBe([]);
        expect($this->payment->fresh()->p_status)->toBe('UNPAID');

        $this->assertDatabaseCount('payment_events', 0);
        $this->assertDatabaseCount('audit_events', 0);
    } finally {
        if (is_file($path)) {
            unlink($path);
        }
    }
});

test('real jpeg or pdf proof can be submitted and downloaded', function ($type) {
    if ($type === 'jpg') {
        $fixture = UploadedFile::fake()->image('receipt.jpg');

        $file = new UploadedFile(
            $fixture->getPathname(),
            'receipt.jpg',
            'image/jpeg',
            UPLOAD_ERR_OK,
            true
        );

        $expectedMime = 'image/jpeg';
    } else {
        // สร้าง PDF ขนาดเล็กพร้อม xref ที่อ้างตำแหน่งจริง
        $pdf = "%PDF-1.4\n";
        $offsets = [];

        $stream = "BT /F1 12 Tf 20 100 Td (Payment proof test) Tj ET\n";

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 200 200] '
                .'/Resources << /Font << /F1 4 0 R >> >> '
                .'/Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Length '.strlen($stream)." >>\nstream\n"
                .$stream.'endstream',
        ];

        foreach ($objects as $index => $object) {
            $id = $index + 1;
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$object}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $fixture = UploadedFile::fake()->createWithContent(
            'receipt.pdf',
            $pdf
        );

        // ใช้ UploadedFile ปกติเพื่อตรวจ MIME จากเนื้อหา
        $file = new UploadedFile(
            $fixture->getPathname(),
            'receipt.pdf',
            'application/pdf',
            UPLOAD_ERR_OK,
            true
        );

        $expectedMime = 'application/pdf';
    }

    expect($file->getMimeType())->toBe($expectedMime);

    $this->actingAs($this->owner)
        ->post($this->submitUrl, [
            ...$this->payload,
            'proof' => $file,
        ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('data.p_status', 'PENDING');

    $payment = $this->payment->fresh();

    Storage::disk('payment_proofs')->assertExists($payment->p_proof);

    $response = $this->get($this->proofUrl)
        ->assertOk()
        ->assertHeader('Content-Type', $expectedMime)
        ->assertDownload(
            'payment-proof-'.$payment->getKey().'.'.$type
        );

    expect($response->streamedContent())
        ->toBe(Storage::disk('payment_proofs')->get($payment->p_proof));

    $this->assertDatabaseCount('payment_events', 1);
    $this->assertDatabaseCount('audit_events', 1);
})->with(['jpg', 'pdf']);

test('proof exactly five MiB is accepted', function () {
    $this->actingAs($this->owner)
        ->post($this->submitUrl, [
            ...$this->payload,
            'proof' => UploadedFile::fake()
                ->image('boundary.png')
                ->size(5120),
        ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('data.p_status', 'PENDING');

    Storage::disk('payment_proofs')->assertExists(
        $this->payment->fresh()->p_proof
    );
});

test('storage failure creates no payment changes or history', function () {
    $disk = Mockery::mock(
        FilesystemAdapter::class
    );

    $disk->shouldReceive('putFile')
        ->once()
        ->andThrow(new RuntimeException('Simulated storage failure'));

    Storage::shouldReceive('disk')
        ->with('payment_proofs')
        ->once()
        ->andReturn($disk);

    $this->actingAs($this->owner)
        ->post($this->submitUrl, [
            ...$this->payload,
            'proof' => UploadedFile::fake()->image('receipt.png'),
        ], ['Accept' => 'application/json'])
        ->assertStatus(500);

    $payment = $this->payment->fresh();

    expect($payment->p_status)->toBe('UNPAID');
    expect($payment->p_proof)->toBeNull();

    $this->assertDatabaseCount('payment_events', 0);
    $this->assertDatabaseCount('audit_events', 0);
});
