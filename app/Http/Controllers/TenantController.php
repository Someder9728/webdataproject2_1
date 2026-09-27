<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    /* แสดงรายการผู้เช่าทั้งหมด */
    public function index(Request $request)
{
    $search = trim((string) $request->input('search'));

    $tenants = Tenant::query()
        ->when($search !== '', function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('t_Fname', 'like', "%{$search}%")
                    ->orWhere('t_Lname', 'like', "%{$search}%")
                    ->orWhere('t_tel', 'like', "%{$search}%")
                    ->orWhere('t_mail', 'like', "%{$search}%");
            });
        })
        ->orderBy('t_id', 'desc')
        ->paginate(20)
        ->withQueryString();

    return view('tenants.index', compact('tenants', 'search'));
}

    /* หน้าเพิ่มผู้เช่า */
    public function create()
    {
        return view('tenants.create');
    }

    /* บันทึกผู้เช่าใหม่ */
    public function store(Request $request)
    {
        $validated = $request->validate([
            't_Fname' => [
                'required',
                'string',
                'max:255',
            ],

            't_Lname' => [
                'required',
                'string',
                'max:255',
            ],

            't_tel' => [
                'required',
                'digits:10',
            ],

            't_mail' => [
                'nullable',
                'email',
                'max:255',
            ],

            't_address' => [
                'nullable',
                'string',
            ],
        ], [
            't_Fname.required' => 'กรุณากรอกชื่อ',
            't_Lname.required' => 'กรุณากรอกนามสกุล',
            't_tel.required' => 'กรุณากรอกเบอร์โทรศัพท์',
            't_tel.digits' => 'เบอร์โทรศัพท์ต้องมี 10 หลัก',
            't_mail.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
        ]);

        Tenant::create($validated);

        return redirect()
            ->route('tenants.index')
            ->with('success', 'เพิ่มข้อมูลผู้เช่าเรียบร้อยแล้ว');
    }

    /* หน้าแก้ไขผู้เช่า */
    public function edit(Tenant $tenant)
    {
        return view('tenants.edit', compact('tenant'));
    }

    /* อัปเดตข้อมูลผู้เช่า */
    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            't_Fname' => [
                'required',
                'string',
                'max:255',
            ],

            't_Lname' => [
                'required',
                'string',
                'max:255',
            ],

            't_tel' => [
                'required',
                'digits:10',
            ],

            't_mail' => [
                'nullable',
                'email',
                'max:255',
            ],

            't_address' => [
                'nullable',
                'string',
            ],
        ], [
            't_Fname.required' => 'กรุณากรอกชื่อ',
            't_Lname.required' => 'กรุณากรอกนามสกุล',
            't_tel.required' => 'กรุณากรอกเบอร์โทรศัพท์',
            't_tel.digits' => 'เบอร์โทรศัพท์ต้องมี 10 หลัก',
            't_mail.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
        ]);

        $tenant->update($validated);

        return redirect()
            ->route('tenants.index')
            ->with('success', 'แก้ไขข้อมูลผู้เช่าเรียบร้อยแล้ว');
    }

    /* ลบผู้เช่า */
    public function destroy(Tenant $tenant)
{
    if ($tenant->user()->exists()) {
        return redirect()
            ->route('tenants.index')
            ->with('error', 'ไม่สามารถลบผู้เช่ารายนี้ได้ เนื่องจากมีบัญชีผู้ใช้งานอยู่');
    }

    $tenant->delete();

    return redirect()
        ->route('tenants.index')
        ->with('success', 'ลบข้อมูลผู้เช่าเรียบร้อยแล้ว');
}
}