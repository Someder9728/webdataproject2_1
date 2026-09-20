<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')

    <style>
        .sidebar-link,
        .sidebar-bottom-link {
            color: #90A1B9;
            transition: all .2s ease;
            cursor: pointer;
        }

        .sidebar-link:hover,
        .sidebar-bottom-link:hover {
            background-color: #304966;
            color: #ffffff;
        }

        .sidebar-link.active {
            background-color: #155DFC;
            color: #ffffff;
        }

        .sidebar-link.active:hover {
            background-color: #155DFC;
            color: #ffffff;
        }

        .cursor-pointer {
            cursor: pointer !important;
        }
    </style>
</head>

<body class="bg-light">

    {{-- sidebar --}}
    <aside id="sidebar" class="d-flex flex-column position-fixed top-0 start-0 vh-100 text-white"
        style="
            width: 260px;
            background-color: #162D4A;
            z-index: 1040;
        ">

        {{-- Sidebar Header --}}
        <div class="p-4">

            {{-- Logo --}}
            <a href="{{ route('dashboard') }}" wire:navigate
                class="text-decoration-none text-white d-flex align-items-center gap-3">

                {{-- Logo Icon --}}
                <div class="d-flex align-items-center justify-content-center rounded-3"
                    style="
                        width: 42px;
                        height: 42px;
                        background-color: #155DFC;
                    ">
                    <i class="bi bi-buildings-fill"></i>
                </div>

                {{-- Logo Text --}}
                <div class="lh-sm">

                    <div class="fw-bold">
                        หอพักสุขสบาย
                    </div>

                    <small style="color: #90A1B9;">
                        ระบบจัดการหอพัก
                    </small>

                </div>

            </a>

        </div>

        {{-- Admin Badge --}}
        <div class="px-4 py-3 border-top border-bottom" style="border-color: #2E425C !important;">

            <span class="badge rounded-pill d-inline-flex align-items-center gap-2 px-3 py-2"
                style="
                    background-color: #3B4A36;
                    color: #FACC15;
                    font-size: 12px;
                ">

                <span class="rounded-circle"
                    style="
                        width: 6px;
                        height: 6px;
                        background-color: #FACC15;"></span>
                ผู้ดูแลระบบ (Admin)
            </span>
        </div>


        {{-- Navigation --}}
        <nav class="flex-grow-1 overflow-auto px-3 py-4">

            <div class="text-uppercase fw-semibold mb-3 px-2"
                style="
                    color: #90A1B9;
                    font-size: 11px;
                    letter-spacing: .5px;
                ">
                เมนูหลัก
            </div>


            {{-- Dashboard --}}
            <a href="{{ route('dashboard') }}" wire:navigate
                class="
                    sidebar-link
                    d-flex
                    align-items-center
                    gap-3
                    text-decoration-none
                    rounded-3
                    px-3
                    py-2
                    mb-1
                    {{ request()->routeIs('dashboard') ? 'active' : '' }}
                ">
                <i class="bi bi-house-door fs-5"></i>

                <span>Dashboard</span>
            </a>


            {{-- ผู้เช่า --}}
            <a href="#"
                class="
                    sidebar-link
                    d-flex
                    align-items-center
                    gap-3
                    text-decoration-none
                    rounded-3
                    px-3
                    py-2
                    mb-1
                ">
                <i class="bi bi-people fs-5"></i>

                <span>ผู้เช่า</span>
            </a>


            {{-- ห้องพัก --}}
            <a href="#"
                class="
                    sidebar-link
                    d-flex
                    align-items-center
                    gap-3
                    text-decoration-none
                    rounded-3
                    px-3
                    py-2
                    mb-1
                ">
                <i class="bi bi-building fs-5"></i>

                <span>ห้องพัก</span>
            </a>

            {{-- การเช่า --}}
            <a href="#"
                class="
                    sidebar-link
                    d-flex
                    align-items-center
                    gap-3
                    text-decoration-none
                    rounded-3
                    px-3
                    py-2
                    mb-1
                ">
                <i class="bi bi-key fs-5"></i>

                <span>การเช่า</span>
            </a>

            {{-- ค่าน้ำ-ค่าไฟ --}}
            <a href="#"
                class="
                    sidebar-link
                    d-flex
                    align-items-center
                    gap-3
                    text-decoration-none
                    rounded-3
                    px-3
                    py-2
                    mb-1
                ">
                <i class="bi bi-lightning-charge fs-5"></i>

                <span>ค่าน้ำ-ค่าไฟ</span>
            </a>

            {{-- ใบแจ้งหนี้ / การชำระ --}}
            <a href="#"
                class="
                    sidebar-link
                    d-flex
                    align-items-center
                    gap-3
                    text-decoration-none
                    rounded-3
                    px-3
                    py-2
                    mb-1
                ">
                <i class="bi bi-credit-card fs-5"></i>

                <span>ใบแจ้งหนี้ / การชำระ</span>
            </a>

            {{-- แจ้งซ่อม --}}
            <a href="#"
                class="
                    sidebar-link
                    d-flex
                    align-items-center
                    gap-3
                    text-decoration-none
                    rounded-3
                    px-3
                    py-2
                    mb-1
                ">
                <i class="bi bi-tools fs-5"></i>

                <span>แจ้งซ่อม</span>
            </a>

        </nav>

        {{-- User Footer --}}
        <div class="border-top p-3" style="border-color: #2E425C !important;">

            {{-- User Information --}}
            <div class="d-flex align-items-center gap-3 mb-3">

                {{-- Avatar --}}
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
                    style="
                        width: 40px;
                        height: 40px;
                        background-color: #2D7FF9;
                    ">
                    {{ auth()->user()->initials() }}
                </div>

                {{-- Name / Role --}}
                <div class="min-w-0">

                    <div class="text-white fw-semibold text-truncate">
                        ผู้ดูแลระบบ
                    </div>

                    <div class="small text-truncate" style="color: #90A1B9;">
                        Administrator
                    </div>

                </div>

            </div>

            {{-- Settings --}}
            <a href="{{ route('profile.edit') }}" wire:navigate
                class="
                    sidebar-bottom-link
                    d-flex
                    align-items-center
                    gap-3
                    text-decoration-none
                    rounded-3
                    px-3
                    py-2
                    mb-1
                ">
                <i class="bi bi-gear fs-5"></i>

                <span>Settings</span>
            </a>

            {{-- Logout --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit"
                    class="
                        sidebar-bottom-link
                        d-flex
                        align-items-center
                        gap-3
                        w-100
                        border-0
                        rounded-3
                        px-3
                        py-2
                        bg-transparent
                        text-start
                        cursor-pointer
                    ">

                    <i class="bi bi-box-arrow-right fs-5"></i>

                    <span>ออกจากระบบ</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- Main Area --}}
    <div style="
            margin-left: 260px;
            min-height: 100vh;
        ">

        {{-- Desktop Header --}}
        <header class="bg-white border-bottom d-none d-lg-flex align-items-center justify-content-between px-4"
            style="height: 70px;">

            {{-- Breadcrumb --}}
            <div class="d-flex align-items-center gap-2 small">

                <span style="color: #90A1B9;">
                    หอพักสุขสบาย
                </span>

                <span style="color: #90A1B9;">
                    /
                </span>

                <span class="fw-semibold" style="color: #162D4A;">
                    Dashboard
                </span>

            </div>


            {{-- Right Side --}}
            <div class="d-flex align-items-center gap-4">

                {{-- Notification --}}
                <button type="button" class="btn border-0 p-2" style="color: #162D4A;">
                    <i class="bi bi-bell fs-5"></i>
                </button>

                {{-- Divider --}}
                <div
                    style="
                        width: 1px;
                        height: 28px;
                        background-color: #D9E2EC;
                    ">
                </div>

                {{-- Admin Information --}}
                <div class="d-flex align-items-center gap-3">

                    {{-- Avatar --}}
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                        style="
                            width: 36px;
                            height: 36px;
                            background-color: #1E3A5F;
                            font-size: 12px;
                        ">
                        {{ auth()->user()->initials() }}
                    </div>

                    {{-- Name --}}
                    <div>

                        <div class="small fw-semibold" style="color: #162D4A;">
                            ผู้ดูแลระบบ
                        </div>

                        <div
                            style="
                                color: #90A1B9;
                                font-size: 11px;
                            ">
                            Administrator
                        </div>
                    </div>
                </div>
            </div>
        </header>

        {{-- Mobile Header --}}
        <header class="bg-white border-bottom d-flex d-lg-none align-items-center justify-content-between px-3"
            style="height: 60px;">

            {{-- Mobile Menu --}}
            <button type="button" class="btn border-0" onclick="toggleSidebar()">
                <i class="bi bi-list fs-3"></i>
            </button>

            {{-- Logo --}}
            <div class="fw-bold" style="color: #162D4A;">
                หอพักสุขสบาย
            </div>

            {{-- User --}}
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                style="
                    width: 36px;
                    height: 36px;
                    background-color: #2D7FF9;
                    font-size: 12px;
                ">
                {{ auth()->user()->initials() }}
            </div>
        </header>

        {{-- Page Content --}}
        <main class="p-4 p-lg-5">
            {{ $slot }}
        </main>
    </div>

    {{-- Mobile Sidebar overlay --}}
    <div id="sidebarOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-none"
        style="
            background: rgba(0, 0, 0, .45);
            z-index: 1035;
        "
        onclick="toggleSidebar()">
    </div>

    <style>
        @media (max-width: 991.98px) {
            #sidebar {
                transform: translateX(-100%);
                transition: transform .25s ease;
            }

            #sidebar.show {
                transform: translateX(0);
            }

            #sidebarOverlay.show {
                display: block !important;
            }

            body.sidebar-open {
                overflow: hidden;
            }

            #sidebar+div {
                margin-left: 0 !important;
            }

        }
    </style>

    {{-- Mobile Sidebar Scripts --}}
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
            document.body.classList.toggle('sidebar-open');

        }
    </script>

    {{-- Toast --}}
    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist


    {{-- Flux Scripts --}}
    @fluxScripts

</body>

</html>
