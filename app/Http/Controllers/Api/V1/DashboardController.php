<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\Repair;
use App\Models\Room;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor->is_active && ! $actor->must_change_password && $actor->u_role === 'admin', 403);
        $validated = $request->validate(['month' => ['sometimes', 'date_format:Y-m']]);
        $month = CarbonImmutable::createFromFormat('!Y-m', $validated['month'] ?? now('Asia/Bangkok')->format('Y-m'), 'Asia/Bangkok');
        $start = $month->toDateString();
        $end = $month->addMonth()->toDateString();

        // One read transaction keeps all cards on the same SQLite snapshot.
        $data = DB::transaction(function () use ($start, $end, $month) {
            $invoices = Invoice::query()->whereDate('period_start', '>=', $start)->whereDate('period_start', '<', $end);
            $payments = Payment::query()->whereHas('invoice');
            $outstanding = (clone $payments)->whereIn('p_status', ['UNPAID', 'PENDING', 'REJECTED']);
            $overdue = (clone $outstanding)->whereHas('invoice', fn ($q) => $q->whereDate('i_due', '<', now('Asia/Bangkok')->toDateString()));
            $received = (clone $payments)->where('p_status', 'PAID')->whereDate('p_date', '>=', $start)->whereDate('p_date', '<', $end);

            return [
                'month' => $month->format('Y-m'),
                'as_of' => now('Asia/Bangkok')->toDateString(),
                'rooms' => [
                    'total' => Room::count(),
                    'vacant' => Room::whereIn('r_status', ['VACANT', 'ว่าง'])->count(),
                    'occupied' => Room::whereIn('r_status', ['OCCUPIED', 'มีผู้พัก'])->count(),
                ],
                'active_rentals' => Rental::where('rt_status', 'ACTIVE')->count(),
                'invoices' => ['count' => (clone $invoices)->count(), 'amount' => $this->money((clone $invoices)->pluck('i_total'))],
                'payments' => [
                    'received_amount' => $this->money($received->pluck('p_amount')),
                    'outstanding_count' => (clone $outstanding)->count(),
                    'outstanding_amount' => $this->money($outstanding->pluck('p_amount')),
                    'overdue_count' => (clone $overdue)->count(),
                    'overdue_amount' => $this->money($overdue->pluck('p_amount')),
                    'pending_count' => (clone $payments)->where('p_status', 'PENDING')->count(),
                ],
                'repairs' => Repair::query()->selectRaw('rp_status, count(*) as total')->groupBy('rp_status')->pluck('total', 'rp_status'),
            ];
        });

        return response()->json(['data' => $data, 'message' => 'อ่านภาพรวมสำเร็จ']);
    }

    /** @param iterable<string|int|float> $amounts */
    private function money(iterable $amounts): string
    {
        $total = BigDecimal::zero();
        foreach ($amounts as $amount) {
            $total = $total->plus((string) $amount);
        }

        return (string) $total->toScale(2);
    }
}
