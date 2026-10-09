<x-layouts::app.sidebar :title="'Dashboard'">

    <div class="container-fluid py-4">

        {{-- HEADER --}}
        <div class="mb-4">
            <h1 class="fw-bold mb-1">
                สวัสดี, {{ auth()->user()->u_username }}
            </h1>

            <p class="text-secondary mb-0">
                ยินดีต้อนรับเข้าสู่ระบบหอพักสุขสบาย
            </p>
        </div>


        {{-- SUMMARY --}}
        <div class="row g-4 mb-4">

            {{-- ห้องพักของฉัน --}}
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">

                        <div class="d-flex align-items-center mb-3">

                            <div class="rounded-3 p-3 me-3" style="background:#EAF2FB; color:#162D4A;">
                                <i class="bi bi-door-open fs-4"></i>
                            </div>

                            <div>
                                <h6 class="mb-1 fw-bold">
                                    ห้องพักของฉัน
                                </h6>

                                <small class="text-secondary">
                                    ข้อมูลห้องพัก
                                </small>
                            </div>

                        </div>

                        <h3 class="fw-bold mb-0" id="user-room">
                            -
                        </h3>

                    </div>
                </div>
            </div>


            {{-- ค่าเช่า --}}
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">

                        <div class="d-flex align-items-center mb-3">

                            <div class="rounded-3 p-3 me-3" style="background:#EAF2FB; color:#162D4A;">
                                <i class="bi bi-cash-stack fs-4"></i>
                            </div>

                            <div>
                                <h6 class="mb-1 fw-bold">
                                    ค่าเช่า
                                </h6>

                                <small class="text-secondary">
                                    ยอดที่ต้องชำระ
                                </small>
                            </div>

                        </div>

                        <h3 class="fw-bold mb-0" id="user-outstanding">
                            -
                        </h3>

                    </div>
                </div>
            </div>


            {{-- แจ้งซ่อม --}}
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">

                        <div class="d-flex align-items-center mb-3">

                            <div class="rounded-3 p-3 me-3" style="background:#EAF2FB; color:#162D4A;">
                                <i class="bi bi-tools fs-4"></i>
                            </div>

                            <div>
                                <h6 class="mb-1 fw-bold">
                                    แจ้งซ่อม
                                </h6>

                                <small class="text-secondary">
                                    รายการแจ้งซ่อมของฉัน
                                </small>
                            </div>

                        </div>

                        <h3 class="fw-bold mb-0" id="user-repair-count">
                            -
                        </h3>

                    </div>
                </div>
            </div>

        </div>


        {{-- ประวัติการเช่า --}}

        <div class="card border-0 shadow-sm mb-4">

            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-1">
                    ประวัติการเช่า
                </h5>

                <small class="text-secondary">
                    ประวัติ Rental ของผู้เช่า
                </small>
            </div>

            <div class="table-responsive">

                <table class="table mb-0 align-middle">

                    <thead>
                        <tr>
                            <th>ห้อง</th>
                            <th>ผู้เช่า</th>
                            <th>วันที่เข้าพัก</th>
                            <th>วันที่ย้ายออก</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>

                    <tbody id="rental-history">

                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">
                                กำลังโหลดข้อมูล...
                            </td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </div>


        {{-- ประวัติใบแจ้งหนี้ --}}

        <div class="card border-0 shadow-sm mb-4">

            <div class="card-header bg-white py-3">

                <h5 class="fw-bold mb-1">
                    ประวัติใบแจ้งหนี้
                </h5>

                <small class="text-secondary">
                    ประวัติ Invoice ของผู้เช่า
                </small>

            </div>

            <div class="table-responsive">

                <table class="table mb-0 align-middle">

                    <thead>
                        <tr>
                            <th>ห้อง</th>
                            <th>วันที่ออก</th>
                            <th>วันครบกำหนด</th>
                            <th>ยอดรวม</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>

                    <tbody id="invoice-history">

                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">
                                กำลังโหลดข้อมูล...
                            </td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </div>


        {{-- ประวัติแจ้งซ่อม --}}

        <div class="card border-0 shadow-sm mb-4">

            <div class="card-header bg-white py-3">

                <h5 class="fw-bold mb-1">
                    ประวัติแจ้งซ่อม
                </h5>

                <small class="text-secondary">
                    ประวัติ Repair ของผู้เช่า
                </small>

            </div>

            <div class="table-responsive">

                <table class="table mb-0 align-middle">

                    <thead>
                        <tr>
                            <th>รหัส</th>
                            <th>หัวข้อ</th>
                            <th>สถานะ</th>
                            <th>วันที่แจ้ง</th>
                        </tr>
                    </thead>

                    <tbody id="repair-history">

                        <tr>
                            <td colspan="4" class="text-center text-secondary py-4">
                                กำลังโหลดข้อมูล...
                            </td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    {{-- USER DASHBOARD API --}}

    <script>
    document.addEventListener('DOMContentLoaded', async () => {

        const rentalBody = document.getElementById('rental-history');
        const invoiceBody = document.getElementById('invoice-history');
        const repairBody = document.getElementById('repair-history');

        const roomElement = document.getElementById('user-room');
        const outstandingElement =
            document.getElementById('user-outstanding');

        const repairCountElement =
            document.getElementById('user-repair-count');

        const escapeHtml = value => {
            const node = document.createElement('span');
            node.textContent = value ?? '';
            return node.innerHTML;
        };

        const statusLabel = (status, type) => {
            const labels = {
                rental: { ACTIVE: 'กำลังเช่า', ENDED: 'สิ้นสุดแล้ว' },
                invoice: { UNPAID: 'ยังไม่ชำระ', PENDING: 'รอตรวจสอบ', PAID: 'ชำระแล้ว', REJECTED: 'ถูกปฏิเสธ' },
                repair: { REPORTED: 'แจ้งใหม่', IN_PROGRESS: 'กำลังดำเนินการ', COMPLETED: 'เสร็จแล้ว' },
            };
            return labels[type]?.[status] ?? status ?? '—';
        };

        const fetchAll = async endpoint => {
            const items = [];
            let page = 1;
            let lastPage = 1;
            do {
                const params = new URLSearchParams({ page, per_page: 100 });
                const response = await fetch(`${endpoint}?${params}`, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                });
                const result = await response.json().catch(() => ({}));
                if (response.status === 401) {
                    window.location.assign('/login');
                    throw new Error('กรุณาเข้าสู่ระบบใหม่');
                }
                if (!response.ok) throw new Error(result.message || 'โหลดข้อมูลไม่สำเร็จ');
                items.push(...(result.data ?? []));
                lastPage = Number(result.meta?.last_page ?? 1);
                page++;
            } while (page <= lastPage);
            return items;
        };


        /* RENTAL */

        try {

            const rentals = await fetchAll('/api/v1/rentals');

            if (rentals.length === 0) {

                rentalBody.innerHTML = `
                        <tr>
                            <td colspan="5"
                                class="text-center text-secondary py-4">
                                ไม่พบประวัติการเช่า
                            </td>
                        </tr>
                    `;

            } else {

                rentalBody.innerHTML = rentals.map(rental => {

                    const room =
                        rental.room?.r_name ?? '-';

                    const tenantName =
                        `${rental.tenant?.t_Fname ?? ''} ${rental.tenant?.t_Lname ?? ''}`.trim() ||
                        '-';

                    const moveIn =
                        rental.rt_movein ?? '-';

                    const moveOut =
                        rental.rt_moveout ?? '-';

                    const status =
                        rental.rt_status ?? '-';

                    return `
                            <tr>
                        <td>${escapeHtml(room)}</td>
                                <td>${escapeHtml(tenantName)}</td>
                                <td>${escapeHtml(moveIn)}</td>
                                <td>${escapeHtml(moveOut)}</td>
                                <td>${escapeHtml(statusLabel(status, 'rental'))}</td>
                            </tr>
                        `;

                }).join('');


                /* ห้องปัจจุบัน */

                const activeRental = rentals.find(
                    rental => rental.rt_status === 'ACTIVE'
                );

                if (activeRental && roomElement) {

                    roomElement.textContent =
                        activeRental.room?.r_name ?? '-';

                }

            }

        } catch (error) {

            console.error('Rental API error:', error);

            rentalBody.innerHTML = `
                    <tr>
                        <td colspan="5"
                            class="text-center text-danger py-4">
                            ไม่สามารถโหลดประวัติการเช่าได้
                            <button type="button" class="btn btn-sm btn-outline-primary ms-2" data-user-dashboard-retry>ลองใหม่</button>
                        </td>
                    </tr>
                `;

        }


        /* INVOICE */

        try {

            const invoices = await fetchAll('/api/v1/invoices');

            if (invoices.length === 0) {

                invoiceBody.innerHTML = `
                        <tr>
                            <td colspan="5"
                                class="text-center text-secondary py-4">
                                ไม่พบประวัติใบแจ้งหนี้
                            </td>
                        </tr>
                    `;

            } else {

                invoiceBody.innerHTML = invoices.map(invoice => {

                    const room =
                        invoice.room?.r_name ?? '-';

                    const date =
                        invoice.i_date ?? '-';

                    const due =
                        invoice.i_due ?? '-';

                    const total =
                        Number(invoice.i_total ?? 0)
                        .toLocaleString('th-TH', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });

                    const status =
                        invoice.payment?.p_status ?? 'UNPAID';

                    if (
                        outstandingElement && ['UNPAID', 'PENDING', 'REJECTED']
                        .includes(status)
                    ) {

                        const current =
                            Number(
                                outstandingElement.dataset.total ?? 0
                            );

                        outstandingElement.dataset.total =
                            current + Number(invoice.i_total ?? 0);

                        outstandingElement.textContent =
                            `฿${(
                                    current + Number(invoice.i_total ?? 0)
                                ).toLocaleString('th-TH', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                })}`;

                    }

                    return `
                            <tr>
                                <td>${escapeHtml(room)}</td>
                                <td>${escapeHtml(date)}</td>
                                <td>${escapeHtml(due)}</td>
                                <td>฿${total}</td>
                                <td>${escapeHtml(statusLabel(status, 'invoice'))}</td>
                            </tr>
                        `;

                }).join('');

            }

        } catch (error) {

            console.error('Invoice API error:', error);

            invoiceBody.innerHTML = `
                    <tr>
                        <td colspan="5"
                            class="text-center text-danger py-4">
                            ไม่สามารถโหลดประวัติใบแจ้งหนี้ได้
                            <button type="button" class="btn btn-sm btn-outline-primary ms-2" data-user-dashboard-retry>ลองใหม่</button>
                        </td>
                    </tr>
                `;

        }


        /* REPAIR */

        try {

            const repairs = await fetchAll('/api/v1/repairs');

            if (repairCountElement) {
                repairCountElement.textContent = repairs.length;
            }

            if (repairs.length === 0) {

                repairBody.innerHTML = `
                        <tr>
                            <td colspan="4"
                                class="text-center text-secondary py-4">
                                ไม่พบประวัติแจ้งซ่อม
                            </td>
                        </tr>
                    `;

            } else {

                repairBody.innerHTML = repairs.map(repair => {

                    return `
                            <tr>
                                <td>${escapeHtml(repair.rp_id ?? '—')}</td>
                                <td>${escapeHtml(repair.rp_name ?? repair.rp_type ?? '—')}</td>
                                <td>${escapeHtml(statusLabel(repair.rp_status, 'repair'))}</td>
                                <td>${escapeHtml(repair.created_at ?? '—')}</td>
                            </tr>
                        `;

                }).join('');

            }

        } catch (error) {

            console.error('Repair API error:', error);

            repairBody.innerHTML = `
                    <tr>
                        <td colspan="4"
                            class="text-center text-danger py-4">
                            ไม่สามารถโหลดประวัติแจ้งซ่อมได้
                            <button type="button" class="btn btn-sm btn-outline-primary ms-2" data-user-dashboard-retry>ลองใหม่</button>
                        </td>
                    </tr>
                `;

        }

        document.addEventListener('click', event => {
            if (event.target.closest('[data-user-dashboard-retry]')) {
                window.location.reload();
            }
        });
    });
    </script>

</x-layouts::app.sidebar>
