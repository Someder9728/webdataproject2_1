<div class="dropdown">

    {{-- User Profile Button --}}
    <button type="button" class="btn d-flex align-items-center gap-2 p-2 border-0 shadow-none text-start"
        data-bs-toggle="dropdown" aria-expanded="false" data-test="sidebar-menu-button">

        {{-- Avatar --}}
        <div class="user-avatar">
            {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}
        </div>

        {{-- User Name --}}
        <div class="d-none d-xl-block user-profile-text">

            <div class="user-name text-truncate">
                {{ auth()->user()->name }}
            </div>

            <div class="user-role text-truncate">
                {{ auth()->user()->u_role ?? 'Administrator' }}
            </div>

        </div>

        {{-- Dropdown Icon --}}
        <i class="bi bi-chevron-down user-dropdown-icon"></i>

    </button>


    {{-- Dropdown Menu --}}
    <div class="dropdown-menu dropdown-menu-end user-dropdown-menu shadow">

        {{-- User Information --}}
        <div class="user-info">

            <div class="user-avatar user-avatar-large">
                {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}
            </div>

            <div class="user-info-text">

                <div class="user-info-name">
                    {{ auth()->user()->name }}
                </div>

                <div class="user-info-role">
                    {{ auth()->user()->u_role ?? 'Administrator' }}
                </div>

                <div class="user-info-email">
                    {{ auth()->user()->email }}
                </div>

            </div>

        </div>


        {{-- Divider --}}
        <div class="dropdown-divider"></div>


        {{-- Settings --}}
        <a href="{{ route('profile.edit') }}" class="dropdown-item user-dropdown-item" wire:navigate>
            <i class="bi bi-gear"></i>
            <span>{{ __('Settings') }}</span>
        </a>


        {{-- Logout --}}
        <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf

            <button type="submit" class="dropdown-item user-dropdown-item logout-item">
                <i class="bi bi-box-arrow-right"></i>
                <span>{{ __('Log out') }}</span>
            </button>

        </form>

    </div>

</div>


<style>
    /* User Profile Button */
    .user-profile-text {
        min-width: 0;
        max-width: 150px;
    }

    .user-name {
        color: #24344D;
        font-size: 14px;
        font-weight: 600;
        line-height: 1.3;
    }

    .user-role {
        margin-top: 2px;
        color: #7B8CA5;
        font-size: 12px;
        line-height: 1.3;
    }

    .user-dropdown-icon {
        color: #7B8CA5;
        font-size: 12px;
    }


    /* Avatar */

    .user-avatar {
        width: 40px;
        height: 40px;
        min-width: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #2161F5;
        color: #ffffff;
        font-size: 14px;
        font-weight: 700;
    }

    .user-avatar-large {
        width: 46px;
        height: 46px;
        min-width: 46px;
        font-size: 15px;
    }


    /* Dropdown */
    .user-dropdown-menu {
        width: 280px;
        margin-top: 8px !important;
        padding: 8px;
        border: 1px solid #DDE5EF;
        border-radius: 12px;
        background: #ffffff;
    }


    /* User Information */
    .user-info {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px;
    }

    .user-info-text {
        min-width: 0;
        flex: 1;
    }

    .user-info-name {
        color: #172033;
        font-size: 14px;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .user-info-role {
        margin-top: 2px;
        color: #7B8CA5;
        font-size: 12px;
    }

    .user-info-email {
        margin-top: 2px;
        color: #9AA8BB;
        font-size: 12px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }


    /* Dropdown Items */
    .user-dropdown-item {
        display: flex !important;
        align-items: center;
        gap: 10px;
        padding: 10px 12px !important;
        border-radius: 8px;
        color: #42526B !important;
        font-size: 14px;
        transition:
            background-color .15s ease,
            color .15s ease;
    }

    .user-dropdown-item i {
        width: 20px;
        color: #7B8CA5;
        font-size: 17px;
        text-align: center;
    }

    .user-dropdown-item:hover {
        background: #F1F5F9 !important;
        color: #172033 !important;
    }

    .user-dropdown-item:hover i {
        color: #2161F5;
    }

    /* Logout */
    .logout-item {
        width: 100%;
        border: 0;
        background: transparent;
        text-align: left;
        cursor: pointer;
    }

    .logout-item:hover {
        background: #FFF1F1 !important;
        color: #DC2626 !important;
    }

    .logout-item:hover i {
        color: #DC2626;
    }


    /* Mobile */

    @media (max-width: 1199.98px) {

        .user-dropdown-icon {
            display: none;
        }

    }
</style>
