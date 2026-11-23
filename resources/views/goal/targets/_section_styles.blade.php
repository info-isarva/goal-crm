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
.goal-targets-page .gt-table { width: 100%; font-size: .82rem; margin: 0; }
.goal-targets-page .gt-table th {
    background: #f8f9fc; font-size: .7rem; font-weight: 750; color: #1e293b;
    padding: 8px 10px; white-space: nowrap; border-bottom: 1px solid #eef0f6;
}
.goal-targets-page .gt-table td {
    padding: 8px 10px; border-bottom: 1px solid #eef0f6; vertical-align: middle; color: #334155;
}
.goal-targets-page .gt-table .num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; }
.goal-targets-page .gt-table .pct { color: #15803d; font-weight: 800; }
.goal-targets-page .gt-filters {
    display: flex; flex-wrap: wrap; gap: 10px; align-items: end; margin-bottom: 1rem;
    padding: .85rem 1rem; background: #fff; border: 1px solid #e8edf5; border-radius: 12px;
}
.goal-targets-page .gt-filters .field { min-width: 140px; flex: 1; }
.goal-targets-page .gt-filters label {
    display: block; font-size: .7rem; font-weight: 700; color: #64748b; margin-bottom: 4px;
    text-transform: uppercase; letter-spacing: .03em;
}
.goal-targets-page .gt-filters .form-select {
    height: 38px; border-radius: 8px; border: 1px solid #dbe2ea; font-size: .84rem;
}
.goal-targets-page .btn-gt-primary {
    height: 38px; border: 0; border-radius: 9px; padding: 0 14px; font-weight: 700; font-size: .82rem;
    color: #fff; background: var(--theme-primary, #059669); display: inline-flex; align-items: center; gap: 6px;
}
.goal-targets-page .btn-gt-ghost {
    height: 38px; border: 1px solid #dbe3f0; border-radius: 9px; padding: 0 12px; font-weight: 650; font-size: .82rem;
    color: #334155; background: #fff; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
}
.goal-targets-page .badge-cf {
    display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 999px;
    font-size: .68rem; font-weight: 750;
}
.goal-targets-page .badge-cf.pending { background: #ffedd5; color: #c2410c; }
.goal-targets-page .badge-cf.applied { background: #dcfce7; color: #15803d; }
.goal-targets-page .badge-cf.rejected { background: #fee2e2; color: #b91c1c; }
.goal-targets-page .cf-actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
</style>
