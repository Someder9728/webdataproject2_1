<section class="tenant-search-card p-4">
    <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
        <h2 class="h6 fw-semibold mb-0">สัญญา {{ $contract->c_number }} · ห้อง {{ $rental->room?->r_name ?? '—' }}</h2>
        <span class="badge {{ ['ACTIVE' => 'text-bg-success', 'EXPIRED' => 'text-bg-warning', 'ENDED' => 'text-bg-secondary'][$contract->c_status] ?? 'text-bg-secondary' }}">{{ ['ACTIVE' => 'มีผล', 'EXPIRED' => 'หมดอายุ', 'ENDED' => 'สิ้นสุดแล้ว'][$contract->c_status] ?? $contract->c_status }}</span>
    </div>
    <dl class="row mb-0">
        <div class="col-12 col-md-6 mb-3"><dt class="small text-secondary">วันเริ่มสัญญา</dt><dd>{{ $contract->c_start->toDateString() }}</dd></div>
        <div class="col-12 col-md-6 mb-3"><dt class="small text-secondary">วันสิ้นสุดสัญญา</dt><dd>{{ $contract->c_end?->toDateString() ?? 'ไม่กำหนดวันสิ้นสุด' }}</dd></div>
        <div class="col-12 col-md-6 mb-3"><dt class="small text-secondary">ค่าเช่ารายเดือน</dt><dd>฿{{ number_format((float)$contract->c_rent, 2) }}</dd></div>
        <div class="col-12 col-md-6 mb-3"><dt class="small text-secondary">เงินประกัน</dt><dd>฿{{ number_format((float)$contract->c_deposit, 2) }}</dd></div>
    </dl>
    @unless(request()->routeIs('rentals.show'))<a class="btn btn-sm tenant-edit-btn" href="{{ route('rentals.show', $rental->getKey()) }}">ดูรายละเอียดการเช่า</a>@endunless
</section>
