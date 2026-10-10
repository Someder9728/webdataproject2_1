{{-- resources/views/rentals/_styles.blade.php --}}
{{-- สไตล์กลางของหน้า R1-R4 (ใช้ prefix .rt- เพื่อไม่ชนกับ class ของ Flux/หน้าอื่น) --}}
{{-- ใช้ใน index / show / contract / create ด้วย @include('rentals._styles') — include ซ้ำได้ ไม่เสียหาย --}}
@once
<style>
    .rt {
        --rt-card: #171717;
        --rt-card-soft: #1f1f1f;
        --rt-border: #404040;
        --rt-text: #f5f5f5;
        --rt-muted: #a3a3a3;
        --rt-primary: #2563eb;
        --rt-primary-hover: #1d4ed8;
        --rt-primary-text: #93c5fd;
        --rt-danger: #dc2626;
        --rt-danger-hover: #b91c1c;
        --rt-row-hover: rgba(255, 255, 255, 0.04);
        --rt-ok-bg: rgba(34, 197, 94, 0.15);   --rt-ok-fg: #4ade80;   --rt-ok-bd: rgba(34, 197, 94, 0.35);
        --rt-off-bg: rgba(163, 163, 163, 0.15); --rt-off-fg: #d4d4d4; --rt-off-bd: rgba(163, 163, 163, 0.35);
        --rt-warn-bg: rgba(250, 204, 21, 0.12); --rt-warn-fg: #facc15; --rt-warn-bd: rgba(250, 204, 21, 0.35);
        --rt-info-bg: rgba(59, 130, 246, 0.12); --rt-info-fg: #93c5fd; --rt-info-bd: rgba(59, 130, 246, 0.35);
        --rt-err-bg: rgba(248, 113, 113, 0.12); --rt-err-fg: #f87171; --rt-err-bd: rgba(248, 113, 113, 0.35);
        color: var(--rt-text);
    }
    .rt, .rt *, .rt *::before, .rt *::after { box-sizing: border-box; }
    .rt [hidden] { display: none !important; } /* กัน class ที่ตั้ง display ทับ attribute hidden */
    html:not(.dark) .rt {
        --rt-card: #ffffff;
        --rt-card-soft: #f9fafb;
        --rt-border: #e5e7eb;
        --rt-text: #111827;
        --rt-muted: #6b7280;
        --rt-primary-text: #1d4ed8;
        --rt-row-hover: rgba(0, 0, 0, 0.03);
        --rt-ok-bg: #dcfce7;   --rt-ok-fg: #166534;   --rt-ok-bd: #bbf7d0;
        --rt-off-bg: #f3f4f6;  --rt-off-fg: #4b5563;  --rt-off-bd: #e5e7eb;
        --rt-warn-bg: #fef9c3; --rt-warn-fg: #854d0e; --rt-warn-bd: #fde68a;
        --rt-info-bg: #eff6ff; --rt-info-fg: #1d4ed8; --rt-info-bd: #bfdbfe;
        --rt-err-bg: #fef2f2;  --rt-err-fg: #b91c1c;  --rt-err-bd: #fecaca;
    }

    /* ---------- โครงหน้า ---------- */
    .rt-page { max-width: 1200px; margin: 0 auto; padding: 24px 16px 48px; }
    .rt-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
    .rt-crumb { font-size: 0.75rem; color: var(--rt-muted); margin-bottom: 4px; }
    .rt-crumb a { color: inherit; text-decoration: none; }
    .rt-crumb a:hover { text-decoration: underline; }
    .rt-title { font-size: 1.5rem; font-weight: 700; line-height: 1.3; margin: 0; }
    .rt-subtitle { color: var(--rt-muted); font-size: 0.9rem; margin-top: 4px; }
    .rt-head-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }

    /* ---------- การ์ด ---------- */
    .rt-card { background: var(--rt-card); border: 1px solid var(--rt-border); border-radius: 12px; padding: 16px 20px; }
    .rt-stack { display: flex; flex-direction: column; gap: 16px; }
    .rt-card--flush { padding: 0; overflow: hidden; }
    .rt-card-head { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 12px; }
    .rt-card-title { font-size: 0.75rem; font-weight: 600; color: var(--rt-muted); margin: 0; }
    .rt-card-title--lg { font-size: 1rem; color: var(--rt-text); }
    .rt-card--flush .rt-card-head { padding: 16px 20px; margin: 0; border-bottom: 1px solid var(--rt-border); }

    /* ---------- ปุ่ม ---------- */
    .rt-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-size: 0.875rem; font-weight: 500; line-height: 1.2; text-decoration: none; cursor: pointer; border: 1px solid var(--rt-border); background: transparent; color: var(--rt-text); transition: background .15s, border-color .15s; }
    .rt-btn:hover:not(:disabled) { background: var(--rt-row-hover); }
    .rt-btn:disabled, .rt-btn[aria-disabled="true"] { opacity: 0.45; cursor: not-allowed; }
    .rt-btn--primary { background: var(--rt-primary); border-color: var(--rt-primary); color: #fff; }
    .rt-btn--primary:hover:not(:disabled) { background: var(--rt-primary-hover); border-color: var(--rt-primary-hover); }
    .rt-btn--danger { background: var(--rt-danger); border-color: var(--rt-danger); color: #fff; }
    .rt-btn--danger:hover:not(:disabled) { background: var(--rt-danger-hover); border-color: var(--rt-danger-hover); }
    .rt-btn--sm { padding: 4px 10px; font-size: 0.75rem; border-radius: 6px; }
    .rt-btn--soft { background: var(--rt-row-hover); }

    /* ---------- ป้าย (badge / chip) ---------- */
    .rt-badge { display: inline-block; padding: 2px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 500; border: 1px solid transparent; white-space: nowrap; }
    .rt-badge--ok   { background: var(--rt-ok-bg);   color: var(--rt-ok-fg);   border-color: var(--rt-ok-bd); }
    .rt-badge--off  { background: var(--rt-off-bg);  color: var(--rt-off-fg);  border-color: var(--rt-off-bd); }
    .rt-badge--warn { background: var(--rt-warn-bg); color: var(--rt-warn-fg); border-color: var(--rt-warn-bd); }
    .rt-badge--info { background: var(--rt-info-bg); color: var(--rt-info-fg); border-color: var(--rt-info-bd); }

    /* ---------- กล่องสถานะ / แจ้งเตือน ---------- */
    .rt-state { padding: 28px 16px; text-align: center; color: var(--rt-muted); font-size: 0.9rem; }
    .rt-state--error { color: var(--rt-err-fg); background: var(--rt-err-bg); border: 1px solid var(--rt-err-bd); border-radius: 12px; }
    .rt-state--error .rt-btn { margin-left: 8px; }
    .rt-notice { padding: 10px 14px; border-radius: 8px; margin: 0 0 16px; font-size: 0.875rem; border: 1px solid transparent; }
    .rt-notice--ok   { background: var(--rt-ok-bg);   color: var(--rt-ok-fg);   border-color: var(--rt-ok-bd); }
    .rt-notice--warn { background: var(--rt-warn-bg); color: var(--rt-warn-fg); border-color: var(--rt-warn-bd); }
    .rt-notice--info { background: var(--rt-info-bg); color: var(--rt-info-fg); border-color: var(--rt-info-bd); }
    .rt-notice--err  { background: var(--rt-err-bg);  color: var(--rt-err-fg);  border-color: var(--rt-err-bd); }

    /* ---------- ตาราง ---------- */
    .rt-table-wrap { overflow-x: auto; }
    .rt-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; min-width: 760px; }
    .rt-table th { text-align: left; font-size: 0.75rem; font-weight: 500; color: var(--rt-muted); padding: 10px 20px; border-bottom: 1px solid var(--rt-border); white-space: nowrap; }
    .rt-table td { padding: 12px 20px; border-bottom: 1px solid var(--rt-border); vertical-align: middle; }
    .rt-table tbody tr:last-child td { border-bottom: none; }
    .rt-table tbody tr:hover { background: var(--rt-row-hover); }
    .rt-table td.rt-actions { text-align: right; white-space: nowrap; }
    .rt-table td.rt-actions > * + * { margin-left: 6px; }
    .rt-muted { color: var(--rt-muted); }
    .rt-person { display: flex; align-items: center; gap: 10px; }
    .rt-avatar { width: 24px; height: 24px; border-radius: 999px; background: var(--rt-info-bg); color: var(--rt-info-fg); border: 1px solid var(--rt-info-bd); display: inline-flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700; flex: none; }
    .rt-avatar--lg { width: 40px; height: 40px; font-size: 1rem; }
    .rt-link { color: var(--rt-primary-text); text-decoration: none; }
    .rt-link:hover { text-decoration: underline; }

    /* ---------- ช่องค้นหา ---------- */
    .rt-search { width: 100%; max-width: 360px; padding: 8px 12px; border: 1px solid var(--rt-border); border-radius: 8px; background: var(--rt-card); color: var(--rt-text); font-size: 0.875rem; }
    .rt-search:focus { outline: 2px solid var(--rt-primary); outline-offset: -1px; }
    .rt-toolbar { margin-bottom: 16px; }
    .rt-pagination { display: flex; align-items: center; justify-content: center; gap: 12px; margin-top: 16px; font-size: 0.875rem; color: var(--rt-muted); }

    /* ---------- รายการ label/ค่า ---------- */
    .rt-kv { display: grid; gap: 10px; margin: 0; }
    .rt-kv > div { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; font-size: 0.875rem; }
    .rt-kv dt { color: var(--rt-muted); font-size: 0.8rem; margin: 0; }
    .rt-kv dd { margin: 0; text-align: right; word-break: break-word; }
    .rt-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px 24px; margin: 0; }
    .rt-fields--3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .rt-field-label { display: block; font-size: 0.75rem; color: var(--rt-muted); margin-bottom: 2px; }
    .rt-field-value { font-weight: 600; font-size: 0.95rem; }
    .rt-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
    .rt-money { color: var(--rt-primary-text); font-weight: 600; }
    .rt-big { font-size: 2rem; font-weight: 700; line-height: 1.1; margin: 0 0 12px; }

    /* ---------- เลย์เอาต์ 2 คอลัมน์ (R3) ---------- */
    .rt-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.7fr); gap: 16px; align-items: start; }
    .rt-col { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
    @media (max-width: 900px) {
        .rt-grid { grid-template-columns: minmax(0, 1fr); }
        .rt-fields--3 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 520px) {
        .rt-fields, .rt-fields--3 { grid-template-columns: minmax(0, 1fr); }
    }

    /* ---------- ฟอร์ม ---------- */
    .rt-form-field { margin-bottom: 16px; }
    .rt-form-field label, .rt-label { display: block; margin-bottom: 6px; font-size: 0.875rem; font-weight: 500; }
    .rt-req { color: var(--rt-err-fg); }
    .rt-input { width: 100%; padding: 9px 12px; border: 1px solid var(--rt-border); border-radius: 8px; background: var(--rt-card); color: var(--rt-text); font-size: 0.9rem; font-family: inherit; }
    .rt-input:focus { outline: 2px solid var(--rt-primary); outline-offset: -1px; }
    .rt-input:disabled { opacity: 0.6; cursor: not-allowed; }
    textarea.rt-input { min-height: 84px; resize: vertical; }
    .rt-hint { display: block; font-size: 0.78rem; color: var(--rt-muted); margin-top: 4px; }
    .rt-field-error { display: block; font-size: 0.82rem; color: var(--rt-err-fg); margin-top: 4px; }
    .rt-form-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    @media (max-width: 520px) { .rt-form-row { grid-template-columns: minmax(0, 1fr); } }
    .rt-form-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; }
    .rt-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .rt-chip { padding: 3px 10px; font-size: 0.78rem; border-radius: 999px; border: 1px solid var(--rt-border); background: transparent; color: var(--rt-text); cursor: pointer; }
    .rt-chip:hover { background: var(--rt-row-hover); }

    /* ---------- ไทม์ไลน์ (R3) ---------- */
    .rt-timeline { list-style: none; margin: 0; padding: 4px 0 0; }
    .rt-timeline > li { position: relative; display: flex; gap: 14px; padding-bottom: 18px; }
    .rt-timeline > li:last-child { padding-bottom: 0; }
    .rt-timeline > li:not(:last-child)::before { content: ''; position: absolute; left: 13px; top: 28px; bottom: 0; width: 1px; background: var(--rt-border); }
    .rt-dot { width: 28px; height: 28px; border-radius: 999px; background: var(--rt-card-soft); border: 1px solid var(--rt-border); display: inline-flex; align-items: center; justify-content: center; flex: none; }
    .rt-dot::after { content: ''; width: 8px; height: 8px; border-radius: 999px; background: var(--rt-primary-text); }
    .rt-dot--end::after { background: var(--rt-err-fg); }
    .rt-dot--ok::after { background: var(--rt-ok-fg); }
    .rt-tl-date { font-size: 0.75rem; color: var(--rt-muted); font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
    .rt-tl-title { font-weight: 600; font-size: 0.9rem; margin-left: 6px; }
    .rt-tl-desc { font-size: 0.8rem; color: var(--rt-muted); margin-top: 2px; }
    .rt-tl-reason { font-size: 0.8rem; margin-top: 2px; }
    .rt-dot--edit::after { background: var(--rt-warn-fg); }
    .rt-tl-note { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 14px; font-size: 0.8rem; color: var(--rt-muted); }

    /* ---------- กล่องฟอร์มสร้างการเช่า (R2) ---------- */
    .rt-modal { max-width: 520px; margin: 16px auto 0; background: var(--rt-card); border: 1px solid var(--rt-border); border-radius: 16px; padding: 24px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25); }
    .rt-modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
    .rt-modal-title { font-size: 1.15rem; font-weight: 700; margin: 0; }
    .rt-close { color: var(--rt-muted); text-decoration: none; font-size: 1.3rem; line-height: 1; padding: 2px 8px; border-radius: 6px; }
    .rt-close:hover { background: var(--rt-row-hover); }
    .rt-autofill { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; padding: 14px; border: 1px solid var(--rt-border); border-radius: 12px; background: var(--rt-card-soft); margin-bottom: 16px; }
    .rt-autofill-label { font-size: 0.75rem; color: var(--rt-muted); margin-bottom: 4px; }
    .rt-autofill-value { font-weight: 600; }
    .rt-autofill-sub { font-size: 0.8rem; color: var(--rt-muted); }
</style>
@endonce
