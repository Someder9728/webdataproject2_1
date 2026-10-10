(() => {
    const root = document.querySelector('[data-maintenance]');
    if (!root || root.dataset.initialized) return;
    root.dataset.initialized = '1';
    const $ = id => root.querySelector(`#${id}`);
    const labels = { REPORTED: 'แจ้งแล้ว', IN_PROGRESS: 'กำลังซ่อม', COMPLETED: 'เสร็จสิ้น', ROOM: 'ห้องพัก', COMMON: 'ส่วนกลาง' };
    let page = 1, lastPage = 1, busy = false, start = null, end = null, editing = null;
    async function api(url, method = 'GET', body) {
        const response = await fetch(`/api/v1${url}`, {
            method, credentials: 'same-origin',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            ...(body ? { body: JSON.stringify(body) } : {}),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const messages = { 401: 'กรุณาเข้าสู่ระบบใหม่', 403: 'บัญชีไม่มีสิทธิ์ทำรายการนี้ หรือจำเป็นต้องเปลี่ยนรหัสผ่าน', 409: 'ข้อมูลซ้ำหรือสถานะเปลี่ยนแล้ว กรุณาโหลดรายการใหม่ก่อนทำต่อ', 419: 'เซสชันหมดอายุ กรุณาโหลดหน้าใหม่' };
            const error = new Error(Object.values(data.errors || {}).flat().join('\n') || messages[response.status] || data.message || 'โหลดข้อมูลไม่สำเร็จ กรุณาลองใหม่');
            error.status = response.status;
            throw error;
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
        catch (error) {
            if (error.status === 409 && root.dataset.maintenance === 'meters') {
                editing = start = end = null; $('meter-edit-section').hidden = true; selection();
                try { await load(); } catch (_) { /* Keep the original mutation error. */ }
            }
            $('work-error').textContent = error.message; $('work-status').textContent = '';
        }
        finally {
            controls.forEach(([el, disabled]) => el.disabled = disabled);
            busy = false;
            $('previous-page').disabled = page <= 1;
            $('next-page').disabled = page >= lastPage;
        }
    }
    function cell(row, text) { const el = document.createElement('td'); el.textContent = text ?? '-'; row.append(el); return el; }
    function button(parent, text, action) { const el = document.createElement('button'); el.type = 'button'; el.className = `btn btn-sm me-2 my-1 ${text === 'ยกเลิกรายการ' ? 'tenant-delete-btn' : 'tenant-edit-btn'}`; el.textContent = text; el.addEventListener('click', action); parent.append(el); }
    function pagination(meta) { page = meta.current_page; lastPage = meta.last_page; $('page-label').textContent = `${page} / ${lastPage} (${meta.total} รายการ)`; }
    function empty(tbody, count) { if (!tbody.children.length) { const row = tbody.insertRow(); const el = cell(row, 'ยังไม่มีรายการ'); el.className = 'tenant-state text-center'; el.colSpan = count; } }
    async function load() {
        if (root.dataset.maintenance === 'meters') {
            const room = $('meter-room').value;
            if (!room) { $('meter-rows').replaceChildren(); empty($('meter-rows'), 4); return; }
            const result = await api(`/rooms/${room}/meters?include_cancelled=1&page=${page}`);
            const tbody = $('meter-rows'); tbody.replaceChildren();
            result.data.forEach(meter => {
                const row = tbody.insertRow(); cell(row, `${meter.m_date}${meter.status === 'CANCELLED' ? ' · ยกเลิก' : ''}`); if (meter.status === 'CANCELLED') row.className = 'text-secondary'; cell(row, meter.m_water); cell(row, meter.m_elec);
                const actions = cell(row, '');
                if (meter.status !== 'CANCELLED') {
                button(actions, 'ต้นช่วง', () => { if (!busy) { start = meter; selection(); } });
                button(actions, 'ปลายช่วง', () => { if (!busy) { end = meter; selection(); } });
                }
                if (meter.can_edit) button(actions, 'แก้ไข', () => {
                    if (busy) return;
                    editing = { meter, room };
                    $('meter-edit-section').hidden = false;
                    $('meter-edit-summary').textContent = `รายการ #${meter.m_id} วันที่ ${meter.m_date} · น้ำ ${meter.m_water} · ไฟ ${meter.m_elec}`;
                    $('meter-edit-date').value = meter.m_date;
                    $('meter-edit-date').readOnly = !!meter.date_locked;
                    $('meter-edit-water').value = meter.m_water;
                    $('meter-edit-elec').value = meter.m_elec;
                    $('meter-edit-reason').value = '';
                    $('meter-edit-water').focus();
                });
                if (meter.can_delete) button(actions, 'ยกเลิกรายการ', () => {
                    if (busy) return;
                    const reason = window.prompt('ระบุเหตุผลที่ต้องการยกเลิกรายการมิเตอร์');
                    if (reason === null) return;
                    if (!reason.trim()) { $('work-error').textContent = 'กรุณาระบุเหตุผล'; return; }
                    if (!window.confirm(`ยืนยันยกเลิกรายการมิเตอร์ห้อง ${$('meter-room').selectedOptions[0].textContent}\nวันที่ ${meter.m_date} · น้ำ ${meter.m_water} · ไฟ ${meter.m_elec}\nเหตุผล: ${reason.trim()}\nรายการยังอยู่ในประวัติ แต่ใช้คำนวณไม่ได้ สามารถบันทึกค่าทดแทนวันเดิมได้`)) return;
                    run(async () => {
                        await api(`/rooms/${room}/meters/${meter.m_id}`, 'DELETE', { reason: reason.trim(), confirmed: true, expected_event_id: meter.latest_event_id ?? null });
                        start = end = editing = null; $('meter-edit-section').hidden = true; selection(); page = 1; await load();
                    });
                });
                if (!meter.can_edit) { const note = document.createElement('span'); note.className = 'd-block small text-secondary mt-1'; note.textContent = ' ล็อก: ใช้ในบิลแล้วหรือรายการยกเลิก'; actions.append(note); }
            });
            empty(tbody, 4); pagination(result.meta);
        } else {
            const status = $('repair-filter').value;
            const result = await api(`/repairs?page=${page}${status ? `&rp_status=${status}` : ''}`);
            const tbody = $('repair-rows'); tbody.replaceChildren();
            result.data.forEach(repair => {
                const row = tbody.insertRow();
                    cell(row, repair.rp_name);
                    cell(row, labels[repair.rp_type]);
                    cell(
                        row,
                        repair.rp_type === 'ROOM'
                            ? (repair.room?.r_name ?? 'ไม่พบข้อมูลห้อง')
                            : 'พื้นที่ส่วนกลาง'
                    );
                const statusCell = cell(row, ''); const badge = document.createElement('span'); badge.className = `badge ${ { REPORTED: 'text-bg-warning', IN_PROGRESS: 'text-bg-primary', COMPLETED: 'text-bg-success' }[repair.rp_status] || 'text-bg-secondary' }`; badge.textContent = labels[repair.rp_status] || repair.rp_status; statusCell.append(badge);
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
        $('repair-description').replaceChildren();
        [data.rp_name, ...(data.rp_description || '').split('\n')].forEach(line => { const paragraph = document.createElement('p'); paragraph.className = 'mb-2'; paragraph.textContent = line; $('repair-description').append(paragraph); });
        $('repair-history').replaceChildren();
        data.histories.forEach(history => { const el = document.createElement('li'); el.textContent = `${labels[history.rph_status]} · ${new Date(history.created_at).toLocaleString('th-TH')} · ผู้ดำเนินการ #${history.changed_by_user_id}`; $('repair-history').append(el); });
    }
    $('previous-page').onclick = () => run(async () => { page = Math.max(1, page - 1); await load(); });
    $('next-page').onclick = () => run(async () => { page = Math.min(lastPage, page + 1); await load(); });
    $('reload').onclick = () => run(async () => {
        if (root.dataset.maintenance === 'meters') { editing = start = end = null; $('meter-edit-section').hidden = true; selection(); }
        await load();
    });
    if (root.dataset.maintenance === 'meters') {
        $('meter-room').onchange = () => run(async () => { page = 1; lastPage = 1; start = end = editing = null; $('meter-edit-section').hidden = true; selection(); await load(); });
        $('meter-edit-cancel').onclick = () => { if (!busy) { editing = null; $('meter-edit-section').hidden = true; } };
        $('meter-edit-form').onsubmit = event => {
            event.preventDefault();
            if (busy || !editing) return;
            const current = editing;
            const body = { m_date: $('meter-edit-date').value, m_water: $('meter-edit-water').value, m_elec: $('meter-edit-elec').value, reason: $('meter-edit-reason').value.trim(), confirmed: true, expected_event_id: current.meter.latest_event_id ?? null };
            if (!body.reason) { $('work-error').textContent = 'กรุณาระบุเหตุผล'; return; }
            if (!window.confirm(`ยืนยันแก้ไขมิเตอร์ #${current.meter.m_id}\nวันที่ ${current.meter.m_date} → ${body.m_date}\nน้ำ ${current.meter.m_water} → ${body.m_water}\nไฟ ${current.meter.m_elec} → ${body.m_elec}\nเหตุผล: ${body.reason}`)) return;
            run(async () => {
                await api(`/rooms/${current.room}/meters/${current.meter.m_id}`, 'PATCH', body);
                editing = start = end = null; $('meter-edit-section').hidden = true; selection(); await load();
            });
        };
        $('meter-form').onsubmit = event => {
            event.preventDefault();
            const body = Object.fromEntries(new FormData(event.target));
            if (busy) return;
            if (!$('meter-room').value) { $('work-error').textContent = 'กรุณาเลือกห้อง'; return; }
            if (!window.confirm(`ยืนยันบันทึกมิเตอร์ห้อง ${$('meter-room').selectedOptions[0].textContent}\nวันที่ ${body.m_date} · น้ำ ${body.m_water} · ไฟ ${body.m_elec}`)) return;
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
