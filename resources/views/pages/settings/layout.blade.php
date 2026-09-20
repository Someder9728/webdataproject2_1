<div class="settings-page">

    <div class="container-fluid settings-container">

        {{-- PAGE HEADER --}}
        <div class="settings-page-header">

            <div class="d-flex align-items-center gap-3">

                {{-- Settings Icon --}}
                <div class="settings-header-icon">
                    <i class="bi bi-gear-fill"></i>
                </div>

                {{-- Title --}}
                <div>
                    <h1 class="settings-title">
                        Settings
                    </h1>

                    <p class="settings-subtitle">
                        จัดการข้อมูลบัญชีและการตั้งค่าของคุณ
                    </p>
                </div>
            </div>
        </div>

        {{-- SETTINGS CARD --}}
        <div class="settings-card">

            <div class="row g-0 settings-card-body">

                {{-- LEFT SIDEBAR --}}
                <aside class="col-12 col-md-3 settings-sidebar">

                    <div class="settings-sidebar-inner">

                        {{-- Sidebar Header --}}
                        <div class="settings-sidebar-header">

                            <div class="settings-section-label">
                                ACCOUNT
                            </div>

                            <div class="settings-section-subtitle">
                                การตั้งค่าบัญชี
                            </div>

                        </div>


                        {{-- Navigation --}}
                        <nav class="settings-nav">

                            {{-- PROFILE --}}
                            <a
                                href="{{ route('profile.edit') }}"
                                wire:navigate
                                class="settings-nav-item
                                    {{ request()->routeIs('profile.edit') ? 'active' : '' }}"
                            >

                                <span class="settings-nav-icon">
                                    <i class="bi bi-person"></i>
                                </span>

                                <span class="settings-nav-text">
                                    Profile
                                </span>

                                @if (request()->routeIs('profile.edit'))
                                    <i class="bi bi-chevron-right settings-nav-arrow"></i>
                                @endif

                            </a>


                            {{-- SECURITY --}}
                            <a
                                href="{{ route('security.edit') }}"
                                wire:navigate
                                class="settings-nav-item
                                    {{ request()->routeIs('security.edit') ? 'active' : '' }}"
                            >

                                <span class="settings-nav-icon">
                                    <i class="bi bi-shield-lock"></i>
                                </span>

                                <span class="settings-nav-text">
                                    Security
                                </span>

                                @if (request()->routeIs('security.edit'))
                                    <i class="bi bi-chevron-right settings-nav-arrow"></i>
                                @endif

                            </a>
                        </nav>

                        {{-- ACCOUNT INFO --}}
                        <div class="settings-account-info">

                            <div class="d-flex gap-3">

                                <div class="settings-info-icon">
                                    <i class="bi bi-info-circle"></i>
                                </div>

                                <div>

                                    <div class="settings-info-title">
                                        บัญชีของคุณ
                                    </div>

                                    <div class="settings-info-text">
                                        ตรวจสอบข้อมูลและการตั้งค่าบัญชี
                                        ของคุณได้จากเมนูด้านบน
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </aside>

                {{-- RIGHT CONTENT --}}
                <main class="col-12 col-md-9 settings-content">

                    <div class="settings-content-inner">

                        {{-- Content Header --}}
                        <div class="settings-content-header">

                            <h2 class="settings-content-title">
                                {{ $heading ?? 'Settings' }}
                            </h2>

                            @if ($subheading ?? false)

                                <p class="settings-content-subtitle">
                                    {{ $subheading }}
                                </p>

                            @endif

                        </div>

                        {{-- ACTUAL PAGE CONTENT --}}
                        <div class="settings-form-content">

                            {{ $slot }}

                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
</div>


<style>

    /* SETTINGS PAGE */
    .settings-page {
        min-height: 100vh;
        padding: 32px;
        background: #ffffff;
        color: #24344D;
    }

    .settings-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0;
    }


    /* PAGE HEADER */

    .settings-page-header {
        margin-bottom: 28px;
    }

    .settings-header-icon {
        width: 48px;
        height: 48px;
        min-width: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: #2161F5;
        color: #ffffff;
        font-size: 21px;
        box-shadow: 0 8px 20px rgba(33, 97, 245, 0.18);
    }

    .settings-title {
        margin: 0;
        color: #24344D;
        font-size: 28px;
        font-weight: 700;
        line-height: 1.2;
        letter-spacing: -0.02em;
    }

    .settings-subtitle {
        margin: 5px 0 0;
        color: #7B8CA5;
        font-size: 14px;
        line-height: 1.5;
    }


    /* MAIN CARD */
    .settings-card {
        overflow: hidden;
        border: 1px solid #DDE5EF;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 4px 18px rgba(36, 52, 77, 0.06);
    }

    .settings-card-body {
        min-height: 600px;
    }


    /* LEFT SIDEBAR */
    .settings-sidebar {
        background: #F8FAFC;
        border-right: 1px solid #E2E8F0;
    }

    .settings-sidebar-inner {
        padding: 24px 20px;
    }

    .settings-sidebar-header {
        margin-bottom: 20px;
        padding: 0 8px;
    }

    .settings-section-label {
        color: #94A3B8;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.18em;
    }

    .settings-section-subtitle {
        margin-top: 4px;
        color: #94A3B8;
        font-size: 12px;
    }


    /* SETTINGS NAVIGATION */
    .settings-nav {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .settings-nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        padding: 10px 12px;
        border-radius: 10px;
        color: #52627A;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition:
            background-color 0.15s ease,
            color 0.15s ease,
            box-shadow 0.15s ease;
    }

    .settings-nav-item:hover {
        background: #EFF6FF;
        color: #2161F5;
    }

    .settings-nav-item.active {
        background: #2161F5;
        color: #ffffff;
        box-shadow: 0 6px 14px rgba(33, 97, 245, 0.18);
    }

    .settings-nav-icon {
        width: 36px;
        height: 36px;
        min-width: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 9px;
        background: #ffffff;
        color: #94A3B8;
        font-size: 17px;
        box-shadow: 0 1px 4px rgba(36, 52, 77, 0.06);
        transition:
            background-color 0.15s ease,
            color 0.15s ease;
    }

    .settings-nav-item:hover .settings-nav-icon {
        color: #2161F5;
    }

    .settings-nav-item.active .settings-nav-icon {
        background: rgba(255, 255, 255, 0.15);
        color: #ffffff;
        box-shadow: none;
    }

    .settings-nav-text {
        flex: 1;
    }

    .settings-nav-arrow {
        font-size: 13px;
        opacity: 0.9;
    }

    /* ACCOUNT INFO */
    .settings-account-info {
        margin-top: 32px;
        padding: 14px;
        border: 1px solid #DBEAFE;
        border-radius: 10px;
        background: #EFF6FF;
    }

    .settings-info-icon {
        flex-shrink: 0;
        margin-top: 1px;
        color: #3B82F6;
        font-size: 18px;
    }

    .settings-info-title {
        color: #1D4ED8;
        font-size: 12px;
        font-weight: 600;
    }

    .settings-info-text {
        margin-top: 4px;
        color: #2563EB;
        font-size: 11px;
        line-height: 1.7;
    }


    /* RIGHT CONTENT */
    .settings-content {
        min-width: 0;

        background: #ffffff;
    }

    .settings-content-inner {
        padding: 44px;
    }

    .settings-content-header {
        margin-bottom: 28px;
        padding-bottom: 22px;
        border-bottom: 1px solid #E2E8F0;
    }

    .settings-content-title {
        margin: 0;
        color: #24344D;
        font-size: 24px;
        font-weight: 700;
        line-height: 1.3;
        letter-spacing: -0.015em;
    }

    .settings-content-subtitle {
        max-width: 600px;
        margin: 8px 0 0;
        color: #7B8CA5;
        font-size: 14px;
        line-height: 1.7;
    }

    .settings-form-content {
        width: 100%;
        max-width: 680px;
        color: #42526B;
    }


    /* FORM ELEMENTS */
    .settings-form-content label {
        display: block;
        margin-bottom: 8px;
        color: #42526B;
        font-size: 14px;
        font-weight: 600;
    }

    .settings-form-content input,
    .settings-form-content textarea,
    .settings-form-content select {
        border-color: #CBD5E1;
        background: #ffffff;
        color: #172033;
    }

    .settings-form-content input::placeholder,
    .settings-form-content textarea::placeholder {
        color: #94A3B8;
    }

    .settings-form-content input:focus,
    .settings-form-content textarea:focus,
    .settings-form-content select:focus {
        border-color: #2161F5;
        box-shadow: 0 0 0 0.2rem rgba(33, 97, 245, 0.12);
    }

    .settings-form-content button {
        cursor: pointer;
    }


    /* RESPONSIVE */
    @media (max-width: 767.98px) {

        .settings-page {
            padding: 20px 16px;
        }

        .settings-title {
            font-size: 24px;
        }

        .settings-header-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;
        }

        .settings-sidebar {
            border-right: 0;
            border-bottom: 1px solid #E2E8F0;
        }

        .settings-sidebar-inner {
            padding: 20px 16px;
        }

        .settings-content-inner {
            padding: 28px 20px;
        }

        .settings-card-body {
            min-height: auto;
        }

    }

    @media (min-width: 768px) and (max-width: 991.98px) {

        .settings-page {
            padding: 24px;
        }

        .settings-content-inner {
            padding: 32px;
        }

        .settings-sidebar-inner {
            padding: 20px 14px;
        }

    }
</style>