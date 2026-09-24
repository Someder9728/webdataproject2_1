<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RentalController extends Controller
{
    /**
     * GET /api/v1/rentals
     * ตาราง R1 ในแผนงาน — list + search Tenant/Room + pagination
     *
     * Query params: search, page (default 1), per_page (default 20, max 100)
     */
    public function index(Request $request)
    {
        // TODO: เปลี่ยนเป็น $this->authorize('viewAny', Rental::class) เมื่อมี Policy จริง
        // ถ้าไม่ authenticated ต้องคืน 401 (จัดการที่ auth middleware อยู่แล้ว)

        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min($perPage, 100)); // กันค่าเกิน 100 ตามที่ตกลงไว้

        $rentals = Rental::query()
            ->with(['tenant', 'room', 'contract'])
            ->search($request->query('search'))
            ->orderByDesc('created_at')
            ->orderByDesc('rt_id')
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json([
            'data' => $rentals->getCollection()->map(fn (Rental $r) => $this->transform($r)),
            'meta' => [
                'page'     => $rentals->currentPage(),
                'per_page' => $rentals->perPage(),
                'total'    => $rentals->total(),
            ],
            'links' => [
                'first' => $rentals->url(1),
                'last'  => $rentals->url($rentals->lastPage()),
                'next'  => $rentals->nextPageUrl(),
                'prev'  => $rentals->previousPageUrl(),
            ],
        ]);
    }

    /**
     * แปลงเป็นรูปแบบตรงกับ mock/GET_rentals.json ที่ทีม Frontend (Big) อ้างอิงอยู่
     * ปรับตรงนี้จุดเดียวถ้าตกลงชื่อ field ต่างจากนี้กับ Big
     */
    private function transform(Rental $r): array
    {
        return [
            'rt_id'       => $r->rt_id,
            'rt_status'   => $r->rt_status,
            'rt_movein'   => optional($r->rt_movein)->format('Y-m-d'),
            'rt_moveout'  => optional($r->rt_moveout)->format('Y-m-d'),
            'tenant' => $r->tenant ? [
                't_id'    => $r->tenant->t_id,
                't_Fname' => $r->tenant->t_Fname,
                't_Lname' => $r->tenant->t_Lname,
                't_tel'   => $r->tenant->t_tel,
            ] : null,
            'room' => $r->room ? [
                'r_id'     => $r->room->r_id,
                'r_name'   => $r->room->r_name,
                'r_floor'  => $r->room->r_floor,
                'r_type'   => $r->room->r_type,
                'r_status' => $r->room->r_status,
            ] : null,
            'contract' => $r->contract ? [
                'c_number' => $r->contract->c_number,
                'c_status' => $r->contract->c_status,
            ] : null,
            'created_at' => optional($r->created_at)->toIso8601String(),
        ];
    }
}