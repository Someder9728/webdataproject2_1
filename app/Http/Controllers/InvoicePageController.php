<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class InvoicePageController extends Controller
{
    public function index(): View
    {
        return view('invoices.index');
    }

    public function history(Request $request): View
    {
        Gate::authorize('viewAny', Invoice::class);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $query = Invoice::query()
            ->visibleTo($request->user())
            ->whereHas('payment', fn ($payment) => $payment->where('p_status', 'PAID'))
            ->search($filters['q'] ?? null)
            ->inMonth($filters['month'] ?? null);

        $total = Payment::query()->whereIn('invoices_i_id', (clone $query)->select('i_id'))->sum('p_amount');

        $invoices = $query
            ->with(['rental.tenant', 'rental.room', 'payment'])
            ->orderByDesc(Payment::select('p_date')->whereColumn('payments.invoices_i_id', 'invoices.i_id'))
            ->orderByDesc('i_id')
            ->paginate(50)
            ->withQueryString();

        return view('invoices.history', [
            'invoices' => $invoices,
            'total' => $total,
            'filters' => $filters,
            'isAdmin' => $request->user()->u_role === 'admin',
        ]);
    }

    public function show(Request $request, Invoice $invoice): View
    {
        Gate::authorize('view', $invoice);

        $invoice->load(['rental.tenant', 'rental.room', 'payment']);

        $events = $invoice->payment
            ? $invoice->payment->events()
                ->with(['actor' => fn ($actor) => $actor->withTrashed()->select(['u_id', 'u_username'])])
                ->reorder()
                ->orderByDesc('pe_id')
                ->get()
            : collect();

        return view('invoices.show', [
            'invoice' => $invoice,
            'events' => $events,
            'isAdmin' => $request->user()->u_role === 'admin',
        ]);
    }
}
