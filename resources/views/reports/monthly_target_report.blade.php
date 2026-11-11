@extends('layouts.app')
@section('content')

@php
    $period = $period ?? 'this_month';
    $showMonthColumn = false;
    if (!empty($startDate) && !empty($endDate)) {
        try {
            $showMonthColumn = \Carbon\Carbon::parse($startDate)->format('Y-m') !== \Carbon\Carbon::parse($endDate)->format('Y-m');
        } catch (\Exception $e) {
            $showMonthColumn = true;
        }
    }
    $colCount = $showMonthColumn ? 8 : 7;
@endphp

<x-report-shell
    title="Monthly Target Report"
    :show-date-range="true"
    :period="$period"
    :start="$startDate ?? null"
    :end="$endDate ?? null"
    :show-run-button="true"
    :form-action="route('reports.monthly_target_report')"
    :period-text="$periodLabel ?? ''"
>
    <x-slot name="toolbar">
        <div class="toolbar-field">
            <label for="user_id">Employee</label>
            <select name="user_id" id="user_id" class="form-select">
                <option value="">All Users</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ (string) ($user_id ?? '') === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="toolbar-field">
            <label for="category">Category</label>
            <select name="category" id="category" class="form-select">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ (string) ($categoryId ?? '') === (string) $category->id ? 'selected' : '' }}>{{ $category->category_name }}</option>
                @endforeach
            </select>
        </div>
    </x-slot>

    <div class="report-table-wrap">
        <table class="report-table monthly-target-table">
            <thead>
                <tr>
                    @if($showMonthColumn)
                        <th>Month</th>
                    @endif
                    <th>Employee</th>
                    <th class="is-number">New Leads</th>
                    <th class="is-number">Target Deals</th>
                    <th class="is-number">Closed Won Deals</th>
                    <th class="is-number">Target Revenue</th>
                    <th class="is-number">Achieved Revenue</th>
                    <th class="is-number">Achievement %</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report as $row)
                    @php
                        $progress = (float) ($row['achievement'] ?? 0);
                        $barColor = 'bg-danger';
                        if ($progress > 85) {
                            $barColor = 'bg-success';
                        } elseif ($progress > 25) {
                            $barColor = 'bg-primary';
                        }
                    @endphp
                    <tr>
                        @if($showMonthColumn)
                            <td data-label="Month">{{ $row['period_label'] ?? \Carbon\Carbon::parse($row['month'] . '-01')->format('F Y') }}</td>
                        @endif
                        <td data-label="Employee" class="employee-cell">{{ $row['user_name'] }}</td>
                        <td data-label="New Leads" class="is-number">
                            @if($row['generated_leads'] > 0)
                                <a href="#" class="open-list-modal" data-type="leads" data-user="{{ $row['user_id'] }}" data-month="{{ $row['month'] }}" data-start="{{ $row['start_date'] ?? '' }}" data-end="{{ $row['end_date'] ?? '' }}" data-category="{{ $categoryId ?? '' }}">{{ $row['generated_leads'] }}</a>
                            @else
                                {{ $row['generated_leads'] }}
                            @endif
                        </td>
                        <td data-label="Target Deals" class="is-number">
                            @if($row['target_deals'] > 0)
                                <a href="#" class="open-list-modal" data-type="deals" data-user="{{ $row['user_id'] }}" data-month="{{ $row['month'] }}" data-start="{{ $row['start_date'] ?? '' }}" data-end="{{ $row['end_date'] ?? '' }}" data-category="{{ $categoryId ?? '' }}">{{ $row['target_deals'] }}</a>
                            @else
                                {{ $row['target_deals'] }}
                            @endif
                        </td>
                        <td data-label="Closed Won Deals" class="is-number">
                            @if($row['closed_won_deals'] > 0)
                                <a href="#" class="open-list-modal" data-type="deals_won" data-user="{{ $row['user_id'] }}" data-month="{{ $row['month'] }}" data-start="{{ $row['start_date'] ?? '' }}" data-end="{{ $row['end_date'] ?? '' }}" data-category="{{ $categoryId ?? '' }}">{{ $row['closed_won_deals'] }}</a>
                            @else
                                {{ $row['closed_won_deals'] }}
                            @endif
                        </td>
                        <td data-label="Target Revenue" class="is-number">{{ $currency_symbol }} {{ number_format($row['target_revenue'], 2) }}</td>
                        <td data-label="Achieved Revenue" class="is-number">
                            @if($row['achieved_revenue'] > 0)
                                <a href="#" class="open-list-modal" data-type="deals_won" data-user="{{ $row['user_id'] }}" data-month="{{ $row['month'] }}" data-start="{{ $row['start_date'] ?? '' }}" data-end="{{ $row['end_date'] ?? '' }}" data-category="{{ $categoryId ?? '' }}">{{ $currency_symbol }} {{ number_format($row['achieved_revenue'], 2) }}</a>
                            @else
                                {{ $currency_symbol }} {{ number_format($row['achieved_revenue'], 2) }}
                            @endif
                        </td>
                        <td data-label="Achievement %" class="is-number achievement-cell">
                            <div class="progress">
                                <div class="progress-bar {{ $barColor }}" role="progressbar" style="width: {{ min(100, max(0, $progress)) }}%;" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                                    {{ number_format($progress, 0) }}%
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $colCount }}" class="report-empty">No data available for selected filters.</td></tr>
                @endforelse
            </tbody>
            @if(!empty($report))
                <tfoot>
                    <tr class="total-row">
                        <td data-label="Employee" class="employee-cell" colspan="{{ $showMonthColumn ? 2 : 1 }}">Total (All Employees)</td>
                        <td data-label="New Leads" class="is-number">{{ $totals['generated_leads'] ?? 0 }}</td>
                        <td data-label="Target Deals" class="is-number">{{ $totals['target_deals'] ?? 0 }}</td>
                        <td data-label="Closed Won Deals" class="is-number">{{ $totals['closed_won_deals'] ?? 0 }}</td>
                        <td data-label="Target Revenue" class="is-number">{{ $currency_symbol }} {{ number_format((float) ($totals['target_revenue'] ?? 0), 2) }}</td>
                        <td data-label="Achieved Revenue" class="is-number">{{ $currency_symbol }} {{ number_format((float) ($totals['achieved_revenue'] ?? 0), 2) }}</td>
                        <td data-label="Achievement %" class="is-number">-</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</x-report-shell>

<div class="modal fade" id="listModal" tabindex="-1" aria-labelledby="listModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="listModalLabel">Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="listModalBody">Loading...</div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.open-list-modal').forEach(function(el) {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            var type = this.getAttribute('data-type');
            var userId = this.getAttribute('data-user');
            var month = this.getAttribute('data-month');
            var startDate = this.getAttribute('data-start');
            var endDate = this.getAttribute('data-end');
            var category = this.getAttribute('data-category');
            var baseUrl = type === 'leads' ? '/reports/user-leads/' + userId : '/reports/user-deals/' + userId;
            var params = new URLSearchParams();
            if (startDate && endDate) {
                params.set('start_date', startDate);
                params.set('end_date', endDate);
            } else if (month) {
                params.set('month', month);
            }
            if (type === 'deals_won') params.set('stage', 'Closed Won');
            if (category) params.set('category', category);
            var url = baseUrl + (params.toString() ? ('?' + params.toString()) : '');
            var modal = new bootstrap.Modal(document.getElementById('listModal'));
            document.getElementById('listModalBody').innerHTML = 'Loading...';
            fetch(url).then(r => r.text()).then(html => { document.getElementById('listModalBody').innerHTML = html; });
            modal.show();
        });
    });
});
</script>
@endpush
@endsection
