<?php

namespace App\Actions\Payments;

use App\Models\AuditEvent;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Support\SqliteTransaction;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ReviewPaymentProof
{
    public function __construct(private SqliteTransaction $transactions) {}

    public function handle(User $actor, Invoice $invoice, array $input): Payment
    {
        return $this->transactions->run(function () use ($actor, $invoice, $input) {
            $currentActor = User::findOrFail($actor->getKey());
            abort_unless(
                $currentActor->is_active && ! $currentActor->must_change_password
                && $currentActor->u_role === 'admin',
                403
            );

            $validated = Validator::make($input, [
                'decision' => ['required', 'in:approve,reject'],
                'expected_event_id' => ['required', 'integer', 'min:1'],
                'reason' => ['required_if:decision,reject', 'nullable', 'string', 'max:1000'],
            ])->validate();

            if ($validated['decision'] === 'reject' && trim($validated['reason'] ?? '') === '') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'reason' => 'กรุณาระบุเหตุผลที่ปฏิเสธหลักฐาน',
                ]);
            }

            $currentInvoice = Invoice::findOrFail($invoice->getKey());
            $payment = $currentInvoice->payment()->firstOrFail();
            $event = $payment->events()->reorder()->orderByDesc('pe_id')->first();

            abort_unless(
                $payment->p_status === 'PENDING'
                && $event
                && (string) $event->getKey() === (string) $validated['expected_event_id']
                && $event->event_type === 'PROOF_SUBMITTED'
                && $event->to_status === 'PENDING'
                && filled($payment->p_proof)
                && $event->proof_path === $payment->p_proof,
                409
            );

            $approved = $validated['decision'] === 'approve';
            if ($approved) {
                abort_unless(
                    $payment->p_type === 'TRANSFER'
                    && $payment->p_date !== null
                    && BigDecimal::of($payment->p_amount)->isEqualTo($currentInvoice->i_total)
                    && Storage::disk('payment_proofs')->exists($payment->p_proof),
                    409
                );
            }

            $status = $approved ? 'PAID' : 'REJECTED';
            $reason = $approved ? null : trim($validated['reason']);
            $payment->fill(['p_status' => $status, 'p_reject_reason' => $reason])->save();

            $payment->events()->create([
                'actor_user_id' => $currentActor->getKey(),
                'event_type' => $approved ? 'PAYMENT_APPROVED' : 'PAYMENT_REJECTED',
                'from_status' => 'PENDING',
                'to_status' => $status,
                'amount' => $payment->p_amount,
                'payment_date' => $payment->p_date?->format('Y-m-d'),
                'method' => $payment->p_type,
                'proof_path' => $payment->p_proof,
                'reason' => $reason,
                'note' => null,
            ]);

            AuditEvent::create([
                'actor_user_id' => $currentActor->getKey(),
                'entity_type' => 'payments',
                'entity_id' => $payment->getKey(),
                'action' => $approved ? 'payment_approved' : 'payment_rejected',
                'old_values' => ['p_status' => 'PENDING'],
                'new_values' => [
                    'p_status' => $status,
                    'p_reject_reason' => $reason,
                    'reviewed_event_id' => $event->getKey(),
                ],
            ]);

            return $payment;
        });
    }
}
