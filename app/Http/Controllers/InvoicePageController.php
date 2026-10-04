<?php

namespace App\Http\Controllers;

class InvoicePageController extends Controller
{
    /**
     * ใบแจ้งหนี้ / การชำระเงิน — รายการ (Admin เห็นทั้งหมด, Tenant เห็นเฉพาะของตัวเอง)
     * สิทธิ์ข้อมูลจริงตรวจที่ API (/api/v1/invoices) หน้านี้เป็นแค่เปลือกที่เรียก API
     */
    public function index()
    {
        return view('invoices.index');
    }

    /**
     * ประวัติการชำระเงิน (ใบแจ้งหนี้ที่ชำระแล้ว)
     */
    public function history()
    {
        return view('invoices.history');
    }

    /**
     * รายละเอียดใบแจ้งหนี้ + สถานะการชำระ + ประวัติการดำเนินการ
     */
    public function show(int $invoice)
    {
        return view('invoices.show', ['invoiceId' => $invoice]);
    }
}
