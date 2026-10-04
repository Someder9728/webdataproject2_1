{{-- resources/views/invoices/_base.blade.php --}}
{{-- ฐานร่วมของหน้าใบแจ้งหนี้/การชำระ: สไตล์ (.iv-*) + ตัวช่วย JS (window.IV) --}}
{{-- ใช้สไตล์กลาง .rt-* ร่วมกับหน้า "การเช่า" และไม่ใส่ข้อมูลจาก API ลง innerHTML (สร้าง DOM ด้วย IV.el / textContent) --}}
@include('rentals._styles')

@once
<style>
    /* ---------- ป้ายสถานะเพิ่มเติม ---------- */
    .rt-badge--err { background: var(--rt-err-bg); color: var(--rt-err-fg); border-color: var(--rt-err-bd); }

    /* ---------- การ์ดสรุปยอด ---------- */
    .iv-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 20px; }
    @media (max-width: 900px) { .iv-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 480px) { .iv-stats { grid-template-columns: minmax(0, 1fr); } }
    .iv-stat { display: flex; align-items: center; gap: 14px; background: var(--rt-card); border: 1px solid var(--rt-border); border-radius: 12px; padding: 16px 18px; min-width: 0; }
    .iv-stat-icon { width: 44px; height: 44px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; flex: none; color: #fff; }
    .iv-stat-icon svg { width: 22px; height: 22px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .iv-stat-icon--total { background: #334155; }
    .iv-stat-icon--ok { background: #16a34a; }
    .iv-stat-icon--warn { background: #d97706; }
    .iv-stat-icon--err { background: #dc2626; }
    .iv-stat-label { font-size: 0.78rem; color: var(--rt-muted); }
    .iv-stat-value { font-size: 1.5rem; font-weight: 700; line-height: 1.2; word-break: break-word; }
    .iv-stat-sub { font-size: 0.72rem; color: var(--rt-muted); margin-top: 2px; }

    /* ---------- แถบค้นหา/กรอง ---------- */
    .iv-toolbar { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; padding: 16px 20px; border-bottom: 1px solid var(--rt-border); }
    .iv-toolbar .rt-search { flex: 1 1 240px; max-width: 360px; }
    .iv-toolbar .rt-input { width: auto; min-width: 150px; }

    /* ---------- ตาราง ---------- */
    .iv-table { min-width: 980px; }
    .iv-table td.num, .iv-table th.num { text-align: right; white-space: nowrap; }
    .iv-room { font-weight: 700; }
    .iv-due--late { color: var(--rt-err-fg); font-weight: 600; }
    .iv-icon-btn { display: inline-flex; width: 30px; height: 30px; align-items: center; justify-content: center; border-radius: 6px; color: var(--rt-muted); border: none; background: none; cursor: pointer; text-decoration: none; vertical-align: middle; }
    .iv-icon-btn:hover { background: var(--rt-row-hover); color: var(--rt-text); }
    .iv-icon-btn svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .rt-btn--pay { background: var(--rt-ok-bg); color: var(--rt-ok-fg); border-color: var(--rt-ok-bd); }
    .rt-btn--pay:hover:not(:disabled) { background: var(--rt-ok-bg); filter: brightness(1.15); }

    /* ---------- รายละเอียดใบแจ้งหนี้ ---------- */
    .iv-bill { border: 1px solid var(--rt-border); border-radius: 12px; overflow: hidden; margin-top: 18px; }
    .iv-bill-head { padding: 10px 16px; font-size: 0.8rem; font-weight: 600; color: var(--rt-muted); background: var(--rt-card-soft); border-bottom: 1px solid var(--rt-border); }
    .iv-bill-row { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; padding: 12px 16px; border-bottom: 1px solid var(--rt-border); font-size: 0.9rem; }
    .iv-bill-row small { display: block; color: var(--rt-muted); font-size: 0.75rem; margin-top: 2px; }
    .iv-bill-row b { font-variant-numeric: tabular-nums; white-space: nowrap; }
    .iv-bill-total { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 16px; background: #0f172a; color: #fff; }
    .iv-bill-total b { font-size: 1.5rem; font-variant-numeric: tabular-nums; }
    .iv-grid { grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr); }
    @media (max-width: 900px) { .iv-grid { grid-template-columns: minmax(0, 1fr); } }

    /* ---------- กล่องโต้ตอบ (dialog) ---------- */
    .iv-dialog { width: min(540px, 94vw); max-height: 92vh; padding: 0; border: 1px solid var(--rt-border); border-radius: 16px; background: var(--rt-card); color: var(--rt-text); box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45); overflow: auto; }
    .iv-dialog::backdrop { background: rgba(0, 0, 0, 0.6); }
    .iv-dialog-body { padding: 22px 24px; }
    .iv-x { background: none; border: none; cursor: pointer; color: var(--rt-muted); font-size: 1.2rem; line-height: 1; padding: 4px 8px; border-radius: 6px; }
    .iv-x:hover { background: var(--rt-row-hover); }
    .iv-summary { padding: 12px 14px; border: 1px solid var(--rt-border); border-radius: 12px; background: var(--rt-card-soft); margin-bottom: 16px; }
    .iv-summary dl { margin: 0; display: grid; gap: 6px; }
    .iv-summary dl > div { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; font-size: 0.875rem; }
    .iv-summary dt { color: var(--rt-muted); }
    .iv-summary dd { margin: 0; text-align: right; font-weight: 600; word-break: break-word; }
    .iv-summary .iv-sum-big { font-size: 1.25rem; }
    .iv-proof { border: 1px dashed var(--rt-border); border-radius: 12px; padding: 12px; text-align: center; margin-bottom: 16px; background: var(--rt-card-soft); }
    .iv-proof img { max-width: 100%; max-height: 360px; border-radius: 8px; display: block; margin: 0 auto; }
    .iv-readonly { background: var(--rt-card-soft); }
    .iv-hint-box { font-size: 0.8rem; color: var(--rt-muted); margin: 0 0 14px; line-height: 1.6; }
    .iv-steps-line { margin: 0 0 10px; font-size: 0.8rem; color: var(--rt-muted); }

    /* ---------- toast ---------- */
    .iv-toast-wrap { position: fixed; right: 16px; bottom: 16px; display: flex; flex-direction: column; gap: 8px; z-index: 9999; max-width: min(420px, calc(100vw - 32px)); }
    .iv-toast-wrap .rt-notice { margin: 0; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3); }
</style>

<script>
window.IV = (function () {
    'use strict';

    const $ = (id) => document.getElementById(id);

    /* ---------- สร้าง DOM แบบปลอดภัย (ข้อความทั้งหมดเข้าทาง textContent) ---------- */
    function el(tag, attrs, ...kids) {
        const n = document.createElement(tag);
        if (attrs) {
            for (const [k, v] of Object.entries(attrs)) {
                if (v === null || v === undefined || v === false) continue;
                if (k === 'class') n.className = v;
                else if (k === 'text') n.textContent = v;
                else if (k === 'dataset') Object.assign(n.dataset, v);
                else if (k.startsWith('on') && typeof v === 'function') n.addEventListener(k.slice(2), v);
                else n.setAttribute(k, v === true ? '' : v);
            }
        }
        (function add(list) {
            for (const c of list) {
                if (c === null || c === undefined || c === false) continue;
                if (Array.isArray(c)) add(c);
                else n.appendChild(c.nodeType ? c : document.createTextNode(String(c)));
            }
        })(kids);
        return n;
    }

    /* ไอคอน: ค่าคงที่ในโค้ดเท่านั้น ไม่มีข้อมูลจากผู้ใช้/API */
    const ICONS = {
        card: '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
        check: '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
        clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        alert: '<path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>',
        eye: '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
    };
    function icon(name) {
        const holder = document.createElement('span');
        holder.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' + (ICONS[name] || '') + '</svg>';
        return holder.firstChild;
    }

    /* ---------- รูปแบบแสดงผล ---------- */
    const day = (s) => String(s ?? '').slice(0, 10);
    const today = () => new Date().toLocaleDateString('sv-SE', { timeZone: 'Asia/Bangkok' });

    function money(n) {
        const v = Number(n);
        if (n === null || n === undefined || n === '' || Number.isNaN(v)) return '-';
        return '฿' + v.toLocaleString('th-TH', {
            minimumFractionDigits: Number.isInteger(v) ? 0 : 2,
            maximumFractionDigits: 2,
        });
    }
    function num(n) {
        const v = Number(n);
        if (n === null || n === undefined || n === '' || Number.isNaN(v)) return '-';
        return v.toLocaleString('th-TH', { maximumFractionDigits: 2 });
    }
    function thaiMonth(key) { // '2026-07' → 'กรกฎาคม 2569'
        const m = /^(\d{4})-(\d{2})$/.exec(String(key ?? ''));
        if (!m) return '';
        return new Date(Number(m[1]), Number(m[2]) - 1, 1)
            .toLocaleDateString('th-TH', { month: 'long', year: 'numeric' });
    }
    function dateTime(iso) {
        const d = new Date(iso);
        if (!iso || Number.isNaN(d.getTime())) return '-';
        return d.toLocaleString('sv-SE', { timeZone: 'Asia/Bangkok' }).slice(0, 16);
    }

    /* เลขใบแจ้งหนี้สำหรับแสดงผล: backend ยังไม่มีเลขเอกสาร จึงประกอบจากปีที่ออกบิลกับ i_id (ไม่ได้เก็บในฐานข้อมูล) */
    function invNo(inv) {
        const year = day(inv?.i_date || inv?.period_start).slice(0, 4) || '----';
        return 'INV-' + year + '-' + String(inv?.i_id ?? '').padStart(4, '0');
    }
    const tenantName = (inv) => inv?.tenant ? `${inv.tenant.t_Fname ?? ''} ${inv.tenant.t_Lname ?? ''}`.trim() || '-' : '-';
    const roomName = (inv) => inv?.room?.r_name ?? '-';
    const monthKey = (inv) => day(inv?.period_start).slice(0, 7);

    /* ---------- สถานะ ---------- */
    const STATUS = {
        paid: { label: 'ชำระแล้ว', cls: 'rt-badge--ok' },
        pending: { label: 'รอตรวจสอบ', cls: 'rt-badge--info' },
        rejected: { label: 'ถูกปฏิเสธ', cls: 'rt-badge--err' },
        overdue: { label: 'ค้างชำระ', cls: 'rt-badge--err' },
        unpaid: { label: 'รอชำระ', cls: 'rt-badge--warn' },
        none: { label: 'ไม่มีข้อมูลชำระ', cls: 'rt-badge--off' },
    };
    function isPastDue(inv) {
        return !!inv?.i_due && day(inv.i_due) < today();
    }
    function statusKey(inv) {
        const s = inv?.payment?.p_status;
        if (s === 'PAID') return 'paid';
        if (s === 'PENDING') return 'pending';
        if (s === 'REJECTED') return 'rejected';
        if (s === 'UNPAID') return isPastDue(inv) ? 'overdue' : 'unpaid';
        return 'none';
    }
    function badge(inv) {
        const s = STATUS[statusKey(inv)];
        return el('span', { class: 'rt-badge ' + s.cls, text: s.label });
    }
    /* แบ่งยอดเป็น 3 กลุ่มที่รวมกันได้ยอดทั้งหมดพอดี: ชำระแล้ว / ค้างชำระ (UNPAID เกินกำหนด) / รอชำระ (ที่เหลือ) */
    function bucket(inv) {
        const k = statusKey(inv);
        if (k === 'paid') return 'paid';
        if (k === 'overdue') return 'overdue';
        return 'waiting';
    }
    const METHOD = { TRANSFER: 'โอนเงิน', CASH: 'เงินสด' };
    const methodLabel = (m) => METHOD[m] ?? (m || '-');

    /* ใบนี้ "ยังไม่มีการดำเนินการชำระ" ใช่ไหม (ตรงกับเงื่อนไขที่ backend อนุญาตให้แก้ใบแจ้งหนี้) */
    function isEditable(inv) {
        return inv?.payment?.p_status === 'UNPAID' && (inv.payment.latest_event_id ?? null) === null;
    }
    function canRecordPayment(inv) {
        const s = inv?.payment?.p_status;
        return s === 'UNPAID' || s === 'REJECTED';
    }

    /* ---------- เรียก API ---------- */
    function csrf() { return document.querySelector('meta[name="csrf-token"]')?.content ?? ''; }

    async function api(method, url, opts = {}) {
        const headers = { Accept: 'application/json' };
        let body;
        if (method !== 'GET') headers['X-CSRF-TOKEN'] = csrf();
        if (opts.json !== undefined) {
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify(opts.json);
        } else if (opts.form) {
            body = opts.form; // FormData: ให้เบราว์เซอร์ตั้ง Content-Type (multipart) เอง
        }
        let res;
        try {
            res = await fetch(url, { method, headers, body, credentials: 'same-origin' });
        } catch (_) {
            return { ok: false, status: 0, body: { message: 'เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ กรุณาตรวจสอบอินเทอร์เน็ตแล้วลองใหม่' } };
        }
        if (res.status === 401) {
            window.location.href = '/login';
            return { ok: false, status: 401, body: {} };
        }
        let data = {};
        try { data = await res.json(); } catch (_) { /* ไม่ใช่ JSON */ }
        return { ok: res.ok, status: res.status, body: data };
    }

    function errMsg(r, fallback) {
        if (r.status === 419) return 'หน้านี้หมดอายุ กรุณารีเฟรชหน้าแล้วลองใหม่';
        if (r.status === 403) return 'คุณไม่มีสิทธิ์ทำรายการนี้';
        const first = r.body?.errors ? Object.values(r.body.errors)[0] : null;
        if (r.status === 422 && Array.isArray(first) && first[0]) return String(first[0]);
        return (typeof r.body?.message === 'string' && r.body.message) || fallback;
    }
    function fieldError(r, name) {
        const v = r.body?.errors?.[name];
        return Array.isArray(v) && v[0] ? String(v[0]) : '';
    }

    /* โหลดทุกหน้า (สูงสุด cap หน้า × 100 รายการ) */
    async function fetchAll(path, params = {}, cap = 5) {
        const rows = [];
        let page = 1;
        let last = 1;
        do {
            const q = new URLSearchParams();
            for (const [k, v] of Object.entries(params)) if (v !== '' && v != null) q.set(k, v);
            q.set('per_page', '100');
            q.set('page', String(page));
            const r = await api('GET', `${path}?${q}`);
            if (!r.ok) return { ok: false, status: r.status, message: errMsg(r, 'โหลดข้อมูลไม่สำเร็จ'), rows: [], truncated: false };
            rows.push(...(r.body.data ?? []));
            last = Number(r.body.meta?.last_page ?? 1) || 1;
            page++;
        } while (page <= last && page <= cap);
        return { ok: true, rows, truncated: last > cap };
    }

    /* ---------- ไฟล์หลักฐาน ---------- */
    async function loadBlob(url) {
        try {
            const res = await fetch(url, { credentials: 'same-origin', headers: { Accept: '*/*' } });
            if (!res.ok) return { ok: false, status: res.status };
            const blob = await res.blob();
            return { ok: true, blob, type: String(blob.type || '').toLowerCase(), url: URL.createObjectURL(blob) };
        } catch (_) {
            return { ok: false, status: 0 };
        }
    }
    const EXT = { 'image/jpeg': 'jpg', 'image/png': 'png', 'application/pdf': 'pdf' };
    async function downloadProof(url, baseName) {
        const f = await loadBlob(url);
        if (!f.ok) {
            toast(f.status === 404 ? 'ไม่พบไฟล์หลักฐาน' : 'ดาวน์โหลดไฟล์หลักฐานไม่สำเร็จ', 'err');
            return;
        }
        const a = el('a', { href: f.url, download: baseName + '.' + (EXT[f.type] ?? 'bin') });
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(() => URL.revokeObjectURL(f.url), 10000);
    }

    /* ---------- ข้อความแจ้งเตือน ---------- */
    function toast(message, kind = 'ok', ms = 5000) {
        const host = document.querySelector('.rt') || document.body;
        let wrap = host.querySelector(':scope > .iv-toast-wrap');
        if (!wrap) {
            wrap = el('div', { class: 'iv-toast-wrap', role: 'status', 'aria-live': 'polite' });
            host.appendChild(wrap);
        }
        const item = el('div', { class: 'rt-notice rt-notice--' + kind, text: message });
        wrap.appendChild(item);
        setTimeout(() => item.remove(), ms);
    }
    function showBanner(node, kind, message) {
        node.className = 'rt-notice rt-notice--' + kind;
        node.textContent = message;
        node.hidden = false;
    }
    function setText(id, text) { const n = $(id); if (n) n.textContent = text; }

    return {
        $, el, icon, day, today, money, num, thaiMonth, dateTime, invNo, tenantName, roomName, monthKey,
        STATUS, statusKey, badge, bucket, isPastDue, methodLabel, isEditable, canRecordPayment,
        api, errMsg, fieldError, fetchAll, loadBlob, downloadProof, toast, showBanner, setText,
    };
})();
</script>
@endonce
