<x-layouts::app.sidebar title="ข้อมูลส่วนตัว">
    @include('partials.admin-page-style')
    @php($account = auth()->user())
    @php($tenant = $account->tenant)
    <div class="tenant-page">
        <header class="tenant-header"><h1 class="tenant-title">ข้อมูลส่วนตัว</h1><p class="tenant-subtitle">หากต้องการแก้ไขข้อมูลผู้เช่า กรุณาติดต่อผู้ดูแล</p></header>
        <section class="tenant-search-card p-4"><dl class="row mb-0">
            <div class="col-12 col-md-6 mb-3"><dt class="small text-secondary">ชื่อผู้ใช้</dt><dd>{{ $account->u_username }}</dd></div>
            <div class="col-12 col-md-6 mb-3"><dt class="small text-secondary">ชื่อผู้เช่า</dt><dd>{{ $tenant?->t_Fname }} {{ $tenant?->t_Lname }}</dd></div>
            <div class="col-12 col-md-6 mb-3"><dt class="small text-secondary">โทรศัพท์</dt><dd>{{ $tenant?->t_tel ?? '—' }}</dd></div>
            <div class="col-12 col-md-6 mb-3"><dt class="small text-secondary">อีเมล</dt><dd>{{ $tenant?->t_mail ?? '—' }}</dd></div>
            <div class="col-12 mb-3"><dt class="small text-secondary">ที่อยู่</dt><dd class="text-break">{{ $tenant?->t_address ?? '—' }}</dd></div>
        </dl><a class="btn tenant-edit-btn" href="{{ route('security.edit') }}">จัดการรหัสผ่าน</a></section>
    </div>
</x-layouts::app.sidebar>
