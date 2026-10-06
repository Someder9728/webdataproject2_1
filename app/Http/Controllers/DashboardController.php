<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user = request()->user();

        if ($user && $user->u_role === 'admin') {
            $totalRooms = Room::count();

            $availableRooms = Room::where('r_status', 'ว่าง')->count();

            $occupiedRooms = Room::where('r_status', 'มีผู้พัก')->count();

            $totalTenants = Tenant::count();

            $currentTenants = User::whereNotNull('tenants_t_id')
                ->where('is_active', true)
                ->count();

            $rooms = Room::orderBy('r_floor')
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
    }
}