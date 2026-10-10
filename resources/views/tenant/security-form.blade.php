@include('partials.admin-page-style')
<div class="tenant-page">
    <header class="tenant-header"><h1 class="tenant-title">เปลี่ยนรหัสผ่าน</h1><p class="tenant-subtitle">ใช้รหัสผ่านอย่างน้อย 12 ตัวอักษร หลังเปลี่ยนแล้วให้เข้าสู่ระบบใหม่</p></header>
    @if(auth()->user()->must_change_password)<div class="alert alert-warning tenant-alert">กรุณาเปลี่ยนรหัสผ่านชั่วคราวก่อนเข้าใช้งานระบบ</div>@endif
    <section class="tenant-search-card p-4">
        <form wire:submit="updatePassword">
            <div class="mb-3"><label class="form-label" for="tenant-current-password">รหัสผ่านปัจจุบัน</label><input class="form-control" id="tenant-current-password" type="password" wire:model="current_password" autocomplete="current-password" required>@error('current_password')<p class="text-danger small mt-2" role="alert">{{ $message }}</p>@enderror</div>
            <div class="mb-3"><label class="form-label" for="tenant-new-password">รหัสผ่านใหม่</label><input class="form-control" id="tenant-new-password" type="password" wire:model="password" autocomplete="new-password" minlength="12" maxlength="72" required>@error('password')<p class="text-danger small mt-2" role="alert">{{ $message }}</p>@enderror</div>
            <div class="mb-3"><label class="form-label" for="tenant-confirm-password">ยืนยันรหัสผ่านใหม่</label><input class="form-control" id="tenant-confirm-password" type="password" wire:model="password_confirmation" autocomplete="new-password" minlength="12" maxlength="72" required>@error('password_confirmation')<p class="text-danger small mt-2" role="alert">{{ $message }}</p>@enderror</div>
            <button class="btn tenant-add-btn" type="submit" wire:loading.attr="disabled" wire:target="updatePassword">เปลี่ยนรหัสผ่าน</button>
            @unless(auth()->user()->must_change_password)<a class="btn tenant-clear-btn ms-2" href="{{ route('my.profile') }}">กลับข้อมูลส่วนตัว</a>@endunless
        </form>
    </section>
</div>
