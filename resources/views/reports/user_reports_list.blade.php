@extends('layouts.app')

@section('content')
@php
    $period = $period ?? 'today';
    $exportUrl = route('reports.user_reports_pdf', request()->only('period', 'start_date', 'end_date', 'user_id'));
@endphp

<x-report-shell
    title="User Reports"
    :show-date-range="true"
    :period="$period"
    :start="$start ?? ($fromDate ?? null)"
    :end="$end ?? ($toDate ?? null)"
    :export-url="$exportUrl"
    :form-action="route('reports.user_reports')"
>
    <x-slot name="toolbar">
        <div class="toolbar-field">
            <label for="user_id">Users</label>
            <select id="user_id" name="user_id" class="form-select">
                <option value="">None</option>
                @foreach(\App\Models\User::where('crm_role_type', '!=', '0')->get() as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </x-slot>

    @if(isset($results))
        @if(count($results) > 0 && collect($results)->sum(fn($row) => count($row['leads']) + count($row['deals'])) > 0)
            @foreach($results as $row)
                <div class="card mb-4">
                    <div class="card-header fw-bold">{{ $row['user']->name }}</div>
                    <div class="card-body">
                        <h5>Leads <span class="badge bg-info">{{ count($row['leads']) }}</span></h5>
                        <div class="report-table-wrap mb-3">
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Title</th>
                                        <th>Company Name</th>
                                        <th>Converted to Deal</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($row['leads'] as $lead)
                                        <tr>
                                            <td data-label="Date">{{ $lead->created_at ? \Carbon\Carbon::parse($lead->created_at)->format('d-m-Y') : '-' }}</td>
                                            <td data-label="Title"><a href="{{ url('/leads/' . $lead->id) }}" target="_blank">{{ $lead->title }}</a></td>
                                            <td data-label="Company Name">{{ $lead->organization ? $lead->organization->name : '-' }}</td>
                                            <td data-label="Converted to Deal">{{ $lead->converted_at ? 'Yes' : 'No' }}</td>
                                            <td data-label="Status">{{ $lead->status }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="report-empty">No leads found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <h5>Deals <span class="badge bg-info">{{ count($row['deals']) }}</span></h5>
                        <div class="report-table-wrap">
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Title</th>
                                        <th>Company Name</th>
                                        <th>Expected Close Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($row['deals'] as $deal)
                                        <tr>
                                            <td data-label="Date">{{ $deal->created_at ? \Carbon\Carbon::parse($deal->created_at)->format('d-m-Y') : '-' }}</td>
                                            <td data-label="Title"><a href="{{ url('/deals/' . $deal->id) }}" target="_blank">{{ $deal->title }}</a></td>
                                            <td data-label="Company Name">{{ $deal->organization ? $deal->organization->name : '-' }}</td>
                                            <td data-label="Expected Close Date">{{ $deal->close_date ? \Carbon\Carbon::parse($deal->close_date)->format('d-m-Y') : '-' }}</td>
                                            <td data-label="Status">{{ $deal->stage_name }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="report-empty">No deals found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <p class="report-empty px-3">No data found for the selected filters.</p>
        @endif
    @endif
</x-report-shell>
@endsection
