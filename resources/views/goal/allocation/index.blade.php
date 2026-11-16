@extends('layouts.app')

@php
    $salesLabel = function ($user) {
        if (!$user) return '—';
        $roleKey = \App\Services\Goal\GoalDashboardService::ROLE_KEYS[(int) ($user->crm_role_type ?? -1)] ?? null;
        $job = strtolower((string) ($user->job_role ?? ''));
        $code = strtoupper($roleKey ?: ($job ?: 'SALES'));
        return $code . ' - ' . $user->name;
    };

    $priorityFor = function ($lead) {
        $type = strtolower((string) ($lead->lead_type ?? ''));
        if ($type === 'immediate') return 'High';
        if ($type === 'future') return 'Medium';
        return 'Low';
    };

    $activeTab = in_array(($tab ?? 'pending'), ['pending', 'assigned'], true) ? $tab : 'pending';
    $kpis = $kpis ?? [
        'pending' => $pending->count(),
        'assigned_today' => 0,
        'assigned_today_trend' => null,
        'reassigned_today' => 0,
        'reassigned_today_trend' => null,
        'month_total' => 0,
        'month_total_trend' => null,
    ];

    $salesPayload = $sales->map(fn ($s) => [
        'id' => $s->id,
        'label' => $salesLabel($s),
        'branch_id' => $s->goal_org_unit_id ? (int) $s->goal_org_unit_id : null,
    ])->values();
@endphp

@push('styles')
<style>
.ba-page{
    --ba-blue:#3b82f6;--ba-indigo:#6366f1;--ba-navy:#0f2744;--ba-ink:#0f172a;--ba-muted:#64748b;--ba-line:#dbe7f5;
    --ba-grad:linear-gradient(90deg,#3b82f6 0%,#6366f1 55%,#7c6cf0 100%);
    color:#1e293b;
    background:linear-gradient(180deg,#eef5ff 0%,#f5f8ff 40%,#f8fafc 100%);
    min-height:100%;
    margin:-0.25rem -0.5rem;padding:1.25rem 1.5rem 2rem !important;
}
.ba-page .ba-hero{
    position:relative;overflow:hidden;border-radius:22px;padding:1.25rem 1.4rem;
    margin-bottom:1rem;
    background:linear-gradient(120deg,#eef4ff 0%,#f5f3ff 48%,#eef8ff 100%);
    border:1px solid #dde7ff;box-shadow:0 12px 28px rgba(59,130,246,.08);
}
.ba-page .ba-hero-row{display:flex;align-items:center;justify-content:space-between;gap:1rem}
.ba-page .ba-hero-left{display:flex;align-items:flex-start;gap:14px;min-width:0}
.ba-page .ba-hero-ico{
    width:52px;height:52px;border-radius:16px;flex-shrink:0;color:#fff;
    background:linear-gradient(145deg,#3b82f6,#4f46e5);
    display:inline-flex;align-items:center;justify-content:center;font-size:1.35rem;
    box-shadow:0 10px 22px rgba(59,130,246,.28);
}
.ba-page .ba-title{margin:0;font-size:1.55rem;font-weight:800;color:var(--ba-ink);letter-spacing:-.02em}
.ba-page .ba-sub{margin:.35rem 0 0;color:var(--ba-muted);font-size:.9rem;max-width:46rem}
.ba-page .ba-hero-art{
    width:72px;height:72px;border-radius:18px;flex-shrink:0;
    background:rgba(255,255,255,.75);border:1px solid #dbeafe;color:#4f46e5;
    display:none;align-items:center;justify-content:center;font-size:1.8rem;
}
@media(min-width:900px){.ba-page .ba-hero-art{display:inline-flex}}

.ba-page .ba-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:1rem}
.ba-page .ba-kpi{
    --kpi-ink:#7c3aed;--kpi-soft:#f3e8ff;--kpi-wave:#e9d5ff;
    position:relative;overflow:hidden;isolation:isolate;
    border-radius:16px;padding:1rem 1.1rem;
    background:#fff;border:1px solid rgba(226,232,240,.9);
    box-shadow:
        0 1px 2px rgba(15,23,42,.04),
        0 10px 28px rgba(148,163,184,.16),
        0 22px 48px rgba(148,163,184,.10);
    min-height:96px;
    display:flex !important;flex-direction:row !important;align-items:center;gap:12px;
}
.ba-page .ba-kpi.tone-purple{--kpi-ink:#7c3aed;--kpi-soft:#f3e8ff;--kpi-wave:#e9d5ff;--kpi-wave-2:#f5d0fe}
.ba-page .ba-kpi.tone-green{--kpi-ink:#16a34a;--kpi-soft:#dcfce7;--kpi-wave:#bbf7d0;--kpi-wave-2:#a7f3d0}
.ba-page .ba-kpi.tone-blue{--kpi-ink:#2563eb;--kpi-soft:#dbeafe;--kpi-wave:#bfdbfe;--kpi-wave-2:#a5b4fc}
.ba-page .ba-kpi.tone-orange{--kpi-ink:#ea580c;--kpi-soft:#ffedd5;--kpi-wave:#fed7aa;--kpi-wave-2:#fde68a}
/* Soft pastel icon — LEFT */
.ba-page .ba-kpi-ico{
    width:42px;height:42px;border-radius:12px;flex:0 0 42px;
    display:inline-flex !important;align-items:center;justify-content:center;order:0;
    background:var(--kpi-soft) !important;color:var(--kpi-ink) !important;font-size:1.15rem;
    position:relative;z-index:3;
}
.ba-page .ba-kpi-body{position:relative;z-index:3;min-width:0;flex:1 1 auto;order:1}
.ba-page .ba-kpi-label{
    margin:0;font-size:.78rem;font-weight:600;color:#1e3a5f;line-height:1.25;
}
.ba-page .ba-kpi-metric{
    display:flex;align-items:center;flex-wrap:wrap;gap:8px;
    margin-top:.35rem;
}
.ba-page .ba-kpi-value{
    margin:0;font-size:1.75rem;font-weight:800;color:#0f172a;line-height:1;letter-spacing:-.03em;
}
.ba-page .ba-kpi-trend{
    display:inline-flex;align-items:center;gap:2px;
    padding:.18rem .5rem;border-radius:999px;
    background:rgba(34,197,94,.14);color:#16a34a;
    font-size:.7rem;font-weight:700;line-height:1.2;
}
.ba-page .ba-kpi-trend.is-down{background:rgba(239,68,68,.12);color:#dc2626}
.ba-page .ba-kpi-trend i{font-size:.68rem}
/* Soft blob / wave on bottom-right (2nd mockup) — NOT a full-width sparkline */
.ba-page .ba-kpi-graph{
    position:absolute !important;right:-8px;bottom:-18px;left:auto;top:auto;
    width:54%;height:78px;z-index:1;pointer-events:none;opacity:.95;
}
.ba-page .ba-kpi-graph svg{width:100%;height:100%;display:block}

/* Tabs — exact reference: active blue gradient, inactive white */
.ba-page .ba-tabs{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:14px}
.ba-page .ba-tab{
    appearance:none;border:1px solid #d7e3f4;background:#fff;color:#0f2744;cursor:pointer;
    border-radius:999px;padding:.62rem 1.05rem;font-weight:700;font-size:.88rem;
    display:inline-flex;align-items:center;gap:8px;
    box-shadow:0 4px 12px rgba(148,163,184,.10);transition:box-shadow .15s,transform .15s;
}
.ba-page .ba-tab.active{
    border-color:transparent;color:#fff;
    background:var(--ba-grad);
    box-shadow:0 10px 20px rgba(59,130,246,.32);
}
.ba-page .ba-tab .count{
    min-width:22px;height:22px;border-radius:999px;padding:0 7px;
    display:inline-flex;align-items:center;justify-content:center;
    font-size:.7rem;font-weight:800;background:#e2e8f0;color:#334155;
}
.ba-page .ba-tab.active .count{background:#1e3a8a;color:#fff}

.ba-page .ba-card{
    background:#fff;border:1px solid rgba(226,232,240,.9);border-radius:16px;
    box-shadow:
        0 1px 2px rgba(15,23,42,.04),
        0 10px 28px rgba(148,163,184,.16),
        0 22px 48px rgba(148,163,184,.10);
    overflow:hidden;margin-bottom:1rem;
}
.ba-page .ba-card-head{
    display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem;
    padding:.95rem 1.15rem;
    background:linear-gradient(180deg,#f5f9ff 0%,#eef5ff 100%);
    border-bottom:1px solid #d9e7f8;
}
.ba-page .ba-card-head h5{
    margin:0;font-size:1.05rem;font-weight:800;color:#0f2744;
    display:inline-flex;align-items:center;gap:10px;
}
.ba-page .ba-head-ico{
    width:34px;height:34px;border-radius:999px;flex-shrink:0;
    display:inline-flex;align-items:center;justify-content:center;
    background:linear-gradient(145deg,#3b82f6,#2563eb);color:#fff;font-size:.95rem;
    box-shadow:0 6px 14px rgba(37,99,235,.28);
}
.ba-page .ba-head-branch{
    position:relative;display:inline-flex;align-items:center;min-width:190px;
}
.ba-page .ba-head-branch > i{
    position:absolute;left:12px;z-index:2;color:#64748b;pointer-events:none;font-size:.9rem;
}
.ba-page .ba-head-branch select.form-select{
    min-height:36px;border-radius:10px;border:1px solid #cfdced;background:#fff;
    padding:.35rem 2rem .35rem 34px;font-size:.85rem;font-weight:600;color:#0f2744;
}
.ba-page .ba-head-branch .select2-container{min-width:190px;width:190px!important}
.ba-page .ba-head-branch .select2-container--default .select2-selection--single{
    min-height:36px;height:36px;border-radius:10px;border:1px solid #cfdced;
}
.ba-page .ba-head-branch .select2-container--default .select2-selection--single .select2-selection__rendered{
    padding-left:34px;padding-right:28px;line-height:34px;font-size:.85rem;font-weight:600;color:#0f2744;
}
.ba-page .ba-head-branch .select2-container--default .select2-selection--single .select2-selection__arrow{
    height:34px;
}

.ba-page .ba-filters{
    display:flex;flex-wrap:nowrap;align-items:center;gap:10px;
    padding:.85rem 1.15rem;border-bottom:1px solid #e4eef9;background:#f8fbff;
}
.ba-page .ba-filters .ba-search{position:relative;flex:1 1 240px;min-width:180px}
.ba-page .ba-filters .ba-search > i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8}
.ba-page .ba-field{
    height:36px;min-height:36px;border-radius:10px;border:1px solid #c9d7e8;background:#fff;
    font-size:.84rem;padding:.3rem .75rem;color:#0f172a;
}
.ba-page .ba-filters .ba-search .ba-field{width:100%;padding-left:34px}
.ba-page .ba-filters select.ba-field{
    min-width:128px;flex:0 0 auto;
}
.ba-page .ba-filters .select2-container{min-width:128px;flex:0 0 auto;width:auto!important}
.ba-page .ba-filters .select2-container--default .select2-selection--single{
    min-height:36px;height:36px;border-radius:10px;border:1px solid #c9d7e8;
}
.ba-page .ba-filters .select2-container--default .select2-selection--single .select2-selection__rendered{
    line-height:34px;font-size:.84rem;padding-left:.75rem;padding-right:28px;color:#0f172a;
}
.ba-page .ba-filters .select2-container--default .select2-selection--single .select2-selection__arrow{height:34px}
.ba-page .ba-btn-reset{
    height:36px;border-radius:10px;border:1px solid #bfdbfe;background:#fff;color:#2563eb;
    font-weight:700;font-size:.84rem;padding:0 12px;
    display:inline-flex;align-items:center;gap:6px;white-space:nowrap;
}
.ba-page .ba-btn-reset:hover{background:#eff6ff}
.ba-page .ba-btn-bulk{
    height:36px;border:0;border-radius:10px;padding:0 14px;margin-left:auto;white-space:nowrap;
    background:var(--ba-grad);color:#fff;font-weight:700;font-size:.84rem;
    display:inline-flex;align-items:center;gap:7px;box-shadow:0 8px 16px rgba(79,70,229,.26);
}
.ba-page .ba-btn-bulk:disabled{opacity:.5;cursor:not-allowed}

.ba-page .ba-table-shell{
    padding:1rem 1.15rem 0;
}
.ba-page .ba-table-shell > .ba-dt-foot{
    margin:0 -1.15rem 0;border-radius:0 0 16px 16px;
}
.ba-page .ba-table-wrap{
    overflow-x:auto;max-height:560px;background:#fff;
    border:1px solid #e8edf5;border-radius:14px;
}
.ba-page .ba-table-wrap > table,
.ba-page .ba-table-wrap .dataTables_wrapper,
.ba-page .dataTables_wrapper{
    width:100% !important;
}
.ba-page .ba-table-wrap > table,
.ba-page table.dataTable{
    display:table !important;width:100% !important;margin:0 !important;
    border-collapse:separate !important;border-spacing:0 !important;
}
.ba-page .ba-table-wrap > table thead,
.ba-page table.dataTable thead{display:table-header-group !important;visibility:visible !important}
.ba-page .ba-table-wrap > table thead th,
.ba-page table.dataTable thead th{
    position:sticky;top:0;z-index:2;
    display:table-cell !important;width:auto !important;
    background:#f1f5f9 !important;color:#64748b !important;
    font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.03em;
    border-bottom:1px solid #e2e8f0 !important;
    white-space:nowrap;padding:.8rem .95rem !important;
}
.ba-page .ba-table-wrap > table tbody,
.ba-page table.dataTable tbody{display:table-row-group !important}
.ba-page .ba-table-wrap > table tr,
.ba-page table.dataTable tr{
    display:table-row !important;width:auto !important;margin:0 !important;border:0 !important;
    background:transparent !important;box-shadow:none !important;padding:0 !important;
}
.ba-page .ba-table-wrap > table tbody td,
.ba-page table.dataTable tbody td{
    display:table-cell !important;width:auto !important;vertical-align:middle !important;
    padding:.85rem .95rem !important;border-bottom:1px solid #eef2f7 !important;
    font-size:.875rem;white-space:nowrap;background:#fff;color:#334155;
}
.ba-page .ba-table-wrap > table tbody td::before{content:none !important;display:none !important}
.ba-page .ba-table-wrap > table tbody tr:hover td,
.ba-page table.dataTable tbody tr:hover td{background:#f8fbff !important}
.ba-page .ba-table-wrap > table tbody tr:last-child td{border-bottom:0 !important}
.ba-page .ba-actions{
    display:inline-flex;align-items:center;gap:8px;flex-wrap:nowrap;
}

.ba-page .ref-link{font-weight:600;color:#2563eb;text-decoration:underline;text-underline-offset:2px}
.ba-page .ref-link:hover{color:#1d4ed8}
.ba-page .cust{display:inline-flex;align-items:center;gap:8px}
.ba-page .avatar{
    width:28px;height:28px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;
    background:#dbeafe;color:#1d4ed8;font-size:.72rem;font-weight:800;
}
.ba-page .meta{display:inline-flex;align-items:center;gap:5px;color:#334155}
.ba-page .meta i{color:#94a3b8}
.ba-page .pill{display:inline-flex;align-items:center;padding:.2rem .55rem;border-radius:999px;font-size:.7rem;font-weight:700}
.ba-page .pill-green{background:#dcfce7;color:#15803d}
.ba-page .pill-blue{background:#dbeafe;color:#1d4ed8}
.ba-page .pill-red{background:#fee2e2;color:#dc2626}
.ba-page .pill-amber{background:#ffedd5;color:#c2410c}
.ba-page .pill-slate{background:#e0e7ff;color:#3730a3}

.ba-page select.ba-sales{
    width:100%;min-width:180px;max-width:240px;height:34px;min-height:34px;
    border-radius:9px;border:1px solid #bfd3ea;background:#fff;
    font-size:.8rem;padding:.2rem .55rem;color:#0f172a;
}
.ba-page td .select2-container{min-width:180px;max-width:240px;width:100%!important}
.ba-page td .select2-container--default .select2-selection--single{
    min-height:34px;height:34px;border-radius:9px;border:1px solid #bfd3ea;
}
.ba-page td .select2-container--default .select2-selection--single .select2-selection__rendered{
    line-height:32px;font-size:.8rem;padding-left:.55rem;padding-right:26px;color:#0f172a;
}
.ba-page td .select2-container--default .select2-selection--single .select2-selection__arrow{height:32px}
.ba-page td .select2-container--default .select2-selection--single .select2-selection__placeholder{color:#94a3b8}
.ba-page .btn-row-assign{
    height:34px;border:0;border-radius:9px;padding:0 12px;white-space:nowrap;
    background:linear-gradient(90deg,#3b82f6 0%,#2563eb 100%);color:#fff;font-weight:700;font-size:.78rem;
    display:inline-flex;align-items:center;gap:6px;box-shadow:0 6px 12px rgba(37,99,235,.22);
}
.ba-page .btn-row-assign:disabled{opacity:.45;box-shadow:none}
.ba-page .icon-btn{
    width:34px;height:34px;border-radius:10px;border:1.5px solid #bfdbfe;background:#eff6ff;color:#2563eb;
    display:inline-flex;align-items:center;justify-content:center;transition:.15s ease;
}
.ba-page .icon-btn:hover{background:#dbeafe;color:#1d4ed8;border-color:#93c5fd}
.ba-page .btn-reassign{
    height:34px;border-radius:10px;border:1px solid #c7d2fe;background:#eef2ff;color:#3730a3;
    font-size:.78rem;font-weight:700;padding:0 12px;display:inline-flex;align-items:center;gap:6px;
}
.ba-page .btn-reassign:hover{background:#e0e7ff;color:#312e81}
.ba-page .empty{padding:2rem;text-align:center;color:#64748b}
.ba-page .panel-hidden{display:none!important}
.ba-page .row-hidden{display:none!important}

.ba-page .select2-container{width:100%!important}
.ba-page .select2-dropdown{
    border:1px solid #d7dee8;border-radius:10px;overflow:hidden;
    box-shadow:0 12px 28px rgba(15,23,42,.12);
}
.ba-page + .select2-container .select2-dropdown,
body > .select2-container .select2-dropdown{
    border:1px solid #d7dee8;border-radius:10px;overflow:hidden;
    box-shadow:0 12px 28px rgba(15,23,42,.12);
}

.ba-modal .modal-content{border:0;border-radius:16px}
.ba-modal .form-control,.ba-modal .form-select{min-height:40px;border-radius:10px}
.ba-modal textarea.form-control{min-height:96px}

@media(max-width:1100px){
    .ba-page .ba-filters{flex-wrap:wrap}
    .ba-page .ba-btn-bulk{margin-left:0}
}
@media(max-width:900px){.ba-page .ba-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:560px){
    .ba-page .ba-kpis{grid-template-columns:1fr}
    .ba-page .ba-filters .ba-search,.ba-page .ba-filters select.ba-field,.ba-page .ba-btn-reset,.ba-page .ba-btn-bulk{flex:1 1 100%;width:100%}
    .ba-page .ba-btn-bulk{justify-content:center}
}
</style>
@endpush

@section('content')
<div class="container-fluid p-4 ba-page"
     data-bulk-url="{{ route('goal.allocation.bulk') }}"
     data-csrf="{{ csrf_token() }}">
    <div class="ba-hero">
        <div class="ba-hero-row">
            <div class="ba-hero-left">
                <div class="ba-hero-ico"><i class="bi bi-people-fill"></i></div>
                <div>
                    <h1 class="ba-title">BM Allocation</h1>
                    <p class="ba-sub">Assign or reassign leads/cases to Sales team (JSM / SM / SSM) under the Branch Manager.</p>
                </div>
            </div>
            <div class="ba-hero-art" aria-hidden="true"><i class="bi bi-clipboard2-check"></i></div>
        </div>
    </div>

    @php
        $kpiGraph = function (string $c1, string $c2, string $id) {
            // Soft abstract wave-blob (2nd image) — no hard sparkline stroke
            return '<svg viewBox="0 0 160 90" preserveAspectRatio="xMaxYMax meet" aria-hidden="true">'
                . '<defs><linearGradient id="' . e($id) . '" x1="0" y1="0" x2="1" y2="1">'
                . '<stop offset="0%" stop-color="' . e($c1) . '" stop-opacity="0.55"/>'
                . '<stop offset="55%" stop-color="' . e($c2) . '" stop-opacity="0.35"/>'
                . '<stop offset="100%" stop-color="' . e($c1) . '" stop-opacity="0.08"/>'
                . '</linearGradient></defs>'
                . '<path d="M8 70 C28 58 36 28 58 32 C78 36 82 58 102 52 C118 48 128 22 148 28 C156 30 160 40 160 48 L160 90 L0 90 L0 78 C2 74 4 72 8 70 Z" fill="url(#' . e($id) . ')"/>'
                . '<path d="M20 78 C40 66 48 40 70 44 C90 48 94 66 114 60 C128 56 136 38 152 42" fill="none" stroke="' . e($c1) . '" stroke-width="1.4" stroke-linecap="round" opacity="0.35"/>'
                . '</svg>';
        };
        $trendBadge = function (?string $trend) {
            if (!$trend) return '';
            $down = str_starts_with(ltrim($trend), '-');
            $icon = $down ? 'bi-arrow-down-short' : 'bi-arrow-up-short';
            $cls = $down ? 'ba-kpi-trend is-down' : 'ba-kpi-trend';
            return '<span class="' . $cls . '"><i class="bi ' . $icon . '"></i>' . e($trend) . '</span>';
        };
    @endphp
    <div class="ba-kpis">
        <div class="ba-kpi tone-purple">
            <span class="ba-kpi-ico"><i class="bi bi-clipboard2-data-fill"></i></span>
            <div class="ba-kpi-body">
                <p class="ba-kpi-label">Pending Allocation</p>
                <div class="ba-kpi-metric">
                    <div class="ba-kpi-value">{{ $kpis['pending'] }}</div>
                </div>
            </div>
            <div class="ba-kpi-graph">{!! $kpiGraph('#c084fc', '#f0abfc', 'baWavePurple') !!}</div>
        </div>
        <div class="ba-kpi tone-green">
            <span class="ba-kpi-ico"><i class="bi bi-people-fill"></i></span>
            <div class="ba-kpi-body">
                <p class="ba-kpi-label">Assigned Today</p>
                <div class="ba-kpi-metric">
                    <div class="ba-kpi-value">{{ $kpis['assigned_today'] }}</div>
                    {!! $trendBadge($kpis['assigned_today_trend'] ?? null) !!}
                </div>
            </div>
            <div class="ba-kpi-graph">{!! $kpiGraph('#4ade80', '#6ee7b7', 'baWaveGreen') !!}</div>
        </div>
        <div class="ba-kpi tone-blue">
            <span class="ba-kpi-ico"><i class="bi bi-arrow-repeat"></i></span>
            <div class="ba-kpi-body">
                <p class="ba-kpi-label">Reassigned Today</p>
                <div class="ba-kpi-metric">
                    <div class="ba-kpi-value">{{ $kpis['reassigned_today'] }}</div>
                    {!! $trendBadge($kpis['reassigned_today_trend'] ?? null) !!}
                </div>
            </div>
            <div class="ba-kpi-graph">{!! $kpiGraph('#60a5fa', '#a5b4fc', 'baWaveBlue') !!}</div>
        </div>
        <div class="ba-kpi tone-orange">
            <span class="ba-kpi-ico"><i class="bi bi-layers-fill"></i></span>
            <div class="ba-kpi-body">
                <p class="ba-kpi-label">Total Allocations (This Month)</p>
                <div class="ba-kpi-metric">
                    <div class="ba-kpi-value">{{ $kpis['month_total'] }}</div>
                    {!! $trendBadge($kpis['month_total_trend'] ?? null) !!}
                </div>
            </div>
            <div class="ba-kpi-graph">{!! $kpiGraph('#fb923c', '#fcd34d', 'baWaveOrange') !!}</div>
        </div>
    </div>

    <div class="ba-tabs" role="tablist">
        <button type="button" class="ba-tab {{ $activeTab === 'pending' ? 'active' : '' }}" data-ba-tab="pending">
            <i class="bi bi-layers-fill"></i> Pending BM Allocation
            <span class="count" id="pendingTabCount">{{ $pending->count() }}</span>
        </button>
        <button type="button" class="ba-tab {{ $activeTab === 'assigned' ? 'active' : '' }}" data-ba-tab="assigned">
            <i class="bi bi-arrow-repeat"></i> Recently Assigned / Reassign
            <span class="count">{{ $assigned->count() }}</span>
        </button>
    </div>

    {{-- Pending --}}
    <div class="ba-card {{ $activeTab === 'pending' ? '' : 'panel-hidden' }}" data-ba-panel="pending">
        <div class="ba-card-head">
            <h5>
                <span class="ba-head-ico" aria-hidden="true"><i class="bi bi-layers-fill"></i></span>
                Pending BM Allocation
            </h5>
            <div class="ba-head-branch">
                <i class="bi bi-building"></i>
                <select id="headerBranch" class="form-select" data-search="true" aria-label="All Branches">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="ba-filters">
            <div class="ba-search">
                <i class="bi bi-search"></i>
                <input type="search" id="pendingSearch" class="ba-field" placeholder="Search by Ref No. or Customer...">
            </div>
            <select id="filterBranch" class="form-select ba-field" data-search="true">
                <option value="">All Branches</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
            <select id="filterLeadType" class="form-select ba-field" data-search="true">
                <option value="">All Types</option>
                <option value="immediate">Immediate</option>
                <option value="future">Future</option>
            </select>
            <select id="filterPriority" class="form-select ba-field" data-search="true">
                <option value="">All Priorities</option>
                <option value="High">High</option>
                <option value="Medium">Medium</option>
                <option value="Low">Low</option>
            </select>
            <button type="button" class="ba-btn-reset" id="pendingResetBtn">
                <i class="bi bi-arrow-clockwise"></i> Reset
            </button>
            <button type="button" class="ba-btn-bulk" id="bulkAssignBtn" disabled>
                <i class="bi bi-send-fill"></i> Assign Selected (<span id="selectedCount">0</span>)
            </button>
        </div>

        <div class="ba-table-shell">
            <div class="ba-table-wrap">
            <table class="table align-middle ba-datatable" id="pendingTable" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:36px;"><input type="checkbox" class="form-check-input" id="selectAllPending"></th>
                        <th>Ref</th>
                        <th>Customer</th>
                        <th>Branch</th>
                        <th>Lead Type</th>
                        <th>Priority</th>
                        <th>Received On</th>
                        <th>Assign to</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($pending as $lead)
                    @php
                        $priority = $priorityFor($lead);
                        $initial = strtoupper(substr($lead->customer_name ?? '?', 0, 1));
                        $branchId = (int) ($lead->goal_org_unit_id ?? 0);
                        $branchName = $lead->orgUnit?->name ?? '—';
                        $type = strtolower((string) ($lead->lead_type ?? ''));
                    @endphp
                    <tr class="pending-row"
                        data-lead-id="{{ $lead->id }}"
                        data-branch-id="{{ $branchId }}"
                        data-branch-name="{{ strtolower($branchName) }}"
                        data-lead-type="{{ $type }}"
                        data-priority="{{ $priority }}"
                        data-search="{{ strtolower($lead->ref_no . ' ' . $lead->customer_name) }}">
                        <td><input type="checkbox" class="form-check-input pending-cb" value="{{ $lead->id }}"></td>
                        <td><a class="ref-link" href="{{ route('goal.leads.show', $lead) }}">{{ $lead->ref_no }}</a></td>
                        <td>
                            <span class="cust">
                                <span class="avatar">{{ $initial }}</span>
                                <span>{{ $lead->customer_name }}</span>
                            </span>
                        </td>
                        <td><span class="meta"><i class="bi bi-building"></i>{{ $branchName }}</span></td>
                        <td>
                            @if($type === 'immediate')
                                <span class="pill pill-green">Immediate</span>
                            @else
                                <span class="pill pill-blue">{{ $type ? ucfirst($type) : '—' }}</span>
                            @endif
                        </td>
                        <td>
                            @if($priority === 'High')
                                <span class="pill pill-red">High</span>
                            @elseif($priority === 'Medium')
                                <span class="pill pill-amber">Medium</span>
                            @else
                                <span class="pill pill-slate">Low</span>
                            @endif
                        </td>
                        <td><span class="meta"><i class="bi bi-calendar3"></i>{{ optional($lead->created_at)->format('d M Y, h:i a') }}</span></td>
                        <td>
                            <select class="form-select ba-sales" data-search="true" data-placeholder="Select Sales (JSM / SM / SSM)" data-branch-id="{{ $branchId }}" aria-label="Assign to">
                                <option value="">Select Sales (JSM / SM / SSM)</option>
                            </select>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('goal.allocation.assign', $lead) }}" class="row-assign-form d-inline">
                                @csrf
                                <input type="hidden" name="action" value="assign">
                                <input type="hidden" name="assigned_to" class="row-assigned-to" value="">
                                <button type="submit" class="btn-row-assign" disabled>
                                    <i class="bi bi-send-fill"></i> Assign
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9"><div class="empty">No leads pending BM allocation.</div></td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    {{-- Recently assigned --}}
    <div class="ba-card {{ $activeTab === 'assigned' ? '' : 'panel-hidden' }}" data-ba-panel="assigned">
        <div class="ba-card-head">
            <h5>
                <span class="ba-head-ico" aria-hidden="true"><i class="bi bi-arrow-repeat"></i></span>
                Recently Assigned / Reassign
            </h5>
            <div class="d-flex align-items-center gap-2">
                <div class="ba-search" style="position:relative;min-width:220px">
                    <i class="bi bi-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8"></i>
                    <input type="search" id="assignedSearch" class="ba-field" style="padding-left:34px;width:100%" placeholder="Search assigned...">
                </div>
            </div>
        </div>
        <div class="ba-table-shell">
            <div class="ba-table-wrap">
            <table class="table align-middle ba-datatable" id="assignedTable" style="width:100%">
                <thead>
                    <tr>
                        <th>Ref</th>
                        <th>Customer</th>
                        <th>Current Assignee</th>
                        <th>Reassigned To</th>
                        <th>Reassign Reason</th>
                        <th>Reassigned On</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($assigned as $lead)
                    @php
                        $last = $lead->assignments->first();
                        $initial = strtoupper(substr($lead->customer_name ?? '?', 0, 1));
                        $search = strtolower(implode(' ', [
                            $lead->ref_no, $lead->customer_name,
                            $lead->assignee?->name, $last?->toUser?->name, $last?->reason,
                        ]));
                    @endphp
                    <tr class="assigned-row" data-search="{{ $search }}">
                        <td><a class="ref-link" href="{{ route('goal.leads.show', $lead) }}">{{ $lead->ref_no }}</a></td>
                        <td>
                            <span class="cust">
                                <span class="avatar">{{ $initial }}</span>
                                <span>{{ $lead->customer_name }}</span>
                            </span>
                        </td>
                        <td>{{ $salesLabel($lead->assignee) }}</td>
                        <td>
                            @if($last?->toUser)
                                <span class="pill pill-blue">{{ $salesLabel($last->toUser) }}</span>
                            @else —
                            @endif
                        </td>
                        <td>{{ $last?->reason ?: ($last?->action === 'assign' ? 'Initial assignment' : '—') }}</td>
                        <td>{{ optional($last?->created_at ?? $lead->assigned_at)->format('d M Y, h:i a') }}</td>
                        <td class="text-end">
                            <div class="ba-actions">
                                <a class="icon-btn" href="{{ route('goal.leads.show', $lead) }}" title="View details"><i class="bi bi-eye"></i></a>
                                <button type="button" class="icon-btn btn-history" title="Assignment history"
                                    data-history-url="{{ route('goal.allocation.history', $lead) }}">
                                    <i class="bi bi-clock-history"></i>
                                </button>
                                <button type="button" class="btn-reassign"
                                    data-bs-toggle="modal" data-bs-target="#reassignModal"
                                    data-assign-url="{{ route('goal.allocation.assign', $lead) }}"
                                    data-lead-ref="{{ $lead->ref_no }}"
                                    data-customer="{{ $lead->customer_name }}"
                                    data-current="{{ $salesLabel($lead->assignee) }}"
                                    data-branch-id="{{ (int) ($lead->goal_org_unit_id ?? 0) }}">
                                    <i class="bi bi-arrow-left-right"></i> Reassign
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty">No recently assigned leads.</div></td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

{{-- Reassign modal --}}
<div class="modal fade ba-modal" id="reassignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content" id="reassignForm">
            @csrf
            <input type="hidden" name="action" value="reassign">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold">Confirm Reassignment</h5>
                    <div class="text-muted small" id="reassignLeadMeta">—</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Current Sales Person</label>
                    <input type="text" class="form-control" id="reassignCurrent" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">New Sales Person <span class="text-danger">*</span></label>
                    <select name="assigned_to" id="reassignAssignee" class="form-select" data-search="true" data-placeholder="Select Sales person" required>
                        <option value="">Select Sales (JSM / SM / SSM)</option>
                    </select>
                </div>
                <div>
                    <label class="form-label fw-semibold">Reassignment Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" id="reassignReason" class="form-control" rows="3" maxlength="500" required
                        placeholder="Mandatory — e.g. workload balancing, territory realignment"></textarea>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-semibold">
                    <i class="bi bi-check2-circle me-1"></i> Confirm Reassignment
                </button>
            </div>
        </form>
    </div>
</div>

{{-- History modal --}}
<div class="modal fade ba-modal" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold">Assignment History</h5>
                    <div class="text-muted small" id="historyLeadMeta">—</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>When</th><th>Action</th><th>From</th><th>To</th><th>By</th><th>Reason</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <tr><td colspan="6" class="text-center text-muted p-4">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" id="historyViewLink" class="btn btn-outline-primary btn-sm" target="_blank">View lead</a>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.BA_SALES = @json($salesPayload);
(function ($) {
    var sales = window.BA_SALES || [];
    var page = document.querySelector('.ba-page');
    var bulkUrl = page.getAttribute('data-bulk-url');
    var csrf = page.getAttribute('data-csrf');

    function salesForBranch(branchId) {
        var bid = branchId ? parseInt(branchId, 10) : null;
        if (!bid) return sales.slice();
        var matched = sales.filter(function (s) { return s.branch_id === bid; });
        // Fall back to unassigned-branch sales + all if none matched
        if (matched.length) return matched;
        return sales.filter(function (s) { return !s.branch_id; }).concat(
            sales.filter(function (s) { return !!s.branch_id; })
        );
    }

    function destroySelect2($el) {
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
    }

    function enhanceSelect2($el, dropdownParent) {
        if (!$.fn.select2 || !$el.length) return;
        destroySelect2($el);
        var searchMode = $el.data('search');
        // Always show search when data-search="true" (branch / assign dropdowns)
        var minSearch = (searchMode === false || searchMode === 'false')
            ? Infinity
            : 0;
        var emptyOpt = $el.find('option[value=""]').first();
        var placeholder = $el.data('placeholder') || emptyOpt.text() || 'Select';
        // Keep filter "All …" labels as real selected options (not clearable placeholders)
        var keepEmptyLabel = $el.is('#headerBranch, #filterBranch, #filterLeadType, #filterPriority');
        $el.select2({
            width: '100%',
            dropdownParent: dropdownParent || $(document.body),
            placeholder: keepEmptyLabel ? undefined : placeholder,
            allowClear: false,
            minimumResultsForSearch: minSearch
        });
    }

    function setSelectVal($el, val) {
        $el.val(val || '').trigger('change.select2');
    }

    function fillSelect($select, branchId, selected) {
        var opts = salesForBranch(branchId);
        var html = '<option value="">Select Sales (JSM / SM / SSM)</option>';
        opts.forEach(function (s) {
            html += '<option value="' + s.id + '"' + (String(selected) === String(s.id) ? ' selected' : '') + '>' + $('<div>').text(s.label).html() + '</option>';
        });
        var $parent = $select.closest('.modal').length ? $select.closest('.modal') : $(document.body);
        destroySelect2($select);
        $select.html(html);
        enhanceSelect2($select, $parent);
    }

    function initRowSales() {
        $('.pending-row').each(function () {
            var $row = $(this);
            var branchId = $row.data('branch-id');
            var $sel = $row.find('select.ba-sales');
            fillSelect($sel, branchId, '');
            $sel.off('change.ba').on('change.ba', function () {
                var val = $(this).val();
                $row.find('.row-assigned-to').val(val || '');
                $row.find('.btn-row-assign').prop('disabled', !val);
                syncBulk();
            });
        });
    }

    function initFilterSelect2() {
        ['#headerBranch', '#filterBranch', '#filterLeadType', '#filterPriority'].forEach(function (sel) {
            enhanceSelect2($(sel), $(document.body));
        });
    }

    function switchTab(tab) {
        $('[data-ba-tab]').each(function () {
            $(this).toggleClass('active', $(this).data('ba-tab') === tab);
        });
        $('[data-ba-panel]').each(function () {
            $(this).toggleClass('panel-hidden', $(this).data('ba-panel') !== tab);
        });
        try {
            var url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url.toString());
        } catch (e) {}
        setTimeout(function () {
            if ($.fn.DataTable) {
                $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
            }
        }, 50);
    }

    $('[data-ba-tab]').on('click', function () {
        switchTab($(this).data('ba-tab'));
    });

    function visiblePendingRows() {
        return $('.pending-row').filter(function () {
            return !$(this).hasClass('row-hidden') && $(this).css('display') !== 'none';
        });
    }

    function applyPendingFilters() {
        var q = ($('#pendingSearch').val() || '').trim().toLowerCase();
        var branch = $('#filterBranch').val() || '';
        var type = ($('#filterLeadType').val() || '').toLowerCase();
        var priority = $('#filterPriority').val() || '';
        var visible = 0;

        $('.pending-row').each(function () {
            var $row = $(this);
            var ok = true;
            if (q && String($row.data('search') || '').indexOf(q) === -1) ok = false;
            if (ok && branch && String($row.data('branch-id')) !== String(branch)) ok = false;
            if (ok && type && String($row.data('lead-type')) !== type) ok = false;
            if (ok && priority && String($row.data('priority')) !== priority) ok = false;
            $row.toggleClass('row-hidden', !ok);
            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#pendingTable')) {
                // DataTables own filtering via search plugin below
            }
            if (!ok) {
                $row.find('.pending-cb').prop('checked', false);
            } else {
                visible++;
            }
        });

        $('#pendingTabCount').text(visible);
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#pendingTable')) {
            $('#pendingTable').DataTable().draw(false);
        }
        syncBulk();
    }

    // Custom DataTables filter for pending row-hidden + criteria
    if ($.fn.dataTable) {
        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            if (settings.nTable && settings.nTable.id !== 'pendingTable') return true;
            var row = settings.aoData[dataIndex] && settings.aoData[dataIndex].nTr;
            if (!row) return true;
            return !row.classList.contains('row-hidden');
        });
    }

    function syncBulk() {
        var selected = visiblePendingRows().find('.pending-cb:checked').length;
        $('#selectedCount').text(selected);
        $('#bulkAssignBtn').prop('disabled', selected === 0);
        var totalVisible = visiblePendingRows().length;
        var all = $('#selectAllPending');
        all.prop('checked', totalVisible > 0 && selected === totalVisible);
        all.prop('indeterminate', selected > 0 && selected < totalVisible);
    }

    function syncBranchFilters(source) {
        var val = $(source).val() || '';
        if (source.id === 'headerBranch') {
            setSelectVal($('#filterBranch'), val);
        } else if (source.id === 'filterBranch') {
            setSelectVal($('#headerBranch'), val);
        }
    }

    $('#pendingSearch').on('input', applyPendingFilters);
    $('#filterBranch, #headerBranch, #filterLeadType, #filterPriority').on('change', function () {
        if (this.id === 'filterBranch' || this.id === 'headerBranch') {
            syncBranchFilters(this);
        }
        $('.pending-row').each(function () {
            var $row = $(this);
            var leadBranch = $row.data('branch-id');
            var $sales = $row.find('select.ba-sales');
            fillSelect($sales, leadBranch, $sales.val());
            $row.find('.row-assigned-to').val($sales.val() || '');
            $row.find('.btn-row-assign').prop('disabled', !$sales.val());
        });
        applyPendingFilters();
    });

    $('#pendingResetBtn').on('click', function () {
        $('#pendingSearch').val('');
        setSelectVal($('#filterBranch'), '');
        setSelectVal($('#headerBranch'), '');
        setSelectVal($('#filterLeadType'), '');
        setSelectVal($('#filterPriority'), '');
        applyPendingFilters();
    });

    $('#selectAllPending').on('change', function () {
        var checked = this.checked;
        visiblePendingRows().find('.pending-cb').prop('checked', checked);
        syncBulk();
    });
    $(document).on('change', '.pending-cb', syncBulk);

    $(document).on('submit', '.row-assign-form', function (e) {
        var $form = $(this);
        var val = $form.find('.row-assigned-to').val();
        if (!val) {
            e.preventDefault();
            alert('Select a Sales user (JSM / SM / SSM) before assigning.');
        }
    });

    $('#bulkAssignBtn').on('click', function () {
        var assignments = [];
        var missing = false;
        visiblePendingRows().find('.pending-cb:checked').each(function () {
            var $row = $(this).closest('.pending-row');
            var leadId = $row.data('lead-id');
            var assignedTo = $row.find('select.ba-sales').val();
            if (!assignedTo) {
                missing = true;
                return false;
            }
            assignments.push({ lead_id: leadId, assigned_to: parseInt(assignedTo, 10) });
        });
        if (missing) {
            alert('Select a Sales user for every selected lead before bulk assign.');
            return;
        }
        if (!assignments.length) {
            alert('Select at least one lead.');
            return;
        }

        var form = document.createElement('form');
        form.method = 'POST';
        form.action = bulkUrl;
        var token = document.createElement('input');
        token.type = 'hidden'; token.name = '_token'; token.value = csrf;
        form.appendChild(token);
        assignments.forEach(function (a, i) {
            var lead = document.createElement('input');
            lead.type = 'hidden'; lead.name = 'assignments[' + i + '][lead_id]'; lead.value = a.lead_id;
            form.appendChild(lead);
            var user = document.createElement('input');
            user.type = 'hidden'; user.name = 'assignments[' + i + '][assigned_to]'; user.value = a.assigned_to;
            form.appendChild(user);
        });
        document.body.appendChild(form);
        form.submit();
    });

    // Assigned search
    $('#assignedSearch').on('input', function () {
        var q = ($(this).val() || '').trim().toLowerCase();
        $('.assigned-row').each(function () {
            var hay = String($(this).data('search') || '');
            $(this).toggleClass('row-hidden', q !== '' && hay.indexOf(q) === -1);
        });
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#assignedTable')) {
            if (!window._baAssignedFilter) {
                $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                    if (settings.nTable && settings.nTable.id !== 'assignedTable') return true;
                    var row = settings.aoData[dataIndex] && settings.aoData[dataIndex].nTr;
                    if (!row) return true;
                    return !row.classList.contains('row-hidden');
                });
                window._baAssignedFilter = true;
            }
            $('#assignedTable').DataTable().draw(false);
        }
    });

    // Reassign modal
    $('#reassignModal').on('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn) return;
        var branchId = btn.getAttribute('data-branch-id');
        $('#reassignForm').attr('action', btn.getAttribute('data-assign-url'));
        $('#reassignLeadMeta').text((btn.getAttribute('data-lead-ref') || '') + ' · ' + (btn.getAttribute('data-customer') || ''));
        $('#reassignCurrent').val(btn.getAttribute('data-current') || '—');
        fillSelect($('#reassignAssignee'), branchId, '');
        $('#reassignReason').val('');
    });

    $('#reassignForm').on('submit', function (e) {
        if (!$('#reassignAssignee').val()) {
            e.preventDefault();
            alert('Select a new Sales person.');
            return;
        }
        if (!($('#reassignReason').val() || '').trim()) {
            e.preventDefault();
            alert('Reassignment reason is mandatory.');
        }
    });

    // History modal
    var historyModal = document.getElementById('historyModal')
        ? new bootstrap.Modal(document.getElementById('historyModal'))
        : null;

    $(document).on('click', '.btn-history', function () {
        var url = $(this).data('history-url');
        $('#historyLeadMeta').text('Loading…');
        $('#historyTableBody').html('<tr><td colspan="6" class="text-center text-muted p-4">Loading…</td></tr>');
        $('#historyViewLink').attr('href', '#');
        if (historyModal) historyModal.show();

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                $('#historyLeadMeta').text((data.ref_no || '') + ' · ' + (data.customer || '') + ' · Current: ' + (data.current || '—'));
                if (data.show_url) $('#historyViewLink').attr('href', data.show_url);
                if (!data.history || !data.history.length) {
                    $('#historyTableBody').html('<tr><td colspan="6" class="text-center text-muted p-4">No assignment history yet.</td></tr>');
                    return;
                }
                $('#historyTableBody').html(data.history.map(function (row) {
                    return '<tr>' +
                        '<td>' + (row.at || '—') + '</td>' +
                        '<td><span class="pill ' + (row.action === 'reassign' ? 'pill-amber' : 'pill-blue') + '">' + (row.action || '—') + '</span></td>' +
                        '<td>' + (row.from || '—') + '</td>' +
                        '<td>' + (row.to || '—') + '</td>' +
                        '<td>' + (row.by || '—') + '</td>' +
                        '<td>' + (row.reason || '—') + '</td>' +
                        '</tr>';
                }).join(''));
            })
            .catch(function () {
                $('#historyTableBody').html('<tr><td colspan="6" class="text-center text-danger p-4">Failed to load history.</td></tr>');
            });
    });

    $(function () {
        initFilterSelect2();

        if ($.fn.DataTable) {
            var common = {
                paging: true,
                pageLength: 10,
                lengthChange: true,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                info: true,
                ordering: true,
                autoWidth: false,
                pagingType: 'simple_numbers',
                /* table inside inset wrap; footer outside scroll area */
                dom: 't<"ba-dt-foot"i<"ba-dt-right"lp>>',
                language: {
                    emptyTable: 'No records found.',
                    info: 'Showing _START_–_END_ of _TOTAL_',
                    infoEmpty: 'Showing 0 entries',
                    infoFiltered: '(filtered from _MAX_ total)',
                    lengthMenu: 'Rows _MENU_',
                    paginate: {
                        previous: '<i class="bi bi-chevron-left" aria-hidden="true"></i>',
                        next: '<i class="bi bi-chevron-right" aria-hidden="true"></i>'
                    }
                },
                initComplete: function () {
                    var $wrap = $(this.api().table().container());
                    var $shell = $wrap.closest('.ba-table-shell');
                    var $foot = $wrap.find('.ba-dt-foot');
                    if ($shell.length && $foot.length) {
                        $shell.append($foot);
                    }
                }
            };
            if ($('#pendingTable tbody tr.pending-row').length) {
                $('#pendingTable').DataTable($.extend({}, common, {
                    columnDefs: [
                        { orderable: false, targets: [0, 7, 8] }
                    ],
                    order: [[6, 'desc']]
                }));
            }
            if ($('#assignedTable tbody tr.assigned-row').length) {
                $('#assignedTable').DataTable($.extend({}, common, {
                    columnDefs: [{ orderable: false, targets: [6] }],
                    order: [[5, 'desc']]
                }));
            }
        }

        // After DataTables so row selects attach to the live cells
        initRowSales();
        syncBulk();
    });
})(jQuery);
</script>
@endpush
