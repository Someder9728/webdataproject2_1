<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RentalPageController extends Controller
{
    /**
     * R1 — หน้ารายการการเช่า (Admin)
     * แค่คืน view เปล่า ข้อมูลจริงโหลดผ่าน JS fetch ไปที่ /api/v1/rentals
     */
    public function index(): View
    {
        return view('rentals.index');
    }

    /**
     * R2 — หน้าสร้างการเช่า (Move-in + Contract ฟอร์มเดียว)
     */
    public function create(): View
    {
        return view('rentals.create');
    }

    /**
     * R3 — หน้ารายละเอียดการเช่า (Admin + Tenant ใช้ view เดียวกัน สิทธิ์ข้อมูลจริงตรวจที่ API)
     */
    public function show(Request $request, int $rental): View
    {
        if ($request->user()->u_role === 'tenant') {
            $record = Rental::with(['tenant', 'room', 'contract'])->findOrFail($rental);
            Gate::authorize('view', $record);

            return view('tenant.rental', ['rental' => $record]);
        }

        return view('rentals.show', ['rentalId' => $rental]);
    }

    /**
     * ผู้เช่า — การเช่าของฉัน (การเช่าปัจจุบัน + ประวัติ)
     */
    public function mine(Request $request): View
    {
        return view('tenant.rentals', [
            'current' => $this->tenantRentals($request)->where('rt_status', 'ACTIVE')->get(),
            'history' => $this->tenantRentals($request)->where('rt_status', '<>', 'ACTIVE')->orderByDesc('rt_movein')->paginate(20),
        ]);
    }

    /**
     * ผู้เช่า — สัญญาของฉัน
     */
    public function myContracts(Request $request): View
    {
        return view('tenant.contracts', ['rentals' => $this->tenantRentals($request)->whereHas('contract')->orderByDesc('rt_movein')->paginate(20)]);
    }

    /** @return Builder<Rental> */
    private function tenantRentals(Request $request): Builder
    {
        Gate::authorize('viewAny', Rental::class);

        return Rental::with(['room', 'contract'])->where('tenants_t_id', $request->user()->tenants_t_id ?? 0);
    }
}
