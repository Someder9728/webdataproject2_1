<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\Repair;
use App\Models\Room;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class DashboardSummary
{
    /** @return array<string, mixed> */
    public function read(string $selectedMonth): array
    {
        $month = CarbonImmutable::createFromFormat('!Y-m', $selectedMonth, 'Asia/Bangkok');
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
                'scheduled_rentals' => Rental::where('rt_status', 'SCHEDULED')->count(),
                'scheduled_moveouts' => Rental::where('rt_status', 'ACTIVE')->whereNotNull('scheduled_moveout_at')->count(),
                'due_moveins' => Rental::where('rt_status', 'SCHEDULED')->whereDate('scheduled_movein_at', '<=', now('Asia/Bangkok')->toDateString())->count(),
                'due_moveouts' => Rental::where('rt_status', 'ACTIVE')->whereDate('scheduled_moveout_at', '<=', now('Asia/Bangkok')->toDateString())->count(),
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

        return $data;
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
