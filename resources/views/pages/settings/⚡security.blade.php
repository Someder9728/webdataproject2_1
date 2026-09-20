<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Facades\Session;

new #[Title('เปลี่ยนรหัสผ่าน')] class extends Component {
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function updatePassword(): void
    {
        $user = Auth::user();

        abort_unless($user && $user->is_active, 403);

        $validated = $this->validate([
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => ['required', 'string', 'min:12', 'max:72', 'confirmed'],
        ]);

        // จำกัดขนาดตาม bcrypt ที่โปรเจกต์ใช้อยู่
        if (strlen($validated['password']) > 72) {
            throw ValidationException::withMessages([
                'password' => 'รหัสผ่านต้องไม่เกิน 72 ไบต์',
            ]);
        }

        if (Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'กรุณาใช้รหัสผ่านใหม่ที่ต่างจากรหัสเดิม',
            ]);
        }

        DB::transaction(function () use ($user, $validated) {
            // User Model มี hashed cast จัดการ hash ให้แล้ว
            $user->password = $validated['password'];
            $user->must_change_password = false;
            $user->remember_token = Str::random(60);
            $user->save();

            // โปรเจกต์ใช้ database session:
            // ยกเลิก session เดิมของบัญชีนี้ทุกอุปกรณ์
            DB::table('sessions')->where('user_id', $user->getKey())->delete();
        });

        $this->reset('current_password', 'password', 'password_confirmation');

        Auth::logout();
        Session::invalidate();
        Session::regenerateToken();

        Session::flash('status', 'เปลี่ยนรหัสผ่านสำเร็จ กรุณาเข้าสู่ระบบด้วยรหัสใหม่');

        $this->redirectRoute('login');
    }
};

?>

<section class="w-100">

    <x-pages::settings.layout :heading="__('เปลี่ยนรหัสผ่าน')" :subheading="__('ใช้รหัสผ่านอย่างน้อย 12 ตัวอักษร')">

        {{-- PASSWORD CHANGE NOTICE --}}
        @if (auth()->user()->must_change_password)
            <div class="security-warning">

                <div class="security-warning-icon">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>

                <div>

                    <div class="security-warning-title">
                        ต้องเปลี่ยนรหัสผ่าน
                    </div>

                    <div class="security-warning-text">
                        กรุณาเปลี่ยนรหัสผ่านชั่วคราวก่อนเข้าใช้งานระบบ
                    </div>

                </div>

            </div>
        @endif

        {{-- PASSWORD FORM --}}
        <form wire:submit="updatePassword" class="security-form">

            {{-- CURRENT PASSWORD --}}
            <div class="security-field">

                <label for="current_password" class="security-label">
                    รหัสผ่านปัจจุบัน
                </label>

                <div class="security-input-wrapper">

                    <i class="bi bi-lock security-input-icon"></i>

                    <input id="current_password" type="password" wire:model="current_password"
                        autocomplete="current-password" required class="form-control security-input">

                    <button type="button" class="security-password-toggle"
                        onclick="toggleSecurityPassword('current_password', this)" aria-label="แสดงรหัสผ่าน">
                        <i class="bi bi-eye"></i>
                    </button>

                </div>

                @error('current_password')
                    <div class="security-error">
                        {{ $message }}
                    </div>
                @enderror

            </div>


            {{-- NEW PASSWORD --}}
            <div class="security-field">

                <label for="password" class="security-label">
                    รหัสผ่านใหม่
                </label>

                <div class="security-input-wrapper">

                    <i class="bi bi-shield-lock security-input-icon"></i>

                    <input id="password" type="password" wire:model="password" autocomplete="new-password"
                        minlength="12" maxlength="72" required class="form-control security-input">

                    <button type="button" class="security-password-toggle"
                        onclick="toggleSecurityPassword('password', this)" aria-label="แสดงรหัสผ่าน">
                        <i class="bi bi-eye"></i>
                    </button>

                </div>

                <div class="security-help">
                    รหัสผ่านต้องมีอย่างน้อย 12 ตัวอักษร
                </div>

                @error('password')
                    <div class="security-error">
                        {{ $message }}
                    </div>
                @enderror

            </div>


            {{-- CONFIRM PASSWORD --}}
            <div class="security-field">

                <label for="password_confirmation" class="security-label">
                    ยืนยันรหัสผ่านใหม่
                </label>

                <div class="security-input-wrapper">

                    <i class="bi bi-check2-circle security-input-icon"></i>

                    <input id="password_confirmation" type="password" wire:model="password_confirmation"
                        autocomplete="new-password" minlength="12" maxlength="72" required
                        class="form-control security-input">

                    <button type="button" class="security-password-toggle"
                        onclick="toggleSecurityPassword('password_confirmation', this)" aria-label="แสดงรหัสผ่าน">
                        <i class="bi bi-eye"></i>
                    </button>

                </div>

                @error('password_confirmation')
                    <div class="security-error">
                        {{ $message }}
                    </div>
                @enderror

            </div>


            {{-- PASSWORD REQUIREMENTS --}}
            <div class="security-requirements">

                <div class="security-requirements-title">
                    <i class="bi bi-info-circle"></i>
                    ข้อกำหนดรหัสผ่าน
                </div>

                <ul>
                    <li>มีความยาวอย่างน้อย 12 ตัวอักษร</li>
                    <li>รหัสผ่านใหม่ต้องแตกต่างจากรหัสผ่านเดิม</li>
                    <li>ต้องกรอกรหัสผ่านใหม่ให้ตรงกันทั้งสองช่อง</li>
                </ul>

            </div>


            {{-- SUBMIT --}}
            <div class="security-submit">

                <button type="submit" class="btn security-submit-button" wire:loading.attr="disabled"
                    wire:target="updatePassword">

                    <span wire:loading.remove wire:target="updatePassword">
                        <i class="bi bi-shield-check me-2"></i>
                        เปลี่ยนรหัสผ่าน
                    </span>

                    <span wire:loading wire:target="updatePassword">
                        <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                        กำลังเปลี่ยนรหัสผ่าน...
                    </span>

                </button>

            </div>

        </form>

    </x-pages::settings.layout>

</section>


<style>
    /* SECURITY FORM */
    .security-form {
        width: 100%;
        max-width: 680px;
    }


    /* WARNING */
    .security-warning {
        display: flex;
        align-items: flex-start;
        gap: 12px;

        margin-bottom: 24px;
        padding: 15px 16px;

        border: 1px solid #FDE68A;
        border-radius: 12px;

        background: #FFFBEB;
    }

    .security-warning-icon {
        flex-shrink: 0;
        color: #D97706;
        font-size: 18px;
        line-height: 1.5;
    }

    .security-warning-title {
        margin-bottom: 3px;
        color: #92400E;
        font-size: 13px;
        font-weight: 700;
    }

    .security-warning-text {
        color: #A16207;
        font-size: 12px;
        line-height: 1.6;
    }


    /*FIELD */
    .security-field {
        margin-bottom: 22px;
    }

    .security-label {
        display: block;
        margin-bottom: 8px;
        color: #42526B;
        font-size: 14px;
        font-weight: 600;
    }


    /* INPUT */
    .security-input-wrapper {
        position: relative;
    }

    .security-input-icon {
        position: absolute;
        top: 50%;
        left: 14px;
        z-index: 2;
        transform: translateY(-50%);
        color: #94A3B8;
        font-size: 17px;
        pointer-events: none;
    }

    .security-input {
        min-height: 46px;
        padding-top: 10px;
        padding-right: 46px;
        padding-bottom: 10px;
        padding-left: 44px;
        border: 1px solid #CBD5E1;
        border-radius: 9px;
        background: #ffffff;
        color: #172033;
        font-size: 14px;
        box-shadow: none;
    }

    .security-input:hover {
        border-color: #94A3B8;
    }

    .security-input:focus {
        border-color: #2161F5;
        background: #ffffff;
        color: #172033;
        box-shadow: 0 0 0 0.2rem rgba(33, 97, 245, 0.12);
    }


    /* PASSWORD TOGGLE */
    .security-password-toggle {
        position: absolute;
        top: 50%;
        right: 12px;
        z-index: 3;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        padding: 0;
        transform: translateY(-50%);
        border: 0;
        border-radius: 6px;
        background: transparent;
        color: #94A3B8;
        font-size: 16px;
        cursor: pointer;
    }

    .security-password-toggle:hover {
        background: #F1F5F9;
        color: #2161F5;
    }


    /* HELP TEXT */
    .security-help {
        margin-top: 6px;
        color: #94A3B8;
        font-size: 12px;
        line-height: 1.5;
    }


    /* ERROR */

    .security-error {
        margin-top: 6px;
        color: #DC2626;
        font-size: 12px;
        line-height: 1.5;
    }


    /* REQUIREMENTS */
    .security-requirements {
        margin-top: 6px;
        margin-bottom: 26px;
        padding: 15px 16px;
        border: 1px solid #E2E8F0;
        border-radius: 10px;
        background: #F8FAFC;
    }

    .security-requirements-title {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 7px;
        color: #42526B;
        font-size: 12px;
        font-weight: 700;
    }

    .security-requirements-title i {
        color: #2161F5;
    }

    .security-requirements ul {
        margin: 0;
        padding-left: 20px;
        color: #7B8CA5;
        font-size: 12px;
        line-height: 1.8;
    }


    /* SUBMIT */
    .security-submit {
        padding-top: 2px;
    }

    .security-submit-button {
        min-height: 44px;
        padding: 10px 18px;
        border: 0;
        border-radius: 8px;
        background: #23416B;
        color: #ffffff;
        font-size: 14px;
        font-weight: 600;
        box-shadow: 0 4px 10px rgba(35, 65, 107, 0.16);
        transition:
            background-color 0.15s ease,
            transform 0.15s ease,
            box-shadow 0.15s ease;
    }

    .security-submit-button:hover {
        background: #1C3558;
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 6px 14px rgba(35, 65, 107, 0.22);
    }

    .security-submit-button:active {
        transform: translateY(0);
    }

    .security-submit-button:disabled {
        opacity: 0.65;
        transform: none;
        cursor: not-allowed;
    }


    /* MOBILE */
    @media (max-width: 575.98px) {

        .security-input {
            min-height: 44px;

            font-size: 14px;
        }

        .security-warning {
            padding: 13px;
        }

        .security-requirements {
            padding: 13px;
        }

        .security-submit-button {
            width: 100%;
        }

    }
</style>


<script>
    function toggleSecurityPassword(inputId, button) {

        const input = document.getElementById(inputId);

        if (!input) {
            return;
        }

        const icon = button.querySelector('i');

        if (input.type === 'password') {

            input.type = 'text';

            if (icon) {
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            }

            button.setAttribute('aria-label', 'ซ่อนรหัสผ่าน');

        } else {

            input.type = 'password';

            if (icon) {
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }

            button.setAttribute('aria-label', 'แสดงรหัสผ่าน');
        }
    }
</script>
