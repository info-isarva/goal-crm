@php
    $reportTitle = $title ?? (\App\Http\Controllers\ReportController::resolvePageTitle() ?? 'Report');
    $periodText = $periodText ?? '';
    if ($periodText === '' && !empty($start) && !empty($end)) {
        $periodText = \App\Http\Controllers\ReportController::periodText($period ?? 'custom', $start, $end);
    }
    $showDateRange = $showDateRange ?? false;
    $exportUrl = $exportUrl ?? null;
    $formAction = $formAction ?? request()->url();
    $formMethod = $formMethod ?? 'GET';
@endphp
@include('reports.partials.styles')

<div class="report-shell">
    @include('reports.partials.header', ['title' => $reportTitle])

    <form method="{{ $formMethod }}" id="reportFilterForm" action="{{ $formAction }}">
        <div class="report-toolbar">
            <div class="filters-label">
                <i class="bi bi-funnel"></i> Filters:
            </div>

            @if($showDateRange)
                @include('reports.partials.date_range', compact('period', 'start', 'end'))
            @endif

            @isset($toolbarFilters)
                {!! $toolbarFilters !!}
            @endisset

            <button type="submit" class="btn-run-report">Run Report</button>

            @if($exportUrl)
                <div class="report-actions-top">
                    <a href="{{ $exportUrl }}" class="btn-export-report btn-sm">
                        <i class="bi bi-download"></i> Export
                    </a>
                </div>
            @endif
        </div>
    </form>

    <div class="report-body">
        <div class="report-paper">
            @include('reports.partials.meta', [
                'title' => $reportTitle,
                'periodText' => $periodText,
            ])

            {!! $slot ?? '' !!}
        </div>
    </div>
</div>

@if($showDateRange)
    @push('scripts')
        @include('reports.partials.date_range_js')
    @endpush
@endif
