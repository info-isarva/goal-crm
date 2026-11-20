@php
    $srTypes = \App\Models\GoalScheduledReport::TYPES;
    $srTimes = [];
    for ($h = 0; $h < 24; $h++) {
        foreach ([0, 30] as $m) {
            $label = \Carbon\Carbon::createFromTime($h, $m)->format('h:i A');
            $srTimes[sprintf('%02d:%02d', $h, $m)] = $label;
        }
    }
    $defaultType = $f['section'] ?? 'overview';
    if (! isset($srTypes[$defaultType])) {
        $defaultType = 'overview';
    }
    $freqOpts = [
        'daily' => ['Daily', 'bi-sun'],
        'weekly' => ['Weekly', 'bi-calendar-week'],
        'monthly' => ['Monthly', 'bi-calendar-month'],
        'custom' => ['Custom', 'bi-sliders'],
    ];
@endphp

<div class="modal fade" id="gaScheduleModal" tabindex="-1" aria-labelledby="gaScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <form method="POST" action="{{ route('goal.scheduled-reports.store') }}" class="modal-content ga-sched-modal" id="gaScheduleForm">
            @csrf
            <input type="hidden" name="fy" value="{{ $f['fy'] ?? '' }}">
            <input type="hidden" name="date_from" value="{{ $f['date_from'] ?? '' }}">
            <input type="hidden" name="date_to" value="{{ $f['date_to'] ?? '' }}">
            <input type="hidden" name="region" value="{{ $f['region'] ?? '' }}">
            <input type="hidden" name="cluster" value="{{ $f['cluster'] ?? '' }}">
            <input type="hidden" name="branch" value="{{ $f['branch'] ?? '' }}">
            <input type="hidden" name="product" value="{{ $f['product'] ?? '' }}">
            <input type="hidden" name="owner" value="{{ $f['owner'] ?? '' }}">
            <input type="hidden" name="start_date" id="srStartDate" value="{{ now()->format('Y-m-d') }}">
            <input type="hidden" name="end_date" id="srEndDate" value="{{ now()->addYear()->format('Y-m-d') }}">

            <div class="modal-header ga-sched-head">
                <div class="ga-sched-head-left">
                    <span class="ga-sched-ico" aria-hidden="true"><i class="bi bi-calendar2-check"></i></span>
                    <div>
                        <h5 class="modal-title" id="gaScheduleModalLabel">Schedule Report</h5>
                        <p class="ga-sched-sub">Automate and receive this report at a scheduled time.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body ga-sched-body">
                <div class="ga-sched-grid">
                    <div class="ga-sched-main">
                        <div class="ga-sched-field">
                            <label>Report Type <span class="req">*</span></label>
                            <select name="report_type" id="srReportType" class="form-select" required>
                                @foreach($srTypes as $key => $label)
                                    <option value="{{ $key }}" @selected($key === $defaultType)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="ga-sched-field">
                            <label>Report Name <span class="req">*</span></label>
                            <input type="text" name="name" id="srReportName" class="form-control" required
                                   maxlength="160" placeholder="Monthly Analytics Summary"
                                   value="Monthly Analytics Summary">
                        </div>

                        <div class="ga-sched-field">
                            <label>Schedule Frequency <span class="req">*</span></label>
                            <div class="ga-freq-grid" role="radiogroup" aria-label="Schedule Frequency">
                                @foreach($freqOpts as $key => [$label, $icon])
                                    <label class="ga-freq-card">
                                        <input type="radio" name="frequency" value="{{ $key }}" @checked($key === 'daily')>
                                        <span class="ga-freq-face">
                                            <i class="bi bi-check-circle-fill ga-freq-check"></i>
                                            <i class="bi {{ $icon }} ga-freq-ico"></i>
                                            <span class="ga-freq-label">{{ $label }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="ga-sched-field ga-freq-extra" data-freq="weekly" hidden>
                            <label>Weekdays <span class="req">*</span></label>
                            <div class="ga-weekday-row">
                                @foreach([1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'] as $d => $lbl)
                                    <label class="ga-chip">
                                        <input type="checkbox" name="weekdays[]" value="{{ $d }}" @checked(in_array($d, [1,5], true))>
                                        <span>{{ $lbl }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="ga-sched-field ga-freq-extra" data-freq="monthly" hidden>
                            <label>Day of Month <span class="req">*</span></label>
                            <select name="day_of_month" class="form-select">
                                @for($d = 1; $d <= 28; $d++)
                                    <option value="{{ $d }}" @selected($d === 1)>{{ $d }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="ga-sched-field ga-freq-extra" data-freq="custom" hidden>
                            <label>Repeat every (days) <span class="req">*</span></label>
                            <input type="number" name="interval_days" class="form-control" min="1" max="365" value="7">
                        </div>

                        <div class="ga-sched-field">
                            <label>Time <span class="req">*</span></label>
                            <div class="ga-time-wrap">
                                <i class="bi bi-clock"></i>
                                <select name="run_time" id="srRunTime" class="form-select" required>
                                    @foreach($srTimes as $val => $label)
                                        <option value="{{ $val }}" @selected($val === '09:00')>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="ga-sched-field">
                            <div class="ga-date-row">
                                <div class="ga-date-col">
                                    <label>Start Date <span class="req">*</span></label>
                                    <div class="ga-dp">
                                        <input type="text" id="srStartDateUi" class="form-control" placeholder="dd-mm-yyyy" readonly required>
                                        <i class="bi bi-calendar3"></i>
                                    </div>
                                </div>
                                <div class="ga-date-col">
                                    <label>End Date <span class="req">*</span></label>
                                    <div class="ga-dp">
                                        <input type="text" id="srEndDateUi" class="form-control" placeholder="dd-mm-yyyy" readonly>
                                        <i class="bi bi-calendar3"></i>
                                    </div>
                                </div>
                                <div class="ga-date-check">
                                    <label class="ga-check">
                                        <input type="checkbox" name="no_end_date" id="srNoEnd" value="1">
                                        <span>No End Date</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="ga-sched-field">
                            <label>Report Format <span class="req">*</span></label>
                            <div class="ga-format-row" role="radiogroup">
                                @foreach(['pdf' => ['PDF', 'bi-file-earmark-pdf-fill'], 'xlsx' => ['Excel (XLSX)', 'bi-file-earmark-excel-fill'], 'csv' => ['CSV', 'bi-filetype-csv']] as $key => [$label, $icon])
                                    <label class="ga-format-opt">
                                        <input type="radio" name="format" value="{{ $key }}" @checked($key === 'pdf')>
                                        <span class="ga-format-dot"></span>
                                        <i class="bi {{ $icon }}"></i>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="ga-sched-field ga-sched-field-last">
                            <label>Recipients <span class="req">*</span></label>
                            <div class="ga-recipient-box" id="srRecipientBox">
                                <div class="ga-recipient-tags" id="srRecipientTags"></div>
                                <input type="text" id="srRecipientInput" class="ga-recipient-input"
                                       placeholder="Add more email addresses…" autocomplete="off">
                            </div>
                            <input type="hidden" name="recipients" id="srRecipients" value="">
                            <div class="form-text">Add one or more email addresses. Reports use your current dashboard filters and role permissions.</div>
                        </div>
                    </div>

                    <aside class="ga-sched-side">
                        <div class="ga-sched-summary">
                            <h6>Schedule Summary</h6>
                            <dl class="ga-sum-list">
                                <div><dt>Report</dt><dd id="srSumType">—</dd></div>
                                <div><dt>Name</dt><dd id="srSumName">—</dd></div>
                                <div><dt>Frequency</dt><dd id="srSumFreq">Daily</dd></div>
                                <div><dt>Time</dt><dd id="srSumTime">09:00 AM</dd></div>
                                <div><dt>Start Date</dt><dd id="srSumStart">—</dd></div>
                                <div><dt>End Date</dt><dd id="srSumEnd">—</dd></div>
                                <div><dt>Format</dt><dd id="srSumFormat">PDF</dd></div>
                                <div><dt>Recipients</dt><dd id="srSumRecipients">0 Email(s)</dd></div>
                            </dl>
                        </div>
                        <div class="ga-sched-info">
                            <i class="bi bi-info-circle-fill"></i>
                            <p>The selected report will be generated automatically and sent to the recipients based on the schedule.</p>
                        </div>
                    </aside>
                </div>
            </div>

            <div class="modal-footer ga-sched-foot">
                <a href="{{ route('goal.scheduled-reports.index') }}" class="ga-sched-manage-link me-auto">
                    <i class="bi bi-list-check"></i> Manage Schedules
                </a>
                <button type="button" class="btn btn-light border ga-sched-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn ga-sched-submit" id="srSubmitBtn">
                    <i class="bi bi-calendar-plus"></i> Create Schedule
                </button>
            </div>
        </form>
    </div>
</div>
