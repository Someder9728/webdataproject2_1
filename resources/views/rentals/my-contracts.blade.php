{{-- resources/views/rentals/my-contracts.blade.php --}}
{{-- ผู้เช่า: สัญญาของฉัน — การ์ดสัญญาทุกฉบับของผู้ที่ล็อกอิน (ใหม่สุดก่อน)
     เรียก GET /api/v1/rentals แล้ว GET /api/v1/rentals/{id}/contract ของแต่ละการเช่า (API ตรวจว่าเป็นของตัวเอง)
     หน้านี้อ่านอย่างเดียว — การแก้/ต่อสัญญาเป็นงานของผู้ดูแล --}}

<x-layouts::app :title="__('สัญญาของฉัน')">
@include('rentals._my-styles')

<div class="rt rt-page">
    <div class="rt-head">
        <div>
            <div class="rt-crumb"><a href="{{ route('my.rentals') }}">การเช่าของฉัน</a> / สัญญาของฉัน</div>
            <h1 class="rt-title">สัญญาของฉัน</h1>
            <div class="rt-subtitle"><span id="mc-summary"></span>ต้องการแก้ไขหรือต่อสัญญา กรุณาติดต่อผู้ดูแลหอพัก</div>
        </div>
    </div>

    <div id="mc-state-loading" class="rt-card rt-state">กำลังโหลดสัญญา...</div>
    <div id="mc-state-empty" class="rt-card rt-state" hidden>ยังไม่มีสัญญาเช่า</div>
    <div id="mc-state-error" class="rt-state rt-state--error" hidden>
        <span id="mc-error-message"></span>
        <button type="button" id="mc-btn-retry" class="rt-btn rt-btn--sm">ลองใหม่</button>
    </div>

    <div id="mc-list" class="my-contracts" hidden></div>
</div>

<script>
(function () {
    const API_RENTALS = '/api/v1/rentals?per_page=100';
    const fetchOpts = { headers: { Accept: 'application/json' }, credentials: 'same-origin' };

    // ค่า c_status ยืนยันกับ backend แล้ว: ACTIVE / EXPIRED / ENDED
    const CONTRACT_STATUS = {
        ACTIVE:  { text: 'มีผล', cls: 'rt-badge--info' },
        EXPIRED: { text: 'หมดอายุ (ยังพักอยู่)', cls: 'rt-badge--warn' },
        ENDED:   { text: 'สิ้นสุด', cls: 'rt-badge--off' },
    };

    const $ = (id) => document.getElementById(id);
    const el = {
        loading: $('mc-state-loading'),
        empty: $('mc-state-empty'),
        error: $('mc-state-error'),
        errorMsg: $('mc-error-message'),
        retry: $('mc-btn-retry'),
        list: $('mc-list'),
        summary: $('mc-summary'),
    };

    let seq = 0;

    function showOnly(name) {
        ['loading', 'empty', 'error', 'list'].forEach((k) => { el[k].hidden = k !== name; });
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    const money = (n) => '฿' + Number(n).toLocaleString('th-TH', { maximumFractionDigits: 2 });

    // วันที่จาก API เป็น 'YYYY-MM-DD' → นับเป็นจำนวนวันแบบ UTC เพื่อไม่ให้เพี้ยนตามเขตเวลา
    const DAY = 86400000;
    const toDay = (d) => Math.floor(Date.parse(d + 'T00:00:00Z') / DAY);
    const todayDay = () => { const n = new Date(); return Math.floor(Date.UTC(n.getFullYear(), n.getMonth(), n.getDate()) / DAY); };

    // ระยะสัญญา เช่น "36 เดือน" หรือ "1 เดือน 14 วัน" (นับรวมวันสุดท้าย)
    function contractLength(start, end) {
        const [y1, m1, d1] = start.split('-').map(Number);
        const [y2, m2, d2] = end.split('-').map(Number);
        let months = (y2 - y1) * 12 + (m2 - m1);
        if (d2 + 1 < d1) months -= 1;
        const days = toDay(end) - Math.floor(Date.UTC(y1, m1 - 1 + months, d1) / DAY) + 1;
        return [months > 0 ? `${months} เดือน` : '', days > 0 ? `${days} วัน` : ''].filter(Boolean).join(' ') || '-';
    }

    // ส่วนท้ายการ์ด: แถบความคืบหน้า (มีวันสิ้นสุด) หรือข้อความบอกว่าไม่กำหนดวันสิ้นสุด
    function progressHtml(c) {
        if (!c.c_start) return '';
        if (!c.c_end) {
            return `<div class="my-progress my-sub">สัญญาแบบไม่กำหนดวันสิ้นสุด — ใช้ได้ต่อเนื่องจนกว่าจะย้ายออก</div>`;
        }
        const start = toDay(c.c_start), end = toDay(c.c_end), now = todayDay();
        const total = Math.max(end - start + 1, 1);
        const used = Math.min(Math.max(now - start + 1, 0), total);
        const pct = Math.round((used / total) * 100);
        const left = end - now;
        let note;
        if (c.c_status === 'ENDED') note = 'สัญญาสิ้นสุดแล้ว';
        else if (left < 0) note = `เลยวันสิ้นสุดมา ${-left} วัน — กรุณาติดต่อผู้ดูแลเรื่องต่อสัญญา`;
        else if (left <= 30) note = `เหลืออีก ${left} วัน — ใกล้ครบกำหนด กรุณาติดต่อผู้ดูแลหากต้องการต่อสัญญา`;
        else note = `เหลืออีก ${left.toLocaleString('th-TH')} วัน`;
        return `
            <div class="my-progress ${c.c_status === 'ENDED' ? 'my-progress--off' : ''}">
                <div class="my-progress-top"><span>${escapeHtml(note)}</span><span>ผ่านไปแล้ว ${pct}%</span></div>
                <div class="my-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="${pct}"><span style="width:${pct}%"></span></div>
            </div>`;
    }

    async function fetchContract(rentalId) {
        try {
            const res = await fetch(`/api/v1/rentals/${rentalId}/contract`, fetchOpts);
            if (!res.ok) return null;
            const body = await res.json();
            return body.data ?? null;
        } catch (_) {
            return null;
        }
    }

    async function load() {
        const mySeq = ++seq;
        showOnly('loading');
        try {
            const res = await fetch(API_RENTALS, fetchOpts);
            if (res.status === 401) { window.location.href = '/login'; return; }
            if (!res.ok) throw new Error('โหลดข้อมูลไม่สำเร็จ (' + res.status + ')');
            const body = await res.json();
            const rentals = body.data ?? [];
            const contracts = await Promise.all(rentals.map((r) => fetchContract(r.rt_id)));
            if (mySeq !== seq) return;

            // จับคู่สัญญากับการเช่า (เอาห้อง/ประเภทจากการเช่า) แล้วเรียงสัญญาใหม่สุดก่อน
            const items = rentals
                .map((r, i) => ({ rental: r, contract: contracts[i] }))
                .filter((x) => x.contract)
                .sort((a, b) => (a.contract.c_start < b.contract.c_start ? 1 : a.contract.c_start > b.contract.c_start ? -1 : 0));

            const activeCount = items.filter((x) => x.contract.c_status !== 'ENDED').length;
            el.summary.textContent = items.length ? `ทั้งหมด ${items.length} ฉบับ · ยังมีผล ${activeCount} · ` : '';
            if (!items.length) { showOnly('empty'); return; }
            el.list.innerHTML = items.map(cardHtml).join('');
            showOnly('list');
        } catch (err) {
            if (mySeq !== seq) return;
            el.errorMsg.textContent = err.message || 'โหลดข้อมูลไม่สำเร็จ';
            showOnly('error');
        }
    }

    function cardHtml({ rental, contract: c }) {
        const room = rental.room || {};
        const s = CONTRACT_STATUS[c.c_status] || { text: c.c_status, cls: 'rt-badge--off' };
        const roomSub = [room.r_floor != null ? `ชั้น ${room.r_floor}` : null, room.r_type].filter(Boolean).join(' · ');
        const length = c.c_start && c.c_end ? contractLength(c.c_start, c.c_end) : null;
        return `
            <section class="rt-card" aria-label="สัญญา ${escapeHtml(c.c_number)}">
                <div class="my-contract-head">
                    <div>
                        <div class="my-contract-kicker">สัญญาเช่าห้องพัก</div>
                        <div class="my-contract-no"><span class="rt-mono">${escapeHtml(c.c_number ?? '-')}</span><span class="rt-badge ${s.cls}">${escapeHtml(s.text)}</span></div>
                    </div>
                    <a href="/rentals/${rental.rt_id}" class="rt-btn rt-btn--sm rt-btn--soft">ดูรายละเอียดการเช่า</a>
                </div>
                <div class="my-contract-grid">
                    <div>
                        <span class="rt-field-label">ห้อง</span>
                        <div class="my-big"><a href="/rentals/${rental.rt_id}">ห้อง ${escapeHtml(room.r_name ?? '-')}</a></div>
                        <div class="my-sub">${escapeHtml(roomSub)}</div>
                    </div>
                    <div>
                        <span class="rt-field-label">ระยะเวลาสัญญา</span>
                        <div class="rt-field-value">${escapeHtml(c.c_start ?? '-')}</div>
                        <div class="my-sub">${c.c_end ? 'ถึง ' + escapeHtml(c.c_end) : 'ไม่กำหนดวันสิ้นสุด'}${length ? ` · ${length}` : ''}</div>
                    </div>
                    <div>
                        <span class="rt-field-label">ค่าเช่ารายเดือน</span>
                        <div class="my-big">${c.c_rent != null ? money(c.c_rent) : '-'}</div>
                    </div>
                    <div>
                        <span class="rt-field-label">เงินประกัน</span>
                        <div class="rt-field-value">${c.c_deposit != null ? money(c.c_deposit) : '-'}</div>
                    </div>
                    <div>
                        <span class="rt-field-label">วันเข้าพัก</span>
                        <div class="rt-field-value">${escapeHtml(rental.rt_movein ?? '-')}</div>
                    </div>
                    <div>
                        <span class="rt-field-label">วันย้ายออก</span>
                        <div class="rt-field-value">${rental.rt_moveout ? escapeHtml(rental.rt_moveout) : 'ยังพักอยู่'}</div>
                    </div>
                </div>
                ${progressHtml(c)}
            </section>
        `;
    }

    el.retry.addEventListener('click', load);

    load();
})();
</script>
</x-layouts::app>
