<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\TenantAccountController;
use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\RoomController;
use App\Http\Controllers\Api\V1\RentalController;

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

        Route::post('/tenants', [TenantController::class, 'store'])
            ->name('tenants.store');

        Route::patch('/tenants/{tenant}', [TenantController::class, 'update'])
            ->whereNumber('tenant')
            ->name('tenants.update');



        Route::post(
            '/tenants/{tenant}/account',
            [TenantAccountController::class, 'store']
        )
            ->whereNumber('tenant')
            ->middleware('role:admin')
            ->name('tenants.account.store');

        Route::post(
            '/accounts/{account}/reset-password',
            [AccountController::class, 'resetPassword']
        )
            ->whereNumber('account')
            ->middleware('role:admin')
            ->name('accounts.reset-password');

        Route::post(
            '/accounts/{account}/suspend',
            [AccountController::class, 'suspend']
        )
            ->whereNumber('account')
            ->middleware('role:admin')
            ->name('accounts.suspend');


        Route::get('/rooms', [RoomController::class, 'index'])
            ->name('rooms.index');

        Route::get('/rooms/{room}', [RoomController::class, 'show'])
            ->whereNumber('room')
            ->name('rooms.show');

        Route::post('/rooms', [RoomController::class, 'store'])
            ->name('rooms.store');

        Route::patch('/rooms/{room}', [RoomController::class, 'update'])
            ->whereNumber('room')
            ->name('rooms.update');

        Route::get('/tenants/{tenant}/account', [
            TenantAccountController::class, 'show',
        ])
            ->whereNumber('tenant')
            ->middleware('role:admin')
            ->name('tenants.account.show');

        Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy'])
            ->whereNumber('tenant')
            ->middleware('role:admin')
            ->name('tenants.destroy');

        Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])
            ->whereNumber('room')
            ->middleware('role:admin')
            ->name('rooms.destroy');        

        Route::get('/rentals', [RentalController::class, 'index'])->name('rentals.index');


        Route::get('/rentals/{rental}', [RentalController::class, 'show'])
            ->whereNumber('rental')
            ->name('rentals.show');

        Route::get('/rentals/{rental}/contract', [
            RentalController::class,
            'showContract',
        ])
            ->whereNumber('rental')
            ->name('rentals.contract.show');

        Route::post('/rentals', [RentalController::class, 'store'])
            ->middleware('role:admin')
            ->name('rentals.store');
        

    });

