@php
    $period = is_string($period ?? null) ? $period : (is_scalar($period ?? null) ? (string) $period : 'this_month');
    $period = $period !== '' ? $period : 'this_month';
    $periodLabels = \App\Http\Controllers\ReportController::periodLabels();
    if (!empty($periodKeys ?? null)) {
        $periodLabels = array_intersect_key($periodLabels, array_flip($periodKeys));
    }
    $start = $start ?? '';
    $end = $end ?? '';
    if (!in_array($period, array_keys($periodLabels), true)) {
        $period = 'this_month';
    }
    $displayLabel = $period === 'custom' && $start && $end
        ? ($start . ' - ' . $end)
        : ($periodLabels[$period] ?? 'This Month');
@endphp
<input type="hidden" name="period" id="periodInput" value="{{ $period }}">
<input type="hidden" name="start_date" id="startDateInput" value="{{ $start }}">
<input type="hidden" name="end_date" id="endDateInput" value="{{ $end }}">

<div class="drp-wrap" id="dateRangePicker">
    <span class="drp-label">Date Range</span>
    <button type="button" class="drp-trigger" id="drpTrigger" aria-expanded="false">
        <span id="drpTriggerLabel">{{ $displayLabel }}</span>
        <i class="bi bi-chevron-down"></i>
    </button>

    <div class="drp-panel" id="drpPanel" role="dialog" aria-label="Date range">
        <div class="drp-presets" id="drpPresets">
            @foreach($periodLabels as $key => $label)
                <button type="button"
                    class="drp-preset {{ $period === $key ? 'active' : '' }}"
                    data-period="{{ $key }}">{{ $label }}</button>
            @endforeach
        </div>
        <div class="drp-calendar-pane {{ $period === 'custom' ? 'show' : '' }}" id="drpCalendarPane">
            <div class="drp-inputs">
                <div class="drp-input" id="drpStartWrap">
                    <i class="bi bi-calendar3"></i>
                    <input type="text" id="drpStartDisplay" value="{{ $start }}" readonly placeholder="YYYY-MM-DD">
                </div>
                <div class="drp-input" id="drpEndWrap">
                    <i class="bi bi-calendar3"></i>
                    <input type="text" id="drpEndDisplay" value="{{ $end }}" readonly placeholder="YYYY-MM-DD">
                </div>
            </div>
            <div class="drp-calendars">
                <div class="drp-cal" id="drpCalLeft"></div>
                <div class="drp-cal" id="drpCalRight"></div>
            </div>
            <div class="drp-footer">
                <div class="drp-range-text" id="drpRangeText">
                    {{ ($start && $end) ? ($start . ' - ' . $end) : '' }}
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn-cancel" id="drpCancel">Cancel</button>
                    <button type="button" class="btn-apply" id="drpApply">Apply</button>
                </div>
            </div>
        </div>
    </div>
</div>
