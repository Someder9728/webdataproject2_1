# โมเดลรายการจดมิเตอร์ — 10 ตุลาคม 2026

เอกสารนี้แทนกฎใน meter-correction.md รุ่นแรก

## กฎล่าสุด

- สร้างการเช่าต้องมีค่ามิเตอร์ตรงวันย้ายเข้าเหมือนเดิม ไม่สร้างค่า 0 แทนค่าที่ไม่รู้
- Admin แก้ตัวเลขเริ่มและปลายช่วงได้ก่อนใช้ใน Invoice ไม่ล็อกเพียงเพราะ Rental ACTIVE
- วันเข้า/ออกของ Rental แก้ตัวเลขได้ แต่เปลี่ยนวันหรือยกเลิกไม่ได้ เพื่อรักษาข้อมูลขอบเขตการเช่า
- รายการที่ Invoice อ้างอิง รวม Invoice soft delete ยังแก้/ยกเลิกไม่ได้ งานนี้ไม่เปลี่ยนยอดบิลเดิม การแก้บิลใช้ workflow Invoice เดิม
- ทุกการแก้/ยกเลิกต้องมีเหตุผล ยืนยัน และส่ง latest_event_id ป้องกันเขียนทับข้อมูลที่มีคนแก้ระหว่างเปิดฟอร์ม
- ตัวเลขต้องอยู่ระหว่างรายการก่อน/หลังที่ใช้งานได้และทศนิยมไม่เกินสองตำแหน่ง

## ยกเลิกและทดแทน

DELETE endpoint เดิมเปลี่ยนเป็น soft delete แถวและเลขเดิมยังอยู่ มี Audit `meter_reading_cancelled` พร้อมเหตุผล ผู้ดำเนินการและเวลา

POST วันเดิมของรายการที่ยกเลิกผ่าน action นี้ จะบันทึกค่าทดแทนในแถวเดิม คืนสถานะใช้งานและเก็บค่าเดิมใน Audit `meter_reading_replaced` วิธีนี้รองรับ unique room/date โดยไม่เพิ่ม migration ไม่คืนรายการ legacy ที่ถูก soft delete ด้วยวิธีอื่นหรือถูก Invoice/Rental boundary อ้างอิง

รายการยกเลิกใช้สร้าง Rental/Invoice หรือคำนวณ usage ไม่ได้ ประวัติก่อนทดแทนคงอยู่ใน Audit

## API และ UI

- PATCH/DELETE `/api/v1/rooms/{room}/meters/{meter}` ใช้ reason, confirmed, expected_event_id เดิม; PATCH เพิ่ม m_date/m_water/m_elec
- GET endpoint รายการเดิมคืนเฉพาะรายการใช้งาน รักษา client เดิม
- เพิ่ม `include_cancelled=1` เพื่อดูประวัติรวมรายการยกเลิก มี status ACTIVE/CANCELLED และ cancelled_at
- หน้า `/meters` ดูแยกแต่ละห้อง แสดงรายการยกเลิก ไม่มีปุ่มเลือกต้น/ปลายหรือแก้ในรายการยกเลิก ปุ่มลบเปลี่ยนเป็นยกเลิกรายการ
- สิทธิ์ Admin, CSRF, transaction, Audit rollback และ validation ยังคงบังคับ

## Invoice

Manual Issue + Automatic Calculation ไม่มี scheduler วันที่ 1 เป็นขอบเขตมาตรฐาน รอบสั้นทำได้ ค่าเช่า prorate และค่าน้ำไฟใช้ exact boundary meters หากไม่มีรายการใช้งานตรงวันเริ่ม/สิ้นสุดต้องบันทึกก่อน ไม่ใช้รายการล่าสุดของวันอื่นแทน

## ผลตรวจและส่งต่อ

- Regression 578 tests: 559 passed, 19 skipped ตามการตั้งค่าเดิม, 2,577 assertions
- PHPStan 0 errors, รูปแบบโค้ด, JavaScript syntax และ Blade compilation ผ่าน
- ทดสอบแก้ค่าเริ่ม Rental ACTIVE, ยกเลิก/ประวัติ/ทดแทน, มิเตอร์ยกเลิกออกบิลไม่ได้, ทดแทนแล้วคิดบิลได้, Invoice locks, validation, สิทธิ์, CSRF, stale version และ rollback
- ยังต้องทดลองผ่าน browser จริง โดยเฉพาะยืนยัน/ยกเลิกและเปลี่ยนห้อง ไม่อ้างว่า E2E ผ่านแล้ว

ฐาน `feature/meter-correction` commit `ebf4b46d1444c198d9b4c74e5fe16bd2622a2d9a` สคริปต์สร้าง `feature/meter-model-v2` ตรวจและ commit/push เมื่อผ่าน จากนั้น review และรวมเข้า integration ที่ทีมใช้งาน

ยังไม่ได้แก้หรือ push ใน VS Code ผู้ใช้จนกว่าจะรันสคริปต์ ไม่มี migration หรือ reset ฐานข้อมูล
