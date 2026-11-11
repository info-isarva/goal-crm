@extends('layouts.app')

@section('content')
@php
    $exportUrl = $leads->isEmpty()
        ? null
        : route('reports.leads_by_status_excel', request()->only('status'));
@endphp

<x-report-shell
    title="Leads by Status Report"
    :show-date-range="false"
    :export-url="$exportUrl"
    :show-run-button="true"
>
    <x-slot name="toolbar">
        <div class="toolbar-field">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-select">
                <option value="">-- All Statuses --</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @if(request('status') == $status) selected @endif>{{ $status }}</option>
                @endforeach
            </select>
        </div>
    </x-slot>

    @if($leads->isEmpty())
        <div class="alert alert-info">No leads found for the selected status.</div>
    @else
        <div class="report-table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Company</th>
                        <th>Contact Person</th>
                        <th>Lead Owner</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($leads as $lead)
                    <tr>
                        <td data-label="#">{{ $loop->iteration }}</td>
                        <td data-label="Title"><a class="text-decoration-none text-primary" href="{{ route('leads.show', $lead->id) }}">{{ $lead->title }}</a></td>
                        <td data-label="Company">{{ $lead->organization->name ?? '-' }}</td>
                        <td data-label="Contact Person">{{ $lead->person ? ($lead->person->first_name . ' ' . $lead->person->last_name) : '-' }}</td>
                        <td data-label="Lead Owner">{{ $lead->owner->name ?? '-' }}</td>
                        <td data-label="Category">
                            @if(!empty($lead->category_names))
                                @foreach($lead->category_names as $categoryName)
                                    <div>{{ $categoryName }}</div>
                                @endforeach
                            @else
                                N/A
                            @endif
                        </td>
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
                        <td data-label="Created At">{{ $lead->created_at->format('d-m-Y H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @include('reports.partials.pagination', ['paginator' => $leads ?? null, 'noun' => 'leads'])
</x-report-shell>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var statusDropdown = document.getElementById('status');
        if (statusDropdown) {
            statusDropdown.addEventListener('change', function() {
                document.getElementById('reportFilterForm').submit();
            });
        }
    });
</script>
@endpush
@endsection
