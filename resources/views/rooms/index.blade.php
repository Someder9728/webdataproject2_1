<x-layouts::app.sidebar :title="'ห้องพัก'">

    <style>
    /* ROOMS PAGE */
    .rooms-page {
        min-height: calc(100vh - 54px);
        background: #f6f8fb;
        padding: 28px 24px 40px;
    }

    /* BREADCRUMB */
    .rooms-breadcrumb {
        margin-bottom: 24px;
        color: #90a1b9;
        font-size: 12px;
    }

    .rooms-breadcrumb .current {
        color: #172033;
        font-weight: 600;
    }

    /* PAGE HEADER */
    .rooms-header {
        margin-bottom: 24px;
    }

    .rooms-title {
        margin: 0 0 5px;
        color: #172033;
        font-size: 24px;
        font-weight: 700;
        line-height: 1.3;
    }

    .rooms-count {
        color: #718096;
        font-size: 13px;
    }

    .btn-add-room {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: 40px;
        padding: 0 17px;
        background: #183b63;
        border: 1px solid #183b63;
        border-radius: 7px;
        color: #ffffff;
        font-size: 13px;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(15, 23, 42, 0.10);
    }

    .btn-add-room:hover {
        background: #123254;
        border-color: #123254;
        color: #ffffff;
    }

    /* SUCCESS */
    .room-success {
        border: 1px solid #b7ebd0;
        border-radius: 8px;
        background: #ecfdf5;
        color: #047857;
        font-size: 13px;
    }

    /* TOOLBAR */
    .room-toolbar {
        margin-bottom: 24px;
        padding: 18px 20px;
        background: #ffffff;
        border: 1px solid #dfe6ee;
        border-radius: 11px;
        box-shadow: 0 2px 5px rgba(15, 23, 42, 0.04);
    }

    .room-search-wrapper {
        position: relative;
        width: 256px;
    }

    .room-search-icon {
        position: absolute;
        top: 50%;
        left: 13px;
        transform: translateY(-50%);
        color: #90a1b9;
        font-size: 14px;
        pointer-events: none;
    }

    .room-search {
        width: 100%;
        height: 40px;
        padding: 0 14px 0 39px;
        border: 1px solid #cbd7e5;
        border-radius: 7px;
        color: #172033;
        font-size: 13px;
        background: #ffffff;
    }

    .room-search::placeholder {
        color: #90a1b9;
    }

    .room-search:focus {
        border-color: #93bdfc;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
        outline: none;
    }

    .room-filter {
        width: 130px;
        height: 40px;
        border: 1px solid #cbd7e5;
        border-radius: 7px;
        color: #475569;
        font-size: 13px;
        background-color: #ffffff;
    }

    .room-filter:focus {
        border-color: #93bdfc;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
        outline: none;
    }

    /* VIEW SWITCH */
    .view-switch {
        display: inline-flex;
        overflow: hidden;
        border: 1px solid #cbd7e5;
        border-radius: 7px;
        background: #ffffff;
    }

    .view-switch button {
        height: 38px;
        padding: 0 15px;
        border: 0;
        background: #ffffff;
        color: #64748b;
        font-size: 13px;
        font-weight: 500;
    }

    .view-switch button+button {
        border-left: 1px solid #cbd7e5;
    }

    .view-switch button.active {
        background: #183b63;
        color: #ffffff;
    }

    /* ROOM GRID */
    .rooms-grid {
        display: grid;
        grid-template-columns:
            repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    /* ROOM CARD */

    .room-card {
        position: relative;
        min-height: 205px;
        padding: 17px;
        border: 2px solid;
        border-radius: 11px;
        transition:
            transform 0.15s ease,
            box-shadow 0.15s ease;
    }

    .room-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.06);
    }

    /* occupied */
    .room-card.occupied {
        background: #eff6ff;
        border-color: #9ac7ff;
    }

    /* available */
    .room-card.available {
        background: #ecfdf5;
        border-color: #7ee8bb;
    }

    /* maintenance */
    .room-card.maintenance {
        background: #fffbeb;
        border-color: #f5cf6d;
    }

    .room-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
    }

    .room-card-number {
        color: #172033;
        font-size: 19px;
        font-weight: 700;
        line-height: 1.25;
    }

    .room-card-type {
        margin-top: 4px;
        color: #718096;
        font-size: 11px;
    }

    .room-dot {
        width: 9px;
        height: 9px;
        margin-top: 5px;
        border-radius: 50%;
    }

    .room-dot.occupied {
        background: #3182f6;
    }

    .room-dot.available {
        background: #10b981;
    }

    .room-dot.maintenance {
        background: #f59e0b;
    }

    /* ROOM STATUS */
    .room-status-badge {
        display: inline-flex;
        align-items: center;
        margin-top: 15px;
        padding: 3px 9px;
        border: 1px solid;
        border-radius: 5px;
        font-size: 11px;
        line-height: 1.4;
    }

    .room-status-badge.occupied {
        border-color: #9ac7ff;
        background: #eff6ff;
        color: #2563eb;
    }

    .room-status-badge.available {
        border-color: #6ee7b7;
        background: #ecfdf5;
        color: #059669;
    }

    .room-status-badge.maintenance {
        border-color: #fcd34d;
        background: #fffbeb;
        color: #d97706;
    }

    /* TENANT */
    .room-tenant {
        margin-top: 11px;
        color: #64748b;
        font-size: 12px;
    }

    .room-tenant-name {
        margin-top: 3px;
        color: #475569;
        font-size: 12px;
    }

    /* PRICE */
    .room-price {
        margin-top: 8px;
        color: #172033;
        font-size: 13px;
        font-weight: 600;
    }

    .room-price span {
        color: #90a1b9;
        font-weight: 400;
    }

    /* ACTION */
    .room-actions {
        display: flex;
        gap: 6px;
        margin-top: 14px;
    }

    .room-action-btn {
        display: inline-flex;
        align-items: center;
        padding: 5px 10px;
        border: 0;
        border-radius: 5px;
        background: rgba(255, 255, 255, 0.75);
        color: #64748b;
        font-size: 11px;
        text-decoration: none;
    }

    .room-action-btn:hover {
        background: #ffffff;
        color: #2563eb;
    }

    /* EMPTY / LOADING */
    .rooms-state {
        grid-column: 1 / -1;
        padding: 60px 20px;
        border: 1px solid #dfe6ee;
        border-radius: 11px;
        background: #ffffff;
        text-align: center;
    }

    .rooms-state-icon {
        margin-bottom: 12px;
        color: #94a3b8;
        font-size: 40px;
    }

    .rooms-state-title {
        margin-bottom: 4px;
        color: #172033;
        font-size: 14px;
        font-weight: 600;
    }

    .rooms-state-text {
        color: #90a1b9;
        font-size: 12px;
    }

    /* TABLE VIEW */
    .room-table-wrapper {
        display: none;
        overflow: hidden;
        border: 1px solid #dfe6ee;
        border-radius: 11px;
        background: #ffffff;
        box-shadow: 0 2px 5px rgba(15, 23, 42, 0.04);
    }

    .room-table {
        margin: 0;
    }

    .room-table thead th {
        padding: 13px 15px;
        border-bottom: 1px solid #e5eaf0;
        background: #ffffff;
        color: #64748b;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
    }

    .room-table tbody td {
        padding: 13px 15px;
        border-bottom: 1px solid #edf1f5;
        color: #475569;
        font-size: 12px;
        vertical-align: middle;
    }

    .room-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .room-table tbody tr:hover {
        background: #f8fafc;
    }

    /* PAGINATION */
    #roomPagination {
        margin-top: 4px;
    }

    #roomPagination .pagination {
        gap: 4px;
    }

    #roomPagination .page-link {
        min-width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #dbe3ec;
        border-radius: 6px !important;
        color: #64748b;
        font-size: 12px;
        background: #ffffff;
    }

    #roomPagination .page-link:hover {
        background: #f1f5f9;
        color: #2563eb;
    }

    #roomPagination .page-item.active .page-link {
        border-color: #183b63;
        background: #183b63;
        color: #ffffff;
    }

    #roomPagination .page-item.disabled .page-link {
        color: #cbd5e1;
        background: #f8fafc;
    }
    </style>


    <div class="rooms-page">

        {{-- BREADCRUMB --}}

        <div class="rooms-breadcrumb">
            หอพักสุขสบาย
            <span class="mx-1">/</span>
            <span class="current">ห้องพัก</span>
        </div>


        {{-- PAGE HEADER --}}

        <div class="rooms-header d-flex justify-content-between align-items-end">

            <div>

                <h1 class="rooms-title">
                    ห้องพัก
                </h1>

                <div id="roomSummary" class="rooms-count">{{ $rooms->total() }} ห้อง</div>

            </div>


            <div class="d-flex align-items-center gap-2">

                {{-- VIEW SWITCH --}}

                <div class="view-switch">

                    <button type="button" id="cardViewButton" class="active">
                        การ์ด
                    </button>

                    <button type="button" id="tableViewButton">
                        ตาราง
                    </button>

                </div>


                {{-- ADD ROOM --}}

                <a href="{{ route('rooms.create') }}" class="btn-add-room">
                    <i class="bi bi-plus-lg"></i>
                    เพิ่มห้อง
                </a>

            </div>

        </div>


        {{-- SUCCESS MESSAGE --}}

        @if (session('success'))

        <div class="alert room-success alert-dismissible fade show mb-4" role="alert">

            <i class="bi bi-check-circle me-2"></i>

            {{ session('success') }}

            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>

        </div>

        @endif


        {{-- SEARCH / FILTER TOOLBAR --}}

        <div class="room-toolbar">

            <form id="roomSearchForm" method="GET" action="{{ route('rooms.index') }}">

                <div class="d-flex align-items-center gap-2">

                    {{-- SEARCH --}}

                    <div class="room-search-wrapper">

                        <i class="bi bi-search room-search-icon"></i>

                        <input type="text" id="roomSearchInput" name="search" value="{{ $search ?? '' }}"
                            class="room-search" placeholder="ค้นหาเลขห้อง..." autocomplete="off">

                    </div>


                    {{-- FLOOR --}}

                    <select id="roomFloorFilter" name="floor" class="form-select room-filter"><option value="">ทุกชั้น</option>@foreach($floors as $floor)<option value="{{ $floor }}" @selected((string)request('floor') === (string)$floor)>ชั้น {{ $floor }}</option>@endforeach</select>


                    {{-- STATUS --}}

                    <select id="roomStatusFilter" name="status" class="form-select room-filter">
                        <option value="">
                            สถานะทั้งหมด
                        </option>

                        <option value="VACANT" @selected(request('status') === 'VACANT')>
                            ว่าง
                        </option>

                        <option value="OCCUPIED" @selected(request('status') === 'OCCUPIED')>
                            มีผู้พัก
                        </option>

                        <option value="MAINTENANCE" @selected(request('status') === 'MAINTENANCE')>
                            ปิดปรับปรุง
                        </option>

                    </select>

                </div>

                <button class="btn-add-room" type="submit">ค้นหา</button><a class="btn btn-light" href="{{ route('rooms.index') }}">ล้าง</a>
            </form>

        </div>


        {{-- CARD VIEW --}}

        <div id="roomCardView">

<div id="roomCardGrid" class="rooms-grid">
                @forelse($rooms as $room)
                    @php($statusClass = in_array($room->r_status, ['VACANT', 'ว่าง'], true) ? 'available' : (in_array($room->r_status, ['MAINTENANCE', 'ปิดปรับปรุง'], true) ? 'maintenance' : 'occupied'))
                    <div class="room-card {{ $statusClass }}">
                        <div class="room-card-header"><div><div class="room-card-number">ห้อง {{ $room->r_name }}</div><div class="room-card-type">ชั้น {{ $room->r_floor }} · {{ $room->r_type }}</div></div><div class="room-dot {{ $statusClass }}"></div></div>
                        <span class="room-status-badge {{ $statusClass }}">{{ ['VACANT' => 'ว่าง', 'OCCUPIED' => 'มีผู้พัก', 'MAINTENANCE' => 'ปิดปรับปรุง'][$room->r_status] ?? $room->r_status }}</span>
                        @if(in_array($room->r_status, ['OCCUPIED', 'มีผู้พัก'], true))<div class="room-tenant">ผู้เช่า</div><div class="room-tenant-name">{{ trim(($room->rentals->first()?->tenant?->t_Fname ?? '').' '.($room->rentals->first()?->tenant?->t_Lname ?? '')) ?: '—' }}</div>@endif
                        <div class="room-price">฿{{ number_format((float)$room->r_rent, 2) }}<span>/เดือน</span></div>
                        <div class="room-actions"><button type="button" class="room-action-btn" data-room-history="{{ $room->r_id }}" data-room-name="{{ $room->r_name }}">ประวัติห้อง</button><a class="room-action-btn" href="{{ route('rooms.edit', $room) }}">แก้ไข</a></div>
                    </div>
                @empty<div class="rooms-state">ไม่พบข้อมูลห้องพัก</div>@endforelse
            </div>
        </div>

        {{-- TABLE VIEW --}}

        <div id="roomTableView" class="room-table-wrapper">

            <div class="table-responsive">

                <table class="table room-table">

                    <thead>

                        <tr>

                            <th>
                                เลขห้อง
                            </th>

                            <th>
                                ชั้น
                            </th>

                            <th>
                                ประเภทห้อง
                            </th>

                            <th>
                                ราคา/เดือน
                            </th>

                            <th>
                                สถานะ
                            </th>

                            <th>ผู้เช่าปัจจุบัน</th><th class="text-center">
                                จัดการ
                            </th>

                        </tr>

                    </thead>


                    <tbody id="roomTableBody">@forelse($rooms as $room)<tr><td>{{ $room->r_name }}</td><td>{{ $room->r_floor }}</td><td>{{ $room->r_type }}</td><td>฿{{ number_format((float)$room->r_rent, 2) }}</td><td>{{ ['VACANT' => 'ว่าง', 'OCCUPIED' => 'มีผู้พัก', 'MAINTENANCE' => 'ปิดปรับปรุง'][$room->r_status] ?? $room->r_status }}</td><td>{{ trim(($room->rentals->first()?->tenant?->t_Fname ?? '').' '.($room->rentals->first()?->tenant?->t_Lname ?? '')) ?: '—' }}</td><td><a class="btn btn-sm room-edit-btn" href="{{ route('rooms.edit', $room) }}">แก้ไข</a><button class="btn btn-sm btn-outline-primary" type="button" data-room-history="{{ $room->r_id }}" data-room-name="{{ $room->r_name }}">ประวัติ</button><form class="d-inline" method="POST" action="{{ route('rooms.destroy', $room) }}" onsubmit="return confirm('ยืนยันลบห้องนี้?')">@csrf @method('DELETE')<button class="btn btn-sm room-delete-btn" type="submit">ลบ</button></form></td></tr>@empty<tr><td colspan="7" class="text-center py-5">ไม่พบข้อมูลห้องพัก</td></tr>@endforelse</tbody>

                </table>

            </div>

        </div>


        {{-- PAGINATION --}}

        <div id="roomPagination" class="d-flex justify-content-center py-4">{{ $rooms->links('pagination::bootstrap-5') }}</div>

    </div>


    {{-- API SCRIPT --}}

    @push('scripts')



    <div class="modal fade" id="room-history-modal" tabindex="-1" aria-labelledby="room-history-title" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="room-history-title">ประวัติห้อง</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                </div>
                <div class="modal-body">
                    <div id="room-history-state" class="text-center text-secondary py-5" role="status" aria-live="polite">กำลังโหลดประวัติ...</div>
                    <div id="room-history-content" class="d-none">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle">
                                <thead><tr><th>ประเภท</th><th>วันที่</th><th>รายละเอียด</th><th>สถานะ</th></tr></thead>
                                <tbody id="room-history-rows"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">ปิด</button></div>
            </div>
        </div>
    </div>



    @endpush

<script>
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.getElementById('roomCardView'); const table = document.getElementById('roomTableView');
    document.getElementById('cardViewButton').onclick = () => {cards.style.display=''; table.style.display='none'; document.getElementById('cardViewButton').classList.add('active'); document.getElementById('tableViewButton').classList.remove('active');};
    document.getElementById('tableViewButton').onclick = () => {cards.style.display='none'; table.style.display='block'; document.getElementById('tableViewButton').classList.add('active'); document.getElementById('cardViewButton').classList.remove('active');};
    const element = document.getElementById('room-history-modal'); const modal = new bootstrap.Modal(element);
    document.querySelectorAll('[data-room-history]').forEach(button => button.onclick = async () => {
        const state=document.getElementById('room-history-state'); const content=document.getElementById('room-history-content'); const rows=document.getElementById('room-history-rows');
        state.classList.remove('d-none'); content.classList.add('d-none'); state.textContent='กำลังโหลดประวัติ...'; document.getElementById('room-history-title').textContent=`ประวัติห้อง ${button.dataset.roomName}`; modal.show();
        try {const response=await fetch(`/rooms/${button.dataset.roomHistory}/history`, {credentials:'same-origin',headers:{Accept:'application/json'}}); if(!response.ok) throw new Error('โหลดประวัติไม่สำเร็จ'); const body=await response.json(); rows.replaceChildren();
            (body.data || []).forEach(item => {const row=rows.insertRow(); ['type','date','detail','status'].forEach(key => {const cell=row.insertCell(); cell.textContent=item[key] ?? '—';});});
            if(!rows.children.length){const cell=rows.insertRow().insertCell(); cell.colSpan=4; cell.textContent='ยังไม่มีประวัติ';}
            state.textContent=body.message; content.classList.remove('d-none');
        }catch(error){state.textContent=error.message;}
    });
});
</script>
</x-layouts::app.sidebar>
