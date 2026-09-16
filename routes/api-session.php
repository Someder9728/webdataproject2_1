<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\TenantController;


Route::prefix('api/v1')
    ->name('api.v1.')
    ->middleware(['auth', 'password.changed'])
    ->group(function () {
        Route::get('/me', function (Request $request) {
            $user = $request->user();

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
                'message' => 'อ่านข้อมูลบัญชีสำเร็จ',
            ]);
        })->name('me');

        Route::get('/tenants', [TenantController::class, 'index'])
            ->name('tenants.index');

        Route::get('/tenants/{tenant}', [TenantController::class, 'show'])
            ->whereNumber('tenant')
            ->name('tenants.show');
    });

