<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\MeterController;
use App\Http\Controllers\Api\V1\PaymentHistoryController;
use App\Http\Controllers\Api\V1\PaymentProofController;
use App\Http\Controllers\Api\V1\PaymentReviewController;
use App\Http\Controllers\Api\V1\RentalController;
use App\Http\Controllers\Api\V1\RentalHistoryController;
use App\Http\Controllers\Api\V1\RepairController;
use App\Http\Controllers\Api\V1\RoomController;
use App\Http\Controllers\Api\V1\TenantAccountController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\WalkInPaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')
    ->name('api.v1.')
    ->middleware(['auth', 'password.changed'])
    ->group(function () {
        Route::patch('/rooms/{room}/meters/{meter}', [MeterController::class, 'update'])->whereNumber('room')->whereNumber('meter')->middleware('role:admin')->name('rooms.meters.update');
        Route::delete('/rooms/{room}/meters/{meter}', [MeterController::class, 'destroy'])->whereNumber('room')->whereNumber('meter')->middleware('role:admin')->name('rooms.meters.destroy');
        Route::get('/rentals/{rental}/history', [RentalHistoryController::class, 'index'])->whereNumber('rental')->middleware('role:admin')->name('rentals.history');
        Route::get('/dashboard', [DashboardController::class, 'show'])->middleware('role:admin')->name('dashboard');
        Route::patch('/rentals/{rental}/contract', [RentalController::class, 'updateContract'])->whereNumber('rental')->middleware('role:admin')->name('rentals.contract.update');
        Route::get('/repairs', [RepairController::class, 'index'])->name('repairs.index');
        Route::post('/repairs', [RepairController::class, 'store'])->name('repairs.store');
        Route::get('/repairs/{repair}', [RepairController::class, 'show'])->whereNumber('repair')->name('repairs.show');
        Route::patch('/repairs/{repair}/status', [RepairController::class, 'update'])->whereNumber('repair')->middleware('role:admin')->name('repairs.status');
        Route::get('/rooms/{room}/meter-usage', [MeterController::class, 'usage'])->whereNumber('room')->middleware('role:admin')->name('rooms.meter-usage');

        Route::get('/me', function (Request $request) {
            $user = $request->user();

            return response()->json([
                'data' => [
                    'u_id' => $user->getKey(),
                    'u_username' => $user->u_username,
                    'u_role' => $user->u_role,
                    'tenants_t_id' => $user->tenants_t_id,
                    'is_active' => (bool) $user->is_active,
                    'must_change_password' => (bool) $user->must_change_password,
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

        Route::get('/rooms/{room}/meters', [MeterController::class, 'index'])
            ->whereNumber('room')
            ->middleware('role:admin')
            ->name('rooms.meters.index');

        Route::post('/rooms/{room}/meters', [MeterController::class, 'store'])
            ->whereNumber('room')
            ->middleware('role:admin')
            ->name('rooms.meters.store');

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

        Route::post('/rentals/{rental}/move-out', [
            RentalController::class,
            'moveOut',
        ])
            ->whereNumber('rental')
            ->middleware('role:admin')
            ->name('rentals.move-out');

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

        Route::post('/invoices/preview', [InvoiceController::class, 'preview'])
            ->middleware('role:admin')
            ->name('invoices.preview');

        Route::post('/invoices', [InvoiceController::class, 'store'])
            ->middleware('role:admin')
            ->name('invoices.store');

        Route::get('/invoices', [InvoiceController::class, 'index'])
            ->name('invoices.index');

        Route::patch('/invoices/{invoice}', [InvoiceController::class, 'update'])
            ->whereNumber('invoice')
            ->middleware('role:admin')
            ->name('invoices.update');

        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])
            ->whereNumber('invoice')
            ->name('invoices.show');

        Route::get('/invoices/{invoice}/payment', [
            InvoiceController::class,
            'payment',
        ])
            ->whereNumber('invoice')
            ->name('invoices.payment.show');

        Route::post('/invoices/{invoice}/payment/submit', [
            PaymentProofController::class,
            'store',
        ])
            ->whereNumber('invoice')
            ->middleware('role:tenant')
            ->name('invoices.payment.submit');

        Route::post('/invoices/{invoice}/payment/review', [
            PaymentReviewController::class,
            'store',
        ])
            ->whereNumber('invoice')
            ->middleware('role:admin')
            ->name('invoices.payment.review');

        Route::post('/invoices/{invoice}/payment/walk-in', [
            WalkInPaymentController::class,
            'store',
        ])
            ->whereNumber('invoice')
            ->middleware('role:admin')
            ->name('invoices.payment.walk-in');

        Route::get('/invoices/{invoice}/payment/events', [
            PaymentHistoryController::class,
            'index',
        ])
            ->whereNumber('invoice')
            ->name('invoices.payment.events');

        Route::get('/payment-events/{paymentEvent}/proof', [
            PaymentHistoryController::class,
            'proof',
        ])
            ->whereNumber('paymentEvent')
            ->name('payment-events.proof');

        Route::get('/invoices/{invoice}/payment/proof', [
            PaymentProofController::class,
            'show',
        ])
            ->whereNumber('invoice')
            ->name('invoices.payment.proof');

    });
