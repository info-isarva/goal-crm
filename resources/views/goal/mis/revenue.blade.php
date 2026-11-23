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

.pd-page.rev-page .rev-main-card{
    background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.04);overflow:hidden
}
.pd-page.rev-page .rev-drawer{
    background:transparent;border:0;border-radius:0;box-shadow:none;overflow:visible;
    display:flex;flex-direction:column;gap:10px
}
.pd-page.rev-page .rev-main-head{
    display:flex;flex-wrap:wrap;gap:8px 12px;align-items:center;justify-content:space-between;
    padding:10px 12px;border-bottom:1px solid #eef2f7;background:#fff
}
.pd-page.rev-page .rev-tabs{
    display:flex;flex-wrap:wrap;gap:6px;align-items:center;padding:0;margin:0;background:transparent;border:0
}
.pd-page.rev-page .rev-tab{
    appearance:none;border:1px solid #dbe4f0;background:#fff;padding:.38rem .75rem;font-size:.78rem;font-weight:700;color:#64748b;
    border-radius:999px;text-decoration:none;display:inline-flex;gap:5px;align-items:center;line-height:1.2
}
.pd-page.rev-page .rev-tab:hover{color:#059669;border-color:#a7f3d0;background:#ecfdf5}
.pd-page.rev-page .rev-tab.active{color:#fff;background:#059669;border-color:#059669}
.pd-page.rev-page .rev-tab .n{
    min-width:1.3rem;height:1.15rem;padding:0 5px;border-radius:999px;background:rgba(15,23,42,.06);color:inherit;
    font-size:.68rem;display:inline-flex;align-items:center;justify-content:center;font-weight:700
}
.pd-page.rev-page .rev-tab.active .n{background:rgba(255,255,255,.22);color:#fff}
.pd-page.rev-page .rev-toolbar{
    display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:flex-end;padding:0;border:0;margin:0
}
.pd-page.rev-page .rev-toolbar-actions{display:flex;flex-wrap:wrap;gap:6px;align-items:center}
.pd-page.rev-page .rev-btn-outline,
.pd-page.rev-page .rev-btn-outline-danger{
    height:32px;padding:0 10px;border-radius:8px;background:#fff;
    font-size:.76rem;font-weight:700;display:inline-flex;align-items:center;gap:5px;cursor:pointer
}
.pd-page.rev-page .rev-btn-outline{border:1.5px solid #10b981;color:#059669}
.pd-page.rev-page .rev-btn-outline:hover{background:#ecfdf5}
.pd-page.rev-page .rev-btn-outline-danger{border:1.5px solid #e11d48;color:#e11d48}
.pd-page.rev-page .rev-btn-outline-danger:hover{background:#fff1f2}
.pd-page.rev-page .rev-main-card .pd-table-wrap{
    overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch;
    scrollbar-width:thin;scrollbar-color:#cbd5e1 transparent
}
.pd-page.rev-page .rev-main-card .pd-table-wrap::-webkit-scrollbar{height:6px}
.pd-page.rev-page .rev-main-card .pd-table-wrap::-webkit-scrollbar-track{background:transparent}
.pd-page.rev-page .rev-main-card .pd-table-wrap::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:999px}
.pd-page.rev-page .rev-main-card .pd-table-wrap::-webkit-scrollbar-thumb:hover{background:#94a3b8}
.pd-page.rev-page .rev-main-card .pd-table{min-width:1080px}
.pd-page.rev-page .rev-main-card .pd-table thead th{
    padding:.55rem .65rem;font-size:.66rem;letter-spacing:.04em;background:#f8fafc;color:#475569;white-space:nowrap
}
.pd-page.rev-page .rev-main-card .pd-table tbody td{
    padding:.5rem .65rem;font-size:.8rem;vertical-align:middle;white-space:nowrap
}
.pd-page.rev-page .rev-main-card .pd-foot{
    display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:space-between;
    padding:.55rem .85rem;border-top:1px solid #eef2f7;font-size:.78rem;color:#64748b
}
.pd-page.rev-page .rev-attr-head{display:flex;align-items:center;justify-content:space-between;gap:8px;width:100%}
.pd-page.rev-page .rev-attr-title{display:flex;align-items:center;gap:8px;min-width:0;flex-wrap:wrap}
.pd-page.rev-page .rev-attr-ico{
    width:28px;height:28px;border-radius:7px;display:inline-flex;align-items:center;justify-content:center;
    background:#16a34a;color:#fff;font-size:.82rem;flex-shrink:0;box-shadow:0 2px 6px rgba(22,163,74,.22)
}
.pd-page.rev-page .rev-attr-title-text{min-width:0;display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.pd-page.rev-page .rev-attr-title-text h5{margin:0;font-size:.88rem;font-weight:800;color:#0f172a;line-height:1.2}
.pd-page.rev-page .rev-attr-title-text .pd-card-sub{
    margin:0;font-size:.72rem;font-weight:600;color:#94a3b8;display:inline-flex;align-items:center;gap:5px
}
.pd-page.rev-page .rev-attr-info{
    width:14px;height:14px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;
    background:#dbeafe;color:#2563eb;font-size:.58rem;border:0;padding:0;line-height:1;cursor:pointer
}
.pd-page.rev-page .rev-view-all{font-size:.75rem;font-weight:700;color:#2563eb;text-decoration:underline;white-space:nowrap}
.pd-page.rev-page .rev-view-all:hover{color:#1d4ed8}
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
.pd-page.rev-page .pd-table td .btn-view{
    width:28px;height:28px;border-radius:7px;border:1px solid #e2e8f0;background:#fff;color:#64748b;
    display:inline-flex;align-items:center;justify-content:center;font-size:.85rem
}
.pd-page.rev-page .pd-table td .btn-view:hover{border-color:#10b981;color:#059669}
.pd-page.rev-page .pd-table td .rev-row-actions{display:inline-flex;gap:4px;align-items:center;position:relative;z-index:2}
.pd-page.rev-page .pd-table tr.is-selected td{background:#ecfdf5}
.pd-page.rev-page .rev-action-menu{min-width:180px;border:1px solid #e2e8f0;border-radius:10px;padding:6px;font-size:.82rem;z-index:1080}
.pd-page.rev-page .rev-action-menu .dropdown-item{border-radius:8px;font-weight:600;padding:.45rem .7rem;cursor:pointer}
.pd-page.rev-page .rev-right.is-flash .rev-sec-card{box-shadow:0 0 0 2px rgba(16,185,129,.35)}
.pd-page.rev-page .pd-case{color:#2563eb;font-weight:600;text-decoration:none}
.pd-page.rev-page .pd-case:hover{text-decoration:underline}
.pd-page.rev-page .btn.rev-ref.is-link{color:#2563eb}

.pd-page.rev-page .rev-drawer-h{display:none}
.pd-page.rev-page .rev-drawer-b{display:flex;flex-direction:column;gap:10px;padding:0;min-width:0}
.pd-page.rev-page .rev-sec-card{
    background:#fff;border:1px solid #e2e8f0;border-radius:10px;
    box-shadow:0 1px 2px rgba(15,23,42,.04),0 4px 12px rgba(148,163,184,.08);
    padding:12px 12px 10px;min-width:0
}
.pd-page.rev-page .rev-sec-card .rev-sec-h{
    display:flex;align-items:center;justify-content:space-between;gap:8px;margin:0 0 10px
}
.pd-page.rev-page .rev-sec-card .rev-sec-h-left{display:flex;align-items:center;gap:8px;min-width:0}
.pd-page.rev-page .rev-sec-card .rev-sec-h h6{
    margin:0;font-size:.82rem;font-weight:800;color:#0f172a;line-height:1.2;
    text-transform:none;letter-spacing:0
}
.pd-page.rev-page .rev-sec-card .sec-ico{
    width:26px;height:26px;border-radius:7px;display:inline-flex;align-items:center;justify-content:center;
    font-size:.78rem;flex-shrink:0
}
.pd-page.rev-page .rev-sec-card .sec-ico.is-green{background:#dcfce7;color:#16a34a}
.pd-page.rev-page .rev-sec-card .sec-ico.is-blue{background:#dbeafe;color:#2563eb}
.pd-page.rev-page .rev-sec-card .sec-ico.is-amber{background:#ffedd5;color:#ea580c}
.pd-page.rev-page .rev-sec-card .sec-ico.is-violet{background:#ede9fe;color:#7c3aed}
.pd-page.rev-page .rev-sec-card .rev-ref-pill{
    display:inline-flex;align-items:center;padding:3px 8px;border-radius:999px;background:#ecfdf5;color:#059669;
    font-size:.7rem;font-weight:800;letter-spacing:.01em;white-space:nowrap;flex-shrink:0
}
.pd-page.rev-page .rev-dl{
    display:grid;grid-template-columns:minmax(108px,42%) minmax(0,1fr);gap:6px 8px;
    font-size:.78rem;align-items:start;margin:0
}
.pd-page.rev-page .rev-dl dt{margin:0;color:#475569;font-weight:600}
.pd-page.rev-page .rev-dl dt::after{content:':'}
.pd-page.rev-page .rev-dl dd{margin:0;color:#0f172a;font-weight:700;word-break:break-word}
.pd-page.rev-page .rev-check{
    display:flex;align-items:center;gap:8px;margin-top:10px;padding:8px 10px;border-radius:8px;
    background:#ecfdf5;color:#059669;font-size:.76rem;font-weight:700
}
.pd-page.rev-page .rev-audit{list-style:none;margin:0;padding:0}
.pd-page.rev-page .rev-audit li{position:relative;padding:0 0 12px 14px;border-left:2px solid #86efac;font-size:.76rem}
.pd-page.rev-page .rev-audit li:last-child{padding-bottom:0}
.pd-page.rev-page .rev-audit li::before{
    content:'';position:absolute;left:-5px;top:4px;width:8px;height:8px;border-radius:50%;background:#16a34a;border:2px solid #fff;box-shadow:0 0 0 1px #86efac
}
.pd-page.rev-page .rev-audit li strong{display:block;color:#0f172a;font-weight:700}
.pd-page.rev-page .rev-drawer-f{
    display:flex;flex-wrap:wrap;gap:8px;padding:0;background:transparent;border:0
}
.pd-page.rev-page .rev-drawer-f .rev-foot-btn{
    flex:1 1 auto;min-width:0;display:inline-flex;align-items:center;justify-content:center;gap:6px;
    padding:8px 10px;border-radius:8px;border:1px solid #cbd5e1;background:#fff;color:#1e40af;
    font-size:.72rem;font-weight:700;text-decoration:none;white-space:nowrap;
    box-shadow:0 1px 2px rgba(15,23,42,.04)
}
.pd-page.rev-page .rev-drawer-f .rev-foot-btn:hover{background:#f8fafc;border-color:#94a3b8;color:#1e3a8a}
.pd-page.rev-page .rev-drawer-empty{
    text-align:center;padding:2rem 1rem;color:#94a3b8;
    background:#fff;border:1px solid #e2e8f0;border-radius:10px;
    box-shadow:0 1px 2px rgba(15,23,42,.04),0 4px 12px rgba(148,163,184,.08)
}
.pd-page.rev-page .rev-drawer-empty i{font-size:1.6rem;display:block;margin-bottom:.5rem;opacity:.55}

.pd-page.rev-page .rev-attr-grid{
    display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;align-items:start
}
@media (max-width:991.98px){.pd-page.rev-page .rev-attr-grid{grid-template-columns:1fr}}
.pd-page.rev-page .rev-attr-grid .pd-card{
    margin:0;display:flex;flex-direction:column;
    border-radius:10px;border:1px solid #e2e8f0;
    box-shadow:0 1px 2px rgba(15,23,42,.04),0 4px 12px rgba(148,163,184,.08);
    overflow:hidden;background:#fff
}
.pd-page.rev-page .rev-attr-grid .pd-card-head{
    padding:10px 12px;border-bottom:1px solid #eef2f7;flex-shrink:0;background:#fff
}
.pd-page.rev-page .rev-attr-grid .pd-table-wrap{overflow:auto}
.pd-page.rev-page .rev-attr-table{font-size:.78rem;width:100%;margin:0}
.pd-page.rev-page .rev-attr-table thead th{
    background:#ebf2ff;color:#0f172a;font-size:.68rem;font-weight:700;
    text-transform:none;letter-spacing:0;border-bottom:1px solid #dbe3f3;
    padding:.45rem .65rem;white-space:nowrap
}
.pd-page.rev-page .rev-attr-table tbody td{
    padding:.42rem .65rem;font-size:.78rem;color:#0f172a;border-bottom:1px solid #eef2f7;vertical-align:middle;line-height:1.25
}
.pd-page.rev-page .rev-attr-table tbody tr:last-child td{border-bottom:0}
.pd-page.rev-page .rev-attr-table .rev-num{width:28px;color:#64748b;font-weight:700;font-variant-numeric:tabular-nums}
.pd-page.rev-page .rev-attr-table .rev-attr-label{font-weight:700;color:#0f172a;white-space:nowrap}
.pd-page.rev-page .rev-attr-table .text-end{font-variant-numeric:tabular-nums;white-space:nowrap}
.pd-page.rev-page .modal .form-label{font-size:.78rem;font-weight:700;color:#475569}
.pd-page.rev-page .pd-hero-actions .dropdown-menu{min-width:168px;border:1px solid #e2e8f0;border-radius:12px;padding:6px}
.pd-page.rev-page .pd-hero-actions .dropdown-item{border-radius:8px;font-size:.84rem;font-weight:600;padding:.45rem .7rem}

/* Adjust / Reverse Entry modal */
#revAdjustModal .modal-content{border:0;border-radius:16px;overflow:hidden;box-shadow:0 20px 48px rgba(15,23,42,.18)}
#revAdjustModal .modal-header{
    display:flex;align-items:flex-start;gap:12px;padding:16px 18px;border-bottom:1px solid #eef2f7;background:#fff
}
#revAdjustModal .rev-adj-hero{display:flex;align-items:flex-start;gap:12px;min-width:0;flex:1}
#revAdjustModal .rev-adj-hero-ico{
    width:40px;height:40px;border-radius:12px;background:#ecfdf5;color:#16a34a;
    display:inline-flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0
}
#revAdjustModal[data-active-mode="reversal"] .rev-adj-hero-ico{background:#fff1f2;color:#e11d48}
#revAdjustModal .rev-adj-hero h5{margin:0;font-size:1.05rem;font-weight:800;color:#0f172a}
#revAdjustModal .rev-adj-hero p{margin:3px 0 0;font-size:.76rem;color:#64748b;line-height:1.35}
#revAdjustModal .modal-body{padding:16px 18px;background:#fff}
#revAdjustModal .rev-adj-grid{display:grid;grid-template-columns:1.4fr .9fr;gap:12px 14px}
@media (max-width:575.98px){#revAdjustModal .rev-adj-grid{grid-template-columns:1fr}}
#revAdjustModal .rev-adj-field{min-width:0;display:flex;flex-direction:column;gap:4px}
#revAdjustModal .rev-adj-field.span-2{grid-column:1 / -1}
#revAdjustModal .rev-adj-field label{
    margin:0;font-size:.72rem;font-weight:700;color:#334155;line-height:1.2
}
#revAdjustModal .rev-adj-field label .req{color:#dc2626;margin-left:1px}
#revAdjustModal .rev-adj-field .form-control,
#revAdjustModal .rev-adj-field .form-select{
    height:40px;min-height:40px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;
    font-size:.84rem;color:#0f172a;box-shadow:none;padding:.4rem .75rem
}
#revAdjustModal .rev-adj-field .form-control:focus,
#revAdjustModal .rev-adj-field .form-select:focus{
    border-color:#86efac;box-shadow:0 0 0 3px rgba(22,163,74,.12)
}
#revAdjustModal .rev-adj-field .form-control:disabled,
#revAdjustModal .rev-adj-field .form-control[readonly]{
    background:#f8fafc;color:#64748b
}
#revAdjustModal .rev-adj-field .form-text{margin:2px 0 0;font-size:.68rem;color:#64748b}
#revAdjustModal .modal-footer{
    display:flex !important;align-items:center;justify-content:flex-end;gap:10px;flex-wrap:wrap;
    padding:12px 18px;border-top:1px solid #eef2f7;background:#f8fafc;
    grid-template-columns:unset !important
}
#revAdjustModal .modal-footer > *{width:auto !important;min-height:0 !important;margin:0}
#revAdjustModal .rev-adj-btn{
    height:40px;padding:0 16px;border-radius:10px;font-size:.84rem;font-weight:700;
    display:inline-flex;align-items:center;justify-content:center;gap:7px;cursor:pointer;
    border:1.5px solid transparent;white-space:nowrap;transition:background .15s,border-color .15s,color .15s
}
#revAdjustModal .rev-adj-btn.is-ghost{background:#fff;border-color:#cbd5e1;color:#334155}
#revAdjustModal .rev-adj-btn.is-ghost:hover{background:#f8fafc;border-color:#94a3b8;color:#0f172a}
#revAdjustModal .rev-adj-btn.is-primary{
    background:#16a34a;border-color:#16a34a;color:#fff;box-shadow:0 6px 14px rgba(22,163,74,.25)
}
#revAdjustModal .rev-adj-btn.is-primary:hover{background:#15803d;border-color:#15803d;color:#fff}
#revAdjustModal[data-active-mode="reversal"] .rev-adj-btn.is-primary{
    background:#e11d48;border-color:#e11d48;box-shadow:0 6px 14px rgba(225,29,72,.25)
}
#revAdjustModal[data-active-mode="reversal"] .rev-adj-btn.is-primary:hover{background:#be123c;border-color:#be123c}
#revAdjustModal .select2-container{width:100% !important}
#revAdjustModal .select2-container--default .select2-selection--single{
    height:40px !important;border:1px solid #e2e8f0 !important;border-radius:10px !important
}
#revAdjustModal .select2-container--default .select2-selection--single .select2-selection__rendered{
    line-height:38px !important;padding-left:.75rem !important;font-size:.84rem !important;color:#0f172a !important
}
#revAdjustModal .select2-container--default .select2-selection--single .select2-selection__arrow{height:38px !important}
</style>
@endpush

@section('content')
@php
    $periodLabel = trim(($f['date_from'] ?? '') && ($f['date_to'] ?? '')
        ? (\Carbon\Carbon::parse($f['date_from'])->format('d M Y').' – '.\Carbon\Carbon::parse($f['date_to'])->format('d M Y'))
        : ($dateRangeLabel ?? ''));
    $topAttr = [];
    foreach ([
        'employee' => 'Employee (Top)',
        'branch' => 'Branch (Top)',
        'cluster' => 'Cluster (Top)',
        'region' => 'Region (Top)',
    ] as $key => $label) {
        $row = collect($attr[$key] ?? [])
            ->sortByDesc(fn ($r) => abs((float) ($r['net'] ?? $r['recognized'] ?? 0)))
            ->first();
        $topAttr[] = [
            'attribute' => $label,
            'name' => $row['label'] ?? '—',
            'count' => (int) ($row['entries'] ?? $row['disbursements'] ?? $row['count'] ?? 0),
            'disb' => (float) ($row['disbursed'] ?? 0),
            // Net posted revenue (credits + adjustments + reversals) for the period.
            'recognized' => (float) ($row['net'] ?? $row['recognized'] ?? 0),
        ];
    }
    $orgTree = [
        'clusters' => collect($opts['clusters'] ?? [])->map(static function ($u) {
            return [
                'id' => (string) $u->id,
                'name' => $u->name,
                'parent_id' => $u->parent_id !== null ? (string) $u->parent_id : '',
            ];
        })->values()->all(),
        'branches' => collect($opts['branches'] ?? [])->map(static function ($u) {
            return [
                'id' => (string) $u->id,
                'name' => $u->name,
                'parent_id' => $u->parent_id !== null ? (string) $u->parent_id : '',
            ];
        })->values()->all(),
        'owners' => collect($opts['owners'] ?? [])->map(static function ($o) {
            $role = (string) ($o->role ?? '');
            $name = (string) $o->name;
            $label = (string) ($o->label ?? ($role !== '' ? $name.' ('.$role.')' : $name));

            return [
                'id' => (string) $o->id,
                'name' => $label,
                'role' => $role,
                'region_id' => isset($o->region_id) && $o->region_id !== null ? (string) $o->region_id : '',
                'cluster_id' => isset($o->cluster_id) && $o->cluster_id !== null ? (string) $o->cluster_id : '',
                'branch_id' => isset($o->branch_id) && $o->branch_id !== null ? (string) $o->branch_id : '',
            ];
        })->values()->all(),
    ];
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
            <button type="button" class="pd-btn" data-bs-toggle="modal" data-bs-target="#revExportModal">
                <i class="bi bi-download"></i> Export Reports
            </button>
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
                            <select name="region" id="revRegion" class="form-select js-rev-org" data-allow-clear="true" data-search="true" data-placeholder="All Regions">
                                <option value=""></option>
                                @foreach(($opts['regions'] ?? []) as $u)
                                    <option value="{{ $u->id }}" @selected((string)($f['region'] ?? '') === (string)$u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rf-field">
                            <label for="revCluster">Cluster</label>
                            <select name="cluster" id="revCluster" class="form-select js-rev-org" data-allow-clear="true" data-search="true" data-placeholder="All Clusters">
                                <option value=""></option>
                                @foreach(($opts['clusters'] ?? []) as $u)
                                    <option value="{{ $u->id }}"
                                        data-parent="{{ $u->parent_id }}"
                                        @selected((string)($f['cluster'] ?? '') === (string)$u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rf-field">
                            <label for="revBranch">Branch</label>
                            <select name="branch" id="revBranch" class="form-select js-rev-org" data-allow-clear="true" data-search="true" data-placeholder="All Branches">
                                <option value=""></option>
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
                            <select name="owner" id="revOwner" class="form-select" data-allow-clear="true" data-search="true" data-placeholder="All Employees">
                                <option value=""></option>
                                @foreach(($opts['owners'] ?? []) as $o)
                                    <option value="{{ $o->id }}"
                                        data-region="{{ $o->region_id ?? '' }}"
                                        data-cluster="{{ $o->cluster_id ?? '' }}"
                                        data-branch="{{ $o->branch_id ?? '' }}"
                                        @selected((string)($f['owner'] ?? '') === (string)$o->id)>{{ $o->label ?? ($o->name.(!empty($o->role) ? ' ('.$o->role.')' : '')) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rf-field">
                            <label for="revProduct">Product</label>
                            <select name="product" id="revProduct" class="form-select" data-allow-clear="true" data-search="true" data-placeholder="All Products">
                                <option value=""></option>
                                @foreach(($opts['products'] ?? []) as $p)
                                    <option value="{{ $p }}" @selected(($f['product'] ?? '') === $p)>{{ $p }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rf-field">
                            <label for="revSource">Source</label>
                            <select name="source" id="revSource" class="form-select" data-allow-clear="true" data-search="true" data-placeholder="All Sources">
                                <option value=""></option>
                                @foreach(($opts['sources'] ?? []) as $s)
                                    <option value="{{ $s }}" @selected(($f['source'] ?? '') === $s)>{{ $s }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rf-field">
                            <label for="revType">Entry Type</label>
                            <select name="entry_type" id="revType" class="form-select" data-allow-clear="true" data-search="true" data-placeholder="All Types">
                                <option value=""></option>
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
            <div class="rev-main-head">
                <div class="rev-tabs">
                    <a class="rev-tab {{ $activeTab === 'all' ? 'active' : '' }}" href="{{ route('goal.revenue-ledger', $tabQs('all')) }}">All Entries <span class="n">{{ number_format($tabs['all'] ?? 0) }}</span></a>
                    <a class="rev-tab {{ $activeTab === 'recognized' ? 'active' : '' }}" href="{{ route('goal.revenue-ledger', $tabQs('recognized')) }}">Revenue Credits <span class="n">{{ number_format($tabs['recognized'] ?? 0) }}</span></a>
                    <a class="rev-tab {{ $activeTab === 'adjustment' ? 'active' : '' }}" href="{{ route('goal.revenue-ledger', $tabQs('adjustment')) }}">Adjustments <span class="n">{{ number_format($tabs['adjustment'] ?? 0) }}</span></a>
                    <a class="rev-tab {{ $activeTab === 'reversal' ? 'active' : '' }}" href="{{ route('goal.revenue-ledger', $tabQs('reversal')) }}">Reversals <span class="n">{{ number_format($tabs['reversal'] ?? 0) }}</span></a>
                </div>
                <div class="rev-toolbar">
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
                            <th>Actions</th>
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
                            <td data-label="Actions" class="pd-col-actions">
                                <div class="rev-row-actions">
                                    <button type="button" class="btn-view js-rev-view" data-id="{{ $e->id }}" title="View details" data-no-loader>
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <div class="dropdown">
                                        <button type="button" class="btn-view" data-bs-toggle="dropdown" aria-expanded="false" title="More" data-no-loader>
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end rev-action-menu">
                                            <li>
                                                <button type="button" class="dropdown-item js-rev-view" data-id="{{ $e->id }}" data-no-loader>
                                                    <i class="bi bi-eye me-2"></i> View details
                                                </button>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('goal.cases.show', $e->goal_loan_case_id) }}">
                                                    <i class="bi bi-folder2-open me-2"></i> View case
                                                </a>
                                            </li>
                                            @if($canAdjust && ($e->entry_type === 'recognized' || $e->entry_type === '' || $e->entry_type === null))
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#revAdjustModal" data-mode="reversal" data-parent-id="{{ $e->id }}" data-no-loader>
                                                        <i class="bi bi-arrow-counterclockwise me-2"></i> Reverse entry
                                                    </button>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
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
            <div class="pd-foot">
                <span>
                    @if($entries->total() > 0)
                        Showing {{ $entries->firstItem() }} to {{ $entries->lastItem() }} of {{ number_format($entries->total()) }} entries
                    @else
                        Showing 0 entries · Net {{ $fmtInr($kpis['net'] ?? 0) }}
                    @endif
                </span>
                <div>@if($entries->hasPages()){{ $entries->links() }}@endif</div>
            </div>
            </div>

            <div class="rev-attr-grid">
                <div class="pd-card">
                    <div class="pd-card-head">
                        <div class="rev-attr-head">
                            <div class="rev-attr-title">
                                <span class="rev-attr-ico" aria-hidden="true"><i class="bi bi-grid-fill"></i></span>
                                <div class="rev-attr-title-text">
                                    <h5>Revenue Attribution</h5>
                                    <div class="pd-card-sub">
                                        <span>(Period: {{ $periodLabel ?: 'Selected range' }})</span>
                                        <button type="button" class="rev-attr-info" data-bs-toggle="modal" data-bs-target="#revPolicyModal" title="Revenue policy" aria-label="Revenue policy">
                                            <i class="bi bi-info"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="pd-table-wrap">
                        <table class="pd-table rev-attr-table">
                            <thead>
                                <tr>
                                    <th class="rev-num">#</th>
                                    <th>Attribute</th>
                                    <th>Count</th>
                                    <th class="text-end">Disbursement Amount (₹)</th>
                                    <th class="text-end">Recognized Revenue (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topAttr as $i => $row)
                                    <tr>
                                        <td class="rev-num">{{ $i + 1 }}</td>
                                        <td class="rev-attr-label">{{ $row['attribute'] }} – {{ $row['name'] }}</td>
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
                            <div class="rev-attr-title">
                                <span class="rev-attr-ico" aria-hidden="true"><i class="bi bi-sliders"></i></span>
                                <div class="rev-attr-title-text">
                                    <h5>Recent Adjustments &amp; Reversals</h5>
                                    <div class="pd-card-sub">(Last 5 · both types)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="pd-table-wrap">
                        <table class="pd-table rev-attr-table">
                            <thead>
                                <tr>
                                    <th class="rev-num">#</th>
                                    <th>Entry ID</th>
                                    <th>Type</th>
                                    <th>Case Ref</th>
                                    <th class="text-end">Amount (₹)</th>
                                    <th>Reason</th>
                                    <th>Approved By</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($recent_adjustments ?? []) as $i => $ra)
                                    @php
                                        $raType = (string) ($ra->entry_type ?: 'adjustment');
                                        $raIsRev = $raType === 'reversal';
                                    @endphp
                                    <tr>
                                        <td class="rev-num">{{ $i + 1 }}</td>
                                        <td><button type="button" class="rev-ref is-link btn btn-link p-0 js-rev-view" data-id="{{ $ra->id }}">{{ $ra->entry_ref ?: ('RV-'.$ra->id) }}</button></td>
                                        <td>
                                            <span class="rev-type {{ $raIsRev ? 'is-reversal' : 'is-adjustment' }}">
                                                {{ $raIsRev ? 'Reversal' : 'Adjustment' }}
                                            </span>
                                        </td>
                                        <td>{{ $ra->loanCase?->case_no ?: '—' }}</td>
                                        <td class="text-end {{ (float)$ra->amount < 0 ? 'pd-amt is-neg' : '' }}">{{ $fmtInr($ra->amount) }}</td>
                                        <td>{{ \Illuminate\Support\Str::limit($ra->reason ?: $ra->note, 40) }}</td>
                                        <td>{{ $ra->approver?->name ?: $ra->creator?->name ?: '—' }}</td>
                                        <td>{{ optional($ra->posted_at ?? $ra->created_at)->format('d M Y') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center text-muted py-3">No adjustments or reversals posted yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <aside class="rev-right">
            <div class="rev-drawer" id="revDrawer" aria-live="polite">
                <div class="rev-drawer-h" hidden>
                    <span id="rdRef">Select an entry</span>
                    <span id="rdStatus" hidden>Posted</span>
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
<div class="modal fade" id="revAdjustModal" tabindex="-1" aria-labelledby="revAdjustModalLabel" aria-hidden="true" data-active-mode="adjustment">
    <div class="modal-dialog modal-dialog-centered" style="max-width:560px">
        <form method="POST" action="{{ route('goal.revenue-ledger.adjust') }}" class="modal-content">
            @csrf
            <input type="hidden" name="entry_type" id="adjEntryType" value="adjustment">
            <div class="modal-header">
                <div class="rev-adj-hero">
                    <span class="rev-adj-hero-ico" id="adjHeroIco" aria-hidden="true"><i class="bi bi-plus-lg"></i></span>
                    <div>
                        <h5 id="revAdjustModalLabel">Add Adjustment</h5>
                        <p id="adjHelp">Creates a separate ledger entry. The original revenue credit is never overwritten.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="rev-adj-grid">
                    <div class="rev-adj-field span-2">
                        <label for="adjParent">Original Entry <span class="req">*</span></label>
                        <select name="parent_entry_id" id="adjParent" class="form-select" required data-search="true" data-allow-clear="true" data-placeholder="Select recognized credit…">
                            <option value=""></option>
                            @foreach(($credit_options ?? []) as $c)
                                <option value="{{ $c->id }}"
                                    data-case="{{ $c->loanCase?->case_no }}"
                                    data-remaining="{{ (float) $c->amount }}">
                                    {{ $c->entry_ref ?: ('RV-'.$c->id) }} · {{ $c->loanCase?->case_no }} · {{ $fmtInr($c->amount) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rev-adj-field">
                        <label for="adjCaseRef">Case Reference</label>
                        <input type="text" class="form-control" id="adjCaseRef" readonly placeholder="Auto from entry">
                    </div>
                    <div class="rev-adj-field" id="adjDirWrap">
                        <label for="adjDir">Adjustment Type</label>
                        <select name="adjustment_direction" id="adjDir" class="form-select" data-search="false" data-allow-clear="false">
                            <option value="increase">Increase</option>
                            <option value="decrease">Decrease</option>
                        </select>
                    </div>
                    <div class="rev-adj-field">
                        <label for="adjAmt">Amount (₹) <span class="req">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="adjAmt" class="form-control" required placeholder="0.00">
                        <div class="form-text" id="adjRemainHint"></div>
                    </div>
                    <div class="rev-adj-field">
                        <label for="adjDate">Date</label>
                        <input type="text" class="form-control js-datepicker" id="adjDate" value="{{ now()->toDateString() }}" disabled>
                    </div>
                    <div class="rev-adj-field span-2">
                        <label for="adjNote">Reason <span class="req">*</span></label>
                        <input type="text" name="note" id="adjNote" class="form-control" required maxlength="500" placeholder="Mandatory explanation for audit">
                    </div>
                    <div class="rev-adj-field span-2">
                        <label for="adjRemarks">Remarks</label>
                        <input type="text" name="remarks" id="adjRemarks" class="form-control" maxlength="500" placeholder="Optional notes">
                    </div>
                </div>
                @error('amount')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                @error('note')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                @error('parent_entry_id')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div>
            <div class="modal-footer">
                <button type="button" class="rev-adj-btn is-ghost" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="rev-adj-btn is-primary" id="adjSubmitBtn"><i class="bi bi-check2-circle"></i> Post Adjustment</button>
            </div>
        </form>
    </div>
</div>
@endif

@include('goal.mis.partials.revenue_export_modal')
@endsection

@push('scripts')
<script>
(function () {
    const fyRanges = @json($fy_range_map ?? []);
    const entryUrlTpl = @json(route('goal.revenue-ledger.entry', ['entry' => '__ID__']));
    const exportPreviewUrl = @json(route('goal.revenue-ledger.export.preview'));
    const exportDownloadUrl = @json(route('goal.revenue-ledger.export'));
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

    // Region → Cluster → Branch cascade (Select2-safe)
    (function () {
        const $ = window.jQuery;
        const regionEl = document.getElementById('revRegion');
        const clusterEl = document.getElementById('revCluster');
        const branchEl = document.getElementById('revBranch');
        const ownerEl = document.getElementById('revOwner');
        if (!regionEl || !clusterEl || !branchEl) return;

        const orgTree = @json($orgTree);
        if (!orgTree.owners) orgTree.owners = [];

        function reinitsSelect2(el) {
            if (!$ || !$.fn || !$.fn.select2) return;
            const $el = $(el);
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
            // Keep searchable + clear (X) after cascade rebuilds options
            $el.attr('data-allow-clear', 'true');
            $el.attr('data-search', 'true');
            if (window.IsarvaSelect2 && typeof window.IsarvaSelect2.initOne === 'function') {
                window.IsarvaSelect2.initOne(el);
            } else {
                $el.select2({
                    width: '100%',
                    allowClear: true,
                    placeholder: $el.data('placeholder') || '',
                    minimumResultsForSearch: 0
                });
            }
        }

        function setOptions(select, items, selected, extraAttrs) {
            const keep = selected == null ? '' : String(selected);
            const html = ['<option value=""></option>']; // empty = Select2 placeholder + clear target
            items.forEach(function (item) {
                const sel = String(item.id) === keep ? ' selected' : '';
                let attrs = ' data-parent="' + (item.parent_id || '') + '"';
                if (extraAttrs) {
                    attrs +=
                        ' data-region="' + (item.region_id || '') + '"' +
                        ' data-cluster="' + (item.cluster_id || '') + '"' +
                        ' data-branch="' + (item.branch_id || '') + '"';
                }
                html.push(
                    '<option value="' + item.id + '"' + attrs + sel + '>' +
                    String(item.name).replace(/</g, '&lt;') + '</option>'
                );
            });
            select.innerHTML = html.join('');
            if (keep && select.querySelector('option[value="' + keep.replace(/"/g, '\\"') + '"]')) {
                select.value = keep;
            } else {
                select.value = '';
            }
            reinitsSelect2(select);
        }

        function clustersForRegion(regionId) {
            if (!regionId) return orgTree.clusters.slice();
            return orgTree.clusters.filter(function (c) { return String(c.parent_id) === String(regionId); });
        }

        function branchesFor(regionId, clusterId) {
            if (clusterId) {
                return orgTree.branches.filter(function (b) { return String(b.parent_id) === String(clusterId); });
            }
            if (regionId) {
                const clusterIds = clustersForRegion(regionId).map(function (c) { return String(c.id); });
                return orgTree.branches.filter(function (b) { return clusterIds.indexOf(String(b.parent_id)) !== -1; });
            }
            return orgTree.branches.slice();
        }

        function ownersFor(regionId, clusterId, branchId) {
            return orgTree.owners.filter(function (o) {
                if (branchId) return String(o.branch_id) === String(branchId);
                if (clusterId) return String(o.cluster_id) === String(clusterId);
                if (regionId) return String(o.region_id) === String(regionId);
                return true;
            });
        }

        function syncOwners(resetOwner) {
            if (!ownerEl) return;
            const regionId = regionEl.value || '';
            const clusterId = clusterEl.value || '';
            const branchId = branchEl.value || '';
            const ownerKeep = resetOwner ? '' : (ownerEl.value || '');
            const valid = ownersFor(regionId, clusterId, branchId);
            const keep = valid.some(function (o) { return String(o.id) === String(ownerKeep); }) ? ownerKeep : '';
            setOptions(ownerEl, valid, keep, true);
        }

        function syncFromRegion(resetDownstream) {
            const regionId = regionEl.value || '';
            const clusterKeep = resetDownstream ? '' : (clusterEl.value || '');
            const validClusters = clustersForRegion(regionId);
            const keepCluster = validClusters.some(function (c) { return String(c.id) === String(clusterKeep); }) ? clusterKeep : '';
            setOptions(clusterEl, validClusters, keepCluster, false);
            syncFromCluster(true);
        }

        function syncFromCluster(resetBranch) {
            const regionId = regionEl.value || '';
            const clusterId = clusterEl.value || '';
            const branchKeep = resetBranch ? '' : (branchEl.value || '');
            const validBranches = branchesFor(regionId, clusterId);
            const keepBranch = validBranches.some(function (b) { return String(b.id) === String(branchKeep); }) ? branchKeep : '';
            setOptions(branchEl, validBranches, keepBranch, false);
            syncOwners(!!resetBranch);
        }

        function bind() {
            if ($) {
                $(regionEl).off('.revCascade').on('change.revCascade select2:select.revCascade select2:clear.revCascade', function () {
                    syncFromRegion(true);
                });
                $(clusterEl).off('.revCascade').on('change.revCascade select2:select.revCascade select2:clear.revCascade', function () {
                    syncFromCluster(true);
                });
                $(branchEl).off('.revCascade').on('change.revCascade select2:select.revCascade select2:clear.revCascade', function () {
                    syncOwners(true);
                });
            } else {
                regionEl.addEventListener('change', function () { syncFromRegion(true); });
                clusterEl.addEventListener('change', function () { syncFromCluster(true); });
                branchEl.addEventListener('change', function () { syncOwners(true); });
            }
        }

        function boot() {
            bind();
            // Normalize lists for any preselected region/cluster without wiping selection.
            const regionId = regionEl.value || '';
            const clusterId = clusterEl.value || '';
            const branchId = branchEl.value || '';
            const ownerId = ownerEl ? (ownerEl.value || '') : '';
            // First paint: ensure placeholders are empty options so clear (X) works
            if (regionEl.querySelector('option[value=""]')) {
                regionEl.querySelector('option[value=""]').textContent = '';
            }
            setOptions(clusterEl, clustersForRegion(regionId), clusterId, false);
            setOptions(branchEl, branchesFor(regionId, clusterEl.value || ''), branchId, false);
            if (ownerEl) setOptions(ownerEl, ownersFor(regionId, clusterEl.value || '', branchEl.value || ''), ownerId, true);
            reinitsSelect2(regionEl);
            bind(); // re-bind after Select2 re-init
        }

        if ($) {
            $(boot);
        } else {
            boot();
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

    function flashDrawer() {
        const pane = document.querySelector('.rev-right');
        if (!pane) return;
        pane.classList.add('is-flash');
        try { pane.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' }); } catch (e) {}
        window.setTimeout(function () { pane.classList.remove('is-flash'); }, 900);
    }

    function loadEntry(id, row) {
        if (!id || !rdBody) return;
        document.querySelectorAll('#revLedgerTable tr.is-selected').forEach(function (tr) { tr.classList.remove('is-selected'); });
        if (row) row.classList.add('is-selected');
        rdBody.innerHTML = '<div class="rev-drawer-empty"><i class="bi bi-hourglass-split"></i>Loading entry…</div>';
        if (rdStatus) rdStatus.hidden = true;
        if (rdFooter) rdFooter.hidden = true;
        flashDrawer();
        const url = String(entryUrlTpl).replace('__ID__', encodeURIComponent(id));
        fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (r) {
            if (!r.ok) {
                return r.text().then(function () {
                    throw new Error('HTTP ' + r.status);
                });
            }
            return r.json();
        }).then(function (j) {
            if (j && j.entry) renderDetail(j.entry);
            else rdBody.innerHTML = '<div class="text-danger small">Unable to load entry.</div>';
        }).catch(function (err) {
            rdBody.innerHTML = '<div class="text-danger small">Unable to load entry' +
                (err && err.message ? ' (' + esc(err.message) + ')' : '') + '.</div>';
        });
    }

    function secHead(icoClass, ico, title, trailing) {
        return '<div class="rev-sec-h"><div class="rev-sec-h-left">' +
            '<span class="sec-ico ' + icoClass + '"><i class="bi ' + ico + '"></i></span>' +
            '<h6>' + title + '</h6></div>' + (trailing || '') + '</div>';
    }

    function renderDetail(entry) {
        if (!entry) return;
        const refText = entry.entry_ref || '—';
        if (rdRef) rdRef.textContent = refText;
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
        const refPill = '<span class="rev-ref-pill">' + esc(refText) + '</span>';

        rdBody.innerHTML =
            '<div class="rev-sec-card">' + secHead('is-green', 'bi-journal-text', 'Ledger Entry Details', refPill) +
            '<dl class="rev-dl">' +
            '<dt>Case Reference</dt><dd>' + esc(c.ref || '—') + '</dd>' +
            '<dt>Customer</dt><dd>' + esc(c.customer || '—') + '</dd>' +
            '<dt>Product</dt><dd>' + esc(c.product || '—') + '</dd>' +
            '<dt>Branch</dt><dd>' + esc(c.branch || '—') + '</dd>' +
            '<dt>Disbursement Ref</dt><dd>' + esc(d.ref || '—') + '</dd>' +
            '<dt>Disbursement Date</dt><dd>' + esc(d.date || '—') + '</dd>' +
            '<dt>Entry Type</dt><dd>' + typeBadge + '</dd>' +
            '<dt>Status</dt><dd>' + statusBadge + '</dd></dl></div>' +

            '<div class="rev-sec-card">' + secHead('is-green', 'bi-check2-circle', 'Verified Disbursement Details') +
            '<dl class="rev-dl">' +
            '<dt>Disbursed Amount</dt><dd>' + (d.amount == null || d.amount === '' ? '—' : money(d.amount)) + '</dd>' +
            '<dt>Verified On</dt><dd>' + esc(d.verified_on || d.date || '—') + '</dd>' +
            '<dt>Verified By</dt><dd>' + esc(d.verified_by || '—') + '</dd></dl></div>' +

            '<div class="rev-sec-card">' + secHead('is-amber', 'bi-file-earmark-text', 'Commercial &amp; Revenue Basis') +
            '<dl class="rev-dl">' +
            '<dt>Commercial Policy Ref</dt><dd>' + esc(r.policy || '—') + '</dd>' +
            '<dt>Revenue Basis</dt><dd>' + esc(r.basis || '—') + '</dd>' +
            '<dt>Remarks</dt><dd>' + esc(r.remarks || entry.reason || '—') + '</dd></dl></div>' +

            '<div class="rev-sec-card">' + secHead('is-violet', 'bi-people', 'Revenue Attribution') +
            '<dl class="rev-dl">' +
            '<dt>Revenue Owner</dt><dd>' + esc(a.owner || '—') + '</dd>' +
            '<dt>Attribution</dt><dd>' + esc(a.mode || '—') + '</dd>' +
            '<dt>Cluster</dt><dd>' + esc(a.cluster || '—') + '</dd>' +
            '<dt>Region</dt><dd>' + esc(a.region || '—') + '</dd></dl></div>' +

            '<div class="rev-sec-card">' + secHead('is-green', 'bi-bullseye', 'Linked Target Achievement') +
            '<dl class="rev-dl">' +
            '<dt>Target Period</dt><dd>' + esc(t.period || '—') + '</dd>' +
            '<dt>Revenue Contribution</dt><dd>' + money(t.contribution) + '</dd>' +
            '<dt>Target Credited To</dt><dd>' + esc(t.credited_to || a.owner || '—') + '</dd></dl>' +
            (t.included
                ? '<div class="rev-check"><i class="bi bi-check-circle-fill"></i> Included in target achievement</div>'
                : '<div class="rev-muted mt-2" style="font-size:.76rem">Pending inclusion in target achievement</div>') +
            '</div>' +

            '<div class="rev-sec-card">' + secHead('is-blue', 'bi-clock-history', 'Audit Timeline') +
            (audits.length
                ? ('<ul class="rev-audit">' + audits.map(function (x) {
                    return '<li><strong>' + esc(x.event || '') + '</strong><div class="rev-muted">by ' + esc(x.actor || 'System') + ' | ' + esc(x.when || '') + '</div>' +
                        (x.body ? ('<div class="rev-muted">' + esc(x.body) + '</div>') : '') + '</li>';
                }).join('') + '</ul>')
                : '<div class="rev-muted">No audit events linked yet.</div>') +
            '</div>';

        let foot = '';
        if (c.url) foot += '<a class="rev-foot-btn" href="' + c.url + '"><i class="bi bi-eye"></i> View Case</a>';
        if (c.url) foot += '<a class="rev-foot-btn" href="' + c.url + '#tab-disbursement"><i class="bi bi-file-earmark-text"></i> View Disbursement</a>';
        if (c.url) foot += '<a class="rev-foot-btn" href="' + c.url + '#tab-audit"><i class="bi bi-list-ul"></i> View Audit</a>';
        rdFooter.innerHTML = foot;
        rdFooter.hidden = !foot;
    }

    // Event delegation so eye / menu actions always work (including dropdown items).
    document.addEventListener('click', function (ev) {
        const btn = ev.target && ev.target.closest ? ev.target.closest('.js-rev-view') : null;
        if (!btn) return;
        ev.preventDefault();
        const id = btn.getAttribute('data-id');
        const row = btn.closest('tr');
        // Close any open bootstrap dropdown
        if (window.bootstrap && btn.closest('.dropdown-menu')) {
            const toggle = btn.closest('.dropdown')?.querySelector('[data-bs-toggle="dropdown"]');
            if (toggle) {
                const inst = bootstrap.Dropdown.getInstance(toggle);
                if (inst) inst.hide();
            }
        }
        loadEntry(id, row);
    });

    // Auto-open first ledger row so the detail pane matches the mockup layout.
    const firstView = document.querySelector('#revLedgerTable .js-rev-view');
    if (firstView) {
        loadEntry(firstView.getAttribute('data-id'), firstView.closest('tr'));
    }

    const modal = document.getElementById('revAdjustModal');
    if (modal) {
        let pendingAdjustMode = 'adjustment';
        let pendingParentId = '';
        function applyAdjustModalMode(mode, parentId) {
            const resolved = mode === 'reversal' ? 'reversal' : 'adjustment';
            const isReversal = resolved === 'reversal';
            const typeEl = document.getElementById('adjEntryType');
            const title = document.getElementById('revAdjustModalLabel');
            const submit = document.getElementById('adjSubmitBtn');
            const dirWrap = document.getElementById('adjDirWrap');
            const help = document.getElementById('adjHelp');
            const parent = document.getElementById('adjParent');
            const heroIco = document.getElementById('adjHeroIco');
            if (typeEl) typeEl.value = resolved;
            if (title) title.textContent = isReversal ? 'Reverse Entry' : 'Add Adjustment';
            if (submit) {
                submit.innerHTML = isReversal
                    ? '<i class="bi bi-arrow-counterclockwise"></i> Post Reversal'
                    : '<i class="bi bi-check2-circle"></i> Post Adjustment';
            }
            if (dirWrap) dirWrap.style.display = isReversal ? 'none' : '';
            if (heroIco) {
                heroIco.innerHTML = isReversal
                    ? '<i class="bi bi-arrow-counterclockwise"></i>'
                    : '<i class="bi bi-plus-lg"></i>';
            }
            if (help) {
                help.textContent = isReversal
                    ? 'Creates a separate negative ledger entry linked to the original credit. Original amount is retained.'
                    : 'Creates a separate ledger entry. The original revenue credit is never overwritten.';
            }
            if (parent && parentId) {
                parent.value = parentId;
                parent.dispatchEvent(new Event('change', { bubbles: true }));
                if (window.jQuery && window.jQuery.fn.select2 && window.jQuery(parent).hasClass('select2-hidden-accessible')) {
                    window.jQuery(parent).val(parentId).trigger('change');
                }
            }
            modal.setAttribute('data-active-mode', resolved);
            pendingAdjustMode = resolved;
            pendingParentId = parentId || '';
        }
        function readModeFromTrigger(trigger) {
            const btn = trigger && trigger.closest
                ? (trigger.closest('[data-mode]') || trigger.closest('[data-bs-target="#revAdjustModal"]') || trigger)
                : null;
            if (!btn || !btn.getAttribute) {
                return { mode: pendingAdjustMode || 'adjustment', parentId: pendingParentId || '' };
            }
            return {
                mode: btn.getAttribute('data-mode') || pendingAdjustMode || 'adjustment',
                parentId: btn.getAttribute('data-parent-id') || ''
            };
        }
        // Capture BEFORE tw-ui opens the modal (relatedTarget is often null in the shim)
        document.addEventListener('click', function (e) {
            const t = e.target && e.target.closest
                ? e.target.closest('[data-bs-target="#revAdjustModal"][data-mode]')
                : null;
            if (!t) return;
            const info = readModeFromTrigger(t);
            pendingAdjustMode = info.mode;
            pendingParentId = info.parentId;
            applyAdjustModalMode(info.mode, info.parentId);
        }, true);
        modal.addEventListener('show.bs.modal', function (ev) {
            const info = readModeFromTrigger(ev.relatedTarget);
            applyAdjustModalMode(info.mode || pendingAdjustMode, info.parentId || pendingParentId);
        });
        modal.addEventListener('shown.bs.modal', function () {
            applyAdjustModalMode(pendingAdjustMode, pendingParentId);
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

    // —— Export Reports modal ——
    const exReport = document.getElementById('revExReport');
    const exFormat = document.getElementById('revExFormat');
    const exForm = document.getElementById('revExportForm');
    const exFrom = document.getElementById('revExFrom');
    const exTo = document.getElementById('revExTo');
    const exRange = document.getElementById('revExRange');
    const exMonth = document.getElementById('revExMonth');
    const exPeriod = document.getElementById('revExPeriod');
    const exFy = document.getElementById('revExFy');
    let exFpRange = null;
    const kpiLabels = {
        recognized: 'Recognized Revenue',
        verified_disb: 'Verified Disbursement',
        adjustments: 'Adjustments',
        reversals: 'Reversals',
        net: 'Net Revenue',
        entries: 'Entries',
        employees: 'Employees',
        revenue: 'Revenue',
        disbursed: 'Disbursed',
        branches: 'Branches',
        clusters: 'Clusters',
        regions: 'Regions',
        payin: 'PayIn',
        payout: 'Payout',
        count: 'Count',
        amount: 'Amount',
        targets: 'Targets',
        actual: 'Actual',
        disbursements: 'Disbursements',
        matched: 'Matched',
        missing: 'Missing',
        cases: 'Cases'
    };
    const moneyInr = function (n) {
        const v = Number(n);
        if (!isFinite(v)) return String(n == null ? '—' : n);
        return '₹ ' + v.toLocaleString('en-IN', { maximumFractionDigits: 0 });
    };
    function pad2(n) { return String(n).padStart(2, '0'); }
    function setExSelect($el, value) {
        if (!$el || !$el.length) return;
        $el.val(value == null ? '' : String(value)).trigger('change');
    }
    function syncExRangePicker(silent) {
        if (!exFpRange || !exFrom || !exTo) return;
        const a = exFrom.value || '';
        const b = exTo.value || '';
        try {
            if (a && b) exFpRange.setDate([a, b], !silent);
            else if (a) exFpRange.setDate([a], !silent);
            else exFpRange.clear(silent);
        } catch (e) {}
    }
    function syncExMonthPicker(silent) {
        if (!exMonth || !exMonth._flatpickr || !exMonth.value) return;
        try { exMonth._flatpickr.setDate(exMonth.value, !silent); } catch (e) {}
    }
    function setExPickerEnabled(fpOrEl, enabled) {
        const fp = fpOrEl && fpOrEl.config ? fpOrEl : (fpOrEl && fpOrEl._flatpickr ? fpOrEl._flatpickr : null);
        if (!fp) {
            if (fpOrEl && fpOrEl.tagName) {
                fpOrEl.disabled = !enabled;
                fpOrEl.readOnly = !enabled;
            }
            return;
        }
        fp.config.clickOpens = !!enabled;
        if (fp._input) {
            fp._input.disabled = !enabled;
            fp._input.readOnly = true;
        }
        if (fp.altInput) {
            fp.altInput.disabled = !enabled;
            fp.altInput.readOnly = true;
            fp.altInput.style.cursor = enabled ? '' : 'not-allowed';
        }
        if (!enabled) {
            try { fp.close(); } catch (e) {}
        }
    }
    function syncExPeriodFields() {
        const mode = exPeriod ? String(exPeriod.value || 'monthly') : 'monthly';
        const monthField = exMonth ? exMonth.closest('.rev-ex-field') : null;
        const rangeField = exRange ? exRange.closest('.rev-ex-field') : null;
        const monthOn = mode === 'monthly';
        const rangeOn = mode === 'custom';
        if (monthField) monthField.classList.toggle('is-locked', !monthOn);
        if (rangeField) rangeField.classList.toggle('is-locked', !rangeOn);
        setExPickerEnabled(exMonth, monthOn);
        setExPickerEnabled(exFpRange || exRange, rangeOn);
        const monthReq = monthField ? monthField.querySelector('label .req') : null;
        const rangeLabel = rangeField ? rangeField.querySelector('label') : null;
        if (monthReq) monthReq.style.display = monthOn ? '' : 'none';
        if (rangeLabel) {
            rangeLabel.innerHTML = rangeOn
                ? 'Date Range <span class="req">*</span>'
                : 'Date Range (Optional)';
        }
    }
    function applyExMonth() {
        if (!exMonth || !exMonth.value || !exFrom || !exTo) return;
        const parts = String(exMonth.value).split('-');
        if (parts.length < 2) return;
        const y = Number(parts[0]);
        const m = Number(parts[1]);
        if (!y || !m) return;
        const last = new Date(y, m, 0).getDate();
        exFrom.value = y + '-' + pad2(m) + '-01';
        exTo.value = y + '-' + pad2(m) + '-' + pad2(last);
        syncExRangePicker(true);
    }
    function applyExFy() {
        if (!exFy || !exFrom || !exTo) return;
        const range = fyRanges[exFy.value];
        if (!range) return;
        exFrom.value = range.date_from || range[0] || '';
        exTo.value = range.date_to || range[1] || '';
        if (exMonth && exFrom.value) {
            exMonth.value = exFrom.value.slice(0, 7);
            syncExMonthPicker(true);
        }
        syncExRangePicker(true);
    }
    function initExRangePicker() {
        if (!exRange || !window.flatpickr) return;
        if (exFpRange) return;
        const defaults = [];
        if (exFrom && exFrom.value) defaults.push(exFrom.value);
        if (exTo && exTo.value) defaults.push(exTo.value);
        exFpRange = flatpickr(exRange, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd M Y',
            allowInput: false,
            disableMobile: true,
            appendTo: document.getElementById('revExportModal') || document.body,
            defaultDate: defaults.length ? defaults : null,
            onChange: function (dates, _s, inst) {
                if (!exFrom || !exTo) return;
                if (exPeriod && exPeriod.value !== 'custom') return;
                exFrom.value = dates[0] ? inst.formatDate(dates[0], 'Y-m-d') : '';
                exTo.value = dates[1]
                    ? inst.formatDate(dates[1], 'Y-m-d')
                    : (dates[0] ? inst.formatDate(dates[0], 'Y-m-d') : '');
            }
        });
        syncExPeriodFields();
    }
    // Export modal Region → Cluster → Branch → Owner cascade
    (function () {
        const $ = window.jQuery;
        const regionEl = document.getElementById('revExRegion');
        const clusterEl = document.getElementById('revExCluster');
        const branchEl = document.getElementById('revExBranch');
        const ownerEl = document.getElementById('revExOwner');
        if (!regionEl || !clusterEl || !branchEl) return;
        const orgTree = @json($orgTree);
        if (!orgTree.owners) orgTree.owners = [];

        function reinitsSelect2(el) {
            if (!$ || !$.fn || !$.fn.select2) return;
            const $el = $(el);
            if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
            $el.attr('data-allow-clear', 'true');
            $el.attr('data-search', 'true');
            if (window.IsarvaSelect2 && typeof window.IsarvaSelect2.initOne === 'function') {
                window.IsarvaSelect2.initOne(el);
            } else {
                $el.select2({
                    width: '100%',
                    allowClear: true,
                    placeholder: $el.data('placeholder') || '',
                    minimumResultsForSearch: 0,
                    dropdownParent: $('#revExportModal')
                });
            }
        }
        function setOptions(select, items, selected, extraAttrs) {
            const keep = selected == null ? '' : String(selected);
            const html = ['<option value=""></option>'];
            items.forEach(function (item) {
                const sel = String(item.id) === keep ? ' selected' : '';
                let attrs = ' data-parent="' + (item.parent_id || '') + '"';
                if (extraAttrs) {
                    attrs +=
                        ' data-region="' + (item.region_id || '') + '"' +
                        ' data-cluster="' + (item.cluster_id || '') + '"' +
                        ' data-branch="' + (item.branch_id || '') + '"';
                }
                html.push(
                    '<option value="' + item.id + '"' + attrs + sel + '>' +
                    String(item.name || item.label || '').replace(/</g, '&lt;') + '</option>'
                );
            });
            select.innerHTML = html.join('');
            select.value = (keep && select.querySelector('option[value="' + keep.replace(/"/g, '\\"') + '"]')) ? keep : '';
            reinitsSelect2(select);
        }
        function clustersForRegion(regionId) {
            if (!regionId) return orgTree.clusters.slice();
            return orgTree.clusters.filter(function (c) { return String(c.parent_id) === String(regionId); });
        }
        function branchesFor(regionId, clusterId) {
            if (clusterId) return orgTree.branches.filter(function (b) { return String(b.parent_id) === String(clusterId); });
            if (regionId) {
                const clusterIds = clustersForRegion(regionId).map(function (c) { return String(c.id); });
                return orgTree.branches.filter(function (b) { return clusterIds.indexOf(String(b.parent_id)) !== -1; });
            }
            return orgTree.branches.slice();
        }
        function ownersFor(regionId, clusterId, branchId) {
            return (orgTree.owners || []).filter(function (o) {
                if (branchId) return String(o.branch_id) === String(branchId);
                if (clusterId) return String(o.cluster_id) === String(clusterId);
                if (regionId) return String(o.region_id) === String(regionId);
                return true;
            }).map(function (o) {
                return { id: o.id, name: o.label || o.name, region_id: o.region_id, cluster_id: o.cluster_id, branch_id: o.branch_id };
            });
        }
        function syncOwners(resetOwner) {
            if (!ownerEl) return;
            const keep = resetOwner ? '' : (ownerEl.value || '');
            const valid = ownersFor(regionEl.value || '', clusterEl.value || '', branchEl.value || '');
            const keepOk = valid.some(function (o) { return String(o.id) === String(keep); }) ? keep : '';
            setOptions(ownerEl, valid, keepOk, true);
        }
        function syncFromCluster(resetBranch) {
            const keep = resetBranch ? '' : (branchEl.value || '');
            const valid = branchesFor(regionEl.value || '', clusterEl.value || '');
            const keepOk = valid.some(function (b) { return String(b.id) === String(keep); }) ? keep : '';
            setOptions(branchEl, valid, keepOk, false);
            syncOwners(!!resetBranch);
        }
        function syncFromRegion(resetDownstream) {
            const keep = resetDownstream ? '' : (clusterEl.value || '');
            const valid = clustersForRegion(regionEl.value || '');
            const keepOk = valid.some(function (c) { return String(c.id) === String(keep); }) ? keep : '';
            setOptions(clusterEl, valid, keepOk, false);
            syncFromCluster(true);
        }
        function bind() {
            if (!$) return;
            $(regionEl).off('.revExCascade').on('change.revExCascade select2:select.revExCascade select2:clear.revExCascade', function () {
                syncFromRegion(true);
            });
            $(clusterEl).off('.revExCascade').on('change.revExCascade select2:select.revExCascade select2:clear.revExCascade', function () {
                syncFromCluster(true);
            });
            $(branchEl).off('.revExCascade').on('change.revExCascade select2:select.revExCascade select2:clear.revExCascade', function () {
                syncOwners(true);
            });
        }
        if ($) $(bind); else bind();
    })();
    function exportFormParams() {
        if (!exForm) return new URLSearchParams();
        const fd = new FormData(exForm);
        const qs = new URLSearchParams();
        fd.forEach(function (v, k) {
            if (k === 'report_period' || k === 'month') return;
            if (v !== null && String(v) !== '') qs.set(k, String(v));
        });
        return qs;
    }
    function moneyShortLakh(n) {
        const v = Number(n);
        if (!isFinite(v)) return '—';
        if (Math.abs(v) >= 100000) return '₹ ' + (v / 100000).toFixed(2).replace(/\.00$/, '') + 'L';
        if (Math.abs(v) >= 1000) return '₹ ' + (v / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
        return moneyInr(v);
    }
    function selText(id, fallback) {
        const el = document.getElementById(id);
        if (!el) return fallback || 'All';
        if (!el.value) return el.getAttribute('data-placeholder') || fallback || 'All';
        const opt = el.options && el.options[el.selectedIndex];
        const t = opt ? String(opt.text || '').trim() : '';
        return t || fallback || 'All';
    }
    function renderPrevFilterBar() {
        const bar = document.getElementById('revExPrevFilters');
        if (!bar) return;
        const items = [
            ['Region', selText('revExRegion', 'All Regions')],
            ['Cluster', selText('revExCluster', 'All Clusters')],
            ['Branch', selText('revExBranch', 'All Branches')],
            ['Product', selText('revExProduct', 'All Products')],
            ['Employee', selText('revExOwner', 'All Employees')],
            ['Entry Type', selText('revExType', 'All Types')]
        ];
        bar.innerHTML = items.map(function (it) {
            return '<div class="fb"><b>' + esc(it[0]) + ':</b> ' + esc(it[1]) + '</div>';
        }).join('');
    }
    const kpiMeta = {
        recognized: { tone: 't-green', icon: 'bi-layers-fill' },
        verified_disb: { tone: 't-blue', icon: 'bi-briefcase-fill' },
        adjustments: { tone: 't-amber', icon: 'bi-sliders' },
        reversals: { tone: 't-rose', icon: 'bi-arrow-counterclockwise' },
        net: { tone: 't-green', icon: 'bi-cash-stack' },
        revenue: { tone: 't-green', icon: 'bi-currency-rupee' },
        payin: { tone: 't-blue', icon: 'bi-box-arrow-in-down' },
        payout: { tone: 't-amber', icon: 'bi-box-arrow-up' },
        entries: { tone: 't-slate', icon: 'bi-list-ul' },
        employees: { tone: 't-slate', icon: 'bi-people-fill' },
        branches: { tone: 't-slate', icon: 'bi-building' }
    };
    function renderExportCharts(charts) {
        const wrap = document.getElementById('revExPrevCharts');
        const bars = document.getElementById('revExBars');
        const legend = document.getElementById('revExLegend');
        const donut = document.getElementById('revExDonut');
        const donutCenter = document.getElementById('revExDonutCenter');
        if (!wrap || !bars || !legend) return;
        const monthly = (charts && charts.monthly) || [];
        const branch = (charts && charts.branch) || [];
        if (!monthly.length && !branch.length) {
            wrap.hidden = true;
            return;
        }
        wrap.hidden = false;
        const maxNet = Math.max(1, ...monthly.map(function (x) { return Number(x.net) || 0; }));
        let peakIdx = -1;
        let peakVal = -1;
        monthly.forEach(function (x, i) {
            const v = Number(x.net) || 0;
            if (v > peakVal) { peakVal = v; peakIdx = i; }
        });
        bars.innerHTML = monthly.map(function (x, i) {
            const v = Number(x.net) || 0;
            const h = Math.max(3, Math.round((v / maxNet) * 62));
            const lbl = String(x.label || '').replace(/\s+\d{4}$/, '');
            const isPeak = i === peakIdx && v > 0;
            const tip = isPeak ? ('<span class="rev-ex-bar-tip">' + esc(moneyShortLakh(v)) + '</span>') : '';
            return '<div class="rev-ex-bar-col' + (isPeak ? ' is-peak' : '') + '">' + tip +
                '<div class="rev-ex-bar" style="height:' + h + 'px"></div><small>' + esc(lbl) + '</small></div>';
        }).join('');
        const colors = ['#16a34a', '#2563eb', '#f59e0b', '#e11d48', '#7c3aed', '#0d9488'];
        const total = branch.reduce(function (s, x) { return s + (Number(x.value) || 0); }, 0);
        const denom = Math.max(1, total);
        let cursor = 0;
        const stops = [];
        legend.innerHTML = branch.slice(0, 6).map(function (x, i) {
            const pct = ((Number(x.value) || 0) / denom) * 100;
            const start = cursor;
            cursor += pct;
            stops.push(colors[i % colors.length] + ' ' + start + '% ' + cursor + '%');
            return '<li><i style="background:' + colors[i % colors.length] + '"></i>' +
                '<span class="nm">' + esc(x.label || '—') + '</span>' +
                '<span class="pc">' + pct.toFixed(0) + '%</span></li>';
        }).join('');
        if (donut) {
            donut.style.background = stops.length
                ? ('conic-gradient(' + stops.join(', ') + ')')
                : 'conic-gradient(#e2e8f0 0 100%)';
        }
        if (donutCenter) {
            donutCenter.innerHTML = '<strong>' + esc(moneyShortLakh(total)) + '</strong><span>Total</span>';
        }
    }
    function renderExportPreview(data) {
        const title = document.getElementById('revExPrevTitle');
        const meta = document.getElementById('revExPrevMeta');
        const badge = document.getElementById('revExPrevBadge');
        const kpisEl = document.getElementById('revExPrevKpis');
        const head = document.getElementById('revExPrevHead');
        const body = document.getElementById('revExPrevBody');
        const tableTitle = document.getElementById('revExTableTitle');
        const footL = document.getElementById('revExPrevFootL');
        if (!title || !body) return;
        const reportTitle = (data.report && data.report.title) || 'Report';
        title.textContent = reportTitle;
        if (badge) badge.textContent = reportTitle;
        const m = data.meta || {};
        let monthLbl = '';
        if (exMonth && exMonth.value) {
            const parts = exMonth.value.split('-');
            if (parts.length >= 2) {
                const d = new Date(Number(parts[0]), Number(parts[1]) - 1, 1);
                monthLbl = d.toLocaleString('en-IN', { month: 'long', year: 'numeric' });
            }
        }
        meta.textContent = [
            m.fy_label ? ('Financial Year: ' + String(m.fy_label).replace(/-/g, ' – ')) : '',
            monthLbl ? ('Month: ' + monthLbl) : (m.period_label ? ('Period: ' + m.period_label) : '')
        ].filter(Boolean).join(' | ') || 'Select filters and refresh preview';
        if (footL) footL.textContent = 'Goal Corporation | ' + reportTitle;
        renderPrevFilterBar();
        if (tableTitle) {
            const key = (data.report && data.report.key) || '';
            tableTitle.textContent = key === 'summary' ? 'Branch-wise Revenue Summary'
                : key === 'org' ? 'Organization Revenue'
                : key === 'employee' ? 'Employee Revenue'
                : 'Report Data';
        }
        kpisEl.innerHTML = '';
        const kpis = data.kpis || {};
        const preferred = ['recognized', 'verified_disb', 'adjustments', 'reversals', 'net', 'revenue', 'payin', 'payout'];
        let kpiKeys = preferred.filter(function (k) { return Object.prototype.hasOwnProperty.call(kpis, k); });
        if (!kpiKeys.length) kpiKeys = Object.keys(kpis).slice(0, 4);
        else kpiKeys = kpiKeys.slice(0, 4);
        kpiKeys.forEach(function (k) {
            const div = document.createElement('div');
            const metaK = kpiMeta[k] || { tone: 't-slate', icon: 'bi-graph-up' };
            div.className = 'rev-ex-kpi ' + metaK.tone;
            const countish = /count|entries|employees|branches|clusters|regions|targets|matched|missing|disbursements|cases/i.test(k);
            const val = countish ? Number(kpis[k]).toLocaleString('en-IN') : moneyInr(kpis[k]);
            div.innerHTML =
                '<div class="kpi-top"><span class="lbl">' + esc(kpiLabels[k] || k.replace(/_/g, ' ')) + '</span>' +
                '<span class="kpi-ico"><i class="bi ' + metaK.icon + '"></i></span></div>' +
                '<div class="val">' + esc(val) + '</div>';
            kpisEl.appendChild(div);
        });
        renderExportCharts(data.charts || {});
        const cols = data.columns || [];
        const moneyCol = /amount|revenue|disburs|pay|net|target|actual/i;
        head.innerHTML = '<tr><th class="idx">#</th>' + cols.map(function (c) {
            return '<th' + (moneyCol.test(String(c)) || /%/.test(String(c)) ? ' class="num"' : '') + '>' + esc(c) + '</th>';
        }).join('') + '</tr>';
        const rows = data.rows || [];
        if (!rows.length) {
            body.innerHTML = '<tr><td class="rev-ex-empty" colspan="' + Math.max(2, cols.length + 1) + '">No records for these filters.</td></tr>';
            return;
        }
        let html = rows.map(function (r, i) {
            const cells = Array.isArray(r) ? r : Object.values(r);
            return '<tr><td class="idx">' + (i + 1) + '</td>' + cells.map(function (cell, ci) {
                const col = String(cols[ci] || '');
                const cls = (moneyCol.test(col) || /%/.test(col) || ci > 0) ? ' class="num"' : '';
                return '<td' + cls + '>' + esc(cell == null || cell === '' ? '—' : cell) + '</td>';
            }).join('') + '</tr>';
        }).join('');
        const totals = data.totals || {};
        if (Object.keys(totals).length && cols.length) {
            html += '<tr class="is-total"><td class="idx"></td>' + cols.map(function (c, ci) {
                const cls = (moneyCol.test(String(c)) || /%/.test(String(c)) || ci > 0) ? ' class="num"' : '';
                let v = '—';
                if (ci === 0) v = 'Total';
                else if (Object.prototype.hasOwnProperty.call(totals, c)) v = totals[c];
                else if (/%/.test(String(c))) v = '100.0%';
                return '<td' + cls + '>' + esc(v) + '</td>';
            }).join('') + '</tr>';
        }
        body.innerHTML = html;
    }
    function refreshExportPreview() {
        const body = document.getElementById('revExPrevBody');
        if (body) body.innerHTML = '<tr><td class="rev-ex-empty">Loading live preview…</td></tr>';
        const qs = exportFormParams();
        fetch(exportPreviewUrl + '?' + qs.toString(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        }).then(function (j) {
            if (j && j.ok) renderExportPreview(j);
            else if (body) body.innerHTML = '<tr><td class="rev-ex-empty text-danger">Unable to load preview.</td></tr>';
        }).catch(function () {
            if (body) body.innerHTML = '<tr><td class="rev-ex-empty text-danger">Unable to load preview.</td></tr>';
        });
    }
    document.querySelectorAll('.rev-ex-type').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.rev-ex-type').forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            if (exReport) exReport.value = btn.getAttribute('data-report') || 'summary';
            refreshExportPreview();
        });
    });
    document.querySelectorAll('.rev-ex-fmt-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.rev-ex-fmt-btn').forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            if (exFormat) exFormat.value = btn.getAttribute('data-format') || 'pdf';
        });
    });
    if (exMonth) exMonth.addEventListener('change', function () {
        if (exPeriod && exPeriod.value !== 'monthly') return;
        applyExMonth();
    });
    if (exPeriod) exPeriod.addEventListener('change', function () {
        if (exPeriod.value === 'monthly') applyExMonth();
        else if (exPeriod.value === 'fy') applyExFy();
        syncExPeriodFields();
    });
    if (exFy) exFy.addEventListener('change', function () {
        if (exPeriod && exPeriod.value === 'fy') applyExFy();
        else if (exPeriod && exPeriod.value === 'monthly') applyExMonth();
    });
    const exRefresh = document.getElementById('revExRefresh');
    if (exRefresh) exRefresh.addEventListener('click', refreshExportPreview);
    const exDownload = document.getElementById('revExDownload');
    if (exDownload) {
        exDownload.addEventListener('click', function () {
            const qs = exportFormParams();
            window.location.href = exportDownloadUrl + '?' + qs.toString();
        });
    }
    const exReset = document.getElementById('revExReset');
    if (exReset && exForm) {
        exReset.addEventListener('click', function () {
            const $ = window.jQuery;
            exForm.reset();
            if (exReport) exReport.value = 'summary';
            if (exFormat) exFormat.value = 'pdf';
            document.querySelectorAll('.rev-ex-type').forEach(function (b, i) {
                b.classList.toggle('is-active', i === 0);
            });
            document.querySelectorAll('.rev-ex-fmt-btn').forEach(function (b) {
                b.classList.toggle('is-active', b.getAttribute('data-format') === 'pdf');
            });
            if ($) {
                $('#revExportForm select.form-select').each(function () {
                    const $el = $(this);
                    if ($el.hasClass('select2-hidden-accessible')) {
                        $el.val(this.id === 'revExPeriod' ? 'monthly' : (this.id === 'revExFy' ? (exFy ? exFy.value : '') : '')).trigger('change');
                    }
                });
            }
            if ($) setExSelect($(exPeriod), 'monthly');
            else if (exPeriod) exPeriod.value = 'monthly';
            applyExMonth();
            syncExMonthPicker(true);
            syncExRangePicker(true);
            syncExPeriodFields();
            refreshExportPreview();
        });
    }
    const exModal = document.getElementById('revExportModal');
    if (exModal) {
        exModal.addEventListener('shown.bs.modal', function () {
            initExRangePicker();
            if (window.IsarvaDatepicker && typeof window.IsarvaDatepicker.init === 'function') {
                window.IsarvaDatepicker.init(exModal);
            }
            if (window.IsarvaSelect2 && typeof window.IsarvaSelect2.init === 'function') {
                window.IsarvaSelect2.init(exModal);
            }
            syncExRangePicker(true);
            syncExMonthPicker(true);
            syncExPeriodFields();
            refreshExportPreview();
        });
    }
})();
</script>
@endpush
