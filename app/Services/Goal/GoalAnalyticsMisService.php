<?php

namespace App\Services\Goal;

use App\Helpers\MoneyFormatter;
use App\Models\GoalCaseTask;
use App\Models\GoalDisbursement;
use App\Models\GoalLoanCase;
use App\Models\GoalLoanLead;
use App\Models\GoalOrgUnit;
use App\Models\GoalPayout;
use App\Models\GoalRevenueEntry;
use App\Models\GoalTarget;
use App\Models\User;
use App\Services\FinancialYearService;
use Carbon\Carbon;
/**
 * Analytics Dashboard + MIS Reports register payload (role-scoped).
 */
class GoalAnalyticsMisService
{
    public function build(User $user, array $filters = []): array
    {
        $roleKey = $this->resolveRoleKey($user);
        $fySvc = app(FinancialYearService::class);
        $defaults = $fySvc->defaultDateFilters();
        if (empty($filters['fy'])) {
            $filters['fy'] = $defaults['fy'];
        }
        if (empty($filters['date_from'])) {
            $filters['date_from'] = $defaults['date_from'];
        }
        if (empty($filters['date_to'])) {
            $filters['date_to'] = $defaults['date_to'];
        }
        $scopeIds = $this->resolveScopeIds($user, $filters);
        $range = $this->resolveDateRange($filters);
        $prev = $this->previousRange($range['start'], $range['end']);

        $leadsQ = $this->leadsQuery($user, $roleKey, $scopeIds, $filters);
        $casesQ = $this->casesQuery($user, $roleKey, $scopeIds, $filters);

        $filterQs = array_filter([
            'date_from' => $range['start']->format('Y-m-d'),
            'date_to' => $range['end']->format('Y-m-d'),
            'branch' => $filters['branch'] ?? null,
            'product' => $filters['product'] ?? null,
            'owner' => $filters['owner'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        $kpis = $this->buildKpis($leadsQ, $casesQ, $scopeIds, $range, $prev, $filterQs);
        $funnel = $this->buildFunnel($leadsQ, $casesQ, $range, $filterQs);
        $trend = $this->buildDisbRevenueTrend($scopeIds, $range, $roleKey, $user);
        $targets = $this->buildTargetTabs($scopeIds, $range, $roleKey, $user);
        $branches = $this->buildBranchComparison($scopeIds, $range, $filters);
        $caseStatus = $this->buildCaseStatusDistribution($casesQ);
        $alerts = $this->buildAlerts($leadsQ, $casesQ, $scopeIds);
        $misRegister = $this->misRegister($range, $filters);
        $filterOptions = $this->filterOptions($user, $scopeIds);

        $section = $filters['section'] ?? 'overview';
        if (! in_array($section, ['overview', 'leads', 'processing', 'disbursement', 'revenue', 'performance'], true)) {
            $section = 'overview';
        }

        return [
            'role_key' => $roleKey,
            'role_label' => GoalDashboardService::ROLE_LABELS[$roleKey] ?? 'User',
            'scope_label' => $this->scopeLabel($user, $scopeIds, $roleKey),
            'filters' => array_merge([
                'section' => $section,
                'fy' => $filters['fy'] ?? $this->currentFy(),
                'date_from' => $range['start']->format('Y-m-d'),
                'date_to' => $range['end']->format('Y-m-d'),
                'region' => $filters['region'] ?? '',
                'cluster' => $filters['cluster'] ?? '',
                'branch' => $filters['branch'] ?? '',
                'product' => $filters['product'] ?? '',
                'owner' => $filters['owner'] ?? '',
                'target_tab' => $filters['target_tab'] ?? 'employee',
            ], $filters, ['section' => $section]),
            'filter_options' => $filterOptions,
            'kpis' => $kpis,
            'funnel' => $funnel,
            'trend' => $trend,
            'targets' => $targets,
            'branches' => $branches,
            'case_status' => $caseStatus,
            'alerts' => $alerts,
            'mis_register' => $misRegister,
            'fy_options' => $this->fyOptions(),
        ];
    }

    public function resolveScopeIds(User $user, array $filters): array
    {
        $base = $user->goalScopedOrgUnitIds();
        $isWide = $user->isSuperAdmin()
            || in_array($this->resolveRoleKey($user), ['md', 'ceo'], true);

        if (! empty($filters['branch'])) {
            return [(int) $filters['branch']];
        }
        if (! empty($filters['cluster'])) {
            $clusterId = (int) $filters['cluster'];
            $ids = GoalOrgUnit::query()->where('parent_id', $clusterId)->pluck('id')->map(fn ($id) => (int) $id)->all();
            $ids[] = $clusterId;

            if ($base === [] && $isWide) {
                return $ids;
            }
            $intersect = array_values(array_intersect($base !== [] ? $base : $ids, $ids));

            return $intersect !== [] ? $intersect : $ids;
        }
        if (! empty($filters['region'])) {
            $regionId = (int) $filters['region'];
            $clusterIds = GoalOrgUnit::query()->where('parent_id', $regionId)->pluck('id')->all();
            $branchIds = GoalOrgUnit::query()->whereIn('parent_id', $clusterIds)->pluck('id')->all();
            $ids = array_map('intval', array_merge([$regionId], $clusterIds, $branchIds));

            if ($base === [] && $isWide) {
                return $ids;
            }
            $intersect = array_values(array_intersect($base !== [] ? $base : $ids, $ids));

            return $intersect !== [] ? $intersect : $ids;
        }

        if ($base !== []) {
            return $base;
        }

        return $isWide ? [] : [-1];
    }

    public function resolveDateRange(array $filters): array
    {
        $fySvc = app(FinancialYearService::class);

        if (! empty($filters['date_from']) || ! empty($filters['date_to'])) {
            $defaults = $fySvc->defaultDateFilters();
            $from = ! empty($filters['date_from'])
                ? Carbon::parse($filters['date_from'])->startOfDay()
                : Carbon::parse($defaults['date_from'])->startOfDay();
            $to = ! empty($filters['date_to'])
                ? Carbon::parse($filters['date_to'])->endOfDay()
                : Carbon::parse($defaults['date_to'])->endOfDay();
        } elseif (! empty($filters['fy']) && preg_match('/^(\d{4})-(\d{4})$/', (string) $filters['fy'], $m)) {
            $match = \App\Models\FinancialYear::query()
                ->orderByDesc('from_date')
                ->get()
                ->first(fn ($fy) => $fySvc->analyticsKey($fy) === $filters['fy']);
            if ($match) {
                [$from, $to] = $fySvc->range($match);
            } else {
                // Mid-year date inside Apr–Mar (or company) window that starts in $m[1]
                [$from, $to] = $fySvc->computeContainingRange(
                    Carbon::create((int) $m[1], 6, 15)->startOfDay()
                );
            }
        } else {
            [$from, $to] = $fySvc->range();
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return ['start' => $from, 'end' => $to];
    }

    protected function previousRange(Carbon $start, Carbon $end): array
    {
        $days = max(1, $start->diffInDays($end) + 1);

        return [
            'start' => $start->copy()->subDays($days)->startOfDay(),
            'end' => $start->copy()->subDay()->endOfDay(),
        ];
    }

    protected function leadsQuery(User $user, string $roleKey, array $scopeIds, array $filters)
    {
        $q = GoalLoanLead::query();
        if ($scopeIds === [-1]) {
            return $q->whereRaw('1 = 0');
        }
        if ($scopeIds !== []) {
            $q->whereIn('goal_org_unit_id', $scopeIds);
        }
        if ($roleKey === 'vrm') {
            $q->where('created_by', $user->id);
        }
        if (! empty($filters['product'])) {
            $q->where('product', $filters['product']);
        }
        if (! empty($filters['owner'])) {
            $q->where('assigned_to', (int) $filters['owner']);
        }

        return $q;
    }

    protected function casesQuery(User $user, string $roleKey, array $scopeIds, array $filters)
    {
        $q = GoalLoanCase::query();
        if ($scopeIds === [-1]) {
            return $q->whereRaw('1 = 0');
        }
        if ($scopeIds !== []) {
            $q->whereIn('goal_org_unit_id', $scopeIds);
        }
        if (in_array($roleKey, ['sm', 'jsm', 'ssm'], true)) {
            $q->where('owner_id', $user->id);
        }
        if (! empty($filters['product'])) {
            $q->where('product', $filters['product']);
        }
        if (! empty($filters['owner'])) {
            $q->where('owner_id', (int) $filters['owner']);
        }

        return $q;
    }

    protected function buildKpis($leadsQ, $casesQ, array $scopeIds, array $range, array $prev, array $filterQs = []): array
    {
        $totalLeads = (clone $leadsQ)->whereBetween('created_at', [$range['start'], $range['end']])->count();
        $prevLeads = (clone $leadsQ)->whereBetween('created_at', [$prev['start'], $prev['end']])->count();

        $allocated = (clone $leadsQ)
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->where(function ($q) {
                $q->whereNotNull('assigned_to')
                    ->orWhere(function ($inner) {
                        $inner->whereNotNull('status')
                            ->where('status', '!=', 'Pending BM Allocation');
                    });
            })
            ->count();
        $prevAllocated = (clone $leadsQ)
            ->whereBetween('created_at', [$prev['start'], $prev['end']])
            ->where(function ($q) {
                $q->whereNotNull('assigned_to')
                    ->orWhere(function ($inner) {
                        $inner->whereNotNull('status')
                            ->where('status', '!=', 'Pending BM Allocation');
                    });
            })
            ->count();

        $processing = (clone $casesQ)
            ->whereNotIn('status', ['Disbursed', 'Closed', 'Rejected', 'Cancelled'])
            ->count();
        $prevProcessing = (clone $casesQ)
            ->whereBetween('created_at', [$prev['start'], $prev['end']])
            ->whereNotIn('status', ['Disbursed', 'Closed', 'Rejected', 'Cancelled'])
            ->count();

        $disbQ = GoalDisbursement::query()
            ->whereNotNull('verified_at')
            ->when($scopeIds !== [] && $scopeIds !== [-1], function ($q) use ($scopeIds) {
                $q->whereHas('loanCase', fn ($c) => $c->whereIn('goal_org_unit_id', $scopeIds));
            });
        $disbAmt = (float) (clone $disbQ)->whereBetween('verified_at', [$range['start'], $range['end']])->sum('amount');
        $prevDisb = (float) (clone $disbQ)->whereBetween('verified_at', [$prev['start'], $prev['end']])->sum('amount');

        $revQ = GoalRevenueEntry::query()
            ->when($scopeIds !== [] && $scopeIds !== [-1], function ($q) use ($scopeIds) {
                $q->whereHas('loanCase', fn ($c) => $c->whereIn('goal_org_unit_id', $scopeIds));
            });
        $revAmt = (float) (clone $revQ)->whereBetween('created_at', [$range['start'], $range['end']])->sum('amount');
        $prevRev = (float) (clone $revQ)->whereBetween('created_at', [$prev['start'], $prev['end']])->sum('amount');

        $payoutQ = GoalPayout::query()
            ->when($scopeIds !== [] && $scopeIds !== [-1], function ($q) use ($scopeIds) {
                $q->whereHas('loanCase', fn ($c) => $c->whereIn('goal_org_unit_id', $scopeIds));
            })
            ->where(function ($q) {
                $q->where('status', 'like', '%Hold%')
                    ->orWhere('status', 'like', '%Outstanding%')
                    ->orWhere('status', 'like', '%Pending%')
                    ->orWhere('status', 'like', '%Eligible%')
                    ->orWhere('status', 'On Hold');
            });
        $payoutOut = (float) (clone $payoutQ)->get()->sum(function ($p) {
            return max(0, (float) $p->expected_amount - (float) $p->received_amount);
        });
        $prevPayoutOut = $payoutOut;

        return [
            $this->kpi('Total Leads', $totalLeads, $prevLeads, false, 'bi-people-fill', 'blue', route('goal.leads.index', $filterQs)),
            $this->kpi('Leads Allocated', $allocated, $prevAllocated, false, 'bi-layers-fill', 'teal', route('goal.allocation.index', $filterQs)),
            $this->kpi('Cases in Processing', $processing, $prevProcessing, false, 'bi-briefcase-fill', 'amber', route('goal.cases.index', array_merge($filterQs, ['status' => 'Processing']))),
            $this->kpi('Disbursed Amount', $disbAmt, $prevDisb, true, 'bi-coin', 'green', route('goal.cases.index', array_merge($filterQs, ['status' => 'Disbursed'])), true),
            $this->kpi('Revenue Achieved', $revAmt, $prevRev, true, 'bi-graph-up-arrow', 'purple', route('goal.revenue-ledger', $filterQs), true),
            $this->kpi('Payout Outstanding', $payoutOut, $prevPayoutOut, true, 'bi-credit-card-2-front-fill', 'red', route('goal.payouts.index'), true),
        ];
    }

    protected function kpi(string $label, float|int $value, float|int $prev, bool $money, string $icon, string $tone, ?string $href = null, bool $compactMoney = false): array
    {
        $delta = null;
        if ((float) $prev > 0) {
            $delta = round((((float) $value - (float) $prev) / (float) $prev) * 100, 1);
        } elseif ((float) $value > 0) {
            $delta = 100.0;
        }

        return [
            'label' => $label,
            'value' => $money
                ? ($compactMoney ? $this->compactInr((float) $value) : $this->inr((float) $value))
                : number_format((int) $value),
            'raw' => $value,
            'delta' => $delta,
            'icon' => $icon,
            'tone' => $tone,
            'href' => $href,
        ];
    }

    protected function buildFunnel($leadsQ, $casesQ, array $range, array $filterQs = []): array
    {
        $received = (clone $leadsQ)->whereBetween('created_at', [$range['start'], $range['end']])->count();
        $allocated = (clone $leadsQ)
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->where(function ($q) {
                $q->whereNotNull('assigned_to')
                    ->orWhere(function ($inner) {
                        $inner->where('status', '!=', 'Pending BM Allocation')->whereNotNull('status');
                    });
            })
            ->count();
        $caseCreated = (clone $casesQ)->whereBetween('created_at', [$range['start'], $range['end']])->count();
        $bankLogin = (clone $casesQ)->whereIn('status', ['Bank Login', 'Bank Query'])->count();
        $sanctioned = (clone $casesQ)->where('status', 'Sanctioned')->count();
        $disbursed = (clone $casesQ)->where('status', 'Disbursed')->count();

        $colors = ['#2563eb', '#38bdf8', '#8b5cf6', '#f97316', '#4ade80', '#0f766e'];
        $hrefs = [
            'received' => route('goal.leads.index', $filterQs),
            'allocated' => route('goal.allocation.index', $filterQs),
            'case' => route('goal.cases.index', $filterQs),
            'bank' => route('goal.cases.index', array_merge($filterQs, ['status' => 'Bank Login'])),
            'sanction' => route('goal.cases.index', array_merge($filterQs, ['status' => 'Sanctioned'])),
            'disbursed' => route('goal.cases.index', array_merge($filterQs, ['status' => 'Disbursed'])),
        ];

        $stages = [
            ['key' => 'received', 'label' => 'Leads Received', 'count' => $received],
            ['key' => 'allocated', 'label' => 'Allocated', 'count' => $allocated],
            ['key' => 'case', 'label' => 'Case Created', 'count' => $caseCreated],
            ['key' => 'bank', 'label' => 'Bank Login', 'count' => $bankLogin],
            ['key' => 'sanction', 'label' => 'Sanctioned', 'count' => $sanctioned],
            ['key' => 'disbursed', 'label' => 'Disbursed', 'count' => $disbursed],
        ];

        $max = max(1, $received);
        for ($i = 0; $i < count($stages); $i++) {
            $prevCount = $i === 0 ? max(1, $stages[0]['count']) : max(1, $stages[$i - 1]['count']);
            $stages[$i]['from_prev_pct'] = $i === 0
                ? 100.0
                : round(($stages[$i]['count'] / $prevCount) * 100, 1);
            $stages[$i]['width_pct'] = max(18, round(($stages[$i]['count'] / $max) * 100, 1));
            $stages[$i]['color'] = $colors[$i] ?? '#64748b';
            $stages[$i]['href'] = $hrefs[$stages[$i]['key']] ?? '#';
        }

        $overall = $received > 0 ? round(($disbursed / $received) * 100, 1) : 0.0;

        return [
            'stages' => $stages,
            'overall_conversion' => $overall,
        ];
    }

    protected function buildCaseStatusDistribution($casesQ): array
    {
        $groups = [
            'Bank Login' => ['Bank Login', 'Bank Query'],
            'Processing' => ['Processing'],
            'Sanctioned' => ['Sanctioned'],
            'Disbursed' => ['Disbursed'],
            'On Hold' => ['On Hold', 'Hold'],
            'Rejected' => ['Rejected', 'Cancelled', 'Closed'],
        ];
        $colors = [
            'Bank Login' => '#3b82f6',
            'Processing' => '#22c55e',
            'Sanctioned' => '#f97316',
            'Disbursed' => '#8b5cf6',
            'On Hold' => '#ef4444',
            'Rejected' => '#94a3b8',
        ];

        $items = [];
        $total = 0;
        foreach ($groups as $label => $statuses) {
            $count = (clone $casesQ)->whereIn('status', $statuses)->count();
            $items[] = [
                'label' => $label,
                'count' => $count,
                'color' => $colors[$label],
                'href' => route('goal.cases.index', ['status' => $statuses[0]]),
            ];
            $total += $count;
        }

        // Catch-all remaining active statuses into Processing bucket visually if needed
        $known = array_merge(...array_values($groups));
        $other = (clone $casesQ)->whereNotIn('status', $known)->count();
        if ($other > 0) {
            foreach ($items as &$item) {
                if ($item['label'] === 'Processing') {
                    $item['count'] += $other;
                    break;
                }
            }
            unset($item);
            $total += $other;
        }

        foreach ($items as &$item) {
            $item['percent'] = $total > 0 ? round(($item['count'] / $total) * 100, 1) : 0.0;
        }
        unset($item);

        return [
            'total' => $total,
            'items' => $items,
        ];
    }

    protected function buildDisbRevenueTrend(array $scopeIds, array $range, string $roleKey, User $user): array
    {
        $labels = [];
        $tooltipLabels = [];
        $disb = [];
        $rev = [];
        $endMonth = $range['end']->copy()->startOfMonth();
        // Always show last 6 months ending at range end for chart readability
        $cursor = $endMonth->copy()->subMonthsNoOverflow(5);

        while ($cursor->lte($endMonth)) {
            $mStart = $cursor->copy()->startOfMonth();
            $mEnd = $cursor->copy()->endOfMonth();
            $labels[] = $cursor->format('M');
            $tooltipLabels[] = $cursor->format('M Y');

            $disbQ = GoalDisbursement::query()
                ->whereNotNull('verified_at')
                ->whereBetween('verified_at', [$mStart, $mEnd])
                ->when($scopeIds !== [] && $scopeIds !== [-1], fn ($q) => $q->whereHas('loanCase', fn ($c) => $c->whereIn('goal_org_unit_id', $scopeIds)));
            if (in_array($roleKey, ['sm', 'jsm', 'ssm'], true)) {
                $disbQ->whereHas('loanCase', fn ($c) => $c->where('owner_id', $user->id));
            }
            $disb[] = round(((float) $disbQ->sum('amount')) / 10000000, 2); // Cr

            $revQ = GoalRevenueEntry::query()
                ->whereBetween('created_at', [$mStart, $mEnd])
                ->when($scopeIds !== [] && $scopeIds !== [-1], fn ($q) => $q->whereHas('loanCase', fn ($c) => $c->whereIn('goal_org_unit_id', $scopeIds)));
            if (in_array($roleKey, ['sm', 'jsm', 'ssm'], true)) {
                $revQ->whereHas('loanCase', fn ($c) => $c->where('owner_id', $user->id));
            }
            $rev[] = round(((float) $revQ->sum('amount')) / 100000, 2); // Lac

            $cursor->addMonthNoOverflow();
        }

        return [
            'labels' => $labels,
            'tooltip_labels' => $tooltipLabels,
            'disbursement_cr' => $disb,
            'revenue_lac' => $rev,
        ];
    }

    protected function buildTargetTabs(array $scopeIds, array $range, string $roleKey, User $user): array
    {
        $periodKey = $range['start']->format('Y-m');
        $base = GoalTarget::query()
            ->with(['user:id,name', 'orgUnit:id,name,type'])
            ->where('period', 'like', $periodKey.'%');

        if ($scopeIds !== [] && $scopeIds !== [-1]) {
            $base->where(function ($q) use ($scopeIds) {
                $q->whereIn('goal_org_unit_id', $scopeIds)
                    ->orWhereHas('user', fn ($u) => $u->whereIn('goal_org_unit_id', $scopeIds));
            });
        }

        if (in_array($roleKey, ['sm', 'jsm', 'ssm'], true)) {
            $base->where('user_id', $user->id);
        }

        $all = $base->orderByDesc('actual')->limit(200)->get();

        $mapRow = function (GoalTarget $t, string $name) {
            $effective = $t->effectiveTarget();
            $actual = (float) $t->actual;
            $pct = $effective > 0 ? (int) round(($actual / $effective) * 100) : 0;

            return [
                'name' => $name,
                'target' => $this->inr($effective),
                'achieved' => $this->inr($actual),
                'percent' => $pct,
            ];
        };

        $employee = $all->filter(fn ($t) => $t->user_id)->take(12)->map(fn ($t) => $mapRow($t, $t->user?->name ?: 'User'))->values()->all();
        $branch = $all->filter(fn ($t) => ! $t->user_id && ($t->orgUnit?->type === 'branch' || $t->orgUnit?->type === GoalOrgUnit::TYPE_BRANCH))
            ->take(12)->map(fn ($t) => $mapRow($t, $t->orgUnit?->name ?: 'Branch'))->values()->all();
        $cluster = $all->filter(fn ($t) => ! $t->user_id && ($t->orgUnit?->type === 'cluster' || $t->orgUnit?->type === GoalOrgUnit::TYPE_CLUSTER))
            ->take(12)->map(fn ($t) => $mapRow($t, $t->orgUnit?->name ?: 'Cluster'))->values()->all();
        $region = $all->filter(fn ($t) => ! $t->user_id && ($t->orgUnit?->type === 'region' || $t->orgUnit?->type === GoalOrgUnit::TYPE_REGION))
            ->take(12)->map(fn ($t) => $mapRow($t, $t->orgUnit?->name ?: 'Region'))->values()->all();

        // Fallback: org unit rows without typed org
        if ($branch === [] && $employee === []) {
            $branch = $all->filter(fn ($t) => ! $t->user_id)->take(12)
                ->map(fn ($t) => $mapRow($t, $t->orgUnit?->name ?: 'Unit'))->values()->all();
        }

        return compact('employee', 'branch', 'cluster', 'region');
    }

    protected function buildBranchComparison(array $scopeIds, array $range, array $filters = []): array
    {
        $branches = GoalOrgUnit::query()
            ->where('type', GoalOrgUnit::TYPE_BRANCH)
            ->when($scopeIds !== [] && $scopeIds !== [-1], fn ($q) => $q->whereIn('id', $scopeIds))
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $rows = [];
        foreach ($branches as $br) {
            $amt = (float) GoalDisbursement::query()
                ->whereNotNull('verified_at')
                ->whereBetween('verified_at', [$range['start'], $range['end']])
                ->whereHas('loanCase', fn ($c) => $c->where('goal_org_unit_id', $br->id))
                ->sum('amount');
            $rev = (float) GoalRevenueEntry::query()
                ->whereBetween('created_at', [$range['start'], $range['end']])
                ->whereHas('loanCase', fn ($c) => $c->where('goal_org_unit_id', $br->id))
                ->sum('amount');
            $rows[] = [
                'id' => (int) $br->id,
                'name' => $br->name,
                'cluster_id' => $br->parent_id ? (int) $br->parent_id : null,
                'disbursement_lac' => round($amt / 100000, 2),
                'revenue_lac' => round($rev / 100000, 2),
                'disbursement_raw' => $amt,
            ];
        }

        usort($rows, fn ($a, $b) => $b['disbursement_raw'] <=> $a['disbursement_raw']);
        $rows = array_slice($rows, 0, 5);

        $drillQs = array_filter([
            'fy' => $filters['fy'] ?? null,
            'date_from' => $range['start']->format('Y-m-d'),
            'date_to' => $range['end']->format('Y-m-d'),
            'region' => $filters['region'] ?? null,
            'product' => $filters['product'] ?? null,
            'owner' => $filters['owner'] ?? null,
            'section' => 'disbursement',
        ], fn ($v) => $v !== null && $v !== '');

        return [
            'labels' => array_column($rows, 'name'),
            'ids' => array_column($rows, 'id'),
            'cluster_ids' => array_column($rows, 'cluster_id'),
            'disbursement_lac' => array_column($rows, 'disbursement_lac'),
            'revenue_lac' => array_column($rows, 'revenue_lac'),
            'drill_base' => route('goal.analytics', $drillQs),
            'active_branch' => $filters['branch'] ?? '',
        ];
    }

    protected function buildAlerts($leadsQ, $casesQ, array $scopeIds): array
    {
        $pendingAlloc = (clone $leadsQ)->where('status', 'Pending BM Allocation')->count();
        $overdueFollowups = GoalCaseTask::query()
            ->visitTypes()
            ->where('status', 'Open')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->when($scopeIds !== [] && $scopeIds !== [-1], function ($q) use ($scopeIds) {
                $q->whereHas('loanCase', fn ($c) => $c->whereIn('goal_org_unit_id', $scopeIds));
            })
            ->count();
        $pendingQueries = \App\Models\GoalBankQuery::query()
            ->whereIn('status', ['Open', 'In Progress', 'Pending'])
            ->when($scopeIds !== [] && $scopeIds !== [-1], function ($q) use ($scopeIds) {
                $q->whereHas('loanCase', fn ($c) => $c->whereIn('goal_org_unit_id', $scopeIds));
            })
            ->count();
        $payoutHolds = GoalPayout::query()
            ->when($scopeIds !== [] && $scopeIds !== [-1], function ($q) use ($scopeIds) {
                $q->whereHas('loanCase', fn ($c) => $c->whereIn('goal_org_unit_id', $scopeIds));
            })
            ->where(function ($q) {
                $q->where('status', 'like', '%Hold%')->orWhere('status', 'On Hold');
            })
            ->count();

        return [
            ['label' => 'Pending BM Allocations', 'count' => $pendingAlloc, 'priority' => 'High', 'tone' => 'danger', 'icon' => 'bi-people', 'href' => route('goal.allocation.index')],
            ['label' => 'Overdue Follow-ups', 'count' => $overdueFollowups, 'priority' => 'Medium', 'tone' => 'warning', 'icon' => 'bi-calendar-event', 'href' => route('goal.visits.index', ['tab' => 'overdue'])],
            ['label' => 'Pending Bank Queries', 'count' => $pendingQueries, 'priority' => 'Medium', 'tone' => 'warning', 'icon' => 'bi-bank', 'href' => route('goal.bank-processing.index')],
            ['label' => 'Payout Holds', 'count' => $payoutHolds, 'priority' => 'Low', 'tone' => 'success', 'icon' => 'bi-credit-card-2-front', 'href' => route('goal.payouts.index')],
        ];
    }

    protected function misRegister(array $range, array $filters): array
    {
        $period = $range['start']->format('M Y');
        $qs = array_filter([
            'date_from' => $range['start']->format('Y-m-d'),
            'date_to' => $range['end']->format('Y-m-d'),
            'branch' => $filters['branch'] ?? null,
            'region' => $filters['region'] ?? null,
            'cluster' => $filters['cluster'] ?? null,
            'product' => $filters['product'] ?? null,
            'fy' => $filters['fy'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        return [
            // Operational (5)
            [
                'key' => 'lead_performance',
                'name' => 'Lead Performance',
                'group' => 'operational',
                'category' => 'Operational',
                'description' => 'Lead inflow, allocation, follow-up and conversion into loan cases.',
                'icon' => 'bi-people-fill',
                'tone' => 'blue',
                'period' => $period,
                'scope' => 'Role scoped',
                'view' => route('goal.mis-reports.show', array_merge($qs, ['report' => 'lead_performance'])),
                'export' => route('goal.mis-reports.export', array_merge($qs, ['report' => 'lead_performance', 'format' => 'xlsx'])),
            ],
            [
                'key' => 'loan_processing',
                'name' => 'Loan Processing Report',
                'group' => 'operational',
                'category' => 'Operational',
                'description' => 'Track cases from creation to disbursement and identify processing delays.',
                'icon' => 'bi-kanban-fill',
                'tone' => 'indigo',
                'period' => $period,
                'scope' => 'Role scoped',
                'view' => route('goal.mis-reports.show', array_merge($qs, ['report' => 'loan_processing'])),
                'export' => route('goal.mis-reports.export', array_merge($qs, ['report' => 'loan_processing', 'format' => 'xlsx'])),
            ],
            [
                'key' => 'visit_followup',
                'name' => 'Visit & Follow-up Report',
                'group' => 'operational',
                'category' => 'Operational',
                'description' => 'Monitor visits, calls, follow-ups and field activity.',
                'icon' => 'bi-geo-alt-fill',
                'tone' => 'teal',
                'period' => $period,
                'scope' => 'Field team',
                'view' => route('goal.mis-reports.show', array_merge($qs, ['report' => 'visit_followup'])),
                'export' => route('goal.mis-reports.export', array_merge($qs, ['report' => 'visit_followup', 'format' => 'xlsx'])),
            ],
            [
                'key' => 'exception_report',
                'name' => 'Exception Report',
                'group' => 'operational',
                'category' => 'Operational',
                'description' => 'Records needing management attention: unallocated leads, overdue follow-ups, queries and holds.',
                'icon' => 'bi-exclamation-triangle-fill',
                'tone' => 'orange',
                'period' => $period,
                'scope' => 'Exceptions',
                'view' => route('goal.mis-reports.show', array_merge($qs, ['report' => 'exception_report'])),
                'export' => route('goal.mis-reports.export', array_merge($qs, ['report' => 'exception_report', 'format' => 'xlsx'])),
            ],
            [
                'key' => 'case_summary',
                'name' => 'Case Summary Report',
                'group' => 'operational',
                'category' => 'Operational',
                'description' => 'Consolidated loan-case lifecycle with status, amounts and ownership.',
                'icon' => 'bi-file-earmark-bar-graph-fill',
                'tone' => 'green',
                'period' => $period,
                'scope' => 'All cases',
                'view' => route('goal.mis-reports.show', array_merge($qs, ['report' => 'case_summary'])),
                'export' => route('goal.mis-reports.export', array_merge($qs, ['report' => 'case_summary', 'format' => 'xlsx'])),
            ],
            // Financial (3)
            [
                'key' => 'disbursement_summary',
                'name' => 'Disbursement Summary',
                'group' => 'financial',
                'category' => 'Financial',
                'description' => 'Actual disbursed amounts by bank, branch and product — not sanctioned amounts.',
                'icon' => 'bi-cash-coin',
                'tone' => 'teal',
                'period' => $period,
                'scope' => 'Verified disbursements',
                'view' => route('goal.mis-reports.show', array_merge($qs, ['report' => 'disbursement_summary'])),
                'export' => route('goal.mis-reports.export', array_merge($qs, ['report' => 'disbursement_summary', 'format' => 'xlsx'])),
            ],
            [
                'key' => 'revenue_ledger',
                'name' => 'Revenue Ledger',
                'group' => 'financial',
                'category' => 'Financial',
                'description' => 'Revenue generated, adjustments, reversals and outstanding amounts.',
                'icon' => 'bi-receipt',
                'tone' => 'amber',
                'period' => $period,
                'scope' => 'Company / role scoped',
                'view' => route('goal.mis-reports.show', array_merge($qs, ['report' => 'revenue_ledger'])),
                'export' => route('goal.mis-reports.export', array_merge($qs, ['report' => 'revenue_ledger', 'format' => 'xlsx'])),
            ],
            [
                'key' => 'payout_ageing',
                'name' => 'Payout Ageing',
                'group' => 'financial',
                'category' => 'Financial',
                'description' => 'Pending, paid and held payouts with ageing buckets from due date.',
                'icon' => 'bi-wallet2',
                'tone' => 'rose',
                'period' => $period,
                'scope' => 'Outstanding & paid',
                'view' => route('goal.mis-reports.show', array_merge($qs, ['report' => 'payout_ageing'])),
                'export' => route('goal.mis-reports.export', array_merge($qs, ['report' => 'payout_ageing', 'format' => 'xlsx'])),
            ],
            // Performance (4)
            [
                'key' => 'branch_mis',
                'name' => 'Branch MIS',
                'group' => 'performance',
                'category' => 'Performance',
                'description' => 'Branch-wise performance summary (leads, cases, disbursement, revenue and payout).',
                'icon' => 'bi-building',
                'tone' => 'green',
                'period' => $period,
                'scope' => 'All authorized branches',
                'view' => route('goal.mis-reports.show', array_merge($qs, ['report' => 'branch_mis'])),
                'export' => route('goal.mis-reports.export', array_merge($qs, ['report' => 'branch_mis', 'format' => 'xlsx'])),
            ],
            [
                'key' => 'target_vs_achievement',
                'name' => 'Target vs Achievement',
                'group' => 'performance',
                'category' => 'Performance',
                'description' => 'Compare assigned targets vs actuals by employee, branch, cluster and region.',
                'icon' => 'bi-bullseye',
                'tone' => 'purple',
                'period' => $period,
                'scope' => 'Role scoped',
                'view' => route('goal.mis-reports.show', array_merge($qs, ['report' => 'target_vs_achievement'])),
                'export' => route('goal.mis-reports.export', array_merge($qs, ['report' => 'target_vs_achievement', 'format' => 'xlsx'])),
            ],
            [
                'key' => 'hierarchy_performance',
                'name' => 'Hierarchy Performance',
                'group' => 'performance',
                'category' => 'Performance',
                'description' => 'Region → cluster → branch → employee performance roll-up.',
                'icon' => 'bi-diagram-3-fill',
                'tone' => 'cyan',
                'period' => $period,
                'scope' => 'Org hierarchy',
                'view' => route('goal.mis-reports.show', array_merge($qs, ['report' => 'hierarchy_performance'])),
                'export' => route('goal.mis-reports.export', array_merge($qs, ['report' => 'hierarchy_performance', 'format' => 'xlsx'])),
            ],
            [
                'key' => 'lender_performance',
                'name' => 'Lender Performance',
                'group' => 'performance',
                'category' => 'Performance',
                'description' => 'Bank/lender applications, sanction rate, TAT, disbursement and revenue.',
                'icon' => 'bi-bank2',
                'tone' => 'slate',
                'period' => $period,
                'scope' => 'All lenders',
                'view' => route('goal.mis-reports.show', array_merge($qs, ['report' => 'lender_performance'])),
                'export' => route('goal.mis-reports.export', array_merge($qs, ['report' => 'lender_performance', 'format' => 'xlsx'])),
            ],
        ];
    }

    public function filterOptions(User $user, array $scopeIds): array
    {
        $units = GoalOrgUnit::query()
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'parent_id']);

        $regions = $units->where('type', GoalOrgUnit::TYPE_REGION)->values();
        $clusters = $units->where('type', GoalOrgUnit::TYPE_CLUSTER)->values();
        $branches = $units->where('type', GoalOrgUnit::TYPE_BRANCH)
            ->when($scopeIds !== [] && $scopeIds !== [-1], fn ($c) => $c->whereIn('id', $scopeIds))
            ->values();

        $products = GoalLoanCase::query()
            ->when($scopeIds !== [] && $scopeIds !== [-1], fn ($q) => $q->whereIn('goal_org_unit_id', $scopeIds))
            ->whereNotNull('product')
            ->where('product', '!=', '')
            ->distinct()
            ->orderBy('product')
            ->limit(80)
            ->pluck('product');

        $owners = User::query()
            ->where('status', 1)
            ->when($scopeIds !== [] && $scopeIds !== [-1], fn ($q) => $q->whereIn('goal_org_unit_id', $scopeIds))
            ->where(function ($q) {
                $q->whereIn('crm_role_type', [8, 10, 11, 12])
                    ->orWhereIn('job_role', ['bm', 'jsm', 'sm', 'ssm', 'branch_manager']);
            })
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name']);

        return compact('regions', 'clusters', 'branches', 'products', 'owners');
    }

    public function scopeLabel(User $user, array $scopeIds, string $roleKey): string
    {
        if ($user->orgUnit?->name) {
            return $user->orgUnit->name;
        }
        if (in_array($roleKey, ['md', 'ceo'], true) || $user->isSuperAdmin()) {
            return 'Company-wide';
        }

        return 'My scope';
    }

    protected function resolveRoleKey(User $user): string
    {
        return app(GoalDashboardService::class)->resolveRoleKey($user);
    }

    protected function currentFy(): string
    {
        return app(FinancialYearService::class)->analyticsKey();
    }

    public function fyOptions(): array
    {
        $fySvc = app(FinancialYearService::class);
        $fySvc->ensureRecentYears();

        $keys = \App\Models\FinancialYear::query()
            ->orderByDesc('from_date')
            ->get()
            ->map(fn ($fy) => $fySvc->analyticsKey($fy))
            ->unique()
            ->values()
            ->all();

        if ($keys === []) {
            $cur = $this->currentFy();
            [$a, $b] = array_map('intval', explode('-', $cur));

            return [
                ($a - 1).'-'.$a,
                $cur,
                $b.'-'.($b + 1),
            ];
        }

        return $keys;
    }

    protected function inr(float $amount): string
    {
        if (method_exists(MoneyFormatter::class, 'formatIndian')) {
            return '₹ '.MoneyFormatter::formatIndian($amount, 0);
        }

        return '₹ '.number_format($amount, 0);
    }

    protected function compactInr(float $amount): string
    {
        $abs = abs($amount);
        if ($abs >= 10000000) {
            return '₹ '.number_format($amount / 10000000, 2).' Cr';
        }
        if ($abs >= 100000) {
            return '₹ '.number_format($amount / 100000, 2).' Lac';
        }

        return $this->inr($amount);
    }
}
