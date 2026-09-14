# Architecture และข้อกำหนดการออกแบบระบบจัดการหอพัก

ฉบับตัดสินใจสำหรับพัฒนา MVP | 14 กันยายน 2026

## 1. สถานะและการใช้อ้างอิง

เอกสารนี้แทน writing-block.md ฉบับเดิมในด้านขอบเขตและการออกแบบ โดยคงคำตัดสินเดิมของผู้ใช้ และตัดสินรายละเอียดที่ค้างตามคำสั่งให้จัดการได้เอง คำตัดสินใหม่คือข้อกำหนดสำหรับงานถัดไป ไม่ใช่การยืนยันว่าได้แก้โค้ด ฐานข้อมูล ER หรือ Figma แล้ว

ฐานหลักฐานของโค้ดคือ webdataproject2_1-feature-backend-auth_3.zip ซึ่งตรวจรายการแก้ Models/Migrations แล้ว แต่ยังไม่มีผลทดสอบระบบจริง เอกสารนี้ไม่ได้อ้างว่า Migration หรือ Authentication ผ่านการทดสอบแล้ว

ลำดับการอ้างอิง: คำสั่งผู้ใช้ที่ใหม่กว่า → เอกสารนี้ → โค้ดสำหรับตรวจสถานะการพัฒนา → ER/Figma/Proposal เดิม ความต่างจากโค้ดให้ถือเป็นงานปรับ ไม่ย้อนกลับไปใช้กฎเก่าโดยอัตโนมัติ

## 2. เป้าหมายและขอบเขต

เว็บแอปพลิเคชันบริหารหอพักหนึ่งแห่ง เชื่อมข้อมูลผู้เช่า ห้อง การเข้าพัก สัญญา มิเตอร์ ใบแจ้งหนี้ การชำระเงิน และการแจ้งซ่อม ผู้ดูแลจัดการข้อมูลรวม ส่วนผู้เช่าเข้าถึงเฉพาะข้อมูลของตนเอง

วงจรหลัก: Tenant/Room → Rental พร้อม Contract → Meter → Invoice พร้อม Payment → ตรวจหรือบันทึกการชำระ → Move-out พร้อมเก็บประวัติ งานซ่อมดำเนินคู่ขนานระหว่างการเข้าพักได้

รวม Dashboard, Search ภายในแต่ละ Module และ History ในหน้ารายละเอียด ไม่มี Global Search ไม่มีเมนู Contract แยก ไม่มี Payment Gateway, Chat, Real-time, ระบบหลายหอพัก หรือการเชื่อมระบบภายนอกซับซ้อนใน MVP

## 3. คำตัดสินทางสถาปัตยกรรม

### 3.1 เทคโนโลยีและการเชื่อมต่อ

- ใช้ Laravel monolith หนึ่งโปรเจกต์ และ SQLite สำหรับ MVP ทั้งพัฒนาและสาธิต ไม่วางแผนย้าย Oracle ในรุ่นนี้
- ใช้ Blade/Flux สำหรับ Layout และองค์ประกอบหน้าจอ ใช้ Livewire สำหรับหน้าบัญชีตาม Starter Kit
- หน้าธุรกิจ Tenant, Room, Rental/Contract, Meter, Invoice/Payment, Repair และ Dashboard ใช้ Blade พร้อม JavaScript เรียก JSON API บน origin เดียวกัน เพื่อคงขอบเขต API และแยกงานทีมชัดเจน
- ใช้ Laravel session authentication ร่วมกันกับหน้าจอและ API พร้อม CSRF สำหรับคำขอเปลี่ยนข้อมูล ไม่เพิ่ม JWT หรือระบบบัญชีอีกชุด
- Controller และ Livewire เป็นตัวรับคำขอ กฎธุรกิจอยู่ใน Application Actions/Services ที่ใช้ร่วมกัน ไม่เขียนกฎเดียวกันซ้ำในสองทางเข้า
- ใช้ Eloquent และ Migrations จัดการข้อมูล ไม่เพิ่ม microservices, repository abstraction หรือ event bus ใน MVP
- Figma เป็นแหล่งอ้างอิงการแสดงผล โค้ด React/TypeScript ใน Prototype ไม่ได้กลายเป็น frontend stack ของระบบโดยอัตโนมัติ

### 3.2 ชั้นความรับผิดชอบ

| ชั้น | หน้าที่ |
|---|---|
| UI | แสดงรายการ/ฟอร์ม/สถานะ โหลดข้อมูล และแสดงข้อผิดพลาด |
| Controller / Livewire | รับ input ตรวจ authentication เรียก validation/policy และ action |
| Policy / Validation | ตรวจสิทธิ์ ownership ชนิดข้อมูล และเงื่อนไขก่อนดำเนินการ |
| Application Actions | คำนวณและเปลี่ยนสถานะ บันทึกหลายตารางใน transaction เดียว |
| Models / Database | Relationships, PK/FK, UNIQUE, CHECK, Soft Delete และข้อมูลประวัติ |
| Private file storage | เก็บหลักฐานชำระเงิน เปิดผ่าน Backend ที่ตรวจสิทธิ์ |

## 4. บัญชีผู้ใช้และสิทธิ์

### 4.1 วงจรบัญชี

- Admin สร้าง Tenant และสร้างบัญชีให้จากหน้า Tenant ไม่เปิดสมัครสมาชิกสาธารณะ
- บัญชี tenant ต้องมี tenants_t_id ที่ไม่ว่างและไม่ซ้ำ บัญชี admin ให้ tenants_t_id เป็น NULL
- Login ด้วย u_username/u_password พร้อม Remember Me Username เป็นอักษรอังกฤษ ตัวเลข จุด ขีดกลาง หรือ underscore ความยาว 3–45 ตัว แปลง lowercase ก่อนตรวจความไม่ซ้ำ
- รหัสผ่านอย่างน้อย 12 ตัว เก็บ hash เท่านั้น Admin สร้างหรือรีเซ็ตรหัสชั่วคราวและส่งมอบนอกระบบ ผู้ใช้ต้องเปลี่ยนในการเข้าใช้งานครั้งแรก/หลังรีเซ็ต ไม่เปิดดูรหัสผ่านเดิม
- เพิ่ม must_change_password และ is_active ใน users การระงับบัญชีหรือรีเซ็ตต้องยกเลิก session และ Remember Me เดิม
- เปิด Login, Logout, Remember Me และเปลี่ยนรหัสผ่าน ปิด public registration, email verification, email password reset, 2FA และ Passkeys สำหรับ MVP โครงสร้าง built-in ที่ยังมีอยู่เก็บไว้ได้ แต่ต้องไม่มีเมนูหรือ endpoint ใช้งานที่เปิดค้าง
- ย้ายออกแล้วบัญชียังเข้าอ่านประวัติและชำระยอดค้างของตนเองได้ แต่สร้างงานซ่อมใหม่ไม่ได้ Admin ระงับบัญชีได้โดยไม่ลบประวัติ
- สร้าง Admin เริ่มต้นผ่านขั้นตอนตั้งค่าระบบ ไม่เปิดฟอร์มให้สมัครหรือเปลี่ยนตัวเองเป็น Admin

### 4.2 Permission Matrix

| การกระทำ | Admin | Tenant ที่กำลังพัก | Tenant ย้ายออก |
|---|---|---|---|
| Dashboard | ทั้งหอพัก | ตนเอง | ประวัติ/ยอดค้างตนเอง |
| Tenant/Room CRUD | ได้ตามข้อจำกัดการลบ | อ่านข้อมูลตนเอง/ห้องตนเอง | อ่านข้อมูลตนเองและประวัติ |
| Rental/Contract | สร้างและจัดการ | อ่านของตนเอง | อ่านของตนเอง |
| Meter | บันทึก/ดูทั้งหมด | ดูข้อมูลการคิดบิลของตนเอง | ดูข้อมูลการคิดบิลย้อนหลังของตนเอง |
| Invoice | ออก/แก้ตามกฎ | อ่านของตนเอง | อ่านของตนเอง |
| ส่งหลักฐาน Payment | ไม่สวมบทเป็น Tenant | ของตนเอง | ยอดค้างของตนเอง |
| อนุมัติ/ปฏิเสธ/Walk-in | ได้ | ไม่ได้ | ไม่ได้ |
| Repair ใหม่ | ได้ทั้ง ROOM/COMMON | ของตนเองทั้งสองประเภท | ไม่ได้ |
| Repair status | เปลี่ยนได้ | อ่านรายการตนเอง | อ่านรายการตนเอง |
| แก้ข้อมูลส่วนตัว | Admin แก้ข้อมูล Tenant | เปลี่ยนรหัสตนเอง; ข้อมูลผู้เช่าให้ Admin แก้ | เช่นเดียวกัน |

Backend ตรวจทุก request ไม่รับ tenant_id/role จาก client มาเป็นหลักฐานสิทธิ์ การดูใบแจ้งหนี้และหลักฐานอ้างเจ้าของผ่าน Invoice → Rental → Tenant ผู้เช่าใหม่ของห้องเดียวกันไม่เห็นบิลของผู้เช่าเดิม

## 5. Tenant / Room / Rental / Contract

### 5.1 ข้อมูลหลักและการค้นหา

Tenant เก็บชื่อ นามสกุล โทรศัพท์ อีเมลและที่อยู่ ค้นหาจาก ID ชื่อและโทรศัพท์ Room เก็บเลขห้อง ชั้น ประเภท ราคาค่าเช่าตั้งต้นและสถานะ เลขห้องไม่ซ้ำทั้งระบบ ค้นหาเลขห้องและกรองชั้น/สถานะได้

### 5.2 การเช่า

- หนึ่ง Tenant มี ACTIVE Rental ได้หนึ่งรายการ และหนึ่ง Room มี ACTIVE Rental ได้หนึ่งรายการ
- MVP ไม่รองรับการจองล่วงหน้า วันเข้าใหม่เป็นวันปัจจุบัน เขตเวลา Asia/Bangkok ข้อมูลประวัติสำหรับสาธิตให้เตรียมผ่านข้อมูลตั้งต้นแยกจากหน้าทำรายการจริง
- สร้าง Rental และ Contract พร้อมกันในฟอร์มเดียว บันทึกทั้งหมดพร้อมเปลี่ยน Room เป็น OCCUPIED ใน transaction เดียว
- ก่อนบันทึกต้องเลือก Tenant/Room ที่ใช้งานอยู่ ไม่มี ACTIVE Rental ซ้ำ และมีเลขมิเตอร์ตั้งต้น ณ วันเข้า
- Auto Fill แสดงข้อมูลอ้างอิงให้ผู้ใช้ตรวจ แต่ Backend โหลดข้อมูลจริงซ้ำก่อนบันทึก ราคาจาก Room ใช้เติมค่าเริ่มต้น Contract เท่านั้น
- Move-out ใช้วันปัจจุบัน ต้องหลังวันเข้า มีมิเตอร์ปิดรอบ และออกบิลสุดท้ายได้ครบ จึงสิ้นสุด Rental และคืน Room เป็น VACANT ใน transaction เดียว ย้ายออกได้แม้มีหนี้ค้าง
- เปลี่ยนห้องใช้การปิด Rental เดิมและเปิด Rental/Contract ใหม่ในวันเดียวกัน ไม่แก้ rooms_r_id ของ Rental เดิม

### 5.3 ช่วงวันและสัญญา

- ช่วงคิดค่าเช่าใช้ [วันเข้า, วันออก) คือคิดวันเข้าแต่ไม่คิดวันออก ช่วงรอบบิลก็ใช้ปลายช่วงแบบไม่รวมเช่นเดียวกัน จึงไม่คิดซ้ำเมื่อเปลี่ยนห้องวันเดียวกัน
- c_start ตรงวันเข้า c_end คือวันสุดท้ายตามสัญญาที่รวมวันนั้นได้; อนุญาต NULL สำหรับไม่กำหนดวันสิ้นสุด
- Contract 1:1 ต่อ Rental เลขสัญญาสร้างจาก rt_id ในรูป CNT-000001 และไม่ซ้ำ
- สัญญาหมดอายุไม่ทำให้ผู้เช่าย้ายออกอัตโนมัติ Rental ยังคง ACTIVE และออกบิลต่อโดยใช้ c_rent จนบันทึก Move-out
- ต่อระยะสัญญาให้แก้ c_end ของ Contract เดิมพร้อมบันทึกเหตุผลและประวัติ ไม่สร้าง Contract ตัวที่สอง ราคาของสัญญาล็อกหลังมี Invoice ใบแรก การเปลี่ยนราคาในระหว่างการเช่าไม่อยู่ใน MVP
- เงินประกันเป็นข้อมูลสัญญาเท่านั้น MVP ไม่ทำบัญชีรับ/คืน/หักเงินประกันและไม่นำมาหัก Invoice อัตโนมัติ

## 6. Meter และ Billing

### 6.1 การอ่านมิเตอร์

- meters เก็บ m_water และ m_elec เป็นเลขสะสมใน record เดียว ไม่เพิ่มคอลัมน์ Previous
- หนึ่งห้องมี record ต่อวันที่ได้หนึ่งรายการ UNIQUE(rooms_r_id, m_date)
- ค่าตั้งต้นเป็นค่าที่อ่านจริงวันที่เริ่มใช้ระบบหรือวันเข้า ไม่ใช้ศูนย์แทนข้อมูลที่ไม่มี record ตั้งต้นยังไม่ก่อให้เกิด Usage
- บันทึกวันที่ 1 ของเดือนเป็นจุดแบ่งรอบ และอ่านเพิ่มในวันเข้า/วันออก ถ้าวันเดียวกันมี record แล้วใช้ record เดียวกัน
- Usage ของใบแจ้งหนี้ = ค่าที่ปลายช่วงลบค่าที่ต้นช่วง ทั้งคู่ต้องเป็น record ของห้องเดียวกันและตรงขอบช่วง ห้ามเดาค่าหรือเฉลี่ยเมื่อข้อมูลไม่ครบ
- ถ้ามีจุดอ่านระหว่างเดือน ใช้จุดอ่านขอบช่วงของ Invoice ไม่ใช่ record ใดก็ได้ที่อยู่ก่อนล่าสุด
- ค่าต้องไม่ติดลบหรือย้อนลดลง เทียบทั้ง record ก่อนและหลังเมื่อตรวจรายการย้อนหลัง; record ที่ใช้ใน Invoice แล้วห้ามแก้/ลบ
- การเปลี่ยนมิเตอร์หรือเลขวนกลับไม่รองรับการคำนวณอัตโนมัติใน MVP ให้ระบบปฏิเสธเลขลดลงและแจ้งข้อจำกัด ไม่สร้างยอดประมาณหรือแก้ประวัติเพื่อให้ผ่าน

### 6.2 กฎใบแจ้งหนี้

- รอบเดือนปกติ: วันที่ 1 ของเดือนถึงวันที่ 1 เดือนถัดไปแบบไม่รวมปลายช่วง ออกบิลย้อนหลังหลังอ่านมิเตอร์ปลายช่วง วันครบกำหนด = วันออกใบแจ้งหนี้ + 7 วันปฏิทิน
- วันออกใบแจ้งหนี้เป็นวันปัจจุบัน และต้องไม่ก่อนปลายช่วงคิดเงิน
- ค่าเช่าใช้ c_rent จาก Contract; เต็มเดือนคิดเต็มราคา บางเดือนคิด c_rent × จำนวนวันที่พักในช่วง / จำนวนวันของเดือนนั้น
- ค่าน้ำ = water_usage × water_rate; ค่าไฟ = elec_usage × elec_rate ปัด HALF_UP สองตำแหน่งต่อองค์ประกอบ แล้วนำยอดที่ปัดแล้วมารวม
- อัตราน้ำ/ไฟเป็นค่าตั้งค่าที่ผู้ดูแลติดตั้งระบบต้องกรอกจากข้อมูลหอพักจริงใน config/dormitory.php ไม่ตั้งตัวเลขสมมติเป็นอัตราจริง หากยังไม่กรอกให้ออกบิลไม่ได้ ไม่สร้างหน้า Settings หรือตารางอัตราใหม่ใน MVP
- เมื่อออกบิลให้ snapshot อัตรา Usage และค่าที่ใช้คำนวณลง Invoice การเปลี่ยนค่าตั้งค่าไม่มีผลกับบิลเก่า
- เปิดหน้าตรวจยอดก่อนกดออก Invoice; คำนวณซ้ำฝั่ง Backend ไม่เชื่อยอดจาก client
- ห้ามช่วง Invoice ของ Rental เดียวกันทับซ้อนกัน และห้ามออกซ้ำช่วงเดิม หากช่วงข้ามเดือนให้แบ่งเป็นรายเดือนก่อนคิดค่าเช่า
- Move-out ออกเฉพาะช่วงที่ยังไม่เคยออกบิลตั้งแต่วันเข้าหรือปลายช่วง Invoice ล่าสุดถึงวันออก ถ้าถึงขอบเดือนให้ใช้บิลเดือนนั้นเป็นบิลสุดท้าย ไม่สร้างช่วงศูนย์วันหรือบิลซ้ำ; ถ้าหลายเดือนยังไม่ได้ออกต้องมี record ที่ขอบเดือนครบก่อนปิดรายการ
- ออก Invoice พร้อม Payment UNPAID ใน transaction เดียว p_amount เริ่มจากยอดเต็มของ Invoice, p_date/p_type/p_proof เป็น NULL
- แก้รอบบิล/ยอดได้เฉพาะยัง UNPAID และยังไม่มีประวัติการส่งหลักฐาน ต้องแสดงยอดตรวจใหม่และปรับ p_amount พร้อมกัน เมื่อมีการส่ง/ปฏิเสธ/ชำระแล้วห้ามแก้ยอดใน MVP
- ไม่รองรับแบ่งจ่าย จ่ายเกิน คืนเงิน ค่าปรับ หรือลดหนี้ใน MVP

### 6.3 ตัวอย่างเกณฑ์คำนวณ (ข้อมูลทดสอบ ไม่ใช่อัตราหอพักจริง)

เดือน 30 วัน ค่าเช่า 3,000 บาท เข้าวันที่ 16 ออกวันที่ 1 เดือนถัดไป พัก 15 วัน ค่าเช่า 1,500 บาท ถ้าน้ำใช้ 8 หน่วย อัตราทดสอบ 18 บาท = 144 บาท ไฟใช้ 50 หน่วย อัตราทดสอบ 7 บาท = 350 บาท รวม 1,994 บาท

## 7. Payment และหลักฐาน

### 7.1 ตารางเปลี่ยนสถานะ

| จาก | เหตุการณ์/ผู้ทำ | ไป | เงื่อนไข |
|---|---|---|---|
| ไม่มี | ออก Invoice / Admin | UNPAID | สร้าง Payment หนึ่งรายการพร้อม Invoice |
| UNPAID หรือ REJECTED | ส่งหลักฐาน / เจ้าของบิล | PENDING | ยอดเต็ม วันชำระ และไฟล์ถูกต้อง |
| PENDING | อนุมัติ / Admin | PAID | ตรวจข้อมูลและหลักฐานแล้ว |
| PENDING | ปฏิเสธ / Admin | REJECTED | ต้องมีเหตุผล |
| UNPAID หรือ REJECTED | Walk-in / Admin | PAID | ระบุยอดเต็ม วันรับเงิน และวิธีรับเงิน |

- PENDING ส่งซ้ำไม่ได้ หากเปลี่ยนเป็น Walk-in ต้องปฏิเสธรายการรอตรวจพร้อมเหตุผลก่อน แล้วบันทึก Walk-in
- PAID เป็นสถานะปลายทางใน MVP ห้ามย้อนสถานะ แก้ยอด หรือลบผ่านหน้าจอ หากพบข้อผิดพลาดหลังอนุมัติให้รายงานเพื่อแก้ข้อมูลอย่างควบคุม นอก Flow ปกติ
- p_type ใช้ TRANSFER หรือ CASH การส่งหลักฐานของ Tenant เป็น TRANSFER; Walk-in เลือก CASH/TRANSFER ได้และไม่บังคับไฟล์หลักฐาน แต่บังคับหมายเหตุการรับชำระ
- วันชำระต้องไม่เป็นอนาคตและไม่ก่อนวันออก Invoice p_amount ต้องเท่ากับ i_total

### 7.2 ประวัติการส่งและตรวจ

คง payments หนึ่งรายการต่อ Invoice และเพิ่ม payment_events เป็นตารางสนับสนุนประวัติแบบ append-only หนึ่ง Payment มีหลาย events จึงไม่เปลี่ยนความสัมพันธ์ Invoice–Payment 1:1

ทุกการส่ง ปฏิเสธ อนุมัติ และ Walk-in เก็บผู้กระทำ เวลา สถานะก่อน/หลัง จำนวนเงิน วันชำระ วิธีชำระ หลักฐาน เหตุผลหรือหมายเหตุ การส่งใหม่ปรับ Payment ให้สะท้อนข้อมูลล่าสุดและล้างเหตุผลปฏิเสธปัจจุบัน แต่เก็บเหตุผล/ไฟล์เดิมใน event เดิมเสมอ

### 7.3 ไฟล์

- รับ JPEG, PNG หรือ PDF ไม่เกิน 5 MiB ตรวจชนิดจริงพร้อมนามสกุล ไม่รับ SVG/HTML/ไฟล์ executable
- ตั้งชื่อจัดเก็บสุ่มบน private disk นอก public web root ไม่ใช้ชื่อจากผู้ใช้เป็น path
- เปิดไฟล์ผ่าน endpoint ที่ตรวจ Admin หรือเจ้าของ Invoice ทุกครั้ง PDF ส่งเป็น download ไม่มี public URL ถาวร
- อัปโหลดไว้ชั่วคราวก่อนบันทึก transaction หากบันทึกไม่สำเร็จให้ล้างไฟล์ชั่วคราว; ไฟล์ที่มี event อ้างอิงแล้วไม่เขียนทับ
- เก็บหลักฐานตามอายุข้อมูลโครงการ ไม่มีระบบล้างถาวรอัตโนมัติใน MVP

## 8. Repair และ History

- ROOM ต้องมี rooms_r_id; COMMON ต้องเป็น NULL ใช้ rp_name/rp_description บอกปัญหาและตำแหน่ง ไม่เพิ่ม location
- Tenant แจ้งได้เมื่อมี ACTIVE Rental เท่านั้น ROOM ผูกห้องปัจจุบันจาก Backend; COMMON ผูก Tenant ผู้แจ้งแต่ไม่ผูกห้อง
- Admin แจ้งเองได้โดยไม่จำเป็นต้องมี Tenant เพิ่ม reported_by_user_id เป็น FK users.u_id และทำ tenants_t_id nullable; ห้ามสร้าง Tenant ปลอมแทน Admin
- Admin แจ้งแทน Tenant ที่พักอยู่ให้เลือก Tenant จริงได้ เก็บ Tenant ผู้เกี่ยวข้องและ User ที่ทำรายการแยกกัน Tenant เห็นรายการที่ tenants_t_id เป็นของตนเองเท่านั้น
- สถานะใช้ REPORTED → IN_PROGRESS → COMPLETED; ไม่ย้อนขั้นและไม่เปิดงานเดิมซ้ำใน MVP ถ้ามีปัญหาอีกให้สร้างรายการใหม่
- สร้างงานพร้อม History แรก และเปลี่ยนสถานะพร้อม History ทุกครั้งใน transaction เดียว History เพิ่ม changed_by_user_id สำหรับระบุผู้กระทำ
- แก้หัวข้อ/รายละเอียดได้เฉพาะ REPORTED โดย Admin หรือผู้แจ้งที่มีสิทธิ์ บันทึกการแก้ใน History ด้วย ตั้งแต่ IN_PROGRESS ให้เพิ่มคำอธิบายผ่าน History ไม่แก้เนื้อหาเดิม
- การย้ายออกไม่เปลี่ยน Tenant หรือ Room ที่ผูกกับงานเดิม ผู้เช่าดูงานเก่าของตนเองได้

## 9. Dashboard / Search / หน้าจอ

### 9.1 นิยามตัวเลข

- ห้องทั้งหมด = Room ที่ไม่ถูกลบ; ห้องมีผู้เช่า = ห้องที่มี ACTIVE Rental; ห้องว่าง = ห้องทั้งหมดลบห้องมีผู้เช่า
- ผู้เช่าปัจจุบัน = จำนวน Tenant ไม่ซ้ำที่มี ACTIVE Rental
- ยอดยังไม่ชำระ = ผลรวม Invoice ที่ Payment ไม่ใช่ PAID รวม PENDING และ REJECTED
- ยอดค้างเกินกำหนด = ยอดยังไม่ชำระที่วันปัจจุบันมากกว่า i_due วันครบกำหนดยังไม่ถือว่าเกินกำหนด
- KPI ที่ชื่อยอดค้างชำระให้ใช้ยอดเกินกำหนด ส่วนยอดยังไม่ชำระแสดงแยกและใช้ชื่อตรงความหมาย
- Overdue เป็น badge ที่คำนวณ ไม่เพิ่มสถานะใน Invoice หรือ Payment; PENDING อาจมี badge เกินกำหนดพร้อมกันได้
- งานซ่อมค้าง = REPORTED + IN_PROGRESS; Tenant Dashboard ใช้ชุดคำนวณเดียวกันแต่จำกัดเจ้าของข้อมูล

### 9.2 Navigation

Admin: Dashboard, ผู้เช่า, ห้องพัก, การเช่า, ค่าน้ำ–ค่าไฟ, ใบแจ้งหนี้/การชำระ, แจ้งซ่อม และบัญชีตนเอง Tenant: Dashboard, การเช่าของฉัน (สัญญา/ประวัติ), ใบแจ้งหนี้ของฉัน, แจ้งซ่อมของฉัน และบัญชีตนเอง

Contract เปิดจาก Rental Detail เท่านั้น History อยู่ใน Detail ที่เกี่ยวข้อง ไม่เพิ่ม Global Search/History menu หน้ารายการต้องมี loading, empty, validation error, permission error และ success feedback พร้อมป้องกันกดส่งซ้ำ

Search ตาม Module: Tenant ID/ชื่อ/โทร, Room เลขห้อง/ชั้น/สถานะ, Rental Tenant/Room, Invoice Tenant/Room/เดือนรอบบิล, Repair Room/ผู้แจ้ง/หัวข้อ ใช้ pagination เริ่ม 20 รายการ สูงสุด 100 เรียงวันที่ล่าสุดแล้ว PK ล่าสุด; Room เรียงชั้น/เลขห้อง

## 10. ข้อตกลง API และ Validation

API อยู่ใต้ /api/v1 แต่ใช้ middleware session/auth/CSRF บน origin เดียวกัน ทุก endpoint ต้องตรวจ policy ฝั่ง server

| กลุ่ม | Endpoint หลัก |
|---|---|
| Dashboard | GET /dashboard |
| Tenant/Room | GET/POST /tenants, /rooms; GET/PATCH/DELETE /tenants/{id}, /rooms/{id} |
| Rental | GET/POST /rentals; GET /rentals/{id}; POST /rentals/{id}/move-out |
| Contract | GET/PATCH /rentals/{id}/contract |
| Meter | GET/POST /meters; PATCH/DELETE /meters/{id} ตามกฎยังไม่ใช้ในบิล |
| Invoice | GET/POST /invoices; GET/PATCH /invoices/{id} ตามกฎ UNPAID |
| Payment | GET /invoices/{id}/payment; POST /invoices/{id}/payment/submit, /approve, /reject, /walk-in |
| หลักฐาน | GET /payment-events/{id}/proof |
| Repair | GET/POST /repairs; GET/PATCH /repairs/{id}; POST /repairs/{id}/status; GET /repairs/{id}/histories |

ชื่อฟิลด์ payload ใช้ชื่อคอลัมน์ตาม Data Dictionary ยกเว้นฟอร์มประกอบมี object rental/contract และคำสั่ง Payment มี amount, payment_date, method, proof, reason/note ซึ่ง Controller map อย่างชัดเจน ไม่ให้ client ส่ง p_status หรือ actor ID เอง Endpoint ย่อยของ Payment เช่น /approve หมายถึง /invoices/{id}/payment/approve; submit รับ multipart/form-data ส่วนคำสั่งอื่นรับ JSON

ผลสำเร็จ: {data, message}; รายการแบ่งหน้าเพิ่ม meta: {page, per_page, total} และ links ข้อผิดพลาด: {message, errors, code} ใช้ 401 ยังไม่ login, 403 บทบาททำไม่ได้, 404 ไม่พบหรือเป็นข้อมูลคนอื่น, 409 สถานะเปลี่ยน/ข้อมูลซ้ำ, 422 validation, 500 ปัญหาภายในโดยไม่เผย stack trace

เงินใน JSON ส่ง decimal string สองตำแหน่ง วันที่ YYYY-MM-DD เวลา ISO 8601 เก็บ timestamp UTC และแสดง Asia/Bangkok; business date คิดตาม Asia/Bangkok

Validation กลาง: trim ข้อความ; ตรวจ enum allowlist; ชื่อ/หัวข้อไม่เกิน 255; description/address ไม่เกิน 2,000 และปรับคอลัมน์ description เป็น text; โทรเป็น string ตัวเลข 10 หลัก; email ไม่บังคับแต่ต้องถูกแบบเมื่อกรอก; จำนวนเงินและมิเตอร์ไม่ติดลบและไม่เกิน precision ของคอลัมน์; FK ต้องมีอยู่และผ่านเงื่อนไขสถานะ/สิทธิ์ ไม่ mass assign role, ownership หรือยอดคำนวณจาก request

## 11. โครงสร้างข้อมูลที่ต้องปรับตามคำตัดสินใหม่

ตารางธุรกิจหลักเดิมยังมี 10 ตาราง: users(u_id), tenants(t_id), rooms(r_id), rentals(rt_id), contracts(c_id), meters(m_id), invoices(i_id), payments(p_id), repairs(rp_id), repair_histories(rph_id) ใช้ numeric auto-increment PK และ created_at/updated_at/deleted_at

| ตาราง | สิ่งที่คงไว้/เพิ่มจาก ZIP รอบ 3 |
|---|---|
| users | คง tenants_t_id nullable UNIQUE; เพิ่ม is_active boolean default true และ must_change_password boolean default true |
| rooms | r_name UNIQUE; r_status ใช้ VACANT/OCCUPIED |
| rentals | rt_status ACTIVE/ENDED; เพิ่ม unique index แบบมีเงื่อนไขสำหรับ Tenant และ Room เมื่อ ACTIVE และไม่ถูกลบ |
| contracts | rentals_rt_id UNIQUE; c_number UNIQUE; c_status ใช้ ACTIVE/EXPIRED/ENDED |
| meters | UNIQUE rooms_r_id + m_date; คงชื่อ m_water/m_elec |
| invoices | เพิ่ม period_start, period_end, start_meter_id, end_meter_id, water_usage, elec_usage, water_rate, elec_rate และ rent_rate; UNIQUE rentals_rt_id + period_start + period_end; ตรวจช่วงทับซ้อนใน transaction |
| payments | คง invoices_i_id UNIQUE และสถานะหลักเดิม; ข้อมูลล่าสุดอยู่ในแถวนี้ |
| repairs | tenants_t_id nullable; เพิ่ม reported_by_user_id NOT NULL FK users.u_id; CHECK ประเภทกับ rooms_r_id; rp_description เป็น text |
| repair_histories | เพิ่ม changed_by_user_id FK users.u_id; rph_description เป็น text |
| payment_events (ใหม่) | pe_id, payments_p_id, actor_user_id, event_type, from_status, to_status, amount, payment_date, method, proof_path nullable, reason nullable, note nullable, created_at; append-only |
| audit_events (ใหม่) | ae_id, actor_user_id nullable สำหรับงานระบบ, entity_type, entity_id, action, old_values/new_values แบบ JSON, reason nullable, created_at; เก็บการแก้ Contract/Invoice และการระงับ/ลบข้อมูลสำคัญ ไม่เก็บ password/token หรือ bytes หลักฐาน |

รวมเป็น 10 ตารางธุรกิจหลัก + 2 ตารางประวัติสนับสนุน + ตาราง Laravel ที่จำเป็น การเพิ่มสองตารางนี้เป็นคำตัดสินใหม่ ไม่ใช่สิ่งที่พบใน ZIP เดิม payment_events ไม่เพิ่มจำนวน Payment ต่อ Invoice

สถานะ Contract ACTIVE เมื่อ Rental ACTIVE และ c_end ยังไม่ผ่านหรือเป็น NULL; EXPIRED เมื่อพ้น c_end แต่ Rental ยัง ACTIVE; ENDED เมื่อ Rental ENDED การประเมินวันและการ sync สถานะใช้กฎเดียวกันทั้งตอนอ่านและงานประจำวัน ไม่เปลี่ยนสถานะ Room จากวันหมดสัญญา

Data type: เงิน DECIMAL(10,2), มิเตอร์/Usage DECIMAL(10,2), อัตราต่อหน่วย DECIMAL(10,2), วัน DATE, enum เป็น string พร้อม validation/check ตามที่รองรับ FK ที่อ้างหลักฐานประวัติใช้ restrict/no action ไม่ cascade ลบประวัติ

## 12. ความสอดคล้อง การลบ และเหตุการณ์พร้อมกัน

- ใช้ transaction สำหรับ Move-in+Contract+Room, Move-out+บิลสุดท้าย+Payment+Room, Invoice+Payment, Payment+Event และ Repair+History
- เข้าสู่ transaction แบบมีสิทธิ์เขียนก่อนอ่านเงื่อนไขสำคัญบน SQLite ตรวจสถานะล่าสุดและอาศัย UNIQUE เป็นด่านสุดท้าย ไม่พึ่ง row lock ที่ฐานข้อมูลนี้ไม่มี
- การเปลี่ยนสถานะใช้เงื่อนไขสถานะก่อนหน้า หากเปลี่ยนไปแล้วตอบ 409 ไม่เขียนทับผลของอีก request ปุ่ม disable เป็นเพียง UX ไม่ใช่มาตรการเดียว
- งานออก Invoice ช่วงเดิม/Move-in ห้องเดิม/อนุมัติซ้ำ ต้องไม่สร้างรายการซ้ำ busy error ให้ retry แบบจำกัดก่อนแจ้งลองใหม่ และไม่ retry หลัง commit โดยไม่ตรวจผลเดิม
- Tenant/Room ลบแบบ Soft Delete ได้เฉพาะยังไม่มีข้อมูลธุรกิจอ้างอิง หากมีประวัติให้เก็บ record เดิม Tenant ที่ย้ายออกถือว่าไม่ใช่ผู้พักปัจจุบันจาก Rental ไม่ต้องลบ Tenant
- User ใช้ is_active ระงับแทนลบเมื่อมีประวัติ; ไม่ลบบัญชี Admin คนสุดท้ายที่ใช้งานได้
- Rental/Contract/Invoice/Payment/Repair ที่บันทึกจริงไม่มีปุ่มลบใน MVP ใช้สิ้นสุดหรือสถานะตาม Flow แทน meter ลบได้เฉพาะยังไม่ถูก Invoice อ้างอิงและไม่ทำให้ช่วงบิลที่ออกแล้วเสียหาย
- ประวัติสำคัญอ่าน reference ที่ Soft Delete แล้วได้เมื่อผู้ใช้ผ่านสิทธิ์ แต่ไม่เอารายการที่ถูกลบมานับ Dashboard ห้ามเปิด withTrashed แบบข้าม ownership
- Audit และประวัติ payment/repair ไม่มี endpoint ให้แก้หรือลบ

## 13. การส่งมอบและขอบเขตการใช้งาน

เป้าหมาย MVP คือระบบหอพักเดียวบนเซิร์ฟเวอร์แอปหนึ่งเครื่อง มี HTTPS และ private persistent storage สำหรับ SQLite/หลักฐาน ห้ามวางไฟล์ SQLite ใน public หรือใช้ SQLite ไฟล์เดียวข้ามหลาย app servers

ก่อนย้ายจากสาธิตไปใช้งานจริงต้องตรวจสภาพแวดล้อมและทดสอบเพิ่มเติม เอกสารนี้ไม่กำหนดว่าระบบรองรับจำนวนผู้ใช้เท่าใดโดยไม่มีผลทดสอบ

สำรองฐานข้อมูลด้วยวิธีที่ได้ snapshot สอดคล้อง พร้อมหลักฐานและ APP_KEY ที่จัดเก็บแยกอย่างปลอดภัย สำรองรายวันและก่อนปรับ Schema เก็บอย่างน้อย 7 ชุดรายวัน และทดลองกู้คืนก่อนส่งมอบ ไม่คัดลอกเฉพาะไฟล์ SQLite ระหว่างเขียนโดยละเลย journal/WAL

ค่าเฉพาะสถานที่ที่ต้องกรอกก่อนใช้งาน: ชื่อหอพัก อัตราน้ำ อัตราไฟ บัญชีรับโอนที่จะแสดง ช่องทางส่งมอบรหัสชั่วคราว URL และตำแหน่ง backup สิ่งเหล่านี้เป็นข้อมูลจริงที่ต้องรับจากเจ้าของหอพัก ไม่ใช่ประเด็น Architecture ที่ยังเปิดค้าง และห้ามใช้ข้อมูลสมมติแทนจริง

## 14. ผู้รับผิดชอบ

| คน | เจ้าของงาน | จุดส่งต่อ |
|---|---|---|
| เกลือ | Schema กลาง, Auth/Policy, Actions/API, Validation, Payment Backend, transaction/audit | ตกลง payload/response กับทุกคน |
| Frame | Layout, Login UI, Tenant/Room, Dashboard และรายละเอียด | ใช้ API และข้อมูลประวัติจาก Big/Ta |
| Big | Rental/Contract, Invoice, Payment UI ทั้ง Tenant/Admin | รับ Meter จาก Ta และใช้ Payment API ของเกลือ |
| Ta | Meter/Usage, Repair UI/Flow/History | ส่ง Usage ให้ Big และงานซ่อมให้ Dashboard |

ทุกคนใช้ Schema และ enum เดียวกัน เจ้าของ UI ไม่เขียนกฎสิทธิ์หรือยอดคำนวณเป็นแหล่งจริงแยกจาก Backend

## 15. เกณฑ์ยอมรับก่อนถือว่าเสร็จ

| กรณี | ผลที่ต้องได้ |
|---|---|
| Login username และบัญชีถูกระงับ | เข้าได้เฉพาะบัญชี active; บัญชีระงับใช้ session เดิมไม่ได้ |
| Tenant เปลี่ยน ID ใน URL | ไม่อ่าน/แก้ข้อมูลหรือหลักฐานของคนอื่น |
| สร้าง Rental สองคำขอห้องเดียวกัน | สำเร็จได้หนึ่งรายการ อีกคำขอแจ้ง conflict; ไม่มี Contract/Room ค้างครึ่งทาง |
| สร้าง Rental | มี Contract เดียวและ Room OCCUPIED หลัง commit |
| สัญญาหมดอายุแต่ยังพัก | แสดง EXPIRED; ห้องไม่กลับเป็นว่างเอง |
| อ่านมิเตอร์ไม่ครบหรือเลขลดลง | ออก Invoice ไม่ได้ และแสดงเหตุผลชัดเจน |
| ออก Invoice ซ้ำ/ช่วงทับซ้อน | ไม่สร้างรายการใหม่ซ้ำ |
| เปลี่ยนอัตราหลังออกบิล | ยอดและอัตราบิลเดิมไม่เปลี่ยน |
| ส่งหลักฐาน | เป็น PENDING ไม่ถือว่าชำระสำเร็จ; มี event และไฟล์ที่ตรวจสิทธิ์ |
| Reject แล้วส่งใหม่ | Payment เดิมกลับ PENDING; เหตุผลและไฟล์เก่ายังอยู่ใน events |
| Walk-in | Admin บันทึกยอดเต็มเป็น PAID มีผู้รับเงิน/วิธี/วัน/หมายเหตุใน event |
| อนุมัติพร้อมกันสองคน | เกิด PAID event สำเร็จครั้งเดียว |
| PENDING เกินกำหนด | แสดงรอตรวจพร้อม overdue และนับยอดค้างตามนิยาม |
| ย้ายออกมีหนี้ | ห้องว่างหลังปิดรอบสำเร็จ; Tenant ยังอ่านและจ่ายบิลเดิมได้ |
| เปลี่ยนผู้เช่าห้อง | ผู้เช่าใหม่ไม่เห็นข้อมูลเดิม และช่วงค่าเช่าไม่ซ้อนกัน |
| Admin แจ้ง COMMON | rooms_r_id/tenants_t_id ว่างได้ และมี reported_by_user_id จริง |
| เปลี่ยนสถานะ Repair | สถานะและ History ถูกบันทึกพร้อมกัน |
| ลบข้อมูลที่มีประวัติ | ระบบปฏิเสธตามกฎ และประวัติไม่สูญหาย |
| กู้คืนข้อมูล | ฐานข้อมูลและหลักฐานกลับมาอ้างอิงกันได้ |

## 16. สิ่งที่ต้องปรับจากฐานเดิมหลังจบการออกแบบ

1. ปรับ ER/Data Dictionary ตามส่วน 11 และรักษาคำตัดสินเดิม tenants_t_id, ชื่อ Meter สั้น และไม่มี location
2. ปรับ Figma ตาม navigation และ state/validation ในเอกสารนี้
3. จัดทำ API contract ของแต่ละหน้าตามส่วน 10 ก่อนแยกพัฒนา
4. เพิ่ม Migrations ใหม่สำหรับฐานข้อมูลที่มีอยู่ ไม่ใช้การแก้ Migration เก่าเป็นหลักฐานว่าฐานจริงเปลี่ยนแล้ว และไม่ใช้ migrate:fresh กับข้อมูลที่ต้องเก็บ
5. นำกฎบัญชี สถานะ Billing/Payment และสิทธิ์ไปพัฒนา พร้อมทดสอบเกณฑ์ส่วน 15

เอกสารนี้ปิดการตัดสินใจสำหรับ MVP แล้ว รายการข้างต้นเป็นงานนำแบบไปใช้ ส่วนการรองรับมิเตอร์เปลี่ยน แบ่งจ่าย คืนเงิน เปลี่ยนราคาสัญญากลางคัน และฐานข้อมูลอื่นถูกตัดออกจากรุ่นนี้อย่างชัดเจน ไม่ใช่งานที่ปล่อยให้ผู้พัฒนาเดาเอง
