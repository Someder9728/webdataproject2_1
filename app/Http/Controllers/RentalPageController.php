<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use App\Support\RentalTimeline;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RentalPageController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Rental::class);

        $search = trim($request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? '');

        $rentals = fn () => Rental::with(['tenant', 'room'])->search($search);

        return view('rentals.index', [
            'search' => $search,
            'active' => $rentals()->where('rt_status', 'ACTIVE')->orderByDesc('rt_movein')->orderByDesc('rt_id')->get(),
            'scheduled' => $rentals()->where('rt_status', 'SCHEDULED')->orderBy('scheduled_movein_at')->get(),
            'ended' => $rentals()->whereIn('rt_status', ['ENDED', 'CANCELLED'])->orderByDesc('rt_moveout')->orderByDesc('rt_id')->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Rental::class);
        $tenants = Tenant::whereHas('user')->orderBy('t_Fname')->get(['t_id', 't_Fname', 't_Lname', 't_tel']);
        $rooms = Room::query()->orderBy('r_name')->get(['r_id', 'r_name', 'r_type', 'r_rent']);

        return view('rentals.create', compact('tenants', 'rooms'));
    }

    public function show(Request $request, int $rental): View
    {
        $record = Rental::with(['tenant', 'room', 'contract'])->findOrFail($rental);
        Gate::authorize('view', $record);

        if ($request->user()->u_role === 'tenant') {
            return view('tenant.rental', ['rental' => $record]);
        }

        $contract = $record->contract;

        $events = AuditEvent::with('actor')
            ->whereIn('entity_type', ['contracts', 'contract'])
            ->whereIn('entity_id', $record->contract()->withTrashed()->pluck('c_id'))
            ->whereIn('action', ['contract_updated', 'CONTRACT_EXTENDED'])
            ->orderByDesc('created_at')
            ->orderByDesc('ae_id')
            ->get();

        $lockReason = match (true) {
            $contract?->c_status === 'ENDED' => 'สัญญานี้สิ้นสุดแล้ว — แก้ไขหรือต่อสัญญาไม่ได้',
            $record->rt_status !== 'ACTIVE' || $record->rt_moveout !== null => 'การเช่านี้ย้ายออกแล้ว — แก้ไขหรือต่อสัญญาไม่ได้',
            default => '',
        };

        return view('rentals.show', [
            'rental' => $record,
            'contract' => $contract,
            'priceLocked' => $record->invoices()->withTrashed()->exists(),
            'lockReason' => $lockReason,
            'timeline' => RentalTimeline::build($record, $contract, $events),
            'lifecycleEvents' => AuditEvent::with('actor')->where('entity_type', 'rentals')->where('entity_id', $record->getKey())->whereIn('action', ['rental_activated', 'moveout_schedule_changed', 'scheduled_rental_cancelled', 'rental_moved_out'])->orderByDesc('ae_id')->get(),
        ]);
    }

    public function mine(Request $request): View
    {
        return view('tenant.rentals', [
            'current' => $this->tenantRentals($request)->whereIn('rt_status', ['ACTIVE', 'SCHEDULED'])->get(),
            'history' => $this->tenantRentals($request)->whereIn('rt_status', ['ENDED', 'CANCELLED'])->orderByDesc('rt_movein')->paginate(20),
        ]);
    }

    public function myContracts(Request $request): View
    {
        return view('tenant.contracts', ['rentals' => $this->tenantRentals($request)->whereHas('contract')->orderByDesc('rt_movein')->paginate(20)]);
    }

    /** @return Builder<Rental> */
    private function tenantRentals(Request $request): Builder
    {
        Gate::authorize('viewAny', Rental::class);

        return Rental::with(['room', 'contract'])->where('tenants_t_id', $request->user()->tenants_t_id ?? 0);
    }
}
