<?php

use App\Http\Controllers\InvoicePageController;
use App\Http\Controllers\MaintenancePageController;
use App\Http\Controllers\RentalPageController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'password.changed', 'role:admin'])
    ->group(function () {
        Route::get('/rentals', [RentalPageController::class, 'index'])
            ->name('rentals.index');
        Route::get('/rentals/create', [RentalPageController::class, 'create'])
            ->name('rentals.create');
        Route::get('/rentals/{rental}', [RentalPageController::class, 'show'])
            ->whereNumber('rental')
            ->name('rentals.show');
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
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/api-session.php';
