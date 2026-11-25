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
.ll-page .leads-table-wrap table{min-width:1180px;width:100%;margin:0}
.ll-page table thead th{
    font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;
    color:#64748b;font-weight:700;background:#f8fafc;
    border-bottom:1px solid #e2e8f0;white-space:nowrap;padding:.75rem .85rem;
}
.ll-page table tbody td{
    vertical-align:middle;font-size:.875rem;color:#334155;
    border-color:#f1f5f9;padding:.85rem .85rem;white-space:nowrap;
}
.ll-page table tbody tr{transition:background .15s}
.ll-page table tbody tr:hover{background:#f8fafc}
.ll-page .ref-cell{font-weight:800;color:#0f172a;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.8rem}
.ll-page .ref-sub{font-size:.72rem;color:#94a3b8;margin-top:3px;font-weight:500}
.ll-page .cust-cell{display:flex;align-items:center;gap:10px;min-width:0}
.ll-page .cust-avatar{
    width:36px;height:36px;border-radius:11px;flex-shrink:0;
    display:inline-flex;align-items:center;justify-content:center;
    background:linear-gradient(145deg,var(--theme-100),var(--theme-200));
    color:var(--theme-primary-hover);font-weight:800;font-size:.78rem;
}
.ll-page .cust-name{font-weight:700;color:#0f172a;line-height:1.25}
.ll-page .cust-mobile{font-size:.78rem;color:#64748b;margin-top:2px}
.ll-page .amount-cell{font-weight:700;color:#0f172a;font-variant-numeric:tabular-nums}
.ll-page .amount-cell .muted{color:#94a3b8;font-weight:500}
.ll-page .meta-stack{display:flex;flex-direction:column;gap:6px;min-width:0;align-items:flex-start}
.ll-page .meta-name{font-weight:650;color:#334155;font-size:.84rem;line-height:1.25}
.ll-page .meta-sub{
    font-size:.72rem;color:#64748b;line-height:1.35;font-weight:500;
    white-space:normal;max-width:160px;
}
.ll-page .created-by{
    display:inline-flex;align-items:center;gap:5px;
    padding:3px 8px;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0;
    color:#64748b;font-size:.7rem;font-weight:600;line-height:1.3;max-width:170px;
}
.ll-page .created-by i{color:#94a3b8;font-size:.7rem;flex-shrink:0}
.ll-page .created-by span{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ll-page .amount-cell .currency{color:#64748b;font-weight:600;margin-right:2px}
.ll-page .type-pill,
.ll-page .source-pill,
.ll-page .status-pill,
.ll-page .product-pill,
.ll-page .branch-pill,
.ll-page .assign-pill,
.ll-page .disp-pill{
    display:inline-flex;align-items:center;justify-content:center;gap:4px;
    padding:.28rem .7rem;border-radius:999px;font-size:.72rem;font-weight:700;
    max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
}
.ll-page .type-pill.immediate{background:var(--theme-100);color:var(--theme-primary-hover)}
.ll-page .type-pill.future{background:#ffedd5;color:#c2410c}
.ll-page .source-pill.channel{background:#ede9fe;color:#6d28d9}
.ll-page .source-pill.vrm{background:#dcfce7;color:#15803d}
.ll-page .source-pill.manual,
.ll-page .source-pill.other{background:#f1f5f9;color:#475569}
.ll-page .status-pill{background:#f1f5f9;color:#334155}
.ll-page .status-pill.pending{background:#fef3c7;color:#b45309}
.ll-page .status-pill.future{background:#ede9fe;color:#6d28d9}
.ll-page .status-pill.closed{background:#fee2e2;color:#b91c1c}
.ll-page .status-pill.active{background:#dcfce7;color:#15803d}
.ll-page .status-pill.assigned{background:#e0f2fe;color:#0369a1}
.ll-page .product-pill{background:#ecfeff;color:#0e7490;border:1px solid #cffafe}
.ll-page .product-pill.empty{background:#f8fafc;color:#94a3b8;border-color:#e2e8f0;font-weight:600}
.ll-page .branch-pill{background:#f1f5f9;color:#475569}
.ll-page .assign-pill{background:#eff6ff;color:#1d4ed8}
.ll-page .assign-pill.empty{background:#fff7ed;color:#c2410c;border:1px dashed #fdba74}
.ll-page .disp-pill{background:#faf5ff;color:#7e22ce}

.ll-page .action-group{display:inline-flex;align-items:center;gap:6px;flex-wrap:wrap}
.ll-page .action-btn{
    width:34px;height:34px;border-radius:10px;border:1.5px solid #e2e8f0;
    background:#fff;color:#475569;
    display:inline-flex;align-items:center;justify-content:center;
    text-decoration:none;transition:.15s ease;cursor:pointer;padding:0;
}
.ll-page .action-btn:hover{background:#f8fafc;color:#0f172a;border-color:#cbd5e1}
.ll-page .action-btn.view{border-color:var(--theme-200);background:var(--theme-50);color:var(--theme-primary)}
.ll-page .action-btn.edit{border-color:#bfdbfe;background:#eff6ff;color:#2563eb}
.ll-page .action-btn.note{border-color:#fde68a;background:#fffbeb;color:#b45309}
.ll-page .action-btn.status{border-color:#bbf7d0;background:#f0fdf4;color:#15803d}
.ll-page .action-btn.allocate{border-color:#ddd6fe;background:#f5f3ff;color:#6d28d9}
.ll-page .action-btn.case{border-color:#e2e8f0;background:#f8fafc;color:#64748b}

.ll-lead-modal .modal-content{
    border:none;border-radius:16px;overflow:hidden;
    box-shadow:0 24px 48px rgba(15,23,42,.18);
}
.ll-lead-modal .modal-header{
    border-bottom:1px solid #e8edf5;padding:18px 24px 16px;align-items:flex-start;background:#fff;
}
.ll-lead-modal .modal-title-wrap{display:flex;align-items:flex-start;gap:12px}
.ll-lead-modal .modal-icon{
    width:44px;height:44px;border-radius:12px;background:var(--theme-100);color:var(--theme-primary);
    display:inline-flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;
}
.ll-lead-modal .modal-title{font-size:1.2rem;font-weight:800;color:#0f172a;margin:0;line-height:1.25}
.ll-lead-modal .modal-sub{font-size:.86rem;color:#64748b;margin-top:3px;line-height:1.35}
.ll-lead-modal .modal-close{
    width:36px;height:36px;border:0;border-radius:10px;background:#f1f5f9;color:#64748b;
    display:inline-flex;align-items:center;justify-content:center;margin-top:2px;
}
.ll-lead-modal .modal-close:hover{background:#e2e8f0;color:#0f172a}
.ll-lead-modal .modal-body{padding:18px 24px 12px}
.ll-lead-modal .modal-footer{
    border-top:1px solid #e8edf5;padding:14px 24px;background:#fff;
    display:flex;align-items:center;justify-content:flex-end;gap:10px;
}
.ll-lead-modal .section-bar{
    display:flex;align-items:center;gap:8px;
    background:var(--theme-50);color:var(--theme-primary-hover);border-radius:8px;
    padding:10px 14px;margin:0 0 14px;font-weight:700;font-size:.9rem;
}
.ll-lead-modal .form-label{font-size:.8rem;font-weight:600;color:#334155;margin-bottom:6px}
.ll-lead-modal .form-label .req{color:#dc2626}
.ll-lead-modal .form-control,
.ll-lead-modal .form-select{
    min-height:42px;border-radius:10px;border:1px solid #d1d5db;font-size:.9rem;
}
.ll-lead-modal .form-control:focus,
.ll-lead-modal .form-select:focus{
    border-color:var(--theme-300);box-shadow:0 0 0 3px var(--theme-primary-ring);
}
.ll-lead-modal .form-section{margin-bottom:18px}
.ll-lead-modal .form-section:last-child{margin-bottom:8px}
.ll-lead-modal .input-icon{position:relative}
.ll-lead-modal .input-icon > i,
.ll-lead-modal .input-icon > .rupee{
    position:absolute;left:12px;top:50%;transform:translateY(-50%);
    color:#94a3b8;font-size:.95rem;pointer-events:none;z-index:2;
}
.ll-lead-modal .input-icon .rupee{font-weight:700;font-style:normal;color:#64748b}
.ll-lead-modal .input-icon.textarea-icon > i{top:14px;transform:none}
.ll-lead-modal .input-icon .form-control,
.ll-lead-modal .input-icon .form-select{padding-left:2.2rem}
.ll-lead-modal .input-icon textarea.form-control{min-height:88px;padding-top:10px;padding-bottom:10px}
.ll-lead-modal .input-icon .form-control[readonly],
.ll-lead-modal .input-icon .form-control:disabled{background:#f1f5f9;color:#475569}
.ll-lead-modal .select2-container{width:100%!important}
.ll-lead-modal .select2-container--default .select2-selection--single{
    min-height:42px!important;height:42px!important;border:1px solid #d1d5db!important;
    border-radius:10px!important;background:#fff!important;
}
.ll-lead-modal .select2-container--default .select2-selection--single .select2-selection__rendered{
    line-height:40px!important;padding-left:.85rem!important;padding-right:2rem!important;font-size:.9rem!important;color:#0f172a!important;
}
.ll-lead-modal .input-icon .select2-container{display:block}
.ll-lead-modal .input-icon:has(.select2-container) > i,
.ll-lead-modal .input-icon:has(.select2-container) > .rupee{display:none}
.ll-lead-modal .select2-container--default .select2-selection--single .select2-selection__arrow{
    height:40px!important;
}
.ll-lead-modal .select2-container--default.select2-container--focus .select2-selection--single,
.ll-lead-modal .select2-container--default.select2-container--open .select2-selection--single{
    border-color:var(--theme-300)!important;box-shadow:0 0 0 3px var(--theme-primary-ring);
}
.ll-lead-modal .select2-dropdown{
    border:1px solid #e2e8f0!important;border-radius:10px!important;overflow:hidden;
    box-shadow:0 12px 28px rgba(15,23,42,.12);
}
.ll-lead-modal .select2-results__option{font-size:.9rem;padding:.55rem .85rem}
.ll-lead-modal .select2-results__option--highlighted{background:var(--theme-primary)!important}
.ll-lead-modal .btn-save{
    background:var(--theme-primary);color:#fff;border:none;border-radius:10px;
    height:42px;padding:0 16px;font-weight:700;display:inline-flex;align-items:center;gap:6px;
}
.ll-lead-modal .btn-save:hover{background:var(--theme-primary-hover);color:#fff}
.ll-lead-modal .btn-cancel{
    border:1px solid var(--theme-300);background:#fff;color:var(--theme-primary);border-radius:10px;
    height:42px;padding:0 14px;font-weight:600;
}
.ll-lead-modal .current-status-box{
    display:flex;align-items:center;justify-content:space-between;gap:12px;
    padding:12px 14px;border-radius:12px;border:1px solid #e2e8f0;background:#f8fafc;
}
.ll-lead-modal .current-status-box .label{font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em}
.ll-lead-modal .ll-status-hint{
    display:flex;gap:8px;align-items:flex-start;padding:12px 14px;border-radius:12px;
    background:var(--theme-50);border:1px solid var(--theme-200);color:#475569;font-size:.82rem;margin-top:4px;
}
.ll-lead-modal .ll-status-hint i{color:var(--theme-primary);flex-shrink:0;margin-top:2px}
.ll-lead-modal .field-grid{
    display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:14px 18px;
}
@media (max-width:767.98px){
    .ll-lead-modal .field-grid{grid-template-columns:1fr}
}

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
#createLeadModal .form-label .req { color: #dc2626; }
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
        if (str_contains($s, 'closed') || str_contains($s, 'reject') || str_contains($s, 'lost')) return 'closed';
        if (str_contains($s, 'future')) return 'future';
        if (str_contains($s, 'assigned') || str_contains($s, 'contact') || str_contains($s, 'qualified')) return 'assigned';
        if (str_contains($s, 'pending') || str_contains($s, 'follow')) return 'pending';
        if (str_contains($s, 'document') || str_contains($s, 'bank') || str_contains($s, 'ready')) return 'active';
        return '';
    };
    $sourceClass = function ($source) {
        $s = strtolower(trim((string) $source));
        if ($s === 'channel' || str_contains($s, 'channel')) return 'channel';
        if ($s === 'vrm' || str_contains($s, 'vrm')) return 'vrm';
        return 'manual';
    };
    $leadSources = $leadSources ?? collect();
    $defaultSourceName = request()->boolean('channel')
        ? (optional($leadSources->first(fn ($s) => stripos($s->name, 'channel') !== false))->name ?? 'Channel')
        : (optional($leadSources->first(fn ($s) => strcasecmp($s->name, 'Manual') === 0))->name
            ?? optional($leadSources->first())->name
            ?? 'Manual');
    $initials = function (?string $name) {
        $name = trim((string) $name);
        if ($name === '') return '?';
        $parts = preg_split('/\s+/', $name) ?: [];
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $second = mb_substr($parts[1] ?? '', 0, 1);
        return strtoupper($first . $second) ?: '?';
    };
    $defaultSource = old('source', $defaultSourceName);
    $canManage = !empty($canManage);
    $canAllocate = !empty($canAllocate);
    $branchLocked = !empty($branchLocked);
    $leadNextById = $leadNextById ?? [];
    $leadEditableById = $leadEditableById ?? [];
    $tableColCount = 9;
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
                    @foreach($leadSources as $src)
                        <option value="{{ $src->name }}" @selected(request('source')===$src->name)>{{ $src->name }}</option>
                    @endforeach
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
                    <div class="head-sub">Product, amount, status and assignment at a glance.</div>
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
                            <th>Product</th>
                            <th>Amount</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Branch</th>
                            <th>Assignee</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($leads as $lead)
                        @php
                            $nextStatuses = $leadNextById[$lead->id] ?? [];
                            $needsAllocation = !empty($canAllocate) && $lead->status === 'Pending BM Allocation';
                            $canStatus = !empty($canManage) && $nextStatuses !== [];
                            $canEditThis = !empty($leadEditableById[$lead->id]);
                            $amountFormatted = $lead->loan_amount !== null
                                ? \App\Helpers\MoneyFormatter::formatIndian((float) $lead->loan_amount, 0)
                                : null;
                            $assigneeRole = optional($lead->assignee)->goalCrmRoleLabel();
                            $creatorRole = optional($lead->creator)->goalCrmRoleLabel();
                            $creatorLabel = trim(($lead->creator->name ?? '') . ($creatorRole && ($lead->creator->name ?? '') !== $creatorRole ? ' ('.$creatorRole.')' : ''));
                        @endphp
                        <tr>
                            <td data-label="Ref">
                                <div class="ref-cell">{{ $lead->ref_no }}</div>
                                <div class="ref-sub">{{ optional($lead->created_at)->format('d M Y, g:i A') }}</div>
                            </td>
                            <td data-label="Customer">
                                <div class="cust-cell">
                                    <span class="cust-avatar">{{ $initials($lead->customer_name) }}</span>
                                    <div>
                                        <div class="cust-name">{{ $lead->customer_name }}</div>
                                        <div class="cust-mobile">{{ $lead->mobile ?: '-' }}</div>
                                        @if($lead->disposition)
                                            <span class="disp-pill mt-1">{{ $lead->disposition }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td data-label="Product">
                                @if($lead->product)
                                    <span class="product-pill" title="{{ $lead->product }}"><i class="bi bi-box-seam"></i> {{ $lead->product }}</span>
                                @else
                                    <span class="product-pill empty">No product</span>
                                @endif
                            </td>
                            <td data-label="Amount">
                                @if($amountFormatted)
                                    <div class="amount-cell"><span class="currency">Rs.</span>{{ $amountFormatted }}</div>
                                @else
                                    <div class="amount-cell muted">-</div>
                                @endif
                            </td>
                            <td data-label="Source">
                                <span class="source-pill {{ $sourceClass($lead->source) }}">{{ $lead->source ?: '-' }}</span>
                            </td>
                            <td data-label="Status">
                                <span class="status-pill {{ $statusClass($lead->status) }}" title="{{ $lead->status }}">{{ $lead->status }}</span>
                            </td>
                            <td data-label="Branch">
                                <span class="branch-pill">{{ $lead->orgUnit?->name ?? '-' }}</span>
                            </td>
                            <td data-label="Assignee">
                                <div class="meta-stack">
                                    @if($lead->assignee)
                                        <span class="assign-pill">{{ $lead->assignee->name }}</span>
                                        @if($assigneeRole)
                                            <span class="meta-sub">{{ $assigneeRole }}</span>
                                        @endif
                                    @else
                                        <span class="assign-pill empty">Unassigned</span>
                                        @if($creatorLabel !== '')
                                            <span class="created-by" title="Created by {{ $creatorLabel }}">
                                                <i class="bi bi-person"></i>
                                                <span>{{ $creatorLabel }}</span>
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </td>
                            <td class="col-actions" data-label="Actions">
                                <div class="action-group">
                                    <a href="{{ route('goal.leads.show', $lead) }}"
                                       class="action-btn view"
                                       data-bs-toggle="tooltip"
                                       data-bs-title="View details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if(!empty($canManage))
                                        @if($canEditThis || $canStatus)
                                        <button type="button"
                                                class="action-btn edit btn-ll-edit"
                                                data-bs-toggle="tooltip"
                                                data-bs-title="Edit lead"
                                                data-update-url="{{ route('goal.leads.update', $lead) }}"
                                                data-ref="{{ $lead->ref_no }}"
                                                data-customer="{{ $lead->customer_name }}"
                                                data-mobile="{{ $lead->mobile }}"
                                                data-lead-type="{{ $lead->lead_type }}"
                                                data-product="{{ $lead->product }}"
                                                data-loan-amount="{{ $lead->loan_amount }}"
                                                data-source="{{ $lead->source }}"
                                                data-branch-id="{{ $lead->goal_org_unit_id }}"
                                                data-branch-name="{{ $lead->orgUnit?->name ?? '' }}"
                                                data-disposition="{{ $lead->disposition }}"
                                                data-consent="{{ $lead->consent_recorded ? '1' : '0' }}"
                                                data-lead-id="{{ $lead->id }}"
                                                data-current-status="{{ $lead->status }}"
                                                data-can-edit="{{ $canEditThis ? '1' : '0' }}"
                                                data-follow-up="{{ optional($lead->follow_up_at)->format('d-m-Y H:i') }}">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        @endif
                                        <button type="button"
                                                class="action-btn note btn-ll-note"
                                                data-bs-toggle="tooltip"
                                                data-bs-title="Add note"
                                                data-note-url="{{ route('goal.leads.notes', $lead) }}"
                                                data-ref="{{ $lead->ref_no }}">
                                            <i class="bi bi-journal-plus"></i>
                                        </button>
                                    @endif
                                    @if($needsAllocation)
                                        <a href="{{ route('goal.allocation.index', ['q' => $lead->ref_no]) }}"
                                           class="action-btn allocate"
                                           data-bs-toggle="tooltip"
                                           data-bs-title="Allocate to sales">
                                            <i class="bi bi-person-check"></i>
                                        </a>
                                    @endif
                                    @if($lead->loanCase)
                                        <a href="{{ route('goal.cases.show', $lead->loanCase) }}"
                                           class="action-btn case"
                                           data-bs-toggle="tooltip"
                                           data-bs-title="View case {{ $lead->loanCase->case_no }}">
                                            <i class="bi bi-folder2-open"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $tableColCount }}" class="col-empty" data-label="">
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
                        Showing {{ $leads->firstItem() }}-{{ $leads->lastItem() }} of {{ $leads->total() }}
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
                            <label class="form-label">Customer name <span class="req">*</span></label>
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
                            <label class="form-label">Disposition <span class="req">*</span></label>
                            @php
                                $createDispositions = $dispositions ?? collect();
                                $oldDisposition = old('disposition');
                                $defaultDisposition = $oldDisposition
                                    ?: optional($createDispositions->firstWhere('name', 'Interested'))->name
                                    ?: optional($createDispositions->first())->name;
                            @endphp
                            <select name="disposition" id="create_disposition" class="form-select js-select2"
                                    data-placeholder="Select disposition" data-search="false" required>
                                <option value="">Select disposition</option>
                                @foreach($createDispositions as $d)
                                    <option value="{{ $d->name }}"
                                            data-maps-to-lead-type="{{ $d->maps_to_lead_type ?: ($d->name === 'Callback' ? 'future' : 'immediate') }}"
                                            @selected($defaultDisposition === $d->name)>{{ $d->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="lead_type" id="create_lead_type" value="{{ old('lead_type', 'immediate') }}">
                            @error('disposition')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Source</label>
                            <select name="source" id="create_source" class="form-select">
                                @forelse($leadSources as $src)
                                    <option value="{{ $src->name }}" @selected($defaultSource===$src->name)>{{ $src->name }}</option>
                                @empty
                                    <option value="Manual" @selected($defaultSource==='Manual')>Manual</option>
                                @endforelse
                            </select>
                        </div>
                        <div class="col-md-4" id="channelPartnerWrap" style="{{ stripos((string) $defaultSource, 'channel') !== false ? '' : 'display:none' }}">
                            <label class="form-label">Channel partner</label>
                            <input name="channel_partner" class="form-control" value="{{ old('channel_partner') }}"
                                   placeholder="Partner name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Product</label>
                            <select name="product" class="form-select">
                                <option value=""> Select </option>
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
                            @php
                                $branchLocked = !empty($branchLocked);
                                $defaultBranchId = old('goal_org_unit_id', auth()->user()->goal_org_unit_id);
                            @endphp
                            @if($branchLocked)
                                <input type="hidden" name="goal_org_unit_id" value="{{ $defaultBranchId }}">
                                <input type="text" class="form-control" value="{{ optional($branches->firstWhere('id', (int) $defaultBranchId))->name ?? optional(auth()->user()->orgUnit)->name ?? 'Your branch' }}" readonly disabled>
                            @else
                                <select name="goal_org_unit_id" class="form-select js-select2" data-placeholder="Select branch">
                                    <option value="">Select branch</option>
                                    @foreach($branches as $b)
                                        <option value="{{ $b->id }}" @selected(old('goal_org_unit_id', auth()->user()->goal_org_unit_id)==$b->id)>{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="create_follow_up_at">Follow-up <span class="req" id="create_follow_req" style="display:none;">*</span></label>
                            <input type="text" name="follow_up_at" id="create_follow_up_at"
                                   class="form-control js-datetimepicker"
                                   value="{{ old('follow_up_at') }}" placeholder="dd-mm-yyyy hh:mm" autocomplete="off">
                            <div class="form-text text-muted" style="font-size:.72rem;">Required when disposition maps to Future / Callback.</div>
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

@if($canManage)
{{-- Edit lead (call-edit style + workflow status) --}}
<div class="modal fade ll-lead-modal" id="llEditLeadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" id="llEditLeadForm" class="modal-content">
            @csrf
            @method('PUT')
            <input type="hidden" name="lead_type" id="ll_edit_lead_type" value="immediate">
            <input type="hidden" name="source" id="ll_edit_source" value="">
            <input type="hidden" name="disposition" id="ll_edit_disposition" value="">

            <div class="modal-header">
                <div class="modal-title-wrap">
                    <span class="modal-icon"><i class="bi bi-pencil-square"></i></span>
                    <div>
                        <h5 class="modal-title">Edit Lead</h5>
                        <div class="modal-sub" id="llEditLeadSub">Update customer, loan details and workflow status.</div>
                    </div>
                </div>
                <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>

            <div class="modal-body">
                <div class="form-section">
                    <div class="section-bar"><i class="bi bi-person"></i> Customer Details</div>
                    <div class="field-grid">
                        <div class="field">
                            <label class="form-label" for="ll_edit_customer_name">Customer <span class="req">*</span></label>
                            <div class="input-icon">
                                <i class="bi bi-person"></i>
                                <input type="text" name="customer_name" id="ll_edit_customer_name"
                                       class="form-control" placeholder="Enter customer name"
                                       required maxlength="150" autocomplete="off">
                            </div>
                        </div>
                        <div class="field">
                            <label class="form-label" for="ll_edit_mobile">Mobile <span class="req">*</span></label>
                            <div class="input-icon">
                                <i class="bi bi-telephone"></i>
                                <input type="text" name="mobile" id="ll_edit_mobile"
                                       class="form-control" placeholder="10-digit mobile" maxlength="10"
                                       inputmode="numeric" pattern="[0-9]{10}" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-bar"><i class="bi bi-telephone"></i> Call / Lead Details</div>
                    <div class="field-grid">
                        <div class="field">
                            <label class="form-label" for="ll_edit_disposition_label">Disposition</label>
                            <div class="input-icon">
                                <i class="bi bi-tag"></i>
                                <input type="text" id="ll_edit_disposition_label" class="form-control" value="" readonly disabled>
                            </div>
                        </div>
                        <div class="field">
                            <label class="form-label" for="ll_edit_branch">Branch</label>
                            @if($branchLocked)
                                <input type="hidden" name="goal_org_unit_id" id="ll_edit_branch" value="">
                                <div class="input-icon">
                                    <i class="bi bi-building"></i>
                                    <input type="text" id="ll_edit_branch_label" class="form-control" value="" readonly disabled>
                                </div>
                            @else
                                <select name="goal_org_unit_id" id="ll_edit_branch"
                                        class="form-select js-select2"
                                        data-placeholder="Select branch" data-allow-clear="true">
                                    <option value="">Select branch</option>
                                    @foreach($branches as $b)
                                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                        <div class="field">
                            <label class="form-label">Current status</label>
                            <div class="current-status-box" style="min-height:42px;padding:8px 12px;">
                                <div id="ll_edit_current_wrap"></div>
                            </div>
                        </div>
                        <div class="field" id="ll_edit_status_wrap">
                            <label class="form-label" for="ll_edit_to_status">Move to</label>
                            <select name="to_status" id="ll_edit_to_status" class="form-select js-select2 no-select2"
                                    data-select2-skip="1" data-placeholder="Keep current status" data-search="false">
                                <option value="">Keep current status</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-bar"><i class="bi bi-bullseye"></i> Lead Information</div>
                    <div class="field-grid">
                        <div class="field">
                            <label class="form-label" for="ll_edit_product">Product</label>
                            <select name="product" id="ll_edit_product"
                                    class="form-select js-select2"
                                    data-placeholder="Select product" data-allow-clear="true">
                                <option value=""></option>
                                @foreach($products as $p)
                                    <option value="{{ $p->product_name }}">{{ $p->product_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field" id="ll_edit_follow_wrap" style="display:none;">
                            <label class="form-label" for="ll_edit_follow_up">Follow-up <span class="req">*</span></label>
                            <div class="input-icon">
                                <i class="bi bi-calendar3"></i>
                                <input type="text" name="follow_up_at" id="ll_edit_follow_up"
                                       class="form-control js-datetimepicker"
                                       placeholder="dd-mm-yyyy --:--" autocomplete="off">
                            </div>
                        </div>
                        <div class="field" id="ll_edit_closure_wrap" style="display:none;">
                            <label class="form-label" for="ll_edit_closure">Closure reason <span class="req">*</span></label>
                            <div class="input-icon">
                                <i class="bi bi-x-octagon"></i>
                                <input name="closure_reason" id="ll_edit_closure" class="form-control"
                                       placeholder="Required when closing">
                            </div>
                        </div>
                        <div class="field check-align d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" name="consent_recorded" value="1" id="ll_edit_consent">
                                <label class="form-check-label" for="ll_edit_consent">Consent recorded</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-bar"><i class="bi bi-cash-coin"></i> Loan Details</div>
                    <div class="field-grid">
                        <div class="field">
                            <label class="form-label" for="ll_edit_loan_amount">Loan Amount</label>
                            <div class="input-icon">
                                <span class="rupee">Rs.</span>
                                <input type="text" inputmode="decimal" data-decimals="2" name="loan_amount"
                                       id="ll_edit_loan_amount" class="form-control js-decimal"
                                       placeholder="Enter loan amount">
                            </div>
                        </div>
                        <div class="field">
                            <label class="form-label" for="ll_edit_notes">Notes</label>
                            <div class="input-icon textarea-icon">
                                <i class="bi bi-file-text"></i>
                                <textarea name="notes" id="ll_edit_notes" class="form-control" rows="3"
                                          placeholder="Add remarks, customer response, or next steps..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ll-status-hint" id="ll_edit_status_hint">
                    <i class="bi bi-info-circle"></i>
                    <span>Allocation to sales is done from <strong>BM Allocation</strong>. Moving to Documents Pending creates the case automatically.</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-save"><i class="bi bi-floppy"></i> Save changes</button>
            </div>
        </form>
    </div>
</div>

{{-- Add note --}}
<div class="modal fade ll-lead-modal" id="llNoteLeadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="llNoteLeadForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <div class="modal-title-wrap">
                    <span class="modal-icon"><i class="bi bi-journal-plus"></i></span>
                    <div>
                        <h5 class="modal-title">Add Note</h5>
                        <div class="modal-sub" id="llNoteLeadSub">Notes are saved in the lead activity log.</div>
                    </div>
                </div>
                <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Note <span class="text-danger">*</span></label>
                <textarea name="note" id="ll_note_body" class="form-control" rows="4" required maxlength="2000"
                          placeholder="Add follow-up remarks, customer response, or next steps..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-save"><i class="bi bi-check2"></i> Save note</button>
            </div>
        </form>
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
        const v = String(sourceEl.value || '').toLowerCase();
        partnerWrap.style.display = v.indexOf('channel') !== -1 ? '' : 'none';
    }
    sourceEl?.addEventListener('change', syncChannelPartner);
    syncChannelPartner();

    const dispositionEl = document.getElementById('create_disposition');
    const leadTypeEl = document.getElementById('create_lead_type');
    const followUpEl = document.getElementById('create_follow_up_at');
    const followReq = document.getElementById('create_follow_req');
    function syncCreateDisposition() {
        if (!dispositionEl || !leadTypeEl) return;
        const opt = dispositionEl.options[dispositionEl.selectedIndex];
        const mapsTo = (opt && opt.getAttribute('data-maps-to-lead-type')) || 'immediate';
        const leadType = mapsTo === 'future' ? 'future' : 'immediate';
        leadTypeEl.value = leadType;
        if (followUpEl) followUpEl.required = leadType === 'future';
        if (followReq) followReq.style.display = leadType === 'future' ? '' : 'none';
    }
    dispositionEl?.addEventListener('change', syncCreateDisposition);
    if (window.jQuery) {
        window.jQuery(document).on('change', '#create_disposition', syncCreateDisposition);
    }
    syncCreateDisposition();

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

    function setVal(id, value) {
        const el = document.getElementById(id);
        if (!el) return;
        const v = value == null ? '' : String(value);
        if (el.tagName === 'SELECT' && v && !Array.from(el.options).some(function (o) { return o.value === v; })) {
            el.add(new Option(v, v, true, true));
        }
        el.value = v;
        if (window.jQuery && window.jQuery.fn.select2 && window.jQuery(el).hasClass('select2-hidden-accessible')) {
            window.jQuery(el).val(v || null).trigger('change');
        }
    }

    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function statusTone(status) {
        const s = String(status || '').toLowerCase();
        if (s.indexOf('closed') !== -1 || s.indexOf('lost') !== -1) return 'closed';
        if (s.indexOf('future') !== -1) return 'future';
        if (s.indexOf('assigned') !== -1 || s.indexOf('contact') !== -1 || s.indexOf('qualified') !== -1) return 'assigned';
        if (s.indexOf('pending') !== -1) return 'pending';
        if (s.indexOf('document') !== -1 || s.indexOf('bank') !== -1 || s.indexOf('ready') !== -1) return 'active';
        return '';
    }

    function syncLlEditStatusFields() {
        const to = (document.getElementById('ll_edit_to_status')?.value || '');
        const followWrap = document.getElementById('ll_edit_follow_wrap');
        const closureWrap = document.getElementById('ll_edit_closure_wrap');
        const followInput = document.getElementById('ll_edit_follow_up');
        const closureInput = document.getElementById('ll_edit_closure');
        if (followWrap) followWrap.style.display = to === 'Future Prospect' ? '' : 'none';
        if (closureWrap) closureWrap.style.display = to === 'Closed / Lost' ? '' : 'none';
        if (followInput) followInput.required = to === 'Future Prospect';
        if (closureInput) closureInput.required = to === 'Closed / Lost';
        if (to !== 'Future Prospect' && followInput) followInput.value = followInput.value;
        if (to !== 'Closed / Lost' && closureInput) closureInput.value = '';
    }

    const llLeadNextById = @json($leadNextById ?? []);

    function rebuildMoveToOptions(next, modalEl) {
        const toEl = document.getElementById('ll_edit_to_status');
        const statusWrap = document.getElementById('ll_edit_status_wrap');
        if (!toEl) return;
        const $ = window.jQuery;
        const $to = $ ? $(toEl) : null;
        if ($to && $to.hasClass('select2-hidden-accessible')) {
            $to.select2('destroy');
        }
        // Allow manual Select2 init for this control (skipped on page load).
        toEl.classList.remove('no-select2');
        toEl.removeAttribute('data-select2-skip');

        const options = Array.isArray(next) ? next.slice() : [];
        toEl.innerHTML = '';
        const keep = document.createElement('option');
        keep.value = '';
        keep.textContent = 'Keep current status';
        toEl.appendChild(keep);
        options.forEach(function (s) {
            const opt = document.createElement('option');
            opt.value = s;
            opt.textContent = s;
            toEl.appendChild(opt);
        });
        toEl.value = '';
        if (statusWrap) statusWrap.style.display = options.length ? '' : 'none';

        const parent = modalEl || document.getElementById('llEditLeadModal') || document.body;
        if ($ && $.fn.select2) {
            $to.select2({
                width: '100%',
                dropdownParent: $(parent),
                minimumResultsForSearch: Infinity,
                placeholder: 'Keep current status',
                allowClear: false
            });
            $to.val('').trigger('change');
        }
        syncLlEditStatusFields();
    }

    document.addEventListener('click', function (e) {
        const editBtn = e.target.closest('.btn-ll-edit');
        if (editBtn) {
            e.preventDefault();
            const form = document.getElementById('llEditLeadForm');
            const modal = document.getElementById('llEditLeadModal');
            if (!form || !modal || typeof bootstrap === 'undefined') return;
            form.action = editBtn.getAttribute('data-update-url') || '';
            document.getElementById('llEditLeadSub').textContent =
                'Update details and status for ' + (editBtn.getAttribute('data-ref') || 'this lead') + '.';

            setVal('ll_edit_customer_name', editBtn.getAttribute('data-customer'));
            setVal('ll_edit_mobile', editBtn.getAttribute('data-mobile'));
            setVal('ll_edit_lead_type', editBtn.getAttribute('data-lead-type') || 'immediate');
            setVal('ll_edit_product', editBtn.getAttribute('data-product'));
            setVal('ll_edit_loan_amount', editBtn.getAttribute('data-loan-amount'));
            setVal('ll_edit_source', editBtn.getAttribute('data-source') || @json($defaultSourceName));
            setVal('ll_edit_branch', editBtn.getAttribute('data-branch-id'));
            const branchLabel = document.getElementById('ll_edit_branch_label');
            if (branchLabel) {
                branchLabel.value = editBtn.getAttribute('data-branch-name')
                    || editBtn.getAttribute('data-branch-id')
                    || 'Your branch';
            }
            const disposition = editBtn.getAttribute('data-disposition') || '';
            setVal('ll_edit_disposition', disposition);
            const dispositionLabel = document.getElementById('ll_edit_disposition_label');
            if (dispositionLabel) dispositionLabel.value = disposition || '-';
            setVal('ll_edit_notes', '');
            setVal('ll_edit_follow_up', editBtn.getAttribute('data-follow-up'));
            setVal('ll_edit_closure', '');
            const consent = document.getElementById('ll_edit_consent');
            if (consent) consent.checked = editBtn.getAttribute('data-consent') === '1';

            const current = editBtn.getAttribute('data-current-status') || '';
            const currentWrap = document.getElementById('ll_edit_current_wrap');
            if (currentWrap) {
                currentWrap.innerHTML = '<span class="status-pill ' + statusTone(current) + '">' + escapeHtml(current || '-') + '</span>';
            }
            const leadId = String(editBtn.getAttribute('data-lead-id') || '');
            const next = llLeadNextById[leadId] || llLeadNextById[Number(leadId)] || [];

            const canEdit = editBtn.getAttribute('data-can-edit') === '1';
            ['ll_edit_customer_name', 'll_edit_mobile', 'll_edit_loan_amount', 'll_edit_notes'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) el.readOnly = !canEdit;
            });
            const productEl = document.getElementById('ll_edit_product');
            if (productEl) productEl.disabled = false;

            const onShown = function () {
                modal.removeEventListener('shown.bs.modal', onShown);
                rebuildMoveToOptions(next, modal);
                if (window.IsarvaSelect2 && typeof window.IsarvaSelect2.initOne === 'function' && productEl) {
                    if (window.jQuery && window.jQuery(productEl).hasClass('select2-hidden-accessible')) {
                        window.jQuery(productEl).select2('destroy');
                    }
                    productEl.classList.remove('no-select2');
                    productEl.removeAttribute('data-select2-skip');
                    window.IsarvaSelect2.initOne(productEl);
                    if (window.jQuery) {
                        window.jQuery(productEl).val(editBtn.getAttribute('data-product') || null).trigger('change');
                    }
                }
            };
            modal.addEventListener('shown.bs.modal', onShown);
            bootstrap.Modal.getOrCreateInstance(modal).show();
            return;
        }

        const noteBtn = e.target.closest('.btn-ll-note');
        if (noteBtn) {
            e.preventDefault();
            const form = document.getElementById('llNoteLeadForm');
            const modal = document.getElementById('llNoteLeadModal');
            if (!form || !modal || typeof bootstrap === 'undefined') return;
            form.action = noteBtn.getAttribute('data-note-url') || '';
            document.getElementById('llNoteLeadSub').textContent =
                'Add a note on ' + (noteBtn.getAttribute('data-ref') || 'this lead') + '. Saved in activity log.';
            setVal('ll_note_body', '');
            bootstrap.Modal.getOrCreateInstance(modal).show();
        }
    });

    document.getElementById('ll_edit_to_status')?.addEventListener('change', syncLlEditStatusFields);
    if (window.jQuery) {
        window.jQuery(document).on('change', '#ll_edit_to_status', syncLlEditStatusFields);
    }
})();
</script>
@endpush
