@props([
    'sidebar' => false,
])

@if ($sidebar)
    <div class="flex items-center gap-3 px-2">
        <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-[#2161F5]">
            <x-app-logo-icon class="size-5 fill-current text-white" />
        </div>

        <div class="flex flex-col">
            <span class="text-sm font-bold text-white">หอพักสุขสบาย</span>
            <span class="text-xs text-[#5CA2FF]">ระบบจัดการหอพัก</span>
        </div>
    </div>
@else
    <flux:brand :name="'หอพักสุขสบาย'" {{ $attributes }}>
        <x-slot name="logo"
            class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
        </x-slot>
    </flux:brand>
@endif
