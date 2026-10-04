<?php

namespace App\Actions\Rentals;

use App\Actions\Billing\IssueInvoice;
use App\Models\AuditEvent;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Rental;
use App\Models\Room;
use App\Models\User;
use App\Support\SqliteTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MoveOutRental
{
    public function __construct(
        private IssueInvoice $billing,
        private SqliteTransaction $transactions
    ) {}

    public function handle(
        User $actor,
        Rental $rental,
        array $input
    ): array {
        return $this->transactions->run(function () use (
            $actor,
            $rental,
            $input
        ) {
            $actor = User::find($actor->getKey());

            abort_unless($actor, 403);

            $rental = Rental::findOrFail($rental->getKey());

            Gate::forUser($actor)->authorize('moveOut', $rental);

            abort_unless(
                $rental->rt_status === 'ACTIVE'
                    && $rental->rt_moveout === null,
                409,
                'การเช่านี้ไม่ได้อยู่ในสถานะพร้อมย้ายออก'
            );

            $data = [];

            foreach (['rt_moveout', 'reason'] as $field) {
                if (array_key_exists($field, $input)) {
                    $data[$field] = is_string($input[$field])
                        ? trim($input[$field])
                        : $input[$field];
                }
            }

            $validated = Validator::make($data, [
                'rt_moveout' => [
                    'required',
                    'date_format:Y-m-d',
                    'after_or_equal:'.$rental->rt_movein->toDateString(),
                    'before_or_equal:'.now('Asia/Bangkok')->toDateString(),
                ],
                'reason' => ['required', 'string', 'max:2000'],
            ])->validate();

            $moveIn = $rental->rt_movein->toDateString();
            $moveOut = $validated['rt_moveout'];

            $room = Room::findOrFail($rental->rooms_r_id);
            $contract = $rental->contract()->firstOrFail();

            abort_unless(
                $room->r_status === 'OCCUPIED',
                409,
                'สถานะห้องไม่ตรงกับการเช่าที่กำลังย้ายออก'
            );

            abort_unless(
                in_array($contract->c_status, ['ACTIVE', 'EXPIRED'], true),
                409,
                'สถานะสัญญาไม่รองรับการย้ายออก'
            );

            // ไม่เปลี่ยนห้องเป็น VACANT หากมีการเช่าอื่นที่ยัง ACTIVE
            $otherActiveRental = Rental::query()
                ->where('rooms_r_id', $room->getKey())
                ->where('rt_id', '<>', $rental->getKey())
                ->where('rt_status', 'ACTIVE')
                ->exists();

            abort_if(
                $otherActiveRental,
                409,
                'ห้องนี้มีการเช่า ACTIVE อื่นอยู่'
            );

            // ตรวจการเช่าอื่นที่ทับช่วงของรายการนี้ รวมข้อมูล soft delete
            $overlappingRental = Rental::withTrashed()
                ->where('rooms_r_id', $room->getKey())
                ->where('rt_id', '<>', $rental->getKey())
                ->whereDate('rt_movein', '<', $moveOut)
                ->where(function ($query) use ($moveIn) {
                    $query->whereNull('rt_moveout')
                        ->orWhereDate('rt_moveout', '>', $moveIn);
                })
                ->exists();

            abort_if(
                $overlappingRental,
                409,
                'พบช่วงการเช่าอื่นทับซ้อน กรุณาตรวจสอบข้อมูลก่อน'
            );

            $closingMeter = Meter::query()
                ->where('rooms_r_id', $room->getKey())
                ->whereDate('m_date', $moveOut)
                ->first();

            if (! $closingMeter) {
                throw ValidationException::withMessages([
                    'rt_moveout' => 'ต้องบันทึกมิเตอร์ตรงวันย้ายออกก่อน',
                ]);
            }

            $existingInvoices = Invoice::withTrashed()
                ->where('rentals_rt_id', $rental->getKey())
                ->orderBy('period_start')
                ->orderBy('i_id')
                ->get();

            // ช่วงว่างทั้งหมด ไม่ใช่แค่ช่วงหลังบิลล่าสุด
            $gaps = [];
            $cursor = $moveIn;

            foreach ($existingInvoices as $invoice) {
                // ไม่ถือว่าบิลที่ลบแล้วเป็นช่วงเรียกเก็บสำเร็จ
                // และไม่สร้างทับ เพราะกฎ Billing กันซ้ำรวม soft delete
                abort_if(
                    $invoice->trashed(),
                    409,
                    'พบใบแจ้งหนี้ที่ถูกลบ กรุณาตรวจสอบก่อนย้ายออก'
                );

                $start = $invoice->period_start?->toDateString();
                $end = $invoice->period_end?->toDateString();

                abort_if(
                    $start === null
                        || $end === null
                        || $start >= $end
                        || $start < $moveIn
                        || $end > $moveOut,
                    409,
                    'ช่วงบิลเดิมไม่สอดคล้องกับวันเข้าและวันย้ายออก'
                );

                abort_if(
                    $start < $cursor,
                    409,
                    'พบใบแจ้งหนี้เดิมมีช่วงซ้อนกัน'
                );

                abort_unless(
                    $invoice->payment()->exists(),
                    409,
                    'ใบแจ้งหนี้เดิมไม่มีข้อมูล Payment ที่ใช้งานได้'
                );

                if ($cursor < $start) {
                    $gaps[] = [$cursor, $start];
                }

                $cursor = $end;
            }

            if ($cursor < $moveOut) {
                $gaps[] = [$cursor, $moveOut];
            }

            $createdInvoices = [];

            foreach ($gaps as [$gapStart, $gapEnd]) {
                $start = CarbonImmutable::parse($gapStart, 'Asia/Bangkok');
                $limit = CarbonImmutable::parse($gapEnd, 'Asia/Bangkok');

                while ($start->lessThan($limit)) {
                    $monthBoundary = $start->startOfMonth()->addMonth();

                    $end = $monthBoundary->lessThan($limit)
                        ? $monthBoundary
                        : $limit;

                    // ใช้ Calculator/อัตรา/กฎมิเตอร์เดิมของการออกบิล
                    // หากช่วงใดล้มเหลว transaction ชั้นนอก rollback ทั้งหมด
                    $createdInvoices[] = $this->billing->handle(
                        $actor,
                        [
                            'rentals_rt_id' => $rental->getKey(),
                            'period_start' => $start->toDateString(),
                            'period_end' => $end->toDateString(),
                        ],
                        persist: true
                    );

                    $start = $end;
                }
            }

            $oldRental = [
                'rt_status' => $rental->rt_status,
                'rt_moveout' => null,
            ];

            $oldContract = [
                'c_status' => $contract->c_status,
                'c_end' => $contract->c_end?->toDateString(),
            ];

            $oldRoom = ['r_status' => $room->r_status];

            $rental->rt_moveout = $moveOut;
            $rental->rt_status = 'ENDED';
            $rental->save();

            $contract->c_status = 'ENDED';
            $contract->save();

            $room->r_status = 'VACANT';
            $room->save();

            $newInvoiceIds = array_column($createdInvoices, 'i_id');

            AuditEvent::create([
                'actor_user_id' => $actor->getKey(),
                'entity_type' => 'rentals',
                'entity_id' => $rental->getKey(),
                'action' => 'rental_moved_out',
                'old_values' => $oldRental,
                'new_values' => [
                    'rt_status' => 'ENDED',
                    'rt_moveout' => $moveOut,
                    'closing_meter_id' => $closingMeter->getKey(),
                    'created_invoice_ids' => $newInvoiceIds,
                ],
                'reason' => $validated['reason'],
            ]);

            AuditEvent::create([
                'actor_user_id' => $actor->getKey(),
                'entity_type' => 'contracts',
                'entity_id' => $contract->getKey(),
                'action' => 'contract_ended',
                'old_values' => $oldContract,
                'new_values' => [
                    'c_status' => 'ENDED',
                    'c_end' => $contract->c_end?->toDateString(),
                ],
                'reason' => $validated['reason'],
            ]);

            AuditEvent::create([
                'actor_user_id' => $actor->getKey(),
                'entity_type' => 'rooms',
                'entity_id' => $room->getKey(),
                'action' => 'room_updated',
                'old_values' => $oldRoom,
                'new_values' => ['r_status' => 'VACANT'],
                'reason' => $validated['reason'],
            ]);

            return [
                'rental' => $rental->load(['tenant', 'room', 'contract']),
                'created_invoices' => $createdInvoices,
            ];
        });
    }
}