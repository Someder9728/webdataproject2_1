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
            'current_password' => [
                'required',
                'string',
                'current_password:web',
            ],
            'password' => [
                'required',
                'string',
                'min:12',
                'max:72',
                'confirmed',
            ],
        ]);

        // จำกัดขนาดตาม bcrypt ที่โปรเจกต์ใช้อยู่
        if (strlen($validated['password']) > 72) {
            throw ValidationException::withMessages([
                'password' => 'รหัสผ่านต้องไม่เกิน 72 ไบต์',
            ]);
        }

        if (Hash::check($validated['password'], $user->u_password)) {
            throw ValidationException::withMessages([
                'password' => 'กรุณาใช้รหัสผ่านใหม่ที่ต่างจากรหัสเดิม',
            ]);
        }

        DB::transaction(function () use ($user, $validated) {
            // User Model มี hashed cast จัดการ hash ให้แล้ว
            $user->u_password = $validated['password'];
            $user->must_change_password = false;
            $user->remember_token = Str::random(60);
            $user->save();

            // โปรเจกต์ใช้ database session:
            // ยกเลิก session เดิมของบัญชีนี้ทุกอุปกรณ์
            DB::table('sessions')
                ->where('user_id', $user->getKey())
                ->delete();
        });

        $this->reset(
            'current_password',
            'password',
            'password_confirmation'
        );

        Auth::logout();
        Session::invalidate();
        Session::regenerateToken();

        Session::flash(
            'status',
            'เปลี่ยนรหัสผ่านสำเร็จ กรุณาเข้าสู่ระบบด้วยรหัสใหม่'
        );

        $this->redirectRoute('login');
    }
};

?>

<section class="w-full">
    @if(auth()->user()?->u_role === 'tenant')
        @include('tenant.security-form')
    @else
    @include('partials.settings-heading')

    <x-pages::settings.layout
        :heading="__('เปลี่ยนรหัสผ่าน')"
        :subheading="__('ใช้รหัสผ่านอย่างน้อย 12 ตัวอักษร')"
    >
        @if (auth()->user()->must_change_password)
            <div class="settings-note mb-4">กรุณาเปลี่ยนรหัสผ่านชั่วคราวก่อนเข้าใช้งานระบบ</div>
        @endif

        <form wire:submit="updatePassword" class="settings-password-form">
            <div class="settings-field">
                <label for="current_password">รหัสผ่านปัจจุบัน</label>
                <input id="current_password" wire:model="current_password" type="password" autocomplete="current-password" required>
                @error('current_password') <div class="settings-error">{{ $message }}</div> @enderror
            </div>

            <div class="settings-field">
                <label for="new_password">รหัสผ่านใหม่</label>
                <input id="new_password" wire:model="password" type="password" autocomplete="new-password" minlength="12" maxlength="72" required>
                <div class="settings-note">ใช้รหัสผ่านอย่างน้อย 12 ตัวอักษร</div>
                @error('password') <div class="settings-error">{{ $message }}</div> @enderror
            </div>

            <div class="settings-field">
                <label for="password_confirmation">ยืนยันรหัสผ่านใหม่</label>
                <input id="password_confirmation" wire:model="password_confirmation" type="password" autocomplete="new-password" minlength="12" maxlength="72" required>
                @error('password_confirmation') <div class="settings-error">{{ $message }}</div> @enderror
            </div>

            <button class="settings-button" type="submit" wire:loading.attr="disabled" wire:target="updatePassword">
                <span wire:loading.remove wire:target="updatePassword">เปลี่ยนรหัสผ่าน</span>
                <span wire:loading wire:target="updatePassword">กำลังบันทึก...</span>
            </button>
        </form>
    </x-pages::settings.layout>
    @endif
</section>
