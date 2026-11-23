<?php
    $p = $payload;
    $unit = $p['unit'] ?? '₹';
    $jumpPct = (float) ($p['jump_pct'] ?? 15);
    $periodOptions = collect(range(0, 11))->map(function ($i) {
        $d = now()->startOfMonth()->addMonths($i);

        return ['value' => $d->format('Y-m'), 'label' => $d->format('M Y')];
    });
    if (! $periodOptions->contains('value', $p['period'] ?? '')) {
        $cur = \Carbon\Carbon::createFromFormat('Y-m', $p['period']);
        $periodOptions->prepend(['value' => $cur->format('Y-m'), 'label' => $cur->format('M Y')]);
    }
    $receives = (bool) ($p['self']['receives_target'] ?? false);
?>

<?php $__env->startPush('styles'); ?>
<?php echo $__env->make('goal.partials.pd_hub_styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<style>
.goal-set-targets {
    --st-accent: var(--theme-primary, #16a34a);
    --st-accent-soft: color-mix(in srgb, var(--st-accent) 12%, #fff);
    --st-border: #e8edf5;
    --st-muted: #64748b;
    --st-text: #0f172a;
    color: var(--st-text);
    padding-bottom: 110px;
    background: #f4f6f9;
}

/* Hero */
.goal-set-targets .st-hero {
    display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between;
    gap: 14px; margin-bottom: 14px;
}
.goal-set-targets .st-crumbs {
    display: flex; flex-wrap: wrap; align-items: center; gap: 6px;
    margin: 0 0 10px; padding: 0; list-style: none; font-size: .78rem; color: #86a89a;
}
.goal-set-targets .st-crumbs a { color: var(--st-accent); text-decoration: none; font-weight: 600; }
.goal-set-targets .st-crumbs .sep { opacity: .55; }
.goal-set-targets .st-title-row {
    display: flex; align-items: flex-start; gap: 14px;
}
.goal-set-targets .st-title-ico {
    width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0;
    display: inline-flex; align-items: center; justify-content: center;
    background: #e8f8ef; color: var(--st-accent); font-size: 1.45rem;
    border: 1px solid #c6ebd5;
}
.goal-set-targets .st-title { margin: 0; font-size: 1.55rem; font-weight: 800; letter-spacing: -.02em; color: #0f172a; }
.goal-set-targets .st-sub { margin: 4px 0 0; color: #64748b; font-size: .88rem; max-width: 520px; line-height: 1.4; }
.goal-set-targets .st-hero-actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }

.goal-set-targets .st-btn {
    height: 38px; min-height: 38px; border-radius: 10px; padding: 0 14px;
    font-weight: 650; font-size: .82rem;
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    border: 1px solid #dbe3f0; background: #fff; color: #334155;
    text-decoration: none; white-space: nowrap; cursor: pointer;
}
.goal-set-targets .st-btn:hover { background: #f8fafc; color: #0f172a; }
.goal-set-targets .st-btn:disabled { opacity: .55; cursor: not-allowed; }
.goal-set-targets .st-btn-primary {
    border: 0; background: var(--st-accent); color: #fff;
    box-shadow: 0 6px 14px color-mix(in srgb, var(--st-accent) 30%, transparent);
}
.goal-set-targets .st-btn-primary:hover { filter: brightness(1.05); color: #fff; }
.goal-set-targets .st-btn-history {
    background: #e8f8ef; border-color: #9fd4b3; color: #166534; font-weight: 700;
}
.goal-set-targets .st-btn-history:hover { background: #dcfce7; color: #14532d; }
.goal-set-targets .st-btn-sm { height: 32px; min-height: 32px; padding: 0 10px; font-size: .75rem; border-radius: 8px; }
.goal-set-targets .st-btn-icon { width: 32px; padding: 0; }

.goal-set-targets .st-control,
.goal-set-targets .form-select.st-control {
    height: 38px; min-height: 38px; border-radius: 9px; border: 1px solid #d1d5db;
    font-size: .84rem; padding: 0 12px; background: #fff; color: var(--st-text); width: 100%;
}
.goal-set-targets .form-select.st-control { padding-right: 2rem; }
.goal-set-targets .st-control:focus {
    border-color: var(--st-accent);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--st-accent) 18%, transparent);
    outline: 0;
}

.goal-set-targets .st-alert {
    border-radius: 10px; padding: .7rem 1rem; font-size: .84rem; margin-bottom: .85rem;
}
.goal-set-targets .st-alert.warn { background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; }
.goal-set-targets .st-alert.ok { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
.goal-set-targets .st-alert.err { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.goal-set-targets .st-alert.info { background: #f8fafc; border: 1px solid #e2e8f0; color: #334155; }

/* KPI cards — compact, icon-left like mockup */
.goal-set-targets .st-kpi-grid {
    display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 14px;
}
@media (max-width: 1199.98px) { .goal-set-targets .st-kpi-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 575.98px) { .goal-set-targets .st-kpi-grid { grid-template-columns: 1fr; } }
.goal-set-targets .st-kpi {
    display: flex; align-items: center; gap: 12px;
    background: #fff; border-radius: 12px; padding: 12px 14px;
    border: 1px solid #e8edf5;
    box-shadow: 0 1px 2px rgba(15,23,42,.03), 0 6px 14px rgba(148,163,184,.08);
    min-height: 0;
}
.goal-set-targets .st-kpi.tone-green {
    background: #f3faf6; border-color: #d8eee0;
}
.goal-set-targets .st-kpi.tone-sky {
    background: #f3f8ff; border-color: #d7e6fb;
}
.goal-set-targets .st-kpi.tone-violet {
    background: #f7f4ff; border-color: #e4ddf8;
}
.goal-set-targets .st-kpi.tone-amber {
    background: #fff8f0; border-color: #f3e0c8;
}
.goal-set-targets .st-kpi-ico {
    width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
    display: inline-flex; align-items: center; justify-content: center; font-size: 1.05rem;
}
.goal-set-targets .st-kpi.tone-green .st-kpi-ico { background: #dcfce7; color: #15803d; }
.goal-set-targets .st-kpi.tone-sky .st-kpi-ico { background: #dbeafe; color: #2563eb; }
.goal-set-targets .st-kpi.tone-violet .st-kpi-ico { background: #ede9fe; color: #7c3aed; }
.goal-set-targets .st-kpi.tone-amber .st-kpi-ico { background: #ffedd5; color: #ea580c; }
.goal-set-targets .st-kpi-body { min-width: 0; flex: 1; }
.goal-set-targets .st-kpi-top {
    display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 2px;
}
.goal-set-targets .st-kpi-label {
    font-size: .72rem; font-weight: 750; color: #334155; margin: 0; line-height: 1.25;
}
.goal-set-targets .st-kpi-badge {
    display: inline-flex; align-items: center; flex-shrink: 0;
    background: #dcfce7; color: #15803d; font-size: .65rem; font-weight: 750;
    padding: 2px 7px; border-radius: 999px; line-height: 1.2;
}
.goal-set-targets .st-kpi-badge.down { background: #fee2e2; color: #b91c1c; }
.goal-set-targets .st-kpi-badge.is-empty { display: none; }
.goal-set-targets .st-kpi-value {
    font-size: 1.2rem; font-weight: 800; letter-spacing: -.02em; margin: 0 0 2px;
    line-height: 1.2; color: #0f172a; font-variant-numeric: tabular-nums;
}
.goal-set-targets .st-kpi-meta {
    font-size: .7rem; color: #94a3b8; margin: 0; line-height: 1.3;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

/* Self pills */
.goal-set-targets .st-mine {
    display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;
}
.goal-set-targets .st-pill {
    display: inline-flex; align-items: center; gap: 6px;
    background: #fff; border: 1px solid var(--st-border); border-radius: 999px;
    padding: 7px 12px; font-size: .8rem; font-weight: 650;
}
.goal-set-targets .st-pill i { color: var(--st-accent); }
.goal-set-targets .st-pill strong { font-variant-numeric: tabular-nums; }
.goal-set-targets .st-pill.left {
    background: var(--st-accent-soft);
    border-color: color-mix(in srgb, var(--st-accent) 28%, #e2e8f0);
}
.goal-set-targets .st-pill.left strong { color: var(--st-accent); }

/* Filters — reference image 2 exact toolbar */
.goal-set-targets .st-filters {
    --ft-h: 36px;
    --ft-green: #059669;
    --ft-green-bg: #ecfdf5;
    --ft-border: #e2e8f0;
    display: flex;
    flex-wrap: nowrap;
    align-items: flex-end;
    gap: 14px;
    background: #fff;
    border: 1px solid var(--ft-border);
    border-radius: 10px;
    padding: 10px 14px;
    margin-bottom: 14px;
    overflow: visible;
    position: relative;
    z-index: 30;
}
.goal-set-targets .st-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex: 0 0 auto;
    min-width: 0;
}
.goal-set-targets .st-field > label {
    font-size: 11px;
    font-weight: 600;
    color: #475569;
    margin: 0;
    line-height: 1.2;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
}
.goal-set-targets .st-field > label .req { color: #ef4444; font-weight: 700; }
.goal-set-targets .st-field > label .st-info {
    color: #94a3b8; font-size: 11px; line-height: 1; cursor: help;
}
.goal-set-targets .st-filters .st-control,
.goal-set-targets .st-filters .form-select.st-control {
    height: var(--ft-h);
    min-height: var(--ft-h);
    width: 132px;
    min-width: 132px;
    max-width: 132px;
    border-radius: 6px;
    border: 1px solid var(--ft-border);
    font-size: 12px;
    padding: 0 28px 0 10px;
    background-color: #fff;
    color: #0f172a;
    box-shadow: none;
}
.goal-set-targets .st-filters .form-select.st-control {
    background-position: right 8px center;
    background-size: 12px 10px;
}
.goal-set-targets .st-filters .st-control:focus,
.goal-set-targets .st-filters .form-select.st-control:focus {
    border-color: var(--ft-green);
    box-shadow: 0 0 0 2px rgba(5, 150, 105, .15);
    outline: 0;
}
/* Custom filter dropdowns — solid panel, no native/Select2 bleed */
.goal-set-targets .st-dd {
    position: relative;
    width: 150px;
}
.goal-set-targets .st-dd.is-metric { width: 132px; }
.goal-set-targets .st-dd-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    height: var(--ft-h);
    padding: 0 10px;
    border: 1px solid var(--ft-border);
    border-radius: 6px;
    background: #fff;
    color: #0f172a;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    text-align: left;
}
.goal-set-targets .st-dd-btn:hover { background: #f8fafc; }
.goal-set-targets .st-dd-btn:focus,
.goal-set-targets .st-dd.is-open .st-dd-btn {
    border-color: var(--ft-green);
    box-shadow: 0 0 0 2px rgba(5, 150, 105, .15);
    outline: 0;
}
.goal-set-targets .st-dd-btn .st-dd-ico {
    color: #64748b;
    font-size: 13px;
    flex-shrink: 0;
}
.goal-set-targets .st-dd-btn .st-dd-label {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.goal-set-targets .st-dd-btn .st-dd-caret {
    color: #94a3b8;
    font-size: 11px;
    flex-shrink: 0;
    margin-left: auto;
}
.goal-set-targets .st-dd-menu {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    min-width: 100%;
    max-height: 240px;
    overflow-y: auto;
    margin: 0;
    padding: 6px;
    list-style: none;
    background: #fff;
    border: 1px solid var(--ft-border);
    border-radius: 8px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, .14), 0 2px 6px rgba(15, 23, 42, .06);
    z-index: 1200;
    display: none;
}
.goal-set-targets .st-dd.is-open .st-dd-menu { display: block; }
.goal-set-targets .st-dd-menu li {
    display: block;
    padding: 8px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
    line-height: 1.25;
}
.goal-set-targets .st-dd-menu li:hover { background: #f1f5f9; color: #0f172a; }
.goal-set-targets .st-dd-menu li.is-selected {
    background: var(--ft-green-bg);
    color: var(--ft-green);
}
.goal-set-targets .st-dd-menu li:focus {
    outline: 0;
    background: var(--ft-green-bg);
}

/* Apply To — connected segmented control */
.goal-set-targets .st-seg {
    display: inline-flex;
    align-items: stretch;
    border: 1px solid var(--ft-border);
    border-radius: 6px;
    overflow: hidden;
    background: #fff;
    height: var(--ft-h);
}
.goal-set-targets .st-seg button {
    border: 0;
    border-right: 1px solid var(--ft-border);
    background: #fff;
    color: #334155;
    font-size: 12px;
    font-weight: 600;
    padding: 0 12px;
    height: 100%;
    line-height: 1;
    cursor: pointer;
    white-space: nowrap;
    margin: 0;
    border-radius: 0;
}
.goal-set-targets .st-seg button:last-child { border-right: 0; }
.goal-set-targets .st-seg button:hover { background: #f8fafc; }
.goal-set-targets .st-seg button.is-active {
    background: var(--ft-green-bg);
    color: var(--ft-green);
    font-weight: 700;
    box-shadow: inset 0 0 0 1px var(--ft-green);
}

/* Hierarchy toggle — aligned to control baseline */
.goal-set-targets .st-toggle-wrap {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    height: var(--ft-h);
    flex: 0 0 auto;
    font-size: 12px;
    font-weight: 600;
    color: #334155;
    white-space: nowrap;
    margin: 0;
    padding: 0;
}
.goal-set-targets .st-switch {
    position: relative;
    width: 36px;
    height: 20px;
    flex-shrink: 0;
    margin: 0;
}
.goal-set-targets .st-switch input {
    opacity: 0; width: 0; height: 0; position: absolute;
}
.goal-set-targets .st-switch span {
    position: absolute; inset: 0;
    background: #cbd5e1;
    border-radius: 999px;
    cursor: pointer;
    transition: .15s;
}
.goal-set-targets .st-switch span::before {
    content: '';
    position: absolute;
    width: 14px; height: 14px;
    left: 3px; top: 3px;
    background: #fff;
    border-radius: 50%;
    transition: .15s;
    box-shadow: 0 1px 2px rgba(15,23,42,.18);
}
.goal-set-targets .st-switch input:checked + span { background: var(--ft-green); }
.goal-set-targets .st-switch input:checked + span::before { transform: translateX(16px); }

/* Search — far right, fixed ~165px */
.goal-set-targets .st-search {
    margin-left: auto;
    flex: 0 0 165px;
    width: 165px;
    max-width: 165px;
}
.goal-set-targets .st-search > label {
    visibility: hidden;
    height: 11px;
    margin: 0;
    overflow: hidden;
    pointer-events: none;
}
.goal-set-targets .st-search-box { position: relative; display: block; }
.goal-set-targets .st-search-box i {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    pointer-events: none;
    font-size: 12px;
}
.goal-set-targets .st-search-box .st-control {
    width: 165px !important;
    min-width: 165px !important;
    max-width: 165px !important;
    padding-left: 30px !important;
    padding-right: 10px !important;
}

@media (max-width: 1100px) {
    .goal-set-targets .st-filters {
        flex-wrap: wrap;
        align-items: flex-end;
    }
    .goal-set-targets .st-search {
        margin-left: 0;
        flex: 1 1 165px;
        max-width: none;
        width: auto;
    }
    .goal-set-targets .st-search-box .st-control {
        width: 100% !important;
        max-width: none !important;
    }
}
@media (max-width: 575.98px) {
    .goal-set-targets .st-filters {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }
    .goal-set-targets .st-filters .st-control,
    .goal-set-targets .st-filters .form-select.st-control {
        width: 100%;
        min-width: 0;
        max-width: none;
    }
    .goal-set-targets .st-dd,
    .goal-set-targets .st-dd.is-metric { width: 100%; }
    .goal-set-targets .st-seg { width: 100%; }
    .goal-set-targets .st-seg button { flex: 1; padding: 0 8px; }
    .goal-set-targets .st-toggle-wrap { height: auto; }
    .goal-set-targets .st-search {
        width: 100%;
        max-width: none;
        flex: 1 1 auto;
    }
}

/* Panel + table */
.goal-set-targets .st-panel {
    position: relative;
    z-index: 1;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
    box-shadow: 0 4px 16px rgba(148,163,184,.1); overflow: hidden;
}
.goal-set-targets .st-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
.goal-set-targets table.st-tree {
    width: 100%; min-width: 1100px; border-collapse: collapse; font-size: .82rem;
}
.goal-set-targets table.st-tree th {
    text-align: left; font-size: .62rem; font-weight: 750; letter-spacing: .04em;
    text-transform: uppercase; color: #64748b;
    padding: 11px 12px; background: #eef2f7; border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}
.goal-set-targets table.st-tree th .th-info {
    color: #94a3b8; margin-left: 3px; font-size: .7rem; vertical-align: middle;
}
.goal-set-targets table.st-tree th.num,
.goal-set-targets table.st-tree td.num { text-align: right; }
.goal-set-targets table.st-tree th.idx,
.goal-set-targets table.st-tree td.idx {
    width: 72px; min-width: 64px; text-align: left; color: #94a3b8; font-weight: 650;
    font-variant-numeric: tabular-nums; font-size: .78rem; white-space: nowrap;
}
.goal-set-targets table.st-tree tbody tr[data-depth="1"] { background: #fafbfc; }
.goal-set-targets table.st-tree tbody tr[data-depth="2"] { background: #f8fafc; }
.goal-set-targets table.st-tree tbody tr[data-depth="3"] { background: #f7f9fb; }
.goal-set-targets table.st-tree th.col-eff {
    background: #dcfce7; color: #166534;
}
.goal-set-targets table.st-tree td.col-eff {
    background: #f0fdf4;
}
.goal-set-targets table.st-tree td {
    padding: 10px 12px; border-bottom: 1px solid #eef2f7; vertical-align: middle;
}
.goal-set-targets table.st-tree tbody tr:hover { background: #f8fafc; }
.goal-set-targets table.st-tree tbody tr:hover td.col-eff { background: #e8f8ef; }
.goal-set-targets table.st-tree tr.st-root { background: #fff; }
.goal-set-targets table.st-tree tr.st-amber { background: #fffbeb; }
.goal-set-targets table.st-tree tr.is-filtered-out { display: none; }
.goal-set-targets table.st-tree tfoot td {
    background: #ecfdf5; font-weight: 800; border-top: 1px solid #86efac;
    padding: 12px; color: #14532d;
}
.goal-set-targets table.st-tree tfoot td.col-eff { background: #dcfce7; }

.goal-set-targets .st-entity {
    display: inline-flex; align-items: center; gap: 8px; min-width: 0;
}
.goal-set-targets .st-toggle {
    width: 20px; height: 20px; border: 0; background: transparent; color: #94a3b8;
    display: inline-flex; align-items: center; justify-content: center; border-radius: 4px;
    padding: 0; flex-shrink: 0;
}
.goal-set-targets .st-toggle:hover { background: #e2e8f0; color: #0f172a; }
.goal-set-targets .st-toggle.is-leaf { visibility: hidden; }
.goal-set-targets .st-ent-ico {
    width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0;
    display: inline-flex; align-items: center; justify-content: center; font-size: .9rem;
}
.goal-set-targets .st-ent-ico.rd { background: #dcfce7; color: #059669; }
.goal-set-targets .st-ent-ico.cluster { background: #dbeafe; color: #2563eb; }
.goal-set-targets .st-ent-ico.bm { background: #ede9fe; color: #7c3aed; }
.goal-set-targets .st-ent-ico.staff { background: #f1f5f9; color: #475569; }
.goal-set-targets .st-ent-ico.other { background: #ffedd5; color: #c2410c; }
.goal-set-targets .st-ent-name { font-weight: 700; font-size: .86rem; color: #0f172a; line-height: 1.25; }
.goal-set-targets .st-ent-meta { font-size: .7rem; color: #94a3b8; margin-top: 1px; }

.goal-set-targets .st-type {
    display: inline-flex; align-items: center; height: 22px; padding: 0 9px;
    border-radius: 999px; font-size: .68rem; font-weight: 750; white-space: nowrap;
}
.goal-set-targets .st-type.rd { background: #dcfce7; color: #059669; }
.goal-set-targets .st-type.cluster { background: #dbeafe; color: #2563eb; }
.goal-set-targets .st-type.bm { background: #ede9fe; color: #7c3aed; }
.goal-set-targets .st-type.staff { background: #f1f5f9; color: #475569; }
.goal-set-targets .st-type.other { background: #ffedd5; color: #c2410c; }

.goal-set-targets .st-cell-num {
    font-variant-numeric: tabular-nums; color: #475569; font-weight: 600;
    display: inline-block; min-width: 4.5rem; text-align: right;
}
.goal-set-targets .st-eff {
    font-variant-numeric: tabular-nums; font-weight: 800; color: #0f172a; font-size: .88rem;
    display: inline-block; min-width: 4.5rem; text-align: right;
}
.goal-set-targets table.st-tree td.num {
    text-align: right;
    vertical-align: middle;
}
.goal-set-targets table.st-tree td.num .st-input-wrap {
    margin-left: auto;
}
.goal-set-targets table.st-tree tfoot td.num {
    text-align: right;
    font-variant-numeric: tabular-nums;
}

/* Single clean currency field — kill global input chrome (double box) */
.goal-set-targets .st-input-wrap {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-sizing: border-box;
    height: 34px;
    width: 118px;
    max-width: 100%;
    padding: 0 10px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    box-shadow: none;
    overflow: hidden;
    vertical-align: middle;
}
.goal-set-targets .st-input-wrap:focus-within {
    border-color: #059669;
    box-shadow: 0 0 0 2px rgba(5, 150, 105, .15);
}
.goal-set-targets .st-input-wrap .cur {
    flex: 0 0 auto;
    color: #94a3b8;
    font-size: .78rem;
    font-weight: 700;
    line-height: 1;
    user-select: none;
}
.goal-set-targets .st-input-wrap input.st-input-num,
.goal-set-targets input.st-input-num {
    flex: 1 1 auto;
    width: 100% !important;
    min-width: 0 !important;
    max-width: none !important;
    height: 100% !important;
    min-height: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    border: 0 !important;
    border-radius: 0 !important;
    outline: 0 !important;
    box-shadow: none !important;
    background: transparent !important;
    background-color: transparent !important;
    color: #0f172a !important;
    font-size: .82rem !important;
    font-weight: 650 !important;
    line-height: 1.2 !important;
    text-align: right !important;
    font-variant-numeric: tabular-nums;
    -webkit-appearance: none !important;
    -moz-appearance: textfield !important;
    appearance: textfield !important;
}
.goal-set-targets .st-input-num::-webkit-outer-spin-button,
.goal-set-targets .st-input-num::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.goal-set-targets .st-input-wrap.is-disabled {
    background: #f8fafc;
    border-color: #e2e8f0;
}
.goal-set-targets .st-input-wrap.is-disabled input.st-input-num,
.goal-set-targets input.st-input-num:disabled {
    color: #94a3b8 !important;
    cursor: not-allowed;
}
.goal-set-targets .st-input-wrap input.st-input-num:focus {
    border: 0 !important;
    box-shadow: none !important;
    outline: 0 !important;
}

.goal-set-targets .st-row-actions {
    display: inline-flex; align-items: center; gap: 6px; justify-content: flex-end;
}
.goal-set-targets .st-btn-assign {
    height: 30px; min-height: 30px; padding: 0 10px; border-radius: 6px;
    border: 1px solid #86efac; background: #fff; color: #059669;
    font-size: .72rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;
    cursor: pointer;
}
.goal-set-targets .st-btn-assign:hover { background: #ecfdf5; }
.goal-set-targets .st-btn-assign:disabled { opacity: .4; cursor: not-allowed; }
.goal-set-targets .st-status-ico { display: none; }
.goal-set-targets .st-action-dd { position: relative; display: inline-flex; }
.goal-set-targets .st-action-menu {
    position: absolute; right: 0; top: calc(100% + 4px);
    min-width: 180px; margin: 0; padding: 6px; list-style: none;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 8px;
    box-shadow: 0 12px 28px rgba(15,23,42,.14); z-index: 40;
    display: none;
}
.goal-set-targets .st-action-dd.is-open .st-action-menu { display: block; }
.goal-set-targets .st-action-menu button {
    display: flex; align-items: center; gap: 8px; width: 100%;
    border: 0; background: transparent; text-align: left;
    padding: 8px 10px; border-radius: 6px; font-size: .75rem; font-weight: 650;
    color: #334155; cursor: pointer;
}
.goal-set-targets .st-action-menu button:hover { background: #ecfdf5; color: #059669; }
.goal-set-targets .st-action-menu button:disabled { opacity: .45; cursor: not-allowed; }
.goal-set-targets .st-action-menu button.danger:hover { background: #fef2f2; color: #b91c1c; }

.goal-set-targets details.st-more { border-top: 1px solid #eef2f7; }
.goal-set-targets details.st-more > summary {
    cursor: pointer; list-style: none; padding: 10px 14px;
    font-weight: 650; font-size: .8rem; color: #64748b;
    display: flex; align-items: center; gap: 6px;
}
.goal-set-targets details.st-more > summary::-webkit-details-marker { display: none; }
.goal-set-targets details.st-more .st-more-body { padding: 0 14px 12px; }
.goal-set-targets .st-audit {
    max-height: 160px; overflow: auto; font-size: .78rem;
    border: 1px solid var(--st-border); border-radius: 8px;
}
.goal-set-targets .st-audit table { width: 100%; border-collapse: collapse; }
.goal-set-targets .st-audit th, .goal-set-targets .st-audit td {
    padding: 7px 10px; border-bottom: 1px solid #f1f5f9; text-align: left;
}
.goal-set-targets .st-audit th {
    background: #f8fafc; font-size: .66rem; text-transform: uppercase; color: var(--st-muted);
}

/* Footer */
.goal-set-targets .st-footer {
    position: fixed; left: 270px; right: 0; bottom: 50px; z-index: 1046;
    background: rgba(255,255,255,.97); backdrop-filter: blur(8px);
    border-top: 1px solid var(--st-border);
    box-shadow: 0 -6px 20px rgba(15,23,42,.08);
    padding: 10px 18px;
}
body.sidebar-collapsed .goal-set-targets .st-footer { left: 76px; }
@media (max-width: 1023.98px) { .goal-set-targets .st-footer { left: 0; } }
.goal-set-targets .st-footer-inner {
    display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between;
    max-width: 1400px; margin: 0 auto;
}
.goal-set-targets .st-tip {
    display: inline-flex; align-items: flex-start; gap: 8px;
    background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;
    border-radius: 10px; padding: 8px 12px; font-size: .78rem; line-height: 1.35;
    max-width: 560px;
}
.goal-set-targets .st-tip i { margin-top: 1px; flex-shrink: 0; }
.goal-set-targets #stCheckSummary { font-size: .8rem; font-weight: 700; margin-top: 4px; }
.goal-set-targets #stCheckSummary.ok { color: #15803d; }
.goal-set-targets #stCheckSummary.warn { color: #c2410c; }
.goal-set-targets .st-footer-actions { display: flex; gap: 8px; flex-shrink: 0; }
.goal-set-targets .st-footer-actions .st-btn { min-width: 130px; height: 40px; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid goal-set-targets p-4">
    <div class="st-hero">
        <div>
            <ul class="st-crumbs" aria-label="Breadcrumb">
                <li><a href="<?php echo e(route('goal.targets.index')); ?>">Targets</a></li>
                <li class="sep" aria-hidden="true">›</li>
                <li>Set Targets</li>
            </ul>
            <div class="st-title-row">
                <span class="st-title-ico" aria-hidden="true"><i class="bi bi-bullseye"></i></span>
                <div>
                    <h1 class="st-title">Set Targets</h1>
                    <p class="st-sub">Define revenue and disbursement targets for regions, clusters, branches and employees.</p>
                </div>
            </div>
        </div>
        <div class="st-hero-actions">
            <a href="<?php echo e(route('goal.targets.index')); ?>" class="st-btn"><i class="bi bi-info-circle"></i> Target Policy</a>
            <button type="button" class="st-btn" disabled title="Coming soon"><i class="bi bi-upload"></i> Import (Excel)</button>
            <a href="<?php echo e(route('goal.targets.export', request()->only('period', 'metric'))); ?>" class="st-btn"><i class="bi bi-download"></i> Export <i class="bi bi-chevron-down" style="font-size:.7rem;opacity:.7"></i></a>
            <a href="#stHistory" class="st-btn st-btn-history" id="stHistoryLink"><i class="bi bi-clock-history"></i> Target History</a>
        </div>
    </div>

    <div id="stFlash" class="st-alert info d-none" role="status"></div>

    <?php if($receives): ?>
        <input type="hidden" id="stAreaTotal" value="<?php echo e(number_format((float) ($p['self']['remainder'] ?? 0), 2, '.', '')); ?>">
        <input type="hidden" id="stLastMonth" value="<?php echo e(number_format((float) ($p['self']['last_month'] ?? 0), 2)); ?>">
        <input type="hidden" id="stMyTarget" value="<?php echo e(number_format((float) ($p['self']['set_target'] ?? 0), 2)); ?>">
        <input type="hidden" id="stMyOwner" value="<?php echo e(number_format((float) ($p['self']['owner_target'] ?? 0), 2)); ?>">
        <input type="hidden" id="stLeftDistribute" value="<?php echo e(number_format((float) ($p['self']['left_to_distribute'] ?? $p['self']['remainder'] ?? 0), 2)); ?>">
        <div class="st-mine">
            <span class="st-pill"><i class="bi bi-bullseye"></i> Your target <strong id="stPillTarget"><?php echo e(number_format((float) ($p['self']['set_target'] ?? 0), 0)); ?></strong></span>
            <span class="st-pill"><i class="bi bi-person"></i> Personal <strong id="stPillOwner"><?php echo e(number_format((float) ($p['self']['owner_target'] ?? 0), 0)); ?></strong></span>
            <span class="st-pill left"><i class="bi bi-share"></i> Left to give <strong id="stPillLeft"><?php echo e(number_format((float) ($p['self']['left_to_distribute'] ?? $p['self']['remainder'] ?? 0), 0)); ?></strong></span>
        </div>
    <?php else: ?>
        <input type="hidden" id="stAreaTotal" value="0">
        <input type="hidden" id="stLastMonth" value="0">
    <?php endif; ?>

    <div class="st-kpi-grid">
        <div class="st-kpi tone-green">
            <span class="st-kpi-ico"><i class="bi bi-currency-rupee"></i></span>
            <div class="st-kpi-body">
                <div class="st-kpi-top">
                    <p class="st-kpi-label" id="stKpiAreaLabel">Total Revenue Target</p>
                    <span class="st-kpi-badge is-empty" id="stKpiChange">—</span>
                </div>
                <div class="st-kpi-value" id="stKpiArea">₹ 0</div>
                <p class="st-kpi-meta" id="stKpiAreaMeta">vs. previous period ₹ 0</p>
            </div>
        </div>
        <div class="st-kpi tone-sky">
            <span class="st-kpi-ico"><i class="bi bi-layers"></i></span>
            <div class="st-kpi-body">
                <div class="st-kpi-top">
                    <p class="st-kpi-label" id="stKpiLastLabel">Total Disbursement Target</p>
                    <span class="st-kpi-badge is-empty" id="stKpiLastBadge">—</span>
                </div>
                <div class="st-kpi-value" id="stKpiLast">₹ 0</div>
                <p class="st-kpi-meta" id="stKpiLastMeta">vs. previous period ₹ 0</p>
            </div>
        </div>
        <div class="st-kpi tone-violet">
            <span class="st-kpi-ico"><i class="bi bi-people"></i></span>
            <div class="st-kpi-body">
                <div class="st-kpi-top">
                    <p class="st-kpi-label">Teams / Employees</p>
                </div>
                <div class="st-kpi-value" id="stKpiCount">0</div>
                <p class="st-kpi-meta" id="stKpiBreakdown">—</p>
            </div>
        </div>
        <div class="st-kpi tone-amber">
            <span class="st-kpi-ico"><i class="bi bi-clock-history"></i></span>
            <div class="st-kpi-body">
                <div class="st-kpi-top">
                    <p class="st-kpi-label" id="stKpiLeftLabel">Opening Shortfall (Revenue)</p>
                </div>
                <div class="st-kpi-value" id="stKpiRemaining">₹ 0</div>
                <p class="st-kpi-meta" id="stKpiLeftMeta">From previous period</p>
            </div>
        </div>
    </div>
    <span id="stKpiAssigned" class="d-none">0</span>

    <div class="st-filters" role="toolbar" aria-label="Target filters">
        <div class="st-field">
            <label id="stPeriodLabelFor">Target Period <span class="req" aria-hidden="true">*</span></label>
            <div class="st-dd" id="stPeriodDd">
                <button type="button" class="st-dd-btn" id="stPeriodBtn"
                    aria-labelledby="stPeriodLabelFor" aria-haspopup="listbox" aria-expanded="false" aria-controls="stPeriodMenu">
                    <i class="bi bi-calendar3 st-dd-ico" aria-hidden="true"></i>
                    <span class="st-dd-label" id="stPeriodLabel">
                        <?php
                            $curPeriodLabel = collect($periodOptions)->firstWhere('value', $p['period'] ?? '')['label']
                                ?? ($periodOptions[0]['label'] ?? 'Select');
                        ?>
                        <?php echo e($curPeriodLabel); ?>

                    </span>
                    <i class="bi bi-chevron-down st-dd-caret" aria-hidden="true"></i>
                </button>
                <ul class="st-dd-menu" id="stPeriodMenu" role="listbox" aria-labelledby="stPeriodLabelFor" tabindex="-1">
                    <?php $__currentLoopData = $periodOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li role="option"
                            data-value="<?php echo e($opt['value']); ?>"
                            class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-selected' => ($p['period'] ?? '') === $opt['value']]); ?>"
                            aria-selected="<?php echo e(($p['period'] ?? '') === $opt['value'] ? 'true' : 'false'); ?>"><?php echo e($opt['label']); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
                <select id="stPeriod" class="d-none no-select2" data-select2-skip="1" tabindex="-1" aria-hidden="true" required>
                    <?php $__currentLoopData = $periodOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($opt['value']); ?>" <?php if(($p['period'] ?? '') === $opt['value']): echo 'selected'; endif; ?>><?php echo e($opt['label']); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
        </div>

        <div class="st-field">
            <label id="stMetricLabelFor">Target For <span class="req" aria-hidden="true">*</span></label>
            <div class="st-dd is-metric" id="stMetricDd">
                <button type="button" class="st-dd-btn" id="stMetricBtn"
                    aria-labelledby="stMetricLabelFor" aria-haspopup="listbox" aria-expanded="false" aria-controls="stMetricMenu">
                    <span class="st-dd-label" id="stMetricLabel">
                        <?php echo e($metrics[$p['metric'] ?? ''] ?? (\Illuminate\Support\Arr::first($metrics) ?? 'Select')); ?>

                    </span>
                    <i class="bi bi-chevron-down st-dd-caret" aria-hidden="true"></i>
                </button>
                <ul class="st-dd-menu" id="stMetricMenu" role="listbox" aria-labelledby="stMetricLabelFor" tabindex="-1">
                    <?php $__currentLoopData = $metrics; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li role="option"
                            data-value="<?php echo e($key); ?>"
                            class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-selected' => ($p['metric'] ?? '') === $key]); ?>"
                            aria-selected="<?php echo e(($p['metric'] ?? '') === $key ? 'true' : 'false'); ?>"><?php echo e($label); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
                <select id="stMetric" class="d-none no-select2" data-select2-skip="1" tabindex="-1" aria-hidden="true" required>
                    <?php $__currentLoopData = $metrics; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($key); ?>" <?php if(($p['metric'] ?? '') === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
        </div>

        <div class="st-field">
            <label>
                Apply To
                <i class="bi bi-info-circle st-info" title="Filter which level is shown in the table" aria-hidden="true"></i>
            </label>
            <div class="st-seg" id="stApplyTo" role="group" aria-label="Apply to">
                <button type="button" data-apply="rd" class="is-active">Region</button>
                <button type="button" data-apply="cluster">Cluster</button>
                <button type="button" data-apply="bm">Branch</button>
                <button type="button" data-apply="staff">Employee</button>
            </div>
        </div>

        <div class="st-toggle-wrap">
            <label class="st-switch" title="Show hierarchy">
                <input type="checkbox" id="stShowHierarchy" checked>
                <span></span>
            </label>
            <span>Show Hierarchy</span>
        </div>

        <div class="st-field st-search">
            <label for="stSearch">&nbsp;</label>
            <div class="st-search-box">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="stSearch" class="st-control" placeholder="Search by name, code...">
            </div>
        </div>

        <input type="hidden" id="stLevelFilter" value="all">
        <button type="button" class="d-none" id="stExpandAll" tabindex="-1" aria-hidden="true"></button>
        <button type="button" class="d-none" id="stCollapseAll" tabindex="-1" aria-hidden="true"></button>
        <button type="button" class="d-none" id="stSaveDraftTop" tabindex="-1" aria-hidden="true"></button>
        <button type="button" class="d-none" id="stAssignTop" tabindex="-1" aria-hidden="true"></button>
    </div>

    <section class="st-panel">
        <div class="st-table-wrap">
            <table class="st-tree" id="stTreeTable">
                <thead>
                    <tr>
                        <th class="idx">#</th>
                        <th style="min-width:260px">Entity / Person (Hierarchy)</th>
                        <th>Type</th>
                        <th class="num">Opening Shortfall (<?php echo e($unit); ?>) <i class="bi bi-info-circle th-info" title="Carried from previous period closing shortfall — not this month’s Base Target"></i></th>
                        <th class="num">Base Target (<?php echo e($unit); ?>)</th>
                        <th class="num">Adjustments (<?php echo e($unit); ?>) <i class="bi bi-info-circle th-info" title="Personal / adjustment amount"></i></th>
                        <th class="num col-eff">Effective Target (<?php echo e($unit); ?>) <i class="bi bi-info-circle th-info" title="Opening Shortfall + Base Target + Adjustments"></i></th>
                        <th class="num">Actions</th>
                    </tr>
                </thead>
                <tbody id="stTreeBody"></tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><strong>Total</strong></td>
                        <td class="num" id="stFootLast">0</td>
                        <td class="num" id="stFootTarget">0</td>
                        <td class="num" id="stFootPersonal">0</td>
                        <td class="num col-eff" id="stFootTeam">0</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <details class="st-more" id="stHistory">
            <summary><i class="bi bi-clock-history"></i> Target History</summary>
            <div class="st-more-body">
                <div class="st-audit">
                    <table>
                        <thead>
                            <tr>
                                <th>When</th>
                                <th>Who</th>
                                <th>By</th>
                                <th>Action</th>
                                <th>Old</th>
                                <th>New</th>
                            </tr>
                        </thead>
                        <tbody id="stAuditBody">
                            <tr><td colspan="6" style="color:#94a3b8">No changes yet.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </details>
    </section>

    <div class="st-footer" role="region" aria-label="Save and assign">
        <div class="st-footer-inner">
            <div>
                <div class="st-tip">
                    <i class="bi bi-info-circle-fill"></i>
                    <span><strong>Opening Shortfall</strong> is carried from last month. Enter <strong>Base Target</strong> for this period, then Assign.</span>
                </div>
                <div id="stCheckSummary" class="ok">Ready</div>
            </div>
            <div class="st-footer-actions">
                <button type="button" class="st-btn" id="stSaveDraft"><i class="bi bi-floppy"></i> Save as Draft</button>
                <button type="button" class="st-btn st-btn-primary" id="stAssign" disabled><i class="bi bi-send"></i> Assign &amp; Notify</button>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const routes = {
        tree: <?php echo json_encode(route('goal.targets.tree'), 15, 512) ?>,
        saveDraft: <?php echo json_encode(route('goal.targets.save-draft'), 15, 512) ?>,
        assign: <?php echo json_encode(route('goal.targets.assign'), 15, 512) ?>,
        audit: <?php echo json_encode(route('goal.targets.audit-log'), 15, 512) ?>,
    };
    const jumpPct = <?php echo e($jumpPct); ?>;
    const unit = <?php echo json_encode($unit, 15, 512) ?>;
    let state = <?php echo json_encode($p, 15, 512) ?>;
    let collapsed = new Set();
    let searchQ = '';
    let levelFilter = 'all';
    let applyFilter = 'rd';
    let showHierarchy = true;

    const $ = (id) => document.getElementById(id);
    const flash = (msg, type = 'info') => {
        const el = $('stFlash');
        el.className = 'st-alert ' + type;
        el.textContent = msg;
        el.classList.remove('d-none');
        if (type !== 'err') setTimeout(() => el.classList.add('d-none'), 4500);
    };
    const fmt = (n) => {
        const v = Number(n || 0);
        return v.toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    };
    const money = (n) => '₹ ' + fmt(n);
    const remainderOf = (n) => Math.max(0, Number(n.set_target || 0) - Number(n.owner_target || 0));
    const receivesTarget = () => !!(state.self && state.self.receives_target);

    async function api(url, opts = {}) {
        const res = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            ...opts,
        });
        const json = await res.json().catch(() => ({}));
        if (!res.ok) {
            const msg = json.message
                || (json.errors && Object.values(json.errors).flat().join(' '))
                || 'Request failed.';
            throw new Error(msg);
        }
        return json;
    }

    function collectRows(nodes, out = []) {
        (nodes || []).forEach((n) => {
            if (n.editable) {
                out.push({
                    employee_id: n.id,
                    set_target: Number(n.set_target || 0),
                    owner_target: Number(n.owner_target || 0),
                    row_source: n.row_source === 'edited' ? 'edited' : 'auto',
                });
            }
            if (n.children?.length) collectRows(n.children, out);
        });
        return out;
    }

    function countNodes(nodes) {
        let c = 0;
        (nodes || []).forEach((n) => {
            c += 1;
            if (n.children?.length) c += countNodes(n.children);
        });
        return c;
    }

    function sumTree(nodes, key) {
        let s = 0;
        (nodes || []).forEach((n) => {
            s += Number(n[key] || 0);
            if (n.children?.length) s += sumTree(n.children, key);
        });
        return s;
    }

    function sumDirectAssigned(nodes) {
        return (nodes || []).reduce((s, n) => s + Number(n.set_target || 0), 0);
    }

    function countByRole(nodes, out = { rd: 0, cluster: 0, bm: 0, staff: 0 }) {
        (nodes || []).forEach((n) => {
            const rm = roleMeta(n);
            if (rm.cls === 'rd') out.rd += 1;
            else if (rm.cls === 'cluster') out.cluster += 1;
            else if (rm.cls === 'bm') out.bm += 1;
            else out.staff += 1;
            if (n.children?.length) countByRole(n.children, out);
        });
        return out;
    }

    function childCounts(n) {
        const kids = n.children || [];
        if (!kids.length) return '';
        const by = countByRole(kids);
        const parts = [];
        if (by.cluster) parts.push(by.cluster + ' Cluster' + (by.cluster > 1 ? 's' : ''));
        if (by.bm) parts.push(by.bm + ' Branch' + (by.bm > 1 ? 'es' : ''));
        if (by.staff && !by.cluster && !by.bm) parts.push(by.staff + ' Employee' + (by.staff > 1 ? 's' : ''));
        else if (by.staff && (by.cluster || by.bm)) parts.push(by.staff + ' Emp');
        if (by.rd) parts.push(by.rd + ' Region' + (by.rd > 1 ? 's' : ''));
        return parts.join(' · ') || (kids.length + ' reports');
    }

    function recompute(nodes) {
        (nodes || []).forEach((n) => {
            const hasKids = !!(n.children && n.children.length);
            n.is_staff = !hasKids;
            if (hasKids) {
                recompute(n.children);
                const teamRollup = n.children.reduce((s, c) => s + Number(c.set_target || 0), 0);
                const owner = Number(n.owner_target || 0);
                const set = Number(n.set_target || 0);
                const rem = Math.max(0, set - owner);
                n.remainder = rem;
                n.total_target = teamRollup;
                n.over_allocated = (teamRollup - rem) > 0.05;
                n.pending_distribution = !n.over_allocated && Math.abs(teamRollup - rem) > 0.05;
                n.check_ok = Math.abs(teamRollup - rem) <= 0.05;
                n.check_label = n.over_allocated ? 'Over limit' : (n.pending_distribution ? 'For them later' : 'Fully set');
            } else {
                n.owner_target = Number(n.set_target || 0);
                n.remainder = 0;
                n.total_target = null;
                n.check_ok = true;
                n.over_allocated = false;
                n.pending_distribution = false;
                n.check_label = null;
            }
            const last = Number(n.last_month || 0);
            const set = Number(n.set_target || 0);
            n.amber = last > 0 && set > last * (1 + jumpPct / 100);
            n.amber_pct = (n.amber && last > 0) ? Math.round(((set - last) / last) * 100) : null;
        });
    }

    function collectChecks(nodes, out = []) {
        (nodes || []).forEach((n) => {
            if (n.children?.length) {
                out.push({
                    id: n.id,
                    label: n.check_label || n.name,
                    ok: !!n.check_ok,
                    over: !!n.over_allocated,
                    pending: !!n.pending_distribution,
                });
                collectChecks(n.children, out);
            }
        });
        return out;
    }

    function roleMeta(n) {
        const role = String(n.role || '').toLowerCase();
        const job = String(n.job_role || '').toLowerCase().replace(/[\s_-]+/g, '');
        if (role.includes('regional') || job === 'rd' || job === 'regionaldirector') {
            return { cls: 'rd', short: 'Region', label: 'Region', icon: 'bi-building' };
        }
        if (role.includes('cluster') || job === 'cluster' || job === 'clusterhead' || job === 'cd') {
            return { cls: 'cluster', short: 'Cluster', label: 'Cluster', icon: 'bi-diagram-3' };
        }
        if (role.includes('branch') || job === 'bm' || job === 'branchmanager') {
            return { cls: 'bm', short: 'Branch', label: 'Branch', icon: 'bi-bank' };
        }
        if (!n.children?.length) return { cls: 'staff', short: 'Employee', label: 'Employee', icon: 'bi-person' };
        return { cls: 'other', short: (n.role || 'Role').slice(0, 10), label: n.role || 'Role', icon: 'bi-person-badge' };
    }

    function statusIcon(n) {
        if (n.over_allocated) return { cls: 'bad', icon: 'bi-exclamation-triangle-fill', title: 'Over limit' };
        if (n.pending_distribution) return { cls: 'pending', icon: 'bi-hourglass-split', title: 'Pending below' };
        if (n.children?.length && n.check_ok && Number(n.set_target || 0) > 0) return { cls: 'ok', icon: 'bi-check-lg', title: 'OK' };
        if ((n.chip || '') === 'Edited' || n.row_source === 'edited') return { cls: 'edit', icon: 'bi-pencil', title: 'Edited' };
        return { cls: 'draft', icon: 'bi-circle', title: 'Draft' };
    }

    function matchesSearch(n) {
        if (!searchQ) return true;
        const hay = `${n.name || ''} ${n.area || ''} ${n.role || ''} ${n.job_role || ''}`.toLowerCase();
        return hay.includes(searchQ);
    }

    function hasSearchHit(n) {
        if (matchesSearch(n)) return true;
        return (n.children || []).some((c) => hasSearchHit(c));
    }

    function matchesFilter(n) {
        // Search only — Apply To is handled by getDisplayRoots()
        if (!searchQ) return true;
        return matchesSearch(n) || hasSearchHit(n);
    }

    function collectByRole(nodes, role, out = []) {
        (nodes || []).forEach((n) => {
            if (roleMeta(n).cls === role) out.push(n);
            else if (n.children?.length) collectByRole(n.children, role, out);
        });
        return out;
    }

    /** Roots for current Apply To + hierarchy mode (mockup behavior). */
    function getDisplayRoots() {
        const tree = state.tree || [];
        if (applyFilter === 'all') return tree;
        if (applyFilter === 'rd') {
            const rds = tree.filter((n) => roleMeta(n).cls === 'rd');
            return rds.length ? rds : tree;
        }
        // Cluster / Branch / Employee → lift matching nodes as roots
        return collectByRole(tree, applyFilter);
    }

    function inputHtml(n, cls, value, disabled, label) {
        const raw = Number(value || 0);
        // Keep plain number in the field (no commas) so typing stays stable
        const shown = Number.isFinite(raw) ? String(raw) : '0';
        return `<span class="st-input-wrap ${disabled ? 'is-disabled' : ''}">
            <span class="cur" aria-hidden="true">₹</span>
            <input type="text" class="st-input-num ${cls}" inputmode="decimal" autocomplete="off"
                data-id="${n.id}" value="${shown}"
                ${disabled ? 'disabled' : ''} aria-label="${label}">
        </span>`;
    }

    /** Effective = Opening Shortfall + Base Target + Adjustments */
    function effectiveOf(n) {
        const open = Number(n.last_month || 0);
        const base = Number(n.set_target || 0);
        const hasKids = !!(n.children && n.children.length);
        const adj = hasKids ? Number(n.owner_target || 0) : 0;
        return Math.round((open + base + adj) * 100) / 100;
    }

    function entityTitle(n) {
        const name = n.name || '';
        const area = n.area && n.area !== n.name ? n.area : '';
        if (area && !name.includes(area)) return `${name}`;
        return name;
    }

    function renderRows(nodes, depth = 0, parentCollapsed = false, rows = [], pathPrefix = '') {
        (nodes || []).forEach((n, i) => {
            n.depth = depth;
            const hasKids = !!(n.children && n.children.length);
            const isCollapsed = showHierarchy ? collapsed.has(n.id) : false;
            const hidden = parentCollapsed;
            const num = pathPrefix ? `${pathPrefix}.${i + 1}` : String(i + 1);
            if (!hidden) {
                const pad = showHierarchy ? depth * 22 : 0;
                const editable = !!n.editable;
                const locked = ['Approved', 'Locked'].includes(n.status);
                const isStaff = !hasKids;
                const eff = effectiveOf(n);
                const filteredOut = !matchesFilter(n);
                const rm = roleMeta(n);
                const meta = childCounts(n) || (n.area && n.area !== n.name ? n.area : (n.role || ''));
                const canEditAdj = editable && !locked && !isStaff;
                rows.push(`
                    <tr class="${depth === 0 ? 'st-root' : ''} ${n.amber ? 'st-amber' : ''} ${filteredOut ? 'is-filtered-out' : ''}" data-id="${n.id}" data-depth="${depth}" data-role="${rm.cls}">
                        <td class="idx">${num}</td>
                        <td>
                            <div class="st-entity" style="padding-left:${pad}px">
                                <button type="button" class="st-toggle ${hasKids && showHierarchy ? '' : 'is-leaf'}" data-toggle="${n.id}" aria-label="${isCollapsed ? 'Expand' : 'Collapse'}">
                                    <i class="bi ${isCollapsed ? 'bi-chevron-right' : 'bi-chevron-down'}"></i>
                                </button>
                                <span class="st-ent-ico ${rm.cls}"><i class="bi ${rm.icon}"></i></span>
                                <div style="min-width:0">
                                    <div class="st-ent-name">${escapeHtml(entityTitle(n))}</div>
                                    <div class="st-ent-meta">${escapeHtml(meta)}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="st-type ${rm.cls}">${escapeHtml(rm.label)}</span></td>
                        <td class="num st-cell-num">${fmt(n.last_month)}</td>
                        <td class="num">
                            ${inputHtml(n, 'st-set', n.set_target, !(editable && !locked), 'Base target for ' + escapeHtml(n.name))}
                        </td>
                        <td class="num">
                            ${isStaff
                                ? `<span class="st-cell-num" data-owner-for="${n.id}">${fmt(0)}</span>`
                                : inputHtml(n, 'st-owner', n.owner_target, !canEditAdj, 'Adjustments for ' + escapeHtml(n.name))}
                        </td>
                        <td class="num col-eff">
                            <span class="st-eff" data-total-for="${n.id}">
                                <span class="st-rem-val">${fmt(eff)}</span>
                                <span class="st-rem-hint d-none" data-check-label="${n.id}"></span>
                            </span>
                        </td>
                        <td class="num">
                            <div class="st-row-actions">
                                <div class="st-action-dd" data-row-dd="${n.id}">
                                    <button type="button" class="st-btn-assign" data-assign-toggle="${n.id}" ${editable && !locked ? '' : 'disabled'} title="Assign options">
                                        Assign <i class="bi bi-chevron-down"></i>
                                    </button>
                                    <ul class="st-action-menu" role="menu">
                                        <li><button type="button" data-row-act="assign" data-id="${n.id}" ${editable && !locked ? '' : 'disabled'}><i class="bi bi-send"></i> Assign &amp; notify</button></li>
                                        <li><button type="button" data-row-act="draft" data-id="${n.id}" ${editable && !locked ? '' : 'disabled'}><i class="bi bi-floppy"></i> Save draft</button></li>
                                        <li><button type="button" data-row-act="copy-open" data-id="${n.id}" ${editable && !locked ? '' : 'disabled'}><i class="bi bi-arrow-down-left-square"></i> Use opening as base</button></li>
                                    </ul>
                                </div>
                                <div class="st-action-dd" data-more-dd="${n.id}">
                                    <button type="button" class="st-btn st-btn-sm st-btn-icon" data-more-toggle="${n.id}" title="More" ${editable && !locked ? '' : 'disabled'}><i class="bi bi-three-dots-vertical"></i></button>
                                    <ul class="st-action-menu" role="menu">
                                        <li><button type="button" data-row-act="clear" data-id="${n.id}" class="danger" ${editable && !locked ? '' : 'disabled'}><i class="bi bi-eraser"></i> Clear base &amp; adj</button></li>
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                `);
            }
            if (hasKids && showHierarchy) {
                renderRows(n.children, depth + 1, hidden || isCollapsed, rows, num);
            }
        });
        return rows;
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[c]);
    }

    function findNode(nodes, id) {
        for (const n of nodes || []) {
            if (n.id === id) return n;
            const hit = findNode(n.children || [], id);
            if (hit) return hit;
        }
        return null;
    }

    function findParentNode(nodes, id) {
        for (const n of nodes || []) {
            if ((n.children || []).some((c) => c.id === id)) return n;
            const deeper = findParentNode(n.children || [], id);
            if (deeper) return deeper;
        }
        return null;
    }

    function maxAllowedTarget(nodeId) {
        const parent = findParentNode(state.tree, nodeId);
        let rem;
        let siblings;
        if (parent) {
            rem = Math.max(0, Number(parent.set_target || 0) - Number(parent.owner_target || 0));
            siblings = parent.children || [];
        } else if (receivesTarget() && state.self) {
            rem = Math.max(0, Number(state.self.set_target || 0) - Number(state.self.owner_target || 0));
            siblings = state.tree || [];
        } else {
            return Number.POSITIVE_INFINITY;
        }
        const others = siblings
            .filter((c) => c.id !== nodeId)
            .reduce((s, c) => s + Number(c.set_target || 0), 0);
        return Math.max(0, Math.round((rem - others) * 100) / 100);
    }

    function maxAllowedOwner(node) {
        const set = Number(node.set_target || 0);
        const kids = (node.children || []).reduce((s, c) => s + Number(c.set_target || 0), 0);
        return Math.max(0, Math.round((set - kids) * 100) / 100);
    }

    function syncSelfRemainder() {
        if (!receivesTarget() || !state.self) return;
        const mySet = Number(state.self.set_target || 0);
        const myOwner = Number(state.self.owner_target || 0);
        const rem = Math.max(0, mySet - myOwner);
        const distributed = sumDirectAssigned(state.tree || []);
        const left = Math.round((rem - distributed) * 100) / 100;
        state.self.remainder = rem;
        state.self.distributed = distributed;
        state.self.left_to_distribute = left;
        const leftEl = $('stLeftDistribute');
        if (leftEl) leftEl.value = fmt(left);
        const pool = $('stAreaTotal');
        if (pool && pool.type === 'hidden') pool.value = String(rem);
        if ($('stPillTarget')) $('stPillTarget').textContent = fmt(mySet);
        if ($('stPillOwner')) $('stPillOwner').textContent = fmt(myOwner);
        if ($('stPillLeft')) $('stPillLeft').textContent = fmt(left);
    }

    function updateFooters() {
        // Sum Apply To roots only (avoid double-counting cascade children)
        let open = 0, base = 0, adj = 0, eff = 0;
        (getDisplayRoots() || []).forEach((n) => {
            open += Number(n.last_month || 0);
            base += Number(n.set_target || 0);
            adj += (n.children?.length) ? Number(n.owner_target || 0) : 0;
            eff += effectiveOf(n);
        });
        if ($('stFootLast')) $('stFootLast').textContent = fmt(open);
        if ($('stFootTarget')) $('stFootTarget').textContent = fmt(base);
        if ($('stFootPersonal')) $('stFootPersonal').textContent = fmt(adj);
        if ($('stFootTeam')) $('stFootTeam').textContent = fmt(eff);
    }

    function sumDisplayRoots(key) {
        return (getDisplayRoots() || []).reduce((s, n) => s + Number(n[key] || 0), 0);
    }

    function updateKpis() {
        const assigned = sumDirectAssigned(state.tree || []);
        // Top-level (Apply To) only — avoid double-counting cascade children
        const current = sumDisplayRoots('set_target');
        const openingAll = sumDisplayRoots('last_month');
        const count = countNodes(state.tree || []);
        const by = countByRole(state.tree || []);
        const parts = [];
        if (by.rd) parts.push(by.rd + ' Region' + (by.rd > 1 ? 's' : ''));
        if (by.cluster) parts.push(by.cluster + ' Cluster' + (by.cluster > 1 ? 's' : ''));
        if (by.bm) parts.push(by.bm + ' Branch' + (by.bm > 1 ? 'es' : ''));
        if (by.staff) parts.push(by.staff + ' Emp');

        const metricKey = String($('stMetric')?.value || state.metric || 'revenue').toLowerCase();
        const isDisb = metricKey.includes('disbur');
        const revenueVal = isDisb ? 0 : current;
        const revenueLast = isDisb ? 0 : openingAll;
        const disbVal = isDisb ? current : 0;
        const disbLast = isDisb ? openingAll : 0;

        if ($('stKpiArea')) $('stKpiArea').textContent = money(revenueVal);
        if ($('stKpiAssigned')) $('stKpiAssigned').textContent = money(assigned);
        if ($('stKpiAreaLabel')) $('stKpiAreaLabel').textContent = 'Total Revenue Target';
        if ($('stKpiAreaMeta')) $('stKpiAreaMeta').textContent = 'Opening shortfall carry ' + money(revenueLast);

        if ($('stKpiLast')) $('stKpiLast').textContent = money(disbVal);
        if ($('stKpiLastLabel')) $('stKpiLastLabel').textContent = 'Total Disbursement Target';
        if ($('stKpiLastMeta')) $('stKpiLastMeta').textContent = 'Opening shortfall carry ' + money(disbLast);

        // Opening shortfall card = carried shortfall at Apply To roots
        let shortfall = openingAll;
        if (receivesTarget() && state.self) {
            shortfall = Number(state.self.last_month || 0);
        }
        if ($('stKpiRemaining')) $('stKpiRemaining').textContent = money(Math.max(0, shortfall));
        if ($('stKpiLeftLabel')) $('stKpiLeftLabel').textContent = 'Opening Shortfall (Revenue)';
        if ($('stKpiLeftMeta')) $('stKpiLeftMeta').textContent = 'Carried from previous period';

        if ($('stKpiCount')) $('stKpiCount').textContent = String(count);
        if ($('stKpiBreakdown')) {
            $('stKpiBreakdown').textContent = parts.length
                ? '(' + parts.join(' · ') + ')'
                : 'No people in scope';
        }

        const setBadge = (el, last, cur) => {
            if (!el) return;
            if (last > 0 && cur > 0) {
                const pct = Math.round(((cur - last) / last) * 1000) / 10;
                el.textContent = (pct >= 0 ? '+' : '') + pct.toFixed(1) + '%';
                el.classList.toggle('down', pct < 0);
                el.classList.remove('is-empty');
            } else {
                el.textContent = '—';
                el.classList.add('is-empty');
                el.classList.remove('down');
            }
        };
        setBadge($('stKpiChange'), revenueLast, revenueVal);
        setBadge($('stKpiLastBadge'), disbLast, disbVal);

        updateFooters();
    }

    function updateCheckSummary() {
        syncSelfRemainder();
        const nested = collectChecks(state.tree || []);
        const overs = nested.filter((c) => c.over);

        let canAssign = (state.tree || []).length > 0 && overs.length === 0;
        const blocking = [];
        if (receivesTarget() && state.self) {
            const left = Number(state.self.left_to_distribute || 0);
            const ok = Math.abs(left) <= 0.05;
            blocking.push({ id: state.self.id, ok });
            if (!ok) canAssign = false;
        }

        state.checks = blocking;
        state.can_assign = canAssign;

        const sum = $('stCheckSummary');
        const saveBtn = $('stSaveDraft');
        const saveTop = $('stSaveDraftTop');
        const assignBtn = $('stAssign');
        const assignTop = $('stAssignTop');

        if (overs.length) {
            sum.className = 'warn';
            sum.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> Over limit — reduce targets';
        } else if (receivesTarget() && blocking.some((b) => !b.ok)) {
            sum.className = 'warn';
            sum.innerHTML = '<i class="bi bi-share"></i> Finish assigning your remainder to unlock Assign';
        } else if (!(state.tree || []).length) {
            sum.className = 'warn';
            sum.textContent = 'No team below you';
        } else {
            sum.className = 'ok';
            sum.innerHTML = '<i class="bi bi-check-circle-fill"></i> Ready to save or assign';
        }

        const assignDisabled = !state.can_assign;
        if (assignBtn) {
            assignBtn.disabled = assignDisabled;
            assignBtn.classList.toggle('st-btn-primary', !assignDisabled);
        }
        if (assignTop) assignTop.disabled = assignDisabled;
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.classList.toggle('st-btn-primary', assignDisabled);
        }
        if (saveTop) {
            saveTop.disabled = false;
            saveTop.classList.toggle('st-btn-primary', assignDisabled);
        }
        updateKpis();
    }

    function refreshDerived() {
        recompute(state.tree || []);
        const walk = (nodes) => {
            (nodes || []).forEach((n) => {
                const row = document.querySelector(`#stTreeBody tr[data-id="${n.id}"]`);
                if (row) {
                    row.classList.toggle('st-amber', !!n.amber);
                    const totalCell = row.querySelector(`[data-total-for="${n.id}"]`);
                    if (totalCell) {
                        const valEl = totalCell.querySelector('.st-rem-val') || totalCell;
                        if (valEl) valEl.textContent = fmt(effectiveOf(n));
                    }
                    const ownerSpan = row.querySelector(`[data-owner-for="${n.id}"]`);
                    if (ownerSpan) ownerSpan.textContent = fmt(0);
                }
                if (n.children?.length) walk(n.children);
            });
        };
        walk(state.tree || []);
        updateCheckSummary();
    }

    function render() {
        recompute(state.tree || []);
        const roots = getDisplayRoots();
        const rows = renderRows(roots, 0, false, [], '');
        $('stTreeBody').innerHTML = rows.join('') || `
            <tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:36px">No matching people for this filter</td></tr>`;
        if ($('stLastMonth')) $('stLastMonth').value = fmt(state.self?.last_month || 0);
        if ($('stMyTarget')) $('stMyTarget').value = fmt(state.self?.set_target || 0);
        if ($('stMyOwner')) $('stMyOwner').value = fmt(state.self?.owner_target || 0);
        updateCheckSummary();
    }

    async function reloadTree() {
        const periodEl = $('stPeriod');
        const metricEl = $('stMetric');
        const period = periodEl ? periodEl.value : '';
        const metric = metricEl ? metricEl.value : 'revenue';
        if (!period) {
            flash('Choose a target period.', 'warn');
            return;
        }

        const body = $('stTreeBody');
        if (body) {
            body.innerHTML = `<tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:28px">
                <i class="bi bi-arrow-repeat" style="margin-right:6px"></i>Loading ${escapeHtml(period)}…
            </td></tr>`;
        }

        const json = await api(`${routes.tree}?period=${encodeURIComponent(period)}&metric=${encodeURIComponent(metric)}`);
        state = json.data || state;
        state.period = period;
        state.metric = metric;
        collapsed = new Set();
        if (showHierarchy) {
            const collapseDeep = (nodes, depth = 0) => {
                (nodes || []).forEach((n) => {
                    if (n.children?.length) {
                        if (depth >= 1) collapsed.add(n.id);
                        collapseDeep(n.children, depth + 1);
                    }
                });
            };
            collapseDeep(getDisplayRoots(), 0);
        }
        if (state.self?.receives_target) {
            const rem = Math.max(0, Number(state.self.set_target || 0) - Number(state.self.owner_target || 0));
            if ($('stAreaTotal')) $('stAreaTotal').value = rem.toFixed(2);
        }
        // Keep address bar in sync so refresh stays on the chosen period
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('period', period);
            url.searchParams.set('metric', metric);
            window.history.replaceState({}, '', url);
        } catch (e) { /* ignore */ }
        render();
        loadAudit();
    }

    async function loadAudit() {
        const period = $('stPeriod').value;
        const metric = $('stMetric').value;
        try {
            const json = await api(`${routes.audit}?period=${encodeURIComponent(period)}&metric=${encodeURIComponent(metric)}`);
            const rows = json.data || [];
            $('stAuditBody').innerHTML = rows.length
                ? rows.map((r) => `<tr>
                    <td>${escapeHtml(r.at)}</td>
                    <td>${escapeHtml(r.employee)}</td>
                    <td>${escapeHtml(r.changed_by)}</td>
                    <td>${escapeHtml(r.action)}</td>
                    <td>${fmt(r.old_value)}</td>
                    <td>${fmt(r.new_value)}</td>
                </tr>`).join('')
                : '<tr><td colspan="6" style="color:#94a3b8">No entries yet.</td></tr>';
        } catch (e) { /* ignore */ }
    }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-toggle]');
        if (!btn) return;
        const id = Number(btn.getAttribute('data-toggle'));
        if (collapsed.has(id)) collapsed.delete(id);
        else collapsed.add(id);
        render();
    });

    function sanitizeDecimal(el) {
        const start = el.selectionStart;
        const before = el.value;
        let cleaned = before.replace(/[^\d.]/g, '');
        const parts = cleaned.split('.');
        if (parts.length > 2) cleaned = parts[0] + '.' + parts.slice(1).join('');
        if (cleaned !== before) {
            el.value = cleaned;
            if (typeof start === 'number') {
                const pos = Math.min(start, cleaned.length);
                el.setSelectionRange(pos, pos);
            }
        }
        if (cleaned === '' || cleaned === '.') return 0;
        const num = Number(cleaned);
        return Number.isNaN(num) ? null : num;
    }

    let overCapToastAt = 0;
    function notifyCap(msg) {
        const now = Date.now();
        if (now - overCapToastAt < 1200) return;
        overCapToastAt = now;
        flash(msg, 'warn');
    }

    $('stTreeBody').addEventListener('input', (e) => {
        const el = e.target;
        if (!el.classList.contains('st-set') && !el.classList.contains('st-owner')) return;
        const id = Number(el.dataset.id);
        const node = findNode(state.tree, id);
        if (!node || !node.editable) return;
        let num = sanitizeDecimal(el);
        if (num === null) return;

        if (el.classList.contains('st-set')) {
            const max = maxAllowedTarget(id);
            if (Number.isFinite(max) && num > max + 0.0001) {
                num = max;
                el.value = String(max);
                notifyCap(`Max allowed here is ${fmt(max)}.`);
            }
            node.set_target = num;
            if (node.is_staff || !(node.children && node.children.length)) {
                node.owner_target = num;
            } else {
                const maxOwner = maxAllowedOwner(node);
                if (Number(node.owner_target || 0) > maxOwner) {
                    node.owner_target = maxOwner;
                    const ownerInput = document.querySelector(`#stTreeBody input.st-owner[data-id="${id}"]`);
                    if (ownerInput && document.activeElement !== ownerInput) ownerInput.value = String(maxOwner);
                }
            }
        }
        if (el.classList.contains('st-owner')) {
            const set = Number(node.set_target || 0);
            let owner = num;
            if (owner > set) {
                owner = set;
                el.value = String(owner);
                notifyCap('Personal cannot exceed Target.');
            }
            const kids = (node.children || []).reduce((s, c) => s + Number(c.set_target || 0), 0);
            const ownerCap = Math.max(0, Math.round((set - kids) * 100) / 100);
            if (owner > ownerCap + 0.0001) {
                owner = ownerCap;
                el.value = String(owner);
                notifyCap(`Personal max is ${fmt(ownerCap)}.`);
            }
            node.owner_target = owner;
        }
        node.row_source = 'edited';
        node.chip = 'Edited';
        refreshDerived();
    });

    $('stTreeBody').addEventListener('blur', (e) => {
        const el = e.target;
        if (!el.classList.contains('st-set') && !el.classList.contains('st-owner')) return;
        const num = sanitizeDecimal(el);
        if (num === null) return;
        el.value = String(num);
    }, true);

    function expandSearchHits(nodes) {
        (nodes || []).forEach((n) => {
            if (!n.children?.length) return;
            const childHit = (n.children || []).some((c) => hasSearchHit(c));
            if (childHit) collapsed.delete(n.id);
            expandSearchHits(n.children);
        });
    }

    function applyRowVisibility() {
        document.querySelectorAll('#stTreeBody tr[data-id]').forEach((tr) => {
            const id = Number(tr.getAttribute('data-id'));
            const n = findNode(state.tree, id);
            if (!n) return;
            tr.classList.toggle('is-filtered-out', !matchesFilter(n));
        });
    }

    $('stSearch').addEventListener('input', (e) => {
        searchQ = String(e.target.value || '').trim().toLowerCase();
        if (searchQ && showHierarchy) {
            expandSearchHits(getDisplayRoots());
            render();
        } else {
            applyRowVisibility();
        }
    });

    document.getElementById('stApplyTo').addEventListener('click', (e) => {
        const btn = e.target.closest('[data-apply]');
        if (!btn) return;
        applyFilter = btn.getAttribute('data-apply') || 'all';
        document.querySelectorAll('#stApplyTo [data-apply]').forEach((b) => {
            b.classList.toggle('is-active', b === btn);
        });
        // Rebuild so Cluster/Branch/Employee become display roots
        render();
    });

    $('stShowHierarchy').addEventListener('change', (e) => {
        showHierarchy = !!e.target.checked;
        if (!showHierarchy) collapsed = new Set();
        else {
            // Collapse deeper levels by default (keep Apply To roots expanded)
            const collapseDeep = (nodes, depth = 0) => {
                (nodes || []).forEach((n) => {
                    if (n.children?.length) {
                        if (depth >= 1) collapsed.add(n.id);
                        collapseDeep(n.children, depth + 1);
                    }
                });
            };
            collapsed = new Set();
            collapseDeep(getDisplayRoots(), 0);
        }
        render();
    });

    $('stHistoryLink').addEventListener('click', (e) => {
        e.preventDefault();
        const d = $('stHistory');
        if (d) { d.open = true; d.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }
    });

    let reloadTimer = null;
    let reloadInFlight = null;
    function onPeriodOrMetricChange() {
        clearTimeout(reloadTimer);
        reloadTimer = setTimeout(() => {
            const run = () => reloadTree().catch((err) => flash(err.message, 'err'));
            reloadInFlight = (reloadInFlight || Promise.resolve()).then(run, run);
        }, 50);
    }
    $('stPeriod')?.addEventListener('change', onPeriodOrMetricChange);
    $('stMetric')?.addEventListener('change', onPeriodOrMetricChange);

    function closeAllFilterDropdowns(exceptId) {
        document.querySelectorAll('.goal-set-targets .st-dd.is-open').forEach((dd) => {
            if (exceptId && dd.id === exceptId) return;
            dd.classList.remove('is-open');
            const btn = dd.querySelector('.st-dd-btn');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        });
    }

    function bindFilterDropdown(ddId, selectId, labelId) {
        const dd = $(ddId);
        const select = $(selectId);
        const label = $(labelId);
        const btn = dd?.querySelector('.st-dd-btn');
        const menu = dd?.querySelector('.st-dd-menu');
        if (!dd || !select || !btn || !menu) return;

        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const willOpen = !dd.classList.contains('is-open');
            closeAllFilterDropdowns(willOpen ? dd.id : null);
            dd.classList.toggle('is-open', willOpen);
            btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            if (willOpen) {
                const sel = menu.querySelector('li.is-selected');
                if (sel) sel.scrollIntoView({ block: 'nearest' });
            }
        });

        menu.addEventListener('click', (e) => {
            const li = e.target.closest('li[data-value]');
            if (!li) return;
            e.preventDefault();
            e.stopPropagation();
            const value = li.getAttribute('data-value') || '';
            const text = li.textContent.trim();
            if (select.value === value) {
                closeAllFilterDropdowns();
                return;
            }
            select.value = value;
            menu.querySelectorAll('li').forEach((item) => {
                const on = item === li;
                item.classList.toggle('is-selected', on);
                item.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            if (label) label.textContent = text;
            closeAllFilterDropdowns();
            select.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    bindFilterDropdown('stPeriodDd', 'stPeriod', 'stPeriodLabel');
    bindFilterDropdown('stMetricDd', 'stMetric', 'stMetricLabel');

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.goal-set-targets .st-dd')) closeAllFilterDropdowns();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeAllFilterDropdowns();
    });

    function closeRowMenus() {
        document.querySelectorAll('.goal-set-targets .st-action-dd.is-open').forEach((el) => el.classList.remove('is-open'));
    }

    function collectRowsForIds(ids) {
        const want = new Set((ids || []).map(Number));
        return collectRows(state.tree || []).filter((r) => want.has(Number(r.employee_id)));
    }

    function totalBaseInRows(rows) {
        return (rows || []).reduce((s, r) => s + Number(r.set_target || 0), 0);
    }

    async function persist(assign, onlyIds = null) {
        const rows = onlyIds ? collectRowsForIds(onlyIds) : collectRows(state.tree || []);
        if (!rows.length) {
            flash('No editable rows to save.', 'warn');
            return;
        }
        if (assign && totalBaseInRows(rows) <= 0) {
            flash('Enter Base Target amounts before Assign. Opening Shortfall is only carry-forward from last month.', 'warn');
            return;
        }
        const btns = [ $('stSaveDraft'), $('stSaveDraftTop'), $('stAssign'), $('stAssignTop') ].filter(Boolean);
        btns.forEach((b) => { b.disabled = true; });
        try {
            const url = assign ? routes.assign : routes.saveDraft;
            const json = await api(url, {
                method: 'POST',
                body: JSON.stringify({
                    period: $('stPeriod').value,
                    metric: $('stMetric').value,
                    rows,
                }),
            });
            if (json.data?.tree) state.tree = json.data.tree;
            flash(assign ? (json.message || 'Assigned.') : `Draft saved (${json.data?.saved || rows.length} rows).`, 'ok');
            render();
            loadAudit();
        } finally {
            updateCheckSummary();
            if ($('stSaveDraft')) $('stSaveDraft').disabled = false;
            if ($('stSaveDraftTop')) $('stSaveDraftTop').disabled = false;
        }
    }

    function bindSave(el) {
        if (!el) return;
        el.addEventListener('click', () => persist(false).catch((err) => flash(err.message, 'err')));
    }
    function bindAssign(el) {
        if (!el) return;
        el.addEventListener('click', () => {
            if (!state.can_assign) {
                flash('Finish assigning to your direct reports first. You can still Save.', 'warn');
                return;
            }
            if (totalBaseInRows(collectRows(state.tree || [])) <= 0) {
                flash('Jan (this period) Base Targets are still 0. Enter amounts in Base Target, then Assign. Opening Shortfall is from last month.', 'warn');
                return;
            }
            if (!confirm('Assign these targets and notify the team?')) return;
            persist(true).catch((err) => flash(err.message, 'err'));
        });
    }
    bindSave($('stSaveDraft'));
    bindSave($('stSaveDraftTop'));
    bindAssign($('stAssign'));
    bindAssign($('stAssignTop'));

    $('stTreeBody').addEventListener('click', (e) => {
        const toggle = e.target.closest('[data-assign-toggle], [data-more-toggle]');
        if (toggle) {
            e.preventDefault();
            e.stopPropagation();
            if (toggle.disabled) return;
            const dd = toggle.closest('.st-action-dd');
            const willOpen = !dd.classList.contains('is-open');
            closeRowMenus();
            if (willOpen) dd.classList.add('is-open');
            return;
        }

        const actBtn = e.target.closest('[data-row-act]');
        if (!actBtn || actBtn.disabled) return;
        e.preventDefault();
        e.stopPropagation();
        const id = Number(actBtn.getAttribute('data-id'));
        const act = actBtn.getAttribute('data-row-act');
        const node = findNode(state.tree, id);
        closeRowMenus();
        if (!node || !node.editable) return;

        if (act === 'copy-open') {
            const open = Number(node.last_month || 0);
            node.set_target = open;
            if (!(node.children && node.children.length)) node.owner_target = open;
            node.row_source = 'edited';
            node.chip = 'Edited';
            render();
            flash(`Base Target set to opening shortfall (${fmt(open)}) for ${node.name}. Save or Assign when ready.`, 'ok');
            return;
        }
        if (act === 'clear') {
            node.set_target = 0;
            if (node.children?.length) node.owner_target = 0;
            else node.owner_target = 0;
            node.row_source = 'edited';
            node.chip = 'Edited';
            render();
            return;
        }
        if (act === 'draft') {
            persist(false, [id]).catch((err) => flash(err.message, 'err'));
            return;
        }
        if (act === 'assign') {
            if (Number(node.set_target || 0) <= 0) {
                flash('Enter a Base Target for this person first (Opening Shortfall is only carry-forward).', 'warn');
                return;
            }
            if (!confirm(`Assign target for ${node.name} and notify them?`)) return;
            persist(true, [id]).catch((err) => flash(err.message, 'err'));
        }
    });

    document.addEventListener('click', () => closeRowMenus());

    if (showHierarchy) {
        const collapseDeep = (nodes, depth = 0) => {
            (nodes || []).forEach((n) => {
                if (n.children?.length) {
                    if (depth >= 1) collapsed.add(n.id);
                    collapseDeep(n.children, depth + 1);
                }
            });
        };
        collapseDeep(getDisplayRoots(), 0);
    }
    render();
    loadAudit();
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\CEM-Goal Corporation\isarva-crm-ver2\resources\views/goal/targets/set.blade.php ENDPATH**/ ?>