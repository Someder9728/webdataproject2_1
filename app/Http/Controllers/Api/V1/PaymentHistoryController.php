<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentHistoryController extends Controller
{
    public function index(Request $request, Invoice $invoice): JsonResponse
    {
        Gate::authorize('viewPaymentHistory', $invoice);

        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $payment = $invoice->payment()->firstOrFail();
        $isAdmin = $request->user()->u_role === 'admin';

        $query = $payment->events()
            ->reorder()
            ->orderByDesc('pe_id');

        if ($isAdmin) {
            $query->with([
                'actor' => fn ($query) => $query
                    ->withTrashed()
                    ->select(['u_id', 'u_username']),
            ]);
        }

        $events = $query->paginate(
            (int) ($validated['per_page'] ?? 20)
        );

        $data = $events->getCollection()->map(
            function (PaymentEvent $event) use ($request, $isAdmin): array {
                $actor = [
                    'is_self' => (string) $event->actor_user_id
                        === (string) $request->user()->getKey(),
                ];

                // ผู้เช่าไม่ต้องได้รับ username หรือ ID บัญชีผู้ดูแล
                if ($isAdmin) {
                    $actor['u_id'] = $event->actor_user_id;
                    $actor['u_username'] = $event->actor?->u_username;
                }

                return [
                    'pe_id' => $event->getKey(),
                    'payments_p_id' => $event->payments_p_id,
                    'event_type' => $event->event_type,
                    'from_status' => $event->from_status,
                    'to_status' => $event->to_status,
                    'amount' => $event->amount,
                    'payment_date' => $event->payment_date?->format('Y-m-d'),
                    'method' => $event->method,
                    'reason' => $event->reason,
                    'note' => $event->note,
                    'has_proof' => filled($event->proof_path),
                    'created_at' => $event->created_at?->toIso8601String(),
                    'actor' => $actor,
                ];
            }
        )->values()->all();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $events->currentPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
                'last_page' => $events->lastPage(),
            ],
            'message' => 'อ่านประวัติการชำระเงินสำเร็จ',
        ]);
    }

    public function proof(PaymentEvent $paymentEvent): StreamedResponse
    {
        $payment = $paymentEvent->payment()->firstOrFail();
        $invoice = $payment->invoice()->firstOrFail();

        Gate::authorize('viewPaymentHistory', $invoice);

        $path = $paymentEvent->proof_path;

        abort_unless(filled($path), 404);

        $disk = Storage::disk('payment_proofs');

        abort_unless($disk->exists($path), 404);

                $mime = (new \finfo(FILEINFO_MIME_TYPE))
            ->file($disk->path($path));

        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
            default => null,
        };

        abort_if($extension === null, 404);

        return $disk->download(
            $path,
            'payment-event-'.$paymentEvent->getKey().'.'.$extension,
            [
                'Content-Type' => $mime,
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}