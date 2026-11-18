@extends('layouts.app')

@push('styles')
<style>
.gd-page{
    --gd-bg:#eef3f8;
    --gd-card:#ffffff;
    --gd-text:#0f172a;
    --gd-muted:#64748b;
    --gd-line:#e8eef5;
    --gd-blue:var(--theme-primary);
    --gd-pink:#ec4899;
    --gd-green:#16a34a;
    --gd-orange:#f97316;
    --gd-purple:#7c3aed;
    color:var(--gd-text);
    background:transparent;
}
.gd-page .welcome-row{margin-bottom:1.1rem}
.gd-page .welcome-card{
    position:relative;overflow:hidden;border:0;border-radius:18px;min-height:122px;height:100%;
    background:linear-gradient(105deg,var(--theme-100) 0%,var(--theme-200) 42%,var(--theme-300) 100%);
    box-shadow:0 10px 28px var(--theme-primary-ring);
    padding:1.2rem 1.4rem;display:flex;align-items:center;gap:1rem;
}
.gd-page .welcome-card::after{
    content:"";position:absolute;right:0;bottom:0;width:58%;height:100%;
    background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 420 160' fill='none'%3E%3Crect x='40' y='55' width='48' height='105' rx='4' fill='%2360a5fa' fill-opacity='.45'/%3E%3Crect x='98' y='30' width='58' height='130' rx='4' fill='%233b82f6' fill-opacity='.4'/%3E%3Crect x='166' y='48' width='44' height='112' rx='4' fill='%2393c5fd' fill-opacity='.55'/%3E%3Crect x='220' y='20' width='70' height='140' rx='4' fill='%232563eb' fill-opacity='.35'/%3E%3Crect x='300' y='42' width='52' height='118' rx='4' fill='%2360a5fa' fill-opacity='.5'/%3E%3C/svg%3E") no-repeat right bottom;
    background-size:contain;pointer-events:none;opacity:.9;
}
.gd-page .welcome-icon{
    width:52px;height:52px;border-radius:14px;background:#1e3a8a;color:#fff;
    display:inline-flex;align-items:center;justify-content:center;font-size:1.35rem;flex-shrink:0;
    box-shadow:0 6px 16px rgba(30,58,138,.25);position:relative;z-index:1;
}
.gd-page .welcome-copy{position:relative;z-index:1;max-width:72%}
.gd-page .welcome-copy h2{font-size:1.4rem;font-weight:800;color:#0f172a;margin:0;letter-spacing:-.02em}
.gd-page .welcome-copy .sub{font-size:.9rem;color:#334155;margin:.4rem 0 0;line-height:1.45}
.gd-page .role-badge{
    display:inline-flex;align-items:center;gap:.35rem;margin-top:.55rem;padding:.32rem .75rem;
    border-radius:999px;background:#0f172a;color:#fff;font-size:.72rem;font-weight:700;
}
.gd-page .pipe-strip{
    display:flex;flex-wrap:wrap;gap:6px;margin-top:.7rem;position:relative;z-index:1;
}
.gd-page .pipe-chip{
    height:30px;padding:0 10px;border-radius:999px;border:1px solid rgba(255,255,255,.55);
    background:rgba(255,255,255,.55);color:#1e3a8a;font-size:.72rem;font-weight:700;
    text-decoration:none;display:inline-flex;align-items:center;gap:5px;
}
.gd-page .pipe-chip:hover{background:#fff;color:#0f172a}
.gd-page a.metric-card{text-decoration:none;color:inherit;display:flex;flex-direction:column;cursor:pointer}
.gd-page a.metric-card:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(23,32,51,.09)}
.gd-page .control-card{
    background:var(--gd-card);border:1px solid var(--gd-line);border-radius:18px;
    box-shadow:0 6px 20px rgba(15,23,42,.05);padding:1.15rem 1.25rem;height:100%;
    display:flex;flex-direction:column;justify-content:center;gap:.9rem;
}
.gd-page .control-meta{display:flex;flex-wrap:wrap;gap:.75rem 1.2rem;align-items:center;justify-content:flex-end}
.gd-page .control-meta span{display:inline-flex;align-items:center;gap:.4rem;font-size:.84rem;font-weight:600;color:#475569}
.gd-page .control-meta .time{font-size:1.08rem;font-weight:800;color:#0f172a}
.gd-page .analytics-cta{
    display:inline-flex;align-items:center;justify-content:center;gap:.5rem;align-self:flex-end;
    padding:.62rem 1.15rem;border-radius:12px;background:var(--gd-blue);color:#fff;
    font-size:.86rem;font-weight:700;text-decoration:none;box-shadow:0 8px 18px var(--theme-primary-ring);
}
.gd-page .analytics-cta:hover{background:var(--theme-primary-hover);color:#fff}

/* KPI cards — compact reference (Image 2) */
.gd-page .metric-card{
    --kpi-accent:#f63383;
    --kpi-icon-bg:#fce3ef;
    --kpi-icon:#e91e63;
    --kpi-heading:#1555d8;
    --kpi-value:#172033;
    --kpi-sub:#64748b;
    --kpi-link:#1555d8;
    --kpi-wave-a:#f3e8ff;
    --kpi-wave-b:#f7d8f1;
    --kpi-wave-c:#ebc9f6;
    position:relative;
    display:flex;
    flex-direction:column;
    background:linear-gradient(160deg,#ffffff 0%,#faf8ff 55%,#ffffff 100%);
    border:1px solid #eaeaf2;
    border-radius:14px;
    padding:.85rem 1rem .75rem 1.05rem;
    height:auto;
    min-height:0;
    overflow:hidden;
    box-shadow:0 4px 14px rgba(23,32,51,.06);
    transition:transform .2s ease,box-shadow .2s ease;
}
.gd-page .metric-card::before{
    content:"";
    position:absolute;left:0;top:0;bottom:0;width:4px;
    background:var(--kpi-accent);z-index:3;
}
.gd-page .metric-card:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 20px rgba(23,32,51,.09);
}
.gd-page .metric-card.t-magenta{
    --kpi-accent:#f63383;--kpi-icon-bg:#fce3ef;--kpi-icon:#e91e63;
    --kpi-wave-a:#f3e0ff;--kpi-wave-b:#f9c2e8;--kpi-wave-c:#e8b4f0;
}
.gd-page .metric-card.t-blue{
    --kpi-accent:var(--theme-primary);--kpi-icon-bg:var(--theme-100);--kpi-icon:var(--theme-primary);
    --kpi-wave-a:#e0ecff;--kpi-wave-b:#c7dbff;--kpi-wave-c:#a8c8ff;
}
.gd-page .metric-card.t-green{
    --kpi-accent:#16a34a;--kpi-icon-bg:#dcfce7;--kpi-icon:#16a34a;
    --kpi-wave-a:#e3f9eb;--kpi-wave-b:#c6f0d4;--kpi-wave-c:#9fe3b8;
}
.gd-page .metric-card.t-orange,
.gd-page .metric-card.t-red{
    --kpi-accent:#f97316;--kpi-icon-bg:#ffedd5;--kpi-icon:#ea580c;
    --kpi-wave-a:#fff0e0;--kpi-wave-b:#ffd9b3;--kpi-wave-c:#ffc48a;
}
.gd-page .metric-head{
    display:flex;align-items:center;justify-content:space-between;gap:.55rem;
    margin-bottom:.35rem;position:relative;z-index:2;
}
.gd-page .metric-icon{
    width:36px;height:32px;border-radius:8px;flex-shrink:0;
    display:inline-flex;align-items:center;justify-content:center;
    background:var(--kpi-icon-bg);color:var(--kpi-icon);font-size:.95rem;
}
.gd-page .metric-label{
    margin:0;font-size:9.5px;font-weight:600;color:var(--kpi-heading);
    text-transform:uppercase;letter-spacing:.04em;text-align:right;
    line-height:1.2;max-width:70%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}
.gd-page .metric-body{
    position:relative;z-index:2;display:flex;flex-direction:column;flex:1;
}
.gd-page .metric-value{
    margin:0;font-size:24px;font-weight:800;color:var(--kpi-value);
    line-height:1;letter-spacing:-.02em;
}
.gd-page .metric-trend{
    margin:.15rem 0 0;font-size:9.5px;font-weight:500;color:var(--kpi-sub);line-height:1.2;
}
.gd-page .metric-link{
    display:inline-block;margin-top:.35rem;font-size:10.5px;font-weight:600;
    text-decoration:none;color:var(--kpi-link);position:relative;z-index:2;
}
.gd-page .metric-link:hover{color:#0f3fb8;text-decoration:none}
/* Soft decorative wave — bottom-right corner only (reference) */
.gd-page .metric-wave{
    position:absolute;
    right:0;
    bottom:0;
    width:46%;
    height:62%;
    pointer-events:none;
    z-index:1;
}
.gd-page .metric-wave svg{
    display:block;width:100%;height:100%;
}
.gd-page .metric-wave .w0{fill:var(--kpi-wave-a);opacity:.9}
.gd-page .metric-wave .w1{fill:var(--kpi-wave-b);opacity:.72}
.gd-page .metric-wave .w2{fill:var(--kpi-wave-c);opacity:.55}

.gd-page .section-label{
    font-size:.72rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#94a3b8;margin:0 0 .75rem;
}

/* Needs attention */
.gd-page .attn-card{
    display:flex;align-items:center;gap:.9rem;background:var(--gd-card);border:1px solid var(--gd-line);
    border-radius:16px;padding:1rem 1.15rem;height:100%;text-decoration:none;color:inherit;
    box-shadow:0 6px 18px rgba(15,23,42,.04);transition:transform .15s,box-shadow .15s;
}
.gd-page .attn-card:hover{transform:translateY(-2px);box-shadow:0 12px 26px rgba(15,23,42,.08);color:inherit}
.gd-page .attn-ico{
    width:46px;height:46px;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;
    font-size:1.2rem;flex-shrink:0;
}
.gd-page .attn-danger .attn-ico{background:#fee2e2;color:#dc2626}
.gd-page .attn-warn .attn-ico{background:#ffedd5;color:#ea580c}
.gd-page .attn-muted .attn-ico{background:#e0f2fe;color:#0284c7}
.gd-page .attn-card .lbl{font-size:.8rem;font-weight:600;color:var(--gd-muted);margin:0;line-height:1.25}
.gd-page .attn-card .cnt{font-size:1.45rem;font-weight:800;color:#0f172a;line-height:1.1;margin:.2rem 0 0}
.gd-page .attn-pill{margin-left:auto;padding:.3rem .7rem;border-radius:999px;font-size:.72rem;font-weight:700}
.gd-page .attn-danger .attn-pill{background:#fee2e2;color:#b91c1c}
.gd-page .attn-warn .attn-pill{background:#ffedd5;color:#c2410c}
.gd-page .attn-muted .attn-pill{background:#e0f2fe;color:#0369a1}

.gd-page .panel{
    background:#fff;border:1px solid #e8eef5;border-radius:16px;
    box-shadow:0 6px 18px rgba(15,23,42,.05);height:100%;overflow:visible;
    display:flex;flex-direction:column;
}
.gd-page .panel-h{
    padding:1rem 1.2rem .85rem;border-bottom:1px solid #f1f5f9;
    display:flex;align-items:center;justify-content:space-between;gap:.75rem;
}
.gd-page .panel-h h5{margin:0;font-size:.98rem;font-weight:700;color:#0f172a}
.gd-page .panel-h .sub{font-size:.75rem;color:#94a3b8;margin:.18rem 0 0}
.gd-page .panel-b{padding:1.1rem 1.2rem 1.2rem;flex:1;display:flex;flex-direction:column}
.gd-page .panel-title-row{display:flex;align-items:center;gap:.7rem}
.gd-page .panel-ico{
    width:40px;height:40px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;
    color:#fff;font-size:1.1rem;flex-shrink:0;
}
.gd-page .panel-ico.blue{background:var(--theme-primary-deep)}
.gd-page .panel-ico.purple{background:#7c3aed}
.gd-page .panel-ico.teal{background:#0f766e}
.gd-page .view-all{font-size:.84rem;font-weight:700;color:#1555d8;text-decoration:none;white-space:nowrap}
.gd-page .view-all:hover{color:#0f3fb8}

/* Target snapshot — reference layout */
.gd-page .target-block{display:flex;flex-direction:column;gap:.95rem;flex:1}
.gd-page .target-top{display:flex;align-items:flex-end;justify-content:space-between;gap:1rem}
.gd-page .target-pct{font-size:2.15rem;font-weight:800;color:#0f172a;line-height:1;letter-spacing:-.03em}
.gd-page .target-pct span{font-size:.88rem;font-weight:600;color:#64748b;margin-left:.3rem}
.gd-page .target-amounts{text-align:right}
.gd-page .target-amounts .ach{font-size:1.05rem;font-weight:800;color:#0f172a}
.gd-page .target-amounts .rem{font-size:.86rem;font-weight:700;color:#c2410c;margin-top:.2rem}
.gd-page .prog{height:10px;background:#eef2f7;border-radius:999px;overflow:hidden}
.gd-page .prog>span{display:block;height:100%;border-radius:999px;background:linear-gradient(90deg,#7c3aed 0%,var(--theme-primary) 100%)}
.gd-page .snap-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:.65rem;margin-top:auto}
.gd-page .snap-stat{
    background:#f8fafc;border:1px solid #eef2f7;border-radius:12px;padding:.7rem .65rem;
    display:flex;align-items:center;gap:.5rem;min-width:0;
}
.gd-page .snap-stat .ico{
    width:28px;height:28px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;
    color:#fff;font-size:.78rem;flex-shrink:0;
}
.gd-page .snap-stat .ico.g{background:#16a34a}.gd-page .snap-stat .ico.b{background:var(--theme-primary)}.gd-page .snap-stat .ico.p{background:#7c3aed}
.gd-page .snap-stat .n{font-size:.88rem;font-weight:800;color:#0f172a;line-height:1.1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gd-page .snap-stat .l{font-size:.68rem;font-weight:600;color:#64748b;margin-top:.1rem}

/* Quick actions — 4-up row (reference) */
.gd-page .qa-grid{
    display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem;flex:1;align-content:stretch;
}
.gd-page .qa-tile{
    border:1px solid transparent;border-radius:14px;padding:1.1rem .6rem;text-align:center;text-decoration:none;
    font-weight:700;font-size:.82rem;display:flex;flex-direction:column;align-items:center;justify-content:center;
    gap:.45rem;min-height:108px;height:100%;transition:transform .15s,box-shadow .15s;
}
.gd-page .qa-tile:hover{transform:translateY(-2px);box-shadow:0 10px 20px rgba(15,23,42,.08);color:inherit}
.gd-page .qa-tile i{font-size:1.4rem}
.gd-page .qa-blue{background:var(--theme-50);color:var(--theme-primary);border-color:var(--theme-100)}
.gd-page .qa-purple{background:#f5f3ff;color:#7c3aed;border-color:#ede9fe}
.gd-page .qa-green{background:#ecfdf5;color:#16a34a;border-color:#d1fae5}
.gd-page .qa-orange{background:#fff7ed;color:#ea580c;border-color:#ffedd5}
@media(max-width:1199.98px){
    .gd-page .qa-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
}

/* Empty states */
.gd-page .empty-state{
    min-height:210px;display:flex;flex-direction:column;align-items:center;justify-content:center;
    text-align:center;padding:1.5rem 1rem;color:#94a3b8;
}
.gd-page .empty-state .illus{
    width:72px;height:72px;border-radius:20px;background:#f1f5f9;color:#94a3b8;
    display:inline-flex;align-items:center;justify-content:center;font-size:1.85rem;margin-bottom:.85rem;
}
.gd-page .empty-state p{margin:0;font-size:.9rem;font-weight:600}

/* Team / cases lists */
.gd-page .team-row,.gd-page .list-row{
    display:flex;align-items:center;gap:.8rem;padding:.8rem 0;border-bottom:1px solid #f1f5f9;text-decoration:none;color:inherit;
}
.gd-page .team-row:last-child,.gd-page .list-row:last-child{border-bottom:none;padding-bottom:0}
.gd-page .team-row:first-child,.gd-page .list-row:first-child{padding-top:0}
.gd-page .list-row:hover{color:inherit}
.gd-page .avatar{
    width:38px;height:38px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;
    color:#fff;font-size:.78rem;font-weight:800;flex-shrink:0;background:var(--theme-primary);
}
.gd-page .avatar.av2{background:#7c3aed}.gd-page .avatar.av3{background:#0ea5e9}
.gd-page .avatar.av4{background:#14b8a6}.gd-page .avatar.av5{background:#f59e0b}
.gd-page .team-meta,.gd-page .list-main{min-width:0;flex:1}
.gd-page .team-meta .n,.gd-page .list-main .t{font-size:.9rem;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gd-page .team-meta .u,.gd-page .list-main .s{font-size:.74rem;color:#94a3b8;margin-top:.12rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gd-page .team-prog{min-width:100px;max-width:130px;flex:1}
.gd-page .team-pct{font-size:.8rem;font-weight:800;min-width:40px;text-align:right}
.gd-page .status-pill{
    display:inline-flex;align-items:center;padding:.28rem .65rem;border-radius:999px;font-size:.72rem;font-weight:700;
    max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}
.gd-page .status-pill.ok{background:#dcfce7;color:#15803d}
.gd-page .status-pill.warn{background:#ffedd5;color:#c2410c}
.gd-page .status-pill.info{background:var(--theme-100);color:var(--theme-primary-hover)}
.gd-page .status-pill.muted{background:#f1f5f9;color:#64748b}
.gd-page .open{
    border:1.5px solid var(--theme-200);background:#fff;border-radius:8px;padding:.3rem .75rem;
    font-size:.76rem;font-weight:700;color:var(--gd-blue);flex-shrink:0;text-decoration:none;
}
.gd-page .open:hover{background:var(--theme-50);color:var(--theme-primary-hover)}

/* Recent leads table — reference card */
.gd-page .leads-panel .panel-h{padding:1.1rem 1.25rem;border-bottom:1px solid #eef2f7}
.gd-page .leads-panel .panel-ico.blue{
    background:linear-gradient(145deg,var(--theme-primary-deep) 0%,var(--theme-primary) 100%);
    box-shadow:0 6px 14px var(--theme-primary-ring);
}
.gd-page .leads-table-wrap{padding:0 0 .35rem}
.gd-page .leads-table{width:100%;margin:0;border-collapse:separate;border-spacing:0}
.gd-page .leads-table thead th{
    font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;color:#475569;font-weight:700;
    padding:.8rem 1.15rem;border-bottom:1px solid #e8eef5;background:#f1f5f9;white-space:nowrap;text-align:left;
}
.gd-page .leads-table tbody td{
    padding:.95rem 1.15rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;font-size:.875rem;color:#334155;
}
.gd-page .leads-table tbody tr:last-child td{border-bottom:none}
.gd-page .leads-table tbody tr:hover{background:#f8fafc}
.gd-page .lead-avatar{
    width:38px;height:38px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;
    background:var(--theme-primary);color:#fff;font-size:.82rem;font-weight:800;flex-shrink:0;
}
.gd-page .lead-name-cell{display:flex;flex-direction:column;gap:.12rem;min-width:0}
.gd-page .lead-name{font-weight:700;color:#0f172a;line-height:1.2}
.gd-page .lead-sub{font-size:.75rem;color:#94a3b8;line-height:1.25}
.gd-page .pill-product{
    display:inline-flex;align-items:center;padding:.28rem .7rem;border-radius:999px;
    font-size:.74rem;font-weight:700;background:var(--theme-100);color:var(--theme-primary-hover);white-space:nowrap;
}
.gd-page .pill-status{
    display:inline-flex;align-items:center;padding:.28rem .7rem;border-radius:999px;
    font-size:.74rem;font-weight:700;background:#ffedd5;color:#c2410c;white-space:nowrap;max-width:180px;
    overflow:hidden;text-overflow:ellipsis;
}
.gd-page .pill-status.ok{background:#dcfce7;color:#15803d}
.gd-page .pill-status.warn{background:#ffedd5;color:#c2410c}
.gd-page .pill-status.info{background:var(--theme-100);color:var(--theme-primary-hover)}
.gd-page .pill-status.muted{background:#f1f5f9;color:#64748b}
.gd-page .received-cell{
    display:inline-flex;align-items:center;gap:.4rem;color:#64748b;font-size:.82rem;white-space:nowrap;
}
.gd-page .received-cell i{font-size:.9rem;color:#94a3b8}
.gd-page .branch-cell{color:#475569;font-weight:500}
.gd-page .btn-more{
    width:34px;height:34px;border:1px solid #e2e8f0;background:#fff;border-radius:10px;
    color:#94a3b8;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;
}
.gd-page .btn-more:hover{background:#f8fafc;color:#475569;border-color:#cbd5e1}

@media(max-width:1199.98px){
    .gd-page .welcome-copy{max-width:100%}
    .gd-page .control-meta{justify-content:flex-start}
    .gd-page .analytics-cta{align-self:flex-start}
}
@media(max-width:767.98px){
    .gd-page.container-fluid{padding:1rem .85rem!important}
    .gd-page .metric-value{font-size:22px}
    .gd-page .metric-label{white-space:normal;max-width:65%}
    .gd-page .welcome-card::after{opacity:.28;width:40%}
    .gd-page .panel-h{flex-wrap:wrap}
    .gd-page .qa-grid{grid-template-columns:1fr 1fr}
    .gd-page .snap-stats{grid-template-columns:1fr}
    .gd-page .leads-table th,.gd-page .leads-table td{padding:.75rem .65rem}
}
</style>
@endpush

@section('content')
@php
    $hour = (int) now()->format('G');
    $greet = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
    $toneCard = fn ($t) => match ($t) {
        'magenta' => 't-magenta',
        'blue' => 't-blue',
        'green' => 't-green',
        'red', 'orange' => 't-orange',
        default => 't-blue',
    };
    $money = fn ($n) => '₹ ' . number_format((float) $n, 0);
    $statusTone = function ($status) {
        $s = strtolower((string) $status);
        if (str_contains($s, 'disburs') || str_contains($s, 'confirm') || str_contains($s, 'eligible') || str_contains($s, 'converted')) return 'ok';
        if (str_contains($s, 'hold') || str_contains($s, 'pending') || str_contains($s, 'query') || str_contains($s, 'allocation')) return 'warn';
        if (str_contains($s, 'personal') || str_contains($s, 'loan')) return 'info';
        return 'muted';
    };
    $avClass = fn ($i) => ['', 'av2', 'av3', 'av4', 'av5'][$i % 5] ?? '';
    $initial = fn ($name) => strtoupper(mb_substr(trim((string) $name) ?: '?', 0, 1));
    $targetPct = min(100, max(0, (int) ($dash['target']['percent'] ?? 0)));
    $sections = $dash['sections'] ?? ['team_targets' => true, 'recent_cases' => true, 'recent_leads' => true];
    $targetUnit = $dash['target']['unit'] ?? 'money';
    $fmtTarget = function ($n) use ($money, $targetUnit) {
        if ($targetUnit === 'calls' || $targetUnit === 'count') {
            return number_format((float) $n, 0);
        }
        return $money($n);
    };
    $targetSnap = $dash['target']['snap'] ?? [];
    $faIcon = function ($bi) {
        $map = [
            'bi-people-fill' => 'fas fa-users',
            'bi-people' => 'fas fa-users',
            'bi-cash-coin' => 'fas fa-money-bill-wave',
            'bi-cash-stack' => 'fas fa-wallet',
            'bi-wallet2' => 'fas fa-wallet',
            'bi-bullseye' => 'fas fa-bullseye',
            'bi-telephone' => 'fas fa-phone',
            'bi-lightning' => 'fas fa-bolt',
            'bi-calendar-event' => 'fas fa-calendar-alt',
            'bi-exclamation-triangle' => 'fas fa-exclamation-triangle',
            'bi-shield-check' => 'fas fa-shield-alt',
            'bi-briefcase' => 'fas fa-briefcase',
            'bi-folder2-open' => 'fas fa-folder-open',
            'bi-graph-up' => 'fas fa-chart-line',
            'bi-journal-check' => 'fas fa-file-invoice',
            'bi-pause-circle' => 'fas fa-pause-circle',
            'bi-check2-circle' => 'fas fa-check-circle',
            'bi-building' => 'fas fa-building',
            'bi-person-check' => 'fas fa-user-check',
            'bi-globe2' => 'fas fa-globe',
        ];
        return $map[$bi] ?? 'fas fa-chart-bar';
    };
    /* Soft overlapping corner blobs — bottom-right only */
    $wave = <<<'SVG'
<svg viewBox="0 0 180 150" preserveAspectRatio="xMaxYMax meet" aria-hidden="true">
  <path class="w0" d="M8 150 C18 108 42 78 88 72 C128 66 158 42 180 18 L180 150 Z"/>
  <path class="w1" d="M28 150 C36 118 58 92 98 88 C132 84 158 62 180 40 L180 150 Z"/>
  <path class="w2" d="M55 150 C62 128 82 112 118 110 C146 108 166 96 180 78 L180 150 Z"/>
</svg>
SVG;
@endphp

<div class="container-fluid p-4 gd-page">
    {{-- Welcome + date/analytics --}}
    <div class="row g-3 welcome-row">
        <div class="col-lg-7 col-xl-8">
            <div class="welcome-card">
                <span class="welcome-icon"><i class="bi bi-bank2"></i></span>
                <div class="welcome-copy">
                    <h2>{{ $greet }}, {{ $dash['role_label'] }}!</h2>
                    <p class="sub"><strong>{{ $dash['scope_label'] }}</strong> · {{ $dash['summary'] }}</p>
                    <span class="role-badge"><i class="bi bi-eye"></i> {{ $dash['badge'] }}</span>
                    <div class="pipe-strip" aria-label="Pipeline shortcuts">
                        <a class="pipe-chip" href="{{ route('goal.leads.index') }}"><i class="bi bi-people"></i> Leads</a>
                        <a class="pipe-chip" href="{{ route('goal.allocation.index') }}"><i class="bi bi-diagram-3"></i> Allocation</a>
                        <a class="pipe-chip" href="{{ route('goal.cases.index') }}"><i class="bi bi-briefcase"></i> Cases</a>
                        <a class="pipe-chip" href="{{ route('goal.documents.index') }}"><i class="bi bi-file-earmark"></i> Docs</a>
                        <a class="pipe-chip" href="{{ route('goal.bank-processing.index') }}"><i class="bi bi-bank"></i> Bank</a>
                        <a class="pipe-chip" href="{{ route('goal.sanction-disbursement.index') }}"><i class="bi bi-award"></i> Sanction</a>
                        <a class="pipe-chip" href="{{ route('goal.pms.index') }}"><i class="bi bi-shield-check"></i> PMS</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5 col-xl-4">
            <div class="control-card">
                <div class="control-meta">
                    <span><i class="bi bi-calendar3"></i> <span id="gdHeroDate">{{ now()->format('l, d M Y') }}</span></span>
                    <span class="time"><i class="bi bi-clock"></i> <span id="gdHeroTime">{{ now()->format('h:i A') }}</span></span>
                </div>
                <a href="{{ route('goal.analytics') }}" class="analytics-cta">
                    <i class="bi bi-bar-chart-fill"></i> Open Analytics
                </a>
            </div>
        </div>
    </div>

    {{-- KPI metric cards --}}
    <div class="row g-3 mb-3">
        @foreach($dash['kpis'] as $kpi)
            @php $tone = $kpi['tone'] ?? 'blue'; @endphp
            <div class="col-12 col-sm-6 col-xl-3">
                <a href="{{ $kpi['link'] }}" class="metric-card {{ $toneCard($tone) }}">
                    <div class="metric-head">
                        <span class="metric-icon" aria-hidden="true"><i class="{{ $faIcon($kpi['icon'] ?? '') }}"></i></span>
                        <h6 class="metric-label">{{ $kpi['label'] }}</h6>
                    </div>
                    <div class="metric-body">
                        <p class="metric-value">{{ $kpi['value'] }}</p>
                        <p class="metric-trend">{{ $kpi['trend'] }}</p>
                        <span class="metric-link">{{ $kpi['link_text'] }}</span>
                    </div>
                    <div class="metric-wave" aria-hidden="true">{!! $wave !!}</div>
                </a>
            </div>
        @endforeach
    </div>

    {{-- Needs attention --}}
    @if(!empty($dash['attention']))
        <p class="section-label">Needs attention</p>
        <div class="row g-3 mb-3">
            @foreach($dash['attention'] as $item)
                <div class="col-12 col-md-4">
                    <a href="{{ $item['href'] }}" class="attn-card attn-{{ $item['tone'] }}">
                        <span class="attn-ico"><i class="bi bi-exclamation-triangle"></i></span>
                        <div>
                            <p class="lbl">{{ $item['label'] }}</p>
                            <div class="cnt">{{ $item['count'] }}</div>
                        </div>
                        <span class="attn-pill">{{ $item['priority'] }}</span>
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Target + Quick actions --}}
    <div class="row g-3 mb-3 align-items-stretch">
        <div class="col-12 col-xl-5">
            <div class="panel">
                <div class="panel-h">
                    <div class="panel-title-row">
                        <span class="panel-ico purple"><i class="bi bi-bullseye"></i></span>
                        <div>
                            <h5>{{ $dash['target']['title'] }}</h5>
                            <p class="sub">{{ $dash['target']['subtitle'] }}</p>
                        </div>
                    </div>
                    <a href="{{ route('goal.analytics') }}" class="view-all">Details →</a>
                </div>
                <div class="panel-b">
                    <div class="target-block">
                        <div class="target-top">
                            <div class="target-pct">{{ $targetPct }}%<span>achieved</span></div>
                            <div class="target-amounts">
                                <div class="ach">{{ $fmtTarget($dash['target']['actual']) }}{{ $targetUnit === 'calls' ? ' calls' : '' }}</div>
                                <div class="rem">{{ $fmtTarget($dash['target']['remaining']) }}{{ $targetUnit === 'calls' ? ' calls' : '' }} left</div>
                            </div>
                        </div>
                        <div class="prog"><span style="width:{{ $targetPct }}%"></span></div>
                        <div class="snap-stats">
                            @forelse($targetSnap as $snap)
                                <div class="snap-stat">
                                    <span class="ico {{ $snap['tone'] }}"><i class="bi {{ $snap['icon'] }}"></i></span>
                                    <div>
                                        <div class="n">{{ $snap['n'] }}</div>
                                        <div class="l">{{ $snap['l'] }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="snap-stat">
                                    <span class="ico g"><i class="bi bi-check-lg"></i></span>
                                    <div>
                                        <div class="n">{{ $dash['metrics']['disbursed_cases'] }}</div>
                                        <div class="l">Disbursed</div>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-7">
            <div class="panel">
                <div class="panel-h">
                    <div class="panel-title-row">
                        <span class="panel-ico blue"><i class="bi bi-lightning-charge-fill"></i></span>
                        <div>
                            <h5>Quick Actions</h5>
                            <p class="sub">Shortcuts for {{ $dash['role_label'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="panel-b">
                    <div class="qa-grid">
                        @foreach($dash['actions'] as $action)
                            <a href="{{ $action[0] }}" class="qa-tile {{ $action[1] }}">
                                <i class="bi {{ $action[2] }}"></i>
                                <span>{{ $action[3] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Team targets + Recent cases (role-gated) --}}
    @if(!empty($sections['team_targets']) || !empty($sections['recent_cases']))
    <div class="row g-3 mb-3">
        @if(!empty($sections['team_targets']))
        <div class="col-12 {{ !empty($sections['recent_cases']) ? 'col-xl-5' : 'col-xl-12' }}">
            <div class="panel">
                <div class="panel-h">
                    <div class="panel-title-row">
                        <span class="panel-ico purple"><i class="bi bi-people-fill"></i></span>
                        <div>
                            <h5>Team Targets</h5>
                            <p class="sub">{{ $dash['period_label'] }} · In your scope</p>
                        </div>
                    </div>
                    <a href="{{ route('goal.targets.index') }}" class="view-all">View All →</a>
                </div>
                <div class="panel-b">
                    @forelse($dash['team_targets'] as $i => $row)
                        <div class="team-row">
                            <span class="avatar {{ $avClass($i) }}">{{ $initial($row['name']) }}</span>
                            <div class="team-meta">
                                <div class="n">{{ $row['name'] }}</div>
                                <div class="u">{{ $row['unit'] }} · {{ $money($row['actual']) }} / {{ $money($row['effective']) }}</div>
                            </div>
                            <div class="team-prog">
                                <div class="prog" style="margin:0"><span style="width:{{ min(100, $row['percent']) }}%;background:{{ $row['percent'] >= 75 ? '#16a34a' : ($row['percent'] >= 50 ? 'var(--theme-primary)' : '#f59e0b') }}"></span></div>
                            </div>
                            <div class="team-pct" style="color:{{ $row['percent'] >= 75 ? '#16a34a' : ($row['percent'] >= 50 ? 'var(--theme-primary)' : '#d97706') }}">{{ $row['percent'] }}%</div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <div class="illus"><i class="bi bi-people"></i></div>
                            <p>No team targets for this period</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
        @endif
        @if(!empty($sections['recent_cases']))
        <div class="col-12 {{ !empty($sections['team_targets']) ? 'col-xl-7' : 'col-xl-12' }}">
            <div class="panel">
                <div class="panel-h">
                    <div class="panel-title-row">
                        <span class="panel-ico teal"><i class="bi bi-briefcase-fill"></i></span>
                        <div>
                            <h5>{{ ($dash['role_key'] ?? '') === 'pms' ? 'PMS Pending Cases' : (($dash['role_key'] ?? '') === 'accounts' ? 'Payout-Linked Cases' : 'Recent Loan Cases') }}</h5>
                            <p class="sub">Latest activity in your scope</p>
                        </div>
                    </div>
                    <a href="{{ route('goal.cases.index') }}" class="view-all">View All →</a>
                </div>
                <div class="panel-b">
                    @forelse($dash['recent_cases'] as $i => $case)
                        <a href="{{ $case['href'] }}" class="list-row">
                            <span class="avatar {{ $avClass($i) }}">{{ $initial($case['customer']) }}</span>
                            <div class="list-main">
                                <div class="t">{{ $case['case_no'] }} · {{ $case['customer'] }}</div>
                                <div class="s">{{ $case['product'] }} · {{ $case['bank'] }}</div>
                            </div>
                            <span class="status-pill {{ $statusTone($case['status']) }}">{{ $case['status'] }}</span>
                            <span class="open">Open</span>
                        </a>
                    @empty
                        <div class="empty-state">
                            <div class="illus"><i class="bi bi-briefcase"></i></div>
                            <p>No loan cases in scope yet</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- Recent leads table (role-gated) --}}
    @if(!empty($sections['recent_leads']))
    <div class="row g-3">
        <div class="col-12">
            <div class="panel leads-panel">
                <div class="panel-h">
                    <div class="panel-title-row">
                        <span class="panel-ico blue"><i class="bi bi-person-badge-fill"></i></span>
                        <div>
                            <h5>{{ ($dash['role_key'] ?? '') === 'bm' ? 'Leads Awaiting Allocation' : (($dash['role_key'] ?? '') === 'vrm' ? 'My Recent Leads' : 'Recent Leads') }}</h5>
                            <p class="sub">{{ ($dash['role_key'] ?? '') === 'bm' ? 'Pending BM allocation in your branch' : 'Newest leads entering the funnel' }}</p>
                        </div>
                    </div>
                    <a href="{{ route('goal.leads.index') }}" class="view-all">View All →</a>
                </div>
                <div class="leads-table-wrap">
                    <div class="table-responsive">
                        <table class="leads-table">
                            <thead>
                                <tr>
                                    <th>Lead ID</th>
                                    <th>Lead Name</th>
                                    <th>Product</th>
                                    <th>Branch</th>
                                    <th>Received On</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($dash['recent_leads'] as $i => $lead)
                                <tr>
                                    <td>
                                        <span class="lead-avatar {{ $avClass($i) }}">{{ $initial($lead['customer']) }}</span>
                                    </td>
                                    <td>
                                        <div class="lead-name-cell">
                                            <span class="lead-name">{{ $lead['customer'] }}</span>
                                            <span class="lead-sub">{{ $lead['ref'] }} · {{ ucfirst($lead['source']) }} · {{ ucfirst($lead['type']) }}</span>
                                        </div>
                                    </td>
                                    <td><span class="pill-product">{{ $lead['product'] !== '—' ? $lead['product'] : 'Loan' }}</span></td>
                                    <td><span class="branch-cell">{{ $lead['branch'] }}</span></td>
                                    <td>
                                        <span class="received-cell">
                                            <i class="bi bi-calendar3"></i>
                                            {{ $lead['received_on'] }}
                                        </span>
                                    </td>
                                    <td><span class="pill-status {{ $statusTone($lead['status']) }}">{{ $lead['status'] }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ $lead['href'] }}" class="btn-more" title="Open lead">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state" style="min-height:140px">
                                            <div class="illus"><i class="bi bi-inbox"></i></div>
                                            <p>No recent leads</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    function tick() {
        const now = new Date();
        const d = document.getElementById('gdHeroDate');
        const t = document.getElementById('gdHeroTime');
        if (d) d.textContent = now.toLocaleDateString('en-IN', { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' });
        if (t) t.textContent = now.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true });
    }
    tick();
    setInterval(tick, 30000);
})();
</script>
@endpush
