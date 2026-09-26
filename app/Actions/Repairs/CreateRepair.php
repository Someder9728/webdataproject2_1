<?php

namespace App\Actions\Repairs;

use App\Models\AuditEvent;
use App\Models\Repair;
use App\Models\RepairHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateRepair
{
    public function handle(User $actor, array $input): Repair
    {
        // อ่านสถานะล่าสุด ไม่เชื่อข้อมูลบทบาทจากหน้าจอ
        $actor = $actor->fresh();

        abort_unless(
            $actor && $actor->is_active && ! $actor->must_change_password,
            403
        );

        $validated = Validator::make($input, [
            'rp_name' => ['required', 'string', 'max:255'],
            'rp_description' => ['nullable', 'string'],
            'rp_type' => ['required', 'string', 'in:' . implode(',', Repair::TYPES)],
            'rooms_r_id' => [
                'nullable',
                Rule::requiredIf(fn () => ($input['rp_type'] ?? null) === 'ROOM'),
                'exists:rooms,r_id',
            ],
            // เฉพาะ Admin เท่านั้นที่เลือก tenants_t_id เองได้ Tenant
            // ถูกบังคับเป็นของตัวเองเสมอไม่ว่าจะส่งค่านี้มาหรือไม่
            'tenants_t_id' => ['nullable', 'exists:tenants,t_id'],
        ])->validate();

        if ($validated['rp_type'] === 'COMMON' && ! empty($validated['rooms_r_id'] ?? null)) {
            throw ValidationException::withMessages([
                'rooms_r_id' => 'งานซ่อมประเภท COMMON ต้องไม่ระบุห้อง',
            ]);
        }

        $isAdmin = $actor->u_role === 'admin';

        // Tenant คือ "เจ้าของห้อง/สัญญา" ส่วน reported_by_user_id คือ
        // "คนที่กดแจ้ง" สองอย่างนี้ไม่ใช่ข้อมูลเดียวกัน — Tenant แจ้งเอง
        // ผูก tenants_t_id เป็นของตัวเองเสมอ, Admin เลือกเองได้/เว้นว่างได้
        $tenantsId = $isAdmin
            ? ($validated['tenants_t_id'] ?? null)
            : $actor->tenants_t_id;

        if (! $isAdmin && ! $tenantsId) {
            // บัญชี tenant ที่ไม่ได้ผูกกับ Tenant record ใด ๆ แจ้งซ่อมไม่ได้
            abort(403);
        }

        return DB::transaction(function () use ($actor, $validated, $tenantsId) {
            $repair = Repair::create([
                'rp_name' => $validated['rp_name'],
                'rp_description' => $validated['rp_description'] ?? null,
                'rp_status' => 'REPORTED',
                'rp_type' => $validated['rp_type'],
                'reported_by_user_id' => $actor->getKey(),
                'tenants_t_id' => $tenantsId,
                'rooms_r_id' => $validated['rooms_r_id'] ?? null,
            ]);

            // สถานะปัจจุบันต้องมีประวัติรองรับเสมอ ตั้งแต่รายการแรกที่สร้าง
            RepairHistory::create([
                'rph_status' => 'REPORTED',
                'rph_name' => $repair->rp_name,
                'rph_description' => $repair->rp_description,
                'repairs_rp_id' => $repair->getKey(),
                'changed_by_user_id' => $actor->getKey(),
            ]);

            AuditEvent::create([
                'actor_user_id' => $actor->getKey(),
                'entity_type' => 'repairs',
                'entity_id' => $repair->getKey(),
                'action' => 'repair_created',
                'new_values' => [
                    'rp_type' => $repair->rp_type,
                    'rp_status' => $repair->rp_status,
                    'reported_by_user_id' => $repair->reported_by_user_id,
                    'tenants_t_id' => $repair->tenants_t_id,
                    'rooms_r_id' => $repair->rooms_r_id,
                ],
            ]);

            return $repair;
        });
    }
}
