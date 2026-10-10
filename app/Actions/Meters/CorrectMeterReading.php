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

class CorrectMeterReading
{
    public function __construct(private SqliteTransaction $transactions) {}

    /** @param array<string, mixed> $input */
    public function handle(User $actor, Room $room, Meter $meter, array $input, bool $delete = false): Meter
    {
        return $this->transactions->run(function () use ($actor, $room, $meter, $input, $delete) {
            $actor = User::whereKey($actor->getKey())->first();
            abort_unless($actor && $actor->is_active && ! $actor->must_change_password && $actor->u_role === 'admin', 403);
            $room = Room::whereKey($room->getKey())->firstOrFail();
            $meter = Meter::whereKey($meter->getKey())->firstOrFail();
            abort_unless((int) $meter->rooms_r_id === (int) $room->getKey(), 404);
            $input['reason'] = is_string($input['reason'] ?? null) ? trim($input['reason']) : ($input['reason'] ?? null);
            $rules = [
                'reason' => ['required', 'string', 'max:2000'],
                'confirmed' => ['required', 'accepted'],
                'expected_event_id' => ['present', 'nullable', 'integer', 'min:1'],
            ];
            if (! $delete) {
                $readingRules = ['required', 'numeric', 'regex:/\A[0-9]{1,8}(?:\.[0-9]{1,2})?\z/'];
                $rules += ['m_date' => ['required', 'date_format:Y-m-d'], 'm_water' => $readingRules, 'm_elec' => $readingRules];
            }
            $data = Validator::make($input, $rules)->validate();
            $latest = MeterMutationRules::latestEventId($meter);
            abort_unless(($latest === null && $data['expected_event_id'] === null)
                || ($latest !== null && $data['expected_event_id'] !== null && (string) $latest === (string) $data['expected_event_id']), 409, 'รายการมิเตอร์เปลี่ยนแล้ว กรุณาโหลดข้อมูลใหม่');
            abort_if(MeterMutationRules::usedByInvoice($meter), 409, 'มิเตอร์นี้ถูกใช้ในใบแจ้งหนี้แล้ว จึงแก้ไขหรือลบไม่ได้');
            $before = $this->snapshot($meter);
            $boundary = MeterMutationRules::rentalBoundary($meter);
            if ($delete) {
                abort_if($boundary, 409, 'มิเตอร์นี้เป็นวันเข้าหรือย้ายออกของการเช่า จึงลบไม่ได้');
                // Keep cancelled readings visible in history, excluded from billing.
                $meter->delete();
            } else {
                abort_if($boundary && $data['m_date'] !== $meter->m_date->toDateString(), 409, 'มิเตอร์วันเข้า/ย้ายออกแก้เลขได้ก่อนออกบิล แต่เปลี่ยนวันที่ไม่ได้');
                abort_if(Meter::withTrashed()->where('rooms_r_id', $room->getKey())->where('m_id', '<>', $meter->getKey())
                    ->whereDate('m_date', $data['m_date'])->exists(), 409, 'ห้องนี้มีมิเตอร์ในวันที่เลือกแล้ว');
                $previous = Meter::where('rooms_r_id', $room->getKey())->where('m_id', '<>', $meter->getKey())->whereDate('m_date', '<', $data['m_date'])->orderByDesc('m_date')->first();
                $next = Meter::where('rooms_r_id', $room->getKey())->where('m_id', '<>', $meter->getKey())->whereDate('m_date', '>', $data['m_date'])->orderBy('m_date')->first();
                foreach (['m_water', 'm_elec'] as $field) {
                    $value = $this->minorUnits($data[$field]);
                    if (($previous && $value < $this->minorUnits($previous->{$field})) || ($next && $value > $this->minorUnits($next->{$field}))) {
                        throw ValidationException::withMessages([$field => 'เลขมิเตอร์ต้องไม่น้อยกว่ารายการก่อนหน้าและไม่มากกว่ารายการถัดไป']);
                    }
                }
                $meter->fill(array_intersect_key($data, array_flip(['m_date', 'm_water', 'm_elec'])));
                if (! $meter->isDirty()) {
                    throw ValidationException::withMessages(['meter' => 'ไม่มีข้อมูลเปลี่ยนแปลง']);
                }
                $meter->save();
            }
            AuditEvent::create([
                'actor_user_id' => $actor->getKey(), 'entity_type' => 'meters', 'entity_id' => $meter->getKey(),
                'action' => $delete ? 'meter_reading_cancelled' : 'meter_reading_corrected',
                'old_values' => $before, 'new_values' => $delete ? null : $this->snapshot($meter), 'reason' => $data['reason'],
            ]);

            return $meter;
        });
    }

    /** @return array<string, mixed> */
    private function snapshot(Meter $meter): array
    {
        return ['rooms_r_id' => $meter->rooms_r_id, 'm_date' => $meter->m_date->toDateString(), 'm_water' => $meter->m_water, 'm_elec' => $meter->m_elec];
    }

    private function minorUnits(string|int|float $value): int
    {
        [$whole, $fraction] = array_pad(explode('.', (string) $value, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
