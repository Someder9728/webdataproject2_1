# Contract Timeline API — 9 October 2026

## Scope

เพิ่ม Backend สำหรับ Timeline การแก้/ต่อสัญญา โดยอ่าน Audit ที่มีอยู่ ไม่เพิ่มตาราง/migration และไม่เปลี่ยน Contract update หรือ UI ของ Big
ใช้ฐาน integration/big-ui commit 4e374f0 ที่รวม Tenant pages ล่าสุดแล้ว
สิทธิ์รอบนี้: Admin active และเปลี่ยนรหัสผ่านแล้วเท่านั้น ผู้เช่ารวมเจ้าของ Rental ได้ 403

## Endpoint

`GET /api/v1/rentals/{rental}/history?page=1&per_page=20`

- session authentication แบบเดียวกับ API เดิม
- page ตั้งแต่ 1, per_page 1–100, ค่าเริ่มต้น 20
- กรอง entity_id จาก Contract ที่ผูกกับ Rental ใน URL เท่านั้น ไม่รับ entity_type/entity_id จาก client เพื่อเลือกข้อมูลอื่น
- อ่าน action contract_updated และ legacy CONTRACT_EXTENDED, รองรับ entity_type contracts/contract
- เรียงใหม่ไปเก่าด้วย created_at แล้ว ae_id; Admin อ่าน Rental ENDED ได้
- Contract soft delete ยังอ่าน audit ได้ แต่ Rental ที่ลบแล้วหรือไม่พบเป็น 404
- ถ้าไม่มีประวัติคืน data=[] และ total=0
- ไม่คืน Audit ของ Account/Payment หรือค่าที่นอก whitelist

```json
{
  "data": [{
    "ae_id": 123,
    "entity_type": "contracts",
    "entity_id": 45,
    "action": "contract_updated",
    "created_at": "2026-10-09T10:30:00+07:00",
    "actor": {"u_id": 1, "u_username": "admin"},
    "reason": "ผู้เช่าขอต่อสัญญา",
    "old_values": {"c_end": "2026-12-01", "c_status": "ACTIVE"},
    "new_values": {"c_end": "2027-06-01", "c_status": "ACTIVE"}
  }],
  "meta": {"current_page": 1, "per_page": 20, "total": 1, "last_page": 1},
  "message": "อ่านประวัติการแก้ไขสัญญาสำเร็จ"
}
```

เวลาเป็น ISO 8601 Asia/Bangkok ผู้แก้คืนเฉพาะ u_id/u_username ไม่มี email/password/session token
actor อาจเป็น null เมื่อไม่มีผู้ใช้ที่อ่านได้ เหตุผลอาจเป็น null ในข้อมูลเก่า ค่า snapshots คืนเฉพาะ c_end/c_status และอาจไม่มีบาง fields ในข้อมูลเก่า

## Handoff ให้ Big

1. เฉพาะหน้า Admin โหลด history จาก Rental ID หลังโหลดรายละเอียด Rental สำเร็จ
2. เพิ่มเหตุการณ์แก้/ต่อสัญญาใน buildTimeline ด้วย ae_id เป็น key, created_at เป็นเวลา, actor.u_username เป็นผู้แก้, reason และ before/after c_end/c_status
3. ถ้า actor=null แสดงว่าไม่พบข้อมูลผู้ดำเนินการ ถ้า reason=null แสดงว่าไม่ได้ระบุ อย่าอนุมานชื่อผู้แก้จาก Admin ที่ login ปัจจุบัน
4. อ่าน pagination ตาม meta จนได้รายการที่ต้องการ ไม่ถือว่า 20 รายการแรกคือประวัติทั้งหมด
5. เมื่อ Contract PATCH สำเร็จ reload history เพราะ Audit ใหม่อยู่ใน transaction เดียวกับการแก้ไข หลีกเลี่ยงการเติม event จำลองที่อาจซ้ำกับ API
6. Timeline Tenant คงข้อมูลเดิมไว้ ไม่เรียก endpoint นี้ เพราะรอบนี้ Admin-only
7. แสดงชื่อ/เหตุผลด้วย textContent หรือ escaping ไม่ใช้ innerHTML กับ Audit text

## ไฟล์ที่เพิ่ม/แก้

- app/Http/Controllers/Api/V1/RentalHistoryController.php — query, pagination และ response whitelist
- app/Policies/RentalPolicy.php — viewHistory policy เฉพาะ Admin
- routes/api-session.php — GET history ภายใต้ auth/password.changed/role:admin
- tests/Feature/RentalHistoryApiTest.php — 8 cases ของ real Contract Audit, scope isolation, legacy alias, ENDED/soft-delete, paging, permissions และ validation
- docs/contract-timeline-api.md — API contract สำหรับ Big

## สถานะส่งมอบ

พัฒนาใน checkout แยกและเตรียมสคริปต์ติดตั้งสำหรับ VS Code ยังไม่ได้เปลี่ยน source หรือ commit/push ใน E:\Herd\webdataproject2_1
สคริปต์จะเริ่มจาก remote integration/big-ui ล่าสุดที่ตรวจไว้ สร้าง feature/contract-timeline-api ลงการเปลี่ยนแปลง ตรวจ tests/type/format/build แล้ว commit/push หากทุกขั้นผ่าน
ไม่รวม branch Frame และยังไม่ได้เชื่อม Timeline UI หรือทดสอบ browser end-to-end
