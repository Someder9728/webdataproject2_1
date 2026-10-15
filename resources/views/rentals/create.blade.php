<x-layouts::app :title="'บันทึกการเช่าใหม่'">
@include('rentals._styles')
<div class="rt rt-page"><div class="rt-modal">
<h1 class="rt-modal-title">บันทึกการเช่าใหม่</h1>
@if($errors->any())<div class="rt-notice rt-notice--err" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form onsubmit="return confirm('ยืนยันบันทึกการเช่าตามข้อมูลนี้หรือไม่?')" method="POST" action="{{ route('rentals.store') }}">@csrf
<div class="rt-form-field"><label for="f-tenant">ผู้เช่า</label><select class="rt-input" id="f-tenant" name="tenants_t_id" required><option value="">เลือกผู้เช่า</option>@foreach($tenants as $tenant)<option value="{{ $tenant->t_id }}" @selected(old('tenants_t_id') == $tenant->t_id)>{{ $tenant->t_Fname }} {{ $tenant->t_Lname }}</option>@endforeach</select></div>
<div class="rt-form-field"><label for="f-room">ห้อง</label><select class="rt-input" id="f-room" name="rooms_r_id" required><option value="">เลือกห้อง</option>@foreach($rooms as $room)<option value="{{ $room->r_id }}" data-rent="{{ $room->r_rent }}" @selected(old('rooms_r_id') == $room->r_id)>{{ $room->r_name }} · {{ $room->r_type }} · {{ number_format((float)$room->r_rent, 2) }} บาท/เดือน</option>@endforeach</select><p class="rt-hint">ระบบตรวจช่วงเวลาของห้องและผู้เช่าก่อนบันทึก แม้ห้องยังมีผู้พักอยู่ก็จองต่อได้เมื่อกำหนดย้ายออกไม่ทับกัน</p></div>
<div class="rt-form-row"><div class="rt-form-field"><label for="f-movein">วันเริ่มเช่า / วันเข้าอยู่จริง</label><input id="f-movein" class="rt-input" type="date" name="rt_movein" value="{{ old('rt_movein', now('Asia/Bangkok')->toDateString()) }}" required><p class="rt-hint">เลือกวันย้อนหลังได้สำหรับผู้ที่ยังพักอยู่ ต้องมีมิเตอร์ตรงวันเข้าอยู่จริง ระบบจะบันทึกเป็นกำลังเช่าและตรวจประวัติไม่ให้ทับซ้อน เวลาเพิ่มข้อมูลยังเป็นเวลาปัจจุบัน ไม่สร้างบิลย้อนหลังให้อัตโนมัติ หากเลือกอนาคตจะเป็นรายการรอเข้าอยู่</p></div>
<div class="rt-form-field"><label for="f-end">วันสิ้นสุดสัญญา</label><input id="f-end" class="rt-input" type="date" name="c_end" value="{{ old('c_end') }}"><p class="rt-hint">เว้นว่างได้ วันสิ้นสุดสัญญาไม่ใช่วันย้ายออกจริง</p></div></div>
<div class="rt-form-row"><div class="rt-form-field"><label for="f-rent">ค่าเช่ารายเดือน</label><input id="f-rent" class="rt-input" type="number" name="c_rent" min="0" max="99999999.99" step="0.01" value="{{ old('c_rent') }}" required></div>
<div class="rt-form-field"><label for="f-deposit">เงินประกัน</label><input id="f-deposit" class="rt-input" type="number" name="c_deposit" min="0" max="99999999.99" step="0.01" value="{{ old('c_deposit', '0.00') }}" required></div></div>
<div class="rt-form-actions"><a class="rt-btn" href="{{ route('rentals.index') }}">ยกเลิก</a><button class="rt-btn rt-btn--primary" type="submit">บันทึกการเช่า</button></div>
</form></div></div>
<script>document.getElementById('f-room').addEventListener('change', e => { document.getElementById('f-rent').value = e.target.selectedOptions[0]?.dataset.rent || ''; });</script>
</x-layouts::app>
