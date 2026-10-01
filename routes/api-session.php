<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\RentalController;
use App\Http\Controllers\Api\V1\RoomController;
use App\Http\Controllers\Api\V1\RentalContractController;
use App\Http\Controllers\Api\V1\AuditEventController;
use App\Http\Controllers\Api\V1\MeterController;

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

        

            Route::get('/rentals', [RentalController::class, 'index'])->name('rentals.index');
            Route::get('/rentals/{rental}', [RentalController::class, 'show']);
            Route::post('/rentals', [RentalController::class, 'store']);   
            Route::get('/rooms', [RoomController::class, 'index']);
            Route::get('/rooms/{room}/meters', [MeterController::class, 'index'])->middleware('role:admin');
            Route::post('/rooms/{room}/meters', [MeterController::class, 'store'])->middleware('role:admin');
            // Route สำหรับก้อนถัดไปตามแผนงาน:
             Route::get('/audit-events', [AuditEventController::class, 'index']);
            Route::get('/rentals/{rental}/contract', [RentalController::class, 'showContract']);
            Route::patch('/rentals/{rental}/contract', [RentalContractController::class, 'update']);
            // Route::post('/rentals/{rental}/move-out', [RentalController::class, 'moveOut']);
        
    });

