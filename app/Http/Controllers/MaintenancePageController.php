<?php

namespace App\Http\Controllers;

use App\Models\Meter;
use App\Models\Repair;
use App\Models\Room;
use App\Support\MeterPresentation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MaintenancePageController extends Controller
{
    public function meters(Request $request, MeterPresentation $presenter): View
    {
        $actor = $request->user();
        abort_unless($actor->is_active && ! $actor->must_change_password && $actor->u_role === 'admin', 403);
        $filters = $request->validate(['room' => ['nullable', 'integer', 'min:1'], 'page' => ['nullable', 'integer', 'min:1']]);
        $selectedRoom = isset($filters['room']) ? Room::whereKey($filters['room'])->firstOrFail() : null;
        $meters = Meter::withTrashed()->where('rooms_r_id', $selectedRoom?->getKey() ?? 0)->orderByDesc('m_date')->orderByDesc('m_id')->paginate(20)->withQueryString();

        return view('maintenance.meters', ['rooms' => Room::orderBy('r_name')->get(['r_id', 'r_name']), 'selectedRoom' => $selectedRoom, 'meters' => $meters, 'meterRows' => $presenter->rows($meters->items())]);
    }

    public function repairs(Request $request): View
    {
        Gate::authorize('viewAny', Repair::class);
        $filters = $request->validate(['rp_status' => ['nullable', 'in:REPORTED,IN_PROGRESS,COMPLETED'], 'page' => ['nullable', 'integer', 'min:1']]);
        $rooms = Room::query();
        if ($request->user()->u_role !== 'admin') {
            $rooms->whereHas('rentals', fn ($q) => $q->where('rt_status', 'ACTIVE')->where('tenants_t_id', $request->user()->tenants_t_id ?? 0));
        }

        return view('maintenance.repairs', [
            'rooms' => $rooms->orderBy('r_name')->get(['r_id', 'r_name']), 'isAdmin' => $request->user()->u_role === 'admin',
            'repairs' => Repair::visibleTo($request->user())->with('room')->when($filters['rp_status'] ?? null, fn ($q, $status) => $q->where('rp_status', $status))->orderByDesc('rp_id')->paginate(20)->withQueryString(),
        ]);
    }
}
