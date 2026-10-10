{{-- resources/views/rentals/mine.blade.php --}}
{{-- ผู้เช่า: การเช่าของฉัน — การเช่าปัจจุบัน (การ์ด) + ประวัติการเช่า (ตาราง)
     เรียก GET /api/v1/rentals (API คืนเฉพาะการเช่าของผู้ที่ล็อกอิน)
     และ GET /api/v1/rentals/{id}/contract ของการเช่าปัจจุบัน เพื่อเอาค่าเช่า/เงินประกัน/ช่วงสัญญา --}}

<x-layouts::app :title="__('การเช่าของฉัน')">
@include('rentals._my-styles')

<div class="rt rt-page">
    <div class="rt-head">
        <div>
            <div class="rt-crumb">การเช่าของฉัน</div>
            <h1 class="rt-title">การเช่าของฉัน</h1>
            <div class="rt-subtitle" id="my-summary">&nbsp;</div>
        </div>
        <div class="rt-head-actions">
            <a href="{{ route('my.contracts') }}" class="rt-btn">สัญญาของฉัน</a>
            <a href="{{ route('invoices.index') }}" class="rt-btn">ค่าเช่าและการชำระเงิน</a>
        </div>
    </div>

    <div id="my-state-loading" class="rt-card rt-state">กำลังโหลดข้อมูล...</div>
    <div id="my-state-error" class="rt-state rt-state--error" hidden>
        <span id="my-error-message"></span>
        <button type="button" id="my-btn-retry" class="rt-btn rt-btn--sm">ลองใหม่</button>
    </div>

    <div id="my-view" hidden>
        <h2 class="my-section-title">การเช่าปัจจุบัน</h2>
        <div id="my-current" class="my-current"></div>
        <div id="my-current-empty" class="rt-card rt-state" hidden>ตอนนี้ไม่มีการเช่าที่กำลังใช้งาน</div>

        <h2 class="my-section-title">ประวัติการเช่า</h2>
        <section class="rt-card rt-card--flush" aria-label="ประวัติการเช่า">
            <div class="rt-table-wrap">
                <table class="rt-table">
                    <thead>
                        <tr><th>ห้อง</th><th>วันเข้าพัก</th><th>วันย้ายออก</th><th>สถานะ</th><th></th></tr>
                    </thead>
                    <tbody id="my-history-body"></tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<script>
(function () {
    const API_RENTALS = '/api/v1/rentals?per_page=100';
    const fetchOpts = { headers: { Accept: 'application/json' }, credentials: 'same-origin' };

    // ค่าสถานะยืนยันกับ backend แล้ว
    const RENTAL_STATUS = {
        ACTIVE: { text: 'กำลังเช่า', cls: 'rt-badge--ok' },
        ENDED:  { text: 'สิ้นสุดแล้ว', cls: 'rt-badge--off' },
    };
    const CONTRACT_STATUS = {
        ACTIVE:  { text: 'มีผล', cls: 'rt-badge--info' },
        EXPIRED: { text: 'หมดอายุ (ยังพักอยู่)', cls: 'rt-badge--warn' },
        ENDED:   { text: 'สิ้นสุด', cls: 'rt-badge--off' },
    };

    const $ = (id) => document.getElementById(id);
    const el = {
        summary: $('my-summary'),
        loading: $('my-state-loading'),
        error: $('my-state-error'),
        errorMsg: $('my-error-message'),
        retry: $('my-btn-retry'),
        view: $('my-view'),
        current: $('my-current'),
        currentEmpty: $('my-current-empty'),
        historyBody: $('my-history-body'),
    };

    let seq = 0; // ใช้เฉพาะคำตอบของการโหลดครั้งล่าสุด

    function showOnly(name) {
        ['loading', 'error', 'view'].forEach((k) => { el[k].hidden = k !== name; });
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    const money = (n) => '฿' + Number(n).toLocaleString('th-TH', { maximumFractionDigits: 2 });

    function badge(map, key) {
        const s = map[key] || { text: key || '-', cls: 'rt-badge--off' };
        return `<span class="rt-badge ${s.cls}">${escapeHtml(s.text)}</span>`;
    }

    // อ่านสัญญาของการเช่าหนึ่งรายการ (ไม่เจอ/ผิดพลาด → null แล้วการ์ดแสดง "-")
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
            if (!res.ok) throw new Error('โหลดข้อมูลการเช่าไม่สำเร็จ (' + res.status + ')');
            const body = await res.json();
            const rentals = body.data ?? [];

            const current = rentals.filter((r) => r.rt_status === 'ACTIVE');
            const history = rentals.filter((r) => r.rt_status !== 'ACTIVE');
            // ต้องใช้ค่าเช่า/เงินประกัน/ช่วงสัญญาจาก contract ของการเช่าปัจจุบัน
            const contracts = await Promise.all(current.map((r) => fetchContract(r.rt_id)));
            if (mySeq !== seq) return;

            render(current, contracts, history);
        } catch (err) {
            if (mySeq !== seq) return;
            el.errorMsg.textContent = err.message || 'โหลดข้อมูลไม่สำเร็จ';
            showOnly('error');
        }
    }

    function render(current, contracts, history) {
        el.summary.textContent = `ปัจจุบัน ${current.length} · ประวัติ ${history.length} · แสดงเฉพาะข้อมูลของฉัน`;

        el.current.innerHTML = current.map((r, i) => currentCardHtml(r, contracts[i])).join('');
        el.currentEmpty.hidden = current.length > 0;

        el.historyBody.innerHTML = history.length
            ? history.map(historyRowHtml).join('')
            : '<tr><td colspan="5" class="rt-muted" style="text-align:center;">ยังไม่มีประวัติการเช่า</td></tr>';

        showOnly('view');
    }

    function currentCardHtml(r, c) {
        const room = r.room || {};
        const sub = [room.r_floor != null ? `ชั้น ${room.r_floor}` : null, room.r_type].filter(Boolean).join(' · ');
        const contractNo = c?.c_number ?? r.contract?.c_number ?? '-';
        const period = c
            ? `${escapeHtml(c.c_start)} → ${c.c_end ? escapeHtml(c.c_end) : 'ไม่กำหนดวันสิ้นสุด'}`
            : '-';
        return `
            <section class="rt-card" aria-label="การเช่าห้อง ${escapeHtml(room.r_name)}">
                <div class="my-current-head">
                    <div>
                        <div class="my-room">
                            <p class="my-room-name">ห้อง ${escapeHtml(room.r_name ?? '-')}</p>
                            ${badge(RENTAL_STATUS, r.rt_status)}
                        </div>
                        <div class="my-room-sub">${escapeHtml(sub || '-')}</div>
                    </div>
                    <div class="my-rent">
                        <div class="my-rent-label">ค่าเช่ารายเดือน</div>
                        <div class="my-rent-value">${c?.c_rent != null ? money(c.c_rent) : '-'}</div>
                    </div>
                </div>
                <div class="my-fields" style="margin-top:16px;">
                    <div><span class="rt-field-label">วันที่เริ่มเช่า</span><span class="rt-field-value">${escapeHtml(r.rt_movein ?? '-')}</span></div>
                    <div><span class="rt-field-label">วันที่สิ้นสุด</span><span class="rt-field-value">${r.rt_moveout ? escapeHtml(r.rt_moveout) : 'ต่อเนื่อง'}</span></div>
                    <div><span class="rt-field-label">เลขสัญญา</span><span class="rt-field-value rt-mono">${escapeHtml(contractNo)}</span></div>
                    <div><span class="rt-field-label">เงินประกัน</span><span class="rt-field-value">${c?.c_deposit != null ? money(c.c_deposit) : '-'}</span></div>
                </div>
                <div class="my-contract-row" style="margin-top:14px;">
                    <div>
                        <span class="rt-field-label">ข้อมูลสัญญา</span>
                        <span class="rt-field-value rt-mono">${escapeHtml(contractNo)}</span>
                    </div>
                    <div class="my-contract-period">
                        <span>${period}</span>
                        ${c ? badge(CONTRACT_STATUS, c.c_status) : ''}
                        <a href="/rentals/${r.rt_id}" class="rt-btn rt-btn--sm rt-btn--soft">ดูรายละเอียด</a>
                    </div>
                </div>
            </section>
        `;
    }

    function historyRowHtml(r) {
        const room = r.room || {};
        const label = [room.r_name ? `ห้อง ${room.r_name}` : '-', room.r_type].filter(Boolean).join(' · ');
        return `
            <tr>
                <td>${escapeHtml(label)}</td>
                <td>${escapeHtml(r.rt_movein ?? '-')}</td>
                <td>${escapeHtml(r.rt_moveout ?? '-')}</td>
                <td>${badge(RENTAL_STATUS, r.rt_status)}</td>
                <td class="rt-actions"><a href="/rentals/${r.rt_id}" class="rt-btn rt-btn--sm rt-btn--soft">ดูรายละเอียด</a></td>
            </tr>
        `;
    }

    el.retry.addEventListener('click', load);

    load();
})();
</script>
</x-layouts::app>
