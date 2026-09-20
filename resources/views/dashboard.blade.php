<x-layouts::app :title="__('Dashboard')">

    <div class="dashboard-page">

        {{-- SUMMARY CARDS --}}
        <div class="row g-4 mb-4">

            {{-- ห้องทั้งหมด --}}
            <div class="col-12 col-md-6 col-xl-4">
                <div class="dashboard-card h-100">
                    <div class="dashboard-icon bg-dark-blue">
                        <i class="bi bi-building"></i>
                    </div>

                    <div>
                        <div class="dashboard-label">ห้องทั้งหมด</div>
                        <div class="dashboard-number">12</div>
                    </div>
                </div>
            </div>

            {{-- ห้องว่าง --}}
            <div class="col-12 col-md-6 col-xl-4">
                <div class="dashboard-card h-100">
                    <div class="dashboard-icon bg-green">
                        <i class="bi bi-check-circle"></i>
                    </div>

                    <div>
                        <div class="dashboard-label">ห้องว่าง</div>
                        <div class="dashboard-number">4</div>
                    </div>
                </div>
            </div>

            {{-- ผู้เช่า --}}
            <div class="col-12 col-md-6 col-xl-4">
                <div class="dashboard-card h-100">
                    <div class="dashboard-icon bg-blue">
                        <i class="bi bi-person"></i>
                    </div>

                    <div>
                        <div class="dashboard-label">ผู้เช่า</div>
                        <div class="dashboard-number">8</div>
                    </div>
                </div>
            </div>

            {{-- ผู้เช่าปัจจุบัน --}}
            <div class="col-12 col-md-6 col-xl-4">
                <div class="dashboard-card h-100">
                    <div class="dashboard-icon bg-purple">
                        <i class="bi bi-people"></i>
                    </div>

                    <div>
                        <div class="dashboard-label">ผู้เช่าปัจจุบัน</div>
                        <div class="dashboard-number">8</div>
                        <div class="dashboard-unit">คน</div>
                    </div>
                </div>
            </div>

            {{-- ยอดค้างชำระ --}}
            <div class="col-12 col-md-6 col-xl-4">
                <div class="dashboard-card h-100">
                    <div class="dashboard-icon bg-red">
                        <i class="bi bi-currency-dollar"></i>
                    </div>

                    <div>
                        <div class="dashboard-label">ยอดค้างชำระ</div>
                        <div class="dashboard-number">฿15,747</div>
                        <div class="dashboard-unit">2 รายการ</div>
                    </div>
                </div>
            </div>

            {{-- แจ้งซ่อมค้าง --}}
            <div class="col-12 col-md-6 col-xl-4">
                <div class="dashboard-card h-100">
                    <div class="dashboard-icon bg-orange">
                        <i class="bi bi-tools"></i>
                    </div>

                    <div>
                        <div class="dashboard-label">แจ้งซ่อมค้าง</div>
                        <div class="dashboard-number">3</div>
                        <div class="dashboard-unit">รายการ</div>
                    </div>
                </div>
            </div>

        </div>

        {{-- สถานะห้องพัก --}}
        <div class="dashboard-section mb-4">

            <div class="section-header">
                <h2>สถานะห้องพัก</h2>
                <p>ภาพรวมห้องพักทั้งหมด</p>
            </div>

            {{-- ชั้น 1 --}}
            <div class="floor-section">

                <div class="floor-title">
                    ชั้น 1
                </div>

                <div class="row g-3">

                    {{-- ห้อง 101 --}}
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="room-card occupied">

                            <div class="room-top">
                                <strong>ห้อง 101</strong>
                                <span class="room-dot"></span>
                            </div>

                            <div class="room-status">
                                มีผู้เช่า
                            </div>

                            <div class="room-tenant">
                                สมชาย ใจดี
                            </div>

                            <div class="room-price">
                                ฿3,500/เดือน
                            </div>

                        </div>
                    </div>

                    {{-- ห้อง 102 --}}
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="room-card occupied">

                            <div class="room-top">
                                <strong>ห้อง 102</strong>
                                <span class="room-dot"></span>
                            </div>

                            <div class="room-status">
                                มีผู้เช่า
                            </div>

                            <div class="room-tenant">
                                สุดา รักดี
                            </div>

                            <div class="room-price">
                                ฿3,500/เดือน
                            </div>

                        </div>
                    </div>

                    {{-- ห้อง 103 --}}
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="room-card occupied">

                            <div class="room-top">
                                <strong>ห้อง 103</strong>
                                <span class="room-dot"></span>
                            </div>

                            <div class="room-status">
                                มีผู้เช่า
                            </div>

                            <div class="room-tenant">
                                ปานะ ชัยเจริญ
                            </div>

                            <div class="room-price">
                                ฿4,500/เดือน
                            </div>

                        </div>
                    </div>

                    {{-- ห้อง 104 --}}
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="room-card available">

                            <div class="room-top">
                                <strong>ห้อง 104</strong>
                                <span class="room-dot"></span>
                            </div>

                            <div class="room-status">
                                ว่าง
                            </div>

                            <div class="room-price">
                                ฿3,500/เดือน
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            {{-- ชั้น 2 --}}
            <div class="floor-section">

                <div class="floor-title">
                    ชั้น 2
                </div>

                <div class="row g-3">

                    {{-- ห้อง 201 --}}
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="room-card occupied">

                            <div class="room-top">
                                <strong>ห้อง 201</strong>
                                <span class="room-dot"></span>
                            </div>

                            <div class="room-status">
                                มีผู้เช่า
                            </div>

                        </div>
                    </div>

                    {{-- ห้อง 202 --}}
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="room-card occupied">

                            <div class="room-top">
                                <strong>ห้อง 202</strong>
                                <span class="room-dot"></span>
                            </div>

                            <div class="room-status">
                                มีผู้เช่า
                            </div>

                        </div>
                    </div>

                    {{-- ห้อง 203 --}}
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="room-card available">

                            <div class="room-top">
                                <strong>ห้อง 203</strong>
                                <span class="room-dot"></span>
                            </div>

                            <div class="room-status">
                                ว่าง
                            </div>

                        </div>
                    </div>

                    {{-- ห้อง 204 --}}
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="room-card occupied">

                            <div class="room-top">
                                <strong>ห้อง 204</strong>
                                <span class="room-dot"></span>
                            </div>

                            <div class="room-status">
                                มีผู้เช่า
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- รายการค้างชำระ --}}
        <div class="dashboard-section mb-4">

            <div class="section-header section-header-flex">

                <div>
                    <h2>รายการค้างชำระ</h2>
                    <p>ยังไม่ได้ชำระ</p>
                </div>

                <a href="#" class="view-all">
                    ดูทั้งหมด →
                </a>

            </div>

            <div class="table-responsive">

                <table class="table dashboard-table mb-0">

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
                            <td>103</td>
                            <td>มานะ ขยันเรียน</td>
                            <td>2024-07</td>
                            <td class="fw-semibold">฿6,572</td>
                            <td>
                                <span class="status-badge waiting">
                                    รอชำระ
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <td>201</td>
                            <td>วิไล สุขสม</td>
                            <td>2024-07</td>
                            <td class="fw-semibold">฿5,870</td>
                            <td>
                                <span class="status-badge waiting">
                                    รอชำระ
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <td>202</td>
                            <td>ประเสริฐ มีสุข</td>
                            <td>2024-07</td>
                            <td class="fw-semibold">฿7,662</td>
                            <td>
                                <span class="status-badge overdue">
                                    ค้างชำระ
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <td>204</td>
                            <td>นิกา บุญมี</td>
                            <td>2024-07</td>
                            <td class="fw-semibold">฿8,085</td>
                            <td>
                                <span class="status-badge overdue">
                                    ค้างชำระ
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <td>303</td>
                            <td>จิรา ดีงาม</td>
                            <td>2024-07</td>
                            <td class="fw-semibold">฿5,972</td>
                            <td>
                                <span class="status-badge waiting">
                                    รอชำระ
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>


        {{-- รายการแจ้งซ่อมล่าสุด --}}
        <div class="dashboard-section mb-4">

            <div class="section-header section-header-flex">

                <div>
                    <h2>รายการแจ้งซ่อมล่าสุด</h2>
                    <p>สถานะการซ่อมแซม</p>
                </div>

                <a href="#" class="view-all">
                    ดูทั้งหมด →
                </a>

            </div>

            <div class="table-responsive">

                <table class="table dashboard-table mb-0">

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
                            <td>MNT-001</td>
                            <td>101</td>
                            <td>ก๊อกน้ำรั่ว</td>
                            <td>2024-07-10</td>
                            <td>
                                <span class="status-badge completed">
                                    เสร็จแล้ว
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <td>MNT-002</td>
                            <td>103</td>
                            <td>แอร์ไม่เย็น</td>
                            <td>2024-07-13</td>
                            <td>
                                <span class="status-badge repairing">
                                    กำลังซ่อม
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <td>MNT-003</td>
                            <td></td>
                            <td>ลิฟต์เปิดปิดช้า</td>
                            <td>2024-07-15</td>
                            <td>
                                <span class="status-badge waiting">
                                    แจ้งแล้ว
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <td>MNT-004</td>
                            <td>301</td>
                            <td>ประตูล็อคไม่ได้</td>
                            <td>2024-07-08</td>
                            <td>
                                <span class="status-badge completed">
                                    เสร็จแล้ว
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <td>MNT-005</td>
                            <td></td>
                            <td>ไฟทางเดินชั้น 3 ดับ</td>
                            <td>2024-07-16</td>
                            <td>
                                <span class="status-badge repairing">
                                    กำลังซ่อม
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    {{-- DASHBOARD CSS --}}
    <style>
        /* Main */
        .dashboard-page {
            min-height: 100%;
            background: #F8FAFC;
            padding: 24px;
        }


        /* Summary Cards */
        .dashboard-card {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 24px;
            background: #ffffff;
            border: 1px solid #DDE5EF;
            border-radius: 14px;
            box-shadow: 0 2px 5px rgba(15, 23, 42, 0.04);
        }

        .dashboard-icon {
            width: 56px;
            height: 56px;
            min-width: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            color: #ffffff;
            font-size: 27px;
        }

        .bg-dark-blue {
            background: #34445C;
        }

        .bg-green {
            background: #00A875;
        }

        .bg-blue {
            background: #2161F5;
        }

        .bg-purple {
            background: #4F3BFF;
        }

        .bg-red {
            background: #F00000;
        }

        .bg-orange {
            background: #ED7800;
        }

        .dashboard-label {
            margin-bottom: 2px;
            color: #7B8CA5;
            font-size: 14px;
        }

        .dashboard-number {
            color: #172033;
            font-size: 30px;
            font-weight: 700;
            line-height: 1.2;
        }

        .dashboard-unit {
            margin-top: 3px;
            color: #9AA8BB;
            font-size: 14px;
        }

        /* Dashboard Sections */
        .dashboard-section {
            overflow: hidden;
            background: #ffffff;
            border: 1px solid #DDE5EF;
            border-radius: 14px;
            box-shadow: 0 2px 5px rgba(15, 23, 42, 0.04);
        }

        .section-header {
            padding: 20px 28px;
            border-bottom: 1px solid #E5EAF0;
        }

        .section-header-flex {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .section-header h2 {
            margin: 0;
            color: #172033;
            font-size: 18px;
            font-weight: 700;
        }

        .section-header p {
            margin: 5px 0 0;
            color: #7B8CA5;
            font-size: 14px;
        }

        .view-all {
            color: #2161F5;
            font-size: 14px;
            text-decoration: none;
            white-space: nowrap;
        }

        .view-all:hover {
            text-decoration: underline;
        }


        /* Room Status */

        .floor-section {
            padding: 0 28px 28px;
        }

        .floor-section:first-of-type {
            padding-top: 24px;
        }

        .floor-title {
            margin-bottom: 12px;
            color: #7B8CA5;
            font-size: 14px;
            font-weight: 500;
        }

        .room-card {
            height: 140px;
            padding: 16px;
            border: 2px solid;
            border-radius: 10px;
        }

        .room-card.occupied {
            background: #EFF6FF;
            border-color: #B8D5FF;
        }

        .room-card.available {
            background: #ECFDF5;
            border-color: #83E5BF;
        }

        .room-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .room-top strong {
            color: #24344D;
            font-size: 15px;
        }

        .room-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #2B7FFF;
        }

        .available .room-dot {
            background: #00B67A;
        }

        .room-status {
            margin-top: 8px;
            color: #2161F5;
            font-size: 14px;
        }

        .available .room-status {
            color: #00A875;
        }

        .room-tenant {
            margin-top: 2px;
            color: #7B8CA5;
            font-size: 14px;
        }

        .room-price {
            margin-top: 4px;
            color: #91A4BF;
            font-size: 14px;
        }


        /* Tables */
        .dashboard-table {
            width: 100%;
            margin-bottom: 0;
            color: #42526B;
            vertical-align: middle;
            table-layout: fixed;
        }

        /*
          ทุกตารางใช้ตำแหน่งคอลัมน์เดียวกัน
          Column 1 = 15%
          Column 2 = 25%
          Column 3 = 20%
          Column 4 = 20%
          Column 5 = 20%
         */

        .dashboard-table th:nth-child(1),
        .dashboard-table td:nth-child(1) {
            width: 15%;
        }

        .dashboard-table th:nth-child(2),
        .dashboard-table td:nth-child(2) {
            width: 25%;
        }

        .dashboard-table th:nth-child(3),
        .dashboard-table td:nth-child(3) {
            width: 20%;
        }

        .dashboard-table th:nth-child(4),
        .dashboard-table td:nth-child(4) {
            width: 20%;
        }

        .dashboard-table th:nth-child(5),
        .dashboard-table td:nth-child(5) {
            width: 20%;
        }

        .dashboard-table thead th {
            height: 64px;
            padding: 16px 28px;
            color: #7B8CA5;
            font-size: 14px;
            font-weight: 500;
            line-height: 1.4;
            background: #ffffff;
            border-bottom: 1px solid #E5EAF0;
            white-space: nowrap;
        }

        .dashboard-table tbody td {
            height: 72px;
            padding: 16px 28px;
            color: #42526B;
            font-size: 14px;
            line-height: 1.4;
            border-bottom: 1px solid #E5EAF0;
        }

        .dashboard-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .dashboard-table tbody tr:nth-child(even) {
            background: #F8FAFC;
        }


        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 6px 13px;
            border: 1px solid;
            border-radius: 7px;
            font-size: 12px;
            line-height: 1.3;
            white-space: nowrap;
        }

        .status-badge.waiting {
            color: #E99A00;
            background: #FFF9E8;
            border-color: #FFD35A;
        }

        .status-badge.overdue {
            color: #FF4B4B;
            background: #FFF1F1;
            border-color: #FFBABA;
        }

        .status-badge.completed {
            color: #00A875;
            background: #ECFDF5;
            border-color: #70E5B5;
        }

        .status-badge.repairing {
            color: #2161F5;
            background: #EFF6FF;
            border-color: #A9CAFF;
        }


        /* Responsive */
        @media (max-width: 991.98px) {

            .dashboard-page {
                padding: 20px;
            }

            .dashboard-table {
                min-width: 850px;
            }

        }

        @media (max-width: 575.98px) {

            .dashboard-page {
                padding: 16px;
            }

            .dashboard-card {
                padding: 18px;
            }

            .dashboard-number {
                font-size: 26px;
            }

            .section-header {
                padding: 18px 20px;
            }

            .section-header-flex {
                align-items: flex-start;
                flex-direction: column;
            }

            .floor-section {
                padding-left: 20px;
                padding-right: 20px;
            }

        }
    </style>

</x-layouts::app>
