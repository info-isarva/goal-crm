@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Employee Targets Dashboard</h1>
                    <p class="text-muted mt-2">Current Month: <strong>{{ $monthName }}</strong></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-light border-0">
                <div class="card-body">
                    <p class="text-muted small mb-1">Total Employees</p>
                    <h4 class="mb-0">{{ count($employeeTargets) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light border-0">
                <div class="card-body">
                    <p class="text-muted small mb-1">Total Target</p>
                    <h4 class="mb-0">{{ $currency_symbol }} {{ number_format(array_sum(array_column($employeeTargets, 'target_amount')), 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light border-0">
                <div class="card-body">
                    <p class="text-muted small mb-1">Total Achieved</p>
                    <h4 class="mb-0">{{ $currency_symbol }} {{ number_format(array_sum(array_column($employeeTargets, 'achieved_sales')), 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light border-0">
                <div class="card-body">
                    <p class="text-muted small mb-1">Avg Progress</p>
                    <h4 class="mb-0">{{ count($employeeTargets) > 0 ? number_format(array_sum(array_column($employeeTargets, 'progress')) / count($employeeTargets), 1) : 0 }}%</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Employees Table -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Employee Targets & Achievement</h5>
                </div>
                <div class="card-body p-0">
                    @if(count($employeeTargets) > 0)
                        <!-- Desktop View (Table) -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 25%;">Employee Name</th>
                                        <th style="width: 18%;">Target Amount</th>
                                        <th style="width: 18%;">Achieved Sales</th>
                                        <th style="width: 15%;">Progress</th>
                                        <th style="width: 24%;">Progress Bar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($employeeTargets as $emp)
                                        <tr>
                                            <td>
                                                <div class="fw-500">{{ $emp['name'] }}</div>
                                            </td>
                                            <td>
                                                <div class="fw-500">{{ $currency_symbol }} {{ number_format($emp['target_amount'], 2) }}</div>
                                            </td>
                                            <td>
                                                <div class="fw-500">{{ $currency_symbol }} {{ number_format($emp['achieved_sales'], 2) }}</div>
                                            </td>
                                            <td>
                                                <div class="fw-bold">
                                                    @if($emp['target_amount'] > 0)
                                                        <span class="@if($emp['progress'] >= 100) text-success @elseif($emp['progress'] >= 75) text-info @elseif($emp['progress'] >= 50) text-warning @else text-danger @endif">
                                                            {{ $emp['progress'] }}%
                                                        </span>
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 24px; background: #f0f0f0;">
                                                    <div class="progress-bar @if($emp['progress'] >= 100) bg-success @elseif($emp['progress'] >= 75) bg-info @elseif($emp['progress'] >= 50) bg-warning @else bg-danger @endif" 
                                                         role="progressbar" 
                                                         style="width: {{ min($emp['progress'], 100) }}%; min-width: 25px;"
                                                         aria-valuenow="{{ $emp['progress'] }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                        <small class="text-white fw-bold">{{ $emp['progress'] }}%</small>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile View (Card Layout) -->
                        <div class="d-md-none">
                            @foreach($employeeTargets as $emp)
                                <div class="card mb-3 border-0 shadow-sm mobile-employee-card" style="margin-left: 0.75rem; margin-right: 0.75rem; margin-bottom: 1rem;">
                                    <div class="card-body p-3">
                                        <div class="row mb-2">
                                            <div class="col-6">
                                                <p class="text-muted small mb-1"><strong>Employee Name</strong></p>
                                                <p class="mb-0 fw-500">{{ $emp['name'] }}</p>
                                            </div>
                                            <div class="col-6 text-end">
                                                <p class="text-muted small mb-1"><strong>Progress</strong></p>
                                                <p class="mb-0 fw-bold">
                                                    @if($emp['target_amount'] > 0)
                                                        <span class="@if($emp['progress'] >= 100) text-success @elseif($emp['progress'] >= 75) text-info @elseif($emp['progress'] >= 50) text-warning @else text-danger @endif">
                                                            {{ $emp['progress'] }}%
                                                        </span>
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                        
                                        <hr class="my-2">
                                        
                                        <div class="row mb-2">
                                            <div class="col-6">
                                                <p class="text-muted small mb-1"><strong>Target Amount</strong></p>
                                                <p class="mb-0 fw-500">{{ $currency_symbol }} {{ number_format($emp['target_amount'], 2) }}</p>
                                            </div>
                                            <div class="col-6 text-end">
                                                <p class="text-muted small mb-1"><strong>Achieved Sales</strong></p>
                                                <p class="mb-0 fw-500">{{ $currency_symbol }} {{ number_format($emp['achieved_sales'], 2) }}</p>
                                            </div>
                                        </div>
                                        
                                        <div class="progress mt-3" style="height: 24px; background: #f0f0f0;">
                                            <div class="progress-bar @if($emp['progress'] >= 100) bg-success @elseif($emp['progress'] >= 75) bg-info @elseif($emp['progress'] >= 50) bg-warning @else bg-danger @endif" 
                                                 role="progressbar" 
                                                 style="width: {{ min($emp['progress'], 100) }}%; min-width: 25px;"
                                                 aria-valuenow="{{ $emp['progress'] }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                <small class="text-white fw-bold">{{ $emp['progress'] }}%</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Legend -->
                        <div class="card-footer bg-light border-top">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <small>
                                        <span class="badge bg-success">100%+</span>
                                        <span class="ms-2">Exceeded Target</span>
                                    </small>
                                </div>
                                <div class="col-md-3">
                                    <small>
                                        <span class="badge bg-info">75-99%</span>
                                        <span class="ms-2">On Track</span>
                                    </small>
                                </div>
                                <div class="col-md-3">
                                    <small>
                                        <span class="badge bg-warning">50-74%</span>
                                        <span class="ms-2">Moderate Progress</span>
                                    </small>
                                </div>
                                <div class="col-md-3">
                                    <small>
                                        <span class="badge bg-danger">&lt;50%</span>
                                        <span class="ms-2">Needs Attention</span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <p class="text-muted">No employees found with targets set.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .fw-500 {
        font-weight: 500;
    }

    .card {
        border-radius: 8px;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
    }

    .progress {
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .progress-bar {
        transition: width 0.6s ease;
    }

    .badge {
        padding: 0.5rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 600;
    }
</style>
@endsection
