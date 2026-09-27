<x-layouts::app.sidebar :title="'Dashboard'">

    <div class="container-fluid py-4">

        <div class="mb-4">
            <h1 class="fw-bold mb-1">
                สวัสดี, {{ auth()->user()->u_username }}
            </h1>

            <p class="text-secondary mb-0">
                ยินดีต้อนรับเข้าสู่ระบบหอพักสุขสบาย
            </p>
        </div>

        <div class="row g-4">

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

                        <h3 class="fw-bold mb-0">
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

                        <h3 class="fw-bold mb-0">
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

                        <h3 class="fw-bold mb-0">
                            -
                        </h3>

                    </div>
                </div>
            </div>

        </div>

    </div>

</x-layouts::app.sidebar>