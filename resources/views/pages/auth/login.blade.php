<x-layouts::auth :title="__('เข้าสู่ระบบ')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('เข้าสู่ระบบ')"
            :description="__('กรอกอีเมลและรหัสผ่านของคุณ')"
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
                name="email"
                :label="__('อีเมล')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="username"
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