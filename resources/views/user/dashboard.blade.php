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
@php($latest = $invoices->first())
        <div class="eyebrow">Dashboard</div>
        <h1>สวัสดี, {{ auth()->user()->u_username }} 👋</h1>
        <p class="lead">ข้อมูลสรุปของห้องพักของคุณ</p>

        <section class="tenant-dashboard-grid" aria-label="สรุปข้อมูลผู้เช่า">
            <article class="tenant-dashboard-card room-card">
                <span class="tenant-dashboard-label">ห้องของฉัน</span>
                <div class="tenant-room-line">
                    <div class="tenant-room-number" id="tenant-room-number">{{ $current?->room?->r_name ?? '—' }}</div>
                    <div><div class="tenant-dashboard-value" id="tenant-room-name">{{ $current ? 'ห้อง '.$current->room?->r_name : 'ยังไม่มีห้องเช่า' }}</div><div class="tenant-dashboard-meta" id="tenant-room-detail">{{ $current?->room ? 'ชั้น '.$current->room->r_floor : 'ยังไม่พบรายการเช่าในบัญชีนี้' }}</div><span class="tenant-status active" id="tenant-rental-status">{{ $current ? (['ACTIVE' => 'กำลังเช่า', 'SCHEDULED' => 'รอเข้าอยู่', 'ENDED' => 'สิ้นสุดการเช่า', 'CANCELLED' => 'ยกเลิกแล้ว'][$current->rt_status] ?? $current->rt_status) : 'ไม่มีรายการ' }}</span></div>
                </div>
                <hr class="tenant-dashboard-separator">
                <div class="tenant-dashboard-split">
                    <div><span class="tenant-dashboard-label">สัญญาเช่า</span><strong id="tenant-rent">{{ $current?->contract ? '฿'.number_format((float)$current->contract->c_rent, 2).'/เดือน' : '—' }}</strong></div>
                    <div><span class="tenant-dashboard-label">{{ $current?->rt_movein === null && $current?->rt_status === 'SCHEDULED' ? 'กำหนดเข้าพัก' : 'เริ่มเข้าพัก' }}</span><strong id="tenant-movein" style="color:#142c4a">{{ ($current?->rt_movein ?? $current?->scheduled_movein_at)?->format('d/m/Y') ?? '—' }}</strong></div>
                </div>
            </article>

            <article class="tenant-dashboard-card"><span class="tenant-dashboard-label">ยอดค้างชำระ</span><div class="tenant-dashboard-value accent" id="tenant-outstanding">฿{{ number_format((float)$outstandingAmount, 2) }}</div><div class="tenant-dashboard-meta" id="tenant-outstanding-count">{{ $outstandingCount }} รายการยังไม่ชำระ</div></article>
            <article class="tenant-dashboard-card"><span class="tenant-dashboard-label">สัญญาเช่า</span><div class="tenant-dashboard-value" id="tenant-contract">{{ $current?->contract?->c_number ?? '—' }}</div><div class="tenant-dashboard-meta" id="tenant-contract-end">{{ $current?->contract ? 'สิ้นสุด '.($current->contract->c_end?->format('d/m/Y') ?? 'ไม่มีกำหนด') : 'ยังไม่มีสัญญา' }}</div></article>
            <article class="tenant-dashboard-card"><span class="tenant-dashboard-label">แจ้งซ่อมที่ยังค้างอยู่</span><div class="tenant-dashboard-value accent" id="tenant-repair-count">{{ $repairCount }}</div><div class="tenant-dashboard-meta">รายการ</div></article>
            <article class="tenant-dashboard-card"><span class="tenant-dashboard-label">ค่าน้ำ-ค่าไฟบิลล่าสุด</span><div class="tenant-dashboard-value" id="tenant-latest-utilities">{{ $latest ? '฿'.number_format((float)$latest->i_water + (float)$latest->i_elec, 2) : 'ยังไม่มีข้อมูล' }}</div><div class="tenant-dashboard-meta" id="tenant-latest-usage">{{ $latest ? 'น้ำ '.$latest->water_usage.' + ไฟ '.$latest->elec_usage.' หน่วย' : 'ยังไม่มีใบแจ้งหนี้' }}</div></article>
        </section>

        <section class="tenant-dashboard-tables">
            <article class="tenant-dashboard-panel">
                <header class="tenant-dashboard-panel-head"><h2>ใบแจ้งหนี้ล่าสุด</h2><p>3 รายการล่าสุด</p></header>
                <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>รอบบิล</th><th>ยอดรวม</th><th>วันครบกำหนด</th><th>สถานะ</th></tr></thead><tbody id="tenant-invoice-rows">@forelse($invoices as $invoice)<tr><td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->period_start?->format('Y-m-d') }}</a></td><td><strong>฿{{ number_format((float)$invoice->i_total, 2) }}</strong></td><td>{{ $invoice->i_due->format('d/m/Y') }}</td><td>@include('invoices._status-badge', ['invoice' => $invoice])</td></tr>@empty<tr><td colspan="4" class="tenant-dashboard-state">ยังไม่มีใบแจ้งหนี้</td></tr>@endforelse</tbody></table></div>
            </article>
            <article class="tenant-dashboard-panel">
                <header class="tenant-dashboard-panel-head"><h2>รายการแจ้งซ่อมของฉัน</h2><p>ติดตามสถานะการซ่อม</p></header>
                <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>หัวข้อ</th><th>วันที่</th><th>สถานะ</th></tr></thead><tbody id="tenant-repair-rows">@forelse($repairs as $repair)<tr><td>{{ $repair->rp_name }}</td><td>{{ $repair->created_at?->format('d/m/Y') }}</td><td><span class="tenant-status active">{{ ['REPORTED' => 'แจ้งใหม่', 'IN_PROGRESS' => 'กำลังซ่อม', 'COMPLETED' => 'เสร็จแล้ว'][$repair->rp_status] ?? $repair->rp_status }}</span></td></tr>@empty<tr><td colspan="3" class="tenant-dashboard-state">ยังไม่มีรายการแจ้งซ่อม</td></tr>@endforelse</tbody></table></div>
            </article>
        </section>
    </main>


</x-layouts::app.sidebar>
