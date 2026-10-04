<?php

use App\Actions\Payments\RecordWalkInPayment;
use App\Actions\Payments\ReviewPaymentProof;
use App\Actions\Payments\SubmitPaymentProof;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->app->bind(
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

    // ใช้ routes จริง แต่แทนข้อมูล Invoice และ Action
    // เพื่อทดสอบเฉพาะว่า CSRF gate อนุญาตให้เข้าถึง Action หรือไม่
    Route::bind('invoice', function ($value) {
        $invoice = new Invoice;
        $invoice->setAttribute('i_id', (int) $value);

        return $invoice;
    });

    $this->csrfToken = str_repeat('a', 40);
});

test('payment endpoints reject requests without valid csrf evidence', function (
    string $endpoint,
    string $role,
    string $actionClass,
    string $scenario
) {
    $user = User::factory()->create([
        'u_role' => $role,
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->mock($actionClass, function ($mock) {
        $mock->shouldNotReceive('handle');
    });

    $headers = match ($scenario) {
        'missing' => [],
        'wrong' => [
            'X-CSRF-TOKEN' => str_repeat('b', 40),
        ],
        'cross-site' => [
            'Sec-Fetch-Site' => 'cross-site',
        ],
    };

    $this->actingAs($user, 'web')
        ->withSession(['_token' => $this->csrfToken])
        ->postJson(
            '/api/v1/invoices/123/payment/'.$endpoint,
            [],
            $headers
        )
        ->assertStatus(419)
        ->assertJsonPath('code', 'CSRF_TOKEN_MISMATCH');
})->with([
    ['submit', 'tenant', SubmitPaymentProof::class],
    ['review', 'admin', ReviewPaymentProof::class],
    ['walk-in', 'admin', RecordWalkInPayment::class],
])->with(['missing', 'wrong', 'cross-site']);

test('payment endpoints reach action with matching session csrf token', function (
    string $endpoint,
    string $role,
    string $actionClass
) {
    $user = User::factory()->create([
        'u_role' => $role,
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $payment = new Payment;

    $payment->forceFill([
        'p_id' => 456,
        'invoices_i_id' => 123,
        'p_amount' => '3494.00',
        'p_date' => '2026-10-02',
        'p_type' => 'TRANSFER',
        'p_status' => $endpoint === 'submit' ? 'PENDING' : 'PAID',
        'p_proof' => null,
        'p_reject_reason' => null,
    ]);

    $this->mock($actionClass, function ($mock) use ($payment, $user) {
        $mock->shouldReceive('handle')
            ->once()
            ->withArgs(function ($actor, $invoice, $input) use ($user) {
                return $actor instanceof User
                    && $actor->getKey() === $user->getKey()
                    && $invoice instanceof Invoice
                    && (int) $invoice->getKey() === 123
                    && is_array($input);
            })
            ->andReturn($payment);
    });

    $this->actingAs($user, 'web')
        ->withSession(['_token' => $this->csrfToken])
        ->postJson(
            '/api/v1/invoices/123/payment/'.$endpoint,
            [],
            ['X-CSRF-TOKEN' => $this->csrfToken]
        )
        ->assertOk()
        ->assertJsonPath('data.p_id', 456);

    $this->assertDatabaseCount('payments', 0);
    $this->assertDatabaseCount('payment_events', 0);
    $this->assertDatabaseCount('audit_events', 0);
})->with([
    ['submit', 'tenant', SubmitPaymentProof::class],
    ['review', 'admin', ReviewPaymentProof::class],
    ['walk-in', 'admin', RecordWalkInPayment::class],
]);
