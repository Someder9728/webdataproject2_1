<?php

namespace App\Actions\Meters;

use App\Models\Meter;
use Brick\Math\BigDecimal;
use Illuminate\Validation\ValidationException;

class CalculateMeterUsage
{
    /**
     * Exact decimal strings, matching the billing snapshot precision.
     *
     * @return array{water_usage: string, elec_usage: string}
     */
    public function handle(Meter $start, Meter $end): array
    {
        if ((int) $start->rooms_r_id !== (int) $end->rooms_r_id || $end->m_date < $start->m_date) {
            throw ValidationException::withMessages(['end_meter_id' => 'ต้องเลือกมิเตอร์ห้องเดียวกันและวันที่ปลายช่วงไม่ก่อนต้นช่วง']);
        }
        $result = [];
        foreach (['m_water' => 'water_usage', 'm_elec' => 'elec_usage'] as $field => $key) {
            $usage = BigDecimal::of((string) $end->$field)->minus((string) $start->$field);
            if ($usage->isLessThan('0')) {
                throw ValidationException::withMessages(['end_meter_id' => 'เลขมิเตอร์ปลายช่วงต้องไม่น้อยกว่าต้นช่วง']);
            }
            $result[$key] = (string) $usage->toScale(2);
        }

        return $result;
    }
}
