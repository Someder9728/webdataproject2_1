<section class="rt-card">
<h2 class="rt-card-title">กำหนดการเข้าและย้ายออก</h2>
@if(session('success'))<p class="rt-notice rt-notice--info" role="status">{{ session('success') }}</p>@endif
@if($errors->any())<div class="rt-notice rt-notice--err" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<p>กำหนดเข้าพัก {{ $rental->scheduled_movein_at?->format('d/m/Y') ?? '—' }} · กำหนดย้ายออก {{ $rental->scheduled_moveout_at?->format('d/m/Y') ?? 'ยังไม่กำหนด' }}</p>
@if($rental->rt_status === 'SCHEDULED')
<p class="rt-hint">ยังไม่เข้าอยู่จริง ต้องมีมิเตอร์ตรงวันที่ยืนยันและห้องต้องว่าง ระบบจึงเริ่มคิดบิลได้</p>
<form method="POST" action="{{ route('rentals.activate', $rental) }}" onsubmit="return confirm('ยืนยันเข้าอยู่จริงวันนี้หรือไม่?')">@csrf<button class="rt-btn rt-btn--primary" type="submit">ยืนยันเข้าอยู่วันนี้</button></form>
<form method="POST" action="{{ route('rentals.cancel', $rental) }}" onsubmit="return confirm('ยืนยันยกเลิกการจองนี้หรือไม่?')">@csrf<div class="rt-form-field"><label>เหตุผลยกเลิก<input class="rt-input" name="reason" maxlength="2000" required></label></div><button class="rt-btn rt-btn--danger" type="submit">ยกเลิกการจอง</button></form>
@elseif($rental->rt_status === 'ACTIVE')
<form method="POST" action="{{ route('rentals.schedule-moveout', $rental) }}" onsubmit="return confirm('ยืนยันเปลี่ยนกำหนดย้ายออกหรือไม่?')">@csrf
<div class="rt-form-field"><label>วันย้ายออกตามกำหนด<input class="rt-input" type="date" name="scheduled_moveout_at" min="{{ now('Asia/Bangkok')->addDay()->toDateString() }}" value="{{ old('scheduled_moveout_at', $rental->scheduled_moveout_at?->toDateString()) }}"></label><p class="rt-hint">เว้นว่างเพื่อยกเลิกกำหนดการ ห้องยังมีผู้พักและคิดบิลตามปกติจนกว่าจะยืนยันย้ายออกจริง</p></div>
<div class="rt-form-field"><label>เหตุผล<input class="rt-input" name="reason" maxlength="2000" required value="{{ old('reason') }}"></label></div><button class="rt-btn rt-btn--primary" type="submit">บันทึกกำหนดการ</button></form>
@endif
@if($lifecycleEvents->isNotEmpty())
<h3 class="rt-card-title">ประวัติกำหนดการ</h3>
<ul class="rt-timeline">@foreach($lifecycleEvents as $event)<li><div>
<strong>{{ ['rental_activated' => 'ยืนยันเข้าอยู่จริง', 'moveout_schedule_changed' => 'เปลี่ยนกำหนดย้ายออก', 'scheduled_rental_cancelled' => 'ยกเลิกการจอง', 'rental_moved_out' => 'ย้ายออกจริง'][$event->action] }}</strong>
<p class="rt-hint">{{ $event->created_at->timezone('Asia/Bangkok')->format('d/m/Y H:i') }} · โดย {{ $event->actor?->u_username ?? 'ระบบ' }}</p>
@if($event->action === 'moveout_schedule_changed')<p>กำหนดย้ายออก {{ $event->old_values['scheduled_moveout_at'] ?? 'ไม่กำหนด' }} → {{ $event->new_values['scheduled_moveout_at'] ?? 'ไม่กำหนด' }}</p>@endif
@if($event->reason)<p>เหตุผล {{ $event->reason }}</p>@endif
</div></li>@endforeach</ul>
@endif
</section>
