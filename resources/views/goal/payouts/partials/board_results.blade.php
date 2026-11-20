@php
    $tab = $filters['tab'] ?? 'all';
    $fmtPill = function (?string $status) {
        $s = (string) $status;
        return match (true) {
            in_array($s, ['Paid', 'Matched', 'Approved', 'Verified'], true) => 'ok',
            $s === 'Eligible' => 'eligible',
            $s === 'Processing' => 'processing',
            in_array($s, ['On Hold', 'Pending', 'Awaiting Report', 'Report Entered', 'Partial'], true) => 'warn',
            in_array($s, ['Exception', 'Rejected'], true) => 'bad',
            default => 'info',
        };
    };
    $inr = fn ($n) => '₹ '.number_format((float) $n, 0);
    $qs = fn (array $extra = []) => array_filter(array_merge(request()->except(['page', 'partial']), $extra), fn ($v) => $v !== null && $v !== '');
    $dateFromLabel = !empty($filters['date_from']) ? \Carbon\Carbon::parse($filters['date_from'])->format('d M Y') : '';
    $dateToLabel = !empty($filters['date_to']) ? \Carbon\Carbon::parse($filters['date_to'])->format('d M Y') : '';
    $dateRangeLabel = ($dateFromLabel && $dateToLabel) ? ($dateFromLabel.' – '.$dateToLabel) : ($dateFromLabel ?: $dateToLabel);
    $showingLabel = $payouts->total()
        ? ('Showing '.$payouts->firstItem().' to '.$payouts->lastItem().' of '.number_format($payouts->total()).' records')
        : 'Showing 0 records';
    $selId = (int) ($filters['selected'] ?? ($selected?->id ?? 0));
    $kpiMeta = [
        'on_hold' => ['On Hold', 'On Hold', 'bi-lock', 'tone-red'],
        'eligible' => ['Eligible', 'Eligible', 'bi-shield-check', 'tone-green'],
        'processing' => ['Processing', 'Process', 'bi-arrow-repeat', 'tone-blue'],
        'paid' => ['Paid', 'Paid', 'bi-currency-rupee', 'tone-emerald'],
        'exception' => ['Exceptions', 'Except.', 'bi-exclamation-triangle', 'tone-rose'],
    ];
@endphp
<div class="pay-layout" id="payLayout" data-tab="{{ $tab }}">
    <div class="pay-main">
        <div class="pay-flow" aria-label="Payout workflow">
            <div class="pay-step tone-red">
                <span class="pay-step-ico"><i class="bi bi-lock"></i></span>
                <div>
                    <p class="pay-step-label"><span class="full">On Hold</span><span class="short">Hold</span></p>
                    <p class="pay-step-sub"><span class="full">Awaiting confirmations</span><span class="short">Awaiting</span></p>
                </div>
            </div>
            <span class="pay-flow-arr"><i class="bi bi-arrow-right"></i></span>
            <div class="pay-step tone-green">
                <span class="pay-step-ico"><i class="bi bi-shield-check"></i></span>
                <div>
                    <p class="pay-step-label"><span class="full">Eligible</span><span class="short">Eligible</span></p>
                    <p class="pay-step-sub">Ready for receipt</p>
                </div>
            </div>
            <span class="pay-flow-arr"><i class="bi bi-arrow-right"></i></span>
            <div class="pay-step tone-blue">
                <span class="pay-step-ico"><i class="bi bi-arrow-repeat"></i></span>
                <div>
                    <p class="pay-step-label"><span class="full">Processing</span><span class="short">Process</span></p>
                    <p class="pay-step-sub">Partial / outstanding</p>
                </div>
            </div>
            <span class="pay-flow-arr"><i class="bi bi-arrow-right"></i></span>
            <div class="pay-step tone-emerald">
                <span class="pay-step-ico"><i class="bi bi-check-lg"></i></span>
                <div>
                    <p class="pay-step-label"><span class="full">Paid</span><span class="short">Paid</span></p>
                    <p class="pay-step-sub">Reconciled</p>
                </div>
            </div>
            <span class="pay-flow-arr"><i class="bi bi-arrow-right"></i></span>
            <div class="pay-step tone-rose">
                <span class="pay-step-ico"><i class="bi bi-exclamation-triangle"></i></span>
                <div>
                    <p class="pay-step-label"><span class="full">Exception</span><span class="short">Except.</span></p>
                    <p class="pay-step-sub">Needs resolution</p>
                </div>
            </div>
        </div>

        <div class="pay-kpi-row">
            @foreach($kpiMeta as $key => [$label, $short, $icon, $tone])
                <a class="pay-kpi {{ $tone }} {{ $tab === $key ? 'is-active' : '' }}"
                   href="{{ route('goal.payouts.index', $qs(['tab' => $key])) }}"
                   data-pay-nav data-tab="{{ $key }}">
                    <span class="pay-kpi-ico"><i class="bi {{ $icon }}"></i></span>
                    <div class="pay-kpi-body">
                        <p class="pay-kpi-label"><span class="full">{{ $label }}</span><span class="short">{{ $short }}</span></p>
                        <p class="pay-kpi-value">{{ number_format($kpis[$key] ?? 0) }}</p>
                        <p class="pay-kpi-amt">{{ $inr($kpis[$key.'_amount'] ?? 0) }}</p>
                        <p class="pay-kpi-pct">{{ number_format($kpis[$key.'_pct'] ?? 0, 1) }}% of total</p>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="pay-tabs" role="tablist">
            @foreach([
                'all' => ['All Payouts', 'All', 'bi-wallet2'],
                'on_hold' => ['On Hold', 'On Hold', 'bi-lock'],
                'eligible' => ['Eligible', 'Eligible', 'bi-shield-check'],
                'processing' => ['Processing', 'Process', 'bi-arrow-repeat'],
                'paid' => ['Paid', 'Paid', 'bi-currency-rupee'],
                'exception' => ['Exception', 'Exception', 'bi-exclamation-triangle'],
            ] as $key => [$label, $short, $icon])
                <a class="pay-tab {{ $tab === $key ? 'active' : '' }}"
                   href="{{ route('goal.payouts.index', $qs(['tab' => $key])) }}"
                   data-pay-nav data-tab="{{ $key }}">
                    <i class="bi {{ $icon }}"></i>
                    <span class="full">{{ $label }}</span><span class="short">{{ $short }}</span>
                    <span class="cnt">({{ $tabCounts[$key] ?? 0 }})</span>
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('goal.payouts.index') }}" class="pay-filters" id="payFilters">
            <input type="hidden" name="tab" id="payTabInput" value="{{ $tab }}">
            @if($selId > 0)
                <input type="hidden" name="selected" value="{{ $selId }}">
            @endif
            <div class="pay-filters-row top">
                <div>
                    <label for="pay-q">Case Reference / Search</label>
                    <div class="pay-search">
                        <i class="bi bi-search"></i>
                        <input id="pay-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Case ref, customer, report, payment ref">
                    </div>
                </div>
                <div>
                    <label for="pay-customer">Customer</label>
                    <input id="pay-customer" type="search" name="customer" value="{{ $filters['customer'] ?? '' }}" class="form-control" placeholder="Customer name">
                </div>
                <div>
                    <label for="pay-report">Report Number</label>
                    <input id="pay-report" type="search" name="report_number" value="{{ $filters['report_number'] ?? '' }}" class="form-control" placeholder="Lender report #">
                </div>
                <div>
                    <label for="pay-payref">Payment Reference</label>
                    <input id="pay-payref" type="search" name="payment_reference" value="{{ $filters['payment_reference'] ?? '' }}" class="form-control" placeholder="Receipt / UTR ref">
                </div>
            </div>
            <div class="pay-filters-row mid">
                <div>
                    <label for="pay-branch">Branch</label>
                    <select id="pay-branch" name="branch_id" class="form-select">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" @selected((string)($filters['branch_id'] ?? '') === (string)$b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="pay-lender">Lender / Bank</label>
                    <select id="pay-lender" name="lender" class="form-select">
                        <option value="">All Lenders</option>
                        @foreach($lenders as $lenderName)
                            <option value="{{ $lenderName }}" @selected(($filters['lender'] ?? '') === $lenderName)>{{ $lenderName }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="pay-pstatus">Payout Status</label>
                    <select id="pay-pstatus" name="payout_status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach(['On Hold','Eligible','Processing','Paid','Exception'] as $st)
                            <option value="{{ $st }}" @selected(($filters['payout_status'] ?? '') === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="pay-rstatus">Reconciliation</label>
                    <select id="pay-rstatus" name="reconciliation_status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach(['Pending','Partial','Matched','Exception','Approved'] as $st)
                            <option value="{{ $st }}" @selected(($filters['reconciliation_status'] ?? '') === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="pay-filters-row bottom">
                <div>
                    <label for="payDateRange">Disbursement Date Range</label>
                    <input type="hidden" name="date_from" id="payDateFrom" value="{{ $filters['date_from'] ?? '' }}">
                    <input type="hidden" name="date_to" id="payDateTo" value="{{ $filters['date_to'] ?? '' }}">
                    <div class="pay-date-wrap">
                        <i class="bi bi-calendar3"></i>
                        <input type="text" id="payDateRange" class="form-control" placeholder="Select date range" value="{{ $dateRangeLabel }}" readonly>
                    </div>
                </div>
                <div class="pay-filter-actions">
                    <button type="submit" class="pd-btn" id="payApplyBtn"><i class="bi bi-funnel"></i> Apply</button>
                    <a href="{{ route('goal.payouts.index', ['tab' => $tab]) }}" class="pd-btn-ghost" id="payResetBtn"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                </div>
            </div>
        </form>

        <div class="pd-card pay-qcard">
            <div class="pd-card-head">
                <div class="pay-qhead">
                    <span class="pay-qico"><i class="bi bi-table"></i></span>
                    <div>
                        <h5 class="pay-qtitle">
                            Payout Register
                            <span class="pay-qcount">({{ $showingLabel }})</span>
                        </h5>
                    </div>
                </div>
            </div>
            <div class="pd-table-wrap">
                <table class="pd-table pay-qtable align-middle" id="payRegisterTable">
                    <thead>
                        <tr>
                            <th class="pay-col-num">#</th>
                            <th>Case Reference</th>
                            <th>Customer</th>
                            <th>Branch</th>
                            <th>Lender</th>
                            <th class="text-end">Expected (₹)</th>
                            <th class="text-end">Received (₹)</th>
                            <th class="text-end">Outstanding (₹)</th>
                            <th>Reconciliation</th>
                            <th>Payout Status</th>
                            <th>Last Updated</th>
                            <th>Ageing</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($payouts as $payout)
                        @php
                            $case = $payout->loanCase;
                            $expected = (float) $payout->expected_amount;
                            $received = (float) $payout->received_amount;
                            $adj = (float) ($payout->adjustments?->sum('amount') ?? 0);
                            $outstanding = max(0, $expected - $received - $adj);
                            $rowRecon = $payout->reconciliation_status ?: 'Pending';
                            $isSel = $selected && (int)$selected->id === (int)$payout->id;
                            $rowNo = ($payouts->firstItem() ?? 1) + $loop->index;
                            $ageFrom = $payout->updated_at ?: $payout->created_at;
                            $age = (!in_array($payout->status, ['Paid'], true) && $ageFrom)
                                ? (int) $ageFrom->diffInDays(now())
                                : null;
                            $canRowReceipt = $canReceipt && in_array($payout->status, ['Eligible', 'Processing'], true);
                        @endphp
                        <tr class="{{ $isSel ? 'pay-row-active' : '' }}" data-pay-select-url="{{ route('goal.payouts.index', $qs(['selected' => $payout->id])) }}">
                            <td class="pay-col-num" data-label="#">{{ $rowNo }}</td>
                            <td data-label="Case Reference">
                                <a class="pd-case" href="{{ route('goal.payouts.index', $qs(['selected' => $payout->id])) }}"
                                   data-pay-nav title="Select in board">{{ $case?->case_no ?: '—' }}</a>
                            </td>
                            <td data-label="Customer">{{ $case?->customer_name ?: '—' }}</td>
                            <td data-label="Branch">{{ $case?->orgUnit?->name ?: '—' }}</td>
                            <td data-label="Lender">{{ $case?->lender_name ?: '—' }}</td>
                            <td data-label="Expected" class="text-end"><span class="pay-amt">{{ number_format($expected, 0) }}</span></td>
                            <td data-label="Received" class="text-end"><span class="pay-amt">{{ number_format($received, 0) }}</span></td>
                            <td data-label="Outstanding" class="text-end"><span class="pay-amt {{ $outstanding > 0 ? 'text-danger' : '' }}">{{ number_format($outstanding, 0) }}</span></td>
                            <td data-label="Reconciliation">
                                <span class="pay-status {{ $fmtPill($rowRecon) }}">{{ $rowRecon }}</span>
                            </td>
                            <td data-label="Payout Status">
                                <span class="pay-status {{ $fmtPill($payout->status) }}">{{ $payout->status ?: '—' }}</span>
                            </td>
                            <td data-label="Last Updated">{{ optional($payout->updated_at)->format('d M Y') ?: '—' }}</td>
                            <td data-label="Ageing">{{ $age !== null ? $age.'d' : '—' }}</td>
                            <td class="pd-col-actions" data-label="Actions">
                                <div class="pay-qacts">
                                    <a href="{{ route('goal.payouts.show', $payout) }}" class="pay-abtn view"><i class="bi bi-eye"></i> View</a>
                                    @if($canRowReceipt)
                                        <button type="button" class="pay-abtn process"
                                                data-bs-toggle="modal" data-bs-target="#payReceiptModal"
                                                data-pay-id="{{ $payout->id }}"
                                                data-case="{{ $case?->case_no }}"
                                                data-expected="{{ number_format((float) $payout->expected_amount, 0, '.', '') }}"
                                                data-received="{{ number_format((float) $payout->received_amount, 0, '.', '') }}">
                                            <i class="bi bi-receipt"></i> Process
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="pd-col-empty">
                                <div class="pd-empty"><i class="bi bi-inbox"></i> No payouts match. Verified disbursements create On Hold payouts via PD-01.</div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pay-qfoot">
                <div>{{ $showingLabel }}</div>
                <div class="pay-pager">{{ $payouts->links() }}</div>
            </div>
        </div>
    </div>

    <aside class="pay-side">
        <div class="pay-panel">
            <div class="pay-panel-h">
                <h3 class="pay-panel-title"><span class="pay-hico"><i class="bi bi-wallet2"></i></span> Payout Details</h3>
            </div>
            <div class="pay-panel-b">
                @if($selected)
                    @php
                        $sc = $selected->loanCase;
                        $acc = $accountsItem;
                        $disb = $selected->disbursement;
                    @endphp
                    <div class="pay-snap-row"><span class="pay-snap-k">Case Reference</span><span class="pay-snap-v mono">{{ $sc?->case_no ?: '—' }}</span></div>
                    <div class="pay-snap-row"><span class="pay-snap-k">Customer</span><span class="pay-snap-v">{{ $sc?->customer_name ?: '—' }}</span></div>
                    <div class="pay-snap-row"><span class="pay-snap-k">Product</span><span class="pay-snap-v">{{ $sc?->product ?: '—' }}</span></div>
                    <div class="pay-snap-row"><span class="pay-snap-k">Branch</span><span class="pay-snap-v">{{ $sc?->orgUnit?->name ?: '—' }}</span></div>
                    <div class="pay-snap-row"><span class="pay-snap-k">Lender</span><span class="pay-snap-v">{{ $sc?->lender_name ?: '—' }}</span></div>
                    <div class="pay-snap-row"><span class="pay-snap-k">Disbursement</span><span class="pay-snap-v">{{ optional($disb?->disbursed_on)->format('d M Y') ?: '—' }}</span></div>
                    <div class="pay-snap-row"><span class="pay-snap-k">Report Number</span><span class="pay-snap-v">{{ $acc?->report_number ?: '—' }}</span></div>
                    <div class="pay-snap-row">
                        <span class="pay-snap-k">Payout Status</span>
                        <span class="pay-snap-v"><span class="pay-status {{ $fmtPill($selected->status) }}">{{ $selected->status }}</span></span>
                    </div>
                    <div class="pay-snap-row">
                        <span class="pay-snap-k">Reconciliation</span>
                        <span class="pay-snap-v"><span class="pay-status {{ $fmtPill($selected->reconciliation_status) }}">{{ $selected->reconciliation_status ?: 'Pending' }}</span></span>
                    </div>
                @else
                    <div class="text-muted small">Select a payout from the register.</div>
                @endif
            </div>
        </div>

        <div class="pay-panel">
            <div class="pay-panel-h">
                <h3 class="pay-panel-title"><span class="pay-hico"><i class="bi bi-cash-stack"></i></span> Payout Summary</h3>
            </div>
            <div class="pay-panel-b">
                <div class="pay-snap-row"><span class="pay-snap-k">Expected Payout</span><span class="pay-snap-v">{{ $inr($reconciliation['expected'] ?? 0) }}</span></div>
                <div class="pay-snap-row"><span class="pay-snap-k">Total Receipts</span><span class="pay-snap-v">{{ $inr($reconciliation['received'] ?? 0) }}</span></div>
                <div class="pay-snap-row">
                    <span class="pay-snap-k">Outstanding Balance</span>
                    <span class="pay-snap-v {{ ($reconciliation['outstanding'] ?? 0) > 0 ? 'danger' : '' }}">{{ $inr($reconciliation['outstanding'] ?? 0) }}</span>
                </div>
                @if($ageingDays !== null)
                    <div class="pay-snap-row"><span class="pay-snap-k">Ageing</span><span class="pay-snap-v">{{ $ageingDays }} days</span></div>
                @endif
            </div>
        </div>

        <div class="pay-panel">
            <div class="pay-panel-h">
                <h3 class="pay-panel-title"><span class="pay-hico"><i class="bi bi-list-check"></i></span> Required Confirmations</h3>
            </div>
            <div class="pay-panel-b">
                @forelse($confirmations as $c)
                    <div class="pay-conf-row">
                        <div class="pay-conf-left">
                            <span class="dot {{ !empty($c['done']) ? 'ok' : '' }}">
                                @if(!empty($c['done']))
                                    <i class="bi bi-check-lg"></i>
                                @else
                                    <i class="bi bi-circle"></i>
                                @endif
                            </span>
                            <span class="lbl">{{ $c['label'] }}</span>
                        </div>
                        <span class="pay-badge {{ !empty($c['done']) ? 'ok' : 'warn' }}">{{ !empty($c['done']) ? 'Completed' : 'Pending' }}</span>
                    </div>
                @empty
                    <div class="text-muted small">Select a payout to view confirmations.</div>
                @endforelse
            </div>
        </div>

        <div class="pay-panel">
            <div class="pay-panel-h">
                <h3 class="pay-panel-title"><span class="pay-hico"><i class="bi bi-receipt"></i></span> Receipts ({{ $selected ? $selected->receipts->count() : 0 }})</h3>
                @if($selected && $canReceipt && in_array($selected->status, ['Eligible', 'Processing'], true))
                    <button type="button" class="pay-panel-link btn btn-link p-0 border-0"
                            data-bs-toggle="modal" data-bs-target="#payReceiptModal"
                            data-pay-id="{{ $selected->id }}"
                            data-case="{{ $selected->loanCase?->case_no }}"
                            data-expected="{{ number_format((float) $selected->expected_amount, 0, '.', '') }}"
                            data-received="{{ number_format((float) $selected->received_amount, 0, '.', '') }}">
                        + Add Receipt
                    </button>
                @endif
            </div>
            <div class="pay-panel-b">
                @if($selected && $selected->receipts->count())
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 pay-rcpt-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Ref</th>
                                    <th class="text-end">Amt</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($selected->receipts->sortByDesc('received_on')->take(5) as $i => $r)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ optional($r->received_on)->format('d M Y') ?: '—' }}</td>
                                    <td>{{ $r->reference ?: '—' }}</td>
                                    <td class="text-end">{{ number_format((float) $r->amount, 0) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif($selected)
                    <div class="text-muted small">No receipts recorded yet.</div>
                @else
                    <div class="text-muted small">—</div>
                @endif
            </div>
        </div>

        <div class="pay-panel">
            <div class="pay-panel-h">
                <h3 class="pay-panel-title"><span class="pay-hico"><i class="bi bi-clock-history"></i></span> Activity</h3>
                @if($selected)
                    <a href="{{ route('goal.payouts.show', $selected) }}" class="pay-panel-link">View All</a>
                @endif
            </div>
            <div class="pay-panel-b">
                <ul class="pay-act">
                    @forelse(collect($activity)->take(4) as $row)
                        <li>
                            <div class="t">{{ $row['title'] }}</div>
                            <div class="m">{{ $row['meta'] }}</div>
                        </li>
                    @empty
                        <li><div class="m" style="padding-left:0;border:0">No activity yet.</div></li>
                    @endforelse
                </ul>
            </div>
        </div>

        @if($selected)
            <div class="pay-side-actions">
                @if($selected->loanCase)
                    <a href="{{ route('goal.cases.show', $selected->loanCase) }}" class="pay-view-case"><i class="bi bi-box-arrow-up-right"></i> View Case</a>
                @endif
                @if($canReceipt && in_array($selected->status, ['Eligible', 'Processing'], true))
                    <button type="button" class="pay-view-case"
                            data-bs-toggle="modal" data-bs-target="#payReceiptModal"
                            data-pay-id="{{ $selected->id }}"
                            data-case="{{ $selected->loanCase?->case_no }}"
                            data-expected="{{ number_format((float) $selected->expected_amount, 0, '.', '') }}"
                            data-received="{{ number_format((float) $selected->received_amount, 0, '.', '') }}">
                        <i class="bi bi-receipt"></i> Process Receipt
                    </button>
                @endif
                @if($canApprove && in_array($selected->status, ['On Hold', 'Eligible'], true))
                    @php
                        $gatesReady = collect($confirmations)->every(fn ($c) => !empty($c['done']));
                    @endphp
                    @if($gatesReady)
                        <form method="POST" action="{{ route('goal.payouts.release-hold', $selected) }}" class="pay-release-form">
                            @csrf
                            <label class="pay-release-check">
                                <input type="checkbox" name="confirm" value="1" required>
                                <span>I confirm all prerequisites and approve release</span>
                            </label>
                            <button type="submit" class="pd-btn w-100"><i class="bi bi-unlock"></i> Approve / Release Hold</button>
                        </form>
                    @else
                        <div class="alert alert-light border py-2 mb-0 small">
                            Complete PD-01, report number, PMS and Accounts approval before releasing hold.
                            @if($accountsItem)
                                <a href="{{ route('goal.accounts.show', $accountsItem) }}" class="d-block mt-1 fw-bold" style="color:#009B80">Open Accounts Process →</a>
                            @endif
                        </div>
                    @endif
                @endif
            </div>
        @endif
    </aside>
</div>