<x-layouts::auth :title="__('เข้าสู่ระบบ')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('เข้าสู่ระบบ')"
            :description="__('กรอกชื่อผู้ใช้และรหัสผ่านของคุณ')"
        />

        <x-auth-session-status
            class="text-center"
            :status="session('status')"
        />

        <form
            method="POST"
            action="{{ route('login.store') }}"
            class="flex flex-col gap-6"
        >
            @csrf

            <flux:input
                name="u_username"
                :label="__('ชื่อผู้ใช้')"
                :value="old('u_username')"
                type="text"
                required
                autofocus
                autocomplete="username"
                maxlength="45"
            />

            <flux:input
                name="password"
                :label="__('รหัสผ่าน')"
                type="password"
                required
                autocomplete="current-password"
                viewable
            />

            <flux:checkbox
                name="remember"
                :label="__('จดจำการเข้าสู่ระบบ')"
                :checked="old('remember')"
            />

            <flux:button
                variant="primary"
                type="submit"
                class="w-full"
            >
                {{ __('เข้าสู่ระบบ') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>