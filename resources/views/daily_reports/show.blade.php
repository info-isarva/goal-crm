@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/leads.css') }}">
<div class="container-fluid p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-0 fw-bold">Daily Report</h4>
            <small class="text-muted">{{ optional($report->user)->name }} — {{ optional($report->report_date)->format('d M Y') }}</small>
            @if($report->status === 'draft')
                <span class="badge bg-warning text-dark ms-2">Draft</span>
            @else
                <span class="badge bg-success ms-2">Submitted</span>
            @endif
        </div>
        <div class="d-flex gap-2">
            @if(!empty($canEdit))
                <a href="{{ route('daily_reports.edit', $report->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>
            @endif
            <a href="{{ route('daily_reports.index') }}" class="btn btn-outline-secondary btn-sm">&laquo; Back</a>
        </div>
    </div>

    @include('daily_reports.partials.form', [
        'formAction' => '#',
        'method' => 'POST',
        'isEdit' => true,
        'readOnly' => true,
        'report' => $report,
    ])
</div>
@endsection
