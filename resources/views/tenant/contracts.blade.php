<x-layouts::app.sidebar title="สัญญาของฉัน">
    @include('partials.admin-page-style')
    <div class="tenant-page">
        <header class="tenant-header"><h1 class="tenant-title">สัญญาของฉัน</h1><p class="tenant-subtitle">ตรวจสอบสัญญาของคุณ หากต้องการแก้ไขหรือต่อสัญญา กรุณาติดต่อผู้ดูแล</p></header>
        @forelse($rentals as $rental)
            @include('tenant.contract-card', ['contract' => $rental->contract])
        @empty <div class="tenant-search-card p-4 text-secondary">ยังไม่มีสัญญาเช่า</div> @endforelse
        {{ $rentals->links('pagination::bootstrap-5') }}
    </div>
</x-layouts::app.sidebar>
