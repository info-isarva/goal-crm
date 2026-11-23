@extends('layouts.app')

@php
    $f = $dash['filters'] ?? [];
    $opts = $dash['filter_options'] ?? [];
    $section = $f['section'] ?? 'overview';
    $targetTab = $f['target_tab'] ?? 'employee';
    $qs = request()->except(['section', 'target_tab']);
    $show = function (array $for) use ($section) {
        return $section === 'overview' || in_array($section, $for, true);
    };
    $kpiTone = [
        'blue' => 't-blue', 'teal' => 't-teal', 'amber' => 't-amber',
        'green' => 't-green', 'purple' => 't-purple', 'red' => 't-red',
    ];
    $sections = [
        'overview' => ['Overview', 'bi-grid-1x2-fill'],
        'leads' => ['Lead Analytics', 'bi-people-fill'],
        'processing' => ['Processing', 'bi-layers-fill'],
        'disbursement' => ['Disbursement', 'bi-coin'],
        'revenue' => ['Revenue & Payout', 'bi-currency-rupee'],
        'performance' => ['Performance', 'bi-trophy-fill'],
    ];
    $dateFromLabel = !empty($f['date_from']) ? \Carbon\Carbon::parse($f['date_from'])->format('d M Y') : '';
    $dateToLabel = !empty($f['date_to']) ? \Carbon\Carbon::parse($f['date_to'])->format('d M Y') : '';
    $dateRangeLabel = trim($dateFromLabel.($dateFromLabel && $dateToLabel ? ' - ' : '').$dateToLabel);
@endphp

@push('styles')
@include('goal.partials.pd_hub_styles')
<style>
.ga-hub .ga-hero{
    display:flex;flex-wrap:nowrap;align-items:flex-start;justify-content:space-between;
    gap:16px 20px;margin-bottom:1.1rem;width:100%
}
.ga-hub .ga-hero-left{
    display:flex;align-items:flex-start;gap:14px;min-width:0;flex:1 1 auto
}
.ga-hub .ga-hero-ico{
    width:48px;height:48px;border-radius:12px;flex:0 0 auto;margin-top:2px;
    display:inline-flex;align-items:center;justify-content:center;
    background:var(--theme-primary);color:#fff;font-size:1.35rem;
    box-shadow:0 8px 18px var(--theme-primary-ring)
}
.ga-hub .ga-hero-copy{min-width:0;flex:1 1 auto}
.ga-hub .ga-hero-copy .pd-title{
    margin:0;font-size:1.55rem;font-weight:800;color:#0f172a;letter-spacing:-.02em;line-height:1.2
}
.ga-hub .ga-hero-copy .pd-sub{
    margin:.35rem 0 0;color:#64748b;font-size:.88rem;line-height:1.45;max-width:42rem
}
.ga-hub .ga-hero-actions{
    display:flex;flex-wrap:nowrap;gap:10px;align-items:center;
    flex:0 0 auto;margin-left:auto;padding-top:4px
}
.ga-hub .ga-hero-actions .dropdown{flex:0 0 auto}
.ga-hub .ga-btn{
    height:40px;min-height:40px;padding:0 16px;border-radius:10px;font-size:.84rem;font-weight:700;
    display:inline-flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;background:#fff;
    cursor:pointer;line-height:1;white-space:nowrap;touch-action:manipulation;-webkit-tap-highlight-color:transparent
}
.ga-hub .ga-btn-export{
    border:1.5px solid var(--theme-primary);color:#fff;background:var(--theme-primary);
    box-shadow:0 6px 14px var(--theme-primary-ring)
}
.ga-hub .ga-btn-export:hover{background:var(--theme-primary-hover,var(--theme-primary));color:#fff;filter:brightness(1.05)}
.ga-hub .ga-btn-schedule{
    border:1px solid #d1d5db;color:#334155;background:#fff
}
.ga-hub .ga-btn-schedule:hover{background:#f8fafc;color:#0f172a}
.ga-hub .btn-short{display:none}
.ga-hub .btn-full{display:inline}
.ga-hub .dropdown-menu .dropdown-item{
    padding:.65rem 1rem;font-size:.86rem;font-weight:600;min-height:42px;
    display:flex;align-items:center
}
.ga-hub .ga-export-menu{min-width:240px;padding:.4rem 0;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 12px 28px rgba(15,23,42,.12)}
.ga-hub .ga-export-menu .dropdown-header{
    font-size:.68rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;padding:.45rem 1rem .2rem
}
.ga-hub .ga-export-menu .dropdown-divider{margin:.35rem 0}

.ga-hub .sec-tabs{
    display:flex;flex-wrap:nowrap;gap:8px;margin:0 0 1rem;padding:2px 2px 8px;
    background:transparent;border-radius:0;overflow-x:auto;-webkit-overflow-scrolling:touch;
    scrollbar-width:thin;scroll-snap-type:x proximity;overscroll-behavior-x:contain
}
.ga-hub .sec-tabs::-webkit-scrollbar{height:4px}
.ga-hub .sec-tabs::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:999px}
.ga-hub .sec-tab{
    display:inline-flex;align-items:center;justify-content:center;gap:8px;
    min-height:44px;padding:.55rem 1.05rem;border-radius:10px;
    font-size:.82rem;font-weight:700;color:#0f172a;text-decoration:none;flex:0 0 auto;
    border:1px solid #e2e8f0;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.03);
    white-space:nowrap;scroll-snap-align:start;touch-action:manipulation;-webkit-tap-highlight-color:transparent
}
.ga-hub .sec-tab i{font-size:.95rem;opacity:.9}
.ga-hub .sec-tab:hover{border-color:#cbd5e1;background:#f8fafc;color:#0f172a}
.ga-hub .sec-tab.active{
    background:var(--theme-primary);border-color:var(--theme-primary);color:#fff;
    box-shadow:0 6px 14px var(--theme-primary-ring)
}
.ga-hub .sec-tab.active i{opacity:1}

.ga-hub .ga-filters{
    display:grid;
    grid-template-columns:minmax(150px,160px) minmax(200px,1.35fr) repeat(5,minmax(110px,1fr)) auto;
    gap:12px;align-items:end;
    margin-bottom:1rem;padding:14px 16px;background:#fff;border:1px solid #e8edf5;border-radius:14px;
    box-shadow:0 6px 16px rgba(148,163,184,.08)
}
.ga-hub .ga-filters > div:not(.ga-filter-actions){min-width:0;display:flex;flex-direction:column;justify-content:flex-end}
.ga-hub .ga-filters > div:first-child{min-width:150px}
.ga-hub .ga-filters > div:first-child .form-select{
    text-overflow:clip;overflow:visible;white-space:nowrap;padding-right:1.75rem
}
.ga-hub .ga-filters label{
    display:block;font-size:.68rem;font-weight:700;color:#64748b;margin:0 0 5px;
    text-transform:uppercase;letter-spacing:.03em;line-height:1.2
}
.ga-hub .ga-filters .form-control,.ga-hub .ga-filters .form-select{
    width:100%;min-height:40px;height:40px;border-radius:8px;border:1px solid #d1d5db;
    font-size:.84rem;background:#fff;color:#0f172a;box-sizing:border-box
}
.ga-hub .ga-date-wrap{position:relative;width:100%}
.ga-hub .ga-date-wrap .bi-calendar3{
    position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.9rem;pointer-events:none;z-index:1
}
.ga-hub .ga-date-wrap .form-control{padding-left:36px}
.ga-hub .ga-filter-actions{
    display:flex;gap:8px;align-items:center;align-self:end;padding-bottom:0;white-space:nowrap;height:40px
}
.ga-hub .ga-btn-apply{
    height:40px;min-height:40px;padding:0 16px;border-radius:8px;border:0;background:var(--theme-primary);color:#fff;
    font-size:.84rem;font-weight:700;display:inline-flex;align-items:center;justify-content:center;gap:7px;
    box-shadow:0 6px 14px var(--theme-primary-ring);cursor:pointer;touch-action:manipulation;-webkit-tap-highlight-color:transparent
}
.ga-hub .ga-btn-apply:hover{filter:brightness(1.05);color:#fff}
.ga-hub .ga-btn-reset{
    height:40px;min-height:40px;padding:0 14px;border-radius:8px;border:1px solid #d1d5db;background:#fff;color:#334155;
    font-size:.84rem;font-weight:700;display:inline-flex;align-items:center;justify-content:center;gap:7px;
    text-decoration:none;cursor:pointer;touch-action:manipulation;-webkit-tap-highlight-color:transparent
}
.ga-hub .ga-btn-reset:hover{background:#f8fafc;color:#0f172a}

.ga-hub .pd-kpi-row{
    display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:14px;margin-bottom:1.1rem;
    align-items:stretch
}

.ga-hub a.ga-kpi{text-decoration:none;color:inherit;display:block;min-width:0;height:100%;transition:transform .15s ease,box-shadow .15s ease}
.ga-hub a.ga-kpi:hover{transform:translateY(-2px);box-shadow:0 12px 24px rgba(15,23,42,.1)}
.ga-hub .ga-kpi{
    --kpi-bg:#eff6ff;--kpi-border:#e2e8f0;--kpi-icon-bg:#dbeafe;--kpi-icon:#2563eb;
    --kpi-wave-a:#bfdbfe;--kpi-wave-b:#93c5fd;--kpi-wave-c:#60a5fa;
    position:relative;overflow:hidden;
    display:flex;flex-direction:column;justify-content:flex-start;
    min-height:118px;height:100%;padding:12px 14px 10px;box-sizing:border-box;
    border:1px solid var(--kpi-border);border-radius:14px;
    background:
        radial-gradient(120% 90% at 0% 0%, var(--kpi-bg) 0%, #ffffff 55%),
        #fff;
    box-shadow:0 4px 14px rgba(15,23,42,.05)
}
.ga-hub .ga-kpi.t-blue{--kpi-bg:#e8f1ff;--kpi-border:#dbe7f8;--kpi-icon-bg:#dbeafe;--kpi-icon:#2563eb;--kpi-wave-a:#bfdbfe;--kpi-wave-b:#93c5fd;--kpi-wave-c:#60a5fa}
.ga-hub .ga-kpi.t-teal{--kpi-bg:#e7f8f4;--kpi-border:#d5f0ea;--kpi-icon-bg:#ccfbf1;--kpi-icon:#0d9488;--kpi-wave-a:#99f6e4;--kpi-wave-b:#5eead4;--kpi-wave-c:#2dd4bf}
.ga-hub .ga-kpi.t-amber{--kpi-bg:#fff1e6;--kpi-border:#ffe0c7;--kpi-icon-bg:#ffedd5;--kpi-icon:#ea580c;--kpi-wave-a:#fed7aa;--kpi-wave-b:#fdba74;--kpi-wave-c:#fb923c}
.ga-hub .ga-kpi.t-green{--kpi-bg:#e9f9ef;--kpi-border:#d6f0df;--kpi-icon-bg:#dcfce7;--kpi-icon:#16a34a;--kpi-wave-a:#bbf7d0;--kpi-wave-b:#86efac;--kpi-wave-c:#4ade80}
.ga-hub .ga-kpi.t-purple{--kpi-bg:#ecefff;--kpi-border:#dde2ff;--kpi-icon-bg:#e0e7ff;--kpi-icon:#4f46e5;--kpi-wave-a:#c7d2fe;--kpi-wave-b:#a5b4fc;--kpi-wave-c:#818cf8}
.ga-hub .ga-kpi.t-red{--kpi-bg:#ffecef;--kpi-border:#ffd6de;--kpi-icon-bg:#ffe4e6;--kpi-icon:#e11d48;--kpi-wave-a:#fecdd3;--kpi-wave-b:#fda4af;--kpi-wave-c:#fb7185}
.ga-hub .ga-kpi-ico{
    width:30px;height:30px;border-radius:8px;flex:0 0 auto;position:relative;z-index:2;
    display:inline-flex;align-items:center;justify-content:center;
    background:var(--kpi-icon-bg);color:var(--kpi-icon);font-size:.92rem;
    box-shadow:inset 0 0 0 1px rgba(255,255,255,.55)
}
.ga-hub .ga-kpi-main{position:relative;z-index:2;margin-top:8px;min-width:0}
.ga-hub .ga-kpi-value{
    margin:0;font-size:1.28rem;font-weight:800;color:#0f172a;line-height:1.05;
    letter-spacing:-.025em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis
}
.ga-hub .ga-kpi-label{
    margin:3px 0 0;font-size:.76rem;font-weight:600;color:#64748b;line-height:1.25;
    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden
}
.ga-hub .ga-kpi-delta{
    margin-top:auto;padding-top:8px;position:relative;z-index:2;
    display:inline-flex;align-items:center;gap:4px;max-width:100%;
    font-size:.72rem;font-weight:700;line-height:1.15
}
.ga-hub .ga-kpi-delta i{font-size:.72rem}
.ga-hub .ga-kpi-delta.up{color:#16a34a}
.ga-hub .ga-kpi-delta.down{color:#e11d48}
.ga-hub .ga-kpi-delta.flat{color:#94a3b8}
.ga-hub .ga-kpi-delta .vs{font-weight:600;opacity:.85}
.ga-hub .ga-kpi-wave{position:absolute;right:-2%;bottom:-2%;width:58%;height:68%;pointer-events:none;z-index:1}
.ga-hub .ga-kpi-wave svg{display:block;width:100%;height:100%}
.ga-hub .ga-kpi-wave .w0{fill:var(--kpi-wave-a);opacity:.55}
.ga-hub .ga-kpi-wave .w1{fill:var(--kpi-wave-b);opacity:.42}
.ga-hub .ga-kpi-wave .w2{fill:var(--kpi-wave-c);opacity:.3}

.ga-hub .panel{
    background:#fff;border:1px solid #e8edf5;border-radius:14px;
    box-shadow:0 4px 14px rgba(148,163,184,.08);height:100%;overflow:hidden;
    display:flex;flex-direction:column
}
.ga-hub .panel-h{
    padding:.75rem .9rem .65rem;border-bottom:1px solid #f1f5f9;
    display:flex;align-items:center;justify-content:space-between;gap:.65rem;flex:0 0 auto
}
.ga-hub .panel-h-left{display:flex;align-items:center;gap:10px;min-width:0;flex:1 1 auto}
.ga-hub .panel-ico{
    width:32px;height:32px;border-radius:9px;flex:0 0 auto;
    display:inline-flex;align-items:center;justify-content:center;
    background:var(--theme-primary);color:#fff;font-size:.95rem
}
.ga-hub .panel-h h5{margin:0;font-size:.88rem;font-weight:750;color:#0f172a;line-height:1.2}
.ga-hub .panel-h .sub{font-size:.7rem;color:#94a3b8;margin:.12rem 0 0;line-height:1.3}
.ga-hub .panel-b{padding:.75rem .9rem .9rem;flex:1 1 auto;display:flex;flex-direction:column;min-height:0}
.ga-hub .conv-badge{
    display:inline-flex;align-items:center;padding:.28rem .6rem;border-radius:999px;
    background:#ecfdf5;color:#047857;font-size:.68rem;font-weight:800;white-space:nowrap
}
.ga-hub .chart-legend{
    display:flex;flex-wrap:wrap;gap:.65rem;align-items:center;justify-content:flex-end
}
.ga-hub .chart-legend span{
    display:inline-flex;align-items:center;gap:.35rem;font-size:.68rem;font-weight:700;color:#64748b
}
.ga-hub .chart-legend i{width:8px;height:8px;border-radius:50%;display:inline-block}

.ga-hub .funnel-layout{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(110px,.75fr);gap:14px;align-items:stretch}
@media (max-width:576px){.ga-hub .funnel-layout{grid-template-columns:1fr}}
.ga-hub .funnel-viz{display:flex;flex-direction:column;align-items:stretch;justify-content:center;gap:3px;padding:2px 0;width:100%}
.ga-hub a.funnel-seg{
    display:flex;align-items:center;justify-content:center;
    width:100%;height:28px;margin:0;text-decoration:none;color:#fff;
    font-size:.68rem;font-weight:750;letter-spacing:.01em;line-height:1;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;padding:0 12px;
    transition:filter .15s
}
.ga-hub a.funnel-seg:hover{filter:brightness(1.08);color:#fff}
/* Continuous taper: each stage top matches previous bottom; never flares wider */
.ga-hub a.funnel-seg.s1{clip-path:polygon(0% 0,100% 0,95% 100%,5% 100%)}
.ga-hub a.funnel-seg.s2{clip-path:polygon(5% 0,95% 0,90% 100%,10% 100%)}
.ga-hub a.funnel-seg.s3{clip-path:polygon(10% 0,90% 0,85% 100%,15% 100%)}
.ga-hub a.funnel-seg.s4{clip-path:polygon(15% 0,85% 0,80% 100%,20% 100%)}
.ga-hub a.funnel-seg.s5{clip-path:polygon(20% 0,80% 0,75% 100%,25% 100%)}
.ga-hub a.funnel-seg.s6{clip-path:polygon(25% 0,75% 0,70% 100%,30% 100%)}
.ga-hub .funnel-meta{display:flex;flex-direction:column;justify-content:center}
.ga-hub a.funnel-meta-row{
    display:grid;grid-template-columns:1fr auto;gap:10px;align-items:center;
    text-decoration:none;color:inherit;min-height:31px;padding:0 2px;
    border-bottom:1px solid #f1f5f9
}
.ga-hub a.funnel-meta-row:last-child{border-bottom:0}
.ga-hub a.funnel-meta-row:hover .cnt{color:var(--theme-primary)}
.ga-hub .funnel-meta-row .cnt{font-size:.84rem;font-weight:800;color:#0f172a;text-align:right}
.ga-hub .funnel-meta-row .pct{
    min-width:3.4rem;text-align:center;padding:.18rem .4rem;border-radius:999px;
    background:#ecfdf5;color:#047857;font-weight:800;font-size:.7rem
}
.ga-hub .funnel-meta-row .pct.is-zero{background:#f1f5f9;color:#94a3b8}

.ga-hub .chart-box{position:relative;height:180px}
.ga-hub .chart-box.sm{height:168px}

.ga-hub .panel.is-branch .panel-b{justify-content:flex-start}
.ga-hub .branch-list{
    display:flex;flex-direction:column;justify-content:flex-start;
    flex:0 0 auto;gap:10px;width:100%
}
.ga-hub a.branch-row{
    display:grid;grid-template-columns:minmax(120px,42%) minmax(48px,1fr) auto;
    gap:12px;align-items:center;text-decoration:none;color:inherit
}
.ga-hub a.branch-row:hover .nm{color:var(--theme-primary)}
.ga-hub .branch-row .nm{
    font-size:.82rem;font-weight:750;color:#0f172a;
    white-space:normal;overflow:visible;text-overflow:unset;line-height:1.25;word-break:break-word
}
.ga-hub .branch-row .amt{
    font-size:.82rem;font-weight:800;color:#0f172a;white-space:nowrap;min-width:4.2rem;text-align:right
}
.ga-hub .branch-bar{height:12px;border-radius:999px;background:#eef2f7;overflow:hidden}
.ga-hub .branch-bar > span{
    display:block;height:100%;border-radius:999px;min-width:0;
    background:linear-gradient(90deg,#2dd4bf,#0d9488)
}
.ga-hub .branch-bar > span.is-empty{width:0 !important;min-width:0}

.ga-hub .row-eq > [class*="col-"]{display:flex}
.ga-hub .row-eq .panel{width:100%}

.ga-hub .tgt-tabs{
    display:flex;flex-wrap:nowrap;align-items:stretch;gap:0;width:100%;margin-bottom:.75rem;
    padding:3px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;
    box-shadow:0 1px 2px rgba(15,23,42,.03);overflow-x:auto;-webkit-overflow-scrolling:touch;
    scrollbar-width:none;overscroll-behavior-x:contain
}
.ga-hub .tgt-tabs::-webkit-scrollbar{display:none}
.ga-hub .tgt-tab{
    position:relative;flex:1 1 0;display:inline-flex;align-items:center;justify-content:center;
    min-height:42px;padding:.55rem .55rem;border:0;border-radius:8px;background:transparent;cursor:pointer;
    font-size:.74rem;font-weight:700;color:#334155;text-decoration:none;line-height:1.2;
    white-space:nowrap;text-align:center;appearance:none;-webkit-appearance:none;
    touch-action:manipulation;-webkit-tap-highlight-color:transparent
}
.ga-hub .tgt-pane{display:none}
.ga-hub .tgt-pane.is-active{display:block}
.ga-hub .tgt-tab:not(.active):not(:first-child)::before{
    content:"";position:absolute;left:0;top:22%;bottom:22%;width:1px;background:#e2e8f0
}
.ga-hub .tgt-tab.active{
    background:var(--theme-primary);color:#fff;
    box-shadow:0 4px 10px var(--theme-primary-ring)
}
.ga-hub .tgt-tab.active + .tgt-tab::before{display:none}
.ga-hub .tgt-tab:hover:not(.active){color:#0f172a;background:#f8fafc}
.ga-hub .tgt-table{width:100%;border-collapse:separate;border-spacing:0}
.ga-hub .tgt-table th{
    font-size:.64rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.03em;
    background:#f1f5f9;padding:.5rem .55rem;text-align:left;border-bottom:1px solid #e2e8f0
}
.ga-hub .tgt-table th:nth-child(2),
.ga-hub .tgt-table th:nth-child(3),
.ga-hub .tgt-table th:nth-child(4),
.ga-hub .tgt-table td:nth-child(2),
.ga-hub .tgt-table td:nth-child(3){text-align:center}
.ga-hub .tgt-table td{
    padding:.55rem .55rem;border-bottom:1px solid #f1f5f9;font-size:.78rem;vertical-align:middle
}
.ga-hub .tgt-table tr:last-child td{border-bottom:0}
.ga-hub .tgt-table .name{font-weight:750;color:#0f172a}
.ga-hub .tgt-table .amt{color:#334155;font-weight:650}
.ga-hub .tgt-ach{display:flex;align-items:center;gap:8px;min-width:0}
.ga-hub .tgt-ach .pct{font-size:.74rem;font-weight:800;color:#0f766e;min-width:2.2rem}
.ga-hub .prog{flex:1;height:8px;border-radius:999px;background:#e2e8f0;overflow:hidden;min-width:48px}
.ga-hub .prog > span{display:block;height:100%;border-radius:999px;background:#0d9488}

.ga-hub .status-grid{
    display:grid;grid-template-columns:minmax(130px,.95fr) minmax(0,1.25fr);
    gap:18px;align-items:center;height:100%;min-height:168px
}
@media (max-width:576px){.ga-hub .status-grid{grid-template-columns:1fr;min-height:0}}
.ga-hub .donut-wrap{position:relative;width:140px;height:140px;margin:0 auto;z-index:1}
.ga-hub .donut-wrap canvas{position:relative;z-index:1}
.ga-hub .donut-center{
    position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;
    pointer-events:none;text-align:center;z-index:0
}
.ga-hub .donut-center .num{font-size:1.45rem;font-weight:800;color:#0f172a;line-height:1;letter-spacing:-.02em}
.ga-hub .donut-center .txt{font-size:.68rem;font-weight:650;color:#64748b;margin-top:.2rem;text-transform:none}
.ga-hub .legend-list{display:flex;flex-direction:column;gap:0;width:100%}
.ga-hub a.legend-item{
    display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:center;
    font-size:.78rem;text-decoration:none;color:inherit;padding:.48rem 0;
    border-bottom:1px solid #f1f5f9
}
.ga-hub a.legend-item:last-child{border-bottom:0}
.ga-hub a.legend-item:hover .name{color:var(--theme-primary)}
.ga-hub .legend-item .left{display:inline-flex;align-items:center;gap:.5rem;color:#0f172a;font-weight:650;min-width:0}
.ga-hub .legend-item .dot{width:9px;height:9px;border-radius:50%;flex-shrink:0}
.ga-hub .legend-item .name{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ga-hub .legend-item .val{font-weight:750;color:#0f172a;white-space:nowrap;text-align:right}
.ga-hub .legend-item .val .cnt{color:#64748b;font-weight:650}

.ga-hub .alert-list{display:flex;flex-direction:column;gap:0}
.ga-hub .alert-item{
    display:grid;grid-template-columns:28px minmax(0,1fr) auto auto;gap:12px;align-items:center;
    padding:.7rem 2px;border:0;border-bottom:1px solid #eef2f7;border-radius:0;
    background:transparent;text-decoration:none;color:inherit
}
.ga-hub .alert-item:last-child{border-bottom:0}
.ga-hub .alert-item:hover{background:transparent}
.ga-hub .alert-item:hover .lbl{color:var(--theme-primary)}
.ga-hub .alert-item .ico{
    width:28px;height:28px;border-radius:0;display:inline-flex;align-items:center;justify-content:center;
    color:var(--theme-primary);background:transparent;font-size:1.05rem;padding:0
}
.ga-hub .alert-item .lbl{font-size:.82rem;font-weight:700;color:#0f172a;min-width:0}
.ga-hub .alert-item .cnt{
    min-width:2rem;height:26px;padding:0 10px;border-radius:999px;
    display:inline-flex;align-items:center;justify-content:center;
    font-size:.8rem;font-weight:800;color:#0f766e;background:#ecfdf5
}
.ga-hub .prio{
    padding:.22rem .6rem;border-radius:999px;font-size:.68rem;font-weight:800;
    text-transform:capitalize;letter-spacing:.01em;min-width:4.2rem;text-align:center
}
.ga-hub .prio.danger{background:#fef2f2;color:#dc2626}
.ga-hub .prio.warning{background:#fff7ed;color:#ea580c}
.ga-hub .prio.success{background:#ecfdf5;color:#16a34a}
.ga-hub .view-all{
    font-size:.78rem;font-weight:750;color:#2563eb;text-decoration:none;white-space:nowrap;
    min-height:36px;padding:.35rem .55rem;border-radius:8px;display:inline-flex;align-items:center;
    touch-action:manipulation
}
.ga-hub .view-all:hover{text-decoration:underline;color:#1d4ed8;background:#eff6ff}
.ga-hub .empty-soft{padding:1rem;text-align:center;color:#94a3b8;font-size:.8rem;font-weight:600}
.ga-hub .ga-results{position:relative;min-height:120px;transition:opacity .2s ease}
.ga-hub .ga-results.is-loading{opacity:.55;pointer-events:none}
.ga-hub .ga-results.is-loading::after{
    content:"";position:absolute;inset:12px 0 auto;margin:0 auto;width:28px;height:28px;
    border:3px solid #e2e8f0;border-top-color:var(--theme-primary);border-radius:50%;
    animation:ga-spin .7s linear infinite;z-index:5
}
@keyframes ga-spin{to{transform:rotate(360deg)}}
.ga-hub .ga-btn-apply.is-busy{opacity:.75;cursor:wait}
.ga-hub .ga-btn-apply:disabled,.ga-hub .ga-btn-reset:disabled{opacity:.7;cursor:wait}

/* Schedule Report modal */
#gaScheduleModal .modal-dialog{max-width:980px}
.ga-sched-modal{border:0;border-radius:16px;overflow:hidden;box-shadow:0 24px 48px rgba(15,23,42,.18)}
.ga-sched-head{
    border-bottom:1px solid #eef2f7;padding:1.05rem 1.25rem;background:#fff;
    display:flex;align-items:flex-start;justify-content:space-between;gap:12px
}
.ga-sched-head-left{display:flex;align-items:flex-start;gap:12px;min-width:0;flex:1 1 auto}
.ga-sched-ico{
    width:44px;height:44px;border-radius:12px;background:var(--theme-primary);color:#fff;
    display:inline-flex;align-items:center;justify-content:center;font-size:1.2rem;flex:0 0 auto;
    box-shadow:0 8px 16px var(--theme-primary-ring)
}
.ga-sched-head .modal-title{margin:0;font-size:1.2rem;font-weight:800;color:#0f172a;line-height:1.2}
.ga-sched-sub{margin:.28rem 0 0;color:#64748b;font-size:.84rem;line-height:1.35}
.ga-sched-body{padding:1.1rem 1.25rem;background:#f8fafc;max-height:min(72vh,640px);overflow:auto}
.ga-sched-grid{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(250px,.78fr);gap:18px;align-items:start}
.ga-sched-main{
    background:#fff;border:1px solid #e8edf5;border-radius:14px;padding:1.05rem 1.1rem 1.15rem;
    box-shadow:0 4px 12px rgba(148,163,184,.06)
}
.ga-sched-field{margin:0 0 .95rem}
.ga-sched-field-last{margin-bottom:0}
.ga-sched-field > label,.ga-date-col > label{
    display:block;font-size:.78rem;font-weight:750;color:#334155;margin:0 0 6px
}
.ga-sched-field .req,.ga-date-col .req{color:#dc2626}
.ga-sched-field .form-control,.ga-sched-field .form-select,
.ga-dp .form-control{
    width:100%;min-height:42px;height:42px;border-radius:10px;border:1px solid #d1d5db;
    font-size:.86rem;color:#0f172a;background:#fff;box-sizing:border-box
}
.ga-sched-field .form-control:focus,.ga-sched-field .form-select:focus,
.ga-dp .form-control:focus{
    border-color:var(--theme-primary);box-shadow:0 0 0 3px var(--theme-primary-ring)
}
.ga-freq-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}
.ga-freq-card{margin:0;cursor:pointer;position:relative}
.ga-freq-card input{position:absolute;opacity:0;pointer-events:none}
.ga-freq-face{
    display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;
    min-height:64px;padding:.55rem .4rem;border:1.5px solid #e2e8f0;border-radius:12px;
    background:#fff;font-size:.78rem;font-weight:750;color:#334155;transition:all .15s;position:relative
}
.ga-freq-ico{font-size:1.05rem;color:#64748b}
.ga-freq-check{display:none;position:absolute;top:6px;right:6px;color:var(--theme-primary);font-size:.9rem}
.ga-freq-card input:checked + .ga-freq-face{
    border-color:var(--theme-primary);background:#ecfdf5;color:#0f766e;
    box-shadow:0 0 0 3px rgba(13,148,136,.12)
}
.ga-freq-card input:checked + .ga-freq-face .ga-freq-check{display:inline}
.ga-freq-card input:checked + .ga-freq-face .ga-freq-ico{color:var(--theme-primary)}
.ga-weekday-row{display:flex;flex-wrap:wrap;gap:8px}
.ga-chip{margin:0;cursor:pointer;position:relative}
.ga-chip input{position:absolute;opacity:0;pointer-events:none}
.ga-chip span{
    display:inline-flex;align-items:center;justify-content:center;min-width:42px;height:34px;padding:0 10px;
    border:1px solid #e2e8f0;border-radius:8px;background:#fff;font-size:.78rem;font-weight:700;color:#475569
}
.ga-chip input:checked + span{border-color:var(--theme-primary);background:#ecfdf5;color:#0f766e}
.ga-time-wrap{position:relative}
.ga-time-wrap > i{
    position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.95rem;z-index:1;pointer-events:none
}
.ga-time-wrap .form-select{padding-left:36px}
.ga-date-row{
    display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr) auto;gap:12px;align-items:end
}
.ga-date-col{min-width:0}
.ga-date-check{padding-bottom:8px;white-space:nowrap}
.ga-dp{position:relative}
.ga-dp .form-control,
.ga-dp .flatpickr-input,
.ga-dp .form-control.input,
.ga-dp input.flatpickr-alt{
    width:100%;min-height:42px;height:42px;padding-right:38px;cursor:pointer;background:#fff;
    border-radius:10px;border:1px solid #d1d5db;font-size:.86rem;color:#0f172a;box-sizing:border-box
}
.ga-dp .flatpickr-input[readonly],
.ga-dp input.flatpickr-alt{background:#fff}
.ga-dp > i{
    position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:1rem;
    pointer-events:none;z-index:2
}
.ga-dp.is-disabled{opacity:.55;pointer-events:none}
.ga-dp.is-disabled input{background:#f8fafc}
.ga-check{
    display:inline-flex;align-items:center;gap:8px;font-size:.82rem;font-weight:650;color:#334155;
    cursor:pointer;user-select:none;margin:0
}
.ga-check input{width:16px;height:16px;accent-color:var(--theme-primary);margin:0}
.ga-format-row{display:flex;flex-wrap:nowrap;gap:18px;align-items:center}
.ga-format-opt{
    display:inline-flex;align-items:center;gap:8px;margin:0;cursor:pointer;position:relative;
    font-size:.84rem;font-weight:700;color:#334155
}
.ga-format-opt input{position:absolute;opacity:0;pointer-events:none}
.ga-format-dot{
    width:16px;height:16px;border-radius:50%;border:2px solid #cbd5e1;background:#fff;flex:0 0 auto;
    position:relative
}
.ga-format-opt input:checked ~ .ga-format-dot{
    border-color:var(--theme-primary);background:var(--theme-primary);
    box-shadow:inset 0 0 0 3px #fff
}
.ga-format-opt i{font-size:1.05rem;color:#64748b}
.ga-format-opt input:checked ~ i{color:var(--theme-primary)}
.ga-format-opt input:checked ~ span{color:#0f766e}
.ga-recipient-box{
    border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;min-height:42px;background:#fff;
    display:flex;flex-wrap:wrap;gap:6px;align-items:center
}
.ga-recipient-box:focus-within{border-color:var(--theme-primary);box-shadow:0 0 0 3px var(--theme-primary-ring)}
.ga-recipient-tags{display:flex;flex-wrap:wrap;gap:6px}
.ga-recipient-tag{
    display:inline-flex;align-items:center;gap:6px;padding:.28rem .6rem;border-radius:999px;
    background:#ecfdf5;color:#0f766e;font-size:.76rem;font-weight:700;border:1px solid #a7f3d0
}
.ga-recipient-tag button{
    border:0;background:transparent;color:#0f766e;padding:0;line-height:1;cursor:pointer;font-size:.9rem
}
.ga-recipient-input{border:0;outline:0;flex:1 1 160px;min-width:140px;font-size:.84rem;padding:.2rem;background:transparent}
.ga-sched-side{display:flex;flex-direction:column;gap:12px;position:sticky;top:0}
.ga-sched-summary{
    background:#ecfdf5;border:1px solid #a7f3d0;border-radius:14px;padding:1rem 1.05rem
}
.ga-sched-summary h6{margin:0 0 .85rem;font-size:.9rem;font-weight:800;color:#065f46}
.ga-sum-list{margin:0;display:flex;flex-direction:column;gap:10px}
.ga-sum-list > div{
    display:grid;grid-template-columns:92px minmax(0,1fr);gap:8px;align-items:start;font-size:.8rem
}
.ga-sum-list dt{margin:0;font-weight:650;color:#64748b}
.ga-sum-list dd{margin:0;font-weight:750;color:#0f172a;word-break:break-word}
.ga-sched-info{
    display:flex;align-items:flex-start;gap:8px;background:#eff6ff;border:1px solid #bfdbfe;
    border-radius:12px;padding:.85rem .9rem;color:#1e40af;font-size:.76rem;line-height:1.45
}
.ga-sched-info i{flex:0 0 auto;margin-top:1px}
.ga-sched-info p{margin:0}
.ga-sched-foot{
    border-top:1px solid #eef2f7;padding:.9rem 1.25rem;gap:10px;background:#fff;
    display:flex;align-items:center;justify-content:flex-end;flex-wrap:wrap
}
.ga-sched-manage-link{
    font-size:.8rem;font-weight:700;color:#2563eb;text-decoration:none;
    display:inline-flex;align-items:center;gap:6px
}
.ga-sched-manage-link:hover{text-decoration:underline;color:#1d4ed8}
.ga-sched-cancel{height:42px;border-radius:10px;font-weight:700;padding:0 16px}
.ga-sched-submit{
    background:var(--theme-primary);border-color:var(--theme-primary);color:#fff;font-weight:700;
    display:inline-flex;align-items:center;gap:7px;border-radius:10px;height:42px;padding:0 16px
}
.ga-sched-submit:hover{filter:brightness(1.05);color:#fff;background:var(--theme-primary)}
#gaScheduleModal .flatpickr-calendar{box-shadow:0 12px 28px rgba(15,23,42,.16);border:1px solid #e2e8f0}
@media (max-width:991.98px){
    .ga-sched-grid{grid-template-columns:1fr}
    .ga-sched-side{position:static}
    .ga-freq-grid{grid-template-columns:1fr 1fr}
    .ga-date-row{grid-template-columns:1fr 1fr;gap:10px}
    .ga-date-check{grid-column:1 / -1;padding-bottom:0}
}
@media (max-width:576px){
    .ga-freq-grid{grid-template-columns:1fr 1fr}
    .ga-date-row{grid-template-columns:1fr}
    .ga-format-row{flex-wrap:wrap;gap:12px}
    .ga-sched-body{max-height:min(68vh,560px)}
}

/* ---------- Responsive ---------- */
@media (max-width:1400px){
    .ga-hub .ga-filters{grid-template-columns:repeat(4,minmax(0,1fr)) auto}
    .ga-hub .ga-filter-actions{grid-column:1/-1;justify-content:flex-end}
    .ga-hub .pd-kpi-row{grid-template-columns:repeat(3,minmax(0,1fr))}
}
@media (max-width:1200px){
    .ga-hub .ga-filters{grid-template-columns:repeat(3,minmax(0,1fr))}
    .ga-hub .ga-filter-actions{grid-column:1/-1;justify-content:flex-end}
}
@media (max-width:1100px){
    .ga-hub .ga-hero{flex-wrap:wrap}
    .ga-hub .ga-hero-left{flex:1 1 100%;max-width:100%}
    .ga-hub .ga-hero-actions{
        width:100%;margin-left:0;padding-top:0;
        display:grid;grid-template-columns:1fr 1fr;gap:10px
    }
    .ga-hub .ga-hero-actions .dropdown{min-width:0;width:100%}
    .ga-hub .ga-hero-actions .ga-btn,
    .ga-hub .ga-hero-actions .dropdown .ga-btn{width:100%;justify-content:center}
}
@media (max-width:992px){
    .ga-hub .ga-hero{gap:.85rem}
    .ga-hub .panel-h{flex-wrap:wrap;align-items:center}
    .ga-hub .panel-h .view-all{margin-left:auto}
    .ga-hub .chart-legend{justify-content:flex-start;width:100%}
    .ga-hub .conv-badge{margin-top:.25rem}
    .ga-hub .funnel-layout{grid-template-columns:1fr;gap:12px}
    .ga-hub .status-grid{grid-template-columns:1fr;min-height:0;gap:12px}
    .ga-hub .donut-wrap{width:128px;height:128px}
    .ga-hub .chart-box{height:200px}
    .ga-hub .pd-kpi-row{grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
    .ga-hub .ga-filter-actions{
        grid-column:1/-1;width:100%;height:auto;display:grid;grid-template-columns:1.35fr 1fr;gap:10px;
        align-items:stretch;white-space:normal
    }
    .ga-hub .ga-btn-apply,.ga-hub .ga-btn-reset{width:100%;box-sizing:border-box}
}
@media (max-width:768px){
    .ga-hub .ga-hero-ico{width:40px;height:40px;font-size:1.15rem;border-radius:10px}
    .ga-hub .ga-hero-copy .pd-title{font-size:1.28rem}
    .ga-hub .ga-hero-copy .pd-sub{font-size:.8rem}
    .ga-hub .ga-btn{height:44px;min-height:44px;padding:0 14px;font-size:.86rem}
    .ga-hub .btn-full{display:none}
    .ga-hub .btn-short{display:inline}
    .ga-hub .sec-tabs{
        margin:0 -4px 1rem;padding:2px 4px 10px;
        mask-image:linear-gradient(90deg,#000 0%,#000 calc(100% - 28px),transparent 100%);
        -webkit-mask-image:linear-gradient(90deg,#000 0%,#000 calc(100% - 28px),transparent 100%)
    }
    .ga-hub .sec-tab{min-height:44px;padding:.55rem .95rem;font-size:.8rem;gap:6px}
    .ga-hub .sec-tab i{font-size:.88rem}
    .ga-hub .ga-filters{
        grid-template-columns:1fr 1fr;gap:10px;padding:12px;margin-bottom:.85rem
    }
    .ga-hub .ga-filter-actions{
        grid-column:1/-1;width:100%;display:grid;grid-template-columns:1.4fr 1fr;gap:10px;
        margin-top:4px;padding-top:10px;border-top:1px solid #f1f5f9
    }
    .ga-hub .ga-btn-apply,.ga-hub .ga-btn-reset{height:44px;min-height:44px;font-size:.88rem}
    .ga-hub .ga-kpi{min-height:108px}
    .ga-hub .pd-kpi-row{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-bottom:.9rem}
    .ga-hub .ga-kpi{padding:11px 12px 9px;border-radius:12px;min-width:0}
    .ga-hub .ga-kpi-ico{width:26px;height:26px;font-size:.82rem;border-radius:7px}
    .ga-hub .ga-kpi-main{margin-top:6px}
    .ga-hub .ga-kpi-value{font-size:1.08rem}
    .ga-hub .ga-kpi-label{font-size:.7rem}
    .ga-hub .ga-kpi-delta{font-size:.64rem;flex-wrap:wrap;margin-top:6px;gap:2px 4px}
    .ga-hub .ga-kpi-delta .vs{font-size:.6rem}
    .ga-hub .ga-kpi-wave{width:52%;height:60%;opacity:.85}
    .ga-hub .panel-h{padding:.7rem .8rem}
    .ga-hub .panel-b{padding:.7rem .8rem .8rem}
    .ga-hub .panel-h h5{font-size:.84rem}
    .ga-hub .panel-h .sub{font-size:.68rem}
    .ga-hub .tgt-tabs{padding:4px;border-radius:12px}
    .ga-hub .tgt-tab{
        flex:1 0 auto;min-width:4.6rem;min-height:44px;padding:.6rem .7rem;font-size:.76rem
    }
    .ga-hub .tgt-table th,.ga-hub .tgt-table td{padding:.45rem .4rem;font-size:.72rem}
    .ga-hub .tgt-ach{gap:6px}
    .ga-hub .tgt-ach .pct{min-width:2rem;font-size:.7rem}
    .ga-hub a.branch-row{grid-template-columns:minmax(100px,40%) minmax(40px,1fr) auto;gap:8px}
    .ga-hub .branch-row .nm,.ga-hub .branch-row .amt{font-size:.76rem}
    .ga-hub .alert-item{
        grid-template-columns:28px minmax(0,1fr) auto;gap:10px;padding:.75rem 2px;min-height:52px
    }
    .ga-hub .alert-item .prio{grid-column:2;justify-self:start;margin-top:2px}
    .ga-hub .alert-item .cnt{grid-column:3;grid-row:1 / span 2;align-self:center}
    .ga-hub .view-all{min-height:40px;padding:.4rem .65rem}
    .ga-hub .dropdown-menu{width:100%;min-width:100%}
}
@media (max-width:576px){
    .ga-hub .ga-hero-actions{grid-template-columns:1fr 1fr;gap:8px}
    .ga-hub .ga-filters{grid-template-columns:1fr}
    .ga-hub .ga-filter-actions{grid-template-columns:1fr;gap:8px}
    .ga-hub .ga-btn-apply{order:1}
    .ga-hub .ga-btn-reset{order:2}
    .ga-hub .ga-filter-actions .btn-full{display:inline}
    .ga-hub .ga-filter-actions .btn-short{display:none}
    .ga-hub .sec-tab i{display:none}
    .ga-hub .sec-tab{padding:.6rem 1rem}
    .ga-hub .pd-kpi-row{grid-template-columns:1fr 1fr;gap:8px}
    .ga-hub .ga-kpi-delta .vs{display:none}
    .ga-hub a.funnel-seg{height:26px;font-size:.62rem;padding:0 8px}
    .ga-hub .panel-h-left{gap:8px}
    .ga-hub .panel-ico{width:28px;height:28px;font-size:.85rem}
    .ga-hub .table-responsive{margin:0 -2px;overflow-x:auto;-webkit-overflow-scrolling:touch}
    .ga-hub .tgt-table{min-width:360px}
    .ga-hub .tgt-tab{min-width:5rem;font-size:.78rem}
}
</style>
@endpush

@section('content')
<div class="container-fluid p-4 pd-page ga-hub">
    <div class="ga-hero">
        <div class="ga-hero-left">
            <span class="ga-hero-ico" aria-hidden="true"><i class="bi bi-bar-chart-fill"></i></span>
            <div class="ga-hero-copy">
                <h1 class="pd-title">Analytics Dashboard</h1>
                <p class="pd-sub">Real-time insights on leads, processing, disbursement, revenue and team performance.</p>
            </div>
        </div>
        <div class="ga-hero-actions">
            <div class="dropdown">
                <button class="ga-btn ga-btn-export dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download"></i>
                    <span class="btn-full">Export</span>
                    <span class="btn-short">Export</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end ga-export-menu" id="gaExportMenu">
                    <li><h6 class="dropdown-header">Dashboard Export</h6></li>
                    <li>
                        <a class="dropdown-item ga-export-link" data-format="pdf"
                           href="{{ route('goal.analytics.export', array_merge(request()->except(['partial','format']), ['format' => 'pdf'])) }}">
                            <i class="bi bi-file-earmark-pdf-fill me-2 text-danger"></i>Export PDF
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item ga-export-link" data-format="xlsx"
                           href="{{ route('goal.analytics.export', array_merge(request()->except(['partial','format']), ['format' => 'xlsx'])) }}">
                            <i class="bi bi-file-earmark-excel-fill me-2 text-success"></i>Export Excel
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item ga-export-link" data-format="csv"
                           href="{{ route('goal.analytics.export', array_merge(request()->except(['partial','format']), ['format' => 'csv'])) }}">
                            <i class="bi bi-filetype-csv me-2 text-primary"></i>Export CSV
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><h6 class="dropdown-header">MIS Reports</h6></li>
                    <li><a class="dropdown-item" href="{{ route('goal.branch-mis.export', ['date_from' => $f['date_from'] ?? null, 'date_to' => $f['date_to'] ?? null]) }}"><i class="bi bi-building me-2"></i>Branch MIS</a></li>
                    <li><a class="dropdown-item" href="{{ route('goal.revenue-ledger.export', ['date_from' => $f['date_from'] ?? null, 'date_to' => $f['date_to'] ?? null]) }}"><i class="bi bi-cash-stack me-2"></i>Revenue Ledger</a></li>
                    <li><a class="dropdown-item" href="{{ route('goal.payouts.export') }}"><i class="bi bi-wallet2 me-2"></i>Payout Ageing</a></li>
                    <li><a class="dropdown-item" href="{{ route('goal.cases.export') }}"><i class="bi bi-folder2-open me-2"></i>Loan Cases</a></li>
                </ul>
            </div>
            <button type="button" class="ga-btn ga-btn-schedule" data-bs-toggle="modal" data-bs-target="#gaScheduleModal">
                <i class="bi bi-calendar3"></i>
                <span class="btn-full">Schedule Report</span>
                <span class="btn-short">Schedule</span>
            </button>
        </div>
    </div>

    <div class="sec-tabs">
        @foreach($sections as $key => [$label, $icon])
            <a class="sec-tab {{ $section === $key ? 'active' : '' }}"
               href="{{ route('goal.analytics', array_merge($qs, ['section' => $key, 'target_tab' => $targetTab])) }}">
                <i class="bi {{ $icon }}"></i> {{ $label }}
            </a>
        @endforeach
    </div>

    <form method="get" action="{{ route('goal.analytics') }}" class="ga-filters" id="gaFiltersForm">
        <input type="hidden" name="section" value="{{ $section }}">
        <input type="hidden" name="target_tab" value="{{ $targetTab }}">
        <input type="hidden" name="date_from" id="gaDateFrom" value="{{ $f['date_from'] ?? '' }}">
        <input type="hidden" name="date_to" id="gaDateTo" value="{{ $f['date_to'] ?? '' }}">

        <div>
            <label>Financial Year</label>
            <select name="fy" id="gaFy" class="form-select">
                @foreach(($dash['fy_options'] ?? []) as $fy)
                    <option value="{{ $fy }}" @selected(($f['fy'] ?? '') === $fy)>{{ str_replace('-', ' - ', $fy) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Date Range</label>
            <div class="ga-date-wrap">
                <i class="bi bi-calendar3"></i>
                <input type="text" id="gaDateRange" class="form-control" placeholder="Select date range" value="{{ $dateRangeLabel }}" readonly>
            </div>
        </div>
        <div>
            <label>Region</label>
            <select name="region" class="form-select">
                <option value="">All Regions</option>
                @foreach(($opts['regions'] ?? []) as $u)
                    <option value="{{ $u->id }}" @selected((string)($f['region'] ?? '') === (string)$u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Cluster</label>
            <select name="cluster" class="form-select">
                <option value="">All Clusters</option>
                @foreach(($opts['clusters'] ?? []) as $u)
                    <option value="{{ $u->id }}" @selected((string)($f['cluster'] ?? '') === (string)$u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Branch</label>
            <select name="branch" class="form-select">
                <option value="">All Branches</option>
                @foreach(($opts['branches'] ?? []) as $u)
                    <option value="{{ $u->id }}" @selected((string)($f['branch'] ?? '') === (string)$u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Product</label>
            <select name="product" class="form-select">
                <option value="">All Products</option>
                @foreach(($opts['products'] ?? []) as $p)
                    <option value="{{ $p }}" @selected(($f['product'] ?? '') === $p)>{{ $p }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Owner</label>
            <select name="owner" class="form-select">
                <option value="">All Owners</option>
                @foreach(($opts['owners'] ?? []) as $o)
                    <option value="{{ $o->id }}" @selected((string)($f['owner'] ?? '') === (string)$o->id)>{{ $o->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="ga-filter-actions">
            <button type="submit" class="ga-btn-apply">
                <i class="bi bi-check2-circle"></i>
                <span class="btn-full">Apply Filters</span>
                <span class="btn-short">Apply</span>
            </button>
            <button type="button" class="ga-btn-reset" id="gaResetBtn" data-reset-url="{{ route('goal.analytics', ['section' => $section]) }}">
                <i class="bi bi-arrow-counterclockwise"></i> Reset
            </button>
        </div>
    </form>

    <div id="gaResults" class="ga-results" aria-live="polite">
        @include('goal.partials.analytics_results', ['dash' => $dash])
    </div>
</div>

@include('goal.partials.schedule_report_modal')
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const analyticsUrl = @json(route('goal.analytics'));
    const form = document.getElementById('gaFiltersForm');
    const results = document.getElementById('gaResults');
    const applyBtn = form ? form.querySelector('.ga-btn-apply') : null;
    const resetBtn = document.getElementById('gaResetBtn');
    let trendChart = null;
    let statusChart = null;
    let fpInstance = null;
    let loading = false;

    function niceMax(v, fallback) {
        if (!v || v <= 0) return fallback;
        const pad = v * 1.15;
        if (pad >= 1) return Math.ceil(pad);
        const step = pad <= 0.05 ? 0.01 : (pad <= 0.2 ? 0.05 : 0.1);
        return Math.ceil(pad / step) * step;
    }

    function destroyCharts() {
        if (trendChart) { try { trendChart.destroy(); } catch (e) {} trendChart = null; }
        if (statusChart) { try { statusChart.destroy(); } catch (e) {} statusChart = null; }
    }

    function readChartData() {
        const el = document.getElementById('gaChartData');
        if (!el) return { trend: {}, case_status: {} };
        try { return JSON.parse(el.textContent || '{}'); }
        catch (e) { return { trend: {}, case_status: {} }; }
    }

    function initTargetTabs() {
        const targetPanel = document.getElementById('gaTargetPanel');
        if (!targetPanel) return;
        const tabs = targetPanel.querySelectorAll('.tgt-tab');
        const panes = targetPanel.querySelectorAll('.tgt-pane');
        const hiddenTab = document.querySelector('#gaFiltersForm input[name="target_tab"]');
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                const key = tab.getAttribute('data-target-tab');
                if (!key) return;
                tabs.forEach(function (t) {
                    const on = t === tab;
                    t.classList.toggle('active', on);
                    t.setAttribute('aria-selected', on ? 'true' : 'false');
                });
                panes.forEach(function (p) {
                    p.classList.toggle('is-active', p.getAttribute('data-target-pane') === key);
                });
                if (hiddenTab) hiddenTab.value = key;
                try {
                    const url = new URL(window.location.href);
                    url.searchParams.set('target_tab', key);
                    window.history.replaceState({}, '', url.toString());
                } catch (e) {}
            });
        });
    }

    function initCharts() {
        destroyCharts();
        if (!window.Chart) return;
        const data = readChartData();
        const trend = data.trend || {};
        const caseStatus = data.case_status || {};
        const teal = getComputedStyle(document.documentElement).getPropertyValue('--theme-primary').trim() || '#0d9488';
        const blue = '#2563eb';
        const disbData = trend.disbursement_cr || [];
        const revData = trend.revenue_lac || [];
        const maxDisb = Math.max(0, ...disbData.map(Number));
        const maxRev = Math.max(0, ...revData.map(Number));

        const trendEl = document.getElementById('gaTrendChart');
        if (trendEl) {
            trendChart = new Chart(trendEl, {
                type: 'line',
                data: {
                    labels: trend.labels || [],
                    datasets: [
                        {
                            label: 'Revenue (₹ Lac)',
                            data: revData,
                            borderColor: blue,
                            backgroundColor: 'rgba(37,99,235,.16)',
                            borderWidth: 2.5,
                            tension: .35,
                            fill: 'origin',
                            pointRadius: 4,
                            pointHoverRadius: 5,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: blue,
                            pointBorderWidth: 2,
                            yAxisID: 'y1',
                            order: 2
                        },
                        {
                            label: 'Disbursement (₹ Cr)',
                            data: disbData,
                            borderColor: teal,
                            backgroundColor: 'transparent',
                            borderWidth: 2.5,
                            tension: .35,
                            fill: false,
                            pointRadius: 4,
                            pointHoverRadius: 5,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: teal,
                            pointBorderWidth: 2,
                            yAxisID: 'y',
                            order: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#fff',
                            titleColor: '#0f172a',
                            bodyColor: '#334155',
                            borderColor: '#e2e8f0',
                            borderWidth: 1,
                            padding: 10,
                            displayColors: true,
                            callbacks: {
                                title: function (items) {
                                    const i = items[0]?.dataIndex ?? 0;
                                    return (trend.tooltip_labels && trend.tooltip_labels[i])
                                        ? trend.tooltip_labels[i]
                                        : (trend.labels?.[i] || '');
                                },
                                label: function (ctx) {
                                    const v = Number(ctx.parsed.y || 0);
                                    if (ctx.dataset.yAxisID === 'y') {
                                        return ' Disbursement: ₹ ' + v.toFixed(2) + ' Cr';
                                    }
                                    return ' Revenue: ₹ ' + v.toFixed(2) + ' Lac';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            position: 'left',
                            beginAtZero: true,
                            suggestedMax: niceMax(maxDisb, 1),
                            title: {
                                display: true,
                                text: 'Disbursement (₹ Cr)',
                                color: '#64748b',
                                font: { size: 10, weight: '600' }
                            },
                            ticks: { font: { size: 10 }, color: '#94a3b8', maxTicksLimit: 8 },
                            grid: { color: '#f1f5f9' }
                        },
                        y1: {
                            type: 'linear',
                            position: 'right',
                            beginAtZero: true,
                            suggestedMax: niceMax(maxRev, 1),
                            title: {
                                display: true,
                                text: 'Revenue (₹ Lac)',
                                color: '#64748b',
                                font: { size: 10, weight: '600' }
                            },
                            ticks: { font: { size: 10 }, color: '#94a3b8', maxTicksLimit: 8 },
                            grid: { drawOnChartArea: false }
                        },
                        x: {
                            ticks: { font: { size: 10 }, color: '#94a3b8' },
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        const statusEl = document.getElementById('gaStatusChart');
        if (statusEl) {
            const items = caseStatus.items || [];
            const values = items.map(i => Number(i.count) || 0);
            const hasData = values.some(v => v > 0);
            statusChart = new Chart(statusEl, {
                type: 'doughnut',
                data: {
                    labels: items.map(i => i.label),
                    datasets: [{
                        data: hasData ? values : [1],
                        backgroundColor: hasData ? items.map(i => i.color) : ['#e2e8f0'],
                        borderColor: '#fff',
                        borderWidth: 3,
                        hoverOffset: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    },
                    onClick: function (_evt, elements) {
                        if (!hasData || !elements.length) return;
                        const item = items[elements[0].index];
                        if (item && item.href) window.location.href = item.href;
                    }
                }
            });
            statusEl.style.cursor = hasData ? 'pointer' : 'default';
        }
    }

    const fyRanges = @json($dash['fy_range_map'] ?? []);

    function applyGaFyDates(fyKey) {
        const range = fyRanges[fyKey];
        const fromEl = document.getElementById('gaDateFrom');
        const toEl = document.getElementById('gaDateTo');
        if (!range || !fromEl || !toEl) return;
        fromEl.value = range.date_from || '';
        toEl.value = range.date_to || '';
        if (fpInstance) {
            try { fpInstance.setDate([range.date_from, range.date_to], true); } catch (e) {}
        }
    }

    function initDateRange() {
        const rangeEl = document.getElementById('gaDateRange');
        const fromEl = document.getElementById('gaDateFrom');
        const toEl = document.getElementById('gaDateTo');
        const fyEl = document.getElementById('gaFy');
        if (!rangeEl || !window.flatpickr) return;
        if (fpInstance) {
            try { fpInstance.destroy(); } catch (e) {}
            fpInstance = null;
        }
        const defaults = [];
        if (fromEl && fromEl.value) defaults.push(fromEl.value);
        if (toEl && toEl.value) defaults.push(toEl.value);
        fpInstance = flatpickr(rangeEl, {
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
        if (fyEl && !fyEl.dataset.fyBound) {
            fyEl.dataset.fyBound = '1';
            fyEl.addEventListener('change', function () {
                applyGaFyDates(fyEl.value);
            });
        }
    }

    function setLoading(on) {
        loading = !!on;
        if (results) results.classList.toggle('is-loading', loading);
        if (applyBtn) {
            applyBtn.disabled = loading;
            applyBtn.classList.toggle('is-busy', loading);
        }
        if (resetBtn) resetBtn.disabled = loading;
    }

    function syncUrl(params) {
        try {
            const url = new URL(analyticsUrl, window.location.origin);
            params.forEach(function (value, key) {
                if (value === null || value === undefined || value === '') url.searchParams.delete(key);
                else url.searchParams.set(key, value);
            });
            url.searchParams.delete('partial');
            window.history.replaceState({}, '', url.pathname + url.search);
        } catch (e) {}
    }

    function loadResults(params, pushUrl) {
        if (!results || loading) return Promise.resolve();
        setLoading(true);
        const qs = new URLSearchParams(params);
        qs.set('partial', '1');
        return fetch(analyticsUrl + '?' + qs.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            },
            credentials: 'same-origin'
        }).then(function (res) {
            if (!res.ok) throw new Error('Failed to load analytics');
            return res.text();
        }).then(function (html) {
            results.innerHTML = html;
            if (pushUrl !== false) {
                const clean = new URLSearchParams(params);
                clean.delete('partial');
                syncUrl(clean);
            }
            initTargetTabs();
            initCharts();
        }).catch(function () {
            results.innerHTML = '<div class="alert alert-warning mb-0">Unable to refresh analytics. Please try again.</div>';
        }).finally(function () {
            setLoading(false);
        });
    }

    function formParams() {
        if (!form) return new URLSearchParams();
        return new URLSearchParams(new FormData(form));
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            loadResults(formParams());
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            if (loading || !form) return;
            const sectionInput = form.querySelector('input[name="section"]');
            const section = sectionInput ? sectionInput.value : 'overview';
            form.querySelectorAll('select').forEach(function (sel) {
                sel.selectedIndex = 0;
            });
            const fromEl = document.getElementById('gaDateFrom');
            const toEl = document.getElementById('gaDateTo');
            if (fromEl) fromEl.value = '';
            if (toEl) toEl.value = '';
            if (fpInstance) {
                try { fpInstance.clear(); } catch (e) {}
            }
            const targetTab = form.querySelector('input[name="target_tab"]');
            if (targetTab) targetTab.value = 'employee';
            const params = new URLSearchParams({ section: section, target_tab: 'employee' });
            loadResults(params);
        });
    }

    initDateRange();
    initTargetTabs();
    initCharts();

    // Keep Export PDF/Excel/CSV links in sync with current filters
    document.querySelectorAll('.ga-export-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            const format = link.getAttribute('data-format') || 'pdf';
            const params = form ? formParams() : new URLSearchParams(window.location.search);
            params.delete('partial');
            params.set('format', format);
            link.href = @json(route('goal.analytics.export')) + '?' + params.toString();
        });
    });

    /* ---- Schedule Report modal ---- */
    (function initScheduleModal() {
        const modal = document.getElementById('gaScheduleModal');
        const schedForm = document.getElementById('gaScheduleForm');
        if (!modal || !schedForm) return;

        const recipients = [];
        const tagsEl = document.getElementById('srRecipientTags');
        const inputEl = document.getElementById('srRecipientInput');
        const hiddenEl = document.getElementById('srRecipients');
        const boxEl = document.getElementById('srRecipientBox');
        const noEnd = document.getElementById('srNoEnd');
        const startHidden = document.getElementById('srStartDate');
        const endHidden = document.getElementById('srEndDate');
        const startUi = document.getElementById('srStartDateUi');
        const endUi = document.getElementById('srEndDateUi');
        const endDpWrap = endUi ? endUi.closest('.ga-dp') : null;
        let startFp = null;
        let endFp = null;

        function fmtDisplay(ymd) {
            if (!ymd) return '—';
            const p = String(ymd).split('-');
            if (p.length !== 3) return ymd;
            return p[2] + '-' + p[1] + '-' + p[0];
        }

        function syncRecipients() {
            if (hiddenEl) hiddenEl.value = recipients.join(',');
            if (tagsEl) {
                tagsEl.innerHTML = recipients.map(function (email, idx) {
                    return '<span class="ga-recipient-tag">' + email +
                        '<button type="button" data-idx="' + idx + '" aria-label="Remove">&times;</button></span>';
                }).join('');
            }
            updateSummary();
        }

        function addRecipient(raw) {
            const email = String(raw || '').trim().toLowerCase();
            if (!email) return;
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                alert('Enter a valid email address.');
                return;
            }
            if (recipients.indexOf(email) === -1) recipients.push(email);
            if (inputEl) inputEl.value = '';
            syncRecipients();
        }

        function updateSummary() {
            const typeSel = document.getElementById('srReportType');
            const nameEl = document.getElementById('srReportName');
            const timeSel = document.getElementById('srRunTime');
            const freq = (schedForm.querySelector('input[name="frequency"]:checked') || {}).value || 'daily';
            const format = (schedForm.querySelector('input[name="format"]:checked') || {}).value || 'pdf';
            const set = function (id, val) {
                const el = document.getElementById(id);
                if (el) el.textContent = val;
            };
            set('srSumType', typeSel ? typeSel.options[typeSel.selectedIndex].text : '—');
            set('srSumName', nameEl && nameEl.value ? nameEl.value : '—');
            set('srSumFreq', freq.charAt(0).toUpperCase() + freq.slice(1));
            set('srSumTime', timeSel ? timeSel.options[timeSel.selectedIndex].text : '—');
            set('srSumStart', fmtDisplay(startHidden && startHidden.value));
            set('srSumEnd', noEnd && noEnd.checked ? 'No End Date' : fmtDisplay(endHidden && endHidden.value));
            set('srSumFormat', format === 'xlsx' ? 'Excel (XLSX)' : format.toUpperCase());
            set('srSumRecipients', recipients.length + ' Email(s)');
        }

        function toggleFreqExtras() {
            const freq = (schedForm.querySelector('input[name="frequency"]:checked') || {}).value || 'daily';
            schedForm.querySelectorAll('.ga-freq-extra').forEach(function (el) {
                el.hidden = el.getAttribute('data-freq') !== freq;
            });
            updateSummary();
        }

        function setEndEnabled(enabled) {
            if (endFp) {
                endFp.set('clickOpens', !!enabled);
                if (!enabled) {
                    endFp.close();
                    endFp.clear();
                }
                if (endFp.altInput) endFp.altInput.disabled = !enabled;
            }
            if (endUi) endUi.disabled = !enabled;
            if (endDpWrap) endDpWrap.classList.toggle('is-disabled', !enabled);
            if (!enabled && endHidden) endHidden.value = '';
        }

        function initDatePickers() {
            if (!window.flatpickr || !startUi || !endUi) return;
            if (startFp) { try { startFp.destroy(); } catch (e) {} startFp = null; }
            if (endFp) { try { endFp.destroy(); } catch (e) {} endFp = null; }

            startFp = flatpickr(startUi, {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd-m-Y',
                allowInput: false,
                defaultDate: startHidden && startHidden.value ? startHidden.value : 'today',
                disableMobile: true,
                appendTo: modal,
                onReady: function (selectedDates, dateStr) {
                    if (startHidden) startHidden.value = dateStr || (startHidden.value || '');
                },
                onChange: function (selectedDates, dateStr) {
                    if (!startHidden) return;
                    startHidden.value = dateStr || '';
                    if (endFp && selectedDates[0]) endFp.set('minDate', selectedDates[0]);
                    updateSummary();
                }
            });

            endFp = flatpickr(endUi, {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd-m-Y',
                allowInput: false,
                defaultDate: endHidden && endHidden.value ? endHidden.value : null,
                minDate: startHidden && startHidden.value ? startHidden.value : 'today',
                disableMobile: true,
                appendTo: modal,
                onReady: function (_selectedDates, dateStr) {
                    if (endHidden && dateStr) endHidden.value = dateStr;
                },
                onChange: function (_selectedDates, dateStr) {
                    if (!endHidden) return;
                    endHidden.value = dateStr || '';
                    updateSummary();
                }
            });
        }

        function syncFilterSnapshot() {
            if (!form) return;
            ['fy', 'date_from', 'date_to', 'region', 'cluster', 'branch', 'product', 'owner'].forEach(function (name) {
                let src = form.querySelector('[name="' + name + '"]');
                if (!src && name === 'date_from') src = document.getElementById('gaDateFrom');
                if (!src && name === 'date_to') src = document.getElementById('gaDateTo');
                const dest = schedForm.querySelector('input[name="' + name + '"]');
                if (!dest) return;
                if (src) dest.value = src.value || '';
            });
            const section = form.querySelector('input[name="section"]');
            const typeSel = document.getElementById('srReportType');
            if (section && typeSel && Array.from(typeSel.options).some(function (o) { return o.value === section.value; })) {
                typeSel.value = section.value;
            }
        }

        if (tagsEl) {
            tagsEl.addEventListener('click', function (e) {
                const btn = e.target.closest('button[data-idx]');
                if (!btn) return;
                recipients.splice(Number(btn.getAttribute('data-idx')), 1);
                syncRecipients();
            });
        }
        if (inputEl) {
            inputEl.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    addRecipient(inputEl.value.replace(/,/g, ''));
                }
            });
            inputEl.addEventListener('blur', function () {
                if (inputEl.value.trim()) addRecipient(inputEl.value);
            });
        }
        if (boxEl) {
            boxEl.addEventListener('click', function () {
                if (inputEl) inputEl.focus();
            });
        }
        if (noEnd) {
            noEnd.addEventListener('change', function () {
                setEndEnabled(!noEnd.checked);
                updateSummary();
            });
        }

        schedForm.querySelectorAll('input[name="frequency"]').forEach(function (el) {
            el.addEventListener('change', toggleFreqExtras);
        });
        schedForm.querySelectorAll('input, select').forEach(function (el) {
            el.addEventListener('change', updateSummary);
            el.addEventListener('input', updateSummary);
        });

        modal.addEventListener('shown.bs.modal', function () {
            syncFilterSnapshot();
            initDatePickers();
            setEndEnabled(!(noEnd && noEnd.checked));
            toggleFreqExtras();
            updateSummary();
        });

        if (sessionStorage.getItem('gaOpenSchedule') === '1') {
            sessionStorage.removeItem('gaOpenSchedule');
            const prefType = sessionStorage.getItem('gaScheduleType');
            if (prefType) {
                sessionStorage.removeItem('gaScheduleType');
                const typeSel = document.getElementById('srReportType');
                if (typeSel) {
                    const opt = Array.from(typeSel.options).find(function (o) { return o.value === prefType; });
                    if (opt) {
                        typeSel.value = prefType;
                        typeSel.dispatchEvent(new Event('change'));
                    }
                }
            }
            setTimeout(function () {
                if (window.bootstrap && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                }
            }, 250);
        }

        schedForm.addEventListener('submit', function (e) {
            if (inputEl && inputEl.value.trim()) addRecipient(inputEl.value);
            if (!recipients.length) {
                e.preventDefault();
                alert('Add at least one recipient email.');
                return;
            }
            if (!startHidden || !startHidden.value) {
                e.preventDefault();
                alert('Select a start date.');
                return;
            }
            if (!(noEnd && noEnd.checked) && (!endHidden || !endHidden.value)) {
                e.preventDefault();
                alert('Select an end date or check No End Date.');
                return;
            }
            syncRecipients();
            const btn = document.getElementById('srSubmitBtn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creating…';
            }
        });

        toggleFreqExtras();
        syncRecipients();
    })();
})();
</script>
@endpush
