<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile settings')] class extends Component
{
    public function getUserProperty()
    {
        return Auth::user();
    }
};

?>

<x-pages::settings.layout>

    <h2>บัญชีของฉัน</h2>

    <p class="settings-description">
        ข้อมูลบัญชีสำหรับเข้าสู่ระบบ
    </p>

    <div class="settings-divider"></div>

    {{-- USERNAME --}}
    <div class="settings-field">

        <label>
            ชื่อผู้ใช้
        </label>

        <input type="text" value="{{ $this->user?->u_username }}" disabled>

    </div>


    {{-- ROLE --}}
    <div class="settings-field">

        <label>
            บทบาท
        </label>

        <input type="text" value="{{ $this->user?->u_role === 'admin' ? 'ผู้ดูแลระบบ' : 'ผู้ใช้งาน' }}" disabled>

    </div>

    {{-- INFORMATION --}}
    <div class="settings-content-box">

        <div class="settings-content-box-icon">
            <i class="bi bi-info-circle"></i>
        </div>

        <div>

            <div class="settings-content-box-title">
                ข้อมูลบัญชี
            </div>

            <div class="settings-content-box-text">
                หากต้องการแก้ไขข้อมูลผู้ใช้งาน
                กรุณาติดต่อผู้ดูแลระบบ
            </div>

        </div>

    </div>

</x-pages::settings.layout>