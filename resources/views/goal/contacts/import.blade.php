@extends('layouts.app')

@push('styles')
<style>
.ci-hub {
    --ink:#0f172a;
    --muted:#64748b;
    --line:#e6edf3;
    --green:#16a34a;
    --green-dark:#15803d;
    --green-soft:#ecfdf3;
    --green-ring:rgba(22,163,74,.22);
    --page:#f4f7f5;
    background:linear-gradient(180deg,#f7faf8 0%, #f3f6f8 100%);
    min-height:calc(100vh - 70px);
}
.ci-hub .ci-head {
    margin:0 0 0;
    padding:8px 2px 10px;
    background:transparent; border:0;
}
.ci-hub .ci-crumbs {
    display:flex; flex-wrap:wrap; align-items:center; gap:5px;
    margin:0 0 8px; padding:0; list-style:none; font-size:.7rem; color:#94a3b8; font-weight:500;
}
.ci-hub .ci-crumbs a { color:#15803d; text-decoration:none; font-weight:600; }
.ci-hub .ci-crumbs a:hover { text-decoration:underline; }
.ci-hub .ci-crumbs .sep { color:#b7c9be; font-weight:400; }
.ci-hub .ci-crumbs .current { color:#64748b; font-weight:500; }

.ci-hub .hero {
    display:flex; flex-wrap:wrap; align-items:center; gap:10px;
}
.ci-hub .hero-left { min-width:0; flex:1; }
.ci-hub .hero-main {
    display:flex; align-items:center; gap:10px; min-width:0;
}
.ci-hub .hero-icon {
    width:30px; height:30px; border-radius:8px; flex-shrink:0;
    background:#16a34a; color:#fff;
    display:inline-flex; align-items:center; justify-content:center;
    font-size:.9rem;
}
.ci-hub .hero-copy { min-width:0; }
.ci-hub .hero-title {
    margin:0; font-size:1rem; font-weight:800; color:#0f172a;
    letter-spacing:-.02em; line-height:1.2;
}
.ci-hub .hero-sub {
    margin:2px 0 0; color:#64748b; font-size:.72rem; line-height:1.3; max-width:32rem;
}

.ci-hub .ci-shell {
    background:#fff; border:1px solid #e5eee8; border-radius:12px;
    overflow:hidden; box-shadow:0 2px 8px rgba(15,23,42,.03);
}
.ci-hub .ci-tabs {
    display:flex; align-items:stretch; gap:0;
    margin:0; padding:0 8px;
    background:#f8faf9; border-bottom:1px solid #e5eee8;
}
.ci-hub .ci-tab {
    position:relative; display:inline-flex; align-items:center; gap:7px;
    height:40px; padding:0 14px; margin-bottom:-1px;
    font-weight:700; font-size:.8rem; text-decoration:none; color:#64748b;
    background:transparent; border:0; border-bottom:2px solid transparent;
}
.ci-hub .ci-tab .num {
    width:18px; height:18px; border-radius:999px;
    display:inline-flex; align-items:center; justify-content:center;
    font-size:.68rem; font-weight:800; background:#e2e8f0; color:#64748b;
}
.ci-hub .ci-tab:hover { color:#0f172a; }
.ci-hub .ci-tab.active {
    color:#16a34a; border-bottom-color:#16a34a; background:#fff;
}
.ci-hub .ci-tab.active .num { background:#16a34a; color:#fff; }
.ci-hub .ci-tab.disabled { opacity:.45; pointer-events:none; }
.ci-hub .ci-panel { padding:12px; }

.ci-hub .hero-illu { display:none !important; }

.ci-hub .ci-grid {
    display:grid; grid-template-columns:minmax(0,1.25fr) minmax(280px,.85fr);
    gap:16px; align-items:start; margin-bottom:16px;
}
@media (max-width:992px) { .ci-hub .ci-grid { grid-template-columns:1fr; } }

.ci-hub .ci-card {
    background:#fff; border:1px solid var(--line); border-radius:18px;
    box-shadow:0 10px 28px rgba(15,23,42,.04); overflow:hidden;
}
.ci-hub .ci-card-head {
    display:flex; align-items:flex-start; justify-content:space-between; gap:12px;
    padding:18px 18px 0;
}
.ci-hub .ci-card-head h2 {
    margin:0; font-size:1.05rem; font-weight:800; color:var(--ink);
}
.ci-hub .ci-card-head p { margin:4px 0 0; font-size:.8rem; color:var(--muted); }
.ci-hub .ci-card-body { padding:16px 18px 18px; }

.ci-hub .upload-head-left {
    display:flex; align-items:flex-start; gap:12px; min-width:0;
}
.ci-hub .upload-head-ico {
    width:40px; height:40px; border-radius:12px; flex-shrink:0;
    background:#ecfdf3; color:#16a34a;
    display:inline-flex; align-items:center; justify-content:center; font-size:1.15rem;
}
.ci-hub .btn-tpl {
    height:38px; padding:0 14px; border-radius:999px; font-weight:700; font-size:.82rem;
    display:inline-flex; align-items:center; gap:.4rem; border:1px solid #bbf7d0;
    background:#fff; color:#15803d; text-decoration:none;
}
.ci-hub .btn-tpl:hover { background:#ecfdf3; color:#14532d; }
.ci-hub .dropdown-menu { border-radius:12px; border-color:#e2e8f0; box-shadow:0 12px 28px rgba(15,23,42,.1); }
.ci-hub .dropdown-item { font-size:.86rem; font-weight:600; }

.ci-hub .form-label {
    font-size:.78rem; font-weight:700; color:#334155; margin-bottom:6px;
    display:flex; align-items:center; gap:4px;
}
.ci-hub .req-star { color:#dc2626; font-weight:800; }
.ci-hub .req {
    font-size:.65rem; font-weight:800; letter-spacing:.03em; text-transform:uppercase;
    color:#b91c1c; background:#fee2e2; padding:.12rem .4rem; border-radius:999px;
}
.ci-hub .form-select, .ci-hub .form-control {
    border-radius:12px; border-color:#dbe3e8; font-size:.9rem; min-height:46px;
    box-shadow:none;
}
.ci-hub .form-select:focus, .ci-hub .form-control:focus {
    border-color:#86efac; box-shadow:0 0 0 .2rem rgba(22,163,74,.15);
}
.ci-hub .select-ico {
    position:relative;
}
.ci-hub .select-ico .leading {
    position:absolute; left:12px; top:50%; transform:translateY(-50%);
    width:28px; height:28px; border-radius:999px; z-index:3; pointer-events:none;
    background:#ecfdf3; color:#16a34a;
    display:inline-flex; align-items:center; justify-content:center; font-size:.9rem;
}
.ci-hub .select-ico .form-select {
    padding-left:52px;
}
/* Select2 renders after the native select — keep text clear of the leading icon */
.ci-hub .select-ico > .select2-container {
    width:100% !important;
}
.ci-hub .select-ico > .select2-container .select2-selection--single {
    min-height:46px !important;
    padding-left:44px !important;
    display:flex !important;
    align-items:center !important;
}
.ci-hub .select-ico > .select2-container .select2-selection--single .select2-selection__rendered {
    padding-left:0 !important;
    line-height:1.35 !important;
    color:#0f172a !important;
}
.ci-hub .select-ico > .select2-container .select2-selection--single .select2-selection__arrow {
    height:44px !important;
}
.ci-hub .select-ico > .select2-container .select2-selection--single .select2-selection__clear {
    margin-right:1.5rem !important;
}

.ci-hub .ci-drop {
    position:relative; margin-top:14px; border:1.5px dashed #86efac; border-radius:18px;
    background:
        radial-gradient(120% 80% at 50% 120%, rgba(134,239,172,.28) 0%, transparent 55%),
        linear-gradient(180deg, #f7fdf9 0%, #eefbf3 100%);
    padding:28px 18px 24px; text-align:center; transition:.15s ease; cursor:pointer;
    overflow:hidden;
}
.ci-hub .ci-drop.is-drag {
    border-color:#16a34a; background:#ecfdf3;
    box-shadow:inset 0 0 0 2px rgba(22,163,74,.08);
}
.ci-hub .ci-drop.has-file { border-style:solid; border-color:#86efac; }
.ci-hub .drop-cloud {
    width:52px; height:52px; margin:0 auto 10px; border-radius:16px;
    background:#fff; color:#16a34a; border:1px solid #dcfce7;
    display:inline-flex; align-items:center; justify-content:center; font-size:1.45rem;
    box-shadow:0 8px 16px rgba(22,163,74,.1);
}
.ci-hub .ci-drop h3 { margin:0 0 4px; font-size:1rem; font-weight:800; color:var(--ink); }
.ci-hub .ci-drop .drop-sub { margin:0 0 14px; font-size:.84rem; color:var(--muted); }
.ci-hub .drop-pills {
    display:flex; justify-content:center; gap:8px; flex-wrap:wrap; margin-bottom:14px;
}
.ci-hub .drop-pill {
    display:inline-flex; align-items:center; gap:6px;
    height:30px; padding:0 12px; border-radius:999px;
    background:#fff; border:1px solid #e2e8f0; color:#334155;
    font-size:.74rem; font-weight:700;
}
.ci-hub .drop-pill i { color:#16a34a; font-size:.95rem; }
.ci-hub .btn-choose {
    position:relative; overflow:hidden; cursor:pointer;
    height:40px; padding:0 18px; border-radius:999px; font-weight:700; font-size:.88rem;
    display:inline-flex; align-items:center; gap:.45rem; border:0; color:#fff;
    background:#16a34a; box-shadow:0 10px 18px var(--green-ring);
}
.ci-hub .btn-choose:hover { background:#15803d; color:#fff; }
.ci-hub .btn-choose input {
    position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%;
}
.ci-hub .file-name {
    margin-top:10px; font-size:.82rem; color:var(--muted); font-weight:600;
}
.ci-hub .file-name.has-file { color:#166534; }

.ci-hub .ci-actions {
    display:flex; flex-wrap:wrap; align-items:center; gap:12px; margin-top:16px;
}
.ci-hub .btn-import, .ci-hub .btn-apply {
    height:44px; padding:0 22px; border-radius:999px; font-weight:800; font-size:.92rem;
    border:0; color:#fff; background:#16a34a;
    box-shadow:0 12px 22px var(--green-ring);
}
.ci-hub .btn-import:hover, .ci-hub .btn-apply:hover { background:#15803d; color:#fff; }
.ci-hub .hint {
    font-size:.8rem; color:#475569; line-height:1.4;
    display:inline-flex; align-items:flex-start; gap:6px;
}
.ci-hub .hint i { color:#16a34a; margin-top:2px; }

.ci-hub .guide-list { list-style:none; margin:0; padding:0; display:grid; gap:10px; }
.ci-hub .guide-item {
    display:flex; gap:12px; align-items:flex-start;
    padding:12px; border-radius:14px; background:#f8faf9; border:1px solid #eef3f0;
}
.ci-hub .guide-ico {
    width:36px; height:36px; border-radius:11px; flex-shrink:0;
    display:inline-flex; align-items:center; justify-content:center; font-size:1rem;
}
.ci-hub .guide-ico.req { background:#fee2e2; color:#dc2626; }
.ci-hub .guide-ico.opt { background:#e0f2fe; color:#0284c7; }
.ci-hub .guide-meta { min-width:0; flex:1; }
.ci-hub .guide-name {
    display:flex; align-items:center; gap:8px; flex-wrap:wrap;
    font-size:.9rem; font-weight:800; color:var(--ink);
}
.ci-hub .pill {
    font-size:.62rem; font-weight:800; letter-spacing:.03em; text-transform:uppercase;
    padding:.18rem .45rem; border-radius:999px;
}
.ci-hub .pill.req { background:#fee2e2; color:#b91c1c; }
.ci-hub .pill.opt { background:#e0f2fe; color:#0369a1; }
.ci-hub .guide-desc { margin:4px 0 0; font-size:.78rem; color:var(--muted); line-height:1.4; }
.ci-hub .sample-bar {
    margin-top:14px; padding:12px 14px; border-radius:12px;
    background:#f0fdf4; border:1px dashed #bbf7d0;
    display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;
}
.ci-hub .sample-bar span { font-size:.8rem; color:#166534; font-weight:600; }
.ci-hub .sample-bar a { font-size:.82rem; font-weight:800; color:var(--green-dark); text-decoration:none; }
.ci-hub .sample-bar a:hover { text-decoration:underline; }

.ci-hub .batches-card { margin-bottom:8px; }
.ci-hub .stats-row {
    display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px;
    padding:16px 18px; border-bottom:1px solid #f1f5f9;
}
@media (max-width:768px) { .ci-hub .stats-row { grid-template-columns:1fr 1fr; } }
.ci-hub .stat {
    background:#f8faf9; border:1px solid #eef3f0; border-radius:14px; padding:12px 14px;
}
.ci-hub .stat .lbl { font-size:.72rem; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; }
.ci-hub .stat .val { margin-top:4px; font-size:1.25rem; font-weight:800; color:var(--ink); }
.ci-hub .stat.ok .val { color:#166534; }
.ci-hub .stat.warn .val { color:#c2410c; }
.ci-hub .stat.bad .val { color:#b91c1c; }

.ci-hub .ci-table { width:100%; border-collapse:collapse; font-size:.86rem; }
.ci-hub .ci-table th {
    text-align:left; font-size:.7rem; text-transform:uppercase; letter-spacing:.04em;
    color:#64748b; padding:12px 14px; border-bottom:1px solid #e8eef2; background:#fbfcfc; font-weight:800;
}
.ci-hub .ci-table td { padding:13px 14px; border-bottom:1px solid #f1f5f9; color:#334155; vertical-align:middle; }
.ci-hub .ci-table tr:hover td { background:#fafdfb; }
.ci-hub .file-cell { display:flex; align-items:center; gap:10px; min-width:0; }
.ci-hub .file-cell i { font-size:1.2rem; color:var(--green-dark); flex-shrink:0; }
.ci-hub .file-cell .name { font-weight:700; color:var(--ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:220px; }
.ci-hub .badge-st {
    display:inline-flex; align-items:center; gap:5px;
    padding:.22rem .55rem; border-radius:999px; font-size:.72rem; font-weight:800;
}
.ci-hub .badge-st.ready, .ci-hub .badge-st.partial { background:#dcfce7; color:#166534; }
.ci-hub .badge-st.failed { background:#fee2e2; color:#b91c1c; }
.ci-hub .badge-st.processing { background:#ffedd5; color:#c2410c; }
.ci-hub .btn-assign {
    height:34px; padding:0 12px; border-radius:10px; font-weight:800; font-size:.8rem;
    display:inline-flex; align-items:center; gap:.35rem; text-decoration:none;
    background:var(--green); color:#fff; border:0;
}
.ci-hub .btn-assign:hover { background:var(--green-dark); color:#fff; }
.ci-hub .btn-ico {
    width:34px; height:34px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center;
    border:1px solid #e2e8f0; background:#fff; color:#475569; text-decoration:none;
}
.ci-hub .btn-ico:hover { background:#f8fafc; color:var(--ink); }
.ci-hub .actions { display:flex; align-items:center; gap:6px; justify-content:flex-end; }

.ci-hub .assign-steps { display:grid; gap:18px; }
.ci-hub .assign-step {
    display:flex; gap:14px; align-items:flex-start;
    padding:14px; border:1px solid #eef3f0; border-radius:16px; background:#fbfdfc;
}
.ci-hub .kpi-mini {
    display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:999px;
    background:#ecfdf3; color:#166534; font-size:.84rem; font-weight:700;
}
.ci-hub .alert { border-radius:14px; }

/* —— Assign tab (compact professional) —— */
.ci-hub .as-scroll {
    scrollbar-width:thin; scrollbar-color:#86efac transparent;
}
.ci-hub .as-scroll::-webkit-scrollbar { width:6px; height:6px; }
.ci-hub .as-scroll::-webkit-scrollbar-thumb {
    background:#86efac; border-radius:999px;
}
.ci-hub .as-scroll::-webkit-scrollbar-track { background:transparent; }

.ci-hub .as-batch {
    display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px;
    margin-bottom:10px; padding:10px 12px;
    background:#fff; border:1px solid #e5eee8; border-radius:12px;
}
.ci-hub .as-batch-main {
    display:flex; flex-wrap:wrap; align-items:center; gap:8px 12px; min-width:0; flex:1;
}
.ci-hub .as-batch-dd { position:relative; min-width:0; }
.ci-hub .as-batch-trigger {
    display:inline-flex; align-items:center; gap:8px; max-width:100%;
    height:36px; padding:0 10px;
    border:1px solid #d7e8de; border-radius:8px;
    background:#f7fbf8; color:#0f172a;
    font-weight:700; font-size:.82rem; cursor:pointer;
}
.ci-hub .as-batch-trigger:hover,
.ci-hub .as-batch-dd.open .as-batch-trigger {
    border-color:#86efac; background:#ecfdf3;
}
.ci-hub .as-batch-trigger .file-ico {
    width:24px; height:24px; border-radius:6px; flex-shrink:0;
    display:inline-flex; align-items:center; justify-content:center;
    background:#fff; color:#16a34a; border:1px solid #d9eee4; font-size:.85rem;
}
.ci-hub .as-batch-trigger .file-name {
    overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:240px;
}
.ci-hub .as-batch-trigger .chev {
    margin-left:2px; color:#64748b; font-size:.7rem; transition:transform .15s ease;
}
.ci-hub .as-batch-dd.open .as-batch-trigger .chev { transform:rotate(180deg); }
.ci-hub .as-batch-menu {
    display:none; position:absolute; left:0; top:calc(100% + 4px); z-index:40;
    min-width:100%; width:max(100%, 300px); max-width:min(92vw, 400px);
    max-height:240px; overflow:auto;
    padding:4px; border-radius:10px;
    background:#fff; border:1px solid #e2e8f0;
    box-shadow:0 12px 28px rgba(15,23,42,.12);
}
.ci-hub .as-batch-dd.open .as-batch-menu { display:block; }
.ci-hub .as-batch-menu a {
    display:flex; align-items:center; gap:8px;
    padding:8px 10px; border-radius:7px;
    color:#334155; text-decoration:none; font-size:.8rem; font-weight:600;
}
.ci-hub .as-batch-menu a:hover { background:#f1f5f9; color:#0f172a; }
.ci-hub .as-batch-menu a.active { background:#ecfdf3; color:#166534; }
.ci-hub .as-batch-menu a .id {
    flex-shrink:0; min-width:30px; color:#94a3b8; font-weight:800; font-size:.72rem;
}
.ci-hub .as-batch-menu a.active .id { color:#16a34a; }
.ci-hub .as-batch-menu a .nm {
    overflow:hidden; text-overflow:ellipsis; white-space:nowrap; flex:1;
}
.ci-hub .as-batch-menu a .open-n {
    flex-shrink:0; font-size:.68rem; font-weight:700; color:#64748b;
    padding:2px 7px; border-radius:999px; background:#f1f5f9;
}
.ci-hub .as-batch-badge {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 8px; border-radius:999px;
    background:#ecfdf3; color:#166534; font-size:.68rem; font-weight:800;
}
.ci-hub .as-batch-meta {
    display:flex; flex-wrap:wrap; align-items:center; gap:4px 12px;
    font-size:.74rem; color:#64748b; font-weight:600;
}
.ci-hub .as-batch-meta strong { color:#0f172a; font-weight:800; }
.ci-hub .as-batch-change {
    display:inline-flex; align-items:center; gap:6px;
    height:34px; padding:0 12px; border-radius:8px;
    border:1px solid #d1e7db; background:#fff; color:#166534;
    font-weight:700; font-size:.78rem; cursor:pointer; text-decoration:none;
}
.ci-hub .as-batch-change:hover { background:#ecfdf3; color:#14532d; }

.ci-hub .as-summary {
    display:flex; flex-wrap:nowrap; align-items:stretch; gap:8px;
    margin-bottom:10px; padding:8px;
    border-radius:12px;
    background:#fff; border:1px solid #e5eee8;
}
.ci-hub .as-summary-left {
    flex:0 0 clamp(180px, 24%, 240px);
    min-width:0; padding:10px 12px; border-radius:10px;
    background:#ecfdf3; border:1px solid #d1fae5;
    display:flex; align-items:center; gap:10px;
}
.ci-hub .as-summary-check {
    width:32px; height:32px; border-radius:999px; flex-shrink:0;
    display:inline-flex; align-items:center; justify-content:center;
    background:#16a34a; color:#fff; font-size:1rem;
}
.ci-hub .as-summary-copy { min-width:0; flex:1; }
.ci-hub .as-summary-title {
    margin:0; font-size:.84rem; font-weight:800; color:#14532d; line-height:1.2;
}
.ci-hub .as-summary-file {
    margin:2px 0 0; font-size:.72rem; font-weight:600; color:#64748b;
    line-height:1.3; word-break:break-word;
}
.ci-hub .as-summary-file span { color:#0f172a; font-weight:700; }
.ci-hub .as-stat-grid {
    flex:1 1 auto; min-width:0;
    display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px;
}
@media (max-width:1100px) {
    .ci-hub .as-summary { flex-wrap:wrap; }
    .ci-hub .as-summary-left { flex:1 1 100%; }
}
@media (max-width:900px) { .ci-hub .as-stat-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media (max-width:520px) { .ci-hub .as-stat-grid { grid-template-columns:1fr; } }
.ci-hub .as-stat {
    display:flex; align-items:center; gap:8px;
    padding:8px 10px; border-radius:10px;
    border:1px solid #eef2f6; background:#fff; min-width:0; min-height:56px;
}
.ci-hub .as-stat-ico {
    width:32px; height:32px; border-radius:999px; flex-shrink:0;
    display:inline-flex; align-items:center; justify-content:center; font-size:.95rem;
}
.ci-hub .as-stat-ico.blue { background:#eff6ff; color:#2563eb; }
.ci-hub .as-stat-ico.green { background:#ecfdf3; color:#16a34a; }
.ci-hub .as-stat-ico.amber { background:#fff7ed; color:#ea580c; }
.ci-hub .as-stat-ico.red { background:#fef2f2; color:#dc2626; }
.ci-hub .as-stat-body { min-width:0; display:flex; flex-direction:column; gap:1px; }
.ci-hub .as-stat-val {
    display:block; font-size:1.05rem; font-weight:800; color:#0f172a; line-height:1.1;
}
.ci-hub .as-stat-lbl {
    display:block; font-size:.68rem; color:#64748b; font-weight:600; line-height:1.2;
}

.ci-hub .as-layout {
    display:grid; grid-template-columns:minmax(0,1fr) 260px; gap:12px; align-items:start;
}
@media (max-width:1100px) { .ci-hub .as-layout { grid-template-columns:1fr; } }

.ci-hub .as-card {
    background:#fff; border:1px solid #e5eee8; border-radius:12px;
    box-shadow:0 2px 8px rgba(15,23,42,.03); padding:14px;
}
.ci-hub .as-card-head {
    display:flex; align-items:flex-start; gap:10px; margin:0 0 12px;
}
.ci-hub .as-card-ico {
    width:34px; height:34px; border-radius:9px; flex-shrink:0;
    display:inline-flex; align-items:center; justify-content:center;
    background:#16a34a; color:#fff; font-size:1rem;
}
.ci-hub .as-card-ico.soft {
    background:#ecfdf3; color:#16a34a; border:1px solid #d1fae5;
}
.ci-hub .as-card-head h3 {
    margin:0; font-size:.95rem; font-weight:800; color:#0f172a; line-height:1.25;
}
.ci-hub .as-card-head p {
    margin:2px 0 0; font-size:.74rem; color:#64748b; font-weight:500; line-height:1.35;
}

.ci-hub .as-methods {
    display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px; margin-bottom:10px;
}
@media (max-width:900px) { .ci-hub .as-methods { grid-template-columns:1fr 1fr; } }
@media (max-width:520px) { .ci-hub .as-methods { grid-template-columns:1fr; } }
.ci-hub .as-method {
    display:flex; flex-direction:column; align-items:flex-start; gap:2px;
    min-height:0; padding:10px 11px;
    border-radius:10px; border:1px solid #e2e8f0; background:#fff;
    cursor:pointer; color:#334155; text-align:left; transition:all .12s ease;
}
.ci-hub .as-method .as-method-top {
    display:inline-flex; align-items:center; gap:6px;
    font-size:.78rem; font-weight:800; line-height:1.2;
}
.ci-hub .as-method i { font-size:.9rem; color:#64748b; }
.ci-hub .as-method .as-method-hint {
    font-size:.68rem; font-weight:500; color:#64748b; line-height:1.3;
}
.ci-hub .as-method.active {
    background:#16a34a; border-color:#16a34a; color:#fff;
    box-shadow:0 4px 10px rgba(22,163,74,.2);
}
.ci-hub .as-method.active i,
.ci-hub .as-method.active .as-method-hint { color:rgba(255,255,255,.9); }
.ci-hub .as-method:hover:not(.active) { border-color:#86efac; background:#f7fdf9; }
.ci-hub .as-method-guide {
    display:flex; flex-wrap:wrap; align-items:center; gap:6px 8px;
    margin:0 0 12px; padding:8px 10px;
    border-radius:8px; background:#f0fdf4; border:1px solid #d1fae5;
    font-size:.74rem; color:#166534; font-weight:600;
}
.ci-hub .as-method-guide .path {
    display:inline-flex; flex-wrap:wrap; align-items:center; gap:5px;
    margin-left:auto; font-weight:700; color:#14532d; font-size:.72rem;
}
.ci-hub .as-method-guide .path .sep { color:#86efac; font-weight:800; }
.ci-hub .as-method-guide .path .on { color:#16a34a; }
.ci-hub .as-field[hidden] { display:none !important; }

.ci-hub .as-fields {
    display:grid; grid-template-columns:1fr 1fr; gap:10px 12px;
}
@media (max-width:700px) { .ci-hub .as-fields { grid-template-columns:1fr; } }
.ci-hub .as-field label {
    display:block; font-size:.72rem; font-weight:700; color:#475569; margin-bottom:5px;
}
.ci-hub .as-field label .req {
    color:#ef4444; margin-left:2px; background:transparent; padding:0;
    font-size:.8rem; text-transform:none; letter-spacing:0; font-weight:800;
}
.ci-hub .as-dd { position:relative; }
.ci-hub .as-dd-native {
    position:absolute !important; width:1px !important; height:1px !important;
    padding:0 !important; margin:-1px !important; overflow:hidden !important;
    clip:rect(0,0,0,0) !important; border:0 !important; opacity:0 !important;
    pointer-events:none !important;
}
.ci-hub .as-dd-trigger {
    display:flex; align-items:center; gap:8px; width:100%;
    min-height:38px; padding:0 32px 0 10px;
    border:1px solid #e2e8f0; border-radius:8px;
    background:#fff; color:#0f172a; font-size:.82rem; font-weight:600;
    text-align:left; cursor:pointer; position:relative;
    transition:border-color .12s ease, box-shadow .12s ease;
}
.ci-hub .as-dd-trigger:hover { border-color:#cbd5e1; }
.ci-hub .as-dd.open .as-dd-trigger,
.ci-hub .as-dd-trigger:focus {
    outline:0; border-color:#86efac;
    box-shadow:0 0 0 3px rgba(22,163,74,.14);
}
.ci-hub .as-dd-ico {
    width:24px; height:24px; border-radius:6px; flex-shrink:0;
    display:inline-flex; align-items:center; justify-content:center;
    background:#ecfdf3; color:#16a34a; font-size:.8rem;
}
.ci-hub .as-dd-text {
    flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
    color:#0f172a;
}
.ci-hub .as-dd-text.is-placeholder { color:#94a3b8; font-weight:500; }
.ci-hub .as-dd-chev {
    position:absolute; right:10px; top:50%; transform:translateY(-50%);
    color:#64748b; font-size:.7rem; pointer-events:none;
    transition:transform .12s ease;
}
.ci-hub .as-dd.open .as-dd-chev { transform:translateY(-50%) rotate(180deg); }
.ci-hub .as-dd-menu {
    display:none; position:absolute; left:0; right:0; top:calc(100% + 4px); z-index:50;
    max-height:200px; overflow:auto; padding:4px;
    background:#fff; border:1px solid #e2e8f0; border-radius:10px;
    box-shadow:0 12px 28px rgba(15,23,42,.12);
}
.ci-hub .as-dd.open .as-dd-menu { display:block; }
.ci-hub .as-dd-item {
    display:block; width:100%; text-align:left; border:0; background:transparent;
    padding:8px 10px; border-radius:7px; cursor:pointer;
    color:#334155; font-size:.8rem; font-weight:600; line-height:1.3;
}
.ci-hub .as-dd-item:hover { background:#f1f5f9; color:#0f172a; }
.ci-hub .as-dd-item.active { background:#ecfdf3; color:#166534; }
.ci-hub .as-dd-item[hidden] { display:none !important; }

.ci-hub .as-options-panel {
    margin-top:12px; padding:12px;
    border-radius:10px; background:#f8faf9; border:1px solid #e8eee9;
}
.ci-hub .as-options-panel > .as-opt-head {
    display:flex; align-items:center; gap:6px;
    margin:0 0 8px; font-size:.82rem; font-weight:800; color:#0f172a;
}
.ci-hub .as-options-panel > .as-opt-head i { color:#16a34a; font-size:.9rem; }
.ci-hub .as-options-row {
    display:grid; grid-template-columns:1fr 1fr; gap:12px 16px;
}
@media (max-width:700px) { .ci-hub .as-options-row { grid-template-columns:1fr; } }
.ci-hub .as-opt-col h4 {
    margin:0 0 6px; font-size:.68rem; font-weight:800; color:#64748b;
    text-transform:uppercase; letter-spacing:.04em;
}
.ci-hub .as-radio, .ci-hub .as-check {
    display:flex; align-items:flex-start; gap:8px;
    padding:4px 0; font-size:.8rem; color:#334155; font-weight:600; cursor:pointer;
}
.ci-hub .as-radio input, .ci-hub .as-check input {
    margin:2px 0 0; accent-color:#16a34a; width:15px; height:15px; flex-shrink:0;
}
.ci-hub .as-radio span, .ci-hub .as-check span { line-height:1.3; }
.ci-hub .as-radio .muted { display:block; margin-top:1px; font-weight:500; font-size:.7rem; color:#94a3b8; }

.ci-hub .as-custom-vrms {
    display:none; margin-top:10px; border:1px solid #e8eee9; border-radius:10px;
    background:#fff; overflow:hidden;
}
.ci-hub .as-custom-vrms.show { display:block; }
.ci-hub .as-vrm-head {
    display:flex; align-items:center; justify-content:space-between;
    padding:8px 12px; background:#f8faf9; border-bottom:1px solid #eef2f6;
    font-size:.72rem; font-weight:800; color:#475569;
}
.ci-hub .as-vrm-list {
    max-height:180px; overflow:auto; padding:4px 6px;
}
.ci-hub .as-vrm-row {
    display:grid; grid-template-columns:18px minmax(0,1fr) auto; align-items:center; gap:8px;
    padding:7px 8px; border-radius:8px;
    font-size:.8rem; font-weight:600; color:#334155; cursor:pointer;
}
.ci-hub .as-vrm-row:hover { background:#f8fafc; }
.ci-hub .as-vrm-row input[type="checkbox"] {
    width:15px; height:15px; margin:0; accent-color:#16a34a;
}
.ci-hub .as-vrm-row .nm {
    min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
}
.ci-hub .as-vrm-row .inputs {
    display:flex; align-items:center; gap:6px; justify-content:flex-end;
}
.ci-hub .as-vrm-row .pct,
.ci-hub .as-vrm-row .cnt {
    width:72px; margin:0; padding:5px 8px; border:1px solid #e2e8f0;
    border-radius:7px; font-size:.78rem; font-weight:700; text-align:right;
}
.ci-hub .as-vrm-row .pct:focus,
.ci-hub .as-vrm-row .cnt:focus {
    outline:0; border-color:#86efac; box-shadow:0 0 0 2px rgba(22,163,74,.12);
}
.ci-hub .as-vrm-row .pct.hidden,
.ci-hub .as-vrm-row .cnt.hidden { display:none; }
.ci-hub .as-split-meter {
    display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:6px;
    margin:0; padding:8px 12px; border-top:1px solid #eef2f6;
    background:#f8fafc; font-size:.74rem; font-weight:700; color:#334155;
}
.ci-hub .as-split-meter.ok { background:#ecfdf3; color:#166534; }
.ci-hub .as-split-meter.bad { background:#fef2f2; color:#b91c1c; }
.ci-hub .as-split-meter .muted { font-weight:600; color:#64748b; }

.ci-hub .as-preview { position:sticky; top:12px; }
.ci-hub .as-preview-table { width:100%; border-collapse:separate; border-spacing:0; font-size:.78rem; }
.ci-hub .as-preview-table th,
.ci-hub .as-preview-table td {
    padding:8px 0; border-bottom:1px solid #f1f5f9; text-align:left; vertical-align:middle;
}
.ci-hub .as-preview-table tr:last-child th,
.ci-hub .as-preview-table tr:last-child td { border-bottom:0; }
.ci-hub .as-preview-table th { width:48%; color:#64748b; font-weight:600; font-size:.72rem; }
.ci-hub .as-preview-table td { color:#0f172a; font-weight:700; text-align:right; word-break:break-word; }
.ci-hub .as-preview-table tr.valid th,
.ci-hub .as-preview-table tr.valid td { background:#ecfdf3; color:#166534; }
.ci-hub .as-preview-table tr.valid th { border-radius:7px 0 0 7px; padding-left:8px; }
.ci-hub .as-preview-table tr.valid td { border-radius:0 7px 7px 0; padding-right:8px; }

.ci-hub .as-footer {
    display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between;
    gap:10px; margin-top:12px;
}
.ci-hub .as-footer-right { display:flex; flex-wrap:wrap; gap:8px; margin-left:auto; }
.ci-hub .btn-ghost {
    display:inline-flex; align-items:center; gap:6px;
    height:36px; padding:0 12px; border-radius:8px;
    border:1px solid #e2e8f0; background:#fff; color:#334155;
    font-weight:700; font-size:.78rem; text-decoration:none; cursor:pointer;
}
.ci-hub .btn-ghost:hover { background:#f8fafc; color:#0f172a; }
.ci-hub .btn-assign-go {
    display:inline-flex; align-items:center; gap:6px;
    height:36px; padding:0 14px; border-radius:8px; border:0;
    background:#16a34a; color:#fff; font-weight:800; font-size:.78rem;
    box-shadow:0 4px 10px rgba(22,163,74,.22); cursor:pointer;
}
.ci-hub .btn-assign-go:hover { background:#15803d; }
.ci-hub .btn-assign-go:disabled { opacity:.55; cursor:not-allowed; box-shadow:none; }

.ci-hub .as-result {
    display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px;
    margin-bottom:16px;
}
@media (max-width:700px) { .ci-hub .as-result { grid-template-columns:1fr; } }
.ci-hub .as-result-card {
    padding:16px; border-radius:14px; border:1px solid #e6edf3; background:#fff;
}
.ci-hub .as-result-card .lbl { font-size:.78rem; font-weight:700; color:#64748b; }
.ci-hub .as-result-card .val { font-size:1.4rem; font-weight:800; margin-top:4px; }
.ci-hub .as-result-card.ok .val { color:#16a34a; }
.ci-hub .as-result-card.skip .val { color:#d97706; }
.ci-hub .as-result-card.fail .val { color:#dc2626; }
</style>
@endpush

@section('content')
@php
    $activeTab = $tab ?? 'import';
    $stats = $batchStats ?? ['total' => 0, 'completed' => 0, 'processing' => 0, 'failed' => 0];
@endphp
<div class="container-fluid px-3 py-3 ci-hub">
    <div class="ci-head">
        <ul class="ci-crumbs">
            <li><a href="{{ url('/') }}">Home</a></li>
            <li class="sep">›</li>
            <li><a href="{{ route('goal.leads.index') }}">Lead Management</a></li>
            <li class="sep">›</li>
            <li class="current">Campaigns</li>
        </ul>
        <div class="hero">
            <div class="hero-left">
                <div class="hero-main">
                    <span class="hero-icon" aria-hidden="true"><i class="bi bi-megaphone"></i></span>
                    <div class="hero-copy">
                        <h1 class="hero-title">Campaigns</h1>
                        <p class="hero-sub">Import contacts, then assign to cluster, branch, or VRM.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="ci-shell">
        <nav class="ci-tabs" role="tablist" aria-label="Campaign steps">
            <a class="ci-tab {{ $activeTab === 'import' ? 'active' : '' }}"
               href="{{ route('goal.contacts.import', ['tab' => 'import']) }}"
               role="tab" aria-selected="{{ $activeTab === 'import' ? 'true' : 'false' }}">
                <span class="num">1</span> Import
            </a>
            @if($canSplit)
            <a class="ci-tab {{ $activeTab === 'assign' ? 'active' : '' }}"
               href="{{ route('goal.contacts.import', array_filter(['tab' => 'assign', 'batch' => $selectedBatchId ?: null])) }}"
               role="tab" aria-selected="{{ $activeTab === 'assign' ? 'true' : 'false' }}">
                <span class="num">2</span> Assign
            </a>
            @else
            <span class="ci-tab disabled" role="tab" aria-disabled="true"><span class="num">2</span> Assign</span>
            @endif
        </nav>
        <div class="ci-panel">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(!empty($assignResult) && is_array($assignResult))
        <div class="as-result">
            <div class="as-result-card ok">
                <div class="lbl">Assigned</div>
                <div class="val">{{ number_format((int)($assignResult['assigned'] ?? 0)) }}</div>
            </div>
            <div class="as-result-card skip">
                <div class="lbl">Skipped (duplicates / invalid)</div>
                <div class="val">{{ number_format((int)($assignResult['skipped'] ?? 0)) }}</div>
            </div>
            <div class="as-result-card fail">
                <div class="lbl">Failed</div>
                <div class="val">{{ number_format((int)($assignResult['failed'] ?? 0)) }}</div>
            </div>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @if($activeTab === 'import')
    <div class="ci-grid">
        <div class="ci-card">
            <div class="ci-card-head">
                <div class="upload-head-left">
                    <span class="upload-head-ico"><i class="bi bi-upload"></i></span>
                    <div>
                        <h2>Upload Contact List</h2>
                        <p>Accepted file types: .csv, .xlsx, .xls | Max file size: 10 MB</p>
                    </div>
                </div>
                <div class="dropdown">
                    <button class="btn-tpl dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-download"></i> Download Template
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="{{ route('goal.contacts.import.template', ['format' => 'csv']) }}">
                                <i class="bi bi-filetype-csv me-2"></i> CSV template
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('goal.contacts.import.template', ['format' => 'xlsx']) }}">
                                <i class="bi bi-file-earmark-excel me-2"></i> Excel template
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="ci-card-body">
                <form action="{{ route('goal.contacts.import.store') }}" method="POST" enctype="multipart/form-data" id="ciImportForm">
                    @csrf
                    <div class="ci-drop" id="ciDrop" role="button" tabindex="0" aria-label="Upload contact file">
                        <div class="drop-cloud"><i class="bi bi-cloud-arrow-up"></i></div>
                        <h3>Drag &amp; drop your file here</h3>
                        <p class="drop-sub">or click to browse from your computer</p>
                        <div class="drop-pills">
                            <span class="drop-pill"><i class="bi bi-filetype-csv"></i> .CSV</span>
                            <span class="drop-pill"><i class="bi bi-file-earmark-excel"></i> .XLSX / .XLS</span>
                        </div>
                        <label class="btn-choose" id="ciChooseBtn">
                            <i class="bi bi-folder2-open"></i> Choose File
                            <input type="file" name="import_file" id="import_file" accept=".csv,.xlsx,.xls,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                        </label>
                        <div class="file-name" id="importFileName">No file chosen</div>
                    </div>

                    <div class="ci-actions">
                        <button type="submit" class="btn-import">
                            <i class="bi bi-cloud-upload me-1"></i> Upload &amp; Import
                        </button>
                        <span class="hint">
                            <i class="bi bi-info-circle"></i>
                            Upload creates a batch. Next: Assign to cluster, branch, or VRM.
                        </span>
                    </div>
                </form>
            </div>
        </div>

        <div class="ci-card">
            <div class="ci-card-head">
                <div>
                    <h2>Column Guide</h2>
                    <p>Required and optional columns for a successful import</p>
                </div>
            </div>
            <div class="ci-card-body">
                <ul class="guide-list">
                    <li class="guide-item">
                        <span class="guide-ico req"><i class="bi bi-telephone"></i></span>
                        <div class="guide-meta">
                            <div class="guide-name">mobile <span class="pill req">Required</span></div>
                            <p class="guide-desc">10-digit mobile number</p>
                        </div>
                    </li>
                    <li class="guide-item">
                        <span class="guide-ico req"><i class="bi bi-person"></i></span>
                        <div class="guide-meta">
                            <div class="guide-name">name <span class="pill req">Required</span></div>
                            <p class="guide-desc">Customer / contact name</p>
                        </div>
                    </li>
                    <li class="guide-item">
                        <span class="guide-ico opt"><i class="bi bi-ui-checks-grid"></i></span>
                        <div class="guide-meta">
                            <div class="guide-name">email, product, loan_amount, source, notes <span class="pill opt">Optional</span></div>
                            <p class="guide-desc">Additional contact and loan details</p>
                        </div>
                    </li>
                    @if($canPickBranch)
                    <li class="guide-item">
                        <span class="guide-ico opt"><i class="bi bi-building"></i></span>
                        <div class="guide-meta">
                            <div class="guide-name">branch_code <span class="pill opt">Optional</span></div>
                            <p class="guide-desc">Branch or RD / Cluster code for row-level mapping</p>
                        </div>
                    </li>
                    @endif
                </ul>
                <div class="sample-bar">
                    <span><i class="bi bi-eye me-1"></i> Need a filled example?</span>
                    <a href="{{ route('goal.contacts.import.template', ['format' => 'csv']) }}">View Sample</a>
                </div>
            </div>
        </div>
    </div>

    <div class="ci-card batches-card">
        <div class="ci-card-head" style="padding-bottom:12px;">
            <div>
                <h2>Recent Batches</h2>
                <p>Review import results, then assign unassigned contacts</p>
            </div>
        </div>
        <div class="stats-row">
            <div class="stat">
                <div class="lbl">Total Batches</div>
                <div class="val">{{ $stats['total'] }}</div>
            </div>
            <div class="stat ok">
                <div class="lbl">Completed</div>
                <div class="val">{{ $stats['completed'] }}</div>
            </div>
            <div class="stat warn">
                <div class="lbl">In Progress</div>
                <div class="val">{{ $stats['processing'] }}</div>
            </div>
            <div class="stat bad">
                <div class="lbl">Failed</div>
                <div class="val">{{ $stats['failed'] }}</div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="ci-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>File Name</th>
                        <th>Organisation</th>
                        <th>Results</th>
                        <th>Unassigned</th>
                        <th>Status</th>
                        <th>Imported On</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                        @php
                            $statusLabel = match ($batch->status) {
                                'ready', 'partial' => 'Completed',
                                'processing' => 'In Progress',
                                'failed' => 'Failed',
                                default => ucfirst($batch->status),
                            };
                            $ext = strtolower(pathinfo($batch->filename, PATHINFO_EXTENSION));
                            $fileIcon = $ext === 'csv' ? 'bi-filetype-csv' : 'bi-file-earmark-excel';
                        @endphp
                        <tr>
                            <td>{{ $batch->id }}</td>
                            <td>
                                <div class="file-cell">
                                    <i class="bi {{ $fileIcon }}"></i>
                                    <span class="name" title="{{ $batch->filename }}">{{ $batch->filename }}</span>
                                </div>
                            </td>
                            <td>{{ $batch->orgUnit?->name ?? '—' }}</td>
                            <td>{{ $batch->success_count }}/{{ $batch->total_rows }} ({{ $batch->success_count }} OK, {{ $batch->failed_count }} Fail)</td>
                            <td>{{ (int) ($batch->unassigned_count ?? 0) }}</td>
                            <td>
                                <span class="badge-st {{ $batch->status }}">
                                    @if(in_array($batch->status, ['ready', 'partial'], true))
                                        <i class="bi bi-check-circle-fill"></i>
                                    @elseif($batch->status === 'failed')
                                        <i class="bi bi-x-circle-fill"></i>
                                    @else
                                        <i class="bi bi-hourglass-split"></i>
                                    @endif
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td>{{ $batch->created_at?->format('d M Y, H:i') }}</td>
                            <td>
                                <div class="actions">
                                    @if($batch->failed_count > 0)
                                        <a href="{{ route('goal.contacts.import.errors', $batch) }}" class="btn-ico" title="Error report">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    @endif
                                    @if($canSplit && (int) ($batch->unassigned_count ?? 0) > 0)
                                        <a href="{{ route('goal.contacts.import', ['tab' => 'assign', 'batch' => $batch->id]) }}" class="btn-assign">
                                            Assign
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">No imports yet. Upload a contact list to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($batches->hasPages())
            <div class="p-3">{{ $batches->links() }}</div>
        @endif
    </div>
    @endif

    @if($activeTab === 'assign' && $canSplit)
    @php
        $summary = $batchSummary ?? ['total' => 0, 'valid' => 0, 'duplicates' => 0, 'invalid' => 0, 'unassigned' => 0];
        $draft = $assignDraft ?? [];
        $draftMethod = $draft['method'] ?? ($canPickBranch ? 'cluster' : 'vrm');
        if (! $canPickBranch && in_array($draftMethod, ['branch', 'cluster'], true)) {
            $draftMethod = 'vrm';
        }
        $draftAlloc = $draft['allocation'] ?? 'all';
        $draftStatus = $draft['lead_status'] ?? 'Future Prospect';
        $draftRegion = (int) ($draft['region_id'] ?? 0);
        $draftCluster = (int) ($draft['cluster_id'] ?? 0);
        $draftBranch = (int) ($draft['branch_id'] ?? 0);
        $draftVrm = (int) ($draft['vrm_id'] ?? 0);
        $draftVrmIds = array_map('intval', $draft['vrm_ids'] ?? []);
        $draftPercents = $draft['percentages'] ?? [];
        $draftCounts = $draft['counts'] ?? [];
        $validCount = (int) ($summary['unassigned'] ?? $unassigned->count());
        $orgTreeJson = $orgTree ?? ['regions' => [], 'clusters' => [], 'branches' => []];
    @endphp

    @if(!$selectedBatch)
        <div class="alert alert-info">Upload a file on Import first, then open Assign from that batch.</div>
    @else
        {{-- Selected batch bar --}}
        <div class="as-batch">
            <div class="as-batch-main">
                <div class="as-batch-dd" id="asBatchDd">
                    <button type="button" class="as-batch-trigger" id="asBatchTrigger" aria-haspopup="listbox" aria-expanded="false">
                        <span class="file-ico"><i class="bi bi-file-earmark-spreadsheet"></i></span>
                        <span class="file-name">#{{ $selectedBatch->id }} · {{ $selectedBatch->filename }}</span>
                        <i class="bi bi-chevron-down chev"></i>
                    </button>
                    <div class="as-batch-menu as-scroll" role="listbox" id="asBatchMenu">
                        @foreach($batches as $batch)
                            <a role="option"
                               class="{{ (int)$selectedBatchId === (int)$batch->id ? 'active' : '' }}"
                               href="{{ route('goal.contacts.import', ['tab' => 'assign', 'batch' => $batch->id]) }}"
                               @if((int)$selectedBatchId === (int)$batch->id) aria-selected="true" @endif>
                                <span class="id">#{{ $batch->id }}</span>
                                <span class="nm">{{ $batch->filename }}</span>
                                <span class="open-n">{{ (int)($batch->unassigned_count ?? 0) }} open</span>
                            </a>
                        @endforeach
                    </div>
                </div>
                <span class="as-batch-badge"><i class="bi bi-check-circle-fill"></i> Imported</span>
                <div class="as-batch-meta">
                    <span>Imported On: <strong>{{ optional($selectedBatch->created_at)->format('d M Y, H:i') }}</strong></span>
                    <span>Total: <strong>{{ number_format((int)$summary['total']) }}</strong></span>
                    <span>Valid: <strong>{{ number_format($validCount) }}</strong></span>
                </div>
            </div>
            <button type="button" class="as-batch-change" id="asChangeBatch" title="Change batch">
                <i class="bi bi-arrow-left-right"></i> Change Batch
            </button>
        </div>

        {{-- Import Completed summary --}}
        <div class="as-summary">
            <div class="as-summary-left">
                <span class="as-summary-check" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
                <div class="as-summary-copy">
                    <p class="as-summary-title">Import Completed</p>
                    <p class="as-summary-file">File: <span>{{ $selectedBatch->filename }}</span></p>
                </div>
            </div>
            <div class="as-stat-grid">
                <div class="as-stat">
                    <span class="as-stat-ico blue" aria-hidden="true"><i class="bi bi-people-fill"></i></span>
                    <div class="as-stat-body">
                        <span class="as-stat-val">{{ number_format((int)$summary['total']) }}</span>
                        <span class="as-stat-lbl">Total Records</span>
                    </div>
                </div>
                <div class="as-stat">
                    <span class="as-stat-ico green" aria-hidden="true"><i class="bi bi-check2"></i></span>
                    <div class="as-stat-body">
                        <span class="as-stat-val">{{ number_format($validCount) }}</span>
                        <span class="as-stat-lbl">Valid (Ready to Assign)</span>
                    </div>
                </div>
                <div class="as-stat">
                    <span class="as-stat-ico amber" aria-hidden="true"><i class="bi bi-exclamation-triangle-fill"></i></span>
                    <div class="as-stat-body">
                        <span class="as-stat-val">{{ number_format((int)$summary['duplicates']) }}</span>
                        <span class="as-stat-lbl">Duplicates</span>
                    </div>
                </div>
                <div class="as-stat">
                    <span class="as-stat-ico red" aria-hidden="true"><i class="bi bi-x-lg"></i></span>
                    <div class="as-stat-body">
                        <span class="as-stat-val">{{ number_format((int)$summary['invalid']) }}</span>
                        <span class="as-stat-lbl">Invalid</span>
                    </div>
                </div>
            </div>
        </div>

        @if($validCount < 1)
            <div class="alert alert-info">No valid unassigned contacts remain in this batch. Invalid and duplicate rows are never assigned.</div>
            <div class="as-footer">
                <a href="{{ route('goal.contacts.import', ['tab' => 'import']) }}" class="btn-ghost">
                    <i class="bi bi-arrow-left"></i> Back to Import
                </a>
            </div>
        @else
        <form method="POST" action="{{ route('goal.contacts.import.split.store', $selectedBatch) }}" id="assignForm">
            @csrf
            <input type="hidden" name="method" id="assignMethod" value="{{ $draftMethod }}">
            <input type="hidden" name="allocation" id="assignAllocation" value="{{ $draftAlloc }}">

            <div class="as-layout">
                <div class="as-card">
                    <div class="as-card-head">
                        <span class="as-card-ico"><i class="bi bi-diagram-3"></i></span>
                        <div>
                            <h3>Assign Contacts</h3>
                            <p>
                                @if($canPickBranch)
                                    Send to a cluster, a branch under it, or directly to VRMs.
                                @else
                                    Assign imported contacts to VRMs in your branch.
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="as-methods" role="group" aria-label="Assignment method">
                        @if($canPickBranch)
                        <button type="button" class="as-method {{ $draftMethod === 'cluster' ? 'active' : '' }}" data-method="cluster">
                            <span class="as-method-top"><i class="bi bi-diagram-3"></i> To Cluster</span>
                            <span class="as-method-hint">Park at cluster level</span>
                        </button>
                        <button type="button" class="as-method {{ $draftMethod === 'branch' ? 'active' : '' }}" data-method="branch">
                            <span class="as-method-top"><i class="bi bi-building"></i> To Branch</span>
                            <span class="as-method-hint">Cluster → Branch</span>
                        </button>
                        @endif
                        <button type="button" class="as-method {{ $draftMethod === 'vrm' ? 'active' : '' }}" data-method="vrm">
                            <span class="as-method-top"><i class="bi bi-person-badge"></i> To VRM</span>
                            <span class="as-method-hint">One VRM receives all</span>
                        </button>
                        <button type="button" class="as-method {{ $draftMethod === 'custom' ? 'active' : '' }}" data-method="custom">
                            <span class="as-method-top"><i class="bi bi-sliders"></i> Split to VRMs</span>
                            <span class="as-method-hint">Equal, %, or custom qty</span>
                        </button>
                    </div>

                    <div class="as-method-guide" id="asMethodGuide" aria-live="polite">
                        <span id="asMethodGuideText">Choose where these contacts should go.</span>
                        <span class="path" id="asMethodPath" hidden></span>
                    </div>

                    <div class="as-fields">
                        @if($canPickBranch)
                        <div class="as-field" data-for="cluster,branch,vrm,custom" data-show="region">
                            <label>Organisation / Region <span class="req">*</span></label>
                            <div class="as-dd" data-dd>
                                <button type="button" class="as-dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="as-dd-ico"><i class="bi bi-building"></i></span>
                                    <span class="as-dd-text is-placeholder">— Select region —</span>
                                    <i class="bi bi-chevron-down as-dd-chev"></i>
                                </button>
                                <select name="region_id" id="asRegion" class="as-dd-native no-select2" data-select2-skip="1" tabindex="-1" aria-hidden="true">
                                    <option value="">— Select region —</option>
                                    @foreach(($orgOptions['regions'] ?? collect()) as $r)
                                        <option value="{{ $r->id }}" @selected($draftRegion === (int)$r->id)>
                                            {{ $r->name }}{{ $r->code ? ' ('.$r->code.')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="as-dd-menu as-scroll" role="listbox"></div>
                            </div>
                        </div>
                        <div class="as-field" data-for="cluster,branch,vrm,custom" data-show="cluster">
                            <label>Cluster <span class="req">*</span></label>
                            <div class="as-dd" data-dd>
                                <button type="button" class="as-dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="as-dd-ico"><i class="bi bi-diagram-3"></i></span>
                                    <span class="as-dd-text is-placeholder">— Select cluster —</span>
                                    <i class="bi bi-chevron-down as-dd-chev"></i>
                                </button>
                                <select name="cluster_id" id="asCluster" class="as-dd-native no-select2" data-select2-skip="1" tabindex="-1" aria-hidden="true">
                                    <option value="">— Select cluster —</option>
                                    @foreach(($orgOptions['clusters'] ?? $clusters) as $c)
                                        <option value="{{ $c->id }}"
                                            data-parent="{{ (int)($c->parent_id ?? 0) }}"
                                            @selected($draftCluster === (int)$c->id)>
                                            {{ $c->name }}{{ $c->code ? ' ('.$c->code.')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="as-dd-menu as-scroll" role="listbox"></div>
                            </div>
                        </div>
                        <div class="as-field" data-for="branch,vrm,custom" data-show="branch">
                            <label><span id="asBranchLabelText">Branch</span> <span class="req" id="asBranchReq">*</span></label>
                            <div class="as-dd" data-dd>
                                <button type="button" class="as-dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="as-dd-ico"><i class="bi bi-shop"></i></span>
                                    <span class="as-dd-text is-placeholder">— Select branch —</span>
                                    <i class="bi bi-chevron-down as-dd-chev"></i>
                                </button>
                                <select name="branch_id" id="asBranch" class="as-dd-native no-select2" data-select2-skip="1" tabindex="-1" aria-hidden="true">
                                    <option value="">— Select branch —</option>
                                    @foreach(($orgOptions['branches'] ?? $branches) as $b)
                                        <option value="{{ $b->id }}"
                                            data-parent="{{ (int)($b->parent_id ?? 0) }}"
                                            @selected($draftBranch === (int)$b->id)>
                                            {{ $b->name }}{{ $b->code ? ' ('.$b->code.')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="as-dd-menu as-scroll" role="listbox"></div>
                            </div>
                        </div>
                        @endif
                        <div class="as-field" data-for="vrm" data-show="vrm" id="asVrmField">
                            <label>VRM <span class="req">*</span></label>
                            <div class="as-dd" data-dd>
                                <button type="button" class="as-dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="as-dd-ico"><i class="bi bi-person-badge"></i></span>
                                    <span class="as-dd-text is-placeholder">— Select VRM —</span>
                                    <i class="bi bi-chevron-down as-dd-chev"></i>
                                </button>
                                <select name="vrm_id" id="asVrm" class="as-dd-native no-select2" data-select2-skip="1" tabindex="-1" aria-hidden="true">
                                    <option value="">— Select VRM —</option>
                                    @foreach($vrms as $vrm)
                                        <option value="{{ $vrm->id }}" @selected($draftVrm === (int)$vrm->id)>{{ $vrm->name }}</option>
                                    @endforeach
                                </select>
                                <div class="as-dd-menu as-scroll" role="listbox"></div>
                            </div>
                        </div>
                        <div class="as-field" data-for="cluster,branch,vrm,custom" data-show="status">
                            <label>Lead Status <span class="req">*</span></label>
                            <div class="as-dd" data-dd>
                                <button type="button" class="as-dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="as-dd-ico"><i class="bi bi-bookmark"></i></span>
                                    <span class="as-dd-text is-placeholder">— Select status —</span>
                                    <i class="bi bi-chevron-down as-dd-chev"></i>
                                </button>
                                <select name="lead_status" id="asLeadStatus" class="as-dd-native no-select2" data-select2-skip="1" tabindex="-1" aria-hidden="true">
                                    <option value="Future Prospect" @selected($draftStatus === 'Future Prospect')>Future Prospect (dialer)</option>
                                    <option value="Pending BM Allocation" @selected($draftStatus === 'Pending BM Allocation')>Pending BM Allocation</option>
                                </select>
                                <div class="as-dd-menu as-scroll" role="listbox"></div>
                            </div>
                        </div>
                    </div>

                    <div class="as-custom-vrms {{ $draftMethod === 'custom' ? 'show' : '' }}" id="asCustomVrms">
                        <div class="as-vrm-head">
                            <span>Select VRMs</span>
                            <span id="asVrmHeadRight">{{ $draftAlloc === 'percentage' ? '%' : ($draftAlloc === 'counts' ? 'Qty' : '') }}</span>
                        </div>
                        <div class="as-vrm-list as-scroll">
                            @forelse($vrms as $vrm)
                                <label class="as-vrm-row">
                                    <input type="checkbox" name="vrm_ids[]" value="{{ $vrm->id }}" class="js-custom-vrm"
                                        @checked(in_array((int)$vrm->id, $draftVrmIds, true))>
                                    <span class="nm">{{ $vrm->name }}</span>
                                    <span class="inputs">
                                        <input type="number" min="0" max="100" step="1"
                                               class="pct {{ $draftAlloc === 'percentage' ? '' : 'hidden' }} js-pct"
                                               name="percentages[{{ $vrm->id }}]"
                                               value="{{ $draftPercents[$vrm->id] ?? '' }}"
                                               placeholder="%">
                                        <input type="number" min="0" max="{{ max($validCount, 0) }}" step="1"
                                               class="cnt {{ $draftAlloc === 'counts' ? '' : 'hidden' }} js-cnt"
                                               name="counts[{{ $vrm->id }}]"
                                               value="{{ $draftCounts[$vrm->id] ?? '' }}"
                                               placeholder="0"
                                               data-valid-total="{{ $validCount }}">
                                    </span>
                                </label>
                            @empty
                                <div class="text-muted px-2 py-2" style="font-size:.78rem;">No VRMs in your scope.</div>
                            @endforelse
                        </div>
                        <div class="as-split-meter {{ $draftAlloc === 'counts' ? '' : 'd-none' }}" id="asSplitMeter" data-total="{{ $validCount }}">
                            <span>Allocated: <strong id="asSplitAllocated">0</strong> / {{ number_format($validCount) }}</span>
                            <span class="muted" id="asSplitMeterHint">Must total {{ number_format($validCount) }}</span>
                        </div>
                    </div>

                    <div class="as-options-panel">
                        <div class="as-opt-head"><i class="bi bi-gear"></i> Assignment Options</div>
                        <div class="as-options-row">
                            <div class="as-opt-col">
                                <h4>Assignment type</h4>
                                <label class="as-radio">
                                    <input type="radio" name="allocation_ui" value="all" @checked($draftAlloc === 'all')>
                                    <span>All Contacts ({{ number_format($validCount) }})</span>
                                </label>
                                <label class="as-radio">
                                    <input type="radio" name="allocation_ui" value="equal" @checked($draftAlloc === 'equal')>
                                    <span>Assign in Equal Split</span>
                                </label>
                                <label class="as-radio">
                                    <input type="radio" name="allocation_ui" value="percentage" @checked($draftAlloc === 'percentage')>
                                    <span>Assign by Percentage</span>
                                </label>
                                <label class="as-radio">
                                    <input type="radio" name="allocation_ui" value="counts" @checked($draftAlloc === 'counts')>
                                    <span>Custom count split<span class="muted">Qty per VRM must total {{ number_format($validCount) }}</span></span>
                                </label>
                            </div>
                            <div class="as-opt-col">
                                <h4>Additional settings</h4>
                                <label class="as-check">
                                    <input type="checkbox" name="create_lead" value="1" @checked(($draft['create_lead'] ?? true))>
                                    <span>Create as Lead (if not exists)</span>
                                </label>
                                <label class="as-check">
                                    <input type="checkbox" name="update_existing" value="1" @checked(($draft['update_existing'] ?? true))>
                                    <span>Update existing contact details</span>
                                </label>
                                <label class="as-check">
                                    <input type="checkbox" name="skip_duplicates" value="1" @checked(array_key_exists('skip_duplicates', $draft) ? !empty($draft['skip_duplicates']) : false)>
                                    <span>Skip duplicate contacts</span>
                                </label>
                                <label class="as-check">
                                    <input type="checkbox" name="notify" value="1" @checked(!empty($draft['notify']))>
                                    <span>Send notification to assignee</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="as-card as-preview">
                    <div class="as-card-head">
                        <span class="as-card-ico soft"><i class="bi bi-eye"></i></span>
                        <div>
                            <h3>Assign Preview</h3>
                            <p>Confirm before assigning.</p>
                        </div>
                    </div>
                    <table class="as-preview-table">
                        <tr>
                            <th>Total Imported</th>
                            <td id="pvTotal">{{ number_format((int)$summary['total']) }}</td>
                        </tr>
                        <tr class="valid">
                            <th>Valid for Assignment</th>
                            <td id="pvValid">{{ number_format($validCount) }}</td>
                        </tr>
                        <tr>
                            <th>Assign To</th>
                            <td id="pvMethod">—</td>
                        </tr>
                        <tr data-pv="region">
                            <th>Organisation / Region</th>
                            <td id="pvRegion">—</td>
                        </tr>
                        <tr data-pv="cluster">
                            <th>Cluster</th>
                            <td id="pvCluster">—</td>
                        </tr>
                        <tr data-pv="branch">
                            <th>Branch</th>
                            <td id="pvBranch">—</td>
                        </tr>
                        <tr data-pv="vrm">
                            <th>VRM</th>
                            <td id="pvVrm">—</td>
                        </tr>
                        <tr>
                            <th>Lead Status</th>
                            <td id="pvStatus">—</td>
                        </tr>
                    </table>
                </aside>
            </div>

            <div class="as-footer">
                <a href="{{ route('goal.contacts.import', ['tab' => 'import']) }}" class="btn-ghost">
                    <i class="bi bi-arrow-left"></i> Back to Import
                </a>
                <div class="as-footer-right">
                    <button type="submit" class="btn-ghost" formaction="{{ route('goal.contacts.import.draft', $selectedBatch) }}" formnovalidate>
                        <i class="bi bi-floppy"></i> Save as Draft
                    </button>
                    <button type="submit" class="btn-assign-go" id="assignConfirmBtn"
                            onclick="return confirm('Assign {{ number_format($validCount) }} valid contact(s) now? Invalid and duplicate rows will not be assigned.');">
                        <i class="bi bi-people-fill"></i> Assign Contacts
                    </button>
                </div>
            </div>
        </form>
        @endif
    @endif
    @endif
        </div>{{-- /.ci-panel --}}
    </div>{{-- /.ci-shell --}}
</div>
@endsection

@push('scripts')
<script>
(function () {
    var input = document.getElementById('import_file');
    var nameEl = document.getElementById('importFileName');
    var drop = document.getElementById('ciDrop');
    var chooseBtn = document.getElementById('ciChooseBtn');
    var allowedExt = ['csv', 'xlsx', 'xls'];

    if (input && nameEl && drop) {
        function isAllowed(file) {
            if (!file || !file.name) return false;
            var parts = file.name.toLowerCase().split('.');
            var ext = parts.length > 1 ? parts.pop() : '';
            return allowedExt.indexOf(ext) !== -1;
        }

        function setFile(file) {
            if (!file) return;
            if (!isAllowed(file)) {
                nameEl.textContent = 'Please choose a .csv, .xlsx, or .xls file';
                nameEl.classList.remove('has-file');
                drop.classList.remove('has-file');
                input.value = '';
                return;
            }
            try {
                var dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
            } catch (err) {
                // Fallback: some browsers only accept assignment from drop
            }
            nameEl.textContent = file.name + (file.size ? ' · ' + Math.max(1, Math.round(file.size / 1024)) + ' KB' : '');
            nameEl.classList.add('has-file');
            drop.classList.add('has-file');
        }

        function showFromInput() {
            var file = input.files && input.files[0];
            if (file) {
                setFile(file);
            } else {
                nameEl.textContent = 'No file chosen';
                nameEl.classList.remove('has-file');
                drop.classList.remove('has-file');
            }
        }

        input.addEventListener('change', showFromInput);

        drop.addEventListener('click', function (e) {
            if (chooseBtn && (chooseBtn === e.target || chooseBtn.contains(e.target))) return;
            input.click();
        });
        drop.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                input.click();
            }
        });

        ['dragenter', 'dragover'].forEach(function (evt) {
            drop.addEventListener(evt, function (e) {
                e.preventDefault();
                e.stopPropagation();
                drop.classList.add('is-drag');
            });
        });
        ['dragleave', 'dragend'].forEach(function (evt) {
            drop.addEventListener(evt, function (e) {
                e.preventDefault();
                drop.classList.remove('is-drag');
            });
        });
        drop.addEventListener('drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            drop.classList.remove('is-drag');
            var files = e.dataTransfer && e.dataTransfer.files;
            if (!files || !files.length) return;
            setFile(files[0]);
        });
    }

    var batchDd = document.getElementById('asBatchDd');
    var batchTrigger = document.getElementById('asBatchTrigger');
    var changeBatchBtn = document.getElementById('asChangeBatch');
    function toggleBatchDd(force) {
        if (!batchDd) return;
        var open = typeof force === 'boolean' ? force : !batchDd.classList.contains('open');
        batchDd.classList.toggle('open', open);
        if (batchTrigger) batchTrigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    if (batchTrigger) {
        batchTrigger.addEventListener('click', function (e) {
            e.stopPropagation();
            toggleBatchDd();
        });
    }
    if (changeBatchBtn) {
        changeBatchBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            toggleBatchDd(true);
            if (batchTrigger) batchTrigger.focus();
        });
    }
    document.addEventListener('click', function (e) {
        if (!batchDd) return;
        if (!batchDd.contains(e.target) && e.target !== changeBatchBtn && !(changeBatchBtn && changeBatchBtn.contains(e.target))) {
            toggleBatchDd(false);
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') toggleBatchDd(false);
    });

    var methodInput = document.getElementById('assignMethod');
    var allocInput = document.getElementById('assignAllocation');
    var form = document.getElementById('assignForm');
    if (!methodInput || !form) return;

    var methodBtns = document.querySelectorAll('.as-method');
    var regionSel = document.getElementById('asRegion');
    var clusterSel = document.getElementById('asCluster');
    var branchSel = document.getElementById('asBranch');
    var vrmSel = document.getElementById('asVrm');
    var vrmField = document.getElementById('asVrmField');
    var statusSel = document.getElementById('asLeadStatus');
    var customBox = document.getElementById('asCustomVrms');
    var methodLabels = { branch: 'Branch', cluster: 'Cluster', vrm: 'VRM', custom: 'Split to VRMs' };
    var methodGuides = {
        cluster: 'Send contacts to a Cluster. They stay at cluster level until a branch/VRM picks them up.',
        branch: 'Choose Region → Cluster → Branch. Contacts move to that branch under the selected cluster.',
        vrm: 'Optionally set Region → Cluster → Branch, then assign every valid contact to one VRM.',
        custom: 'Select Region/Cluster/Branch context, then split contacts across VRMs (equal or %).'
    };

    function closeAllDd(except) {
        document.querySelectorAll('.as-dd.open').forEach(function (dd) {
            if (except && dd === except) return;
            dd.classList.remove('open');
            var t = dd.querySelector('.as-dd-trigger');
            if (t) t.setAttribute('aria-expanded', 'false');
        });
    }

    function syncDdUi(sel) {
        if (!sel) return;
        var dd = sel.closest('.as-dd');
        if (!dd) return;
        var textEl = dd.querySelector('.as-dd-text');
        var menu = dd.querySelector('.as-dd-menu');
        var opt = sel.options[sel.selectedIndex];
        var label = opt ? opt.text.trim() : '';
        var isEmpty = !sel.value;
        if (textEl) {
            textEl.textContent = label || '—';
            textEl.classList.toggle('is-placeholder', isEmpty);
        }
        if (menu) {
            menu.querySelectorAll('.as-dd-item').forEach(function (item) {
                var val = item.getAttribute('data-value') || '';
                item.classList.toggle('active', val === String(sel.value));
                var matching = null;
                Array.prototype.forEach.call(sel.options, function (o) {
                    if (String(o.value) === val) matching = o;
                });
                if (matching) {
                    item.hidden = !!matching.hidden;
                }
            });
        }
    }

    function buildDd(dd) {
        var sel = dd.querySelector('select.as-dd-native');
        var trigger = dd.querySelector('.as-dd-trigger');
        var menu = dd.querySelector('.as-dd-menu');
        if (!sel || !trigger || !menu) return;

        menu.innerHTML = '';
        Array.prototype.forEach.call(sel.options, function (opt) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'as-dd-item' + (opt.value === sel.value ? ' active' : '');
            btn.setAttribute('role', 'option');
            btn.setAttribute('data-value', opt.value);
            btn.textContent = opt.text.trim();
            if (opt.hidden) btn.hidden = true;
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                sel.value = opt.value;
                sel.dispatchEvent(new Event('change', { bubbles: true }));
                closeAllDd();
            });
            menu.appendChild(btn);
        });
        syncDdUi(sel);

        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var willOpen = !dd.classList.contains('open');
            closeAllDd();
            if (willOpen) {
                dd.classList.add('open');
                trigger.setAttribute('aria-expanded', 'true');
            }
        });
    }

    document.querySelectorAll('.as-dd[data-dd]').forEach(buildDd);

    document.addEventListener('click', function () { closeAllDd(); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeAllDd();
    });

    function selectedText(sel) {
        if (!sel || !sel.value) return '—';
        var opt = sel.options[sel.selectedIndex];
        return opt ? opt.text.trim() : '—';
    }

    function filterByParent(sel, parentId, keepValue) {
        if (!sel) return;
        var pid = parentId ? String(parentId) : '';
        Array.prototype.forEach.call(sel.options, function (opt, i) {
            if (i === 0) { opt.hidden = false; return; }
            var p = opt.getAttribute('data-parent') || '0';
            var show = !pid || p === pid || p === '0';
            opt.hidden = !show;
            if (!show && opt.value === sel.value) sel.value = '';
        });
        if (keepValue) {
            var found = Array.prototype.some.call(sel.options, function (o) {
                return o.value === String(keepValue) && !o.hidden;
            });
            if (found) sel.value = String(keepValue);
        }
        syncDdUi(sel);
    }

    function syncCascade() {
        var regionId = regionSel ? regionSel.value : '';
        filterByParent(clusterSel, regionId, clusterSel ? clusterSel.value : null);
        filterByParent(branchSel, clusterSel && clusterSel.value ? clusterSel.value : '', branchSel ? branchSel.value : null);
        if (regionId && clusterSel && !clusterSel.value && branchSel) {
            var allowedParents = {};
            Array.prototype.forEach.call(clusterSel.options, function (opt, i) {
                if (i === 0 || opt.hidden) return;
                allowedParents[opt.value] = true;
            });
            Array.prototype.forEach.call(branchSel.options, function (opt, i) {
                if (i === 0) return;
                var p = opt.getAttribute('data-parent') || '0';
                var show = !!allowedParents[p] || p === '0';
                opt.hidden = !show;
                if (!show && opt.value === branchSel.value) branchSel.value = '';
            });
            syncDdUi(branchSel);
        }
        syncDdUi(regionSel);
        syncDdUi(clusterSel);
        syncDdUi(branchSel);
        syncDdUi(vrmSel);
        syncDdUi(statusSel);
    }

    function setMethod(method) {
        methodInput.value = method;
        methodBtns.forEach(function (b) {
            b.classList.toggle('active', b.getAttribute('data-method') === method);
        });

        document.querySelectorAll('.as-field[data-for]').forEach(function (field) {
            var allowed = (field.getAttribute('data-for') || '').split(',').map(function (s) { return s.trim(); });
            field.hidden = allowed.indexOf(method) === -1;
        });

        if (customBox) customBox.classList.toggle('show', method === 'custom');

        var guideText = document.getElementById('asMethodGuideText');
        if (guideText) guideText.textContent = methodGuides[method] || 'Choose where these contacts should go.';

        var branchReq = document.getElementById('asBranchReq');
        var branchLabelText = document.getElementById('asBranchLabelText');
        if (branchReq && branchLabelText) {
            var branchRequired = method === 'branch';
            branchReq.style.display = branchRequired ? '' : 'none';
            branchLabelText.textContent = branchRequired ? 'Branch' : 'Branch (optional)';
        }

        updateMethodPath(method);
        updatePreview();
    }

    function updateMethodPath(method) {
        var pathEl = document.getElementById('asMethodPath');
        if (!pathEl) return;
        var steps;
        if (method === 'cluster') {
            steps = ['Region', 'Cluster'];
        } else if (method === 'branch') {
            steps = ['Region', 'Cluster', 'Branch'];
        } else if (method === 'vrm') {
            steps = ['Region', 'Cluster', 'Branch', 'VRM'];
        } else if (method === 'custom') {
            steps = ['Region', 'Cluster', 'Branch', 'VRMs'];
        } else {
            pathEl.hidden = true;
            return;
        }
        pathEl.hidden = false;
        pathEl.innerHTML = steps.map(function (s, i) {
            return '<span class="on">' + s + '</span>' + (i < steps.length - 1 ? '<span class="sep">›</span>' : '');
        }).join('');
    }

    function updateSplitMeter() {
        var meter = document.getElementById('asSplitMeter');
        if (!meter) return;
        var total = parseInt(meter.getAttribute('data-total') || '0', 10) || 0;
        var sum = 0;
        document.querySelectorAll('.js-custom-vrm:checked').forEach(function (cb) {
            var cnt = cb.closest('.as-vrm-row').querySelector('.js-cnt');
            sum += cnt ? (parseInt(cnt.value || '0', 10) || 0) : 0;
        });
        var allocatedEl = document.getElementById('asSplitAllocated');
        var hintEl = document.getElementById('asSplitMeterHint');
        if (allocatedEl) allocatedEl.textContent = String(sum);
        meter.classList.remove('ok', 'bad', 'd-none');
        if (allocInput.value !== 'counts') {
            meter.classList.add('d-none');
            return;
        }
        if (sum === total && total > 0) {
            meter.classList.add('ok');
            if (hintEl) hintEl.textContent = 'Total matches — ready to assign';
        } else {
            meter.classList.add('bad');
            if (hintEl) hintEl.textContent = 'Must total exactly ' + total + ' (remaining ' + (total - sum) + ')';
        }
    }

    function setAllocation(alloc) {
        allocInput.value = alloc;
        document.querySelectorAll('.js-pct').forEach(function (el) {
            el.classList.toggle('hidden', alloc !== 'percentage');
        });
        document.querySelectorAll('.js-cnt').forEach(function (el) {
            el.classList.toggle('hidden', alloc !== 'counts');
        });
        var headRight = document.getElementById('asVrmHeadRight');
        if (headRight) {
            headRight.textContent = alloc === 'percentage' ? '%' : (alloc === 'counts' ? 'Qty' : '');
        }
        if ((alloc === 'equal' || alloc === 'percentage' || alloc === 'counts') && methodInput.value !== 'custom') {
            setMethod('custom');
            updateSplitMeter();
            return;
        }
        updateSplitMeter();
        updatePreview();
    }

    function updatePreview() {
        var method = methodInput.value || 'branch';
        var alloc = allocInput.value || 'all';
        var pvMethod = document.getElementById('pvMethod');
        var pvRegion = document.getElementById('pvRegion');
        var pvCluster = document.getElementById('pvCluster');
        var pvBranch = document.getElementById('pvBranch');
        var pvVrm = document.getElementById('pvVrm');
        var pvStatus = document.getElementById('pvStatus');

        if (pvMethod) pvMethod.textContent = methodLabels[method] || method;
        if (pvRegion) pvRegion.textContent = selectedText(regionSel);
        if (pvCluster) pvCluster.textContent = selectedText(clusterSel);
        if (pvBranch) pvBranch.textContent = selectedText(branchSel);
        if (pvStatus) pvStatus.textContent = selectedText(statusSel);

        if (pvVrm) {
            if (method === 'vrm') {
                pvVrm.textContent = selectedText(vrmSel);
            } else if (method === 'custom') {
                var names = [];
                document.querySelectorAll('.js-custom-vrm:checked').forEach(function (cb) {
                    var row = cb.closest('.as-vrm-row');
                    var label = row ? row.querySelector('.nm') : null;
                    names.push(label ? label.textContent.trim() : cb.value);
                });
                pvVrm.textContent = names.length ? names.join(', ') : '—';
            } else {
                pvVrm.textContent = '—';
            }
        }

        document.querySelectorAll('[data-pv]').forEach(function (row) {
            var key = row.getAttribute('data-pv');
            var show = true;
            if (key === 'vrm') show = method === 'vrm' || method === 'custom';
            row.style.display = show ? '' : 'none';
        });
    }

    methodBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            setMethod(btn.getAttribute('data-method'));
        });
    });

    document.querySelectorAll('input[name="allocation_ui"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            if (radio.checked) setAllocation(radio.value);
        });
    });

    [regionSel, clusterSel, branchSel, vrmSel, statusSel].forEach(function (el) {
        if (el) el.addEventListener('change', function () {
            if (el === regionSel || el === clusterSel) syncCascade();
            else syncDdUi(el);
            updatePreview();
        });
    });

    document.querySelectorAll('.js-custom-vrm, .js-pct, .js-cnt').forEach(function (el) {
        el.addEventListener('change', function () {
            updateSplitMeter();
            updatePreview();
        });
        el.addEventListener('input', function () {
            updateSplitMeter();
            updatePreview();
        });
    });

    form.addEventListener('submit', function (e) {
        var submitter = e.submitter;
        var isDraft = submitter && submitter.getAttribute('formaction') && submitter.getAttribute('formaction').indexOf('/draft') !== -1;
        if (isDraft) return;

        var method = methodInput.value;
        if (method === 'cluster') {
            if (clusterSel && !clusterSel.value) {
                e.preventDefault();
                alert('Select a cluster. Contacts will be parked at that cluster.');
                return;
            }
        }
        if (method === 'branch') {
            if (clusterSel && !clusterSel.value) {
                e.preventDefault();
                alert('Select the cluster first, then the branch under it.');
                return;
            }
            if (branchSel && !branchSel.value) {
                e.preventDefault();
                alert('Select a branch under the chosen cluster.');
                return;
            }
        }
        if (method === 'vrm' && vrmSel && !vrmSel.value) {
            e.preventDefault();
            alert('Select the VRM who should receive these contacts.');
            return;
        }
        if (method === 'custom') {
            var checked = document.querySelectorAll('.js-custom-vrm:checked');
            if (!checked.length) {
                e.preventDefault();
                alert('Select at least one VRM for custom rules.');
                return;
            }
            if (allocInput.value === 'percentage') {
                var pctSum = 0;
                checked.forEach(function (cb) {
                    var pct = cb.closest('.as-vrm-row').querySelector('.js-pct');
                    pctSum += pct ? (parseFloat(pct.value) || 0) : 0;
                });
                if (Math.abs(pctSum - 100) > 0.5) {
                    e.preventDefault();
                    alert('Percentages for selected VRMs must total 100%.');
                    return;
                }
            }
            if (allocInput.value === 'counts') {
                var meter = document.getElementById('asSplitMeter');
                var total = meter ? (parseInt(meter.getAttribute('data-total') || '0', 10) || 0) : 0;
                var cntSum = 0;
                checked.forEach(function (cb) {
                    var cnt = cb.closest('.as-vrm-row').querySelector('.js-cnt');
                    cntSum += cnt ? (parseInt(cnt.value || '0', 10) || 0) : 0;
                });
                if (cntSum !== total) {
                    e.preventDefault();
                    alert('Custom split must total exactly ' + total + ' contacts (currently ' + cntSum + ').');
                }
            }
        }
    });

    syncCascade();
    setMethod(methodInput.value || 'branch');
    setAllocation(allocInput.value || 'all');
})();
</script>
@endpush
