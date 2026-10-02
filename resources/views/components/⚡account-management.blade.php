<?php

use App\Models\AuditEvent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component
{
    /* CREATE ACCOUNT */
    public $selectedTenantId = '';

    public string $username = '';
    public string $password = '';
    public string $password_confirmation = '';

    /* RESET PASSWORD */
    public $resetUserId = '';

    public string $resetPassword = '';
    public string $resetPassword_confirmation = '';
    public string $resetReason = '';

    /* SUSPEND */
    public $suspendUserId = '';

    public string $suspendReason = '';

    /* INITIAL LOAD */
    public function mount(): void
    {
        $this->authorizeAdmin();
    }

    /* ADMIN CHECK */
    protected function authorizeAdmin(): User
    {
        $admin = Auth::user()?->fresh();

        abort_unless(
            $admin &&
            $admin->is_active &&
            ! $admin->must_change_password &&
            $admin->u_role === 'admin',
            403
        );

        return $admin;
    }

    /* CREATE TENANT ACCOUNT */
    public function createAccount(): void
    {
        $admin = $this->authorizeAdmin();

        $validated = $this->validate([
            'selectedTenantId' => [
                'required',
                'integer',
                'exists:tenants,t_id',
            ],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:45',
                'regex:/\A[a-z0-9._-]+\z/',
                'unique:users,u_username',
            ],
            'password' => [
                'required',
                'string',
                'min:12',
                'max:72',
                'confirmed',
            ],
        ], [
            'selectedTenantId.required' => 'กรุณาเลือกผู้เช่า',
            'selectedTenantId.exists' => 'ไม่พบข้อมูลผู้เช่า',

            'username.required' => 'กรุณากรอก Username',
            'username.min' => 'Username ต้องมีอย่างน้อย 3 ตัวอักษร',
            'username.max' => 'Username ต้องไม่เกิน 45 ตัวอักษร',
            'username.regex' => 'Username ใช้ได้เฉพาะ a-z, 0-9, จุด, ขีดกลาง และขีดล่าง',
            'username.unique' => 'Username นี้ถูกใช้งานแล้ว',

            'password.required' => 'กรุณากรอกรหัสผ่าน',
            'password.min' => 'รหัสผ่านต้องมีอย่างน้อย 12 ตัวอักษร',
            'password.max' => 'รหัสผ่านต้องไม่เกิน 72 ตัวอักษร',
            'password.confirmed' => 'ยืนยันรหัสผ่านไม่ตรงกัน',
        ]);

        if (strlen($validated['password']) > 72) {
            throw ValidationException::withMessages([
                'password' => 'รหัสผ่านต้องไม่เกิน 72 ไบต์',
            ]);
        }

        DB::transaction(function () use ($admin, $validated) {

            $tenant = Tenant::findOrFail(
                $validated['selectedTenantId']
            );

            $existingAccount = User::withTrashed()
                ->where('tenants_t_id', $tenant->getKey())
                ->exists();

            if ($existingAccount) {
                throw ValidationException::withMessages([
                    'selectedTenantId' => 'ผู้เช่ารายนี้มีบัญชีอยู่แล้ว',
                ]);
            }

            $user = new User();

            $user->u_username = Str::lower(
                trim($validated['username'])
            );

            $user->u_password = $validated['password'];
            $user->u_role = 'tenant';
            $user->tenants_t_id = $tenant->getKey();
            $user->is_active = true;
            $user->must_change_password = true;

            $user->save();

            AuditEvent::create([
                'actor_user_id' => $admin->getKey(),
                'entity_type' => 'users',
                'entity_id' => $user->getKey(),
                'action' => 'tenant_account_created',
                'new_values' => [
                    'u_username' => $user->u_username,
                    'u_role' => $user->u_role,
                    'tenants_t_id' => $user->tenants_t_id,
                    'is_active' => $user->is_active,
                    'must_change_password' => $user->must_change_password,
                ],
            ]);
        });

        $this->reset([
            'selectedTenantId',
            'username',
            'password',
            'password_confirmation',
        ]);

        session()->flash(
            'success',
            'สร้างบัญชีผู้เช่าเรียบร้อยแล้ว'
        );
    }

    /* RESET PASSWORD */
    public function resetAccountPassword(): void
    {
        $admin = $this->authorizeAdmin();

        $validated = $this->validate([
            'resetUserId' => [
                'required',
                'integer',
                'exists:users,u_id',
            ],
            'resetPassword' => [
                'required',
                'string',
                'min:12',
                'max:72',
                'confirmed',
            ],
            'resetReason' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ], [
            'resetUserId.required' => 'กรุณาเลือกบัญชี',
            'resetUserId.exists' => 'ไม่พบบัญชี',

            'resetPassword.required' => 'กรุณากรอกรหัสผ่านใหม่',
            'resetPassword.min' => 'รหัสผ่านต้องมีอย่างน้อย 12 ตัวอักษร',
            'resetPassword.max' => 'รหัสผ่านต้องไม่เกิน 72 ตัวอักษร',
            'resetPassword.confirmed' => 'ยืนยันรหัสผ่านไม่ตรงกัน',
        ]);

        if (strlen($validated['resetPassword']) > 72) {
            throw ValidationException::withMessages([
                'resetPassword' => 'รหัสผ่านต้องไม่เกิน 72 ไบต์',
            ]);
        }

        DB::transaction(function () use ($admin, $validated) {

            $target = User::findOrFail(
                $validated['resetUserId']
            );

            if ($target->u_role !== 'tenant') {
                throw ValidationException::withMessages([
                    'resetUserId' => 'สามารถ Reset ได้เฉพาะบัญชี Tenant',
                ]);
            }

            if (
                Hash::check(
                    $validated['resetPassword'],
                    $target->u_password
                )
            ) {
                throw ValidationException::withMessages([
                    'resetPassword' => 'รหัสผ่านใหม่ต้องต่างจากรหัสเดิม',
                ]);
            }

            $oldValues = [
                'must_change_password' => $target->must_change_password,
            ];

            $target->u_password = $validated['resetPassword'];
            $target->must_change_password = true;
            $target->remember_token = Str::random(60);
            $target->save();

            DB::table('sessions')
                ->where('user_id', $target->getKey())
                ->delete();

            AuditEvent::create([
                'actor_user_id' => $admin->getKey(),
                'entity_type' => 'users',
                'entity_id' => $target->getKey(),
                'action' => 'account_password_reset',
                'old_values' => $oldValues,
                'new_values' => [
                    'must_change_password' => true,
                ],
                'reason' => $validated['resetReason'] ?? null,
            ]);
        });

        $this->reset([
            'resetUserId',
            'resetPassword',
            'resetPassword_confirmation',
            'resetReason',
        ]);

        session()->flash(
            'success',
            'Reset รหัสผ่านเรียบร้อยแล้ว บัญชีนี้ต้องเปลี่ยนรหัสผ่านเมื่อเข้าสู่ระบบครั้งถัดไป'
        );
    }

    /* SUSPEND ACCOUNT */
    public function suspendAccount(): void
    {
        $admin = $this->authorizeAdmin();

        $validated = $this->validate([
            'suspendUserId' => [
                'required',
                'integer',
                'exists:users,u_id',
            ],
            'suspendReason' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ], [
            'suspendUserId.required' => 'กรุณาเลือกบัญชี',
            'suspendUserId.exists' => 'ไม่พบบัญชี',
        ]);

        DB::transaction(function () use ($admin, $validated) {

            $target = User::findOrFail(
                $validated['suspendUserId']
            );

            if (! $target->is_active) {
                throw ValidationException::withMessages([
                    'suspendUserId' => 'บัญชีนี้ถูกระงับอยู่แล้ว',
                ]);
            }

            if ($target->u_role === 'admin') {

                $otherActiveAdminExists = User::query()
                    ->where('u_role', 'admin')
                    ->where('is_active', true)
                    ->where('u_id', '!=', $target->getKey())
                    ->exists();

                if (! $otherActiveAdminExists) {
                    throw ValidationException::withMessages([
                        'suspendUserId' =>
                            'ไม่สามารถระงับ Admin ที่ใช้งานได้คนสุดท้าย',
                    ]);
                }
            }

            $target->is_active = false;
            $target->remember_token = Str::random(60);
            $target->save();

            DB::table('sessions')
                ->where('user_id', $target->getKey())
                ->delete();

            AuditEvent::create([
                'actor_user_id' => $admin->getKey(),
                'entity_type' => 'users',
                'entity_id' => $target->getKey(),
                'action' => 'account_suspended',
                'old_values' => [
                    'is_active' => true,
                ],
                'new_values' => [
                    'is_active' => false,
                ],
                'reason' => $validated['suspendReason'] ?? null,
            ]);
        });

        $this->reset([
            'suspendUserId',
            'suspendReason',
        ]);

        session()->flash(
            'success',
            'ระงับบัญชีเรียบร้อยแล้ว'
        );
    }

    /* DATA */
    public function render()
    {
        $this->authorizeAdmin();

        $tenants = Tenant::query()
            ->with('user')
            ->orderBy('t_id', 'desc')
            ->get();

        $accounts = User::query()
            ->with('tenant')
            ->where('u_role', 'tenant')
            ->orderBy('u_id', 'desc')
            ->get();

        return view('components.⚡account-management', [
            'tenants' => $tenants,
            'accounts' => $accounts,
        ]);
    }
};
?>

<div class="container-fluid py-4">

    {{-- HEADER --}}
    <div class="mb-4">
        <h1 class="fw-bold mb-1">จัดการบัญชีผู้เช่า</h1>
        <p class="text-secondary mb-0">
            สร้างบัญชี รีเซ็ตรหัสผ่าน และระงับบัญชีผู้เช่า
        </p>
    </div>

    {{-- SUCCESS --}}
    @if (session()->has('success'))
    <div class="alert alert-success border-0 shadow-sm">
        {{ session('success') }}
    </div>
    @endif

    {{-- VALIDATION --}}
    @if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm">
        <div class="fw-semibold mb-1">
            กรุณาตรวจสอบข้อมูล
        </div>

        <ul class="mb-0">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif


    {{-- CREATE ACCOUNT --}}

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-1">
                สร้างบัญชีผู้เช่า
            </h5>

            <small class="text-secondary">
                บัญชีใหม่จะต้องเปลี่ยนรหัสผ่านเมื่อเข้าสู่ระบบครั้งแรก
            </small>
        </div>

        <div class="card-body">

            <form wire:submit="createAccount">

                <div class="row g-3">

                    {{-- TENANT --}}
                    <div class="col-md-6">

                        <label class="form-label fw-semibold">
                            ผู้เช่า
                        </label>

                        <select class="form-select @error('selectedTenantId') is-invalid @enderror"
                            wire:model="selectedTenantId">
                            <option value="">
                                -- เลือกผู้เช่า --
                            </option>

                            @foreach ($tenants as $tenant)
                            @if (! $tenant->user)
                            <option value="{{ $tenant->t_id }}">
                                {{ $tenant->t_Fname }}
                                {{ $tenant->t_Lname }}
                                — {{ $tenant->t_tel }}
                            </option>
                            @endif
                            @endforeach
                        </select>

                        @error('selectedTenantId')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror

                    </div>


                    {{-- USERNAME --}}
                    <div class="col-md-6">

                        <label class="form-label fw-semibold">
                            Username
                        </label>

                        <input type="text" class="form-control @error('username') is-invalid @enderror"
                            wire:model="username" placeholder="เช่น tenant001">

                        @error('username')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror

                    </div>


                    {{-- PASSWORD --}}
                    <div class="col-md-6">

                        <label class="form-label fw-semibold">
                            รหัสผ่าน
                        </label>

                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                            wire:model="password">

                        @error('password')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror

                        <div class="form-text">
                            อย่างน้อย 12 ตัวอักษร
                        </div>

                    </div>


                    {{-- CONFIRM PASSWORD --}}
                    <div class="col-md-6">

                        <label class="form-label fw-semibold">
                            ยืนยันรหัสผ่าน
                        </label>

                        <input type="password" class="form-control" wire:model="password_confirmation">

                    </div>

                </div>


                <div class="mt-4">

                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>
                            สร้างบัญชี
                        </span>

                        <span wire:loading>
                            กำลังสร้าง...
                        </span>
                    </button>

                </div>

            </form>

        </div>
    </div>


    {{-- EXISTING ACCOUNTS --}}

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0">
                บัญชีผู้เช่า
            </h5>
        </div>

        <div class="card-body p-0">

            @if ($accounts->isEmpty())

            <div class="text-center text-secondary py-5">
                ยังไม่มีบัญชีผู้เช่า
            </div>

            @else

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">

                        <tr>
                            <th>Username</th>
                            <th>ผู้เช่า</th>
                            <th>สถานะ</th>
                            <th>การเปลี่ยนรหัส</th>
                            <th class="text-end">จัดการ</th>
                        </tr>

                    </thead>

                    <tbody>

                        @foreach ($accounts as $account)

                        <tr>

                            <td class="fw-semibold">
                                {{ $account->u_username }}
                            </td>

                            <td>
                                @if ($account->tenant)
                                {{ $account->tenant->t_Fname }}
                                {{ $account->tenant->t_Lname }}
                                @else
                                -
                                @endif
                            </td>

                            <td>

                                @if ($account->is_active)

                                <span class="badge text-bg-success">
                                    ใช้งาน
                                </span>

                                @else

                                <span class="badge text-bg-secondary">
                                    ระงับ
                                </span>

                                @endif

                            </td>

                            <td>

                                @if ($account->must_change_password)

                                <span class="badge text-bg-warning">
                                    ต้องเปลี่ยน
                                </span>

                                @else

                                <span class="badge text-bg-success">
                                    เรียบร้อย
                                </span>

                                @endif

                            </td>

                            <td class="text-end">

                                @if ($account->is_active)

                                <button type="button" class="btn btn-sm btn-outline-primary me-1"
                                    wire:click="$set('resetUserId', {{ $account->u_id }})">
                                    Reset Password
                                </button>

                                <button type="button" class="btn btn-sm btn-outline-danger"
                                    wire:click="$set('suspendUserId', {{ $account->u_id }})">
                                    ระงับ
                                </button>

                                @else

                                <span class="text-secondary">
                                    ไม่มีการจัดการ
                                </span>

                                @endif

                            </td>

                        </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

            @endif

        </div>
    </div>


    {{-- RESET PASSWORD --}}

    @if ($resetUserId)

    <div class="card border-0 shadow-sm mt-4">

        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0">
                Reset Password
            </h5>
        </div>

        <div class="card-body">

            <form wire:submit="resetAccountPassword">

                <div class="mb-3">

                    <label class="form-label fw-semibold">
                        รหัสผ่านใหม่
                    </label>

                    <input type="password" class="form-control" wire:model="resetPassword">

                </div>

                <div class="mb-3">

                    <label class="form-label fw-semibold">
                        ยืนยันรหัสผ่านใหม่
                    </label>

                    <input type="password" class="form-control" wire:model="resetPassword_confirmation">

                </div>

                <div class="mb-3">

                    <label class="form-label fw-semibold">
                        เหตุผล
                    </label>

                    <textarea class="form-control" rows="3" wire:model="resetReason"></textarea>

                </div>

                <button type="submit" class="btn btn-primary me-2" wire:loading.attr="disabled">
                    Reset Password
                </button>

                <button type="button" class="btn btn-outline-secondary" wire:click="$set('resetUserId', '')">
                    ยกเลิก
                </button>

            </form>

        </div>
    </div>

    @endif


    {{-- SUSPEND --}}

    @if ($suspendUserId)

    <div class="card border-0 shadow-sm mt-4">

        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0 text-danger">
                ระงับบัญชี
            </h5>
        </div>

        <div class="card-body">

            <form wire:submit="suspendAccount">

                <div class="mb-3">

                    <label class="form-label fw-semibold">
                        เหตุผล
                    </label>

                    <textarea class="form-control" rows="3" wire:model="suspendReason"
                        placeholder="ระบุเหตุผล (ถ้ามี)"></textarea>

                </div>

                <div class="alert alert-warning">
                    เมื่อระงับบัญชีแล้ว ผู้ใช้จะไม่สามารถเข้าสู่ระบบได้
                </div>

                <button type="submit" class="btn btn-danger me-2" wire:loading.attr="disabled">
                    ยืนยันระงับบัญชี
                </button>

                <button type="button" class="btn btn-outline-secondary" wire:click="$set('suspendUserId', '')">
                    ยกเลิก
                </button>

            </form>

        </div>
    </div>

    @endif

</div>