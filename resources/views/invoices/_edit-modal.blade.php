{{-- resources/views/invoices/_edit-modal.blade.php --}}
{{-- Admin: แก้ไขใบแจ้งหนี้ (ช่วงคิดเงิน / วันครบกำหนด + เหตุผล) — PATCH /api/v1/invoices/{id}
     เรียก: window.InvoiceEdit.open(invoiceId, onDone)
     backend อนุญาตเฉพาะบิลที่ยัง UNPAID และยังไม่มีประวัติการชำระ (ตรวจซ้ำที่ฝั่งเซิร์ฟเวอร์เสมอ) --}}
@include('invoices._base')

<dialog class="iv-dialog" id="ie-dialog" aria-labelledby="ie-title">
    <div class="iv-dialog-body">
        <div class="rt-modal-head">
            <h2 class="rt-modal-title" id="ie-title">แก้ไขใบแจ้งหนี้</h2>
            <button type="button" class="iv-x" id="ie-x" aria-label="ปิด">✕</button>
        </div>
        <div class="iv-summary">
            <dl>
                <div><dt>เลขที่</dt><dd id="ie-no">-</dd></div>
                <div><dt>ผู้เช่า / ห้อง</dt><dd id="ie-who">-</dd></div>
                <div><dt>ยอดปัจจุบัน</dt><dd id="ie-total">-</dd></div>
            </dl>
        </div>
        <p id="ie-banner" class="rt-notice rt-notice--err" hidden></p>
        <form id="ie-form" novalidate>
            <div class="rt-form-row">
                <div class="rt-form-field">
                    <label for="ie-start">เริ่มช่วงคิดเงิน <span class="rt-req">*</span></label>
                    <input type="date" id="ie-start" class="rt-input" required>
                    <span class="rt-field-error" id="ie-err-start"></span>
                </div>
                <div class="rt-form-field">
                    <label for="ie-end">สิ้นสุดช่วงคิดเงิน <span class="rt-req">*</span></label>
                    <input type="date" id="ie-end" class="rt-input" required>
                    <span class="rt-field-error" id="ie-err-end"></span>
                </div>
            </div>
            <div class="rt-form-field">
                <label for="ie-due">วันครบกำหนดชำระ <span class="rt-req">*</span></label>
                <input type="date" id="ie-due" class="rt-input" required>
                <span class="rt-hint" id="ie-due-hint"></span>
                <span class="rt-field-error" id="ie-err-due"></span>
            </div>
            <p class="iv-hint-box">
                ถ้าเปลี่ยนช่วงคิดเงิน ระบบจะคำนวณยอดใหม่จากเลขมิเตอร์ของวันที่เลือก (ใช้อัตราค่าเช่า ค่าน้ำ ค่าไฟเดิมของใบนี้)
                จึงต้องมีมิเตอร์ตรงวันเริ่มและวันสิ้นสุด — ถ้าเปลี่ยนแค่วันครบกำหนด ยอดจะไม่เปลี่ยน
            </p>
            <div class="rt-form-field">
                <label for="ie-reason">เหตุผลในการแก้ไข <span class="rt-req">*</span></label>
                <textarea id="ie-reason" class="rt-input" maxlength="2000" required placeholder="เช่น ขยายวันครบกำหนดตามที่ผู้เช่าขอ / จดมิเตอร์ผิดวัน"></textarea>
                <span class="rt-hint" id="ie-reason-count">0 / 2000</span>
                <span class="rt-field-error" id="ie-err-reason"></span>
            </div>
            <div class="rt-form-actions">
                <button type="button" class="rt-btn" id="ie-cancel">ยกเลิก</button>
                <button type="submit" class="rt-btn rt-btn--primary" id="ie-submit">บันทึกการแก้ไข</button>
            </div>
        </form>
    </div>
</dialog>

<script>
(function () {
    const { $ } = IV;
    const dlg = $('ie-dialog');
    let cur = null, busy = false, finished = false, stale = false, opening = false;

    const clear = () => {
        ['start', 'end', 'due', 'reason'].forEach((k) => IV.setText('ie-err-' + k, ''));
        $('ie-banner').hidden = true;
    };

    async function open(invoiceId, onDone) {
        if (cur || opening) return;
        opening = true;
        let r;
        try { r = await IV.api('GET', `/api/v1/invoices/${encodeURIComponent(invoiceId)}`); } finally { opening = false; }
        if (!r.ok) { IV.toast(IV.errMsg(r, 'โหลดข้อมูลใบแจ้งหนี้ไม่สำเร็จ'), 'err'); return; }
        const inv = r.body.data;
        if (!IV.isEditable(inv)) {
            IV.toast('แก้ไขได้เฉพาะใบแจ้งหนี้ที่ยังไม่ชำระและยังไม่มีประวัติการชำระ', 'warn');
            if (onDone) onDone();
            return;
        }
        cur = { inv, onDone, result: null };
        busy = false; finished = false; stale = false;
        clear();
        IV.setText('ie-no', IV.invNo(inv));
        IV.setText('ie-who', `${IV.tenantName(inv)} / ห้อง ${IV.roomName(inv)}`);
        IV.setText('ie-total', IV.money(inv.i_total));
        $('ie-start').value = IV.day(inv.period_start);
        $('ie-end').value = IV.day(inv.period_end);
        $('ie-due').value = IV.day(inv.i_due);
        $('ie-due').min = IV.day(inv.i_date);
        IV.setText('ie-due-hint', 'ต้องไม่ก่อนวันออกใบแจ้งหนี้ (' + IV.day(inv.i_date) + ')');
        $('ie-reason').value = '';
        IV.setText('ie-reason-count', '0 / 2000');
        $('ie-submit').disabled = false;
        dlg.showModal();
        $('ie-due').focus();
    }

    const close = () => { if (!busy && dlg.open) dlg.close(); };
    $('ie-x').addEventListener('click', close);
    $('ie-cancel').addEventListener('click', close);
    dlg.addEventListener('cancel', (e) => { if (busy) e.preventDefault(); });
    dlg.addEventListener('close', () => {
        const cb = (finished || stale) && cur ? cur.onDone : null;
        const result = cur ? cur.result : null;
        cur = null;
        if (cb) cb(result);
    });
    $('ie-reason').addEventListener('input', () => IV.setText('ie-reason-count', `${$('ie-reason').value.length} / 2000`));

    $('ie-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        if (busy || !cur) return;
        clear();
        const inv = cur.inv;
        const start = $('ie-start').value;
        const end = $('ie-end').value;
        const due = $('ie-due').value;
        const reason = $('ie-reason').value.trim();
        let ok = true;

        if (!start) { IV.setText('ie-err-start', 'กรุณาเลือกวันเริ่มช่วงคิดเงิน'); ok = false; }
        if (!end) { IV.setText('ie-err-end', 'กรุณาเลือกวันสิ้นสุดช่วงคิดเงิน'); ok = false; }
        else if (start && end <= start) { IV.setText('ie-err-end', 'วันสิ้นสุดต้องอยู่หลังวันเริ่ม'); ok = false; }
        if (!due) { IV.setText('ie-err-due', 'กรุณาเลือกวันครบกำหนด'); ok = false; }
        else if (due < IV.day(inv.i_date)) { IV.setText('ie-err-due', 'วันครบกำหนดต้องไม่ก่อนวันออกใบแจ้งหนี้'); ok = false; }
        if (!reason) { IV.setText('ie-err-reason', 'กรุณาระบุเหตุผลในการแก้ไข'); ok = false; }
        if (!ok) return;

        if (start === IV.day(inv.period_start) && end === IV.day(inv.period_end) && due === IV.day(inv.i_due)) {
            IV.showBanner($('ie-banner'), 'warn', 'ยังไม่มีข้อมูลที่เปลี่ยนแปลง');
            return;
        }

        busy = true;
        $('ie-submit').disabled = true;
        const r = await IV.api('PATCH', `/api/v1/invoices/${inv.i_id}`, {
            json: { period_start: start, period_end: end, i_due: due, reason },
        });
        busy = false;
        $('ie-submit').disabled = false;

        if (r.ok) {
            finished = true;
            cur.result = r.body.data;
            dlg.close();
            IV.toast('แก้ไขใบแจ้งหนี้สำเร็จ');
            return;
        }
        if (r.status === 422) {
            let shown = false;
            for (const [field, id] of [['period_start', 'ie-err-start'], ['period_end', 'ie-err-end'], ['i_due', 'ie-err-due'], ['reason', 'ie-err-reason']]) {
                const m = IV.fieldError(r, field);
                if (m) { IV.setText(id, m); shown = true; }
            }
            if (!shown) IV.showBanner($('ie-banner'), 'err', IV.errMsg(r, 'ข้อมูลไม่ถูกต้อง'));
            return;
        }
        if (r.status === 409) {
            stale = true;
            IV.showBanner($('ie-banner'), 'err', (r.body?.message || 'ไม่สามารถแก้ไขใบแจ้งหนี้นี้ได้ในสถานะปัจจุบัน หรือช่วงเวลาทับซ้อนกับใบอื่น') + ' — ปิดหน้าต่างนี้เพื่อโหลดข้อมูลใหม่');
            return;
        }
        IV.showBanner($('ie-banner'), 'err', IV.errMsg(r, 'แก้ไขไม่สำเร็จ (' + r.status + ')'));
    });

    window.InvoiceEdit = { open };
})();
</script>
