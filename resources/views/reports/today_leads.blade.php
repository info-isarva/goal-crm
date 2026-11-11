@extends('layouts.app')

@section('content')
@php
    $exportUrl = (!empty($historicalSelected) && $historicalSelected) || $leads->isEmpty()
        ? null
        : route('reports.today_leads_excel', request()->except('page'));
@endphp

<x-report-shell
    title="Today's Leads Report"
    :show-date-range="false"
    :export-url="$exportUrl"
    :show-run-button="false"
>
    @if(!empty($historicalSelected) && $historicalSelected)
        <div class="alert alert-warning">Today's leads are not available for a historical financial year selection.</div>
    @elseif($leads->isEmpty())
        <div class="alert alert-info">No leads created today.</div>
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
@endsection
