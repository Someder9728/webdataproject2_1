<?php

use App\Http\Controllers\AccountManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\TenantController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::view('user/dashboard', 'user.dashboard')
        ->middleware('user')
        ->name('user.dashboard');

    Route::middleware('admin')->group(function () {
        Route::view('rentals', 'rentals.index', ['mode' => 'rentals'])->name('rentals.index');
        Route::view('contracts', 'rentals.index', ['mode' => 'contracts'])->name('contracts.index');

        Route::resource('tenants', TenantController::class)->except(['show']);
        Route::resource('rooms', RoomController::class)->except(['show']);

        Route::livewire('admin/accounts', '⚡account-management')->name('admin.accounts');
        Route::post('admin/accounts/create', [AccountManagementController::class, 'create'])
            ->name('admin.accounts.create');
        Route::post('admin/accounts/{userId}/password', [AccountManagementController::class, 'resetPassword'])
            ->whereNumber('userId')
            ->name('admin.accounts.reset-password');
        Route::post('admin/accounts/{userId}/suspend', [AccountManagementController::class, 'suspend'])
            ->whereNumber('userId')
            ->name('admin.accounts.suspend');
    });
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

require __DIR__.'/settings.php';

