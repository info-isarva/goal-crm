@extends('layouts.app')

@section('content')
@php
    $period = $period ?? 'this_month';
@endphp

<x-report-shell
    title="User Wise Report"
    :show-date-range="true"
    :period="$period"
    :start="$start ?? ($fromDate ?? null)"
    :end="$end ?? ($toDate ?? null)"
    :form-action="route('reports.user_wise')"
>
    <x-slot name="toolbar">
        <div class="toolbar-field">
            <label for="user_id">User</label>
            <select id="user_id" name="user_id" class="form-select select2">
                <option value="">All Users</option>
                @foreach(App\Models\User::orderBy('name')->where('crm_role_type', '!=', '0')->get() as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
    </x-slot>

    <div class="report-table-wrap">
        <table class="report-table">
            <thead>
                <tr>
                    <th>User Name</th>
                    <th>Email</th>
                    <th>Number of Leads</th>
                    <th>Converted Leads</th>
                    <th>Number of Deals</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report as $row)
                    <tr>
                        <td data-label="User Name">{{ $row['user']->name }}</td>
                        <td data-label="Email">{{ $row['user']->email }}</td>
                        <td data-label="Number of Leads">
                            <a href="#" class="show-leads" data-user-id="{{ $row['user']->id }}">{{ $row['leads_count'] }}</a>
                        </td>
                        <td data-label="Converted Leads">
                            <a href="#" class="show-converted-leads" data-user-id="{{ $row['user']->id }}">{{ $row['converted_leads_count'] }}</a>
                        </td>
                        <td data-label="Number of Deals">
                            <a href="#" class="show-deals" data-user-id="{{ $row['user']->id }}">{{ $row['deals_count'] }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-report-shell>

<div class="modal fade" id="dealsModal" tabindex="-1" aria-labelledby="dealsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dealsModalLabel">Deals List</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="dealsModalBody"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="leadsModal" tabindex="-1" aria-labelledby="leadsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leadsModalLabel">Leads List</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="leadsModalBody"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="convertedLeadsModal" tabindex="-1" aria-labelledby="convertedLeadsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="convertedLeadsModalLabel">Converted Leads List</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="convertedLeadsModalBody"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function dateParams() {
        var start = document.getElementById('startDateInput');
        var end = document.getElementById('endDateInput');
        var fromDate = start ? start.value : '';
        var toDate = end ? end.value : '';
        return '?from_date=' + encodeURIComponent(fromDate) + '&to_date=' + encodeURIComponent(toDate);
    }

    document.querySelectorAll('.show-leads').forEach(function(el) {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            var userId = this.getAttribute('data-user-id');
            fetch('/reports/user-leads/' + userId + dateParams())
                .then(response => response.text())
                .then(html => {
                    document.getElementById('leadsModalBody').innerHTML = html;
                    $('#leadsModal').modal('show');
                });
        });
    });

    document.querySelectorAll('.show-deals').forEach(function(el) {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            var userId = this.getAttribute('data-user-id');
            fetch('/reports/user-deals/' + userId + dateParams())
                .then(response => response.text())
                .then(html => {
                    document.getElementById('dealsModalBody').innerHTML = html;
                    $('#dealsModal').modal('show');
                });
        });
    });

    document.querySelectorAll('.show-converted-leads').forEach(function(el) {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            var userId = this.getAttribute('data-user-id');
            fetch('/reports/converted-leads/' + userId + dateParams())
                .then(response => response.text())
                .then(html => {
                    document.getElementById('convertedLeadsModalBody').innerHTML = html;
                    $('#convertedLeadsModal').modal('show');
                });
        });
    });
});
</script>
@endpush
