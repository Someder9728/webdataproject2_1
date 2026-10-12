<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Repair;
use App\Models\Room;
use App\Models\Tenant;
use App\Support\DashboardSummary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardSummary $summary): View|RedirectResponse
    {
        $user = request()->user();

        if ($user && $user->u_role === 'admin') {
            abort_unless($user->is_active && ! $user->must_change_password, 403);
            $validated = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
            $dashboard = $summary->read($validated['month'] ?? now('Asia/Bangkok')->format('Y-m'));
            $dueInvoices = Invoice::with(['rental.tenant', 'rental.room', 'payment'])->whereHas('payment', fn ($q) => $q->whereIn('p_status', ['UNPAID', 'PENDING', 'REJECTED']))->orderBy('i_due')->orderBy('i_id')->limit(5)->get();
            $recentRepairs = Repair::with('room')->orderByDesc('rp_id')->limit(5)->get();
            $totalRooms = $dashboard['rooms']['total'];

            // รองรับแถวเก่าที่ยังเก็บสถานะภาษาไทยจาก seed/ข้อมูลเดิม
            $availableRooms = $dashboard['rooms']['vacant'];

            $occupiedRooms = $dashboard['rooms']['occupied'];

            $totalTenants = Tenant::count();

            $currentTenants = $dashboard['active_rentals'];

            $rooms = Room::orderBy('r_floor')
                ->orderBy('r_name')
                ->get();

            return view('dashboard', compact(
                'totalRooms',
                'availableRooms',
                'occupiedRooms',
                'totalTenants',
                'currentTenants',
                'rooms',
                'dashboard',
                'dueInvoices',
                'recentRepairs'
            ));
        }

        return redirect()->route('user.dashboard');
    }
}
