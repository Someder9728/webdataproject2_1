<?php

namespace App\Actions\Meters;

use App\Models\Meter;
use Illuminate\Validation\ValidationException;

class CalculateMeterUsage
{
    /**
     * คำนวณหน่วยที่ใช้ระหว่าง Meter ต้นช่วงกับปลายช่วงของ Rental หนึ่ง ๆ
     * ตรงกับ start_meter_id / end_meter_id ที่ผูกไว้กับ Invoice
     *
     * @return array{water_usage: float, elec_usage: float}
     */
    public function handle(Meter $start, Meter $end): array
    {
        if ($start->rooms_r_id !== $end->rooms_r_id) {
            throw ValidationException::withMessages([
                'end_meter_id' => 'มิเตอร์ต้นช่วงและปลายช่วงต้องเป็นห้องเดียวกัน',
            ]);
        }

        if ($end->m_date < $start->m_date) {
            throw ValidationException::withMessages([
                'end_meter_id' => 'มิเตอร์ปลายช่วงต้องมีวันที่ไม่ก่อนมิเตอร์ต้นช่วง',
            ]);
        }

        // เผื่อกรณีมีคนแก้ Meter ตรง ๆ ข้าม RecordMeterReading มา ต้องเช็คซ้ำ
        // ตรงนี้อีกชั้น ไม่พึ่งแค่ validation ตอนบันทึกอย่างเดียว
        if ((float) $end->m_water < (float) $start->m_water) {
            throw ValidationException::withMessages([
                'end_meter_id' => 'เลขมิเตอร์น้ำปลายช่วงน้อยกว่าต้นช่วง ข้อมูลผิดปกติ',
            ]);
        }

        if ((float) $end->m_elec < (float) $start->m_elec) {
            throw ValidationException::withMessages([
                'end_meter_id' => 'เลขมิเตอร์ไฟปลายช่วงน้อยกว่าต้นช่วง ข้อมูลผิดปกติ',
            ]);
        }

        return [
            'water_usage' => (float) $end->m_water - (float) $start->m_water,
            'elec_usage' => (float) $end->m_elec - (float) $start->m_elec,
        ];
    }
}
