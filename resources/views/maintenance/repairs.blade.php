<x-layouts::app :title="'แจ้งซ่อม'">
    @include('maintenance.style')
    <main class="maintenance" data-maintenance="repairs" data-admin="{{ $isAdmin ? '1' : '0' }}">
        <h1>แจ้งซ่อม</h1><p class="muted">ติดตามงานซ่อมห้องพักและพื้นที่ส่วนกลาง</p>
        <p role="alert" id="work-error"></p><p role="status" id="work-status" aria-live="polite"></p>
        @if($isAdmin || $rooms->isNotEmpty())
        <section><h2>แจ้งงานใหม่</h2><form id="repair-form">
            <label>หัวข้องาน<input name="rp_name" maxlength="255" required></label>
            <div class="grid-fields"><label>ประเภท<select name="rp_type" id="repair-type"><option value="ROOM">ห้องพัก</option><option value="COMMON">ส่วนกลาง</option></select></label>
            <label id="repair-room-label">ห้อง<select name="rooms_r_id" id="repair-room" required><option value="">เลือกห้อง</option>@foreach($rooms as $room)<option value="{{ $room->r_id }}">{{ $room->r_name }}</option>@endforeach</select></label></div>
            <label>รายละเอียดและจุดที่ต้องซ่อม<textarea name="rp_description" rows="3" maxlength="10000"></textarea></label><button type="submit">ส่งแจ้งซ่อม</button>
        </form></section>
        @else <p>การแจ้งงานใหม่ต้องมีสัญญาเช่าที่กำลังใช้งาน คุณยังดูประวัติงานเดิมได้</p> @endif
        <section><h2>รายการงานซ่อม</h2><label>สถานะ<select id="repair-filter"><option value="">ทั้งหมด</option><option value="REPORTED">แจ้งแล้ว</option><option value="IN_PROGRESS">กำลังซ่อม</option><option value="COMPLETED">เสร็จสิ้น</option></select></label>
            <div class="scroll"><table><thead><tr><th>หัวข้อ</th><th>ประเภท</th><th>สถานะ</th><th>จัดการ</th></tr></thead><tbody id="repair-rows"></tbody></table></div>
            <button id="previous-page">ก่อนหน้า</button><span id="page-label"></span><button id="next-page">ถัดไป</button><button id="reload">โหลดใหม่</button>
        </section><section id="repair-detail" hidden><h2>รายละเอียดและประวัติ</h2><p id="repair-description" style="white-space:pre-wrap"></p><ol id="repair-history"></ol></section>
    </main>
</x-layouts::app>
