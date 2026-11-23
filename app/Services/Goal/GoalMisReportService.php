<?php

namespace App\Services\Goal;

use App\Models\GoalBankApplication;
use App\Models\GoalCaseTask;
use App\Models\GoalDisbursement;
use App\Models\GoalLoanCase;
use App\Models\GoalLoanLead;
use App\Models\GoalOrgUnit;
use App\Models\GoalPayout;
use App\Models\GoalRevenueEntry;
use App\Models\GoalTarget;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class GoalMisReportService
{
    public const REPORTS = [
        'lead_performance' => ['Lead Performance', 'operational', 'Lead generation, allocation and conversion.'],
        'loan_processing' => ['Loan Processing Report', 'operational', 'Case pipeline from creation to disbursement.'],
        'visit_followup' => ['Visit & Follow-up Report', 'operational', 'Visits, calls and follow-up activity.'],
        'exception_report' => ['Exception Report', 'operational', 'Items needing management attention.'],
        'case_summary' => ['Case Summary Report', 'operational', 'Consolidated case lifecycle view.'],
        'disbursement_summary' => ['Disbursement Summary', 'financial', 'Actual disbursement transactions.'],
        'revenue_ledger' => ['Revenue Ledger', 'financial', 'Revenue generated, received and outstanding.'],
        'payout_ageing' => ['Payout Ageing', 'financial', 'Payout pending, paid and ageing buckets.'],
        'branch_mis' => ['Branch MIS', 'performance', 'Complete branch business performance.'],
        'target_vs_achievement' => ['Target vs Achievement', 'performance', 'Targets compared with actuals.'],
        'hierarchy_performance' => ['Hierarchy Performance', 'performance', 'Org hierarchy performance roll-up.'],
        'lender_performance' => ['Lender Performance', 'performance', 'Bank and lender performance metrics.'],
    ];

    public function __construct(
        protected GoalAnalyticsMisService $analytics
    ) {}

    public function catalog(): array
    {
        return self::REPORTS;
    }

    public function build(User $user, string $reportKey, array $filters = []): array
    {
        if (! isset(self::REPORTS[$reportKey])) {
            throw new \InvalidArgumentException('Unknown MIS report.');
        }

        [$title, $group, $description] = self::REPORTS[$reportKey];
        $scopeIds = $this->scopeIds($user, $filters);
        $range = $this->dateRange($filters);

        $payload = match ($reportKey) {
            'branch_mis' => $this->branchMis($user, $scopeIds, $range, $filters),
            'lead_performance' => $this->leadPerformance($user, $scopeIds, $range, $filters),
            'loan_processing' => $this->loanProcessing($user, $scopeIds, $range, $filters),
            'visit_followup' => $this->visitFollowup($user, $scopeIds, $range, $filters),
            'exception_report' => $this->exceptionReport($user, $scopeIds, $range, $filters),
            'case_summary' => $this->caseSummary($user, $scopeIds, $range, $filters),
            'disbursement_summary' => $this->disbursementSummary($user, $scopeIds, $range, $filters),
            'revenue_ledger' => $this->revenueLedger($user, $scopeIds, $range, $filters),
            'payout_ageing' => $this->payoutAgeing($user, $scopeIds, $range, $filters),
            'target_vs_achievement' => $this->targetVsAchievement($user, $scopeIds, $range, $filters),
            'hierarchy_performance' => $this->hierarchyPerformance($user, $scopeIds, $range, $filters),
            'lender_performance' => $this->lenderPerformance($user, $scopeIds, $range, $filters),
            default => ['kpis' => [], 'charts' => [], 'columns' => [], 'rows' => []],
        };

        return array_merge([
            'key' => $reportKey,
            'title' => $title,
            'description' => $description,
            'group' => $group,
            'filters' => array_merge($filters, [
                'date_from' => $range['start']->format('Y-m-d'),
                'date_to' => $range['end']->format('Y-m-d'),
            ]),
            'scope_label' => $this->analyticsScopeLabel($user, $scopeIds),
            'period_label' => $range['start']->format('d M Y').' – '.$range['end']->format('d M Y'),
            'fy' => $filters['fy'] ?? '',
            'generated_at' => now(),
        ], $payload);
    }

    protected function branchMis(User $user, array $scopeIds, array $range, array $filters): array
    {
        $leads = $this->leadsBase($user, $scopeIds, $filters)
            ->whereBetween('created_at', [$range['start'], $range['end']])->get(['id', 'goal_org_unit_id', 'status', 'assigned_to']);
        $cases = $this->casesBase($user, $scopeIds, $filters)
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->with(['orgUnit:id,name', 'disbursements'])
            ->get();

        $branchIds = $cases->pluck('goal_org_unit_id')->merge($leads->pluck('goal_org_unit_id'))->filter()->unique()->values();
        $branches = GoalOrgUnit::query()->whereIn('id', $branchIds)->get(['id', 'name'])->keyBy('id');

        $rows = [];
        $trendMonths = collect();
        for ($i = 5; $i >= 0; $i--) {
            $m = $range['end']->copy()->startOfMonth()->subMonths($i);
            $trendMonths->push($m->format('M'));
        }

        $leadTrend = [];
        $caseTrend = [];
        $disbTrend = [];
        foreach ($trendMonths as $idx => $label) {
            $mStart = $range['end']->copy()->startOfMonth()->subMonths(5 - $idx)->startOfMonth();
            $mEnd = $mStart->copy()->endOfMonth();
            $leadTrend[] = $this->leadsBase($user, $scopeIds, $filters)->whereBetween('created_at', [$mStart, $mEnd])->count();
            $caseTrend[] = $this->casesBase($user, $scopeIds, $filters)->whereBetween('created_at', [$mStart, $mEnd])->count();
            $disbTrend[] = (float) GoalDisbursement::query()
                ->where('status', 'Verified')
                ->whereBetween('disbursed_on', [$mStart, $mEnd])
                ->whereHas('loanCase', function ($q) use ($user, $scopeIds, $filters) {
                    $this->applyCaseScope($q, $user, $scopeIds, $filters);
                })->sum('amount');
        }

        foreach ($branches as $bid => $branch) {
            $bLeads = $leads->where('goal_org_unit_id', $bid);
            $bCases = $cases->where('goal_org_unit_id', $bid);
            $disbursedCases = $bCases->where('status', 'Disbursed');
            $disbAmt = $disbursedCases->sum(function ($c) {
                return (float) $c->disbursements->where('status', 'Verified')->sum('amount');
            });
            $login = $bCases->whereIn('status', ['Bank Login', 'Bank Query', 'Processing', 'Sanctioned', 'Disbursed', 'PMS'])->count();
            $sanc = $bCases->whereIn('status', ['Sanctioned', 'Disbursed', 'PMS'])->count();
            $rows[] = [
                'branch' => $branch->name,
                'leads' => $bLeads->count(),
                'allocated' => $bLeads->whereNotNull('assigned_to')->count(),
                'cases' => $bCases->count(),
                'bank_login' => $login,
                'sanctioned' => $sanc,
                'disbursed' => $disbursedCases->count(),
                'disbursed_amount' => $this->money($disbAmt),
                'disbursed_amount_raw' => $disbAmt,
                'conversion' => $bLeads->count() > 0
                    ? round(($disbursedCases->count() / $bLeads->count()) * 100, 1).'%'
                    : '0%',
            ];
        }
        usort($rows, fn ($a, $b) => $b['disbursed_amount_raw'] <=> $a['disbursed_amount_raw']);

        $totalLeads = $leads->count();
        $totalCases = $cases->count();
        $totalDisb = $cases->where('status', 'Disbursed')->count();
        $conv = $totalLeads > 0 ? round(($totalDisb / $totalLeads) * 100, 1) : 0;

        return [
            'kpis' => [
                ['label' => 'Total Leads', 'value' => number_format($totalLeads), 'tone' => 'blue'],
                ['label' => 'Cases', 'value' => number_format($totalCases), 'tone' => 'indigo'],
                ['label' => 'Disbursed', 'value' => number_format($totalDisb), 'tone' => 'green'],
                ['label' => 'Conversion', 'value' => $conv.'%', 'tone' => 'teal'],
            ],
            'charts' => [
                [
                    'id' => 'misTrend',
                    'type' => 'line',
                    'title' => 'Monthly business trend',
                    'labels' => $trendMonths->values()->all(),
                    'datasets' => [
                        ['label' => 'Leads', 'data' => $leadTrend, 'borderColor' => '#2563eb', 'backgroundColor' => 'rgba(37,99,235,.15)', 'tension' => .35, 'fill' => true],
                        ['label' => 'Cases', 'data' => $caseTrend, 'borderColor' => '#7c3aed', 'backgroundColor' => 'transparent', 'tension' => .35],
                        ['label' => 'Disbursed Amt', 'data' => $disbTrend, 'borderColor' => '#0d9488', 'backgroundColor' => 'transparent', 'tension' => .35],
                    ],
                ],
                [
                    'id' => 'misBranchBars',
                    'type' => 'bar',
                    'title' => 'Branch-wise disbursement',
                    'labels' => array_column(array_slice($rows, 0, 8), 'branch'),
                    'datasets' => [[
                        'label' => 'Disbursed Amount',
                        'data' => array_column(array_slice($rows, 0, 8), 'disbursed_amount_raw'),
                        'backgroundColor' => '#0d9488',
                    ]],
                ],
            ],
            'columns' => [
                ['key' => 'branch', 'label' => 'Branch'],
                ['key' => 'leads', 'label' => 'Leads'],
                ['key' => 'allocated', 'label' => 'Allocated'],
                ['key' => 'cases', 'label' => 'Cases'],
                ['key' => 'bank_login', 'label' => 'Bank Login'],
                ['key' => 'sanctioned', 'label' => 'Sanctioned'],
                ['key' => 'disbursed', 'label' => 'Disbursed'],
                ['key' => 'disbursed_amount', 'label' => 'Disbursed Amount'],
                ['key' => 'conversion', 'label' => 'Conversion %'],
            ],
            'rows' => $rows,
            'search_keys' => ['branch'],
        ];
    }

    protected function leadPerformance(User $user, array $scopeIds, array $range, array $filters): array
    {
        $leads = $this->leadsBase($user, $scopeIds, $filters)
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->with(['assignee:id,name', 'orgUnit:id,name'])
            ->latest()
            ->limit(2000)
            ->get();

        $converted = $leads->filter(fn ($l) => GoalLoanCase::query()->where('goal_loan_lead_id', $l->id)->exists())->count();
        $allocated = $leads->whereNotNull('assigned_to')->count();
        $unallocated = $leads->whereNull('assigned_to')->count();
        $lost = $leads->filter(fn ($l) => str_contains((string) $l->status, 'Closed') || str_contains((string) $l->status, 'Lost'))->count();
        $eligible = max(1, $leads->count() - $lost);
        $conv = round(($converted / $eligible) * 100, 1);

        $bySource = $leads->groupBy(fn ($l) => $l->source ?: 'Unknown')->map->count();
        $byProduct = $leads->groupBy(fn ($l) => $l->product ?: 'Unknown')->map->count();
        $byBranch = $leads->groupBy(fn ($l) => $l->orgUnit?->name ?: 'Unassigned')->map->count();

        $rows = $leads->take(500)->map(function ($l) {
            $case = GoalLoanCase::query()->where('goal_loan_lead_id', $l->id)->value('case_no');

            return [
                'lead_id' => $l->ref_no,
                'lead_name' => $l->customer_name,
                'mobile' => $l->mobile,
                'source' => $l->source ?: '—',
                'product' => $l->product ?: '—',
                'received' => optional($l->created_at)->format('d M Y'),
                'branch' => $l->orgUnit?->name ?: '—',
                'assigned_bm' => $l->assignee?->name ?: '—',
                'status' => $l->status,
                'follow_up' => optional($l->follow_up_at)->format('d M Y H:i') ?: '—',
                'case_ref' => $case ?: '—',
                'age' => $l->created_at ? (int) $l->created_at->diffInDays(now()) : 0,
                '_link' => route('goal.leads.show', $l),
            ];
        })->values()->all();

        return [
            'kpis' => [
                ['label' => 'Total Leads Received', 'value' => number_format($leads->count()), 'tone' => 'blue'],
                ['label' => 'Allocated', 'value' => number_format($allocated), 'tone' => 'teal'],
                ['label' => 'Unallocated', 'value' => number_format($unallocated), 'tone' => 'amber'],
                ['label' => 'Converted', 'value' => number_format($converted), 'tone' => 'green'],
                ['label' => 'Lost', 'value' => number_format($lost), 'tone' => 'rose'],
                ['label' => 'Conversion %', 'value' => $conv.'%', 'tone' => 'indigo'],
            ],
            'charts' => [
                ['id' => 'leadSource', 'type' => 'doughnut', 'title' => 'Leads by Source', 'labels' => $bySource->keys()->all(), 'datasets' => [['data' => $bySource->values()->all(), 'backgroundColor' => $this->palette($bySource->count())]]],
                ['id' => 'leadProduct', 'type' => 'bar', 'title' => 'Leads by Product', 'labels' => $byProduct->keys()->all(), 'datasets' => [['label' => 'Leads', 'data' => $byProduct->values()->all(), 'backgroundColor' => '#2563eb']]],
                ['id' => 'leadBranch', 'type' => 'bar', 'title' => 'Leads by Branch', 'labels' => $byBranch->keys()->take(8)->values()->all(), 'datasets' => [['label' => 'Leads', 'data' => $byBranch->values()->take(8)->values()->all(), 'backgroundColor' => '#0d9488']]],
            ],
            'columns' => [
                ['key' => 'lead_id', 'label' => 'Lead ID'],
                ['key' => 'lead_name', 'label' => 'Lead Name'],
                ['key' => 'mobile', 'label' => 'Mobile'],
                ['key' => 'source', 'label' => 'Source'],
                ['key' => 'product', 'label' => 'Product'],
                ['key' => 'received', 'label' => 'Received'],
                ['key' => 'branch', 'label' => 'Branch'],
                ['key' => 'assigned_bm', 'label' => 'Assigned BM'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'follow_up', 'label' => 'Follow-up'],
                ['key' => 'case_ref', 'label' => 'Case Ref'],
                ['key' => 'age', 'label' => 'Lead Age'],
            ],
            'rows' => $rows,
            'search_keys' => ['lead_id', 'lead_name', 'mobile', 'branch', 'status'],
            'link_key' => '_link',
        ];
    }

    protected function loanProcessing(User $user, array $scopeIds, array $range, array $filters): array
    {
        $cases = $this->casesBase($user, $scopeIds, $filters)
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->with(['owner:id,name', 'orgUnit:id,name', 'bankApplications'])
            ->latest()
            ->limit(2000)
            ->get();

        $byStatus = $cases->groupBy('status')->map->count();
        $rows = $cases->take(500)->map(function ($c) {
            $pendingDays = $c->updated_at ? (int) $c->updated_at->diffInDays(now()) : 0;
            $tat = $c->created_at
                ? (int) $c->created_at->diffInDays($c->status === 'Disbursed' ? ($c->updated_at ?? now()) : now())
                : 0;

            return [
                'case_ref' => $c->case_no,
                'customer' => $c->customer_name,
                'product' => $c->product ?: '—',
                'requested' => $this->money((float) $c->loan_amount),
                'lender' => $c->lender_name ?: ($c->bankApplications->first()?->lender_name ?: '—'),
                'branch' => $c->orgUnit?->name ?: '—',
                'owner' => $c->owner?->name ?: '—',
                'created' => optional($c->created_at)->format('d M Y'),
                'status' => $c->status,
                'updated' => optional($c->updated_at)->format('d M Y'),
                'processing_days' => $tat,
                'pending_days' => $pendingDays,
                'pending_reason' => $c->bank_query ?: ($c->rejection_reason ?: '—'),
                '_link' => route('goal.cases.show', $c),
            ];
        })->values()->all();

        $kpiOrder = [
            'Draft' => 'slate',
            'Ready for Login' => 'indigo',
            'Bank Login' => 'blue',
            'Bank Query' => 'amber',
            'Processing' => 'teal',
            'Sanctioned' => 'purple',
            'Disbursed' => 'green',
            'Rejected' => 'rose',
        ];
        $kpis = [['label' => 'Total Cases Created', 'value' => number_format($cases->count()), 'tone' => 'blue']];
        foreach ($kpiOrder as $st => $tone) {
            $kpis[] = ['label' => $st, 'value' => number_format((int) ($byStatus[$st] ?? 0)), 'tone' => $tone];
        }

        return [
            'kpis' => $kpis,
            'charts' => [
                ['id' => 'caseStages', 'type' => 'doughnut', 'title' => 'Cases by Workflow Stage', 'labels' => $byStatus->keys()->all(), 'datasets' => [['data' => $byStatus->values()->all(), 'backgroundColor' => $this->palette($byStatus->count())]]],
                ['id' => 'casePending', 'type' => 'bar', 'title' => 'Branch-wise cases', 'labels' => $cases->groupBy(fn ($c) => $c->orgUnit?->name ?: '—')->map->count()->keys()->take(8)->values()->all(), 'datasets' => [['label' => 'Cases', 'data' => $cases->groupBy(fn ($c) => $c->orgUnit?->name ?: '—')->map->count()->values()->take(8)->values()->all(), 'backgroundColor' => '#4f46e5']]],
            ],
            'columns' => [
                ['key' => 'case_ref', 'label' => 'Case Reference'],
                ['key' => 'customer', 'label' => 'Customer'],
                ['key' => 'product', 'label' => 'Product'],
                ['key' => 'requested', 'label' => 'Requested'],
                ['key' => 'lender', 'label' => 'Lender'],
                ['key' => 'branch', 'label' => 'Branch'],
                ['key' => 'owner', 'label' => 'Owner'],
                ['key' => 'created', 'label' => 'Created'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'processing_days', 'label' => 'TAT Days'],
                ['key' => 'pending_days', 'label' => 'Pending Age'],
                ['key' => 'pending_reason', 'label' => 'Pending Reason'],
            ],
            'rows' => $rows,
            'search_keys' => ['case_ref', 'customer', 'branch', 'status', 'lender'],
            'link_key' => '_link',
        ];
    }

    protected function visitFollowup(User $user, array $scopeIds, array $range, array $filters): array
    {
        $tasks = GoalCaseTask::query()
            ->visitTypes()
            ->with(['loanCase.orgUnit', 'assignee:id,name'])
            ->whereBetween('due_at', [$range['start'], $range['end']])
            ->whereHas('loanCase', function ($q) use ($user, $scopeIds, $filters) {
                $this->applyCaseScope($q, $user, $scopeIds, $filters);
            })
            ->latest('due_at')
            ->limit(2000)
            ->get();

        $completed = $tasks->where('status', GoalCaseTask::STATUS_COMPLETED)->count();
        $open = $tasks->where('status', GoalCaseTask::STATUS_OPEN)->count();
        $overdue = $tasks->filter(fn ($t) => $t->status === GoalCaseTask::STATUS_OPEN && $t->due_at && $t->due_at->lt(now()))->count();
        $today = $tasks->filter(fn ($t) => $t->due_at && $t->due_at->isToday())->count();

        $rows = $tasks->take(500)->map(function ($t) {
            $missed = $t->status === GoalCaseTask::STATUS_OPEN && $t->due_at && $t->due_at->lt(now());

            return [
                'activity_id' => 'T-'.$t->id,
                'customer' => $t->loanCase?->customer_name ?: '—',
                'case_id' => $t->loanCase?->case_no ?: '—',
                'type' => $t->typeLabel(),
                'employee' => $t->assignee?->name ?: '—',
                'branch' => $t->loanCase?->orgUnit?->name ?: '—',
                'scheduled' => optional($t->due_at)->format('d M Y H:i') ?: '—',
                'completed' => optional($t->completed_at)->format('d M Y H:i') ?: '—',
                'status' => $missed ? 'Missed' : $t->status,
                'outcome' => $t->outcome ?: '—',
                'remarks' => Str::limit((string) $t->notes, 80) ?: '—',
                '_link' => $t->loanCase ? route('goal.cases.show', $t->loanCase) : '#',
            ];
        })->values()->all();

        $byEmp = $tasks->groupBy(fn ($t) => $t->assignee?->name ?: 'Unassigned')->map->count();

        return [
            'kpis' => [
                ['label' => 'Scheduled', 'value' => number_format($tasks->count()), 'tone' => 'blue'],
                ['label' => 'Completed', 'value' => number_format($completed), 'tone' => 'green'],
                ['label' => 'Pending', 'value' => number_format($open), 'tone' => 'amber'],
                ['label' => 'Overdue', 'value' => number_format($overdue), 'tone' => 'rose'],
                ['label' => "Today's Follow-ups", 'value' => number_format($today), 'tone' => 'teal'],
            ],
            'charts' => [
                ['id' => 'visitEmp', 'type' => 'bar', 'title' => 'Employee-wise Visits', 'labels' => $byEmp->keys()->take(8)->values()->all(), 'datasets' => [['label' => 'Activities', 'data' => $byEmp->values()->take(8)->values()->all(), 'backgroundColor' => '#0d9488']]],
                ['id' => 'visitStatus', 'type' => 'doughnut', 'title' => 'Completed vs Pending', 'labels' => ['Completed', 'Pending', 'Overdue'], 'datasets' => [['data' => [$completed, max(0, $open - $overdue), $overdue], 'backgroundColor' => ['#16a34a', '#f59e0b', '#e11d48']]]],
            ],
            'columns' => [
                ['key' => 'activity_id', 'label' => 'Activity ID'],
                ['key' => 'customer', 'label' => 'Customer'],
                ['key' => 'case_id', 'label' => 'Lead / Case'],
                ['key' => 'type', 'label' => 'Type'],
                ['key' => 'employee', 'label' => 'Employee'],
                ['key' => 'branch', 'label' => 'Branch'],
                ['key' => 'scheduled', 'label' => 'Scheduled'],
                ['key' => 'completed', 'label' => 'Completed'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'outcome', 'label' => 'Outcome'],
                ['key' => 'remarks', 'label' => 'Remarks'],
            ],
            'rows' => $rows,
            'search_keys' => ['customer', 'case_id', 'employee', 'branch', 'status'],
            'link_key' => '_link',
        ];
    }

    protected function exceptionReport(User $user, array $scopeIds, array $range, array $filters): array
    {
        $rows = [];

        $unallocated = $this->leadsBase($user, $scopeIds, $filters)
            ->with('orgUnit:id,name')
            ->whereNull('assigned_to')
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->limit(200)->get();
        foreach ($unallocated as $l) {
            $rows[] = $this->exceptionRow('Unallocated Leads', $l->ref_no, $l->customer_name, $l->orgUnit?->name, $l->created_at, null, 'High', route('goal.leads.show', $l));
        }

        $overdueFu = $this->leadsBase($user, $scopeIds, $filters)
            ->with('orgUnit:id,name')
            ->whereNotNull('follow_up_at')->where('follow_up_at', '<', now())
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->limit(200)->get();
        foreach ($overdueFu as $l) {
            $rows[] = $this->exceptionRow('Overdue Follow-ups', $l->ref_no, $l->customer_name, $l->orgUnit?->name, $l->follow_up_at, $l->follow_up_at, 'High', route('goal.leads.show', $l));
        }

        $queries = $this->casesBase($user, $scopeIds, $filters)
            ->with('orgUnit:id,name')
            ->where('status', 'Bank Query')
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->limit(200)->get();
        foreach ($queries as $c) {
            $rows[] = $this->exceptionRow('Pending Bank Queries', $c->case_no, $c->customer_name, $c->orgUnit?->name, $c->updated_at, $c->bank_query_due, 'High', route('goal.cases.show', $c));
        }

        $holds = GoalPayout::query()
            ->where('status', 'On Hold')
            ->whereHas('loanCase', fn ($q) => $this->applyCaseScope($q, $user, $scopeIds, $filters))
            ->with('loanCase.orgUnit')
            ->limit(200)->get();
        foreach ($holds as $p) {
            $rows[] = $this->exceptionRow('Payout Hold', $p->loanCase?->case_no, $p->loanCase?->customer_name, $p->loanCase?->orgUnit?->name, $p->created_at, null, 'Medium', $p->loanCase ? route('goal.cases.show', $p->loanCase) : '#');
        }

        $delay = $this->casesBase($user, $scopeIds, $filters)
            ->with('orgUnit:id,name')
            ->whereNotIn('status', ['Disbursed', 'Rejected', 'Closed', 'PMS'])
            ->where('created_at', '<=', now()->subDays(15))
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->limit(200)->get();
        foreach ($delay as $c) {
            $rows[] = $this->exceptionRow('Processing Delay', $c->case_no, $c->customer_name, $c->orgUnit?->name, $c->created_at, null, 'Medium', route('goal.cases.show', $c));
        }

        $high = collect($rows)->where('priority', 'High')->count();

        return [
            'kpis' => [
                ['label' => 'Total Exceptions', 'value' => number_format(count($rows)), 'tone' => 'rose'],
                ['label' => 'High Priority', 'value' => number_format($high), 'tone' => 'orange'],
                ['label' => 'Unallocated Leads', 'value' => number_format($unallocated->count()), 'tone' => 'amber'],
                ['label' => 'Bank Queries', 'value' => number_format($queries->count()), 'tone' => 'indigo'],
                ['label' => 'Payout Holds', 'value' => number_format($holds->count()), 'tone' => 'purple'],
            ],
            'charts' => [
                ['id' => 'excTypes', 'type' => 'doughnut', 'title' => 'Exceptions by Type', 'labels' => collect($rows)->groupBy('type')->keys()->all(), 'datasets' => [['data' => collect($rows)->groupBy('type')->map->count()->values()->all(), 'backgroundColor' => $this->palette(6)]]],
            ],
            'columns' => [
                ['key' => 'type', 'label' => 'Exception Type'],
                ['key' => 'reference', 'label' => 'Reference'],
                ['key' => 'customer', 'label' => 'Customer'],
                ['key' => 'branch', 'label' => 'Branch'],
                ['key' => 'created', 'label' => 'Created'],
                ['key' => 'due', 'label' => 'Due'],
                ['key' => 'pending_days', 'label' => 'Pending Days'],
                ['key' => 'priority', 'label' => 'Priority'],
                ['key' => 'status', 'label' => 'Status'],
            ],
            'rows' => $rows,
            'search_keys' => ['type', 'reference', 'customer', 'branch', 'priority'],
            'link_key' => '_link',
        ];
    }

    protected function caseSummary(User $user, array $scopeIds, array $range, array $filters): array
    {
        $cases = $this->casesBase($user, $scopeIds, $filters)
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->with(['owner:id,name', 'orgUnit:id,name,parent_id', 'lead:id,ref_no', 'disbursements', 'bankApplications'])
            ->latest()
            ->limit(2000)
            ->get();

        $rows = $cases->take(500)->map(function ($c) {
            $verified = $c->disbursements->where('status', 'Verified');
            $cluster = $c->orgUnit?->parent_id
                ? GoalOrgUnit::query()->where('id', $c->orgUnit->parent_id)->value('name')
                : '—';

            return [
                'case_ref' => $c->case_no,
                'lead_ref' => $c->lead?->ref_no ?: '—',
                'customer' => $c->customer_name,
                'mobile' => $c->mobile,
                'product' => $c->product ?: '—',
                'requested' => $this->money((float) $c->loan_amount),
                'branch' => $c->orgUnit?->name ?: '—',
                'cluster' => $cluster,
                'owner' => $c->owner?->name ?: '—',
                'lender' => $c->lender_name ?: ($c->bankApplications->first()?->lender_name ?: '—'),
                'created' => optional($c->created_at)->format('d M Y'),
                'login' => optional($c->bankApplications->first()?->login_date)->format('d M Y') ?: '—',
                'sanction' => optional($c->sanction_date)->format('d M Y') ?: '—',
                'sanction_amt' => $c->sanction_amount !== null ? $this->money((float) $c->sanction_amount) : '—',
                'disb_date' => optional($verified->sortByDesc('disbursed_on')->first()?->disbursed_on)->format('d M Y') ?: '—',
                'disb_amt' => $this->money((float) $verified->sum('amount')),
                'status' => $c->status,
                'age' => $c->created_at ? (int) $c->created_at->diffInDays(now()) : 0,
                '_link' => route('goal.cases.show', $c),
            ];
        })->values()->all();

        $byStatus = $cases->groupBy('status')->map->count();

        return [
            'kpis' => [
                ['label' => 'Total Cases', 'value' => number_format($cases->count()), 'tone' => 'blue'],
                ['label' => 'Disbursed', 'value' => number_format($cases->where('status', 'Disbursed')->count()), 'tone' => 'green'],
                ['label' => 'In Process', 'value' => number_format($cases->whereNotIn('status', ['Disbursed', 'Rejected', 'Closed'])->count()), 'tone' => 'amber'],
                ['label' => 'Rejected', 'value' => number_format($cases->where('status', 'Rejected')->count()), 'tone' => 'rose'],
            ],
            'charts' => [
                ['id' => 'caseSumStatus', 'type' => 'doughnut', 'title' => 'Case Status Distribution', 'labels' => $byStatus->keys()->all(), 'datasets' => [['data' => $byStatus->values()->all(), 'backgroundColor' => $this->palette($byStatus->count())]]],
            ],
            'columns' => [
                ['key' => 'case_ref', 'label' => 'Case Reference'],
                ['key' => 'lead_ref', 'label' => 'Lead Reference'],
                ['key' => 'customer', 'label' => 'Customer'],
                ['key' => 'mobile', 'label' => 'Mobile'],
                ['key' => 'product', 'label' => 'Product'],
                ['key' => 'requested', 'label' => 'Requested'],
                ['key' => 'branch', 'label' => 'Branch'],
                ['key' => 'owner', 'label' => 'Owner'],
                ['key' => 'lender', 'label' => 'Lender'],
                ['key' => 'created', 'label' => 'Created'],
                ['key' => 'sanction_amt', 'label' => 'Sanction Amt'],
                ['key' => 'disb_amt', 'label' => 'Disbursed Amt'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'age', 'label' => 'Case Age'],
            ],
            'rows' => $rows,
            'search_keys' => ['case_ref', 'customer', 'branch', 'status', 'lender'],
            'link_key' => '_link',
        ];
    }

    protected function disbursementSummary(User $user, array $scopeIds, array $range, array $filters): array
    {
        $disbs = GoalDisbursement::query()
            ->with(['loanCase.orgUnit', 'loanCase.owner:id,name', 'application'])
            ->whereBetween('disbursed_on', [$range['start'], $range['end']])
            ->whereHas('loanCase', fn ($q) => $this->applyCaseScope($q, $user, $scopeIds, $filters))
            ->latest('disbursed_on')
            ->limit(3000)
            ->get();

        $verified = $disbs->where('status', 'Verified');
        $prevStart = $range['start']->copy()->subMonthNoOverflow()->startOfMonth();
        $prevEnd = $range['start']->copy()->subMonthNoOverflow()->endOfMonth();
        $prevAmt = (float) GoalDisbursement::query()
            ->where('status', 'Verified')
            ->whereBetween('disbursed_on', [$prevStart, $prevEnd])
            ->whereHas('loanCase', fn ($q) => $this->applyCaseScope($q, $user, $scopeIds, $filters))
            ->sum('amount');

        $rows = $disbs->take(500)->map(function ($d) {
            return [
                'case_ref' => $d->loanCase?->case_no ?: '—',
                'customer' => $d->loanCase?->customer_name ?: '—',
                'lender' => $d->application?->lender_name ?: ($d->loanCase?->lender_name ?: '—'),
                'product' => $d->loanCase?->product ?: '—',
                'branch' => $d->loanCase?->orgUnit?->name ?: '—',
                'sanction' => $d->loanCase?->sanction_amount !== null ? $this->money((float) $d->loanCase->sanction_amount) : '—',
                'disbursed' => $this->money((float) $d->amount),
                'disb_date' => optional($d->disbursed_on)->format('d M Y') ?: '—',
                'reference' => $d->bank_reference ?: '—',
                'owner' => $d->loanCase?->owner?->name ?: '—',
                'status' => $d->status,
                '_link' => $d->loanCase ? route('goal.cases.show', $d->loanCase) : '#',
            ];
        })->values()->all();

        $byBranch = $verified->groupBy(fn ($d) => $d->loanCase?->orgUnit?->name ?: '—')->map(fn ($g) => (float) $g->sum('amount'));
        $byMonth = $verified->groupBy(fn ($d) => optional($d->disbursed_on)->format('M Y') ?: '—')->map(fn ($g) => (float) $g->sum('amount'));

        return [
            'kpis' => [
                ['label' => 'Disbursed Cases', 'value' => number_format($verified->pluck('goal_loan_case_id')->unique()->count()), 'tone' => 'green'],
                ['label' => 'Total Disbursed Amount', 'value' => $this->money((float) $verified->sum('amount')), 'tone' => 'teal'],
                ['label' => 'Current Period', 'value' => $this->money((float) $verified->sum('amount')), 'tone' => 'blue'],
                ['label' => 'Previous Month', 'value' => $this->money($prevAmt), 'tone' => 'orange'],
                ['label' => 'Average Disbursement', 'value' => $this->money($verified->count() ? (float) $verified->avg('amount') : 0), 'tone' => 'indigo'],
                ['label' => 'Pending Verification', 'value' => $this->money((float) $disbs->where('status', 'Pending Verification')->sum('amount')), 'tone' => 'amber'],
            ],
            'charts' => [
                ['id' => 'disbTrend', 'type' => 'line', 'title' => 'Disbursement Trend', 'labels' => $byMonth->keys()->all(), 'datasets' => [['label' => 'Amount', 'data' => $byMonth->values()->all(), 'borderColor' => '#0d9488', 'tension' => .35, 'fill' => false]]],
                ['id' => 'disbBranch', 'type' => 'bar', 'title' => 'Branch-wise Disbursement', 'labels' => $byBranch->keys()->take(8)->values()->all(), 'datasets' => [['label' => 'Amount', 'data' => $byBranch->values()->take(8)->values()->all(), 'backgroundColor' => '#0d9488']]],
            ],
            'columns' => [
                ['key' => 'case_ref', 'label' => 'Case Reference'],
                ['key' => 'customer', 'label' => 'Customer'],
                ['key' => 'lender', 'label' => 'Lender'],
                ['key' => 'product', 'label' => 'Product'],
                ['key' => 'branch', 'label' => 'Branch'],
                ['key' => 'sanction', 'label' => 'Sanction Amount'],
                ['key' => 'disbursed', 'label' => 'Disbursed Amount'],
                ['key' => 'disb_date', 'label' => 'Disbursement Date'],
                ['key' => 'reference', 'label' => 'Disbursement Reference'],
                ['key' => 'owner', 'label' => 'SM/JSM'],
                ['key' => 'status', 'label' => 'Status'],
            ],
            'rows' => $rows,
            'search_keys' => ['case_ref', 'customer', 'branch', 'lender', 'status'],
            'link_key' => '_link',
        ];
    }

    protected function revenueLedger(User $user, array $scopeIds, array $range, array $filters): array
    {
        $entries = GoalRevenueEntry::query()
            ->with(['loanCase.orgUnit', 'loanCase.owner:id,name', 'orgUnit:id,name'])
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->when($scopeIds !== [] && $scopeIds !== [-1], fn ($q) => $q->whereIn('goal_org_unit_id', $scopeIds))
            ->latest()
            ->limit(3000)
            ->get();

        $recognized = $entries->where('entry_type', 'recognized')->sum('amount');
        $adjust = $entries->where('entry_type', 'adjustment')->sum('amount');
        $reversal = abs((float) $entries->where('entry_type', 'reversal')->sum('amount'));
        $net = (float) $entries->sum('amount');

        $rows = $entries->take(500)->map(function ($e) {
            return [
                'ledger_id' => 'REV-'.$e->id,
                'date' => optional($e->created_at)->format('d M Y'),
                'case_ref' => $e->loanCase?->case_no ?: '—',
                'customer' => $e->loanCase?->customer_name ?: '—',
                'branch' => $e->orgUnit?->name ?: ($e->loanCase?->orgUnit?->name ?: '—'),
                'employee' => $e->loanCase?->owner?->name ?: '—',
                'type' => ucfirst((string) $e->entry_type),
                'gross' => $this->money((float) $e->amount),
                'note' => Str::limit((string) $e->note, 60) ?: '—',
                '_link' => $e->loanCase ? route('goal.cases.show', $e->loanCase) : '#',
            ];
        })->values()->all();

        $byBranch = $entries->groupBy(fn ($e) => $e->orgUnit?->name ?: '—')->map(fn ($g) => (float) $g->sum('amount'));

        return [
            'kpis' => [
                ['label' => 'Total Revenue (Net)', 'value' => $this->money($net), 'tone' => 'green'],
                ['label' => 'Recognized', 'value' => $this->money((float) $recognized), 'tone' => 'teal'],
                ['label' => 'Adjustments', 'value' => $this->money((float) $adjust), 'tone' => 'amber'],
                ['label' => 'Reversed', 'value' => $this->money($reversal), 'tone' => 'rose'],
                ['label' => 'Entries', 'value' => number_format($entries->count()), 'tone' => 'blue'],
            ],
            'charts' => [
                ['id' => 'revBranch', 'type' => 'bar', 'title' => 'Branch-wise Revenue', 'labels' => $byBranch->keys()->take(8)->values()->all(), 'datasets' => [['label' => 'Net Revenue', 'data' => $byBranch->values()->take(8)->values()->all(), 'backgroundColor' => '#ea580c']]],
                ['id' => 'revType', 'type' => 'doughnut', 'title' => 'Revenue by Entry Type', 'labels' => ['Recognized', 'Adjustment', 'Reversal'], 'datasets' => [['data' => [(float) $recognized, (float) $adjust, $reversal], 'backgroundColor' => ['#16a34a', '#f59e0b', '#e11d48']]]],
            ],
            'columns' => [
                ['key' => 'ledger_id', 'label' => 'Ledger ID'],
                ['key' => 'date', 'label' => 'Transaction Date'],
                ['key' => 'case_ref', 'label' => 'Case Reference'],
                ['key' => 'customer', 'label' => 'Customer'],
                ['key' => 'branch', 'label' => 'Branch'],
                ['key' => 'employee', 'label' => 'Employee'],
                ['key' => 'type', 'label' => 'Revenue Type'],
                ['key' => 'gross', 'label' => 'Amount'],
                ['key' => 'note', 'label' => 'Note'],
            ],
            'rows' => $rows,
            'search_keys' => ['ledger_id', 'case_ref', 'customer', 'branch', 'type'],
            'link_key' => '_link',
        ];
    }

    protected function payoutAgeing(User $user, array $scopeIds, array $range, array $filters): array
    {
        $payouts = GoalPayout::query()
            ->with(['loanCase.orgUnit', 'receipts'])
            ->whereHas('loanCase', fn ($q) => $this->applyCaseScope($q, $user, $scopeIds, $filters))
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->latest()
            ->limit(3000)
            ->get();

        $paid = $payouts->where('status', 'Paid');
        $pending = $payouts->whereIn('status', ['Eligible', 'Processing']);
        $hold = $payouts->where('status', 'On Hold');

        $buckets = ['0-7' => 0, '8-15' => 0, '16-30' => 0, '31-60' => 0, '60+' => 0];
        $rows = $payouts->take(500)->map(function ($p) use (&$buckets) {
            $due = $p->created_at?->copy()->addDays(7) ?? now();
            $age = $p->status === 'Paid' ? 0 : $due->diffInDays(now(), false);
            $age = max(0, (int) $age);
            if ($p->status !== 'Paid') {
                if ($age <= 7) {
                    $buckets['0-7']++;
                } elseif ($age <= 15) {
                    $buckets['8-15']++;
                } elseif ($age <= 30) {
                    $buckets['16-30']++;
                } elseif ($age <= 60) {
                    $buckets['31-60']++;
                } else {
                    $buckets['60+']++;
                }
            }
            $received = (float) $p->received_amount;
            $expected = (float) $p->expected_amount;

            return [
                'payout_id' => 'PO-'.$p->id,
                'case_ref' => $p->loanCase?->case_no ?: '—',
                'branch' => $p->loanCase?->orgUnit?->name ?: '—',
                'expected' => $this->money($expected),
                'received' => $this->money($received),
                'pending' => $this->money(max(0, $expected - $received)),
                'ageing' => $p->status === 'Paid' ? 0 : $age,
                'status' => $p->status,
                'hold_reason' => $p->exception_reason ?: '—',
                '_link' => $p->loanCase ? route('goal.cases.show', $p->loanCase) : '#',
            ];
        })->values()->all();

        return [
            'kpis' => [
                ['label' => 'Total Payout Amount', 'value' => $this->money((float) $payouts->sum('expected_amount')), 'tone' => 'blue'],
                ['label' => 'Payout Paid', 'value' => $this->money((float) $paid->sum('received_amount')), 'tone' => 'green'],
                ['label' => 'Payout Pending', 'value' => $this->money((float) $pending->sum(fn ($p) => max(0, (float) $p->expected_amount - (float) $p->received_amount))), 'tone' => 'amber'],
                ['label' => 'On Hold', 'value' => $this->money((float) $hold->sum('expected_amount')), 'tone' => 'rose'],
                ['label' => 'Overdue (16+ days)', 'value' => number_format($buckets['16-30'] + $buckets['31-60'] + $buckets['60+']), 'tone' => 'orange'],
            ],
            'charts' => [
                ['id' => 'payBuckets', 'type' => 'bar', 'title' => 'Payout Ageing Buckets', 'labels' => array_keys($buckets), 'datasets' => [['label' => 'Count', 'data' => array_values($buckets), 'backgroundColor' => ['#16a34a', '#84cc16', '#f59e0b', '#f97316', '#e11d48']]]],
                ['id' => 'payStatus', 'type' => 'doughnut', 'title' => 'Payout Status', 'labels' => ['Paid', 'Pending', 'Hold'], 'datasets' => [['data' => [$paid->count(), $pending->count(), $hold->count()], 'backgroundColor' => ['#16a34a', '#f59e0b', '#e11d48']]]],
            ],
            'columns' => [
                ['key' => 'payout_id', 'label' => 'Payout ID'],
                ['key' => 'case_ref', 'label' => 'Case Reference'],
                ['key' => 'branch', 'label' => 'Branch'],
                ['key' => 'expected', 'label' => 'Payout Amount'],
                ['key' => 'received', 'label' => 'Received'],
                ['key' => 'pending', 'label' => 'Pending'],
                ['key' => 'ageing', 'label' => 'Ageing Days'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'hold_reason', 'label' => 'Hold Reason'],
            ],
            'rows' => $rows,
            'search_keys' => ['payout_id', 'case_ref', 'branch', 'status'],
            'link_key' => '_link',
        ];
    }

    protected function targetVsAchievement(User $user, array $scopeIds, array $range, array $filters): array
    {
        $period = $range['end']->format('Y-m');
        $targets = GoalTarget::query()
            ->with(['user:id,name', 'orgUnit:id,name'])
            ->when($scopeIds === [-1], fn ($q) => $q->whereRaw('1 = 0'))
            ->when($scopeIds !== [] && $scopeIds !== [-1], function ($q) use ($scopeIds) {
                $q->where(function ($qq) use ($scopeIds) {
                    $qq->whereIn('goal_org_unit_id', $scopeIds)->orWhereNull('goal_org_unit_id');
                });
            })
            ->where(function ($q) use ($period, $range) {
                $q->where('period', 'like', $period.'%')
                    ->orWhereBetween('created_at', [$range['start'], $range['end']]);
            })
            ->limit(500)
            ->get();

        if ($targets->isEmpty()) {
            $targets = GoalTarget::query()
                ->with(['user:id,name', 'orgUnit:id,name'])
                ->when($scopeIds === [-1], fn ($q) => $q->whereRaw('1 = 0'))
                ->when($scopeIds !== [] && $scopeIds !== [-1], fn ($q) => $q->whereIn('goal_org_unit_id', $scopeIds))
                ->latest()
                ->limit(200)
                ->get();
        }

        $rows = $targets->map(function ($t) {
            $assigned = (float) $t->effectiveTarget();
            $actual = (float) $t->actual;
            $pct = $assigned > 0 ? round(($actual / $assigned) * 100, 1) : 0;

            return [
                'owner' => $t->user?->name ?: ($t->orgUnit?->name ?: '—'),
                'metric' => ucfirst((string) $t->metric),
                'branch' => $t->orgUnit?->name ?: '—',
                'period' => $t->period,
                'target' => $this->money($assigned),
                'achieved' => $this->money($actual),
                'remaining' => $this->money(max(0, $assigned - $actual)),
                'achievement' => $pct.'%',
                'status' => $t->status,
                '_pct' => $pct,
            ];
        })->values()->all();

        $totalTarget = $targets->sum(fn ($t) => (float) $t->effectiveTarget());
        $totalActual = (float) $targets->sum('actual');
        $above = collect($rows)->where('_pct', '>=', 100)->count();
        $below = collect($rows)->where('_pct', '<', 100)->count();

        return [
            'kpis' => [
                ['label' => 'Assigned Target', 'value' => $this->money($totalTarget), 'tone' => 'blue'],
                ['label' => 'Achieved', 'value' => $this->money($totalActual), 'tone' => 'green'],
                ['label' => 'Remaining', 'value' => $this->money(max(0, $totalTarget - $totalActual)), 'tone' => 'amber'],
                ['label' => 'Achievement %', 'value' => ($totalTarget > 0 ? round(($totalActual / $totalTarget) * 100, 1) : 0).'%', 'tone' => 'teal'],
                ['label' => 'Above Target', 'value' => number_format($above), 'tone' => 'green'],
                ['label' => 'Below Target', 'value' => number_format($below), 'tone' => 'rose'],
            ],
            'charts' => [
                ['id' => 'tgtBars', 'type' => 'bar', 'title' => 'Target vs Achievement', 'labels' => array_column(array_slice($rows, 0, 8), 'owner'), 'datasets' => [
                    ['label' => 'Target', 'data' => array_map(fn ($r) => (float) str_replace([',', '₹', ' '], '', $r['target']), array_slice($rows, 0, 8)), 'backgroundColor' => '#94a3b8'],
                    ['label' => 'Achieved', 'data' => array_map(fn ($r) => (float) str_replace([',', '₹', ' '], '', $r['achieved']), array_slice($rows, 0, 8)), 'backgroundColor' => '#0d9488'],
                ]],
            ],
            'columns' => [
                ['key' => 'owner', 'label' => 'Employee / Branch'],
                ['key' => 'metric', 'label' => 'Metric'],
                ['key' => 'branch', 'label' => 'Branch'],
                ['key' => 'period', 'label' => 'Period'],
                ['key' => 'target', 'label' => 'Target'],
                ['key' => 'achieved', 'label' => 'Achieved'],
                ['key' => 'remaining', 'label' => 'Remaining'],
                ['key' => 'achievement', 'label' => 'Achievement %'],
                ['key' => 'status', 'label' => 'Status'],
            ],
            'rows' => $rows,
            'search_keys' => ['owner', 'branch', 'metric', 'period'],
        ];
    }

    protected function hierarchyPerformance(User $user, array $scopeIds, array $range, array $filters): array
    {
        $units = GoalOrgUnit::query()->orderBy('name')->get(['id', 'name', 'type', 'parent_id']);
        $branches = $units->where('type', GoalOrgUnit::TYPE_BRANCH)
            ->when($scopeIds !== [] && $scopeIds !== [-1], fn ($c) => $c->whereIn('id', $scopeIds))
            ->values();

        $cases = $this->casesBase($user, $scopeIds, $filters)
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->with('disbursements')
            ->get(['id', 'goal_org_unit_id', 'status', 'owner_id']);
        $leads = $this->leadsBase($user, $scopeIds, $filters)
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->get(['id', 'goal_org_unit_id']);

        $rows = [];
        foreach ($branches as $b) {
            $cluster = $units->firstWhere('id', $b->parent_id);
            $region = $cluster ? $units->firstWhere('id', $cluster->parent_id) : null;
            $bCases = $cases->where('goal_org_unit_id', $b->id);
            $disbAmt = $bCases->sum(fn ($c) => (float) $c->disbursements->where('status', 'Verified')->sum('amount'));
            $rows[] = [
                'region' => $region?->name ?: '—',
                'cluster' => $cluster?->name ?: '—',
                'branch' => $b->name,
                'leads' => $leads->where('goal_org_unit_id', $b->id)->count(),
                'cases' => $bCases->count(),
                'disbursed' => $bCases->where('status', 'Disbursed')->count(),
                'disbursed_amount' => $this->money($disbAmt),
            ];
        }

        return [
            'kpis' => [
                ['label' => 'Regions', 'value' => number_format($units->where('type', GoalOrgUnit::TYPE_REGION)->count()), 'tone' => 'blue'],
                ['label' => 'Clusters', 'value' => number_format($units->where('type', GoalOrgUnit::TYPE_CLUSTER)->count()), 'tone' => 'indigo'],
                ['label' => 'Branches', 'value' => number_format($branches->count()), 'tone' => 'teal'],
                ['label' => 'Disbursement', 'value' => $this->money((float) $cases->sum(fn ($c) => (float) $c->disbursements->where('status', 'Verified')->sum('amount'))), 'tone' => 'green'],
            ],
            'charts' => [
                ['id' => 'hierBars', 'type' => 'bar', 'title' => 'Branch Cases', 'labels' => array_column(array_slice($rows, 0, 8), 'branch'), 'datasets' => [['label' => 'Cases', 'data' => array_column(array_slice($rows, 0, 8), 'cases'), 'backgroundColor' => '#0891b2']]],
            ],
            'columns' => [
                ['key' => 'region', 'label' => 'Region'],
                ['key' => 'cluster', 'label' => 'Cluster'],
                ['key' => 'branch', 'label' => 'Branch'],
                ['key' => 'leads', 'label' => 'Leads'],
                ['key' => 'cases', 'label' => 'Cases'],
                ['key' => 'disbursed', 'label' => 'Disbursed'],
                ['key' => 'disbursed_amount', 'label' => 'Disbursed Amount'],
            ],
            'rows' => $rows,
            'search_keys' => ['region', 'cluster', 'branch'],
        ];
    }

    protected function lenderPerformance(User $user, array $scopeIds, array $range, array $filters): array
    {
        $apps = GoalBankApplication::query()
            ->with(['loanCase.orgUnit', 'loanCase.disbursements', 'loanCase.sanctions'])
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->whereHas('loanCase', fn ($q) => $this->applyCaseScope($q, $user, $scopeIds, $filters))
            ->limit(5000)
            ->get();

        $grouped = $apps->groupBy(fn ($a) => $a->lender_name ?: 'Unknown');
        $rows = [];
        foreach ($grouped as $lender => $items) {
            $sanctioned = $items->whereIn('status', ['Sanctioned', 'Disbursed'])->count();
            $rejected = $items->where('status', 'Rejected')->count();
            $decided = $sanctioned + $rejected;
            $disbursed = $items->where('status', 'Disbursed')->count();
            $disbAmt = $items->sum(fn ($a) => (float) ($a->loanCase?->disbursements?->where('status', 'Verified')->sum('amount') ?? 0));
            $loginAmt = (float) $items->sum('requested_amount');
            $rows[] = [
                'lender' => $lender,
                'applications' => $items->count(),
                'login_amount' => $this->money($loginAmt),
                'sanctioned' => $sanctioned,
                'rejected' => $rejected,
                'disbursed' => $disbursed,
                'disbursed_amount' => $this->money($disbAmt),
                'approval_rate' => $decided > 0 ? round(($sanctioned / $decided) * 100, 1).'%' : '0%',
            ];
        }
        usort($rows, fn ($a, $b) => $b['applications'] <=> $a['applications']);

        return [
            'kpis' => [
                ['label' => 'Total Lenders', 'value' => number_format($grouped->count()), 'tone' => 'teal'],
                ['label' => 'Applications', 'value' => number_format($apps->count()), 'tone' => 'blue'],
                ['label' => 'Login Amount', 'value' => $this->money((float) $apps->sum('requested_amount')), 'tone' => 'indigo'],
                ['label' => 'Disbursed Amount', 'value' => $this->money((float) array_sum(array_map(fn ($r) => (float) str_replace([',', '₹', ' '], '', $r['disbursed_amount']), $rows))), 'tone' => 'green'],
            ],
            'charts' => [
                ['id' => 'lenderApps', 'type' => 'bar', 'title' => 'Lender-wise Applications', 'labels' => array_column(array_slice($rows, 0, 8), 'lender'), 'datasets' => [['label' => 'Applications', 'data' => array_column(array_slice($rows, 0, 8), 'applications'), 'backgroundColor' => '#475569']]],
            ],
            'columns' => [
                ['key' => 'lender', 'label' => 'Lender Name'],
                ['key' => 'applications', 'label' => 'Applications'],
                ['key' => 'login_amount', 'label' => 'Login Amount'],
                ['key' => 'sanctioned', 'label' => 'Sanctioned'],
                ['key' => 'rejected', 'label' => 'Rejected'],
                ['key' => 'disbursed', 'label' => 'Disbursed'],
                ['key' => 'disbursed_amount', 'label' => 'Disbursed Amount'],
                ['key' => 'approval_rate', 'label' => 'Approval Rate'],
            ],
            'rows' => $rows,
            'search_keys' => ['lender'],
        ];
    }

    /* ---------- helpers ---------- */

    protected function exceptionRow(string $type, ?string $ref, ?string $customer, ?string $branch, $created, $due, string $priority, string $link): array
    {
        $createdAt = $created ? Carbon::parse($created) : null;
        $dueAt = $due ? Carbon::parse($due) : null;

        return [
            'type' => $type,
            'reference' => $ref ?: '—',
            'customer' => $customer ?: '—',
            'branch' => $branch ?: '—',
            'created' => $createdAt?->format('d M Y') ?: '—',
            'due' => $dueAt?->format('d M Y') ?: '—',
            'pending_days' => $createdAt ? (int) $createdAt->diffInDays(now()) : 0,
            'priority' => $priority,
            'status' => 'Open',
            '_link' => $link,
        ];
    }

    protected function scopeIds(User $user, array $filters): array
    {
        return $this->analytics->resolveScopeIds($user, $filters);
    }

    protected function dateRange(array $filters): array
    {
        return $this->analytics->resolveDateRange($filters);
    }

    protected function analyticsScopeLabel(User $user, array $scopeIds): string
    {
        $roleKey = app(GoalDashboardService::class)->resolveRoleKey($user);

        return $this->analytics->scopeLabel($user, $scopeIds, $roleKey);
    }

    protected function leadsBase(User $user, array $scopeIds, array $filters)
    {
        $q = GoalLoanLead::query();
        if ($scopeIds === [-1]) {
            return $q->whereRaw('1 = 0');
        }
        if ($scopeIds !== []) {
            $q->whereIn('goal_org_unit_id', $scopeIds);
        }
        if (! empty($filters['product'])) {
            $q->where('product', $filters['product']);
        }
        if (! empty($filters['branch'])) {
            $q->where('goal_org_unit_id', (int) $filters['branch']);
        }
        if (! empty($filters['owner'])) {
            $q->where('assigned_to', (int) $filters['owner']);
        }

        return $q;
    }

    protected function casesBase(User $user, array $scopeIds, array $filters)
    {
        $q = GoalLoanCase::query();
        $this->applyCaseScope($q, $user, $scopeIds, $filters);

        return $q;
    }

    protected function applyCaseScope($q, User $user, array $scopeIds, array $filters): void
    {
        if ($scopeIds === [-1]) {
            $q->whereRaw('1 = 0');

            return;
        }
        if ($scopeIds !== []) {
            $q->whereIn('goal_org_unit_id', $scopeIds);
        }
        if (! empty($filters['product'])) {
            $q->where('product', $filters['product']);
        }
        if (! empty($filters['branch'])) {
            $q->where('goal_org_unit_id', (int) $filters['branch']);
        }
        if (! empty($filters['owner'])) {
            $q->where('owner_id', (int) $filters['owner']);
        }
    }

    protected function money(float $n): string
    {
        if (abs($n) >= 10000000) {
            return '₹ '.number_format($n / 10000000, 2).' Cr';
        }
        if (abs($n) >= 100000) {
            return '₹ '.number_format($n / 100000, 2).' Lac';
        }

        return '₹ '.number_format($n, 0);
    }

    protected function palette(int $n): array
    {
        $base = ['#0d9488', '#2563eb', '#7c3aed', '#ea580c', '#e11d48', '#0891b2', '#16a34a', '#64748b', '#db2777', '#4f46e5'];
        if ($n <= 0) {
            return ['#e2e8f0'];
        }

        return array_values(array_slice(array_merge($base, $base), 0, $n));
    }
}
