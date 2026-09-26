<?php

namespace App\Actions\Billing;

use App\Models\AuditEvent;
use App\Models\Invoice;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class IssueInvoice
{
    public function __construct(
        private CalculateInvoice $calculator
    ) {}

    public function handle(
        User $actor,
        array $input,
        bool $persist = false
    ): array {
        return DB::transaction(function () use ($actor, $input, $persist) {
            $actor = User::find($actor->getKey());

            abort_unless(
                $actor &&
                $actor->is_active &&
                ! $actor->must_change_password &&
                $actor->u_role === 'admin',
                403
            );

            $validated = Validator::make($input, [
                'rentals_rt_id' => ['required', 'integer', 'min:1'],
                'period_start' => ['required', 'date_format:Y-m-d'],
                'period_end' => [
                    'required',
                    'date_format:Y-m-d',
                    'after:period_start',
                ],
            ])->validate();

            $rental = Rental::findOrFail($validated['rentals_rt_id']);

            $snapshot = $this->calculator->handle($rental, $validated);

            if (! $persist) {
                return $snapshot;
            }

            $invoice = Invoice::create($snapshot);

            $payment = $invoice->payment()->create([
                'p_amount' => $snapshot['i_total'],
                'p_status' => 'UNPAID',
                'p_date' => null,
                'p_type' => null,
                'p_proof' => null,
                'p_reject_reason' => null,
            ]);

            AuditEvent::create([
                'actor_user_id' => $actor->getKey(),
                'entity_type' => 'invoices',
                'entity_id' => $invoice->getKey(),
                'action' => 'invoice_created',
                'old_values' => null,
                'new_values' => [
                    ...$snapshot,
                    'payment' => [
                        'p_id' => $payment->getKey(),
                        'p_amount' => $snapshot['i_total'],
                        'p_status' => 'UNPAID',
                    ],
                ],
            ]);

            return [
                'i_id' => $invoice->getKey(),
                ...$snapshot,
                'payment' => [
                    'p_id' => $payment->getKey(),
                    'p_amount' => $snapshot['i_total'],
                    'p_status' => 'UNPAID',
                    'p_date' => null,
                    'p_type' => null,
                ],
            ];
        }, 3);
    }
}