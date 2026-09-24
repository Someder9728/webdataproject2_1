<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Accounts\CreateTenantAccount;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantAccountController extends Controller
{
    public function store(
        Request $request,
        Tenant $tenant,
        CreateTenantAccount $action
    ): JsonResponse {
        // ตรวจชนิดข้อมูลก่อนส่งให้ Action ทำ normalization
        $input = $request->validate([
            'u_username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'password_confirmation' => ['required', 'string'],
        ]);

        $user = $action->handle(
            $request->user(),
            $tenant,
            $input
        );

        return response()->json([
            'data' => [
                'u_id' => $user->getKey(),
                'u_username' => $user->u_username,
                'u_role' => $user->u_role,
                'tenants_t_id' => $user->tenants_t_id,
                'is_active' => (bool) $user->is_active,
                'must_change_password' =>
                    (bool) $user->must_change_password,
            ],
            'message' => 'สร้างบัญชีผู้เช่าสำเร็จ',
        ], 201);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        $account = \App\Models\User::withTrashed()
            ->where('tenants_t_id', $tenant->getKey())
            ->first();

        return response()->json([
            'data' => [
                'has_account' => $account !== null,
                'account' => $account ? [
                    'u_id' => $account->getKey(),
                    'u_username' => $account->u_username,
                    'u_role' => $account->u_role,
                    'is_active' => (bool) $account->is_active,
                    'must_change_password' =>
                        (bool) $account->must_change_password,
                    'is_deleted' => $account->trashed(),
                ] : null,
            ],
            'message' => 'อ่านสถานะบัญชีผู้เช่าสำเร็จ',
        ]);
    }
}