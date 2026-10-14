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

                <form id="tenantSearchForm" method="GET" action="{{ route('tenants.index') }}">

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


                        <a href="{{ route('tenants.index') }}" class="tenant-clear-btn d-inline-flex align-items-center text-decoration-none">ล้าง</a>

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


                    <tbody id="tenantTableBody">@forelse($tenants as $tenant)<tr><td class="tenant-id">{{ $tenant->t_id }}</td><td class="tenant-name">{{ $tenant->t_Fname }} {{ $tenant->t_Lname }}</td><td class="tenant-phone">{{ $tenant->t_tel }}</td><td class="tenant-email">{{ $tenant->t_mail ?? '—' }}</td><td class="tenant-address">{{ $tenant->t_address ?? '—' }}</td><td class="text-center"><a class="tenant-action tenant-edit-btn" href="{{ route('tenants.edit', $tenant) }}" aria-label="แก้ไขผู้เช่า"><i class="bi bi-pencil"></i></a><form class="d-inline" method="POST" action="{{ route('tenants.destroy', $tenant) }}" onsubmit="return confirm('ยืนยันลบผู้เช่ารายการนี้?')">@csrf @method('DELETE')<button class="tenant-action tenant-delete-btn" type="submit" aria-label="ลบผู้เช่า"><i class="bi bi-trash"></i></button></form></td></tr>@empty<tr><td colspan="6" class="tenant-state text-center">ไม่พบข้อมูลผู้เช่า</td></tr>@endforelse</tbody>

                </table>

            </div>


            {{-- API PAGINATION --}}

            <div id="tenantPagination" class="d-flex justify-content-center">{{ $tenants->links('pagination::bootstrap-5') }}</div>

        </div>

    </div>


    {{-- API SCRIPT --}}


</x-layouts::app.sidebar>
