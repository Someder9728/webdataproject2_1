<x-layouts::app :title="__('ประวัติการชำระเงิน')">
@include('invoices._base')

<div class="rt rt-page">
    <div class="rt-head">
        <div>
            <div class="rt-crumb"><a href="{{ route('invoices.index') }}">ค่าเช่าและการชำระเงิน</a> / ประวัติ</div>
            <h1 class="rt-title">ประวัติการชำระเงิน</h1>
            <div class="rt-subtitle" id="hs-subtitle">{{ number_format($invoices->total()) }} รายการ · รวม {{ \App\Support\Format::money($total) }}</div>
        </div>
        <div class="rt-head-actions">
            <a href="{{ route('invoices.index') }}" class="rt-btn">‹ ย้อนกลับ</a>
        </div>
    </div>

    <section class="rt-card rt-card--flush" id="hs-card" aria-label="ประวัติการชำระเงิน">
        <form method="GET" class="iv-toolbar" id="hs-filter">
            <input type="search" name="q" id="hs-search" class="rt-search" value="{{ $filters['q'] ?? '' }}" placeholder="ค้นหาห้อง, ผู้เช่า... แล้วกด Enter" aria-label="ค้นหา">
            <input type="month" name="month" id="hs-month" class="rt-input" value="{{ $filters['month'] ?? '' }}" aria-label="รอบบิลเดือน" onchange="this.form.submit()">
            @if (! empty($filters['month']))
                <a href="{{ route('invoices.history', array_filter(['q' => $filters['q'] ?? null])) }}" class="rt-btn rt-btn--sm" id="hs-month-clear">ทุกรอบบิล</a>
            @endif
        </form>
        <div class="rt-table-wrap">
            <table class="rt-table iv-table" style="min-width:820px;">
                <thead>
                    <tr>
                        @if ($isAdmin)
                            <th>ผู้เช่า</th>
                        @endif
                        <th>ห้อง</th>
                        <th>รอบบิล</th>
                        <th class="num">จำนวนเงิน</th>
                        <th>วันที่ชำระ</th>
                        <th>วิธีชำระ</th>
                        <th>สถานะ</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="hs-body">
                    @forelse ($invoices as $invoice)
                        <tr>
                            @if ($isAdmin)
                                <td>{{ trim($invoice->rental?->tenant?->t_Fname.' '.$invoice->rental?->tenant?->t_Lname) ?: '-' }}</td>
                            @endif
                            <td><span class="rt-badge rt-badge--info">ห้อง {{ $invoice->rental?->room?->r_name ?? '-' }}</span></td>
                            <td>{{ $invoice->period_start?->format('Y-m') ?? '-' }}</td>
                            <td class="num"><b>{{ \App\Support\Format::money($invoice->payment->p_amount ?? $invoice->i_total) }}</b></td>
                            <td>{{ $invoice->payment->p_date?->format('Y-m-d') ?? '-' }}</td>
                            <td>{{ \App\Support\Format::method($invoice->payment->p_type) }}</td>
                            <td>@include('invoices._status-badge')</td>
                            <td class="rt-actions">
                                <a class="rt-btn rt-btn--sm rt-btn--soft" href="{{ route('invoices.show', $invoice) }}"
                                   aria-label="ดูรายละเอียดใบแจ้งหนี้ {{ $invoice->number() }}">ดู</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="rt-state" id="hs-empty">
                                {{ ($filters['q'] ?? null) || ($filters['month'] ?? null) ? 'ไม่พบรายการที่ตรงกับคำค้นหา' : 'ยังไม่มีประวัติการชำระเงิน' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @include('rentals._pager', ['paginator' => $invoices])
</div>
</x-layouts::app>
