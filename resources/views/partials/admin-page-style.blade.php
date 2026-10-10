{{-- Original tenant page CSS, shared by administration pages. --}}
<style>
    /* TENANTS PAGE */
    .tenant-page {
        min-height: calc(100vh - 54px);
        background: #f7f9fc;
        padding: 28px 30px 40px;
    }

    /* HEADER */
    .tenant-header {
        margin-bottom: 25px;
    }

    .tenant-title {
        margin: 0;
        color: #162033;
        font-size: 24px;
        font-weight: 700;
        line-height: 1.3;
    }

    .tenant-subtitle {
        margin-top: 5px;
        color: #7c8ba1;
        font-size: 13px;
    }

    .tenant-add-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 40px;
        padding: 0 17px;
        border: 0;
        border-radius: 7px;
        background: #1a3d6f;
        color: #ffffff;
        font-size: 13px;
        font-weight: 600;
        box-shadow: 0 2px 5px rgba(22, 45, 74, 0.12);
    }

    .tenant-add-btn:hover {
        background: #142f57;
        color: #ffffff;
    }

    /* ALERT */
    .tenant-alert {
        border-radius: 8px;
        font-size: 13px;
    }

    /* SEARCH CARD */
    .tenant-search-card {
        margin-bottom: 20px;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        background: #ffffff;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.035);
    }

    .tenant-search-card .card-body {
        padding: 17px 19px;
    }

    .tenant-search-input {
        height: 40px;
        border: 1px solid #d6dee8;
        border-left: 0;
        color: #263449;
        font-size: 13px;
    }

    .tenant-search-input::placeholder {
        color: #9aa8ba;
    }

    .tenant-search-input:focus {
        border-color: #9cb8dc;
        box-shadow: none;
    }

    .tenant-search-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        border: 1px solid #d6dee8;
        border-right: 0;
        border-radius: 7px 0 0 7px;
        background: #ffffff;
        color: #8c9bad;
    }

    .tenant-search-btn {
        height: 40px;
        padding: 0 17px;
        border: 0;
        border-radius: 6px;
        background: #1a3d6f;
        color: #ffffff;
        font-size: 13px;
        font-weight: 600;
    }

    .tenant-search-btn:hover {
        background: #142f57;
    }

    .tenant-clear-btn {
        height: 40px;
        padding: 0 16px;
        border: 1px solid #d6dee8;
        border-radius: 6px;
        background: #ffffff;
        color: #64748b;
        font-size: 13px;
    }

    .tenant-clear-btn:hover {
        background: #f8fafc;
        color: #334155;
    }

    /* TABLE CARD */
    .tenant-table-card {
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        background: #ffffff;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.035);
    }

    .tenant-table {
        margin: 0;
    }

    .tenant-table thead th {
        height: 47px;
        padding: 0 16px;
        border-bottom: 1px solid #e5eaf0;
        background: #f8fafc;
        color: #64748b;
        font-size: 11px;
        font-weight: 600;
        vertical-align: middle;
        white-space: nowrap;
    }

    .tenant-table thead th:first-child {
        padding-left: 20px;
    }

    .tenant-table tbody td {
        min-height: 58px;
        padding: 12px 16px;
        border-bottom: 1px solid #edf1f5;
        color: #475569;
        font-size: 12px;
        vertical-align: middle;
    }

    .tenant-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .tenant-table tbody tr:hover {
        background: #fafcff;
    }

    .tenant-id {
        color: #94a3b8;
        font-size: 11px;
    }

    .tenant-name {
        color: #1e293b;
        font-size: 13px;
        font-weight: 600;
    }

    .tenant-phone {
        color: #475569;
    }

    .tenant-email {
        color: #64748b;
    }

    .tenant-address {
        max-width: 280px;
        overflow: hidden;
        color: #64748b;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* ACTION BUTTONS */
    .tenant-action {
        width: 31px;
        height: 31px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border-radius: 6px;
        font-size: 12px;
    }

    .tenant-edit-btn {
        border: 1px solid #b8cbea;
        background: #f4f8ff;
        color: #315f9f;
    }

    .tenant-edit-btn:hover {
        border-color: #8caedc;
        background: #eaf2ff;
        color: #234e88;
    }

    .tenant-delete-btn {
        border: 1px solid #f1c0c0;
        background: #fff7f7;
        color: #dc4c4c;
    }

    .tenant-delete-btn:hover {
        border-color: #e59b9b;
        background: #fff0f0;
        color: #c93636;
    }

    /* PAGINATION */
    #tenantPagination {
        padding: 18px 20px !important;
    }

    #tenantPagination .pagination {
        gap: 4px;
        margin: 0;
    }

    #tenantPagination .page-link {
        min-width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 9px;
        border: 1px solid #dce3eb;
        border-radius: 5px !important;
        background: #ffffff;
        color: #64748b;
        font-size: 11px;
    }

    #tenantPagination .page-link:hover {
        background: #f5f8fc;
        color: #1a3d6f;
    }

    #tenantPagination .page-item.active .page-link {
        border-color: #1a3d6f;
        background: #1a3d6f;
        color: #ffffff;
    }

    #tenantPagination .page-item.disabled .page-link {
        background: #f8fafc;
        color: #cbd5e1;
    }

    /* EMPTY / LOADING */
    .tenant-state {
        padding: 55px 20px !important;
    }

    .tenant-state-icon {
        margin-bottom: 12px;
        color: #a0aec0;
        font-size: 35px;
    }

    .tenant-state-title {
        margin-bottom: 5px;
        color: #334155;
        font-size: 13px;
        font-weight: 600;
    }

    .tenant-state-text {
        color: #94a3b8;
        font-size: 12px;
    }
    </style>
