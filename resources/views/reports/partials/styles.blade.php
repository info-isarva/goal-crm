<style>
.report-shell {
    background: #f4f6f8;
    min-height: calc(100vh - 120px);
    margin: 0;
    padding: 0;
    width: 100%;
    max-width: 100%;
    overflow: visible;
    box-sizing: border-box;
}
.report-sticky-top {
    position: sticky;
    top: 70px; /* below .top-slim-header */
    z-index: 1040;
    background: #fff;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.08);
}
.report-page-header {
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.report-page-header .report-back-btn {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background: #fff;
    color: #374151;
    text-decoration: none;
}
.report-page-header .report-back-btn:hover { background: #f9fafb; color: #111827; }
.report-page-header .report-page-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #111827;
}
.report-toolbar {
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
    padding: 14px 20px;
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 12px 16px;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}
.report-toolbar .filters-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    color: #374151;
    font-size: 14px;
    align-self: center;
    padding-bottom: 8px;
}
.report-toolbar .filters-label .bi { color: #6b7280; }
.report-toolbar .toolbar-field {
    display: inline-flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
    flex: 0 0 auto;
}
.report-toolbar .toolbar-field label {
    margin: 0;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    white-space: nowrap;
    letter-spacing: 0.02em;
    line-height: 1.2;
}
.report-shell .report-toolbar .toolbar-field .form-select,
.report-shell .report-toolbar .toolbar-field .form-control {
    width: 170px;
    max-width: 200px;
    min-width: 140px;
    padding: 7px 28px 7px 12px !important;
    border: 1px solid #d1d5db !important;
    border-radius: 6px !important;
    font-size: 14px !important;
    color: #111827;
    background-color: #fff !important;
    line-height: 1.4 !important;
    box-shadow: none !important;
    height: auto;
}
.report-shell .report-toolbar .toolbar-field .form-select:focus,
.report-shell .report-toolbar .toolbar-field .form-control:focus {
    border-color: #93c5fd !important;
    box-shadow: none !important;
    outline: none;
}
.report-toolbar .btn-run-report {
    flex-shrink: 0;
}
.report-toolbar .btn-run-report:last-child {
    margin-left: auto;
}
.report-actions-top {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 8px;
}
.btn-run-report {
    background: #2563eb;
    color: #fff;
    border: 0;
    border-radius: 6px;
    padding: 8px 18px;
    font-weight: 600;
    font-size: 14px;
}
.btn-run-report:hover { background: #1d4ed8; color: #fff; }
.btn-export-report {
    background: #16a34a;
    color: #fff !important;
    border: 1px solid #16a34a;
    border-radius: 6px;
    padding: 8px 14px;
    font-weight: 600;
    font-size: 14px;
    line-height: 1.2;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.btn-export-report:hover {
    background: #15803d;
    border-color: #15803d;
    color: #fff !important;
}
.report-body {
    padding: 16px 16px 24px;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    overflow-x: hidden;
}
.report-paper {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
    width: 100%;
    max-width: 100%;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
}
.report-meta {
    text-align: center;
    padding: 28px 24px 22px;
    border-bottom: 1px solid #eef2f7;
    background: linear-gradient(180deg, #fafbfc 0%, #fff 100%);
}
.report-meta.report-meta-with-asides {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    text-align: left;
}
.report-meta-center {
    flex: 1 1 auto;
    text-align: center;
    min-width: 0;
}
.report-meta-aside {
    flex: 0 0 auto;
    min-width: 140px;
}
.report-meta-aside-right {
    text-align: right;
}
.report-meta-stat {
    display: inline-block;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px 14px;
    min-width: 140px;
}
.report-meta-stat .label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 2px;
}
.report-meta-stat .value {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}
.report-meta-stat.amount .value {
    color: #0f766e;
}
.report-meta .company {
    font-size: 13px;
    color: #64748b;
    margin-bottom: 6px;
    letter-spacing: 0.02em;
    text-transform: uppercase;
}
.report-meta .title {
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 6px;
    line-height: 1.3;
}
.report-meta .period {
    font-size: 14px;
    color: #64748b;
    font-weight: 500;
}
.report-content-pad {
    padding: 16px 16px 0;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}
.report-chart-wrap {
    position: relative;
    width: 100%;
    max-width: 100%;
    max-height: 420px;
    padding: 16px 16px 8px;
    box-sizing: border-box;
}
.report-chart-wrap-lg {
    min-height: 640px;
    height: 640px;
    max-height: none;
    padding: 16px 16px 24px;
}
.report-chart-wrap canvas {
    width: 100% !important;
    max-height: 380px;
}
.report-chart-wrap-lg canvas {
    height: 100% !important;
    max-height: none;
}
.report-table-wrap {
    padding: 20px 24px 28px;
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
    box-sizing: border-box;
}
.report-table {
    width: 100% !important;
    max-width: 100%;
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
    table-layout: auto;
    font-size: 14px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    background: #fff;
}
.report-table thead th {
    background: #f8fafc;
    color: #475569;
    font-weight: 700;
    font-size: 11px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 14px 18px;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
    vertical-align: middle;
}
.report-table thead th.is-number,
.report-table tbody td.is-number,
.report-table tfoot td.is-number {
    text-align: right;
    font-variant-numeric: tabular-nums;
}
.report-table tbody td {
    padding: 15px 18px;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
    vertical-align: middle;
    line-height: 1.45;
}
.report-table tbody td.employee-cell,
.report-table tfoot td.employee-cell {
    font-weight: 600;
    color: #0f172a;
}
.report-table tfoot td {
    padding: 15px 18px;
    vertical-align: middle;
    border-top: 2px solid #bfdbfe;
}
.report-table tfoot tr.total-row td {
    background: #eff6ff;
    color: #0f172a;
    font-weight: 700;
    font-size: 14px;
}
.report-table .achievement-cell {
    min-width: 130px;
    padding-top: 12px;
    padding-bottom: 12px;
}
.report-table .achievement-cell .progress {
    height: 22px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
    min-width: 110px;
    max-width: 150px;
    margin-left: auto;
    box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.06);
}
.report-table .achievement-cell .progress-bar {
    font-size: 11px;
    font-weight: 700;
    line-height: 22px;
}
.report-table tbody tr:hover { background: #f8fafc; }
.report-table tbody tr:last-child td { border-bottom: 1px solid #f1f5f9; }
.report-table a { color: #2563eb; font-weight: 600; text-decoration: none; }
.report-table a:hover { text-decoration: underline; color: #1d4ed8; }
.monthly-target-table thead th:first-child,
.monthly-target-table tbody td:first-child,
.monthly-target-table tfoot td:first-child {
    padding-left: 22px;
}
.monthly-target-table thead th:last-child,
.monthly-target-table tbody td:last-child,
.monthly-target-table tfoot td:last-child {
    padding-right: 22px;
}
.monthly-target-table tbody td.is-number:not(.achievement-cell) {
    white-space: nowrap;
}
.report-table .source-cell {
    font-weight: 600;
    color: #1f2937;
    background: #fcfdfe;
}
.report-empty {
    text-align: center;
    padding: 48px 24px;
    color: #64748b;
    font-size: 14px;
}
.report-charts { padding: 16px; }
.report-charts .graph-card { height: 100%; }

.pagination-custom { display: flex; justify-content: flex-end; }
.pagination-custom .pagination { margin: 0; }
.pagination-custom .page-item .page-link {
    border-radius: 8px;
    border: 1px solid #e6e9ef;
    color: #232323;
    padding: 8px 14px;
    background: #fff;
}
.pagination-custom .page-item.disabled .page-link {
    color: #bfc6d1;
    background: #fff;
    border-color: #f0f2f5;
}
.pagination-custom .page-link:focus, .pagination-custom .page-link:hover { box-shadow: none; }

.drp-wrap {
    position: relative;
    display: inline-flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
}
.drp-wrap .drp-label {
    font-weight: 600;
    color: #64748b;
    white-space: nowrap;
    font-size: 12px;
    letter-spacing: 0.02em;
    line-height: 1.2;
}
.drp-trigger {
    min-width: 170px;
    display: inline-flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 7px 14px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: #fff;
    color: #111827;
    font-size: 14px;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}
.drp-trigger:hover { border-color: #93c5fd; }
.drp-trigger .bi-chevron-down { font-size: 12px; color: #6b7280; }
.drp-panel {
    display: none;
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    z-index: 1050;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 12px 32px rgba(15, 23, 42, 0.14);
    overflow: hidden;
    max-width: min(740px, calc(100vw - 40px));
}
.drp-panel.open { display: flex; }
.drp-presets {
    width: 150px;
    flex: 0 0 150px;
    border-right: 1px solid #e5e7eb;
    max-height: 380px;
    overflow-y: auto;
    padding: 8px 0;
    background: #fff;
}
.drp-preset {
    display: block;
    width: 100%;
    text-align: left;
    border: 0;
    background: transparent;
    padding: 10px 18px;
    font-size: 14px;
    color: #374151;
    cursor: pointer;
    font-weight: 400;
}
.drp-preset:hover { background: #f3f4f6; }
.drp-preset.active {
    background: #edf2ff;
    color: #2563eb;
    font-weight: 600;
}
.drp-calendar-pane {
    display: none;
    width: 540px;
    max-width: 100%;
    flex: 1 1 auto;
    padding: 16px;
}
.drp-calendar-pane.show { display: block; }
.drp-inputs { display: flex; gap: 12px; margin-bottom: 14px; }
.drp-inputs .drp-input {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid #3b82f6;
    border-radius: 8px;
    padding: 8px 12px;
    background: #fff;
}
.drp-inputs .drp-input input {
    border: 0; outline: none; width: 100%; font-size: 14px; color: #111827; background: transparent; font-weight: 500;
}
.drp-inputs .drp-input .bi { color: #6b7280; font-size: 16px; }
.drp-inputs .drp-input.active-field { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.18); }
.drp-calendars { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.drp-cal-header {
    display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; gap: 6px;
}
.drp-cal-header select {
    border: 1px solid #d1d5db; border-radius: 6px; padding: 4px 8px; font-size: 13px; background: #fff; color: #111827; font-weight: 500;
}
.drp-nav-btn {
    border: 0; background: transparent; color: #4b5563; width: 28px; height: 28px; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center;
}
.drp-nav-btn:hover { background: #f3f4f6; color: #111827; }
.drp-weekdays, .drp-days { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; text-align: center; }
.drp-weekdays span { font-size: 13px; color: #4b5563; padding: 4px 0; font-weight: 500; }
.drp-day {
    border: 0; background: transparent; height: 32px; width: 32px; margin: 0 auto; border-radius: 6px; font-size: 13px; color: #111827; cursor: pointer; display: flex; align-items: center; justify-content: center;
}
.drp-day:hover:not(.empty) { background: #edf2ff; color: #2563eb; }
.drp-day.empty { cursor: default; }
.drp-day.in-range { background: #dbeafe; border-radius: 0; width: 100%; }
.drp-day.start, .drp-day.end {
    background: #2563eb !important; color: #fff !important; border-radius: 8px; font-weight: 600;
}
.drp-footer {
    display: flex; align-items: center; justify-content: space-between;
    margin-top: 14px; padding-top: 12px; border-top: 1px solid #e5e7eb;
}
.drp-footer .drp-range-text { font-size: 13px; color: #374151; font-weight: 500; }
.drp-footer .btn-cancel {
    border: 0; background: transparent; color: #2563eb; font-weight: 600; padding: 8px 14px; font-size: 14px; border-radius: 6px;
}
.drp-footer .btn-cancel:hover { background: #f3f4f6; }
.drp-footer .btn-apply {
    background: #2563eb; color: #fff; border: 0; border-radius: 8px; padding: 8px 22px; font-weight: 600; font-size: 14px;
}
.drp-footer .btn-apply:hover { background: #1d4ed8; }

/* Month range picker (analytics) — reuses .drp-* shell, custom pane only */
.mrp-panel { max-width: min(520px, calc(100vw - 40px)); }
.mrp-month-pane {
    width: 360px;
    max-width: 100%;
}
.mrp-select-row {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.mrp-select-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.mrp-select-label {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    letter-spacing: 0.02em;
}
.mrp-selects {
    display: flex;
    gap: 10px;
}
.mrp-selects select {
    flex: 1;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 14px;
    background: #fff;
    color: #111827;
    font-weight: 500;
}
.mrp-selects select:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.18);
}
@media (max-width: 560px) {
    .mrp-panel { flex-direction: column; }
    .mrp-panel .drp-presets {
        width: 100%;
        flex: none;
        border-right: 0;
        border-bottom: 1px solid #e5e7eb;
        max-height: none;
        display: flex;
        flex-wrap: wrap;
        gap: 0;
        padding: 4px 0;
    }
    .mrp-panel .drp-preset { width: auto; padding: 8px 14px; }
    .mrp-month-pane { width: 100%; }
}

main.with-top-header:has(.report-shell) {
    overflow-x: clip;
}
.report-shell *,
.report-shell *::before,
.report-shell *::after {
    box-sizing: border-box;
}

@media (max-width: 1023px) {
    .report-sticky-top {
        top: 56px; /* below fixed mobile topbar */
    }
}

@media (max-width: 1100px) {
    .report-table { font-size: 13px; }
    .report-table thead th,
    .report-table tbody td,
    .report-table tfoot td { padding: 12px 14px; }
    .report-table-wrap { padding: 16px 16px 22px; }
}

@media (max-width: 767.98px) {
    .report-body { padding: 12px 12px 20px; }
    .report-meta { padding: 22px 16px 18px; }
    .report-meta.report-meta-with-asides {
        flex-wrap: wrap;
        justify-content: center;
        gap: 12px;
    }
    .report-meta-aside {
        min-width: 0;
        width: calc(50% - 6px);
        text-align: left;
    }
    .report-meta-aside-right {
        text-align: right;
    }
    .report-meta-center {
        order: -1;
        width: 100%;
        flex: 1 0 100%;
    }
    .report-meta-stat {
        display: block;
        width: 100%;
        box-sizing: border-box;
        min-width: 0;
    }
    .report-table-wrap { padding: 12px 12px 18px; }
    .report-toolbar { padding: 12px; }
    .report-shell .report-toolbar .toolbar-field {
        width: calc(50% - 8px);
        min-width: 0;
    }
    .report-shell .report-toolbar .toolbar-field .form-select,
    .report-shell .report-toolbar .toolbar-field .form-control {
        width: 100%;
        max-width: none;
        min-width: 0;
    }
    .report-toolbar .btn-run-report:last-child {
        margin-left: 0;
        width: 100%;
    }
    .report-actions-top { margin-left: 0; width: 100%; justify-content: flex-end; }
    .drp-panel.open { flex-direction: column; width: min(94vw, 420px); }
    .drp-presets { width: 100%; max-height: 180px; border-right: 0; border-bottom: 1px solid #e5e7eb; }
    .drp-calendar-pane { width: 100%; }
    .drp-calendars { grid-template-columns: 1fr; }
    .report-table { table-layout: auto; }
    .report-table thead { display: none; }
    .report-table tbody tr { display: block; border-bottom: 1px solid #eef2f7; padding: 8px 0; }
    .report-table tbody td {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 6px 16px;
        border: 0;
        width: 100%;
    }
    .report-table tbody td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #6b7280;
        margin-right: 12px;
        flex: 0 0 110px;
    }
    .report-table .source-cell { display: flex !important; }
}
</style>
