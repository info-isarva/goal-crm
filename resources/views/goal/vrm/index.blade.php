@extends('layouts.app')

@push('styles')
<style>
.vrm-page { color: #1e293b; }
.vrm-page .page-hero {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
    gap: 1rem; margin-bottom: 1.25rem;
}
.vrm-page .page-hero-left {
    display: flex; align-items: center; gap: 14px; min-width: 0;
}
.vrm-page .page-hero-icon {
    width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0;
    background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
    color: #fff; display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.35rem; box-shadow: 0 10px 20px rgba(99, 102, 241, 0.28);
}
.vrm-page .vrm-title { font-size: 1.55rem; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.2; }
.vrm-page .vrm-subtitle { color: #64748b; font-size: 0.9rem; margin-top: 4px; line-height: 1.35; }
.vrm-page .page-hero-actions {
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
}
.vrm-page .vrm-pill {
    display: inline-flex; align-items: center; gap: 0.45rem;
    background: var(--theme-50); color: var(--theme-primary-hover); border: 1px solid var(--theme-200);
    border-radius: 999px; padding: 0.45rem 0.95rem; font-size: 0.85rem; font-weight: 600;
    height: 40px;
}
.vrm-page .vrm-pill .dot {
    width: 8px; height: 8px; border-radius: 50%; background: var(--theme-primary); display: inline-block;
}
.vrm-page .btn-log-call {
    background: linear-gradient(90deg, #7c3aed, #6d28d9);
    color: #fff; border: none; border-radius: 10px;
    height: 40px; padding: 0 1.05rem; font-weight: 700; font-size: 0.9rem;
    display: inline-flex; align-items: center; gap: 0.4rem;
    box-shadow: 0 8px 16px rgba(109, 40, 217, 0.28);
}
.vrm-page .btn-log-call:hover { filter: brightness(1.05); color: #fff; }

/* KPI cards — icon left (centered), text right, corner wedge accent */
.vrm-page .kpi-row { margin-bottom: 1.25rem; }
.vrm-page .kpi-card {
    position: relative; overflow: hidden;
    background: #fff;
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 18px;
    padding: 1.15rem 1.2rem;
    min-height: 96px;
    height: 100%;
    box-shadow: 0 8px 24px rgba(148, 163, 184, 0.14);
    display: flex; align-items: center; gap: 14px;
}
/* Soft corner wedge / wave like design */
.vrm-page .kpi-card::before {
    content: '';
    position: absolute;
    right: -8px; bottom: -32px;
    width: 130px; height: 92px;
    pointer-events: none;
    opacity: 0.42;
    border-radius: 70% 70% 0 80%;
    transform: rotate(-12deg);
}
.vrm-page .kpi-card::after {
    content: '';
    position: absolute;
    right: -28px; bottom: -42px;
    width: 112px; height: 112px;
    border-radius: 50%;
    pointer-events: none;
    opacity: 0.16;
}
.vrm-page .kpi-card.tone-blue::before {
    background: linear-gradient(135deg, transparent 20%, var(--theme-300) 100%);
}
.vrm-page .kpi-card.tone-blue::after { background: var(--theme-primary); }
.vrm-page .kpi-card.tone-green::before {
    background: linear-gradient(135deg, transparent 20%, #86efac 100%);
}
.vrm-page .kpi-card.tone-green::after { background: #22c55e; }
.vrm-page .kpi-card.tone-orange::before {
    background: linear-gradient(135deg, transparent 20%, #fdba74 100%);
}
.vrm-page .kpi-card.tone-orange::after { background: #f59e0b; }
.vrm-page .kpi-card.tone-purple::before {
    background: linear-gradient(135deg, transparent 20%, #c4b5fd 100%);
}
.vrm-page .kpi-card.tone-purple::after { background: #8b5cf6; }
.vrm-page .kpi-card.tone-teal::before {
    background: linear-gradient(135deg, transparent 20%, #5eead4 100%);
}
.vrm-page .kpi-card.tone-teal::after { background: #0d9488; }
.vrm-page .kpi-card.tone-amber::before {
    background: linear-gradient(135deg, transparent 20%, #fcd34d 100%);
}
.vrm-page .kpi-card.tone-amber::after { background: #d97706; }
.vrm-page .kpi-icon {
    position: relative; z-index: 1;
    width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.3rem; line-height: 1;
}
.vrm-page .kpi-icon.blue { background: var(--theme-100); color: var(--theme-primary); }
.vrm-page .kpi-icon.green { background: #dcfce7; color: #16a34a; }
.vrm-page .kpi-icon.orange { background: #ffedd5; color: #ea580c; }
.vrm-page .kpi-icon.purple { background: #ede9fe; color: #7c3aed; }
.vrm-page .kpi-icon.teal { background: #ccfbf1; color: #0f766e; }
.vrm-page .kpi-icon.amber { background: #fef3c7; color: #b45309; }
.vrm-page .kpi-meta {
    position: relative; z-index: 1;
    min-width: 0;
    display: flex; flex-direction: column; justify-content: center;
}
.vrm-page .kpi-label {
    font-size: 0.82rem; color: #475569; font-weight: 600;
    margin: 0 0 6px; line-height: 1.2;
}
.vrm-page .kpi-value {
    font-size: 1.85rem; font-weight: 800; color: #0f172a;
    line-height: 1; margin: 0; letter-spacing: -0.02em;
}

.vrm-page .vrm-card {
    background: #fff; border: 1px solid #e8edf5; border-radius: 16px;
    box-shadow: 0 8px 24px rgba(148, 163, 184, 0.12); overflow: visible;
}
.vrm-page .vrm-card-head {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: 1.1rem 1.25rem 1rem; border-bottom: 1px solid #eef2f7;
}
.vrm-page .vrm-card-head-left {
    display: flex; align-items: center; gap: 12px; min-width: 0;
}
.vrm-page .section-icon {
    width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;
    background: var(--theme-100); color: var(--theme-primary);
    display: inline-flex; align-items: center; justify-content: center; font-size: 1.15rem;
}
.vrm-page .vrm-card-head h5 {
    margin: 0; font-size: 1.08rem; font-weight: 800; color: #0f172a; line-height: 1.2;
}
.vrm-page .vrm-card-head .head-sub {
    margin: 3px 0 0; font-size: 0.82rem; color: #64748b; line-height: 1.3;
}
.vrm-page .vrm-card-body { padding: 0; }
.vrm-page .filter-card {
    background: #fff; border: 1px solid #e8edf5; border-radius: 16px;
    box-shadow: 0 8px 24px rgba(148, 163, 184, 0.12); margin-bottom: 1.15rem;
    padding: 1rem 1.15rem;
}
.vrm-page .filter-grid {
    display: grid;
    grid-template-columns: minmax(180px, 1.4fr) repeat(4, minmax(130px, 1fr)) auto;
    gap: 10px; align-items: end;
}
.vrm-page .filter-grid label {
    display: block; font-size: 0.75rem; font-weight: 700; color: #64748b;
    margin: 0 0 5px; text-transform: uppercase; letter-spacing: .03em;
}
.vrm-page .filter-grid .form-control,
.vrm-page .filter-grid .form-select {
    min-height: 38px; height: 38px; border-radius: 8px; border: 1px solid #d1d5db; background: #fff;
}
.vrm-page .filter-grid .select2-container { width: 100% !important; }
.vrm-page .filter-grid .select2-container--default .select2-selection--single {
    height: 38px !important; min-height: 38px !important; border: 1px solid #d1d5db !important;
    border-radius: 8px !important; background: #fff !important;
}
.vrm-page .filter-grid .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 36px !important; padding-left: 0.75rem !important; color: #0f172a !important;
}
.vrm-page .filter-card, .vrm-page .filter-grid { overflow: visible !important; position: relative; z-index: 5; }
/* .form-select / Select2 use global dropdown theme */
.vrm-page .btn-apply {
    height: 31px; border-radius: 8px; border: none; background: #7c3aed; color: #fff;
    padding: 0 14px; font-weight: 700; font-size: 0.85rem;
    display: inline-flex; align-items: center; gap: 6px;
}
.vrm-page .btn-apply:hover { background: #6d28d9; color: #fff; }
.vrm-page .btn-reset {
    height: 31px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; color: #475569;
    padding: 0 14px; font-weight: 600; font-size: 0.85rem; text-decoration: none;
    display: inline-flex; align-items: center; gap: 6px;
}
.vrm-page .btn-reset:hover { background: #f8fafc; color: #0f172a; }
.vrm-page .toolbar {
    display: flex; flex-wrap: wrap; align-items: center; gap: 8px;
    justify-content: flex-end; min-width: 0;
}
.vrm-page .toolbar-search {
    width: 220px; max-width: 100%; border-radius: 8px;
}
.vrm-page .toolbar-actions {
    display: inline-flex; flex-wrap: wrap; align-items: center; gap: 8px;
}
.vrm-page .btn-tool {
    height: 34px; border-radius: 8px; border: 1px solid #dbe3ee; background: #fff; color: #475569;
    padding: 0 12px; font-size: 0.82rem; font-weight: 600;
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    text-decoration: none; cursor: pointer; white-space: nowrap;
}
.vrm-page .btn-tool:hover { background: #f8fafc; color: #0f172a; }
.vrm-page .btn-tool.excel { border-color: #bbf7d0; background: #f0fdf4; color: #15803d; }
.vrm-page .btn-tool.excel:hover { background: #dcfce7; }
.vrm-page .filter-actions {
    display: flex; align-items: end; gap: 8px;
}
.vrm-page .filter-actions .btn-apply,
.vrm-page .filter-actions .btn-reset {
    flex: 0 0 auto;
}
.vrm-page .export-menu {
    border: 1px solid #e2e8f0; border-radius: 10px; padding: 6px; min-width: 160px;
    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.12);
}
.vrm-page .export-menu .dropdown-item {
    border-radius: 8px; font-weight: 600; font-size: 0.875rem; color: #334155;
    display: flex; align-items: center; gap: 8px; padding: 8px 10px;
}
.vrm-page .export-menu .dropdown-item:hover { background: #f8fafc; color: #0f172a; }
.vrm-page .input-icon { position: relative; }
.vrm-page .input-icon > i,
.vrm-page .input-icon > .rupee {
    position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
    color: #94a3b8; font-size: 0.95rem; z-index: 2; pointer-events: none;
}
.vrm-page .input-icon .rupee { font-weight: 700; font-style: normal; }
.vrm-page .table-footer-bar {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
    gap: 12px 16px;
    margin-top: 0;
    padding: .75rem 1.15rem;
    border-top: 1px solid #eef2f7;
    color: #64748b; font-size: 0.84rem;
}
.vrm-page .foot-showing {
    font-size: 0.84rem; color: #64748b; font-weight: 500; line-height: 1.4;
}
.vrm-page .foot-right {
    display: flex; flex-wrap: wrap; align-items: center; gap: 12px 16px;
    margin-left: auto;
}
.vrm-page .per-page-wrap {
    display: inline-flex; align-items: center; gap: 10px;
    margin: 0; font-size: 0.84rem; color: #64748b; font-weight: 500;
}
.vrm-page .per-page-wrap select {
    height: 34px; min-width: 56px; width: auto;
    border: 1px solid #dbe2ea; border-radius: 8px;
    padding: 0 28px 0 12px; background: #fff;
    font-size: 0.84rem; font-weight: 600; color: #1a1c32;
    box-shadow: none; outline: none; cursor: pointer;
    appearance: none; -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 16 16'%3E%3Cpath fill='%2364748b' d='M4.5 6l3.5 3.5L11.5 6'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 10px center;
}
.vrm-page .per-page-wrap select:focus {
    border-color: #94a3b8; box-shadow: 0 0 0 3px rgba(26,28,50,.06);
}
.vrm-page .foot-pager { display: inline-flex; align-items: center; }
.vrm-page .foot-pager .pagination {
    list-style: none; margin: 0; padding: 0; gap: 6px;
    display: inline-flex; align-items: center;
    border: 0; background: transparent;
}
.vrm-page .foot-pager .page-item { margin: 0; display: flex; }
.vrm-page .foot-pager .page-link {
    border: 0 !important; border-radius: 8px !important;
    color: #475569 !important; background: transparent !important;
    min-width: 34px; height: 34px;
    display: inline-flex !important; align-items: center; justify-content: center;
    padding: 0 10px !important; font-size: 0.84rem; font-weight: 600; line-height: 1;
    text-decoration: none !important; box-shadow: none !important;
}
.vrm-page .foot-pager .page-link:hover {
    background: #f1f5f9 !important; color: #1a1c32 !important;
}
.vrm-page .foot-pager .page-item.active .page-link {
    background: #7c3aed !important; color: #fff !important;
    box-shadow: 0 4px 10px rgba(124, 58, 237, 0.28) !important;
}
.vrm-page .foot-pager .page-item.disabled .page-link {
    background: transparent !important; color: #cbd5e1 !important;
    pointer-events: none; box-shadow: none !important;
}
.vrm-page .foot-pager .page-item.us-page-arrow .page-link,
.vrm-page .foot-pager .page-item:first-child .page-link,
.vrm-page .foot-pager .page-item:last-child .page-link {
    background: #f5f3ff !important; color: #7c3aed !important;
}
.vrm-page .foot-pager .page-item.us-page-arrow.disabled .page-link,
.vrm-page .foot-pager .page-item.disabled:first-child .page-link,
.vrm-page .foot-pager .page-item.disabled:last-child .page-link {
    background: #f1f5f9 !important; color: #cbd5e1 !important;
}
.vrm-page .dataTables_wrapper .dataTables_filter,
.vrm-page .dataTables_wrapper .dataTables_length,
.vrm-page .dataTables_wrapper .dataTables_info,
.vrm-page .dataTables_wrapper .dataTables_paginate,
.vrm-page .dt-buttons { display: none; }

.vrm-page .calls-table-wrap {
    border: 0; border-radius: 0;
    overflow-x: auto; -webkit-overflow-scrolling: touch; background: #fff;
    width: 100%; max-width: 100%;
}
.vrm-page .calls-table-wrap .dataTables_wrapper {
    width: 100%; overflow: visible;
}
.vrm-page .calls-table-wrap table,
.vrm-page .calls-table-wrap table.table,
.vrm-page .calls-table-wrap #vrmCallsTable {
    width: 100% !important;
    min-width: 980px !important;
    table-layout: auto;
    margin: 0 !important;
}
.vrm-page .calls-table-wrap table th,
.vrm-page .calls-table-wrap table td {
    white-space: nowrap;
}
.vrm-page .calls-table-wrap .notes-cell {
    white-space: nowrap;
    max-width: 180px;
}
.vrm-page .table { margin: 0; border-collapse: separate; border-spacing: 0; }
.vrm-page .table thead th {
    font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;
    color: #64748b; font-weight: 700; background: #f1f5f9;
    border-bottom: 1px solid #e2e8f0; border-top: none; white-space: nowrap;
    vertical-align: middle; padding: 0.75rem 0.9rem;
}
.vrm-page .table thead th .th-sort {
    display: inline-flex; align-items: center; gap: 4px;
}
.vrm-page .table thead th .th-sort i {
    font-size: 0.7rem; color: #94a3b8; opacity: 0.85;
}
.vrm-page .table tbody tr { background: #fff; transition: background 0.12s ease; }
.vrm-page .table tbody tr:hover { background: #f8fafc; }
.vrm-page .table td {
    vertical-align: middle; font-size: 0.875rem; color: #334155;
    border-color: #eef2f7; padding: 0.85rem 0.9rem;
}
.vrm-page .when-cell {
    display: flex; align-items: center; gap: 0.65rem;
}
.vrm-page .when-icon {
    width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
    background: #ede9fe; color: #7c3aed;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 0.95rem; line-height: 1;
}
.vrm-page .when-text { min-width: 0; display: flex; flex-direction: column; justify-content: center; }
.vrm-page .when-main {
    font-weight: 700; color: #0f172a; line-height: 1.25; margin: 0;
}
.vrm-page .when-sub { font-size: 0.75rem; color: #94a3b8; margin: 2px 0 0; }
.vrm-page .cust-name { font-weight: 700; color: #0f172a; line-height: 1.25; }
.vrm-page .cust-mobile { font-size: 0.84rem; color: #475569; font-weight: 500; }
.vrm-page .notes-cell {
    max-width: 220px; color: #64748b; font-size: 0.84rem;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.vrm-page .disp-pill {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 0.3rem 0.75rem; border-radius: 999px;
    font-size: 0.74rem; font-weight: 700; white-space: nowrap; line-height: 1.2;
}
.vrm-page .disp-pill.interested { background: #dcfce7; color: #15803d; }
.vrm-page .disp-pill.callback { background: #fef3c7; color: #b45309; }
.vrm-page .disp-pill.not-interested { background: var(--theme-100); color: var(--theme-primary-hover); }
.vrm-page .disp-pill.busy,
.vrm-page .disp-pill.no-answer,
.vrm-page .disp-pill.follow-up { background: #ede9fe; color: #6d28d9; }
.vrm-page .disp-pill.invalid-number { background: #fee2e2; color: #b91c1c; }
.vrm-page .lead-badge {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 44px; padding: 0.22rem 0.6rem; border-radius: 999px;
    font-size: 0.74rem; font-weight: 700;
}
.vrm-page .lead-badge.yes { background: #dcfce7; color: #15803d; }
.vrm-page .lead-badge.no { background: #f1f5f9; color: #475569; }
.vrm-page .actions-cell {
    white-space: nowrap; min-width: 168px; padding-right: 1rem !important;
}
.vrm-page .actions-cell .action-group {
    display: inline-flex !important; align-items: center; gap: 8px;
}
.vrm-page .action-btn {
    width: 34px; height: 34px; min-width: 34px; min-height: 34px;
    border-radius: 50%; border: 1px solid transparent;
    background: #f8fafc; color: #64748b;
    display: inline-flex !important; align-items: center; justify-content: center;
    text-decoration: none; flex-shrink: 0; overflow: visible; line-height: 1;
    padding: 0; box-sizing: border-box; vertical-align: middle;
    cursor: pointer;
    transition: transform .15s ease, background-color .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease;
}
.vrm-page .action-btn i {
    font-size: 0.92rem; line-height: 1; display: block;
    width: 1em; height: 1em; margin: 0;
}
.vrm-page .action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 14px rgba(15, 23, 42, 0.08);
}
.vrm-page .action-btn:focus-visible {
    outline: 2px solid #c4b5fd;
    outline-offset: 2px;
}
.vrm-page .action-btn.view {
    border-color: #dbe3ee; background: #f8fafc; color: #475569;
}
.vrm-page .action-btn.view:hover { background: #eef2f7; color: #0f172a; border-color: #cbd5e1; }
.vrm-page .action-btn.edit {
    border-color: #fed7aa; background: #fff7ed; color: #ea580c;
}
.vrm-page .action-btn.edit:hover { background: #ffedd5; color: #c2410c; border-color: #fdba74; }
.vrm-page .action-btn.delete {
    border-color: #fecaca; background: #fef2f2; color: #dc2626;
}
.vrm-page .action-btn.delete:hover { background: #fee2e2; color: #b91c1c; border-color: #fca5a5; }
.vrm-page .action-btn.lead {
    border-color: #dbe3ee; background: #fff; color: #64748b;
}
.vrm-page .action-btn.lead:hover { background: #f1f5f9; color: #334155; border-color: #cbd5e1; }
.tooltip.vrm-action-tooltip .tooltip-inner {
    background: #0f172a; font-size: 0.75rem; font-weight: 600;
    border-radius: 8px; padding: 6px 10px;
}
.tooltip.vrm-action-tooltip.bs-tooltip-top .tooltip-arrow::before { border-top-color: #0f172a; }
.vrm-page .empty-state { padding: 2.5rem 1rem; text-align: center; color: #94a3b8; }
.vrm-page .empty-state i {
    width: 48px; height: 48px; border-radius: 14px; margin: 0 auto 0.75rem;
    background: #f1f5f9; color: #94a3b8;
    display: inline-flex; align-items: center; justify-content: center; font-size: 1.35rem;
}
.vrm-page .table-footer {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
    gap: 0.75rem; padding-top: 0.95rem; margin-top: 0.85rem;
    border-top: 1px solid #eef2f7; color: #64748b; font-size: 0.82rem;
}
.vrm-page .pager {
    display: inline-flex; align-items: center; gap: 4px;
}
.vrm-page .pager span,
.vrm-page .pager a {
    min-width: 30px; height: 30px; border-radius: 8px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 0.8rem; font-weight: 600; text-decoration: none;
    color: #64748b; background: transparent;
}
.vrm-page .pager .active {
    background: #7c3aed; color: #fff; box-shadow: 0 4px 10px rgba(124, 58, 237, 0.28);
}

/* Modal: design field sizes, sticky footer — size via Bootstrap modal-xl */
.vrm-call-modal .modal-content {
    border: none;
    overflow: hidden;
    box-shadow: 0 24px 48px rgba(15, 23, 42, 0.18);
}
.vrm-call-modal form.vrm-log-form {
    min-height: 0;
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    overflow: hidden;
}
.vrm-call-modal .modal-header {
    border-bottom: 1px solid #e8edf5;
    padding: 18px 24px 16px;
    align-items: flex-start;
    flex-shrink: 0;
    background: #fff;
}
.vrm-call-modal .modal-title-wrap { display: flex; align-items: flex-start; gap: 12px; }
.vrm-call-modal .modal-icon {
    width: 44px; height: 44px; border-radius: 12px;
    background: var(--theme-100); color: var(--theme-primary);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink: 0;
}
.vrm-call-modal .modal-title { font-size: 1.2rem; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.25; }
.vrm-call-modal .modal-sub { font-size: 0.86rem; color: #64748b; margin-top: 3px; line-height: 1.35; }
.vrm-call-modal .modal-close { margin-top: 2px; }
.vrm-call-modal .modal-body {
    padding: 18px 24px 12px;
    overflow-y: auto;
    flex: 1 1 auto;
    min-height: 0;
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE/Edge legacy */
}
.vrm-call-modal .modal-body::-webkit-scrollbar {
    width: 0;
    height: 0;
    display: none; /* Chrome/Safari/Edge */
}
.vrm-call-modal .modal-footer {
    border-top: 1px solid #e8edf5;
    padding: 14px 24px;
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    flex-shrink: 0; background: #fff;
}
@media (max-width: 767.98px) {
    .vrm-call-modal .modal-footer {
        display: grid !important;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        padding: 12px 14px calc(12px + env(safe-area-inset-bottom, 0px));
    }
    .vrm-call-modal .modal-footer .footer-actions {
        display: contents;
    }
    .vrm-call-modal .modal-footer .btn-clear,
    .vrm-call-modal .modal-footer .btn-cancel,
    .vrm-call-modal .modal-footer .btn-save-new,
    .vrm-call-modal .modal-footer .btn-save-call {
        width: 100%;
        min-height: 48px;
        height: 48px;
        justify-content: center;
    }
}
.vrm-call-modal .form-section { margin-bottom: 20px; }
.vrm-call-modal .form-section:last-child { margin-bottom: 8px; }
.vrm-call-modal .section-bar {
    display: flex; align-items: center; gap: 8px;
    background: var(--theme-50); color: var(--theme-primary-hover); border-radius: 8px;
    padding: 10px 14px; margin: 0 0 12px; font-weight: 700; font-size: 0.9rem;
}
.vrm-call-modal .section-bar i { font-size: 0.95rem; }

/* Field grids — equal 50/50 columns, same order throughout */
.vrm-call-modal .field-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    column-gap: 18px;
    row-gap: 14px;
    align-items: start;
}
.vrm-call-modal .field {
    min-width: 0;
    width: 100%;
    display: flex;
    flex-direction: column;
}
.vrm-call-modal .contact-suggest-wrap { position: relative; }
.vrm-call-modal .contact-suggest {
    display: none; position: absolute; z-index: 40; left: 0; right: 0; top: calc(100% + 4px);
    max-height: 240px; overflow: auto; margin: 0; padding: 6px;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
}
.vrm-call-modal .contact-suggest.show { display: block; }
.vrm-call-modal .contact-suggest-item {
    display: flex; flex-direction: column; gap: 2px; width: 100%;
    border: 0; background: transparent; text-align: left;
    padding: 9px 10px; border-radius: 8px; cursor: pointer; color: #0f172a;
}
.vrm-call-modal .contact-suggest-item:hover,
.vrm-call-modal .contact-suggest-item.active { background: #f0fdf4; }
.vrm-call-modal .contact-suggest-item .sg-name {
    font-size: 0.86rem; font-weight: 700; line-height: 1.25;
}
.vrm-call-modal .contact-suggest-item .sg-meta {
    font-size: 0.74rem; color: #64748b; font-weight: 500;
}
.vrm-call-modal .contact-suggest-empty {
    padding: 10px; font-size: 0.8rem; color: #64748b; font-weight: 600;
}
.vrm-call-modal .contact-source-hint {
    margin-top: 6px; font-size: 0.74rem; font-weight: 600; color: #64748b;
    display: inline-flex; align-items: center; gap: 6px;
}
.vrm-call-modal .contact-source-hint.assigned { color: #0f766e; }
.vrm-call-modal .contact-source-hint.new { color: #7c3aed; }
.vrm-call-modal .contact-source-hint .chip {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 999px; font-size: 0.7rem; font-weight: 700;
    background: #f1f5f9; color: inherit;
}
.vrm-call-modal .contact-source-hint.assigned .chip { background: #ccfbf1; }
.vrm-call-modal .contact-source-hint.new .chip { background: #ede9fe; }
.vrm-call-modal .product-with-add {
    display: flex;
    align-items: stretch;
    gap: 8px;
    width: 100%;
}
.vrm-call-modal .product-with-add .input-icon {
    flex: 1 1 auto;
    min-width: 0;
}
.vrm-call-modal .btn-quick-add-product {
    flex: 0 0 38px;
    width: 38px;
    height: 38px;
    border-radius: 8px;
    border: 1px solid #c4b5fd;
    background: #f5f3ff;
    color: #7c3aed;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    line-height: 1;
}
.vrm-call-modal .btn-quick-add-product:hover {
    background: #ede9fe;
    color: #6d28d9;
}
/* Nested quick-add must sit above Log Call (stack handled in tw-ui.js) */
#quickAddProductModal.modal.show { z-index: 10670 !important; }
#quickAddDispositionModal.modal.show { z-index: 10670 !important; }
#quickAddProductModal .modal-content {
    border: none;
    border-radius: 14px;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.2);
}
#quickAddProductModal .modal-header {
    border-bottom: 1px solid #e8edf5;
    padding: 14px 16px;
}
#quickAddProductModal .modal-title {
    font-size: 1rem;
    font-weight: 800;
    color: #0f172a;
}
#quickAddProductModal .modal-body { padding: 14px 16px; }
#quickAddProductModal .modal-footer {
    border-top: 1px solid #e8edf5;
    padding: 12px 16px;
}
#quickAddProductModal .field-error {
    display: none;
    margin-top: 6px;
    font-size: 0.75rem;
    color: #dc2626;
}
#quickAddProductModal .field-error.show { display: block; }
#quickAddProductModal .btn-save-quick {
    background: linear-gradient(90deg, #7c3aed, #6d28d9);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
#quickAddProductModal .btn-save-quick:hover { filter: brightness(1.05); color: #fff; }
#quickAddProductModal .btn-save-quick:disabled { opacity: 0.65; }
.vrm-call-modal .field.check-align {
    justify-content: flex-end;
    min-height: calc(16px + 6px + 38px); /* label + gap + form-control md */
    padding-bottom: 0;
}
.vrm-call-modal .form-label {
    display: block;
    height: 16px;
    font-size: 0.8rem; font-weight: 600; color: #334155;
    margin: 0 0 6px; line-height: 16px;
    white-space: nowrap;
}
.vrm-call-modal .form-label .req { color: #ef4444; margin-left: 2px; }
.vrm-call-modal .input-icon {
    position: relative;
    width: 100%;
}
.vrm-call-modal .input-icon > i,
.vrm-call-modal .input-icon > .rupee {
    position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
    color: #94a3b8; font-size: 0.85rem; z-index: 2; pointer-events: none;
    line-height: 1; width: 14px; text-align: center;
}
.vrm-call-modal .input-icon .rupee {
    font-weight: 700; font-style: normal; font-size: 0.88rem;
}
.vrm-call-modal .input-icon.textarea-icon > i {
    top: 10px; transform: none;
}
.vrm-call-modal .form-control {
    width: 100%;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    color: #0f172a;
    background-color: #fff;
    box-shadow: none;
}
.vrm-call-modal .input-icon .form-control {
    padding-left: 30px;
}
/* Select2 / form-select: global dropdown theme */
.vrm-call-modal textarea.form-control {
    min-height: 72px;
    resize: vertical;
    line-height: 1.4;
}
.vrm-call-modal .input-icon textarea.form-control {
    padding-left: 30px;
}
.vrm-call-modal .form-control::placeholder { color: #94a3b8; }
.vrm-call-modal .form-control:focus {
    border-color: var(--theme-300);
    box-shadow: 0 0 0 3px var(--theme-primary-ring);
}
.vrm-call-modal .field-error {
    display: none;
    margin-top: 4px;
    font-size: 0.75rem;
    font-weight: 500;
    color: #dc2626;
    line-height: 1.3;
}
.vrm-call-modal .field-error.show { display: block; }
.vrm-call-modal .form-control.is-invalid,
.vrm-call-modal .form-select.is-invalid {
    border-color: #fca5a5 !important;
}
.vrm-call-modal .form-control.is-invalid:focus,
.vrm-call-modal .form-select.is-invalid:focus {
    border-color: #f87171 !important;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
}
.vrm-call-modal .field.is-invalid .select2-container--default .select2-selection--single {
    border-color: #fca5a5 !important;
}
.vrm-call-modal .field.is-invalid .select2-container--default.select2-container--focus .select2-selection--single,
.vrm-call-modal .field.is-invalid .select2-container--default.select2-container--open .select2-selection--single {
    border-color: #f87171 !important;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
}
.vrm-call-modal .form-check {
    margin: 0;
    min-height: 38px;
    display: flex; align-items: center; gap: 8px;
}
.vrm-call-modal .form-check-input {
    width: 16px; height: 16px; margin: 0; flex-shrink: 0;
    border-color: #cbd5e1;
}
.vrm-call-modal .form-check-input:checked { background-color: var(--theme-primary); border-color: var(--theme-primary); }
.vrm-call-modal .form-check-label { font-size: 0.875rem; color: #334155; margin: 0; line-height: 1.3; }
.vrm-call-modal .footer-actions { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
.vrm-call-modal .btn-clear,
.vrm-call-modal .btn-cancel,
.vrm-call-modal .btn-save-new,
.vrm-call-modal .btn-save-call {
    border-radius: 8px; height: 40px; padding: 0 14px;
    font-weight: 600; font-size: 0.875rem;
    display: inline-flex; align-items: center; gap: 6px;
}
.vrm-call-modal .btn-clear {
    border: 1px solid var(--theme-300); background: #fff; color: var(--theme-primary);
}
.vrm-call-modal .btn-clear:hover { background: var(--theme-50); color: var(--theme-primary-hover); }
.vrm-call-modal .btn-cancel,
.vrm-call-modal .btn-save-new {
    border: 1px solid var(--theme-300); background: #fff; color: var(--theme-primary);
}
.vrm-call-modal .btn-cancel:hover,
.vrm-call-modal .btn-save-new:hover { background: var(--theme-50); color: var(--theme-primary-hover); }
.vrm-call-modal .btn-save-call {
    background: var(--theme-primary); color: #fff; border: 1px solid var(--theme-primary); font-weight: 700;
}
.vrm-call-modal .btn-save-call:hover { background: var(--theme-primary-hover); color: #fff; border-color: var(--theme-primary-hover); }
@media (max-width: 1199.98px) {
    .vrm-page .filter-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .vrm-page .filter-actions {
        grid-column: 1 / -1;
        justify-content: flex-start;
    }
}
@media (max-width: 991.98px) {
    .vrm-page .vrm-card-head {
        flex-direction: column;
        align-items: stretch;
        gap: 0.85rem;
    }
    .vrm-page .toolbar {
        width: 100%;
        justify-content: stretch;
    }
    .vrm-page .toolbar-search {
        width: 100%;
        flex: 1 1 100%;
    }
    .vrm-page .toolbar-actions {
        width: 100%;
    }
    .vrm-page .toolbar-actions .btn-tool,
    .vrm-page .toolbar-actions .dropdown {
        flex: 1 1 0;
    }
    .vrm-page .toolbar-actions .dropdown .btn-tool {
        width: 100%;
    }
}
@media (max-width: 767.98px) {
    .vrm-page.container-fluid,
    .vrm-page {
        padding-left: 0.85rem !important;
        padding-right: 0.85rem !important;
        padding-top: 1rem !important;
        padding-bottom: 1rem !important;
    }
    .vrm-page .page-hero {
        flex-direction: column;
        align-items: stretch;
        gap: 0.85rem;
        margin-bottom: 1rem;
    }
    .vrm-page .page-hero-left { gap: 12px; }
    .vrm-page .page-hero-icon {
        width: 44px; height: 44px; border-radius: 12px; font-size: 1.15rem;
    }
    .vrm-page .vrm-title { font-size: 1.3rem; }
    .vrm-page .vrm-subtitle { font-size: 0.82rem; }
    .vrm-page .page-hero-actions {
        width: 100%;
        display: grid;
        grid-template-columns: 1fr;
        gap: 8px;
    }
    .vrm-page .vrm-pill,
    .vrm-page .btn-log-call {
        width: 100%;
        justify-content: center;
    }
    .vrm-page .filter-card { padding: 0.9rem; margin-bottom: 1rem; }
    .vrm-page .filter-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    .vrm-page .filter-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        width: 100%;
    }
    .vrm-page .filter-actions .btn-apply,
    .vrm-page .filter-actions .btn-reset {
        width: 100%;
        height: 38px;
        justify-content: center;
    }
    .vrm-page .kpi-row { margin-bottom: 1rem; }
    .vrm-page .kpi-card {
        padding: 0.9rem 0.85rem;
        min-height: 84px;
        gap: 10px;
        border-radius: 14px;
    }
    .vrm-page .kpi-icon {
        width: 42px; height: 42px; border-radius: 12px; font-size: 1.1rem;
    }
    .vrm-page .kpi-label { font-size: 0.74rem; margin-bottom: 4px; }
    .vrm-page .kpi-value { font-size: 1.45rem; }
    .vrm-page .vrm-card-head {
        padding: 0.95rem 0.95rem 0.85rem;
    }
    .vrm-page .vrm-card-head h5 { font-size: 1rem; }
    .vrm-page .vrm-card-head .head-sub { font-size: 0.78rem; }
    .vrm-page .section-icon {
        width: 36px; height: 36px; border-radius: 10px; font-size: 1rem;
    }
    .vrm-page .vrm-card-body { padding: 0; }
    .vrm-page .toolbar-actions {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
        width: 100%;
    }
    .vrm-page .toolbar-actions .btn-tool {
        width: 100%;
        padding: 0 8px;
        font-size: 0.78rem;
    }
    .vrm-page .when-sub { display: none; }
    .vrm-page .when-icon { width: 30px; height: 30px; border-radius: 8px; font-size: 0.85rem; }
    .vrm-page .notes-cell { max-width: none; white-space: normal !important; }

    /* Mobile: stack rows into cards (true responsive) */
    .vrm-page .calls-table-wrap {
        border: 0;
        background: transparent;
        overflow: visible;
    }
    .vrm-page .calls-table-wrap .dataTables_wrapper {
        width: 100% !important;
    }
    .vrm-page .calls-table-wrap table,
    .vrm-page .calls-table-wrap table.table,
    .vrm-page .calls-table-wrap #vrmCallsTable {
        display: block !important;
        width: 100% !important;
        min-width: 0 !important;
        border: 0 !important;
    }
    .vrm-page .calls-table-wrap table thead {
        display: none !important;
    }
    .vrm-page .calls-table-wrap table tbody {
        display: block !important;
        width: 100% !important;
    }
    .vrm-page .calls-table-wrap table tbody tr {
        display: block !important;
        width: 100% !important;
        margin: 0 0 0.85rem !important;
        padding: 0.75rem 0.85rem !important;
        border: 1px solid #e8edf5 !important;
        border-radius: 14px !important;
        background: #fff !important;
        box-shadow: 0 6px 16px rgba(148, 163, 184, 0.1);
    }
    .vrm-page .calls-table-wrap table tbody tr:last-child {
        margin-bottom: 0 !important;
    }
    .vrm-page .calls-table-wrap table tbody td {
        display: flex !important;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        width: 100% !important;
        padding: 0.45rem 0 !important;
        border: 0 !important;
        border-bottom: 1px dashed #eef2f7 !important;
        white-space: normal !important;
        text-align: right;
    }
    .vrm-page .calls-table-wrap table tbody td:last-child {
        border-bottom: 0 !important;
        padding-top: 0.7rem !important;
    }
    .vrm-page .calls-table-wrap table tbody td::before {
        content: attr(data-label);
        flex: 0 0 auto;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: .03em;
        text-transform: uppercase;
        color: #64748b;
        text-align: left;
    }
    .vrm-page .calls-table-wrap table tbody td.col-num {
        justify-content: flex-start;
        gap: 0.5rem;
        padding-top: 0 !important;
        padding-bottom: 0.65rem !important;
        border-bottom: 1px solid #eef2f7 !important;
        font-weight: 800;
        color: #0f172a;
        text-align: left;
    }
    .vrm-page .calls-table-wrap table tbody td.col-num::before {
        content: "#";
    }
    .vrm-page .calls-table-wrap table tbody td.col-actions {
        display: block !important;
        text-align: left;
    }
    .vrm-page .calls-table-wrap table tbody td.col-actions::before {
        display: block;
        margin-bottom: 0.55rem;
    }
    .vrm-page .calls-table-wrap table tbody td.col-actions .action-group {
        display: flex !important;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-start;
    }
    .vrm-page .calls-table-wrap table tbody td.col-empty {
        display: block !important;
        text-align: center;
        border: 0 !important;
        padding: 0.5rem 0 !important;
    }
    .vrm-page .calls-table-wrap table tbody td.col-empty::before {
        display: none !important;
    }
    .vrm-page .when-cell {
        justify-content: flex-end;
        text-align: left;
    }
    .vrm-page .when-main { white-space: nowrap; }
    .vrm-page .notes-cell {
        max-width: 58%;
        text-align: right;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap !important;
    }

    .vrm-page .table-footer-bar {
        gap: 10px;
        padding: 12px 2px 0;
        margin-top: 0.75rem;
    }
    .vrm-page .foot-showing {
        flex: 1 1 auto;
        min-width: 0;
        font-size: 0.78rem;
    }
    .vrm-page .foot-right {
        margin-left: 0;
        width: 100%;
        justify-content: space-between;
        gap: 10px;
    }
    .vrm-page .per-page-wrap {
        gap: 8px;
        font-size: 0.78rem;
        flex: 0 0 auto;
    }
    .vrm-page .per-page-wrap select {
        height: 32px;
        min-width: 52px;
        padding: 0 24px 0 10px;
        font-size: 0.8rem;
    }
    .vrm-page .foot-pager {
        margin-left: auto;
        overflow-x: auto;
        max-width: 100%;
    }
    .vrm-call-modal .field-grid { grid-template-columns: 1fr; }
    .vrm-call-modal .field.check-align {
        min-height: 0;
        justify-content: flex-start;
    }
}
@media (max-width: 575.98px) {
    .vrm-page .kpi-label {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }
    .vrm-page .toolbar-actions .btn-tool span.tool-label { display: none; }
    .vrm-page .toolbar-actions .btn-tool { gap: 0; }
}
</style>
@endpush

@section('content')
@php
    $callbacksDueCount = (int) ($callbacksDueCount ?? 0);
    $todayCount = (int) ($todayCount ?? 0);
    $openModal = $errors->any() || request()->boolean('log_call') || session('reopen_log_call');
    $seesTeamLogs = (bool) ($seesTeamLogs ?? false);
    $tableColCount = $seesTeamLogs ? 11 : 10;
    $dispClass = function ($d) {
        return [
            'Interested' => 'interested',
            'Callback' => 'callback',
            'Not Interested' => 'not-interested',
            'Busy' => 'busy',
            'No Answer' => 'no-answer',
            'Invalid Number' => 'invalid-number',
        ][$d] ?? 'follow-up';
    };
    $filters = request()->only(['q', 'disposition', 'lead_status', 'from_date', 'to_date', 'per_page']);
    $dispositionValue = request('disposition', request('disposition_filter'));
@endphp

<div class="container-fluid p-4 vrm-page">
    <div class="page-hero">
        <div class="page-hero-left">
            <span class="page-hero-icon"><i class="bi bi-telephone-outbound"></i></span>
            <div>
                <h1 class="vrm-title">VRM Calling</h1>
                <div class="vrm-subtitle">Log customer calls, capture disposition and manage full call history.</div>
            </div>
        </div>
        <div class="page-hero-actions">
            <span class="vrm-pill" title="Pending to call out of assigned contacts">
                <span class="dot" aria-hidden="true"></span>
                Pending: {{ (int) ($pendingToCallCount ?? 0) }} / {{ (int) ($assignedContactsCount ?? 0) }} contacts
            </span>
            <button type="button" class="btn btn-log-call" data-bs-toggle="modal" data-bs-target="#logCallModal">
                <i class="bi bi-plus-lg"></i> Log Call
            </button>
        </div>
    </div>

    <div class="filter-card">
        <form method="GET" action="{{ route('goal.vrm.index') }}" id="vrmFilterForm" class="filter-grid">
            <div>
                <label for="q">Customer Search</label>
                <input type="text" name="q" id="q" class="form-control"
                       placeholder="Name, mobile or ID..." value="{{ request('q') }}">
            </div>
            <div>
                <label for="disposition">Disposition</label>
                <select name="disposition" id="disposition_filter" class="form-select js-select2"
                        data-placeholder="All dispositions" data-allow-clear="true">
                    <option value="">All</option>
                    @foreach(($dispositions ?? []) as $d)
                        <option value="{{ $d->name }}" @selected($dispositionValue===$d->name)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="lead_status">Lead Status</label>
                <select name="lead_status" id="lead_status" class="form-select js-select2"
                        data-placeholder="All">
                    <option value="all" @selected(request('lead_status', 'all')==='all')>All</option>
                    <option value="yes" @selected(request('lead_status')==='yes')>Yes</option>
                    <option value="no" @selected(request('lead_status')==='no')>No</option>
                </select>
            </div>
            <div>
                <label for="from_date">From Date</label>
                <input type="text" name="from_date" id="from_date" class="form-control js-datepicker"
                       placeholder="dd-mm-yyyy" value="{{ request('from_date') }}" autocomplete="off">
            </div>
            <div>
                <label for="to_date">To Date</label>
                <input type="text" name="to_date" id="to_date" class="form-control js-datepicker"
                       placeholder="dd-mm-yyyy" value="{{ request('to_date') }}" autocomplete="off">
            </div>
            <div class="filter-actions">
                <div>
                    <label class="d-none d-xl-block">&nbsp;</label>
                    <button type="submit" class="btn-apply"><i class="bi bi-funnel"></i> Apply</button>
                </div>
                <div>
                    <label class="d-none d-xl-block">&nbsp;</label>
                    <a href="{{ route('goal.vrm.index') }}" class="btn-reset"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                </div>
            </div>
        </form>
    </div>

    <div class="row g-3 kpi-row">
        <div class="col-6 col-xl-2">
            <div class="kpi-card tone-teal">
                <span class="kpi-icon teal"><i class="bi bi-people-fill"></i></span>
                <div class="kpi-meta">
                    <div class="kpi-label">Assigned Contacts</div>
                    <div class="kpi-value">{{ $assignedContactsCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="kpi-card tone-{{ $pendingToCallCount > 0 ? 'amber' : 'teal' }}">
                <span class="kpi-icon {{ $pendingToCallCount > 0 ? 'amber' : 'teal' }}"><i class="bi bi-telephone-x"></i></span>
                <div class="kpi-meta">
                    <div class="kpi-label">Pending to Call</div>
                    <div class="kpi-value">{{ $pendingToCallCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="kpi-card tone-blue">
                <span class="kpi-icon blue"><i class="bi bi-telephone"></i></span>
                <div class="kpi-meta">
                    <div class="kpi-label">Calls Today</div>
                    <div class="kpi-value">{{ $todayCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <a href="{{ route('goal.vrm.index', ['callbacks_due' => 1]) }}" class="text-decoration-none">
                <div class="kpi-card tone-{{ $callbacksDueCount > 0 ? 'orange' : 'blue' }}">
                    <span class="kpi-icon {{ $callbacksDueCount > 0 ? 'orange' : 'blue' }}"><i class="bi bi-alarm"></i></span>
                    <div class="kpi-meta">
                        <div class="kpi-label">Callbacks due</div>
                        <div class="kpi-value">{{ $callbacksDueCount }}</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-2">
            <div class="kpi-card tone-green">
                <span class="kpi-icon green"><i class="bi bi-hand-thumbs-up"></i></span>
                <div class="kpi-meta">
                    <div class="kpi-label">Interested</div>
                    <div class="kpi-value">{{ $interestedCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="kpi-card tone-purple">
                <span class="kpi-icon purple"><i class="bi bi-person-plus"></i></span>
                <div class="kpi-meta">
                    <div class="kpi-label">Leads Created</div>
                    <div class="kpi-value">{{ $leadsCreatedCount }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="vrm-card">
        <div class="vrm-card-head">
            <div class="vrm-card-head-left">
                <span class="section-icon"><i class="bi bi-table"></i></span>
                <div>
                    <h5>All Call Logs</h5>
                    <div class="head-sub">Complete call history with search, import and export.</div>
                </div>
            </div>
            <div class="toolbar">
                <input type="search" id="callsTableSearch" class="form-control toolbar-search"
                       placeholder="Search table...">
                <div class="toolbar-actions">
                    <a href="{{ route('goal.vrm.calls.import') }}" class="btn-tool">
                        <i class="bi bi-upload"></i> <span class="tool-label">Import</span>
                    </a>
                    <button type="button" class="btn-tool" id="btnColVis">
                        <i class="bi bi-layout-three-columns"></i> <span class="tool-label">Columns</span>
                    </button>
                    <div class="dropdown">
                        <button type="button" class="btn-tool excel dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-download"></i> <span class="tool-label">Export</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end export-menu">
                            <li>
                                <a class="dropdown-item" href="{{ route('goal.vrm.calls.export', array_merge($filters, ['format' => 'pdf'])) }}">
                                    <i class="bi bi-file-earmark-pdf text-danger"></i> PDF
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('goal.vrm.calls.export', array_merge($filters, ['format' => 'csv'])) }}">
                                    <i class="bi bi-filetype-csv text-primary"></i> CSV
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('goal.vrm.calls.export', array_merge($filters, ['format' => 'excel'])) }}">
                                    <i class="bi bi-file-earmark-excel text-success"></i> Excel
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="vrm-card-body">
            <div class="calls-table-wrap">
                <table class="table align-middle mb-0" id="vrmCallsTable" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>When</th>
                            <th>Customer</th>
                            <th>Mobile</th>
                            <th>Disposition</th>
                            <th>Callback</th>
                            <th>Lead</th>
                            <th>Notes</th>
                            <th>Branch</th>
                            @if($seesTeamLogs)
                                <th>Called by</th>
                            @endif
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($logs as $log)
                        @php
                            $when = $log->called_at;
                            $whenLabel = $when ? ($when->isToday() ? 'Today, '.$when->format('g:i A') : $when->format('d M, g:i A')) : '—';
                            $whenExact = $when ? $when->format('d-m-Y H:i') : '';
                            $branch = $log->resolvedBranchName();
                            $cb = $log->callback_at;
                            $cbDue = $cb && $cb->lte(now());
                            $cbLabel = $cb
                                ? ($cb->isToday() ? 'Today, '.$cb->format('g:i A') : ($cb->isTomorrow() ? 'Tomorrow, '.$cb->format('g:i A') : $cb->format('d M, g:i A')))
                                : null;
                            $isOwnLog = (int) $log->user_id === (int) auth()->id();
                            $calledByName = optional($log->user)->name;
                            $calledByRole = optional($log->user)->goalCrmRoleLabel();
                        @endphp
                        <tr>
                            <td class="col-num" data-label="#">{{ $logs->firstItem() + $loop->index }}</td>
                            <td data-label="When">
                                <div class="when-cell">
                                    <span class="when-icon"><i class="bi bi-calendar3"></i></span>
                                    <div class="when-text">
                                        <div class="when-main">{{ $whenLabel }}</div>
                                        @if($whenExact)<div class="when-sub">{{ $whenExact }}</div>@endif
                                    </div>
                                </div>
                            </td>
                            <td data-label="Customer"><div class="cust-name">{{ $log->customer_name ?: '—' }}</div></td>
                            <td data-label="Mobile"><span class="cust-mobile">{{ $log->mobile ?: '—' }}</span></td>
                            <td data-label="Disposition"><span class="disp-pill {{ $dispClass($log->disposition) }}">{{ $log->disposition }}</span></td>
                            <td data-label="Callback">
                                @if($cbLabel)
                                    <div class="when-cell">
                                        <span class="when-icon {{ $cbDue ? 'text-danger' : '' }}"><i class="bi bi-alarm"></i></span>
                                        <div class="when-text">
                                            <div class="when-main {{ $cbDue ? 'text-danger fw-semibold' : '' }}">{{ $cbLabel }}</div>
                                            <div class="when-sub">{{ $cb->format('d-m-Y H:i') }}{{ $cbDue ? ' · due' : '' }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td data-label="Lead">
                                @if($log->goal_loan_lead_id)
                                    <span class="lead-badge yes">Yes</span>
                                @else
                                    <span class="lead-badge no">No</span>
                                @endif
                            </td>
                            <td data-label="Notes"><div class="notes-cell" title="{{ $log->notes }}">{{ $log->notes ?: '—' }}</div></td>
                            <td data-label="Branch">{{ $branch ?: '—' }}</td>
                            @if($seesTeamLogs)
                                <td data-label="Called by">
                                    @if($calledByName)
                                        <div class="cust-name">{{ $calledByName }}</div>
                                        @if($calledByRole)
                                            <div class="when-sub">{{ $calledByRole }}</div>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                            @endif
                            <td class="actions-cell col-actions" data-label="Actions">
                                <div class="action-group">
                                    <button type="button"
                                            class="action-btn view btn-view-call"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            data-bs-title="View details"
                                            data-url="{{ route('goal.vrm.calls.show', $log) }}">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    @if($isOwnLog)
                                    <button type="button"
                                            class="action-btn edit btn-edit-call"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            data-bs-title="Edit"
                                            data-id="{{ $log->id }}"
                                            data-update-url="{{ route('goal.vrm.calls.update', $log) }}"
                                            data-customer="{{ $log->customer_name }}"
                                            data-mobile="{{ $log->mobile }}"
                                            data-disposition="{{ $log->disposition }}"
                                            data-notes="{{ $log->notes }}"
                                            data-callback="{{ optional($log->callback_at)->format('Y-m-d H:i') }}"
                                            data-branch-id="{{ $log->goal_org_unit_id ?: $log->lead?->goal_org_unit_id }}"
                                            data-branch-name="{{ $branch ?: '' }}"
                                            data-lead-type="{{ $log->lead?->lead_type }}"
                                            data-product="{{ $log->lead?->product }}"
                                            data-loan-amount="{{ $log->lead?->loan_amount }}"
                                            data-has-lead="{{ $log->goal_loan_lead_id ? '1' : '0' }}"
                                            data-lead-id="{{ $log->goal_loan_lead_id }}">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    @unless($log->goal_loan_lead_id)
                                    <form method="POST" action="{{ route('goal.vrm.calls.destroy', $log) }}" class="d-inline"
                                          onsubmit="return confirm('Delete this call log?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="action-btn delete"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                data-bs-title="Delete">
                                            <i class="bi bi-trash3-fill"></i>
                                        </button>
                                    </form>
                                    @endunless
                                    @endif
                                    @if($log->goal_loan_lead_id)
                                        <a href="{{ route('goal.leads.show', $log->goal_loan_lead_id) }}"
                                           class="action-btn lead"
                                           data-bs-toggle="tooltip"
                                           data-bs-placement="top"
                                           data-bs-title="Open lead">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $tableColCount }}" class="col-empty" data-label="">
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <div>No call logs found. Click <strong>Log Call</strong> to get started.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-footer-bar">
                <div class="foot-showing">
                    @if($logs->total() > 0)
                        Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }}
                    @else
                        Showing 0 entries
                    @endif
                </div>
                <div class="foot-right">
                    <form method="GET" action="{{ route('goal.vrm.index') }}" class="per-page-wrap" id="perPageForm">
                        @foreach(request()->except('per_page', 'page') as $key => $value)
                            @if(is_array($value))
                                @foreach($value as $v)
                                    <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <span>Rows</span>
                        <select name="per_page" class="form-select-compact" data-select2-skip="1" onchange="this.form.submit()" aria-label="Rows per page">
                            @foreach([10, 25, 50, 100] as $n)
                                <option value="{{ $n }}" @selected((int)($perPage ?? 25) === $n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </form>
                    <div class="foot-pager">
                        {{ $logs->onEachSide(1)->links('vendor.pagination.users') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Log Call Modal --}}
<div class="modal fade vrm-call-modal" id="logCallModal" tabindex="-1" aria-labelledby="logCallModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('goal.vrm.store') }}" id="vrmCallForm" class="vrm-log-form" novalidate
                  data-assigned-search="{{ route('goal.vrm.assigned-contacts') }}">
                @csrf
                <input type="hidden" name="goal_loan_lead_id" id="goal_loan_lead_id" value="{{ old('goal_loan_lead_id') }}">
                <div class="modal-header">
                    <div class="modal-title-wrap">
                        <span class="modal-icon"><i class="bi bi-telephone-outbound"></i></span>
                        <div>
                            <h5 class="modal-title" id="logCallModalLabel">Log Call</h5>
                            <div class="modal-sub">Record a customer call and update lead details.</div>
                        </div>
                    </div>
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                </div>

                <div class="modal-body">
                    <div class="form-section">
                        <div class="section-bar"><i class="bi bi-person"></i> Customer Details</div>
                        <div class="field-grid">
                            <div class="field {{ $errors->has('customer_name') ? 'is-invalid' : '' }}">
                                <label class="form-label" for="customer_name">Customer <span class="req">*</span></label>
                                <div class="contact-suggest-wrap">
                                    <div class="input-icon">
                                        <i class="bi bi-person"></i>
                                        <input type="text" name="customer_name" id="customer_name"
                                               class="form-control js-contact-search {{ $errors->has('customer_name') ? 'is-invalid' : '' }}"
                                               placeholder="Type name to find assigned contact"
                                               value="{{ old('customer_name') }}" required autocomplete="off"
                                               data-required-message="Please enter customer name."
                                               data-search-field="name">
                                    </div>
                                    <div class="contact-suggest" id="customerSuggest" role="listbox" hidden></div>
                                </div>
                                <div class="contact-source-hint" id="contactSourceHint" hidden></div>
                                <div class="field-error {{ $errors->has('customer_name') ? 'show' : '' }}" data-error-for="customer_name">{{ $errors->first('customer_name') }}</div>
                            </div>
                            <div class="field {{ $errors->has('mobile') ? 'is-invalid' : '' }}">
                                <label class="form-label" for="mobile">Mobile <span class="req">*</span></label>
                                <div class="contact-suggest-wrap">
                                    <div class="input-icon">
                                        <i class="bi bi-telephone"></i>
                                        <input type="text" name="mobile" id="mobile"
                                               class="form-control js-contact-search {{ $errors->has('mobile') ? 'is-invalid' : '' }}"
                                               placeholder="Type mobile to find assigned contact" value="{{ old('mobile') }}"
                                               maxlength="10" inputmode="numeric" pattern="[0-9]{10}" required autocomplete="off"
                                               data-search-field="mobile">
                                    </div>
                                    <div class="contact-suggest" id="mobileSuggest" role="listbox" hidden></div>
                                </div>
                                <div class="field-error {{ $errors->has('mobile') ? 'show' : '' }}" data-error-for="mobile">{{ $errors->first('mobile') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-bar"><i class="bi bi-telephone"></i> Call Details</div>
                        <div class="field-grid">
                            <div class="field {{ $errors->has('disposition') ? 'is-invalid' : '' }}">
                                <label class="form-label" for="disposition">Disposition <span class="req">*</span></label>
                                <div class="goal-disposition-with-add product-with-add">
                                    <select name="disposition" id="disposition"
                                            class="form-select js-select2 {{ $errors->has('disposition') ? 'is-invalid' : '' }}"
                                            data-placeholder="Select disposition" required
                                            data-required-message="Please select disposition.">
                                        @foreach(($dispositions ?? []) as $d)
                                            <option value="{{ $d->name }}"
                                                    data-creates-lead="{{ $d->creates_lead ? '1' : '0' }}"
                                                    data-maps-to-lead-type="{{ $d->maps_to_lead_type }}"
                                                    @selected(old('disposition', 'Interested') === $d->name)>{{ $d->name }}</option>
                                        @endforeach
                                    </select>
                                    @if(!empty($canQuickAddDisposition))
                                    <button type="button" class="btn-quick-add-disposition" data-bs-toggle="modal"
                                            data-bs-target="#quickAddDispositionModal" data-disposition-select="#disposition"
                                            title="Quick add disposition" aria-label="Quick add disposition">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                    @endif
                                </div>
                                <div class="field-error {{ $errors->has('disposition') ? 'show' : '' }}" data-error-for="disposition">{{ $errors->first('disposition') }}</div>
                            </div>
                            <div class="field {{ $errors->has('callback_at') ? 'is-invalid' : '' }}">
                                <label class="form-label" for="callback_at">Callback</label>
                                <div class="input-icon">
                                    <i class="bi bi-calendar3"></i>
                                    <input type="text" name="callback_at" id="callback_at"
                                           class="form-control js-datetimepicker {{ $errors->has('callback_at') ? 'is-invalid' : '' }}"
                                           placeholder="dd-mm-yyyy --:--" value="{{ old('callback_at') }}"
                                           autocomplete="off">
                                </div>
                                <div class="field-error {{ $errors->has('callback_at') ? 'show' : '' }}" data-error-for="callback_at">{{ $errors->first('callback_at') }}</div>
                            </div>
                            <div class="field {{ $errors->has('goal_org_unit_id') ? 'is-invalid' : '' }}">
                                <label class="form-label" for="goal_org_unit_id">Branch</label>
                                @php
                                    $branchLocked = !empty($branchLocked);
                                    $defaultBranchId = old('goal_org_unit_id', auth()->user()->goal_org_unit_id);
                                @endphp
                                @if($branchLocked)
                                    <input type="hidden" name="goal_org_unit_id" value="{{ $defaultBranchId }}">
                                    <div class="input-icon">
                                        <i class="bi bi-building"></i>
                                        <input type="text" class="form-control" value="{{ optional($branches->firstWhere('id', (int) $defaultBranchId))->name ?? optional(auth()->user()->orgUnit)->name ?? 'Your branch' }}" readonly disabled>
                                    </div>
                                    <div class="form-text" style="font-size:.72rem;color:#64748b;">VRM can log calls for their assigned branch only.</div>
                                @else
                                    <div class="input-icon">
                                        <i class="bi bi-building"></i>
                                        <select name="goal_org_unit_id" id="goal_org_unit_id"
                                                class="form-select js-select2 {{ $errors->has('goal_org_unit_id') ? 'is-invalid' : '' }}"
                                                data-placeholder="Select branch" data-allow-clear="true">
                                            <option value="">Select branch</option>
                                            @foreach($branches as $b)
                                                <option value="{{ $b->id }}" @selected($defaultBranchId==$b->id)>{{ $b->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="field-error {{ $errors->has('goal_org_unit_id') ? 'show' : '' }}" data-error-for="goal_org_unit_id">{{ $errors->first('goal_org_unit_id') }}</div>
                            </div>
                            <div class="field check-align">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="create_lead" value="1" id="create_lead" @checked(old('create_lead'))>
                                    <label class="form-check-label" for="create_lead" id="create_lead_label">Create lead when disposition allows</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-bar"><i class="bi bi-bullseye"></i> Lead Information</div>
                        <div class="field-grid">
                            <div class="field {{ $errors->has('product') ? 'is-invalid' : '' }}">
                                <label class="form-label" for="product">Product <span class="req lead-required-mark" hidden>*</span></label>
                                <div class="product-with-add">
                                    <div class="input-icon">
                                        <i class="bi bi-layers"></i>
                                        <select name="product" id="product"
                                                class="form-select js-select2 {{ $errors->has('product') ? 'is-invalid' : '' }}"
                                                data-placeholder="Select product" data-allow-clear="true"
                                                data-required-message="Please select a product when creating a lead.">
                                            <option value=""></option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->product_name }}" @selected(old('product') === $product->product_name)>
                                                    {{ $product->product_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @if(!empty($canQuickAddProduct))
                                    <button type="button" class="btn-quick-add-product" id="btnQuickAddProduct"
                                            title="Quick add product" aria-label="Quick add product">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                    @endif
                                </div>
                                <div class="field-error {{ $errors->has('product') ? 'show' : '' }}" data-error-for="product">{{ $errors->first('product') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-bar"><i class="bi bi-cash-coin"></i> Loan Details</div>
                        <div class="field-grid">
                            <div class="field {{ $errors->has('loan_amount') ? 'is-invalid' : '' }}">
                                <label class="form-label" for="loan_amount">Loan Amount <span class="req lead-required-mark" hidden>*</span></label>
                                <div class="input-icon">
                                    <span class="rupee">₹</span>
                                    <input type="text" inputmode="decimal" data-decimals="2" name="loan_amount" id="loan_amount"
                                           class="form-control js-decimal {{ $errors->has('loan_amount') ? 'is-invalid' : '' }}"
                                           placeholder="Enter loan amount" value="{{ old('loan_amount') }}"
                                           data-required-message="Please enter loan amount when creating a lead.">
                                </div>
                                <div class="field-error {{ $errors->has('loan_amount') ? 'show' : '' }}" data-error-for="loan_amount">{{ $errors->first('loan_amount') }}</div>
                            </div>
                            <div class="field {{ $errors->has('notes') ? 'is-invalid' : '' }}">
                                <label class="form-label" for="notes">Notes</label>
                                <div class="input-icon textarea-icon">
                                    <i class="bi bi-file-text"></i>
                                    <textarea name="notes" id="notes"
                                              class="form-control {{ $errors->has('notes') ? 'is-invalid' : '' }}" rows="3"
                                              placeholder="Add call notes, customer response, or remarks...">{{ old('notes') }}</textarea>
                                </div>
                                <div class="field-error {{ $errors->has('notes') ? 'show' : '' }}" data-error-for="notes">{{ $errors->first('notes') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-clear" id="btnClearForm">
                        <i class="bi bi-arrow-counterclockwise"></i> Clear
                    </button>
                    <div class="footer-actions">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="save_and_new" value="1" class="btn btn-save-new">
                            <i class="bi bi-plus-lg"></i> Save &amp; New
                        </button>
                        <button type="submit" class="btn btn-save-call">
                            <i class="bi bi-floppy"></i> Save Call
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@if(!empty($canQuickAddProduct))
{{-- Quick Add Product (nested over Log Call; opened via JS so Log Call stays open) --}}
<div class="modal fade" id="quickAddProductModal" tabindex="-1" aria-labelledby="quickAddProductModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-sm modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickAddProductModalLabel">Quick Add Product</h5>
                <button type="button" class="modal-close" id="btnCloseQuickProduct" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="quick_product_name">Product Name <span class="text-danger">*</span></label>
                <input type="text" id="quick_product_name" class="form-control"
                       placeholder="e.g. Home Loan" maxlength="150" autocomplete="off">
                <div class="field-error" id="quickProductError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" id="btnCancelQuickProduct">Cancel</button>
                <button type="button" class="btn btn-sm btn-save-quick" id="btnSaveQuickProduct">
                    <i class="bi bi-check-lg"></i> Add
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@include('goal.vrm.partials.call-detail-modal')
@include('goal.vrm.partials.call-edit-modal')
@if(!empty($canQuickAddDisposition))
@include('goal.partials.quick-add-disposition')
@endif
@php
    $dispositionMeta = collect($dispositions ?? [])->mapWithKeys(function ($d) {
        return [
            $d->name => [
                'creates_lead' => (bool) $d->creates_lead,
                'maps_to_lead_type' => $d->maps_to_lead_type,
            ],
        ];
    });
@endphp
<script>
window.__vrmDispositionMeta = @json($dispositionMeta);
</script>
@endsection

@push('scripts')
<script>
(function () {
    // Soft action-button tooltips
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        document.querySelectorAll('.vrm-page [data-bs-toggle="tooltip"]').forEach(function (el) {
            bootstrap.Tooltip.getOrCreateInstance(el, {
                container: 'body',
                customClass: 'vrm-action-tooltip',
                trigger: 'hover focus',
                delay: { show: 80, hide: 40 }
            });
            el.addEventListener('click', function () {
                const tip = bootstrap.Tooltip.getInstance(el);
                if (tip) tip.hide();
            });
        });
    }

    const $ = window.jQuery;
    if ($) {
        const tableHasRows = {{ $logs->count() > 0 ? 'true' : 'false' }};
        const isMobileTable = window.matchMedia('(max-width: 767.98px)').matches;
        let dt = null;

        // Client search without DataTables on mobile (card layout)
        function filterMobileCards(term) {
            const q = (term || '').toLowerCase().trim();
            $('#vrmCallsTable tbody tr').each(function () {
                if ($(this).find('td.col-empty').length) return;
                const text = $(this).text().toLowerCase();
                $(this).toggle(!q || text.indexOf(q) !== -1);
            });
        }

        const seesTeamLogs = @json($seesTeamLogs);
        const actionsCol = seesTeamLogs ? 10 : 9;

        if (tableHasRows && $.fn.DataTable && !isMobileTable) {
            dt = $('#vrmCallsTable').DataTable({
                paging: false,
                info: false,
                searching: true,
                ordering: true,
                autoWidth: false,
                scrollX: true,
                order: [],
                columnDefs: [{ orderable: false, targets: [actionsCol] }],
                dom: 't',
                language: { emptyTable: 'No call logs found.' }
            });

            $('#callsTableSearch').on('keyup', function () {
                dt.search(this.value).draw();
            });

            $('#btnColVis').on('click', function () {
                const menu = ['When', 'Customer', 'Mobile', 'Disposition', 'Callback', 'Lead', 'Notes', 'Branch'];
                const map = { When: 1, Customer: 2, Mobile: 3, Disposition: 4, Callback: 5, Lead: 6, Notes: 7, Branch: 8 };
                if (seesTeamLogs) {
                    menu.push('Called by');
                    map['Called by'] = 9;
                }
                let html = '<div class="p-2">';
                menu.forEach(function (label) {
                    const idx = map[label];
                    const visible = dt.column(idx).visible();
                    html += '<label class="d-block mb-1" style="font-size:.85rem;cursor:pointer;">'
                        + '<input type="checkbox" class="form-check-input me-2 col-toggle" data-col="' + idx + '" '
                        + (visible ? 'checked' : '') + '> ' + label + '</label>';
                });
                html += '</div>';

                let $pop = $('#colVisPopover');
                if (!$pop.length) {
                    $pop = $('<div id="colVisPopover" class="shadow border bg-white rounded-3" style="position:absolute;z-index:1080;min-width:180px;"></div>');
                    $('body').append($pop);
                }
                const btn = this.getBoundingClientRect();
                $pop.html(html).css({
                    top: (window.scrollY + btn.bottom + 6) + 'px',
                    left: (window.scrollX + btn.left) + 'px'
                }).show();

                $pop.find('.col-toggle').on('change', function () {
                    dt.column(parseInt(this.dataset.col, 10)).visible(this.checked);
                });
            });

            $(document).on('click', function (e) {
                if (!$(e.target).closest('#btnColVis, #colVisPopover').length) {
                    $('#colVisPopover').hide();
                }
            });
        } else {
            $('#callsTableSearch').on('keyup', function () {
                filterMobileCards(this.value);
            });
            $('#btnColVis').prop('disabled', true).addClass('disabled').attr('title', 'Columns available on desktop');
        }
    }

    const modalEl = document.getElementById('logCallModal');
    const form = document.getElementById('vrmCallForm');
    if (!modalEl || !form) return;

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    function clearFieldError(el) {
        if (!el) return;
        const field = el.closest('.field');
        el.classList.remove('is-invalid');
        if (field) {
            field.classList.remove('is-invalid');
            const err = field.querySelector('.field-error');
            if (err) {
                err.textContent = '';
                err.classList.remove('show');
            }
        }
    }

    function showFieldError(el, message) {
        if (!el) return;
        const field = el.closest('.field');
        el.classList.add('is-invalid');
        if (field) {
            field.classList.add('is-invalid');
            const err = field.querySelector('.field-error');
            if (err) {
                err.textContent = message || 'This field is required.';
                err.classList.add('show');
            }
        }
    }

    function clearAllErrors() {
        form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
        form.querySelectorAll('.field-error').forEach((err) => {
            err.textContent = '';
            err.classList.remove('show');
        });
    }

    function syncLeadRequiredFields() {
        const createLead = document.getElementById('create_lead');
        const required = !!(createLead && createLead.checked && !createLead.disabled);
        form.querySelectorAll('.lead-required-mark').forEach(function (mark) {
            mark.hidden = !required;
        });
        ['product', 'loan_amount'].forEach(function (id) {
            const el = document.getElementById(id);
            if (!el) return;
            if (required) el.setAttribute('required', 'required');
            else el.removeAttribute('required');
        });
    }

    function validateForm() {
        clearAllErrors();
        syncLeadRequiredFields();
        let firstInvalid = null;
        form.querySelectorAll('[required]').forEach((el) => {
            const value = (el.value || '').trim();
            if (!value) {
                const msg = el.getAttribute('data-required-message') || 'This field is required.';
                showFieldError(el, msg);
                if (!firstInvalid) firstInvalid = el;
            }
        });
        if (firstInvalid) {
            firstInvalid.focus();
            if (window.jQuery && window.jQuery(firstInvalid).hasClass('select2-hidden-accessible')) {
                window.jQuery(firstInvalid).select2('open');
            }
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }
        return true;
    }

    function resetFormDefaults() {
        form.reset();
        clearAllErrors();
        const leadIdInput = document.getElementById('goal_loan_lead_id');
        if (leadIdInput) leadIdInput.value = '';
        setContactSourceHint(null);
        hideSuggestMenus();
        const disp = document.getElementById('disposition');
        if (disp) {
            const preferred = Array.from(disp.options).find(function (o) { return o.value === 'Interested'; });
            disp.value = preferred ? 'Interested' : (disp.options[0]?.value || '');
        }
        const createLead = document.getElementById('create_lead');
        if (createLead) createLead.checked = false;
        form.querySelectorAll('input.js-datepicker, input.js-datetimepicker').forEach(function (el) {
            if (el._flatpickr) el._flatpickr.clear();
        });
        if (window.jQuery) {
            window.jQuery(form).find('select.js-select2').each(function () {
                window.jQuery(this).val(this.value).trigger('change');
            });
        }
        syncCreateLeadAvailability();
        syncLeadRequiredFields();
    }

    const assignedSearchUrl = form.getAttribute('data-assigned-search') || '';
    const leadIdInput = document.getElementById('goal_loan_lead_id');
    const nameInput = document.getElementById('customer_name');
    const mobileInput = document.getElementById('mobile');
    const nameSuggest = document.getElementById('customerSuggest');
    const mobileSuggest = document.getElementById('mobileSuggest');
    const contactHint = document.getElementById('contactSourceHint');
    let suggestTimer = null;
    let suggestAbort = null;
    let selectingContact = false;

    function hideSuggestMenus() {
        [nameSuggest, mobileSuggest].forEach(function (menu) {
            if (!menu) return;
            menu.classList.remove('show');
            menu.hidden = true;
            menu.innerHTML = '';
        });
    }

    function setContactSourceHint(mode, meta) {
        if (!contactHint) return;
        if (!mode) {
            contactHint.hidden = true;
            contactHint.className = 'contact-source-hint';
            contactHint.innerHTML = '';
            return;
        }
        contactHint.hidden = false;
        if (mode === 'assigned') {
            contactHint.className = 'contact-source-hint assigned';
            contactHint.innerHTML = '<span class="chip"><i class="bi bi-person-check"></i> Assigned contact</span>'
                + (meta && meta.ref_no ? ' ' + meta.ref_no : '')
                + (meta && meta.status ? ' · ' + meta.status : '');
        } else {
            contactHint.className = 'contact-source-hint new';
            contactHint.innerHTML = '<span class="chip"><i class="bi bi-person-plus"></i> New contact</span> Not from your assigned list';
        }
    }

    function clearAssignedSelection(opts) {
        const options = opts || {};
        if (leadIdInput) leadIdInput.value = '';
        if (!options.keepHint) setContactSourceHint(null);
        syncCreateLeadAvailability({ autoCheck: false });
    }

    function applyAssignedContact(item) {
        if (!item) return;
        selectingContact = true;
        if (leadIdInput) leadIdInput.value = String(item.id || '');
        if (nameInput) nameInput.value = item.customer_name || '';
        if (mobileInput) mobileInput.value = (item.mobile || '').replace(/\D+/g, '').slice(0, 10);
        const branchSel = document.getElementById('goal_org_unit_id');
        if (branchSel && item.goal_org_unit_id) {
            branchSel.value = String(item.goal_org_unit_id);
            if (window.jQuery) window.jQuery(branchSel).val(String(item.goal_org_unit_id)).trigger('change');
        }
        const productSel = document.getElementById('product');
        if (productSel && item.product) {
            const hasOpt = Array.from(productSel.options).some(function (o) { return o.value === item.product; });
            if (hasOpt) {
                productSel.value = item.product;
                if (window.jQuery) window.jQuery(productSel).val(item.product).trigger('change');
            }
        }
        const loanAmt = document.getElementById('loan_amount');
        if (loanAmt && item.loan_amount != null && item.loan_amount !== '') {
            loanAmt.value = item.loan_amount;
        }
        const createLead = document.getElementById('create_lead');
        if (createLead) {
            createLead.checked = false;
            createLead.disabled = true;
        }
        const label = document.getElementById('create_lead_label');
        if (label) label.textContent = 'Lead already assigned — call will link to this contact';
        setContactSourceHint('assigned', item);
        hideSuggestMenus();
        syncLeadRequiredFields();
        setTimeout(function () { selectingContact = false; }, 0);
    }

    function renderSuggest(menu, items, query) {
        if (!menu) return;
        menu.innerHTML = '';
        if (!items.length) {
            menu.innerHTML = '<div class="contact-suggest-empty">No assigned contact found — continue as new contact</div>';
            menu.hidden = false;
            menu.classList.add('show');
            setContactSourceHint('new');
            return;
        }
        items.forEach(function (item, idx) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'contact-suggest-item' + (idx === 0 ? ' active' : '');
            btn.setAttribute('role', 'option');
            btn.innerHTML = '<span class="sg-name"></span><span class="sg-meta"></span>';
            btn.querySelector('.sg-name').textContent = item.customer_name || '—';
            const bits = [item.mobile, item.ref_no, item.branch_name, item.status].filter(Boolean);
            btn.querySelector('.sg-meta').textContent = bits.join(' · ');
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
                applyAssignedContact(item);
            });
            menu.appendChild(btn);
        });
        menu.hidden = false;
        menu.classList.add('show');
    }

    function searchAssignedContacts(query, menu) {
        if (!assignedSearchUrl || !menu) return;
        const q = (query || '').trim();
        if (q.length < 2) {
            hideSuggestMenus();
            if (!leadIdInput || !leadIdInput.value) setContactSourceHint(null);
            return;
        }
        if (suggestAbort) suggestAbort.abort();
        suggestAbort = new AbortController();
        fetch(assignedSearchUrl + '?q=' + encodeURIComponent(q), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            signal: suggestAbort.signal,
            credentials: 'same-origin',
        })
            .then(function (r) { return r.json(); })
            .then(function (payload) {
                const items = (payload && payload.data) ? payload.data : [];
                renderSuggest(menu, items, q);
                if (!items.length && (!leadIdInput || !leadIdInput.value)) {
                    setContactSourceHint('new');
                }
            })
            .catch(function (err) {
                if (err && err.name === 'AbortError') return;
                hideSuggestMenus();
            });
    }

    function bindContactSearch(input, menu) {
        if (!input || !menu) return;
        input.addEventListener('input', function () {
            if (selectingContact) return;
            clearAssignedSelection({ keepHint: true });
            const q = input.value || '';
            clearTimeout(suggestTimer);
            suggestTimer = setTimeout(function () {
                searchAssignedContacts(q, menu);
            }, 220);
        });
        input.addEventListener('focus', function () {
            const q = (input.value || '').trim();
            if (q.length >= 2 && !leadIdInput?.value) searchAssignedContacts(q, menu);
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') hideSuggestMenus();
            if (e.key === 'Enter' && menu.classList.contains('show')) {
                const first = menu.querySelector('.contact-suggest-item');
                if (first) {
                    e.preventDefault();
                    first.dispatchEvent(new Event('mousedown'));
                }
            }
        });
        input.addEventListener('blur', function () {
            setTimeout(function () {
                if (!menu.contains(document.activeElement)) {
                    menu.classList.remove('show');
                    menu.hidden = true;
                }
            }, 150);
            if (!leadIdInput?.value && (nameInput?.value || '').trim().length >= 2) {
                setContactSourceHint('new');
            }
        });
    }

    bindContactSearch(nameInput, nameSuggest);
    bindContactSearch(mobileInput, mobileSuggest);
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.contact-suggest-wrap')) hideSuggestMenus();
    });
    if (leadIdInput && leadIdInput.value) {
        setContactSourceHint('assigned', { ref_no: '' });
    }

    form.addEventListener('submit', function (e) {
        if (!validateForm()) {
            e.preventDefault();
            e.stopPropagation();
        }
    });

    form.querySelectorAll('input, select, textarea').forEach((el) => {
        el.addEventListener('input', () => clearFieldError(el));
        el.addEventListener('change', () => clearFieldError(el));
    });

    if (window.jQuery) {
        window.jQuery(form).on('select2:select select2:clear change', 'select.js-select2', function () {
            clearFieldError(this);
        });
    }

    document.getElementById('btnClearForm')?.addEventListener('click', resetFormDefaults);

    function dispositionAllowsCreateLead() {
        const disp = document.getElementById('disposition');
        if (!disp) return false;
        const name = disp.value || '';
        const meta = (window.__vrmDispositionMeta || {})[name];
        if (meta) return !!meta.creates_lead;
        const opt = disp.selectedOptions && disp.selectedOptions[0];
        return !!(opt && opt.dataset.createsLead === '1');
    }

    function syncCreateLeadAvailability(opts) {
        const options = opts || {};
        const createLead = document.getElementById('create_lead');
        const label = document.getElementById('create_lead_label');
        if (!createLead) return;
        const assignedSelected = !!(document.getElementById('goal_loan_lead_id')?.value);
        if (assignedSelected) {
            createLead.checked = false;
            createLead.disabled = true;
            if (label) label.textContent = 'Lead already assigned — call will link to this contact';
            syncLeadRequiredFields();
            return;
        }
        const allows = dispositionAllowsCreateLead();
        createLead.disabled = !allows;
        if (!allows) {
            createLead.checked = false;
        } else if (options.autoCheck) {
            // Selecting a "Can create lead" disposition turns the box on; user may still uncheck.
            createLead.checked = true;
        }
        if (label) {
            label.textContent = allows
                ? 'Create lead from this disposition'
                : 'Create lead (not available for this disposition)';
        }
        syncLeadRequiredFields();
    }

    document.getElementById('create_lead')?.addEventListener('change', syncLeadRequiredFields);

    document.getElementById('disposition')?.addEventListener('change', function () {
        syncCreateLeadAvailability({ autoCheck: true });
    });
    if (window.jQuery) {
        window.jQuery('#disposition').on('select2:select select2:clear change', function () {
            syncCreateLeadAvailability({ autoCheck: true });
        });
    }
    modalEl.addEventListener('shown.bs.modal', function () {
        syncCreateLeadAvailability({ autoCheck: false });
    });
    syncCreateLeadAvailability({ autoCheck: false });

    @if($openModal)
        modal.show();
    @endif

    @if(session('reopen_log_call'))
        modalEl.addEventListener('shown.bs.modal', function once() {
            resetFormDefaults();
            syncCreateLeadAvailability();
            modalEl.removeEventListener('shown.bs.modal', once);
        });
    @endif
})();

(function () {
    const logCallEl = document.getElementById('logCallModal');
    const quickModalEl = document.getElementById('quickAddProductModal');
    const openBtn = document.getElementById('btnQuickAddProduct');
    const nameInput = document.getElementById('quick_product_name');
    const errEl = document.getElementById('quickProductError');
    const saveBtn = document.getElementById('btnSaveQuickProduct');
    const productSelect = document.getElementById('product');
    if (!quickModalEl || !nameInput || !saveBtn || !productSelect) return;

    const quickModal = bootstrap.Modal.getOrCreateInstance(quickModalEl, {
        backdrop: 'static',
        keyboard: false,
        focus: true,
    });
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('#vrmCallForm input[name="_token"]')?.value
        || '';

    function showQuickError(msg) {
        if (!errEl) return;
        errEl.textContent = msg || '';
        errEl.classList.toggle('show', !!msg);
        nameInput.classList.toggle('is-invalid', !!msg);
    }

    function selectProduct(name) {
        let opt = Array.from(productSelect.options).find(function (o) {
            return o.value === name;
        });
        if (!opt) {
            opt = new Option(name, name, true, true);
            productSelect.add(opt);
        }
        productSelect.value = name;
        if (window.jQuery && window.jQuery(productSelect).hasClass('select2-hidden-accessible')) {
            window.jQuery(productSelect).val(name).trigger('change');
        }
    }

    function openQuickAdd(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        showQuickError('');
        nameInput.value = '';
        quickModal.show();
    }

    function closeQuickAdd(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        quickModal.hide();
    }

    async function saveQuickProduct(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        const name = (nameInput.value || '').trim();
        if (!name) {
            showQuickError('Please enter product name.');
            nameInput.focus();
            return;
        }
        showQuickError('');
        saveBtn.disabled = true;
        const original = saveBtn.innerHTML;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

        try {
            const res = await fetch(@json(route('goal.vrm.products.quick')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ product_name: name }),
            });
            const data = await res.json().catch(function () { return {}; });
            if (!res.ok) {
                const msg = data?.errors?.product_name?.[0]
                    || data?.message
                    || 'Unable to add product.';
                showQuickError(msg);
                return;
            }
            selectProduct(data.product_name);
            nameInput.value = '';
            quickModal.hide();
        } catch (err) {
            showQuickError('Unable to add product. Please try again.');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = original;
        }
    }

    // Do not close Log Call while Quick Add is open
    if (logCallEl) {
        logCallEl.addEventListener('hide.bs.modal', function (e) {
            if (quickModalEl.classList.contains('show')) {
                e.preventDefault();
            }
        });
    }

    quickModalEl.addEventListener('shown.bs.modal', function () {
        // Keep parent modal visible under the nested one
        if (logCallEl) {
            logCallEl.classList.add('show');
            logCallEl.style.display = 'block';
            logCallEl.removeAttribute('aria-hidden');
            logCallEl.setAttribute('aria-modal', 'true');
        }
        document.body.classList.add('modal-open');
        nameInput.focus();
    });

    quickModalEl.addEventListener('hidden.bs.modal', function () {
        if (logCallEl && logCallEl.classList.contains('show')) {
            document.body.classList.add('modal-open');
            const closeBtn = logCallEl.querySelector('.modal-close, .btn-close');
            if (closeBtn) closeBtn.focus();
        }
    });

    openBtn?.addEventListener('click', openQuickAdd);
    document.getElementById('btnCloseQuickProduct')?.addEventListener('click', closeQuickAdd);
    document.getElementById('btnCancelQuickProduct')?.addEventListener('click', closeQuickAdd);

    nameInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            e.stopPropagation();
            saveQuickProduct(e);
        }
    });
    nameInput.addEventListener('input', function () { showQuickError(''); });
    saveBtn.addEventListener('click', saveQuickProduct);
})();
</script>
@endpush
