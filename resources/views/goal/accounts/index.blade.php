@extends('layouts.app')

@push('styles')
@include('goal.partials.pd_hub_styles')
@endpush

@section('content')
@php
    $fmtPill = function (?string $status) {
        $s = (string) $status;
        return match (true) {
            in_array($s, ['Approved', 'Report Entered', 'Eligible', 'Ready'], true) => 'ok',
            in_array($s, ['On Hold', 'Pending', 'Awaiting Report'], true) => 'warn',
            in_array($s, ['Exception', 'Rejected'], true) => 'bad',
            default => 'info',
        };
    };
    $statusFilter = $filters['status'] ?? '';
@endphp
<div class="container-fluid p-4 pd-page">
    <div class="pd-hero">
        <div>
            <ul class="pd-crumbs">
                <li><a href="{{ url('/') }}">Home</a></li>
                <li class="sep">›</li>
                <li>Post-Disbursement</li>
                <li class="sep">›</li>
                <li>Accounts Queue</li>
            </ul>
            <h1 class="pd-title">Accounts Queue</h1>
            <p class="pd-sub">Enter report numbers, then Approve to unlock payout eligibility (PD-01).</p>
        </div>
        <div class="pd-hero-actions">
            <a href="{{ route('goal.pms.index') }}" class="pd-link-btn"><i class="bi bi-briefcase"></i> PMS</a>
            <a href="{{ route('goal.payouts.index') }}" class="pd-link-btn"><i class="bi bi-cash-stack"></i> Payouts</a>
            <a href="{{ route('goal.revenue-ledger') }}" class="pd-link-btn"><i class="bi bi-currency-rupee"></i> Revenue</a>
            <a href="{{ route('goal.accounts.export', request()->query()) }}" class="pd-link-btn primary"><i class="bi bi-download"></i> Export</a>
        </div>
    </div>

    <div class="pd-kpi-row">
        <a class="pd-kpi tone-blue {{ $statusFilter === '' ? 'is-active' : '' }}" href="{{ route('goal.accounts.index') }}">
            <p class="pd-kpi-label">Total</p>
            <p class="pd-kpi-value">{{ number_format($kpis['total'] ?? 0) }}</p>
        </a>
        <a class="pd-kpi tone-amber {{ $statusFilter === 'awaiting' ? 'is-active' : '' }}" href="{{ route('goal.accounts.index', ['status' => 'awaiting']) }}">
            <p class="pd-kpi-label">Awaiting Report</p>
            <p class="pd-kpi-value">{{ number_format($kpis['awaiting'] ?? 0) }}</p>
        </a>
        <a class="pd-kpi tone-slate {{ $statusFilter === 'reported' ? 'is-active' : '' }}" href="{{ route('goal.accounts.index', ['status' => 'reported']) }}">
            <p class="pd-kpi-label">Report Entered</p>
            <p class="pd-kpi-value">{{ number_format($kpis['reported'] ?? 0) }}</p>
        </a>
        <a class="pd-kpi tone-green {{ $statusFilter === 'approved' ? 'is-active' : '' }}" href="{{ route('goal.accounts.index', ['status' => 'approved']) }}">
            <p class="pd-kpi-label">Approved</p>
            <p class="pd-kpi-value">{{ number_format($kpis['approved'] ?? 0) }}</p>
        </a>
    </div>

    <form method="GET" action="{{ route('goal.accounts.index') }}" class="pd-filters">
        <div>
            <label for="acc-q">Search</label>
            <input id="acc-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Case or customer">
        </div>
        <div>
            <label for="acc-status">Status</label>
            <select id="acc-status" name="status" class="form-select">
                <option value="">All</option>
                <option value="awaiting" @selected($statusFilter === 'awaiting')>Awaiting Report</option>
                <option value="reported" @selected($statusFilter === 'reported')>Report Entered</option>
                <option value="approved" @selected($statusFilter === 'approved')>Approved</option>
            </select>
        </div>
        <div class="pd-filter-actions">
            <button type="submit" class="pd-btn"><i class="bi bi-funnel"></i> Filter</button>
            <a href="{{ route('goal.accounts.index') }}" class="pd-btn-ghost">Reset</a>
        </div>
    </form>

    <div class="pd-card">
        <div class="pd-card-head">
            <h5>Report number capture</h5>
            @if(($kpis['awaiting'] ?? 0) > 0)
                <span class="pd-badge">{{ $kpis['awaiting'] }} awaiting</span>
            @endif
        </div>
        <div class="pd-table-wrap">
            <table class="pd-table align-middle">
                <thead>
                    <tr>
                        <th>Case</th>
                        <th>Status</th>
                        <th>Report #</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        <td data-label="Case">
                            <a class="pd-case" href="{{ route('goal.cases.show', $item->goal_loan_case_id) }}">{{ $item->loanCase?->case_no ?: '—' }}</a>
                            <div class="pd-muted">{{ $item->loanCase?->customer_name ?: '' }}</div>
                        </td>
                        <td data-label="Status">
                            <span class="pd-pill {{ $fmtPill($item->status) }}">{{ $item->status ?: '—' }}</span>
                        </td>
                        <td data-label="Report #">
                            <span class="pd-cust">{{ $item->report_number ?: '—' }}</span>
                        </td>
                        <td class="pd-col-actions" data-label="Action">
                            <div class="pd-stack-forms">
                                <form class="pd-action-form" method="POST" action="{{ route('goal.accounts.report', $item) }}">
                                    @csrf
                                    <input name="report_number" class="form-control flex-grow" placeholder="Report number" value="{{ $item->report_number }}" title="Report number" required>
                                    <button type="submit" class="pd-btn" title="Save report number"><i class="bi bi-save"></i> Save</button>
                                </form>
                                @if(filled($item->report_number) && $item->status !== 'Approved')
                                    <form class="pd-action-form" method="POST" action="{{ route('goal.accounts.approve', $item) }}">
                                        @csrf
                                        <button type="submit" class="pd-btn" title="Approve for payout eligibility"><i class="bi bi-shield-check"></i> Approve</button>
                                    </form>
                                @elseif($item->status === 'Approved')
                                    <div class="pd-muted">Approved {{ optional($item->approved_at)->format('d M Y H:i') }}</div>
                                @endif
                                <a href="{{ route('goal.cases.show', $item->goal_loan_case_id) }}" class="pd-btn-ghost">Open case</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="pd-col-empty">
                            <div class="pd-empty">
                                <i class="bi bi-inbox"></i>
                                No accounts items match. Verified disbursements enqueue cases here for report capture.
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
            <div class="pd-foot">{{ $items->links() }}</div>
        @endif
    </div>

    <p class="pd-note"><i class="bi bi-info-circle me-1"></i>Save report number → Approve. Payout leaves On Hold only after PMS Verified + Accounts Approved.</p>
</div>
@endsection
