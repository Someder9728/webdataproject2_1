{{-- resources/views/rentals/index.blade.php --}}
{{-- R1: รายการการเช่า (Admin) — เรียก GET /api/v1/rentals แล้วแยกแสดง "การเช่าปัจจุบัน" / "ประวัติการเช่า" ฝั่งหน้าเว็บ --}}

<x-layouts::app :title="__('การเช่า')">
@include('rentals._styles')

<div class="rt rt-page">
    <div class="rt-head">
        <div>
            <div class="rt-crumb">การเช่า</div>
            <h1 class="rt-title">การเช่า</h1>
            <div class="rt-subtitle" id="rental-summary">&nbsp;</div>
        </div>
        <div class="rt-head-actions">
            <a href="{{ route('rentals.create') }}" class="rt-btn rt-btn--primary" id="btn-create-rental">
                + บันทึกการเช่า
            </a>
        </div>
    </div>

    <div id="rental-banner" class="rt-notice rt-notice--ok" hidden></div>

    <div class="rt-toolbar">
        <input
            type="search"
            id="rental-search"
            class="rt-search"
            placeholder="ค้นหาผู้เช่าหรือเลขห้อง..."
            aria-label="ค้นหาผู้เช่าหรือเลขห้อง"
        >
    </div>

    <div id="rental-state-loading" class="rt-card rt-state" hidden>กำลังโหลดข้อมูล...</div>
    <div id="rental-state-error" class="rt-state rt-state--error" hidden>
        <span id="rental-error-message">โหลดข้อมูลไม่สำเร็จ</span>
        <button type="button" id="btn-retry" class="rt-btn rt-btn--sm">ลองใหม่</button>
    </div>
    <div id="rental-state-empty" class="rt-card rt-state" hidden>ยังไม่มีรายการการเช่า</div>

    <div id="rental-lists" class="rt-stack" hidden>
        {{-- การเช่าปัจจุบัน (rt_status = ACTIVE) --}}
        <section class="rt-card rt-card--flush" aria-labelledby="active-title">
            <div class="rt-card-head">
                <h2 class="rt-card-title rt-card-title--lg" id="active-title">การเช่าปัจจุบัน</h2>
            </div>
            <div class="rt-table-wrap">
                <table class="rt-table" id="rental-table">
                    <thead>
                        <tr>
                            <th>ผู้เช่า</th>
                            <th>ห้อง</th>
                            <th>วันที่เข้า</th>
                            <th>วันที่ออก (ประมาณ)</th>
                            <th>สถานะ</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="rental-table-body"></tbody>
                </table>
            </div>
        </section>

        {{-- ประวัติการเช่า (rt_status = ENDED) --}}
        <section class="rt-card rt-card--flush" aria-labelledby="history-title">
            <div class="rt-card-head">
                <h2 class="rt-card-title rt-card-title--lg" id="history-title">ประวัติการเช่า</h2>
            </div>
            <div class="rt-table-wrap">
                <table class="rt-table" id="rental-history-table">
                    <thead>
                        <tr>
                            <th>ผู้เช่า</th>
                            <th>ห้อง</th>
                            <th>วันที่เข้า</th>
                            <th>วันที่ออก</th>
                            <th>สถานะ</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="rental-history-body"></tbody>
                </table>
            </div>
        </section>
    </div>

    <div id="rental-pagination" class="rt-pagination" hidden>
        <button type="button" id="btn-prev-page" class="rt-btn rt-btn--sm">ก่อนหน้า</button>
        <span id="pagination-info"></span>
        <button type="button" id="btn-next-page" class="rt-btn rt-btn--sm">ถัดไป</button>
    </div>
</div>

<script>
(function () {
    // ดึงทีละ 100 (สูงสุดที่ API อนุญาต) เพื่อให้แยกกำลังเช่า/สิ้นสุดแล้วฝั่งหน้าเว็บได้ครบในหน้าเดียวเท่าที่ทำได้
    const API_BASE = '/api/v1/rentals';
    const PER_PAGE = 100;

    // ค่า rt_status ยืนยันกับ backend แล้ว: ACTIVE / ENDED
    const STATUS_LABEL = {
        ACTIVE: { text: 'กำลังเช่า', cls: 'rt-badge--ok' },
        ENDED:  { text: 'สิ้นสุดแล้ว', cls: 'rt-badge--off' },
    };

    const $ = (id) => document.getElementById(id);
    const el = {
        search: $('rental-search'),
        summary: $('rental-summary'),
        banner: $('rental-banner'),
        loading: $('rental-state-loading'),
        empty: $('rental-state-empty'),
        error: $('rental-state-error'),
        errorMsg: $('rental-error-message'),
        retry: $('btn-retry'),
        lists: $('rental-lists'),
        activeBody: $('rental-table-body'),
        historyBody: $('rental-history-body'),
        pagination: $('rental-pagination'),
        paginationInfo: $('pagination-info'),
        prevBtn: $('btn-prev-page'),
        nextBtn: $('btn-next-page'),
    };

    let state = { page: 1, search: '' };
    let searchDebounce = null;
    let inFlight = false; // กันยิงซ้ำระหว่างรอ response

    // แสดง banner หลังสร้างการเช่าสำเร็จ (?created=1)
    if (new URLSearchParams(window.location.search).get('created') === '1') {
        el.banner.textContent = 'สร้างการเช่าสำเร็จ';
        el.banner.hidden = false;
        window.history.replaceState({}, '', window.location.pathname);
    }

    function showOnly(name) {
        ['loading', 'empty', 'error', 'lists'].forEach((k) => { el[k].hidden = k !== name; });
        if (name !== 'lists') el.pagination.hidden = true;
    }

    async function loadRentals() {
        if (inFlight) return;
        inFlight = true;
        showOnly('loading');

        const params = new URLSearchParams({ page: state.page, per_page: PER_PAGE });
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
            el.summary.textContent = state.search ? 'ไม่พบรายการที่ค้นหา' : 'ยังไม่มีรายการ';
            el.empty.textContent = state.search ? 'ไม่พบรายการการเช่าที่ตรงกับคำค้นหา' : 'ยังไม่มีรายการการเช่า';
            showOnly('empty');
            return;
        }

        const active = rows.filter((r) => r.rt_status === 'ACTIVE');
        const ended = rows.filter((r) => r.rt_status !== 'ACTIVE');

        el.activeBody.innerHTML = active.length
            ? active.map(activeRowHtml).join('')
            : emptyRowHtml('ไม่มีการเช่าที่กำลังดำเนินอยู่');
        el.historyBody.innerHTML = ended.length
            ? ended.map(historyRowHtml).join('')
            : emptyRowHtml('ยังไม่มีประวัติการเช่า');

        // นับเฉพาะรายการที่โหลดมาในหน้านี้ (ถ้าทั้งหมดเกิน 100 รายการจะมีปุ่มเปลี่ยนหน้า)
        const lastPage = Math.max(1, Math.ceil(meta.total / meta.per_page));
        el.summary.textContent = lastPage > 1
            ? `หน้านี้: กำลังเช่า ${active.length} รายการ · สิ้นสุดแล้ว ${ended.length} รายการ (ทั้งหมด ${meta.total} รายการ)`
            : `กำลังเช่า ${active.length} รายการ · สิ้นสุดแล้ว ${ended.length} รายการ`;

        el.paginationInfo.textContent = `หน้า ${meta.page} จาก ${lastPage}`;
        el.prevBtn.disabled = meta.page <= 1;
        el.nextBtn.disabled = meta.page >= lastPage;

        showOnly('lists');
        el.pagination.hidden = lastPage <= 1;
    }

    function emptyRowHtml(text) {
        return `<tr><td colspan="6" class="rt-muted" style="text-align:center;padding:20px;">${escapeHtml(text)}</td></tr>`;
    }

    function tenantName(r) {
        return r.tenant ? `${r.tenant.t_Fname ?? ''} ${r.tenant.t_Lname ?? ''}`.trim() : '-';
    }

    function statusBadge(r) {
        const s = STATUS_LABEL[r.rt_status] || { text: r.rt_status, cls: 'rt-badge--off' };
        return `<span class="rt-badge ${s.cls}">${escapeHtml(s.text)}</span>`;
    }

    function activeRowHtml(r) {
        const name = tenantName(r);
        const initial = name !== '-' ? name.charAt(0) : '?';
        const room = r.room ? r.room.r_name : '-';

        // ปุ่ม "ย้ายออก" ยังไม่เปิดใช้งาน — รอทำหน้า Move-out (งานถัดไป) ผูกด้วย data-action="move-out" + data-rental-id
        return `
            <tr>
                <td><div class="rt-person"><span class="rt-avatar">${escapeHtml(initial)}</span>${escapeHtml(name)}</div></td>
                <td><span class="rt-badge rt-badge--info">${escapeHtml(room)}</span></td>
                <td>${escapeHtml(r.rt_movein ?? '-')}</td>
                <td class="rt-muted">${escapeHtml(r.rt_moveout ?? '-')}</td>
                <td>${statusBadge(r)}</td>
                <td class="rt-actions">
                    <a href="/rentals/${r.rt_id}" class="rt-btn rt-btn--sm rt-btn--soft">ดูรายละเอียด</a>
                    <button type="button" class="rt-btn rt-btn--sm" data-action="move-out" data-rental-id="${r.rt_id}"
                            disabled title="ฟังก์ชันย้ายออกจะเปิดใช้งานเร็ว ๆ นี้">ย้ายออก</button>
                </td>
            </tr>
        `;
    }

    function historyRowHtml(r) {
        const name = tenantName(r);
        const room = r.room ? r.room.r_name : '-';

        return `
            <tr>
                <td>${escapeHtml(name)}</td>
                <td class="rt-link">${escapeHtml(room)}</td>
                <td>${escapeHtml(r.rt_movein ?? '-')}</td>
                <td>${escapeHtml(r.rt_moveout ?? '-')}</td>
                <td>${statusBadge(r)}</td>
                <td class="rt-actions">
                    <a href="/rentals/${r.rt_id}" class="rt-btn rt-btn--sm rt-btn--soft">ดูรายละเอียด</a>
                </td>
            </tr>
        `;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
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
