@extends('layouts.app')

@push('styles')
@include('goal.partials.pd_hub_styles')
<style>
.acc-page{--acc:#009B80;--acc-dark:#007F68;background:#F5F8FC;min-height:100%}
.acc-page .pd-hero{margin-bottom:1rem;align-items:center}
.acc-page .pd-hero-brand{display:flex;align-items:flex-start;gap:14px;min-width:0}
.acc-page .acc-hero-ico{
    width:48px;height:48px;border-radius:14px;flex:0 0 auto;
    display:inline-flex;align-items:center;justify-content:center;
    background:linear-gradient(160deg,#10b981 0%,#059669 100%);color:#fff;
    box-shadow:0 8px 18px rgba(5,150,105,.28);font-size:1.25rem;margin-top:2px
}
.acc-page .pd-title{font-size:1.5rem;color:#0f172a}
.acc-page .pd-sub{max-width:42rem}
.acc-page .pd-hero-actions .pd-link-btn{border-radius:10px;height:40px;padding:0 16px}
.acc-page #accResults{transition:opacity .15s ease}
.acc-page #accResults.is-loading{opacity:.6;pointer-events:none}

/* Workflow stepper — coloured step cards */
.acc-page .acc-flow{
    display:flex;flex-wrap:nowrap;align-items:stretch;gap:8px;margin-bottom:.85rem;
    padding:0 0 2px;background:transparent;border:0;overflow-x:auto;-webkit-overflow-scrolling:touch;
    scrollbar-width:none
}
.acc-page .acc-flow::-webkit-scrollbar{display:none;height:0}
.acc-page .acc-step{
    flex:1 1 0;min-width:0;display:flex;align-items:center;gap:8px;
    padding:.65rem .7rem;background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;
    box-shadow:0 1px 2px rgba(15,23,42,.04),0 6px 14px rgba(148,163,184,.08)
}
.acc-page .acc-step-label .short{display:none}
.acc-page .acc-step-label .full{display:inline}
.acc-page .acc-step-ico{
    width:30px;height:30px;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;
    font-size:.85rem;flex:0 0 auto
}
.acc-page .acc-step-label{font-size:.7rem;font-weight:750;color:#0f172a;line-height:1.2;margin:0}
.acc-page .acc-step-sub{font-size:.6rem;font-weight:650;color:#64748b;margin:.1rem 0 0;line-height:1.15}
.acc-page .acc-flow-arr{
    display:inline-flex;align-items:center;color:#94a3b8;font-size:.8rem;flex:0 0 auto;align-self:center
}
.acc-page .acc-step.tone-green{border-color:#86efac;background:#f0fdf4}
.acc-page .acc-step.tone-green .acc-step-ico{background:#dcfce7;color:#15803d}
.acc-page .acc-step.tone-blue{border-color:#93c5fd;background:#eff6ff}
.acc-page .acc-step.tone-blue .acc-step-ico{background:#dbeafe;color:#1d4ed8}
.acc-page .acc-step.tone-teal{border-color:#5eead4;background:#f0fdfa}
.acc-page .acc-step.tone-teal .acc-step-ico{background:#ccfbf1;color:#0f766e}
.acc-page .acc-step.tone-emerald{border-color:#6ee7b7;background:#ecfdf5}
.acc-page .acc-step.tone-emerald .acc-step-ico{background:#d1fae5;color:#047857}
.acc-page .acc-step.tone-mint{border-color:#a7f3d0;background:#f0fdf4}
.acc-page .acc-step.tone-mint .acc-step-ico{background:#dcfce7;color:#059669}

.acc-page .acc-hold-banner{
    display:flex;align-items:flex-start;gap:10px;margin-bottom:1rem;
    padding:.7rem .95rem;border-radius:12px;border:1px solid #fed7aa;
    background:#fff7ed;color:#9a3412;font-size:.82rem;font-weight:650;line-height:1.4
}
.acc-page .acc-hold-banner i{color:#ea580c;font-size:1.05rem;margin-top:1px;flex:0 0 auto}

/* KPI cards */
.acc-page .acc-kpi-row{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:0}
.acc-page a.acc-kpi{
    --kpi-accent:#ef4444;--kpi-soft:#fee2e2;position:relative;overflow:hidden;isolation:isolate;
    text-decoration:none;color:inherit;background:#fff;border:1px solid #e8edf5;border-radius:16px;
    padding:.95rem 1.05rem;min-height:92px;display:flex;flex-direction:row;align-items:center;gap:12px;
    box-shadow:0 2px 4px rgba(15,23,42,.04),0 10px 24px rgba(148,163,184,.12);
    transition:transform .15s ease,box-shadow .15s ease;text-align:left
}
.acc-page a.acc-kpi::after{
    content:'';position:absolute;right:-18px;bottom:-28px;width:110px;height:110px;border-radius:50%;
    background:radial-gradient(circle at 30% 30%, color-mix(in srgb, var(--kpi-accent) 28%, transparent), transparent 68%);
    opacity:.55;pointer-events:none;z-index:0
}
.acc-page a.acc-kpi:hover{
    transform:translateY(-2px);
    box-shadow:0 4px 8px rgba(15,23,42,.05),0 14px 28px rgba(148,163,184,.16)
}
.acc-page a.acc-kpi.is-active{outline:2px solid var(--acc);outline-offset:1px}
.acc-page a.acc-kpi.tone-red{--kpi-accent:#ef4444;--kpi-soft:#fee2e2}
.acc-page a.acc-kpi.tone-blue{--kpi-accent:#3b82f6;--kpi-soft:#dbeafe}
.acc-page a.acc-kpi.tone-green{--kpi-accent:#22c55e;--kpi-soft:#dcfce7}
.acc-page a.acc-kpi.tone-purple{--kpi-accent:#8b5cf6;--kpi-soft:#ede9fe}
.acc-page .acc-kpi-ico{
    position:relative;z-index:1;width:44px;height:44px;border-radius:12px;
    display:inline-flex;align-items:center;justify-content:center;
    background:var(--kpi-soft);color:var(--kpi-accent);font-size:1.15rem;flex:0 0 auto
}
.acc-page .acc-kpi-body{position:relative;z-index:1;min-width:0;flex:1 1 auto}
.acc-page .acc-kpi-label{margin:0;font-size:.78rem;font-weight:700;color:#1e293b;line-height:1.25}
.acc-page .acc-kpi-label .short{display:none}
.acc-page .acc-kpi-label .full{display:inline}
.acc-page .acc-kpi-value{margin:.2rem 0 0;font-size:1.55rem;font-weight:800;color:#0f172a;line-height:1;letter-spacing:-.03em}
.acc-page a.acc-kpi.tone-red .acc-kpi-value{color:#dc2626}
.acc-page a.acc-kpi.tone-blue .acc-kpi-value{color:#2563eb}
.acc-page a.acc-kpi.tone-green .acc-kpi-value{color:#16a34a}
.acc-page a.acc-kpi.tone-purple .acc-kpi-value{color:#7c3aed}
.acc-page .acc-kpi-pct{margin:.25rem 0 0;font-size:.7rem;font-weight:700;color:#64748b}

/* Tabs */
.acc-page .acc-tabs{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:0}
.acc-page .acc-tab{
    display:inline-flex;align-items:center;gap:7px;padding:.55rem 1rem;border-radius:999px;
    font-size:.76rem;font-weight:700;color:#334155;text-decoration:none;white-space:nowrap;
    background:#fff;border:1px solid #e2e8f0;
    box-shadow:0 1px 2px rgba(15,23,42,.04),0 4px 10px rgba(148,163,184,.08)
}
.acc-page .acc-tab i{font-size:.9rem;color:#64748b}
.acc-page .acc-tab:hover{background:#f8fafc;color:#0f172a;border-color:#cbd5e1}
.acc-page .acc-tab.active{
    background:linear-gradient(180deg,#00a88e 0%,#009B80 100%);
    border-color:transparent;color:#fff;box-shadow:0 6px 14px rgba(0,155,128,.28)
}
.acc-page .acc-tab.active i{color:#fff}
.acc-page .acc-tab .cnt{font-weight:800;opacity:.92}
.acc-page .acc-tab .full{display:inline}
.acc-page .acc-tab .short{display:none}

/* Layout */
.acc-page .acc-layout{
    display:grid;grid-template-columns:minmax(0,1fr) minmax(300px,360px);gap:14px;align-items:start
}
.acc-page .acc-main{display:flex;flex-direction:column;gap:12px;min-width:0}
.acc-page .acc-side{
    display:flex;flex-direction:column;gap:8px;
    position:sticky;top:76px;align-self:start;
    overflow:visible
}

/* Filters */
.acc-page .acc-filters{
    background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:.85rem 1rem;
    box-shadow:0 2px 4px rgba(15,23,42,.04),0 8px 20px rgba(148,163,184,.1)
}
.acc-page .acc-filters-row{display:grid;gap:10px 12px;align-items:end}
.acc-page .acc-filters-row.top{grid-template-columns:1.4fr 1fr 1fr 1.15fr;margin-bottom:10px}
.acc-page .acc-filters-row.bottom{grid-template-columns:1fr 1fr auto}
.acc-page .acc-filters label{
    display:block;font-size:.68rem;font-weight:700;color:#64748b;margin:0 0 4px
}
.acc-page .acc-filters .form-control,
.acc-page .acc-filters .form-select{
    min-height:34px;height:34px;border-radius:8px;border:1px solid #cbd5e1;
    font-size:.8rem;color:#0f172a;background:#fff;width:100%;padding:.25rem .65rem;box-shadow:none
}
.acc-page .acc-filters .form-control:focus,
.acc-page .acc-filters .form-select:focus{
    border-color:#009B80;box-shadow:0 0 0 2px rgba(0,155,128,.12)
}
.acc-page .acc-search{position:relative}
.acc-page .acc-search > i{
    position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.8rem;pointer-events:none;z-index:1
}
.acc-page .acc-search .form-control{padding-left:1.9rem}
.acc-page .acc-date-wrap{position:relative}
.acc-page .acc-date-wrap > i{
    position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#64748b;font-size:.8rem;z-index:2;pointer-events:none
}
.acc-page .acc-date-wrap .form-control,
.acc-page .acc-date-wrap .flatpickr-input{
    padding-left:1.9rem;cursor:pointer
}
.acc-page .acc-date-wrap .flatpickr-wrapper{display:block;width:100%}
.acc-page .acc-filter-actions{display:flex;gap:6px;align-items:end;justify-content:flex-end}
.acc-page .acc-filter-actions .pd-btn,
.acc-page .acc-filter-actions .pd-btn-ghost{
    height:34px;border-radius:8px;padding:0 12px;font-size:.75rem;white-space:nowrap
}
.acc-page .acc-filter-actions .pd-btn{
    background:linear-gradient(180deg,#00a88e 0%,#009B80 100%);
    box-shadow:0 6px 12px rgba(0,155,128,.22)
}
.acc-page .acc-filter-actions .pd-btn-ghost{border:1.5px solid #009B80;color:#009B80;background:#fff}
.acc-page .acc-filter-actions .pd-btn-ghost:hover{background:#ecfdf5;color:#007F68}

/* Side panels — compact separate cards, no inner scroll */
.acc-page .acc-panel{
    background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;
    box-shadow:0 1px 2px rgba(15,23,42,.04),0 6px 14px rgba(148,163,184,.1);
    flex:0 0 auto
}
.acc-page .acc-panel-h{
    padding:.4rem .65rem;border-bottom:1px solid #eef2f7;background:#fff;
    display:flex;align-items:center;justify-content:space-between;gap:6px
}
.acc-page .acc-panel-title{
    display:inline-flex;align-items:center;gap:7px;font-size:.78rem;font-weight:800;color:#0f172a;margin:0
}
.acc-page .acc-hico{
    width:24px;height:24px;border-radius:7px;display:inline-flex;align-items:center;justify-content:center;
    background:#d1fae5;color:#059669;font-size:.75rem;flex:0 0 auto
}
.acc-page .acc-panel-b{padding:.4rem .65rem .5rem}
.acc-page .acc-panel-link{
    font-size:.68rem;font-weight:700;color:#2563eb;text-decoration:none;white-space:nowrap
}
.acc-page .acc-panel-link:hover{text-decoration:underline;color:#1d4ed8}
.acc-page .acc-conf-row{
    display:flex;align-items:center;justify-content:space-between;gap:6px;padding:.2rem 0;
    border-bottom:1px solid #f1f5f9
}
.acc-page .acc-conf-row:last-of-type{border-bottom:0}
.acc-page .acc-conf-left{display:flex;align-items:center;gap:7px;min-width:0}
.acc-page .acc-conf-left .lbl{font-size:.72rem;font-weight:700;color:#0f172a;line-height:1.2}
.acc-page .acc-badge{
    display:inline-flex;align-items:center;padding:.1rem .4rem;border-radius:999px;
    font-size:.6rem;font-weight:750;white-space:nowrap
}
.acc-page .acc-badge.ok{background:#dcfce7;color:#15803d}
.acc-page .acc-badge.warn{background:#ffedd5;color:#c2410c}
.acc-page .acc-note{
    margin-top:.35rem;padding:.35rem .45rem;border-radius:8px;font-size:.64rem;line-height:1.3;font-weight:600
}
.acc-page .acc-note.info{background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af}
.acc-page .acc-note.warn{background:#fff7ed;border:1px solid #fed7aa;color:#9a3412}
.acc-page .acc-snap-row{
    display:grid;grid-template-columns:1fr auto;gap:6px;align-items:center;
    padding:.12rem 0;border-bottom:1px solid #f1f5f9
}
.acc-page .acc-snap-row:last-child{border-bottom:0}
.acc-page .acc-snap-k{font-size:.68rem;color:#64748b;font-weight:600;margin:0}
.acc-page .acc-snap-v{
    font-size:.72rem;font-weight:750;color:#0f172a;margin:0;text-align:right;line-height:1.2;
    word-break:break-word;max-width:160px
}
.acc-page .acc-snap-v.mono{
    font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:800
}
.acc-page .acc-snap-foot{
    margin-top:.4rem;padding-top:.4rem;border-top:1px solid #f1f5f9
}
.acc-page .acc-snap-foot-label{
    font-size:.62rem;font-weight:700;color:#64748b;margin:0 0 .3rem
}
.acc-page .acc-view-case{
    height:28px;padding:0 10px;border-radius:7px;border:1.5px solid #3b82f6;
    background:#fff;color:#2563eb;font-size:.7rem;font-weight:700;text-decoration:none;
    display:inline-flex;align-items:center;gap:4px
}
.acc-page .acc-view-case:hover{background:#eff6ff;color:#1d4ed8}

.acc-page .acc-conf-left .dot{
    width:16px;height:16px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;
    font-size:.58rem;flex:0 0 auto;background:#ffedd5;color:#c2410c
}
.acc-page .acc-conf-left .dot.ok{background:#dcfce7;color:#15803d}
.acc-page .acc-conf-left .dot i{line-height:1}
.acc-page .acc-act{margin:0;padding:0;list-style:none}
.acc-page .acc-act li{
    position:relative;padding:0 0 .55rem 12px;border-left:2px solid #d1fae5;font-size:.7rem
}
.acc-page .acc-act li:last-child{padding-bottom:0;border-left-color:transparent}
.acc-page .acc-act li::before{
    content:'';position:absolute;left:-4px;top:3px;width:6px;height:6px;border-radius:50%;background:#10b981
}
.acc-page .acc-act .t{font-weight:650;color:#0f172a;line-height:1.3}
.acc-page .acc-act .m{color:#94a3b8;margin-top:2px;font-size:.64rem}

/* Queue table — Case List mockup */
.acc-page .pd-card{border-radius:14px;border-color:#e2e8f0;box-shadow:0 1px 2px rgba(15,23,42,.04),0 8px 20px rgba(148,163,184,.1)}
.acc-page .acc-qcard .pd-card-head{
    padding:.85rem 1.1rem;align-items:center;gap:12px;border-bottom:1px solid #eef2f7;flex-wrap:nowrap
}
.acc-page .acc-qhead{display:flex;align-items:center;gap:12px;min-width:0;flex:1 1 auto}
.acc-page .acc-qico{
    width:36px;height:36px;border-radius:10px;background:#d1fae5;color:#059669;
    display:inline-flex;align-items:center;justify-content:center;font-size:1rem;flex:0 0 auto
}
.acc-page .acc-qtitle{
    margin:0;font-size:.98rem;font-weight:800;color:#0f172a;line-height:1.25;
    display:flex;flex-wrap:wrap;align-items:baseline;gap:6px
}
.acc-page .acc-qcount{font-size:.72rem;font-weight:650;color:#64748b}
.acc-page .acc-qcard{overflow:visible !important}
.acc-page .acc-qcard .pd-card-head{overflow:visible;position:relative;z-index:20}
.acc-page .acc-col-dd{position:relative;z-index:21}
.acc-page .acc-col-btn{
    height:34px;padding:0 12px;border-radius:8px;border:1px solid #dbe3f0;background:#fff;
    color:#334155;font-size:.75rem;font-weight:700;display:inline-flex;align-items:center;gap:6px;
    white-space:nowrap
}
.acc-page .acc-col-btn:hover{background:#f8fafc;color:#0f172a}
.acc-page .acc-col-menu{
    min-width:220px;max-height:none;overflow:visible;border-radius:10px;border:1px solid #e2e8f0;
    padding:.45rem;box-shadow:0 10px 28px rgba(15,23,42,.14)
}
.acc-page .acc-col-item{
    display:flex;align-items:center;gap:8px;font-size:.78rem;font-weight:650;color:#334155;
    border-radius:6px;cursor:pointer;padding:.4rem .55rem;margin:0;user-select:none
}
.acc-page .acc-col-item:hover,.acc-page .acc-col-item:focus{background:#f1f5f9}
.acc-page .acc-col-item .form-check-input{
    margin:0;flex:0 0 auto;cursor:pointer;float:none
}
.acc-page .acc-qcard .pd-table-wrap{
    overflow-x:auto;-webkit-overflow-scrolling:touch;
    scrollbar-width:thin;scrollbar-color:#cbd5e1 transparent
}
.acc-page .acc-qcard .pd-table-wrap::-webkit-scrollbar{height:6px}
.acc-page .acc-qcard .pd-table-wrap::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:999px}
.acc-page .acc-qcard .pd-table-wrap::-webkit-scrollbar-track{background:transparent}
.acc-page .acc-qtable{width:100%;min-width:0;table-layout:auto}
.acc-page .acc-qtable thead th{
    font-size:.68rem;font-weight:750;letter-spacing:.01em;text-transform:none;
    padding:.55rem .5rem;background:#eef4fb;color:#1e3a5f;
    white-space:nowrap;border-bottom:1px solid #dbe3f0
}
.acc-page .acc-qtable tbody td{
    padding:.45rem .5rem;font-size:.76rem;vertical-align:middle;white-space:nowrap;
    color:#334155;border-bottom:1px solid #eef2f7;line-height:1.25;background:#fff
}
.acc-page .acc-qtable tbody tr:last-child td{border-bottom:0}
.acc-page .acc-qtable tbody tr:hover td{background:#f8fafc}
.acc-page .acc-qtable tbody tr.acc-row-active td{background:#ecfdf5 !important}
.acc-page .acc-qtable .acc-col-num{width:36px;color:#64748b;font-weight:650;text-align:center}
.acc-page .acc-qtable .pd-case{font-size:.76rem;font-weight:750;color:#009B80;text-decoration:none}
.acc-page .acc-qtable .pd-case:hover{color:#007F68;text-decoration:underline}
.acc-page .acc-amt{font-weight:750;color:#0f172a;white-space:nowrap;font-variant-numeric:tabular-nums}
.acc-page .acc-status{
    display:inline-flex;align-items:center;padding:.18rem .5rem;border-radius:999px;
    font-size:.64rem;font-weight:750;white-space:nowrap;background:#f1f5f9;color:#475569
}
.acc-page .acc-status.warn{background:#ffedd5;color:#c2410c}
.acc-page .acc-status.report{background:#dbeafe;color:#1d4ed8}
.acc-page .acc-status.ok{background:#dcfce7;color:#15803d}
.acc-page .acc-status.eligible{background:#d1fae5;color:#047857}
.acc-page .acc-status.processing{background:#ede9fe;color:#6d28d9}
.acc-page .acc-status.bad{background:#fee2e2;color:#b91c1c}
.acc-page .acc-status.info{background:#e0f2fe;color:#0369a1}
.acc-page .acc-qacts{display:inline-flex;align-items:center;gap:5px;flex-wrap:nowrap}
.acc-page .acc-abtn{
    height:28px;padding:0 8px;border-radius:7px;font-size:.68rem;font-weight:700;
    display:inline-flex;align-items:center;justify-content:center;gap:4px;text-decoration:none;
    white-space:nowrap;border:1.5px solid #dbe3f0;background:#fff;color:#334155;line-height:1
}
.acc-page .acc-abtn:hover{background:#f8fafc;color:#0f172a}
.acc-page .acc-abtn.view{border-color:#93c5fd;color:#2563eb}
.acc-page .acc-abtn.view:hover{background:#eff6ff;color:#1d4ed8}
.acc-page .acc-abtn.process{border-color:#6ee7b7;color:#047857}
.acc-page .acc-abtn.process:hover{background:#ecfdf5;color:#065f46}
.acc-page .acc-qtable th.is-hidden,
.acc-page .acc-qtable td.is-hidden{display:none !important}
.acc-page .acc-qtable thead th:last-child,
.acc-page .acc-qtable tbody td.pd-col-actions{
    position:sticky;right:0;z-index:2;background:#fff;
    box-shadow:-6px 0 10px -8px rgba(15,23,42,.18)
}
.acc-page .acc-qtable thead th:last-child{background:#eef4fb;z-index:3}
.acc-page .acc-qtable tbody tr:hover td.pd-col-actions{background:#f8fafc}
.acc-page .acc-qtable tbody tr.acc-row-active td.pd-col-actions{background:#ecfdf5}
.acc-page .acc-qfoot{
    display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;
    padding:.7rem 1rem;border-top:1px solid #e2e8f0;background:#fafbfc;
    font-size:.76rem;color:#64748b;position:relative;z-index:1
}
.acc-page .acc-qfoot .pagination{margin:0;display:flex;flex-wrap:wrap;gap:4px}
.acc-page .acc-qfoot .page-link{
    min-width:30px;height:30px;padding:0 .5rem;display:inline-flex;align-items:center;justify-content:center;
    border-radius:8px;font-size:.75rem;font-weight:650;color:#334155;border:1px solid #e2e8f0;background:#fff
}
.acc-page .acc-qfoot .page-item.active .page-link{
    background:#0f766e;border-color:#0f766e;color:#fff
}
.acc-page .acc-qfoot .page-item.disabled .page-link{opacity:.45}

@media (max-width:1199.98px){
    .acc-page .acc-layout{grid-template-columns:1fr}
    .acc-page .acc-side{position:static;max-height:none;overflow:visible;display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .acc-page .acc-kpi-row{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
    .acc-page a.acc-kpi{padding:.85rem .9rem;min-height:84px;gap:10px;border-radius:14px}
    .acc-page .acc-kpi-ico{width:40px;height:40px;font-size:1.05rem;border-radius:10px}
    .acc-page .acc-kpi-label{font-size:.74rem}
    .acc-page .acc-kpi-value{font-size:1.4rem}
    .acc-page .acc-filters-row.top,.acc-page .acc-filters-row.bottom{grid-template-columns:1fr 1fr}
    .acc-page .acc-step{min-width:150px;flex:0 0 auto}
}
@media (max-width:991.98px){
    .acc-page .pd-hero{flex-direction:column;align-items:stretch}
    .acc-page .pd-hero-actions{width:100%;display:grid;grid-template-columns:1fr 1fr;gap:8px}
    .acc-page .pd-hero-actions .dropdown{min-width:0}
    .acc-page .pd-hero-actions .pd-link-btn{justify-content:center;width:100%}
    .acc-page .acc-tabs{flex-wrap:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch;padding-bottom:6px;scrollbar-width:none}
    .acc-page .acc-tabs::-webkit-scrollbar{display:none}
    .acc-page .acc-tab .full{display:none}
    .acc-page .acc-tab .short{display:inline}
    .acc-page .acc-kpi-label .full{display:none}
    .acc-page .acc-kpi-label .short{display:inline}
    .acc-page .acc-step-label .full{display:none}
    .acc-page .acc-step-label .short{display:inline}
    .acc-page .acc-step-sub .full{display:none}
    .acc-page .acc-step-sub .short{display:inline}
    .acc-page .acc-flow{gap:6px;padding-bottom:2px;margin-bottom:.75rem}
    .acc-page .acc-flow-arr{font-size:.7rem}
    .acc-page .acc-step{padding:.55rem .65rem;gap:6px;border-radius:10px;min-width:118px;flex:0 0 auto}
    .acc-page .acc-step-ico{width:26px;height:26px;font-size:.75rem;border-radius:7px}
    .acc-page .acc-step-label{font-size:.64rem;white-space:nowrap}
    .acc-page .acc-step-sub{font-size:.56rem;white-space:nowrap}
    .acc-page .acc-hold-banner{font-size:.76rem;padding:.6rem .8rem;margin-bottom:.85rem}
}
@media (max-width:767.98px){
    .acc-page.container-fluid{padding-left:.85rem!important;padding-right:.85rem!important}
    .acc-page .acc-side{grid-template-columns:1fr}
    .acc-page .acc-filters-row.top,.acc-page .acc-filters-row.bottom{grid-template-columns:1fr}
    .acc-page .acc-filter-actions{width:100%}
    .acc-page .acc-filter-actions .pd-btn,
    .acc-page .acc-filter-actions .pd-btn-ghost{flex:1}
    .acc-page .acc-kpi-row{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
    .acc-page a.acc-kpi{
        flex-direction:row;align-items:center;justify-content:flex-start;
        padding:.65rem .7rem;min-height:0;gap:8px;border-radius:12px;text-align:left
    }
    .acc-page a.acc-kpi::after{width:72px;height:72px;right:-20px;bottom:-28px}
    .acc-page .acc-kpi-ico{width:32px;height:32px;font-size:.9rem;border-radius:9px;flex-shrink:0}
    .acc-page .acc-kpi-body{display:flex;flex-direction:column;align-items:flex-start;justify-content:center;min-width:0}
    .acc-page .acc-kpi-label{
        font-size:.68rem;line-height:1.15;
        white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%
    }
    .acc-page .acc-kpi-value{font-size:1.22rem;margin-top:.1rem;line-height:1}
    .acc-page .acc-kpi-pct{font-size:.6rem;margin-top:.12rem;opacity:.9}
    .acc-page .acc-view-case{width:100%;justify-content:center}
    .acc-page .acc-qtable thead th:last-child,
    .acc-page .acc-qtable tbody td.pd-col-actions{
        position:static;box-shadow:none
    }
    .acc-page .acc-qfoot{flex-direction:column;align-items:stretch}
    .acc-page .acc-qfoot .pagination{justify-content:center}
    .acc-page .acc-step{min-width:112px}
}
@media (max-width:479.98px){
    .acc-page.container-fluid{padding-left:.7rem!important;padding-right:.7rem!important}
    .acc-page .acc-kpi-row{gap:6px}
    .acc-page a.acc-kpi{padding:.55rem .55rem;gap:6px;border-radius:11px}
    .acc-page .acc-kpi-ico{width:28px;height:28px;font-size:.8rem;border-radius:8px}
    .acc-page .acc-kpi-label{font-size:.6rem}
    .acc-page .acc-kpi-value{font-size:1.1rem}
    .acc-page .acc-kpi-pct{display:none}
}
</style>
@endpush

@section('content')
@php
    $qs = fn (array $extra = []) => array_filter(array_merge(request()->except(['page', 'partial']), $extra), fn ($v) => $v !== null && $v !== '');
@endphp
<div class="container-fluid p-4 pd-page acc-page" data-acc-index-url="{{ route('goal.accounts.index') }}" data-acc-export-url="{{ route('goal.accounts.export') }}">
    <div class="pd-hero">
        <div class="pd-hero-brand">
            <span class="acc-hero-ico" aria-hidden="true"><i class="bi bi-credit-card-2-front"></i></span>
            <div>
                <ul class="pd-crumbs">
                    <li><a href="{{ url('/') }}">Home</a></li>
                    <li class="sep">›</li>
                    <li>Post-Disbursement</li>
                    <li class="sep">›</li>
                    <li>Accounts Queue</li>
                </ul>
                <h1 class="pd-title">Accounts Queue</h1>
                <p class="pd-sub">Capture report numbers, verify payout eligibility, record receipts and reconcile payouts.</p>
            </div>
        </div>
        <div class="pd-hero-actions">
            <div class="dropdown">
                <button class="pd-link-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download"></i> Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" id="accExportLink" href="{{ route('goal.accounts.export', $qs()) }}">CSV</a></li>
                </ul>
            </div>
            <button type="button" class="pd-link-btn" data-bs-toggle="modal" data-bs-target="#accGuideModal">
                <i class="bi bi-info-circle"></i> Accounts Guide
            </button>
        </div>
    </div>

    @if(!empty($missingOrgScope))
        <div class="alert alert-warning py-2 d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
            <div>
                <strong>Organization unit not assigned.</strong>
                Accounts Queue is empty until a branch/region is linked to your user.
            </div>
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
    @endif

    <div id="accResults" class="acc-results" aria-live="polite">
        @include('goal.accounts.partials.queue_results')
    </div>
</div>

<div class="modal fade" id="accGuideModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px">
            <div class="modal-header">
                <h5 class="modal-title">Accounts Guide</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size:.9rem;color:#334155;line-height:1.5">
                <p class="mb-2" style="font-size:.72rem;font-weight:800;color:#0f766e;text-transform:uppercase;letter-spacing:.04em">Accounts Workflow (after PD-01)</p>
                <p class="mb-2"><strong>PD-01</strong> verifies disbursement → Accounts Queue + PMS Queue open in parallel → Payout stays On Hold.</p>
                <ol class="mb-0 ps-3">
                    <li>Capture the lender report number (and optional report date/document).</li>
                    <li>Confirm PMS verification and commercial terms before approval.</li>
                    <li>Record receipts (partial allowed) and reconcile outstanding amounts.</li>
                    <li>Approve reconciliation to unlock payout eligibility with PMS.</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const page = document.querySelector('.acc-page');
    const results = document.getElementById('accResults');
    if (!page || !results) return;

    const indexUrl = page.getAttribute('data-acc-index-url') || '/goal/accounts';
    const exportUrl = page.getAttribute('data-acc-export-url') || '/goal/accounts/export';
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
        const rangeEl = document.getElementById('accDateRange');
        const fromEl = document.getElementById('accDateFrom');
        const toEl = document.getElementById('accDateTo');
        if (!rangeEl || !window.flatpickr) return;
        const defaults = [];
        if (fromEl && fromEl.value) defaults.push(fromEl.value);
        if (toEl && toEl.value) defaults.push(toEl.value);
        fpInstance = flatpickr(rangeEl, {
            mode: 'range', dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: false,
            defaultDate: defaults.length ? defaults : null,
            onChange: function (selectedDates, _str, instance) {
                if (!fromEl || !toEl) return;
                fromEl.value = selectedDates[0] ? instance.formatDate(selectedDates[0], 'Y-m-d') : '';
                toEl.value = selectedDates[1] ? instance.formatDate(selectedDates[1], 'Y-m-d') : (selectedDates[0] ? instance.formatDate(selectedDates[0], 'Y-m-d') : '');
            }
        });
    }

    function initSelect2(root) {
        if (window.IsarvaSelect2 && typeof window.IsarvaSelect2.init === 'function') {
            window.IsarvaSelect2.init(root || results);
        }
    }

    const COL_KEY = 'accQueueCols';
    function savedCols() {
        try { return JSON.parse(localStorage.getItem(COL_KEY) || '{}') || {}; }
        catch (e) { return {}; }
    }
    function setColVisible(key, show) {
        const saved = savedCols();
        saved[key] = !!show;
        try { localStorage.setItem(COL_KEY, JSON.stringify(saved)); } catch (err) {}
        applyColumnVisibility();
    }
    function applyColumnVisibility() {
        const table = document.getElementById('accQueueTable');
        if (!table) return;
        const saved = savedCols();
        table.querySelectorAll('th[data-col], td[data-col]').forEach(function (el) {
            const key = el.getAttribute('data-col');
            if (!key) return;
            const show = saved[key] !== false;
            el.classList.toggle('is-hidden', !show);
        });
        document.querySelectorAll('.acc-page .acc-col-toggle, .acc-col-menu .acc-col-toggle').forEach(function (cb) {
            const key = cb.getAttribute('data-col');
            if (!key) return;
            cb.checked = saved[key] !== false;
        });
    }
    function bindResults() {
        initDateRange();
        initSelect2(results);
        syncExport(currentParams());
        applyColumnVisibility();
    }

    function setLoading(on) {
        loading = !!on;
        results.classList.toggle('is-loading', loading);
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

    function syncExport(params) {
        const link = document.getElementById('accExportLink');
        if (!link) return;
        const qs = new URLSearchParams(params);
        qs.delete('partial'); qs.delete('page'); qs.delete('selected');
        link.href = exportUrl + '?' + qs.toString();
    }

    function currentParams() {
        const form = document.getElementById('accFilters');
        if (form) return new URLSearchParams(new FormData(form));
        return new URLSearchParams(window.location.search);
    }

    function loadQueue(params) {
        if (loading) return Promise.resolve();
        setLoading(true);
        if (abortCtrl) { try { abortCtrl.abort(); } catch (e) {} }
        abortCtrl = new AbortController();
        const qs = new URLSearchParams(params);
        qs.set('partial', '1');
        return fetch(indexUrl + '?' + qs.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
            credentials: 'same-origin',
            signal: abortCtrl.signal
        }).then(function (res) {
            if (!res.ok) throw new Error('Failed');
            return res.text();
        }).then(function (html) {
            destroyWidgets(results);
            results.innerHTML = html;
            bindResults();
            const clean = new URLSearchParams(params);
            clean.delete('partial');
            syncUrl(clean);
            syncExport(params);
        }).catch(function (err) {
            if (err && err.name === 'AbortError') return;
            results.innerHTML = '<div class="alert alert-warning mb-0">Unable to refresh Accounts Queue. Please try again.</div>';
        }).finally(function () { setLoading(false); });
    }

    function paramsFromUrl(href) {
        try { return new URL(href, window.location.origin).searchParams; }
        catch (e) { return new URLSearchParams(); }
    }

    results.addEventListener('click', function (e) {
        // Case reference / action links should navigate normally (Process screen).
        if (e.target.closest('a.pd-case, a.acc-abtn')) return;

        const nav = e.target.closest('[data-acc-nav]');
        if (nav && results.contains(nav)) {
            e.preventDefault();
            const params = paramsFromUrl(nav.getAttribute('href'));
            if (nav.hasAttribute('data-tab')) {
                const form = document.getElementById('accFilters');
                const merged = form ? new URLSearchParams(new FormData(form)) : new URLSearchParams();
                merged.set('tab', nav.getAttribute('data-tab') || 'all');
                merged.delete('page'); merged.delete('selected');
                loadQueue(merged);
                return;
            }
            loadQueue(params);
            return;
        }

        // Clicking a queue row (not a link) selects it in the right panels.
        const row = e.target.closest('tr[data-acc-select-url]');
        if (row && results.contains(row) && !e.target.closest('a, button, input, label')) {
            e.preventDefault();
            loadQueue(paramsFromUrl(row.getAttribute('data-acc-select-url')));
            return;
        }

        const pageLink = e.target.closest('.acc-pager a, .pagination a');
        if (pageLink && results.contains(pageLink)) {
            e.preventDefault();
            loadQueue(paramsFromUrl(pageLink.getAttribute('href')));
            return;
        }
        const resetBtn = e.target.closest('#accResetBtn');
        if (resetBtn && results.contains(resetBtn)) {
            e.preventDefault();
            const tab = (document.getElementById('accTabInput') || {}).value || 'all';
            loadQueue(new URLSearchParams({ tab: tab }));
        }
    });

    results.addEventListener('submit', function (e) {
        const form = e.target.closest('#accFilters');
        if (!form || !results.contains(form)) return;
        e.preventDefault();
        const params = new URLSearchParams(new FormData(form));
        params.delete('page'); params.delete('selected');
        loadQueue(params);
    });

    // Delegate on document — Bootstrap may render the menu outside #accResults.
    document.addEventListener('change', function (e) {
        const t = e.target;
        if (!t || !t.classList || !t.classList.contains('acc-col-toggle')) return;
        if (!document.querySelector('.acc-page')) return;
        const key = t.getAttribute('data-col');
        if (!key) return;
        setColVisible(key, t.checked);
    });
    document.addEventListener('click', function (e) {
        if (!e.target.closest || !e.target.closest('.acc-col-menu')) return;
        // Keep menu open while toggling checkboxes.
        e.stopPropagation();
    });

    bindResults();
})();
</script>
@endpush
