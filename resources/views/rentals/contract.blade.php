@php
    $canEditContract = auth()->user()?->u_role === 'admin';
    [$statusText, $statusClass] = match ($contract?->c_status) {
        'SCHEDULED' => ['รอเริ่มสัญญา', 'rt-badge--info'],
        'CANCELLED' => ['ยกเลิกแล้ว', 'rt-badge--off'],
        'ACTIVE' => ['มีผล', 'rt-badge--ok'],
        'EXPIRED' => ['หมดอายุ (ยังพักอยู่)', 'rt-badge--warn'],
        'ENDED' => ['สิ้นสุด', 'rt-badge--off'],
        default => [$contract?->c_status, 'rt-badge--off'],
    };
    $lockNote = request('contract_conflict')
        ? 'แก้ไขสัญญาไม่ได้: การเช่านี้ย้ายออกแล้ว หรือสัญญาสิ้นสุดแล้ว — ข้อมูลด้านบนเป็นข้อมูลล่าสุดแล้ว'
        : $lockReason;
@endphp

<section class="rt-card contract-panel" aria-labelledby="contract-card-title">
    @if (! $contract)
        <div class="rt-state rt-state--error">ไม่พบสัญญาของการเช่านี้</div>
    @else
        <div id="contract-view">
            <div class="rt-card-head">
                <h2 class="rt-card-title" id="contract-card-title">ข้อมูลสัญญาเช่า</h2>
                <span id="c-status" class="rt-badge {{ $statusClass }}">{{ $statusText }}</span>
            </div>

            <div class="rt-field-value rt-mono" id="c-number" style="margin-bottom:12px;">{{ $contract->c_number }}</div>

            <div class="rt-fields">
                <div><span class="rt-field-label">วันเริ่มสัญญา</span><span class="rt-field-value" id="c-start">{{ $contract->c_start->format('Y-m-d') }}</span></div>
                <div><span class="rt-field-label">วันสิ้นสุดสัญญา</span><span class="rt-field-value" id="c-end">{{ $contract->c_end?->format('Y-m-d') ?? 'ไม่กำหนดวันสิ้นสุด' }}</span></div>
                <div>
                    <span class="rt-field-label">ค่าเช่าตามสัญญา</span>
                    <span class="rt-field-value rt-money" id="c-rent">฿{{ number_format((float) $contract->c_rent, 2) }}/เดือน</span>
                    @if ($priceLocked)
                        <span class="rt-hint" id="c-price-locked">ล็อกแล้ว — ออกใบแจ้งหนี้ไปแล้ว</span>
                    @endif
                </div>
                <div><span class="rt-field-label">เงินประกัน</span><span class="rt-field-value" id="c-deposit">฿{{ number_format((float) $contract->c_deposit, 2) }}</span></div>
            </div>

            @if ($contract->c_status === 'EXPIRED')
                <p id="c-expired-note" class="rt-notice rt-notice--warn" style="margin-top:14px;margin-bottom:0;">
                    สัญญาหมดอายุแล้วแต่ยังพักอยู่ — ยังออกบิลต่อได้ตามปกติ ห้องจะไม่ถูกปรับเป็นว่างเอง
                </p>
            @endif
            @if ($lockNote)
                <p id="contract-locked-note" class="rt-notice rt-notice--info" style="margin-top:14px;margin-bottom:0;">{{ $lockNote }}</p>
            @endif
            @if (request('contract_saved'))
                <p id="contract-saved-note" class="rt-notice rt-notice--ok" style="margin-top:14px;margin-bottom:0;">บันทึกการแก้ไขสัญญาเรียบร้อย</p>
            @endif

            @if ($canEditContract && ! $lockNote)
                <div style="margin-top:16px;">
                    <button type="button" id="contract-btn-edit" class="rt-btn rt-btn--sm">แก้ไข / ต่อสัญญา</button>
                </div>
            @endif
        </div>

        @if ($canEditContract && ! $lockNote)
            <form id="contract-edit-form" novalidate hidden style="margin-top:16px;padding-top:16px;border-top:1px solid var(--rt-border);"
                  data-url="{{ url('/api/v1/rentals/'.$rental->rt_id.'/contract') }}"
                  data-start="{{ $contract->c_start->format('Y-m-d') }}" data-end="{{ $contract->c_end?->format('Y-m-d') }}">
                <h3 class="rt-card-title rt-card-title--lg" style="margin-bottom:12px;">แก้ไข / ต่อสัญญา</h3>

                <div class="rt-form-field">
                    <label for="f-c-end">วันสิ้นสุดสัญญาใหม่ <span class="rt-muted" style="font-weight:400;">(เว้นว่าง = ไม่กำหนด)</span></label>
                    <input type="date" id="f-c-end" name="c_end" class="rt-input" value="{{ $contract->c_end?->format('Y-m-d') }}"
                           min="{{ $contract->c_start->copy()->addDay()->format('Y-m-d') }}">
                    <span class="rt-hint">เลือกได้ตั้งแต่ {{ $contract->c_start->copy()->addDay()->format('Y-m-d') }} (หลังวันเริ่มสัญญา)</span>
                    @if ($contract->c_end)
                        <div class="rt-chips" id="extend-chips">
                            <span class="rt-hint" style="margin:0 4px 0 0;align-self:center;">ต่อเพิ่ม:</span>
                            <button type="button" class="rt-chip" data-months="1">+1 เดือน</button>
                            <button type="button" class="rt-chip" data-months="3">+3 เดือน</button>
                            <button type="button" class="rt-chip" data-months="6">+6 เดือน</button>
                            <button type="button" class="rt-chip" data-months="12">+1 ปี</button>
                        </div>
                        <span class="rt-hint" id="extend-hint"></span>
                    @endif
                    <span class="rt-field-error" id="err-c-end"></span>
                </div>

                <div class="rt-form-field">
                    <label for="f-reason">เหตุผล <span class="rt-req">*</span></label>
                    <textarea id="f-reason" name="reason" class="rt-input" required maxlength="500" placeholder="เช่น ผู้เช่าขอต่อสัญญาอีก 6 เดือน"></textarea>
                    <span class="rt-hint" id="reason-count">0 / 500</span>
                    <span class="rt-field-error" id="err-reason"></span>
                </div>

                <p id="contract-form-error" class="rt-notice rt-notice--err" hidden></p>

                <div class="rt-form-actions" style="margin-top:8px;">
                    <button type="button" id="contract-btn-cancel" class="rt-btn">ยกเลิก</button>
                    <button type="submit" id="contract-btn-save" class="rt-btn rt-btn--primary">บันทึก</button>
                </div>
            </form>

            <script>
            (function () {
                const $ = (id) => document.getElementById(id);
                const form = $('contract-edit-form');
                const start = form.dataset.start;
                const end = form.dataset.end || null;
                const todayStr = () => new Date().toLocaleDateString('sv-SE', { timeZone: 'Asia/Bangkok' });
                const extendBase = () => (end && end >= todayStr() ? end : todayStr());
                let saving = false;

                function addMonths(dateStr, months) {
                    const [y, m, d] = dateStr.split('-').map(Number);
                    const target = new Date(Date.UTC(y, m - 1 + months, 1));
                    const lastDay = new Date(Date.UTC(target.getUTCFullYear(), target.getUTCMonth() + 1, 0)).getUTCDate();
                    target.setUTCDate(Math.min(d, lastDay));
                    return target.toISOString().slice(0, 10);
                }

                function toggle(editing) {
                    form.hidden = !editing;
                    $('contract-btn-edit').hidden = editing;
                }

                function clearErrors() {
                    $('err-c-end').textContent = '';
                    $('err-reason').textContent = '';
                    $('contract-form-error').hidden = true;
                }

                $('contract-btn-edit').addEventListener('click', () => {
                    if ($('extend-hint')) $('extend-hint').textContent = `ต่อเพิ่มจะนับจาก ${extendBase()} — ยังไม่บันทึกจนกว่าจะกด "บันทึก"`;
                    toggle(true);
                    $('f-c-end').focus();
                });
                $('contract-btn-cancel').addEventListener('click', () => toggle(false));
                $('f-reason').addEventListener('input', () => { $('reason-count').textContent = `${$('f-reason').value.length} / 500`; });
                $('extend-chips')?.addEventListener('click', (e) => {
                    const btn = e.target.closest('[data-months]');
                    if (btn) $('f-c-end').value = addMonths(extendBase(), Number(btn.dataset.months));
                });

                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    if (saving) return;
                    clearErrors();

                    const newEnd = $('f-c-end').value || null;
                    const reason = $('f-reason').value.trim();
                    if (newEnd && newEnd <= start) $('err-c-end').textContent = `วันสิ้นสุดต้องอยู่หลังวันเริ่มสัญญา (${start})`;
                    else if (newEnd === end) $('err-c-end').textContent = 'ไม่มีข้อมูลเปลี่ยนแปลง — เลือกวันสิ้นสุดใหม่ หรือเว้นว่างเพื่อไม่กำหนด';
                    if (!reason) $('err-reason').textContent = 'กรุณาระบุเหตุผล';
                    if ($('err-c-end').textContent || $('err-reason').textContent) return;

                    saving = true;
                    $('contract-btn-save').disabled = true;
                    try {
                        const res = await fetch(form.dataset.url, {
                            method: 'PATCH',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                            },
                            body: JSON.stringify({ c_end: newEnd, reason }),
                        });
                        if (res.status === 401) { window.location.href = '/login'; return; }
                        if (res.ok || res.status === 409) {
                            window.location.href = window.location.pathname + (res.ok ? '?contract_saved=1' : '?contract_conflict=1');
                            return;
                        }
                        const body = await res.json().catch(() => ({}));
                        if (res.status === 422 && (body.errors?.c_end || body.errors?.reason)) {
                            $('err-c-end').textContent = body.errors.c_end?.[0] ?? '';
                            $('err-reason').textContent = body.errors.reason?.[0] ?? '';
                            return;
                        }
                        throw new Error(res.status === 419 ? 'หน้านี้หมดอายุ กรุณารีเฟรชหน้าแล้วลองใหม่' : 'บันทึกไม่สำเร็จ (' + res.status + ')');
                    } catch (err) {
                        $('contract-form-error').textContent = err instanceof TypeError ? 'เชื่อมต่อเซิร์ฟเวอร์ไม่ได้' : err.message;
                        $('contract-form-error').hidden = false;
                    } finally {
                        saving = false;
                        $('contract-btn-save').disabled = false;
                    }
                });
            })();
            </script>
        @endif
    @endif
</section>
