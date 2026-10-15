# สรุปการรวมประวัติและอัปเดตโปรเจกต์

วันที่ 15 ตุลาคม 2569 ฐาน source ล่าสุด origin/main 8afe0c7 hotfix พร้อมงาน local ที่ยังไม่ commit

## การรวมเวอร์ชันล่าสุด

fetch origin แล้ว fast forward main จาก 9d3694e เป็น 8afe0c7 สำรองงาน local 64 ไฟล์ไว้ก่อนและคง recovery stash bc5a4046b94ec4f78cf768a70adeac7d524c59d6 มี conflict เฉพาะหน้าสร้างการเช่า ซึ่งรวมโดยรักษา SSR form วันเริ่มเช่าล่วงหน้า การยืนยัน และความหมายวันสิ้นสุดสัญญาที่ upstream แก้ไว้ งานเดิม CreateInitialAdmin ของผู้ใช้ยังครบ

คง hotfix การ trust proxy เฉพาะ loopback สำหรับ HTTPS ผ่าน Tunnel, Bootstrap/Icons ใน public/vendor, pagination แบบ Bootstrap, GET form เลือกห้องดูมิเตอร์ และชื่อผู้เช่าปัจจุบันในหน้าห้องพัก ไม่มี migration เพิ่มจาก hotfix นี้

## โครงสร้างปัจจุบัน

มี 19 ตารางจริง 163 คอลัมน์ 14 physical FK และสอง read-only views รวม 23 คอลัมน์ payment_events และ repair_histories ไม่มีแถวเก็บข้อมูลของตัวเองแล้ว ทั้งสองอ่าน audit_events ส่วน PaymentEvent/RepairHistory เป็น adapter สำหรับรูปแบบข้อมูลเดิม

เหตุการณ์ใหม่ของการชำระและซ่อมเขียน Audit เพียงหนึ่งแถว ใช้ entity_type/entity_id และ action เดิม Snapshot ของประวัติอยู่ใน new_values.history พร้อม kind, สถานะ, จำนวนเงิน, วันที่, วิธีชำระ, หลักฐาน หรือรายละเอียดซ่อมตามประเภท ยังคง old_values, actor_user_id, reason, created_at และการป้องกัน UPDATE/DELETE

รักษา legacy ID ใน history.legacy_id เพื่อให้ลิงก์หลักฐานและ expected_event_id เดิมยังใช้ได้ รายการใหม่ใช้ ae_id ที่ตั้ง sequence ไม่ชน ID เดิม เพิ่ม trigger ตรวจ entity/actor ของประวัติใหม่และป้องกัน physical delete ของ Payment/Repair ที่มีประวัติ ห้ามส่ง Audit ทั้งก้อนให้ผู้เช่า ต้องผ่าน endpoint เดิมที่ตรวจเจ้าของและซ่อน path หลักฐาน/ข้อมูล Admin

## การย้ายข้อมูล

สำรองก่อน migrate เปิด maintenance ระหว่างเปลี่ยน และเปิดระบบกลับแล้ว ย้ายประวัติชำระ 15 รายการกับซ่อม 25 รายการ ตรวจทุกฟิลด์ว่าเหมือนเดิม Audit 195 เป็น 197 แถว: เติม snapshot ใน Audit ที่จับคู่ได้ และเพิ่มเพียง 2 ประวัติที่ไม่มี Audit ตรงกัน ไม่ลบ Audit เก่าที่จับคู่ไม่ชัดเจน integrity_check และ foreign_key_check ผ่าน

การย้ายนี้ไม่ย้อนกลับอัตโนมัติ ต้องใช้ backup ก่อน migration คู่กับ source ก่อนเปลี่ยน และพิจารณาข้อมูลใหม่ที่เกิดหลัง backup ก่อน restore การรวม storage ไม่รับประกันว่าไฟล์ SQLite จะเล็กลงทันที เพราะใช้ JSON และมีหน้าว่างในไฟล์รอใช้ซ้ำ

## ผล recheck ข้อเสนอแนะ

- Tenant และ User สร้างพร้อมกันใน transaction เดียว Admin ไม่ต้องมี Tenant ตรวจข้อมูล local ไม่มี Tenant ที่ขาดบัญชี ข้อขั้นต่ำว่าต้องมี User บังคับด้วย workflow ไม่ใช่ FK เดิมเมื่อเขียน SQL ข้ามระบบ
- เช่าล่วงหน้าและกำหนดย้ายออกแยกวันนัดจากวันจริง มีการยืนยันเข้าอยู่ ยกเลิกจอง และตรวจช่วงชนกัน การย้ายออกจริงยังตรวจ Meter/Billing และไม่ล้างยอดค้าง
- เพิ่มยืนยันก่อนเปลี่ยนสถานะซ่อม ส่งแจ้งซ่อม สร้างผู้เช่า และสร้างการเช่า คง confirmation มิเตอร์และการชำระเดิมไว้ ใช้ browser confirm ไม่มี dependency modal ใหม่
- เติมมิเตอร์ล่าสุดที่ใช้งานและไม่เกินวันที่เลือก รองรับย้อนหลังและไม่มีมิเตอร์เดิม ค่าเติมเป็นตัวช่วยกรอก ผู้ดูแลต้องตรวจค่าที่อ่านจริง และ Backend ยังคงกันเลขลด/วันซ้ำ
- ยืนยันเวลาสร้าง Tenant, User และ Audit เป็น instant ปัจจุบันใน UTC แล้วแสดง Asia/Bangkok ไม่เลื่อน timestamp เก่า และไม่เลื่อน business dates ตาม timezone

## ผลตรวจหลังรวม Git ล่าสุด

composer test ผ่าน: 625 tests ผ่าน 606 ข้าม 19 รายการเดิม Pint และ PHPStan ผ่าน รวม TunnelLoginTest ใหม่สองกรณี npm ci และ npm run build สำเร็จ View cache ผ่าน

ทดสอบ concurrency ผ่าน: ซ่อม 1 กรณี ส่งหลักฐาน 2 กรณี รับ/ตรวจชำระ 6 กรณี แก้บิลชนชำระ 4 กรณี และย้ายออกชนออก/แก้บิล 5 กรณี ทุกกรณีใช้ฐานข้อมูลทดสอบแยก ตรวจ confirmation ด้วย simulated DOM ว่ากดยกเลิกไม่ส่ง request และกดยืนยันส่ง PATCH เพียงหนึ่งคำขอ

npm audit ระหว่างติดตั้งแจ้ง 6 รายการ (high 1, critical 5) ยังไม่ได้ปรับ dependency นอกเวอร์ชัน lockfile ของ Git ล่าสุด ไม่ใช้ npm audit fix --force อัตโนมัติ ส่วน build มีคำเตือน optional fontaine แต่ build สำเร็จ

## เอกสารส่งมอบ

Data Dictionary เป็นตารางเรียบ มีเพียงชื่อ object กับข้อมูลฟิลด์ แยก VIEW ชัดเจน ไม่มีบทอธิบายยาว ER มี full diagram 19 ตารางและ business diagram 10 ตาราง ชนิด MySQL ใน MWB เป็นการแสดงเทียบเคียง SQLite ไม่ใช้ Forward Engineer แทน Laravel migrations

ไฟล์ Word ตรวจ ZIP/XML และรายการฟิลด์ครบ แต่เครื่องนี้ไม่มี LibreOffice สำหรับเรนเดอร์หน้ากระดาษ จึงยังไม่ยืนยัน visual pagination ควรเปิดตรวจใน Word/Google Docs ก่อนพิมพ์

ยังไม่ commit หรือ push งานชุดใหม่ ไม่มีการเขียนรหัสผ่าน/ข้อมูลบัญชีจริงลงเอกสารหรือ source
