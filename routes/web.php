<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\TenantController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'password.changed'])->group(function () {

    // DASHBOARD
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // NORMAL USER DASHBOARD
    Route::view('user/dashboard', 'user.dashboard')
        ->middleware('user')
        ->name('user.dashboard');

    // ADMIN - TENANTS AND ROOMS
    Route::middleware('admin')->group(function () {

        Route::resource('tenants', TenantController::class)
            ->except(['show']);

        Route::resource('rooms', RoomController::class)
            ->except(['show']);
    });

    // ADMIN - ACCOUNT MANAGEMENT
    Route::livewire('admin/accounts', '⚡account-management')
        ->name('admin.accounts');
});

// LOGOUT
Route::post('/logout', function (Request $request) {

    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');

})->middleware('auth')->name('logout');

require __DIR__.'/settings.php';