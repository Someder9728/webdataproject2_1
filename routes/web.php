<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RentalPageController;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'password.changed', 'role:admin'])
    ->group(function () {
        Route::get('/rentals', [RentalPageController::class, 'index'])
            ->name('rentals.index');
    });


Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::get('/meters', [\App\Http\Controllers\MaintenancePageController::class, 'meters'])->middleware('role:admin')->name('meters.index');
    Route::get('/repairs', [\App\Http\Controllers\MaintenancePageController::class, 'repairs'])->name('repairs.index');
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/api-session.php';
