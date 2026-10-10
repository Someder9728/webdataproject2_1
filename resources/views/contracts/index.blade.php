@php($mode = $mode ?? 'rentals')
<x-layouts::app.sidebar :title="$mode === 'contracts' ? 'สัญญาเช่า' : 'การเช่า'">
    @include('partials.admin-page-style')
    <div class="tenant-page">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
            <div>
                <h1 class="tenant-title">{{ $mode === 'contracts' ? 'สัญญาเช่า' : 'รายการการเช่า' }}</h1>
                <p class="tenant-subtitle mb-0">
                    {{ $mode === 'contracts' ? 'ตรวจสอบและแก้ไขวันสิ้นสุดสัญญา' : 'ดูรายการเช่าและบันทึกการย้ายออก' }}
                </p>
            </div>
            <a class="btn tenant-edit-btn" href="{{ route($mode === 'contracts' ? 'rentals.index' : 'contracts.index') }}">
                <i class="bi bi-{{ $mode === 'contracts' ? 'key' : 'file-earmark-text' }} me-1"></i>
                {{ $mode === 'contracts' ? 'ไปหน้าการเช่า' : 'จัดการสัญญา' }}
            </a>
        </div>

        <div id="rental-notice" class="alert d-none" role="status" aria-live="polite"></div>

        <div class="card tenant-table-card">
            <div class="card-body border-bottom">
                <label class="visually-hidden" for="rental-search">ค้นหาผู้เช่าหรือห้อง</label>
                <div class="input-group" style="max-width: 440px">
                    <span class="input-group-text tenant-search-icon"><i class="bi bi-search"></i></span>
                    <input id="rental-search" class="form-control tenant-search-input" type="search"
                        placeholder="ค้นหาชื่อผู้เช่าหรือเลขห้อง" value="{{ request('search', '') }}" autocomplete="off">
                </div>
            </div>

            <div id="rental-state" class="p-5 text-center text-secondary" role="status" aria-live="polite">
                <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>กำลังโหลดข้อมูล...
            </div>

            <div id="rental-table-wrap" class="table-responsive d-none">
                <table class="table tenant-table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            @if($mode === 'contracts')
                                <th>เลขที่สัญญา</th><th>ผู้เช่า</th><th>ห้อง</th><th>วันเริ่ม</th><th>วันสิ้นสุด</th><th>สถานะ</th><th></th>
                            @else
                                <th>ผู้เช่า</th><th>ห้อง</th><th>วันเข้า</th><th>วันออก</th><th>สถานะ</th><th>สัญญา</th><th></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody id="rental-table-body"></tbody>
                </table>
            </div>

            <div id="rental-pagination" class="d-none align-items-center justify-content-between gap-3 p-3">
                <span id="rental-page-label" class="small text-secondary"></span>
                <div class="btn-group">
                    <button id="rental-prev" class="btn tenant-clear-btn" type="button">ก่อนหน้า</button>
                    <button id="rental-next" class="btn tenant-clear-btn" type="button">ถัดไป</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="contract-modal" tabindex="-1" aria-labelledby="contract-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="contract-form">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="contract-modal-title">รายละเอียดสัญญา</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="ปิด"></button>
                    </div>
                    <div class="modal-body">
                        <div id="contract-loading" class="text-secondary">กำลังโหลดข้อมูลสัญญา...</div>
                        <div id="contract-fields" class="d-none">
                            <dl class="row small mb-3">
                                <dt class="col-5">เลขที่สัญญา</dt><dd class="col-7" id="contract-number">—</dd>
                                <dt class="col-5">ช่วงสัญญา</dt><dd class="col-7" id="contract-range">—</dd>
                                <dt class="col-5">ค่าเช่า</dt><dd class="col-7" id="contract-rent">—</dd>
                                <dt class="col-5">เงินประกัน</dt><dd class="col-7" id="contract-deposit">—</dd>
                                <dt class="col-5">สถานะ</dt><dd class="col-7" id="contract-status">—</dd>
                            </dl>
                            <label for="contract-end" class="form-label">วันสิ้นสุดสัญญา</label>
                            <input id="contract-end" name="c_end" type="date" class="form-control mb-3">
                            <label for="contract-reason" class="form-label">เหตุผลที่แก้ไข <span class="text-danger">*</span></label>
                            <textarea id="contract-reason" name="reason" class="form-control" rows="3" maxlength="500" required></textarea>
                            <div class="form-text">ปล่อยว่างวันสิ้นสุด หากสัญญาไม่มีกำหนดวันสิ้นสุด</div>
                            <div id="contract-error" class="text-danger small mt-2" role="alert"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn tenant-clear-btn" data-bs-dismiss="modal">ปิด</button>
                        <button id="contract-save" type="submit" class="btn tenant-add-btn d-none">บันทึกสัญญา</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="moveout-modal" tabindex="-1" aria-labelledby="moveout-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="moveout-form">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="moveout-modal-title">บันทึกย้ายออก</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="ปิด"></button>
                    </div>
                    <div class="modal-body">
                        <p id="moveout-description" class="text-secondary"></p>
                        <label for="moveout-date" class="form-label">วันที่ย้ายออก <span class="text-danger">*</span></label>
                        <input id="moveout-date" name="rt_moveout" type="date" class="form-control mb-3" required>
                        <label for="moveout-reason" class="form-label">เหตุผล <span class="text-danger">*</span></label>
                        <textarea id="moveout-reason" name="reason" class="form-control" rows="3" maxlength="2000" required></textarea>
                        <div id="moveout-error" class="text-danger small mt-2" role="alert"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn tenant-clear-btn" data-bs-dismiss="modal">ยกเลิก</button>
                        <button id="moveout-submit" type="submit" class="btn btn-danger">ยืนยันย้ายออก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const mode = @json($mode);
        const state = document.getElementById('rental-state');
        const tableWrap = document.getElementById('rental-table-wrap');
        const tbody = document.getElementById('rental-table-body');
        const pagination = document.getElementById('rental-pagination');
        const search = document.getElementById('rental-search');
        const notice = document.getElementById('rental-notice');
        const contractModal = new bootstrap.Modal(document.getElementById('contract-modal'));
        const moveoutModal = new bootstrap.Modal(document.getElementById('moveout-modal'));
        let currentPage = 1;
        let lastPage = 1;
        let selectedRentalId = null;
        let selectedContractId = null;
        let debounce = null;
        let activeRequest = null;

        const escapeHtml = value => {
            const node = document.createElement('span');
            node.textContent = value ?? '';
            return node.innerHTML;
        };

        const statusLabel = status => ({ ACTIVE: 'กำลังเช่า', ENDED: 'สิ้นสุดแล้ว', EXPIRED: 'หมดอายุ' })[status] ?? status ?? '—';
        const apiRequest = async (url, options = {}) => {
            const response = await fetch(url, {
                credentials: 'same-origin',
                ...options,
                headers: {
                    Accept: 'application/json',
                    ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    ...(options.headers ?? {}),
                },
            });
            const body = await response.json().catch(() => ({}));
            if (response.status === 401) {
                window.location.assign('/login');
                throw new Error('กรุณาเข้าสู่ระบบใหม่');
            }
            if (!response.ok) {
                const validation = Object.values(body.errors ?? {}).flat().join(' ');
                throw new Error(validation || body.message || 'ดำเนินการไม่สำเร็จ กรุณาลองใหม่');
            }
            return body;
        };

        const showState = (message, isError = false, retry = false) => {
            tableWrap.classList.add('d-none');
            pagination.classList.add('d-none');
            state.className = `p-5 text-center ${isError ? 'text-danger' : 'text-secondary'}`;
            state.innerHTML = `${escapeHtml(message)}${retry ? ' <button id="rental-retry" type="button" class="btn btn-sm tenant-edit-btn ms-2">ลองใหม่</button>' : ''}`;
            document.getElementById('rental-retry')?.addEventListener('click', loadRentals);
        };

        async function loadRentals() {
            activeRequest?.abort();
            activeRequest = new AbortController();
            showState('กำลังโหลดข้อมูล...');
            const params = new URLSearchParams({ page: currentPage, per_page: 20 });
            if (search.value.trim()) params.set('search', search.value.trim());
            try {
                const result = await apiRequest(`/api/v1/rentals?${params}`, { signal: activeRequest.signal });
                let rows = result.data ?? [];
                if (mode === 'contracts') rows = rows.filter(row => row.contract);
                if (!rows.length) {
                    showState(mode === 'contracts' ? 'ยังไม่มีสัญญาที่ตรงกับการค้นหา' : 'ยังไม่มีรายการการเช่า');
                    return;
                }
                tbody.innerHTML = rows.map(rowHtml).join('');
                state.classList.add('d-none');
                tableWrap.classList.remove('d-none');
                lastPage = Number(result.meta?.last_page ?? 1);
                document.getElementById('rental-page-label').textContent = `หน้า ${Number(result.meta?.current_page ?? currentPage)} จาก ${lastPage} · ทั้งหมด ${Number(result.meta?.total ?? rows.length)} รายการ`;
                document.getElementById('rental-prev').disabled = currentPage <= 1;
                document.getElementById('rental-next').disabled = currentPage >= lastPage;
                pagination.classList.remove('d-none');
                pagination.classList.add('d-flex');
            } catch (error) {
                if (error.name === 'AbortError') return;
                showState(error.message || 'โหลดข้อมูลไม่สำเร็จ', true, true);
            }
        }

        function rowHtml(rental) {
            const tenant = rental.tenant ? `${rental.tenant.t_Fname ?? ''} ${rental.tenant.t_Lname ?? ''}`.trim() : '—';
            const room = rental.room?.r_name ?? '—';
            const status = statusLabel(rental.rt_status);
            const badge = rental.rt_status === 'ACTIVE' ? 'text-bg-success' : 'text-bg-secondary';
            if (mode === 'contracts') {
                return `<tr><td>${escapeHtml(rental.contract?.c_number ?? '—')}</td><td>${escapeHtml(tenant)}</td><td>${escapeHtml(room)}</td><td>${escapeHtml(rental.contract?.c_start ?? rental.rt_movein ?? '—')}</td><td>${escapeHtml(rental.contract?.c_end ?? 'ไม่มีกำหนด')}</td><td><span class="badge ${badge}">${escapeHtml(statusLabel(rental.contract?.c_status ?? rental.rt_status))}</span></td><td><button type="button" class="btn btn-sm tenant-edit-btn" data-contract-id="${Number(rental.rt_id)}" data-contract-editable="${rental.rt_status === 'ACTIVE' ? 'true' : 'false'}">ดู/แก้ไข</button></td></tr>`;
            }
            const contractButton = rental.contract
                ? `<button type="button" class="btn btn-sm tenant-edit-btn" data-contract-id="${Number(rental.rt_id)}" data-contract-editable="${rental.rt_status === 'ACTIVE' ? 'true' : 'false'}">สัญญา</button>`
                : '<span class="text-secondary small">ไม่มีสัญญา</span>';
            const moveOutButton = rental.rt_status === 'ACTIVE'
                ? `<button type="button" class="btn btn-sm btn-outline-danger" data-moveout-id="${Number(rental.rt_id)}" data-moveout-name="${escapeHtml(tenant)}" data-moveout-room="${escapeHtml(room)}" data-movein="${escapeHtml(rental.rt_movein ?? '')}">ย้ายออก</button>`
                : '';
            return `<tr><td>${escapeHtml(tenant)}</td><td>${escapeHtml(room)}</td><td>${escapeHtml(rental.rt_movein ?? '—')}</td><td>${escapeHtml(rental.rt_moveout ?? '—')}</td><td><span class="badge ${badge}">${escapeHtml(status)}</span></td><td>${contractButton}</td><td class="text-end text-nowrap">${moveOutButton}</td></tr>`;
        }

        const today = () => {
            const date = new Date();
            return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
        };

        tbody.addEventListener('click', async event => {
            const contractButton = event.target.closest('[data-contract-id]');
            if (contractButton) {
                selectedContractId = Number(contractButton.dataset.contractId);
                document.getElementById('contract-loading').classList.remove('d-none');
                document.getElementById('contract-fields').classList.add('d-none');
                document.getElementById('contract-save').classList.add('d-none');
                document.getElementById('contract-error').textContent = '';
                contractModal.show();
                try {
                    const result = await apiRequest(`/api/v1/rentals/${selectedContractId}/contract`);
                    const contract = result.data;
                    document.getElementById('contract-number').textContent = contract.c_number ?? '—';
                    document.getElementById('contract-range').textContent = `${contract.c_start ?? '—'} ถึง ${contract.c_end ?? 'ไม่มีกำหนด'}`;
                    document.getElementById('contract-rent').textContent = `฿${Number(contract.c_rent ?? 0).toLocaleString('th-TH', { minimumFractionDigits: 2 })}`;
                    document.getElementById('contract-deposit').textContent = `฿${Number(contract.c_deposit ?? 0).toLocaleString('th-TH', { minimumFractionDigits: 2 })}`;
                    document.getElementById('contract-status').textContent = contract.c_status ?? '—';
                    document.getElementById('contract-end').value = contract.c_end ?? '';
                    document.getElementById('contract-reason').value = '';
                    const canEdit = contractButton.dataset.contractEditable === 'true'
                        && ['ACTIVE', 'EXPIRED'].includes(contract.c_status);
                    document.getElementById('contract-end').disabled = !canEdit;
                    document.getElementById('contract-reason').disabled = !canEdit;
                    document.getElementById('contract-save').classList.toggle('d-none', !canEdit);
                    document.getElementById('contract-loading').classList.add('d-none');
                    document.getElementById('contract-fields').classList.remove('d-none');
                } catch (error) {
                    document.getElementById('contract-loading').textContent = error.message;
                }
                return;
            }
            const moveOutButton = event.target.closest('[data-moveout-id]');
            if (moveOutButton) {
                selectedRentalId = Number(moveOutButton.dataset.moveoutId);
                const date = document.getElementById('moveout-date');
                date.value = today();
                date.min = moveOutButton.dataset.movein || '';
                date.max = today();
                document.getElementById('moveout-description').textContent = `${moveOutButton.dataset.moveoutName} · ห้อง ${moveOutButton.dataset.moveoutRoom}`;
                document.getElementById('moveout-reason').value = '';
                document.getElementById('moveout-error').textContent = '';
                moveoutModal.show();
            }
        });

        document.getElementById('moveout-form').addEventListener('submit', async event => {
            event.preventDefault();
            const button = document.getElementById('moveout-submit');
            const date = document.getElementById('moveout-date').value;
            const reason = document.getElementById('moveout-reason').value.trim();
            if (!reason) {
                document.getElementById('moveout-error').textContent = 'กรุณาระบุเหตุผล';
                return;
            }
            button.disabled = true;
            button.textContent = 'กำลังบันทึก...';
            try {
                const result = await apiRequest(`/api/v1/rentals/${selectedRentalId}/move-out`, {
                    method: 'POST', body: JSON.stringify({ rt_moveout: date, reason }),
                });
                moveoutModal.hide();
                const count = result.data?.created_invoices?.length ?? 0;
                notice.className = 'alert alert-success';
                notice.textContent = `บันทึกย้ายออกสำเร็จ${count ? ` และออกใบแจ้งหนี้ ${count} รายการ` : ''}`;
                notice.classList.remove('d-none');
                await loadRentals();
            } catch (error) {
                document.getElementById('moveout-error').textContent = error.message;
            } finally {
                button.disabled = false;
                button.textContent = 'ยืนยันย้ายออก';
            }
        });

        document.getElementById('contract-form').addEventListener('submit', async event => {
            event.preventDefault();
            const button = document.getElementById('contract-save');
            const reason = document.getElementById('contract-reason').value.trim();
            if (!reason) {
                document.getElementById('contract-error').textContent = 'กรุณาระบุเหตุผลที่แก้ไข';
                return;
            }
            button.disabled = true;
            try {
                await apiRequest(`/api/v1/rentals/${selectedContractId}/contract`, {
                    method: 'PATCH',
                    body: JSON.stringify({ c_end: document.getElementById('contract-end').value || null, reason }),
                });
                contractModal.hide();
                notice.className = 'alert alert-success';
                notice.textContent = 'บันทึกสัญญาสำเร็จ';
                notice.classList.remove('d-none');
                await loadRentals();
            } catch (error) {
                document.getElementById('contract-error').textContent = error.message;
            } finally {
                button.disabled = false;
            }
        });

        document.getElementById('rental-prev').addEventListener('click', () => {
            if (currentPage > 1) { currentPage--; loadRentals(); }
        });
        document.getElementById('rental-next').addEventListener('click', () => {
            if (currentPage < lastPage) { currentPage++; loadRentals(); }
        });
        search.addEventListener('input', () => {
            clearTimeout(debounce);
            debounce = setTimeout(() => { currentPage = 1; loadRentals(); }, 300);
        });

        loadRentals();
    });
    </script>
</x-layouts::app.sidebar>
