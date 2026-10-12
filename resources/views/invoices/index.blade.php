<x-layouts::app :title="__('ใบแจ้งหนี้ / การชำระ')">
@include('invoices._base')
<div class="rt rt-page" id="iv-root" data-admin="{{ $isAdmin ? '1' : '0' }}">
    @include('invoices._pay-modals')
    @if($isAdmin) @include('invoices._issue-modal') @endif
    <div class="rt-head"><div><div class="rt-crumb">ใบแจ้งหนี้ / การชำระ</div><h1 class="rt-title">ค่าเช่าและการชำระเงิน</h1><div class="rt-subtitle">{{ $filters['month'] ?? 'ทุกรอบบิล' }}</div></div>
        <div class="rt-head-actions"><a href="{{ route('invoices.history') }}" class="rt-btn">ประวัติการชำระเงิน</a>@if($isAdmin)<button type="button" class="rt-btn rt-btn--primary" id="btn-issue">+ ออกใบแจ้งหนี้</button>@endif</div>
    </div>
    @if(!$isAdmin && $stats['waiting']['count'] + $stats['overdue']['count'] > 0)
        <div class="rt-notice rt-notice--warn" role="status">มีรายการที่ยังไม่ได้ชำระหรือรอตรวจสอบ กรุณาดูสถานะในใบแจ้งหนี้</div>
    @endif
    <div class="iv-stats">
        @foreach(['total' => 'ยอดรวมทั้งหมด', 'paid' => 'ชำระแล้ว', 'waiting' => 'รอชำระ', 'overdue' => 'ค้างชำระ'] as $key => $label)
            <div class="iv-stat"><span class="iv-stat-icon iv-stat-icon--{{ ['total' => 'total', 'paid' => 'ok', 'waiting' => 'warn', 'overdue' => 'err'][$key] }}" data-stat-icon="{{ ['total' => 'card', 'paid' => 'check', 'waiting' => 'clock', 'overdue' => 'alert'][$key] }}"></span><div><div class="iv-stat-label">{{ $label }}</div><div class="iv-stat-value">฿{{ number_format((float)$stats[$key]['amount'], 2) }}</div><div class="iv-stat-sub">{{ $stats[$key]['count'] }} ใบ</div></div></div>
        @endforeach
    </div>
    <section class="rt-card rt-card--flush" aria-label="รายการใบแจ้งหนี้">
        <form class="iv-toolbar" method="GET" action="{{ route('invoices.index') }}">
            <input type="search" name="q" class="rt-search" value="{{ $filters['q'] ?? '' }}" placeholder="ค้นหาห้อง, ผู้เช่า, รหัสใบแจ้งหนี้..." aria-label="ค้นหา">
            <input type="month" name="month" class="rt-input" value="{{ $filters['month'] ?? '' }}" aria-label="รอบบิลเดือน">
            <select name="status" class="rt-input" aria-label="สถานะ">@foreach(['all' => 'สถานะทั้งหมด', 'unpaid' => 'รอชำระ', 'overdue' => 'ค้างชำระ', 'pending' => 'รอตรวจสอบ', 'rejected' => 'ถูกปฏิเสธ', 'paid' => 'ชำระแล้ว'] as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? 'all') === $value)>{{ $label }}</option>@endforeach</select>
            <button class="rt-btn" type="submit">ค้นหา</button><a class="rt-btn" href="{{ route('invoices.index') }}">ล้าง</a>
        </form>
        <div class="rt-table-wrap"><table class="rt-table iv-table"><thead><tr><th>ห้อง</th>@if($isAdmin)<th>ผู้เช่า</th>@endif<th>รอบบิล</th><th class="num">ค่าเช่า</th><th class="num">ค่าน้ำ</th><th class="num">ค่าไฟ</th><th class="num">รวม</th><th>วันครบกำหนด</th><th>สถานะ</th><th></th></tr></thead><tbody>
        @forelse($invoices as $invoice)
            <tr><td><span class="iv-room">{{ $invoice->rental?->room?->r_name ?? '—' }}</span></td>@if($isAdmin)<td>{{ $invoice->rental?->tenant?->t_Fname }} {{ $invoice->rental?->tenant?->t_Lname }}</td>@endif<td>{{ $invoice->period_start?->format('Y-m') }}</td>
                @foreach(['i_rent', 'i_water', 'i_elec', 'i_total'] as $field)<td class="num">฿{{ number_format((float)$invoice->$field, 2) }}</td>@endforeach
                <td class="{{ $invoice->statusKey() === 'overdue' ? 'iv-due--late' : '' }}">{{ $invoice->i_due->format('Y-m-d') }}</td><td>@include('invoices._status-badge', ['invoice' => $invoice])</td>
                <td class="rt-actions"><a class="iv-icon-btn" href="{{ route('invoices.show', $invoice) }}" aria-label="ดูรายละเอียด {{ $invoice->number() }}">ดู</a>
                @if($invoice->canRecordPayment())<button type="button" class="rt-btn rt-btn--sm rt-btn--pay" data-payment="{{ $isAdmin ? 'walkIn' : 'submitProof' }}" data-invoice="{{ $invoice->getKey() }}">{{ $isAdmin ? 'ชำระ' : 'ส่งหลักฐาน' }}</button>
                @elseif($isAdmin && $invoice->payment?->p_status === 'PENDING')<button type="button" class="rt-btn rt-btn--sm" data-payment="review" data-invoice="{{ $invoice->getKey() }}">ตรวจสอบ</button>@endif</td>
            </tr>
        @empty <tr><td colspan="{{ $isAdmin ? 10 : 9 }}" class="rt-state">ไม่พบใบแจ้งหนี้ที่ตรงกับเงื่อนไข</td></tr> @endforelse
        </tbody></table></div><div class="p-3">{{ $invoices->links() }}</div>
    </section>
</div>
<script>
(() => {
    const refresh = () => window.location.reload();
    document.querySelectorAll('[data-stat-icon]').forEach(node => node.appendChild(IV.icon(node.dataset.statIcon)));
    document.querySelectorAll('[data-payment]').forEach(button => button.addEventListener('click', () => window.InvoicePay[button.dataset.payment](Number(button.dataset.invoice), refresh)));
    document.getElementById('btn-issue')?.addEventListener('click', () => window.InvoiceIssue.open(refresh));
})();
</script>
</x-layouts::app>
