{{-- resources/views/invoices/show.blade.php --}}
{{-- รายละเอียดใบแจ้งหนี้ + สถานะการชำระ + ประวัติการดำเนินการ
     ข้อมูลจาก GET /api/v1/invoices/{id} และ GET /api/v1/invoices/{id}/payment/events
     Admin: แก้ไขใบแจ้งหนี้ / บันทึกรับชำระ / ตรวจหลักฐาน — Tenant: ส่งหลักฐานการโอน --}}
<x-layouts::app :title="__('ใบแจ้งหนี้')">
@include('invoices._base')

<div class="rt rt-page" id="iv-root"
     data-admin="{{ auth()->user()?->u_role === 'admin' ? '1' : '0' }}"
     data-id="{{ $invoiceId }}">
    {{-- กล่องโต้ตอบต้องอยู่ "ภายใน" .rt เพื่อให้ได้ตัวแปรสี (--rt-*) ของธีม --}}
    @include('invoices._pay-modals')
    @if (auth()->user()?->u_role === 'admin')
        @include('invoices._edit-modal')
    @endif

    <div class="rt-head">
        <div>
            <div class="rt-crumb"><a href="{{ route('invoices.index') }}">ใบแจ้งหนี้ / การชำระ</a> / <span id="sh-crumb">…</span></div>
            <h1 class="rt-title" id="sh-title">ใบแจ้งหนี้</h1>
        </div>
        <div class="rt-head-actions">
            <button type="button" class="rt-btn" id="btn-edit" hidden>แก้ไขใบแจ้งหนี้</button>
            <button type="button" class="rt-btn rt-btn--pay" id="btn-walkin" hidden>บันทึกการชำระเงิน</button>
            <button type="button" class="rt-btn rt-btn--primary" id="btn-review" hidden>ตรวจสอบหลักฐาน</button>
            <button type="button" class="rt-btn rt-btn--primary" id="btn-submit" hidden>ส่งหลักฐานการชำระ</button>
            <a href="{{ route('invoices.index') }}" class="rt-btn">‹ ย้อนกลับ</a>
        </div>
    </div>

    <div id="sh-loading" class="rt-card rt-state">กำลังโหลดข้อมูล...</div>
    <div id="sh-error" class="rt-state rt-state--error" hidden>
        <span id="sh-error-message">โหลดข้อมูลไม่สำเร็จ</span>
        <button type="button" id="sh-retry" class="rt-btn rt-btn--sm">ลองใหม่</button>
    </div>

    <div class="rt-grid iv-grid" id="sh-content" hidden>
        {{-- ซ้าย: ตัวใบแจ้งหนี้ --}}
        <div class="rt-col">
            <section class="rt-card" aria-labelledby="sh-no-label">
                <div class="rt-card-head" style="align-items:flex-start;">
                    <div>
                        <div class="rt-field-label" id="sh-no-label">เลขที่ใบแจ้งหนี้</div>
                        <div class="rt-field-value rt-mono" id="sh-no" style="font-size:1.1rem;">-</div>
                    </div>
                    <span id="sh-badge"></span>
                </div>
                <dl class="rt-fields">
                    <div><dt class="rt-field-label">ผู้เช่า</dt><dd class="rt-field-value" style="margin:0;" id="sh-tenant">-</dd></div>
                    <div><dt class="rt-field-label">ห้อง</dt><dd class="rt-field-value" style="margin:0;" id="sh-room">-</dd></div>
                    <div><dt class="rt-field-label">รอบบิล</dt><dd class="rt-field-value" style="margin:0;" id="sh-month">-</dd></div>
                    <div><dt class="rt-field-label">วันครบกำหนด</dt><dd class="rt-field-value" style="margin:0;" id="sh-due">-</dd></div>
                    <div><dt class="rt-field-label">ช่วงคิดเงิน</dt><dd class="rt-field-value" style="margin:0;" id="sh-period">-</dd></div>
                    <div><dt class="rt-field-label">วันที่ออกใบแจ้งหนี้</dt><dd class="rt-field-value" style="margin:0;" id="sh-issued">-</dd></div>
                </dl>

                <div class="iv-bill">
                    <div class="iv-bill-head">รายละเอียดค่าใช้จ่าย</div>
                    <div class="iv-bill-row"><span>ค่าเช่ารายเดือน<small id="sh-rent-sub"></small></span><b id="sh-rent">-</b></div>
                    <div class="iv-bill-row"><span>ค่าน้ำ<small id="sh-water-sub"></small></span><b id="sh-water">-</b></div>
                    <div class="iv-bill-row"><span>ค่าไฟ<small id="sh-elec-sub"></small></span><b id="sh-elec">-</b></div>
                    <div class="iv-bill-total"><span>ยอดรวม</span><b id="sh-total">-</b></div>
                </div>
            </section>
        </div>

        {{-- ขวา: สถานะการชำระ + ประวัติ --}}
        <div class="rt-col">
            <section class="rt-card" aria-labelledby="sh-pay-title">
                <div class="rt-card-head"><h2 class="rt-card-title rt-card-title--lg" id="sh-pay-title">การชำระเงิน</h2></div>
                <div id="sh-paybox"></div>
            </section>

            <section class="rt-card" aria-labelledby="sh-hist-title">
                <div class="rt-card-head"><h2 class="rt-card-title rt-card-title--lg" id="sh-hist-title">ประวัติการดำเนินการ</h2></div>
                <div id="sh-hist"></div>
            </section>
        </div>
    </div>
</div>

<script>
(function () {
    const { $, el } = IV;
    const root = $('iv-root');
    const IS_ADMIN = root.dataset.admin === '1';
    const ID = root.dataset.id;
    let inv = null;
    let seq = 0;

    const EVENT = {
        PROOF_SUBMITTED: { title: 'ผู้เช่าส่งหลักฐานการชำระ', dot: '' },
        PAYMENT_APPROVED: { title: 'อนุมัติการชำระเงิน', dot: 'rt-dot--ok' },
        PAYMENT_REJECTED: { title: 'ปฏิเสธหลักฐานการชำระ', dot: 'rt-dot--end' },
        WALK_IN_RECORDED: { title: 'บันทึกรับชำระหน้าเคาน์เตอร์', dot: 'rt-dot--ok' },
    };

    function showState(which) { // loading | error | ready
        $('sh-loading').hidden = which !== 'loading';
        $('sh-error').hidden = which !== 'error';
        $('sh-content').hidden = which !== 'ready';
    }

    function daysLate(dueStr) {
        const [y1, m1, d1] = dueStr.split('-').map(Number);
        const [y2, m2, d2] = IV.today().split('-').map(Number);
        return Math.round((Date.UTC(y2, m2 - 1, d2) - Date.UTC(y1, m1 - 1, d1)) / 86400000);
    }

    /* ---------- ตัวใบแจ้งหนี้ ---------- */
    function renderInvoice() {
        const no = IV.invNo(inv);
        document.title = no;
        IV.setText('sh-crumb', no);
        IV.setText('sh-title', 'ใบแจ้งหนี้ ' + no);
        IV.setText('sh-no', no);
        $('sh-badge').replaceChildren(IV.badge(inv));
        IV.setText('sh-tenant', IV.tenantName(inv));

        const roomCell = $('sh-room');
        const label = 'ห้อง ' + IV.roomName(inv);
        if (IS_ADMIN && inv.rentals_rt_id) {
            roomCell.replaceChildren(el('a', { class: 'rt-link', href: `/rentals/${inv.rentals_rt_id}`, text: label }));
        } else {
            roomCell.textContent = label;
        }

        IV.setText('sh-month', IV.monthKey(inv) || '-');
        const dueCell = $('sh-due');
        dueCell.textContent = IV.day(inv.i_due) || '-';
        dueCell.className = 'rt-field-value' + (IV.statusKey(inv) === 'overdue' ? ' iv-due--late' : '');
        IV.setText('sh-period', `${IV.day(inv.period_start)} → ${IV.day(inv.period_end)}`);
        IV.setText('sh-issued', IV.day(inv.i_date) || '-');

        IV.setText('sh-rent', IV.money(inv.i_rent));
        IV.setText('sh-rent-sub', `อัตรา ${IV.money(inv.rent_rate)} / เดือน (คิดตามจำนวนวันในช่วง)`);
        IV.setText('sh-water', IV.money(inv.i_water));
        IV.setText('sh-water-sub', `${IV.num(inv.water_usage)} หน่วย × ${IV.money(inv.water_rate)}`);
        IV.setText('sh-elec', IV.money(inv.i_elec));
        IV.setText('sh-elec-sub', `${IV.num(inv.elec_usage)} หน่วย × ${IV.money(inv.elec_rate)}`);
        IV.setText('sh-total', IV.money(inv.i_total));
    }

    /* ---------- กล่องสถานะการชำระ ---------- */
    function notice(kind, ...kids) { return el('div', { class: 'rt-notice rt-notice--' + kind, style: 'margin:0;' }, ...kids); }

    function renderPaybox() {
        const p = inv.payment;
        const box = $('sh-paybox');
        const proofBtn = (label) => el('button', {
            type: 'button', class: 'rt-btn rt-btn--sm', style: 'margin-top:10px;', text: label,
            onclick: () => IV.downloadProof(`/api/v1/invoices/${inv.i_id}/payment/proof`, `payment-proof-${inv.i_id}`),
        });
        const parts = [];

        if (!p) {
            parts.push(notice('warn', 'ไม่พบข้อมูลการชำระเงินของใบแจ้งหนี้นี้'));
        } else if (p.p_status === 'PAID') {
            parts.push(notice('ok',
                `✓ ชำระแล้วเมื่อวันที่ ${IV.day(p.p_date) || '-'} ผ่าน${IV.methodLabel(p.p_type)}`,
                el('div', { style: 'margin-top:4px;font-size:0.8rem;', text: 'ยอดที่ชำระ ' + IV.money(p.p_amount) })
            ));
            if (p.has_proof) parts.push(proofBtn('ดาวน์โหลดหลักฐานการโอน'));
        } else if (p.p_status === 'PENDING') {
            parts.push(notice('info',
                `ส่งหลักฐานแล้ว (วันที่โอน ${IV.day(p.p_date) || '-'}, ยอด ${IV.money(p.p_amount)}) — รอผู้ดูแลตรวจสอบ`
            ));
            if (p.has_proof) parts.push(proofBtn('ดาวน์โหลดหลักฐานที่ส่ง'));
        } else if (p.p_status === 'REJECTED') {
            parts.push(notice('err',
                'หลักฐานการชำระถูกปฏิเสธ',
                p.p_reject_reason ? el('div', { style: 'margin-top:4px;', text: 'เหตุผล: ' + p.p_reject_reason }) : null,
                el('div', { style: 'margin-top:4px;font-size:0.8rem;', text: IS_ADMIN ? 'รอผู้เช่าส่งหลักฐานใหม่ หรือบันทึกรับชำระหน้าเคาน์เตอร์ได้' : 'กรุณาส่งหลักฐานใหม่' })
            ));
        } else { // UNPAID
            const late = IV.statusKey(inv) === 'overdue';
            const n = late ? daysLate(IV.day(inv.i_due)) : 0;
            parts.push(notice(late ? 'err' : 'warn',
                late ? `ค้างชำระ — เกินกำหนด ${n} วัน (ครบกำหนด ${IV.day(inv.i_due)})` : `ยังไม่ชำระ — ครบกำหนด ${IV.day(inv.i_due)}`
            ));
            if (IS_ADMIN && !IV.isEditable(inv)) {
                parts.push(el('p', { class: 'rt-hint', style: 'margin-top:8px;', text: 'ใบนี้มีประวัติการชำระแล้ว จึงแก้ไขใบแจ้งหนี้ไม่ได้' }));
            }
        }
        box.replaceChildren(...parts);
    }

    /* ---------- ประวัติ ---------- */
    function whoLabel(ev) {
        if (IS_ADMIN) return ev.actor?.u_username ? 'โดย ' + ev.actor.u_username : 'โดยผู้ใช้ที่ถูกลบ';
        return ev.actor?.is_self ? 'โดยคุณ' : 'โดยผู้ดูแลระบบ';
    }

    function renderHistory(res) {
        const box = $('sh-hist');
        if (!res.ok) {
            box.replaceChildren(el('div', { class: 'rt-state rt-state--error' },
                el('span', { text: IV.errMsg(res, 'โหลดประวัติไม่สำเร็จ') }),
                el('button', { type: 'button', class: 'rt-btn rt-btn--sm', text: 'ลองใหม่', onclick: () => loadHistory() })));
            return;
        }
        const events = res.body.data ?? [];
        if (!events.length) {
            box.replaceChildren(el('p', { class: 'rt-muted', style: 'margin:0;font-size:0.875rem;', text: 'ยังไม่มีประวัติการชำระ' }));
            return;
        }
        const items = events.map((ev) => {
            const meta = EVENT[ev.event_type] ?? { title: ev.event_type, dot: '' };
            const lines = [];
            const bits = [];
            if (ev.amount !== null && ev.amount !== undefined) bits.push(IV.money(ev.amount));
            if (ev.method) bits.push(IV.methodLabel(ev.method));
            if (ev.payment_date) bits.push('วันที่ชำระ ' + IV.day(ev.payment_date));
            if (bits.length) lines.push(el('div', { class: 'rt-tl-desc', text: bits.join(' · ') }));
            lines.push(el('div', { class: 'rt-tl-desc', text: whoLabel(ev) }));
            if (ev.reason) lines.push(el('div', { class: 'rt-tl-reason', text: 'เหตุผล: ' + ev.reason }));
            if (ev.note) lines.push(el('div', { class: 'rt-tl-reason', text: 'หมายเหตุ: ' + ev.note }));
            if (ev.has_proof) {
                lines.push(el('button', {
                    type: 'button', class: 'rt-btn rt-btn--sm', style: 'margin-top:6px;', text: 'ดาวน์โหลดหลักฐาน',
                    onclick: () => IV.downloadProof(`/api/v1/payment-events/${ev.pe_id}/proof`, `payment-event-${ev.pe_id}`),
                }));
            }
            return el('li', null,
                el('span', { class: 'rt-dot ' + meta.dot }),
                el('div', null,
                    el('div', null,
                        el('span', { class: 'rt-tl-date', text: IV.dateTime(ev.created_at) }),
                        el('span', { class: 'rt-tl-title', text: meta.title })),
                    ...lines)
            );
        });
        box.replaceChildren(el('ul', { class: 'rt-timeline' }, ...items));
    }

    async function loadHistory() {
        const res = await IV.api('GET', `/api/v1/invoices/${encodeURIComponent(ID)}/payment/events?per_page=100`);
        renderHistory(res);
    }

    /* ---------- ปุ่มการทำงานด้านบน ---------- */
    function renderActions() {
        $('btn-edit').hidden = !(IS_ADMIN && IV.isEditable(inv));
        $('btn-walkin').hidden = !(IS_ADMIN && IV.canRecordPayment(inv));
        $('btn-review').hidden = !(IS_ADMIN && inv.payment?.p_status === 'PENDING');
        $('btn-submit').hidden = !(!IS_ADMIN && IV.canRecordPayment(inv));
    }

    async function load(silent = false) {
        const my = ++seq;
        if (!silent) showState('loading');
        const [r, ev] = await Promise.all([
            IV.api('GET', `/api/v1/invoices/${encodeURIComponent(ID)}`),
            IV.api('GET', `/api/v1/invoices/${encodeURIComponent(ID)}/payment/events?per_page=100`),
        ]);
        if (my !== seq) return;
        if (!r.ok) {
            if (silent) { IV.toast(IV.errMsg(r, 'โหลดข้อมูลไม่สำเร็จ'), 'err'); return; }
            const msg = r.status === 404 ? 'ไม่พบใบแจ้งหนี้นี้' : IV.errMsg(r, 'โหลดข้อมูลไม่สำเร็จ');
            IV.setText('sh-error-message', msg);
            $('sh-retry').hidden = r.status === 404;
            showState('error');
            return;
        }
        inv = r.body.data;
        renderInvoice();
        renderPaybox();
        renderActions();
        renderHistory(ev);
        showState('ready');
    }

    const refresh = () => load(true);
    $('btn-walkin').addEventListener('click', () => window.InvoicePay.walkIn(inv.i_id, refresh));
    $('btn-review').addEventListener('click', () => window.InvoicePay.review(inv.i_id, refresh));
    $('btn-submit').addEventListener('click', () => window.InvoicePay.submitProof(inv.i_id, refresh));
    $('btn-edit').addEventListener('click', () => { if (window.InvoiceEdit) window.InvoiceEdit.open(inv.i_id, refresh); });
    $('sh-retry').addEventListener('click', () => load());

    load();
})();
</script>
</x-layouts::app>
