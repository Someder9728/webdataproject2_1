<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('บัญชีของฉัน')] class extends Component {
    //
};

?>

<section class="w-100">

    <x-pages::settings.layout :heading="__('บัญชีของฉัน')" :subheading="__('ข้อมูลบัญชีสำหรับเข้าสู่ระบบ')">

        {{-- PROFILE INFORMATION --}}
        <div class="profile-info-list">

            {{-- USERNAME --}}
            <div class="profile-info-item">

                <div class="profile-info-icon">
                    <i class="bi bi-person"></i>
                </div>

                <div class="profile-info-content">

                    <div class="profile-info-label">
                        ชื่อผู้ใช้
                    </div>

                    <div class="profile-info-value">
                        {{ auth()->user()->u_username }}
                    </div>

                </div>

            </div>


            {{-- ROLE --}}
            <div class="profile-info-item">

                <div class="profile-info-icon">
                    <i class="bi bi-shield-check"></i>
                </div>

                <div class="profile-info-content">

                    <div class="profile-info-label">
                        บทบาท
                    </div>

                    <div class="profile-info-value">
                        {{ auth()->user()->u_role }}
                    </div>

                </div>

            </div>


            {{-- INFORMATION --}}
            <div class="profile-notice">

                <div class="profile-notice-icon">
                    <i class="bi bi-info-circle"></i>
                </div>

                <div>

                    <div class="profile-notice-title">
                        ข้อมูลบัญชี
                    </div>

                    <div class="profile-notice-text">
                        หากต้องการแก้ไขข้อมูลผู้เช่า
                        กรุณาติดต่อผู้ดูแลหอพัก
                    </div>

                </div>
            </div>
        </div>
    </x-pages::settings.layout>

</section>


<style>
    /* PROFILE INFORMATION */
    .profile-info-list {
        width: 100%;
        max-width: 680px;
    }

    .profile-info-item {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 18px;
        margin-bottom: 14px;
        border: 1px solid #E2E8F0;
        border-radius: 12px;
        background: #ffffff;
        transition:
            border-color 0.15s ease,
            box-shadow 0.15s ease;
    }

    .profile-info-item:hover {
        border-color: #CBD5E1;
        box-shadow: 0 4px 12px rgba(36, 52, 77, 0.05);
    }


    /* ICON */
    .profile-info-icon {
        width: 42px;
        height: 42px;
        min-width: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        bakground: #EFF6FF;
        color: #2161F5;
        font-size: 19px;
    }


    /* CONTENT */
    .profile-info-content {
        min-width: 0;
        flex: 1;
    }

    .profile-info-label {
        margin-bottom: 4px;
        color: #7B8CA5;
        font-size: 12px;
        font-weight: 600;
    }

    .profile-info-value {
        color: #24344D;
        font-size: 15px;
        font-weight: 600;
        word-break: break-word;
    }


    /*INFORMATION NOTICE */
    .profile-notice {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-top: 24px;
        padding: 16px;
        border: 1px solid #DBEAFE;
        border-radius: 12px;
        background: #EFF6FF;
    }

    .profile-notice-icon {
        flex-shrink: 0;
        color: #3B82F6;
        font-size: 18px;
        line-height: 1.4;
    }

    .profile-notice-title {
        margin-bottom: 3px;
        color: #1D4ED8;
        font-size: 13px;
        font-weight: 600;
    }

    .profile-notice-text {
        color: #2563EB;
        font-size: 12px;
        line-height: 1.7;
    }


    /* MOBILE */
    @media (max-width: 575.98px) {

        .profile-info-item {
            padding: 15px;
        }

        .profile-info-icon {
            width: 38px;
            height: 38px;
            min-width: 38px;

            font-size: 17px;
        }

        .profile-info-value {
            font-size: 14px;
        }

    }
</style>
