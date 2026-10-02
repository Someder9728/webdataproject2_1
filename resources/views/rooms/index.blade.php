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

                <div id="roomSummary" class="rooms-count">
                    กำลังโหลดข้อมูล...
                </div>

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

            <form id="roomSearchForm">

                <div class="d-flex align-items-center gap-2">

                    {{-- SEARCH --}}

                    <div class="room-search-wrapper">

                        <i class="bi bi-search room-search-icon"></i>

                        <input type="text" id="roomSearchInput" name="search" value="{{ $search ?? '' }}"
                            class="room-search" placeholder="ค้นหาเลขห้อง..." autocomplete="off">

                    </div>


                    {{-- FLOOR --}}

                    <select id="roomFloorFilter" class="form-select room-filter">
                        <option value="">
                            ทุกชั้น
                        </option>
                    </select>


                    {{-- STATUS --}}

                    <select id="roomStatusFilter" class="form-select room-filter">
                        <option value="">
                            สถานะทั้งหมด
                        </option>

                        <option value="ว่าง">
                            ว่าง
                        </option>

                        <option value="มีผู้พัก">
                            มีผู้พัก
                        </option>

                        <option value="ปิดปรับปรุง">
                            ปิดปรับปรุง
                        </option>

                    </select>

                </div>

            </form>

        </div>


        {{-- CARD VIEW --}}

        <div id="roomCardView">

            <div id="roomCardGrid" class="rooms-grid">

                <div class="rooms-state">

                    <div class="spinner-border text-primary mb-3">
                        <span class="visually-hidden">
                            Loading...
                        </span>
                    </div>

                    <div class="rooms-state-text">
                        กำลังโหลดข้อมูลห้องพัก...
                    </div>

                </div>

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

                            <th class="text-center">
                                จัดการ
                            </th>

                        </tr>

                    </thead>


                    <tbody id="roomTableBody">

                        <tr>

                            <td colspan="6" class="text-center py-5">

                                <div class="spinner-border text-primary mb-3">
                                    <span class="visually-hidden">
                                        Loading...
                                    </span>
                                </div>

                                <div class="text-secondary">
                                    กำลังโหลดข้อมูลห้องพัก...
                                </div>

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>


        {{-- PAGINATION --}}

        <div id="roomPagination" class="d-flex justify-content-center py-4"></div>

    </div>


    {{-- API SCRIPT --}}

    @push('scripts')

    <script>
    document.addEventListener('DOMContentLoaded', function() {

        /* =====================================================
           ELEMENTS
        ====================================================== */

        const cardGrid =
            document.getElementById('roomCardGrid');

        const tableBody =
            document.getElementById('roomTableBody');

        const paginationContainer =
            document.getElementById('roomPagination');

        const searchForm =
            document.getElementById('roomSearchForm');

        const searchInput =
            document.getElementById('roomSearchInput');

        const floorFilter =
            document.getElementById('roomFloorFilter');

        const statusFilter =
            document.getElementById('roomStatusFilter');

        const roomSummary =
            document.getElementById('roomSummary');

        const cardViewButton =
            document.getElementById('cardViewButton');

        const tableViewButton =
            document.getElementById('tableViewButton');

        const cardView =
            document.getElementById('roomCardView');

        const tableView =
            document.getElementById('roomTableView');


        /* STATE */
        let currentPage = 1;

        const perPage = 20;

        let allRoomsCurrentPage = [];


        /* LOAD ROOMS */
        async function loadRooms(
            search = '',
            page = 1
        ) {

            currentPage = page;


            /* LOADING - CARD */
            cardGrid.innerHTML = `

                        <div class="rooms-state">

                            <div
                                class="spinner-border text-primary mb-3"
                                role="status"
                            >

                                <span class="visually-hidden">
                                    Loading...
                                </span>

                            </div>

                            <div class="rooms-state-text">
                                กำลังโหลดข้อมูลห้องพัก...
                            </div>

                        </div>

                    `;


            /* LOADING - TABLE */
            tableBody.innerHTML = `

                        <tr>

                            <td
                                colspan="6"
                                class="text-center py-5"
                            >

                                <div
                                    class="spinner-border text-primary mb-3"
                                    role="status"
                                >

                                    <span class="visually-hidden">
                                        Loading...
                                    </span>

                                </div>

                                <div class="text-secondary">
                                    กำลังโหลดข้อมูลห้องพัก...
                                </div>

                            </td>

                        </tr>

                    `;


            paginationContainer.innerHTML = '';


            try {

                const result =
                    await window.roomApi.getAll(
                        search,
                        page,
                        perPage
                    );


                console.log(
                    'Room API Result:',
                    result
                );


                const rooms =
                    result.data || [];


                allRoomsCurrentPage =
                    rooms;


                renderFloorFilter(rooms);


                renderRooms(rooms);


                renderPagination(
                    result.pagination
                );


            } catch (error) {

                console.error(
                    'Room API Error:',
                    error
                );


                renderError();

            }

        }


        /* FLOOR FILTER OPTIONS */
        function renderFloorFilter(rooms) {

            const selectedValue =
                floorFilter.value;


            const floors = [
                ...new Set(
                    rooms
                    .map(room => room.r_floor)
                    .filter(
                        floor =>
                        floor !== null &&
                        floor !== undefined
                    )
                )
            ].sort(
                (a, b) =>
                Number(a) - Number(b)
            );


            floorFilter.innerHTML = `

                        <option value="">
                            ทุกชั้น
                        </option>

                        ${
                            floors.map(
                                floor => `
                                    <option value="${escapeHtml(floor)}">
                                        ชั้น ${escapeHtml(floor)}
                                    </option>
                                `
                            ).join('')
                        }

                    `;


            if (
                floors.some(
                    floor =>
                    String(floor) ===
                    String(selectedValue)
                )
            ) {

                floorFilter.value =
                    selectedValue;

            }

        }


        /* FILTER CURRENT PAGE */
        function getFilteredRooms(rooms) {

            const selectedFloor =
                floorFilter.value;

            const selectedStatus =
                statusFilter.value;


            return rooms.filter(
                function(room) {

                    const floorMatch = !selectedFloor ||
                        String(room.r_floor) ===
                        String(selectedFloor);


                    const statusMatch = !selectedStatus ||
                        room.r_status ===
                        selectedStatus;


                    return (
                        floorMatch &&
                        statusMatch
                    );

                }
            );

        }


        /* RENDER ROOMS */
        function renderRooms(rooms) {

            if (!rooms.length) {

                renderEmpty();

                return;

            }


            const filteredRooms =
                getFilteredRooms(rooms);


            /* SUMMARY */
            const availableCount =
                rooms.filter(
                    room =>
                    room.r_status === 'ว่าง'
                ).length;


            const total =
                rooms.length;


            roomSummary.textContent =
                `ข้อมูล ${total} ห้อง · ว่าง ${availableCount} ห้อง`;


            /* NO RESULT AFTER FILTER */
            if (!filteredRooms.length) {

                cardGrid.innerHTML = `

                            <div class="rooms-state">

                                <div class="rooms-state-icon">
                                    <i class="bi bi-funnel"></i>
                                </div>

                                <div class="rooms-state-title">
                                    ไม่พบห้องพัก
                                </div>

                                <div class="rooms-state-text">
                                    ไม่พบห้องที่ตรงกับตัวกรอง
                                </div>

                                <button
                                    type="button"
                                    id="clearRoomFilters"
                                    class="btn btn-sm btn-outline-secondary mt-3"
                                >
                                    ล้างตัวกรอง
                                </button>

                            </div>

                        `;


                tableBody.innerHTML = `

                            <tr>

                                <td
                                    colspan="6"
                                    class="text-center py-5"
                                >

                                    <div class="text-secondary">
                                        ไม่พบห้องที่ตรงกับตัวกรอง
                                    </div>

                                </td>

                            </tr>

                        `;


                document
                    .getElementById('clearRoomFilters')
                    ?.addEventListener(
                        'click',
                        clearFilters
                    );


                return;

            }


            /* CARD VIEW */

            cardGrid.innerHTML =
                filteredRooms
                .map(
                    renderRoomCard
                )
                .join('');


            /* TABLE VIEW */

            tableBody.innerHTML =
                filteredRooms
                .map(
                    renderRoomTableRow
                )
                .join('');

        }


        /* ROOM CARD */
        function renderRoomCard(room) {

            let cardClass =
                'occupied';

            let dotClass =
                'occupied';

            let badgeClass =
                'occupied';


            if (
                room.r_status ===
                'ว่าง'
            ) {

                cardClass =
                    'available';

                dotClass =
                    'available';

                badgeClass =
                    'available';

            }


            if (
                room.r_status ===
                'ปิดปรับปรุง'
            ) {

                cardClass =
                    'maintenance';

                dotClass =
                    'maintenance';

                badgeClass =
                    'maintenance';

            }


            const tenantHtml =
                room.r_status ===
                'มีผู้พัก'

                ?
                `

                                <div class="room-tenant">
                                    ผู้เช่า
                                </div>

                                <div class="room-tenant-name">
                                    -
                                </div>

                              `

                :
                '';


            return `

                        <div class="room-card ${cardClass}">

                            <div class="room-card-header">

                                <div>

                                    <div class="room-card-number">
                                        ห้อง ${escapeHtml(room.r_name)}
                                    </div>

                                    <div class="room-card-type">
                                        ชั้น ${escapeHtml(room.r_floor)}
                                        ·
                                        ${escapeHtml(room.r_type)}
                                    </div>

                                </div>


                                <div
                                    class="room-dot ${dotClass}"
                                ></div>

                            </div>


                            <span
                                class="room-status-badge ${badgeClass}"
                            >
                                ${escapeHtml(room.r_status)}
                            </span>


                            ${tenantHtml}


                            <div class="room-price">

                                ฿${formatPrice(room.r_rent)}

                                <span>
                                    /เดือน
                                </span>

                            </div>


                            <div class="room-actions">

                                <button
                                    type="button"
                                    class="room-action-btn"
                                    data-room-id="${room.r_id}"
                                    onclick="return false;"
                                >
                                    ดูข้อมูล
                                </button>


                                <a
                                    href="/rooms/${room.r_id}/edit"
                                    class="room-action-btn"
                                >
                                    แก้ไข
                                </a>

                            </div>

                        </div>

                    `;

        }


        /* TABLE ROW */
        function renderRoomTableRow(room) {

            let badgeClass =
                'occupied';


            if (
                room.r_status ===
                'ว่าง'
            ) {

                badgeClass =
                    'available';

            }


            if (
                room.r_status ===
                'ปิดปรับปรุง'
            ) {

                badgeClass =
                    'maintenance';

            }


            return `

                        <tr>

                            <td>

                                <span class="fw-semibold">
                                    ห้อง ${escapeHtml(room.r_name)}
                                </span>

                            </td>


                            <td>
                                ชั้น ${escapeHtml(room.r_floor)}
                            </td>


                            <td>
                                ${escapeHtml(room.r_type)}
                            </td>


                            <td>

                                <span class="fw-semibold">
                                    ฿${formatPrice(room.r_rent)}
                                </span>

                            </td>


                            <td>

                                <span
                                    class="room-status-badge ${badgeClass}"
                                >
                                    ${escapeHtml(room.r_status)}
                                </span>

                            </td>


                            <td class="text-center">

                                <a
                                    href="/rooms/${room.r_id}/edit"
                                    class="btn btn-sm btn-outline-primary"
                                    title="แก้ไข"
                                >

                                    <i class="bi bi-pencil"></i>

                                </a>


                                <form
                                    action="/rooms/${room.r_id}"
                                    method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('ต้องการลบห้องพักนี้หรือไม่?')"
                                >

                                    @csrf

                                    <input
                                        type="hidden"
                                        name="_method"
                                        value="DELETE"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-outline-danger"
                                        title="ลบ"
                                    >

                                        <i class="bi bi-trash"></i>

                                    </button>

                                </form>

                            </td>

                        </tr>

                    `;

        }


        /* EMPTY */
        function renderEmpty() {

            const hasSearch =
                searchInput.value.trim() !== '';


            cardGrid.innerHTML = `

                        <div class="rooms-state">

                            <div class="rooms-state-icon">
                                <i class="bi bi-building"></i>
                            </div>

                            <div class="rooms-state-title">
                                ไม่พบข้อมูลห้องพัก
                            </div>

                            <div class="rooms-state-text">

                                ${
                                    hasSearch
                                        ? 'ไม่พบข้อมูลที่ตรงกับการค้นหา'
                                        : 'ยังไม่มีข้อมูลห้องพัก'
                                }

                            </div>

                            <button
                                type="button"
                                id="emptyClearSearch"
                                class="btn btn-sm btn-outline-secondary mt-3"
                            >
                                ล้างการค้นหา
                            </button>

                        </div>

                    `;


            tableBody.innerHTML = `

                        <tr>

                            <td
                                colspan="6"
                                class="text-center py-5"
                            >

                                <div class="rooms-state-icon">
                                    <i class="bi bi-building"></i>
                                </div>

                                <div class="rooms-state-title">
                                    ไม่พบข้อมูลห้องพัก
                                </div>

                                <div class="rooms-state-text">
                                    ${
                                        hasSearch
                                            ? 'ไม่พบข้อมูลที่ตรงกับการค้นหา'
                                            : 'ยังไม่มีข้อมูลห้องพัก'
                                    }
                                </div>

                            </td>

                        </tr>

                    `;


            roomSummary.textContent =
                'ไม่พบข้อมูล';


            document
                .getElementById('emptyClearSearch')
                ?.addEventListener(
                    'click',
                    function() {

                        searchInput.value = '';

                        clearFilters();

                    }
                );

        }


        /* ERROR */
        function renderError() {

            cardGrid.innerHTML = `

                        <div class="rooms-state">

                            <div
                                class="rooms-state-icon text-danger"
                            >
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>

                            <div class="rooms-state-title">
                                ไม่สามารถโหลดข้อมูลห้องพักได้
                            </div>

                            <div class="rooms-state-text">
                                กรุณาลองใหม่อีกครั้ง
                            </div>

                            <button
                                type="button"
                                id="retryRoomButton"
                                class="btn btn-sm btn-outline-primary mt-3"
                            >
                                <i class="bi bi-arrow-clockwise me-1"></i>
                                ลองใหม่
                            </button>

                        </div>

                    `;


            tableBody.innerHTML = `

                        <tr>

                            <td
                                colspan="6"
                                class="text-center py-5"
                            >

                                <div class="text-danger mb-2">
                                    <i class="bi bi-exclamation-triangle fs-3"></i>
                                </div>

                                <div class="fw-semibold mb-1">
                                    ไม่สามารถโหลดข้อมูลห้องพักได้
                                </div>

                                <div class="small text-secondary">
                                    กรุณาลองใหม่อีกครั้ง
                                </div>

                            </td>

                        </tr>

                    `;


            roomSummary.textContent =
                'ไม่สามารถโหลดข้อมูล';


            document
                .getElementById('retryRoomButton')
                ?.addEventListener(
                    'click',
                    function() {

                        loadRooms(
                            searchInput.value.trim(),
                            currentPage
                        );

                    }
                );

        }


        /* PAGINATION */
        function renderPagination(
            pagination
        ) {

            if (!pagination) {

                paginationContainer.innerHTML =
                    '';

                return;

            }


            const current =
                Number(
                    pagination.current_page
                );


            const last =
                Number(
                    pagination.last_page
                );


            if (
                !last ||
                last <= 1
            ) {

                paginationContainer.innerHTML =
                    '';

                return;

            }


            let html = `

                        <nav
                            aria-label="Room pagination"
                        >

                            <ul class="pagination mb-0">

                    `;


            /* PREVIOUS */
            html += `

                        <li
                            class="page-item ${
                                current === 1
                                    ? 'disabled'
                                    : ''
                            }"
                        >

                            <button
                                type="button"
                                class="page-link room-page-button"
                                data-page="${current - 1}"
                                ${
                                    current === 1
                                        ? 'disabled'
                                        : ''
                                }
                            >

                                <i class="bi bi-chevron-left"></i>

                            </button>

                        </li>

                    `;

            /* PAGE NUMBERS */
            for (
                let page = 1; page <= last; page++
            ) {

                html += `

                            <li
                                class="page-item ${
                                    page === current
                                        ? 'active'
                                        : ''
                                }"
                            >

                                <button
                                    type="button"
                                    class="page-link room-page-button"
                                    data-page="${page}"
                                >
                                    ${page}
                                </button>

                            </li>

                        `;

            }

            /* NEXT */
            html += `

                        <li
                            class="page-item ${
                                current === last
                                    ? 'disabled'
                                    : ''
                            }"
                        >

                            <button
                                type="button"
                                class="page-link room-page-button"
                                data-page="${current + 1}"
                                ${
                                    current === last
                                        ? 'disabled'
                                        : ''
                                }
                            >

                                <i class="bi bi-chevron-right"></i>

                            </button>

                        </li>

                    `;


            html += `

                            </ul>

                        </nav>

                    `;


            paginationContainer.innerHTML =
                html;


            document
                .querySelectorAll(
                    '.room-page-button'
                )
                .forEach(
                    function(button) {

                        button.addEventListener(
                            'click',
                            function() {

                                const page =
                                    Number(
                                        this.dataset.page
                                    );


                                if (
                                    page < 1 ||
                                    page > last ||
                                    page === current
                                ) {

                                    return;

                                }


                                loadRooms(
                                    searchInput.value.trim(),
                                    page
                                );

                            }
                        );

                    }
                );

        }


        /* SEARCH */
        searchForm.addEventListener(
            'submit',
            function(event) {

                event.preventDefault();


                const search =
                    searchInput.value.trim();


                loadRooms(
                    search,
                    1
                );

            }
        );


        /* FLOOR FILTER */
        floorFilter.addEventListener(
            'change',
            function() {

                renderRooms(
                    allRoomsCurrentPage
                );

            }
        );


        /* STATUS FILTER */
        statusFilter.addEventListener(
            'change',
            function() {

                renderRooms(
                    allRoomsCurrentPage
                );

            }
        );


        /* CLEAR FILTER */
        function clearFilters() {

            floorFilter.value =
                '';

            statusFilter.value =
                '';

            renderRooms(
                allRoomsCurrentPage
            );

        }


        /* VIEW SWITCH */
        cardViewButton.addEventListener(
            'click',
            function() {

                cardView.style.display =
                    'block';

                tableView.style.display =
                    'none';


                cardViewButton.classList.add(
                    'active'
                );

                tableViewButton.classList.remove(
                    'active'
                );

            }
        );


        tableViewButton.addEventListener(
            'click',
            function() {

                cardView.style.display =
                    'none';

                tableView.style.display =
                    'block';


                tableViewButton.classList.add(
                    'active'
                );

                cardViewButton.classList.remove(
                    'active'
                );

            }
        );


        /* FORMAT PRICE */
        function formatPrice(price) {

            return Number(
                price || 0
            ).toLocaleString(
                'th-TH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            );

        }


        /* ESCAPE HTML */
        function escapeHtml(value) {

            const div =
                document.createElement(
                    'div'
                );


            div.textContent =
                value ?? '';


            return div.innerHTML;

        }


        /* START */
        loadRooms(
            searchInput.value.trim(),
            1
        );

    });
    </script>

    @endpush

</x-layouts::app.sidebar>