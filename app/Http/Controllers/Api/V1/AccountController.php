<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Accounts\ResetAccountPassword;
use App\Actions\Accounts\SuspendAccount;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function resetPassword(
        Request $request,
        User $account,
        ResetAccountPassword $action
    ): JsonResponse {
        $input = $request->validate([
            'password' => ['required', 'string'],
            'password_confirmation' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $action->handle($request->user(), $account, $input);

        return $this->accountResponse(
            $account->fresh(),
            'รีเซ็ตรหัสผ่านสำเร็จ'
        );
    }

    public function suspend(
        Request $request,
        User $account,
        SuspendAccount $action
    ): JsonResponse {
        $input = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $action->handle(
            $request->user(),
            $account,
            $input['reason'] ?? null
        );

        return $this->accountResponse(
            $account->fresh(),
            'ระงับบัญชีสำเร็จ'
        );
    }

    private function accountResponse(
        User $account,
        string $message
    ): JsonResponse {
        return response()->json([
            'data' => [
                'u_id' => $account->getKey(),
                'u_username' => $account->u_username,
                'u_role' => $account->u_role,
                'tenants_t_id' => $account->tenants_t_id,
                'is_active' => (bool) $account->is_active,
                'must_change_password' =>
                    (bool) $account->must_change_password,
            ],
            'message' => $message,
        ]);
    }
}