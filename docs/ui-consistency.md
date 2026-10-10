# ปรับ UI ให้ใช้ CSS เดิมร่วมกัน — 10 ตุลาคม 2026

## เปลี่ยนอะไร

- ย้าย style เดิมจากหน้าผู้เช่าไป `resources/views/partials/admin-page-style.blade.php` โดยไม่เปลี่ยน CSS declarations และให้หน้าผู้เช่าเรียก partial นี้
- หน้าสัญญาเช่า/การเช่าที่ใช้ view ร่วมกัน นำ page/title/table/search/button classes เดิมมาใช้
- หน้ามิเตอร์และแจ้งซ่อมนำการ์ด ตาราง ปุ่ม หัวหน้าและคำอธิบายจากหน้าผู้เช่ามาใช้ รวม Bootstrap form/control/responsive utilities ที่โปรเจกต์โหลดอยู่แล้ว
- ถอด bespoke CSS `.maintenance` เดิมออก คง loader ของ maintenance.js และ cache version เดิม
- ปุ่มจาก JS ใช้ tenant-edit-btn/tenant-delete-btn เดิม สถานะงานซ่อมใช้ Bootstrap badges ที่มีอยู่แล้ว
- เพิ่ม active menu ให้มิเตอร์/แจ้งซ่อมตาม route เดียวกับเมนูอื่น
- คง IDs, names, hidden, API payload, CSRF และ handlers ของฟอร์ม ใช้ DOM paragraphs แสดงบรรทัดคำอธิบายงานซ่อมแทน inline style เดิม
- ไม่เพิ่มสีใหม่หรือ CSS rules ใหม่ ไม่แก้ Backend/schema/business rules

## ตรวจแล้ว

- ตรวจสไตล์ต้นฉบับ Dashboard/ผู้เช่า/ห้องพักก่อนแก้
- CSS partial ตรงกับ style block เดิมหน้าผู้เช่า
- Browser ด้วยข้อมูล DEMO: เปิดหน้าสัญญา/รายละเอียดสัญญา, เลือกห้องมิเตอร์/เปิดและปิดฟอร์มแก้ไข, โหลดงานซ่อม/เปิดประวัติ ผ่าน ไม่ทดสอบส่ง mutation ทุกกรณีใน browser
- Regression 578 tests: 559 passed, 19 skipped, 2,577 assertions
- Pint, Blade compilation และ JavaScript syntax ผ่าน
- ยังไม่ได้ตรวจทุก viewport และทุกสถานะ error ใน browser

## ประเด็นเดิมที่พบระหว่างตรวจ

หน้ารายการสัญญาแสดงวันสิ้นสุดเป็นไม่มีกำหนดเมื่อ API rental list ส่ง contract summary ที่ไม่มี c_end แต่รายละเอียดสัญญามีวันจริง ประเด็นนี้มีอยู่ก่อนแพตช์ CSS และยังไม่ได้แก้ในงานนี้

## ติดตั้งและส่งต่อ

ฐาน `feature/meter-model-v2` commit `a3966f0f5098d28757e1a6602bef8fc3ce6c40b2`
installer สร้าง `feature/ui-consistency` ตรวจฐาน source ลงแพตช์ build/test แล้ว commit และ push เมื่อผ่าน
ตรวจภาพและลองใช้งานก่อนรวมเข้า integration ไม่มี migration หรือการแก้ฐานข้อมูลจริง
