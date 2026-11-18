@extends('layouts.app')

@push('styles')
@include('goal.partials.pd_hub_styles')
@endpush

@section('content')
@php
    $fmtPill = function (?string $status) {
        $s = (string) $status;
        return match (true) {
            $s === 'Paid' => 'ok',
            in_array($s, ['Eligible', 'Processing'], true) => 'info',
            $s === 'On Hold' => 'warn',
            $s === 'Exception' => 'bad',
            default => 'slate',
        };
    };
    $fmtInr = function ($n) {
        if ($n === null || $n === '') return '—';
        return '₹ '.number_format((float) $n, 0);
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
                <li>Payouts</li>
            </ul>
            <h1 class="pd-title">Payout Processing</h1>
            <p class="pd-sub">Record receipts and exceptions once payouts become Eligible.</p>
        </div>
        <div class="pd-hero-actions">
            <a href="{{ route('goal.pms.index') }}" class="pd-link-btn"><i class="bi bi-briefcase"></i> PMS</a>
            <a href="{{ route('goal.accounts.index') }}" class="pd-link-btn"><i class="bi bi-journal-check"></i> Accounts</a>
            <a href="{{ route('goal.revenue-ledger') }}" class="pd-link-btn"><i class="bi bi-currency-rupee"></i> Revenue</a>
            <a href="{{ route('goal.payouts.export', request()->query()) }}" class="pd-link-btn primary"><i class="bi bi-download"></i> Export</a>
        </div>
    </div>

    <div class="pd-kpi-row">
        <a class="pd-kpi tone-amber {{ $statusFilter === 'on_hold' ? 'is-active' : '' }}" href="{{ route('goal.payouts.index', ['status' => 'on_hold']) }}">
            <p class="pd-kpi-label">On Hold</p>
            <p class="pd-kpi-value">{{ number_format($kpis['on_hold'] ?? 0) }}</p>
        </a>
        <a class="pd-kpi tone-blue {{ $statusFilter === 'eligible' ? 'is-active' : '' }}" href="{{ route('goal.payouts.index', ['status' => 'eligible']) }}">
            <p class="pd-kpi-label">Eligible / Processing</p>
            <p class="pd-kpi-value">{{ number_format($kpis['eligible'] ?? 0) }}</p>
        </a>
        <a class="pd-kpi tone-green {{ $statusFilter === 'Paid' ? 'is-active' : '' }}" href="{{ route('goal.payouts.index', ['status' => 'Paid']) }}">
            <p class="pd-kpi-label">Paid</p>
            <p class="pd-kpi-value">{{ number_format($kpis['paid'] ?? 0) }}</p>
        </a>
        <a class="pd-kpi tone-red {{ $statusFilter === 'Exception' ? 'is-active' : '' }}" href="{{ route('goal.payouts.index', ['status' => 'Exception']) }}">
            <p class="pd-kpi-label">Exception</p>
            <p class="pd-kpi-value">{{ number_format($kpis['exception'] ?? 0) }}</p>
        </a>
    </div>

    <form method="GET" action="{{ route('goal.payouts.index') }}" class="pd-filters">
        <div>
            <label for="pay-q">Search</label>
            <input id="pay-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Case or customer">
        </div>
        <div>
            <label for="pay-status">Status</label>
            <select id="pay-status" name="status" class="form-select">
                <option value="">All</option>
                <option value="on_hold" @selected($statusFilter === 'on_hold')>On Hold</option>
                <option value="eligible" @selected($statusFilter === 'eligible')>Eligible / Processing</option>
                <option value="Paid" @selected($statusFilter === 'Paid')>Paid</option>
                <option value="Exception" @selected($statusFilter === 'Exception')>Exception</option>
            </select>
        </div>
        <div class="pd-filter-actions">
            <button type="submit" class="pd-btn"><i class="bi bi-funnel"></i> Filter</button>
            <a href="{{ route('goal.payouts.index') }}" class="pd-btn-ghost">Reset</a>
        </div>
    </form>

    <div class="pd-card">
        <div class="pd-card-head">
            <h5>Payout board</h5>
            @if(($kpis['eligible'] ?? 0) > 0)
                <span class="pd-badge" style="background:#eff6ff;color:#1d4ed8">{{ $kpis['eligible'] }} actionable</span>
            @endif
        </div>
        <div class="pd-table-wrap">
            <table class="pd-table align-middle">
                <thead>
                    <tr>
                        <th>Case</th>
                        <th>Status</th>
                        <th>Expected</th>
                        <th>Received</th>
                        <th>Receipt / Exception</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($payouts as $payout)
                    @php $canReceipt = in_array($payout->status, ['Eligible', 'Processing'], true); @endphp
                    <tr>
                        <td data-label="Case">
                            <a class="pd-case" href="{{ route('goal.cases.show', $payout->goal_loan_case_id) }}">{{ $payout->loanCase?->case_no ?: '—' }}</a>
                            <div class="pd-muted">{{ $payout->loanCase?->customer_name ?: '' }}</div>
                        </td>
                        <td data-label="Status">
                            <span class="pd-pill {{ $fmtPill($payout->status) }}">{{ $payout->status ?: '—' }}</span>
                        </td>
                        <td data-label="Expected" class="pd-amt">{{ $fmtInr($payout->expected_amount) }}</td>
                        <td data-label="Received" class="pd-amt">{{ $fmtInr($payout->received_amount) }}</td>
                        <td class="pd-col-actions" data-label="Actions">
                            <div class="pd-stack-forms">
                                @if($canReceipt)
                                    <form class="pd-action-form" method="POST" action="{{ route('goal.payouts.receipt', $payout) }}">
                                        @csrf
                                        <input type="text" inputmode="decimal" data-decimals="2" name="amount" class="form-control js-decimal" placeholder="Amount" title="Receipt amount" required>
                                        <input type="date" name="received_on" class="form-control" title="Received on" required>
                                        <input name="reference" class="form-control flex-grow" placeholder="Ref" title="Reference">
                                        <input name="difference_reason" class="form-control flex-grow" placeholder="Diff reason (if partial)" title="Required when received &lt; expected" value="{{ $payout->difference_reason }}">
                                        <button type="submit" class="pd-btn" title="Record receipt"><i class="bi bi-plus-lg"></i> Receipt</button>
                                    </form>
                                @else
                                    <div class="pd-muted">Receipts available when status is Eligible / Processing.</div>
                                @endif
                                @if($payout->status !== 'Paid')
                                    <form class="pd-action-form" method="POST" action="{{ route('goal.payouts.exception', $payout) }}">
                                        @csrf
                                        <input name="exception_reason" class="form-control flex-grow" placeholder="Exception reason" title="Exception reason" required>
                                        <button type="submit" class="pd-btn-danger" title="Mark exception"><i class="bi bi-exclamation-triangle"></i> Exception</button>
                                    </form>
                                @endif
                                <a href="{{ route('goal.cases.show', $payout->goal_loan_case_id) }}" class="pd-btn-ghost">Open case</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="pd-col-empty">
                            <div class="pd-empty">
                                <i class="bi bi-inbox"></i>
                                No payouts match. PD-01 creates On Hold payouts when disbursements are verified.
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($payouts->hasPages())
            <div class="pd-foot">{{ $payouts->links() }}</div>
        @endif
    </div>

    <p class="pd-note"><i class="bi bi-info-circle me-1"></i>Partial receipts require a difference reason. When received ≥ expected, status moves to Paid (Matched).</p>
</div>
@endsection
