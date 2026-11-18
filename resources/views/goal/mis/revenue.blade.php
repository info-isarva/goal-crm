@extends('layouts.app')

@push('styles')
@include('goal.partials.pd_hub_styles')
<style>
.pd-page.mis-page .mis-filters{
    display:grid;grid-template-columns:minmax(180px,1fr) auto;
    gap:10px;align-items:end;margin-bottom:.85rem;padding:.85rem 1rem;
    background:#fff;border:1px solid #e8edf5;border-radius:14px;
    box-shadow:0 6px 16px rgba(148,163,184,.08);
}
.pd-page.mis-page .mis-filters label{
    display:block;font-size:.72rem;font-weight:700;color:#64748b;margin:0 0 4px;
}
.pd-page.mis-page .mis-filters .form-control{
    min-height:38px;height:38px;border-radius:9px;border:1px solid #d1d5db;font-size:.84rem;
}
.pd-page.mis-page .mis-filter-actions{display:flex;gap:8px}
@media (max-width:767.98px){
    .pd-page.mis-page .mis-filters{grid-template-columns:1fr}
}
</style>
@endpush

@section('content')
@php
    $fmtInr = fn ($n) => $n !== null && $n !== '' ? '₹ '.number_format((float) $n, 0) : '—';
@endphp
<div class="container-fluid p-4 pd-page mis-page">
    <div class="pd-hero">
        <div>
            <ul class="pd-crumbs">
                <li><a href="{{ url('/') }}">Home</a></li>
                <li class="sep">›</li>
                <li>Performance</li>
                <li class="sep">›</li>
                <li>Revenue Ledger</li>
            </ul>
            <h1 class="pd-title">Revenue Ledger</h1>
            <p class="pd-sub">REV-01 credits created when disbursements are verified (PD-01).</p>
        </div>
        <div class="pd-hero-actions">
            <a href="{{ route('goal.branch-mis') }}" class="pd-link-btn"><i class="bi bi-table"></i> Branch MIS</a>
            <a href="{{ route('goal.revenue-ledger.export', request()->query()) }}" class="pd-link-btn primary"><i class="bi bi-download"></i> Export</a>
        </div>
    </div>

    <div class="pd-kpi-row" style="grid-template-columns:repeat(2,minmax(0,1fr))">
        <div class="pd-kpi tone-blue">
            <p class="pd-kpi-label">Entries</p>
            <p class="pd-kpi-value">{{ number_format($kpis['total'] ?? 0) }}</p>
        </div>
        <div class="pd-kpi tone-green">
            <p class="pd-kpi-label">Recognized amount</p>
            <p class="pd-kpi-value" style="font-size:1.15rem">{{ $fmtInr($kpis['amount'] ?? 0) }}</p>
        </div>
    </div>

    @if(!empty($rollups))
        <div class="pd-card" style="margin-bottom:.85rem">
            <div class="pd-card-head"><h5>Org rollups (REV-01)</h5></div>
            <div class="pd-table-wrap">
                <table class="pd-table align-middle">
                    <thead><tr><th>Type</th><th>Org unit</th><th>Amount</th></tr></thead>
                    <tbody>
                    @foreach($rollups as $r)
                        <tr>
                            <td data-label="Type">{{ strtoupper($r['type']) }}</td>
                            <td data-label="Org">{{ $r['name'] }}</td>
                            <td data-label="Amount" class="pd-amt">{{ $fmtInr($r['amount']) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <form method="GET" action="{{ route('goal.revenue-ledger') }}" class="mis-filters">
        <div>
            <label for="revQ">Search</label>
            <input id="revQ" type="search" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Case / customer / note">
        </div>
        <div class="mis-filter-actions">
            <button type="submit" class="pd-btn" style="height:38px"><i class="bi bi-funnel"></i> Apply</button>
            <a href="{{ route('goal.revenue-ledger') }}" class="pd-btn-ghost" style="height:38px">Reset</a>
        </div>
    </form>

    <div class="pd-card" style="margin-bottom:.85rem">
        <div class="pd-card-head"><h5>REV-03 adjustment / reversal</h5></div>
        <form method="POST" action="{{ route('goal.revenue-ledger.adjust') }}" class="mis-filters" style="margin:0;box-shadow:none;border:0;padding:.5rem 1rem 1rem;grid-template-columns:minmax(160px,1.4fr) 120px 140px minmax(140px,1fr) auto">
            @csrf
            <div>
                <label for="adjCase">Case</label>
                <select id="adjCase" name="goal_loan_case_id" class="form-select" required>
                    <option value="">Select case…</option>
                    @foreach(($cases ?? []) as $c)
                        <option value="{{ $c->id }}" @selected((string) old('goal_loan_case_id') === (string) $c->id)>{{ $c->case_no }} · {{ $c->customer_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="adjAmt">Amount</label>
                <input id="adjAmt" type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required>
            </div>
            <div>
                <label for="adjType">Type</label>
                <select id="adjType" name="entry_type" class="form-select" required>
                    <option value="adjustment" @selected(old('entry_type') === 'adjustment')>Adjustment</option>
                    <option value="reversal" @selected(old('entry_type') === 'reversal')>Reversal</option>
                </select>
            </div>
            <div>
                <label for="adjNote">Note</label>
                <input id="adjNote" type="text" name="note" class="form-control" value="{{ old('note') }}" required maxlength="255">
            </div>
            <div class="mis-filter-actions" style="align-self:end">
                <button type="submit" class="pd-btn" style="height:38px">Post</button>
            </div>
        </form>
        @error('amount')<div class="text-danger small px-3 pb-2">{{ $message }}</div>@enderror
        @error('goal_loan_case_id')<div class="text-danger small px-3 pb-2">{{ $message }}</div>@enderror
        @error('note')<div class="text-danger small px-3 pb-2">{{ $message }}</div>@enderror
    </div>

    <div class="pd-card">
        <div class="pd-card-head">
            <h5>Revenue entries</h5>
        </div>
        <div class="pd-table-wrap">
            <table class="pd-table align-middle">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Case</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($entries as $e)
                    <tr>
                        <td data-label="When">{{ optional($e->created_at)->format('d M Y, H:i') ?: '—' }}</td>
                        <td data-label="Case">
                            <a class="pd-case" href="{{ route('goal.cases.show', $e->goal_loan_case_id) }}">{{ $e->loanCase?->case_no ?: '—' }}</a>
                        </td>
                        <td data-label="Customer"><span class="pd-cust">{{ $e->loanCase?->customer_name ?: '—' }}</span></td>
                        <td data-label="Type">{{ $e->entry_type ?: 'recognized' }}</td>
                        <td data-label="Amount" class="pd-amt">{{ $fmtInr($e->amount) }}</td>
                        <td data-label="Note">{{ $e->note ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="pd-col-empty">
                            <div class="pd-empty">
                                <i class="bi bi-currency-rupee"></i>
                                No revenue entries yet. PD-01 creates REV-01 on verified disbursement.
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($entries->hasPages())
            <div class="pd-foot">
                <span>Showing {{ $entries->firstItem() }}–{{ $entries->lastItem() }} of {{ $entries->total() }}</span>
                <div>{{ $entries->links() }}</div>
            </div>
        @endif
    </div>
</div>
@endsection
