<x-layouts::app.sidebar :title="'ผู้เช่า'">

    @include('partials.admin-page-style')


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