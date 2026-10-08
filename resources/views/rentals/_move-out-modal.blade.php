{{-- resources/views/rentals/_move-out-modal.blade.php --}}
{{-- Modal "ย้ายออก" (Move-out) — เรียก POST /api/v1/rentals/{rental}/move-out --}}
{{-- ใช้: @include('rentals._move-out-modal') ภายใน <div class="rt ..."> ของหน้า R1 / R3 แล้วเรียก
     window.RentalMoveOut.open({ rentalId, tenantName, roomName, roomId, moveIn, onDone }) --}}

@include('rentals._styles')

@once
<style>
    .rt-dialog { width: min(560px, 92vw); max-height: 90vh; padding: 0; border: 1px solid var(--rt-border); border-radius: 16px; background: var(--rt-card); color: var(--rt-text); box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45); overflow: auto; }
    .rt-dialog::backdrop { background: rgba(0, 0, 0, 0.6); }
    .rt-dialog-body { padding: 22px 24px; }
    .rt-steps { display: flex; gap: 6px; margin-bottom: 16px; }
    .rt-steps > span { flex: 1; height: 4px; border-radius: 999px; background: var(--rt-border); }
    .rt-steps > span.is-on { background: var(--rt-primary); }
    .rt-summary { padding: 12px 14px; border: 1px solid var(--rt-border); border-radius: 12px; background: var(--rt-card-soft); margin-bottom: 16px; }
    .rt-summary dl { margin: 0; display: grid; gap: 6px; }
    .rt-summary dl > div { display: flex; justify-content: space-between; gap: 12px; font-size: 0.875rem; }
    .rt-summary dt { color: var(--rt-muted); }
    .rt-summary dd { margin: 0; text-align: right; font-weight: 600; word-break: break-word; }
    .rt-effects { margin: 0 0 16px; padding-left: 20px; font-size: 0.875rem; line-height: 1.7; }
    .rt-reason-box { white-space: pre-wrap; word-break: break-word; font-size: 0.875rem; }
    .rt-mini-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; margin-top: 8px; }
    .rt-mini-table th { text-align: left; color: var(--rt-muted); font-weight: 500; padding: 6px 8px; border-bottom: 1px solid var(--rt-border); }
    .rt-mini-table td { padding: 8px; border-bottom: 1px solid var(--rt-border); }
    .rt-mini-table td.num, .rt-mini-table th.num { text-align: right; }
    .rt-mini-table tfoot td { font-weight: 700; border-bottom: none; }
</style>
@endonce

<dialog class="rt-dialog" id="mo-dialog" aria-labelledby="mo-title">
    <div class="rt-dialog-body">
        <div class="rt-modal-head">
            <h2 class="rt-modal-title" id="mo-title">ย้ายออก</h2>
            <button type="button" class="rt-close" id="mo-x" aria-label="ปิด" style="background:none;border:none;cursor:pointer;">✕</button>
        </div>

        <div class="rt-steps" aria-hidden="true">
            <span id="mo-s1" class="is-on"></span><span id="mo-s2"></span><span id="mo-s3"></span>
        </div>

        <div class="rt-summary">
            <dl>
                <div><dt>ผู้เช่า</dt><dd id="mo-tenant">-</dd></div>
                <div><dt>ห้อง</dt><dd id="mo-room">-</dd></div>
                <div><dt>วันที่เข้าพัก</dt><dd id="mo-movein">-</dd></div>
            </dl>
        </div>

        {{-- ขั้น 1: กรอกข้อมูล --}}
        <form id="mo-form" novalidate>
            <div class="rt-form-field">
                <label for="mo-date">วันที่ย้ายออก <span class="rt-req">*</span></label>
                <input type="date" id="mo-date" class="rt-input" required>
                <span class="rt-hint">เลือกย้อนหลังได้ (ไม่เกินวันนี้ และไม่ก่อนวันเข้าพัก)</span>
                <span class="rt-field-error" id="mo-err-date"></span>
            </div>
            <p id="mo-meter-warn" class="rt-notice rt-notice--warn" hidden>
                ยังไม่พบเลขมิเตอร์ของวันที่เลือก — ระบบจะปฏิเสธการย้ายออกจนกว่าจะบันทึกมิเตอร์ของวันนั้นก่อน
            </p>
            <div class="rt-form-field">
                <label for="mo-reason">เหตุผล <span class="rt-req">*</span></label>
                <textarea id="mo-reason" class="rt-input" maxlength="2000" required placeholder="เช่น ผู้เช่าแจ้งย้ายออกเมื่อวันที่ ..."></textarea>
                <span class="rt-hint" id="mo-reason-count">0 / 2000</span>
                <span class="rt-field-error" id="mo-err-reason"></span>
            </div>
            <div class="rt-form-actions">
                <button type="button" class="rt-btn" id="mo-cancel">ยกเลิก</button>
                <button type="submit" class="rt-btn rt-btn--primary">ถัดไป: ตรวจสอบ</button>
            </div>
        </form>

        {{-- ขั้น 2: ยืนยัน --}}
        <div id="mo-confirm" hidden>
            <div class="rt-summary">
                <dl>
                    <div><dt>วันที่ย้ายออก</dt><dd id="mo-c-date">-</dd></div>
                </dl>
                <div class="rt-field-label" style="margin-top:10px;">เหตุผล</div>
                <div class="rt-reason-box" id="mo-c-reason"></div>
            </div>
            <p class="rt-label" style="margin-bottom:6px;">เมื่อยืนยัน ระบบจะทำสิ่งต่อไปนี้ <strong>ย้อนกลับไม่ได้</strong>:</p>
            <ul class="rt-effects">
                <li>ออกใบแจ้งหนี้ให้อัตโนมัติสำหรับช่วงที่ยังไม่ได้เรียกเก็บ จนถึงวันย้ายออก (ใช้อัตราค่าน้ำ-ไฟ-ค่าเช่า <strong>ปัจจุบัน</strong>)</li>
                <li>ปิดสัญญาเป็นสถานะ "สิ้นสุด" และการเช่าเป็น "สิ้นสุดแล้ว"</li>
                <li>เปลี่ยนห้องเป็น "ว่าง"</li>
                <li>ไม่บังคับให้ชำระหนี้ค้างก่อนย้ายออก</li>
            </ul>
            <p id="mo-error" class="rt-notice rt-notice--err" hidden></p>
            <div class="rt-form-actions">
                <button type="button" class="rt-btn" id="mo-back">‹ แก้ไขข้อมูล</button>
                <button type="button" class="rt-btn rt-btn--danger" id="mo-confirm-btn">ยืนยันย้ายออก</button>
            </div>
        </div>

        {{-- ขั้น 3: ผลลัพธ์ --}}
        <div id="mo-done" hidden>
            <p class="rt-notice rt-notice--ok" style="margin-bottom:12px;">✓ บันทึกการย้ายออกเรียบร้อย</p>
            <div id="mo-invoices"></div>
            <div class="rt-form-actions">
                <button type="button" class="rt-btn rt-btn--primary" id="mo-close">เสร็จสิ้น</button>
            </div>
        </div>
    </div>
</dialog>

<script>
(function () {
    const dlg = document.getElementById('mo-dialog');
    const $ = (id) => document.getElementById(id);
    const fetchOpts = { headers: { Accept: 'application/json' }, credentials: 'same-origin' };

    const money = (n) => '฿' + Number(n).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const day = (s) => String(s ?? '').slice(0, 10) || '-';
    const todayStr = () => new Date().toLocaleDateString('sv-SE', { timeZone: 'Asia/Bangkok' });
    const escapeHtml = (s) => { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; };

    let ctx = null;          // ข้อมูลการเช่าที่กำลังย้ายออก
    let step = 1;
    let submitting = false;
    let finished = false;    // ย้ายออกสำเร็จแล้ว (ใช้ตัดสินใจว่าต้องรีเฟรชหน้าหลังปิดไหม)
    let meterDates = null;   // Set ของวันที่ที่มีมิเตอร์ของห้องนี้ (null = ตรวจไม่ได้)
    let meterSeq = 0;        // กัน response เก่าทับของใหม่

    function csrf() { return document.querySelector('meta[name="csrf-token"]')?.content ?? ''; }

    function showStep(n) {
        step = n;
        $('mo-form').hidden = n !== 1;
        $('mo-confirm').hidden = n !== 2;
        $('mo-done').hidden = n !== 3;
        $('mo-s1').classList.toggle('is-on', n >= 1);
        $('mo-s2').classList.toggle('is-on', n >= 2);
        $('mo-s3').classList.toggle('is-on', n >= 3);
        $('mo-title').textContent = n === 3 ? 'ย้ายออกเรียบร้อย' : 'ย้ายออก';
    }

    function clearErrors() {
        $('mo-err-date').textContent = '';
        $('mo-err-reason').textContent = '';
        $('mo-error').hidden = true;
    }

    async function loadMeterDates(roomId) {
        const seq = ++meterSeq;
        meterDates = null;
        updateMeterWarn();
        if (!roomId) return;
        try {
            const res = await fetch(`/api/v1/rooms/${roomId}/meters?per_page=100`, fetchOpts);
            if (!res.ok) return; // ตรวจไม่ได้ก็ไม่เตือน ให้ backend ตัดสินตอนส่ง
            const body = await res.json();
            if (seq !== meterSeq) return;
            meterDates = new Set((body.data ?? []).map((m) => String(m.m_date).slice(0, 10)));
            updateMeterWarn();
        } catch (_) { /* เงียบไว้ */ }
    }

    function updateMeterWarn() {
        const v = $('mo-date').value;
        $('mo-meter-warn').hidden = !(meterDates && v && !meterDates.has(v));
    }

    function open(opts) {
        ctx = opts;
        finished = false;
        submitting = false;
        clearErrors();

        $('mo-tenant').textContent = opts.tenantName || '-';
        $('mo-room').textContent = opts.roomName || '-';
        $('mo-movein').textContent = day(opts.moveIn);

        const today = todayStr();
        const dateInput = $('mo-date');
        dateInput.min = day(opts.moveIn) === '-' ? '' : day(opts.moveIn);
        dateInput.max = today;
        dateInput.value = today;
        $('mo-reason').value = '';
        $('mo-reason-count').textContent = '0 / 2000';

        showStep(1);
        loadMeterDates(opts.roomId);
        dlg.showModal();
        dateInput.focus();
    }

    function close() {
        if (dlg.open) dlg.close();
    }

    // ปิด dialog ได้หลายทาง (Esc, ปุ่ม X) — ถ้าย้ายออกสำเร็จแล้วให้หน้าหลักรีเฟรชข้อมูล
    dlg.addEventListener('close', () => {
        const done = finished && ctx && typeof ctx.onDone === 'function';
        const cb = done ? ctx.onDone : null;
        finished = false;
        if (cb) cb();
    });
    // กด Esc ระหว่างกำลังบันทึก → ไม่ให้ปิด (ไม่งั้นหน้าไม่รีเฟรชทั้งที่ย้ายออกสำเร็จ)
    dlg.addEventListener('cancel', (e) => { if (submitting) e.preventDefault(); });
    dlg.addEventListener('click', (e) => { if (e.target === dlg && step !== 2 && !submitting) close(); });

    $('mo-x').addEventListener('click', () => { if (!submitting) close(); });
    $('mo-cancel').addEventListener('click', close);
    $('mo-close').addEventListener('click', close);
    $('mo-date').addEventListener('change', updateMeterWarn);
    $('mo-reason').addEventListener('input', () => {
        $('mo-reason-count').textContent = `${$('mo-reason').value.length} / 2000`;
    });

    // ขั้น 1 → 2 (ตรวจเบื้องต้นฝั่งหน้าจอ แล้วให้ backend ตัดสินจริงตอนยืนยัน)
    $('mo-form').addEventListener('submit', (e) => {
        e.preventDefault();
        clearErrors();
        const date = $('mo-date').value;
        const reason = $('mo-reason').value.trim();
        let ok = true;

        if (!date) { $('mo-err-date').textContent = 'กรุณาเลือกวันที่ย้ายออก'; ok = false; }
        else if (date > todayStr()) { $('mo-err-date').textContent = 'วันที่ย้ายออกต้องไม่เกินวันนี้'; ok = false; }
        else if (ctx.moveIn && date < day(ctx.moveIn)) { $('mo-err-date').textContent = 'วันที่ย้ายออกต้องไม่ก่อนวันเข้าพัก'; ok = false; }

        if (!reason) { $('mo-err-reason').textContent = 'กรุณาระบุเหตุผล'; ok = false; }
        if (!ok) return;

        $('mo-c-date').textContent = date;
        $('mo-c-reason').textContent = reason;
        showStep(2);
    });

    $('mo-back').addEventListener('click', () => { if (!submitting) { clearErrors(); showStep(1); } });

    $('mo-confirm-btn').addEventListener('click', async () => {
        if (submitting) return;
        submitting = true;
        $('mo-confirm-btn').disabled = true;
        $('mo-back').disabled = true;
        clearErrors();

        try {
            const res = await fetch(`/api/v1/rentals/${ctx.rentalId}/move-out`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                credentials: 'same-origin',
                body: JSON.stringify({ rt_moveout: $('mo-date').value, reason: $('mo-reason').value.trim() }),
            });

            let body = {};
            try { body = await res.json(); } catch (_) {}

            if (res.status === 401) { window.location.href = '/login'; return; }

            if (res.status === 422) {
                // ข้อผิดพลาดของช่องกรอก → กลับไปขั้น 1 แล้วแสดงใต้ช่อง
                if (body.errors?.rt_moveout) $('mo-err-date').textContent = body.errors.rt_moveout[0];
                if (body.errors?.reason) $('mo-err-reason').textContent = body.errors.reason[0];
                if (!body.errors?.rt_moveout && !body.errors?.reason) {
                    // error จากการออกบิลสุดท้าย (เช่น period_start / period_end) ไม่ใช่ช่องในฟอร์ม → บอกสาเหตุจริง
                    const other = Object.values(body.errors || {}).flat()[0];
                    $('mo-err-date').textContent = other
                        ? 'ออกบิลสุดท้ายไม่ได้: ' + other + ' — บันทึกมิเตอร์ให้ครบก่อนแล้วลองใหม่'
                        : (body.message || 'ข้อมูลไม่ถูกต้อง');
                }
                showStep(1);
                return;
            }
            if (res.status === 403) throw new Error('คุณไม่มีสิทธิ์ย้ายออกรายการนี้');
            if (res.status === 409) {
                throw new Error((body.message || 'สถานะข้อมูลเปลี่ยนไป ไม่สามารถย้ายออกได้') + ' — ปิดหน้าต่างนี้แล้วโหลดข้อมูลใหม่');
            }
            if (!res.ok) throw new Error(body.message || ('ย้ายออกไม่สำเร็จ (' + res.status + ')'));

            finished = true;
            renderResult(body.data?.created_invoices ?? []);
            showStep(3);
        } catch (err) {
            $('mo-error').textContent = err.message;
            $('mo-error').hidden = false;
        } finally {
            submitting = false;
            $('mo-confirm-btn').disabled = false;
            $('mo-back').disabled = false;
        }
    });

    function renderResult(invoices) {
        const box = $('mo-invoices');
        if (!invoices.length) {
            box.innerHTML = '<p class="rt-muted" style="font-size:0.875rem;margin:0 0 12px;">ไม่มีช่วงที่ต้องออกบิลเพิ่ม เนื่องจากเรียกเก็บครบถึงวันย้ายออกแล้ว</p>';
            return;
        }
        const total = invoices.reduce((s, i) => s + Number(i.i_total ?? 0), 0);
        box.innerHTML = `
            <div class="rt-label">ใบแจ้งหนี้ที่ระบบออกให้ ${invoices.length} ใบ</div>
            <table class="rt-mini-table">
                <thead><tr><th>เลขที่</th><th>ช่วงที่เรียกเก็บ</th><th class="num">ยอดรวม</th></tr></thead>
                <tbody>
                    ${invoices.map((i) => `
                        <tr>
                            <td>#${escapeHtml(String(i.i_id))}</td>
                            <td>${escapeHtml(day(i.period_start))} → ${escapeHtml(day(i.period_end))}</td>
                            <td class="num">${money(i.i_total ?? 0)}</td>
                        </tr>`).join('')}
                </tbody>
                <tfoot><tr><td colspan="2">รวม</td><td class="num">${money(total)}</td></tr></tfoot>
            </table>
            <p class="rt-hint" style="margin:8px 0 12px;">ใบแจ้งหนี้ทั้งหมดอยู่ในสถานะ "ยังไม่ชำระ"</p>`;
    }

    window.RentalMoveOut = { open };
})();
</script>
