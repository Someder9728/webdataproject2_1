{{-- resources/views/rentals/index.blade.php --}}
{{-- R1: รายการการเช่า (Admin) — เรียก GET /api/v1/rentals --}}


<x-layouts::app :title="__('รายการการเช่า')">
<div class="rentals-page">
    <div class="rentals-page__header">
        <h1>รายการการเช่า</h1>
        <a href="#" class="btn btn--primary" id="btn-create-rental">
            + สร้างการเช่า
        </a>
    </div>

    <div class="rentals-page__toolbar">
        <input
            type="search"
            id="rental-search"
            placeholder="ค้นหาผู้เช่าหรือห้อง..."
            aria-label="ค้นหาผู้เช่าหรือห้อง"
        >
    </div>

    <div id="rental-state-loading" class="state-box" hidden>กำลังโหลดข้อมูล...</div>
    <div id="rental-state-empty" class="state-box" hidden>ยังไม่มีรายการการเช่า</div>
    <div id="rental-state-error" class="state-box state-box--error" hidden>
        <span id="rental-error-message">โหลดข้อมูลไม่สำเร็จ</span>
        <button type="button" id="btn-retry">ลองใหม่</button>
    </div>

    <table id="rental-table" class="rental-table" hidden>
        <thead>
            <tr>
                <th>ผู้เช่า</th>
                <th>ห้อง</th>
                <th>วันเข้า</th>
                <th>วันออก</th>
                <th>สถานะ</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="rental-table-body"></tbody>
    </table>

    <div id="rental-pagination" class="pagination" hidden>
        <button type="button" id="btn-prev-page">ก่อนหน้า</button>
        <span id="pagination-info"></span>
        <button type="button" id="btn-next-page">ถัดไป</button>
    </div>
</div>

<style>
    .rentals-page { max-width: 960px; margin: 0 auto; padding: 24px 16px; }
    .rentals-page__header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
    .rentals-page__toolbar { margin-bottom: 12px; }
    #rental-search { width: 100%; max-width: 320px; padding: 8px 12px; border: 1px solid #d0d0d0; border-radius: 4px; }
    .btn { display: inline-block; padding: 8px 16px; border-radius: 4px; text-decoration: none; }
    .btn--primary { background: #2563eb; color: #fff; }
    .state-box { padding: 24px; text-align: center; color: #555; border: 1px dashed #ccc; border-radius: 6px; }
    .state-box--error { color: #b91c1c; border-color: #fca5a5; background: #fef2f2; }
    .rental-table { width: 100%; border-collapse: collapse; }
    .rental-table th, .rental-table td { text-align: left; padding: 10px 8px; border-bottom: 1px solid #eee; }
    .badge { padding: 2px 8px; border-radius: 999px; font-size: 0.85em; }
    .badge--active { background: #dcfce7; color: #166534; }
    .badge--ended { background: #f3f4f6; color: #4b5563; }
    .pagination { display: flex; align-items: center; gap: 12px; margin-top: 16px; }
</style>

<script>
(function () {
    
    const API_BASE = '/api/v1/rentals';

    
    const STATUS_LABEL = {
        ACTIVE: { text: 'กำลังเช่า', cls: 'badge--active' },
        ENDED:  { text: 'สิ้นสุดแล้ว', cls: 'badge--ended' },
    };

    const el = {
        search: document.getElementById('rental-search'),
        loading: document.getElementById('rental-state-loading'),
        empty: document.getElementById('rental-state-empty'),
        error: document.getElementById('rental-state-error'),
        errorMsg: document.getElementById('rental-error-message'),
        retry: document.getElementById('btn-retry'),
        table: document.getElementById('rental-table'),
        tbody: document.getElementById('rental-table-body'),
        pagination: document.getElementById('rental-pagination'),
        paginationInfo: document.getElementById('pagination-info'),
        prevBtn: document.getElementById('btn-prev-page'),
        nextBtn: document.getElementById('btn-next-page'),
    };

    let state = { page: 1, perPage: 20, search: '', total: 0 };
    let searchDebounce = null;
    let inFlight = false; // กันยิงซ้ำระหว่างรอ response

    function showOnly(name) {
        ['loading', 'empty', 'error', 'table', 'pagination'].forEach((k) => {
            el[k].hidden = k !== name;
        });
    }

    async function loadRentals() {
        if (inFlight) return;
        inFlight = true;
        showOnly('loading');

        const params = new URLSearchParams({
            page: state.page,
            per_page: state.perPage,
        });
        if (state.search) params.set('search', state.search);

        try {
            const res = await fetch(`${API_BASE}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (res.status === 401) {
                window.location.href = '/login';
                return;
            }
            if (res.status === 403) {
                let body = null;
                try { body = await res.json(); } catch (_) {}
                throw new Error(body?.message || 'คุณไม่มีสิทธิ์ดูข้อมูลนี้');
            }
            if (!res.ok) {
                throw new Error('เกิดข้อผิดพลาดในการโหลดข้อมูล (' + res.status + ')');
            }

            const body = await res.json();
            state.total = body.meta.total;
            render(body.data, body.meta);
        } catch (err) {
            el.errorMsg.textContent = err.message || 'โหลดข้อมูลไม่สำเร็จ';
            showOnly('error');
        } finally {
            inFlight = false;
        }
    }

    function render(rows, meta) {
        if (!rows.length) {
            showOnly('empty');
            return;
        }

        el.tbody.innerHTML = rows.map(rowHtml).join('');
        el.paginationInfo.textContent = `หน้า ${meta.page} จาก ${Math.max(1, Math.ceil(meta.total / meta.per_page))} (ทั้งหมด ${meta.total} รายการ)`;
        el.prevBtn.disabled = meta.page <= 1;
        el.nextBtn.disabled = meta.page * meta.per_page >= meta.total;

        showOnly('table');
        el.pagination.hidden = false; // แสดงคู่กับตาราง
    }

    function rowHtml(r) {
        const status = STATUS_LABEL[r.rt_status] || { text: r.rt_status, cls: '' };
        const tenantName = r.tenant ? `${r.tenant.t_Fname} ${r.tenant.t_Lname}` : '-';
        const roomName = r.room ? r.room.r_name : '-';

        return `
            <tr>
                <td>${escapeHtml(tenantName)}</td>
                <td>${escapeHtml(roomName)}</td>
                <td>${r.rt_movein ?? '-'}</td>
                <td>${r.rt_moveout ?? '-'}</td>
                <td><span class="badge ${status.cls}">${escapeHtml(status.text)}</span></td>
                <td><a href="/rentals/${r.rt_id}">ดูรายละเอียด</a></td>
            </tr>
        `;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    el.search.addEventListener('input', () => {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => {
            state.search = el.search.value.trim();
            state.page = 1;
            loadRentals();
        }, 300);
    });

    el.retry.addEventListener('click', loadRentals);
    el.prevBtn.addEventListener('click', () => {
        if (state.page > 1) { state.page -= 1; loadRentals(); }
    });
    el.nextBtn.addEventListener('click', () => {
        state.page += 1;
        loadRentals();
    });

    loadRentals();
})();
</script>
</x-layouts::app>
