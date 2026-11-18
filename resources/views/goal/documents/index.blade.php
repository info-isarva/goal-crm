@extends('layouts.app')

@push('styles')
<style>
.doc-page{color:#1e293b}
.doc-page .page-hero{
    display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;
    gap:.85rem;margin-bottom:.85rem;
}
.doc-page .page-hero-left{min-width:0}
.doc-page .hero-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.doc-page a.stat-tile{text-decoration:none;color:inherit;display:flex;align-items:center;gap:.85rem;transition:box-shadow .15s,transform .15s}
.doc-page a.stat-tile:hover{box-shadow:0 4px 14px rgba(148,163,184,.2);transform:translateY(-1px)}
.doc-page a.stat-tile.is-active{outline:2px solid var(--theme-primary);outline-offset:1px}
.doc-page .breadcrumb-doc{
    display:flex;flex-wrap:wrap;align-items:center;gap:6px;
    margin:0 0 8px;padding:0;list-style:none;font-size:.78rem;color:#64748b;
}
.doc-page .breadcrumb-doc a{color:var(--theme-primary);text-decoration:none;font-weight:600}
.doc-page .breadcrumb-doc a:hover{text-decoration:underline}
.doc-page .breadcrumb-doc .sep{opacity:.5}
.doc-page .page-title{font-size:1.55rem;font-weight:800;color:#0f172a;margin:0;line-height:1.2;letter-spacing:-.02em}
.doc-page .page-sub{color:#64748b;font-size:.9rem;margin-top:4px;line-height:1.4}
/* Primary / cancel buttons — used in page header AND modals (modals sit outside .doc-page) */
.btn-doc-primary,
.doc-page .btn-upload{
    height:40px;border-radius:10px;border:1px solid transparent !important;
    background:var(--theme-primary) !important;
    color:#fff !important;
    padding:0 16px;font-weight:700;font-size:.88rem;line-height:1;
    display:inline-flex;align-items:center;justify-content:center;gap:7px;
    box-shadow:0 8px 18px var(--theme-primary-ring);
    text-decoration:none;cursor:pointer;
}
.btn-doc-primary:hover,
.btn-doc-primary:focus,
.doc-page .btn-upload:hover,
.doc-page .btn-upload:focus{
    background:var(--theme-primary-hover) !important;
    color:#fff !important;
    filter:none;
}
.btn-doc-primary:disabled{opacity:.65;cursor:not-allowed}
.btn-doc-cancel{
    height:40px;border-radius:10px;border:1px solid #cbd5e1 !important;
    background:#fff !important;color:#334155 !important;
    padding:0 16px;font-weight:600;font-size:.88rem;line-height:1;
    display:inline-flex;align-items:center;justify-content:center;gap:6px;
}
.btn-doc-cancel:hover{background:#f8fafc !important;color:#0f172a !important}
.btn-doc-danger{
    height:40px;border-radius:10px;border:1px solid transparent !important;
    background:#dc2626 !important;color:#fff !important;
    padding:0 16px;font-weight:700;font-size:.88rem;line-height:1;
    display:inline-flex;align-items:center;justify-content:center;gap:6px;
}
.btn-doc-danger:hover{background:#b91c1c !important;color:#fff !important}

.doc-modal .modal-content{
    border:1px solid #e8edf5;border-radius:16px;overflow:hidden;
    box-shadow:0 20px 50px rgba(15,23,42,.18);
}
.doc-modal .modal-header{
    padding:.85rem 1.05rem;border-bottom:1px solid #eef2f7;background:#fff;
    display:flex;align-items:center;justify-content:space-between;gap:.75rem;
}
.doc-modal .modal-title{font-size:1.05rem;font-weight:800;color:#0f172a;margin:0;line-height:1.3}
.doc-modal .modal-close{
    width:34px;height:34px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;
    color:#64748b;display:inline-flex;align-items:center;justify-content:center;
    padding:0;flex-shrink:0;cursor:pointer;
}
.doc-modal .modal-close:hover{background:#f8fafc;color:#0f172a}
.doc-modal .modal-body{padding:.9rem 1.05rem .75rem;background:#fff}
.doc-modal .modal-footer{
    padding:.8rem 1.05rem;border-top:1px solid #eef2f7;background:#f8fafc;
    gap:8px;
}
.doc-modal .form-label{
    font-size:.76rem;font-weight:700;color:#475569;margin-bottom:5px;
}
.doc-modal .form-control,
.doc-modal .form-select{
    min-height:40px;border-radius:10px;border:1px solid #d1d5db;font-size:.9rem;width:100%;
}
.doc-modal .form-control:focus,
.doc-modal .form-select:focus{
    border-color:var(--theme-primary);box-shadow:0 0 0 3px var(--theme-primary-ring);
}
.doc-modal .mb-3{margin-bottom:.75rem !important}
.doc-modal .form-text{font-size:.74rem;color:#94a3b8;margin-top:6px;line-height:1.4}
.doc-modal .file-field{
    border:1px dashed #cbd5e1;border-radius:12px;background:#f8fafc;padding:.65rem .75rem;
}
.doc-modal .file-field input[type="file"]{
    width:100%;font-size:.82rem;color:#475569;border:0;background:transparent;padding:0;min-height:0;
}
.doc-modal .file-field input[type="file"]::file-selector-button{
    height:34px;margin-right:10px;padding:0 12px;border:1px solid #cbd5e1;border-radius:8px;
    background:#fff;color:#334155;font-weight:650;font-size:.8rem;cursor:pointer;
}
@media (max-width:767.98px){
    .doc-modal .modal-header{padding:.75rem .85rem}
    .doc-modal .modal-title{font-size:.98rem}
    .doc-modal .modal-body{padding:.75rem .85rem}
    .doc-modal .modal-footer{padding:.7rem .85rem}
    .doc-modal .modal-footer .btn-doc-cancel,
    .doc-modal .modal-footer .btn-doc-primary,
    .doc-modal .modal-footer .btn-doc-danger{
        width:100%;justify-content:center;min-height:48px;height:48px;box-shadow:none;font-size:.84rem;
    }
    .doc-modal .mb-3{margin-bottom:.65rem !important}
    .doc-modal .form-control,
    .doc-modal .form-select{min-height:42px;font-size:.86rem}
}

.doc-page .kpi-row{margin-bottom:.7rem}
.doc-page .stat-tile{
    --tile-ink:#2563eb;--tile-soft:#dbeafe;--tile-wave:#93c5fd;
    position:relative;overflow:hidden;height:100%;
    border:1px solid #e8edf5;border-radius:14px;padding:.7rem .85rem;
    background:#fff;
    box-shadow:0 1px 2px rgba(15,23,42,.04),0 8px 18px rgba(148,163,184,.12);
    display:flex;flex-direction:row;align-items:center;gap:.75rem;
}
.doc-page .stat-tile::after{
    content:"";position:absolute;right:-12px;bottom:-22px;width:72px;height:56px;
    background:radial-gradient(ellipse at center, var(--tile-wave) 0%, transparent 70%);
    opacity:.45;pointer-events:none;
}
.doc-page .stat-tile.tone-blue{--tile-ink:#2563eb;--tile-soft:#dbeafe;--tile-wave:#93c5fd}
.doc-page .stat-tile.tone-orange{--tile-ink:#ea580c;--tile-soft:#ffedd5;--tile-wave:#fdba74}
.doc-page .stat-tile.tone-green{--tile-ink:#16a34a;--tile-soft:#dcfce7;--tile-wave:#86efac}
.doc-page .stat-tile.tone-red{--tile-ink:#dc2626;--tile-soft:#fee2e2;--tile-wave:#fca5a5}
.doc-page .stat-icon{
    position:relative;z-index:1;width:40px;height:40px;border-radius:12px;flex-shrink:0;
    display:inline-flex;align-items:center;justify-content:center;
    background:var(--tile-soft);color:var(--tile-ink);font-size:1.05rem;
}
.doc-page .stat-meta{position:relative;z-index:1;min-width:0;flex:1}
.doc-page .stat-value{margin:0;font-size:1.35rem;font-weight:800;color:#0f172a;line-height:1;letter-spacing:-.03em;white-space:nowrap}
.doc-page .stat-label{margin:4px 0 0;font-size:.78rem;font-weight:600;color:#64748b;line-height:1.2}

.doc-page .tabs-card,
.doc-page .filter-card,
.doc-page .table-card{
    background:#fff;border:1px solid #e8edf5;border-radius:16px;
    box-shadow:0 10px 28px rgba(148,163,184,.12);
}
.doc-page .tabs-card{padding:.6rem .75rem;margin-bottom:.7rem;overflow:hidden}
.doc-page .status-tabs{display:flex;flex-wrap:wrap;align-items:center;gap:6px}
.doc-page .status-tab{
    display:inline-flex;align-items:center;gap:7px;
    height:36px;padding:0 14px;border-radius:999px;
    border:1px solid var(--theme-200);background:#fff;color:var(--theme-primary-hover);
    font-size:.84rem;font-weight:650;text-decoration:none;flex:0 0 auto;white-space:nowrap;
}
.doc-page .status-tab .count{opacity:.85;font-weight:700}
.doc-page .status-tab:hover{background:var(--theme-50)}
.doc-page .status-tab.active{
    background:var(--theme-primary);border-color:var(--theme-primary);color:#fff;
    box-shadow:0 6px 14px var(--theme-primary-ring);
}

.doc-page .filter-card{padding:.9rem 1rem;margin-bottom:.7rem}
.doc-page .filter-grid{
    display:grid;
    grid-template-columns:minmax(200px,1.6fr) repeat(3,minmax(140px,1fr));
    gap:10px;align-items:end;
}
.doc-page .filter-grid.row-2{
    grid-template-columns:repeat(4,minmax(140px,1fr)) auto;
    margin-top:10px;
}
.doc-page .filter-grid label{
    display:block;font-size:.72rem;font-weight:700;color:#64748b;
    margin:0 0 6px;text-transform:uppercase;letter-spacing:.04em;
}
.doc-page .filter-grid .form-control,
.doc-page .filter-grid .form-select{
    min-height:38px;height:38px;border-radius:10px;border:1px solid #d1d5db;font-size:.9rem;background:#fff;
}
.doc-page .doc-search{position:relative}
.doc-page .doc-search > i{
    position:absolute;left:12px;top:50%;transform:translateY(-50%);
    color:#94a3b8;z-index:2;pointer-events:none;
}
.doc-page .doc-search .form-control{padding-left:38px}
.doc-page .doc-date{position:relative}
.doc-page .doc-date > i{
    position:absolute;right:12px;top:50%;transform:translateY(-50%);
    color:#94a3b8;pointer-events:none;
}
.doc-page .filter-actions{display:flex;align-items:end;gap:8px;flex-wrap:wrap}
.doc-page .btn-apply{
    height:38px;border-radius:10px;border:1px solid transparent !important;
    background:var(--theme-primary) !important;color:#fff !important;
    padding:0 16px;font-weight:700;font-size:.85rem;
    display:inline-flex;align-items:center;gap:6px;
    box-shadow:0 6px 14px var(--theme-primary-ring);
}
.doc-page .btn-apply:hover{background:var(--theme-primary-hover) !important;color:#fff !important}
.doc-page .btn-reset{
    height:38px;border-radius:10px;border:1px solid #cbd5e1;background:#fff;color:#475569;
    padding:0 14px;font-weight:600;font-size:.85rem;text-decoration:none;
    display:inline-flex;align-items:center;gap:6px;
}

.doc-page .table-card{margin-bottom:.7rem}
.doc-page .table-card-head{
    display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;
    gap:.5rem;padding:.75rem 1rem;border-bottom:1px solid #eef2f7;
}
.doc-page .table-card-head h5{margin:0;font-size:1.02rem;font-weight:800;color:#0f172a}
.doc-page .head-sub{margin:2px 0 0;font-size:.78rem;color:#64748b}
.doc-page .btn-export{
    height:36px;border-radius:10px;border:1px solid #cbd5e1;background:#fff;color:#334155;
    padding:0 14px;font-weight:650;font-size:.84rem;text-decoration:none;
    display:inline-flex;align-items:center;gap:6px;
}
.doc-page .table-card-body{padding:0}
.doc-page .docs-table-wrap{
    border:0;border-radius:0;overflow-x:auto;background:#fff;max-width:100%;
    -webkit-overflow-scrolling:touch;overscroll-behavior-x:contain;
    scrollbar-width:thin;scrollbar-color:#cbd5e1 transparent;
}
.doc-page .docs-table-wrap::-webkit-scrollbar{height:6px}
.doc-page .docs-table-wrap::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:999px}
.doc-page table{width:100%;min-width:1100px;margin:0;border-collapse:separate;border-spacing:0}
.doc-page table thead th{
    font-size:.7rem;text-transform:uppercase;letter-spacing:.04em;
    color:#64748b;font-weight:700;background:#f1f5f9;
    border-bottom:1px solid #e2e8f0;white-space:nowrap;padding:.75rem .85rem;
}
.doc-page table tbody td{
    vertical-align:middle;font-size:.875rem;color:#334155;
    border-color:#eef2f7;padding:.75rem .85rem;white-space:nowrap;
}
.doc-page table tbody tr:hover{background:#f8fbff}
.doc-page .case-link{
    font-weight:800;color:var(--theme-primary);text-decoration:none;
    font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.82rem;
}
.doc-page .case-link:hover{text-decoration:underline}
.doc-page .file-cell{display:inline-flex;align-items:center;gap:8px;max-width:220px}
.doc-page .file-cell i{color:#ef4444}
.doc-page .file-name{overflow:hidden;text-overflow:ellipsis;font-weight:600;color:#0f172a}
.doc-page .req-pill{
    display:inline-flex;margin-left:6px;padding:.1rem .4rem;border-radius:999px;
    font-size:.65rem;font-weight:700;background:#fee2e2;color:#b91c1c;
}
.doc-page .status-pill{
    display:inline-flex;align-items:center;justify-content:center;
    padding:.28rem .7rem;border-radius:999px;font-size:.74rem;font-weight:700;
}
.doc-page .status-pill.pending{background:#ffedd5;color:#c2410c}
.doc-page .status-pill.verified{background:#dcfce7;color:#15803d}
.doc-page .status-pill.rejected{background:#fee2e2;color:#b91c1c}
.doc-page .actions-cell{display:inline-flex;align-items:center;gap:4px}
.doc-page .action-btn{
    width:32px;height:32px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#475569;
    display:inline-flex;align-items:center;justify-content:center;text-decoration:none;padding:0;
}
.doc-page .action-btn:hover{background:#f8fafc;color:var(--theme-primary)}
.doc-page .action-btn.ok:hover{color:#15803d}
.doc-page .action-btn.bad:hover{color:#dc2626}
.doc-page .table-footer-bar{
    display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;
    gap:.75rem;padding:.75rem 1.15rem;border-top:1px solid #eef2f7;font-size:.82rem;color:#64748b;
}
.doc-page .table-card-body:has(> .empty-state){padding:0 !important}
.doc-page .empty-state{
    display:flex;align-items:center;justify-content:flex-start;gap:12px;
    padding:.85rem 1rem;margin:0;color:#64748b;text-align:left;
    background:#f8fafc;
}
.doc-page .empty-state .illus{
    width:40px;height:40px;border-radius:12px;margin:0;flex-shrink:0;
    display:flex;align-items:center;justify-content:center;
    background:var(--theme-50);color:var(--theme-primary);font-size:1.1rem;
}
.doc-page .empty-state .copy{min-width:0}
.doc-page .empty-state .copy strong{
    display:block;font-size:.88rem;font-weight:700;color:#475569;line-height:1.25;
}
.doc-page .empty-state .copy span{
    display:block;font-size:.78rem;color:#94a3b8;margin-top:2px;line-height:1.35;
}
.preview-frame,
.doc-page .preview-frame{
    width:100%;min-height:70vh;border:0;border-radius:12px;background:#0f172a;
}
@media (max-width:991.98px){
    .doc-page .status-tabs{
        flex-wrap:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none;
    }
    .doc-page .status-tabs::-webkit-scrollbar{display:none;height:0}
    .doc-page .filter-grid,
    .doc-page .filter-grid.row-2{grid-template-columns:1fr 1fr}
    .doc-page .filter-grid.row-2 .filter-actions{grid-column:1 / -1}
}
@media (max-width:575.98px){
    .doc-page.container-fluid{padding:.65rem !important}
    .doc-page .page-hero{margin-bottom:.65rem;gap:.55rem}
    .doc-page .page-title{font-size:1.22rem}
    .doc-page .page-sub{font-size:.8rem;margin-top:2px}
    .doc-page .btn-doc-primary{
        width:100%;justify-content:center;height:38px;box-shadow:none;font-size:.84rem;
    }

    .doc-page .kpi-row{margin-bottom:.55rem}
    .doc-page .kpi-row > [class*="col-"]{padding-left:.3rem;padding-right:.3rem}
    .doc-page .stat-tile{padding:.5rem .55rem;gap:.45rem;border-radius:12px;min-height:78px}
    .doc-page .stat-icon{width:30px;height:30px;border-radius:9px;font-size:.88rem}
    .doc-page .stat-value{font-size:1.1rem}
    .doc-page .stat-label{font-size:.68rem;margin-top:2px}

    .doc-page .tabs-card{padding:.5rem .55rem;margin-bottom:.55rem;border-radius:12px}
    .doc-page .status-tab{height:32px;padding:0 11px;font-size:.78rem;gap:5px}

    .doc-page .filter-card{padding:.65rem .7rem;margin-bottom:.55rem;border-radius:12px}
    .doc-page .filter-grid{grid-template-columns:1fr 1fr !important;gap:8px}
    .doc-page .filter-grid > div:first-child{grid-column:1 / -1}
    .doc-page .filter-grid.row-2{grid-template-columns:1fr 1fr !important;margin-top:8px}
    .doc-page .filter-grid.row-2 .filter-actions{grid-column:1 / -1}
    .doc-page .filter-grid label{margin-bottom:4px;font-size:.66rem}
    .doc-page .filter-grid .form-control,
    .doc-page .filter-grid .form-select{min-height:36px;height:36px;font-size:.84rem}
    .doc-page .filter-actions{width:100%;display:grid;grid-template-columns:1fr 1fr;gap:8px}
    .doc-page .btn-apply,.doc-page .btn-reset{width:100%;justify-content:center;height:36px;box-shadow:none}

    .doc-page .table-card{border-radius:12px;margin-bottom:.55rem}
    .doc-page .table-card-head{padding:.55rem .7rem}
    .doc-page .table-card-head h5{font-size:.95rem}
    .doc-page .head-sub{display:none}
    .doc-page .btn-export{height:32px;padding:0 10px;font-size:.78rem}
    .doc-page .table-footer-bar{padding:.55rem .7rem}
    .doc-page .empty-state{padding:.7rem .75rem;gap:10px}
    .doc-page .empty-state .illus{width:36px;height:36px;border-radius:10px;font-size:1rem}
    .doc-page .empty-state .copy strong{font-size:.84rem}
    .doc-page .empty-state .copy span{font-size:.74rem}

    /* Dense document cards */
    .doc-page .table-card-body{padding:.5rem .55rem !important}
    .doc-page .docs-table-wrap > table tr{
        margin:0 0 .5rem !important;
        padding:.45rem .55rem !important;
        border-radius:10px !important;
        box-shadow:0 2px 8px rgba(148,163,184,.1) !important;
    }
    .doc-page .docs-table-wrap > table td{
        padding:.18rem 0 !important;
        font-size:.8rem !important;
        gap:.45rem !important;
        line-height:1.25 !important;
        align-items:flex-start !important;
    }
    .doc-page .docs-table-wrap > table td::before{
        font-size:.62rem !important;
        flex:0 0 36% !important;
        max-width:36% !important;
        padding-top:.1rem;
    }
    .doc-page .docs-table-wrap > table td > *{max-width:64%;overflow-wrap:anywhere;word-break:break-word}
    .doc-page .docs-table-wrap > table td[data-label="#"],
    .doc-page .docs-table-wrap > table td[data-label="Reviewer"],
    .doc-page .docs-table-wrap > table td[data-label="Uploaded By"]{
        display:none !important;
    }
    .doc-page .docs-table-wrap > table td[data-label="Case Reference"]{
        padding-bottom:.3rem !important;margin-bottom:.1rem !important;
        border-bottom:1px solid #f1f5f9 !important;
    }
    .doc-page .docs-table-wrap > table td[data-label="Case Reference"]::before{content:"Case"}
    .doc-page .docs-table-wrap > table td[data-label="Document"]::before{content:"Document"}
    .doc-page .docs-table-wrap > table td[data-label="Uploaded On"]::before{content:"Uploaded"}
    .doc-page .docs-table-wrap > table td[data-label="Status"]::before{content:"Status"}
    .doc-page .docs-table-wrap > table td.col-actions{
        padding-top:.35rem !important;margin-top:.1rem !important;align-items:center !important;
    }
    .doc-page .file-cell{max-width:100%}
    .doc-page .action-btn{width:30px;height:30px}
}
</style>
@endpush

@section('content')
@php
    $canUpload = $canUpload ?? false;
    $canVerify = $canVerify ?? false;
    $statusLabel = fn ($s) => $s === 'Pending' ? 'Pending Verification' : $s;
    $statusClass = fn ($s) => match ($s) {
        'Verified' => 'verified',
        'Rejected' => 'rejected',
        default => 'pending',
    };
    $fileIcon = function ($name) {
        $ext = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));
        return match ($ext) {
            'pdf' => 'bi-file-earmark-pdf-fill',
            'jpg', 'jpeg', 'png', 'webp' => 'bi-file-earmark-image',
            default => 'bi-file-earmark-text',
        };
    };
    $tabQuery = fn ($key) => array_filter(array_merge(request()->except('page'), ['tab' => $key]));
@endphp

<div class="container-fluid p-4 doc-page">
    <div class="page-hero">
        <div class="page-hero-left">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb-doc">
                    <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="sep">/</li>
                    <li><a href="{{ route('goal.cases.index') }}">Loan Processing</a></li>
                    <li class="sep">/</li>
                    <li>Documents</li>
                </ol>
            </nav>
            <h1 class="page-title">Documents</h1>
            <div class="page-sub">Centralized document register — verification, reviewer history and case workspace links.</div>
        </div>
        <div class="hero-actions">
            <a href="{{ route('goal.cases.index') }}" class="btn-doc-cancel" style="text-decoration:none"><i class="bi bi-briefcase"></i> Cases</a>
            <a href="{{ route('goal.bank-processing.index') }}" class="btn-doc-cancel" style="text-decoration:none"><i class="bi bi-bank"></i> Bank</a>
            <a href="{{ route('goal.allocation.index') }}" class="btn-doc-cancel" style="text-decoration:none"><i class="bi bi-people"></i> Allocation</a>
            <a href="{{ route('goal.documents.export', request()->query()) }}" class="btn-doc-cancel" style="text-decoration:none"><i class="bi bi-download"></i> Export</a>
            @if($canUpload)
                <button type="button" class="btn-doc-primary" data-bs-toggle="modal" data-bs-target="#docUploadModal">
                    <i class="bi bi-upload"></i> Upload Documents
                </button>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0 small">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row g-2 kpi-row">
        <div class="col-6 col-lg-3">
            <a class="stat-tile tone-blue {{ $tab === 'all' ? 'is-active' : '' }}" href="{{ route('goal.documents.index', $tabQuery('all')) }}">
                <span class="stat-icon"><i class="bi bi-files"></i></span>
                <div class="stat-meta">
                    <div class="stat-value">{{ number_format($kpis['total']) }}</div>
                    <p class="stat-label">Total Documents</p>
                </div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a class="stat-tile tone-orange {{ $tab === 'pending' ? 'is-active' : '' }}" href="{{ route('goal.documents.index', $tabQuery('pending')) }}">
                <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
                <div class="stat-meta">
                    <div class="stat-value">{{ number_format($kpis['pending']) }}</div>
                    <p class="stat-label">Pending Verification</p>
                </div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a class="stat-tile tone-green {{ $tab === 'verified' ? 'is-active' : '' }}" href="{{ route('goal.documents.index', $tabQuery('verified')) }}">
                <span class="stat-icon"><i class="bi bi-check2-circle"></i></span>
                <div class="stat-meta">
                    <div class="stat-value">{{ number_format($kpis['verified']) }}</div>
                    <p class="stat-label">Verified</p>
                </div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a class="stat-tile tone-red {{ $tab === 'rejected' ? 'is-active' : '' }}" href="{{ route('goal.documents.index', $tabQuery('rejected')) }}">
                <span class="stat-icon"><i class="bi bi-x-circle"></i></span>
                <div class="stat-meta">
                    <div class="stat-value">{{ number_format($kpis['rejected']) }}</div>
                    <p class="stat-label">Rejected</p>
                </div>
            </a>
        </div>
    </div>

    <div class="tabs-card">
        <div class="status-tabs">
            @foreach($tabs as $key => $meta)
                <a href="{{ route('goal.documents.index', $tabQuery($key)) }}"
                   class="status-tab {{ $tab === $key ? 'active' : '' }}">
                    <i class="bi {{ $meta['icon'] }}"></i>
                    {{ $meta['label'] }}
                    <span class="count">({{ number_format($meta['count']) }})</span>
                </a>
            @endforeach
        </div>
    </div>

    <div class="filter-card">
        <form method="GET" action="{{ route('goal.documents.index') }}" id="docFilterForm">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="hidden" name="date_from" id="date_from" value="{{ request('date_from') }}">
            <input type="hidden" name="date_to" id="date_to" value="{{ request('date_to') }}">

            <div class="filter-grid">
                <div>
                    <label for="q">Search</label>
                    <div class="doc-search">
                        <i class="bi bi-search"></i>
                        <input type="text" name="q" id="q" class="form-control"
                               placeholder="Case Reference / Customer Name..." value="{{ request('q') }}">
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
                    <label for="case_id">Case Reference</label>
                    <select name="case_id" id="case_id" class="form-select">
                        <option value="">All Cases</option>
                        @foreach($cases as $c)
                            <option value="{{ $c->id }}" @selected((string) request('case_id') === (string) $c->id)>
                                {{ $c->case_no }} — {{ $c->customer_name }}
                            </option>
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
            </div>

            <div class="filter-grid row-2">
                <div>
                    <label for="doc_type">Document Type</label>
                    <select name="doc_type" id="doc_type" class="form-select" data-search="false">
                        <option value="">All Types</option>
                        @foreach($docTypes as $type)
                            <option value="{{ $type }}" @selected(request('doc_type') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status">Verification Status</label>
                    <select name="status" id="status" class="form-select" data-search="false" @disabled($tab !== 'all')>
                        <option value="">All Status</option>
                        <option value="Pending" @selected(request('status') === 'Pending')>Pending Verification</option>
                        <option value="Verified" @selected(request('status') === 'Verified')>Verified</option>
                        <option value="Rejected" @selected(request('status') === 'Rejected')>Rejected</option>
                    </select>
                </div>
                <div>
                    <label for="docDateRange">Uploaded Date</label>
                    <div class="doc-date">
                        <input type="text" id="docDateRange" class="form-control" placeholder="Select Date Range" autocomplete="off" readonly>
                        <i class="bi bi-calendar3"></i>
                    </div>
                </div>
                <div>
                    <label for="reviewer">Reviewer</label>
                    <select name="reviewer" id="reviewer" class="form-select" data-search="false">
                        <option value="">All Reviewers</option>
                        @foreach($reviewers as $rev)
                            <option value="{{ $rev }}" @selected(request('reviewer') === $rev)>{{ $rev }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-apply"><i class="bi bi-funnel"></i> Apply</button>
                    <a href="{{ route('goal.documents.index', ['tab' => $tab]) }}" class="btn-reset"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                </div>
            </div>
        </form>
    </div>

    <div class="table-card">
        <div class="table-card-head">
            <div>
                <h5>Document Register</h5>
                <p class="head-sub">{{ number_format($documents->total()) }} document{{ $documents->total() === 1 ? '' : 's' }} in this view</p>
            </div>
            <a class="btn-export" href="{{ route('goal.documents.export', request()->query()) }}">
                <i class="bi bi-download"></i> Export
            </a>
        </div>
        <div class="table-card-body">
            @if($documents->total() === 0)
                <div class="empty-state">
                    <div class="illus"><i class="bi bi-file-earmark-text"></i></div>
                    <div class="copy">
                        <strong>No documents found.</strong>
                        <span>Upload documents to a case to populate this register.</span>
                    </div>
                </div>
            @else
                <div class="docs-table-wrap">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Case Reference</th>
                                <th>Customer</th>
                                <th>Document Type</th>
                                <th>File Name</th>
                                <th>Uploaded By</th>
                                <th>Uploaded On</th>
                                <th>Verification Status</th>
                                <th>Reviewer</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($documents as $i => $doc)
                            @php
                                $uploadedOn = null;
                                if (!empty($doc['uploaded_at'])) {
                                    try { $uploadedOn = \Carbon\Carbon::parse($doc['uploaded_at'])->format('d M Y'); } catch (\Throwable) { $uploadedOn = $doc['uploaded_at']; }
                                }
                                $isPending = in_array($doc['status'], ['Pending'], true);
                            @endphp
                            <tr>
                                <td data-label="#">{{ $documents->firstItem() + $i }}</td>
                                <td data-label="Case Reference">
                                    <a class="case-link" href="{{ route('goal.cases.show', $doc['case_id']) }}?tab=documents">
                                        {{ $doc['case_no'] }}
                                    </a>
                                </td>
                                <td data-label="Customer">{{ $doc['customer_name'] ?: '—' }}</td>
                                <td data-label="Document">
                                    {{ $doc['label'] }}
                                    @if(!empty($doc['mandatory']))<span class="req-pill">Required</span>@endif
                                </td>
                                <td data-label="File">
                                    <span class="file-cell" title="{{ $doc['file_name'] }}">
                                        <i class="bi {{ $fileIcon($doc['file_name']) }}"></i>
                                        <span class="file-name">{{ $doc['file_name'] ?: '—' }}</span>
                                    </span>
                                </td>
                                <td data-label="Uploaded By">{{ $doc['uploaded_by'] ?: '—' }}</td>
                                <td data-label="Uploaded On">{{ $uploadedOn ?: '—' }}</td>
                                <td data-label="Status">
                                    <span class="status-pill {{ $statusClass($doc['status']) }}">{{ $statusLabel($doc['status']) }}</span>
                                </td>
                                <td data-label="Reviewer">{{ $doc['reviewer'] ?: '—' }}</td>
                                <td class="col-actions" data-label="Actions">
                                    <div class="actions-cell">
                                        @if(!empty($doc['preview_url']))
                                            <button type="button" class="action-btn"
                                                    data-doc-preview
                                                    data-url="{{ $doc['preview_url'] }}"
                                                    data-title="{{ $doc['label'] }} — {{ $doc['case_no'] }}"
                                                    title="Preview">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <a href="{{ $doc['download_url'] }}" class="action-btn" title="Download"><i class="bi bi-download"></i></a>
                                        @else
                                            <span class="action-btn" title="File unavailable"><i class="bi bi-eye-slash"></i></span>
                                        @endif
                                        <div class="dropdown">
                                            <button type="button" class="action-btn" data-bs-toggle="dropdown" aria-expanded="false" title="More">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('goal.cases.show', $doc['case_id']) }}?tab=documents">
                                                        <i class="bi bi-folder2-open me-2"></i> Open Loan Case Workspace
                                                    </a>
                                                </li>
                                                @if($canVerify && $isPending && !empty($doc['preview_url']))
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" action="{{ route('goal.documents.verify', $doc['case_id']) }}">
                                                            @csrf
                                                            <input type="hidden" name="key" value="{{ $doc['key'] }}">
                                                            <input type="hidden" name="party" value="{{ $doc['party'] }}">
                                                            <input type="hidden" name="action" value="Verified">
                                                            <button type="submit" class="dropdown-item text-success">
                                                                <i class="bi bi-check2-circle me-2"></i> Verify
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <button type="button" class="dropdown-item text-danger"
                                                                data-bs-toggle="modal" data-bs-target="#docRejectModal"
                                                                data-case-id="{{ $doc['case_id'] }}"
                                                                data-doc-key="{{ $doc['key'] }}"
                                                                data-doc-party="{{ $doc['party'] }}">
                                                            <i class="bi bi-x-circle me-2"></i> Reject
                                                        </button>
                                                    </li>
                                                @endif
                                                @if(!empty($doc['rejection_reason']))
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><h6 class="dropdown-header">Rejection reason</h6></li>
                                                    <li><span class="dropdown-item-text small text-wrap">{{ $doc['rejection_reason'] }}</span></li>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="table-footer-bar">
                    <div>Showing {{ $documents->firstItem() }}–{{ $documents->lastItem() }} of {{ $documents->total() }}</div>
                    <div>{{ $documents->links() }}</div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Secure preview modal --}}
<div class="modal fade doc-modal" id="docPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="docPreviewTitle">Document Preview</h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body p-2">
                <iframe id="docPreviewFrame" class="preview-frame" title="Secure document viewer"></iframe>
            </div>
        </div>
    </div>
</div>

@if($canUpload)
<div class="modal fade doc-modal" id="docUploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="{{ route('goal.documents.upload') }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Upload Documents</h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Case Reference <span class="text-danger">*</span></label>
                    <select name="case_id" class="form-select" required>
                        <option value="">Select case...</option>
                        @foreach($cases as $c)
                            <option value="{{ $c->id }}">{{ $c->case_no }} — {{ $c->customer_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Document Key <span class="text-danger">*</span></label>
                    <input type="text" name="key" class="form-control" required maxlength="60" placeholder="e.g. pan">
                </div>
                <div class="mb-3">
                    <label class="form-label">Document Type <span class="text-danger">*</span></label>
                    <input type="text" name="label" class="form-control" required maxlength="120" placeholder="e.g. PAN Card">
                </div>
                <div class="mb-3">
                    <label class="form-label">Party</label>
                    <select name="party" class="form-select">
                        <option value="Applicant">Applicant</option>
                        <option value="Co-Applicant">Co-Applicant</option>
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label">File <span class="text-danger">*</span></label>
                    <div class="file-field">
                        <input type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png,.webp">
                    </div>
                    <div class="form-text">PDF / JPG / PNG / WEBP · max 5 MB. Uploads start as Pending Verification and are audited.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-doc-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-doc-primary"><i class="bi bi-upload"></i> Upload</button>
            </div>
        </form>
    </div>
</div>
@endif

@if($canVerify)
<div class="modal fade doc-modal" id="docRejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="" class="modal-content" id="docRejectForm">
            @csrf
            <input type="hidden" name="key" id="docRejectKey">
            <input type="hidden" name="party" id="docRejectParty" value="Applicant">
            <input type="hidden" name="action" value="Rejected">
            <div class="modal-header">
                <h5 class="modal-title">Reject Document</h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Rejection reason <span class="text-danger">*</span></label>
                <textarea name="rejection_reason" class="form-control" rows="3" required maxlength="500"
                          placeholder="Mandatory reason recorded in the audit trail"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-doc-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-doc-danger"><i class="bi bi-x-circle"></i> Reject</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('docFilterForm');
    if (form) {
        form.querySelectorAll('select').forEach(function (el) {
            el.addEventListener('change', function () { form.submit(); });
        });
        var q = document.getElementById('q');
        if (q) {
            q.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); form.submit(); }
            });
        }
        var rangeEl = document.getElementById('docDateRange');
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
                        fromEl.value = ''; toEl.value = ''; form.submit(); return;
                    }
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

    var previewModal = document.getElementById('docPreviewModal');
    var previewFrame = document.getElementById('docPreviewFrame');
    var previewTitle = document.getElementById('docPreviewTitle');
    document.querySelectorAll('[data-doc-preview]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (previewFrame) previewFrame.src = btn.getAttribute('data-url') || '';
            if (previewTitle) previewTitle.textContent = btn.getAttribute('data-title') || 'Document Preview';
            if (previewModal && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(previewModal).show();
            }
        });
    });
    if (previewModal) {
        previewModal.addEventListener('hidden.bs.modal', function () {
            if (previewFrame) previewFrame.src = 'about:blank';
        });
    }

    var rejectModal = document.getElementById('docRejectModal');
    var rejectForm = document.getElementById('docRejectForm');
    if (rejectModal && rejectForm) {
        rejectModal.addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            if (!btn) return;
            var caseId = btn.getAttribute('data-case-id');
            rejectForm.action = @json(url('goal/documents')).replace(/\/?$/, '') + '/' + caseId + '/verify';
            document.getElementById('docRejectKey').value = btn.getAttribute('data-doc-key') || '';
            document.getElementById('docRejectParty').value = btn.getAttribute('data-doc-party') || 'Applicant';
        });
    }
})();
</script>
@endpush
