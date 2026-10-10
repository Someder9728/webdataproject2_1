<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Billing\IssueInvoice;
use App\Actions\Billing\UpdateInvoice;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function preview(
        Request $request,
        IssueInvoice $action
    ): JsonResponse {
        $data = $action->handle(
            $request->user(),
            $this->input($request)
        );

        return response()->json([
            'data' => $data,
            'message' => 'คำนวณยอดตรวจสอบสำเร็จ ยังไม่ได้ออกใบแจ้งหนี้',
        ]);
    }

    public function store(
        Request $request,
        IssueInvoice $action
    ): JsonResponse {
        $data = $action->handle(
            $request->user(),
            $this->input($request),
            persist: true
        );

        return response()->json([
            'data' => $data,
            'message' => 'ออกใบแจ้งหนี้สำเร็จ',
        ], 201);
    }

    public function update(
        Request $request,
        Invoice $invoice,
        UpdateInvoice $action
    ): JsonResponse {
        $invoice = $action->handle(
            $request->user(),
            $invoice,
            $request->only([
                'period_start',
                'period_end',
                'i_due',
                'reason',
            ])
        );

        $invoice->load(['rental.tenant', 'rental.room', 'payment']);

        return response()->json([
            'data' => $this->invoiceData($invoice),
            'message' => 'แก้ไขใบแจ้งหนี้สำเร็จ',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function input(Request $request): array
    {
        return $request->only([
            'rentals_rt_id',
            'period_start',
            'period_end',
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Invoice::class);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'month' => ['nullable', 'date_format:Y-m'],
            'status' => [
                'nullable',
                Rule::in(['UNPAID', 'PENDING', 'PAID', 'REJECTED']),
            ],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Invoice::query()->with([
            'rental.tenant',
            'rental.room',
            'payment',
        ]);

        $user = $request->user();

        // จำกัดเจ้าของก่อนเพิ่มเงื่อนไขค้นหา
        if ($user->u_role === 'tenant') {
            if ($user->tenants_t_id === null) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas('rental', function ($rental) use ($user) {
                    $rental->where('tenants_t_id', $user->tenants_t_id);
                });
            }
        }

        $search = trim($validated['search'] ?? '');

        if ($search !== '') {
            $query->where(function ($filter) use ($search) {
                $filter->whereHas('rental.tenant', function ($tenant) use ($search) {
                    $tenant->where(function ($names) use ($search) {
                        $names->whereRaw('instr(t_Fname, ?) > 0', [$search])
                            ->orWhereRaw('instr(t_Lname, ?) > 0', [$search])
                            ->orWhereRaw(
                                "instr(t_Fname || ' ' || t_Lname, ?) > 0",
                                [$search]
                            );
                    });
                })->orWhereHas('rental.room', function ($room) use ($search) {
                    $room->whereRaw('instr(r_name, ?) > 0', [$search]);
                });
            });
        }

        if (! empty($validated['month'])) {
            $month = CarbonImmutable::createFromFormat(
                '!Y-m',
                $validated['month'],
                'Asia/Bangkok'
            );

            // เดือนรอบบิลอ้างอิง period_start ไม่ใช่วันออกบิล
            $query->whereDate('period_start', '>=', $month->toDateString())
                ->whereDate(
                    'period_start',
                    '<',
                    $month->addMonth()->toDateString()
                );
        }

        if (! empty($validated['status'])) {
            $query->whereHas('payment', function ($payment) use ($validated) {
                $payment->where('p_status', $validated['status']);
            });
        }

        $invoices = $query
            ->orderByDesc('created_at')
            ->orderByDesc('i_id')
            ->paginate(
                (int) ($validated['per_page'] ?? 20),
                ['*'],
                'page',
                (int) ($validated['page'] ?? 1)
            );

        return response()->json([
            'data' => $invoices->getCollection()
                ->map(fn (Invoice $invoice) => $this->invoiceData($invoice)),
            'message' => 'อ่านรายการใบแจ้งหนี้สำเร็จ',
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
                'last_page' => $invoices->lastPage(),
            ],
        ]);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        Gate::authorize('view', $invoice);

        $invoice->load(['rental.tenant', 'rental.room', 'payment']);

        return response()->json([
            'data' => $this->invoiceData($invoice),
            'message' => 'อ่านรายละเอียดใบแจ้งหนี้สำเร็จ',
        ]);
    }

    public function payment(Invoice $invoice): JsonResponse
    {
        Gate::authorize('view', $invoice);

        $payment = $invoice->payment()->firstOrFail();

        return response()->json([
            'data' => $this->paymentData($payment),
            'message' => 'อ่านข้อมูลการชำระเงินสำเร็จ',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function invoiceData(Invoice $invoice): array
    {
        $data = [
            'i_id' => $invoice->getKey(),
            'rentals_rt_id' => $invoice->rentals_rt_id,
            'start_meter_id' => $invoice->start_meter_id,
            'end_meter_id' => $invoice->end_meter_id,
        ];

        foreach (['i_date', 'i_due', 'period_start', 'period_end'] as $field) {
            $data[$field] = $invoice->{$field}?->format('Y-m-d');
        }

        foreach ([
            'i_rent',
            'i_water',
            'i_elec',
            'i_total',
            'water_usage',
            'elec_usage',
            'water_rate',
            'elec_rate',
            'rent_rate',
        ] as $field) {
            $data[$field] = $invoice->{$field};
        }

        $tenant = $invoice->rental?->tenant;
        $room = $invoice->rental?->room;

        $data['tenant'] = $tenant ? [
            't_id' => $tenant->getKey(),
            't_Fname' => $tenant->t_Fname,
            't_Lname' => $tenant->t_Lname,
        ] : null;

        $data['room'] = $room ? [
            'r_id' => $room->getKey(),
            'r_name' => $room->r_name,
        ] : null;

        $data['payment'] = $invoice->payment
            ? $this->paymentData($invoice->payment)
            : null;

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentData(Payment $payment): array
    {
        return [
            'p_id' => $payment->getKey(),
            'invoices_i_id' => $payment->invoices_i_id,
            'p_amount' => $payment->p_amount,
            'p_status' => $payment->p_status,
            'p_date' => $payment->p_date?->format('Y-m-d'),
            'p_type' => $payment->p_type,
            'p_reject_reason' => $payment->p_reject_reason,
            'has_proof' => filled($payment->p_proof),
            'latest_event_id' => $payment->events()->reorder()->orderByDesc('pe_id')->value('pe_id'),
        ];
    }
}
