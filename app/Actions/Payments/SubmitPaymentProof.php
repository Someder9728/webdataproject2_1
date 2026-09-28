<?php

namespace App\Actions\Payments;

use App\Models\AuditEvent;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Support\SqliteTransaction;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class SubmitPaymentProof
{
    public function __construct(
        private SqliteTransaction $transactions
    ) {}

    public function handle(
        User $actor,
        Invoice $invoice,
        array $input
    ): Payment {
        $actor = User::findOrFail($actor->getKey());
        $invoice = Invoice::findOrFail($invoice->getKey());

        Gate::forUser($actor)->authorize('submitPayment', $invoice);

        $validated = Validator::make($input, [
            'amount' => [
                'required',
                'numeric',
                'regex:/\A[0-9]{1,8}(?:\.[0-9]{1,2})?\z/',
            ],
            'payment_date' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:'.now('Asia/Bangkok')->toDateString(),
            ],
            'proof' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'extensions:jpg,jpeg,png,pdf',
                'max:5120',
            ],
        ])->validate();

        $this->validatePayment($invoice, $validated);

        $payment = $invoice->payment()->firstOrFail();

        abort_unless(
            in_array($payment->p_status, ['UNPAID', 'REJECTED'], true),
            409
        );

        $disk = Storage::disk('payment_proofs');

        // เก็บครั้งเดียวด้านนอก retry และใช้ชื่อที่ระบบสร้าง
        $path = $disk->putFile(
            'invoices/'.$invoice->getKey(),
            $validated['proof']
        );

        if ($path === false) {
            throw new \RuntimeException('Cannot store payment proof.');
        }

        try {
            return $this->transactions->run(function () use (
                $actor,
                $invoice,
                $validated,
                $path
            ) {
                // ทุก retry ต้องอ่านสถานะและสิทธิ์ใหม่
                $currentActor = User::findOrFail($actor->getKey());
                $currentInvoice = Invoice::findOrFail($invoice->getKey());

                Gate::forUser($currentActor)->authorize(
                    'submitPayment',
                    $currentInvoice
                );

                $this->validatePayment($currentInvoice, $validated);

                $payment = $currentInvoice->payment()->firstOrFail();
                $oldStatus = $payment->p_status;

                abort_unless(
                    in_array($oldStatus, ['UNPAID', 'REJECTED'], true),
                    409
                );

                $payment->fill([
                    'p_amount' => $currentInvoice->i_total,
                    'p_date' => $validated['payment_date'],
                    'p_type' => 'TRANSFER',
                    'p_status' => 'PENDING',
                    'p_proof' => $path,
                    'p_reject_reason' => null,
                ])->save();

                $payment->events()->create([
                    'actor_user_id' => $currentActor->getKey(),
                    'event_type' => 'PROOF_SUBMITTED',
                    'from_status' => $oldStatus,
                    'to_status' => 'PENDING',
                    'amount' => $currentInvoice->i_total,
                    'payment_date' => $validated['payment_date'],
                    'method' => 'TRANSFER',
                    'proof_path' => $path,
                    'reason' => null,
                    'note' => null,
                ]);

                AuditEvent::create([
                    'actor_user_id' => $currentActor->getKey(),
                    'entity_type' => 'payments',
                    'entity_id' => $payment->getKey(),
                    'action' => 'payment_proof_submitted',
                    'old_values' => [
                        'p_status' => $oldStatus,
                    ],
                    'new_values' => [
                        'p_status' => 'PENDING',
                        'p_amount' => $currentInvoice->i_total,
                        'p_date' => $validated['payment_date'],
                        'p_type' => 'TRANSFER',
                    ],
                ]);

                return $payment;
            });
        } catch (Throwable $exception) {
            // ลบเฉพาะไฟล์ใหม่ของคำขอที่บันทึกไม่สำเร็จ
            // ไม่ลบหลักฐานเก่าที่ Payment Events อ้างอิงอยู่
            try {
                if (! $disk->delete($path)) {
                    report(new \RuntimeException(
                        'Unable to clean up failed payment proof upload.'
                    ));
                }
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }

            throw $exception;
        }
    }

    private function validatePayment(
        Invoice $invoice,
        array $validated
    ): void {
        if (! BigDecimal::of((string) $validated['amount'])
            ->isEqualTo($invoice->i_total)) {
            throw ValidationException::withMessages([
                'amount' => 'ยอดชำระต้องเท่ากับยอดเต็มของใบแจ้งหนี้',
            ]);
        }

        if (
            $validated['payment_date'] <
            $invoice->i_date->toDateString()
        ) {
            throw ValidationException::withMessages([
                'payment_date' => 'วันชำระต้องไม่ก่อนวันออกใบแจ้งหนี้',
            ]);
        }
    }
}