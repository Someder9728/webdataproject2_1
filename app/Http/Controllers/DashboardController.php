<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = request()->user();

        if ($user && $user->u_role === 'admin') {
            $totalRooms = Room::count();

            // รองรับแถวเก่าที่ยังเก็บสถานะภาษาไทยจาก seed/ข้อมูลเดิม
            $availableRooms = Room::whereIn('r_status', ['VACANT', 'ว่าง'])->count();

            $occupiedRooms = Room::whereIn('r_status', ['OCCUPIED', 'มีผู้พัก'])->count();

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
