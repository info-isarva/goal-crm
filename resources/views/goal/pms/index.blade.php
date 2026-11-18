@extends('layouts.app')

@push('styles')
@include('goal.partials.pd_hub_styles')
@endpush

@section('content')
@php
    $fmtPill = function (?string $status) {
        $s = (string) $status;
        return match (true) {
            in_array($s, ['Verified', 'Confirmed'], true) => 'ok',
            in_array($s, ['Follow-up Due', 'Pending', 'Pending Verification'], true) => 'warn',
            in_array($s, ['Closed', 'Failed', 'Exception'], true) => 'bad',
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
                <li>PMS Queue</li>
            </ul>
            <h1 class="pd-title">PMS Verification Queue</h1>
            <p class="pd-sub">Confirm disbursed cases into portfolio and track follow-ups (PD-01 handoff).</p>
        </div>
        <div class="pd-hero-actions">
            <a href="{{ route('goal.sanction-disbursement.index') }}" class="pd-link-btn"><i class="bi bi-bank"></i> Sanction</a>
            <a href="{{ route('goal.accounts.index') }}" class="pd-link-btn"><i class="bi bi-journal-check"></i> Accounts</a>
            <a href="{{ route('goal.payouts.index') }}" class="pd-link-btn"><i class="bi bi-cash-stack"></i> Payouts</a>
            <a href="{{ route('goal.pms.export', request()->query()) }}" class="pd-link-btn primary"><i class="bi bi-download"></i> Export</a>
        </div>
    </div>

    <div class="pd-kpi-row">
        <a class="pd-kpi tone-blue {{ $statusFilter === '' ? 'is-active' : '' }}" href="{{ route('goal.pms.index') }}">
            <p class="pd-kpi-label">Total</p>
            <p class="pd-kpi-value">{{ number_format($kpis['total'] ?? 0) }}</p>
        </a>
        <a class="pd-kpi tone-amber {{ $statusFilter === 'pending' ? 'is-active' : '' }}" href="{{ route('goal.pms.index', ['status' => 'pending']) }}">
            <p class="pd-kpi-label">Pending</p>
            <p class="pd-kpi-value">{{ number_format($kpis['pending'] ?? 0) }}</p>
        </a>
        <a class="pd-kpi tone-green {{ $statusFilter === 'Verified' ? 'is-active' : '' }}" href="{{ route('goal.pms.index', ['status' => 'Verified']) }}">
            <p class="pd-kpi-label">Verified</p>
            <p class="pd-kpi-value">{{ number_format($kpis['verified'] ?? 0) }}</p>
        </a>
        <a class="pd-kpi tone-red {{ $statusFilter === 'Follow-up Due' ? 'is-active' : '' }}" href="{{ route('goal.pms.index', ['status' => 'Follow-up Due']) }}">
            <p class="pd-kpi-label">Follow-up Due</p>
            <p class="pd-kpi-value">{{ number_format($kpis['follow_up'] ?? 0) }}</p>
        </a>
    </div>

    <form method="GET" action="{{ route('goal.pms.index') }}" class="pd-filters">
        <div>
            <label for="pms-q">Search</label>
            <input id="pms-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Case or customer">
        </div>
        <div>
            <label for="pms-status">Status</label>
            <select id="pms-status" name="status" class="form-select">
                <option value="">All</option>
                <option value="pending" @selected($statusFilter === 'pending')>Pending</option>
                <option value="Verified" @selected($statusFilter === 'Verified')>Verified</option>
                <option value="Follow-up Due" @selected($statusFilter === 'Follow-up Due')>Follow-up Due</option>
                <option value="Closed" @selected($statusFilter === 'Closed')>Closed</option>
            </select>
        </div>
        <div class="pd-filter-actions">
            <button type="submit" class="pd-btn"><i class="bi bi-funnel"></i> Filter</button>
            <a href="{{ route('goal.pms.index') }}" class="pd-btn-ghost">Reset</a>
        </div>
    </form>

    <div class="pd-card">
        <div class="pd-card-head">
            <h5>Portfolio verification</h5>
            @if(($kpis['pending'] ?? 0) > 0)
                <span class="pd-badge">{{ $kpis['pending'] }} pending</span>
            @endif
        </div>
        <div class="pd-table-wrap">
            <table class="pd-table align-middle">
                <thead>
                    <tr>
                        <th>Case</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        <td data-label="Case">
                            <a class="pd-case" href="{{ route('goal.cases.show', $item->goal_loan_case_id) }}">{{ $item->loanCase?->case_no ?: '—' }}</a>
                        </td>
                        <td data-label="Customer">
                            <div class="pd-cust">{{ $item->loanCase?->customer_name ?: '—' }}</div>
                        </td>
                        <td data-label="Status">
                            <span class="pd-pill {{ $fmtPill($item->status) }}">{{ $item->status ?: '—' }}</span>
                        </td>
                        <td class="pd-col-actions" data-label="Action">
                            <form class="pd-action-form" method="POST" action="{{ route('goal.pms.verify', $item) }}">
                                @csrf
                                <select name="status" class="form-select" title="Verification status" aria-label="Status">
                                    <option value="Verified" @selected($item->status === 'Verified')>Verified</option>
                                    <option value="Follow-up Due" @selected($item->status === 'Follow-up Due')>Follow-up Due</option>
                                    <option value="Closed" @selected($item->status === 'Closed')>Closed</option>
                                </select>
                                <select name="activity_type" class="form-select" title="Activity" aria-label="Activity">
                                    <option value="">Activity…</option>
                                    <option value="portfolio" @selected($item->activity_type === 'portfolio')>Portfolio</option>
                                    <option value="follow_up" @selected($item->activity_type === 'follow_up')>Follow-up</option>
                                    <option value="repeat_opportunity" @selected($item->activity_type === 'repeat_opportunity')>Repeat opp.</option>
                                    <option value="branch_referral" @selected($item->activity_type === 'branch_referral')>Branch referral</option>
                                </select>
                                <input type="datetime-local" name="follow_up_at" class="form-control" title="Follow-up due"
                                       value="{{ optional($item->follow_up_at)->format('Y-m-d\\TH:i') }}">
                                <select name="referral_org_unit_id" class="form-select" title="Referral branch" aria-label="Referral branch">
                                    <option value="">Referral branch…</option>
                                    @foreach(($branches ?? []) as $b)
                                        <option value="{{ $b->id }}" @selected((int) $item->referral_org_unit_id === (int) $b->id)>{{ $b->name }}</option>
                                    @endforeach
                                </select>
                                <input name="notes" class="form-control flex-grow" placeholder="Notes" title="Notes" value="{{ $item->notes }}">
                                <button type="submit" class="pd-btn" title="Save PMS status"><i class="bi bi-check2"></i> Save</button>
                                <a href="{{ route('goal.cases.show', $item->goal_loan_case_id) }}" class="pd-btn-ghost">Open case</a>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="pd-col-empty">
                            <div class="pd-empty">
                                <i class="bi bi-inbox"></i>
                                No PMS items match. Verified disbursements create portfolio entries via PD-01.
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

    <p class="pd-note"><i class="bi bi-info-circle me-1"></i>Payout stays On Hold until PMS is Verified and Accounts Approves the report number.</p>
</div>
@endsection
