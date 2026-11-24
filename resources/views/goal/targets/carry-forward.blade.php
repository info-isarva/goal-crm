@extends('layouts.app')

@php
    $currency = config('app.currency_symbol', '₹');
    $fmt = fn ($n) => $currency . ' ' . number_format((float) $n, 2, '.', ',');
    $fmtPeriod = function ($p) {
        try {
            return \Carbon\Carbon::createFromFormat('Y-m', (string) $p)->format('M Y');
        } catch (\Throwable $e) {
            return (string) $p;
        }
    };
    $total = method_exists($table, 'total') ? (int) $table->total() : count($table);
    $from = method_exists($table, 'firstItem') ? ($table->firstItem() ?? 0) : ($total ? 1 : 0);
    $to = method_exists($table, 'lastItem') ? ($table->lastItem() ?? 0) : $total;
    $perPage = (int) ($perPage ?? 10);
    $colspan = $canApprove ? 11 : 10;
@endphp

@push('styles')
@include('goal.targets._section_styles')
<style>
.goal-targets-page .gt-kpi-ico.is-pending { background: #ffedd5; color: #c2410c; }
.goal-targets-page .gt-kpi-ico.is-applied { background: #dcfce7; color: #15803d; }
.goal-targets-page .gt-kpi-ico.is-rejected { background: #fee2e2; color: #b91c1c; }
.goal-targets-page .gt-kpi-ico.is-queue { background: #eff6ff; color: #2563eb; }
.goal-targets-page .gt-flow {
    display: inline-flex; align-items: center; flex-wrap: wrap; gap: 8px;
    margin-top: 8px; font-size: .78rem; font-weight: 700; color: #475569;
}
.goal-targets-page .gt-flow-chip {
    display: inline-flex; align-items: center; gap: 6px;
    height: 28px; padding: 0 10px; border-radius: 8px;
    background: #f8fafc; border: 1px solid #e2e8f0; color: #334155;
}
.goal-targets-page .gt-flow-chip i { color: #94a3b8; font-size: .78rem; }
.goal-targets-page .gt-flow-arrow { color: #94a3b8; }
.goal-targets-page .cf-actions {
    display: flex; flex-wrap: wrap; align-items: center; gap: 8px;
}
.goal-targets-page .cf-reason {
    height: 34px; border: 1px solid #e2e8f0; border-radius: 8px;
    padding: 0 10px; font-size: .78rem; min-width: 180px; max-width: 240px;
    background: #fff; color: #0f172a;
}
.goal-targets-page .cf-reason:focus {
    outline: 0; border-color: #059669; box-shadow: 0 0 0 .15rem rgba(5,150,105,.15);
}
.goal-targets-page .gt-check {
    width: 16px; height: 16px; accent-color: #059669; cursor: pointer;
}
.goal-targets-page .gt-amt-neg { color: #dc2626; font-weight: 700; }
.goal-targets-page .gt-amt-eff { color: #0f172a; font-weight: 800; }
.goal-targets-page .gt-note {
    display: flex; align-items: flex-start; gap: 10px;
    margin-top: 12px; padding: 12px 14px;
    background: #f8fafc; border: 1px solid #e8edf5; border-radius: 12px;
    font-size: .78rem; color: #64748b; line-height: 1.45;
}
.goal-targets-page .gt-note i { color: #059669; font-size: 1rem; margin-top: 1px; }
.goal-targets-page .badge-cf {
    display: inline-flex; align-items: center; gap: 4px;
    height: 22px; padding: 0 8px; border-radius: 999px;
    font-size: .65rem; font-weight: 750; white-space: nowrap;
}
</style>
@endpush

@section('content')
<div class="container-fluid p-4 goal-targets-page">
    <div class="gt-hero">
        <div class="gt-hero-title">
            <span class="gt-hero-ico"><i class="bi bi-arrow-left-right"></i></span>
            <div>
                <h1>Shortfall Carry Forward</h1>
                <p>Review previous-period closing shortfall before it becomes next-period opening.</p>
                <div class="gt-flow">
                    <span class="gt-flow-chip">
                        <i class="bi bi-calendar3"></i>
                        Source {{ $fmtPeriod($sourcePeriod) }}
                    </span>
                    <span class="gt-flow-arrow"><i class="bi bi-arrow-right"></i></span>
                    <span class="gt-flow-chip">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Target {{ $fmtPeriod($nextPeriod) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    @include('goal.targets._subnav', ['active' => 'carry', 'canManage' => $canManage])

    @if(session('success'))
        <div class="alert alert-success py-2 d-flex align-items-center gap-2">
            <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger py-2 d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
        </div>
    @endif

    <form method="GET" action="{{ route('goal.targets.carry-forward') }}" class="gt-filters" id="gtCfFilters">
        <div class="field">
            <label for="cf_period">Previous period (source)</label>
            <select name="period" id="cf_period" class="form-select"
                data-search="true" data-allow-clear="false" data-placeholder="Select period">
                @foreach($periods as $p)
                    <option value="{{ $p }}" @selected($sourcePeriod === $p)>{{ $fmtPeriod($p) }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="cf_metric">Target type</label>
            <select name="metric" id="cf_metric" class="form-select"
                data-search="false" data-allow-clear="false" data-placeholder="All">
                <option value="">All</option>
                <option value="revenue" @selected($metric === 'revenue')>Revenue</option>
                <option value="disbursement" @selected($metric === 'disbursement')>Disbursement</option>
            </select>
        </div>
        <div class="field">
            <label for="cf_branch">Branch</label>
            <select name="goal_org_unit_id" id="cf_branch" class="form-select"
                data-search="true" data-allow-clear="false" data-placeholder="All branches">
                <option value="">All branches</option>
                @foreach(($branchUnits ?? collect()) as $b)
                    <option value="{{ $b->id }}" @selected((string) ($branchId ?? '') === (string) $b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-gt-primary"><i class="bi bi-funnel"></i> Load</button>
        <a href="{{ route('goal.targets.carry-forward') }}" class="btn-gt-ghost">Reset</a>
    </form>

    <div class="gt-kpi-row">
        <div class="gt-kpi">
            <span class="gt-kpi-ico is-pending"><i class="bi bi-hourglass-split"></i></span>
            <div>
                <div class="lbl">Pending approval</div>
                <div class="val">{{ number_format($pending) }}</div>
                <div class="meta">{{ $fmt($totalShortfall) }} shortfall</div>
            </div>
        </div>
        <div class="gt-kpi">
            <span class="gt-kpi-ico is-applied"><i class="bi bi-check2-circle"></i></span>
            <div>
                <div class="lbl">Applied</div>
                <div class="val">{{ number_format($applied) }}</div>
                <div class="meta">Opening already set</div>
            </div>
        </div>
        <div class="gt-kpi">
            <span class="gt-kpi-ico is-rejected"><i class="bi bi-x-circle"></i></span>
            <div>
                <div class="lbl">Rejected</div>
                <div class="val">{{ number_format($rejected) }}</div>
                <div class="meta">Will not carry</div>
            </div>
        </div>
        <div class="gt-kpi">
            <span class="gt-kpi-ico is-queue"><i class="bi bi-list-check"></i></span>
            <div>
                <div class="lbl">Candidates</div>
                <div class="val">{{ number_format($total) }}</div>
                <div class="meta">In queue for {{ $fmtPeriod($sourcePeriod) }}</div>
            </div>
        </div>
    </div>

    <form method="POST" id="cfForm">
        @csrf
        <input type="hidden" name="period" value="{{ $sourcePeriod }}">
        <input type="hidden" name="metric" value="{{ $metric }}">
        <input type="hidden" name="goal_org_unit_id" value="{{ $branchId }}">

        <div class="gt-card">
            <div class="gt-card-head">
                <h3>
                    <i class="bi bi-arrow-repeat"></i>
                    Carry-forward queue
                    <span class="gt-count-pill">{{ number_format($total) }}</span>
                </h3>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    @if($canApprove)
                        <div class="cf-actions">
                            <input type="text" name="reason" class="cf-reason" placeholder="Reason (optional)" maxlength="500">
                            <button type="submit" formaction="{{ route('goal.targets.carry-forward.approve') }}" class="btn-gt-primary" id="cfApproveBtn" disabled>
                                <i class="bi bi-check2-circle"></i> Approve
                            </button>
                            <button type="submit" formaction="{{ route('goal.targets.carry-forward.reject') }}" class="btn-gt-ghost" id="cfRejectBtn" disabled onclick="return confirm('Reject carry-forward for selected rows? Opening will not be applied.');">
                                <i class="bi bi-x-circle"></i> Reject
                            </button>
                        </div>
                    @endif
                    <div class="d-flex align-items-center gap-2">
                        <label class="mb-0 small text-muted fw-semibold" for="cf_per_page">Rows</label>
                        <select id="cf_per_page" class="gt-rows-select" form="gtCfFilters" name="per_page"
                            onchange="document.getElementById('gtCfFilters').submit();">
                            @foreach([10, 25, 50, 100] as $n)
                                <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="gt-table">
                    <thead>
                        <tr>
                            @if($canApprove)
                                <th scope="col" style="width:44px">
                                    <input type="checkbox" id="cfCheckAll" class="gt-check" title="Select all pending">
                                </th>
                            @endif
                            <th scope="col">Entity</th>
                            <th scope="col">Level</th>
                            <th scope="col">Branch</th>
                            <th scope="col">Type</th>
                            <th scope="col" class="num">Prev target</th>
                            <th scope="col" class="num">Prev achieved</th>
                            <th scope="col" class="num">Shortfall</th>
                            <th scope="col" class="num">Next base</th>
                            <th scope="col" class="num">Effective if applied</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($table as $row)
                            @php
                                $metricKey = strtolower((string) ($row['metric'] ?? ''));
                                $metricCls = $metricKey === 'disbursement' ? 'is-disbursement' : ($metricKey === 'revenue' ? 'is-revenue' : 'is-other');
                                $isEmp = ($row['owner_type'] ?? '') === 'employee';
                                $ownerLabel = $row['owner_label'] ?? ($isEmp ? 'Employee' : 'Org unit');
                                $status = (string) ($row['status'] ?? 'pending');
                            @endphp
                            <tr>
                                @if($canApprove)
                                    <td>
                                        @if($status === 'pending')
                                            <input type="checkbox" name="source_ids[]" value="{{ $row['source_id'] }}" class="cf-row-check gt-check">
                                        @endif
                                    </td>
                                @endif
                                <td>
                                    <div class="gt-entity">
                                        <span class="gt-entity-ico {{ $isEmp ? 'is-emp' : 'is-org' }}">
                                            <i class="bi {{ $isEmp ? 'bi-person' : 'bi-building' }}"></i>
                                        </span>
                                        <div style="min-width:0">
                                            <div class="gt-entity-name" title="{{ $row['owner'] }}">{{ $row['owner'] }}</div>
                                            <div class="gt-entity-sub">{{ $ownerLabel }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="gt-level-pill">{{ $row['level'] ?? $ownerLabel }}</span></td>
                                <td>
                                    <span class="gt-branch-chip" title="{{ $row['branch'] ?? '—' }}">
                                        <i class="bi bi-geo-alt"></i> {{ $row['branch'] ?? '—' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="gt-metric-pill {{ $metricCls }}">
                                        <i class="bi {{ $metricKey === 'disbursement' ? 'bi-cash-coin' : 'bi-currency-rupee' }}"></i>
                                        {{ ucfirst($row['metric']) }}
                                    </span>
                                </td>
                                <td class="num">{{ $fmt($row['previous_target']) }}</td>
                                <td class="num">{{ $fmt($row['previous_achieved']) }}</td>
                                <td class="num gt-amt-neg">{{ $fmt($row['previous_shortfall']) }}</td>
                                <td class="num">{{ $fmt($row['next_base']) }}</td>
                                <td class="num gt-amt-eff">{{ $fmt($row['effective_if_applied']) }}</td>
                                <td>
                                    <span class="badge-cf {{ $status }}">
                                        @if($status === 'applied')
                                            <i class="bi bi-check2"></i>
                                        @elseif($status === 'rejected')
                                            <i class="bi bi-x"></i>
                                        @else
                                            <i class="bi bi-clock"></i>
                                        @endif
                                        {{ ucfirst($status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $colspan }}">
                                    <div class="gt-empty">
                                        <i class="bi bi-inbox"></i>
                                        <div>No Approved targets with closing shortfall for {{ $fmtPeriod($sourcePeriod) }}.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="gt-table-foot">
                <div class="gt-showing">
                    @if($total > 0)
                        Showing {{ number_format($from) }}–{{ number_format($to) }} of {{ number_format($total) }}
                    @else
                        No rows to show
                    @endif
                </div>
                <div class="gt-pager">
                    @if(method_exists($table, 'links'))
                        {{ $table->onEachSide(1)->links('pagination::bootstrap-5') }}
                    @endif
                </div>
            </div>
        </div>
    </form>

    <div class="gt-note">
        <i class="bi bi-info-circle"></i>
        <div>
            Closing shortfall does not auto-become next opening until approved here (or via the scheduled carry-forward job for bulk ops).
            Only approvers (Admin / MD / RD / Cluster) can approve or reject.
        </div>
    </div>
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
        if (all) {
            const total = boxes().length;
            all.checked = total > 0 && n === total;
            all.indeterminate = n > 0 && n < total;
        }
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
