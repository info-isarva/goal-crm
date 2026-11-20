@extends('layouts.app')

@php
    $f = $dash['filters'] ?? [];
    $opts = $dash['filter_options'] ?? [];
    $reports = $dash['mis_register'] ?? [];
    $counts = [
        'all' => count($reports),
        'operational' => collect($reports)->where('group', 'operational')->count(),
        'financial' => collect($reports)->where('group', 'financial')->count(),
        'performance' => collect($reports)->where('group', 'performance')->count(),
    ];
    $dateFromLabel = !empty($f['date_from']) ? \Carbon\Carbon::parse($f['date_from'])->format('d M Y') : '';
    $dateToLabel = !empty($f['date_to']) ? \Carbon\Carbon::parse($f['date_to'])->format('d M Y') : '';
    $dateRangeLabel = trim($dateFromLabel.($dateFromLabel && $dateToLabel ? ' - ' : '').$dateToLabel);
@endphp

@push('styles')
@include('goal.partials.pd_hub_styles')
<style>
.mis-page{color:#1e293b}
.mis-page.container-fluid{padding:.85rem 1rem 1.1rem !important}
.mis-page .mis-hero{
    display:flex;flex-wrap:nowrap;align-items:center;justify-content:space-between;
    gap:.75rem 1rem;margin-bottom:.65rem;width:100%
}
.mis-page .mis-hero-actions{flex:0 0 auto;margin-left:auto;padding-top:0}
.mis-page .mis-hero-left{display:flex;align-items:flex-start;gap:12px;min-width:0}
.mis-page .mis-hero-ico{
    width:42px;height:42px;border-radius:10px;flex:0 0 auto;margin-top:1px;
    display:inline-flex;align-items:center;justify-content:center;
    background:var(--theme-primary);color:#fff;font-size:1.2rem;
    box-shadow:0 6px 14px var(--theme-primary-ring)
}
.mis-page .mis-title{margin:0;font-size:1.4rem;font-weight:800;color:#0f172a;letter-spacing:-.02em;line-height:1.2}
.mis-page .mis-sub{margin:.25rem 0 0;color:#64748b;font-size:.84rem;line-height:1.35;max-width:40rem}
.mis-page .mis-crumbs{
    display:flex;flex-wrap:wrap;align-items:center;gap:5px;margin:.4rem 0 0;
    padding:0;list-style:none;font-size:.75rem;color:#64748b
}
.mis-page .mis-crumbs a{color:var(--theme-primary);text-decoration:none;font-weight:600}
.mis-page .mis-crumbs a:hover{text-decoration:underline}
.mis-page .mis-crumbs .sep{opacity:.45}
.mis-page .mis-hero-actions{display:flex;flex-wrap:nowrap;gap:8px;align-items:center}
.mis-page .mis-link-btn{
    height:36px;padding:0 12px;border-radius:8px;font-size:.8rem;font-weight:700;
    display:inline-flex;align-items:center;gap:6px;text-decoration:none;background:#fff;
    border:1.5px solid var(--theme-primary);color:var(--theme-primary)
}
.mis-page .mis-link-btn:hover{background:rgba(0,0,0,.03);color:var(--theme-primary)}
.mis-page .mis-link-btn.primary{
    border:0;background:var(--theme-primary);color:#fff;box-shadow:0 4px 12px var(--theme-primary-ring)
}
.mis-page .mis-link-btn.primary:hover{color:#fff;filter:brightness(1.05)}

.mis-page .mis-filters{
    display:flex;flex-wrap:nowrap;align-items:flex-end;gap:8px;
    margin-bottom:.55rem;padding:10px 12px;width:100%;
    background:#fff;border:1px solid #e2e8f0;border-radius:12px;
    box-shadow:0 2px 8px rgba(148,163,184,.06);box-sizing:border-box
}
.mis-page .mis-filters > div{min-width:0;display:flex;flex-direction:column;justify-content:flex-end}
.mis-page .mis-filters > .mis-f-fy{flex:0 1 118px}
.mis-page .mis-filters > .mis-f-date{flex:1 1 200px;max-width:240px}
.mis-page .mis-filters > .mis-f-region,
.mis-page .mis-filters > .mis-f-cluster,
.mis-page .mis-filters > .mis-f-branch,
.mis-page .mis-filters > .mis-f-product{flex:1 1 0;min-width:108px}
.mis-page .mis-filters label{
    display:block;font-size:.65rem;font-weight:700;color:#64748b;margin:0 0 4px;
    text-transform:uppercase;letter-spacing:.03em;line-height:1;height:12px
}
.mis-page .mis-filters .form-control,.mis-page .mis-filters .form-select,
.mis-page .mis-filters .flatpickr-input,.mis-page .mis-filters .form-control.input{
    width:100%;min-height:36px !important;height:36px !important;border-radius:8px;border:1px solid #d1d5db;
    font-size:.8rem;background:#fff;color:#0f172a;box-sizing:border-box;padding:.35rem .65rem;line-height:1.2
}
.mis-page .mis-date-wrap{position:relative;width:100%}
.mis-page .mis-date-wrap .bi-calendar3{
    position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.85rem;pointer-events:none;z-index:2
}
.mis-page .mis-date-wrap .form-control,
.mis-page .mis-date-wrap .flatpickr-input,
.mis-page .mis-date-wrap .form-control.input{padding-left:32px !important}
.mis-page .mis-date-wrap .flatpickr-wrapper{width:100%;display:block}
.mis-page .mis-filter-actions{
    flex:0 0 auto;display:flex;flex-direction:column;justify-content:flex-end;margin-left:2px
}
.mis-page .mis-filter-actions .mis-filter-actions-row{
    display:flex;gap:6px;align-items:center;height:36px;white-space:nowrap
}
.mis-page .mis-btn-apply{
    height:36px;padding:0 14px;border-radius:8px;border:0;background:var(--theme-primary);color:#fff;
    font-size:.8rem;font-weight:700;display:inline-flex;align-items:center;justify-content:center;gap:6px;
    box-shadow:0 4px 10px var(--theme-primary-ring)
}
.mis-page .mis-btn-apply:hover{filter:brightness(1.05);color:#fff}
.mis-page .mis-btn-reset{
    height:36px;padding:0 12px;border-radius:8px;border:1.5px solid var(--theme-primary);background:#fff;
    color:var(--theme-primary);font-size:.8rem;font-weight:700;display:inline-flex;align-items:center;justify-content:center;gap:6px;text-decoration:none
}
.mis-page .mis-btn-reset:hover{background:rgba(0,0,0,.03);color:var(--theme-primary)}

.mis-page .mis-cat-row{
    display:flex;flex-wrap:nowrap;align-items:center;justify-content:space-between;
    gap:14px;margin:0 0 .65rem;width:100%;min-height:44px
}
.mis-page .mis-cat-label{
    margin:0;font-size:.92rem;font-weight:800;color:#0f172a;letter-spacing:-.01em;white-space:nowrap;flex:0 0 auto
}
.mis-page .mis-tabs{
    display:inline-flex;flex-wrap:nowrap;align-items:stretch;gap:0;margin:0 0 0 auto;padding:3px;
    background:#fff;border:1px solid #e2e8f0;border-radius:999px;
    box-shadow:0 1px 2px rgba(15,23,42,.03);overflow:hidden;flex:0 0 auto;max-width:100%
}
.mis-page .mis-tab{
    position:relative;display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:36px;
    padding:.4rem 1.1rem;border-radius:999px;border:0;background:transparent;
    color:#0f172a;font-size:.82rem;font-weight:700;cursor:pointer;line-height:1;white-space:nowrap
}
.mis-page .mis-tab + .mis-tab::before{
    content:'';position:absolute;left:0;top:22%;bottom:22%;width:1px;background:#e2e8f0;pointer-events:none
}
.mis-page .mis-tab.active::before,
.mis-page .mis-tab.active + .mis-tab::before{opacity:0}
.mis-page .mis-tab i{font-size:.95rem;opacity:.9}
.mis-page .mis-tab .n{
    font-weight:700;opacity:.85;font-variant-numeric:tabular-nums
}
.mis-page .mis-tab.active{
    background:var(--theme-primary);color:#fff;
    box-shadow:0 4px 12px var(--theme-primary-ring);z-index:1
}
.mis-page .mis-tab.active .n{opacity:1}
.mis-page .mis-tab:not(.active):hover{color:var(--theme-primary)}

.mis-page .mis-grid{
    display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px
}
.mis-page .mis-card{
    background:#fff;border:1px solid #e8edf5;border-radius:16px;padding:20px;
    box-shadow:0 4px 14px rgba(148,163,184,.10);display:flex;flex-direction:column;
    min-height:100%;transition:box-shadow .15s ease
}
.mis-page .mis-card:hover{box-shadow:0 8px 22px rgba(15,23,42,.08)}
.mis-page .mis-card-top{
    display:flex;align-items:flex-start;gap:12px;margin:0 0 18px
}
.mis-page .mis-card-ico{
    width:40px;height:40px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;
    font-size:1.1rem;flex:0 0 auto
}
.mis-page .mis-card-ico.t-green{background:#dcfce7;color:#16a34a}
.mis-page .mis-card-ico.t-blue{background:#dbeafe;color:#2563eb}
.mis-page .mis-card-ico.t-indigo{background:#e0e7ff;color:#4f46e5}
.mis-page .mis-card-ico.t-teal{background:#ccfbf1;color:#0d9488}
.mis-page .mis-card-ico.t-amber{background:#ffedd5;color:#ea580c}
.mis-page .mis-card-ico.t-rose{background:#ffe4e6;color:#e11d48}
.mis-page .mis-card-ico.t-purple{background:#f3e8ff;color:#9333ea}
.mis-page .mis-card-ico.t-cyan{background:#cffafe;color:#0891b2}
.mis-page .mis-card-ico.t-slate{background:#e2e8f0;color:#475569}
.mis-page .mis-card-ico.t-orange{background:#ffedd5;color:#c2410c}
.mis-page .mis-card-heading{flex:1 1 auto;min-width:0;padding-right:4px}
.mis-page .mis-card-title{
    margin:0 0 6px;font-size:1.02rem;font-weight:800;color:#0f172a;line-height:1.25;letter-spacing:-.01em
}
.mis-page .mis-card-desc{
    margin:0;color:#64748b;font-size:.82rem;line-height:1.5;font-weight:500
}
.mis-page .mis-info{
    width:22px;height:22px;border-radius:999px;border:0;background:transparent;color:#94a3b8;
    display:inline-flex;align-items:center;justify-content:center;cursor:help;flex:0 0 auto;
    padding:0;margin:2px 0 0;line-height:1;font-size:1rem
}
.mis-page .mis-info:hover{color:#64748b}
.mis-page .mis-card-actions{
    display:flex;flex-wrap:nowrap;align-items:center;justify-content:center;gap:8px;margin-top:auto
}
.mis-page .mis-act{
    height:30px;padding:0 11px;border-radius:7px;border:1.5px solid var(--theme-primary);
    background:#fff;color:var(--theme-primary);
    display:inline-flex;align-items:center;justify-content:center;gap:5px;
    font-size:.72rem;font-weight:700;text-decoration:none;cursor:pointer;line-height:1;
    flex:0 0 auto;width:auto;box-shadow:none
}
.mis-page .mis-act i{font-size:.82rem;color:inherit}
.mis-page .mis-act:hover{
    background:var(--theme-primary);border-color:var(--theme-primary);color:#fff
}
.mis-page .mis-act:focus{outline:none;box-shadow:0 0 0 3px var(--theme-primary-ring)}
.mis-page .mis-act.is-disabled{opacity:.45;pointer-events:none}
.mis-page .mis-dl-drop{position:relative}
.mis-page .mis-export-menu{
    min-width:210px;padding:.4rem 0;border-radius:12px;border:1px solid #e2e8f0;
    box-shadow:0 12px 28px rgba(15,23,42,.12);font-size:.82rem
}
.mis-page .mis-export-menu .dropdown-header{
    font-size:.66rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;padding:.35rem .9rem .1rem
}
.mis-page .mis-export-menu .dropdown-item{
    display:flex;align-items:center;gap:8px;padding:.55rem .9rem;font-weight:600;min-height:38px
}
.mis-page .mis-export-menu .dropdown-item i{font-size:1rem}
.mis-page .mis-empty{
    grid-column:1/-1;padding:2rem 1rem;text-align:center;color:#94a3b8;
    background:#fff;border:1px dashed #cbd5e1;border-radius:12px;font-weight:650
}

@media (max-width:1200px){
    .mis-page .mis-filters{flex-wrap:wrap}
    .mis-page .mis-filters > .mis-f-fy,
    .mis-page .mis-filters > .mis-f-date,
    .mis-page .mis-filters > .mis-f-region,
    .mis-page .mis-filters > .mis-f-cluster,
    .mis-page .mis-filters > .mis-f-branch,
    .mis-page .mis-filters > .mis-f-product{flex:1 1 calc(33.333% - 8px);max-width:none}
    .mis-page .mis-filter-actions{margin-left:auto}
}
@media (max-width:991.98px){
    .mis-page .mis-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
    .mis-page .mis-filters > .mis-f-fy,
    .mis-page .mis-filters > .mis-f-date,
    .mis-page .mis-filters > .mis-f-region,
    .mis-page .mis-filters > .mis-f-cluster,
    .mis-page .mis-filters > .mis-f-branch,
    .mis-page .mis-filters > .mis-f-product{flex:1 1 calc(50% - 8px)}
}
@media (max-width:767.98px){
    .mis-page.container-fluid{padding:.65rem .6rem 1rem !important}
    .mis-page .mis-title{font-size:1.2rem}
    .mis-page .mis-filters{flex-direction:column;align-items:stretch}
    .mis-page .mis-filters > .mis-f-fy,
    .mis-page .mis-filters > .mis-f-date,
    .mis-page .mis-filters > .mis-f-region,
    .mis-page .mis-filters > .mis-f-cluster,
    .mis-page .mis-filters > .mis-f-branch,
    .mis-page .mis-filters > .mis-f-product{flex:1 1 auto;max-width:none;width:100%}
    .mis-page .mis-filter-actions{width:100%;margin-left:0}
    .mis-page .mis-filter-actions .mis-filter-actions-row{display:grid;grid-template-columns:1.35fr 1fr;width:100%}
    .mis-page .mis-btn-apply,.mis-page .mis-btn-reset{width:100%}
    .mis-page .mis-grid{grid-template-columns:1fr}
    .mis-page .mis-hero{flex-wrap:wrap;align-items:flex-start}
    .mis-page .mis-hero-actions{width:100%;margin-left:0}
    .mis-page .mis-link-btn{flex:1 1 calc(50% - 4px);justify-content:center}
    .mis-page .mis-cat-row{flex-wrap:wrap;gap:8px}
    .mis-page .mis-tabs{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}
    .mis-page .mis-tab{flex:0 0 auto;padding:.4rem .85rem;font-size:.78rem}
}
</style>
@endpush

@section('content')
<div class="container-fluid pd-page mis-page">
    <div class="mis-hero">
        <div class="mis-hero-left">
            <span class="mis-hero-ico" aria-hidden="true"><i class="bi bi-file-earmark-bar-graph-fill"></i></span>
            <div>
                <h1 class="mis-title">MIS Reports</h1>
                <p class="mis-sub">Standard and management reports for leads, processing, disbursement, revenue and payout.</p>
                <ul class="mis-crumbs">
                    <li><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="sep">›</li>
                    <li><a href="{{ route('goal.analytics') }}">Analytics / MIS Reports</a></li>
                    <li class="sep">›</li>
                    <li>MIS Reports</li>
                </ul>
            </div>
        </div>
        <div class="mis-hero-actions">
            <a class="mis-link-btn primary" href="{{ route('goal.analytics', request()->except('section')) }}">
                <i class="bi bi-speedometer2"></i> Analytics Dashboard
            </a>
        </div>
    </div>

    @if(!empty($dash['load_error']))
        <div class="alert alert-warning">{{ $dash['load_error'] }}</div>
    @endif

    <form method="get" action="{{ route('goal.mis-reports') }}" class="mis-filters" id="misFiltersForm">
        <input type="hidden" name="date_from" id="misDateFrom" value="{{ $f['date_from'] ?? '' }}">
        <input type="hidden" name="date_to" id="misDateTo" value="{{ $f['date_to'] ?? '' }}">

        <div class="mis-f-fy">
            <label>Financial Year</label>
            <select name="fy" class="form-select">
                @foreach(($dash['fy_options'] ?? []) as $fy)
                    <option value="{{ $fy }}" @selected(($f['fy'] ?? '') === $fy)>{{ str_replace('-', ' - ', $fy) }}</option>
                @endforeach
            </select>
        </div>
        <div class="mis-f-date">
            <label>Date Range</label>
            <div class="mis-date-wrap">
                <i class="bi bi-calendar3"></i>
                <input type="text" id="misDateRange" class="form-control" placeholder="Select date range" value="{{ $dateRangeLabel }}" readonly>
            </div>
        </div>
        <div class="mis-f-region">
            <label>Region</label>
            <select name="region" class="form-select">
                <option value="">All Regions</option>
                @foreach(($opts['regions'] ?? []) as $u)
                    <option value="{{ $u->id }}" @selected((string)($f['region'] ?? '') === (string)$u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mis-f-cluster">
            <label>Cluster</label>
            <select name="cluster" class="form-select">
                <option value="">All Clusters</option>
                @foreach(($opts['clusters'] ?? []) as $u)
                    <option value="{{ $u->id }}" @selected((string)($f['cluster'] ?? '') === (string)$u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mis-f-branch">
            <label>Branch</label>
            <select name="branch" class="form-select">
                <option value="">All Branches</option>
                @foreach(($opts['branches'] ?? []) as $u)
                    <option value="{{ $u->id }}" @selected((string)($f['branch'] ?? '') === (string)$u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mis-f-product">
            <label>Product</label>
            <select name="product" class="form-select">
                <option value="">All Products</option>
                @foreach(($opts['products'] ?? []) as $p)
                    <option value="{{ $p }}" @selected(($f['product'] ?? '') === $p)>{{ $p }}</option>
                @endforeach
            </select>
        </div>
        <div class="mis-filter-actions">
            <label aria-hidden="true">&nbsp;</label>
            <div class="mis-filter-actions-row">
                <button type="submit" class="mis-btn-apply"><i class="bi bi-funnel-fill"></i> Apply</button>
                <a href="{{ route('goal.mis-reports') }}" class="mis-btn-reset"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
            </div>
        </div>
    </form>

    <div class="mis-cat-row">
        <h2 class="mis-cat-label">Report Categories</h2>
        <div class="mis-tabs" role="tablist" aria-label="Report Categories">
            <button type="button" class="mis-tab active" data-mis-group="all">
                <i class="bi bi-grid-fill"></i> All Reports <span class="n">({{ $counts['all'] }})</span>
            </button>
            <button type="button" class="mis-tab" data-mis-group="operational">
                <i class="bi bi-graph-up-arrow"></i> Operational <span class="n">({{ $counts['operational'] }})</span>
            </button>
            <button type="button" class="mis-tab" data-mis-group="financial">
                <i class="bi bi-stack"></i> Financial <span class="n">({{ $counts['financial'] }})</span>
            </button>
            <button type="button" class="mis-tab" data-mis-group="performance">
                <i class="bi bi-bullseye"></i> Performance <span class="n">({{ $counts['performance'] }})</span>
            </button>
        </div>
    </div>

    <div class="mis-grid" id="misReportGrid">
        @forelse($reports as $rep)
            <article class="mis-card" data-group="{{ $rep['group'] ?? 'operational' }}">
                <div class="mis-card-top">
                    <span class="mis-card-ico t-{{ $rep['tone'] ?? 'green' }}">
                        <i class="bi {{ $rep['icon'] ?? 'bi-file-earmark-text' }}"></i>
                    </span>
                    <div class="mis-card-heading">
                        <h3 class="mis-card-title">{{ $rep['name'] }}</h3>
                        <p class="mis-card-desc">{{ $rep['description'] ?? '' }}</p>
                    </div>
                    <button type="button" class="mis-info" title="{{ $rep['description'] ?? '' }}" aria-label="Report info">
                        <i class="bi bi-info-circle"></i>
                    </button>
                </div>
                <div class="mis-card-actions">
                    <a class="mis-act" href="{{ $rep['view'] ?? '#' }}">
                        <i class="bi bi-eye"></i> View
                    </a>
                    @php
                        $dlQs = array_merge(request()->only(['fy','date_from','date_to','region','cluster','branch','product']), ['report' => $rep['key'] ?? '']);
                    @endphp
                    <div class="dropdown mis-dl-drop">
                        <button type="button" class="mis-act dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-download"></i> Download
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end mis-export-menu">
                            <li><h6 class="dropdown-header">Download Report</h6></li>
                            <li>
                                <a class="dropdown-item" href="{{ !empty($rep['key']) ? route('goal.mis-reports.export', array_merge($dlQs, ['format' => 'pdf'])) : '#' }}">
                                    <i class="bi bi-file-earmark-pdf-fill text-danger"></i> PDF
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ !empty($rep['key']) ? route('goal.mis-reports.export', array_merge($dlQs, ['format' => 'xlsx'])) : '#' }}">
                                    <i class="bi bi-file-earmark-excel-fill text-success"></i> Excel
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ !empty($rep['key']) ? route('goal.mis-reports.export', array_merge($dlQs, ['format' => 'csv'])) : '#' }}">
                                    <i class="bi bi-filetype-csv text-primary"></i> CSV
                                </a>
                            </li>
                        </ul>
                    </div>
                    <a class="mis-act" href="{{ route('goal.analytics') }}#schedule"
                       onclick="sessionStorage.setItem('gaOpenSchedule','1');sessionStorage.setItem('gaScheduleType','{{ $rep['key'] ?? 'overview' }}')">
                        <i class="bi bi-calendar3"></i> Schedule
                    </a>
                </div>
            </article>
        @empty
            <div class="mis-empty">No reports available for your permissions.</div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('misFiltersForm');
    const rangeEl = document.getElementById('misDateRange');
    const fromEl = document.getElementById('misDateFrom');
    const toEl = document.getElementById('misDateTo');
    if (rangeEl && window.flatpickr) {
        const defaults = [];
        if (fromEl && fromEl.value) defaults.push(fromEl.value);
        if (toEl && toEl.value) defaults.push(toEl.value);
        flatpickr(rangeEl, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd M Y',
            defaultDate: defaults.length ? defaults : null,
            onChange: function (selectedDates, _str, instance) {
                if (!fromEl || !toEl) return;
                fromEl.value = selectedDates[0] ? instance.formatDate(selectedDates[0], 'Y-m-d') : '';
                toEl.value = selectedDates[1]
                    ? instance.formatDate(selectedDates[1], 'Y-m-d')
                    : (selectedDates[0] ? instance.formatDate(selectedDates[0], 'Y-m-d') : '');
            }
        });
    }

    const tabs = document.querySelectorAll('.mis-tab');
    const cards = document.querySelectorAll('#misReportGrid .mis-card');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            const group = tab.getAttribute('data-mis-group') || 'all';
            tabs.forEach(function (t) { t.classList.toggle('active', t === tab); });
            let visible = 0;
            cards.forEach(function (card) {
                const show = group === 'all' || card.getAttribute('data-group') === group;
                card.hidden = !show;
                if (show) visible++;
            });
            let empty = document.getElementById('misFilterEmpty');
            if (visible === 0) {
                if (!empty) {
                    empty = document.createElement('div');
                    empty.id = 'misFilterEmpty';
                    empty.className = 'mis-empty';
                    empty.textContent = 'No reports in this category.';
                    document.getElementById('misReportGrid').appendChild(empty);
                }
                empty.hidden = false;
            } else if (empty) {
                empty.hidden = true;
            }
        });
    });
})();
</script>
@endpush
