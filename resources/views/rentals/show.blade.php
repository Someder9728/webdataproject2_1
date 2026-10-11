<x-layouts::app :title="__('รายละเอียดการเช่า')">
@include('rentals._styles')
@php
    $tenantName = trim($rental->tenant?->t_Fname.' '.$rental->tenant?->t_Lname) ?: '-';
    $isActive = $rental->rt_status === 'ACTIVE';
    [$roomStatus, $roomClass] = match ($rental->room?->r_status) {
        'OCCUPIED' => ['มีผู้เช่า', 'rt-badge--ok'],
        'VACANT' => ['ว่าง', 'rt-badge--info'],
        default => [$rental->room?->r_status ?? '-', 'rt-badge--off'],
    };
@endphp

<div class="rt rt-page" id="rental-detail-page">
    <div class="rt-head">
        <div>
            <div class="rt-crumb"><a href="{{ route('rentals.index') }}">การเช่า</a> / <span id="d-crumb">{{ $tenantName }}</span></div>
            <h1 class="rt-title" id="detail-title">การเช่า · {{ $tenantName }}</h1>
        </div>
        <div class="rt-head-actions">
            @if ($isActive)
                <button type="button" id="btn-move-out" class="rt-btn rt-btn--danger"
                        data-rental="{{ json_encode(['rentalId' => $rental->rt_id, 'tenantName' => $tenantName, 'roomName' => $rental->room?->r_name ?? '-', 'roomId' => $rental->room?->r_id, 'moveIn' => $rental->rt_movein?->format('Y-m-d')]) }}">ย้ายออก</button>
            @endif
            <a href="{{ route('rentals.index') }}" class="rt-btn">‹ ย้อนกลับ</a>
        </div>
    </div>

    <div class="rt-grid">
        <div class="rt-col">
            <section class="rt-card" aria-labelledby="tenant-card-title">
                <div class="rt-card-head"><h2 class="rt-card-title" id="tenant-card-title">ข้อมูลผู้เช่า</h2></div>
                <div class="rt-person" style="margin-bottom:14px;">
                    <span class="rt-avatar rt-avatar--lg" id="d-avatar">{{ mb_substr($tenantName, 0, 1) }}</span>
                    <div>
                        <div class="rt-field-value" id="d-tenant">{{ $tenantName }}</div>
                        <div class="rt-tl-date" id="d-tenant-code">{{ $rental->tenant ? 'TN-'.str_pad((string) $rental->tenant->t_id, 3, '0', STR_PAD_LEFT) : '-' }}</div>
                    </div>
                </div>
                <dl class="rt-kv">
                    <div><dt>โทรศัพท์</dt><dd id="d-tel">{{ $rental->tenant?->t_tel ?: '-' }}</dd></div>
                    <div><dt>อีเมล</dt><dd id="d-mail">{{ $rental->tenant?->t_mail ?: '-' }}</dd></div>
                </dl>
            </section>

            <section class="rt-card" aria-labelledby="room-card-title">
                <div class="rt-card-head"><h2 class="rt-card-title" id="room-card-title">ข้อมูลห้อง</h2></div>
                <p class="rt-big" id="d-room">{{ $rental->room?->r_name ?? '-' }}</p>
                <dl class="rt-kv">
                    <div><dt>ชั้น</dt><dd id="d-floor">{{ $rental->room?->r_floor !== null ? 'ชั้น '.$rental->room->r_floor : '-' }}</dd></div>
                    <div><dt>ประเภท</dt><dd id="d-type">{{ $rental->room?->r_type ?: '-' }}</dd></div>
                    <div><dt>ค่าเช่า</dt><dd id="d-room-rent" class="rt-money">{{ $rental->room?->r_rent !== null ? \App\Support\Format::money($rental->room->r_rent).'/เดือน' : '-' }}</dd></div>
                    <div><dt>สถานะห้อง</dt><dd id="d-room-status"><span class="rt-badge {{ $roomClass }}">{{ $roomStatus }}</span></dd></div>
                </dl>
            </section>
        </div>

        <div class="rt-col">
            <section class="rt-card" aria-labelledby="rental-card-title">
                <div class="rt-card-head">
                    <h2 class="rt-card-title" id="rental-card-title">ข้อมูลการเช่า</h2>
                    <span id="d-status" class="rt-badge {{ $isActive ? 'rt-badge--ok' : 'rt-badge--off' }}">{{ $isActive ? 'กำลังเช่า' : 'สิ้นสุดแล้ว' }}</span>
                </div>
                <div class="rt-fields rt-fields--3">
                    <div><span class="rt-field-label">วันที่เข้าพัก</span><span class="rt-field-value" id="d-movein">{{ $rental->rt_movein?->format('Y-m-d') ?? '-' }}</span></div>
                    <div><span class="rt-field-label">วันที่ย้ายออก</span><span class="rt-field-value" id="d-moveout">{{ $rental->rt_moveout?->format('Y-m-d') ?? '-' }}</span></div>
                    <div><span class="rt-field-label">สถานะ</span><span class="rt-field-value" id="d-status-text">{{ $isActive ? 'กำลังเช่าอยู่' : 'สิ้นสุดแล้ว' }}</span></div>
                </div>
            </section>

            @include('rentals.contract')

            <section class="rt-card rt-card--flush" aria-labelledby="timeline-title">
                <div class="rt-card-head"><h2 class="rt-card-title rt-card-title--lg" id="timeline-title">ประวัติการเช่า</h2></div>
                <div style="padding:16px 20px;">
                    <ul class="rt-timeline" id="timeline-list">
                        @forelse ($timeline as $item)
                            <li>
                                <span class="rt-dot {{ $item['kind'] ? 'rt-dot--'.$item['kind'] : '' }}"></span>
                                <div>
                                    <div><span class="rt-tl-date">{{ $item['date'] }}{{ isset($item['time']) ? ' '.$item['time'] : '' }}</span><span class="rt-tl-title">{{ $item['title'] }}</span></div>
                                    @if ($item['desc'])
                                        <div class="rt-tl-desc">{{ $item['desc'] }}</div>
                                    @endif
                                    @isset($item['by'])
                                        <div class="rt-tl-desc">{{ $item['by'] }}</div>
                                        <div class="rt-tl-reason">เหตุผล: {{ $item['reason'] }}</div>
                                    @endisset
                                </div>
                            </li>
                        @empty
                            <li class="rt-muted" style="font-size:0.875rem;">ยังไม่มีประวัติ</li>
                        @endforelse
                    </ul>
                </div>
            </section>
        </div>
    </div>

    @include('rentals._move-out-modal')
</div>

<script>
document.getElementById('btn-move-out')?.addEventListener('click', (e) => {
    window.RentalMoveOut.open({ ...JSON.parse(e.currentTarget.dataset.rental), onDone: () => window.location.reload() });
});
</script>
</x-layouts::app>
