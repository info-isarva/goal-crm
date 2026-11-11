@php
    $reportTitle = $title ?? (\App\Http\Controllers\ReportController::resolvePageTitle() ?? 'Report');
@endphp
<div class="report-page-header">
    <a href="{{ route('reports.index') }}" class="report-back-btn" title="Back to Reports" aria-label="Back to Reports">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="report-page-title">{{ $reportTitle }}</h4>
</div>
