<section class="tenant-search-card p-4">
    <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
        <div><h2 class="h5 fw-semibold mb-2">ห้อง {{ $rental->room?->r_name ?? '—' }}</h2><span class="badge {{ $rental->rt_status === 'ACTIVE' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $rental->rt_status === 'ACTIVE' ? 'กำลังเช่า' : 'สิ้นสุดแล้ว' }}</span></div>
        @unless(request()->routeIs('rentals.show'))<a class="btn tenant-edit-btn align-self-start" href="{{ route('rentals.show', $rental->getKey()) }}">ดูรายละเอียดการเช่า</a>@endunless
    </div>
    <dl class="row mb-0">
        <div class="col-12 col-md-4 mb-3"><dt class="small text-secondary">วันเข้าพัก</dt><dd>{{ $rental->rt_movein->toDateString() }}</dd></div>
        <div class="col-12 col-md-4 mb-3"><dt class="small text-secondary">ค่าเช่ารายเดือนตามสัญญา</dt><dd>{{ $rental->contract ? '฿'.number_format((float)$rental->contract->c_rent, 2) : 'ไม่มีข้อมูลสัญญา' }}</dd></div>
        <div class="col-12 col-md-4 mb-3"><dt class="small text-secondary">วันย้ายออก</dt><dd>{{ $rental->rt_moveout?->toDateString() ?? 'ยังพักอยู่' }}</dd></div>
    </dl>
</section>
