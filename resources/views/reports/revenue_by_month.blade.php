@extends('layouts.app')

@section('content')
@php
    $period = $period ?? 'this_month';
@endphp

<x-report-shell
    title="Revenue Reports"
    :show-date-range="true"
    :period="$period"
    :start="$start ?? null"
    :end="$end ?? null"
    :form-action="route('reports.revenue_by_month')"
>
    @if(isset($historicalSelected) && $historicalSelected)
        <div class="report-content-pad">
            <div class="alert alert-warning mb-0">Unable to process the selected report for a historical financial year.</div>
        </div>
    @endif

    @if(!$monthlyRevenue->isEmpty())
        <div class="report-chart-wrap">
            <canvas id="revenueBarChart"></canvas>
        </div>
    @endif

    @if($monthlyRevenue->isEmpty())
        <div class="report-content-pad">
            <div class="alert alert-info mb-0">No revenue data found for this period.</div>
        </div>
    @else
        <div class="report-table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($monthlyRevenue as $i => $row)
                        @php
                            $isInFy = true;
                            if (isset($selectedFyId) && $selectedFyId && isset($start) && isset($end)) {
                                try {
                                    $monthDate = \Carbon\Carbon::parse('01 ' . $row['month']);
                                    $fyStart = \Carbon\Carbon::parse($start)->startOfDay();
                                    $fyEnd = \Carbon\Carbon::parse($end)->endOfDay();
                                    $isInFy = $monthDate->between($fyStart, $fyEnd);
                                } catch (\Exception $e) {
                                    $isInFy = true;
                                }
                            }
                        @endphp
                        <tr>
                            <td data-label="Month">
                                @if($isInFy)
                                    <a href="#" class="month-link text-decoration-none" data-month="{{ $row['month'] }}">{{ $row['month'] }}</a>
                                @else
                                    <span class="text-muted" title="Month outside selected financial year">{{ $row['month'] }}</span>
                                @endif
                            </td>
                            <td data-label="Amount">{{ \App\Helpers\MoneyFormatter::format($row['amount']) }}</td>
                        </tr>
                    @endforeach
                    <tr style="font-weight:bold;background:#ffffff;">
                        <td class="text-end" data-label="Total">Total</td>
                        <td data-label="Total Amount">
                            {{ \App\Helpers\MoneyFormatter::format($monthlyRevenue->sum('amount')) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif
</x-report-shell>

<div class="modal fade" id="monthDealsModal" tabindex="-1" aria-labelledby="monthDealsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="monthDealsModalLabel">Closed Won Deals - <span id="modalMonthLabel"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="monthDealsTableContainer">
                    <div class="text-center py-4" id="monthDealsLoading" style="display:none;">Loading...</div>
                    <table class="report-table" id="monthDealsTable" style="display:none;">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Company</th>
                                <th>Contact Person</th>
                                <th>Stage</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <div class="alert alert-info" id="monthDealsEmpty" style="display:none;">No closed won deals found for this month.</div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var period = @json($period);
        var startDate = @json($start ?? null);
        var endDate = @json($end ?? null);

        function buildMonthAjaxUrl(month) {
            var params = new URLSearchParams({
                ajax: '1',
                month: month,
                period: period || 'custom'
            });
            if (startDate) params.set('start_date', startDate);
            if (endDate) params.set('end_date', endDate);
            return `{{ route('reports.revenue_by_month') }}?` + params.toString();
        }

        document.querySelectorAll('.month-link').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var month = this.getAttribute('data-month');
                document.getElementById('modalMonthLabel').textContent = month;
                document.getElementById('monthDealsLoading').style.display = '';
                document.getElementById('monthDealsTable').style.display = 'none';
                document.getElementById('monthDealsEmpty').style.display = 'none';
                fetch(buildMonthAjaxUrl(month))
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('monthDealsLoading').style.display = 'none';
                        var tbody = document.querySelector('#monthDealsTable tbody');
                        tbody.innerHTML = '';
                        if (data.length > 0) {
                            let totalAmount = 0;
                            data.forEach(function(deal) {
                                totalAmount += parseFloat(deal.amount);
                                tbody.innerHTML += `<tr>
                                    <td data-label="Title">${deal.title}</td>
                                    <td data-label="Company">${deal.company}</td>
                                    <td data-label="Contact Person">${deal.contact_person}</td>
                                    <td data-label="Stage">${deal.stage === 'Closed Won' ? '<span class="badge bg-success-subtle text-success">Closed Won</span>' : deal.stage}</td>
                                    <td data-label="Amount">&#8377;${parseFloat(deal.amount).toLocaleString('en-IN', {minimumFractionDigits:2})}</td>
                                </tr>`;
                            });
                            tbody.innerHTML += `<tr style='font-weight:bold;background:#f6fff6;'><td colspan='4' class='text-end' data-label="Total">Total</td><td data-label="Total Amount">&#8377;${totalAmount.toLocaleString('en-IN', {minimumFractionDigits:2})}</td></tr>`;
                            document.getElementById('monthDealsTable').style.display = '';
                            document.getElementById('monthDealsEmpty').style.display = 'none';
                        } else {
                            document.getElementById('monthDealsTable').style.display = 'none';
                            document.getElementById('monthDealsEmpty').style.display = '';
                        }
                        var modal = new bootstrap.Modal(document.getElementById('monthDealsModal'));
                        modal.show();
                    })
                    .catch(() => {
                        document.getElementById('monthDealsLoading').style.display = 'none';
                        document.getElementById('monthDealsTable').style.display = 'none';
                        document.getElementById('monthDealsEmpty').style.display = '';
                        var modal = new bootstrap.Modal(document.getElementById('monthDealsModal'));
                        modal.show();
                    });
            });
        });

        var revenueLabels = @json($monthlyRevenue->pluck('month'));
        var revenueAmounts = @json($monthlyRevenue->pluck('amount'));
        if (revenueLabels.length > 0 && revenueAmounts.length > 0) {
            var ctxBar = document.getElementById('revenueBarChart').getContext('2d');
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: revenueLabels,
                    datasets: [{
                        label: 'Revenue',
                        data: revenueAmounts,
                        backgroundColor: '#4BC0C0',
                        barPercentage: 0.5,
                        categoryPercentage: 0.5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        title: { display: true, text: 'Revenue by Month' }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }
    });
</script>
@endpush
@endsection
