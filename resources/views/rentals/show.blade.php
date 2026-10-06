{{-- resources/views/rentals/show.blade.php --}}
{{-- R3: รายละเอียดการเช่า — การ์ดผู้เช่า/ห้อง/การเช่า/สัญญา (R4 อยู่ในการ์ดสัญญา) + ไทม์ไลน์ประวัติการเช่า --}}

<x-layouts::app :title="__('รายละเอียดการเช่า')">
@include('rentals._styles')

<div class="rt rt-page" id="rental-detail-page" data-rental-id="{{ $rentalId }}">
    <div id="detail-state-loading" class="rt-card rt-state">กำลังโหลดข้อมูล...</div>
    <div id="detail-state-error" class="rt-state rt-state--error" hidden>
        <span id="detail-error-message"></span>
        <button type="button" id="detail-btn-retry" class="rt-btn rt-btn--sm">ลองใหม่</button>
        <a href="{{ route('rentals.index') }}" class="rt-btn rt-btn--sm">← กลับ</a>
    </div>

    <div id="detail-view" hidden>
        <div class="rt-head">
            <div>
                <div class="rt-crumb"><a href="{{ route('rentals.index') }}">การเช่า</a> / <span id="d-crumb">-</span></div>
                <h1 class="rt-title" id="detail-title">การเช่า</h1>
            </div>
            <div class="rt-head-actions">
                {{-- ปุ่มย้ายออก: แสดงเฉพาะเมื่อ ACTIVE และเป็น admin — กดแล้วเปิด modal (_move-out-modal) --}}
                @if (auth()->user()?->u_role === 'admin')
                    <button type="button" id="btn-move-out" class="rt-btn rt-btn--danger" data-action="move-out"
                            data-rental-id="{{ $rentalId }}" hidden>ย้ายออก</button>
                @endif
                <a href="{{ route('rentals.index') }}" class="rt-btn">‹ ย้อนกลับ</a>
            </div>
        </div>

        <div class="rt-grid">
            {{-- คอลัมน์ซ้าย: ผู้เช่า + ห้อง --}}
            <div class="rt-col">
                <section class="rt-card" aria-labelledby="tenant-card-title">
                    <div class="rt-card-head"><h2 class="rt-card-title" id="tenant-card-title">ข้อมูลผู้เช่า</h2></div>
                    <div class="rt-person" style="margin-bottom:14px;">
                        <span class="rt-avatar rt-avatar--lg" id="d-avatar">-</span>
                        <div>
                            <div class="rt-field-value" id="d-tenant">-</div>
                            <div class="rt-tl-date" id="d-tenant-code">-</div>
                        </div>
                    </div>
                    <dl class="rt-kv">
                        <div><dt>โทรศัพท์</dt><dd id="d-tel">-</dd></div>
                        <div><dt>อีเมล</dt><dd id="d-mail">-</dd></div>
                    </dl>
                </section>

                <section class="rt-card" aria-labelledby="room-card-title">
                    <div class="rt-card-head"><h2 class="rt-card-title" id="room-card-title">ข้อมูลห้อง</h2></div>
                    <p class="rt-big" id="d-room">-</p>
                    <dl class="rt-kv">
                        <div><dt>ชั้น</dt><dd id="d-floor">-</dd></div>
                        <div><dt>ประเภท</dt><dd id="d-type">-</dd></div>
                        <div><dt>ค่าเช่า</dt><dd id="d-room-rent" class="rt-money">-</dd></div>
                        <div><dt>สถานะห้อง</dt><dd id="d-room-status">-</dd></div>
                    </dl>
                </section>
            </div>

            {{-- คอลัมน์ขวา: การเช่า + สัญญา + ไทม์ไลน์ --}}
            <div class="rt-col">
                <section class="rt-card" aria-labelledby="rental-card-title">
                    <div class="rt-card-head">
                        <h2 class="rt-card-title" id="rental-card-title">ข้อมูลการเช่า</h2>
                        <span id="d-status" class="rt-badge"></span>
                    </div>
                    <div class="rt-fields rt-fields--3">
                        <div><span class="rt-field-label">วันที่เข้าพัก</span><span class="rt-field-value" id="d-movein">-</span></div>
                        <div><span class="rt-field-label">วันที่ย้ายออก</span><span class="rt-field-value" id="d-moveout">-</span></div>
                        <div><span class="rt-field-label">สถานะ</span><span class="rt-field-value" id="d-status-text">-</span></div>
                    </div>
                </section>

                {{-- R4: การ์ดสัญญา + ฟอร์มแก้ไข/ต่อสัญญา --}}
                @include('rentals.contract', ['rentalId' => $rentalId])

                <section class="rt-card rt-card--flush" aria-labelledby="timeline-title">
                    <div class="rt-card-head"><h2 class="rt-card-title rt-card-title--lg" id="timeline-title">ประวัติการเช่า</h2></div>
                    <div style="padding:16px 20px;">
                        <ul class="rt-timeline" id="timeline-list"></ul>
                    </div>
                </section>
            </div>
        </div>
    </div>

    {{-- Modal ย้ายออก (Move-out) — เฉพาะ admin --}}
    @if (auth()->user()?->u_role === 'admin')
        @include('rentals._move-out-modal')
    @endif
</div>

<script>
(function () {
    const page = document.getElementById('rental-detail-page');
    const rentalId = page.dataset.rentalId;
    const API_URL = `/api/v1/rentals/${rentalId}`;
    const fetchOpts = { headers: { Accept: 'application/json' }, credentials: 'same-origin' };

    // ค่าสถานะยืนยันกับ backend แล้ว
    const STATUS_LABEL = {
        ACTIVE: { text: 'กำลังเช่า', long: 'กำลังเช่าอยู่', cls: 'rt-badge--ok' },
        ENDED:  { text: 'สิ้นสุดแล้ว', long: 'สิ้นสุดแล้ว', cls: 'rt-badge--off' },
    };
    const ROOM_STATUS = {
        OCCUPIED: { text: 'มีผู้เช่า', cls: 'rt-badge--ok' },
        VACANT:   { text: 'ว่าง', cls: 'rt-badge--info' },
    };

    const $ = (id) => document.getElementById(id);
    const set = (id, v) => { $(id).textContent = (v === null || v === undefined || v === '') ? '-' : v; };
    const money = (n) => '฿' + Number(n).toLocaleString('th-TH', { maximumFractionDigits: 2 });
    const escapeHtml = (s) => { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; };

    // ข้อมูลที่ใช้สร้างไทม์ไลน์ — ค่อย ๆ เติมเมื่อแต่ละส่วนโหลดเสร็จ
    const tl = { rental: null, contract: null };

    async function loadDetail() {
        $('detail-state-loading').hidden = false;
        $('detail-state-error').hidden = true;
        $('detail-view').hidden = true;

        try {
            const res = await fetch(API_URL, fetchOpts);

            if (res.status === 401) { window.location.href = '/login'; return; }
            if (res.status === 404) throw new Error('ไม่พบข้อมูลการเช่านี้ หรือคุณไม่มีสิทธิ์เข้าถึง');
            if (!res.ok) throw new Error('โหลดข้อมูลไม่สำเร็จ (' + res.status + ')');

            const body = await res.json();
            render(body.data);
        } catch (err) {
            $('detail-error-message').textContent = err.message;
            $('detail-state-loading').hidden = true;
            $('detail-state-error').hidden = false;
        }
    }

    function render(r) {
        tl.rental = r;

        const tenantName = r.tenant ? `${r.tenant.t_Fname ?? ''} ${r.tenant.t_Lname ?? ''}`.trim() : '-';
        const roomName = r.room ? r.room.r_name : '-';

        $('detail-title').textContent = `การเช่า · ${tenantName}`;
        set('d-crumb', tenantName);

        // การ์ดผู้เช่า (ข้อมูลที่ API รายการเช่ามีให้ก่อน — อีเมลโหลดเพิ่มทีหลัง)
        set('d-avatar', tenantName !== '-' ? tenantName.charAt(0) : '?');
        set('d-tenant', tenantName);
        set('d-tenant-code', r.tenant ? 'TN-' + String(r.tenant.t_id).padStart(3, '0') : '-');
        set('d-tel', r.tenant?.t_tel);
        set('d-mail', '-');

        // การ์ดห้อง
        set('d-room', roomName);
        set('d-floor', r.room?.r_floor != null ? `ชั้น ${r.room.r_floor}` : '-');
        set('d-type', r.room?.r_type);
        set('d-room-rent', '-');
        const rs = ROOM_STATUS[r.room?.r_status];
        $('d-room-status').innerHTML = rs
            ? `<span class="rt-badge ${rs.cls}">${rs.text}</span>`
            : escapeHtml(r.room?.r_status ?? '-');

        // การ์ดการเช่า
        const st = STATUS_LABEL[r.rt_status] || { text: r.rt_status, long: r.rt_status, cls: 'rt-badge--off' };
        set('d-movein', r.rt_movein);
        set('d-moveout', r.rt_moveout);
        set('d-status-text', st.long);
        $('d-status').textContent = st.text;
        $('d-status').className = 'rt-badge ' + st.cls;

        const moveOutBtn = $('btn-move-out'); // มีเฉพาะ admin
        if (moveOutBtn) {
            moveOutBtn.hidden = r.rt_status !== 'ACTIVE';
            moveOutBtn.onclick = () => window.RentalMoveOut.open({
                rentalId: r.rt_id,
                tenantName,
                roomName,
                roomId: r.room?.r_id ?? null,
                moveIn: r.rt_movein,
                onDone: () => window.location.reload(), // ข้อมูลหลายส่วน (สัญญา/ไทม์ไลน์/ห้อง) เปลี่ยนพร้อมกัน จึงโหลดหน้าใหม่
            });
        }

        $('detail-state-loading').hidden = true;
        $('detail-view').hidden = false;

        renderTimeline();

        // บอกการ์ดสัญญาว่าการเช่านี้สถานะอะไร (ย้ายออกแล้ว → แก้สัญญาไม่ได้)
        window.__rentalInfo = r; // เผื่อการ์ดสัญญายังไม่ทันฟัง event
        document.dispatchEvent(new CustomEvent('rental:loaded', { detail: r }));

        // โหลดส่วนเสริมแบบไม่บล็อกหน้า — พลาดก็แค่แสดง "-"
        if (r.tenant?.t_id) loadTenantExtra(r.tenant.t_id);
        if (r.room?.r_id) loadRoomExtra(r.room.r_id);

        // สัญญาอาจโหลดเสร็จก่อนแล้ว
        if (window.__rentalContract) onContract(window.__rentalContract);
    }

    async function loadTenantExtra(tenantId) {
        try {
            const res = await fetch(`/api/v1/tenants/${tenantId}`, fetchOpts);
            if (!res.ok) return;
            const body = await res.json();
            set('d-mail', body.data?.t_mail);
        } catch (_) { /* เงียบไว้ */ }
    }

    async function loadRoomExtra(roomId) {
        try {
            const res = await fetch(`/api/v1/rooms/${roomId}`, fetchOpts);
            if (!res.ok) return;
            const body = await res.json();
            if (body.data?.r_rent != null) set('d-room-rent', money(body.data.r_rent) + '/เดือน');
        } catch (_) { /* เงียบไว้ */ }
    }

    // ---------- ไทม์ไลน์ ----------
    // สร้างจากข้อมูลการเช่า + สัญญาเท่านั้น
    // (backend-completion ยังไม่มี API อ่าน audit_events จึงยังแสดงประวัติ "แก้/ต่อสัญญา" พร้อมเหตุผลไม่ได้
    //  ถ้าเกลือเพิ่ม endpoint ภายหลัง ค่อยดึง action 'contract_updated' ของ entity 'contracts' มาเติม)
    function onContract(c) {
        tl.contract = c;
        renderTimeline();
    }
    document.addEventListener('rental:contract-loaded', (e) => onContract(e.detail));
    document.addEventListener('rental:contract-updated', (e) => onContract(e.detail));

    function buildTimeline() {
        const items = [];
        const r = tl.rental;
        if (!r) return items;

        const roomName = r.room?.r_name ?? '-';
        if (r.rt_movein) {
            items.push({ date: r.rt_movein, order: 0, title: 'ย้ายเข้า', desc: `ห้อง ${roomName}`, kind: '' });
        }
        if (tl.contract?.c_start) {
            const end = tl.contract.c_end ? `สิ้นสุด ${tl.contract.c_end}` : 'ไม่กำหนดวันสิ้นสุด';
            items.push({ date: tl.contract.c_start, order: 1, title: 'สร้างสัญญาเช่า', desc: `${tl.contract.c_number} · ${end}`, kind: '' });
        }

        if (r.rt_moveout) {
            items.push({ date: r.rt_moveout, order: 3, kind: 'end', title: 'ย้ายออก', desc: `ห้อง ${roomName}` });
        }
        // สัญญาสิ้นสุด — backend ตั้ง c_status = ENDED ตอนย้ายออก (ไม่ได้แก้ c_end) จึงใช้วันย้ายออกเป็นหลัก
        if (tl.contract?.c_status === 'ENDED') {
            const endDate = r.rt_moveout || tl.contract.c_end;
            if (endDate) items.push({ date: endDate, order: 4, kind: 'end', title: 'สัญญาสิ้นสุด', desc: tl.contract.c_number || '' });
        }

        items.sort((a, b) => (a.date === b.date ? a.order - b.order : a.date < b.date ? -1 : 1));
        return items;
    }

    function renderTimeline() {
        const items = buildTimeline();
        const list = $('timeline-list');
        if (!items.length) {
            list.innerHTML = '<li class="rt-muted" style="font-size:0.875rem;">ยังไม่มีประวัติ</li>';
            return;
        }
        list.innerHTML = items.map((i) => `
            <li>
                <span class="rt-dot ${i.kind ? 'rt-dot--' + i.kind : ''}"></span>
                <div>
                    <div><span class="rt-tl-date">${escapeHtml(i.date)}</span><span class="rt-tl-title">${escapeHtml(i.title)}</span></div>
                    ${i.desc ? `<div class="rt-tl-desc">${escapeHtml(i.desc)}</div>` : ''}
                    ${i.reason ? `<div class="rt-tl-reason">เหตุผล: ${escapeHtml(i.reason)}</div>` : ''}
                </div>
            </li>
        `).join('');
    }

    $('detail-btn-retry').addEventListener('click', loadDetail);

    loadDetail();
})();
</script>
</x-layouts::app>
