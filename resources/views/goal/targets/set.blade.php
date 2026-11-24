@extends('layouts.app')

@php
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
@endphp

@push('styles')
@include('goal.partials.pd_hub_styles')
<style>
.goal-set-targets {
    --st-accent: var(--theme-primary, #16a34a);
    --st-accent-soft: color-mix(in srgb, var(--st-accent) 12%, #fff);
    --st-border: #e8edf5;
    --st-muted: #64748b;
    --st-text: #0f172a;
    color: var(--st-text);
    padding-bottom: 96px;
    background: #f4f6f9;
    max-width: 100%;
    overflow-x: clip;
    box-sizing: border-box;
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
        min-width: 0 !important;
        max-width: none !important;
    }
}

/* —— Responsive: tablet / phone —— */
@media (max-width: 991.98px) {
    .goal-set-targets.container-fluid,
    .goal-set-targets {
        padding-left: 12px !important;
        padding-right: 12px !important;
    }
    .goal-set-targets .st-hero {
        flex-direction: column;
        gap: 12px;
    }
    .goal-set-targets .st-hero-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        width: 100%;
        gap: 8px;
    }
    .goal-set-targets .st-hero-actions .st-btn {
        width: 100%;
        min-width: 0;
        justify-content: center;
        font-size: .75rem;
        padding: 0 8px;
        height: 36px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .goal-set-targets .st-title { font-size: 1.28rem; }
    .goal-set-targets .st-sub { font-size: .82rem; max-width: none; }
    .goal-set-targets .st-title-ico { width: 44px; height: 44px; font-size: 1.2rem; }
    .goal-set-targets .st-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    .goal-set-targets .st-kpi { padding: 10px 12px; gap: 10px; }
    .goal-set-targets .st-kpi-value { font-size: 1.05rem; }
    .goal-set-targets .st-kpi-meta { white-space: normal; }
}

@media (max-width: 767.98px) {
    .goal-set-targets { padding-bottom: 72px; }
    .goal-set-targets .st-filters {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
        padding: 12px;
    }
    .goal-set-targets .st-field { width: 100%; }
    .goal-set-targets .st-filters .st-control,
    .goal-set-targets .st-filters .form-select.st-control {
        width: 100% !important;
        min-width: 0 !important;
        max-width: none !important;
    }
    .goal-set-targets .st-dd,
    .goal-set-targets .st-dd.is-metric { width: 100%; }
    .goal-set-targets .st-seg {
        width: 100%;
        display: grid;
        grid-template-columns: 1fr 1fr;
        height: auto;
        overflow: hidden;
    }
    .goal-set-targets .st-seg button {
        flex: none;
        height: 36px;
        padding: 0 6px;
        border-right: 0;
        border-bottom: 1px solid var(--ft-border);
        font-size: 11px;
    }
    .goal-set-targets .st-seg button:nth-child(odd) { border-right: 1px solid var(--ft-border); }
    .goal-set-targets .st-seg button:nth-child(n+3) { border-bottom: 0; }
    .goal-set-targets .st-toggle-wrap {
        height: auto;
        width: 100%;
        justify-content: space-between;
        padding: 4px 0;
    }
    .goal-set-targets .st-search {
        width: 100%;
        max-width: none;
        flex: 1 1 auto;
        margin-left: 0;
    }
    .goal-set-targets .st-search > label { display: none; }

    /* Mobile: dense aligned tree rows */
    .goal-set-targets .st-table-scroll-hint { display: none !important; }
    .goal-set-targets .st-table-wrap { display: none !important; }
    .goal-set-targets .st-mobile-list {
        display: flex !important;
        flex-direction: column;
        gap: 0;
        padding: 0;
        background: #fff;
        border-top: 1px solid #e2e8f0;
    }
    .goal-set-targets .st-mcard {
        position: relative;
        background: #fff;
        border: 0;
        border-bottom: 1px solid #eef2f7;
        border-radius: 0;
        padding: 8px 10px 8px 8px;
        margin: 0;
        box-shadow: none;
    }
    .goal-set-targets .st-mcard[data-depth="0"] { padding-left: 10px; }
    .goal-set-targets .st-mcard[data-depth="1"] { padding-left: 22px; }
    .goal-set-targets .st-mcard[data-depth="2"] { padding-left: 34px; }
    .goal-set-targets .st-mcard[data-depth="3"],
    .goal-set-targets .st-mcard[data-depth="4"] { padding-left: 46px; }
    .goal-set-targets .st-mcard::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 3px;
        background: #e2e8f0;
    }
    .goal-set-targets .st-mcard[data-depth="0"]::before { background: #059669; }
    .goal-set-targets .st-mcard[data-depth="1"]::before { background: #3b82f6; }
    .goal-set-targets .st-mcard[data-depth="2"]::before { background: #8b5cf6; }
    .goal-set-targets .st-mcard[data-depth="3"]::before,
    .goal-set-targets .st-mcard[data-depth="4"]::before { background: #94a3b8; }
    .goal-set-targets .st-mcard.is-filtered-out { display: none; }
    .goal-set-targets .st-mcard.st-amber { background: #fffbeb; }

    .goal-set-targets .st-mcard-under { display: none; }

    .goal-set-targets .st-mcard-top {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 6px;
        min-width: 0;
    }
    .goal-set-targets .st-mcard-top .st-toggle {
        margin: 0;
        width: 22px;
        height: 22px;
        flex-shrink: 0;
        border-radius: 5px;
        background: transparent;
        border: 0;
        color: #64748b;
    }
    .goal-set-targets .st-mcard-top .st-toggle.is-leaf {
        visibility: hidden;
        pointer-events: none;
    }
    .goal-set-targets .st-mcard-top .st-ent-ico {
        width: 24px;
        height: 24px;
        font-size: .7rem;
        flex-shrink: 0;
        border-radius: 6px;
    }
    .goal-set-targets .st-mcard-head {
        flex: 1;
        min-width: 0;
    }
    .goal-set-targets .st-mcard-title-row {
        display: flex;
        align-items: center;
        gap: 6px;
        min-width: 0;
        margin-bottom: 0;
    }
    .goal-set-targets .st-mcard-idx {
        font-size: .65rem;
        font-weight: 700;
        color: #94a3b8;
        font-variant-numeric: tabular-nums;
        flex-shrink: 0;
    }
    .goal-set-targets .st-mcard-title-row .st-type {
        font-size: .58rem;
        padding: 1px 6px;
        border-radius: 4px;
        font-weight: 700;
        flex-shrink: 0;
    }
    .goal-set-targets .st-mcard-kids { display: none; }
    .goal-set-targets .st-mcard-name {
        font-size: .82rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.25;
        word-break: break-word;
        white-space: normal;
    }
    .goal-set-targets .st-mcard-meta {
        margin-top: 1px;
        font-size: .68rem;
        color: #64748b;
        line-height: 1.3;
        white-space: normal;
    }

    /* 4 aligned columns — same grid on every row */
    .goal-set-targets .st-mcard-grid {
        display: grid;
        grid-template-columns: minmax(0, .9fr) minmax(0, 1.15fr) minmax(0, 1.15fr) minmax(0, .95fr);
        gap: 6px;
        margin: 0;
        padding: 0;
        border: 0;
        align-items: end;
    }
    .goal-set-targets .st-mfield {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        justify-content: flex-end;
        gap: 2px;
        background: transparent;
        border: 0;
        border-radius: 0;
        padding: 0;
        min-width: 0;
    }
    .goal-set-targets .st-mfield.is-eff {
        background: transparent;
        border: 0;
        margin: 0;
        padding: 0;
    }
    .goal-set-targets .st-mfield-label {
        display: block;
        font-size: .58rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .02em;
        color: #94a3b8;
        margin: 0;
        line-height: 1.1;
        white-space: nowrap;
    }
    .goal-set-targets .st-mfield.is-eff .st-mfield-label { color: #059669; }
    .goal-set-targets .st-mfield .st-input-wrap,
    .goal-set-targets .st-mfield .st-cell-num,
    .goal-set-targets .st-mfield .st-eff {
        width: 100%;
        min-width: 0;
        max-width: none;
        justify-content: flex-end;
        font-size: .78rem;
        font-variant-numeric: tabular-nums;
    }
    .goal-set-targets .st-mfield .st-input-wrap {
        height: 30px;
        padding: 0 5px;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        background: #fff;
        box-shadow: none;
    }
    .goal-set-targets .st-mfield .st-input-wrap .cur {
        font-size: .62rem;
        color: #94a3b8;
    }
    .goal-set-targets .st-mfield .st-input-wrap input {
        font-size: .76rem;
        font-weight: 650;
        padding: 0 2px;
        text-align: right;
        min-width: 0;
    }
    .goal-set-targets .st-mfield .st-cell-num {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        height: 30px;
        font-weight: 650;
        color: #334155;
        padding: 0 2px;
    }
    .goal-set-targets .st-mfield .st-eff {
        font-size: .8rem;
        font-weight: 800;
        color: #059669;
        display: flex;
        align-items: center;
        height: 30px;
        justify-content: flex-end;
        padding: 0 2px;
    }
    .goal-set-targets .st-mfield .st-rem-caption {
        max-width: none;
        text-align: right;
        margin-top: 2px;
        color: #64748b;
    }

    .goal-set-targets .st-mcard-actions {
        display: flex;
        align-items: center;
        flex-shrink: 0;
        margin: 0;
        padding: 0;
        border: 0;
        gap: 0;
    }
    .goal-set-targets .st-mcard-actions .st-btn-assign {
        height: 26px;
        min-height: 26px;
        padding: 0 8px;
        font-size: .66rem;
        width: auto !important;
        border-radius: 6px;
    }
    .goal-set-targets .st-mcard-actions .st-btn-icon { display: none; }
    .goal-set-targets .st-panel { overflow: visible; }
    .goal-set-targets .st-mcard-actions .st-action-dd { position: relative; }
    .goal-set-targets .st-mcard-actions .st-action-menu {
        min-width: 190px;
        z-index: 1080;
    }
    .goal-set-targets .st-mcard-empty {
        text-align: center;
        color: #94a3b8;
        padding: 24px 12px;
        font-size: .82rem;
    }
    .goal-set-targets .st-mobile-totals {
        background: #f0fdf4;
        border: 0;
        border-top: 1px solid #bbf7d0;
        border-radius: 0;
        padding: 10px 12px;
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 6px;
        margin: 0;
    }
    .goal-set-targets .st-mobile-totals .st-mfield {
        display: block;
        background: transparent;
        border: 0;
        padding: 0;
    }
    .goal-set-targets .st-mobile-totals .st-mfield-label {
        color: #166534;
        font-size: .58rem;
        margin-bottom: 2px;
    }
    .goal-set-targets .st-mobile-totals .st-mfield-val {
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        color: #14532d;
        font-size: .78rem;
    }
}
@media (min-width: 768px) {
    .goal-set-targets .st-mobile-list { display: none !important; }
}

@media (max-width: 575.98px) {
    .goal-set-targets .st-kpi-grid { grid-template-columns: 1fr; }
    .goal-set-targets .st-hero-actions { grid-template-columns: 1fr 1fr; }
    .goal-set-targets .st-footer-tip-short { display: inline; }
    .goal-set-targets .st-footer-tip-full { display: none; }
}
@media (min-width: 576px) {
    .goal-set-targets .st-footer-tip-short { display: none; }
    .goal-set-targets .st-footer-tip-full { display: inline; }
}
@media (min-width: 768px) {
    .goal-set-targets .st-table-scroll-hint { display: none !important; }
}

/* Panel + table */
.goal-set-targets .st-panel {
    position: relative;
    z-index: 1;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
    box-shadow: 0 4px 16px rgba(148,163,184,.1); overflow: hidden;
}
.goal-set-targets .st-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
.goal-set-targets .st-mobile-list { display: none; }
.goal-set-targets .st-table-scroll-hint { display: none; }
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
    cursor: help;
}
.goal-set-targets table.st-tree th .th-info:hover,
.goal-set-targets table.st-tree th .th-info:focus {
    color: #059669;
    outline: none;
}
.st-tip-bubble {
    position: fixed;
    z-index: 3000;
    max-width: 260px;
    padding: 8px 11px;
    border-radius: 8px;
    background: #0f172a;
    color: #f8fafc;
    font-size: .75rem;
    font-weight: 600;
    line-height: 1.35;
    text-align: left;
    box-shadow: 0 10px 24px rgba(15, 23, 42, .22);
    pointer-events: none;
    opacity: 0;
    transform: translateY(4px);
    transition: opacity .12s ease, transform .12s ease;
}
.st-tip-bubble.is-on {
    opacity: 1;
    transform: translateY(0);
}
.st-tip-bubble::after {
    content: '';
    position: absolute;
    left: 50%;
    bottom: -6px;
    margin-left: -6px;
    border: 6px solid transparent;
    border-top-color: #0f172a;
    border-bottom-width: 0;
}
.st-tip-bubble.is-below::after {
    top: -6px;
    bottom: auto;
    border-top-color: transparent;
    border-bottom-color: #0f172a;
    border-top-width: 0;
    border-bottom-width: 6px;
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
.goal-set-targets .st-ent-lead {
    font-weight: 650; white-space: nowrap;
}
.goal-set-targets .st-ent-lead.rd { color: #059669; }
.goal-set-targets .st-ent-lead.cluster { color: #2563eb; }
.goal-set-targets .st-ent-lead.bm { color: #7c3aed; }
.goal-set-targets .st-ent-lead.staff,
.goal-set-targets .st-ent-lead.other { color: #0f766e; }

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
    display: inline-flex; align-items: center; justify-content: flex-end;
    box-sizing: border-box; width: 118px; min-width: 118px; text-align: right;
}
.goal-set-targets .st-eff {
    font-variant-numeric: tabular-nums; font-weight: 800; color: #0f172a; font-size: .88rem;
    display: inline-flex; align-items: center; justify-content: flex-end;
    box-sizing: border-box; width: 118px; min-width: 118px; text-align: right;
}
.goal-set-targets .st-rem-stack {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 1px;
    margin-left: auto;
}
.goal-set-targets .st-rem-stack .st-eff {
    width: auto;
    min-width: 0;
}
.goal-set-targets .st-rem-caption {
    display: block;
    font-size: .58rem;
    font-weight: 600;
    line-height: 1.2;
    color: #64748b;
    text-align: right;
    text-transform: none;
    letter-spacing: 0;
    white-space: normal;
    max-width: 110px;
}
.goal-set-targets .st-th-sub {
    display: block;
    margin-top: 2px;
    font-size: .58rem;
    font-weight: 600;
    letter-spacing: 0;
    text-transform: none;
    color: #166534;
    opacity: .85;
}
.goal-set-targets table.st-tree th.col-eff {
    white-space: normal;
    line-height: 1.25;
    vertical-align: bottom;
}
.goal-set-targets table.st-tree td.col-eff {
    vertical-align: middle;
    white-space: normal;
}
.goal-set-targets table.st-tree td.num {
    text-align: right;
    vertical-align: middle;
    white-space: nowrap;
}
.goal-set-targets table.st-tree td.num > .st-input-wrap,
.goal-set-targets table.st-tree td.num > .st-cell-num,
.goal-set-targets table.st-tree td.num > .st-eff {
    margin-left: auto;
    margin-right: 0;
}
.goal-set-targets table.st-tree td.col-eff > .st-rem-stack {
    margin-left: auto;
    margin-right: 0;
}
.goal-set-targets table.st-tree tfoot td.num {
    text-align: right;
    font-variant-numeric: tabular-nums;
}
.goal-set-targets table.st-tree th.num,
.goal-set-targets table.st-tree td.num {
    width: 132px;
    min-width: 124px;
}
.goal-set-targets table.st-tree th.col-eff,
.goal-set-targets table.st-tree td.col-eff {
    width: 138px;
    min-width: 128px;
}
.goal-set-targets table.st-tree th.col-actions,
.goal-set-targets table.st-tree td.col-actions {
    width: 148px;
    min-width: 136px;
    text-align: right;
    white-space: nowrap;
    padding-right: 14px;
}
.goal-set-targets table.st-tree tr.st-focus-row > td,
.goal-set-targets .st-mcard.st-focus-row {
    background: #ecfdf5 !important;
    box-shadow: inset 3px 0 0 #059669;
}
.goal-set-targets .st-mcard.st-focus-row {
    border-bottom-color: #bbf7d0;
}
.goal-set-targets .st-row-actions {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: 6px;
    width: 100%;
}

/* Single clean currency field — kill global input chrome (double box) */
.goal-set-targets .st-input-wrap {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    box-sizing: border-box;
    height: 34px;
    width: 118px;
    max-width: 100%;
    padding: 0 8px 0 10px;
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
    line-height: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    user-select: none;
}
.goal-set-targets .st-input-wrap input.st-input-num,
.goal-set-targets input.st-input-num {
    flex: 1 1 auto;
    width: 100% !important;
    min-width: 0 !important;
    max-width: none !important;
    height: 32px !important;
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
    line-height: 32px !important;
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
    position: fixed; right: auto; top: 0; left: 0;
    min-width: 190px; margin: 0; padding: 6px; list-style: none;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 8px;
    box-shadow: 0 12px 28px rgba(15,23,42,.18); z-index: 1080;
    display: none;
}
.goal-set-targets .st-action-dd.is-open .st-action-menu { display: block; }
.goal-set-targets table.st-tree tr.st-menu-open,
.goal-set-targets .st-mcard.st-menu-open {
    position: relative;
    z-index: 50;
}
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

/* Footer — single compact action bar */
.goal-set-targets .st-footer {
    position: fixed;
    left: 270px;
    right: 0;
    bottom: 0;
    z-index: 1046;
    height: auto;
    max-height: none;
    background: #fff;
    border-top: 1px solid #e2e8f0;
    box-shadow: 0 -4px 18px rgba(15, 23, 42, .07);
    padding: 10px 16px;
    padding-bottom: max(10px, env(safe-area-inset-bottom, 0px));
}
body.sidebar-collapsed .goal-set-targets .st-footer { left: 76px; }
@media (max-width: 1023.98px) { .goal-set-targets .st-footer { left: 0; } }
.goal-set-targets .st-footer-inner {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 14px;
    align-items: center;
    justify-content: space-between;
    max-width: 1400px;
    margin: 0 auto;
}
.goal-set-targets .st-footer-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px 12px;
    min-width: 0;
    flex: 1 1 auto;
}
.goal-set-targets .st-tip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #f0f9ff;
    color: #075985;
    border: 1px solid #bae6fd;
    border-radius: 8px;
    padding: 6px 10px;
    font-size: .74rem;
    line-height: 1.3;
    max-width: min(560px, 100%);
}
.goal-set-targets .st-tip i {
    flex-shrink: 0;
    font-size: .85rem;
    color: #0284c7;
}
.goal-set-targets #stCheckSummary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: .78rem;
    font-weight: 700;
    margin: 0;
    white-space: nowrap;
}
.goal-set-targets #stCheckSummary.ok { color: #15803d; }
.goal-set-targets #stCheckSummary.warn { color: #c2410c; }
.goal-set-targets .st-footer-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
    margin-left: auto;
}
.goal-set-targets .st-footer-actions .st-btn {
    min-width: 132px;
    height: 38px;
}

/* Mobile footer: tight bar — no empty “card” */
@media (max-width: 767.98px) {
    .goal-set-targets { padding-bottom: 72px !important; }
    .goal-set-targets .st-footer {
        padding: 8px 12px;
        padding-bottom: max(8px, env(safe-area-inset-bottom, 0px));
    }
    .goal-set-targets .st-footer-inner {
        flex-direction: column;
        align-items: stretch;
        justify-content: flex-start;
        gap: 8px;
    }
    .goal-set-targets .st-footer-meta {
        flex: 0 0 auto;
        flex-direction: row;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px 10px;
    }
    /* Tip is secondary on phone — keep one compact status line */
    .goal-set-targets .st-tip {
        display: none;
    }
    .goal-set-targets #stCheckSummary {
        width: 100%;
        white-space: normal;
        font-size: .74rem;
        line-height: 1.3;
        padding: 0;
    }
    .goal-set-targets .st-footer-actions {
        margin-left: 0;
        width: 100%;
        gap: 8px;
    }
    .goal-set-targets .st-footer-actions .st-btn {
        flex: 1 1 0;
        min-width: 0;
        height: 40px;
        font-size: .78rem;
    }
}
</style>
@endpush

@section('content')
<div class="container-fluid goal-set-targets p-4">
    <div class="st-hero">
        <div>
            <ul class="st-crumbs" aria-label="Breadcrumb">
                <li><a href="{{ route('goal.targets.index') }}">Targets</a></li>
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
            <a href="{{ route('goal.targets.index') }}" class="st-btn"><i class="bi bi-info-circle"></i> Target Policy</a>
            <button type="button" class="st-btn" disabled title="Coming soon"><i class="bi bi-upload"></i> Import (Excel)</button>
            <a href="{{ route('goal.targets.export', request()->only('period', 'metric')) }}" class="st-btn"><i class="bi bi-download"></i> Export <i class="bi bi-chevron-down" style="font-size:.7rem;opacity:.7"></i></a>
            <a href="#stHistory" class="st-btn st-btn-history" id="stHistoryLink"><i class="bi bi-clock-history"></i> Target History</a>
        </div>
    </div>

    <div id="stFlash" class="st-alert info d-none" role="status"></div>

    @if($receives)
        <input type="hidden" id="stAreaTotal" value="{{ number_format((float) ($p['self']['remainder'] ?? 0), 2, '.', '') }}">
        <input type="hidden" id="stLastMonth" value="{{ number_format((float) ($p['self']['last_month'] ?? 0), 2) }}">
        <input type="hidden" id="stMyTarget" value="{{ number_format((float) ($p['self']['set_target'] ?? 0), 2) }}">
        <input type="hidden" id="stMyOwner" value="{{ number_format((float) ($p['self']['owner_target'] ?? 0), 2) }}">
        <input type="hidden" id="stLeftDistribute" value="{{ number_format((float) ($p['self']['left_to_distribute'] ?? $p['self']['remainder'] ?? 0), 2) }}">
        <div class="st-mine">
            <span class="st-pill"><i class="bi bi-bullseye"></i> Your target <strong id="stPillTarget">{{ number_format((float) ($p['self']['set_target'] ?? 0), 0) }}</strong></span>
            <span class="st-pill"><i class="bi bi-person"></i> Personal <strong id="stPillOwner">{{ number_format((float) ($p['self']['owner_target'] ?? 0), 0) }}</strong></span>
            <span class="st-pill left"><i class="bi bi-share"></i> Left to give <strong id="stPillLeft">{{ number_format((float) ($p['self']['left_to_distribute'] ?? $p['self']['remainder'] ?? 0), 0) }}</strong></span>
        </div>
    @else
        <input type="hidden" id="stAreaTotal" value="0">
        <input type="hidden" id="stLastMonth" value="0">
    @endif

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
                    <p class="st-kpi-label" id="stKpiLeftLabel">Last Month Target (Revenue)</p>
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
                        @php
                            $curPeriodLabel = collect($periodOptions)->firstWhere('value', $p['period'] ?? '')['label']
                                ?? ($periodOptions[0]['label'] ?? 'Select');
                        @endphp
                        {{ $curPeriodLabel }}
                    </span>
                    <i class="bi bi-chevron-down st-dd-caret" aria-hidden="true"></i>
                </button>
                <ul class="st-dd-menu" id="stPeriodMenu" role="listbox" aria-labelledby="stPeriodLabelFor" tabindex="-1">
                    @foreach($periodOptions as $opt)
                        <li role="option"
                            data-value="{{ $opt['value'] }}"
                            @class(['is-selected' => ($p['period'] ?? '') === $opt['value']])
                            aria-selected="{{ ($p['period'] ?? '') === $opt['value'] ? 'true' : 'false' }}">{{ $opt['label'] }}</li>
                    @endforeach
                </ul>
                <select id="stPeriod" class="d-none no-select2" data-select2-skip="1" tabindex="-1" aria-hidden="true" required>
                    @foreach($periodOptions as $opt)
                        <option value="{{ $opt['value'] }}" @selected(($p['period'] ?? '') === $opt['value'])>{{ $opt['label'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="st-field">
            <label id="stMetricLabelFor">Target For <span class="req" aria-hidden="true">*</span></label>
            <div class="st-dd is-metric" id="stMetricDd">
                <button type="button" class="st-dd-btn" id="stMetricBtn"
                    aria-labelledby="stMetricLabelFor" aria-haspopup="listbox" aria-expanded="false" aria-controls="stMetricMenu">
                    <span class="st-dd-label" id="stMetricLabel">
                        {{ $metrics[$p['metric'] ?? ''] ?? (\Illuminate\Support\Arr::first($metrics) ?? 'Select') }}
                    </span>
                    <i class="bi bi-chevron-down st-dd-caret" aria-hidden="true"></i>
                </button>
                <ul class="st-dd-menu" id="stMetricMenu" role="listbox" aria-labelledby="stMetricLabelFor" tabindex="-1">
                    @foreach($metrics as $key => $label)
                        <li role="option"
                            data-value="{{ $key }}"
                            @class(['is-selected' => ($p['metric'] ?? '') === $key])
                            aria-selected="{{ ($p['metric'] ?? '') === $key ? 'true' : 'false' }}">{{ $label }}</li>
                    @endforeach
                </ul>
                <select id="stMetric" class="d-none no-select2" data-select2-skip="1" tabindex="-1" aria-hidden="true" required>
                    @foreach($metrics as $key => $label)
                        <option value="{{ $key }}" @selected(($p['metric'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
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
        <div class="st-table-wrap" id="stTableWrap">
            <table class="st-tree" id="stTreeTable">
                <thead>
                    <tr>
                        <th class="idx">#</th>
                        <th style="min-width:260px">Entity / Person (Hierarchy)</th>
                        <th>Type</th>
                        <th class="num">Last Month Target ({{ $unit }})
                            <i class="bi bi-info-circle th-info" role="button" tabindex="0"
                               data-st-tip="Previous period’s assigned total target (read-only)."></i>
                        </th>
                        <th class="num">Total Target ({{ $unit }})
                            <i class="bi bi-info-circle th-info" role="button" tabindex="0"
                               data-st-tip="This period’s total target to assign."></i>
                        </th>
                        <th class="num">Owner Target ({{ $unit }})
                            <i class="bi bi-info-circle th-info" role="button" tabindex="0"
                               data-st-tip="Personal share kept by this person — must be within Total Target (not extra)."></i>
                        </th>
                        <th class="num col-eff">Remaining ({{ $unit }})
                            <i class="bi bi-info-circle th-info" role="button" tabindex="0"
                               data-st-tip="Still left to assign downline = (Total − Owner) − children Total Targets."></i>
                            <span class="st-th-sub">left to assign downline</span>
                        </th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody id="stTreeBody"></tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><strong>Total</strong></td>
                        <td class="num"><span class="st-cell-num" id="stFootLast">0</span></td>
                        <td class="num"><span class="st-cell-num" id="stFootTarget">0</span></td>
                        <td class="num"><span class="st-cell-num" id="stFootPersonal">0</span></td>
                        <td class="num col-eff">
                            <div class="st-rem-stack">
                                <span class="st-eff" id="stFootTeam">0</span>
                                <span class="st-rem-caption">left to assign downline</span>
                            </div>
                        </td>
                        <td class="col-actions"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="st-mobile-list" id="stMobileList" aria-label="Targets list"></div>

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
            <div class="st-footer-meta">
                <div class="st-tip">
                    <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
                    <span id="stAuthTip">
                        <span class="st-footer-tip-full">You can set targets for people below your level. Your own target is set by your reporting authority only.</span>
                        <span class="st-footer-tip-short">Edit team below you · own target is view-only</span>
                    </span>
                </div>
                <div id="stCheckSummary" class="ok"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Ready to save or assign</div>
            </div>
            <div class="st-footer-actions">
                <button type="button" class="st-btn" id="stSaveDraft"><i class="bi bi-floppy"></i> Save as Draft</button>
                <button type="button" class="st-btn st-btn-primary" id="stAssign" disabled><i class="bi bi-send"></i> Assign &amp; Notify</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const routes = {
        tree: @json(route('goal.targets.tree')),
        saveDraft: @json(route('goal.targets.save-draft')),
        assign: @json(route('goal.targets.assign')),
        audit: @json(route('goal.targets.audit-log')),
    };
    const jumpPct = {{ $jumpPct }};
    const unit = @json($unit);
    let state = @json($p);
    let collapsed = new Set();
    let searchQ = '';
    let levelFilter = 'all';
    let applyFilter = String(state.authority?.default_apply || 'rd');
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
            if (n.is_org_group && n.linked_user_id && n.editable) {
                out.push({
                    employee_id: Number(n.linked_user_id),
                    set_target: Number(n.set_target || 0),
                    owner_target: Number(n.owner_target || 0),
                    row_source: n.row_source === 'edited' ? 'edited' : 'auto',
                    org_unit_id: n.org_unit_id ? Number(n.org_unit_id) : null,
                });
            } else if (n.editable && !n.is_org_group) {
                out.push({
                    employee_id: n.id,
                    set_target: Number(n.set_target || 0),
                    owner_target: Number(n.owner_target || 0),
                    row_source: n.row_source === 'edited' ? 'edited' : 'auto',
                    org_unit_id: null,
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
            if (n.is_org_group) {
                const t = String(n.org_type || '').toLowerCase();
                if (t === 'region') out.rd += 1;
                else if (t === 'cluster') out.cluster += 1;
                else if (t === 'branch') out.bm += 1;
            } else {
                out.staff += 1;
            }
            if (n.children?.length) countByRole(n.children, out);
        });
        return out;
    }

    function countOrgChildren(nodes, out = { cluster: 0, branch: 0, people: 0 }) {
        (nodes || []).forEach((n) => {
            if (n.is_org_group) {
                const t = String(n.org_type || '').toLowerCase();
                if (t === 'cluster') out.cluster += 1;
                else if (t === 'branch') out.branch += 1;
                if (n.children?.length) countOrgChildren(n.children, out);
            } else {
                out.people += 1;
            }
        });
        return out;
    }

    function childCounts(n) {
        const kids = n.children || [];
        if (!kids.length) return '';
        if (n.is_org_group) {
            const by = countOrgChildren(kids);
            const parts = [];
            if (by.cluster) parts.push(by.cluster + ' Cluster' + (by.cluster > 1 ? 's' : ''));
            if (by.branch) parts.push(by.branch + ' Branch' + (by.branch > 1 ? 'es' : ''));
            if (by.people) parts.push(by.people + ' Emp');
            return parts.join(' · ') || (kids.length + ' items');
        }
        const by = countByRole(kids);
        const parts = [];
        if (by.rd) parts.push(by.rd + ' Region' + (by.rd > 1 ? 's' : ''));
        if (by.cluster) parts.push(by.cluster + ' Cluster' + (by.cluster > 1 ? 's' : ''));
        if (by.bm) parts.push(by.bm + ' Branch' + (by.bm > 1 ? 'es' : ''));
        if (by.staff && !by.cluster && !by.bm && !by.rd) parts.push(by.staff + ' Employee' + (by.staff > 1 ? 's' : ''));
        else if (by.staff && (by.cluster || by.bm || by.rd)) parts.push(by.staff + ' Emp');
        return parts.join(' · ') || (kids.length + ' reports');
    }

    function recompute(nodes) {
        (nodes || []).forEach((n) => {
            const hasKids = !!(n.children && n.children.length);
            n.is_staff = !hasKids && !n.is_org_group;
            if (hasKids) {
                recompute(n.children);
                const teamRollup = n.children.reduce((s, c) => s + Number(c.set_target || 0), 0);
                const openRollup = n.children.reduce((s, c) => s + Number(c.last_month || 0), 0);
                if (n.is_org_group && !n.linked_user_id) {
                    n.set_target = Math.round(teamRollup * 100) / 100;
                    n.last_month = Math.round(openRollup * 100) / 100;
                    n.owner_target = 0;
                    n.total_target = teamRollup;
                    n.remainder = 0;
                    n.over_allocated = false;
                    n.pending_distribution = false;
                    n.check_ok = true;
                    n.check_label = null;
                    return;
                }
                const owner = Number(n.owner_target || 0);
                const set = Number(n.set_target || 0);
                const pool = Math.max(0, set - owner);
                const left = Math.round((pool - teamRollup) * 100) / 100;
                n.remainder = Math.max(0, left);
                n.total_target = teamRollup;
                n.over_allocated = (teamRollup - pool) > 0.05;
                n.pending_distribution = !n.over_allocated && Math.abs(teamRollup - pool) > 0.05;
                n.check_ok = Math.abs(teamRollup - pool) <= 0.05;
                n.check_label = n.over_allocated
                    ? `Over by ${fmt(teamRollup - pool)} — children exceed Remaining pool`
                    : (n.pending_distribution ? `${fmt(Math.max(0, left))} left to distribute` : 'Fully set');
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
                if (!n.is_org_group || n.linked_user_id) {
                    out.push({
                        id: n.linked_user_id || n.id,
                        label: n.check_label || n.linked_user_name || n.name,
                        ok: !!n.check_ok,
                        over: !!n.over_allocated,
                        pending: !!n.pending_distribution,
                    });
                }
                collectChecks(n.children, out);
            }
        });
        return out;
    }

    function roleMeta(n) {
        const orgType = String(n.org_type || '').toLowerCase();
        const crm = Number(n.crm_role_type);
        const role = String(n.role || '').toLowerCase();
        const job = String(n.job_role || '').toLowerCase().replace(/[\s_-]+/g, '');
        // Org scaffold rows (Target Allocation style)
        if (n.is_org_group) {
            if (orgType === 'cluster') return { cls: 'cluster', short: 'Cluster', label: 'Cluster', icon: 'bi-diagram-3' };
            if (orgType === 'branch') return { cls: 'bm', short: 'Branch', label: 'Branch', icon: 'bi-bank' };
            return { cls: 'rd', short: 'Region', label: 'Region', icon: 'bi-building' };
        }
        // People — use role, not org unit type (people on a Region unit are not always Type=Region)
        if (crm === 6 || role.includes('regional') || role === 'region' || job === 'rd' || job === 'regionaldirector') {
            return { cls: 'rd', short: 'Region', label: 'Region', icon: 'bi-building' };
        }
        if (crm === 7 || role.includes('cluster') || job === 'cluster' || job === 'clusterhead' || job === 'cd') {
            return { cls: 'cluster', short: 'Cluster', label: 'Cluster', icon: 'bi-diagram-3' };
        }
        if (crm === 8 || role.includes('branch') || job === 'bm' || job === 'branchmanager') {
            return { cls: 'bm', short: 'Branch', label: 'Branch', icon: 'bi-bank' };
        }
        if (!n.children?.length || n.is_staff) return { cls: 'staff', short: 'Employee', label: 'Employee', icon: 'bi-person' };
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
        const hay = `${n.name || ''} ${n.linked_user_name || ''} ${n.area || ''} ${n.role || ''} ${n.job_role || ''}`.toLowerCase();
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

    function collectOrgByType(nodes, type, out = []) {
        (nodes || []).forEach((n) => {
            if (n.is_org_group && String(n.org_type || '') === type) out.push(n);
            else if (n.children?.length) collectOrgByType(n.children, type, out);
        });
        return out;
    }

    /** Roots for current Apply To + hierarchy mode (mockup behavior). */
    function getDisplayRoots() {
        const tree = state.tree || [];
        if (applyFilter === 'all') return tree;
        if (applyFilter === 'rd') {
            const regions = tree.filter((n) => n.is_org_group && n.org_type === 'region');
            if (regions.length) return regions;
            const found = collectOrgByType(tree, 'region');
            // Scoped users (e.g. Cluster Director) may not have Region in tree — keep their root
            return found.length ? found : tree;
        }
        if (applyFilter === 'cluster') {
            const clusters = collectOrgByType(tree, 'cluster');
            if (clusters.length) return clusters;
            const byRole = collectByRole(tree, 'cluster');
            return byRole.length ? byRole : tree;
        }
        if (applyFilter === 'bm') {
            const branches = collectOrgByType(tree, 'branch');
            if (branches.length) return branches;
            const byRole = collectByRole(tree, 'bm');
            return byRole.length ? byRole : tree;
        }
        // Employee → people leaves
        const staff = collectByRole(tree, 'staff');
        return staff.length ? staff : tree;
    }

    function syncApplyToButtons() {
        document.querySelectorAll('#stApplyTo [data-apply]').forEach((b) => {
            b.classList.toggle('is-active', b.getAttribute('data-apply') === applyFilter);
        });
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

    /** Owner amount shown in the Owner Target column (staff leaves show 0). */
    function ownerDisplayed(n) {
        const hasKids = !!(n.children && n.children.length);
        if (n.is_org_group) {
            return n.linked_user_id ? Number(n.owner_target || 0) : 0;
        }
        if (!hasKids) return 0; // staff: Owner Target column shows 0
        return Number(n.owner_target || 0);
    }

    /** Direct children Total Targets (what was already given below). */
    function childrenTotal(n) {
        return (n.children || []).reduce((s, c) => s + Number(c.set_target || 0), 0);
    }

    /**
     * Remaining left to distribute below =
     * (Total Target − Owner Target) − sum(children Total Targets).
     * Owner is within Total (not extra). Last Month is reference only.
     */
    function effectiveOf(n) {
        const total = Number(n.set_target || 0);
        const owner = ownerDisplayed(n);
        const pool = Math.max(0, total - owner);
        // Leaf staff: nothing left to push down
        if (!n.is_org_group && !(n.children && n.children.length)) {
            return 0;
        }
        // Plain org rollup (no linked lead): fully represented by children
        if (n.is_org_group && !n.linked_user_id) {
            return 0;
        }
        const left = Math.round((pool - childrenTotal(n)) * 100) / 100;
        return Math.max(0, left);
    }

    function entityTitle(n) {
        if (n.is_org_group) return n.name || n.region_name || 'Region';
        const name = n.name || '';
        const org = n.org_name && n.org_name !== name ? n.org_name : '';
        const code = n.org_code ? ` (${n.org_code})` : '';
        // Region / Cluster / Branch people: show org unit like Target Allocation when useful
        const rm = roleMeta(n);
        if (org && (rm.cls === 'rd' || rm.cls === 'cluster' || rm.cls === 'bm') && !name.includes(org)) {
            return `${name}`;
        }
        return name;
    }

    function personRoleLabel(n) {
        const role = String(n.role || '').trim();
        if (role && !['team member', 'role'].includes(role.toLowerCase())) return role;
        const job = String(n.job_role || '').trim();
        if (!job) return 'Employee';
        const map = {
            rd: 'Regional Director',
            cluster: 'Cluster Director',
            bm: 'Branch Manager',
            ssm: 'Senior Sales Manager',
            sm: 'Sales Manager',
            jsm: 'Junior Sales',
            vrm: 'VRM',
        };
        const key = job.toLowerCase().replace(/[\s_-]+/g, '');
        return map[key] || job;
    }

    function entityMeta(n) {
        if (n.is_org_group) {
            const parts = [];
            const counts = childCounts(n);
            if (counts) parts.push(escapeHtml(counts));
            if (n.linked_user_name) {
                const cls = roleMeta(n).cls || 'other';
                parts.push(`<span class="st-ent-lead ${cls}">${escapeHtml(n.linked_user_name)}</span>`);
            }
            return parts.join(' · ');
        }
        // People: show job role under the name
        return escapeHtml(personRoleLabel(n));
    }

    function renderMobileCards(nodes, depth = 0, parentCollapsed = false, cards = [], pathPrefix = '', parentTitle = '') {
        (nodes || []).forEach((n, i) => {
            n.depth = depth;
            const hasKids = !!(n.children && n.children.length);
            const isCollapsed = showHierarchy ? collapsed.has(n.id) : false;
            const hidden = parentCollapsed;
            const num = pathPrefix ? `${pathPrefix}.${i + 1}` : String(i + 1);
            const title = entityTitle(n);
            if (!hidden) {
                const editable = !!n.editable;
                // Only hard-Locked blocks edit. Approved can be revised for next-month planning.
                // Org-hydrated amounts are never treated as locked person targets.
                const locked = String(n.status || '') === 'Locked' && !n.from_org_target;
                const isStaff = !hasKids;
                const eff = effectiveOf(n);
                const filteredOut = !matchesFilter(n);
                const rm = roleMeta(n);
                const meta = entityMeta(n);
                const typeLabel = n.is_org_group ? rm.label : personRoleLabel(n);
                const linkedEditable = !!(n.is_org_group && n.linked_user_id && editable && !locked);
                const canEditAdj = (editable && !locked && !isStaff && !n.is_org_group) || linkedEditable;
                const canEditBase = (editable && !locked && !n.is_org_group) || linkedEditable;
                const totalHtml = canEditBase
                    ? inputHtml(n, 'st-set', n.set_target, false, 'Total target for ' + escapeHtml(n.name))
                    : (n.is_org_group
                        ? `<span class="st-cell-num" data-set-for="${n.id}">${fmt(n.set_target)}</span>`
                        : inputHtml(n, 'st-set', n.set_target, true, 'Total target for ' + escapeHtml(n.name)));
                const ownerHtml = canEditAdj
                    ? inputHtml(n, 'st-owner', n.owner_target, false, 'Owner target for ' + escapeHtml(n.name))
                    : `<span class="st-cell-num" data-owner-for="${n.id}">${fmt(ownerDisplayed(n))}</span>`;
                cards.push(`
                    <article class="st-mcard ${n.amber ? 'st-amber' : ''} ${filteredOut ? 'is-filtered-out' : ''}" data-id="${n.id}" data-depth="${depth}">
                        <div class="st-mcard-top">
                            <button type="button" class="st-toggle ${hasKids && showHierarchy ? '' : 'is-leaf'}" data-toggle="${n.id}" aria-label="${isCollapsed ? 'Expand' : 'Collapse'}">
                                <i class="bi ${isCollapsed ? 'bi-chevron-right' : 'bi-chevron-down'}"></i>
                            </button>
                            <span class="st-ent-ico ${rm.cls}"><i class="bi ${rm.icon}"></i></span>
                            <div class="st-mcard-head">
                                <div class="st-mcard-title-row">
                                    <span class="st-mcard-idx">${num}</span>
                                    <span class="st-type ${rm.cls}">${escapeHtml(typeLabel)}</span>
                                </div>
                                <div class="st-mcard-name">${escapeHtml(title)}</div>
                                <div class="st-mcard-meta">${meta}</div>
                            </div>
                            <div class="st-mcard-actions">
                                <div class="st-action-dd" data-row-dd="${n.id}">
                                    <button type="button" class="st-btn-assign" data-assign-toggle="${n.id}" ${editable && !locked ? '' : 'disabled'}>
                                        Assign <i class="bi bi-chevron-down"></i>
                                    </button>
                                    <ul class="st-action-menu" role="menu">
                                        <li><button type="button" data-row-act="assign" data-id="${n.id}" ${editable && !locked ? '' : 'disabled'}><i class="bi bi-send"></i> Assign &amp; notify</button></li>
                                        <li><button type="button" data-row-act="draft" data-id="${n.id}" ${editable && !locked ? '' : 'disabled'}><i class="bi bi-floppy"></i> Save draft</button></li>
                                        <li><button type="button" data-row-act="copy-open" data-id="${n.id}" ${editable && !locked ? '' : 'disabled'}><i class="bi bi-arrow-down-left-square"></i> Use last month as total</button></li>
                                        <li><button type="button" data-row-act="clear" data-id="${n.id}" class="danger" ${editable && !locked ? '' : 'disabled'}><i class="bi bi-eraser"></i> Clear</button></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="st-mcard-grid">
                            <div class="st-mfield">
                                <span class="st-mfield-label">Last</span>
                                <span class="st-cell-num" data-open-for="${n.id}">${fmt(n.last_month)}</span>
                            </div>
                            <div class="st-mfield">
                                <span class="st-mfield-label">Total</span>
                                ${totalHtml}
                            </div>
                            <div class="st-mfield">
                                <span class="st-mfield-label">Owner</span>
                                ${ownerHtml}
                            </div>
                            <div class="st-mfield is-eff">
                                <span class="st-mfield-label">Remaining</span>
                                <span class="st-eff" data-total-for="${n.id}"><span class="st-rem-val">${fmt(eff)}</span></span>
                                ${hasKids ? `<span class="st-rem-caption">left to assign downline</span>` : ''}
                            </div>
                        </div>
                    </article>
                `);
            }
            if (hasKids && showHierarchy) {
                renderMobileCards(n.children, depth + 1, hidden || isCollapsed, cards, num, title);
            }
        });
        return cards;
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
                // Only hard-Locked blocks edit. Approved can be revised for next-month planning.
                // Org-hydrated amounts are never treated as locked person targets.
                const locked = String(n.status || '') === 'Locked' && !n.from_org_target;
                const isStaff = !hasKids;
                const eff = effectiveOf(n);
                const filteredOut = !matchesFilter(n);
                const rm = roleMeta(n);
                const meta = entityMeta(n);
                const typeLabel = n.is_org_group ? rm.label : personRoleLabel(n);
                const linkedEditable = !!(n.is_org_group && n.linked_user_id && editable && !locked);
                const canEditAdj = (editable && !locked && !isStaff && !n.is_org_group) || linkedEditable;
                const canEditBase = (editable && !locked && !n.is_org_group) || linkedEditable;
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
                                    <div class="st-ent-meta">${meta}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="st-type ${rm.cls}" title="${escapeHtml(typeLabel)}">${escapeHtml(typeLabel)}</span></td>
                        <td class="num"><span class="st-cell-num" data-open-for="${n.id}">${fmt(n.last_month)}</span></td>
                        <td class="num">
                            ${canEditBase
                                ? inputHtml(n, 'st-set', n.set_target, false, 'Total target for ' + escapeHtml(n.name))
                                : (n.is_org_group
                                    ? `<span class="st-cell-num" data-set-for="${n.id}">${fmt(n.set_target)}</span>`
                                    : inputHtml(n, 'st-set', n.set_target, true, 'Total target for ' + escapeHtml(n.name)))}
                        </td>
                        <td class="num">
                            ${canEditAdj
                                ? inputHtml(n, 'st-owner', n.owner_target, false, 'Owner target for ' + escapeHtml(n.name))
                                : (n.is_org_group || isStaff
                                    ? `<span class="st-cell-num" data-owner-for="${n.id}">${fmt(ownerDisplayed(n))}</span>`
                                    : inputHtml(n, 'st-owner', n.owner_target, true, 'Owner target for ' + escapeHtml(n.name)))}
                        </td>
                        <td class="num col-eff">
                            <div class="st-rem-stack">
                                <span class="st-eff" data-total-for="${n.id}">
                                    <span class="st-rem-val">${fmt(eff)}</span>
                                    <span class="st-rem-hint d-none" data-check-label="${n.id}"></span>
                                </span>
                                ${hasKids ? `<span class="st-rem-caption">left to assign downline</span>` : ''}
                            </div>
                        </td>
                        <td class="col-actions">
                            <div class="st-row-actions">
                                <div class="st-action-dd" data-row-dd="${n.id}">
                                    <button type="button" class="st-btn-assign" data-assign-toggle="${n.id}" ${editable && !locked ? '' : 'disabled'} title="Assign options">
                                        Assign <i class="bi bi-chevron-down"></i>
                                    </button>
                                    <ul class="st-action-menu" role="menu">
                                        <li><button type="button" data-row-act="assign" data-id="${n.id}" ${editable && !locked ? '' : 'disabled'}><i class="bi bi-send"></i> Assign &amp; notify</button></li>
                                        <li><button type="button" data-row-act="draft" data-id="${n.id}" ${editable && !locked ? '' : 'disabled'}><i class="bi bi-floppy"></i> Save draft</button></li>
                                        <li><button type="button" data-row-act="copy-open" data-id="${n.id}" ${editable && !locked ? '' : 'disabled'}><i class="bi bi-arrow-down-left-square"></i> Use last month as total</button></li>
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
            if (Number(n.id) === Number(id)) return n;
            const hit = findNode(n.children || [], id);
            if (hit) return hit;
        }
        return null;
    }

    function findParentNode(nodes, id) {
        for (const n of nodes || []) {
            if ((n.children || []).some((c) => Number(c.id) === Number(id))) return n;
            const deeper = findParentNode(n.children || [], id);
            if (deeper) return deeper;
        }
        return null;
    }

    function findOrgNodeByUnitId(nodes, orgUnitId) {
        const want = Number(orgUnitId);
        if (!want) return null;
        const syntheticId = -100000 - want;
        for (const n of nodes || []) {
            if (n.is_org_group && Number(n.id) === syntheticId) return n;
            const hit = findOrgNodeByUnitId(n.children || [], want);
            if (hit) return hit;
        }
        return null;
    }

    function findUserNode(nodes, userId) {
        const want = Number(userId);
        if (!want) return null;
        for (const n of nodes || []) {
            if (!n.is_org_group && Number(n.id) === want) return n;
            if (n.is_org_group && Number(n.linked_user_id) === want) return n;
            const hit = findUserNode(n.children || [], want);
            if (hit) return hit;
        }
        return null;
    }

    function pathIdsToRoot(id) {
        const path = [];
        let cur = findNode(state.tree || [], id);
        while (cur) {
            path.unshift(Number(cur.id));
            const parent = findParentNode(state.tree || [], cur.id);
            cur = parent;
        }
        return path;
    }

    function applyFocusFromUrl() {
        let focusOrg = 0;
        let focusUser = 0;
        try {
            const url = new URL(window.location.href);
            focusOrg = Number(url.searchParams.get('focus_org') || 0) || 0;
            focusUser = Number(url.searchParams.get('focus_user') || 0) || 0;
        } catch (e) { /* ignore */ }
        if (!focusOrg && !focusUser) return null;

        let target = focusUser
            ? findUserNode(state.tree || [], focusUser)
            : findOrgNodeByUnitId(state.tree || [], focusOrg);
        if (!target) return null;

        // Show full hierarchy so the focused node is reachable under Region roots
        applyFilter = 'rd';
        const regions = (state.tree || []).filter((n) => n.is_org_group && n.org_type === 'region');
        if (!regions.length) applyFilter = String(state.authority?.default_apply || 'rd');
        syncApplyToButtons();

        const path = pathIdsToRoot(target.id);
        // Expand every ancestor; keep the focused node expanded if it has kids
        path.forEach((id, idx) => {
            if (idx < path.length - 1) collapsed.delete(id);
        });
        if (target.children?.length) collapsed.delete(Number(target.id));

        return Number(target.id);
    }

    function highlightFocus(focusId) {
        if (!focusId) return;
        requestAnimationFrame(() => {
            const row = document.querySelector(`#stTreeBody tr[data-id="${focusId}"]`)
                || document.querySelector(`#stMobileList .st-mcard[data-id="${focusId}"]`);
            if (!row) return;
            row.classList.add('st-focus-row');
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            const input = row.querySelector('input.st-set:not([disabled]), input.st-owner:not([disabled])');
            if (input) {
                try { input.focus({ preventScroll: true }); } catch (e) { input.focus(); }
            }
            setTimeout(() => row.classList.remove('st-focus-row'), 4500);
        });
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
            adj += ownerDisplayed(n);
            eff += effectiveOf(n);
        });
        if ($('stFootLast')) $('stFootLast').textContent = fmt(open);
        if ($('stFootTarget')) $('stFootTarget').textContent = fmt(base);
        if ($('stFootPersonal')) $('stFootPersonal').textContent = fmt(adj);
        if ($('stFootTeam')) $('stFootTeam').textContent = fmt(eff);
        const m = $('stMobileList');
        if (m) {
            const vals = m.querySelectorAll('.st-mobile-totals [data-mfoot]');
            vals.forEach((el) => {
                const key = el.getAttribute('data-mfoot');
                if (key === 'last') el.textContent = fmt(open);
                if (key === 'total') el.textContent = fmt(base);
                if (key === 'owner') el.textContent = fmt(adj);
                if (key === 'rem') el.textContent = fmt(eff);
            });
        }
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
        if ($('stKpiAreaMeta')) $('stKpiAreaMeta').textContent = 'Last month target ' + money(revenueLast);

        if ($('stKpiLast')) $('stKpiLast').textContent = money(disbVal);
        if ($('stKpiLastLabel')) $('stKpiLastLabel').textContent = 'Total Disbursement Target';
        if ($('stKpiLastMeta')) $('stKpiLastMeta').textContent = 'Last month target ' + money(disbLast);

        // Opening shortfall card = carried shortfall at Apply To roots
        let shortfall = openingAll;
        if (receivesTarget() && state.self) {
            shortfall = Number(state.self.last_month || 0);
        }
        if ($('stKpiRemaining')) $('stKpiRemaining').textContent = money(Math.max(0, shortfall));
        if ($('stKpiLeftLabel')) $('stKpiLeftLabel').textContent = 'Last Month Target (Revenue)';
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

        const canEdit = state.can_edit !== false && state.authority?.can_edit !== false;
        let canAssign = canEdit && (state.tree || []).length > 0 && overs.length === 0;
        const blocking = [];
        if (receivesTarget() && state.self) {
            const left = Number(state.self.left_to_distribute || 0);
            const ok = Math.abs(left) <= 0.05;
            blocking.push({ id: state.self.id, ok });
            if (!ok) canAssign = false;
        }

        state.checks = blocking;
        state.can_assign = canAssign;
        state.can_edit = canEdit;

        const tip = $('stAuthTip');
        if (tip && state.authority?.message) {
            const msg = String(state.authority.message);
            const full = tip.querySelector('.st-footer-tip-full');
            const short = tip.querySelector('.st-footer-tip-short');
            if (full && short) {
                full.textContent = msg;
                short.textContent = canEdit
                    ? 'Edit team below you · own target is view-only'
                    : 'View only — your authority sets your target';
            } else {
                tip.textContent = msg;
            }
        }

        const sum = $('stCheckSummary');
        const saveBtn = $('stSaveDraft');
        const saveTop = $('stSaveDraftTop');
        const assignBtn = $('stAssign');
        const assignTop = $('stAssignTop');

        if (!canEdit) {
            sum.className = 'warn';
            sum.innerHTML = '<i class="bi bi-lock-fill" aria-hidden="true"></i> View only — your authority sets your target';
        } else if (overs.length) {
            sum.className = 'warn';
            const overMsg = overs[0]?.label || 'Children exceed Remaining pool';
            sum.innerHTML = '<i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> ' + escapeHtml(overMsg);
        } else if (receivesTarget() && blocking.some((b) => !b.ok)) {
            sum.className = 'warn';
            sum.innerHTML = '<i class="bi bi-share" aria-hidden="true"></i> Finish assigning your remainder to unlock Assign';
        } else if (!(state.tree || []).length) {
            sum.className = 'warn';
            sum.innerHTML = '<i class="bi bi-people" aria-hidden="true"></i> No team below you in your authorized scope';
        } else {
            sum.className = 'ok';
            sum.innerHTML = '<i class="bi bi-check-circle-fill" aria-hidden="true"></i> Ready to save or assign';
        }

        const assignDisabled = !state.can_assign;
        const saveDisabled = !canEdit;
        if (assignBtn) {
            assignBtn.disabled = assignDisabled;
            assignBtn.classList.toggle('st-btn-primary', !assignDisabled);
        }
        if (assignTop) assignTop.disabled = assignDisabled;
        if (saveBtn) {
            saveBtn.disabled = saveDisabled;
            saveBtn.classList.toggle('st-btn-primary', canEdit && assignDisabled);
        }
        if (saveTop) {
            saveTop.disabled = saveDisabled;
            saveTop.classList.toggle('st-btn-primary', canEdit && assignDisabled);
        }
        updateKpis();
    }

    function refreshDerived() {
        recompute(state.tree || []);
        const walk = (nodes) => {
            (nodes || []).forEach((n) => {
                document.querySelectorAll(`[data-id="${n.id}"]`).forEach((row) => {
                    row.classList.toggle('st-amber', !!n.amber);
                    const totalCell = row.querySelector(`[data-total-for="${n.id}"]`);
                    if (totalCell) {
                        const valEl = totalCell.querySelector('.st-rem-val') || totalCell;
                        if (valEl) valEl.textContent = fmt(effectiveOf(n));
                    }
                    const setSpan = row.querySelector(`[data-set-for="${n.id}"]`);
                    if (setSpan) setSpan.textContent = fmt(n.set_target);
                    if (n.is_org_group && !n.linked_user_id) {
                        const openCell = row.querySelector(`[data-open-for="${n.id}"]`);
                        if (openCell) openCell.textContent = fmt(n.last_month);
                    }
                    const ownerSpan = row.querySelector(`[data-owner-for="${n.id}"]`);
                    if (ownerSpan) {
                        ownerSpan.textContent = fmt(ownerDisplayed(n));
                    }
                });
                if (n.children?.length) walk(n.children);
            });
        };
        walk(state.tree || []);
        updateCheckSummary();
        updateFooters();
    }

    function render() {
        recompute(state.tree || []);
        const roots = getDisplayRoots();
        const rows = renderRows(roots, 0, false, [], '');
        $('stTreeBody').innerHTML = rows.join('') || `
            <tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:36px">No matching people for this filter</td></tr>`;
        const mobile = $('stMobileList');
        if (mobile) {
            const cards = renderMobileCards(roots, 0, false, [], '');
            let open = 0, base = 0, adj = 0, rem = 0;
            (roots || []).forEach((n) => {
                open += Number(n.last_month || 0);
                base += Number(n.set_target || 0);
                adj += ownerDisplayed(n);
                rem += effectiveOf(n);
            });
            const totals = roots.length ? `
                <div class="st-mobile-totals">
                    <div class="st-mfield"><span class="st-mfield-label">Last</span><div class="st-mfield-val" data-mfoot="last">${fmt(open)}</div></div>
                    <div class="st-mfield"><span class="st-mfield-label">Total</span><div class="st-mfield-val" data-mfoot="total">${fmt(base)}</div></div>
                    <div class="st-mfield"><span class="st-mfield-label">Owner</span><div class="st-mfield-val" data-mfoot="owner">${fmt(adj)}</div></div>
                    <div class="st-mfield"><span class="st-mfield-label">Remaining</span><div class="st-mfield-val" data-mfoot="rem">${fmt(rem)}</div><span class="st-rem-caption">left to assign downline</span></div>
                </div>` : '';
            mobile.innerHTML = (cards.join('') || `<div class="st-mcard-empty">No matching people for this filter</div>`) + totals;
        }
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
        const mobile = $('stMobileList');
        if (mobile) {
            mobile.innerHTML = `<div class="st-mcard-empty"><i class="bi bi-arrow-repeat" style="margin-right:6px"></i>Loading ${escapeHtml(period)}…</div>`;
        }

        const json = await api(`${routes.tree}?period=${encodeURIComponent(period)}&metric=${encodeURIComponent(metric)}`);
        state = json.data || state;
        state.period = period;
        state.metric = metric;
        if (state.authority?.default_apply) {
            applyFilter = String(state.authority.default_apply);
            syncApplyToButtons();
        }
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

    function onTargetInput(e) {
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
                    document.querySelectorAll(`input.st-owner[data-id="${id}"]`).forEach((ownerInput) => {
                        if (document.activeElement !== ownerInput) ownerInput.value = String(maxOwner);
                    });
                }
            }
        }
        if (el.classList.contains('st-owner')) {
            const set = Number(node.set_target || 0);
            let owner = num;
            if (owner > set) {
                owner = set;
                el.value = String(owner);
                notifyCap('Owner Target cannot exceed Total Target.');
            }
            const kids = (node.children || []).reduce((s, c) => s + Number(c.set_target || 0), 0);
            const ownerCap = Math.max(0, Math.round((set - kids) * 100) / 100);
            if (owner > ownerCap + 0.0001) {
                owner = ownerCap;
                el.value = String(owner);
                notifyCap(`Owner Target max is ${fmt(ownerCap)}.`);
            }
            node.owner_target = owner;
        }
        node.row_source = 'edited';
        node.chip = 'Edited';
        refreshDerived();
    }

    function onTargetBlur(e) {
        const el = e.target;
        if (!el.classList.contains('st-set') && !el.classList.contains('st-owner')) return;
        const num = sanitizeDecimal(el);
        if (num === null) return;
        el.value = String(num);
    }

    function positionActionMenu(toggle, menu) {
        if (!toggle || !menu) return;
        const rect = toggle.getBoundingClientRect();
        const menuW = Math.max(190, menu.offsetWidth || 190);
        const menuH = menu.offsetHeight || 160;
        let top = rect.bottom + 4;
        let left = rect.right - menuW;
        if (top + menuH > window.innerHeight - 8) top = Math.max(8, rect.top - menuH - 4);
        if (left < 8) left = 8;
        if (left + menuW > window.innerWidth - 8) left = window.innerWidth - menuW - 8;
        menu.style.top = `${top}px`;
        menu.style.left = `${left}px`;
        menu.style.right = 'auto';
    }

    function onRowActionClick(e) {
        const toggle = e.target.closest('[data-assign-toggle], [data-more-toggle]');
        if (toggle) {
            e.preventDefault();
            e.stopPropagation();
            if (toggle.disabled) return;
            const dd = toggle.closest('.st-action-dd');
            const menu = dd?.querySelector('.st-action-menu');
            const willOpen = !dd.classList.contains('is-open');
            closeRowMenus();
            if (willOpen && dd) {
                dd.classList.add('is-open');
                const row = toggle.closest('tr, .st-mcard');
                if (row) row.classList.add('st-menu-open');
                if (menu) {
                    // Measure after display:block, then pin to viewport (escapes table stacking)
                    positionActionMenu(toggle, menu);
                    requestAnimationFrame(() => positionActionMenu(toggle, menu));
                }
            }
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
            flash(`Total Target set from Last Month Target (${fmt(open)}) for ${node.linked_user_name || node.name}. Save or Assign when ready.`, 'ok');
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
                flash('Enter a Total Target for this person first (Last Month Target is from the previous period).', 'warn');
                return;
            }
            const label = node.linked_user_name || node.name;
            if (!confirm(`Assign target for ${label} and notify them?`)) return;
            persist(true, [id]).catch((err) => flash(err.message, 'err'));
        }
    }

    $('stTreeBody').addEventListener('input', onTargetInput);
    $('stTreeBody').addEventListener('blur', onTargetBlur, true);
    const stMobileListEl = $('stMobileList');
    if (stMobileListEl) {
        stMobileListEl.addEventListener('input', onTargetInput);
        stMobileListEl.addEventListener('blur', onTargetBlur, true);
    }

    function expandSearchHits(nodes) {
        (nodes || []).forEach((n) => {
            if (!n.children?.length) return;
            const childHit = (n.children || []).some((c) => hasSearchHit(c));
            if (childHit) collapsed.delete(n.id);
            expandSearchHits(n.children);
        });
    }

    function applyRowVisibility() {
        document.querySelectorAll('#stTreeBody tr[data-id], #stMobileList .st-mcard[data-id]').forEach((el) => {
            const id = Number(el.getAttribute('data-id'));
            const n = findNode(state.tree, id);
            if (!n) return;
            el.classList.toggle('is-filtered-out', !matchesFilter(n));
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
        document.querySelectorAll('.goal-set-targets .st-action-dd.is-open').forEach((el) => {
            el.classList.remove('is-open');
            const menu = el.querySelector('.st-action-menu');
            if (menu) {
                menu.style.top = '';
                menu.style.left = '';
                menu.style.right = '';
            }
        });
        document.querySelectorAll('.goal-set-targets tr.st-menu-open, .goal-set-targets .st-mcard.st-menu-open')
            .forEach((el) => el.classList.remove('st-menu-open'));
    }

    function collectRowsForIds(ids) {
        const want = new Set();
        (ids || []).forEach((raw) => {
            const id = Number(raw);
            want.add(id);
            const n = findNode(state.tree, id);
            if (n?.linked_user_id) want.add(Number(n.linked_user_id));
        });
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
            flash('Enter Total Target amounts before Assign. Last Month Target is from the previous period.', 'warn');
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
                flash('This period’s Total Targets are still 0. Enter Total Target, then Assign. Last Month Target is from the previous period.', 'warn');
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

    $('stTreeBody').addEventListener('click', onRowActionClick);
    if (stMobileListEl) stMobileListEl.addEventListener('click', onRowActionClick);

    document.addEventListener('click', () => closeRowMenus());
    document.addEventListener('scroll', () => closeRowMenus(), true);
    window.addEventListener('resize', () => closeRowMenus());

    syncApplyToButtons();
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
    const focusId = applyFocusFromUrl();
    render();
    highlightFocus(focusId);
    loadAudit();

    (function bindColumnTips() {
        let bubble = document.getElementById('stTipBubble');
        if (!bubble) {
            bubble = document.createElement('div');
            bubble.id = 'stTipBubble';
            bubble.className = 'st-tip-bubble';
            bubble.setAttribute('role', 'tooltip');
            document.body.appendChild(bubble);
        }
        let hideTimer = null;
        const showTip = (el) => {
            const text = el.getAttribute('data-st-tip') || '';
            if (!text) return;
            clearTimeout(hideTimer);
            bubble.textContent = text;
            bubble.classList.remove('is-below');
            bubble.classList.add('is-on');
            const rect = el.getBoundingClientRect();
            const tipW = bubble.offsetWidth || 200;
            const tipH = bubble.offsetHeight || 40;
            let left = rect.left + (rect.width / 2) - (tipW / 2);
            let top = rect.top - tipH - 10;
            if (top < 8) {
                top = rect.bottom + 10;
                bubble.classList.add('is-below');
            }
            if (left < 8) left = 8;
            if (left + tipW > window.innerWidth - 8) left = window.innerWidth - tipW - 8;
            bubble.style.left = `${left}px`;
            bubble.style.top = `${top}px`;
        };
        const hideTip = () => {
            hideTimer = setTimeout(() => bubble.classList.remove('is-on'), 40);
        };
        document.querySelectorAll('.goal-set-targets [data-st-tip]').forEach((el) => {
            el.addEventListener('mouseenter', () => showTip(el));
            el.addEventListener('mouseleave', hideTip);
            el.addEventListener('focus', () => showTip(el));
            el.addEventListener('blur', hideTip);
        });
        document.addEventListener('scroll', () => bubble.classList.remove('is-on'), true);
        window.addEventListener('resize', () => bubble.classList.remove('is-on'));
    })();
})();
</script>
@endpush
