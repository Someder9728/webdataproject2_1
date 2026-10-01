<?php

namespace App\Http\Controllers;

class RentalPageController extends Controller
{
    /**
     * R1 — หน้ารายการการเช่า (Admin)
     */
    public function index()
    {
        return view('rentals.index');
    }

    /**
     * R2 — หน้าสร้างการเช่า (Move-in + Contract ฟอร์มเดียว)
     */
    public function create()
    {
        return view('rentals.create');
    }

    /**
     * R3 — หน้ารายละเอียดการเช่า (Admin + Tenant ใช้ view เดียวกัน แยกสิทธิ์ที่ API)
     */
    public function show(int $rental)
    {
        return view('rentals.show', ['rentalId' => $rental]);
    }
}