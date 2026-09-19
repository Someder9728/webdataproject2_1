<div class="min-h-screen px-4 py-6 text-slate-800 md:px-8 md:py-8">

    <div class="mx-auto max-w-6xl">

        {{-- ========================= --}}
        {{-- PAGE HEADER --}}
        {{-- ========================= --}}
        <div class="mb-7">

            <div class="flex items-center gap-4">

                {{-- Settings Icon --}}
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl
                           bg-blue-600 shadow-lg shadow-blue-600/20">

                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="1.8">

                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0
                               a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826
                               2.37 2.37a1.724 1.724 0 001.065 2.572
                               c1.756.426 1.756 2.924 0 3.35
                               a1.724 1.724 0 00-1.066 2.573
                               c.94 1.543-.826 3.31-2.37 2.37
                               a1.724 1.724 0 00-2.572 1.065
                               c-.426 1.756-2.924 1.756-3.35 0
                               a1.724 1.724 0 00-2.573-1.066
                               c-1.543.608-2.296.07-2.572-1.065z" />

                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />

                    </svg>
                </div>

                {{-- Title --}}
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-800 md:text-3xl">
                        Settings
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        จัดการข้อมูลบัญชีและการตั้งค่าของคุณ
                    </p>
                </div>

            </div>

        </div>


        {{-- ========================= --}}
        {{-- SETTINGS CARD --}}
        {{-- ========================= --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200
                   bg-white shadow-sm">

            <div class="flex min-h-[600px] flex-col md:flex-row">


                {{-- ========================= --}}
                {{-- LEFT SIDEBAR --}}
                {{-- ========================= --}}
                <aside
                    class="w-full shrink-0 border-b border-slate-200
                           bg-slate-50 md:w-[250px]
                           md:border-b-0 md:border-r">

                    <div class="p-5">

                        {{-- Sidebar Header --}}
                        <div class="mb-5 px-2">

                            <p
                                class="text-[11px] font-bold uppercase
                                       tracking-[0.18em] text-slate-400">

                                Account

                            </p>

                            <p class="mt-1 text-xs text-slate-400">
                                การตั้งค่าบัญชี
                            </p>

                        </div>


                        {{-- Navigation --}}
                        <nav class="space-y-2">

                            {{-- PROFILE --}}
                            <a href="{{ route('profile.edit') }}" wire:navigate
                                class="group flex items-center gap-3 rounded-xl
                                       px-3.5 py-3 text-sm font-medium
                                       transition-all duration-200

                                       {{ request()->routeIs('profile.edit')
                                           ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20'
                                           : 'text-slate-600 hover:bg-blue-50 hover:text-blue-700' }}">

                                <span
                                    class="flex h-9 w-9 shrink-0 items-center
                                           justify-center rounded-lg

                                           {{ request()->routeIs('profile.edit')
                                               ? 'bg-white/15 text-white'
                                               : 'bg-white text-slate-400 shadow-sm group-hover:text-blue-600' }}">

                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">

                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0
                                               3.75 3.75 0 017.5 0z" />

                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4.5 20.25a8.25 8.25 0 0115 0" />

                                    </svg>

                                </span>

                                <span class="flex-1">
                                    Profile
                                </span>

                                @if (request()->routeIs('profile.edit'))
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">

                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />

                                    </svg>
                                @endif

                            </a>


                            {{-- SECURITY --}}
                            <a href="{{ route('security.edit') }}" wire:navigate
                                class="group flex items-center gap-3 rounded-xl
                                       px-3.5 py-3 text-sm font-medium
                                       transition-all duration-200

                                       {{ request()->routeIs('security.edit')
                                           ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20'
                                           : 'text-slate-600 hover:bg-blue-50 hover:text-blue-700' }}">

                                <span
                                    class="flex h-9 w-9 shrink-0 items-center
                                           justify-center rounded-lg

                                           {{ request()->routeIs('security.edit')
                                               ? 'bg-white/15 text-white'
                                               : 'bg-white text-slate-400 shadow-sm group-hover:text-blue-600' }}">

                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">

                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75a3 3 0 100-6
                                               3 3 0 000 6z" />

                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 10.5V8.25a7.5 7.5 0 00-15 0v2.25" />

                                        <rect x="3.75" y="10.5" width="16.5" height="9.75" rx="2" />

                                    </svg>

                                </span>

                                <span class="flex-1">
                                    Security
                                </span>

                                @if (request()->routeIs('security.edit'))
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">

                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />

                                    </svg>
                                @endif

                            </a>


                            {{-- APPEARANCE --}}
                            <a href="{{ route('appearance.edit') }}" wire:navigate
                                class="group flex items-center gap-3 rounded-xl
                                       px-3.5 py-3 text-sm font-medium
                                       transition-all duration-200

                                       {{ request()->routeIs('appearance.edit')
                                           ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20'
                                           : 'text-slate-600 hover:bg-blue-50 hover:text-blue-700' }}">

                                <span
                                    class="flex h-9 w-9 shrink-0 items-center
                                           justify-center rounded-lg

                                           {{ request()->routeIs('appearance.edit')
                                               ? 'bg-white/15 text-white'
                                               : 'bg-white text-slate-400 shadow-sm group-hover:text-blue-600' }}">

                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">

                                        <circle cx="12" cy="12" r="8.5" />

                                        <path stroke-linecap="round" d="M12 3v18" />

                                        <path stroke-linecap="round" d="M3 12h18" />

                                    </svg>

                                </span>

                                <span class="flex-1">
                                    Appearance
                                </span>

                                @if (request()->routeIs('appearance.edit'))
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">

                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />

                                    </svg>
                                @endif

                            </a>

                        </nav>


                        {{-- ACCOUNT INFO --}}
                        <div
                            class="mt-8 rounded-xl border border-blue-100
                                   bg-blue-50 p-4">

                            <div class="flex gap-3">

                                <div class="mt-0.5 shrink-0">

                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">

                                        <circle cx="12" cy="12" r="9" />

                                        <path stroke-linecap="round" d="M12 10v6" />

                                        <path stroke-linecap="round" d="M12 7.5h.01" />

                                    </svg>

                                </div>

                                <div>

                                    <p class="text-xs font-semibold text-blue-700">
                                        บัญชีของคุณ
                                    </p>

                                    <p
                                        class="mt-1 text-[11px]
                                               leading-5 text-blue-600/80">

                                        ตรวจสอบข้อมูลและการตั้งค่าบัญชี
                                        ของคุณได้จากเมนูด้านบน

                                    </p>

                                </div>

                            </div>

                        </div>

                    </div>

                </aside>


                {{-- ========================= --}}
                {{-- RIGHT CONTENT --}}
                {{-- ========================= --}}
                <main class="min-w-0 flex-1 bg-white">

                    <div class="p-6 md:p-9 lg:p-11">

                        {{-- Content Header --}}
                        <div class="mb-8 border-b border-slate-200 pb-6">

                            <h2
                                class="text-2xl font-bold tracking-tight
                                       text-slate-800">

                                {{ $heading ?? 'Settings' }}

                            </h2>

                            @if ($subheading ?? false)
                                <p
                                    class="mt-2 max-w-xl text-sm
                                           leading-6 text-slate-500">

                                    {{ $subheading }}

                                </p>
                            @endif

                        </div>


                        {{-- ========================= --}}
                        {{-- ACTUAL PAGE CONTENT --}}
                        {{-- ========================= --}}
                        <div
                            class="
                                w-full max-w-2xl
                                text-slate-700

                                [&_label]:mb-2
                                [&_label]:block
                                [&_label]:text-sm
                                [&_label]:font-semibold
                                [&_label]:text-slate-700

                                [&_input]:!border-slate-300
                                [&_input]:!bg-white
                                [&_input]:!text-slate-900
                                [&_input]:!placeholder-slate-400

                                [&_input]:focus:!border-blue-500

                                [&_textarea]:!border-slate-300
                                [&_textarea]:!bg-white
                                [&_textarea]:!text-slate-900

                                [&_select]:!border-slate-300
                                [&_select]:!bg-white
                                [&_select]:!text-slate-900

                                [&_button]:cursor-pointer
                            ">

                            {{ $slot }}

                        </div>

                    </div>

                </main>

            </div>

        </div>

    </div>

</div>
