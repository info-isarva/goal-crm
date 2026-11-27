@extends('layouts.app')

@push('styles')
<style>
.vrm-page {
    color: #1e293b;
    --g: #16a34a;
    --gd: #15803d;
    --g50: #f0fdf4;
    --g100: #dcfce7;
    --g200: #bbf7d0;
    --g300: #86efac;
}
.vrm-page .page-hero {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
    gap: 1rem; margin-bottom: 1.25rem;
}
.vrm-page .page-hero-left {
    display: flex; align-items: center; gap: 14px; min-width: 0;
}
.vrm-page .page-hero-icon {
    width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #fff; display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.35rem; box-shadow: 0 10px 20px rgba(22, 163, 74, 0.28);
}
.vrm-page .vrm-title { font-size: 1.55rem; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.2; }
.vrm-page .vrm-subtitle { color: #64748b; font-size: 0.9rem; margin-top: 4px; line-height: 1.35; }
.vrm-page .page-hero-actions {
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
}
.vrm-page .progress-card {
    background: #fff; border: 1px solid var(--g200); border-radius: 14px;
    padding: 10px 14px; min-width: 220px; max-width: 280px;
    box-shadow: 0 6px 16px rgba(22, 163, 74, 0.08);
}
.vrm-page .progress-card .pc-top {
    display: flex; align-items: center; justify-content: space-between; gap: 8px;
    font-size: .78rem; font-weight: 700; color: #475569; margin-bottom: 6px;
}
.vrm-page .progress-card .pc-val { color: var(--gd); font-size: .82rem; }
.vrm-page .progress-card .pc-bar {
    height: 8px; border-radius: 999px; background: #ecfdf5; overflow: hidden;
}
.vrm-page .progress-card .pc-bar > span {
    display: block; height: 100%; border-radius: 999px;
    background: linear-gradient(90deg, #22c55e, var(--g));
}
.vrm-page .progress-card .pc-sub {
    margin-top: 6px; font-size: .72rem; color: #64748b; font-weight: 600;
}
.vrm-page .assign-group-row { cursor: pointer; user-select: none; }
.vrm-page .assign-group-row .ag-collapse-icon {
    color: var(--g); font-size: .9rem; transition: transform .15s; display: inline-flex;
}
.vrm-page .assign-group-row.collapsed .ag-collapse-icon { transform: rotate(-90deg); }
.vrm-page .assign-contact-row.is-hidden-by-group { display: none; }
.vrm-page .analytics-grid {
    display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px;
    padding: 1.15rem;
}
@media (max-width: 991.98px) { .vrm-page .analytics-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 575.98px) { .vrm-page .analytics-grid { grid-template-columns: 1fr; } }
.vrm-page .analytics-card {
    border: 1px solid #e8edf5; border-radius: 14px; padding: 14px 16px; background: #fff;
}
.vrm-page .analytics-card .al { font-size: .78rem; font-weight: 700; color: #64748b; text-transform: uppercase; }
.vrm-page .analytics-card .av { font-size: 1.65rem; font-weight: 800; color: #0f172a; margin-top: 4px; }
.vrm-page .disp-bars { padding: 0 1.15rem 1.25rem; }
.vrm-page .disp-bar-row {
    display: grid; grid-template-columns: 120px 1fr 40px; gap: 10px; align-items: center;
    margin-bottom: 8px; font-size: .82rem;
}
.vrm-page .disp-bar-row .track { height: 10px; background: #f1f5f9; border-radius: 999px; overflow: hidden; }
.vrm-page .disp-bar-row .fill { height: 100%; background: linear-gradient(90deg, #86efac, var(--g)); border-radius: 999px; }
.vrm-page .analytics-charts {
    display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px;
    padding: 0 1.15rem 1.25rem;
}
@media (max-width: 991.98px) { .vrm-page .analytics-charts { grid-template-columns: 1fr; } }
.vrm-page .chart-panel {
    border: 1px solid #e8edf5; border-radius: 14px; padding: 14px 16px; background: #fff;
}
.vrm-page .chart-panel h6 {
    font-size: .88rem; font-weight: 800; color: #0f172a; margin: 0 0 10px;
}
.vrm-page .chart-panel .chart-wrap { position: relative; height: 220px; }
.vrm-page .callback-overdue { color: #b45309 !important; font-weight: 700; }
.vrm-page .filter-grid.calls-filters {
    grid-template-columns: minmax(180px, 1.4fr) repeat(2, minmax(130px, 1fr)) minmax(220px, 1.2fr) auto;
}
.vrm-page .filter-grid.assigned-filters {
    grid-template-columns: minmax(180px, 1.4fr) minmax(140px, 1fr) minmax(140px, 1fr) auto;
}
.vrm-page .filter-grid.assigned-filters.no-branch {
    grid-template-columns: minmax(180px, 1.4fr) minmax(140px, 1fr) auto;
}
.vrm-page .filter-grid.assigned-filters.bm-filters {
    grid-template-columns: minmax(180px, 1.4fr) repeat(3, minmax(130px, 1fr)) auto;
}
.vrm-page .filter-grid.analytics-filters {
    grid-template-columns: minmax(240px, 1fr) auto;
}
.vrm-page .filter-grid .date-range-wrap {
    position: relative;
    width: 100%;
    cursor: pointer;
}
.vrm-page .filter-grid .date-range-wrap .bi-calendar3 {
    position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
    color: #94a3b8; font-size: .9rem; z-index: 3; pointer-events: none;
}
.vrm-page .filter-grid .date-range-wrap .form-control,
.vrm-page .filter-grid .date-range-wrap .flatpickr-input,
.vrm-page .filter-grid .date-range-wrap input.flatpickr-alt {
    width: 100%;
    min-height: 38px;
    height: 38px;
    padding-left: 2rem !important;
    cursor: pointer;
    background: #fff;
}
.vrm-page .flatpickr-calendar {
    z-index: 20050 !important;
}
@media (max-width: 1199.98px) {
    .vrm-page .filter-grid.calls-filters,
    .vrm-page .filter-grid.assigned-filters,
    .vrm-page .filter-grid.assigned-filters.no-branch,
    .vrm-page .filter-grid.assigned-filters.bm-filters,
    .vrm-page .filter-grid.analytics-filters {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
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
    background: linear-gradient(90deg, var(--g), var(--gd));
    color: #fff; border: none; border-radius: 10px;
    height: 40px; padding: 0 1.05rem; font-weight: 700; font-size: 0.9rem;
    display: inline-flex; align-items: center; gap: 0.4rem;
    box-shadow: 0 8px 16px rgba(22, 163, 74, 0.28);
}
.vrm-page .btn-log-call:hover { filter: brightness(1.05); color: #fff; }
.vrm-page .vrm-tabs {
    display: flex; flex-wrap: wrap; align-items: flex-end;
    gap: 6px;
    margin: 0;
    padding: 8px 10px 0;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-bottom: 0;
    border-radius: 14px 14px 0 0;
}
.vrm-page .vrm-tab {
    position: relative;
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 16px;
    margin: 0;
    border: 1px solid #dbe3ee;
    border-bottom: 0;
    border-radius: 10px 10px 0 0;
    background: #e8eef5;
    color: #64748b;
    font-size: .88rem; font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: color .15s ease, border-color .15s ease, background .15s ease, box-shadow .15s ease;
}
.vrm-page .vrm-tab i { font-size: .95rem; opacity: .9; }
.vrm-page .vrm-tab:hover {
    color: var(--gd);
    background: #fff;
    border-color: var(--g200);
}
.vrm-page .vrm-tab.on {
    color: var(--gd);
    background: #fff;
    border-color: #e2e8f0;
    border-bottom-color: #fff;
    box-shadow: inset 0 3px 0 0 var(--g);
    z-index: 2;
}
.vrm-page .vrm-tab.on i { opacity: 1; color: var(--g); }
.vrm-page .vrm-tab .count {
    min-width: 22px; height: 22px; padding: 0 7px; border-radius: 999px;
    background: #fff; color: #475569; font-size: .75rem; font-weight: 800;
    border: 1px solid #e2e8f0;
    display: inline-flex; align-items: center; justify-content: center;
}
.vrm-page .vrm-tab:hover .count { background: var(--g100); color: var(--gd); border-color: var(--g200); }
.vrm-page .vrm-tab.on .count { background: var(--g); color: #fff; border-color: var(--g); }
/* Connect tab bar flush to the panel below */
.vrm-page .call-status {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 9px; border-radius: 999px; font-size: .72rem; font-weight: 700;
}
.vrm-page .call-status.pending { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
.vrm-page .call-status.called { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.vrm-page .btn-log-row {
    height: 32px; padding: 0 12px; border-radius: 8px; border: none;
    background: linear-gradient(90deg, var(--g), var(--gd)); color: #fff;
    font-size: .78rem; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;
    box-shadow: 0 4px 10px rgba(22, 163, 74, .22); white-space: nowrap; flex-shrink: 0;
}
.vrm-page .btn-log-row:hover { filter: brightness(1.05); color: #fff; }
.vrm-page td.col-actions,
.vrm-page th.col-actions {
    white-space: nowrap; width: 1%; min-width: 110px; vertical-align: middle !important;
}
.vrm-page td.col-actions .action-group {
    display: inline-flex !important; flex-direction: row; flex-wrap: nowrap;
    align-items: center; justify-content: flex-end; gap: 8px; width: 100%;
}
.vrm-page td.col-actions .dropdown { display: inline-flex; flex-shrink: 0; }
.vrm-page td.col-actions .action-btn.dropdown-toggle::after { display: none !important; }
.vrm-page .assign-group-row td {
    background: #e8f8ee !important;
    border-top: 1px solid #d8efe2 !important;
    border-bottom: 0 !important;
    padding: 6px 12px !important;
    vertical-align: middle;
}
.vrm-page .assign-group-row .ag-wrap {
    display: flex;
    flex-wrap: nowrap;
    align-items: center;
    gap: 8px;
    width: 100%;
    min-height: 0;
    padding: 0;
    border-radius: 0;
    background: transparent;
    border: 0;
    font-size: .78rem;
    color: #14532d;
    font-weight: 700;
}
.vrm-page .assign-group-row .ag-collapse-icon {
    flex: 0 0 auto;
    font-size: .8rem;
}
.vrm-page .assign-group-row .ag-main {
    display: inline-flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-start;
    gap: 0;
    flex: 1 1 auto;
    min-width: 0;
    text-align: left;
    line-height: 1.25;
}
.vrm-page .assign-group-row .ag-main .ag-calendar {
    color: var(--g);
    margin-right: 5px;
    font-size: .85rem;
}
.vrm-page .assign-group-row .ag-sep {
    display: inline-block;
    margin: 0 8px;
    color: #86a393;
    font-weight: 600;
}
.vrm-page .assign-group-row .ag-count {
    margin-left: auto;
    display: inline-flex; align-items: center; gap: 5px;
    padding: 2px 9px; border-radius: 999px;
    background: var(--g); color: #fff; font-size: .7rem; font-weight: 800;
    white-space: nowrap; flex: 0 0 auto;
}
.vrm-page .assign-group-row.is-today td {
    background: #dff7e8 !important;
}
.vrm-page .assign-group-row .ag-today {
    margin-left: 6px;
    padding: 1px 6px; border-radius: 999px; background: #fef3c7; color: #b45309;
    font-size: .65rem; font-weight: 800;
}
/* Attach contact rows to their group header (no separating border) */
.vrm-page #vrmAssignedTable tr.assign-group-row + tr.assign-contact-row td {
    border-top: 0 !important;
}
.vrm-page #vrmAssignedTable tr.assign-contact-row + tr.assign-contact-row td {
    border-top: 1px solid #eef2f7;
}
.vrm-page #vrmAssignedTable tr.assign-contact-row:hover td {
    background: #f8fafc;
}
.vrm-page #vrmAssignedTable tr.assign-contact-row + tr.assign-group-row td {
    border-top: 1px solid #d8efe2 !important;
}
.vrm-page #vrmAssignedTable tr.assign-contact-row:has(+ tr.assign-group-row) td,
.vrm-page #vrmAssignedTable tr.assign-contact-row:last-child td {
    border-bottom: 1px solid #eef2f7 !important;
}
.vrm-page .vrm-card-head:has(+ .vrm-count-chips) {
    padding-bottom: 0.65rem;
    border-bottom: 0;
}
.vrm-page .vrm-count-chips {
    display: flex; flex-wrap: wrap; align-items: center; gap: 6px;
    padding: 0 1.15rem 0.65rem;
    border-bottom: 1px solid #eef2f7;
    margin: 0;
}
.vrm-page .vrm-count-chip {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 8px 3px 9px;
    border-radius: 999px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #64748b;
    font-size: .72rem;
    font-weight: 700;
    line-height: 1.2;
    text-decoration: none;
    white-space: nowrap;
    transition: background .12s ease, border-color .12s ease, color .12s ease;
}
.vrm-page .vrm-count-chip:hover {
    border-color: var(--g200);
    color: var(--gd);
    background: var(--g50);
}
.vrm-page .vrm-count-chip.on {
    border-color: var(--g);
    background: var(--g);
    color: #fff;
}
.vrm-page .vrm-count-chip .n {
    min-width: 16px; height: 16px; padding: 0 5px; border-radius: 999px;
    background: #fff; color: var(--gd); font-size: .65rem; font-weight: 800;
    display: inline-flex; align-items: center; justify-content: center;
    border: 1px solid #e2e8f0;
}
.vrm-page .vrm-count-chip.on .n {
    background: #fff; color: var(--gd); border-color: transparent;
}

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
    background: linear-gradient(135deg, transparent 20%, var(--g200) 100%);
}
.vrm-page .kpi-card.tone-purple::after { background: #22c55e; }
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
.vrm-page .kpi-icon.purple { background: var(--g100); color: var(--g); }
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
.vrm-page .vrm-tabs + .filter-card {
    margin-top: 0;
    border-top-left-radius: 0;
    border-top-right-radius: 0;
    border-top-color: #e2e8f0;
    box-shadow: 0 10px 24px rgba(148, 163, 184, 0.1);
}
.vrm-page .filter-grid {
    display: grid;
    grid-template-columns: minmax(180px, 1.4fr) repeat(3, minmax(130px, 1fr)) auto;
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
    height: 31px; border-radius: 8px; border: none; background: var(--g); color: #fff;
    padding: 0 14px; font-weight: 700; font-size: 0.85rem;
    display: inline-flex; align-items: center; gap: 6px;
}
.vrm-page .btn-apply:hover { background: var(--gd); color: #fff; }
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
    background: var(--g) !important; color: #fff !important;
    box-shadow: 0 4px 10px rgba(22, 163, 74, 0.28) !important;
}
.vrm-page .foot-pager .page-item.disabled .page-link {
    background: transparent !important; color: #cbd5e1 !important;
    pointer-events: none; box-shadow: none !important;
}
.vrm-page .foot-pager .page-item.us-page-arrow .page-link,
.vrm-page .foot-pager .page-item:first-child .page-link,
.vrm-page .foot-pager .page-item:last-child .page-link {
    background: var(--g50) !important; color: var(--g) !important;
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
    width: 100%;
    overflow: visible;
}
.vrm-page .calls-table-wrap table,
.vrm-page .calls-table-wrap table.table,
.vrm-page .calls-table-wrap #vrmCallsTable,
.vrm-page .calls-table-wrap #vrmAssignedTable {
    width: max-content !important;
    min-width: 100% !important;
    table-layout: auto !important;
    margin: 0 !important;
    border-collapse: separate;
    border-spacing: 0;
}
.vrm-page .calls-table-wrap thead th,
.vrm-page .calls-table-wrap tbody td {
    white-space: nowrap;
    vertical-align: middle !important;
    box-sizing: border-box !important;
    text-align: left;
    padding: 0.75rem 0.9rem !important;
}
.vrm-page .calls-table-wrap thead th {
    background: #f1f5f9;
}
.vrm-page .calls-table-wrap td.col-num,
.vrm-page .calls-table-wrap th.col-num {
    width: 1%;
    text-align: center !important;
    color: #64748b;
    font-weight: 700;
}
.vrm-page .calls-table-wrap td.col-actions,
.vrm-page .calls-table-wrap th.col-actions {
    width: 1%;
    text-align: right !important;
    white-space: nowrap;
}
.vrm-page .calls-table-wrap td.col-actions .action-group {
    justify-content: flex-end;
    flex-wrap: nowrap;
}
.vrm-page .calls-table-wrap .when-cell {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    min-width: 150px;
}
.vrm-page .calls-table-wrap .when-text {
    display: flex;
    flex-direction: column;
    gap: 1px;
    min-width: 0;
}
.vrm-page .calls-table-wrap .cust-name {
    font-weight: 700;
    color: #0f172a;
    line-height: 1.25;
}
.vrm-page .calls-table-wrap .cust-ref {
    font-size: .72rem;
    color: #94a3b8;
    margin-top: 2px;
}
.vrm-page .calls-table-wrap .mobile-cell {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #0f172a;
    text-decoration: none;
    font-weight: 600;
}
.vrm-page .calls-table-wrap .mobile-cell i { color: var(--g); font-size: .9rem; }
.vrm-page .calls-table-wrap .mobile-cell:hover { color: var(--gd); }
.vrm-page .lead-status-pill {
    display: inline-flex; align-items: center;
    padding: 3px 10px; border-radius: 999px;
    font-size: .72rem; font-weight: 700; white-space: nowrap;
    background: #e0f2fe; color: #0369a1;
}
.vrm-page .lead-status-pill.tone-docs { background: #f3e8ff; color: #7c3aed; }
.vrm-page .lead-status-pill.tone-ok { background: #dcfce7; color: #15803d; }
.vrm-page .lead-status-pill.tone-warn { background: #ffedd5; color: #c2410c; }
.vrm-page .lead-status-pill.tone-bad { background: #fee2e2; color: #b91c1c; }
.vrm-page .lead-status-pill.tone-muted { background: #f1f5f9; color: #64748b; }
/* Neutralize DataTables default sort sprites; keep a compact indicator */
.vrm-page .calls-table-wrap table.dataTable thead th {
    position: relative;
}
.vrm-page .calls-table-wrap table.dataTable thead .sorting,
.vrm-page .calls-table-wrap table.dataTable thead .sorting_asc,
.vrm-page .calls-table-wrap table.dataTable thead .sorting_desc {
    background-image: none !important;
    padding-right: 1.25rem !important;
}
.vrm-page .calls-table-wrap table.dataTable thead .sorting:before,
.vrm-page .calls-table-wrap table.dataTable thead .sorting_asc:before,
.vrm-page .calls-table-wrap table.dataTable thead .sorting_desc:before {
    display: none !important;
    content: none !important;
}
.vrm-page .calls-table-wrap table.dataTable thead .sorting:after,
.vrm-page .calls-table-wrap table.dataTable thead .sorting_asc:after,
.vrm-page .calls-table-wrap table.dataTable thead .sorting_desc:after {
    position: absolute !important;
    right: 0.4rem !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    display: block !important;
    font-size: 0.62rem !important;
    line-height: 1 !important;
    color: #94a3b8 !important;
    opacity: 0.9 !important;
    content: "⇅" !important;
    font-family: inherit !important;
    border: 0 !important;
    bottom: auto !important;
    left: auto !important;
}
.vrm-page .calls-table-wrap table.dataTable thead .sorting_asc:after { content: "▲" !important; color: var(--g) !important; }
.vrm-page .calls-table-wrap table.dataTable thead .sorting_desc:after { content: "▼" !important; color: var(--g) !important; }
.vrm-page .calls-table-wrap .notes-cell {
    white-space: nowrap;
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
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
    background: var(--g100); color: var(--g);
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
.vrm-page .disp-pill.follow-up { background: var(--g100); color: var(--gd); }
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
    outline: 2px solid var(--g200);
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
.vrm-action-tip {
    position: fixed;
    z-index: 30000;
    background: #0f172a;
    color: #fff;
    font-size: 0.75rem;
    font-weight: 600;
    line-height: 1.2;
    border-radius: 8px;
    padding: 6px 10px;
    white-space: nowrap;
    pointer-events: none;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.22);
    opacity: 0;
    transition: opacity .12s ease;
}
.vrm-action-tip.is-on { opacity: 1; }
.vrm-action-tip::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 6px solid transparent;
    border-top-color: #0f172a;
}
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
    background: var(--g); color: #fff; box-shadow: 0 4px 10px rgba(22, 163, 74, 0.28);
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
    row-gap: 12px;
    align-items: start;
}
.vrm-call-modal .field {
    min-width: 0;
    width: 100%;
    display: flex;
    flex-direction: column;
}
.vrm-call-modal .field.field-span-2 { grid-column: 1 / -1; }
.vrm-call-modal .create-lead-row {
    margin-top: 4px;
    margin-bottom: 0;
    padding: 0;
    min-height: 0;
}
.vrm-call-modal .create-lead-row .form-check {
    margin: 0;
    padding: 0;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 0 !important;
}
.vrm-call-modal .field .form-text {
    margin-top: 4px;
    margin-bottom: 0;
    line-height: 1.3;
}
.vrm-call-modal #notes {
    min-height: 42px;
    resize: vertical;
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
.vrm-call-modal .contact-source-hint.new { color: var(--g); }
.vrm-call-modal .contact-source-hint .chip {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 999px; font-size: 0.7rem; font-weight: 700;
    background: #f1f5f9; color: inherit;
}
.vrm-call-modal .contact-source-hint.assigned .chip { background: #ccfbf1; }
.vrm-call-modal .contact-source-hint.new .chip { background: var(--g100); }
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
    border: 1px solid var(--g200);
    background: var(--g50);
    color: var(--g);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    line-height: 1;
}
.vrm-call-modal .btn-quick-add-product:hover {
    background: var(--g100);
    color: var(--gd);
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
    background: linear-gradient(90deg, var(--g), var(--gd));
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
        display: inline-flex !important;
        flex-direction: row;
        flex-wrap: nowrap;
        gap: 8px;
        justify-content: flex-start;
        width: auto;
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
    $canSeeAssignedContacts = (bool) ($canSeeAssignedContacts ?? false);
    $isBmAssignedView = (bool) ($isBmAssignedView ?? false);
    $defaultTab = $canSeeAssignedContacts ? 'assigned' : 'calls';
    $tabRaw = $activeTab ?? request('tab', $defaultTab);
    if (is_string($tabRaw) && in_array($tabRaw, ['assigned', 'calls', 'analytics'], true)) {
        $activeTab = $tabRaw;
    } else {
        $activeTab = $defaultTab;
    }
    if ($activeTab === 'assigned' && ! $canSeeAssignedContacts) {
        $activeTab = 'calls';
    }
    $callingProgress = $callingProgress ?? ['show' => false, 'completed' => 0, 'target' => 0, 'pending' => 0, 'pct' => 0];
    $callAnalytics = $callAnalytics ?? [];
    $assignedLeadStatuses = $assignedLeadStatuses ?? collect();
    $filterBranchId = (int) ($filterBranchId ?? 0);
    $filterAssignedLeadStatus = $filterAssignedLeadStatus ?? request('lead_status', '');
    $todayIso = now()->format('Y-m-d');
    $callStatus = $callStatus ?? request('call_status', 'all');
    $filterVrmId = (int) ($filterVrmId ?? 0);
    $vrmOptions = $vrmOptions ?? collect();
    $vrmAssignedCounts = $vrmAssignedCounts ?? [];
    $assignedContacts = $assignedContacts ?? collect();
    $assignedContactGroups = $assignedContactGroups ?? collect();
    $filters = request()->only(['q', 'disposition', 'from_date', 'to_date', 'per_page', 'tab', 'call_status', 'vrm_id', 'lead_status', 'branch_id', 'callbacks_due']);
    $dispositionValue = request('disposition', request('disposition_filter'));
@endphp

<div class="container-fluid p-4 vrm-page">
    <div class="page-hero">
        <div class="page-hero-left">
            <span class="page-hero-icon"><i class="bi bi-telephone-outbound"></i></span>
            <div>
                <h1 class="vrm-title">VRM Calling</h1>
                <div class="vrm-subtitle">Manage outbound calls, capture call outcomes and convert contacts to leads.</div>
            </div>
        </div>
        <div class="page-hero-actions">
            @if(!empty($callingProgress['show']))
            <div class="progress-card" title="Today's calls vs assigned contacts{{ $isBmAssignedView ? ' (all branch VRMs)' : '' }}">
                <div class="pc-top">
                    <span>Today's Calling Progress</span>
                    <span class="pc-val">{{ (int) $callingProgress['completed'] }} / {{ (int) $callingProgress['target'] }} Calls Completed</span>
                </div>
                <div class="pc-bar"><span style="width: {{ min(100, (float) $callingProgress['pct']) }}%"></span></div>
                <div class="pc-sub">
                    Achievement {{ $callingProgress['pct'] }}% · Pending {{ (int) $callingProgress['pending'] }}
                    @if(!empty($isBmAssignedView)) · Branch VRMs @endif
                </div>
            </div>
            @endif
            @if($canSeeAssignedContacts)
            <span class="vrm-pill" title="Pending to call out of assigned contacts">
                <span class="dot" aria-hidden="true"></span>
                Pending: {{ (int) ($pendingToCallCount ?? 0) }} / {{ (int) ($assignedContactsCount ?? 0) }} contacts
            </span>
            @endif
            <button type="button" class="btn btn-log-call" data-bs-toggle="modal" data-bs-target="#logCallModal">
                <i class="bi bi-plus-lg"></i> Log Call
            </button>
        </div>
    </div>

    <div class="vrm-tabs" role="tablist" aria-label="VRM Calling sections">
        <a class="vrm-tab {{ $activeTab === 'calls' ? 'on' : '' }}"
           href="{{ route('goal.vrm.index', ['tab' => 'calls']) }}"
           role="tab" aria-selected="{{ $activeTab === 'calls' ? 'true' : 'false' }}">
            <i class="bi bi-telephone-forward"></i> All Call Logs
            <span class="count">{{ (int) ($totalCalls ?? 0) }}</span>
        </a>
        @if($canSeeAssignedContacts)
        <a class="vrm-tab {{ $activeTab === 'assigned' ? 'on' : '' }}"
           href="{{ route('goal.vrm.index', ['tab' => 'assigned']) }}"
           role="tab" aria-selected="{{ $activeTab === 'assigned' ? 'true' : 'false' }}">
            <i class="bi bi-people"></i> Assigned Contacts
            <span class="count">{{ (int) ($assignedContactsCount ?? 0) }}</span>
        </a>
        @endif
        <a class="vrm-tab {{ $activeTab === 'analytics' ? 'on' : '' }}"
           href="{{ route('goal.vrm.index', array_merge(request()->only(['from_date', 'to_date']), ['tab' => 'analytics'])) }}"
           role="tab" aria-selected="{{ $activeTab === 'analytics' ? 'true' : 'false' }}">
            <i class="bi bi-bar-chart-line"></i> Call Analytics
        </a>
    </div>

    @if($activeTab !== 'analytics')
    <div class="filter-card">
        @php
            $vrmFromDate = (string) request('from_date', '');
            $vrmToDate = (string) request('to_date', '');
            $vrmDateRangeLabel = '';
            if ($vrmFromDate !== '' || $vrmToDate !== '') {
                try {
                    $fromLabel = $vrmFromDate !== '' ? \Carbon\Carbon::parse($vrmFromDate)->format('d M Y') : '';
                    $toLabel = $vrmToDate !== '' ? \Carbon\Carbon::parse($vrmToDate)->format('d M Y') : $fromLabel;
                    $vrmDateRangeLabel = $fromLabel !== ''
                        ? ($toLabel !== '' && $toLabel !== $fromLabel ? $fromLabel.' – '.$toLabel : $fromLabel)
                        : $toLabel;
                } catch (\Throwable $e) {
                    $vrmDateRangeLabel = '';
                }
            }
        @endphp
        <form method="GET" action="{{ route('goal.vrm.index') }}" id="vrmFilterForm"
              class="filter-grid {{ $activeTab === 'assigned' ? 'assigned-filters'.(!empty($branchLocked) ? ' no-branch' : '').(!empty($isBmAssignedView) ? ' bm-filters' : '') : 'calls-filters' }}">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <div>
                <label for="q">Customer Search</label>
                <input type="text" name="q" id="q" class="form-control"
                       placeholder="Name, mobile or ID..." value="{{ request('q') }}">
            </div>
            @if($activeTab === 'assigned')
            <div>
                <label for="call_status">Call Status</label>
                <select name="call_status" id="call_status" class="form-select js-select2"
                        data-placeholder="All statuses" data-allow-clear="true">
                    <option value="all" @selected($callStatus === 'all' || $callStatus === '')>All</option>
                    <option value="pending" @selected($callStatus === 'pending')>Pending to Call</option>
                    <option value="called" @selected($callStatus === 'called')>Called</option>
                    <option value="connected" @selected($callStatus === 'connected')>Called – Connected</option>
                    <option value="not_connected" @selected($callStatus === 'not_connected')>Called – Not Connected</option>
                    <option value="callback" @selected($callStatus === 'callback')>Callback Scheduled</option>
                </select>
            </div>
            {{-- Branch filter is for BM/managers only — VRMs are locked to their own branch --}}
            @if(empty($branchLocked))
            <div>
                <label for="branch_id">Branch</label>
                <select name="branch_id" id="branch_id" class="form-select js-select2"
                        data-placeholder="All branches" data-allow-clear="true">
                    <option value="">All</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" @selected($filterBranchId === (int) $b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            @if($isBmAssignedView)
            <div>
                <label for="vrm_id">VRM</label>
                <select name="vrm_id" id="vrm_id" class="form-select js-select2"
                        data-placeholder="All VRMs" data-allow-clear="true">
                    <option value="">All VRMs</option>
                    @foreach($vrmOptions as $vrm)
                        <option value="{{ $vrm->id }}" @selected($filterVrmId === (int) $vrm->id)>
                            {{ $vrm->name }} ({{ (int) ($vrmAssignedCounts[(int) $vrm->id] ?? 0) }})
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
            @else
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
                <label for="interest_level_filter">Interest Level</label>
                <select name="interest_level" id="interest_level_filter" class="form-select js-select2"
                        data-placeholder="All levels" data-allow-clear="true">
                    <option value="">All</option>
                    @foreach(\App\Models\GoalVrmCallLog::allowedInterestLevels() as $level)
                        <option value="{{ $level }}" @selected(request('interest_level') === $level)>{{ $level }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="vrmDateRange">Date Range</label>
                <div class="date-range-wrap" data-vrm-daterange>
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                    <input type="text" id="vrmDateRange" class="form-control js-vrm-daterange"
                           placeholder="Select date range" value=""
                           readonly autocomplete="off"
                           data-from="{{ $vrmFromDate }}" data-to="{{ $vrmToDate }}">
                    <input type="hidden" name="from_date" id="from_date" value="{{ $vrmFromDate }}">
                    <input type="hidden" name="to_date" id="to_date" value="{{ $vrmToDate }}">
                </div>
            </div>
            @endif
            <div class="filter-actions">
                <div>
                    <label class="d-none d-xl-block">&nbsp;</label>
                    <button type="submit" class="btn-apply"><i class="bi bi-funnel"></i> Apply</button>
                </div>
                <div>
                    <label class="d-none d-xl-block">&nbsp;</label>
                    <a href="{{ route('goal.vrm.index', ['tab' => $activeTab]) }}" class="btn-reset"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                </div>
            </div>
        </form>
    </div>
    @else
    @php
        $vrmFromDate = request('from_date', $callAnalytics['from'] ?? '');
        $vrmToDate = request('to_date', $callAnalytics['to'] ?? '');
        $vrmDateRangeLabel = '';
        if ($vrmFromDate || $vrmToDate) {
            try {
                $fromLabel = $vrmFromDate ? \Carbon\Carbon::parse($vrmFromDate)->format('d M Y') : '';
                $toLabel = $vrmToDate ? \Carbon\Carbon::parse($vrmToDate)->format('d M Y') : $fromLabel;
                $vrmDateRangeLabel = trim($fromLabel.($toLabel && $toLabel !== $fromLabel ? ' – '.$toLabel : ($fromLabel ? ' – '.$fromLabel : '')));
            } catch (\Throwable $e) {
                $vrmDateRangeLabel = '';
            }
        }
    @endphp
    <div class="filter-card">
        <form method="GET" action="{{ route('goal.vrm.index') }}" id="vrmAnalyticsFilterForm" class="filter-grid analytics-filters">
            <input type="hidden" name="tab" value="analytics">
            <div>
                <label for="vrmAnalyticsDateRange">Date Range</label>
                <div class="date-range-wrap" data-vrm-daterange>
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                    <input type="text" id="vrmAnalyticsDateRange" class="form-control js-vrm-daterange"
                           placeholder="Select date range" value=""
                           readonly autocomplete="off"
                           data-from="{{ $vrmFromDate }}" data-to="{{ $vrmToDate }}">
                    <input type="hidden" name="from_date" id="analytics_from_date" value="{{ $vrmFromDate }}">
                    <input type="hidden" name="to_date" id="analytics_to_date" value="{{ $vrmToDate }}">
                </div>
            </div>
            <div class="filter-actions">
                <div>
                    <label class="d-none d-xl-block">&nbsp;</label>
                    <button type="submit" class="btn-apply"><i class="bi bi-funnel"></i> Apply</button>
                </div>
                <div>
                    <label class="d-none d-xl-block">&nbsp;</label>
                    <a href="{{ route('goal.vrm.index', ['tab' => 'analytics']) }}" class="btn-reset"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                </div>
            </div>
        </form>
    </div>
    @endif

    <div class="row g-3 kpi-row">
        @if($canSeeAssignedContacts)
        <div class="col-6 col-xl-2">
            <a href="{{ route('goal.vrm.index', ['tab' => 'assigned']) }}" class="text-decoration-none">
                <div class="kpi-card tone-teal">
                    <span class="kpi-icon teal"><i class="bi bi-people-fill"></i></span>
                    <div class="kpi-meta">
                        <div class="kpi-label">Assigned Contacts</div>
                        <div class="kpi-value">{{ $assignedContactsCount }}</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-2">
            <a href="{{ route('goal.vrm.index', ['tab' => 'assigned', 'call_status' => 'pending']) }}" class="text-decoration-none">
                <div class="kpi-card tone-{{ $pendingToCallCount > 0 ? 'amber' : 'teal' }}">
                    <span class="kpi-icon {{ $pendingToCallCount > 0 ? 'amber' : 'teal' }}"><i class="bi bi-telephone-x"></i></span>
                    <div class="kpi-meta">
                        <div class="kpi-label">Pending to Call</div>
                        <div class="kpi-value">{{ $pendingToCallCount }}</div>
                    </div>
                </div>
            </a>
        </div>
        @endif
        <div class="col-6 {{ $canSeeAssignedContacts ? 'col-xl-2' : 'col-xl-3' }}">
            <a href="{{ route('goal.vrm.index', ['tab' => 'calls', 'from_date' => $todayIso, 'to_date' => $todayIso]) }}" class="text-decoration-none">
                <div class="kpi-card tone-blue">
                    <span class="kpi-icon blue"><i class="bi bi-telephone"></i></span>
                    <div class="kpi-meta">
                        <div class="kpi-label">Calls Today</div>
                        <div class="kpi-value">{{ $todayCount }}</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 {{ $canSeeAssignedContacts ? 'col-xl-2' : 'col-xl-3' }}">
            <a href="{{ route('goal.vrm.index', ['tab' => 'calls', 'callbacks_due' => 1]) }}" class="text-decoration-none">
                <div class="kpi-card tone-{{ $callbacksDueCount > 0 ? 'orange' : 'blue' }}">
                    <span class="kpi-icon {{ $callbacksDueCount > 0 ? 'orange' : 'blue' }}"><i class="bi bi-alarm"></i></span>
                    <div class="kpi-meta">
                        <div class="kpi-label">Callbacks due</div>
                        <div class="kpi-value">{{ $callbacksDueCount }}</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 {{ $canSeeAssignedContacts ? 'col-xl-2' : 'col-xl-3' }}">
            <a href="{{ route('goal.vrm.index', ['tab' => 'calls', 'lead_status' => 'hot']) }}" class="text-decoration-none">
                <div class="kpi-card tone-green">
                    <span class="kpi-icon green"><i class="bi bi-fire"></i></span>
                    <div class="kpi-meta">
                        <div class="kpi-label">Hot Leads</div>
                        <div class="kpi-value">{{ $hotLeadCount }}</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 {{ $canSeeAssignedContacts ? 'col-xl-2' : 'col-xl-3' }}">
            <a href="{{ route('goal.vrm.index', ['tab' => 'calls', 'lead_status' => 'yes']) }}" class="text-decoration-none">
                <div class="kpi-card tone-green">
                    <span class="kpi-icon green"><i class="bi bi-person-plus"></i></span>
                    <div class="kpi-meta">
                        <div class="kpi-label">Leads Created</div>
                        <div class="kpi-value">{{ $leadsCreatedCount }}</div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    @if($activeTab === 'assigned' && $canSeeAssignedContacts)
    @php
        // #, customer, mobile, [vrm], branch, product, lead status, call status, last call, assigned on, actions
        $assignedColCount = $isBmAssignedView ? 11 : 10;
    @endphp
    <div class="vrm-card">
        <div class="vrm-card-head">
            <div class="vrm-card-head-left">
                <span class="section-icon"><i class="bi bi-people"></i></span>
                <div>
                    <h5>Assigned Contacts</h5>
                    <div class="head-sub">
                        @if($isBmAssignedView)
                            Contacts assigned to your branch VRMs. Filter by VRM and log calls as needed.
                        @else
                            Contacts assigned to you. Log a call to update status and add to All Call Logs.
                        @endif
                    </div>
                </div>
            </div>
            <div class="toolbar">
                <input type="search" id="assignedTableSearch" class="form-control toolbar-search"
                       placeholder="Search table...">
                <div class="toolbar-actions">
                    <button type="button" class="btn-tool" id="btnAssignedColVis">
                        <i class="bi bi-layout-three-columns"></i> <span class="tool-label">Columns</span>
                    </button>
                </div>
            </div>
        </div>
        @if($isBmAssignedView && $vrmOptions->isNotEmpty())
        <div class="vrm-count-chips" role="group" aria-label="Filter by VRM">
            <a class="vrm-count-chip {{ $filterVrmId === 0 ? 'on' : '' }}"
               href="{{ route('goal.vrm.index', array_merge(request()->except('vrm_id', 'assigned_page'), ['tab' => 'assigned'])) }}">
                All VRMs<span class="n">{{ (int) $assignedContactsCount }}</span>
            </a>
            @foreach($vrmOptions as $vrm)
                @php $vCount = (int) ($vrmAssignedCounts[(int) $vrm->id] ?? 0); @endphp
                <a class="vrm-count-chip {{ $filterVrmId === (int) $vrm->id ? 'on' : '' }}"
                   href="{{ route('goal.vrm.index', array_merge(request()->except('assigned_page'), ['tab' => 'assigned', 'vrm_id' => $vrm->id])) }}">
                    {{ $vrm->name }}<span class="n">{{ $vCount }}</span>
                </a>
            @endforeach
        </div>
        @endif
        <div class="vrm-card-body">
            <div class="calls-table-wrap">
                <table class="table align-middle mb-0" id="vrmAssignedTable" style="width:100%">
                    <thead>
                        <tr>
                            <th class="col-num">#</th>
                            <th>Customer</th>
                            <th>Mobile</th>
                            @if($isBmAssignedView)
                                <th>VRM</th>
                            @endif
                            <th>Branch</th>
                            <th>Product</th>
                            <th>Lead Status</th>
                            <th>Call Status</th>
                            <th>Last Call</th>
                            <th>Assigned On</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignedContacts as $contact)
                            @php
                                $lastCall = $contact->vrmCalls->first();
                                $hasCalled = (int) ($contact->vrm_calls_count ?? 0) > 0;
                                $callLabel = $hasCalled
                                    ? ('Called'.($lastCall?->disposition ? ' - '.$lastCall->disposition : ''))
                                    : 'Pending to Call';
                                $statusRaw = strtolower(trim((string) ($contact->status ?? '')));
                                $leadTone = 'tone-muted';
                                if (str_contains($statusRaw, 'document')) {
                                    $leadTone = 'tone-docs';
                                } elseif (str_contains($statusRaw, 'assign') || str_contains($statusRaw, 'new')) {
                                    $leadTone = '';
                                } elseif (str_contains($statusRaw, 'interest') || str_contains($statusRaw, 'convert') || str_contains($statusRaw, 'disburs')) {
                                    $leadTone = 'tone-ok';
                                } elseif (str_contains($statusRaw, 'pending') || str_contains($statusRaw, 'follow')) {
                                    $leadTone = 'tone-warn';
                                } elseif (str_contains($statusRaw, 'reject') || str_contains($statusRaw, 'lost') || str_contains($statusRaw, 'cancel')) {
                                    $leadTone = 'tone-bad';
                                }
                                $mobileDigits = preg_replace('/\D+/', '', (string) ($contact->mobile ?? '')) ?: '';
                                $assignedAt = $contact->assigned_at;
                                $assignedLabel = $assignedAt
                                    ? ($assignedAt->isToday() ? 'Today' : $assignedAt->format('d M Y'))
                                    : '—';
                                $assignedExact = $assignedAt ? $assignedAt->format('d-m-Y H:i') : '';
                                $assignedByName = optional($contact->assigner)->name;
                            @endphp
                            <tr>
                                <td class="col-num" data-label="#">{{ $assignedContacts->firstItem() + $loop->index }}</td>
                                <td class="col-customer" data-label="Customer">
                                    <div class="cust-name">{{ $contact->customer_name ?: '—' }}</div>
                                    @if($contact->ref_no)
                                        <div class="cust-ref">{{ $contact->ref_no }}</div>
                                    @endif
                                </td>
                                <td data-label="Mobile">
                                    @if($mobileDigits !== '')
                                        <a class="mobile-cell" href="tel:{{ $mobileDigits }}">
                                            <i class="bi bi-telephone-fill" aria-hidden="true"></i>
                                            <span>{{ $contact->mobile }}</span>
                                        </a>
                                    @else
                                        <span class="cust-mobile">—</span>
                                    @endif
                                </td>
                                @if($isBmAssignedView)
                                    <td data-label="VRM">{{ optional($contact->assignee)->name ?: '—' }}</td>
                                @endif
                                <td data-label="Branch" title="{{ optional($contact->orgUnit)->name }}">{{ optional($contact->orgUnit)->name ?: '—' }}</td>
                                <td data-label="Product" title="{{ $contact->product }}">{{ $contact->product ?: '—' }}</td>
                                <td data-label="Lead Status">
                                    @if($contact->status)
                                        <span class="lead-status-pill {{ $leadTone }}">{{ $contact->status }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td data-label="Call Status">
                                    <span class="call-status {{ $hasCalled ? 'called' : 'pending' }}">
                                        <i class="bi bi-{{ $hasCalled ? 'check2-circle' : 'telephone' }}"></i>
                                        {{ $callLabel }}
                                    </span>
                                </td>
                                <td data-label="Last Call">
                                    @if($lastCall && $lastCall->called_at)
                                        <div class="when-cell">
                                            <span class="when-icon"><i class="bi bi-telephone"></i></span>
                                            <div class="when-text">
                                                <div class="when-main">{{ $lastCall->called_at->isToday() ? 'Today, '.$lastCall->called_at->format('g:i A') : $lastCall->called_at->format('d M, g:i A') }}</div>
                                                <div class="when-sub">{{ $lastCall->called_at->format('d-m-Y H:i') }}</div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td data-label="Assigned On">
                                    <div class="when-text">
                                        <div class="when-main">{{ $assignedLabel }}</div>
                                        @if($assignedByName)
                                            <div class="when-sub">by {{ $assignedByName }}</div>
                                        @elseif($assignedExact)
                                            <div class="when-sub">{{ $assignedExact }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="actions-cell col-actions" data-label="Actions">
                                    <div class="action-group">
                                        <button type="button"
                                            class="btn-log-row js-log-assigned"
                                            data-tip="Log Call"
                                            aria-label="Log Call"
                                            title="Log Call"
                                            data-id="{{ (int) $contact->id }}"
                                            data-name="{{ e($contact->customer_name) }}"
                                            data-mobile="{{ e($contact->mobile) }}"
                                            data-ref="{{ e($contact->ref_no) }}"
                                            data-status="{{ e($contact->status) }}"
                                            data-product="{{ e($contact->product) }}"
                                            data-amount="{{ $contact->loan_amount !== null ? (float) $contact->loan_amount : '' }}"
                                            data-branch-id="{{ (int) ($contact->goal_org_unit_id ?? 0) }}"
                                            data-branch-name="{{ e(optional($contact->orgUnit)->name) }}">
                                            <i class="bi bi-telephone-plus"></i> Log Call
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $assignedColCount }}" class="col-empty" data-label="">
                                    <div class="empty-state">
                                        <i class="bi bi-people"></i>
                                        <div>No assigned contacts found.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if(method_exists($assignedContacts, 'links'))
            <div class="table-footer-bar">
                <div class="foot-showing">
                    @if($assignedContacts->total() > 0)
                        Showing {{ $assignedContacts->firstItem() }} – {{ $assignedContacts->lastItem() }} of {{ $assignedContacts->total() }}
                    @else
                        Showing 0 contacts
                    @endif
                </div>
                <div class="foot-right">
                    <form method="GET" action="{{ route('goal.vrm.index') }}" class="d-inline-flex align-items-center gap-2" style="font-size:.8rem;color:#64748b;">
                        @foreach(request()->except(['per_page', 'assigned_page']) as $fk => $fv)
                            @if(is_array($fv))
                                @foreach($fv as $fvv)
                                    <input type="hidden" name="{{ $fk }}[]" value="{{ $fvv }}">
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $fk }}" value="{{ $fv }}">
                            @endif
                        @endforeach
                        <input type="hidden" name="tab" value="assigned">
                        <span>Rows per page:</span>
                        <select name="per_page" class="form-select form-select-sm" style="width:auto;min-width:64px;" data-select2-skip="1" onchange="this.form.submit()">
                            @foreach([10, 25, 50, 100] as $n)
                                <option value="{{ $n }}" @selected((int)($perPage ?? 25) === $n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </form>
                    <div class="foot-pager">
                        {{ $assignedContacts->onEachSide(1)->links('vendor.pagination.users') }}
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
    @elseif($activeTab === 'analytics')
    @php
        $dispCounts = $callAnalytics['disposition_counts'] ?? [];
        $dispMax = $dispCounts !== [] ? max($dispCounts) : 0;
        $interestCounts = $callAnalytics['interest_counts'] ?? [];
    @endphp
    <div class="vrm-card">
        <div class="vrm-card-head">
            <div class="vrm-card-head-left">
                <span class="section-icon"><i class="bi bi-bar-chart-line"></i></span>
                <div>
                    <h5>Call Analytics</h5>
                    <div class="head-sub">Aggregates for {{ $callAnalytics['from'] ?? '—' }} → {{ $callAnalytics['to'] ?? '—' }} (your visibility scope).</div>
                </div>
            </div>
        </div>
        <div class="analytics-grid">
            <div class="analytics-card">
                <div class="al">Assigned contacts</div>
                <div class="av">{{ (int) ($callAnalytics['assigned_contacts'] ?? 0) }}</div>
            </div>
            <div class="analytics-card">
                <div class="al">Total calls</div>
                <div class="av">{{ (int) ($callAnalytics['total_calls'] ?? 0) }}</div>
            </div>
            <div class="analytics-card">
                <div class="al">Connected</div>
                <div class="av">{{ (int) ($callAnalytics['connected'] ?? 0) }}</div>
            </div>
            <div class="analytics-card">
                <div class="al">Not connected</div>
                <div class="av">{{ (int) ($callAnalytics['not_connected'] ?? 0) }}</div>
            </div>
            <div class="analytics-card">
                <div class="al">Interested</div>
                <div class="av">{{ (int) ($callAnalytics['interested'] ?? 0) }}</div>
            </div>
            <div class="analytics-card">
                <div class="al">Callbacks scheduled</div>
                <div class="av">{{ (int) ($callAnalytics['callbacks'] ?? 0) }}</div>
            </div>
            <div class="analytics-card">
                <div class="al">Leads created</div>
                <div class="av">{{ (int) ($callAnalytics['leads_created'] ?? 0) }}</div>
            </div>
            <div class="analytics-card">
                <div class="al">Conversion</div>
                <div class="av">{{ $callAnalytics['conversion_pct'] ?? 0 }}%</div>
            </div>
        </div>
        <div class="analytics-charts">
            <div class="chart-panel">
                <h6>Daily Calling Trend</h6>
                <div class="chart-wrap"><canvas id="vrmDailyTrendChart"></canvas></div>
            </div>
            <div class="chart-panel">
                <h6>Call Disposition Distribution</h6>
                <div class="chart-wrap"><canvas id="vrmDispositionChart"></canvas></div>
            </div>
            <div class="chart-panel">
                <h6>Interest Level Distribution</h6>
                <div class="chart-wrap"><canvas id="vrmInterestChart"></canvas></div>
            </div>
            <div class="chart-panel">
                <h6>Lead Conversion Trend</h6>
                <div class="chart-wrap"><canvas id="vrmLeadTrendChart"></canvas></div>
            </div>
            <div class="chart-panel">
                <h6>Follow-up Performance</h6>
                <div class="chart-wrap"><canvas id="vrmFollowUpChart"></canvas></div>
            </div>
            <div class="chart-panel">
                <h6>Disposition breakdown</h6>
                @if($dispCounts !== [])
                <div class="disp-bars" style="padding:0;">
                    @foreach($dispCounts as $dispName => $dispTotal)
                        @php $pctW = $dispMax > 0 ? round(((int) $dispTotal / $dispMax) * 100) : 0; @endphp
                        <div class="disp-bar-row">
                            <span>{{ $dispName }}</span>
                            <div class="track"><span class="fill" style="width: {{ $pctW }}%"></span></div>
                            <strong>{{ (int) $dispTotal }}</strong>
                        </div>
                    @endforeach
                </div>
                @else
                <div class="text-muted small">No disposition data in this range.</div>
                @endif
            </div>
        </div>
    </div>
    @php
        $vrmAnalyticsCharts = [
            'daily' => $callAnalytics['daily_trend'] ?? ['labels' => [], 'values' => []],
            'disposition' => [
                'labels' => array_keys($dispCounts),
                'values' => array_values(array_map('intval', $dispCounts)),
            ],
            'interest' => [
                'labels' => array_keys($interestCounts),
                'values' => array_values(array_map('intval', $interestCounts)),
            ],
            'leads' => $callAnalytics['lead_trend'] ?? ['labels' => [], 'values' => []],
            'follow_up' => [
                'labels' => ['Due / Overdue', 'Upcoming'],
                'values' => [
                    (int) ($callAnalytics['follow_up_due'] ?? 0),
                    (int) ($callAnalytics['follow_up_upcoming'] ?? 0),
                ],
            ],
        ];
    @endphp
    <script>
    window.__vrmAnalyticsCharts = @json($vrmAnalyticsCharts);
    </script>
    @else
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
                            <th>Interest</th>
                            <th>Duration</th>
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
                            <td data-label="Interest">{{ $log->interest_level ?: '—' }}</td>
                            <td data-label="Duration">
                                @if($log->duration_seconds !== null)
                                    {{ (int) $log->duration_seconds }}s
                                @else
                                    —
                                @endif
                            </td>
                            <td data-label="Callback">
                                @if($cbLabel)
                                    <div class="when-cell">
                                        <span class="when-icon {{ $cbDue ? 'text-danger' : '' }}"><i class="bi bi-alarm"></i></span>
                                        <div class="when-text">
                                            <div class="when-main {{ $cbDue ? 'text-danger fw-semibold callback-overdue' : '' }}">{{ $cbLabel }}</div>
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
                                            data-tip="View details"
                                            aria-label="View details"
                                            data-url="{{ route('goal.vrm.calls.show', $log) }}">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    @if($isOwnLog)
                                    <button type="button"
                                            class="action-btn edit btn-edit-call"
                                            data-tip="Edit"
                                            aria-label="Edit"
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
                                                data-tip="Delete"
                                                aria-label="Delete">
                                            <i class="bi bi-trash3-fill"></i>
                                        </button>
                                    </form>
                                    @endunless
                                    @endif
                                    @if($log->goal_loan_lead_id)
                                        <a href="{{ route('goal.leads.show', $log->goal_loan_lead_id) }}"
                                           class="action-btn lead"
                                           data-tip="Open lead"
                                           aria-label="Open lead">
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
    @endif
</div>

{{-- Log Call Modal --}}
<div class="modal fade vrm-call-modal" id="logCallModal" tabindex="-1" aria-labelledby="logCallModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('goal.vrm.store') }}" id="vrmCallForm" class="vrm-log-form" novalidate
                  autocomplete="off" data-assigned-search="{{ route('goal.vrm.assigned-contacts') }}">
                @csrf
                <input type="hidden" name="goal_loan_lead_id" id="goal_loan_lead_id" value="{{ old('goal_loan_lead_id') }}">
                <input type="hidden" name="return_tab" id="return_tab" value="{{ $activeTab }}">
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
                            <div class="field {{ $errors->has('called_at') ? 'is-invalid' : '' }}">
                                <label class="form-label" for="called_at">Call date &amp; time</label>
                                <div class="input-icon">
                                    <i class="bi bi-clock-history"></i>
                                    <input type="text" name="called_at" id="called_at"
                                           class="form-control js-datetimepicker {{ $errors->has('called_at') ? 'is-invalid' : '' }}"
                                           placeholder="dd-mm-yyyy --:--" value="{{ old('called_at', now()->format('d-m-Y H:i')) }}"
                                           autocomplete="off">
                                </div>
                                <div class="field-error {{ $errors->has('called_at') ? 'show' : '' }}" data-error-for="called_at">{{ $errors->first('called_at') }}</div>
                            </div>
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
                            <div class="field {{ $errors->has('callback_at') ? 'is-invalid' : '' }}" id="callbackAtField">
                                <label class="form-label" for="callback_at">Follow-up date &amp; time <span class="req follow-up-required-mark" hidden>*</span></label>
                                <div class="input-icon">
                                    <i class="bi bi-calendar3"></i>
                                    <input type="text" name="callback_at" id="callback_at"
                                           class="form-control js-datetimepicker {{ $errors->has('callback_at') ? 'is-invalid' : '' }}"
                                           placeholder="dd-mm-yyyy --:--" value="{{ old('callback_at') }}"
                                           autocomplete="off"
                                           data-required-message="Follow-up date and time are required for Callback.">
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
                        </div>
                        <div class="create-lead-row">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="create_lead" value="1" id="create_lead" @checked(old('create_lead'))>
                                <label class="form-check-label" for="create_lead" id="create_lead_label">Create lead when disposition allows</label>
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
                            <div class="field field-span-2 {{ $errors->has('notes') ? 'is-invalid' : '' }}">
                                <label class="form-label" for="notes">Remarks <span class="req notes-required-mark" hidden>*</span></label>
                                <div class="input-icon textarea-icon">
                                    <i class="bi bi-file-text"></i>
                                    <textarea name="notes" id="notes"
                                              class="form-control {{ $errors->has('notes') ? 'is-invalid' : '' }}" rows="2"
                                              placeholder="Add call notes, customer response, or remarks..."
                                              data-required-message="Remarks are required for this disposition.">{{ old('notes') }}</textarea>
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
    // Lightweight action tooltips (Bootstrap Tooltip is not bundled in this app)
    (function initVrmActionTips() {
        let tipEl = null;
        let showTimer = null;

        function ensureTip() {
            if (tipEl) return tipEl;
            tipEl = document.createElement('div');
            tipEl.className = 'vrm-action-tip';
            tipEl.setAttribute('role', 'tooltip');
            document.body.appendChild(tipEl);
            return tipEl;
        }

        function hideTip() {
            if (showTimer) {
                clearTimeout(showTimer);
                showTimer = null;
            }
            if (tipEl) tipEl.classList.remove('is-on');
        }

        function showTip(anchor) {
            const text = (anchor.getAttribute('data-tip') || anchor.getAttribute('aria-label') || '').trim();
            if (!text) return;
            const tip = ensureTip();
            tip.textContent = text;
            tip.classList.remove('is-on');
            tip.style.visibility = 'hidden';
            tip.style.display = 'block';
            const rect = anchor.getBoundingClientRect();
            const tipW = tip.offsetWidth || 0;
            const tipH = tip.offsetHeight || 0;
            tip.style.left = Math.round(rect.left + rect.width / 2 - tipW / 2) + 'px';
            tip.style.top = Math.round(rect.top - tipH - 10) + 'px';
            tip.style.visibility = '';
            showTimer = setTimeout(function () {
                tip.classList.add('is-on');
            }, 60);
        }

        document.addEventListener('mouseover', function (e) {
            const anchor = e.target.closest('.vrm-page [data-tip]');
            if (!anchor) return;
            showTip(anchor);
        });
        document.addEventListener('mouseout', function (e) {
            const anchor = e.target.closest('.vrm-page [data-tip]');
            if (!anchor) return;
            const to = e.relatedTarget;
            if (to && anchor.contains(to)) return;
            hideTip();
        });
        document.addEventListener('focusin', function (e) {
            const anchor = e.target.closest('.vrm-page [data-tip]');
            if (anchor) showTip(anchor);
        });
        document.addEventListener('focusout', function (e) {
            const anchor = e.target.closest('.vrm-page [data-tip]');
            if (anchor) hideTip();
        });
        document.addEventListener('scroll', hideTip, true);
        window.addEventListener('resize', hideTip);
    })();

    // Single date-range picker → hidden from_date / to_date
    function initVrmDateRanges() {
        const fp = window.flatpickr;
        if (typeof fp !== 'function') return;

        document.querySelectorAll('.js-vrm-daterange').forEach(function (rangeEl) {
            const wrap = rangeEl.closest('[data-vrm-daterange]');
            const fromEl = wrap ? wrap.querySelector('input[name="from_date"]') : null;
            const toEl = wrap ? wrap.querySelector('input[name="to_date"]') : null;
            if (!fromEl || !toEl) return;
            if (rangeEl._flatpickr) {
                try { rangeEl._flatpickr.destroy(); } catch (e) {}
            }

            const fromVal = rangeEl.getAttribute('data-from') || fromEl.value || '';
            const toVal = rangeEl.getAttribute('data-to') || toEl.value || '';
            const defaults = [];
            if (fromVal) defaults.push(fromVal);
            if (toVal) defaults.push(toVal);

            const instance = fp(rangeEl, {
                mode: 'range',
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd M Y',
                allowInput: false,
                clickOpens: true,
                disableMobile: true,
                static: false,
                defaultDate: defaults.length ? defaults : null,
                appendTo: document.body,
                onReady: function (_d, _s, inst) {
                    if (inst.altInput) {
                        inst.altInput.classList.add('form-control', 'flatpickr-alt');
                        inst.altInput.placeholder = rangeEl.getAttribute('placeholder') || 'Select date range';
                        inst.altInput.readOnly = true;
                    }
                    // Keep calendar above filter cards / select2
                    if (inst.calendarContainer) {
                        inst.calendarContainer.style.zIndex = '20050';
                    }
                },
                onChange: function (selectedDates, _str, inst) {
                    fromEl.value = selectedDates[0] ? inst.formatDate(selectedDates[0], 'Y-m-d') : '';
                    toEl.value = selectedDates[1]
                        ? inst.formatDate(selectedDates[1], 'Y-m-d')
                        : (selectedDates[0] ? inst.formatDate(selectedDates[0], 'Y-m-d') : '');
                },
                onClose: function (selectedDates, _str, inst) {
                    if (selectedDates.length === 1) {
                        fromEl.value = inst.formatDate(selectedDates[0], 'Y-m-d');
                        toEl.value = fromEl.value;
                    }
                }
            });

            if (wrap && !wrap.dataset.fpBound) {
                wrap.dataset.fpBound = '1';
                wrap.addEventListener('click', function (e) {
                    if (e.target.closest('.flatpickr-calendar')) return;
                    const fpInst = rangeEl._flatpickr;
                    if (fpInst && typeof fpInst.open === 'function') fpInst.open();
                });
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initVrmDateRanges);
    } else {
        initVrmDateRanges();
    }

    const $ = window.jQuery;
    if ($) {
        const tableHasRows = {{ ($activeTab === 'calls' && $logs->count() > 0) ? 'true' : 'false' }};
        const assignedHasRows = {{ ($activeTab === 'assigned' && method_exists($assignedContacts, 'count') && $assignedContacts->count() > 0) ? 'true' : 'false' }};
        const analyticsTab = {{ $activeTab === 'analytics' ? 'true' : 'false' }};
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
        const isBmAssignedView = @json(!empty($isBmAssignedView));
        const actionsCol = seesTeamLogs ? 12 : 11;
        const assignedActionsCol = isBmAssignedView ? 10 : 9;
        let assignedDt = null;

        function hideColVisPopover() {
            $('#colVisPopover').hide().removeData('owner');
        }

        function bindColVis(btnId, tableApi, menu, map) {
            $('#' + btnId).on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                let $pop = $('#colVisPopover');
                // Toggle closed if already open for this button
                if ($pop.length && $pop.is(':visible') && $pop.data('owner') === btnId) {
                    hideColVisPopover();
                    return;
                }

                let html = '<div class="p-2">';
                menu.forEach(function (label) {
                    const idx = map[label];
                    const visible = tableApi.column(idx).visible();
                    html += '<label class="d-block mb-1" style="font-size:.85rem;cursor:pointer;">'
                        + '<input type="checkbox" class="form-check-input me-2 col-toggle" data-col="' + idx + '" '
                        + (visible ? 'checked' : '') + '> ' + label + '</label>';
                });
                html += '</div>';

                if (!$pop.length) {
                    $pop = $('<div id="colVisPopover" class="shadow border bg-white rounded-3"></div>');
                    $('body').append($pop);
                }
                $pop.data('owner', btnId).html(html).css({
                    position: 'fixed',
                    zIndex: 1080,
                    minWidth: '180px',
                    maxWidth: '240px',
                    maxHeight: '70vh',
                    overflowY: 'auto',
                    left: 'auto',
                    right: 'auto',
                    top: '0',
                    visibility: 'hidden',
                    display: 'block'
                });

                const btn = this.getBoundingClientRect();
                const popW = $pop.outerWidth() || 180;
                const popH = $pop.outerHeight() || 200;
                const pad = 8;
                let top = btn.bottom + 6;
                if (top + popH > window.innerHeight - pad) {
                    top = Math.max(pad, btn.top - popH - 6);
                }
                let left = btn.right - popW;
                left = Math.max(pad, Math.min(left, window.innerWidth - popW - pad));

                $pop.css({
                    top: top + 'px',
                    left: left + 'px',
                    right: 'auto',
                    visibility: 'visible'
                }).show();

                $pop.find('.col-toggle').on('change', function () {
                    tableApi.column(parseInt(this.dataset.col, 10)).visible(this.checked);
                    const $tbl = $(tableApi.table().node());
                    $tbl.find('th, td').css({ width: '', minWidth: '', maxWidth: '' });
                    $tbl.css('width', 'max-content');
                });
            });
        }

        // Close Columns popover on outside click / Escape (works on every tab)
        $(document).on('click.vrmColVis', function (e) {
            if (!$(e.target).closest('#btnColVis, #btnAssignedColVis, #colVisPopover').length) {
                hideColVisPopover();
            }
        });
        $(document).on('keydown.vrmColVis', function (e) {
            if (e.key === 'Escape') hideColVisPopover();
        });

        if (tableHasRows && $.fn.DataTable && !isMobileTable && $('#vrmCallsTable').length) {
            dt = $('#vrmCallsTable').DataTable({
                paging: false,
                info: false,
                searching: true,
                ordering: true,
                autoWidth: true,
                scrollX: false,
                order: [],
                columnDefs: [
                    { orderable: false, targets: [actionsCol] },
                    { className: 'col-num', targets: [0] },
                    { className: 'col-actions', targets: [actionsCol] }
                ],
                dom: 't',
                language: { emptyTable: 'No call logs found.' }
            });
            // Drop inline widths DataTables injects — they desync header/body
            $('#vrmCallsTable').find('th, td').css({ width: '', minWidth: '', maxWidth: '' });
            $('#vrmCallsTable').css('width', 'max-content');

            $('#callsTableSearch').on('keyup', function () {
                dt.search(this.value).draw();
            });

            const callsMenu = ['When', 'Customer', 'Mobile', 'Disposition', 'Interest', 'Duration', 'Callback', 'Lead', 'Notes', 'Branch'];
            const callsMap = { When: 1, Customer: 2, Mobile: 3, Disposition: 4, Interest: 5, Duration: 6, Callback: 7, Lead: 8, Notes: 9, Branch: 10 };
            if (seesTeamLogs) {
                callsMenu.push('Called by');
                callsMap['Called by'] = 11;
            }
            bindColVis('btnColVis', dt, callsMenu, callsMap);
        } else {
            $('#callsTableSearch').on('keyup', function () {
                filterMobileCards(this.value);
            });
            $('#btnColVis').prop('disabled', true).addClass('disabled').attr('title', 'Columns available on desktop');
        }

        if (assignedHasRows && $.fn.DataTable && !isMobileTable && $('#vrmAssignedTable').length) {
            assignedDt = $('#vrmAssignedTable').DataTable({
                paging: false,
                info: false,
                searching: true,
                ordering: true,
                autoWidth: true,
                scrollX: false,
                order: [],
                columnDefs: [
                    { orderable: false, targets: [assignedActionsCol] },
                    { className: 'col-num', targets: [0] },
                    { className: 'col-actions', targets: [assignedActionsCol] }
                ],
                dom: 't',
                language: { emptyTable: 'No assigned contacts found.' }
            });
            $('#vrmAssignedTable').find('th, td').css({ width: '', minWidth: '', maxWidth: '' });
            $('#vrmAssignedTable').css('width', 'max-content');

            $('#assignedTableSearch').on('keyup', function () {
                assignedDt.search(this.value).draw();
            });

            const assignedMenu = ['Customer', 'Mobile'];
            const assignedMap = { Customer: 1, Mobile: 2 };
            let nextIdx = 3;
            if (isBmAssignedView) {
                assignedMenu.push('VRM');
                assignedMap.VRM = nextIdx++;
            }
            ['Branch', 'Product', 'Lead Status', 'Call Status', 'Last Call', 'Assigned On'].forEach(function (label) {
                assignedMenu.push(label);
                assignedMap[label] = nextIdx++;
            });
            bindColVis('btnAssignedColVis', assignedDt, assignedMenu, assignedMap);
        } else {
            $('#assignedTableSearch').on('keyup', function () {
                const q = String(this.value || '').toLowerCase().trim();
                $('#vrmAssignedTable tbody tr').each(function () {
                    if ($(this).find('td.col-empty').length) return;
                    const text = $(this).text().toLowerCase();
                    $(this).toggle(!q || text.indexOf(q) !== -1);
                });
            });
            $('#btnAssignedColVis').prop('disabled', true).addClass('disabled').attr('title', 'Columns available on desktop');
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

    function syncFollowUpFields() {
        const disp = document.getElementById('disposition');
        const callback = document.getElementById('callback_at');
        const notes = document.getElementById('notes');
        const dispName = (disp && disp.value) || '';
        const followNeeded = dispName === 'Callback';
        form.querySelectorAll('.follow-up-required-mark').forEach(function (mark) {
            mark.hidden = !followNeeded;
        });
        if (callback) {
            callback.removeAttribute('disabled');
            if (followNeeded) callback.setAttribute('required', 'required');
            else callback.removeAttribute('required');
        }
        const notesNeeded = dispName === 'Invalid Number';
        form.querySelectorAll('.notes-required-mark').forEach(function (mark) {
            mark.hidden = !notesNeeded;
        });
        if (notes) {
            if (notesNeeded) notes.setAttribute('required', 'required');
            else notes.removeAttribute('required');
        }
    }

    function validateForm() {
        clearAllErrors();
        syncLeadRequiredFields();
        syncFollowUpFields();
        let firstInvalid = null;
        form.querySelectorAll('[required]').forEach((el) => {
            if (el.disabled) return;
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
        clearAllErrors();
        hideSuggestMenus();

        const leadId = document.getElementById('goal_loan_lead_id');
        if (leadId) leadId.value = '';
        setContactSourceHint(null);

        const clearIds = [
            'customer_name', 'mobile', 'notes', 'loan_amount',
            'product', 'callback_at', 'called_at'
        ];
        clearIds.forEach(function (id) {
            const el = document.getElementById(id);
            if (!el) return;
            if (el.tagName === 'SELECT') el.value = '';
            else el.value = '';
            if (el._flatpickr) el._flatpickr.clear();
        });

        const createLead = document.getElementById('create_lead');
        if (createLead) {
            createLead.checked = false;
            createLead.disabled = false;
        }

        const disp = document.getElementById('disposition');
        if (disp) {
            const preferred = Array.from(disp.options).find(function (o) { return o.value === 'Interested'; });
            disp.value = preferred ? 'Interested' : (disp.options[0]?.value || '');
        }

        const nowStr = (function () {
            const d = new Date();
            const pad = function (n) { return String(n).padStart(2, '0'); };
            return pad(d.getDate()) + '-' + pad(d.getMonth() + 1) + '-' + d.getFullYear()
                + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
        })();
        const calledAt = document.getElementById('called_at');
        if (calledAt) {
            if (calledAt._flatpickr) calledAt._flatpickr.setDate(nowStr, true);
            else calledAt.value = nowStr;
        }

        const branchSel = document.getElementById('goal_org_unit_id');
        if (branchSel && !branchSel.disabled) {
            const defaultBranch = @json(auth()->user()->goal_org_unit_id ? (string) auth()->user()->goal_org_unit_id : '');
            branchSel.value = defaultBranch;
        }

        if (window.jQuery) {
            window.jQuery(form).find('select.js-select2').each(function () {
                window.jQuery(this).val(this.value || null).trigger('change');
            });
        }

        syncCreateLeadAvailability({ autoCheck: true });
        syncFollowUpFields();
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
        // Drop lead-linked fields so a new contact does not keep previous product/amount.
        if (!options.keepLeadFields) {
            const productSel = document.getElementById('product');
            if (productSel) {
                productSel.value = '';
                if (window.jQuery) window.jQuery(productSel).val(null).trigger('change');
            }
            const loanAmt = document.getElementById('loan_amount');
            if (loanAmt) loanAmt.value = '';
            const createLead = document.getElementById('create_lead');
            if (createLead) createLead.disabled = false;
        }
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

    window.vrmApplyAssignedContact = applyAssignedContact;

    document.querySelectorAll('.js-log-assigned').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const item = {
                id: parseInt(btn.getAttribute('data-id') || '0', 10) || 0,
                customer_name: btn.getAttribute('data-name') || '',
                mobile: btn.getAttribute('data-mobile') || '',
                ref_no: btn.getAttribute('data-ref') || '',
                status: btn.getAttribute('data-status') || '',
                product: btn.getAttribute('data-product') || '',
                loan_amount: btn.getAttribute('data-amount') || '',
                goal_org_unit_id: parseInt(btn.getAttribute('data-branch-id') || '0', 10) || null,
                branch_name: btn.getAttribute('data-branch-name') || ''
            };
            const returnTab = document.getElementById('return_tab');
            if (returnTab) returnTab.value = 'assigned';
            applyAssignedContact(item);
            const modal = document.getElementById('logCallModal');
            if (modal && window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(modal).show();
            } else if (window.jQuery) {
                window.jQuery(modal).modal('show');
            }
        });
    });

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
        syncFollowUpFields();
    });
    if (window.jQuery) {
        window.jQuery('#disposition').on('select2:select select2:clear change', function () {
            syncCreateLeadAvailability({ autoCheck: true });
            syncFollowUpFields();
        });
    }
    modalEl.addEventListener('shown.bs.modal', function () {
        // Default disposition (e.g. Hot Lead / Interested) should auto-check create-lead when allowed.
        syncCreateLeadAvailability({ autoCheck: true });
        syncFollowUpFields();
    });
    syncCreateLeadAvailability({ autoCheck: true });

    const shouldResetForNew = @json((bool) session('reopen_log_call') && ! $errors->any());

    // Header "+ Log Call" — always start blank (do not keep previous contact).
    document.querySelectorAll('.btn-log-call[data-bs-target="#logCallModal"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            resetFormDefaults();
        });
    });

    @if($openModal)
        if (shouldResetForNew) {
            // Clear before show so Save & New never flashes previous values.
            resetFormDefaults();
        }
        modal.show();
        if (shouldResetForNew) {
            // Re-apply after show so Select2 / flatpickr paint the empty state.
            setTimeout(function () { resetFormDefaults(); }, 0);
        }
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
@if(($activeTab ?? '') === 'analytics')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const data = window.__vrmAnalyticsCharts || {};
    const green = '#16a34a';
    const greens = ['#86efac', '#4ade80', '#22c55e', '#16a34a', '#15803d', '#166534'];
    function emptyNote(canvasId) {
        const el = document.getElementById(canvasId);
        if (!el || !el.parentElement) return;
        el.parentElement.innerHTML = '<div class="text-muted small py-4 text-center">No data in selected range.</div>';
    }
    function makeChart(canvasId, type, labels, values, opts) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || typeof Chart === 'undefined') return;
        if (!labels || !labels.length) {
            emptyNote(canvasId);
            return;
        }
        new Chart(canvas, {
            type: type,
            data: {
                labels: labels,
                datasets: [{
                    label: (opts && opts.label) || 'Count',
                    data: values,
                    backgroundColor: type === 'line' ? 'rgba(22,163,74,.15)' : greens,
                    borderColor: green,
                    borderWidth: type === 'line' ? 2 : 0,
                    fill: type === 'line',
                    tension: 0.3,
                    pointRadius: 3,
                    borderRadius: type === 'bar' ? 6 : 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: type === 'doughnut' || type === 'pie' } },
                scales: (type === 'doughnut' || type === 'pie') ? {} : {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
    makeChart('vrmDailyTrendChart', 'line', data.daily?.labels || [], data.daily?.values || [], { label: 'Calls' });
    makeChart('vrmDispositionChart', 'doughnut', data.disposition?.labels || [], data.disposition?.values || []);
    makeChart('vrmInterestChart', 'bar', data.interest?.labels || [], data.interest?.values || [], { label: 'Interest' });
    makeChart('vrmLeadTrendChart', 'line', data.leads?.labels || [], data.leads?.values || [], { label: 'Leads' });
    makeChart('vrmFollowUpChart', 'bar', data.follow_up?.labels || [], data.follow_up?.values || [], { label: 'Follow-ups' });
})();
</script>
@endif
@endpush
