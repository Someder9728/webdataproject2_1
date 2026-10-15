<section class="tenant-search-card p-4">
    <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
        <div><h2 class="h5 fw-semibold mb-2">ห้อง {{ $rental->room?->r_name ?? '—' }}</h2><span class="badge {{ $rental->rt_status === 'ACTIVE' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ (['ACTIVE' => 'กำลังเช่า', 'SCHEDULED' => 'รอเข้าอยู่', 'ENDED' => 'สิ้นสุดแล้ว', 'CANCELLED' => 'ยกเลิกแล้ว'][$rental->rt_status] ?? $rental->rt_status) }}</span></div>
        @unless(request()->routeIs('rentals.show'))<a class="btn tenant-edit-btn align-self-start" href="{{ route('rentals.show', $rental->getKey()) }}">ดูรายละเอียดการเช่า</a>@endunless
    </div>
    <dl class="row mb-0">
        <div class="col-12 col-md-4 mb-3"><dt class="small text-secondary">{{ $rental->rt_movein === null ? 'กำหนดเข้าพัก' : 'วันเข้าพักจริง' }}</dt><dd>{{ ($rental->rt_movein ?? $rental->scheduled_movein_at)?->toDateString() ?? '—' }}</dd></div>
        <div class="col-12 col-md-4 mb-3"><dt class="small text-secondary">ค่าเช่ารายเดือนตามสัญญา</dt><dd>{{ $rental->contract ? '฿'.number_format((float)$rental->contract->c_rent, 2) : 'ไม่มีข้อมูลสัญญา' }}</dd></div>
        <div class="col-12 col-md-4 mb-3"><dt class="small text-secondary">วันย้ายออก</dt><dd>{{ $rental->rt_moveout?->toDateString() ?? ($rental->rt_status === 'ACTIVE' ? 'ยังพักอยู่' : 'ยังไม่มีวันย้ายออกจริง') }}</dd></div>
    </dl>
</section>
