<?php

namespace App\Services\Goal;

use App\Models\GoalLoanCase;
use App\Models\GoalLoanLead;
use App\Models\GoalOrgUnit;
use App\Models\GoalPayout;
use App\Models\GoalPmsItem;
use App\Models\GoalTarget;
use App\Models\GoalVrmCallLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GoalDashboardService
{
    public const ROLE_KEYS = [
        0 => 'md',
        4 => 'md',
        5 => 'ceo',
        6 => 'rd',
        7 => 'cluster',
        8 => 'bm',
        9 => 'vrm',
        10 => 'jsm',
        11 => 'sm',
        12 => 'ssm',
        13 => 'pms',
        14 => 'accounts',
    ];

    public const ROLE_LABELS = [
        'md' => 'Managing Director',
        'ceo' => 'CEO',
        'rd' => 'Regional Director',
        'cluster' => 'Cluster Head',
        'bm' => 'Branch Manager',
        'vrm' => 'VRM',
        'sm' => 'Sales Manager',
        'jsm' => 'Junior Sales Manager',
        'ssm' => 'Senior Sales Manager',
        'pms' => 'PMS',
        'accounts' => 'Accounts',
    ];

    public static function userUsesGoalDashboard(User $user): bool
    {
        if (in_array((int) ($user->crm_role_type ?? -1), array_keys(self::ROLE_KEYS), true)) {
            return true;
        }

        $guards = [
            'vrm_calling_guard', 'view_leads_guard', 'create_lead_guard', 'channel_lead_guard',
            'bm_allocation_guard', 'view_loan_cases_guard', 'pms_verification_guard',
            'accounts_queue_guard', 'payout_processing_guard', 'view_targets_guard',
            'branch_mis_guard', 'manage_org_units_guard', 'revenue_ledger_guard',
        ];

        foreach ($guards as $guard) {
            if ($user->hasCrmPermission($guard)) {
                return true;
            }
        }

        return false;
    }

    public function build(User $user, string $period = 'this_month', string $interval = 'weekly'): array
    {
        $roleKey = $this->resolveRoleKey($user);
        $scopeIds = $user->goalScopedOrgUnitIds();
        $orgUnit = $user->orgUnit;
        $periodMeta = $this->resolvePeriod($period);
        $rangeStart = $periodMeta['start'];
        $rangeEnd = $periodMeta['end'];
        $interval = in_array($interval, ['weekly', 'monthly'], true) ? $interval : 'weekly';

        $leadsQ = GoalLoanLead::query();
        $casesQ = GoalLoanCase::query();
        $isCompanyWide = in_array($roleKey, ['md', 'ceo'], true) || $user->isSuperAdmin();

        if ($scopeIds !== []) {
            $leadsQ->whereIn('goal_org_unit_id', $scopeIds);
            $casesQ->whereIn('goal_org_unit_id', $scopeIds);
        } elseif (!$isCompanyWide) {
            // No org scope assigned — never fall open to company-wide data
            $leadsQ->whereRaw('1 = 0');
            $casesQ->whereRaw('1 = 0');
        }

        // Sales roles: only their assigned pipeline
        if (in_array($roleKey, ['sm', 'jsm', 'ssm'], true)) {
            $casesQ->where('owner_id', $user->id);
        }
        // VRM: only leads they created
        if ($roleKey === 'vrm') {
            $leadsQ->where('created_by', $user->id);
        }
        // BM: recent-leads focus stays branch-scoped; allocation queue uses pending status below
        if ($roleKey === 'bm') {
            // no extra filter — branch scope already applied
        }

        $pendingAllocation = (clone $leadsQ)->where('status', 'Pending BM Allocation')->count();
        $immediateLeads = (clone $leadsQ)->where('lead_type', 'immediate')
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])->count();
        $futureLeads = (clone $leadsQ)->where('lead_type', 'future')
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])->count();
        $totalLeadsPeriod = (clone $leadsQ)->whereBetween('created_at', [$rangeStart, $rangeEnd])->count();

        $activeCases = (clone $casesQ)->whereNotIn('status', ['Disbursed', 'Closed', 'Rejected', 'Cancelled'])->count();
        $disbursedCases = (clone $casesQ)->where('status', 'Disbursed')
            ->whereBetween('updated_at', [$rangeStart, $rangeEnd])->count();
        $pipelineCases = $activeCases;

        $pmsPending = GoalPmsItem::query()
            ->when($scopeIds !== [], function ($q) use ($scopeIds) {
                $q->whereHas('loanCase', fn ($c) => $c->whereIn('goal_org_unit_id', $scopeIds));
            })
            ->whereIn('status', ['Pending', 'pending', 'PD-01', 'Awaiting Verification'])
            ->count();

        $payoutsQ = GoalPayout::query()
            ->when($scopeIds !== [], function ($q) use ($scopeIds) {
                $q->whereHas('loanCase', fn ($c) => $c->whereIn('goal_org_unit_id', $scopeIds));
            });
        $payoutHold = (clone $payoutsQ)->where(function ($q) {
            $q->where('status', 'like', '%Hold%')->orWhere('status', 'On Hold')->orWhere('status', 'Hold');
        })->count();
        $payoutEligible = (clone $payoutsQ)->where(function ($q) {
            $q->where('status', 'like', '%Eligible%')->orWhere('status', 'Eligible')->orWhere('status', 'Ready');
        })->count();
        $accountsAwaiting = GoalPayout::query()
            ->when($scopeIds !== [], function ($q) use ($scopeIds) {
                $q->whereHas('loanCase', fn ($c) => $c->whereIn('goal_org_unit_id', $scopeIds));
            })
            ->where(function ($q) {
                $q->where('status', 'like', '%Report%')
                    ->orWhere('status', 'like', '%Awaiting%')
                    ->orWhere('status', 'On Hold');
            })
            ->count();

        $callsToday = GoalVrmCallLog::query()
            ->when($roleKey === 'vrm', fn ($q) => $q->where('user_id', $user->id))
            ->whereDate('called_at', today())
            ->count();
        $callbacksOverdue = GoalVrmCallLog::query()
            ->when($roleKey === 'vrm', fn ($q) => $q->where('user_id', $user->id))
            ->where('disposition', 'Callback')
            ->whereNotNull('callback_at')
            ->where('callback_at', '<', now())
            ->count();
        $dailyCallTarget = 200;

        $target = $this->resolveTarget($user, $scopeIds, $periodMeta);
        $targetEffective = $target ? $target->effectiveTarget() : 0.0;
        $targetActual = $target ? (float) $target->actual : 0.0;
        $targetUnit = 'money';

        // VRM target is daily call quota, not revenue
        if ($roleKey === 'vrm') {
            $targetEffective = (float) $dailyCallTarget;
            $targetActual = (float) $callsToday;
            $targetUnit = 'calls';
        }

        $targetPct = $targetEffective > 0 ? (int) round(($targetActual / $targetEffective) * 100) : 0;
        $targetRemaining = max(0, $targetEffective - $targetActual);

        $leadsBySource = (clone $leadsQ)
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->selectRaw("COALESCE(NULLIF(source,''), 'manual') as src, COUNT(*) as cnt")
            ->groupBy('src')
            ->pluck('cnt', 'src')
            ->toArray();

        $casesByStatus = (clone $casesQ)
            ->selectRaw("COALESCE(NULLIF(status,''), 'Unknown') as st, COUNT(*) as cnt")
            ->groupBy('st')
            ->orderByDesc('cnt')
            ->pluck('cnt', 'st')
            ->toArray();

        $sections = $this->roleSections($roleKey);
        $trend = $this->buildTrend($scopeIds, $roleKey, $user, $rangeStart, $rangeEnd, $interval);
        $secondaryDist = $this->buildSecondaryDistribution($roleKey, $user, $scopeIds, $casesByStatus, $leadsBySource);

        $scopeLabel = $orgUnit?->name
            ?? (in_array($roleKey, ['md', 'ceo'], true) ? 'Company-wide' : 'My scope');

        $payload = [
            'role_key' => $roleKey,
            'role_label' => self::ROLE_LABELS[$roleKey] ?? 'User',
            'scope_label' => $scopeLabel,
            'badge' => 'View: ' . (self::ROLE_LABELS[$roleKey] ?? 'User') . ' · ' . $scopeLabel,
            'period' => $period,
            'period_label' => $periodMeta['label'],
            'interval' => $interval,
            'interval_label' => $interval === 'monthly' ? 'Monthly' : 'Weekly',
            'period_options' => [
                'today' => 'Today',
                'this_month' => 'This Month',
                'last_month' => 'Last Month',
                'this_year' => 'This Year',
            ],
            'sections' => $sections,
            'metrics' => [
                'pending_allocation' => $pendingAllocation,
                'active_cases' => $activeCases,
                'pms_pending' => $pmsPending,
                'payout_hold' => $payoutHold,
                'payout_eligible' => $payoutEligible,
                'immediate_leads' => $immediateLeads,
                'future_leads' => $futureLeads,
                'total_leads_period' => $totalLeadsPeriod,
                'calls_today' => $callsToday,
                'callbacks_overdue' => $callbacksOverdue,
                'daily_call_target' => $dailyCallTarget,
                'disbursed_cases' => $disbursedCases,
                'pipeline_cases' => $pipelineCases,
                'accounts_awaiting' => $accountsAwaiting,
                'my_active_cases' => $activeCases,
            ],
            'target' => [
                'title' => $this->targetTitle($roleKey),
                'subtitle' => $periodMeta['label'] . ' · ' . $scopeLabel,
                'effective' => $targetEffective,
                'actual' => $targetActual,
                'percent' => $targetPct,
                'remaining' => $targetRemaining,
                'unit' => $targetUnit,
            ],
            'leads_by_source' => $leadsBySource,
            'cases_by_status' => $casesByStatus,
            'trend' => $trend,
            'kpis' => [],
            'exceptions' => [],
            'actions' => [],
            'summary' => '',
        ];

        $payload = $this->applyRolePresentation($payload, $roleKey, $dailyCallTarget);
        $payload['target']['snap'] = $this->buildTargetSnap($payload, $roleKey);
        $payload['attention'] = $this->buildAttentionCards($payload);
        $payload['recent_cases'] = $sections['recent_cases']
            ? $this->buildRecentCases($this->scopedRecentCasesQuery($casesQ, $roleKey))
            : [];
        $payload['recent_leads'] = $sections['recent_leads']
            ? $this->buildRecentLeads($this->scopedRecentLeadsQuery($leadsQ, $roleKey))
            : [];
        $payload['team_targets'] = $sections['team_targets']
            ? $this->buildTeamTargets($scopeIds, $periodMeta)
            : [];
        $payload['analytics'] = $this->buildAnalyticsCharts($payload, $secondaryDist, $sections);

        return $payload;
    }

    protected function roleSections(string $roleKey): array
    {
        return match ($roleKey) {
            'vrm' => [
                'team_targets' => false,
                'recent_cases' => false,
                'recent_leads' => true,
                'show_source_chart' => true,
                'show_secondary_chart' => true,
                'secondary_key' => 'dispositions',
                'trend_mode' => 'leads_calls',
            ],
            'pms' => [
                'team_targets' => false,
                'recent_cases' => true,
                'recent_leads' => false,
                'show_source_chart' => false,
                'show_secondary_chart' => true,
                'secondary_key' => 'pms',
                'trend_mode' => 'cases_only',
            ],
            'accounts' => [
                'team_targets' => false,
                'recent_cases' => true,
                'recent_leads' => false,
                'show_source_chart' => false,
                'show_secondary_chart' => true,
                'secondary_key' => 'payouts',
                'trend_mode' => 'cases_only',
            ],
            'sm', 'jsm', 'ssm' => [
                'team_targets' => false,
                'recent_cases' => true,
                'recent_leads' => true,
                'show_source_chart' => true,
                'show_secondary_chart' => true,
                'secondary_key' => 'cases',
                'trend_mode' => 'leads_cases',
            ],
            'md', 'ceo' => [
                'team_targets' => true,
                'recent_cases' => true,
                'recent_leads' => true,
                'show_source_chart' => true,
                'show_secondary_chart' => true,
                'secondary_key' => 'cases',
                'trend_mode' => 'leads_cases',
            ],
            'rd', 'cluster' => [
                'team_targets' => true,
                'recent_cases' => true,
                'recent_leads' => true,
                'show_source_chart' => true,
                'show_secondary_chart' => true,
                'secondary_key' => 'cases',
                'trend_mode' => 'leads_cases',
            ],
            default => [ // bm
                'team_targets' => true,
                'recent_cases' => true,
                'recent_leads' => true,
                'show_source_chart' => true,
                'show_secondary_chart' => true,
                'secondary_key' => 'cases',
                'trend_mode' => 'leads_cases',
            ],
        };
    }

    protected function scopedRecentCasesQuery($casesQ, string $roleKey)
    {
        $q = clone $casesQ;
        if ($roleKey === 'pms') {
            $q->whereHas('pmsItems', function ($p) {
                $p->whereIn('status', ['Pending', 'pending', 'PD-01', 'Awaiting Verification']);
            });
        }
        if ($roleKey === 'accounts') {
            $q->whereHas('payouts', function ($p) {
                $p->where(function ($w) {
                    $w->where('status', 'like', '%Hold%')
                        ->orWhere('status', 'like', '%Eligible%')
                        ->orWhere('status', 'like', '%Awaiting%')
                        ->orWhere('status', 'like', '%Report%');
                });
            });
        }

        return $q;
    }

    protected function scopedRecentLeadsQuery($leadsQ, string $roleKey)
    {
        $q = clone $leadsQ;
        if ($roleKey === 'bm') {
            $q->where('status', 'Pending BM Allocation');
        }

        return $q;
    }

    protected function buildTargetSnap(array $payload, string $roleKey): array
    {
        $m = $payload['metrics'];
        $t = $payload['target'];

        return match ($roleKey) {
            'vrm' => [
                ['tone' => 'g', 'icon' => 'bi-telephone', 'n' => (string) $m['calls_today'], 'l' => 'Calls today'],
                ['tone' => 'b', 'icon' => 'bi-exclamation-triangle', 'n' => (string) $m['callbacks_overdue'], 'l' => 'Overdue'],
                ['tone' => 'p', 'icon' => 'bi-bullseye', 'n' => (string) $m['daily_call_target'], 'l' => 'Daily target'],
            ],
            'pms' => [
                ['tone' => 'g', 'icon' => 'bi-check-lg', 'n' => (string) $m['disbursed_cases'], 'l' => 'Disbursed'],
                ['tone' => 'b', 'icon' => 'bi-shield-check', 'n' => (string) $m['pms_pending'], 'l' => 'Pending'],
                ['tone' => 'p', 'icon' => 'bi-briefcase', 'n' => (string) $m['pipeline_cases'], 'l' => 'Pipeline'],
            ],
            'accounts' => [
                ['tone' => 'g', 'icon' => 'bi-check-lg', 'n' => (string) $m['payout_eligible'], 'l' => 'Eligible'],
                ['tone' => 'b', 'icon' => 'bi-pause-circle', 'n' => (string) $m['payout_hold'], 'l' => 'On hold'],
                ['tone' => 'p', 'icon' => 'bi-journal-check', 'n' => (string) $m['accounts_awaiting'], 'l' => 'Awaiting'],
            ],
            default => [
                ['tone' => 'g', 'icon' => 'bi-check-lg', 'n' => (string) $m['disbursed_cases'], 'l' => 'Disbursed'],
                ['tone' => 'b', 'icon' => 'bi-clock', 'n' => (string) $m['pipeline_cases'], 'l' => 'Pipeline'],
                ['tone' => 'p', 'icon' => 'bi-bullseye', 'n' => $t['unit'] === 'money'
                    ? ('₹ ' . number_format((float) $t['effective'], 0))
                    : (string) (int) $t['effective'], 'l' => 'Target'],
            ],
        };
    }

    protected function buildSecondaryDistribution(
        string $roleKey,
        User $user,
        array $scopeIds,
        array $casesByStatus,
        array $leadsBySource
    ): array {
        if ($roleKey === 'vrm') {
            $items = GoalVrmCallLog::query()
                ->where('user_id', $user->id)
                ->whereDate('called_at', '>=', now()->startOfMonth())
                ->selectRaw("COALESCE(NULLIF(disposition,''), 'Unknown') as d, COUNT(*) as cnt")
                ->groupBy('d')
                ->orderByDesc('cnt')
                ->pluck('cnt', 'd')
                ->toArray();

            return [
                'title' => 'Call Dispositions',
                'center_label' => 'Calls',
                'items' => $items,
            ];
        }

        if ($roleKey === 'pms') {
            $items = GoalPmsItem::query()
                ->when($scopeIds !== [], function ($q) use ($scopeIds) {
                    $q->whereHas('loanCase', fn ($c) => $c->whereIn('goal_org_unit_id', $scopeIds));
                })
                ->selectRaw("COALESCE(NULLIF(status,''), 'Unknown') as st, COUNT(*) as cnt")
                ->groupBy('st')
                ->orderByDesc('cnt')
                ->pluck('cnt', 'st')
                ->toArray();

            return [
                'title' => 'PMS Queue Status',
                'center_label' => 'Items',
                'items' => $items,
            ];
        }

        if ($roleKey === 'accounts') {
            $items = GoalPayout::query()
                ->when($scopeIds !== [], function ($q) use ($scopeIds) {
                    $q->whereHas('loanCase', fn ($c) => $c->whereIn('goal_org_unit_id', $scopeIds));
                })
                ->selectRaw("COALESCE(NULLIF(status,''), 'Unknown') as st, COUNT(*) as cnt")
                ->groupBy('st')
                ->orderByDesc('cnt')
                ->pluck('cnt', 'st')
                ->toArray();

            return [
                'title' => 'Payout Status',
                'center_label' => 'Payouts',
                'items' => $items,
            ];
        }

        return [
            'title' => 'Case Status',
            'center_label' => 'Cases',
            'items' => $casesByStatus,
        ];
    }

    protected function buildAnalyticsCharts(array $payload, array $secondaryDist, array $sections): array
    {
        $trend = $payload['trend'];
        $mode = $sections['trend_mode'] ?? 'leads_cases';

        $intervalWord = ($payload['interval'] ?? 'weekly') === 'monthly' ? 'Monthly' : 'Weekly';
        if ($mode === 'leads_calls') {
            $trendBlock = [
                'title' => 'Leads & Calls Trend',
                'subtitle' => $intervalWord . ' movement · ' . $payload['period_label'],
                'labels' => $trend['labels'],
                'series' => [
                    ['label' => 'Leads', 'data' => $trend['leads'], 'color' => '#22c55e'],
                    ['label' => 'Calls', 'data' => $trend['calls'] ?? $trend['cases'], 'color' => '#a855f7'],
                ],
            ];
        } elseif ($mode === 'cases_only') {
            $trendBlock = [
                'title' => 'Cases Trend',
                'subtitle' => $intervalWord . ' movement · ' . $payload['period_label'],
                'labels' => $trend['labels'],
                'series' => [
                    ['label' => 'Loan Cases', 'data' => $trend['cases'], 'color' => '#3b82f6'],
                ],
            ];
        } else {
            $trendBlock = [
                'title' => 'Leads & Cases Trend',
                'subtitle' => $intervalWord . ' movement · ' . $payload['period_label'],
                'labels' => $trend['labels'],
                'series' => [
                    ['label' => 'Leads', 'data' => $trend['leads'], 'color' => '#22c55e'],
                    ['label' => 'Loan Cases', 'data' => $trend['cases'], 'color' => '#3b82f6'],
                ],
            ];
        }

        $sourceItems = $payload['leads_by_source'] ?? [];
        $secondaryItems = $secondaryDist['items'] ?? [];

        return [
            'trend' => $trendBlock,
            'distribution_a' => [
                'show' => (bool) ($sections['show_source_chart'] ?? true),
                'title' => 'Leads by Source',
                'center_value' => (int) ($payload['metrics']['total_leads_period'] ?? array_sum($sourceItems)),
                'center_label' => 'Total',
                'items' => $sourceItems,
            ],
            'distribution_b' => [
                'show' => (bool) ($sections['show_secondary_chart'] ?? true),
                'title' => $secondaryDist['title'] ?? 'Case Status',
                'center_value' => (int) array_sum($secondaryItems),
                'center_label' => $secondaryDist['center_label'] ?? 'Total',
                'items' => $secondaryItems,
            ],
        ];
    }

    protected function buildAttentionCards(array $payload): array
    {
        $cards = [];
        foreach ($payload['exceptions'] ?? [] as $ex) {
            $priority = $ex[2] ?? 'Low';
            $cards[] = [
                'label' => $ex[0] ?? 'Item',
                'count' => $ex[1] ?? '0',
                'priority' => $priority,
                'href' => $ex[3] ?? route('goal.analytics'),
                'tone' => $priority === 'High' ? 'danger' : ($priority === 'Medium' ? 'warn' : 'muted'),
            ];
        }

        return array_slice($cards, 0, 3);
    }

    protected function buildRecentCases($casesQ): array
    {
        return (clone $casesQ)
            ->with(['owner:id,name'])
            ->latest('updated_at')
            ->limit(5)
            ->get(['id', 'case_no', 'customer_name', 'product', 'lender_name', 'status', 'owner_id', 'loan_amount', 'updated_at'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'case_no' => $c->case_no ?: ('CS-' . $c->id),
                'customer' => $c->customer_name ?: '—',
                'product' => $c->product ?: '—',
                'bank' => $c->lender_name ?: '—',
                'status' => $c->status ?: 'Unknown',
                'owner' => $c->owner?->name,
                'href' => route('goal.cases.show', $c),
            ])
            ->all();
    }

    protected function buildRecentLeads($leadsQ): array
    {
        return (clone $leadsQ)
            ->with(['orgUnit:id,name'])
            ->latest('created_at')
            ->limit(6)
            ->get(['id', 'ref_no', 'customer_name', 'source', 'lead_type', 'status', 'product', 'goal_org_unit_id', 'created_at'])
            ->map(fn ($l) => [
                'id' => $l->id,
                'ref' => $l->ref_no ?: ('LD-' . str_pad((string) $l->id, 4, '0', STR_PAD_LEFT)),
                'customer' => $l->customer_name ?: '—',
                'source' => $l->source ?: 'manual',
                'type' => $l->lead_type ?: '—',
                'product' => $l->product ?: '—',
                'branch' => $l->orgUnit?->name ?: '—',
                'received_on' => optional($l->created_at)->format('d M Y, h:i A') ?: '—',
                'status' => $l->status ?: '—',
                'href' => route('goal.leads.show', $l),
            ])
            ->all();
    }

    protected function buildTeamTargets(array $scopeIds, array $periodMeta): array
    {
        $periodKey = $periodMeta['start']->format('Y-m');
        $q = GoalTarget::query()
            ->with(['user:id,name', 'orgUnit:id,name'])
            ->where('period', 'like', $periodKey . '%')
            ->whereNotNull('user_id')
            ->orderByDesc('actual')
            ->limit(5);

        if ($scopeIds !== []) {
            $q->whereIn('goal_org_unit_id', $scopeIds);
        }

        return $q->get()->map(function (GoalTarget $t) {
            $effective = $t->effectiveTarget();
            $actual = (float) $t->actual;
            $pct = $effective > 0 ? (int) round(($actual / $effective) * 100) : 0;

            return [
                'name' => $t->user?->name ?? 'User',
                'unit' => $t->orgUnit?->name ?? '—',
                'effective' => $effective,
                'actual' => $actual,
                'percent' => $pct,
            ];
        })->all();
    }

    protected function applyRolePresentation(array $d, string $roleKey, int $dailyCallTarget): array
    {
        $m = $d['metrics'];
        $fmtMoney = fn ($n) => '₹ ' . number_format((float) $n, 0);

        switch ($roleKey) {
            case 'vrm':
                $d['summary'] = "{$m['calls_today']} of {$dailyCallTarget} calls today · {$m['callbacks_overdue']} overdue callback(s).";
                $d['kpis'] = [
                    ['label' => 'Calls today', 'value' => (string) $m['calls_today'], 'trend' => round(($m['calls_today'] / max(1, $dailyCallTarget)) * 100) . '% of ' . $dailyCallTarget, 'link' => route('goal.vrm.index'), 'link_text' => 'Calling →', 'icon' => 'bi-telephone', 'tone' => 'magenta'],
                    ['label' => 'Immediate leads', 'value' => (string) $m['immediate_leads'], 'trend' => 'In period', 'link' => route('goal.leads.index', ['lead_type' => 'immediate']), 'link_text' => 'Leads →', 'icon' => 'bi-lightning', 'tone' => 'blue'],
                    ['label' => 'Future prospects', 'value' => (string) $m['future_leads'], 'trend' => 'Follow-ups set', 'link' => route('goal.leads.index', ['lead_type' => 'future']), 'link_text' => 'Leads →', 'icon' => 'bi-calendar-event', 'tone' => 'red'],
                    ['label' => 'Callbacks overdue', 'value' => (string) $m['callbacks_overdue'], 'trend' => 'Needs action', 'link' => route('goal.vrm.index', ['disposition' => 'Callback']), 'link_text' => 'Callbacks →', 'icon' => 'bi-exclamation-triangle', 'tone' => 'green'],
                ];
                $d['exceptions'] = [
                    ['Overdue callbacks', (string) $m['callbacks_overdue'], $m['callbacks_overdue'] > 0 ? 'High' : 'Low', route('goal.vrm.index')],
                    ['Daily target gap', (string) max(0, $dailyCallTarget - $m['calls_today']) . ' calls', 'Medium', route('goal.vrm.index')],
                    ['Pending BM allocation', (string) $m['pending_allocation'], 'Medium', route('goal.allocation.index')],
                ];
                $d['actions'] = [
                    [route('goal.vrm.index', ['log_call' => 1]), 'qa-blue', 'bi-telephone-outbound', 'Log Call'],
                    [route('goal.leads.index', ['create' => 1]), 'qa-purple', 'bi-person-plus', 'Create Lead'],
                    [route('goal.leads.index'), 'qa-green', 'bi-people', 'Lead Register'],
                    [route('goal.vrm.index'), 'qa-orange', 'bi-bar-chart', 'Call Logs'],
                ];
                break;

            case 'pms':
                $d['summary'] = "{$m['pms_pending']} pending verification · keep SLA within 48 hrs.";
                $d['kpis'] = [
                    ['label' => 'Pending verification', 'value' => (string) $m['pms_pending'], 'trend' => 'PMS queue', 'link' => route('goal.pms.index'), 'link_text' => 'PMS Queue →', 'icon' => 'bi-shield-check', 'tone' => 'magenta'],
                    ['label' => 'Active cases', 'value' => (string) $m['active_cases'], 'trend' => 'In scope', 'link' => route('goal.cases.index'), 'link_text' => 'Cases →', 'icon' => 'bi-briefcase', 'tone' => 'blue'],
                    ['label' => 'Disbursed (period)', 'value' => (string) $m['disbursed_cases'], 'trend' => $d['period_label'], 'link' => route('goal.cases.index'), 'link_text' => 'Cases →', 'icon' => 'bi-check2-circle', 'tone' => 'green'],
                    ['label' => 'Accounts awaiting', 'value' => (string) $m['accounts_awaiting'], 'trend' => 'Report / hold', 'link' => route('goal.accounts.index'), 'link_text' => 'Accounts →', 'icon' => 'bi-journal-check', 'tone' => 'red'],
                ];
                $d['exceptions'] = [
                    ['Pending PMS verification', (string) $m['pms_pending'], $m['pms_pending'] > 0 ? 'High' : 'Low', route('goal.pms.index')],
                    ['Accounts still awaiting', (string) $m['accounts_awaiting'], 'Medium', route('goal.accounts.index')],
                    ['Active pipeline cases', (string) $m['pipeline_cases'], 'Low', route('goal.cases.index')],
                ];
                $d['actions'] = [
                    [route('goal.pms.index'), 'qa-purple', 'bi-shield-check', 'PMS Queue'],
                    [route('goal.cases.index'), 'qa-blue', 'bi-briefcase', 'Loan Cases'],
                    [route('goal.accounts.index'), 'qa-orange', 'bi-journal-check', 'Accounts'],
                    [route('goal.branch-mis'), 'qa-green', 'bi-bar-chart', 'MIS'],
                ];
                break;

            case 'accounts':
                $d['summary'] = "{$m['accounts_awaiting']} awaiting report · {$m['payout_eligible']} payout eligible · {$m['payout_hold']} on hold.";
                $d['kpis'] = [
                    ['label' => 'Awaiting report no.', 'value' => (string) $m['accounts_awaiting'], 'trend' => 'On hold / awaiting', 'link' => route('goal.accounts.index'), 'link_text' => 'Accounts →', 'icon' => 'bi-journal-check', 'tone' => 'magenta'],
                    ['label' => 'Payout eligible', 'value' => (string) $m['payout_eligible'], 'trend' => 'Ready', 'link' => route('goal.payouts.index'), 'link_text' => 'Payouts →', 'icon' => 'bi-check-circle', 'tone' => 'green'],
                    ['label' => 'On hold', 'value' => (string) $m['payout_hold'], 'trend' => 'Need PMS + RPT', 'link' => route('goal.payouts.index'), 'link_text' => 'Board →', 'icon' => 'bi-pause-circle', 'tone' => 'red'],
                    ['label' => 'Active cases', 'value' => (string) $m['active_cases'], 'trend' => 'In scope', 'link' => route('goal.cases.index'), 'link_text' => 'Cases →', 'icon' => 'bi-briefcase', 'tone' => 'blue'],
                ];
                $d['exceptions'] = [
                    ['Missing / awaiting report', (string) $m['accounts_awaiting'], $m['accounts_awaiting'] > 0 ? 'High' : 'Low', route('goal.accounts.index')],
                    ['Payout on hold', (string) $m['payout_hold'], $m['payout_hold'] > 0 ? 'High' : 'Low', route('goal.payouts.index')],
                    ['Eligible awaiting processing', (string) $m['payout_eligible'], 'Medium', route('goal.payouts.index')],
                ];
                $d['actions'] = [
                    [route('goal.accounts.index'), 'qa-blue', 'bi-journal-check', 'Accounts Queue'],
                    [route('goal.payouts.index'), 'qa-purple', 'bi-cash-stack', 'Payout Board'],
                    [route('goal.cases.index'), 'qa-orange', 'bi-file-earmark', 'Cases'],
                    [route('goal.branch-mis'), 'qa-green', 'bi-bar-chart', 'MIS'],
                ];
                break;

            case 'sm':
            case 'jsm':
            case 'ssm':
                $d['summary'] = 'Focus on assigned pipeline — visits, bank login and disbursement.';
                $d['kpis'] = [
                    ['label' => 'My active cases', 'value' => (string) $m['my_active_cases'], 'trend' => 'Assigned to you', 'link' => route('goal.cases.index'), 'link_text' => 'Loan Cases →', 'icon' => 'bi-briefcase', 'tone' => 'magenta'],
                    ['label' => 'Target progress', 'value' => $d['target']['percent'] . '%', 'trend' => $fmtMoney($d['target']['actual']) . ' / ' . $fmtMoney($d['target']['effective']), 'link' => route('goal.targets.index'), 'link_text' => 'Targets →', 'icon' => 'bi-bullseye', 'tone' => 'blue'],
                    ['label' => 'Disbursed (period)', 'value' => (string) $m['disbursed_cases'], 'trend' => $d['period_label'], 'link' => route('goal.cases.index'), 'link_text' => 'Cases →', 'icon' => 'bi-cash-coin', 'tone' => 'green'],
                    ['label' => 'Pending allocation (branch)', 'value' => (string) $m['pending_allocation'], 'trend' => 'Branch queue', 'link' => route('goal.allocation.index'), 'link_text' => 'Allocation →', 'icon' => 'bi-people', 'tone' => 'red'],
                ];
                $d['exceptions'] = [
                    ['Active assigned cases', (string) $m['my_active_cases'], 'Medium', route('goal.cases.index')],
                    ['Target remaining', $fmtMoney($d['target']['remaining']), $d['target']['percent'] < 70 ? 'High' : 'Low', route('goal.targets.index')],
                    ['Pending BM allocation', (string) $m['pending_allocation'], 'Medium', route('goal.allocation.index')],
                ];
                $d['actions'] = [
                    [route('goal.cases.index'), 'qa-purple', 'bi-briefcase', 'My Cases'],
                    [route('goal.targets.index'), 'qa-green', 'bi-bullseye', 'My Target'],
                    [route('goal.leads.index'), 'qa-blue', 'bi-people', 'Leads'],
                    [route('goal.branch-mis'), 'qa-orange', 'bi-table', 'Branch MIS'],
                ];
                break;

            case 'md':
                $d['summary'] = 'Company performance across regions, clusters and payouts.';
                $d['kpis'] = [
                    ['label' => 'Leads pending allocation', 'value' => (string) $m['pending_allocation'], 'trend' => 'All branches', 'link' => route('goal.allocation.index'), 'link_text' => 'Allocation →', 'icon' => 'bi-people-fill', 'tone' => 'magenta'],
                    ['label' => 'Disbursed (period)', 'value' => (string) $m['disbursed_cases'], 'trend' => $d['period_label'], 'link' => route('goal.branch-mis'), 'link_text' => 'Branch MIS →', 'icon' => 'bi-cash-coin', 'tone' => 'blue'],
                    ['label' => 'Revenue / target', 'value' => $d['target']['percent'] . '%', 'trend' => $fmtMoney($d['target']['actual']) . ' of ' . $fmtMoney($d['target']['effective']), 'link' => route('goal.targets.index'), 'link_text' => 'Targets →', 'icon' => 'bi-bullseye', 'tone' => 'green'],
                    ['label' => 'Payout hold / eligible', 'value' => $m['payout_hold'] . ' / ' . $m['payout_eligible'], 'trend' => 'Hold | Eligible', 'link' => route('goal.payouts.index'), 'link_text' => 'Payouts →', 'icon' => 'bi-wallet2', 'tone' => 'orange'],
                ];
                $d['exceptions'] = [
                    ['Unassigned Immediate leads (all branches)', (string) $m['pending_allocation'], $m['pending_allocation'] > 0 ? 'High' : 'Low', route('goal.allocation.index')],
                    ['Payout on hold', (string) $m['payout_hold'], $m['payout_hold'] > 0 ? 'High' : 'Low', route('goal.payouts.index')],
                    ['PMS backlog', (string) $m['pms_pending'], 'Medium', route('goal.pms.index')],
                ];
                $d['actions'] = [
                    [route('goal.branch-mis'), 'qa-green', 'bi-bar-chart-fill', 'MIS Reports'],
                    [route('goal.targets.index'), 'qa-blue', 'bi-bullseye', 'Targets'],
                    [route('goal.allocation.index'), 'qa-purple', 'bi-arrow-left-right', 'Allocation'],
                    [route('goal.payouts.index'), 'qa-orange', 'bi-cash-stack', 'Payouts'],
                ];
                break;

            case 'ceo':
                $d['summary'] = 'Monitor regions, disbursement and Stage 2–3 queues across the company.';
                $d['kpis'] = [
                    ['label' => 'Disb vs target', 'value' => $d['target']['percent'] . '%', 'trend' => $fmtMoney($d['target']['actual']) . ' / ' . $fmtMoney($d['target']['effective']), 'link' => route('goal.targets.index'), 'link_text' => 'Targets →', 'icon' => 'bi-graph-up', 'tone' => 'magenta'],
                    ['label' => 'Active cases', 'value' => (string) $m['active_cases'], 'trend' => 'All branches', 'link' => route('goal.cases.index'), 'link_text' => 'Cases →', 'icon' => 'bi-briefcase', 'tone' => 'blue'],
                    ['label' => 'PMS backlog', 'value' => (string) $m['pms_pending'], 'trend' => 'Company queue', 'link' => route('goal.pms.index'), 'link_text' => 'PMS →', 'icon' => 'bi-shield-check', 'tone' => 'red'],
                    ['label' => 'Accounts on hold', 'value' => (string) $m['accounts_awaiting'], 'trend' => 'Awaiting report #', 'link' => route('goal.accounts.index'), 'link_text' => 'Accounts →', 'icon' => 'bi-journal-check', 'tone' => 'green'],
                ];
                $d['exceptions'] = [
                    ['Target below 70%', $d['target']['percent'] < 70 ? '1' : '0', $d['target']['percent'] < 70 ? 'High' : 'Low', route('goal.targets.index')],
                    ['PMS backlog', (string) $m['pms_pending'], $m['pms_pending'] > 0 ? 'High' : 'Low', route('goal.pms.index')],
                    ['Unassigned Immediate leads', (string) $m['pending_allocation'], 'Medium', route('goal.allocation.index')],
                ];
                $d['actions'] = [
                    [route('goal.branch-mis'), 'qa-green', 'bi-bar-chart-fill', 'MIS Reports'],
                    [route('goal.payouts.index'), 'qa-purple', 'bi-cash-stack', 'Payouts'],
                    [route('goal.targets.index'), 'qa-orange', 'bi-bullseye', 'Targets'],
                    [route('goal.cases.index'), 'qa-blue', 'bi-briefcase', 'Loan Cases'],
                ];
                break;

            case 'rd':
                $d['summary'] = 'Region view · Focus on target gap and branch exceptions.';
                $d['kpis'] = [
                    ['label' => 'Region target', 'value' => $d['target']['percent'] . '%', 'trend' => $fmtMoney($d['target']['actual']) . ' of ' . $fmtMoney($d['target']['effective']), 'link' => route('goal.targets.index'), 'link_text' => 'Targets →', 'icon' => 'bi-building', 'tone' => 'magenta'],
                    ['label' => 'Disbursed (period)', 'value' => (string) $m['disbursed_cases'], 'trend' => $d['period_label'], 'link' => route('goal.branch-mis'), 'link_text' => 'Branch MIS →', 'icon' => 'bi-cash-coin', 'tone' => 'blue'],
                    ['label' => 'Unassigned Immediate', 'value' => (string) $m['pending_allocation'], 'trend' => 'Across region', 'link' => route('goal.allocation.index'), 'link_text' => 'Allocation →', 'icon' => 'bi-people', 'tone' => 'red'],
                    ['label' => 'Active cases', 'value' => (string) $m['active_cases'], 'trend' => 'Regional pipeline', 'link' => route('goal.cases.index'), 'link_text' => 'Cases →', 'icon' => 'bi-briefcase', 'tone' => 'green'],
                ];
                $d['exceptions'] = [
                    ['Unassigned Immediate leads', (string) $m['pending_allocation'], $m['pending_allocation'] > 0 ? 'High' : 'Low', route('goal.allocation.index')],
                    ['Region target remaining', $fmtMoney($d['target']['remaining']), $d['target']['percent'] < 70 ? 'High' : 'Low', route('goal.targets.index')],
                    ['Payout on hold', (string) $m['payout_hold'], 'Medium', route('goal.payouts.index')],
                ];
                $d['actions'] = [
                    [route('goal.branch-mis'), 'qa-green', 'bi-table', 'Branch MIS'],
                    [route('goal.targets.index'), 'qa-blue', 'bi-bullseye', 'Region Targets'],
                    [route('goal.allocation.index'), 'qa-purple', 'bi-arrow-left-right', 'Allocation'],
                    [route('goal.cases.index'), 'qa-orange', 'bi-briefcase', 'Loan Cases'],
                ];
                break;

            case 'cluster':
                $d['summary'] = 'Cluster view · Coordinate BMs on allocation and targets.';
                $d['kpis'] = [
                    ['label' => 'Cluster target', 'value' => $d['target']['percent'] . '%', 'trend' => $fmtMoney($d['target']['actual']) . ' of ' . $fmtMoney($d['target']['effective']), 'link' => route('goal.targets.index'), 'link_text' => 'Targets →', 'icon' => 'bi-building', 'tone' => 'magenta'],
                    ['label' => 'Pending BM allocation', 'value' => (string) $m['pending_allocation'], 'trend' => 'Across cluster branches', 'link' => route('goal.allocation.index'), 'link_text' => 'Allocation →', 'icon' => 'bi-people', 'tone' => 'red'],
                    ['label' => 'Disbursed (period)', 'value' => (string) $m['disbursed_cases'], 'trend' => $d['period_label'], 'link' => route('goal.branch-mis'), 'link_text' => 'Branch MIS →', 'icon' => 'bi-cash-coin', 'tone' => 'blue'],
                    ['label' => 'Payout hold', 'value' => (string) $m['payout_hold'], 'trend' => 'Need Accounts', 'link' => route('goal.payouts.index'), 'link_text' => 'Payouts →', 'icon' => 'bi-pause-circle', 'tone' => 'green'],
                ];
                $d['exceptions'] = [
                    ['Unassigned Immediate leads', (string) $m['pending_allocation'], $m['pending_allocation'] > 0 ? 'High' : 'Low', route('goal.allocation.index')],
                    ['Cluster target remaining', $fmtMoney($d['target']['remaining']), $d['target']['percent'] < 70 ? 'High' : 'Low', route('goal.targets.index')],
                    ['Payout on hold', (string) $m['payout_hold'], 'Medium', route('goal.payouts.index')],
                ];
                $d['actions'] = [
                    [route('goal.allocation.index'), 'qa-purple', 'bi-arrow-left-right', 'BM Allocation'],
                    [route('goal.targets.index'), 'qa-blue', 'bi-bullseye', 'Cluster Targets'],
                    [route('goal.branch-mis'), 'qa-green', 'bi-table', 'Branch MIS'],
                    [route('goal.cases.index'), 'qa-orange', 'bi-briefcase', 'Loan Cases'],
                ];
                break;

            case 'bm':
            default:
                $d['summary'] = "You have {$m['active_cases']} active case(s) in pipeline · {$m['pending_allocation']} lead(s) awaiting allocation.";
                $d['kpis'] = [
                    ['label' => 'Leads pending allocation', 'value' => (string) $m['pending_allocation'], 'trend' => 'Branch queue', 'link' => route('goal.allocation.index'), 'link_text' => 'View Leads →', 'icon' => 'bi-people-fill', 'tone' => 'magenta'],
                    ['label' => 'Active loan cases', 'value' => (string) $m['active_cases'], 'trend' => 'In pipeline', 'link' => route('goal.cases.index'), 'link_text' => 'View Loan Cases →', 'icon' => 'bi-folder2-open', 'tone' => 'blue'],
                    ['label' => 'PMS pending', 'value' => (string) $m['pms_pending'], 'trend' => 'Verification queue', 'link' => route('goal.pms.index'), 'link_text' => 'View PMS Queue →', 'icon' => 'bi-shield-check', 'tone' => 'red'],
                    ['label' => 'Payout hold / eligible', 'value' => $m['payout_hold'] . ' / ' . $m['payout_eligible'], 'trend' => 'Hold | Eligible', 'link' => route('goal.payouts.index'), 'link_text' => 'View Payouts →', 'icon' => 'bi-cash-stack', 'tone' => 'green'],
                ];
                $d['exceptions'] = [
                    ['Unassigned Immediate leads', (string) $m['pending_allocation'], $m['pending_allocation'] > 0 ? 'High' : 'Low', route('goal.allocation.index')],
                    ['PMS pending', (string) $m['pms_pending'], $m['pms_pending'] > 0 ? 'High' : 'Low', route('goal.pms.index')],
                    ['Payout on hold (branch)', (string) $m['payout_hold'], 'Medium', route('goal.payouts.index')],
                ];
                $d['actions'] = [
                    [route('goal.leads.index', ['create' => 1]), 'qa-blue', 'bi-person-plus-fill', 'Create Lead'],
                    [route('goal.allocation.index'), 'qa-purple', 'bi-arrow-left-right', 'Allocate Case'],
                    [route('goal.branch-mis'), 'qa-green', 'bi-bar-chart-fill', 'View Reports'],
                    [route('goal.cases.index'), 'qa-orange', 'bi-file-earmark-text-fill', 'Case Workspace'],
                ];
                break;
        }

        return $d;
    }

    protected function resolveRoleKey(User $user): string
    {
        $job = strtolower((string) ($user->job_role ?? ''));
        if ($job !== '' && isset(self::ROLE_LABELS[$job])) {
            return $job;
        }
        if (in_array($job, ['cluster_head', 'cluster-head'], true)) {
            return 'cluster';
        }

        $type = (int) ($user->crm_role_type ?? -1);
        if (isset(self::ROLE_KEYS[$type])) {
            return self::ROLE_KEYS[$type];
        }

        // Permission fallbacks
        if ($user->hasCrmPermission('vrm_calling_guard') && !$user->hasCrmPermission('bm_allocation_guard')) {
            return 'vrm';
        }
        if ($user->hasCrmPermission('pms_verification_guard') && !$user->hasCrmPermission('bm_allocation_guard')) {
            return 'pms';
        }
        if ($user->hasCrmPermission('accounts_queue_guard') || $user->hasCrmPermission('payout_processing_guard')) {
            return 'accounts';
        }
        if ($user->hasCrmPermission('bm_allocation_guard')) {
            return 'bm';
        }

        return 'bm';
    }

    protected function resolvePeriod(string $period): array
    {
        return match ($period) {
            'today' => [
                'start' => now()->startOfDay(),
                'end' => now()->endOfDay(),
                'label' => 'Today',
            ],
            'last_month' => [
                'start' => now()->subMonthNoOverflow()->startOfMonth(),
                'end' => now()->subMonthNoOverflow()->endOfMonth(),
                'label' => 'Last Month',
            ],
            'this_year' => [
                'start' => now()->startOfYear(),
                'end' => now()->endOfDay(),
                'label' => 'This Year',
            ],
            default => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfDay(),
                'label' => 'This Month',
            ],
        };
    }

    protected function resolveTarget(User $user, array $scopeIds, array $periodMeta): ?GoalTarget
    {
        $periodKey = $periodMeta['start']->format('Y-m');
        $q = GoalTarget::query()->where('period', 'like', $periodKey . '%');

        if (in_array((int) ($user->crm_role_type ?? -1), [10, 11, 12], true)) {
            $mine = (clone $q)->where('user_id', $user->id)->first();
            if ($mine) {
                return $mine;
            }
        }

        if ($user->goal_org_unit_id) {
            $unit = (clone $q)->where('goal_org_unit_id', $user->goal_org_unit_id)->whereNull('user_id')->first();
            if ($unit) {
                return $unit;
            }
        }

        if ($scopeIds !== []) {
            return (clone $q)->whereIn('goal_org_unit_id', $scopeIds)->whereNull('user_id')->orderByDesc('actual')->first();
        }

        return $q->whereNull('user_id')->orderByDesc('actual')->first();
    }

    protected function targetTitle(string $roleKey): string
    {
        return match ($roleKey) {
            'vrm' => 'Daily Call Target',
            'md', 'ceo' => 'Company Revenue Target',
            'rd' => 'Region Revenue Target',
            'cluster' => 'Cluster Revenue Target',
            'sm', 'jsm', 'ssm' => 'My Sales Target',
            'pms' => 'Verification SLA',
            'accounts' => 'Payout release readiness',
            default => 'Branch Disbursement Target',
        };
    }

    protected function buildTrend(
        array $scopeIds,
        string $roleKey,
        User $user,
        Carbon $start,
        Carbon $end,
        string $interval = 'weekly'
    ): array {
        $labels = [];
        $leads = [];
        $cases = [];
        $calls = [];

        if ($interval === 'monthly') {
            $cursor = $start->copy()->startOfMonth();
            // Ensure at least a few months of context for short periods
            if ($cursor->diffInMonths($end) < 2) {
                $cursor = $end->copy()->subMonthsNoOverflow(5)->startOfMonth();
            }
            $i = 0;
            while ($cursor->lte($end) && $i < 12) {
                $bucketEnd = $cursor->copy()->endOfMonth();
                if ($bucketEnd->gt($end)) {
                    $bucketEnd = $end->copy();
                }
                $bucketStart = $cursor->copy()->startOfMonth();
                if ($bucketStart->lt($start) && $start->diffInMonths($end) >= 2) {
                    $bucketStart = $start->copy();
                }

                [$l, $c, $k] = $this->trendBucketCounts($scopeIds, $roleKey, $user, $bucketStart, $bucketEnd);
                $labels[] = $cursor->format('M');
                $leads[] = $l;
                $cases[] = $c;
                $calls[] = $k;

                $cursor->addMonthNoOverflow();
                $i++;
            }
        } else {
            $cursor = $start->copy()->startOfWeek();
            $week = 1;
            while ($cursor->lte($end) && $week <= 12) {
                $wEnd = $cursor->copy()->endOfWeek();
                if ($wEnd->gt($end)) {
                    $wEnd = $end->copy();
                }

                [$l, $c, $k] = $this->trendBucketCounts($scopeIds, $roleKey, $user, $cursor, $wEnd);
                $labels[] = 'W' . $week;
                $leads[] = $l;
                $cases[] = $c;
                $calls[] = $k;

                $cursor->addWeek();
                $week++;
            }
        }

        if ($labels === []) {
            $labels = $interval === 'monthly' ? ['Jan', 'Feb', 'Mar', 'Apr'] : ['W1', 'W2', 'W3', 'W4'];
            $leads = [0, 0, 0, 0];
            $cases = [0, 0, 0, 0];
            $calls = [0, 0, 0, 0];
        }

        return compact('labels', 'leads', 'cases', 'calls');
    }

    protected function trendBucketCounts(
        array $scopeIds,
        string $roleKey,
        User $user,
        Carbon $from,
        Carbon $to
    ): array {
        $lq = GoalLoanLead::query()->whereBetween('created_at', [$from, $to]);
        $cq = GoalLoanCase::query()->whereBetween('created_at', [$from, $to]);
        if ($scopeIds !== []) {
            $lq->whereIn('goal_org_unit_id', $scopeIds);
            $cq->whereIn('goal_org_unit_id', $scopeIds);
        }
        if (in_array($roleKey, ['sm', 'jsm', 'ssm'], true)) {
            $cq->where('owner_id', $user->id);
        }
        if ($roleKey === 'vrm') {
            $lq->where('created_by', $user->id);
        }

        $callCount = 0;
        if ($roleKey === 'vrm') {
            $callCount = GoalVrmCallLog::query()
                ->where('user_id', $user->id)
                ->whereBetween('called_at', [$from, $to])
                ->count();
        }

        return [$lq->count(), $cq->count(), $callCount];
    }
}
