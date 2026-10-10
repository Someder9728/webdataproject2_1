<?php

namespace App\Http\Controllers;

use App\Actions\Accounts\CreateTenantAccount;
use App\Actions\Accounts\ResetAccountPassword;
use App\Actions\Accounts\SuspendAccount;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AccountManagementController extends Controller
{
    public function create(Request $request, CreateTenantAccount $action): RedirectResponse
    {
        $admin = $this->authorizeAdmin();

        $validated = $request->validate([
            'selectedTenantId' => ['required', 'integer', 'exists:tenants,t_id'],
            'username' => ['required', 'string', 'min:3', 'max:45', 'regex:/\A[a-z0-9._-]+\z/'],
            'password' => ['required', 'string', 'min:12', 'max:72', 'confirmed'],
        ], [
            'selectedTenantId.required' => 'กรุณาเลือกผู้เช่า',
            'selectedTenantId.exists' => 'ไม่พบข้อมูลผู้เช่า',
            'username.required' => 'กรุณากรอก Username',
            'username.min' => 'Username ต้องมีอย่างน้อย 3 ตัวอักษร',
            'username.max' => 'Username ต้องไม่เกิน 45 ตัวอักษร',
            'username.regex' => 'Username ใช้ได้เฉพาะ a-z, 0-9, จุด, ขีดกลาง และขีดล่าง',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
            'password.min' => 'รหัสผ่านต้องมีอย่างน้อย 12 ตัวอักษร',
            'password.max' => 'รหัสผ่านต้องไม่เกิน 72 ตัวอักษร',
            'password.confirmed' => 'ยืนยันรหัสผ่านไม่ตรงกัน',
        ]);

        $tenant = Tenant::whereKey($validated['selectedTenantId'])->firstOrFail();

        $action->handle($admin, $tenant, [
            'u_username' => $validated['username'],
            'password' => $validated['password'],
            'password_confirmation' => $request->input('password_confirmation'),
        ]);

        return redirect()->route('admin.accounts')->with('success', 'สร้างบัญชีผู้เช่าเรียบร้อยแล้ว');
    }

    public function resetPassword(Request $request, int $userId, ResetAccountPassword $action): RedirectResponse
    {
        $admin = $this->authorizeAdmin();
        $target = $this->activeTenantAccount($userId);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:12', 'max:72', 'confirmed'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ], [
            'password.required' => 'กรุณากรอกรหัสผ่านใหม่',
            'password.min' => 'รหัสผ่านต้องมีอย่างน้อย 12 ตัวอักษร',
            'password.max' => 'รหัสผ่านต้องไม่เกิน 72 ตัวอักษร',
            'password.confirmed' => 'ยืนยันรหัสผ่านไม่ตรงกัน',
        ]);

        $action->handle($admin, $target, [
            'password' => $validated['password'],
            'password_confirmation' => $request->input('password_confirmation'),
            'reason' => $validated['reason'] ?? null,
        ]);

        return redirect()->route('admin.accounts')->with(
            'success',
            'ตั้งรหัสผ่านใหม่เรียบร้อยแล้ว บัญชีนี้ต้องเปลี่ยนรหัสผ่านเมื่อเข้าสู่ระบบครั้งถัดไป'
        );
    }

    public function suspend(Request $request, int $userId, SuspendAccount $action): RedirectResponse
    {
        $admin = $this->authorizeAdmin();
        $target = $this->activeTenantAccount($userId);
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $action->handle($admin, $target, $validated['reason'] ?? null);

        return redirect()->route('admin.accounts')->with('success', 'ระงับบัญชีเรียบร้อยแล้ว');
    }

    private function authorizeAdmin(): User
    {
        $admin = Auth::user()?->fresh();

        abort_unless(
            $admin && $admin->is_active && ! $admin->must_change_password && $admin->u_role === 'admin',
            403
        );

        return $admin;
    }

    private function activeTenantAccount(int $userId): User
    {
        $target = User::query()->where('u_role', 'tenant')->where('is_active', true)->whereKey($userId)->first();

        if (! $target) {
            throw ValidationException::withMessages([
                'account' => 'ไม่พบบัญชีผู้เช่าที่ใช้งานอยู่',
            ]);
        }

        return $target;
    }
}
