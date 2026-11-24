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
    $perPage = (int) ($filters['per_page'] ?? 10);
    $rankOffset = max(0, (int) $from - 1);
@endphp

@push('styles')
@include('goal.targets._section_styles')
<style>
.goal-targets-page .gt-kpi-ico.is-short { background: #fef2f2; color: #dc2626; }
.goal-targets-page .gt-kpi-ico.is-surplus { background: #ecfdf5; color: #059669; }
.goal-targets-page .gt-rank {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 28px; height: 28px; border-radius: 8px;
    font-size: .72rem; font-weight: 800; color: #475569; background: #f1f5f9;
}
.goal-targets-page .gt-rank.is-1 { background: #fef3c7; color: #b45309; }
.goal-targets-page .gt-rank.is-2 { background: #e2e8f0; color: #475569; }
.goal-targets-page .gt-rank.is-3 { background: #ffedd5; color: #c2410c; }
.goal-targets-page .gt-amt-neg { color: #dc2626; font-weight: 700; }
.goal-targets-page .gt-amt-pos { color: #059669; font-weight: 700; }
.goal-targets-page .gt-card-head h3 i.is-warn { color: #d97706; }
.goal-targets-page .gt-card-head h3 i.is-low { color: #dc2626; }
.goal-targets-page .gt-mini-empty {
    text-align: center; padding: 28px 12px; color: #94a3b8; font-size: .82rem;
}
.goal-targets-page .gt-mini-empty i {
    display: block; font-size: 1.4rem; margin-bottom: 6px; color: #cbd5e1;
}
</style>
@endpush

@section('content')
<div class="container-fluid p-4 goal-targets-page">
    <div class="gt-hero">
        <div class="gt-hero-title">
            <span class="gt-hero-ico"><i class="bi bi-trophy"></i></span>
            <div>
                <h1>Achievement</h1>
                <p>Employee and org performance, ranking, top and lowest achievers.</p>
            </div>
        </div>
        <a href="{{ route('goal.targets.export', request()->query()) }}" class="btn-gt-ghost">
            <i class="bi bi-download"></i> Export
        </a>
    </div>

    @include('goal.targets._subnav', ['active' => 'achievement', 'canManage' => $canManage])

    <form method="GET" action="{{ route('goal.targets.achievement') }}" class="gt-filters" id="gtAchFilters">
        <div class="field">
            <label for="ach_period">Period</label>
            <select name="period" id="ach_period" class="form-select"
                data-search="true" data-allow-clear="false" data-placeholder="All periods">
                <option value="">All periods</option>
                @foreach($periods as $p)
                    <option value="{{ $p }}" @selected(($filters['period'] ?? '') === $p)>{{ $fmtPeriod($p) }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="ach_metric">Target type</label>
            <select name="metric" id="ach_metric" class="form-select"
                data-search="false" data-allow-clear="false" data-placeholder="All">
                <option value="">All</option>
                <option value="revenue" @selected(($filters['metric'] ?? '') === 'revenue')>Revenue</option>
                <option value="disbursement" @selected(($filters['metric'] ?? '') === 'disbursement')>Disbursement</option>
            </select>
        </div>
        <div class="field">
            <label for="ach_owner">View by</label>
            <select name="owner_type" id="ach_owner" class="form-select"
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
            <label for="ach_branch">Branch</label>
            <select name="goal_org_unit_id" id="ach_branch" class="form-select"
                data-search="true" data-allow-clear="false" data-placeholder="All branches">
                <option value="">All branches</option>
                @foreach(($branchUnits ?? collect()) as $b)
                    <option value="{{ $b->id }}" @selected((string) ($filters['goal_org_unit_id'] ?? '') === (string) $b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-gt-primary"><i class="bi bi-funnel"></i> Apply</button>
        <a href="{{ route('goal.targets.achievement') }}" class="btn-gt-ghost">Reset</a>
    </form>

    <div class="gt-kpi-row">
        <div class="gt-kpi">
            <span class="gt-kpi-ico is-pct"><i class="bi bi-percent"></i></span>
            <div>
                <div class="lbl">Achievement %</div>
                <div class="val">{{ number_format((float) $stats['achievement_pct'], 1) }}%</div>
                <div class="meta">Vs effective target</div>
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
            <span class="gt-kpi-ico is-short"><i class="bi bi-arrow-down-right"></i></span>
            <div>
                <div class="lbl">Shortfall</div>
                <div class="val">{{ $fmt($stats['total_closing']) }}</div>
                <div class="meta">Below target</div>
            </div>
        </div>
        <div class="gt-kpi">
            <span class="gt-kpi-ico is-surplus"><i class="bi bi-arrow-up-right"></i></span>
            <div>
                <div class="lbl">Surplus</div>
                <div class="val">{{ $fmt($stats['surplus']) }}</div>
                <div class="meta">Above target</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="gt-card h-100">
                <div class="gt-card-head">
                    <h3><i class="bi bi-award is-warn"></i> Top performers</h3>
                </div>
                <div class="table-responsive">
                    <table class="gt-table">
                        <thead>
                            <tr>
                                <th scope="col" style="width:48px">#</th>
                                <th scope="col">Entity</th>
                                <th scope="col">Level</th>
                                <th scope="col">Branch</th>
                                <th scope="col" class="num">Achieved</th>
                                <th scope="col" class="num">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($top as $i => $row)
                                @php
                                    $isEmp = ($row['owner_type'] ?? '') === 'employee';
                                    $ownerLabel = $row['owner_label'] ?? ($isEmp ? 'Employee' : 'Org unit');
                                @endphp
                                <tr>
                                    <td><span class="gt-rank {{ $i < 3 ? 'is-'.($i+1) : '' }}">{{ $i + 1 }}</span></td>
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
                                    <td class="num">{{ $fmt($row['achieved']) }}</td>
                                    <td class="num">
                                        <div class="gt-pct-cell">
                                            <span class="pct">{{ number_format($row['pct'], 1) }}%</span>
                                            <div class="gt-pct-bar"><span style="width: {{ min(100, max(0, $row['pct'])) }}%"></span></div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="gt-mini-empty"><i class="bi bi-inbox"></i>No data</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="gt-card h-100">
                <div class="gt-card-head">
                    <h3><i class="bi bi-exclamation-triangle is-low"></i> Lowest performers</h3>
                </div>
                <div class="table-responsive">
                    <table class="gt-table">
                        <thead>
                            <tr>
                                <th scope="col" style="width:48px">#</th>
                                <th scope="col">Entity</th>
                                <th scope="col">Level</th>
                                <th scope="col">Branch</th>
                                <th scope="col" class="num">Shortfall</th>
                                <th scope="col" class="num">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lowest as $i => $row)
                                @php
                                    $isEmp = ($row['owner_type'] ?? '') === 'employee';
                                    $ownerLabel = $row['owner_label'] ?? ($isEmp ? 'Employee' : 'Org unit');
                                @endphp
                                <tr>
                                    <td><span class="gt-rank">{{ $i + 1 }}</span></td>
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
                                    <td class="num gt-amt-neg">{{ $fmt($row['shortfall']) }}</td>
                                    <td class="num">
                                        <div class="gt-pct-cell">
                                            <span class="pct">{{ number_format($row['pct'], 1) }}%</span>
                                            <div class="gt-pct-bar"><span style="width: {{ min(100, max(0, $row['pct'])) }}%"></span></div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="gt-mini-empty"><i class="bi bi-inbox"></i>No data</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="gt-card">
        <div class="gt-card-head">
            <h3>
                <i class="bi bi-bar-chart-steps"></i>
                Full ranking
                <span class="gt-count-pill">{{ number_format($total) }}</span>
            </h3>
            <div class="d-flex align-items-center gap-2">
                <label class="mb-0 small text-muted fw-semibold" for="ach_per_page">Rows</label>
                <select id="ach_per_page" class="gt-rows-select" form="gtAchFilters" name="per_page"
                    onchange="document.getElementById('gtAchFilters').submit();">
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
                        <th scope="col" style="width:48px">#</th>
                        <th scope="col">Entity</th>
                        <th scope="col">Level</th>
                        <th scope="col">Branch</th>
                        <th scope="col">Type</th>
                        <th scope="col">Period</th>
                        <th scope="col" class="num">Effective</th>
                        <th scope="col" class="num">Achieved</th>
                        <th scope="col" class="num">Shortfall</th>
                        <th scope="col" class="num">Surplus</th>
                        <th scope="col" class="num">%</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($table as $i => $row)
                        @php
                            $metricKey = strtolower((string) ($row['metric'] ?? ''));
                            $metricCls = $metricKey === 'disbursement' ? 'is-disbursement' : ($metricKey === 'revenue' ? 'is-revenue' : 'is-other');
                            $isEmp = ($row['owner_type'] ?? '') === 'employee';
                            $ownerLabel = $row['owner_label'] ?? ($isEmp ? 'Employee' : 'Org unit');
                            $rank = $rankOffset + $i + 1;
                            $pct = (float) ($row['pct'] ?? 0);
                        @endphp
                        <tr>
                            <td><span class="gt-rank {{ $rank <= 3 ? 'is-'.$rank : '' }}">{{ $rank }}</span></td>
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
                            <td>
                                <span class="gt-period-chip">
                                    <i class="bi bi-calendar3"></i>
                                    {{ $fmtPeriod($row['period']) }}
                                </span>
                            </td>
                            <td class="num">{{ $fmt($row['effective']) }}</td>
                            <td class="num">{{ $fmt($row['achieved']) }}</td>
                            <td class="num {{ ($row['shortfall'] ?? 0) > 0 ? 'gt-amt-neg' : '' }}">{{ $fmt($row['shortfall']) }}</td>
                            <td class="num {{ ($row['surplus'] ?? 0) > 0 ? 'gt-amt-pos' : '' }}">{{ $fmt($row['surplus']) }}</td>
                            <td class="num">
                                <div class="gt-pct-cell">
                                    <span class="pct">{{ number_format($pct, 1) }}%</span>
                                    <div class="gt-pct-bar" title="{{ number_format($pct, 1) }}%">
                                        <span style="width: {{ min(100, max(0, $pct)) }}%"></span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11">
                                <div class="gt-empty">
                                    <i class="bi bi-inbox"></i>
                                    <div>No achievement rows for current filters.</div>
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
</div>
@endsection
