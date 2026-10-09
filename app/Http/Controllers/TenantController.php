<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        $validated = $this->validateTenantFields($request);

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
        $validated = $this->validateTenantFields($request);

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

    /** @return array<string, mixed> */
    private function validateTenantFields(Request $request): array
    {
        $fields = ['t_Fname', 't_Lname', 't_tel', 't_mail', 't_address'];
        $data = [];

        foreach ($request->only($fields) as $field => $value) {
            $value = is_string($value) ? trim($value) : $value;
            $data[$field] = $value === '' ? null : $value;
        }

        return Validator::make($data, [
            't_Fname' => ['required', 'string', 'max:255'],
            't_Lname' => ['required', 'string', 'max:255'],
            't_tel' => ['required', 'string', 'regex:/\A[0-9]{10}\z/'],
            't_mail' => ['nullable', 'string', 'email', 'max:255'],
            't_address' => ['nullable', 'string', 'max:2000'],
        ], [
            't_Fname.required' => 'กรุณากรอกชื่อ',
            't_Fname.string' => 'ชื่อต้องเป็นข้อความ',
            't_Fname.max' => 'ชื่อต้องไม่เกิน 255 ตัวอักษร',
            't_Lname.required' => 'กรุณากรอกนามสกุล',
            't_Lname.string' => 'นามสกุลต้องเป็นข้อความ',
            't_Lname.max' => 'นามสกุลต้องไม่เกิน 255 ตัวอักษร',
            't_tel.required' => 'กรุณากรอกเบอร์โทรศัพท์',
            't_tel.string' => 'เบอร์โทรศัพท์ไม่ถูกต้อง',
            't_tel.regex' => 'เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลัก',
            't_mail.string' => 'อีเมลไม่ถูกต้อง',
            't_mail.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            't_mail.max' => 'อีเมลต้องไม่เกิน 255 ตัวอักษร',
            't_address.string' => 'ที่อยู่ต้องเป็นข้อความ',
            't_address.max' => 'ที่อยู่ต้องไม่เกิน 2,000 ตัวอักษร',
        ])->validate();
    }
}
