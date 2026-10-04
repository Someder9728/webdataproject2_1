<?php

namespace App\Actions\Billing;

use App\Models\AuditEvent;
use App\Models\Invoice;
use App\Models\Rental;
use App\Models\User;
use App\Support\SqliteTransaction;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateInvoice
{
    private const SNAPSHOT_FIELDS = [
        'rentals_rt_id',
        'period_start',
        'period_end',
        'start_meter_id',
        'end_meter_id',
        'water_usage',
        'elec_usage',
        'water_rate',
        'elec_rate',
        'rent_rate',
        'i_date',
        'i_due',
        'i_rent',
        'i_water',
        'i_elec',
        'i_total',
    ];

    public function __construct(
        private CalculateInvoice $calculator,
        private SqliteTransaction $transactions
    ) {}

    public function handle(
        User $actor,
        Invoice $invoice,
        array $input
    ): Invoice {
        return $this->transactions->run(function () use (
            $actor,
            $invoice,
            $input
        ) {
            // อ่านใหม่ทุก retry รวมทั้งการเรียก Action โดยตรง
            $actor = User::find($actor->getKey());

            abort_unless($actor, 403);

            $invoice = Invoice::findOrFail($invoice->getKey());

            Gate::forUser($actor)->authorize('update', $invoice);

            $payment = $invoice->payment()->first();

            abort_unless(
                $payment && $payment->p_status === 'UNPAID',
                409,
                'แก้ไขได้เฉพาะบิลที่ยังไม่ได้ดำเนินการชำระ'
            );

            abort_if(
                $payment->events()->exists(),
                409,
                'บิลนี้มีประวัติการชำระแล้ว'
            );

            // ป้องกันข้อมูลเก่าที่มีรายละเอียดชำระแต่ไม่มี Event
            foreach ([
                'p_date',
                'p_type',
                'p_proof',
                'p_reject_reason',
            ] as $field) {
                abort_if(
                    $payment->{$field} !== null,
                    409,
                    'ข้อมูลการชำระไม่อยู่ในสถานะเริ่มต้น'
                );
            }

            $data = [];

            foreach ([
                'period_start',
                'period_end',
                'i_due',
                'reason',
            ] as $field) {
                if (array_key_exists($field, $input)) {
                    $data[$field] = is_string($input[$field])
                        ? trim($input[$field])
                        : $input[$field];
                }
            }

            if (! array_key_exists('period_start', $data)) {
                $data['period_start'] =
                    $invoice->period_start?->toDateString();
            }

            if (! array_key_exists('period_end', $data)) {
                $data['period_end'] =
                    $invoice->period_end?->toDateString();
            }

            if (! array_key_exists('i_due', $data)) {
                $data['i_due'] = $invoice->i_due?->toDateString();
            }

            abort_unless($invoice->i_date !== null, 409);

            $validated = Validator::make($data, [
                'period_start' => ['required', 'date_format:Y-m-d'],
                'period_end' => [
                    'required',
                    'date_format:Y-m-d',
                    'after:period_start',
                ],
                'i_due' => [
                    'required',
                    'date_format:Y-m-d',
                    'after_or_equal:'.$invoice->i_date->toDateString(),
                ],
                'reason' => ['required', 'string', 'max:2000'],
            ])->validate();

            $periodChanged =
                $validated['period_start']
                    !== $invoice->period_start?->toDateString()
                || $validated['period_end']
                    !== $invoice->period_end?->toDateString();

            $dueChanged =
                $validated['i_due'] !== $invoice->i_due?->toDateString();

            if (! $periodChanged && ! $dueChanged) {
                throw ValidationException::withMessages([
                    'invoice' => 'ไม่มีข้อมูลเปลี่ยนแปลง',
                ]);
            }

            $before = $this->snapshot($invoice);

            $oldPayment = [
                'p_id' => $payment->getKey(),
                'p_amount' => $payment->p_amount,
                'p_status' => $payment->p_status,
            ];

            // แก้เฉพาะวันครบกำหนด ไม่คำนวณยอดหรืออ่านมิเตอร์ใหม่
            if ($periodChanged) {
                $rental = Rental::findOrFail($invoice->rentals_rt_id);

                $calculated = $this->calculator->handle(
                    $rental,
                    $validated,
                    $invoice
                );

                $invoice->fill($calculated);
            }

            $invoice->i_due = $validated['i_due'];
            $invoice->save();

            $payment->p_amount = $invoice->i_total;
            $payment->save();

            AuditEvent::create([
                'actor_user_id' => $actor->getKey(),
                'entity_type' => 'invoices',
                'entity_id' => $invoice->getKey(),
                'action' => 'invoice_updated',
                'old_values' => [
                    ...$before,
                    'payment' => $oldPayment,
                ],
                'new_values' => [
                    ...$this->snapshot($invoice),
                    'payment' => [
                        'p_id' => $payment->getKey(),
                        'p_amount' => $payment->p_amount,
                        'p_status' => $payment->p_status,
                    ],
                ],
                'reason' => $validated['reason'],
            ]);

            return $invoice;
        });
    }

    private function snapshot(Invoice $invoice): array
    {
        $data = $invoice->only(self::SNAPSHOT_FIELDS);

        foreach ([
            'period_start',
            'period_end',
            'i_date',
            'i_due',
        ] as $field) {
            $data[$field] = $invoice->{$field}?->toDateString();
        }

        return $data;
    }
}