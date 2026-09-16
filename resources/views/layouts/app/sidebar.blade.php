<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-[#F8FAFC]">
    <flux:sidebar sticky collapsible="mobile"
        class="border-e border-[#2E425C] bg-[#162D4A] dark:border-[#2E425C] dark:bg-[#162D4A]">
        <flux:sidebar.header class="flex-col items-stretch">
            <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
            <div class="px-2 pb-3">
                <span class="inline-block rounded-md bg-[#1A3D6F] px-3 py-1 text-xs font-medium text-[#90A1B9]">
                    ผู้ดูแลระบบ (Admin)
                </span>
            </div>
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.group :heading="__('เมนูหลัก')" class="grid">

                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                    wire:navigate class="text-[#90A1B9] data-current:bg-[#155DFC]! data-current:text-white!">
                    {{ __('Dashboard') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="users" href="#">
                    {{ __('ผู้เช่า') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="building-office" href="#">
                    {{ __('ห้องพัก') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="key" href="#">
                    {{ __('การเช่า') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="bolt" href="#">
                    {{ __('ค่าน้ำ-ค่าไฟ') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="credit-card" href="#">
                    {{ __('ใบแจ้งหนี้ / การชำระ') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="wrench" href="#">
                    {{ __('แจ้งซ่อม') }}
                </flux:sidebar.item>

            </flux:sidebar.group>
        </flux:sidebar.nav>

        <flux:spacer />

        <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
    </flux:sidebar>

    <!-- Desktop Header -->
    <flux:header class="hidden border-b border-[#2E425C] bg-white lg:flex">
        <flux:spacer />

        <div class="flex items-center gap-4">
            <flux:button variant="ghost" icon="bell" class="text-[#90A1B9]" />

            <div class="h-6 w-px bg-[#2E425C]"></div>

            <div class="text-right">
                <div class="text-sm font-semibold text-[#162D4A]">
                    ผู้ดูแลระบบ
                </div>
                <div class="text-xs text-[#90A1B9]">
                    Administrator
                </div>
            </div>
        </div>
    </flux:header>

    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                        class="w-full cursor-pointer" data-test="logout-button">
                        {{ __('Log out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</class=>

</html>
