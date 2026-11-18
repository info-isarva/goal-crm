@extends('layouts.app')

@push('styles')
@include('goal.partials.pd_hub_styles')
<style>
.pd-page.visits-page .pd-hero-brand{display:flex;align-items:flex-start;gap:12px;min-width:0}
.pd-page.visits-page .pd-hero-ico{
    width:44px;height:44px;border-radius:12px;flex:0 0 auto;
    display:inline-flex;align-items:center;justify-content:center;
    background:var(--theme-primary);color:#fff;font-size:1.2rem;
    box-shadow:0 8px 18px var(--theme-primary-ring);
}
.pd-page.visits-page .pd-kpi-row{grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
.pd-page.visits-page .vis-kpi{
    --kpi-bg:#eff6ff;--kpi-border:#dbeafe;--kpi-icon-bg:#dbeafe;--kpi-icon:#2563eb;
    --kpi-wave-a:#bfdbfe;--kpi-wave-b:#93c5fd;--kpi-wave-c:#60a5fa;
    position:relative;overflow:hidden;text-align:left;width:100%;appearance:none;-webkit-appearance:none;
    display:flex;align-items:center;gap:12px;min-height:92px;padding:14px 16px;
    border:1px solid var(--kpi-border);border-radius:16px;cursor:pointer;
    background:linear-gradient(145deg, #fff 0%, var(--kpi-bg) 55%, #fff 100%);
    box-shadow:0 6px 18px rgba(15,23,42,.05);transition:transform .15s ease,box-shadow .15s ease;
}
.pd-page.visits-page .vis-kpi:hover{transform:translateY(-2px);box-shadow:0 10px 22px rgba(15,23,42,.08)}
.pd-page.visits-page .vis-kpi.is-active{outline:2px solid var(--theme-primary);outline-offset:1px}
.pd-page.visits-page .vis-kpi.tone-blue{
    --kpi-bg:#eff6ff;--kpi-border:#dbeafe;--kpi-icon-bg:#dbeafe;--kpi-icon:#2563eb;
    --kpi-wave-a:#bfdbfe;--kpi-wave-b:#93c5fd;--kpi-wave-c:#60a5fa;
}
.pd-page.visits-page .vis-kpi.tone-amber{
    --kpi-bg:#fff7ed;--kpi-border:#ffedd5;--kpi-icon-bg:#ffedd5;--kpi-icon:#ea580c;
    --kpi-wave-a:#fed7aa;--kpi-wave-b:#fdba74;--kpi-wave-c:#fb923c;
}
.pd-page.visits-page .vis-kpi.tone-red{
    --kpi-bg:#fef2f2;--kpi-border:#fee2e2;--kpi-icon-bg:#fee2e2;--kpi-icon:#dc2626;
    --kpi-wave-a:#fecaca;--kpi-wave-b:#fca5a5;--kpi-wave-c:#f87171;
}
.pd-page.visits-page .vis-kpi.tone-green{
    --kpi-bg:#f0fdf4;--kpi-border:#dcfce7;--kpi-icon-bg:#dcfce7;--kpi-icon:#16a34a;
    --kpi-wave-a:#bbf7d0;--kpi-wave-b:#86efac;--kpi-wave-c:#4ade80;
}
.pd-page.visits-page .vis-kpi-ico{
    width:42px;height:42px;border-radius:12px;flex:0 0 auto;position:relative;z-index:2;
    display:inline-flex;align-items:center;justify-content:center;
    background:var(--kpi-icon-bg);color:var(--kpi-icon);font-size:1.15rem;
}
.pd-page.visits-page .vis-kpi-body{position:relative;z-index:2;min-width:0}
.pd-page.visits-page .vis-kpi-value{
    margin:0;font-size:1.65rem;font-weight:800;color:#0f172a;line-height:1;letter-spacing:-.02em;
}
.pd-page.visits-page .vis-kpi-label{
    margin:4px 0 0;font-size:.8rem;font-weight:700;color:#475569;line-height:1.2;
}
.pd-page.visits-page .vis-kpi-wave{
    position:absolute;right:0;bottom:0;width:48%;height:70%;pointer-events:none;z-index:1;
}
.pd-page.visits-page .vis-kpi-wave svg{display:block;width:100%;height:100%}
.pd-page.visits-page .vis-kpi-wave .w0{fill:var(--kpi-wave-a);opacity:.55}
.pd-page.visits-page .vis-kpi-wave .w1{fill:var(--kpi-wave-b);opacity:.4}
.pd-page.visits-page .vis-kpi-wave .w2{fill:var(--kpi-wave-c);opacity:.28}
.pd-page.visits-page .vis-tabs{
    display:flex;flex-wrap:wrap;gap:8px;margin-bottom:.95rem;
}
.pd-page.visits-page .vis-tab{
    display:inline-flex;align-items:center;gap:7px;padding:.48rem .95rem;border-radius:999px;
    cursor:pointer;border:1px solid #e2e8f0;background:#fff;color:#334155;
    font-size:.82rem;font-weight:700;text-decoration:none;
    box-shadow:0 1px 2px rgba(15,23,42,.03);
}
.pd-page.visits-page .vis-tab i{font-size:.92rem;opacity:.9}
.pd-page.visits-page .vis-tab:hover{background:#f8fafc;color:#0f172a;border-color:#cbd5e1}
.pd-page.visits-page .vis-tab.active{
    background:var(--theme-primary);border-color:var(--theme-primary);color:#fff;
    box-shadow:0 6px 14px var(--theme-primary-ring);
}
.pd-page.visits-page .vis-tab .n{
    min-width:22px;height:22px;padding:0 7px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;
    background:#f1f5f9;font-size:.72rem;font-weight:800;color:#475569;
}
.pd-page.visits-page .vis-tab.active .n{background:rgba(255,255,255,.22);color:#fff}
.pd-page.visits-page .vis-filters{
    display:grid;grid-template-columns:minmax(160px,1.4fr) repeat(4,minmax(110px,1fr)) minmax(120px,1fr) auto;
    gap:10px;align-items:end;margin-bottom:.85rem;padding:.85rem 1rem;
    background:#fff;border:1px solid #e8edf5;border-radius:14px;
}
.pd-page.visits-page .vis-filters label{display:block;font-size:.72rem;font-weight:700;color:#64748b;margin:0 0 4px}
.pd-page.visits-page .vis-filters .form-control,
.pd-page.visits-page .vis-filters .form-select{min-height:38px;height:38px;border-radius:9px;font-size:.84rem}
.pd-page.visits-page .vis-top,
.pd-page.visits-page .vis-bottom{
    display:grid;gap:14px;align-items:start;margin-bottom:14px;
}
.pd-page.visits-page .vis-top{grid-template-columns:minmax(0,1.75fr) minmax(260px,.85fr)}
.pd-page.visits-page .vis-bottom{grid-template-columns:minmax(0,1.45fr) minmax(260px,1fr)}
.pd-page.visits-page .vis-card{
    background:#fff;border:1px solid #e8edf5;border-radius:16px;overflow:hidden;
    box-shadow:0 8px 24px rgba(15,23,42,.04);
}
.pd-page.visits-page .vis-card-head{
    display:flex;align-items:flex-start;justify-content:space-between;gap:12px;
    padding:14px 16px 10px;
}
.pd-page.visits-page .vis-card-head .left{display:flex;align-items:flex-start;gap:10px;min-width:0}
.pd-page.visits-page .vis-ico{
    width:34px;height:34px;border-radius:10px;flex:0 0 auto;
    display:inline-flex;align-items:center;justify-content:center;font-size:1rem;color:#fff;
}
.pd-page.visits-page .vis-ico.green{background:var(--theme-primary)}
.pd-page.visits-page .vis-ico.red{background:#ef4444}
.pd-page.visits-page .vis-card-head h5{
    margin:0;font-size:.95rem;font-weight:800;color:#0f172a;letter-spacing:-.01em;
}
.pd-page.visits-page .vis-card-head p{
    margin:2px 0 0;font-size:.74rem;color:#64748b;line-height:1.35;
}
.pd-page.visits-page .vis-export{
    display:inline-flex;align-items:center;gap:6px;height:34px;padding:0 12px;
    border:1px solid #d1fae5;border-radius:10px;background:#fff;color:var(--theme-primary);
    font-size:.8rem;font-weight:700;text-decoration:none;white-space:nowrap;cursor:pointer;
}
.pd-page.visits-page .vis-export:hover,
.pd-page.visits-page .vis-export:focus{background:#ecfdf5;color:var(--theme-primary)}
.pd-page.visits-page .vis-export-menu{
    min-width:180px;border:1px solid #e8edf5;border-radius:12px;padding:6px;
    box-shadow:0 12px 28px rgba(15,23,42,.12);
}
.pd-page.visits-page .vis-export-menu .dropdown-item{
    display:flex;align-items:center;gap:8px;border-radius:8px;font-size:.84rem;font-weight:600;padding:8px 10px;
}
.pd-page.visits-page .vis-export-menu .dropdown-item i{font-size:1rem}
.pd-page.visits-page .pd-pill.sched{background:#dbeafe;color:#1d4ed8}
.pd-page.visits-page .pd-pill.up{background:#ffedd5;color:#c2410c}
.pd-page.visits-page .pd-pill.over{background:#fee2e2;color:#b91c1c}
.pd-page.visits-page .pd-pill.done{background:#dcfce7;color:#15803d}
.pd-page.visits-page .pd-pill.esc-bm{background:#ffedd5;color:#c2410c}
.pd-page.visits-page .pd-pill.esc-cluster{background:#fee2e2;color:#b91c1c}
.pd-page.visits-page .vis-type{display:inline-flex;align-items:center;gap:6px;white-space:nowrap}
.pd-page.visits-page .vis-type.visit{color:#2563eb}
.pd-page.visits-page .vis-type.followup{color:#7c3aed}
.pd-page.visits-page .vis-when{display:inline-flex;align-items:center;gap:6px;color:#475569;white-space:nowrap}
.pd-page.visits-page .vis-when i{color:#94a3b8}
.pd-page.visits-page .vis-act{
    width:30px;height:30px;border-radius:999px;border:1px solid #e2e8f0;background:#fff;
    color:#64748b;display:inline-flex;align-items:center;justify-content:center;
    padding:0;text-decoration:none;cursor:pointer;
}
.pd-page.visits-page .vis-act i{pointer-events:none}
.pd-page.visits-page .vis-act:hover{background:#f8fafc;color:#0f172a}
.pd-page.visits-page .cal-nav{
    display:flex;align-items:center;justify-content:space-between;gap:6px;
    padding:2px 14px 2px;
}
.pd-page.visits-page .cal-nav button{
    width:26px;height:26px;border-radius:8px;border:1px solid #e2e8f0;
    background:#fff;color:#475569;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;
    font-size:.75rem;
}
.pd-page.visits-page .cal-nav button:hover{background:#f8fafc;color:#0f172a}
.pd-page.visits-page .cal-month-label{
    display:inline-flex;align-items:center;gap:4px;border:0;background:transparent;
    font-size:.8rem;font-weight:800;color:#0f172a;cursor:default;padding:0;
}
.pd-page.visits-page .cal-month-label i{font-size:.62rem;color:#94a3b8}
.pd-page.visits-page .cal-grid{
    display:grid;grid-template-columns:repeat(7,minmax(0,1fr));
    gap:4px;padding:2px 14px 10px;width:100%;
}
.pd-page.visits-page .cal-dow{
    height:18px;text-align:center;font-size:.62rem;font-weight:700;color:#94a3b8;
    display:flex;align-items:center;justify-content:center;padding:0;
}
.pd-page.visits-page .cal-day{
    width:100%;aspect-ratio:1;max-width:36px;max-height:36px;margin:0 auto;
    border:0;border-radius:999px;background:transparent;cursor:pointer;
    display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0;
    color:#334155;font-size:.72rem;font-weight:600;padding:0;position:relative;
}
.pd-page.visits-page .cal-day:hover{background:#f1f5f9}
.pd-page.visits-page .cal-day.mute{color:#cbd5e1;pointer-events:none}
.pd-page.visits-page .cal-day.has{background:#e8f8f3;color:#0f766e}
.pd-page.visits-page .cal-day.today{box-shadow:inset 0 0 0 1.5px #99f6e4}
.pd-page.visits-page .cal-day.active{
    background:var(--theme-primary);color:#fff;font-weight:800;
    box-shadow:0 3px 8px rgba(15,118,110,.25);
}
.pd-page.visits-page .cal-day .dot{
    width:4px;height:4px;border-radius:999px;background:#ef4444;position:absolute;bottom:3px;
}
.pd-page.visits-page .cal-day.active .dot,
.pd-page.visits-page .cal-day.active.has .dot{background:#fff}
.pd-page.visits-page .cal-day.has .dot{background:#14b8a6}
.pd-page.visits-page .vis-sched-head{
    display:flex;align-items:center;justify-content:space-between;gap:10px;
    padding:8px 14px 6px;border-top:1px solid #f1f5f9;
}
.pd-page.visits-page .vis-sched-head h5{margin:0;font-size:.86rem;font-weight:800;color:#0f172a}
.pd-page.visits-page .vis-sched-count{
    display:inline-flex;align-items:center;justify-content:center;
    min-height:24px;padding:0 10px;border-radius:999px;
    background:#e6f7f2;color:#0f766e;font-size:.7rem;font-weight:800;white-space:nowrap;
}
.pd-page.visits-page .vis-sched-list{padding:0 4px 8px;max-height:260px;overflow:auto}
.pd-page.visits-page .vis-sched-item{
    display:grid;grid-template-columns:84px minmax(0,1fr) auto auto;gap:8px;align-items:center;
    padding:10px 10px;border-top:1px solid #f1f5f9;text-decoration:none;color:inherit;
}
.pd-page.visits-page .vis-sched-item:hover{background:#f8fafc}
.pd-page.visits-page .vis-sched-time{
    display:inline-flex;align-items:center;gap:5px;font-size:.74rem;font-weight:700;color:#f59e0b;white-space:nowrap;
}
.pd-page.visits-page .vis-sched-who .n{font-size:.82rem;font-weight:800;color:#0f172a;line-height:1.2}
.pd-page.visits-page .vis-sched-who .c{font-size:.7rem;color:#64748b;margin-top:2px}
.pd-page.visits-page .vis-sched-asg{font-size:.74rem;color:#475569;font-weight:600;white-space:nowrap}
.pd-page.visits-page .vis-sched-type{
    width:26px;height:26px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:.82rem;
}
.pd-page.visits-page .vis-sched-type.visit{background:#dbeafe;color:#2563eb}
.pd-page.visits-page .vis-sched-type.followup{background:#ede9fe;color:#7c3aed}
.pd-page.visits-page .vis-sched-empty{padding:18px 14px;text-align:center;color:#94a3b8;font-size:.8rem}
.pd-page.visits-page .vis-act-list{padding:4px 16px 14px;max-height:320px;overflow:auto}
.pd-page.visits-page .vis-act-item{
    display:grid;grid-template-columns:18px minmax(0,1fr) auto;gap:10px;align-items:start;
    position:relative;padding:0 0 16px;
}
.pd-page.visits-page .vis-act-item:last-child{padding-bottom:0}
.pd-page.visits-page .vis-act-rail{
    width:10px;display:flex;flex-direction:column;align-items:center;position:relative;min-height:100%;
}
.pd-page.visits-page .vis-act-dot{
    width:10px;height:10px;border-radius:999px;background:var(--theme-primary);
    border:2px solid #ccfbf1;flex:0 0 auto;z-index:1;margin-top:4px;
}
.pd-page.visits-page .vis-act-item:not(:last-child) .vis-act-rail::after{
    content:"";position:absolute;top:14px;bottom:-16px;width:2px;background:#e2e8f0;
}
.pd-page.visits-page .vis-act-body{min-width:0}
.pd-page.visits-page .vis-act-body .t{font-size:.82rem;font-weight:700;color:#0f172a;line-height:1.35}
.pd-page.visits-page .vis-act-body .m{font-size:.72rem;color:#64748b;margin-top:2px}
.pd-page.visits-page .vis-act-time{font-size:.7rem;color:#94a3b8;white-space:nowrap;padding-top:3px}
.pd-page.visits-page .vis-act-empty{padding:22px 8px;text-align:center;color:#94a3b8;font-size:.82rem}
@media (max-width:1199.98px){
    .pd-page.visits-page .vis-top,
    .pd-page.visits-page .vis-bottom{grid-template-columns:1fr}
    .pd-page.visits-page .vis-filters{grid-template-columns:1fr 1fr 1fr}
}
@media (max-width:767.98px){
    .pd-page.visits-page .pd-kpi-row{grid-template-columns:1fr 1fr}
    .pd-page.visits-page .vis-filters{grid-template-columns:1fr}
    .pd-page.visits-page .vis-sched-item{grid-template-columns:78px minmax(0,1fr) auto;gap:8px}
    .pd-page.visits-page .vis-sched-asg{grid-column:2;font-size:.7rem}
}
</style>
@endpush

@section('content')
@php
    $tab = $filters['tab'] ?? 'all';
    $statusClass = fn ($s) => match ($s) {
        'Completed' => 'done',
        'Overdue' => 'over',
        'Upcoming' => 'up',
        default => 'sched',
    };
    $typeIco = fn ($t) => $t === 'visit' ? 'bi-geo-alt' : 'bi-telephone';
    $bucketOf = function ($task) {
        if ($task->isCompleted()) {
            return 'completed';
        }
        if ($task->isOverdue()) {
            return 'overdue';
        }
        if ($task->isDueToday()) {
            return 'today';
        }
        if ($task->isUpcoming()) {
            return 'upcoming';
        }

        return 'today';
    };
@endphp
<div class="container-fluid p-4 pd-page visits-page" id="visBoard"
     data-initial-tab="{{ $tab }}"
     data-initial-day="{{ $filters['day'] ?? '' }}"
     data-cal-month="{{ $calendarMonth->format('Y-m') }}">
    @php
        $kpiWave = <<<'SVG'
<svg viewBox="0 0 180 150" preserveAspectRatio="xMaxYMax meet" aria-hidden="true">
  <path class="w0" d="M8 150 C18 108 42 78 88 72 C128 66 158 42 180 18 L180 150 Z"/>
  <path class="w1" d="M28 150 C36 118 58 92 98 88 C132 84 158 62 180 40 L180 150 Z"/>
  <path class="w2" d="M55 150 C62 128 82 112 118 110 C146 108 166 96 180 78 L180 150 Z"/>
</svg>
SVG;
    @endphp
    <div class="pd-hero">
        <div class="pd-hero-brand">
            <span class="pd-hero-ico" aria-hidden="true"><i class="bi bi-calendar2-check"></i></span>
            <div>
                <h1 class="pd-title">Customer Visits &amp; Follow-ups</h1>
                <p class="pd-sub">Schedule, track and complete customer visits and case follow-up tasks.</p>
                <ul class="pd-crumbs" style="margin-top:8px;margin-bottom:0">
                    <li><a href="{{ url('/') }}">Home</a></li>
                    <li class="sep">›</li>
                    <li>Loan Processing</li>
                    <li class="sep">›</li>
                    <li>Customer Visits &amp; Follow-ups</li>
                </ul>
            </div>
        </div>
        <div class="pd-hero-actions">
            <button type="button" class="pd-link-btn primary" data-bs-toggle="modal" data-bs-target="#visScheduleModal">
                <i class="bi bi-plus-lg"></i> Schedule Visit / Follow-up
            </button>
        </div>
    </div>

    <div class="pd-kpi-row">
        <button type="button" class="vis-kpi tone-blue {{ $tab === 'today' ? 'is-active' : '' }}" data-vis-tab="today">
            <span class="vis-kpi-ico"><i class="bi bi-calendar3"></i></span>
            <span class="vis-kpi-body">
                <p class="vis-kpi-value" data-kpi="today">{{ number_format($kpis['today']) }}</p>
                <p class="vis-kpi-label">Today's Visits</p>
            </span>
            <span class="vis-kpi-wave">{!! $kpiWave !!}</span>
        </button>
        <button type="button" class="vis-kpi tone-amber {{ $tab === 'upcoming' ? 'is-active' : '' }}" data-vis-tab="upcoming">
            <span class="vis-kpi-ico"><i class="bi bi-calendar-event"></i></span>
            <span class="vis-kpi-body">
                <p class="vis-kpi-value" data-kpi="upcoming">{{ number_format($kpis['upcoming']) }}</p>
                <p class="vis-kpi-label">Upcoming</p>
            </span>
            <span class="vis-kpi-wave">{!! $kpiWave !!}</span>
        </button>
        <button type="button" class="vis-kpi tone-red {{ $tab === 'overdue' ? 'is-active' : '' }}" data-vis-tab="overdue">
            <span class="vis-kpi-ico"><i class="bi bi-exclamation-triangle"></i></span>
            <span class="vis-kpi-body">
                <p class="vis-kpi-value" data-kpi="overdue">{{ number_format($kpis['overdue']) }}</p>
                <p class="vis-kpi-label">Overdue Tasks</p>
            </span>
            <span class="vis-kpi-wave">{!! $kpiWave !!}</span>
        </button>
        <button type="button" class="vis-kpi tone-green {{ $tab === 'completed' ? 'is-active' : '' }}" data-vis-tab="completed">
            <span class="vis-kpi-ico"><i class="bi bi-check-circle"></i></span>
            <span class="vis-kpi-body">
                <p class="vis-kpi-value" data-kpi="completed">{{ number_format($kpis['completed_month']) }}</p>
                <p class="vis-kpi-label">Completed This Month</p>
            </span>
            <span class="vis-kpi-wave">{!! $kpiWave !!}</span>
        </button>
    </div>

    <div class="vis-tabs">
        <button type="button" class="vis-tab {{ $tab === 'all' ? 'active' : '' }}" data-vis-tab="all">
            <i class="bi bi-list-ul"></i> All Tasks <span class="n" data-tab-count="all">{{ $kpis['all'] }}</span>
        </button>
        <button type="button" class="vis-tab {{ $tab === 'today' ? 'active' : '' }}" data-vis-tab="today">
            <i class="bi bi-calendar3"></i> Today <span class="n" data-tab-count="today">{{ $kpis['today'] }}</span>
        </button>
        <button type="button" class="vis-tab {{ $tab === 'upcoming' ? 'active' : '' }}" data-vis-tab="upcoming">
            <i class="bi bi-calendar-event"></i> Upcoming <span class="n" data-tab-count="upcoming">{{ $kpis['upcoming'] }}</span>
        </button>
        <button type="button" class="vis-tab {{ $tab === 'overdue' ? 'active' : '' }}" data-vis-tab="overdue">
            <i class="bi bi-exclamation-triangle"></i> Overdue <span class="n" data-tab-count="overdue">{{ $kpis['overdue'] }}</span>
        </button>
        <button type="button" class="vis-tab {{ $tab === 'completed' ? 'active' : '' }}" data-vis-tab="completed">
            <i class="bi bi-check-circle"></i> Completed <span class="n" data-tab-count="completed">{{ $kpis['completed_month'] }}</span>
        </button>
    </div>

    <form method="GET" action="{{ route('goal.visits.index') }}" class="vis-filters" id="visFilterForm">
        <div>
            <label>Search</label>
            <input type="search" name="q" id="visLiveSearch" class="form-control" value="{{ $filters['q'] }}" placeholder="Case Ref / Customer" autocomplete="off">
        </div>
        <div>
            <label>Branch</label>
            <select name="branch" class="form-select">
                <option value="">All</option>
                @foreach($branches as $br)
                    <option value="{{ $br->id }}" @selected((int)($filters['branch'] ?? 0) === (int)$br->id)>{{ $br->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Assigned To</label>
            <select name="assigned_to" class="form-select">
                <option value="">All</option>
                @foreach($assignees as $u)
                    <option value="{{ $u->id }}" @selected((int)($filters['assigned_to'] ?? 0) === (int)$u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Task Type</label>
            <select name="task_type" class="form-select">
                <option value="">All</option>
                <option value="visit" @selected($filters['task_type'] === 'visit')>Customer Visit</option>
                <option value="followup" @selected($filters['task_type'] === 'followup')>Follow-up</option>
            </select>
        </div>
        <div>
            <label>Date From</label>
            <input type="text" name="date_from" class="form-control js-datepicker" value="{{ $filters['date_from'] }}" placeholder="dd-mm-yyyy" autocomplete="off">
        </div>
        <div>
            <label>Date To</label>
            <input type="text" name="date_to" class="form-control js-datepicker" value="{{ $filters['date_to'] }}" placeholder="dd-mm-yyyy" autocomplete="off">
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="pd-btn" style="height:38px"><i class="bi bi-funnel"></i> Apply</button>
            <a href="{{ route('goal.visits.index') }}" class="pd-btn-ghost" style="height:38px">Reset</a>
        </div>
    </form>

    <div class="vis-top">
        <div class="vis-card">
            <div class="vis-card-head">
                <div class="left">
                    <span class="vis-ico green" aria-hidden="true"><i class="bi bi-calendar2-check"></i></span>
                    <div>
                        <h5>Visit &amp; Follow-up Register</h5>
                        <p>List of scheduled visits and follow-up tasks</p>
                    </div>
                </div>
                <div class="dropdown">
                    <button type="button" class="vis-export dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-download"></i> Export
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end vis-export-menu">
                        <li>
                            <a class="dropdown-item" href="{{ route('goal.visits.export', array_merge(request()->query(), ['format' => 'pdf'])) }}">
                                <i class="bi bi-file-earmark-pdf text-danger"></i> Export to PDF
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('goal.visits.export', array_merge(request()->query(), ['format' => 'excel'])) }}">
                                <i class="bi bi-file-earmark-excel text-success"></i> Export to Excel
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('goal.visits.export', array_merge(request()->query(), ['format' => 'csv'])) }}">
                                <i class="bi bi-filetype-csv text-primary"></i> Export to CSV
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="pd-table-wrap">
                <table class="pd-table align-middle" id="visRegisterTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Case Reference</th>
                            <th>Customer</th>
                            <th>Task Type</th>
                            <th>Scheduled Date &amp; Time</th>
                            <th>Assigned To</th>
                            <th>Branch</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($tasks as $i => $task)
                        @php
                            $st = $task->displayStatus();
                            $bucket = $bucketOf($task);
                            $dueKey = optional($task->due_at)->format('Y-m-d') ?: '';
                            $dueTime = optional($task->due_at)->format('h:i A') ?: '';
                            $typeKey = $task->task_type === 'followup' ? 'followup' : 'visit';
                            $searchHay = strtolower(($task->loanCase?->case_no ?: '').' '.($task->loanCase?->customer_name ?: '').' '.($task->assignee?->name ?: ''));
                            $caseUrl = $task->loanCase
                                ? route('goal.cases.show', ['case' => $task->loanCase, 'tab' => 'visits'])
                                : '';
                        @endphp
                        <tr class="vis-row"
                            data-bucket="{{ $bucket }}"
                            data-due="{{ $dueKey }}"
                            data-time="{{ $dueTime }}"
                            data-sort="{{ optional($task->due_at)->format('Y-m-d H:i:s') ?: '' }}"
                            data-customer="{{ e($task->loanCase?->customer_name ?: '—') }}"
                            data-case="{{ e($task->loanCase?->case_no ?: '—') }}"
                            data-assignee="{{ e($task->assignee?->name ?: '—') }}"
                            data-type="{{ $task->task_type }}"
                            data-href="{{ $caseUrl }}"
                            data-search="{{ e($searchHay) }}">
                            <td class="vis-row-num">{{ $i + 1 }}</td>
                            <td>
                                @if($task->loanCase)
                                    <a class="pd-case" href="{{ $caseUrl }}">{{ $task->loanCase->case_no }}</a>
                                @else — @endif
                            </td>
                            <td><span class="pd-cust">{{ $task->loanCase?->customer_name ?: '—' }}</span></td>
                            <td>
                                <span class="vis-type {{ $typeKey }}">
                                    <i class="bi {{ $typeIco($task->task_type) }}"></i>{{ $task->typeLabel() }}
                                </span>
                            </td>
                            <td>
                                <span class="vis-when">
                                    <i class="bi bi-calendar3"></i>
                                    {{ optional($task->due_at)->format('d M Y, h:i A') ?: '—' }}
                                </span>
                            </td>
                            <td>{{ $task->assignee?->name ?: '—' }}</td>
                            <td>{{ $task->loanCase?->orgUnit?->name ?: '—' }}</td>
                            <td><span class="pd-pill {{ $statusClass($st) }}">{{ $st }}</span></td>
                            <td>
                                <div class="d-flex gap-1">
                                    @if($task->loanCase)
                                        <a href="{{ $caseUrl }}" class="vis-act" title="View case" aria-label="View case" data-bs-toggle="tooltip"><i class="bi bi-eye"></i></a>
                                    @endif
                                    @if(! $task->isCompleted())
                                        <button type="button" class="vis-act" title="Edit visit / follow-up" aria-label="Edit visit / follow-up"
                                                data-bs-toggle="modal" data-bs-target="#visEditModal"
                                                data-id="{{ $task->id }}"
                                                data-type="{{ $task->task_type }}"
                                                data-date="{{ optional($task->due_at)->format('Y-m-d') }}"
                                                data-time="{{ optional($task->due_at)->format('H:i') }}"
                                                data-assigned="{{ $task->assigned_to }}"
                                                data-branch-id="{{ $task->loanCase?->goal_org_unit_id }}"
                                                data-branch="{{ e($task->loanCase?->orgUnit?->name ?: '') }}"
                                                data-case-label="{{ e(($task->loanCase?->case_no ?: '—').' — '.($task->loanCase?->customer_name ?: '').' ('.($task->loanCase?->status ?: '').')') }}"
                                                data-notes="{{ e($task->notes) }}"
                                                data-location="{{ e($task->location) }}"
                                                data-action="{{ route('goal.visits.update', $task) }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="vis-act" title="Mark complete" aria-label="Mark complete"
                                                data-bs-toggle="modal" data-bs-target="#visCompleteModal"
                                                data-action="{{ route('goal.visits.complete', $task) }}"
                                                data-title="{{ $task->typeLabel() }} — {{ $task->loanCase?->customer_name }}">
                                            <i class="bi bi-check2"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                        <tr id="visEmptyRow" class="{{ $tasks->isEmpty() ? '' : 'd-none' }}">
                            <td colspan="9" class="pd-col-empty">
                                <div class="pd-empty">
                                    <i class="bi bi-geo-alt"></i>
                                    No visit / follow-up tasks for this filter.
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="pd-foot">
                <span id="visShowingLabel">Showing {{ $tasks->count() }} task(s)</span>
            </div>
        </div>

        <div class="vis-card">
            <div class="vis-card-head">
                <div class="left">
                    <span class="vis-ico green" aria-hidden="true"><i class="bi bi-calendar3"></i></span>
                    <div>
                        <h5>Visit Calendar</h5>
                        <p>View and manage scheduled visits and follow-ups.</p>
                    </div>
                </div>
            </div>
            <div class="cal-nav">
                <button type="button" id="visCalPrev" aria-label="Previous month"><i class="bi bi-chevron-left"></i></button>
                <span class="cal-month-label" id="visCalLabel">{{ $calendarMonth->format('F Y') }} <i class="bi bi-caret-down-fill"></i></span>
                <button type="button" id="visCalNext" aria-label="Next month"><i class="bi bi-chevron-right"></i></button>
            </div>
            <div class="cal-grid" id="visCalGrid"></div>
            <div class="vis-sched-head">
                <h5 id="visSchedTitle">Today's Schedule</h5>
                <span class="vis-sched-count" id="visSchedCount">0 Tasks</span>
            </div>
            <div class="vis-sched-list" id="visSchedList">
                <div class="vis-sched-empty">No visits scheduled for this day.</div>
            </div>
        </div>
    </div>

    <div class="vis-bottom">
        <div class="vis-card">
            <div class="vis-card-head">
                <div class="left">
                    <span class="vis-ico red" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></span>
                    <div>
                        <h5>Overdue Follow-ups</h5>
                        <p>Tasks past due · escalate to BM / Cluster Head</p>
                    </div>
                </div>
            </div>
            <div class="pd-table-wrap">
                <table class="pd-table align-middle">
                    <thead>
                        <tr>
                            <th>Task Type</th>
                            <th>Customer</th>
                            <th>Case Reference</th>
                            <th>Scheduled Date</th>
                            <th>Assigned To</th>
                            <th>Escalation</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($overdueTasks as $task)
                        @php
                            $typeKey = $task->task_type === 'followup' ? 'followup' : 'visit';
                            $escClass = match ($task->escalated_level) {
                                'cluster' => 'esc-cluster',
                                'bm' => 'esc-bm',
                                default => 'over',
                            };
                        @endphp
                        <tr>
                            <td>
                                <span class="vis-type {{ $typeKey }}">
                                    <i class="bi {{ $typeIco($task->task_type) }}"></i>{{ $task->typeLabel() }}
                                </span>
                            </td>
                            <td>{{ $task->loanCase?->customer_name ?: '—' }}</td>
                            <td>
                                @if($task->loanCase)
                                    <a class="pd-case" href="{{ route('goal.cases.show', ['case' => $task->loanCase, 'tab' => 'visits']) }}">{{ $task->loanCase->case_no }}</a>
                                @else — @endif
                            </td>
                            <td>
                                <span class="vis-when">
                                    <i class="bi bi-calendar3"></i>
                                    {{ optional($task->due_at)->format('d M Y, h:i A') ?: '—' }}
                                </span>
                            </td>
                            <td>{{ $task->assignee?->name ?: '—' }}</td>
                            <td>
                                @if($task->escalated_level)
                                    <span class="pd-pill {{ $escClass }}">{{ $task->escalationLabel() }}</span>
                                @else
                                    <span class="text-muted">Pending</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">No overdue tasks.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="vis-card">
            <div class="vis-card-head">
                <div class="left">
                    <span class="vis-ico green" aria-hidden="true"><i class="bi bi-layers"></i></span>
                    <div>
                        <h5>Task Activity</h5>
                        <p>Recent visit and follow-up updates</p>
                    </div>
                </div>
            </div>
            <div class="vis-act-list">
                @forelse($activity as $task)
                    @php
                        if ($task->isCompleted()) {
                            $actTitle = $task->typeLabel().' completed for '.($task->loanCase?->customer_name ?: 'case');
                        } elseif ($task->escalated_level) {
                            $actTitle = 'Overdue — '.$task->escalationLabel().' · '.($task->loanCase?->customer_name ?: 'case');
                        } else {
                            $actTitle = $task->typeLabel().' scheduled for '.($task->loanCase?->customer_name ?: 'case');
                        }
                        $by = $task->assignee?->name;
                    @endphp
                    <div class="vis-act-item">
                        <div class="vis-act-rail"><span class="vis-act-dot"></span></div>
                        <div class="vis-act-body">
                            <div class="t">{{ $actTitle }}@if($by) by {{ $by }}@endif</div>
                            <div class="m">{{ $task->loanCase?->case_no ?: '—' }}</div>
                        </div>
                        <div class="vis-act-time">{{ optional($task->updated_at)->format('d M, h:i A') }}</div>
                    </div>
                @empty
                    <div class="vis-act-empty">No recent task activity.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@include('goal.visits.partials.modals')
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.IsarvaDatepicker) window.IsarvaDatepicker.init(document);

    if (window.bootstrap && bootstrap.Tooltip) {
        document.querySelectorAll('.visits-page .vis-act[title]').forEach(function (el) {
            bootstrap.Tooltip.getOrCreateInstance(el, {
                container: 'body',
                trigger: 'hover focus',
                title: el.getAttribute('title') || el.getAttribute('aria-label') || ''
            });
        });
    }

    var board = document.getElementById('visBoard');
    if (board) {
        function todayKey() {
            var d = new Date();
            return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        }
        function esc(s) {
            return String(s == null ? '' : s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }
        function fmtDayLabel(key) {
            if (!key) return '';
            var p = key.split('-');
            var dt = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
            var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            return dt.getDate() + ' ' + months[dt.getMonth()] + ' ' + dt.getFullYear();
        }

        var state = {
            tab: board.getAttribute('data-initial-tab') || 'all',
            day: board.getAttribute('data-initial-day') || todayKey(),
            cal: board.getAttribute('data-cal-month') || todayKey().slice(0, 7)
        };
        var monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];

        function rows() {
            return Array.prototype.slice.call(document.querySelectorAll('#visRegisterTable tr.vis-row'));
        }

        function syncUrl() {
            try {
                var url = new URL(window.location.href);
                if (state.tab && state.tab !== 'all') url.searchParams.set('tab', state.tab);
                else url.searchParams.delete('tab');
                if (state.day) url.searchParams.set('day', state.day);
                else url.searchParams.delete('day');
                if (state.cal) url.searchParams.set('cal', state.cal);
                history.replaceState(null, '', url.pathname + url.search);
            } catch (e) {}
        }

        function renderSchedule() {
            var list = document.getElementById('visSchedList');
            var title = document.getElementById('visSchedTitle');
            var countEl = document.getElementById('visSchedCount');
            if (!list) return;
            var day = state.day || todayKey();
            var isToday = day === todayKey();
            if (title) {
                title.textContent = (isToday ? "Today's Schedule" : 'Schedule') + ' (' + fmtDayLabel(day) + ')';
            }
            var items = rows().filter(function (row) {
                return (row.getAttribute('data-due') || '') === day;
            }).sort(function (a, b) {
                return String(a.getAttribute('data-sort') || '').localeCompare(String(b.getAttribute('data-sort') || ''));
            });
            if (countEl) countEl.textContent = items.length + (items.length === 1 ? ' Task' : ' Tasks');
            if (!items.length) {
                list.innerHTML = '<div class="vis-sched-empty">No visits scheduled for this day.</div>';
                return;
            }
            list.innerHTML = items.map(function (row) {
                var type = row.getAttribute('data-type') === 'followup' ? 'followup' : 'visit';
                var ico = type === 'followup' ? 'bi-telephone-fill' : 'bi-geo-alt-fill';
                var href = row.getAttribute('data-href') || '';
                var tag = href ? 'a' : 'div';
                var hrefAttr = href ? ' href="' + esc(href) + '"' : '';
                return '<' + tag + ' class="vis-sched-item"' + hrefAttr + '>'
                    + '<span class="vis-sched-time"><i class="bi bi-clock"></i>' + esc(row.getAttribute('data-time') || '—') + '</span>'
                    + '<span class="vis-sched-who"><div class="n">' + esc(row.getAttribute('data-customer') || '—') + '</div>'
                    + '<div class="c">' + esc(row.getAttribute('data-case') || '—') + '</div></span>'
                    + '<span class="vis-sched-asg">' + esc(row.getAttribute('data-assignee') || '—') + '</span>'
                    + '<span class="vis-sched-type ' + type + '" title="' + (type === 'followup' ? 'Follow-up' : 'Customer Visit') + '"><i class="bi ' + ico + '"></i></span>'
                    + '</' + tag + '>';
            }).join('');
        }

        function applyFilter() {
            var qEl = document.getElementById('visLiveSearch');
            var q = (qEl && qEl.value ? qEl.value : '').trim().toLowerCase();
            var visible = 0;
            rows().forEach(function (row) {
                var bucket = row.getAttribute('data-bucket') || '';
                var due = row.getAttribute('data-due') || '';
                var hay = row.getAttribute('data-search') || '';
                var okTab = state.tab === 'all' || bucket === state.tab;
                var okSearch = !q || hay.indexOf(q) !== -1;
                // Calendar day only drives Today's Schedule — register stays tab/search filtered.
                var show = okTab && okSearch;
                row.style.display = show ? '' : 'none';
                if (show) {
                    visible += 1;
                    var num = row.querySelector('.vis-row-num');
                    if (num) num.textContent = String(visible);
                }
            });
            var empty = document.getElementById('visEmptyRow');
            if (empty) empty.classList.toggle('d-none', visible > 0);
            var label = document.getElementById('visShowingLabel');
            if (label) label.textContent = 'Showing ' + visible + ' task(s)';

            document.querySelectorAll('[data-vis-tab]').forEach(function (el) {
                var t = el.getAttribute('data-vis-tab');
                el.classList.toggle('active', t === state.tab);
                el.classList.toggle('is-active', t === state.tab);
            });

            syncUrl();
            renderCalendar();
            renderSchedule();
        }

        function setTab(tab) {
            state.tab = tab || 'all';
            applyFilter();
        }

        function setDay(day) {
            if (!day) return;
            state.day = day;
            if (day.length >= 7) state.cal = day.slice(0, 7);
            syncUrl();
            renderCalendar();
            renderSchedule();
        }

        function shiftMonth(delta) {
            var parts = (state.cal || '1970-01').split('-');
            var y = parseInt(parts[0], 10);
            var m = parseInt(parts[1], 10) - 1 + delta;
            while (m < 0) { m += 12; y -= 1; }
            while (m > 11) { m -= 12; y += 1; }
            state.cal = y + '-' + String(m + 1).padStart(2, '0');
            renderCalendar();
            syncUrl();
        }

        function dayCounts() {
            var map = {};
            rows().forEach(function (row) {
                var due = row.getAttribute('data-due');
                if (!due) return;
                map[due] = (map[due] || 0) + 1;
            });
            return map;
        }

        function renderCalendar() {
            var grid = document.getElementById('visCalGrid');
            var label = document.getElementById('visCalLabel');
            if (!grid || !state.cal) return;
            var parts = state.cal.split('-');
            var y = parseInt(parts[0], 10);
            var m = parseInt(parts[1], 10) - 1;
            if (label) label.innerHTML = monthNames[m] + ' ' + y + ' <i class="bi bi-caret-down-fill"></i>';
            var first = new Date(y, m, 1);
            var pad = first.getDay();
            var daysInMonth = new Date(y, m + 1, 0).getDate();
            var counts = dayCounts();
            var today = todayKey();
            var html = ['Su','Mo','Tu','We','Th','Fr','Sa'].map(function (d) {
                return '<div class="cal-dow">' + d + '</div>';
            }).join('');
            for (var i = 0; i < pad; i++) html += '<div class="cal-day mute"></div>';
            for (var day = 1; day <= daysInMonth; day++) {
                var key = y + '-' + String(m + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                var count = counts[key] || 0;
                var cls = 'cal-day'
                    + (count ? ' has' : '')
                    + (key === today ? ' today' : '')
                    + (state.day === key ? ' active' : '');
                html += '<button type="button" class="' + cls + '" data-cal-day="' + key + '"><span>' + day + '</span>'
                    + (count ? '<span class="dot" title="' + count + ' task(s)"></span>' : '')
                    + '</button>';
            }
            grid.innerHTML = html;
        }

        board.querySelectorAll('[data-vis-tab]').forEach(function (el) {
            el.addEventListener('click', function () { setTab(el.getAttribute('data-vis-tab')); });
        });
        var prev = document.getElementById('visCalPrev');
        var next = document.getElementById('visCalNext');
        if (prev) prev.addEventListener('click', function () { shiftMonth(-1); });
        if (next) next.addEventListener('click', function () { shiftMonth(1); });
        var grid = document.getElementById('visCalGrid');
        if (grid) {
            grid.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-cal-day]');
                if (!btn) return;
                setDay(btn.getAttribute('data-cal-day'));
            });
        }

        var search = document.getElementById('visLiveSearch');
        if (search) {
            var t;
            search.addEventListener('input', function () {
                clearTimeout(t);
                t = setTimeout(applyFilter, 150);
            });
        }

        applyFilter();
    }

    function reinitSelect2(selectEl) {
        if (!selectEl || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) return;
        var $el = window.jQuery(selectEl);
        if ($el.hasClass('select2-hidden-accessible')) {
            try { $el.select2('destroy'); } catch (e) {}
        }
        if (window.IsarvaSelect2 && typeof window.IsarvaSelect2.initOne === 'function') {
            window.IsarvaSelect2.initOne(selectEl);
        } else {
            $el.select2({ width: '100%', dropdownParent: $el.closest('.modal').length ? $el.closest('.modal') : window.jQuery(document.body) });
        }
    }

    function filterAssigneesByBranch(selectEl, branchId, preferUserId) {
        if (!selectEl) return 0;
        var bid = branchId ? String(branchId) : '';
        var visible = 0;
        Array.prototype.forEach.call(selectEl.options, function (opt, idx) {
            if (idx === 0 && !opt.value) {
                opt.hidden = false;
                opt.disabled = false;
                return;
            }
            var ob = opt.getAttribute('data-branch-id') || '';
            var show = !bid || ob === bid;
            opt.hidden = !show;
            opt.disabled = !show;
            if (show) visible += 1;
        });
        if (preferUserId) {
            var pref = Array.prototype.find.call(selectEl.options, function (o) {
                return String(o.value) === String(preferUserId) && !o.disabled && !o.hidden;
            });
            selectEl.value = pref ? String(preferUserId) : '';
        } else if (selectEl.selectedOptions[0] && (selectEl.selectedOptions[0].disabled || selectEl.selectedOptions[0].hidden)) {
            selectEl.value = '';
        }
        reinitSelect2(selectEl);
        return visible;
    }

    function setFlatpickrValue(el, value) {
        if (!el) return;
        if (el._flatpickr) {
            if (value) el._flatpickr.setDate(value, true);
            else el._flatpickr.clear();
            return;
        }
        el.value = value || '';
        if (window.IsarvaDatepicker) window.IsarvaDatepicker.initOne(el);
        if (el._flatpickr && value) el._flatpickr.setDate(value, true);
    }

    function resolveModalTrigger(e, selector) {
        var t = e && e.relatedTarget ? e.relatedTarget : null;
        if (!t && window.__visLastTrigger) t = window.__visLastTrigger;
        if (t && t.closest) {
            var hit = t.closest(selector);
            if (hit) t = hit;
        }
        return t && t.getAttribute ? t : null;
    }

    document.addEventListener('click', function (e) {
        var t = e.target && e.target.closest
            ? e.target.closest('[data-bs-target="#visEditModal"], [data-bs-target="#visCompleteModal"]')
            : null;
        if (t) window.__visLastTrigger = t;
    }, true);

    var editModal = document.getElementById('visEditModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (e) {
            var btn = resolveModalTrigger(e, '[data-bs-target="#visEditModal"]');
            if (!btn || !btn.getAttribute('data-action')) return;
            var form = document.getElementById('visEditForm');
            form.action = btn.getAttribute('data-action');
            var caseLabel = document.getElementById('vis_edit_case_label');
            var branchLabel = document.getElementById('vis_edit_branch_label');
            var hint = document.getElementById('vis_edit_assigned_hint');
            var branchName = btn.getAttribute('data-branch') || '';
            var branchId = btn.getAttribute('data-branch-id') || '';
            if (caseLabel) caseLabel.value = btn.getAttribute('data-case-label') || '';
            if (branchLabel) branchLabel.value = branchName || 'Not set on case';
            var typeEl = document.getElementById('vis_edit_task_type');
            if (typeEl) {
                typeEl.value = btn.getAttribute('data-type') || 'visit';
                reinitSelect2(typeEl);
            }
            document.getElementById('vis_edit_notes').value = btn.getAttribute('data-notes') || '';
            document.getElementById('vis_edit_location').value = btn.getAttribute('data-location') || '';
            var count = filterAssigneesByBranch(
                document.getElementById('vis_edit_assigned_to'),
                branchId,
                btn.getAttribute('data-assigned') || ''
            );
            if (hint) {
                if (!branchId) hint.textContent = 'Case has no branch — showing all sales staff in your scope.';
                else if (!count) hint.textContent = 'No sales staff found for this branch.';
                else hint.textContent = 'Showing staff from ' + (branchName || 'this branch') + '.';
            }
            setFlatpickrValue(document.getElementById('vis_edit_scheduled_date'), btn.getAttribute('data-date') || '');
            setFlatpickrValue(document.getElementById('vis_edit_scheduled_time'), btn.getAttribute('data-time') || '');
        });
    }
    var completeModal = document.getElementById('visCompleteModal');
    if (completeModal) {
        completeModal.addEventListener('show.bs.modal', function (e) {
            var btn = resolveModalTrigger(e, '[data-bs-target="#visCompleteModal"]');
            if (!btn || !btn.getAttribute('data-action')) return;
            document.getElementById('visCompleteForm').action = btn.getAttribute('data-action');
            document.getElementById('visCompleteTitle').textContent = btn.getAttribute('data-title') || 'Complete task';
        });
    }
    var caseSel = document.getElementById('vis_case_id');
    var branchEl = document.getElementById('vis_branch_label');
    var assigneeSel = document.getElementById('vis_assigned_to');
    var assigneeHint = document.getElementById('vis_assigned_hint');
    function syncAssigneeForCase() {
        if (!caseSel) return;
        var opt = caseSel.options[caseSel.selectedIndex];
        if (opt && !opt.value) opt = null;
        var branchName = opt ? (opt.getAttribute('data-branch') || '') : '';
        var branchId = opt ? (opt.getAttribute('data-branch-id') || '') : '';
        var ownerId = opt ? (opt.getAttribute('data-owner') || '') : '';
        if (branchEl) branchEl.value = branchName || (caseSel.value ? 'Not set on case' : '');
        var count = filterAssigneesByBranch(assigneeSel, caseSel.value ? branchId : '', ownerId);
        if (assigneeHint) {
            if (!caseSel.value) {
                assigneeHint.textContent = 'Select a loan case to list that branch’s staff.';
            } else if (!branchId) {
                assigneeHint.textContent = 'Case has no branch — showing all sales staff in your scope.';
            } else if (!count) {
                assigneeHint.textContent = 'No sales staff found for this branch.';
            } else {
                assigneeHint.textContent = 'Showing staff from ' + (branchName || 'this branch') + '.';
            }
        }
    }
    if (caseSel) {
        caseSel.addEventListener('change', syncAssigneeForCase);
        if (window.jQuery) {
            window.jQuery(caseSel).on('change.select2 select2:select select2:clear', syncAssigneeForCase);
        }
        var scheduleModal = document.getElementById('visScheduleModal');
        if (scheduleModal) {
            scheduleModal.addEventListener('shown.bs.modal', syncAssigneeForCase);
        }
        syncAssigneeForCase();
    }
    try {
        var prefill = sessionStorage.getItem('visPrefillCase');
        if (prefill && caseSel) {
            caseSel.value = prefill;
            caseSel.dispatchEvent(new Event('change'));
            sessionStorage.removeItem('visPrefillCase');
            var modalEl = document.getElementById('visScheduleModal');
            if (modalEl && window.bootstrap) {
                window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        }
    } catch (e) {}
});
</script>
@endpush
