<x-layouts::app :title="'มิเตอร์และการใช้งาน'">
    @include('maintenance.style')
    <main class="maintenance" data-maintenance="meters">
        <h1>มิเตอร์และการใช้งาน</h1>
        <p class="muted">บันทึกเลขน้ำและไฟได้วันละหนึ่งรายการต่อห้อง รวมถึงก่อนรับผู้เช่าเข้าพัก แก้ตัวเลขได้ทั้งต้นและปลายช่วงก่อนใช้ในบิล รายการยกเลิกยังอยู่ในประวัติแต่ใช้คำนวณไม่ได้ ต้องมีค่าตรงวันเริ่มและวันสิ้นสุดก่อนออกบิล</p>
        <p role="alert" id="work-error"></p><p role="status" id="work-status" aria-live="polite"></p>
        <label>ห้อง<select id="meter-room"><option value="">เลือกห้อง</option>@foreach($rooms as $room)<option value="{{ $room->r_id }}">{{ $room->r_name }}</option>@endforeach</select></label>
        <section><h2>บันทึกมิเตอร์</h2>
            <form id="meter-form"><div class="grid-fields">
                <label>วันที่<input name="m_date" type="date" required value="{{ now('Asia/Bangkok')->toDateString() }}"></label>
                <label>เลขมิเตอร์น้ำ<input name="m_water" type="number" min="0" max="99999999.99" step="0.01" required></label>
                <label>เลขมิเตอร์ไฟ<input name="m_elec" type="number" min="0" max="99999999.99" step="0.01" required></label>
            </div><button type="submit">บันทึก</button></form>
        </section>
        <section id="meter-edit-section" hidden><h2>แก้ไขมิเตอร์ที่กรอกผิด</h2>
            <p id="meter-edit-summary"></p>
            <form id="meter-edit-form"><div class="grid-fields">
                <label>วันที่<input id="meter-edit-date" type="date" required></label>
                <label>เลขมิเตอร์น้ำ<input id="meter-edit-water" type="number" min="0" max="99999999.99" step="0.01" required></label>
                <label>เลขมิเตอร์ไฟ<input id="meter-edit-elec" type="number" min="0" max="99999999.99" step="0.01" required></label>
                <label>เหตุผล<textarea id="meter-edit-reason" required maxlength="2000"></textarea></label>
            </div><button type="submit">ตรวจและยืนยันแก้ไข</button><button id="meter-edit-cancel" type="button">ยกเลิก</button></form>
        </section>
        <section><h2>ประวัติมิเตอร์</h2><div class="scroll"><table><thead><tr><th>วันที่</th><th>น้ำ</th><th>ไฟ</th><th>เลือกช่วง / จัดการ</th></tr></thead><tbody id="meter-rows"></tbody></table></div>
            <button id="previous-page">ก่อนหน้า</button><span id="page-label"></span><button id="next-page">ถัดไป</button><button id="reload">โหลดใหม่</button>
        </section>
        <section><h2>หน่วยที่ใช้ระหว่างสองรายการ</h2><p id="usage-selection">เลือกต้นช่วงและปลายช่วงจากประวัติมิเตอร์</p><button id="calculate-usage">คำนวณหน่วย</button><p id="usage-result" aria-live="polite"></p><p class="muted">หน่วยแสดงจากระบบกลาง ยอดที่ต้องชำระให้ยึดใบแจ้งหนี้</p></section>
    </main>
</x-layouts::app>
