<x-layouts::app.sidebar :title="'Dashboard ของฉัน'">
    <style>
        .tenant-dashboard { background:#f6f8fb; min-height:calc(100vh - 70px); padding:24px; }
        .tenant-kicker { color:#64748b; font-size:13px; margin-bottom:6px; }
        .tenant-heading { color:#172b4d; font-size:24px; font-weight:700; margin:0 0 4px; }
        .tenant-subtitle { color:#8292aa; font-size:14px; margin:0; }
        .tenant-summary-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); grid-auto-rows:minmax(130px,auto); gap:16px; }
        .tenant-summary { background:#fff; border:1px solid #dfe7f1; border-radius:14px; box-shadow:0 2px 5px #0f172a0a; height:100%; min-height:130px; }
        .tenant-summary-room { grid-row:span 2; }
        .tenant-summary-icon { align-items:center; background:#eaf2fb; border-radius:12px; color:#1d3b64; display:flex; flex:none; height:52px; justify-content:center; width:52px; }
        .tenant-label { color:#8292aa; font-size:12px; margin-bottom:5px; }
        .tenant-value { color:#172b4d; font-size:22px; font-weight:700; line-height:1.25; }
        .tenant-value.money { color:#e87500; font-size:25px; }
        .tenant-meta { color:#8292aa; font-size:12px; margin-top:5px; }
        .tenant-panel { background:#fff; border:1px solid #dfe7f1; border-radius:14px; box-shadow:0 2px 5px #0f172a0a; height:100%; overflow:hidden; }
        .tenant-panel-header { border-bottom:1px solid #edf1f6; padding:16px 20px 13px; }
        .tenant-panel-title { color:#172b4d; font-size:16px; font-weight:700; margin:0 0 3px; }
        .tenant-panel-subtitle { color:#8292aa; font-size:12px; margin:0; }
        .tenant-table { margin:0; }
        .tenant-table th { background:#fbfcfe; color:#718096; font-size:11px; font-weight:600; padding:11px 14px; white-space:nowrap; }
        .tenant-table td { color:#334155; font-size:12px; padding:12px 14px; white-space:nowrap; }
        .tenant-state { border-radius:999px; display:inline-block; font-size:11px; padding:4px 9px; }
        .tenant-state.active { background:#eff6ff; color:#2563eb; }
        .tenant-state.paid { background:#ecfdf5; color:#059669; }
        .tenant-state.pending { background:#fffbeb; color:#b45309; }
        .tenant-error { color:#dc3545; font-size:13px; text-align:center; white-space:normal; }
        @media (max-width:991.98px) { .tenant-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .tenant-summary-room { grid-row:span 2; } }
        @media (max-width:767.98px) { .tenant-dashboard { padding:16px; } .tenant-heading { font-size:21px; } .tenant-summary-grid { grid-template-columns:1fr; } .tenant-summary-room { grid-row:auto; } }
    </style>

    <main class="tenant-dashboard">
        <header class="mb-4">
            <div class="tenant-kicker">Dashboard</div>
            <h1 class="tenant-heading">สวัสดี, {{ auth()->user()->u_username }} <span aria-hidden="true">👋</span></h1>
            <p class="tenant-subtitle">ข้อมูลสรุปของห้องพักคุณ</p>
        </header>

        <section class="tenant-summary-grid mb-4" aria-label="ข้อมูลสรุป">
                <article class="tenant-summary tenant-summary-room p-4">
                    <div class="tenant-label">ห้องของฉัน</div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="tenant-summary-icon" style="background:#1d3b64;color:#fff;width:64px;height:64px"><i class="bi bi-door-open fs-3"></i></div>
                        <div><div class="tenant-value" id="user-room">กำลังโหลด...</div><div class="tenant-meta" id="user-room-detail">ข้อมูลห้องพัก</div></div>
                    </div>
                    <div class="border-top mt-3 pt-3 d-flex justify-content-between gap-3">
                        <div><div class="tenant-label">ค่าเช่า</div><div class="fw-semibold text-primary" id="user-rent">—</div></div>
                        <div><div class="tenant-label">เข้าพักวันที่</div><div class="small" id="user-move-in">—</div></div>
                    </div>
                </article>
                <article class="tenant-summary p-4">
                    <div class="tenant-label">ยอดค้างชำระ</div>
                    <div class="tenant-value money" id="user-outstanding">฿0</div>
                    <div class="tenant-meta" id="user-outstanding-count">กำลังโหลดรายการ...</div>
                </article>
                <article class="tenant-summary p-4">
                    <div class="tenant-label">สัญญาเช่า</div>
                    <div class="tenant-value fs-5" id="user-contract">—</div>
                    <div class="tenant-meta">สิ้นสุด <span id="user-contract-end">—</span></div>
                </article>
                <article class="tenant-summary p-4">
                    <div class="tenant-label">แจ้งซ่อมที่ยังค้างอยู่</div>
                    <div class="tenant-value money" id="user-repair-count">—</div>
                    <div class="tenant-meta">รายการ</div>
                </article>
        </section>

        <section class="row g-3" aria-label="รายการล่าสุด">
            <div class="col-xl-6">
                <article class="tenant-panel">
                    <header class="tenant-panel-header"><h2 class="tenant-panel-title">ใบแจ้งหนี้ล่าสุด</h2><p class="tenant-panel-subtitle">3 รายการล่าสุด</p></header>
                    <div class="table-responsive"><table class="table tenant-table align-middle"><thead><tr><th>รอบบิล</th><th>ยอดรวม</th><th>วันครบกำหนด</th><th>สถานะ</th></tr></thead><tbody id="invoice-history"><tr><td colspan="4" class="text-center text-secondary py-4">กำลังโหลดข้อมูล...</td></tr></tbody></table></div>
                </article>
            </div>
            <div class="col-xl-6">
                <article class="tenant-panel">
                    <header class="tenant-panel-header"><h2 class="tenant-panel-title">รายการแจ้งซ่อมของฉัน</h2><p class="tenant-panel-subtitle">ติดตามสถานะการซ่อม</p></header>
                    <div class="table-responsive"><table class="table tenant-table align-middle"><thead><tr><th>หัวข้อ</th><th>วันที่</th><th>สถานะ</th></tr></thead><tbody id="repair-history"><tr><td colspan="3" class="text-center text-secondary py-4">กำลังโหลดข้อมูล...</td></tr></tbody></table></div>
                </article>
            </div>
        </section>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', async () => {
        const escapeHtml = value => { const node = document.createElement('span'); node.textContent = value ?? ''; return node.innerHTML; };
        const fetchAll = async endpoint => {
            const items = []; let page = 1; let lastPage = 1;
            do {
                const response = await fetch(`${endpoint}?${new URLSearchParams({ page, per_page: 100 })}`, { credentials:'same-origin', headers:{ Accept:'application/json' } });
                const result = await response.json().catch(() => ({}));
                if (response.status === 401) { window.location.assign('/login'); throw new Error('กรุณาเข้าสู่ระบบใหม่'); }
                if (!response.ok) throw new Error(result.message || 'โหลดข้อมูลไม่สำเร็จ');
                items.push(...(result.data ?? [])); lastPage = Number(result.meta?.last_page ?? 1); page++;
            } while (page <= lastPage);
            return items;
        };
        const state = value => ({ ACTIVE:'กำลังเช่า', ENDED:'สิ้นสุดแล้ว', UNPAID:'รอชำระ', PENDING:'รอตรวจสอบ', PAID:'ชำระแล้ว', REJECTED:'ถูกปฏิเสธ', REPORTED:'แจ้งใหม่', IN_PROGRESS:'กำลังซ่อม', COMPLETED:'เสร็จแล้ว' })[value] ?? value ?? '—';
        const showError = (body, columns, label) => { body.innerHTML = `<tr><td colspan="${columns}" class="tenant-error py-4">ไม่สามารถโหลด${label}ได้ <button type="button" class="btn btn-sm btn-outline-primary ms-2" data-user-dashboard-retry>ลองใหม่</button></td></tr>`; };

        try {
            const rentals = await fetchAll('/api/v1/rentals');
            const active = rentals.find(rental => rental.rt_status === 'ACTIVE');
            if (active) {
                document.getElementById('user-room').textContent = active.room?.r_name ?? '—';
                document.getElementById('user-room-detail').textContent = `ชั้น ${active.room?.r_floor ?? '—'} · ${active.room?.r_type ?? 'ห้องพัก'}`;
                document.getElementById('user-move-in').textContent = active.rt_movein ?? '—';
                const rent = active.contract?.c_rent ?? active.room?.r_price;
                document.getElementById('user-rent').textContent = rent == null ? '—' : `฿${Number(rent).toLocaleString('th-TH')}/เดือน`;
                document.getElementById('user-contract').textContent = active.contract?.c_number ?? 'ไม่มีเลขที่สัญญา';
                document.getElementById('user-contract-end').textContent = active.contract?.c_end ?? 'ไม่มีกำหนด';
            } else {
                document.getElementById('user-room').textContent = 'ยังไม่มีห้องเช่า';
                document.getElementById('user-room-detail').textContent = 'ไม่พบรายการเช่าที่กำลังดำเนินอยู่';
            }
        } catch (error) { console.error('Rental API error:', error); document.getElementById('user-room').textContent = 'โหลดไม่ได้'; document.getElementById('user-room-detail').textContent = error.message; }

        try {
            const invoices = await fetchAll('/api/v1/invoices');
            const unpaid = invoices.filter(invoice => ['UNPAID','PENDING','REJECTED'].includes(invoice.payment?.p_status ?? 'UNPAID'));
            const outstanding = unpaid.reduce((sum, invoice) => sum + Number(invoice.i_total ?? 0), 0);
            document.getElementById('user-outstanding').textContent = `฿${outstanding.toLocaleString('th-TH', { maximumFractionDigits:2 })}`;
            document.getElementById('user-outstanding-count').textContent = `${unpaid.length} รายการยังไม่ชำระ`;
            const rows = invoices.slice(0, 3);
            document.getElementById('invoice-history').innerHTML = rows.length ? rows.map(invoice => {
                const status = invoice.payment?.p_status ?? 'UNPAID';
                const badge = status === 'PAID' ? 'paid' : 'pending';
                return `<tr><td>${escapeHtml(invoice.i_date ?? '—')}</td><td class="fw-semibold">฿${Number(invoice.i_total ?? 0).toLocaleString('th-TH', { minimumFractionDigits:2 })}</td><td>${escapeHtml(invoice.i_due ?? '—')}</td><td><span class="tenant-state ${badge}">${escapeHtml(state(status))}</span></td></tr>`;
            }).join('') : '<tr><td colspan="4" class="text-center text-secondary py-4">ยังไม่มีใบแจ้งหนี้</td></tr>';
        } catch (error) { console.error('Invoice API error:', error); document.getElementById('user-outstanding').textContent = 'โหลดไม่ได้'; document.getElementById('user-outstanding-count').textContent = 'ลองใหม่ภายหลัง'; showError(document.getElementById('invoice-history'), 4, 'ใบแจ้งหนี้'); }

        try {
            const repairs = await fetchAll('/api/v1/repairs');
            const openRepairs = repairs.filter(repair => ['REPORTED','IN_PROGRESS'].includes(repair.rp_status));
            document.getElementById('user-repair-count').textContent = openRepairs.length;
            const rows = repairs.slice(0, 3);
            document.getElementById('repair-history').innerHTML = rows.length ? rows.map(repair => `<tr><td>${escapeHtml(repair.rp_name ?? repair.rp_type ?? '—')}</td><td>${escapeHtml((repair.created_at ?? '—').toString().slice(0,10))}</td><td><span class="tenant-state ${repair.rp_status === 'COMPLETED' ? 'paid' : 'pending'}">${escapeHtml(state(repair.rp_status))}</span></td></tr>`).join('') : '<tr><td colspan="3" class="text-center text-secondary py-4">ยังไม่มีรายการแจ้งซ่อม</td></tr>';
        } catch (error) { console.error('Repair API error:', error); document.getElementById('user-repair-count').textContent = '—'; showError(document.getElementById('repair-history'), 3, 'รายการแจ้งซ่อม'); }

        document.addEventListener('click', event => { if (event.target.closest('[data-user-dashboard-retry]')) window.location.reload(); });
    });
    </script>
</x-layouts::app.sidebar>
