<?php

use App\Actions\Accounts\CreateTenantAccount;
use App\Actions\Accounts\ResetAccountPassword;
use App\Actions\Accounts\SuspendAccount;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component
{
    public $selectedTenantId = '';
    public string $username = '';
    public string $password = '';
    public string $password_confirmation = '';

    public $resetUserId = '';
    public string $resetPassword = '';
    public string $resetPassword_confirmation = '';
    public string $resetReason = '';

    public $suspendUserId = '';
    public string $suspendReason = '';

    public function mount(): void
    {
        $this->authorizeAdmin();

        $resetId = request()->integer('reset');
        if ($resetId > 0) {
            $this->resetUserId = User::query()
                ->where('u_role', 'tenant')
                ->where('is_active', true)
                ->whereKey($resetId)
                ->value('u_id') ?? '';
        }

        $suspendId = request()->integer('suspend');
        if ($suspendId > 0) {
            $this->suspendUserId = User::query()
                ->where('u_role', 'tenant')
                ->where('is_active', true)
                ->whereKey($suspendId)
                ->value('u_id') ?? '';
        }
    }

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

    public function openPasswordReset(int $userId): void
    {
        $this->authorizeAdmin();

        $target = User::query()
            ->where('u_role', 'tenant')
            ->where('is_active', true)
            ->findOrFail($userId);

        $this->resetValidation();
        $this->resetUserId = $target->u_id;
        $this->resetPassword = '';
        $this->resetPassword_confirmation = '';
        $this->resetReason = '';
    }

    public function closePasswordReset(): void
    {
        $this->resetValidation();
        $this->reset([
            'resetUserId',
            'resetPassword',
            'resetPassword_confirmation',
            'resetReason',
        ]);
    }

    public function openAccountSuspension(int $userId): void
    {
        $this->authorizeAdmin();

        $target = User::query()
            ->where('u_role', 'tenant')
            ->where('is_active', true)
            ->findOrFail($userId);

        $this->resetValidation();
        $this->suspendUserId = $target->u_id;
        $this->suspendReason = '';
    }

    public function closeAccountSuspension(): void
    {
        $this->resetValidation();
        $this->reset([
            'suspendUserId',
            'suspendReason',
        ]);
    }

    public function createAccount(
        CreateTenantAccount $action
    ): void {
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
            ],
            'password' => [
                'required',
                'string',
                'min:12',
                'max:72',
                'confirmed',
            ],
        ], [
            'selectedTenantId.required' =>
                'กรุณาเลือกผู้เช่า',
            'selectedTenantId.exists' =>
                'ไม่พบข้อมูลผู้เช่า',
            'username.required' =>
                'กรุณากรอก Username',
            'username.min' =>
                'Username ต้องมีอย่างน้อย 3 ตัวอักษร',
            'username.max' =>
                'Username ต้องไม่เกิน 45 ตัวอักษร',
            'username.regex' =>
                'Username ใช้ได้เฉพาะ a-z, 0-9, จุด, ขีดกลาง และขีดล่าง',
            'password.required' =>
                'กรุณากรอกรหัสผ่าน',
            'password.min' =>
                'รหัสผ่านต้องมีอย่างน้อย 12 ตัวอักษร',
            'password.max' =>
                'รหัสผ่านต้องไม่เกิน 72 ตัวอักษร',
            'password.confirmed' =>
                'ยืนยันรหัสผ่านไม่ตรงกัน',
        ]);

        if (strlen($validated['password']) > 72) {
            throw ValidationException::withMessages([
                'password' =>
                    'รหัสผ่านต้องไม่เกิน 72 ไบต์',
            ]);
        }

        $tenant = Tenant::findOrFail(
            $validated['selectedTenantId']
        );

        $action->handle(
            $admin,
            $tenant,
            [
                'u_username' => $validated['username'],
                'password' => $validated['password'],
                'password_confirmation' => $this->password_confirmation,
            ]
        );

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

    public function resetAccountPassword(
        ResetAccountPassword $action
    ): void {
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
            'resetUserId.required' =>
                'กรุณาเลือกบัญชี',
            'resetUserId.exists' =>
                'ไม่พบบัญชี',
            'resetPassword.required' =>
                'กรุณากรอกรหัสผ่านใหม่',
            'resetPassword.min' =>
                'รหัสผ่านต้องมีอย่างน้อย 12 ตัวอักษร',
            'resetPassword.max' =>
                'รหัสผ่านต้องไม่เกิน 72 ตัวอักษร',
            'resetPassword.confirmed' =>
                'ยืนยันรหัสผ่านไม่ตรงกัน',
        ]);

        if (strlen($validated['resetPassword']) > 72) {
            throw ValidationException::withMessages([
                'resetPassword' =>
                    'รหัสผ่านต้องไม่เกิน 72 ไบต์',
            ]);
        }

        $target = User::findOrFail(
            $validated['resetUserId']
        );

        if ($target->u_role !== 'tenant') {
            throw ValidationException::withMessages([
                'resetUserId' =>
                    'สามารถ Reset ได้เฉพาะบัญชี Tenant',
            ]);
        }

        $action->handle(
            $admin,
            $target,
            [
                'password' => $validated['resetPassword'],
                'password_confirmation' => $this->resetPassword_confirmation,
                'reason' => $validated['resetReason'] ?? null,
            ]
        );

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

    public function suspendAccount(
        SuspendAccount $action
    ): void {
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
            'suspendUserId.required' =>
                'กรุณาเลือกบัญชี',
            'suspendUserId.exists' =>
                'ไม่พบบัญชี',
        ]);

        $target = User::findOrFail(
            $validated['suspendUserId']
        );

        if ($target->u_role !== 'tenant') {
            throw ValidationException::withMessages([
                'suspendUserId' =>
                    'สามารถระงับได้เฉพาะบัญชี Tenant',
            ]);
        }

        $action->handle(
            $admin,
            $target,
            $validated['suspendReason'] ?? null
        );

        $this->reset([
            'suspendUserId',
            'suspendReason',
        ]);

        session()->flash(
            'success',
            'ระงับบัญชีเรียบร้อยแล้ว'
        );
    }

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

        return view(
            'components.⚡account-management',
            [
                'tenants' => $tenants,
                'accounts' => $accounts,
            ]
        );
    }
};
?>

<style>
    .account-management-page { max-width: 1500px; margin: 0 auto; padding: 28px 24px; }
    .account-page-eyebrow { color:#74859c; font-size:13px; margin-bottom:5px; }
    .account-page-title { color:#172b4d; font-size:28px; font-weight:750; margin:0 0 5px; }
    .account-page-subtitle { color:#8292aa; margin:0; }
    .account-stat-card, .account-panel { background:#fff; border:1px solid #e1e8f0; border-radius:14px; box-shadow:0 3px 10px rgba(15,23,42,.04); }
    .account-stat-card { min-height:112px; padding:18px 20px; }
    .account-stat-label { color:#8292aa; font-size:12px; }
    .account-stat-value { color:#172b4d; font-size:25px; font-weight:700; margin-top:4px; }
    .account-panel { overflow:hidden; }
    .account-panel-header { align-items:center; border-bottom:1px solid #e9eef4; display:flex; gap:12px; padding:17px 20px; }
    .account-panel-icon { align-items:center; background:#edf4ff; border-radius:10px; color:#2168f3; display:flex; flex:none; height:40px; justify-content:center; width:40px; }
    .account-panel-title { color:#172b4d; font-size:17px; font-weight:700; margin:0 0 3px; }
    .account-panel-subtitle { color:#8292aa; font-size:12px; margin:0; }
    .account-panel-body { padding:20px; }
    .account-management-page .form-label { color:#334155; font-size:13px; }
    .account-management-page .form-control, .account-management-page .form-select { min-height:43px; border-color:#d7e0eb; border-radius:9px; }
    .account-management-page .form-control:focus, .account-management-page .form-select:focus { border-color:#78a8ff; box-shadow:0 0 0 .2rem rgba(33,104,243,.12); }
    .account-management-page .btn { border-radius:8px; }
    .account-table { margin:0; }
    .account-table thead th { background:#f8fafc; border-bottom:1px solid #e5ebf2; color:#718096; font-size:12px; font-weight:600; padding:13px 16px; white-space:nowrap; }
    .account-table tbody td { color:#334155; font-size:13px; padding:14px 16px; }
    .account-user-cell { font-weight:650; color:#172b4d !important; }
    .account-status { border-radius:999px; display:inline-flex; font-size:11px; font-weight:600; padding:5px 10px; }
    .account-status.active { background:#ecfdf5; color:#047857; }
    .account-status.suspended { background:#f1f5f9; color:#64748b; }
    .account-action-overlay { align-items:center; background:rgba(15,23,42,.52); display:grid; inset:0; padding:18px; position:fixed; z-index:2000; }
    .account-action-dialog { background:#fff; border:1px solid #e1e8f0; border-radius:16px; box-shadow:0 24px 70px rgba(15,23,42,.24); margin:auto; max-height:90vh; max-width:560px; overflow:auto; width:100%; }
    .account-dialog-header { align-items:flex-start; border-bottom:1px solid #e9eef4; display:flex; justify-content:space-between; padding:19px 22px; }
    .account-dialog-body { padding:22px; }
    @media (max-width:767.98px) { .account-management-page { padding:20px 14px; } .account-page-title { font-size:23px; } .account-panel-body { padding:16px; } }
</style>

<main class="account-management-page">

    <div class="mb-4">
        <div class="account-page-eyebrow">ตั้งค่าผู้ใช้ / บัญชี</div>
        <h1 class="account-page-title">จัดการบัญชีผู้เช่า</h1>
        <p class="account-page-subtitle">สร้างบัญชี ตั้งรหัสผ่านใหม่ และจัดการสถานะบัญชี</p>
    </div>

    @if (session()->has('success'))
    <div class="alert alert-success border-0 shadow-sm">
        {{ session('success') }}
    </div>
    @endif

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

    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="account-stat-card"><div class="account-stat-label">บัญชีทั้งหมด</div><div class="account-stat-value">{{ $accounts->count() }}</div></div>
        </div>
        <div class="col-sm-4">
            <div class="account-stat-card"><div class="account-stat-label">กำลังใช้งาน</div><div class="account-stat-value text-success">{{ $accounts->where('is_active', true)->count() }}</div></div>
        </div>
        <div class="col-sm-4">
            <div class="account-stat-card"><div class="account-stat-label">ระงับบัญชี</div><div class="account-stat-value text-secondary">{{ $accounts->where('is_active', false)->count() }}</div></div>
        </div>
    </div>

    <section class="account-panel mb-4">
        <div class="account-panel-header">
            <div class="account-panel-icon"><i class="bi bi-person-plus"></i></div>
            <div><h2 class="account-panel-title">
                สร้างบัญชีผู้เช่า
            </h2>
            <p class="account-panel-subtitle">
                บัญชีใหม่จะต้องเปลี่ยนรหัสผ่านเมื่อเข้าสู่ระบบครั้งแรก
            </p></div>
        </div>

        <div class="account-panel-body">
            <form method="POST" action="{{ route('admin.accounts.create') }}"> @csrf
                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            ผู้เช่า
                        </label>

                        <select class="form-select @error('selectedTenantId') is-invalid @enderror"
                            name="selectedTenantId" required>
                            <option value="" @selected(old('selectedTenantId') == '')>
                                -- เลือกผู้เช่า --
                            </option>

                            @foreach ($tenants as $tenant)
                            @if (! $tenant->user)
                            <option value="{{ $tenant->t_id }}" @selected(old('selectedTenantId') == $tenant->t_id)>
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

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Username
                        </label>

                        <input type="text" class="form-control @error('username') is-invalid @enderror"
                            name="username" value="{{ old('username') }}" placeholder="เช่น tenant001" required autocomplete="username">

                        @error('username')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            รหัสผ่าน
                        </label>

                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                            name="password" required autocomplete="new-password">

                        @error('password')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror

                        <div class="form-text">
                            อย่างน้อย 12 ตัวอักษร
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            ยืนยันรหัสผ่าน
                        </label>

                        <input type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-person-plus me-1"></i>สร้างบัญชี
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="account-panel">
        <div class="account-panel-header">
            <div class="account-panel-icon"><i class="bi bi-people"></i></div>
            <div><h2 class="account-panel-title">บัญชีผู้เช่า</h2><p class="account-panel-subtitle">ตรวจสอบสถานะและจัดการบัญชีที่มีอยู่</p></div>
        </div>

        <div class="card-body p-0">
            @if ($accounts->isEmpty())
            <div class="text-center text-secondary py-5">
                ยังไม่มีบัญชีผู้เช่า
            </div>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle account-table">
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
                            <td class="account-user-cell">
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
                                <span class="account-status active">
                                    ใช้งาน
                                </span>
                                @else
                                <span class="account-status suspended">
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
                                <a class="btn btn-sm btn-outline-primary me-1"
                                    href="{{ route('admin.accounts', ['reset' => $account->u_id]) }}">
                                    <i class="bi bi-key me-1"></i>ตั้งรหัสใหม่
                                </a>

                                <a class="btn btn-sm btn-outline-danger"
                                    href="{{ route('admin.accounts', ['suspend' => $account->u_id]) }}">
                                    <i class="bi bi-slash-circle me-1"></i>ระงับ
                                </a>
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
    </section>

    @if ($resetUserId)
    <div class="account-action-overlay">
        <section class="account-action-dialog" role="dialog" aria-modal="true" aria-labelledby="reset-account-title">
            <div class="account-dialog-header">
                <div><h2 id="reset-account-title" class="account-panel-title">ตั้งรหัสผ่านใหม่</h2><p class="account-panel-subtitle">บัญชี {{ $accounts->firstWhere('u_id', $resetUserId)?->u_username }}</p></div>
                <a href="{{ route('admin.accounts') }}" class="btn-close" aria-label="ปิด"></a>
            </div>
            <div class="account-dialog-body">
                <form method="POST" action="{{ route('admin.accounts.reset-password', $resetUserId) }}"> @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">รหัสผ่านใหม่</label>
                        <input type="password" autocomplete="new-password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">อย่างน้อย 12 ตัวอักษร</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">ยืนยันรหัสผ่านใหม่</label>
                        <input type="password" autocomplete="new-password" class="form-control @error('password_confirmation') is-invalid @enderror" name="password_confirmation" required autocomplete="new-password">
                        @error('password_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">เหตุผล (ถ้ามี)</label>
                        <textarea class="form-control" rows="3" name="reason"></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.accounts') }}" class="btn btn-outline-secondary">ยกเลิก</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-key me-1"></i>บันทึกรหัสผ่านใหม่

                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
    @endif

    @if ($suspendUserId)
    <div class="account-action-overlay">
        <section class="account-action-dialog" role="dialog" aria-modal="true" aria-labelledby="suspend-account-title">
            <div class="account-dialog-header">
                <div><h2 id="suspend-account-title" class="account-panel-title text-danger">ระงับบัญชีผู้เช่า</h2><p class="account-panel-subtitle">บัญชี {{ $accounts->firstWhere('u_id', $suspendUserId)?->u_username }}</p></div>
                <a href="{{ route('admin.accounts') }}" class="btn-close" aria-label="ปิด"></a>
            </div>
            <div class="account-dialog-body">
                <form method="POST" action="{{ route('admin.accounts.suspend', $suspendUserId) }}"> @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">เหตุผล (ถ้ามี)</label>
                        <textarea class="form-control" rows="3" name="reason" placeholder="ระบุเหตุผลประกอบการระงับบัญชี"></textarea>
                    </div>
                    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>เมื่อระงับบัญชีแล้ว ผู้ใช้จะไม่สามารถเข้าสู่ระบบได้</div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.accounts') }}" class="btn btn-outline-secondary">ยกเลิก</a>
                        <button type="submit" class="btn btn-danger">
                            ยืนยันระงับบัญชี

                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
    @endif

</main>
