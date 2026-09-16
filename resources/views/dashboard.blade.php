<x-layouts::app :title="__('Dashboard')">

    <div class="min-h-full bg-[#F8FAFC] p-6">

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">

            {{-- ห้องทั้งหมด --}}
            <div class="flex items-center gap-5 rounded-xl border border-[#DDE5EF] bg-white p-6 shadow-sm">
                <div class="flex size-14 items-center justify-center rounded-xl bg-[#34445C] text-white">
                    <flux:icon name="building-office" class="size-7" />
                </div>

                <div>
                    <p class="text-sm text-[#7B8CA5]">ห้องทั้งหมด</p>
                    <p class="text-3xl font-bold text-[#172033]">12</p>
                </div>
            </div>

            {{-- ห้องว่าง --}}
            <div class="flex items-center gap-5 rounded-xl border border-[#DDE5EF] bg-white p-6 shadow-sm">
                <div class="flex size-14 items-center justify-center rounded-xl bg-[#00A875] text-white">
                    <flux:icon name="check-circle" class="size-7" />
                </div>

                <div>
                    <p class="text-sm text-[#7B8CA5]">ห้องว่าง</p>
                    <p class="text-3xl font-bold text-[#172033]">4</p>
                </div>
            </div>

            {{-- ผู้เช่า --}}
            <div class="flex items-center gap-5 rounded-xl border border-[#DDE5EF] bg-white p-6 shadow-sm">
                <div class="flex size-14 items-center justify-center rounded-xl bg-[#2161F5] text-white">
                    <flux:icon name="user" class="size-7" />
                </div>

                <div>
                    <p class="text-sm text-[#7B8CA5]">ผู้เช่า</p>
                    <p class="text-3xl font-bold text-[#172033]">8</p>
                </div>
            </div>

            {{-- ผู้เช่าปัจจุบัน --}}
            <div class="flex items-center gap-5 rounded-xl border border-[#DDE5EF] bg-white p-6 shadow-sm">
                <div class="flex size-14 items-center justify-center rounded-xl bg-[#4F3BFF] text-white">
                    <flux:icon name="users" class="size-7" />
                </div>

                <div>
                    <p class="text-sm text-[#7B8CA5]">ผู้เช่าปัจจุบัน</p>
                    <p class="text-3xl font-bold text-[#172033]">8</p>
                    <p class="text-sm text-[#9AA8BB]">คน</p>
                </div>
            </div>

            {{-- ยอดค้างชำระ --}}
            <div class="flex items-center gap-5 rounded-xl border border-[#DDE5EF] bg-white p-6 shadow-sm">
                <div class="flex size-14 items-center justify-center rounded-xl bg-[#F00000] text-white">
                    <flux:icon name="currency-dollar" class="size-7" />
                </div>

                <div>
                    <p class="text-sm text-[#7B8CA5]">ยอดค้างชำระ</p>
                    <p class="text-3xl font-bold text-[#172033]">฿15,747</p>
                    <p class="text-sm text-[#9AA8BB]">2 รายการ</p>
                </div>
            </div>

            {{-- แจ้งซ่อมค้าง --}}
            <div class="flex items-center gap-5 rounded-xl border border-[#DDE5EF] bg-white p-6 shadow-sm">
                <div class="flex size-14 items-center justify-center rounded-xl bg-[#ED7800] text-white">
                    <flux:icon name="cog-6-tooth" class="size-7" />
                </div>

                <div>
                    <p class="text-sm text-[#7B8CA5]">แจ้งซ่อมค้าง</p>
                    <p class="text-3xl font-bold text-[#172033]">3</p>
                    <p class="text-sm text-[#9AA8BB]">รายการ</p>
                </div>
            </div>

        </div>


        {{-- สถานะห้องพัก --}}
        <div class="mt-7 mb-8 overflow-hidden rounded-xl border border-[#DDE5EF] bg-white shadow-sm">

            <div class="border-b border-[#E5EAF0] px-7 py-5">
                <h2 class="text-lg font-bold text-[#172033]">
                    สถานะห้องพัก
                </h2>

                <p class="mt-1 text-sm text-[#7B8CA5]">
                    ภาพรวมห้องพักทั้งหมด
                </p>
            </div>

            {{-- ชั้น 1 --}}
            <div class="px-7 pb-7">
                <p class="mb-3 text-sm font-medium text-[#7B8CA5]">
                    ชั้น 1
                </p>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">

                    {{-- ห้อง 101 --}}
                    <div class="h-[140px] rounded-lg border-2 border-[#B8D5FF] bg-[#EFF6FF] p-4">
                        <div class="flex items-center justify-between">
                            <p class="font-bold text-[#24344D]">ห้อง 101</p>
                            <span class="size-2.5 rounded-full bg-[#2B7FFF]"></span>
                        </div>

                        <p class="mt-2 text-sm text-[#2161F5]">มีผู้เช่า</p>
                        <p class="text-sm text-[#7B8CA5]">สมชาย ใจดี</p>
                        <p class="mt-1 text-sm text-[#91A4BF]">฿3,500/เดือน</p>
                    </div>

                    {{-- ห้อง 102 --}}
                    <div class="h-[140px] rounded-lg border-2 border-[#B8D5FF] bg-[#EFF6FF] p-4">
                        <div class="flex items-center justify-between">
                            <p class="font-bold text-[#24344D]">ห้อง 102</p>
                            <span class="size-2.5 rounded-full bg-[#2B7FFF]"></span>
                        </div>

                        <p class="mt-2 text-sm text-[#2161F5]">มีผู้เช่า</p>
                        <p class="text-sm text-[#7B8CA5]">สุดา รักดี</p>
                        <p class="mt-1 text-sm text-[#91A4BF]">฿3,500/เดือน</p>
                    </div>

                    {{-- ห้อง 103 --}}
                    <div class="h-[140px] rounded-lg border-2 border-[#B8D5FF] bg-[#EFF6FF] p-4">
                        <div class="flex items-center justify-between">
                            <p class="font-bold text-[#24344D]">ห้อง 103</p>
                            <span class="size-2.5 rounded-full bg-[#2B7FFF]"></span>
                        </div>

                        <p class="mt-2 text-sm text-[#2161F5]">มีผู้เช่า</p>
                        <p class="text-sm text-[#7B8CA5]">ปานะ ชัยเจริญ</p>
                        <p class="mt-1 text-sm text-[#91A4BF]">฿4,500/เดือน</p>
                    </div>

                    {{-- ห้อง 104 --}}
                    <div class="h-[140px] rounded-lg border-2 border-[#83E5BF] bg-[#ECFDF5] p-4">
                        <div class="flex items-center justify-between">
                            <p class="font-bold text-[#24344D]">ห้อง 104</p>
                            <span class="size-2.5 rounded-full bg-[#00B67A]"></span>
                        </div>

                        <p class="mt-2 text-sm text-[#00A875]">ว่าง</p>
                        <p class="mt-1 text-sm text-[#91A4BF]">฿3,500/เดือน</p>
                    </div>

                </div>
            </div>

            {{-- ชั้น 2 --}}
            <div class="px-7 pb-7">
                <p class="mb-3 text-sm font-medium text-[#7B8CA5]">
                    ชั้น 2
                </p>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">

                    {{-- ห้อง 201 --}}
                    <div class="h-[140px] rounded-lg border-2 border-[#B8D5FF] bg-[#EFF6FF] p-4">
                        <div class="flex items-center justify-between">
                            <p class="font-bold text-[#24344D]">ห้อง 201</p>
                            <span class="size-2.5 rounded-full bg-[#2B7FFF]"></span>
                        </div>

                        <p class="mt-2 text-sm text-[#2161F5]">มีผู้เช่า</p>
                    </div>

                    {{-- ห้อง 202 --}}
                    <div class="h-[140px] rounded-lg border-2 border-[#B8D5FF] bg-[#EFF6FF] p-4">
                        <div class="flex items-center justify-between">
                            <p class="font-bold text-[#24344D]">ห้อง 202</p>
                            <span class="size-2.5 rounded-full bg-[#2B7FFF]"></span>
                        </div>

                        <p class="mt-2 text-sm text-[#2161F5]">มีผู้เช่า</p>
                    </div>

                    {{-- ห้อง 203 --}}
                    <div class="h-[140px] rounded-lg border-2 border-[#83E5BF] bg-[#ECFDF5] p-4">
                        <div class="flex items-center justify-between">
                            <p class="font-bold text-[#24344D]">ห้อง 203</p>
                            <span class="size-2.5 rounded-full bg-[#00B67A]"></span>
                        </div>

                        <p class="mt-2 text-sm text-[#00A875]">ว่าง</p>
                    </div>

                    {{-- ห้อง 204 --}}
                    <div class="h-[140px] rounded-lg border-2 border-[#B8D5FF] bg-[#EFF6FF] p-4">
                        <div class="flex items-center justify-between">
                            <p class="font-bold text-[#24344D]">ห้อง 204</p>
                            <span class="size-2.5 rounded-full bg-[#2B7FFF]"></span>
                        </div>

                        <p class="mt-2 text-sm text-[#2161F5]">มีผู้เช่า</p>
                    </div>

                </div>
            </div>

        </div>


        {{-- รายการค้างชำระ --}}
        <div class="mb-8 overflow-hidden rounded-xl border border-[#DDE5EF] bg-white shadow-sm">

            <div class="flex items-center justify-between border-b border-[#E5EAF0] px-6 py-5">
                <div>
                    <h2 class="text-lg font-bold text-[#24344D]">
                        รายการค้างชำระ
                    </h2>

                    <p class="mt-1 text-sm text-[#7B8CA5]">
                        ยังไม่ได้ชำระ
                    </p>
                </div>

                <a href="#" class="text-sm text-[#2161F5] hover:underline">
                    ดูทั้งหมด →
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">

                    <thead class="border-b border-[#E5EAF0] text-[#7B8CA5]">
                        <tr>
                            <th class="px-6 py-4 font-medium">ห้อง</th>
                            <th class="px-6 py-4 font-medium">ผู้เช่า</th>
                            <th class="px-6 py-4 font-medium">รอบบิล</th>
                            <th class="px-6 py-4 font-medium">ยอด</th>
                            <th class="px-6 py-4 font-medium">สถานะ</th>
                        </tr>
                    </thead>

                    <tbody class="text-[#42526B]">

                        <tr class="border-b border-[#E5EAF0]">
                            <td class="px-6 py-4">103</td>
                            <td class="px-6 py-4">มานะ ขยันเรียน</td>
                            <td class="px-6 py-4">2024-07</td>
                            <td class="px-6 py-4 font-semibold">฿6,572</td>
                            <td class="px-6 py-4">
                                <span
                                    class="rounded-md border border-[#FFD35A] bg-[#FFF9E8] px-3 py-1 text-xs text-[#E99A00]">
                                    รอชำระ
                                </span>
                            </td>
                        </tr>

                        <tr class="border-b border-[#E5EAF0] bg-[#F8FAFC]">
                            <td class="px-6 py-4">201</td>
                            <td class="px-6 py-4">วิไล สุขสม</td>
                            <td class="px-6 py-4">2024-07</td>
                            <td class="px-6 py-4 font-semibold">฿5,870</td>
                            <td class="px-6 py-4">
                                <span
                                    class="rounded-md border border-[#FFD35A] bg-[#FFF9E8] px-3 py-1 text-xs text-[#E99A00]">
                                    รอชำระ
                                </span>
                            </td>
                        </tr>

                        <tr class="border-b border-[#E5EAF0]">
                            <td class="px-6 py-4">202</td>
                            <td class="px-6 py-4">ประเสริฐ มีสุข</td>
                            <td class="px-6 py-4">2024-07</td>
                            <td class="px-6 py-4 font-semibold">฿7,662</td>
                            <td class="px-6 py-4">
                                <span
                                    class="rounded-md border border-[#FFBABA] bg-[#FFF1F1] px-3 py-1 text-xs text-[#FF4B4B]">
                                    ค้างชำระ
                                </span>
                            </td>
                        </tr>

                        <tr class="border-b border-[#E5EAF0] bg-[#F8FAFC]">
                            <td class="px-6 py-4">204</td>
                            <td class="px-6 py-4">นิกา บุญมี</td>
                            <td class="px-6 py-4">2024-07</td>
                            <td class="px-6 py-4 font-semibold">฿8,085</td>
                            <td class="px-6 py-4">
                                <span
                                    class="rounded-md border border-[#FFBABA] bg-[#FFF1F1] px-3 py-1 text-xs text-[#FF4B4B]">
                                    ค้างชำระ
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4">303</td>
                            <td class="px-6 py-4">จิรา ดีงาม</td>
                            <td class="px-6 py-4">2024-07</td>
                            <td class="px-6 py-4 font-semibold">฿5,972</td>
                            <td class="px-6 py-4">
                                <span
                                    class="rounded-md border border-[#FFD35A] bg-[#FFF9E8] px-3 py-1 text-xs text-[#E99A00]">
                                    รอชำระ
                                </span>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>

        </div>


        {{-- รายการแจ้งซ่อมล่าสุด --}}
        <div class="overflow-hidden rounded-xl border border-[#DDE5EF] bg-white shadow-sm">

            <div class="flex items-center justify-between border-b border-[#E5EAF0] px-6 py-5">
                <div>
                    <h2 class="text-lg font-bold text-[#24344D]">
                        รายการแจ้งซ่อมล่าสุด
                    </h2>

                    <p class="mt-1 text-sm text-[#7B8CA5]">
                        สถานะการซ่อมแซม
                    </p>
                </div>

                <a href="#" class="text-sm text-[#2161F5] hover:underline">
                    ดูทั้งหมด →
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">

                    <thead class="border-b border-[#E5EAF0] text-[#7B8CA5]">
                        <tr>
                            <th class="px-6 py-4 font-medium">รหัส</th>
                            <th class="px-6 py-4 font-medium">ห้อง</th>
                            <th class="px-6 py-4 font-medium">หัวข้อ</th>
                            <th class="px-6 py-4 font-medium">วันที่</th>
                            <th class="px-6 py-4 font-medium">สถานะ</th>
                        </tr>
                    </thead>

                    <tbody class="text-[#42526B]">

                        <tr class="border-b border-[#E5EAF0]">
                            <td class="px-6 py-4">MNT-001</td>
                            <td class="px-6 py-4">101</td>
                            <td class="px-6 py-4">ก๊อกน้ำรั่ว</td>
                            <td class="px-6 py-4">2024-07-10</td>
                            <td class="px-6 py-4">
                                <span
                                    class="rounded-md border border-[#70E5B5] bg-[#ECFDF5] px-3 py-1 text-xs text-[#00A875]">
                                    เสร็จแล้ว
                                </span>
                            </td>
                        </tr>

                        <tr class="border-b border-[#E5EAF0] bg-[#F8FAFC]">
                            <td class="px-6 py-4">MNT-002</td>
                            <td class="px-6 py-4">103</td>
                            <td class="px-6 py-4">แอร์ไม่เย็น</td>
                            <td class="px-6 py-4">2024-07-13</td>
                            <td class="px-6 py-4">
                                <span
                                    class="rounded-md border border-[#A9CAFF] bg-[#EFF6FF] px-3 py-1 text-xs text-[#2161F5]">
                                    กำลังซ่อม
                                </span>
                            </td>
                        </tr>

                        <tr class="border-b border-[#E5EAF0]">
                            <td class="px-6 py-4">MNT-003</td>
                            <td class="px-6 py-4"></td>
                            <td class="px-6 py-4">ลิฟต์เปิดปิดช้า</td>
                            <td class="px-6 py-4">2024-07-15</td>
                            <td class="px-6 py-4">
                                <span
                                    class="rounded-md border border-[#FFD35A] bg-[#FFF9E8] px-3 py-1 text-xs text-[#E99A00]">
                                    แจ้งแล้ว
                                </span>
                            </td>
                        </tr>

                        <tr class="border-b border-[#E5EAF0] bg-[#F8FAFC]">
                            <td class="px-6 py-4">MNT-004</td>
                            <td class="px-6 py-4">301</td>
                            <td class="px-6 py-4">ประตูล็อคไม่ได้</td>
                            <td class="px-6 py-4">2024-07-08</td>
                            <td class="px-6 py-4">
                                <span
                                    class="rounded-md border border-[#70E5B5] bg-[#ECFDF5] px-3 py-1 text-xs text-[#00A875]">
                                    เสร็จแล้ว
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <td class="px-6 py-4">MNT-005</td>
                            <td class="px-6 py-4"></td>
                            <td class="px-6 py-4">ไฟทางเดินชั้น 3 ดับ</td>
                            <td class="px-6 py-4">2024-07-16</td>
                            <td class="px-6 py-4">
                                <span
                                    class="rounded-md border border-[#A9CAFF] bg-[#EFF6FF] px-3 py-1 text-xs text-[#2161F5]">
                                    กำลังซ่อม
                                </span>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>

        </div>

    </div>

</x-layouts::app>
