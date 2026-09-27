<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    /* แสดงรายการห้องพัก */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));

        $rooms = Room::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('r_number', 'like', "%{$search}%")
                        ->orWhere('r_type', 'like', "%{$search}%")
                        ->orWhere('r_status', 'like', "%{$search}%");
                });
            })
            ->orderBy('r_number')
            ->paginate(20)
            ->withQueryString();

        return view('rooms.index', compact('rooms', 'search'));
    }

    /* หน้าเพิ่มห้องพัก */
    public function create()
    {
        return view('rooms.create');
    }

    /* บันทึกห้องพักใหม่ */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'r_number' => [
                'required',
                'string',
                'max:20',
                'unique:rooms,r_number',
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

            'r_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'r_status' => [
                'required',
                'in:ว่าง,มีผู้พัก,ปิดปรับปรุง',
            ],
        ], [
            'r_number.required' => 'กรุณากรอกเลขห้อง',
            'r_number.unique' => 'เลขห้องนี้มีอยู่แล้ว',

            'r_floor.required' => 'กรุณากรอกชั้น',
            'r_floor.integer' => 'ชั้นต้องเป็นตัวเลข',

            'r_type.required' => 'กรุณาเลือกประเภทห้อง',

            'r_price.required' => 'กรุณากรอกราคาห้อง',
            'r_price.numeric' => 'ราคาห้องต้องเป็นตัวเลข',

            'r_status.required' => 'กรุณาเลือกสถานะห้อง',
        ]);

        Room::create($validated);

        return redirect()
            ->route('rooms.index')
            ->with('success', 'เพิ่มข้อมูลห้องพักเรียบร้อยแล้ว');
    }

    /* หน้าแก้ไขห้องพัก */
    public function edit(Room $room)
    {
        return view('rooms.edit', compact('room'));
    }

    /* อัปเดตข้อมูลห้องพัก */
    public function update(Request $request, Room $room)
    {
        $validated = $request->validate([
            'r_number' => [
                'required',
                'string',
                'max:20',
                'unique:rooms,r_number,' . $room->r_id . ',r_id',
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

            'r_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'r_status' => [
                'required',
                'in:ว่าง,มีผู้พัก,ปิดปรับปรุง',
            ],
        ], [
            'r_number.required' => 'กรุณากรอกเลขห้อง',
            'r_number.unique' => 'เลขห้องนี้มีอยู่แล้ว',

            'r_floor.required' => 'กรุณากรอกชั้น',
            'r_floor.integer' => 'ชั้นต้องเป็นตัวเลข',

            'r_type.required' => 'กรุณาเลือกประเภทห้อง',

            'r_price.required' => 'กรุณากรอกราคาห้อง',
            'r_price.numeric' => 'ราคาห้องต้องเป็นตัวเลข',

            'r_status.required' => 'กรุณาเลือกสถานะห้อง',
        ]);

        $room->update($validated);

        return redirect()
            ->route('rooms.index')
            ->with('success', 'แก้ไขข้อมูลห้องพักเรียบร้อยแล้ว');
    }

    /* ลบห้องพัก */
    public function destroy(Room $room)
    {
        $room->delete();

        return redirect()
            ->route('rooms.index')
            ->with('success', 'ลบข้อมูลห้องพักเรียบร้อยแล้ว');
    }
}