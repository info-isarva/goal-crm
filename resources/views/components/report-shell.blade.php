@props([
    'title' => null,
    'showDateRange' => false,
    'period' => 'this_month',
    'start' => null,
    'end' => null,
    'exportUrl' => null,
    'formAction' => null,
    'formMethod' => 'GET',
    'periodText' => '',
    'showRunButton' => true,
])

@php
    $reportTitle = $title ?? (\App\Http\Controllers\ReportController::resolvePageTitle() ?? 'Report');
    if ($periodText === '' && (!empty($start) || !empty($end) || !empty($period))) {
        $periodText = \App\Http\Controllers\ReportController::periodText($period ?? 'custom', $start, $end);
    }
    $formAction = $formAction ?? request()->url();
    $hasExtraToolbar = isset($toolbar) && trim(strip_tags((string) $toolbar)) !== '';
    $hasActions = isset($actions) && trim(strip_tags((string) $actions)) !== '';
    $showToolbar = $showDateRange || $hasExtraToolbar || $showRunButton || $exportUrl || $hasActions;
@endphp

@include('reports.partials.styles')

<div class="report-shell">
    <div class="report-sticky-top">
        @include('reports.partials.header', ['title' => $reportTitle])

        @if($showToolbar)
            <form method="{{ $formMethod }}" id="reportFilterForm" action="{{ $formAction }}">
                <div class="report-toolbar">
                    @if($showDateRange || $hasExtraToolbar)
                        <div class="filters-label">
                            <i class="bi bi-funnel"></i> Filters:
                        </div>
                    @endif

                    @if($showDateRange)
                        @include('reports.partials.date_range', [
                            'period' => $period,
                            'start' => $start,
                            'end' => $end,
                        ])
                    @endif

                    @if($hasExtraToolbar)
                        {{ $toolbar }}
                    @endif

                    @if($showRunButton)
                        <button type="submit" class="btn-run-report">Run Report</button>
                    @endif

                    @if($exportUrl)
                        <div class="report-actions-top">
                            <a href="{{ $exportUrl }}" class="btn-export-report btn-sm">
                                <i class="bi bi-download"></i> Export
                            </a>
                        </div>
                    @elseif($hasActions)
                        <div class="report-actions-top">
                            {{ $actions }}
                        </div>
                    @endif
                </div>
            </form>
        @endif
    </div>

    <div class="report-body">
        <div class="report-paper">
            @include('reports.partials.meta', [
                'title' => $reportTitle,
                'periodText' => $periodText,
                'start' => $start,
                'end' => $end,
                'period' => $period,
                'metaAsideLeft' => isset($metaAsideLeft) ? (string) $metaAsideLeft : null,
                'metaAsideRight' => isset($metaAsideRight) ? (string) $metaAsideRight : null,
            ])

            {{ $slot }}
        </div>
    </div>
</div>

@if($showDateRange)
    @push('scripts')
        @include('reports.partials.date_range_js')
    @endpush
@endif
