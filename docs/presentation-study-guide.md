# คู่มือเตรียมนำเสนอ Web Application และ Database

## สถาปัตยกรรมปัจจุบันหลัง SSR Migration 12 ตุลาคม 2026

ส่วนนี้แทนคำอธิบายเก่าที่หน้า Dashboard/Invoice Index/Room/Tenant/Meter/Repair ใช้ JavaScript โหลดข้อมูลหลักตอนเปิดหน้า เนื้อหาด้านล่างที่ลงวันที่ก่อนหน้านี้เป็นประวัติการเปลี่ยนแปลง

ตอนนี้ Page Controller ตรวจสิทธิ์และ query ข้อมูลก่อนส่ง Blade HTML กลับ Browser สำหรับรายการหลักทั้งหมดที่ audit ส่วนค้นหาและ filter ใช้ GET query parameters และ Laravel pagination พร้อม withQueryString ข้อมูลหนึ่งหน้าไม่ได้ขึ้นกับการดึง JSON ทั้งหมดแล้วค่อยแบ่งใน JavaScript

ตัวอย่างตามอ่าน: GET /invoices เข้า InvoicePageController::index ตรวจ Gate viewAny, validate q/month/status/page, ใช้ Invoice::visibleTo/search/inMonth, คำนวณสรุปตามคำค้น/เดือน, กรองสถานะรายการ, eager load ความสัมพันธ์ แล้ว paginate 20 และส่ง invoices/stats/filters/isAdmin ไป Blade สรุปด้านบนครอบคลุมคำค้น/เดือนทั้งหมด ส่วน dropdown สถานะกรองแถวในตาราง ควรอธิบายความแตกต่างนี้ให้ชัด

GET /user/dashboard เข้า TenantDashboardController ตรวจ Gate และ tenant scope อ่านสัญญาเพื่อแสดง c_rent รายเดือน รวมยอดค้างจากฐานข้อมูล และ query บิล/แจ้งซ่อมล่าสุด 3 รายการ ไม่โหลดทุกหน้า API ส่วน Admin Dashboard ใช้ DashboardSummary ร่วมกับ API endpoint เดิม จึงไม่คัดลอกสูตรสรุปคนละที่

GET /contracts ใช้ ContractPageController แสดงวันสิ้นสุดจริง มี search/status/page การเปิด modal ยังเรียก API เพื่อได้รายละเอียดล่าสุด และ PATCH ยังผ่าน UpdateContract ตามเดิม GET /rentals/create ส่งผู้เช่าที่ไม่มี ACTIVE Rental และห้อง VACANT ที่ไม่มี ACTIVE Rental เข้า Blade การเปลี่ยนห้องจึงค่อยตรวจ Meter ผ่าน API; ตอน submit CreateRental ยังตรวจทุกกฎซ้ำเพื่อกันข้อมูลหน้าค้างและการกดพร้อมกัน

GET /meters?room=... แสดงประวัติมิเตอร์รวมยกเลิกจาก server พร้อม flags ที่ batch query ด้วย MeterPresentation การแก้/ยกเลิกยังต้องใช้ reason/confirmation/expected_event_id และถูกตรวจโดย Action การเลือกต้น/ปลายเก็บชั่วคราวใน sessionStorage แยกตามห้องเพื่อใช้ข้าม pagination ไม่สร้าง Invoice เมื่อกดคำนวณหน่วยยังส่งรหัสไปให้ Backend อ่านค่าจริงและตรวจใหม่

GET /repairs ใช้ Repair::visibleTo และ eager load room ก่อน render ค่า status filter อยู่ใน URL ประวัติของรายการหนึ่งโหลดเมื่อกดดูเท่านั้น ผู้เช่าที่มีห้องเดียวเลือกห้องได้อัตโนมัติ ส่วน Admin เลือกเอง

Room/Tenant ใช้ข้อมูลที่ Controller ส่งมา render จริงแล้ว ไม่ fetch รายการทับตอนเปิดหน้า การลบยังผ่าน DeleteUnusedRecord และฟอร์ม POST พร้อม method DELETE/CSRF ส่วนประวัติห้องโหลดตามต้องการจาก /rooms/{room}/history ที่ตรวจ Admin/Policy และกรองตามรหัสห้องจริง จำกัดล่าสุด 50 รายการต่อประเภท

แนวตอบอาจารย์: SSR เปลี่ยนจุดที่อ่านและสร้าง HTML แต่ไม่ได้ย้ายกฎธุรกิจเข้า Blade ยังคง Actions เป็นจุดตรวจ/เขียนข้อมูล การซ่อนปุ่มไม่ได้แทน Authorization และ Blade {{ ... }} escape ข้อความ ส่วนข้อมูล JS ใช้ Js::from หรือ JSON_HEX_* ป้องกันข้อความหลุดออกจาก script

สิ่งที่ยังใช้ JavaScript: เปิด/ปิด modal, autofill, dynamic lookup, ส่ง API mutation, แสดง validation/conflict, คำนวณหน่วย preview และสลับรูปแบบห้อง หลัง mutation สำเร็จ reload หน้าเพื่ออ่านสถานะจริง ไม่ใช่ว่าระบบ SSR ห้ามมี JavaScript หรือห้ามมี JSON API

อ่านรายละเอียดและรายการ routes/API dependencies ใน docs/ssr-migration.md, docs/ssr-migration-inventory.csv, docs/ssr-route-inventory.json และ docs/ssr-api-references.json ผลตรวจล่าสุดก่อน commit: Pint ผ่าน, PHPStan 0 errors, tests 605 กรณี ผ่าน 586 ข้าม 19 และ 2,786 assertions, Blade compile/JS syntax/build ผ่าน ไม่ควรอ้างว่าการทดสอบครอบคลุม UI ทุกกรณี


ตรวจ source ครั้งแรกใน E:/Herd/webdataproject2_1 วันที่ 10 ตุลาคม 2026: branch main, commit 1aa1515 (edit maintanace history view), working tree สะอาด ณ เวลาตรวจครั้งแรก

อัปเดต 11 ตุลาคม 2026: fetch แล้วพบ origin/main ล่าสุด 1bdbb32 (Merge pull request #5 from Someder9728/chore/big-cleanup) คู่มือนี้เพิ่มข้อมูลจาก source ของ origin/main โดยตรง Local main ยังเป็น 1aa1515 และมี resources/views/maintenance/meters.blade.php แก้ค้าง จึงไม่ได้ pull หรือแก้ไฟล์โปรเจกต์ ไม่มีการรัน regression ใหม่ในรอบอัปเดตนี้

## ข้อมูลใหม่สำหรับนำเสนอ ณ origin/main 1bdbb32

- มี Dashboard ผู้เช่าจริงแล้วที่ /user/dashboard ใช้ resources/views/user/dashboard.blade.php ก่อนหน้านี้ route นี้ redirect ไป /my/rentals ข้อความเก่าว่าไม่มี Dashboard ผู้เช่าต้องเปลี่ยน
- หน้า /dashboard ผ่าน DashboardController: Admin ได้ view dashboard ส่วนผู้ใช้ฝั่ง Tenant ไป user.dashboard ทั้งสอง role ใช้ layout/sidebar ร่วมกันแต่มีคนละหน้า Dashboard
- Dashboard ผู้เช่าเรียก API rentals, invoices, repairs แบบอ่านทุกหน้าของ pagination ผ่าน getAll และแสดงห้อง/สัญญา ยอดค้าง บิลล่าสุด และแจ้งซ่อม ข้อมูลพึ่ง owner scope ที่ API ไม่มี tenant dashboard summary endpoint ใหม่ใน change นี้
- เพิ่ม /my/meters ผ่าน TenantMeterPageController และ view rentals/my-meters เป็นหน้าประวัติเลขมิเตอร์แบบอ่านอย่างเดียว Controller กรอง Rental ตาม tenants_t_id และมิเตอร์ตามช่วง rt_movein ถึง rt_moveout ถ้ามี ไม่ใช่ช่องให้ผู้เช่าบันทึก/แก้มิเตอร์ และไม่ใช่ยอดเรียกเก็บแทน Invoice
- Admin Dashboard กดดูทั้งหมดไป Invoice และ Repair ได้แล้ว และการนับห้องรองรับค่าเก่าภาษาไทยควบคู่ VACANT/OCCUPIED การรองรับนี้อยู่ใน Dashboard queries ไม่ได้แปลว่าทุก business Action ยอมรับสถานะภาษาไทย
- Logout เปลี่ยนปลายทางเป็นหน้า login พร้อมปรับ AuthenticationTest และ Fortify logout redirect
- Cleanup ลบ tenant views เก่าที่ไม่ใช้ ได้แก่ rentals/mine, rentals/my-contracts, rentals/_my-styles หน้า tenant/rentals และ tenant/contracts ยังคงอยู่ มีการจัดรูปแบบ/ลบ comments ในหลายไฟล์ด้วย

ส่วนที่ต้องเพิ่มในสไลด์ 2 และ 8: อธิบาย Dashboard ผู้เช่าแบบ client fetch เทียบกับหน้าประวัติมิเตอร์ที่ Controller query แล้ว render Blade ฝั่ง server เป็นตัวอย่างว่าเว็บเดียวใช้การโหลดข้อมูลต่างรูปแบบได้

ส่วนที่ต้องเพิ่มในเดโม: หลัง Tenant login เปิด Dashboard และ /my/meters ก่อนเข้า Invoice อธิบายว่าเลขมิเตอร์เป็นข้อมูลต้นทาง ส่วนยอดเรียกเก็บยึด snapshot ใน Invoice

### จุดที่พบจาก source ใหม่และต้องตรวจเพิ่มก่อนนำเสนอ

1. Dashboard ผู้เช่าโหลดสัญญาแล้วแสดง c_rent แต่หลังจากนั้นเขียนทับด้วย invoices[0].i_rent พร้อมข้อความ /เดือน บิล partial period มี i_rent เป็นค่าเช่าเฉพาะช่วง จึงอาจแสดงผิดความหมาย และบิลล่าสุดอาจเป็นของการเช่าเก่า ต้องยืนยัน UI หรือปรับก่อนเดโม อย่าอธิบายว่าตัวเลขนี้เป็นอัตรารายเดือนที่ถูกต้องเสมอ
2. Dashboard ใช้ Number รวมยอดเพื่อแสดงผลฝั่ง client ต่างจาก Calculator ฝั่ง server ที่ใช้ BigDecimal ข้อความว่าเงินทั้งระบบใช้ BigDecimal ต้องจำกัดความว่าเป็น business calculation ของ Invoice ที่ตรวจพบ
3. TenantMeterPageController query มิเตอร์แต่ละ Rental ภายใน map จึงมีจำนวน queries เพิ่มตามจำนวน Rental และโหลดรายการทั้งหมด ยังไม่ควรอ้างว่าหน้านี้ optimize สำหรับประวัติปริมาณมากแล้ว
4. หากไม่มี ACTIVE rental Dashboard ใช้ rentals[0] เป็น fallback จึงอาจแสดงห้องของประวัติการเช่าล่าสุดพร้อมสถานะสิ้นสุด ต้องเดโมบัญชี ENDED และไม่มี Rental ด้วย
5. Source ของ Dashboard มี CSS เฉพาะหน้าเพิ่มเข้ามา ดังนั้นข้อความว่า UI ทุกหน้าใช้ shared CSS เดิมทั้งหมดไม่ตรงแล้ว ให้บอกว่ามี layout ร่วมและรูปแบบสอดคล้องกัน โดย Dashboard มี style เฉพาะหน้า

รายการนี้เป็นข้อสังเกตจาก source ไม่ใช่ผลทดสอบ UI หรือคำยืนยันว่าแก้ไขแล้ว Business Actions ของ Billing/Rental/Meter/Payment และ migrations ไม่อยู่ใน diff ระหว่าง 1aa1515 กับ 1bdbb32 ตามการตรวจครั้งนี้

เอกสารนี้เป็นบทพูดและคู่มือศึกษา ยังไม่ใช่ไฟล์ PowerPoint การตรวจครั้งนี้อ่าน routes/API, CreateRental, IssueInvoice, CalculateInvoice, NextBillingPeriod, UpdateInvoice, MoveOutRental, ReviewPaymentProof, InvoicePolicy, SqliteTransaction และ migrations บางรายการ ยังไม่ใช่การตรวจทุกไฟล์หรือรันทดสอบใหม่ทั้งระบบ ตัวเลขผลทดสอบจาก commit ก่อนหน้าไม่ควรนำมาอ้างว่าเป็นผลล่าสุดโดยไม่มีการรันใหม่

## เป้าหมายในการทำความเข้าใจ

### อัปเดต SSR ของ Big และการ sync Local 12 ตุลาคม 2026

Pull origin/main แบบ fast-forward สำเร็จที่ E:/Herd/webdataproject2_1 จาก 0ca2ed2 ไป 8ee6f76 ซึ่งเป็น merge PR #6 feature/big-ssr งานนี้ประกอบด้วย 54483c2 (server-render rental and invoice read pages) และ b8acdc8 (แก้ PHPStan ของ rental timeline) ไฟล์ resources/views/maintenance/meters.blade.php ที่ผู้ใช้แก้ค้างได้รับการเก็บไว้ ไม่ได้นำไปรวม commit ใหม่โดยอัตโนมัติ

ให้ปรับบทอธิบาย architecture ของหน้าต่อไปนี้จากการโหลดข้อมูลหลักด้วย JavaScript เป็น Controller อ่านข้อมูลก่อนส่ง HTML:

- Invoice History: InvoicePageController::history ตรวจ Gate viewAny, validate q/month, ใช้ Invoice scopes visibleTo/search/inMonth, กรอง PAID, รวมยอดจาก Payment และ paginate 50 พร้อม withQueryString
- Invoice Detail: InvoicePageController::show ใช้ Invoice route binding, ตรวจ Gate view, eager load rental.tenant/rental.room/payment และอ่าน Payment Events พร้อม actor ก่อน render invoices.show
- Rental Index: RentalPageController::index ตรวจ Gate viewAny, validate/search, ส่งรายการ ACTIVE และประวัติสิ้นสุดให้ Blade โดยประวัติ paginate 20 ส่วน ACTIVE ยังโหลดทั้งหมด
- Rental Detail: RentalPageController::show อ่าน Rental พร้อม tenant/room/contract และตรวจ Gate view ก่อนเลือกหน้า Admin/Tenant ฝั่ง Admin อ่าน Audit Events และประกอบ RentalTimeline ก่อน render
- Contract Detail: resources/views/rentals/contract.blade.php เปลี่ยนเป็น SSR ในชุดนี้ ต้องตาม route/controller ที่เรียกก่อนซ้อมตอบรายละเอียดหน้าสัญญา

API mutation และ business Actions ยังเป็นส่วนหลักสำหรับการเขียนข้อมูล การเปลี่ยน rendering ไม่ได้เปลี่ยนกฎคิดเงินหรือ workflow Payment ควรตาม Model scopes ใน app/Models/Invoice.php เพราะ API และหน้า SSR ใช้ query logic ร่วมกัน ลดการทำ search/month/visibility ซ้ำคนละที่

เพิ่มไฟล์ศึกษา: app/Support/Format.php, app/Support/RentalTimeline.php, tests/Feature/ServerRenderedPagesTest.php ลองเปิดหน้าแบบปิด JavaScriptเพื่อดูว่าข้อมูลหลักอยู่ใน HTML แล้ว โดย interaction ที่ต้องใช้ JavaScript อาจไม่ทำงานตามปกติ

สถานะ migration นี้เป็น SSR บางหน้า ยังไม่ใช่ Holistic SSR เสร็จทั้งระบบ: InvoicePageController::index ยังคืน view เปล่า, Dashboard และ Contract Index/ตัวเลือก Rental Create/Meter/Repair ยังต้อง audit และ migrate ตามเอกสารแยก ไม่ควรใช้คำว่า SSR ครบทุกหน้าในพรีเซนต์

ผลตรวจหลัง pull: composer test ผ่าน Pint, PHPStan 0 errors, Pest ทั้งหมด 593 กรณี ผ่าน 574 ข้าม 19 และ 2,692 assertions การตรวจนี้เป็น automated suite ไม่ใช่การตรวจ UI ทุก interaction ด้วย Browser

### อัปเดตการแสดงผลและการออกจากระบบ 11 ตุลาคม 2026

Local main อัปเดตถึง 8e10853 แล้ว มี commit 367e430 แก้ค่าเช่ารายเดือนให้ใช้สัญญา และ 8e10853 ใช้ shared CSS ในหน้าประวัติมิเตอร์ผู้เช่า ข้อสังเกตเรื่องนำ i_rent ของบิลล่าสุดมาทับ c_rent ในรายการตรวจรอบ 1bdbb32 เป็นประวัติข้อที่พบก่อนการแก้ ไม่ใช่ข้อสรุปสถานะล่าสุด

เพิ่มกล่องยืนยันออกจากระบบในฟอร์มสามจุด ได้แก่ resources/views/layouts/app/sidebar.blade.php, resources/views/components/desktop-user-menu.blade.php และ resources/views/pages/auth/verify-email.blade.php การแก้รอบนี้ยังไม่ commit/push โดยผู้ช่วย ต้องใช้คำสั่ง commit เฉพาะสามไฟล์ดังกล่าว

ลำดับการทำงาน:

1. เมื่อผู้ใช้ submit ฟอร์ม Logout จะเรียก onsubmit="return window.confirm('คุณแน่ใจหรือไม่ว่าต้องการออกจากระบบ?')" ก่อนส่งฟอร์ม
2. window.confirm เป็น dialog มาตรฐานของ Browser เมื่อยกเลิกคืน false ทำให้หยุด default form submission จึงไม่มี POST Logout และ session คงอยู่ เมื่อยืนยันคืน true จึงส่งฟอร์มต่อ กล่องนี้ใช้ได้ทั้ง role Admin/Tenant ที่ใช้ฟอร์มเดียวกัน
3. ฟอร์มยังใช้ POST ไป route('logout') พร้อม @csrf เหมือนเดิม กล่องยืนยันเป็นการป้องกันกดผิดฝั่ง UI ไม่ใช่การตรวจสิทธิ์หรือการตรวจรหัสผ่านซ้ำฝั่ง server
4. app/Livewire/Actions/Logout.php ที่ตรวจพบมี Auth::guard('web')->logout(), Session::invalidate(), Session::regenerateToken() และ redirect()->route('login') โครงสร้าง route/provider ต้องไล่เพิ่มหากอาจารย์ถามว่า Fortify ผูก endpoint Logout กับ handler ใด การมีไฟล์ Action นี้เพียงอย่างเดียวไม่พิสูจน์ว่า POST route เรียก Action นี้โดยตรง
5. การออกจากระบบอัตโนมัติหลังเปลี่ยนรหัสผ่านหรือ session หมดอายุไม่ต้องรอ dialog นี้ เพราะไม่ใช่การ submit ฟอร์ม Logout ของผู้ใช้

คำถามที่อาจารย์ถาม: ทำไมใช้ onsubmit ไม่ใช้เฉพาะ onclick? เพราะยืนยันเมื่อฟอร์ม submit รวมถึงการ submit ผ่านปุ่มหรือแป้นพิมพ์ ไม่ผูกกับการคลิกปุ่มเพียงวิธีเดียว

คำถามที่อาจารย์ถาม: ถ้าปิด JavaScript ยังออกจากระบบได้ไหม? ได้ ฟอร์มยังส่ง POST ตามปกติ แต่จะไม่มีกล่องยืนยัน Backend และ CSRF ยังคงเป็นส่วนที่จัดการความถูกต้องของ request

การตรวจรอบ Logout: AuthenticationTest มี 5 กรณี ผ่าน 4 ข้าม 1 มี 11 assertions, Blade view:cache ผ่าน, git diff --check ผ่าน ผลนี้ไม่ได้ยืนยันการกด dialog จริงใน Browser ให้ซ้อม Admin/Tenant อย่างละสองครั้ง: ครั้งแรกกดยกเลิกและยังอยู่หน้าเดิม ครั้งที่สองยืนยันและกลับหน้า login โดยหน้าที่ต้อง login ไม่ควรเข้าถึงได้อีก

การเปลี่ยนนี้อยู่ใน Blade จึงไม่ต้อง npm build ใหม่ ใช้ view:clear หลังตรวจ Blade cache เพื่อให้หน้าเว็บอ่าน view ที่แก้ล่าสุด

ทุกฟังก์ชันต้องตอบให้ได้ว่าใครเรียกได้ รับข้อมูลอะไร เข้าทาง route ไหน ตรวจเงื่อนไขตรงไหน อ่าน/เขียนตารางไหน เปลี่ยนสถานะอะไร หากล้มเหลวจะเกิดอะไร และมี test ใดพิสูจน์พฤติกรรมนั้น

หน้าที่ของผู้รวบรวมงานเป็นงานวิศวกรรมที่อธิบายได้ตรงไปตรงมา: ตรวจ interface ระหว่างส่วนงาน รวม branch ตรวจ business rules แก้ความไม่สอดคล้อง และทดสอบระบบร่วมกัน ไม่ต้องอ้างว่าเขียนทุกบรรทัดเอง ต้องแยกสิ่งที่ตนรับผิดชอบกับส่วนที่เพื่อนพัฒนา

## 10 สไลด์และบทพูด

### 1. การเช่าหนึ่งรายการเชื่อมระบบทั้งระบบ

ข้อความบนสไลด์: ห้องว่าง / ย้ายเข้า / บันทึกมิเตอร์ / ออกบิล / ชำระเงิน / ย้ายออก

บทพูด: วันนี้ใช้การเช่าหนึ่งรายการอธิบายว่าหน้าเว็บ กฎของระบบ และฐานข้อมูลทำงานร่วมกันอย่างไร ข้อมูลแต่ละขั้นเชื่อมกัน เราจึงต้องรักษาประวัติและความถูกต้องตลอดอายุการเช่า

ภาพ: เส้นทางผู้เช่าตัวอย่างคนเดียว ตั้งชื่อห้องเดียวกันตลอดการนำเสนอ ใช้ข้อมูลจำลอง

### 2. Request เดินทางผ่านส่วนใดบ้าง

ข้อความบนสไลด์: UI ส่ง request, Route เลือกปลายทาง, Middleware ตรวจเงื่อนไขเข้าถึง, Controller รับ/ตอบข้อมูล, Action ทำธุรกรรม, Model ติดต่อฐานข้อมูล

บทพูด: โครงการใช้ Laravel และมี Blade/Livewire รวมถึง JavaScript ที่เรียก API ภายในเว็บ จึงไม่ได้หมายความว่าทุกหน้าจะใช้วิธี render แบบเดียวกัน ตัวอย่างออกบิลใช้ POST /api/v1/invoices ผ่าน InvoiceController ไป IssueInvoice และ CalculateInvoice ก่อนบันทึกด้วย Eloquent

คำที่ต้องเข้าใจ: Controller ไม่เท่ากับ Model; Action เป็นการจัดโค้ด business operation ของทีม ไม่ใช่สิ่งที่ MVC บังคับ; dependency injection ส่ง calculator และ transaction helper ให้ Action ผ่าน constructor

หลักฐาน: routes/api-session.php, app/Http/Controllers/Api/V1/InvoiceController.php, app/Actions/Billing/IssueInvoice.php

### 3. ความสัมพันธ์ในฐานข้อมูล

ข้อความบนสไลด์: Tenant และ Room มีประวัติ Rental หลายรายการ; Rental เชื่อม Contract และ Invoice; Invoice เชื่อม Payment; Payment มี Event หลายรายการ; Room มี Meter หลายวัน

บทพูด: ความสัมพันธ์ระยะยาวไม่เหมือนข้อจำกัดในสถานะปัจจุบัน ห้องหนึ่งมีการเช่าได้หลายครั้งในประวัติ แต่มี ACTIVE ได้หนึ่งรายการในเวลาเดียวกัน สัญญาเป็นหนึ่งต่อหนึ่งกับการเช่าในโครงสร้างปัจจุบัน เพราะ contracts.rentals_rt_id มี unique ส่วน Payment เป็นหนึ่งต่อหนึ่งกับ Invoice และใช้ Event เก็บการดำเนินการหลายครั้ง

ภาพ: ERD เฉพาะตารางหลัก ใช้ AuditEvent เป็นที่เก็บประวัติส่วนกลาง ส่วน RepairHistory/PaymentEvent อ่านผ่าน View ไม่ใช่ตารางเก็บประวัติแยก หลีกเลี่ยงการวาด User กับ Tenant เป็น cardinality ที่ยังไม่ตรวจ constraint จริง

หลักฐาน: database/migrations/2026_09_11_121744_create_contracts_table.php, 2026_09_11_122241_create_payments_table.php, 2026_09_15_091651_add_unique_constraints_to_rooms_rentals_and_meters.php

### 4. ทำไมต้องมี Rental

ข้อความบนสไลด์: Rental บันทึกว่าใครอยู่ห้องไหนในช่วงใด และเป็นเจ้าของสัญญา/บิลของช่วงนั้น

บทพูด: ถ้าเก็บแค่รหัสผู้เช่าปัจจุบันบนห้อง เมื่อเปลี่ยนคน เราจะเสียบริบทว่าบิลเก่าเป็นของใคร การแยก Rental ทำให้คนเดิมย้ายห้องได้และห้องเดิมมีผู้เช่าคนใหม่ได้ โดยบิลยังอ้างอิงการเช่าเดิม

คำถามต่อ: ACTIVE rental มีหนึ่งรายการต่อผู้เช่าและต่อห้องตามกฎปัจจุบัน เป็นข้อกำหนดของระบบ ไม่ใช่ข้อจำกัดสากลของหอพักทุกแห่ง

### 5. ธุรกรรมย้ายเข้า

ข้อความบนสไลด์: ตรวจสิทธิ์และข้อมูล, ตรวจห้องว่างและมิเตอร์วันเข้าพัก, สร้าง Rental/Contract, เปลี่ยนห้อง OCCUPIED, เก็บ Audit

บทพูด: CreateRental ใช้วันปัจจุบันตาม Asia/Bangkok เป็นวันเข้าพัก และต้องมี Meter ของวันนั้นอยู่ก่อน Action นี้ไม่ได้สร้างมิเตอร์ให้เอง ระบบตรวจว่าคนและห้องไม่มี ACTIVE rental แล้วบันทึกข้อมูลที่เกี่ยวข้องภายใน transaction เพื่อให้สำเร็จทั้งหมดหรือย้อนกลับทั้งหมด

คำถามต่อ: ถ้าสร้าง Rental สำเร็จแต่ Contract ล้มเหลว transaction ป้องกันการเหลือ Rental ที่ไม่มี Contract หาก exception ออกไปจากธุรกรรม ข้อมูลในธุรกรรมจะ rollback

หลักฐาน: app/Actions/Rentals/CreateRental.php, app/Support/SqliteTransaction.php

### 6. สูตรออกบิล

ข้อความบนสไลด์: Admin สั่งออกบิล; ค่าเช่าตามจำนวนวัน; น้ำไฟจาก Meter ตรงวันต้นและปลาย; เก็บผลคำนวณใน Invoice

บทพูด: วันที่ 1 เป็นขอบเขตรอบมาตรฐาน แต่ช่วงสั้นกว่าหนึ่งเดือนได้ Calculator ใช้ช่วง [start, end) จึงนับวันเริ่มและไม่นับวันปลาย ตัวอย่าง 15 ตุลาคมถึง 1 พฤศจิกายนคิดค่าเช่า 17 วันจากเดือนที่มี 31 วัน น้ำไฟใช้ผลต่างเลขมิเตอร์ ไม่หารตามจำนวนวัน

ตัวอย่างสำหรับสไลด์ โดยกำหนดอัตราสมมติเพื่ออธิบาย ไม่ใช่ค่า config ที่ตรวจยืนยันแล้ว:

- ค่าเช่า 4,500 บาท: 4,500 × 17 / 31 = 2,467.74 บาท
- น้ำ 104 − 100 = 4 หน่วย สมมติ 20 บาทต่อหน่วย รวม 80 บาท
- ไฟ 1,045 − 1,000 = 45 หน่วย สมมติ 8 บาทต่อหน่วย รวม 360 บาท
- รวมองค์ประกอบที่ปัดทศนิยมแล้ว = 2,907.74 บาท

Calculator ใช้ BigDecimal และ HalfUp ที่สองตำแหน่งสำหรับยอดเงิน ไม่ใช้ float คิดเงินโดยตรง บิลหนึ่งรายการต้องไม่เลยวันที่ 1 ของเดือนถัดจากเดือนเริ่ม ไม่ออกช่วงที่วันสิ้นสุดอยู่ในอนาคต และต้องไม่ทับบิลเดิม รวมถึงบิล soft delete

หลักฐาน: app/Actions/Billing/CalculateInvoice.php

### 7. ประวัติและการแก้ไขข้อมูล

ข้อความบนสไลด์: Snapshot ใน Invoice, ข้อจำกัดการแก้ไข, Audit ก่อน/หลังพร้อมผู้ดำเนินการ

บทพูด: Invoice เก็บรหัสมิเตอร์ หน่วย อัตรา และยอดที่คำนวณเพื่อรักษาผล ณ ตอนออกบิล การเปลี่ยนอัตรา config ภายหลังจึงไม่คำนวณบิลเก่าทุกครั้งที่เปิดดู Snapshot มีประโยชน์ด้านประวัติ แต่บิลยังแก้ได้ภายใต้กฎเฉพาะ ไม่ใช่ immutable ทุกกรณี

UpdateInvoice อนุญาตเฉพาะ Payment UNPAID ไม่มี Event และไม่มีข้อมูลการชำระตกค้าง ต้องมี reason ถ้าเปลี่ยน period จะคำนวณใหม่ด้วย rate ที่เก็บในบิลเดิม ถ้าเปลี่ยน due date อย่างเดียวจะไม่อ่านมิเตอร์หรือคำนวณยอดใหม่ หลัง Rental ENDED ยังแก้ period ได้ภายในช่วงการเช่าและวันย้ายออกตาม Calculator

หลักฐาน: app/Actions/Billing/UpdateInvoice.php

ข้อควรตอบให้ตรง: Soft delete คือยังมี row อยู่พร้อม deleted_at ไม่ใช่ลบถาวร การใช้ withTrashed ทำให้กฎบางเรื่องยังพิจารณาข้อมูลนั้น ไม่ใช่ทุก query จะเห็นข้อมูล soft delete เอง

### 8. สิทธิ์ในหน้าเว็บและสิทธิ์ในข้อมูล

ข้อความบนสไลด์: Sidebar ตาม role, Middleware คุม route, Policy คุมรายการ, Query จำกัดข้อมูลผู้เช่า

บทพูด: เมนูที่ต่างกันช่วยให้ใช้งานง่าย ส่วนความปลอดภัยต้องตรวจฝั่ง server ด้วย InvoicePolicy ตรวจบัญชี active และตรวจว่าบิลเชื่อม Rental ของ tenant คนที่ล็อกอิน ถ้าไม่ใช่เจ้าของจะตอบเป็น 404 เพื่อไม่เปิดเผยว่ารายการของคนอื่นมีอยู่

คำถามต่อ: Authentication บอกว่าเป็นใคร Authorization บอกว่าทำอะไรกับข้อมูลใดได้ API ภายในโครงการนี้มี auth middleware ที่ routes/api-session.php จึงไม่ควรนำเสนอว่าใช้ JWT โดยไม่มีหลักฐาน

หลักฐาน: routes/api-session.php, app/Policies/InvoicePolicy.php

### 9. การชำระและการรวมงาน

ข้อความบนสไลด์: Payment เก็บสถานะปัจจุบัน, Event เก็บประวัติ, expected_event_id ตรวจว่าหน้ายังอ้างอิงหลักฐานล่าสุด

บทพูด: การอัปโหลดหลักฐานยังไม่เท่ากับชำระสำเร็จ Admin ต้องตรวจหลักฐาน ReviewPaymentProof อนุมัติได้เมื่ออยู่ PENDING และ Event ตรงกับรายการล่าสุด ถ้าข้อมูลเปลี่ยนหลังจากเปิดหน้า ระบบตอบ conflict ให้โหลดใหม่ เมื่ออนุมัติแล้วจึงเป็น PAID พร้อม Event และ Audit

เล่าบทบาทของตน: รวมงานจากสมาชิก ตรวจ API/UI contract แก้ integration และ regression แสดง branch flow จากหลักฐาน Git จริง ไม่กำหนดเจ้าของงานจากชื่อไฟล์เอง

หลักฐาน: app/Actions/Payments/ReviewPaymentProof.php, composer.json และ tests/Feature

### 10. ย้ายออกและเดโม

ข้อความบนสไลด์: ตรวจ Meter วันย้ายออก, ตรวจบิลเดิมและช่วงว่าง, เติมบิลจริง, จบ Rental/Contract, ห้องกลับ VACANT

บทพูด: ไม่มี scheduler ออกบิลรายเดือน แต่เมื่อ Admin สั่งย้ายออก Action สามารถสร้าง Invoice จริงเพื่อเติมช่วงที่ยังไม่เรียกเก็บได้ แบ่งตามขอบเขตเดือน และต้องมี Meter ตรง boundary ทุกช่วง หากช่วงใดคำนวณไม่ผ่าน ธุรกรรมชั้นนอก rollback ทั้งการย้ายออก

จุดสำคัญ: Rental ENDED ไม่ได้แปลว่าบิลทุกใบ PAID ควรแยกสถานะการพักกับหนี้ค้างชำระ การมี c_deposit ไม่ได้พิสูจน์ว่าระบบมี flow คืนเงินมัดจำครบถ้วน

หลักฐาน: app/Actions/Rentals/MoveOutRental.php

## Walkthrough โค้ดด้วยตนเอง

ทุก path ด้านล่างอ้างอิง root E:/Herd/webdataproject2_1

1. อ่าน composer.json เพื่อรู้ stack และคำสั่งตรวจคุณภาพ จากนั้น routes/web.php, routes/api-session.php และ bootstrap/app.php เพื่อดูการลงทะเบียน route/middleware อย่าสับสน URL หน้าเว็บกับ URL API
2. เปิด migrations ตามลำดับเวลา ไม่อ่านเฉพาะ migration สร้างตาราง เพราะ migration ภายหลังเพิ่ม constraint และ field สำคัญ จด PK, FK, nullable, unique, decimal, timestamps, softDeletes
3. อ่าน Model คู่กับ migration คำสั่ง belongsTo/hasMany/hasOne บอก ORM relationship ส่วน foreign key/unique ใน migration บังคับฐานข้อมูลจริง เป็นคนละหน้าที่
4. ตาม CreateRental ทีละบล็อก จด input, Validator, Gate, query ตรวจ ACTIVE, Meter วันนี้, write ทั้งหมด และ Audit ลองอธิบายโดยไม่เปิดโค้ดแล้วกลับมาเช็ก
5. ตาม InvoiceController ถึง IssueInvoice และ CalculateInvoice แยก preview กับ persist ให้ชัด NextBillingPeriod เลือกช่วงว่างแรก ส่วน Calculator ตัดสินว่าช่วงนั้นออกบิลได้หรือไม่
6. อ่าน UpdateInvoice ดูว่ากฎห้ามแก้ขึ้นกับ Payment/Event อย่างไร แล้วทดลองแยก due-only กับ period change บนข้อมูลจำลอง
7. อ่าน SubmitPaymentProof, ReviewPaymentProof, RecordWalkInPayment และ migration centralize_payment_and_repair_history จดทุก transition จากโค้ดจริง ยังต้องอ่าน submit/walk-in แบบเต็มเพื่อยืนยันรายละเอียดการอัปโหลดและรับเงิน
8. อ่าน MoveOutRental ตั้งแต่ตรวจสถานะถึง gap loop เขียนตัวอย่างมีบิลกลางเดือนแต่ขาดบิลก่อนหน้า แล้วตาม cursor ทีละรอบ
9. อ่าน RecordMeterReading, CorrectMeterReading, CancelMeterReading และ MeterController ตรวจเงื่อนไขยกเลิก/แก้/restore ก่อนตอบรายละเอียด Meter locking ยังต้องตรวจ source ทั้งก้อนล่าสุด
10. อ่าน RepairController, CreateRepair, UpdateRepairStatus, RepairPolicy และ maintenance.js เปรียบเทียบ request กับ JSON ที่นำไป render
11. เปิด tenant views, RentalPageController และ sidebar เพื่อแยกว่า server render กับ client fetch อยู่จุดใด ตรวจ owner scope ในทุก endpoint ที่ใช้
12. เปิด Feature test คู่กับฟังก์ชันที่อ่าน เลือก test ที่สร้างสถานการณ์ผิดแล้วตรวจว่าไม่มีข้อมูลถูกเขียนหรือรั่วไหล

## คำถามอาจารย์และคำตอบหลัก

**ทำไมแยก User กับ Tenant?** User ใช้ข้อมูลบัญชีและสิทธิ์ Tenant ใช้ข้อมูลบุคคลและประวัติการเช่า ทำให้หน้าที่ต่างกัน ต้องตรวจ unique ของ account linkage ก่อนระบุ cardinality จริง

**ฐานข้อมูลเป็น 3NF ไหม?** อธิบาย functional dependency ของแต่ละตารางจาก PK และ constraint จริงก่อน ไม่รับรองทั้ง schema แบบเหมารวม Snapshot ใน Invoice เป็นการเก็บข้อเท็จจริงธุรกรรมตามเวลา มีเหตุผลด้านประวัติและต้องควบคุมการอัปเดต

**ทำไมสถานะห้องกับ Rental ดูซ้ำกัน?** Room เก็บสถานะปัจจุบันเพื่อใช้แสดง/ตรวจ ส่วน Rental เก็บวงจรการพัก การเปลี่ยนสถานะต้องทำร่วมกันใน transaction และมี constraint กัน ACTIVE ซ้ำ ต้องยอมรับว่าการแก้ DB ตรงอาจทำให้ข้อมูลไม่สอดคล้องได้

**Foreign Key ป้องกันอะไร?** ป้องกันการอ้างอิง row ที่ไม่มีจริง แต่ไม่ได้ตรวจทุก business rule เช่นวันบิลทับกันหรือห้องว่าง เงื่อนไขเหล่านั้นต้องตรวจใน application เพิ่ม

**Unique กับ overlap ต่างกันอย่างไร?** Unique ของ rental/period/start/end กันช่วงที่เหมือนกันเป๊ะ Calculator ตรวจ overlap ด้วย start เก่า < end ใหม่ และ end เก่า > start ใหม่ เพื่อกันช่วงทับแม้วันไม่เหมือนกัน

**ทำไมต้อง [start,end)?** ทำให้ช่วงติดกันใช้ boundary เดียวกันโดยไม่คิดวันซ้ำ เช่น 15 ต.ค.–1 พ.ย. กับ 1 พ.ย.–1 ธ.ค. และใช้ Meter วัน boundary ร่วมกันได้

**ทุก Invoice ต้องจบวันที่ 1 ไหม?** ไม่จำเป็น วันที่ 1 เป็น standard boundary แต่ช่วงสั้นหรือช่วงท้ายถึงวันย้ายออกได้ ทั้งนี้ Invoice เดียวไม่ข้ามเลย boundary เดือนที่ Calculator กำหนด

**ทำไมมิเตอร์ล่าสุดใช้แทนวันปลายบิลไม่ได้?** เพราะจะรวมการใช้คนละช่วงกับที่เรียกเก็บ ต้องใช้ Meter ห้องเดียวกันตรงวัน start/end

**เลือกต้น/ปลายในหน้ามิเตอร์แล้วออกบิลเลยไหม?** ไม่ใช่ หน้านั้นเรียก GET meter-usage เพื่อแสดงหน่วย การออกบิลเป็น request อีกชุดและ Calculator หา Meter ตามวันของ Invoice ใหม่

**Preview เขียนข้อมูลไหม?** IssueInvoice คืน snapshot ก่อน Invoice::create เมื่อ persist=false จึงไม่สร้าง Invoice/Payment/Audit การ preview ยังต้องผ่านกฎคำนวณรวมถึงวันสิ้นสุดไม่เป็นอนาคต

**ทำไม Payment หนึ่งต่อ Invoice แต่มี Event หลายรายการ?** Payment แทนสถานะปัจจุบันของการชำระบิลนั้น Event เก็บเหตุการณ์ส่งหลักฐาน/พิจารณาหลายครั้ง จึงไม่ควรเล่าว่ามี Payment ใหม่ทุกครั้งที่ส่งหลักฐาน

**กดอนุมัติจากหน้าที่เปิดค้างไว้อย่างไร?** Review ตรวจ expected_event_id กับ Event ล่าสุดและ PENDING ถ้าไม่ตรงตอบ 409 ไม่มีการอนุมัติข้อมูลเก่าทับข้อมูลใหม่

**ระบบรองรับ partial payment ไหม?** ห้ามอ้างว่ารองรับจากการมี p_amount เพียงอย่างเดียว การอนุมัติที่อ่านพบตรวจยอดเท่ากับ i_total ส่วนการแบ่งจ่ายต้องมี flow และ tests พิสูจน์เพิ่มเติม

**ทำไมไม่ float?** จำนวนเงินต้องควบคุม precision และ rounding Calculator ใช้ BigDecimal และปัดองค์ประกอบแต่ละตัวก่อนรวม

**Transaction กันทุก race condition ไหม?** Transaction รักษาการเขียนเป็นกลุ่ม แต่ต้องใช้ร่วมกับ constraint และการตรวจสถานะล่าสุด SQLite helper retry เฉพาะ busy/locked สูงสุด 5 ครั้ง ไม่ใช่ retry ทุก error และไม่ใช่หลักฐานว่ารองรับโหลดระดับใหญ่

**SQLite เปลี่ยนเป็น MySQL ได้ทันทีไหม?** ไม่ควรรับรอง Migration มี partial unique index แบบ WHERE และ SQL บางจุดเฉพาะฐานข้อมูล ต้องทบทวน migration/query และทดสอบใหม่ก่อนเปลี่ยน

**Soft delete คือ Audit ไหม?** ไม่เหมือนกัน Soft delete บอกว่าข้อมูลถูกยกเลิกเมื่อใด ส่วน Audit เก็บใครเปลี่ยนอะไร ก่อน/หลัง และ reason ใน operation ที่กำหนด Audit table ไม่ได้พิสูจน์ว่าป้องกันผู้ดูแลฐานข้อมูลแก้ log ได้

**ระบบ production-ready ไหม?** แยกสิ่งที่ทำและทดสอบแล้วออกจากงาน deployment เช่น secrets, HTTPS, backup/restore, permission storage, monitoring และ load test ซึ่งต้องมีหลักฐานแยก การ merge main ไม่เท่ากับ deploy สำเร็จ

## แผนเดโมที่ทำซ้ำได้

ใช้ฐานข้อมูลจำลองแยกจากข้อมูลจริง เตรียม Admin และ Tenant สองคน ห้องและ Rental ที่รู้รหัส มิเตอร์ตรงวัน boundary และสถานะบิลที่ตั้งใจสาธิต หลีกเลี่ยงแก้เวลาระบบคอมพิวเตอร์เพื่อทำเดโมวันอนาคต

1. เปิด Admin แสดงห้องและการเช่าตัวอย่าง พร้อม Tenant/Rental/Contract ที่สัมพันธ์กัน
2. เปิด Meter เปรียบเทียบต้น/ปลายและคำนวณหน่วย แยกจากการออก Invoice ให้ชัด
3. เปิด Invoice preview และ issue ที่ช่วงจบไม่เกินวันจริง ชี้หน่วย อัตรา ยอด และ Payment UNPAID
4. เข้า Tenant แสดงเฉพาะบิลตัวเอง ทดสอบ URL ของอีกคนเป็นกรณีป้องกันข้อมูลรั่ว
5. ใช้หลักฐานจำลองแสดง PENDING และให้ Admin อนุมัติเป็น PAID โดยเตรียม flow คืนสถานะก่อนซ้อมรอบใหม่
6. ถ้าเวลาพอ แสดง Rental ย้ายออกที่เตรียมไว้พร้อม closing meter และบิลที่เกิดจาก gap หลีกเลี่ยงสร้างการย้ายออกหลายรอบบนข้อมูลชุดเดียว

ก่อนวันนำเสนอเตรียม screenshot สำรองทุกขั้น รายการบัญชีเดโม และรายการข้อมูลก่อน/หลังเดโม มีวิธีคืนฐานข้อมูลจำลองที่ตรวจแล้ว ไม่ใช้ migrate:fresh บนฐานข้อมูลจริง

## เกณฑ์พร้อมนำเสนอ

- อธิบาย request หนึ่งรายการได้ครบตั้งแต่ UI ถึงฐานข้อมูล
- เขียนความสัมพันธ์หลักและอธิบาย constraint โดยไม่ดูสไลด์ได้
- คำนวณบิลตัวอย่างได้เองและอธิบาย boundary/rounding ได้
- แยก authentication/authorization และอธิบาย owner scope ได้
- อธิบาย failure case อย่างน้อยห้ากรณีพร้อมผลต่อฐานข้อมูลได้
- เดโมสองรอบติดโดยไม่มีแก้ข้อมูลเฉพาะหน้า และมีหลักฐานทดสอบจาก commit ที่ใช้เดโมจริง
- บอกข้อจำกัดและส่วนงานของตนได้ตามจริง ไม่ตอบว่าใช้ได้ทุกกรณีหรือรองรับทุก DB

## การรันทดสอบประกอบการซ้อม

อ่าน phpunit.xml และ tests/Pest.php เพื่อยืนยันว่าใช้ฐานข้อมูลทดสอบแยกก่อนรัน คำสั่ง composer test ใน source ปัจจุบันรวม config clear, Pint check, PHPStan และ artisan test การรันสำเร็จต้องเก็บผลและ commit ที่ตรงกัน ไม่ใช้เพียงจำนวน test มายืนยันว่าทุก flow UI ผ่าน

ใน VS Code ใช้ breakpoint ที่ Controller และ Action หรือเปิด Network tab ดู request/response เพื่อ walk through หนึ่งกรณีสำเร็จและหนึ่งกรณีล้มเหลว ไม่ต้องเขียนระบบใหม่ทั้งหมดเพื่อเรียนรู้ ระบบที่มีอยู่เป็นตัวอย่างให้ไล่ execution และข้อมูลได้


### อัตราน้ำไฟสำหรับทดลองบน local (13 ตุลาคม 2569)

ตั้งค่า local เป็นน้ำ 10.20 บาท/หน่วย และไฟ 4.42 บาท/หน่วย สำหรับทดสอบและนำเสนอเท่านั้น ไม่ใช่อัตราเรียกเก็บจริงของหอพักในขอนแก่น น้ำอ้างอิงขั้น 0–10 ลบ.ม. ของที่อยู่อาศัยในตารางพื้นที่ทั่วไปของ กปภ.; ไฟปัดจากฐานขั้นเกิน 400 หน่วย 4.4217 บาทของกิจการขนาดเล็ก กฟภ. ระบบปัจจุบันคิดราคาคงที่ ไม่คำนวณขั้นบันได ค่า Ft ภาษี หรือค่าบริการเพิ่มเติม ก่อนเรียกเก็บจริงต้องกำหนดอัตราของหอพักจากข้อมูลที่ยืนยันแล้ว

แหล่งอ้างอิง: https://www.pwa.co.th/contents/service/table-price และ https://www.pea.co.th/sites/default/files/documents/tariff/Electricity_Tariff_2015.pdf

กำหนดผ่าน DORMITORY_WATER_RATE และ DORMITORY_ELEC_RATE ใน .env จากนั้นใช้ php artisan config:clear; ไม่ต้อง build หน้าเว็บใหม่ อัตราในบิลเดิมยังใช้ snapshot ของบิลเดิม เมื่ออัตราว่าง ระบบแจ้งให้ตั้งค่าค่าน้ำหรือค่าไฟก่อนออกบิล


## อัปเดต Rental Lifecycle และ Tenant Account 15 ตุลาคม 2569

ระบบรองรับ SCHEDULED → ACTIVE → ENDED และ SCHEDULED → CANCELLED แยกวันนัดจากวันเข้าจริง Billing เริ่มจากวันเข้าจริงหลังยืนยันและมีมิเตอร์ตรงวัน กำหนดย้ายออกยังไม่ทำให้หยุดคิดบิล การสร้าง Tenant สร้างบัญชีพร้อมกันใน transaction เดียว อ่านกฎ ข้อจำกัด FK และวิธีรวมงานเพิ่มเติมที่ docs/task01-task02-handoff.md


## อัปเดตประวัติส่วนกลางและ Git ล่าสุด 15 ตุลาคม 2569

ฐาน source main 8afe0c7 พร้อมงาน local ปัจจุบัน เก็บ Payment/Repair history ที่ audit_events แห่งเดียว payment_events และ repair_histories เป็น read-only views รวมมี 19 ตารางจริงและ 2 View รักษา legacy ID กับการตรวจ expected_event_id เดิม FK ที่มีจริงต่างจากกฎขั้นต่ำที่ workflow บังคับ อ่านรายละเอียดและผลทดสอบใน docs/central-audit-handoff.md
