{{-- resources/views/rentals/show.blade.php --}}
{{-- R3: รายละเอียดการเช่า + Contract panel + History --}}

<x-layouts::app :title="__('รายละเอียดการเช่า')">
<div class="rental-detail-page" data-rental-id="{{ $rentalId }}">
    <div class="rental-detail-page__header">
        <a href="{{ route('rentals.index') }}" class="btn">← กลับไปรายการการเช่า</a>
    </div>

    <div id="detail-state-loading" class="state-box">กำลังโหลดข้อมูล...</div>
    <div id="detail-state-error" class="state-box state-box--error" hidden>
        <span id="detail-error-message"></span>
        <button type="button" id="detail-btn-retry">ลองใหม่</button>
    </div>

    <div id="detail-view" hidden>
        <h1 id="detail-title">การเช่า</h1>

        <div class="rental-detail-page__grid">
            <div><span class="label">ผู้เช่า</span><span id="d-tenant"></span></div>
            <div><span class="label">ห้อง</span><span id="d-room"></span></div>
            <div><span class="label">วันเข้า</span><span id="d-movein"></span></div>
            <div><span class="label">วันออก</span><span id="d-moveout"></span></div>
            <div><span class="label">สถานะ</span><span id="d-status" class="badge"></span></div>
        </div>

        <button type="button" id="btn-move-out" class="btn" hidden>ย้ายออก</button>
        {{-- ปุ่มนี้ยังไม่ผูก logic จริง (R5 ยังไม่ทำ) แสดงไว้เฉพาะเมื่อ ACTIVE ตามกฎที่ตกลงไว้ --}}

        @include('rentals.contract', ['rentalId' => $rentalId])
    </div>
</div>

<style>
    .rental-detail-page { max-width: 720px; margin: 0 auto; padding: 24px 16px; }
    .rental-detail-page__header { margin-bottom: 16px; }
    .rental-detail-page__grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin: 16px 0; }
    .rental-detail-page__grid .label { display: block; font-size: 0.85em; color: #888; }
    .state-box { padding: 24px; text-align: center; color: #999; border: 1px dashed #444; border-radius: 6px; }
    .state-box--error { color: #f87171; border-color: #7f1d1d; background: rgba(127,29,29,0.15); }
    .btn { display: inline-block; padding: 8px 16px; border-radius: 4px; text-decoration: none; border: 1px solid #444; color: inherit; background: transparent; cursor: pointer; margin-bottom: 12px; }
    .badge { padding: 2px 8px; border-radius: 999px; font-size: 0.85em; }
    .badge--active { background: #dcfce7; color: #166534; }
    .badge--ended { background: #f3f4f6; color: #4b5563; }
</style>

<script>
(function () {
    const rentalId = document.querySelector('.rental-detail-page').dataset.rentalId;
    const API_URL = `/api/v1/rentals/${rentalId}`;

    // TODO(ยืนยันกับเกลือ): ค่า rt_status จริง — ตอนนี้สมมติ ACTIVE/ENDED
    const STATUS_LABEL = {
        ACTIVE: { text: 'กำลังเช่า', cls: 'badge--active' },
        ENDED:  { text: 'สิ้นสุดแล้ว', cls: 'badge--ended' },
    };

    const el = {};
    ['detail-state-loading', 'detail-state-error', 'detail-error-message', 'detail-btn-retry',
     'detail-view', 'detail-title', 'd-tenant', 'd-room', 'd-movein', 'd-moveout', 'd-status',
     'btn-move-out'].forEach((id) => { el[id] = document.getElementById(id); });

    async function loadDetail() {
        el['detail-state-loading'].hidden = false;
        el['detail-state-error'].hidden = true;
        el['detail-view'].hidden = true;

        try {
            const res = await fetch(API_URL, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });

            if (res.status === 401) {
                window.location.href = '/login';
                return;
            }
            if (res.status === 404) {
                throw new Error('ไม่พบข้อมูลการเช่านี้ หรือคุณไม่มีสิทธิ์เข้าถึง');
            }
            if (!res.ok) {
                throw new Error('โหลดข้อมูลไม่สำเร็จ (' + res.status + ')');
            }

            const body = await res.json();
            render(body.data);
        } catch (err) {
            el['detail-error-message'].textContent = err.message;
            el['detail-state-loading'].hidden = true;
            el['detail-state-error'].hidden = false;
        }
    }

    function render(r) {
        const tenantName = r.tenant ? `${r.tenant.t_Fname} ${r.tenant.t_Lname}` : '-';
        const roomName = r.room ? `${r.room.r_name} (ชั้น ${r.room.r_floor})` : '-';

        el['detail-title'].textContent = `การเช่า: ${tenantName} — ${roomName}`;
        el['d-tenant'].textContent = tenantName;
        el['d-room'].textContent = roomName;
        el['d-movein'].textContent = r.rt_movein ?? '-';
        el['d-moveout'].textContent = r.rt_moveout ?? '-';

        const status = STATUS_LABEL[r.rt_status] || { text: r.rt_status, cls: '' };
        el['d-status'].textContent = status.text;
        el['d-status'].className = 'badge ' + status.cls;

        el['btn-move-out'].hidden = r.rt_status !== 'ACTIVE';

        el['detail-state-loading'].hidden = true;
        el['detail-view'].hidden = false;
    }

    el['detail-btn-retry'].addEventListener('click', loadDetail);

    loadDetail();
})();
</script>
</x-layouts::app>
