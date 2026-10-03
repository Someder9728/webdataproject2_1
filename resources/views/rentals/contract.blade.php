{{-- resources/views/rentals/contract.blade.php --}}
{{-- การ์ดสัญญาเช่า (R3) + ฟอร์มแก้ไข/ต่อสัญญา (R4) --}}
{{-- ใช้งาน: @include('rentals.contract', ['rentalId' => $rentalId]) ภายในหน้า R3 --}}
{{-- ส่ง event ให้หน้า R3: 'rental:contract-loaded' / 'rental:contract-updated' (detail = ข้อมูลสัญญา) เพื่อให้ไทม์ไลน์อัปเดต --}}

@include('rentals._styles')

@php($canEditContract = auth()->user()?->u_role === 'admin')

<section class="rt-card contract-panel" data-rental-id="{{ $rentalId }}" aria-labelledby="contract-card-title">
    <div id="contract-state-loading" class="rt-state">กำลังโหลดสัญญา...</div>
    <div id="contract-state-error" class="rt-state rt-state--error" hidden>
        <span id="contract-error-message"></span>
        <button type="button" id="contract-btn-retry" class="rt-btn rt-btn--sm">ลองใหม่</button>
    </div>

    <div id="contract-view" hidden>
        <div class="rt-card-head">
            <h2 class="rt-card-title" id="contract-card-title">ข้อมูลสัญญาเช่า</h2>
            <span id="c-status" class="rt-badge"></span>
        </div>

        <div class="rt-field-value rt-mono" id="c-number" style="margin-bottom:12px;">-</div>

        <div class="rt-fields">
            <div><span class="rt-field-label">วันเริ่มสัญญา</span><span class="rt-field-value" id="c-start">-</span></div>
            <div><span class="rt-field-label">วันสิ้นสุดสัญญา</span><span class="rt-field-value" id="c-end">-</span></div>
            <div><span class="rt-field-label">ค่าเช่าตามสัญญา</span><span class="rt-field-value rt-money" id="c-rent">-</span></div>
            <div><span class="rt-field-label">เงินประกัน</span><span class="rt-field-value" id="c-deposit">-</span></div>
        </div>

        <p id="c-expired-note" class="rt-notice rt-notice--warn" style="margin-top:14px;margin-bottom:0;" hidden>
            สัญญาหมดอายุแล้วแต่ยังพักอยู่ — ยังออกบิลต่อได้ตามปกติ ห้องจะไม่ถูกปรับเป็นว่างเอง
        </p>

        <p id="contract-saved-note" class="rt-notice rt-notice--ok" style="margin-top:14px;margin-bottom:0;" hidden>
            บันทึกการแก้ไขสัญญาเรียบร้อย
        </p>

        @if ($canEditContract)
            <div style="margin-top:16px;">
                <button type="button" id="contract-btn-edit" class="rt-btn rt-btn--sm">แก้ไข / ต่อสัญญา</button>
            </div>
        @endif
    </div>

    {{-- R4: ฟอร์มแก้สัญญา — ซ่อนไว้ก่อน เปิดเมื่อกด "แก้ไข / ต่อสัญญา" --}}
    @if ($canEditContract)
        <form id="contract-edit-form" hidden style="margin-top:16px;padding-top:16px;border-top:1px solid var(--rt-border);">
            <h3 class="rt-card-title rt-card-title--lg" style="margin-bottom:12px;">แก้ไข / ต่อสัญญา</h3>

            <div class="rt-form-field">
                <label for="f-c-end">วันสิ้นสุดสัญญาใหม่ <span class="rt-muted" style="font-weight:400;">(เว้นว่าง = ไม่กำหนด)</span></label>
                <input type="date" id="f-c-end" name="c_end" class="rt-input">
                <div class="rt-chips" id="extend-chips" hidden>
                    <span class="rt-hint" style="margin:0 4px 0 0;align-self:center;">ต่อเพิ่ม:</span>
                    <button type="button" class="rt-chip" data-months="1">+1 เดือน</button>
                    <button type="button" class="rt-chip" data-months="3">+3 เดือน</button>
                    <button type="button" class="rt-chip" data-months="6">+6 เดือน</button>
                    <button type="button" class="rt-chip" data-months="12">+1 ปี</button>
                </div>
                <span class="rt-hint" id="extend-hint" hidden></span>
                <span class="rt-field-error" id="err-c-end"></span>
            </div>

            <div class="rt-form-field">
                <label for="f-reason">เหตุผล <span class="rt-req">*</span></label>
                <textarea id="f-reason" name="reason" class="rt-input" required maxlength="500" placeholder="เช่น ผู้เช่าขอต่อสัญญาอีก 6 เดือน"></textarea>
                <span class="rt-hint" id="reason-count">0 / 500</span>
                <span class="rt-field-error" id="err-reason"></span>
            </div>

            <p id="contract-form-error" class="rt-notice rt-notice--err" hidden></p>

            <div class="rt-form-actions" style="margin-top:8px;">
                <button type="button" id="contract-btn-cancel" class="rt-btn">ยกเลิก</button>
                <button type="submit" id="contract-btn-save" class="rt-btn rt-btn--primary">บันทึก</button>
            </div>
        </form>
    @endif
</section>

<script>
(function () {
    const panel = document.querySelector('.contract-panel');
    const rentalId = panel.dataset.rentalId;
    const API_URL = `/api/v1/rentals/${rentalId}/contract`;
    const fetchOpts = { headers: { Accept: 'application/json' }, credentials: 'same-origin' };

    // ค่า c_status ยืนยันกับ backend แล้ว: ACTIVE / EXPIRED / ENDED
    const STATUS_LABEL = {
        ACTIVE:  { text: 'มีผล', cls: 'rt-badge--ok' },
        EXPIRED: { text: 'หมดอายุ (ยังพักอยู่)', cls: 'rt-badge--warn' },
        ENDED:   { text: 'สิ้นสุด', cls: 'rt-badge--off' },
    };

    const el = {};
    ['contract-state-loading', 'contract-state-error', 'contract-error-message', 'contract-btn-retry',
     'contract-view', 'c-number', 'c-start', 'c-end', 'c-rent', 'c-deposit', 'c-status', 'c-expired-note',
     'contract-saved-note', 'contract-btn-edit', 'contract-edit-form', 'f-c-end', 'f-reason', 'err-c-end',
     'err-reason', 'contract-btn-save', 'contract-btn-cancel', 'contract-form-error', 'extend-chips',
     'extend-hint', 'reason-count'].forEach((id) => { el[id] = document.getElementById(id); }); // ปุ่ม/ฟอร์มแก้ไขมีเฉพาะ admin

    let currentContract = null;
    let saving = false; // กันกดบันทึกซ้ำ

    const money = (n) => '฿' + Number(n).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function showState(name) {
        el['contract-state-loading'].hidden = name !== 'loading';
        el['contract-state-error'].hidden = name !== 'error';
        el['contract-view'].hidden = name !== 'view' && name !== 'edit';
        if (el['contract-edit-form']) el['contract-edit-form'].hidden = name !== 'edit';
        if (el['contract-btn-edit']) el['contract-btn-edit'].hidden = name === 'edit';
    }

    function announce(type, contract) {
        window.__rentalContract = contract; // เผื่อหน้า R3 ยังไม่ทันฟัง event
        document.dispatchEvent(new CustomEvent(type, { detail: contract }));
    }

    async function loadContract() {
        showState('loading');
        try {
            const res = await fetch(API_URL, fetchOpts);
            if (res.status === 404) throw new Error('ไม่พบสัญญาของการเช่านี้');
            if (!res.ok) throw new Error('โหลดสัญญาไม่สำเร็จ (' + res.status + ')');

            const body = await res.json();
            currentContract = body.data;
            renderView(currentContract);
            announce('rental:contract-loaded', currentContract);
        } catch (err) {
            el['contract-error-message'].textContent = err.message;
            showState('error');
        }
    }

    function renderView(c) {
        el['c-number'].textContent = c.c_number ?? '-';
        el['c-start'].textContent = c.c_start ?? '-';
        el['c-end'].textContent = c.c_end ?? 'ไม่กำหนดวันสิ้นสุด';
        el['c-rent'].textContent = c.c_rent != null ? money(c.c_rent) + '/เดือน' : '-';
        el['c-deposit'].textContent = c.c_deposit != null ? money(c.c_deposit) : '-';

        const status = STATUS_LABEL[c.c_status] || { text: c.c_status, cls: 'rt-badge--off' };
        el['c-status'].textContent = status.text;
        el['c-status'].className = 'rt-badge ' + status.cls;

        el['c-expired-note'].hidden = c.c_status !== 'EXPIRED';

        // แก้/ต่อสัญญาได้เฉพาะสัญญาที่ยังไม่สิ้นสุด
        if (el['contract-btn-edit']) el['contract-btn-edit'].hidden = c.c_status === 'ENDED';

        showState('view');
    }

    // ---------- R4: แก้ไข / ต่อสัญญา ----------
    function todayStr() {
        return new Date().toLocaleDateString('sv-SE', { timeZone: 'Asia/Bangkok' });
    }

    // บวกเดือนกับวันที่ YYYY-MM-DD (ถ้าวันเกินเดือนปลายทาง เช่น 31 → ใช้วันสุดท้ายของเดือนนั้น)
    function addMonths(dateStr, months) {
        const [y, m, d] = dateStr.split('-').map(Number);
        const target = new Date(Date.UTC(y, m - 1 + months, 1));
        const lastDay = new Date(Date.UTC(target.getUTCFullYear(), target.getUTCMonth() + 1, 0)).getUTCDate();
        target.setUTCDate(Math.min(d, lastDay));
        return target.toISOString().slice(0, 10);
    }

    function extendBase() {
        // ต่อจากวันสิ้นสุดเดิม ถ้าสัญญาหมดไปแล้วให้นับจากวันนี้
        const end = currentContract.c_end;
        const today = todayStr();
        return end && end >= today ? end : today;
    }

    function openEditForm() {
        el['f-c-end'].value = currentContract.c_end ?? '';
        el['f-reason'].value = '';
        el['reason-count'].textContent = '0 / 500';
        el['contract-saved-note'].hidden = true;
        clearFieldErrors();

        // ปุ่มต่อเพิ่มแสดงเฉพาะสัญญาที่มีวันสิ้นสุด
        const hasEnd = !!currentContract.c_end;
        el['extend-chips'].hidden = !hasEnd;
        el['extend-hint'].hidden = !hasEnd;
        if (hasEnd) {
            el['extend-hint'].textContent = `ต่อเพิ่มจะนับจาก ${extendBase()} — ยังไม่บันทึกจนกว่าจะกด "บันทึก"`;
        }
        showState('edit');
        el['f-c-end'].focus();
    }

    function clearFieldErrors() {
        el['err-c-end'].textContent = '';
        el['err-reason'].textContent = '';
        el['contract-form-error'].hidden = true;
    }

    async function submitEdit(e) {
        e.preventDefault();
        if (saving) return;
        saving = true;
        el['contract-btn-save'].disabled = true;
        clearFieldErrors();

        try {
            const res = await fetch(API_URL, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    c_end: el['f-c-end'].value || null,
                    reason: el['f-reason'].value,
                }),
            });

            const body = await res.json();

            if (res.status === 422) {
                if (body.errors?.c_end) el['err-c-end'].textContent = body.errors.c_end[0];
                if (body.errors?.reason) el['err-reason'].textContent = body.errors.reason[0];
                return;
            }
            if (res.status === 409) {
                el['contract-form-error'].textContent = body.message;
                el['contract-form-error'].hidden = false;
                await loadContract(); // โหลดสถานะล่าสุดตามที่ error บอก
                return;
            }
            if (!res.ok) {
                throw new Error(body.message || 'บันทึกไม่สำเร็จ');
            }

            currentContract = body.data;
            renderView(currentContract);
            el['contract-saved-note'].hidden = false;
            announce('rental:contract-updated', currentContract);
        } catch (err) {
            el['contract-form-error'].textContent = err.message;
            el['contract-form-error'].hidden = false;
        } finally {
            saving = false;
            el['contract-btn-save'].disabled = false;
        }
    }

    el['contract-btn-retry'].addEventListener('click', loadContract);

    if (el['contract-edit-form']) {
        el['contract-btn-edit'].addEventListener('click', openEditForm);
        el['contract-btn-cancel'].addEventListener('click', () => showState('view'));
        el['contract-edit-form'].addEventListener('submit', submitEdit);
        el['f-reason'].addEventListener('input', () => {
            el['reason-count'].textContent = `${el['f-reason'].value.length} / 500`;
        });
        el['extend-chips'].addEventListener('click', (ev) => {
            const btn = ev.target.closest('[data-months]');
            if (!btn) return;
            el['f-c-end'].value = addMonths(extendBase(), Number(btn.dataset.months));
        });
    }

    loadContract();
})();
</script>
