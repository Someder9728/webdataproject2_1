<?php

namespace App\Actions\Payments;

use App\Models\AuditEvent;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Support\SqliteTransaction;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RecordWalkInPayment
{
    public function __construct(private SqliteTransaction $transactions) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $actor, Invoice $invoice, array $input): Payment
    {
        return $this->transactions->run(function () use ($actor, $invoice, $input) {
            $currentActor = User::whereKey($actor->getKey())->firstOrFail();
            abort_unless(
                $currentActor->is_active && ! $currentActor->must_change_password
                && $currentActor->u_role === 'admin',
                403
            );

            $validated = Validator::make($input, [
                'amount' => ['required', 'numeric', 'regex:/\A[0-9]{1,8}(?:\.[0-9]{1,2})?\z/'],
                'payment_date' => ['required', 'date_format:Y-m-d',
                    'before_or_equal:'.now('Asia/Bangkok')->toDateString()],
                'method' => ['required', 'in:CASH,TRANSFER'],
                'note' => ['required', 'string', 'max:1000'],
                // Null means that no payment event existed when the admin opened the form.
                'expected_event_id' => ['present', 'nullable', 'integer', 'min:1'],
                'proof' => ['prohibited'],
            ])->validate();

            if (trim($validated['note']) === '') {
                throw ValidationException::withMessages(['note' => 'กรุณาระบุหมายเหตุการรับชำระ']);
            }

            $currentInvoice = Invoice::whereKey($invoice->getKey())->firstOrFail();
            $payment = $currentInvoice->payment()->firstOrFail();
            $oldStatus = $payment->p_status;
            $latestId = $payment->events()->reorder()->orderByDesc('pe_id')->value('pe_id');
            $expectedId = $validated['expected_event_id'];

            abort_unless(
                in_array($oldStatus, ['UNPAID', 'REJECTED'], true)
                && (($latestId === null && $expectedId === null)
                    || ($latestId !== null && $expectedId !== null && (string) $latestId === (string) $expectedId)),
                409
            );

            if (! BigDecimal::of((string) $validated['amount'])->isEqualTo($currentInvoice->i_total)) {
                throw ValidationException::withMessages(['amount' => 'ยอดชำระต้องเท่ากับยอดเต็มของใบแจ้งหนี้']);
            }
            if ($validated['payment_date'] < $currentInvoice->i_date->toDateString()) {
                throw ValidationException::withMessages(['payment_date' => 'วันชำระต้องไม่ก่อนวันออกใบแจ้งหนี้']);
            }

            // Old proof remains in historical events and private storage, not the new receipt.
            $payment->fill([
                'p_amount' => $currentInvoice->i_total,
                'p_date' => $validated['payment_date'],
                'p_type' => $validated['method'],
                'p_status' => 'PAID',
                'p_proof' => null,
                'p_reject_reason' => null,
            ])->save();

            $payment->events()->create([
                'actor_user_id' => $currentActor->getKey(),
                'event_type' => 'WALK_IN_RECORDED',
                'from_status' => $oldStatus,
                'to_status' => 'PAID',
                'amount' => $currentInvoice->i_total,
                'payment_date' => $validated['payment_date'],
                'method' => $validated['method'],
                'proof_path' => null,
                'reason' => null,
                'note' => trim($validated['note']),
            ]);

            AuditEvent::create([
                'actor_user_id' => $currentActor->getKey(),
                'entity_type' => 'payments',
                'entity_id' => $payment->getKey(),
                'action' => 'walk_in_payment_recorded',
                'old_values' => ['p_status' => $oldStatus],
                'new_values' => [
                    'p_status' => 'PAID',
                    'p_amount' => $currentInvoice->i_total,
                    'p_date' => $validated['payment_date'],
                    'p_type' => $validated['method'],
                    'note' => trim($validated['note']),
                ],
            ]);

            return $payment;
        });
    }
}
