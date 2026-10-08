{{-- resources/views/rentals/create.blade.php --}}
{{-- R2: บันทึกการเช่าใหม่ (Move-in + Contract ฟอร์มเดียว) — หน้าตาแบบ modal ตาม Figma แต่ยังเป็นหน้าแยก (route /rentals/create) --}}

<x-layouts::app :title="__('บันทึกการเช่าใหม่')">
@include('rentals._styles')

<div class="rt rt-page">
    <div class="rt-modal">
        <div class="rt-modal-head">
            <h1 class="rt-modal-title">บันทึกการเช่าใหม่</h1>
            <a href="{{ route('rentals.index') }}" class="rt-close" aria-label="ปิดและกลับไปรายการการเช่า">✕</a>
        </div>

        <div id="page-state-loading" class="rt-state">กำลังโหลดข้อมูลผู้เช่า/ห้อง...</div>
        <div id="page-state-error" class="rt-state rt-state--error" hidden>
            <span id="page-error-message"></span>
            <button type="button" id="btn-page-retry" class="rt-btn rt-btn--sm">ลองใหม่</button>
        </div>

        <form id="rental-create-form" hidden novalidate>
            <div class="rt-notice rt-notice--info">
                💡 เลือกผู้เช่าหรือห้อง ระบบจะ <strong>Auto Fill</strong> ข้อมูลที่เกี่ยวข้องให้อัตโนมัติ
            </div>

            <p id="meter-warning" class="rt-notice rt-notice--warn" hidden>
                ห้องนี้ยังไม่มีเลขมิเตอร์ตั้งต้น ณ วันนี้ — ระบบจะปฏิเสธการสร้างรายการนี้จนกว่าจะบันทึกมิเตอร์ก่อน
            </p>

            <div class="rt-form-field">
                <label for="f-tenant">ผู้เช่า <span class="rt-req">*</span></label>
                <select id="f-tenant" name="tenant" class="rt-input" required>
                    <option value="">-- เลือกผู้เช่า --</option>
                </select>
                <span class="rt-field-error" id="err-tenant"></span>
            </div>

            <div class="rt-form-field">
                <label for="f-room">ห้องว่าง <span class="rt-req">*</span></label>
                <select id="f-room" name="room" class="rt-input" required>
                    <option value="">-- เลือกห้องว่าง --</option>
                </select>
                <span class="rt-hint" id="room-empty-hint" hidden>ตอนนี้ไม่มีห้องว่าง</span>
                <span class="rt-field-error" id="err-room"></span>
            </div>

            <div class="rt-autofill" aria-live="polite">
                <div>
                    <div class="rt-autofill-label">✓ ผู้เช่า (Auto Fill)</div>
                    <div class="rt-autofill-value" id="af-tenant">-</div>
                    <div class="rt-autofill-sub" id="af-tenant-sub"></div>
                </div>
                <div>
                    <div class="rt-autofill-label">✓ ห้อง (Auto Fill)</div>
                    <div class="rt-autofill-value" id="af-room">-</div>
                    <div class="rt-autofill-sub" id="af-room-sub"></div>
                </div>
            </div>

            <div class="rt-form-row">
                <div class="rt-form-field">
                    <label for="f-movein">วันที่เข้า</label>
                    <input type="date" id="f-movein" class="rt-input" value="{{ now('Asia/Bangkok')->format('Y-m-d') }}" disabled>
                    <span class="rt-hint">ระบบกำหนดเป็นวันปัจจุบันเสมอ</span>
                </div>
                <div class="rt-form-field">
                    <label for="f-end">วันที่ออก (ประมาณ)</label>
                    <input type="date" id="f-end" name="c_end" class="rt-input">
                    <span class="rt-hint">เว้นว่าง = ไม่กำหนด · นับรวมวันที่เลือกเป็นวันสุดท้ายของสัญญา</span>
                    <span class="rt-field-error" id="err-end"></span>
                </div>
            </div>

            <div class="rt-form-row">
                <div class="rt-form-field">
                    <label for="f-rent">ค่าเช่า (บาท/เดือน) <span class="rt-req">*</span></label>
                    <input type="number" id="f-rent" name="c_rent" class="rt-input" step="0.01" min="0" required>
                    <span class="rt-hint" id="rent-autofill-hint" hidden>เติมอัตโนมัติจากราคาห้อง — กรุณาตรวจสอบก่อนบันทึก</span>
                    <span class="rt-field-error" id="err-rent"></span>
                </div>
                <div class="rt-form-field">
                    <label for="f-deposit">เงินประกัน (บาท)</label>
                    <input type="number" id="f-deposit" name="c_deposit" class="rt-input" step="0.01" min="0" placeholder="ไม่บังคับ">
                    <span class="rt-field-error" id="err-deposit"></span>
                </div>
            </div>

            <p id="form-error" class="rt-notice rt-notice--err" hidden></p>

            <div class="rt-form-actions">
                <a href="{{ route('rentals.index') }}" class="rt-btn">ยกเลิก</a>
                <button type="submit" id="btn-submit" class="rt-btn rt-btn--primary" disabled>✓ บันทึกการเช่า</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const API_RENTALS = '/api/v1/rentals';
    const API_TENANTS = '/api/v1/tenants?per_page=100';
    const API_ROOMS   = '/api/v1/rooms?r_status=VACANT&per_page=100';
    const RENTALS_INDEX_URL = "{{ route('rentals.index') }}";
    const TODAY_STR = "{{ now('Asia/Bangkok')->format('Y-m-d') }}"; // ใช้เทียบวันที่มิเตอร์ล่าสุดกับวันนี้
    const fetchOpts = { headers: { Accept: 'application/json' }, credentials: 'same-origin' };

    const $ = (id) => document.getElementById(id);
    const money = (n) => '฿' + Number(n).toLocaleString('th-TH', { maximumFractionDigits: 2 });

    const el = {};
    ['page-state-loading', 'page-state-error', 'page-error-message', 'btn-page-retry',
     'rental-create-form', 'f-tenant', 'f-room', 'f-rent', 'rent-autofill-hint', 'f-deposit',
     'f-end', 'err-tenant', 'err-room', 'err-rent', 'err-end', 'err-deposit', 'form-error', 'btn-submit',
     'meter-warning', 'room-empty-hint', 'af-tenant', 'af-tenant-sub', 'af-room', 'af-room-sub'
    ].forEach((id) => { el[id] = $(id); });

    let tenants = new Map(); // t_id -> tenant
    let rooms = new Map();   // r_id -> room
    let submitting = false;  // กันกดซ้ำ

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    }

    async function loadFormData() {
        el['page-state-loading'].hidden = false;
        el['page-state-error'].hidden = true;
        el['rental-create-form'].hidden = true;

        try {
            const [tenantsRes, roomsRes] = await Promise.all([
                fetch(API_TENANTS, fetchOpts),
                fetch(API_ROOMS, fetchOpts),
            ]);

            if (tenantsRes.status === 401 || roomsRes.status === 401) {
                window.location.href = '/login';
                return;
            }
            if (!tenantsRes.ok) throw new Error('โหลดรายชื่อผู้เช่าไม่สำเร็จ (' + tenantsRes.status + ')');
            if (!roomsRes.ok) throw new Error('โหลดรายชื่อห้องไม่สำเร็จ (' + roomsRes.status + ')');

            const tenantsBody = await tenantsRes.json();
            const roomsBody = await roomsRes.json();
            const busy = await loadBusyTenantIds();

            populateTenants(tenantsBody.data ?? [], busy);
            populateRooms(roomsBody.data ?? []);
            refreshAutofill();

            el['page-state-loading'].hidden = true;
            el['rental-create-form'].hidden = false;
        } catch (err) {
            el['page-error-message'].textContent = err.message || 'โหลดข้อมูลไม่สำเร็จ';
            el['page-state-loading'].hidden = true;
            el['page-state-error'].hidden = false;
        }
    }

    // id ผู้เช่าที่มีการเช่า ACTIVE อยู่แล้ว (backend ห้ามเช่าซ้อน) — โหลดไม่ได้ก็ไม่เป็นไร backend ยังตรวจอีกชั้น
    async function loadBusyTenantIds() {
        try {
            const res = await fetch('/api/v1/rentals?per_page=100', fetchOpts);
            if (!res.ok) return new Set();
            const body = await res.json();
            return new Set((body.data ?? []).filter((r) => r.rt_status === 'ACTIVE').map((r) => String(r.tenant?.t_id)));
        } catch (_) {
            return new Set();
        }
    }

    function populateTenants(list, busy = new Set()) {
        tenants = new Map(list.map((t) => [String(t.t_id), t]));
        const select = el['f-tenant'];
        select.innerHTML = '<option value="">-- เลือกผู้เช่า --</option>';

        list.forEach((t) => {
            const opt = document.createElement('option');
            opt.value = String(t.t_id);
            opt.textContent = `${t.t_Fname ?? ''} ${t.t_Lname ?? ''}`.trim();
            if (busy.has(opt.value)) {
                opt.disabled = true;
                opt.textContent += ' (มีการเช่าอยู่แล้ว)';
            }
            select.appendChild(opt);
        });
    }

    function populateRooms(list) {
        rooms = new Map(list.map((r) => [String(r.r_id), r]));
        const select = el['f-room'];
        select.innerHTML = '<option value="">-- เลือกห้องว่าง --</option>';

        list.forEach((r) => {
            const opt = document.createElement('option');
            opt.value = String(r.r_id);
            opt.textContent = `ห้อง ${r.r_name} · ${r.r_type ?? '-'} · ${money(r.r_rent)}/เดือน`;
            select.appendChild(opt);
        });

        el['room-empty-hint'].hidden = list.length > 0;
    }

    // อัปเดตกล่อง Auto Fill + ปุ่มบันทึก (กดได้เมื่อเลือกครบทั้งผู้เช่าและห้อง)
    function refreshAutofill() {
        const tenant = tenants.get(el['f-tenant'].value);
        const room = rooms.get(el['f-room'].value);

        el['af-tenant'].textContent = tenant ? `${tenant.t_Fname ?? ''} ${tenant.t_Lname ?? ''}`.trim() : '-';
        el['af-tenant-sub'].textContent = tenant ? (tenant.t_tel ?? '') : '';
        el['af-room'].textContent = room ? `ห้อง ${room.r_name}` : '-';
        el['af-room-sub'].textContent = room ? `${money(room.r_rent)}/เดือน` : '';

        el['btn-submit'].disabled = submitting || !(tenant && room);
    }

    el['f-tenant'].addEventListener('change', refreshAutofill);

    el['f-room'].addEventListener('change', () => {
        const room = rooms.get(el['f-room'].value);

        if (room) {
            el['f-rent'].value = room.r_rent;
            el['rent-autofill-hint'].hidden = false;
        } else {
            el['rent-autofill-hint'].hidden = true;
        }

        refreshAutofill();
        checkMeterBaseline(room?.r_id);
    });

    // เช็คจาก GET /rooms/{room}/meters (รายการล่าสุด) ว่ามีมิเตอร์ของวันนี้หรือยัง — เป็นแค่คำเตือนล่วงหน้า
    async function checkMeterBaseline(roomId) {
        el['meter-warning'].hidden = true;
        if (!roomId) return;

        try {
            const res = await fetch(`/api/v1/rooms/${roomId}/meters?per_page=1`, fetchOpts);
            if (!res.ok) return; // เช็คไม่ได้ก็ปล่อยผ่าน ให้ backend เป็นคนฟันธงตอน submit

            const body = await res.json();
            const hasBaseline = body.data?.[0]?.m_date === TODAY_STR;
            el['meter-warning'].hidden = hasBaseline;
        } catch (_) {
            // เงียบไว้ — ไม่ใช่ validation หลัก
        }
    }

    function clearFieldErrors() {
        ['tenant', 'room', 'rent', 'end', 'deposit'].forEach((k) => { el['err-' + k].textContent = ''; });
        el['form-error'].hidden = true;
    }

    async function submitForm(e) {
        e.preventDefault();
        if (submitting) return;

        const tenant = tenants.get(el['f-tenant'].value);
        const room = rooms.get(el['f-room'].value);

        clearFieldErrors();
        if (!tenant) { el['err-tenant'].textContent = 'กรุณาเลือกผู้เช่า'; return; }
        if (!room) { el['err-room'].textContent = 'กรุณาเลือกห้อง'; return; }
        if (el['f-rent'].value === '') { el['err-rent'].textContent = 'กรุณากรอกค่าเช่า'; return; }

        submitting = true;
        el['btn-submit'].disabled = true;

        // body แบบ flat ให้ตรงกับ CreateRental Action ของเกลือ
        const payload = {
            tenants_t_id: tenant.t_id,
            rooms_r_id: room.r_id,
            c_rent: Number(el['f-rent'].value),
            c_deposit: el['f-deposit'].value ? Number(el['f-deposit'].value) : 0,
            c_end: el['f-end'].value || null,
        };

        try {
            const res = await fetch(API_RENTALS, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });

            const body = await res.json();

            if (res.status === 422) {
                if (body.errors?.tenants_t_id) el['err-tenant'].textContent = body.errors.tenants_t_id[0];
                if (body.errors?.rooms_r_id)   el['err-room'].textContent   = body.errors.rooms_r_id[0];
                if (body.errors?.c_rent)       el['err-rent'].textContent   = body.errors.c_rent[0];
                if (body.errors?.c_deposit)    el['err-deposit'].textContent = body.errors.c_deposit[0];
                if (body.errors?.c_end)        el['err-end'].textContent    = body.errors.c_end[0];
                return;
            }

            if (res.status === 409) {
                // 409 ของจริงไม่มี message ติดมา ต้องใส่ข้อความ generic เอง
                el['form-error'].textContent = 'ห้องนี้ไม่ว่าง หรือผู้เช่า/ห้องนี้มีรายการ Active อยู่แล้ว — กำลังโหลดข้อมูลล่าสุด';
                el['form-error'].hidden = false;
                await loadFormData(); // ห้อง/ผู้เช่าอาจถูกใช้ไปแล้ว โหลดรายการใหม่
                return;
            }

            if (!res.ok) {
                throw new Error(body.message || 'สร้างการเช่าไม่สำเร็จ');
            }

            // สำเร็จ — พาไปหน้ารายละเอียด (R3)
            window.location.href = RENTALS_INDEX_URL + '/' + body.data.rt_id;
        } catch (err) {
            el['form-error'].textContent = err.message;
            el['form-error'].hidden = false;
        } finally {
            submitting = false;
            refreshAutofill();
        }
    }

    el['btn-page-retry'].addEventListener('click', loadFormData);
    el['rental-create-form'].addEventListener('submit', submitForm);

    loadFormData();
})();
</script>
</x-layouts::app>
