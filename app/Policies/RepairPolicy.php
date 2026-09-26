<?php

namespace App\Policies;

use App\Models\Repair;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RepairPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active
            && in_array($user->u_role, ['admin', 'tenant'], true);
    }

    public function view(User $user, Repair $repair): Response
    {
        if (! $user->is_active) {
            return Response::deny();
        }

        if ($user->u_role === 'admin') {
            return Response::allow();
        }

        if (
            $user->u_role === 'tenant' &&
            (
                (
                    $user->tenants_t_id !== null &&
                    (string) $user->tenants_t_id === (string) $repair->tenants_t_id
                ) ||
                (string) $user->getKey() === (string) $repair->reported_by_user_id
            )
        ) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        // Admin แจ้งได้เสมอ; Tenant แจ้งได้ก็ต่อเมื่อบัญชีผูกกับ
        // Tenant record จริง (เช็คซ้ำอีกชั้นใน CreateRepair action)
        return $user->is_active && (
            $user->u_role === 'admin' ||
            ($user->u_role === 'tenant' && $user->tenants_t_id !== null)
        );
    }

    public function updateStatus(User $user, Repair $repair): bool
    {
        // เปลี่ยนสถานะได้เฉพาะ Admin เท่านั้น ผู้แจ้งดูได้อย่างเดียว
        return $user->is_active && $user->u_role === 'admin';
    }
}
