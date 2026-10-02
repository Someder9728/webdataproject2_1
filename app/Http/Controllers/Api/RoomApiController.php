<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomApiController extends Controller
{
    /* แสดงรายการห้องพักทั้งหมด */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));

        $perPage = min(
            max((int) $request->input('per_page', 20), 1),
            100
        );

        $rooms = Room::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('r_name', 'like', "%{$search}%")
                        ->orWhere('r_type', 'like', "%{$search}%")
                        ->orWhere('r_status', 'like', "%{$search}%");
                });
            })
            ->orderBy('r_name')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $rooms->items(),
            'pagination' => [
                'current_page' => $rooms->currentPage(),
                'last_page' => $rooms->lastPage(),
                'per_page' => $rooms->perPage(),
                'total' => $rooms->total(),
            ],
        ]);
    }

    /* แสดงข้อมูลห้องพัก 1 ห้อง */
    public function show(Room $room)
    {
        return response()->json([
            'success' => true,
            'data' => $room,
        ]);
    }

    /* เพิ่มห้องพัก */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'r_name' => [
                'required',
                'string',
                'max:20',
                'unique:rooms,r_name',
            ],
            'r_floor' => [
                'required',
                'integer',
                'min:1',
            ],
            'r_type' => [
                'required',
                'string',
                'max:100',
            ],
            'r_rent' => [
                'required',
                'numeric',
                'min:0',
            ],
            'r_status' => [
                'required',
                'in:ว่าง,มีผู้พัก,ปิดปรับปรุง',
            ],
        ]);

        $room = Room::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มข้อมูลห้องพักเรียบร้อยแล้ว',
            'data' => $room,
        ], 201);
    }

    /* แก้ไขห้องพัก */
    public function update(Request $request, Room $room)
    {
        $validated = $request->validate([
            'r_name' => [
                'required',
                'string',
                'max:20',
                'unique:rooms,r_name,' . $room->r_id . ',r_id',
            ],
            'r_floor' => [
                'required',
                'integer',
                'min:1',
            ],
            'r_type' => [
                'required',
                'string',
                'max:100',
            ],
            'r_rent' => [
                'required',
                'numeric',
                'min:0',
            ],
            'r_status' => [
                'required',
                'in:ว่าง,มีผู้พัก,ปิดปรับปรุง',
            ],
        ]);

        $room->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'แก้ไขข้อมูลห้องพักเรียบร้อยแล้ว',
            'data' => $room,
        ]);
    }

    /* ลบห้องพัก */
    public function destroy(Room $room)
    {
        $room->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบข้อมูลห้องพักเรียบร้อยแล้ว',
        ]);
    }
}