@extends('layouts.app')

@section('content')
<x-report-shell
    title="User Activity Report"
    :show-date-range="false"
    :export-url="null"
    :show-run-button="false"
>
    @if($activities->isEmpty())
        <div class="alert alert-info">No activities found for this project.</div>
    @else
        <div class="report-table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activities as $activity)
                        <tr>
                            <td data-label="Type">{{ $activity['type'] }}</td>
                            <td data-label="Title">{{ $activity['title'] }}</td>
                            <td data-label="Created At">{{ $activity['created_at']->format('d-m-Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @include('reports.partials.pagination', ['paginator' => null, 'noun' => 'results'])
</x-report-shell>
@endsection
