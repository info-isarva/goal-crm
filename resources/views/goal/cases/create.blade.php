@extends('layouts.app')

@push('styles')
<style>
.lc-create{
    --lc-border:#e8edf5;--lc-text:#0f172a;--lc-muted:#64748b;
    color:var(--lc-text);padding-bottom:96px;
}
.lc-create .wiz-top{
    display:flex;align-items:flex-start;justify-content:space-between;gap:14px;
    margin-bottom:1.1rem;flex-wrap:wrap;
}
.lc-create .wiz-top-main{display:flex;align-items:flex-start;gap:14px;min-width:0}
.lc-create .wiz-back{
    width:42px;height:42px;border-radius:12px;border:1px solid var(--lc-border);background:#fff;
    color:var(--theme-primary);display:inline-flex;align-items:center;justify-content:center;text-decoration:none;flex-shrink:0;
    box-shadow:0 4px 12px rgba(148,163,184,.10);
}
.lc-create .wiz-back:hover{background:var(--theme-50);color:var(--theme-primary-hover)}
.lc-create .wiz-crumbs{
    display:flex;flex-wrap:wrap;align-items:center;gap:6px;
    margin:0 0 6px;padding:0;list-style:none;font-size:.78rem;color:#94a3b8;
}
.lc-create .wiz-crumbs a{color:var(--theme-primary);text-decoration:none;font-weight:600}
.lc-create .wiz-crumbs a:hover{text-decoration:underline}
.lc-create .wiz-crumbs .sep{opacity:.5}
.lc-create .wiz-title{margin:0;font-size:1.45rem;font-weight:800;color:var(--lc-text);letter-spacing:-.02em;line-height:1.2}
.lc-create .wiz-sub{margin:4px 0 0;font-size:.88rem;color:var(--lc-muted);line-height:1.45;max-width:52rem}
.lc-create .wiz-badge{
    height:34px;padding:0 12px;border-radius:999px;border:1px solid #dbe3f0;background:#fff;
    display:inline-flex;align-items:center;gap:6px;font-size:.78rem;font-weight:700;color:#475569;
}

.lc-create .sec-card{
    background:#fff;border:1px solid var(--lc-border);border-radius:16px;
    box-shadow:0 6px 18px rgba(148,163,184,.08);margin-bottom:1rem;overflow:hidden;height:100%;
}
.lc-create .sec-head{
    display:flex;align-items:center;gap:12px;padding:12px 16px;
    border-bottom:1px solid #eef2f7;background:linear-gradient(180deg,#fff 0%,#fbfcfe 100%);
}
.lc-create .sec-ico{
    width:38px;height:38px;border-radius:11px;display:inline-flex;align-items:center;justify-content:center;
    background:var(--theme-100);color:var(--theme-primary);font-size:1rem;flex-shrink:0;
}
.lc-create .sec-ico.ico-loan{background:#ecfdf5;color:#059669}
.lc-create .sec-ico.ico-job{background:#eff6ff;color:#2563eb}
.lc-create .sec-ico.ico-own{background:#f0fdfa;color:#0d9488}
.lc-create .sec-ico.ico-doc{background:#f5f3ff;color:#7c3aed}
.lc-create .sec-ico.ico-amber{background:#fffbeb;color:#d97706}
.lc-create .sec-head h6{margin:0;font-size:.95rem;font-weight:800;color:#0f172a}
.lc-create .sec-head .sec-note{margin-left:auto;font-size:.75rem;color:#94a3b8;font-weight:600}
.lc-create .sec-body{padding:16px 18px 18px}
.lc-create .sec-pair{margin-bottom:1rem}
.lc-create .sec-pair > [class*="col-"]{display:flex;flex-direction:column}
.lc-create .sec-pair .sec-card{margin-bottom:0}

.lc-create .form-label{display:block;font-size:.76rem;font-weight:700;color:#475569;margin-bottom:6px}
.lc-create .form-label .req{color:#dc2626;margin-left:2px;font-weight:800}
.lc-create .form-control,.lc-create .form-select{
    min-height:44px;border-radius:10px;border:1px solid #d1d5db;background:#fff;
    font-size:.9rem;color:var(--lc-text);padding:.55rem .9rem;
}
.lc-create .form-control::placeholder{color:#94a3b8}
.lc-create .form-control:focus,.lc-create .form-select:focus{
    border-color:var(--theme-300);background:#fff;box-shadow:0 0 0 3px var(--theme-primary-ring);
}
.lc-create .form-control:disabled,.lc-create .form-control.is-readonly{
    background:#f1f5f9;color:#64748b;border-color:#e2e8f0;cursor:default;
}
.lc-create .ld-input-ico{position:relative;width:100%}
.lc-create .ld-input-ico > i,.lc-create .ld-input-ico > .rupee{
    position:absolute;left:0;top:0;bottom:0;width:40px;z-index:3;pointer-events:none;
    display:inline-flex;align-items:center;justify-content:center;color:#64748b;font-size:.9rem;
}
.lc-create .ld-input-ico .rupee{font-weight:800;font-style:normal;color:#475569}
.lc-create .ld-input-ico .form-control,.lc-create .ld-input-ico .form-select,
.lc-create .ld-input-ico .flatpickr-input,
.lc-create .ld-input-ico .flatpickr-alt{padding-left:40px!important;width:100%}
.lc-create .select2-container{width:100%!important}
.lc-create .select2-container--default .select2-selection--single{
    min-height:44px;height:44px;border-radius:10px;border:1px solid #d1d5db;background:#fff;
}
.lc-create .select2-container--default .select2-selection--single .select2-selection__rendered{
    padding-left:.9rem;padding-right:2rem;line-height:42px;font-size:.9rem;font-weight:500;color:var(--lc-text);
}
.lc-create .select2-container--default .select2-selection--single .select2-selection__arrow{height:42px}
.lc-create .ld-input-ico .select2-container--default .select2-selection--single .select2-selection__rendered{padding-left:40px!important}
.lc-create .select2-container--default.select2-container--focus .select2-selection--single,
.lc-create .select2-container--default.select2-container--open .select2-selection--single{
    border-color:var(--theme-300);box-shadow:0 0 0 3px var(--theme-primary-ring);
}

.lc-create .consent-box{
    min-height:48px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;
    padding:10px 12px;display:flex;align-items:center;gap:12px;cursor:pointer;
    transition:border-color .15s ease,background .15s ease;
}
.lc-create .consent-box:hover{border-color:#cbd5e1}
.lc-create .consent-box.is-on{border-color:#86efac;background:#f0fdf4}
.lc-create .consent-box .consent-copy{min-width:0;flex:1}
.lc-create .consent-box .consent-meta{font-size:.75rem;color:var(--lc-muted);line-height:1.35}
.lc-create .consent-box #consentLabelText{font-size:.86rem;font-weight:700}
.lc-create .consent-box:not(.is-on) #consentLabelText{color:#b91c1c}
.lc-create .consent-box.is-on #consentLabelText{color:#15803d}
.lc-create .consent-toggle{
    position:relative;flex-shrink:0;width:46px;height:26px;margin:0;display:inline-block;
}
.lc-create .consent-toggle input{
    position:absolute;inset:0;width:100%;height:100%;margin:0;opacity:0;cursor:pointer;z-index:2;
}
.lc-create .consent-toggle .track{
    position:absolute;inset:0;border-radius:999px;background:#cbd5e1;
    box-shadow:inset 0 0 0 1px rgba(15,23,42,.06);
    transition:background .18s ease;
}
.lc-create .consent-toggle .knob{
    position:absolute;top:3px;left:3px;width:20px;height:20px;border-radius:50%;
    background:#fff;box-shadow:0 1px 3px rgba(15,23,42,.25);
    transition:transform .18s ease;pointer-events:none;
}
.lc-create .consent-toggle input:checked + .track{background:var(--theme-primary)}
.lc-create .consent-toggle input:checked + .track .knob{transform:translateX(20px)}
.lc-create .consent-toggle input:focus-visible + .track{
    outline:2px solid var(--theme-primary);outline-offset:2px;
}

.lc-create .bm-reco-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0}
.lc-create .bm-reco-cell{padding:4px 16px;border-right:1px solid #eef2f7}
.lc-create .bm-reco-cell:first-child{padding-left:0}
.lc-create .bm-reco-cell:last-child{border-right:0;padding-right:0}
.lc-create .bm-reco-cell .k{font-size:.72rem;font-weight:700;color:#64748b;margin-bottom:4px}
.lc-create .bm-reco-cell .v{font-size:.92rem;font-weight:600;color:var(--lc-text)}
.lc-create .bm-reco-cell .v.muted{color:#94a3b8;font-weight:500}

.lc-create .doc-table{width:100%;margin:0;border-collapse:separate;border-spacing:0}
.lc-create .doc-table th{
    font-size:.72rem;text-transform:uppercase;letter-spacing:.03em;color:#64748b;font-weight:700;
    background:#f8fafc;padding:.7rem .75rem;border-bottom:1px solid #eef2f7;
}
.lc-create .doc-table td{padding:.7rem .75rem;border-bottom:1px solid #eef2f7;vertical-align:middle;font-size:.86rem}
.lc-create .doc-table tr:last-child td{border-bottom:0}
.lc-create .sec-body .table-responsive{border-radius:12px;overflow:hidden;border:1px solid #eef2f7}
.lc-create .doc-badge{
    display:inline-block;font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:999px;
    background:var(--theme-50);color:var(--theme-primary);
}
.lc-create .doc-badge.req{background:#fef2f2;color:#dc2626}
.lc-create .doc-empty{text-align:center;padding:1.5rem 1rem;color:#94a3b8;font-size:.88rem}
.lc-create .doc-empty i{display:block;font-size:1.4rem;margin-bottom:6px;opacity:.7}

.lc-create .wiz-footer{
    position:sticky;bottom:58px;z-index:30;width:100%;
    margin:0 0 8px;border:1px solid var(--lc-border);border-radius:16px;
    background:rgba(255,255,255,.96);backdrop-filter:blur(8px);padding:12px 16px;
    display:flex;align-items:center;justify-content:space-between;gap:12px;
    box-shadow:0 10px 28px rgba(15,23,42,.10);
}
.lc-create .wiz-footer-hint{font-size:.78rem;color:#64748b;max-width:28rem;line-height:1.4}
.lc-create .wiz-footer-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.lc-create .btn-cancel{
    height:42px;padding:0 16px;border-radius:10px;font-weight:700;font-size:.86rem;
    border:1px solid #d1d5db;background:#fff;color:#334155;text-decoration:none;
    display:inline-flex;align-items:center;
}
.lc-create .btn-cancel:hover{background:#f8fafc;color:#0f172a}
.lc-create .btn-draft{
    height:42px;padding:0 16px;border-radius:10px;font-weight:700;font-size:.86rem;
    border:1.5px solid var(--theme-300);background:#fff;color:var(--theme-primary);
    display:inline-flex;align-items:center;gap:6px;
}
.lc-create .btn-draft:hover{background:var(--theme-50)}
.lc-create .btn-next{
    height:42px;padding:0 18px;border-radius:10px;font-weight:700;font-size:.86rem;border:0;color:#fff;
    background:var(--theme-primary);
    display:inline-flex;align-items:center;gap:6px;box-shadow:0 8px 16px var(--theme-primary-ring);
}
.lc-create .btn-next:hover{background:var(--theme-primary-hover);color:#fff}
.lc-create .btn-next:disabled,.lc-create .btn-draft:disabled{opacity:.65;pointer-events:none}
.lc-create .lead-hint{font-size:.78rem;color:var(--lc-muted);margin-top:6px;line-height:1.35}
.lc-create .lead-hint.err{color:#b91c1c}
.lc-create .lead-hint.ok{color:#15803d}
.lc-create .alert-top{border-radius:12px;border:1px solid #fecaca;background:#fef2f2;color:#991b1b;padding:10px 14px;margin-bottom:1rem;font-size:.86rem}

body > .select2-container{z-index:2000!important}
body > .select2-container .select2-dropdown{
    border:1px solid var(--lc-border);border-radius:10px;box-shadow:0 12px 28px rgba(15,23,42,.12);
}
body > .select2-container .select2-results > .select2-results__options{max-height:min(70vh,420px)}

@media (max-width:991.98px){
    .lc-create .bm-reco-grid{grid-template-columns:1fr 1fr}
    .lc-create .bm-reco-cell{border-right:0;border-bottom:1px solid #eef2f7;padding:10px 0}
    .lc-create .sec-pair .sec-card{margin-bottom:1rem}
    .lc-create .wiz-footer{flex-direction:column;align-items:stretch}
    .lc-create .wiz-footer-actions{justify-content:stretch}
    .lc-create .wiz-footer-actions > *{flex:1;justify-content:center}
}
@media (max-width:767.98px){
    .lc-create .bm-reco-grid{grid-template-columns:1fr}
    .lc-create .wiz-footer{bottom:12px}
}
</style>
@endpush

@section('content')
@php
    $prefill = $prefillLead ?? null;
    $fmtOwner = function ($u) {
        $role = match ((int) ($u->crm_role_type ?? -1)) {
            10 => 'JSM',
            11 => 'SM',
            12 => 'SSM',
            8 => 'BM',
            default => strtoupper((string) ($u->job_role ?: 'User')),
        };
        return $role . ' - ' . $u->name;
    };
@endphp
<div class="container-fluid p-4 lc-create"
     data-lookup-url="{{ $lookupUrl }}"
     data-product-docs-url="{{ $productDocsUrl }}"
     data-prefill='@json($prefill)'>

    <div class="wiz-top">
        <div class="wiz-top-main">
            <a href="{{ route('goal.cases.index') }}" class="wiz-back" title="Back"><i class="bi bi-arrow-left"></i></a>
            <div>
                <ul class="wiz-crumbs">
                    <li><a href="{{ url('/') }}">Home</a></li>
                    <li class="sep">›</li>
                    <li><a href="{{ route('goal.cases.index') }}">Loan Cases</a></li>
                    <li class="sep">›</li>
                    <li>Create Case</li>
                </ul>
                <h1 class="wiz-title">Create Loan Case</h1>
                <div class="wiz-sub">Pick a Qualified lead, or leave Lead Reference blank to open a case for a new customer. Bank processing continues in Case Details.</div>
            </div>
        </div>
        <span class="wiz-badge"><i class="bi bi-shield-check"></i> Consent required</span>
    </div>

    @if($errors->any())
        <div class="alert-top">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ $storeUrl }}" id="lcCreateForm" enctype="multipart/form-data" novalidate>
        @csrf
        <input type="hidden" name="goal_loan_lead_id" id="goal_loan_lead_id" value="{{ old('goal_loan_lead_id', $prefill['id'] ?? '') }}">
        <input type="hidden" name="save_action" id="save_action" value="draft">

        {{-- Customer Details --}}
        <div class="sec-card">
            <div class="sec-head">
                <span class="sec-ico"><i class="bi bi-person-vcard"></i></span>
                <h6>Customer Details</h6>
                <span class="sec-note">Lead optional</span>
            </div>
            <div class="sec-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6 col-xl-3" id="leadSelectWrap">
                        <label class="form-label" for="lead_select">Lead Reference <span class="text-muted fw-semibold">(optional)</span></label>
                        <select id="lead_select" class="form-select" data-select2-skip="1"></select>
                        <div class="lead-hint" id="lookupMsg">Optional — pick a Qualified lead without a case, or leave blank to create a new customer.</div>
                        @error('goal_loan_lead_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        @error('case_origin')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label" for="customer_ref">Customer Reference</label>
                        <input type="text" name="customer_ref" id="customer_ref" class="form-control is-readonly"
                               value="{{ old('customer_ref', $prefill['customer_ref'] ?? '') }}" readonly tabindex="-1">
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label" for="customer_name">Customer Name <span class="req">*</span></label>
                        <input type="text" name="customer_name" id="customer_name" class="form-control"
                               value="{{ old('customer_name', $prefill['customer_name'] ?? '') }}" required maxlength="150" placeholder="Full name">
                        @error('customer_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label" for="mobile">Mobile Number <span class="req">*</span></label>
                        <div class="ld-input-ico">
                            <i class="bi bi-telephone"></i>
                            <input type="text" name="mobile" id="mobile" class="form-control js-mobile"
                                   value="{{ old('mobile', $prefill['mobile'] ?? '') }}" required maxlength="10" inputmode="numeric"
                                   pattern="[0-9]{10}" placeholder="10-digit mobile">
                        </div>
                        @error('mobile')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label" for="alt_mobile">Alternate Number</label>
                        <div class="ld-input-ico">
                            <i class="bi bi-telephone"></i>
                            <input type="text" name="alt_mobile" id="alt_mobile" class="form-control js-mobile"
                                   value="{{ old('alt_mobile') }}" maxlength="10" inputmode="numeric"
                                   pattern="[0-9]{10}" placeholder="Optional">
                        </div>
                        @error('alt_mobile')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label" for="email">Email</label>
                        <div class="ld-input-ico">
                            <i class="bi bi-envelope"></i>
                            <input type="email" name="email" id="email" class="form-control"
                                   value="{{ old('email', $prefill['email'] ?? '') }}" maxlength="150" placeholder="name@example.com">
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label">Preferred Contact Time</label>
                        @php
                            $oldPct = old('preferred_contact_time', '');
                            $pctFrom = old('preferred_contact_from', '');
                            $pctTo = old('preferred_contact_to', '');
                            if ($oldPct && str_contains($oldPct, ' - ') && ($pctFrom === '' || $pctTo === '')) {
                                [$pctFrom, $pctTo] = array_map('trim', explode(' - ', $oldPct, 2));
                            }
                        @endphp
                        <input type="hidden" name="preferred_contact_time" id="preferred_contact_time" value="{{ $oldPct }}">
                        <div class="d-flex align-items-center gap-2">
                            <div class="ld-input-ico flex-grow-1">
                                <i class="bi bi-clock"></i>
                                <input type="text" name="preferred_contact_from" id="preferred_contact_from"
                                       class="form-control js-timepicker" value="{{ $pctFrom }}"
                                       placeholder="From" autocomplete="off" data-minute-increment="15">
                            </div>
                            <span class="text-muted small fw-semibold">—</span>
                            <div class="ld-input-ico flex-grow-1">
                                <i class="bi bi-clock"></i>
                                <input type="text" name="preferred_contact_to" id="preferred_contact_to"
                                       class="form-control js-timepicker" value="{{ $pctTo }}"
                                       placeholder="To" autocomplete="off" data-minute-increment="15">
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label" for="consent_recorded">Customer Consent <span class="req">*</span></label>
                        @php $consentOn = (bool) old('consent_recorded', $prefill['consent_recorded'] ?? false); @endphp
                        <div class="consent-box {{ $consentOn ? 'is-on' : '' }}" id="consentBox">
                            <label class="consent-toggle" for="consent_recorded" aria-label="Customer consent">
                                <input type="checkbox" role="switch" name="consent_recorded" id="consent_recorded"
                                       value="1" required @checked($consentOn)>
                                <span class="track"><span class="knob"></span></span>
                            </label>
                            <div class="consent-copy">
                                <div id="consentLabelText">{{ $consentOn ? 'Consent recorded' : 'Not recorded' }}</div>
                                <div class="consent-meta">Required before Create Case. Timestamp and user are stored on save.</div>
                            </div>
                        </div>
                        <div class="text-danger small mt-1 d-none" id="consentClientError">Customer consent must be recorded.</div>
                        @error('consent_recorded')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Loan Requirement --}}
        <div class="sec-card">
            <div class="sec-head">
                <span class="sec-ico ico-loan"><i class="bi bi-layers"></i></span>
                <h6>Loan Requirement</h6>
            </div>
            <div class="sec-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label" for="product">Product <span class="req">*</span></label>
                        <select name="product" id="product" data-draft-optional="1" class="form-select" required>
                            <option value="">Select product</option>
                            @foreach($products as $p)
                                <option value="{{ $p->product_name }}" @selected(old('product', $prefill['product'] ?? '') === $p->product_name)>{{ $p->product_name }}</option>
                            @endforeach
                        </select>
                        @error('product')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label" for="loan_amount">Requested Amount <span class="req">*</span></label>
                        <div class="ld-input-ico">
                            <span class="rupee">₹</span>
                            <input type="text" inputmode="decimal" data-decimals="2" name="loan_amount" id="loan_amount" data-draft-optional="1"
                                   class="form-control js-decimal" value="{{ old('loan_amount', $prefill['loan_amount'] ?? '') }}" placeholder="0.00" required>
                        </div>
                        @error('loan_amount')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label" for="purpose">Purpose</label>
                        <select name="purpose" id="purpose" class="form-select" data-search="false">
                            <option value="">Select</option>
                            @foreach($purposes as $p)
                                <option value="{{ $p }}" @selected(old('purpose') === $p)>{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label" for="tenure_months">Tenure (Months)</label>
                        <input type="text" inputmode="numeric" name="tenure_months" id="tenure_months"
                               class="form-control js-int" value="{{ old('tenure_months') }}" maxlength="3" placeholder="e.g. 60">
                        @error('tenure_months')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Employment + Ownership --}}
        <div class="row g-3 sec-pair">
            <div class="col-12 col-lg-7">
                <div class="sec-card">
                    <div class="sec-head">
                        <span class="sec-ico ico-job"><i class="bi bi-briefcase"></i></span>
                        <h6>Employment &amp; Financial Details</h6>
                    </div>
                    <div class="sec-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="employment_type">Employment Type <span class="req">*</span></label>
                                <select name="employment_type" id="employment_type" data-draft-optional="1" class="form-select" data-search="false" required>
                                    <option value="">Select</option>
                                    @foreach($employmentTypes as $t)
                                        <option value="{{ $t }}" @selected(old('employment_type') === $t)>{{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="monthly_income">Monthly Income <span class="req">*</span></label>
                                <div class="ld-input-ico">
                                    <span class="rupee">₹</span>
                                    <input type="text" inputmode="decimal" data-decimals="2" name="monthly_income" id="monthly_income" data-draft-optional="1"
                                           class="form-control js-decimal" value="{{ old('monthly_income') }}" placeholder="0.00" required>
                                </div>
                                @error('monthly_income')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="existing_obligations">Existing Obligations</label>
                                <div class="ld-input-ico">
                                    <span class="rupee">₹</span>
                                    <input type="text" inputmode="decimal" data-decimals="2" name="existing_obligations" id="existing_obligations"
                                           class="form-control js-decimal" value="{{ old('existing_obligations') }}" placeholder="0.00">
                                </div>
                                @error('existing_obligations')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="employer_name">Employer / Business Name</label>
                                <input type="text" name="employer_name" id="employer_name" class="form-control"
                                       value="{{ old('employer_name') }}" maxlength="150" placeholder="Company / business name">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="work_experience_years">Work Experience (Years)</label>
                                <input type="text" inputmode="decimal" data-decimals="2" name="work_experience_years" id="work_experience_years"
                                       class="form-control js-decimal" value="{{ old('work_experience_years') }}" placeholder="0">
                                @error('work_experience_years')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="net_income">Net Income</label>
                                <div class="ld-input-ico">
                                    <span class="rupee">₹</span>
                                    <input type="text" inputmode="decimal" data-decimals="2" name="net_income" id="net_income"
                                           class="form-control js-decimal is-readonly" value="{{ old('net_income') }}"
                                           placeholder="Auto-calculated" readonly tabindex="-1">
                                </div>
                                @error('net_income')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-5">
                <div class="sec-card">
                    <div class="sec-head">
                        <span class="sec-ico ico-own"><i class="bi bi-people"></i></span>
                        <h6>Case Ownership</h6>
                    </div>
                    <div class="sec-body">
                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <label class="form-label" for="goal_org_unit_id">Branch <span class="req">*</span></label>
                                <div class="ld-input-ico">
                                    <i class="bi bi-building"></i>
                                    <select name="goal_org_unit_id" id="goal_org_unit_id" data-draft-optional="1" class="form-select" required>
                                        <option value="">Select branch</option>
                                        @foreach($branches as $b)
                                            <option value="{{ $b->id }}" @selected((string) old('goal_org_unit_id', $prefill['goal_org_unit_id'] ?? auth()->user()->goal_org_unit_id) === (string) $b->id)>{{ $b->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('goal_org_unit_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-sm-6" id="bmFieldWrap">
                                <label class="form-label" for="branch_manager_id">Branch Manager <span class="req">*</span></label>
                                <div class="ld-input-ico">
                                    <i class="bi bi-person"></i>
                                    <select name="branch_manager_id" id="branch_manager_id" data-draft-optional="1" class="form-select" required>
                                        <option value="">Select BM</option>
                                        @foreach($branchManagers as $u)
                                            <option value="{{ $u->id }}"
                                                    data-branch-id="{{ $u->goal_org_unit_id ?: '' }}"
                                                    @selected((string) old('branch_manager_id', $prefill['branch_manager_id'] ?? '') === (string) $u->id)>{{ $fmtOwner($u) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="lead-hint" id="bmBranchHint">Select a branch first.</div>
                                @error('branch_manager_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-sm-6" id="ownerFieldWrap">
                                <label class="form-label" for="owner_id">Assigned To (SM/JSM) <span class="req">*</span></label>
                                <div class="ld-input-ico">
                                    <i class="bi bi-person"></i>
                                    <select name="owner_id" id="owner_id" data-draft-optional="1" class="form-select" required>
                                        <option value="">Select owner</option>
                                        @foreach($owners as $u)
                                            <option value="{{ $u->id }}"
                                                    data-branch-id="{{ $u->goal_org_unit_id ?: '' }}"
                                                    data-reports-to="{{ $u->reports_to_user_id ?: '' }}"
                                                    @selected((string) old('owner_id', $prefill['owner_id'] ?? auth()->id()) === (string) $u->id)>{{ $fmtOwner($u) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="lead-hint" id="ownerBranchHint">Select Branch Manager next to list SM / JSM / SSM under them.</div>
                                @error('owner_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label">Case Reference No.</label>
                                <input type="text" class="form-control is-readonly" value="(Auto Generate)" disabled>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- BM Recommendation (only when present) --}}
        <div class="sec-card d-none" id="bmRecoCard">
            <div class="sec-head">
                <span class="sec-ico ico-amber"><i class="bi bi-lightbulb"></i></span>
                <h6>BM Recommendation</h6>
            </div>
            <div class="sec-body">
                <div class="bm-reco-grid">
                    <div class="bm-reco-cell">
                        <div class="k">Recommended Amount</div>
                        <div class="v" id="bmRecoAmount">—</div>
                    </div>
                    <div class="bm-reco-cell">
                        <div class="k">Recommended By</div>
                        <div class="v" id="bmRecoBy">—</div>
                    </div>
                    <div class="bm-reco-cell">
                        <div class="k">Recommendation Date</div>
                        <div class="v" id="bmRecoDate">—</div>
                    </div>
                    <div class="bm-reco-cell">
                        <div class="k">Remarks / Status</div>
                        <div class="v muted" id="bmRecoRemarks">—</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Documents --}}
        <div class="sec-card">
            <div class="sec-head">
                <span class="sec-ico ico-doc"><i class="bi bi-file-earmark-check"></i></span>
                <h6>Documents</h6>
                <span class="sec-note">From product config</span>
            </div>
            <div class="sec-body">
                <p class="lead-hint mb-3">Document types load from product configuration. Uploads are stored privately. Verification is completed in Case Details — uploads stay <strong>Pending</strong> until reviewed.</p>
                @error('documents')<div class="alert alert-danger py-2 small">{{ $message }}</div>@enderror
                <div class="table-responsive">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>Document Type</th>
                                <th style="width:120px">Requirement</th>
                                <th style="width:220px">Upload</th>
                                <th style="width:120px">Status</th>
                            </tr>
                        </thead>
                        <tbody id="docChecklistBody">
                            <tr><td colspan="4"><div class="doc-empty"><i class="bi bi-folder2-open"></i>Select a product to load required documents.</div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="wiz-footer">
            <div class="wiz-footer-hint">Draft saves progress. Create Case validates required fields and opens the case workspace.</div>
            <div class="wiz-footer-actions">
                <a href="{{ route('goal.cases.index') }}" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-draft" data-action="draft" id="btnDraft"><i class="bi bi-floppy"></i> Save as Draft</button>
                <button type="submit" class="btn-next" data-action="next" id="btnNext"><i class="bi bi-check2-circle"></i> Create Case</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var page = document.querySelector('.lc-create');
    var form = document.getElementById('lcCreateForm');
    if (!page || !form) return;

    var lookupUrl = page.getAttribute('data-lookup-url');
    var productDocsUrl = page.getAttribute('data-product-docs-url');
    var msgEl = document.getElementById('lookupMsg');
    var leadIdEl = document.getElementById('goal_loan_lead_id');
    var saveActionEl = document.getElementById('save_action');
    var submitting = false;
    var prefill = null;
    try { prefill = JSON.parse(page.getAttribute('data-prefill') || 'null'); } catch (e) { prefill = null; }

    form.querySelectorAll('[data-action]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var action = btn.getAttribute('data-action') || 'draft';
            if (saveActionEl) saveActionEl.value = action;
            form.querySelectorAll('[data-draft-optional]').forEach(function (el) {
                if (action === 'draft') {
                    el.dataset.wasRequired = el.required ? '1' : '0';
                    el.required = false;
                } else {
                    el.required = true;
                }
            });
        });
    });

    form.addEventListener('submit', function (e) {
        if (submitting) {
            e.preventDefault();
            return;
        }
        syncPreferredContactTime();
        var consent = document.getElementById('consent_recorded');
        var consentErr = document.getElementById('consentClientError');
        if (!consent || !consent.checked) {
            e.preventDefault();
            if (consentErr) consentErr.classList.remove('d-none');
            if (consent) {
                consent.focus();
                consent.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }
        if (consentErr) consentErr.classList.add('d-none');
        submitting = true;
        form.querySelectorAll('button[type="submit"]').forEach(function (b) { b.disabled = true; });
    });

    setMsg('Optional — pick a Qualified lead without a case, or leave blank to create a new customer.', null);

    // Cascade: Branch ? Branch Manager ? Assigned To (reports to BM).
    // Keep Select2 design: destroy ? rebuild options ? re-init.
    var peopleCaches = {};
    function cachePeopleSelect(selectId) {
        var sel = document.getElementById(selectId);
        if (!sel || peopleCaches[selectId]) return;
        peopleCaches[selectId] = Array.prototype.map.call(sel.options, function (opt) {
            return {
                value: opt.value,
                text: opt.text,
                branchId: String(opt.getAttribute('data-branch-id') || ''),
                reportsTo: String(opt.getAttribute('data-reports-to') || '')
            };
        });
    }
    function destroySelect2(el) {
        if (!el || !window.jQuery || !jQuery.fn.select2) return;
        var $el = jQuery(el);
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
    }
    function refreshSelect2(el) {
        if (!el || !window.jQuery || !jQuery.fn.select2) return;
        var $el = jQuery(el);
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.trigger('change.select2');
            return;
        }
        if (window.IsarvaSelect2 && typeof window.IsarvaSelect2.initOne === 'function') {
            window.IsarvaSelect2.initOne(el);
        } else {
            $el.select2({
                width: '100%',
                allowClear: true,
                placeholder: $el.find('option[value=""]').first().text() || 'Select',
                dropdownParent: jQuery(document.body)
            });
        }
    }
    function rebuildSelect(selectId, matcher, preferredValue) {
        var sel = document.getElementById(selectId);
        if (!sel || !peopleCaches[selectId]) return 0;
        var prev = preferredValue != null ? String(preferredValue) : String(sel.value || '');
        destroySelect2(sel);
        var placeholder = peopleCaches[selectId].find(function (o) { return !o.value; });
        sel.innerHTML = '';
        if (placeholder) {
            var ph = document.createElement('option');
            ph.value = '';
            ph.textContent = placeholder.text || 'Select';
            sel.appendChild(ph);
        }
        var matchCount = 0;
        peopleCaches[selectId].forEach(function (o) {
            if (!o.value) return;
            if (matcher && !matcher(o)) return;
            var opt = document.createElement('option');
            opt.value = o.value;
            opt.textContent = o.text;
            opt.setAttribute('data-branch-id', o.branchId);
            if (o.reportsTo) opt.setAttribute('data-reports-to', o.reportsTo);
            sel.appendChild(opt);
            matchCount += 1;
        });
        if (prev && Array.prototype.some.call(sel.options, function (o) { return o.value === prev; })) {
            sel.value = prev;
        } else {
            sel.value = '';
        }
        refreshSelect2(sel);
        return matchCount;
    }
    function syncOwnershipCascade(preferredBm, preferredOwner) {
        var branchEl = document.getElementById('goal_org_unit_id');
        var bmEl = document.getElementById('branch_manager_id');
        var ownerEl = document.getElementById('owner_id');
        var bmHint = document.getElementById('bmBranchHint');
        var ownerHint = document.getElementById('ownerBranchHint');
        var branchId = branchEl ? String(branchEl.value || '') : '';

        cachePeopleSelect('branch_manager_id');
        cachePeopleSelect('owner_id');

        if (!branchId) {
            rebuildSelect('branch_manager_id', function () { return false; }, '');
            rebuildSelect('owner_id', function () { return false; }, '');
            if (bmEl) bmEl.disabled = true;
            if (ownerEl) ownerEl.disabled = true;
            refreshSelect2(bmEl);
            refreshSelect2(ownerEl);
            if (bmHint) { bmHint.textContent = 'Select a branch first.'; bmHint.className = 'lead-hint'; }
            if (ownerHint) { ownerHint.textContent = 'Select Branch Manager next to list SM / JSM / SSM under them.'; ownerHint.className = 'lead-hint'; }
            return;
        }

        if (bmEl) bmEl.disabled = false;
        var bmCount = rebuildSelect('branch_manager_id', function (o) {
            return o.branchId === branchId;
        }, preferredBm);

        if (bmCount === 1 && bmEl && !bmEl.value) {
            bmEl.value = bmEl.options[1] ? bmEl.options[1].value : '';
            refreshSelect2(bmEl);
        }

        var bmId = bmEl ? String(bmEl.value || '') : '';
        if (bmHint) {
            bmHint.textContent = bmCount
                ? ('Showing ' + bmCount + ' Branch Manager(s) for this branch.')
                : 'No Branch Manager found for this branch.';
            bmHint.className = 'lead-hint' + (bmCount ? ' ok' : ' err');
        }

        if (!bmId) {
            rebuildSelect('owner_id', function () { return false; }, '');
            if (ownerEl) ownerEl.disabled = true;
            refreshSelect2(ownerEl);
            if (ownerHint) {
                ownerHint.textContent = 'Select Branch Manager next to list SM / JSM / SSM under them.';
                ownerHint.className = 'lead-hint';
            }
            return;
        }

        if (ownerEl) ownerEl.disabled = false;
        var ownerCount = rebuildSelect('owner_id', function (o) {
            return o.branchId === branchId && o.reportsTo === bmId;
        }, preferredOwner);

        if (ownerHint) {
            ownerHint.textContent = ownerCount
                ? ('Showing ' + ownerCount + ' sales user(s) under this Branch Manager.')
                : 'No SM / JSM / SSM report to this Branch Manager.';
            ownerHint.className = 'lead-hint' + (ownerCount ? ' ok' : ' err');
        }
    }

    function bindOwnershipEvents() {
        var $ = window.jQuery;
        if ($ && $.fn.select2) {
            $(document).off('change.lcOwnerCascade', '#goal_org_unit_id, #branch_manager_id');
            $(document).on('change.lcOwnerCascade', '#goal_org_unit_id', function () {
                syncOwnershipCascade('', '');
            });
            $(document).on('change.lcOwnerCascade', '#branch_manager_id', function () {
                syncOwnershipCascade(this.value, '');
            });
        } else {
            var branchSelect = document.getElementById('goal_org_unit_id');
            var bmSelect = document.getElementById('branch_manager_id');
            if (branchSelect) {
                branchSelect.addEventListener('change', function () { syncOwnershipCascade('', ''); });
            }
            if (bmSelect) {
                bmSelect.addEventListener('change', function () { syncOwnershipCascade(bmSelect.value, ''); });
            }
        }
    }

    function bootOwnershipCascade() {
        bindOwnershipEvents();
        // Ensure Select2 is on ownership selects first, then cascade.
        ['goal_org_unit_id', 'branch_manager_id', 'owner_id'].forEach(function (id) {
            refreshSelect2(document.getElementById(id));
        });
        syncOwnershipCascade(
            document.getElementById('branch_manager_id') ? document.getElementById('branch_manager_id').value : '',
            document.getElementById('owner_id') ? document.getElementById('owner_id').value : ''
        );
    }
    if (window.jQuery) {
        jQuery(function () { setTimeout(bootOwnershipCascade, 50); });
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { setTimeout(bootOwnershipCascade, 50); });
    } else {
        setTimeout(bootOwnershipCascade, 50);
    }

    page.querySelectorAll('.js-mobile').forEach(function (el) {
        el.addEventListener('input', function () {
            el.value = String(el.value || '').replace(/\D+/g, '').slice(0, 10);
        });
    });
    page.querySelectorAll('.js-int').forEach(function (el) {
        el.addEventListener('input', function () {
            el.value = String(el.value || '').replace(/\D+/g, '').slice(0, 3);
        });
    });

    function setMsg(text, ok) {
        if (!msgEl) return;
        msgEl.textContent = text || '';
        msgEl.className = 'lead-hint ' + (ok === true ? 'ok' : ok === false ? 'err' : '');
    }

    function syncConsentLabel() {
        var consent = document.getElementById('consent_recorded');
        var label = document.getElementById('consentLabelText');
        var consentErr = document.getElementById('consentClientError');
        var box = document.getElementById('consentBox');
        if (!consent || !label) return;
        label.textContent = consent.checked ? 'Consent recorded' : 'Not recorded';
        if (box) box.classList.toggle('is-on', !!consent.checked);
        if (consent.checked && consentErr) consentErr.classList.add('d-none');
    }
    var consentEl = document.getElementById('consent_recorded');
    if (consentEl) consentEl.addEventListener('change', syncConsentLabel);
    var consentBox = document.getElementById('consentBox');
    if (consentBox && consentEl) {
        consentBox.addEventListener('click', function (e) {
            if (e.target === consentEl || (e.target.closest && e.target.closest('.consent-toggle'))) return;
            consentEl.checked = !consentEl.checked;
            consentEl.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    function syncPreferredContactTime() {
        var fromEl = document.getElementById('preferred_contact_from');
        var toEl = document.getElementById('preferred_contact_to');
        var hidden = document.getElementById('preferred_contact_time');
        if (!hidden) return;
        var from = (fromEl && fromEl.value ? fromEl.value : '').trim();
        var to = (toEl && toEl.value ? toEl.value : '').trim();
        if (from && to) hidden.value = from + ' - ' + to;
        else if (from) hidden.value = from;
        else if (to) hidden.value = to;
        else hidden.value = '';
    }
    ['preferred_contact_from', 'preferred_contact_to'].forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('change', syncPreferredContactTime);
        el.addEventListener('input', syncPreferredContactTime);
    });
    if (window.IsarvaDatepicker) {
        window.IsarvaDatepicker.init(page);
    }
    syncPreferredContactTime();

    function fillSelect(id, value) {
        var el = document.getElementById(id);
        if (!el || value == null || value === '') return;
        el.value = String(value);
        if (window.jQuery && jQuery.fn.select2 && jQuery(el).hasClass('select2-hidden-accessible')) {
            jQuery(el).val(String(value)).trigger('change');
        } else {
            el.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function renderDocs(docs) {
        var body = document.getElementById('docChecklistBody');
        if (!body) return;
        docs = docs || [];
        if (!docs.length) {
            body.innerHTML = '<tr><td colspan="4"><div class="doc-empty"><i class="bi bi-inbox"></i>No document requirements configured for this product.</div></td></tr>';
            return;
        }
        body.innerHTML = docs.map(function (doc, i) {
            var req = doc.mandatory
                ? '<span class="doc-badge req">Mandatory</span>'
                : '<span class="doc-badge">Optional</span>';
            return '<tr>' +
                '<td><strong>' + escapeHtml(doc.label) + '</strong>' +
                '<input type="hidden" name="documents[' + i + '][key]" value="' + escapeHtml(doc.key) + '">' +
                '<input type="hidden" name="documents[' + i + '][label]" value="' + escapeHtml(doc.label) + '">' +
                '<input type="hidden" name="documents[' + i + '][mandatory]" value="' + (doc.mandatory ? '1' : '0') + '">' +
                '</td>' +
                '<td>' + req + '</td>' +
                '<td><input type="file" name="documents[' + i + '][file]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.webp"></td>' +
                '<td><span class="doc-badge">Pending</span></td>' +
                '</tr>';
        }).join('');
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatInr(v) {
        if (v == null || v === '') return '—';
        var n = Number(v);
        if (isNaN(n)) return String(v);
        return '₹ ' + n.toLocaleString('en-IN', { maximumFractionDigits: 2 });
    }

    function showBmReco(reco) {
        var card = document.getElementById('bmRecoCard');
        if (!card) return;
        if (!reco) {
            card.classList.add('d-none');
            return;
        }
        card.classList.remove('d-none');
        document.getElementById('bmRecoAmount').textContent = formatInr(reco.amount);
        document.getElementById('bmRecoBy').textContent = reco.by || '—';
        document.getElementById('bmRecoDate').textContent = reco.date || '—';
        document.getElementById('bmRecoRemarks').textContent = reco.remarks || reco.status || '—';
    }

    function syncNetIncome() {
        var income = parseFloat((document.getElementById('monthly_income') || {}).value || '0') || 0;
        var obl = parseFloat((document.getElementById('existing_obligations') || {}).value || '0') || 0;
        var netEl = document.getElementById('net_income');
        if (!netEl) return;
        netEl.value = Math.max(0, income - obl).toFixed(2);
    }
    ['monthly_income', 'existing_obligations'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', syncNetIncome);
    });

    function applyLead(lead) {
        if (!lead) return;
        if (leadIdEl) leadIdEl.value = lead.id || '';
        setVal('customer_ref', lead.customer_ref);
        setVal('customer_name', lead.customer_name);
        setVal('mobile', lead.mobile);
        setVal('email', lead.email);
        setVal('loan_amount', lead.loan_amount);
        fillSelect('product', lead.product);
        fillSelect('goal_org_unit_id', lead.goal_org_unit_id);
        syncOwnershipCascade(lead.branch_manager_id || '', lead.owner_id || '');
        var consent = document.getElementById('consent_recorded');
        if (consent) {
            consent.checked = !!lead.consent_recorded;
            syncConsentLabel();
        }
        showBmReco(lead.bm_reco || null);
        renderDocs(lead.documents || []);
        syncNetIncome();
        if (lead.can_create === false) {
            setMsg(lead.eligibility_message || ('Lead loaded, but status ' + (lead.status || '') + ' cannot create a case yet.'), false);
        } else {
            setMsg('Lead ' + (lead.ref_no || '') + ' loaded — ' + (lead.status || '') + '.', true);
        }
        loadProductDocs(lead.product);
    }

    function setVal(id, value) {
        var el = document.getElementById(id);
        if (!el || value == null) return;
        el.value = value;
    }

    function loadProductDocs(product) {
        if (!product) {
            renderDocs([]);
            return;
        }
        fetch(productDocsUrl + '?product=' + encodeURIComponent(product), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (data && data.ok) renderDocs(data.documents || []);
        }).catch(function () {});
    }

    var productEl = document.getElementById('product');
    if (productEl) {
        productEl.addEventListener('change', function () {
            loadProductDocs(productEl.value);
        });
    }

    function loadLeadById(id) {
        if (!id) return;
        setMsg('Loading lead…', true);
        fetch(lookupUrl + '?lead_id=' + encodeURIComponent(id), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) {
            return r.json().then(function (data) { return { status: r.status, data: data }; });
        }).then(function (res) {
            if (!res.data || !res.data.ok) {
                setMsg((res.data && res.data.message) || 'Unable to load lead.', false);
                if (leadIdEl) leadIdEl.value = '';
                return;
            }
            applyLead(res.data.lead);
        }).catch(function () {
            setMsg('Lookup failed. Try again.', false);
        });
    }

    if (window.jQuery && jQuery.fn.select2) {
        var $lead = jQuery('#lead_select');
        $lead.select2({
            width: '100%',
            placeholder: 'Search lead reference, name or mobile',
            allowClear: true,
            dropdownParent: jQuery(document.body),
            ajax: {
                url: lookupUrl,
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term || '' }; },
                processResults: function (data) {
                    return { results: (data && data.results) ? data.results : [] };
                }
            },
            minimumInputLength: 0
        });

        $lead.on('select2:select', function (e) {
            var id = e.params.data && e.params.data.id;
            loadLeadById(id);
        });
        $lead.on('select2:clear', function () {
            if (leadIdEl) leadIdEl.value = '';
            showBmReco(null);
            setMsg('Lead cleared. Fill details below to create a new customer case.', true);
        });

        if (prefill && prefill.id) {
            var option = new Option(prefill.ref_no + ' — ' + prefill.customer_name + ' — ' + (prefill.mobile || '—'), prefill.id, true, true);
            $lead.append(option).trigger('change');
            applyLead(prefill);
        } else if (leadIdEl.value) {
            loadLeadById(leadIdEl.value);
        } else if (productEl && productEl.value) {
            loadProductDocs(productEl.value);
        }
    } else if (prefill) {
        applyLead(prefill);
    }
})();
</script>
@endpush
