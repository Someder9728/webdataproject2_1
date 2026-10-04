{{-- resources/views/invoices/index.blade.php --}}
{{-- ใบแจ้งหนี้ / การชำระเงิน (รายการ) — Admin เห็นทั้งหมด, Tenant เห็นเฉพาะของตัวเอง (กรองที่ API)
     เรียก GET /api/v1/invoices (month ส่งไปกรองที่ API, ค้นหา/สถานะกรองในหน้า) --}}
<x-layouts::app :title="__('ใบแจ้งหนี้ / การชำระ')">
@include('invoices._base')

<div class="rt rt-page" id="iv-root" data-admin="{{ auth()->user()?->u_role === 'admin' ? '1' : '0' }}">
    {{-- กล่องโต้ตอบต้องอยู่ "ภายใน" .rt เพื่อให้ได้ตัวแปรสี (--rt-*) ของธีม --}}
    @include('invoices._pay-modals')
    @if (auth()->user()?->u_role === 'admin')
        @include('invoices._issue-modal')
    @endif

    <div class="rt-head">
        <div>
            <div class="rt-crumb">ใบแจ้งหนี้ / การชำระ</div>
            <h1 class="rt-title">ค่าเช่าและการชำระเงิน</h1>
            <div class="rt-subtitle" id="iv-subtitle">&nbsp;</div>
        </div>
        <div class="rt-head-actions">
            <a href="{{ route('invoices.history') }}" class="rt-btn">ประวัติการชำระเงิน</a>
            @if (auth()->user()?->u_role === 'admin')
                <button type="button" class="rt-btn rt-btn--primary" id="btn-issue">+ ออกใบแจ้งหนี้</button>
            @endif
        </div>
    </div>

    <div class="iv-stats" id="iv-stats" hidden>
        <div class="iv-stat">
            <span class="iv-stat-icon iv-stat-icon--total" id="ic-total"></span>
            <div><div class="iv-stat-label">ยอดรวมทั้งหมด</div><div class="iv-stat-value" id="st-total">-</div><div class="iv-stat-sub" id="st-total-sub"></div></div>
        </div>
        <div class="iv-stat">
            <span class="iv-stat-icon iv-stat-icon--ok" id="ic-paid"></span>
            <div><div class="iv-stat-label">ชำระแล้ว</div><div class="iv-stat-value" id="st-paid">-</div><div class="iv-stat-sub" id="st-paid-sub"></div></div>
        </div>
        <div class="iv-stat">
            <span class="iv-stat-icon iv-stat-icon--warn" id="ic-waiting"></span>
            <div><div class="iv-stat-label">รอชำระ</div><div class="iv-stat-value" id="st-waiting">-</div><div class="iv-stat-sub" id="st-waiting-sub"></div></div>
        </div>
        <div class="iv-stat">
            <span class="iv-stat-icon iv-stat-icon--err" id="ic-overdue"></span>
            <div><div class="iv-stat-label">ค้างชำระ</div><div class="iv-stat-value" id="st-overdue">-</div><div class="iv-stat-sub" id="st-overdue-sub"></div></div>
        </div>
    </div>

    <div id="iv-trunc" class="rt-notice rt-notice--warn" hidden>แสดงเฉพาะ 500 รายการล่าสุด — กรุณาเลือกรอบบิลเป็นรายเดือนเพื่อดูรายการที่เหลือ และตัวเลขสรุปด้านบนอาจไม่ครบ</div>

    <div id="iv-state-loading" class="rt-card rt-state">กำลังโหลดข้อมูล...</div>
    <div id="iv-state-error" class="rt-state rt-state--error" hidden>
        <span id="iv-error-message">โหลดข้อมูลไม่สำเร็จ</span>
        <button type="button" id="iv-retry" class="rt-btn rt-btn--sm">ลองใหม่</button>
    </div>

    <section class="rt-card rt-card--flush" id="iv-card" hidden aria-label="รายการใบแจ้งหนี้">
        <div class="iv-toolbar">
            <input type="search" id="iv-search" class="rt-search" placeholder="ค้นหาห้อง, ผู้เช่า, เลขที่ใบแจ้งหนี้..." aria-label="ค้นหา">
            <input type="month" id="iv-month" class="rt-input" aria-label="รอบบิลเดือน" placeholder="YYYY-MM">
            <button type="button" class="rt-btn rt-btn--sm" id="iv-month-clear" hidden>ทุกรอบบิล</button>
            <select id="iv-status" class="rt-input" aria-label="สถานะ">
                <option value="all">สถานะทั้งหมด</option>
                <option value="unpaid">รอชำระ</option>
                <option value="overdue">ค้างชำระ</option>
                <option value="pending">รอตรวจสอบ</option>
                <option value="rejected">ถูกปฏิเสธ</option>
                <option value="paid">ชำระแล้ว</option>
            </select>
        </div>
        <div class="rt-table-wrap">
            <table class="rt-table iv-table">
                <thead>
                    <tr>
                        <th>ห้อง</th>
                        @if (auth()->user()?->u_role === 'admin')
                            <th>ผู้เช่า</th>
                        @endif
                        <th>รอบบิล</th>
                        <th class="num">ค่าเช่า</th>
                        <th class="num">ค่าน้ำ</th>
                        <th class="num">ค่าไฟ</th>
                        <th class="num">รวม</th>
                        <th>วันครบกำหนด</th>
                        <th>สถานะ</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="iv-body"></tbody>
            </table>
        </div>
        <div id="iv-empty" class="rt-state" hidden></div>
    </section>
</div>

<script>
(function () {
    const { $, el } = IV;
    const root = $('iv-root');
    const IS_ADMIN = root.dataset.admin === '1';
    let all = [];
    let loadSeq = 0;
    let searchTimer = null;

    // ไอคอนของการ์ดสรุป
    [['ic-total', 'card'], ['ic-paid', 'check'], ['ic-waiting', 'clock'], ['ic-overdue', 'alert']]
        .forEach(([id, name]) => $(id).appendChild(IV.icon(name)));

    function showState(which) { // 'loading' | 'error' | 'ready'
        $('iv-state-loading').hidden = which !== 'loading';
        $('iv-state-error').hidden = which !== 'error';
        $('iv-card').hidden = which !== 'ready';
        $('iv-stats').hidden = which !== 'ready';
    }

    async function load(silent = false) {
        const my = ++loadSeq;
        if (!silent) showState('loading');
        const month = $('iv-month').value;
        const res = await IV.fetchAll('/api/v1/invoices', /^\d{4}-\d{2}$/.test(month) ? { month } : {}, 5);
        if (my !== loadSeq) return; // มีคำขอใหม่กว่าแล้ว
        if (!res.ok) {
            if (silent) { IV.toast(res.message, 'err'); return; }
            IV.setText('iv-error-message', res.message);
            showState('error');
            return;
        }
        all = res.rows;
        $('iv-trunc').hidden = !res.truncated;
        showState('ready');
        render();
    }

    function sum(list) { return list.reduce((s, i) => s + Number(i.i_total || 0), 0); }

    function renderStats() {
        const paid = all.filter((i) => IV.bucket(i) === 'paid');
        const overdue = all.filter((i) => IV.bucket(i) === 'overdue');
        const waiting = all.filter((i) => IV.bucket(i) === 'waiting');
        const pending = waiting.filter((i) => IV.statusKey(i) === 'pending').length;
        IV.setText('st-total', IV.money(sum(all)));
        IV.setText('st-total-sub', `${all.length} ใบ`);
        IV.setText('st-paid', IV.money(sum(paid)));
        IV.setText('st-paid-sub', `${paid.length} ใบ`);
        IV.setText('st-waiting', IV.money(sum(waiting)));
        IV.setText('st-waiting-sub', `${waiting.length} ใบ` + (pending ? ` · รอตรวจสอบ ${pending} ใบ` : ''));
        IV.setText('st-overdue', IV.money(sum(overdue)));
        IV.setText('st-overdue-sub', `${overdue.length} ใบ`);

        const month = $('iv-month').value;
        IV.setText('iv-subtitle', /^\d{4}-\d{2}$/.test(month) ? 'รอบบิลเดือน' + IV.thaiMonth(month) : 'ทุกรอบบิล');
        $('iv-month-clear').hidden = !/^\d{4}-\d{2}$/.test(month);
    }

    function matches(inv, q, status) {
        if (status !== 'all' && IV.statusKey(inv) !== status) return false;
        if (!q) return true;
        const hay = [IV.roomName(inv), IV.tenantName(inv), IV.invNo(inv)].join(' ').toLowerCase();
        return hay.includes(q);
    }

    function actionsFor(inv) {
        const cells = [el('a', {
            class: 'iv-icon-btn', href: `/invoices/${inv.i_id}`, title: 'ดูรายละเอียด',
            'aria-label': `ดูรายละเอียดใบแจ้งหนี้ห้อง ${IV.roomName(inv)}`,
        }, IV.icon('eye'))];
        const refresh = () => load(true);
        if (IS_ADMIN) {
            if (IV.canRecordPayment(inv)) {
                cells.push(el('button', {
                    type: 'button', class: 'rt-btn rt-btn--sm rt-btn--pay', text: 'ชำระ',
                    'aria-label': `บันทึกการชำระเงิน ห้อง ${IV.roomName(inv)}`,
                    onclick: () => window.InvoicePay.walkIn(inv.i_id, refresh),
                }));
            } else if (inv.payment?.p_status === 'PENDING') {
                cells.push(el('button', {
                    type: 'button', class: 'rt-btn rt-btn--sm', text: 'ตรวจสอบ',
                    'aria-label': `ตรวจสอบหลักฐาน ห้อง ${IV.roomName(inv)}`,
                    onclick: () => window.InvoicePay.review(inv.i_id, refresh),
                }));
            }
        } else if (IV.canRecordPayment(inv)) {
            cells.push(el('button', {
                type: 'button', class: 'rt-btn rt-btn--sm rt-btn--pay', text: 'ส่งหลักฐาน',
                'aria-label': `ส่งหลักฐานการชำระเงิน ห้อง ${IV.roomName(inv)}`,
                onclick: () => window.InvoicePay.submitProof(inv.i_id, refresh),
            }));
        }
        return cells;
    }

    function rowFor(inv) {
        const late = IV.statusKey(inv) === 'overdue';
        return el('tr', null,
            el('td', null, el('span', { class: 'iv-room', text: IV.roomName(inv) })),
            IS_ADMIN ? el('td', { text: IV.tenantName(inv) }) : null,
            el('td', { text: IV.monthKey(inv) || '-' }),
            el('td', { class: 'num', text: IV.money(inv.i_rent) }),
            el('td', { class: 'num', text: IV.money(inv.i_water) }),
            el('td', { class: 'num', text: IV.money(inv.i_elec) }),
            el('td', { class: 'num' }, el('b', { text: IV.money(inv.i_total) })),
            el('td', { class: late ? 'iv-due--late' : null, text: IV.day(inv.i_due) || '-' }),
            el('td', null, IV.badge(inv)),
            el('td', { class: 'rt-actions' }, actionsFor(inv))
        );
    }

    function render() {
        renderStats();
        const q = $('iv-search').value.trim().toLowerCase();
        const status = $('iv-status').value;
        const rows = all.filter((i) => matches(i, q, status));
        $('iv-body').replaceChildren(...rows.map(rowFor));
        const empty = $('iv-empty');
        if (rows.length) { empty.hidden = true; return; }
        empty.hidden = false;
        if (!all.length) {
            empty.textContent = IS_ADMIN ? 'ยังไม่มีใบแจ้งหนี้ — กด "+ ออกใบแจ้งหนี้" เพื่อสร้างใบแรก' : 'ยังไม่มีใบแจ้งหนี้';
        } else {
            empty.textContent = 'ไม่พบรายการที่ตรงกับเงื่อนไข';
        }
    }

    $('iv-search').addEventListener('input', () => { clearTimeout(searchTimer); searchTimer = setTimeout(render, 150); });
    $('iv-status').addEventListener('change', render);
    $('iv-month').addEventListener('change', () => load());
    $('iv-month-clear').addEventListener('click', () => { $('iv-month').value = ''; load(); });
    $('iv-retry').addEventListener('click', () => load());
    const issueBtn = $('btn-issue');
    if (issueBtn) issueBtn.addEventListener('click', () => window.InvoiceIssue.open(() => load(true)));

    load();
})();
</script>
</x-layouts::app>
