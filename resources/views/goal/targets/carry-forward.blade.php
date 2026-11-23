@extends('layouts.app')

@php
    $currency = config('app.currency_symbol', '₹');
    $fmt = fn ($n) => $currency . ' ' . number_format((float) $n, 2, '.', ',');
@endphp

@push('styles')
@include('goal.targets._section_styles')
@endpush

@section('content')
<div class="container-fluid p-4 goal-targets-page">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h1 class="h4 mb-1 fw-bold">Shortfall Carry Forward</h1>
            <p class="text-muted mb-0 small">
                Review previous-period closing shortfall before it becomes next-period opening.
                Source <strong>{{ $sourcePeriod }}</strong> → Target <strong>{{ $nextPeriod }}</strong>.
            </p>
        </div>
    </div>

    @include('goal.targets._subnav', ['active' => 'carry', 'canManage' => $canManage])

    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('goal.targets.carry-forward') }}" class="gt-filters">
        <div class="field">
            <label for="cf_period">Previous period (source)</label>
            <select name="period" id="cf_period" class="form-select">
                @foreach($periods as $p)
                    <option value="{{ $p }}" @selected($sourcePeriod === $p)>{{ $p }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="cf_metric">Target type</label>
            <select name="metric" id="cf_metric" class="form-select">
                <option value="">All</option>
                <option value="revenue" @selected($metric === 'revenue')>Revenue</option>
                <option value="disbursement" @selected($metric === 'disbursement')>Disbursement</option>
            </select>
        </div>
        <button type="submit" class="btn-gt-primary"><i class="bi bi-funnel"></i> Load</button>
    </form>

    <div class="gt-kpi-row">
        <div class="gt-kpi"><div class="lbl">Pending approval</div><div class="val">{{ $pending }}</div></div>
        <div class="gt-kpi"><div class="lbl">Applied</div><div class="val">{{ $applied }}</div></div>
        <div class="gt-kpi"><div class="lbl">Rejected</div><div class="val">{{ $rejected }}</div></div>
        <div class="gt-kpi"><div class="lbl">Candidates</div><div class="val">{{ count($candidates) }}</div></div>
    </div>

    <form method="POST" id="cfForm">
        @csrf
        <input type="hidden" name="period" value="{{ $sourcePeriod }}">
        <input type="hidden" name="metric" value="{{ $metric }}">

        <div class="gt-card">
            <div class="gt-card-head">
                <h3>Carry-forward queue</h3>
                @if($canApprove)
                    <div class="cf-actions">
                        <input type="text" name="reason" class="form-control form-control-sm" style="max-width:220px" placeholder="Reason (optional)" maxlength="500">
                        <button type="submit" formaction="{{ route('goal.targets.carry-forward.approve') }}" class="btn-gt-primary" id="cfApproveBtn" disabled>
                            <i class="bi bi-check2-circle"></i> Approve selected
                        </button>
                        <button type="submit" formaction="{{ route('goal.targets.carry-forward.reject') }}" class="btn-gt-ghost" id="cfRejectBtn" disabled onclick="return confirm('Reject carry-forward for selected rows? Opening will not be applied.');">
                            <i class="bi bi-x-circle"></i> Reject
                        </button>
                    </div>
                @endif
            </div>
            <div class="table-responsive">
                <table class="gt-table">
                    <thead>
                        <tr>
                            @if($canApprove)<th style="width:36px"><input type="checkbox" id="cfCheckAll"></th>@endif
                            <th>Entity</th>
                            <th>Metric</th>
                            <th class="num">Prev target</th>
                            <th class="num">Prev achieved</th>
                            <th class="num">Shortfall</th>
                            <th class="num">Next base</th>
                            <th class="num">Effective if applied</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($candidates as $row)
                            <tr>
                                @if($canApprove)
                                    <td>
                                        @if($row['status'] === 'pending')
                                            <input type="checkbox" name="source_ids[]" value="{{ $row['source_id'] }}" class="cf-row-check">
                                        @endif
                                    </td>
                                @endif
                                <td>
                                    <strong>{{ $row['owner'] }}</strong>
                                    <div class="small text-muted">{{ $row['owner_type'] }}</div>
                                </td>
                                <td>{{ ucfirst($row['metric']) }}</td>
                                <td class="num">{{ $fmt($row['previous_target']) }}</td>
                                <td class="num">{{ $fmt($row['previous_achieved']) }}</td>
                                <td class="num">{{ $fmt($row['previous_shortfall']) }}</td>
                                <td class="num">{{ $fmt($row['next_base']) }}</td>
                                <td class="num"><strong>{{ $fmt($row['effective_if_applied']) }}</strong></td>
                                <td><span class="badge-cf {{ $row['status'] }}">{{ ucfirst($row['status']) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canApprove ? 9 : 8 }}" class="text-center text-muted py-4">
                                    No Approved targets with closing shortfall for {{ $sourcePeriod }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    <p class="small text-muted mt-2 mb-0">
        Closing shortfall does not auto-become next opening until approved here (or via the scheduled carry-forward job for bulk ops).
        Only approvers (Admin / MD / RD / Cluster) can approve or reject.
    </p>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const all = document.getElementById('cfCheckAll');
    const boxes = () => Array.from(document.querySelectorAll('.cf-row-check'));
    const sync = () => {
        const n = boxes().filter(b => b.checked).length;
        const approve = document.getElementById('cfApproveBtn');
        const reject = document.getElementById('cfRejectBtn');
        if (approve) approve.disabled = n === 0;
        if (reject) reject.disabled = n === 0;
    };
    all?.addEventListener('change', function () {
        boxes().forEach(b => { b.checked = all.checked; });
        sync();
    });
    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('cf-row-check')) sync();
    });
})();
</script>
@endpush
