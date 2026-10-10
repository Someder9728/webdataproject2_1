# Frame integration handoff — 10 October 2026

## ฐานและขอบเขต

- Big/Backend/Timeline ฐาน: integration/big-ui / 92c4b78e341c430efba58fe0f555174a5120a7ca
- Frame source: feature/week3-admin-backend / 3862d18c6562662071ee72b6680551c9daf5d88e
- Branch ติดตั้งผลรวม: integration/frame-admin-ui
- งานรอบนี้รับ Dashboard, Room/Tenant/Account UI, layout/sidebar/search และหน้ารายการสัญญาจาก Frame
- คง Rental/Invoice/Payment/Move-out/Tenant pages/Timeline ของ Big และ API/Actions/policies/models/Auth/schema ของฐานกลาง
- ไม่เพิ่มตาราง/migration ไม่ merge เข้า main/develop และไม่ deploy

## การ resolve source

เดิมจำลอง merge พบ 17 conflicts Resolve ใน checkout แยกแล้ว:

1. คง schema/migrations/models/API/Actions และ tests ของฐานกลาง ไม่รับ migrations เก่าที่สร้าง tenants/users/rooms/audit_events ซ้ำ
2. คงหน้า /rentals ของ Big ย้ายหน้ารายการสัญญาแบบ mode ของ Frame เป็น contracts/index และ /contracts ไม่ทับ Rental UI
3. รวม routes ของ Frame เพิ่ม Room/Tenant resources และ Account POST forms โดยรักษา Big/My Rental/Invoice/Meter/Repair/Timeline routes
4. ฟอร์ม Room/Tenant แบบ server เดิมของ Frame เขียน Model ตรง เปลี่ยนเป็น SaveRoom/CreateTenant/UpdateTenant/DeleteUnusedRecord เพื่อรักษา Audit/permission/reference guards
5. สถานะห้องใน server forms เป็น read-only ข้อความไทย ใช้สถานะจริงจาก Backend สถานะเปลี่ยนผ่านรับเข้า/ย้ายออก ไม่เพิ่มสถานะ maintenance หรือเปิดให้แก้ occupancy เอง
6. Dashboard ใช้ Rental model นับ ACTIVE เพื่อไม่รวม soft-deleted rentals; ห้อง query VACANT/OCCUPIED, UI ไทยตามเดิม
7. เก็บ Fortify configuration/rate limiting เดิม เติมเฉพาะข้อความบัญชีระงับหลัง password ถูกต้อง รับ login UI Frame
8. ใช้ role:admin/tenant ของฐานกลาง แทน middleware aliases เก่าของ Frame, Account Controller โหลด Admin ล่าสุดก่อนทำรายการ
9. Sidebar เพิ่มลิงก์ Invoice/Meter/Repair และ My Contract ให้ใช้ routes เดิมของ Big; tenant user/dashboard redirect ไป /my/rentals
10. ไม่รับ Audit read controller รุ่นเก่าที่ไม่มีสิทธิ์; ใช้ Timeline API ที่รวมในฐานแล้ว

## ผลตรวจ

- Tests ใหม่ FrameIntegrationTest: 18 passed ครอบคลุมหน้า Admin/Big, Tenant denied, audited form writes, reference delete guards, occupancy soft delete, suspension login, stale suspended Admin และ CSRF
- Regression รวม: 545 passed / 19 skipped / 2,510 assertions (564 tests)
- PHPStan: 0 errors
- Pint: ผ่าน
- Frontend build: ผ่าน (23 modules)
- Blade view:cache / view:clear: ผ่าน
- 19 skipped เป็น Fortify features ที่ไม่เปิดเหมือนเดิม
- ทดสอบในฐานข้อมูล in-memory ของ checkout แยก ไม่แตะข้อมูล VS Code ของผู้ใช้
- HTTP/view rendering tests ไม่ใช่ browser end-to-end หรือการคลิก JavaScript ทุก flow ยังต้องตรวจหลังติดตั้ง

## การติดตั้งและ commit/push

สคริปต์ตรวจ source ทั้งสอง branch ว่ายังเป็น hashes ข้างต้นก่อนสร้าง integration/frame-admin-ui
ใช้ merge strategy ours เพื่อบันทึก Frame ancestry โดยไม่รับ schema เก่าเข้ามาอัตโนมัติ จากนั้นลง diff ของ source ที่เลือกและ resolve/test แล้วตามรายการนี้
นี่เป็นการรวม source แบบเลือกส่วนที่ใช้งาน ไม่ใช่รับทุกไฟล์ของ Frame รวมทั้ง schema เก่า
สคริปต์ลงเฉพาะ source/tests/doc ใน diff, install/build/test ก่อน commit และ push หากผ่านทั้งหมด หยุดเมื่อพบ error ไม่ force push ไม่ stash/reset งานผู้ใช้
ก่อนสคริปต์ผู้ใช้รัน ยังไม่ได้แก้ E:/Herd/webdataproject2_1 หรือ commit/push งาน integration นี้

## ทีมต้องลองหลังติดตั้ง

Dashboard aggregates และ list data, Room/Tenant create/edit/delete, Account validation/reset/suspend/login/session, Global Search และ layout Bootstrap รวมถึง Big Rental/Invoice/Payment/Move-out/Timeline และ Tenant pages
ถ้าพบปัญหา JavaScript/layout ให้ระบุหน้า/role/ขั้นตอนทำซ้ำ และแยก UI กับ API error
Dependency vulnerabilities ที่ npm เคยรายงานยังไม่ได้วิเคราะห์/แก้ในรอบนี้ ต้องตรวจ audit แยกก่อน deployment
