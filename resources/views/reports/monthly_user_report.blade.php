@extends('layouts.app')

@section('content')
@php
    $exportUrl = (isset($results) && empty($selectedOutsideFy) && count($results) > 0)
        ? route('reports.monthly_user_report_excel', array_merge(request()->all()))
        : null;
@endphp

<x-report-shell
    title="Monthly User Report"
    :show-date-range="false"
    :export-url="$exportUrl"
    :show-run-button="true"
>
    <x-slot name="toolbar">
        <div class="toolbar-field">
            <label for="month">Month</label>
            <input type="month" id="month" name="month" class="form-control" value="{{ request('month', date('Y-m')) }}">
        </div>
        <div class="toolbar-field">
            <label for="user_id">Employee</label>
            <select id="user_id" name="user_id" class="form-select">
                <option value="">All</option>
                @foreach(\App\Models\User::where('crm_role_type', '!=', '0')->get() as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <a href="{{ url()->current() }}" class="btn btn-secondary btn-sm">Reset</a>
    </x-slot>

    @if(isset($results))
        <div class="report-table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Employee Name</th>
                        <th>Total Calls Made</th>
                        <th>Total Leads Generated</th>
                        <th>Total Deals Generated</th>
                        <th>Deals Closed Won</th>
                        <th>Deals Closed Lost</th>
                        <th>Deal Value</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $row)
                    <tr>
                        <td data-label="Month">{{ $row['month'] }}</td>
                        <td data-label="Employee Name">{{ $row['user']->name }}</td>
                        <td data-label="Total Calls Made">
                            @if($row['calls_count'] > 0)
                                <a href="#" class="open-list-modal" data-type="calls" data-user="{{ $row['user']->id }}" data-month="{{ request('month', date('Y-m')) }}">
                                    {{ $row['calls_count'] }}
                                </a>
                            @else
                                {{ $row['calls_count'] }}
                            @endif
                        </td>
                        <td data-label="Total Leads Generated">
                            @if(isset($row['leads_count']) && $row['leads_count'] > 0)
                                <a href="#" class="open-list-modal" data-type="leads" data-user="{{ $row['user']->id }}" data-month="{{ request('month', date('Y-m')) }}">
                                    {{ $row['leads_count'] }}
                                </a>
                            @else
                                {{ $row['leads_count'] }}
                            @endif
                        </td>
                        <td data-label="Total Deals Generated">
                            @if(isset($row['deals_count']) && $row['deals_count'] > 0)
                                <a href="#" class="open-list-modal" data-type="deals" data-user="{{ $row['user']->id }}" data-month="{{ request('month', date('Y-m')) }}">
                                    {{ $row['deals_count'] }}
                                </a>
                            @else
                                {{ $row['deals_count'] }}
                            @endif
                        </td>
                        <td data-label="Deals Closed Won">
                            @if(isset($row['deals_won_count']) && $row['deals_won_count'] > 0)
                            <a href="#" class="open-list-modal" data-type="deals_won" data-user="{{ $row['user']->id }}" data-month="{{ request('month', date('Y-m')) }}">
                                {{ $row['deals_won_count'] }}
                            </a>
                            @else
                                {{ $row['deals_won_count'] }}
                            @endif
                        </td>
                        <td data-label="Deals Closed Lost">
                            @if(isset($row['deals_lost_count']) && $row['deals_lost_count'] > 0)
                            <a href="#" class="open-list-modal" data-type="deals_lost" data-user="{{ $row['user']->id }}" data-month="{{ request('month', date('Y-m')) }}">
                                {{ $row['deals_lost_count'] }}
                            </a>
                            @else
                                {{ $row['deals_lost_count'] }}
                            @endif
                        </td>
                        <td data-label="Deal Value">{{ \App\Helpers\MoneyFormatter::format($row['deal_amount']) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="report-empty">Data Not Available this Financial  Year</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    @include('reports.partials.pagination', ['paginator' => null, 'noun' => 'results'])
</x-report-shell>

<div class="modal fade" id="listModal" tabindex="-1" aria-labelledby="listModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="listModalLabel">Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="listModalBody">
                Loading...
            </div>
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

                        var url = '';
                        if(type === 'leads') {
                            url = '/reports/user-leads/' + userId + '?month=' + month;
                        } else if(type === 'deals') {
                            url = '/reports/user-deals/' + userId + '?month=' + month;
                        } else if(type === 'deals_won') {
                            url = '/reports/user-deals/' + userId + '?month=' + month + '&stage=Closed Won';
                        } else if(type === 'deals_lost') {
                            url = '/reports/user-deals/' + userId + '?month=' + month + '&stage=Closed Lost';
                        } else if(type === 'calls') {
                            url = '/reports/user-calls/' + userId + '?month=' + month;
                        }
                        var modal = new bootstrap.Modal(document.getElementById('listModal'));
                        document.getElementById('listModalBody').innerHTML = 'Loading...';
                        fetch(url)
                                .then(response => response.text())
                                .then(html => {
                                        document.getElementById('listModalBody').innerHTML = html;
                                });
                        modal.show();
                });
        });
});
</script>
@endpush
@endsection
