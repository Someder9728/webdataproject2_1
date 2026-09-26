<?php

namespace App\Actions\Meters;

use App\Models\AuditEvent;
use App\Models\Meter;
use App\Models\Rental;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RecordMeterReading
{
    public function handle(User $actor, Room $room, array $input): Meter
    {
        // อ่านสถานะล่าสุด ไม่เชื่อข้อมูลบทบาทจากหน้าจอ
        $actor = $actor->fresh();

        abort_unless(
            $actor &&
            $actor->is_active &&
            ! $actor->must_change_password &&
            $actor->u_role === 'admin',
            403
        );

        $validated = Validator::make($input, [
            'm_date' => ['required', 'date'],
            'm_water' => ['required', 'numeric', 'min:0'],
            'm_elec' => ['required', 'numeric', 'min:0'],
            'm_type' => ['required', 'string', 'in:' . implode(',', Meter::TYPES)],
        ])->validate();

        return DB::transaction(function () use ($actor, $room, $validated) {
            $room = Room::findOrFail($room->getKey());

            // m_type ที่ระบุมาต้องตรงกับข้อมูล Rental จริงของห้องนี้
            // ไม่ปล่อยให้ label ผิดจากวันที่จริง
            $isMoveInDate = Rental::where('rooms_r_id', $room->getKey())
                ->where('rt_movein', $validated['m_date'])
                ->exists();

            $isMoveOutDate = Rental::where('rooms_r_id', $room->getKey())
                ->where('rt_moveout', $validated['m_date'])
                ->exists();

            $isFirstOfMonth = (int) date('j', strtotime($validated['m_date'])) === 1;

            $consistent = match ($validated['m_type']) {
                'move_in' => $isMoveInDate,
                'move_out' => $isMoveOutDate,
                'monthly' => $isFirstOfMonth,
                default => false,
            };

            if (! $consistent) {
                throw ValidationException::withMessages([
                    'm_type' => match ($validated['m_type']) {
                        'move_in' => 'วันที่ระบุไม่ตรงกับวันเข้าพักของ Rental ห้องนี้',
                        'move_out' => 'วันที่ระบุไม่ตรงกับวันย้ายออกของ Rental ห้องนี้',
                        'monthly' => 'รอบบันทึกปกติ (monthly) ต้องเป็นวันที่ 1 ของเดือนเท่านั้น',
                        default => 'ประเภทมิเตอร์ไม่ถูกต้อง',
                    },
                ]);
            }

            // กันข้อมูลซ้ำระดับ Application ก่อนชน Unique Index จริง
            // (ห้อง + วันที่ + ประเภท) ที่ DB
            $duplicate = Meter::where('rooms_r_id', $room->getKey())
                ->where('m_date', $validated['m_date'])
                ->where('m_type', $validated['m_type'])
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'm_date' => 'มีรายการมิเตอร์ประเภทนี้ของห้องนี้ในวันที่เลือกอยู่แล้ว',
                ]);
            }

            $previous = Meter::query()
                ->where('rooms_r_id', $room->getKey())
                ->where('m_date', '<', $validated['m_date'])
                ->orderByDesc('m_date')
                ->orderByDesc('m_id')
                ->first();

            // เลขมิเตอร์ต้องไม่ย้อนกลับจากรอบก่อนหน้า ไม่ว่าจะเป็น type ไหน
            if ($previous) {
                if ((float) $validated['m_water'] < (float) $previous->m_water) {
                    throw ValidationException::withMessages([
                        'm_water' => 'เลขมิเตอร์น้ำต้องไม่น้อยกว่าค่ารอบก่อนหน้า ('
                            . $previous->m_water . ')',
                    ]);
                }

                if ((float) $validated['m_elec'] < (float) $previous->m_elec) {
                    throw ValidationException::withMessages([
                        'm_elec' => 'เลขมิเตอร์ไฟต้องไม่น้อยกว่าค่ารอบก่อนหน้า ('
                            . $previous->m_elec . ')',
                    ]);
                }
            }

            $meter = Meter::create([
                'm_date' => $validated['m_date'],
                'm_water' => $validated['m_water'],
                'm_elec' => $validated['m_elec'],
                'm_type' => $validated['m_type'],
                'rooms_r_id' => $room->getKey(),
            ]);

            AuditEvent::create([
                'actor_user_id' => $actor->getKey(),
                'entity_type' => 'meters',
                'entity_id' => $meter->getKey(),
                'action' => 'meter_reading_recorded',
                'new_values' => [
                    'm_date' => (string) $meter->m_date,
                    'm_water' => (string) $meter->m_water,
                    'm_elec' => (string) $meter->m_elec,
                    'm_type' => $meter->m_type,
                    'rooms_r_id' => $meter->rooms_r_id,
                ],
            ]);

            return $meter;
        });
    }
}
