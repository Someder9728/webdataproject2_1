(() => {
    const root = document.querySelector('[data-maintenance]');
    if (!root || root.dataset.initialized) return;
    root.dataset.initialized = '1';
    const $ = id => root.querySelector(`#${id}`);
    const labels = { REPORTED: 'แจ้งแล้ว', IN_PROGRESS: 'กำลังซ่อม', COMPLETED: 'เสร็จสิ้น', ROOM: 'ห้องพัก', COMMON: 'ส่วนกลาง' };
    let page = 1, lastPage = 1, busy = false, start = null, end = null;
    async function api(url, method = 'GET', body) {
        const response = await fetch(`/api/v1${url}`, {
            method, credentials: 'same-origin',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            ...(body ? { body: JSON.stringify(body) } : {}),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const messages = { 401: 'กรุณาเข้าสู่ระบบใหม่', 403: 'บัญชีไม่มีสิทธิ์ทำรายการนี้ หรือจำเป็นต้องเปลี่ยนรหัสผ่าน', 409: 'ข้อมูลซ้ำหรือสถานะเปลี่ยนแล้ว กรุณาโหลดรายการใหม่ก่อนทำต่อ', 419: 'เซสชันหมดอายุ กรุณาโหลดหน้าใหม่' };
            throw new Error(Object.values(data.errors || {}).flat().join('\n') || messages[response.status] || data.message || 'โหลดข้อมูลไม่สำเร็จ กรุณาลองใหม่');
        }
        return data;
    }
    async function run(action) {
        if (busy) return;
        busy = true;
        $('work-error').textContent = '';
        $('work-status').textContent = 'กำลังดำเนินการ…';
        const controls = [...root.querySelectorAll('button,input,select,textarea')].map(el => [el, el.disabled]);
        controls.forEach(([el]) => el.disabled = true);
        try { await action(); $('work-status').textContent = 'อัปเดตข้อมูลแล้ว'; }
        catch (error) { $('work-error').textContent = error.message; $('work-status').textContent = ''; }
        finally {
            controls.forEach(([el, disabled]) => el.disabled = disabled);
            busy = false;
            $('previous-page').disabled = page <= 1;
            $('next-page').disabled = page >= lastPage;
        }
    }
    function cell(row, text) { const el = document.createElement('td'); el.textContent = text ?? '-'; row.append(el); return el; }
    function button(parent, text, action) { const el = document.createElement('button'); el.type = 'button'; el.textContent = text; el.addEventListener('click', action); parent.append(el); }
    function pagination(meta) { page = meta.current_page; lastPage = meta.last_page; $('page-label').textContent = `${page} / ${lastPage} (${meta.total} รายการ)`; }
    function empty(tbody, count) { if (!tbody.children.length) { const row = tbody.insertRow(); const el = cell(row, 'ยังไม่มีรายการ'); el.colSpan = count; } }
    async function load() {
        if (root.dataset.maintenance === 'meters') {
            const room = $('meter-room').value;
            if (!room) { $('meter-rows').replaceChildren(); empty($('meter-rows'), 4); return; }
            const result = await api(`/rooms/${room}/meters?page=${page}`);
            const tbody = $('meter-rows'); tbody.replaceChildren();
            result.data.forEach(meter => {
                const row = tbody.insertRow(); cell(row, meter.m_date); cell(row, meter.m_water); cell(row, meter.m_elec);
                const actions = cell(row, '');
                button(actions, 'ต้นช่วง', () => { if (!busy) { start = meter; selection(); } });
                button(actions, 'ปลายช่วง', () => { if (!busy) { end = meter; selection(); } });
            });
            empty(tbody, 4); pagination(result.meta);
        } else {
            const status = $('repair-filter').value;
            const result = await api(`/repairs?page=${page}${status ? `&rp_status=${status}` : ''}`);
            const tbody = $('repair-rows'); tbody.replaceChildren();
            result.data.forEach(repair => {
                const row = tbody.insertRow(); cell(row, repair.rp_name); cell(row, labels[repair.rp_type]); cell(row, labels[repair.rp_status]);
                const actions = cell(row, '');
                button(actions, 'ประวัติ', () => run(() => detail(repair.rp_id)));
                const next = { REPORTED: 'IN_PROGRESS', IN_PROGRESS: 'COMPLETED' }[repair.rp_status];
                if (root.dataset.admin === '1' && next) button(actions, labels[next], () => run(async () => {
                    await api(`/repairs/${repair.rp_id}/status`, 'PATCH', { expected_status: repair.rp_status, rp_status: next });
                    await load(); await detail(repair.rp_id);
                }));
            });
            empty(tbody, 4); pagination(result.meta);
        }
    }
    function selection() {
        $('usage-selection').textContent = `ต้นช่วง: ${start?.m_date || 'ยังไม่เลือก'} → ปลายช่วง: ${end?.m_date || 'ยังไม่เลือก'}`;
        $('usage-result').textContent = '';
    }
    async function detail(id) {
        const { data } = await api(`/repairs/${id}`);
        $('repair-detail').hidden = false;
        $('repair-description').textContent = `${data.rp_name}\n${data.rp_description || ''}`;
        $('repair-history').replaceChildren();
        data.histories.forEach(history => { const el = document.createElement('li'); el.textContent = `${labels[history.rph_status]} · ${new Date(history.created_at).toLocaleString('th-TH')} · ผู้ดำเนินการ #${history.changed_by_user_id}`; $('repair-history').append(el); });
    }
    $('previous-page').onclick = () => run(async () => { page = Math.max(1, page - 1); await load(); });
    $('next-page').onclick = () => run(async () => { page = Math.min(lastPage, page + 1); await load(); });
    $('reload').onclick = () => run(load);
    if (root.dataset.maintenance === 'meters') {
        $('meter-room').onchange = () => run(async () => { page = 1; lastPage = 1; start = end = null; selection(); await load(); });
        $('meter-form').onsubmit = event => {
            event.preventDefault();
            const body = Object.fromEntries(new FormData(event.target));
            run(async () => {
                const room = $('meter-room').value; if (!room) throw new Error('กรุณาเลือกห้อง');
                await api(`/rooms/${room}/meters`, 'POST', body); page = 1; await load();
            });
        };
        $('calculate-usage').onclick = () => run(async () => {
            if (!start || !end) throw new Error('กรุณาเลือกมิเตอร์ต้นช่วงและปลายช่วง');
            const { data } = await api(`/rooms/${$('meter-room').value}/meter-usage?start_meter_id=${start.m_id}&end_meter_id=${end.m_id}`);
            $('usage-result').textContent = `น้ำ ${data.water_usage} หน่วย · ไฟ ${data.elec_usage} หน่วย`;
        });
    } else {
        $('repair-filter').onchange = () => run(async () => { page = 1; await load(); });
        if ($('repair-form')) {
            $('repair-type').onchange = () => {
                const common = $('repair-type').value === 'COMMON';
                $('repair-room-label').hidden = common; $('repair-room').disabled = common; $('repair-room').required = !common;
            };
            $('repair-form').onsubmit = event => {
                event.preventDefault();
                const body = Object.fromEntries(new FormData(event.target));
                run(async () => { await api('/repairs', 'POST', body); event.target.elements.rp_name.value = ''; event.target.elements.rp_description.value = ''; page = 1; $('repair-filter').value = ''; await load(); });
            };
        }
    }
    run(load);
})();
