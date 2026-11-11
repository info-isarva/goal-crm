@extends('layouts.app')

@section('content')
@php
    $period = $period ?? 'today';
    $exportUrl = route('calllogs.report.excel', request()->only('period', 'start_date', 'end_date', 'user_id'));
@endphp

<x-report-shell
    title="Calls Reports"
    :show-date-range="true"
    :period="$period"
    :start="$start ?? null"
    :end="$end ?? null"
    :export-url="$exportUrl"
    :show-run-button="true"
    :form-action="route('calllogs.report')"
>
    <x-slot name="toolbar">
        <div class="toolbar-field">
            <label for="user_id">User</label>
            <select name="user_id" id="user_id" class="form-select">
                <option value="">All Users</option>
                @foreach($filterUsers as $owner)
                    <option value="{{ $owner->id }}" {{ (string) ($filterUserId ?? '') === (string) $owner->id ? 'selected' : '' }}>
                        {{ $owner->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </x-slot>

    <div class="report-table-wrap">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Total Calls</th>
                    <th>Call Logs</th>
                    <th>Contacts Followup</th>
                    <th>Follow-up Leads</th>
                    <th>Follow-up Deals</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report as $row)
                    <tr>
                        <td data-label="Employee">{{ $row->creator->name ?? 'Unknown' }}</td>
                        <td data-label="Total Calls">{{ $row->total_calls }}</td>
                        <td data-label="Call Logs">{{ $row->call_logs }}</td>
                        <td data-label="Contacts Followup">{{ $row->contacts }}</td>
                        <td data-label="Follow-up Leads">{{ $row->followup_leads }}</td>
                        <td data-label="Follow-up Deals">{{ $row->followup_deals }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="report-empty">No call data found for the selected period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-report-shell>
@endsection
