<x-layouts::app.sidebar title="รายละเอียดการเช่าของฉัน">
    @include('partials.admin-page-style')
    <div class="tenant-page">
        <header class="tenant-header d-flex flex-wrap justify-content-between gap-3"><h1 class="tenant-title">รายละเอียดการเช่าของฉัน</h1><a class="btn tenant-clear-btn" href="{{ route('my.rentals') }}">กลับหน้าการเช่าของฉัน</a></header>
        @include('tenant.rental-card')
        <section class="tenant-search-card p-4"><h2 class="h6 fw-semibold mb-3">ข้อมูลผู้เช่าและห้องพัก</h2>
            <dl class="row mb-0">
                <div class="col-12 col-md-6 mb-3"><dt class="small text-secondary">ผู้เช่า</dt><dd>{{ $rental->tenant?->t_Fname }} {{ $rental->tenant?->t_Lname }}</dd></div>
                <div class="col-12 col-md-6 mb-3"><dt class="small text-secondary">โทรศัพท์</dt><dd>{{ $rental->tenant?->t_tel ?? '—' }}</dd></div>
                <div class="col-12 col-md-6 mb-3"><dt class="small text-secondary">อีเมล</dt><dd>{{ $rental->tenant?->t_mail ?? '—' }}</dd></div>
                <div class="col-12 col-md-6 mb-3"><dt class="small text-secondary">ชั้น / ประเภทห้อง</dt><dd>{{ $rental->room?->r_floor ?? '—' }} / {{ $rental->room?->r_type ?? '—' }}</dd></div>
            </dl>
        </section>
        @if($rental->contract) @include('tenant.contract-card', ['contract' => $rental->contract])
        @else <p class="text-secondary">ไม่พบสัญญาของการเช่านี้ กรุณาติดต่อผู้ดูแล</p> @endif
        <a class="btn tenant-add-btn" href="{{ route('invoices.index') }}">ดูใบแจ้งหนี้และการชำระเงิน</a>
    </div>
</x-layouts::app.sidebar>
