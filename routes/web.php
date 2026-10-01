<?php

use App\Http\Controllers\RentalPageController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('/rentals', [RentalPageController::class, 'index'])->name('rentals.index');
    Route::get('/rentals/create', [RentalPageController::class, 'create'])->name('rentals.create');
    Route::get('/rentals/{rental}', [RentalPageController::class, 'show'])->name('rentals.show');

});

Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/api-session.php';