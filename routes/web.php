<?php

use App\Http\Controllers\AccountManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoicePageController;
use App\Http\Controllers\MaintenancePageController;
use App\Http\Controllers\RentalPageController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'password.changed', 'role:admin'])
    ->group(function () {
        Route::get('/rentals', [RentalPageController::class, 'index'])
            ->name('rentals.index');
        Route::get('/rentals/create', [RentalPageController::class, 'create'])
            ->name('rentals.create');
    });

// รายละเอียดการเช่า — Admin ดูได้ทุกการเช่า, Tenant ดูได้เฉพาะของตัวเอง (API ตรวจสิทธิ์ แล้วตอบ 404 ถ้าไม่ใช่ของตน)
Route::middleware(['auth', 'password.changed', 'role:admin,tenant'])
    ->group(function () {
        Route::get('/rentals/{rental}', [RentalPageController::class, 'show'])
            ->whereNumber('rental')
            ->name('rentals.show');
    });

// ฝั่งผู้เช่า — แสดงเฉพาะข้อมูลของผู้ที่ล็อกอิน (API กรองตาม tenants_t_id ให้แล้ว)
Route::middleware(['auth', 'password.changed', 'role:tenant'])
    ->group(function () {
        Route::get('/my/rentals', [RentalPageController::class, 'mine'])
            ->name('my.rentals');
        Route::get('/my/contracts', [RentalPageController::class, 'myContracts'])
            ->name('my.contracts');
    });

// ใบแจ้งหนี้ / การชำระเงิน — Admin และ Tenant ใช้หน้าเดียวกัน แยกสิทธิ์ที่ API
// (/history ต้องประกาศก่อน /{invoice} เพื่อไม่ให้ถูกมองเป็นเลขที่ใบแจ้งหนี้)
Route::middleware(['auth', 'password.changed', 'role:admin,tenant'])
    ->group(function () {
        Route::get('/invoices', [InvoicePageController::class, 'index'])
            ->name('invoices.index');
        Route::get('/invoices/history', [InvoicePageController::class, 'history'])
            ->name('invoices.history');
        Route::get('/invoices/{invoice}', [InvoicePageController::class, 'show'])
            ->whereNumber('invoice')
            ->name('invoices.show');
    });

Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::get('/meters', [MaintenancePageController::class, 'meters'])->middleware('role:admin')->name('meters.index');
    Route::get('/repairs', [MaintenancePageController::class, 'repairs'])->name('repairs.index');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware(['auth', 'password.changed', 'role:admin'])->group(function () {
    Route::resource('rooms', RoomController::class)->except(['show']);
    Route::resource('tenants', TenantController::class)->except(['show']);
    Route::view('/contracts', 'contracts.index', ['mode' => 'contracts'])->name('contracts.index');
    Route::livewire('admin/accounts', '⚡account-management')->name('admin.accounts');
    Route::post('admin/accounts/create', [AccountManagementController::class, 'create'])->name('admin.accounts.create');
    Route::post('admin/accounts/{userId}/password', [AccountManagementController::class, 'resetPassword'])->whereNumber('userId')->name('admin.accounts.reset-password');
    Route::post('admin/accounts/{userId}/suspend', [AccountManagementController::class, 'suspend'])->whereNumber('userId')->name('admin.accounts.suspend');
});

Route::middleware(['auth', 'password.changed', 'role:tenant'])->group(function () {
    Route::redirect('user/dashboard', '/my/rentals')->name('user.dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/api-session.php';
