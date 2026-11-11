@php
    $companyName = $companyName ?? (optional(\App\Models\Company::first())->name ?? config('app.name', 'Isarva CRM'));
    $metaTitle = $title ?? '';
    $periodText = $periodText ?? '';
    if ($periodText === '' && !empty($start) && !empty($end)) {
        $periodText = \App\Http\Controllers\ReportController::periodText($period ?? 'custom', $start, $end);
    }
    $hasMetaAside = !empty($metaAsideLeft) || !empty($metaAsideRight);
@endphp
<div class="report-meta {{ $hasMetaAside ? 'report-meta-with-asides' : '' }}">
    @if($hasMetaAside)
        <div class="report-meta-aside report-meta-aside-left">
            {!! $metaAsideLeft ?? '' !!}
        </div>
    @endif
    <div class="report-meta-center">
        <div class="company">{{ $companyName }}</div>
        <div class="title">{{ $metaTitle }}</div>
        @if($periodText !== '')
            <div class="period">{{ $periodText }}</div>
        @endif
    </div>
    @if($hasMetaAside)
        <div class="report-meta-aside report-meta-aside-right">
            {!! $metaAsideRight ?? '' !!}
        </div>
    @endif
</div>
