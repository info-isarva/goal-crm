@extends('layouts.app')

@php
    $currency = config('app.currency_symbol', '₹');
    $fmt = fn ($n) => $currency . ' ' . number_format((float) $n, 2);
    $openAddModal = $errors->any() && old('_form') === 'create';
    $openEditModal = $errors->any() && old('_form') === 'edit';
@endphp

@push('styles')
@include('goal.partials.pd_hub_styles')
<style>
.goal-targets-page {
    --gt-gap: .85rem;
    --gt-control-h: 38px;
    color: #1e293b;
}
.goal-targets-page.pd-page .pd-hero {
    align-items: center;
    margin-bottom: var(--gt-gap);
}
.goal-targets-page .btn-gt-primary {
    height: var(--gt-control-h); border: none; border-radius: 10px; padding: 0 16px; font-weight: 700; font-size: .84rem;
    color: #fff; background: var(--theme-primary); box-shadow: 0 6px 14px var(--theme-primary-ring);
    display: inline-flex; align-items: center; justify-content: center; gap: .35rem;
    line-height: 1; white-space: nowrap;
}
.goal-targets-page .btn-gt-primary:hover { background: var(--theme-primary-hover); color: #fff; }
.goal-targets-page .btn-gt-ghost {
    height: var(--gt-control-h); border: 1px solid #dbe3f0; border-radius: 10px; padding: 0 14px; font-weight: 650; font-size: .82rem;
    color: #334155; background: #fff; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    line-height: 1; white-space: nowrap;
}
.goal-targets-page .btn-gt-ghost:hover { background: #f8fafc; color: #0f172a; }

.goal-targets-page .kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: var(--gt-gap);
    align-items: stretch;
}
@media (max-width: 1199.98px) { .goal-targets-page .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 575.98px) { .goal-targets-page .kpi-grid { grid-template-columns: 1fr; } }

.goal-targets-page .kpi-card {
    background: #fff; border: 1px solid #e8edf5; border-radius: 12px; padding: .85rem 1rem;
    box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 6px 14px rgba(148,163,184,.08);
    border-left: 3px solid var(--theme-primary);
    display: flex; flex-direction: column; justify-content: center; min-height: 88px;
}
.goal-targets-page .kpi-card.tone-sky { border-left-color: var(--theme-primary); }
.goal-targets-page .kpi-card.tone-emerald { border-left-color: #16a34a; }
.goal-targets-page .kpi-card.tone-amber { border-left-color: #ea580c; }
.goal-targets-page .kpi-card.tone-rose { border-left-color: #dc2626; }
.goal-targets-page .kpi-label { font-size: .68rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .03em; margin: 0; }
.goal-targets-page .kpi-value { margin: .25rem 0 0; font-size: 1.35rem; font-weight: 800; color: #0f172a; line-height: 1.15; letter-spacing: -.02em; }
.goal-targets-page .kpi-meta { margin: 4px 0 0; font-size: .75rem; color: #94a3b8; line-height: 1.3; min-height: 1.3em; }

.goal-targets-page .charts-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr);
    gap: 12px;
    margin-bottom: var(--gt-gap);
    align-items: stretch;
}
@media (max-width: 991.98px) { .goal-targets-page .charts-grid { grid-template-columns: 1fr; } }
.goal-targets-page .panel {
    background: #fff; border: 1px solid #e8edf5; border-radius: 14px;
    box-shadow: 0 6px 16px rgba(148,163,184,.08); overflow: hidden;
    display: flex; flex-direction: column; min-width: 0;
}
.goal-targets-page .panel-head {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;
    padding: .85rem 1.1rem; border-bottom: 1px solid #eef2f7; flex-shrink: 0;
}
.goal-targets-page .panel-head h5 { margin: 0; font-size: 1.02rem; font-weight: 800; color: #0f172a; line-height: 1.25; }
.goal-targets-page .panel-sub { font-size: .78rem; color: #94a3b8; margin-top: 2px; line-height: 1.3; }
.goal-targets-page .panel-body { padding: 14px 16px; flex: 1; display: flex; flex-direction: column; }
.goal-targets-page .chart-wrap { position: relative; height: 240px; width: 100%; flex: 1; }
.goal-targets-page .chart-wrap.sm { height: 240px; }
.goal-targets-page .chart-empty{
    position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;
    color:#94a3b8;font-size:.86rem;pointer-events:none;z-index:2;text-align:center;padding:12px;
}
.goal-targets-page .chart-empty i{font-size:1.5rem;margin-bottom:6px;opacity:.7;display:block}

/* Register table — aligned to Users list */
.goal-targets-page .gt-table-card {
    background: #fff; border: 1px solid #e8edf5; border-radius: 14px;
    box-shadow: 0 6px 16px rgba(148,163,184,.08); overflow: hidden; margin-bottom: var(--gt-gap);
}
.goal-targets-page .gt-table-head {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    padding: .85rem 1.1rem; border-bottom: 1px solid #eef2f7;
}
.goal-targets-page .gt-table-title {
    margin: 0; font-size: 1.02rem; font-weight: 800; color: #0f172a;
    display: inline-flex; align-items: center; gap: 10px; line-height: 1.25;
}
.goal-targets-page .gt-table-title i { color: var(--theme-primary); font-size: 1.1rem; }
.goal-targets-page .gt-table-meta { font-size: .78rem; color: #94a3b8; font-weight: 600; line-height: 1.3; }
.goal-targets-page .gt-table-wrap {
    overflow-x: auto; -webkit-overflow-scrolling: touch;
    padding: 0; width: 100%; max-width: 100%;
}
.goal-targets-page .gt-table {
    font-size: .875rem; width: 100%; min-width: 920px; margin: 0;
    border-collapse: separate; border-spacing: 0;
}
.goal-targets-page .gt-table thead th {
    background: #f5f6fb; color: #1a1c32; font-size: .78rem; font-weight: 700;
    text-transform: none; letter-spacing: 0; border-bottom: 1px solid #eef0f6; border-top: 0;
    white-space: nowrap; padding: 13px 14px; vertical-align: middle; text-align: left;
}
.goal-targets-page .gt-table thead th.col-amt,
.goal-targets-page .gt-table thead th.col-actions {
    text-align: right;
}
.goal-targets-page .gt-table thead th.col-num { text-align: left; }
.goal-targets-page .gt-table thead,
.goal-targets-page .gt-table tbody,
.goal-targets-page .gt-table tr {
    display: table-row-group;
}
.goal-targets-page .gt-table thead { display: table-header-group; }
.goal-targets-page .gt-table tr { display: table-row; }
.goal-targets-page .gt-table th,
.goal-targets-page .gt-table td {
    display: table-cell !important;
}
.goal-targets-page .gt-table tbody td {
    padding: 14px; border-bottom: 1px solid #eef0f6; border-top: 0;
    vertical-align: middle; color: #334155; background: #fff;
}
.goal-targets-page .gt-table tbody tr:last-child td { border-bottom: 0; }
.goal-targets-page .gt-table tbody tr:hover td { background: #f8fafc; }
.goal-targets-page .gt-table .col-num { width: 48px; color: #1a1c32; font-weight: 600; }
.goal-targets-page .gt-table .col-amt {
    text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap;
    font-weight: 600; color: #1a1c32;
}
.goal-targets-page .gt-table .col-amt.is-sf { color: #c2410c; }
.goal-targets-page .gt-table .col-actions { width: 72px; text-align: right; }
.goal-targets-page .gt-table td:nth-child(4) { min-width: 160px; }
.goal-targets-page .gt-owner-name { font-weight: 700; color: #1a1c32; line-height: 1.25; }
.goal-targets-page .gt-owner-sub { font-size: .75rem; color: #94a3b8; margin-top: 2px; }
.goal-targets-page .gt-progress-wrap { display: flex; align-items: center; gap: 10px; min-width: 120px; }
.goal-targets-page .gt-progress {
    flex: 1; height: 7px; border-radius: 999px; background: #e2e8f0; overflow: hidden; min-width: 72px;
}
.goal-targets-page .gt-progress > span {
    display: block; height: 100%; border-radius: inherit;
    background: var(--theme-primary);
}
.goal-targets-page .gt-progress-pct { font-size: .78rem; font-weight: 700; color: #64748b; min-width: 42px; }
.goal-targets-page .metric-pill {
    display: inline-flex; border-radius: 999px; padding: 5px 12px; font-size: .72rem; font-weight: 700; line-height: 1.2;
}
.goal-targets-page .metric-pill.is-revenue { background: var(--theme-100); color: var(--theme-primary-hover); }
.goal-targets-page .metric-pill.is-disbursement { background: #ecfdf5; color: #047857; }
.goal-targets-page .badge-status {
    display: inline-flex; align-items: center; gap: .35rem; border-radius: 999px;
    padding: 5px 12px; font-size: .72rem; font-weight: 700; line-height: 1.2;
}
.goal-targets-page .badge-status.is-approved { background: #d1fae5; color: #047857; }
.goal-targets-page .badge-status.is-draft { background: #f1f5f9; color: #475569; }
.goal-targets-page .badge-status .dot {
    width: 6px; height: 6px; border-radius: 50%; background: currentColor; display: inline-block;
}
.goal-targets-page .gt-action-btn {
    width: 36px; height: 36px; border: 0; border-radius: 10px;
    background: var(--theme-100); color: var(--theme-primary);
    display: inline-flex; align-items: center; justify-content: center;
}
.goal-targets-page .gt-action-btn:hover { background: var(--theme-200, var(--theme-100)); color: var(--theme-primary-hover); }
.goal-targets-page .gt-table-foot {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
    gap: 12px 16px; padding: 16px 20px; border-top: 1px solid #e8edf5; background: #fff;
}
.goal-targets-page .gt-showing { font-size: .84rem; color: #64748b; font-weight: 500; }
.goal-targets-page .gt-foot-right { display: flex; flex-wrap: wrap; align-items: center; gap: 16px 20px; }
.goal-targets-page .gt-rows-label {
    display: inline-flex; align-items: center; gap: 10px;
    font-size: .84rem; color: #64748b; font-weight: 500; margin: 0;
}
.goal-targets-page .gt-rows-label select {
    height: 34px; min-width: 56px; border: 1px solid #dbe2ea; border-radius: 8px;
    padding: 0 28px 0 12px; background: #fff; font-size: .84rem; font-weight: 600; color: #1a1c32;
    appearance: none; -webkit-appearance: none; cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 16 16'%3E%3Cpath fill='%2364748b' d='M4.5 6l3.5 3.5L11.5 6'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 10px center;
}
.goal-targets-page .gt-pager .pagination {
    list-style: none; margin: 0; padding: 0; gap: 6px;
    display: inline-flex; align-items: center;
}
.goal-targets-page .gt-pager .page-link {
    border: 0 !important; border-radius: 8px !important;
    color: #475569 !important; background: transparent !important;
    min-width: 34px; height: 34px;
    display: inline-flex !important; align-items: center; justify-content: center;
    padding: 0 10px !important; font-size: .84rem; font-weight: 600; text-decoration: none !important;
}
.goal-targets-page .gt-pager .page-link:hover { background: #f1f5f9 !important; color: #1a1c32 !important; }
.goal-targets-page .gt-pager .page-item.active .page-link {
    background: var(--theme-primary) !important; color: #fff !important;
    box-shadow: 0 4px 10px var(--theme-primary-ring) !important;
}
.goal-targets-page .gt-pager .page-item.us-page-arrow .page-link {
    background: var(--theme-100) !important; color: var(--theme-primary) !important;
}
.goal-targets-page .gt-pager .page-item.disabled .page-link {
    color: #cbd5e1 !important; pointer-events: none;
}
.goal-targets-page .gt-empty { color: #94a3b8; padding: 28px 12px; text-align: center; }
.goal-targets-page .gt-empty i { font-size: 1.8rem; display: block; margin-bottom: 6px; }

/* Keep real table layout (override global stacked-card rules) — same as Users */
@media (max-width: 1320.98px) {
    .goal-targets-page .gt-table-wrap.table-responsive > table.gt-table,
    .goal-targets-page .gt-table-wrap.table-responsive > table.gt-table thead,
    .goal-targets-page .gt-table-wrap.table-responsive > table.gt-table tbody,
    .goal-targets-page .gt-table-wrap.table-responsive > table.gt-table tr,
    .goal-targets-page .gt-table-wrap.table-responsive > table.gt-table th,
    .goal-targets-page .gt-table-wrap.table-responsive > table.gt-table td {
        display: table !important;
        width: auto !important;
        visibility: visible !important;
        height: auto !important;
        overflow: visible !important;
    }
    .goal-targets-page .gt-table-wrap.table-responsive > table.gt-table {
        display: table !important; width: 100% !important; min-width: 920px !important;
    }
    .goal-targets-page .gt-table-wrap.table-responsive > table.gt-table thead { display: table-header-group !important; }
    .goal-targets-page .gt-table-wrap.table-responsive > table.gt-table tbody { display: table-row-group !important; }
    .goal-targets-page .gt-table-wrap.table-responsive > table.gt-table tr {
        display: table-row !important; margin: 0 !important; border: 0 !important;
        border-radius: 0 !important; padding: 0 !important; background: transparent !important;
    }
    .goal-targets-page .gt-table-wrap.table-responsive > table.gt-table th,
    .goal-targets-page .gt-table-wrap.table-responsive > table.gt-table td {
        display: table-cell !important;
        width: auto !important;
        padding: 13px 14px !important;
        border-bottom: 1px solid #eef0f6 !important;
        justify-content: initial !important;
        align-items: initial !important;
        gap: 0 !important;
    }
    .goal-targets-page .gt-table-wrap.table-responsive > table.gt-table thead th {
        background: #f5f6fb !important;
        padding: 13px 14px !important;
    }
    .goal-targets-page .gt-table-wrap.table-responsive > table.gt-table tbody td { padding: 14px !important; }
    .goal-targets-page .gt-table-wrap.table-responsive > table.gt-table td:before,
    .goal-targets-page .gt-table-wrap.table-responsive > table.gt-table td::before {
        content: none !important;
        display: none !important;
    }
}
@media (max-width: 767.98px) {
    .goal-targets-page .gt-table-wrap { padding: 0; }
    .goal-targets-page .gt-table-head { padding: 14px 14px 10px; }
    .goal-targets-page .gt-table-foot { padding: 12px 14px; }
}

.goal-targets-page .filters-bar {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr)) max-content;
    gap: 12px;
    align-items: end;
    margin-bottom: var(--gt-gap);
    padding: 1rem 1.1rem;
    background: #fff; border: 1px solid #e8edf5; border-radius: 14px;
    box-shadow: 0 6px 16px rgba(148,163,184,.08);
    overflow: visible;
}
@media (max-width: 1399.98px) {
    .goal-targets-page .filters-bar {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .goal-targets-page .filters-bar .filter-actions {
        grid-column: 1 / -1;
        justify-content: flex-end;
    }
}
@media (max-width: 767.98px) {
    .goal-targets-page .filters-bar { grid-template-columns: 1fr 1fr; gap: 10px; padding: .85rem; }
    .goal-targets-page .filters-bar .filter-actions { grid-column: 1 / -1; justify-content: stretch; }
    .goal-targets-page .filters-bar .filter-actions-row { width: 100%; }
    .goal-targets-page .filters-bar .btn-gt-apply,
    .goal-targets-page .filters-bar .btn-gt-reset { flex: 1; justify-content: center; }
}
.goal-targets-page .filters-bar .gt-filter-field {
    min-width: 0;
    display: flex;
    flex-direction: column;
}
.goal-targets-page .filters-bar .form-label {
    font-size: .72rem; font-weight: 700; color: #64748b;
    margin: 0 0 6px; display: block; line-height: 1.2;
    text-transform: uppercase; letter-spacing: .03em;
}
.goal-targets-page .filters-bar .form-control,
.goal-targets-page .filters-bar .form-select {
    min-height: var(--gt-control-h) !important;
    height: var(--gt-control-h) !important;
    font-size: .84rem !important;
    border-radius: 10px !important;
    width: 100%;
}
.goal-targets-page .filters-bar .select2-container {
    width: 100% !important;
    max-width: 100%;
}
.goal-targets-page .filters-bar .select2-container--default .select2-selection--single {
    height: var(--gt-control-h) !important;
    min-height: var(--gt-control-h) !important;
    border-radius: 10px !important;
    border: 1px solid #d1d5db !important;
    display: flex !important;
    align-items: center !important;
}
.goal-targets-page .filters-bar .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 1.25 !important;
    padding-left: 12px !important;
    padding-right: 28px !important;
    font-size: .84rem !important;
    color: #0f172a !important;
    width: 100%;
}
.goal-targets-page .filters-bar .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 100% !important;
    top: 0 !important;
    right: 6px !important;
    display: flex; align-items: center; justify-content: center;
}
.goal-targets-page .filters-bar .select2-container--default .select2-selection--single .select2-selection__clear {
    margin-right: 18px !important;
    height: auto !important;
    line-height: 1 !important;
}
.goal-targets-page .filter-actions {
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    min-width: 0;
}
.goal-targets-page .filter-actions .form-label {
    visibility: hidden;
    pointer-events: none;
    user-select: none;
}
.goal-targets-page .filter-actions-row {
    display: flex;
    align-items: center;
    gap: 8px;
    height: var(--gt-control-h);
}
.goal-targets-page .btn-gt-apply {
    height: var(--gt-control-h); border: none; border-radius: 10px; padding: 0 16px; font-weight: 700; font-size: .82rem;
    color: #fff; background: var(--theme-primary);
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    line-height: 1; white-space: nowrap; box-shadow: 0 6px 14px var(--theme-primary-ring);
}
.goal-targets-page .btn-gt-apply:hover { background: var(--theme-primary-hover); color: #fff; }
.goal-targets-page .btn-gt-reset {
    height: var(--gt-control-h); border: 1px solid #dbe3f0; border-radius: 10px; padding: 0 14px; font-weight: 650; font-size: .82rem;
    color: #334155; background: #fff; text-decoration: none;
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    line-height: 1; white-space: nowrap; box-sizing: border-box;
}
.goal-targets-page .btn-gt-reset:hover { background: #f8fafc; color: #0f172a; }

.goal-targets-page .field-hint { font-size: .72rem; color: #94a3b8; margin-top: 4px; }
.goal-targets-page .invalid-feedback { display: block; }

/* Target modal — standalone like .ll-lead-modal (works if modal is on body) */
.gt-modal .modal-content{
    border:none;border-radius:16px;overflow:hidden;
    box-shadow:0 24px 48px rgba(15,23,42,.18);
}
.gt-modal .modal-header{
    border-bottom:1px solid #e8edf5;padding:18px 24px 16px;align-items:flex-start;background:#fff;
}
.gt-modal .modal-title-wrap{display:flex;align-items:flex-start;gap:12px}
.gt-modal .modal-icon{
    width:44px;height:44px;border-radius:12px;background:var(--theme-100);color:var(--theme-primary);
    display:inline-flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;
}
.gt-modal .modal-title{font-size:1.2rem;font-weight:800;color:#0f172a;margin:0;line-height:1.25}
.gt-modal .modal-sub{font-size:.86rem;color:#64748b;margin-top:3px;line-height:1.35}
.gt-modal .modal-close{
    width:36px;height:36px;border:0;border-radius:10px;background:#f1f5f9;color:#64748b;
    display:inline-flex;align-items:center;justify-content:center;margin-top:2px;
}
.gt-modal .modal-close:hover{background:#e2e8f0;color:#0f172a}
.gt-modal .modal-body{padding:18px 24px 12px}
.gt-modal .modal-footer{
    border-top:1px solid #e8edf5;padding:14px 24px;background:#fff;
    display:flex;align-items:center;justify-content:flex-end;gap:10px;
}
.gt-modal .section-bar{
    display:flex;align-items:center;gap:8px;
    background:var(--theme-50);color:var(--theme-primary-hover);border-radius:8px;
    padding:10px 14px;margin:0 0 14px;font-weight:700;font-size:.9rem;
}
.gt-modal .form-label{font-size:.8rem;font-weight:600;color:#334155;margin-bottom:6px}
.gt-modal .form-label .req,.gt-modal .req{color:#dc2626}
.gt-modal .form-label .gt-tip{color:#94a3b8;font-size:.78rem;cursor:help;margin-left:4px}
.gt-modal .form-control,
.gt-modal .form-select{
    min-height:42px;height:42px;border-radius:10px;border:1px solid #d1d5db;font-size:.9rem;background:#fff;
}
.gt-modal .form-control:focus,
.gt-modal .form-select:focus{
    border-color:var(--theme-300);box-shadow:0 0 0 3px var(--theme-primary-ring);
}
.gt-modal .input-icon{position:relative}
.gt-modal .input-icon > .rupee{
    position:absolute;left:12px;top:50%;transform:translateY(-50%);
    font-weight:700;font-style:normal;color:#64748b;font-size:.95rem;pointer-events:none;z-index:2;
}
.gt-modal .input-icon .form-control{padding-left:2.2rem}
.gt-modal .select2-container{width:100%!important}
.gt-modal .select2-container--default .select2-selection--single{
    min-height:42px!important;height:42px!important;border:1px solid #d1d5db!important;
    border-radius:10px!important;background:#fff!important;
}
.gt-modal .select2-container--default .select2-selection--single .select2-selection__rendered{
    line-height:40px!important;padding-left:.85rem!important;padding-right:2rem!important;font-size:.9rem!important;color:#0f172a!important;
}
.gt-modal .select2-container--default .select2-selection--single .select2-selection__arrow{height:40px!important}
.gt-modal .select2-container--default.select2-container--focus .select2-selection--single,
.gt-modal .select2-container--default.select2-container--open .select2-selection--single{
    border-color:var(--theme-300)!important;box-shadow:0 0 0 3px var(--theme-primary-ring);
}
.gt-modal .btn-save{
    background:var(--theme-primary);color:#fff;border:none;border-radius:10px;
    height:42px;padding:0 16px;font-weight:700;display:inline-flex;align-items:center;gap:6px;
}
.gt-modal .btn-save:hover{background:var(--theme-primary-hover);color:#fff}
.gt-modal .btn-cancel{
    border:1px solid var(--theme-300,#99f6e4);background:#fff;color:var(--theme-primary);border-radius:10px;
    height:42px;padding:0 14px;font-weight:600;display:inline-flex;align-items:center;
}
.gt-modal .btn-cancel:hover{background:#f8fafc;color:var(--theme-primary-hover)}
.gt-modal .gt-owner-toggle{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px}
@media (max-width:575.98px){.gt-modal .gt-owner-toggle{grid-template-columns:1fr}}
.gt-modal .gt-owner-opt{position:relative;margin:0;cursor:pointer;display:block}
.gt-modal .gt-owner-opt input{position:absolute;opacity:0;width:0;height:0;pointer-events:none}
.gt-modal .gt-owner-opt span{
    display:flex;align-items:center;justify-content:center;gap:8px;
    height:42px;border:1px solid #dbe3f0;border-radius:10px;
    background:#fff;color:#475569;font-size:.88rem;font-weight:650;
    transition:border-color .15s,background .15s,color .15s,box-shadow .15s;
}
.gt-modal .gt-owner-opt:hover span{border-color:#cbd5e1;background:#f8fafc}
.gt-modal .gt-owner-opt input:checked + span{
    border-color:var(--theme-primary);background:var(--theme-100,#f0fdfa);
    color:var(--theme-primary-hover,#0f766e);box-shadow:0 0 0 3px var(--theme-primary-ring);
}
.gt-modal .calc-box{
    display:grid;grid-template-columns:1fr 1fr;gap:10px;
    padding:12px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;
}
.gt-modal .calc-box .calc-item{
    background:#fff;border:1px solid #eef2f7;border-radius:10px;padding:10px 12px;min-width:0;
}
.gt-modal .calc-box .calc-label{
    font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin:0;
}
.gt-modal .calc-box .calc-value{
    margin-top:4px;font-size:1.05rem;font-weight:800;color:#0f172a;line-height:1.2;
    font-variant-numeric:tabular-nums;
}
.gt-modal .calc-box .calc-value.is-sf{color:#c2410c}
.gt-modal .row.g-3{--bs-gutter-x:1.1rem;--bs-gutter-y:1rem}
</style>
@endpush

@section('content')
<div class="container-fluid p-4 goal-targets-page pd-page">
    <div class="pd-hero">
        <div>
            <ul class="pd-crumbs">
                <li><a href="{{ url('/') }}">Home</a></li>
                <li class="sep">›</li>
                <li>Performance & Finance</li>
                <li class="sep">›</li>
                <li>Targets</li>
            </ul>
            <h1 class="pd-title">Targets & Carry-forward</h1>
            <p class="pd-sub">Track branch period targets, actuals and shortfall carry-forward.</p>
        </div>
        <div class="pd-hero-actions">
            <a href="{{ route('goal.targets.export', request()->query()) }}" class="pd-link-btn">
                <i class="bi bi-download"></i> Export CSV
            </a>
            @if($canManage)
                <a href="{{ route('goal.targets.set', request()->only('period', 'metric')) }}" class="pd-link-btn primary">
                    <i class="bi bi-diagram-3"></i> Set Targets
                </a>
            @endif
        </div>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card tone-sky">
            <div class="kpi-label">Targets</div>
            <div class="kpi-value">{{ number_format($stats['count']) }}</div>
            <div class="kpi-meta">{{ $stats['approved'] }} approved · {{ $stats['draft'] }} draft</div>
        </div>
        <div class="kpi-card tone-emerald">
            <div class="kpi-label">Effective Target</div>
            <div class="kpi-value">{{ $fmt($stats['total_effective']) }}</div>
            <div class="kpi-meta">Base + opening SF + adjustments</div>
        </div>
        <div class="kpi-card tone-amber">
            <div class="kpi-label">Actual Achieved</div>
            <div class="kpi-value">{{ $fmt($stats['total_actual']) }}</div>
            <div class="kpi-meta">{{ $stats['achievement_pct'] }}% of effective</div>
        </div>
        <div class="kpi-card tone-rose">
            <div class="kpi-label">Closing Shortfall</div>
            <div class="kpi-value">{{ $fmt($stats['total_closing']) }}</div>
            <div class="kpi-meta">Carry-forward to next period</div>
        </div>
    </div>

    <div class="charts-grid">
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h5>Target vs Actual by Period</h5>
                    <div class="panel-sub">Effective target compared with recorded actuals</div>
                </div>
            </div>
            <div class="panel-body">
                <div class="chart-wrap">
                    @if(empty($chart['labels'] ?? []))
                        <div class="chart-empty"><i class="bi bi-bar-chart"></i>No period data yet</div>
                    @endif
                    <canvas id="gtTrendChart" aria-label="Target vs actual chart"></canvas>
                </div>
            </div>
        </div>
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h5>Effective by Metric</h5>
                    <div class="panel-sub">Revenue vs disbursement mix</div>
                </div>
            </div>
            <div class="panel-body">
                <div class="chart-wrap sm">
                    @if(empty($metricChart['labels'] ?? []))
                        <div class="chart-empty"><i class="bi bi-pie-chart"></i>No metric mix yet</div>
                    @endif
                    <canvas id="gtMetricChart" aria-label="Metric mix chart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('goal.targets.index') }}" class="filters-bar">
        <input type="hidden" name="per_page" value="{{ $filters['per_page'] ?? 10 }}">
        <div class="gt-filter-field">
            <label class="form-label" for="filter_period">Period</label>
            <select name="period" id="filter_period" class="form-select" data-search="true" data-allow-clear="false" data-placeholder="All periods">
                <option value="">All periods</option>
                @foreach($periods as $p)
                    <option value="{{ $p }}" @selected($filters['period'] === $p)>{{ $p }}</option>
                @endforeach
            </select>
        </div>
        <div class="gt-filter-field">
            <label class="form-label" for="filter_metric">Metric</label>
            <select name="metric" id="filter_metric" class="form-select" data-search="false" data-allow-clear="false" data-placeholder="All metrics">
                <option value="">All metrics</option>
                <option value="revenue" @selected($filters['metric'] === 'revenue')>Revenue</option>
                <option value="disbursement" @selected($filters['metric'] === 'disbursement')>Disbursement</option>
            </select>
        </div>
        <div class="gt-filter-field">
            <label class="form-label" for="filter_owner_type">Owner type</label>
            <select name="owner_type" id="filter_owner_type" class="form-select" data-search="false" data-allow-clear="false" data-placeholder="All owners">
                <option value="">All owners</option>
                <option value="employee" @selected(($filters['owner_type'] ?? '') === 'employee')>Team member</option>
                <option value="org_unit" @selected(($filters['owner_type'] ?? '') === 'org_unit')>Org unit</option>
            </select>
        </div>
        <div class="gt-filter-field">
            <label class="form-label" for="filter_branch">Org unit</label>
            <select name="goal_org_unit_id" id="filter_branch" class="form-select" data-search="true" data-allow-clear="false" data-placeholder="All org units">
                <option value="">All org units</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" @selected((string) $filters['goal_org_unit_id'] === (string) $b->id)>
                        {{ strtoupper($b->type) }} · {{ $b->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="gt-filter-field">
            <label class="form-label" for="filter_user">Team member</label>
            <select name="user_id" id="filter_user" class="form-select" data-search="true" data-allow-clear="false" data-placeholder="All people">
                <option value="">All people</option>
                @foreach($teamUsers as $u)
                    <option value="{{ $u->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="gt-filter-field">
            <label class="form-label" for="filter_status">Status</label>
            <select name="status" id="filter_status" class="form-select" data-search="false" data-allow-clear="false" data-placeholder="All status">
                <option value="">All status</option>
                <option value="Draft" @selected($filters['status'] === 'Draft')>Draft</option>
                <option value="Approved" @selected($filters['status'] === 'Approved')>Approved</option>
            </select>
        </div>
        <div class="filter-actions">
            <label class="form-label" aria-hidden="true">&nbsp;</label>
            <div class="filter-actions-row">
                <button type="submit" class="btn-gt-apply"><i class="bi bi-funnel"></i> Apply</button>
                <a href="{{ route('goal.targets.index') }}" class="btn-gt-reset">Reset</a>
            </div>
        </div>
    </form>

        <div class="gt-table-card">
            <div class="gt-table-head">
                <h3 class="gt-table-title"><i class="bi bi-list-ul"></i> Target Register ({{ $targets->total() }})</h3>
                <span class="gt-table-meta">Period targets · carry-forward</span>
            </div>

            <div class="table-responsive gt-table-wrap">
                <table class="table align-middle mb-0 gt-table" id="targetsRegisterTable">
                    <thead>
                        <tr>
                            <th class="col-num">#</th>
                            <th>Period</th>
                            <th>Metric</th>
                            <th>Owner</th>
                            <th class="col-amt">Effective</th>
                            <th class="col-amt">Actual</th>
                            <th>Progress</th>
                            <th class="col-amt">Closing SF</th>
                            <th>Status</th>
                            @if($canManage)<th class="col-actions">Actions</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($targets as $t)
                        @php
                            $effective = $t->effectiveTarget();
                            $pct = $effective > 0 ? min(100, round(((float) $t->actual / $effective) * 100, 1)) : 0;
                            $ownerType = $t->user_id ? 'employee' : 'org_unit';
                            $ownerLabel = $t->user_id
                                ? ($t->user?->name ?? ('User #'.$t->user_id))
                                : ($t->orgUnit?->name ?? '—');
                        @endphp
                        <tr>
                            <td class="col-num">{{ $targets->firstItem() + $loop->index }}</td>
                            <td><span class="fw-semibold text-dark">{{ $t->period }}</span></td>
                            <td>
                                <span class="metric-pill is-{{ $t->metric }}">{{ ucfirst($t->metric) }}</span>
                            </td>
                            <td>
                                <div class="gt-owner-name">{{ $ownerLabel }}</div>
                                <div class="gt-owner-sub">{{ $ownerType === 'employee' ? 'Team member' : 'Org unit' }}</div>
                            </td>
                            <td class="col-amt">{{ number_format($effective, 2) }}</td>
                            <td class="col-amt">{{ number_format((float) $t->actual, 2) }}</td>
                            <td>
                                <div class="gt-progress-wrap" title="{{ $pct }}%">
                                    <div class="gt-progress"><span style="width: {{ $pct }}%"></span></div>
                                    <span class="gt-progress-pct">{{ $pct }}%</span>
                                </div>
                            </td>
                            <td class="col-amt {{ (float) $t->closing_shortfall > 0 ? 'is-sf' : '' }}">
                                {{ number_format((float) $t->closing_shortfall, 2) }}
                            </td>
                            <td>
                                <span class="badge-status {{ $t->status === 'Approved' ? 'is-approved' : 'is-draft' }}">
                                    <span class="dot"></span>{{ $t->status }}
                                </span>
                            </td>
                            @if($canManage)
                            <td class="col-actions">
                                <button type="button"
                                    class="gt-action-btn btn-edit-target"
                                    title="Edit target"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editTargetModal"
                                    data-id="{{ $t->id }}"
                                    data-action="{{ route('goal.targets.update', $t) }}"
                                    data-owner-type="{{ $ownerType }}"
                                    data-period="{{ $t->period }}"
                                    data-metric="{{ $t->metric }}"
                                    data-org="{{ $t->goal_org_unit_id }}"
                                    data-user="{{ $t->user_id }}"
                                    data-base="{{ $t->base_target }}"
                                    data-opening="{{ $t->opening_shortfall }}"
                                    data-adjustments="{{ $t->adjustments }}"
                                    data-actual="{{ $t->actual }}"
                                    data-status="{{ $t->status }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 10 : 9 }}" class="text-center">
                                <div class="gt-empty">
                                    <i class="bi bi-inbox"></i>
                                    <div>No targets match your filters.</div>
                                    @if($canManage)
                                        <a href="{{ route('goal.targets.set') }}" class="btn-gt-primary mt-3">
                                            <i class="bi bi-diagram-3"></i> Set Targets
                                        </a>
                                    @else
                                        <a href="{{ route('goal.targets.index') }}" class="btn-gt-reset mt-2 d-inline-flex">Reset filters</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($targets->total() > 0)
                <div class="gt-table-foot">
                    <div class="gt-showing">
                        Showing {{ $targets->firstItem() }} to {{ $targets->lastItem() }} of {{ $targets->total() }} results
                    </div>
                    <div class="gt-foot-right">
                        <form method="GET" action="{{ route('goal.targets.index') }}" class="gt-rows-label" id="gtRowsForm">
                            @foreach(request()->except(['per_page', 'page']) as $key => $val)
                                @if(is_array($val))
                                    @foreach($val as $v)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                                @endif
                            @endforeach
                            <span>Rows per page:</span>
                            <select name="per_page" class="form-select-compact" data-select2-skip="1" onchange="this.form.submit()" aria-label="Rows per page">
                                @foreach([10, 25, 50, 100] as $n)
                                    <option value="{{ $n }}" @selected((int) ($filters['per_page'] ?? 10) === $n)>{{ $n }}</option>
                                @endforeach
                            </select>
                        </form>
                        <div class="gt-pager">
                            {{ $targets->onEachSide(1)->links('vendor.pagination.users') }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
</div>

@if($canManage)
{{-- Modals outside page wrap so styles match Leads (.gt-modal standalone) --}}
<div class="modal fade gt-modal" id="addTargetModal" tabindex="-1" aria-labelledby="addTargetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('goal.targets.store') }}" id="addTargetForm" novalidate>
                @csrf
                <input type="hidden" name="_form" value="create">
                <div class="modal-header">
                    <div class="modal-title-wrap">
                        <span class="modal-icon"><i class="bi bi-bullseye"></i></span>
                        <div>
                            <h5 class="modal-title" id="addTargetModalLabel">Add Target</h5>
                            <div class="modal-sub">Set period target, actuals and shortfall carry-forward.</div>
                        </div>
                    </div>
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body">
                    @include('goal.targets._form_fields', [
                        'prefix' => 'add',
                        'values' => [
                            'owner_type' => old('_form') === 'create' ? old('owner_type', 'employee') : 'employee',
                            'period' => old('_form') === 'create' ? old('period', now()->format('Y-m')) : now()->format('Y-m'),
                            'metric' => old('_form') === 'create' ? old('metric', 'revenue') : 'revenue',
                            'goal_org_unit_id' => old('_form') === 'create' ? old('goal_org_unit_id') : '',
                            'user_id' => old('_form') === 'create' ? old('user_id') : '',
                            'base_target' => old('_form') === 'create' ? old('base_target') : '',
                            'opening_shortfall' => old('_form') === 'create' ? old('opening_shortfall', '0') : '0',
                            'adjustments' => old('_form') === 'create' ? old('adjustments', '0') : '0',
                            'actual' => old('_form') === 'create' ? old('actual', '0') : '0',
                            'status' => old('_form') === 'create' ? old('status', 'Draft') : 'Draft',
                        ],
                        'branches' => $branches,
                        'teamUsers' => $teamUsers,
                        'canApprove' => $canApprove,
                        'showErrors' => old('_form') === 'create',
                    ])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-save"><i class="bi bi-check2-circle"></i> Save Target</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade gt-modal" id="editTargetModal" tabindex="-1" aria-labelledby="editTargetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ (old('_form') === 'edit' && old('target_id')) ? route('goal.targets.update', old('target_id')) : '#' }}" id="editTargetForm" novalidate>
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit">
                <input type="hidden" name="target_id" id="edit_target_id" value="{{ old('target_id') }}">
                <div class="modal-header">
                    <div class="modal-title-wrap">
                        <span class="modal-icon"><i class="bi bi-pencil-square"></i></span>
                        <div>
                            <h5 class="modal-title" id="editTargetModalLabel">Edit Target</h5>
                            <div class="modal-sub">Update period target, actuals and shortfall.</div>
                        </div>
                    </div>
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body">
                    @include('goal.targets._form_fields', [
                        'prefix' => 'edit',
                        'values' => [
                            'owner_type' => old('_form') === 'edit' ? old('owner_type', 'employee') : 'employee',
                            'period' => old('_form') === 'edit' ? old('period') : '',
                            'metric' => old('_form') === 'edit' ? old('metric', 'revenue') : 'revenue',
                            'goal_org_unit_id' => old('_form') === 'edit' ? old('goal_org_unit_id') : '',
                            'user_id' => old('_form') === 'edit' ? old('user_id') : '',
                            'base_target' => old('_form') === 'edit' ? old('base_target') : '',
                            'opening_shortfall' => old('_form') === 'edit' ? old('opening_shortfall', '0') : '0',
                            'adjustments' => old('_form') === 'edit' ? old('adjustments', '0') : '0',
                            'actual' => old('_form') === 'edit' ? old('actual', '0') : '0',
                            'status' => old('_form') === 'edit' ? old('status', 'Draft') : 'Draft',
                        ],
                        'branches' => $branches,
                        'teamUsers' => $teamUsers,
                        'canApprove' => $canApprove,
                        'showErrors' => old('_form') === 'edit',
                    ])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-save"><i class="bi bi-check2-circle"></i> Update Target</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    const currency = @json($currency);
    const trend = @json($chart);
    const metricChart = @json($metricChart);
    const openAdd = @json($openAddModal);
    const openEdit = @json($openEditModal);

    function money(n) {
        return currency + ' ' + (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function num(el) {
        if (!el) return 0;
        const raw = String(el.value || '').replace(/,/g, '').trim();
        const n = parseFloat(raw);
        return Number.isFinite(n) ? n : 0;
    }

    function bindCalc(prefix) {
        const fields = ['base_target', 'opening_shortfall', 'adjustments', 'actual'].map(function (k) {
            return document.getElementById(prefix + '_' + k);
        });
        const effEl = document.getElementById(prefix + '_calc_effective');
        const closeEl = document.getElementById(prefix + '_calc_closing');
        function refresh() {
            const base = num(fields[0]);
            const opening = num(fields[1]);
            const adj = num(fields[2]);
            const actual = num(fields[3]);
            const effective = base + opening + adj;
            const closing = Math.max(0, effective - actual);
            if (effEl) effEl.textContent = money(effective);
            if (closeEl) closeEl.textContent = money(closing);
        }
        fields.forEach(function (el) {
            el?.addEventListener('input', refresh);
            el?.addEventListener('change', refresh);
        });
        refresh();
    }

    function setSelectValue(el, value) {
        if (!el) return;
        el.value = value == null ? '' : String(value);
        if (window.jQuery && window.jQuery(el).hasClass('select2-hidden-accessible')) {
            window.jQuery(el).val(el.value).trigger('change');
        }
    }

    function setMonthValue(el, value) {
        if (!el) return;
        const v = value == null ? '' : String(value);
        if (el._flatpickr) {
            if (v) el._flatpickr.setDate(v, true);
            else el._flatpickr.clear();
            return;
        }
        el.value = v;
    }

    function syncOwnerType(prefix, type) {
        const isEmployee = type === 'employee';
        document.querySelectorAll('.owner-employee-wrap[data-prefix="' + prefix + '"]').forEach(function (el) {
            el.style.display = isEmployee ? '' : 'none';
        });
        document.querySelectorAll('.owner-org-wrap[data-prefix="' + prefix + '"]').forEach(function (el) {
            el.style.display = isEmployee ? 'none' : '';
        });
        const userSel = document.getElementById(prefix + '_user_id');
        const orgSel = document.getElementById(prefix + '_goal_org_unit_id');
        if (isEmployee) {
            setSelectValue(orgSel, '');
        } else {
            setSelectValue(userSel, '');
        }
        const empRadio = document.getElementById(prefix + '_owner_employee');
        const orgRadio = document.getElementById(prefix + '_owner_org');
        if (empRadio) empRadio.checked = isEmployee;
        if (orgRadio) orgRadio.checked = !isEmployee;
    }

    document.querySelectorAll('.owner-type-radio').forEach(function (radio) {
        radio.addEventListener('change', function () {
            if (!radio.checked) return;
            syncOwnerType(radio.getAttribute('data-prefix'), radio.value);
        });
    });
    syncOwnerType('add', document.querySelector('#add_owner_employee')?.checked ? 'employee' : 'org_unit');
    syncOwnerType('edit', document.querySelector('#edit_owner_employee')?.checked ? 'employee' : 'org_unit');

    function validateForm(form, prefix) {
        const period = form.querySelector('[name="period"]');
        const base = form.querySelector('[name="base_target"]');
        const metric = form.querySelector('[name="metric"]');
        const ownerType = (form.querySelector('input[name="owner_type"]:checked') || {}).value;
        const userSel = form.querySelector('[name="user_id"]');
        const orgSel = form.querySelector('[name="goal_org_unit_id"]');
        let ok = true;
        let firstMsg = '';

        function fail(el, msg) {
            ok = false;
            if (el) el.classList.add('is-invalid');
            if (!firstMsg) firstMsg = msg;
        }
        function clear(el) {
            el?.classList.remove('is-invalid');
        }

        [period, base, metric, userSel, orgSel].forEach(clear);

        if (!ownerType) {
            fail(null, 'Select whether the target is for a team member or a branch.');
        }
        if (ownerType === 'employee' && (!userSel || !userSel.value)) {
            fail(userSel, 'Please select a team member.');
        }
        if (ownerType === 'org_unit' && (!orgSel || !orgSel.value)) {
            fail(orgSel, 'Please select a branch.');
        }
        if (!period || !/^\d{4}-(0[1-9]|1[0-2])$/.test(String(period.value || '').trim())) {
            fail(period, 'Period must be YYYY-MM (e.g. 2026-11).');
        }
        if (!metric || !metric.value) {
            fail(metric, 'Please select a metric.');
        }
        if (!base || String(base.value).trim() === '' || Number(String(base.value).replace(/,/g, '')) < 0 || Number.isNaN(Number(String(base.value).replace(/,/g, '')))) {
            fail(base, 'Base target is required and must be a non-negative number.');
        }

        ['opening_shortfall', 'actual'].forEach(function (name) {
            const el = form.querySelector('[name="' + name + '"]');
            clear(el);
            if (el && String(el.value).trim() !== '') {
                const v = Number(String(el.value).replace(/,/g, ''));
                if (Number.isNaN(v) || v < 0) fail(el, name.replace('_', ' ') + ' cannot be negative.');
            }
        });

        const adj = form.querySelector('[name="adjustments"]');
        clear(adj);
        if (adj && String(adj.value).trim() !== '' && Number.isNaN(Number(String(adj.value).replace(/,/g, '')))) {
            fail(adj, 'Adjustments must be a valid number.');
        }

        if (!ok && typeof showFlashSwal === 'function') {
            showFlashSwal('error', firstMsg);
        } else if (!ok && window.Swal) {
            Swal.fire({ icon: 'error', title: 'Validation', text: firstMsg, confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--theme-primary').trim() || '#0f766e' });
        }
        return ok;
    }

    // Charts — Goal theme greens
    const css = getComputedStyle(document.documentElement);
    const themePrimary = (css.getPropertyValue('--theme-primary').trim() || '#0f766e');
    const themeHover = (css.getPropertyValue('--theme-primary-hover').trim() || '#0d9488');
    const trendEl = document.getElementById('gtTrendChart');
    if (trendEl && window.Chart) {
        const hasTrend = (trend.labels || []).length > 0;
        if (hasTrend) {
            new Chart(trendEl, {
                type: 'bar',
                data: {
                    labels: trend.labels,
                    datasets: [
                        {
                            label: 'Effective',
                            data: trend.effective,
                            backgroundColor: themePrimary,
                            borderRadius: 6,
                            maxBarThickness: 28
                        },
                        {
                            label: 'Actual',
                            data: trend.actual,
                            backgroundColor: '#16a34a',
                            borderRadius: 6,
                            maxBarThickness: 28
                        },
                        {
                            label: 'Closing SF',
                            data: trend.closing,
                            type: 'line',
                            borderColor: '#ea580c',
                            backgroundColor: 'rgba(234,88,12,.12)',
                            tension: .35,
                            yAxisID: 'y'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true } },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) { return ctx.dataset.label + ': ' + money(ctx.parsed.y); }
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (v) {
                                    if (v >= 10000000) return (v / 10000000).toFixed(1) + 'Cr';
                                    if (v >= 100000) return (v / 100000).toFixed(1) + 'L';
                                    if (v >= 1000) return (v / 1000).toFixed(0) + 'K';
                                    return v;
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    const metricEl = document.getElementById('gtMetricChart');
    if (metricEl && window.Chart) {
        const hasMetric = (metricChart.labels || []).length > 0;
        if (hasMetric) {
            new Chart(metricEl, {
                type: 'doughnut',
                data: {
                    labels: metricChart.labels,
                    datasets: [{
                        data: metricChart.values,
                        backgroundColor: [themePrimary, themeHover, '#14b8a6'],
                        borderWidth: 0,
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true } },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    const val = ctx.parsed || 0;
                                    return ' ' + ctx.label + ': ' + money(val);
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    @if($canManage)
    bindCalc('add');
    bindCalc('edit');

    document.querySelectorAll('.btn-edit-target').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const form = document.getElementById('editTargetForm');
            if (!form) return;
            form.action = btn.getAttribute('data-action');
            document.getElementById('edit_target_id').value = btn.getAttribute('data-id') || '';
            const ownerType = btn.getAttribute('data-owner-type') || 'org_unit';
            syncOwnerType('edit', ownerType);
            setMonthValue(document.getElementById('edit_period'), btn.getAttribute('data-period') || '');
            setSelectValue(document.getElementById('edit_metric'), btn.getAttribute('data-metric') || 'revenue');
            setSelectValue(document.getElementById('edit_goal_org_unit_id'), btn.getAttribute('data-org') || '');
            setSelectValue(document.getElementById('edit_user_id'), btn.getAttribute('data-user') || '');
            document.getElementById('edit_base_target').value = btn.getAttribute('data-base') || '0';
            document.getElementById('edit_opening_shortfall').value = btn.getAttribute('data-opening') || '0';
            document.getElementById('edit_adjustments').value = btn.getAttribute('data-adjustments') || '0';
            document.getElementById('edit_actual').value = btn.getAttribute('data-actual') || '0';
            const statusEl = document.getElementById('edit_status');
            if (statusEl && statusEl.tagName === 'SELECT') {
                setSelectValue(statusEl, btn.getAttribute('data-status') || 'Draft');
            }
            form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
            bindCalc('edit');
        });
    });

    const addForm = document.getElementById('addTargetForm');
    addForm?.addEventListener('submit', function (e) {
        if (!validateForm(addForm, 'add')) e.preventDefault();
    });
    const editForm = document.getElementById('editTargetForm');
    editForm?.addEventListener('submit', function (e) {
        if (!validateForm(editForm, 'edit')) e.preventDefault();
    });

    if (openAdd) {
        const modal = new bootstrap.Modal(document.getElementById('addTargetModal'));
        modal.show();
    }
    if (openEdit) {
        const modal = new bootstrap.Modal(document.getElementById('editTargetModal'));
        modal.show();
    }
    @endif

})();
</script>
@endpush
