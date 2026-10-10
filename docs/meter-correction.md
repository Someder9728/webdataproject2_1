# Meter correction — 10 October 2026

## กฎตามที่ผู้ใช้ตกลง

- Meter ต้นงวดที่ใช้เปิด Rental ACTIVE/ยังไม่ย้ายออก แก้หรือลบไม่ได้
- ปลายงวดที่ยังไม่ถูก Invoice ใช้ แก้เลขผิดได้ ต้องมีเหตุผลและยืนยัน
- Meter ที่ Invoice ใช้เป็นต้น/ปลาย (รวม Invoice soft delete) แก้/ลบไม่ได้ ไม่คำนวณบิลเดิมใหม่ในงานนี้
- ลบได้เฉพาะรายการที่ไม่ถูก Invoice ใช้และไม่ใช่วันเข้า/วันออกของ Rental รวม Rental soft delete
- การลบกรณีนี้เป็น hard delete เฉพาะรายการผิดที่ผ่าน reference guards เพื่อให้บันทึกใหม่วันเดิมได้ ไม่ติด unique room/date ของ soft delete; Audit เก็บเลข/วันที่เดิม ผู้ลบ เวลา และเหตุผลไว้
- แก้วันที่ได้เฉพาะรายการไม่ใช่ Rental boundary และไม่ชนวันที่ของ Meter อื่น
- เลขใหม่ต้องไม่ติดลบ/ไม่เกินฐานข้อมูล/ทศนิยมไม่เกิน 2 ตำแหน่ง และอยู่ระหว่าง readings ก่อนหน้า/ถัดไป
- ไม่เปลี่ยน Room status, Rental หรือ Invoice/Payment และไม่เพิ่ม migration

## API

PATCH /api/v1/rooms/{room}/meters/{meter}

```json
{"m_date":"2026-10-01","m_water":"123.00","m_elec":"456.00","reason":"แก้เลขที่กรอกผิด","confirmed":true,"expected_event_id":123}
```

DELETE endpoint เดียวกัน ส่ง reason/confirmed/expected_event_id เป็น JSON
expected_event_id ใช้ latest_event_id จาก Meter list/store/update response; legacy Meter ที่ไม่มี audit เป็น null แต่ต้องส่ง field มา
ถ้าข้อมูลเปลี่ยนระหว่างเปิดฟอร์มกับยืนยันจะคืน 409 ให้ reload; actor/room/meter/references ตรวจใหม่ภายใน SqliteTransaction ทุก retry
มี role:admin/session/password-change/CSRF เช่น API เดิม ทุก mutation เก็บ Audit ใน transaction เดียวกัน Audit ล้มเหลว rollback รวม hard delete
Meter response เพิ่ม can_edit/can_delete/date_locked/latest_event_id ให้ UI ใช้แสดงความสามารถ แต่ Backend ตรวจสิทธิ์/สถานะซ้ำเสมอ

## UI

หน้า /meters มีปุ่มแก้/ลบเฉพาะรายการที่ทำได้ ฟอร์มแก้ต้องกรอกเหตุผลและเห็น before→after ใน confirm; ลบมี reason prompt และ confirm ระบุห้อง/วันที่/เลขเดิม
บันทึกใหม่มี confirm ระบุห้อง/วันที่/เลข ปุ่มระหว่างบันทึกถูก disable การ cancel ไม่ส่ง mutation request
หลังแก้/ลบและเมื่อเจอ conflict เคลียร์ selection ของ usage/edit และโหลดรายการใหม่ ป้องกันค้างเลขเก่า
JS shared กับ Repair ยังคง logic Repair เดิม เพิ่ม cache version จาก filemtime เพื่อโหลดสคริปต์ใหม่หลังติดตั้ง

## ผลตรวจใน checkout แยก

- Regression: 558 passed / 19 skipped / 2,563 assertions (577 tests)
- Meter tests ใหม่ 13 cases รวม active opening/unbilled ending, invoice references, delete/recreate, expected version, permissions, neighbor bounds, validation, confirmation, CSRF, UI render และ Audit rollback
- PHPStan/format checks และ JS syntax ตรวจแยกก่อนส่ง installer
- ยังไม่ได้ทดสอบคลิกทุก flow ผ่าน browser จริง ยังไม่ได้เปลี่ยน VS Code หรือ push งานนี้จนกว่าผู้ใช้รัน installer

## ติดตั้ง/ส่งงาน

ฐาน integration/frame-admin-ui commit 65aea49bc241c34becb17a2e4130e12cc6a73247
installer สร้าง feature/meter-correction ตรวจ source ว่ายังเป็นฐานเดิม ลง patch ตรวจ tests/build แล้ว commit/push เมื่อผ่าน
ไม่ต้องใช้แก้บิลที่ออกไปแล้ว กรณีนั้นต้องตกลง workflow แก้ไขบัญชี/บิลแยกต่างหาก
