<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    // ต้องเข้าได้แม้ยังไม่ได้เปลี่ยนรหัสครั้งแรก
    Route::livewire('settings/security', 'pages::settings.security')
        ->name('security.edit');

    Route::middleware(['password.changed'])->group(function () {
        Route::livewire('settings/profile', 'pages::settings.profile')
            ->name('profile.edit');

        Route::livewire('settings/appearance', 'pages::settings.appearance')
            ->name('appearance.edit');
    });
});