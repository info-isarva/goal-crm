@extends('layouts.app')

@push('styles')
@include('goal.partials.pd_hub_styles')
<style>
.pms-page{--pms:#009B80;--pms-dark:#007F68;background:#F5F8FC;min-height:100%}
.pms-page .pd-title{font-size:1.5rem}
.pms-page .pd-hero{margin-bottom:1rem}
.pms-page .pd-hero-actions .pd-link-btn{border-radius:10px;height:40px;padding:0 16px}
.pms-page #pmsResults{transition:opacity .15s ease}
.pms-page #pmsResults.is-loading{opacity:.6;pointer-events:none}

/* KPI cards — icon left, text right, corner wave */
.pms-page .pms-kpi-row{
    display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:1rem
}
.pms-page a.pms-kpi{
    --kpi-accent:#2563eb;
    --kpi-soft:#dbeafe;
    position:relative;overflow:hidden;isolation:isolate;
    text-decoration:none;color:inherit;background:#fff;border:1px solid #e8edf5;border-radius:16px;
    padding:1.05rem 1.15rem;min-height:92px;
    display:flex;align-items:center;gap:14px;
    box-shadow:0 2px 4px rgba(15,23,42,.04),0 10px 24px rgba(148,163,184,.12);
    transition:transform .15s ease,box-shadow .15s ease
}
.pms-page a.pms-kpi::after{
    content:'';position:absolute;right:-18px;bottom:-28px;width:110px;height:110px;border-radius:50%;
    background:radial-gradient(circle at 30% 30%, color-mix(in srgb, var(--kpi-accent) 28%, transparent), transparent 68%);
    opacity:.55;pointer-events:none;z-index:0
}
.pms-page a.pms-kpi::before{
    content:'';position:absolute;right:8px;bottom:-6px;width:78px;height:42px;border-radius:50% 50% 40% 60%;
    background:color-mix(in srgb, var(--kpi-accent) 14%, transparent);
    transform:rotate(-12deg);pointer-events:none;z-index:0
}
.pms-page a.pms-kpi:hover{
    transform:translateY(-2px);
    box-shadow:0 4px 8px rgba(15,23,42,.05),0 14px 28px rgba(148,163,184,.16)
}
.pms-page a.pms-kpi.is-active{outline:2px solid var(--pms);outline-offset:1px}
.pms-page a.pms-kpi.tone-blue{--kpi-accent:#3b82f6;--kpi-soft:#dbeafe}
.pms-page a.pms-kpi.tone-green{--kpi-accent:#22c55e;--kpi-soft:#dcfce7}
.pms-page a.pms-kpi.tone-amber{--kpi-accent:#f97316;--kpi-soft:#ffedd5}
.pms-page a.pms-kpi.tone-red{--kpi-accent:#ef4444;--kpi-soft:#fee2e2}
.pms-page .pms-kpi-ico{
    position:relative;z-index:1;
    width:46px;height:46px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;
    font-size:1.2rem;flex:0 0 auto;background:var(--kpi-soft);color:var(--kpi-accent)
}
.pms-page .pms-kpi-body{position:relative;z-index:1;min-width:0;flex:1 1 auto}
.pms-page .pms-kpi-label{
    font-size:.82rem;font-weight:700;color:#1e293b;margin:0;line-height:1.25;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis
}
.pms-page .pms-kpi-label .short{display:none}
.pms-page .pms-kpi-value{
    font-size:1.65rem;font-weight:800;color:#0f172a;margin:.2rem 0 0;line-height:1;letter-spacing:-.03em
}
.pms-page a.pms-kpi.tone-red .pms-kpi-value{color:#dc2626}

/* Tabs — individual pill buttons with icons */
.pms-page .pms-tabs{
    display:flex;flex-wrap:wrap;gap:8px;margin-bottom:0;padding:0;
    background:transparent;border:0;box-shadow:none
}
.pms-page .pms-tab{
    display:inline-flex;align-items:center;gap:7px;padding:.55rem 1rem;border-radius:999px;
    font-size:.78rem;font-weight:700;color:#334155;text-decoration:none;white-space:nowrap;
    background:#fff;border:1px solid #e2e8f0;
    box-shadow:0 1px 2px rgba(15,23,42,.04),0 4px 10px rgba(148,163,184,.08)
}
.pms-page .pms-tab i{font-size:.9rem;color:#64748b}
.pms-page .pms-tab:hover{background:#f8fafc;color:#0f172a;border-color:#cbd5e1}
.pms-page .pms-tab.active{
    background:linear-gradient(180deg,#00a88e 0%,#009B80 100%);
    border-color:transparent;color:#fff;
    box-shadow:0 6px 14px rgba(0,155,128,.28)
}
.pms-page .pms-tab.active i{color:#fff}
.pms-page .pms-tab .cnt{font-weight:800;opacity:.92}
.pms-page .pms-tab .full{display:inline}
.pms-page .pms-tab .short{display:none}
.pms-page .pms-tab.tab-pending i{color:#3b82f6}
.pms-page .pms-tab.tab-verified i{color:#16a34a}
.pms-page .pms-tab.tab-due i{color:#2563eb}
.pms-page .pms-tab.tab-overdue i{color:#dc2626}
.pms-page .pms-tab.active.tab-pending i,
.pms-page .pms-tab.active.tab-verified i,
.pms-page .pms-tab.active.tab-due i,
.pms-page .pms-tab.active.tab-overdue i{color:#fff}

/* Collapsible filters (mobile) */
.pms-page .pms-filters-toggle{
    display:none;width:100%;align-items:center;justify-content:space-between;gap:10px;
    border:0;background:transparent;padding:0;margin:0 0 .65rem;cursor:pointer;text-align:left
}
.pms-page .pms-filters-toggle .ttl{
    display:inline-flex;align-items:center;gap:8px;font-size:.84rem;font-weight:800;color:#0f172a
}
.pms-page .pms-filters-toggle .ttl i{
    width:28px;height:28px;border-radius:8px;background:#ecfdf5;color:#059669;
    display:inline-flex;align-items:center;justify-content:center;font-size:.85rem
}
.pms-page .pms-filters-toggle .meta{font-size:.72rem;font-weight:700;color:#64748b}
.pms-page .pms-filters-toggle .chev{color:#64748b;transition:transform .2s ease}
.pms-page .pms-filters-body{display:block}

/* Filters card — compact fields */
.pms-page .pms-filters{
    background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:.85rem 1rem;
    margin-bottom:0;box-shadow:0 2px 4px rgba(15,23,42,.04),0 8px 20px rgba(148,163,184,.1);
    overflow:hidden
}
.pms-page .pms-filters-row{
    display:grid;gap:10px 12px;align-items:end
}
.pms-page .pms-filters-row.top{
    grid-template-columns:1.55fr .9fr .95fr 1.15fr;margin-bottom:10px
}
.pms-page .pms-filters-row.bottom{
    grid-template-columns:minmax(0,1fr) minmax(0,1fr) minmax(0,1.15fr) auto
}
.pms-page .pms-filters-row .pms-field{min-width:0}
.pms-page .pms-filters-row label{
    display:block;font-size:.68rem;font-weight:700;color:#64748b;margin:0 0 4px
}
.pms-page .pms-filters-row .form-control,
.pms-page .pms-filters-row .form-select{
    min-height:34px;height:34px;border-radius:8px;border:1px solid #cbd5e1;
    font-size:.8rem;color:#0f172a;background:#fff;width:100%;max-width:100%;
    padding:.25rem .65rem;box-shadow:none
}
.pms-page .pms-filters-row .form-control:focus,
.pms-page .pms-filters-row .form-select:focus{
    border-color:#009B80;box-shadow:0 0 0 2px rgba(0,155,128,.12)
}
.pms-page .pms-filters-row .form-control::placeholder{color:#94a3b8}
.pms-page .pms-search{position:relative}
.pms-page .pms-search > i{
    position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.8rem;pointer-events:none;z-index:1
}
.pms-page .pms-search .form-control{padding-left:1.9rem}
.pms-page .pms-date-wrap{position:relative;min-width:0}
.pms-page .pms-date-wrap > i{
    position:absolute;left:10px;top:50%;transform:translateY(-50%);
    color:#64748b;font-size:.8rem;z-index:2;pointer-events:none
}
.pms-page .pms-date-wrap .form-control,
.pms-page .pms-date-wrap .flatpickr-input{
    min-height:34px;height:34px;border-radius:8px;border:1px solid #cbd5e1;
    font-size:.8rem;padding:.25rem .65rem .25rem 1.9rem;background:#fff;width:100%;cursor:pointer
}
.pms-page .pms-date-wrap .flatpickr-wrapper{display:block;width:100%}
.pms-page .pms-date-wrap .form-control:focus,
.pms-page .pms-date-wrap .flatpickr-input:focus{
    border-color:#009B80;box-shadow:0 0 0 2px rgba(0,155,128,.12)
}
.pms-page .pms-filter-actions{
    display:flex;gap:6px;align-items:end;justify-content:flex-end;padding-bottom:0;flex-wrap:nowrap
}
.pms-page .pms-filter-actions .pd-btn,
.pms-page .pms-filter-actions .pd-btn-ghost{
    height:34px;border-radius:8px;padding:0 12px;font-size:.75rem;white-space:nowrap;flex:0 0 auto
}
.pms-page .pms-filter-actions .pd-btn{
    background:linear-gradient(180deg,#00a88e 0%,#009B80 100%);
    box-shadow:0 6px 12px rgba(0,155,128,.22)
}
.pms-page .pms-filter-actions .pd-btn-ghost{border:1.5px solid #009B80;color:#009B80;background:#fff}
.pms-page .pms-filter-actions .pd-btn-ghost:hover{background:#ecfdf5;color:#007F68}

/* Main layout: left = KPIs/tabs/filters/table, right = snapshot stack (aligned to top) */
.pms-page .pms-layout{
    display:grid;grid-template-columns:minmax(0,1fr) minmax(300px,360px);gap:14px;align-items:start
}
.pms-page .pms-main{display:flex;flex-direction:column;gap:12px;min-width:0}
.pms-page .pms-side{
    display:flex;flex-direction:column;gap:12px;
    position:sticky;top:76px;align-self:start;max-height:calc(100vh - 96px);overflow:auto
}
.pms-page .pms-main .pms-kpi-row{margin-bottom:0;grid-template-columns:repeat(4,minmax(0,1fr))}
.pms-page .pms-main .pms-kpi{padding:.85rem .9rem;min-height:84px;gap:10px}
.pms-page .pms-main .pms-kpi-ico{width:40px;height:40px;font-size:1.05rem;border-radius:10px}
.pms-page .pms-main .pms-kpi-label{font-size:.72rem}
.pms-page .pms-main .pms-kpi-value{font-size:1.4rem}
.pms-page .pms-panel{
    background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;
    box-shadow:0 2px 4px rgba(15,23,42,.04),0 8px 20px rgba(148,163,184,.1)
}
.pms-page .pms-panel-h{
    padding:.55rem .75rem;border-bottom:1px solid #f1f5f9;background:#fff;
    display:flex;align-items:center;justify-content:space-between;gap:8px
}
.pms-page .pms-panel-title{
    display:inline-flex;align-items:center;gap:7px;font-size:.82rem;font-weight:800;color:#0f172a;margin:0
}
.pms-page .pms-panel-title .pms-hico{
    width:24px;height:24px;border-radius:7px;display:inline-flex;align-items:center;justify-content:center;
    background:#d1fae5;color:#059669;font-size:.78rem;flex:0 0 auto
}
.pms-page .pms-panel-link{
    font-size:.72rem;font-weight:700;color:#2563eb;text-decoration:none;white-space:nowrap
}
.pms-page .pms-panel-link:hover{text-decoration:underline;color:#1d4ed8}
.pms-page .pms-panel-b{padding:.55rem .75rem .65rem}

/* Snapshot rows: compact label left / value right */
.pms-page .pms-snap-rows{display:flex;flex-direction:column;gap:0;margin:0}
.pms-page .pms-snap-row{
    display:grid;grid-template-columns:1fr auto;gap:8px;align-items:center;
    padding:.28rem 0;border-bottom:1px solid #f1f5f9
}
.pms-page .pms-snap-row:last-child{border-bottom:0;padding-bottom:0}
.pms-page .pms-snap-row:first-child{padding-top:0}
.pms-page .pms-snap-k{font-size:.72rem;color:#64748b;font-weight:600;margin:0;line-height:1.25}
.pms-page .pms-snap-v{
    font-size:.78rem;font-weight:750;color:#0f172a;margin:0;text-align:right;line-height:1.25;
    word-break:break-word;max-width:170px
}
.pms-page .pms-snap-v.mono{
    font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;color:#0f172a;font-weight:800
}
.pms-page .pms-snap-v a{color:#2563eb;font-weight:750;text-decoration:none}
.pms-page .pms-snap-v a:hover{text-decoration:underline}
.pms-page .pms-view-case{
    margin-top:.55rem;height:30px;padding:0 10px;border-radius:7px;border:1.5px solid #3b82f6;
    background:#fff;color:#2563eb;font-size:.74rem;font-weight:700;text-decoration:none;
    display:inline-flex;align-items:center;gap:5px
}
.pms-page .pms-view-case:hover{background:#eff6ff;color:#1d4ed8}

.pms-page .pms-fu-table{width:100%;border-collapse:collapse}
.pms-page .pms-fu-table th{
    font-size:.62rem;text-transform:uppercase;letter-spacing:.03em;color:#94a3b8;font-weight:700;
    padding:.3rem .2rem .45rem;border-bottom:1px solid #eef2f7;text-align:left;white-space:nowrap
}
.pms-page .pms-fu-table td{
    font-size:.72rem;padding:.55rem .2rem;border-bottom:1px solid #f8fafc;vertical-align:middle;color:#334155
}
.pms-page .pms-fu-table tr:last-child td{border-bottom:0}
.pms-page .pms-fu-table .task{font-weight:650;color:#0f172a;max-width:110px}

/* Workflow timeline with checkmarks */
.pms-page .pms-wf{list-style:none;margin:0;padding:0}
.pms-page .pms-wf li{
    position:relative;display:grid;grid-template-columns:22px 1fr auto;gap:8px;align-items:flex-start;
    padding:0 0 1rem 0
}
.pms-page .pms-wf li:last-child{padding-bottom:0}
.pms-page .pms-wf li:not(:last-child)::after{
    content:'';position:absolute;left:10px;top:20px;bottom:0;width:2px;background:#e2e8f0
}
.pms-page .pms-wf-dot{
    width:20px;height:20px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;
    font-size:.65rem;flex:0 0 auto;position:relative;z-index:1;background:#fff;border:2px solid #cbd5e1;color:#fff
}
.pms-page .pms-wf li.done .pms-wf-dot{background:#10b981;border-color:#10b981}
.pms-page .pms-wf li.hold .pms-wf-dot{background:#fff;border-color:#f97316;color:#f97316}
.pms-page .pms-wf li.pending .pms-wf-dot{background:#fff;border-color:#cbd5e1}
.pms-page .pms-wf-main{min-width:0}
.pms-page .pms-wf-title{font-size:.78rem;font-weight:750;color:#0f172a;margin:0;line-height:1.25}
.pms-page .pms-wf-detail{font-size:.7rem;color:#64748b;margin:.15rem 0 0}
.pms-page .pms-wf-meta{text-align:right;white-space:nowrap}
.pms-page .pms-wf-time{font-size:.68rem;color:#94a3b8;display:block}
.pms-page .pms-wf-badge{
    display:inline-flex;margin-top:4px;padding:.12rem .45rem;border-radius:999px;
    font-size:.62rem;font-weight:750;background:#ffedd5;color:#c2410c
}
.pms-page .pms-wf-badge.ok{background:#dcfce7;color:#15803d}
.pms-page .pms-wf-badge.info{background:#e0f2fe;color:#0369a1}

/* Activity feed */
.pms-page .pms-act{list-style:none;margin:0;padding:0}
.pms-page .pms-act li{
    position:relative;padding:0 0 .85rem 14px;border-left:2px solid #d1fae5;font-size:.75rem
}
.pms-page .pms-act li:last-child{padding-bottom:0;border-left-color:transparent}
.pms-page .pms-act li::before{
    content:'';position:absolute;left:-5px;top:4px;width:8px;height:8px;border-radius:50%;background:#10b981
}
.pms-page .pms-act .t{font-weight:650;color:#0f172a;line-height:1.35}
.pms-page .pms-act .m{color:#94a3b8;margin-top:3px;font-size:.7rem}

.pms-page .pms-row-active td{background:#ecfdf5 !important}
.pms-page .pms-amt{font-weight:750;color:#0f172a;white-space:nowrap}
.pms-page .pd-card{border-radius:14px;border-color:#e2e8f0;box-shadow:0 1px 2px rgba(15,23,42,.04),0 8px 20px rgba(148,163,184,.1)}
.pms-page .pd-muted{color:#94a3b8;font-size:.78rem}

/* Queue table — compact mockup layout */
.pms-page .pms-qcard .pd-card-head{
    padding:.9rem 1.1rem;align-items:center;gap:12px;border-bottom:1px solid #eef2f7;flex-wrap:nowrap
}
.pms-page .pms-qhead{display:flex;align-items:center;gap:12px;min-width:0;flex:1 1 auto}
.pms-page .pms-qico{
    width:38px;height:38px;border-radius:10px;background:#d1fae5;color:#059669;
    display:inline-flex;align-items:center;justify-content:center;font-size:1.05rem;flex:0 0 auto
}
.pms-page .pms-qtitle{margin:0;font-size:1rem;font-weight:800;color:#0f172a;line-height:1.2}
.pms-page .pms-qsub{margin:.18rem 0 0;font-size:.72rem;color:#64748b;line-height:1.3}
.pms-page .pms-qbadge{
    display:inline-flex;align-items:center;padding:.32rem .75rem;border-radius:999px;
    font-size:.72rem;font-weight:700;background:#ecfdf5;color:#047857;white-space:nowrap;
    border:1px solid #a7f3d0;flex:0 0 auto;margin-left:auto
}
.pms-page .pms-qtable{min-width:1020px;width:100%}
.pms-page .pms-qtable thead th{
    font-size:.66rem;font-weight:750;letter-spacing:.02em;text-transform:none;
    padding:.62rem .65rem;background:#f0f7ff;color:#1e3a5f;
    white-space:nowrap;border-bottom:1px solid #dbeafe
}
.pms-page .pms-qtable tbody td{
    padding:.48rem .65rem;font-size:.78rem;vertical-align:middle;white-space:nowrap;
    color:#334155;border-bottom:1px solid #f1f5f9;line-height:1.3
}
.pms-page .pms-qtable tbody tr:hover td{background:#f8fafc}
.pms-page .pms-qtable .pd-case{font-size:.78rem;font-weight:700;color:#009B80;text-decoration:none}
.pms-page .pms-qtable .pd-case:hover{color:#007F68;text-decoration:underline}
.pms-page .pms-qtable .pd-cust{font-size:.8rem;font-weight:700;color:#0f172a}
.pms-page .pms-qtable .pd-pill{font-size:.66rem;padding:.2rem .55rem;white-space:nowrap;border-radius:999px;font-weight:700}
.pms-page .pms-qtable thead th:last-child,
.pms-page .pms-qtable tbody td.pd-col-actions{
    position:sticky;right:0;z-index:2;width:1%;white-space:nowrap;
    box-shadow:-6px 0 10px -8px rgba(15,23,42,.18)
}
.pms-page .pms-qtable thead th:last-child{background:#f0f7ff;z-index:3}
.pms-page .pms-qtable tbody td.pd-col-actions{background:#fff}
.pms-page .pms-qtable tbody tr:hover td.pd-col-actions{background:#f8fafc}
.pms-page .pms-qtable tbody tr.pms-row-active td.pd-col-actions{background:#ecfdf5}
.pms-page .pms-qacts{
    display:inline-flex !important;flex-direction:row !important;flex-wrap:nowrap !important;
    align-items:center;gap:6px;max-width:none
}
.pms-page .pms-qacts .pms-abtn{
    height:28px;padding:0 10px;border-radius:7px;font-size:.7rem;font-weight:700;
    display:inline-flex;align-items:center;justify-content:center;gap:4px;text-decoration:none;
    white-space:nowrap;border:1px solid #dbe3f0;background:#fff;color:#334155;line-height:1
}
.pms-page .pms-qacts .pms-abtn:hover{background:#f8fafc;color:#0f172a}
.pms-page .pms-qacts .pms-abtn.view{border-color:#93c5fd;color:#2563eb;background:#fff}
.pms-page .pms-qacts .pms-abtn.view:hover{background:#eff6ff;color:#1d4ed8}
.pms-page .pms-qacts .pms-abtn.verify{
    border:0;background:#009B80;color:#fff;box-shadow:0 3px 8px rgba(0,155,128,.22)
}
.pms-page .pms-qacts .pms-abtn.verify:hover{color:#fff;filter:brightness(1.05)}
.pms-page .pms-qacts .pms-abtn.more{
    width:28px;padding:0;color:#64748b;border-color:#e2e8f0
}
.pms-page .pms-qacts .pms-abtn.more::after{display:none}
.pms-page .pms-qfoot{
    display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;
    padding:.75rem 1.1rem;border-top:1px solid #eef2f7;font-size:.78rem;color:#64748b
}
.pms-page .pms-qfoot .pagination{margin:0;gap:4px}
.pms-page .pms-qfoot .page-link{
    min-width:32px;height:32px;padding:0 .55rem;display:inline-flex;align-items:center;justify-content:center;
    border-radius:8px;font-size:.78rem;font-weight:650;color:#334155;border:1px solid #e2e8f0;background:#fff
}
.pms-page .pms-qfoot .page-item.active .page-link{
    background:#009B80;border-color:#009B80;color:#fff;border-radius:999px
}
.pms-page .pms-qfoot .page-item.disabled .page-link{opacity:.45}
@media (max-width:1399.98px){
    .pms-page .pms-filters-grid{grid-template-columns:1.2fr 1fr 1fr 1fr}
}
@media (max-width:1199.98px){
    .pms-page .pms-layout{grid-template-columns:1fr}
    .pms-page .pms-side{
        position:static;max-height:none;overflow:visible;
        display:grid;grid-template-columns:1fr 1fr;gap:12px
    }
    .pms-page .pms-filters-row.top{grid-template-columns:1fr 1fr}
    .pms-page .pms-filters-row.bottom{grid-template-columns:1fr 1fr auto}
    .pms-page .pms-field-spacer{display:none}
    .pms-page .pms-main .pms-kpi-row{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media (max-width:991.98px){
    .pms-page.container-fluid{padding:1rem !important}
    .pms-page .pd-hero{
        display:flex;flex-direction:column;align-items:stretch;gap:10px;margin-bottom:.75rem
    }
    .pms-page .pd-hero-actions{width:100%;display:grid;grid-template-columns:1fr 1fr;gap:8px}
    .pms-page .pd-hero-actions .dropdown{min-width:0}
    .pms-page .pd-hero-actions .pd-link-btn{
        justify-content:center;width:100%;height:38px;padding:0 10px;font-size:.78rem
    }
    .pms-page .pd-title{font-size:1.28rem}
    .pms-page .pd-sub{margin-top:.2rem}
    .pms-page .pms-kpi-row,
    .pms-page .pms-main .pms-kpi-row{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
    .pms-page a.pms-kpi{padding:.8rem .85rem;min-height:76px;gap:10px;border-radius:14px}
    .pms-page .pms-kpi-ico{width:38px;height:38px;font-size:1.05rem;border-radius:10px}
    .pms-page .pms-kpi-label{font-size:.74rem;white-space:normal}
    .pms-page .pms-kpi-label .full{display:none}
    .pms-page .pms-kpi-label .short{display:inline}
    .pms-page .pms-kpi-value{font-size:1.35rem}
    .pms-page .pms-tabs{
        flex-wrap:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch;
        gap:6px;padding:2px 2px 8px;margin:0 -2px 2px;scrollbar-width:none
    }
    .pms-page .pms-tabs::-webkit-scrollbar{display:none}
    .pms-page .pms-tab{padding:.45rem .8rem;font-size:.72rem;flex:0 0 auto}
    .pms-page .pms-tab .full{display:none}
    .pms-page .pms-tab .short{display:inline}
}
@media (max-width:767.98px){
    .pms-page.container-fluid{padding:.75rem !important}
    .pms-page .pms-side{grid-template-columns:1fr;gap:10px}
    .pms-page .pms-main{gap:10px}
    .pms-page .pms-filters{padding:.7rem .8rem}
    .pms-page .pms-filters-toggle{display:flex}
    .pms-page .pms-filters.is-collapsed .pms-filters-body{display:none}
    .pms-page .pms-filters.is-collapsed .pms-filters-toggle .chev{transform:rotate(-90deg)}
    .pms-page .pms-filters-row.top,
    .pms-page .pms-filters-row.bottom{grid-template-columns:1fr;margin-bottom:0;gap:8px}
    .pms-page .pms-filters-row.top{margin-bottom:8px}
    .pms-page .pms-filter-actions{justify-content:stretch;width:100%;margin-top:4px}
    .pms-page .pms-filter-actions .pd-btn,
    .pms-page .pms-filter-actions .pd-btn-ghost{flex:1}
    .pms-page .pms-qcard .pd-card-head{
        flex-wrap:wrap;padding:.7rem .8rem;gap:8px
    }
    .pms-page .pms-qbadge{margin-left:0}
    .pms-page .pms-qsub{display:none}
    .pms-page .pms-qtable tbody td{
        padding:.4rem 0 !important;font-size:.78rem
    }
    .pms-page .pms-qacts{
        flex-wrap:nowrap !important;width:100%;gap:6px;margin-top:2px
    }
    .pms-page .pms-qacts .pms-abtn{flex:1 1 auto;justify-content:center;height:34px}
    .pms-page .pms-qacts .pms-abtn.more{flex:0 0 36px}
    .pms-page .pms-qtable thead th:last-child,
    .pms-page .pms-qtable tbody td.pd-col-actions{
        position:static;box-shadow:none;background:transparent !important
    }
    .pms-page .pms-qfoot{
        flex-direction:column;align-items:stretch;padding:.65rem .8rem;gap:8px
    }
    .pms-page .pms-qfoot .pagination{justify-content:center}
    .pms-page .pms-panel-h{padding:.5rem .7rem}
    .pms-page .pms-panel-b{padding:.5rem .7rem .6rem}
    .pms-page .pms-snap-row{padding:.22rem 0}
    .pms-page .pms-view-case{width:100%;justify-content:center}
}
@media (max-width:479.98px){
    .pms-page .pms-kpi-row,
    .pms-page .pms-main .pms-kpi-row{grid-template-columns:1fr 1fr;gap:8px}
    .pms-page a.pms-kpi{padding:.7rem .65rem;min-height:70px;gap:8px}
    .pms-page .pms-kpi-ico{width:34px;height:34px;font-size:.95rem}
    .pms-page .pms-kpi-label{font-size:.7rem}
    .pms-page .pms-kpi-value{font-size:1.2rem}
    .pms-page .pd-sub{font-size:.78rem}
}
</style>
@endpush

@section('content')
@php
    $qs = fn (array $extra = []) => array_filter(array_merge(request()->except(['page', 'partial']), $extra), fn ($v) => $v !== null && $v !== '');
@endphp
<div class="container-fluid p-4 pd-page pms-page" data-pms-index-url="{{ route('goal.pms.index') }}" data-pms-export-url="{{ route('goal.pms.export') }}">
    <div class="pd-hero">
        <div>
            <ul class="pd-crumbs">
                <li><a href="{{ url('/') }}">Home</a></li>
                <li class="sep">›</li>
                <li>Post-Disbursement</li>
                <li class="sep">›</li>
                <li>PMS Queue</li>
            </ul>
            <h1 class="pd-title">PMS Queue</h1>
            <p class="pd-sub">Verify disbursed cases and manage post-disbursement portfolio follow-ups.</p>
        </div>
        <div class="pd-hero-actions">
            <div class="dropdown">
                <button class="pd-link-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download"></i> Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item pms-export-link" data-format="csv" href="{{ route('goal.pms.export', $qs(['format' => 'csv'])) }}">CSV</a></li>
                    <li><a class="dropdown-item pms-export-link" data-format="xlsx" href="{{ route('goal.pms.export', $qs(['format' => 'xlsx'])) }}">Excel</a></li>
                    <li><a class="dropdown-item pms-export-link" data-format="pdf" href="{{ route('goal.pms.export', $qs(['format' => 'pdf'])) }}">PDF</a></li>
                </ul>
            </div>
            <button type="button" class="pd-link-btn" data-bs-toggle="modal" data-bs-target="#pmsGuideModal">
                <i class="bi bi-info-circle"></i> Queue Guide
            </button>
        </div>
    </div>

    @if(!empty($missingOrgScope))
        <div class="alert alert-warning py-2 d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
            <div>
                <strong>Organization unit not assigned.</strong>
                Your PMS Queue is empty because no branch/region is linked to your user.
                Ask an admin to set <em>Goal Org Unit</em> on your profile.
            </div>
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    <div id="pmsResults" class="pms-results" aria-live="polite">
        @include('goal.pms.partials.queue_results')
    </div>
</div>

<div class="modal fade" id="pmsGuideModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px">
            <div class="modal-header">
                <h5 class="modal-title">PMS Queue Guide</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size:.9rem;color:#334155;line-height:1.5">
                <p class="mb-2" style="font-size:.72rem;font-weight:800;color:#0f766e;text-transform:uppercase;letter-spacing:.04em">Post-Disbursement Process Flow</p>
                <p class="mb-2"><strong>PD-01</strong> verifies disbursement → PMS Queue + Accounts Queue open in parallel → Payout placed On Hold.</p>
                <p class="mb-2" style="color:#64748b">This is the PMS worklist for verification and portfolio follow-ups. Payout release is handled separately by Accounts.</p>
                <ol class="mb-0 ps-3">
                    <li>Start or complete PMS verification on assigned cases.</li>
                    <li>Schedule portfolio follow-ups (EMI confirmation, welcome, etc.).</li>
                    <li>Accounts captures report numbers separately — PMS cannot release payouts.</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const page = document.querySelector('.pms-page');
    const results = document.getElementById('pmsResults');
    if (!page || !results) return;

    const indexUrl = page.getAttribute('data-pms-index-url') || '/goal/pms';
    const exportUrl = page.getAttribute('data-pms-export-url') || '/goal/pms/export';
    let loading = false;
    let fpInstance = null;
    let abortCtrl = null;

    function destroyWidgets(root) {
        if (window.jQuery) {
            window.jQuery(root).find('select.select2-hidden-accessible').each(function () {
                try { window.jQuery(this).select2('destroy'); } catch (e) {}
            });
        }
        if (fpInstance) {
            try { fpInstance.destroy(); } catch (e) {}
            fpInstance = null;
        }
    }

    function initDateRange() {
        const rangeEl = document.getElementById('pmsDateRange');
        const fromEl = document.getElementById('pmsDateFrom');
        const toEl = document.getElementById('pmsDateTo');
        if (!rangeEl || !window.flatpickr) return;

        const defaults = [];
        if (fromEl && fromEl.value) defaults.push(fromEl.value);
        if (toEl && toEl.value) defaults.push(toEl.value);

        fpInstance = flatpickr(rangeEl, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd M Y',
            allowInput: false,
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

    function initFilterToggle() {
        const filters = document.getElementById('pmsFilters');
        const toggle = document.getElementById('pmsFiltersToggle');
        if (!filters || !toggle) return;
        toggle.addEventListener('click', function () {
            const collapsed = filters.classList.toggle('is-collapsed');
            toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        });
    }

    function initSelect2(root) {
        if (window.IsarvaSelect2 && typeof window.IsarvaSelect2.init === 'function') {
            window.IsarvaSelect2.init(root || results);
        }
    }

    function bindResults() {
        initFilterToggle();
        initDateRange();
        initSelect2(results);
        syncExportLinks(currentParams());
    }

    function setLoading(on) {
        loading = !!on;
        results.classList.toggle('is-loading', loading);
        const applyBtn = document.getElementById('pmsApplyBtn');
        if (applyBtn) applyBtn.disabled = loading;
    }

    function syncUrl(params) {
        try {
            const url = new URL(indexUrl, window.location.origin);
            params.forEach(function (value, key) {
                if (value === null || value === undefined || value === '') url.searchParams.delete(key);
                else url.searchParams.set(key, value);
            });
            url.searchParams.delete('partial');
            window.history.replaceState({}, '', url.pathname + url.search);
        } catch (e) {}
    }

    function syncExportLinks(params) {
        document.querySelectorAll('.pms-export-link').forEach(function (link) {
            const format = link.getAttribute('data-format') || 'csv';
            const qs = new URLSearchParams(params);
            qs.delete('partial');
            qs.delete('page');
            qs.delete('selected');
            qs.set('format', format);
            link.href = exportUrl + '?' + qs.toString();
        });
    }

    function currentParams() {
        const form = document.getElementById('pmsFilters');
        if (form) return new URLSearchParams(new FormData(form));
        return new URLSearchParams(window.location.search);
    }

    function loadQueue(params, pushUrl) {
        if (loading) return Promise.resolve();
        setLoading(true);
        if (abortCtrl) {
            try { abortCtrl.abort(); } catch (e) {}
        }
        abortCtrl = new AbortController();

        const qs = new URLSearchParams(params);
        qs.set('partial', '1');

        return fetch(indexUrl + '?' + qs.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            },
            credentials: 'same-origin',
            signal: abortCtrl.signal
        }).then(function (res) {
            if (!res.ok) throw new Error('Failed to load PMS queue');
            return res.text();
        }).then(function (html) {
            destroyWidgets(results);
            results.innerHTML = html;
            bindResults();
            if (pushUrl !== false) {
                const clean = new URLSearchParams(params);
                clean.delete('partial');
                syncUrl(clean);
            }
            syncExportLinks(params);
        }).catch(function (err) {
            if (err && err.name === 'AbortError') return;
            results.innerHTML = '<div class="alert alert-warning mb-0">Unable to refresh the queue. Please try again.</div>';
        }).finally(function () {
            setLoading(false);
        });
    }

    function paramsFromUrl(href) {
        try {
            const url = new URL(href, window.location.origin);
            return url.searchParams;
        } catch (e) {
            return new URLSearchParams();
        }
    }

    results.addEventListener('click', function (e) {
        const nav = e.target.closest('[data-pms-nav]');
        if (nav && results.contains(nav)) {
            e.preventDefault();
            const params = paramsFromUrl(nav.getAttribute('href'));
            const form = document.getElementById('pmsFilters');
            if (form && nav.hasAttribute('data-tab')) {
                const tabInput = document.getElementById('pmsTabInput');
                if (tabInput) tabInput.value = nav.getAttribute('data-tab') || 'all';
                const merged = new URLSearchParams(new FormData(form));
                merged.set('tab', nav.getAttribute('data-tab') || 'all');
                merged.delete('page');
                merged.delete('selected');
                loadQueue(merged);
                return;
            }
            loadQueue(params);
            return;
        }

        const pageLink = e.target.closest('.pms-pager a, .pagination a');
        if (pageLink && results.contains(pageLink)) {
            e.preventDefault();
            loadQueue(paramsFromUrl(pageLink.getAttribute('href')));
            return;
        }

        const resetBtn = e.target.closest('#pmsResetBtn');
        if (resetBtn && results.contains(resetBtn)) {
            e.preventDefault();
            const tab = (document.getElementById('pmsTabInput') || {}).value || 'all';
            loadQueue(new URLSearchParams({ tab: tab }));
        }
    });

    results.addEventListener('submit', function (e) {
        const form = e.target.closest('#pmsFilters');
        if (!form || !results.contains(form)) return;
        e.preventDefault();
        const params = new URLSearchParams(new FormData(form));
        params.delete('page');
        params.delete('selected');
        loadQueue(params);
    });

    bindResults();
})();
</script>
@endpush
