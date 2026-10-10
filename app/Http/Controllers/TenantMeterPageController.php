<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TenantMeterPageController extends Controller
{
    public function index(): View
    {
        $tenantId = request()->user()->tenants_t_id;

        $rentals = Rental::query()
            ->with('room')
            ->where('tenants_t_id', $tenantId)
            ->orderByDesc('rt_movein')
            ->get();

        $usage = $rentals->map(function (Rental $rental): array {
            $meters = $rental->room?->meters()
                ->whereDate('m_date', '>=', $rental->rt_movein)
                ->when($rental->rt_moveout, fn ($query) => $query->whereDate('m_date', '<=', $rental->rt_moveout))
                ->orderByDesc('m_date')
                ->orderByDesc('m_id')
                ->get() ?? new Collection();

            return compact('rental', 'meters');
        });

        return view('rentals.my-meters', compact('usage'));
    }
}
