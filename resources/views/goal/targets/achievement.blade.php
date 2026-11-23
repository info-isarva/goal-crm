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
            <h1 class="h4 mb-1 fw-bold">Achievement</h1>
            <p class="text-muted mb-0 small">Employee and org performance, ranking, top and lowest achievers.</p>
        </div>
    </div>

    @include('goal.targets._subnav', ['active' => 'achievement', 'canManage' => $canManage])

    <form method="GET" action="{{ route('goal.targets.achievement') }}" class="gt-filters">
        <div class="field">
            <label for="ach_period">Period</label>
            <select name="period" id="ach_period" class="form-select">
                <option value="">All periods</option>
                @foreach($periods as $p)
                    <option value="{{ $p }}" @selected(($filters['period'] ?? '') === $p)>{{ $p }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="ach_metric">Target type</label>
            <select name="metric" id="ach_metric" class="form-select">
                <option value="">All</option>
                <option value="revenue" @selected(($filters['metric'] ?? '') === 'revenue')>Revenue</option>
                <option value="disbursement" @selected(($filters['metric'] ?? '') === 'disbursement')>Disbursement</option>
            </select>
        </div>
        <div class="field">
            <label for="ach_owner">View by</label>
            <select name="owner_type" id="ach_owner" class="form-select">
                <option value="">Overall</option>
                <option value="employee" @selected(($filters['owner_type'] ?? '') === 'employee')>Employee</option>
                <option value="org_unit" @selected(($filters['owner_type'] ?? '') === 'org_unit')>Org unit</option>
            </select>
        </div>
        <button type="submit" class="btn-gt-primary"><i class="bi bi-funnel"></i> Apply</button>
        <a href="{{ route('goal.targets.achievement') }}" class="btn-gt-ghost">Reset</a>
    </form>

    <div class="gt-kpi-row">
        <div class="gt-kpi">
            <div class="lbl">Achievement %</div>
            <div class="val">{{ number_format((float) $stats['achievement_pct'], 1) }}%</div>
        </div>
        <div class="gt-kpi">
            <div class="lbl">Achieved</div>
            <div class="val">{{ $fmt($stats['total_actual']) }}</div>
        </div>
        <div class="gt-kpi">
            <div class="lbl">Shortfall</div>
            <div class="val">{{ $fmt($stats['total_closing']) }}</div>
        </div>
        <div class="gt-kpi">
            <div class="lbl">Surplus</div>
            <div class="val">{{ $fmt($stats['surplus']) }}</div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="gt-card h-100">
                <div class="gt-card-head"><h3>Top performers</h3></div>
                <div class="table-responsive">
                    <table class="gt-table">
                        <thead><tr><th>#</th><th>Entity</th><th class="num">Achieved</th><th class="num">%</th></tr></thead>
                        <tbody>
                            @forelse($top as $i => $row)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $row['owner'] }}</td>
                                    <td class="num">{{ $fmt($row['achieved']) }}</td>
                                    <td class="num pct">{{ number_format($row['pct'], 1) }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">No data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="gt-card h-100">
                <div class="gt-card-head"><h3>Lowest performers</h3></div>
                <div class="table-responsive">
                    <table class="gt-table">
                        <thead><tr><th>#</th><th>Entity</th><th class="num">Shortfall</th><th class="num">%</th></tr></thead>
                        <tbody>
                            @forelse($lowest as $i => $row)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $row['owner'] }}</td>
                                    <td class="num">{{ $fmt($row['shortfall']) }}</td>
                                    <td class="num pct">{{ number_format($row['pct'], 1) }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">No data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="gt-card">
        <div class="gt-card-head">
            <h3>Full ranking ({{ number_format(count($ranked)) }})</h3>
        </div>
        <div class="table-responsive">
            <table class="gt-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Entity</th>
                        <th>Type</th>
                        <th>Period</th>
                        <th class="num">Effective</th>
                        <th class="num">Achieved</th>
                        <th class="num">Shortfall</th>
                        <th class="num">Surplus</th>
                        <th class="num">%</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ranked as $i => $row)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <strong>{{ $row['owner'] }}</strong>
                                <div class="small text-muted">{{ $row['owner_type'] }}</div>
                            </td>
                            <td>{{ ucfirst($row['metric']) }}</td>
                            <td>{{ $row['period'] }}</td>
                            <td class="num">{{ $fmt($row['effective']) }}</td>
                            <td class="num">{{ $fmt($row['achieved']) }}</td>
                            <td class="num">{{ $fmt($row['shortfall']) }}</td>
                            <td class="num">{{ $fmt($row['surplus']) }}</td>
                            <td class="num pct">{{ number_format($row['pct'], 1) }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No achievement rows for current filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
