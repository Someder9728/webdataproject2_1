<?php

namespace App\Actions\Billing;

use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Rental;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CalculateInvoice
{
    /**
     * ใช้ภายใน Action ที่ตรวจสิทธิ์และเปิด transaction แล้ว
     * คืน snapshot สำหรับ Preview หรือสร้าง Invoice
     */
    public function handle(Rental $rental, array $input): array
    {
        $validated = Validator::make($input, [
            'period_start' => ['required', 'date_format:Y-m-d'],
            'period_end' => [
                'required',
                'date_format:Y-m-d',
                'after:period_start',
            ],
        ])->validate();

        $rental = Rental::findOrFail($rental->getKey());
        $contract = $rental->contract()->first();

        if (! $contract) {
            $this->fail('rental', 'ไม่พบสัญญาของการเช่านี้');
        }

        $start = CarbonImmutable::parse(
            $validated['period_start'],
            'Asia/Bangkok'
        )->startOfDay();

        $end = CarbonImmutable::parse(
            $validated['period_end'],
            'Asia/Bangkok'
        )->startOfDay();

        $today = CarbonImmutable::today('Asia/Bangkok');

        if ($end->greaterThan($today)) {
            $this->fail(
                'period_end',
                'ยังออกบิลไม่ได้ เพราะยังไม่ถึงวันสิ้นสุดช่วงคิดเงิน'
            );
        }

        if ($start->toDateString() < $rental->rt_movein->toDateString()) {
            $this->fail(
                'period_start',
                'ช่วงคิดเงินต้องไม่เริ่มก่อนวันเข้าพัก'
            );
        }

        if (
            $rental->rt_moveout !== null &&
            $end->toDateString() > $rental->rt_moveout->toDateString()
        ) {
            $this->fail(
                'period_end',
                'ช่วงคิดเงินต้องไม่เกินวันย้ายออก'
            );
        }

        // หนึ่ง Invoice คิดค่าเช่าภายในเดือนเดียว
        // ปลายช่วงเป็นวันที่ 1 เดือนถัดไปได้ เพราะไม่รวมวันปลายช่วง
        $monthBoundary = $start->startOfMonth()->addMonth();

        if ($end->greaterThan($monthBoundary)) {
            $this->fail(
                'period_end',
                'กรุณาแบ่งช่วงคิดเงินตามเดือนก่อนออกใบแจ้งหนี้'
            );
        }

        // เก็บการป้องกันบิลซ้ำแม้มีการ soft delete ข้อมูลเดิม
        $overlaps = Invoice::withTrashed()
            ->where('rentals_rt_id', $rental->getKey())
            ->whereDate('period_start', '<', $end->toDateString())
            ->whereDate('period_end', '>', $start->toDateString())
            ->exists();

        abort_if($overlaps, 409);

        $startMeter = Meter::query()
            ->where('rooms_r_id', $rental->rooms_r_id)
            ->where('m_date', $start->toDateString())
            ->first();

        $endMeter = Meter::query()
            ->where('rooms_r_id', $rental->rooms_r_id)
            ->where('m_date', $end->toDateString())
            ->first();

        if (! $startMeter) {
            $this->fail(
                'period_start',
                'ไม่พบมิเตอร์ของห้องที่ตรงวันเริ่มช่วงคิดเงิน'
            );
        }

        if (! $endMeter) {
            $this->fail(
                'period_end',
                'ไม่พบมิเตอร์ของห้องที่ตรงวันสิ้นสุดช่วงคิดเงิน'
            );
        }

        $waterRate = $this->decimal(
            config('dormitory.water_rate'),
            'water_rate'
        );

        $elecRate = $this->decimal(
            config('dormitory.elec_rate'),
            'elec_rate'
        );

        $rentRate = $this->decimal($contract->c_rent, 'c_rent');

        $startWater = $this->decimal($startMeter->m_water, 'start_water');
        $endWater = $this->decimal($endMeter->m_water, 'end_water');
        $startElec = $this->decimal($startMeter->m_elec, 'start_elec');
        $endElec = $this->decimal($endMeter->m_elec, 'end_elec');

        $waterUsage = $endWater->minus($startWater);
        $elecUsage = $endElec->minus($startElec);

        if (
            $waterUsage->isLessThan('0') ||
            $elecUsage->isLessThan('0')
        ) {
            $this->fail(
                'period_end',
                'เลขมิเตอร์ปลายช่วงลดลง ระบบไม่รองรับการเปลี่ยนหรือวนเลขมิเตอร์'
            );
        }

        $days = (int) $start->diffInDays($end);

        $rent = $rentRate
            ->multipliedBy($days)
            ->dividedBy($start->daysInMonth, 2, RoundingMode::HalfUp);

        $water = $waterUsage
            ->multipliedBy($waterRate)
            ->toScale(2, RoundingMode::HalfUp);

        $elec = $elecUsage
            ->multipliedBy($elecRate)
            ->toScale(2, RoundingMode::HalfUp);

        // รวมองค์ประกอบที่ปัดแล้ว
        $total = $rent->plus($water)->plus($elec);

        foreach ([
            'i_rent' => $rent,
            'i_water' => $water,
            'i_elec' => $elec,
            'i_total' => $total,
        ] as $field => $amount) {
            if ($amount->isGreaterThan('99999999.99')) {
                $this->fail(
                    $field,
                    'ยอดเงินเกินขนาดที่ฐานข้อมูลรองรับ'
                );
            }
        }

        return [
            'rentals_rt_id' => $rental->getKey(),
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'start_meter_id' => $startMeter->getKey(),
            'end_meter_id' => $endMeter->getKey(),
            'water_usage' => (string) $waterUsage->toScale(2),
            'elec_usage' => (string) $elecUsage->toScale(2),
            'water_rate' => (string) $waterRate,
            'elec_rate' => (string) $elecRate,
            'rent_rate' => (string) $rentRate,
            'i_date' => $today->toDateString(),
            'i_due' => $today->addDays(7)->toDateString(),
            'i_rent' => (string) $rent,
            'i_water' => (string) $water,
            'i_elec' => (string) $elec,
            'i_total' => (string) $total->toScale(2),
        ];
    }

    private function decimal(mixed $value, string $field): BigDecimal
    {
        if (
            ! is_scalar($value) ||
            ! preg_match(
                '/\A[0-9]{1,8}(?:\.[0-9]{1,2})?\z/',
                (string) $value
            )
        ) {
            $this->fail(
                $field,
                'ต้องกำหนดค่าตัวเลขไม่ติดลบ ไม่เกิน 8 หลักและทศนิยม 2 ตำแหน่ง'
            );
        }

        return BigDecimal::of((string) $value)->toScale(2);
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([
            $field => $message,
        ]);
    }
}