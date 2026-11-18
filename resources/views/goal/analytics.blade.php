@extends('layouts.app')

@push('styles')
<style>
.ga-page{color:#1e293b;position:relative;min-height:60vh}
.ga-page .page-head{display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.15rem}
.ga-page .page-head h2{margin:0;font-size:1.45rem;font-weight:800;color:#0f172a;letter-spacing:-.02em}
.ga-page .page-head .sub{margin:.35rem 0 0;font-size:.88rem;color:#64748b;line-height:1.45}
.ga-page .role-badge{display:inline-flex;align-items:center;gap:.35rem;margin-top:.55rem;padding:.3rem .75rem;border-radius:999px;background:#0f172a;color:#fff;font-size:.72rem;font-weight:700}
.ga-page .head-controls{display:flex;flex-direction:column;align-items:flex-end;gap:.65rem}
.ga-page .period-seg{display:flex;flex-wrap:wrap;gap:.4rem;align-items:center;justify-content:flex-end}
.ga-page .period-btn{border:1px solid #e2e8f0;background:#fff;border-radius:8px;padding:.42rem .78rem;font-size:.78rem;font-weight:600;color:#64748b;text-decoration:none;transition:all .15s}
.ga-page .period-btn.active,.ga-page .period-btn:hover{background:var(--theme-primary);border-color:var(--theme-primary);color:#fff}
.ga-page .interval-seg{display:inline-flex;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;background:#fff}
.ga-page .interval-btn{border:0;background:transparent;padding:.38rem .85rem;font-size:.76rem;font-weight:700;color:#64748b;text-decoration:none;transition:all .15s}
.ga-page .interval-btn.active{background:#1e3a8a;color:#fff}
.ga-page .interval-btn:hover:not(.active){background:#f8fafc;color:var(--theme-primary-deep)}

/* KPI cards — match Home / mockup */
.ga-page .metric-card{--kpi-accent:#f63383;--kpi-icon-bg:#fce3ef;--kpi-icon:#e91e63;--kpi-heading:#1555d8;--kpi-value:#172033;--kpi-sub:#64748b;--kpi-link:#1555d8;--kpi-wave-a:#f3e0ff;--kpi-wave-b:#f9c2e8;--kpi-wave-c:#e8b4f0;position:relative;display:flex;flex-direction:column;background:linear-gradient(160deg,#ffffff 0%,#faf8ff 55%,#ffffff 100%);border:1px solid #eaeaf2;border-radius:14px;padding:.85rem 1rem .75rem 1.05rem;height:100%;min-height:108px;overflow:hidden;box-shadow:0 4px 14px rgba(23,32,51,.06);transition:transform .2s ease,box-shadow .2s ease}
.ga-page .metric-card::before{content:"";position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--kpi-accent);z-index:3}
.ga-page .metric-card:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(23,32,51,.09)}
.ga-page .metric-card.t-magenta{--kpi-accent:#f63383;--kpi-icon-bg:#fce3ef;--kpi-icon:#e91e63;--kpi-wave-a:#f3e0ff;--kpi-wave-b:#f9c2e8;--kpi-wave-c:#e8b4f0}
.ga-page .metric-card.t-blue{--kpi-accent:var(--theme-primary);--kpi-icon-bg:var(--theme-100);--kpi-icon:var(--theme-primary);--kpi-wave-a:#e0ecff;--kpi-wave-b:#c7dbff;--kpi-wave-c:#a8c8ff}
.ga-page .metric-card.t-green{--kpi-accent:#16a34a;--kpi-icon-bg:#dcfce7;--kpi-icon:#16a34a;--kpi-wave-a:#e3f9eb;--kpi-wave-b:#c6f0d4;--kpi-wave-c:#9fe3b8}
.ga-page .metric-card.t-orange,.ga-page .metric-card.t-red{--kpi-accent:#f97316;--kpi-icon-bg:#ffedd5;--kpi-icon:#ea580c;--kpi-wave-a:#fff0e0;--kpi-wave-b:#ffd9b3;--kpi-wave-c:#ffc48a}
.ga-page .metric-head{display:flex;align-items:center;justify-content:space-between;gap:.55rem;margin-bottom:.35rem;position:relative;z-index:2}
.ga-page .metric-icon{width:36px;height:32px;border-radius:8px;flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;background:var(--kpi-icon-bg);color:var(--kpi-icon);font-size:.95rem}
.ga-page .metric-label{margin:0;font-size:9.5px;font-weight:600;color:var(--kpi-heading);text-transform:uppercase;letter-spacing:.04em;text-align:right;line-height:1.2;max-width:70%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ga-page .metric-body{position:relative;z-index:2;display:flex;flex-direction:column;flex:1}
.ga-page .metric-value{margin:0;font-size:24px;font-weight:800;color:var(--kpi-value);line-height:1;letter-spacing:-.02em}
.ga-page .metric-trend{margin:.15rem 0 0;font-size:9.5px;font-weight:500;color:var(--kpi-sub);line-height:1.2}
.ga-page .metric-link{display:inline-block;margin-top:.35rem;font-size:10.5px;font-weight:600;text-decoration:none;color:var(--kpi-link);position:relative;z-index:2}
.ga-page .metric-link:hover{color:#0f3fb8;text-decoration:none}
.ga-page a.metric-card{text-decoration:none;color:inherit;display:flex;flex-direction:column;cursor:pointer}
.ga-page .metric-wave{position:absolute;right:0;bottom:0;width:46%;height:62%;pointer-events:none;z-index:1}
.ga-page .metric-wave svg{display:block;width:100%;height:100%}
.ga-page .metric-wave .w0{fill:var(--kpi-wave-a);opacity:.9}
.ga-page .metric-wave .w1{fill:var(--kpi-wave-b);opacity:.72}
.ga-page .metric-wave .w2{fill:var(--kpi-wave-c);opacity:.55}

/* Panels */
.ga-page .panel{background:#fff;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 4px 16px rgba(15,23,42,.05);height:100%;overflow:hidden;position:relative}
.ga-page .panel-h{padding:1rem 1.15rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem}
.ga-page .panel-h h5{margin:0;font-size:.95rem;font-weight:700;color:#0f172a}
.ga-page .panel-h .sub{font-size:.75rem;color:#94a3b8;margin:.15rem 0 0}
.ga-page .panel-b{padding:1rem 1.15rem}
.ga-page .panel-title-row{display:flex;align-items:flex-start;gap:.65rem}
.ga-page .panel-ico{width:36px;height:36px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:1rem;flex-shrink:0}
.ga-page .panel-ico.purple{background:#7c3aed}.ga-page .panel-ico.blue{background:var(--theme-primary-deep)}
.ga-page .panel-actions{display:flex;align-items:center;gap:.55rem;flex-wrap:wrap}
.ga-page .view-all{font-size:.82rem;font-weight:700;color:var(--theme-primary);text-decoration:none;white-space:nowrap}
.ga-page .view-all:hover{color:var(--theme-primary-hover);text-decoration:underline}
.ga-page .target-head-right{text-align:right}
.ga-page .target-head-right .tgt-amt{font-size:.82rem;font-weight:800;color:#0f172a;margin-top:.35rem}
.ga-page .target-layout{display:grid;grid-template-columns:150px 1fr;gap:1.1rem 1.35rem;align-items:center}
.ga-page .donut-wrap{position:relative;width:148px;height:148px}
.ga-page .donut-center{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none}
.ga-page .donut-center .pct{font-size:1.55rem;font-weight:800;color:#0f172a;line-height:1}
.ga-page .donut-center .lbl{font-size:.68rem;color:#94a3b8;font-weight:700;text-transform:uppercase;margin-top:.2rem;letter-spacing:.04em}
.ga-page .target-ach{font-size:1.55rem;font-weight:800;color:#0f172a;line-height:1.15}
.ga-page .target-rem{font-size:1rem;font-weight:800;color:#dc2626;margin-top:.25rem}
.ga-page .target-rem span{font-weight:600;color:#64748b;font-size:.85rem}
.ga-page .stat-badges{display:grid;grid-template-columns:repeat(3,1fr);gap:.65rem;grid-column:1/-1}
.ga-page .stat-badge{border-radius:12px;padding:.7rem .75rem;display:flex;align-items:center;gap:.55rem}
.ga-page .stat-badge .ico{width:28px;height:28px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:.8rem;flex-shrink:0}
.ga-page .stat-badge .num{font-size:1rem;font-weight:800;line-height:1.1}
.ga-page .stat-badge .lbl{font-size:.72rem;font-weight:600;opacity:.85}
.ga-page .sb-green{background:#ecfdf5;color:#047857}.ga-page .sb-green .ico{background:#16a34a}
.ga-page .sb-blue{background:var(--theme-50);color:var(--theme-primary-hover)}.ga-page .sb-blue .ico{background:var(--theme-primary)}
.ga-page .sb-purple{background:#f5f3ff;color:#6d28d9}.ga-page .sb-purple .ico{background:#7c3aed}
.ga-page .chart-box{position:relative;height:248px}
.ga-page .chart-box.is-empty{display:flex;align-items:center;justify-content:center}
.ga-page .trend-legend{display:flex;flex-wrap:wrap;gap:1rem;margin-bottom:.65rem}
.ga-page .trend-legend span{display:inline-flex;align-items:center;gap:.4rem;font-size:.78rem;font-weight:600;color:#475569}
.ga-page .trend-legend i{width:8px;height:8px;border-radius:50%;display:inline-block}
.ga-page .donut-split{display:grid;grid-template-columns:1fr 1.05fr;gap:1rem;align-items:center;min-height:190px}
.ga-page .donut-ring{position:relative;width:100%;max-width:150px;height:150px;margin:0 auto}
.ga-page .donut-ring .center-lbl{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;text-align:center}
.ga-page .donut-ring .center-lbl .num{font-size:1.35rem;font-weight:800;color:#0f172a;line-height:1}
.ga-page .donut-ring .center-lbl .txt{font-size:.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-top:.2rem}
.ga-page .legend-list{display:flex;flex-direction:column;gap:.55rem}
.ga-page .legend-item{display:flex;align-items:center;justify-content:space-between;gap:.5rem;font-size:.8rem}
.ga-page .legend-item .left{display:inline-flex;align-items:center;gap:.45rem;color:#475569;font-weight:600;min-width:0}
.ga-page .legend-item .dot{width:9px;height:9px;border-radius:50%;flex-shrink:0}
.ga-page .legend-item .val{font-weight:800;color:#0f172a;white-space:nowrap}
.ga-page .qa-grid{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
.ga-page .qa-tile{border:1px solid transparent;border-radius:14px;padding:1.05rem .75rem;text-align:center;text-decoration:none;font-weight:700;font-size:.82rem;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.45rem;min-height:96px;transition:transform .15s,box-shadow .15s}
.ga-page .qa-tile:hover{transform:translateY(-2px);box-shadow:0 8px 18px rgba(15,23,42,.08);color:inherit}
.ga-page .qa-tile i{font-size:1.35rem}
.ga-page .qa-blue{background:var(--theme-50);color:var(--theme-primary);border-color:var(--theme-100)}
.ga-page .qa-purple{background:#f5f3ff;color:#7c3aed;border-color:#ede9fe}
.ga-page .qa-green{background:#ecfdf5;color:#16a34a;border-color:#d1fae5}
.ga-page .qa-orange{background:#fff7ed;color:#ea580c;border-color:#ffedd5}

/* States */
.ga-page .empty-state,.ga-page .error-state{display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:1.75rem 1rem;min-height:160px;color:#64748b}
.ga-page .empty-state .illus,.ga-page .error-state .illus{width:56px;height:56px;border-radius:16px;display:inline-flex;align-items:center;justify-content:center;background:#f1f5f9;color:#94a3b8;font-size:1.4rem;margin-bottom:.75rem}
.ga-page .error-state .illus{background:#fef2f2;color:#dc2626}
.ga-page .empty-state p,.ga-page .error-state p{margin:0;font-size:.88rem;font-weight:600}
.ga-page .error-banner{display:flex;align-items:flex-start;gap:.75rem;padding:.9rem 1.1rem;border-radius:12px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;margin-bottom:1rem}
.ga-page .error-banner i{font-size:1.15rem;margin-top:.1rem}
.ga-page .loading-overlay{position:absolute;inset:0;z-index:20;background:rgba(248,250,252,.72);backdrop-filter:blur(2px);display:none;align-items:center;justify-content:center;border-radius:16px}
.ga-page.is-loading .loading-overlay{display:flex}
.ga-page .loading-spinner{width:42px;height:42px;border:3px solid var(--theme-100);border-top-color:var(--theme-primary);border-radius:50%;animation:ga-spin .7s linear infinite}
@keyframes ga-spin{to{transform:rotate(360deg)}}
.ga-page .kpi-skel{height:108px;border-radius:14px;background:linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);background-size:200% 100%;animation:ga-shimmer 1.2s infinite}
@keyframes ga-shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}

@media(max-width:1199.98px){
  .ga-page .target-layout{grid-template-columns:130px 1fr}
  .ga-page .stat-badges{grid-template-columns:1fr 1fr}
  .ga-page .stat-badges .stat-badge:last-child{grid-column:1/-1}
  .ga-page .donut-split{grid-template-columns:1fr;justify-items:center}
  .ga-page .head-controls{align-items:flex-start}
  .ga-page .period-seg{justify-content:flex-start}
}
@media(max-width:767.98px){
  .ga-page.container-fluid{padding:1rem .85rem!important}
  .ga-page .metric-value{font-size:1.45rem}
  .ga-page .chart-box{height:210px}
  .ga-page .panel-h{flex-wrap:wrap}
  .ga-page .qa-grid{grid-template-columns:1fr 1fr}
}
@media(max-width:575.98px){
  .ga-page .target-layout{grid-template-columns:1fr;justify-items:center;text-align:center}
  .ga-page .stat-badges{grid-template-columns:1fr}
  .ga-page .period-btn{padding:.35rem .55rem;font-size:.72rem}
}
</style>
@endpush

@section('content')
@php
    $toneCard = fn ($t) => match ($t) {
        'magenta' => 't-magenta',
        'blue' => 't-blue',
        'green' => 't-green',
        'red', 'orange' => 't-orange',
        default => 't-blue',
    };
    $faIcon = function ($bi) {
        $map = [
            'bi-people-fill' => 'fas fa-users',
            'bi-people' => 'fas fa-users',
            'bi-cash-coin' => 'fas fa-money-bill-wave',
            'bi-cash-stack' => 'fas fa-wallet',
            'bi-wallet2' => 'fas fa-wallet',
            'bi-bullseye' => 'fas fa-bullseye',
            'bi-telephone' => 'fas fa-phone',
            'bi-lightning' => 'fas fa-bolt',
            'bi-calendar-event' => 'fas fa-calendar-alt',
            'bi-exclamation-triangle' => 'fas fa-exclamation-triangle',
            'bi-shield-check' => 'fas fa-shield-alt',
            'bi-briefcase' => 'fas fa-briefcase',
            'bi-folder2-open' => 'fas fa-folder-open',
            'bi-graph-up' => 'fas fa-chart-line',
            'bi-journal-check' => 'fas fa-file-invoice',
            'bi-pause-circle' => 'fas fa-pause-circle',
            'bi-check2-circle' => 'fas fa-check-circle',
            'bi-check-circle' => 'fas fa-check-circle',
            'bi-building' => 'fas fa-building',
            'bi-person-check' => 'fas fa-user-check',
            'bi-globe2' => 'fas fa-globe',
        ];
        return $map[$bi] ?? 'fas fa-chart-bar';
    };
    $wave = <<<'SVG'
<svg viewBox="0 0 180 150" preserveAspectRatio="xMaxYMax meet" aria-hidden="true">
  <path class="w0" d="M8 150 C18 108 42 78 88 72 C128 66 158 42 180 18 L180 150 Z"/>
  <path class="w1" d="M28 150 C36 118 58 92 98 88 C132 84 158 62 180 40 L180 150 Z"/>
  <path class="w2" d="M55 150 C62 128 82 112 118 110 C146 108 166 96 180 78 L180 150 Z"/>
</svg>
SVG;
    $sourceColorMap = ['vrm'=>'var(--theme-400)','channel'=>'var(--theme-primary)','manual'=>'#a855f7','walk-in'=>'#f97316','referral'=>'#06b6d4','digital'=>'#14b8a6'];
    $statusPalette = ['var(--theme-primary)','#7c3aed','#16a34a','#f59e0b','#ef4444','#06b6d4','#64748b','#db2777'];
    $money = fn ($n) => '₹ ' . number_format((float) $n, 0);
    $targetUnit = $dash['target']['unit'] ?? 'money';
    $fmtTarget = function ($n) use ($money, $targetUnit) {
        if ($targetUnit === 'calls' || $targetUnit === 'count') {
            return number_format((float) $n, 0);
        }
        return $money($n);
    };
    $targetSnap = $dash['target']['snap'] ?? [];
    $analytics = $dash['analytics'] ?? [];
    $trendBlock = $analytics['trend'] ?? [
        'title' => 'Leads & Cases Trend',
        'subtitle' => $dash['period_label'] ?? '',
        'labels' => $dash['trend']['labels'] ?? [],
        'series' => [],
    ];
    $distA = $analytics['distribution_a'] ?? ['show' => true, 'title' => 'Leads by Source', 'items' => [], 'center_value' => 0, 'center_label' => 'Total'];
    $distB = $analytics['distribution_b'] ?? ['show' => true, 'title' => 'Case Status', 'items' => [], 'center_value' => 0, 'center_label' => 'Cases'];
    $distAItems = $distA['items'] ?? [];
    $distBItems = $distB['items'] ?? [];
    $distATotal = max(1, array_sum($distAItems));
    $distBTotal = max(1, array_sum($distBItems));
    $colorFor = function ($key, $i) use ($sourceColorMap, $statusPalette) {
        $k = strtolower((string) $key);
        return $sourceColorMap[$k] ?? $statusPalette[$i % count($statusPalette)];
    };
    $interval = $dash['interval'] ?? 'weekly';
    $hasKpis = !empty($dash['kpis']);
    $hasError = !empty($dash['load_error']);
    $trendHasData = collect($trendBlock['series'] ?? [])->contains(fn ($s) => array_sum($s['data'] ?? []) > 0);
    $distAHasData = array_sum($distAItems) > 0;
    $distBHasData = array_sum($distBItems) > 0;
    $qaActions = $dash['actions'] ?? [];
@endphp

<div class="container-fluid p-4 ga-page" id="gaPage"
     data-period="{{ $dash['period'] }}"
     data-interval="{{ $interval }}"
     data-endpoint="{{ route('goal.analytics') }}">

    <div class="loading-overlay" id="gaLoading" aria-hidden="true"><div class="loading-spinner" role="status" aria-label="Loading"></div></div>

    @if($hasError)
        <div class="error-banner">
            <i class="bi bi-exclamation-octagon-fill"></i>
            <div>
                <strong>Analytics error</strong>
                <div class="small mt-1">{{ $dash['load_error'] }}</div>
            </div>
        </div>
    @endif

    <div class="page-head">
        <div>
            <h2>Analytics</h2>
            <p class="sub"><strong>{{ $dash['scope_label'] }}</strong> · Role-based performance for {{ $dash['role_label'] }} · {{ $dash['period_label'] }}</p>
            <span class="role-badge"><i class="bi bi-eye"></i> {{ $dash['badge'] }}</span>
        </div>
        <div class="head-controls">
            <div class="period-seg" role="group" aria-label="Date period">
                @foreach($dash['period_options'] as $key => $label)
                    <a href="{{ route('goal.analytics', ['period' => $key, 'interval' => $interval]) }}"
                       class="period-btn {{ $dash['period'] === $key ? 'active' : '' }}"
                       data-period="{{ $key }}">{{ $label }}</a>
                @endforeach
            </div>
            <div class="interval-seg" role="group" aria-label="Chart interval">
                <a href="{{ route('goal.analytics', ['period' => $dash['period'], 'interval' => 'weekly']) }}"
                   class="interval-btn {{ $interval === 'weekly' ? 'active' : '' }}" data-interval="weekly">Weekly</a>
                <a href="{{ route('goal.analytics', ['period' => $dash['period'], 'interval' => 'monthly']) }}"
                   class="interval-btn {{ $interval === 'monthly' ? 'active' : '' }}" data-interval="monthly">Monthly</a>
            </div>
        </div>
    </div>

    {{-- KPI row --}}
    <div class="row g-3 mb-3" id="gaKpiRow">
        @if($hasKpis)
            @foreach($dash['kpis'] as $kpi)
                <div class="col-12 col-sm-6 col-xl-3">
                    <a href="{{ $kpi['link'] }}" class="metric-card {{ $toneCard($kpi['tone'] ?? 'blue') }}">
                        <div class="metric-head">
                            <span class="metric-icon" aria-hidden="true"><i class="{{ $faIcon($kpi['icon'] ?? '') }}"></i></span>
                            <h6 class="metric-label">{{ $kpi['label'] }}</h6>
                        </div>
                        <div class="metric-body">
                            <p class="metric-value">{{ $kpi['value'] }}</p>
                            <p class="metric-trend">{{ $kpi['trend'] }}</p>
                            <span class="metric-link">{{ $kpi['link_text'] }}</span>
                        </div>
                        <div class="metric-wave" aria-hidden="true">{!! $wave !!}</div>
                    </a>
                </div>
            @endforeach
        @elseif(!$hasError)
            @for($i = 0; $i < 4; $i++)
                <div class="col-12 col-sm-6 col-xl-3"><div class="kpi-skel"></div></div>
            @endfor
        @else
            <div class="col-12">
                <div class="panel"><div class="error-state"><div class="illus"><i class="bi bi-bar-chart"></i></div><p>KPI metrics unavailable</p></div></div>
            </div>
        @endif
    </div>

    {{-- Target + Trend --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-5">
            <div class="panel">
                <div class="panel-h">
                    <div class="panel-title-row">
                        <span class="panel-ico purple"><i class="bi bi-bullseye"></i></span>
                        <div>
                            <h5>{{ $dash['target']['title'] }}</h5>
                            <p class="sub">{{ $dash['target']['subtitle'] }}</p>
                        </div>
                    </div>
                    <div class="target-head-right">
                        <a href="{{ route('goal.targets.index') }}" class="view-all">Details →</a>
                        <div class="tgt-amt">Target: {{ $fmtTarget($dash['target']['effective']) }}{{ $targetUnit === 'calls' ? ' calls' : '' }}</div>
                    </div>
                </div>
                <div class="panel-b">
                    @if($hasError)
                        <div class="error-state"><div class="illus"><i class="bi bi-exclamation-triangle"></i></div><p>Target data could not be loaded</p></div>
                    @else
                        <div class="target-layout">
                            <div class="donut-wrap">
                                <canvas id="gaChartTarget"></canvas>
                                <div class="donut-center"><div class="pct" id="gaTargetPct">{{ (int) $dash['target']['percent'] }}%</div><div class="lbl">Achieved</div></div>
                            </div>
                            <div>
                                <div class="target-ach" id="gaTargetAch">{{ $fmtTarget($dash['target']['actual']) }}{{ $targetUnit === 'calls' ? ' calls' : '' }}</div>
                                <div class="small text-muted fw-semibold">Achieved</div>
                                <div class="target-rem" id="gaTargetRem">{{ $fmtTarget($dash['target']['remaining']) }}{{ $targetUnit === 'calls' ? ' calls' : '' }} <span>Remaining</span></div>
                            </div>
                            <div class="stat-badges" id="gaTargetSnap">
                                @forelse($targetSnap as $snap)
                                    @php
                                        $sbClass = match ($snap['tone'] ?? 'g') {
                                            'b' => 'sb-blue',
                                            'p' => 'sb-purple',
                                            default => 'sb-green',
                                        };
                                    @endphp
                                    <div class="stat-badge {{ $sbClass }}">
                                        <span class="ico"><i class="bi {{ $snap['icon'] }}"></i></span>
                                        <div><div class="num">{{ $snap['n'] }}</div><div class="lbl">{{ $snap['l'] }}</div></div>
                                    </div>
                                @empty
                                    <div class="stat-badge sb-green">
                                        <span class="ico"><i class="bi bi-check-lg"></i></span>
                                        <div><div class="num">{{ $dash['metrics']['disbursed_cases'] ?? 0 }}</div><div class="lbl">Disbursed</div></div>
                                    </div>
                                    <div class="stat-badge sb-blue">
                                        <span class="ico"><i class="bi bi-clock"></i></span>
                                        <div><div class="num">{{ $dash['metrics']['pipeline_cases'] ?? 0 }}</div><div class="lbl">Pipeline</div></div>
                                    </div>
                                    <div class="stat-badge sb-purple">
                                        <span class="ico"><i class="bi bi-bullseye"></i></span>
                                        <div><div class="num">{{ $fmtTarget($dash['target']['effective']) }}</div><div class="lbl">Target</div></div>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-7">
            <div class="panel">
                <div class="panel-h">
                    <div class="panel-title-row">
                        <span class="panel-ico blue"><i class="bi bi-graph-up-arrow"></i></span>
                        <div>
                            <h5 id="gaTrendTitle">{{ $trendBlock['title'] }}</h5>
                            <p class="sub" id="gaTrendSub">{{ $trendBlock['subtitle'] }}</p>
                        </div>
                    </div>
                    <div class="panel-actions">
                        <div class="interval-seg">
                            <a href="{{ route('goal.analytics', ['period' => $dash['period'], 'interval' => 'weekly']) }}"
                               class="interval-btn {{ $interval === 'weekly' ? 'active' : '' }}">Weekly</a>
                            <a href="{{ route('goal.analytics', ['period' => $dash['period'], 'interval' => 'monthly']) }}"
                               class="interval-btn {{ $interval === 'monthly' ? 'active' : '' }}">Monthly</a>
                        </div>
                    </div>
                </div>
                <div class="panel-b">
                    <div class="trend-legend" id="gaTrendLegend">
                        @forelse($trendBlock['series'] ?? [] as $series)
                            <span><i style="background:{{ $series['color'] }}"></i> {{ $series['label'] }}</span>
                        @empty
                            <span class="text-muted">No series</span>
                        @endforelse
                    </div>
                    <div class="chart-box {{ $trendHasData || $hasError ? '' : 'is-empty' }}" id="gaTrendBox">
                        @if($hasError)
                            <div class="error-state"><div class="illus"><i class="bi bi-graph-up"></i></div><p>Trend chart unavailable</p></div>
                        @elseif(!$trendHasData)
                            <div class="empty-state"><div class="illus"><i class="bi bi-graph-up"></i></div><p>No trend data for this period</p></div>
                        @else
                            <canvas id="gaChartTrend"></canvas>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Distributions + Quick Actions --}}
    <div class="row g-3 mb-3">
        @if(!empty($distA['show']))
        <div class="col-12 col-md-6 {{ !empty($qaActions) ? 'col-xl-4' : 'col-xl-6' }}">
            <div class="panel">
                <div class="panel-h">
                    <div class="panel-title-row">
                        <span class="panel-ico purple"><i class="bi bi-pie-chart-fill"></i></span>
                        <div><h5 id="gaDistATitle">{{ $distA['title'] }}</h5></div>
                    </div>
                </div>
                <div class="panel-b">
                    @if($hasError)
                        <div class="error-state"><div class="illus"><i class="bi bi-pie-chart"></i></div><p>Chart unavailable</p></div>
                    @elseif(!$distAHasData)
                        <div class="empty-state"><div class="illus"><i class="bi bi-inbox"></i></div><p>No data in this period</p></div>
                    @else
                        <div class="donut-split">
                            <div class="donut-ring">
                                <canvas id="gaChartSource"></canvas>
                                <div class="center-lbl"><div class="num" id="gaDistACenter">{{ $distA['center_value'] }}</div><div class="txt">{{ $distA['center_label'] }}</div></div>
                            </div>
                            <div class="legend-list" id="gaDistALegend">
                                @foreach($distAItems as $src => $cnt)
                                    @php $color = $colorFor($src, $loop->index); @endphp
                                    <div class="legend-item">
                                        <span class="left"><i class="dot" style="background:{{ $color }}"></i> {{ ucfirst($src) }}</span>
                                        <span class="val">{{ $cnt }} ({{ round(($cnt / $distATotal) * 100) }}%)</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        @if(!empty($distB['show']))
        <div class="col-12 col-md-6 {{ !empty($qaActions) ? 'col-xl-4' : 'col-xl-6' }}">
            <div class="panel">
                <div class="panel-h">
                    <div class="panel-title-row">
                        <span class="panel-ico purple"><i class="bi bi-clipboard2-data"></i></span>
                        <div><h5 id="gaDistBTitle">{{ $distB['title'] }}</h5></div>
                    </div>
                </div>
                <div class="panel-b">
                    @if($hasError)
                        <div class="error-state"><div class="illus"><i class="bi bi-clipboard-data"></i></div><p>Chart unavailable</p></div>
                    @elseif(!$distBHasData)
                        <div class="empty-state"><div class="illus"><i class="bi bi-inbox"></i></div><p>No data in scope</p></div>
                    @else
                        <div class="donut-split">
                            <div class="donut-ring">
                                <canvas id="gaChartStatus"></canvas>
                                <div class="center-lbl"><div class="num" id="gaDistBCenter">{{ $distB['center_value'] }}</div><div class="txt">{{ $distB['center_label'] }}</div></div>
                            </div>
                            <div class="legend-list" id="gaDistBLegend">
                                @foreach($distBItems as $st => $cnt)
                                    @php $color = $colorFor($st, $loop->index); @endphp
                                    <div class="legend-item">
                                        <span class="left"><i class="dot" style="background:{{ $color }}"></i> {{ $st }}</span>
                                        <span class="val">{{ $cnt }} ({{ round(($cnt / $distBTotal) * 100) }}%)</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        @if(!empty($qaActions))
        <div class="col-12 col-xl-4">
            <div class="panel">
                <div class="panel-h">
                    <div class="panel-title-row">
                        <span class="panel-ico blue"><i class="bi bi-lightning-charge-fill"></i></span>
                        <div>
                            <h5>Quick Actions</h5>
                            <p class="sub">Shortcuts for {{ $dash['role_label'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="panel-b">
                    <div class="qa-grid">
                        @foreach($qaActions as $action)
                            <a href="{{ $action[0] }}" class="qa-tile {{ $action[1] }}">
                                <i class="bi {{ $action[2] }}"></i>
                                <span>{{ $action[3] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function ($) {
    'use strict';

    var charts = { target: null, trend: null, source: null, status: null };
    var page = $('#gaPage');
    var endpoint = page.data('endpoint');
    var hasError = @json($hasError);

    function setLoading(on) {
        page.toggleClass('is-loading', !!on);
        $('#gaLoading').attr('aria-hidden', on ? 'false' : 'true');
    }

    function destroyChart(key) {
        if (charts[key]) {
            charts[key].destroy();
            charts[key] = null;
        }
    }

    function makeDoughnut(el, labels, values, colors, cutout) {
        if (!el || typeof Chart === 'undefined') return null;
        return new Chart(el, {
            type: 'doughnut',
            data: {
                labels: labels.length ? labels : ['None'],
                datasets: [{
                    data: values.length ? values : [1],
                    backgroundColor: colors.length ? colors : ['#e2e8f0'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                cutout: cutout || '70%',
                plugins: { legend: { display: false }, tooltip: { enabled: labels.length > 0 } },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    function initCharts() {
        if (typeof Chart === 'undefined') {
            page.prepend('<div class="error-banner"><i class="bi bi-exclamation-octagon-fill"></i><div><strong>Chart library failed to load</strong><div class="small mt-1">Refresh the page or check your network.</div></div></div>');
            return;
        }
        if (hasError) return;

        var targetPct = {{ (int) ($dash['target']['percent'] ?? 0) }};
        var remainPct = Math.max(0, 100 - targetPct);
        var targetEl = document.getElementById('gaChartTarget');
        if (targetEl) {
            destroyChart('target');
            charts.target = makeDoughnut(
                targetEl,
                ['Achieved', 'Remaining'],
                [targetPct, remainPct || (targetPct ? 0 : 1)],
                ['#7c3aed', '#e2e8f0'],
                '72%'
            );
        }

        @if($trendHasData && !$hasError)
        var trendEl = document.getElementById('gaChartTrend');
        if (trendEl) {
            var trendSeries = @json($trendBlock['series'] ?? []);
            destroyChart('trend');
            charts.trend = new Chart(trendEl, {
                type: 'line',
                data: {
                    labels: @json($trendBlock['labels'] ?? []),
                    datasets: trendSeries.map(function (s) {
                        return {
                            label: s.label,
                            data: s.data,
                            borderColor: s.color,
                            backgroundColor: s.color + '1F',
                            fill: true,
                            tension: 0.35,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            borderWidth: 2.5
                        };
                    })
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { weight: '600', size: 11 } } },
                        y: { beginAtZero: true, ticks: { precision: 0, color: '#94a3b8' }, grid: { color: '#f1f5f9' } }
                    }
                }
            });
        }
        @endif

        @if($distAHasData && !empty($distA['show']) && !$hasError)
        charts.source = makeDoughnut(
            document.getElementById('gaChartSource'),
            @json(array_map(fn ($k) => ucfirst((string) $k), array_keys($distAItems))),
            @json(array_values($distAItems)),
            @json(collect(array_keys($distAItems))->values()->map(fn ($k, $i) => $colorFor($k, $i))->all())
        );
        @endif

        @if($distBHasData && !empty($distB['show']) && !$hasError)
        charts.status = makeDoughnut(
            document.getElementById('gaChartStatus'),
            @json(array_keys($distBItems)),
            @json(array_values($distBItems)),
            @json(collect(array_keys($distBItems))->values()->map(fn ($k, $i) => $colorFor($k, $i))->all())
        );
        @endif
    }

    // Soft loading feedback on filter navigation
    $(document).on('click', '.ga-page .period-btn, .ga-page .interval-btn', function () {
        setLoading(true);
    });

    $(function () {
        try {
            initCharts();
        } catch (err) {
            console.error(err);
            page.prepend('<div class="error-banner"><i class="bi bi-exclamation-octagon-fill"></i><div><strong>Unable to render charts</strong><div class="small mt-1">Please refresh and try again.</div></div></div>');
        } finally {
            setLoading(false);
        }
    });
})(jQuery);
</script>
@endpush
