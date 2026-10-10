<?php

namespace App\Actions\Billing;

use App\Models\Rental;
use Carbon\CarbonImmutable;

class NextBillingPeriod
{
    /** @return array{period_start: string, period_end: string} */
    public function handle(Rental $rental): array
    {
        $cursor = $rental->rt_movein->toDateString();
        $limit = $rental->rt_moveout?->toDateString();
        $gapEnd = null;
        foreach ($rental->invoices()->withTrashed()->orderBy('period_start')->get() as $invoice) {
            abort_if($invoice->trashed(), 409, 'พบใบแจ้งหนี้ที่ถูกลบ กรุณาตรวจสอบก่อนออกบิล');
            $start = $invoice->period_start?->toDateString();
            $end = $invoice->period_end?->toDateString();
            abort_if($start === null || $end === null || $start < $cursor || $start >= $end || ($limit !== null && $end > $limit), 409);
            if ($start > $cursor) {
                $gapEnd = $start;
                break;
            }
            $cursor = $end;
        }
        abort_if($limit !== null && $cursor >= $limit, 409, 'ออกบิลครบถึงวันย้ายออกแล้ว');
        $end = CarbonImmutable::parse($cursor, 'Asia/Bangkok')->startOfMonth()->addMonth()->toDateString();
        foreach ([$gapEnd, $limit] as $boundary) {
            if ($boundary !== null && $boundary < $end) {
                $end = $boundary;
            }
        }

        return ['period_start' => $cursor, 'period_end' => $end];
    }
}
