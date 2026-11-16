@extends('layouts.app')

@push('styles')
<style>
.lc-page{color:#1e293b}
.lc-page .lc-hero-banner{
    position:relative;overflow:hidden;
    display:flex;align-items:center;justify-content:space-between;gap:1.25rem;
    min-height:118px;margin-bottom:1.15rem;padding:1.15rem 1.35rem;
    border-radius:20px;border:1px solid #e8e4f8;
    background:linear-gradient(118deg,#f4f0ff 0%,#eef4ff 48%,#f7f9ff 100%);
    box-shadow:0 10px 28px rgba(148,163,184,.12);
}
.lc-page .lc-hero-waves{
    position:absolute;inset:0;pointer-events:none;z-index:0;opacity:.9;
}
.lc-page .lc-hero-waves svg{width:100%;height:100%;display:block}
.lc-page .lc-hero-main{
    position:relative;z-index:1;display:flex;align-items:center;gap:14px;min-width:0;
}
.lc-page .page-hero-icon{
    width:52px;height:52px;border-radius:14px;flex-shrink:0;
    background:linear-gradient(145deg,#4f46e5 0%,#2563eb 55%,#7c3aed 100%);
    color:#fff;display:inline-flex;align-items:center;justify-content:center;
    font-size:1.3rem;box-shadow:0 12px 24px rgba(79,70,229,.28);
}
.lc-page .page-title{font-size:1.55rem;font-weight:800;color:#0f172a;margin:0;line-height:1.2;letter-spacing:-.02em}
.lc-page .page-sub{color:#64748b;font-size:.9rem;margin-top:4px;line-height:1.4}
.lc-page .lc-hero-art{
    position:relative;z-index:1;flex-shrink:0;width:118px;height:96px;
    display:flex;align-items:center;justify-content:center;
}
.lc-page .lc-hero-art svg{width:110px;height:96px;filter:drop-shadow(0 10px 18px rgba(37,99,235,.22))}

.lc-page .kpi-row{margin-bottom:1.15rem}
.lc-page .stat-tile{
    --tile-ink:#2563eb;--tile-soft:#dbeafe;--tile-chip:#eff6ff;--tile-wave:#93c5fd;
    position:relative;overflow:hidden;height:100%;min-height:96px;
    border:1px solid #eef2f7;border-radius:16px;padding:1rem 1.05rem;
    background:#fff;
    box-shadow:0 8px 22px rgba(148,163,184,.12);
    display:flex;align-items:center;gap:12px;
}
.lc-page .stat-tile::after{
    content:"";position:absolute;right:-18px;bottom:-28px;width:86px;height:86px;
    border-radius:42% 58% 55% 45% / 48% 42% 58% 52%;
    background:radial-gradient(circle at 30% 30%,var(--tile-wave),transparent 70%);
    opacity:.35;pointer-events:none;
}
.lc-page .stat-tile.tone-blue{--tile-ink:#2563eb;--tile-soft:#bfdbfe;--tile-chip:#eff6ff;--tile-wave:#60a5fa}
.lc-page .stat-tile.tone-orange{--tile-ink:#ea580c;--tile-soft:#fed7aa;--tile-chip:#fff7ed;--tile-wave:#fb923c}
.lc-page .stat-tile.tone-green{--tile-ink:#16a34a;--tile-soft:#bbf7d0;--tile-chip:#f0fdf4;--tile-wave:#4ade80}
.lc-page .stat-tile.tone-purple{--tile-ink:#7c3aed;--tile-soft:#ddd6fe;--tile-chip:#f5f3ff;--tile-wave:#a78bfa}
.lc-page .stat-tile.tone-red{--tile-ink:#dc2626;--tile-soft:#fecaca;--tile-chip:#fef2f2;--tile-wave:#f87171}
.lc-page .stat-icon{
    position:relative;z-index:1;width:44px;height:44px;border-radius:12px;flex-shrink:0;
    display:inline-flex;align-items:center;justify-content:center;
    background:var(--tile-chip);color:var(--tile-ink);font-size:1.15rem;
}
.lc-page .stat-body{position:relative;z-index:1;min-width:0}
.lc-page .stat-label{margin:0 0 4px;font-size:.78rem;font-weight:600;color:#64748b}
.lc-page .stat-value-row{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.lc-page .stat-value{margin:0;font-size:1.7rem;font-weight:800;color:#0f172a;line-height:1;letter-spacing:-.03em}
.lc-page .stat-trend{
    display:inline-flex;align-items:center;gap:3px;
    padding:.18rem .5rem;border-radius:999px;font-size:.7rem;font-weight:700;line-height:1;
}
.lc-page .stat-trend.up{background:#dcfce7;color:#15803d}
.lc-page .stat-trend.down{background:#fee2e2;color:#b91c1c}

.lc-page .lc-tabs-card,
.lc-page .lc-filter-card,
.lc-page .table-card{
    background:#fff;border:1px solid #e8edf5;border-radius:16px;
    box-shadow:0 10px 28px rgba(148,163,184,.14);
}
.lc-page .lc-tabs-card{
    padding:.7rem .85rem;margin-bottom:.85rem;
}
.lc-page .lc-filter-card{padding:1rem 1.15rem;margin-bottom:1.15rem}
.lc-page .status-tabs{
    display:flex;flex-wrap:wrap;align-items:center;gap:4px 2px;
}
.lc-page .status-tab{
    display:inline-flex;align-items:center;gap:7px;
    height:38px;padding:0 14px;border-radius:999px;
    border:0;background:transparent;color:#334155;
    font-size:.86rem;font-weight:600;text-decoration:none;
    transition:background .15s,color .15s,box-shadow .15s;
}
.lc-page .status-tab i{font-size:.95rem;opacity:.9}
.lc-page .status-tab:hover{background:#f8fafc;color:#0f172a}
.lc-page .status-tab.active{
    background:#2563eb;color:#fff;
    box-shadow:0 6px 14px rgba(37,99,235,.28);
}
.lc-page .filter-grid{
    display:grid;
    grid-template-columns:minmax(200px,1.5fr) repeat(3,minmax(130px,1fr)) minmax(180px,1.1fr) auto;
    gap:12px;align-items:end;
}
.lc-page .filter-grid label{
    display:block;font-size:.78rem;font-weight:600;color:#64748b;
    margin:0 0 6px;
}
.lc-page .filter-grid .form-control,
.lc-page .filter-grid .form-select{
    min-height:40px;height:40px;border-radius:10px;border:1px solid #d1d5db;font-size:.9rem;background:#fff;
}
.lc-page .filter-grid .select2-container{width:100%!important}
.lc-page .filter-grid .select2-container--default .select2-selection--single{
    height:40px!important;min-height:40px!important;border:1px solid #d1d5db!important;
    border-radius:10px!important;background:#fff!important;
}
.lc-page .filter-grid .select2-container--default .select2-selection--single .select2-selection__rendered{
    line-height:38px!important;padding-left:.75rem!important;color:#0f172a!important;
}
.lc-page .lc-filter-card,.lc-page .filter-grid{overflow:visible!important;position:relative;z-index:5}
.lc-page .lc-search{position:relative}
.lc-page .lc-search > i{
    position:absolute;left:12px;top:50%;transform:translateY(-50%);
    color:#94a3b8;font-size:.95rem;z-index:2;pointer-events:none;
}
.lc-page .lc-search .form-control{padding-left:38px}
.lc-page .lc-date{position:relative}
.lc-page .lc-date > i{
    position:absolute;right:12px;top:50%;transform:translateY(-50%);
    color:#94a3b8;font-size:.95rem;z-index:2;pointer-events:none;
}
.lc-page .lc-date .form-control,
.lc-page .lc-date .form-control.flatpickr-input,
.lc-page .lc-date input.form-control{padding-right:36px;width:100%}
.lc-page .filter-actions{display:flex;align-items:end;gap:8px;flex-wrap:wrap}
.lc-page .btn-reset{
    height:40px;border-radius:10px;border:1px solid #cbd5e1;background:#fff;color:#334155;
    padding:0 14px;font-weight:600;font-size:.85rem;text-decoration:none;
    display:inline-flex;align-items:center;gap:6px;
}
.lc-page .btn-reset:hover{background:#f8fafc;color:#0f172a}
.lc-page .btn-create-sm{
    height:40px;border-radius:10px;border:none;
    background:linear-gradient(90deg,#2563eb,#7c3aed);color:#fff;
    padding:0 16px;font-weight:700;font-size:.85rem;text-decoration:none;
    display:inline-flex;align-items:center;gap:6px;
    box-shadow:0 6px 14px rgba(37,99,235,.22);
}
.lc-page .btn-create-sm:hover{filter:brightness(1.05);color:#fff}

.lc-page .table-card-head{
    display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;
    gap:.85rem;padding:1.1rem 1.25rem;border-bottom:1px solid #eef2f7;
}
.lc-page .table-head-left{display:flex;align-items:flex-start;gap:12px;min-width:0}
.lc-page .table-card-head h5{margin:0;font-size:1.08rem;font-weight:800;color:#0f172a}
.lc-page .section-icon{
    width:42px;height:42px;border-radius:12px;flex-shrink:0;
    background:linear-gradient(145deg,#dbeafe,#bfdbfe);color:#1d4ed8;
    display:inline-flex;align-items:center;justify-content:center;font-size:1.1rem;
}
.lc-page .head-sub{margin:4px 0 0;font-size:.82rem;color:#64748b}
.lc-page .table-card-body{padding:1rem 1.25rem 1.2rem}
.lc-page .cases-table-wrap{
    border:1px solid #e8edf5;border-radius:14px;overflow-x:auto;
    -webkit-overflow-scrolling:touch;background:#fff;
}
.lc-page .cases-table-wrap table{min-width:1480px;width:100%;margin:0}
.lc-page .person-cell{display:flex;flex-direction:column;gap:2px;min-width:0}
.lc-page .person-cell .p-name{font-weight:700;color:#0f172a;line-height:1.25}
.lc-page .person-cell .p-role{font-size:.72rem;color:#64748b;font-weight:600}
.lc-page .muted-dash{color:#94a3b8}
.lc-page .lead-ref{
    font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
    font-size:.78rem;font-weight:700;color:#475569;text-decoration:none;
}
.lc-page .lead-ref:hover{color:#2563eb;text-decoration:underline}
.lc-page .amount-cell{font-weight:700;color:#0f172a}
.lc-page table thead th{
    font-size:.7rem;text-transform:uppercase;letter-spacing:.04em;
    color:#64748b;font-weight:700;background:#f1f5f9;
    border-bottom:1px solid #e2e8f0;white-space:nowrap;padding:.8rem .9rem;
}
.lc-page table tbody td{
    vertical-align:middle;font-size:.875rem;color:#334155;
    border-color:#eef2f7;padding:.85rem .9rem;white-space:nowrap;
}
.lc-page table tbody tr:hover{background:#f8fbff}
.lc-page .case-link{
    font-weight:800;color:#2563eb;text-decoration:none;
    font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.82rem;
}
.lc-page .case-link:hover{color:#1d4ed8;text-decoration:underline}
.lc-page .cust-cell{display:flex;align-items:center;gap:10px;min-width:0}
.lc-page .cust-avatar{
    width:36px;height:36px;border-radius:999px;flex-shrink:0;
    display:inline-flex;align-items:center;justify-content:center;
    background:linear-gradient(145deg,#dbeafe,#bfdbfe);color:#1d4ed8;
    font-size:.72rem;font-weight:800;letter-spacing:.02em;
}
.lc-page .cust-name{font-weight:700;color:#0f172a;line-height:1.25}
.lc-page .cust-mobile{font-size:.78rem;color:#64748b;margin-top:2px}
.lc-page .product-pill{
    display:inline-flex;align-items:center;gap:6px;
    padding:.28rem .7rem;border-radius:999px;font-size:.74rem;font-weight:700;
    background:#eff6ff;color:#1d4ed8;
}
.lc-page .product-pill.home{background:#ecfdf5;color:#059669}
.lc-page .product-pill.personal{background:#eff6ff;color:#2563eb}
.lc-page .product-pill.business{background:#fff7ed;color:#c2410c}
.lc-page .branch-cell{
    display:inline-flex;align-items:center;gap:6px;color:#475569;font-weight:600;
}
.lc-page .branch-cell i{color:#94a3b8}
.lc-page .reco-cell{font-weight:700;color:#0f172a}
.lc-page .status-pill{
    display:inline-flex;align-items:center;justify-content:center;
    padding:.3rem .75rem;border-radius:999px;font-size:.74rem;font-weight:700;
    background:#f1f5f9;color:#334155;
}
.lc-page .status-pill.in-process{background:#dbeafe;color:#1d4ed8}
.lc-page .status-pill.bank-query{background:#ffedd5;color:#c2410c}
.lc-page .status-pill.sanctioned{background:#dcfce7;color:#15803d}
.lc-page .status-pill.disbursed{background:#ccfbf1;color:#0f766e}
.lc-page .status-pill.rejected{background:#fee2e2;color:#b91c1c}
.lc-page .status-pill.draft{background:#f1f5f9;color:#64748b}
.lc-page .updated-cell{
    display:inline-flex;align-items:center;gap:6px;color:#64748b;font-weight:600;font-size:.82rem;
}
.lc-page .updated-cell i{color:#94a3b8}
.lc-page .actions-cell{display:inline-flex;align-items:center;gap:6px}
.lc-page .action-btn{
    width:34px;height:34px;border-radius:10px;border:1.5px solid #bfdbfe;
    background:#eff6ff;color:#2563eb;
    display:inline-flex;align-items:center;justify-content:center;
    text-decoration:none;transition:.15s ease;
}
.lc-page .action-btn:hover{background:#dbeafe;color:#1d4ed8}
.lc-page .action-btn.more{
    border-color:#e2e8f0;background:#fff;color:#64748b;
}
.lc-page .action-btn.more:hover{background:#f8fafc;color:#0f172a}
.lc-page .table-footer-bar{
    display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;
    gap:12px 16px;margin-top:.95rem;padding-top:.95rem;
    border-top:1px solid #eef2f7;color:#64748b;font-size:.84rem;
}
.lc-page .foot-showing{font-weight:600}
.lc-page .foot-right{display:flex;flex-wrap:wrap;align-items:center;gap:12px 16px;margin-left:auto}
.lc-page .foot-pager .pagination{margin:0;gap:6px}
.lc-page .foot-pager .page-link{
    border:0!important;border-radius:8px!important;min-width:34px;height:34px;
    display:inline-flex!important;align-items:center;justify-content:center;
    color:#475569!important;background:transparent!important;font-weight:600;
}
.lc-page .foot-pager .page-item.active .page-link{
    background:#2563eb!important;color:#fff!important;
    box-shadow:0 4px 10px rgba(37,99,235,.28)!important;
}
.lc-page .empty-state{padding:2.5rem 1rem;text-align:center;color:#94a3b8}
.lc-page .empty-state .illus{
    width:56px;height:56px;border-radius:16px;margin:0 auto .75rem;
    background:#f1f5f9;color:#94a3b8;display:inline-flex;align-items:center;justify-content:center;font-size:1.4rem;
}

@media (max-width: 1199.98px) {
    .lc-page .filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    .lc-page .filter-actions{grid-column:1 / -1}
}
@media (max-width: 767.98px) {
    .lc-page.container-fluid{padding:1rem .85rem!important}
    .lc-page .lc-hero-banner{padding:1rem 1.05rem;min-height:0}
    .lc-page .lc-hero-art{display:none}
    .lc-page .stat-value{font-size:1.45rem}
    .lc-page .filter-grid{grid-template-columns:1fr}
    .lc-page .filter-actions{display:grid;grid-template-columns:1fr 1fr;width:100%}
    .lc-page .btn-reset,.lc-page .btn-create-sm{width:100%;justify-content:center}
    .lc-page .cases-table-wrap{border:0;background:transparent;overflow:visible}
    .lc-page .cases-table-wrap table{min-width:0!important;display:block;width:100%}
    .lc-page .cases-table-wrap thead{display:none}
    .lc-page .cases-table-wrap tbody{display:block}
    .lc-page .cases-table-wrap tr{
        display:block;margin:0 0 .85rem;padding:.75rem .85rem;
        border:1px solid #e8edf5;border-radius:14px;background:#fff;
        box-shadow:0 6px 16px rgba(148,163,184,.1);
    }
    .lc-page .cases-table-wrap td{
        display:flex!important;align-items:center;justify-content:space-between;
        gap:.75rem;width:100%;padding:.45rem 0!important;
        border:0!important;border-bottom:1px dashed #eef2f7!important;
        white-space:normal!important;text-align:right;
    }
    .lc-page .cases-table-wrap td:last-child{border-bottom:0!important}
    .lc-page .cases-table-wrap td::before{
        content:attr(data-label);flex:0 0 auto;
        font-size:.72rem;font-weight:700;letter-spacing:.03em;
        text-transform:uppercase;color:#64748b;text-align:left;
    }
    .lc-page .cases-table-wrap td.col-empty{display:block!important;text-align:center;border:0!important}
    .lc-page .cases-table-wrap td.col-empty::before{display:none}
}
</style>
@endpush

@section('content')
@php
    $statusLabel = function ($status) {
        return $status === 'Processing' ? 'In Process' : $status;
    };
    $statusClass = function ($status) {
        return match ($status) {
            'Processing' => 'in-process',
            'Bank Query' => 'bank-query',
            'Sanctioned' => 'sanctioned',
            'Disbursed' => 'disbursed',
            'Rejected' => 'rejected',
            'Draft' => 'draft',
            default => '',
        };
    };
    $productClass = function ($product) {
        $p = strtolower((string) $product);
        if (str_contains($p, 'home')) return 'home';
        if (str_contains($p, 'personal')) return 'personal';
        if (str_contains($p, 'business') || str_contains($p, 'sme')) return 'business';
        return '';
    };
    $productIcon = function ($product) {
        $p = strtolower((string) $product);
        if (str_contains($p, 'home')) return 'bi-house-door';
        if (str_contains($p, 'personal')) return 'bi-briefcase';
        return 'bi-box';
    };
    $initials = function ($name) {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $a = strtoupper(substr($parts[0] ?? 'C', 0, 1));
        $b = strtoupper(substr($parts[1] ?? ($parts[0] ?? 'S'), 0, 1));
        return $a . $b;
    };
    $bmReco = function ($case) {
        $amount = data_get($case->bm_reco, 'amount');
        return $amount !== null && $amount !== '' ? (float) $amount : null;
    };
    $roleLabel = function ($user) {
        if (!$user) return null;
        return match ((int) ($user->crm_role_type ?? -1)) {
            8 => 'BM',
            10 => 'JSM',
            11 => 'SM',
            12 => 'SSM',
            default => strtoupper((string) ($user->job_role ?: 'User')),
        };
    };
    $branchManagersById = $branchManagersById ?? collect();
    $caseBranchManagerIds = $caseBranchManagerIds ?? [];
    $tabQuery = function ($key) {
        return array_filter(array_merge(request()->except('page'), ['tab' => $key]));
    };
@endphp

<div class="container-fluid p-4 lc-page">
    <div class="lc-hero-banner">
        <div class="lc-hero-waves" aria-hidden="true">
            <svg viewBox="0 0 900 140" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0 90 C120 50 220 120 340 70 C460 20 560 110 700 60 C800 30 860 80 900 55 L900 140 L0 140 Z" fill="rgba(167,139,250,.18)"/>
                <path d="M0 105 C140 75 250 130 390 95 C530 60 620 120 760 85 C840 65 880 95 900 80 L900 140 L0 140 Z" fill="rgba(96,165,250,.14)"/>
            </svg>
        </div>
        <div class="lc-hero-main">
            <span class="page-hero-icon"><i class="bi bi-briefcase"></i></span>
            <div>
                <h1 class="page-title">Loan Cases</h1>
                <div class="page-sub">Process loan cases from documentation to disbursement.</div>
            </div>
        </div>
        <div class="lc-hero-art" aria-hidden="true">
            <svg viewBox="0 0 110 96" xmlns="http://www.w3.org/2000/svg">
                <rect x="28" y="10" width="54" height="72" rx="8" fill="#2563eb"/>
                <rect x="34" y="18" width="42" height="56" rx="4" fill="#eff6ff"/>
                <rect x="42" y="6" width="26" height="10" rx="3" fill="#93c5fd"/>
                <circle cx="44" cy="34" r="5" fill="#22c55e"/>
                <path d="M41.5 34 l1.8 1.8 3.6-3.6" stroke="#fff" stroke-width="1.6" fill="none" stroke-linecap="round"/>
                <rect x="52" y="31" width="18" height="5" rx="2" fill="#bfdbfe"/>
                <circle cx="44" cy="48" r="5" fill="#22c55e"/>
                <path d="M41.5 48 l1.8 1.8 3.6-3.6" stroke="#fff" stroke-width="1.6" fill="none" stroke-linecap="round"/>
                <rect x="52" y="45" width="18" height="5" rx="2" fill="#bfdbfe"/>
                <circle cx="44" cy="62" r="5" fill="#22c55e"/>
                <path d="M41.5 62 l1.8 1.8 3.6-3.6" stroke="#fff" stroke-width="1.6" fill="none" stroke-linecap="round"/>
                <rect x="52" y="59" width="18" height="5" rx="2" fill="#bfdbfe"/>
            </svg>
        </div>
    </div>

    <div class="row g-3 kpi-row">
        <div class="col-6 col-md">
            <div class="stat-tile tone-blue">
                <span class="stat-icon"><i class="bi bi-folder2"></i></span>
                <div class="stat-body">
                    <p class="stat-label">Total Cases</p>
                    <div class="stat-value-row">
                        <div class="stat-value">{{ number_format($kpis['total']) }}</div>
                        @if($kpis['total_trend_pct'] !== null)
                            <span class="stat-trend {{ $kpis['total_trend_pct'] >= 0 ? 'up' : 'down' }}">
                                <i class="bi {{ $kpis['total_trend_pct'] >= 0 ? 'bi-arrow-up' : 'bi-arrow-down' }}"></i>
                                {{ ($kpis['total_trend_pct'] >= 0 ? '+' : '') . rtrim(rtrim(number_format($kpis['total_trend_pct'], 1), '0'), '.') }}%
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-tile tone-orange">
                <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
                <div class="stat-body">
                    <p class="stat-label">In Process</p>
                    <div class="stat-value-row"><div class="stat-value">{{ number_format($kpis['in_process']) }}</div></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-tile tone-green">
                <span class="stat-icon"><i class="bi bi-check-lg"></i></span>
                <div class="stat-body">
                    <p class="stat-label">Sanctioned</p>
                    <div class="stat-value-row"><div class="stat-value">{{ number_format($kpis['sanctioned']) }}</div></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-tile tone-purple">
                <span class="stat-icon"><i class="bi bi-cash-coin"></i></span>
                <div class="stat-body">
                    <p class="stat-label">Disbursed</p>
                    <div class="stat-value-row"><div class="stat-value">{{ number_format($kpis['disbursed']) }}</div></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="stat-tile tone-red">
                <span class="stat-icon"><i class="bi bi-x-lg"></i></span>
                <div class="stat-body">
                    <p class="stat-label">Rejected</p>
                    <div class="stat-value-row"><div class="stat-value">{{ number_format($kpis['rejected']) }}</div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="lc-tabs-card">
        <div class="status-tabs">
            @foreach($tabs as $key => $meta)
                <a href="{{ route('goal.cases.index', $tabQuery($key)) }}"
                   class="status-tab {{ $tab === $key ? 'active' : '' }}">
                    <i class="bi {{ $meta['icon'] }}"></i> {{ $meta['label'] }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="lc-filter-card">
        <form method="GET" action="{{ route('goal.cases.index') }}" class="filter-grid" id="lcFilterForm">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="hidden" name="date_from" id="date_from" value="{{ request('date_from') }}">
            <input type="hidden" name="date_to" id="date_to" value="{{ request('date_to') }}">
            <div>
                <label for="q">Search</label>
                <div class="lc-search">
                    <i class="bi bi-search"></i>
                    <input type="text" name="q" id="q" class="form-control"
                           placeholder="Search by Case No. / Customer..." value="{{ request('q') }}">
                </div>
            </div>
            <div>
                <label for="branch">Branch</label>
                <select name="branch" id="branch" class="form-select" data-search="false">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" @selected((string) request('branch') === (string) $b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status">Case Status</label>
                <select name="status" id="status" class="form-select" data-search="false">
                    <option value="">All Status</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ $statusLabel($st) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="product">Product</label>
                <select name="product" id="product" class="form-select" data-search="false">
                    <option value="">All Products</option>
                    @foreach($products as $p)
                        <option value="{{ $p->product_name }}" @selected(request('product') === $p->product_name)>{{ $p->product_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="lcDateRange">Date Range</label>
                <div class="lc-date">
                    <input type="text" id="lcDateRange" class="form-control" placeholder="Select Date Range" autocomplete="off" readonly>
                    <i class="bi bi-calendar3"></i>
                </div>
            </div>
            <div class="filter-actions">
                <a href="{{ route('goal.cases.index', ['tab' => $tab]) }}" class="btn-reset"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                @if($canCreate)
                    <a href="{{ route('goal.cases.create') }}" class="btn-create-sm"><i class="bi bi-plus-lg"></i> Create Case</a>
                @endif
            </div>
        </form>
    </div>

    <div class="table-card">
        <div class="table-card-head">
            <div class="table-head-left">
                <span class="section-icon"><i class="bi bi-table"></i></span>
                <div>
                    <h5>Case List</h5>
                    <p class="head-sub">{{ number_format($cases->total()) }} case{{ $cases->total() === 1 ? '' : 's' }} in this view</p>
                </div>
            </div>
        </div>
        <div class="table-card-body">
            <div class="cases-table-wrap">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Case No.</th>
                            <th>Customer</th>
                            <th>Lead Ref</th>
                            <th>Product</th>
                            <th>Amount</th>
                            <th>Branch</th>
                            <th>Branch Manager</th>
                            <th>Assigned To</th>
                            <th>BM Reco</th>
                            <th>Case Status</th>
                            <th>Updated On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($cases as $case)
                        @php
                            $reco = $bmReco($case);
                            $bmId = (int) ($caseBranchManagerIds[$case->id] ?? 0);
                            $bmUser = $bmId ? ($branchManagersById[$bmId] ?? null) : null;
                            $owner = $case->owner;
                        @endphp
                        <tr>
                            <td data-label="Case No.">
                                <a class="case-link" href="{{ route('goal.cases.show', $case) }}">{{ $case->case_no }}</a>
                            </td>
                            <td data-label="Customer">
                                <div class="cust-cell">
                                    <span class="cust-avatar">{{ $initials($case->customer_name) }}</span>
                                    <div>
                                        <div class="cust-name">{{ $case->customer_name }}</div>
                                        @if($case->mobile)
                                            <div class="cust-mobile">{{ $case->mobile }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td data-label="Lead Ref">
                                @if($case->lead)
                                    <a class="lead-ref" href="{{ route('goal.leads.show', $case->lead) }}">{{ $case->lead->ref_no }}</a>
                                @else
                                    <span class="muted-dash">—</span>
                                @endif
                            </td>
                            <td data-label="Product">
                                @if($case->product)
                                    <span class="product-pill {{ $productClass($case->product) }}">
                                        <i class="bi {{ $productIcon($case->product) }}"></i>
                                        {{ $case->product }}
                                    </span>
                                @else
                                    <span class="muted-dash">—</span>
                                @endif
                            </td>
                            <td data-label="Amount" class="amount-cell">
                                {{ $case->loan_amount !== null ? '₹ ' . number_format((float) $case->loan_amount, 0) : '—' }}
                            </td>
                            <td data-label="Branch">
                                <span class="branch-cell">
                                    <i class="bi bi-building"></i>
                                    {{ $case->orgUnit?->name ?? '—' }}
                                </span>
                            </td>
                            <td data-label="Branch Manager">
                                @if($bmUser)
                                    <div class="person-cell">
                                        <span class="p-name">{{ $bmUser->name }}</span>
                                        <span class="p-role">{{ $roleLabel($bmUser) }}</span>
                                    </div>
                                @else
                                    <span class="muted-dash">—</span>
                                @endif
                            </td>
                            <td data-label="Assigned To">
                                @if($owner)
                                    <div class="person-cell">
                                        <span class="p-name">{{ $owner->name }}</span>
                                        <span class="p-role">{{ $roleLabel($owner) }}</span>
                                    </div>
                                @else
                                    <span class="muted-dash">—</span>
                                @endif
                            </td>
                            <td data-label="BM Reco" class="reco-cell">
                                {{ $reco !== null ? '₹ ' . number_format($reco, 0) : '—' }}
                            </td>
                            <td data-label="Case Status">
                                <span class="status-pill {{ $statusClass($case->status) }}">{{ $statusLabel($case->status) }}</span>
                            </td>
                            <td data-label="Updated On">
                                <span class="updated-cell">
                                    <i class="bi bi-calendar3"></i>
                                    {{ optional($case->updated_at)->format('d M Y') ?? '—' }}
                                </span>
                            </td>
                            <td data-label="Actions">
                                <div class="actions-cell">
                                    <a href="{{ route('goal.cases.show', $case) }}" class="action-btn" title="View workspace">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <div class="dropdown">
                                        <button type="button" class="action-btn more" data-bs-toggle="dropdown" aria-expanded="false" title="More">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('goal.cases.show', $case) }}">
                                                    <i class="bi bi-folder2-open me-2"></i> Open workspace
                                                </a>
                                            </li>
                                            @if($case->lead)
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('goal.leads.show', $case->lead) }}">
                                                        <i class="bi bi-person-lines-fill me-2"></i> Open lead
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="col-empty">
                                <div class="empty-state">
                                    <div class="illus"><i class="bi bi-briefcase"></i></div>
                                    <div>No cases found.</div>
                                    <div class="small mt-1">Create a case or convert a Qualified lead to Documents Pending.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($cases->total() > 0)
                <div class="table-footer-bar">
                    <div class="foot-showing">
                        Showing {{ $cases->firstItem() }}–{{ $cases->lastItem() }} of {{ $cases->total() }}
                    </div>
                    <div class="foot-right">
                        <div class="foot-pager">{{ $cases->links() }}</div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('lcFilterForm');
    if (!form) return;

    form.querySelectorAll('select').forEach(function (el) {
        el.addEventListener('change', function () { form.submit(); });
    });

    var q = document.getElementById('q');
    if (q) {
        q.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                form.submit();
            }
        });
    }

    var rangeEl = document.getElementById('lcDateRange');
    var fromEl = document.getElementById('date_from');
    var toEl = document.getElementById('date_to');
    if (rangeEl && window.flatpickr) {
        var defaultDates = [];
        if (fromEl && fromEl.value) defaultDates.push(fromEl.value);
        if (toEl && toEl.value) defaultDates.push(toEl.value);
        flatpickr(rangeEl, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd-m-Y',
            allowInput: false,
            defaultDate: defaultDates.length ? defaultDates : null,
            onClose: function (selectedDates) {
                if (!fromEl || !toEl) return;
                if (selectedDates.length === 0) {
                    fromEl.value = '';
                    toEl.value = '';
                    form.submit();
                    return;
                }
                if (selectedDates.length === 1) {
                    fromEl.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                    toEl.value = fromEl.value;
                    form.submit();
                    return;
                }
                fromEl.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                toEl.value = flatpickr.formatDate(selectedDates[1], 'Y-m-d');
                form.submit();
            }
        });
    }
})();
</script>
@endpush
