<x-layouts::app.sidebar :title="'ค่าน้ำ-ค่าไฟของฉัน'">
    @include('partials.admin-page-style')

    <div class="tenant-page">
        <h1 class="tenant-title">ค่าน้ำ-ค่าไฟของฉัน</h1>
        <p class="tenant-subtitle mb-4">
            ประวัติเลขมิเตอร์ในช่วงที่คุณเช่าห้อง
            ยอดที่ต้องชำระให้ยึดตามใบแจ้งหนี้
        </p>

        @forelse ($usage as $item)
            <section class="tenant-search-card p-4 mb-4">
                <h2 class="h6 fw-semibold mb-2">
                    ห้อง {{ $item['rental']->room?->r_name ?? '—' }}
                </h2>

                <p class="tenant-subtitle mb-3">
                    ช่วงเช่า
                    {{ $item['rental']->rt_movein?->format('d/m/Y') ?? '—' }}
                    ถึง
                    {{ $item['rental']->rt_moveout?->format('d/m/Y') ?? 'ปัจจุบัน' }}
                </p>

                <div class="table-responsive">
                    <table class="table tenant-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>วันที่บันทึก</th>
                                <th>เลขมิเตอร์น้ำ</th>
                                <th>เลขมิเตอร์ไฟ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($item['meters'] as $meter)
                                <tr>
                                    <td>
                                        {{ $meter->m_date?->format('d/m/Y') ?? '—' }}
                                    </td>
                                    <td>{{ $meter->m_water }}</td>
                                    <td>{{ $meter->m_elec }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3"
                                        class="text-center text-secondary py-4">
                                        ยังไม่มีข้อมูลมิเตอร์ในช่วงเช่านี้
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <section class="tenant-search-card p-4">
                <p class="text-center text-secondary mb-0">
                    ยังไม่พบประวัติการเช่าของบัญชีนี้
                </p>
            </section>
        @endforelse
    </div>
</x-layouts::app.sidebar>