@extends('layouts.app')

@section('content')
    @php
        $reportDate = $reportDate ?? now();
        $dateLabel = $reportDate->format('d-m-y');
        $formatInr = function ($amount) {
            $amount = (float) $amount;
            $negative = $amount < 0;
            $amount = abs($amount);
            $int = (string) (int) round($amount);
            $last3 = substr($int, -3);
            $rest = substr($int, 0, -3);
            if ($rest !== '' && $rest !== false) {
                $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
                $formatted = $rest . ',' . $last3;
            } else {
                $formatted = $last3;
            }
            return ($negative ? '-' : '') . $formatted;
        };

        $coldColspan = 2;
        $productColspans = [];
        foreach ($productGroups as $group) {
            $productColspans[$group['key']] = count($group['columns']);
        }
        $marketingColspan = max(1, $marketingTypes->count()) + 1; // + Data Pre Analysis
        $leadStatusColspan = 4; // Leads Gen + Leads Converted + Quote Sent + Invoice Sent
        $matrixColCount = 1 + $coldColspan + array_sum($productColspans) + $marketingColspan + $leadStatusColspan;
    @endphp

    <x-report-shell title="Daily Sales & Marketing Report" :show-date-range="false" :show-run-button="true"
        :period-text="$reportDate->format('d F Y')"
        :form-action="route('reports.sales_marketing_daily')"
        :export-url="route('reports.sales_marketing_daily_excel', request()->only('report_date', 'user_id'))">
        <x-slot name="toolbar">
            <div class="toolbar-field">
                <label for="report_date">Report Date</label>
                <input type="date" id="report_date" name="report_date" class="form-control"
                    value="{{ $reportDate->format('Y-m-d') }}" data-sm-export-filter>
            </div>
            @if(in_array((int) auth()->user()->crm_role_type, [0, 1, 2], true))
                <div class="toolbar-field">
                    <label for="user_id">Team Member</label>
                    <select id="user_id" name="user_id" class="form-select" data-sm-export-filter>
                        <option value="">All</option>
                        @foreach(\App\Models\User::where('status', 1)->whereNotIn('crm_role_type', [0, 1])->orderBy('name')->get() as $u)
                            <option value="{{ $u->id }}" {{ (string) request('user_id') === (string) $u->id ? 'selected' : '' }}>
                                {{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </x-slot>

        <div class="sm-daily-report">
            {{-- Top activity matrix --}}
            <div class="sm-scroll">
                <table class="sm-matrix">
                    <thead>
                        <tr class="sm-cat-row">
                            <th class="sm-date-cell" rowspan="2">
                                <div class="sm-date-badge">Report<br>{{ $dateLabel }}</div>
                            </th>
                            <th class="sm-cat sm-cold" colspan="{{ $coldColspan }}">Cold Calling</th>
                            @foreach($productGroups as $group)
                                <th class="sm-cat" colspan="{{ count($group['columns']) }}"
                                    style="background: {{ $group['header_bg'] }};">{{ $group['label'] }}</th>
                            @endforeach
                            <th class="sm-cat sm-mkt" colspan="{{ $marketingColspan }}">Marketing Activities</th>
                            <th class="sm-cat sm-lead" colspan="{{ $leadStatusColspan }}">Lead Status</th>
                        </tr>
                        <tr class="sm-sub-row">
                            <th class="sm-cold-sub">Follow ups</th>
                            <th class="sm-cold-sub">New Calls</th>
                            @foreach($productGroups as $group)
                                @foreach($group['columns'] as $col)
                                    <th class="sm-prod-sub" style="background: {{ $group['cell_bg'] }};">{{ $col['label'] }}</th>
                                @endforeach
                            @endforeach
                            @forelse($marketingTypes as $type)
                                <th class="sm-mkt-sub">{{ $type->name }}</th>
                            @empty
                                <th class="sm-mkt-sub">—</th>
                            @endforelse
                            <th class="sm-mkt-sub">Data Pre Analysis</th>
                            <th class="sm-lead-sub">Leads Gen</th>
                            <th class="sm-lead-sub">Leads Converted</th>
                            <th class="sm-lead-sub">Quote Sent</th>
                            <th class="sm-lead-sub">Invoice Sent</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td class="sm-name">{{ $row['user']->name }}</td>
                                <td class="sm-num sm-cold-cell">{{ $row['follow_ups'] }}</td>
                                <td class="sm-num sm-cold-cell">{{ $row['new_calls'] }}</td>
                                @foreach($productGroups as $group)
                                    @foreach($group['columns'] as $colKey => $col)
                                        <td class="sm-num" style="background: {{ $group['cell_bg'] }};">
                                            {{ $row['products'][$group['key']][$colKey] ?? 0 }}</td>
                                    @endforeach
                                @endforeach
                                @forelse($marketingTypes as $type)
                                    <td class="sm-num sm-mkt-cell">{{ $row['marketing'][$type->id] ?? 0 }}</td>
                                @empty
                                    <td class="sm-num sm-mkt-cell">0</td>
                                @endforelse
                                <td class="sm-num sm-mkt-cell">{{ $row['data_pre_analysis'] }}</td>
                                <td class="sm-num sm-lead-cell">{{ $row['leads_gen'] }}</td>
                                <td class="sm-num sm-lead-cell">{{ $row['leads_converted'] }}</td>
                                <td class="sm-num sm-lead-cell">{{ $row['quote_sent'] }}</td>
                                <td class="sm-num sm-lead-cell">{{ $row['invoice_sent'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $matrixColCount }}" class="text-center py-3">No daily reports submitted for this date.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($rows)
                        <tfoot>
                            <tr class="sm-total-row">
                                <td>Total</td>
                                <td class="sm-num">{{ $totals['follow_ups'] }}</td>
                                <td class="sm-num">{{ $totals['new_calls'] }}</td>
                                @foreach($productGroups as $group)
                                    @foreach($group['columns'] as $colKey => $col)
                                        <td class="sm-num">{{ $totals['products'][$group['key']][$colKey] ?? 0 }}</td>
                                    @endforeach
                                @endforeach
                                @forelse($marketingTypes as $type)
                                    <td class="sm-num">{{ $totals['marketing'][$type->id] ?? 0 }}</td>
                                @empty
                                    <td class="sm-num">0</td>
                                @endforelse
                                <td class="sm-num">{{ $totals['data_pre_analysis'] }}</td>
                                <td class="sm-num">{{ $totals['leads_gen'] }}</td>
                                <td class="sm-num">{{ $totals['leads_converted'] }}</td>
                                <td class="sm-num">{{ $totals['quote_sent'] }}</td>
                                <td class="sm-num">{{ $totals['invoice_sent'] }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            {{-- Bottom grid --}}
            {{-- Bottom grid --}}
            <div class="sm-bottom">
                <div class="sm-bottom-left">
                    <div class="sm-side-table-wrap">
                        <table class="sm-side-table">
                            <thead>
                                <tr>
                                    <th colspan="6" class="sm-side-head">Sales Target &amp; Revenue Status -
                                        {{ $monthLabel }}</th>
                                </tr>
                                <tr class="sm-side-cols">
                                    <th>Team Members</th>
                                    <th>Month Target</th>
                                    <th>Month Achieve</th>
                                    <th>Month Balance</th>
                                    <th>{{ $weekLabel }} Target Amt</th>
                                    <th>{{ $weekLabel }} Achieved</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $row)
                                    <tr>
                                        <td>{{ $row['user']->name }}</td>
                                        <td class="sm-num">{{ $formatInr($row['month_target']) }}</td>
                                        <td class="sm-num">{{ $formatInr($row['month_achieve']) }}</td>
                                        <td class="sm-num">{{ $formatInr($row['month_balance']) }}</td>
                                        <td class="sm-num">{{ $formatInr($row['week_target']) }}</td>
                                        <td class="sm-num">{{ $formatInr($row['week_achieve']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No data</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if($rows)
                                <tfoot>
                                    <tr class="sm-total-row">
                                        <td>Total</td>
                                        <td class="sm-num">{{ $formatInr($totals['month_target']) }}</td>
                                        <td class="sm-num">{{ $formatInr($totals['month_achieve']) }}</td>
                                        <td class="sm-num">{{ $formatInr($totals['month_balance']) }}</td>
                                        <td class="sm-num">{{ $formatInr($totals['week_target']) }}</td>
                                        <td class="sm-num">{{ $formatInr($totals['week_achieve']) }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>

                    <div class="sm-side-table-wrap">
                        <table class="sm-side-table sm-leads-table">
                            <thead>
                                <tr>
                                    <th colspan="6" class="sm-leads-head">Lead Details</th>
                                </tr>
                                <tr class="sm-side-cols sm-leads-cols">
                                    <th>Team Members</th>
                                    <th>Lead Details</th>
                                    <th>Product/Services</th>
                                    <th>Industries</th>
                                    <th>Source</th>
                                    <th>Estimated Amt</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $hasLeadRows = false; @endphp
                                @foreach($rows as $row)
                                    @php
                                        $userLeads = $leadsByUser->get($row['user']->id) ?? collect();
                                    @endphp
                                    @if($userLeads->isEmpty())
                                        <tr>
                                            <td>{{ $row['user']->name }}</td>
                                            <td>—</td>
                                            <td>—</td>
                                            <td>—</td>
                                            <td>—</td>
                                            <td>—</td>
                                        </tr>
                                        @php $hasLeadRows = true; @endphp
                                    @else
                                        @foreach($userLeads as $lead)
                                            @php
                                                $hasLeadRows = true;
                                                $personName = trim(implode(' ', array_filter([
                                                    optional($lead->person)->first_name,
                                                    optional($lead->person)->last_name,
                                                ])));
                                                $detail = $lead->title
                                                    ?: optional($lead->organization)->name
                                                    ?: ($personName !== '' ? $personName : null)
                                                    ?: 'Lead #' . $lead->id;
                                                $product = $lead->product_services_label ?? '—';
                                                $category = $lead->industry_label ?? '—';
                                                $estimatedAmt = ($lead->amount !== null && $lead->amount !== '' && (float) $lead->amount > 0) ? $formatInr($lead->amount) : '—';
                                                $source = optional($lead->leadSource)->name ?? '—';
                                            @endphp
                                            <tr>
                                                <td>{{ $row['user']->name }}</td>
                                                <td>{{ $detail }}</td>
                                                <td>{{ $product }}</td>
                                                <td>{{ $category }}</td>
                                                <td>{{ $source }}</td>
                                                <td>{{ $estimatedAmt }}</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @endforeach
                                @if(!$hasLeadRows)
                                    <tr>
                                        <td colspan="6" class="text-center">No leads created on this date.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    @if($todayDirectDeals->isNotEmpty())
                        <div class="sm-side-table-wrap">
                            <table class="sm-side-table sm-deals-table">
                                <thead>
                                    <tr>
                                        <th colspan="6" class="sm-deals-head">Deal Details</th>
                                    </tr>
                                    <tr class="sm-side-cols sm-deals-cols">
                                        <th>Team Members</th>
                                        <th>Deal Details</th>
                                        <th>Product/Services</th>
                                        <th>Industries</th>
                                        <th>Source</th>
                                        <th>Estimated Amt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rows as $row)
                                        @php
                                            $userDeals = $dealsByUser->get($row['user']->id) ?? collect();
                                        @endphp
                                        @foreach($userDeals as $deal)
                                            @php
                                                $personName = trim(implode(' ', array_filter([
                                                    optional($deal->person)->first_name,
                                                    optional($deal->person)->last_name,
                                                ])));
                                                $detail = $deal->title
                                                    ?: optional($deal->organization)->name
                                                    ?: ($personName !== '' ? $personName : null)
                                                    ?: 'Deal #' . $deal->id;
                                                $product = $deal->product_services_label ?? '—';
                                                $category = $deal->industry_label ?? '—';
                                                $estimatedAmt = ($deal->amount !== null && $deal->amount !== '' && (float) $deal->amount > 0) ? $formatInr($deal->amount) : '—';
                                                $source = optional($deal->dealSource)->name ?? '—';
                                            @endphp
                                            <tr>
                                                <td>{{ $row['user']->name }}</td>
                                                <td>{{ $detail }}</td>
                                                <td>{{ $product }}</td>
                                                <td>{{ $category }}</td>
                                                <td>{{ $source }}</td>
                                                <td>{{ $estimatedAmt }}</td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="sm-bottom-right">
                    <div class="sm-activity-wrap">
                        <div class="sm-activity-title">Sales and Marketing Activities Details</div>
                        <div class="sm-activity-cols">
                            @forelse($rows as $row)
                                <div class="sm-activity-col">
                                    <div class="sm-activity-user">{{ $row['user']->name }}</div>
                                    <div class="sm-activity-body">
                                        @php
                                            $todayActs = $row['today_activities'] ?? [];
                                            $hasOther = !empty($row['other_activity']) && trim(strip_tags($row['other_activity'])) !== '';
                                        @endphp

                                        @if(!empty($todayActs))
                                            <div class="sm-today-act-section mb-3">
                                                <div class="fw-bold small text-primary mb-1">Today's Activities:</div>
                                                <ol class="ps-3 mb-2 small">
                                                    @foreach($todayActs as $act)
                                                        <li class="mb-1">
                                                            <span class="badge bg-secondary me-1"
                                                                style="font-size:10px;">{{ $act['type'] }}</span>
                                                            <strong>{{ $act['title'] }}</strong>
                                                            @if(!empty($act['related']))
                                                                <span class="text-muted">({{ $act['related'] }})</span>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ol>
                                            </div>
                                        @endif

                                        @if($hasOther)
                                            @if(!empty($todayActs))
                                                <div class="fw-bold small text-secondary mb-1">Other Related Activity:</div>
                                            @endif
                                            {!! $row['other_activity'] !!}
                                        @elseif(empty($todayActs))
                                            <p class="sm-empty-note">
                                                {{ $row['has_report'] ? 'No activity details entered.' : 'Daily report not submitted.' }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="sm-activity-col">
                                    <div class="sm-activity-body">
                                        <p class="sm-empty-note">No daily reports submitted for this date.</p>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-report-shell>

    <style>
        .sm-daily-report {
            font-size: 12px;
            color: #1a1a1a;
            width: 100%;
            overflow-x: hidden;
        }

        .sm-scroll {
            overflow-x: auto;
            margin-bottom: 16px;
            border: 1px solid #9aa5b1;
            width: 100%;
            max-width: 100%;
        }

        .sm-matrix {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
            table-layout: fixed;
        }

        .sm-matrix th,
        .sm-matrix td {
            border: 1px solid #7f8c9b;
            padding: 4px 6px;
            text-align: center;
            vertical-align: middle;
            overflow: hidden;
        }

        .sm-cat-row th {
            color: #fff;
            font-weight: 700;
            font-size: 11px;
            line-height: 1.2;
        }

        .sm-date-cell {
            background: #1e4d8c;
            width: 160px;
            min-width: 160px;
            max-width: 160px;
            padding: 4px !important;
        }

        .sm-date-badge {
            background: #f4d03f;
            color: #1a1a1a;
            font-weight: 800;
            border-radius: 2px;
            padding: 8px 4px;
            line-height: 1.25;
            font-size: 12px;
        }

        .sm-cold {
            background: #c0392b !important;
        }

        .sm-mkt {
            background: #196f3d !important;
        }

        .sm-lead {
            background: #148f77 !important;
        }

        .sm-sub-row th {
            font-size: 10px;
            font-weight: 700;
            color: #1a1a1a;
        }

        .sm-cold-sub {
            background: #f5b7b1;
        }

        .sm-mkt-sub {
            background: #a9dfbf;
        }

        .sm-lead-sub {
            background: #a3e4d7;
        }

        .sm-prod-sub {
            color: #1a1a1a;
        }

        .sm-team-label {
            background: #d5d8dc;
            font-weight: 700;
            text-align: left !important;
        }

        .sm-name {
            text-align: left !important;
            font-weight: 600;
            background: #f8f9f9;
            width: 160px;
            min-width: 160px;
            max-width: 160px;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
            line-height: 1.25;
        }

        .sm-total-row td:first-child {
            text-align: left !important;
            width: 160px;
            min-width: 160px;
            max-width: 160px;
        }

        .sm-num {
            font-variant-numeric: tabular-nums;
        }

        .sm-cold-cell {
            background: #fadbd8;
        }

        .sm-mkt-cell {
            background: #d5f5e3;
        }

        .sm-lead-cell {
            background: #d1f2eb;
        }

        .sm-total-row td {
            background: #1e4d8c !important;
            color: #fff !important;
            font-weight: 700;
        }

        .sm-bottom {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 16px;
            align-items: start;
            width: 100%;
        }

        .sm-bottom-left,
        .sm-bottom-right {
            min-width: 0;
            width: 100%;
        }

        .sm-side-table-wrap {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 14px;
        }

        .sm-side-table {
            width: 100%;
            min-width: 520px;
            border-collapse: collapse;
            border: 1px solid #7f8c9b;
        }

        .sm-side-table th,
        .sm-side-table td {
            border: 1px solid #7f8c9b;
            padding: 5px 7px;
            font-size: 12px;
        }

        .sm-side-head {
            background: #1e4d8c;
            color: #fff;
            text-align: center;
            font-weight: 700;
        }

        .sm-side-cols th {
            background: #d6eaf8;
            font-weight: 700;
            text-align: center;
            font-size: 11px;
        }

        .sm-leads-head {
            background: #922b21 !important;
            color: #fff !important;
            text-align: center;
            font-weight: 700;
        }

        .sm-leads-cols th {
            background: #f5b7b1;
        }

        .sm-deals-head {
            background: #1a5276 !important;
            color: #fff;
            text-align: center;
            font-weight: 700;
        }

        .sm-deals-cols th {
            background: #a9cce3;
            font-weight: 700;
            text-align: center;
            font-size: 11px;
        }

        .sm-activity-wrap {
            border: 1px solid #7f8c9b;
            min-height: 100%;
            width: 100%;
            background: #fff;
            overflow-x: auto;
        }

        .sm-activity-title {
            background: #1e4d8c;
            color: #fff;
            text-align: center;
            font-weight: 700;
            padding: 7px 10px;
        }

        .sm-activity-cols {
            display: flex;
            flex-wrap: wrap;
            width: 100%;
        }

        .sm-activity-col {
            flex: 1 1 220px;
            min-width: 200px;
            border-right: 1px solid #7f8c9b;
            border-bottom: 1px solid #7f8c9b;
            box-sizing: border-box;
        }

        .sm-activity-col:last-child {
            border-right: 0;
        }

        .sm-activity-user {
            background: #922b21;
            color: #fff;
            text-align: center;
            font-weight: 700;
            padding: 6px 8px;
        }

        .sm-activity-body {
            padding: 10px 12px;
            font-size: 12px;
            line-height: 1.45;
            word-break: break-word;
        }

        .sm-activity-body ol,
        .sm-activity-body ul {
            padding-left: 18px;
            margin: 0 0 8px;
        }

        .sm-activity-body p {
            margin: 0 0 8px;
        }

        .sm-empty-note {
            color: #7f8c8d;
            font-style: italic;
            margin: 0;
        }

        @media (max-width: 1400px) {
            .sm-bottom {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <script>
        (function () {
            var baseUrl = @json(route('reports.sales_marketing_daily_excel'));
            function syncExportUrl() {
                var link = document.querySelector('.btn-export-report');
                if (!link) return;
                var params = new URLSearchParams();
                var dateEl = document.getElementById('report_date');
                var userEl = document.getElementById('user_id');
                if (dateEl && dateEl.value) params.set('report_date', dateEl.value);
                if (userEl && userEl.value) params.set('user_id', userEl.value);
                var qs = params.toString();
                link.href = qs ? (baseUrl + '?' + qs) : baseUrl;
            }
            document.addEventListener('change', function (e) {
                if (e.target && e.target.matches('[data-sm-export-filter]')) syncExportUrl();
            });
            syncExportUrl();
        })();
    </script>
@endsection