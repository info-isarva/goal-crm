@extends('layouts.app')

@section('content')
@php
    $period = $period ?? 'this_month';
    $exportUrl = $leads->isEmpty()
        ? null
        : route('reports.converted_leads_excel', request()->only('period', 'start_date', 'end_date', 'user_id'));
@endphp

<x-report-shell
    title="Converted Leads Report"
    :show-date-range="true"
    :period="$period"
    :start="$start ?? null"
    :end="$end ?? null"
    :export-url="$exportUrl"
    :show-run-button="true"
    :form-action="route('reports.converted_leads')"
>
    <x-slot name="toolbar">
        <div class="toolbar-field">
            <label for="user_id">User</label>
            <select name="user_id" id="user_id" class="form-select">
                <option value="">All Users</option>
                @foreach($usersList as $owner)
                    <option value="{{ $owner->id }}" {{ (string) ($filterUserId ?? '') === (string) $owner->id ? 'selected' : '' }}>
                        {{ $owner->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </x-slot>

    @if($leads->isEmpty())
        <div class="report-content-pad">
            <div class="alert alert-info mb-0">No converted leads found for the selected filters.</div>
        </div>
    @else
        <div class="report-table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Company</th>
                        <th>Contact Person</th>
                        <th>Lead Owner</th>
                        <th>Status</th>
                        <th>Converted At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($leads as $lead)
                    <tr>
                        <td data-label="#">{{ $loop->iteration }}</td>
                        <td data-label="Title"><a class="text-decoration-none" href="{{ route('leads.show', $lead->id) }}">{{ $lead->title }}</a></td>
                        <td data-label="Category">
                            @if(!empty($lead->category_names))
                                @foreach($lead->category_names as $categoryName)
                                    <div>{{ $categoryName }}</div>
                                @endforeach
                            @else
                                N/A
                            @endif
                        </td>
                        <td data-label="Company">{{ $lead->organization->name ?? '-' }}</td>
                        <td data-label="Contact Person">{{ $lead->person ? ($lead->person->first_name . ' ' . $lead->person->last_name) : '-' }}</td>
                        <td data-label="Lead Owner">{{ $lead->owner->name ?? '-' }}</td>
                        <td data-label="Status">
                            @php
                                $status = $lead->status ?? '-';
                                $statusColors = [
                                    'New' => 'bg-primary',
                                    'Contacted' => 'bg-info text-dark',
                                    'Qualified' => 'bg-success-subtle text-success',
                                    'Lost' => 'bg-danger-subtle text-danger',
                                    'Converted' => 'bg-warning text-dark',
                                ];
                                $color = $statusColors[$status] ?? 'bg-secondary';
                            @endphp
                            <span class="badge {{ $color }}">{{ $status }}</span>
                        </td>
                        <td data-label="Converted At">{{ $lead->converted_at ? $lead->converted_at->format('d-m-Y H:i') : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @include('reports.partials.pagination', ['paginator' => $leads ?? null, 'noun' => 'leads'])
</x-report-shell>
@endsection
