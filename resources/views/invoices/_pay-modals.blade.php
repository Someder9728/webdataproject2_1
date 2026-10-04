{{-- resources/views/invoices/_pay-modals.blade.php --}}
{{-- กล่องโต้ตอบเกี่ยวกับการชำระเงิน — ใช้ภายใน <div class="rt ..."> ของหน้า index / show
     window.InvoicePay.walkIn(invoiceId, onDone)      Admin: บันทึกรับชำระหน้าเคาน์เตอร์  POST /api/v1/invoices/{id}/payment/walk-in
     window.InvoicePay.review(invoiceId, onDone)      Admin: อนุมัติ/ปฏิเสธหลักฐาน        POST /api/v1/invoices/{id}/payment/review
     window.InvoicePay.submitProof(invoiceId, onDone) Tenant: ส่งหลักฐานการโอน            POST /api/v1/invoices/{id}/payment/submit
     ทุกครั้งที่เปิด จะโหลดใบแจ้งหนี้ล่าสุดจาก API ก่อน (ใช้ latest_event_id กันการกดซ้อนกัน) --}}
@include('invoices._base')

@php($payIsAdmin = auth()->user()?->u_role === 'admin')

@if ($payIsAdmin)
{{-- ================= Admin: บันทึกการรับชำระ (Walk-in) ================= --}}
<dialog class="iv-dialog" id="wi-dialog" aria-labelledby="wi-title">
    <div class="iv-dialog-body">
        <div class="rt-modal-head">
            <h2 class="rt-modal-title" id="wi-title">บันทึกการชำระเงิน</h2>
            <button type="button" class="iv-x" id="wi-x" aria-label="ปิด">✕</button>
        </div>
        <div class="iv-summary">
            <dl>
                <div><dt>ผู้เช่า</dt><dd id="wi-tenant">-</dd></div>
                <div><dt>ห้อง</dt><dd id="wi-room">-</dd></div>
                <div><dt>รอบบิล</dt><dd id="wi-period">-</dd></div>
                <div><dt>ยอดที่ต้องชำระ</dt><dd class="iv-sum-big" id="wi-total">-</dd></div>
            </dl>
        </div>
        <p id="wi-rejected" class="rt-notice rt-notice--warn" hidden></p>
        <p id="wi-banner" class="rt-notice rt-notice--err" hidden></p>
        <form id="wi-form" novalidate>
            <div class="rt-form-field">
                <label for="wi-amount">จำนวนเงิน (บาท)</label>
                <input type="text" id="wi-amount" class="rt-input iv-readonly" readonly>
                <span class="rt-hint">ต้องเท่ากับยอดเต็มของใบแจ้งหนี้ (ระบบไม่รองรับการชำระบางส่วน)</span>
                <span class="rt-field-error" id="wi-err-amount"></span>
            </div>
            <div class="rt-form-row">
                <div class="rt-form-field">
                    <label for="wi-date">วันที่ชำระ <span class="rt-req">*</span></label>
                    <input type="date" id="wi-date" class="rt-input" required>
                    <span class="rt-field-error" id="wi-err-date"></span>
                </div>
                <div class="rt-form-field">
                    <label for="wi-method">วิธีการชำระ <span class="rt-req">*</span></label>
                    <select id="wi-method" class="rt-input" required>
                        <option value="">เลือกวิธีการชำระ</option>
                        <option value="TRANSFER">โอนเงิน</option>
                        <option value="CASH">เงินสด</option>
                    </select>
                    <span class="rt-field-error" id="wi-err-method"></span>
                </div>
            </div>
            <div class="rt-form-field">
                <label for="wi-note">หมายเหตุการรับชำระ <span class="rt-req">*</span></label>
                <textarea id="wi-note" class="rt-input" maxlength="1000" required placeholder="เช่น รับเงินสดที่เคาน์เตอร์ / ตรวจสลิปโอนแล้ว"></textarea>
                <span class="rt-hint" id="wi-note-count">0 / 1000</span>
                <span class="rt-field-error" id="wi-err-note"></span>
            </div>
            <p class="iv-hint-box">เมื่อบันทึก ใบแจ้งหนี้จะเป็น "ชำระแล้ว" และไม่สามารถแก้ไขรายการนี้ได้ ระบบจะเก็บชื่อผู้บันทึกและเวลาไว้ในประวัติ</p>
            <div class="rt-form-actions">
                <button type="button" class="rt-btn" id="wi-cancel">ยกเลิก</button>
                <button type="submit" class="rt-btn rt-btn--primary" id="wi-submit">ยืนยันการชำระเงิน</button>
            </div>
        </form>
    </div>
</dialog>

{{-- ================= Admin: ตรวจหลักฐานการโอน (อนุมัติ / ปฏิเสธ) ================= --}}
<dialog class="iv-dialog" id="rv-dialog" aria-labelledby="rv-title">
    <div class="iv-dialog-body">
        <div class="rt-modal-head">
            <h2 class="rt-modal-title" id="rv-title">ตรวจสอบหลักฐานการชำระเงิน</h2>
            <button type="button" class="iv-x" id="rv-x" aria-label="ปิด">✕</button>
        </div>
        <div class="iv-summary">
            <dl>
                <div><dt>ผู้เช่า</dt><dd id="rv-tenant">-</dd></div>
                <div><dt>ห้อง</dt><dd id="rv-room">-</dd></div>
                <div><dt>ยอดใบแจ้งหนี้</dt><dd class="iv-sum-big" id="rv-total">-</dd></div>
                <div><dt>ยอดที่ผู้เช่าแจ้ง</dt><dd id="rv-amount">-</dd></div>
                <div><dt>วันที่ชำระ (ตามที่ผู้เช่าแจ้ง)</dt><dd id="rv-date">-</dd></div>
                <div><dt>วิธีการชำระ</dt><dd id="rv-method">-</dd></div>
            </dl>
        </div>
        <div class="iv-proof" id="rv-proof" aria-live="polite"></div>
        <p id="rv-banner" class="rt-notice rt-notice--err" hidden></p>

        <div id="rv-panel-reject" hidden>
            <div class="rt-form-field">
                <label for="rv-reason">เหตุผลที่ปฏิเสธ <span class="rt-req">*</span></label>
                <textarea id="rv-reason" class="rt-input" maxlength="1000" placeholder="เช่น สลิปไม่ชัด / ยอดไม่ตรง / ไม่พบรายการเข้าบัญชี"></textarea>
                <span class="rt-hint" id="rv-reason-count">0 / 1000</span>
                <span class="rt-field-error" id="rv-err-reason"></span>
            </div>
            <p class="iv-hint-box">ผู้เช่าจะเห็นเหตุผลนี้ และสามารถส่งหลักฐานใหม่ได้</p>
        </div>
        <div id="rv-panel-approve" hidden>
            <p class="iv-hint-box">ยืนยันว่าหลักฐานถูกต้องและยอดตรงกับใบแจ้งหนี้ — ใบแจ้งหนี้จะเป็น "ชำระแล้ว" และทำย้อนกลับไม่ได้</p>
        </div>

        <div class="rt-form-actions" id="rv-act-view">
            <button type="button" class="rt-btn" id="rv-close">ปิด</button>
            <button type="button" class="rt-btn rt-btn--danger" id="rv-reject">ปฏิเสธหลักฐาน</button>
            <button type="button" class="rt-btn rt-btn--primary" id="rv-approve" disabled>อนุมัติการชำระ</button>
        </div>
        <div class="rt-form-actions" id="rv-act-reject" hidden>
            <button type="button" class="rt-btn" id="rv-reject-back">กลับ</button>
            <button type="button" class="rt-btn rt-btn--danger" id="rv-reject-confirm">ยืนยันการปฏิเสธ</button>
        </div>
        <div class="rt-form-actions" id="rv-act-approve" hidden>
            <button type="button" class="rt-btn" id="rv-approve-back">กลับ</button>
            <button type="button" class="rt-btn rt-btn--primary" id="rv-approve-confirm">ยืนยันอนุมัติ</button>
        </div>
    </div>
</dialog>
@endif

@if (! $payIsAdmin)
{{-- ================= Tenant: ส่งหลักฐานการโอนเงิน ================= --}}
<dialog class="iv-dialog" id="sp-dialog" aria-labelledby="sp-title">
    <div class="iv-dialog-body">
        <div class="rt-modal-head">
            <h2 class="rt-modal-title" id="sp-title">ส่งหลักฐานการชำระเงิน</h2>
            <button type="button" class="iv-x" id="sp-x" aria-label="ปิด">✕</button>
        </div>
        <div class="iv-summary">
            <dl>
                <div><dt>ห้อง</dt><dd id="sp-room">-</dd></div>
                <div><dt>รอบบิล</dt><dd id="sp-period">-</dd></div>
                <div><dt>วันครบกำหนด</dt><dd id="sp-due">-</dd></div>
                <div><dt>ยอดที่ต้องชำระ</dt><dd class="iv-sum-big" id="sp-total">-</dd></div>
            </dl>
        </div>
        <p id="sp-rejected" class="rt-notice rt-notice--warn" hidden></p>
        <p id="sp-banner" class="rt-notice rt-notice--err" hidden></p>
        <form id="sp-form" novalidate>
            <div class="rt-form-field">
                <label for="sp-amount">จำนวนเงินที่โอน (บาท)</label>
                <input type="text" id="sp-amount" class="rt-input iv-readonly" readonly>
                <span class="rt-hint">ต้องโอนเต็มยอดของใบแจ้งหนี้</span>
                <span class="rt-field-error" id="sp-err-amount"></span>
            </div>
            <div class="rt-form-field">
                <label for="sp-date">วันที่โอน <span class="rt-req">*</span></label>
                <input type="date" id="sp-date" class="rt-input" required>
                <span class="rt-field-error" id="sp-err-date"></span>
            </div>
            <div class="rt-form-field">
                <label for="sp-file">หลักฐานการโอน (สลิป) <span class="rt-req">*</span></label>
                <input type="file" id="sp-file" class="rt-input" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" required>
                <span class="rt-hint" id="sp-file-info">ไฟล์ JPG, PNG หรือ PDF ขนาดไม่เกิน 5 MiB</span>
                <span class="rt-field-error" id="sp-err-proof"></span>
            </div>
            <p class="iv-hint-box">หลังส่งแล้ว ผู้ดูแลจะตรวจสอบหลักฐาน — ระหว่างรอตรวจสอบจะส่งซ้ำไม่ได้</p>
            <div class="rt-form-actions">
                <button type="button" class="rt-btn" id="sp-cancel">ยกเลิก</button>
                <button type="submit" class="rt-btn rt-btn--primary" id="sp-submit">ส่งหลักฐาน</button>
            </div>
        </form>
    </div>
</dialog>
@endif

<script>
(function () {
    const { $, el } = IV;
    const MAX_BYTES = 5 * 1024 * 1024; // backend: max 5120 KB
    const FILE_EXT = ['jpg', 'jpeg', 'png', 'pdf'];

    async function freshInvoice(id) {
        const r = await IV.api('GET', `/api/v1/invoices/${encodeURIComponent(id)}`);
        if (!r.ok) {
            IV.toast(IV.errMsg(r, 'โหลดข้อมูลใบแจ้งหนี้ไม่สำเร็จ'), 'err');
            return null;
        }
        return r.body.data;
    }

    function explainStatus(inv) {
        const s = inv?.payment?.p_status;
        if (s === 'PENDING') return 'ผู้เช่าส่งหลักฐานมาแล้ว กรุณาตรวจสอบหลักฐานก่อน';
        if (s === 'PAID') return 'ใบแจ้งหนี้นี้ชำระแล้ว';
        return 'ใบแจ้งหนี้นี้ไม่อยู่ในสถานะที่ทำรายการนี้ได้';
    }

    const STALE_MSG = 'สถานะของใบแจ้งหนี้เปลี่ยนไปแล้ว (อาจมีผู้อื่นทำรายการก่อน) กรุณาปิดหน้าต่างนี้ — ระบบจะโหลดข้อมูลใหม่ให้';

    /* ====================== Walk-in (Admin) ====================== */
    const wiDlg = $('wi-dialog');
    if (wiDlg) {
        let cur = null, busy = false, finished = false, stale = false, opening = false;
        const clear = () => {
            ['amount', 'date', 'method', 'note'].forEach((k) => IV.setText('wi-err-' + k, ''));
            $('wi-banner').hidden = true;
        };

        async function openWalkIn(invoiceId, onDone) {
            if (cur || opening) return; // กันกดซ้ำระหว่างโหลด/เปิดอยู่แล้ว
            opening = true;
            let inv;
            try { inv = await freshInvoice(invoiceId); } finally { opening = false; }
            if (!inv) return;
            if (!IV.canRecordPayment(inv)) {
                IV.toast(explainStatus(inv), 'warn');
                if (onDone) onDone();
                return;
            }
            cur = { inv, onDone };
            busy = false; finished = false; stale = false;
            clear();
            IV.setText('wi-tenant', IV.tenantName(inv));
            IV.setText('wi-room', 'ห้อง ' + IV.roomName(inv));
            IV.setText('wi-period', `${IV.day(inv.period_start)} → ${IV.day(inv.period_end)}`);
            IV.setText('wi-total', IV.money(inv.i_total));
            $('wi-amount').value = inv.i_total;
            const note = $('wi-rejected');
            if (inv.payment.p_status === 'REJECTED') {
                IV.showBanner(note, 'warn', 'หลักฐานก่อนหน้าถูกปฏิเสธ' + (inv.payment.p_reject_reason ? ': ' + inv.payment.p_reject_reason : '') + ' — การบันทึกรับชำระนี้จะแทนที่สถานะดังกล่าว');
            } else {
                note.hidden = true;
            }
            const date = $('wi-date');
            date.min = IV.day(inv.i_date);
            date.max = IV.today();
            date.value = IV.today();
            $('wi-method').value = '';
            $('wi-note').value = '';
            IV.setText('wi-note-count', '0 / 1000');
            $('wi-submit').disabled = false;
            wiDlg.showModal();
            $('wi-method').focus();
        }

        const closeWi = () => { if (!busy && wiDlg.open) wiDlg.close(); };
        $('wi-x').addEventListener('click', closeWi);
        $('wi-cancel').addEventListener('click', closeWi);
        wiDlg.addEventListener('cancel', (e) => { if (busy) e.preventDefault(); });
        wiDlg.addEventListener('close', () => {
            const cb = (finished || stale) && cur ? cur.onDone : null;
            cur = null;
            if (cb) cb();
        });
        $('wi-note').addEventListener('input', () => IV.setText('wi-note-count', `${$('wi-note').value.length} / 1000`));

        $('wi-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            if (busy || !cur) return;
            clear();
            const inv = cur.inv;
            const date = $('wi-date').value;
            const method = $('wi-method').value;
            const note = $('wi-note').value.trim();
            let ok = true;
            if (!date) { IV.setText('wi-err-date', 'กรุณาเลือกวันที่ชำระ'); ok = false; }
            else if (date > IV.today()) { IV.setText('wi-err-date', 'วันที่ชำระต้องไม่เกินวันนี้'); ok = false; }
            else if (date < IV.day(inv.i_date)) { IV.setText('wi-err-date', 'วันที่ชำระต้องไม่ก่อนวันออกใบแจ้งหนี้ (' + IV.day(inv.i_date) + ')'); ok = false; }
            if (!method) { IV.setText('wi-err-method', 'กรุณาเลือกวิธีการชำระ'); ok = false; }
            if (!note) { IV.setText('wi-err-note', 'กรุณาระบุหมายเหตุการรับชำระ'); ok = false; }
            if (!ok) return;

            busy = true;
            $('wi-submit').disabled = true;
            const r = await IV.api('POST', `/api/v1/invoices/${inv.i_id}/payment/walk-in`, {
                json: {
                    amount: inv.i_total,
                    payment_date: date,
                    method,
                    note,
                    expected_event_id: inv.payment.latest_event_id ?? null,
                },
            });
            busy = false;
            $('wi-submit').disabled = false;

            if (r.ok) {
                finished = true;
                wiDlg.close();
                IV.toast('บันทึกการชำระเงินสำเร็จ');
                return;
            }
            if (r.status === 422) {
                let shown = false;
                for (const [field, id] of [['amount', 'wi-err-amount'], ['payment_date', 'wi-err-date'], ['method', 'wi-err-method'], ['note', 'wi-err-note']]) {
                    const m = IV.fieldError(r, field);
                    if (m) { IV.setText(id, m); shown = true; }
                }
                if (!shown) IV.showBanner($('wi-banner'), 'err', IV.errMsg(r, 'ข้อมูลไม่ถูกต้อง'));
                return;
            }
            if (r.status === 409) { stale = true; IV.showBanner($('wi-banner'), 'err', STALE_MSG); return; }
            IV.showBanner($('wi-banner'), 'err', IV.errMsg(r, 'บันทึกไม่สำเร็จ (' + r.status + ')'));
        });

        window.__ivWalkIn = openWalkIn;
    }

    /* ====================== ตรวจหลักฐาน (Admin) ====================== */
    const rvDlg = $('rv-dialog');
    if (rvDlg) {
        let cur = null, busy = false, finished = false, stale = false, opening = false, blobUrl = null, seq = 0;

        const setMode = (mode) => {
            $('rv-panel-reject').hidden = mode !== 'reject';
            $('rv-panel-approve').hidden = mode !== 'approve';
            $('rv-act-view').hidden = mode !== 'view';
            $('rv-act-reject').hidden = mode !== 'reject';
            $('rv-act-approve').hidden = mode !== 'approve';
            $('rv-banner').hidden = true;
            IV.setText('rv-err-reason', '');
            if (mode === 'reject') $('rv-reason').focus();
        };

        function dropBlob() { if (blobUrl) { URL.revokeObjectURL(blobUrl); blobUrl = null; } }

        async function loadProof(invoiceId, mySeq) {
            const box = $('rv-proof');
            box.replaceChildren(el('span', { class: 'rt-muted', text: 'กำลังโหลดหลักฐาน...' }));
            const f = await IV.loadBlob(`/api/v1/invoices/${invoiceId}/payment/proof`);
            if (mySeq !== seq) { if (f.ok) URL.revokeObjectURL(f.url); return; }
            if (!f.ok) {
                box.replaceChildren(el('p', { class: 'rt-notice rt-notice--err', style: 'margin:0', text: f.status === 404 ? 'ไม่พบไฟล์หลักฐาน' : 'โหลดหลักฐานไม่สำเร็จ' }));
                return;
            }
            if (f.type === 'image/jpeg' || f.type === 'image/png') {
                blobUrl = f.url;
                box.replaceChildren(el('img', { src: f.url, alt: 'หลักฐานการชำระเงินที่ผู้เช่าส่งมา' }));
            } else if (f.type === 'application/pdf') {
                blobUrl = f.url;
                box.replaceChildren(
                    el('p', { style: 'margin:0 0 8px', text: 'หลักฐานเป็นไฟล์ PDF' }),
                    el('a', { class: 'rt-btn rt-btn--sm', href: f.url, target: '_blank', rel: 'noopener', text: 'เปิดไฟล์ PDF ในแท็บใหม่' })
                );
            } else {
                URL.revokeObjectURL(f.url);
                box.replaceChildren(el('p', { class: 'rt-notice rt-notice--err', style: 'margin:0', text: 'ชนิดไฟล์หลักฐานไม่รองรับ' }));
                return;
            }
            $('rv-approve').disabled = false; // อนุมัติได้เมื่อเห็นหลักฐานแล้วเท่านั้น
        }

        async function openReview(invoiceId, onDone) {
            if (cur || opening) return;
            opening = true;
            let inv;
            try { inv = await freshInvoice(invoiceId); } finally { opening = false; }
            if (!inv) return;
            if (inv.payment?.p_status !== 'PENDING') {
                IV.toast(explainStatus(inv), 'warn');
                if (onDone) onDone();
                return;
            }
            cur = { inv, onDone };
            busy = false; finished = false; stale = false;
            const mySeq = ++seq;
            dropBlob();
            setMode('view');
            $('rv-approve').disabled = true;
            $('rv-reason').value = '';
            IV.setText('rv-reason-count', '0 / 1000');
            IV.setText('rv-tenant', IV.tenantName(inv));
            IV.setText('rv-room', 'ห้อง ' + IV.roomName(inv));
            IV.setText('rv-total', IV.money(inv.i_total));
            IV.setText('rv-amount', IV.money(inv.payment.p_amount));
            IV.setText('rv-date', IV.day(inv.payment.p_date) || '-');
            IV.setText('rv-method', IV.methodLabel(inv.payment.p_type));
            rvDlg.showModal();
            loadProof(inv.i_id, mySeq);
        }

        const closeRv = () => { if (!busy && rvDlg.open) rvDlg.close(); };
        $('rv-x').addEventListener('click', closeRv);
        $('rv-close').addEventListener('click', closeRv);
        rvDlg.addEventListener('cancel', (e) => { if (busy) e.preventDefault(); });
        rvDlg.addEventListener('close', () => {
            seq++;
            dropBlob();
            const cb = (finished || stale) && cur ? cur.onDone : null;
            cur = null;
            if (cb) cb();
        });
        $('rv-reason').addEventListener('input', () => IV.setText('rv-reason-count', `${$('rv-reason').value.length} / 1000`));
        $('rv-reject').addEventListener('click', () => setMode('reject'));
        $('rv-reject-back').addEventListener('click', () => { if (!busy) setMode('view'); });
        $('rv-approve').addEventListener('click', () => setMode('approve'));
        $('rv-approve-back').addEventListener('click', () => { if (!busy) setMode('view'); });

        async function decide(decision) {
            if (busy || !cur) return;
            const inv = cur.inv;
            const reason = $('rv-reason').value.trim();
            $('rv-banner').hidden = true;
            IV.setText('rv-err-reason', '');
            if (decision === 'reject' && !reason) {
                IV.setText('rv-err-reason', 'กรุณาระบุเหตุผลที่ปฏิเสธ');
                return;
            }
            const eventId = inv.payment.latest_event_id;
            if (eventId === null || eventId === undefined) {
                IV.showBanner($('rv-banner'), 'err', 'ไม่พบรายการส่งหลักฐานล่าสุด กรุณาปิดแล้วเปิดใหม่');
                return;
            }
            busy = true;
            const btns = ['rv-reject-confirm', 'rv-approve-confirm', 'rv-reject-back', 'rv-approve-back'].map($);
            btns.forEach((b) => { b.disabled = true; });
            const payload = { decision, expected_event_id: eventId };
            if (decision === 'reject') payload.reason = reason;
            const r = await IV.api('POST', `/api/v1/invoices/${inv.i_id}/payment/review`, { json: payload });
            busy = false;
            btns.forEach((b) => { b.disabled = false; });

            if (r.ok) {
                finished = true;
                rvDlg.close();
                IV.toast(decision === 'approve' ? 'อนุมัติการชำระเงินแล้ว' : 'ปฏิเสธหลักฐานแล้ว — ผู้เช่าสามารถส่งใหม่ได้');
                return;
            }
            if (r.status === 422) {
                const m = IV.fieldError(r, 'reason');
                if (m) { IV.setText('rv-err-reason', m); return; }
                IV.showBanner($('rv-banner'), 'err', IV.errMsg(r, 'ข้อมูลไม่ถูกต้อง'));
                return;
            }
            if (r.status === 409) { stale = true; IV.showBanner($('rv-banner'), 'err', STALE_MSG); return; }
            IV.showBanner($('rv-banner'), 'err', IV.errMsg(r, 'บันทึกผลไม่สำเร็จ (' + r.status + ')'));
        }
        $('rv-approve-confirm').addEventListener('click', () => decide('approve'));
        $('rv-reject-confirm').addEventListener('click', () => decide('reject'));

        window.__ivReview = openReview;
    }

    /* ====================== ส่งหลักฐาน (Tenant) ====================== */
    const spDlg = $('sp-dialog');
    if (spDlg) {
        let cur = null, busy = false, finished = false, stale = false, opening = false;
        const clear = () => {
            ['amount', 'date', 'proof'].forEach((k) => IV.setText('sp-err-' + k, ''));
            $('sp-banner').hidden = true;
        };
        const fmtSize = (b) => b >= 1024 * 1024 ? (b / 1024 / 1024).toFixed(2) + ' MiB' : Math.max(1, Math.round(b / 1024)) + ' KiB';
        const HINT = 'ไฟล์ JPG, PNG หรือ PDF ขนาดไม่เกิน 5 MiB';

        async function openSubmit(invoiceId, onDone) {
            if (cur || opening) return;
            opening = true;
            let inv;
            try { inv = await freshInvoice(invoiceId); } finally { opening = false; }
            if (!inv) return;
            if (!IV.canRecordPayment(inv)) {
                IV.toast(inv?.payment?.p_status === 'PENDING' ? 'ส่งหลักฐานไปแล้ว กำลังรอผู้ดูแลตรวจสอบ' : explainStatus(inv), 'warn');
                if (onDone) onDone();
                return;
            }
            cur = { inv, onDone };
            busy = false; finished = false; stale = false;
            clear();
            IV.setText('sp-room', 'ห้อง ' + IV.roomName(inv));
            IV.setText('sp-period', `${IV.day(inv.period_start)} → ${IV.day(inv.period_end)}`);
            IV.setText('sp-due', IV.day(inv.i_due) || '-');
            IV.setText('sp-total', IV.money(inv.i_total));
            $('sp-amount').value = inv.i_total;
            const note = $('sp-rejected');
            if (inv.payment.p_status === 'REJECTED') {
                IV.showBanner(note, 'warn', 'หลักฐานครั้งก่อนถูกปฏิเสธ' + (inv.payment.p_reject_reason ? ': ' + inv.payment.p_reject_reason : '') + ' — กรุณาส่งหลักฐานใหม่');
            } else {
                note.hidden = true;
            }
            const date = $('sp-date');
            date.min = IV.day(inv.i_date);
            date.max = IV.today();
            date.value = IV.today();
            $('sp-file').value = '';
            IV.setText('sp-file-info', HINT);
            $('sp-submit').disabled = false;
            spDlg.showModal();
            $('sp-date').focus();
        }

        const closeSp = () => { if (!busy && spDlg.open) spDlg.close(); };
        $('sp-x').addEventListener('click', closeSp);
        $('sp-cancel').addEventListener('click', closeSp);
        spDlg.addEventListener('cancel', (e) => { if (busy) e.preventDefault(); });
        spDlg.addEventListener('close', () => {
            const cb = (finished || stale) && cur ? cur.onDone : null;
            cur = null;
            if (cb) cb();
        });
        $('sp-file').addEventListener('change', () => {
            const f = $('sp-file').files[0];
            IV.setText('sp-file-info', f ? `${f.name} (${fmtSize(f.size)})` : HINT);
            IV.setText('sp-err-proof', '');
        });

        $('sp-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            if (busy || !cur) return;
            clear();
            const inv = cur.inv;
            const date = $('sp-date').value;
            const file = $('sp-file').files[0];
            let ok = true;
            if (!date) { IV.setText('sp-err-date', 'กรุณาเลือกวันที่โอน'); ok = false; }
            else if (date > IV.today()) { IV.setText('sp-err-date', 'วันที่โอนต้องไม่เกินวันนี้'); ok = false; }
            else if (date < IV.day(inv.i_date)) { IV.setText('sp-err-date', 'วันที่โอนต้องไม่ก่อนวันออกใบแจ้งหนี้ (' + IV.day(inv.i_date) + ')'); ok = false; }
            if (!file) { IV.setText('sp-err-proof', 'กรุณาแนบหลักฐานการโอน'); ok = false; }
            else {
                const ext = (file.name.split('.').pop() || '').toLowerCase();
                if (!FILE_EXT.includes(ext)) { IV.setText('sp-err-proof', 'รองรับเฉพาะไฟล์ JPG, PNG หรือ PDF'); ok = false; }
                else if (file.size === 0) { IV.setText('sp-err-proof', 'ไฟล์ว่างเปล่า'); ok = false; }
                else if (file.size > MAX_BYTES) { IV.setText('sp-err-proof', 'ไฟล์ใหญ่เกิน 5 MiB'); ok = false; }
            }
            if (!ok) return;

            busy = true;
            $('sp-submit').disabled = true;
            const fd = new FormData();
            fd.append('amount', inv.i_total);
            fd.append('payment_date', date);
            fd.append('proof', file);
            const r = await IV.api('POST', `/api/v1/invoices/${inv.i_id}/payment/submit`, { form: fd });
            busy = false;
            $('sp-submit').disabled = false;

            if (r.ok) {
                finished = true;
                spDlg.close();
                IV.toast('ส่งหลักฐานเรียบร้อย รอผู้ดูแลตรวจสอบ');
                return;
            }
            if (r.status === 422) {
                let shown = false;
                for (const [field, id] of [['amount', 'sp-err-amount'], ['payment_date', 'sp-err-date'], ['proof', 'sp-err-proof']]) {
                    const m = IV.fieldError(r, field);
                    if (m) { IV.setText(id, m); shown = true; }
                }
                if (!shown) IV.showBanner($('sp-banner'), 'err', IV.errMsg(r, 'ข้อมูลไม่ถูกต้อง'));
                return;
            }
            if (r.status === 409) { stale = true; IV.showBanner($('sp-banner'), 'err', STALE_MSG); return; }
            IV.showBanner($('sp-banner'), 'err', IV.errMsg(r, 'ส่งหลักฐานไม่สำเร็จ (' + r.status + ')'));
        });

        window.__ivSubmit = openSubmit;
    }

    window.InvoicePay = {
        walkIn: (id, cb) => (window.__ivWalkIn ? window.__ivWalkIn(id, cb) : undefined),
        review: (id, cb) => (window.__ivReview ? window.__ivReview(id, cb) : undefined),
        submitProof: (id, cb) => (window.__ivSubmit ? window.__ivSubmit(id, cb) : undefined),
    };
})();
</script>
