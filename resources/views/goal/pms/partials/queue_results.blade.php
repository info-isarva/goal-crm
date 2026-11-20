@php
    $tab = $filters['tab'] ?? 'all';
    $fmtPill = $fmtPill ?? function (?string $status) {
        $s = (string) $status;
        return match (true) {
            in_array($s, ['Verified', 'Completed'], true) => 'ok',
            in_array($s, ['Pending Verification', 'Pending', 'In Progress', 'On Hold', 'Due', 'Scheduled'], true) => 'warn',
            in_array($s, ['Rejected', 'Overdue', 'Closed', 'Cancelled'], true) => 'bad',
            in_array($s, ['Follow-up Due'], true) => 'info',
            default => 'info',
        };
    };
    $inr = $inr ?? fn ($n) => '₹ '.number_format((float) $n, 0);
    $qs = $qs ?? fn (array $extra = []) => array_filter(array_merge(request()->except(['page', 'partial']), $extra), fn ($v) => $v !== null && $v !== '');
    $dateFromLabel = !empty($filters['date_from']) ? \Carbon\Carbon::parse($filters['date_from'])->format('d M Y') : '';
    $dateToLabel = !empty($filters['date_to']) ? \Carbon\Carbon::parse($filters['date_to'])->format('d M Y') : '';
    $dateRangeLabel = ($dateFromLabel && $dateToLabel)
        ? ($dateFromLabel.' – '.$dateToLabel)
        : ($dateFromLabel ?: $dateToLabel);
    $hasActiveFilters = filled($filters['q'] ?? null)
        || filled($filters['branch_id'] ?? null)
        || filled($filters['assigned_to'] ?? null)
        || filled($filters['date_from'] ?? null)
        || filled($filters['date_to'] ?? null)
        || filled($filters['verification_status'] ?? null)
        || filled($filters['followup_status'] ?? null);
@endphp
<div class="pms-layout" id="pmsLayout" data-tab="{{ $tab }}">
    <div class="pms-main">
        <div class="pms-kpi-row">
            <a class="pms-kpi tone-blue {{ $tab === 'pending' ? 'is-active' : '' }}" href="{{ route('goal.pms.index', $qs(['tab' => 'pending'])) }}" data-pms-nav data-tab="pending">
                <span class="pms-kpi-ico"><i class="bi bi-layers"></i></span>
                <div class="pms-kpi-body">
                    <p class="pms-kpi-label"><span class="full">Pending Verification</span><span class="short">Pending</span></p>
                    <p class="pms-kpi-value">{{ number_format($kpis['pending'] ?? 0) }}</p>
                </div>
            </a>
            <a class="pms-kpi tone-green {{ $tab === 'verified' ? 'is-active' : '' }}" href="{{ route('goal.pms.index', $qs(['tab' => 'verified'])) }}" data-pms-nav data-tab="verified">
                <span class="pms-kpi-ico"><i class="bi bi-shield-check"></i></span>
                <div class="pms-kpi-body">
                    <p class="pms-kpi-label"><span class="full">Verified This Month</span><span class="short">Verified</span></p>
                    <p class="pms-kpi-value">{{ number_format($kpis['verified_month'] ?? 0) }}</p>
                </div>
            </a>
            <a class="pms-kpi tone-amber {{ $tab === 'followups_due' ? 'is-active' : '' }}" href="{{ route('goal.pms.index', $qs(['tab' => 'followups_due'])) }}" data-pms-nav data-tab="followups_due">
                <span class="pms-kpi-ico"><i class="bi bi-calendar3"></i></span>
                <div class="pms-kpi-body">
                    <p class="pms-kpi-label"><span class="full">Follow-ups Due</span><span class="short">Follow-ups</span></p>
                    <p class="pms-kpi-value">{{ number_format($kpis['followups_due'] ?? 0) }}</p>
                </div>
            </a>
            <a class="pms-kpi tone-red {{ $tab === 'overdue' ? 'is-active' : '' }}" href="{{ route('goal.pms.index', $qs(['tab' => 'overdue'])) }}" data-pms-nav data-tab="overdue">
                <span class="pms-kpi-ico"><i class="bi bi-exclamation-triangle"></i></span>
                <div class="pms-kpi-body">
                    <p class="pms-kpi-label"><span class="full">Overdue Follow-ups</span><span class="short">Overdue</span></p>
                    <p class="pms-kpi-value">{{ number_format($kpis['overdue'] ?? 0) }}</p>
                </div>
            </a>
        </div>

        <div class="pms-tabs" role="tablist" aria-label="Queue tabs">
            <a class="pms-tab {{ $tab === 'all' ? 'active' : '' }}" href="{{ route('goal.pms.index', $qs(['tab' => 'all'])) }}" data-pms-nav data-tab="all">
                <i class="bi bi-folder2-open"></i>
                <span class="full">All Queue</span><span class="short">All</span>
                <span class="cnt">({{ $tabCounts['all'] ?? 0 }})</span>
            </a>
            <a class="pms-tab tab-pending {{ $tab === 'pending' ? 'active' : '' }}" href="{{ route('goal.pms.index', $qs(['tab' => 'pending'])) }}" data-pms-nav data-tab="pending">
                <i class="bi bi-layers"></i>
                <span class="full">Pending Verification</span><span class="short">Pending</span>
                <span class="cnt">({{ $tabCounts['pending'] ?? 0 }})</span>
            </a>
            <a class="pms-tab tab-verified {{ $tab === 'verified' ? 'active' : '' }}" href="{{ route('goal.pms.index', $qs(['tab' => 'verified'])) }}" data-pms-nav data-tab="verified">
                <i class="bi bi-check-circle"></i>
                <span class="full">Verified</span><span class="short">Verified</span>
                <span class="cnt">({{ $tabCounts['verified'] ?? 0 }})</span>
            </a>
            <a class="pms-tab tab-due {{ $tab === 'followups_due' ? 'active' : '' }}" href="{{ route('goal.pms.index', $qs(['tab' => 'followups_due'])) }}" data-pms-nav data-tab="followups_due">
                <i class="bi bi-calendar3"></i>
                <span class="full">Follow-ups Due</span><span class="short">Due</span>
                <span class="cnt">({{ $tabCounts['followups_due'] ?? 0 }})</span>
            </a>
            <a class="pms-tab tab-overdue {{ $tab === 'overdue' ? 'active' : '' }}" href="{{ route('goal.pms.index', $qs(['tab' => 'overdue'])) }}" data-pms-nav data-tab="overdue">
                <i class="bi bi-exclamation-triangle"></i>
                <span class="full">Overdue</span><span class="short">Overdue</span>
                <span class="cnt">({{ $tabCounts['overdue'] ?? 0 }})</span>
            </a>
        </div>

        <form method="GET" action="{{ route('goal.pms.index') }}" class="pms-filters {{ $hasActiveFilters ? '' : 'is-collapsed' }}" id="pmsFilters">
            <input type="hidden" name="tab" id="pmsTabInput" value="{{ $tab }}">
            <button type="button" class="pms-filters-toggle" id="pmsFiltersToggle" aria-expanded="{{ $hasActiveFilters ? 'true' : 'false' }}">
                <span class="ttl"><i class="bi bi-funnel"></i> Filters</span>
                <span class="meta">{{ $hasActiveFilters ? 'Filters applied' : 'Search & refine' }}</span>
                <i class="bi bi-chevron-down chev"></i>
            </button>
            <div class="pms-filters-body">
            <div class="pms-filters-row top">
                <div class="pms-field">
                    <label for="pms-q">Search</label>
                    <div class="pms-search">
                        <i class="bi bi-search"></i>
                        <input id="pms-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Search by Case Reference / Customer Name...">
                    </div>
                </div>
                <div class="pms-field">
                    <label for="pms-branch">Branch</label>
                    <select id="pms-branch" name="branch_id" class="form-select">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" @selected((string)($filters['branch_id'] ?? '') === (string)$b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="pms-field">
                    <label for="pms-assignee">Assigned PMS User</label>
                    <select id="pms-assignee" name="assigned_to" class="form-select">
                        <option value="">All Users</option>
                        @foreach($pmsUsers as $u)
                            <option value="{{ $u->id }}" @selected((string)($filters['assigned_to'] ?? '') === (string)$u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="pms-field">
                    <label for="pmsDateRange">Disbursement Date Range</label>
                    <input type="hidden" name="date_from" id="pmsDateFrom" value="{{ $filters['date_from'] ?? '' }}">
                    <input type="hidden" name="date_to" id="pmsDateTo" value="{{ $filters['date_to'] ?? '' }}">
                    <div class="pms-date-wrap">
                        <i class="bi bi-calendar3"></i>
                        <input type="text" id="pmsDateRange" class="form-control" placeholder="Select date range" value="{{ $dateRangeLabel }}" readonly>
                    </div>
                </div>
            </div>
            <div class="pms-filters-row bottom">
                <div class="pms-field">
                    <label for="pms-vstatus">Verification Status</label>
                    <select id="pms-vstatus" name="verification_status" class="form-select">
                        <option value="">All Status</option>
                        @foreach(['Pending Verification','In Progress','Verified','Rejected','On Hold','Follow-up Due','Closed'] as $st)
                            <option value="{{ $st }}" @selected(($filters['verification_status'] ?? '') === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="pms-field">
                    <label for="pms-fstatus">Follow-up Status</label>
                    <select id="pms-fstatus" name="followup_status" class="form-select">
                        <option value="">All Status</option>
                        @foreach(['Scheduled','Due','In Progress','Completed','Overdue','Cancelled'] as $st)
                            <option value="{{ $st }}" @selected(($filters['followup_status'] ?? '') === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="pms-field pms-field-spacer" aria-hidden="true"></div>
                <div class="pms-filter-actions">
                    <button type="submit" class="pd-btn" id="pmsApplyBtn"><i class="bi bi-funnel"></i> Apply</button>
                    <a href="{{ route('goal.pms.index', ['tab' => $tab]) }}" class="pd-btn-ghost" id="pmsResetBtn"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                </div>
            </div>
            </div>
        </form>

        <div class="pd-card pms-qcard">
            <div class="pd-card-head">
                <div class="pms-qhead">
                    <span class="pms-qico"><i class="bi bi-layers"></i></span>
                    <div>
                        <h5 class="pms-qtitle">PMS Verification Queue</h5>
                        <p class="pms-qsub">Cases created automatically after verified disbursement (PD-01).</p>
                    </div>
                </div>
                <span class="pms-qbadge">Total Records: {{ number_format($items->total()) }}</span>
            </div>
            <div class="pd-table-wrap">
                <table class="pd-table pms-qtable align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Case Reference</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Branch</th>
                            <th>Disbursement Date</th>
                            <th>Disbursed Amount</th>
                            <th>PMS Status</th>
                            <th>Next Follow-up</th>
                            <th>Assigned To</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($items as $i => $item)
                        @php
                            $case = $item->loanCase;
                            $disb = $item->disbursement;
                            $isSel = $selected && (int)$selected->id === (int)$item->id;
                            $nextFu = $item->followups->first();
                        @endphp
                        <tr class="{{ $isSel ? 'pms-row-active' : '' }}">
                            <td data-label="#">{{ $items->firstItem() + $i }}</td>
                            <td data-label="Case Reference">
                                <a class="pd-case" href="{{ route('goal.pms.index', $qs(['selected' => $item->id])) }}" data-pms-nav>{{ $case?->case_no ?: '—' }}</a>
                            </td>
                            <td data-label="Customer"><div class="pd-cust">{{ $case?->customer_name ?: '—' }}</div></td>
                            <td data-label="Product">{{ $case?->product ?: '—' }}</td>
                            <td data-label="Branch">{{ $case?->orgUnit?->name ?: '—' }}</td>
                            <td data-label="Disbursement Date">{{ optional($disb?->disbursed_on)->format('d M Y') ?: '—' }}</td>
                            <td data-label="Disbursed Amount"><span class="pms-amt">{{ $disb ? $inr($disb->amount) : '—' }}</span></td>
                            <td data-label="PMS Status"><span class="pd-pill {{ $fmtPill($item->statusLabel()) }}">{{ $item->statusLabel() }}</span></td>
                            <td data-label="Next Follow-up">
                                @if($nextFu)
                                    {{ $nextFu->scheduled_date?->format('d M Y') }}
                                @elseif($item->follow_up_at)
                                    {{ $item->follow_up_at->format('d M Y') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td data-label="Assigned To">{{ $item->assignee?->name ?: '—' }}</td>
                            <td class="pd-col-actions" data-label="Actions">
                                <div class="pms-qacts">
                                    <a href="{{ route('goal.pms.show', $item) }}" class="pms-abtn view" title="View">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    @if($item->canCompleteVerification())
                                        <a href="{{ route('goal.pms.show', $item) }}#verify" class="pms-abtn verify" title="Verify">
                                            <i class="bi bi-check2"></i> Verify
                                        </a>
                                    @endif
                                    <div class="dropdown">
                                        <button class="pms-abtn more" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="More">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="{{ route('goal.pms.show', $item) }}#activity">Activity history</a></li>
                                            <li><a class="dropdown-item" href="{{ route('goal.pms.show', $item) }}#followups">Follow-ups</a></li>
                                            <li><a class="dropdown-item" href="{{ route('goal.pms.show', $item) }}#reassign">Reassign</a></li>
                                            @if($case)
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item" href="{{ route('goal.cases.show', $case) }}">Open loan case</a></li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="pd-col-empty">
                                <div class="pd-empty">
                                    <i class="bi bi-inbox"></i>
                                    No PMS items match. Verified disbursements create queue entries via PD-01.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pms-qfoot">
                <div>
                    @if($items->total())
                        Showing {{ $items->firstItem() }} to {{ $items->lastItem() }} of {{ number_format($items->total()) }} entries
                    @else
                        No entries
                    @endif
                </div>
                <div class="pms-pager">{{ $items->links() }}</div>
            </div>
        </div>
    </div>

    <aside class="pms-side">
        <div class="pms-panel">
            <div class="pms-panel-h">
                <h3 class="pms-panel-title"><span class="pms-hico"><i class="bi bi-file-earmark-text"></i></span> Selected Case Snapshot</h3>
            </div>
            <div class="pms-panel-b">
                @if($selected)
                    @php $sc = $selected->loanCase; $sd = $selected->disbursement; @endphp
                    <div class="pms-snap-rows">
                        <div class="pms-snap-row">
                            <p class="pms-snap-k">Case Reference</p>
                            <p class="pms-snap-v mono">{{ $sc?->case_no ?: '—' }}</p>
                        </div>
                        <div class="pms-snap-row">
                            <p class="pms-snap-k">Customer</p>
                            <p class="pms-snap-v">{{ $sc?->customer_name ?: '—' }}</p>
                        </div>
                        <div class="pms-snap-row">
                            <p class="pms-snap-k">Product</p>
                            <p class="pms-snap-v">{{ $sc?->product ?: '—' }}</p>
                        </div>
                        <div class="pms-snap-row">
                            <p class="pms-snap-k">Branch</p>
                            <p class="pms-snap-v">{{ $sc?->orgUnit?->name ?: '—' }}</p>
                        </div>
                        <div class="pms-snap-row">
                            <p class="pms-snap-k">Verified Disbursement (PD-01)</p>
                            <p class="pms-snap-v">{{ optional($sd?->disbursed_on)->format('d M Y') ?: '—' }}</p>
                        </div>
                        <div class="pms-snap-row">
                            <p class="pms-snap-k">Disbursed Amount</p>
                            <p class="pms-snap-v">{{ $sd ? $inr($sd->amount) : '—' }}</p>
                        </div>
                        <div class="pms-snap-row">
                            <p class="pms-snap-k">Linked Loan Case</p>
                            <p class="pms-snap-v">
                                @if($sc)
                                    <a href="{{ route('goal.cases.show', $sc) }}">{{ $sc->case_no }}</a>
                                @else
                                    —
                                @endif
                            </p>
                        </div>
                    </div>
                    @if($sc)
                        <a href="{{ route('goal.cases.show', $sc) }}" class="pms-view-case">
                            <i class="bi bi-box-arrow-up-right"></i> View Loan Case
                        </a>
                    @endif
                @else
                    <div class="pd-muted">Select a case from the queue.</div>
                @endif
            </div>
        </div>

        <div class="pms-panel">
            <div class="pms-panel-h">
                <h3 class="pms-panel-title"><span class="pms-hico"><i class="bi bi-list-check"></i></span> Portfolio Follow-ups</h3>
                @if($selected)
                    <a href="{{ route('goal.pms.show', $selected) }}#followups" class="pms-panel-link">+ Add Follow-up</a>
                @endif
            </div>
            <div class="pms-panel-b">
                @if(($selected?->followups ?? collect())->count())
                    <div style="overflow-x:auto">
                        <table class="pms-fu-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Follow-up Task</th>
                                    <th>Due Date</th>
                                    <th>Owner</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(($selected->followups ?? collect())->take(5) as $fi => $fu)
                                    <tr>
                                        <td>{{ $fi + 1 }}</td>
                                        <td class="task">{{ $fu->subject }}</td>
                                        <td>{{ $fu->scheduled_date?->format('d M Y') }}</td>
                                        <td>{{ $fu->assignee?->name ?: '—' }}</td>
                                        <td><span class="pd-pill {{ $fmtPill($fu->displayStatus()) }}">{{ $fu->displayStatus() }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="pd-muted">No follow-ups yet.</div>
                @endif
            </div>
        </div>

        <div class="pms-panel">
            <div class="pms-panel-h">
                <h3 class="pms-panel-title"><span class="pms-hico"><i class="bi bi-shield-check"></i></span> PD-01 Workflow (Post-Disbursement)</h3>
            </div>
            <div class="pms-panel-b">
                <ul class="pms-wf">
                    @forelse($workflow as $ev)
                        @php
                            $state = $ev['state'] ?? (!empty($ev['done']) ? 'done' : 'pending');
                            $badgeClass = match ($ev['badge'] ?? null) {
                                'On Hold' => '',
                                'Eligible', 'Paid', 'Verified', 'Approved' => 'ok',
                                default => (($ev['badge'] ?? null) ? 'info' : null),
                            };
                        @endphp
                        <li class="{{ $state }}">
                            <span class="pms-wf-dot">
                                @if($state === 'done')
                                    <i class="bi bi-check-lg"></i>
                                @elseif($state === 'hold')
                                    <i class="bi bi-pause-fill" style="font-size:.55rem"></i>
                                @endif
                            </span>
                            <div class="pms-wf-main">
                                <p class="pms-wf-title">{{ $ev['label'] }}</p>
                                @if(!empty($ev['detail']))
                                    <p class="pms-wf-detail">{{ $ev['detail'] }}</p>
                                @endif
                            </div>
                            <div class="pms-wf-meta">
                                <span class="pms-wf-time">{{ $ev['at'] ? \Carbon\Carbon::parse($ev['at'])->format('d M Y, h:i A') : '—' }}</span>
                                @if(!empty($ev['badge']))
                                    <span class="pms-wf-badge {{ $badgeClass }}">{{ $ev['badge'] }}</span>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="pending">
                            <span class="pms-wf-dot"></span>
                            <div class="pms-wf-main"><p class="pms-wf-detail">Select a case to view workflow.</p></div>
                            <div></div>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="pms-panel">
            <div class="pms-panel-h">
                <h3 class="pms-panel-title"><span class="pms-hico"><i class="bi bi-clock-history"></i></span> Recent PMS Activity</h3>
                @if($selected)
                    <a href="{{ route('goal.pms.show', $selected) }}#activity" class="pms-panel-link">View All</a>
                @endif
            </div>
            <div class="pms-panel-b">
                <ul class="pms-act">
                    @forelse(($selected?->activityLogs ?? collect())->take(6) as $log)
                        <li>
                            <div class="t">{{ $log->description }}</div>
                            <div class="m">by {{ $log->user?->name ?: 'System' }} | {{ $log->created_at?->format('d M Y, h:i A') }}</div>
                        </li>
                    @empty
                        <li><div class="m" style="padding-left:0">No activity yet.</div></li>
                    @endforelse
                </ul>
            </div>
        </div>
    </aside>
</div>
