@extends('layouts.app')

@push('styles')
@include('goal.partials.pd_hub_styles')
<style>
.acc-show{
    --acc:#009B80;--acc-dark:#007F68;--acc-soft:#ecfdf5;--border:#e2e8f0;--muted:#64748b;--text:#0f172a;
    background:#F5F8FC;min-height:100%;padding-bottom:2rem
}
.acc-show .pd-hero{margin-bottom:1rem;align-items:center}
.acc-show .pd-hero-brand{display:flex;align-items:flex-start;gap:14px;min-width:0}
.acc-show .acc-hero-ico{
    width:48px;height:48px;border-radius:14px;flex:0 0 auto;margin-top:2px;
    display:inline-flex;align-items:center;justify-content:center;
    background:linear-gradient(160deg,#10b981 0%,#059669 100%);color:#fff;
    box-shadow:0 8px 18px rgba(5,150,105,.28);font-size:1.25rem
}
.acc-show .pd-title{font-size:1.45rem;color:var(--text)}
.acc-show .pd-sub{display:flex;flex-wrap:wrap;align-items:center;gap:8px;max-width:48rem}
.acc-show .pd-hero-actions .pd-link-btn{border-radius:10px;height:40px;padding:0 16px}
.acc-show .pd-hero-actions .pd-link-btn.primary{background:var(--acc);border-color:var(--acc);color:#fff}
.acc-show .pd-hero-actions .pd-link-btn.primary:hover{background:var(--acc-dark);color:#fff}

/* Jump tabs */
.acc-show .acc-jump{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px}
.acc-show .acc-jump a{
    display:inline-flex;align-items:center;gap:6px;height:34px;padding:0 14px;border-radius:999px;
    border:1px solid #dbe3f0;background:#fff;color:#334155;font-size:.78rem;font-weight:700;text-decoration:none;
    box-shadow:0 1px 2px rgba(15,23,42,.04)
}
.acc-show .acc-jump a:hover{border-color:#99f6e4;background:var(--acc-soft);color:var(--acc-dark)}
.acc-show .acc-jump a i{font-size:.9rem;color:var(--acc)}

/* Layout */
.acc-show .acc-grid{display:grid;grid-template-columns:minmax(0,1.7fr) minmax(300px,.85fr);gap:14px;align-items:start}
.acc-show .acc-stack{display:flex;flex-direction:column;gap:12px;min-width:0}
.acc-show .acc-side{position:sticky;top:76px;display:flex;flex-direction:column;gap:12px}

/* Section cards */
.acc-show .sec-card{
    background:#fff;border:1px solid var(--border);border-radius:16px;overflow:hidden;
    box-shadow:0 1px 2px rgba(15,23,42,.04),0 10px 24px rgba(148,163,184,.1)
}
.acc-show .sec-head{
    display:flex;align-items:center;justify-content:space-between;gap:10px;
    padding:.9rem 1.05rem;border-bottom:1px solid #eef2f7;
    background:linear-gradient(180deg,#fbfdff 0%,#fff 100%)
}
.acc-show .sec-head-left{display:flex;align-items:center;gap:12px;min-width:0}
.acc-show .sec-ico{
    width:40px;height:40px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;
    font-size:1.05rem;flex:0 0 auto
}
.acc-show .sec-ico.ico-blue{background:#dbeafe;color:#1d4ed8}
.acc-show .sec-ico.ico-teal{background:#d1fae5;color:#059669}
.acc-show .sec-ico.ico-amber{background:#ffedd5;color:#c2410c}
.acc-show .sec-ico.ico-purple{background:#ede9fe;color:#7c3aed}
.acc-show .sec-ico.ico-rose{background:#ffe4e6;color:#e11d48}
.acc-show .sec-ico.ico-sky{background:#e0f2fe;color:#0369a1}
.acc-show .sec-ico.ico-slate{background:#e2e8f0;color:#475569}
.acc-show .sec-ico.ico-emerald{background:#ecfdf5;color:#047857}
.acc-show .sec-head h5{margin:0;font-size:.96rem;font-weight:800;color:var(--text);line-height:1.2}
.acc-show .sec-head p{margin:.15rem 0 0;font-size:.72rem;color:var(--muted);line-height:1.3}
.acc-show .sec-body{padding:1.05rem 1.1rem}
.acc-show .sec-badge{
    display:inline-flex;align-items:center;gap:5px;padding:.28rem .7rem;border-radius:999px;
    font-size:.68rem;font-weight:750;background:#f1f5f9;color:#475569;white-space:nowrap
}

/* Notes / empty */
.acc-show .sec-note{
    display:flex;align-items:flex-start;gap:8px;padding:.7rem .85rem;border-radius:12px;
    background:#f8fafc;border:1px solid #eef2f7;font-size:.8rem;color:#475569;margin-bottom:1rem
}
.acc-show .sec-note i{color:#0ea5e9;margin-top:1px;flex:0 0 auto}
.acc-show .sec-empty{
    display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;text-align:center;
    padding:1.35rem 1rem;border-radius:12px;border:1.5px dashed #dbe3f0;background:#fafbfc;color:#94a3b8
}
.acc-show .sec-empty i{font-size:1.55rem;opacity:.55}
.acc-show .sec-empty p{margin:0;font-size:.8rem;font-weight:650;color:#64748b;max-width:26rem;line-height:1.4}
.acc-show .sec-warn{
    display:flex;align-items:flex-start;gap:8px;padding:.7rem .85rem;border-radius:12px;
    background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;font-size:.8rem;font-weight:650;line-height:1.4
}
.acc-show .sec-warn i{color:#ea580c;margin-top:1px;flex:0 0 auto}

/* Metric tiles */
.acc-show .metric-row{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:12px}
.acc-show .metric-row.cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}
.acc-show .metric-row.cols-4{grid-template-columns:repeat(4,minmax(0,1fr))}
.acc-show .metric-tile{
    position:relative;overflow:hidden;border:1px solid #e8edf5;border-radius:14px;padding:.85rem .95rem;
    background:#fff
}
.acc-show .metric-tile::after{
    content:'';position:absolute;right:-16px;bottom:-22px;width:72px;height:72px;border-radius:50%;
    background:radial-gradient(circle at 30% 30%, color-mix(in srgb, var(--mt) 22%, transparent), transparent 70%);
    pointer-events:none
}
.acc-show .metric-tile.mt-green{--mt:#22c55e;background:linear-gradient(160deg,#fff 55%,#f0fdf4)}
.acc-show .metric-tile.mt-purple{--mt:#8b5cf6;background:linear-gradient(160deg,#fff 55%,#f5f3ff)}
.acc-show .metric-tile.mt-blue{--mt:#3b82f6;background:linear-gradient(160deg,#fff 55%,#eff6ff)}
.acc-show .metric-tile.mt-amber{--mt:#f59e0b;background:linear-gradient(160deg,#fff 55%,#fffbeb)}
.acc-show .metric-tile.mt-rose{--mt:#f43f5e;background:linear-gradient(160deg,#fff 55%,#fff1f2)}
.acc-show .metric-label{margin:0;font-size:.66rem;font-weight:750;text-transform:uppercase;letter-spacing:.03em;color:var(--muted)}
.acc-show .metric-value{margin:.3rem 0 0;font-size:1.2rem;font-weight:800;color:var(--text);letter-spacing:-.02em;line-height:1.15}
.acc-show .metric-tile.mt-green .metric-value{color:#15803d}
.acc-show .metric-tile.mt-purple .metric-value{color:#6d28d9}
.acc-show .metric-tile.mt-rose .metric-value{color:#be123c}

/* KV grid */
.acc-show .kv-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px 18px;margin:0}
.acc-show .kv-grid.cols-3{grid-template-columns:repeat(3,minmax(0,1fr))}
.acc-show .kv-item{min-width:0}
.acc-show .kv-item.span-2{grid-column:span 2}
.acc-show .kv-item.span-3{grid-column:1 / -1}
.acc-show .kv-label{
    display:flex;align-items:center;gap:5px;font-size:.66rem;font-weight:700;text-transform:uppercase;
    letter-spacing:.03em;color:var(--muted);margin:0 0 4px
}
.acc-show .kv-label i{font-size:.75rem;opacity:.85}
.acc-show .kv-value{margin:0;font-size:.88rem;font-weight:700;color:var(--text);line-height:1.35;word-break:break-word}
.acc-show .kv-value.muted{color:#94a3b8;font-weight:600}
.acc-show .kv-value.amt{color:#15803d;font-weight:800;font-size:.95rem}
.acc-show .kv-value.amt-dark{color:var(--text);font-weight:800;font-size:.95rem}

/* Forms */
.acc-show .form-label{font-size:.78rem;font-weight:700;color:#334155;margin-bottom:.3rem;text-transform:none;letter-spacing:0}
.acc-show .form-label .req{color:#dc2626;margin-left:2px}
.acc-show .form-control,.acc-show .form-select{
    border-radius:10px;border:1.5px solid #dbe3f0;font-size:.84rem;min-height:40px;box-shadow:none
}
.acc-show .form-control:focus,.acc-show .form-select:focus{
    border-color:#5eead4;box-shadow:0 0 0 3px rgba(0,155,128,.12)
}
.acc-show textarea.form-control{min-height:78px}
.acc-show .btn-acc{
    background:linear-gradient(180deg,#00a88e 0%,#009B80 100%);border:0;color:#fff;font-weight:750;
    border-radius:10px;height:40px;padding:0 18px;box-shadow:0 6px 14px rgba(0,155,128,.22)
}
.acc-show .btn-acc:hover{background:linear-gradient(180deg,#009B80 0%,#007F68 100%);color:#fff}
.acc-show .btn-acc:disabled{opacity:.45;box-shadow:none}
.acc-show .btn-ghost{
    border:1.5px solid #dbe3f0;background:#fff;color:#334155;font-weight:750;border-radius:10px;height:40px;padding:0 16px
}
.acc-show .btn-ghost:hover{background:#f8fafc;border-color:#cbd5e1;color:var(--text)}
.acc-show .form-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:.25rem}
.acc-show .file-hint{font-size:.72rem;color:var(--muted);margin-top:.35rem}

/* Receipt table */
.acc-show .receipt-table{width:100%;font-size:.8rem;margin-bottom:0}
.acc-show .receipt-table th{
    font-size:.66rem;text-transform:uppercase;letter-spacing:.03em;color:var(--muted);font-weight:750;
    border-bottom:1px solid #eef2f7;padding:.55rem .5rem;background:#f8fafc
}
.acc-show .receipt-table td{padding:.55rem .5rem;border-bottom:1px solid #f1f5f9;color:var(--text);font-weight:650}
.acc-show .receipt-wrap{border:1px solid #eef2f7;border-radius:12px;overflow:hidden;margin-bottom:1rem}

/* Confirmations */
.acc-show .conf-list{display:flex;flex-direction:column;gap:8px}
.acc-show .conf-item{
    display:flex;align-items:flex-start;gap:10px;padding:.7rem .8rem;border-radius:12px;
    border:1px solid #eef2f7;background:#fafbfc
}
.acc-show .conf-item.is-done{background:#f0fdf4;border-color:#bbf7d0}
.acc-show .conf-ico{
    width:28px;height:28px;border-radius:999px;flex:0 0 auto;
    display:inline-flex;align-items:center;justify-content:center;font-size:.95rem
}
.acc-show .conf-item.is-done .conf-ico{background:#dcfce7;color:#15803d}
.acc-show .conf-item:not(.is-done) .conf-ico{background:#e2e8f0;color:#94a3b8}
.acc-show .conf-label{margin:0;font-size:.82rem;font-weight:750;color:var(--text);line-height:1.25}
.acc-show .conf-detail{margin:.15rem 0 0;font-size:.7rem;color:var(--muted);line-height:1.3}
.acc-show .conf-pill{
    margin-left:auto;flex:0 0 auto;align-self:center;font-size:.62rem;font-weight:750;
    padding:.2rem .55rem;border-radius:999px
}
.acc-show .conf-item.is-done .conf-pill{background:#dcfce7;color:#15803d}
.acc-show .conf-item:not(.is-done) .conf-pill{background:#ffedd5;color:#c2410c}

/* Workflow timeline */
.acc-show .wf-list{display:flex;flex-direction:column;gap:0;margin:0;padding:0;list-style:none}
.acc-show .wf-item{display:flex;gap:12px;position:relative;padding-bottom:14px}
.acc-show .wf-item:last-child{padding-bottom:0}
.acc-show .wf-item:not(:last-child)::before{
    content:'';position:absolute;left:13px;top:30px;bottom:0;width:2px;background:#e2e8f0
}
.acc-show .wf-dot{
    width:28px;height:28px;border-radius:999px;flex:0 0 auto;z-index:1;
    display:inline-flex;align-items:center;justify-content:center;font-size:.75rem;
    border:2px solid #e2e8f0;background:#fff;color:#94a3b8
}
.acc-show .wf-item.is-done .wf-dot{background:#dcfce7;border-color:#86efac;color:#15803d}
.acc-show .wf-item.is-current .wf-dot{background:#ffedd5;border-color:#fdba74;color:#c2410c}
.acc-show .wf-body{min-width:0;padding-top:2px;flex:1}
.acc-show .wf-label{margin:0;font-size:.8rem;font-weight:750;color:var(--text);line-height:1.25}
.acc-show .wf-detail{margin:.15rem 0 0;font-size:.7rem;color:var(--muted);line-height:1.3}
.acc-show .wf-badge{margin-top:5px;display:inline-flex}

/* Activity */
.acc-show .act-list{display:flex;flex-direction:column;gap:0}
.acc-show .act-item{
    display:flex;gap:10px;padding:.65rem 0;border-bottom:1px solid #f1f5f9
}
.acc-show .act-item:last-child{border-bottom:0;padding-bottom:0}
.acc-show .act-item:first-child{padding-top:0}
.acc-show .act-dot{
    width:8px;height:8px;border-radius:999px;background:var(--acc);margin-top:6px;flex:0 0 auto;
    box-shadow:0 0 0 3px #d1fae5
}
.acc-show .act-title{margin:0;font-size:.78rem;font-weight:700;color:var(--text);line-height:1.3}
.acc-show .act-meta{margin:.15rem 0 0;font-size:.7rem;color:var(--muted)}

@media (max-width:1199.98px){
    .acc-show .metric-row,
    .acc-show .metric-row.cols-4{grid-template-columns:repeat(2,minmax(0,1fr))}
    .acc-show .kv-grid.cols-3{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media (max-width:991.98px){
    .acc-show .acc-grid{grid-template-columns:1fr}
    .acc-show .acc-side{position:static}
    .acc-show .pd-hero{flex-direction:column;align-items:stretch}
    .acc-show .pd-hero-actions{width:100%;display:grid;grid-template-columns:1fr 1fr;gap:8px}
    .acc-show .pd-hero-actions .pd-link-btn{justify-content:center;width:100%}
}
@media (max-width:767.98px){
    .acc-show.container-fluid{padding-left:.85rem!important;padding-right:.85rem!important}
    .acc-show .metric-row,
    .acc-show .metric-row.cols-2,
    .acc-show .metric-row.cols-4,
    .acc-show .kv-grid,
    .acc-show .kv-grid.cols-3{grid-template-columns:1fr}
    .acc-show .kv-item.span-2{grid-column:auto}
    .acc-show .acc-jump{flex-wrap:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none;padding-bottom:4px}
    .acc-show .acc-jump::-webkit-scrollbar{display:none}
    .acc-show .acc-jump a{flex:0 0 auto}
}
</style>
@endpush

@section('content')
@php
    $case = $item->loanCase;
    $disb = $item->disbursement;
    $payout = $item->payout;
    $inr = fn ($n) => '₹ '.number_format((float) $n, 2);
    $fmtPill = function (?string $status) {
        $s = (string) $status;
        return match (true) {
            in_array($s, ['Paid', 'Matched', 'Approved', 'Eligible', 'Verified'], true) => 'ok',
            in_array($s, ['On Hold', 'Pending', 'Awaiting Report', 'Report Entered', 'Partial', 'Processing'], true) => 'warn',
            in_array($s, ['Exception', 'Rejected'], true) => 'bad',
            default => 'info',
        };
    };
    $wfDone = function (array $ev) {
        $b = strtolower((string) ($ev['badge'] ?? ''));
        return in_array($b, ['verified', 'done', 'complete', 'completed', 'captured', 'eligible', 'paid'], true)
            || (!empty($ev['done']));
    };
@endphp
<div class="container-fluid p-4 acc-show pd-page">
    <div class="pd-hero">
        <div class="pd-hero-brand">
            <span class="acc-hero-ico"><i class="bi bi-wallet2"></i></span>
            <div>
                <ul class="pd-crumbs">
                    <li><a href="{{ url('/') }}">Home</a></li>
                    <li class="sep">›</li>
                    <li><a href="{{ route('goal.accounts.index') }}">Accounts Queue</a></li>
                    <li class="sep">›</li>
                    <li>{{ $case?->case_no ?: 'Process' }}</li>
                </ul>
                <h1 class="pd-title">Accounts Case Processing</h1>
                <p class="pd-sub">
                    <span>{{ $case?->customer_name ?: '—' }} · {{ $case?->product ?: '—' }}</span>
                    <span class="pd-pill {{ $fmtPill($item->status) }}">{{ $item->status }}</span>
                </p>
            </div>
        </div>
        <div class="pd-hero-actions">
            <a href="{{ route('goal.accounts.index', ['selected' => $item->id]) }}" class="pd-link-btn"><i class="bi bi-arrow-left"></i> Back to Queue</a>
            @if($case)
                <a href="{{ route('goal.cases.show', $case) }}" class="pd-link-btn primary"><i class="bi bi-folder2-open"></i> Loan Case</a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
    @endif

    <nav class="acc-jump" aria-label="Jump to section">
        <a href="#loan"><i class="bi bi-bank2"></i> Loan</a>
        <a href="#report"><i class="bi bi-file-earmark-text"></i> Report</a>
        <a href="#expected"><i class="bi bi-currency-rupee"></i> Expected</a>
        <a href="#receipts"><i class="bi bi-receipt"></i> Receipts</a>
        <a href="#recon"><i class="bi bi-calculator"></i> Reconciliation</a>
        <a href="#approval"><i class="bi bi-shield-check"></i> Approval</a>
    </nav>

    <div class="acc-grid">
        <div class="acc-stack">
            {{-- Section 1 --}}
            <section class="sec-card" id="loan">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-blue"><i class="bi bi-bank2"></i></span>
                        <div>
                            <h5>Loan & Disbursement Details</h5>
                            <p>Case context from verified disbursement (PD-01)</p>
                        </div>
                    </div>
                    <span class="sec-badge"><i class="bi bi-check2-circle"></i> PD-01</span>
                </div>
                <div class="sec-body">
                    <div class="metric-row cols-2">
                        <div class="metric-tile mt-green">
                            <p class="metric-label">Disbursed Amount</p>
                            <p class="metric-value">{{ $disb ? $inr($disb->amount) : '—' }}</p>
                        </div>
                        <div class="metric-tile mt-blue">
                            <p class="metric-label">Disbursement Date</p>
                            <p class="metric-value" style="font-size:1.05rem">{{ optional($disb?->disbursed_on)->format('d M Y') ?: '—' }}</p>
                        </div>
                    </div>
                    <div class="kv-grid cols-3">
                        <div class="kv-item">
                            <p class="kv-label"><i class="bi bi-hash"></i> Case Reference</p>
                            <p class="kv-value">{{ $case?->case_no ?: '—' }}</p>
                        </div>
                        <div class="kv-item">
                            <p class="kv-label"><i class="bi bi-person"></i> Customer Name</p>
                            <p class="kv-value">{{ $case?->customer_name ?: '—' }}</p>
                        </div>
                        <div class="kv-item">
                            <p class="kv-label"><i class="bi bi-box"></i> Product</p>
                            <p class="kv-value">{{ $case?->product ?: '—' }}</p>
                        </div>
                        <div class="kv-item">
                            <p class="kv-label"><i class="bi bi-geo-alt"></i> Branch</p>
                            <p class="kv-value {{ $case?->orgUnit?->name ? '' : 'muted' }}">{{ $case?->orgUnit?->name ?: '—' }}</p>
                        </div>
                        <div class="kv-item">
                            <p class="kv-label"><i class="bi bi-building"></i> Lender</p>
                            <p class="kv-value {{ $case?->lender_name ? '' : 'muted' }}">{{ $case?->lender_name ?: '—' }}</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Section 2 --}}
            <section class="sec-card" id="report">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-teal"><i class="bi bi-file-earmark-text"></i></span>
                        <div>
                            <h5>Report Number Entry</h5>
                            <p>Report number is mandatory for payout eligibility</p>
                        </div>
                    </div>
                    @if($item->hasReportNumber())
                        <span class="pd-pill ok">Captured</span>
                    @else
                        <span class="pd-pill warn">Required</span>
                    @endif
                </div>
                <div class="sec-body">
                    <form method="POST" action="{{ route('goal.accounts.report', $item) }}" enctype="multipart/form-data" class="row g-3">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label" for="report_number">Report Number <span class="req">*</span></label>
                            <input type="text" name="report_number" id="report_number" class="form-control" value="{{ old('report_number', $item->report_number) }}" required maxlength="100" placeholder="Enter lender report no.">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="report_date">Report Date</label>
                            <input type="text" name="report_date" id="report_date" class="form-control js-datepicker" value="{{ old('report_date', optional($item->report_date)->format('Y-m-d')) }}" placeholder="Select date">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="report_document">Supporting Document</label>
                            <input type="file" name="report_document" id="report_document" class="form-control">
                            @if($item->report_document_path)
                                <div class="file-hint"><i class="bi bi-paperclip"></i> Document on file</div>
                            @endif
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="remarks_report">Remarks</label>
                            <textarea name="remarks" id="remarks_report" class="form-control" rows="2" placeholder="Optional notes for this report entry">{{ old('remarks', $item->remarks) }}</textarea>
                        </div>
                        <div class="col-12 form-actions">
                            <button type="submit" class="btn btn-acc"><i class="bi bi-check2 me-1"></i> Save Report</button>
                        </div>
                    </form>
                </div>
            </section>

            {{-- Section 3 --}}
            <section class="sec-card" id="expected">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-purple"><i class="bi bi-cash-coin"></i></span>
                        <div>
                            <h5>Expected Payout</h5>
                            <p>From approved commercial terms (Branch MIS) with policy fallback</p>
                        </div>
                    </div>
                </div>
                <div class="sec-body">
                    <div class="metric-row">
                        <div class="metric-tile mt-purple">
                            <p class="metric-label">Expected Payout</p>
                            <p class="metric-value">{{ $inr($reconciliation['expected'] ?? 0) }}</p>
                        </div>
                        <div class="metric-tile mt-blue">
                            <p class="metric-label">Commercial %</p>
                            <p class="metric-value" style="font-size:1.05rem">{{ $commercial['payout_pct'] !== null ? $commercial['payout_pct'].'%' : '—' }}</p>
                        </div>
                        <div class="metric-tile mt-green">
                            <p class="metric-label">Commercial Amount</p>
                            <p class="metric-value" style="font-size:1.05rem">{{ $commercial['payout_amount'] !== null ? $inr($commercial['payout_amount']) : '—' }}</p>
                        </div>
                    </div>
                    <div class="kv-grid">
                        <div class="kv-item span-2">
                            <p class="kv-label"><i class="bi bi-chat-left-text"></i> Approved Exception / Remark</p>
                            <p class="kv-value {{ filled($commercial['exception_note'] ?? null) ? '' : 'muted' }}">{{ $commercial['exception_note'] ?: '—' }}</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Section 4 --}}
            <section class="sec-card" id="receipts">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-amber"><i class="bi bi-receipt"></i></span>
                        <div>
                            <h5>Receipt Entry</h5>
                            <p>Append-only. Never overwrites previous receipts.</p>
                        </div>
                    </div>
                    @if(($payout?->receipts ?? collect())->count())
                        <span class="sec-badge">{{ $payout->receipts->count() }} recorded</span>
                    @endif
                </div>
                <div class="sec-body">
                    @if(($payout?->receipts ?? collect())->count())
                        <div class="receipt-wrap">
                            <table class="receipt-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Amount</th>
                                        <th>Receipt Date</th>
                                        <th>Payment Reference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payout->receipts as $i => $r)
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td class="kv-value amt-dark">{{ $inr($r->amount) }}</td>
                                            <td>{{ optional($r->received_on)->format('d M Y') }}</td>
                                            <td>{{ $r->reference ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="sec-empty mb-3">
                            <i class="bi bi-inbox"></i>
                            <p>No receipts yet. Record receipts once payout is Eligible or Processing.</p>
                        </div>
                    @endif

                    @if($canReceipt && $payout && in_array($payout->status, ['Eligible', 'Processing'], true))
                        <form method="POST" action="{{ route('goal.accounts.receipts.store', $item) }}" class="row g-3">
                            @csrf
                            <div class="col-md-3">
                                <label class="form-label" for="amount">Received Amount <span class="req">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="form-control" required value="{{ old('amount') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="received_on">Receipt Date <span class="req">*</span></label>
                                <input type="text" name="received_on" id="received_on" class="form-control js-datepicker" required value="{{ old('received_on', now()->toDateString()) }}" placeholder="Select date">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="reference">Payment Reference</label>
                                <input type="text" name="reference" id="reference" class="form-control" maxlength="100" value="{{ old('reference') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="difference_reason">Difference Reason</label>
                                <input type="text" name="difference_reason" id="difference_reason" class="form-control" maxlength="255" value="{{ old('difference_reason') }}" placeholder="Required if partial">
                            </div>
                            <div class="col-12 form-actions">
                                <button type="submit" class="btn btn-acc"><i class="bi bi-plus-lg me-1"></i> Add Receipt</button>
                            </div>
                        </form>
                    @elseif(!$payout)
                        <div class="sec-warn"><i class="bi bi-exclamation-circle-fill"></i><div>No linked payout record.</div></div>
                    @elseif(!$canReceipt)
                        <div class="sec-warn"><i class="bi bi-lock-fill"></i><div>You do not have permission to record receipts.</div></div>
                    @else
                        <div class="sec-warn">
                            <i class="bi bi-hourglass-split"></i>
                            <div>Receipts unlock when payout is <strong>Eligible</strong> or <strong>Processing</strong> (current: {{ $payout->status }}).</div>
                        </div>
                    @endif
                </div>
            </section>

            {{-- Section 5 --}}
            <section class="sec-card" id="recon">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-sky"><i class="bi bi-calculator"></i></span>
                        <div>
                            <h5>Reconciliation</h5>
                            <p>Outstanding = Expected − Total Receipts − Approved Adjustments</p>
                        </div>
                    </div>
                    <span class="pd-pill {{ $fmtPill($reconciliation['status'] ?? null) }}">{{ $reconciliation['status'] ?: '—' }}</span>
                </div>
                <div class="sec-body">
                    <div class="metric-row cols-4">
                        <div class="metric-tile mt-blue">
                            <p class="metric-label">Expected</p>
                            <p class="metric-value" style="font-size:1.05rem">{{ $inr($reconciliation['expected'] ?? 0) }}</p>
                        </div>
                        <div class="metric-tile mt-green">
                            <p class="metric-label">Received</p>
                            <p class="metric-value" style="font-size:1.05rem">{{ $inr($reconciliation['received'] ?? 0) }}</p>
                        </div>
                        <div class="metric-tile mt-amber">
                            <p class="metric-label">Adjustments</p>
                            <p class="metric-value" style="font-size:1.05rem">{{ $inr($reconciliation['adjustments'] ?? 0) }}</p>
                        </div>
                        <div class="metric-tile mt-rose">
                            <p class="metric-label">Outstanding</p>
                            <p class="metric-value" style="font-size:1.05rem">{{ $inr($reconciliation['outstanding'] ?? 0) }}</p>
                        </div>
                    </div>
                    <div class="kv-grid cols-3">
                        <div class="kv-item">
                            <p class="kv-label">Reconciliation Status</p>
                            <p class="kv-value"><span class="pd-pill {{ $fmtPill($reconciliation['reconciliation'] ?? null) }}">{{ $reconciliation['reconciliation'] ?: '—' }}</span></p>
                        </div>
                        <div class="kv-item">
                            <p class="kv-label">Payout Status</p>
                            <p class="kv-value"><span class="pd-pill {{ $fmtPill($reconciliation['status'] ?? null) }}">{{ $reconciliation['status'] ?: '—' }}</span></p>
                        </div>
                        <div class="kv-item">
                            <p class="kv-label">Difference Reason</p>
                            <p class="kv-value {{ filled($reconciliation['difference_reason'] ?? null) ? '' : 'muted' }}">{{ $reconciliation['difference_reason'] ?: '—' }}</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Section 6 --}}
            <section class="sec-card" id="approval">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-rose"><i class="bi bi-shield-check"></i></span>
                        <div>
                            <h5>Approval</h5>
                            <p>Submit for verification, then approve (authorized users)</p>
                        </div>
                    </div>
                </div>
                <div class="sec-body">
                    <div class="kv-grid cols-3 mb-3">
                        <div class="kv-item">
                            <p class="kv-label"><i class="bi bi-person-badge"></i> Entered By</p>
                            <p class="kv-value {{ ($item->updater?->name ?: $item->submitter?->name) ? '' : 'muted' }}">{{ $item->updater?->name ?: ($item->submitter?->name ?: '—') }}</p>
                        </div>
                        <div class="kv-item">
                            <p class="kv-label"><i class="bi bi-person-check"></i> Approved By</p>
                            <p class="kv-value {{ $item->approver?->name ? '' : 'muted' }}">{{ $item->approver?->name ?: '—' }}</p>
                        </div>
                        <div class="kv-item">
                            <p class="kv-label"><i class="bi bi-calendar-check"></i> Approval Date</p>
                            <p class="kv-value {{ $item->approved_at ? '' : 'muted' }}">{{ optional($item->approved_at)->format('d M Y, h:i A') ?: '—' }}</p>
                        </div>
                        <div class="kv-item">
                            <p class="kv-label"><i class="bi bi-send"></i> Submitted At</p>
                            <p class="kv-value {{ $item->submitted_for_approval_at ? '' : 'muted' }}">{{ optional($item->submitted_for_approval_at)->format('d M Y, h:i A') ?: '—' }}</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('goal.accounts.submit', $item) }}" class="mb-3">
                        @csrf
                        <label class="form-label" for="remarks_submit">Remarks</label>
                        <textarea name="remarks" id="remarks_submit" class="form-control mb-2" rows="2" placeholder="Notes for verification / approval">{{ old('remarks', $item->remarks) }}</textarea>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-ghost"><i class="bi bi-send me-1"></i> Submit for Verification</button>
                        </div>
                    </form>

                    @if($canApprove)
                        <form method="POST" action="{{ route('goal.accounts.approve', $item) }}">
                            @csrf
                            <input type="hidden" name="remarks" id="remarks_approve" value="{{ old('remarks', $item->remarks) }}">
                            <div class="form-actions">
                                <button type="submit" class="btn btn-acc" @disabled(!$item->hasReportNumber())>
                                    <i class="bi bi-shield-check me-1"></i> Approve
                                </button>
                            </div>
                            @unless($item->hasReportNumber())
                                <div class="file-hint mt-2"><i class="bi bi-info-circle"></i> Report number required before approval.</div>
                            @endunless
                        </form>
                    @else
                        <div class="file-hint"><i class="bi bi-lock"></i> Approval requires Accounts Approver permission.</div>
                    @endif
                </div>
            </section>
        </div>

        <aside class="acc-side">
            <section class="sec-card">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-emerald"><i class="bi bi-list-check"></i></span>
                        <div>
                            <h5>Required Confirmations</h5>
                            <p>Eligibility gates</p>
                        </div>
                    </div>
                </div>
                <div class="sec-body">
                    <div class="conf-list">
                        @foreach($confirmations as $c)
                            @php $done = !empty($c['done']); @endphp
                            <div class="conf-item {{ $done ? 'is-done' : '' }}">
                                <span class="conf-ico">
                                    <i class="bi {{ $done ? 'bi-check-lg' : 'bi-circle' }}"></i>
                                </span>
                                <div style="min-width:0;flex:1">
                                    <p class="conf-label">{{ $c['label'] }}</p>
                                    @if(!empty($c['detail']))
                                        <p class="conf-detail">{{ $c['detail'] }}</p>
                                    @endif
                                </div>
                                <span class="conf-pill">{{ $done ? 'Done' : 'Pending' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="sec-card">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-blue"><i class="bi bi-diagram-3"></i></span>
                        <div>
                            <h5>PD-01 / Accounts Workflow</h5>
                            <p>Process chain</p>
                        </div>
                    </div>
                </div>
                <div class="sec-body">
                    <ul class="wf-list">
                        @foreach($workflow as $i => $ev)
                            @php
                                $done = $wfDone($ev);
                                $hasBadge = filled($ev['badge'] ?? null);
                                $isCurrent = !$done && $hasBadge;
                            @endphp
                            <li class="wf-item {{ $done ? 'is-done' : ($isCurrent ? 'is-current' : '') }}">
                                <span class="wf-dot">
                                    <i class="bi {{ $done ? 'bi-check-lg' : ($isCurrent ? 'bi-hourglass-split' : 'bi-circle') }}"></i>
                                </span>
                                <div class="wf-body">
                                    <p class="wf-label">{{ $ev['label'] }}</p>
                                    @if(!empty($ev['detail']))
                                        <p class="wf-detail">{{ $ev['detail'] }}</p>
                                    @endif
                                    @if($hasBadge)
                                        <span class="pd-pill {{ $fmtPill($ev['badge']) }} wf-badge">{{ $ev['badge'] }}</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>

            <section class="sec-card">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-slate"><i class="bi bi-clock-history"></i></span>
                        <div>
                            <h5>Recent Activity</h5>
                            <p>Audit trail</p>
                        </div>
                    </div>
                </div>
                <div class="sec-body">
                    <div class="act-list">
                        @forelse($activity as $row)
                            <div class="act-item">
                                <span class="act-dot"></span>
                                <div>
                                    <p class="act-title">{{ $row['title'] }}</p>
                                    <p class="act-meta">{{ $row['meta'] }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="sec-empty" style="padding:1rem">
                                <i class="bi bi-clock"></i>
                                <p>No activity yet.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('remarks_submit')?.addEventListener('input', function () {
    const approve = document.getElementById('remarks_approve');
    if (approve) approve.value = this.value;
});
</script>
@endpush
