# TASK 01 และ TASK 02 การเช่าและความสัมพันธ์ข้อมูล

วันที่ 15 ตุลาคม 2569 อ้างอิง source local บน main หลัง 8afe0c7 พร้อมการแก้ไขชุดนี้

## กฎการเช่า

scheduled_movein_at คือวันนัดเข้าพัก ส่วน rt_movein คือวันที่เข้าอยู่จริงและเป็น NULL ระหว่าง SCHEDULED การสร้างวันปัจจุบันเป็น ACTIVE เมื่อมีมิเตอร์ตรงวันนี้ การสร้างอนาคตเป็น SCHEDULED และยังไม่ต้องมีมิเตอร์ วันหมดสัญญาต้องไม่ก่อนวันนัดเข้าพัก

Admin ยืนยันเข้าอยู่ตั้งแต่วันนัดเป็นต้นไป ห้องต้อง VACANT และไม่มี ACTIVE อยู่ ต้องมีมิเตอร์ของวันเข้าจริง หากเข้าอยู่ช้าให้เริ่ม Rental และ Contract จากวันยืนยันจริง หากวันหมดสัญญาผ่านไปแล้วให้ยกเลิกและสร้างใหม่

scheduled_moveout_at คือวันที่นัดย้ายออก ใช้เฉพาะ ACTIVE การกำหนด เลื่อน และยกเลิกต้องมีเหตุผล ไม่เปลี่ยนสถานะห้องหรือหยุดคิดบิล ถึงกำหนดแล้วแสดงจำนวนแจ้งเตือนบน Dashboard แต่ไม่มี scheduler เปลี่ยนสถานะหรือออกบิลเอง

ตรวจช่วง [start, end) ของทั้งห้องและผู้เช่า โดยใช้กำหนดย้ายออกช่วยตรวจการจองต่อ การเช่าที่ยังไม่กำหนดจบถือว่าปลายเปิด วันหมดสัญญาไม่แทนวันย้ายออก ถ้ามีคนจองต่อ จะเลื่อนหรือยกเลิกกำหนดย้ายออกให้ชนกันไม่ได้ การยืนยันเข้าอยู่ยังต้องรอคนเก่าย้ายออกจริง

ยกเลิกการจองใช้ CANCELLED เก็บ Rental และ Contract ไว้ การเปลี่ยนวันนัดเข้าอยู่ให้ยกเลิกแล้วสร้างใหม่เพื่อรักษาประวัติ การย้ายออกจริงใช้ MoveOutRental เดิม ตรวจมิเตอร์และบิล เติมช่วงที่ยังไม่ออกบิล แล้วล้างกำหนดย้ายออก เปลี่ยน Rental และ Contract เป็น ENDED และห้องเป็น VACANT ยอดค้างเดิมยังคงอยู่

Billing ปฏิเสธ SCHEDULED และ CANCELLED ใช้เฉพาะวันเข้าและออกจริง มิเตอร์ต้องตรง boundary บิลเก่ายังเป็น snapshot เดิม หน้า Meter ของผู้เช่าจะไม่เปิดประวัติห้องของคนก่อนให้รายการรอเข้าอยู่

## ความสัมพันธ์ Tenant และ User

คง users.tenants_t_id เป็น nullable FK และ UNIQUE ตามโครงสร้างเดิม Tenant มี hasOne User และ User belongsTo Tenant บัญชี Admin ไม่มี Tenant ได้ การสร้างผู้เช่าผ่านทั้ง Web และ API จะสร้างบัญชี role tenant พร้อมกันใน transaction เดียว บัญชีซ้ำหรือข้อมูลรหัสผ่านผิดทำให้ rollback ผู้เช่าและ Audit ทั้งหมด

หน้าเพิ่มผู้เช่ารับ account[u_username], account[password], account[password_confirmation] ผู้เช่าต้องเปลี่ยนรหัสครั้งแรก ห้ามรับ role หรือ ID จาก client และไม่ส่งรหัสผ่านลง Audit/response

เพื่อรองรับ API client เก่าที่ส่งเฉพาะข้อมูลผู้เช่า ระบบสร้างบัญชีชื่อ tenant_ ตามด้วยอักขระสุ่ม พร้อมรหัสสุ่มที่ไม่แสดงออกมา Admin ต้องตั้งรหัสผ่านใหม่ในหน้าจัดการบัญชีก่อนส่งให้ผู้เช่า API ส่งคืนเฉพาะ username และ must_change_password

Migration เติมบัญชีให้ผู้เช่าเดิมที่ยังไม่มีบัญชีโดยไม่แก้รหัสผ่านบัญชีเดิม การ rollback ไม่ลบบัญชีหรือ Audit ที่สร้างแล้ว

ความหมายของข้อบังคับต้องแยกกัน: FK บังคับว่าบัญชีที่อ้าง Tenant ต้องอ้างแถวที่มีจริง และ UNIQUE บังคับว่า Tenant มีบัญชีได้ไม่เกินหนึ่ง แต่การมี User อย่างน้อยหนึ่งต่อ Tenant บังคับใน workflow/transaction และตรวจข้อมูลย้อนหลัง ไม่ใช่ข้อบังคับที่ FK เดิมรับประกันเมื่อเขียน SQL ตรงโดยข้ามระบบ

## จุดแก้ไข

- migrations เพิ่ม scheduled_movein_at / scheduled_moveout_at และให้ rt_movein nullable; backfill วันนัดจากวันเข้าเดิม
- RentalAvailabilityService ตรวจช่วงห้องและผู้เช่าภายใน SqliteTransaction ซึ่ง retry ทั้งการตรวจและเขียนเมื่อ SQLite มีคำขอชนกัน
- CreateRental, ActivateRental, ScheduleMoveOut, CancelScheduledRental, MoveOutRental
- CalculateInvoice และ NextBillingPeriod กันออกบิลก่อนเข้าอยู่
- CreateTenant สร้างบัญชีผ่าน CreateTenantAccount เดิมแบบ atomic
- Controller/Blade รองรับหน้า SSR สำหรับสร้างการเช่าและจัดการกำหนดการ พร้อม API ใช้ Actions ชุดเดียวกัน
- Dashboard เพิ่มจำนวนรอเข้าอยู่ รอย้ายออก และจำนวนที่ถึงกำหนดแล้ว

## เส้นทางใหม่

Web: POST /rentals, POST /rentals/{rental}/activate, POST /rentals/{rental}/schedule-moveout, POST /rentals/{rental}/cancel

API: POST /api/v1/rentals/{rental}/activate; POST/PATCH /api/v1/rentals/{rental}/schedule-moveout; DELETE เส้นทางเดียวกันเพื่อล้างวันที่ โดยยังส่ง reason; POST /api/v1/rentals/{rental}/cancel

Admin เท่านั้นที่ทำรายการได้ ตรวจสถานะบัญชีล่าสุดใน Action และใช้ CSRF/session เดิม ไม่มีการเปิดสิทธิ์ให้ผู้เช่าแก้กำหนดการเอง

## การติดตั้งและตรวจสอบ

สำรอง SQLite ก่อน migrate รักษา conditional unique indexes สำหรับ ACTIVE ต่อห้อง/ผู้เช่าไว้ เพราะ SQLite table rebuild อาจทำ predicate ของ index หาย Migration down ปฏิเสธเมื่อมี rt_movein เป็น NULL เพื่อไม่ทำข้อมูลจองหาย

ทดสอบย้อนหลังทั้งระบบ รวมการจองซ้ำ เข้าอยู่ช้า ห้องยังมีผู้พัก มิเตอร์หาย การยกเลิกที่กระทบคนจองต่อ การย้ายออกจริงที่ยังมียอดค้าง และ rollback เมื่อ Audit ล้มเหลว ใช้ฐานข้อมูล SQLite แยกสำหรับ tests

ฐานข้อมูล local ได้รับอนุญาตให้อัปเดตแล้ว มี backup ก่อนเปลี่ยน พบ meter migrations เดิมค้างสองรายการและ apply พร้อมกัน จำนวน tenants/users/rentals/contracts/invoices/payments/meters/repairs ก่อนและหลังเท่ากัน ตรวจ PRAGMA integrity_check ผ่าน และ foreign_key_check ไม่พบปัญหา ผู้เช่าปัจจุบันไม่มีรายใดขาดบัญชี

## ขอบเขตที่ไม่ได้รวม

อัปเดตต่อแล้ว: รวมประวัติ Payment/Repair เข้า Audit ผ่าน migration 2026_10_15_120000 เพิ่ม confirmation และ date-aware meter autofill พร้อมตรวจ timestamp UTC/Asia/Bangkok อ่านขอบเขตและข้อจำกัดปัจจุบันใน docs/central-audit-handoff.md

ยังไม่ commit หรือ push การแก้ไขชุดนี้ และไม่แก้ CreateInitialAdmin ที่ผู้ใช้มีงานค้างอยู่
