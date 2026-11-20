@extends('layouts.app')

@php
    $f = $filters ?? ($report['filters'] ?? []);
    $opts = $filterOptions ?? [];
    $key = $report['key'] ?? 'branch_mis';
    $columns = $report['columns'] ?? [];
    $rows = $report['rows'] ?? [];
    $charts = $report['charts'] ?? [];
    $linkKey = $report['link_key'] ?? null;
    $dateFromLabel = !empty($f['date_from']) ? \Carbon\Carbon::parse($f['date_from'])->format('d M Y') : '';
    $dateToLabel = !empty($f['date_to']) ? \Carbon\Carbon::parse($f['date_to'])->format('d M Y') : '';
    $dateRangeLabel = trim($dateFromLabel.($dateFromLabel && $dateToLabel ? ' – ' : '').$dateToLabel);
    $exportQs = array_filter([
        'fy' => $f['fy'] ?? null,
        'date_from' => $f['date_from'] ?? null,
        'date_to' => $f['date_to'] ?? null,
        'region' => $f['region'] ?? null,
        'cluster' => $f['cluster'] ?? null,
        'branch' => $f['branch'] ?? null,
        'product' => $f['product'] ?? null,
        'owner' => $f['owner'] ?? null,
    ], fn ($v) => $v !== null && $v !== '');
    $kpiTone = [
        'blue' => 't-blue', 'teal' => 't-teal', 'amber' => 't-amber', 'green' => 't-green',
        'indigo' => 't-indigo', 'rose' => 't-rose', 'orange' => 't-orange', 'slate' => 't-slate', 'purple' => 't-purple',
    ];
    $kpiIcons = [
        'blue' => 'bi-people-fill', 'teal' => 'bi-person-check-fill', 'amber' => 'bi-hourglass-split',
        'green' => 'bi-check-circle-fill', 'indigo' => 'bi-graph-up-arrow', 'rose' => 'bi-x-circle-fill',
        'orange' => 'bi-exclamation-triangle-fill', 'slate' => 'bi-layers-fill', 'purple' => 'bi-trophy-fill',
    ];
    $reportHeroIcons = [
        'lead_performance' => 'bi-people-fill',
        'loan_processing' => 'bi-kanban-fill',
        'visit_followup' => 'bi-geo-alt-fill',
        'exception_report' => 'bi-exclamation-octagon-fill',
        'case_summary' => 'bi-folder2-open',
        'disbursement_summary' => 'bi-cash-stack',
        'revenue_ledger' => 'bi-receipt',
        'payout_ageing' => 'bi-clock-history',
        'branch_mis' => 'bi-building',
        'target_vs_achievement' => 'bi-bullseye',
        'hierarchy_performance' => 'bi-diagram-3-fill',
        'lender_performance' => 'bi-bank2',
    ];
    $heroIcon = $reportHeroIcons[$key] ?? 'bi-bar-chart-line-fill';
    $badgeKeys = [
        'status', 'priority', 'type', 'outcome', 'metric',
        'payout_status', 'payment_status', 'achievement', 'conversion', 'approval_rate',
    ];
    $iconKeys = [
        'mobile' => 'bi-telephone', 'source' => 'bi-broadcast', 'product' => 'bi-box-seam',
        'branch' => 'bi-building', 'region' => 'bi-globe2', 'cluster' => 'bi-diagram-2',
        'received' => 'bi-calendar-event', 'created' => 'bi-calendar-plus', 'date' => 'bi-calendar3',
        'due' => 'bi-calendar-x', 'disb_date' => 'bi-calendar-check', 'period' => 'bi-calendar-range',
        'follow_up' => 'bi-alarm', 'scheduled' => 'bi-calendar2-week', 'completed' => 'bi-calendar-check',
        'age' => 'bi-hourglass', 'ageing' => 'bi-hourglass-split', 'processing_days' => 'bi-stopwatch',
        'pending_days' => 'bi-clock-history', 'owner' => 'bi-person', 'assigned_bm' => 'bi-person-badge',
        'employee' => 'bi-person', 'lender' => 'bi-bank', 'customer' => 'bi-person-vcard',
        'lead_name' => 'bi-person', 'reference' => 'bi-hash', 'case_id' => 'bi-folder',
        'lead_ref' => 'bi-bookmark', 'case_ref' => 'bi-folder2-open', 'activity_id' => 'bi-activity',
        'ledger_id' => 'bi-receipt', 'payout_id' => 'bi-wallet2', 'pending_reason' => 'bi-chat-left-text',
        'hold_reason' => 'bi-slash-circle', 'remarks' => 'bi-chat-square-text', 'note' => 'bi-sticky',
        'outcome' => 'bi-chat-dots',
    ];
    $moneyKeys = [
        'requested', 'sanction', 'sanction_amt', 'disbursed', 'disbursed_amount', 'disb_amt',
        'gross', 'expected', 'received', 'pending', 'target', 'achieved', 'remaining',
        'login_amount',
    ];
    $countKeys = [
        'leads', 'allocated', 'cases', 'bank_login', 'sanctioned', 'rejected',
        'applications', 'disbursed',
    ];
    $statusBadge = function ($raw, $key = '') {
        $rawStr = trim((string) $raw);
        $v = strtolower($rawStr);
        if ($v === '' || $v === '—' || $v === '-') {
            return 'b-slate';
        }
        if (in_array($key, ['achievement', 'conversion', 'approval_rate'], true) || str_ends_with($v, '%')) {
            $n = (float) preg_replace('/[^0-9.\-]/', '', $rawStr);
            if ($n >= 100) {
                return 'b-green';
            }
            if ($n >= 70) {
                return 'b-teal';
            }
            if ($n >= 40) {
                return 'b-amber';
            }

            return 'b-rose';
        }
        if (str_contains($v, 'disburs') || str_contains($v, 'complet') || str_contains($v, 'paid') || str_contains($v, 'verified') || str_contains($v, 'converted') || str_contains($v, 'sanction') || str_contains($v, 'interested') || str_contains($v, 'recognized') || str_contains($v, 'eligible') || $v === 'high' || str_contains($v, 'above') || str_contains($v, 'active')) {
            return 'b-green';
        }
        if (str_contains($v, 'reject') || str_contains($v, 'lost') || str_contains($v, 'missed') || str_contains($v, 'hold') || str_contains($v, 'overdue') || str_contains($v, 'revers') || $v === 'critical' || str_contains($v, 'below') || str_contains($v, 'cancel')) {
            return 'b-rose';
        }
        if (str_contains($v, 'query') || str_contains($v, 'pending') || str_contains($v, 'draft') || str_contains($v, 'open') || str_contains($v, 'unalloc') || str_contains($v, 'adjust') || $v === 'medium' || str_contains($v, 'process') || str_contains($v, 'partial')) {
            return 'b-amber';
        }
        if (str_contains($v, 'login') || str_contains($v, 'allocat') || str_contains($v, 'new') || str_contains($v, 'visit') || str_contains($v, 'call') || str_contains($v, 'follow') || $v === 'low') {
            return 'b-blue';
        }
        if (str_contains($v, 'pms') || str_contains($v, 'ready') || str_contains($v, 'schedule')) {
            return 'b-teal';
        }
        if (str_contains($v, 'lead') || str_contains($v, 'disbursement') || str_contains($v, 'revenue')) {
            return 'b-indigo';
        }

        return 'b-slate';
    };
@endphp

@push('styles')
@include('goal.partials.pd_hub_styles')
<style>
.mis-show{color:#1e293b}
.mis-show.container-fluid{padding:.85rem 1rem 1.15rem !important}
.mis-show .ms-hero{
    display:flex;flex-wrap:nowrap;align-items:center;justify-content:space-between;
    gap:.75rem 1rem;margin-bottom:.7rem;width:100%
}
.mis-show .ms-hero-left{display:flex;align-items:flex-start;gap:12px;min-width:0;flex:1}
.mis-show .ms-ico{
    width:42px;height:42px;border-radius:10px;flex:0 0 auto;margin-top:1px;
    display:inline-flex;align-items:center;justify-content:center;
    background:var(--theme-primary);color:#fff;font-size:1.2rem;
    box-shadow:0 6px 14px var(--theme-primary-ring)
}
.mis-show .ms-title{margin:0;font-size:1.35rem;font-weight:800;color:#0f172a;letter-spacing:-.02em;line-height:1.2}
.mis-show .ms-sub{margin:.25rem 0 0;color:#64748b;font-size:.84rem;line-height:1.4;max-width:42rem}
.mis-show .ms-crumbs{display:flex;flex-wrap:wrap;align-items:center;gap:5px;margin:.4rem 0 0;padding:0;list-style:none;font-size:.75rem;color:#64748b}
.mis-show .ms-crumbs a{color:var(--theme-primary);text-decoration:none;font-weight:600}
.mis-show .ms-crumbs .sep{opacity:.45}
.mis-show .ms-meta{display:flex;flex-wrap:wrap;gap:8px 14px;margin-top:.45rem;font-size:.78rem;color:#64748b;font-weight:600}
.mis-show .ms-meta span{display:inline-flex;align-items:center;gap:5px}
.mis-show .ms-meta i{color:var(--theme-primary);opacity:.85}
.mis-show .ms-actions{display:flex;flex-wrap:nowrap;gap:8px;align-items:center;margin-left:auto;flex:0 0 auto}
.mis-show .ms-btn{
    height:36px;padding:0 12px;border-radius:8px;font-size:.8rem;font-weight:700;
    display:inline-flex;align-items:center;gap:6px;text-decoration:none;background:#fff;
    border:1.5px solid var(--theme-primary);color:var(--theme-primary);cursor:pointer
}
.mis-show .ms-btn:hover{background:rgba(0,0,0,.03);color:var(--theme-primary)}
.mis-show .ms-btn.primary{border:0;background:var(--theme-primary);color:#fff;box-shadow:0 4px 12px var(--theme-primary-ring)}
.mis-show .ms-btn.primary:hover{color:#fff;filter:brightness(1.05)}

.mis-show .ms-filters{
    display:flex;flex-wrap:nowrap;align-items:flex-end;gap:8px;width:100%;
    margin-bottom:.7rem;padding:10px 12px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;
    box-shadow:0 2px 8px rgba(148,163,184,.06);box-sizing:border-box
}
.mis-show .ms-filters > div{min-width:0;display:flex;flex-direction:column;justify-content:flex-end}
.mis-show .ms-filters > .ms-f-fy{flex:0 1 118px}
.mis-show .ms-filters > .ms-f-date{flex:1 1 200px;max-width:240px}
.mis-show .ms-filters > .ms-f-region,
.mis-show .ms-filters > .ms-f-cluster,
.mis-show .ms-filters > .ms-f-branch,
.mis-show .ms-filters > .ms-f-product{flex:1 1 0;min-width:108px}
.mis-show .ms-filters label{
    display:block;font-size:.65rem;font-weight:700;color:#64748b;margin:0 0 4px;
    text-transform:uppercase;letter-spacing:.03em;line-height:1;height:12px
}
.mis-show .ms-filters .form-control,.mis-show .ms-filters .form-select,
.mis-show .ms-filters .flatpickr-input{
    width:100%;min-height:36px !important;height:36px !important;border-radius:8px;border:1px solid #d1d5db;
    font-size:.8rem;background:#fff;box-sizing:border-box;padding:.35rem .65rem
}
.mis-show .ms-date-wrap{position:relative;width:100%}
.mis-show .ms-date-wrap .bi-calendar3{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;z-index:2;pointer-events:none}
.mis-show .ms-date-wrap .form-control,.mis-show .ms-date-wrap .flatpickr-input{padding-left:32px !important}
.mis-show .ms-date-wrap .flatpickr-wrapper{width:100%;display:block}
.mis-show .ms-filter-actions{flex:0 0 auto;display:flex;flex-direction:column;justify-content:flex-end}
.mis-show .ms-filter-actions-row{display:flex;gap:6px;align-items:center;height:36px;white-space:nowrap}
.mis-show .ms-btn-apply{
    height:36px;padding:0 14px;border-radius:8px;border:0;background:var(--theme-primary);color:#fff;
    font-size:.8rem;font-weight:700;display:inline-flex;align-items:center;gap:6px;
    box-shadow:0 4px 10px var(--theme-primary-ring)
}
.mis-show .ms-btn-reset{
    height:36px;padding:0 12px;border-radius:8px;border:1.5px solid var(--theme-primary);background:#fff;
    color:var(--theme-primary);font-size:.8rem;font-weight:700;display:inline-flex;align-items:center;gap:6px;text-decoration:none
}

.mis-show .ms-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-bottom:.75rem}
.mis-show .ms-kpi{
    background:#fff;border:1px solid #e8edf5;border-radius:12px;padding:12px 14px 12px 16px;
    box-shadow:0 2px 8px rgba(148,163,184,.07);position:relative;overflow:hidden;
    display:flex;align-items:flex-start;gap:10px
}
.mis-show .ms-kpi::before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:#cbd5e1}
.mis-show .ms-kpi.t-blue::before{background:#2563eb}
.mis-show .ms-kpi.t-teal::before{background:#0d9488}
.mis-show .ms-kpi.t-amber::before{background:#f59e0b}
.mis-show .ms-kpi.t-green::before{background:#16a34a}
.mis-show .ms-kpi.t-indigo::before{background:#4f46e5}
.mis-show .ms-kpi.t-rose::before{background:#e11d48}
.mis-show .ms-kpi.t-orange::before{background:#ea580c}
.mis-show .ms-kpi.t-slate::before{background:#64748b}
.mis-show .ms-kpi.t-purple::before{background:#7c3aed}
.mis-show .ms-kpi-ico{
    width:34px;height:34px;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;
    flex:0 0 auto;font-size:.95rem;background:#f1f5f9;color:#64748b
}
.mis-show .ms-kpi.t-blue .ms-kpi-ico{background:#dbeafe;color:#2563eb}
.mis-show .ms-kpi.t-teal .ms-kpi-ico{background:#ccfbf1;color:#0d9488}
.mis-show .ms-kpi.t-amber .ms-kpi-ico{background:#fef3c7;color:#d97706}
.mis-show .ms-kpi.t-green .ms-kpi-ico{background:#dcfce7;color:#16a34a}
.mis-show .ms-kpi.t-indigo .ms-kpi-ico{background:#e0e7ff;color:#4f46e5}
.mis-show .ms-kpi.t-rose .ms-kpi-ico{background:#ffe4e6;color:#e11d48}
.mis-show .ms-kpi.t-orange .ms-kpi-ico{background:#ffedd5;color:#ea580c}
.mis-show .ms-kpi.t-purple .ms-kpi-ico{background:#f3e8ff;color:#9333ea}
.mis-show .ms-kpi .lbl{font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.03em;line-height:1.2}
.mis-show .ms-kpi .val{margin-top:4px;font-size:1.28rem;font-weight:800;color:#0f172a;letter-spacing:-.02em;line-height:1.15}

.mis-show .ms-section{
    background:#fff;border:1px solid #e8edf5;border-radius:14px;padding:14px 16px;
    margin-bottom:.75rem;box-shadow:0 2px 8px rgba(148,163,184,.06)
}
.mis-show .ms-section-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:12px}
.mis-show .ms-section h2{margin:0;font-size:1rem;font-weight:800;color:#0f172a;display:flex;align-items:center;gap:8px}
.mis-show .ms-section h2 i{color:var(--theme-primary);font-size:1.05rem}
.mis-show .ms-section .hint{margin:.2rem 0 0;font-size:.8rem;color:#64748b}
.mis-show .ms-charts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
.mis-show .ms-chart-card{border:1px solid #eef2f7;border-radius:12px;padding:12px 14px;background:#fafbfc}
.mis-show .ms-chart-card h3{margin:0 0 10px;font-size:.84rem;font-weight:700;color:#334155;display:flex;align-items:center;gap:6px}
.mis-show .ms-chart-card h3 i{color:var(--theme-primary);font-size:.9rem}
.mis-show .ms-chart-wrap{position:relative;height:220px}
.mis-show .ms-table-toolbar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;margin-bottom:10px}
.mis-show .ms-search{position:relative;min-width:220px;flex:1 1 220px;max-width:320px}
.mis-show .ms-search .bi-search{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8}
.mis-show .ms-search input{width:100%;height:36px;padding:0 12px 0 36px;border-radius:8px;border:1px solid #d1d5db;font-size:.82rem}
.mis-show .ms-row-count{
    display:inline-flex;align-items:center;gap:6px;height:30px;padding:0 10px;border-radius:999px;
    background:#f1f5f9;color:#475569;font-size:.76rem;font-weight:700
}
.mis-show .ms-table-wrap{overflow:auto;border:1px solid #e8edf5;border-radius:12px}
.mis-show table.ms-table{width:100%;border-collapse:separate;border-spacing:0;min-width:880px}
.mis-show table.ms-table th{
    position:sticky;top:0;z-index:1;background:#f8fafc;color:#64748b;font-size:.7rem;font-weight:800;
    text-transform:uppercase;letter-spacing:.04em;padding:11px 12px;border-bottom:1px solid #e2e8f0;
    white-space:nowrap;cursor:pointer;user-select:none
}
.mis-show table.ms-table th:hover{color:var(--theme-primary)}
.mis-show table.ms-table td{padding:11px 12px;border-bottom:1px solid #f1f5f9;font-size:.82rem;color:#0f172a;vertical-align:middle}
.mis-show table.ms-table tbody tr:hover{background:#f8fafc}
.mis-show table.ms-table tbody tr:last-child td{border-bottom:0}
.mis-show table.ms-table a{color:var(--theme-primary);font-weight:700;text-decoration:none}
.mis-show table.ms-table a:hover{text-decoration:underline}
.mis-show .ms-cell{display:inline-flex;align-items:center;gap:7px;min-width:0}
.mis-show .ms-cell i{color:#94a3b8;font-size:.88rem;flex:0 0 auto}
.mis-show .ms-badge{
    display:inline-flex;align-items:center;gap:5px;max-width:100%;
    padding:.28rem .6rem;border-radius:999px;font-size:.72rem;font-weight:700;line-height:1.2;white-space:nowrap
}
.mis-show .ms-badge i{font-size:.75rem}
.mis-show .ms-badge.b-green{background:#dcfce7;color:#15803d}
.mis-show .ms-badge.b-amber{background:#fef3c7;color:#b45309}
.mis-show .ms-badge.b-rose{background:#ffe4e6;color:#be123c}
.mis-show .ms-badge.b-blue{background:#dbeafe;color:#1d4ed8}
.mis-show .ms-badge.b-teal{background:#ccfbf1;color:#0f766e}
.mis-show .ms-badge.b-slate{background:#f1f5f9;color:#475569}
.mis-show .ms-badge.b-indigo{background:#e0e7ff;color:#4338ca}
.mis-show .ms-age{
    display:inline-flex;align-items:center;gap:5px;padding:.2rem .5rem;border-radius:6px;
    background:#f8fafc;border:1px solid #e2e8f0;font-weight:700;font-size:.76rem;color:#334155
}
.mis-show .ms-money{
    display:inline-flex;align-items:center;gap:5px;font-weight:700;color:#0f172a;white-space:nowrap
}
.mis-show .ms-money i{color:var(--theme-primary);font-size:.85rem}
.mis-show .ms-count{
    display:inline-flex;align-items:center;justify-content:center;min-width:1.7rem;height:1.55rem;
    padding:0 .45rem;border-radius:6px;background:#f1f5f9;color:#334155;font-weight:800;font-size:.78rem
}
.mis-show .ms-pager{display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:space-between;margin-top:12px;font-size:.8rem;color:#64748b;font-weight:600}
.mis-show .ms-pager .btns{display:flex;gap:6px}
.mis-show .ms-pager button{
    height:32px;padding:0 12px;border-radius:8px;border:1.5px solid var(--theme-primary);background:#fff;
    color:var(--theme-primary);font-weight:700;font-size:.78rem;cursor:pointer
}
.mis-show .ms-pager button:hover:not(:disabled){background:var(--theme-primary);color:#fff}
.mis-show .ms-pager button:disabled{opacity:.4;cursor:not-allowed}
.mis-show .ms-empty{padding:28px;text-align:center;color:#64748b;font-weight:600}
.mis-show .ms-alert{margin-bottom:.75rem;padding:12px 14px;border-radius:10px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;font-size:.86rem;font-weight:600}

.mis-show .ms-dl-drop{position:relative}
.mis-show .ms-export-menu{
    min-width:230px;padding:.45rem 0;border-radius:12px;border:1px solid #e2e8f0;
    box-shadow:0 12px 28px rgba(15,23,42,.12)
}
.mis-show .ms-export-menu .dropdown-header{
    font-size:.68rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;padding:.4rem 1rem .15rem
}
.mis-show .ms-export-menu .dropdown-item{
    display:flex;align-items:flex-start;gap:10px;padding:.65rem 1rem;font-size:.84rem;font-weight:600;min-height:44px
}
.mis-show .ms-export-menu .dropdown-item i{font-size:1.1rem;margin-top:1px}
.mis-show .ms-export-menu .dropdown-item .sub{display:block;font-size:.72rem;font-weight:500;color:#64748b;margin-top:1px}

@media (max-width:1200px){
    .mis-show .ms-filters{flex-wrap:wrap}
    .mis-show .ms-filters > div{flex:1 1 calc(33.333% - 8px) !important;max-width:none}
    .mis-show .ms-filter-actions{margin-left:auto}
    .mis-show .ms-charts{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media (max-width:767.98px){
    .mis-show .ms-hero{flex-wrap:wrap;align-items:flex-start}
    .mis-show .ms-actions{width:100%;margin-left:0;flex-wrap:wrap}
    .mis-show .ms-filters{flex-direction:column;align-items:stretch}
    .mis-show .ms-filters > div{flex:1 1 auto !important;width:100%}
    .mis-show .ms-filter-actions-row{display:grid;grid-template-columns:1.35fr 1fr;width:100%}
    .mis-show .ms-charts{grid-template-columns:1fr}
    .mis-show .ms-chart-wrap{height:210px}
}
</style>
@endpush

@section('content')
<div class="container-fluid pd-hub mis-show">
    <div class="ms-hero">
        <div class="ms-hero-left">
            <span class="ms-ico" aria-hidden="true"><i class="bi {{ $heroIcon }}"></i></span>
            <div>
                <h1 class="ms-title">{{ $report['title'] ?? 'MIS Report' }}</h1>
                <p class="ms-sub">{{ $report['description'] ?? '' }}</p>
                <ul class="ms-crumbs">
                    <li><a href="{{ route('goal.mis-reports') }}">MIS Reports</a></li>
                    <li class="sep">/</li>
                    <li>{{ $report['title'] ?? 'Report' }}</li>
                </ul>
                <div class="ms-meta">
                    <span><i class="bi bi-calendar3"></i> {{ $report['period_label'] ?? $dateRangeLabel }}</span>
                    <span><i class="bi bi-geo-alt"></i> {{ $report['scope_label'] ?? '—' }}</span>
                    @if(!empty($f['fy']))
                        <span><i class="bi bi-briefcase"></i> FY {{ $f['fy'] }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="ms-actions">
            <a class="ms-btn" href="{{ route('goal.mis-reports', $exportQs) }}"><i class="bi bi-arrow-left"></i> Back</a>
            <div class="dropdown ms-dl-drop">
                <button type="button" class="ms-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download"></i> Download
                </button>
                <ul class="dropdown-menu dropdown-menu-end ms-export-menu">
                    <li><h6 class="dropdown-header">Download Report</h6></li>
                    <li>
                        <a class="dropdown-item" href="{{ route('goal.mis-reports.export', array_merge($exportQs, ['report' => $key, 'format' => 'pdf'])) }}">
                            <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
                            <span>PDF<span class="sub">Print-ready</span></span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('goal.mis-reports.export', array_merge($exportQs, ['report' => $key, 'format' => 'xlsx'])) }}">
                            <i class="bi bi-file-earmark-excel-fill text-success"></i>
                            <span>Excel<span class="sub">Analysis</span></span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('goal.mis-reports.export', array_merge($exportQs, ['report' => $key, 'format' => 'csv'])) }}">
                            <i class="bi bi-filetype-csv text-primary"></i>
                            <span>CSV<span class="sub">Raw data</span></span>
                        </a>
                    </li>
                </ul>
            </div>
            <a class="ms-btn" href="{{ route('goal.analytics', array_merge($exportQs, ['section' => 'overview'])) }}#schedule"
               onclick="sessionStorage.setItem('gaOpenSchedule','1');sessionStorage.setItem('gaScheduleType','{{ $key }}')">
                <i class="bi bi-calendar3"></i> Schedule
            </a>
            <a class="ms-btn primary" href="{{ route('goal.mis-reports.export', array_merge($exportQs, ['report' => $key, 'format' => 'pdf', 'inline' => 1])) }}" target="_blank" rel="noopener">
                <i class="bi bi-printer"></i> Print
            </a>
        </div>
    </div>

    @if(!empty($report['load_error']))
        <div class="ms-alert">{{ $report['load_error'] }}</div>
    @endif

    <form method="get" action="{{ route('goal.mis-reports.show', $key) }}" class="ms-filters" id="misShowFilters">
        <div class="ms-f-fy">
            <label for="msFy">Financial Year</label>
            <select name="fy" id="msFy" class="form-select">
                <option value="">Current period</option>
                @foreach(($fyOptions ?? []) as $fy)
                    <option value="{{ $fy }}" @selected(($f['fy'] ?? '') == $fy)>{{ $fy }}</option>
                @endforeach
            </select>
        </div>
        <div class="ms-f-date">
            <label for="msDateRange">Date Range</label>
            <div class="ms-date-wrap">
                <i class="bi bi-calendar3"></i>
                <input type="text" id="msDateRange" class="form-control" placeholder="Select range" value="{{ $dateRangeLabel }}" readonly>
            </div>
            <input type="hidden" name="date_from" id="msDateFrom" value="{{ $f['date_from'] ?? '' }}">
            <input type="hidden" name="date_to" id="msDateTo" value="{{ $f['date_to'] ?? '' }}">
        </div>
        <div class="ms-f-region">
            <label for="msRegion">Region</label>
            <select name="region" id="msRegion" class="form-select">
                <option value="">All Regions</option>
                @foreach(($opts['regions'] ?? []) as $r)
                    <option value="{{ $r->id }}" @selected((string)($f['region'] ?? '') === (string)$r->id)>{{ $r->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="ms-f-cluster">
            <label for="msCluster">Cluster</label>
            <select name="cluster" id="msCluster" class="form-select">
                <option value="">All Clusters</option>
                @foreach(($opts['clusters'] ?? []) as $c)
                    <option value="{{ $c->id }}" @selected((string)($f['cluster'] ?? '') === (string)$c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="ms-f-branch">
            <label for="msBranch">Branch</label>
            <select name="branch" id="msBranch" class="form-select">
                <option value="">All Branches</option>
                @foreach(($opts['branches'] ?? []) as $b)
                    <option value="{{ $b->id }}" @selected((string)($f['branch'] ?? '') === (string)$b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="ms-f-product">
            <label for="msProduct">Product</label>
            <select name="product" id="msProduct" class="form-select">
                <option value="">All Products</option>
                @foreach(($opts['products'] ?? []) as $p)
                    @php $pv = is_string($p) ? $p : ($p->name ?? $p->product ?? (string)$p); @endphp
                    <option value="{{ $pv }}" @selected(($f['product'] ?? '') === $pv)>{{ $pv }}</option>
                @endforeach
            </select>
        </div>
        <div class="ms-filter-actions">
            <label aria-hidden="true">&nbsp;</label>
            <div class="ms-filter-actions-row">
                <button type="submit" class="ms-btn-apply"><i class="bi bi-funnel-fill"></i> Apply</button>
                <a href="{{ route('goal.mis-reports.show', $key) }}" class="ms-btn-reset"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
            </div>
        </div>
    </form>

    <div class="ms-kpis">
        @forelse(($report['kpis'] ?? []) as $kpi)
            @php $tone = $kpi['tone'] ?? 'slate'; @endphp
            <div class="ms-kpi {{ $kpiTone[$tone] ?? 't-slate' }}">
                <span class="ms-kpi-ico" aria-hidden="true"><i class="bi {{ $kpiIcons[$tone] ?? 'bi-bar-chart-fill' }}"></i></span>
                <div>
                    <div class="lbl">{{ $kpi['label'] ?? '' }}</div>
                    <div class="val">{{ $kpi['value'] ?? '—' }}</div>
                </div>
            </div>
        @empty
            <div class="ms-kpi t-slate">
                <span class="ms-kpi-ico"><i class="bi bi-bar-chart"></i></span>
                <div><div class="lbl">No KPIs</div><div class="val">—</div></div>
            </div>
        @endforelse
    </div>

    @if(count($charts))
        <div class="ms-section">
            <div class="ms-section-head">
                <div>
                    <h2><i class="bi bi-pie-chart-fill"></i> Charts and Analytics</h2>
                    <p class="hint">Visual summary for the selected filters and period.</p>
                </div>
            </div>
            <div class="ms-charts">
                @foreach($charts as $chart)
                    <div class="ms-chart-card">
                        <h3>
                            <i class="bi {{ ($chart['type'] ?? '') === 'doughnut' ? 'bi-pie-chart' : (($chart['type'] ?? '') === 'line' ? 'bi-graph-up' : 'bi-bar-chart') }}"></i>
                            {{ $chart['title'] ?? 'Chart' }}
                        </h3>
                        <div class="ms-chart-wrap">
                            <canvas id="{{ $chart['id'] ?? ('chart'.$loop->index) }}"></canvas>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="ms-section">
        <div class="ms-section-head">
            <div>
                <h2><i class="bi bi-table"></i> Detailed Report Table</h2>
                <p class="hint">Search, sort and open linked records for drill-down.</p>
            </div>
        </div>
        <div class="ms-table-toolbar">
            <div class="ms-search">
                <i class="bi bi-search"></i>
                <input type="search" id="misTableSearch" placeholder="Search table…" autocomplete="off">
            </div>
            <span class="ms-row-count"><i class="bi bi-list-ul"></i> <span id="misRowCount">{{ count($rows) }}</span> rows</span>
        </div>
        <div class="ms-table-wrap">
            <table class="ms-table" id="misDetailTable">
                <thead>
                    <tr>
                        @foreach($columns as $col)
                            <th data-key="{{ $col['key'] ?? '' }}">{{ $col['label'] ?? '' }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            @foreach($columns as $col)
                                @php
                                    $ck = $col['key'] ?? '';
                                    $val = $row[$ck] ?? '—';
                                    $isLink = $loop->first && $linkKey && !empty($row[$linkKey]) && $row[$linkKey] !== '#';
                                    $isBadge = in_array($ck, $badgeKeys, true);
                                    $cellIcon = $iconKeys[$ck] ?? null;
                                    $isAge = in_array($ck, ['age', 'ageing', 'processing_days', 'pending_days'], true);
                                    $isMoney = in_array($ck, $moneyKeys, true) && is_string($val) && (str_contains($val, '₹') || str_contains($val, 'Cr') || str_contains($val, 'Lac'));
                                    $isCount = in_array($ck, $countKeys, true) && is_numeric($val);
                                    // For branch_mis, "disbursed" is a count; for disbursement_summary it can be money string.
                                    if ($ck === 'disbursed' && is_numeric($val)) {
                                        $isMoney = false;
                                        $isCount = true;
                                    }
                                @endphp
                                <td>
                                    @if($isLink)
                                        <a href="{{ $row[$linkKey] }}"><i class="bi bi-box-arrow-up-right" style="font-size:.72rem;margin-right:4px"></i>{{ $val }}</a>
                                    @elseif($isBadge)
                                        <span class="ms-badge {{ $statusBadge($val, $ck) }}">
                                            <i class="bi bi-circle-fill" style="font-size:.45rem"></i>{{ $val }}
                                        </span>
                                    @elseif($isAge)
                                        <span class="ms-age"><i class="bi {{ $cellIcon ?? 'bi-hourglass' }}"></i>{{ $val }}{{ is_numeric($val) ? 'd' : '' }}</span>
                                    @elseif($isMoney)
                                        <span class="ms-money"><i class="bi bi-currency-rupee"></i>{{ str_replace('₹', '', (string) $val) }}</span>
                                    @elseif($isCount)
                                        <span class="ms-count">{{ $val }}</span>
                                    @elseif($cellIcon)
                                        <span class="ms-cell"><i class="bi {{ $cellIcon }}"></i><span>{{ $val }}</span></span>
                                    @else
                                        {{ $val }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ max(1, count($columns)) }}" class="ms-empty">No records for the selected filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="ms-pager">
            <div id="misPageInfo">Page 1</div>
            <div class="btns">
                <button type="button" id="misPrevPage" disabled><i class="bi bi-chevron-left"></i> Previous</button>
                <button type="button" id="misNextPage" disabled>Next <i class="bi bi-chevron-right"></i></button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const rangeEl = document.getElementById('msDateRange');
    const fromEl = document.getElementById('msDateFrom');
    const toEl = document.getElementById('msDateTo');
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

    const chartDefs = @json($charts);
    chartDefs.forEach(function (cfg) {
        const el = document.getElementById(cfg.id);
        if (!el || !window.Chart) return;
        const type = cfg.type || 'bar';
        new Chart(el, {
            type: type,
            data: {
                labels: cfg.labels || [],
                datasets: (cfg.datasets || []).map(function (ds) {
                    return Object.assign({
                        borderWidth: type === 'line' ? 2 : 0,
                        borderRadius: type === 'bar' ? 6 : 0,
                    }, ds);
                })
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: type !== 'doughnut' || (cfg.labels || []).length <= 8, position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: type === 'doughnut' ? {} : {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,.25)' }, ticks: { font: { size: 10 } } }
                }
            }
        });
    });

    const table = document.getElementById('misDetailTable');
    const search = document.getElementById('misTableSearch');
    const pageInfo = document.getElementById('misPageInfo');
    const prevBtn = document.getElementById('misPrevPage');
    const nextBtn = document.getElementById('misNextPage');
    const rowCountEl = document.getElementById('misRowCount');
    if (!table) return;

    const allRows = Array.from(table.querySelectorAll('tbody tr')).filter(function (tr) {
        return !tr.querySelector('.ms-empty');
    });
    let filtered = allRows.slice();
    let page = 1;
    const perPage = 25;
    let sortKey = null;
    let sortAsc = true;

    function render() {
        allRows.forEach(function (r) { r.style.display = 'none'; });
        const total = filtered.length;
        const pages = Math.max(1, Math.ceil(total / perPage));
        if (page > pages) page = pages;
        const start = (page - 1) * perPage;
        filtered.slice(start, start + perPage).forEach(function (r) { r.style.display = ''; });
        if (rowCountEl) rowCountEl.textContent = String(total);
        if (pageInfo) pageInfo.textContent = total ? ('Page ' + page + ' of ' + pages) : 'No rows';
        if (prevBtn) prevBtn.disabled = page <= 1 || total === 0;
        if (nextBtn) nextBtn.disabled = page >= pages || total === 0;
    }

    function applySearch() {
        const q = (search && search.value ? search.value : '').trim().toLowerCase();
        filtered = allRows.filter(function (tr) {
            if (!q) return true;
            return tr.textContent.toLowerCase().indexOf(q) !== -1;
        });
        page = 1;
        render();
    }

    if (search) search.addEventListener('input', applySearch);
    if (prevBtn) prevBtn.addEventListener('click', function () { page--; render(); });
    if (nextBtn) nextBtn.addEventListener('click', function () { page++; render(); });

    table.querySelectorAll('thead th').forEach(function (th, colIdx) {
        th.addEventListener('click', function () {
            const key = th.getAttribute('data-key') || String(colIdx);
            if (sortKey === key) sortAsc = !sortAsc; else { sortKey = key; sortAsc = true; }
            filtered.sort(function (a, b) {
                const av = (a.children[colIdx] ? a.children[colIdx].textContent : '').trim();
                const bv = (b.children[colIdx] ? b.children[colIdx].textContent : '').trim();
                const an = parseFloat(av.replace(/[^0-9.\-]/g, ''));
                const bn = parseFloat(bv.replace(/[^0-9.\-]/g, ''));
                let cmp;
                if (!isNaN(an) && !isNaN(bn) && av.match(/[0-9]/) && bv.match(/[0-9]/)) cmp = an - bn;
                else cmp = av.localeCompare(bv, undefined, { numeric: true, sensitivity: 'base' });
                return sortAsc ? cmp : -cmp;
            });
            page = 1;
            render();
        });
    });

    render();
})();
</script>
@endpush
