# ปรับฝั่งผู้เช่า — 10 ตุลาคม 2026

## สิ่งที่แก้

- ตัดเมนูซ้ำและลิงก์ placeholder ให้เหลือการเช่า, สัญญา, ใบแจ้งหนี้/ค่าน้ำค่าไฟ, ประวัติชำระ, แจ้งซ่อม และข้อมูลส่วนตัว พร้อม active route ที่ถูกต้อง
- รวมค่าน้ำค่าไฟไว้ใน Invoice ผู้เช่าไม่สามารถเข้าหน้าจดมิเตอร์ของ Admin ได้ตามสิทธิ์เดิม
- หน้าการเช่า/สัญญาโหลดข้อมูลที่เป็นของผู้เช่าปัจจุบันจาก Controller โดยตรง แบ่งหน้าประวัติและสัญญา แก้ปัญหาเดิมที่อ่านเพียง 100 รายการและกลืน API errors เป็นข้อมูลว่าง
- หน้ารายละเอียดสำหรับ Tenant ตรวจ RentalPolicy ก่อนแสดง แสดงข้อมูลผู้เช่า ห้อง และสัญญาจริง ไม่เรียก API ห้อง/ผู้เช่าที่สงวนให้ Admin
- เพิ่ม /my/profile แสดงข้อมูลของตัวเอง ลิงก์ไปหน้าเปลี่ยนรหัสผ่านเดิม ไม่มีฟอร์มแก้ข้อมูลผู้เช่าหรือสิทธิ์ใหม่
- หน้าเปลี่ยนรหัสผ่าน Tenant ใช้ Bootstrap form และ CSS partial เดียวกับ Admin ตัดหัวข้อ Settings ซ้ำเฉพาะ Tenant; action เปลี่ยนรหัสผ่าน, validation, logout และ session invalidation เดิมไม่เปลี่ยน
- ใช้ partial admin-page-style เดิม ไม่มี CSS declarations หรือสีใหม่ หน้า Invoice/Payment ใช้ view และ CSS ชุดเดียวกับ Admin อยู่แล้ว หน้า Repair ใช้ชุดร่วมที่ปรับก่อนหน้านี้
- คง legacy URL /user/dashboard ที่ redirect ไป /my/rentals ไม่แสดงเมนู Dashboard ที่ซ้ำกับการเช่า
- Admin ยังคงหน้ารายละเอียด/ฟอร์มจัดการเดิม

## ตรวจแล้ว

- Regression 582 tests: 563 passed, 19 skipped, 2,620 assertions
- PHPStan 0 errors, Pint และ Blade compilation ผ่าน
- Tests ใหม่ 4 cases: owned data, foreign Rental 404, เมนูจริงและไม่ซ้ำ, tenant ห้ามแก้สัญญา/เข้าหน้าจัดการ Admin, pagination, ไม่มี Tenant link, หน้า security และ forced password change
- Browser demo.tenant1: หน้าเช่า/รายละเอียด/สัญญา/Invoice/ประวัติชำระ/Repair/Profile/Security เปิดได้; เห็นเฉพาะห้องและบิลตนเอง; เปิดและยกเลิกฟอร์มส่งหลักฐานได้
- ไม่ส่งสลิปหรือเปลี่ยนรหัสผ่านจริงผ่าน browser และไม่ได้ทดสอบทุก viewport/ทุก failure state

## ส่งต่อ

ฐาน feature/ui-consistency commit 685392e4a68d0f6f1e8c3ccbe26465dc021ca87d
installer สร้าง feature/tenant-portal-fix ลง patch และตรวจ build/tests ก่อน commit/push
ไม่มี migration ไม่มี seed/reset ฐานข้อมูลใน installer และยังไม่แก้ VS Code ของผู้ใช้จนกว่าจะรัน

หลังติดตั้งให้ลองบัญชีผู้เช่าที่มีบิลชำระแล้ว บัญชีที่ย้ายออก และบัญชีที่ต้องเปลี่ยนรหัสผ่าน จากนั้น review และรวม branch นี้เข้า integration
