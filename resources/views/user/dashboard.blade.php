<x-layouts::app.sidebar :title="'Dashboard ของฉัน'">
    <style>
        .tenant-dashboard { max-width: 1500px; margin: 0 auto; padding: 28px 26px 40px; color: #142c4a; }
        .tenant-dashboard .eyebrow { color: #6480a4; font-size: 13px; margin-bottom: 4px; }
        .tenant-dashboard h1 { font-size: 24px; font-weight: 700; margin: 0; }
        .tenant-dashboard .lead { color: #7187a5; margin: 4px 0 24px; font-size: 14px; }
        .tenant-dashboard-grid { display: grid; grid-template-columns: 1.08fr 1fr 1fr; gap: 16px; }
        .tenant-dashboard-card, .tenant-dashboard-panel { background: #fff; border: 1px solid #dce5f0; border-radius: 14px; box-shadow: 0 2px 4px rgba(20,44,74,.08); }
        .tenant-dashboard-card { padding: 22px; min-height: 126px; }
        .tenant-dashboard-card.room-card { grid-row: span 2; min-height: 274px; }
        .tenant-dashboard-label { display: block; color: #8295b0; font-size: 12px; margin-bottom: 6px; }
        .tenant-dashboard-value { color: #122944; font-weight: 700; font-size: 23px; line-height: 1.25; }
        .tenant-dashboard-value.accent { color: #e97800; }
        .tenant-dashboard-meta { color: #8295b0; font-size: 12px; margin-top: 5px; }
        .tenant-room-line { display:flex; align-items:center; gap:16px; margin: 8px 0 20px; }
        .tenant-room-number { width:72px; height:72px; flex:0 0 72px; border-radius:18px; background:#203e66; display:grid; place-items:center; color:#fff; font-size:25px; font-weight:700; }
        .tenant-dashboard-separator { border:0; border-top:1px solid #edf1f6; margin:18px 0 14px; }
        .tenant-dashboard-split { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .tenant-dashboard-split strong { display:block; color:#1655e8; font-size:14px; }
        .tenant-dashboard-tables { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-top:24px; }
        .tenant-dashboard-panel { overflow:hidden; }
        .tenant-dashboard-panel-head { padding:17px 20px; border-bottom:1px solid #edf1f6; }
        .tenant-dashboard-panel-head h2 { font-size:17px; font-weight:700; margin:0 0 3px; }
        .tenant-dashboard-panel-head p { color:#8295b0; font-size:12px; margin:0; }
        .tenant-dashboard-panel .table { margin:0; font-size:13px; }
        .tenant-dashboard-panel .table th { color:#7187a5; font-weight:500; white-space:nowrap; }
        .tenant-dashboard-panel .table td, .tenant-dashboard-panel .table th { padding:12px 16px; }
        .tenant-dashboard-state { color:#7187a5; text-align:center; padding:24px 12px !important; }
        .tenant-dashboard-error { color:#b42318; }
        .tenant-status { display:inline-block; padding:3px 9px; border:1px solid #ffd66b; border-radius:7px; background:#fffaf0; color:#b96a00; font-size:11px; white-space:nowrap; }
        .tenant-status.active { border-color:#bfd6ff; background:#eff6ff; color:#2264ed; }
        @media (max-width: 1100px) { .tenant-dashboard-grid { grid-template-columns:1fr 1fr; } .tenant-dashboard-card.room-card { grid-row:span 2; } }
        @media (max-width: 720px) { .tenant-dashboard { padding:20px 14px 30px; } .tenant-dashboard-grid, .tenant-dashboard-tables { grid-template-columns:1fr; } .tenant-dashboard-card.room-card { grid-row:auto; min-height:unset; } }
    </style>

    <main class="tenant-dashboard">
        <div class="eyebrow">Dashboard</div>
        <h1>สวัสดี, {{ auth()->user()->u_username }} 👋</h1>
        <p class="lead">ข้อมูลสรุปของห้องพักของคุณ</p>

        <section class="tenant-dashboard-grid" aria-label="สรุปข้อมูลผู้เช่า">
            <article class="tenant-dashboard-card room-card">
                <span class="tenant-dashboard-label">ห้องของฉัน</span>
                <div class="tenant-room-line">
                    <div class="tenant-room-number" id="tenant-room-number">—</div>
                    <div><div class="tenant-dashboard-value" id="tenant-room-name">กำลังโหลด...</div><div class="tenant-dashboard-meta" id="tenant-room-detail">ข้อมูลห้องพัก</div><span class="tenant-status active" id="tenant-rental-status">—</span></div>
                </div>
                <hr class="tenant-dashboard-separator">
                <div class="tenant-dashboard-split">
                    <div><span class="tenant-dashboard-label">สัญญาเช่า</span><strong id="tenant-rent">—</strong></div>
                    <div><span class="tenant-dashboard-label">เริ่มเข้าพัก</span><strong id="tenant-movein" style="color:#142c4a">—</strong></div>
                </div>
            </article>

            <article class="tenant-dashboard-card"><span class="tenant-dashboard-label">ยอดค้างชำระ</span><div class="tenant-dashboard-value accent" id="tenant-outstanding">กำลังโหลด...</div><div class="tenant-dashboard-meta" id="tenant-outstanding-count">ใบแจ้งหนี้</div></article>
            <article class="tenant-dashboard-card"><span class="tenant-dashboard-label">สัญญาเช่า</span><div class="tenant-dashboard-value" id="tenant-contract">กำลังโหลด...</div><div class="tenant-dashboard-meta" id="tenant-contract-end">ดูรายละเอียดสัญญาของคุณ</div></article>
            <article class="tenant-dashboard-card"><span class="tenant-dashboard-label">แจ้งซ่อมที่ยังค้างอยู่</span><div class="tenant-dashboard-value accent" id="tenant-repair-count">กำลังโหลด...</div><div class="tenant-dashboard-meta">รายการ</div></article>
            <article class="tenant-dashboard-card"><span class="tenant-dashboard-label">ค่าน้ำ-ค่าไฟบิลล่าสุด</span><div class="tenant-dashboard-value" id="tenant-latest-utilities">กำลังโหลด...</div><div class="tenant-dashboard-meta" id="tenant-latest-usage">จากใบแจ้งหนี้ล่าสุด</div></article>
        </section>

        <section class="tenant-dashboard-tables">
            <article class="tenant-dashboard-panel">
                <header class="tenant-dashboard-panel-head"><h2>ใบแจ้งหนี้ล่าสุด</h2><p>3 รายการล่าสุด</p></header>
                <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>รอบบิล</th><th>ยอดรวม</th><th>วันครบกำหนด</th><th>สถานะ</th></tr></thead><tbody id="tenant-invoice-rows"><tr><td colspan="4" class="tenant-dashboard-state">กำลังโหลดข้อมูล...</td></tr></tbody></table></div>
            </article>
            <article class="tenant-dashboard-panel">
                <header class="tenant-dashboard-panel-head"><h2>รายการแจ้งซ่อมของฉัน</h2><p>ติดตามสถานะการซ่อม</p></header>
                <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>หัวข้อ</th><th>วันที่</th><th>สถานะ</th></tr></thead><tbody id="tenant-repair-rows"><tr><td colspan="3" class="tenant-dashboard-state">กำลังโหลดข้อมูล...</td></tr></tbody></table></div>
            </article>
        </section>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', async () => {
        const byId = id => document.getElementById(id);
        const escapeHtml = value => { const node = document.createElement('span'); node.textContent = value ?? ''; return node.innerHTML; };
        const money = value => `฿${Number(value ?? 0).toLocaleString('th-TH', {minimumFractionDigits: 0, maximumFractionDigits: 2})}`;
        const date = value => value ? new Date(`${value}T00:00:00`).toLocaleDateString('th-TH', {year:'numeric', month:'2-digit', day:'2-digit'}) : '—';
        const statusNames = {UNPAID:'รอชำระ', PENDING:'รอตรวจสอบ', PAID:'ชำระแล้ว', REJECTED:'ถูกปฏิเสธ', REPORTED:'แจ้งใหม่', IN_PROGRESS:'กำลังซ่อม', COMPLETED:'เสร็จแล้ว'};
        const getAll = async path => {
            let page = 1, last = 1, all = [];
            do {
                const response = await fetch(`${path}?page=${page}&per_page=100`, {credentials:'same-origin', headers:{Accept:'application/json'}});
                const body = await response.json().catch(() => ({}));
                if (response.status === 401) { location.assign('/login'); throw new Error('กรุณาเข้าสู่ระบบใหม่'); }
                if (!response.ok) throw new Error(body.message || 'โหลดข้อมูลไม่สำเร็จ');
                all = all.concat(body.data ?? []); last = Number(body.meta?.last_page ?? 1); page++;
            } while (page <= last);
            return all;
        };
        const showError = (tbody, cols, error) => tbody.innerHTML = `<tr><td colspan="${cols}" class="tenant-dashboard-state tenant-dashboard-error">${escapeHtml(error.message)} <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">ลองใหม่</button></td></tr>`;
        try {
            const [rentals, invoices, repairs] = await Promise.all([getAll('/api/v1/rentals'), getAll('/api/v1/invoices'), getAll('/api/v1/repairs')]);
            const current = rentals.find(rental => rental.rt_status === 'ACTIVE') ?? rentals[0];
            if (current) {
                const roomName = current.room?.r_name ?? '—';
                byId('tenant-room-number').textContent = roomName;
                byId('tenant-room-name').textContent = `ห้อง ${roomName}`;
                byId('tenant-room-detail').textContent = current.room?.r_floor ? `ชั้น ${current.room.r_floor}` : 'ข้อมูลห้องพัก';
                byId('tenant-rental-status').textContent = current.rt_status === 'ACTIVE' ? 'กำลังเช่า' : 'สิ้นสุดการเช่า';
                byId('tenant-rent').textContent = current.room?.r_rent ? money(current.room.r_rent) + '/เดือน' : (current.contract?.c_number ?? 'ดูสัญญาของคุณ');
                byId('tenant-movein').textContent = date(current.rt_movein);
                byId('tenant-contract').textContent = current.contract?.c_number ?? 'ยังไม่มีสัญญา';
                byId('tenant-contract-end').textContent = current.contract?.c_status ?? 'ดูรายละเอียดสัญญาของคุณ';
                if (current.contract?.c_number) {
                    const contractResponse = await fetch(`/api/v1/rentals/${current.rt_id}/contract`, {credentials:'same-origin', headers:{Accept:'application/json'}});
                    if (contractResponse.ok) {
                        const contract = (await contractResponse.json()).data;
                        byId('tenant-contract-end').textContent = `สิ้นสุด ${date(contract.c_end)}`;
                        if (contract.c_rent) byId('tenant-rent').textContent = `${money(contract.c_rent)}/เดือน`;
                    }
                }
            } else {
                byId('tenant-room-name').textContent = 'ยังไม่มีห้องเช่า';
                byId('tenant-room-number').textContent = '—';
                byId('tenant-room-detail').textContent = 'ยังไม่พบรายการเช่าในบัญชีนี้';
                byId('tenant-contract').textContent = '—';
                byId('tenant-rental-status').textContent = 'ไม่มีรายการ';
            }

            const openInvoices = invoices.filter(invoice => ['UNPAID','PENDING','REJECTED'].includes(invoice.payment?.p_status ?? 'UNPAID'));
            byId('tenant-outstanding').textContent = money(openInvoices.reduce((sum, invoice) => sum + Number(invoice.i_total ?? 0), 0));
            byId('tenant-outstanding-count').textContent = `${openInvoices.length} รายการยังไม่ชำระ`;
            const latest = invoices[0];
            if (latest) {
                if (latest.i_rent) byId('tenant-rent').textContent = `${money(latest.i_rent)}/เดือน`;
                const utility = Number(latest.i_water ?? 0) + Number(latest.i_elec ?? 0);
                byId('tenant-latest-utilities').textContent = money(utility);
                byId('tenant-latest-usage').textContent = `น้ำ ${latest.water_usage ?? '—'} + ไฟ ${latest.elec_usage ?? '—'} หน่วย`;
            } else {
                byId('tenant-latest-utilities').textContent = 'ยังไม่มีข้อมูล';
                byId('tenant-latest-usage').textContent = 'ยังไม่มีใบแจ้งหนี้';
            }

            const activeRepairs = repairs.filter(repair => ['REPORTED','IN_PROGRESS'].includes(repair.rp_status));
            byId('tenant-repair-count').textContent = activeRepairs.length;
            const invoiceRows = byId('tenant-invoice-rows');
            invoiceRows.innerHTML = invoices.length ? invoices.slice(0,3).map(invoice => {
                const period = invoice.period_start ?? invoice.i_date ?? '—';
                const paymentStatus = invoice.payment?.p_status ?? 'UNPAID';
                return `<tr><td>${escapeHtml(period)}</td><td><strong>${money(invoice.i_total)}</strong></td><td>${escapeHtml(date(invoice.i_due))}</td><td><span class="tenant-status">${escapeHtml(statusNames[paymentStatus] ?? paymentStatus)}</span></td></tr>`;
            }).join('') : '<tr><td colspan="4" class="tenant-dashboard-state">ยังไม่มีใบแจ้งหนี้</td></tr>';
            const repairRows = byId('tenant-repair-rows');
            repairRows.innerHTML = repairs.length ? repairs.slice(0,3).map(repair => `<tr><td>${escapeHtml(repair.rp_name ?? repair.rp_type ?? '—')}</td><td>${escapeHtml(date((repair.created_at ?? '').slice(0,10)))}</td><td><span class="tenant-status active">${escapeHtml(statusNames[repair.rp_status] ?? repair.rp_status ?? '—')}</span></td></tr>`).join('') : '<tr><td colspan="3" class="tenant-dashboard-state">ยังไม่มีรายการแจ้งซ่อม</td></tr>';
        } catch (error) {
            console.error('Tenant dashboard load failed:', error);
            showError(byId('tenant-invoice-rows'), 4, error);
            showError(byId('tenant-repair-rows'), 3, error);
            ['tenant-room-name','tenant-outstanding','tenant-contract','tenant-repair-count','tenant-latest-utilities'].forEach(id => byId(id).textContent = 'โหลดไม่สำเร็จ');
        }
    });
    </script>
</x-layouts::app.sidebar>
