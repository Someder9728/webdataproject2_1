<?php

namespace App\Services;

use App\Models\Rental;

class RentalAvailabilityService
{
    /** Call inside SqliteTransaction so a competing write retries the complete check. */
    public function assertAvailable(int $roomId, int $tenantId, string $start, ?string $end = null, ?int $except = null): void
    {
        $rows = Rental::withTrashed()->where('rt_status', '<>', 'CANCELLED')
            ->when($except !== null, fn ($q) => $q->where('rt_id', '<>', $except))
            ->where(fn ($q) => $q->where('rooms_r_id', $roomId)->orWhere('tenants_t_id', $tenantId))->get();
        foreach ($rows as $row) {
            $otherStart = ($row->rt_movein ?? $row->scheduled_movein_at)?->toDateString();
            $otherEnd = ($row->rt_status === 'ENDED' ? $row->rt_moveout : $row->scheduled_moveout_at)?->toDateString();
            abort_if($otherStart === null || (($end === null || $otherStart < $end) && ($otherEnd === null || $start < $otherEnd)), 409,
                'ช่วงการเช่าของห้องหรือผู้เช่าทับซ้อนกับรายการเดิม กรุณาตรวจสอบกำหนดเข้าและย้ายออก');
        }
    }
}
