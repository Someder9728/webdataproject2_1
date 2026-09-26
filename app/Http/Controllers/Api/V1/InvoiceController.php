<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Billing\IssueInvoice;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    private function input(Request $request): array
    {
        return $request->only([
            'rentals_rt_id',
            'period_start',
            'period_end',
        ]);
    }
}