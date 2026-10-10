<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Payments\SubmitPaymentProof;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentProofController extends Controller
{
    public function store(
        Request $request,
        Invoice $invoice,
        SubmitPaymentProof $action
    ): JsonResponse {
        $payment = $action->handle(
            $request->user(),
            $invoice,
            [
                'amount' => $request->input('amount'),
                'payment_date' => $request->input('payment_date'),
                'proof' => $request->file('proof'),
            ]
        );

        return response()->json([
            'data' => [
                'p_id' => $payment->getKey(),
                'invoices_i_id' => $payment->invoices_i_id,
                'p_amount' => $payment->p_amount,
                'p_date' => $payment->p_date?->format('Y-m-d'),
                'p_type' => $payment->p_type,
                'p_status' => $payment->p_status,
                'has_proof' => true,
            ],
            'message' => 'ส่งหลักฐานชำระเงินสำเร็จ รอผู้ดูแลตรวจสอบ',
        ]);
    }

    public function show(Invoice $invoice): StreamedResponse
    {
        Gate::authorize('viewPaymentProof', $invoice);

        $payment = $invoice->payment()->firstOrFail();
        $path = $payment->p_proof;

        abort_unless(filled($path), 404);

        $disk = Storage::disk('payment_proofs');

        abort_unless($disk->exists($path), 404);

        $mime = $disk->mimeType($path);

        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
            default => null,
        };

        abort_if($extension === null, 404);

        return $disk->download(
            $path,
            'payment-proof-'.$payment->getKey().'.'.$extension,
            [
                'Content-Type' => $mime,
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
