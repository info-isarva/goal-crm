<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Models\GoalOrgUnit;
use App\Models\GoalTarget;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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

        $rows = (clone $baseQuery)->get();
        $totalEffective = $rows->sum(fn (GoalTarget $t) => $t->effectiveTarget());
        $totalActual = (float) $rows->sum('actual');
        $totalClosing = (float) $rows->sum('closing_shortfall');
        $stats = [
            'count' => $rows->count(),
            'approved' => $rows->where('status', 'Approved')->count(),
            'draft' => $rows->where('status', 'Draft')->count(),
            'total_effective' => $totalEffective,
            'total_actual' => $totalActual,
            'total_closing' => $totalClosing,
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
            ->limit(8)
            ->get();

        $chart = [
            'labels' => $periodBuckets->pluck('period')->values()->all(),
            'effective' => $periodBuckets->map(fn ($r) => round((float) $r->effective_sum, 2))->values()->all(),
            'actual' => $periodBuckets->map(fn ($r) => round((float) $r->actual_sum, 2))->values()->all(),
            'closing' => $periodBuckets->map(fn ($r) => round((float) $r->closing_sum, 2))->values()->all(),
        ];

        $metricQuery = GoalTarget::query();
        $this->applyScope($metricQuery, $actor);
        $metricQuery
            ->when($filters['period'] !== '', fn ($q) => $q->where('period', $filters['period']))
            ->when($filters['status'] !== '', fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['goal_org_unit_id'], fn ($q) => $q->where('goal_org_unit_id', $filters['goal_org_unit_id']))
            ->when($filters['user_id'], fn ($q) => $q->where('user_id', $filters['user_id']));
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
        $branches = GoalOrgUnit::query()
            ->ofType('branch')
            ->active()
            ->when(!$this->isGlobalTargetViewer($actor), fn ($q) => $q->whereIn('id', $scopedOrgIds ?: [0]))
            ->orderBy('name')
            ->get();

        $teamUsers = $this->manageableTeamUsers($actor);
        $periods = GoalTarget::query()
            ->tap(fn ($q) => $this->applyScope($q, $actor))
            ->distinct()
            ->orderByDesc('period')
            ->pluck('period');

        $canManage = $actor->hasCrmPermission('manage_targets_guard');
        $canApprove = $this->canApproveTargets($actor);

        return view('goal.targets.index', compact(
            'targets',
            'branches',
            'teamUsers',
            'periods',
            'filters',
            'stats',
            'chart',
            'metricChart',
            'canManage',
            'canApprove'
        ));
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
        if (!empty($filters['goal_org_unit_id'])) {
            $query->where('goal_org_unit_id', $filters['goal_org_unit_id']);
        }
        if (!empty($filters['user_id'])) {
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
            'owner_type.required' => 'Select whether the target is for a team member or a branch.',
            'owner_type.in' => 'Owner must be either Employee or Branch.',
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
                throw ValidationException::withMessages(['goal_org_unit_id' => 'Please select a branch.']);
            }
            $userId = null;
            $scoped = $actor->goalScopedOrgUnitIds();
            if (!$this->isGlobalTargetViewer($actor) && !in_array($orgUnitId, array_map('intval', $scoped), true)) {
                throw ValidationException::withMessages(['goal_org_unit_id' => 'You can only set targets for branches in your scope.']);
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
