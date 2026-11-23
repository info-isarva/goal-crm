<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Models\GoalOrgUnit;
use App\Models\GoalTarget;
use App\Models\GoalTargetAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoalTargetController extends Controller
{
    use AuthorizesGoal;

    public function index(Request $request)
    {
        $this->requireGoalPermission('view_targets_guard');

        $actor = auth()->user();
        $perPage = (int) $request->get('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $filters = [
            'period' => trim((string) $request->get('period', '')),
            'metric' => trim((string) $request->get('metric', '')),
            'status' => trim((string) $request->get('status', '')),
            'goal_org_unit_id' => $request->get('goal_org_unit_id'),
            'owner_type' => trim((string) $request->get('owner_type', '')),
            'user_id' => $request->get('user_id'),
            'per_page' => $perPage,
        ];

        $baseQuery = GoalTarget::query()->with(['orgUnit', 'user']);
        $this->applyScope($baseQuery, $actor);
        $this->applyFilters($baseQuery, $filters);

        $targets = (clone $baseQuery)
            ->orderByDesc('period')
            ->orderBy('metric')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        $rows = (clone $baseQuery)->with(['user.orgUnit.parent.parent', 'orgUnit.parent.parent'])->get();
        $totalEffective = $rows->sum(fn (GoalTarget $t) => $t->effectiveTarget());
        $totalActual = (float) $rows->sum('actual');
        $totalClosing = (float) $rows->sum('closing_shortfall');
        $totalBase = (float) $rows->sum('base_target');
        $totalOpening = (float) $rows->sum('opening_shortfall');
        $totalAdjustments = (float) $rows->sum('adjustments');
        $stats = [
            'count' => $rows->count(),
            'approved' => $rows->where('status', 'Approved')->count(),
            'draft' => $rows->where('status', 'Draft')->count(),
            'total_effective' => $totalEffective,
            'total_actual' => $totalActual,
            'total_closing' => $totalClosing,
            'total_base' => $totalBase,
            'total_opening' => $totalOpening,
            'total_adjustments' => $totalAdjustments,
            'achievement_pct' => $totalEffective > 0
                ? round(min(100, ($totalActual / $totalEffective) * 100), 1)
                : 0,
        ];

        $chartQuery = GoalTarget::query();
        $this->applyScope($chartQuery, $actor);
        $this->applyFilters($chartQuery, array_merge($filters, ['period' => '']));
        $periodBuckets = $chartQuery
            ->selectRaw('period')
            ->selectRaw('SUM(base_target + opening_shortfall + adjustments) as effective_sum')
            ->selectRaw('SUM(actual) as actual_sum')
            ->selectRaw('SUM(closing_shortfall) as closing_sum')
            ->groupBy('period')
            ->orderBy('period')
            ->limit(12)
            ->get();

        $chart = [
            'labels' => $periodBuckets->map(function ($r) {
                try {
                    return \Carbon\Carbon::createFromFormat('Y-m', (string) $r->period)->format('M');
                } catch (\Throwable $e) {
                    return (string) $r->period;
                }
            })->values()->all(),
            'periods' => $periodBuckets->pluck('period')->values()->all(),
            'effective' => $periodBuckets->map(fn ($r) => round((float) $r->effective_sum, 2))->values()->all(),
            'actual' => $periodBuckets->map(fn ($r) => round((float) $r->actual_sum, 2))->values()->all(),
            'closing' => $periodBuckets->map(fn ($r) => round((float) $r->closing_sum, 2))->values()->all(),
            'pct' => $periodBuckets->map(function ($r) {
                $eff = (float) $r->effective_sum;
                $act = (float) $r->actual_sum;

                return $eff > 0 ? round(min(100, ($act / $eff) * 100), 1) : 0;
            })->values()->all(),
        ];

        // Achievement by region (donut)
        $regionBuckets = [];
        foreach ($rows as $t) {
            $regionName = $this->resolveRegionName($t);
            if (! isset($regionBuckets[$regionName])) {
                $regionBuckets[$regionName] = ['actual' => 0.0, 'effective' => 0.0];
            }
            $regionBuckets[$regionName]['actual'] += (float) $t->actual;
            $regionBuckets[$regionName]['effective'] += $t->effectiveTarget();
        }
        arsort($regionBuckets);
        $regionChart = [
            'labels' => array_keys($regionBuckets),
            'values' => array_map(fn ($r) => round($r['actual'], 2), array_values($regionBuckets)),
            'total_actual' => round($totalActual, 2),
        ];

        $metricQuery = GoalTarget::query();
        $this->applyScope($metricQuery, $actor);
        $this->applyFilters($metricQuery, array_merge($filters, ['metric' => '']));
        $metricSplit = $metricQuery
            ->selectRaw('metric')
            ->selectRaw('SUM(base_target + opening_shortfall + adjustments) as effective_sum')
            ->groupBy('metric')
            ->get();

        $metricChart = [
            'labels' => $metricSplit->map(fn ($r) => ucfirst((string) $r->metric))->values()->all(),
            'values' => $metricSplit->map(fn ($r) => round((float) $r->effective_sum, 2))->values()->all(),
        ];

        $scopedOrgIds = $actor->goalScopedOrgUnitIds();
        $orgUnitsQuery = GoalOrgUnit::query()
            ->active()
            ->whereIn('type', [
                GoalOrgUnit::TYPE_BRANCH,
                GoalOrgUnit::TYPE_CLUSTER,
                GoalOrgUnit::TYPE_REGION,
            ])
            ->when(! $this->isGlobalTargetViewer($actor), fn ($q) => $q->whereIn('id', $scopedOrgIds ?: [0]))
            ->orderBy('name');

        $branches = (clone $orgUnitsQuery)
            ->orderByRaw("CASE type WHEN 'region' THEN 0 WHEN 'cluster' THEN 1 ELSE 2 END")
            ->get();

        $regions = $branches->where('type', GoalOrgUnit::TYPE_REGION)->values();
        $clusters = $branches->where('type', GoalOrgUnit::TYPE_CLUSTER)->values();
        $branchUnits = $branches->where('type', GoalOrgUnit::TYPE_BRANCH)->values();

        $teamUsers = $this->manageableTeamUsers($actor);
        $periods = GoalTarget::query()
            ->tap(fn ($q) => $this->applyScope($q, $actor))
            ->distinct()
            ->orderByDesc('period')
            ->pluck('period');

        // Ensure current + next 2 months appear even with no rows yet
        $extraPeriods = collect(range(0, 2))->map(fn ($i) => now()->startOfMonth()->addMonths($i)->format('Y-m'));
        $periods = $periods->merge($extraPeriods)->unique()->sortDesc()->values();

        $insights = [
            'employees' => max($teamUsers->count(), $rows->whereNotNull('user_id')->pluck('user_id')->unique()->count()),
            'regions' => $regions->count(),
            'clusters' => $clusters->count(),
            'branches' => $branchUnits->count(),
        ];

        $recentActivity = GoalTargetAuditLog::query()
            ->with(['employee:id,name', 'changer:id,name'])
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(function (GoalTargetAuditLog $l) {
                $action = (string) ($l->action ?? 'update');
                $title = match ($action) {
                    'assign' => 'Target assigned to '.($l->employee?->name ?? 'team member'),
                    'revision_request' => 'Revision requested for '.($l->employee?->name ?? 'target'),
                    default => 'Target updated for '.($l->employee?->name ?? 'team member'),
                };

                return [
                    'title' => $title,
                    'by' => $l->changer?->name ?? 'System',
                    'at' => optional($l->created_at)->diffForHumans() ?? '',
                    'action' => $action,
                ];
            })
            ->values()
            ->all();

        $canManage = $actor->hasCrmPermission('manage_targets_guard');
        $canApprove = $this->canApproveTargets($actor);

        $allocationTree = $this->buildAllocationTree(
            $branches,
            $rows,
            $filters['goal_org_unit_id'] ?? null
        );

        return view('goal.targets.index', compact(
            'targets',
            'branches',
            'regions',
            'clusters',
            'branchUnits',
            'teamUsers',
            'periods',
            'filters',
            'stats',
            'chart',
            'metricChart',
            'regionChart',
            'insights',
            'recentActivity',
            'allocationTree',
            'canManage',
            'canApprove'
        ));
    }

    /**
     * Hierarchical Region → Cluster → Branch → Employee rows for the allocation table.
     *
     * @param  \Illuminate\Support\Collection<int, GoalOrgUnit>  $orgUnits
     * @param  \Illuminate\Support\Collection<int, GoalTarget>  $rows
     * @return list<array<string, mixed>>
     */
    protected function buildAllocationTree($orgUnits, $rows, $filterOrgId = null): array
    {
        $unitsById = $orgUnits->keyBy(fn (GoalOrgUnit $u) => (int) $u->id);
        $byParent = $orgUnits->groupBy(fn (GoalOrgUnit $u) => (int) ($u->parent_id ?: 0));

        $empByOrg = [];
        $orgOwn = [];
        foreach ($rows as $t) {
            if ($t->user_id) {
                $oid = (int) ($t->user?->goal_org_unit_id ?: 0);
                if ($oid <= 0) {
                    continue;
                }
                $empByOrg[$oid][] = $this->allocationLeafFromTarget($t, 'employee');
            } elseif ($t->goal_org_unit_id) {
                $orgOwn[(int) $t->goal_org_unit_id] = $this->allocationMetricsFromTarget($t);
            }
        }

        $build = function (GoalOrgUnit $unit) use (&$build, $byParent, $empByOrg, $orgOwn): array {
            $children = [];
            foreach ($byParent->get((int) $unit->id, collect()) as $child) {
                $children[] = $build($child);
            }
            if ($unit->type === GoalOrgUnit::TYPE_BRANCH) {
                foreach ($empByOrg[(int) $unit->id] ?? [] as $emp) {
                    $children[] = $emp;
                }
            }

            return $this->rollupAllocationNode([
                'key' => 'org-'.$unit->id,
                'name' => $unit->name.($unit->code ? ' ('.$unit->code.')' : ''),
                'type' => (string) $unit->type,
                'children' => $children,
            ], $orgOwn[(int) $unit->id] ?? null);
        };

        if ($filterOrgId && $unitsById->has((int) $filterOrgId)) {
            return [$build($unitsById->get((int) $filterOrgId))];
        }

        $roots = $orgUnits->where('type', GoalOrgUnit::TYPE_REGION)->values();
        if ($roots->isEmpty()) {
            $roots = $orgUnits->filter(function (GoalOrgUnit $u) use ($unitsById) {
                $pid = (int) ($u->parent_id ?: 0);

                return $pid === 0 || ! $unitsById->has($pid);
            })->values();
        }

        return $roots->map(fn (GoalOrgUnit $u) => $build($u))->values()->all();
    }

    /**
     * @return array{base: float, opening: float, adjustments: float, actual: float, closing: float, target_id: int|null, period: string|null, metric: string|null, status: string|null, user_id: int|null, org_id: int|null}
     */
    protected function allocationMetricsFromTarget(GoalTarget $t): array
    {
        return [
            'base' => (float) $t->base_target,
            'opening' => (float) $t->opening_shortfall,
            'adjustments' => (float) $t->adjustments,
            'actual' => (float) $t->actual,
            'closing' => (float) $t->closing_shortfall,
            'target_id' => (int) $t->id,
            'period' => (string) $t->period,
            'metric' => (string) $t->metric,
            'status' => (string) $t->status,
            'user_id' => $t->user_id ? (int) $t->user_id : null,
            'org_id' => $t->goal_org_unit_id ? (int) $t->goal_org_unit_id : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function allocationLeafFromTarget(GoalTarget $t, string $type): array
    {
        $m = $this->allocationMetricsFromTarget($t);
        $effective = $m['base'] + $m['opening'] + $m['adjustments'];
        $pct = $effective > 0 ? round(min(100, ($m['actual'] / $effective) * 100), 1) : 0.0;

        return [
            'key' => 'user-'.$t->user_id.'-'.$t->id,
            'name' => $t->user?->name ?? ('User #'.$t->user_id),
            'type' => $type,
            'meta' => $t->period.' · '.ucfirst((string) $t->metric),
            'base' => $m['base'],
            'opening' => $m['opening'],
            'adjustments' => $m['adjustments'],
            'effective' => round($effective, 2),
            'actual' => $m['actual'],
            'closing' => $m['closing'],
            'pct' => $pct,
            'children' => [],
            'target_id' => $m['target_id'],
            'period' => $m['period'],
            'metric' => $m['metric'],
            'status' => $m['status'],
            'user_id' => $m['user_id'],
            'org_id' => $m['org_id'],
            'owner_type' => 'employee',
        ];
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>|null  $own
     * @return array<string, mixed>
     */
    protected function rollupAllocationNode(array $node, ?array $own = null): array
    {
        $children = $node['children'] ?? [];
        if ($own) {
            $base = $own['base'];
            $opening = $own['opening'];
            $adj = $own['adjustments'];
            $actual = $own['actual'];
            $closing = $own['closing'];
            $node['target_id'] = $own['target_id'];
            $node['period'] = $own['period'];
            $node['metric'] = $own['metric'];
            $node['status'] = $own['status'];
            $node['user_id'] = $own['user_id'];
            $node['org_id'] = $own['org_id'];
            $node['owner_type'] = 'org_unit';
        } else {
            $base = $opening = $adj = $actual = $closing = 0.0;
            foreach ($children as $c) {
                $base += (float) ($c['base'] ?? 0);
                $opening += (float) ($c['opening'] ?? 0);
                $adj += (float) ($c['adjustments'] ?? 0);
                $actual += (float) ($c['actual'] ?? 0);
                $closing += (float) ($c['closing'] ?? 0);
            }
            $node['owner_type'] = 'org_unit';
            $node['target_id'] = null;
        }

        $effective = round($base + $opening + $adj, 2);
        $pct = $effective > 0 ? round(min(100, ($actual / $effective) * 100), 1) : 0.0;

        $counts = ['cluster' => 0, 'branch' => 0, 'employee' => 0];
        $countWalk = function (array $nodes) use (&$countWalk, &$counts): void {
            foreach ($nodes as $n) {
                $t = (string) ($n['type'] ?? '');
                if (isset($counts[$t])) {
                    $counts[$t]++;
                }
                if (! empty($n['children'])) {
                    $countWalk($n['children']);
                }
            }
        };
        $countWalk($children);
        $metaParts = [];
        if ($counts['cluster']) {
            $metaParts[] = $counts['cluster'].' Cluster'.($counts['cluster'] > 1 ? 's' : '');
        }
        if ($counts['branch']) {
            $metaParts[] = $counts['branch'].' Branch'.($counts['branch'] > 1 ? 'es' : '');
        }
        if ($counts['employee']) {
            $metaParts[] = $counts['employee'].' Emp';
        }

        $node['base'] = round($base, 2);
        $node['opening'] = round($opening, 2);
        $node['adjustments'] = round($adj, 2);
        $node['effective'] = $effective;
        $node['actual'] = round($actual, 2);
        $node['closing'] = round($closing, 2);
        $node['pct'] = $pct;
        $node['meta'] = implode(' · ', $metaParts);
        $node['children'] = $children;

        return $node;
    }

    protected function resolveRegionName(GoalTarget $t): string
    {
        $unit = $t->orgUnit;
        if (! $unit && $t->user) {
            $unit = $t->user->orgUnit;
        }
        if (! $unit) {
            return 'Unassigned';
        }

        $cursor = $unit;
        $guard = 0;
        while ($cursor && $guard < 6) {
            if ($cursor->type === GoalOrgUnit::TYPE_REGION) {
                return $cursor->name;
            }
            $cursor = $cursor->parent;
            $guard++;
        }

        return $unit->name;
    }

    public function export(Request $request): StreamedResponse
    {
        $this->requireGoalPermission('view_targets_guard');

        $actor = auth()->user();
        $filters = [
            'period' => trim((string) $request->get('period', '')),
            'metric' => trim((string) $request->get('metric', '')),
            'status' => trim((string) $request->get('status', '')),
            'goal_org_unit_id' => $request->get('goal_org_unit_id'),
            'owner_type' => trim((string) $request->get('owner_type', '')),
            'user_id' => $request->get('user_id'),
        ];

        $query = GoalTarget::query()->with(['orgUnit', 'user']);
        $this->applyScope($query, $actor);
        $this->applyFilters($query, $filters);
        $rows = $query->orderByDesc('period')->orderBy('metric')->limit(5000)->get();
        $filename = 'targets-carry-forward-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'Period', 'Metric', 'Owner', 'Owner Type',
                'Base Target', 'Opening Shortfall', 'Adjustments', 'Effective',
                'Actual', 'Closing Shortfall', 'Status',
            ]);
            foreach ($rows as $t) {
                $ownerType = $t->user_id ? 'employee' : 'org_unit';
                $ownerLabel = $t->user_id
                    ? ($t->user?->name ?? ('User #'.$t->user_id))
                    : ($t->orgUnit?->name ?? '');
                fputcsv($out, [
                    $t->period,
                    $t->metric,
                    $ownerLabel,
                    $ownerType,
                    $t->base_target,
                    $t->opening_shortfall,
                    $t->adjustments,
                    $t->effectiveTarget(),
                    $t->actual,
                    $t->closing_shortfall,
                    $t->status,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(Request $request)
    {
        $this->requireGoalPermission('manage_targets_guard');
        $actor = auth()->user();
        $data = $this->validatedTargetPayload($request, $actor);

        $this->assertUniqueOwnerPeriodMetric($data);

        $target = new GoalTarget($data);
        $opening = (float) ($data['opening_shortfall'] ?? 0);
        $target->opening_shortfall = $opening > 0 ? $opening : $this->priorClosingShortfall($data);
        $target->adjustments = $data['adjustments'] ?? 0;
        $target->actual = $data['actual'] ?? 0;
        $target->status = $this->resolveStatus($data['status'] ?? 'Draft', $actor);
        $target->recalculateClosingShortfall();
        $target->save();

        $this->maybeSyncUserSalesTarget($target);

        return redirect()
            ->route('goal.targets.index')
            ->with('success', 'Target created. Effective ₹' . number_format($target->effectiveTarget(), 2)
                . ' · Closing shortfall ₹' . number_format((float) $target->closing_shortfall, 2));
    }

    public function update(Request $request, GoalTarget $target)
    {
        $this->requireGoalPermission('manage_targets_guard');
        $actor = auth()->user();
        $this->assertCanAccessTarget($actor, $target);

        $data = $this->validatedTargetPayload($request, $actor, $target);
        $this->assertUniqueOwnerPeriodMetric($data, $target->id);

        $target->fill($data);
        $target->opening_shortfall = $data['opening_shortfall'] ?? 0;
        $target->adjustments = $data['adjustments'] ?? 0;
        $target->actual = $data['actual'] ?? 0;
        $target->status = $this->resolveStatus($data['status'] ?? $target->status, $actor, $target);
        $target->recalculateClosingShortfall();
        $target->save();

        $this->maybeSyncUserSalesTarget($target);

        return redirect()
            ->route('goal.targets.index')
            ->with('success', 'Target updated. Effective ₹' . number_format($target->effectiveTarget(), 2)
                . ' · Closing shortfall ₹' . number_format((float) $target->closing_shortfall, 2));
    }

    protected function isGlobalTargetViewer(?User $user = null): bool
    {
        $user = $user ?: auth()->user();

        return $user->isSuperAdmin() || in_array((int) ($user->crm_role_type ?? -1), [1, 4, 5], true);
    }

    protected function canApproveTargets(?User $user = null): bool
    {
        $user = $user ?: auth()->user();
        // Super Admin / Admin / MD / CEO / RD / Cluster can approve.
        return $user->isSuperAdmin() || in_array((int) ($user->crm_role_type ?? -1), [1, 4, 5, 6, 7], true);
    }

    protected function manageableTeamUsers(User $actor)
    {
        if ($this->isGlobalTargetViewer($actor)) {
            return User::query()
                ->where('status', 1)
                ->where('crm_role_type', '!=', 0)
                ->orderBy('name')
                ->get(['id', 'name', 'job_role', 'crm_role_type', 'goal_org_unit_id']);
        }

        $orgIds = $actor->goalScopedOrgUnitIds();

        return User::query()
            ->where('status', 1)
            ->where('id', '!=', $actor->id)
            ->where(function ($q) use ($actor, $orgIds) {
                $q->where('reports_to_user_id', $actor->id)
                    ->orWhere('assign_manager', $actor->id);
                if (!empty($orgIds)) {
                    $q->orWhereIn('goal_org_unit_id', $orgIds);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'job_role', 'crm_role_type', 'goal_org_unit_id']);
    }

    protected function applyScope($query, User $actor): void
    {
        if ($this->isGlobalTargetViewer($actor)) {
            return;
        }

        $orgIds = $actor->goalScopedOrgUnitIds();
        $teamIds = $this->manageableTeamUsers($actor)->pluck('id')->all();

        $query->where(function ($q) use ($orgIds, $teamIds, $actor) {
            if (!empty($orgIds)) {
                $q->whereIn('goal_org_unit_id', $orgIds);
            } else {
                $q->whereRaw('1 = 0');
            }
            if (!empty($teamIds)) {
                $q->orWhereIn('user_id', $teamIds);
            }
            // Allow viewing own employee targets if any.
            $q->orWhere('user_id', $actor->id);
        });
    }

    protected function applyFilters($query, array $filters): void
    {
        if ($filters['period'] !== '') {
            $query->where('period', $filters['period']);
        }
        if ($filters['metric'] !== '' && in_array($filters['metric'], ['revenue', 'disbursement'], true)) {
            $query->where('metric', $filters['metric']);
        }
        if ($filters['status'] !== '' && in_array($filters['status'], ['Draft', 'Approved'], true)) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['goal_org_unit_id'])) {
            $unit = GoalOrgUnit::query()->find((int) $filters['goal_org_unit_id']);
            $orgIds = $unit ? $unit->descendantIds() : [(int) $filters['goal_org_unit_id']];
            $query->where(function ($q) use ($orgIds) {
                $q->whereIn('goal_org_unit_id', $orgIds)
                    ->orWhereHas('user', fn ($uq) => $uq->whereIn('goal_org_unit_id', $orgIds));
            });
        }
        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (($filters['owner_type'] ?? '') === 'employee') {
            $query->whereNotNull('user_id')->whereNull('goal_org_unit_id');
        } elseif (($filters['owner_type'] ?? '') === 'org_unit') {
            $query->whereNotNull('goal_org_unit_id')->whereNull('user_id');
        }
    }

    protected function validatedTargetPayload(Request $request, User $actor, ?GoalTarget $existing = null): array
    {
        $ownerType = (string) $request->input('owner_type', '');
        $orgUnitId = $request->input('goal_org_unit_id');
        $orgUnitId = ($orgUnitId === '' || $orgUnitId === null) ? null : (int) $orgUnitId;
        $userId = $request->input('user_id');
        $userId = ($userId === '' || $userId === null) ? null : (int) $userId;

        $data = $request->validate([
            'owner_type' => ['required', 'in:employee,org_unit'],
            'period' => [
                'required',
                'string',
                'max:7',
                'regex:/^\d{4}-(0[1-9]|1[0-2])$/',
            ],
            'metric' => ['required', 'in:revenue,disbursement'],
            'goal_org_unit_id' => ['nullable', 'exists:goal_org_units,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'base_target' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'opening_shortfall' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'adjustments' => ['nullable', 'numeric', 'max:999999999999.99'],
            'actual' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'status' => ['nullable', 'in:Draft,Approved'],
        ], [
            'owner_type.required' => 'Select whether the target is for a team member or an org unit.',
            'owner_type.in' => 'Owner must be either Employee or Org unit.',
            'period.required' => 'Period is required (YYYY-MM).',
            'period.regex' => 'Period must be in YYYY-MM format (e.g. 2026-11).',
            'metric.required' => 'Please select a metric.',
            'base_target.required' => 'Base target is required.',
            'base_target.min' => 'Base target cannot be negative.',
            'opening_shortfall.min' => 'Opening shortfall cannot be negative.',
            'actual.min' => 'Actual cannot be negative.',
        ]);

        if ($ownerType === 'employee') {
            if (!$userId) {
                throw ValidationException::withMessages(['user_id' => 'Please select a team member.']);
            }
            $orgUnitId = null;
            $allowedTeamIds = $this->manageableTeamUsers($actor)->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (!$this->isGlobalTargetViewer($actor) && !in_array($userId, $allowedTeamIds, true)) {
                throw ValidationException::withMessages(['user_id' => 'You can only set targets for people in your team / branch scope.']);
            }
        } else {
            if (!$orgUnitId) {
                throw ValidationException::withMessages(['goal_org_unit_id' => 'Please select a region, cluster, or branch.']);
            }
            $userId = null;
            $unit = GoalOrgUnit::query()->find($orgUnitId);
            if (! $unit || ! in_array($unit->type, [GoalOrgUnit::TYPE_BRANCH, GoalOrgUnit::TYPE_CLUSTER, GoalOrgUnit::TYPE_REGION], true)) {
                throw ValidationException::withMessages(['goal_org_unit_id' => 'Select a valid region, cluster, or branch.']);
            }
            $scoped = $actor->goalScopedOrgUnitIds();
            if (!$this->isGlobalTargetViewer($actor) && !in_array($orgUnitId, array_map('intval', $scoped), true)) {
                throw ValidationException::withMessages(['goal_org_unit_id' => 'You can only set targets for org units in your scope.']);
            }
        }

        $data['goal_org_unit_id'] = $orgUnitId;
        $data['user_id'] = $userId;
        unset($data['owner_type']);

        return $data;
    }

    protected function assertUniqueOwnerPeriodMetric(array $data, ?int $ignoreId = null): void
    {
        $q = GoalTarget::query()
            ->where('period', $data['period'])
            ->where('metric', $data['metric']);

        if (!empty($data['user_id'])) {
            $q->where('user_id', $data['user_id'])->whereNull('goal_org_unit_id');
        } else {
            $q->where('goal_org_unit_id', $data['goal_org_unit_id'])->whereNull('user_id');
        }

        if ($ignoreId) {
            $q->where('id', '!=', $ignoreId);
        }

        if ($q->exists()) {
            throw ValidationException::withMessages([
                'period' => 'An approved/existing target already exists for this owner, period and metric.',
            ]);
        }
    }

    protected function assertCanAccessTarget(User $actor, GoalTarget $target): void
    {
        if ($this->isGlobalTargetViewer($actor)) {
            return;
        }

        $orgIds = array_map('intval', $actor->goalScopedOrgUnitIds());
        $teamIds = $this->manageableTeamUsers($actor)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $ok = false;
        if ($target->goal_org_unit_id && in_array((int) $target->goal_org_unit_id, $orgIds, true)) {
            $ok = true;
        }
        if ($target->user_id && (in_array((int) $target->user_id, $teamIds, true) || (int) $target->user_id === (int) $actor->id)) {
            $ok = true;
        }

        if (!$ok) {
            abort(403, 'You cannot manage this target.');
        }
    }

    protected function resolveStatus(?string $requested, User $actor, ?GoalTarget $existing = null): string
    {
        $requested = $requested ?: ($existing->status ?? 'Draft');
        if ($requested === 'Approved' && !$this->canApproveTargets($actor)) {
            return 'Draft';
        }

        return in_array($requested, ['Draft', 'Approved'], true) ? $requested : 'Draft';
    }

    protected function priorClosingShortfall(array $data): float
    {
        [$y, $m] = array_map('intval', explode('-', $data['period']));
        $m--;
        if ($m < 1) {
            $m = 12;
            $y--;
        }
        $prev = sprintf('%04d-%02d', $y, $m);

        $q = GoalTarget::query()
            ->where('period', $prev)
            ->where('metric', $data['metric']);

        if (!empty($data['user_id'])) {
            $q->where('user_id', $data['user_id'])->whereNull('goal_org_unit_id');
        } else {
            $q->where('goal_org_unit_id', $data['goal_org_unit_id'])->whereNull('user_id');
        }

        $prevTarget = $q->first();

        return $prevTarget ? (float) $prevTarget->closing_shortfall : 0.0;
    }

    /**
     * Keep legacy users.sales_target in sync for current-month employee revenue targets.
     */
    protected function maybeSyncUserSalesTarget(GoalTarget $target): void
    {
        if (!$target->user_id || $target->metric !== 'revenue') {
            return;
        }
        if ($target->period !== now()->format('Y-m')) {
            return;
        }

        User::query()->where('id', $target->user_id)->update([
            'sales_target' => $target->base_target,
        ]);
    }
}
