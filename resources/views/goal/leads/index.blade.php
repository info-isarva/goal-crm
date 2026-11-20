@extends('layouts.app')

@push('styles')
<style>
.ll-page{color:#1e293b}
.ll-page .page-hero{
    display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;
    gap:1rem;margin-bottom:1.2rem;
}
.ll-page .page-hero-left{display:flex;align-items:center;gap:14px;min-width:0}
.ll-page .page-hero-icon{
    width:52px;height:52px;border-radius:16px;flex-shrink:0;
    background:linear-gradient(145deg,var(--theme-primary) 0%,var(--theme-primary-hover) 55%,var(--theme-accent) 100%);
    color:#fff;display:inline-flex;align-items:center;justify-content:center;
    font-size:1.35rem;box-shadow:0 12px 24px var(--theme-primary-ring);
}
.ll-page .page-title{font-size:1.55rem;font-weight:800;color:#0f172a;margin:0;line-height:1.2;letter-spacing:-.02em}
.ll-page .page-sub{color:#64748b;font-size:.9rem;margin-top:4px;line-height:1.4}
.ll-page .btn-create{
    background:linear-gradient(90deg,var(--theme-primary),var(--theme-primary-hover));
    color:#fff;border:none;border-radius:12px;
    height:42px;padding:0 1.15rem;font-weight:700;font-size:.9rem;
    display:inline-flex;align-items:center;gap:.45rem;
    box-shadow:0 10px 20px var(--theme-primary-ring);transition:transform .15s,filter .15s;
}
.ll-page .btn-create:hover{filter:brightness(1.05);color:#fff;transform:translateY(-1px)}

.ll-page .filter-card,
.ll-page .table-card{
    background:#fff;border:1px solid #e8edf5;border-radius:18px;
    box-shadow:0 10px 28px rgba(148,163,184,.14);
}
.ll-page .filter-card{padding:1.05rem 1.2rem;margin-bottom:1.15rem}
.ll-page .filter-grid{
    display:grid;
    grid-template-columns:minmax(200px,1.6fr) repeat(3,minmax(140px,1fr)) auto;
    gap:12px;align-items:end;
}
.ll-page .filter-grid label{
    display:block;font-size:.72rem;font-weight:700;color:#64748b;
    margin:0 0 6px;text-transform:uppercase;letter-spacing:.04em;
}
.ll-page .filter-grid .form-control,
.ll-page .filter-grid .form-select{
    min-height:38px;height:38px;border-radius:10px;border:1px solid #d1d5db;font-size:.9rem;background:#fff;
}
.ll-page .filter-grid .form-control:focus,
.ll-page .filter-grid .form-select:focus{
    border-color:var(--theme-300);box-shadow:0 0 0 3px var(--theme-primary-ring);
}
.ll-page .filter-grid .select2-container{width:100%!important}
.ll-page .filter-grid .select2-container--default .select2-selection--single{
    height:38px!important;min-height:38px!important;border:1px solid #d1d5db!important;
    border-radius:10px!important;background:#fff!important;
}
.ll-page .filter-grid .select2-container--default .select2-selection--single .select2-selection__rendered{
    line-height:36px!important;padding-left:.75rem!important;font-size:.9rem!important;color:#0f172a!important;
}
.ll-page .filter-card,.ll-page .filter-grid{overflow:visible!important;position:relative;z-index:5}
.ll-page .kpi-row{position:relative;z-index:1}
.ll-page .filter-actions{display:flex;align-items:end;gap:8px}
.ll-page .btn-apply{
    height:38px;border-radius:10px;border:none;background:var(--theme-primary);color:#fff;
    padding:0 16px;font-weight:700;font-size:.85rem;
    display:inline-flex;align-items:center;gap:6px;box-shadow:0 6px 14px var(--theme-primary-ring);
}
.ll-page .btn-apply:hover{background:var(--theme-primary-hover);color:#fff}
.ll-page .btn-reset{
    height:38px;border-radius:10px;border:1px solid #cbd5e1;background:#fff;color:#475569;
    padding:0 14px;font-weight:600;font-size:.85rem;text-decoration:none;
    display:inline-flex;align-items:center;gap:6px;
}
.ll-page .btn-reset:hover{background:#f8fafc;color:#0f172a}

/* Loan Leads compact KPI strip */
.ll-page .kpi-row{margin-bottom:.85rem}
.ll-page .stat-tile{
    --tile-ink:var(--theme-primary-hover);--tile-soft:var(--theme-100);--tile-fill:#fff;--tile-ring:var(--theme-primary-ring);
    position:relative;overflow:hidden;height:100%;min-height:0;
    border:1px solid var(--theme-card-border, #E2E8F0);border-radius:12px;padding:.7rem .85rem;
    background:var(--tile-fill);
    box-shadow:0 1px 2px rgba(15,23,42,.04),0 6px 14px rgba(148,163,184,.10);
    display:flex;flex-direction:row;align-items:center;gap:.75rem;
    transition:border-color .15s ease,box-shadow .15s ease;
}
.ll-page .stat-tile:hover{border-color:var(--tile-soft);box-shadow:0 4px 14px var(--tile-ring)}
.ll-page .stat-tile.tone-blue{--tile-ink:var(--theme-primary-hover);--tile-soft:var(--theme-200);--tile-fill:#fff;--tile-ring:var(--theme-primary-ring);border-left:3px solid var(--theme-primary)}
.ll-page .stat-tile.tone-green{--tile-ink:#15803d;--tile-soft:#bbf7d0;--tile-fill:#fff;--tile-ring:rgba(22,163,74,.12);border-left:3px solid #16a34a}
.ll-page .stat-tile.tone-orange{--tile-ink:#c2410c;--tile-soft:#fdba74;--tile-fill:#fff;--tile-ring:rgba(234,88,12,.12);border-left:3px solid #ea580c}
.ll-page .stat-tile.tone-purple{--tile-ink:#6d28d9;--tile-soft:#c4b5fd;--tile-fill:#fff;--tile-ring:rgba(124,58,237,.12);border-left:3px solid #7c3aed}
.ll-page .stat-tile::before,
.ll-page .stat-tile::after{content:none}
.ll-page .stat-icon{
    width:34px;height:34px;border-radius:10px;flex-shrink:0;
    display:inline-flex;align-items:center;justify-content:center;
    background:color-mix(in srgb, var(--tile-soft) 55%, #fff);color:var(--tile-ink);font-size:.95rem;
}
.ll-page .stat-top{display:contents}
.ll-page .stat-body{display:contents}
.ll-page .stat-meta{min-width:0;flex:1 1 auto}
.ll-page .stat-label{margin:0;font-size:.72rem;font-weight:700;color:#64748b;letter-spacing:.02em;text-transform:uppercase}
.ll-page .stat-chip{
    display:block;margin-top:2px;padding:0;border-radius:0;background:transparent;
    color:#94a3b8;font-size:.68rem;font-weight:500;line-height:1.25;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}
.ll-page .stat-value{margin:0 0 0 auto;font-size:1.35rem;font-weight:800;color:#0f172a;line-height:1;letter-spacing:-.02em;flex-shrink:0}

.ll-page .table-card-head{
    display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;
    gap:.65rem;padding:.85rem 1.15rem;border-bottom:1px solid #eef2f7;
    background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%);
}
.ll-page .table-head-left{display:flex;align-items:flex-start;gap:12px;min-width:0}
.ll-page .table-card-head h5{margin:0;font-size:1.08rem;font-weight:800;color:#0f172a}
.ll-page .section-icon{
    width:42px;height:42px;border-radius:12px;flex-shrink:0;
    background:linear-gradient(145deg,var(--theme-100),var(--theme-200));color:var(--theme-primary-hover);
    display:inline-flex;align-items:center;justify-content:center;font-size:1.1rem;
}
.ll-page .head-sub{margin:4px 0 0;font-size:.82rem;color:#64748b}
.ll-page .table-card-body{padding:0}
.ll-page .leads-table-wrap{
    border:0;border-radius:0;overflow-x:auto;
    -webkit-overflow-scrolling:touch;background:#fff;
}
.ll-page .leads-table-wrap table{min-width:980px;width:100%;margin:0}
.ll-page table thead th{
    font-size:.7rem;text-transform:uppercase;letter-spacing:.04em;
    color:#64748b;font-weight:700;background:#f1f5f9;
    border-bottom:1px solid #e2e8f0;white-space:nowrap;padding:.8rem .9rem;
}
.ll-page table tbody td{
    vertical-align:middle;font-size:.875rem;color:#334155;
    border-color:#eef2f7;padding:.9rem .9rem;white-space:nowrap;
}
.ll-page table tbody tr{transition:background .15s}
.ll-page table tbody tr:hover{background:#f8fbff}
.ll-page .ref-cell{font-weight:800;color:#0f172a;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.82rem}
.ll-page .cust-name{font-weight:700;color:#0f172a;line-height:1.25}
.ll-page .cust-mobile{font-size:.8rem;color:#64748b;margin-top:2px}
.ll-page .type-pill,
.ll-page .source-pill,
.ll-page .status-pill{
    display:inline-flex;align-items:center;justify-content:center;
    padding:.3rem .75rem;border-radius:999px;font-size:.74rem;font-weight:700;
}
.ll-page .type-pill.immediate{background:var(--theme-100);color:var(--theme-primary-hover)}
.ll-page .type-pill.future{background:#ffedd5;color:#c2410c}
.ll-page .source-pill.channel{background:#ede9fe;color:#6d28d9}
.ll-page .source-pill.vrm{background:#dcfce7;color:#15803d}
.ll-page .source-pill.manual,
.ll-page .source-pill.other{background:#f1f5f9;color:#475569}
.ll-page .status-pill{background:#f1f5f9;color:#334155;max-width:200px;overflow:hidden;text-overflow:ellipsis}
.ll-page .status-pill.pending{background:#fef3c7;color:#b45309}
.ll-page .status-pill.future{background:#ede9fe;color:#6d28d9}
.ll-page .status-pill.closed{background:#fee2e2;color:#b91c1c}
.ll-page .status-pill.active{background:#dcfce7;color:#15803d}
.ll-page .branch-cell{color:#475569;font-weight:600}
.ll-page .assignee-cell{color:#64748b}

.ll-page .action-btn{
    width:34px;height:34px;border-radius:10px;border:1.5px solid var(--theme-200);
    background:var(--theme-50);color:var(--theme-primary);
    display:inline-flex;align-items:center;justify-content:center;
    text-decoration:none;transition:.15s ease;
}
.ll-page .action-btn:hover{background:var(--theme-100);color:var(--theme-primary-hover);border-color:var(--theme-300)}
.ll-page .action-btn.open{border-color:var(--theme-200);background:var(--theme-50);color:var(--theme-primary)}

.ll-page .table-footer-bar{
    display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;
    gap:12px 16px;margin:0;padding:.75rem 1.15rem;
    border-top:1px solid #eef2f7;color:#64748b;font-size:.84rem;
}
.ll-page .foot-showing{font-weight:600}
.ll-page .foot-right{display:flex;flex-wrap:wrap;align-items:center;gap:12px 16px;margin-left:auto}
.ll-page .per-page-wrap{display:inline-flex;align-items:center;gap:10px;margin:0;font-weight:500}
.ll-page .foot-pager .pagination{margin:0;gap:6px}
.ll-page .foot-pager .page-link{
    border:0!important;border-radius:8px!important;min-width:34px;height:34px;
    display:inline-flex!important;align-items:center;justify-content:center;
    color:#475569!important;background:transparent!important;font-weight:600;
}
.ll-page .foot-pager .page-item.active .page-link{
    background:var(--theme-primary)!important;color:#fff!important;
    box-shadow:0 4px 10px var(--theme-primary-ring)!important;
}
.ll-page .empty-state{padding:2.5rem 1rem;text-align:center;color:#94a3b8}
.ll-page .empty-state .illus{
    width:56px;height:56px;border-radius:16px;margin:0 auto .75rem;
    background:#f1f5f9;color:#94a3b8;display:inline-flex;align-items:center;justify-content:center;font-size:1.4rem;
}

/* Create modal */
#createLeadModal .modal-content {
    border: none; border-radius: 16px; overflow: hidden;
    box-shadow: 0 24px 48px rgba(15, 23, 42, 0.18);
}
#createLeadModal .modal-header {
    border-bottom: 1px solid #e8edf5; padding: 18px 24px 16px; align-items: flex-start;
}
#createLeadModal .modal-title-wrap { display: flex; align-items: flex-start; gap: 12px; }
#createLeadModal .modal-icon {
    width: 44px; height: 44px; border-radius: 12px; background: var(--theme-100); color: var(--theme-primary);
    display: inline-flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;
}
#createLeadModal .modal-title { font-size: 1.2rem; font-weight: 800; color: #0f172a; margin: 0; }
#createLeadModal .modal-sub { font-size: 0.86rem; color: #64748b; margin-top: 3px; }
#createLeadModal .modal-body { padding: 18px 24px 12px; }
#createLeadModal .modal-footer {
    border-top: 1px solid #e8edf5; padding: 14px 24px;
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
}
#createLeadModal .section-bar {
    display: flex; align-items: center; gap: 8px;
    background: var(--theme-50); color: var(--theme-primary-hover); border-radius: 8px;
    padding: 10px 14px; margin: 0 0 14px; font-weight: 700; font-size: 0.9rem;
}
#createLeadModal .form-label { font-size: 0.8rem; font-weight: 600; color: #334155; }
#createLeadModal .form-control { border-radius: 8px; border-color: #d1d5db; }
#createLeadModal .btn-save {
    background: var(--theme-primary); color: #fff; border: none; border-radius: 8px;
    height: 40px; padding: 0 16px; font-weight: 700;
    display: inline-flex; align-items: center; gap: 6px;
}
#createLeadModal .btn-save:hover { background: var(--theme-primary-hover); color: #fff; }
#createLeadModal .btn-light-outline {
    border: 1px solid var(--theme-300); background: #fff; color: var(--theme-primary); border-radius: 8px;
    height: 40px; padding: 0 14px; font-weight: 600;
}

@media (max-width: 1199.98px) {
    .ll-page .filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .ll-page .filter-actions { grid-column: 1 / -1; }
}
@media (max-width: 767.98px) {
    .ll-page.container-fluid { padding: 1rem 0.85rem !important; }
    .ll-page .page-hero { flex-direction: column; align-items: stretch; }
    .ll-page .btn-create { width: 100%; justify-content: center; }
    .ll-page .filter-grid { grid-template-columns: 1fr; }
    .ll-page .filter-actions { display: grid; grid-template-columns: 1fr 1fr; width: 100%; }
    .ll-page .btn-apply, .ll-page .btn-reset { width: 100%; justify-content: center; height: 38px; }
    .ll-page .stat-tile { padding: 0.65rem 0.75rem; }
    .ll-page .stat-value { font-size: 1.2rem; }
    .ll-page .stat-icon { width: 30px; height: 30px; font-size: .88rem; }
    .ll-page .table-card-head { padding: 0.85rem 0.95rem; }
    .ll-page .table-card-body { padding: 0; }

    .ll-page .leads-table-wrap { border: 0; background: transparent; overflow: visible; }
    .ll-page .leads-table-wrap table { min-width: 0 !important; display: block; width: 100%; }
    .ll-page .leads-table-wrap thead { display: none; }
    .ll-page .leads-table-wrap tbody { display: block; }
    .ll-page .leads-table-wrap tr {
        display: block; margin: 0 0 0.85rem; padding: 0.75rem 0.85rem;
        border: 1px solid #e8edf5; border-radius: 14px; background: #fff;
        box-shadow: 0 6px 16px rgba(148, 163, 184, 0.1);
    }
    .ll-page .leads-table-wrap td {
        display: flex !important; align-items: center; justify-content: space-between;
        gap: 0.75rem; width: 100%; padding: 0.45rem 0 !important;
        border: 0 !important; border-bottom: 1px dashed #eef2f7 !important;
        white-space: normal !important; text-align: right;
    }
    .ll-page .leads-table-wrap td:last-child { border-bottom: 0 !important; padding-top: 0.7rem !important; }
    .ll-page .leads-table-wrap td::before {
        content: attr(data-label); flex: 0 0 auto;
        font-size: 0.72rem; font-weight: 700; letter-spacing: .03em;
        text-transform: uppercase; color: #64748b; text-align: left;
    }
    .ll-page .leads-table-wrap td.col-actions { display: block !important; text-align: left; }
    .ll-page .leads-table-wrap td.col-actions::before { display: block; margin-bottom: 0.5rem; }
    .ll-page .leads-table-wrap td.col-empty { display: block !important; text-align: center; border: 0 !important; }
    .ll-page .leads-table-wrap td.col-empty::before { display: none; }
    .ll-page .table-footer-bar { flex-direction: column; align-items: stretch; }
    .ll-page .foot-right { margin-left: 0; width: 100%; justify-content: space-between; }
    #createLeadModal .modal-footer {
        display: grid !important;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    #createLeadModal .modal-footer > * {
        width: 100%;
        min-height: 48px;
        justify-content: center;
        margin: 0;
    }
}
</style>
@endpush

@section('content')
@php
    $statusClass = function ($status) {
        $s = strtolower((string) $status);
        if (str_contains($s, 'pending') || str_contains($s, 'follow')) return 'pending';
        if (str_contains($s, 'future')) return 'future';
        if (str_contains($s, 'closed') || str_contains($s, 'reject') || str_contains($s, 'lost')) return 'closed';
        if (str_contains($s, 'document') || str_contains($s, 'allocat') || str_contains($s, 'progress')) return 'active';
        return '';
    };
    $sourceClass = function ($source) {
        $s = strtolower((string) $source);
        if ($s === 'channel') return 'channel';
        if ($s === 'vrm') return 'vrm';
        return 'manual';
    };
    $defaultSource = request()->boolean('channel') ? 'channel' : old('source', 'manual');
@endphp

<div class="container-fluid p-4 ll-page">
    <div class="page-hero">
        <div class="page-hero-left">
            <span class="page-hero-icon"><i class="bi bi-people-fill"></i></span>
            <div>
                <h1 class="page-title">Loan Leads</h1>
                <div class="page-sub">Track, filter and create loan leads including channel partners.</div>
            </div>
        </div>
        @if($canCreate)
            <button type="button" class="btn btn-create" data-bs-toggle="modal" data-bs-target="#createLeadModal">
                <i class="bi bi-plus-lg"></i> Create Lead
            </button>
        @endif
    </div>

    <div class="filter-card">
        <form method="GET" action="{{ route('goal.leads.index') }}" class="filter-grid" id="llFilterForm">
            <div>
                <label for="q">Search</label>
                <input type="text" name="q" id="q" class="form-control"
                       placeholder="Name, mobile, ref or partner..." value="{{ request('q') }}">
            </div>
            <div>
                <label for="lead_type">Lead Type</label>
                <select name="lead_type" id="lead_type" class="form-select" data-search="false">
                    <option value="">All</option>
                    <option value="immediate" @selected(request('lead_type')==='immediate')>Immediate</option>
                    <option value="future" @selected(request('lead_type')==='future')>Future</option>
                </select>
            </div>
            <div>
                <label for="status">Status</label>
                <select name="status" id="status" class="form-select" data-search="false">
                    <option value="">All</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st }}" @selected(request('status')===$st)>{{ $st }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="source">Source</label>
                <select name="source" id="source" class="form-select" data-search="false">
                    <option value="">All</option>
                    <option value="manual" @selected(request('source')==='manual')>Manual</option>
                    <option value="vrm" @selected(request('source')==='vrm')>VRM</option>
                    <option value="channel" @selected(request('source')==='channel')>Channel</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-apply"><i class="bi bi-funnel"></i> Apply</button>
                <a href="{{ route('goal.leads.index') }}" class="btn-reset"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
            </div>
        </form>
    </div>

    <div class="row g-2 kpi-row">
        <div class="col-6 col-xl-3">
            <div class="stat-tile tone-blue">
                <span class="stat-icon"><i class="bi bi-people-fill"></i></span>
                <div class="stat-meta">
                    <p class="stat-label">Total Leads</p>
                    <span class="stat-chip">In current filters</span>
                </div>
                <p class="stat-value">{{ $totalLeads }}</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-tile tone-green">
                <span class="stat-icon"><i class="bi bi-lightning-charge-fill"></i></span>
                <div class="stat-meta">
                    <p class="stat-label">Immediate</p>
                    <span class="stat-chip">Ready to allocate</span>
                </div>
                <p class="stat-value">{{ $immediateCount }}</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-tile tone-orange">
                <span class="stat-icon"><i class="bi bi-calendar-event-fill"></i></span>
                <div class="stat-meta">
                    <p class="stat-label">Future</p>
                    <span class="stat-chip">Follow-ups queued</span>
                </div>
                <p class="stat-value">{{ $futureCount }}</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-tile tone-purple">
                <span class="stat-icon"><i class="bi bi-diagram-3-fill"></i></span>
                <div class="stat-meta">
                    <p class="stat-label">Channel</p>
                    <span class="stat-chip">Partner sourced</span>
                </div>
                <p class="stat-value">{{ $channelCount }}</p>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-card-head">
            <div class="table-head-left">
                <span class="section-icon"><i class="bi bi-file-earmark-text-fill"></i></span>
                <div>
                    <h5>All Loan Leads</h5>
                    <div class="head-sub">Browse leads with status, source and assignment details.</div>
                </div>
            </div>
        </div>
        <div class="table-card-body">
            <div class="leads-table-wrap">
                <table class="table align-middle mb-0" id="llLeadsTable">
                    <thead>
                        <tr>
                            <th>Ref</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Branch</th>
                            <th>Assignee</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($leads as $lead)
                        <tr>
                            <td data-label="Ref"><span class="ref-cell">{{ $lead->ref_no }}</span></td>
                            <td data-label="Customer">
                                <div class="cust-name">{{ $lead->customer_name }}</div>
                                <div class="cust-mobile">{{ $lead->mobile ?: '—' }}</div>
                            </td>
                            <td data-label="Type">
                                <span class="type-pill {{ $lead->lead_type }}">{{ ucfirst($lead->lead_type) }}</span>
                            </td>
                            <td data-label="Source">
                                <span class="source-pill {{ $sourceClass($lead->source) }}">{{ $lead->source ? ucfirst($lead->source) : '—' }}</span>
                            </td>
                            <td data-label="Status">
                                <span class="status-pill {{ $statusClass($lead->status) }}" title="{{ $lead->status }}">{{ $lead->status }}</span>
                            </td>
                            <td data-label="Branch"><span class="branch-cell">{{ $lead->orgUnit?->name ?? '—' }}</span></td>
                            <td data-label="Assignee"><span class="assignee-cell">{{ $lead->assignee?->name ?? '—' }}</span></td>
                            <td class="col-actions" data-label="Actions">
                                <a href="{{ route('goal.leads.show', $lead) }}"
                                   class="action-btn open"
                                   data-bs-toggle="tooltip"
                                   data-bs-title="Open lead">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="col-empty" data-label="">
                                <div class="empty-state">
                                    <div class="illus"><i class="bi bi-inbox"></i></div>
                                    <div>No leads found.@if($canCreate) Click <strong>Create Lead</strong> to add one.@endif</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-footer-bar">
                <div class="foot-showing">
                    @if($leads->total() > 0)
                        Showing {{ $leads->firstItem() }}–{{ $leads->lastItem() }} of {{ $leads->total() }}
                    @else
                        Showing 0 entries
                    @endif
                </div>
                <div class="foot-right">
                    <form method="GET" action="{{ route('goal.leads.index') }}" class="per-page-wrap">
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
                                <option value="{{ $n }}" @selected((int)$perPage === $n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </form>
                    <div class="foot-pager">
                        {{ $leads->onEachSide(1)->links('vendor.pagination.users') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($canCreate)
<div class="modal fade" id="createLeadModal" tabindex="-1" aria-labelledby="createLeadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('goal.leads.store') }}" id="createLeadForm" novalidate>
                @csrf
                <div class="modal-header">
                    <div class="modal-title-wrap">
                        <span class="modal-icon"><i class="bi bi-person-plus"></i></span>
                        <div>
                            <h5 class="modal-title" id="createLeadModalLabel">Create Loan Lead</h5>
                            <div class="modal-sub">Capture customer details, source and follow-up.</div>
                        </div>
                    </div>
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body">
                    <div class="section-bar"><i class="bi bi-person-vcard"></i> Customer details</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Customer name <span class="text-danger">*</span></label>
                            <input name="customer_name" class="form-control @error('customer_name') is-invalid @enderror"
                                   value="{{ old('customer_name') }}" required maxlength="150"
                                   placeholder="Full name">
                            @error('customer_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Mobile <span class="req">*</span></label>
                            <input name="mobile" id="create_mobile" class="form-control" value="{{ old('mobile') }}" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" placeholder="10-digit mobile" autocomplete="off" required>
                            <div id="create_party_matches" class="small mt-1" style="display:none;color:#b45309"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Email</label>
                            <input name="email" type="email" class="form-control" value="{{ old('email') }}" placeholder="optional">
                        </div>
                    </div>

                    <div class="section-bar"><i class="bi bi-sliders"></i> Lead details</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Lead type <span class="text-danger">*</span></label>
                            <select name="lead_type" id="create_lead_type" class="form-select" required>
                                <option value="immediate" @selected(old('lead_type', 'immediate')==='immediate')>Immediate</option>
                                <option value="future" @selected(old('lead_type')==='future')>Future</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Source</label>
                            <select name="source" id="create_source" class="form-select">
                                <option value="manual" @selected($defaultSource==='manual')>Manual</option>
                                <option value="vrm" @selected($defaultSource==='vrm')>VRM</option>
                                <option value="channel" @selected($defaultSource==='channel')>Channel</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="channelPartnerWrap" style="{{ $defaultSource==='channel' ? '' : 'display:none' }}">
                            <label class="form-label">Channel partner</label>
                            <input name="channel_partner" class="form-control" value="{{ old('channel_partner') }}"
                                   placeholder="Partner name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Product</label>
                            <select name="product" class="form-select">
                                <option value="">— Select —</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->product_name }}" @selected(old('product')===$p->product_name)>{{ $p->product_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Loan amount</label>
                            <input name="loan_amount" type="text" inputmode="decimal" data-decimals="2"
                                   class="form-control js-decimal" value="{{ old('loan_amount') }}" placeholder="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Branch</label>
                            <select name="goal_org_unit_id" class="form-select">
                                <option value="">—</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" @selected(old('goal_org_unit_id', auth()->user()->goal_org_unit_id)==$b->id)>{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Follow-up (required for Future)</label>
                            <input type="text" name="follow_up_at" id="create_follow_up_at"
                                   class="form-control js-datetimepicker"
                                   value="{{ old('follow_up_at') }}" placeholder="dd-mm-yyyy hh:mm" autocomplete="off">
                            @error('follow_up_at')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes">{{ old('notes') }}</textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" name="consent_recorded" id="consent_recorded"
                                       @checked(old('consent_recorded'))>
                                <label class="form-check-label" for="consent_recorded">Consent recorded</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-outline" data-bs-dismiss="modal">Cancel</button>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" name="save_and_new" value="1" class="btn btn-light-outline">Save &amp; New</button>
                        <button type="submit" class="btn btn-save"><i class="bi bi-check-lg"></i> Save Lead</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        document.querySelectorAll('.ll-page [data-bs-toggle="tooltip"]').forEach(function (el) {
            bootstrap.Tooltip.getOrCreateInstance(el, { container: 'body', trigger: 'hover focus' });
        });
    }

    const sourceEl = document.getElementById('create_source');
    const partnerWrap = document.getElementById('channelPartnerWrap');
    function syncChannelPartner() {
        if (!sourceEl || !partnerWrap) return;
        partnerWrap.style.display = sourceEl.value === 'channel' ? '' : 'none';
    }
    sourceEl?.addEventListener('change', syncChannelPartner);
    syncChannelPartner();

    const mobileEl = document.getElementById('create_mobile');
    const matchBox = document.getElementById('create_party_matches');
    let matchTimer = null;
    function renderMatches(data) {
        if (!matchBox) return;
        const leads = (data && data.leads) || [];
        const cases = (data && data.cases) || [];
        if (!leads.length && !cases.length) {
            matchBox.style.display = 'none';
            matchBox.innerHTML = '';
            return;
        }
        const bits = [];
        leads.slice(0, 3).forEach(function (l) {
            bits.push('<a href="' + l.url + '" target="_blank" rel="noopener">' + (l.ref_no || 'Lead') + '</a> · ' + (l.status || ''));
        });
        cases.slice(0, 3).forEach(function (c) {
            bits.push('<a href="' + c.url + '" target="_blank" rel="noopener">' + (c.case_no || 'Case') + '</a> · ' + (c.status || ''));
        });
        matchBox.innerHTML = 'Possible duplicates: ' + bits.join(' · ');
        matchBox.style.display = '';
    }
    function fetchMatches() {
        if (!mobileEl || !matchBox) return;
        const mobile = (mobileEl.value || '').trim();
        if (mobile.replace(/\D+/g, '').length < 8) {
            matchBox.style.display = 'none';
            matchBox.innerHTML = '';
            return;
        }
        const url = @json(route('goal.leads.party-matches')) + '?mobile=' + encodeURIComponent(mobile);
        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) { if (data) renderMatches(data); })
            .catch(function () {});
    }
    mobileEl?.addEventListener('input', function () {
        clearTimeout(matchTimer);
        matchTimer = setTimeout(fetchMatches, 400);
    });
    mobileEl?.addEventListener('blur', fetchMatches);

    @if(!empty($openCreate) && $canCreate)
    const modalEl = document.getElementById('createLeadModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
    @endif
})();
</script>
@endpush
