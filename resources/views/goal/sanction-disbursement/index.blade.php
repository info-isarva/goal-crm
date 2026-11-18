@extends('layouts.app')

@push('styles')
<style>
.sd-page{color:#1e293b}
.sd-page .page-hero{display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1rem}
.sd-page .breadcrumb-sd{display:flex;flex-wrap:wrap;align-items:center;gap:6px;margin:0 0 8px;padding:0;list-style:none;font-size:.78rem;color:#64748b}
.sd-page .breadcrumb-sd a{color:var(--theme-primary);text-decoration:none;font-weight:600}
.sd-page .page-title{font-size:1.55rem;font-weight:800;color:#0f172a;margin:0;line-height:1.2}
.sd-page .page-sub{color:#64748b;font-size:.9rem;margin-top:4px}
.sd-page .hero-actions{display:flex;flex-wrap:wrap;gap:8px}

.btn-sd-primary{
    height:40px;border-radius:10px;border:1px solid transparent !important;
    background:var(--theme-primary) !important;color:#fff !important;
    padding:0 16px;font-weight:700;font-size:.88rem;
    display:inline-flex;align-items:center;gap:7px;
    box-shadow:0 8px 18px var(--theme-primary-ring);
}
.btn-sd-primary:hover{background:var(--theme-primary-hover) !important;color:#fff !important}
.btn-sd-outline{
    height:40px;border-radius:10px;border:1px solid var(--theme-primary) !important;
    background:#fff !important;color:var(--theme-primary) !important;
    padding:0 16px;font-weight:700;font-size:.88rem;
    display:inline-flex;align-items:center;gap:7px;
}
.btn-sd-outline:hover{background:var(--theme-50) !important;color:var(--theme-primary-hover) !important}
.btn-sd-cancel{
    height:40px;border-radius:10px;border:1px solid #cbd5e1 !important;
    background:#fff !important;color:#334155 !important;padding:0 16px;font-weight:600;font-size:.88rem;
}

.sd-page .kpi-row{margin-bottom:.9rem}
.sd-page .stat-tile{
    --tile-ink:#2563eb;--tile-soft:#dbeafe;--tile-wave:#93c5fd;
    position:relative;overflow:hidden;border:1px solid #e8edf5;border-radius:14px;
    padding:.75rem .9rem;background:#fff;
    box-shadow:0 1px 2px rgba(15,23,42,.04),0 8px 18px rgba(148,163,184,.12);
    display:flex;align-items:center;gap:.85rem;
}
.sd-page .stat-tile::after{
    content:"";position:absolute;right:-12px;bottom:-22px;width:72px;height:56px;
    background:radial-gradient(ellipse at center,var(--tile-wave) 0%,transparent 70%);opacity:.45;pointer-events:none;
}
.sd-page .stat-tile.tone-blue{--tile-ink:#2563eb;--tile-soft:#dbeafe;--tile-wave:#93c5fd}
.sd-page .stat-tile.tone-orange{--tile-ink:#ea580c;--tile-soft:#ffedd5;--tile-wave:#fdba74}
.sd-page .stat-tile.tone-green{--tile-ink:#16a34a;--tile-soft:#dcfce7;--tile-wave:#86efac}
.sd-page .stat-tile.tone-purple{--tile-ink:#7c3aed;--tile-soft:#ede9fe;--tile-wave:#c4b5fd}
.sd-page .stat-icon{
    width:40px;height:40px;border-radius:12px;flex-shrink:0;
    display:inline-flex;align-items:center;justify-content:center;
    background:var(--tile-soft);color:var(--tile-ink);font-size:1.05rem;position:relative;z-index:1;
}
.sd-page .stat-meta{position:relative;z-index:1;min-width:0;flex:1}
.sd-page .stat-value{
    margin:0;font-size:1.2rem;font-weight:800;color:#0f172a;line-height:1.15;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-variant-numeric:tabular-nums;
}
.sd-page .stat-label{margin:4px 0 0;font-size:.78rem;font-weight:600;color:#64748b;line-height:1.25}

.sd-page .tabs-card,.sd-page .filter-card,.sd-page .table-card,.sd-page .side-card{
    background:#fff;border:1px solid #e8edf5;border-radius:16px;
    box-shadow:0 10px 28px rgba(148,163,184,.12);
}
.sd-page .tabs-card{padding:.65rem .8rem;margin-bottom:.85rem;overflow:hidden}
.sd-page .status-tabs{display:flex;flex-wrap:wrap;gap:6px}
.sd-page .status-tab{
    display:inline-flex;align-items:center;gap:7px;height:36px;padding:0 14px;border-radius:999px;
    border:1px solid var(--theme-200);background:#fff;color:var(--theme-primary-hover);
    font-size:.84rem;font-weight:650;text-decoration:none;flex:0 0 auto;white-space:nowrap;
}
.sd-page .status-tab.active{background:var(--theme-primary);border-color:var(--theme-primary);color:#fff;box-shadow:0 6px 14px var(--theme-primary-ring)}
.sd-page .filter-card{padding:1rem 1.15rem;margin-bottom:.75rem;overflow:visible !important}
.sd-page .filter-grid{display:grid;grid-template-columns:minmax(220px,1.6fr) repeat(3,minmax(130px,1fr));gap:12px;align-items:end}
.sd-page .filter-grid.row-2{grid-template-columns:minmax(180px,1fr) auto;margin-top:12px;max-width:520px}
.sd-page .filter-grid label{display:block;font-size:.72rem;font-weight:700;color:#64748b;margin:0 0 6px;text-transform:uppercase;letter-spacing:.04em}
.sd-page .filter-grid .form-control,.sd-page .filter-grid .form-select{min-height:38px;height:38px;border-radius:10px;border:1px solid #d1d5db;font-size:.9rem}
.sd-page .sd-search{position:relative}
.sd-page .sd-search > i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none}
.sd-page .sd-search .form-control{padding-left:38px}
.sd-page .sd-date{position:relative}
.sd-page .sd-date > i{position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none}
.sd-page .filter-actions{display:flex;align-items:end;gap:8px}
.sd-page .btn-apply{
    height:38px;border-radius:10px;border:none;background:var(--theme-primary)!important;color:#fff!important;
    padding:0 16px;font-weight:700;font-size:.85rem;display:inline-flex;align-items:center;gap:6px;
}
.sd-page .btn-reset{
    height:38px;border-radius:10px;border:1px solid #cbd5e1;background:#fff;color:#475569;
    padding:0 14px;font-weight:600;font-size:.85rem;text-decoration:none;display:inline-flex;align-items:center;gap:6px;
}

.sd-page .table-card{margin-bottom:.75rem}
.sd-page .table-card-head{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.5rem;padding:.75rem 1rem;border-bottom:1px solid #eef2f7}
.sd-page .table-card-head h5{margin:0;font-size:1.02rem;font-weight:800}
.sd-page .head-sub{margin:2px 0 0;font-size:.78rem;color:#64748b}
.sd-page .table-card-body{padding:0;max-width:100%}
.sd-page .table-wrap{
    border:0;border-radius:0;overflow-x:auto;overflow-y:hidden;max-width:100%;
    -webkit-overflow-scrolling:touch;overscroll-behavior-x:contain;
    scrollbar-width:thin;scrollbar-color:#cbd5e1 transparent;
}
.sd-page .table-wrap::-webkit-scrollbar{height:6px;width:6px}
.sd-page .table-wrap::-webkit-scrollbar-track{background:transparent}
.sd-page .table-wrap::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:999px}
.sd-page .table-wrap::-webkit-scrollbar-thumb:hover{background:#94a3b8}
.sd-page .table-wrap > table{width:100%;min-width:1100px;margin:0;table-layout:auto;border-collapse:separate;border-spacing:0}
.sd-page table thead th{font-size:.7rem;text-transform:uppercase;letter-spacing:.04em;color:#64748b;font-weight:700;background:#f1f5f9;padding:.65rem .7rem;white-space:nowrap}
.sd-page table tbody td{vertical-align:middle;font-size:.84rem;color:#334155;border-color:#eef2f7;padding:.65rem .7rem;white-space:nowrap;background:#fff}
/* Keep Actions pinned while the register scrolls horizontally */
.sd-page .table-wrap > table thead th.col-actions,
.sd-page .table-wrap > table tbody td.col-actions{
    position:sticky;right:0;z-index:3;
    min-width:118px;width:118px;
    box-shadow:-6px 0 10px rgba(15,23,42,.06);
    border-left:1px solid #eef2f7;
}
.sd-page .table-wrap > table thead th.col-actions{background:#f1f5f9;z-index:4}
.sd-page .table-wrap > table tbody td.col-actions{background:#fff}
.sd-page .case-link{font-weight:800;color:var(--theme-primary);text-decoration:none;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.82rem}
.sd-page .case-link:hover{text-decoration:underline}
.sd-page .lender-cell{display:inline-flex;align-items:center;gap:8px;font-weight:700;color:#0f172a}
.sd-page .lender-mark{
    width:28px;height:28px;border-radius:8px;background:var(--theme-50);color:var(--theme-primary);
    display:inline-flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;
}
.sd-page .status-pill{display:inline-flex;padding:.28rem .7rem;border-radius:999px;font-size:.74rem;font-weight:700}
.sd-page .status-pill.pending{background:#ffedd5;color:#c2410c}
.sd-page .status-pill.partial{background:#dbeafe;color:#1d4ed8}
.sd-page .status-pill.completed{background:#dcfce7;color:#15803d}
.sd-page .status-pill.verified{background:#dcfce7;color:#15803d}
.sd-page .status-pill.awaiting{background:#fef3c7;color:#b45309}
.sd-page .actions-cell{display:inline-flex;align-items:center;gap:4px}
.sd-page .action-btn{
    width:32px;height:32px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#475569;
    display:inline-flex;align-items:center;justify-content:center;text-decoration:none;padding:0;
}
.sd-page .action-btn:hover{background:#f8fafc;color:var(--theme-primary)}
.sd-page .table-footer-bar{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem;padding:.75rem 1.15rem;border-top:1px solid #eef2f7;font-size:.82rem;color:#64748b}
.sd-page .bottom-grid{display:grid;grid-template-columns:1.35fr .95fr;gap:.75rem;margin-bottom:.75rem}
.sd-page .side-card-head{padding:.75rem 1rem;border-bottom:1px solid #eef2f7}
.sd-page .side-card-head h5{margin:0;font-size:1rem;font-weight:800}
.sd-page .side-card-body{padding:.55rem .85rem .7rem}
.sd-page .side-card-body .table-wrap{
    overflow-x:auto;scrollbar-width:thin;scrollbar-color:#cbd5e1 transparent;max-width:100%;
}
.sd-page .side-card-body .table-wrap::-webkit-scrollbar{height:6px}
.sd-page .side-card-body .table-wrap::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:999px}
.sd-page .d-table{width:100%;margin:0;min-width:720px;border-collapse:separate;border-spacing:0}
.sd-page .d-table th{font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;color:#64748b;font-weight:700;padding:.55rem .45rem;border-bottom:1px solid #e2e8f0;background:#f8fafc;white-space:nowrap}
.sd-page .d-table td{font-size:.82rem;padding:.6rem .45rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;white-space:nowrap}
.sd-page .workflow{
    --wf-dot:32px;
    display:flex;align-items:flex-start;gap:0;margin-top:.25rem;
    width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;
    padding-bottom:.15rem;scrollbar-width:thin;
}
.sd-page .wf-step{
    flex:1 1 0;min-width:0;text-align:center;position:relative;padding:0 6px;
}
.sd-page .wf-step:not(:last-child)::after{
    content:"";position:absolute;z-index:0;pointer-events:none;
    top:calc(var(--wf-dot) / 2);left:calc(50% + (var(--wf-dot) / 2));
    width:calc(100% - var(--wf-dot));height:2px;
    transform:translateY(-50%);background:#e2e8f0;
}
.sd-page .wf-step.done:not(:last-child)::after{background:#86efac}
.sd-page .wf-step.active:not(:last-child)::after{background:linear-gradient(90deg,#86efac,#cbd5e1)}
.sd-page .wf-dot{
    width:var(--wf-dot);height:var(--wf-dot);border-radius:999px;margin:0 auto 8px;
    display:flex;align-items:center;justify-content:center;box-sizing:border-box;
    background:#f1f5f9;color:#94a3b8;border:2px solid #e2e8f0;font-size:.9rem;
    position:relative;z-index:1;flex-shrink:0;line-height:1;
}
.sd-page .wf-dot i{line-height:1;display:block;font-size:.95rem}
.sd-page .wf-step.done .wf-dot{background:#dcfce7;color:#15803d;border-color:#86efac}
.sd-page .wf-step.active .wf-dot{background:var(--theme-primary);color:#fff;border-color:var(--theme-primary)}
.sd-page .wf-label{
    font-size:.78rem;font-weight:700;color:#0f172a;line-height:1.25;
    overflow-wrap:anywhere;word-break:break-word;
}
.sd-page .wf-desc{font-size:.7rem;color:#94a3b8;margin-top:2px;line-height:1.3}
.sd-page .empty-state{padding:2rem 1rem;text-align:center;color:#64748b}
.sd-page .info-banner{
    display:flex;gap:12px;align-items:flex-start;padding:14px 16px;border-radius:14px;
    background:#eff6ff;border:1px solid #bfdbfe;color:#1e3a8a;font-size:.86rem;line-height:1.45;
}
.sd-page .info-banner i{font-size:1.15rem;margin-top:2px;color:#2563eb}
.sd-modal .modal-content{
    border:1px solid #e8edf5;
    box-shadow:0 20px 50px rgba(15,23,42,.18);
}
.sd-modal .modal-header{padding:.85rem 1rem;border-bottom:1px solid #eef2f7}
.sd-modal .modal-title{font-size:1.05rem;font-weight:800;margin:0}
.sd-modal .modal-body{padding:.9rem 1rem}
.sd-modal .modal-footer{padding:.8rem 1rem;border-top:1px solid #eef2f7;background:#f8fafc;gap:8px}
@media (max-width:767.98px){
    .sd-modal .modal-footer .btn-sd-cancel,
    .sd-modal .modal-footer .btn-sd-primary,
    .sd-modal .modal-footer .btn-sd-outline{width:100%;justify-content:center;min-height:48px;height:48px;box-shadow:none}
}
.sd-modal .form-label{font-size:.78rem;font-weight:700;color:#475569}
.sd-modal .form-control,.sd-modal .form-select{min-height:40px;border-radius:10px}
.sd-modal .form-control:focus,.sd-modal .form-select:focus{border-color:var(--theme-primary);box-shadow:0 0 0 3px var(--theme-primary-ring)}
.sd-modal .hint{font-size:.75rem;color:#64748b;margin-top:4px}
@media (max-width:1199.98px){
    .sd-page .bottom-grid{grid-template-columns:1fr}
    .sd-page .filter-grid{grid-template-columns:minmax(180px,1.4fr) repeat(2,minmax(120px,1fr))}
}
@media (max-width:991.98px){
    .sd-page .status-tabs{
        flex-wrap:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch;
        scrollbar-width:none;padding-bottom:2px;
    }
    .sd-page .status-tabs::-webkit-scrollbar{display:none;height:0}
    .sd-page .filter-grid{grid-template-columns:1fr 1fr}
    .sd-page .filter-grid.row-2{grid-template-columns:1fr auto;max-width:none}
}
@media (max-width:575.98px){
    .sd-page.container-fluid{padding:.65rem !important}
    .sd-page .page-hero{margin-bottom:.65rem;gap:.65rem}
    .sd-page .page-title{font-size:1.22rem}
    .sd-page .page-sub{font-size:.8rem;margin-top:2px}
    .sd-page .hero-actions{width:100%;display:grid;grid-template-columns:1fr 1fr;gap:8px}
    .sd-page .btn-sd-primary,.sd-page .btn-sd-outline{
        width:100%;justify-content:center;height:38px;padding:0 10px;font-size:.8rem;gap:5px;
        box-shadow:none;
    }

    .sd-page .kpi-row{margin-bottom:.65rem}
    .sd-page .kpi-row > [class*="col-"]{padding-left:.3rem;padding-right:.3rem}
    .sd-page .stat-tile{
        flex-direction:column;align-items:flex-start;gap:.4rem;
        padding:.55rem .6rem;border-radius:12px;min-height:86px;
    }
    .sd-page .stat-icon{width:30px;height:30px;border-radius:9px;font-size:.9rem}
    .sd-page .stat-value{font-size:.92rem;letter-spacing:-.01em}
    .sd-page .stat-label{font-size:.68rem;margin-top:2px}

    .sd-page .tabs-card{padding:.5rem .55rem;margin-bottom:.65rem;border-radius:12px}
    .sd-page .status-tab{height:32px;padding:0 11px;font-size:.78rem}

    .sd-page .filter-card{padding:.7rem .75rem;margin-bottom:.65rem;border-radius:12px}
    .sd-page .filter-grid{grid-template-columns:1fr 1fr !important;gap:8px}
    .sd-page .filter-grid > div:first-child{grid-column:1 / -1}
    .sd-page .filter-grid.row-2{grid-template-columns:1fr !important;margin-top:8px;max-width:none}
    .sd-page .filter-grid label{margin-bottom:4px;font-size:.68rem}
    .sd-page .filter-grid .form-control,
    .sd-page .filter-grid .form-select{min-height:36px;height:36px;font-size:.84rem}
    .sd-page .filter-actions{width:100%;display:grid;grid-template-columns:1fr 1fr;gap:8px}
    .sd-page .btn-apply,.sd-page .btn-reset{width:100%;justify-content:center;height:36px}

    .sd-page .table-card,.sd-page .side-card{border-radius:12px;margin-bottom:.65rem}
    .sd-page .table-card-head,.sd-page .side-card-head{padding:.6rem .75rem}
    .sd-page .table-card-head h5,.sd-page .side-card-head h5{font-size:.95rem}
    .sd-page .head-sub{display:none}
    .sd-page .table-footer-bar{padding:.55rem .75rem}

    /* Dense register cards (override app-theme mobile stack) */
    .sd-page .table-card-body,
    .sd-page .side-card-body{padding:.5rem .55rem !important}
    .sd-page .table-wrap > table tr,
    .sd-page .side-card-body .table-wrap > table tr{
        margin:0 0 .5rem !important;
        padding:.45rem .55rem !important;
        border-radius:10px !important;
        box-shadow:0 2px 8px rgba(148,163,184,.1) !important;
    }
    .sd-page .table-wrap > table td,
    .sd-page .side-card-body .table-wrap > table td{
        padding:.18rem 0 !important;
        font-size:.8rem !important;
        gap:.45rem !important;
        line-height:1.25 !important;
    }
    .sd-page .table-wrap > table td::before,
    .sd-page .side-card-body .table-wrap > table td::before{
        font-size:.62rem !important;
        flex:0 0 38% !important;
        max-width:38% !important;
    }
    .sd-page .table-wrap > table td > *,
    .sd-page .side-card-body .table-wrap > table td > *{max-width:62%}

    /* Hide low-value fields to shorten register / disbursement cards */
    .sd-page .table-wrap > table td[data-label="#"],
    .sd-page .table-wrap > table td[data-label="Application Reference"],
    .sd-page .table-wrap > table td[data-label="Requested Amount"],
    .sd-page .side-card-body .table-wrap > table td[data-label="#"],
    .sd-page .side-card-body .table-wrap > table td[data-label="Verified By"]{
        display:none !important;
    }
    .sd-page .table-wrap > table td[data-label="Case Reference"]{
        padding-bottom:.35rem !important;margin-bottom:.15rem !important;
        border-bottom:1px solid #f1f5f9 !important;
    }
    .sd-page .table-wrap > table td[data-label="Case Reference"]::before{content:"Case"}
    .sd-page .table-wrap > table td[data-label="Sanctioned Amount"]::before{content:"Sanctioned"}
    .sd-page .table-wrap > table td[data-label="Total Disbursed"]::before{content:"Disbursed"}
    .sd-page .table-wrap > table td[data-label="Remaining Amount"]::before{content:"Remaining"}
    .sd-page .table-wrap > table td[data-label="Sanction Date"]::before{content:"Date"}
    .sd-page .table-wrap > table td.col-actions{
        padding-top:.4rem !important;margin-top:.15rem !important;
    }
    .sd-page .lender-mark{display:none}
    .sd-page .action-btn{width:30px;height:30px}

    .sd-page .bottom-grid{gap:.65rem;margin-bottom:.65rem}
    .sd-page .workflow{--wf-dot:28px;overflow:visible;padding-bottom:0}
    .sd-page .wf-step{flex:1 1 0;min-width:0;padding:0 4px}
    .sd-page .wf-desc{display:none}
    .sd-page .wf-label{font-size:.68rem;line-height:1.2}
    .sd-page .info-banner{font-size:.76rem;padding:10px 12px;gap:8px;border-radius:12px;margin-bottom:.25rem}
    .sd-page .info-banner i{font-size:1rem}
}
</style>
@endpush

@section('content')
@php
    $tabQuery = fn ($key) => array_filter(array_merge(request()->except('page'), ['tab' => $key]));
    $fmtInr = fn ($n) => $n !== null && $n !== '' ? '₹ '.number_format((float) $n, 0) : '—';
    $lenderInitials = function ($name) {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        return strtoupper(substr($parts[0] ?? 'B', 0, 1).substr($parts[1] ?? '', 0, 1));
    };
    $evidencePath = function (?string $note) {
        if (!$note || !str_contains($note, 'evidence:')) return null;
        return trim(explode('evidence:', $note, 2)[1] ?? '') ?: null;
    };
    $activeIdx = collect($workflow)->search(fn ($s) => $s['key'] === $activeWorkflow);
    if ($activeIdx === false) $activeIdx = 1;
@endphp

<div class="container-fluid p-4 sd-page">
    <div class="page-hero">
        <div>
            <ol class="breadcrumb-sd">
                <li><a href="{{ route('dashboard') }}">Home</a></li>
                <li>/</li>
                <li><a href="{{ route('goal.cases.index') }}">Loan Processing</a></li>
                <li>/</li>
                <li>Sanction &amp; Disbursement</li>
            </ol>
            <h1 class="page-title">Sanction &amp; Disbursement</h1>
            <div class="page-sub">Record lender sanction decisions and track verified disbursements.</div>
        </div>
        <div class="hero-actions">
            <a href="{{ route('goal.pms.index') }}" class="btn-sd-cancel" style="text-decoration:none"><i class="bi bi-briefcase"></i> PMS</a>
            <a href="{{ route('goal.accounts.index') }}" class="btn-sd-cancel" style="text-decoration:none"><i class="bi bi-journal-check"></i> Accounts</a>
            <a href="{{ route('goal.payouts.index', ['status' => 'on_hold']) }}" class="btn-sd-cancel" style="text-decoration:none"><i class="bi bi-cash-stack"></i> Payouts</a>
            @if($canEdit)
                <button type="button" class="btn-sd-primary" data-bs-toggle="modal" data-bs-target="#sdSanctionModal">
                    <i class="bi bi-plus-lg"></i> Record Sanction
                </button>
                <button type="button" class="btn-sd-outline" data-bs-toggle="modal" data-bs-target="#sdDisbModal">
                    <i class="bi bi-cash-coin"></i> Record Disbursement
                </button>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger py-2"><ul class="mb-0 small">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-2 kpi-row">
        <div class="col-6 col-lg-3">
            <div class="stat-tile tone-blue">
                <span class="stat-icon"><i class="bi bi-award"></i></span>
                <div class="stat-meta">
                    <div class="stat-value">{{ number_format($kpis['applications']) }}</div>
                    <p class="stat-label">Sanctioned Applications</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-tile tone-orange">
                <span class="stat-icon"><i class="bi bi-currency-rupee"></i></span>
                <div class="stat-meta">
                    <div class="stat-value">{{ $fmtInr($kpis['sanctioned_amount']) }}</div>
                    <p class="stat-label">Sanctioned Amount</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-tile tone-green">
                <span class="stat-icon"><i class="bi bi-check2-circle"></i></span>
                <div class="stat-meta">
                    <div class="stat-value">{{ $fmtInr($kpis['verified_amount']) }}</div>
                    <p class="stat-label">Total Disbursed (Verified)</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-tile tone-purple">
                <span class="stat-icon"><i class="bi bi-pie-chart"></i></span>
                <div class="stat-meta">
                    <div class="stat-value">{{ $fmtInr($kpis['remaining_amount']) }}</div>
                    <p class="stat-label">Remaining Sanction Amount</p>
                </div>
            </div>
        </div>
    </div>

    <div class="tabs-card">
        <div class="status-tabs">
            @foreach($tabs as $key => $meta)
                <a href="{{ route('goal.sanction-disbursement.index', $tabQuery($key)) }}" class="status-tab {{ $tab === $key ? 'active' : '' }}">
                    <i class="bi {{ $meta['icon'] }}"></i> {{ $meta['label'] }}
                    <span>({{ number_format($meta['count']) }})</span>
                </a>
            @endforeach
        </div>
    </div>

    <div class="filter-card">
        <form method="GET" action="{{ route('goal.sanction-disbursement.index') }}" id="sdFilterForm">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="hidden" name="date_from" id="date_from" value="{{ request('date_from') }}">
            <input type="hidden" name="date_to" id="date_to" value="{{ request('date_to') }}">
            <div class="filter-grid">
                <div>
                    <label for="q">Search</label>
                    <div class="sd-search">
                        <i class="bi bi-search"></i>
                        <input type="text" name="q" id="q" class="form-control" value="{{ request('q') }}"
                               placeholder="Case / Customer / Lender / Application Ref">
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
                    <label for="lender">Lender</label>
                    <select name="lender" id="lender" class="form-select" data-search="false">
                        <option value="">All Lenders</option>
                        @foreach($lenders as $ln)
                            <option value="{{ $ln }}" @selected(request('lender') === $ln)>{{ $ln }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="owner">Case Owner</label>
                    <select name="owner" id="owner" class="form-select" data-search="false">
                        <option value="">All Owners</option>
                        @foreach($owners as $o)
                            <option value="{{ $o->id }}" @selected((string) request('owner') === (string) $o->id)>{{ $o->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="filter-grid row-2">
                <div>
                    <label for="sdDateRange">Sanction Date</label>
                    <div class="sd-date">
                        <input type="text" id="sdDateRange" class="form-control" placeholder="Select Date Range" autocomplete="off" readonly>
                        <i class="bi bi-calendar3"></i>
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-apply"><i class="bi bi-funnel"></i> Apply</button>
                    <a href="{{ route('goal.sanction-disbursement.index', ['tab' => $tab]) }}" class="btn-reset"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                </div>
            </div>
        </form>
    </div>

    <div class="table-card">
        <div class="table-card-head">
            <div>
                <h5>Sanction &amp; Disbursement Register</h5>
                <p class="head-sub">Active sanctions with verified disbursement progress and remaining amount.</p>
            </div>
        </div>
        <div class="table-card-body">
            <div class="table-wrap">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Case Reference</th>
                            <th>Customer</th>
                            <th>Lender</th>
                            <th>Application Reference</th>
                            <th>Requested Amount</th>
                            <th>Sanctioned Amount</th>
                            <th>Sanction Date</th>
                            <th>Total Disbursed</th>
                            <th>Remaining Amount</th>
                            <th>Status</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($register as $i => $row)
                        @php
                            $case = $row->case;
                            $app = $row->application;
                            $s = $row->sanction;
                        @endphp
                        <tr>
                            <td data-label="#">{{ $rows->firstItem() + $i }}</td>
                            <td data-label="Case Reference">
                                @if($case)
                                    <a class="case-link" href="{{ route('goal.cases.show', $case) }}?tab=sanction">{{ $case->case_no }}</a>
                                @else — @endif
                            </td>
                            <td data-label="Customer">{{ $case?->customer_name ?: '—' }}</td>
                            <td data-label="Lender">
                                <span class="lender-cell">
                                    <span class="lender-mark">{{ $lenderInitials($app?->lender_name) }}</span>
                                    {{ $app?->lender_name ?: '—' }}
                                </span>
                            </td>
                            <td data-label="Application Reference">{{ $app?->application_ref ?: '—' }}</td>
                            <td data-label="Requested Amount">{{ $fmtInr($app?->requested_amount) }}</td>
                            <td data-label="Sanctioned Amount"><strong>{{ $fmtInr($s->sanction_amount) }}</strong></td>
                            <td data-label="Sanction Date">{{ optional($s->sanction_date)->format('d M Y') ?: '—' }}</td>
                            <td data-label="Total Disbursed">{{ $fmtInr($row->verified) }}</td>
                            <td data-label="Remaining Amount">{{ $fmtInr($row->remaining) }}</td>
                            <td data-label="Status"><span class="status-pill {{ $row->bucket }}">{{ $row->status_label }}</span></td>
                            <td class="col-actions" data-label="Actions">
                                <div class="actions-cell">
                                    @if($case)
                                        <a href="{{ route('goal.cases.show', $case) }}?tab=sanction" class="action-btn" title="View"><i class="bi bi-eye"></i></a>
                                        <a href="{{ route('goal.cases.show', $case) }}?tab=activity" class="action-btn" title="History"><i class="bi bi-clock-history"></i></a>
                                    @endif
                                    @if($canEdit && $case && $row->remaining > 0)
                                        <button type="button" class="action-btn" title="Record Disbursement"
                                                data-bs-toggle="modal" data-bs-target="#sdDisbModal"
                                                data-case-id="{{ $case->id }}"
                                                data-app-id="{{ $app?->id }}"
                                                data-sanction-id="{{ $s->id }}">
                                            <i class="bi bi-cash-coin"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12">
                                <div class="empty-state">
                                    <div>No active sanctions found.</div>
                                    <div class="small mt-1">Record a sanction against a lender application to populate this register.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($rows->total() > 0)
                <div class="table-footer-bar">
                    <div>Showing {{ $rows->firstItem() }}—{{ $rows->lastItem() }} of {{ $rows->total() }}</div>
                    <div>{{ $rows->links() }}</div>
                </div>
            @endif
        </div>
    </div>

    <div class="bottom-grid">
        <div class="side-card">
            <div class="side-card-head">
                <h5>Disbursement Records</h5>
                <p class="head-sub mb-0">Each disbursement is a separate record. Only verified amounts credit revenue.</p>
            </div>
            <div class="side-card-body">
                <div class="table-wrap">
                    <table class="d-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Case Reference</th>
                                <th>Lender Reference</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Verification</th>
                                <th>Evidence</th>
                                <th>Verified By</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($recentDisbursements as $i => $d)
                            @php $path = $evidencePath($d->evidence_note); @endphp
                            <tr>
                                <td data-label="#">{{ $i + 1 }}</td>
                                <td data-label="Case Reference">
                                    @if($d->loanCase)
                                        <a class="case-link" href="{{ route('goal.cases.show', $d->loanCase) }}?tab=disbursement">{{ $d->loanCase->case_no }}</a>
                                    @else — @endif
                                </td>
                                <td data-label="Lender Reference">{{ $d->bank_reference ?: '—' }}</td>
                                <td data-label="Date">{{ optional($d->disbursed_on)->format('d M Y') ?: '—' }}</td>
                                <td data-label="Amount">{{ $fmtInr($d->amount) }}</td>
                                <td data-label="Verification">
                                    @if($d->verified_at)
                                        <span class="status-pill verified">Verified</span>
                                    @else
                                        <span class="status-pill awaiting">Pending</span>
                                        @if($d->created_at)
                                            <div class="text-muted" style="font-size:.7rem;margin-top:2px">Queued {{ $d->created_at->diffForHumans() }}</div>
                                        @endif
                                    @endif
                                </td>
                                <td data-label="Evidence">
                                    @if($path)
                                        <a href="{{ asset('storage/'.$path) }}" target="_blank" class="action-btn" title="Evidence"><i class="bi bi-file-earmark-pdf"></i></a>
                                    @else — @endif
                                </td>
                                <td data-label="Verified By">{{ $d->verifier?->name ?: '—' }}</td>
                                <td class="col-actions" data-label="Actions">
                                    <div class="actions-cell">
                                        @if($d->loanCase)
                                            <a href="{{ route('goal.cases.show', $d->loanCase) }}?tab=disbursement" class="action-btn" title="Open"><i class="bi bi-eye"></i></a>
                                        @endif
                                        @if($canVerify && !$d->verified_at)
                                            <form method="POST" action="{{ route('goal.disbursements.verify', $d) }}" class="d-inline"
                                                  onsubmit="return confirm('Verify this disbursement? Revenue, PMS and Accounts will be triggered.');">
                                                @csrf
                                                <button type="submit" class="action-btn" title="Verify"><i class="bi bi-shield-check"></i></button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-3">No disbursement records yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="side-card">
            <div class="side-card-head">
                <h5>Workflow</h5>
                <p class="head-sub mb-0">From bank processing through verified disbursement.</p>
            </div>
            <div class="side-card-body">
                <div class="workflow">
                    @foreach($workflow as $idx => $step)
                        @php $state = $idx < $activeIdx ? 'done' : ($idx === $activeIdx ? 'active' : ''); @endphp
                        <div class="wf-step {{ $state }}">
                            <div class="wf-dot">
                                @if($state === 'done')
                                    <i class="bi bi-check-lg"></i>
                                @else
                                    <i class="bi bi-circle"></i>
                                @endif
                            </div>
                            <div class="wf-label">{{ $step['label'] }}</div>
                            <div class="wf-desc">{{ $step['desc'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="info-banner">
        <i class="bi bi-info-circle-fill"></i>
        <div>
            Partial disbursements are supported. Total disbursed cannot exceed the active sanction amount.
            Only <strong>verified</strong> disbursements credit revenue and open PMS &amp; Accounts queues in parallel (payout stays On Hold).
            Sanction revisions supersede the previous Active record and keep full history.
        </div>
    </div>
</div>

@if($canEdit)
{{-- Record Sanction --}}
<div class="modal fade sd-modal" id="sdSanctionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="" enctype="multipart/form-data" class="modal-content" id="sdSanctionForm">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Record Sanction</h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Loan Case <span class="text-danger">*</span></label>
                        <select id="sdSanCase" class="form-select" required>
                            <option value="">Select case...</option>
                            @foreach($casesForSanction as $c)
                                <option value="{{ $c->id }}">{{ $c->case_no }} — {{ $c->customer_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Lender Application <span class="text-danger">*</span></label>
                        <select id="sdSanApp" class="form-select" required disabled>
                            <option value="">Select application...</option>
                        </select>
                        <div class="hint">Recording a new sanction supersedes any Active sanction on this application.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sanctioned Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="sanction_amount" id="sdSanAmount" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sanction Date <span class="text-danger">*</span></label>
                        <input type="date" name="sanction_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Validity</label>
                        <input type="date" name="validity_until" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Sanction Terms</label>
                        <textarea name="terms" class="form-control" rows="2" maxlength="2000" placeholder="ROI, tenure, conditions..."></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Change Reason</label>
                        <input type="text" name="change_reason" class="form-control" maxlength="500" placeholder="Required for revisions">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Sanction Evidence <span class="text-danger">*</span></label>
                        <input type="file" name="evidence" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-sd-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-sd-primary"><i class="bi bi-check2"></i> Save Sanction</button>
            </div>
        </form>
    </div>
</div>

{{-- Record Disbursement --}}
<div class="modal fade sd-modal" id="sdDisbModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="" enctype="multipart/form-data" class="modal-content" id="sdDisbForm">
            @csrf
            <input type="hidden" name="goal_sanction_id" id="sdDisbSanctionId">
            <div class="modal-header">
                <h5 class="modal-title">Record Disbursement</h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Loan Case <span class="text-danger">*</span></label>
                        <select id="sdDisbCase" class="form-select" required>
                            <option value="">Select case...</option>
                            @foreach($casesForDisbursement as $c)
                                <option value="{{ $c->id }}">{{ $c->case_no }} — {{ $c->customer_name }}</option>
                            @endforeach
                        </select>
                        <div class="hint" id="sdDisbCapHint"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Lender Application</label>
                        <select name="goal_bank_application_id" id="sdDisbApp" class="form-select">
                            <option value="">Optional...</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Disbursement Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Disbursement Date <span class="text-danger">*</span></label>
                        <input type="date" name="disbursed_on" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Lender Reference <span class="text-danger">*</span></label>
                        <input type="text" name="bank_reference" class="form-control" maxlength="100" required placeholder="UTR / ref no.">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Supporting Evidence <span class="text-danger">*</span></label>
                        <input type="file" name="evidence" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Notes</label>
                        <input type="text" name="evidence_note" class="form-control" maxlength="500" placeholder="Optional note">
                    </div>
                    <div class="col-12">
                        <div class="hint mb-0">Status starts as <strong>Pending Verification</strong>. An authorized reviewer must verify before revenue / PMS / Accounts are triggered. Partial disbursements are allowed within the sanction cap.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-sd-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-sd-primary"><i class="bi bi-cash-coin"></i> Save Disbursement</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('sdFilterForm');
    if (form) {
        form.querySelectorAll('select').forEach(function (el) {
            el.addEventListener('change', function () { form.submit(); });
        });
        var q = document.getElementById('q');
        if (q) q.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); form.submit(); }
        });
        var rangeEl = document.getElementById('sdDateRange');
        var fromEl = document.getElementById('date_from');
        var toEl = document.getElementById('date_to');
        if (rangeEl && window.flatpickr) {
            var defaultDates = [];
            if (fromEl && fromEl.value) defaultDates.push(fromEl.value);
            if (toEl && toEl.value) defaultDates.push(toEl.value);
            flatpickr(rangeEl, {
                mode: 'range', dateFormat: 'Y-m-d', altInput: true, altFormat: 'd-m-Y', allowInput: false,
                defaultDate: defaultDates.length ? defaultDates : null,
                onClose: function (selectedDates) {
                    if (!fromEl || !toEl) return;
                    if (selectedDates.length === 0) { fromEl.value = ''; toEl.value = ''; form.submit(); return; }
                    if (selectedDates.length === 1) {
                        fromEl.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                        toEl.value = fromEl.value; form.submit(); return;
                    }
                    fromEl.value = flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                    toEl.value = flatpickr.formatDate(selectedDates[1], 'Y-m-d');
                    form.submit();
                }
            });
        }
    }

    var caseApps = @json($caseAppsJson);
    var caseDisb = @json($caseDisbJson);
    var sanStoreBase = @json(url('goal/bank-applications'));
    var disbStoreBase = @json(url('goal/cases'));

    function fillSelect(sel, items, placeholder) {
        sel.innerHTML = '';
        var opt0 = document.createElement('option');
        opt0.value = '';
        opt0.textContent = placeholder || 'Select...';
        sel.appendChild(opt0);
        (items || []).forEach(function (item) {
            var o = document.createElement('option');
            o.value = item.id;
            o.textContent = item.label;
            if (item.requested != null) o.setAttribute('data-requested', item.requested);
            sel.appendChild(o);
        });
        sel.disabled = !(items && items.length);
    }

    var sanCase = document.getElementById('sdSanCase');
    var sanApp = document.getElementById('sdSanApp');
    var sanForm = document.getElementById('sdSanctionForm');
    var sanAmount = document.getElementById('sdSanAmount');
    if (sanCase && sanApp && sanForm) {
        sanCase.addEventListener('change', function () {
            fillSelect(sanApp, caseApps[sanCase.value] || [], 'Select application...');
            sanForm.action = '';
        });
        sanApp.addEventListener('change', function () {
            var appId = sanApp.value;
            sanForm.action = appId ? (sanStoreBase + '/' + appId + '/sanctions') : '';
            var opt = sanApp.options[sanApp.selectedIndex];
            if (opt && opt.getAttribute('data-requested') && sanAmount && !sanAmount.value) {
                sanAmount.value = opt.getAttribute('data-requested');
            }
        });
        sanForm.addEventListener('submit', function (e) {
            if (!sanForm.action) {
                e.preventDefault();
                alert('Select a lender application first.');
            }
        });
    }

    var disbCase = document.getElementById('sdDisbCase');
    var disbApp = document.getElementById('sdDisbApp');
    var disbForm = document.getElementById('sdDisbForm');
    var disbSanction = document.getElementById('sdDisbSanctionId');
    var disbCapHint = document.getElementById('sdDisbCapHint');
    function syncDisbCase(caseId, appId, sanctionId) {
        if (!disbCase || !disbForm) return;
        if (caseId) disbCase.value = String(caseId);
        var meta = caseDisb[disbCase.value] || {};
        fillSelect(disbApp, meta.apps || [], 'Optional...');
        if (appId) disbApp.value = String(appId);
        if (disbSanction) disbSanction.value = sanctionId || meta.sanction_id || '';
        disbForm.action = disbCase.value ? (disbStoreBase + '/' + disbCase.value + '/disbursements') : '';
        if (disbCapHint) {
            disbCapHint.textContent = meta.sanction_amount
                ? ('Active sanction cap: ₹ ' + Number(meta.sanction_amount).toLocaleString('en-IN'))
                : '';
        }
    }
    if (disbCase) {
        disbCase.addEventListener('change', function () { syncDisbCase(disbCase.value); });
    }
    if (disbForm) {
        disbForm.addEventListener('submit', function (e) {
            if (!disbForm.action) {
                e.preventDefault();
                alert('Select a loan case first.');
            }
        });
    }
    var disbModal = document.getElementById('sdDisbModal');
    if (disbModal) {
        disbModal.addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            if (!btn) return;
            syncDisbCase(
                btn.getAttribute('data-case-id'),
                btn.getAttribute('data-app-id'),
                btn.getAttribute('data-sanction-id')
            );
        });
    }
})();
</script>
@endpush
