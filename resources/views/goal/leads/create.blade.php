@extends('layouts.app')

@push('styles')
<style>
.ll-create{color:#1e293b}
.ll-create .page-hero{
    display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;
    gap:1rem;margin-bottom:1rem;
}
.ll-create .page-hero-left{display:flex;align-items:flex-start;gap:14px;min-width:0}
.ll-create .page-hero-icon{
    width:52px;height:52px;border-radius:16px;flex-shrink:0;
    background:linear-gradient(145deg,var(--theme-primary) 0%,var(--theme-primary-hover) 55%,var(--theme-accent) 100%);
    color:#fff;display:inline-flex;align-items:center;justify-content:center;
    font-size:1.35rem;box-shadow:0 12px 24px var(--theme-primary-ring);
}
.ll-create .crumbs{
    display:flex;flex-wrap:wrap;align-items:center;gap:6px;
    margin:0 0 8px;padding:0;list-style:none;font-size:.78rem;color:#64748b;
}
.ll-create .crumbs a{color:var(--theme-primary);text-decoration:none;font-weight:600}
.ll-create .crumbs a:hover{text-decoration:underline}
.ll-create .crumbs .sep{opacity:.5}
.ll-create .page-title{font-size:1.45rem;font-weight:800;color:#0f172a;margin:0;line-height:1.2;letter-spacing:-.02em}
.ll-create .page-sub{color:#64748b;font-size:.88rem;margin-top:4px;line-height:1.4}
.ll-create .btn-back{
    height:40px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#334155;
    padding:0 14px;font-weight:650;font-size:.84rem;text-decoration:none;
    display:inline-flex;align-items:center;gap:6px;
}
.ll-create .btn-back:hover{background:#f8fafc;color:#0f172a}
.ll-create .form-card{
    background:#fff;border:1px solid #e8edf5;border-radius:18px;overflow:hidden;
    box-shadow:0 10px 28px rgba(148,163,184,.14);
}
.ll-create .form-card-head{
    display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.5rem;
    padding:.9rem 1.15rem;border-bottom:1px solid #eef2f7;
}
.ll-create .form-card-head h5{margin:0;font-size:1.02rem;font-weight:800;color:#0f172a}
.ll-create .form-card-head .hint{font-size:.78rem;color:#94a3b8}
.ll-create .form-card-body{padding:1.1rem 1.15rem 1.25rem}
.ll-create .sec-label{
    font-size:.72rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase;
    color:#64748b;margin:0 0 .65rem;
}
.ll-create .sec-block + .sec-block{margin-top:1.15rem;padding-top:1rem;border-top:1px dashed #e2e8f0}
.ll-create .form-label{font-size:.82rem;font-weight:650;color:#334155;margin-bottom:.35rem}
.ll-create .form-control,.ll-create .form-select{
    min-height:40px;border-radius:10px;border:1px solid #d1d5db;font-size:.9rem;
}
.ll-create .form-control:focus,.ll-create .form-select:focus{
    border-color:var(--theme-300);box-shadow:0 0 0 3px var(--theme-primary-ring);
}
.ll-create textarea.form-control{min-height:78px}
.ll-create .req{color:#dc2626}
.ll-create .form-actions{
    display:flex;flex-wrap:wrap;gap:8px;margin-top:1.15rem;padding-top:1rem;
    border-top:1px solid #eef2f7;
}
.ll-create .btn-save{
    height:42px;border:0;border-radius:12px;padding:0 1.15rem;font-weight:700;font-size:.9rem;
    display:inline-flex;align-items:center;gap:.45rem;cursor:pointer;color:#fff;
    background:linear-gradient(90deg,var(--theme-primary),var(--theme-primary-hover));
    box-shadow:0 10px 20px var(--theme-primary-ring);
}
.ll-create .btn-save:hover{filter:brightness(1.05);color:#fff}
.ll-create .btn-cancel{
    height:42px;border-radius:12px;border:1px solid #e2e8f0;background:#fff;color:#475569;
    padding:0 1rem;font-weight:650;font-size:.88rem;text-decoration:none;
    display:inline-flex;align-items:center;gap:.4rem;
}
.ll-create .btn-cancel:hover{background:#f8fafc;color:#0f172a}
.ll-create .channel-banner{
    display:flex;align-items:flex-start;gap:10px;margin-bottom:1rem;padding:.75rem .9rem;
    border-radius:12px;background:#f5f3ff;border:1px solid #e9e5ff;color:#5b21b6;font-size:.84rem;line-height:1.4;
}
.ll-create .channel-banner i{font-size:1.1rem;margin-top:1px}
@media (max-width:767.98px){
    .ll-create.container-fluid{padding:.75rem .7rem 1.1rem !important}
    .ll-create .page-title{font-size:1.2rem}
    .ll-create .page-hero{flex-direction:column;align-items:stretch}
    .ll-create .btn-back{width:100%;justify-content:center}
    .ll-create .form-card-body{padding:.9rem .85rem 1rem}
    .ll-create .btn-save,.ll-create .btn-cancel{width:100%;justify-content:center}
}
</style>
@endpush

@section('content')
@php
    $isChannel = !empty($channel);
    $title = $isChannel ? 'Channel Lead' : 'Create Loan Lead';
@endphp
<div class="container-fluid p-4 ll-create">
    <div class="page-hero">
        <div class="page-hero-left">
            <span class="page-hero-icon"><i class="bi bi-person-plus"></i></span>
            <div>
                <ul class="crumbs">
                    <li><a href="{{ url('/') }}">Home</a></li>
                    <li class="sep">›</li>
                    <li><a href="{{ route('goal.leads.index') }}">Loan Leads</a></li>
                    <li class="sep">›</li>
                    <li>{{ $isChannel ? 'Channel' : 'Create' }}</li>
                </ul>
                <h1 class="page-title">{{ $title }}</h1>
                <p class="page-sub">
                    {{ $isChannel
                        ? 'Capture a channel-sourced lead for BM allocation.'
                        : 'Capture customer intent before allocation and case conversion.' }}
                </p>
            </div>
        </div>
        <a href="{{ route('goal.leads.index') }}" class="btn-back"><i class="bi bi-arrow-left"></i> Back to leads</a>
    </div>

    <div class="form-card">
        <div class="form-card-head">
            <h5>Lead details</h5>
            <span class="hint">Fields marked <span class="req">*</span> are required</span>
        </div>
        <div class="form-card-body">
            @if($isChannel)
                <div class="channel-banner">
                    <i class="bi bi-diagram-3"></i>
                    <div>Channel entry mode — source is set to <strong>channel</strong> automatically.</div>
                </div>
            @endif

            <form method="POST" action="{{ route('goal.leads.store') }}">
                @csrf
                @if($isChannel)
                    <input type="hidden" name="channel" value="1">
                    <input type="hidden" name="source" value="channel">
                @endif

                <div class="sec-block">
                    <p class="sec-label">Customer</p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="customer_name">Customer name <span class="req">*</span></label>
                            <input id="customer_name" name="customer_name" class="form-control" value="{{ old('customer_name') }}" required maxlength="150" placeholder="Full name">
                            @error('customer_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="mobile">Mobile</label>
                            <input id="mobile" name="mobile" class="form-control" value="{{ old('mobile') }}" placeholder="10-digit mobile">
                            @error('mobile')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="email">Email</label>
                            <input id="email" name="email" type="email" class="form-control" value="{{ old('email') }}" placeholder="optional@email.com">
                            @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="sec-block">
                    <p class="sec-label">Loan intent</p>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label" for="lead_type">Lead type <span class="req">*</span></label>
                            <select id="lead_type" name="lead_type" class="form-select" required>
                                <option value="immediate" @selected(old('lead_type', 'immediate') === 'immediate')>Immediate</option>
                                <option value="future" @selected(old('lead_type') === 'future')>Future</option>
                            </select>
                            @error('lead_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        @if($isChannel)
                            <div class="col-md-3">
                                <label class="form-label" for="channel_partner">Channel partner</label>
                                <input id="channel_partner" name="channel_partner" class="form-control" value="{{ old('channel_partner') }}" placeholder="Partner name">
                                @error('channel_partner')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label" for="product">Product</label>
                            <input id="product" name="product" class="form-control" value="{{ old('product') }}" placeholder="e.g. Home Loan">
                            @error('product')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="loan_amount">Loan amount</label>
                            <input id="loan_amount" name="loan_amount" type="text" inputmode="decimal" data-decimals="2" class="form-control js-decimal" value="{{ old('loan_amount') }}" placeholder="0.00">
                            @error('loan_amount')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="goal_org_unit_id">Branch</label>
                            <select id="goal_org_unit_id" name="goal_org_unit_id" class="form-select">
                                <option value="">— Select —</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" @selected((string) old('goal_org_unit_id', auth()->user()->goal_org_unit_id) === (string) $b->id)>{{ $b->name }}</option>
                                @endforeach
                            </select>
                            @error('goal_org_unit_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="follow_up_at">Follow-up (Future)</label>
                            <input id="follow_up_at" type="datetime-local" name="follow_up_at" class="form-control" value="{{ old('follow_up_at') }}">
                            @error('follow_up_at')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="sec-block">
                    <p class="sec-label">Notes</p>
                    <label class="form-label" for="notes">Additional notes</label>
                    <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Context for BM / sales...">{{ old('notes') }}</textarea>
                    @error('notes')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-save"><i class="bi bi-check2-circle"></i> Save lead</button>
                    <a href="{{ route('goal.leads.index') }}" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
