@extends('layouts.app')

@section('content')
<x-report-shell
    title="Month Lead Comparison Report"
    :show-date-range="false"
    :export-url="null"
    :show-run-button="true"
    :form-action="route('reports.month_lead_comparison')"
>
    <x-slot name="toolbar">
        <div class="toolbar-field">
            <label for="time_range">Time Range</label>
            <select name="time_range" id="time_range" class="form-select">
                <option value="current_vs_previous" {{ $timeRange === 'current_vs_previous' ? 'selected' : '' }}>Current Month vs Previous Month</option>
                <option value="last_3_months" {{ $timeRange === 'last_3_months' ? 'selected' : '' }}>Last 3 Months</option>
                <option value="last_6_months" {{ $timeRange === 'last_6_months' ? 'selected' : '' }}>Last 6 Months</option>
            </select>
        </div>
        <div class="toolbar-field">
            <label for="user_id">User</label>
            <select name="user_id" id="user_id" class="form-select">
                <option value="all" {{ $selectedUserId === 'all' ? 'selected' : '' }}>All Users</option>
                @foreach ($usersList as $user)
                    <option value="{{ $user->id }}" {{ $selectedUserId == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
    </x-slot>

    <div class="report-chart-wrap report-chart-wrap-lg">
        <canvas id="monthLeadComparisonChart"></canvas>
    </div>

    @if($usersList->isEmpty())
        <div class="report-content-pad">
            <div class="alert alert-info mb-0">No users available for comparison.</div>
        </div>
    @elseif($leadData->isEmpty())
        <div class="report-content-pad">
            <div class="alert alert-info mb-0">No lead data found for the selected period.</div>
        </div>
    @endif
</x-report-shell>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('monthLeadComparisonChart').getContext('2d');
        const months   = @json($months->values());
        const leadData = @json($leadData);

        const colors = [
            'rgba(255, 99,  132, 0.8)',
            'rgba(54,  162, 235, 0.8)',
            'rgba(255, 206, 86,  0.8)',
            'rgba(75,  192, 192, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(255, 159, 64,  0.8)',
        ];

        const datasets = Object.entries(leadData).map(([userName, data], index) => {
            return {
                label: userName,
                data: months.map(month => data[month] ?? 0),
                backgroundColor: colors[index % colors.length],
                borderColor: colors[index % colors.length].replace('0.8', '1'),
                borderWidth: 1
            };
        });

        if (datasets.length === 0) {
            console.warn('No datasets available to render the chart.');
        } else {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: months,
                    datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const index = context.dataIndex;
                                    const allDatasets = context.chart.data.datasets;
                                    let total = 0;
                                    allDatasets.forEach(ds => { total += ds.data[index]; });
                                    const count = context.raw;
                                    const pct = total > 0 ? ((count / total) * 100).toFixed(2) : 0;
                                    return `${context.dataset.label}: ${count} leads (${pct}%)`;
                                }
                            }
                        },
                        datalabels: {
                            color: 'white',
                            anchor: 'center',
                            align: 'center',
                            font: { weight: 'bold', size: 11 },
                            formatter: function(value, context) {
                                if (value > 0) {
                                    const userName = context.dataset.label;
                                    return userName + '\n' + '(' + value + ' leads)';
                                }
                                return "";
                            }
                        },
                        legend: {
                            position: 'top',
                        },
                    },
                    scales: {
                        x: {
                            stacked: true,
                            title: {
                                display: true,
                                text: 'Months'
                            }
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Lead Count'
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }
    });
</script>
@endpush
