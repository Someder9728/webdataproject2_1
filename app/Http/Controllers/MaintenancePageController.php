<?php

namespace App\Http\Controllers;

use App\Models\Repair;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MaintenancePageController extends Controller
{
    public function meters(): View
    {
        return view('maintenance.meters', ['rooms' => Room::orderBy('r_name')->get(['r_id', 'r_name'])]);
    }

    public function repairs(Request $request): View
    {
        Gate::authorize('viewAny', Repair::class);
        $rooms = Room::query();
        if ($request->user()->u_role !== 'admin') {
            $rooms->whereHas('rentals', fn ($q) => $q->where('rt_status', 'ACTIVE')
                ->where('tenants_t_id', $request->user()->tenants_t_id ?? 0));
        }

        return view('maintenance.repairs', [
            'rooms' => $rooms->orderBy('r_name')->get(['r_id', 'r_name']),
            'isAdmin' => $request->user()->u_role === 'admin',
        ]);
    }
}
