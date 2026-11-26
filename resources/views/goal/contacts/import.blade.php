@extends('layouts.app')

@push('styles')
<style>
/* Campaigns — default CRM sizing */
.cg-page{
  --g:#16a34a;--gd:#15803d;--ink:#0f172a;--muted:#64748b;--line:#e8eef2;--soft:#f8faf9;
  background:#f4f7f5;padding:1rem .85rem 1.5rem;width:100%;max-width:100%;box-sizing:border-box;
}
.cg-page *,.cg-page *::before,.cg-page *::after{box-sizing:border-box}
.cg-page .crumbs{display:none}
.cg-page .title-row{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:1rem}
.cg-page .title-row h1{margin:0;font-size:1.35rem;font-weight:800;color:var(--ink);line-height:1.25}
.cg-page .title-row p{margin:4px 0 0;font-size:.85rem;color:var(--muted);line-height:1.4;max-width:40rem}
.cg-card{background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:0 2px 10px rgba(15,23,42,.04)}
.cg-shell{padding:0;margin:0;overflow:hidden}
.cg-steps{
  display:flex;flex-wrap:nowrap;gap:6px 10px;align-items:center;
  padding:10px 14px;margin:0;border-bottom:1px solid var(--line);background:#fbfcfc;
  overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:thin;
}
.cg-step{display:inline-flex;align-items:center;gap:6px;font-size:.8rem;font-weight:700;color:#94a3b8;white-space:nowrap;flex:0 0 auto}
.cg-step .n{width:20px;height:20px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;background:#e2e8f0;color:#64748b;font-size:.7rem;font-weight:800}
.cg-step.on{color:var(--g)}
.cg-step.on .n{background:var(--g);color:#fff}
.cg-steps>.sep{color:#cbd5e1;font-size:.8rem;flex:0 0 auto}

.cg-grid{display:grid;grid-template-columns:1fr;gap:0;margin:0}
.cg-block{padding:14px 16px;border-bottom:1px solid var(--line);min-width:0}
.cg-block h3{margin:0 0 10px;font-size:.92rem;font-weight:800;color:var(--ink);display:flex;align-items:center;gap:8px}
.cg-block h3 .num{width:20px;height:20px;border-radius:999px;background:var(--g);color:#fff;font-size:.7rem;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0}
.cg-drop{border:1.5px dashed #cbd5e1;border-radius:10px;padding:18px 12px;text-align:center;background:#fcfdfd;cursor:pointer;transition:.15s}
.cg-drop:hover,.cg-drop.is-drag{border-color:#86efac;background:#f0fdf4}
.cg-drop.has-file{border-style:solid;border-color:#86efac}
.cg-drop .ico{font-size:1.4rem;color:var(--g);margin-bottom:4px;line-height:1}
.cg-drop p{margin:0;font-size:.84rem;color:#475569;font-weight:600}
.cg-drop .pills{display:flex;gap:6px;justify-content:center;flex-wrap:wrap;margin:8px 0}
.cg-drop .pill{font-size:.7rem;font-weight:700;padding:3px 8px;border-radius:999px;background:#ecfdf3;color:#166534}
.cg-btn-choose{display:inline-flex;align-items:center;gap:6px;min-height:36px;padding:0 14px;border-radius:8px;border:0;background:var(--g);color:#fff;font-weight:800;font-size:.82rem;cursor:pointer}
.cg-btn-choose input{display:none}
.cg-file{margin-top:6px;font-size:.78rem;color:#64748b;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cg-file.has-file{color:#166534}
.cg-field{margin-bottom:10px;position:relative}
.cg-field label{display:block;font-size:.75rem;font-weight:700;color:#475569;margin-bottom:4px}
.cg-field .req{color:#dc2626}
.cg-field input[type=text],.cg-field textarea,.cg-field select{
  width:100%;max-width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px;font-size:.875rem;color:var(--ink);background:#fff;min-height:38px
}
.cg-field select{
  appearance:none;-webkit-appearance:none;-moz-appearance:none;
  padding-right:36px;cursor:pointer;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%2364748b' d='M1.4 0.6L6 5.2 10.6.6 12 2 6 8 0 2z'/%3E%3C/svg%3E");
  background-repeat:no-repeat;background-position:right 12px center;background-color:#fff;
}
.cg-field select:focus{outline:none;border-color:#86efac;box-shadow:0 0 0 3px rgba(22,163,74,.15)}
.cg-field textarea{min-height:64px;height:auto;resize:vertical}
.cg-page .select2-container{width:100%!important}
.cg-page .select2-container--default .select2-selection--single{
  min-height:38px;border:1px solid #e2e8f0;border-radius:8px;padding:4px 8px;background:#fff
}
.cg-page .select2-container--default .select2-selection--single .select2-selection__rendered{
  line-height:28px;color:#0f172a;font-size:.875rem;padding-left:2px
}
.cg-page .select2-container--default .select2-selection--single .select2-selection__arrow{height:36px;right:8px}
.cg-page .select2-container--default.select2-container--focus .select2-selection--single,
.cg-page .select2-container--default.select2-container--open .select2-selection--single{
  border-color:#86efac;box-shadow:0 0 0 3px rgba(22,163,74,.15)
}
.cg-page .select2-dropdown,
.select2-dropdown.cg-select2-drop{
  border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;box-shadow:0 12px 28px rgba(15,23,42,.12);z-index:1200
}
.cg-page .select2-results__option,
.select2-dropdown.cg-select2-drop .select2-results__option{font-size:.875rem;padding:8px 12px}
.cg-page .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable,
.select2-dropdown.cg-select2-drop .select2-results__option--highlighted.select2-results__option--selectable{
  background:#16a34a;color:#fff
}
.cg-page .select2-container--default .select2-results__option--selected,
.select2-dropdown.cg-select2-drop .select2-results__option--selected{background:#f0fdf4;color:#166534}
.cg-assign-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px}
.cg-assign-tabs label{display:inline-flex;align-items:center;gap:5px;padding:7px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:.8rem;font-weight:700;color:#64748b;cursor:pointer;background:#fff}
.cg-assign-tabs label.on{border-color:#86efac;background:#f0fdf4;color:#166534}
.cg-assign-tabs input{accent-color:var(--g)}
.cg-row2{display:grid;grid-template-columns:1fr;gap:8px}
.cg-alloc{display:flex;flex-wrap:wrap;gap:8px 12px;margin:8px 0 4px}
.cg-alloc label{font-size:.78rem;font-weight:700;color:#475569;display:inline-flex;align-items:center;gap:5px}
.cg-split{display:none;margin-top:8px;border:1px solid #eef3f0;border-radius:10px;overflow:hidden}
.cg-split.show{display:block}
.cg-split-head{display:flex;justify-content:space-between;gap:8px;align-items:center;padding:8px 12px;background:var(--soft);font-size:.78rem;font-weight:800;color:#334155}
.cg-split-head .js-split-total{font-weight:700;color:#64748b;font-size:.72rem}
.cg-split-list{max-height:220px;overflow:auto;-webkit-overflow-scrolling:touch}
.cg-split-table{width:100%;border-collapse:collapse;font-size:.8rem}
.cg-split-table th{text-align:left;font-size:.68rem;text-transform:uppercase;letter-spacing:.03em;color:#64748b;font-weight:800;padding:8px 10px;background:#f8fafc;border-bottom:1px solid #e8eef2;white-space:nowrap}
.cg-split-table td{padding:8px 10px;border-bottom:1px solid #f1f5f9;vertical-align:middle;color:#334155}
.cg-split-table .col-check{width:36px;text-align:center}
.cg-split-table .col-pct{width:88px}
.cg-split-table .col-count{width:110px;font-weight:800}
.cg-split-table .nm{font-weight:600;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cg-split-table input[type=number]{width:72px;border:1px solid #e2e8f0;border-radius:6px;padding:4px 6px;min-height:30px;font-size:.78rem}
.cg-split-table .js-pct.hidden,.cg-split-table .js-cnt.hidden{display:none!important}
.cg-split:not(.mode-pct) .col-pct{display:none}
.cg-split.mode-equal .js-cnt{display:none!important}
.cg-split.mode-pct .js-cnt{display:none!important}
.cg-split.mode-counts .js-split-count{display:none!important}
.cg-split-table .js-split-count{display:inline-block;min-width:2rem;font-variant-numeric:tabular-nums;color:#0f172a}
.cg-meter{display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;padding:8px 12px;font-size:.78rem;font-weight:700;background:#fffbeb;color:#92400e}
.cg-meter.ok{background:#ecfdf3;color:#166534}
.cg-meter.bad{background:#fef2f2;color:#b91c1c}
.cg-summary{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.cg-sum-card{
  display:flex;align-items:center;gap:10px;padding:12px;
  border-radius:12px;border:1px solid #e8eef2;background:#fff;
  border-left:3px solid var(--sum-accent,#94a3b8);
}
.cg-sum-card .sum-ico{
  width:38px;height:38px;border-radius:10px;flex-shrink:0;
  display:inline-flex;align-items:center;justify-content:center;
  background:var(--sum-soft,#f1f5f9);color:var(--sum-ink,#475569);font-size:1rem;
}
.cg-sum-card .sum-meta{min-width:0;flex:1}
.cg-sum-card .l{margin:0;font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.03em}
.cg-sum-card .v{margin:2px 0 0;font-size:1.2rem;font-weight:800;color:var(--sum-ink,#0f172a);line-height:1.1}
.cg-sum-card.tone-total{--sum-accent:#475569;--sum-ink:#334155;--sum-soft:#e2e8f0}
.cg-sum-card.tone-ok{--sum-accent:#16a34a;--sum-ink:#15803d;--sum-soft:#dcfce7}
.cg-sum-card.tone-warn{--sum-accent:#ea580c;--sum-ink:#c2410c;--sum-soft:#ffedd5}
.cg-sum-card.tone-bad{--sum-accent:#dc2626;--sum-ink:#b91c1c;--sum-soft:#fee2e2}
.cg-actions{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:12px 16px;border-top:1px solid var(--line);background:#fbfcfc;margin:0;flex-wrap:wrap}
.cg-actions .cg-actions-right{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-left:auto}
.cg-actions .cg-btn{flex:0 0 auto}
.cg-btn{min-height:36px;padding:0 14px;border-radius:8px;font-weight:800;font-size:.84rem;border:1px solid #e2e8f0;background:#fff;color:#475569;cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none;white-space:nowrap}
.cg-btn-primary{background:var(--g);border-color:var(--g);color:#fff}
.cg-btn-primary:hover{background:var(--gd);color:#fff}
.cg-btn-primary:disabled{opacity:.55;cursor:not-allowed;background:#94a3b8;border-color:#94a3b8}
.cg-list-head{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;padding:16px 18px 10px}
.cg-list-head h2{margin:0;font-size:1.1rem;font-weight:800}
.cg-list-head p{margin:2px 0 0;font-size:.8rem;color:var(--muted)}
.cg-kpi-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:0 0 14px}
.cg-kpi-card{
  display:flex;align-items:center;gap:12px;
  padding:14px 16px;border-radius:12px;border:1px solid #e8eef2;background:#fff;
  box-shadow:0 2px 10px rgba(15,23,42,.04);border-left:3px solid var(--kpi-accent,#94a3b8);
  transition:box-shadow .15s,border-color .15s;min-width:0;
}
.cg-kpi-card:hover{box-shadow:0 6px 18px var(--kpi-ring,rgba(15,23,42,.08));border-color:var(--kpi-soft,#e2e8f0)}
.cg-kpi-card .kpi-ico{
  width:42px;height:42px;border-radius:12px;flex-shrink:0;
  display:inline-flex;align-items:center;justify-content:center;
  background:var(--kpi-soft,#f1f5f9);color:var(--kpi-ink,#475569);font-size:1.1rem;
}
.cg-kpi-card .kpi-body{min-width:0;flex:1}
.cg-kpi-card .kpi-label{margin:0;font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.03em}
.cg-kpi-card .kpi-hint{margin:2px 0 0;font-size:.7rem;color:#94a3b8;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cg-kpi-card .kpi-value{margin:0 0 0 auto;font-size:1.45rem;font-weight:800;color:#0f172a;line-height:1;letter-spacing:-.02em;flex-shrink:0}
.cg-kpi-card.tone-total{--kpi-accent:#475569;--kpi-ink:#334155;--kpi-soft:#e2e8f0;--kpi-ring:rgba(71,85,105,.12)}
.cg-kpi-card.tone-total .kpi-value{color:#334155}
.cg-kpi-card.tone-active{--kpi-accent:#16a34a;--kpi-ink:#15803d;--kpi-soft:#dcfce7;--kpi-ring:rgba(22,163,74,.14)}
.cg-kpi-card.tone-active .kpi-value{color:#15803d}
.cg-kpi-card.tone-done{--kpi-accent:#2563eb;--kpi-ink:#1d4ed8;--kpi-soft:#dbeafe;--kpi-ring:rgba(37,99,235,.14)}
.cg-kpi-card.tone-done .kpi-value{color:#1d4ed8}
.cg-kpi-card.tone-draft{--kpi-accent:#ea580c;--kpi-ink:#c2410c;--kpi-soft:#ffedd5;--kpi-ring:rgba(234,88,12,.14)}
.cg-kpi-card.tone-draft .kpi-value{color:#c2410c}
.cg-filters{display:grid;grid-template-columns:1fr 1fr;gap:8px;padding:0 18px 14px;align-items:center}
.cg-filters input,.cg-filters select{width:100%;min-height:38px;border:1px solid #e2e8f0;border-radius:8px;padding:0 10px;font-size:.875rem}
.cg-filters input[type=search]{grid-column:1 / -1}
.cg-filters .cg-btn{width:100%;justify-content:center}
.cg-table-wrap{overflow:auto;padding:0 8px 12px;-webkit-overflow-scrolling:touch}
.cg-table{width:100%;min-width:900px;border-collapse:collapse;font-size:.84rem}
.cg-table th{text-align:left;font-size:.7rem;text-transform:uppercase;letter-spacing:.04em;color:#64748b;padding:10px 12px;border-bottom:1px solid #e8eef2;background:#fbfcfc;font-weight:800;white-space:nowrap}
.cg-table td{padding:12px;border-bottom:1px solid #f1f5f9;color:#334155;vertical-align:middle}
.cg-table .name{font-weight:800;color:var(--ink);display:flex;align-items:center;gap:8px;font-size:.9rem}
.cg-table .name i{color:var(--g);font-size:.95rem}
.cg-recs{display:grid;grid-template-columns:repeat(2,auto);gap:2px 10px;font-size:.75rem}
.cg-recs b{font-weight:800;color:var(--ink)}
.cg-badge{display:inline-flex;align-items:center;gap:4px;padding:.25rem .55rem;border-radius:999px;font-size:.72rem;font-weight:800}
.cg-badge.active{background:#dcfce7;color:#166534}
.cg-badge.completed{background:#dbeafe;color:#1d4ed8}
.cg-badge.draft{background:#ffedd5;color:#c2410c}
.cg-badge.failed{background:#fee2e2;color:#b91c1c}
.cg-badge.await{background:#fef3c7;color:#b45309}
.cg-prog{min-width:110px}
.cg-prog .bar{height:6px;border-radius:999px;background:#e2e8f0;overflow:hidden;margin-bottom:4px}
.cg-prog .bar>span{display:block;height:100%;background:var(--g)}
.cg-prog.is-completed .bar>span{background:#2563eb}
.cg-prog.is-await .bar>span{background:#f59e0b}
.cg-prog.is-draft .bar>span{background:#ea580c}
.cg-prog.is-failed .bar>span{background:#dc2626}
.cg-prog .txt{font-size:.72rem;color:#64748b;font-weight:600}
.cg-ico{width:34px;height:34px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;display:inline-flex;align-items:center;justify-content:center;color:#475569;text-decoration:none;font-size:.85rem;vertical-align:middle;cursor:pointer;padding:0;line-height:1;font:inherit}
.cg-ico:hover{filter:brightness(.96)}
.cg-ico.tone-view{background:#eff6ff;border-color:#93c5fd;color:#1d4ed8}
.cg-ico.tone-view:hover{background:#dbeafe;color:#1e40af}
.cg-ico.tone-active{background:#ecfdf3;border-color:#86efac;color:#15803d}
.cg-ico.tone-active:hover{background:#dcfce7;color:#166534}
.cg-ico.tone-await{background:#fffbeb;border-color:#fcd34d;color:#b45309}
.cg-ico.tone-await:hover{background:#fef3c7;color:#92400e}
.cg-ico.tone-draft{background:#fff7ed;border-color:#fdba74;color:#c2410c}
.cg-ico.tone-draft:hover{background:#ffedd5;color:#9a3412}
.cg-ico.tone-failed,.cg-ico.tone-errors{background:#fef2f2;border-color:#fca5a5;color:#b91c1c}
.cg-ico.tone-failed:hover,.cg-ico.tone-errors:hover{background:#fee2e2;color:#991b1b}
.cg-btn-assign{background:#16a34a;border-color:#16a34a;color:#fff;height:32px;padding:0 10px;font-size:.75rem}
.cg-btn-assign:hover{background:#15803d;border-color:#15803d;color:#fff}
.cg-btn-assign.tone-await{background:#d97706;border-color:#d97706}
.cg-btn-assign.tone-await:hover{background:#b45309;border-color:#b45309}
.cg-view-modal{position:fixed;inset:0;z-index:1080;display:none;align-items:center;justify-content:center;padding:16px}
.cg-view-modal.open{display:flex}
.cg-view-modal .cg-vm-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.45)}
.cg-view-modal .cg-vm-dialog{position:relative;z-index:1;width:min(560px,100%);max-height:min(86vh,720px);overflow:auto;background:#fff;border-radius:14px;border:1px solid #e8eef2;box-shadow:0 24px 60px rgba(15,23,42,.22)}
.cg-view-modal .cg-vm-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:16px 18px;border-bottom:1px solid #eef3f0}
.cg-view-modal .cg-vm-head h3{margin:0;font-size:1.05rem;font-weight:800;color:#0f172a;line-height:1.3}
.cg-view-modal .cg-vm-head p{margin:4px 0 0;font-size:.8rem;color:#64748b}
.cg-view-modal .cg-vm-close{width:34px;height:34px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#475569;display:inline-flex;align-items:center;justify-content:center;cursor:pointer}
.cg-view-modal .cg-vm-close:hover{background:#f8fafc}
.cg-view-modal .cg-vm-body{padding:16px 18px}
.cg-view-modal .cg-vm-stats{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin-bottom:14px}
@media(min-width:480px){.cg-view-modal .cg-vm-stats{grid-template-columns:repeat(4,minmax(0,1fr))}}
.cg-view-modal .cg-vm-stat{padding:10px;border-radius:10px;border:1px solid #e8eef2;border-left:3px solid #94a3b8;background:#fff}
.cg-view-modal .cg-vm-stat .l{margin:0;font-size:.65rem;font-weight:700;color:#64748b;text-transform:uppercase}
.cg-view-modal .cg-vm-stat .v{margin:2px 0 0;font-size:1.15rem;font-weight:800;color:#0f172a}
.cg-view-modal .cg-vm-stat.ok{border-left-color:#16a34a}.cg-view-modal .cg-vm-stat.ok .v{color:#15803d}
.cg-view-modal .cg-vm-stat.warn{border-left-color:#ea580c}.cg-view-modal .cg-vm-stat.warn .v{color:#c2410c}
.cg-view-modal .cg-vm-stat.bad{border-left-color:#dc2626}.cg-view-modal .cg-vm-stat.bad .v{color:#b91c1c}
.cg-view-modal .cg-vm-grid{display:grid;grid-template-columns:1fr;gap:10px;font-size:.84rem;color:#334155}
@media(min-width:480px){.cg-view-modal .cg-vm-grid{grid-template-columns:1fr 1fr}}
.cg-view-modal .cg-vm-grid span{display:block;color:#64748b;font-weight:700;font-size:.72rem;margin-bottom:2px}
.cg-view-modal .cg-vm-desc{margin:12px 0 0;font-size:.84rem;color:#475569;line-height:1.45}
.cg-view-modal .cg-vm-foot{display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;padding:12px 18px 16px;border-top:1px solid #eef3f0}
.cg-assign-layout{display:grid;grid-template-columns:1fr;gap:14px}
@media(min-width:1100px){.cg-assign-layout:not(.is-selected-only){grid-template-columns:minmax(280px,.95fr) minmax(360px,1.2fr);align-items:start}}
.cg-assign-layout.is-selected-only .cg-loaded{max-width:100%}
.cg-assign-layout.is-selected-only.has-onward{grid-template-columns:1fr}
@media(min-width:1100px){.cg-assign-layout.is-selected-only.has-onward{grid-template-columns:minmax(280px,.95fr) minmax(360px,1.2fr);align-items:start}}
.cg-loaded{border:1px solid #e8eef2;border-radius:12px;overflow:hidden;background:#fff}
.cg-loaded-head{display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;padding:12px 14px;background:#f8faf9;border-bottom:1px solid #eef3f0}
.cg-loaded-head h4{margin:0;font-size:.9rem;font-weight:800;color:#0f172a}
.cg-loaded-head p{margin:2px 0 0;font-size:.72rem;color:#64748b}
.cg-loaded-pills{display:flex;flex-wrap:wrap;gap:6px}
.cg-loaded-pill{display:inline-flex;align-items:center;gap:4px;padding:4px 8px;border-radius:999px;font-size:.7rem;font-weight:800;background:#e2e8f0;color:#334155}
.cg-loaded-pill.open{background:#ffedd5;color:#c2410c}
.cg-loaded-pill.done{background:#dcfce7;color:#166534}
.cg-loaded-table-wrap{max-height:360px;overflow:auto}
.cg-loaded-table{width:100%;border-collapse:collapse;font-size:.78rem}
.cg-loaded-table th{position:sticky;top:0;background:#fbfcfc;text-align:left;font-size:.66rem;text-transform:uppercase;letter-spacing:.03em;color:#64748b;font-weight:800;padding:8px 10px;border-bottom:1px solid #e8eef2;white-space:nowrap}
.cg-loaded-table td{padding:8px 10px;border-bottom:1px solid #f1f5f9;color:#334155;vertical-align:middle}
.cg-loaded-table .nm{font-weight:700;color:#0f172a}
.cg-loaded-table .muted{color:#94a3b8}
.cg-st{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:999px;font-size:.68rem;font-weight:800}
.cg-st.open{background:#fff7ed;color:#c2410c}
.cg-st.done{background:#ecfdf3;color:#166534}
.cg-handoff{margin:0 0 12px;padding:12px 14px;border-radius:10px;background:#fffbeb;border:1px solid #fde68a;color:#92400e;display:flex;gap:10px;align-items:flex-start}
.cg-handoff h4{margin:0 0 4px;font-size:.9rem;font-weight:800}
.cg-handoff p{margin:0;font-size:.8rem}
.cg-tpl{font-size:.75rem;margin-top:6px}
.cg-tpl a{color:var(--gd);font-weight:700;text-decoration:none}
.cg-page .alert{padding:.6rem .85rem;font-size:.875rem;margin-bottom:12px;border-radius:8px}
.cg-sum-note{margin:8px 0 0;font-size:.72rem;color:#94a3b8;line-height:1.35}

.cg-title-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center;flex-shrink:0}
.cg-btn-add{background:var(--g)!important;border-color:var(--g)!important;color:#fff!important}
.cg-btn-add:hover{background:var(--gd)!important;color:#fff!important}
.cg-btn-tpl{background:#0f766e;border-color:#0f766e;color:#fff}
.cg-btn-tpl:hover{background:#0d9488;border-color:#0d9488;color:#fff}
.cg-btn-csv{background:#eff6ff;border-color:#93c5fd!important;color:#1d4ed8}
.cg-btn-csv:hover{background:#dbeafe;color:#1e40af}
.cg-btn-xlsx{background:#ecfdf3;border-color:#86efac!important;color:#15803d}
.cg-btn-xlsx:hover{background:#dcfce7;color:#166534}
.cg-dd{position:relative}
.cg-dd > summary{list-style:none;cursor:pointer}
.cg-dd > summary::-webkit-details-marker{display:none}
.cg-dd > summary::after{content:"";width:.45rem;height:.45rem;margin-left:2px;border-right:2px solid currentColor;border-bottom:2px solid currentColor;transform:rotate(45deg) translateY(-2px);opacity:.7}
.cg-dd[open] > summary::after{transform:rotate(-135deg) translateY(-1px)}
.cg-dd-menu{position:absolute;right:0;top:calc(100% + 6px);z-index:30;min-width:180px;padding:6px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;box-shadow:0 10px 28px rgba(15,23,42,.12)}
.cg-dd-menu a{display:flex;align-items:center;gap:8px;padding:9px 11px;border-radius:8px;font-weight:800;font-size:.82rem;text-decoration:none;border:1px solid transparent;margin-bottom:4px}
.cg-dd-menu a:last-child{margin-bottom:0}
.cg-dd-menu a i{font-size:1rem}

@media (min-width:576px){
  .cg-row2{grid-template-columns:1fr 1fr}
  .cg-filters{grid-template-columns:1fr 1fr auto auto}
  .cg-filters input[type=search]{grid-column:auto}
  .cg-recs{grid-template-columns:repeat(4,auto)}
}

@media (min-width:768px){
  .cg-page{padding:1rem 1rem 1.75rem}
  .cg-kpi-row{grid-template-columns:repeat(4,1fr);gap:14px}
  .cg-filters{display:flex;flex-wrap:wrap}
  .cg-filters input,.cg-filters select{width:auto}
  .cg-filters input[type=search]{min-width:220px;flex:1}
  .cg-filters .cg-btn{width:auto}
}

@media (min-width:1100px){
  .cg-page{padding:1rem 1.25rem 2rem}
  .cg-table{min-width:0}
}

@media (max-width:575px){
  .cg-page .title-row h1{font-size:1.15rem}
  .cg-actions{justify-content:stretch}
  .cg-actions .cg-actions-right{width:100%;justify-content:stretch}
  .cg-actions .cg-btn{flex:1 1 auto;justify-content:center}
  .cg-filters input,.cg-filters select{font-size:16px}
}
</style>
@endpush

@section('content')
@php
    $handoff = $handoff ?? ['key' => 'hq', 'label' => 'HQ', 'unit' => 'organisation', 'next' => ''];
    $handoffKey = (string) ($handoff['key'] ?? 'hq');
    $isBmAssigner = (bool) ($isBmAssigner ?? false);
    $canPickBranch = (bool) ($canPickBranch ?? false);
    $canSplit = (bool) ($canSplit ?? false);
    $activeTab = $tab ?? 'import';
    $stats = $batchStats ?? ['total' => 0, 'active' => 0, 'completed' => 0, 'draft' => 0];
    $canAssignRegion = (bool) ($canAssignRegion ?? false);
    $canAssignCluster = $canPickBranch && ! in_array($handoffKey, ['cluster', 'bm'], true);
    $canAssignBranch = $canPickBranch && $handoffKey !== 'bm';
    $defaultMethod = $canAssignRegion ? 'region' : ($canAssignCluster ? 'cluster' : ($canAssignBranch ? 'branch' : 'vrm'));
@endphp
<div class="cg-page">
    <ul class="crumbs">
        <li><a href="{{ url('/') }}">Home</a></li>
        <li>›</li>
        <li><a href="{{ route('goal.leads.index') }}">Lead Management</a></li>
        <li>›</li>
        <li class="cur">Campaigns</li>
    </ul>

    <div class="title-row">
        <div style="min-width:0;flex:1">
            <h1>Campaigns</h1>
            <p>
                @if($handoffKey === 'bm')
                    Contacts sent to your branch — assign them to your VRM team.
                @elseif($handoffKey === 'cluster')
                    Contacts sent to your cluster — send to Branch (BM) or VRMs.
                @else
                    Import contacts, assign to Cluster / Branch (BM) / VRM, and track campaigns.
                @endif
            </p>
        </div>
        <div class="cg-title-actions">
            <details class="cg-dd">
                <summary class="cg-btn cg-btn-tpl">
                    <i class="bi bi-download"></i> <span class="d-none d-sm-inline">Template</span>
                </summary>
                <div class="cg-dd-menu" role="menu">
                    <a class="cg-btn-csv" href="{{ route('goal.contacts.import.template', ['format' => 'csv']) }}" role="menuitem">
                        <i class="bi bi-filetype-csv"></i> Download CSV
                    </a>
                    <a class="cg-btn-xlsx" href="{{ route('goal.contacts.import.template', ['format' => 'xlsx']) }}" role="menuitem">
                        <i class="bi bi-file-earmark-excel"></i> Download Excel
                    </a>
                </div>
            </details>
            @if($activeTab !== 'assign')
            <a class="cg-btn cg-btn-add" href="{{ route('goal.contacts.import.create') }}">
                <i class="bi bi-plus-lg"></i> Add Campaign
            </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @php
        $focusBatch = !empty($selectedBatch) && $activeTab === 'assign';
        $summary = $batchSummary ?? ['total' => 0, 'valid' => 0, 'duplicates' => 0, 'invalid' => 0, 'unassigned' => 0];
        $validCount = (int) ($summary['unassigned'] ?? ($unassigned->count() ?? 0));
        $batchLeads = $batchLeads ?? collect();
        $loadedTotal = $batchLeads->count();
        $loadedAssigned = $batchLeads->whereNotNull('assigned_to')->count();
        $loadedOpen = $batchLeads->whereNull('assigned_to')->count();
        // Already distributed once (e.g. MD split to regions) — show selected data, not Region split table again
        $alreadyDistributed = $focusBatch && (
            filled($selectedBatch->assigned_at ?? null) || filled($selectedBatch->assigned_label ?? null)
        );
        $orgBuckets = $batchLeads
            ->groupBy(fn ($l) => optional($l->orgUnit)->name ?: 'Unassigned org')
            ->map->count();
        $onwardDefault = $isBmAssigner ? 'vrm' : (
            $canAssignCluster ? 'cluster' : ($canAssignBranch ? 'branch' : 'vrm')
        );
        // After region park, HQ/RD continue from cluster (not region again)
        if ($alreadyDistributed && $canAssignRegion) {
            $onwardDefault = $canAssignCluster ? 'cluster' : ($canAssignBranch ? 'branch' : 'vrm');
        }
        // Pre-selected org(s) from parked contacts — restore method + checked targets on Finish assign
        $preselectedRegionId = 0;
        $preselectedClusterId = 0;
        $preselectedBranchId = 0;
        $preselectedTargetIds = [];
        $preselectedMethod = $canAssignRegion ? 'region' : $onwardDefault;
        $preselectedAlloc = 'all';
        if ($alreadyDistributed && $batchLeads->isNotEmpty()) {
            $orgUnits = $batchLeads->pluck('orgUnit')->filter()->unique('id')->values();
            $orgIds = $orgUnits->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
            $types = $orgUnits->pluck('type')->unique()->values();
            $sameType = $types->count() === 1 ? (string) $types->first() : '';

            if ($sameType === \App\Models\GoalOrgUnit::TYPE_REGION) {
                $preselectedMethod = 'region';
                $preselectedTargetIds = $orgIds;
                $preselectedRegionId = count($orgIds) === 1 ? $orgIds[0] : 0;
                $preselectedAlloc = count($orgIds) > 1 ? 'equal' : 'all';
            } elseif ($sameType === \App\Models\GoalOrgUnit::TYPE_CLUSTER) {
                $preselectedMethod = 'cluster';
                $preselectedTargetIds = $orgIds;
                $preselectedClusterId = count($orgIds) === 1 ? $orgIds[0] : 0;
                $preselectedRegionId = (int) ($orgUnits->first()->parent_id ?? 0);
                $preselectedAlloc = count($orgIds) > 1 ? 'equal' : 'all';
            } elseif ($sameType === \App\Models\GoalOrgUnit::TYPE_BRANCH) {
                $preselectedMethod = 'branch';
                $preselectedTargetIds = $orgIds;
                $preselectedBranchId = count($orgIds) === 1 ? $orgIds[0] : 0;
                $preselectedClusterId = (int) ($orgUnits->first()->parent_id ?? 0);
                $preselectedAlloc = count($orgIds) > 1 ? 'equal' : 'all';
            } else {
                $preselectedMethod = $canAssignRegion ? 'region' : $onwardDefault;
                $preselectedAlloc = 'equal';
            }
        }
    @endphp

    {{-- Assign onward (focused page) --}}
    @if($focusBatch && $canSplit)
        @if($validCount > 0 && in_array($handoffKey, ['bm', 'cluster', 'rd'], true))
            <div class="cg-handoff">
                <i class="bi bi-person-workspace" style="font-size:1.2rem"></i>
                <div>
                    <h4>{{ number_format($validCount) }} contact(s) assigned to you ({{ $handoff['label'] }})</h4>
                    <p>{{ $handoff['next'] }} Campaign: <strong>{{ $selectedBatch->displayName() }}</strong></p>
                </div>
            </div>
        @endif
        <div class="cg-card" style="margin-bottom:14px;padding:16px 18px">
            <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:14px">
                <div>
                    <h3 style="margin:0;font-size:1rem;font-weight:800">
                        {{ $alreadyDistributed ? 'Selected data' : 'Assign' }}: {{ $selectedBatch->displayName() }}
                    </h3>
                    <p style="margin:2px 0 0;font-size:.78rem;color:#64748b">
                        {{ $selectedBatch->filename }}
                        @if($alreadyDistributed && $selectedBatch->assigned_label)
                            · {{ $selectedBatch->assigned_label }}
                        @else
                            · {{ number_format($loadedOpen) }} open of {{ number_format($loadedTotal) }} loaded
                        @endif
                    </p>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <a href="{{ route('goal.contacts.import') }}" class="cg-btn"><i class="bi bi-arrow-left"></i> Back to Campaigns</a>
                </div>
            </div>

            @if($alreadyDistributed)
                {{-- Selected data header + assign fields (Branch / VRM) always visible --}}
                <div id="cgOnwardPanel" style="margin-top:4px">
                    @if($loadedOpen > 0)
                        @include('goal.contacts.partials.assign-form', [
                            'formId' => 'assignForm',
                            'formAction' => route('goal.contacts.import.split.store', $selectedBatch),
                            'submitLabel' => $isBmAssigner ? 'Assign to VRM team' : 'Assign Contacts',
                            'showAllMethods' => $canPickBranch,
                            // MD/HQ edit-style: show all tabs like Create Campaign
                            'canAssignRegion' => $canAssignRegion,
                            'canAssignCluster' => $canAssignCluster,
                            'canAssignBranch' => $canAssignBranch,
                            'handoffKey' => $handoffKey,
                            'defaultMethod' => $preselectedMethod,
                            'defaultAlloc' => $preselectedAlloc,
                            'validCount' => $validCount,
                            'embedInNew' => false,
                            'lockSelectedOrg' => false,
                            'hideAllocation' => false,
                            'preselectedRegionId' => $preselectedRegionId,
                            'preselectedClusterId' => $preselectedClusterId,
                            'preselectedBranchId' => $preselectedBranchId,
                            'preselectedTargetIds' => $preselectedTargetIds,
                        ])
                    @else
                        <div style="padding:18px;border:1px dashed #cbd5e1;border-radius:12px;background:#f8fafc;text-align:center">
                            <i class="bi bi-check2-circle" style="font-size:1.6rem;color:#16a34a"></i>
                            <p style="margin:8px 0 4px;font-weight:800;color:#0f172a">All loaded contacts are already assigned</p>
                            <p style="margin:0;font-size:.8rem;color:#64748b">Nothing left to split for this campaign.</p>
                        </div>
                    @endif
                </div>
            @else
            <div class="cg-assign-layout">
                <div class="cg-loaded">
                    <div class="cg-loaded-head">
                        <div>
                            <h4>Loaded contacts</h4>
                            <p>Check who is already assigned before splitting</p>
                        </div>
                        <div class="cg-loaded-pills">
                            <span class="cg-loaded-pill">Total {{ number_format($loadedTotal) }}</span>
                            <span class="cg-loaded-pill open">Open {{ number_format($loadedOpen) }}</span>
                            <span class="cg-loaded-pill done">Assigned {{ number_format($loadedAssigned) }}</span>
                        </div>
                    </div>
                    <div class="cg-loaded-table-wrap">
                        <table class="cg-loaded-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Mobile</th>
                                    <th>Org</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($batchLeads as $li => $lead)
                                    @php
                                        $isAssigned = filled($lead->assigned_to);
                                        $orgName = optional($lead->orgUnit)->name ?: '—';
                                        $assigneeName = optional($lead->assignee)->name;
                                    @endphp
                                    <tr>
                                        <td class="muted">{{ $li + 1 }}</td>
                                        <td class="nm">{{ $lead->customer_name ?: '—' }}</td>
                                        <td>{{ $lead->mobile ?: '—' }}</td>
                                        <td>{{ $orgName }}</td>
                                        <td>
                                            @if($isAssigned)
                                                <span class="cg-st done" title="{{ $assigneeName }}">
                                                    <i class="bi bi-check2-circle"></i> Assigned{{ $assigneeName ? ': '.$assigneeName : '' }}
                                                </span>
                                            @else
                                                <span class="cg-st open"><i class="bi bi-hourglass-split"></i> Open</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No contacts loaded for this campaign.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="cgOnwardPanel">
                    @if($loadedOpen > 0)
                        @include('goal.contacts.partials.assign-form', [
                            'formId' => 'assignForm',
                            'formAction' => route('goal.contacts.import.split.store', $selectedBatch),
                            'submitLabel' => $isBmAssigner ? 'Assign to VRM team' : 'Assign Contacts',
                            'showAllMethods' => $canPickBranch,
                            'canAssignRegion' => $canAssignRegion,
                            'canAssignCluster' => $canAssignCluster,
                            'canAssignBranch' => $canAssignBranch,
                            'handoffKey' => $handoffKey,
                            'defaultMethod' => $onwardDefault,
                            'validCount' => $validCount,
                            'embedInNew' => false,
                        ])
                    @else
                        <div style="padding:18px;border:1px dashed #cbd5e1;border-radius:12px;background:#f8fafc;text-align:center">
                            <i class="bi bi-check2-circle" style="font-size:1.6rem;color:#16a34a"></i>
                            <p style="margin:8px 0 4px;font-weight:800;color:#0f172a">All loaded contacts are already assigned</p>
                            <p style="margin:0;font-size:.8rem;color:#64748b">Nothing left to split for this campaign.</p>
                        </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    @endif

@if(! $focusBatch)
    {{-- KPI cards --}}
    <div class="cg-kpi-row">
        <div class="cg-kpi-card tone-total">
            <span class="kpi-ico"><i class="bi bi-megaphone-fill"></i></span>
            <div class="kpi-body">
                <p class="kpi-label">Total Campaigns</p>
                <p class="kpi-hint">All imported batches</p>
            </div>
            <p class="kpi-value">{{ number_format((int)($stats['total'] ?? 0)) }}</p>
        </div>
        <div class="cg-kpi-card tone-active">
            <span class="kpi-ico"><i class="bi bi-activity"></i></span>
            <div class="kpi-body">
                <p class="kpi-label">Active</p>
                <p class="kpi-hint">Open / awaiting assign</p>
            </div>
            <p class="kpi-value">{{ number_format((int)($stats['active'] ?? 0)) }}</p>
        </div>
        <div class="cg-kpi-card tone-done">
            <span class="kpi-ico"><i class="bi bi-check2-circle"></i></span>
            <div class="kpi-body">
                <p class="kpi-label">Completed</p>
                <p class="kpi-hint">Fully assigned to VRMs</p>
            </div>
            <p class="kpi-value">{{ number_format((int)($stats['completed'] ?? 0)) }}</p>
        </div>
        <div class="cg-kpi-card tone-draft">
            <span class="kpi-ico"><i class="bi bi-pencil-square"></i></span>
            <div class="kpi-body">
                <p class="kpi-label">Draft</p>
                <p class="kpi-hint">Processing or incomplete</p>
            </div>
            <p class="kpi-value">{{ number_format((int)($stats['draft'] ?? 0)) }}</p>
        </div>
    </div>

    {{-- Campaigns list --}}
    <div class="cg-card">
        <div class="cg-list-head">
            <div>
                <h2>Campaigns (Assigned)</h2>
                <p>Track imported campaigns and handoff progress</p>
            </div>
        </div>

        <form method="GET" action="{{ route('goal.contacts.import') }}" class="cg-filters">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search campaign by name…">
            <select name="status">
                <option value="">Status</option>
                <option value="Active" @selected(request('status') === 'Active')>Active</option>
                <option value="Completed" @selected(request('status') === 'Completed')>Completed</option>
                <option value="Draft" @selected(request('status') === 'Draft')>Draft</option>
            </select>
            @if($canPickBranch)
            <select name="region_id" id="fltRegion">
                <option value="">Region</option>
                @foreach(($orgOptions['regions'] ?? collect()) as $r)
                    <option value="{{ $r->id }}" @selected((int)request('region_id') === (int)$r->id)>{{ $r->name }}</option>
                @endforeach
            </select>
            <select name="cluster_id" id="fltCluster">
                <option value="">Cluster</option>
                @foreach(($orgOptions['clusters'] ?? $clusters) as $c)
                    <option value="{{ $c->id }}" data-parent="{{ (int)($c->parent_id ?? 0) }}" @selected((int)request('cluster_id') === (int)$c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
            @endif
            <select name="branch_id" id="fltBranch">
                <option value="">Branch</option>
                @foreach(($orgOptions['branches'] ?? $branches) as $b)
                    <option value="{{ $b->id }}" data-parent="{{ (int)($b->parent_id ?? 0) }}" @selected((int)request('branch_id') === (int)$b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
            <input type="date" name="import_date" value="{{ request('import_date') }}">
            <button type="submit" class="cg-btn"><i class="bi bi-funnel"></i> Filter</button>
            <a href="{{ route('goal.contacts.import') }}" class="cg-btn">Clear</a>
        </form>

        <div class="cg-table-wrap">
            <table class="cg-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Campaign Name</th>
                        <th>File Name</th>
                        <th>Imported On</th>
                        <th>Records</th>
                        <th>Assigned To</th>
                        <th>Assigned On</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $i => $batch)
                        @php
                            $unassignedN = (int) ($batch->unassigned_count ?? 0);
                            $dupN = (int) ($batch->duplicate_count ?? 0);
                            $invN = (int) ($batch->invalid_count ?? 0);
                            $assignedPeople = (int) ($batch->assigned_people_count ?? 0);
                            $statusLabel = $batch->campaignStatusLabel($unassignedN);
                            $statusClass = strtolower($statusLabel);
                            $orgType = (string) ($batch->orgUnit->type ?? '');
                            if ($unassignedN > 0 && $orgType === \App\Models\GoalOrgUnit::TYPE_BRANCH && $handoffKey === 'bm') {
                                $statusLabel = 'Assigned to you';
                                $statusClass = 'await';
                            } elseif ($unassignedN > 0 && $orgType === \App\Models\GoalOrgUnit::TYPE_CLUSTER && $handoffKey === 'cluster') {
                                $statusLabel = 'Assigned to you';
                                $statusClass = 'await';
                            } elseif ($unassignedN > 0 && $orgType === \App\Models\GoalOrgUnit::TYPE_BRANCH) {
                                $statusLabel = 'Awaiting BM';
                                $statusClass = 'await';
                            } elseif ($unassignedN > 0 && $orgType === \App\Models\GoalOrgUnit::TYPE_CLUSTER) {
                                $statusLabel = 'Awaiting Cluster';
                                $statusClass = 'await';
                            }
                            $assignedLabel = $batch->assigned_label
                                ?: (($batch->orgUnit)
                                    ? (($orgType === 'branch' ? 'Branch: ' : ($orgType === 'cluster' ? 'Cluster: ' : '')).$batch->orgUnit->name)
                                    : '—');
                            $validN = (int) $batch->success_count;
                            $pct = $validN > 0 ? (int) round(($assignedPeople / $validN) * 100) : ($unassignedN === 0 && $validN > 0 ? 100 : 0);
                            if ($statusClass === 'completed') { $pct = 100; $assignedPeople = max($assignedPeople, $validN); }
                            $tone = match ($statusClass) {
                                'completed' => 'view',
                                'active' => 'active',
                                'await' => 'await',
                                'draft' => 'draft',
                                'failed' => 'failed',
                                default => 'view',
                            };
                            $progTone = in_array($statusClass, ['completed', 'await', 'draft', 'failed', 'active'], true) ? $statusClass : '';

                            // Once every contact is on a VRM (Completed), never show Assign buttons.
                            $assignedToVrms = $validN > 0
                                && $unassignedN === 0
                                && ($statusClass === 'completed' || $assignedPeople >= $validN || ($batch->status ?? '') === 'assigned');
                            $showAssignBtn = false;
                            $assignBtnLabel = 'Assign';
                            if ($canSplit && $unassignedN > 0 && ! $assignedToVrms && $statusClass !== 'completed') {
                                if ($handoffKey === 'bm' && $orgType === \App\Models\GoalOrgUnit::TYPE_BRANCH) {
                                    $showAssignBtn = true;
                                    $assignBtnLabel = 'Assign to team';
                                } elseif ($handoffKey === 'cluster') {
                                    $showAssignBtn = true;
                                    $assignBtnLabel = $orgType === \App\Models\GoalOrgUnit::TYPE_CLUSTER
                                        ? 'Assign onward'
                                        : 'Re-assign';
                                } elseif ($handoffKey === 'rd') {
                                    $showAssignBtn = true;
                                    $assignBtnLabel = $orgType === \App\Models\GoalOrgUnit::TYPE_REGION
                                        ? 'Assign onward'
                                        : 'Re-assign';
                                } elseif (in_array($handoffKey, ['hq', 'other'], true)) {
                                    $showAssignBtn = true;
                                    $assignBtnLabel = in_array($orgType, [
                                        \App\Models\GoalOrgUnit::TYPE_BRANCH,
                                        \App\Models\GoalOrgUnit::TYPE_CLUSTER,
                                    ], true) ? 'Re-assign' : 'Finish assign';
                                }
                            }
                        @endphp
                        <tr>
                            <td>{{ $batches->firstItem() + $i }}</td>
                            <td>
                                <div class="name"><i class="bi bi-megaphone"></i>{{ $batch->displayName() }}</div>
                            </td>
                            <td title="{{ $batch->filename }}">{{ \Illuminate\Support\Str::limit($batch->filename, 28) }}</td>
                            <td>{{ optional($batch->created_at)->format('d M Y, H:i') }}</td>
                            <td>
                                <div class="cg-recs">
                                    <span>Total <b>{{ number_format((int)$batch->total_rows) }}</b></span>
                                    <span>Valid <b>{{ number_format($validN) }}</b></span>
                                    <span>Dup <b>{{ number_format($dupN) }}</b></span>
                                    <span>Invalid <b>{{ number_format($invN) }}</b></span>
                                </div>
                            </td>
                            <td>{{ $assignedLabel }}</td>
                            <td>{{ optional($batch->assigned_at)->format('d M Y, H:i') ?: '—' }}</td>
                            <td><span class="cg-badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                            <td>
                                <div class="cg-prog {{ $progTone ? 'is-'.$progTone : '' }}">
                                    <div class="bar"><span style="width:{{ min(100, $pct) }}%"></span></div>
                                    <div class="txt">{{ number_format($assignedPeople) }} Assigned — {{ $pct }}%</div>
                                </div>
                            </td>
                            <td style="white-space:nowrap">
                                @if($showAssignBtn)
                                    <a class="cg-btn cg-btn-assign tone-await" href="{{ route('goal.contacts.import', ['tab' => 'assign', 'batch' => $batch->id]) }}" aria-label="{{ $assignBtnLabel }}">
                                        {{ $assignBtnLabel }}
                                    </a>
                                @endif
                                @if($batch->failed_count > 0)
                                    <a class="cg-ico tone-errors" href="{{ route('goal.contacts.import.errors', $batch) }}" aria-label="Error report"><i class="bi bi-download"></i></a>
                                @endif
                                <button type="button"
                                    class="cg-ico tone-{{ $tone }} js-cg-view"
                                    aria-label="View campaign"
                                    data-name="{{ e($batch->displayName()) }}"
                                    data-file="{{ e($batch->filename) }}"
                                    data-imported="{{ optional($batch->created_at)->format('d M Y, H:i') }}"
                                    data-total="{{ (int) $batch->total_rows }}"
                                    data-valid="{{ $validN }}"
                                    data-open="{{ $unassignedN }}"
                                    data-invalid="{{ $invN }}"
                                    data-assigned-to="{{ e($assignedLabel) }}"
                                    data-assigned-on="{{ optional($batch->assigned_at)->format('d M Y, H:i') ?: '—' }}"
                                    data-source="{{ e($batch->lead_source ?: '—') }}"
                                    data-status="{{ e($statusLabel) }}"
                                    data-desc="{{ e($batch->description ?: '') }}"
                                    data-assign-url="{{ $showAssignBtn ? route('goal.contacts.import', ['tab' => 'assign', 'batch' => $batch->id]) : '' }}"
                                    data-assign-label="{{ e($assignBtnLabel) }}"
                                ><i class="bi bi-eye"></i></button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
                                No campaigns yet.
                                <a class="cg-btn cg-btn-add" style="margin-left:8px" href="{{ route('goal.contacts.import.create') }}">
                                    <i class="bi bi-plus-lg"></i> Add Campaign
                                </a>
                            </td>
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

{{-- Campaign view popup --}}
<div class="cg-view-modal" id="cgViewModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="cgVmTitle">
    <div class="cg-vm-backdrop" data-cg-close></div>
    <div class="cg-vm-dialog">
        <div class="cg-vm-head">
            <div style="min-width:0">
                <h3 id="cgVmTitle">Campaign</h3>
                <p id="cgVmSub">—</p>
            </div>
            <button type="button" class="cg-vm-close" data-cg-close aria-label="Close"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="cg-vm-body">
            <div class="cg-vm-stats">
                <div class="cg-vm-stat"><p class="l">Total</p><p class="v" id="cgVmTotal">0</p></div>
                <div class="cg-vm-stat ok"><p class="l">Valid</p><p class="v" id="cgVmValid">0</p></div>
                <div class="cg-vm-stat warn"><p class="l">Open</p><p class="v" id="cgVmOpen">0</p></div>
                <div class="cg-vm-stat bad"><p class="l">Invalid</p><p class="v" id="cgVmInvalid">0</p></div>
            </div>
            <div class="cg-vm-grid">
                <div><span>Assigned to</span><div id="cgVmAssignedTo">—</div></div>
                <div><span>Assigned on</span><div id="cgVmAssignedOn">—</div></div>
                <div><span>Lead source</span><div id="cgVmSource">—</div></div>
                <div><span>Status</span><div id="cgVmStatus">—</div></div>
            </div>
            <p class="cg-vm-desc" id="cgVmDesc" hidden></p>
        </div>
        <div class="cg-vm-foot">
            <button type="button" class="cg-btn" data-cg-close>Close</button>
            <a class="cg-btn cg-btn-assign tone-await" id="cgVmAssign" href="#" hidden>Assign onward</a>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    function notify(type, message, title) {
        if (window.toastr && typeof toastr[type] === 'function') { toastr[type](message); return; }
        if (typeof Swal !== 'undefined' && Swal.fire) {
            Swal.fire({ icon: type === 'error' ? 'error' : 'success', title: title || 'Campaigns', text: message, confirmButtonColor: '#16a34a' });
            return;
        }
        alert(message);
    }
    @if(session('success'))
    notify('success', @json(session('success')), 'Success');
    @endif
    @if($errors->any())
    notify('error', @json($errors->first()), 'Error');
    @endif

    var modal = document.getElementById('cgViewModal');
    if (!modal) return;

    function fmt(n) {
        n = parseInt(n, 10) || 0;
        return n.toLocaleString();
    }
    function openView(btn) {
        document.getElementById('cgVmTitle').textContent = btn.getAttribute('data-name') || 'Campaign';
        document.getElementById('cgVmSub').textContent =
            (btn.getAttribute('data-file') || '') + ' · Imported ' + (btn.getAttribute('data-imported') || '—');
        document.getElementById('cgVmTotal').textContent = fmt(btn.getAttribute('data-total'));
        document.getElementById('cgVmValid').textContent = fmt(btn.getAttribute('data-valid'));
        document.getElementById('cgVmOpen').textContent = fmt(btn.getAttribute('data-open'));
        document.getElementById('cgVmInvalid').textContent = fmt(btn.getAttribute('data-invalid'));
        document.getElementById('cgVmAssignedTo').textContent = btn.getAttribute('data-assigned-to') || '—';
        document.getElementById('cgVmAssignedOn').textContent = btn.getAttribute('data-assigned-on') || '—';
        document.getElementById('cgVmSource').textContent = btn.getAttribute('data-source') || '—';
        document.getElementById('cgVmStatus').textContent = btn.getAttribute('data-status') || '—';
        var desc = (btn.getAttribute('data-desc') || '').trim();
        var descEl = document.getElementById('cgVmDesc');
        if (desc) { descEl.hidden = false; descEl.textContent = desc; }
        else { descEl.hidden = true; descEl.textContent = ''; }
        var assign = document.getElementById('cgVmAssign');
        var assignUrl = btn.getAttribute('data-assign-url') || '';
        if (assignUrl) {
            assign.hidden = false;
            assign.href = assignUrl;
            assign.textContent = btn.getAttribute('data-assign-label') || 'Assign onward';
        } else {
            assign.hidden = true;
            assign.removeAttribute('href');
        }
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
    function closeView() {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }
    document.querySelectorAll('.js-cg-view').forEach(function (btn) {
        btn.addEventListener('click', function () { openView(btn); });
    });
    modal.querySelectorAll('[data-cg-close]').forEach(function (el) {
        el.addEventListener('click', closeView);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('open')) closeView();
    });
})();
</script>
@endpush
