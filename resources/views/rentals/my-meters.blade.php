<x-layouts::app.sidebar :title="'ค่าน้ำ-ค่าไฟของฉัน'">
    <main class="container-fluid py-4">
        <div class="mb-4">
            <h1 class="h3 fw-bold">ค่าน้ำ-ค่าไฟของฉัน</h1>
            <p class="text-secondary mb-0">ประวัติเลขมิเตอร์ในช่วงที่คุณเช่าห้อง</p>
        </div>

        @forelse ($usage as $item)
            <section class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h2 class="h5 mb-1">ห้อง {{ $item['rental']->room?->r_name ?? '—' }}</h2>
                    <small class="text-secondary">
                        ช่วงเช่า {{ $item['rental']->rt_movein?->format('d/m/Y') ?? '—' }}
                        ถึง {{ $item['rental']->rt_moveout?->format('d/m/Y') ?? 'ปัจจุบัน' }}
                    </small>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>วันที่บันทึก</th><th>มิเตอร์น้ำ</th><th>มิเตอร์ไฟ</th></tr></thead>
                        <tbody>
                            @forelse ($item['meters'] as $meter)
                                <tr>
                                    <td>{{ $meter->m_date?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $meter->m_water }}</td>
                                    <td>{{ $meter->m_elec }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-secondary py-4">ยังไม่มีข้อมูลมิเตอร์ในช่วงเช่านี้</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <div class="card border-0 shadow-sm p-5 text-center text-secondary">ยังไม่พบประวัติการเช่าของบัญชีนี้</div>
        @endforelse
    </main>
</x-layouts::app.sidebar>
