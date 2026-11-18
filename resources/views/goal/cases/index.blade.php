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
.lc-page .lc-hero-links{display:flex;flex-wrap:wrap;gap:8px;margin-top:.75rem}
.lc-page .lc-hero-link{
    height:34px;padding:0 12px;border-radius:9px;border:1px solid #dbe3f0;background:#fff;color:#334155;
    font-size:.78rem;font-weight:650;text-decoration:none;display:inline-flex;align-items:center;gap:5px;
}
.lc-page .lc-hero-link:hover{background:#f8fafc;color:#0f172a}
.lc-page a.stat-tile{text-decoration:none;color:inherit;transition:box-shadow .15s,transform .15s}
.lc-page a.stat-tile:hover{border-color:var(--tile-soft);box-shadow:0 4px 14px var(--tile-ring);transform:translateY(-1px)}
.lc-page a.stat-tile.is-active{outline:2px solid var(--theme-primary);outline-offset:1px}
.lc-page .lc-hero-main{
    position:relative;z-index:1;display:flex;align-items:center;gap:14px;min-width:0;
}
.lc-page .page-hero-icon{
    width:52px;height:52px;border-radius:14px;flex-shrink:0;
    background:linear-gradient(145deg,#4f46e5 0%,var(--theme-primary) 55%,#7c3aed 100%);
    color:#fff;display:inline-flex;align-items:center;justify-content:center;
    font-size:1.3rem;box-shadow:0 12px 24px rgba(79,70,229,.28);
}
.lc-page .page-title{font-size:1.55rem;font-weight:800;color:#0f172a;margin:0;line-height:1.2;letter-spacing:-.02em}
.lc-page .page-sub{color:#64748b;font-size:.9rem;margin-top:4px;line-height:1.4}
.lc-page .lc-hero-art{
    position:relative;z-index:1;flex-shrink:0;width:118px;height:96px;
    display:flex;align-items:center;justify-content:center;
}
.lc-page .lc-hero-art svg{width:110px;height:96px;filter:drop-shadow(0 10px 18px var(--theme-primary-ring))}

.lc-page .kpi-row{margin-bottom:.85rem}
.lc-page .stat-tile{
    --tile-ink:var(--theme-primary-hover);--tile-soft:var(--theme-100);--tile-fill:#fff;--tile-ring:var(--theme-primary-ring);
    position:relative;overflow:hidden;height:100%;min-height:0;
    border:1px solid var(--theme-card-border, #E2E8F0);border-radius:12px;padding:.7rem .85rem;
    background:var(--tile-fill);
    box-shadow:0 1px 2px rgba(15,23,42,.04),0 6px 14px rgba(148,163,184,.10);
    display:flex;flex-direction:row;align-items:center;gap:.75rem;
    transition:border-color .15s ease,box-shadow .15s ease;
}
.lc-page .stat-tile:hover{border-color:var(--tile-soft);box-shadow:0 4px 14px var(--tile-ring)}
.lc-page .stat-tile::before,
.lc-page .stat-tile::after{content:none}
.lc-page .stat-tile.tone-blue{--tile-ink:var(--theme-primary-hover);--tile-soft:var(--theme-200);--tile-ring:var(--theme-primary-ring);border-left:3px solid var(--theme-primary)}
.lc-page .stat-tile.tone-orange{--tile-ink:#c2410c;--tile-soft:#fdba74;--tile-ring:rgba(234,88,12,.12);border-left:3px solid #ea580c}
.lc-page .stat-tile.tone-green{--tile-ink:#15803d;--tile-soft:#bbf7d0;--tile-ring:rgba(22,163,74,.12);border-left:3px solid #16a34a}
.lc-page .stat-tile.tone-purple{--tile-ink:#6d28d9;--tile-soft:#c4b5fd;--tile-ring:rgba(124,58,237,.12);border-left:3px solid #7c3aed}
.lc-page .stat-tile.tone-red{--tile-ink:#b91c1c;--tile-soft:#fecaca;--tile-ring:rgba(220,38,38,.12);border-left:3px solid #dc2626}
.lc-page .stat-icon{
    width:34px;height:34px;border-radius:10px;flex-shrink:0;
    display:inline-flex;align-items:center;justify-content:center;
    background:color-mix(in srgb, var(--tile-soft) 55%, #fff);color:var(--tile-ink);font-size:.95rem;
}
.lc-page .stat-body{display:contents}
.lc-page .stat-meta{min-width:0;flex:1 1 auto}
.lc-page .stat-label{margin:0;font-size:.72rem;font-weight:700;color:#64748b;letter-spacing:.02em;text-transform:uppercase}
.lc-page .stat-chip{
    display:block;margin-top:2px;padding:0;border-radius:0;background:transparent;
    color:#94a3b8;font-size:.68rem;font-weight:500;line-height:1.25;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}
.lc-page .stat-value-row{display:contents}
.lc-page .stat-value{margin:0 0 0 auto;font-size:1.35rem;font-weight:800;color:#0f172a;line-height:1;letter-spacing:-.02em;flex-shrink:0}
.lc-page .stat-trend{
    display:inline-flex;align-items:center;gap:3px;margin-left:6px;
    padding:.14rem .4rem;border-radius:999px;font-size:.65rem;font-weight:700;line-height:1;
}
.lc-page .stat-trend.up{background:#dcfce7;color:#15803d}
.lc-page .stat-trend.down{background:#fee2e2;color:#b91c1c}
.lc-page .doc-progress{
    display:inline-flex;align-items:center;gap:6px;
    padding:.28rem .65rem;border-radius:999px;font-size:.74rem;font-weight:700;
    background:#f1f5f9;color:#334155;
}
.lc-page .doc-progress.warn{background:#ffedd5;color:#c2410c}
.lc-page .doc-progress.ok{background:#dcfce7;color:#15803d}
.lc-page .doc-progress.bad{background:#fee2e2;color:#b91c1c}

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
    background:var(--theme-primary);color:#fff;
    box-shadow:0 6px 14px var(--theme-primary-ring);
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
    background:linear-gradient(90deg,var(--theme-primary),#7c3aed);color:#fff;
    padding:0 16px;font-weight:700;font-size:.85rem;text-decoration:none;
    display:inline-flex;align-items:center;gap:6px;
    box-shadow:0 6px 14px var(--theme-primary-ring);
}
.lc-page .btn-create-sm:hover{filter:brightness(1.05);color:#fff}

.lc-page .table-card-head{
    display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;
    gap:.65rem;padding:.85rem 1.15rem;border-bottom:1px solid #eef2f7;
}
.lc-page .table-head-left{display:flex;align-items:flex-start;gap:12px;min-width:0}
.lc-page .table-card-head h5{margin:0;font-size:1.08rem;font-weight:800;color:#0f172a}
.lc-page .section-icon{
    width:42px;height:42px;border-radius:12px;flex-shrink:0;
    background:linear-gradient(145deg,var(--theme-100),var(--theme-200));color:var(--theme-primary-hover);
    display:inline-flex;align-items:center;justify-content:center;font-size:1.1rem;
}
.lc-page .head-sub{margin:4px 0 0;font-size:.82rem;color:#64748b}
.lc-page .table-card-body{padding:0}
.lc-page .cases-table-wrap{
    border:0;border-radius:0;overflow-x:auto;
    -webkit-overflow-scrolling:touch;background:#fff;
}
.lc-page .cases-table-wrap table{min-width:1100px;width:100%;margin:0}
.lc-page.is-module-hub .cases-table-wrap table{min-width:920px}
.lc-page .person-cell{display:flex;flex-direction:column;gap:2px;min-width:0}
.lc-page .person-cell .p-name{font-weight:700;color:#0f172a;line-height:1.25}
.lc-page .person-cell .p-role{font-size:.72rem;color:#64748b;font-weight:600}
.lc-page .muted-dash{color:#94a3b8}
.lc-page .lead-ref{
    font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
    font-size:.78rem;font-weight:700;color:#475569;text-decoration:none;
}
.lc-page .lead-ref:hover{color:var(--theme-primary);text-decoration:underline}
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
    font-weight:800;color:var(--theme-primary);text-decoration:none;
    font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.82rem;
}
.lc-page .case-link:hover{color:var(--theme-primary-hover);text-decoration:underline}
.lc-page .cust-cell{display:flex;align-items:center;gap:10px;min-width:0}
.lc-page .cust-avatar{
    width:36px;height:36px;border-radius:999px;flex-shrink:0;
    display:inline-flex;align-items:center;justify-content:center;
    background:linear-gradient(145deg,var(--theme-100),var(--theme-200));color:var(--theme-primary-hover);
    font-size:.72rem;font-weight:800;letter-spacing:.02em;
}
.lc-page .cust-name{font-weight:700;color:#0f172a;line-height:1.25}
.lc-page .cust-mobile{font-size:.78rem;color:#64748b;margin-top:2px}
.lc-page .product-pill{
    display:inline-flex;align-items:center;gap:6px;
    padding:.28rem .7rem;border-radius:999px;font-size:.74rem;font-weight:700;
    background:var(--theme-50);color:var(--theme-primary-hover);
}
.lc-page .product-pill.home{background:#ecfdf5;color:#059669}
.lc-page .product-pill.personal{background:var(--theme-50);color:var(--theme-primary)}
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
.lc-page .status-pill.in-process{background:var(--theme-100);color:var(--theme-primary-hover)}
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
    width:34px;height:34px;border-radius:10px;border:1.5px solid var(--theme-200);
    background:var(--theme-50);color:var(--theme-primary);
    display:inline-flex;align-items:center;justify-content:center;
    text-decoration:none;transition:.15s ease;
}
.lc-page .action-btn:hover{background:var(--theme-100);color:var(--theme-primary-hover)}
.lc-page .action-btn.more{
    border-color:#e2e8f0;background:#fff;color:#64748b;
}
.lc-page .action-btn.more:hover{background:#f8fafc;color:#0f172a}
.lc-page .table-footer-bar{
    display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;
    gap:12px 16px;margin:0;padding:.75rem 1.15rem;
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
    background:var(--theme-primary)!important;color:#fff!important;
    box-shadow:0 4px 10px var(--theme-primary-ring)!important;
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
    .lc-page.container-fluid{padding:.75rem .7rem 1.1rem!important}

    /* Hero */
    .lc-page .lc-hero-banner{
        padding:.85rem .9rem;min-height:0;margin-bottom:.7rem;border-radius:14px;gap:.75rem;
    }
    .lc-page .page-hero-icon{width:42px;height:42px;border-radius:12px;font-size:1.1rem}
    .lc-page .page-title{font-size:1.15rem}
    .lc-page .page-sub{font-size:.78rem;margin-top:2px}
    .lc-page .lc-hero-art{display:none}

    /* KPI grid: stable 2-col cards, no overflow */
    .lc-page .kpi-row{
        --bs-gutter-x:.5rem;--bs-gutter-y:.5rem;
        margin-bottom:.7rem;
    }
    .lc-page .kpi-row > [class*="col-"]{
        min-width:0;max-width:100%;
    }
    .lc-page .stat-tile{
        display:grid;
        grid-template-columns:auto minmax(0,1fr);
        grid-template-areas:
            "ico meta"
            "val val";
        column-gap:8px;row-gap:5px;align-items:center;
        padding:.7rem .75rem;min-height:0;height:100%;
        overflow:hidden;border-radius:12px;
    }
    .lc-page .stat-tile .stat-icon{
        grid-area:ico;width:30px;height:30px;border-radius:9px;font-size:.85rem;
    }
    .lc-page .stat-tile .stat-meta{
        grid-area:meta;min-width:0;width:auto;flex:none;
        display:flex;flex-direction:column;gap:1px;
    }
    .lc-page .stat-tile .stat-value{
        grid-area:val;margin:0;width:100%;
        display:flex;align-items:center;flex-wrap:wrap;gap:6px;
        font-size:1.2rem;line-height:1.1;font-weight:800;
        white-space:normal;overflow:visible;
    }
    .lc-page .stat-label{
        font-size:.62rem;letter-spacing:.03em;line-height:1.2;
        white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
    }
    .lc-page .stat-chip{
        font-size:.62rem;margin-top:0;line-height:1.2;
        white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;
    }
    .lc-page .stat-trend{
        margin-left:0;padding:.12rem .35rem;font-size:.6rem;flex-shrink:0;
    }
    .lc-page .stat-body,
    .lc-page .stat-value-row{display:contents}

    /* Status tabs: swipe row */
    .lc-page .lc-tabs-card{padding:.45rem;margin-bottom:.7rem;border-radius:14px}
    .lc-page .status-tabs{
        flex-wrap:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch;
        gap:4px;scrollbar-width:none;padding:2px;
    }
    .lc-page .status-tabs::-webkit-scrollbar{display:none}
    .lc-page .status-tab{
        flex:0 0 auto;height:36px;padding:0 12px;font-size:.78rem;gap:5px;
    }

    /* Filters */
    .lc-page .lc-filter-card{padding:.85rem .85rem;margin-bottom:.7rem;border-radius:14px}
    .lc-page .filter-grid{grid-template-columns:1fr;gap:10px}
    .lc-page .filter-grid label{margin:0 0 4px;font-size:.72rem}
    .lc-page .filter-actions{display:grid;grid-template-columns:1fr 1fr;width:100%;gap:8px}
    .lc-page .btn-reset,.lc-page .btn-create-sm{width:100%;justify-content:center;height:40px}

    /* Case list cards */
    .lc-page .table-card{border-radius:14px;box-shadow:0 6px 16px rgba(148,163,184,.12)}
    .lc-page .table-card-head{padding:.75rem .85rem;gap:.5rem}
    .lc-page .table-card-head h5{font-size:.98rem}
    .lc-page .section-icon{width:36px;height:36px;border-radius:10px;font-size:1rem}
    .lc-page .head-sub{font-size:.74rem;margin-top:2px}
    .lc-page .cases-table-wrap{border:0;background:transparent;overflow:visible;padding:0 .65rem .65rem}
    .lc-page .cases-table-wrap table{min-width:0!important;display:block;width:100%}
    .lc-page .cases-table-wrap thead{display:none}
    .lc-page .cases-table-wrap tbody{display:block}
    .lc-page .cases-table-wrap tr{
        display:block;margin:0 0 .65rem;padding:.65rem .7rem;
        border:1px solid #e8edf5;border-radius:12px;background:#f8fafc;
        box-shadow:none;
    }
    .lc-page .cases-table-wrap tr:last-child{margin-bottom:0}
    .lc-page .cases-table-wrap td{
        display:flex!important;align-items:flex-start;justify-content:space-between;
        gap:.55rem;width:100%;padding:.32rem 0!important;
        border:0!important;border-bottom:1px solid #eef2f7!important;
        white-space:normal!important;text-align:right;font-size:.8rem!important;line-height:1.3;
    }
    .lc-page .cases-table-wrap td:last-child{border-bottom:0!important;padding-top:.45rem!important}
    .lc-page .cases-table-wrap td::before{
        content:attr(data-label);flex:0 0 38%;max-width:38%;
        font-size:.62rem;font-weight:700;letter-spacing:.03em;
        text-transform:uppercase;color:#64748b;text-align:left;line-height:1.3;padding-top:1px;
    }
    .lc-page .cases-table-wrap td > *{max-width:62%;margin-left:auto}
    .lc-page .cases-table-wrap td.col-actions > *{max-width:none}
    .lc-page .cases-table-wrap td.col-empty{display:block!important;text-align:center;border:0!important}
    .lc-page .cases-table-wrap td.col-empty::before{display:none}
    .lc-page .cust-cell{justify-content:flex-end;gap:8px}
    .lc-page .cust-avatar{width:30px;height:30px;font-size:.65rem}
    .lc-page .person-cell{align-items:flex-end}
    .lc-page .person-cell .p-name{font-size:.8rem}
    .lc-page .person-cell .p-role{font-size:.65rem}
    .lc-page .product-pill{font-size:.68rem;padding:.2rem .55rem}
    .lc-page .status-pill{font-size:.68rem;padding:.22rem .55rem}
    .lc-page .actions-cell{justify-content:flex-end}
    .lc-page .table-footer-bar{
        flex-direction:column;align-items:stretch;padding:.7rem .85rem;gap:8px;
    }
    .lc-page .foot-right{margin-left:0;justify-content:space-between}
}
@media (max-width: 379.98px) {
    .lc-page .stat-chip{display:none}
    .lc-page .stat-value{font-size:1.1rem}
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
    $module = $module ?? (string) request('module', '');
    $moduleView = $moduleView ?? null;
    $workspaceTab = $workspaceTab ?? ($moduleView['tab'] ?? null);
    $moduleKpis = $moduleKpis ?? null;
    $caseModuleStats = $caseModuleStats ?? [];
    $isModuleHub = $workspaceTab !== null && $workspaceTab !== '';
    $caseWorkspaceUrl = function ($case) use ($workspaceTab) {
        $url = route('goal.cases.show', $case);
        return $workspaceTab ? $url.'?tab='.urlencode($workspaceTab) : $url;
    };
    $tabQuery = function ($key) use ($module) {
        $q = array_filter(array_merge(request()->except('page'), ['tab' => $key]));
        if ($module !== '') {
            $q['module'] = $module;
        } else {
            unset($q['module']);
        }
        return $q;
    };
@endphp

<div class="container-fluid p-4 lc-page {{ $isModuleHub ? 'is-module-hub' : '' }}">
    <div class="lc-hero-banner">
        <div class="lc-hero-waves" aria-hidden="true">
            <svg viewBox="0 0 900 140" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0 90 C120 50 220 120 340 70 C460 20 560 110 700 60 C800 30 860 80 900 55 L900 140 L0 140 Z" fill="rgba(167,139,250,.18)"/>
                <path d="M0 105 C140 75 250 130 390 95 C530 60 620 120 760 85 C840 65 880 95 900 80 L900 140 L0 140 Z" fill="rgba(96,165,250,.14)"/>
            </svg>
        </div>
        <div class="lc-hero-main">
            <span class="page-hero-icon"><i class="bi {{ $moduleView['icon'] ?? 'bi-briefcase' }}"></i></span>
            <div>
                <h1 class="page-title">{{ $moduleView['title'] ?? 'Loan Cases' }}</h1>
                <div class="page-sub">{{ $moduleView['sub'] ?? 'Process loan cases from documentation to disbursement.' }}</div>
                @unless($isModuleHub)
                    <div class="lc-hero-links">
                        <a class="lc-hero-link" href="{{ route('goal.documents.index') }}"><i class="bi bi-file-earmark-text"></i> Documents</a>
                        <a class="lc-hero-link" href="{{ route('goal.bank-processing.index') }}"><i class="bi bi-bank"></i> Bank Processing</a>
                        <a class="lc-hero-link" href="{{ route('goal.allocation.index') }}"><i class="bi bi-people"></i> Allocation</a>
                        <a class="lc-hero-link" href="{{ route('goal.sanction-disbursement.index') }}"><i class="bi bi-award"></i> Sanction</a>
                        <a class="lc-hero-link" href="{{ route('goal.cases.export', request()->query()) }}"><i class="bi bi-download"></i> Export</a>
                    </div>
                @endunless
            </div>
        </div>
        <div class="lc-hero-art" aria-hidden="true">
            <svg viewBox="0 0 110 96" xmlns="http://www.w3.org/2000/svg">
                <rect x="28" y="10" width="54" height="72" rx="8" fill="var(--theme-primary)"/>
                <rect x="34" y="18" width="42" height="56" rx="4" fill="var(--theme-50)"/>
                <rect x="42" y="6" width="26" height="10" rx="3" fill="var(--theme-300)"/>
                <circle cx="44" cy="34" r="5" fill="#22c55e"/>
                <path d="M41.5 34 l1.8 1.8 3.6-3.6" stroke="#fff" stroke-width="1.6" fill="none" stroke-linecap="round"/>
                <rect x="52" y="31" width="18" height="5" rx="2" fill="var(--theme-200)"/>
                <circle cx="44" cy="48" r="5" fill="#22c55e"/>
                <path d="M41.5 48 l1.8 1.8 3.6-3.6" stroke="#fff" stroke-width="1.6" fill="none" stroke-linecap="round"/>
                <rect x="52" y="45" width="18" height="5" rx="2" fill="var(--theme-200)"/>
                <circle cx="44" cy="62" r="5" fill="#22c55e"/>
                <path d="M41.5 62 l1.8 1.8 3.6-3.6" stroke="#fff" stroke-width="1.6" fill="none" stroke-linecap="round"/>
                <rect x="52" y="59" width="18" height="5" rx="2" fill="var(--theme-200)"/>
            </svg>
        </div>
    </div>

    <div class="row g-2 kpi-row">
        @if($workspaceTab === 'documents' && $moduleKpis)
            <div class="col-6 col-md">
                <div class="stat-tile tone-blue">
                    <span class="stat-icon"><i class="bi bi-briefcase"></i></span>
                    <div class="stat-meta"><p class="stat-label">Cases</p><span class="stat-chip">In scope</span></div>
                    <div class="stat-value">{{ number_format($moduleKpis['cases']) }}</div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-tile tone-orange">
                    <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
                    <div class="stat-meta"><p class="stat-label">Pending Verify</p><span class="stat-chip">Awaiting review</span></div>
                    <div class="stat-value">{{ number_format($moduleKpis['pending']) }}</div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-tile tone-green">
                    <span class="stat-icon"><i class="bi bi-check2-circle"></i></span>
                    <div class="stat-meta"><p class="stat-label">Verified</p><span class="stat-chip">Approved docs</span></div>
                    <div class="stat-value">{{ number_format($moduleKpis['verified']) }}</div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-tile tone-red">
                    <span class="stat-icon"><i class="bi bi-x-circle"></i></span>
                    <div class="stat-meta"><p class="stat-label">Rejected</p><span class="stat-chip">Needs re-upload</span></div>
                    <div class="stat-value">{{ number_format($moduleKpis['rejected']) }}</div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-tile tone-purple">
                    <span class="stat-icon"><i class="bi bi-file-earmark-minus"></i></span>
                    <div class="stat-meta"><p class="stat-label">Missing</p><span class="stat-chip">Mandatory gaps</span></div>
                    <div class="stat-value">{{ number_format($moduleKpis['missing']) }}</div>
                </div>
            </div>
        @elseif(in_array($workspaceTab, ['lenders', 'queries'], true) && $moduleKpis)
            <div class="col-6 col-md">
                <div class="stat-tile tone-blue">
                    <span class="stat-icon"><i class="bi bi-layers"></i></span>
                    <div class="stat-meta"><p class="stat-label">Applications</p><span class="stat-chip">All lenders</span></div>
                    <div class="stat-value">{{ number_format($moduleKpis['applications']) }}</div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-tile tone-orange">
                    <span class="stat-icon"><i class="bi bi-box-arrow-in-right"></i></span>
                    <div class="stat-meta"><p class="stat-label">Bank Login</p><span class="stat-chip">Logged in</span></div>
                    <div class="stat-value">{{ number_format($moduleKpis['login']) }}</div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-tile tone-purple">
                    <span class="stat-icon"><i class="bi bi-gear"></i></span>
                    <div class="stat-meta"><p class="stat-label">Processing</p><span class="stat-chip">With bank</span></div>
                    <div class="stat-value">{{ number_format($moduleKpis['processing']) }}</div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-tile tone-red">
                    <span class="stat-icon"><i class="bi bi-question-circle"></i></span>
                    <div class="stat-meta"><p class="stat-label">Open Queries</p><span class="stat-chip">Unresolved</span></div>
                    <div class="stat-value">{{ number_format($moduleKpis['queries']) }}</div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-tile tone-green">
                    <span class="stat-icon"><i class="bi bi-award"></i></span>
                    <div class="stat-meta"><p class="stat-label">Sanctioned</p><span class="stat-chip">Apps</span></div>
                    <div class="stat-value">{{ number_format($moduleKpis['sanctioned']) }}</div>
                </div>
            </div>
        @elseif(in_array($workspaceTab, ['sanction', 'disbursement'], true) && $moduleKpis)
            <div class="col-6 col-md">
                <div class="stat-tile tone-green">
                    <span class="stat-icon"><i class="bi bi-award"></i></span>
                    <div class="stat-meta"><p class="stat-label">Sanctioned</p><span class="stat-chip">Cases</span></div>
                    <div class="stat-value">{{ number_format($moduleKpis['sanctioned_cases']) }}</div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-tile tone-blue">
                    <span class="stat-icon"><i class="bi bi-file-earmark-check"></i></span>
                    <div class="stat-meta"><p class="stat-label">Active Sanctions</p><span class="stat-chip">Records</span></div>
                    <div class="stat-value">{{ number_format($moduleKpis['active_sanctions']) }}</div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-tile tone-orange">
                    <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
                    <div class="stat-meta"><p class="stat-label">Pending Disb.</p><span class="stat-chip">Awaiting verify</span></div>
                    <div class="stat-value">{{ number_format($moduleKpis['pending_disb']) }}</div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-tile tone-purple">
                    <span class="stat-icon"><i class="bi bi-cash-coin"></i></span>
                    <div class="stat-meta"><p class="stat-label">Verified Disb.</p><span class="stat-chip">Recognized</span></div>
                    <div class="stat-value">{{ number_format($moduleKpis['verified_disb']) }}</div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-tile tone-blue">
                    <span class="stat-icon"><i class="bi bi-check2-all"></i></span>
                    <div class="stat-meta"><p class="stat-label">Disbursed Cases</p><span class="stat-chip">Status</span></div>
                    <div class="stat-value">{{ number_format($moduleKpis['disbursed_cases']) }}</div>
                </div>
            </div>
        @else
            <div class="col-6 col-md">
                <a class="stat-tile tone-blue {{ $tab === 'all' ? 'is-active' : '' }}" href="{{ route('goal.cases.index', $tabQuery('all')) }}">
                    <span class="stat-icon"><i class="bi bi-folder2"></i></span>
                    <div class="stat-meta">
                        <p class="stat-label">Total Cases</p>
                        <span class="stat-chip">In current scope</span>
                    </div>
                    <div class="stat-value">
                        {{ number_format($kpis['total']) }}
                        @if($kpis['total_trend_pct'] !== null)
                            <span class="stat-trend {{ $kpis['total_trend_pct'] >= 0 ? 'up' : 'down' }}">
                                <i class="bi {{ $kpis['total_trend_pct'] >= 0 ? 'bi-arrow-up' : 'bi-arrow-down' }}"></i>
                                {{ ($kpis['total_trend_pct'] >= 0 ? '+' : '') . rtrim(rtrim(number_format($kpis['total_trend_pct'], 1), '0'), '.') }}%
                            </span>
                        @endif
                    </div>
                </a>
            </div>
            <div class="col-6 col-md">
                <a class="stat-tile tone-orange {{ $tab === 'in_process' ? 'is-active' : '' }}" href="{{ route('goal.cases.index', $tabQuery('in_process')) }}">
                    <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
                    <div class="stat-meta"><p class="stat-label">In Process</p><span class="stat-chip">Active pipeline</span></div>
                    <div class="stat-value">{{ number_format($kpis['in_process']) }}</div>
                </a>
            </div>
            <div class="col-6 col-md">
                <a class="stat-tile tone-green {{ $tab === 'sanctioned' ? 'is-active' : '' }}" href="{{ route('goal.cases.index', $tabQuery('sanctioned')) }}">
                    <span class="stat-icon"><i class="bi bi-check-lg"></i></span>
                    <div class="stat-meta"><p class="stat-label">Sanctioned</p><span class="stat-chip">Approved</span></div>
                    <div class="stat-value">{{ number_format($kpis['sanctioned']) }}</div>
                </a>
            </div>
            <div class="col-6 col-md">
                <a class="stat-tile tone-purple {{ $tab === 'disbursed' ? 'is-active' : '' }}" href="{{ route('goal.cases.index', $tabQuery('disbursed')) }}">
                    <span class="stat-icon"><i class="bi bi-cash-coin"></i></span>
                    <div class="stat-meta"><p class="stat-label">Disbursed</p><span class="stat-chip">Completed</span></div>
                    <div class="stat-value">{{ number_format($kpis['disbursed']) }}</div>
                </a>
            </div>
            <div class="col-6 col-md">
                <a class="stat-tile tone-red {{ $tab === 'rejected' ? 'is-active' : '' }}" href="{{ route('goal.cases.index', $tabQuery('rejected')) }}">
                    <span class="stat-icon"><i class="bi bi-x-lg"></i></span>
                    <div class="stat-meta"><p class="stat-label">Rejected</p><span class="stat-chip">Closed out</span></div>
                    <div class="stat-value">{{ number_format($kpis['rejected']) }}</div>
                </a>
            </div>
        @endif
    </div>

    @unless($isModuleHub)
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
    @endunless

    <div class="lc-filter-card">
        <form method="GET" action="{{ route('goal.cases.index') }}" class="filter-grid" id="lcFilterForm">
            <input type="hidden" name="tab" value="{{ $tab }}">
            @if($module !== '')
                <input type="hidden" name="module" value="{{ $module }}">
            @endif
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
                <a href="{{ route('goal.cases.index', array_filter(['tab' => $tab, 'module' => $module ?: null])) }}" class="btn-reset"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
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
                    <h5>{{ $isModuleHub ? (($moduleView['title'] ?? 'Module').' Queue') : 'Case List' }}</h5>
                    <p class="head-sub">
                        {{ number_format($cases->total()) }} case{{ $cases->total() === 1 ? '' : 's' }} in this view
                        @if($workspaceTab)
                            — open a case to work in <strong>{{ $moduleView['title'] ?? 'workspace' }}</strong>
                        @endif
                    </p>
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
                            <th>Product</th>
                            @if($workspaceTab === 'documents')
                                <th>Doc Progress</th>
                                <th>Verified</th>
                                <th>Pending</th>
                                <th>Missing</th>
                            @elseif(in_array($workspaceTab, ['lenders', 'queries'], true))
                                <th>Applications</th>
                                <th>Open Queries</th>
                                <th>Amount</th>
                            @elseif(in_array($workspaceTab, ['sanction', 'disbursement'], true))
                                <th>Sanction</th>
                                <th>Verified Disb.</th>
                                <th>Pending Disb.</th>
                            @else
                                <th>Lead Ref</th>
                                <th>Amount</th>
                                <th>Branch</th>
                                <th>Branch Manager</th>
                                <th>Assigned To</th>
                                <th>BM Reco</th>
                            @endif
                            <th>Case Status</th>
                            <th>Updated On</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($cases as $case)
                        @php
                            $reco = $bmReco($case);
                            $bmId = (int) ($caseBranchManagerIds[$case->id] ?? 0);
                            $bmUser = $bmId ? ($branchManagersById[$bmId] ?? null) : null;
                            $owner = $case->owner;
                            $mStats = $caseModuleStats[$case->id] ?? [];
                            $colspan = $isModuleHub ? 10 : 12;
                        @endphp
                        <tr>
                            <td data-label="Case No.">
                                <a class="case-link" href="{{ $caseWorkspaceUrl($case) }}">{{ $case->case_no }}</a>
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
                            @if($workspaceTab === 'documents')
                                @php
                                    $docTone = ($mStats['missing'] ?? 0) > 0 || ($mStats['rejected'] ?? 0) > 0
                                        ? 'bad'
                                        : ((($mStats['pending'] ?? 0) > 0) ? 'warn' : (($mStats['total'] ?? 0) > 0 ? 'ok' : ''));
                                @endphp
                                <td data-label="Doc Progress">
                                    <span class="doc-progress {{ $docTone }}">{{ $mStats['label'] ?? 'No checklist' }}</span>
                                </td>
                                <td data-label="Verified">{{ number_format($mStats['verified'] ?? 0) }}</td>
                                <td data-label="Pending">{{ number_format($mStats['pending'] ?? 0) }}</td>
                                <td data-label="Missing">{{ number_format($mStats['missing'] ?? 0) }}</td>
                            @elseif(in_array($workspaceTab, ['lenders', 'queries'], true))
                                <td data-label="Applications">{{ number_format($mStats['applications'] ?? 0) }}</td>
                                <td data-label="Open Queries">{{ number_format($mStats['open_queries'] ?? 0) }}</td>
                                <td data-label="Amount" class="amount-cell">
                                    {{ $case->loan_amount !== null ? '₹ ' . number_format((float) $case->loan_amount, 0) : '—' }}
                                </td>
                            @elseif(in_array($workspaceTab, ['sanction', 'disbursement'], true))
                                <td data-label="Sanction" class="amount-cell">
                                    {{ isset($mStats['sanction_amount']) && $mStats['sanction_amount'] !== null
                                        ? '₹ ' . number_format((float) $mStats['sanction_amount'], 0)
                                        : '—' }}
                                </td>
                                <td data-label="Verified Disb." class="amount-cell">
                                    ₹ {{ number_format((float) ($mStats['verified_disb'] ?? 0), 0) }}
                                </td>
                                <td data-label="Pending Disb." class="amount-cell">
                                    ₹ {{ number_format((float) ($mStats['pending_disb'] ?? 0), 0) }}
                                </td>
                            @else
                                <td data-label="Lead Ref">
                                    @if($case->lead)
                                        <a class="lead-ref" href="{{ route('goal.leads.show', $case->lead) }}">{{ $case->lead->ref_no }}</a>
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
                            @endif
                            <td data-label="Case Status">
                                <span class="status-pill {{ $statusClass($case->status) }}">{{ $statusLabel($case->status) }}</span>
                            </td>
                            <td data-label="Updated On">
                                <span class="updated-cell">
                                    <i class="bi bi-calendar3"></i>
                                    {{ optional($case->updated_at)->format('d M Y') ?? '—' }}
                                </span>
                            </td>
                            <td class="col-actions" data-label="Actions">
                                <div class="actions-cell">
                                    <a href="{{ $caseWorkspaceUrl($case) }}" class="action-btn" title="{{ $isModuleHub ? 'Open '.$moduleView['title'] : 'View workspace' }}">
                                        <i class="bi {{ $isModuleHub ? ($moduleView['icon'] ?? 'bi-eye') : 'bi-eye' }}"></i>
                                    </a>
                                    <div class="dropdown">
                                        <button type="button" class="action-btn more" data-bs-toggle="dropdown" aria-expanded="false" title="More">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ $caseWorkspaceUrl($case) }}">
                                                    <i class="bi bi-folder2-open me-2"></i>
                                                    {{ $isModuleHub ? 'Open '.$moduleView['title'] : 'Open workspace' }}
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
                            <td colspan="{{ $isModuleHub ? 10 : 12 }}" class="col-empty">
                                <div class="empty-state">
                                    <div class="illus"><i class="bi {{ $moduleView['icon'] ?? 'bi-briefcase' }}"></i></div>
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
                        Showing {{ $cases->firstItem() }}—{{ $cases->lastItem() }} of {{ $cases->total() }}
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
