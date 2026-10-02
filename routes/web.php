<?php

use App\Http\Controllers\RoomController;
use App\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'password.changed'])->group(function () {

    // DASHBOARD
    Route::get('dashboard', function () {
    $user = request()->user();

    if ($user && $user->u_role === 'admin') {
        $totalRooms = \App\Models\Room::count();

        $availableRooms = \App\Models\Room::where('r_status', 'ว่าง')->count();

        $occupiedRooms = \App\Models\Room::where('r_status', 'มีผู้พัก')->count();

        $totalTenants = \App\Models\Tenant::count();

        $currentTenants = \App\Models\User::whereNotNull('tenants_t_id')
            ->where('is_active', true)
            ->count();

        $rooms = \App\Models\Room::orderBy('r_floor')
            ->orderBy('r_name')
            ->get();

        return view('dashboard', compact(
            'totalRooms',
            'availableRooms',
            'occupiedRooms',
            'totalTenants',
            'currentTenants',
            'rooms'
        ));
    }

    return redirect()->route('user.dashboard');
})->name('dashboard');

    // NORMAL USER DASHBOARD
    Route::view('user/dashboard', 'user.dashboard')
        ->middleware('user')
        ->name('user.dashboard');
        
    // ADMIN - TENANTS
    Route::middleware('admin')->group(function () {

    Route::resource('tenants', TenantController::class)
        ->except(['show']);

    Route::resource('rooms', RoomController::class)
        ->except(['show']);
    });

    Route::livewire('admin/accounts','⚡account-management')
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