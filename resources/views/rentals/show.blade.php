<x-layouts::app :title="__('รายละเอียดการเช่า')">
@include('rentals._styles')
@php($backUrl = auth()->user()?->u_role === 'admin' ? route('rentals.index') : route('my.rentals'))
@php($backLabel = auth()->user()?->u_role === 'admin' ? 'การเช่า' : 'การเช่าของฉัน')

<div class="rt rt-page" id="rental-detail-page" data-rental-id="{{ $rentalId }}">
    <div id="detail-state-loading" class="rt-card rt-state">กำลังโหลดข้อมูล...</div>
    <div id="detail-state-error" class="rt-state rt-state--error" hidden>
        <span id="detail-error-message"></span>
        <button type="button" id="detail-btn-retry" class="rt-btn rt-btn--sm">ลองใหม่</button>
        <a href="{{ $backUrl }}" class="rt-btn rt-btn--sm">← กลับ</a>
    </div>

    <div id="detail-view" hidden>
        <div class="rt-head">
            <div>
                <div class="rt-crumb"><a href="{{ $backUrl }}">{{ $backLabel }}</a> / <span id="d-crumb">-</span></div>
                <h1 class="rt-title" id="detail-title">การเช่า</h1>
            </div>
            <div class="rt-head-actions">
                @if (auth()->user()?->u_role === 'admin')
                    <button type="button" id="btn-move-out" class="rt-btn rt-btn--danger" data-action="move-out"
                            data-rental-id="{{ $rentalId }}" hidden>ย้ายออก</button>
                @endif
                <a href="{{ $backUrl }}" class="rt-btn">‹ ย้อนกลับ</a>
            </div>
        </div>

        <div class="rt-grid">
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

                @include('rentals.contract', ['rentalId' => $rentalId])

                <section class="rt-card rt-card--flush" aria-labelledby="timeline-title">
                    <div class="rt-card-head"><h2 class="rt-card-title rt-card-title--lg" id="timeline-title">ประวัติการเช่า</h2></div>
                    <div style="padding:16px 20px;">
                        <ul class="rt-timeline" id="timeline-list"></ul>
                        <div class="rt-tl-note" id="history-note" hidden>
                            <span id="history-note-text"></span>
                            <button type="button" id="history-retry" class="rt-btn rt-btn--sm" hidden>ลองใหม่</button>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    @if (auth()->user()?->u_role === 'admin')
        @include('rentals._move-out-modal')
    @endif
</div>

<script>
(function () {
    const page = document.getElementById('rental-detail-page');
    const rentalId = page.dataset.rentalId;
    const API_URL = `/api/v1/rentals/${rentalId}`;
    const IS_ADMIN = @json(auth()->user()?->u_role === 'admin');
    const HISTORY_URL = `/api/v1/rentals/${rentalId}/history`;
    const HISTORY_PER_PAGE = 100;
    const HISTORY_MAX_PAGES = 10;
    const fetchOpts = { headers: { Accept: 'application/json' }, credentials: 'same-origin' };

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

    const tl = { rental: null, contract: null, history: [], historyComplete: false };

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

        set('d-avatar', tenantName !== '-' ? tenantName.charAt(0) : '?');
        set('d-tenant', tenantName);
        set('d-tenant-code', r.tenant ? 'TN-' + String(r.tenant.t_id).padStart(3, '0') : '-');
        set('d-tel', r.tenant?.t_tel);
        set('d-mail', '-');

        set('d-room', roomName);
        set('d-floor', r.room?.r_floor != null ? `ชั้น ${r.room.r_floor}` : '-');
        set('d-type', r.room?.r_type);
        set('d-room-rent', '-');
        const rs = ROOM_STATUS[r.room?.r_status];
        $('d-room-status').innerHTML = rs
            ? `<span class="rt-badge ${rs.cls}">${rs.text}</span>`
            : escapeHtml(r.room?.r_status ?? '-');

        const st = STATUS_LABEL[r.rt_status] || { text: r.rt_status, long: r.rt_status, cls: 'rt-badge--off' };
        set('d-movein', r.rt_movein);
        set('d-moveout', r.rt_moveout);
        set('d-status-text', st.long);
        $('d-status').textContent = st.text;
        $('d-status').className = 'rt-badge ' + st.cls;

        const moveOutBtn = $('btn-move-out');
        if (moveOutBtn) {
            moveOutBtn.hidden = r.rt_status !== 'ACTIVE';
            moveOutBtn.onclick = () => window.RentalMoveOut.open({
                rentalId: r.rt_id,
                tenantName,
                roomName,
                roomId: r.room?.r_id ?? null,
                moveIn: r.rt_movein,
                onDone: () => window.location.reload(),
            });
        }

        $('detail-state-loading').hidden = true;
        $('detail-view').hidden = false;

        renderTimeline();

        window.__rentalInfo = r;
        document.dispatchEvent(new CustomEvent('rental:loaded', { detail: r }));

        if (r.tenant?.t_id) loadTenantExtra(r.tenant.t_id);
        if (r.room?.r_id) loadRoomExtra(r.room.r_id);
        if (IS_ADMIN) loadHistory();

        if (window.__rentalContract) onContract(window.__rentalContract);
    }

    async function loadTenantExtra(tenantId) {
        try {
            const res = await fetch(`/api/v1/tenants/${tenantId}`, fetchOpts);
            if (!res.ok) return;
            const body = await res.json();
            set('d-mail', body.data?.t_mail);
        } catch (_) {  }
    }

    async function loadRoomExtra(roomId) {
        try {
            const res = await fetch(`/api/v1/rooms/${roomId}`, fetchOpts);
            if (!res.ok) return;
            const body = await res.json();
            if (body.data?.r_rent != null) set('d-room-rent', money(body.data.r_rent) + '/เดือน');
        } catch (_) {  }
    }

    function onContract(c) {
        tl.contract = c;
        renderTimeline();
    }
    document.addEventListener('rental:contract-loaded', (e) => onContract(e.detail));
    document.addEventListener('rental:contract-updated', (e) => {
        onContract(e.detail);
        if (IS_ADMIN) loadHistory();
    });

    let historySeq = 0;

    function showHistoryNote(text, canRetry) {
        $('history-note').hidden = !text;
        $('history-note-text').textContent = text || '';
        $('history-retry').hidden = !canRetry;
    }

    async function loadHistory() {
        const mySeq = ++historySeq;
        showHistoryNote('กำลังโหลดประวัติการแก้ไขสัญญา...', false);
        try {
            const events = [];
            let page = 1;
            let lastPage = 1;
            do {
                const res = await fetch(`${HISTORY_URL}?page=${page}&per_page=${HISTORY_PER_PAGE}`, fetchOpts);
                if (mySeq !== historySeq) return;
                if (res.status === 401) { window.location.href = '/login'; return; }
                if (res.status === 403) { showHistoryNote('', false); return; }
                if (!res.ok) throw new Error(String(res.status));
                const body = await res.json();
                events.push(...(body.data ?? []));
                lastPage = body.meta?.last_page ?? 1;
                page += 1;
            } while (page <= lastPage && page <= HISTORY_MAX_PAGES);
            if (mySeq !== historySeq) return;

            tl.history = events;
            tl.historyComplete = lastPage <= HISTORY_MAX_PAGES;
            renderTimeline();
            showHistoryNote(lastPage > HISTORY_MAX_PAGES
                ? `แสดงประวัติการแก้ไขสัญญาล่าสุด ${events.length.toLocaleString('th-TH')} รายการ (ยังมีรายการเก่ากว่านี้)`
                : '', false);
        } catch (_) {
            if (mySeq !== historySeq) return;
            showHistoryNote('โหลดประวัติการแก้ไขสัญญาไม่สำเร็จ', true);
        }
    }
    $('history-retry').addEventListener('click', loadHistory);

    const CONTRACT_STATUS_TEXT = { ACTIVE: 'มีผล', EXPIRED: 'หมดอายุ', ENDED: 'สิ้นสุด' };
    const endText = (d) => d || 'ไม่กำหนด';

    function historyItem(ev) {
        const oldV = ev.old_values || {};
        const newV = ev.new_values || {};
        const hasOldEnd = 'c_end' in oldV;
        const hasNewEnd = 'c_end' in newV;

        let title = 'แก้ไขสัญญา';
        if (ev.action === 'CONTRACT_EXTENDED') title = 'ต่อสัญญา';
        else if (hasOldEnd && hasNewEnd) {
            if (oldV.c_end && newV.c_end) {
                if (newV.c_end > oldV.c_end) title = 'ต่อสัญญา';
                else if (newV.c_end < oldV.c_end) title = 'แก้วันสิ้นสุดสัญญา';
            }
            else if (!newV.c_end && oldV.c_end) title = 'เปลี่ยนเป็นไม่กำหนดวันสิ้นสุด';
            else if (newV.c_end && !oldV.c_end) title = 'กำหนดวันสิ้นสุดสัญญา';
        }

        const parts = [];
        if (hasOldEnd || hasNewEnd) {
            parts.push(`วันสิ้นสุด ${hasOldEnd ? endText(oldV.c_end) : '?'} → ${hasNewEnd ? endText(newV.c_end) : '?'}`);
        }
        if (oldV.c_status && newV.c_status && oldV.c_status !== newV.c_status) {
            const st = (k) => CONTRACT_STATUS_TEXT[k] || k;
            parts.push(`สถานะ ${st(oldV.c_status)} → ${st(newV.c_status)}`);
        }

        const at = String(ev.created_at || '');
        return {
            date: at.slice(0, 10),
            time: at.slice(11, 16),
            order: 2,
            seq: ev.ae_id,
            kind: 'edit',
            title,
            desc: parts.join(' · '),
            by: ev.actor?.u_username ? `โดย ${ev.actor.u_username}` : 'ไม่พบข้อมูลผู้ดำเนินการ',
            reason: ev.reason ?? 'ไม่ได้ระบุ',
        };
    }

    function buildTimeline() {
        const items = [];
        const r = tl.rental;
        if (!r) return items;

        const roomName = r.room?.r_name ?? '-';
        if (r.rt_movein) {
            items.push({ date: r.rt_movein, order: 0, title: 'ย้ายเข้า', desc: `ห้อง ${roomName}`, kind: '' });
        }
        if (tl.contract?.c_start) {
            const oldest = tl.historyComplete ? tl.history[tl.history.length - 1] : null;
            const firstEnd = oldest && 'c_end' in (oldest.old_values || {}) ? oldest.old_values.c_end : tl.contract.c_end;
            const end = firstEnd ? `สิ้นสุด ${firstEnd}` : 'ไม่กำหนดวันสิ้นสุด';
            items.push({ date: tl.contract.c_start, order: 1, title: 'สร้างสัญญาเช่า', desc: `${tl.contract.c_number} · ${end}`, kind: '' });
        }

        if (r.rt_moveout) {
            items.push({ date: r.rt_moveout, order: 3, kind: 'end', title: 'ย้ายออก', desc: `ห้อง ${roomName}` });
        }
        if (tl.contract?.c_status === 'ENDED') {
            const endDate = r.rt_moveout || tl.contract.c_end;
            if (endDate) items.push({ date: endDate, order: 4, kind: 'end', title: 'สัญญาสิ้นสุด', desc: tl.contract.c_number || '' });
        }

        tl.history.forEach((ev) => items.push(historyItem(ev)));

        items.sort((a, b) => {
            if (a.date !== b.date) return a.date < b.date ? -1 : 1;
            if (a.order !== b.order) return a.order - b.order;
            if ((a.time || '') !== (b.time || '')) return (a.time || '') < (b.time || '') ? -1 : 1;
            return (a.seq || 0) - (b.seq || 0);
        });
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
                    <div><span class="rt-tl-date">${escapeHtml(i.date)}${i.time ? ' ' + escapeHtml(i.time) : ''}</span><span class="rt-tl-title">${escapeHtml(i.title)}</span></div>
                    ${i.desc ? `<div class="rt-tl-desc">${escapeHtml(i.desc)}</div>` : ''}
                    ${i.by ? `<div class="rt-tl-desc">${escapeHtml(i.by)}</div>` : ''}
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
