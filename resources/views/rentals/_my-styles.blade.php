{{-- resources/views/rentals/_my-styles.blade.php --}}
{{-- สไตล์เพิ่มของหน้าฝั่งผู้เช่า (การเช่าของฉัน / สัญญาของฉัน) — ใช้ตัวแปรสีชุดเดียวกับ rentals._styles --}}
@include('rentals._styles')

@once
<style>
    .my-section-title { font-size: 0.95rem; font-weight: 600; margin: 24px 0 10px; }
    .my-current { display: grid; gap: 16px; }
    .my-current-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; }
    .my-room { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .my-room-name { font-size: 1.6rem; font-weight: 700; line-height: 1.1; margin: 0; }
    .my-room-sub { font-size: 0.85rem; color: var(--rt-muted); margin-top: 6px; }
    .my-rent { text-align: right; }
    .my-rent-label { font-size: 0.75rem; color: var(--rt-muted); }
    .my-rent-value { font-size: 1.4rem; font-weight: 700; color: var(--rt-primary-text); }
    .my-fields { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px 24px; padding: 16px 0; border-top: 1px solid var(--rt-border); border-bottom: 1px solid var(--rt-border); }
    .my-contract-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
    .my-contract-period { font-size: 0.85rem; color: var(--rt-muted); display: inline-flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    /* สัญญาของฉัน: การ์ดกว้างเต็มหน้า หัวการ์ด (เลขสัญญา+สถานะ+ปุ่ม) แล้วตามด้วยช่องข้อมูล 4 คอลัมน์ */
    .my-contracts { display: grid; gap: 16px; }
    .my-contract-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap;
        padding-bottom: 14px; margin-bottom: 16px; border-bottom: 1px solid var(--rt-border); }
    .my-contract-kicker { font-size: 0.75rem; color: var(--rt-muted); }
    .my-contract-no { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; font-size: 1.35rem; font-weight: 700; margin-top: 2px; letter-spacing: 0.02em; }
    .my-contract-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px 24px; }
    .my-big { font-size: 1.15rem; font-weight: 700; color: var(--rt-primary-text); }
    .my-sub { font-size: 0.8rem; color: var(--rt-muted); margin-top: 2px; }
    .my-contract-grid a { color: var(--rt-primary-text); text-decoration: none; }
    .my-contract-grid a:hover { text-decoration: underline; }
    /* แถบความคืบหน้าของสัญญา (เฉพาะสัญญาที่มีวันสิ้นสุด) */
    .my-progress { margin-top: 18px; }
    .my-progress-top { display: flex; justify-content: space-between; gap: 12px; font-size: 0.8rem; color: var(--rt-muted); margin-bottom: 6px; }
    .my-progress-bar { height: 8px; border-radius: 999px; background: var(--rt-row-hover); overflow: hidden; }
    .my-progress-bar > span { display: block; height: 100%; background: var(--rt-primary); border-radius: 999px; }
    .my-progress--off .my-progress-bar > span { background: var(--rt-muted); }
    @media (max-width: 760px) {
        .my-fields { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .my-rent { text-align: left; }
        .my-contract-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 420px) {
        .my-contract-grid { grid-template-columns: minmax(0, 1fr); }
    }
</style>
@endonce
