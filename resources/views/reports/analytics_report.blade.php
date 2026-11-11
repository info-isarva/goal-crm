@extends('layouts.app')

@section('content')
<x-report-shell
    title="User Performance Analytics Reports"
    :show-date-range="false"
    :export-url="null"
    :show-run-button="true"
    :form-action="route('analytics.reports')"
    :period-text="$periodText ?? 'This Month'"
>
    <x-slot name="toolbar">
        @include('reports.partials.month_range', [
            'monthPeriod' => $monthPeriod ?? 'this_month',
            'fromYear' => $fromYear ?? date('Y'),
            'fromMonth' => $fromMonth ?? date('n'),
            'toYear' => $toYear ?? date('Y'),
            'toMonth' => $toMonth ?? date('n'),
        ])
        <div class="toolbar-field">
            <label for="user_id">User</label>
            <select name="user_id" id="user_id" class="form-select">
                <option value="">All Users</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
    </x-slot>

    <div class="chart-container mb-4" style="position: relative; height: 50vh; width: 100%;">
        <canvas id="analyticsChart"></canvas>
    </div>

    <div class="report-table-wrap">
        <table class="report-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Year</th>
                    <th>Month</th>
                    <th>Sales Target</th>
                    <th>Achieved Sales</th>
                    <th>Progress (%)</th>
                    <th>Generated Deals</th>
                    <th>Contacted Leads</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report as $row)
                    <tr>
                        <td data-label="User">{{ $row['user_name'] }}</td>
                        <td data-label="Year">{{ $row['year'] }}</td>
                        <td data-label="Month">{{ date('F', mktime(0, 0, 0, $row['month'], 1)) }}</td>
                        <td data-label="Sales Target">{{ $currency_symbol }}{{ number_format($row['sales_target'], 2) }}</td>
                        <td data-label="Achieved Sales">{{ $currency_symbol }}{{ number_format($row['achieved_sales'], 2) }}</td>
                        <td data-label="Progress (%)">{{ $row['progress'] }}%</td>
                        <td data-label="Generated Deals">{{ $row['generated_deals'] }}</td>
                        <td data-label="Contacted Leads">{{ $row['contacted_leads'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="report-empty">No data available for the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('reports.partials.pagination', ['paginator' => null, 'noun' => 'results'])
</x-report-shell>
@endsection

@push('scripts')
@include('reports.partials.month_range_js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('analyticsChart').getContext('2d');
        const chartData = @json($report);
        const monthNames = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
        ];
        const labels = chartData.map(row => {
            const monthName = monthNames[row.month - 1];
            return `${row.user_name} (${monthName} ${row.year})`;
        });
        const achievedSales = chartData.map(row => row.achieved_sales);
        const salesTarget = chartData.map(row => row.sales_target);
        const generatedDeals = chartData.map(row => row.generated_deals || 0);
        const contactedLeads = chartData.map(row => row.contacted_leads || 0);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Achieved Sales',
                        data: achievedSales,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderWidth: 2,
                    },
                    {
                        label: 'Sales Target',
                        data: salesTarget,
                        borderColor: 'rgba(255, 99, 132, 1)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderWidth: 2,
                    },
                    {
                        label: 'Generated Deals',
                        data: generatedDeals,
                        type: 'line',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderWidth: 2,
                        fill: false,
                        yAxisID: 'y1',
                    },
                    {
                        label: 'Contacted Leads',
                        data: contactedLeads,
                        type: 'line',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        backgroundColor: 'rgba(153, 102, 255, 0.2)',
                        borderWidth: 2,
                        fill: false,
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Target Values (e.g., Deals or Sales)'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        title: {
                            display: true,
                            text: 'Counts (Deals / Leads)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Users and Time Period'
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
