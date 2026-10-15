<?php

namespace App\Http\Controllers;

use App\Actions\DeleteUnusedRecord;
use App\Actions\Rooms\SaveRoom;
use App\Models\Invoice;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Room::class);
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'floor' => ['nullable', 'integer', 'min:1'], 'status' => ['nullable', 'in:VACANT,OCCUPIED,MAINTENANCE'], 'page' => ['nullable', 'integer', 'min:1']]);
        $search = trim($filters['search'] ?? '');
        $rooms = Room::query()
            ->with(['rentals' => fn ($query) => $query->where('rt_status', 'ACTIVE')->whereNull('rt_moveout')->with('tenant')])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($filter) use ($search) {
                    $filter->where('r_name', 'like', '%'.$search.'%');
                    $filter->orWhere('r_type', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['floor'] ?? null, fn ($q, $floor) => $q->where('r_floor', $floor))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('r_status', $status))
            ->orderByDesc('r_id')
            ->paginate(20)->withQueryString();

        return view('rooms.index', ['rooms' => $rooms, 'search' => $search, 'filters' => $filters, 'floors' => Room::orderBy('r_floor')->distinct()->pluck('r_floor')]);
    }

    public function history(Room $room): JsonResponse
    {
        Gate::authorize('view', $room);
        $rentals = $room->rentals()->with('tenant')->orderByDesc('rt_id')->limit(50)->get()->map(fn ($r) => ['type' => 'การเช่า', 'date' => ($r->rt_movein ?? $r->scheduled_movein_at)?->toDateString(), 'detail' => trim(($r->tenant->t_Fname ?? '').' '.($r->tenant->t_Lname ?? '')), 'status' => $r->rt_status]);
        $invoices = Invoice::with('payment')->whereHas('rental', fn ($q) => $q->where('rooms_r_id', $room->getKey()))->orderByDesc('i_id')->limit(50)->get()->map(fn ($i) => ['type' => 'ใบแจ้งหนี้', 'date' => $i->i_date->toDateString(), 'detail' => $i->number().' · ฿'.$i->i_total, 'status' => $i->payment->p_status ?? '—']);
        $repairs = $room->repairs()->orderByDesc('rp_id')->limit(50)->get()->map(fn ($r) => ['type' => 'แจ้งซ่อม', 'date' => $r->created_at?->format('Y-m-d'), 'detail' => $r->rp_name, 'status' => $r->rp_status]);

        return response()->json(['data' => $rentals->concat($invoices)->concat($repairs)->sortByDesc('date')->values(), 'message' => 'ประวัติล่าสุดไม่เกิน 50 รายการต่อประเภท']);
    }

    public function create(): View
    {
        return view('rooms.create');
    }

    public function edit(Room $room): View
    {
        return view('rooms.edit', compact('room'));
    }

    public function store(Request $request, SaveRoom $action): RedirectResponse
    {
        $action->handle($request->user(), $request->only(['r_name', 'r_floor', 'r_type', 'r_rent']));

        return redirect()->route('rooms.index')->with('success', 'บันทึกข้อมูลสำเร็จ');
    }

    public function update(Request $request, Room $room, SaveRoom $action): RedirectResponse
    {
        $action->handle($request->user(), $request->only(['r_name', 'r_floor', 'r_type', 'r_rent']), $room);

        return redirect()->route('rooms.index')->with('success', 'แก้ไขข้อมูลสำเร็จ');
    }

    public function destroy(Request $request, Room $room, DeleteUnusedRecord $action): RedirectResponse
    {
        $action->handle($request->user(), $room);

        return redirect()->route('rooms.index')->with('success', 'ลบข้อมูลสำเร็จ');
    }
}
