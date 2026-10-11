<x-layouts::app :title="$invoice->number()">
@include('invoices._base')
@php
    $payment = $invoice->payment;
    $status = $invoice->statusKey();
    $due = $invoice->i_due->format('Y-m-d');
    $eventMeta = [
        'PROOF_SUBMITTED' => ['ผู้เช่าส่งหลักฐานการชำระ', ''],
        'PAYMENT_APPROVED' => ['อนุมัติการชำระเงิน', 'rt-dot--ok'],
        'PAYMENT_REJECTED' => ['ปฏิเสธหลักฐานการชำระ', 'rt-dot--end'],
        'WALK_IN_RECORDED' => ['บันทึกรับชำระหน้าเคาน์เตอร์', 'rt-dot--ok'],
    ];
@endphp

<div class="rt rt-page" id="iv-root">
    @include('invoices._pay-modals')
    @if ($isAdmin)
        @include('invoices._edit-modal')
    @endif

    <div class="rt-head">
        <div>
            <div class="rt-crumb"><a href="{{ route('invoices.index') }}">ใบแจ้งหนี้ / การชำระ</a> / <span id="sh-crumb">{{ $invoice->number() }}</span></div>
            <h1 class="rt-title" id="sh-title">ใบแจ้งหนี้ {{ $invoice->number() }}</h1>
        </div>
        <div class="rt-head-actions" id="sh-actions" data-id="{{ $invoice->getKey() }}">
            @if ($isAdmin && $invoice->isEditable())
                <button type="button" class="rt-btn" id="btn-edit" data-open="edit">แก้ไขใบแจ้งหนี้</button>
            @endif
            @if ($isAdmin && $invoice->canRecordPayment())
                <button type="button" class="rt-btn rt-btn--pay" id="btn-walkin" data-open="walkIn">บันทึกการชำระเงิน</button>
            @endif
            @if ($isAdmin && $payment?->p_status === 'PENDING')
                <button type="button" class="rt-btn rt-btn--primary" id="btn-review" data-open="review">ตรวจสอบหลักฐาน</button>
            @endif
            @if (! $isAdmin && $invoice->canRecordPayment())
                <button type="button" class="rt-btn rt-btn--primary" id="btn-submit" data-open="submitProof">ส่งหลักฐานการชำระ</button>
            @endif
            <a href="{{ route('invoices.index') }}" class="rt-btn">‹ ย้อนกลับ</a>
        </div>
    </div>

    <div class="rt-grid iv-grid" id="sh-content">
        <div class="rt-col">
            <section class="rt-card" aria-labelledby="sh-no-label">
                <div class="rt-card-head" style="align-items:flex-start;">
                    <div>
                        <div class="rt-field-label" id="sh-no-label">เลขที่ใบแจ้งหนี้</div>
                        <div class="rt-field-value rt-mono" id="sh-no" style="font-size:1.1rem;">{{ $invoice->number() }}</div>
                    </div>
                    <span id="sh-badge">@include('invoices._status-badge')</span>
                </div>
                <dl class="rt-fields">
                    <div><dt class="rt-field-label">ผู้เช่า</dt><dd class="rt-field-value" style="margin:0;" id="sh-tenant">{{ trim($invoice->rental?->tenant?->t_Fname.' '.$invoice->rental?->tenant?->t_Lname) ?: '-' }}</dd></div>
                    <div><dt class="rt-field-label">ห้อง</dt><dd class="rt-field-value" style="margin:0;" id="sh-room">
                        @if ($isAdmin && $invoice->rentals_rt_id)
                            <a class="rt-link" href="{{ route('rentals.show', $invoice->rentals_rt_id) }}">ห้อง {{ $invoice->rental?->room?->r_name ?? '-' }}</a>
                        @else
                            ห้อง {{ $invoice->rental?->room?->r_name ?? '-' }}
                        @endif
                    </dd></div>
                    <div><dt class="rt-field-label">รอบบิล</dt><dd class="rt-field-value" style="margin:0;" id="sh-month">{{ $invoice->period_start?->format('Y-m') ?? '-' }}</dd></div>
                    <div><dt class="rt-field-label">วันครบกำหนด</dt><dd class="rt-field-value {{ $status === 'overdue' ? 'iv-due--late' : '' }}" style="margin:0;" id="sh-due">{{ $due }}</dd></div>
                    <div><dt class="rt-field-label">ช่วงคิดเงิน</dt><dd class="rt-field-value" style="margin:0;" id="sh-period">{{ $invoice->period_start?->format('Y-m-d') }} → {{ $invoice->period_end?->format('Y-m-d') }}</dd></div>
                    <div><dt class="rt-field-label">วันที่ออกใบแจ้งหนี้</dt><dd class="rt-field-value" style="margin:0;" id="sh-issued">{{ $invoice->i_date->format('Y-m-d') }}</dd></div>
                </dl>

                <div class="iv-bill">
                    <div class="iv-bill-head">รายละเอียดค่าใช้จ่าย</div>
                    <div class="iv-bill-row"><span>ค่าเช่ารายเดือน<small>อัตรา {{ \App\Support\Format::money($invoice->rent_rate) }} / เดือน (คิดตามจำนวนวันในช่วง)</small></span><b id="sh-rent">{{ \App\Support\Format::money($invoice->i_rent) }}</b></div>
                    <div class="iv-bill-row"><span>ค่าน้ำ<small>{{ \App\Support\Format::num($invoice->water_usage) }} หน่วย × {{ \App\Support\Format::money($invoice->water_rate) }}</small></span><b id="sh-water">{{ \App\Support\Format::money($invoice->i_water) }}</b></div>
                    <div class="iv-bill-row"><span>ค่าไฟ<small>{{ \App\Support\Format::num($invoice->elec_usage) }} หน่วย × {{ \App\Support\Format::money($invoice->elec_rate) }}</small></span><b id="sh-elec">{{ \App\Support\Format::money($invoice->i_elec) }}</b></div>
                    <div class="iv-bill-total"><span>ยอดรวม</span><b id="sh-total">{{ \App\Support\Format::money($invoice->i_total) }}</b></div>
                </div>
            </section>
        </div>

        <div class="rt-col">
            <section class="rt-card" aria-labelledby="sh-pay-title">
                <div class="rt-card-head"><h2 class="rt-card-title rt-card-title--lg" id="sh-pay-title">การชำระเงิน</h2></div>
                <div id="sh-paybox">
                    @if (! $payment)
                        <div class="rt-notice rt-notice--warn" style="margin:0;">ไม่พบข้อมูลการชำระเงินของใบแจ้งหนี้นี้</div>
                    @elseif ($payment->p_status === 'PAID')
                        <div class="rt-notice rt-notice--ok" style="margin:0;">
                            ✓ ชำระแล้วเมื่อวันที่ {{ $payment->p_date?->format('Y-m-d') ?? '-' }} ผ่าน{{ \App\Support\Format::method($payment->p_type) }}
                            <div style="margin-top:4px;font-size:0.8rem;">ยอดที่ชำระ {{ \App\Support\Format::money($payment->p_amount) }}</div>
                        </div>
                    @elseif ($payment->p_status === 'PENDING')
                        <div class="rt-notice rt-notice--info" style="margin:0;">
                            ส่งหลักฐานแล้ว (วันที่โอน {{ $payment->p_date?->format('Y-m-d') ?? '-' }}, ยอด {{ \App\Support\Format::money($payment->p_amount) }}) — รอผู้ดูแลตรวจสอบ
                        </div>
                    @elseif ($payment->p_status === 'REJECTED')
                        <div class="rt-notice rt-notice--err" style="margin:0;">
                            หลักฐานการชำระถูกปฏิเสธ
                            @if ($payment->p_reject_reason)
                                <div style="margin-top:4px;">เหตุผล: {{ $payment->p_reject_reason }}</div>
                            @endif
                            <div style="margin-top:4px;font-size:0.8rem;">{{ $isAdmin ? 'รอผู้เช่าส่งหลักฐานใหม่ หรือบันทึกรับชำระหน้าเคาน์เตอร์ได้' : 'กรุณาส่งหลักฐานใหม่' }}</div>
                        </div>
                    @elseif ($status === 'overdue')
                        <div class="rt-notice rt-notice--err" style="margin:0;">
                            ค้างชำระ — เกินกำหนด {{ $invoice->daysLate() }} วัน (ครบกำหนด {{ $due }})
                        </div>
                    @else
                        <div class="rt-notice rt-notice--warn" style="margin:0;">ยังไม่ชำระ — ครบกำหนด {{ $due }}</div>
                    @endif

                    @if (in_array($payment?->p_status, ['PAID', 'PENDING'], true) && filled($payment->p_proof))
                        <a class="rt-btn rt-btn--sm" style="margin-top:10px;" href="{{ route('api.v1.invoices.payment.proof', $invoice) }}">
                            {{ $payment->p_status === 'PAID' ? 'ดาวน์โหลดหลักฐานการโอน' : 'ดาวน์โหลดหลักฐานที่ส่ง' }}
                        </a>
                    @endif
                    @if ($isAdmin && $payment?->p_status === 'UNPAID' && ! $invoice->isEditable())
                        <p class="rt-hint" style="margin-top:8px;">ใบนี้มีประวัติการชำระแล้ว จึงแก้ไขใบแจ้งหนี้ไม่ได้</p>
                    @endif
                </div>
            </section>

            <section class="rt-card" aria-labelledby="sh-hist-title">
                <div class="rt-card-head"><h2 class="rt-card-title rt-card-title--lg" id="sh-hist-title">ประวัติการดำเนินการ</h2></div>
                <div id="sh-hist">
                    @if ($events->isEmpty())
                        <p class="rt-muted" style="margin:0;font-size:0.875rem;">ยังไม่มีประวัติการชำระ</p>
                    @else
                        <ul class="rt-timeline">
                            @foreach ($events as $event)
                                @php
                                    [$title, $dot] = $eventMeta[$event->event_type] ?? [$event->event_type, ''];
                                    $bits = array_filter([
                                        $event->amount !== null ? \App\Support\Format::money($event->amount) : null,
                                        $event->method ? \App\Support\Format::method($event->method) : null,
                                        $event->payment_date ? 'วันที่ชำระ '.$event->payment_date->format('Y-m-d') : null,
                                    ]);
                                @endphp
                                <li>
                                    <span class="rt-dot {{ $dot }}"></span>
                                    <div>
                                        <div>
                                            <span class="rt-tl-date">{{ $event->created_at?->timezone('Asia/Bangkok')->format('Y-m-d H:i') ?? '-' }}</span>
                                            <span class="rt-tl-title">{{ $title }}</span>
                                        </div>
                                        @if ($bits)
                                            <div class="rt-tl-desc">{{ implode(' · ', $bits) }}</div>
                                        @endif
                                        <div class="rt-tl-desc">
                                            @if ($isAdmin)
                                                {{ $event->actor?->u_username ? 'โดย '.$event->actor->u_username : 'โดยผู้ใช้ที่ถูกลบ' }}
                                            @else
                                                {{ (string) $event->actor_user_id === (string) auth()->id() ? 'โดยคุณ' : 'โดยผู้ดูแลระบบ' }}
                                            @endif
                                        </div>
                                        @if ($event->reason)
                                            <div class="rt-tl-reason">เหตุผล: {{ $event->reason }}</div>
                                        @endif
                                        @if ($event->note)
                                            <div class="rt-tl-reason">หมายเหตุ: {{ $event->note }}</div>
                                        @endif
                                        @if (filled($event->proof_path))
                                            <a class="rt-btn rt-btn--sm" style="margin-top:6px;" href="{{ route('api.v1.payment-events.proof', $event) }}">ดาวน์โหลดหลักฐาน</a>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>

<script>
(function () {
    const box = document.getElementById('sh-actions');
    const id = box.dataset.id;
    const reload = () => window.location.reload();
    box.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-open]');
        if (!btn) return;
        if (btn.dataset.open === 'edit') window.InvoiceEdit?.open(id, reload);
        else window.InvoicePay[btn.dataset.open](id, reload);
    });
})();
</script>
</x-layouts::app>
