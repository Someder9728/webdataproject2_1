<?php

namespace App\Actions\Meters;

use App\Models\AuditEvent;
use App\Models\Meter;
use App\Models\Room;
use App\Models\User;
use App\Support\MeterMutationRules;
use App\Support\SqliteTransaction;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RecordMeterReading
{
    public function __construct(
        private SqliteTransaction $transactions
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $actor, Room $room, array $input): Meter
    {
        return $this->transactions->run(function () use ($actor, $room, $input) {
            $actor = User::whereKey($actor->getKey())->first();

            abort_unless(
                $actor &&
                $actor->is_active &&
                ! $actor->must_change_password &&
                $actor->u_role === 'admin',
                403
            );

            $room = Room::whereKey($room->getKey())->firstOrFail();

            $readingRules = [
                'required',
                'numeric',
                'regex:/\A[0-9]{1,8}(?:\.[0-9]{1,2})?\z/',
            ];

            $validated = Validator::make($input, [
                'm_date' => ['required', 'date_format:Y-m-d'],
                'm_water' => $readingRules,
                'm_elec' => $readingRules,
            ])->validate();

            $date = $validated['m_date'];

            // Reuse a cancelled room/date slot; previous values remain in audit history.
            $existing = Meter::withTrashed()
                ->where('rooms_r_id', $room->getKey())
                ->whereDate('m_date', $date)
                ->first();

            abort_if(
                $existing && ! $existing->trashed(),
                409,
                'ห้องนี้มีรายการมิเตอร์ในวันที่เลือกแล้ว'
            );

            if ($existing && $existing->trashed()) {
                $lastAction = AuditEvent::where('entity_type', 'meters')->where('entity_id', $existing->getKey())
                    ->orderByDesc('ae_id')->value('action');
                abort_if($lastAction !== 'meter_reading_cancelled' || MeterMutationRules::usedByInvoice($existing)
                    || MeterMutationRules::rentalBoundary($existing), 409, 'รายการเดิมถูกล็อก ไม่สามารถบันทึกทดแทนได้');
            }

            $previous = Meter::query()
                ->where('rooms_r_id', $room->getKey())
                ->whereDate('m_date', '<', $date)
                ->orderByDesc('m_date')
                ->orderByDesc('m_id')
                ->first();

            $next = Meter::query()
                ->where('rooms_r_id', $room->getKey())
                ->whereDate('m_date', '>', $date)
                ->orderBy('m_date')
                ->orderBy('m_id')
                ->first();

            $errors = [];

            foreach (['m_water' => 'น้ำ', 'm_elec' => 'ไฟฟ้า'] as $field => $label) {
                $value = $this->toMinorUnits($validated[$field]);

                if (
                    $previous &&
                    $value < $this->toMinorUnits($previous->{$field})
                ) {
                    $errors[$field] =
                        "เลขมิเตอร์{$label}ต้องไม่น้อยกว่ารายการก่อนหน้า";
                }

                if (
                    $next &&
                    $value > $this->toMinorUnits($next->{$field})
                ) {
                    $errors[$field] =
                        "เลขมิเตอร์{$label}ต้องไม่มากกว่ารายการถัดไป";
                }
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            $before = $existing ? ['rooms_r_id' => $existing->rooms_r_id, 'm_date' => $existing->m_date->toDateString(), 'm_water' => $existing->m_water, 'm_elec' => $existing->m_elec, 'status' => 'CANCELLED'] : null;
            $meter = $existing ?? new Meter;
            $meter->fill([
                'rooms_r_id' => $room->getKey(),
                'm_date' => $date,
                'm_water' => $validated['m_water'],
                'm_elec' => $validated['m_elec'],
            ]);

            if ($meter->trashed()) {
                $meter->restore();
            } else {
                $meter->save();
            }

            AuditEvent::create([
                'actor_user_id' => $actor->getKey(),
                'entity_type' => 'meters',
                'entity_id' => $meter->getKey(),
                'action' => $existing ? 'meter_reading_replaced' : 'meter_reading_recorded',
                'old_values' => $before,
                'new_values' => [
                    'rooms_r_id' => $room->getKey(),
                    'm_date' => $date,
                    'm_water' => $meter->m_water,
                    'm_elec' => $meter->m_elec,
                ],
            ]);

            return $meter;
        });
    }

    private function toMinorUnits(string|int|float $value): int
    {
        [$whole, $fraction] = array_pad(
            explode('.', (string) $value, 2),
            2,
            ''
        );

        return ((int) $whole * 100)
            + (int) str_pad($fraction, 2, '0');
    }
}
