<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ContractPageController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Rental::class);
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'in:ACTIVE,EXPIRED,ENDED,SCHEDULED,CANCELLED'], 'page' => ['nullable', 'integer', 'min:1']]);
        $rentals = Rental::with(['tenant', 'room', 'contract'])->whereHas('contract')->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->whereHas('contract', fn ($c) => $c->where('c_status', $status)))
            ->orderByDesc('rt_id')->paginate(20)->withQueryString();

        return view('contracts.index', ['mode' => 'contracts', 'rentals' => $rentals, 'filters' => $filters]);
    }
}
