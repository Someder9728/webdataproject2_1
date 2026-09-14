<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('บัญชีของฉัน')] class extends Component {
    //
};

?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout
        :heading="__('บัญชีของฉัน')"
        :subheading="__('ข้อมูลบัญชีสำหรับเข้าสู่ระบบ')"
    >
        <div class="space-y-4">
            <div>
                <flux:heading>ชื่อผู้ใช้</flux:heading>
                <flux:text>
                    {{ auth()->user()->u_username }}
                </flux:text>
            </div>

            <div>
                <flux:heading>บทบาท</flux:heading>
                <flux:text>
                    {{ auth()->user()->u_role }}
                </flux:text>
            </div>

            <flux:text>
                หากต้องการแก้ไขข้อมูลผู้เช่า กรุณาติดต่อผู้ดูแลหอพัก
            </flux:text>
        </div>
    </x-pages::settings.layout>
</section>