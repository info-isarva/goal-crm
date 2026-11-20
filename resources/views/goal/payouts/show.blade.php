@extends('layouts.app')

@push('styles')
@include('goal.partials.pd_hub_styles')
<style>
.pay-show{
    --pay:#009B80;--pay-dark:#007F68;--pay-soft:#ecfdf5;--border:#e2e8f0;--muted:#64748b;--text:#0f172a;
    background:#F5F8FC;min-height:100%;padding-bottom:2rem
}
.pay-show .pd-hero{margin-bottom:1rem;align-items:center}
.pay-show .pd-hero-brand{display:flex;align-items:flex-start;gap:14px;min-width:0}
.pay-show .pay-hero-ico{
    width:48px;height:48px;border-radius:14px;flex:0 0 auto;margin-top:2px;
    display:inline-flex;align-items:center;justify-content:center;
    background:linear-gradient(160deg,#10b981 0%,#059669 100%);color:#fff;
    box-shadow:0 8px 18px rgba(5,150,105,.28);font-size:1.25rem
}
.pay-show .pd-title{font-size:1.45rem;color:var(--text)}
.pay-show .pd-sub{display:flex;flex-wrap:wrap;align-items:center;gap:8px;max-width:48rem}
.pay-show .pd-hero-actions .pd-link-btn{border-radius:10px;height:40px;padding:0 16px}
.pay-show .pd-hero-actions .pd-link-btn.primary{background:var(--pay);border-color:var(--pay);color:#fff}
.pay-show .pd-hero-actions .pd-link-btn.primary:hover{background:var(--pay-dark);color:#fff}

.pay-show .pay-jump{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px}
.pay-show .pay-jump a{
    display:inline-flex;align-items:center;gap:6px;height:34px;padding:0 14px;border-radius:999px;
    border:1px solid #dbe3f0;background:#fff;color:#334155;font-size:.78rem;font-weight:700;text-decoration:none;
    box-shadow:0 1px 2px rgba(15,23,42,.04)
}
.pay-show .pay-jump a:hover{border-color:#99f6e4;background:var(--pay-soft);color:var(--pay-dark)}
.pay-show .pay-jump a i{font-size:.9rem;color:var(--pay)}

.pay-show .pay-grid{display:grid;grid-template-columns:minmax(0,1.7fr) minmax(300px,.85fr);gap:14px;align-items:start}
.pay-show .pay-stack{display:flex;flex-direction:column;gap:12px;min-width:0}
.pay-show .pay-aside{position:sticky;top:76px;display:flex;flex-direction:column;gap:12px}

.pay-show .sec-card{
    background:#fff;border:1px solid var(--border);border-radius:16px;overflow:hidden;
    box-shadow:0 1px 2px rgba(15,23,42,.04),0 10px 24px rgba(148,163,184,.1)
}
.pay-show .sec-head{
    display:flex;align-items:center;justify-content:space-between;gap:10px;
    padding:.9rem 1.05rem;border-bottom:1px solid #eef2f7;
    background:linear-gradient(180deg,#fbfdff 0%,#fff 100%)
}
.pay-show .sec-head-left{display:flex;align-items:center;gap:12px;min-width:0}
.pay-show .sec-ico{
    width:40px;height:40px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;
    font-size:1.05rem;flex:0 0 auto
}
.pay-show .sec-ico.ico-blue{background:#dbeafe;color:#1d4ed8}
.pay-show .sec-ico.ico-teal{background:#d1fae5;color:#059669}
.pay-show .sec-ico.ico-amber{background:#ffedd5;color:#c2410c}
.pay-show .sec-ico.ico-purple{background:#ede9fe;color:#7c3aed}
.pay-show .sec-ico.ico-rose{background:#ffe4e6;color:#e11d48}
.pay-show .sec-ico.ico-sky{background:#e0f2fe;color:#0369a1}
.pay-show .sec-ico.ico-slate{background:#e2e8f0;color:#475569}
.pay-show .sec-ico.ico-emerald{background:#ecfdf5;color:#047857}
.pay-show .sec-head h5{margin:0;font-size:.96rem;font-weight:800;color:var(--text);line-height:1.2}
.pay-show .sec-head p{margin:.15rem 0 0;font-size:.72rem;color:var(--muted);line-height:1.3}
.pay-show .sec-body{padding:1.05rem 1.1rem}
.pay-show .sec-badge{
    display:inline-flex;align-items:center;gap:5px;padding:.28rem .7rem;border-radius:999px;
    font-size:.68rem;font-weight:750;background:#f1f5f9;color:#475569;white-space:nowrap
}

.pay-show .sec-empty{
    display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;text-align:center;
    padding:1.35rem 1rem;border-radius:12px;border:1.5px dashed #dbe3f0;background:#fafbfc;color:#94a3b8
}
.pay-show .sec-empty i{font-size:1.55rem;opacity:.55}
.pay-show .sec-empty p{margin:0;font-size:.8rem;font-weight:650;color:#64748b;max-width:26rem;line-height:1.4}
.pay-show .sec-warn{
    display:flex;align-items:flex-start;gap:8px;padding:.7rem .85rem;border-radius:12px;
    background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;font-size:.8rem;font-weight:650;line-height:1.4
}
.pay-show .sec-warn i{color:#ea580c;margin-top:1px;flex:0 0 auto}
.pay-show .sec-note{
    display:flex;align-items:flex-start;gap:8px;padding:.7rem .85rem;border-radius:12px;
    background:#f8fafc;border:1px solid #eef2f7;font-size:.8rem;color:#475569
}
.pay-show .sec-note i{color:#0ea5e9;margin-top:1px;flex:0 0 auto}

.pay-show .metric-row{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
.pay-show .metric-tile{
    position:relative;overflow:hidden;border:1px solid #e8edf5;border-radius:14px;padding:.85rem .95rem;background:#fff
}
.pay-show .metric-tile::after{
    content:'';position:absolute;right:-16px;bottom:-22px;width:72px;height:72px;border-radius:50%;
    background:radial-gradient(circle at 30% 30%, color-mix(in srgb, var(--mt) 22%, transparent), transparent 70%);
    pointer-events:none
}
.pay-show .metric-tile.mt-green{--mt:#22c55e;background:linear-gradient(160deg,#fff 55%,#f0fdf4)}
.pay-show .metric-tile.mt-blue{--mt:#3b82f6;background:linear-gradient(160deg,#fff 55%,#eff6ff)}
.pay-show .metric-tile.mt-rose{--mt:#f43f5e;background:linear-gradient(160deg,#fff 55%,#fff1f2)}
.pay-show .metric-tile.mt-purple{--mt:#8b5cf6;background:linear-gradient(160deg,#fff 55%,#f5f3ff)}
.pay-show .metric-label{margin:0;font-size:.66rem;font-weight:750;text-transform:uppercase;letter-spacing:.03em;color:var(--muted)}
.pay-show .metric-value{margin:.3rem 0 0;font-size:1.2rem;font-weight:800;color:var(--text);letter-spacing:-.02em;line-height:1.15}
.pay-show .metric-tile.mt-green .metric-value{color:#15803d}
.pay-show .metric-tile.mt-blue .metric-value{color:#1d4ed8}
.pay-show .metric-tile.mt-rose .metric-value{color:#be123c}
.pay-show .metric-tile.mt-purple .metric-value{color:#6d28d9}

.pay-show .kv-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px 18px;margin:0}
.pay-show .kv-item{min-width:0}
.pay-show .kv-label{
    display:flex;align-items:center;gap:5px;font-size:.66rem;font-weight:700;text-transform:uppercase;
    letter-spacing:.03em;color:var(--muted);margin:0 0 4px
}
.pay-show .kv-label i{font-size:.75rem;opacity:.85}
.pay-show .kv-value{margin:0;font-size:.88rem;font-weight:700;color:var(--text);line-height:1.35;word-break:break-word}
.pay-show .kv-value.muted{color:#94a3b8;font-weight:600}
.pay-show .kv-value.amt{color:#15803d;font-weight:800}

.pay-show .form-label{font-size:.78rem;font-weight:700;color:#334155;margin-bottom:.3rem}
.pay-show .form-control,.pay-show .form-select{
    border-radius:10px;border:1.5px solid #dbe3f0;font-size:.84rem;min-height:40px;box-shadow:none
}
.pay-show .form-control:focus{border-color:#5eead4;box-shadow:0 0 0 3px rgba(0,155,128,.12)}
.pay-show .btn-pay{
    background:linear-gradient(180deg,#00a88e 0%,#009B80 100%);border:0;color:#fff;font-weight:750;
    border-radius:10px;height:40px;padding:0 18px;box-shadow:0 6px 14px rgba(0,155,128,.22)
}
.pay-show .btn-pay:hover{background:linear-gradient(180deg,#009B80 0%,#007F68 100%);color:#fff}
.pay-show .btn-ghost{
    border:1.5px solid #dbe3f0;background:#fff;color:#334155;font-weight:750;border-radius:10px;height:40px;padding:0 16px
}
.pay-show .btn-ghost:hover{background:#f8fafc;border-color:#cbd5e1}
.pay-show .btn-danger-soft{
    background:#fff;border:1.5px solid #fecaca;color:#b91c1c;font-weight:750;border-radius:10px;height:40px;padding:0 16px
}
.pay-show .btn-danger-soft:hover{background:#fef2f2;border-color:#f87171;color:#991b1b}
.pay-show .form-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center}

.pay-show .receipt-wrap{border:1px solid #eef2f7;border-radius:12px;overflow:hidden}
.pay-show .receipt-table{width:100%;font-size:.8rem;margin:0}
.pay-show .receipt-table th{
    font-size:.66rem;text-transform:uppercase;letter-spacing:.03em;color:var(--muted);font-weight:750;
    border-bottom:1px solid #eef2f7;padding:.55rem .5rem;background:#f8fafc
}
.pay-show .receipt-table td{padding:.55rem .5rem;border-bottom:1px solid #f1f5f9;color:var(--text);font-weight:650}

.pay-show .conf-list{display:flex;flex-direction:column;gap:8px}
.pay-show .conf-item{
    display:flex;align-items:flex-start;gap:10px;padding:.7rem .8rem;border-radius:12px;
    border:1px solid #eef2f7;background:#fafbfc
}
.pay-show .conf-item.is-done{background:#f0fdf4;border-color:#bbf7d0}
.pay-show .conf-ico{
    width:28px;height:28px;border-radius:999px;flex:0 0 auto;
    display:inline-flex;align-items:center;justify-content:center;font-size:.95rem
}
.pay-show .conf-item.is-done .conf-ico{background:#dcfce7;color:#15803d}
.pay-show .conf-item:not(.is-done) .conf-ico{background:#e2e8f0;color:#94a3b8}
.pay-show .conf-label{margin:0;font-size:.82rem;font-weight:750;color:var(--text);line-height:1.25}
.pay-show .conf-detail{margin:.15rem 0 0;font-size:.7rem;color:var(--muted);line-height:1.3}
.pay-show .conf-pill{
    margin-left:auto;flex:0 0 auto;align-self:center;font-size:.62rem;font-weight:750;
    padding:.2rem .55rem;border-radius:999px
}
.pay-show .conf-item.is-done .conf-pill{background:#dcfce7;color:#15803d}
.pay-show .conf-item:not(.is-done) .conf-pill{background:#ffedd5;color:#c2410c}

.pay-show .act-list{display:flex;flex-direction:column;gap:0}
.pay-show .act-item{display:flex;gap:10px;padding:.65rem 0;border-bottom:1px solid #f1f5f9}
.pay-show .act-item:last-child{border-bottom:0;padding-bottom:0}
.pay-show .act-item:first-child{padding-top:0}
.pay-show .act-dot{
    width:8px;height:8px;border-radius:999px;background:var(--pay);margin-top:6px;flex:0 0 auto;
    box-shadow:0 0 0 3px #d1fae5
}
.pay-show .act-title{margin:0;font-size:.78rem;font-weight:700;color:var(--text);line-height:1.3}
.pay-show .act-meta{margin:.15rem 0 0;font-size:.7rem;color:var(--muted)}

.pay-show .pay-release-check{
    display:flex;align-items:flex-start;gap:8px;font-size:.78rem;font-weight:650;color:#334155;margin-bottom:.85rem;cursor:pointer
}
.pay-show .pay-release-check input{margin-top:3px;accent-color:var(--pay)}

@media (max-width:1199.98px){
    .pay-show .kv-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media (max-width:991.98px){
    .pay-show .pay-grid{grid-template-columns:1fr}
    .pay-show .pay-aside{position:static}
    .pay-show .pd-hero{flex-direction:column;align-items:stretch}
    .pay-show .pd-hero-actions{width:100%;display:grid;grid-template-columns:1fr 1fr;gap:8px}
    .pay-show .pd-hero-actions .pd-link-btn{justify-content:center;width:100%}
    .pay-show .metric-row{grid-template-columns:1fr}
}
@media (max-width:767.98px){
    .pay-show.container-fluid{padding-left:.85rem!important;padding-right:.85rem!important}
    .pay-show .kv-grid{grid-template-columns:1fr}
    .pay-show .pay-jump{flex-wrap:nowrap;overflow-x:auto;scrollbar-width:none;padding-bottom:4px}
    .pay-show .pay-jump::-webkit-scrollbar{display:none}
    .pay-show .pay-jump a{flex:0 0 auto}
}
</style>
@endpush

@section('content')
@php
    $case = $payout->loanCase;
    $disb = $payout->disbursement;
    $acc = $accountsItem;
    $inr = fn ($n) => '₹ '.number_format((float) $n, 0);
    $fmtPill = function (?string $status) {
        $s = (string) $status;
        return match (true) {
            in_array($s, ['Paid', 'Matched', 'Approved', 'Verified'], true) => 'ok',
            $s === 'Eligible' => 'eligible',
            $s === 'Processing' => 'processing',
            in_array($s, ['On Hold', 'Pending', 'Partial'], true) => 'warn',
            in_array($s, ['Exception', 'Rejected'], true) => 'bad',
            default => 'info',
        };
    };
    $canRecord = $canReceipt && in_array($payout->status, ['Eligible', 'Processing'], true);
    $gatesReady = collect($confirmations)->every(fn ($c) => ! empty($c['done']));
    $receiptCount = $payout->receipts->count();
@endphp
<div class="container-fluid p-4 pay-show pd-page" data-pay-receipt-base="{{ url('/goal/payouts') }}">
    <div class="pd-hero">
        <div class="pd-hero-brand">
            <span class="pay-hero-ico"><i class="bi bi-wallet2"></i></span>
            <div>
                <ul class="pd-crumbs">
                    <li><a href="{{ url('/') }}">Home</a></li>
                    <li class="sep">›</li>
                    <li><a href="{{ route('goal.payouts.index') }}">Payout Management</a></li>
                    <li class="sep">›</li>
                    <li>{{ $case?->case_no ?: 'Payout' }}</li>
                </ul>
                <h1 class="pd-title">Payout Workspace</h1>
                <p class="pd-sub">
                    <span>{{ $case?->customer_name ?: '—' }}@if($case?->product) · {{ $case->product }}@endif</span>
                    <span class="pd-pill {{ $fmtPill($payout->status) }}">{{ $payout->status }}</span>
                    @if($payout->reconciliation_status)
                        <span class="pd-pill {{ $fmtPill($payout->reconciliation_status) }}">{{ $payout->reconciliation_status }}</span>
                    @endif
                    @if($ageingDays !== null)
                        <span class="sec-badge"><i class="bi bi-hourglass-split"></i> {{ $ageingDays }}d ageing</span>
                    @endif
                </p>
            </div>
        </div>
        <div class="pd-hero-actions">
            <a href="{{ route('goal.payouts.index', ['selected' => $payout->id]) }}" class="pd-link-btn"><i class="bi bi-arrow-left"></i> Back to Board</a>
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

    <nav class="pay-jump" aria-label="Jump to section">
        <a href="#details"><i class="bi bi-bank2"></i> Details</a>
        <a href="#summary"><i class="bi bi-cash-coin"></i> Summary</a>
        <a href="#receipts"><i class="bi bi-receipt"></i> Receipts</a>
        @if($payout->status !== 'Paid')
            <a href="#exception"><i class="bi bi-exclamation-triangle"></i> Exception</a>
        @endif
        <a href="#confirmations"><i class="bi bi-list-check"></i> Gates</a>
    </nav>

    <div class="pay-grid">
        <div class="pay-stack">
            <section class="sec-card" id="details">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-blue"><i class="bi bi-bank2"></i></span>
                        <div>
                            <h5>Payout Details</h5>
                            <p>Case, lender and report linkage</p>
                        </div>
                    </div>
                    <span class="sec-badge"><i class="bi bi-hash"></i> {{ $case?->case_no ?: '—' }}</span>
                </div>
                <div class="sec-body">
                    <div class="kv-grid">
                        <div class="kv-item">
                            <p class="kv-label"><i class="bi bi-hash"></i> Case Reference</p>
                            <p class="kv-value">{{ $case?->case_no ?: '—' }}</p>
                        </div>
                        <div class="kv-item">
                            <p class="kv-label"><i class="bi bi-person"></i> Customer</p>
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
                        <div class="kv-item">
                            <p class="kv-label"><i class="bi bi-file-earmark-text"></i> Report Number</p>
                            <p class="kv-value {{ $acc?->report_number ? '' : 'muted' }}">
                                @if($acc?->report_number)
                                    {{ $acc->report_number }}
                                    <i class="bi bi-check-circle-fill text-success ms-1" style="font-size:.85rem"></i>
                                @else
                                    —
                                @endif
                            </p>
                        </div>
                        <div class="kv-item">
                            <p class="kv-label"><i class="bi bi-calendar3"></i> Disbursement Date</p>
                            <p class="kv-value">{{ optional($disb?->disbursed_on)->format('d M Y') ?: '—' }}</p>
                        </div>
                        <div class="kv-item">
                            <p class="kv-label"><i class="bi bi-hourglass-split"></i> Ageing</p>
                            <p class="kv-value {{ $ageingDays !== null ? '' : 'muted' }}">{{ $ageingDays !== null ? $ageingDays.' days' : '—' }}</p>
                        </div>
                        <div class="kv-item">
                            <p class="kv-label"><i class="bi bi-clock"></i> Last Updated</p>
                            <p class="kv-value">{{ optional($payout->updated_at)->format('d M Y, h:i A') ?: '—' }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="sec-card" id="summary">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-purple"><i class="bi bi-cash-coin"></i></span>
                        <div>
                            <h5>Payout Summary</h5>
                            <p>Expected vs received · outstanding recalculates with every receipt</p>
                        </div>
                    </div>
                    @if($payout->reconciliation_status)
                        <span class="pd-pill {{ $fmtPill($payout->reconciliation_status) }}">{{ $payout->reconciliation_status }}</span>
                    @endif
                </div>
                <div class="sec-body">
                    <div class="metric-row mb-3">
                        <div class="metric-tile mt-purple">
                            <p class="metric-label">Expected Payout</p>
                            <p class="metric-value">{{ $inr($reconciliation['expected'] ?? 0) }}</p>
                        </div>
                        <div class="metric-tile mt-blue">
                            <p class="metric-label">Total Receipts</p>
                            <p class="metric-value">{{ $inr($reconciliation['received'] ?? 0) }}</p>
                        </div>
                        <div class="metric-tile mt-rose">
                            <p class="metric-label">Outstanding</p>
                            <p class="metric-value">{{ $inr($reconciliation['outstanding'] ?? 0) }}</p>
                        </div>
                    </div>
                    <div class="form-actions">
                        @if($canRecord)
                            <button type="button" class="btn btn-pay"
                                    data-bs-toggle="modal" data-bs-target="#payReceiptModal"
                                    data-pay-id="{{ $payout->id }}"
                                    data-case="{{ $case?->case_no }}"
                                    data-expected="{{ number_format((float) $payout->expected_amount, 0, '.', '') }}"
                                    data-received="{{ number_format((float) $payout->received_amount, 0, '.', '') }}">
                                <i class="bi bi-plus-lg me-1"></i> Process Receipt
                            </button>
                        @elseif(!$canReceipt)
                            <div class="sec-warn mb-0 flex-grow-1"><i class="bi bi-lock-fill"></i><div>You do not have permission to record receipts.</div></div>
                        @elseif($payout->status === 'On Hold')
                            <div class="sec-warn mb-0 flex-grow-1"><i class="bi bi-hourglass-split"></i><div>Receipts unlock after hold release (status Eligible / Processing).</div></div>
                        @elseif($payout->status === 'Exception')
                            <div class="sec-warn mb-0 flex-grow-1"><i class="bi bi-exclamation-triangle-fill"></i><div>Resolve the exception before recording receipts.</div></div>
                        @endif
                    </div>
                </div>
            </section>

            <section class="sec-card" id="receipts">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-amber"><i class="bi bi-receipt"></i></span>
                        <div>
                            <h5>Receipt History</h5>
                            <p>Append-only ledger · never overwrites prior receipts</p>
                        </div>
                    </div>
                    @if($receiptCount)
                        <span class="sec-badge">{{ $receiptCount }} recorded</span>
                    @endif
                </div>
                <div class="sec-body">
                    @if($receiptCount)
                        <div class="receipt-wrap">
                            <table class="receipt-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Reference</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($payout->receipts->sortBy('received_on')->values() as $i => $r)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ optional($r->received_on)->format('d M Y') ?: '—' }}</td>
                                        <td>{{ $r->reference ?: '—' }}</td>
                                        <td class="text-end kv-value amt">{{ $inr($r->amount) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($payout->status === 'Processing')
                            <div class="sec-note mt-3 mb-0"><i class="bi bi-info-circle-fill"></i><div>Partial receipt in progress — outstanding remains until fully matched or adjusted.</div></div>
                        @endif
                    @else
                        <div class="sec-empty">
                            <i class="bi bi-inbox"></i>
                            <p>No receipts recorded yet. Process receipts once the payout is Eligible or Processing.</p>
                        </div>
                    @endif
                </div>
            </section>

            @if($payout->status !== 'Paid')
                <section class="sec-card" id="exception">
                    <div class="sec-head">
                        <div class="sec-head-left">
                            <span class="sec-ico ico-rose"><i class="bi bi-exclamation-triangle"></i></span>
                            <div>
                                <h5>Exception Management</h5>
                                <p>Flag discrepancy, missing report, or unresolved lender issues</p>
                            </div>
                        </div>
                        @if($payout->status === 'Exception')
                            <span class="pd-pill bad">Active</span>
                        @endif
                    </div>
                    <div class="sec-body">
                        @if($payout->status === 'Exception' && $payout->exception_reason)
                            <div class="sec-warn mb-3"><i class="bi bi-exclamation-circle-fill"></i><div>{{ $payout->exception_reason }}</div></div>
                        @endif
                        <form method="POST" action="{{ route('goal.payouts.exception', $payout) }}">
                            @csrf
                            <label class="form-label" for="exception_reason">Exception reason</label>
                            <div class="d-flex flex-wrap gap-2 align-items-start">
                                <input type="text" name="exception_reason" id="exception_reason" class="form-control flex-grow-1" required maxlength="255"
                                       style="min-width:min(100%,240px)"
                                       value="{{ old('exception_reason', $payout->exception_reason) }}"
                                       placeholder="Describe why payout is exceptional">
                                <button type="submit" class="btn btn-danger-soft flex-shrink-0">
                                    <i class="bi bi-exclamation-triangle me-1"></i> Mark Exception
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            @endif
        </div>

        <aside class="pay-aside">
            <section class="sec-card" id="confirmations">
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
                            @php $done = ! empty($c['done']); @endphp
                            <div class="conf-item {{ $done ? 'is-done' : '' }}">
                                <span class="conf-ico">
                                    <i class="bi {{ $done ? 'bi-check-lg' : 'bi-circle' }}"></i>
                                </span>
                                <div style="min-width:0;flex:1">
                                    <p class="conf-label">{{ $c['label'] }}</p>
                                    @if(! empty($c['detail']))
                                        <p class="conf-detail">{{ $c['detail'] }}</p>
                                    @endif
                                </div>
                                <span class="conf-pill">{{ $done ? 'Done' : 'Pending' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="sec-card" id="activity">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-slate"><i class="bi bi-clock-history"></i></span>
                        <div>
                            <h5>Activity Timeline</h5>
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

            @if($canApprove && in_array($payout->status, ['On Hold', 'Eligible'], true))
                <section class="sec-card" id="approval">
                    <div class="sec-head">
                        <div class="sec-head-left">
                            <span class="sec-ico ico-teal"><i class="bi bi-shield-lock"></i></span>
                            <div>
                                <h5>Approve / Release Hold</h5>
                                <p>Accounts Approver only</p>
                            </div>
                        </div>
                    </div>
                    <div class="sec-body">
                        @if($gatesReady)
                            <form method="POST" action="{{ route('goal.payouts.release-hold', $payout) }}">
                                @csrf
                                <label class="pay-release-check">
                                    <input type="checkbox" name="confirm" value="1" required>
                                    <span>I confirm all prerequisites and approve release from hold</span>
                                </label>
                                <button type="submit" class="btn btn-pay w-100">
                                    <i class="bi bi-unlock me-1"></i> Approve / Release Hold
                                </button>
                            </form>
                        @else
                            <div class="sec-warn mb-0">
                                <i class="bi bi-info-circle-fill"></i>
                                <div>
                                    Complete PD-01, report number, PMS and Accounts approval before releasing hold.
                                    @if($accountsItem)
                                        <a href="{{ route('goal.accounts.show', $accountsItem) }}" class="d-block mt-1 fw-bold" style="color:#009B80">Open Accounts Process →</a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </section>
            @endif
        </aside>
    </div>
</div>

@include('goal.payouts.partials.receipt_modal', ['receiptAction' => route('goal.payouts.receipt', $payout)])
@endsection

@push('scripts')
<script>
(function () {
    const page = document.querySelector('.pay-show');
    const modal = document.getElementById('payReceiptModal');
    const form = document.getElementById('payReceiptForm');
    if (!modal || !form) return;
    const receiptBase = (page && page.getAttribute('data-pay-receipt-base')) || '/goal/payouts';
    modal.addEventListener('show.bs.modal', function (ev) {
        const btn = ev.relatedTarget;
        if (!btn || !btn.hasAttribute('data-pay-id')) return;
        const id = btn.getAttribute('data-pay-id');
        const base = receiptBase.replace(/\/$/, '');
        form.action = base + '/' + encodeURIComponent(id) + '/receipt';
        const caseEl = document.getElementById('payRcCase');
        const expEl = document.getElementById('payRcExpected');
        const recEl = document.getElementById('payRcReceived');
        if (caseEl) caseEl.value = btn.getAttribute('data-case') || '';
        if (expEl) expEl.value = btn.getAttribute('data-expected') || '';
        if (recEl) recEl.value = btn.getAttribute('data-received') || '';
    });
})();
</script>
@endpush
