<?php

namespace App\Actions\Repairs;

use App\Models\AuditEvent;
use App\Models\Repair;
use App\Models\RepairHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateRepairStatus
{
    /**
     * ลำดับที่ไปต่อได้ของแต่ละสถานะ ห้ามข้ามขั้นและห้ามย้อนกลับ
     */
    private const NEXT_STATUS = [
        'REPORTED' => 'IN_PROGRESS',
        'IN_PROGRESS' => 'COMPLETED',
    ];

    public function handle(User $actor, Repair $repair, string $newStatus): Repair
    {
        $actor = $actor->fresh();

        // เฉพาะ Admin เท่านั้นที่เปลี่ยนสถานะงานซ่อมได้ ผู้แจ้ง (Tenant)
        // ดูได้อย่างเดียว
        abort_unless(
            $actor &&
            $actor->is_active &&
            ! $actor->must_change_password &&
            $actor->u_role === 'admin',
            403
        );

        return DB::transaction(function () use ($actor, $repair, $newStatus) {
            $repair = Repair::lockForUpdate()->findOrFail($repair->getKey());

            $expectedNext = self::NEXT_STATUS[$repair->rp_status] ?? null;

            if ($expectedNext === null) {
                throw ValidationException::withMessages([
                    'rp_status' => "งานซ่อมสถานะ {$repair->rp_status} เปลี่ยนสถานะต่อไม่ได้แล้ว",
                ]);
            }

            if ($newStatus !== $expectedNext) {
                throw ValidationException::withMessages([
                    'rp_status' => "เปลี่ยนสถานะได้แค่ {$repair->rp_status} → {$expectedNext} เท่านั้น "
                        . '(ห้ามข้ามขั้นหรือย้อนกลับ)',
                ]);
            }

            $oldStatus = $repair->rp_status;

            $repair->rp_status = $newStatus;
            $repair->save();

            RepairHistory::create([
                'rph_status' => $newStatus,
                'rph_name' => $repair->rp_name,
                'rph_description' => $repair->rp_description,
                'repairs_rp_id' => $repair->getKey(),
                'changed_by_user_id' => $actor->getKey(),
            ]);

            AuditEvent::create([
                'actor_user_id' => $actor->getKey(),
                'entity_type' => 'repairs',
                'entity_id' => $repair->getKey(),
                'action' => 'repair_status_changed',
                'old_values' => ['rp_status' => $oldStatus],
                'new_values' => ['rp_status' => $newStatus],
            ]);

            return $repair;
        });
    }
}
