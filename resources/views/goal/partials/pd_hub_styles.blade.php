{{-- Shared styles for PMS / Accounts / Payouts hubs --}}
<style>
.pd-page{color:#1e293b}
.pd-page .pd-hero{
    display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;
    gap:.85rem;margin-bottom:.85rem;
}
.pd-page .pd-crumbs{
    display:flex;flex-wrap:wrap;align-items:center;gap:6px;
    margin:0 0 8px;padding:0;list-style:none;font-size:.78rem;color:#64748b;
}
.pd-page .pd-crumbs a{color:var(--theme-primary);text-decoration:none;font-weight:600}
.pd-page .pd-crumbs a:hover{text-decoration:underline}
.pd-page .pd-crumbs .sep{opacity:.5}
.pd-page .pd-title{font-size:1.45rem;font-weight:800;color:#0f172a;margin:0;line-height:1.2;letter-spacing:-.02em}
.pd-page .pd-sub{color:#64748b;font-size:.88rem;margin-top:4px;line-height:1.4}
.pd-page .pd-hero-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.pd-page .pd-link-btn{
    height:38px;border-radius:10px;border:1px solid #dbe3f0;background:#fff;color:#334155;
    padding:0 14px;font-weight:650;font-size:.82rem;text-decoration:none;
    display:inline-flex;align-items:center;gap:6px;
}
.pd-page .pd-link-btn:hover{background:#f8fafc;color:#0f172a}
.pd-page .pd-link-btn.primary{
    border:0;background:var(--theme-primary);color:#fff;
    box-shadow:0 6px 14px var(--theme-primary-ring);
}
.pd-page .pd-link-btn.primary:hover{color:#fff;filter:brightness(1.05)}

.pd-page .pd-filters{
    display:grid;grid-template-columns:minmax(160px,1.5fr) minmax(140px,1fr) auto;
    gap:10px;align-items:end;margin-bottom:.85rem;padding:.85rem 1rem;
    background:#fff;border:1px solid #e8edf5;border-radius:14px;
    box-shadow:0 6px 16px rgba(148,163,184,.08);
}
.pd-page .pd-filters label{
    display:block;font-size:.72rem;font-weight:700;color:#64748b;margin:0 0 4px;
}
.pd-page .pd-filters .form-control,
.pd-page .pd-filters .form-select{
    min-height:38px;height:38px;border-radius:9px;border:1px solid #d1d5db;font-size:.84rem;background:#fff;
}
.pd-page .pd-filter-actions{display:flex;gap:8px;flex-wrap:wrap}
.pd-page a.pd-kpi{
    text-decoration:none;color:inherit;transition:box-shadow .15s ease,transform .15s ease;
}
.pd-page a.pd-kpi:hover{
    box-shadow:0 2px 4px rgba(15,23,42,.06),0 10px 20px rgba(148,163,184,.14);
    transform:translateY(-1px);
}
.pd-page a.pd-kpi.is-active{outline:2px solid var(--theme-primary);outline-offset:1px}

.pd-page .pd-kpi-row{
    display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:.85rem;
}
.pd-page .pd-kpi{
    background:#fff;border:1px solid #e8edf5;border-radius:12px;padding:.75rem .85rem;
    box-shadow:0 1px 2px rgba(15,23,42,.04),0 6px 14px rgba(148,163,184,.08);
    border-left:3px solid var(--theme-primary);
}
.pd-page .pd-kpi.tone-amber{border-left-color:#ea580c}
.pd-page .pd-kpi.tone-green{border-left-color:#16a34a}
.pd-page .pd-kpi.tone-red{border-left-color:#dc2626}
.pd-page .pd-kpi.tone-blue{border-left-color:var(--theme-primary)}
.pd-page .pd-kpi.tone-slate{border-left-color:#64748b}
.pd-page .pd-kpi-label{font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.03em;margin:0}
.pd-page .pd-kpi-value{font-size:1.35rem;font-weight:800;color:#0f172a;margin:.2rem 0 0;line-height:1.1;letter-spacing:-.02em}

.pd-page .pd-card{
    background:#fff;border:1px solid #e8edf5;border-radius:16px;overflow:hidden;
    box-shadow:0 10px 28px rgba(148,163,184,.12);
}
.pd-page .pd-card-head{
    display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.5rem;
    padding:.85rem 1.1rem;border-bottom:1px solid #eef2f7;
}
.pd-page .pd-card-head h5{margin:0;font-size:1.02rem;font-weight:800;color:#0f172a}
.pd-page .pd-card-head .pd-badge{
    display:inline-flex;align-items:center;padding:.22rem .65rem;border-radius:999px;
    font-size:.72rem;font-weight:700;background:#fff7ed;color:#c2410c;
}
.pd-page .pd-table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
.pd-page .pd-table{width:100%;margin:0;border-collapse:separate;border-spacing:0}
.pd-page .pd-table thead th{
    font-size:.7rem;text-transform:uppercase;letter-spacing:.04em;color:#64748b;font-weight:700;
    background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:.75rem .9rem;white-space:nowrap;
}
.pd-page .pd-table tbody td{
    vertical-align:middle;font-size:.86rem;color:#334155;
    border-bottom:1px solid #eef2f7;padding:.8rem .9rem;
}
.pd-page .pd-table tbody tr:last-child td{border-bottom:0}
.pd-page .pd-table tbody tr:hover td{background:#f8fbff}
.pd-page .pd-case{
    font-weight:800;color:var(--theme-primary);text-decoration:none;
    font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.82rem;
}
.pd-page .pd-case:hover{text-decoration:underline}
.pd-page .pd-cust{font-weight:650;color:#0f172a}
.pd-page .pd-muted{font-size:.75rem;color:#94a3b8;margin-top:2px}
.pd-page .pd-amt{font-weight:700;color:#0f172a;white-space:nowrap}
.pd-page .pd-pill{
    display:inline-flex;align-items:center;padding:.22rem .6rem;border-radius:999px;
    font-size:.7rem;font-weight:700;background:#f1f5f9;color:#475569;
}
.pd-page .pd-pill.ok{background:#dcfce7;color:#15803d}
.pd-page .pd-pill.warn{background:#ffedd5;color:#c2410c}
.pd-page .pd-pill.bad{background:#fee2e2;color:#b91c1c}
.pd-page .pd-pill.info{background:var(--theme-100);color:var(--theme-primary-hover)}
.pd-page .pd-pill.slate{background:#e2e8f0;color:#334155}
.pd-page .pd-pill.eligible{background:#dcfce7;color:#15803d}
.pd-page .pd-pill.processing{background:#dbeafe;color:#1d4ed8}

.pd-page .pd-action-form{
    display:flex;flex-wrap:wrap;align-items:center;gap:6px;min-width:0;
}
.pd-page .pd-action-form .form-control,
.pd-page .pd-action-form .form-select{
    min-height:36px;height:36px;border-radius:8px;border:1px solid #d1d5db;
    font-size:.82rem;padding:.25rem .55rem;background:#fff;max-width:160px;
}
.pd-page .pd-action-form .form-control.flex-grow,
.pd-page .pd-action-form .form-select.flex-grow{max-width:none;flex:1 1 120px}
.pd-page .pd-btn{
    height:36px;border-radius:8px;border:0;padding:0 12px;font-weight:700;font-size:.78rem;
    display:inline-flex;align-items:center;justify-content:center;gap:5px;cursor:pointer;white-space:nowrap;
    background:var(--theme-primary);color:#fff;
}
.pd-page .pd-btn:hover{filter:brightness(1.05);color:#fff}
.pd-page .pd-btn-ghost{
    height:36px;border-radius:8px;border:1px solid #e2e8f0;padding:0 12px;font-weight:650;font-size:.78rem;
    display:inline-flex;align-items:center;justify-content:center;gap:5px;cursor:pointer;white-space:nowrap;
    background:#fff;color:#475569;text-decoration:none;
}
.pd-page .pd-btn-ghost:hover{background:#f8fafc;color:#0f172a}
.pd-page .pd-btn-danger{
    height:36px;border-radius:8px;border:1px solid #fecaca;padding:0 12px;font-weight:700;font-size:.78rem;
    display:inline-flex;align-items:center;justify-content:center;gap:5px;cursor:pointer;white-space:nowrap;
    background:#fff;color:#b91c1c;
}
.pd-page .pd-btn-danger:hover{background:#fef2f2}
.pd-page .pd-stack-forms{display:flex;flex-direction:column;gap:6px;min-width:220px}
.pd-page .pd-empty{
    padding:2rem 1rem;text-align:center;color:#94a3b8;
}
.pd-page .pd-empty i{font-size:1.6rem;display:block;margin-bottom:.5rem;opacity:.5}
.pd-page .pd-foot{
    display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;
    padding:.75rem 1.1rem;border-top:1px solid #eef2f7;font-size:.82rem;color:#64748b;
}
.pd-page .pd-note{
    margin:.75rem 0 0;padding:.65rem .85rem;border-radius:10px;font-size:.78rem;color:#475569;
    background:#f8fafc;border:1px dashed #cbd5e1;line-height:1.45;
}

@media (max-width:991.98px){
    .pd-page .pd-kpi-row{grid-template-columns:repeat(2,minmax(0,1fr))}
    .pd-page .pd-filters{grid-template-columns:1fr 1fr}
    .pd-page .pd-filter-actions{grid-column:1 / -1}
}
@media (max-width:767.98px){
    .pd-page .pd-filters{grid-template-columns:1fr;padding:.75rem}
    .pd-page.container-fluid{padding:.75rem .7rem 1.1rem !important}
    .pd-page .pd-title{font-size:1.15rem}
    .pd-page .pd-sub{font-size:.78rem}
    .pd-page .pd-kpi-row{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin-bottom:.7rem}
    .pd-page .pd-kpi{padding:.65rem .7rem;border-radius:10px}
    .pd-page .pd-kpi-value{font-size:1.15rem}
    .pd-page .pd-kpi-label{font-size:.6rem}
    .pd-page .pd-card{border-radius:14px}
    .pd-page .pd-card-head{padding:.7rem .85rem}
    .pd-page .pd-card-head h5{font-size:.95rem}
    .pd-page .pd-table-wrap{overflow:visible;padding:0 .65rem .65rem}
    .pd-page .pd-table{min-width:0 !important;display:block;width:100%}
    .pd-page .pd-table thead{display:none}
    .pd-page .pd-table tbody{display:block}
    .pd-page .pd-table tr{
        display:block;margin:0 0 .65rem;padding:.7rem .75rem;
        border:1px solid #e8edf5;border-radius:12px;background:#f8fafc;
    }
    .pd-page .pd-table tr:last-child{margin-bottom:0}
    .pd-page .pd-table td{
        display:flex !important;align-items:flex-start;justify-content:space-between;gap:.55rem;
        width:100%;padding:.35rem 0 !important;border:0 !important;border-bottom:1px solid #eef2f7 !important;
        font-size:.8rem !important;white-space:normal !important;text-align:right;
    }
    .pd-page .pd-table td:last-child{border-bottom:0 !important;padding-top:.5rem !important;display:block !important;text-align:left}
    .pd-page .pd-table td::before{
        content:attr(data-label);flex:0 0 36%;max-width:36%;
        font-size:.62rem;font-weight:700;letter-spacing:.03em;text-transform:uppercase;
        color:#64748b;text-align:left;line-height:1.3;padding-top:1px;
    }
    .pd-page .pd-table td.pd-col-actions::before{display:none}
    .pd-page .pd-table td.pd-col-empty{display:block !important;text-align:center;border:0 !important}
    .pd-page .pd-table td.pd-col-empty::before{display:none}
    .pd-page .pd-action-form{flex-direction:column;align-items:stretch}
    .pd-page .pd-action-form .form-control,
    .pd-page .pd-action-form .form-select{max-width:none;width:100%}
    .pd-page .pd-btn,.pd-page .pd-btn-ghost,.pd-page .pd-btn-danger{width:100%;justify-content:center;height:40px}
    .pd-page .pd-stack-forms{min-width:0}
    .pd-page .pd-foot{flex-direction:column;align-items:stretch;padding:.7rem .85rem}
}
@media (max-width:379.98px){
    .pd-page .pd-kpi-row{grid-template-columns:1fr 1fr}
}
</style>
