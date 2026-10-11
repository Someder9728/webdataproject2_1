@if ($paginator->hasPages())
    <nav class="rt-pagination" aria-label="เปลี่ยนหน้า">
        @if ($paginator->onFirstPage())
            <span class="rt-btn rt-btn--sm" aria-disabled="true">ก่อนหน้า</span>
        @else
            <a class="rt-btn rt-btn--sm" href="{{ $paginator->previousPageUrl() }}" rel="prev">ก่อนหน้า</a>
        @endif
        <span>หน้า {{ $paginator->currentPage() }} จาก {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a class="rt-btn rt-btn--sm" href="{{ $paginator->nextPageUrl() }}" rel="next">ถัดไป</a>
        @else
            <span class="rt-btn rt-btn--sm" aria-disabled="true">ถัดไป</span>
        @endif
    </nav>
@endif
