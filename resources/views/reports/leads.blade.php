@extends('layouts.app')

@section('content')
@php
    $period = $period ?? 'today';
@endphp

<style>
@media (max-width: 767.98px) {
    .graph-card { margin-bottom: 20px; }
    .row > [class*='col-'] { margin-bottom: 20px; }
}
</style>

<x-report-shell
    title="Leads Analytics"
    :show-date-range="true"
    :period="$period"
    :start="$start ?? null"
    :end="$end ?? null"
    :show-run-button="true"
    :form-action="route('reports.leads')"
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
            <label for="status">Lead Status</label>
            <select name="status" id="status" class="form-select">
                <option value="">All Statuses</option>
                @foreach($statuses ?? [] as $statusOption)
                    <option value="{{ $statusOption }}" {{ ($statusFilter ?? '') === $statusOption ? 'selected' : '' }}>
                        {{ $statusOption }}
                    </option>
                @endforeach
            </select>
        </div>
    </x-slot>

    @if(isset($historicalSelected) && $historicalSelected)
        <div class="alert alert-warning mx-3">Unable to process the selected report for a historical financial year.</div>
    @endif

    @if(!$leads->isEmpty())
    <div class="row mb-4 px-3">
            <div class="col-md-6">
                <div class="card shadow-sm mb-4 graph-card" style="height: 100%;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Lead Source Distribution</h5>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <canvas id="leadSourcePieChart" style="max-width: 100%; height: 500px;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm mb-4 graph-card" style="height: 100%;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Lead Status Analysis</h5>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center flex-column">
                        <canvas id="leadStatusBarChart" style="max-width: 100%; height: 500px;"></canvas>
                        <div id="statusColorLegend" class="mt-3 w-100 text-center"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm mb-4 graph-card" style="height: 100%;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Month-wise Leads Analytics</h5>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <canvas id="monthWiseLeadsChart" style="max-width: 100%; height: 500px;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm mb-4 graph-card" style="height: 100%;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Lead Owner-wise Analytics</h5>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <canvas id="leadOwnerBarChart" style="max-width: 100%; height: 500px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        @endif

    @if($leads->isEmpty())
        <div class="alert alert-info mx-3">No leads found for this period.</div>
    @else
        <div class="report-table-wrap mx-3 mb-3">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Company Name</th>
                        <th>Contact Person</th>
                        <th>Lead Owner</th>
                        <th>Lead Source</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($leads as $lead)
                    <tr>
                        <td data-label="Title"><a class="text-decoration-none" href="{{ route('leads.show', $lead->id) }}">{{ $lead->title }}</a></td>
                        <td data-label="Company Name">{{ $lead->organization->name ?? '-' }}</td>
                        <td data-label="Contact Person">{{ $lead->person ? ($lead->person->first_name . ' ' . $lead->person->last_name) : '-' }}</td>
                        <td data-label="Lead Owner">{{ $lead->owner->name ?? '-' }}</td>
                        <td data-label="Lead Source">{{ $lead->leadSource->name ?? '-' }}</td>
                        <td data-label="Status">
                            @php
                                $status = $lead->status ?? '-';
                                $statusColors = [
                                    'New' => 'bg-primary',
                                    'Contacted' => 'bg-info text-dark',
                                    'Qualified' => 'bg-success-subtle text-success',
                                    'Lost' => 'bg-danger-subtle text-danger',
                                    'Converted' => 'bg-warning text-dark',
                                ];
                                $color = $statusColors[$status] ?? 'bg-secondary';
                            @endphp
                            <span class="badge {{ $color }}">{{ $status }}</span>
                        </td>
                        <td data-label="Created At">{{ $lead->created_at->format('d-m-Y H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('reports.partials.pagination', ['paginator' => $leads, 'noun' => 'leads'])
    @endif
</x-report-shell>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Pie chart data
    var leadSourceLabels = @json($leadSourceLabels ?? []);
    var leadSourceCounts = @json($leadSourceCounts ?? []);
    if (leadSourceLabels.length > 0 && leadSourceCounts.length > 0) {
        var ctxPie = document.getElementById('leadSourcePieChart').getContext('2d');
        new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: leadSourceLabels,
                datasets: [{
                    data: leadSourceCounts,
                    backgroundColor: [
                        '#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF'
                    ],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: true, text: 'Lead Source Analytics' }
                }
            }
        });
    }

    // Bar chart data
    var leadStatusLabels = @json($leadStatusLabels ?? []);
    var leadStatusCounts = @json($leadStatusCounts ?? []);
    if (leadStatusLabels.length > 0 && leadStatusCounts.length > 0) {
        var ctxBar = document.getElementById('leadStatusBarChart').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: leadStatusLabels,
                datasets: [{
                    label: 'Leads by Status',
                    data: leadStatusCounts,
                    backgroundColor: '#36A2EB',
                    barPercentage: 0.5, // reduce bar width
                    categoryPercentage: 0.5 // reduce bar width
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Lead Status Analytics' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // Month-wise leads data (Bar Chart with formatted labels)
    var monthLabels = @json($monthLabels ?? []);
    var monthLeadCounts = @json($monthLeadCounts ?? []);
    if (monthLabels.length > 0 && monthLeadCounts.length > 0) {
        // var formattedMonthLabels = monthLabels.map(function(label) {
        //     var date = new Date(label + '-01'); // Append day to ensure valid date
        //     return date.toLocaleString('default', { month: 'long', year: 'numeric' });
        // });

        var ctxBarMonth = document.getElementById('monthWiseLeadsChart').getContext('2d');
        new Chart(ctxBarMonth, {
            type: 'bar',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Leads per Month',
                    data: monthLeadCounts,
                    backgroundColor: '#36A2EB',
                    barPercentage: 0.5, // reduce bar width
                    categoryPercentage: 0.5 // reduce bar width
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Month-wise Leads Analytics' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // Lead owner-wise data
    var leadOwnerLabels = @json($leadOwnerLabels ?? []);
    var leadOwnerCounts = @json($leadOwnerCounts ?? []);
    if (leadOwnerLabels.length > 0 && leadOwnerCounts.length > 0) {
        var ctxOwnerBar = document.getElementById('leadOwnerBarChart').getContext('2d');
        new Chart(ctxOwnerBar, {
            type: 'bar',
            data: {
                labels: leadOwnerLabels,
                datasets: [{
                    label: 'Leads by Owner',
                    data: leadOwnerCounts,
                    backgroundColor: '#8BC34A',
                    barPercentage: 0.5,
                    categoryPercentage: 0.5
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Lead Owner-wise Analytics' }
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

    document.getElementById('leadSourcePieChart').style.height = graphHeight;
    document.getElementById('leadSourcePieChart').style.width = graphWidth;
    document.getElementById('leadSourcePieChart').style.maxHeight = graphHeight;
    document.getElementById('leadSourcePieChart').style.maxWidth = graphWidth;

    document.getElementById('leadStatusBarChart').style.height = graphHeight;
    document.getElementById('leadStatusBarChart').style.width = '700px';
    document.getElementById('leadStatusBarChart').style.maxHeight = graphHeight;
    document.getElementById('leadStatusBarChart').style.maxWidth = '700px';

    document.getElementById('monthWiseLeadsChart').style.height = graphHeight;
    document.getElementById('monthWiseLeadsChart').style.width = '700px';
    document.getElementById('monthWiseLeadsChart').style.maxHeight = graphHeight;
    document.getElementById('monthWiseLeadsChart').style.maxWidth = '700px';

    document.getElementById('leadOwnerBarChart').style.height = graphHeight;
    document.getElementById('leadOwnerBarChart').style.width = '700px';
    document.getElementById('leadOwnerBarChart').style.maxHeight = graphHeight;
    document.getElementById('leadOwnerBarChart').style.maxWidth = '700px';
</script>
@endpush
@endsection
