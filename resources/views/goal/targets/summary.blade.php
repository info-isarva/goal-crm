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
            <h1 class="h4 mb-1 fw-bold">Target Summary</h1>
            <p class="text-muted mb-0 small">Assigned targets, openings, adjustments, achievement and remaining.</p>
        </div>
        <a href="{{ route('goal.targets.export', request()->query()) }}" class="btn-gt-ghost"><i class="bi bi-download"></i> Export</a>
    </div>

    @include('goal.targets._subnav', ['active' => 'summary', 'canManage' => $canManage])

    <form method="GET" action="{{ route('goal.targets.summary') }}" class="gt-filters">
        <div class="field">
            <label for="sum_period">Period</label>
            <select name="period" id="sum_period" class="form-select">
                <option value="">All periods</option>
                @foreach($periods as $p)
                    <option value="{{ $p }}" @selected(($filters['period'] ?? '') === $p)>{{ $p }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="sum_metric">Target type</label>
            <select name="metric" id="sum_metric" class="form-select">
                <option value="">All</option>
                <option value="revenue" @selected(($filters['metric'] ?? '') === 'revenue')>Revenue</option>
                <option value="disbursement" @selected(($filters['metric'] ?? '') === 'disbursement')>Disbursement</option>
            </select>
        </div>
        <div class="field">
            <label for="sum_owner">View by</label>
            <select name="owner_type" id="sum_owner" class="form-select">
                <option value="">Overall</option>
                <option value="employee" @selected(($filters['owner_type'] ?? '') === 'employee')>Employee</option>
                <option value="org_unit" @selected(($filters['owner_type'] ?? '') === 'org_unit')>Org unit</option>
            </select>
        </div>
        <button type="submit" class="btn-gt-primary"><i class="bi bi-funnel"></i> Apply</button>
        <a href="{{ route('goal.targets.summary') }}" class="btn-gt-ghost">Reset</a>
    </form>

    <div class="gt-kpi-row">
        <div class="gt-kpi">
            <div class="lbl">Effective Target</div>
            <div class="val">{{ $fmt($stats['total_effective']) }}</div>
        </div>
        <div class="gt-kpi">
            <div class="lbl">Achieved</div>
            <div class="val">{{ $fmt($stats['total_actual']) }}</div>
        </div>
        <div class="gt-kpi">
            <div class="lbl">Achievement %</div>
            <div class="val">{{ number_format((float) $stats['achievement_pct'], 1) }}%</div>
        </div>
        <div class="gt-kpi">
            <div class="lbl">Remaining</div>
            <div class="val">{{ $fmt($stats['remaining']) }}</div>
        </div>
    </div>

    <div class="gt-card">
        <div class="gt-card-head">
            <h3>Summary register ({{ number_format(count($table)) }})</h3>
        </div>
        <div class="table-responsive">
            <table class="gt-table">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Type</th>
                        <th>Entity</th>
                        <th class="num">Base</th>
                        <th class="num">Opening</th>
                        <th class="num">Adj.</th>
                        <th class="num">Effective</th>
                        <th class="num">Achieved</th>
                        <th class="num">Remaining</th>
                        <th class="num">%</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($table as $row)
                        <tr>
                            <td>{{ $row['period'] }}</td>
                            <td>{{ ucfirst($row['metric']) }}</td>
                            <td>
                                <strong>{{ $row['owner'] }}</strong>
                                <div class="small text-muted">{{ $row['owner_type'] }}</div>
                            </td>
                            <td class="num">{{ $fmt($row['base']) }}</td>
                            <td class="num">{{ $fmt($row['opening']) }}</td>
                            <td class="num">{{ $fmt($row['adjustments']) }}</td>
                            <td class="num">{{ $fmt($row['effective']) }}</td>
                            <td class="num">{{ $fmt($row['achieved']) }}</td>
                            <td class="num">{{ $fmt($row['remaining']) }}</td>
                            <td class="num pct">{{ number_format($row['pct'], 1) }}%</td>
                            <td>{{ $row['status'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted py-4">No targets for current filters.</td></tr>
                    @endforelse
                </tbody>
                @if(count($table))
                    <tfoot>
                        <tr>
                            <td colspan="3"><strong>Total</strong></td>
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
    </div>
</div>
@endsection
