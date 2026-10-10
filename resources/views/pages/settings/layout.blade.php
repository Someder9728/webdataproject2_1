{{-- ========================================================= --}}
{{-- SETTINGS SHARED LAYOUT --}}
{{-- ========================================================= --}}

@once
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endonce

<div class="settings-page">

    {{-- ===================================================== --}}
    {{-- HEADER --}}
    {{-- ===================================================== --}}

    <div class="settings-header">

        <div class="settings-header-icon">
            <i class="bi bi-gear-fill"></i>
        </div>

        <div class="settings-header-text">
            <h1>Settings</h1>

            <p>
                จัดการข้อมูลบัญชีและการตั้งค่าของคุณ
            </p>
        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- MAIN CARD --}}
    {{-- ===================================================== --}}

    <div class="settings-card">

        {{-- ================================================= --}}
        {{-- LEFT MENU --}}
        {{-- ================================================= --}}

        <aside class="settings-sidebar">

            <div class="settings-section-title">
                ACCOUNT
            </div>

            <div class="settings-section-subtitle">
                การตั้งค่าบัญชี
            </div>


            {{-- PROFILE --}}
            <a href="{{ route('profile.edit') }}" wire:navigate
                class="settings-menu-item {{ request()->routeIs('profile.edit') ? 'active' : '' }}">

                <span class="settings-menu-icon">
                    <i class="bi bi-person"></i>
                </span>

                <span class="settings-menu-text">
                    Profile
                </span>

                @if(request()->routeIs('profile.edit'))
                <i class="bi bi-chevron-right settings-menu-arrow"></i>
                @endif

            </a>


            {{-- SECURITY --}}
            <a href="{{ route('security.edit') }}" wire:navigate
                class="settings-menu-item {{ request()->routeIs('security.edit') ? 'active' : '' }}">

                <span class="settings-menu-icon">
                    <i class="bi bi-shield-lock"></i>
                </span>

                <span class="settings-menu-text">
                    Security
                </span>

                @if(request()->routeIs('security.edit'))
                <i class="bi bi-chevron-right settings-menu-arrow"></i>
                @endif

            </a>


            {{-- APPEARANCE --}}
            <a href="{{ route('appearance.edit') }}" wire:navigate
                class="settings-menu-item {{ request()->routeIs('appearance.edit') ? 'active' : '' }}">

                <span class="settings-menu-icon">
                    <i class="bi bi-palette"></i>
                </span>

                <span class="settings-menu-text">
                    Appearance
                </span>

                @if(request()->routeIs('appearance.edit'))
                <i class="bi bi-chevron-right settings-menu-arrow"></i>
                @endif

            </a>


            {{-- ================================================= --}}
            {{-- ACCOUNT INFO --}}
            {{-- ================================================= --}}

            <div class="settings-info-box">

                <div class="settings-info-icon">
                    <i class="bi bi-info-circle"></i>
                </div>

                <div class="settings-info-content">

                    <div class="settings-info-title">
                        บัญชีของคุณ
                    </div>

                    <div class="settings-info-text">
                        ตรวจสอบและจัดการข้อมูลบัญชีของคุณ
                    </div>

                </div>

            </div>

        </aside>


        {{-- ================================================= --}}
        {{-- RIGHT CONTENT --}}
        {{-- ================================================= --}}

        <main class="settings-content">

            {{ $slot }}

        </main>

    </div>

</div>


<style>
/* =========================================================
       SETTINGS PAGE
       ========================================================= */

.settings-page {
    min-height: calc(100vh - 70px);

    padding: 28px 40px 40px;

    background: #ffffff;

    color: #162D4A;

    box-sizing: border-box;
}


/* =========================================================
       HEADER
       ========================================================= */

.settings-header {
    display: flex;
    align-items: center;

    gap: 13px;

    margin-bottom: 24px;
}

.settings-header-icon {
    width: 37px;
    height: 37px;

    flex: 0 0 37px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 10px;

    background: #2864e8;

    color: #ffffff;

    font-size: 17px;

    box-shadow:
        0 5px 12px rgba(40, 100, 232, 0.20);
}

.settings-header-text h1 {
    margin: 0;

    color: #162D4A;

    font-size: 21px;
    font-weight: 700;

    line-height: 1.2;
}

.settings-header-text p {
    margin: 3px 0 0;

    color: #7b8ba3;

    font-size: 10px;

    line-height: 1.4;
}


/* =========================================================
       MAIN CARD
       ========================================================= */

.settings-card {
    width: 100%;
    max-width: 900px;

    min-height: 450px;

    display: flex;

    overflow: hidden;

    background: #ffffff;

    border: 1px solid #d9e2ef;

    border-radius: 12px;

    box-shadow:
        0 4px 14px rgba(22, 45, 74, 0.06);

    box-sizing: border-box;
}


/* =========================================================
       LEFT SIDEBAR
       ========================================================= */

.settings-sidebar {
    width: 224px;

    flex: 0 0 224px;

    padding: 20px 15px;

    background: #f8fafc;

    border-right: 1px solid #d9e2ef;

    box-sizing: border-box;
}


/* =========================================================
       SECTION TITLE
       ========================================================= */

.settings-section-title {
    padding-left: 6px;

    color: #8ba0b8;

    font-size: 9px;
    font-weight: 700;

    letter-spacing: 1.2px;
}

.settings-section-subtitle {
    padding-left: 6px;

    margin-top: 4px;
    margin-bottom: 13px;

    color: #8ba0b8;

    font-size: 9px;
}


/* =========================================================
       MENU
       ========================================================= */

.settings-menu-item {
    width: 100%;
    height: 43px;

    display: flex;
    align-items: center;

    gap: 9px;

    margin-bottom: 5px;
    padding: 0 9px;

    border-radius: 8px;

    color: #40536b;

    text-decoration: none;

    font-size: 11px;

    box-sizing: border-box;

    transition:
        background-color 0.15s ease,
        color 0.15s ease;
}

.settings-menu-item:hover {
    background: #edf4ff;

    color: #2864e8;
}

.settings-menu-item.active {
    background: #2864e8;

    color: #ffffff;

    box-shadow:
        0 4px 10px rgba(40, 100, 232, 0.18);
}


/* =========================================================
       MENU ICON
       ========================================================= */

.settings-menu-icon {
    width: 28px;
    height: 28px;

    flex: 0 0 28px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 7px;

    background: #ffffff;

    color: #2864e8;

    font-size: 13px;
}

.settings-menu-item.active .settings-menu-icon {
    background: rgba(255, 255, 255, 0.16);

    color: #ffffff;
}

.settings-menu-text {
    flex: 1;
}

.settings-menu-arrow {
    font-size: 10px;
}


/* =========================================================
       ACCOUNT INFO
       ========================================================= */

.settings-info-box {
    display: flex;

    gap: 9px;

    margin-top: 30px;
    padding: 10px;

    border: 1px solid #cfe0ff;

    border-radius: 8px;

    background: #eff6ff;

    box-sizing: border-box;
}

.settings-info-icon {
    flex: 0 0 auto;

    padding-top: 1px;

    color: #2864e8;

    font-size: 13px;
}

.settings-info-content {
    min-width: 0;
}

.settings-info-title {
    margin-bottom: 3px;

    color: #2864e8;

    font-size: 9px;
    font-weight: 700;
}

.settings-info-text {
    color: #6680a5;

    font-size: 8px;

    line-height: 1.5;
}


/* =========================================================
       RIGHT CONTENT
       ========================================================= */

.settings-content {
    flex: 1;

    min-width: 0;

    padding: 30px 34px;

    background: #ffffff;

    box-sizing: border-box;
}


/* =========================================================
       PAGE TITLE
       ========================================================= */

.settings-content h2 {
    margin: 0;

    color: #162D4A;

    font-size: 18px;
    font-weight: 700;

    line-height: 1.3;
}

.settings-description {
    margin: 5px 0 0;

    color: #7b8ba3;

    font-size: 10px;

    line-height: 1.5;
}


/* =========================================================
       DIVIDER
       ========================================================= */

.settings-divider {
    width: 100%;
    height: 1px;

    margin: 18px 0 20px;

    background: #dce4ef;
}


/* =========================================================
       FIELD
       ========================================================= */

.settings-field {
    margin-bottom: 15px;
}

.settings-field label {
    display: block;

    margin-bottom: 6px;

    color: #53667f;

    font-size: 10px;
    font-weight: 600;
}

.settings-field input,
.settings-field select {
    width: 100%;
    height: 43px;

    padding: 0 12px;

    border: 1px solid #d7e0ec;

    border-radius: 8px;

    outline: none;

    background: #ffffff;

    color: #162D4A;

    font-size: 11px;

    box-sizing: border-box;

    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease;
}

.settings-field input:focus,
.settings-field select:focus {
    border-color: #2864e8;

    box-shadow:
        0 0 0 3px rgba(40, 100, 232, 0.10);
}

.settings-field input:disabled,
.settings-field select:disabled {
    background: #f8fafc;

    color: #7b8ba3;

    cursor: not-allowed;
}


/* =========================================================
       INFO CARD
       ========================================================= */

.settings-content-box {
    display: flex;

    align-items: flex-start;

    gap: 10px;

    padding: 12px;

    margin-bottom: 15px;

    border: 1px solid #cfe0ff;

    border-radius: 8px;

    background: #eff6ff;
}

.settings-content-box-icon {
    flex: 0 0 auto;

    color: #2864e8;

    font-size: 15px;
}

.settings-content-box-title {
    margin-bottom: 3px;

    color: #2864e8;

    font-size: 10px;
    font-weight: 700;
}

.settings-content-box-text {
    color: #6680a5;

    font-size: 9px;

    line-height: 1.5;
}


/* =========================================================
       BUTTON
       ========================================================= */

.settings-button {
    height: 40px;

    padding: 0 18px;

    border: none;

    border-radius: 8px;

    background: #2864e8;

    color: #ffffff;

    font-size: 11px;
    font-weight: 600;

    cursor: pointer;

    box-shadow:
        0 4px 10px rgba(40, 100, 232, 0.16);

    transition:
        background-color 0.15s ease,
        transform 0.15s ease;
}

.settings-button:hover {
    background: #1f56d0;
}

.settings-button:active {
    transform: translateY(1px);
}


/* =========================================================
       ERROR
       ========================================================= */

.settings-error {
    margin-top: 5px;

    color: #dc3545;

    font-size: 9px;
}


/* =========================================================
       SMALL NOTE
       ========================================================= */

.settings-note {
    margin-top: 6px;

    color: #8ba0b8;

    font-size: 9px;
}
</style>