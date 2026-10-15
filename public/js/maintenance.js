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
        }
    }
    const selectionKey = root.dataset.maintenance === 'meters' ? `meter-selection-${$('meter-room').value}` : null;
    async function load() { if (selectionKey) sessionStorage.removeItem(selectionKey); window.location.reload(); }
    const initial = document.getElementById('meter-initial');
    const meters = initial ? JSON.parse(initial.textContent) : {};
    if (selectionKey) {
        try { const saved = JSON.parse(sessionStorage.getItem(selectionKey) || '{}'); start = saved.start ? (meters[saved.start.m_id] || saved.start) : null; end = saved.end ? (meters[saved.end.m_id] || saved.end) : null; }
        catch (_) { sessionStorage.removeItem(selectionKey); }
        selection();
    }
    root.addEventListener('click', event => {
        const meterButton = event.target.closest('[data-meter-action]');
        if (meterButton && !busy) {
            const meter = meters[meterButton.dataset.meterId];
            if (!meter) return;
            const room = $('meter-room').value;
            const action = meterButton.dataset.meterAction;
            if (action === 'start') { start = meter; selection(); }
            if (action === 'end') { end = meter; selection(); }
            if (action === 'edit') {
                editing = {meter, room}; $('meter-edit-section').hidden = false;
                $('meter-edit-summary').textContent = `รายการ #${meter.m_id} วันที่ ${meter.m_date} · น้ำ ${meter.m_water} · ไฟ ${meter.m_elec}`;
                $('meter-edit-date').value = meter.m_date; $('meter-edit-date').readOnly = !!meter.date_locked;
                $('meter-edit-water').value = meter.m_water; $('meter-edit-elec').value = meter.m_elec; $('meter-edit-reason').value = '';
                $('meter-edit-water').focus();
            }
            if (action === 'cancel') {
                const reason = window.prompt('ระบุเหตุผลที่ต้องการยกเลิกรายการมิเตอร์');
                if (reason === null) return;
                if (!reason.trim()) { $('work-error').textContent = 'กรุณาระบุเหตุผล'; return; }
                if (!window.confirm(`ยืนยันยกเลิกมิเตอร์วันที่ ${meter.m_date} · น้ำ ${meter.m_water} · ไฟ ${meter.m_elec}\nเหตุผล: ${reason.trim()}`)) return;
                run(async () => { await api(`/rooms/${room}/meters/${meter.m_id}`, 'DELETE', {reason: reason.trim(), confirmed: true, expected_event_id: meter.latest_event_id}); await load(); });
            }
        }
        const detailButton = event.target.closest('[data-repair-detail]');
        if (detailButton) run(() => detail(Number(detailButton.dataset.repairDetail)));
        const nextButton = event.target.closest('[data-repair-next]');
        if (nextButton && !busy) {
            const next = {REPORTED:'IN_PROGRESS', IN_PROGRESS:'COMPLETED'}[nextButton.dataset.status];
            if (!next || !window.confirm(`ยืนยันเปลี่ยนสถานะงานซ่อม #${nextButton.dataset.repairNext} จาก ${labels[nextButton.dataset.status]} เป็น ${labels[next]} หรือไม่?`)) return;
            run(async () => {
                await api(`/repairs/${nextButton.dataset.repairNext}/status`, 'PATCH', {expected_status:nextButton.dataset.status, rp_status:next});
                await load();
            });
        }
    });
    function selection() {
        $('usage-selection').textContent = `ต้นช่วง: ${start?.m_date || 'ยังไม่เลือก'} → ปลายช่วง: ${end?.m_date || 'ยังไม่เลือก'}`;
        $('usage-result').textContent = '';
        if (selectionKey) sessionStorage.setItem(selectionKey, JSON.stringify({start, end}));
    }
    async function detail(id) {
        const { data } = await api(`/repairs/${id}`);
        $('repair-detail').hidden = false;
        $('repair-description').replaceChildren();
        [data.rp_name, ...(data.rp_description || '').split('\n')].forEach(line => { const paragraph = document.createElement('p'); paragraph.className = 'mb-2'; paragraph.textContent = line; $('repair-description').append(paragraph); });
        $('repair-history').replaceChildren();
        data.histories.forEach(history => { const el = document.createElement('li'); el.textContent = `${labels[history.rph_status]} · ${new Date(history.created_at).toLocaleString('th-TH', {timeZone: 'Asia/Bangkok'})} · ผู้ดำเนินการ #${history.changed_by_user_id}`; $('repair-history').append(el); });
    }
    $('reload').onclick = () => run(async () => {
        if (root.dataset.maintenance === 'meters') { editing = start = end = null; $('meter-edit-section').hidden = true; selection(); }
        await load();
    });
    if (root.dataset.maintenance === 'meters') {
        $('meter-room').onchange = () => { const url = new URL(window.location.href); url.searchParams.delete('page'); if ($('meter-room').value) url.searchParams.set('room', $('meter-room').value); else url.searchParams.delete('room'); window.location.assign(url); };
        $('meter-form').elements.m_date.onchange = event => {
            if (!$('meter-room').value) return;
            const url = new URL(window.location.href); url.searchParams.delete('page'); url.searchParams.set('m_date', event.target.value); window.location.assign(url);
        };
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
        $('repair-filter').onchange = () => { const url = new URL(window.location.href); url.searchParams.delete('page'); if ($('repair-filter').value) url.searchParams.set('rp_status', $('repair-filter').value); else url.searchParams.delete('rp_status'); window.location.assign(url); };
        if ($('repair-form')) {
            $('repair-type').onchange = () => {
                const common = $('repair-type').value === 'COMMON';
                $('repair-room-label').hidden = common; $('repair-room').disabled = common; $('repair-room').required = !common;
            };
            $('repair-form').onsubmit = event => {
                event.preventDefault();
                const body = Object.fromEntries(new FormData(event.target));
                if (busy || !window.confirm('ยืนยันส่งรายการแจ้งซ่อมนี้หรือไม่?')) return;
                run(async () => { await api('/repairs', 'POST', body); event.target.elements.rp_name.value = ''; event.target.elements.rp_description.value = ''; page = 1; $('repair-filter').value = ''; await load(); });
            };
        }
    }

})();
