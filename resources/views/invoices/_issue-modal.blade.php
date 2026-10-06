{{-- resources/views/invoices/_issue-modal.blade.php --}}
{{-- Admin: ออกใบแจ้งหนี้ใหม่ — 2 ขั้น: คำนวณ/ตรวจยอด (POST /api/v1/invoices/preview) แล้วค่อยออกจริง (POST /api/v1/invoices)
     มี 2 โหมด:
       - auto   : ส่งแค่ rentals_rt_id ให้ backend หารอบที่ยังไม่ออกบิล (เติมช่องว่างแรก) แล้วคืนช่วงมาให้ดู
       - manual : เลือก period_start / period_end เอง (แบบเดิม)
     ตอนยืนยัน ส่ง period_start / period_end "ที่ได้จากผลพรีวิว" เสมอ เพื่อให้ออกบิลตรงช่วงที่ผู้ใช้เห็น
     เรียก: window.InvoiceIssue.open(onDone) --}}
@include('invoices._base')

<dialog class="iv-dialog" id="is-dialog" aria-labelledby="is-title">
    <div class="iv-dialog-body">
        <div class="rt-modal-head">
            <h2 class="rt-modal-title" id="is-title">ออกใบแจ้งหนี้</h2>
            <button type="button" class="iv-x" id="is-x" aria-label="ปิด">✕</button>
        </div>
        <p class="iv-steps-line" id="is-step-line">ขั้นที่ 1 จาก 2 — เลือกการเช่าและช่วงคิดเงิน</p>
        <p id="is-banner" class="rt-notice rt-notice--err" hidden></p>

        {{-- ขั้น 1 --}}
        <form id="is-form" novalidate>
            <div class="rt-form-field">
                <label for="is-rental">การเช่า <span class="rt-req">*</span></label>
                <select id="is-rental" class="rt-input" required disabled>
                    <option value="">กำลังโหลด...</option>
                </select>
                <span class="rt-field-error" id="is-err-rental"></span>
            </div>
            <fieldset class="iv-mode">
                <legend>ช่วงคิดเงิน</legend>
                <label>
                    <input type="radio" name="is-mode" value="auto" id="is-mode-auto" checked>
                    <span>ให้ระบบหารอบถัดไปให้ (แนะนำ)<small>ระบบเลือกรอบแรกที่ยังไม่ได้ออกบิล ตั้งแต่วันเข้าพัก/บิลล่าสุด ถึงวันที่ 1 เดือนถัดไป หรือวันย้ายออก</small></span>
                </label>
                <label>
                    <input type="radio" name="is-mode" value="manual" id="is-mode-manual">
                    <span>กำหนดวันเอง<small>ใช้เมื่อต้องการคิดบางช่วง เช่น คิดถึงวันนี้ก่อนสิ้นเดือน</small></span>
                </label>
            </fieldset>
            <div id="is-manual-fields" hidden>
            <div class="rt-form-row">
                <div class="rt-form-field">
                    <label for="is-start">เริ่มช่วงคิดเงิน <span class="rt-req">*</span></label>
                    <input type="date" id="is-start" class="rt-input" required>
                    <span class="rt-field-error" id="is-err-start"></span>
                </div>
                <div class="rt-form-field">
                    <label for="is-end">สิ้นสุดช่วงคิดเงิน <span class="rt-req">*</span></label>
                    <input type="date" id="is-end" class="rt-input" required>
                    <span class="rt-field-error" id="is-err-end"></span>
                </div>
            </div>
            <div class="rt-chips" style="margin:-6px 0 12px;">
                <button type="button" class="rt-chip" id="is-chip-prev">เดือนที่แล้วทั้งเดือน</button>
                <button type="button" class="rt-chip" id="is-chip-this">เดือนนี้ (ถึงวันนี้)</button>
            </div>
            </div>
            <p class="iv-hint-box">
                ระบบคำนวณยอดใหม่ทุกครั้งที่ฝั่งเซิร์ฟเวอร์ หนึ่งใบคิดภายในเดือนเดียว และวันสิ้นสุดต้องไม่เกินวันนี้ ต้องมีเลขมิเตอร์ของห้องตรงกับวันเริ่มและวันสิ้นสุดช่วง
                (ระบบไม่นับวันสิ้นสุดเป็นวันคิดค่าเช่า) ใบแจ้งหนี้จะมีกำหนดชำระ 7 วันนับจากวันที่ออก
            </p>
            <div class="rt-form-actions">
                <button type="button" class="rt-btn" id="is-cancel">ยกเลิก</button>
                <button type="submit" class="rt-btn rt-btn--primary" id="is-preview-btn">คำนวณยอด</button>
            </div>
        </form>

        {{-- ขั้น 2 --}}
        <div id="is-step2" hidden>
            <div class="iv-summary">
                <dl>
                    <div><dt>ผู้เช่า / ห้อง</dt><dd id="is-p-who">-</dd></div>
                    <div><dt>ช่วงคิดเงิน</dt><dd id="is-p-period">-</dd></div>
                    <div><dt>วันที่ออก → ครบกำหนด</dt><dd id="is-p-dates">-</dd></div>
                </dl>
            </div>
            <div class="iv-bill" style="margin-top:0;">
                <div class="iv-bill-head">รายละเอียดค่าใช้จ่าย</div>
                <div class="iv-bill-row"><span>ค่าเช่า<small id="is-p-rent-sub"></small></span><b id="is-p-rent">-</b></div>
                <div class="iv-bill-row"><span>ค่าน้ำ<small id="is-p-water-sub"></small></span><b id="is-p-water">-</b></div>
                <div class="iv-bill-row"><span>ค่าไฟ<small id="is-p-elec-sub"></small></span><b id="is-p-elec">-</b></div>
                <div class="iv-bill-total"><span>ยอดรวม</span><b id="is-p-total">-</b></div>
            </div>
            <p class="rt-notice rt-notice--info" style="margin-top:14px;">นี่เป็นเพียงการคำนวณตรวจสอบ — ยังไม่ได้ออกใบแจ้งหนี้จนกว่าจะกดปุ่มด้านล่าง</p>
            <div class="rt-form-actions">
                <button type="button" class="rt-btn" id="is-back">‹ แก้ไขช่วง</button>
                <button type="button" class="rt-btn rt-btn--primary" id="is-issue-btn">ออกใบแจ้งหนี้</button>
            </div>
        </div>
    </div>
</dialog>

<script>
(function () {
    const { $, el } = IV;
    const dlg = $('is-dialog');
    let busy = false, finished = false, created = null, onDoneCb = null, snapshot = null, loadSeq = 0;

    const firstOfMonth = (offset) => {
        const [y, m] = IV.today().split('-').map(Number);
        return new Date(Date.UTC(y, m - 1 + offset, 1)).toISOString().slice(0, 10);
    };
    const firstOfNextMonth = (dateStr) => {
        const [y, m] = dateStr.split('-').map(Number);
        return new Date(Date.UTC(y, m, 1)).toISOString().slice(0, 10);
    };

    const clear = () => {
        ['rental', 'start', 'end'].forEach((k) => IV.setText('is-err-' + k, ''));
        $('is-banner').hidden = true;
    };
    function showStep(n) {
        $('is-form').hidden = n !== 1;
        $('is-step2').hidden = n !== 2;
        IV.setText('is-step-line', n === 1 ? 'ขั้นที่ 1 จาก 2 — เลือกการเช่าและช่วงคิดเงิน' : 'ขั้นที่ 2 จาก 2 — ตรวจยอดแล้วยืนยันการออกใบแจ้งหนี้');
    }

    async function loadRentals() {
        const my = ++loadSeq;
        const sel = $('is-rental');
        sel.disabled = true;
        sel.replaceChildren(el('option', { value: '', text: 'กำลังโหลด...' }));
        const res = await IV.fetchAll('/api/v1/rentals', {}, 5);
        if (my !== loadSeq) return;
        if (!res.ok) {
            sel.replaceChildren(el('option', { value: '', text: 'โหลดรายการไม่สำเร็จ' }));
            IV.showBanner($('is-banner'), 'err', res.message);
            return;
        }
        const active = res.rows
            .filter((r) => r.rt_status === 'ACTIVE')
            .sort((a, b) => String(a.room?.r_name ?? '').localeCompare(String(b.room?.r_name ?? ''), 'th', { numeric: true }));
        if (!active.length) {
            sel.replaceChildren(el('option', { value: '', text: 'ไม่มีการเช่าที่ใช้งานอยู่' }));
            return;
        }
        sel.replaceChildren(
            el('option', { value: '', text: 'เลือกการเช่า' }),
            ...active.map((r) => el('option', {
                value: String(r.rt_id),
                text: `ห้อง ${r.room?.r_name ?? '-'} — ${IV.tenantName(r)}`,
            }))
        );
        sel.disabled = false;
    }

    function open(onDone) {
        if (dlg.open) return;
        onDoneCb = onDone;
        busy = false; finished = false; created = null; snapshot = null;
        clear();
        showStep(1);
        $('is-start').max = IV.today();
        $('is-end').max = IV.today();
        $('is-start').value = firstOfMonth(-1);
        $('is-end').value = firstOfMonth(0);
        $('is-mode-auto').checked = true;
        applyMode();
        $('is-preview-btn').disabled = false;
        $('is-issue-btn').disabled = false;
        dlg.showModal();
        loadRentals();
    }

    const close = () => { if (!busy && dlg.open) dlg.close(); };
    $('is-x').addEventListener('click', close);
    $('is-cancel').addEventListener('click', close);
    dlg.addEventListener('cancel', (e) => { if (busy) e.preventDefault(); });
    dlg.addEventListener('close', () => {
        loadSeq++;
        const cb = finished ? onDoneCb : null;
        const data = created;
        onDoneCb = null;
        if (cb) cb(data);
    });
    $('is-chip-prev').addEventListener('click', () => { $('is-start').value = firstOfMonth(-1); $('is-end').value = firstOfMonth(0); });
    $('is-chip-this').addEventListener('click', () => { $('is-start').value = firstOfMonth(0); $('is-end').value = IV.today(); });
    $('is-mode-auto').addEventListener('change', applyMode);
    $('is-mode-manual').addEventListener('change', applyMode);
    $('is-back').addEventListener('click', () => { if (!busy) { clear(); showStep(1); } });

    const isAuto = () => $('is-mode-auto').checked;
    function applyMode() {
        $('is-manual-fields').hidden = isAuto();
        IV.setText('is-err-start', '');
        IV.setText('is-err-end', '');
    }

    function readForm() {
        const f = { rentals_rt_id: Number($('is-rental').value) || null };
        if (!isAuto()) {
            // โหมด auto ต้อง "ไม่ส่ง" ทั้งสองวัน (backend จะหารอบให้) — ส่งแค่วันเดียวจะได้ 422
            f.period_start = $('is-start').value;
            f.period_end = $('is-end').value;
        }
        return f;
    }

    function mapErrors(r) {
        let shown = false;
        const fields = isAuto()
            ? [['rentals_rt_id', 'is-err-rental']]   // ช่องวันที่ซ่อนอยู่ → error ของช่วงไปแสดงที่ banner แทน
            : [['rentals_rt_id', 'is-err-rental'], ['period_start', 'is-err-start'], ['period_end', 'is-err-end']];
        for (const [field, id] of fields) {
            const m = IV.fieldError(r, field);
            if (m) { IV.setText(id, m); shown = true; }
        }
        return shown;
    }
    // ข้อความ error ของช่วงคิดเงินในโหมด auto (แสดงที่ banner พร้อมคำแนะนำ)
    function autoPeriodMessage(r) {
        const msgs = ['period_start', 'period_end', 'rental'].map((k) => IV.fieldError(r, k)).filter(Boolean);
        if (!msgs.length) return '';
        return 'รอบถัดไปที่ระบบหาให้: ' + msgs.join(' / ')
            + ' — ถ้าต้องการคิดบางช่วง ให้เลือก "กำหนดวันเอง"';
    }
    const hasThai = (t) => typeof t === 'string' && /[\u0E00-\u0E7F]/.test(t);
    function conflictMessage(r) {
        if (r.status !== 409) return IV.errMsg(r, 'ทำรายการไม่สำเร็จ (' + r.status + ')');
        const msg = r.body?.message;
        if (hasThai(msg)) return msg; // เช่น "ออกบิลครบถึงวันย้ายออกแล้ว", "พบใบแจ้งหนี้ที่ถูกลบ ..."
        return isAuto()
            ? 'หารอบบิลถัดไปไม่ได้: ประวัติใบแจ้งหนี้ของการเช่านี้มีช่วงที่ทับกันหรือผิดรูปแบบ กรุณาตรวจสอบ หรือเลือก "กำหนดวันเอง"'
            : 'ออกใบแจ้งหนี้ไม่ได้: ช่วงเวลานี้ทับซ้อนกับใบแจ้งหนี้ที่มีอยู่แล้วของการเช่านี้ (หรือสถานะข้อมูลเปลี่ยนไป)';
    }

    /* ขั้น 1 → คำนวณ */
    $('is-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        if (busy) return;
        clear();
        const f = readForm();
        const today = IV.today();
        let ok = true;
        if (!f.rentals_rt_id) { IV.setText('is-err-rental', 'กรุณาเลือกการเช่า'); ok = false; }
        if (!isAuto()) { // โหมด auto ไม่ต้องตรวจวันที่ — backend หาให้และตรวจเอง
            if (!f.period_start) { IV.setText('is-err-start', 'กรุณาเลือกวันเริ่ม'); ok = false; }
            if (!f.period_end) { IV.setText('is-err-end', 'กรุณาเลือกวันสิ้นสุด'); ok = false; }
            else if (f.period_start && f.period_end <= f.period_start) { IV.setText('is-err-end', 'วันสิ้นสุดต้องอยู่หลังวันเริ่ม'); ok = false; }
            else if (f.period_end > today) { IV.setText('is-err-end', 'ยังออกบิลไม่ได้ เพราะยังไม่ถึงวันสิ้นสุดช่วงคิดเงิน'); ok = false; }
            else if (f.period_start && f.period_end > firstOfNextMonth(f.period_start)) { IV.setText('is-err-end', 'หนึ่งใบคิดภายในเดือนเดียว กรุณาแบ่งช่วงตามเดือน'); ok = false; }
        }
        if (!ok) return;

        busy = true;
        $('is-preview-btn').disabled = true;
        const r = await IV.api('POST', '/api/v1/invoices/preview', { json: f });
        busy = false;
        $('is-preview-btn').disabled = false;

        if (!r.ok) {
            if (r.status === 422) {
                const shown = mapErrors(r);
                const autoMsg = isAuto() ? autoPeriodMessage(r) : '';
                if (autoMsg) IV.showBanner($('is-banner'), 'err', autoMsg);
                if (shown || autoMsg) return;
            }
            IV.showBanner($('is-banner'), 'err', conflictMessage(r));
            return;
        }
        const d = r.body.data ?? {};
        // ยืนยันด้วยช่วงที่ backend คืนมาในพรีวิว (โหมด auto ไม่มีวันในฟอร์ม)
        snapshot = { rentals_rt_id: f.rentals_rt_id, period_start: d.period_start, period_end: d.period_end };
        const opt = $('is-rental').selectedOptions[0];
        IV.setText('is-p-who', opt ? opt.textContent.replace(/^ห้อง\s*/, 'ห้อง ').replace(' — ', ' / ') : '-');
        IV.setText('is-p-period', `${IV.day(d.period_start)} → ${IV.day(d.period_end)}` + (isAuto() ? ' (ระบบหาให้)' : ''));
        IV.setText('is-p-dates', `${IV.day(d.i_date)} → ${IV.day(d.i_due)}`);
        IV.setText('is-p-rent', IV.money(d.i_rent));
        IV.setText('is-p-rent-sub', `อัตรา ${IV.money(d.rent_rate)} / เดือน`);
        IV.setText('is-p-water', IV.money(d.i_water));
        IV.setText('is-p-water-sub', `${IV.num(d.water_usage)} หน่วย × ${IV.money(d.water_rate)}`);
        IV.setText('is-p-elec', IV.money(d.i_elec));
        IV.setText('is-p-elec-sub', `${IV.num(d.elec_usage)} หน่วย × ${IV.money(d.elec_rate)}`);
        IV.setText('is-p-total', IV.money(d.i_total));
        showStep(2);
    });

    /* ขั้น 2 → ออกจริง */
    $('is-issue-btn').addEventListener('click', async () => {
        if (busy || !snapshot) return;
        clear();
        busy = true;
        $('is-issue-btn').disabled = true;
        $('is-back').disabled = true;
        const r = await IV.api('POST', '/api/v1/invoices', { json: snapshot });
        busy = false;
        $('is-issue-btn').disabled = false;
        $('is-back').disabled = false;

        if (r.ok) {
            finished = true;
            created = r.body.data ?? null;
            dlg.close();
            IV.toast('ออกใบแจ้งหนี้สำเร็จ');
            return;
        }
        if (r.status === 422) {
            // ข้อมูลเปลี่ยนไประหว่างพรีวิวกับยืนยัน (เช่น มิเตอร์ถูกแก้) → กลับไปขั้น 1 พร้อมข้อความ
            showStep(1);
            const shown = mapErrors(r);
            const autoMsg = isAuto() ? autoPeriodMessage(r) : '';
            if (autoMsg) IV.showBanner($('is-banner'), 'err', autoMsg);
            else if (!shown) IV.showBanner($('is-banner'), 'err', IV.errMsg(r, 'ข้อมูลไม่ถูกต้อง'));
            return;
        }
        IV.showBanner($('is-banner'), 'err', conflictMessage(r));
    });

    window.InvoiceIssue = { open };
})();
</script>
