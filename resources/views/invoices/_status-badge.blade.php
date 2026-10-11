@php
    [$label, $class] = match ($invoice->statusKey()) {
        'paid' => ['ชำระแล้ว', 'rt-badge--ok'],
        'pending' => ['รอตรวจสอบ', 'rt-badge--info'],
        'rejected' => ['ถูกปฏิเสธ', 'rt-badge--err'],
        'overdue' => ['ค้างชำระ', 'rt-badge--err'],
        'unpaid' => ['รอชำระ', 'rt-badge--warn'],
        default => ['ไม่มีข้อมูลชำระ', 'rt-badge--off'],
    };
@endphp
<span class="rt-badge {{ $class }}">{{ $label }}</span>
