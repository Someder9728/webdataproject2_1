<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Payments\RecordWalkInPayment;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalkInPaymentController extends Controller
{
    public function store(Request $request, Invoice $invoice, RecordWalkInPayment $record): JsonResponse
    {
        $payment = $record->handle($request->user(), $invoice, $request->only([
            'amount', 'payment_date', 'method', 'note', 'expected_event_id', 'proof',
        ]));

        return response()->json([
            'data' => [
                'p_id' => $payment->getKey(),
                'invoices_i_id' => $payment->invoices_i_id,
                'p_status' => $payment->p_status,
                'p_amount' => $payment->p_amount,
                'p_date' => $payment->p_date?->format('Y-m-d'),
                'p_type' => $payment->p_type,
                'p_reject_reason' => $payment->p_reject_reason,
                'has_proof' => filled($payment->p_proof),
                'latest_event_id' => $payment->events()->reorder()->orderByDesc('pe_id')->value('pe_id'),
            ],
            'message' => 'บันทึกการรับชำระ Walk-in สำเร็จ',
        ]);
    }
}
