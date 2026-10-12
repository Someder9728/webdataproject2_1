<x-layouts::app.sidebar :title="'แจ้งซ่อม'">
    @include('maintenance.style')
    <div class="tenant-page" data-maintenance="repairs" data-admin="{{ $isAdmin ? '1' : '0' }}">
        <h1 class="tenant-title">แจ้งซ่อม</h1><p class="tenant-subtitle mb-4">ติดตามงานซ่อมห้องพักและพื้นที่ส่วนกลาง</p>
        <p role="alert" class="text-danger small text-break" id="work-error"></p><p role="status" class="text-secondary small" id="work-status" aria-live="polite"></p>
        @if($isAdmin || $rooms->isNotEmpty())
        <section class="tenant-search-card p-4"><h2 class="h6 fw-semibold mb-3">แจ้งงานใหม่</h2><form id="repair-form">
            <label class="form-label d-block mb-3">หัวข้องาน<input class="form-control mt-2" name="rp_name" maxlength="255" required></label>
            <div class="d-flex flex-wrap gap-3">
                <label class="form-label d-block mb-3">ประเภท<select class="form-select mt-2" name="rp_type" id="repair-type">
                    <option value="ROOM">ห้องพัก</option><option value="COMMON">ส่วนกลาง</option></select></label>
                <label class="form-label d-block mb-3" id="repair-room-label">ห้อง
                <select class="form-select mt-2"
                        name="rooms_r_id"
                        id="repair-room"
                        required>
                    <option value="">เลือกห้อง</option>

                    @foreach($rooms as $room)
                        <option value="{{ $room->r_id }}"
                                @selected(!$isAdmin && $rooms->count() === 1)>
                            {{ $room->r_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <label class="form-label d-block mb-3">รายละเอียดและจุดที่ต้องซ่อม<textarea class="form-control mt-2" name="rp_description" rows="3" maxlength="10000"></textarea></label><button class="btn tenant-add-btn" type="submit">ส่งแจ้งซ่อม</button>
        </form></section>
        @else <p>การแจ้งงานใหม่ต้องมีสัญญาเช่าที่กำลังใช้งาน คุณยังดูประวัติงานเดิมได้</p> @endif
        <section class="tenant-search-card p-4"><h2 class="h6 fw-semibold mb-3">รายการงานซ่อม</h2><label class="form-label d-block mb-3">สถานะ<select class="form-select mt-2" id="repair-filter"><option value="">ทั้งหมด</option><option value="REPORTED" @selected(request('rp_status') === 'REPORTED')>แจ้งแล้ว</option><option value="IN_PROGRESS" @selected(request('rp_status') === 'IN_PROGRESS')>กำลังซ่อม</option><option value="COMPLETED" @selected(request('rp_status') === 'COMPLETED')>เสร็จสิ้น</option></select></label>
            <div class="table-responsive"><table class="table tenant-table align-middle mb-0"><thead>
                    <tr>
                        <th>หัวข้อ</th>
                        <th>ประเภท</th>
                        <th>ห้อง / พื้นที่</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody id="repair-rows">@forelse($repairs as $repair)<tr><td>{{ $repair->rp_name }}</td><td>{{ $repair->rp_type === 'ROOM' ? 'ห้องพัก' : 'ส่วนกลาง' }}</td><td>{{ $repair->room?->r_name ?? 'พื้นที่ส่วนกลาง' }}</td><td><span class="badge {{ ['REPORTED' => 'text-bg-warning', 'IN_PROGRESS' => 'text-bg-primary', 'COMPLETED' => 'text-bg-success'][$repair->rp_status] }}">{{ ['REPORTED' => 'แจ้งแล้ว', 'IN_PROGRESS' => 'กำลังซ่อม', 'COMPLETED' => 'เสร็จสิ้น'][$repair->rp_status] }}</span></td><td><button class="btn btn-sm tenant-edit-btn" type="button" data-repair-detail="{{ $repair->rp_id }}">ประวัติ</button>@if($isAdmin && $repair->rp_status !== 'COMPLETED')<button class="btn btn-sm tenant-edit-btn" type="button" data-repair-next="{{ $repair->rp_id }}" data-status="{{ $repair->rp_status }}">{{ $repair->rp_status === 'REPORTED' ? 'กำลังซ่อม' : 'เสร็จสิ้น' }}</button>@endif</td></tr>@empty<tr><td colspan="5" class="tenant-state text-center">ยังไม่มีรายการ</td></tr>@endforelse</tbody></table></div>
            <div class="mt-3">{{ $repairs->links() }}</div><button class="btn tenant-clear-btn" id="reload" type="button">โหลดใหม่</button>
        </section>
        <section class="tenant-search-card p-4" id="repair-detail" hidden><h2 class="h6 fw-semibold mb-3">รายละเอียดและประวัติ</h2><p id="repair-description" class="text-break"></p><ol id="repair-history"></ol></section>
    </div>
</x-layouts::app.sidebar>
