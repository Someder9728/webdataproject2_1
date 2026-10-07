{{-- resources/views/invoices/history.blade.php --}}
{{-- ประวัติการชำระเงิน = ใบแจ้งหนี้ที่ชำระแล้ว (GET /api/v1/invoices?status=PAID) — backend ยังไม่มี endpoint รวมประวัติการชำระทั้งระบบ
     ประวัติรายละเอียดของแต่ละใบ (ส่งหลักฐาน/อนุมัติ/ปฏิเสธ) ดูได้ที่หน้ารายละเอียดใบแจ้งหนี้ --}}
<x-layouts::app :title="__('ประวัติการชำระเงิน')">
@include('invoices._base')

<div class="rt rt-page" id="iv-root" data-admin="{{ auth()->user()?->u_role === 'admin' ? '1' : '0' }}">
    <div class="rt-head">
        <div>
            <div class="rt-crumb"><a href="{{ route('invoices.index') }}">ค่าเช่าและการชำระเงิน</a> / ประวัติ</div>
            <h1 class="rt-title">ประวัติการชำระเงิน</h1>
            <div class="rt-subtitle" id="hs-subtitle">&nbsp;</div>
        </div>
        <div class="rt-head-actions">
            <a href="{{ route('invoices.index') }}" class="rt-btn">‹ ย้อนกลับ</a>
        </div>
    </div>

    <div id="hs-loading" class="rt-card rt-state">กำลังโหลดข้อมูล...</div>
    <div id="hs-error" class="rt-state rt-state--error" hidden>
        <span id="hs-error-message">โหลดข้อมูลไม่สำเร็จ</span>
        <button type="button" id="hs-retry" class="rt-btn rt-btn--sm">ลองใหม่</button>
    </div>
    <div id="hs-trunc" class="rt-notice rt-notice--warn" hidden>แสดงเฉพาะ 500 รายการล่าสุด — เลือกรอบบิลเป็นรายเดือนเพื่อดูรายการที่เหลือ</div>

    <section class="rt-card rt-card--flush" id="hs-card" hidden aria-label="ประวัติการชำระเงิน">
        <div class="iv-toolbar">
            <input type="search" id="hs-search" class="rt-search" placeholder="ค้นหาห้อง, ผู้เช่า..." aria-label="ค้นหา">
            <input type="month" id="hs-month" class="rt-input" aria-label="รอบบิลเดือน" placeholder="YYYY-MM">
            <button type="button" class="rt-btn rt-btn--sm" id="hs-month-clear" hidden>ทุกรอบบิล</button>
        </div>
        <div class="rt-table-wrap">
            <table class="rt-table iv-table" style="min-width:820px;">
                <thead>
                    <tr>
                        @if (auth()->user()?->u_role === 'admin')
                            <th>ผู้เช่า</th>
                        @endif
                        <th>ห้อง</th>
                        <th>รอบบิล</th>
                        <th class="num">จำนวนเงิน</th>
                        <th>วันที่ชำระ</th>
                        <th>วิธีชำระ</th>
                        <th>สถานะ</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="hs-body"></tbody>
            </table>
        </div>
        <div id="hs-empty" class="rt-state" hidden></div>
    </section>
</div>

<script>
(function () {
    const { $, el } = IV;
    const IS_ADMIN = $('iv-root').dataset.admin === '1';
    let all = [];
    let seq = 0;
    let timer = null;

    const monthOk = (v) => /^\d{4}-\d{2}$/.test(v);

    function showState(which) {
        $('hs-loading').hidden = which !== 'loading';
        $('hs-error').hidden = which !== 'error';
        $('hs-card').hidden = which !== 'ready';
    }

    async function load() {
        const my = ++seq;
        showState('loading');
        const month = $('hs-month').value;
        const res = await IV.fetchAll('/api/v1/invoices', { status: 'PAID', ...(monthOk(month) ? { month } : {}) }, 5);
        if (my !== seq) return;
        if (!res.ok) {
            IV.setText('hs-error-message', res.message);
            showState('error');
            return;
        }
        // ใหม่สุดก่อน: วันที่ชำระ แล้วตามด้วยเลขใบแจ้งหนี้
        all = res.rows
            .filter((i) => i.payment?.p_status === 'PAID')
            .sort((a, b) => IV.day(b.payment.p_date).localeCompare(IV.day(a.payment.p_date)) || Number(b.i_id) - Number(a.i_id));
        $('hs-trunc').hidden = !res.truncated;
        $('hs-month-clear').hidden = !monthOk(month);
        showState('ready');
        render();
    }

    function rowFor(inv) {
        const p = inv.payment;
        return el('tr', null,
            IS_ADMIN ? el('td', { text: IV.tenantName(inv) }) : null,
            el('td', null, el('span', { class: 'rt-badge rt-badge--info', text: 'ห้อง ' + IV.roomName(inv) })),
            el('td', { text: IV.monthKey(inv) || '-' }),
            el('td', { class: 'num' }, el('b', { text: IV.money(p.p_amount ?? inv.i_total) })),
            el('td', { text: IV.day(p.p_date) || '-' }),
            el('td', { text: IV.methodLabel(p.p_type) }),
            el('td', null, IV.badge(inv)),
            el('td', { class: 'rt-actions' }, el('a', {
                class: 'iv-icon-btn', href: `/invoices/${inv.i_id}`, title: 'ดูรายละเอียด',
                'aria-label': `ดูรายละเอียดใบแจ้งหนี้ ${IV.invNo(inv)}`,
            }, IV.icon('eye')))
        );
    }

    function render() {
        const q = $('hs-search').value.trim().toLowerCase();
        const rows = all.filter((i) => !q || [IV.roomName(i), IV.tenantName(i), IV.invNo(i)].join(' ').toLowerCase().includes(q));
        $('hs-body').replaceChildren(...rows.map(rowFor));
        const total = rows.reduce((s, i) => s + Number(i.payment?.p_amount ?? i.i_total ?? 0), 0);
        IV.setText('hs-subtitle', `${rows.length} รายการ · รวม ${IV.money(total)}`);
        const empty = $('hs-empty');
        empty.hidden = rows.length > 0;
        if (!rows.length) empty.textContent = all.length ? 'ไม่พบรายการที่ตรงกับคำค้นหา' : 'ยังไม่มีประวัติการชำระเงิน';
    }

    $('hs-search').addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(render, 150); });
    $('hs-month').addEventListener('change', load);
    $('hs-month-clear').addEventListener('click', () => { $('hs-month').value = ''; load(); });
    $('hs-retry').addEventListener('click', load);
    load();
})();
</script>
</x-layouts::app>
