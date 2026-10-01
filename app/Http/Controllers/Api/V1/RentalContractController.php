<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\Rental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RentalContractController extends Controller
{
    /**
     * GET /api/v1/rentals/{rental}/contract
     * ใช้ในหน้า R3 (Contract panel)
     */
    public function show(Rental $rental)
    {
        // TODO: authorize — Admin ดูได้ทุกใบ, Tenant ดูได้เฉพาะของตัวเอง (ownership check)

        $contract = $rental->contract;

        if (! $contract) {
            return response()->json([
                'message' => 'ไม่พบสัญญาของการเช่านี้',
                'errors'  => null,
                'code'    => 'NOT_FOUND',
            ], 404);
        }

        return response()->json([
            'data' => $this->transform($contract),
            'message' => 'OK',
        ]);
    }

    /**
     * PATCH /api/v1/rentals/{rental}/contract
     * ใช้ในหน้า R4 — แก้ได้เฉพาะ c_end + เหตุผล (บังคับกรอก)
     * c_rent ล็อกทันทีที่มี Invoice ใบแรก และไม่รับค่านี้จาก client เลย
     */
    public function update(Request $request, Rental $rental)
    {
        // TODO: authorize — เฉพาะ Admin

        $contract = $rental->contract;

        if (! $contract) {
            return response()->json([
                'message' => 'ไม่พบสัญญาของการเช่านี้',
                'errors'  => null,
                'code'    => 'NOT_FOUND',
            ], 404);
        }

        // Rental จบไปแล้ว (Move-out แล้ว) แก้สัญญาไม่ได้อีก — กันเคสแก้ย้อนหลังหลัง 409
        if ($rental->rt_status === Rental::STATUS_ENDED) {
            return response()->json([
                'message' => 'สถานะสัญญาเปลี่ยนไปแล้ว กรุณาโหลดข้อมูลใหม่',
                'errors'  => null,
                'code'    => 'STATE_CONFLICT',
            ], 409);
        }

        // ถ้า client แอบส่ง c_rent มา ให้ตีเป็น 422 ไปเลย ไม่เงียบ ๆ ทิ้งค่า
        if ($request->has('c_rent') && $contract->isPriceLocked()) {
            return response()->json([
                'message' => 'ไม่สามารถแก้ไขค่าเช่าได้ เนื่องจากมี Invoice ใบแรกแล้ว',
                'errors'  => ['c_rent' => ['ราคาถูกล็อกหลังออก Invoice ใบแรก']],
                'code'    => 'VALIDATION_ERROR',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'c_end'  => ['nullable', 'date', 'after:' . $contract->c_start->format('Y-m-d')],
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'c_end.after'      => 'วันสิ้นสุดสัญญาต้องอยู่หลังวันเข้า',
            'reason.required'  => 'กรุณาระบุเหตุผล',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors'  => $validator->errors(),
                'code'    => 'VALIDATION_ERROR',
            ], 422);
        }

        // กัน race condition: โหลดสถานะล่าสุดใน transaction ก่อนเขียนทับ
        $updated = DB::transaction(function () use ($request, $contract, $rental) {
            /** @var \App\Models\Contract $fresh */
            $fresh = $rental->contract()->lockForUpdate()->first();

            $old = [
                'c_end'     => optional($fresh->c_end)->format('Y-m-d'),
                'c_status'  => $fresh->c_status,
            ];

            $fresh->c_end = $request->input('c_end');

            // ต่อสัญญาที่หมดอายุแล้ว (EXPIRED) ให้กลับเป็น ACTIVE ถ้ากำหนด c_end ใหม่ให้เป็นอนาคต
            // TODO(ยืนยันกับเกลือ): กฎการเปลี่ยน c_status ตอนต่อสัญญาควรเป็นแบบนี้ไหม
            if ($fresh->c_status === \App\Models\Contract::STATUS_EXPIRED
                && (! $request->filled('c_end') || now()->lt($request->input('c_end')))) {
                $fresh->c_status = \App\Models\Contract::STATUS_ACTIVE;
            }

            $fresh->save();

            AuditEvent::create([
                'actor_user_id' => auth()->id(),
                'entity_type'   => 'contract',
                'entity_id'     => $fresh->c_id,
                'action'        => 'CONTRACT_EXTENDED',
                'old_values'    => $old,
                'new_values'    => [
                    'c_end'    => optional($fresh->c_end)->format('Y-m-d'),
                    'c_status' => $fresh->c_status,
                ],
                'reason'        => $request->input('reason'),
            ]);

            return $fresh;
        });

        return response()->json([
            'data' => $this->transform($updated),
            'message' => 'ต่อสัญญาสำเร็จ',
        ]);
    }

    private function transform(\App\Models\Contract $c): array
    {
        return [
            'c_id'          => $c->c_id,
            'c_number'      => $c->c_number,
            'rentals_rt_id' => $c->rentals_rt_id,
            'c_start'       => optional($c->c_start)->format('Y-m-d'),
            'c_end'         => optional($c->c_end)->format('Y-m-d'),
            'c_rent'        => (string) $c->c_rent,
            'c_status'      => $c->c_status,
            'c_deposit'     => (string) $c->c_deposit,
            'price_locked'  => $c->isPriceLocked(),
            'price_locked_reason' => $c->priceLockedReason(),
        ];
    }
}