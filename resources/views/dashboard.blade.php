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

    .summary-card > .d-flex {
        width: 100%;
        min-width: 0;
    }

    .summary-card > .d-flex > div:last-child {
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

                            <div class="summary-value" id="dashboard-current-due-amount">
                                กำลังโหลด...
                            </div>

                            <div class="summary-unit" id="dashboard-current-due-count">
                                —
                            </div>
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

                            <div class="summary-value" id="dashboard-overdue-amount">
                                กำลังโหลด...
                            </div>

                            <div class="summary-unit" id="dashboard-overdue-count">
                                —
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

                            <tbody id="dashboard-invoice-rows">

                                <tr>
                                    <td colspan="5" class="empty-table" role="status" aria-live="polite">
                                        กำลังโหลดรายการใบแจ้งหนี้...
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

                            <tbody id="dashboard-repair-rows">

                                <tr>
                                    <td colspan="5" class="empty-table" role="status" aria-live="polite">
                                        กำลังโหลดรายการแจ้งซ่อม...
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
            ['dashboard-current-due-amount', 'dashboard-overdue-amount'].forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.textContent = 'โหลดไม่ได้';
                    element.classList.add('is-error');
                }
            });
            ['dashboard-current-due-count', 'dashboard-overdue-count'].forEach(id => {
                const element = document.getElementById(id);
                if (element) element.textContent = 'ลองใหม่ภายหลัง';
            });
            return;
        }

        try {
            const response = await window.dashboardApi.get();
            const data = response.data;

            const currentDueAmount = document.getElementById(
                'dashboard-current-due-amount'
            );

            const currentDueCount = document.getElementById(
                'dashboard-current-due-count'
            );

            const overdueAmount = document.getElementById(
                'dashboard-overdue-amount'
            );

            const overdueCount = document.getElementById(
                'dashboard-overdue-count'
            );

            const repairCount = document.getElementById(
                'dashboard-repair-count'
            );

            const toCents = value => Math.round(Number(value ?? 0) * 100);
            const formatBaht = cents => `฿${(cents / 100).toLocaleString('th-TH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })}`;
            const currentDueAmountCents = Math.max(
                0,
                toCents(data.payments.outstanding_amount) -
                    toCents(data.payments.overdue_amount)
            );
            const currentDueCountValue = Math.max(
                0,
                Number(data.payments.outstanding_count ?? 0) -
                    Number(data.payments.overdue_count ?? 0)
            );

            if (currentDueAmount) currentDueAmount.textContent = formatBaht(currentDueAmountCents);
            if (currentDueCount) currentDueCount.textContent = `${currentDueCountValue} รายการ · ยังไม่เกินกำหนด`;
            if (overdueAmount) overdueAmount.textContent = formatBaht(toCents(data.payments.overdue_amount));
            if (overdueCount) overdueCount.textContent = `${Number(data.payments.overdue_count ?? 0)} รายการ`;

            if (repairCount) {
                const repairs = data.repairs ?? {};
                const totalRepairs =
                    Number(repairs.REPORTED ?? 0) +
                    Number(repairs.IN_PROGRESS ?? 0);

                repairCount.textContent = totalRepairs;
            }
        } catch (error) {
            console.error('Dashboard API error:', error);
            [
                'dashboard-current-due-amount',
                'dashboard-overdue-amount',
            ].forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.textContent = 'โหลดไม่ได้';
                    element.classList.add('is-error');
                }
            });
            [
                'dashboard-current-due-count',
                'dashboard-overdue-count',
            ].forEach(id => {
                const element = document.getElementById(id);
                if (element) element.textContent = 'ลองใหม่ภายหลัง';
            });
        }

        const escapeHtml = value => {
            const node = document.createElement('span');
            node.textContent = value ?? '';
            return node.innerHTML;
        };
        const statusLabel = status => ({
            UNPAID: 'ยังไม่ชำระ',
            PENDING: 'รอตรวจสอบ',
            REJECTED: 'ถูกปฏิเสธ',
            PAID: 'ชำระแล้ว',
            REPORTED: 'แจ้งใหม่',
            IN_PROGRESS: 'กำลังดำเนินการ',
            COMPLETED: 'เสร็จแล้ว',
        })[status] ?? status ?? '—';
        const apiGet = async path => {
            const response = await fetch(path, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            const body = await response.json().catch(() => ({}));
            if (response.status === 401) {
                window.location.assign('/login');
                throw new Error('กรุณาเข้าสู่ระบบใหม่');
            }
            if (!response.ok) throw new Error(body.message || 'โหลดข้อมูลไม่สำเร็จ');
            return body;
        };

        const invoiceRows = document.getElementById('dashboard-invoice-rows');
        try {
            const statusResults = await Promise.all(['UNPAID', 'PENDING', 'REJECTED'].map(status => {
                const params = new URLSearchParams({ status, per_page: 5 });
                return apiGet(`/api/v1/invoices?${params}`);
            }));
            const invoices = statusResults.flatMap(result => result.data ?? [])
                .sort((left, right) => String(left.i_due ?? '').localeCompare(String(right.i_due ?? '')))
                .slice(0, 5);
            invoiceRows.innerHTML = invoices.length ? invoices.map(invoice => {
                const tenant = `${invoice.tenant?.t_Fname ?? ''} ${invoice.tenant?.t_Lname ?? ''}`.trim() || '—';
                const status = invoice.payment?.p_status ?? 'UNPAID';
                return `<tr><td>${escapeHtml(invoice.room?.r_name ?? '—')}</td><td>${escapeHtml(tenant)}</td><td>${escapeHtml(invoice.period_start ?? invoice.i_date ?? '—')}</td><td class="amount">฿${Number(invoice.i_total ?? 0).toLocaleString('th-TH', { minimumFractionDigits: 2 })}</td><td><span class="status-badge ${status === 'REJECTED' ? 'status-overdue' : 'status-waiting'}">${escapeHtml(statusLabel(status))}</span></td></tr>`;
            }).join('') : '<tr><td colspan="5" class="empty-table">ยังไม่มีรายการค้างชำระ</td></tr>';
        } catch (error) {
            invoiceRows.innerHTML = `<tr><td colspan="5" class="empty-table text-danger">${escapeHtml(error.message || 'โหลดรายการไม่สำเร็จ')} <button type="button" class="btn btn-sm btn-outline-primary ms-2" data-dashboard-retry="invoices">ลองใหม่</button></td></tr>`;
        }

        const repairRows = document.getElementById('dashboard-repair-rows');
        try {
            const result = await apiGet('/api/v1/repairs?per_page=5');
            const repairs = result.data ?? [];
            repairRows.innerHTML = repairs.length ? repairs.map(repair => `<tr><td>${escapeHtml(repair.rp_id ?? '—')}</td><td>${escapeHtml(repair.room?.r_name ?? repair.rooms_r_id ?? 'ส่วนกลาง')}</td><td>${escapeHtml(repair.rp_name ?? 'แจ้งซ่อม')}</td><td>${escapeHtml((repair.created_at ?? '—').toString().slice(0, 10))}</td><td><span class="status-badge status-repair">${escapeHtml(statusLabel(repair.rp_status))}</span></td></tr>`).join('') : '<tr><td colspan="5" class="empty-table">ยังไม่มีข้อมูลแจ้งซ่อม</td></tr>';
        } catch (error) {
            repairRows.innerHTML = `<tr><td colspan="5" class="empty-table text-danger">${escapeHtml(error.message || 'โหลดรายการไม่สำเร็จ')} <button type="button" class="btn btn-sm btn-outline-primary ms-2" data-dashboard-retry="repairs">ลองใหม่</button></td></tr>`;
        }

        document.addEventListener('click', event => {
            const retry = event.target.closest('[data-dashboard-retry]');
            if (!retry) return;
            window.location.reload();
        });
    });
    </script>

</x-layouts::app.sidebar>
