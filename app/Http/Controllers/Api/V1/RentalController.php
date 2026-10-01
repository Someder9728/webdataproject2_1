<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Actions\Rentals\CreateRental;

class RentalController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Rental::class);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $request->user();

        $query = Rental::query()
            ->with(['tenant', 'room', 'contract']);

        if ($user->u_role === 'tenant') {
            if ($user->tenants_t_id === null) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('tenants_t_id', $user->tenants_t_id);
            }
        }

        $rentals = $query
            ->search(trim($validated['search'] ?? ''))
            ->orderByDesc('created_at')
            ->orderByDesc('rt_id')
            ->paginate(
                (int) ($validated['per_page'] ?? 20),
                ['*'],
                'page',
                (int) ($validated['page'] ?? 1)
            )
            ->appends($request->only(['search', 'per_page']));

        return response()->json([
            'data' => $rentals->getCollection()
                ->map(fn (Rental $r) => $this->transform($r)),
            'message' => 'อ่านรายการการเช่าสำเร็จ',
            'meta' => [
                // คง page ไว้ให้หน้าจอของ Big ใช้งานต่อได้
                'page' => $rentals->currentPage(),
                'current_page' => $rentals->currentPage(),
                'per_page' => $rentals->perPage(),
                'total' => $rentals->total(),
                'last_page' => $rentals->lastPage(),
            ],
            'links' => [
                'first' => $rentals->url(1),
                'last' => $rentals->url($rentals->lastPage()),
                'next' => $rentals->nextPageUrl(),
                'prev' => $rentals->previousPageUrl(),
            ],
        ]);
    }


    public function show(Rental $rental)
    {
        Gate::authorize('view', $rental);

        $rental->load(['tenant', 'room', 'contract']);

        return response()->json([
            'data' => $this->transform($rental),
            'message' => 'อ่านรายละเอียดการเช่าสำเร็จ',
        ]);
    }

    public function showContract(Rental $rental)
    {
        Gate::authorize('view', $rental);

        $contract = $rental->contract()->firstOrFail();

        return response()->json([
            'data' => [
                'c_id' => $contract->getKey(),
                'rentals_rt_id' => $rental->getKey(),
                'c_number' => $contract->c_number,
                'c_start' => $contract->c_start?->format('Y-m-d'),
                'c_end' => $contract->c_end?->format('Y-m-d'),
                'c_rent' => $contract->c_rent,
                'c_deposit' => $contract->c_deposit,
                'c_status' => $contract->c_status,
            ],
            'message' => 'อ่านสัญญาการเช่าสำเร็จ',
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

    public function store(Request $request, CreateRental $action)
    {
        $rental = $action->handle(
            $request->user(),
            $request->only([
                'tenants_t_id',
                'rooms_r_id',
                'c_end',
                'c_rent',
                'c_deposit',
            ])
        );

        return response()->json([
            'data' => $this->transform($rental),
            'message' => 'รับผู้เช่าเข้าพักสำเร็จ',
        ], 201);
    }
}