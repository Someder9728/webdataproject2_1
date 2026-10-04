<?php

namespace App\Http\Controllers;

class RentalPageController extends Controller
{
    /**
     * R1 — หน้ารายการการเช่า (Admin)
     * แค่คืน view เปล่า ข้อมูลจริงโหลดผ่าน JS fetch ไปที่ /api/v1/rentals
     */
    public function index()
    {
        return view('rentals.index');
    }
}