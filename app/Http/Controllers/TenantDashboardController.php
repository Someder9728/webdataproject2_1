<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Rental;
use App\Models\Repair;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TenantDashboardController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Rental::class);
        Gate::authorize('viewAny', Invoice::class);
        Gate::authorize('viewAny', Repair::class);
        $actor = $request->user();
        $rentals = Rental::with(['room', 'contract'])->where('tenants_t_id', $actor->tenants_t_id ?? 0);
        $current = (clone $rentals)->where('rt_status', 'ACTIVE')->first()
            ?? (clone $rentals)->orderByDesc('rt_movein')->orderByDesc('rt_id')->first();
        $invoices = Invoice::visibleTo($actor);
        $open = (clone $invoices)->whereHas('payment', fn ($q) => $q->whereIn('p_status', ['UNPAID', 'PENDING', 'REJECTED']));
        $repairs = Repair::visibleTo($actor);

        return view('user.dashboard', [
            'current' => $current,
            'invoices' => (clone $invoices)->with('payment')->orderByDesc('created_at')->orderByDesc('i_id')->limit(3)->get(),
            'outstandingAmount' => (clone $open)->sum('i_total'),
            'outstandingCount' => (clone $open)->count(),
            'repairCount' => (clone $repairs)->whereIn('rp_status', ['REPORTED', 'IN_PROGRESS'])->count(),
            'repairs' => $repairs->orderByDesc('rp_id')->limit(3)->get(),
        ]);
    }
}
