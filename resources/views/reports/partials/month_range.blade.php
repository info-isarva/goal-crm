@php
    $monthPeriod = is_string($monthPeriod ?? null) ? $monthPeriod : (is_scalar($monthPeriod ?? null) ? (string) $monthPeriod : 'this_month');
    $monthPeriod = $monthPeriod !== '' ? $monthPeriod : 'this_month';
    $monthPeriodLabels = [
        'this_month' => 'This Month',
        'last_month' => 'Last Month',
        'year' => 'Year',
        'custom' => 'Custom',
    ];
    if (!in_array($monthPeriod, array_keys($monthPeriodLabels), true)) {
        $monthPeriod = 'this_month';
    }

    $fromYear = (int) ($fromYear ?? date('Y'));
    $fromMonth = (int) ($fromMonth ?? date('n'));
    $toYear = (int) ($toYear ?? date('Y'));
    $toMonth = (int) ($toMonth ?? date('n'));
    if ($fromMonth < 1 || $fromMonth > 12) $fromMonth = (int) date('n');
    if ($toMonth < 1 || $toMonth > 12) $toMonth = (int) date('n');

    $monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    $fromLabel = ($monthNames[$fromMonth - 1] ?? '') . ' ' . $fromYear;
    $toLabel = ($monthNames[$toMonth - 1] ?? '') . ' ' . $toYear;
    $displayLabel = $monthPeriod === 'custom'
        ? ($fromLabel . ' - ' . $toLabel)
        : ($monthPeriodLabels[$monthPeriod] ?? 'This Month');

    $yearOptions = range((int) date('Y') + 1, (int) date('Y') - 10);
@endphp
<input type="hidden" name="month_period" id="monthPeriodInput" value="{{ $monthPeriod }}">
<input type="hidden" name="from_year" id="fromYearInput" value="{{ $fromYear }}">
<input type="hidden" name="from_month" id="fromMonthInput" value="{{ $fromMonth }}">
<input type="hidden" name="to_year" id="toYearInput" value="{{ $toYear }}">
<input type="hidden" name="to_month" id="toMonthInput" value="{{ $toMonth }}">

<div class="drp-wrap mrp-wrap" id="monthRangePicker">
    <span class="drp-label">Month Range</span>
    <button type="button" class="drp-trigger" id="mrpTrigger" aria-expanded="false">
        <span id="mrpTriggerLabel">{{ $displayLabel }}</span>
        <i class="bi bi-chevron-down"></i>
    </button>

    <div class="drp-panel mrp-panel" id="mrpPanel" role="dialog" aria-label="Month range">
        <div class="drp-presets" id="mrpPresets">
            @foreach($monthPeriodLabels as $key => $label)
                <button type="button"
                    class="drp-preset {{ $monthPeriod === $key ? 'active' : '' }}"
                    data-period="{{ $key }}">{{ $label }}</button>
            @endforeach
        </div>
        <div class="drp-calendar-pane mrp-month-pane {{ $monthPeriod === 'custom' ? 'show' : '' }}" id="mrpCustomPane">
            <div class="mrp-select-row">
                <div class="mrp-select-group">
                    <label class="mrp-select-label">From Month</label>
                    <div class="mrp-selects">
                        <select id="mrpFromMonth" aria-label="From month">
                            @foreach($monthNames as $idx => $name)
                                <option value="{{ $idx + 1 }}" {{ $fromMonth === ($idx + 1) ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                        <select id="mrpFromYear" aria-label="From year">
                            @foreach($yearOptions as $y)
                                <option value="{{ $y }}" {{ $fromYear === (int) $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mrp-select-group">
                    <label class="mrp-select-label">To Month</label>
                    <div class="mrp-selects">
                        <select id="mrpToMonth" aria-label="To month">
                            @foreach($monthNames as $idx => $name)
                                <option value="{{ $idx + 1 }}" {{ $toMonth === ($idx + 1) ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                        <select id="mrpToYear" aria-label="To year">
                            @foreach($yearOptions as $y)
                                <option value="{{ $y }}" {{ $toYear === (int) $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="drp-footer">
                <div class="drp-range-text" id="mrpRangeText">{{ $fromLabel }} - {{ $toLabel }}</div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn-cancel" id="mrpCancel">Cancel</button>
                    <button type="button" class="btn-apply" id="mrpApply">Apply</button>
                </div>
            </div>
        </div>
    </div>
</div>
