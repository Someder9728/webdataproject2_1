<x-layouts::app.sidebar :title="'มิเตอร์และการใช้งาน'">
    @include('maintenance.style')
    <div class="tenant-page" data-maintenance="meters">
        <h1 class="tenant-title">มิเตอร์และการใช้งาน</h1>
        <p class="tenant-subtitle mb-4">บันทึกเลขน้ำและไฟได้วันละหนึ่งรายการต่อห้อง รวมถึงก่อนรับผู้เช่าเข้าพัก แก้ตัวเลขได้ทั้งต้นและปลายช่วงก่อนใช้ในบิล รายการยกเลิกยังอยู่ในประวัติแต่ใช้คำนวณไม่ได้ ต้องมีค่าตรงวันเริ่มและวันสิ้นสุดก่อนออกบิล</p>
        <p role="alert" class="text-danger small text-break" id="work-error"></p><p role="status" class="text-secondary small" id="work-status" aria-live="polite"></p>
        <label class="form-label d-block mb-3">ห้อง<select class="form-select mt-2" id="meter-room"><option value="">เลือกห้อง</option>@foreach($rooms as $room)<option value="{{ $room->r_id }}">{{ $room->r_name }}</option>@endforeach</select></label>
        <section class="tenant-search-card p-4"><h2 class="h6 fw-semibold mb-3">บันทึกมิเตอร์</h2>
            <form id="meter-form"><div class="d-flex flex-wrap gap-3">
                <label class="form-label d-block mb-3">วันที่<input class="form-control mt-2" name="m_date" type="date" required value="{{ now('Asia/Bangkok')->toDateString() }}"></label>
                <label class="form-label d-block mb-3">เลขมิเตอร์น้ำ<input class="form-control mt-2" name="m_water" type="number" min="0" max="99999999.99" step="0.01" required></label>
                <label class="form-label d-block mb-3">เลขมิเตอร์ไฟ<input class="form-control mt-2" name="m_elec" type="number" min="0" max="99999999.99" step="0.01" required></label>
            </div><button class="btn tenant-add-btn" type="submit">บันทึก</button></form>
        </section>
        <section class="tenant-search-card p-4" id="meter-edit-section" hidden><h2 class="h6 fw-semibold mb-3">แก้ไขมิเตอร์ที่กรอกผิด</h2>
            <p id="meter-edit-summary"></p>
            <form id="meter-edit-form"><div class="d-flex flex-wrap gap-3">
                <label class="form-label d-block mb-3">วันที่<input class="form-control mt-2" id="meter-edit-date" type="date" required></label>
                <label class="form-label d-block mb-3">เลขมิเตอร์น้ำ<input class="form-control mt-2" id="meter-edit-water" type="number" min="0" max="99999999.99" step="0.01" required></label>
                <label class="form-label d-block mb-3">เลขมิเตอร์ไฟ<input class="form-control mt-2" id="meter-edit-elec" type="number" min="0" max="99999999.99" step="0.01" required></label>
                <label class="form-label d-block mb-3">เหตุผล<textarea class="form-control mt-2" id="meter-edit-reason" required maxlength="2000"></textarea></label>
            </div><button class="btn tenant-add-btn" type="submit">ตรวจและยืนยันแก้ไข</button><button class="btn tenant-clear-btn me-2 mt-2" id="meter-edit-cancel" type="button">ยกเลิก</button></form>
        </section>
        <section class="tenant-search-card p-4"><h2 class="h6 fw-semibold mb-3">ประวัติมิเตอร์</h2><div class="table-responsive"><table class="table tenant-table align-middle mb-0"><thead><tr><th>วันที่</th><th>น้ำ</th><th>ไฟ</th><th>เลือกช่วง / จัดการ</th></tr></thead><tbody id="meter-rows"></tbody></table></div>
            <button class="btn tenant-clear-btn me-2 mt-2" id="previous-page">ก่อนหน้า</button><span class="small text-secondary me-2" id="page-label"></span><button class="btn tenant-clear-btn me-2 mt-2" id="next-page">ถัดไป</button><button class="btn tenant-clear-btn me-2 mt-2" id="reload">โหลดใหม่</button>
        </section>
        <section class="tenant-search-card p-4"><h2 class="h6 fw-semibold mb-3">หน่วยที่ใช้ระหว่างสองรายการ</h2><p id="usage-selection">เลือกต้นช่วงและปลายช่วงจากประวัติมิเตอร์</p><button class="btn tenant-clear-btn me-2 mt-2" id="calculate-usage">คำนวณหน่วย</button><p id="usage-result" aria-live="polite"></p><p class="tenant-subtitle mb-4">หน่วยแสดงจากระบบกลาง ยอดที่ต้องชำระให้ยึดใบแจ้งหนี้</p></section>
    </div>
</x-layouts::app.sidebar>
