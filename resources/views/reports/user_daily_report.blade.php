@extends('layouts.app')

@section('content')
@php
    $period = $period ?? 'today';
    $exportUrl = route('reports.user_daily_report_excel', array_filter([
        'period' => $period,
        'start_date' => $start ?? ($startDate ?? null),
        'end_date' => $end ?? ($endDate ?? null),
        'user_id' => request('user_id'),
    ]));
@endphp

<x-report-shell
    title="User Daily Report"
    :show-date-range="true"
    :period="$period"
    :start="$start ?? ($startDate ?? null)"
    :end="$end ?? ($endDate ?? null)"
    :export-url="$exportUrl"
>
    <x-slot name="toolbar">
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
    </x-slot>

    @if(isset($results))
    <div class="report-table-wrap">
        <table class="report-table">
            <thead>
                <tr>
                    <th colspan="3"></th>
                    <th colspan="2" class="text-center">Lead Section</th>
                    <th colspan="6" class="text-center">Deals Section</th>
                </tr>
                <tr>
                    <th>User Name</th>
                    <th>Date</th>
                    <th>Call Count</th>
                    <th>Lead Name</th>
                    <th>Lead Source</th>
                    <th>Deal Title</th>
                    <th>Stage</th>
                    <th>Status</th>
                    <th>Closed Date</th>
                    <th>Deal Amount</th>
                    <th>Loss Reason</th>
                </tr>
            </thead>
            <tbody>
                @php
                $grouped = collect($results)->groupBy(function($row) {
                    return $row['user']->id . '|' . $row['date'];
                });
                @endphp
                @forelse($grouped as $key => $rows)
                    @php $first = $rows->first(); $rowCount = $rows->count(); $i = 0; @endphp
                    @foreach($rows as $row)
                        <tr>
                            @if($i == 0)
                                <td rowspan="{{ $rowCount }}" style="vertical-align: middle;" class="d-none d-md-table-cell" data-label="User Name">{{ $first['user']->name }}</td>
                                <td rowspan="{{ $rowCount }}" style="vertical-align: middle;" class="d-none d-md-table-cell" data-label="Date">{{ $first['date'] }}</td>
                                <td rowspan="{{ $rowCount }}" style="vertical-align: middle;" class="d-none d-md-table-cell" data-label="Call Count">{{ $row['call_count'] }}</td>
                            @endif
                            <td class="d-md-none" data-label="User Name">{{ $first['user']->name }}</td>
                            <td class="d-md-none" data-label="Date">{{ $first['date'] }}</td>
                            <td class="d-md-none" data-label="Call Count">{{ $first['call_count'] ?? $row['call_count'] }}</td>
                            <td data-label="Lead Name">{{ $row['lead_name'] !== '' ? $row['lead_name'] : '-' }}</td>
                            <td data-label="Lead Source">{{ $row['lead_source'] !== '' ? $row['lead_source'] : '-' }}</td>
                            <td data-label="Deal Title">{{ !empty($row['deal_title']) ? $row['deal_title'] : '-' }}</td>
                            <td data-label="Stage">{{ $row['stage'] !== '' ? $row['stage'] : '-' }}</td>
                            <td data-label="Status">{{ $row['status'] !== '' ? $row['status'] : '-' }}</td>
                            <td data-label="Closed Date">{{ $row['closed_date'] !== '' ? $row['closed_date'] : '-' }}</td>
                            <td data-label="Deal Amount">{{ $row['deal_amount'] !== '' ? $row['deal_amount'] : '-' }}</td>
                            <td data-label="Loss Reason">{{ $row['loss_reason'] !== '' ? $row['loss_reason'] : '-' }}</td>
                        </tr>
                        @php $i++; @endphp
                    @endforeach
                @empty
                <tr><td colspan="11" class="report-empty">No data found for selected filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif
</x-report-shell>
@endsection
