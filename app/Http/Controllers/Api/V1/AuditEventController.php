<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use Illuminate\Http\Request;

class AuditEventController extends Controller
{
    /**
     * GET /api/v1/audit-events?entity_type=contract&entity_id=101
     *
     * TODO(ยืนยันกับเกลือ): endpoint/ชื่อ query param นี้เป็นการม่ั่วเอานะฮาฟฟู็
     * เพราะ database.sqlite ยืนยันแค่ว่ามีตาราง audit_events แบบ polymorphic
     * แต่ไม่มีข้อมูลว่า API เปิด endpoint นี้จริงหรือชื่ออะไร
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'entity_type' => ['required', 'string'],
            'entity_id'   => ['required', 'integer'],
        ]);

        $events = AuditEvent::query()
            ->with('actor:u_id,u_username') // ปรับชื่อคอลัมน์ users ให้ตรงกับ schema จริงถ้าต่าง
            ->where('entity_type', $validated['entity_type'])
            ->where('entity_id', $validated['entity_id'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $events->map(fn (AuditEvent $e) => [
                'ae_id'       => $e->ae_id,
                'actor'       => $e->actor ? [
                    'u_id'       => $e->actor->u_id,
                    'u_username' => $e->actor->u_username,
                ] : null,
                'entity_type' => $e->entity_type,
                'entity_id'   => $e->entity_id,
                'action'      => $e->action,
                'old_values'  => $e->old_values,
                'new_values'  => $e->new_values,
                'reason'      => $e->reason,
                'created_at'  => optional($e->created_at)->toIso8601String(),
            ]),
        ]);
    }
}
