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
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/api-session.php';