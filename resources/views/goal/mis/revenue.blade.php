@extends('layouts.app')

@php
    $f = $filters ?? [];
    $opts = $filterOptions ?? [];
    $kpis = $kpis ?? [];
    $tabs = $tab_counts ?? [];
    $attr = $attribution ?? [];
    $fmtInr = function ($n) {
        $n = (float) $n;
        $neg = $n < 0;
        $s = '₹ '.preg_replace('/\.00$/', '', number_format(abs($n), 2));
        return $neg ? '-'.$s : $s;
    };
    $fmtInr0 = function ($n) {
        return '₹ '.number_format((float) $n, 0);
    };
    $dateFromLabel = !empty($f['date_from']) ? \Carbon\Carbon::parse($f['date_from'])->format('d M Y') : '';
    $dateToLabel = !empty($f['date_to']) ? \Carbon\Carbon::parse($f['date_to'])->format('d M Y') : '';
    $dateRangeLabel = trim($dateFromLabel.($dateFromLabel && $dateToLabel ? ' – ' : '').$dateToLabel);
    $qs = request()->except(['page']);
    $cardQs = fn ($card) => array_merge($qs, ['card' => $card, 'tab' => $card === 'recognized' ? 'recognized' : ($card === 'adjustment' ? 'adjustment' : ($card === 'reversal' ? 'reversal' : ($f['tab'] ?? 'all'))), 'page' => null]);
    $tabQs = fn ($tab) => array_merge($qs, ['tab' => $tab, 'entry_type' => $tab === 'all' ? '' : $tab, 'card' => '', 'page' => null]);
    $activeTab = $f['tab'] ?? 'all';
    $activeCard = $f['card'] ?? '';
@endphp

@push('styles')
@include('goal.partials.pd_hub_styles')
<style>
.pd-page.rev-page{color:#1e293b;background:#f1f5f9}
.pd-page.rev-page .pd-hero{align-items:flex-start;margin-bottom:.75rem}
.pd-page.rev-page .pd-title{display:flex;align-items:center;gap:10px}
.pd-page.rev-page .pd-title .rev-hero-ico{
    width:40px;height:40px;border-radius:10px;background:#ecfdf5;color:#059669;
    display:inline-flex;align-items:center;justify-content:center;font-size:1.15rem
}
.pd-page.rev-page .rev-banner{
    display:flex;gap:10px;align-items:flex-start;margin:0;padding:12px 14px;
    background:#ecfdf5;border:1px solid #a7f3d0;border-radius:10px;color:#065f46;font-size:.84rem;line-height:1.45
}
.pd-page.rev-page .rev-banner i{color:#059669;font-size:1.05rem;margin-top:2px}
.pd-page.rev-page .rev-banner .rev-banner-ico{
    width:22px;height:22px;border-radius:50%;background:#10b981;color:#fff;
    display:inline-flex;align-items:center;justify-content:center;font-size:.72rem;flex-shrink:0;margin-top:1px
}

/* Page shell: left (KPIs+filters+table) | right (detail cards) */
.pd-page.rev-page .rev-shell{
    display:grid;grid-template-columns:minmax(0,1fr) minmax(300px,340px);gap:14px;align-items:start
}
@media (max-width:1199.98px){.pd-page.rev-page .rev-shell{grid-template-columns:1fr}}
.pd-page.rev-page .rev-left{min-width:0;display:flex;flex-direction:column;gap:12px}
.pd-page.rev-page .rev-right{min-width:0;align-self:start;position:sticky;top:84px;z-index:2}

/* KPI + filters card (left top) */
.pd-page.rev-page .rev-summary-card{
    background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:14px;box-shadow:0 1px 2px rgba(15,23,42,.04)
}
.pd-page.rev-page .rev-kpi-row{
    display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin-bottom:14px
}
@media (max-width:1400px){.pd-page.rev-page .rev-kpi-row{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media (max-width:767.98px){.pd-page.rev-page .rev-kpi-row{grid-template-columns:1fr}}
.pd-page.rev-page a.rev-kpi{
    text-decoration:none;color:inherit;border:1px solid #e8edf5;border-radius:14px;
    padding:14px 16px;position:relative;overflow:hidden;min-height:92px;
    display:flex;align-items:center;gap:12px;background:#fff;
    box-shadow:0 1px 2px rgba(15,23,42,.03);
    transition:transform .15s ease,box-shadow .15s ease,border-color .15s ease
}
.pd-page.rev-page a.rev-kpi::after{
    content:'';position:absolute;right:-18px;bottom:-28px;width:88px;height:88px;border-radius:50%;
    pointer-events:none;opacity:.55;filter:blur(2px)
}
.pd-page.rev-page a.rev-kpi:hover{transform:translateY(-1px);box-shadow:0 8px 18px rgba(15,23,42,.08)}
.pd-page.rev-page a.rev-kpi.is-active{border-color:#10b981;box-shadow:0 0 0 2px rgba(16,185,129,.22)}
.pd-page.rev-page a.rev-kpi.k-green::after{background:radial-gradient(circle,rgba(34,197,94,.28) 0%,rgba(34,197,94,0) 70%)}
.pd-page.rev-page a.rev-kpi.k-teal::after{background:radial-gradient(circle,rgba(37,99,235,.26) 0%,rgba(37,99,235,0) 70%)}
.pd-page.rev-page a.rev-kpi.k-amber::after{background:radial-gradient(circle,rgba(245,158,11,.28) 0%,rgba(245,158,11,0) 70%)}
.pd-page.rev-page a.rev-kpi.k-rose::after{background:radial-gradient(circle,rgba(244,63,94,.26) 0%,rgba(244,63,94,0) 70%)}
.pd-page.rev-page a.rev-kpi.k-violet::after{background:radial-gradient(circle,rgba(124,58,237,.26) 0%,rgba(124,58,237,0) 70%)}
.pd-page.rev-page .rev-kpi-ico{
    width:42px;height:42px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;
    font-size:1.15rem;flex-shrink:0;position:relative;z-index:1
}
.pd-page.rev-page .k-green .rev-kpi-ico{background:#dcfce7;color:#16a34a}
.pd-page.rev-page .k-teal .rev-kpi-ico{background:#dbeafe;color:#2563eb}
.pd-page.rev-page .k-amber .rev-kpi-ico{background:#ffedd5;color:#ea580c}
.pd-page.rev-page .k-rose .rev-kpi-ico{background:#ffe4e6;color:#e11d48}
.pd-page.rev-page .k-violet .rev-kpi-ico{background:#ede9fe;color:#7c3aed}
.pd-page.rev-page .rev-kpi-body{min-width:0;position:relative;z-index:1;display:flex;flex-direction:column;gap:2px}
.pd-page.rev-page .rev-kpi-label{
    margin:0;font-size:.78rem;font-weight:700;color:#334155;line-height:1.25
}
.pd-page.rev-page .rev-kpi-value{
    margin:0;font-size:1.2rem;font-weight:800;color:#0f172a;letter-spacing:-.02em;line-height:1.15;word-break:break-word
}
.pd-page.rev-page .rev-kpi-meta{margin:0;font-size:.75rem;font-weight:600;color:#64748b}

/* Filters — two rows inside summary card */
.pd-page.rev-page .rev-filters{
    background:#fff;border:1px solid #e8edf5;border-radius:10px;padding:12px
}
.pd-page.rev-page .rev-filter-row{display:grid;gap:8px;align-items:end}
.pd-page.rev-page .rev-filter-row.r1{grid-template-columns:1.15fr .85fr repeat(3,minmax(0,1fr));margin-bottom:8px}
.pd-page.rev-page .rev-filter-row.r2{grid-template-columns:repeat(4,minmax(0,1fr)) auto}
.pd-page.rev-page .rev-filters label{
    display:block;font-size:.62rem;font-weight:700;color:#64748b;margin:0 0 4px;text-transform:uppercase;letter-spacing:.03em
}
.pd-page.rev-page .rev-filters .form-control,
.pd-page.rev-page .rev-filters .form-select{
    min-height:34px;height:34px;border-radius:8px;border:1px solid #d1d5db;font-size:.78rem;background:#fff;padding:.25rem .55rem
}
.pd-page.rev-page .rev-filters .rf-fy .form-select{text-overflow:clip;white-space:nowrap;padding-right:1.6rem}
.pd-page.rev-page .rev-date-wrap{position:relative}
.pd-page.rev-page .rev-date-wrap .bi-calendar3{
    position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#94a3b8;z-index:2;pointer-events:none;font-size:.8rem
}
.pd-page.rev-page .rev-date-wrap .form-control{padding-left:28px}
.pd-page.rev-page .rev-filter-actions{display:flex;gap:6px;align-items:center;height:34px}
.pd-page.rev-page .rev-filter-actions .pd-btn,
.pd-page.rev-page .rev-filter-actions .pd-btn-ghost{height:34px;padding:0 12px;font-size:.78rem}
@media (max-width:1100px){
    .pd-page.rev-page .rev-filter-row.r1,
    .pd-page.rev-page .rev-filter-row.r2{grid-template-columns:repeat(2,minmax(0,1fr))}
    .pd-page.rev-page .rev-filter-actions{grid-column:1/-1}
}

.pd-page.rev-page .rev-main-card,
.pd-page.rev-page .rev-drawer{
    background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.04);overflow:hidden
}
.pd-page.rev-page .rev-tabs{
    display:flex;flex-wrap:wrap;gap:8px;align-items:center;padding:12px 14px 0;background:#fff
}
.pd-page.rev-page .rev-tab{
    appearance:none;border:1px solid #e2e8f0;background:#f8fafc;padding:.45rem .85rem;font-size:.8rem;font-weight:700;color:#64748b;
    border-radius:999px;text-decoration:none;display:inline-flex;gap:6px;align-items:center
}
.pd-page.rev-page .rev-tab:hover{color:#059669;border-color:#a7f3d0;background:#ecfdf5}
.pd-page.rev-page .rev-tab.active{color:#fff;background:#059669;border-color:#059669}
.pd-page.rev-page .rev-tab .n{
    min-width:1.45rem;height:1.25rem;padding:0 6px;border-radius:999px;background:rgba(15,23,42,.06);color:inherit;
    font-size:.7rem;display:inline-flex;align-items:center;justify-content:center;font-weight:700
}
.pd-page.rev-page .rev-tab.active .n{background:rgba(255,255,255,.22);color:#fff}
.pd-page.rev-page .rev-toolbar{
    display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;
    padding:.75rem 1rem;border-bottom:1px solid #f1f5f9
}
.pd-page.rev-page .rev-toolbar-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.pd-page.rev-page .rev-btn-outline,
.pd-page.rev-page .rev-btn-outline-danger{
    height:36px;padding:0 12px;border-radius:8px;background:#fff;
    font-size:.8rem;font-weight:700;display:inline-flex;align-items:center;gap:6px;cursor:pointer
}
.pd-page.rev-page .rev-btn-outline{border:1.5px solid #10b981;color:#059669}
.pd-page.rev-page .rev-btn-outline:hover{background:#ecfdf5}
.pd-page.rev-page .rev-btn-outline-danger{border:1.5px solid #e11d48;color:#e11d48}
.pd-page.rev-page .rev-btn-outline-danger:hover{background:#fff1f2}
.pd-page.rev-page .rev-attr-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;width:100%}
.pd-page.rev-page .rev-view-all{font-size:.78rem;font-weight:700;color:#059669;text-decoration:none;white-space:nowrap}
.pd-page.rev-page .rev-view-all:hover{text-decoration:underline}
.pd-page.rev-page .btn.rev-ref.is-link{font-weight:700;color:#059669;text-decoration:none;border:0;background:transparent;box-shadow:none}
.pd-page.rev-page .btn.rev-ref.is-link:hover{color:#047857;text-decoration:underline}
.pd-page.rev-page .rev-type,
.pd-page.rev-page .rev-status{
    display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:999px;font-size:.72rem;font-weight:700
}
.pd-page.rev-page .rev-type.is-recognized{background:#dcfce7;color:#15803d}
.pd-page.rev-page .rev-type.is-adjustment{background:#f3e8ff;color:#7e22ce}
.pd-page.rev-page .rev-type.is-reversal{background:#fee2e2;color:#b91c1c}
.pd-page.rev-page .rev-status.is-posted,.pd-page.rev-page .rev-status.is-approved{background:#dcfce7;color:#15803d}
.pd-page.rev-page .rev-status.is-pending_review{background:#fef3c7;color:#b45309}
.pd-page.rev-page .pd-amt.is-neg{color:#b91c1c}
.pd-page.rev-page .rev-ref{font-weight:700;color:#0f172a;font-variant-numeric:tabular-nums}
.pd-page.rev-page .rev-ref.is-link{color:#059669;text-decoration:none}
.pd-page.rev-page .rev-muted{font-size:.75rem;color:#94a3b8}
.pd-page.rev-page .pd-table thead th{font-size:.68rem;letter-spacing:.03em}
.pd-page.rev-page .pd-table td .btn-view{
    width:32px;height:32px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#475569;
    display:inline-flex;align-items:center;justify-content:center
}
.pd-page.rev-page .pd-table td .btn-view:hover{border-color:#10b981;color:#059669}
.pd-page.rev-page .pd-table tr.is-selected td{background:#ecfdf5}

.pd-page.rev-page .rev-drawer{overflow:visible}
.pd-page.rev-page .rev-drawer-h{
    display:flex;align-items:flex-start;justify-content:space-between;gap:10px;
    padding:14px 16px;border-bottom:1px solid #eef2f7;background:#fff
}
.pd-page.rev-page .rev-drawer-h h5{margin:0;font-size:.98rem;font-weight:800}
.pd-page.rev-page .rev-drawer-h .rev-ref-pill{
    display:inline-flex;align-items:center;padding:3px 10px;border-radius:999px;background:#ecfdf5;color:#059669;
    font-size:.78rem;font-weight:800;letter-spacing:.01em
}
.pd-page.rev-page .rev-drawer-b{padding:14px 16px}
.pd-page.rev-page .rev-sec{margin-bottom:1.05rem;padding-bottom:1rem;border-bottom:1px solid #f1f5f9}
.pd-page.rev-page .rev-sec:last-child{border-bottom:0;margin-bottom:0;padding-bottom:0}
.pd-page.rev-page .rev-sec h6{
    margin:0 0 10px;font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#64748b;
    display:flex;align-items:center;gap:8px
}
.pd-page.rev-page .rev-sec h6 .sec-ico{
    width:20px;height:20px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:.7rem;flex-shrink:0
}
.pd-page.rev-page .rev-sec h6 .sec-ico.is-green{background:#dcfce7;color:#16a34a}
.pd-page.rev-page .rev-sec h6 .sec-ico.is-blue{background:#dbeafe;color:#2563eb}
.pd-page.rev-page .rev-sec h6 .sec-ico.is-amber{background:#ffedd5;color:#ea580c}
.pd-page.rev-page .rev-sec h6 .sec-ico.is-violet{background:#ede9fe;color:#7c3aed}
.pd-page.rev-page .rev-dl{display:grid;grid-template-columns:118px minmax(0,1fr);gap:7px 10px;font-size:.84rem;align-items:baseline}
.pd-page.rev-page .rev-dl dt{margin:0;color:#94a3b8;font-weight:600}
.pd-page.rev-page .rev-dl dt::after{content:':'}
.pd-page.rev-page .rev-dl dd{margin:0;color:#0f172a;font-weight:700;word-break:break-word}
.pd-page.rev-page .rev-check{
    display:flex;align-items:center;gap:8px;margin-top:8px;padding:8px 10px;border-radius:8px;
    background:#ecfdf5;color:#059669;font-size:.8rem;font-weight:700
}
.pd-page.rev-page .rev-audit{list-style:none;margin:0;padding:0}
.pd-page.rev-page .rev-audit li{position:relative;padding:0 0 14px 16px;border-left:2px solid #e2e8f0;font-size:.8rem}
.pd-page.rev-page .rev-audit li:last-child{padding-bottom:0}
.pd-page.rev-page .rev-audit li::before{
    content:'';position:absolute;left:-5px;top:4px;width:8px;height:8px;border-radius:50%;background:#10b981
}
.pd-page.rev-page .rev-drawer-f{
    display:flex;flex-wrap:wrap;gap:8px;padding:12px 16px;border-top:1px solid #eef2f7;background:#fff
}
.pd-page.rev-page .rev-drawer-empty{
    text-align:center;padding:2.5rem 1.25rem;color:#94a3b8
}
.pd-page.rev-page .rev-drawer-empty i{font-size:1.8rem;display:block;margin-bottom:.6rem;opacity:.55}

.pd-page.rev-page .rev-attr-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
@media (max-width:991.98px){.pd-page.rev-page .rev-attr-grid{grid-template-columns:1fr}}
.pd-page.rev-page .rev-attr-grid .pd-card{margin:0;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.pd-page.rev-page .rev-attr-grid .pd-card-sub{font-size:.75rem;color:#94a3b8;margin-top:2px}
.pd-page.rev-page .rev-attr-table{font-size:.8rem}
.pd-page.rev-page .modal .form-label{font-size:.78rem;font-weight:700;color:#475569}
.pd-page.rev-page .pd-hero-actions .dropdown-menu{min-width:168px;border:1px solid #e2e8f0;border-radius:12px;padding:6px}
.pd-page.rev-page .pd-hero-actions .dropdown-item{border-radius:8px;font-size:.84rem;font-weight:600;padding:.45rem .7rem}
</style>
@endpush

@section('content')
@php
    $periodLabel = trim(($f['date_from'] ?? '') && ($f['date_to'] ?? '')
        ? (\Carbon\Carbon::parse($f['date_from'])->format('d M Y').' – '.\Carbon\Carbon::parse($f['date_to'])->format('d M Y'))
        : ($dateRangeLabel ?? ''));
    $topAttr = [];
    foreach ([
        'employee' => 'Top Employee',
        'branch' => 'Top Branch',
        'cluster' => 'Top Cluster',
        'region' => 'Top Region',
    ] as $key => $label) {
        $row = collect($attr[$key] ?? [])->sortByDesc(fn ($r) => (float) ($r['recognized'] ?? $r['net'] ?? 0))->first();
        $topAttr[] = [
            'attribute' => $label,
            'name' => $row['label'] ?? '—',
            'count' => (int) ($row['disbursements'] ?? $row['count'] ?? 0),
            'disb' => (float) ($row['disbursed'] ?? 0),
            'recognized' => (float) ($row['recognized'] ?? 0),
        ];
    }
@endphp
<div class="container-fluid p-4 pd-page rev-page">
    <div class="pd-hero">
        <div>
            <ul class="pd-crumbs">
                <li><a href="{{ url('/') }}">Home</a></li>
                <li class="sep">›</li>
                <li>Performance & Finance</li>
                <li class="sep">›</li>
                <li>Revenue Ledger</li>
            </ul>
            <h1 class="pd-title">
                <span class="rev-hero-ico"><i class="bi bi-currency-rupee"></i></span>
                Revenue Ledger
            </h1>
            <p class="pd-sub">
                Recognized revenue from verified loan disbursements, attribution and auditable adjustments.
            </p>
        </div>
        <div class="pd-hero-actions">
            <div class="dropdown">
                <button class="pd-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download"></i> Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    @foreach(['csv' => 'CSV', 'xlsx' => 'Excel', 'pdf' => 'PDF'] as $fmt => $label)
                        <li>
                            <a class="dropdown-item" data-no-loader href="{{ route('goal.revenue-ledger.export', array_merge(request()->query(), ['format' => $fmt])) }}" download>
                                {{ $label }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <button type="button" class="pd-link-btn" data-bs-toggle="modal" data-bs-target="#revPolicyModal">
                <i class="bi bi-info-circle"></i> Revenue Policy
            </button>
        </div>
    </div>

    <div class="rev-shell" id="revLayout">
        <div class="rev-left">
            <div class="rev-banner">
                <span class="rev-banner-ico"><i class="bi bi-info-lg"></i></span>
                <div>
                    Revenue is recognized only after <strong>verified disbursement</strong> using approved commercial rules
                    ({{ rtrim(rtrim(number_format($revenueRatePct, 4), '0'), '.') }}%).
                    Payout receipts are tracked separately in Accounts / Payout Management — not in this ledger.
                </div>
            </div>

            <div class="rev-summary-card">
                <div class="rev-kpi-row">
                    <a class="rev-kpi k-green {{ $activeCard === 'recognized' ? 'is-active' : '' }}" href="{{ route('goal.revenue-ledger', $cardQs('recognized')) }}">
                        <span class="rev-kpi-ico" aria-hidden="true"><i class="bi bi-layers-fill"></i></span>
                        <div class="rev-kpi-body">
                            <p class="rev-kpi-label">Recognized Revenue</p>
                            <p class="rev-kpi-value">{{ $fmtInr0($kpis['recognized'] ?? 0) }}</p>
                            <p class="rev-kpi-meta">{{ number_format($kpis['recognized_count'] ?? 0) }} Entries</p>
                        </div>
                    </a>
                    <a class="rev-kpi k-teal {{ $activeCard === 'verified_disb' ? 'is-active' : '' }}" href="{{ route('goal.revenue-ledger', $cardQs('verified_disb')) }}">
                        <span class="rev-kpi-ico" aria-hidden="true"><i class="bi bi-briefcase-fill"></i></span>
                        <div class="rev-kpi-body">
                            <p class="rev-kpi-label">Verified Disbursement Amount</p>
                            <p class="rev-kpi-value">{{ $fmtInr0($kpis['verified_disb'] ?? 0) }}</p>
                            <p class="rev-kpi-meta">{{ number_format($kpis['verified_disb_count'] ?? 0) }} Disbursements</p>
                        </div>
                    </a>
                    <a class="rev-kpi k-amber {{ $activeCard === 'adjustment' ? 'is-active' : '' }}" href="{{ route('goal.revenue-ledger', $cardQs('adjustment')) }}">
                        <span class="rev-kpi-ico" aria-hidden="true"><i class="bi bi-sliders"></i></span>
                        <div class="rev-kpi-body">
                            <p class="rev-kpi-label">Revenue Adjustments</p>
                            <p class="rev-kpi-value">{{ $fmtInr0($kpis['adjustments'] ?? 0) }}</p>
                            <p class="rev-kpi-meta">{{ number_format($kpis['adjustment_count'] ?? 0) }} Entries</p>
                        </div>
                    </a>
                    <a class="rev-kpi k-rose {{ $activeCard === 'reversal' ? 'is-active' : '' }}" href="{{ route('goal.revenue-ledger', $cardQs('reversal')) }}">
                        <span class="rev-kpi-ico" aria-hidden="true"><i class="bi bi-arrow-counterclockwise"></i></span>
                        <div class="rev-kpi-body">
                            <p class="rev-kpi-label">Revenue Reversals</p>
                            <p class="rev-kpi-value">{{ $fmtInr0($kpis['reversals'] ?? 0) }}</p>
                            <p class="rev-kpi-meta">{{ number_format($kpis['reversal_count'] ?? 0) }} Entries</p>
                        </div>
                    </a>
                    <a class="rev-kpi k-violet {{ $activeCard === 'pending' ? 'is-active' : '' }}" href="{{ route('goal.revenue-ledger', $cardQs('pending')) }}">
                        <span class="rev-kpi-ico" aria-hidden="true"><i class="bi bi-clock-fill"></i></span>
                        <div class="rev-kpi-body">
                            <p class="rev-kpi-label">Pending Review</p>
                            <p class="rev-kpi-value">{{ $fmtInr0($kpis['pending'] ?? 0) }}</p>
                            <p class="rev-kpi-meta">{{ number_format($kpis['pending_count'] ?? 0) }} Entries</p>
                        </div>
                    </a>
                </div>

                <form method="GET" action="{{ route('goal.revenue-ledger') }}" class="rev-filters" id="revFiltersForm">
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                    <input type="hidden" name="card" value="{{ $activeCard }}">
                    <input type="hidden" name="date_from" id="revDateFrom" value="{{ $f['date_from'] ?? '' }}">
                    <input type="hidden" name="date_to" id="revDateTo" value="{{ $f['date_to'] ?? '' }}">

                    <div class="rev-filter-row r1">
                        <div class="rf-date">
                            <label for="revDateRange">Date Range</label>
                            <div class="rev-date-wrap">
                                <i class="bi bi-calendar3"></i>
                                <input type="text" id="revDateRange" class="form-control" value="{{ $dateRangeLabel }}" placeholder="Select range" readonly>
                            </div>
                        </div>
                        <div class="rf-fy">
                            <label for="revFy">Financial Year</label>
                            <select name="fy" id="revFy" class="form-select">
                                @foreach(($fy_options ?? []) as $fy)
                                    <option value="{{ $fy }}" @selected(($f['fy'] ?? '') === $fy)>{{ $fy }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rf-field">
                            <label for="revRegion">Region</label>
                            <select name="region" id="revRegion" class="form-select" data-allow-clear="false" data-placeholder="All Regions">
                                <option value="">All Regions</option>
                                @foreach(($opts['regions'] ?? []) as $u)
                                    <option value="{{ $u->id }}" @selected((string)($f['region'] ?? '') === (string)$u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rf-field">
                            <label for="revCluster">Cluster</label>
                            <select name="cluster" id="revCluster" class="form-select" data-allow-clear="false" data-placeholder="All Clusters">
                                <option value="">All Clusters</option>
                                @foreach(($opts['clusters'] ?? []) as $u)
                                    <option value="{{ $u->id }}"
                                        data-parent="{{ $u->parent_id }}"
                                        @selected((string)($f['cluster'] ?? '') === (string)$u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rf-field">
                            <label for="revBranch">Branch</label>
                            <select name="branch" id="revBranch" class="form-select" data-allow-clear="false" data-placeholder="All Branches">
                                <option value="">All Branches</option>
                                @foreach(($opts['branches'] ?? []) as $u)
                                    <option value="{{ $u->id }}"
                                        data-parent="{{ $u->parent_id }}"
                                        @selected((string)($f['branch'] ?? '') === (string)$u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="rev-filter-row r2">
                        <div class="rf-field">
                            <label for="revOwner">Employee / Revenue Owner</label>
                            <select name="owner" id="revOwner" class="form-select">
                                <option value="">All Employees</option>
                                @foreach(($opts['owners'] ?? []) as $o)
                                    <option value="{{ $o->id }}" @selected((string)($f['owner'] ?? '') === (string)$o->id)>{{ $o->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rf-field">
                            <label for="revProduct">Product</label>
                            <select name="product" id="revProduct" class="form-select">
                                <option value="">All Products</option>
                                @foreach(($opts['products'] ?? []) as $p)
                                    <option value="{{ $p }}" @selected(($f['product'] ?? '') === $p)>{{ $p }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rf-field">
                            <label for="revSource">Source</label>
                            <select name="source" id="revSource" class="form-select">
                                <option value="">All Sources</option>
                                @foreach(($opts['sources'] ?? []) as $s)
                                    <option value="{{ $s }}" @selected(($f['source'] ?? '') === $s)>{{ $s }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rf-field">
                            <label for="revType">Entry Type</label>
                            <select name="entry_type" id="revType" class="form-select">
                                <option value="">All Types</option>
                                <option value="recognized" @selected(($f['entry_type'] ?? '') === 'recognized')>Revenue Credit</option>
                                <option value="adjustment" @selected(($f['entry_type'] ?? '') === 'adjustment')>Adjustment</option>
                                <option value="reversal" @selected(($f['entry_type'] ?? '') === 'reversal')>Reversal</option>
                            </select>
                        </div>
                        <div class="rev-filter-actions">
                            <button type="submit" class="pd-btn"><i class="bi bi-funnel"></i> Apply</button>
                            <a href="{{ route('goal.revenue-ledger') }}" class="pd-btn-ghost"><i class="bi bi-arrow-clockwise"></i> Reset</a>
                        </div>
                    </div>
                    <input type="hidden" name="q" id="revQ" value="{{ $f['q'] ?? '' }}">
                </form>
            </div>

            <div class="rev-main-card">
            <div class="rev-tabs">
                <a class="rev-tab {{ $activeTab === 'all' ? 'active' : '' }}" href="{{ route('goal.revenue-ledger', $tabQs('all')) }}">All Entries <span class="n">{{ number_format($tabs['all'] ?? 0) }}</span></a>
                <a class="rev-tab {{ $activeTab === 'recognized' ? 'active' : '' }}" href="{{ route('goal.revenue-ledger', $tabQs('recognized')) }}">Revenue Credits <span class="n">{{ number_format($tabs['recognized'] ?? 0) }}</span></a>
                <a class="rev-tab {{ $activeTab === 'adjustment' ? 'active' : '' }}" href="{{ route('goal.revenue-ledger', $tabQs('adjustment')) }}">Adjustments <span class="n">{{ number_format($tabs['adjustment'] ?? 0) }}</span></a>
                <a class="rev-tab {{ $activeTab === 'reversal' ? 'active' : '' }}" href="{{ route('goal.revenue-ledger', $tabQs('reversal')) }}">Reversals <span class="n">{{ number_format($tabs['reversal'] ?? 0) }}</span></a>
            </div>

            <div class="rev-toolbar">
                <div class="rev-muted">Showing ledger rows in your org scope · Net {{ $fmtInr($kpis['net'] ?? 0) }}</div>
                <div class="rev-toolbar-actions">
                    @if($canAdjust)
                        <button type="button" class="rev-btn-outline" data-bs-toggle="modal" data-bs-target="#revAdjustModal" data-mode="adjustment">
                            <i class="bi bi-plus-lg"></i> Add Adjustment
                        </button>
                        <button type="button" class="rev-btn-outline-danger" data-bs-toggle="modal" data-bs-target="#revAdjustModal" data-mode="reversal">
                            <i class="bi bi-arrow-counterclockwise"></i> Reverse Entry
                        </button>
                    @endif
                </div>
            </div>

            <div class="pd-table-wrap">
                <table class="pd-table align-middle" id="revLedgerTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Entry ID</th>
                            <th>Disbursement Ref</th>
                            <th>Case Ref</th>
                            <th>Customer</th>
                            <th>Disbursement Date</th>
                            <th class="text-end">Disbursed Amount (₹)</th>
                            <th class="text-end">Recognized Revenue (₹)</th>
                            <th>Revenue Owner</th>
                            <th>Branch</th>
                            <th>Entry Type</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($entries as $i => $e)
                        @php
                            $etype = $e->entry_type ?: 'recognized';
                            $amt = (float) $e->amount;
                            $disbAmt = (float) ($e->disbursement?->amount ?? 0);
                            $disbRef = $e->disbursement ? ('DSB-'.str_pad((string)$e->disbursement->id, 4, '0', STR_PAD_LEFT)) : '—';
                            $statusKey = $e->status ?: 'posted';
                        @endphp
                        <tr data-entry-id="{{ $e->id }}">
                            <td data-label="#">{{ ($entries->firstItem() ?? 1) + $i }}</td>
                            <td data-label="Entry">
                                <button type="button" class="rev-ref is-link btn btn-link p-0 js-rev-view" data-id="{{ $e->id }}">{{ $e->entry_ref ?: ('RV-'.$e->id) }}</button>
                            </td>
                            <td data-label="Disb">{{ $disbRef }}</td>
                            <td data-label="Case">
                                <a class="pd-case" href="{{ route('goal.cases.show', $e->goal_loan_case_id) }}">{{ $e->loanCase?->case_no ?: '—' }}</a>
                            </td>
                            <td data-label="Customer"><span class="pd-cust">{{ $e->loanCase?->customer_name ?: '—' }}</span></td>
                            <td data-label="Disb Date">{{ optional($e->disbursement?->disbursed_on ?? $e->disbursement?->verified_at)->format('d M Y') ?: '—' }}</td>
                            <td data-label="Disbursed" class="pd-amt text-end">{{ $disbAmt > 0 ? $fmtInr($disbAmt) : '—' }}</td>
                            <td data-label="Revenue" class="pd-amt text-end {{ $amt < 0 ? 'is-neg' : '' }}">{{ $fmtInr($amt) }}</td>
                            <td data-label="Owner">{{ $e->attributedUser?->name ?: '—' }}</td>
                            <td data-label="Branch">{{ $e->orgUnit?->name ?: '—' }}</td>
                            <td data-label="Type"><span class="rev-type is-{{ $etype === '' ? 'recognized' : $etype }}">{{ $e->typeLabel() }}</span></td>
                            <td data-label="Status"><span class="rev-status is-{{ $statusKey }}">{{ $e->statusLabel() }}</span></td>
                            <td data-label="Actions">
                                <button type="button" class="btn-view js-rev-view" data-id="{{ $e->id }}" title="View details">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="pd-col-empty">
                                <div class="pd-empty">
                                    <i class="bi bi-journal-text"></i>
                                    <div style="font-weight:700;color:#334155;margin-bottom:4px">No revenue entries for these filters</div>
                                    <div>Revenue credits appear when a disbursement is verified (PD-01).</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($entries->hasPages())
                <div class="pd-foot">
                    <span>Showing {{ $entries->firstItem() }} to {{ $entries->lastItem() }} of {{ $entries->total() }} entries</span>
                    <div>{{ $entries->links() }}</div>
                </div>
            @endif
            </div>

            <div class="rev-attr-grid">
                <div class="pd-card">
                    <div class="pd-card-head">
                        <div class="rev-attr-head">
                            <div>
                                <h5>Revenue Attribution</h5>
                                <div class="pd-card-sub">Period: {{ $periodLabel ?: 'Selected range' }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="pd-table-wrap">
                        <table class="pd-table rev-attr-table">
                            <thead>
                                <tr>
                                    <th>Attribute</th>
                                    <th>Count</th>
                                    <th class="text-end">Disbursement Amount (₹)</th>
                                    <th class="text-end">Recognized Revenue (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topAttr as $row)
                                    <tr>
                                        <td>
                                            <div style="font-weight:700">{{ $row['attribute'] }}</div>
                                            <div class="rev-muted">{{ $row['name'] }}</div>
                                        </td>
                                        <td>{{ number_format($row['count']) }}</td>
                                        <td class="text-end">{{ $fmtInr0($row['disb']) }}</td>
                                        <td class="text-end"><strong>{{ $fmtInr0($row['recognized']) }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="pd-card">
                    <div class="pd-card-head">
                        <div class="rev-attr-head">
                            <div>
                                <h5>Recent Adjustments</h5>
                                <div class="pd-card-sub">Last 5</div>
                            </div>
                            <a class="rev-view-all" href="{{ route('goal.revenue-ledger', array_merge(request()->query(), ['tab' => 'adjustment', 'entry_type' => 'adjustment', 'card' => 'adjustment', 'page' => null])) }}">View All</a>
                        </div>
                    </div>
                    <div class="pd-table-wrap">
                        <table class="pd-table rev-attr-table">
                            <thead>
                                <tr>
                                    <th>Entry ID</th>
                                    <th>Case Ref</th>
                                    <th class="text-end">Amount (₹)</th>
                                    <th>Reason</th>
                                    <th>Approved By</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($recent_adjustments ?? []) as $ra)
                                    <tr>
                                        <td><button type="button" class="rev-ref is-link btn btn-link p-0 js-rev-view" data-id="{{ $ra->id }}">{{ $ra->entry_ref ?: ('RV-'.$ra->id) }}</button></td>
                                        <td>{{ $ra->loanCase?->case_no ?: '—' }}</td>
                                        <td class="text-end {{ (float)$ra->amount < 0 ? 'pd-amt is-neg' : '' }}">{{ $fmtInr($ra->amount) }}</td>
                                        <td>{{ \Illuminate\Support\Str::limit($ra->reason ?: $ra->note, 40) }}</td>
                                        <td>{{ $ra->approver?->name ?: $ra->creator?->name ?: '—' }}</td>
                                        <td>{{ optional($ra->posted_at ?? $ra->created_at)->format('d M Y') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-3">No adjustments posted yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <aside class="rev-right">
            <div class="rev-drawer" id="revDrawer" aria-live="polite">
                <div class="rev-drawer-h">
                    <div>
                        <div class="rev-muted">Ledger Entry Details</div>
                        <h5><span class="rev-ref-pill" id="rdRef">Select an entry</span></h5>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="rev-status is-posted" id="rdStatus" hidden>Posted</span>
                    </div>
                </div>
                <div class="rev-drawer-b" id="rdBody">
                    <div class="rev-drawer-empty">
                        <i class="bi bi-journal-text"></i>
                        Select a ledger row to view case, disbursement, attribution and audit history.
                    </div>
                </div>
                <div class="rev-drawer-f" id="rdFooter" hidden></div>
            </div>
        </aside>
    </div>
</div>

{{-- Revenue Policy info --}}
<div class="modal fade" id="revPolicyModal" tabindex="-1" aria-labelledby="revPolicyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="revPolicyModalLabel">Revenue Policy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Revenue is recognized only after <strong>verified disbursement</strong> (PD-01).</p>
                <ul class="mb-0 ps-3">
                    <li>Default commercial rate: <strong>{{ rtrim(rtrim(number_format($revenueRatePct, 4), '0'), '.') }}%</strong> of verified disbursed amount.</li>
                    <li>Attribution follows Employee → Branch → Cluster → Region (REV-02).</li>
                    <li>Adjustments and reversals create separate ledger rows; original credits are never overwritten.</li>
                    <li>Payout receipts are tracked in Accounts / Payout Management — not in this ledger.</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="pd-btn" data-bs-dismiss="modal">Got it</button>
            </div>
        </div>
    </div>
</div>

@if($canAdjust)
<div class="modal fade" id="revAdjustModal" tabindex="-1" aria-labelledby="revAdjustModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ route('goal.revenue-ledger.adjust') }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <input type="hidden" name="entry_type" id="adjEntryType" value="adjustment">
            <div class="modal-header">
                <h5 class="modal-title" id="revAdjustModalLabel">Add Adjustment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3" id="adjHelp">
                    Creates a separate ledger entry. The original revenue credit is never overwritten.
                </p>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="adjParent">Original Entry <span class="text-danger">*</span></label>
                        <select name="parent_entry_id" id="adjParent" class="form-select" required>
                            <option value="">Select recognized credit…</option>
                            @foreach(($credit_options ?? []) as $c)
                                <option value="{{ $c->id }}"
                                    data-case="{{ $c->loanCase?->case_no }}"
                                    data-remaining="{{ (float) $c->amount }}">
                                    {{ $c->entry_ref ?: ('RV-'.$c->id) }} · {{ $c->loanCase?->case_no }} · {{ $fmtInr($c->amount) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Case Reference</label>
                        <input type="text" class="form-control" id="adjCaseRef" readonly placeholder="Auto from entry">
                    </div>
                    <div class="col-md-4" id="adjDirWrap">
                        <label class="form-label" for="adjDir">Adjustment Type</label>
                        <select name="adjustment_direction" id="adjDir" class="form-select">
                            <option value="increase">Increase</option>
                            <option value="decrease">Decrease</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="adjAmt">Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="adjAmt" class="form-control" required>
                        <div class="form-text" id="adjRemainHint"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="adjDate">Date</label>
                        <input type="date" class="form-control" id="adjDate" value="{{ now()->toDateString() }}" disabled>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="adjNote">Reason <span class="text-danger">*</span></label>
                        <input type="text" name="note" id="adjNote" class="form-control" required maxlength="500" placeholder="Mandatory explanation for audit">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="adjEvidence">Supporting Evidence</label>
                        <input type="file" name="evidence" id="adjEvidence" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="adjRemarks">Remarks</label>
                        <input type="text" name="remarks" id="adjRemarks" class="form-control" maxlength="500">
                    </div>
                </div>
                @error('amount')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                @error('note')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                @error('parent_entry_id')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div>
            <div class="modal-footer">
                <button type="button" class="pd-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="pd-btn" id="adjSubmitBtn">Post Adjustment</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    const fyRanges = @json($fy_range_map ?? []);
    const entryUrlTpl = @json(route('goal.revenue-ledger.entry', ['entry' => '__ID__']));
    const fromEl = document.getElementById('revDateFrom');
    const toEl = document.getElementById('revDateTo');
    const rangeEl = document.getElementById('revDateRange');
    const fyEl = document.getElementById('revFy');
    let fp = null;

    function applyFy(key) {
        const range = fyRanges[key];
        if (!range || !fromEl || !toEl) return;
        fromEl.value = range.date_from || '';
        toEl.value = range.date_to || '';
        if (fp) {
            try { fp.setDate([range.date_from, range.date_to], true); } catch (e) {}
        }
    }
    if (fyEl) fyEl.addEventListener('change', function () { applyFy(fyEl.value); });

    // Region → Cluster → Branch cascade (keeps Select2 in sync)
    (function () {
        const regionEl = document.getElementById('revRegion');
        const clusterEl = document.getElementById('revCluster');
        const branchEl = document.getElementById('revBranch');
        if (!regionEl || !clusterEl || !branchEl) return;

        const clusterAll = Array.from(clusterEl.querySelectorAll('option')).map(function (o) {
            return { value: o.value, label: o.textContent, parent: o.getAttribute('data-parent') || '' };
        });
        const branchAll = Array.from(branchEl.querySelectorAll('option')).map(function (o) {
            return { value: o.value, label: o.textContent, parent: o.getAttribute('data-parent') || '' };
        });

        function refreshSelect(el) {
            if (!(window.jQuery && window.jQuery.fn.select2)) return;
            var $el = window.jQuery(el);
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
            if (window.IsarvaSelect2 && typeof window.IsarvaSelect2.initOne === 'function') {
                window.IsarvaSelect2.initOne(el);
            } else {
                $el.select2({ width: '100%', allowClear: false, minimumResultsForSearch: Infinity });
            }
        }

        function fill(select, rows, selected) {
            const keep = selected || '';
            select.innerHTML = '';
            rows.forEach(function (r) {
                const opt = document.createElement('option');
                opt.value = r.value;
                opt.textContent = r.label;
                if (r.parent) opt.setAttribute('data-parent', r.parent);
                if (String(r.value) === String(keep)) opt.selected = true;
                select.appendChild(opt);
            });
            refreshSelect(select);
        }

        function syncClusters(keepBranch) {
            const regionId = regionEl.value;
            const rows = clusterAll.filter(function (r) {
                return r.value === '' || !regionId || String(r.parent) === String(regionId);
            });
            const keep = rows.some(function (r) { return String(r.value) === String(clusterEl.value); }) ? clusterEl.value : '';
            fill(clusterEl, rows, keep);
            syncBranches(keepBranch);
        }

        function syncBranches(forceKeep) {
            const clusterId = clusterEl.value;
            const regionId = regionEl.value;
            const allowedClusterIds = clusterAll
                .filter(function (r) { return r.value && (!regionId || String(r.parent) === String(regionId)); })
                .map(function (r) { return String(r.value); });
            const rows = branchAll.filter(function (r) {
                if (r.value === '') return true;
                if (clusterId) return String(r.parent) === String(clusterId);
                if (regionId) return allowedClusterIds.indexOf(String(r.parent)) !== -1;
                return true;
            });
            const keep = forceKeep || (rows.some(function (r) { return String(r.value) === String(branchEl.value); }) ? branchEl.value : '');
            fill(branchEl, rows, keep);
        }

        regionEl.addEventListener('change', function () { syncClusters(''); });
        clusterEl.addEventListener('change', function () { syncBranches(''); });
        // Run after global Select2 init so rebuild keeps widgets in sync
        function bootCascade() { syncClusters(branchEl.value); }
        if (window.jQuery) {
            window.jQuery(bootCascade);
        } else {
            bootCascade();
        }
    })();

    if (rangeEl && window.flatpickr) {
        const defaults = [];
        if (fromEl && fromEl.value) defaults.push(fromEl.value);
        if (toEl && toEl.value) defaults.push(toEl.value);
        fp = flatpickr(rangeEl, {
            mode: 'range', dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y',
            defaultDate: defaults.length ? defaults : null,
            onChange: function (dates, _s, inst) {
                if (!fromEl || !toEl) return;
                fromEl.value = dates[0] ? inst.formatDate(dates[0], 'Y-m-d') : '';
                toEl.value = dates[1] ? inst.formatDate(dates[1], 'Y-m-d') : (dates[0] ? inst.formatDate(dates[0], 'Y-m-d') : '');
            }
        });
    }

    const rdBody = document.getElementById('rdBody');
    const rdFooter = document.getElementById('rdFooter');
    const rdRef = document.getElementById('rdRef');
    const rdStatus = document.getElementById('rdStatus');
    const money = function (n) {
        const v = Number(n) || 0;
        const neg = v < 0;
        const s = '₹ ' + Math.abs(v).toLocaleString('en-IN', { maximumFractionDigits: 0 });
        return neg ? '-' + s : s;
    };
    const esc = function (v) {
        return String(v == null ? '' : v)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    };

    function loadEntry(id, row) {
        if (!id) return;
        document.querySelectorAll('#revLedgerTable tr.is-selected').forEach(function (tr) { tr.classList.remove('is-selected'); });
        if (row) row.classList.add('is-selected');
        rdBody.innerHTML = '<div class="rev-drawer-empty"><i class="bi bi-hourglass-split"></i>Loading entry…</div>';
        if (rdStatus) rdStatus.hidden = true;
        fetch(entryUrlTpl.replace('__ID__', id), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); }).then(function (j) {
            if (j && j.entry) renderDetail(j.entry);
            else rdBody.innerHTML = '<div class="text-danger small">Unable to load entry.</div>';
        }).catch(function () {
            rdBody.innerHTML = '<div class="text-danger small">Unable to load entry.</div>';
        });
    }

    function renderDetail(entry) {
        if (!entry) return;
        rdRef.textContent = entry.entry_ref || '—';
        if (rdStatus) {
            rdStatus.hidden = false;
            rdStatus.textContent = entry.status || 'Posted';
            rdStatus.className = 'rev-status is-' + (entry.status_key || 'posted');
        }
        const c = entry.case || {};
        const d = entry.disbursement || {};
        const r = entry.revenue || {};
        const a = entry.attribution || {};
        const t = entry.target || {};
        const audits = entry.audit || [];
        const typeKey = entry.entry_type_key || 'recognized';
        const typeText = (typeKey === 'recognized' || typeKey === '')
            ? 'Revenue Credit'
            : (entry.entry_type || r.type || 'Credit');
        const typeBadge = '<span class="rev-type is-' + esc(typeKey || 'recognized') + '">' + esc(typeText) + '</span>';
        const statusBadge = '<span class="rev-status is-' + esc(entry.status_key || 'posted') + '">' + esc(entry.status || 'Posted') + '</span>';

        rdBody.innerHTML =
            '<div class="rev-sec"><h6><span class="sec-ico is-green"><i class="bi bi-journal-text"></i></span>Ledger Entry Details</h6><dl class="rev-dl">' +
            '<dt>Case Ref</dt><dd>' + esc(c.ref || '—') + '</dd>' +
            '<dt>Customer</dt><dd>' + esc(c.customer || '—') + '</dd>' +
            '<dt>Product</dt><dd>' + esc(c.product || '—') + '</dd>' +
            '<dt>Branch</dt><dd>' + esc(c.branch || '—') + '</dd>' +
            '<dt>Disbursement Ref</dt><dd>' + esc(d.ref || '—') + '</dd>' +
            '<dt>Disbursement Date</dt><dd>' + esc(d.date || '—') + '</dd>' +
            '<dt>Entry Type</dt><dd>' + typeBadge + '</dd>' +
            '<dt>Status</dt><dd>' + statusBadge + '</dd></dl></div>' +

            '<div class="rev-sec"><h6><span class="sec-ico is-blue"><i class="bi bi-briefcase"></i></span>Verified Disbursement Details</h6><dl class="rev-dl">' +
            '<dt>Disbursed Amount</dt><dd>' + money(d.amount) + '</dd>' +
            '<dt>Verified On</dt><dd>' + esc(d.verified_on || d.date || '—') + '</dd>' +
            '<dt>Verified By</dt><dd>' + esc(d.verified_by || (d.verified ? 'Sales Team' : '—')) + '</dd></dl></div>' +

            '<div class="rev-sec"><h6><span class="sec-ico is-amber"><i class="bi bi-file-earmark-text"></i></span>Commercial &amp; Revenue Basis</h6><dl class="rev-dl">' +
            '<dt>Commercial Policy Ref</dt><dd>' + esc(r.policy || '—') + '</dd>' +
            '<dt>Revenue Basis</dt><dd>' + esc(r.basis || 'Per approved commercial terms') + '</dd>' +
            '<dt>Remarks</dt><dd>' + esc(r.remarks || entry.reason || '—') + '</dd></dl></div>' +

            '<div class="rev-sec"><h6><span class="sec-ico is-violet"><i class="bi bi-people"></i></span>Revenue Attribution</h6><dl class="rev-dl">' +
            '<dt>Revenue Owner</dt><dd>' + esc(a.owner || '—') + '</dd>' +
            '<dt>Attribution</dt><dd>' + esc(a.mode || 'Team') + '</dd>' +
            '<dt>Cluster</dt><dd>' + esc(a.cluster || '—') + '</dd>' +
            '<dt>Region</dt><dd>' + esc(a.region || '—') + '</dd></dl></div>' +

            '<div class="rev-sec"><h6><span class="sec-ico is-green"><i class="bi bi-bullseye"></i></span>Linked Target Achievement</h6><dl class="rev-dl">' +
            '<dt>Target Period</dt><dd>' + esc(t.period || '—') + '</dd>' +
            '<dt>Revenue Contribution</dt><dd>' + money(t.contribution) + '</dd>' +
            '<dt>Target Credited To</dt><dd>' + esc(t.credited_to || a.owner || '—') + '</dd></dl>' +
            (t.included
                ? '<div class="rev-check"><i class="bi bi-check-circle-fill"></i> Included in target achievement</div>'
                : '<div class="rev-muted mt-2">Pending inclusion in target achievement</div>') +
            '</div>' +

            '<div class="rev-sec"><h6><span class="sec-ico is-blue"><i class="bi bi-clock-history"></i></span>Audit Timeline</h6>' +
            (audits.length
                ? ('<ul class="rev-audit">' + audits.map(function (x) {
                    return '<li><strong>' + esc(x.event || '') + '</strong><div class="rev-muted">' + esc(x.actor || 'System') + ' · ' + esc(x.when || '') + '</div>' +
                        (x.body ? ('<div>' + esc(x.body) + '</div>') : '') + '</li>';
                }).join('') + '</ul>')
                : '<div class="rev-muted">No audit events linked yet.</div>') +
            '</div>';

        let foot = '';
        if (c.url) foot += '<a class="pd-btn-ghost" href="' + c.url + '"><i class="bi bi-folder2-open"></i> View Case</a>';
        if (d.id && c.url) foot += '<a class="pd-btn-ghost" href="' + c.url + '#tab-disbursement"><i class="bi bi-cash-coin"></i> View Disbursement</a>';
        if (c.url) foot += '<a class="pd-btn-ghost" href="' + c.url + '#tab-audit"><i class="bi bi-clock-history"></i> View Audit</a>';
        rdFooter.innerHTML = foot;
        rdFooter.hidden = !foot;
    }

    document.querySelectorAll('.js-rev-view').forEach(function (btn) {
        btn.addEventListener('click', function () {
            loadEntry(btn.getAttribute('data-id'), btn.closest('tr'));
        });
    });

    // Auto-open first ledger row so the detail pane matches the mockup layout.
    const firstView = document.querySelector('#revLedgerTable .js-rev-view');
    if (firstView) {
        loadEntry(firstView.getAttribute('data-id'), firstView.closest('tr'));
    }

    const modal = document.getElementById('revAdjustModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function (ev) {
            const btn = ev.relatedTarget;
            const mode = (btn && btn.getAttribute('data-mode')) || 'adjustment';
            const typeEl = document.getElementById('adjEntryType');
            const title = document.getElementById('revAdjustModalLabel');
            const submit = document.getElementById('adjSubmitBtn');
            const dirWrap = document.getElementById('adjDirWrap');
            const help = document.getElementById('adjHelp');
            if (typeEl) typeEl.value = mode;
            if (mode === 'reversal') {
                if (title) title.textContent = 'Reverse Entry';
                if (submit) submit.textContent = 'Post Reversal';
                if (dirWrap) dirWrap.style.display = 'none';
                if (help) help.textContent = 'Creates a separate negative ledger entry linked to the original credit. Original amount is retained.';
            } else {
                if (title) title.textContent = 'Add Adjustment';
                if (submit) submit.textContent = 'Post Adjustment';
                if (dirWrap) dirWrap.style.display = '';
                if (help) help.textContent = 'Creates a separate ledger entry. The original revenue credit is never overwritten.';
            }
        });
        const parent = document.getElementById('adjParent');
        const caseRef = document.getElementById('adjCaseRef');
        const remain = document.getElementById('adjRemainHint');
        if (parent) {
            parent.addEventListener('change', function () {
                const opt = parent.options[parent.selectedIndex];
                if (caseRef) caseRef.value = opt ? (opt.getAttribute('data-case') || '') : '';
                const rem = opt ? opt.getAttribute('data-remaining') : '';
                if (remain) remain.textContent = rem ? ('Reversible remaining: ₹ ' + Number(rem).toLocaleString('en-IN')) : '';
            });
        }
    }
})();
</script>
@endpush
