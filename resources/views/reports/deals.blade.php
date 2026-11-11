@extends('layouts.app')

@section('content')
@php
    $period = $period ?? 'today';
@endphp

<style>
@media (max-width: 767.98px) {
    .row > [class*='col-'] { margin-bottom: 20px; }
}
</style>

<x-report-shell
    title="Deals Analytics"
    :show-date-range="true"
    :period="$period"
    :start="$start ?? null"
    :end="$end ?? null"
    :show-run-button="true"
    :form-action="route('reports.deals')"
>
    <x-slot name="toolbar">
        <div class="toolbar-field">
            <label for="user_id">User</label>
            <select name="user_id" id="user_id" class="form-select">
                <option value="">All Users</option>
                @foreach($usersList ?? [] as $owner)
                    <option value="{{ $owner->id }}" {{ (string) ($filterUserId ?? '') === (string) $owner->id ? 'selected' : '' }}>
                        {{ $owner->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="toolbar-field">
            <label for="lead_source">Lead Source</label>
            <select name="lead_source" id="lead_source" class="form-select">
                <option value="">All Lead Sources</option>
                @foreach($leadSources ?? [] as $source)
                    <option value="{{ $source->id }}" {{ (string) ($leadSourceId ?? '') === (string) $source->id ? 'selected' : '' }}>
                        {{ $source->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="toolbar-field">
            <label for="stage">Stage</label>
            <select name="stage" id="stage" class="form-select">
                <option value="">All Stages</option>
                @foreach($stages ?? [] as $stageName)
                    <option value="{{ $stageName }}" {{ ($stageFilter ?? '') === $stageName ? 'selected' : '' }}>
                        {{ $stageName }}
                    </option>
                @endforeach
            </select>
        </div>
    </x-slot>

    @if(isset($historicalSelected) && $historicalSelected)
        <div class="alert alert-warning mx-3">Unable to process the selected report for a historical financial year.</div>
    @endif

    @if(!$deals->isEmpty())
    <div class="row mb-4 px-3">
            <div class="col-md-6">
                <div class="card shadow-sm mb-4" style="height: 100%;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Deal Source Distribution</h5>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <canvas id="dealSourcePieChart" style="max-width: 100%; height: 500px;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm mb-4" style="height: 100%;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Deal Stage Analysis</h5>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center flex-column">
                        <canvas id="dealStageBarChart" style="max-width: 100%; height: 500px;"></canvas>
                        <div id="stageColorLegend" class="mt-3 w-100 text-center"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm mb-4 graph-card" style="height: 100%;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Month-wise Deals Analytics</h5>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <canvas id="monthWiseDealsChart" style="max-width: 100%; height: 500px;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm mb-4 graph-card" style="height: 100%;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Deal Owner-wise Analytics</h5>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <canvas id="dealOwnerBarChart" style="max-width: 100%; height: 500px;"></canvas>
                    </div>
                </div>
            </div>
        </div>
        @endif

    @if($deals->isEmpty())
        <div class="alert alert-info mx-3">No deals found for this period.</div>
    @else
        <div class="report-table-wrap mx-3 mb-3">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Company</th>
                        <th>Contact Person</th>
                        <th>Deal Owner</th>
                        <th>Stage</th>
                        <th>Lead Source</th>
                        <th>Amount</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($deals as $deal)
                    <tr>
                        <td data-label="Title"><a class="text-decoration-none" href="{{ route('deals.show', $deal->id) }}">{{ $deal->title }}</a></td>
                        <td data-label="Company">{{ $deal->organization->name ?? '-' }}</td>
                        <td data-label="Contact Person">{{ $deal->person ? ($deal->person->first_name . ' ' . $deal->person->last_name) : '-' }}</td>
                        <td data-label="Deal Owner">{{ $deal->owner->name ?? '-' }}</td>
                        <td data-label="Stage">
                            @php
                                $stage = $deal->stage_name ?? '-';
                                $stageColors = [
                                    'Open' => 'bg-primary',
                                    'Closed Won' => 'bg-success-subtle text-success',
                                    'Closed Lost' => 'bg-danger-subtle text-danger',
                                ];
                                $color = $stageColors[$stage] ?? 'bg-secondary';
                            @endphp
                            <span class="badge {{ $color }}">{{ $stage }}</span>
                        </td>
                        <td data-label="Lead Source">{{ $deal->dealSource->name ?? '-' }}</td>
                        <td data-label="Amount">{{ $deal->amount ?? '-' }}</td>
                        <td data-label="Created At">{{ $deal->created_at->format('d-m-Y H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('reports.partials.pagination', ['paginator' => $deals, 'noun' => 'deals'])
    @endif
</x-report-shell>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Pie chart data
    var dealSourceLabels = @json($dealSourceLabels ?? []);
    var dealSourceCounts = @json($dealSourceCounts ?? []);
    if (dealSourceLabels.length > 0 && dealSourceCounts.length > 0) {
        var ctxPie = document.getElementById('dealSourcePieChart').getContext('2d');
        // Use a different color palette for pie chart
        var pieColors = [
            '#F44336', '#9C27B0', '#3F51B5', '#009688', '#CDDC39', '#FF9800', '#607D8B', '#00BFAE', '#FFB300', '#8D6E63', '#C51162', '#00C853'
        ];
        var pieBgColors = [];
        for (var i = 0; i < dealSourceLabels.length; i++) {
            pieBgColors.push(pieColors[i % pieColors.length]);
        }
        new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: dealSourceLabels,
                datasets: [{
                    data: dealSourceCounts,
                    backgroundColor: pieBgColors,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: true, text: 'Deal Source Analytics' }
                }
            }
        });
    }

    // Bar chart data
    var dealStageLabels = @json($dealStageLabels ?? []);
    var dealStageCounts = @json($dealStageCounts ?? []);
    if (dealStageLabels.length > 0 && dealStageCounts.length > 0) {
        var ctxBar = document.getElementById('dealStageBarChart').getContext('2d');
        // Assign a color for each stage
        var stageColors = [
            '#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF', '#8BC34A', '#E91E63', '#00BCD4', '#FFC107', '#795548'
        ];
        // If more stages than colors, repeat colors
        var barColors = [];
        for (var i = 0; i < dealStageLabels.length; i++) {
            barColors.push(stageColors[i % stageColors.length]);
        }
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: dealStageLabels,
                datasets: [{
                    label: 'Deals by Stage',
                    data: dealStageCounts,
                    backgroundColor: barColors,
                    barPercentage: 0.5, // reduce bar width
                    categoryPercentage: 0.5 // reduce bar width
                }]
            },
            options: {
                indexAxis: 'y', // horizontal bar chart
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Deal Stage Analytics' }
                },
                scales: {
                    x: { beginAtZero: true }
                }
            }
        });
        // Render color legend below chart
        var legendHtml = '<div class="d-flex flex-wrap gap-3">';
        for (var i = 0; i < dealStageLabels.length; i++) {
            legendHtml += '<span style="display:inline-flex;align-items:center;gap:6px;"><span style="width:18px;height:18px;display:inline-block;border-radius:3px;background:' + barColors[i] + ';"></span> ' + dealStageLabels[i] + '</span>';
        }
        legendHtml += '</div>';
        document.getElementById('stageColorLegend').innerHTML = legendHtml;
    }

    // Month-wise deals data
    var monthLabels = @json($monthLabels ?? []);
    var monthDealCounts = @json($monthDealCounts ?? []);
    if (monthLabels.length > 0 && monthDealCounts.length > 0) {
        var ctxMonthDeals = document.getElementById('monthWiseDealsChart').getContext('2d');
        new Chart(ctxMonthDeals, {
            type: 'bar',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Deals per Month',
                    data: monthDealCounts,
                    backgroundColor: '#36A2EB',
                    barPercentage: 0.5,
                    categoryPercentage: 0.5
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Month-wise Deals Analytics' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // Deal owner-wise data
    var dealOwnerLabels = @json($dealOwnerLabels ?? []);
    var dealOwnerCounts = @json($dealOwnerCounts ?? []);
    if (dealOwnerLabels.length > 0 && dealOwnerCounts.length > 0) {
        var ctxDealOwner = document.getElementById('dealOwnerBarChart').getContext('2d');
        new Chart(ctxDealOwner, {
            type: 'bar',
            data: {
                labels: dealOwnerLabels,
                datasets: [{
                    label: 'Deals by Owner',
                    data: dealOwnerCounts,
                    backgroundColor: '#8BC34A',
                    barPercentage: 0.5,
                    categoryPercentage: 0.5
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Deal Owner-wise Analytics' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // Explicitly set the height and width of the graphs to prevent auto-resizing
    const graphHeight = '500px';
    const graphWidth = '500px';

    document.getElementById('dealSourcePieChart').style.height = graphHeight;
    document.getElementById('dealSourcePieChart').style.width = graphWidth;
    document.getElementById('dealSourcePieChart').style.maxHeight = graphHeight;
    document.getElementById('dealSourcePieChart').style.maxWidth = graphWidth;

    document.getElementById('dealStageBarChart').style.height = graphHeight;
    document.getElementById('dealStageBarChart').style.width = graphWidth;
    document.getElementById('dealStageBarChart').style.maxHeight = graphHeight;
    document.getElementById('dealStageBarChart').style.maxWidth = graphWidth;

    document.getElementById('monthWiseDealsChart').style.height = graphHeight;
    document.getElementById('monthWiseDealsChart').style.width = '700px';
    document.getElementById('monthWiseDealsChart').style.maxHeight = graphHeight;
    document.getElementById('monthWiseDealsChart').style.maxWidth = '700px';

    document.getElementById('dealOwnerBarChart').style.height = graphHeight;
    document.getElementById('dealOwnerBarChart').style.width = '700px';
    document.getElementById('dealOwnerBarChart').style.maxHeight = graphHeight;
    document.getElementById('dealOwnerBarChart').style.maxWidth = '700px';

    
</script>
@endpush
@endsection
