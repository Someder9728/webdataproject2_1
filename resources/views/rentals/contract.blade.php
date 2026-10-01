{{-- resources/views/rentals/_contract-panel.blade.php --}}
{{-- ใช้ @include('rentals._contract-panel', ['rentalId' => $rental->rt_id]) เพิ่อดึงหน้า รายละเอียด contract มาเบิ่ง--}}

<div class="contract-panel" data-rental-id="{{ $rentalId }}">
    <div id="contract-state-loading" class="state-box">กำลังโหลดสัญญา...</div>
    <div id="contract-state-error" class="state-box state-box--error" hidden>
        <span id="contract-error-message"></span>
        <button type="button" id="contract-btn-retry">ลองใหม่</button>
    </div>

    <div id="contract-view" hidden>
        <div class="contract-panel__grid">
            <div><span class="label">เลขที่สัญญา</span><span id="c-number"></span></div>
            <div><span class="label">วันเริ่ม</span><span id="c-start"></span></div>
            <div><span class="label">วันสิ้นสุด</span><span id="c-end"></span></div>
            <div><span class="label">ค่าเช่า</span><span id="c-rent"></span></div>
            <div><span class="label">สถานะ</span><span id="c-status" class="badge"></span></div>
        </div>

        <p id="c-expired-note" class="notice notice--warning" hidden>
            สัญญาหมดอายุแล้วแต่ยังพักอยู่ — ยังออกบิลต่อได้ตามปกติ ห้องจะไม่ถูกปรับเป็นว่างเอง
        </p>

        <button type="button" id="contract-btn-edit" class="btn">แก้ไข / ต่อสัญญา</button>

        <div class="contract-history">
            <h3>ประวัติการแก้สัญญา</h3>
            <div id="history-loading" class="state-box state-box--small">กำลังโหลดประวัติ...</div>
            <div id="history-empty" class="state-box state-box--small" hidden>ยังไม่มีประวัติการแก้ไข</div>
            <ul id="history-list" class="history-list" hidden></ul>
        </div>
    </div>

    {{-- R4: ฟอร์มแก้สัญญา — ซ่อนไว้ก่อน เปิดเมื่อกด "แก้ไข / ต่อสัญญา" --}}
    <form id="contract-edit-form" hidden>
        <div class="form-field">
            <label for="f-c-end">วันสิ้นสุดสัญญาใหม่ (เว้นว่าง = ไม่กำหนด)</label>
            <input type="date" id="f-c-end" name="c_end">
            <span class="field-error" id="err-c-end"></span>
        </div>
        <div class="form-field">
            <label for="f-reason">เหตุผล (จำเป็น)</label>
            <textarea id="f-reason" name="reason" required maxlength="500"></textarea>
            <span class="field-error" id="err-reason"></span>
        </div>
        <div class="form-actions">
            <button type="submit" id="contract-btn-save" class="btn btn--primary">บันทึก</button>
            <button type="button" id="contract-btn-cancel" class="btn">ยกเลิก</button>
        </div>
        <p id="contract-form-error" class="notice notice--error" hidden></p>
    </form>
</div>

<style>
    .contract-panel { border: 1px solid #eee; border-radius: 6px; padding: 16px; margin-top: 16px; }
    .contract-panel__grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 12px; }
    .contract-panel__grid .label { display: block; font-size: 0.85em; color: #666; }
    .notice { padding: 8px 12px; border-radius: 4px; margin: 8px 0; }
    .notice--warning { background: #fef9c3; color: #854d0e; }
    .notice--error { background: #fef2f2; color: #b91c1c; }
    .badge { padding: 2px 8px; border-radius: 999px; font-size: 0.85em; }
    .badge--active { background: #dcfce7; color: #166534; }
    .badge--warning { background: #fef9c3; color: #854d0e; }
    .badge--ended { background: #f3f4f6; color: #4b5563; }
    .form-field { margin-bottom: 12px; }
    .form-field label { display: block; margin-bottom: 4px; font-size: 0.9em; }
    .form-field input, .form-field textarea { width: 100%; max-width: 400px; padding: 6px 10px; border: 1px solid #ccc; border-radius: 4px; }
    .field-error { color: #b91c1c; font-size: 0.85em; display: block; }
    .form-actions { display: flex; gap: 8px; }
    .contract-history { margin-top: 20px; border-top: 1px solid #eee; padding-top: 12px; }
    .contract-history h3 { font-size: 0.95em; margin-bottom: 8px; }
    .state-box--small { padding: 8px 0; text-align: left; border: none; color: #888; font-size: 0.85em; }
    .history-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px; }
    .history-list li { font-size: 0.85em; padding: 8px 10px; background: #fafafa; border-radius: 4px; }
    .history-list .history-reason {color: #333333; /* หรือสีเข้มที่ต้องการ เช่น #1f2937 */margin-top: 4px; }
    .history-list .history-meta { color: #020202; display: block; margin-bottom: 2px; }
</style>

<script>
(function () {
    const rentalId = document.querySelector('.contract-panel').dataset.rentalId;
    const API_URL = `/api/v1/rentals/${rentalId}/contract`;

    // TODO(ยืนยันกับพรี่เกลือ): ค่า c_status จริง — ตอนนี้สมมติ ACTIVE/EXPIRED/ENDED
    const STATUS_LABEL = {
        ACTIVE:  { text: 'มีผล', cls: 'badge--active' },
        EXPIRED: { text: 'หมดอายุ (ยังพักอยู่)', cls: 'badge--warning' },
        ENDED:   { text: 'สิ้นสุด', cls: 'badge--ended' },
    };

    const el = {};
    ['contract-state-loading', 'contract-state-error', 'contract-error-message', 'contract-btn-retry',
     'contract-view', 'c-number', 'c-start', 'c-end', 'c-rent', 'c-status', 'c-expired-note',
     'contract-btn-edit', 'contract-edit-form', 'f-c-end', 'f-reason', 'err-c-end', 'err-reason',
     'contract-btn-save', 'contract-btn-cancel', 'contract-form-error',
     'history-loading', 'history-empty', 'history-list'].forEach((id) => {
        el[id] = document.getElementById(id);
    });

    let currentContract = null;
    let saving = false; // กันกดบันทึกซ้ำ

    function showState(name) {
        el['contract-state-loading'].hidden = name !== 'loading';
        el['contract-state-error'].hidden = name !== 'error';
        el['contract-view'].hidden = name !== 'view' && name !== 'edit';
        el['contract-edit-form'].hidden = name !== 'edit';
    }

    async function loadContract() {
        showState('loading');
        try {
            const res = await fetch(API_URL, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (res.status === 404) throw new Error('ไม่พบสัญญาของการเช่านี้');
            if (!res.ok) throw new Error('โหลดสัญญาไม่สำเร็จ (' + res.status + ')');

            const body = await res.json();
            currentContract = body.data;
            renderView(currentContract);
            loadHistory(currentContract.c_id);
        } catch (err) {
            el['contract-error-message'].textContent = err.message;
            showState('error');
        }
    }

    
    async function loadHistory(contractId) {
        el['history-loading'].hidden = false;
        el['history-empty'].hidden = true;
        el['history-list'].hidden = true;

        try {
            const res = await fetch(`/api/v1/audit-events?entity_type=contract&entity_id=${contractId}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('โหลดประวัติไม่สำเร็จ');

            const body = await res.json();
            renderHistory(body.data ?? []);
        } catch (err) {
            // ประวัติโหลดไม่ได้ ไม่ควรบล็อกการดูสัญญาหลัก แค่โชว์ว่าง
            el['history-loading'].hidden = true;
            el['history-empty'].hidden = false;
            el['history-empty'].textContent = 'โหลดประวัติไม่สำเร็จ';
        }
    }

    function renderHistory(events) {
        el['history-loading'].hidden = true;

        if (!events.length) {
            el['history-empty'].hidden = false;
            el['history-list'].hidden = true;
            return;
        }

        el['history-list'].innerHTML = events.map((e) => {
            const who = e.actor ? e.actor.u_username : 'ระบบ';
            const when = new Date(e.created_at).toLocaleString('th-TH');
 const reasonLine = e.reason ? `<div class="history-reason">เหตุผล: ${escapeHtml(e.reason)}</div>` : '';
            return `
                <li>
                    <span class="history-meta">${escapeHtml(who)} • ${when} • ${escapeHtml(e.action)}</span>
                    ${reasonLine}
                </li>
            `;
        }).join('');

        el['history-empty'].hidden = true;
        el['history-list'].hidden = false;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    function renderView(c) {
        el['c-number'].textContent = c.c_number;
        el['c-start'].textContent = c.c_start;
        el['c-end'].textContent = c.c_end ?? 'ไม่กำหนดวันสิ้นสุด';
        el['c-rent'].textContent = Number(c.c_rent).toLocaleString('th-TH', { minimumFractionDigits: 2 }) + ' บาท';

        const status = STATUS_LABEL[c.c_status] || { text: c.c_status, cls: '' };
        el['c-status'].textContent = status.text;
        el['c-status'].className = 'badge ' + status.cls;

        el['c-expired-note'].hidden = c.c_status !== 'EXPIRED';

        showState('view');
    }

    function openEditForm() {
        el['f-c-end'].value = currentContract.c_end ?? '';
        el['f-reason'].value = '';
        clearFieldErrors();
        showState('edit');
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
            loadHistory(currentContract.c_id);
        } catch (err) {
            el['contract-form-error'].textContent = err.message;
            el['contract-form-error'].hidden = false;
        } finally {
            saving = false;
            el['contract-btn-save'].disabled = false;
        }
    }

    el['contract-btn-retry'].addEventListener('click', loadContract);
    el['contract-btn-edit'].addEventListener('click', openEditForm);
    el['contract-btn-cancel'].addEventListener('click', () => showState('view'));
    el['contract-edit-form'].addEventListener('submit', submitEdit);

    loadContract();
})();
</script>
