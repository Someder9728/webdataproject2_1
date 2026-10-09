<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Rental;
use App\Models\Tenant;

class DashboardController extends Controller
{
    public function index()
    {
        $user = request()->user();

        if ($user && $user->u_role === 'admin') {
            $totalRooms = Room::count();

            $availableRooms = Room::where('r_status', 'VACANT')->count();

            $occupiedRooms = Room::where('r_status', 'OCCUPIED')->count();

            $totalTenants = Tenant::count();

            $currentTenants = Rental::where('rt_status', 'ACTIVE')->count();

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
