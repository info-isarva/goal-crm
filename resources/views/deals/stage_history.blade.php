@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <h2 class="mb-4">Deal Stage History</h2>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Date/Time</th>
                <th>Stage</th>
                <th>Amount</th>
                <th>Probability</th>
                <th>Close Date</th>
                <th>Modified By</th>
            </tr>
        </thead>
        <tbody>
            @foreach($history as $row)
                <tr>
                    <td>{{ $row->modified_time }}</td>
                    <td>{{ $row->stage_name }}</td>
                    <td>{{ $row->amount }}</td>
                    <td>{{ $row->probability }}%</td>
                    <td>{{ $row->close_date }}</td>
                    <td>{{ optional($row->user)->name ?? $row->modified_by }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <a href="{{ route('deals.index') }}" class="btn btn-secondary mt-3">Back to Deals</a>
</div>
@endsection
