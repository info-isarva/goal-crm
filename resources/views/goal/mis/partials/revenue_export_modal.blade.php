@php
    $reportCatalog = $reportCatalog ?? [];
    $opts = $filterOptions ?? [];
    $f = $filters ?? [];
    $monthVal = !empty($f['date_from']) ? \Carbon\Carbon::parse($f['date_from'])->format('Y-m') : now()->format('Y-m');
@endphp
<style>
/* Beat global tw-overlays.css modal width caps — XXL workspace */
.modal.rev-export-modal.show .modal-dialog,
.modal.rev-export-modal .modal-dialog{
    width:min(1600px,98vw) !important;
    max-width:min(1600px,98vw) !important;
    margin:0 !important;
    max-height:94dvh !important;
    height:auto !important
}
.modal.rev-export-modal .modal-content{
    border:0 !important;border-radius:16px !important;overflow:hidden !important;
    box-shadow:0 24px 64px rgba(15,23,42,.22) !important;
    height:auto !important;max-height:94dvh !important;
    display:flex !important;flex-direction:column !important
}
.rev-export-modal .modal-header{
    padding:14px 18px;border-bottom:1px solid #e8edf5;align-items:flex-start;gap:12px;background:#fff;flex-shrink:0
}
.rev-export-modal .rev-ex-hero{display:flex;align-items:flex-start;gap:12px;min-width:0;flex:1}
.rev-export-modal .rev-ex-hero-ico{
    width:42px;height:42px;border-radius:12px;background:#16a34a;color:#fff;
    display:inline-flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;
    box-shadow:0 6px 14px rgba(22,163,74,.28)
}
.rev-export-modal .rev-ex-hero h5{margin:0;font-size:1.12rem;font-weight:800;color:#0f172a;letter-spacing:-.01em}
.rev-export-modal .rev-ex-hero p{margin:4px 0 0;font-size:.8rem;color:#64748b;line-height:1.35}
.rev-export-modal .modal-body{
    padding:0 !important;background:#fff;flex:1 1 auto;min-height:0;overflow:hidden !important;
    display:flex;flex-direction:column
}
.rev-export-modal .rev-ex-grid{
    display:grid;grid-template-columns:340px minmax(360px,1fr) minmax(420px,1.2fr);
    flex:1 1 auto;min-height:0;height:min(720px, calc(94dvh - 140px));width:100%
}
@media (max-width:1399.98px){
    .modal.rev-export-modal.show .modal-dialog,
    .modal.rev-export-modal .modal-dialog{
        width:min(1440px,98vw) !important;max-width:min(1440px,98vw) !important
    }
    .rev-export-modal .rev-ex-grid{grid-template-columns:310px minmax(320px,1fr) minmax(360px,1.1fr)}
}
@media (max-width:1199.98px){
    .modal.rev-export-modal.show .modal-dialog,
    .modal.rev-export-modal .modal-dialog{
        width:min(1180px,96vw) !important;max-width:min(1180px,96vw) !important
    }
    .rev-export-modal .rev-ex-grid{grid-template-columns:280px minmax(280px,1fr) minmax(300px,1fr)}
}
@media (max-width:991.98px){
    .rev-export-modal .modal-body{overflow:auto !important}
    .rev-export-modal .rev-ex-grid{grid-template-columns:1fr;height:auto}
}
.rev-export-modal .rev-ex-col{
    padding:14px 14px 16px;border-right:1px solid #eef2f7;background:#fff;
    overflow:auto;min-height:0;min-width:0
}
.rev-export-modal .rev-ex-col.is-types{
    display:flex;flex-direction:column;overflow:hidden;padding-right:10px
}
.rev-export-modal .rev-ex-types-list{
    flex:1 1 auto;min-height:0;overflow-y:auto;overflow-x:hidden;
    padding-right:4px;margin-right:-2px;
    scrollbar-width:none;-ms-overflow-style:none
}
.rev-export-modal .rev-ex-types-list::-webkit-scrollbar{width:0;height:0;display:none}
.rev-export-modal .rev-ex-types-list:hover,
.rev-export-modal .rev-ex-types-list:focus-within{
    scrollbar-width:thin;scrollbar-color:rgba(148,163,184,.7) transparent
}
.rev-export-modal .rev-ex-types-list:hover::-webkit-scrollbar,
.rev-export-modal .rev-ex-types-list:focus-within::-webkit-scrollbar{
    width:6px;display:block
}
.rev-export-modal .rev-ex-types-list:hover::-webkit-scrollbar-thumb,
.rev-export-modal .rev-ex-types-list:focus-within::-webkit-scrollbar-thumb{
    background:rgba(148,163,184,.75);border-radius:999px
}
.rev-export-modal .rev-ex-types-list:hover::-webkit-scrollbar-track,
.rev-export-modal .rev-ex-types-list:focus-within::-webkit-scrollbar-track{background:transparent}
.rev-export-modal .rev-ex-col.is-preview{border-right:0;background:#f1f5f9;padding:12px 12px 10px;display:flex;flex-direction:column;min-height:0;overflow:hidden}
.rev-export-modal .rev-ex-label{
    font-size:.7rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase;color:#94a3b8;margin:0 0 10px
}
.rev-export-modal .rev-ex-types-h{
    display:flex;align-items:center;gap:8px;margin:0 0 12px
}
.rev-export-modal .rev-ex-types-h-ico{
    width:28px;height:28px;border-radius:8px;background:#16a34a;color:#fff;
    display:inline-flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0;
    box-shadow:0 3px 8px rgba(22,163,74,.25)
}
.rev-export-modal .rev-ex-types-h .rev-ex-label{margin:0;color:#0f172a;letter-spacing:.02em;text-transform:none;font-size:.88rem}
.rev-export-modal .rev-ex-types-h .n{font-size:.72rem;font-weight:700;color:#94a3b8}
.rev-export-modal .rev-ex-type{
    width:100%;display:flex;align-items:center;gap:12px;text-align:left;position:relative;
    border:1.5px solid #e5eaf2;border-radius:12px;background:#fff;padding:12px 14px;
    margin:0 0 10px;cursor:pointer;
    box-shadow:0 1px 2px rgba(15,23,42,.03);
    transition:border-color .15s,background .15s,box-shadow .15s
}
.rev-export-modal .rev-ex-type:last-child{margin-bottom:2px}
.rev-export-modal .rev-ex-type:hover{border-color:#86efac;background:#f8fffb}
.rev-export-modal .rev-ex-type.is-active{
    border-color:#16a34a;background:#ecfdf5;
    box-shadow:0 0 0 1px rgba(22,163,74,.15),0 4px 12px rgba(22,163,74,.08)
}
.rev-export-modal .rev-ex-type-ico{
    width:40px;height:40px;border-radius:10px;display:inline-flex !important;align-items:center;justify-content:center;
    font-size:1.15rem !important;flex-shrink:0;color:#fff !important;margin:0 !important;line-height:1 !important
}
.rev-export-modal .rev-ex-type-ico > i,
.rev-export-modal .rev-ex-type-ico.bi::before{
    color:#fff !important;font-size:1.15rem !important;line-height:1 !important;display:inline-block
}
.rev-export-modal .rev-ex-type-ico.t-green{background:#16a34a}
.rev-export-modal .rev-ex-type-ico.t-teal{background:#0d9488}
.rev-export-modal .rev-ex-type-ico.t-blue{background:#2563eb}
.rev-export-modal .rev-ex-type-ico.t-indigo{background:#4f46e5}
.rev-export-modal .rev-ex-type-ico.t-amber{background:#f59e0b}
.rev-export-modal .rev-ex-type-ico.t-rose{background:#e11d48}
.rev-export-modal .rev-ex-type-ico.t-violet{background:#7c3aed}
.rev-export-modal .rev-ex-type-ico.t-mint{background:#10b981}
.rev-export-modal .rev-ex-type-ico.t-navy{background:#1e40af}
.rev-export-modal .rev-ex-type-ico.t-slate{background:#475569}
.rev-export-modal .rev-ex-type-body{min-width:0;flex:1;padding-right:18px}
.rev-export-modal .rev-ex-type-body strong{display:block;font-size:.82rem;color:#0f172a;line-height:1.25;font-weight:800}
.rev-export-modal .rev-ex-type-body span{display:block;font-size:.68rem;color:#64748b;margin-top:2px;line-height:1.35}
.rev-export-modal .rev-ex-type .chk{
    position:absolute;top:10px;right:10px;color:#16a34a;opacity:0;font-size:1.05rem;
    width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center
}
.rev-export-modal .rev-ex-type.is-active .chk{opacity:1}
.rev-export-modal .rev-ex-head-row{display:flex;align-items:center;justify-content:space-between;gap:8px;margin:0 0 8px}
.rev-export-modal .rev-ex-sec-h{
    display:inline-flex;align-items:center;gap:6px;margin:0;
    font-size:.82rem;font-weight:800;color:#0f172a;letter-spacing:.01em;line-height:1.2
}
.rev-export-modal .rev-ex-sec-h > i{color:#16a34a;font-size:.88rem;line-height:1}
.rev-export-modal .rev-ex-reset{
    display:inline-flex;align-items:center;gap:4px;border:1px solid #e2e8f0;background:#fff;
    color:#475569;font-size:.68rem;font-weight:700;padding:3px 8px;border-radius:6px;
    box-shadow:none;transition:border-color .15s,color .15s,background .15s;line-height:1.2
}
.rev-export-modal .rev-ex-reset:hover{border-color:#cbd5e1;color:#0f172a;background:#f8fafc;text-decoration:none}
.rev-export-modal .rev-ex-col.is-filters{
    display:flex;flex-direction:column;overflow:hidden;padding:12px 12px 10px
}
.rev-export-modal .rev-ex-filters-scroll{
    flex:1 1 auto;min-height:0;overflow-y:auto;overflow-x:hidden;
    scrollbar-width:none;-ms-overflow-style:none
}
.rev-export-modal .rev-ex-filters-scroll::-webkit-scrollbar{width:0;height:0;display:none}
.rev-export-modal .rev-ex-filters-scroll:hover{scrollbar-width:thin;scrollbar-color:rgba(148,163,184,.7) transparent}
.rev-export-modal .rev-ex-filters-scroll:hover::-webkit-scrollbar{width:6px;display:block}
.rev-export-modal .rev-ex-filters-scroll:hover::-webkit-scrollbar-thumb{background:rgba(148,163,184,.75);border-radius:999px}
.rev-export-modal .rev-ex-filters{
    display:grid;grid-template-columns:1fr 1fr;gap:6px 8px;align-items:start
}
.rev-export-modal .rev-ex-filters .span-2{grid-column:1 / -1}
.rev-export-modal .rev-ex-field{min-width:0;display:flex;flex-direction:column;gap:2px}
.rev-export-modal .rev-ex-field label{
    display:block;font-size:.62rem;font-weight:700;color:#1e293b;margin:0;line-height:1.15
}
.rev-export-modal .rev-ex-field label .req{color:#dc2626;font-weight:800;margin-left:1px}
/* Beat global .form-control min-h-[38px] / py-2 — keep compact fields */
#revExportModal.rev-export-modal .rev-ex-field .form-control,
#revExportModal.rev-export-modal .rev-ex-field .form-select,
#revExportModal.rev-export-modal .rev-ex-field .flatpickr-input,
#revExportModal.rev-export-modal .rev-ex-field .flatpickr-alt,
#revExportModal.rev-export-modal .rev-ex-field input.form-control{
    height:28px !important;min-height:28px !important;max-height:28px !important;
    font-size:.72rem !important;border-radius:6px !important;border:1px solid #e2e8f0 !important;
    background:#fff !important;box-shadow:none !important;
    padding:0 .55rem !important;line-height:26px !important;width:100% !important;
    box-sizing:border-box !important;color:#334155 !important
}
#revExportModal.rev-export-modal .rev-ex-field .form-select{
    padding-right:1.55rem !important;background-position:right .45rem center !important;
    background-size:10px 8px !important
}
.rev-export-modal .rev-ex-range-wrap,
.rev-export-modal .rev-ex-month-wrap{position:relative}
.rev-export-modal .rev-ex-range-wrap .flatpickr-wrapper,
.rev-export-modal .rev-ex-month-wrap .flatpickr-wrapper{display:block;width:100%}
.rev-export-modal .rev-ex-field.is-locked{opacity:.55;pointer-events:none}
.rev-export-modal .rev-ex-field.is-locked .rev-ex-range-ico{opacity:.7}
#revExportModal.rev-export-modal .rev-ex-field.is-locked .form-control,
#revExportModal.rev-export-modal .rev-ex-field.is-locked .flatpickr-alt,
#revExportModal.rev-export-modal .rev-ex-field.is-locked .flatpickr-input{
    background:#f1f5f9 !important;color:#64748b !important;cursor:not-allowed !important
}
#revExportModal.rev-export-modal .rev-ex-range-wrap .form-control,
#revExportModal.rev-export-modal .rev-ex-month-wrap .form-control,
#revExportModal.rev-export-modal .rev-ex-range-wrap .flatpickr-alt,
#revExportModal.rev-export-modal .rev-ex-month-wrap .flatpickr-alt{
    padding-right:1.65rem !important
}
.rev-export-modal .rev-ex-range-wrap .rev-ex-range-ico,
.rev-export-modal .rev-ex-month-wrap .rev-ex-range-ico{
    position:absolute;right:8px;top:50%;transform:translateY(-50%);
    color:#94a3b8;font-size:.72rem;pointer-events:none;line-height:1;z-index:3
}
#revExportModal.rev-export-modal .rev-ex-field .form-control:focus,
#revExportModal.rev-export-modal .rev-ex-field .form-select:focus,
#revExportModal.rev-export-modal .rev-ex-field .flatpickr-alt:focus{
    border-color:#86efac !important;box-shadow:0 0 0 2px rgba(22,163,74,.1) !important;outline:0 !important
}
/* Compact searchable Select2 */
#revExportModal .rev-ex-field .select2-container{width:100% !important}
#revExportModal .rev-ex-field .select2-container--default .select2-selection--single{
    height:28px !important;min-height:28px !important;border:1px solid #e2e8f0 !important;
    border-radius:6px !important;background:#fff !important
}
#revExportModal .rev-ex-field .select2-container--default .select2-selection--single .select2-selection__rendered{
    line-height:26px !important;padding-left:.55rem !important;padding-right:1.6rem !important;
    font-size:.72rem !important;color:#334155 !important
}
#revExportModal .rev-ex-field .select2-container--default .select2-selection--single .select2-selection__arrow{
    height:26px !important;right:4px !important
}
#revExportModal .rev-ex-field .select2-container--default .select2-selection--single .select2-selection__clear{
    height:26px !important;margin-right:1.1rem !important;font-size:.85rem !important;line-height:26px !important
}
#revExportModal .rev-ex-field .select2-container--default.select2-container--focus .select2-selection--single,
#revExportModal .rev-ex-field .select2-container--default.select2-container--open .select2-selection--single{
    border-color:#86efac !important;box-shadow:0 0 0 2px rgba(22,163,74,.1) !important
}
#revExportModal .select2-dropdown{
    border:1px solid #e2e8f0 !important;border-radius:8px !important;font-size:.76rem !important;
    box-shadow:0 10px 24px rgba(15,23,42,.12) !important
}
#revExportModal .select2-search--dropdown .select2-search__field{
    height:30px !important;min-height:30px !important;border-radius:6px !important;
    border:1px solid #e2e8f0 !important;font-size:.76rem !important;padding:.25rem .5rem !important
}
#revExportModal .select2-results__option{padding:6px 10px !important;font-size:.76rem !important}
#revExportModal .select2-results__option--highlighted{background:#16a34a !important;color:#fff !important}
.rev-export-modal .rev-ex-fmt-wrap{
    margin-top:8px;padding-top:8px;border-top:1px solid #eef2f7;flex-shrink:0
}
.rev-export-modal .rev-ex-fmt-wrap .rev-ex-sec-h{margin-bottom:6px}
.rev-export-modal .rev-ex-fmt{
    display:flex;flex-direction:column;gap:6px;border:0;border-radius:0;
    background:transparent;overflow:visible
}
.rev-export-modal .rev-ex-fmt-btn{
    display:flex;align-items:center;gap:8px;border:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0;
    background:#fff;padding:7px 10px;cursor:pointer;text-align:left;width:100%;min-height:0;
    border-radius:8px;box-shadow:none;transition:background .15s,border-color .15s
}
.rev-export-modal .rev-ex-fmt-btn:last-child{border-bottom:1px solid #e2e8f0}
.rev-export-modal .rev-ex-fmt-btn:hover{background:#f8fffb;border-color:#bbf7d0}
.rev-export-modal .rev-ex-fmt-btn.is-active{background:#f0fdf4;border-color:#86efac}
.rev-export-modal .rev-ex-fmt-radio{
    width:16px;height:16px;border-radius:999px;border:1.5px solid #cbd5e1;flex-shrink:0;
    display:inline-flex !important;align-items:center;justify-content:center;background:#fff;
    margin:0 !important;font-size:0 !important;line-height:0 !important;color:transparent !important;
    position:relative
}
.rev-export-modal .rev-ex-fmt-btn.is-active .rev-ex-fmt-radio{
    border-color:#16a34a;background:#16a34a;color:#fff !important
}
.rev-export-modal .rev-ex-fmt-btn.is-active .rev-ex-fmt-radio::after{
    content:'\F26E';font-family:bootstrap-icons !important;font-size:.65rem !important;
    color:#fff !important;line-height:1 !important;display:block
}
.rev-export-modal .rev-ex-fmt-ico{
    width:auto;height:auto;border-radius:0;background:transparent !important;
    display:inline-flex !important;align-items:center;justify-content:center;
    font-size:1.05rem !important;flex-shrink:0;margin:0 !important;line-height:1 !important;
    padding:0;min-width:1.05rem
}
.rev-export-modal .rev-ex-fmt-ico > i{
    color:inherit !important;font-size:1.05rem !important;line-height:1 !important;display:inline-block;margin:0
}
.rev-export-modal .rev-ex-fmt-ico.is-pdf{color:#dc2626 !important}
.rev-export-modal .rev-ex-fmt-ico.is-xls{color:#16a34a !important}
.rev-export-modal .rev-ex-fmt-txt{min-width:0;flex:1;display:flex;flex-direction:column;justify-content:center;gap:0}
.rev-export-modal .rev-ex-fmt-txt strong{display:block;font-size:.74rem;color:#0f172a;font-weight:800;line-height:1.2;margin:0}
.rev-export-modal .rev-ex-fmt-txt em{
    display:block;font-size:.62rem;color:#64748b;line-height:1.25;font-style:normal;margin:0
}
.rev-export-modal .rev-ex-prev-top{
    display:flex;align-items:center;justify-content:space-between;gap:8px;margin:0 0 10px;flex-shrink:0
}
.rev-export-modal .rev-ex-prev-top .rev-ex-sec-h{font-size:.82rem}
.rev-export-modal .rev-ex-prev-badge{
    display:inline-flex;align-items:center;max-width:55%;padding:3px 9px;border-radius:999px;
    background:#ecfdf5;border:1px solid #bbf7d0;color:#15803d;font-size:.65rem;font-weight:800;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.3
}
.rev-export-modal .rev-ex-preview{
    background:#fff;border:1px solid #e2e8f0;border-radius:14px;height:100%;
    box-shadow:0 10px 28px rgba(15,23,42,.08);padding:0;
    display:flex;flex-direction:column;min-height:0;overflow:hidden
}
.rev-export-modal .rev-ex-preview-scroll{flex:1;min-height:0;overflow:auto;padding:12px 12px 8px}
.rev-export-modal .rev-ex-brand{
    position:relative;overflow:hidden;border:1px solid #e8edf5;border-radius:12px;
    padding:12px 14px 11px;margin:0 0 10px;background:#fff
}
.rev-export-modal .rev-ex-brand::after{
    content:'';position:absolute;top:-28px;right:-24px;width:88px;height:88px;
    background:linear-gradient(135deg,rgba(34,197,94,.22),rgba(22,163,74,.05));
    transform:rotate(28deg);border-radius:18px;pointer-events:none
}
.rev-export-modal .rev-ex-brand-row{display:grid;grid-template-columns:auto 1fr;gap:8px 10px;align-items:center;position:relative;z-index:1}
.rev-export-modal .rev-ex-brand-logo{
    width:34px;height:34px;border-radius:10px;background:linear-gradient(145deg,#16a34a,#22c55e);
    color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;
    box-shadow:0 4px 10px rgba(22,163,74,.28);grid-row:1 / span 2
}
.rev-export-modal .rev-ex-brand-co{font-size:.62rem;font-weight:800;color:#059669;letter-spacing:.06em;text-transform:uppercase;line-height:1.15;grid-column:2}
.rev-export-modal .rev-ex-brand-co span{display:inline;font-size:.58rem;font-weight:600;color:#64748b;letter-spacing:.02em;text-transform:none;margin-left:4px}
.rev-export-modal .rev-ex-brand-main{grid-column:1 / -1;text-align:center;padding:2px 8px 0}
.rev-export-modal .rev-ex-brand-main h6{margin:0;font-size:.92rem;font-weight:800;color:#0f172a;line-height:1.25}
.rev-export-modal .rev-ex-brand-main .sub{margin:3px 0 0;font-size:.66rem;color:#64748b;line-height:1.35}
.rev-export-modal .rev-ex-filters-bar{
    display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:4px 10px;
    background:#eff6ff;border:1px solid #dbeafe;border-radius:10px;padding:8px 10px;margin:0 0 10px
}
.rev-export-modal .rev-ex-filters-bar .fb{
    font-size:.62rem;color:#334155;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis
}
.rev-export-modal .rev-ex-filters-bar .fb b{font-weight:800;color:#0f172a}
.rev-export-modal .rev-ex-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:7px;margin-bottom:10px}
@media (max-width:767.98px){.rev-export-modal .rev-ex-kpis{grid-template-columns:1fr 1fr}}
.rev-export-modal .rev-ex-kpi{
    border-radius:10px;padding:8px 9px;border:1px solid transparent;min-width:0
}
.rev-export-modal .rev-ex-kpi .kpi-top{display:flex;align-items:center;justify-content:space-between;gap:4px;margin-bottom:4px}
.rev-export-modal .rev-ex-kpi .kpi-ico{
    width:22px;height:22px;border-radius:7px;display:inline-flex;align-items:center;justify-content:center;
    font-size:.72rem;flex-shrink:0
}
.rev-export-modal .rev-ex-kpi .lbl{font-size:.58rem;font-weight:800;letter-spacing:.02em;line-height:1.15}
.rev-export-modal .rev-ex-kpi .val{font-size:.78rem;font-weight:800;line-height:1.15;word-break:break-word;color:#0f172a}
.rev-export-modal .rev-ex-kpi.t-green{background:#ecfdf5;border-color:#bbf7d0}
.rev-export-modal .rev-ex-kpi.t-green .lbl,.rev-export-modal .rev-ex-kpi.t-green .kpi-ico{color:#15803d}
.rev-export-modal .rev-ex-kpi.t-green .kpi-ico{background:#dcfce7}
.rev-export-modal .rev-ex-kpi.t-blue{background:#eff6ff;border-color:#bfdbfe}
.rev-export-modal .rev-ex-kpi.t-blue .lbl,.rev-export-modal .rev-ex-kpi.t-blue .kpi-ico{color:#1d4ed8}
.rev-export-modal .rev-ex-kpi.t-blue .kpi-ico{background:#dbeafe}
.rev-export-modal .rev-ex-kpi.t-amber{background:#fffbeb;border-color:#fde68a}
.rev-export-modal .rev-ex-kpi.t-amber .lbl,.rev-export-modal .rev-ex-kpi.t-amber .kpi-ico{color:#b45309}
.rev-export-modal .rev-ex-kpi.t-amber .kpi-ico{background:#fef3c7}
.rev-export-modal .rev-ex-kpi.t-rose{background:#fff1f2;border-color:#fecdd3}
.rev-export-modal .rev-ex-kpi.t-rose .lbl,.rev-export-modal .rev-ex-kpi.t-rose .kpi-ico{color:#be123c}
.rev-export-modal .rev-ex-kpi.t-rose .kpi-ico{background:#ffe4e6}
.rev-export-modal .rev-ex-kpi.t-slate{background:#f8fafc;border-color:#e2e8f0}
.rev-export-modal .rev-ex-kpi.t-slate .lbl,.rev-export-modal .rev-ex-kpi.t-slate .kpi-ico{color:#475569}
.rev-export-modal .rev-ex-kpi.t-slate .kpi-ico{background:#e2e8f0}
.rev-export-modal .rev-ex-charts{display:grid;grid-template-columns:1.2fr .8fr;gap:8px;margin-bottom:10px}
.rev-export-modal .rev-ex-chart{
    border:1px solid #eef2f7;border-radius:10px;padding:9px 10px;background:#fff;min-height:118px
}
.rev-export-modal .rev-ex-chart h6{margin:0 0 8px;font-size:.7rem;font-weight:800;color:#0f172a}
.rev-export-modal .rev-ex-bars{display:flex;align-items:flex-end;gap:5px;height:78px;padding:0 2px}
.rev-export-modal .rev-ex-bar-col{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;gap:3px;min-width:0;height:100%;position:relative}
.rev-export-modal .rev-ex-bar{
    width:100%;max-width:18px;border-radius:4px 4px 2px 2px;background:#34d399;min-height:3px
}
.rev-export-modal .rev-ex-bar-col.is-peak .rev-ex-bar{background:#15803d}
.rev-export-modal .rev-ex-bar-tip{
    position:absolute;top:0;left:50%;transform:translateX(-50%);
    background:#14532d;color:#fff;font-size:.52rem;font-weight:700;padding:2px 5px;border-radius:4px;
    white-space:nowrap;line-height:1.2;pointer-events:none;z-index:2
}
.rev-export-modal .rev-ex-bar-col small{font-size:.55rem;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%}
.rev-export-modal .rev-ex-donut-wrap{display:flex;align-items:center;gap:10px}
.rev-export-modal .rev-ex-donut-box{position:relative;width:78px;height:78px;flex-shrink:0}
.rev-export-modal .rev-ex-donut{
    width:78px;height:78px;border-radius:50%;
    background:conic-gradient(#16a34a 0 40%, #2563eb 40% 65%, #f59e0b 65% 85%, #e11d48 85% 100%);
    mask:radial-gradient(circle at center, transparent 52%, #000 54%);
    -webkit-mask:radial-gradient(circle at center, transparent 52%, #000 54%)
}
.rev-export-modal .rev-ex-donut-center{
    position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;
    pointer-events:none;text-align:center;padding:10px
}
.rev-export-modal .rev-ex-donut-center strong{font-size:.62rem;font-weight:800;color:#0f172a;line-height:1.1}
.rev-export-modal .rev-ex-donut-center span{font-size:.5rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em}
.rev-export-modal .rev-ex-legend{list-style:none;margin:0;padding:0;font-size:.62rem;color:#475569;min-width:0;flex:1}
.rev-export-modal .rev-ex-legend li{display:flex;align-items:center;gap:5px;margin:0 0 3px;min-width:0}
.rev-export-modal .rev-ex-legend i{width:7px;height:7px;border-radius:2px;display:inline-block;flex-shrink:0}
.rev-export-modal .rev-ex-legend .nm{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.rev-export-modal .rev-ex-legend .pc{font-weight:700;color:#64748b;flex-shrink:0}
.rev-export-modal .rev-ex-table-title{font-size:.72rem;font-weight:800;color:#0f172a;margin:0 0 6px}
.rev-export-modal .rev-ex-table-wrap{border:1px solid #eef2f7;border-radius:10px;overflow:auto;max-height:190px}
.rev-export-modal .rev-ex-table{width:100%;border-collapse:collapse;font-size:.66rem}
.rev-export-modal .rev-ex-table th{
    background:#ecfdf5;color:#065f46;font-weight:800;padding:6px 7px;text-align:left;
    white-space:nowrap;border-bottom:1px solid #d1fae5;position:sticky;top:0;z-index:1
}
.rev-export-modal .rev-ex-table th.num,.rev-export-modal .rev-ex-table td.num{text-align:right}
.rev-export-modal .rev-ex-table th.idx,.rev-export-modal .rev-ex-table td.idx{width:22px;text-align:center;color:#94a3b8}
.rev-export-modal .rev-ex-table td{padding:6px 7px;border-bottom:1px solid #f1f5f9;color:#334155;white-space:nowrap}
.rev-export-modal .rev-ex-table tr:last-child td{border-bottom:0}
.rev-export-modal .rev-ex-table .is-total td{font-weight:800;background:#ecfdf5;color:#065f46}
.rev-export-modal .rev-ex-empty{text-align:center;padding:1.5rem 1rem;color:#94a3b8;font-size:.78rem}
.rev-export-modal .rev-ex-preview-f{
    flex-shrink:0;margin:0;padding:8px 12px;border-top:1px solid #eef2f7;background:#fafbfc;
    display:flex;justify-content:space-between;gap:8px;font-size:.62rem;color:#94a3b8;font-weight:600
}
.modal.rev-export-modal .modal-footer{
    display:flex !important;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;
    padding:14px 20px;border-top:1px solid #e8edf5;background:#f8fafc;flex-shrink:0;
    grid-template-columns:unset !important
}
.modal.rev-export-modal .modal-footer > *{width:auto !important;min-height:0 !important;margin:0}
.rev-export-modal .rev-ex-note{
    font-size:.76rem;color:#1e40af;display:flex;align-items:flex-start;gap:10px;line-height:1.4;
    flex:1 1 320px;max-width:620px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;
    padding:10px 12px
}
.rev-export-modal .rev-ex-note i{color:#2563eb;font-size:1rem;margin-top:1px}
.rev-export-modal .rev-ex-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-left:auto}
.rev-export-modal .rev-ex-btn{
    height:42px;padding:0 16px;border-radius:10px;font-size:.82rem;font-weight:700;
    display:inline-flex;align-items:center;justify-content:center;gap:7px;cursor:pointer;
    white-space:nowrap;border:1.5px solid transparent;width:auto !important;min-width:0 !important;
    transition:background .15s,border-color .15s,color .15s,box-shadow .15s
}
.rev-export-modal .rev-ex-btn.is-ghost{
    background:#fff;border-color:#cbd5e1;color:#334155;box-shadow:0 1px 2px rgba(15,23,42,.04)
}
.rev-export-modal .rev-ex-btn.is-ghost:hover{background:#f8fafc;border-color:#94a3b8;color:#0f172a}
.rev-export-modal .rev-ex-btn.is-primary{
    background:#16a34a;border-color:#16a34a;color:#fff;
    box-shadow:0 6px 14px rgba(22,163,74,.28)
}
.rev-export-modal .rev-ex-btn.is-primary:hover{background:#15803d;border-color:#15803d;color:#fff}
</style>

<div class="modal fade rev-export-modal" id="revExportModal" tabindex="-1" aria-labelledby="revExportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xxl">
        <div class="modal-content">
            <div class="modal-header">
                <div class="rev-ex-hero">
                    <span class="rev-ex-hero-ico" aria-hidden="true"><i class="bi bi-box-arrow-down"></i></span>
                    <div>
                        <h5 id="revExportModalLabel">Export Revenue Reports</h5>
                        <p>Generate and download Revenue Ledger reports in PDF or Excel format.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="revExportForm" class="rev-ex-grid">
                    <input type="hidden" name="report" id="revExReport" value="summary">
                    <input type="hidden" name="format" id="revExFormat" value="pdf">

                    <div class="rev-ex-col is-types">
                        <div class="rev-ex-types-h">
                            <span class="rev-ex-types-h-ico" aria-hidden="true"><i class="bi bi-file-earmark-ruled"></i></span>
                            <div class="rev-ex-label">Select Report Type</div>
                            <span class="n">({{ count($reportCatalog) }} Reports)</span>
                        </div>
                        <div class="rev-ex-types-list">
                            @foreach($reportCatalog as $rep)
                                <button type="button" class="rev-ex-type {{ ($rep['key'] ?? '') === 'summary' ? 'is-active' : '' }}" data-report="{{ $rep['key'] }}">
                                    <span class="rev-ex-type-ico t-{{ $rep['tone'] ?? 'green' }} bi {{ $rep['icon'] ?? 'bi-file-earmark' }}" aria-hidden="true"></span>
                                    <div class="rev-ex-type-body">
                                        <strong>{{ $rep['title'] }}</strong>
                                        <span>{{ $rep['blurb'] }}</span>
                                    </div>
                                    <i class="bi bi-check-circle-fill chk" aria-hidden="true"></i>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="rev-ex-col is-filters">
                        <div class="rev-ex-head-row">
                            <div class="rev-ex-sec-h"><i class="bi bi-funnel-fill" aria-hidden="true"></i> Report Filters</div>
                            <button type="button" class="rev-ex-reset" id="revExReset"><i class="bi bi-arrow-clockwise"></i> Reset All</button>
                        </div>
                        <div class="rev-ex-filters-scroll">
                            <div class="rev-ex-filters">
                                <div class="rev-ex-field">
                                    <label for="revExFy">Financial Year <span class="req">*</span></label>
                                    <select name="fy" id="revExFy" class="form-select" data-search="true" data-allow-clear="false" data-placeholder="Select FY">
                                        @foreach(($fy_options ?? []) as $fyKey)
                                            <option value="{{ $fyKey }}" @selected(($f['fy'] ?? '') === $fyKey)>{{ str_replace('-', ' – ', $fyKey) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="rev-ex-field">
                                    <label for="revExPeriod">Report Period <span class="req">*</span></label>
                                    <select name="report_period" id="revExPeriod" class="form-select" data-search="true" data-allow-clear="false" data-placeholder="Select period">
                                        <option value="monthly" selected>Monthly</option>
                                        <option value="custom">Custom Range</option>
                                        <option value="fy">Full Financial Year</option>
                                    </select>
                                </div>
                                <div class="rev-ex-field span-2">
                                    <label for="revExMonth">Month <span class="req">*</span></label>
                                    <div class="rev-ex-month-wrap">
                                        <input type="text" name="month" id="revExMonth" class="form-control js-monthpicker" value="{{ $monthVal }}" placeholder="Select month" autocomplete="off">
                                        <i class="bi bi-calendar3 rev-ex-range-ico" aria-hidden="true"></i>
                                    </div>
                                </div>
                                <div class="rev-ex-field span-2">
                                    <label for="revExRange">Date Range (Optional)</label>
                                    <div class="rev-ex-range-wrap">
                                        <input type="text" id="revExRange" class="form-control" placeholder="Select date range" autocomplete="off">
                                        <i class="bi bi-calendar3 rev-ex-range-ico" aria-hidden="true"></i>
                                    </div>
                                    <input type="hidden" name="date_from" id="revExFrom" value="{{ $f['date_from'] ?? '' }}">
                                    <input type="hidden" name="date_to" id="revExTo" value="{{ $f['date_to'] ?? '' }}">
                                </div>
                                <div class="rev-ex-field">
                                    <label for="revExRegion">Region</label>
                                    <select name="region" id="revExRegion" class="form-select" data-search="true" data-allow-clear="true" data-placeholder="All Regions">
                                        <option value=""></option>
                                        @foreach(($opts['regions'] ?? []) as $u)
                                            <option value="{{ $u->id }}" @selected((string)($f['region'] ?? '') === (string)$u->id)>{{ $u->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="rev-ex-field">
                                    <label for="revExCluster">Cluster</label>
                                    <select name="cluster" id="revExCluster" class="form-select" data-search="true" data-allow-clear="true" data-placeholder="All Clusters">
                                        <option value=""></option>
                                        @foreach(($opts['clusters'] ?? []) as $u)
                                            <option value="{{ $u->id }}" data-parent="{{ $u->parent_id ?? '' }}" @selected((string)($f['cluster'] ?? '') === (string)$u->id)>{{ $u->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="rev-ex-field">
                                    <label for="revExBranch">Branch</label>
                                    <select name="branch" id="revExBranch" class="form-select" data-search="true" data-allow-clear="true" data-placeholder="All Branches">
                                        <option value=""></option>
                                        @foreach(($opts['branches'] ?? []) as $u)
                                            <option value="{{ $u->id }}" data-parent="{{ $u->parent_id ?? '' }}" @selected((string)($f['branch'] ?? '') === (string)$u->id)>{{ $u->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="rev-ex-field">
                                    <label for="revExOwner">Employee / Revenue Owner</label>
                                    <select name="owner" id="revExOwner" class="form-select" data-search="true" data-allow-clear="true" data-placeholder="All Employees">
                                        <option value=""></option>
                                        @foreach(($opts['owners'] ?? []) as $o)
                                            <option value="{{ $o->id }}" @selected((string)($f['owner'] ?? '') === (string)$o->id)>{{ $o->label ?? $o->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="rev-ex-field">
                                    <label for="revExProduct">Product</label>
                                    <select name="product" id="revExProduct" class="form-select" data-search="true" data-allow-clear="true" data-placeholder="All Products">
                                        <option value=""></option>
                                        @foreach(($opts['products'] ?? []) as $p)
                                            <option value="{{ $p }}" @selected(($f['product'] ?? '') === $p)>{{ $p }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="rev-ex-field">
                                    <label for="revExSource">Source</label>
                                    <select name="source" id="revExSource" class="form-select" data-search="true" data-allow-clear="true" data-placeholder="All Sources">
                                        <option value=""></option>
                                        @foreach(($opts['sources'] ?? []) as $s)
                                            <option value="{{ $s }}" @selected(($f['source'] ?? '') === $s)>{{ $s }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="rev-ex-field">
                                    <label for="revExType">Entry Type</label>
                                    <select name="entry_type" id="revExType" class="form-select" data-search="true" data-allow-clear="true" data-placeholder="All Types">
                                        <option value=""></option>
                                        <option value="recognized" @selected(($f['entry_type'] ?? '') === 'recognized')>Revenue Credit</option>
                                        <option value="adjustment" @selected(($f['entry_type'] ?? '') === 'adjustment')>Adjustment</option>
                                        <option value="reversal" @selected(($f['entry_type'] ?? '') === 'reversal')>Reversal</option>
                                    </select>
                                </div>
                                <div class="rev-ex-field">
                                    <label for="revExStatus">Status</label>
                                    <select name="status" id="revExStatus" class="form-select" data-search="true" data-allow-clear="true" data-placeholder="All Statuses">
                                        <option value=""></option>
                                        <option value="posted" @selected(($f['status'] ?? '') === 'posted')>Posted</option>
                                        <option value="pending_review" @selected(($f['status'] ?? '') === 'pending_review')>Pending Review</option>
                                        <option value="approved" @selected(($f['status'] ?? '') === 'approved')>Approved</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="rev-ex-fmt-wrap">
                            <div class="rev-ex-sec-h"><i class="bi bi-file-earmark-text-fill" aria-hidden="true"></i> Export Format</div>
                            <div class="rev-ex-fmt">
                                <button type="button" class="rev-ex-fmt-btn is-active" data-format="pdf">
                                    <span class="rev-ex-fmt-radio" aria-hidden="true"></span>
                                    <span class="rev-ex-fmt-ico is-pdf" aria-hidden="true"><i class="bi bi-file-earmark-pdf-fill"></i></span>
                                    <div class="rev-ex-fmt-txt">
                                        <strong>PDF (Print Ready)</strong>
                                        <em>Formatted report with header, summary and totals.</em>
                                    </div>
                                </button>
                                <button type="button" class="rev-ex-fmt-btn" data-format="xlsx">
                                    <span class="rev-ex-fmt-radio" aria-hidden="true"></span>
                                    <span class="rev-ex-fmt-ico is-xls" aria-hidden="true"><i class="bi bi-file-earmark-excel-fill"></i></span>
                                    <div class="rev-ex-fmt-txt">
                                        <strong>Excel (XLSX)</strong>
                                        <em>Detailed data with filters and calculations.</em>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="rev-ex-col is-preview">
                        <div class="rev-ex-prev-top">
                            <div class="rev-ex-sec-h"><i class="bi bi-eye-fill" aria-hidden="true"></i> Report Preview</div>
                            <span class="rev-ex-prev-badge" id="revExPrevBadge">Revenue Summary Report</span>
                        </div>
                        <div class="rev-ex-preview" id="revExPreview">
                            <div class="rev-ex-preview-scroll">
                                <div class="rev-ex-brand">
                                    <div class="rev-ex-brand-row">
                                        <span class="rev-ex-brand-logo" aria-hidden="true"><i class="bi bi-bar-chart-fill"></i></span>
                                        <div class="rev-ex-brand-co">GOAL CORPORATION<span>Goal Loan CRM</span></div>
                                        <div class="rev-ex-brand-main">
                                            <h6 id="revExPrevTitle">Revenue Summary Report</h6>
                                            <div class="sub" id="revExPrevMeta">Select filters and refresh preview</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="rev-ex-filters-bar" id="revExPrevFilters"></div>
                                <div class="rev-ex-kpis" id="revExPrevKpis"></div>
                                <div class="rev-ex-charts" id="revExPrevCharts" hidden>
                                    <div class="rev-ex-chart">
                                        <h6>Monthly Revenue Trend</h6>
                                        <div class="rev-ex-bars" id="revExBars"></div>
                                    </div>
                                    <div class="rev-ex-chart">
                                        <h6>Revenue by Branch</h6>
                                        <div class="rev-ex-donut-wrap">
                                            <div class="rev-ex-donut-box">
                                                <div class="rev-ex-donut" id="revExDonut" aria-hidden="true"></div>
                                                <div class="rev-ex-donut-center" id="revExDonutCenter"><strong>—</strong><span>Total</span></div>
                                            </div>
                                            <ul class="rev-ex-legend" id="revExLegend"></ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="rev-ex-table-title" id="revExTableTitle">Branch-wise Revenue Summary</div>
                                <div class="rev-ex-table-wrap">
                                    <table class="rev-ex-table">
                                        <thead id="revExPrevHead"></thead>
                                        <tbody id="revExPrevBody">
                                            <tr><td class="rev-ex-empty">Click Refresh Preview to load live data.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="rev-ex-preview-f">
                                <span id="revExPrevFootL">Goal Corporation | Revenue Report</span>
                                <span>Page 1 of 1</span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <div class="rev-ex-note">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>The preview shows live ledger data for your filters. Downloads are logged with user, report type and timestamp.</span>
                </div>
                <div class="rev-ex-actions">
                    <button type="button" class="rev-ex-btn is-ghost" id="revExRefresh"><i class="bi bi-eye"></i> Refresh Preview</button>
                    <button type="button" class="rev-ex-btn is-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="rev-ex-btn is-primary" id="revExDownload"><i class="bi bi-download"></i> Download Report</button>
                </div>
            </div>
        </div>
    </div>
</div>
