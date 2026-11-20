@php
    $tab = $filters['tab'] ?? 'all';
    $fmtPill = function (?string $status) {
        $s = (string) $status;
        return match (true) {
            in_array($s, ['Paid', 'Matched', 'Approved', 'Verified'], true) => 'ok',
            $s === 'Eligible' => 'eligible',
            $s === 'Processing' => 'processing',
            $s === 'Report Pending' => 'report',
            in_array($s, ['On Hold', 'Pending', 'Awaiting Report', 'Report Entered', 'Partial'], true) => 'warn',
            in_array($s, ['Exception', 'Rejected'], true) => 'bad',
            default => 'info',
        };
    };
    $displayPayoutStatus = function ($item, $payout) {
        $st = (string) ($payout?->status ?: '');
        if (in_array($st, ['Paid', 'Processing', 'Eligible', 'Exception'], true)) {
            return $st;
        }
        if (! filled($item->report_number)) {
            return 'Report Pending';
        }

        return $st !== '' ? $st : 'On Hold';
    };
    $inr = fn ($n) => '₹ '.number_format((float) $n, 0);
    $qs = fn (array $extra = []) => array_filter(array_merge(request()->except(['page', 'partial']), $extra), fn ($v) => $v !== null && $v !== '');
    $total = max(1, (int) ($kpis['total'] ?? 0));
    $pct = fn ($n) => number_format(((int) $n / $total) * 100, 1).'%';
    $dateFromLabel = !empty($filters['date_from']) ? \Carbon\Carbon::parse($filters['date_from'])->format('d M Y') : '';
    $dateToLabel = !empty($filters['date_to']) ? \Carbon\Carbon::parse($filters['date_to'])->format('d M Y') : '';
    $dateRangeLabel = ($dateFromLabel && $dateToLabel) ? ($dateFromLabel.' – '.$dateToLabel) : ($dateFromLabel ?: $dateToLabel);
    $showingLabel = $items->total()
        ? ('Showing '.$items->firstItem().' to '.$items->lastItem().' of '.number_format($items->total()).' records')
        : 'Showing 0 records';
@endphp
<div class="acc-layout" id="accLayout" data-tab="{{ $tab }}">
    <div class="acc-main">
        <div class="acc-flow" aria-label="Accounts workflow">
            <div class="acc-step tone-green">
                <span class="acc-step-ico"><i class="bi bi-check-lg"></i></span>
                <div>
                    <p class="acc-step-label"><span class="full">Verified Disbursement</span><span class="short">Verified</span></p>
                    <p class="acc-step-sub">PD-01</p>
                </div>
            </div>
            <span class="acc-flow-arr"><i class="bi bi-arrow-right"></i></span>
            <div class="acc-step tone-blue">
                <span class="acc-step-ico"><i class="bi bi-layers"></i></span>
                <div>
                    <p class="acc-step-label"><span class="full">Accounts Queue Created</span><span class="short">Queue Created</span></p>
                    <p class="acc-step-sub"><span class="full">Parallel with PMS</span><span class="short">vs PMS</span></p>
                </div>
            </div>
            <span class="acc-flow-arr"><i class="bi bi-arrow-right"></i></span>
            <div class="acc-step tone-teal">
                <span class="acc-step-ico"><i class="bi bi-file-earmark-text"></i></span>
                <div>
                    <p class="acc-step-label"><span class="full">Report Number Captured</span><span class="short">Report Captured</span></p>
                </div>
            </div>
            <span class="acc-flow-arr"><i class="bi bi-arrow-right"></i></span>
            <div class="acc-step tone-emerald">
                <span class="acc-step-ico"><i class="bi bi-shield-check"></i></span>
                <div>
                    <p class="acc-step-label"><span class="full">Confirmations Complete</span><span class="short">Confirmed</span></p>
                </div>
            </div>
            <span class="acc-flow-arr"><i class="bi bi-arrow-right"></i></span>
            <div class="acc-step tone-mint">
                <span class="acc-step-ico"><i class="bi bi-currency-rupee"></i></span>
                <div>
                    <p class="acc-step-label"><span class="full">Payout Eligible</span><span class="short">Eligible</span></p>
                </div>
            </div>
        </div>

        <div class="acc-hold-banner" role="status">
            <i class="bi bi-exclamation-circle-fill"></i>
            <div>Payout remains On Hold until required confirmations and report processing are complete.</div>
        </div>

        <div class="acc-kpi-row">
            <a class="acc-kpi tone-red {{ $tab === 'on_hold' ? 'is-active' : '' }}" href="{{ route('goal.accounts.index', $qs(['tab' => 'on_hold'])) }}" data-acc-nav data-tab="on_hold">
                <span class="acc-kpi-ico"><i class="bi bi-lock"></i></span>
                <div class="acc-kpi-body">
                    <p class="acc-kpi-label"><span class="full">Payout On Hold</span><span class="short">On Hold</span></p>
                    <p class="acc-kpi-value">{{ number_format($kpis['on_hold'] ?? 0) }}</p>
                    <p class="acc-kpi-pct">Cases ({{ $pct($kpis['on_hold'] ?? 0) }})</p>
                </div>
            </a>
            <a class="acc-kpi tone-blue {{ $tab === 'report_pending' ? 'is-active' : '' }}" href="{{ route('goal.accounts.index', $qs(['tab' => 'report_pending'])) }}" data-acc-nav data-tab="report_pending">
                <span class="acc-kpi-ico"><i class="bi bi-file-earmark-text"></i></span>
                <div class="acc-kpi-body">
                    <p class="acc-kpi-label"><span class="full">Report Number Pending</span><span class="short">Report Pending</span></p>
                    <p class="acc-kpi-value">{{ number_format($kpis['report_pending'] ?? 0) }}</p>
                    <p class="acc-kpi-pct">Cases ({{ $pct($kpis['report_pending'] ?? 0) }})</p>
                </div>
            </a>
            <a class="acc-kpi tone-green {{ $tab === 'eligible' ? 'is-active' : '' }}" href="{{ route('goal.accounts.index', $qs(['tab' => 'eligible'])) }}" data-acc-nav data-tab="eligible">
                <span class="acc-kpi-ico"><i class="bi bi-shield-check"></i></span>
                <div class="acc-kpi-body">
                    <p class="acc-kpi-label"><span class="full">Payout Eligible</span><span class="short">Eligible</span></p>
                    <p class="acc-kpi-value">{{ number_format($kpis['eligible'] ?? 0) }}</p>
                    <p class="acc-kpi-pct">Cases ({{ $pct($kpis['eligible'] ?? 0) }})</p>
                </div>
            </a>
            <a class="acc-kpi tone-purple {{ $tab === 'processing' ? 'is-active' : '' }}" href="{{ route('goal.accounts.index', $qs(['tab' => 'processing'])) }}" data-acc-nav data-tab="processing">
                <span class="acc-kpi-ico"><i class="bi bi-layers"></i></span>
                <div class="acc-kpi-body">
                    <p class="acc-kpi-label"><span class="full">Processing / Outstanding</span><span class="short">Outstanding</span></p>
                    <p class="acc-kpi-value">{{ number_format($kpis['processing'] ?? 0) }}</p>
                    <p class="acc-kpi-pct">Cases ({{ $pct($kpis['processing'] ?? 0) }})</p>
                </div>
            </a>
        </div>

        <div class="acc-tabs" role="tablist">
            @foreach([
                'all' => ['All Accounts', 'All', 'bi-folder2-open'],
                'on_hold' => ['On Hold', 'On Hold', 'bi-lock'],
                'report_pending' => ['Report Pending', 'Report', 'bi-file-earmark'],
                'eligible' => ['Eligible', 'Eligible', 'bi-shield-check'],
                'processing' => ['Processing', 'Process', 'bi-layers'],
                'paid' => ['Paid', 'Paid', 'bi-currency-rupee'],
                'exception' => ['Exception', 'Exception', 'bi-exclamation-triangle'],
            ] as $key => [$label, $short, $icon])
                <a class="acc-tab {{ $tab === $key ? 'active' : '' }}" href="{{ route('goal.accounts.index', $qs(['tab' => $key])) }}" data-acc-nav data-tab="{{ $key }}">
                    <i class="bi {{ $icon }}"></i>
                    <span class="full">{{ $label }}</span><span class="short">{{ $short }}</span>
                    <span class="cnt">({{ $tabCounts[$key] ?? 0 }})</span>
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('goal.accounts.index') }}" class="acc-filters" id="accFilters">
            <input type="hidden" name="tab" id="accTabInput" value="{{ $tab }}">
            <div class="acc-filters-row top">
                <div>
                    <label for="acc-q">Search</label>
                    <div class="acc-search">
                        <i class="bi bi-search"></i>
                        <input id="acc-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Case / Customer / Report Number">
                    </div>
                </div>
                <div>
                    <label for="acc-branch">Branch</label>
                    <select id="acc-branch" name="branch_id" class="form-select">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" @selected((string)($filters['branch_id'] ?? '') === (string)$b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="acc-lender">Lender / Bank</label>
                    <select id="acc-lender" name="lender" class="form-select">
                        <option value="">All Lenders</option>
                        @foreach($lenders as $lender)
                            <option value="{{ $lender }}" @selected(($filters['lender'] ?? '') === $lender)>{{ $lender }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="accDateRange">Disbursement Date Range</label>
                    <input type="hidden" name="date_from" id="accDateFrom" value="{{ $filters['date_from'] ?? '' }}">
                    <input type="hidden" name="date_to" id="accDateTo" value="{{ $filters['date_to'] ?? '' }}">
                    <div class="acc-date-wrap">
                        <i class="bi bi-calendar3"></i>
                        <input type="text" id="accDateRange" class="form-control" placeholder="Select date range" value="{{ $dateRangeLabel }}" readonly>
                    </div>
                </div>
            </div>
            <div class="acc-filters-row bottom">
                <div>
                    <label for="acc-pstatus">Payout Status</label>
                    <select id="acc-pstatus" name="payout_status" class="form-select">
                        <option value="">All Status</option>
                        @foreach(['On Hold','Eligible','Processing','Paid','Exception'] as $st)
                            <option value="{{ $st }}" @selected(($filters['payout_status'] ?? '') === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="acc-rstatus">Reconciliation Status</label>
                    <select id="acc-rstatus" name="reconciliation_status" class="form-select">
                        <option value="">All Status</option>
                        @foreach(['Pending','Partial','Matched','Exception','Approved'] as $st)
                            <option value="{{ $st }}" @selected(($filters['reconciliation_status'] ?? '') === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="acc-filter-actions">
                    <button type="submit" class="pd-btn" id="accApplyBtn"><i class="bi bi-funnel"></i> Apply</button>
                    <a href="{{ route('goal.accounts.index', ['tab' => $tab]) }}" class="pd-btn-ghost" id="accResetBtn"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                </div>
            </div>
        </form>

        <div class="pd-card acc-qcard">
            <div class="pd-card-head">
                <div class="acc-qhead">
                    <span class="acc-qico"><i class="bi bi-table"></i></span>
                    <div>
                        <h5 class="acc-qtitle">
                            Accounts Queue – Case List
                            <span class="acc-qcount">({{ $showingLabel }})</span>
                        </h5>
                    </div>
                </div>
                <div class="dropdown acc-col-dd">
                    <button class="acc-col-btn dropdown-toggle" type="button" id="accColBtn"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside"
                            aria-expanded="false" aria-controls="accColMenu">
                        <i class="bi bi-layout-three-columns"></i> Columns
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end acc-col-menu" id="accColMenu">
                        @foreach([
                            'case' => 'Case Reference',
                            'customer' => 'Customer',
                            'branch' => 'Branch',
                            'lender' => 'Lender',
                            'disb_date' => 'Disbursement Date',
                            'report' => 'Report Number',
                            'expected' => 'Expected Payout',
                            'received' => 'Received',
                            'outstanding' => 'Outstanding',
                            'status' => 'Payout Status',
                        ] as $colKey => $colLabel)
                            <li>
                                <label class="acc-col-item" for="acc-col-{{ $colKey }}">
                                    <input type="checkbox" id="acc-col-{{ $colKey }}" class="form-check-input acc-col-toggle" data-col="{{ $colKey }}" checked>
                                    <span>{{ $colLabel }}</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="pd-table-wrap">
                <table class="pd-table acc-qtable align-middle" id="accQueueTable">
                    <thead>
                        <tr>
                            <th class="acc-col-num">#</th>
                            <th data-col="case">Case Reference</th>
                            <th data-col="customer">Customer</th>
                            <th data-col="branch">Branch</th>
                            <th data-col="lender">Lender</th>
                            <th data-col="disb_date">Disbursement Date</th>
                            <th data-col="report">Report Number</th>
                            <th data-col="expected" class="text-end">Expected Payout (₹)</th>
                            <th data-col="received" class="text-end">Received (₹)</th>
                            <th data-col="outstanding" class="text-end">Outstanding (₹)</th>
                            <th data-col="status">Payout Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($items as $item)
                        @php
                            $case = $item->loanCase;
                            $disb = $item->disbursement;
                            $payout = $item->payout;
                            $expected = (float) ($payout?->expected_amount ?? 0);
                            $received = (float) ($payout?->received_amount ?? 0);
                            $adj = (float) ($payout?->adjustments?->sum('amount') ?? 0);
                            $outstanding = max(0, $expected - $received - $adj);
                            $isSel = $selected && (int)$selected->id === (int)$item->id;
                            $rowStatus = $displayPayoutStatus($item, $payout);
                            $rowNo = ($items->firstItem() ?? 1) + $loop->index;
                        @endphp
                        <tr class="{{ $isSel ? 'acc-row-active' : '' }}" data-acc-select-url="{{ route('goal.accounts.index', $qs(['selected' => $item->id])) }}">
                            <td class="acc-col-num" data-label="#">{{ $rowNo }}</td>
                            <td data-col="case" data-label="Case Reference">
                                <a class="pd-case" href="{{ route('goal.accounts.show', $item) }}" title="Open case processing">{{ $case?->case_no ?: '—' }}</a>
                            </td>
                            <td data-col="customer" data-label="Customer">{{ $case?->customer_name ?: '—' }}</td>
                            <td data-col="branch" data-label="Branch">{{ $case?->orgUnit?->name ?: '—' }}</td>
                            <td data-col="lender" data-label="Lender">{{ $case?->lender_name ?: '—' }}</td>
                            <td data-col="disb_date" data-label="Disbursement Date">{{ optional($disb?->disbursed_on)->format('d M Y') ?: '—' }}</td>
                            <td data-col="report" data-label="Report Number">{{ $item->report_number ?: '—' }}</td>
                            <td data-col="expected" data-label="Expected Payout" class="text-end"><span class="acc-amt">{{ number_format($expected, 0) }}</span></td>
                            <td data-col="received" data-label="Received" class="text-end"><span class="acc-amt">{{ number_format($received, 0) }}</span></td>
                            <td data-col="outstanding" data-label="Outstanding" class="text-end"><span class="acc-amt">{{ number_format($outstanding, 0) }}</span></td>
                            <td data-col="status" data-label="Payout Status"><span class="acc-status {{ $fmtPill($rowStatus) }}">{{ $rowStatus }}</span></td>
                            <td class="pd-col-actions" data-label="Actions">
                                <div class="acc-qacts">
                                    <a href="{{ route('goal.accounts.show', $item) }}" class="acc-abtn view"><i class="bi bi-eye"></i> View</a>
                                    <a href="{{ route('goal.accounts.show', $item) }}" class="acc-abtn process"><i class="bi bi-box-arrow-in-right"></i> Process</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="pd-col-empty">
                                <div class="pd-empty"><i class="bi bi-inbox"></i> No accounts items match. Verified disbursements create queue entries via PD-01.</div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="acc-qfoot">
                <div>{{ $showingLabel }}</div>
                <div class="acc-pager">{{ $items->links() }}</div>
            </div>
        </div>
    </div>

    <aside class="acc-side">
        {{-- Card 1 --}}
        <div class="acc-panel">
            <div class="acc-panel-h">
                <h3 class="acc-panel-title"><span class="acc-hico"><i class="bi bi-file-earmark-text"></i></span> Selected Case Snapshot</h3>
            </div>
            <div class="acc-panel-b">
                @if($selected)
                    @php $sc = $selected->loanCase; $sd = $selected->disbursement; @endphp
                    <div class="acc-snap-row"><span class="acc-snap-k">Case Reference</span><span class="acc-snap-v mono">{{ $sc?->case_no ?: '—' }}</span></div>
                    <div class="acc-snap-row"><span class="acc-snap-k">Customer</span><span class="acc-snap-v">{{ $sc?->customer_name ?: '—' }}</span></div>
                    <div class="acc-snap-row"><span class="acc-snap-k">Product</span><span class="acc-snap-v">{{ $sc?->product ?: '—' }}</span></div>
                    <div class="acc-snap-row"><span class="acc-snap-k">Branch</span><span class="acc-snap-v">{{ $sc?->orgUnit?->name ?: '—' }}</span></div>
                    <div class="acc-snap-row"><span class="acc-snap-k">Lender</span><span class="acc-snap-v">{{ $sc?->lender_name ?: '—' }}</span></div>
                    <div class="acc-snap-row"><span class="acc-snap-k">Disbursement Date</span><span class="acc-snap-v">{{ optional($sd?->disbursed_on)->format('d M Y') ?: '—' }}</span></div>
                    <div class="acc-snap-row"><span class="acc-snap-k">Disbursed Amount</span><span class="acc-snap-v">{{ $sd ? $inr($sd->amount) : '—' }}</span></div>
                    @if($sc)
                        <div class="acc-snap-foot">
                            <p class="acc-snap-foot-label">Linked Loan Case</p>
                            <a href="{{ route('goal.cases.show', $sc) }}" class="acc-view-case"><i class="bi bi-box-arrow-up-right"></i> View Loan Case</a>
                        </div>
                    @endif
                @else
                    <div class="text-muted small">Select a case from the queue.</div>
                @endif
            </div>
        </div>

        {{-- Card 2 --}}
        <div class="acc-panel">
            <div class="acc-panel-h">
                <h3 class="acc-panel-title"><span class="acc-hico"><i class="bi bi-cash-stack"></i></span> Payout Reconciliation</h3>
            </div>
            <div class="acc-panel-b">
                <div class="acc-snap-row"><span class="acc-snap-k">Expected Payout</span><span class="acc-snap-v">{{ $inr($reconciliation['expected'] ?? 0) }}</span></div>
                <div class="acc-snap-row"><span class="acc-snap-k">Received Amount</span><span class="acc-snap-v">{{ $inr($reconciliation['received'] ?? 0) }}</span></div>
                <div class="acc-snap-row"><span class="acc-snap-k">Outstanding Balance</span><span class="acc-snap-v">{{ $inr($reconciliation['outstanding'] ?? 0) }}</span></div>
                <div class="acc-note info">Partial receipts supported. Posted receipts keep date and reference.</div>
            </div>
        </div>

        {{-- Card 3 --}}
        <div class="acc-panel">
            <div class="acc-panel-h">
                <h3 class="acc-panel-title"><span class="acc-hico"><i class="bi bi-list-check"></i></span> Required Confirmations</h3>
            </div>
            <div class="acc-panel-b">
                @forelse($confirmations as $c)
                    <div class="acc-conf-row">
                        <div class="acc-conf-left">
                            <span class="dot {{ !empty($c['done']) ? 'ok' : '' }}">
                                @if(!empty($c['done']))
                                    <i class="bi bi-check-lg"></i>
                                @else
                                    <i class="bi bi-circle"></i>
                                @endif
                            </span>
                            <span class="lbl">{{ $c['label'] }}{{ $c['key'] === 'pms' ? ' (Verification)' : '' }}</span>
                        </div>
                        <span class="acc-badge {{ !empty($c['done']) ? 'ok' : 'warn' }}">{{ !empty($c['done']) ? 'Completed' : 'Pending' }}</span>
                    </div>
                @empty
                    <div class="text-muted small">Select a case to view confirmations.</div>
                @endforelse
                <div class="acc-note warn">Report number is mandatory for payout eligibility.</div>
            </div>
        </div>

        {{-- Card 4 --}}
        <div class="acc-panel">
            <div class="acc-panel-h">
                <h3 class="acc-panel-title"><span class="acc-hico"><i class="bi bi-clock-history"></i></span> Recent Accounts Activity</h3>
                @if($selected)
                    <a href="{{ route('goal.accounts.show', $selected) }}" class="acc-panel-link">View All</a>
                @endif
            </div>
            <div class="acc-panel-b">
                <ul class="acc-act">
                    @forelse(collect($activity)->take(3) as $row)
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
    </aside>
</div>
