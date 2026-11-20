@extends('layouts.app')

@push('styles')
@include('goal.partials.pd_hub_styles')
<style>
.pay-page{--pay:#009B80;--pay-dark:#007F68;background:#F5F8FC;min-height:100%}
.pay-page .pd-hero{margin-bottom:1rem;align-items:center}
.pay-page .pd-hero-brand{display:flex;align-items:flex-start;gap:14px;min-width:0}
.pay-page .pay-hero-ico{
    width:48px;height:48px;border-radius:14px;flex:0 0 auto;
    display:inline-flex;align-items:center;justify-content:center;
    background:linear-gradient(160deg,#10b981 0%,#059669 100%);color:#fff;
    box-shadow:0 8px 18px rgba(5,150,105,.28);font-size:1.25rem;margin-top:2px
}
.pay-page .pd-title{font-size:1.5rem;color:#0f172a}
.pay-page .pd-sub{max-width:44rem}
.pay-page .pd-hero-actions .pd-link-btn{border-radius:10px;height:40px;padding:0 16px}
.pay-page #payResults{transition:opacity .15s ease}
.pay-page #payResults.is-loading{opacity:.6;pointer-events:none}

.pay-page .pay-flow{
    display:flex;flex-wrap:nowrap;align-items:stretch;gap:8px;margin-bottom:.85rem;
    overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none
}
.pay-page .pay-flow::-webkit-scrollbar{display:none}
.pay-page .pay-step{
    flex:1 1 0;min-width:118px;display:flex;align-items:center;gap:8px;
    padding:.6rem .7rem;background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;
    box-shadow:0 1px 2px rgba(15,23,42,.04),0 6px 14px rgba(148,163,184,.08)
}
.pay-page .pay-step-label .short,.pay-page .pay-step-sub .short{display:none}
.pay-page .pay-step-ico{width:28px;height:28px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:.82rem;flex:0 0 auto}
.pay-page .pay-step-label{font-size:.68rem;font-weight:750;color:#0f172a;margin:0;line-height:1.2}
.pay-page .pay-step-sub{font-size:.58rem;font-weight:650;color:#64748b;margin:.08rem 0 0}
.pay-page .pay-flow-arr{display:inline-flex;align-items:center;color:#94a3b8;font-size:.75rem;flex:0 0 auto}
.pay-page .pay-step.tone-red{border-color:#fca5a5;background:#fff1f2}.pay-page .pay-step.tone-red .pay-step-ico{background:#fee2e2;color:#b91c1c}
.pay-page .pay-step.tone-green{border-color:#86efac;background:#f0fdf4}.pay-page .pay-step.tone-green .pay-step-ico{background:#dcfce7;color:#15803d}
.pay-page .pay-step.tone-blue{border-color:#93c5fd;background:#eff6ff}.pay-page .pay-step.tone-blue .pay-step-ico{background:#dbeafe;color:#1d4ed8}
.pay-page .pay-step.tone-emerald{border-color:#6ee7b7;background:#ecfdf5}.pay-page .pay-step.tone-emerald .pay-step-ico{background:#d1fae5;color:#047857}
.pay-page .pay-step.tone-rose{border-color:#fda4af;background:#fff1f2}.pay-page .pay-step.tone-rose .pay-step-ico{background:#ffe4e6;color:#e11d48}

.pay-page .pay-kpi-row{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px;margin-bottom:.5rem}
.pay-page a.pay-kpi{
    --kpi-accent:#64748b;--kpi-soft:#f1f5f9;text-decoration:none;color:inherit;background:#fff;
    border:1px solid #e8edf5;border-radius:14px;padding:.8rem .85rem;min-height:96px;
    display:flex;flex-direction:row;align-items:flex-start;gap:10px;
    box-shadow:0 2px 4px rgba(15,23,42,.04),0 8px 20px rgba(148,163,184,.1);transition:transform .15s,box-shadow .15s
}
.pay-page a.pay-kpi:hover{transform:translateY(-2px);box-shadow:0 4px 10px rgba(15,23,42,.06),0 12px 26px rgba(148,163,184,.14)}
.pay-page a.pay-kpi.is-active{outline:2px solid var(--pay);outline-offset:1px}
.pay-page a.pay-kpi.tone-red{--kpi-accent:#ef4444;--kpi-soft:#fee2e2}
.pay-page a.pay-kpi.tone-green{--kpi-accent:#22c55e;--kpi-soft:#dcfce7}
.pay-page a.pay-kpi.tone-blue{--kpi-accent:#3b82f6;--kpi-soft:#dbeafe}
.pay-page a.pay-kpi.tone-emerald{--kpi-accent:#059669;--kpi-soft:#d1fae5}
.pay-page a.pay-kpi.tone-rose{--kpi-accent:#f43f5e;--kpi-soft:#ffe4e6}
.pay-page .pay-kpi-ico{width:40px;height:40px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;background:var(--kpi-soft);color:var(--kpi-accent);font-size:1.05rem;flex:0 0 auto}
.pay-page .pay-kpi-body{min-width:0;flex:1}
.pay-page .pay-kpi-label{font-size:.72rem;font-weight:700;color:#1e293b;margin:0}
.pay-page .pay-kpi-label .short{display:none}
.pay-page .pay-kpi-value{font-size:1.35rem;font-weight:800;color:#0f172a;margin:.15rem 0 0;line-height:1}
.pay-page .pay-kpi-amt{font-size:.72rem;font-weight:750;color:#334155;margin:.2rem 0 0}
.pay-page .pay-kpi-pct{font-size:.64rem;font-weight:700;color:#64748b;margin:.1rem 0 0}

.pay-page .pay-tabs{display:flex;flex-wrap:wrap;gap:8px;margin:.5rem 0}
.pay-page .pay-tab{
    display:inline-flex;align-items:center;gap:7px;padding:.52rem 1rem;border-radius:999px;
    font-size:.75rem;font-weight:700;color:#334155;text-decoration:none;background:#fff;border:1px solid #e2e8f0;
    box-shadow:0 1px 2px rgba(15,23,42,.04),0 4px 10px rgba(148,163,184,.08);white-space:nowrap
}
.pay-page .pay-tab:hover{background:#f8fafc;border-color:#cbd5e1}
.pay-page .pay-tab.active{background:linear-gradient(180deg,#00a88e 0%,#009B80 100%);color:#fff;border-color:transparent;box-shadow:0 6px 14px rgba(0,155,128,.28)}
.pay-page .pay-tab.active i{color:#fff}
.pay-page .pay-tab .short{display:none}

.pay-page .pay-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(300px,360px);gap:14px;align-items:start}
.pay-page .pay-main{display:flex;flex-direction:column;gap:12px;min-width:0}
.pay-page .pay-side{display:flex;flex-direction:column;gap:8px;position:sticky;top:76px;align-self:start}

.pay-page .pay-filters{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:.85rem 1rem;box-shadow:0 2px 4px rgba(15,23,42,.04),0 8px 20px rgba(148,163,184,.1)}
.pay-page .pay-filters-row{display:grid;gap:10px 12px;align-items:end}
.pay-page .pay-filters-row.top{grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:10px}
.pay-page .pay-filters-row.mid{grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:10px}
.pay-page .pay-filters-row.bottom{grid-template-columns:1fr auto}
.pay-page .pay-filters label{display:block;font-size:.68rem;font-weight:700;color:#64748b;margin:0 0 4px}
.pay-page .pay-filters .form-control,.pay-page .pay-filters .form-select{min-height:34px;height:34px;border-radius:8px;border:1px solid #cbd5e1;font-size:.8rem;width:100%}
.pay-page .pay-search{position:relative}
.pay-page .pay-search>i{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.8rem;pointer-events:none}
.pay-page .pay-search .form-control{padding-left:1.9rem}
.pay-page .pay-date-wrap{position:relative}
.pay-page .pay-date-wrap>i{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#64748b;font-size:.8rem;z-index:2;pointer-events:none}
.pay-page .pay-date-wrap .form-control,.pay-page .pay-date-wrap .flatpickr-input{padding-left:1.9rem;cursor:pointer}
.pay-page .pay-filter-actions{display:flex;gap:6px;align-items:end;justify-content:flex-end}
.pay-page .pay-filter-actions .pd-btn{height:34px;background:linear-gradient(180deg,#00a88e 0%,#009B80 100%)}
.pay-page .pay-filter-actions .pd-btn-ghost{height:34px;border:1.5px solid #009B80;color:#009B80}

.pay-page .pay-panel{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.04),0 6px 14px rgba(148,163,184,.1)}
.pay-page .pay-panel-h{padding:.45rem .65rem;border-bottom:1px solid #eef2f7;display:flex;align-items:center;justify-content:space-between;gap:6px}
.pay-page .pay-panel-title{display:inline-flex;align-items:center;gap:7px;font-size:.78rem;font-weight:800;color:#0f172a;margin:0}
.pay-page .pay-hico{width:24px;height:24px;border-radius:7px;display:inline-flex;align-items:center;justify-content:center;background:#d1fae5;color:#059669;font-size:.75rem}
.pay-page .pay-panel-b{padding:.45rem .65rem .55rem}
.pay-page .pay-panel-link{font-size:.68rem;font-weight:700;color:#2563eb;text-decoration:none}
.pay-page .pay-snap-row{display:grid;grid-template-columns:1fr auto;gap:6px;padding:.1rem 0;border-bottom:1px solid #f1f5f9}
.pay-page .pay-snap-row:last-child{border-bottom:0}
.pay-page .pay-snap-k{font-size:.68rem;color:#64748b;font-weight:600}
.pay-page .pay-snap-v{font-size:.72rem;font-weight:750;color:#0f172a;text-align:right;max-width:160px;word-break:break-word}
.pay-page .pay-snap-v.mono{font-family:ui-monospace,Menlo,monospace;font-weight:800}
.pay-page .pay-snap-v.danger{color:#dc2626;font-weight:800}
.pay-page .pay-conf-row{display:flex;align-items:center;justify-content:space-between;gap:6px;padding:.18rem 0;border-bottom:1px solid #f1f5f9}
.pay-page .pay-conf-row:last-child{border-bottom:0}
.pay-page .pay-conf-left{display:flex;align-items:center;gap:7px;min-width:0}
.pay-page .pay-conf-left .lbl{font-size:.71rem;font-weight:700;color:#0f172a}
.pay-page .pay-conf-left .dot{width:16px;height:16px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:.58rem;background:#ffedd5;color:#c2410c}
.pay-page .pay-conf-left .dot.ok{background:#dcfce7;color:#15803d}
.pay-page .pay-badge{display:inline-flex;padding:.1rem .4rem;border-radius:999px;font-size:.6rem;font-weight:750}
.pay-page .pay-badge.ok{background:#dcfce7;color:#15803d}
.pay-page .pay-badge.warn{background:#ffedd5;color:#c2410c}
.pay-page .pay-rcpt-table{font-size:.68rem}
.pay-page .pay-rcpt-table th{font-size:.62rem;text-transform:uppercase;color:#64748b}
.pay-page .pay-act{margin:0;padding:0;list-style:none}
.pay-page .pay-act li{position:relative;padding:0 0 .5rem 12px;border-left:2px solid #d1fae5;font-size:.69rem}
.pay-page .pay-act li::before{content:'';position:absolute;left:-4px;top:3px;width:6px;height:6px;border-radius:50%;background:#10b981}
.pay-page .pay-act .t{font-weight:650;color:#0f172a}
.pay-page .pay-act .m{color:#94a3b8;font-size:.64rem}
.pay-page .pay-side-actions{display:flex;flex-direction:column;gap:8px}
.pay-page .pay-view-case{
    height:32px;padding:0 10px;border-radius:8px;border:1.5px solid #3b82f6;background:#fff;color:#2563eb;
    font-size:.72rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:4px;width:100%
}
.pay-page .pay-release-form{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:.65rem}
.pay-page .pay-release-check{display:flex;align-items:flex-start;gap:8px;font-size:.7rem;font-weight:650;color:#334155;margin-bottom:.5rem}

.pay-page .pay-qcard .pd-card-head{padding:.85rem 1.1rem;border-bottom:1px solid #eef2f7}
.pay-page .pay-qhead{display:flex;align-items:center;gap:12px}
.pay-page .pay-qico{width:36px;height:36px;border-radius:10px;background:#d1fae5;color:#059669;display:inline-flex;align-items:center;justify-content:center}
.pay-page .pay-qtitle{margin:0;font-size:.98rem;font-weight:800;color:#0f172a}
.pay-page .pay-qcount{font-size:.72rem;font-weight:650;color:#64748b}
.pay-page .pay-qtable thead th{font-size:.68rem;font-weight:750;padding:.55rem .5rem;background:#eef4fb;color:#1e3a5f;white-space:nowrap}
.pay-page .pay-qtable tbody td{padding:.45rem .5rem;font-size:.76rem;white-space:nowrap;border-bottom:1px solid #eef2f7}
.pay-page .pay-qtable tbody tr:hover td{background:#f8fafc}
.pay-page .pay-qtable tbody tr.pay-row-active td{background:#ecfdf5!important}
.pay-page .pay-col-num{width:36px;text-align:center;color:#64748b}
.pay-page .pay-amt{font-weight:750;font-variant-numeric:tabular-nums}
.pay-page .pay-status{display:inline-flex;padding:.18rem .5rem;border-radius:999px;font-size:.64rem;font-weight:750;white-space:nowrap;background:#f1f5f9;color:#475569}
.pay-page .pay-status.ok{background:#dcfce7;color:#15803d}
.pay-page .pay-status.warn{background:#ffedd5;color:#c2410c}
.pay-page .pay-status.bad{background:#fee2e2;color:#b91c1c}
.pay-page .pay-status.info{background:#e0f2fe;color:#0369a1}
.pay-page .pay-status.eligible{background:#d1fae5;color:#047857}
.pay-page .pay-status.processing{background:#ede9fe;color:#6d28d9}
.pay-page .pay-qacts{display:inline-flex;gap:5px;flex-wrap:nowrap}
.pay-page .pay-abtn{
    height:28px;padding:0 8px;border-radius:7px;font-size:.68rem;font-weight:700;border:1.5px solid #dbe3f0;background:#fff;color:#334155;
    display:inline-flex;align-items:center;gap:4px;text-decoration:none;white-space:nowrap
}
.pay-page .pay-abtn.view{border-color:#93c5fd;color:#2563eb}
.pay-page .pay-abtn.process{border-color:#6ee7b7;color:#047857}
.pay-page .pay-qfoot{display:flex;flex-wrap:wrap;justify-content:space-between;gap:10px;padding:.7rem 1rem;border-top:1px solid #e2e8f0;background:#fafbfc;font-size:.76rem;color:#64748b}

@media (max-width:1399.98px){.pay-page .pay-kpi-row{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media (max-width:1199.98px){
    .pay-page .pay-layout{grid-template-columns:1fr}
    .pay-page .pay-side{position:static;display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .pay-page .pay-filters-row.top,.pay-page .pay-filters-row.mid{grid-template-columns:1fr 1fr}
    .pay-page .pay-kpi-row{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media (max-width:991.98px){
    .pay-page .pay-tab .full,.pay-page .pay-kpi-label .full,.pay-page .pay-step-label .full,.pay-page .pay-step-sub .full{display:none}
    .pay-page .pay-tab .short,.pay-page .pay-kpi-label .short,.pay-page .pay-step-label .short,.pay-page .pay-step-sub .short{display:inline}
    .pay-page .pd-hero{flex-direction:column;align-items:stretch}
    .pay-page .pd-hero-actions{width:100%;display:grid;grid-template-columns:1fr 1fr;gap:8px}
}
@media (max-width:767.98px){
    .pay-page.container-fluid{padding-left:.85rem!important;padding-right:.85rem!important}
    .pay-page .pay-side{grid-template-columns:1fr}
    .pay-page .pay-filters-row.top,.pay-page .pay-filters-row.mid,.pay-page .pay-filters-row.bottom{grid-template-columns:1fr}
    .pay-page .pay-filter-actions{width:100%}
    .pay-page .pay-filter-actions .pd-btn,.pay-page .pay-filter-actions .pd-btn-ghost{flex:1}
    .pay-page .pay-kpi-row{gap:8px}
    .pay-page a.pay-kpi{padding:.65rem .7rem;min-height:0;gap:8px;align-items:center}
    .pay-page .pay-kpi-ico{width:32px;height:32px;font-size:.9rem}
    .pay-page .pay-kpi-value{font-size:1.15rem}
    .pay-page .pay-kpi-amt{font-size:.66rem}
    .pay-page .pay-kpi-pct{display:none}
    .pay-page .pay-step{min-width:112px;flex:0 0 auto}
}
@media (max-width:479.98px){
    .pay-page a.pay-kpi{padding:.55rem .55rem;gap:6px}
    .pay-page .pay-kpi-ico{width:28px;height:28px;font-size:.8rem}
    .pay-page .pay-kpi-label{font-size:.62rem}
    .pay-page .pay-kpi-value{font-size:1.05rem}
    .pay-page .pay-kpi-amt{font-size:.6rem}
}
</style>
@endpush

@section('content')
@php
    $qs = fn (array $extra = []) => array_filter(array_merge(request()->except(['page', 'partial']), $extra), fn ($v) => $v !== null && $v !== '');
@endphp
<div class="container-fluid p-4 pd-page pay-page"
     data-pay-index-url="{{ route('goal.payouts.index') }}"
     data-pay-export-url="{{ route('goal.payouts.export') }}"
     data-pay-receipt-base="{{ url('/goal/payouts') }}">
    <div class="pd-hero">
        <div class="pd-hero-brand">
            <span class="pay-hero-ico" aria-hidden="true"><i class="bi bi-wallet2"></i></span>
            <div>
                <ul class="pd-crumbs">
                    <li><a href="{{ url('/') }}">Home</a></li>
                    <li class="sep">›</li>
                    <li>Post-Disbursement</li>
                    <li class="sep">›</li>
                    <li>Payout Management</li>
                </ul>
                <h1 class="pd-title">Payout Management</h1>
                <p class="pd-sub">Monitor payout eligibility, process receipts and reconcile outstanding amounts.</p>
            </div>
        </div>
        <div class="pd-hero-actions">
            <div class="dropdown">
                <button class="pd-link-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download"></i> Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" id="payExportLink" href="{{ route('goal.payouts.export', $qs()) }}">CSV</a></li>
                </ul>
            </div>
            <button type="button" class="pd-link-btn" data-bs-toggle="modal" data-bs-target="#payGuideModal">
                <i class="bi bi-info-circle"></i> Payout Guide
            </button>
        </div>
    </div>

    @if(!empty($missingOrgScope))
        <div class="alert alert-warning py-2 d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
            <div><strong>Organization unit not assigned.</strong> Payout board is empty until a branch/region is linked to your user.</div>
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
    @endif

    <div id="payResults" class="pay-results" aria-live="polite">
        @include('goal.payouts.partials.board_results')
    </div>
</div>

@include('goal.payouts.partials.receipt_modal', ['receiptAction' => ''])

<div class="modal fade" id="payGuideModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px">
            <div class="modal-header">
                <h5 class="modal-title">Payout Guide</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size:.9rem;color:#334155;line-height:1.5">
                <p class="mb-2" style="font-size:.72rem;font-weight:800;color:#0f766e;text-transform:uppercase;letter-spacing:.04em">Payout Process (Post-Disbursement)</p>
                <ol class="mb-0 ps-3">
                    <li>On Hold until PD-01, report number, PMS and Accounts approvals complete.</li>
                    <li>Approver releases hold → status becomes Eligible for receipt processing.</li>
                    <li>Record partial or full receipts; outstanding recalculates automatically.</li>
                    <li>Matched payouts move to Paid; unresolved items can be marked Exception.</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const page = document.querySelector('.pay-page');
    const results = document.getElementById('payResults');
    if (!page || !results) return;

    const indexUrl = page.getAttribute('data-pay-index-url') || '/goal/payouts';
    const exportUrl = page.getAttribute('data-pay-export-url') || '/goal/payouts/export';
    const receiptBase = page.getAttribute('data-pay-receipt-base') || '/goal/payouts';
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
        const rangeEl = document.getElementById('payDateRange');
        const fromEl = document.getElementById('payDateFrom');
        const toEl = document.getElementById('payDateTo');
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

    const receiptModal = document.getElementById('payReceiptModal');
    const receiptForm = document.getElementById('payReceiptForm');
    if (receiptModal && receiptForm) {
        receiptModal.addEventListener('show.bs.modal', function (ev) {
            const btn = ev.relatedTarget;
            if (!btn) return;
            const id = btn.getAttribute('data-pay-id');
            if (!id) return;
            const base = receiptBase.replace(/\/$/, '');
            receiptForm.action = base + '/' + encodeURIComponent(id) + '/receipt';
            const caseEl = document.getElementById('payRcCase');
            const expEl = document.getElementById('payRcExpected');
            const recEl = document.getElementById('payRcReceived');
            if (caseEl) caseEl.value = btn.getAttribute('data-case') || '';
            if (expEl) expEl.value = btn.getAttribute('data-expected') || '';
            if (recEl) recEl.value = btn.getAttribute('data-received') || '';
        });
    }

    function bindResults() {
        initDateRange();
        initSelect2(results);
        syncExport(currentParams());
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
        const link = document.getElementById('payExportLink');
        if (!link) return;
        const qs = new URLSearchParams(params);
        qs.delete('partial'); qs.delete('page'); qs.delete('selected');
        link.href = exportUrl + '?' + qs.toString();
    }

    function currentParams() {
        const form = document.getElementById('payFilters');
        if (form) return new URLSearchParams(new FormData(form));
        return new URLSearchParams(window.location.search);
    }

    function loadBoard(params) {
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
            results.innerHTML = '<div class="alert alert-warning mb-0">Unable to refresh Payout board. Please try again.</div>';
        }).finally(function () { setLoading(false); });
    }

    function paramsFromUrl(href) {
        try { return new URL(href, window.location.origin).searchParams; }
        catch (e) { return new URLSearchParams(); }
    }

    results.addEventListener('click', function (e) {
        if (e.target.closest('a.pay-abtn.view, [data-bs-target="#payReceiptModal"]')) return;

        const nav = e.target.closest('[data-pay-nav]');
        if (nav && results.contains(nav)) {
            e.preventDefault();
            const params = paramsFromUrl(nav.getAttribute('href'));
            if (nav.hasAttribute('data-tab')) {
                const form = document.getElementById('payFilters');
                const merged = form ? new URLSearchParams(new FormData(form)) : new URLSearchParams();
                merged.set('tab', nav.getAttribute('data-tab') || 'all');
                merged.delete('page');
                if (!nav.closest('.pay-kpi-row, .pay-tabs')) merged.delete('selected');
                loadBoard(merged);
                return;
            }
            loadBoard(params);
            return;
        }

        const row = e.target.closest('tr[data-pay-select-url]');
        if (row && results.contains(row) && !e.target.closest('a, button, input, label')) {
            e.preventDefault();
            loadBoard(paramsFromUrl(row.getAttribute('data-pay-select-url')));
            return;
        }

        const pageLink = e.target.closest('.pay-pager a, .pagination a');
        if (pageLink && results.contains(pageLink)) {
            e.preventDefault();
            loadBoard(paramsFromUrl(pageLink.getAttribute('href')));
            return;
        }

        const resetBtn = e.target.closest('#payResetBtn');
        if (resetBtn && results.contains(resetBtn)) {
            e.preventDefault();
            const tab = (document.getElementById('payTabInput') || {}).value || 'all';
            loadBoard(new URLSearchParams({ tab: tab }));
        }
    });

    results.addEventListener('submit', function (e) {
        const form = e.target.closest('#payFilters');
        if (!form || !results.contains(form)) return;
        e.preventDefault();
        const params = new URLSearchParams(new FormData(form));
        params.delete('page');
        loadBoard(params);
    });

    bindResults();
})();
</script>
@endpush