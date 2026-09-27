<x-layouts::app.sidebar :title="'ผู้เช่า'">

    <style>
    /* TENANTS PAGE */
    .tenant-page {
        min-height: calc(100vh - 54px);
        background: #f7f9fc;
        padding: 28px 30px 40px;
    }

    /* HEADER */
    .tenant-header {
        margin-bottom: 25px;
    }

    .tenant-title {
        margin: 0;
        color: #162033;
        font-size: 24px;
        font-weight: 700;
        line-height: 1.3;
    }

    .tenant-subtitle {
        margin-top: 5px;
        color: #7c8ba1;
        font-size: 13px;
    }

    .tenant-add-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 40px;
        padding: 0 17px;
        border: 0;
        border-radius: 7px;
        background: #1a3d6f;
        color: #ffffff;
        font-size: 13px;
        font-weight: 600;
        box-shadow: 0 2px 5px rgba(22, 45, 74, 0.12);
    }

    .tenant-add-btn:hover {
        background: #142f57;
        color: #ffffff;
    }

    /* ALERT */
    .tenant-alert {
        border-radius: 8px;
        font-size: 13px;
    }

    /* SEARCH CARD */
    .tenant-search-card {
        margin-bottom: 20px;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        background: #ffffff;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.035);
    }

    .tenant-search-card .card-body {
        padding: 17px 19px;
    }

    .tenant-search-input {
        height: 40px;
        border: 1px solid #d6dee8;
        border-left: 0;
        color: #263449;
        font-size: 13px;
    }

    .tenant-search-input::placeholder {
        color: #9aa8ba;
    }

    .tenant-search-input:focus {
        border-color: #9cb8dc;
        box-shadow: none;
    }

    .tenant-search-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        border: 1px solid #d6dee8;
        border-right: 0;
        border-radius: 7px 0 0 7px;
        background: #ffffff;
        color: #8c9bad;
    }

    .tenant-search-btn {
        height: 40px;
        padding: 0 17px;
        border: 0;
        border-radius: 6px;
        background: #1a3d6f;
        color: #ffffff;
        font-size: 13px;
        font-weight: 600;
    }

    .tenant-search-btn:hover {
        background: #142f57;
    }

    .tenant-clear-btn {
        height: 40px;
        padding: 0 16px;
        border: 1px solid #d6dee8;
        border-radius: 6px;
        background: #ffffff;
        color: #64748b;
        font-size: 13px;
    }

    .tenant-clear-btn:hover {
        background: #f8fafc;
        color: #334155;
    }

    /* TABLE CARD */
    .tenant-table-card {
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        background: #ffffff;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.035);
    }

    .tenant-table {
        margin: 0;
    }

    .tenant-table thead th {
        height: 47px;
        padding: 0 16px;
        border-bottom: 1px solid #e5eaf0;
        background: #f8fafc;
        color: #64748b;
        font-size: 11px;
        font-weight: 600;
        vertical-align: middle;
        white-space: nowrap;
    }

    .tenant-table thead th:first-child {
        padding-left: 20px;
    }

    .tenant-table tbody td {
        min-height: 58px;
        padding: 12px 16px;
        border-bottom: 1px solid #edf1f5;
        color: #475569;
        font-size: 12px;
        vertical-align: middle;
    }

    .tenant-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .tenant-table tbody tr:hover {
        background: #fafcff;
    }

    .tenant-id {
        color: #94a3b8;
        font-size: 11px;
    }

    .tenant-name {
        color: #1e293b;
        font-size: 13px;
        font-weight: 600;
    }

    .tenant-phone {
        color: #475569;
    }

    .tenant-email {
        color: #64748b;
    }

    .tenant-address {
        max-width: 280px;
        overflow: hidden;
        color: #64748b;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* ACTION BUTTONS */
    .tenant-action {
        width: 31px;
        height: 31px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border-radius: 6px;
        font-size: 12px;
    }

    .tenant-edit-btn {
        border: 1px solid #b8cbea;
        background: #f4f8ff;
        color: #315f9f;
    }

    .tenant-edit-btn:hover {
        border-color: #8caedc;
        background: #eaf2ff;
        color: #234e88;
    }

    .tenant-delete-btn {
        border: 1px solid #f1c0c0;
        background: #fff7f7;
        color: #dc4c4c;
    }

    .tenant-delete-btn:hover {
        border-color: #e59b9b;
        background: #fff0f0;
        color: #c93636;
    }

    /* PAGINATION */
    #tenantPagination {
        padding: 18px 20px !important;
    }

    #tenantPagination .pagination {
        gap: 4px;
        margin: 0;
    }

    #tenantPagination .page-link {
        min-width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 9px;
        border: 1px solid #dce3eb;
        border-radius: 5px !important;
        background: #ffffff;
        color: #64748b;
        font-size: 11px;
    }

    #tenantPagination .page-link:hover {
        background: #f5f8fc;
        color: #1a3d6f;
    }

    #tenantPagination .page-item.active .page-link {
        border-color: #1a3d6f;
        background: #1a3d6f;
        color: #ffffff;
    }

    #tenantPagination .page-item.disabled .page-link {
        background: #f8fafc;
        color: #cbd5e1;
    }

    /* EMPTY / LOADING */
    .tenant-state {
        padding: 55px 20px !important;
    }

    .tenant-state-icon {
        margin-bottom: 12px;
        color: #a0aec0;
        font-size: 35px;
    }

    .tenant-state-title {
        margin-bottom: 5px;
        color: #334155;
        font-size: 13px;
        font-weight: 600;
    }

    .tenant-state-text {
        color: #94a3b8;
        font-size: 12px;
    }
    </style>


    <div class="tenant-page">

        {{-- PAGE HEADER --}}
        <div class="tenant-header d-flex justify-content-between align-items-end">

            <div>
                <h1 class="tenant-title">
                    ผู้เช่า
                </h1>

                <div class="tenant-subtitle">
                    จัดการข้อมูลผู้เช่าของหอพัก
                </div>
            </div>


            <a href="{{ route('tenants.create') }}" class="tenant-add-btn text-decoration-none">
                <i class="bi bi-plus-lg"></i>
                เพิ่มผู้เช่า
            </a>

        </div>


        {{-- SUCCESS --}}
        @if (session('success'))

        <div class="alert alert-success tenant-alert alert-dismissible fade show" role="alert">

            <i class="bi bi-check-circle me-2"></i>

            {{ session('success') }}

            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>

        </div>

        @endif


        {{-- ERROR --}}
        @if (session('error'))

        <div class="alert alert-danger tenant-alert alert-dismissible fade show" role="alert">

            <i class="bi bi-exclamation-triangle me-2"></i>

            {{ session('error') }}

            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>

        </div>

        @endif


        {{-- SEARCH --}}
        <div class="tenant-search-card">

            <div class="card-body">

                <form id="tenantSearchForm">

                    <div class="d-flex gap-2">

                        <div class="input-group flex-grow-1">

                            <span class="tenant-search-icon">
                                <i class="bi bi-search"></i>
                            </span>

                            <input type="text" id="tenantSearchInput" name="search" value="{{ $search ?? '' }}"
                                class="form-control tenant-search-input"
                                placeholder="ค้นหาชื่อ นามสกุล เบอร์โทรศัพท์ หรืออีเมล" autocomplete="off">

                        </div>


                        <button type="submit" class="tenant-search-btn">
                            ค้นหา
                        </button>


                        <button type="button" id="clearTenantSearch" class="tenant-clear-btn">
                            ล้าง
                        </button>

                    </div>

                </form>

            </div>

        </div>


        {{-- TENANT TABLE --}}
        <div class="tenant-table-card">

            <div class="table-responsive">

                <table class="table tenant-table align-middle">

                    <thead>

                        <tr>

                            <th style="width: 70px;">
                                #
                            </th>

                            <th style="min-width: 190px;">
                                ชื่อ-นามสกุล
                            </th>

                            <th style="min-width: 145px;">
                                เบอร์โทรศัพท์
                            </th>

                            <th style="min-width: 190px;">
                                อีเมล
                            </th>

                            <th style="min-width: 220px;">
                                ที่อยู่
                            </th>

                            <th class="text-center" style="width: 105px;">
                                จัดการ
                            </th>

                        </tr>

                    </thead>


                    <tbody id="tenantTableBody">

                        <tr>

                            <td colspan="6" class="text-center tenant-state">

                                <div class="spinner-border text-primary mb-3">
                                    <span class="visually-hidden">
                                        Loading...
                                    </span>
                                </div>

                                <div class="tenant-state-text">
                                    กำลังโหลดข้อมูลผู้เช่า...
                                </div>

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>


            {{-- API PAGINATION --}}

            <div id="tenantPagination" class="d-flex justify-content-center"></div>

        </div>

    </div>


    {{-- API SCRIPT --}}
    @push('scripts')

    <script>
    document.addEventListener('DOMContentLoaded', function() {

        const tableBody =
            document.getElementById('tenantTableBody');

        const paginationContainer =
            document.getElementById('tenantPagination');

        const searchForm =
            document.getElementById('tenantSearchForm');

        const searchInput =
            document.getElementById('tenantSearchInput');

        const clearButton =
            document.getElementById('clearTenantSearch');


        /* PAGINATION STATE */
        let currentPage = 1;

        const perPage = 20;


        /* LOAD TENANTS */
        async function loadTenants(
            search = '',
            page = 1
        ) {

            currentPage = page;


            tableBody.innerHTML = `

                    <tr>

                        <td
                            colspan="6"
                            class="text-center tenant-state"
                        >

                            <div
                                class="spinner-border text-primary mb-3"
                                role="status"
                            >

                                <span class="visually-hidden">
                                    Loading...
                                </span>

                            </div>

                            <div class="tenant-state-text">
                                กำลังโหลดข้อมูลผู้เช่า...
                            </div>

                        </td>

                    </tr>

                `;


            paginationContainer.innerHTML = '';


            try {

                const result =
                    await window.tenantApi.getAll(
                        search,
                        page,
                        perPage
                    );


                console.log(
                    'Tenant API Result:',
                    result
                );


                const tenants =
                    result.data || [];


                renderTenants(tenants);

                renderPagination(
                    result.pagination
                );


            } catch (error) {

                console.error(
                    'Tenant API Error:',
                    error
                );


                tableBody.innerHTML = `

                        <tr>

                            <td
                                colspan="6"
                                class="text-center tenant-state"
                            >

                                <div class="tenant-state-icon text-danger">
                                    <i class="bi bi-exclamation-triangle"></i>
                                </div>

                                <div class="tenant-state-title">
                                    ไม่สามารถโหลดข้อมูลผู้เช่าได้
                                </div>

                                <div class="tenant-state-text">
                                    กรุณาลองใหม่อีกครั้ง
                                </div>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary mt-3"
                                    id="retryTenantButton"
                                >
                                    <i class="bi bi-arrow-clockwise me-1"></i>
                                    ลองใหม่
                                </button>

                            </td>

                        </tr>

                    `;


                document
                    .getElementById('retryTenantButton')
                    ?.addEventListener(
                        'click',
                        function() {

                            loadTenants(
                                searchInput.value.trim(),
                                currentPage
                            );

                        }
                    );

            }

        }


        /* RENDER TENANTS */
        function renderTenants(tenants) {

            if (tenants.length === 0) {

                tableBody.innerHTML = `

                        <tr>

                            <td
                                colspan="6"
                                class="text-center tenant-state"
                            >

                                <div class="tenant-state-icon">
                                    <i class="bi bi-people"></i>
                                </div>

                                <div class="tenant-state-title">
                                    ไม่พบข้อมูลผู้เช่า
                                </div>

                                <div class="tenant-state-text">

                                    ${
                                        searchInput.value.trim()
                                            ? 'ไม่พบข้อมูลที่ตรงกับการค้นหา'
                                            : 'ยังไม่มีข้อมูลผู้เช่า'
                                    }

                                </div>

                                <button
                                    type="button"
                                    id="emptyClearSearch"
                                    class="btn btn-sm btn-outline-secondary mt-3"
                                >
                                    ล้างการค้นหา
                                </button>

                            </td>

                        </tr>

                    `;


                document
                    .getElementById('emptyClearSearch')
                    ?.addEventListener(
                        'click',
                        function() {

                            searchInput.value = '';

                            loadTenants('', 1);

                        }
                    );


                return;
            }


            tableBody.innerHTML =
                tenants
                .map(function(tenant) {

                    const fullName =
                        `${tenant.t_Fname ?? ''} ${tenant.t_Lname ?? ''}`.trim();


                    const email =
                        tenant.t_mail ?
                        escapeHtml(tenant.t_mail) :
                        '<span class="text-secondary">-</span>';


                    const address =
                        tenant.t_address ?
                        escapeHtml(tenant.t_address) :
                        '<span class="text-secondary">-</span>';


                    return `

                                <tr>

                                    <td>

                                        <span class="tenant-id">
                                            ${tenant.t_id}
                                        </span>

                                    </td>


                                    <td>

                                        <div class="tenant-name">
                                            ${escapeHtml(fullName)}
                                        </div>

                                    </td>


                                    <td>

                                        <span class="tenant-phone">
                                            ${escapeHtml(tenant.t_tel ?? '-')}
                                        </span>

                                    </td>


                                    <td>

                                        <span class="tenant-email">
                                            ${email}
                                        </span>

                                    </td>


                                    <td>

                                        <div class="tenant-address">
                                            ${address}
                                        </div>

                                    </td>


                                    <td class="text-center">

                                        <a
                                            href="/tenants/${tenant.t_id}/edit"
                                            class="btn tenant-action tenant-edit-btn me-1"
                                            title="แก้ไข"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </a>


                                        <form
                                            action="/tenants/${tenant.t_id}"
                                            method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('ต้องการลบผู้เช่ารายนี้หรือไม่?')"
                                        >

                                            @csrf

                                            <input
                                                type="hidden"
                                                name="_method"
                                                value="DELETE"
                                            >

                                            <button
                                                type="submit"
                                                class="btn tenant-action tenant-delete-btn"
                                                title="ลบ"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            `;

                })
                .join('');

        }


        /* PAGINATION */
        function renderPagination(pagination) {

            if (!pagination) {

                paginationContainer.innerHTML = '';

                return;

            }


            const current =
                Number(pagination.current_page);

            const last =
                Number(pagination.last_page);


            if (last <= 1) {

                paginationContainer.innerHTML = '';

                return;

            }


            let html = `

                    <nav aria-label="Tenant pagination">

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
                            class="page-link tenant-page-button"
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
                                class="page-link tenant-page-button"
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
                            class="page-link tenant-page-button"
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
                    '.tenant-page-button'
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


                                loadTenants(
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


                loadTenants(
                    search,
                    1
                );

            }
        );


        /* CLEAR SEARCH */
        clearButton.addEventListener(
            'click',
            function() {

                searchInput.value = '';

                loadTenants(
                    '',
                    1
                );

            }
        );


        /* ESCAPE HTML */
        function escapeHtml(value) {

            const div =
                document.createElement('div');

            div.textContent =
                value ?? '';

            return div.innerHTML;

        }


        /* START */
        loadTenants(
            searchInput.value.trim(),
            1
        );

    });
    </script>

    @endpush

</x-layouts::app.sidebar>