{{-- resources/views/rentals/create.blade.php --}}
{{-- R2: สร้างการเช่า (Move-in + Contract ฟอร์มเดียว) --}}

<x-layouts::app :title="__('สร้างการเช่า')">
<div class="rental-form-page">
    <div class="rental-form-page__header">
        <h1>สร้างการเช่า</h1>
        <a href="{{ route('rentals.index') }}" class="btn">← กลับไปรายการการเช่า</a>
    </div>

    <div id="page-state-loading" class="state-box">กำลังโหลดข้อมูลผู้เช่า/ห้อง...</div>
    <div id="page-state-error" class="state-box state-box--error" hidden>
        <span id="page-error-message"></span>
        <button type="button" id="btn-page-retry">ลองใหม่</button>
    </div>

    <form id="rental-create-form" hidden>
        <p id="meter-warning" class="notice notice--warning" hidden>
            ห้องนี้ยังไม่มีเลขมิเตอร์ตั้งต้น ณ วันนี้ — ระบบจะปฏิเสธการสร้างรายการนี้จนกว่าจะบันทึกมิเตอร์ก่อน
        </p>

        <div class="form-field">
            <label for="f-tenant">ผู้เช่า</label>
            <input type="text" id="f-tenant" name="tenant_display" list="tenant-options" placeholder="พิมพ์ชื่อผู้เช่า..." autocomplete="off" required>
            <datalist id="tenant-options"></datalist>
            <span class="field-error" id="err-tenant"></span>
        </div>

        <div class="form-field">
            <label for="f-room">ห้อง</label>
            <input type="text" id="f-room" name="room_display" list="room-options" placeholder="พิมพ์ชื่อห้อง..." autocomplete="off" required>
            <datalist id="room-options"></datalist>
            <span class="field-error" id="err-room"></span>
        </div>

        <div class="form-field">
            <label>วันเข้า</label>
            <input type="text" value="{{ now('Asia/Bangkok')->format('Y-m-d') }}" disabled>
            <span class="field-hint">วันเข้าเป็นวันปัจจุบันเสมอ ระบบกำหนดให้อัตโนมัติ</span>
        </div>

        <div class="form-field">
            <label for="f-rent">ค่าเช่า (บาท/เดือน)</label>
            <input type="number" id="f-rent" name="c_rent" step="0.01" min="0" required>
            <span class="field-hint" id="rent-autofill-hint" hidden>
                เติมอัตโนมัติจากราคาห้อง — เป็นค่าอ้างอิง กรุณาตรวจสอบก่อนบันทึก
            </span>
            <span class="field-error" id="err-rent"></span>
        </div>

        <div class="form-field">
            <label for="f-deposit">เงินประกัน (บาท) — ไม่บังคับ</label>
            <input type="number" id="f-deposit" name="c_deposit" step="0.01" min="0">
        </div>

        <div class="form-field">
            <label for="f-end">วันสิ้นสุดสัญญา — เว้นว่าง = ไม่กำหนด</label>
            <input type="date" id="f-end" name="c_end">
            <span class="field-hint">นับรวมวันที่เลือกเป็นวันสุดท้ายของสัญญา</span>
            <span class="field-error" id="err-end"></span>
        </div>

        <p id="form-error" class="notice notice--error" hidden></p>

        <div class="form-actions">
            <button type="submit" id="btn-submit" class="btn btn--primary">สร้างการเช่า</button>
        </div>
    </form>
</div>

<style>
    .rental-form-page { max-width: 560px; margin: 0 auto; padding: 24px 16px; }
    .rental-form-page__header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
    .state-box { padding: 24px; text-align: center; color: #999; border: 1px dashed #444; border-radius: 6px; }
    .state-box--error { color: #f87171; border-color: #7f1d1d; background: rgba(127,29,29,0.15); }
    .btn { display: inline-block; padding: 8px 16px; border-radius: 4px; text-decoration: none; border: 1px solid #444; color: inherit; background: transparent; cursor: pointer; }
    .btn--primary { background: #2563eb; color: #fff; border-color: #2563eb; }
    .notice { padding: 10px 14px; border-radius: 4px; margin-bottom: 16px; font-size: 0.9em; }
    .notice--warning { background: rgba(250,204,21,0.12); color: #facc15; border: 1px solid rgba(250,204,21,0.3); }
    .notice--error { background: rgba(248,113,113,0.12); color: #f87171; border: 1px solid rgba(248,113,113,0.3); }
    .form-field { margin-bottom: 16px; }
    .form-field label { display: block; margin-bottom: 4px; font-size: 0.9em; }
    .form-field input, .form-field select { width: 100%; padding: 8px 12px; border: 1px solid #444; border-radius: 4px; background: transparent; color: inherit; }
    .form-field input:disabled { opacity: 0.6; }
    .field-hint { display: block; font-size: 0.8em; color: #888; margin-top: 4px; }
    .field-error { display: block; font-size: 0.85em; color: #f87171; margin-top: 4px; }
</style>

<script>
(function () {
    const API_RENTALS = '/api/v1/rentals';
    const API_TENANTS = '/api/v1/tenants';
    const API_ROOMS   = '/api/v1/rooms?r_status=VACANT&per_page=100'; // แก้ param ให้ตรงกับ RoomController จริงของเกลือ (r_status ไม่ใช่ status)
    const RENTALS_INDEX_URL = "{{ route('rentals.index') }}";
    const TODAY_STR = "{{ now('Asia/Bangkok')->format('Y-m-d') }}"; // ใช้เทียบวันที่มิเตอร์ล่าสุดกับวันนี้

    const el = {};
    ['page-state-loading', 'page-state-error', 'page-error-message', 'btn-page-retry',
     'rental-create-form', 'f-tenant', 'f-room', 'f-rent', 'rent-autofill-hint', 'f-deposit',
     'f-end', 'err-tenant', 'err-room', 'err-rent', 'err-end', 'form-error', 'btn-submit',
     'meter-warning'
    ].forEach((id) => { el[id] = document.getElementById(id); });

    let rooms = [];
    let tenantMap = new Map(); // label -> t_id
    let roomMap = new Map();   // label -> { r_id, r_rent }
    let submitting = false; // กันกดซ้ำ

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    }

    async function loadFormData() {
        el['page-state-loading'].hidden = false;
        el['page-state-error'].hidden = true;
        el['rental-create-form'].hidden = true;

        try {
            const [tenantsRes, roomsRes] = await Promise.all([
                fetch(API_TENANTS, { headers: { Accept: 'application/json' }, credentials: 'same-origin' }),
                fetch(API_ROOMS, { headers: { Accept: 'application/json' }, credentials: 'same-origin' }),
            ]);

            if (tenantsRes.status === 401 || roomsRes.status === 401) {
                window.location.href = '/login';
                return;
            }
            if (!tenantsRes.ok) throw new Error('โหลดรายชื่อผู้เช่าไม่สำเร็จ (' + tenantsRes.status + ')');
            if (!roomsRes.ok) throw new Error('โหลดรายชื่อห้องไม่สำเร็จ (' + roomsRes.status + ')');

            const tenantsBody = await tenantsRes.json();
            const roomsBody = await roomsRes.json();

            populateTenants(tenantsBody.data ?? []);
            populateRooms(roomsBody.data ?? []);

            el['page-state-loading'].hidden = true;
            el['rental-create-form'].hidden = false;
        } catch (err) {
            el['page-error-message'].textContent = err.message || 'โหลดข้อมูลไม่สำเร็จ';
            el['page-state-loading'].hidden = true;
            el['page-state-error'].hidden = false;
        }
    }

    function populateTenants(list) {
        tenantMap.clear();
        const datalist = document.getElementById('tenant-options');
        datalist.innerHTML = '';

        list.forEach((t) => {
            const label = `${t.t_Fname} ${t.t_Lname}`;
            tenantMap.set(label, t.t_id);

            const opt = document.createElement('option');
            opt.value = label;
            datalist.appendChild(opt);
        });
    }

    function populateRooms(list) {
        rooms = list;
        roomMap.clear();
        const datalist = document.getElementById('room-options');
        datalist.innerHTML = '';

        list.forEach((r) => {
            const label = `${r.r_name} (ชั้น ${r.r_floor})`;
            roomMap.set(label, { r_id: r.r_id, r_rent: r.r_rent });

            const opt = document.createElement('option');
            opt.value = label;
            datalist.appendChild(opt);
        });
    }

    el['f-room'].addEventListener('input', () => {
        const match = roomMap.get(el['f-room'].value);

        if (match) {
            el['f-rent'].value = match.r_rent;
            el['rent-autofill-hint'].hidden = false;
        } else {
            el['rent-autofill-hint'].hidden = true;
        }

        checkMeterBaseline(match?.r_id);
    });

    // เปลี่ยนมาใช้ GET /rooms/{room}/meters ของเกลือแทน (ไม่มี endpoint /meter-status แยกต่างหากจริง)
    // เช็คจากรายการล่าสุด (per_page=1, เรียง m_date desc) ว่าเป็นของวันนี้หรือยัง
    async function checkMeterBaseline(roomId) {
        el['meter-warning'].hidden = true;
        if (!roomId) return;

        try {
            const res = await fetch(`/api/v1/rooms/${roomId}/meters?per_page=1`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!res.ok) return; // เช็คไม่ได้ก็ปล่อยผ่าน ไม่บล็อก UX ให้ backend เป็นคนฟันธงตอน submit

            const body = await res.json();
            const hasBaseline = body.data?.[0]?.m_date === TODAY_STR;
            el['meter-warning'].hidden = hasBaseline;
        } catch (_) {
            // เงียบไว้ — ไม่ใช่ validation หลัก แค่ช่วยเตือนล่วงหน้า
        }
    }

    function clearFieldErrors() {
        ['tenant', 'room', 'rent', 'end'].forEach((k) => { el['err-' + k].textContent = ''; });
        el['form-error'].hidden = true;
    }

    async function submitForm(e) {
        e.preventDefault();
        if (submitting) return;
        submitting = true;
        el['btn-submit'].disabled = true;
        clearFieldErrors();

        const tenantId = tenantMap.get(el['f-tenant'].value);
        const roomMatch = roomMap.get(el['f-room'].value);

        if (!tenantId) {
            el['err-tenant'].textContent = 'กรุณาเลือกผู้เช่าจากรายการที่ค้นหาเจอ';
            submitting = false;
            el['btn-submit'].disabled = false;
            return;
        }
        if (!roomMatch) {
            el['err-room'].textContent = 'กรุณาเลือกห้องจากรายการที่ค้นหาเจอ';
            submitting = false;
            el['btn-submit'].disabled = false;
            return;
        }

        // body แบบ flat ให้ตรงกับ CreateRental Action ของเกลือ (ไม่ nest เป็น rental/contract อีกแล้ว)
        const payload = {
            tenants_t_id: tenantId,
            rooms_r_id: roomMatch.r_id,
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
                // key ของ errors เปลี่ยนเป็น field ตรง ๆ (ไม่มี prefix rental./contract. แล้ว)
                if (body.errors?.tenants_t_id) el['err-tenant'].textContent = body.errors.tenants_t_id[0];
                if (body.errors?.rooms_r_id)   el['err-room'].textContent   = body.errors.rooms_r_id[0];
                if (body.errors?.c_rent)       el['err-rent'].textContent   = body.errors.c_rent[0];
                if (body.errors?.c_end)        el['err-end'].textContent    = body.errors.c_end[0];
                return;
            }

            if (res.status === 409) {
                // 409 ของจริงไม่มี message ติดมา (abort_unless เปล่า ๆ) ต้องใส่ข้อความ generic เอง
                el['form-error'].textContent = 'ห้องนี้ไม่ว่าง หรือผู้เช่า/ห้องนี้มีรายการ Active อยู่แล้ว — กำลังโหลดข้อมูลล่าสุด';
                el['form-error'].hidden = false;
                await loadFormData(); // ห้อง/ผู้เช่าอาจถูกใช้ไปแล้ว โหลด dropdown ใหม่
                return;
            }

            if (!res.ok) {
                throw new Error(body.message || 'สร้างการเช่าไม่สำเร็จ');
            }

            // สำเร็จ — พาไปหน้ารายละเอียด (R3) ตามที่แผนงานตั้งใจไว้
            window.location.href = RENTALS_INDEX_URL + '/' + body.data.rt_id;
        } catch (err) {
            el['form-error'].textContent = err.message;
            el['form-error'].hidden = false;
        } finally {
            submitting = false;
            el['btn-submit'].disabled = false;
        }
    }

    el['btn-page-retry'].addEventListener('click', loadFormData);
    el['rental-create-form'].addEventListener('submit', submitForm);

    loadFormData();
})();
</script>
</x-layouts::app>