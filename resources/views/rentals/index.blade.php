<x-layouts::app :title="__('การเช่า')">
@include('rentals._styles')

<div class="rt rt-page">
    <div class="rt-head">
        <div>
            <div class="rt-crumb">การเช่า</div>
            <h1 class="rt-title">การเช่า</h1>
            <div class="rt-subtitle" id="rental-summary">กำลังเช่า {{ $active->count() }} รายการ · สิ้นสุดแล้ว {{ $ended->total() }} รายการ</div>
        </div>
        <div class="rt-head-actions">
            <a href="{{ route('rentals.create') }}" class="rt-btn rt-btn--primary" id="btn-create-rental">+ บันทึกการเช่า</a>
        </div>
    </div>

    @if (request('created') === '1')
        <div id="rental-banner" class="rt-notice rt-notice--ok">สร้างการเช่าสำเร็จ</div>
    @endif

    <form method="GET" class="rt-toolbar">
        <input type="search" name="q" id="rental-search" class="rt-search" value="{{ $search }}"
               placeholder="ค้นหาผู้เช่าหรือเลขห้อง แล้วกด Enter" aria-label="ค้นหาผู้เช่าหรือเลขห้อง">
    </form>

    <div id="rental-lists" class="rt-stack">
        <section class="rt-card rt-card--flush" aria-labelledby="active-title">
            <div class="rt-card-head">
                <h2 class="rt-card-title rt-card-title--lg" id="active-title">การเช่าปัจจุบัน</h2>
            </div>
            <div class="rt-table-wrap">
                <table class="rt-table" id="rental-table">
                    <thead>
                        <tr><th>ผู้เช่า</th><th>ห้อง</th><th>วันที่เข้า</th><th>วันที่ออก (ประมาณ)</th><th>สถานะ</th><th></th></tr>
                    </thead>
                    <tbody id="rental-table-body">
                        @forelse ($active as $rental)
                            @php($name = trim($rental->tenant?->t_Fname.' '.$rental->tenant?->t_Lname) ?: '-')
                            <tr>
                                <td><div class="rt-person"><span class="rt-avatar">{{ mb_substr($name, 0, 1) }}</span>{{ $name }}</div></td>
                                <td><span class="rt-badge rt-badge--info">{{ $rental->room?->r_name ?? '-' }}</span></td>
                                <td>{{ $rental->rt_movein?->format('Y-m-d') ?? '-' }}</td>
                                <td class="rt-muted">{{ $rental->rt_moveout?->format('Y-m-d') ?? '-' }}</td>
                                <td><span class="rt-badge rt-badge--ok">กำลังเช่า</span></td>
                                <td class="rt-actions">
                                    <a href="{{ route('rentals.show', $rental->rt_id) }}" class="rt-btn rt-btn--sm rt-btn--soft">ดูรายละเอียด</a>
                                    <button type="button" class="rt-btn rt-btn--sm" data-action="move-out"
                                            data-rental="{{ json_encode(['rentalId' => $rental->rt_id, 'tenantName' => $name, 'roomName' => $rental->room?->r_name ?? '-', 'roomId' => $rental->room?->r_id, 'moveIn' => $rental->rt_movein?->format('Y-m-d')]) }}">ย้ายออก</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="rt-muted" style="text-align:center;padding:20px;">{{ $search ? 'ไม่พบการเช่าที่ตรงกับคำค้นหา' : 'ไม่มีการเช่าที่กำลังดำเนินอยู่' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rt-card rt-card--flush" aria-labelledby="history-title">
            <div class="rt-card-head">
                <h2 class="rt-card-title rt-card-title--lg" id="history-title">ประวัติการเช่า</h2>
            </div>
            <div class="rt-table-wrap">
                <table class="rt-table" id="rental-history-table">
                    <thead>
                        <tr><th>ผู้เช่า</th><th>ห้อง</th><th>วันที่เข้า</th><th>วันที่ออก</th><th>สถานะ</th><th></th></tr>
                    </thead>
                    <tbody id="rental-history-body">
                        @forelse ($ended as $rental)
                            <tr>
                                <td>{{ trim($rental->tenant?->t_Fname.' '.$rental->tenant?->t_Lname) ?: '-' }}</td>
                                <td class="rt-link">{{ $rental->room?->r_name ?? '-' }}</td>
                                <td>{{ $rental->rt_movein?->format('Y-m-d') ?? '-' }}</td>
                                <td>{{ $rental->rt_moveout?->format('Y-m-d') ?? '-' }}</td>
                                <td><span class="rt-badge rt-badge--off">สิ้นสุดแล้ว</span></td>
                                <td class="rt-actions"><a href="{{ route('rentals.show', $rental->rt_id) }}" class="rt-btn rt-btn--sm rt-btn--soft">ดูรายละเอียด</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="rt-muted" style="text-align:center;padding:20px;">{{ $search ? 'ไม่พบประวัติที่ตรงกับคำค้นหา' : 'ยังไม่มีประวัติการเช่า' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    @include('rentals._pager', ['paginator' => $ended])

    @include('rentals._move-out-modal')
</div>

<script>
document.getElementById('rental-table-body').addEventListener('click', (e) => {
    const btn = e.target.closest('[data-action="move-out"]');
    if (!btn) return;
    window.RentalMoveOut.open({ ...JSON.parse(btn.dataset.rental), onDone: () => window.location.reload() });
});
</script>
</x-layouts::app>
