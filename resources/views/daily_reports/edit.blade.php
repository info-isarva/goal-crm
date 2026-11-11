@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/leads.css') }}">
<div class="container-fluid p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-0 fw-bold">Edit Daily Report</h4>
            <small class="text-muted">{{ optional($report->report_date)->format('d M Y') }}</small>
        </div>
        <a href="{{ route('daily_reports.index') }}" class="btn btn-outline-secondary btn-sm">&laquo; Back</a>
    </div>

    @include('daily_reports.partials.form', [
        'formAction' => route('daily_reports.update', $report->id),
        'method' => 'PUT',
        'isEdit' => true,
        'readOnly' => false,
        'report' => $report,
    ])
</div>
@endsection
