@extends('layouts.app')

@push('styles')
<style>
.bp-page{color:#1e293b}
.bp-page .page-hero{display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1rem}
.bp-page .hero-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.bp-page a.stat-tile{text-decoration:none;color:inherit;transition:box-shadow .15s,transform .15s}
.bp-page a.stat-tile:hover{box-shadow:0 4px 14px rgba(148,163,184,.2);transform:translateY(-1px)}
.bp-page a.stat-tile.is-active{outline:2px solid var(--theme-primary);outline-offset:1px}
.bp-page .breadcrumb-bp{display:flex;flex-wrap:wrap;align-items:center;gap:6px;margin:0 0 8px;padding:0;list-style:none;font-size:.78rem;color:#64748b}
.bp-page .breadcrumb-bp a{color:var(--theme-primary);text-decoration:none;font-weight:600}
.bp-page .page-title{font-size:1.55rem;font-weight:800;color:#0f172a;margin:0;line-height:1.2}
.bp-page .page-sub{color:#64748b;font-size:.9rem;margin-top:4px}

.btn-bp-primary{
    height:40px;border-radius:10px;border:1px solid transparent !important;
    background:var(--theme-primary) !important;color:#fff !important;
    padding:0 16px;font-weight:700;font-size:.88rem;
    display:inline-flex;align-items:center;gap:7px;
    box-shadow:0 8px 18px var(--theme-primary-ring);
}
.btn-bp-primary:hover{background:var(--theme-primary-hover) !important;color:#fff !important}
.btn-bp-cancel{
    height:40px;border-radius:10px;border:1px solid #cbd5e1 !important;
    background:#fff !important;color:#334155 !important;padding:0 16px;font-weight:600;font-size:.88rem;
}
.btn-bp-cancel:hover{background:#f8fafc !important}

.bp-page .kpi-row{margin-bottom:.9rem}
.bp-page .stat-tile{
    --tile-ink:#2563eb;--tile-soft:#dbeafe;--tile-wave:#93c5fd;
    position:relative;overflow:hidden;border:1px solid #e8edf5;border-radius:14px;
    padding:.75rem .9rem;background:#fff;
    box-shadow:0 1px 2px rgba(15,23,42,.04),0 8px 18px rgba(148,163,184,.12);
    display:flex;align-items:center;gap:.85rem;
}
.bp-page .stat-tile::after{
    content:"";position:absolute;right:-12px;bottom:-22px;width:72px;height:56px;
    background:radial-gradient(ellipse at center,var(--tile-wave) 0%,transparent 70%);opacity:.45;pointer-events:none;
}
.bp-page .stat-tile.tone-blue{--tile-ink:#2563eb;--tile-soft:#dbeafe;--tile-wave:#93c5fd}
.bp-page .stat-tile.tone-green{--tile-ink:#16a34a;--tile-soft:#dcfce7;--tile-wave:#86efac}
.bp-page .stat-tile.tone-orange{--tile-ink:#ea580c;--tile-soft:#ffedd5;--tile-wave:#fdba74}
.bp-page .stat-tile.tone-purple{--tile-ink:#7c3aed;--tile-soft:#ede9fe;--tile-wave:#c4b5fd}
.bp-page .stat-icon{
    width:40px;height:40px;border-radius:12px;flex-shrink:0;
    display:inline-flex;align-items:center;justify-content:center;
    background:var(--tile-soft);color:var(--tile-ink);font-size:1.05rem;position:relative;z-index:1;
}
.bp-page .stat-meta{position:relative;z-index:1}
.bp-page .stat-value{margin:0;font-size:1.55rem;font-weight:800;color:#0f172a;line-height:1}
.bp-page .stat-label{margin:5px 0 0;font-size:.82rem;font-weight:600;color:#64748b}

.bp-page .tabs-card,.bp-page .filter-card,.bp-page .table-card,.bp-page .side-card{
    background:#fff;border:1px solid #e8edf5;border-radius:16px;
    box-shadow:0 10px 28px rgba(148,163,184,.12);
}
.bp-page .tabs-card{padding:.65rem .8rem;margin-bottom:.85rem;overflow:hidden}
.bp-page .status-tabs{display:flex;flex-wrap:wrap;gap:6px}
.bp-page .status-tab{
    display:inline-flex;align-items:center;gap:7px;height:36px;padding:0 14px;border-radius:999px;
    border:1px solid var(--theme-200);background:#fff;color:var(--theme-primary-hover);
    font-size:.84rem;font-weight:650;text-decoration:none;flex:0 0 auto;white-space:nowrap;
}
.bp-page .status-tab.active{background:var(--theme-primary);border-color:var(--theme-primary);color:#fff;box-shadow:0 6px 14px var(--theme-primary-ring)}
.bp-page .filter-card{padding:1rem 1.15rem;margin-bottom:1rem;overflow:visible}
.bp-page .filter-grid{display:grid;grid-template-columns:minmax(220px,1.6fr) repeat(4,minmax(130px,1fr));gap:12px;align-items:end}
.bp-page .filter-grid.row-2{grid-template-columns:minmax(180px,1fr) auto;margin-top:12px;max-width:520px}
.bp-page .filter-grid label{display:block;font-size:.72rem;font-weight:700;color:#64748b;margin:0 0 6px;text-transform:uppercase;letter-spacing:.04em}
.bp-page .filter-grid .form-control,.bp-page .filter-grid .form-select{min-height:38px;height:38px;border-radius:10px;border:1px solid #d1d5db;font-size:.9rem}
.bp-page .bp-search{position:relative}
.bp-page .bp-search > i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none}
.bp-page .bp-search .form-control{padding-left:38px}
.bp-page .bp-date{position:relative}
.bp-page .bp-date > i{position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none}
.bp-page .filter-actions{display:flex;align-items:end;gap:8px}
.bp-page .btn-apply{
    height:38px;border-radius:10px;border:none;background:var(--theme-primary)!important;color:#fff!important;
    padding:0 16px;font-weight:700;font-size:.85rem;display:inline-flex;align-items:center;gap:6px;
}
.bp-page .btn-reset{
    height:38px;border-radius:10px;border:1px solid #cbd5e1;background:#fff;color:#475569;
    padding:0 14px;font-weight:600;font-size:.85rem;text-decoration:none;display:inline-flex;align-items:center;gap:6px;
}

.bp-page .table-card{margin-bottom:1rem}
.bp-page .table-card-head{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.65rem;padding:.85rem 1.15rem;border-bottom:1px solid #eef2f7}
.bp-page .table-card-head h5{margin:0;font-size:1.05rem;font-weight:800}
.bp-page .head-sub{margin:3px 0 0;font-size:.8rem;color:#64748b}
.bp-page .table-card-body{padding:0;max-width:100%}
.bp-page .table-wrap{
    border:0;border-radius:0;overflow-x:auto;overflow-y:hidden;max-width:100%;
    -webkit-overflow-scrolling:touch;overscroll-behavior-x:contain;
    scrollbar-width:thin;scrollbar-color:#cbd5e1 transparent;
}
.bp-page .table-wrap::-webkit-scrollbar{height:6px}
.bp-page .table-wrap::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:999px}
.bp-page table{width:100%;min-width:1100px;margin:0;border-collapse:separate;border-spacing:0}
.bp-page table thead th{font-size:.7rem;text-transform:uppercase;letter-spacing:.04em;color:#64748b;font-weight:700;background:#f1f5f9;padding:.75rem .85rem;white-space:nowrap}
.bp-page table tbody td{vertical-align:middle;font-size:.875rem;color:#334155;border-color:#eef2f7;padding:.75rem .85rem;white-space:nowrap}
.bp-page .case-link{font-weight:800;color:var(--theme-primary);text-decoration:none;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.82rem}
.bp-page .case-link:hover{text-decoration:underline}
.bp-page .lender-cell{display:inline-flex;align-items:center;gap:8px;font-weight:700;color:#0f172a}
.bp-page .lender-mark{
    width:28px;height:28px;border-radius:8px;background:var(--theme-50);color:var(--theme-primary);
    display:inline-flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;
}
.bp-page .status-pill{display:inline-flex;padding:.28rem .7rem;border-radius:999px;font-size:.74rem;font-weight:700}
.bp-page .status-pill.login{background:#dbeafe;color:#1d4ed8}
.bp-page .status-pill.query{background:#ffedd5;color:#c2410c}
.bp-page .status-pill.processing{background:#ede9fe;color:#6d28d9}
.bp-page .status-pill.sanctioned{background:#dcfce7;color:#15803d}
.bp-page .status-pill.rejected{background:#fee2e2;color:#b91c1c}
.bp-page .status-pill.disbursed{background:#ecfeff;color:#0e7490}
.bp-page .status-pill.open,.bp-page .status-pill.pending{background:#ffedd5;color:#c2410c}
.bp-page .status-pill.overdue{background:#fee2e2;color:#b91c1c}
.bp-page .actions-cell{display:inline-flex;align-items:center;gap:4px}
.bp-page .action-btn{
    width:32px;height:32px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#475569;
    display:inline-flex;align-items:center;justify-content:center;text-decoration:none;padding:0;
}
.bp-page .action-btn:hover{background:#f8fafc;color:var(--theme-primary)}
.bp-page .table-footer-bar{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem;padding:.75rem 1.15rem;border-top:1px solid #eef2f7;font-size:.82rem;color:#64748b}
.bp-page .bottom-grid{display:grid;grid-template-columns:1.4fr .9fr;gap:1rem}
.bp-page .side-card-head{padding:.85rem 1.15rem;border-bottom:1px solid #eef2f7}
.bp-page .side-card-head h5{margin:0;font-size:1rem;font-weight:800}
.bp-page .side-card-body{padding:.55rem 1rem .75rem;overflow-x:auto;-webkit-overflow-scrolling:touch;max-width:100%}
.bp-page .q-table{width:100%;margin:0;min-width:640px;border-collapse:separate;border-spacing:0}
.bp-page .q-table th{font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;color:#64748b;font-weight:700;padding:.55rem .4rem;border-bottom:1px solid #e2e8f0}
.bp-page .q-table td{font-size:.82rem;padding:.65rem .4rem;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.bp-page .workflow{
    --wf-dot:32px;
    display:flex;align-items:flex-start;gap:0;margin-top:.35rem;width:100%;
}
.bp-page .wf-step{
    flex:1 1 0;min-width:0;text-align:center;position:relative;padding:0 6px;
}
.bp-page .wf-step:not(:last-child)::after{
    content:"";position:absolute;z-index:0;pointer-events:none;
    top:calc(var(--wf-dot) / 2);left:calc(50% + (var(--wf-dot) / 2));
    width:calc(100% - var(--wf-dot));height:2px;
    transform:translateY(-50%);background:#e2e8f0;
}
.bp-page .wf-step.done:not(:last-child)::after{background:#86efac}
.bp-page .wf-step.active:not(:last-child)::after{background:linear-gradient(90deg,#86efac,#cbd5e1)}
.bp-page .wf-dot{
    width:var(--wf-dot);height:var(--wf-dot);border-radius:999px;margin:0 auto 8px;
    display:flex;align-items:center;justify-content:center;box-sizing:border-box;
    background:#f1f5f9;color:#94a3b8;border:2px solid #e2e8f0;font-size:.9rem;
    position:relative;z-index:1;flex-shrink:0;line-height:1;
}
.bp-page .wf-dot i{line-height:1;display:block;font-size:.95rem}
.bp-page .wf-step.done .wf-dot{background:#dcfce7;color:#15803d;border-color:#86efac}
.bp-page .wf-step.active .wf-dot{background:var(--theme-primary);color:#fff;border-color:var(--theme-primary)}
.bp-page .wf-label{
    font-size:.8rem;font-weight:700;color:#0f172a;line-height:1.25;
    overflow-wrap:anywhere;word-break:break-word;
}
.bp-page .wf-desc{font-size:.72rem;color:#94a3b8;margin-top:2px;line-height:1.3}
.bp-page .empty-state{padding:2rem 1rem;text-align:center;color:#64748b}
/* ── Add Lender Application modal (sheet on mobile, centered card on desktop) ── */
.bp-modal.modal{
    --bp-header-h:60px;
    --bp-teal:#0F8B72;
    --bp-teal-ring:rgba(20,184,166,.28);
    padding:0 !important;
    overflow:hidden !important;
    background:transparent !important;
}
.bp-modal.modal.show{
    display:block !important;
    padding:0 !important;
    overflow:hidden !important;
}
/* Beat global .modal.show .modal-dialog / .modal-content !important rules in app-theme.css */
.bp-modal.modal.show .modal-dialog,
.bp-modal .modal-dialog{
    position:absolute !important;
    margin:0 !important;
    pointer-events:none;
    height:auto !important;
    max-height:none !important;
    max-width:none !important;
    transform:none !important;
}
.bp-modal.modal.show .modal-content,
.bp-modal .modal-content{
    pointer-events:auto;
    display:flex !important;
    flex-direction:column !important;
    width:100% !important;
    height:100% !important;
    max-height:100% !important;
    min-height:0 !important;
    overflow:hidden !important;
    border:1px solid #e8edf5;
    background:#fff;
    box-shadow:0 20px 50px rgba(15,23,42,.18);
}
.bp-modal .modal-header{
    flex-shrink:0;
    display:flex;align-items:flex-start;justify-content:space-between;gap:12px;
    padding:14px 16px;
    border-bottom:1px solid #eef2f7;
    background:linear-gradient(180deg,#f0fdfa 0%,#ffffff 100%);
}
.bp-modal .bp-modal-head{display:flex;align-items:flex-start;gap:12px;min-width:0}
.bp-modal .bp-modal-ico{
    width:40px;height:40px;border-radius:12px;flex-shrink:0;
    display:inline-flex;align-items:center;justify-content:center;
    background:#ccfbf1;color:var(--bp-teal);font-size:1.1rem;
}
.bp-modal .modal-title{font-size:1.05rem;font-weight:800;color:#0f172a;margin:0;line-height:1.25}
.bp-modal .bp-modal-sub{margin:3px 0 0;font-size:.8rem;color:#64748b;line-height:1.35}
.bp-modal .modal-close{
    width:36px;height:36px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;
    color:#64748b;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;
}
.bp-modal .modal-close:hover{background:#f8fafc;color:#0f172a}
.bp-modal .modal-body{
    flex:1 1 auto !important;
    min-height:0 !important;
    overflow-x:hidden !important;
    overflow-y:auto !important;
    overscroll-behavior:contain;
    -webkit-overflow-scrolling:touch;
    padding:16px !important;
}
.bp-modal .modal-footer{
    flex-shrink:0;
    display:grid !important;
    grid-template-columns:1fr 1fr;
    gap:12px;
    padding:12px 16px calc(12px + env(safe-area-inset-bottom,0px));
    border-top:1px solid #eef2f7;
    background:#f8fafc;
}
.bp-modal .form-label{
    display:block;font-size:.78rem;font-weight:700;color:#475569;margin:0 0 6px;
}
.bp-modal .form-control,
.bp-modal .form-select{
    min-height:46px;height:46px;border-radius:10px;border:1px solid #CBD5E1;
    font-size:.9rem;background:#fff;color:#0f172a;width:100%;
}
.bp-modal textarea.form-control{height:auto;min-height:88px;padding-top:10px}
.bp-modal .form-control:focus,
.bp-modal .form-select:focus{
    border-color:#14B8A6;box-shadow:0 0 0 3px var(--bp-teal-ring);outline:none;
}
.bp-modal .bp-field-grid{display:grid;grid-template-columns:1fr;gap:14px}
.bp-modal .bp-amount-wrap{position:relative}
.bp-modal .bp-amount-wrap > span{
    position:absolute;left:12px;top:50%;transform:translateY(-50%);
    font-weight:700;color:#64748b;pointer-events:none;
}
.bp-modal .bp-amount-wrap .form-control{padding-left:28px}
.bp-modal .bp-file{
    border:1.5px dashed #cbd5e1;border-radius:12px;background:#f8fafc;
    padding:14px;text-align:center;
}
.bp-modal .bp-file input[type=file]{width:100%;font-size:.84rem;color:#475569}
.bp-modal .bp-file-hint{margin-top:6px;font-size:.74rem;color:#94a3b8}
.bp-modal .btn-bp-cancel,
.bp-modal .btn-bp-primary{
    width:100%;justify-content:center;min-height:48px;height:48px;border-radius:10px;margin:0;
}
.bp-modal .btn-bp-primary{background:var(--bp-teal)!important;border-color:var(--bp-teal)!important;box-shadow:none}

/* Mobile / small tablet: sheet under app header */
@media (max-width:767.98px){
    body.tw-modal-open .top-slim-header{z-index:10660 !important}
    .bp-modal.modal.show{z-index:10650 !important}
    .bp-modal.modal.show .modal-dialog,
    .bp-modal .modal-dialog{
        top:var(--bp-header-h);left:0;right:0;bottom:0;
        width:100% !important;max-width:none !important;
    }
    .bp-modal.modal.show .modal-content,
    .bp-modal .modal-content{
        border-radius:16px 16px 0 0;
        border-left:0;border-right:0;border-bottom:0;
        height:100% !important;
        max-height:100% !important;
    }
}

/* Tablet */
@media (min-width:768px) and (max-width:1023.98px){
    .bp-modal.modal.show .modal-dialog,
    .bp-modal .modal-dialog{
        top:50%;left:50%;
        transform:translate(-50%,-50%) !important;
        width:90vw !important;max-width:640px !important;
        height:auto !important;max-height:92dvh !important;
        bottom:auto;right:auto;
    }
    .bp-modal.modal.show .modal-content,
    .bp-modal .modal-content{
        height:auto !important;
        max-height:92dvh !important;
        border-radius:16px;
    }
    .bp-modal .bp-field-grid{grid-template-columns:1fr 1fr}
    .bp-modal .bp-span-2{grid-column:1 / -1}
}

/* Desktop */
@media (min-width:1024px){
    .bp-modal.modal.show .modal-dialog,
    .bp-modal .modal-dialog{
        top:50%;left:50%;
        transform:translate(-50%,-50%) !important;
        width:min(680px,92vw) !important;max-width:680px !important;
        height:auto !important;max-height:90dvh !important;
        bottom:auto;right:auto;
    }
    .bp-modal.modal.show .modal-content,
    .bp-modal .modal-content{
        height:auto !important;
        max-height:90dvh !important;
        border-radius:16px;
    }
    .bp-modal .bp-field-grid{grid-template-columns:1fr 1fr}
    .bp-modal .bp-span-2{grid-column:1 / -1}
}
@media (max-width:1199.98px){
    .bp-page .bottom-grid{grid-template-columns:1fr}
    .bp-page .filter-grid{grid-template-columns:minmax(180px,1.4fr) repeat(2,minmax(120px,1fr))}
}
@media (max-width:991.98px){
    .bp-page .status-tabs{
        flex-wrap:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch;
        scrollbar-width:none;padding-bottom:2px;margin:0 -.15rem;padding-left:.15rem;padding-right:.15rem;
    }
    .bp-page .status-tabs::-webkit-scrollbar{display:none;height:0}
    .bp-page .filter-grid{grid-template-columns:1fr 1fr}
    .bp-page .filter-grid.row-2{grid-template-columns:1fr auto;max-width:none}
}
@media (max-width:575.98px){
    .bp-page.container-fluid{padding:.65rem !important}
    .bp-page .page-hero{margin-bottom:.65rem}
    .bp-page .page-title{font-size:1.22rem}
    .bp-page .page-sub{font-size:.8rem}
    .bp-page .btn-bp-primary{width:100%;justify-content:center;height:38px;box-shadow:none}
    .bp-page .stat-tile{padding:.5rem .55rem;gap:.5rem;border-radius:12px}
    .bp-page .stat-icon{width:30px;height:30px;border-radius:9px;font-size:.88rem}
    .bp-page .stat-value{font-size:1.05rem;white-space:nowrap}
    .bp-page .stat-label{font-size:.68rem}
    .bp-page .tabs-card{padding:.5rem .55rem;margin-bottom:.65rem;border-radius:12px}
    .bp-page .status-tab{height:32px;padding:0 11px;font-size:.78rem}
    .bp-page .filter-card{padding:.7rem .75rem;margin-bottom:.65rem;border-radius:12px}
    .bp-page .filter-grid{grid-template-columns:1fr 1fr !important;gap:8px}
    .bp-page .filter-grid > div:first-child{grid-column:1 / -1}
    .bp-page .filter-grid.row-2{grid-template-columns:1fr !important;margin-top:8px}
    .bp-page .filter-grid label{margin-bottom:4px;font-size:.68rem}
    .bp-page .filter-grid .form-control,.bp-page .filter-grid .form-select{min-height:36px;height:36px;font-size:.84rem}
    .bp-page .filter-actions{width:100%;display:grid;grid-template-columns:1fr 1fr;gap:8px}
    .bp-page .btn-apply,.bp-page .btn-reset{width:100%;justify-content:center;height:36px}
    .bp-page .table-card,.bp-page .side-card{border-radius:12px;margin-bottom:.65rem}
    .bp-page .table-card-head,.bp-page .side-card-head{padding:.6rem .75rem}
    .bp-page .table-card-head h5,.bp-page .side-card-head h5{font-size:.95rem}
    .bp-page .head-sub{display:none}
    .bp-page .table-footer-bar{padding:.55rem .75rem}
    .bp-page .workflow{--wf-dot:28px;overflow:visible;padding-bottom:0}
    .bp-page .wf-step{flex:1 1 0;min-width:0;padding:0 4px}
    .bp-page .wf-label{font-size:.68rem;line-height:1.2}
    .bp-page .wf-desc{display:none}

    /* Dense lender application cards */
    .bp-page .table-card-body,
    .bp-page .side-card-body{padding:.5rem .55rem !important}
    .bp-page .table-wrap > table tr,
    .bp-page .side-card-body .table-wrap > table tr{
        margin:0 0 .5rem !important;
        padding:.45rem .55rem !important;
        border-radius:10px !important;
        box-shadow:0 2px 8px rgba(148,163,184,.1) !important;
    }
    .bp-page .table-wrap > table td,
    .bp-page .side-card-body .table-wrap > table td{
        padding:.18rem 0 !important;
        font-size:.8rem !important;
        gap:.45rem !important;
        line-height:1.25 !important;
        align-items:flex-start !important;
    }
    .bp-page .table-wrap > table td::before,
    .bp-page .side-card-body .table-wrap > table td::before{
        font-size:.62rem !important;
        flex:0 0 36% !important;
        max-width:36% !important;
        padding-top:.1rem;
    }
    .bp-page .table-wrap > table td > *,
    .bp-page .side-card-body .table-wrap > table td > *{
        max-width:64%;
        overflow-wrap:anywhere;
        word-break:break-word;
    }
    .bp-page .table-wrap > table td.col-actions > *,
    .bp-page .side-card-body .table-wrap > table td.col-actions > *{
        max-width:100%;
        overflow:visible;
    }
    .bp-page .table-wrap > table td.col-actions,
    .bp-page .actions-cell,
    .bp-page .actions-cell .dropdown{
        overflow:visible !important;
    }

    .bp-page .table-wrap > table td[data-label="#"],
    .bp-page .table-wrap > table td[data-label="Application Reference"],
    .bp-page .table-wrap > table td[data-label="Follow-up"],
    .bp-page .side-card-body .table-wrap > table td[data-label="Assigned To"]{
        display:none !important;
    }
    .bp-page .table-wrap > table td[data-label="Case Reference"]{
        padding-bottom:.3rem !important;margin-bottom:.1rem !important;
        border-bottom:1px solid #f1f5f9 !important;
    }
    .bp-page .table-wrap > table td[data-label="Case Reference"]::before{content:"Case"}
    .bp-page .table-wrap > table td[data-label="Login Date"]::before{content:"Login"}
    .bp-page .table-wrap > table td[data-label="Requested Amount"]::before{content:"Amount"}
    .bp-page .table-wrap > table td[data-label="Case Owner"]::before{content:"Owner"}
    .bp-page .table-wrap > table td.col-actions,
    .bp-page .side-card-body .table-wrap > table td.col-actions{
        padding-top:.35rem !important;margin-top:.1rem !important;
        align-items:center !important;
    }
    .bp-page .lender-mark{display:none}
    .bp-page .action-btn{width:30px;height:30px}
}
</style>
@endpush

@section('content')
@php
    $canEdit = $canEdit ?? false;
    $statusClass = fn ($s) => match ($s) {
        'Bank Login' => 'login',
        'Bank Query' => 'query',
        'Processing' => 'processing',
        'Sanctioned' => 'sanctioned',
        'Rejected' => 'rejected',
        'Disbursed' => 'disbursed',
        default => 'login',
    };
    $queryClass = fn ($q) => strtolower($q->displayStatus()) === 'overdue' ? 'overdue' : (strtolower($q->displayStatus()) === 'resolved' ? 'sanctioned' : 'pending');
    $tabQuery = fn ($key) => array_filter(array_merge(request()->except('page'), ['tab' => $key]));
    $fmtInr = fn ($n) => $n !== null && $n !== '' ? '₹ '.number_format((float) $n, 0) : '—';
    $lenderInitials = function ($name) {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        return strtoupper(substr($parts[0] ?? 'B', 0, 1).substr($parts[1] ?? '', 0, 1));
    };
    $activeIdx = collect($workflow)->search(fn ($s) => $s['key'] === $activeWorkflow);
    if ($activeIdx === false) $activeIdx = 0;
@endphp

<div class="container-fluid p-4 bp-page">
    <div class="page-hero">
        <div>
            <ol class="breadcrumb-bp">
                <li><a href="{{ route('dashboard') }}">Home</a></li>
                <li>/</li>
                <li>Bank Processing</li>
            </ol>
            <h1 class="page-title">Bank Processing</h1>
            <div class="page-sub">Manage lender applications, bank login status and bank queries.</div>
        </div>
        <div class="hero-actions">
            <a href="{{ route('goal.cases.index') }}" class="btn-bp-cancel" style="text-decoration:none"><i class="bi bi-briefcase"></i> Cases</a>
            <a href="{{ route('goal.documents.index') }}" class="btn-bp-cancel" style="text-decoration:none"><i class="bi bi-file-earmark-text"></i> Documents</a>
            <a href="{{ route('goal.sanction-disbursement.index') }}" class="btn-bp-cancel" style="text-decoration:none"><i class="bi bi-award"></i> Sanction</a>
            <a href="{{ route('goal.bank-processing.export', request()->query()) }}" class="btn-bp-cancel" style="text-decoration:none"><i class="bi bi-download"></i> Export</a>
            @if($canEdit)
                <button type="button" class="btn-bp-primary" data-bs-toggle="modal" data-bs-target="#bpAddAppModal">
                    <i class="bi bi-plus-lg"></i> Add Lender Application
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
            <a class="stat-tile tone-blue {{ $tab === 'all' ? 'is-active' : '' }}" href="{{ route('goal.bank-processing.index', $tabQuery('all')) }}">
                <span class="stat-icon"><i class="bi bi-layers"></i></span>
                <div class="stat-meta"><div class="stat-value">{{ number_format($kpis['total']) }}</div><p class="stat-label">Total Applications</p></div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a class="stat-tile tone-green {{ $tab === 'login' ? 'is-active' : '' }}" href="{{ route('goal.bank-processing.index', $tabQuery('login')) }}">
                <span class="stat-icon"><i class="bi bi-box-arrow-in-right"></i></span>
                <div class="stat-meta"><div class="stat-value">{{ number_format($kpis['login']) }}</div><p class="stat-label">Bank Login</p></div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a class="stat-tile tone-orange {{ $tab === 'query' ? 'is-active' : '' }}" href="{{ route('goal.bank-processing.index', $tabQuery('query')) }}">
                <span class="stat-icon"><i class="bi bi-chat-left-text"></i></span>
                <div class="stat-meta"><div class="stat-value">{{ number_format($kpis['queries']) }}</div><p class="stat-label">Bank Queries</p></div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a class="stat-tile tone-purple {{ $tab === 'processing' ? 'is-active' : '' }}" href="{{ route('goal.bank-processing.index', $tabQuery('processing')) }}">
                <span class="stat-icon"><i class="bi bi-gear"></i></span>
                <div class="stat-meta"><div class="stat-value">{{ number_format($kpis['processing']) }}</div><p class="stat-label">Processing</p></div>
            </a>
        </div>
    </div>

    <div class="tabs-card">
        <div class="status-tabs">
            @foreach($tabs as $key => $meta)
                <a href="{{ route('goal.bank-processing.index', $tabQuery($key)) }}" class="status-tab {{ $tab === $key ? 'active' : '' }}">
                    <i class="bi {{ $meta['icon'] }}"></i> {{ $meta['label'] }}
                    <span>({{ number_format($meta['count']) }})</span>
                </a>
            @endforeach
        </div>
    </div>

    <div class="filter-card">
        <form method="GET" action="{{ route('goal.bank-processing.index') }}" id="bpFilterForm">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="hidden" name="date_from" id="date_from" value="{{ request('date_from') }}">
            <input type="hidden" name="date_to" id="date_to" value="{{ request('date_to') }}">
            <div class="filter-grid">
                <div>
                    <label for="q">Search</label>
                    <div class="bp-search">
                        <i class="bi bi-search"></i>
                        <input type="text" name="q" id="q" class="form-control" value="{{ request('q') }}"
                               placeholder="Case Reference / Customer / Application Reference">
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
                    <label for="status">Application Status</label>
                    <select name="status" id="status" class="form-select" data-search="false" @disabled($tab !== 'all')>
                        <option value="">All Status</option>
                        @foreach($statuses as $st)
                            <option value="{{ $st }}" @selected(request('status') === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="owner">Assigned Owner</label>
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
                    <label for="bpDateRange">Login Date</label>
                    <div class="bp-date">
                        <input type="text" id="bpDateRange" class="form-control" placeholder="Select Date Range" autocomplete="off" readonly>
                        <i class="bi bi-calendar3"></i>
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-apply"><i class="bi bi-funnel"></i> Apply</button>
                    <a href="{{ route('goal.bank-processing.index', ['tab' => $tab]) }}" class="btn-reset"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                </div>
            </div>
        </form>
    </div>

    <div class="table-card">
        <div class="table-card-head">
            <div>
                <h5>Lender Applications</h5>
                <p class="head-sub">List of lender applications with bank processing status.</p>
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
                            <th>Login Date</th>
                            <th>Requested Amount</th>
                            <th>Status</th>
                            <th>Assigned Follow-up</th>
                            <th>Case Owner</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($applications as $i => $app)
                        @php $case = $app->loanCase; @endphp
                        <tr>
                            <td data-label="#">{{ $applications->firstItem() + $i }}</td>
                            <td data-label="Case Reference">
                                @if($case)
                                    <a class="case-link" href="{{ route('goal.cases.show', $case) }}?tab=lenders">{{ $case->case_no }}</a>
                                @else — @endif
                            </td>
                            <td data-label="Customer">{{ $case?->customer_name ?: '—' }}</td>
                            <td data-label="Lender">
                                <span class="lender-cell">
                                    <span class="lender-mark">{{ $lenderInitials($app->lender_name) }}</span>
                                    {{ $app->lender_name }}
                                </span>
                            </td>
                            <td data-label="Application Reference">{{ $app->application_ref ?: '—' }}</td>
                            <td data-label="Login Date">{{ optional($app->login_date)->format('d M Y') ?: '—' }}</td>
                            <td data-label="Requested Amount">{{ $fmtInr($app->requested_amount) }}</td>
                            <td data-label="Status"><span class="status-pill {{ $statusClass($app->status) }}">{{ $app->status }}</span></td>
                            <td data-label="Follow-up">
                                @if($app->follow_up_at)
                                    <i class="bi bi-calendar3 text-muted me-1"></i>{{ $app->follow_up_at->format('d M Y') }}
                                @else — @endif
                            </td>
                            <td data-label="Case Owner">{{ $case?->owner?->name ?: '—' }}</td>
                            <td class="col-actions" data-label="Actions">
                                <div class="actions-cell">
                                    @if($case)
                                        <a href="{{ route('goal.cases.show', $case) }}?tab=lenders" class="action-btn" title="View Application"><i class="bi bi-eye"></i></a>
                                    @endif
                                    @if($canEdit && $case)
                                        <button type="button" class="action-btn" title="Raise Query"
                                                data-bs-toggle="modal" data-bs-target="#bpAddQueryModal"
                                                data-app-id="{{ $app->id }}"
                                                data-app-label="{{ $app->lender_name }} — {{ $app->application_ref ?: ('#'.$app->id) }}">
                                            <i class="bi bi-chat-left-text"></i>
                                        </button>
                                    @endif
                                    <div class="dropdown">
                                        <button type="button" class="action-btn" data-bs-toggle="dropdown" aria-label="More actions" aria-expanded="false"><i class="bi bi-three-dots-vertical"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @if($case)
                                                <li><a class="dropdown-item" href="{{ route('goal.cases.show', $case) }}?tab=lenders"><i class="bi bi-folder2-open me-2"></i> Open workspace</a></li>
                                                <li><a class="dropdown-item" href="{{ route('goal.cases.show', $case) }}?tab=queries"><i class="bi bi-chat-dots me-2"></i> View queries</a></li>
                                                <li><a class="dropdown-item" href="{{ route('goal.cases.show', $case) }}?tab=sanction"><i class="bi bi-award me-2"></i> Sanction &amp; Disbursement</a></li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11">
                                <div class="empty-state">
                                    <div>No lender applications found.</div>
                                    <div class="small mt-1">Add a lender application against an existing loan case.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($applications->total() > 0)
                <div class="table-footer-bar">
                    <div>Showing {{ $applications->firstItem() }}—{{ $applications->lastItem() }} of {{ $applications->total() }}</div>
                    <div>{{ $applications->links() }}</div>
                </div>
            @endif
        </div>
    </div>

    <div class="bottom-grid">
        <div class="side-card">
            <div class="side-card-head">
                <h5>Pending Bank Queries</h5>
                <p class="head-sub mb-0">Open queries needing resolution or non-blocking clearance.</p>
            </div>
            <div class="side-card-body">
                <div class="table-wrap" style="min-height:0">
                    <table class="q-table">
                        <thead>
                            <tr>
                                <th>Case Reference</th>
                                <th>Lender</th>
                                <th>Query Summary</th>
                                <th>Assigned To</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($pendingQueries as $query)
                            <tr>
                                <td data-label="Case Reference">
                                    @if($query->loanCase)
                                        <a class="case-link" href="{{ route('goal.cases.show', $query->loanCase) }}?tab=queries">{{ $query->loanCase->case_no }}</a>
                                    @else — @endif
                                </td>
                                <td data-label="Lender">{{ $query->application?->lender_name ?: '—' }}</td>
                                <td data-label="Query Summary" title="{{ $query->details }}">{{ \Illuminate\Support\Str::limit($query->details, 42) }}</td>
                                <td data-label="Assigned To">{{ $query->responsible?->name ?: '—' }}</td>
                                <td data-label="Due Date">{{ optional($query->due_at)->format('d M Y') ?: '—' }}</td>
                                <td data-label="Status"><span class="status-pill {{ $queryClass($query) }}">{{ $query->displayStatus() }}</span></td>
                                <td class="col-actions" data-label="Actions">
                                    @if($query->loanCase)
                                        <a class="action-btn" href="{{ route('goal.cases.show', $query->loanCase) }}?tab=queries" title="View Query"><i class="bi bi-eye"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">No pending bank queries.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="side-card">
            <div class="side-card-head">
                <h5>Workflow</h5>
                <p class="head-sub mb-0">Bank processing flow for lender applications.</p>
            </div>
            <div class="side-card-body">
                <div class="workflow">
                    @foreach($workflow as $idx => $step)
                        @php
                            $state = $idx < $activeIdx ? 'done' : ($idx === $activeIdx ? 'active' : '');
                        @endphp
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
</div>

@if($canEdit)
<div class="modal fade bp-modal" id="bpAddAppModal" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="bpAddAppTitle" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" action="{{ route('goal.bank-processing.applications.store') }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <div class="bp-modal-head">
                    <span class="bp-modal-ico" aria-hidden="true"><i class="bi bi-bank2"></i></span>
                    <div>
                        <h5 class="modal-title" id="bpAddAppTitle">Add Lender Application</h5>
                        <p class="bp-modal-sub">Link a lender application to this loan case.</p>
                    </div>
                </div>
                <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="bp-field-grid">
                    <div class="bp-span-2">
                        <label class="form-label" for="bp_case_id">Loan Case <span class="text-danger">*</span></label>
                        <select name="case_id" id="bp_case_id" class="form-select" required>
                            <option value="">Select case...</option>
                            @foreach($cases as $c)
                                <option value="{{ $c->id }}" data-amount="{{ $c->loan_amount }}">
                                    {{ $c->case_no }} — {{ $c->customer_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="bp_lender_name">Lender <span class="text-danger">*</span></label>
                        <input type="text" name="lender_name" id="bp_lender_name" class="form-control" required maxlength="150" list="bpLenderList" placeholder="e.g. HDFC Bank" autocomplete="off">
                        <datalist id="bpLenderList">
                            <option value="HDFC Bank"></option>
                            <option value="ICICI Bank"></option>
                            <option value="SBI"></option>
                            <option value="Axis Bank"></option>
                            <option value="Kotak Mahindra"></option>
                            <option value="Bajaj Finserv"></option>
                            <option value="Tata Capital"></option>
                        </datalist>
                    </div>
                    <div>
                        <label class="form-label" for="bp_application_ref">Application Reference</label>
                        <input type="text" name="application_ref" id="bp_application_ref" class="form-control" maxlength="100" placeholder="Bank application number">
                    </div>
                    <div>
                        <label class="form-label" for="bp_login_date">Login Date</label>
                        <input type="date" name="login_date" id="bp_login_date" class="form-control" value="{{ now()->toDateString() }}">
                    </div>
                    <div>
                        <label class="form-label" for="bpAppStatus">Status</label>
                        <select name="status" class="form-select" id="bpAppStatus">
                            <option value="Bank Login" selected>Bank Login</option>
                            <option value="Processing">Processing</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="bp_follow_up_at">Follow-up</label>
                        <input type="date" name="follow_up_at" id="bp_follow_up_at" class="form-control">
                    </div>
                    <div>
                        <label class="form-label" for="bpReqAmount">Requested Amount</label>
                        <div class="bp-amount-wrap">
                            <span>₹</span>
                            <input type="number" step="0.01" name="requested_amount" id="bpReqAmount" class="form-control" placeholder="0.00">
                        </div>
                    </div>
                    <div class="bp-span-2">
                        <label class="form-label" for="bp_evidence">Supporting Evidence</label>
                        <div class="bp-file">
                            <input type="file" name="evidence" id="bp_evidence" accept=".pdf,.jpg,.jpeg,.png,.webp">
                            <div class="bp-file-hint">PDF / JPG / PNG / WEBP · drag &amp; drop or choose a file</div>
                        </div>
                    </div>
                    <div class="bp-span-2 d-none" id="bpRejectWrap">
                        <label class="form-label" for="bp_rejection_reason">Rejection Reason <span class="text-danger">*</span></label>
                        <input type="text" name="rejection_reason" id="bp_rejection_reason" class="form-control" maxlength="500" placeholder="Mandatory when status is Rejected">
                    </div>
                    <div class="bp-span-2">
                        <label class="form-label" for="bp_notes">Notes</label>
                        <textarea name="notes" id="bp_notes" class="form-control" rows="2" maxlength="1000" placeholder="Optional notes for this application"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-bp-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-bp-primary"><i class="bi bi-check2"></i> Save Application</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade bp-modal" id="bpAddQueryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="" class="modal-content" id="bpAddQueryForm">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Raise Bank Query</h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Application</label>
                    <input type="text" class="form-control" id="bpQueryAppLabel" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Query Details <span class="text-danger">*</span></label>
                    <textarea name="details" class="form-control" rows="3" required maxlength="2000"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Responsible Employee <span class="text-danger">*</span></label>
                    <select name="responsible_user_id" class="form-select" required>
                        <option value="">Select employee...</option>
                        @foreach($assignees as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Due Date <span class="text-danger">*</span></label>
                    <input type="date" name="due_at" class="form-control" required>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="blocks_processing" value="1" id="bpBlocks" checked>
                    <label class="form-check-label" for="bpBlocks">Blocks processing until resolved</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-bp-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-bp-primary"><i class="bi bi-chat-left-text"></i> Raise Query</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('bpFilterForm');
    if (form) {
        form.querySelectorAll('select').forEach(function (el) {
            el.addEventListener('change', function () { form.submit(); });
        });
        var q = document.getElementById('q');
        if (q) q.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); form.submit(); }
        });
        var rangeEl = document.getElementById('bpDateRange');
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

    var caseSelect = document.querySelector('#bpAddAppModal select[name="case_id"]');
    var amt = document.getElementById('bpReqAmount');
    if (caseSelect && amt) {
        caseSelect.addEventListener('change', function () {
            var opt = caseSelect.options[caseSelect.selectedIndex];
            if (opt && opt.getAttribute('data-amount')) amt.value = opt.getAttribute('data-amount');
        });
    }
    var statusEl = document.getElementById('bpAppStatus');
    var rejectWrap = document.getElementById('bpRejectWrap');
    if (statusEl && rejectWrap) {
        statusEl.addEventListener('change', function () {
            rejectWrap.classList.toggle('d-none', statusEl.value !== 'Rejected');
        });
    }

    var queryModal = document.getElementById('bpAddQueryModal');
    var queryForm = document.getElementById('bpAddQueryForm');
    if (queryModal && queryForm) {
        queryModal.addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            if (!btn) return;
            var id = btn.getAttribute('data-app-id');
            queryForm.action = @json(url('goal/bank-processing/applications')) + '/' + id + '/queries';
            document.getElementById('bpQueryAppLabel').value = btn.getAttribute('data-app-label') || '';
        });
    }
})();
</script>
@endpush
