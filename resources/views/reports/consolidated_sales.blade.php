@extends('layouts.app')

@section('content')
@php
    $formatInr = function ($amount) {
        $amount = (float) $amount;
        $negative = $amount < 0;
        $amount = abs($amount);
        $int = (string) (int) round($amount);
        $last3 = substr($int, -3);
        $rest = substr($int, 0, -3);
        if ($rest !== '' && $rest !== false) {
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $formatted = $rest . ',' . $last3;
        } else {
            $formatted = $last3;
        }
        return '₹' . ($negative ? '-' : '') . $formatted;
    };
@endphp

<x-report-shell
    title="Consolidated Sales Report"
    :show-date-range="true"
    :period="$period"
    :start="$start ?? null"
    :end="$end ?? null"
    :period-text="$periodText ?? ''"
    :show-run-button="true"
    :form-action="route('reports.consolidated_sales')"
    :export-url="route('reports.consolidated_sales_excel', request()->only('period', 'start_date', 'end_date', 'user_id'))">
    @if(in_array((int) auth()->user()->crm_role_type, [0, 1, 2], true))
        <x-slot name="toolbar">
            <div class="toolbar-field">
                <label for="user_id">Team Member</label>
                <select id="user_id" name="user_id" class="form-select">
                    <option value="">All</option>
                    @foreach(\App\Models\User::where('status', 1)->whereNotIn('crm_role_type', [0, 1])->orderBy('name')->get() as $u)
                        <option value="{{ $u->id }}" {{ (string) request('user_id') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
        </x-slot>
    @endif

    <div class="csr-report">
        {{-- Sales Target & Revenue Status Table (Screenshot 1 Top Format) --}}
        <div class="csr-scroll mb-4">
            <table class="csr-table csr-revenue">
                <thead>
                    <tr>
                        <th colspan="8" class="csr-title-blue">Sales Target &amp; Revenue Status - {{ $monthLabel }}</th>
                    </tr>
                    <tr class="csr-rev-heads">
                        <th class="csr-th-blue">Team Members</th>
                        <th class="csr-th-red">Estimated Revenue Current Week</th>
                        <th class="csr-th-maroon">Estimated Revenue in Pipeline</th>
                        <th class="csr-th-maroon">Month Achieved (INR)</th>
                        <th class="csr-th-blue">Month Balance</th>
                        <th class="csr-th-maroon">{{ $weekLabel }} Target Amt (INR)</th>
                        <th class="csr-th-maroon">{{ $weekLabel }} Achieved Amt (INR)</th>
                        <th class="csr-th-maroon">{{ $weekLabel }} Balance Amt (INR)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td class="csr-name">{{ $row['user']->name }}</td>
                            <td class="csr-num">{{ $formatInr($row['est_week']) }}</td>
                            <td class="csr-num">{{ $formatInr($row['pipeline']) }}</td>
                            <td class="csr-num">{{ $formatInr($row['month_achieve']) }}</td>
                            <td class="csr-num">{{ $formatInr($row['month_balance']) }}</td>
                            <td class="csr-num">{{ $formatInr($row['week_target']) }}</td>
                            <td class="csr-num">{{ $formatInr($row['week_achieve']) }}</td>
                            <td class="csr-num">{{ $formatInr($row['week_balance']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-3">No daily reports submitted in this period.</td></tr>
                    @endforelse
                </tbody>
                @if(count($rows))
                <tfoot>
                    <tr class="csr-total">
                        <td class="fw-bold">Total</td>
                        <td class="csr-num fw-bold">{{ $formatInr($totals['est_week']) }}</td>
                        <td class="csr-num fw-bold">{{ $formatInr($totals['pipeline']) }}</td>
                        <td class="csr-num fw-bold">{{ $formatInr($totals['month_achieve']) }}</td>
                        <td class="csr-num fw-bold">{{ $formatInr($totals['month_balance']) }}</td>
                        <td class="csr-num fw-bold">{{ $formatInr($totals['week_target']) }}</td>
                        <td class="csr-num fw-bold">{{ $formatInr($totals['week_achieve']) }}</td>
                        <td class="csr-num fw-bold">{{ $formatInr($totals['week_balance']) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        <div class="csr-bottom">
            {{-- Consolidated Activity Matrix (Screenshot 1 Bottom Left Format) --}}
            <div class="csr-bottom-left">
                <div class="csr-scroll">
                    <table class="csr-table csr-matrix">
                        <thead>
                            <tr>
                                <th colspan="{{ 2 + count($rows) }}" class="csr-title-yellow">Consolidated Report - {{ $shortMonthLabel }}</th>
                            </tr>
                            <tr class="csr-matrix-heads">
                                <th class="csr-th-teal">Team Member</th>
                                @foreach($rows as $row)
                                    <th class="csr-th-teal">{{ $row['user']->name }}</th>
                                @endforeach
                                <th class="csr-th-teal">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($metricRows as $metric)
                                <tr>
                                    <td class="csr-metric-label">{{ $metric['label'] }}</td>
                                    @foreach($rows as $row)
                                        <td class="csr-num">{{ $row['metrics'][$metric['key']] ?? 0 }}</td>
                                    @endforeach
                                    <td class="csr-num csr-total-col">{{ $totals['metrics'][$metric['key']] ?? 0 }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ 2 + max(1, count($rows)) }}" class="text-center py-3">No activity metrics for this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Analytics Reports (Screenshot 1 Bottom Right Format) --}}
            <div class="csr-bottom-right">
                <div class="csr-chart-card mb-3">
                    <div class="csr-chart-header">
                        <span class="legend-box est-week-box"></span> Estimated Revenue Current Week
                        <span class="legend-box pipeline-box ms-3"></span> Estimated Revenue in Pipeline
                    </div>
                    <canvas id="csrRevenueChart" height="200"></canvas>
                </div>
                <div class="csr-chart-card">
                    <div class="csr-pie-title">Consolidated Report - {{ $shortMonthLabel }}</div>
                    <div class="csr-pie-wrap">
                        <canvas id="csrPieChart" height="240"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-report-shell>

<style>
.csr-report { font-size: 13px; color: #111827; font-family: system-ui, -apple-system, sans-serif; }
.csr-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
.csr-table { width: 100%; border-collapse: collapse; min-width: 720px; font-size: 13px; }
.csr-table th, .csr-table td { border: 1px solid #1e293b; padding: 7px 10px; vertical-align: middle; }
.csr-title-blue { background: #002060; color: #ffffff; text-align: center; font-weight: 700; font-size: 14px; letter-spacing: 0.3px; }
.csr-title-yellow { background: #f4d03f; color: #000000; text-align: center; font-weight: 700; font-size: 14px; }
.csr-th-blue { background: #002060 !important; color: #ffffff !important; text-align: center; font-weight: 700; font-size: 12px; }
.csr-th-red { background: #c0392b !important; color: #ffffff !important; text-align: center; font-weight: 700; font-size: 12px; }
.csr-th-maroon { background: #922b21 !important; color: #ffffff !important; text-align: center; font-weight: 700; font-size: 12px; }
.csr-th-teal { background: #104e5b !important; color: #ffffff !important; text-align: center; font-weight: 700; font-size: 12px; }
.csr-name { text-align: left; font-weight: 600; white-space: nowrap; background: #ffffff; color: #111827; }
.csr-num { text-align: center; white-space: nowrap; background: #ffffff; }
.csr-total td { background: #002060 !important; color: #ffffff !important; font-weight: 700; }
.csr-metric-label { background: #002060; color: #ffffff; font-weight: 700; white-space: nowrap; padding-left: 12px; }
.csr-total-col { background: #fde9a9 !important; color: #000000 !important; font-weight: 700; }
.csr-matrix { min-width: 480px; }
.csr-matrix th, .csr-matrix td { min-width: 80px; }
.csr-matrix th:first-child, .csr-matrix td:first-child { min-width: 150px; }
.csr-bottom { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr); gap: 20px; align-items: start; width: 100%; }
.csr-bottom > div { min-width: 0; width: 100%; }
.csr-chart-card { border: 1px solid #cbd5e1; background: #ffffff; padding: 14px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.csr-chart-header { font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 12px; display: flex; align-items: center; justify-content: center; }
.csr-chart-header .legend-box { display: inline-block; width: 12px; height: 12px; border-radius: 2px; margin-right: 4px; }
.csr-chart-header .est-week-box { background: #5dade2; }
.csr-chart-header .pipeline-box { background: #e74c3c; }
.csr-pie-title { text-align: center; color: #002060; font-weight: 700; margin-bottom: 12px; font-size: 15px; }
.csr-pie-wrap { position: relative; width: 100%; min-height: 240px; }
@media (max-width: 1400px) {
    .csr-bottom { grid-template-columns: 1fr; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script>
(function () {
    var labels = @json($chartLabels);
    var estWeek = @json($chartEstWeek);
    var pipeline = @json($chartPipeline);
    var pieLabels = @json($pieLabels);
    var pieValues = @json($pieValues);

    var barEl = document.getElementById('csrRevenueChart');
    if (barEl && labels.length) {
        new Chart(barEl, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Estimated Revenue Current Week',
                        data: estWeek,
                        backgroundColor: '#5dade2',
                        borderColor: '#2e86c1',
                        borderWidth: 1
                    },
                    {
                        label: 'Estimated Revenue in Pipeline',
                        data: pipeline,
                        backgroundColor: '#e74c3c',
                        borderColor: '#c0392b',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                var n = Number(ctx.raw || 0);
                                return ctx.dataset.label + ': ₹' + n.toLocaleString('en-IN');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (v) { return '₹' + Number(v).toLocaleString('en-IN'); }
                        }
                    }
                }
            }
        });
    }

    var pieEl = document.getElementById('csrPieChart');
    if (pieEl && pieLabels.length) {
        var totalVal = pieValues.reduce(function(a, b) { return a + b; }, 0);
        new Chart(pieEl, {
            type: 'pie',
            plugins: [ChartDataLabels],
            data: {
                labels: pieLabels,
                datasets: [{
                    data: pieValues,
                    backgroundColor: [
                        '#4285f4','#ea4335','#fbbc05','#34a853','#ff6d01',
                        '#46bdc6','#ab47bc','#00acc1','#ff7043','#9e9d24',
                        '#5c6bc0','#ec407a','#26a69a','#8d6e63','#78909c'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: 20
                },
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            font: { size: 11, weight: '500' }
                        }
                    },
                    datalabels: {
                        color: '#111827',
                        font: { size: 10, weight: '600' },
                        formatter: function(value) {
                            if (!totalVal) return '';
                            var pct = ((value / totalVal) * 100).toFixed(1);
                            return pct >= 2.0 ? pct + '%' : '';
                        },
                        anchor: 'end',
                        align: 'end',
                        offset: 4
                    },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                var pct = totalVal ? ((ctx.raw / totalVal) * 100).toFixed(1) : 0;
                                return ctx.label + ': ' + ctx.raw + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
})();
</script>
@endsection
