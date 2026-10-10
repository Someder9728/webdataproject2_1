<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Appearance settings')] class extends Component
{
    public string $appearance = 'light';
};

?>

<x-pages::settings.layout>

    <h2>การแสดงผล</h2>

    <p class="settings-description">
        ปรับแต่งรูปแบบการแสดงผลของระบบ
    </p>

    <div class="settings-divider"></div>


    {{-- APPEARANCE --}}
    <div class="settings-field">

        <label for="appearance">
            รูปแบบการแสดงผล
        </label>

        <select id="appearance" wire:model="appearance">

            <option value="light">
                Light
            </option>

            <option value="dark">
                Dark
            </option>

        </select>

    </div>

    {{-- INFORMATION --}}
    <div class="settings-content-box">

        <div class="settings-content-box-icon">
            <i class="bi bi-palette"></i>
        </div>

        <div>

            <div class="settings-content-box-title">
                การตั้งค่าการแสดงผล
            </div>

            <div class="settings-content-box-text">
                เลือกรูปแบบการแสดงผลที่ต้องการใช้งาน
            </div>

        </div>

    </div>

</x-pages::settings.layout>