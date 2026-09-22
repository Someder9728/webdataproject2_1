<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')

    <style>
    :root {
        --sidebar-bg: #162D4A;
        --sidebar-border: #2E425C;
        --sidebar-active: #1A3D6F;
        --sidebar-text: #90A1B9;
    }

    html,
    body {
        margin: 0;
        padding: 0;
        min-height: 100%;
    }

    body {
        background-color: #f8fafc;
    }

    /* SIDEBAR */

    .app-sidebar {
        width: 250px;
        min-height: 100vh;
        background-color: var(--sidebar-bg);
        border-right: 1px solid var(--sidebar-border);
    }

    .app-brand {
        height: 70px;
        display: flex;
        align-items: center;
        padding: 0 22px;
        color: #ffffff;
        text-decoration: none;
        font-size: 20px;
        font-weight: 700;
        border-bottom: 1px solid var(--sidebar-border);
    }

    .app-brand:hover {
        color: #ffffff;
    }

    .sidebar-title {
        padding: 24px 20px 10px;
        color: var(--sidebar-text);
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .05em;
    }

    .sidebar-link {
        display: flex;
        align-items: center;
        margin: 3px 12px;
        padding: 11px 14px;
        border-radius: 8px;
        color: var(--sidebar-text);
        text-decoration: none;
        font-size: 14px;
        transition: .2s ease;
    }

    .sidebar-link:hover {
        color: #ffffff;
        background-color: rgba(255, 255, 255, 0.08);
    }

    .sidebar-link.active {
        color: #ffffff;
        background-color: var(--sidebar-active);
    }

    .sidebar-link i {
        width: 20px;
        font-size: 16px;
    }

    /* USER PANEL*/

    .sidebar-user {
        margin-top: auto;
        padding: 16px;
        border-top: 1px solid var(--sidebar-border);
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background-color: var(--sidebar-active);
        color: #ffffff;
        font-size: 14px;
        font-weight: 600;
    }

    .user-name {
        color: #ffffff;
        font-size: 14px;
        font-weight: 600;
    }

    .user-email {
        max-width: 145px;
        color: var(--sidebar-text);
        font-size: 12px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* TOP NAVBAR */
    .app-navbar {
        height: 70px;
        background-color: #ffffff;
        border-bottom: 1px solid #e5e7eb;
    }

    .navbar-title {
        color: #162D4A;
        font-size: 18px;
        font-weight: 600;
    }

    /* CONTENT */

    .app-content {
        min-height: calc(100vh - 70px);
        padding: 28px;
    }
    </style>
</head>

<body>

    <div class="d-flex min-vh-100">

        {{-- SIDEBAR --}}
        <aside class="app-sidebar d-flex flex-column flex-shrink-0">

            {{-- Logo / Brand --}}
            <a href="{{ route('dashboard') }}" class="app-brand">
                หอพักสุขสบาย
            </a>


            {{-- Main Menu --}}
            <nav class="mt-1">

                <div class="sidebar-title">
                    เมนูหลัก
                </div>

                {{-- Dashboard --}}
                <a href="{{ route('dashboard') }}"
                    class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid"></i>
                    <span>Dashboard</span>
                </a>

            </nav>


            {{-- User Area --}}
            <div class="sidebar-user">

                <div class="d-flex align-items-center gap-3">

                    <div class="user-avatar">
                        {{ auth()->user()->initials() }}
                    </div>

                    <div class="flex-grow-1 overflow-hidden">

                        <div class="user-name">
                            {{ auth()->user()->name }}
                        </div>

                        <div class="user-email">
                            {{ auth()->user()->email }}
                        </div>

                    </div>

                </div>


                {{-- Settings --}}
                <a href="{{ route('profile.edit') }}" class="sidebar-link mt-3">
                    <i class="bi bi-gear"></i>
                    <span>Settings</span>
                </a>


                {{-- Logout --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit" class="sidebar-link w-100 border-0 bg-transparent text-start">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Log out</span>
                    </button>
                </form>

            </div>

        </aside>


        {{-- MAIN AREA --}}
        <div class="flex-grow-1">

            {{-- Top Navbar --}}
            <nav class="app-navbar navbar px-4">

                <div class="container-fluid p-0">

                    <span class="navbar-title">
                        {{ $title ?? 'Dashboard' }}
                    </span>

                    <div class="d-flex align-items-center gap-2">

                        <span class="text-secondary small">
                            {{ auth()->user()->name }}
                        </span>

                        <div class="user-avatar bg-secondary">
                            {{ auth()->user()->initials() }}
                        </div>

                    </div>

                </div>

            </nav>


            {{-- Page Content --}}
            <main class="app-content">
                {{ $slot }}
            </main>

        </div>

    </div>


    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>