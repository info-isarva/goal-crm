<style>
.goal-targets-page {
    --gt-accent: var(--theme-primary, #059669);
    --gt-gap: 1.1rem;
}
.goal-targets-page .gt-subnav {
    display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 1rem;
}
.goal-targets-page .gt-subnav a {
    display: inline-flex; align-items: center; gap: 6px;
    height: 36px; padding: 0 12px; border-radius: 9px;
    font-size: .8rem; font-weight: 700; color: #334155; text-decoration: none;
    background: #fff; border: 1px solid #e2e8f0;
}
.goal-targets-page .gt-subnav a:hover { background: #f8fafc; color: #0f172a; }
.goal-targets-page .gt-subnav a.is-active {
    background: var(--gt-accent); color: #fff; border-color: var(--gt-accent);
    box-shadow: 0 4px 12px rgba(5,150,105,.22);
}
.goal-targets-page .gt-card {
    background: #fff; border: 1px solid #e8edf5; border-radius: 12px;
    box-shadow: 0 6px 16px rgba(148,163,184,.08); overflow: hidden; margin-bottom: 1rem;
}
.goal-targets-page .gt-card-head {
    display: flex; align-items: center; justify-content: space-between; gap: 10px;
    padding: .75rem 1rem; border-bottom: 1px solid #eef2f7;
}
.goal-targets-page .gt-card-head h3 { margin: 0; font-size: .95rem; font-weight: 800; color: #0f172a; }
.goal-targets-page .gt-card-body { padding: .85rem 1rem 1rem; }
.goal-targets-page .gt-kpi-row {
    display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .85rem; margin-bottom: 1rem;
}
@media (max-width: 991.98px) { .goal-targets-page .gt-kpi-row { grid-template-columns: 1fr 1fr; } }
@media (max-width: 575.98px) { .goal-targets-page .gt-kpi-row { grid-template-columns: 1fr; } }
.goal-targets-page .gt-kpi {
    background: #fff; border: 1px solid #e8edf5; border-radius: 12px; padding: 14px 16px;
    border-left: 4px solid var(--gt-accent);
}
.goal-targets-page .gt-kpi .lbl { font-size: .72rem; font-weight: 700; color: #64748b; }
.goal-targets-page .gt-kpi .val { margin-top: 4px; font-size: 1.25rem; font-weight: 800; color: #0f172a; }
.goal-targets-page .gt-table {
    width: 100%; font-size: .82rem; margin: 0;
    border-collapse: separate; border-spacing: 0;
}
.goal-targets-page .gt-table th,
.goal-targets-page .gt-table td {
    padding: 10px 14px; vertical-align: middle; color: #334155;
}
.goal-targets-page .gt-table td {
    border-bottom: 1px solid #f1f5f9;
}
.goal-targets-page .gt-table .num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; }
.goal-targets-page .gt-table .pct { color: #15803d; font-weight: 800; }
.goal-targets-page .gt-filters {
    display: flex; flex-wrap: wrap; gap: 12px; align-items: end; margin-bottom: 1rem;
    padding: .9rem 1rem; background: #fff; border: 1px solid #e8edf5; border-radius: 12px;
    position: relative; z-index: 5; overflow: visible;
}
.goal-targets-page .gt-filters .field {
    min-width: 160px; flex: 1 1 160px; display: flex; flex-direction: column; gap: 6px;
}
.goal-targets-page .gt-filters label {
    display: block; font-size: .72rem; font-weight: 700; color: #64748b; margin: 0;
    text-transform: uppercase; letter-spacing: .03em; line-height: 1.2;
}
.goal-targets-page .gt-filters .form-select {
    min-height: 40px; height: 40px; border-radius: 10px; border: 1px solid #d1d5db;
    font-size: .84rem; color: #0f172a; background-color: #fff;
    padding: .4rem 2.2rem .4rem .85rem;
}
.goal-targets-page .gt-filters .form-select:focus {
    border-color: #059669; box-shadow: 0 0 0 .2rem rgba(5, 150, 105, .15);
}
/* Select2 (auto-init on .form-select) */
.goal-targets-page .gt-filters .select2-container {
    width: 100% !important; max-width: 100%;
}
.goal-targets-page .gt-filters .select2-container--default .select2-selection--single {
    height: 40px !important; min-height: 40px !important;
    border: 1px solid #d1d5db !important; border-radius: 10px !important;
    background: #fff !important;
    display: flex !important; align-items: center !important;
}
.goal-targets-page .gt-filters .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 1.25 !important; padding-left: 12px !important; padding-right: 28px !important;
    font-size: .84rem !important; color: #0f172a !important; font-weight: 600;
}
.goal-targets-page .gt-filters .select2-container--default .select2-selection--single .select2-selection__placeholder {
    color: #64748b !important; font-weight: 500;
}
.goal-targets-page .gt-filters .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 100% !important; top: 0 !important; right: 6px !important;
    display: flex; align-items: center; justify-content: center;
}
.goal-targets-page .gt-filters .select2-container--default.select2-container--focus .select2-selection--single,
.goal-targets-page .gt-filters .select2-container--default.select2-container--open .select2-selection--single {
    border-color: #059669 !important;
    box-shadow: 0 0 0 .2rem rgba(5, 150, 105, .15);
}
.goal-targets-page .gt-filters .select2-container--default .select2-selection--single .select2-selection__clear {
    margin-right: 18px !important; height: auto !important; line-height: 1 !important;
}
body > .select2-container { z-index: 10050 !important; }
body > .select2-container .select2-dropdown {
    border: 1px solid #e2e8f0 !important; border-radius: 10px !important;
    box-shadow: 0 10px 28px rgba(15, 23, 42, .12); overflow: hidden;
    background: #fff;
}
body > .select2-container .select2-results__option {
    padding: 8px 12px !important; font-size: .84rem !important; color: #334155;
}
body > .select2-container .select2-results__option--highlighted[aria-selected],
body > .select2-container .select2-results__option--highlighted {
    background: #ecfdf5 !important; color: #047857 !important;
}
body > .select2-container .select2-results__option[aria-selected=true] {
    background: #f0fdf4 !important; color: #047857 !important; font-weight: 700;
}
body > .select2-container .select2-search--dropdown .select2-search__field {
    border: 1px solid #e2e8f0 !important; border-radius: 8px !important;
    padding: 6px 10px !important; font-size: .82rem !important;
}
.goal-targets-page .btn-gt-primary {
    height: 40px; border: 0; border-radius: 10px; padding: 0 16px; font-weight: 700; font-size: .82rem;
    color: #fff; background: var(--theme-primary, #059669); display: inline-flex; align-items: center; gap: 6px;
    white-space: nowrap; flex: 0 0 auto;
}
.goal-targets-page .btn-gt-ghost {
    height: 40px; border: 1px solid #dbe3f0; border-radius: 10px; padding: 0 14px; font-weight: 650; font-size: .82rem;
    color: #334155; background: #fff; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    white-space: nowrap; flex: 0 0 auto;
}
.goal-targets-page .badge-cf {
    display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 999px;
    font-size: .68rem; font-weight: 750;
}
.goal-targets-page .badge-cf.pending { background: #ffedd5; color: #c2410c; }
.goal-targets-page .badge-cf.applied { background: #dcfce7; color: #15803d; }
.goal-targets-page .badge-cf.rejected { background: #fee2e2; color: #b91c1c; }
.goal-targets-page .cf-actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }

/* —— Modern summary / shared register UI —— */
.goal-targets-page .gt-hero {
    display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between;
    gap: 12px 16px; margin-bottom: 1rem;
}
.goal-targets-page .gt-hero-title {
    display: flex; align-items: flex-start; gap: 12px;
}
.goal-targets-page .gt-hero-ico {
    width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
    display: inline-flex; align-items: center; justify-content: center;
    background: linear-gradient(145deg, #d1fae5, #ecfdf5); color: #059669;
    font-size: 1.2rem; border: 1px solid #bbf7d0;
}
.goal-targets-page .gt-hero h1 {
    margin: 0; font-size: 1.35rem; font-weight: 800; color: #0f172a; letter-spacing: -.02em;
}
.goal-targets-page .gt-hero p {
    margin: 3px 0 0; font-size: .84rem; color: #64748b; max-width: 520px;
}
.goal-targets-page .gt-kpi {
    display: flex; align-items: flex-start; gap: 12px;
    background: #fff; border: 1px solid #e8edf5; border-radius: 14px;
    padding: 14px 16px; border-left: 0;
    box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    transition: box-shadow .15s ease, border-color .15s ease;
}
.goal-targets-page .gt-kpi:hover {
    border-color: #d1fae5;
    box-shadow: 0 8px 20px rgba(5, 150, 105, .08);
}
.goal-targets-page .gt-kpi-ico {
    width: 40px; height: 40px; border-radius: 11px; flex-shrink: 0;
    display: inline-flex; align-items: center; justify-content: center; font-size: 1.05rem;
}
.goal-targets-page .gt-kpi-ico.is-eff { background: #ecfdf5; color: #059669; }
.goal-targets-page .gt-kpi-ico.is-ach { background: #eff6ff; color: #2563eb; }
.goal-targets-page .gt-kpi-ico.is-pct { background: #fef3c7; color: #d97706; }
.goal-targets-page .gt-kpi-ico.is-rem { background: #f0fdf4; color: #15803d; }
.goal-targets-page .gt-kpi .lbl {
    font-size: .68rem; font-weight: 750; color: #64748b;
    text-transform: uppercase; letter-spacing: .04em;
}
.goal-targets-page .gt-kpi .val {
    margin-top: 4px; font-size: 1.2rem; font-weight: 800; color: #0f172a;
    font-variant-numeric: tabular-nums; line-height: 1.2;
}
.goal-targets-page .gt-kpi .meta {
    margin-top: 3px; font-size: .7rem; color: #94a3b8; font-weight: 600;
}
.goal-targets-page .gt-card-head {
    padding: 12px 16px; background: #fcfcfd;
}
.goal-targets-page .gt-card-head h3 {
    display: inline-flex; align-items: center; gap: 8px; font-size: .92rem;
}
.goal-targets-page .gt-card-head h3 i { color: #059669; }
.goal-targets-page .gt-count-pill {
    display: inline-flex; align-items: center; height: 22px; padding: 0 8px;
    border-radius: 999px; background: #ecfdf5; color: #047857;
    font-size: .68rem; font-weight: 800;
}
.goal-targets-page .gt-table thead th {
    position: sticky; top: 0; z-index: 2;
    background: #fff;
    color: #64748b;
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: 0;
    text-transform: none;
    white-space: nowrap;
    text-align: left;
    padding: 12px 14px;
    border-bottom: 1px solid #e8edf5;
    box-shadow: inset 0 -1px 0 #e8edf5, 0 1px 0 rgba(15, 23, 42, .02);
}
.goal-targets-page .gt-table thead th.num {
    text-align: right;
}
.goal-targets-page .gt-table thead th:first-child {
    padding-left: 16px;
}
.goal-targets-page .gt-table thead th:last-child {
    padding-right: 16px;
}
.goal-targets-page .gt-table tbody tr { transition: background .12s ease; }
.goal-targets-page .gt-table tbody tr:hover { background: #f8fafc; }
.goal-targets-page .gt-table tbody td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; }
.goal-targets-page .gt-table tbody td:first-child,
.goal-targets-page .gt-table tfoot td:first-child { padding-left: 16px; }
.goal-targets-page .gt-table tbody td:last-child,
.goal-targets-page .gt-table tfoot td:last-child { padding-right: 16px; }
.goal-targets-page .gt-table tfoot td {
    background: #f0fdf4; border-top: 1px solid #bbf7d0; font-weight: 800; color: #14532d;
    padding: 12px 14px;
}
.goal-targets-page .gt-entity {
    display: flex; align-items: center; gap: 10px; min-width: 0;
}
.goal-targets-page .gt-entity-ico {
    width: 32px; height: 32px; border-radius: 9px; flex-shrink: 0;
    display: inline-flex; align-items: center; justify-content: center; font-size: .85rem;
}
.goal-targets-page .gt-entity-ico.is-emp { background: #eff6ff; color: #2563eb; }
.goal-targets-page .gt-entity-ico.is-org { background: #ecfdf5; color: #059669; }
.goal-targets-page .gt-entity-name {
    font-weight: 750; color: #0f172a; line-height: 1.25;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px;
}
.goal-targets-page .gt-entity-sub {
    font-size: .68rem; color: #94a3b8; font-weight: 650; margin-top: 1px;
}
.goal-targets-page .gt-metric-pill,
.goal-targets-page .gt-status-pill {
    display: inline-flex; align-items: center; gap: 4px;
    height: 22px; padding: 0 8px; border-radius: 999px;
    font-size: .65rem; font-weight: 750; white-space: nowrap;
}
.goal-targets-page .gt-metric-pill.is-revenue { background: #ecfdf5; color: #047857; }
.goal-targets-page .gt-metric-pill.is-disbursement { background: #eff6ff; color: #1d4ed8; }
.goal-targets-page .gt-metric-pill.is-other { background: #f1f5f9; color: #475569; }
.goal-targets-page .gt-status-pill.is-approved { background: #dcfce7; color: #15803d; }
.goal-targets-page .gt-status-pill.is-draft { background: #f1f5f9; color: #475569; }
.goal-targets-page .gt-status-pill.is-submitted { background: #dbeafe; color: #1d4ed8; }
.goal-targets-page .gt-status-pill.is-locked { background: #ffedd5; color: #c2410c; }
.goal-targets-page .gt-pct-cell {
    display: inline-flex; flex-direction: column; align-items: flex-end; gap: 3px; min-width: 64px;
}
.goal-targets-page .gt-pct-bar {
    width: 64px; height: 4px; border-radius: 999px; background: #e2e8f0; overflow: hidden;
}
.goal-targets-page .gt-pct-bar > span {
    display: block; height: 100%; border-radius: 999px; background: #059669;
}
.goal-targets-page .gt-table-foot {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
    gap: 10px 14px; padding: 12px 16px; border-top: 1px solid #eef2f7; background: #fff;
}
.goal-targets-page .gt-showing {
    font-size: .8rem; color: #64748b; font-weight: 600;
}
.goal-targets-page .gt-pager {
    display: flex; flex-wrap: wrap; align-items: center; gap: 8px;
}
.goal-targets-page .gt-pager .pagination {
    margin: 0; gap: 4px;
}
.goal-targets-page .gt-pager .page-link {
    border-radius: 8px !important; border: 1px solid #e2e8f0;
    color: #334155; font-size: .78rem; font-weight: 650;
    min-width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center;
    padding: 0 10px;
}
.goal-targets-page .gt-pager .page-item.active .page-link {
    background: #059669; border-color: #059669; color: #fff;
}
.goal-targets-page .gt-pager .page-item.disabled .page-link {
    color: #94a3b8; background: #f8fafc;
}
.goal-targets-page .gt-rows-select {
    height: 34px; border: 1px solid #e2e8f0; border-radius: 8px;
    font-size: .78rem; font-weight: 650; color: #334155; padding: 0 8px; background: #fff;
}
.goal-targets-page .gt-empty {
    text-align: center; padding: 40px 16px; color: #94a3b8;
}
.goal-targets-page .gt-empty i {
    display: block; font-size: 1.8rem; margin-bottom: 8px; color: #cbd5e1;
}
.goal-targets-page .gt-period-chip {
    display: inline-flex; align-items: center; gap: 5px;
    font-weight: 700; color: #334155; white-space: nowrap;
}
.goal-targets-page .gt-period-chip i { color: #94a3b8; font-size: .75rem; }
.goal-targets-page .gt-level-pill {
    display: inline-flex; align-items: center; height: 22px; padding: 0 8px;
    border-radius: 999px; background: #f1f5f9; color: #475569;
    font-size: .65rem; font-weight: 750; white-space: nowrap;
}
.goal-targets-page .gt-branch-chip {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: .78rem; font-weight: 650; color: #334155; white-space: nowrap;
    max-width: 160px; overflow: hidden; text-overflow: ellipsis;
}
.goal-targets-page .gt-branch-chip i { color: #94a3b8; font-size: .72rem; flex-shrink: 0; }
</style>
