<?php

namespace App\Http\Controllers;

use App\Actions\DeleteUnusedRecord;
use App\Actions\Rooms\SaveRoom;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $rooms = Room::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($filter) use ($search) {
                    $filter->where('r_name', 'like', '%'.$search.'%');
                    $filter->orWhere('r_type', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('r_id')
            ->paginate(20)->withQueryString();

        return view('rooms.index', compact('rooms', 'search'));
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
