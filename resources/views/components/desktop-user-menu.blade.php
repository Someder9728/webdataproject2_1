<flux:dropdown position="bottom" align="start">

    {{-- Sidebar User Profile --}}
    <button type="button" class="flex w-full items-center gap-3 px-2 py-3 text-left">
        <div
            class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-[#555B62] text-lg font-medium text-white">
            {{ auth()->user()->initials() }}
        </div>

        <div class="min-w-0 flex-1">
            <div class="truncate text-sm font-semibold text-white">
                ผู้ดูแลระบบ
            </div>

            <div class="truncate text-xs text-[#90A1B9]">
                Administrator
            </div>
        </div>

        <flux:icon name="chevrons-up-down" class="size-5 text-[#90A1B9]" />
    </button>

    {{-- Dropdown เดิม --}}
    <flux:menu>
        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
            <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />

            <div class="grid flex-1 text-start text-sm leading-tight">
                <flux:heading class="truncate">
                    {{ auth()->user()->name }}
                </flux:heading>

                <flux:text class="truncate">
                    {{ auth()->user()->email }}
                </flux:text>
            </div>
        </div>

        <flux:menu.separator />

        <flux:menu.radio.group>
            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                {{ __('Settings') }}
            </flux:menu.item>

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf

                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                    class="w-full cursor-pointer" data-test="logout-button">
                    {{ __('Log out') }}
                </flux:menu.item>
            </form>
        </flux:menu.radio.group>
    </flux:menu>

</flux:dropdown>
