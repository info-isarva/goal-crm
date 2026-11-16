@extends('layouts.app')

@section('content')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="{{asset('css/user.css') }}" rel="stylesheet">
    <style>
        .users-view-toggle {
            display: inline-flex; background: #eef2ff; border-radius: 10px; padding: 3px; gap: 2px;
        }
        .users-view-toggle .uv-btn {
            border: 0; background: transparent; color: #64748b; font-weight: 700; font-size: .82rem;
            height: 34px; padding: 0 14px; border-radius: 8px; cursor: pointer;
        }
        .users-view-toggle .uv-btn.is-active {
            background: linear-gradient(135deg, #4361ee, #7c3aed); color: #fff;
            box-shadow: 0 4px 12px rgba(67,97,238,.28);
        }

        /* ===== User Settings list (mockup) ===== */
        .us-manage {
            --primary: #4361ee; --primary-light: #eef2ff; --success: #10b981; --success-light: #d1fae5;
            --danger: #ef4444; --warning: #f59e0b; --text-dark: #1e293b; --text-light: #64748b;
            --border-color: #e2e8f0;
        }
        .us-page-head { margin-bottom: 14px; }
        .us-page-brand { display: flex; align-items: center; gap: 12px; }
        .us-page-icon {
            width: 48px; height: 48px; border-radius: 14px; flex-shrink: 0;
            display: inline-flex; align-items: center; justify-content: center;
            background: linear-gradient(145deg, #60a5fa, #7c3aed); color: #fff; font-size: 1.25rem;
            box-shadow: 0 8px 18px rgba(99,102,241,.28);
        }
        .us-page-title { margin: 0; font-size: 1.35rem; font-weight: 800; color: var(--text-dark); }
        .us-page-sub { margin: 2px 0 0; color: var(--text-light); font-size: .85rem; }

        .us-kpis {
            display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 14px; margin-bottom: 14px;
        }
        @media (max-width: 991.98px) { .us-kpis { grid-template-columns: repeat(2, minmax(0,1fr)); } }
        @media (max-width: 575.98px) { .us-kpis { grid-template-columns: 1fr; } }
        .us-kpi {
            position: relative; overflow: hidden;
            display: flex; align-items: center; gap: 14px;
            min-height: 96px; padding: 18px 18px 18px 16px;
            border-radius: 16px; border: 1px solid #eef2f7;
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
            text-decoration: none; color: inherit; cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .us-kpi:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(15, 23, 42, .1);
            color: inherit;
        }
        .us-kpi-icon {
            width: 48px; height: 48px; border-radius: 50%; flex-shrink: 0;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.2rem; position: relative; z-index: 1;
        }
        .us-kpi-body { position: relative; z-index: 1; min-width: 0; }
        .us-kpi-label {
            font-size: .82rem; font-weight: 600; color: #334155; line-height: 1.2; margin-bottom: 4px;
        }
        .us-kpi-value {
            font-size: 1.75rem; font-weight: 800; color: #0f172a; line-height: 1; letter-spacing: -.02em;
        }
        .us-kpi-wave {
            position: absolute; right: 0; bottom: 0; width: 118px; height: 70px;
            pointer-events: none; z-index: 0; opacity: .95;
        }
        .us-kpi-wave svg { width: 100%; height: 100%; display: block; }
        .us-kpi--violet .us-kpi-icon { background: rgba(139, 92, 246, .14); color: #7c3aed; }
        .us-kpi--green .us-kpi-icon { background: rgba(16, 185, 129, .14); color: #10b981; }
        .us-kpi--amber .us-kpi-icon { background: rgba(245, 158, 11, .16); color: #f59e0b; }
        .us-kpi--sky .us-kpi-icon { background: rgba(59, 130, 246, .14); color: #3b82f6; }

        .us-control-card {
            background: #fff; border: 1px solid var(--border-color); border-radius: 14px;
            box-shadow: 0 8px 22px rgba(15,23,42,.06); padding: 14px; margin-bottom: 14px;
        }
        .us-control-top {
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
            gap: 10px; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;
        }
        .us-control-card .users-view-toggle {
            background: #f1f5f9; border-radius: 10px; padding: 4px; gap: 4px;
        }
        .us-control-card .uv-btn {
            height: 36px; padding: 0 14px; font-size: .82rem; gap: 6px;
            display: inline-flex; align-items: center;
        }
        .us-control-card .uv-btn i { font-size: .95rem; }
        .us-filters {
            display: flex; flex-wrap: wrap; align-items: center; gap: 10px;
            background: transparent; border: 0; border-radius: 0; padding: 0; margin: 0; box-shadow: none;
        }
        .us-filter-search { position: relative; flex: 1 1 240px; min-width: 200px; }
        .us-filter-search i {
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;
        }
        .us-filter-search input {
            width: 100%; height: 40px; border: 1px solid #e2e8f0; border-radius: 10px;
            padding: 0 12px 0 36px; font-size: .85rem; background: #fff;
        }
        .us-filter-search input:focus {
            outline: none; border-color: #a78bfa; box-shadow: 0 0 0 3px rgba(124,58,237,.12);
        }
        .us-filter-select {
            min-width: 150px; max-width: 190px;
        }
        .us-filter-select--sm { min-width: 120px; max-width: 130px; }
        .us-filters .select2-container { min-width: 150px; max-width: 190px; }
        .us-filters select.us-filter-select--sm + .select2-container { min-width: 120px; max-width: 130px; }
        .us-filters .select2-container--default .select2-selection--single {
            height: 40px; border: 1px solid #e2e8f0; border-radius: 10px;
            display: flex; align-items: center; background: #fff;
        }
        .us-filters .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px; padding-left: 12px; font-size: .82rem; color: #334155;
        }
        .us-filters .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 38px; right: 6px;
        }
        .us-filters .select2-container--default.select2-container--focus .select2-selection--single,
        .us-filters .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #a78bfa; box-shadow: 0 0 0 3px rgba(124,58,237,.12);
        }
        .us-btn {
            height: 40px; border-radius: 10px; border: 1px solid #e2e8f0; background: #fff;
            padding: 0 14px; font-size: .82rem; font-weight: 700; color: #475569;
            display: inline-flex; align-items: center; justify-content: center; gap: 7px;
            text-decoration: none; cursor: pointer; white-space: nowrap;
        }
        .us-btn i { font-size: .95rem; line-height: 1; }
        .us-btn--primary {
            background: linear-gradient(135deg, #4361ee, #7c3aed); border: 0; color: #fff;
            box-shadow: 0 6px 14px rgba(67,97,238,.28);
        }
        .us-btn--primary:hover { color: #fff; filter: brightness(1.03); }
        .us-btn--create { height: 40px; padding: 0 16px; }
        .us-btn--outline {
            background: #fff; border: 1.5px solid #c4b5fd; color: #6d28d9;
        }
        .us-btn--outline:hover { background: #f5f3ff; color: #5b21b6; }
        .us-btn--ghost:hover { background: #f8fafc; color: var(--text-dark); }
        .us-btn.disabled, .us-btn[disabled] { opacity: .55; pointer-events: none; }
        @media (max-width: 767.98px) {
            .us-control-top { flex-direction: column; align-items: stretch; }
            .us-btn--create { width: 100%; }
            .us-filters .us-btn, .us-filters .dropdown { flex: 1 1 auto; }
            .us-filters .dropdown .us-btn { width: 100%; }
        }

        .us-table-card {
            background: #fff; border: 1px solid #e6eaf2; border-radius: 14px;
            box-shadow: 0 8px 24px rgba(26, 28, 50, .06); overflow: hidden;
        }
        .us-table-head {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            padding: 18px 20px 14px; border-bottom: 0;
        }
        .us-table-title {
            margin: 0; font-size: 1.05rem; font-weight: 800; color: #1a1c32;
            display: inline-flex; align-items: center; gap: 10px;
        }
        .us-table-title i { color: #4f46e5; font-size: 1.15rem; }
        .us-seats { font-size: .78rem; color: #64748b; font-weight: 600; }
        .us-table-wrap {
            overflow-x: auto; -webkit-overflow-scrolling: touch;
            padding: 0 8px 4px; width: 100%; max-width: 100%;
        }
        .us-table {
            font-size: .875rem; width: 100%; min-width: 1080px; margin: 0;
            border-collapse: separate; border-spacing: 0;
            table-layout: auto;
        }
        .us-table thead th {
            background: #f5f6fb; color: #1a1c32; font-size: .78rem; font-weight: 700;
            text-transform: none; letter-spacing: 0;
            border-bottom: 1px solid #eef0f6; border-top: 0;
            white-space: nowrap; padding: 13px 14px; vertical-align: middle;
        }
        .us-table tbody td {
            padding: 16px 14px; border-bottom: 1px solid #eef0f6; border-top: 0;
            vertical-align: middle; color: #334155; background: #fff;
        }
        .us-table tbody tr:last-child td { border-bottom: 0; }
        .us-table tbody tr:hover td { background: #fafbff; }
        .us-table .col-check { width: 48px; text-align: center; }
        .us-table .col-num { width: 48px; color: #1a1c32; font-weight: 600; }
        .us-table .col-target {
            text-align: left; font-variant-numeric: tabular-nums; white-space: nowrap;
            font-weight: 600; color: #1a1c32;
        }
        .us-table .col-actions { width: 132px; }
        .us-table .form-check-input {
            width: 1.05rem; height: 1.05rem; margin: 0; border-radius: 4px;
            border-color: #c9d0dc; cursor: pointer;
        }
        .us-table .form-check-input:checked {
            background-color: #4f46e5; border-color: #4f46e5;
        }
        .us-sort { color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .us-sort:hover { color: #4f46e5; }
        .us-name-cell { display: flex; align-items: center; gap: 12px; min-width: 160px; }
        .us-avatar {
            width: 40px; height: 40px; border-radius: 50%; font-weight: 800;
            font-size: .78rem; display: inline-flex; align-items: center; justify-content: center;
            flex-shrink: 0; letter-spacing: .02em;
        }
        .us-name-link { font-weight: 700; color: #3e63d2; text-decoration: none; line-height: 1.25; }
        .us-name-link:hover { color: #2f4fb8; text-decoration: underline; }
        .us-name-text { font-weight: 700; color: #1a1c32; }
        .us-role {
            display: inline-flex; align-items: center; padding: 5px 12px; border-radius: 999px;
            font-size: .72rem; font-weight: 700; white-space: nowrap; line-height: 1.2; color: #fff;
        }
        .us-role--ceo { background: #2563eb; }
        .us-role--md { background: #7c3aed; }
        .us-role--bm { background: #3b82f6; }
        .us-role--cluster { background: #8b5cf6; }
        .us-role--vrm { background: #0ea5e9; }
        .us-role--sm, .us-role--ssm, .us-role--jsm { background: #10b981; }
        .us-role--rd { background: #6366f1; }
        .us-role--pms, .us-role--accounts { background: #f43f5e; }
        .us-role--dark { background: #1e293b; }
        .us-role--default { background: #64748b; }
        .us-status {
            display: inline-flex; align-items: center; padding: 5px 12px; border-radius: 999px;
            font-size: .72rem; font-weight: 700; line-height: 1.2;
        }
        .us-status--active { background: #d1fae5; color: #047857; }
        .us-status--inactive { background: #fee2e2; color: #b91c1c; }
        .us-2fa-switch {
            display: inline-flex; align-items: center; gap: 10px; min-width: 110px;
        }
        .us-2fa-switch .form-check-input {
            width: 2.35rem; height: 1.2rem; margin: 0; cursor: pointer;
        }
        .us-2fa-switch .form-check-input:checked { background-color: #22c55e; border-color: #22c55e; }
        .us-2fa-switch .form-check-label {
            font-size: .8rem; font-weight: 600; color: #475569; margin: 0; padding: 0;
        }
        .us-2fa-switch .form-check-input:checked + .form-check-label { color: #166534; }
        .us-actions { display: inline-flex; align-items: center; gap: 8px; }
        .us-actions .dropdown { display: inline-flex; }
        .us-action-btn {
            width: 36px; height: 36px; min-width: 36px; padding: 0; border: 0;
            border-radius: 50%;
            background: #eef2ff !important; color: #4338ca !important;
            display: inline-flex; align-items: center; justify-content: center;
            text-decoration: none; cursor: pointer; line-height: 1;
            box-shadow: none !important; outline: none !important;
            transition: background .15s, color .15s, transform .15s;
        }
        .us-action-btn i {
            color: #4338ca !important; font-size: 1rem; line-height: 1;
            width: auto; height: auto; margin: 0;
        }
        .us-action-btn:hover,
        .us-action-btn:focus,
        .us-action-btn:active,
        .us-action-btn.show {
            background: #e0e7ff !important; color: #3730a3 !important;
            transform: translateY(-1px);
        }
        .us-action-btn:hover i,
        .us-action-btn:focus i,
        .us-action-btn:active i,
        .us-action-btn.show i { color: #3730a3 !important; }
        .us-action-btn.disabled { opacity: .4; pointer-events: none; }
        .us-actions .dropdown-menu {
            min-width: 190px; border: 1px solid #e8edf5; border-radius: 12px;
            padding: 6px; box-shadow: 0 12px 28px rgba(15,23,42,.12);
        }
        .us-actions .dropdown-item {
            border-radius: 8px; font-size: .84rem; font-weight: 600; padding: 8px 12px;
            display: flex; align-items: center; gap: 8px;
        }
        .us-actions .dropdown-item i { width: 16px; text-align: center; color: #64748b; }
        .us-actions .dropdown-item.text-danger i { color: #ef4444; }

        /* User Details modal */
        .us-detail-modal {
            border: 0; border-radius: 18px; overflow: hidden;
            box-shadow: 0 24px 60px rgba(15, 23, 42, .18);
        }
        .us-detail-modal__head {
            border: 0; padding: 18px 20px 12px; align-items: flex-start;
            background: linear-gradient(180deg, #f5f3ff 0%, #fff 100%);
        }
        .us-detail-modal__title-wrap {
            display: flex; align-items: center; gap: 12px; min-width: 0;
        }
        .us-detail-modal__badge {
            width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;
            display: inline-flex; align-items: center; justify-content: center;
            background: linear-gradient(145deg, #6366f1, #7c3aed); color: #fff; font-size: 1.15rem;
            box-shadow: 0 8px 16px rgba(99,102,241,.28);
        }
        .us-detail-modal__title {
            margin: 0; font-size: 1.1rem; font-weight: 800; color: #0f172a;
        }
        .us-detail-modal__sub {
            margin: 2px 0 0; font-size: .78rem; color: #64748b; font-weight: 500;
        }
        .us-detail-modal__body { padding: 8px 20px 16px; }
        .us-detail-modal__foot {
            border: 0; padding: 12px 20px 18px; gap: 10px;
            background: #fafbff;
        }
        .us-detail-hero {
            display: flex; align-items: center; gap: 14px;
            padding: 14px; margin-bottom: 14px;
            border: 1px solid #eef2ff; border-radius: 14px;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        }
        .us-detail-hero__avatar {
            width: 56px; height: 56px; border-radius: 50%; flex-shrink: 0;
            display: inline-flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 1rem; letter-spacing: .02em;
            box-shadow: 0 0 0 3px #fff, 0 0 0 5px #e0e7ff;
        }
        .us-detail-hero__name {
            margin: 0; font-size: 1.05rem; font-weight: 800; color: #0f172a; line-height: 1.2;
        }
        .us-detail-hero__email {
            margin: 4px 0 8px; font-size: .82rem; color: #64748b;
        }
        .us-detail-hero__pills { display: flex; flex-wrap: wrap; gap: 6px; }
        .us-detail-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
        }
        @media (max-width: 575.98px) {
            .us-detail-grid { grid-template-columns: 1fr; }
        }
        .us-detail-item {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 12px; border: 1px solid #eef2f7; border-radius: 12px; background: #fff;
        }
        .us-detail-item__icon {
            width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
            display: inline-flex; align-items: center; justify-content: center;
            background: #eef2ff; color: #4338ca; font-size: .95rem;
        }
        .us-detail-item__label {
            font-size: .72rem; font-weight: 700; color: #94a3b8;
            text-transform: uppercase; letter-spacing: .04em; margin-bottom: 2px;
        }
        .us-detail-item__value {
            font-size: .875rem; font-weight: 700; color: #0f172a; line-height: 1.3;
            word-break: break-word;
        }
        .us-detail-item__value.is-muted { color: #94a3b8; font-weight: 600; }
        .us-detail-pill {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 4px 10px; border-radius: 999px;
            font-size: .72rem; font-weight: 700; line-height: 1.2;
        }
        .us-detail-pill--ok { background: #d1fae5; color: #047857; }
        .us-detail-pill--off { background: #fee2e2; color: #b91c1c; }
        .us-detail-pill--warn { background: #f1f5f9; color: #64748b; }

        .us-table-foot {
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
            gap: 12px 16px; padding: 16px 20px; border-top: 1px solid #e8edf5; background: #fff;
        }
        .us-showing { font-size: .84rem; color: #64748b; font-weight: 500; line-height: 1.4; }
        .us-foot-right { display: flex; flex-wrap: wrap; align-items: center; gap: 16px 20px; }
        .us-rows-label {
            display: inline-flex; align-items: center; gap: 10px;
            font-size: .84rem; color: #64748b; font-weight: 500; margin: 0;
        }
        .us-rows-label select {
            height: 34px; min-width: 56px; border: 1px solid #dbe2ea; border-radius: 8px;
            padding: 0 28px 0 12px; background: #fff; font-size: .84rem; font-weight: 600;
            color: #1a1c32; box-shadow: none; outline: none; cursor: pointer;
            appearance: none; -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 16 16'%3E%3Cpath fill='%2364748b' d='M4.5 6l3.5 3.5L11.5 6'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 10px center;
        }
        .us-rows-label select:focus { border-color: #94a3b8; box-shadow: 0 0 0 3px rgba(26,28,50,.06); }
        .us-pager { display: inline-flex; align-items: center; }
        .us-pager-nav { display: inline-flex; }
        .us-pager .us-pagination,
        .us-pager .pagination {
            list-style: none; margin: 0; padding: 0; gap: 6px;
            display: inline-flex; align-items: center;
            border: 0; border-radius: 0; overflow: visible; background: transparent;
        }
        .us-pager .page-item { margin: 0; display: flex; }
        .us-pager .page-link {
            border: 0 !important; border-radius: 8px !important;
            color: #475569 !important; background: transparent !important;
            min-width: 34px; height: 34px;
            display: inline-flex !important; align-items: center; justify-content: center;
            padding: 0 10px !important; font-size: .84rem; font-weight: 600; line-height: 1;
            text-decoration: none !important; box-shadow: none !important;
            transition: background .15s, color .15s;
        }
        .us-pager .page-link:hover {
            background: #f1f5f9 !important; color: #1a1c32 !important;
        }
        .us-pager .page-item.active .page-link {
            background: #4f46e5 !important; color: #fff !important;
            box-shadow: 0 4px 10px rgba(79, 70, 229, .28) !important;
        }
        .us-pager .page-item.us-page-arrow .page-link {
            background: #eef2ff !important; color: #6366f1 !important;
        }
        .us-pager .page-item.us-page-arrow .page-link:hover {
            background: #e0e7ff !important; color: #4338ca !important;
        }
        .us-pager .page-item.disabled .page-link {
            background: transparent !important; color: #cbd5e1 !important;
            pointer-events: none; box-shadow: none !important;
        }
        .us-pager .page-item.us-page-arrow.disabled .page-link {
            background: #f1f5f9 !important; color: #cbd5e1 !important;
        }
        .us-pager .page-link i { font-size: .75rem; line-height: 1; }
        .us-empty { color: #94a3b8; padding: 12px; }
        .us-empty i { font-size: 1.8rem; display: block; margin-bottom: 6px; }

        /* Keep real table layout on all breakpoints (override global stacked-card rules) */
        @media (max-width: 1320.98px) {
            .us-table-wrap.table-responsive > table.us-table,
            .us-table-wrap.table-responsive > table.us-table thead,
            .us-table-wrap.table-responsive > table.us-table tbody,
            .us-table-wrap.table-responsive > table.us-table tr,
            .us-table-wrap.table-responsive > table.us-table th,
            .us-table-wrap.table-responsive > table.us-table td {
                display: table !important;
                width: auto !important;
                visibility: visible !important;
                height: auto !important;
                overflow: visible !important;
            }
            .us-table-wrap.table-responsive > table.us-table { display: table !important; width: 100% !important; min-width: 1080px !important; }
            .us-table-wrap.table-responsive > table.us-table thead { display: table-header-group !important; }
            .us-table-wrap.table-responsive > table.us-table tbody { display: table-row-group !important; }
            .us-table-wrap.table-responsive > table.us-table tr { display: table-row !important; margin: 0 !important; border: 0 !important; border-radius: 0 !important; padding: 0 !important; background: transparent !important; }
            .us-table-wrap.table-responsive > table.us-table th,
            .us-table-wrap.table-responsive > table.us-table td {
                display: table-cell !important;
                width: auto !important;
                padding: 13px 14px !important;
                border-bottom: 1px solid #eef0f6 !important;
                justify-content: initial !important;
                align-items: initial !important;
                gap: 0 !important;
            }
            .us-table-wrap.table-responsive > table.us-table thead th {
                background: #f5f6fb !important;
                padding: 13px 14px !important;
            }
            .us-table-wrap.table-responsive > table.us-table tbody td { padding: 16px 14px !important; }
            .us-table-wrap.table-responsive > table.us-table td:before,
            .us-table-wrap.table-responsive > table.us-table td::before {
                content: none !important;
                display: none !important;
            }
        }
        @media (max-width: 767.98px) {
            .us-table-wrap { padding: 0 4px 4px; }
            .us-table-head { padding: 14px 14px 10px; }
            .us-table-foot { padding: 12px 14px; }
        }

        /* Hierarchy Users — mockup header + wave metric cards */
        .uh-wrap {
            padding-bottom: 8px;
            font-size: .875rem; /* match .us-table */
            line-height: 1.4;
            font-family: inherit;
            -webkit-font-smoothing: antialiased;
        }
        .uh-wrap button,
        .uh-wrap input,
        .uh-wrap select {
            font-family: inherit;
        }
        .uh-header {
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
            gap: 8px 12px; margin-bottom: 14px;
        }
        .uh-header-left { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 16px; min-width: 0; }
        .uh-brand { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .uh-brand-icon {
            width: 48px; height: 48px; border-radius: 14px; flex-shrink: 0;
            display: inline-flex; align-items: center; justify-content: center;
            background: linear-gradient(145deg, #60a5fa 0%, #7c3aed 100%);
            color: #fff; font-size: 1.25rem;
            box-shadow: 0 8px 18px rgba(99,102,241,.28);
        }
        .uh-title { margin: 0; font-size: 1.35rem; font-weight: 800; color: #0f172a; line-height: 1.2; }
        .uh-sub { margin: 2px 0 0; color: #64748b; font-size: .85rem; }

        .uh-stats {
            display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 14px; margin-bottom: 14px;
        }
        @media (max-width: 991.98px) { .uh-stats { grid-template-columns: repeat(2, minmax(0,1fr)); } }
        @media (max-width: 575.98px) { .uh-stats { grid-template-columns: 1fr; } }

        .uh-stat {
            position: relative; overflow: hidden;
            display: flex; align-items: center; gap: 14px;
            min-height: 96px; padding: 18px 18px 18px 16px;
            border-radius: 16px; border: 1px solid #eef2f7;
            background: #fff;
            box-shadow: 0 10px 24px rgba(15,23,42,.06);
        }
        .uh-stat-icon {
            width: 48px; height: 48px; border-radius: 50%; flex-shrink: 0;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.2rem; position: relative; z-index: 1;
        }
        .uh-stat-text { position: relative; z-index: 1; min-width: 0; }
        .uh-stat-label {
            font-size: .82rem; font-weight: 600; color: #334155; line-height: 1.2; margin-bottom: 4px;
        }
        .uh-stat-value {
            font-size: 1.75rem; font-weight: 800; color: #0f172a; line-height: 1; letter-spacing: -.02em;
        }
        .uh-stat-wave {
            position: absolute; right: -6px; bottom: -18px; width: 92px; height: 56px;
            border-radius: 50% 50% 0 60%; opacity: .9; pointer-events: none;
            transform: rotate(-12deg);
        }
        .uh-stat-wave::before {
            content: ''; position: absolute; inset: 10px -8px auto auto; width: 70px; height: 34px;
            border-radius: 50%; opacity: .55;
            background: inherit;
        }

        .uh-stat--violet .uh-stat-icon { background: #efe7ff; color: #7c3aed; }
        .uh-stat--violet .uh-stat-wave { background: linear-gradient(135deg, #c4b5fd, #8b5cf6); }

        .uh-stat--teal .uh-stat-icon { background: #dff7f4; color: #0d9488; }
        .uh-stat--teal .uh-stat-wave { background: linear-gradient(135deg, #99f6e4, #2dd4bf); }

        .uh-stat--amber .uh-stat-icon { background: #fff1e0; color: #ea580c; }
        .uh-stat--amber .uh-stat-wave { background: linear-gradient(135deg, #fdba74, #fb923c); }

        .uh-stat--sky .uh-stat-icon { background: #e0f2fe; color: #0284c7; }
        .uh-stat--sky .uh-stat-wave { background: linear-gradient(135deg, #7dd3fc, #38bdf8); }

        .uh-toolbar {
            display: flex; flex-wrap: wrap; align-items: center; gap: 8px;
            background: #fff; border: 1px solid #e8edf5; border-radius: 10px;
            padding: 8px; margin-bottom: 10px;
        }
        .uh-search {
            position: relative; flex: 1 1 180px; min-width: 140px;
        }
        .uh-search i {
            position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .95rem;
        }
        .uh-search input {
            width: 100%; height: 36px; border: 1px solid #e2e8f0; border-radius: 8px;
            padding: 0 12px 0 36px; font-size: .85rem;
        }
        .uh-select {
            height: 36px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0 28px 0 12px;
            background: #fff; font-size: .82rem; color: #334155; min-width: 120px;
        }
        .uh-mode-toggle {
            display: inline-flex; background: #eef2ff; border-radius: 10px; padding: 3px; gap: 2px;
        }
        .uh-mode {
            border: 0; background: transparent; height: 34px; padding: 0 14px; border-radius: 8px;
            font-size: .82rem; font-weight: 700; color: #64748b; cursor: pointer;
        }
        .uh-mode.is-active { background: #7c3aed; color: #fff; }
        .uh-btn {
            height: 36px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff;
            padding: 0 14px; font-size: .82rem; font-weight: 700; color: #475569;
            display: inline-flex; align-items: center; gap: 6px; cursor: pointer;
        }
        .uh-btn:hover { background: #f8fafc; }

        .uh-main {
            display: grid; grid-template-columns: 220px minmax(0,1fr); gap: 12px; align-items: stretch;
        }
        .uh-main.is-chart-only { grid-template-columns: 1fr; }
        .uh-main.is-chart-only .uh-tree-panel { display: none; }
        @media (max-width: 991.98px) {
            .uh-main { grid-template-columns: 1fr; }
            .uh-tree-panel, .uh-chart-panel { max-height: none; min-height: 260px; }
            .uh-chart-scroll { max-height: 60vh; }
        }
        .uh-tree-panel, .uh-chart-panel {
            background: #fff; border: 1px solid #e8edf5; border-radius: 12px;
            box-shadow: 0 4px 12px rgba(148,163,184,.06);
            min-height: 420px; max-height: calc(100vh - 210px);
            height: calc(100vh - 210px);
        }
        .uh-panel-title {
            padding: 12px 14px; color: #0f172a; border-bottom: 1px solid #f1f5f9;
            font-size: 1.05rem; font-weight: 800; flex-shrink: 0;
        }
        .uh-tree { list-style: none; margin: 0; padding: 8px 6px 12px; overflow: auto; height: calc(100% - 42px); }
        .uh-tree-children { list-style: none; margin: 0; padding-left: 14px; }
        .uh-tree-item:not(.is-open) > .uh-tree-children { display: none; }
        .uh-tree-row {
            width: 100%; border: 0; background: transparent; text-align: left;
            display: flex; align-items: center; gap: 6px; padding: 8px 10px; border-radius: 8px;
            font-size: .875rem; font-weight: 600; color: #334155; cursor: pointer;
        }
        .uh-tree-row:hover { background: #f8fafc; }
        .uh-tree-row.is-active { background: #f5f3ff; color: #5b21b6; font-weight: 700; }
        .uh-folder { color: #f59e0b; font-size: .95rem; }
        .uh-caret { font-size: .7rem; color: #94a3b8; width: 10px; }
        .uh-caret-spacer { width: 10px; display: inline-block; }
        .uh-tree-item:not(.is-open) > .uh-tree-row .uh-caret { transform: rotate(-90deg); }

        .uh-chart-panel { position: relative; display: flex; flex-direction: column; overflow: hidden; }
        .uh-chart-scroll {
            overflow: auto; padding: 12px 12px 48px; flex: 1;
            min-height: 0; -webkit-overflow-scrolling: touch;
            cursor: grab; user-select: none;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE/Edge legacy */
        }
        .uh-chart-scroll::-webkit-scrollbar { display: none; width: 0; height: 0; }
        .uh-chart-scroll.is-panning { cursor: grabbing; }
        .uh-chart-scroll .uh-node-card { cursor: grab; }
        .uh-chart-scroll.is-panning .uh-node-card { cursor: grabbing; }
        .uh-chart {
            transform-origin: top center; transition: transform .15s ease;
            min-width: max-content; padding: 4px 8px 16px; width: max-content; margin: 0 auto;
        }

        /* Compact dynamic org chart */
        .uh-org {
            display: flex; justify-content: center; align-items: flex-start;
            list-style: none; margin: 0; padding: 0; position: relative;
        }
        .uh-org-item {
            display: flex; flex-direction: column; align-items: center;
            position: relative; padding: 0 6px;
        }

        /* Sibling horizontal rail */
        .uh-org > .uh-org-item:not(:only-child)::before {
            content: ''; position: absolute; top: 0; height: 0;
            border-top: 1.5px solid #8b5cf6; left: 0; right: 0;
        }
        .uh-org > .uh-org-item:first-child:not(:only-child)::before { left: 50%; }
        .uh-org > .uh-org-item:last-child:not(:only-child)::before { right: 50%; }

        /* Drop line + arrow into card (non-root) */
        .uh-org-item:not(.is-root) > .uh-node-wrap { padding-top: 16px; }
        .uh-org-item:not(.is-root) > .uh-node-wrap::before {
            content: ''; position: absolute; top: 0; left: 50%;
            width: 1.5px; height: 11px; background: #8b5cf6; transform: translateX(-50%);
        }
        .uh-org-item:not(.is-root) > .uh-node-wrap::after {
            content: ''; position: absolute; top: 9px; left: 50%; transform: translateX(-50%);
            border-left: 5px solid transparent; border-right: 5px solid transparent;
            border-top: 6px solid #7c3aed; z-index: 2;
        }

        .uh-node-wrap {
            position: relative; z-index: 2;
            display: flex; flex-direction: column; align-items: center;
        }

        .uh-org-item.has-kids > .uh-node-wrap .uh-stem {
            width: 1.5px; height: 10px; background: #8b5cf6; margin-top: 1px;
        }
        .uh-org-item.has-kids > .uh-node-wrap .uh-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: #7c3aed; border: 1.5px solid #fff;
            box-shadow: 0 0 0 1.5px #8b5cf6; position: relative; z-index: 3;
        }
        .uh-org-item.has-kids > .uh-org { margin-top: 0; padding-top: 10px; }
        .uh-org-item.has-kids > .uh-org::before {
            content: ''; position: absolute; top: 0; left: 50%;
            width: 1.5px; height: 10px; background: #8b5cf6; transform: translateX(-50%);
        }

        .uh-node-card {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
            box-shadow: 0 3px 10px rgba(15,23,42,.05);
            padding: 10px 12px; width: 190px;
            display: flex; align-items: center; gap: 10px;
            position: relative; z-index: 2;
            font-size: .875rem;
        }
        .uh-node-card.is-root-card {
            border: 1.5px solid #7c3aed;
            box-shadow: 0 4px 12px rgba(109,40,217,.14);
        }
        .uh-avatar {
            width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
            background: linear-gradient(145deg,#c4b5fd,#7c3aed); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: .78rem; position: relative;
        }
        .uh-online {
            position: absolute; right: 0; bottom: 0; width: 7px; height: 7px;
            border-radius: 50%; background: #22c55e; border: 1.5px solid #fff;
        }
        .uh-node-body { min-width: 0; flex: 1; }
        .uh-node-name {
            font-weight: 700; color: #3e63d2; font-size: .875rem; line-height: 1.25;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .uh-node-role { color: #64748b; font-size: .82rem; margin-top: 2px; line-height: 1.2; font-weight: 500; }
        .uh-node-meta {
            margin-top: 4px; font-size: .78rem; color: #7c3aed; font-weight: 700;
            display: inline-flex; align-items: center; gap: 4px;
        }
        .uh-node-meta i { font-size: .78rem; }
        .uh-empty {
            padding: 28px 16px; text-align: center; color: #94a3b8; font-size: .85rem;
        }
        .uh-more-roots { margin-top: 16px; border-top: 1px dashed #e9d5ff; padding-top: 8px; }
        .uh-flat-row { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; padding: 6px 0 8px; }
        .uh-zoom {
            position: absolute; right: 10px; bottom: 10px; z-index: 5;
            display: inline-flex; align-items: center; gap: 4px;
            background: #fff; border: 1px solid #e2e8f0; border-radius: 999px;
            padding: 3px 6px; box-shadow: 0 4px 12px rgba(15,23,42,.08);
        }
        .uh-zoom button {
            width: 24px; height: 24px; border: 0; border-radius: 50%; background: #f8fafc;
            color: #475569; cursor: pointer; font-size: .75rem;
        }
        .uh-zoom span { font-size: .7rem; font-weight: 700; color: #64748b; min-width: 34px; text-align: center; }
        .uh-node-card.is-hidden, .uh-org-item.is-hidden { display: none !important; }
        .uh-node-card.is-selected {
            border-color: #7c3aed !important;
            box-shadow: 0 0 0 2px rgba(124,58,237,.25);
        }

        /* Users under selected card */
        .uh-user-drawer {
            position: absolute; top: 42px; right: 0; bottom: 0; width: min(320px, 92%);
            background: #fff; border-left: 1px solid #e8edf5;
            box-shadow: -8px 0 24px rgba(15,23,42,.08);
            z-index: 8; display: flex; flex-direction: column;
            animation: uhDrawerIn .18s ease;
        }
        .uh-user-drawer[hidden] { display: none !important; }
        @keyframes uhDrawerIn {
            from { transform: translateX(12px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .uh-drawer-head {
            display: flex; align-items: flex-start; justify-content: space-between;
            gap: 10px; padding: 12px 12px 8px; border-bottom: 1px solid #f1f5f9;
        }
        .uh-drawer-who { display: flex; align-items: center; gap: 10px; min-width: 0; }
        .uh-drawer-avatar { width: 40px; height: 40px; font-size: .78rem; }
        .uh-drawer-name { font-weight: 800; font-size: .875rem; color: #0f172a; line-height: 1.2; }
        .uh-drawer-role { font-size: .82rem; color: #64748b; margin-top: 2px; }
        .uh-drawer-close {
            width: 28px; height: 28px; border: 0; border-radius: 8px; background: #f8fafc;
            color: #64748b; font-size: 1.2rem; line-height: 1; cursor: pointer;
        }
        .uh-drawer-close:hover { background: #f1f5f9; color: #0f172a; }
        .uh-drawer-meta {
            padding: 8px 12px; font-size: .75rem; color: #64748b;
            border-bottom: 1px solid #f1f5f9;
        }
        .uh-drawer-tabs {
            display: flex; gap: 4px; padding: 8px 10px; background: #f8fafc;
            border-bottom: 1px solid #f1f5f9;
        }
        .uh-drawer-tab {
            flex: 1; border: 0; background: transparent; height: 30px; border-radius: 8px;
            font-size: .75rem; font-weight: 700; color: #64748b; cursor: pointer;
        }
        .uh-drawer-tab.is-active { background: #fff; color: #7c3aed; box-shadow: 0 1px 3px rgba(15,23,42,.08); }
        .uh-drawer-list { flex: 1; overflow: auto; padding: 8px; }
        .uh-drawer-item {
            display: flex; align-items: center; gap: 10px; padding: 8px;
            border-radius: 10px; border: 1px solid #f1f5f9; margin-bottom: 6px;
            background: #fff;
        }
        .uh-drawer-item .uh-avatar { width: 36px; height: 36px; font-size: .72rem; }
        .uh-drawer-item-name { font-weight: 700; font-size: .875rem; color: #3e63d2; }
        .uh-drawer-item-role { font-size: .82rem; color: #64748b; }
        .uh-drawer-item-org { font-size: .78rem; color: #94a3b8; margin-top: 1px; }
        .uh-drawer-empty {
            text-align: center; color: #94a3b8; font-size: .8rem; padding: 28px 12px;
        }
    </style>

    <div class="container-fluid p-4 hub-page-wrap hub-page-wrap--crm">
        @include('partials.skeletons.crm_list', [
            'skeletonId' => 'usersPageSkeleton',
            'columns' => 7,
            'rows' => 10,
            'filterFields' => 2,
        ])

        @include('users.partials.manage_list')
        @include('users.partials.hierarchy')
    </div>
@endsection

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var buttons = document.querySelectorAll('.delete-user-btn');
        attachDeleteHandlers(buttons, 'data-user-name');

        var listView = document.getElementById('usersListView');
        var hierarchyView = document.getElementById('usersHierarchyView');

        function setMainView(mode) {
            var isHierarchy = mode === 'hierarchy';
            if (listView) listView.hidden = isHierarchy;
            if (hierarchyView) hierarchyView.hidden = !isHierarchy;
            document.querySelectorAll('.users-view-list-btn').forEach(function (btn) {
                btn.classList.toggle('is-active', !isHierarchy);
            });
            document.querySelectorAll('.users-view-hierarchy-btn').forEach(function (btn) {
                btn.classList.toggle('is-active', isHierarchy);
            });
            try { localStorage.setItem('usersManageView', mode); } catch (e) {}
        }

        document.querySelectorAll('.users-view-list-btn').forEach(function (btn) {
            btn.addEventListener('click', function () { setMainView('list'); });
        });
        document.querySelectorAll('.users-view-hierarchy-btn').forEach(function (btn) {
            btn.addEventListener('click', function () { setMainView('hierarchy'); });
        });

        try {
            if (localStorage.getItem('usersManageView') === 'hierarchy') setMainView('hierarchy');
        } catch (e) {}

        // ---- List view interactions ----
        function confirmThen(opts, onYes) {
            if (window.Swal) {
                Swal.fire({
                    icon: opts.icon || 'question',
                    title: opts.title || 'Confirm',
                    text: opts.text || '',
                    showCancelButton: true,
                    confirmButtonColor: '#7c3aed',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: opts.confirmText || 'Yes'
                }).then(function (r) { if (r.isConfirmed) onYes(); });
            } else if (window.confirm(opts.text || opts.title)) {
                onYes();
            }
        }

        document.querySelectorAll('.us-2fa-form').forEach(function (form) {
            var toggle = form.querySelector('.us-2fa-toggle');
            if (!toggle) return;
            toggle.addEventListener('change', function (e) {
                e.preventDefault();
                var enabling = toggle.checked;
                // Revert until confirmed + server round-trip (full page reload after submit)
                toggle.checked = !enabling;
                var name = form.getAttribute('data-user-name') || 'this user';
                confirmThen({
                    title: enabling ? 'Enable 2FA?' : 'Disable 2FA?',
                    text: (enabling ? 'Enable' : 'Disable') + ' two-factor authentication for ' + name + '?',
                    confirmText: enabling ? 'Enable' : 'Disable'
                }, function () {
                    if (enabling) {
                        if (!form.querySelector('input[name="2fa_enabled"]')) {
                            var h = document.createElement('input');
                            h.type = 'hidden'; h.name = '2fa_enabled'; h.value = '1';
                            form.appendChild(h);
                        }
                        toggle.checked = true;
                        toggle.name = '2fa_enabled';
                    } else {
                        toggle.name = '';
                        toggle.checked = false;
                    }
                    form.submit();
                });
            });
        });

        document.querySelectorAll('.us-status-form').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var next = form.querySelector('input[name="status"]')?.value;
                var activating = String(next) === '1';
                confirmThen({
                    title: activating ? 'Activate user?' : 'Deactivate user?',
                    text: activating ? 'This user will be able to sign in again.' : 'This user will not be able to sign in.',
                    confirmText: activating ? 'Activate' : 'Deactivate',
                    icon: activating ? 'question' : 'warning'
                }, function () { form.submit(); });
            });
        });

        document.querySelectorAll('.us-reset-form').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                confirmThen({
                    title: 'Send password reset?',
                    text: 'A reset link will be emailed to this user.',
                    confirmText: 'Send link'
                }, function () { form.submit(); });
            });
        });

        var detailModalEl = document.getElementById('usUserDetailModal');
        var detailModal = detailModalEl && window.bootstrap ? new bootstrap.Modal(detailModalEl) : null;
        document.querySelectorAll('.us-view-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var raw = btn.getAttribute('data-user') || '';
                var u = {};
                try {
                    u = JSON.parse(raw.indexOf('{') === 0 ? raw : atob(raw));
                } catch (err) {
                    try { u = JSON.parse(decodeURIComponent(escape(atob(raw)))); } catch (e2) { u = {}; }
                }
                var body = document.getElementById('usUserDetailBody');
                function val(s) {
                    return !(s == null || String(s).trim() === '');
                }
                function esc(s) {
                    return String(s == null || s === '' ? '—' : s).replace(/[&<>"']/g, function (c) {
                        return ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[c];
                    });
                }
                function item(icon, label, valueHtml) {
                    return ''
                        + '<div class="us-detail-item">'
                        +   '<span class="us-detail-item__icon"><i class="bi ' + icon + '"></i></span>'
                        +   '<div class="min-w-0">'
                        +     '<div class="us-detail-item__label">' + label + '</div>'
                        +     '<div class="us-detail-item__value' + (valueHtml.indexOf('—') === 0 ? ' is-muted' : '') + '">' + valueHtml + '</div>'
                        +   '</div>'
                        + '</div>';
                }
                var initials = esc(u.initials || ((u.name || 'U').toString().split(/\s+/).map(function (p) { return p.charAt(0); }).join('').slice(0, 2).toUpperCase() || 'U'));
                var avatarBg = esc(u.avatar_bg || '#e0e7ff');
                var avatarFg = esc(u.avatar_fg || '#3730a3');
                var statusPill = (u.status === 'Active')
                    ? '<span class="us-detail-pill us-detail-pill--ok"><i class="bi bi-check-circle-fill"></i> Active</span>'
                    : '<span class="us-detail-pill us-detail-pill--off"><i class="bi bi-x-circle-fill"></i> ' + esc(u.status || 'Inactive') + '</span>';
                var twoFaPill = (u.two_fa === 'Enabled')
                    ? '<span class="us-detail-pill us-detail-pill--ok"><i class="bi bi-shield-lock-fill"></i> Enabled</span>'
                    : '<span class="us-detail-pill us-detail-pill--warn"><i class="bi bi-shield"></i> Disabled</span>';
                var roleHtml = val(u.role)
                    ? '<span class="us-role ' + esc(u.role_class || 'us-role--default') + '">' + esc(u.role) + '</span>'
                    : '—';
                if (body) {
                    body.innerHTML = ''
                        + '<div class="us-detail-hero">'
                        +   '<span class="us-detail-hero__avatar" style="background:' + avatarBg + ';color:' + avatarFg + '">' + initials + '</span>'
                        +   '<div class="min-w-0">'
                        +     '<h6 class="us-detail-hero__name">' + esc(u.name) + '</h6>'
                        +     '<div class="us-detail-hero__email">' + esc(u.email) + '</div>'
                        +     '<div class="us-detail-hero__pills">' + roleHtml + statusPill + twoFaPill + '</div>'
                        +   '</div>'
                        + '</div>'
                        + '<div class="us-detail-grid">'
                        +   item('bi-envelope', 'Email', esc(u.email))
                        +   item('bi-phone', 'Mobile', esc(u.mobile))
                        +   item('bi-briefcase', 'CRM Role', roleHtml)
                        +   item('bi-person-badge', 'Job Role', esc(u.job_role))
                        +   item('bi-building', 'Org Unit', esc(u.org))
                        +   item('bi-currency-rupee', 'Sales Target', '₹ ' + esc(u.sales_target || '0.00'))
                        +   item('bi-activity', 'Status', statusPill)
                        +   item('bi-shield-lock', '2FA', twoFaPill)
                        + '</div>';
                }
                var edit = document.getElementById('usUserDetailEdit');
                if (edit) {
                    if (u.id) {
                        edit.href = @json(url('/users')) + '/' + u.id + '/edit';
                        edit.classList.remove('disabled');
                    } else {
                        edit.removeAttribute('href');
                        edit.classList.add('disabled');
                    }
                }
                if (detailModal) detailModal.show();
            });
        });

        var selectAll = document.getElementById('usSelectAll');
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                document.querySelectorAll('.us-row-check').forEach(function (cb) {
                    cb.checked = selectAll.checked;
                });
            });
        }

        // ---- Dynamic hierarchy chart (built from reports_to_user_id) ----
        var main = document.getElementById('uhMain');
        var chartEl = document.getElementById('uhChart');
        var allUsers = [];
        try {
            var jsonEl = document.getElementById('uhUsersJson');
            allUsers = JSON.parse(jsonEl ? jsonEl.textContent : '[]');
        } catch (e) { allUsers = []; }

        var selectedOrgIds = []; // empty = all
        var zoom = 1; // keep chart text size aligned with the left tree

        // Build orgId -> [self + descendant ids] from tree buttons
        var orgSubtreeMap = {};
        document.querySelectorAll('.uh-tree-row[data-org]').forEach(function (row) {
            var id = row.getAttribute('data-org') || '';
            var ids = (row.getAttribute('data-org-ids') || id || '')
                .split(',').map(function (x) { return x.trim(); }).filter(Boolean);
            if (id) orgSubtreeMap[id] = ids;
        });

        function rolePriority(roleId) {
            // Goal hierarchy order: MD→CEO→RD→CH→BM→VRM→JSM→SM→SSM→PMS→Accounts
            var order = { 4: 0, 5: 1, 6: 2, 7: 3, 8: 4, 9: 5, 10: 6, 11: 7, 12: 8, 13: 9, 14: 10 };
            roleId = parseInt(roleId, 10);
            return order[roleId] != null ? order[roleId] : 50;
        }

        function activeOrgIds() {
            if (selectedOrgIds && selectedOrgIds.length) return selectedOrgIds;
            var orgSelect = document.getElementById('uhFilterOrg')?.value || '';
            if (orgSelect) return orgSubtreeMap[orgSelect] || [String(orgSelect)];
            return [];
        }

        function filteredUsers() {
            var q = (document.getElementById('uhSearch')?.value || '').toLowerCase().trim();
            var role = document.getElementById('uhFilterRole')?.value || '';
            var orgIds = activeOrgIds();

            return allUsers.filter(function (u) {
                if (q) {
                    var hay = (u.name + ' ' + u.role + ' ' + (u.org_name || '')).toLowerCase();
                    if (hay.indexOf(q) === -1) return false;
                }
                if (role && String(u.role_id) !== String(role)) return false;
                if (orgIds.length) {
                    if (!u.org_id || orgIds.indexOf(String(u.org_id)) === -1) return false;
                }
                return true;
            });
        }

        function childrenOf(list, parentId) {
            var ids = {};
            list.forEach(function (u) { ids[u.id] = true; });
            return list.filter(function (u) {
                if (parentId === null) {
                    // Root: no manager, or manager not in filtered set
                    return !u.reports_to || !ids[u.reports_to];
                }
                return u.reports_to === parentId;
            }).sort(function (a, b) {
                return rolePriority(a.role_id) - rolePriority(b.role_id) || String(a.name).localeCompare(String(b.name));
            });
        }

        function depthOf(list, userId, memo) {
            memo = memo || {};
            if (memo[userId] != null) return memo[userId];
            var kids = list.filter(function (u) { return u.reports_to === userId; });
            if (!kids.length) return memo[userId] = 1;
            var max = 1;
            kids.forEach(function (k) { max = Math.max(max, 1 + depthOf(list, k.id, memo)); });
            return memo[userId] = max;
        }

        function updateStats(list) {
            var roots = childrenOf(list, null);
            var managers = list.filter(function (u) {
                return list.some(function (c) { return c.reports_to === u.id; });
            }).length;
            var levels = 1;
            roots.forEach(function (r) { levels = Math.max(levels, depthOf(list, r.id)); });
            if (!list.length) levels = 1;
            var set = function (id, v) { var el = document.getElementById(id); if (el) el.textContent = v; };
            set('uhStatTotal', list.length);
            set('uhStatTop', roots.length);
            set('uhStatManagers', managers);
            set('uhStatLevels', levels);
            // Root count always reflects full (unscoped by org tree selection uses filtered)
            set('uhTreeRootCount', allUsers.length);

            // Update each folder count from current search/role filters (org-scoped)
            var q = (document.getElementById('uhSearch')?.value || '').toLowerCase().trim();
            var role = document.getElementById('uhFilterRole')?.value || '';
            document.querySelectorAll('.uh-tree-count').forEach(function (span) {
                var ids = (span.getAttribute('data-org-ids') || '').split(',').map(function (x) { return x.trim(); }).filter(Boolean);
                var n = allUsers.filter(function (u) {
                    if (!u.org_id || ids.indexOf(String(u.org_id)) === -1) return false;
                    if (role && String(u.role_id) !== String(role)) return false;
                    if (q) {
                        var hay = (u.name + ' ' + u.role + ' ' + (u.org_name || '')).toLowerCase();
                        if (hay.indexOf(q) === -1) return false;
                    }
                    return true;
                }).length;
                span.textContent = n;
            });
        }

        function escapeHtml(s) {
            return String(s || '').replace(/[&<>"']/g, function (c) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
            });
        }

        function cardHtml(u, isRoot, childCount) {
            return ''
                + '<div class="uh-node-card' + (isRoot ? ' is-root-card' : '') + '"'
                + ' data-id="' + u.id + '" data-name="' + escapeHtml((u.name || '').toLowerCase()) + '"'
                + ' data-role="' + u.role_id + '" data-org="' + (u.org_id || '') + '">'
                +   '<div class="uh-avatar">' + escapeHtml(u.initials || 'U') + '<span class="uh-online"></span></div>'
                +   '<div class="uh-node-body">'
                +     '<div class="uh-node-name">' + escapeHtml(u.name) + '</div>'
                +     '<div class="uh-node-role">' + escapeHtml(u.role) + '</div>'
                +     '<div class="uh-node-meta"><i class="bi bi-people-fill"></i> ' + childCount + ' Users</div>'
                +   '</div>'
                + '</div>';
        }

        function renderNode(u, list, isRoot) {
            var kids = childrenOf(list, u.id);
            // Only true children that report to this user (not orphan promotion)
            var trueKids = list.filter(function (c) { return c.reports_to === u.id; });
            var hasKids = trueKids.length > 0;
            var html = '<li class="uh-org-item' + (isRoot ? ' is-root' : '') + (hasKids ? ' has-kids' : '') + '">';
            html += '<div class="uh-node-wrap">';
            html += cardHtml(u, isRoot, trueKids.length);
            if (hasKids) {
                html += '<div class="uh-stem"></div><div class="uh-dot"></div>';
            }
            html += '</div>';
            if (hasKids) {
                html += '<ul class="uh-org">';
                trueKids.sort(function (a, b) {
                    return rolePriority(a.role_id) - rolePriority(b.role_id) || String(a.name).localeCompare(String(b.name));
                }).forEach(function (k) { html += renderNode(k, list, false); });
                html += '</ul>';
            }
            html += '</li>';
            return html;
        }

        function renderChart() {
            if (!chartEl) return;
            var list = filteredUsers();
            updateStats(list);

            var roots = childrenOf(list, null);
            roots.sort(function (a, b) {
                return rolePriority(a.role_id) - rolePriority(b.role_id) || String(a.name).localeCompare(String(b.name));
            });

            if (!roots.length) {
                chartEl.innerHTML = '<div class="uh-empty">No users match the current filters.</div>';
                return;
            }

            // Prefer a single primary tree (highest priority root); show other disconnected roots below
            var primary = roots[0];
            var others = roots.slice(1);
            var html = '<ul class="uh-org uh-org-root">' + renderNode(primary, list, true) + '</ul>';
            if (others.length) {
                html += '<div class="uh-more-roots"><div class="uh-panel-title">Other top-level users</div>';
                others.forEach(function (u) {
                    html += '<ul class="uh-org uh-org-root" style="margin-top:18px">' + renderNode(u, list, true) + '</ul>';
                });
                html += '</div>';
            }
            chartEl.innerHTML = html;
            applyZoom();
            if (selectedUserId != null) {
                document.querySelectorAll('.uh-node-card').forEach(function (c) {
                    c.classList.toggle('is-selected', String(c.getAttribute('data-id')) === String(selectedUserId));
                });
                if (drawer && !drawer.hidden) renderDrawerList();
            }
        }

        function applyZoom() {
            if (chartEl) chartEl.style.transform = 'scale(' + zoom + ')';
            var zoomLabel = document.getElementById('uhZoomLabel');
            if (zoomLabel) zoomLabel.textContent = Math.round(zoom * 100) + '%';
        }

        document.querySelectorAll('.uh-mode').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.uh-mode').forEach(function (b) { b.classList.remove('is-active'); });
                btn.classList.add('is-active');
                if (main) main.classList.toggle('is-chart-only', btn.getAttribute('data-mode') === 'chart');
            });
        });

        document.querySelectorAll('.uh-tree-row').forEach(function (row) {
            row.addEventListener('click', function (e) {
                var item = row.closest('.uh-tree-item');
                var caret = e.target.closest('.uh-caret');
                // Caret toggles expand/collapse; row click selects + keeps ancestors open
                if (caret && item && item.querySelector(':scope > .uh-tree-children')) {
                    item.classList.toggle('is-open');
                    e.stopPropagation();
                    return;
                }
                if (item) {
                    item.classList.add('is-open');
                    var parent = item.parentElement?.closest('.uh-tree-item');
                    while (parent) {
                        parent.classList.add('is-open');
                        parent = parent.parentElement?.closest('.uh-tree-item');
                    }
                }
                document.querySelectorAll('.uh-tree-row').forEach(function (r) { r.classList.remove('is-active'); });
                row.classList.add('is-active');

                var org = row.getAttribute('data-org') || '';
                selectedOrgIds = (row.getAttribute('data-org-ids') || org || '')
                    .split(',').map(function (x) { return x.trim(); }).filter(Boolean);

                var orgSelect = document.getElementById('uhFilterOrg');
                if (orgSelect) orgSelect.value = org;
                renderChart();
            });
        });

        function syncExpandAllButton() {
            var btn = document.getElementById('uhExpandAll');
            if (!btn) return;
            var items = Array.prototype.slice.call(document.querySelectorAll('.uh-tree-item')).filter(function (item) {
                return !!item.querySelector(':scope > .uh-tree-children');
            });
            var allOpen = items.length > 0 && items.every(function (item) { return item.classList.contains('is-open'); });
            var icon = btn.querySelector('i');
            var label = btn.querySelector('.uh-expand-label');
            if (allOpen) {
                if (icon) icon.className = 'bi bi-arrows-collapse';
                if (label) label.textContent = 'Collapse All';
                btn.setAttribute('data-mode', 'collapse');
            } else {
                if (icon) icon.className = 'bi bi-arrows-expand';
                if (label) label.textContent = 'Expand All';
                btn.setAttribute('data-mode', 'expand');
            }
        }

        document.getElementById('uhExpandAll')?.addEventListener('click', function () {
            var btn = document.getElementById('uhExpandAll');
            var mode = btn?.getAttribute('data-mode') || 'expand';
            document.querySelectorAll('.uh-tree-item').forEach(function (item) {
                if (!item.querySelector(':scope > .uh-tree-children')) return;
                if (mode === 'collapse') item.classList.remove('is-open');
                else item.classList.add('is-open');
            });
            // Keep the company root open so the tree never looks empty
            var root = document.querySelector('#uhOrgTree > .uh-tree-item');
            if (root) root.classList.add('is-open');
            syncExpandAllButton();
        });

        document.querySelectorAll('.uh-tree-row .uh-caret').forEach(function (caret) {
            caret.addEventListener('click', function () {
                setTimeout(syncExpandAllButton, 0);
            });
        });
        syncExpandAllButton();

        document.getElementById('uhZoomIn')?.addEventListener('click', function () {
            zoom = Math.min(1.4, +(zoom + 0.1).toFixed(2)); applyZoom();
        });
        document.getElementById('uhZoomOut')?.addEventListener('click', function () {
            zoom = Math.max(0.4, +(zoom - 0.1).toFixed(2)); applyZoom();
        });

        // ---- Drawer: users under a clicked card ----
        var drawer = document.getElementById('uhUserDrawer');
        var drawerTab = 'direct';
        var selectedUserId = null;

        function descendantsOf(userId, list) {
            var byParent = {};
            list.forEach(function (u) {
                var key = u.reports_to || 0;
                (byParent[key] = byParent[key] || []).push(u);
            });
            var out = [];
            var walk = function (id) {
                (byParent[id] || []).forEach(function (c) {
                    out.push(c);
                    walk(c.id);
                });
            };
            walk(userId);
            return out;
        }

        function directReportsOf(userId, list) {
            return list.filter(function (u) { return u.reports_to === userId; })
                .sort(function (a, b) {
                    return rolePriority(a.role_id) - rolePriority(b.role_id) || String(a.name).localeCompare(String(b.name));
                });
        }

        function renderDrawerList() {
            var listEl = document.getElementById('uhDrawerList');
            if (!listEl || selectedUserId == null) return;
            var pool = filteredUsers();
            var rows = drawerTab === 'all'
                ? descendantsOf(selectedUserId, pool)
                : directReportsOf(selectedUserId, pool);
            rows.sort(function (a, b) {
                return rolePriority(a.role_id) - rolePriority(b.role_id) || String(a.name).localeCompare(String(b.name));
            });

            if (!rows.length) {
                listEl.innerHTML = '<div class="uh-drawer-empty">No users report under this person.</div>';
                return;
            }
            listEl.innerHTML = rows.map(function (u) {
                return ''
                    + '<div class="uh-drawer-item">'
                    +   '<div class="uh-avatar">' + escapeHtml(u.initials || 'U') + '</div>'
                    +   '<div>'
                    +     '<div class="uh-drawer-item-name">' + escapeHtml(u.name) + '</div>'
                    +     '<div class="uh-drawer-item-role">' + escapeHtml(u.role) + '</div>'
                    +     (u.org_name ? '<div class="uh-drawer-item-org">' + escapeHtml(u.org_name) + '</div>' : '')
                    +   '</div>'
                    + '</div>';
            }).join('');
        }

        function openUserDrawer(userId) {
            var u = allUsers.find(function (x) { return x.id === userId; });
            if (!u || !drawer) return;
            selectedUserId = userId;
            document.getElementById('uhDrawerAvatar').textContent = u.initials || 'U';
            document.getElementById('uhDrawerName').textContent = u.name;
            document.getElementById('uhDrawerRole').textContent = u.role;
            var direct = directReportsOf(userId, filteredUsers()).length;
            var all = descendantsOf(userId, filteredUsers()).length;
            document.getElementById('uhDrawerMeta').textContent =
                (u.org_name ? u.org_name + ' · ' : '') + direct + ' direct · ' + all + ' total under';
            drawer.hidden = false;
            drawer.setAttribute('aria-hidden', 'false');
            document.querySelectorAll('.uh-node-card').forEach(function (c) {
                c.classList.toggle('is-selected', String(c.getAttribute('data-id')) === String(userId));
            });
            renderDrawerList();
        }

        function closeUserDrawer() {
            selectedUserId = null;
            if (drawer) {
                drawer.hidden = true;
                drawer.setAttribute('aria-hidden', 'true');
            }
            document.querySelectorAll('.uh-node-card.is-selected').forEach(function (c) {
                c.classList.remove('is-selected');
            });
        }

        document.getElementById('uhDrawerClose')?.addEventListener('click', closeUserDrawer);
        document.querySelectorAll('.uh-drawer-tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                document.querySelectorAll('.uh-drawer-tab').forEach(function (t) { t.classList.remove('is-active'); });
                tab.classList.add('is-active');
                drawerTab = tab.getAttribute('data-tab') || 'direct';
                renderDrawerList();
            });
        });

        // Hand cursor + drag-to-pan; short click on card opens users drawer
        (function initChartPan() {
            var scroller = document.getElementById('uhChartScroll');
            if (!scroller) return;
            var panning = false;
            var moved = false;
            var startX = 0, startY = 0, scrollLeft = 0, scrollTop = 0;
            var cardTarget = null;

            scroller.addEventListener('mousedown', function (e) {
                if (e.button !== 0) return;
                if (e.target.closest('.uh-zoom') || e.target.closest('.uh-user-drawer')) return;
                panning = true;
                moved = false;
                cardTarget = e.target.closest('.uh-node-card');
                scroller.classList.add('is-panning');
                startX = e.clientX;
                startY = e.clientY;
                scrollLeft = scroller.scrollLeft;
                scrollTop = scroller.scrollTop;
                e.preventDefault();
            });

            window.addEventListener('mousemove', function (e) {
                if (!panning) return;
                var dx = e.clientX - startX;
                var dy = e.clientY - startY;
                if (Math.abs(dx) > 4 || Math.abs(dy) > 4) moved = true;
                scroller.scrollLeft = scrollLeft - dx;
                scroller.scrollTop = scrollTop - dy;
            });

            window.addEventListener('mouseup', function () {
                if (!panning) return;
                panning = false;
                scroller.classList.remove('is-panning');
                if (!moved && cardTarget) {
                    var id = parseInt(cardTarget.getAttribute('data-id'), 10);
                    if (id) openUserDrawer(id);
                }
                cardTarget = null;
            });

            var tX = 0, tY = 0, tSL = 0, tST = 0, tMoved = false, tCard = null;
            scroller.addEventListener('touchstart', function (e) {
                if (!e.touches[0] || e.target.closest('.uh-zoom') || e.target.closest('.uh-user-drawer')) return;
                tX = e.touches[0].clientX;
                tY = e.touches[0].clientY;
                tSL = scroller.scrollLeft;
                tST = scroller.scrollTop;
                tMoved = false;
                tCard = e.target.closest('.uh-node-card');
            }, { passive: true });
            scroller.addEventListener('touchmove', function (e) {
                if (!e.touches[0]) return;
                var dx = e.touches[0].clientX - tX;
                var dy = e.touches[0].clientY - tY;
                if (Math.abs(dx) > 4 || Math.abs(dy) > 4) tMoved = true;
                scroller.scrollLeft = tSL - dx;
                scroller.scrollTop = tST - dy;
            }, { passive: true });
            scroller.addEventListener('touchend', function () {
                if (!tMoved && tCard) {
                    var id = parseInt(tCard.getAttribute('data-id'), 10);
                    if (id) openUserDrawer(id);
                }
                tCard = null;
            });

            scroller.addEventListener('wheel', function (e) {
                if (e.shiftKey || Math.abs(e.deltaX) > Math.abs(e.deltaY)) {
                    scroller.scrollLeft += (e.deltaX || e.deltaY);
                } else {
                    scroller.scrollTop += e.deltaY;
                    scroller.scrollLeft += e.deltaX || 0;
                }
            }, { passive: true });
        })();

        document.getElementById('uhSearch')?.addEventListener('input', renderChart);
        document.getElementById('uhFilterRole')?.addEventListener('change', renderChart);
        document.getElementById('uhFilterOrg')?.addEventListener('change', function () {
            var v = this.value || '';
            var opt = this.options[this.selectedIndex];
            var fromOpt = (opt && opt.getAttribute('data-org-ids')) || '';
            selectedOrgIds = v
                ? (fromOpt ? fromOpt.split(',').map(function (x) { return x.trim(); }).filter(Boolean) : (orgSubtreeMap[v] || [String(v)]))
                : [];
            document.querySelectorAll('.uh-tree-row').forEach(function (r) {
                r.classList.toggle('is-active', (r.getAttribute('data-org') || '') === v);
            });
            renderChart();
        });

        document.getElementById('uhExport')?.addEventListener('click', function () {
            var rows = [['Name', 'Role', 'Org Unit', 'Reports To', 'Direct Reports']];
            filteredUsers().forEach(function (u) {
                var mgr = allUsers.find(function (x) { return x.id === u.reports_to; });
                var kids = allUsers.filter(function (c) { return c.reports_to === u.id; }).length;
                rows.push([u.name, u.role, u.org_name || '', mgr ? mgr.name : '', kids]);
            });
            var csv = rows.map(function (r) {
                return r.map(function (c) { return '"' + String(c).replace(/"/g, '""') + '"'; }).join(',');
            }).join('\n');
            var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'hierarchy-users.csv';
            a.click();
        });

        renderChart();
    });
</script>
