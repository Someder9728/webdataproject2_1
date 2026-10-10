<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Payments\ReviewPaymentProof;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentReviewController extends Controller
{
    public function store(Request $request, Invoice $invoice, ReviewPaymentProof $review): JsonResponse
    {
        $payment = $review->handle($request->user(), $invoice, $request->only([
            'decision', 'expected_event_id', 'reason',
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
            ],
            'message' => 'บันทึกผลตรวจหลักฐานสำเร็จ',
        ]);
    }
}
