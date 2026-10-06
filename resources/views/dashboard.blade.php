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
        box-shadow: 0 2px 5px rgba(15, 23, 42, 0.04);
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


            {{-- ยอดค้างชำระ --}}
            <div class="col">
                <div class="summary-card p-3">

                    <div class="d-flex align-items-center gap-3">

                        <div class="summary-icon" style="background:#ef0011;color:#ffffff;">
                            <i class="bi bi-cash-stack"></i>
                        </div>

                        <div>
                            <div class="summary-label">
                                ยอดค้างชำระ
                            </div>

                            <div class="summary-value" id="dashboard-outstanding-amount">
                                ฿0
                            </div>

                            <div class="summary-unit" id="dashboard-outstanding-count">
                                0 รายการ
                            </div>
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

                            <div class="summary-value" id="dashboard-repair-count">
                                0
                            </div>

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
                    $isAvailable = $room->r_status === 'ว่าง';
                    @endphp

                    <div class="room-box {{ $isAvailable ? 'available' : 'occupied' }}">

                        <div class="d-flex justify-content-between">

                            <div>

                                <div class="room-number">
                                    ห้อง {{ $room->r_name }}
                                </div>

                                <div class="room-status {{ $isAvailable ? 'available-text' : 'occupied-text' }}">
                                    {{ $room->r_status }}
                                </div>

                                @if (!$isAvailable)
                                <div class="room-tenant">
                                    มีผู้เช่า
                                </div>
                                @endif

                                <div class="room-price">
                                    ฿{{ number_format($room->r_rent, 2) }}/เดือน
                                </div>

                            </div>

                            <div class="room-dot {{ $isAvailable ? 'available-dot' : 'occupied-dot' }}"></div>

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

                            <a href="#" class="view-all">
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

                            <tbody>

                                <tr>
                                    <td colspan="5" class="empty-table">
                                        ยังไม่มีข้อมูลค้างชำระ
                                    </td>
                                </tr>

                            </tbody>

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

                            <a href="#" class="view-all">
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

                            <tbody>

                                <tr>
                                    <td colspan="5" class="empty-table">
                                        ยังไม่มีข้อมูลแจ้งซ่อม
                                    </td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <script>
    document.addEventListener('DOMContentLoaded', async () => {
        if (!window.dashboardApi) {
            return;
        }

        try {
            const response = await window.dashboardApi.get();
            const data = response.data;

            const outstandingAmount = document.getElementById(
                'dashboard-outstanding-amount'
            );

            const outstandingCount = document.getElementById(
                'dashboard-outstanding-count'
            );

            const repairCount = document.getElementById(
                'dashboard-repair-count'
            );

            if (outstandingAmount) {
                outstandingAmount.textContent =
                    `฿${Number(data.payments.outstanding_amount).toLocaleString('th-TH', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })}`;
            }

            if (outstandingCount) {
                outstandingCount.textContent =
                    `${data.payments.outstanding_count} รายการ`;
            }

            if (repairCount) {
                const repairs = Object.values(data.repairs ?? {});
                const totalRepairs = repairs.reduce(
                    (total, count) => total + Number(count),
                    0
                );

                repairCount.textContent = totalRepairs;
            }
        } catch (error) {
            console.error('Dashboard API error:', error);
        }
    });
    </script>

</x-layouts::app.sidebar>