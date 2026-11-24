@extends('layouts.app')

@php
    $currency = config('app.currency_symbol', '₹');
    $fmt = fn ($n) => $currency . ' ' . number_format((float) $n, 2, '.', ',');
    $openAddModal = $errors->any() && old('_form') === 'create';
    $openEditModal = $errors->any() && old('_form') === 'edit';

    $regions = $regions ?? collect();
    $clusters = $clusters ?? collect();
    $branchUnits = $branchUnits ?? collect();
    $regionChart = $regionChart ?? ['labels' => [], 'values' => [], 'effective' => [], 'org_ids' => [], 'total_actual' => 0];
    $stats = $stats ?? [];
    $insights = $insights ?? ['employees' => 0, 'regions' => 0, 'clusters' => 0, 'branches' => 0];
    $recentActivity = $recentActivity ?? [];
    $allocationTree = $allocationTree ?? [];

    $filterRegionId = '';
    $filterClusterId = '';
    $filterBranchId = '';
    $selOrgId = $filters['goal_org_unit_id'] ?? null;
    if ($selOrgId && isset($branches)) {
        $unit = $branches->firstWhere('id', (int) $selOrgId);
        if ($unit) {
            if ($unit->type === \App\Models\GoalOrgUnit::TYPE_BRANCH) {
                $filterBranchId = (string) $unit->id;
                $parent = $branches->firstWhere('id', (int) $unit->parent_id);
                if ($parent) {
                    if ($parent->type === \App\Models\GoalOrgUnit::TYPE_CLUSTER) {
                        $filterClusterId = (string) $parent->id;
                        $filterRegionId = $parent->parent_id ? (string) $parent->parent_id : '';
                    } elseif ($parent->type === \App\Models\GoalOrgUnit::TYPE_REGION) {
                        $filterRegionId = (string) $parent->id;
                    }
                }
            } elseif ($unit->type === \App\Models\GoalOrgUnit::TYPE_CLUSTER) {
                $filterClusterId = (string) $unit->id;
                $filterRegionId = $unit->parent_id ? (string) $unit->parent_id : '';
            } elseif ($unit->type === \App\Models\GoalOrgUnit::TYPE_REGION) {
                $filterRegionId = (string) $unit->id;
            }
        }
    }

    $tableMetric = in_array($filters['metric'] ?? '', ['revenue', 'disbursement'], true)
        ? $filters['metric']
        : 'revenue';
    $tabQuery = fn (string $metric) => array_merge(request()->except(['page']), ['metric' => $metric]);
@endphp

@push('styles')
@include('goal.partials.pd_hub_styles')
<style>
.goal-targets-page {
    --gt-gap: 1.25rem;
    --gt-control-h: 38px;
    --gt-accent: var(--theme-primary, #059669);
    color: #1e293b;
    background: #f4f6f9;
    min-height: 100%;
    padding-bottom: 1.5rem;
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
.goal-targets-page .btn-gt-ghost.is-active {
    border-color: var(--theme-primary); background: var(--theme-100, #ecfdf5); color: var(--theme-primary);
}
.goal-targets-page .gt-subnav {
    display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: var(--gt-gap);
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

.goal-targets-page .kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1.1rem;
    margin-bottom: var(--gt-gap);
    align-items: stretch;
}
@media (max-width: 1199.98px) { .goal-targets-page .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 575.98px) { .goal-targets-page .kpi-grid { grid-template-columns: 1fr; } }

.goal-targets-page .kpi-card {
    display: flex; align-items: center; gap: 14px;
    background: #fff; border: 1px solid #e8edf5; border-radius: 14px;
    padding: 1.05rem 1.15rem; min-height: 108px;
    box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 8px 18px rgba(148,163,184,.1);
    border-left: 4px solid #86efac;
}
.goal-targets-page .kpi-card.tone-green { border-left-color: #16a34a; background: #fff; }
.goal-targets-page .kpi-card.tone-sky { border-left-color: #16a34a; background: #fff; }
.goal-targets-page .kpi-card.tone-violet { border-left-color: #3b82f6; background: #fff; }
.goal-targets-page .kpi-card.tone-amber { border-left-color: #f59e0b; background: #fff; }
.goal-targets-page button.kpi-card {
    appearance: none; -webkit-appearance: none; border-top: 0; border-right: 0; border-bottom: 0;
    text-align: left; width: 100%; font: inherit; color: inherit;
}
.goal-targets-page .kpi-ico {
    width: 48px; height: 48px; border-radius: 12px; flex-shrink: 0;
    display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem;
}
.goal-targets-page .kpi-card.tone-green .kpi-ico { background: #dcfce7; color: #15803d; }
.goal-targets-page .kpi-card.tone-sky .kpi-ico { background: #dcfce7; color: #15803d; }
.goal-targets-page .kpi-card.tone-violet .kpi-ico { background: #dbeafe; color: #2563eb; }
.goal-targets-page .kpi-card.tone-amber .kpi-ico { background: #ffedd5; color: #ea580c; }
.goal-targets-page .kpi-body { min-width: 0; flex: 1; }
.goal-targets-page .kpi-label {
    font-size: .78rem; font-weight: 700; color: #64748b; margin: 0; line-height: 1.25;
}
.goal-targets-page .kpi-value {
    margin: 6px 0 0; font-size: 1.45rem; font-weight: 800; color: #0f172a;
    line-height: 1.15; letter-spacing: -.025em; font-variant-numeric: tabular-nums;
}
.goal-targets-page .kpi-meta { margin: 6px 0 0; font-size: .74rem; color: #94a3b8; line-height: 1.35; }
.goal-targets-page .kpi-card.is-clickable { cursor: pointer; transition: box-shadow .15s, transform .15s; }
.goal-targets-page .kpi-card.is-clickable:hover {
    box-shadow: 0 10px 22px rgba(148,163,184,.16); transform: translateY(-1px);
}
.goal-targets-page .kpi-card .kpi-hint {
    margin-top: 6px; font-size: .68rem; font-weight: 650; color: var(--gt-accent);
    display: inline-flex; align-items: center; gap: 4px;
}

.goal-targets-page .charts-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(0, 1.05fr) minmax(220px, .72fr);
    gap: .85rem;
    margin-bottom: var(--gt-gap);
    align-items: stretch;
}
@media (max-width: 1199.98px) { .goal-targets-page .charts-grid { grid-template-columns: 1fr 1fr; gap: .85rem; } }
@media (max-width: 991.98px) { .goal-targets-page .charts-grid { grid-template-columns: 1fr; } }
.goal-targets-page .charts-grid .panel-actions { min-height: 100%; }
@media (max-width: 1199.98px) and (min-width: 992px) {
    .goal-targets-page .charts-grid .panel-actions { grid-column: 1 / -1; }
}
.goal-targets-page .charts-grid .panel-head {
    padding: .7rem .9rem; align-items: center;
}
.goal-targets-page .charts-grid .panel-head h5 {
    font-size: .88rem; font-weight: 800; display: inline-flex; align-items: center; gap: 7px;
}
.goal-targets-page .charts-grid .panel-head h5 i { color: var(--gt-accent); font-size: .95rem; }
.goal-targets-page .charts-grid .panel-sub { display: none; }
.goal-targets-page .charts-grid .panel-body {
    padding: .55rem .85rem .85rem; flex: 1; min-height: 0;
}
.goal-targets-page .donut-layout {
    display: flex; align-items: center; gap: 12px; height: 100%; min-height: 0;
}
.goal-targets-page .donut-wrap {
    position: relative; flex: 0 0 46%; max-width: 180px; height: 180px; min-height: 180px;
}
.goal-targets-page .donut-center {
    position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%);
    text-align: center; pointer-events: none; z-index: 3; max-width: 58%;
}
.goal-targets-page .donut-center .dc-value {
    font-size: .95rem; font-weight: 800; color: #0f172a; font-variant-numeric: tabular-nums; line-height: 1.15;
}
.goal-targets-page .donut-center .dc-label {
    font-size: .65rem; font-weight: 650; color: #94a3b8; margin-top: 2px;
}
.goal-targets-page .donut-legend {
    list-style: none; margin: 0; padding: 0; flex: 1; min-width: 0;
    display: flex; flex-direction: column; gap: 8px;
}
.goal-targets-page .donut-legend li {
    display: grid; grid-template-columns: 8px minmax(0, 1fr); gap: 8px; align-items: start;
}
.goal-targets-page .donut-legend .dl-dot {
    width: 8px; height: 8px; border-radius: 50%; margin-top: 4px; flex-shrink: 0;
}
.goal-targets-page .donut-legend .dl-name {
    font-size: .72rem; font-weight: 700; color: #0f172a; line-height: 1.25;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.goal-targets-page .donut-legend .dl-meta {
    font-size: .68rem; color: #64748b; font-weight: 600; margin-top: 1px;
    font-variant-numeric: tabular-nums;
}
.goal-targets-page .qa-box {
    background: #f0fdf4; border: 1px solid #dcfce7; border-radius: 12px;
    padding: 6px; display: flex; flex-direction: column; gap: 4px; height: 100%;
}
.goal-targets-page .qa-item {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px; border-radius: 10px; text-decoration: none;
    color: #0f172a; background: transparent; border: 0; width: 100%;
    font-size: .8rem; font-weight: 700; cursor: pointer; transition: background .15s;
}
.goal-targets-page .qa-item:hover { background: #fff; color: #059669; }
.goal-targets-page .qa-item .qa-ico {
    width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0;
    background: #dcfce7; color: #059669;
    display: inline-flex; align-items: center; justify-content: center; font-size: .85rem;
}
.goal-targets-page .qa-item span { flex: 1; text-align: left; }
.goal-targets-page .qa-item .qa-chev { color: #94a3b8; font-size: .75rem; }
.goal-targets-page .dashboard-lower {
    display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(280px, 1fr); gap: 1.1rem; align-items: start;
}
@media (max-width: 991.98px) { .goal-targets-page .dashboard-lower { grid-template-columns: 1fr; } }
.goal-targets-page .activity-panel .panel-body { max-height: 520px; overflow-y: auto; }
.goal-targets-page .activity-timeline { list-style: none; margin: 0; padding: 0; }
.goal-targets-page .activity-timeline li {
    position: relative; padding: 0 0 16px 22px; border-left: 2px solid #e2e8f0; margin-left: 6px;
}
.goal-targets-page .activity-timeline li:last-child { padding-bottom: 0; border-left-color: transparent; }
.goal-targets-page .activity-timeline li::before {
    content: ''; position: absolute; left: -5px; top: 4px; width: 8px; height: 8px;
    border-radius: 50%; background: var(--gt-accent); box-shadow: 0 0 0 3px #ecfdf5;
}
.goal-targets-page .activity-timeline .at-title { font-size: .84rem; font-weight: 650; color: #0f172a; line-height: 1.35; margin: 0; }
.goal-targets-page .activity-timeline .at-meta { font-size: .74rem; color: #94a3b8; margin-top: 3px; }
.goal-targets-page .btn-gt-ghost.is-active {
    border-color: var(--gt-accent); color: var(--gt-accent); background: #ecfdf5;
}
.goal-targets-page .metric-tabs {
    display: inline-flex; gap: 6px; padding: 0; background: transparent; border-radius: 0;
}
.goal-targets-page .metric-tabs a,
.goal-targets-page .metric-tabs .metric-tab-btn {
    display: inline-flex; align-items: center; gap: 6px; padding: 7px 12px; border-radius: 8px;
    font-size: .78rem; font-weight: 700; color: #0f172a; text-decoration: none; line-height: 1.2;
    background: #fff; border: 1px solid #e2e8f0; cursor: pointer;
}
.goal-targets-page .metric-tabs a:hover,
.goal-targets-page .metric-tabs .metric-tab-btn:hover { background: #f8fafc; color: #0f172a; }
.goal-targets-page .metric-tabs a.is-active,
.goal-targets-page .metric-tabs .metric-tab-btn.is-active {
    background: var(--gt-accent); color: #fff; border-color: var(--gt-accent);
    box-shadow: 0 3px 10px rgba(5, 150, 105, .22);
}
.goal-targets-page .panel {
    background: #fff; border: 1px solid #e8edf5; border-radius: 14px;
    box-shadow: 0 6px 16px rgba(148,163,184,.08); overflow: hidden;
    display: flex; flex-direction: column; min-width: 0;
}
.goal-targets-page .panel-head {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;
    padding: 1rem 1.2rem; border-bottom: 1px solid #eef2f7; flex-shrink: 0;
}
.goal-targets-page .panel-head h5 { margin: 0; font-size: 1.05rem; font-weight: 800; color: #0f172a; line-height: 1.25; }
.goal-targets-page .panel-sub { font-size: .78rem; color: #94a3b8; margin-top: 3px; line-height: 1.3; }
.goal-targets-page .panel-body { padding: 1rem 1.15rem 1.15rem; flex: 1; display: flex; flex-direction: column; min-height: 0; }
.goal-targets-page .chart-wrap { position: relative; height: 200px; width: 100%; flex: 1; min-height: 180px; }
.goal-targets-page .chart-wrap.sm { height: 180px; min-height: 160px; }
.goal-targets-page .chart-empty{
    position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;
    color:#94a3b8;font-size:.86rem;pointer-events:none;z-index:2;text-align:center;padding:12px;
}
.goal-targets-page .chart-empty i{font-size:1.5rem;margin-bottom:6px;opacity:.7;display:block}

/* Register table — compact density */
.goal-targets-page .gt-table-card {
    background: #fff; border: 1px solid #e8edf5; border-radius: 12px;
    box-shadow: 0 6px 16px rgba(148,163,184,.08); overflow: visible; margin-bottom: var(--gt-gap);
}
.goal-targets-page .gt-table-head {
    display: flex; align-items: center; justify-content: space-between; gap: 10px;
    padding: .65rem .9rem; border-bottom: 1px solid #eef2f7;
}
.goal-targets-page .gt-table-title {
    margin: 0; font-size: .95rem; font-weight: 800; color: #0f172a;
    display: inline-flex; align-items: center; gap: 8px; line-height: 1.25;
}
.goal-targets-page .gt-table-title i { color: var(--theme-primary); font-size: 1rem; }
.goal-targets-page .gt-table-meta { font-size: .72rem; color: #94a3b8; font-weight: 600; line-height: 1.3; }
.goal-targets-page .gt-table-wrap {
    overflow-x: auto; overflow-y: visible; -webkit-overflow-scrolling: touch;
    padding: 0; width: 100%; max-width: 100%;
}
.goal-targets-page .gt-table {
    font-size: .8rem; width: 100%; min-width: 0; margin: 0;
    table-layout: fixed; border-collapse: separate; border-spacing: 0;
}
.goal-targets-page .gt-table thead th {
    background: #f8f9fc; color: #1e293b; font-size: .68rem; font-weight: 750;
    text-transform: none; letter-spacing: .01em; border-bottom: 1px solid #eef0f6; border-top: 0;
    white-space: nowrap; padding: 8px 10px; vertical-align: middle; text-align: left;
}
.goal-targets-page .gt-table thead th.col-amt,
.goal-targets-page .gt-table thead th.col-actions {
    text-align: right;
}
.goal-targets-page .gt-table thead th.col-num { text-align: left; width: 40px; }
.goal-targets-page .gt-table thead th.col-entity { width: 22%; }
.goal-targets-page .gt-table thead th.col-pct { width: 9%; }
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
    padding: 7px 10px; border-bottom: 1px solid #eef0f6; border-top: 0;
    vertical-align: middle; color: #334155; background: #fff;
}
.goal-targets-page .gt-table tbody tr:last-child td { border-bottom: 0; }
.goal-targets-page .gt-table tbody tr:hover td { background: #f8fafc; }
.goal-targets-page .gt-table .col-num { width: 40px; color: #64748b; font-weight: 650; font-variant-numeric: tabular-nums; font-size: .75rem; }
.goal-targets-page .gt-table .col-amt {
    text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap;
    font-weight: 600; color: #1a1c32; font-size: .78rem;
}
.goal-targets-page .gt-table .col-amt.is-eff { font-weight: 800; color: #0f172a; }
.goal-targets-page .gt-table .col-amt.is-sf { color: #c2410c; }
.goal-targets-page .gt-table .col-actions {
    width: 118px; text-align: right;
    border-left: 1px solid #f1f5f9;
}
.goal-targets-page .gt-owner-name {
    font-weight: 700; color: #1a1c32; line-height: 1.2; font-size: .8rem;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;
}
.goal-targets-page .gt-owner-sub {
    font-size: .66rem; color: #94a3b8; margin-top: 1px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.goal-targets-page .gt-progress-wrap {
    display: flex; flex-direction: column; align-items: flex-start; gap: 4px; min-width: 0; width: 100%;
}
.goal-targets-page .gt-progress {
    width: 100%; height: 5px; border-radius: 999px; background: #e2e8f0; overflow: hidden; min-width: 0;
}
.goal-targets-page .gt-progress > span {
    display: block; height: 100%; border-radius: inherit;
    background: #16a34a;
}
.goal-targets-page .gt-progress-pct {
    font-size: .75rem; font-weight: 800; color: #15803d; line-height: 1;
    order: -1;
}
.goal-targets-page .gt-tree-toggle {
    width: 18px; height: 18px; border: 0; background: transparent; color: #94a3b8;
    display: inline-flex; align-items: center; justify-content: center; border-radius: 4px;
    padding: 0; flex-shrink: 0; cursor: pointer; font-size: .7rem;
}
.goal-targets-page .gt-tree-toggle:hover { background: #e2e8f0; color: #0f172a; }
.goal-targets-page .gt-tree-toggle.is-leaf { visibility: hidden; }
.goal-targets-page .gt-entity {
    display: inline-flex; align-items: center; gap: 6px; min-width: 0; max-width: 100%;
}
.goal-targets-page .gt-entity > div { min-width: 0; overflow: hidden; }
.goal-targets-page .gt-ent-ico {
    width: 22px; height: 22px; border-radius: 6px; flex-shrink: 0;
    display: inline-flex; align-items: center; justify-content: center; font-size: .7rem;
}
.goal-targets-page .gt-ent-ico.region { background: #dcfce7; color: #059669; }
.goal-targets-page .gt-ent-ico.cluster { background: #dbeafe; color: #2563eb; }
.goal-targets-page .gt-ent-ico.branch { background: #ede9fe; color: #7c3aed; }
.goal-targets-page .gt-ent-ico.employee { background: #f1f5f9; color: #475569; }
.goal-targets-page .gt-type-pill {
    display: inline-flex; align-items: center; height: 22px; padding: 0 9px;
    border-radius: 999px; font-size: .68rem; font-weight: 750; white-space: nowrap;
}
.goal-targets-page .gt-type-pill.region { background: #dcfce7; color: #059669; }
.goal-targets-page .gt-type-pill.cluster { background: #dbeafe; color: #2563eb; }
.goal-targets-page .gt-type-pill.branch { background: #ede9fe; color: #7c3aed; }
.goal-targets-page .gt-type-pill.employee { background: #f1f5f9; color: #475569; }
.goal-targets-page .gt-table tfoot td {
    background: #e8f7ed; font-weight: 800; color: #14532d;
    border-top: 2px solid #86efac; padding: 8px 10px; font-size: .78rem;
}
.goal-targets-page .gt-table tfoot .gt-progress-pct { color: #15803d; }
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
    width: 28px; height: 28px; border: 0; border-radius: 7px;
    background: #ecfdf5; color: #059669;
    display: inline-flex; align-items: center; justify-content: center;
}
.goal-targets-page .gt-action-btn:hover { background: #dcfce7; color: #047857; }
.goal-targets-page .gt-row-actions {
    display: inline-flex; align-items: center; gap: 4px; justify-content: flex-end;
}
.goal-targets-page .gt-btn-edit {
    height: 26px; padding: 0 9px; border-radius: 999px;
    border: 1px solid #86efac; background: #fff; color: #059669;
    font-size: .68rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;
    cursor: pointer; white-space: nowrap; text-decoration: none;
}
.goal-targets-page .gt-btn-edit:hover { background: #ecfdf5; color: #047857; }
.goal-targets-page .gt-more-dd { position: relative; display: inline-flex; }
.goal-targets-page .gt-btn-more {
    width: 26px; height: 26px; border-radius: 7px; border: 1px solid #e2e8f0;
    background: #fff; color: #64748b; display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; padding: 0; font-size: .8rem;
}
.goal-targets-page .gt-btn-more:hover { background: #f8fafc; color: #0f172a; }
.goal-targets-page .gt-more-menu {
    position: fixed; z-index: 2000;
    min-width: 180px; margin: 0; padding: 4px; list-style: none;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
    box-shadow: 0 12px 28px rgba(15,23,42,.18); display: none;
}
.goal-targets-page .gt-more-dd.is-open .gt-more-menu { display: block; }
/* Portaled to body — escape table / foot stacking */
.gt-more-menu.is-portal {
    display: block !important;
    position: fixed !important;
    z-index: 2000 !important;
}
.goal-targets-page .gt-more-menu button,
.goal-targets-page .gt-more-menu a {
    display: flex; align-items: center; gap: 7px; width: 100%;
    border: 0; background: transparent; text-align: left;
    padding: 7px 9px; border-radius: 6px; font-size: .74rem; font-weight: 650;
    color: #334155; cursor: pointer; text-decoration: none;
}
.goal-targets-page .gt-more-menu button:hover,
.goal-targets-page .gt-more-menu a:hover { background: #ecfdf5; color: #059669; }
.goal-targets-page .gt-more-menu button.danger,
.goal-targets-page .gt-more-menu a.danger { color: #b91c1c; }
.goal-targets-page .gt-more-menu button.danger:hover,
.goal-targets-page .gt-more-menu a.danger:hover { background: #fef2f2; color: #991b1b; }
.goal-targets-page .gt-more-menu button:disabled { opacity: .45; cursor: not-allowed; }
.goal-targets-page .gt-table-foot {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
    gap: 10px 14px; padding: 10px 14px; border-top: 1px solid #e8edf5; background: #fff;
    border-radius: 0 0 12px 12px;
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
        display: table !important; width: 100% !important; min-width: 0 !important;
        table-layout: fixed !important;
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
        padding: 7px 10px !important;
        border-bottom: 1px solid #eef0f6 !important;
        justify-content: initial !important;
        align-items: initial !important;
        gap: 0 !important;
    }
    .goal-targets-page .gt-table-wrap.table-responsive > table.gt-table thead th {
        background: #f8f9fc !important;
        padding: 8px 10px !important;
    }
    .goal-targets-page .gt-table-wrap.table-responsive > table.gt-table tbody td { padding: 7px 10px !important; }
    .goal-targets-page .gt-table-wrap.table-responsive > table.gt-table tfoot td { padding: 8px 10px !important; }
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
    grid-template-columns: repeat(7, minmax(0, 1fr)) max-content;
    gap: 14px;
    align-items: end;
    margin-bottom: var(--gt-gap);
    padding: 1.05rem 1.2rem;
    background: #fff; border: 1px solid #e8edf5; border-radius: 14px;
    box-shadow: 0 6px 16px rgba(148,163,184,.08);
    overflow: visible;
}
@media (max-width: 1399.98px) {
    .goal-targets-page .filters-bar {
        grid-template-columns: repeat(4, minmax(0, 1fr));
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
            <h1 class="pd-title">Targets Dashboard</h1>
            <p class="pd-sub">Overview of revenue and disbursement targets, achievement and shortfall.</p>
        </div>
        <div class="pd-hero-actions d-flex flex-wrap gap-2">
            @if($canManage)
                <a href="{{ route('goal.targets.set', request()->only('period', 'metric')) }}" class="btn-gt-primary">
                    <i class="bi bi-plus-lg"></i> Set Targets
                </a>
            @endif
            <a href="{{ route('goal.targets.summary', request()->only('period', 'metric')) }}" class="btn-gt-ghost">
                <i class="bi bi-clipboard-data"></i> Summary
            </a>
            <a href="{{ route('goal.targets.achievement', request()->only('period', 'metric')) }}" class="btn-gt-ghost">
                <i class="bi bi-graph-up-arrow"></i> Achievement
            </a>
            <a href="{{ route('goal.targets.carry-forward', request()->only('period', 'metric')) }}" class="btn-gt-ghost">
                <i class="bi bi-arrow-repeat"></i> Carry Forward
            </a>
            <a href="{{ route('goal.targets.export', request()->query()) }}" class="btn-gt-ghost">
                <i class="bi bi-download"></i> Export
            </a>
        </div>
    </div>

    @include('goal.targets._subnav', ['active' => 'dashboard', 'canManage' => $canManage])

    <form method="GET" action="{{ route('goal.targets.index') }}" class="filters-bar" id="gtFiltersForm">
        <input type="hidden" name="per_page" value="{{ $filters['per_page'] ?? 10 }}">
        <input type="hidden" name="goal_org_unit_id" id="filter_goal_org_unit_id" value="{{ $filters['goal_org_unit_id'] ?? '' }}">
        @if(($filters['status'] ?? '') !== '')
            <input type="hidden" name="status" value="{{ $filters['status'] }}">
        @endif
        @if(!empty($filters['user_id']))
            <input type="hidden" name="user_id" value="{{ $filters['user_id'] }}">
        @endif
        <div class="gt-filter-field">
            <label class="form-label" for="filter_metric">Target Type</label>
            <select name="metric" id="filter_metric" class="form-select" data-search="false" data-allow-clear="false" data-placeholder="All types">
                <option value="">All types</option>
                <option value="revenue" @selected($filters['metric'] === 'revenue')>Revenue</option>
                <option value="disbursement" @selected($filters['metric'] === 'disbursement')>Disbursement</option>
            </select>
        </div>
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
            <label class="form-label" for="filter_owner_type">View By</label>
            <select name="owner_type" id="filter_owner_type" class="form-select" data-search="false" data-allow-clear="false" data-placeholder="Overall">
                <option value="">Overall</option>
                <option value="region" @selected(($filters['owner_type'] ?? '') === 'region')>Region</option>
                <option value="cluster" @selected(($filters['owner_type'] ?? '') === 'cluster')>Cluster</option>
                <option value="branch" @selected(($filters['owner_type'] ?? '') === 'branch')>Branch</option>
                <option value="employee" @selected(($filters['owner_type'] ?? '') === 'employee')>Employee</option>
                <option value="bm" @selected(($filters['owner_type'] ?? '') === 'bm')>BM</option>
                <option value="cd" @selected(($filters['owner_type'] ?? '') === 'cd')>Cluster Director</option>
                <option value="rd" @selected(($filters['owner_type'] ?? '') === 'rd')>Regional Director</option>
            </select>
        </div>
        <div class="gt-filter-field">
            <label class="form-label" for="filter_region">Region</label>
            <select id="filter_region" class="form-select gt-org-filter" data-search="true" data-allow-clear="false" data-placeholder="All regions">
                <option value="">All regions</option>
                @foreach($regions as $r)
                    <option value="{{ $r->id }}" @selected($filterRegionId === (string) $r->id)>{{ $r->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="gt-filter-field">
            <label class="form-label" for="filter_cluster">Cluster</label>
            <select id="filter_cluster" class="form-select gt-org-filter" data-search="true" data-allow-clear="false" data-placeholder="All clusters">
                <option value="">All clusters</option>
                @foreach($clusters as $c)
                    <option value="{{ $c->id }}" data-parent="{{ $c->parent_id }}" @selected($filterClusterId === (string) $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="gt-filter-field">
            <label class="form-label" for="filter_branch_unit">Branch</label>
            <select id="filter_branch_unit" class="form-select gt-org-filter" data-search="true" data-allow-clear="false" data-placeholder="All branches">
                <option value="">All branches</option>
                @foreach($branchUnits as $b)
                    @php
                        $bParent = $branches->firstWhere('id', (int) $b->parent_id);
                        $bRegionId = '';
                        if ($bParent) {
                            $bRegionId = $bParent->type === \App\Models\GoalOrgUnit::TYPE_REGION
                                ? (string) $bParent->id
                                : (($bParent->parent_id ?? '') !== '' ? (string) $bParent->parent_id : '');
                        }
                    @endphp
                    <option value="{{ $b->id }}" data-parent="{{ $b->parent_id }}" data-region="{{ $bRegionId }}" @selected($filterBranchId === (string) $b->id)>{{ $b->name }}</option>
                @endforeach
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

    <div class="kpi-grid">
        <button type="button" class="kpi-card tone-green is-clickable" data-kpi="effective" data-bs-toggle="modal" data-bs-target="#gtKpiModal">
            <span class="kpi-ico" aria-hidden="true"><i class="bi bi-bullseye"></i></span>
            <div class="kpi-body">
                <div class="kpi-label">Effective Target</div>
                <div class="kpi-value">{{ $fmt($stats['total_effective']) }}</div>
                <div class="kpi-meta">Base {{ $fmt($stats['total_base']) }} + Opening {{ $fmt($stats['total_opening']) }}</div>
                <div class="kpi-hint"><i class="bi bi-info-circle"></i> View breakdown</div>
            </div>
        </button>
        <button type="button" class="kpi-card tone-sky is-clickable" data-kpi="achieved" data-bs-toggle="modal" data-bs-target="#gtKpiModal">
            <span class="kpi-ico" aria-hidden="true"><i class="bi bi-cash-stack"></i></span>
            <div class="kpi-body">
                <div class="kpi-label">Achieved</div>
                <div class="kpi-value">{{ $fmt($stats['total_actual']) }}</div>
                <div class="kpi-meta">{{ number_format($stats['count']) }} target{{ $stats['count'] === 1 ? '' : 's' }} in view</div>
                <div class="kpi-hint"><i class="bi bi-info-circle"></i> View details</div>
            </div>
        </button>
        <button type="button" class="kpi-card tone-violet is-clickable" data-kpi="pct" data-bs-toggle="modal" data-bs-target="#gtKpiModal">
            <span class="kpi-ico" aria-hidden="true"><i class="bi bi-trophy"></i></span>
            <div class="kpi-body">
                <div class="kpi-label">Achievement %</div>
                <div class="kpi-value">{{ number_format((float) $stats['achievement_pct'], 1) }}%</div>
                <div class="kpi-meta">Achieved ÷ Effective target</div>
                <div class="kpi-hint"><i class="bi bi-info-circle"></i> Hierarchy breakdown</div>
            </div>
        </button>
        <button type="button" class="kpi-card tone-amber is-clickable" id="gtShortfallKpi" data-kpi="shortfall" data-bs-toggle="modal" data-bs-target="#gtKpiModal">
            <span class="kpi-ico" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></span>
            <div class="kpi-body">
                <div class="kpi-label">Closing Shortfall</div>
                <div class="kpi-value">{{ $fmt($stats['total_closing']) }}</div>
                @if(($stats['surplus'] ?? 0) > 0)
                    <div class="kpi-meta">Surplus {{ $fmt($stats['surplus']) }} · period remaining only</div>
                @else
                    <div class="kpi-meta">Remaining for selected period</div>
                @endif
                <div class="kpi-hint"><i class="bi bi-info-circle"></i> Shortfall detail</div>
            </div>
        </button>
    </div>

    @php
        $compactAmt = function ($n) use ($currency) {
            $n = (float) $n;
            $abs = abs($n);
            if ($abs >= 10000000) {
                return $currency . ' ' . rtrim(rtrim(number_format($n / 10000000, 2, '.', ''), '0'), '.') . 'Cr';
            }
            if ($abs >= 100000) {
                return $currency . ' ' . rtrim(rtrim(number_format($n / 100000, 2, '.', ''), '0'), '.') . 'L';
            }
            if ($abs >= 1000) {
                return $currency . ' ' . rtrim(rtrim(number_format($n / 1000, 1, '.', ''), '0'), '.') . 'K';
            }
            return $currency . ' ' . number_format($n, 0, '.', ',');
        };
        $regionPalette = ['#15803d', '#22d3ee', '#eab308', '#16a34a', '#0ea5e9', '#a78bfa', '#f59e0b', '#64748b'];
        $regionTotal = (float) ($regionChart['total_actual'] ?? 0);
        $setUrl = route('goal.targets.set', request()->only('period', 'metric'));
    @endphp
    <div class="charts-grid">
        <div class="panel" id="gtTrendSection">
            <div class="panel-head">
                <div>
                    <h5>Target vs Achievement (Monthly Trend)</h5>
                </div>
            </div>
            <div class="panel-body">
                <div class="chart-wrap">
                    @if(empty($chart['labels'] ?? []))
                        <div class="chart-empty"><i class="bi bi-bar-chart"></i>No period data yet</div>
                    @endif
                    <canvas id="gtTrendChart" aria-label="Target vs achievement chart"></canvas>
                </div>
            </div>
        </div>
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h5>Achievement by Region</h5>
                </div>
            </div>
            <div class="panel-body">
                @if(empty($regionChart['labels'] ?? []))
                    <div class="chart-wrap sm">
                        <div class="chart-empty"><i class="bi bi-pie-chart"></i>No regional data yet</div>
                        <canvas id="gtRegionChart" aria-label="Achievement by region chart" class="d-none"></canvas>
                    </div>
                @else
                    <div class="donut-layout">
                        <div class="donut-wrap">
                            <div class="donut-center" id="gtRegionCenter" aria-hidden="true">
                                <div class="dc-value">{{ $compactAmt($regionTotal) }}</div>
                                <div class="dc-label">Achieved</div>
                            </div>
                            <canvas id="gtRegionChart" aria-label="Achievement by region chart"></canvas>
                        </div>
                        <ul class="donut-legend" id="gtRegionLegend">
                            @foreach(($regionChart['labels'] ?? []) as $i => $label)
                                @php
                                    $val = (float) ($regionChart['values'][$i] ?? 0);
                                    $pct = $regionTotal > 0 ? round(($val / $regionTotal) * 100, 1) : 0;
                                    $color = $regionPalette[$i % count($regionPalette)];
                                @endphp
                                <li>
                                    <span class="dl-dot" style="background:{{ $color }}"></span>
                                    <div>
                                        <div class="dl-name">{{ $label }}</div>
                                        <div class="dl-meta">{{ $compactAmt($val) }} · {{ $pct }}%</div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
        <div class="panel panel-actions">
            <div class="panel-head">
                <div>
                    <h5><i class="bi bi-bar-chart-line"></i> Quick Actions</h5>
                </div>
            </div>
            <div class="panel-body">
                <div class="qa-box">
                    @if($canManage)
                        <a href="{{ $setUrl }}" class="qa-item">
                            <span class="qa-ico"><i class="bi bi-pencil"></i></span>
                            <span>Edit Targets</span>
                            <i class="bi bi-chevron-right qa-chev"></i>
                        </a>
                        <a href="{{ $setUrl }}" class="qa-item">
                            <span class="qa-ico"><i class="bi bi-layers"></i></span>
                            <span>Reallocate Target</span>
                            <i class="bi bi-chevron-right qa-chev"></i>
                        </a>
                    @endif
                    <a href="{{ route('goal.targets.summary', request()->only('period', 'metric')) }}" class="qa-item">
                        <span class="qa-ico"><i class="bi bi-clipboard-data"></i></span>
                        <span>Target Summary</span>
                        <i class="bi bi-chevron-right qa-chev"></i>
                    </a>
                    <a href="{{ route('goal.targets.achievement', request()->only('period', 'metric')) }}" class="qa-item">
                        <span class="qa-ico"><i class="bi bi-trophy"></i></span>
                        <span>Achievement ranking</span>
                        <i class="bi bi-chevron-right qa-chev"></i>
                    </a>
                    <a href="{{ route('goal.targets.carry-forward', request()->only('period', 'metric')) }}" class="qa-item">
                        <span class="qa-ico"><i class="bi bi-arrow-repeat"></i></span>
                        <span>Carry Forward</span>
                        <i class="bi bi-chevron-right qa-chev"></i>
                    </a>
                    <a href="#gtActivityPanel" class="qa-item" id="gtQaHistory">
                        <span class="qa-ico"><i class="bi bi-clock-history"></i></span>
                        <span>Target History</span>
                        <i class="bi bi-chevron-right qa-chev"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-lower">
        <div class="gt-table-card">
            <div class="gt-table-head">
                <div>
                    <h3 class="gt-table-title"><i class="bi bi-diagram-3"></i> Target Allocation</h3>
                    <div class="gt-table-meta">Expand Region → Cluster → Branch → Employee. Figures follow filters above.</div>
                </div>
                <nav class="metric-tabs" aria-label="Target metric">
                    <button type="button" class="metric-tab-btn {{ $tableMetric === 'revenue' ? 'is-active' : '' }}" data-metric-switch="revenue">
                        <i class="bi bi-cash-stack"></i> Revenue Target
                    </button>
                    <button type="button" class="metric-tab-btn {{ $tableMetric === 'disbursement' ? 'is-active' : '' }}" data-metric-switch="disbursement">
                        <i class="bi bi-cash-stack"></i> Disbursement Target
                    </button>
                </nav>
            </div>

            <div class="table-responsive gt-table-wrap">
                <table class="table align-middle mb-0 gt-table" id="targetsAllocationTable">
                    <thead>
                        <tr>
                            <th class="col-num">#</th>
                            <th class="col-entity">Entity (Hierarchy)</th>
                            <th class="col-amt">Base (₹)</th>
                            <th class="col-amt">Opening (₹)</th>
                            <th class="col-amt">Adj. (₹)</th>
                            <th class="col-amt">Effective (₹)</th>
                            <th class="col-amt">Achieved (₹)</th>
                            <th class="col-pct">Ach. %</th>
                            @if($canManage)<th class="col-actions">Actions</th>@endif
                        </tr>
                    </thead>
                    <tbody id="gtAllocBody">
                        <tr><td colspan="{{ $canManage ? 9 : 8 }}" class="text-center"><div class="gt-empty"><i class="bi bi-hourglass-split"></i>Loading hierarchy…</div></td></tr>
                    </tbody>
                    <tfoot id="gtAllocFoot" class="d-none">
                        <tr>
                            <td colspan="2"><strong>Total</strong></td>
                            <td class="col-amt" id="gtFootBase">0</td>
                            <td class="col-amt" id="gtFootOpen">0</td>
                            <td class="col-amt" id="gtFootAdj">0</td>
                            <td class="col-amt is-eff" id="gtFootEff">0</td>
                            <td class="col-amt" id="gtFootAct">0</td>
                            <td id="gtFootPct"><span class="gt-progress-pct">—</span></td>
                            @if($canManage)<td class="col-actions"></td>@endif
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="gt-table-foot">
                <div class="gt-showing" id="gtAllocHint">Expand the hierarchy to review figures. Use Edit to change targets in Set Targets.</div>
                <div class="gt-foot-right">
                    @if($canManage)
                        <a href="{{ route('goal.targets.set', request()->only('period', 'metric')) }}" class="btn-gt-primary">
                            <i class="bi bi-bullseye"></i> Manage targets
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="panel activity-panel" id="gtActivityPanel">
            <div class="panel-head">
                <div>
                    <h5>Recent Activity</h5>
                    <div class="panel-sub">Latest target changes and assignments</div>
                </div>
                <button type="button" class="btn-gt-ghost" id="gtViewAllActivity" data-bs-toggle="modal" data-bs-target="#gtActivityModal">
                    View all
                </button>
            </div>
            <div class="panel-body">
                @if(empty($recentActivity))
                    <div class="gt-empty py-4">
                        <i class="bi bi-clock-history"></i>
                        <div>No recent activity yet.</div>
                    </div>
                @else
                    <ul class="activity-timeline">
                        @foreach($recentActivity as $item)
                            <li>
                                <p class="at-title">{{ $item['title'] ?? 'Target updated' }}</p>
                                <div class="at-meta">{{ $item['by'] ?? 'System' }} · {{ $item['at'] ?? '' }}</div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- KPI / Month / Activity drill-down modals --}}
<div class="modal fade gt-modal" id="gtKpiModal" tabindex="-1" aria-labelledby="gtKpiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title-wrap">
                    <span class="modal-icon"><i class="bi bi-pie-chart"></i></span>
                    <div>
                        <h5 class="modal-title" id="gtKpiModalLabel">KPI breakdown</h5>
                        <div class="modal-sub" id="gtKpiModalSub">Figures follow filters above.</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="gtKpiModalBody"></div>
            <div class="modal-footer">
                <a href="#targetsAllocationTable" class="btn-cancel" data-bs-dismiss="modal" id="gtKpiGotoTable">View allocation</a>
                @if($canManage)
                    <a href="{{ route('goal.targets.set', request()->only('period', 'metric')) }}" class="btn-save"><i class="bi bi-bullseye"></i> Manage targets</a>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade gt-modal" id="gtMonthModal" tabindex="-1" aria-labelledby="gtMonthModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title-wrap">
                    <span class="modal-icon"><i class="bi bi-calendar3"></i></span>
                    <div>
                        <h5 class="modal-title" id="gtMonthModalLabel">Month performance</h5>
                        <div class="modal-sub">Click Apply on filters to load this period on the dashboard.</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="gtMonthModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn-save" id="gtMonthApply"><i class="bi bi-funnel"></i> Filter to this period</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade gt-modal" id="gtActivityModal" tabindex="-1" aria-labelledby="gtActivityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title-wrap">
                    <span class="modal-icon"><i class="bi bi-clock-history"></i></span>
                    <div>
                        <h5 class="modal-title" id="gtActivityModalLabel">Target activity history</h5>
                        <div class="modal-sub">Who changed what, and when.</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date &amp; Time</th>
                                <th>Activity</th>
                                <th>Entity</th>
                                <th class="text-end">Previous</th>
                                <th class="text-end">New</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody id="gtActivityModalBody">
                            <tr><td colspan="6" class="text-center text-muted py-4">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
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
    const regionChart = @json($regionChart);
    const allocationTree = @json($allocationTree);
    const dashStats = @json($stats);
    const canManage = @json((bool) $canManage);
    const updateRouteTpl = @json($canManage ? route('goal.targets.update', ['target' => '__ID__']) : '');
    const setTargetsUrl = @json(route('goal.targets.set'));
    const dashPeriod = @json((string) ($filters['period'] ?? ''));
    const dashMetric = @json((string) ($tableMetric ?? 'revenue'));
    const activityUrl = @json(route('goal.targets.activity'));
    const openAdd = @json($openAddModal);
    const openEdit = @json($openEditModal);
    let collapsed = new Set();
    let pendingPeriod = '';

    function money(n) {
        return currency + ' ' + (Number(n) || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function amt(n) {
        return (Number(n) || 0).toLocaleString('en-IN', { maximumFractionDigits: 0 });
    }
    function submitFilters() {
        const form = document.getElementById('gtFiltersForm');
        if (form) form.submit();
    }
    function setSelectValueNative(id, value) {
        const el = document.getElementById(id);
        if (!el) return;
        el.value = value == null ? '' : String(value);
        el.dispatchEvent(new Event('change', { bubbles: true }));
        if (window.jQuery && jQuery.fn.select2 && jQuery(el).hasClass('select2-hidden-accessible')) {
            jQuery(el).val(el.value).trigger('change');
        }
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
        });
    }

    function typeMeta(type) {
        const t = String(type || '').toLowerCase();
        if (t === 'region') return { label: 'Region', icon: 'bi-building', cls: 'region' };
        if (t === 'cluster') return { label: 'Cluster', icon: 'bi-diagram-3', cls: 'cluster' };
        if (t === 'branch') return { label: 'Branch', icon: 'bi-bank', cls: 'branch' };
        return { label: 'Employee', icon: 'bi-person', cls: 'employee' };
    }

    function defaultCollapse(nodes, depth) {
        (nodes || []).forEach(function (n) {
            if (n.children && n.children.length) {
                if (depth >= 0) collapsed.add(n.key);
                defaultCollapse(n.children, depth + 1);
            }
        });
    }

    function firstPeriodInTree(nodes) {
        for (let i = 0; i < (nodes || []).length; i++) {
            const n = nodes[i];
            if (n.period) return String(n.period);
            const nested = firstPeriodInTree(n.children || []);
            if (nested) return nested;
        }
        return '';
    }

    function setTargetsHref(n) {
        const url = new URL(setTargetsUrl, window.location.origin);
        // Prefer dashboard filter period so Edit matches what the user is viewing
        const period = String(dashPeriod || n.period || firstPeriodInTree(allocationTree) || '').trim();
        const metric = String(dashMetric || n.metric || 'revenue').trim() || 'revenue';
        if (period) url.searchParams.set('period', period);
        url.searchParams.set('metric', metric);
        let orgId = Number(n.org_id || 0);
        let userId = Number(n.user_id || 0);
        const key = String(n.key || '');
        if (!orgId && key.indexOf('org-') === 0) orgId = Number(key.slice(4)) || 0;
        if (!userId && key.indexOf('user-') === 0) {
            const parts = key.split('-');
            userId = Number(parts[1]) || 0;
        }
        if (userId) url.searchParams.set('focus_user', String(userId));
        else if (orgId) url.searchParams.set('focus_org', String(orgId));
        return url.pathname + url.search;
    }

    function renderAllocRows(nodes, depth, parentCollapsed, pathPrefix, rows) {
        (nodes || []).forEach(function (n, i) {
            const num = pathPrefix ? pathPrefix + '.' + (i + 1) : String(i + 1);
            const hasKids = !!(n.children && n.children.length);
            const isCollapsed = collapsed.has(n.key);
            const hidden = parentCollapsed;
            if (!hidden) {
                const tm = typeMeta(n.type);
                const pad = depth * 14;
                const pct = Number(n.pct || 0);
                let actions = '';
                if (canManage) {
                    const href = setTargetsHref(n);
                    actions = `<td class="col-actions">
                        <div class="gt-row-actions">
                            <a href="${escapeHtml(href)}" class="gt-btn-edit" title="Edit in Set Targets">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <div class="gt-more-dd">
                                <button type="button" class="gt-btn-more" data-more-toggle="${escapeHtml(n.key)}" title="More" aria-haspopup="true">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="gt-more-menu" role="menu">
                                    <li><a href="${escapeHtml(href)}"><i class="bi bi-pencil"></i> Edit in Set Targets</a></li>
                                    <li><button type="button" data-alloc-act="allocate" data-key="${escapeHtml(n.key)}" ${hasKids ? '' : 'disabled'}>
                                        <i class="bi bi-people"></i> Allocate to Child
                                    </button></li>
                                    <li><button type="button" data-alloc-act="details" data-key="${escapeHtml(n.key)}">
                                        <i class="bi bi-file-text"></i> View Details
                                    </button></li>
                                    <li><button type="button" data-alloc-act="history" data-key="${escapeHtml(n.key)}">
                                        <i class="bi bi-clock-history"></i> Target History
                                    </button></li>
                                    <li><a href="${escapeHtml(href)}" class="danger"><i class="bi bi-eraser"></i> Clear / reassign</a></li>
                                </ul>
                            </div>
                        </div>
                    </td>`;
                }
                rows.push(`<tr data-key="${escapeHtml(n.key)}" data-depth="${depth}">
                    <td class="col-num">${num}</td>
                    <td>
                        <div class="gt-entity" style="padding-left:${pad}px">
                            <button type="button" class="gt-tree-toggle ${hasKids ? '' : 'is-leaf'}" data-toggle-key="${escapeHtml(n.key)}" aria-label="${isCollapsed ? 'Expand' : 'Collapse'}">
                                <i class="bi ${isCollapsed ? 'bi-chevron-right' : 'bi-chevron-down'}"></i>
                            </button>
                            <span class="gt-ent-ico ${tm.cls}"><i class="bi ${tm.icon}"></i></span>
                            <div style="min-width:0">
                                <div class="gt-owner-name">${escapeHtml(n.name)}</div>
                                <div class="gt-owner-sub">${escapeHtml(n.meta || '')}</div>
                            </div>
                        </div>
                    </td>
                    <td class="col-amt">${amt(n.base)}</td>
                    <td class="col-amt">${amt(n.opening)}</td>
                    <td class="col-amt">${amt(n.adjustments)}</td>
                    <td class="col-amt is-eff">${amt(n.effective)}</td>
                    <td class="col-amt">${amt(n.actual)}</td>
                    <td>
                        <div class="gt-progress-wrap" title="${pct}%">
                            <span class="gt-progress-pct">${pct}%</span>
                            <div class="gt-progress"><span style="width:${Math.min(100, pct)}%"></span></div>
                        </div>
                    </td>
                    ${actions}
                </tr>`);
            }
            if (hasKids) {
                renderAllocRows(n.children, depth + 1, hidden || isCollapsed, num, rows);
            }
        });
        return rows;
    }

    function renderAllocationTable() {
        closeAllocMenus();
        const body = document.getElementById('gtAllocBody');
        const foot = document.getElementById('gtAllocFoot');
        if (!body) return;
        const tree = allocationTree || [];
        if (!tree.length) {
            body.innerHTML = `<tr><td colspan="${canManage ? 9 : 8}" class="text-center">
                <div class="gt-empty"><i class="bi bi-inbox"></i><div>No hierarchy / targets for these filters.</div>
                ${canManage ? `<a href="{{ route('goal.targets.set') }}" class="btn-gt-primary mt-3 d-inline-flex"><i class="bi bi-bullseye"></i> Set Targets</a>` : ''}
                </div></td></tr>`;
            if (foot) foot.classList.add('d-none');
            return;
        }
        body.innerHTML = renderAllocRows(tree, 0, false, '', []).join('');
        let base = 0, open = 0, adj = 0, eff = 0, act = 0;
        tree.forEach(function (n) {
            base += Number(n.base || 0);
            open += Number(n.opening || 0);
            adj += Number(n.adjustments || 0);
            eff += Number(n.effective || 0);
            act += Number(n.actual || 0);
        });
        const pct = eff > 0 ? Math.round(Math.min(100, (act / eff) * 1000)) / 10 : 0;
        if (document.getElementById('gtFootBase')) document.getElementById('gtFootBase').textContent = amt(base);
        if (document.getElementById('gtFootOpen')) document.getElementById('gtFootOpen').textContent = amt(open);
        if (document.getElementById('gtFootAdj')) document.getElementById('gtFootAdj').textContent = amt(adj);
        if (document.getElementById('gtFootEff')) document.getElementById('gtFootEff').textContent = amt(eff);
        if (document.getElementById('gtFootAct')) document.getElementById('gtFootAct').textContent = amt(act);
        if (document.getElementById('gtFootPct')) {
            document.getElementById('gtFootPct').innerHTML = `<span class="gt-progress-pct">${pct}%</span>`;
        }
        if (foot) foot.classList.remove('d-none');
    }

    defaultCollapse(allocationTree, 0);
    // Keep top-level regions expanded by default
    (allocationTree || []).forEach(function (n) { collapsed.delete(n.key); });
    renderAllocationTable();

    function findAllocNode(nodes, key) {
        for (let i = 0; i < (nodes || []).length; i++) {
            const n = nodes[i];
            if (n.key === key) return n;
            const found = findAllocNode(n.children || [], key);
            if (found) return found;
        }
        return null;
    }

    function positionAllocMenu(toggle, menu) {
        if (!toggle || !menu) return;
        const rect = toggle.getBoundingClientRect();
        const menuW = Math.max(180, menu.offsetWidth || 180);
        const menuH = menu.offsetHeight || 200;
        let top = rect.bottom + 4;
        let left = rect.right - menuW;
        if (top + menuH > window.innerHeight - 8) top = Math.max(8, rect.top - menuH - 4);
        if (left < 8) left = 8;
        if (left + menuW > window.innerWidth - 8) left = window.innerWidth - menuW - 8;
        menu.style.top = top + 'px';
        menu.style.left = left + 'px';
        menu.style.right = 'auto';
    }

    document.getElementById('gtAllocBody')?.addEventListener('click', function (e) {
        const moreToggle = e.target.closest('[data-more-toggle]');
        if (moreToggle) {
            e.preventDefault();
            e.stopPropagation();
            const dd = moreToggle.closest('.gt-more-dd');
            const menu = dd.querySelector('.gt-more-menu');
            const willOpen = !dd.classList.contains('is-open');
            closeAllocMenus();
            if (willOpen && menu) {
                dd.classList.add('is-open');
                let ownerKey = dd.getAttribute('data-more-owner');
                if (!ownerKey) {
                    ownerKey = 'gt-more-' + Math.random().toString(36).slice(2, 9);
                    dd.setAttribute('data-more-owner', ownerKey);
                }
                menu.dataset.portalOwner = ownerKey;
                document.body.appendChild(menu);
                menu.classList.add('is-portal');
                positionAllocMenu(moreToggle, menu);
                requestAnimationFrame(function () { positionAllocMenu(moreToggle, menu); });
            }
            return;
        }

        const btn = e.target.closest('[data-toggle-key]');
        if (!btn) return;
        const key = btn.getAttribute('data-toggle-key');
        if (collapsed.has(key)) collapsed.delete(key);
        else collapsed.add(key);
        renderAllocationTable();
    });

    function runAllocAction(allocAct) {
        if (!allocAct || allocAct.disabled) return;
        closeAllocMenus();
        const act = allocAct.getAttribute('data-alloc-act');
        const key = allocAct.getAttribute('data-key');
        if (act === 'allocate' && key) {
            collapsed.delete(key);
            const node = findAllocNode(allocationTree, key);
            (node?.children || []).forEach(function (c) {
                if (c.children && c.children.length) collapsed.add(c.key);
            });
            renderAllocationTable();
            return;
        }
        if (act === 'details' || act === 'history') {
            document.getElementById('gtActivityPanel')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            return;
        }
        if (act === 'remove') {
            if (typeof showFlashSwal === 'function') {
                showFlashSwal('info', 'Use Set Targets to clear or reassign this row.');
            } else if (window.Swal) {
                Swal.fire({ icon: 'info', title: 'Remove target', text: 'Use Set Targets to clear or reassign this row.', confirmButtonColor: '#059669' });
            }
        }
    }

    function closeAllocMenus() {
        document.querySelectorAll('.gt-more-menu.is-portal').forEach(function (menu) {
            const ownerKey = menu.dataset.portalOwner || '';
            const dd = ownerKey
                ? document.querySelector('.gt-more-dd[data-more-owner="' + ownerKey + '"]')
                : null;
            menu.classList.remove('is-portal');
            menu.style.top = '';
            menu.style.left = '';
            menu.style.right = '';
            menu.style.display = '';
            if (dd) dd.appendChild(menu);
            else if (menu.parentElement === document.body) menu.remove();
        });
        document.querySelectorAll('.gt-more-dd.is-open').forEach(function (el) {
            el.classList.remove('is-open');
        });
    }

    document.addEventListener('click', function (e) {
        const allocAct = e.target.closest('.gt-more-menu [data-alloc-act]');
        if (allocAct) {
            e.preventDefault();
            e.stopPropagation();
            runAllocAction(allocAct);
            return;
        }
        if (e.target.closest('.gt-more-menu, [data-more-toggle]')) return;
        closeAllocMenus();
    });
    document.addEventListener('scroll', closeAllocMenus, true);
    window.addEventListener('resize', closeAllocMenus);

    function syncOrgUnitFilterHidden() {
        const hidden = document.getElementById('filter_goal_org_unit_id');
        const branch = document.getElementById('filter_branch_unit');
        const cluster = document.getElementById('filter_cluster');
        const region = document.getElementById('filter_region');
        if (!hidden) return;
        const pick = (branch && branch.value) || (cluster && cluster.value) || (region && region.value) || '';
        hidden.value = pick;
    }

    function filterOrgOptions(selectEl, parentId, regionId) {
        if (!selectEl) return;
        const wantParent = parentId ? String(parentId) : '';
        const wantRegion = regionId ? String(regionId) : '';
        Array.from(selectEl.options).forEach(function (opt, idx) {
            if (idx === 0) {
                opt.hidden = false;
                return;
            }
            const p = opt.getAttribute('data-parent');
            const r = opt.getAttribute('data-region');
            if (p == null && r == null) {
                opt.hidden = false;
                return;
            }
            if (wantParent === '' && wantRegion === '') {
                opt.hidden = false;
                return;
            }
            const matchParent = wantParent !== '' && String(p) === wantParent;
            const matchRegion = wantRegion !== '' && r != null && String(r) === wantRegion;
            opt.hidden = !(matchParent || matchRegion);
        });
        if (selectEl.value) {
            const selected = selectEl.options[selectEl.selectedIndex];
            if (selected && selected.hidden) {
                selectEl.value = '';
                if (window.jQuery && window.jQuery(selectEl).hasClass('select2-hidden-accessible')) {
                    window.jQuery(selectEl).val('').trigger('change');
                }
            }
        }
    }

    function cascadeOrgFilters() {
        const region = document.getElementById('filter_region');
        const cluster = document.getElementById('filter_cluster');
        const branch = document.getElementById('filter_branch_unit');
        const regionVal = region?.value || '';
        const clusterVal = cluster?.value || '';
        filterOrgOptions(cluster, regionVal, '');
        filterOrgOptions(branch, clusterVal, clusterVal ? '' : regionVal);
        syncOrgUnitFilterHidden();
    }

    ['filter_region', 'filter_cluster', 'filter_branch_unit'].forEach(function (id) {
        const el = document.getElementById(id);
        el?.addEventListener('change', cascadeOrgFilters);
    });
    cascadeOrgFilters();
    document.getElementById('gtFiltersForm')?.addEventListener('submit', syncOrgUnitFilterHidden);

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

    document.getElementById('gtQaHistory')?.addEventListener('click', function (e) {
        e.preventDefault();
        document.getElementById('gtActivityPanel')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });

    document.querySelectorAll('[data-metric-switch]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const metric = btn.getAttribute('data-metric-switch') || '';
            setSelectValueNative('filter_metric', metric);
            submitFilters();
        });
    });

    function kpiBreakdownHtml(kind) {
        const s = dashStats || {};
        const rows = (regionChart.labels || []).map(function (label, i) {
            const act = Number(regionChart.values?.[i] || 0);
            const eff = Number(regionChart.effective?.[i] || 0);
            const pct = eff > 0 ? ((act / eff) * 100).toFixed(1) : '0.0';
            return `<tr>
                <td>${escapeHtml(label)}</td>
                <td class="text-end">${money(eff)}</td>
                <td class="text-end">${money(act)}</td>
                <td class="text-end">${pct}%</td>
            </tr>`;
        }).join('');
        const regionTable = rows
            ? `<div class="table-responsive mt-3"><table class="table table-sm mb-0"><thead><tr><th>Region</th><th class="text-end">Effective</th><th class="text-end">Achieved</th><th class="text-end">%</th></tr></thead><tbody>${rows}</tbody></table></div>`
            : '<p class="text-muted mb-0 mt-2">No regional rows in the current filter.</p>';

        if (kind === 'effective') {
            return `<table class="table table-sm mb-0">
                <tr><td>Base Target</td><td class="text-end fw-semibold">${money(s.total_base)}</td></tr>
                <tr><td>Opening Shortfall</td><td class="text-end fw-semibold">${money(s.total_opening)}</td></tr>
                <tr><td>Approved Adjustments</td><td class="text-end fw-semibold">${money(s.total_adjustments)}</td></tr>
                <tr class="table-success"><th>Effective Target</th><th class="text-end">${money(s.total_effective)}</th></tr>
            </table>
            <p class="small text-muted mt-3 mb-0">ET = Base + Opening Shortfall + Adjustments.</p>`;
        }
        if (kind === 'achieved') {
            return `<table class="table table-sm mb-0">
                <tr><td>Achieved</td><td class="text-end fw-semibold">${money(s.total_actual)}</td></tr>
                <tr><td>Targets in view</td><td class="text-end fw-semibold">${Number(s.count || 0).toLocaleString('en-IN')}</td></tr>
                <tr><td>Approved</td><td class="text-end">${Number(s.approved || 0).toLocaleString('en-IN')}</td></tr>
                <tr><td>Draft</td><td class="text-end">${Number(s.draft || 0).toLocaleString('en-IN')}</td></tr>
            </table>${regionTable}`;
        }
        if (kind === 'pct') {
            return `<p class="mb-2">Achievement % = Achieved ÷ Effective × 100</p>
            <table class="table table-sm mb-0">
                <tr><td>Achieved</td><td class="text-end">${money(s.total_actual)}</td></tr>
                <tr><td>Effective</td><td class="text-end">${money(s.total_effective)}</td></tr>
                <tr class="table-success"><th>Achievement</th><th class="text-end">${Number(s.achievement_pct || 0).toFixed(1)}%</th></tr>
            </table>${regionTable}`;
        }
        return `<table class="table table-sm mb-0">
            <tr><td>Effective Target</td><td class="text-end">${money(s.total_effective)}</td></tr>
            <tr><td>Achieved</td><td class="text-end">${money(s.total_actual)}</td></tr>
            <tr><td>Remaining (period)</td><td class="text-end fw-semibold">${money(s.remaining ?? s.total_closing)}</td></tr>
            <tr><td>Closing Shortfall</td><td class="text-end fw-semibold">${money(s.total_closing)}</td></tr>
            <tr><td>Surplus (over-achievement)</td><td class="text-end">${money(s.surplus || 0)}</td></tr>
        </table>
        <p class="small text-muted mt-3 mb-0">Closing shortfall is for the selected period only. Carry-forward to the next period requires an approved workflow.</p>`;
    }

    document.querySelectorAll('[data-kpi]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const kind = btn.getAttribute('data-kpi') || 'effective';
            const titles = {
                effective: 'Effective Target breakdown',
                achieved: 'Achievement details',
                pct: 'Achievement % by region',
                shortfall: 'Closing shortfall detail'
            };
            document.getElementById('gtKpiModalLabel').textContent = titles[kind] || 'KPI breakdown';
            document.getElementById('gtKpiModalBody').innerHTML = kpiBreakdownHtml(kind);
        });
    });

    document.getElementById('gtMonthApply')?.addEventListener('click', function () {
        if (!pendingPeriod) return;
        setSelectValueNative('filter_period', pendingPeriod);
        const modalEl = document.getElementById('gtMonthModal');
        const inst = bootstrap.Modal.getInstance(modalEl);
        inst?.hide();
        submitFilters();
    });

    async function loadActivityModal() {
        const body = document.getElementById('gtActivityModalBody');
        if (!body) return;
        body.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Loading…</td></tr>';
        const params = new URLSearchParams();
        const period = document.getElementById('filter_period')?.value || '';
        const metric = document.getElementById('filter_metric')?.value || '';
        if (period) params.set('period', period);
        if (metric) params.set('metric', metric);
        try {
            const res = await fetch(activityUrl + (params.toString() ? ('?' + params.toString()) : ''), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            const rows = json.data || [];
            if (!rows.length) {
                body.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No activity for current filters.</td></tr>';
                return;
            }
            body.innerHTML = rows.map(function (r) {
                return `<tr>
                    <td>${escapeHtml(r.when || r.at || '')}</td>
                    <td>${escapeHtml(r.title || r.action || '')}</td>
                    <td>${escapeHtml(r.entity || '')}</td>
                    <td class="text-end">${money(r.old_value)}</td>
                    <td class="text-end">${money(r.new_value)}</td>
                    <td>${escapeHtml(r.by || '')}</td>
                </tr>`;
            }).join('');
        } catch (err) {
            body.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">Could not load activity.</td></tr>';
        }
    }
    document.getElementById('gtActivityModal')?.addEventListener('show.bs.modal', loadActivityModal);

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
                            label: 'Target',
                            data: trend.effective,
                            backgroundColor: '#86efac',
                            borderRadius: 4,
                            maxBarThickness: 14,
                            categoryPercentage: 0.7,
                            barPercentage: 0.9,
                            yAxisID: 'y',
                            order: 2
                        },
                        {
                            label: 'Achieved',
                            data: trend.actual,
                            backgroundColor: '#15803d',
                            borderRadius: 4,
                            maxBarThickness: 14,
                            categoryPercentage: 0.7,
                            barPercentage: 0.9,
                            yAxisID: 'y',
                            order: 2
                        },
                        {
                            label: 'Achievement %',
                            data: trend.pct || [],
                            type: 'line',
                            borderColor: '#166534',
                            backgroundColor: 'transparent',
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#166534',
                            pointBorderWidth: 2,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            tension: .35,
                            yAxisID: 'y1',
                            order: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { top: 4, bottom: 0 } },
                    onClick: function (evt, elements) {
                        if (!elements || !elements.length) return;
                        const idx = elements[0].index;
                        const period = (trend.periods || [])[idx] || '';
                        const label = (trend.labels || [])[idx] || period;
                        const target = Number((trend.effective || [])[idx] || 0);
                        const achieved = Number((trend.actual || [])[idx] || 0);
                        const shortfall = Math.max(0, target - achieved);
                        const pct = Number((trend.pct || [])[idx] || 0);
                        pendingPeriod = period;
                        document.getElementById('gtMonthModalLabel').textContent = label + ' performance';
                        document.getElementById('gtMonthModalBody').innerHTML = `<table class="table table-sm mb-0">
                            <tr><td>Period</td><td class="text-end fw-semibold">${escapeHtml(period)}</td></tr>
                            <tr><td>Target</td><td class="text-end">${money(target)}</td></tr>
                            <tr><td>Achieved</td><td class="text-end">${money(achieved)}</td></tr>
                            <tr><td>Shortfall</td><td class="text-end">${money(shortfall)}</td></tr>
                            <tr class="table-success"><th>Achievement</th><th class="text-end">${pct.toFixed(1)}%</th></tr>
                        </table>`;
                        new bootstrap.Modal(document.getElementById('gtMonthModal')).show();
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 10, boxHeight: 10, usePointStyle: true, pointStyle: 'rectRounded',
                                font: { size: 11 }, padding: 12, color: '#475569'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    if (ctx.dataset.yAxisID === 'y1') {
                                        return ctx.dataset.label + ': ' + (ctx.parsed.y ?? 0) + '%';
                                    }
                                    return ctx.dataset.label + ': ' + money(ctx.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10 }, color: '#94a3b8', maxRotation: 0 }
                        },
                        y: {
                            type: 'linear',
                            position: 'left',
                            beginAtZero: true,
                            ticks: {
                                font: { size: 10 }, color: '#94a3b8',
                                callback: function (v) {
                                    if (v >= 10000000) return '₹ ' + (v / 10000000).toFixed(0) + ' Cr';
                                    if (v >= 100000) return '₹ ' + (v / 100000).toFixed(0) + ' L';
                                    if (v >= 1000) return '₹ ' + (v / 1000).toFixed(0) + ' K';
                                    return '₹ ' + v;
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            position: 'right',
                            beginAtZero: true,
                            suggestedMax: 100,
                            grid: { drawOnChartArea: false },
                            ticks: {
                                font: { size: 10 }, color: '#94a3b8',
                                callback: function (v) { return v + '%'; }
                            }
                        }
                    }
                }
            });
        }
    }

    const regionEl = document.getElementById('gtRegionChart');
    if (regionEl && window.Chart) {
        const hasRegion = (regionChart.labels || []).length > 0;
        if (hasRegion) {
            const palette = ['#15803d', '#22d3ee', '#eab308', '#16a34a', '#0ea5e9', '#a78bfa', '#f59e0b', '#64748b'];
            new Chart(regionEl, {
                type: 'doughnut',
                data: {
                    labels: regionChart.labels,
                    datasets: [{
                        data: regionChart.values,
                        backgroundColor: regionChart.labels.map(function (_, i) { return palette[i % palette.length]; }),
                        borderWidth: 2,
                        borderColor: '#fff',
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    onClick: function (evt, elements) {
                        if (!elements || !elements.length) return;
                        const idx = elements[0].index;
                        const orgId = (regionChart.org_ids || [])[idx];
                        if (!orgId) return;
                        const regionSel = document.getElementById('filter_region');
                        if (regionSel) {
                            regionSel.value = String(orgId);
                            regionSel.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        document.getElementById('filter_goal_org_unit_id').value = String(orgId);
                        setSelectValueNative('filter_cluster', '');
                        setSelectValueNative('filter_branch_unit', '');
                        submitFilters();
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    const val = ctx.parsed || 0;
                                    const total = (regionChart.values || []).reduce(function (s, n) { return s + Number(n || 0); }, 0);
                                    const pct = total > 0 ? ((val / total) * 100).toFixed(1) : '0';
                                    return ' ' + ctx.label + ': ' + money(val) + ' (' + pct + '%)';
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

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-edit-target');
        if (!btn) return;
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
