@extends('layouts.app')

@push('styles')
@include('goal.partials.pd_hub_styles')
<style>
.pms-show{
    --pms:#009B80;--pms-dark:#007F68;--pms-soft:#ecfdf5;--border:#e2e8f0;--muted:#64748b;--text:#0f172a;
    background:#F5F8FC;min-height:100%;padding-bottom:2rem
}
.pms-show .pd-hero{margin-bottom:1rem}
.pms-show .pd-title{font-size:1.45rem}
.pms-show .pd-hero-actions .pd-link-btn{border-radius:10px;height:40px;padding:0 16px}
.pms-show .pd-hero-actions .pd-link-btn.primary{background:var(--pms);border-color:var(--pms);color:#fff}
.pms-show .pd-hero-actions .pd-link-btn.primary:hover{background:var(--pms-dark);color:#fff}

/* Summary ribbon */
.pms-show .pms-ribbon{
    display:flex;align-items:stretch;gap:0;flex-wrap:wrap;
    background:#fff;border:1px solid var(--border);border-radius:16px;padding:14px 16px;margin-bottom:12px;
    box-shadow:0 1px 2px rgba(15,23,42,.04),0 10px 24px rgba(148,163,184,.12)
}
.pms-show .pms-ribbon .rb-lead{
    width:46px;height:46px;border-radius:12px;flex:0 0 auto;margin-right:8px;
    background:linear-gradient(145deg,#009B80,#0ea5e9);color:#fff;
    display:inline-flex;align-items:center;justify-content:center;font-size:1.15rem;
    box-shadow:0 8px 16px rgba(0,155,128,.25)
}
.pms-show .pms-ribbon .rb-item{
    flex:1 1 120px;min-width:110px;padding:2px 14px;border-right:1px solid #eef2f7;
    display:flex;align-items:flex-start;gap:8px
}
.pms-show .pms-ribbon .rb-item:last-child{border-right:0}
.pms-show .pms-ribbon .rb-ico{color:#0ea5e9;font-size:1.05rem;margin-top:2px;flex:0 0 auto}
.pms-show .pms-ribbon .rb-label{
    font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6477C7;line-height:1.2
}
.pms-show .pms-ribbon .rb-value{font-size:.86rem;font-weight:750;color:var(--text);margin-top:3px;line-height:1.25;word-break:break-word}

/* Jump tabs */
.pms-show .pms-jump{
    display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px
}
.pms-show .pms-jump a{
    display:inline-flex;align-items:center;gap:6px;height:34px;padding:0 14px;border-radius:999px;
    border:1px solid #dbe3f0;background:#fff;color:#334155;font-size:.78rem;font-weight:700;text-decoration:none
}
.pms-show .pms-jump a:hover,.pms-show .pms-jump a:focus{border-color:#99f6e4;background:var(--pms-soft);color:var(--pms-dark)}
.pms-show .pms-jump a i{font-size:.9rem;color:var(--pms)}

/* Layout */
.pms-show .pms-grid{display:grid;grid-template-columns:minmax(0,1.65fr) minmax(280px,.85fr);gap:12px;align-items:start}
.pms-show .pms-stack{display:flex;flex-direction:column;gap:12px;min-width:0}
.pms-show .pms-side{display:flex;flex-direction:column;gap:12px;position:sticky;top:76px}

/* Section cards */
.pms-show .sec-card{
    background:#fff;border:1px solid var(--border);border-radius:14px;overflow:hidden;
    box-shadow:0 1px 2px rgba(15,23,42,.04),0 8px 20px rgba(148,163,184,.1)
}
.pms-show .sec-head{
    display:flex;align-items:center;justify-content:space-between;gap:10px;
    padding:.85rem 1rem;border-bottom:1px solid #eef2f7;background:linear-gradient(180deg,#fbfdff,#fff)
}
.pms-show .sec-head-left{display:flex;align-items:center;gap:10px;min-width:0}
.pms-show .sec-ico{
    width:36px;height:36px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;
    font-size:1rem;flex:0 0 auto
}
.pms-show .sec-ico.ico-blue{background:#dbeafe;color:#1d4ed8}
.pms-show .sec-ico.ico-teal{background:#d1fae5;color:#059669}
.pms-show .sec-ico.ico-amber{background:#ffedd5;color:#c2410c}
.pms-show .sec-ico.ico-purple{background:#ede9fe;color:#7c3aed}
.pms-show .sec-ico.ico-rose{background:#ffe4e6;color:#e11d48}
.pms-show .sec-ico.ico-sky{background:#e0f2fe;color:#0369a1}
.pms-show .sec-ico.ico-slate{background:#e2e8f0;color:#475569}
.pms-show .sec-head h5{margin:0;font-size:.95rem;font-weight:800;color:var(--text);line-height:1.2}
.pms-show .sec-head p{margin:.12rem 0 0;font-size:.72rem;color:var(--muted);line-height:1.3}
.pms-show .sec-body{padding:1rem}
.pms-show .sec-note{
    display:flex;align-items:flex-start;gap:8px;padding:.65rem .8rem;border-radius:10px;
    background:#f8fafc;border:1px solid #eef2f7;font-size:.78rem;color:#475569;margin-bottom:1rem
}
.pms-show .sec-note i{color:#0ea5e9;margin-top:1px}
.pms-show .sec-ok{
    display:flex;align-items:flex-start;gap:8px;padding:.7rem .85rem;border-radius:10px;
    background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;font-size:.8rem;margin-bottom:1rem
}
.pms-show .sec-ok i{font-size:1.1rem;margin-top:1px}
.pms-show .sec-warn{
    display:flex;align-items:flex-start;gap:8px;padding:.7rem .85rem;border-radius:10px;
    background:#fff7ed;border:1px solid #fed7aa;color:#c2410c;font-size:.8rem;margin-bottom:1rem
}

/* KV grid */
.pms-show .kv-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px 18px;margin:0}
.pms-show .kv-grid.cols-3{grid-template-columns:repeat(3,minmax(0,1fr))}
.pms-show .kv-item{min-width:0}
.pms-show .kv-label{
    display:flex;align-items:center;gap:5px;font-size:.66rem;font-weight:700;text-transform:uppercase;
    letter-spacing:.03em;color:var(--muted);margin:0 0 3px
}
.pms-show .kv-label i{font-size:.75rem;opacity:.85}
.pms-show .kv-value{margin:0;font-size:.88rem;font-weight:700;color:var(--text);line-height:1.35;word-break:break-word}
.pms-show .kv-value.muted{color:#94a3b8;font-weight:600}
.pms-show .kv-value.amt{color:#0f172a;font-weight:800}

/* Forms */
.pms-show .form-label{font-size:.78rem;font-weight:700;color:#334155;margin-bottom:.3rem}
.pms-show .form-label .req{color:#dc2626;margin-left:2px}
.pms-show .form-control,.pms-show .form-select{
    border-radius:10px;border:1.5px solid #dbe3f0;font-size:.84rem;min-height:40px;
    box-shadow:none
}
.pms-show .form-control:focus,.pms-show .form-select:focus,
.pms-show .flatpickr-alt:focus{
    border-color:#5eead4;box-shadow:0 0 0 3px rgba(0,155,128,.12)
}
.pms-show .form-control.is-invalid,.pms-show .form-select.is-invalid,
.pms-show .flatpickr-alt.is-invalid{border-color:#f87171}
.pms-show .pms-date-wrap{position:relative}
.pms-show .pms-date-wrap > i{
    position:absolute;left:12px;top:50%;transform:translateY(-50%);z-index:2;
    color:#64748b;font-size:.9rem;pointer-events:none
}
.pms-show .pms-date-wrap .form-control,
.pms-show .pms-date-wrap .flatpickr-input,
.pms-show .pms-date-wrap .flatpickr-alt{
    padding-left:2.15rem !important;cursor:pointer;background:#fff;width:100%
}
.pms-show .pms-date-wrap .flatpickr-wrapper{display:block;width:100%}
.pms-show .fu-acts .pms-date-wrap{min-width:140px}
.pms-show .fu-acts .pms-date-wrap .form-control,
.pms-show .fu-acts .pms-date-wrap .flatpickr-alt{min-height:32px;font-size:.74rem;padding-left:1.85rem !important}
.pms-show .invalid-feedback{font-size:.74rem;font-weight:600}
.pms-show .field-hint{font-size:.7rem;color:#94a3b8;margin-top:.25rem}
.pms-show .pms-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.pms-show .pd-btn{height:38px;padding:0 16px;border-radius:10px;font-size:.8rem}
.pms-show .pd-btn-ghost{height:38px;padding:0 14px;border-radius:10px;font-size:.8rem}
.pms-show .pd-btn-danger{height:34px;padding:0 12px;border-radius:8px;font-size:.74rem}

/* Timeline */
.pms-show .pms-tl{list-style:none;margin:0;padding:0}
.pms-show .pms-tl li{position:relative;padding:0 0 .95rem 18px;border-left:2px solid #e2e8f0;font-size:.78rem}
.pms-show .pms-tl li:last-child{border-left-color:transparent;padding-bottom:0}
.pms-show .pms-tl li::before{
    content:'';position:absolute;left:-5px;top:4px;width:8px;height:8px;border-radius:50%;background:#cbd5e1;border:2px solid #fff;box-shadow:0 0 0 1px #e2e8f0
}
.pms-show .pms-tl li.done::before{background:#009B80;box-shadow:0 0 0 1px #a7f3d0}
.pms-show .pms-tl li.hold::before{background:#f59e0b;box-shadow:0 0 0 1px #fde68a}
.pms-show .pms-tl .t{font-weight:750;color:var(--text);line-height:1.35}
.pms-show .pms-tl .d{color:var(--muted);margin-top:2px;font-size:.74rem}
.pms-show .pms-tl .m{color:#94a3b8;margin-top:2px;font-size:.7rem}

/* Follow-up table */
.pms-show .fu-table{width:100%;margin:0;border-collapse:separate;border-spacing:0}
.pms-show .fu-table thead th{
    font-size:.66rem;font-weight:750;letter-spacing:.03em;text-transform:uppercase;
    padding:.55rem .65rem;background:#f0f7ff;color:#1e3a5f;border-bottom:1px solid #dbeafe;white-space:nowrap
}
.pms-show .fu-table tbody td{
    padding:.6rem .65rem;font-size:.8rem;vertical-align:middle;border-bottom:1px solid #f1f5f9;color:#334155
}
.pms-show .fu-acts{display:flex;flex-wrap:wrap;gap:6px;align-items:center}
.pms-show .fu-acts .form-control{min-height:32px;font-size:.74rem;width:auto}

/* Status chips in side */
.pms-show .stat-chip{
    display:flex;align-items:center;gap:10px;padding:.7rem .8rem;border-radius:12px;
    background:#f8fafc;border:1px solid #eef2f7;margin-bottom:8px
}
.pms-show .stat-chip:last-child{margin-bottom:0}
.pms-show .stat-chip .ico{
    width:34px;height:34px;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto
}
.pms-show .stat-chip .lbl{font-size:.68rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.03em}
.pms-show .stat-chip .val{font-size:.84rem;font-weight:750;color:var(--text);margin-top:1px}

@media (max-width:1199.98px){
    .pms-show .pms-side{position:static}
    .pms-show .kv-grid.cols-3{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media (max-width:991.98px){
    .pms-show .pms-grid{grid-template-columns:1fr}
    .pms-show .pms-ribbon .rb-item{border-right:0;border-bottom:1px solid #eef2f7;padding:8px 4px;flex:1 1 45%}
    .pms-show .pms-ribbon .rb-lead{margin:4px 8px 4px 0}
}
@media (max-width:575.98px){
    .pms-show .kv-grid,.pms-show .kv-grid.cols-3{grid-template-columns:1fr}
}
</style>
@endpush

@section('content')
@php
    $case = $item->loanCase;
    $disb = $item->disbursement;
    $inr = fn ($n) => '₹ '.number_format((float) $n, 0);
    $fmtPill = function (?string $status) {
        $s = (string) $status;
        return match (true) {
            in_array($s, ['Verified', 'Completed'], true) => 'ok',
            in_array($s, ['Pending Verification', 'Pending', 'In Progress', 'On Hold', 'Due', 'Scheduled'], true) => 'warn',
            in_array($s, ['Rejected', 'Overdue', 'Closed', 'Cancelled'], true) => 'bad',
            in_array($s, ['Follow-up Due'], true) => 'info',
            default => 'info',
        };
    };
    $email = data_get($case?->customer_details, 'email')
        ?: data_get($case?->customer_details, 'applicant.email')
        ?: null;
    $address = data_get($case?->customer_details, 'address')
        ?: data_get($case?->customer_details, 'residence.address')
        ?: data_get($case?->customer_details, 'applicant.address')
        ?: null;
    $oldResult = old('verification_result', 'Verified');
    $remarksRequired = in_array($oldResult, ['Rejected', 'On Hold'], true);
@endphp
<div class="container-fluid p-4 pd-page pms-show">
    <div class="pd-hero">
        <div>
            <ul class="pd-crumbs">
                <li><a href="{{ url('/') }}">Home</a></li>
                <li class="sep">›</li>
                <li><a href="{{ route('goal.pms.index') }}">PMS Queue</a></li>
                <li class="sep">›</li>
                <li>{{ $case?->case_no ?: 'Details' }}</li>
            </ul>
            <h1 class="pd-title">{{ $case?->case_no ?: 'PMS Case' }}</h1>
            <p class="pd-sub mb-0">
                {{ $case?->customer_name ?: 'Customer' }}
                · {{ $case?->product ?: 'Loan' }}
                · <span class="pd-pill {{ $fmtPill($item->statusLabel()) }}">{{ $item->statusLabel() }}</span>
            </p>
        </div>
        <div class="pd-hero-actions">
            <a href="{{ route('goal.pms.index', ['selected' => $item->id]) }}" class="pd-link-btn">
                <i class="bi bi-arrow-left"></i> Back to Queue
            </a>
            @if($case)
                <a href="{{ route('goal.cases.show', $case) }}" class="pd-link-btn primary">
                    <i class="bi bi-folder2-open"></i> Loan Case
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2 d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger py-2 d-flex align-items-center gap-2"><i class="bi bi-exclamation-triangle-fill"></i><span>{{ session('error') }}</span></div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger py-2">
            <div class="fw-bold mb-1"><i class="bi bi-x-circle-fill me-1"></i> Please fix the highlighted fields</div>
            <ul class="mb-0 ps-3 small">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Summary ribbon --}}
    <div class="pms-ribbon">
        <div class="rb-lead" aria-hidden="true"><i class="bi bi-layers-fill"></i></div>
        <div class="rb-item">
            <i class="bi bi-hash rb-ico"></i>
            <div>
                <div class="rb-label">Case Reference</div>
                <div class="rb-value">{{ $case?->case_no ?: '—' }}</div>
            </div>
        </div>
        <div class="rb-item">
            <i class="bi bi-person rb-ico"></i>
            <div>
                <div class="rb-label">Customer</div>
                <div class="rb-value">{{ $case?->customer_name ?: '—' }}</div>
            </div>
        </div>
        <div class="rb-item">
            <i class="bi bi-cash-stack rb-ico"></i>
            <div>
                <div class="rb-label">Disbursed</div>
                <div class="rb-value">{{ $disb ? $inr($disb->amount) : '—' }}</div>
            </div>
        </div>
        <div class="rb-item">
            <i class="bi bi-building rb-ico"></i>
            <div>
                <div class="rb-label">Branch</div>
                <div class="rb-value">{{ $case?->orgUnit?->name ?: '—' }}</div>
            </div>
        </div>
        <div class="rb-item">
            <i class="bi bi-person-badge rb-ico"></i>
            <div>
                <div class="rb-label">Assigned To</div>
                <div class="rb-value">{{ $item->assignee?->name ?: 'Unassigned' }}</div>
            </div>
        </div>
        <div class="rb-item">
            <i class="bi bi-shield-check rb-ico"></i>
            <div>
                <div class="rb-label">PMS Status</div>
                <div class="rb-value"><span class="pd-pill {{ $fmtPill($item->statusLabel()) }}">{{ $item->statusLabel() }}</span></div>
            </div>
        </div>
    </div>

    <nav class="pms-jump" aria-label="Page sections">
        <a href="#overview"><i class="bi bi-grid-1x2"></i> Overview</a>
        <a href="#verify"><i class="bi bi-shield-check"></i> Verification</a>
        <a href="#followups"><i class="bi bi-calendar2-check"></i> Follow-ups</a>
        <a href="#activity"><i class="bi bi-clock-history"></i> Activity</a>
        <a href="#reassign"><i class="bi bi-arrow-left-right"></i> Reassign</a>
    </nav>

    <div class="pms-grid">
        <div class="pms-stack" id="overview">
            {{-- Customer --}}
            <section class="sec-card">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-blue"><i class="bi bi-person"></i></span>
                        <div>
                            <h5>Customer Information</h5>
                            <p>Contact details linked to this loan case</p>
                        </div>
                    </div>
                </div>
                <div class="sec-body">
                    <dl class="kv-grid">
                        <div class="kv-item">
                            <dt class="kv-label"><i class="bi bi-person-fill"></i> Customer Name</dt>
                            <dd class="kv-value">{{ $case?->customer_name ?: '—' }}</dd>
                        </div>
                        <div class="kv-item">
                            <dt class="kv-label"><i class="bi bi-phone"></i> Mobile</dt>
                            <dd class="kv-value {{ $case?->mobile ? '' : 'muted' }}">{{ $case?->mobile ?: '—' }}</dd>
                        </div>
                        <div class="kv-item">
                            <dt class="kv-label"><i class="bi bi-envelope"></i> Email</dt>
                            <dd class="kv-value {{ $email ? '' : 'muted' }}">{{ $email ?: '—' }}</dd>
                        </div>
                        <div class="kv-item">
                            <dt class="kv-label"><i class="bi bi-geo-alt"></i> Address</dt>
                            <dd class="kv-value {{ $address ? '' : 'muted' }}">{{ $address ?: '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            {{-- Loan --}}
            <section class="sec-card">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-teal"><i class="bi bi-briefcase"></i></span>
                        <div>
                            <h5>Loan Information</h5>
                            <p>Product, branch and ownership context</p>
                        </div>
                    </div>
                </div>
                <div class="sec-body">
                    <dl class="kv-grid">
                        <div class="kv-item">
                            <dt class="kv-label"><i class="bi bi-hash"></i> Case Reference</dt>
                            <dd class="kv-value">{{ $case?->case_no ?: '—' }}</dd>
                        </div>
                        <div class="kv-item">
                            <dt class="kv-label"><i class="bi bi-tag"></i> Product</dt>
                            <dd class="kv-value">{{ $case?->product ?: '—' }}</dd>
                        </div>
                        <div class="kv-item">
                            <dt class="kv-label"><i class="bi bi-currency-rupee"></i> Loan Amount</dt>
                            <dd class="kv-value amt">{{ $case?->loan_amount ? $inr($case->loan_amount) : '—' }}</dd>
                        </div>
                        <div class="kv-item">
                            <dt class="kv-label"><i class="bi bi-building"></i> Branch</dt>
                            <dd class="kv-value">{{ $case?->orgUnit?->name ?: '—' }}</dd>
                        </div>
                        <div class="kv-item">
                            <dt class="kv-label"><i class="bi bi-person-workspace"></i> Sales Owner</dt>
                            <dd class="kv-value">{{ $case?->owner?->name ?: '—' }}</dd>
                        </div>
                        <div class="kv-item">
                            <dt class="kv-label"><i class="bi bi-bank"></i> Bank / Lender</dt>
                            <dd class="kv-value">{{ $case?->lender_name ?: '—' }}</dd>
                        </div>
                        <div class="kv-item">
                            <dt class="kv-label"><i class="bi bi-file-earmark-text"></i> Application Ref</dt>
                            <dd class="kv-value">{{ $case?->application_ref ?: '—' }}</dd>
                        </div>
                        <div class="kv-item">
                            <dt class="kv-label"><i class="bi bi-person-badge"></i> Assigned PMS</dt>
                            <dd class="kv-value">{{ $item->assignee?->name ?: 'Unassigned' }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            {{-- Disbursement --}}
            <section class="sec-card">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-purple"><i class="bi bi-cash-coin"></i></span>
                        <div>
                            <h5>Disbursement Information</h5>
                            <p>Verified disbursement that opened this PMS queue (PD-01)</p>
                        </div>
                    </div>
                </div>
                <div class="sec-body">
                    <dl class="kv-grid">
                        <div class="kv-item">
                            <dt class="kv-label"><i class="bi bi-calendar-event"></i> Disbursement Date</dt>
                            <dd class="kv-value">{{ optional($disb?->disbursed_on)->format('d M Y') ?: '—' }}</dd>
                        </div>
                        <div class="kv-item">
                            <dt class="kv-label"><i class="bi bi-currency-rupee"></i> Disbursed Amount</dt>
                            <dd class="kv-value amt">{{ $disb ? $inr($disb->amount) : '—' }}</dd>
                        </div>
                        <div class="kv-item">
                            <dt class="kv-label"><i class="bi bi-upc"></i> Bank Reference</dt>
                            <dd class="kv-value">{{ $disb?->bank_reference ?: '—' }}</dd>
                        </div>
                        <div class="kv-item">
                            <dt class="kv-label"><i class="bi bi-flag"></i> Disbursement Status</dt>
                            <dd class="kv-value">
                                @if($disb?->status)
                                    <span class="pd-pill {{ $fmtPill($disb->status) }}">{{ $disb->status }}</span>
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div class="kv-item">
                            <dt class="kv-label"><i class="bi bi-check2-circle"></i> Verified At</dt>
                            <dd class="kv-value">{{ optional($disb?->verified_at)->format('d M Y, h:i A') ?: '—' }}</dd>
                        </div>
                        <div class="kv-item">
                            <dt class="kv-label"><i class="bi bi-person-check"></i> Verified By</dt>
                            <dd class="kv-value">{{ $disb?->verifier?->name ?: '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            {{-- Verification --}}
            <section class="sec-card" id="verify">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-teal"><i class="bi bi-shield-check"></i></span>
                        <div>
                            <h5>PMS Verification</h5>
                            <p>Confirm post-disbursement details before portfolio follow-up</p>
                        </div>
                    </div>
                    @if($item->canStartVerification())
                        <form method="POST" action="{{ route('goal.pms.start', $item) }}">
                            @csrf
                            <button class="pd-btn" type="submit"><i class="bi bi-play-fill me-1"></i> Start Verification</button>
                        </form>
                    @endif
                </div>
                <div class="sec-body">
                    @if($item->status === 'Verified' || $item->verified_at)
                        <div class="sec-ok">
                            <i class="bi bi-check-circle-fill"></i>
                            <div>
                                <strong>Verified</strong> by {{ $item->verifier?->name ?: '—' }}
                                on {{ optional($item->verified_at)->format('d M Y, h:i A') }}
                                @if($item->verification_remarks)
                                    <div class="mt-1" style="font-weight:600;opacity:.9">{{ $item->verification_remarks }}</div>
                                @endif
                            </div>
                        </div>
                    @elseif($item->status === 'Rejected')
                        <div class="sec-warn">
                            <i class="bi bi-x-octagon-fill"></i>
                            <div>
                                <strong>Rejected</strong>
                                @if($item->verification_remarks)
                                    — {{ $item->verification_remarks }}
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($item->canCompleteVerification())
                        <div class="sec-note">
                            <i class="bi bi-info-circle-fill"></i>
                            <div>Remarks are <strong>required</strong> when result is Rejected or On Hold. Verified cases can unlock payout only after Accounts clearance.</div>
                        </div>

                        <form method="POST" action="{{ route('goal.pms.verify', $item) }}" class="row g-3" id="pmsVerifyForm" novalidate>
                            @csrf
                            <div class="col-md-4">
                                <label class="form-label" for="verification_result">Verification Result <span class="req">*</span></label>
                                <select name="verification_result" id="verification_result" class="form-select @error('verification_result') is-invalid @enderror" required>
                                    @foreach(['Verified','Rejected','On Hold'] as $opt)
                                        <option value="{{ $opt }}" @selected(old('verification_result', 'Verified') === $opt)>{{ $opt }}</option>
                                    @endforeach
                                </select>
                                @error('verification_result')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="activity_type">Activity Type</label>
                                <select name="activity_type" id="activity_type" class="form-select @error('activity_type') is-invalid @enderror">
                                    <option value="portfolio" @selected(old('activity_type', $item->activity_type ?: 'portfolio') === 'portfolio')>Portfolio</option>
                                    <option value="follow_up" @selected(old('activity_type', $item->activity_type) === 'follow_up')>Follow-up</option>
                                    <option value="repeat_opportunity" @selected(old('activity_type', $item->activity_type) === 'repeat_opportunity')>Repeat opportunity</option>
                                    <option value="branch_referral" @selected(old('activity_type', $item->activity_type) === 'branch_referral')>Branch referral</option>
                                </select>
                                @error('activity_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4" id="referralBranchWrap" style="{{ old('activity_type', $item->activity_type) === 'branch_referral' ? '' : 'display:none' }}">
                                <label class="form-label" for="referral_org_unit_id">Referral Branch <span class="req">*</span></label>
                                <select name="referral_org_unit_id" id="referral_org_unit_id" class="form-select @error('referral_org_unit_id') is-invalid @enderror">
                                    <option value="">Select branch</option>
                                    @foreach($branches as $b)
                                        <option value="{{ $b->id }}" @selected((int) old('referral_org_unit_id', $item->referral_org_unit_id) === (int) $b->id)>{{ $b->name }}</option>
                                    @endforeach
                                </select>
                                @error('referral_org_unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="follow_up_at">Next Follow-up At</label>
                                <div class="pms-date-wrap">
                                    <i class="bi bi-calendar3"></i>
                                    <input type="text" name="follow_up_at" id="follow_up_at"
                                           class="form-control js-datetimepicker @error('follow_up_at') is-invalid @enderror"
                                           placeholder="Select date & time"
                                           data-min-date="today"
                                           value="{{ old('follow_up_at', optional($item->follow_up_at)->format('Y-m-d H:i')) }}"
                                           autocomplete="off">
                                </div>
                                <div class="field-hint">Optional — schedules portfolio follow-up after verification</div>
                                @error('follow_up_at')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="verification_remarks">
                                    Remarks
                                    <span class="req" id="remarksReqMark" style="{{ $remarksRequired ? '' : 'display:none' }}">*</span>
                                </label>
                                <textarea name="verification_remarks" id="verification_remarks" rows="3"
                                          class="form-control @error('verification_remarks') is-invalid @enderror"
                                          placeholder="Add notes. Required for Rejected / On Hold."
                                          maxlength="2000"
                                          @if($remarksRequired) required @endif>{{ old('verification_remarks', $item->verification_remarks) }}</textarea>
                                <div class="field-hint"><span id="remarksCount">0</span>/2000 characters</div>
                                @error('verification_remarks')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 pms-actions">
                                <button type="submit" class="pd-btn"><i class="bi bi-check2-circle me-1"></i> Complete Verification</button>
                            </div>
                        </form>
                    @else
                        <div class="sec-note mb-0">
                            <i class="bi bi-lock-fill"></i>
                            <div>Verification is locked for status <strong>{{ $item->statusLabel() }}</strong>.</div>
                        </div>
                    @endif

                    @if($item->verifications->count())
                        <hr class="my-3">
                        <h6 class="fw-bold mb-3" style="font-size:.85rem">Verification History</h6>
                        <ul class="pms-tl">
                            @foreach($item->verifications as $v)
                                <li class="done">
                                    <div class="t">{{ $v->verification_result }}</div>
                                    <div class="d">{{ $v->verifier?->name }} · {{ optional($v->verified_at)->format('d M Y, h:i A') }}</div>
                                    @if($v->verification_remarks)<div class="m">{{ $v->verification_remarks }}</div>@endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>

            {{-- Follow-ups --}}
            <section class="sec-card" id="followups">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-amber"><i class="bi bi-calendar2-check"></i></span>
                        <div>
                            <h5>Portfolio Follow-ups</h5>
                            <p>Schedule EMI confirmation, welcome calls and reviews</p>
                        </div>
                    </div>
                </div>
                <div class="sec-body">
                    <form method="POST" action="{{ route('goal.pms.followups.store', $item) }}" class="row g-3 mb-3" id="pmsFollowupForm" novalidate>
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label" for="followup_type">Type <span class="req">*</span></label>
                            <select name="followup_type" id="followup_type" class="form-select @error('followup_type') is-invalid @enderror" required>
                                @foreach(\App\Models\GoalPmsFollowup::TYPES as $key => $label)
                                    <option value="{{ $key }}" @selected(old('followup_type') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('followup_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="subject">Subject <span class="req">*</span></label>
                            <input type="text" name="subject" id="subject" maxlength="180" required
                                   class="form-control @error('subject') is-invalid @enderror"
                                   value="{{ old('subject') }}" placeholder="e.g. EMI confirmation call">
                            @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="scheduled_date">Date <span class="req">*</span></label>
                            <div class="pms-date-wrap">
                                <i class="bi bi-calendar3"></i>
                                <input type="text" name="scheduled_date" id="scheduled_date" required
                                       class="form-control js-datepicker @error('scheduled_date') is-invalid @enderror"
                                       placeholder="Select date"
                                       data-min-date="today"
                                       value="{{ old('scheduled_date', now()->toDateString()) }}"
                                       autocomplete="off">
                            </div>
                            @error('scheduled_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="scheduled_time">Time</label>
                            <div class="pms-date-wrap">
                                <i class="bi bi-clock"></i>
                                <input type="text" name="scheduled_time" id="scheduled_time"
                                       class="form-control js-timepicker @error('scheduled_time') is-invalid @enderror"
                                       placeholder="Select time"
                                       data-minute-increment="15"
                                       value="{{ old('scheduled_time', '10:00') }}"
                                       autocomplete="off">
                            </div>
                            @error('scheduled_time')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="priority">Priority</label>
                            <select name="priority" id="priority" class="form-select @error('priority') is-invalid @enderror">
                                @foreach(\App\Models\GoalPmsFollowup::PRIORITIES as $p)
                                    <option value="{{ $p }}" @selected(old('priority', 'Medium') === $p)>{{ $p }}</option>
                                @endforeach
                            </select>
                            @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="fu_assigned_to">Assign To</label>
                            <select name="assigned_to" id="fu_assigned_to" class="form-select @error('assigned_to') is-invalid @enderror">
                                @foreach($pmsUsers as $u)
                                    <option value="{{ $u->id }}" @selected((int) old('assigned_to', $item->assigned_to ?: auth()->id()) === (int) $u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                            @error('assigned_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="description">Description</label>
                            <textarea name="description" id="description" rows="2" maxlength="2000"
                                      class="form-control @error('description') is-invalid @enderror"
                                      placeholder="Optional notes for the assignee">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 pms-actions">
                            <button type="submit" class="pd-btn"><i class="bi bi-plus-lg me-1"></i> Save Follow-up</button>
                        </div>
                    </form>

                    <div class="table-responsive" style="border:1px solid #eef2f7;border-radius:12px;overflow:hidden">
                        <table class="fu-table">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Due</th>
                                    <th>Owner</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($item->followups as $fu)
                                <tr>
                                    <td>
                                        <div class="fw-bold" style="color:#0f172a">{{ $fu->subject }}</div>
                                        <div class="pd-muted" style="font-size:.72rem">{{ $fu->typeLabel() }} · {{ $fu->priority }}</div>
                                    </td>
                                    <td>{{ $fu->scheduled_date?->format('d M Y') }}{{ $fu->scheduled_time ? ' · '.substr((string)$fu->scheduled_time,0,5) : '' }}</td>
                                    <td>{{ $fu->assignee?->name ?: '—' }}</td>
                                    <td><span class="pd-pill {{ $fmtPill($fu->displayStatus()) }}">{{ $fu->displayStatus() }}</span></td>
                                    <td>
                                        @if(!in_array($fu->status, ['Completed','Cancelled'], true))
                                            <div class="fu-acts">
                                                <form method="POST" action="{{ route('goal.pms.followups.complete', $fu) }}">
                                                    @csrf
                                                    <button class="pd-btn" type="submit" style="height:32px;font-size:.72rem;padding:0 10px">Complete</button>
                                                </form>
                                                <form method="POST" action="{{ route('goal.pms.followups.cancel', $fu) }}" onsubmit="return confirm('Cancel this follow-up?')">
                                                    @csrf
                                                    <button class="pd-btn-danger" type="submit">Cancel</button>
                                                </form>
                                                <form method="POST" action="{{ route('goal.pms.followups.reschedule', $fu) }}" class="fu-acts">
                                                    @csrf
                                                    <div class="pms-date-wrap">
                                                        <i class="bi bi-calendar3"></i>
                                                        <input type="text" name="scheduled_date"
                                                               class="form-control js-datepicker" required
                                                               placeholder="Date"
                                                               data-min-date="today"
                                                               value="{{ $fu->scheduled_date?->format('Y-m-d') }}"
                                                               autocomplete="off">
                                                    </div>
                                                    <button class="pd-btn-ghost" type="submit" style="height:32px;font-size:.72rem;padding:0 10px">Reschedule</button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="pd-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="pd-empty py-3">
                                            <i class="bi bi-calendar-x"></i>
                                            No follow-ups yet. Schedule the first portfolio task above.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            {{-- Activity --}}
            <section class="sec-card" id="activity">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-slate"><i class="bi bi-clock-history"></i></span>
                        <div>
                            <h5>Activity History</h5>
                            <p>Audit trail for verification, assignment and follow-ups</p>
                        </div>
                    </div>
                </div>
                <div class="sec-body">
                    <ul class="pms-tl">
                        @forelse($item->activityLogs as $log)
                            <li class="done">
                                <div class="t">{{ $log->description }}</div>
                                <div class="d">{{ $log->user?->name ?: 'System' }} · {{ $log->created_at?->format('d M Y, h:i A') }} · {{ $log->activity_type }}</div>
                                @if($log->old_value || $log->new_value)
                                    <div class="m">{{ $log->old_value ?: '—' }} → {{ $log->new_value ?: '—' }}</div>
                                @endif
                            </li>
                        @empty
                            <li>
                                <div class="d">No activity recorded yet.</div>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </section>
        </div>

        <aside class="pms-side">
            {{-- Quick status --}}
            <section class="sec-card">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-sky"><i class="bi bi-speedometer2"></i></span>
                        <div>
                            <h5>Case Snapshot</h5>
                            <p>At-a-glance status</p>
                        </div>
                    </div>
                </div>
                <div class="sec-body">
                    <div class="stat-chip">
                        <span class="ico" style="background:#d1fae5;color:#059669"><i class="bi bi-shield"></i></span>
                        <div>
                            <div class="lbl">PMS Status</div>
                            <div class="val"><span class="pd-pill {{ $fmtPill($item->statusLabel()) }}">{{ $item->statusLabel() }}</span></div>
                        </div>
                    </div>
                    <div class="stat-chip">
                        <span class="ico" style="background:#dbeafe;color:#1d4ed8"><i class="bi bi-person-badge"></i></span>
                        <div>
                            <div class="lbl">Assigned</div>
                            <div class="val">{{ $item->assignee?->name ?: 'Unassigned' }}</div>
                        </div>
                    </div>
                    <div class="stat-chip">
                        <span class="ico" style="background:#ffedd5;color:#c2410c"><i class="bi bi-calendar-event"></i></span>
                        <div>
                            <div class="lbl">Next Follow-up</div>
                            <div class="val">{{ optional($item->follow_up_at)->format('d M Y, h:i A') ?: 'Not set' }}</div>
                        </div>
                    </div>
                    <div class="stat-chip">
                        <span class="ico" style="background:#ede9fe;color:#7c3aed"><i class="bi bi-cash-stack"></i></span>
                        <div>
                            <div class="lbl">Disbursed Amount</div>
                            <div class="val">{{ $disb ? $inr($disb->amount) : '—' }}</div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- PD-01 workflow --}}
            <section class="sec-card">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-teal"><i class="bi bi-diagram-3"></i></span>
                        <div>
                            <h5>PD-01 Workflow</h5>
                            <p>Post-disbursement chain</p>
                        </div>
                    </div>
                </div>
                <div class="sec-body">
                    <ul class="pms-tl">
                        @foreach($workflow as $ev)
                            @php
                                $liClass = match ($ev['state'] ?? null) {
                                    'done' => 'done',
                                    'hold' => 'hold',
                                    default => (!empty($ev['done']) ? 'done' : ''),
                                };
                            @endphp
                            <li class="{{ $liClass }}">
                                <div class="t">{{ $ev['label'] }}</div>
                                @if(!empty($ev['detail']))
                                    <div class="d">{{ $ev['detail'] }}</div>
                                @endif
                                <div class="m">
                                    {{ !empty($ev['at']) ? \Carbon\Carbon::parse($ev['at'])->format('d M Y, h:i A') : 'Pending' }}
                                    @if(!empty($ev['badge']))
                                        · <span class="pd-pill {{ $fmtPill($ev['badge']) }}">{{ $ev['badge'] }}</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>

            {{-- Reassign --}}
            <section class="sec-card" id="reassign">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-rose"><i class="bi bi-arrow-left-right"></i></span>
                        <div>
                            <h5>Reassign</h5>
                            <p>Transfer ownership to another PMS user</p>
                        </div>
                    </div>
                </div>
                <div class="sec-body">
                    <form method="POST" action="{{ route('goal.pms.reassign', $item) }}" id="pmsReassignForm" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="assigned_to">PMS User <span class="req">*</span></label>
                            <select name="assigned_to" id="assigned_to" class="form-select @error('assigned_to') is-invalid @enderror" required>
                                @forelse($pmsUsers as $u)
                                    <option value="{{ $u->id }}" @selected((int) old('assigned_to', $item->assigned_to) === (int) $u->id)>{{ $u->name }}</option>
                                @empty
                                    <option value="">No PMS users available</option>
                                @endforelse
                            </select>
                            @error('assigned_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="reassignment_reason">Reason</label>
                            <input type="text" name="reassignment_reason" id="reassignment_reason" maxlength="500"
                                   class="form-control @error('reassignment_reason') is-invalid @enderror"
                                   value="{{ old('reassignment_reason') }}" placeholder="Optional — why reassigning">
                            @error('reassignment_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="pd-btn w-100" @disabled($pmsUsers->isEmpty())>
                            <i class="bi bi-arrow-left-right me-1"></i> Reassign Case
                        </button>
                    </form>

                    @if($item->assignmentHistory->count())
                        <hr class="my-3">
                        <h6 class="fw-bold mb-3" style="font-size:.85rem">Assignment History</h6>
                        <ul class="pms-tl">
                            @foreach($item->assignmentHistory as $h)
                                <li class="done">
                                    <div class="t">{{ $h->previousUser?->name ?: '—' }} → {{ $h->newUser?->name ?: '—' }}</div>
                                    <div class="d">{{ $h->assigner?->name }} · {{ $h->assigned_at?->format('d M Y, h:i A') }}</div>
                                    @if($h->reassignment_reason ?? $h->reason ?? null)
                                        <div class="m">{{ $h->reassignment_reason ?? $h->reason }}</div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const resultEl = document.getElementById('verification_result');
    const remarksEl = document.getElementById('verification_remarks');
    const remarksMark = document.getElementById('remarksReqMark');
    const remarksCount = document.getElementById('remarksCount');
    const activityEl = document.getElementById('activity_type');
    const referralWrap = document.getElementById('referralBranchWrap');
    const referralEl = document.getElementById('referral_org_unit_id');
    const verifyForm = document.getElementById('pmsVerifyForm');

    function syncRemarksRequired() {
        if (!resultEl || !remarksEl) return;
        const needs = ['Rejected', 'On Hold'].includes(resultEl.value);
        remarksEl.required = needs;
        if (remarksMark) remarksMark.style.display = needs ? '' : 'none';
        if (!needs) remarksEl.classList.remove('is-invalid');
    }

    function syncReferral() {
        if (!activityEl || !referralWrap || !referralEl) return;
        const show = activityEl.value === 'branch_referral';
        referralWrap.style.display = show ? '' : 'none';
        referralEl.required = show;
        if (!show) {
            referralEl.value = '';
            referralEl.classList.remove('is-invalid');
        }
    }

    function syncCount() {
        if (remarksEl && remarksCount) {
            remarksCount.textContent = String((remarksEl.value || '').length);
        }
    }

    resultEl?.addEventListener('change', syncRemarksRequired);
    activityEl?.addEventListener('change', syncReferral);
    remarksEl?.addEventListener('input', syncCount);
    syncRemarksRequired();
    syncReferral();
    syncCount();

    verifyForm?.addEventListener('submit', function (e) {
        let ok = true;
        const needsRemarks = ['Rejected', 'On Hold'].includes(resultEl?.value || '');
        if (needsRemarks && !(remarksEl?.value || '').trim()) {
            remarksEl?.classList.add('is-invalid');
            ok = false;
        }
        if (activityEl?.value === 'branch_referral' && !(referralEl?.value || '')) {
            referralEl?.classList.add('is-invalid');
            ok = false;
        }
        if (!resultEl?.value) {
            resultEl?.classList.add('is-invalid');
            ok = false;
        }
        if (!ok) {
            e.preventDefault();
            verifyForm.querySelector('.is-invalid')?.focus();
        }
    });

    const fuForm = document.getElementById('pmsFollowupForm');
    fuForm?.addEventListener('submit', function (e) {
        const subject = document.getElementById('subject');
        const date = document.getElementById('scheduled_date');
        const dateAlt = date?._flatpickr?.altInput || null;
        let ok = true;
        if (!(subject?.value || '').trim()) {
            subject?.classList.add('is-invalid');
            ok = false;
        }
        if (!(date?.value || '')) {
            date?.classList.add('is-invalid');
            dateAlt?.classList.add('is-invalid');
            ok = false;
        }
        if (!ok) {
            e.preventDefault();
            (dateAlt || fuForm.querySelector('.is-invalid'))?.focus();
        }
    });

    document.querySelectorAll('.pms-jump a').forEach((a) => {
        a.addEventListener('click', (e) => {
            const id = a.getAttribute('href');
            const el = id ? document.querySelector(id) : null;
            if (el) {
                e.preventDefault();
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    if (window.IsarvaDatepicker) {
        window.IsarvaDatepicker.init(document.querySelector('.pms-show') || document);
    }
})();
</script>
@endpush
