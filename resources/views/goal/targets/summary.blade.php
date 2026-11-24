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
    $statusClass = function ($status) {
        $s = strtolower((string) $status);
        return match (true) {
            str_contains($s, 'approved') => 'is-approved',
            str_contains($s, 'submit') => 'is-submitted',
            str_contains($s, 'lock') => 'is-locked',
            default => 'is-draft',
        };
    };
    $total = method_exists($table, 'total') ? (int) $table->total() : count($table);
    $from = method_exists($table, 'firstItem') ? ($table->firstItem() ?? 0) : ($total ? 1 : 0);
    $to = method_exists($table, 'lastItem') ? ($table->lastItem() ?? 0) : $total;
    $perPage = (int) ($filters['per_page'] ?? 10);
@endphp

@push('styles')
@include('goal.targets._section_styles')
@endpush

@section('content')
<div class="container-fluid p-4 goal-targets-page">
    <div class="gt-hero">
        <div class="gt-hero-title">
            <span class="gt-hero-ico"><i class="bi bi-clipboard-data"></i></span>
            <div>
                <h1>Target Summary</h1>
                <p>Assigned targets, openings, adjustments, achievement and remaining across your hierarchy.</p>
            </div>
        </div>
        <a href="{{ route('goal.targets.export', request()->query()) }}" class="btn-gt-ghost">
            <i class="bi bi-download"></i> Export
        </a>
    </div>

    @include('goal.targets._subnav', ['active' => 'summary', 'canManage' => $canManage])

    <form method="GET" action="{{ route('goal.targets.summary') }}" class="gt-filters" id="gtSummaryFilters">
        <div class="field">
            <label for="sum_period">Period</label>
            <select name="period" id="sum_period" class="form-select"
                data-search="true" data-allow-clear="false" data-placeholder="All periods">
                <option value="">All periods</option>
                @foreach($periods as $p)
                    <option value="{{ $p }}" @selected(($filters['period'] ?? '') === $p)>{{ $fmtPeriod($p) }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="sum_metric">Target type</label>
            <select name="metric" id="sum_metric" class="form-select"
                data-search="false" data-allow-clear="false" data-placeholder="All">
                <option value="">All</option>
                <option value="revenue" @selected(($filters['metric'] ?? '') === 'revenue')>Revenue</option>
                <option value="disbursement" @selected(($filters['metric'] ?? '') === 'disbursement')>Disbursement</option>
            </select>
        </div>
        <div class="field">
            <label for="sum_owner">View by</label>
            <select name="owner_type" id="sum_owner" class="form-select"
                data-search="false" data-allow-clear="false" data-placeholder="Overall">
                <option value="">Overall</option>
                <option value="region" @selected(($filters['owner_type'] ?? '') === 'region')>Region</option>
                <option value="cluster" @selected(($filters['owner_type'] ?? '') === 'cluster')>Cluster</option>
                <option value="branch" @selected(($filters['owner_type'] ?? '') === 'branch')>Branch</option>
                <option value="employee" @selected(($filters['owner_type'] ?? '') === 'employee')>Employee</option>
                <option value="bm" @selected(($filters['owner_type'] ?? '') === 'bm')>BM</option>
                <option value="cd" @selected(($filters['owner_type'] ?? '') === 'cd')>Cluster Director</option>
                <option value="rd" @selected(($filters['owner_type'] ?? '') === 'rd')>Regional Director</option>
            </select>
        </div>
        <div class="field">
            <label for="sum_branch">Branch</label>
            <select name="goal_org_unit_id" id="sum_branch" class="form-select"
                data-search="true" data-allow-clear="false" data-placeholder="All branches">
                <option value="">All branches</option>
                @foreach(($branchUnits ?? collect()) as $b)
                    <option value="{{ $b->id }}" @selected((string) ($filters['goal_org_unit_id'] ?? '') === (string) $b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-gt-primary"><i class="bi bi-funnel"></i> Apply</button>
        <a href="{{ route('goal.targets.summary') }}" class="btn-gt-ghost">Reset</a>
    </form>

    <div class="gt-kpi-row">
        <div class="gt-kpi">
            <span class="gt-kpi-ico is-eff"><i class="bi bi-bullseye"></i></span>
            <div>
                <div class="lbl">Effective Target</div>
                <div class="val">{{ $fmt($stats['total_effective']) }}</div>
                <div class="meta">Base + opening + adj.</div>
            </div>
        </div>
        <div class="gt-kpi">
            <span class="gt-kpi-ico is-ach"><i class="bi bi-graph-up-arrow"></i></span>
            <div>
                <div class="lbl">Achieved</div>
                <div class="val">{{ $fmt($stats['total_actual']) }}</div>
                <div class="meta">Actual booked</div>
            </div>
        </div>
        <div class="gt-kpi">
            <span class="gt-kpi-ico is-pct"><i class="bi bi-percent"></i></span>
            <div>
                <div class="lbl">Achievement %</div>
                <div class="val">{{ number_format((float) $stats['achievement_pct'], 1) }}%</div>
                <div class="meta">Vs effective target</div>
            </div>
        </div>
        <div class="gt-kpi">
            <span class="gt-kpi-ico is-rem"><i class="bi bi-hourglass-split"></i></span>
            <div>
                <div class="lbl">Remaining</div>
                <div class="val">{{ $fmt($stats['remaining']) }}</div>
                <div class="meta">Still to achieve</div>
            </div>
        </div>
    </div>

    <div class="gt-card">
        <div class="gt-card-head">
            <h3>
                <i class="bi bi-table"></i>
                Summary register
                <span class="gt-count-pill">{{ number_format($total) }}</span>
            </h3>
            <div class="d-flex align-items-center gap-2">
                <label class="mb-0 small text-muted fw-semibold" for="sum_per_page">Rows</label>
                <select id="sum_per_page" class="gt-rows-select" form="gtSummaryFilters" name="per_page"
                    onchange="document.getElementById('gtSummaryFilters').submit();">
                    @foreach([10, 25, 50, 100] as $n)
                        <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="gt-table">
                <thead>
                    <tr>
                        <th scope="col">Period</th>
                        <th scope="col">Type</th>
                        <th scope="col">Entity</th>
                        <th scope="col">Level</th>
                        <th scope="col">Branch</th>
                        <th scope="col" class="num">Base</th>
                        <th scope="col" class="num">Opening</th>
                        <th scope="col" class="num">Adj.</th>
                        <th scope="col" class="num">Effective</th>
                        <th scope="col" class="num">Achieved</th>
                        <th scope="col" class="num">Remaining</th>
                        <th scope="col" class="num">%</th>
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
                            $pct = (float) ($row['pct'] ?? 0);
                        @endphp
                        <tr>
                            <td>
                                <span class="gt-period-chip">
                                    <i class="bi bi-calendar3"></i>
                                    {{ $fmtPeriod($row['period']) }}
                                </span>
                            </td>
                            <td>
                                <span class="gt-metric-pill {{ $metricCls }}">
                                    <i class="bi {{ $metricKey === 'disbursement' ? 'bi-cash-coin' : 'bi-currency-rupee' }}"></i>
                                    {{ ucfirst($row['metric']) }}
                                </span>
                            </td>
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
                            <td>
                                <span class="gt-level-pill">{{ $row['level'] ?? $ownerLabel }}</span>
                            </td>
                            <td>
                                <span class="gt-branch-chip" title="{{ $row['branch'] ?? '—' }}">
                                    <i class="bi bi-geo-alt"></i>
                                    {{ $row['branch'] ?? '—' }}
                                </span>
                            </td>
                            <td class="num">{{ $fmt($row['base']) }}</td>
                            <td class="num">{{ $fmt($row['opening']) }}</td>
                            <td class="num">{{ $fmt($row['adjustments']) }}</td>
                            <td class="num">{{ $fmt($row['effective']) }}</td>
                            <td class="num">{{ $fmt($row['achieved']) }}</td>
                            <td class="num">{{ $fmt($row['remaining']) }}</td>
                            <td class="num">
                                <div class="gt-pct-cell">
                                    <span class="pct">{{ number_format($pct, 1) }}%</span>
                                    <div class="gt-pct-bar" title="{{ number_format($pct, 1) }}%">
                                        <span style="width: {{ min(100, max(0, $pct)) }}%"></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="gt-status-pill {{ $statusClass($row['status']) }}">
                                    {{ $row['status'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13">
                                <div class="gt-empty">
                                    <i class="bi bi-inbox"></i>
                                    <div>No targets for current filters.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($total > 0)
                    <tfoot>
                        <tr>
                            <td colspan="5"><strong>Total (all filtered)</strong></td>
                            <td class="num">{{ $fmt($stats['total_base']) }}</td>
                            <td class="num">{{ $fmt($stats['total_opening']) }}</td>
                            <td class="num">{{ $fmt($stats['total_adjustments']) }}</td>
                            <td class="num"><strong>{{ $fmt($stats['total_effective']) }}</strong></td>
                            <td class="num">{{ $fmt($stats['total_actual']) }}</td>
                            <td class="num">{{ $fmt($stats['remaining']) }}</td>
                            <td class="num pct">{{ number_format((float) $stats['achievement_pct'], 1) }}%</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
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
</div>
@endsection
