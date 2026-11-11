@extends('layouts.app')

@section('content')
@php
    $period = $period ?? 'this_month';
    $exportUrl = route('reports.leads_by_source_excel', request()->only('period', 'start_date', 'end_date'));
@endphp

<x-report-shell
    title="Leads by Source"
    :show-date-range="true"
    :period="$period"
    :start="$start ?? null"
    :end="$end ?? null"
    :export-url="$exportUrl"
>
    <div class="report-table-wrap">
        <table class="report-table">
            <colgroup>
                <col class="col-source">
                <col class="col-category">
                <col class="col-name">
                <col class="col-mobile">
                <col class="col-company">
                <col class="col-title">
                <col class="col-created">
                <col class="col-owner">
            </colgroup>
            <thead>
                <tr>
                    <th>Lead Source</th>
                    <th>Category</th>
                    <th>Lead Name</th>
                    <th>Mobile</th>
                    <th>Company</th>
                    <th>Title</th>
                    <th>Created Time</th>
                    <th>Lead Owner</th>
                </tr>
            </thead>
            <tbody>
                @forelse($grouped as $source => $leads)
                    @php $count = $leads->count(); $i = 0; @endphp
                    @foreach($leads as $lead)
                        <tr>
                            @if($i == 0)
                                <td class="source-cell d-none d-md-table-cell" rowspan="{{ $count }}" data-label="Lead Source">
                                    {{ $source }} <span class="text-muted fw-normal">({{ $count }})</span>
                                </td>
                            @endif
                            <td data-label="Category">
                                @if(!empty($lead->category_names))
                                    @foreach($lead->category_names as $categoryName)
                                        <div>{{ $categoryName }}</div>
                                    @endforeach
                                @else
                                    N/A
                                @endif
                            </td>
                            @php $fullName = $lead->person ? trim(($lead->person->first_name ?? '') . ' ' . ($lead->person->last_name ?? '')) : ''; @endphp
                            <td data-label="Lead Name">
                                <a href="{{ url('/leads/'.$lead->id) }}" target="_blank">{{ $fullName !== '' ? $fullName : '-' }}</a>
                            </td>
                            <td data-label="Mobile">{{ $lead->person && ($lead->person->mobile ?? '') ? $lead->person->mobile : ($lead->mobile ?? '-') }}</td>
                            <td data-label="Company">{{ $lead->organization ? $lead->organization->name : '-' }}</td>
                            <td data-label="Title">{{ $lead->title }}</td>
                            <td data-label="Created Time">{{ $lead->created_at ? $lead->created_at->format('d-m-Y h:i A') : '-' }}</td>
                            <td data-label="Lead Owner">{{ $lead->owner ? $lead->owner->name : '-' }}</td>
                        </tr>
                        @php $i++; @endphp
                    @endforeach
                @empty
                    <tr>
                        <td colspan="8" class="report-empty">No leads found for selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('reports.partials.pagination', ['paginator' => $leadsPaginator ?? null, 'noun' => 'leads'])
</x-report-shell>
@endsection
