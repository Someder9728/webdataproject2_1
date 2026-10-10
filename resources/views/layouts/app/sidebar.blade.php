@php
$user = auth()->user();
$isAdmin = $user && $user->u_role === 'admin';

// ข้อมูลที่แสดงด้านล่าง Sidebar
if ($isAdmin) {
$displayName = 'ผู้ดูแลระบบ';
$displayRole = 'Administrator';
$roleLabel = 'ผู้ดูแลระบบ (Admin)';
} else {
$displayName = $user?->tenant
? trim($user->tenant->t_Fname . ' ' . $user->tenant->t_Lname)
: ($user?->u_username ?? 'ผู้ใช้งาน');

$displayRole = 'Tenant';
$roleLabel = 'ผู้เช่า (User)';
}

$initials = $user?->initials() ?? 'U';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        {{ $title ?? 'หอพักสุขสบาย' }}
    </title>

    {{-- Bootstrap --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Bootstrap Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
    :root {
        --sidebar-bg: #162D4A;
        --sidebar-border: #2E425C;
        --sidebar-active: #1A3D6F;
        --sidebar-text: #90A1B9;
        --sidebar-white: #FFFFFF;
        --sidebar-blue: #2F80FF;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        background: #F5F7FA;
        color: #162D4A;
        font-family:
            "Segoe UI",
            Tahoma,
            Arial,
            sans-serif;
    }

    /* SIDEBAR */
    .app-sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: 230px;
        height: 100vh;
        background: var(--sidebar-bg);
        border-right: 1px solid var(--sidebar-border);
        display: flex;
        flex-direction: column;
        z-index: 1000;
    }

    /* BRAND */

    .sidebar-brand {
        min-height: 84px;
        display: flex;
        align-items: center;
        padding: 18px 14px;
        border-bottom: 1px solid var(--sidebar-border);
    }

    .sidebar-brand-icon {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border-radius: 8px;
        background: var(--sidebar-blue);
        color: white;
        font-size: 19px;
    }

    .sidebar-brand-text {
        margin-left: 10px;
        line-height: 1.15;
    }

    .sidebar-brand-name {
        color: #FFFFFF;
        font-size: 14px;
        font-weight: 700;
        margin: 0;
    }

    .sidebar-brand-subtitle {
        color: #B7C7DC;
        font-size: 11px;
        margin-top: 3px;
    }

    /* ROLE BADGE */
    .sidebar-role-area {
        padding: 10px 14px;
        border-bottom: 1px solid var(--sidebar-border);
    }

    .sidebar-role {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 9px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }

    .sidebar-role.admin {
        color: #FFC107;
        background: rgba(255, 193, 7, 0.10);
    }

    .sidebar-role.user {
        color: #55A7FF;
        background: rgba(47, 128, 255, 0.18);
    }

    .sidebar-role-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }

    .admin .sidebar-role-dot {
        background: #FFC107;
    }

    .user .sidebar-role-dot {
        background: #2F80FF;
    }

    /* MENU */
    .sidebar-menu-wrapper {
        flex: 1;
        overflow-y: auto;
        padding: 14px 8px;
    }

    .sidebar-section-title {
        color: #71869F;
        font-size: 10px;
        font-weight: 500;
        padding: 0 6px;
        margin-bottom: 7px;
        letter-spacing: 0.2px;
    }

    .sidebar-menu {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .sidebar-menu-item {
        min-height: 40px;
        display: flex;
        align-items: center;
        padding: 0 13px;
        border-radius: 8px;
        color: var(--sidebar-text);
        text-decoration: none;
        font-size: 13px;
        font-weight: 400;
        transition:
            background 0.15s ease,
            color 0.15s ease;
    }

    .sidebar-menu-item i {
        width: 22px;
        margin-right: 7px;
        font-size: 17px;
        text-align: center;
    }

    .sidebar-menu-item:hover {
        color: #FFFFFF;
        background: rgba(255, 255, 255, 0.05);
    }

    .sidebar-menu-item.active {
        color: #FFFFFF;
        background: #2168F3;
        font-weight: 600;
        box-shadow:
            0 3px 8px rgba(0, 0, 0, 0.12);
    }

    /* BOTTOM USER AREA */
    .sidebar-bottom {
        border-top: 1px solid var(--sidebar-border);
        padding: 14px 13px 13px;
    }

    .sidebar-user {
        display: flex;
        align-items: center;
        margin-bottom: 9px;
    }

    .sidebar-user-link {
        display: flex;
        align-items: center;
        padding: 5px;
        margin: -5px -5px 9px;
        border-radius: 10px;
        color: inherit;
        text-decoration: none;
        transition: background 0.15s ease;
    }

    .sidebar-user-link:hover,
    .sidebar-user-link:focus-visible {
        background: rgba(255, 255, 255, 0.08);
        outline: none;
    }

    .sidebar-avatar {
        width: 34px;
        height: 34px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #2F80FF;
        color: white;
        font-size: 12px;
        font-weight: 700;
    }

    .sidebar-user-info {
        min-width: 0;
        margin-left: 9px;
    }

    .sidebar-user-name {
        color: #FFFFFF;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sidebar-user-role {
        color: #A5B7CC;
        font-size: 10px;
        margin-top: 2px;
    }

    .sidebar-logout {
        width: 100%;
        display: flex;
        align-items: center;
        padding: 7px 4px;
        border: 0;
        background: transparent;
        color: var(--sidebar-text);
        font-size: 12px;
        text-decoration: none;
        cursor: pointer;
    }

    .sidebar-logout i {
        width: 20px;
        margin-right: 7px;
        font-size: 15px;
    }

    .sidebar-logout:hover {
        color: #FFFFFF;
    }

    /* MAIN CONTENT */
    .app-main {
        min-height: 100vh;
        margin-left: 230px;
    }

    .app-topbar {
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 26px;
        background: #FFFFFF;
        border-bottom: 1px solid #DEE3E8;
    }

    .app-page-title {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #162D4A;
        font-size: 18px;
        font-weight: 600;
    }

    .app-brand-name {
        color: #162D4A;
        font-size: 18px;
        font-weight: 700;
    }

    .app-breadcrumb {
        color: #94A3B8;
        font-size: 18px;
        font-weight: 400;
    }

    .app-current-page {
        color: #475569;
        font-size: 15px;
        font-weight: 500;
    }

    .app-top-avatar {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #6C7782;
        color: #FFFFFF;
        font-size: 12px;
        font-weight: 600;
    }

    .app-content {
        min-height: calc(100vh - 60px);
        background: #F5F7FA;
    }

    .app-global-search {
        position: relative;
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 0 1 430px;
        width: auto;
        min-width: 140px;
        margin-left: auto;
        margin-right: 18px;
        padding: 0 12px;
        border: 1px solid #d8e0e9;
        border-radius: 8px;
        background: #fff;
        color: #64748b;
    }

    .app-global-search input {
        width: 100%;
        height: 38px;
        border: 0;
        outline: 0;
        color: #172033;
        background: transparent;
        font-size: 13px;
    }

    .global-search-results {
        position: absolute;
        z-index: 1100;
        top: calc(100% + 6px);
        left: 0;
        right: 0;
        max-height: 65vh;
        overflow: auto;
        border: 1px solid #d8e0e9;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .16);
    }

    .global-search-results a, .global-search-message {
        display: block;
        padding: 10px 13px;
        color: #334155;
        font-size: 13px;
        text-decoration: none;
    }

    .global-search-results a + a { border-top: 1px solid #eef2f6; }
    .global-search-results a:hover, .global-search-results a:focus { background: #f1f5f9; }
    .global-search-results small { display: block; color: #64748b; }

    @media (max-width: 700px) {
        .app-global-search { width: min(56vw, 250px); margin-right: 10px; }
        .app-global-search input { font-size: 12px; }
        .app-global-search input::placeholder { color: transparent; }
    }
    </style>
    @livewireStyles
</head>


<body>

    {{-- SIDEBAR --}}
    <aside class="app-sidebar">

        {{-- BRAND --}}
        <div class="sidebar-brand">

            <div class="sidebar-brand-icon">
                <i class="bi bi-building"></i>
            </div>

            <div class="sidebar-brand-text">

                <div class="sidebar-brand-name">
                    หอพักสุขสบาย
                </div>

                <div class="sidebar-brand-subtitle">
                    ระบบจัดการหอพัก
                </div>

            </div>

        </div>


        {{-- ROLE --}}
        <div class="sidebar-role-area">

            @if($isAdmin)

            <div class="sidebar-role admin">
                <span class="sidebar-role-dot"></span>
                <span>ผู้ดูแลระบบ (Admin)</span>
            </div>

            @else

            <div class="sidebar-role user">
                <span class="sidebar-role-dot"></span>
                <span>ผู้เช่า (User)</span>
            </div>

            @endif

        </div>


        {{-- MENU --}}
        <div class="sidebar-menu-wrapper">

            <div class="sidebar-section-title">
                เมนูหลัก
            </div>

            <div class="sidebar-menu">

                @if($isAdmin)

                {{-- Dashboard --}}
                <a href="{{ route('dashboard') }}"
                    class="sidebar-menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-house-door"></i>
                    <span>Dashboard</span>
                </a>

                {{-- ผู้เช่า --}}
                <a href="{{ route('tenants.index') }}"
                    class="sidebar-menu-item {{ request()->routeIs('tenants.*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i>
                    <span>ผู้เช่า</span>
                </a>

                {{-- ห้องพัก --}}
                <a href="{{ route('rooms.index') }}"
                    class="sidebar-menu-item {{ request()->routeIs('rooms.*') ? 'active' : '' }}">
                    <i class="bi bi-building"></i>
                    <span>ห้องพัก</span>
                </a>

                {{-- การเช่า --}}
                <a href="{{ route('rentals.index') }}"
                    class="sidebar-menu-item {{ request()->routeIs('rentals.*') ? 'active' : '' }}">
                    <i class="bi bi-key"></i>
                    <span>การเช่า</span>
                </a>

                {{-- สัญญาเช่า --}}
                <a href="{{ route('invoices.index') }}" class="sidebar-menu-item">ค่าเช่าและการชำระเงิน</a>
                <a href="{{ route('meters.index') }}" class="sidebar-menu-item">มิเตอร์และการใช้งาน</a>
                <a href="{{ route('repairs.index') }}" class="sidebar-menu-item">แจ้งซ่อม</a>
                <a href="{{ route('contracts.index') }}"
                    class="sidebar-menu-item {{ request()->routeIs('contracts.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>สัญญาเช่า</span>
                </a>

                {{-- ค่าน้ำ-ค่าไฟ --}}
                <a href="#" class="sidebar-menu-item">
                    <i class="bi bi-lightning-charge"></i>
                    <span>ค่าน้ำ-ค่าไฟ</span>
                </a>

                {{-- ใบแจ้งหนี้ / การชำระ --}}
                <a href="#" class="sidebar-menu-item">
                    <i class="bi bi-credit-card"></i>
                    <span>ใบแจ้งหนี้ / การชำระ</span>
                </a>

                {{-- แจ้งซ่อม --}}
                <a href="#" class="sidebar-menu-item">
                    <i class="bi bi-gear"></i>
                    <span>แจ้งซ่อม</span>
                </a>

                @else

                {{-- Dashboard --}}
                <a href="{{ route('my.contracts') }}" class="sidebar-menu-item">สัญญาของฉัน</a>
                <a href="{{ route('invoices.index') }}" class="sidebar-menu-item">ค่าเช่าและการชำระเงิน</a>
                <a href="{{ route('repairs.index') }}" class="sidebar-menu-item">แจ้งซ่อม</a>
                <a href="{{ route('user.dashboard') }}" class="sidebar-menu-item active">
                    <i class="bi bi-house-door"></i>
                    <span>Dashboard ของฉัน</span>
                </a>

                {{-- การเช่าของฉัน --}}
                <a href="#" class="sidebar-menu-item">
                    <i class="bi bi-key"></i>
                    <span>การเช่าของฉัน</span>
                </a>

                {{-- สัญญาของฉัน --}}
                <a href="#" class="sidebar-menu-item">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>สัญญาของฉัน</span>
                </a>

                {{-- ค่าน้ำ-ค่าไฟของฉัน --}}
                <a href="#" class="sidebar-menu-item">
                    <i class="bi bi-lightning-charge"></i>
                    <span>ค่าน้ำ-ค่าไฟของฉัน</span>
                </a>

                {{-- ค่าเช่าและการชำระเงิน --}}
                <a href="#" class="sidebar-menu-item">
                    <i class="bi bi-credit-card"></i>
                    <span>ค่าเช่าและการชำระเงิน</span>
                </a>

                {{-- แจ้งซ่อม --}}
                <a href="#" class="sidebar-menu-item">
                    <i class="bi bi-gear"></i>
                    <span>แจ้งซ่อม</span>
                </a>

                {{-- ข้อมูลส่วนตัว --}}
                <a href="#" class="sidebar-menu-item">
                    <i class="bi bi-person"></i>
                    <span>ข้อมูลส่วนตัว</span>
                </a>

                @endif

            </div>

        </div>


        {{-- BOTTOM USER --}}
        <div class="sidebar-bottom">

            @if ($isAdmin)
            <a href="{{ route('admin.accounts') }}" class="sidebar-user sidebar-user-link" aria-label="เปิดหน้าจัดการบัญชีผู้เช่า">
            @else
            <div class="sidebar-user">
            @endif

                <div class="sidebar-avatar">
                    {{ $initials }}
                </div>

                <div class="sidebar-user-info">

                    <div class="sidebar-user-name">
                        {{ $displayName }}
                    </div>

                    <div class="sidebar-user-role">
                        {{ $displayRole }}
                    </div>

                </div>

            @if ($isAdmin)
            </a>
            @else
            </div>
            @endif


            {{-- Logout --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit" class="sidebar-logout">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>ออกจากระบบ</span>
                </button>

            </form>

        </div>

    </aside>

    {{-- MAIN --}}
    <main class="app-main">

        <div class="app-topbar">

            {{-- Dynamic Header --}}
            <div class="app-page-title">

                <span class="app-brand-name">
                    หอพักสุขสบาย
                </span>

                <span class="app-breadcrumb">
                    /
                </span>

                <span class="app-current-page">
                    {{ $title ?? 'Dashboard' }}
                </span>

            </div>

            @if($isAdmin)
                <div class="app-global-search" role="search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input id="global-search-input" type="search" maxlength="100" placeholder="ค้นหาผู้เช่า ห้อง การเช่า และสัญญา..."
                        aria-label="ค้นหาทั่วทั้งระบบ" autocomplete="off" aria-controls="global-search-results">
                    <div id="global-search-results" class="global-search-results d-none" role="listbox" aria-live="polite"></div>
                </div>
            @endif


            {{-- User Avatar --}}
            <div class="app-top-avatar">
                {{ $initials }}
            </div>

        </div>


        <div class="app-content">
            {{ $slot }}
        </div>

    </main>

    @vite(['resources/js/app.js'])
    @stack('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    @livewireScripts
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('global-search-input');
        const results = document.getElementById('global-search-results');
        if (!input || !results) return;

        let timer;
        let controller;
        const escapeHtml = value => {
            const node = document.createElement('span');
            node.textContent = value ?? '';
            return node.innerHTML;
        };
        const statusLabel = status => ({
            VACANT: 'ว่าง', OCCUPIED: 'มีผู้พัก',
            ACTIVE: 'กำลังเช่า', ENDED: 'สิ้นสุดแล้ว',
        })[status] ?? status ?? '';

        input.addEventListener('input', () => {
            clearTimeout(timer);
            controller?.abort();
            const term = input.value.trim();
            if (term.length < 2) {
                results.classList.add('d-none');
                results.replaceChildren();
                return;
            }

            results.classList.remove('d-none');
            results.innerHTML = '<div class="global-search-message">กำลังค้นหา...</div>';
            timer = setTimeout(async () => {
                controller = new AbortController();
                const params = new URLSearchParams({ search: term, per_page: 5 });
                const resources = [
                    { path: '/api/v1/tenants', label: 'ผู้เช่า' },
                    { path: '/api/v1/rooms', label: 'ห้องพัก' },
                    { path: '/api/v1/rentals', label: 'การเช่า' },
                ];
                try {
                    const groups = await Promise.all(resources.map(async resource => {
                        const response = await fetch(`${resource.path}?${params}`, {
                            credentials: 'same-origin',
                            headers: { Accept: 'application/json' },
                            signal: controller.signal,
                        });
                        if (response.status === 401) {
                            window.location.assign('/login');
                            throw new Error('กรุณาเข้าสู่ระบบใหม่');
                        }
                        if (!response.ok) throw new Error('ค้นหาบางรายการไม่สำเร็จ');
                        const body = await response.json();
                        return { ...resource, rows: body.data ?? [] };
                    }));
                    const hits = groups.flatMap(group => group.rows.map(row => {
                        if (group.label === 'ผู้เช่า') {
                            return { label: group.label, title: `${row.t_Fname ?? ''} ${row.t_Lname ?? ''}`.trim(), detail: row.t_tel ?? '', href: `/tenants/${row.t_id}/edit` };
                        }
                        if (group.label === 'ห้องพัก') {
                            return { label: group.label, title: `ห้อง ${row.r_name ?? row.r_id}`, detail: `${row.r_type ?? ''} · ${statusLabel(row.r_status)}`, href: `/rooms/${row.r_id}/edit` };
                        }
                        const person = `${row.tenant?.t_Fname ?? ''} ${row.tenant?.t_Lname ?? ''}`.trim();
                        return {
                            label: row.contract?.c_number ? 'สัญญาเช่า' : group.label,
                            title: `${person || 'ผู้เช่า'} · ห้อง ${row.room?.r_name ?? '—'}`,
                            detail: row.contract?.c_number ?? `${row.rt_movein ?? ''} · ${row.rt_status ?? ''}`,
                            href: row.contract?.c_number
                                ? `/contracts?search=${encodeURIComponent(term)}`
                                : `/rentals?search=${encodeURIComponent(term)}`,
                        };
                    }));

                    if (!hits.length) {
                        results.innerHTML = '<div class="global-search-message">ไม่พบข้อมูลที่ตรงกับคำค้น</div>';
                        return;
                    }
                    results.innerHTML = hits.slice(0, 12).map(hit => `<a role="option" href="${escapeHtml(hit.href)}"><strong>${escapeHtml(hit.title)}</strong><small>${escapeHtml(hit.label)}${hit.detail ? ` · ${escapeHtml(hit.detail)}` : ''}</small></a>`).join('');
                } catch (error) {
                    if (error.name === 'AbortError') return;
                    results.innerHTML = `<div class="global-search-message text-danger">${escapeHtml(error.message || 'ค้นหาไม่สำเร็จ')}</div>`;
                }
            }, 250);
        });

        document.addEventListener('click', event => {
            if (!event.target.closest('.app-global-search')) results.classList.add('d-none');
        });
        input.addEventListener('focus', () => {
            if (input.value.trim().length >= 2) results.classList.remove('d-none');
        });
    });
    </script>
</body>

</html>
