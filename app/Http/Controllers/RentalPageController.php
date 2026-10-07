<?php

namespace App\Http\Controllers;

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
     * R3 — หน้ารายละเอียดการเช่า (สิทธิ์ข้อมูลจริงตรวจที่ API)
     */
    public function show(int $rental): View
    {
        return view('rentals.show', ['rentalId' => $rental]);
    }
}
