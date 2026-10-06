# Backend completion — 6 October 2026

## ฐานงานและขอบเขต

ตรวจ repository จริงและ clone branches ก่อนแก้ ไม่มีการแก้ GitHub remote หรือ merge งาน teammate ระหว่างงานนี้
Checkout อยู่ใน `work/webdataproject2_1`, branch `backend-completion`, HEAD ฐาน `2959b54991346afb839a46589f8e076006dfe0f6` จาก `feature/meter-repair`.
Branch นี้รวม Move-out, Invoice Edit, Payment, Meter/Repair และ type fixes แล้ว จึงใช้เป็นฐานแทน `main` ซึ่งยังไม่มี Backend ชุดนี้

| Branch ที่ตรวจ | Commit |
| --- | --- |
| main | a7930125a24f4ed9e8bbe430ba558f3b8800690e |
| feature/meter-repair | 2959b54991346afb839a46589f8e076006dfe0f6 |
| integration/invoice-edit | 85deb31632e5b400ff5323e29c0583e64396fa5d |
| integration/move-out-backend | ebcecef109b51b39a4fbee2e4ecb5f0bc2ba4e7c |
| feature-invoice-payment-ui | 0d32eadc23a8cfbb99ea202965e5398e98d260c6 |
| feature-invoice-moveout-ui | 782619ca928b521a6677186fc96b5fbae44c7cb7 |
| feature-rental-ui | 88c55a21e573d98db11f68850683ed99bb4e3044 |

## กฎ Invoice/Billing ที่ใช้

- ไม่มี automatic invoice scheduler การออกบิลเริ่มจากคำสั่ง Admin รวมถึงการกด Move-out ซึ่งออกบิลปิดงวดใน transaction เดียวกัน
- ช่วงวันเป็น `[start, end)` ไม่รวมวันปลายช่วง วันที่ 1 เป็น standard boundary
- ค่าเช่า = rent rate × จำนวนวัน / จำนวนวันในเดือนของวันเริ่ม ปัด HalfUp เป็น 2 ตำแหน่ง
- หนึ่งบิลอยู่ภายในเดือนเดียว ปลายช่วงเป็นวันที่ 1 เดือนถัดไปได้
- น้ำ/ไฟ = exact end meter − exact start meter แล้วคูณ rate ไม่มีการเฉลี่ยน้ำไฟหรือใช้มิเตอร์ใกล้วันที่สุดแทน
- Preview และ Issue ต้องคำนวณใหม่ใน Backend ไม่เชื่อยอดจาก client ไม่มีบิลซ้อน รวมบิลที่ soft delete แล้ว
- Invoice Edit: Admin ที่ active และเปลี่ยนรหัสผ่านแล้ว, Payment ต้องเป็น UNPAID เริ่มต้น ไม่มี payment events หรือรายละเอียดชำระเก่า ต้องมีเหตุผล
- ตามคำตอบผู้ใช้: Rental ENDED ยังแก้ period ได้ภายในวันเข้า–วันย้ายออก ไม่ล็อก period หลังย้ายออก
- เปลี่ยน period จะคำนวณใหม่จากมิเตอร์ตรงวัน โดยใช้อัตรา snapshot เดิมและวันออกบิลเดิม เปลี่ยน due อย่างเดียวจะไม่อ่านมิเตอร์ใหม่
- Due ต้องไม่ก่อนวันออกบิล Invoice/Payment/Audit เปลี่ยนใน transaction เดียวกัน
- การแก้ period อาจเปิด billing gap ได้ การออกบิลโดยไม่ส่งวันที่จะกลับไปเติม gap แรก ส่วน Move-out เดิมตรวจและเติม gaps ทั้งหมด

`CalculateInvoice.php` และ `UpdateInvoice.php` มีแกนกฎที่ต้องการอยู่แล้ว จึงเพิ่ม regression tests ยืนยัน ENDED โดยไม่เปลี่ยน logic ให้ผิดจาก source เดิม

## อ่านโค้ดที่เปลี่ยนตามลำดับ

### 1. `app/Actions/Billing/NextBillingPeriod.php`

เริ่ม cursor ที่ move-in อ่าน invoices ตาม period_start ถ้าบิลเริ่มหลัง cursor จะพบ gap ถ้าบิลติดกันจะเลื่อน cursor ไป period_end
เลือกปลายช่วงที่ใกล้ที่สุดระหว่างวันที่ 1 เดือนถัดไป, จุดเริ่มบิลถัดไป, และ move-out
พบข้อมูลบิลผิดรูปแบบ/ทับกัน/soft delete จะคืน conflict เพื่อไม่ข้ามข้อมูลที่ต้องตรวจสอบ
ENDED ที่คิดครบถึง move-out แล้วคืน 409

### 2. `app/Actions/Billing/IssueInvoice.php`

เพิ่มการเรียก NextBillingPeriod เมื่อไม่ส่งทั้ง period_start และ period_end ภายใน transaction เดิม
ส่งเพียงหนึ่งวันยังเป็น validation error ส่งทั้งสองวันยังรองรับเหมือนเดิม
Calculator เดิมยังตรวจไม่ให้คิดช่วงที่ยังไม่จบหรือไม่มี exact meters

```json
POST /api/v1/invoices/preview
{"rentals_rt_id": 12}
```

Preview คืน period และยอดโดยไม่เขียนข้อมูล หลังผู้ใช้ตรวจ Preview สามารถส่ง period ที่ได้รับไป `POST /api/v1/invoices` เพื่อยืนยันช่วงเดียวกัน
Issue โดย Rental ID อย่างเดียวก็รองรับ แต่ Preview → Confirm ควรส่ง explicit boundaries เพื่อให้ผู้ใช้ยืนยันช่วงที่เห็น

### 3. `app/Http/Controllers/Api/V1/DashboardController.php`

เพิ่ม `GET /api/v1/dashboard?month=2026-09` เฉพาะ Admin ใช้ read transaction ให้ cards เห็น SQLite snapshot เดียวกัน
ค่าเงินเป็น string ทศนิยม 2 ตำแหน่ง รวมด้วย BigDecimal เพื่อไม่รวมเงินด้วย floating-point

| Response | ความหมาย |
| --- | --- |
| month | เดือนที่เลือก ถ้าไม่ส่งใช้เดือนปัจจุบัน Asia/Bangkok |
| as_of | วันปัจจุบัน Asia/Bangkok |
| rooms.total/vacant/occupied | ห้องปัจจุบันที่ไม่ soft delete |
| active_rentals | การเช่า ACTIVE ปัจจุบัน |
| invoices.count/amount | บิลที่ period_start อยู่ในเดือนที่เลือก |
| payments.received_amount | Payment PAID ที่ p_date อยู่ในเดือนที่เลือก ไม่ใช้เดือนรอบบิล |
| payments.outstanding_count/amount | ยอดค้างทั้งหมด ณ ปัจจุบัน UNPAID/PENDING/REJECTED |
| payments.overdue_count/amount | ยอดค้างที่ i_due ก่อนวันนี้ วันครบกำหนดยังไม่ถือว่าเกินกำหนด |
| payments.pending_count | Payment รอตรวจทั้งหมด ณ ปัจจุบัน |
| repairs | จำนวนงานซ่อมปัจจุบันแยกตาม rp_status |

Payments ต้องมี Invoice ที่ยังไม่ soft delete ถึงนับในภาพรวม ช่อง current/as_of ไม่ใช่ประวัติ occupancy ย้อนหลังตาม month
หน้า Dashboard ปัจจุบันยังเป็น placeholder งานนี้เพิ่ม Backend API ไม่ได้ทำ UI Dashboard

### 4. `app/Actions/Rentals/UpdateContract.php`

เพิ่ม `PATCH /api/v1/rentals/{rental}/contract` ด้วย `c_end` และ `reason`
ใช้ SqliteTransaction เดียวกับ Move-out โหลด actor/rental/contract ใหม่ทุก retry ตรวจสิทธิ์ Admin และ Rental ACTIVE/ไม่มี move-out
Contract ต้องเป็น ACTIVE หรือ EXPIRED; ENDED ไม่เปิดกลับด้วยการแก้วันสิ้นสุด
c_end ต้องส่งมา explicit เป็น null หรือ Y-m-d ที่หลัง c_start, reason ต้องไม่ว่าง/ไม่เกิน 500 ตัวอักษร และ no-op คืน 422
c_rent/c_deposit/c_start/c_status/c_number/rentals_rt_id ห้ามแก้ผ่าน endpoint นี้
อัปเดต c_status เป็น EXPIRED เมื่อ c_end ก่อนวันนี้ หรือ ACTIVE เมื่อ null/วันนี้/อนาคต ใช้ Asia/Bangkok
การแก้วันหมดอายุไม่ย้ายผู้เช่าออก ไม่เปลี่ยน room หรือ Invoice snapshot
Audit `contract_updated` เก็บก่อน/หลังและเหตุผล หาก Audit ล้มเหลว rollback Contract

```json
PATCH /api/v1/rentals/12/contract
{"c_end":"2027-01-01","reason":"ผู้เช่าขอต่อสัญญา"}
```

### 5. Controller/Routes และ tests

`RentalController.php`: เพิ่ม updateContract และ price_locked ใน GET contract โดยรวม invoices ที่ soft delete ในการตรวจประวัติ
GET เดิมยังใช้ RentalPolicy เพื่อให้ Tenant อ่านได้เฉพาะสัญญาตัวเอง
`routes/api-session.php`: เพิ่ม Dashboard GET และ Contract PATCH ภายใต้ session auth/password-change และ role:admin โดยยังใช้ CSRF middleware
`tests/Feature/UpdateInvoiceApiTest.php`: ใช้ fixture Billing/Payment/Rental เดิมทดสอบกฎ ENDED, next period/gaps, Dashboard, Contract permission/validation/stale model/rollback/CSRF

## Integration ที่ตรวจจาก branches ล่าสุด

| ส่วน | ผลตรวจ |
| --- | --- |
| Meter/Repair | อยู่ในฐาน feature/meter-repair รวม tenant ownership, history และ migrations ของมิเตอร์แล้ว regression ของ Meter/Repair ผ่าน |
| Payment UI | ส่ง amount/payment_date/method/expected_event_id ตรง Backend guard; review ใช้ event ID และ submit ใช้ multipart proof |
| Invoice UI | ส่ง explicit periods ตรง Preview/Issue เดิม ยังบังคับเลือกวันที่เอง ต้องปรับ UI หากจะใช้ next-period อัตโนมัติ |
| Invoice Edit | API และ tests รองรับ period correction หลัง ENDED โดยจำกัดวันย้ายออก; payment เริ่มดำเนินการแล้วแก้ไม่ได้ |
| Move-out UI | ส่ง rt_moveout/reason ตรง Backend และดึงมิเตอร์ห้องผ่าน endpoint เดิม |
| Contract UI | ส่ง c_end/reason ไป endpoint เดียวกับที่เพิ่ม งาน controller ใน branch ของ Big ยังมี TODO authorization จึงไม่แทนที่ Backend ที่ตรวจแล้วด้วยไฟล์นั้น |

การตรวจ UI branches เป็น source/payload review ยังไม่ได้ merge หรือทดสอบ browser end-to-end ของ UI branches ทั้งชุด
เมื่อรวมงานให้รักษา Backend actions/models/policies/migrations ที่ฐานนี้ และนำ UI เข้ามาโดย resolve routes/controllers อย่างเจาะจง หลีกเลี่ยงการแทนทั้งไฟล์จาก branch ที่ไม่มี type fixes/Meter/Repair integration

## ผลตรวจ

- PHP 8.4, dependencies ตาม composer.lock/package-lock.json, Frontend build ผ่าน
- Regression: 538 tests, 519 passed, 19 skipped, 2,418 assertions, ไม่มี failures
- 19 skips มาจาก `skipUnlessFortifyHas` สำหรับ Fortify features ที่ config ไม่เปิด ไม่ใช่ skips ที่เพิ่มในงานนี้
- PHPStan ผ่าน 0 errors ด้วย memory limit 1G (128M เดิมไม่พอ)
- Pint ผ่านหลังจัดรูปแบบเฉพาะไฟล์ที่เปลี่ยน
- Invoice Edit vs Payment concurrency: 4 cases ผ่าน
- Billing concurrency: same period และ overlapping periods 2 cases ผ่าน มี Invoice/Payment/Audit เพียงหนึ่งชุดสำหรับช่วงซ้ำ
- Move-out concurrency: 5 cases ผ่าน (Move-out ซ้ำ, Issue และ Edit ทั้งสองลำดับ)
- Concurrency ใช้ฐานข้อมูลชั่วคราว ไม่ใช้ฐานข้อมูลจริงของผู้ใช้

## ใช้ patch และงานที่ยังแยกอยู่

`backend-completion.patch` เป็น Git diff บนฐาน commit 2959b54 รวม source ใหม่, tests และเอกสารนี้ ไม่มี .env, vendor, node_modules หรือไฟล์ build
ใช้ใน checkout ที่มีฐาน Backend นี้ หลังตรวจงาน local ที่ยังไม่ commit ก่อน ไม่ควร apply ไป main โดยตรง

```text
git apply --check backend-completion.patch
git apply backend-completion.patch
```

ตรวจซ้ำด้วย `php artisan test`, `vendor/bin/phpstan analyse --memory-limit=1G` และ `vendor/bin/pint --test` ในสภาพแวดล้อม PHP 8.4 ที่ติดตั้ง dependencies/build assets แล้ว
ยังไม่ได้ push/สร้าง PR/merge/deploy ไม่มีการรอ teammate สำหรับ Backend ที่เพิ่ม
UI Dashboard, UI next-period และการรวม UI branches/ทดสอบ browser ของทีมยังแยกจากงาน Backend ชุดนี้
