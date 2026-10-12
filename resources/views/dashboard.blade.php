<x-layouts::app.sidebar :title="'Dashboard'">

    <style>
    .dashboard-page {
        background: #f6f8fb;
        min-height: calc(100vh - 70px);
        padding: 24px;
    }

    .dashboard-title {
        color: #162d4a;
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 4px;
    }

    .dashboard-subtitle {
        color: #90a1b9;
        font-size: 13px;
    }

    /* SUMMARY CARDS */
    .summary-card {
        background: #ffffff;
        border: 1px solid #e1e7ef;
        border-radius: 12px;
        min-height: 108px;
        height: 100%;
        display: flex;
        align-items: center;
        box-shadow: 0 2px 5px rgba(15, 23, 42, 0.04);
    }

    .summary-card>.d-flex {
        width: 100%;
        min-width: 0;
    }

    .summary-card>.d-flex>div:last-child {
        min-width: 0;
    }

    .summary-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .summary-label {
        color: #718096;
        font-size: 12px;
        margin-bottom: 2px;
    }

    .summary-value {
        color: #172033;
        font-size: 25px;
        font-weight: 700;
        line-height: 1.15;
    }

    .summary-unit {
        color: #90a1b9;
        font-size: 11px;
        margin-top: 2px;
    }

    .summary-value.is-error {
        color: #64748b;
        font-size: 13px;
        line-height: 1.35;
        white-space: nowrap;
    }


    /* MAIN CARD */
    .dashboard-card {
        background: #ffffff;
        border: 1px solid #dfe6ee;
        border-radius: 12px;
        box-shadow: 0 2px 5px rgba(15, 23, 42, 0.04);
    }

    .dashboard-card-header {
        padding: 18px 22px;
        border-bottom: 1px solid #e7ebf0;
    }

    .dashboard-card-title {
        color: #1e293b;
        font-size: 16px;
        font-weight: 700;
        margin: 0;
    }

    .dashboard-card-subtitle {
        color: #90a1b9;
        font-size: 12px;
        margin-top: 4px;
    }


    /* ROOM STATUS */
    .room-status-body {
        padding: 18px 22px 20px;
    }

    .floor-title {
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 9px;
    }

    .room-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 8px;
        margin-bottom: 17px;
    }

    .room-box {
        min-height: 105px;
        border-radius: 8px;
        padding: 12px;
        border: 2px solid;
    }

    .room-box.available {
        background: #ecfdf5;
        border-color: #6ee7b7;
    }

    .room-box.occupied {
        background: #eff6ff;
        border-color: #93c5fd;
    }

    .room-number {
        color: #26364d;
        font-size: 14px;
        font-weight: 700;
    }

    .room-status {
        font-size: 12px;
        margin-top: 4px;
    }

    .room-status.available-text {
        color: #059669;
    }

    .room-status.occupied-text {
        color: #2563eb;
    }

    .room-tenant {
        color: #90a1b9;
        font-size: 11px;
        margin-top: 2px;
    }

    .room-price {
        color: #90a1b9;
        font-size: 11px;
        margin-top: 3px;
    }

    .room-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-top: 5px;
    }

    .room-dot.available-dot {
        background: #10b981;
    }

    .room-dot.occupied-dot {
        background: #3b82f6;
    }


    /* BOTTOM TABLES */
    .table-card {
        background: #ffffff;
        border: 1px solid #dfe6ee;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 5px rgba(15, 23, 42, 0.04);
    }

    .table-header {
        padding: 18px 22px 15px;
        border-bottom: 1px solid #e7ebf0;
    }

    .table-title {
        color: #1e293b;
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 3px;
    }

    .table-subtitle {
        color: #90a1b9;
        font-size: 12px;
    }

    .view-all {
        color: #1769ff;
        font-size: 12px;
        text-decoration: none;
    }

    .view-all:hover {
        color: #0d5bdd;
    }

    .dashboard-table {
        width: 100%;
        margin: 0;
        border-collapse: collapse;
    }

    .dashboard-table th {
        background: #ffffff;
        color: #64748b;
        font-size: 11px;
        font-weight: 600;
        padding: 11px 14px;
        border-bottom: 1px solid #e5eaf0;
        white-space: nowrap;
    }

    .dashboard-table td {
        color: #475569;
        font-size: 12px;
        padding: 11px 14px;
        border-bottom: 1px solid #edf1f5;
        white-space: nowrap;
    }

    .dashboard-table tr:last-child td {
        border-bottom: 0;
    }

    .amount {
        color: #334155;
        font-weight: 700;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 9px;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 500;
        border: 1px solid;
    }

    .status-waiting {
        color: #d97706;
        background: #fffbeb;
        border-color: #fcd34d;
    }

    .status-overdue {
        color: #dc2626;
        background: #fef2f2;
        border-color: #fca5a5;
    }

    .status-done {
        color: #059669;
        background: #ecfdf5;
        border-color: #6ee7b7;
    }

    .status-repair {
        color: #2563eb;
        background: #eff6ff;
        border-color: #93c5fd;
    }

    .empty-table {
        text-align: center;
        color: #94a3b8;
        padding: 35px 15px !important;
    }

    .room-box.maintenance {
        background: #fff7ed;
        border-color: #fdba74;
    }

    .room-status.maintenance-text {
        color: #c2410c;
    }

    .room-dot.maintenance-dot {
        background: #f97316;
    }
    </style>


    <div class="dashboard-page">

        {{-- SUMMARY --}}
        <div class="row g-3 mb-4">

            {{-- ห้องทั้งหมด --}}
            <div class="col">
                <div class="summary-card p-3">

                    <div class="d-flex align-items-center gap-3">

                        <div class="summary-icon" style="background:#334155;color:#ffffff;">
                            <i class="bi bi-building"></i>
                        </div>

                        <div>
                            <div class="summary-label">
                                ห้องทั้งหมด
                            </div>

                            <div class="summary-value">
                                {{ $totalRooms }}
                            </div>

                            <div class="summary-unit">
                                ห้อง
                            </div>
                        </div>

                    </div>

                </div>
            </div>


            {{-- ห้องว่าง --}}
            <div class="col">
                <div class="summary-card p-3">

                    <div class="d-flex align-items-center gap-3">

                        <div class="summary-icon" style="background:#00a878;color:#ffffff;">
                            <i class="bi bi-check-circle"></i>
                        </div>

                        <div>
                            <div class="summary-label">
                                ห้องว่าง
                            </div>

                            <div class="summary-value">
                                {{ $availableRooms }}
                            </div>

                            <div class="summary-unit">
                                ห้อง
                            </div>
                        </div>

                    </div>

                </div>
            </div>


            {{-- ผู้เช่า --}}
            <div class="col">
                <div class="summary-card p-3">

                    <div class="d-flex align-items-center gap-3">

                        <div class="summary-icon" style="background:#1769ff;color:#ffffff;">
                            <i class="bi bi-person"></i>
                        </div>

                        <div>
                            <div class="summary-label">
                                ผู้เช่า
                            </div>

                            <div class="summary-value">
                                {{ $totalTenants }}
                            </div>

                            <div class="summary-unit">
                                คน
                            </div>
                        </div>

                    </div>

                </div>
            </div>


            {{-- ผู้เช่าปัจจุบัน --}}
            <div class="col">
                <div class="summary-card p-3">

                    <div class="d-flex align-items-center gap-3">

                        <div class="summary-icon" style="background:#5138ee;color:#ffffff;">
                            <i class="bi bi-people"></i>
                        </div>

                        <div>
                            <div class="summary-label">
                                ผู้เช่าปัจจุบัน
                            </div>

                            <div class="summary-value">
                                {{ $currentTenants }}
                            </div>

                            <div class="summary-unit">
                                คน
                            </div>
                        </div>

                    </div>

                </div>
            </div>


            {{-- ยังไม่ถึงกำหนด --}}
            <div class="col">
                <div class="summary-card p-3">

                    <div class="d-flex align-items-center gap-3">

                        <div class="summary-icon" style="background:#ef0011;color:#ffffff;">
                            <i class="bi bi-cash-stack"></i>
                        </div>

                        <div>
                            <div class="summary-label">
                                ยังไม่ชำระ
                            </div>

                            <div class="summary-value" id="dashboard-current-due-amount">฿{{ number_format((float)$dashboard['payments']['outstanding_amount'] - (float)$dashboard['payments']['overdue_amount'], 2) }}</div>

                            <div class="summary-unit" id="dashboard-current-due-count">{{ $dashboard['payments']['outstanding_count'] - $dashboard['payments']['overdue_count'] }} รายการ · ยังไม่เกินกำหนด</div>
                        </div>

                    </div>

                </div>
            </div>


            {{-- เกินกำหนด --}}
            <div class="col">
                <div class="summary-card p-3">

                    <div class="d-flex align-items-center gap-3">

                        <div class="summary-icon" style="background:#dc2626;color:#ffffff;">
                            <i class="bi bi-exclamation-circle"></i>
                        </div>

                        <div>
                            <div class="summary-label">
                                เกินกำหนดชำระ
                            </div>

                            <div class="summary-value" id="dashboard-overdue-amount">฿{{ number_format((float)$dashboard['payments']['overdue_amount'], 2) }}</div>

                            <div class="summary-unit" id="dashboard-overdue-count">{{ $dashboard['payments']['overdue_count'] }} รายการ</div>
                        </div>

                    </div>

                </div>
            </div>


            {{-- แจ้งซ่อมค้าง --}}
            <div class="col">
                <div class="summary-card p-3">

                    <div class="d-flex align-items-center gap-3">

                        <div class="summary-icon" style="background:#e87500;color:#ffffff;">
                            <i class="bi bi-gear"></i>
                        </div>

                        <div>
                            <div class="summary-label">
                                แจ้งซ่อมค้าง
                            </div>

                            <div class="summary-value" id="dashboard-repair-count">{{ ($dashboard['repairs']['REPORTED'] ?? 0) + ($dashboard['repairs']['IN_PROGRESS'] ?? 0) }}</div>

                            <div class="summary-unit">
                                รายการ
                            </div>
                        </div>

                    </div>

                </div>
            </div>

        </div>


        {{-- ROOM STATUS --}}
        <div class="dashboard-card mb-4">

            <div class="dashboard-card-header">

                <div class="dashboard-card-title">
                    สถานะห้องพัก
                </div>

                <div class="dashboard-card-subtitle">
                    ภาพรวมห้องพักทั้งหมด
                </div>

            </div>


            <div class="room-status-body">

                @forelse ($rooms->groupBy('r_floor') as $floor => $floorRooms)

                <div class="floor-title">
                    ชั้น {{ $floor }}
                </div>

                <div class="room-grid">

                    @foreach ($floorRooms as $room)

                    @php
                    $isAvailable = in_array($room->r_status, ['VACANT', 'ว่าง'], true);
                    $isOccupied = in_array($room->r_status, ['OCCUPIED', 'มีผู้พัก'], true);
                    $roomStatusLabel = match ($room->r_status) {
                    'VACANT' => 'ว่าง',
                    'OCCUPIED' => 'มีผู้พัก',
                    'MAINTENANCE' => 'ปิดปรับปรุง',
                    default => $room->r_status,
                    };

                    $roomStatusClass = match ($room->r_status) {
                    'VACANT', 'ว่าง' => 'available',
                    'OCCUPIED', 'มีผู้พัก' => 'occupied',
                    default => 'maintenance',
                    };
                    @endphp

                    <div class="room-box {{ $roomStatusClass }}">

                        <div class="d-flex justify-content-between">

                            <div>

                                <div class="room-number">
                                    ห้อง {{ $room->r_name }}
                                </div>

                                <div
                                    class="room-status {{ $isAvailable ? 'available-text' : ($isOccupied ? 'occupied-text' : 'maintenance-text') }}">
                                    {{ $roomStatusLabel }}
                                </div>

                                @if ($isOccupied)
                                <div class="room-tenant">มีผู้เช่า</div>
                                @endif

                                <div class="room-price">
                                    ฿{{ number_format($room->r_rent, 2) }}/เดือน
                                </div>

                            </div>

                            <div
                                class="room-dot
                                {{ $isAvailable ? 'available-dot' : ($isOccupied ? 'occupied-dot' : 'maintenance-dot') }}">
                            </div>

                        </div>

                    </div>

                    @endforeach

                </div>

                @empty

                <div class="text-center py-5 text-secondary">
                    ยังไม่มีข้อมูลห้องพัก
                </div>

                @endforelse

            </div>

        </div>


        {{-- BOTTOM --}}
        <div class="row g-4">

            {{-- รายการค้างชำระ --}}
            <div class="col-lg-6">

                <div class="table-card">

                    <div class="table-header">

                        <div class="d-flex justify-content-between align-items-start">

                            <div>
                                <div class="table-title">
                                    รายการค้างชำระ
                                </div>

                                <div class="table-subtitle">
                                    ยังไม่ได้ชำระ
                                </div>
                            </div>

                            <a href="{{ route('invoices.index') }}" class="view-all">
                                ดูทั้งหมด →
                            </a>

                        </div>

                    </div>

                    <div class="table-responsive">

                        <table class="dashboard-table">

                            <thead>
                                <tr>
                                    <th>ห้อง</th>
                                    <th>ผู้เช่า</th>
                                    <th>รอบบิล</th>
                                    <th>ยอด</th>
                                    <th>สถานะ</th>
                                </tr>
                            </thead>

                            <tbody id="dashboard-invoice-rows">@forelse($dueInvoices as $invoice)<tr><td>{{ $invoice->rental?->room?->r_name ?? '—' }}</td><td>{{ $invoice->rental?->tenant?->t_Fname }} {{ $invoice->rental?->tenant?->t_Lname }}</td><td>{{ $invoice->period_start?->format('Y-m-d') }}</td><td class="amount">฿{{ number_format((float)$invoice->i_total, 2) }}</td><td>@include('invoices._status-badge', ['invoice' => $invoice])</td></tr>@empty<tr><td colspan="5" class="empty-table">ยังไม่มีรายการค้างชำระ</td></tr>@endforelse</tbody>

                        </table>

                    </div>

                </div>

            </div>


            {{-- แจ้งซ่อมล่าสุด --}}
            <div class="col-lg-6">

                <div class="table-card">

                    <div class="table-header">

                        <div class="d-flex justify-content-between align-items-start">

                            <div>
                                <div class="table-title">
                                    รายการแจ้งซ่อมล่าสุด
                                </div>

                                <div class="table-subtitle">
                                    สถานะการซ่อมแซม
                                </div>
                            </div>

                            <a href="{{ route('repairs.index') }}" class="view-all">
                                ดูทั้งหมด →
                            </a>

                        </div>

                    </div>

                    <div class="table-responsive">

                        <table class="dashboard-table">

                            <thead>
                                <tr>
                                    <th>รหัส</th>
                                    <th>ห้อง</th>
                                    <th>หัวข้อ</th>
                                    <th>วันที่</th>
                                    <th>สถานะ</th>
                                </tr>
                            </thead>

                            <tbody id="dashboard-repair-rows">@forelse($recentRepairs as $repair)<tr><td>{{ $repair->rp_id }}</td><td>{{ $repair->room?->r_name ?? 'ส่วนกลาง' }}</td><td>{{ $repair->rp_name }}</td><td>{{ $repair->created_at?->format('Y-m-d') }}</td><td><span class="status-badge status-repair">{{ ['REPORTED' => 'แจ้งใหม่', 'IN_PROGRESS' => 'กำลังดำเนินการ', 'COMPLETED' => 'เสร็จแล้ว'][$repair->rp_status] ?? $repair->rp_status }}</span></td></tr>@empty<tr><td colspan="5" class="empty-table">ยังไม่มีข้อมูลแจ้งซ่อม</td></tr>@endforelse</tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>



</x-layouts::app.sidebar>
