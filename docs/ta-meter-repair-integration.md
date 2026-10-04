# ส่งต่องานตา: Meter / Usage / Repair

ชุดแก้วันที่ 4 ตุลาคม 2569 อ้างอิงรายการงานในรายงานทีมวันที่ 30 กันยายน 2569 และตรวจโค้ดจริงบน GitHub

## ฐานของชุดแก้

- Repository: `Someder9728/webdataproject2_1`
- Base: `integration/move-out-backend` commit `ebcecef109b51b39a4fbee2e4ecb5f0bc2ba4e7c`
- ฐานนี้รวม Meter integration, Invoice edit และ Move-out backend อยู่แล้ว
- งานเดิมของตาอยู่ที่ `feature/meter-repair` commit `c5015c266688bfe24f3f101d775ca92d29a909a7`
- ใช้ชุดนี้ต่อจาก Backend กลาง ไม่ควรนำ migration มิเตอร์แบบมี `m_type` หรือ Invoice model hook ที่สร้าง Payment เป็น `PENDING` จาก branch เก่ามาทับ
- ชุดนี้ไม่มี migration ใหม่ และไม่เปลี่ยน schema ข้อมูลเดิม

## งานที่แก้

| รายการ | ผลลัพธ์ |
|---|---|
| Meter UI | หน้า `/meters` สำหรับ Admin ใช้ GET/POST `/api/v1/rooms/{room}/meters`, แสดงประวัติแบ่งหน้า เลือกห้อง วันที่ และเลขทศนิยมสองตำแหน่ง |
| Meter rules | ใช้ `RecordMeterReading` กลาง หนึ่งห้องหนึ่งวัน ตรวจเลขก่อนหน้าและถัดไป บันทึกก่อนมี Rental ได้ ไม่ส่ง `m_type` |
| Usage | คำนวณฝั่งเซิร์ฟเวอร์ด้วย BigDecimal คืนหน่วยเป็นข้อความทศนิยมสองตำแหน่ง ตรวจห้อง วันที่ และเลขย้อน ยืนยันผลตรงกับ Billing snapshot |
| Repair COMMON | ไม่มีห้อง แต่เก็บผู้แจ้งและผู้เช่าที่เกี่ยวข้องได้ ใช้ชื่อและรายละเอียดเดิมบอกจุดซ่อม |
| Ownership | Tenant แจ้งใหม่ได้เมื่อมี ACTIVE rental; ROOM ต้องเป็นห้องที่กำลังเช่า บังคับ tenant และ reporter จากบัญชีจริง |
| History | สถานะ REPORTED → IN_PROGRESS → COMPLETED เฉพาะ Admin; เก็บ Repair, History, Audit ใน transaction เดียว |
| คำขอชนกัน | PATCH ต้องส่ง `expected_status`; คำขอเก่าหรือซ้ำตอบ 409 ใช้ SQLite transaction/retry กลาง |
| Repair UI | หน้า `/repairs` แจ้งใหม่ กรองสถานะ แบ่งหน้า ดูประวัติ และเปลี่ยนสถานะสำหรับ Admin |
| การอ่าน | Admin เห็นทั้งหมด Tenant เห็นงานที่ตนแจ้งหรือผูกกับตน รวมประวัติหลังย้ายออก งานผู้อื่นตอบ 404 |
| ความปลอดภัยหน้าจอ | Session + CSRF, แสดงข้อผิดพลาด, ปิดปุ่มระหว่างส่ง, แสดงข้อความผู้ใช้ด้วย textContent |

## API ที่เพิ่ม

ทุก endpoint ใช้ session ที่เข้าสู่ระบบ บัญชี active และผ่านการเปลี่ยนรหัสผ่าน

| Method | Path | Input / Output |
|---|---|---|
| GET | `/api/v1/rooms/{room}/meter-usage` | Admin; `start_meter_id`, `end_meter_id`; คืน `data.water_usage`, `data.elec_usage` เป็น string |
| GET | `/api/v1/repairs` | `page`, `per_page` 1–100 ค่าเริ่มต้น 20, `rp_status` ถ้าต้องการกรอง; คืน `data`, `meta` |
| POST | `/api/v1/repairs` | `rp_name` สูงสุด 255, `rp_description` สูงสุด 10000, `rp_type` ROOM/COMMON, `rooms_r_id` เฉพาะ ROOM; Admin ส่ง `tenants_t_id` ได้ |
| GET | `/api/v1/repairs/{repair}` | คืนรายละเอียดพร้อม `histories` เรียงตามรหัสประวัติ |
| PATCH | `/api/v1/repairs/{repair}/status` | Admin; `expected_status` และ `rp_status`; ไม่อนุญาตข้ามขั้นหรือย้อนกลับ |

POST สำเร็จ 201; อ่าน/เปลี่ยนสถานะสำเร็จ 200; ไม่เข้าสู่ระบบ 401; ไม่มีสิทธิ์ 403; ไม่พบหรือไม่มีสิทธิ์อ่านรายการ 404; คำขอเก่า 409; CSRF ไม่ผ่าน 419; validation ไม่ผ่าน 422

Usage เป็นข้อมูลหน่วยสำหรับแสดงผล ยอดชำระให้ใช้ Invoice และ snapshot ของ Backend เสมอ

## ทดสอบซ้ำ

ต้องใช้ PHP 8.3+ พร้อม SQLite, Composer และส่วนประกอบตาม composer.lock; การทดสอบทั้งโครงการต้องมี GD ด้วย

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan test tests/Feature/RepairApiTest.php tests/Feature/MeterUsageApiTest.php tests/Feature/RepairSchemaTest.php tests/Feature/MeterApiTest.php tests/Feature/IssueInvoiceApiTest.php tests/Feature/CreateRentalApiTest.php tests/Feature/DatabaseUniquenessTest.php tests/Feature/Auth/MaintenanceCsrfTest.php
php tests/Manual/repair-concurrency.php
php tests/Manual/meter-concurrency.php
```

ทดสอบ concurrency ใช้ฐาน SQLite ชั่วคราวแยกจากข้อมูลใช้งานจริง การรัน migration กับฐานเดิมยังต้องปฏิบัติตามเงื่อนไข backfill ผู้แจ้งของ migration กลางที่มีอยู่ก่อนชุดนี้

## ขอบเขตการส่งมอบ

ผลตรวจบนเครื่องทดสอบ:

- ชุดทดสอบที่เกี่ยวข้อง 95 tests / 414 assertions ผ่านทั้งหมด
- ทดสอบสอง process: Repair ได้ 200/409 และมีประวัติ/Audit เพิ่มเพียงชุดเดียว; Meter ผ่านทั้งวันซ้ำ เลขย้อน และเลขถูกลำดับ
- ทดสอบผ่าน Chrome จริง: Admin บันทึกมิเตอร์/วันซ้ำ/คำนวณหน่วย, Tenant แจ้ง COMMON/ดูประวัติ, Admin เปลี่ยนครบลำดับ; หน้าจอมือถือไม่มีล้นทั้งหน้าและไม่มี JavaScript error
- Build หน้าเว็บผ่าน; PHPStan เฉพาะไฟล์หลักของชุดแก้ผ่าน 0 errors
- ชุดทดสอบรวม: 526 tests, ผ่าน 504, ข้าม 19, ไม่ผ่าน 3; ทดสอบซ้ำบน base commit เดิมแล้วยืนยันว่า 3 failures เหมือนเดิม ได้แก่ AuthenticationTest (ชื่อ field ล็อกอิน), PasswordConfirmationTest (ไม่มี route passkey.confirm-options), DashboardTest (เงื่อนไขเปลี่ยนรหัสผ่านทำให้ redirect)
- PHPStan ทั้งโครงการยังมีข้อผิดพลาดนอกส่วนงานนี้ จึงไม่ได้รับรองว่าการตรวจรวมทั้งโครงการผ่านทั้งหมด

ชุดแก้พร้อมตรวจรวมบน Backend กลาง ไม่ได้หมายความว่าได้ merge ทุก feature branch หรือ deploy ระบบจริงแล้ว การรวม layout ล่าสุดจากเฟมและหน้าจออื่นของ Big ต้องทำตาม branch ที่ทีมเลือกใช้งาน
