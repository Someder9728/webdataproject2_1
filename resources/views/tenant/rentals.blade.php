<x-layouts::app.sidebar title="การเช่าของฉัน">
    @include('partials.admin-page-style')
    <div class="tenant-page">
        <header class="tenant-header"><h1 class="tenant-title">การเช่าของฉัน</h1><p class="tenant-subtitle">ข้อมูลการพักอาศัยปัจจุบันและประวัติของคุณ</p></header>
        <h2 class="h6 fw-semibold mb-3">การเช่าปัจจุบัน</h2>
        @forelse($current as $rental)
            @include('tenant.rental-card')
        @empty
            <div class="tenant-search-card p-4 text-secondary">ตอนนี้คุณไม่มีการเช่าที่กำลังใช้งาน</div>
        @endforelse
        <h2 class="h6 fw-semibold mb-3 mt-4">ประวัติการเช่า</h2>
        <div class="tenant-table-card table-responsive">
            <table class="table tenant-table"><thead><tr><th>ห้อง</th><th>วันเข้าพัก</th><th>วันย้ายออก</th><th>สถานะ</th><th>รายละเอียด</th></tr></thead>
                <tbody>@forelse($history as $rental)
                    <tr><td>{{ $rental->room?->r_name ?? '—' }}</td><td>{{ $rental->rt_movein->toDateString() }}</td><td>{{ $rental->rt_moveout?->toDateString() ?? '—' }}</td><td><span class="badge text-bg-secondary">สิ้นสุดแล้ว</span></td><td><a class="btn btn-sm tenant-edit-btn" href="{{ route('rentals.show', $rental->getKey()) }}">ดูรายละเอียด</a></td></tr>
                @empty <tr><td colspan="5" class="tenant-state text-center">ยังไม่มีประวัติการเช่า</td></tr> @endforelse</tbody>
            </table>
        </div>
        <div class="mt-3">{{ $history->links('pagination::bootstrap-5') }}</div>
    </div>
</x-layouts::app.sidebar>
