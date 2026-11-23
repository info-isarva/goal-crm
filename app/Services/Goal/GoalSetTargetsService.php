<?php

namespace App\Services\Goal;

use App\Helpers\ActivityLogger;
use App\Models\GoalTarget;
use App\Models\GoalTargetAuditLog;
use App\Models\GoalTargetRoleWeight;
use App\Models\User;
use App\Services\Goal\GoalNotify;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoalSetTargetsService
{
    /**
     * Super Admin / Admin / MD / CEO do not receive personal targets.
     */
    public function isExemptFromTargets(User $user): bool
    {
        $role = (int) ($user->crm_role_type ?? -1);
        $exemptRoles = array_map('intval', (array) config('goal.target_exempt_crm_roles', [0, 1, 4, 5]));
        if (in_array($role, $exemptRoles, true)) {
            return true;
        }

        $job = strtolower(trim((string) ($user->job_role ?? '')));
        $exemptJobs = array_map('strtolower', (array) config('goal.target_exempt_job_roles', ['ceo', 'md', 'admin']));

        return $job !== '' && in_array($job, $exemptJobs, true);
    }

    public function actorReceivesTarget(User $actor): bool
    {
        return ! $this->isExemptFromTargets($actor);
    }

    /**
     * Employee IDs the actor may SET/EDIT (subtree excluding self and target-exempt roles).
     *
     * @return list<int>
     */
    public function editableEmployeeIds(User $actor): array
    {
        $team = $actor->goalTeamUserIds();
        $self = (int) $actor->id;
        $ids = array_values(array_filter(
            array_map('intval', $team),
            static fn (int $id) => $id !== $self
        ));

        if ($ids === []) {
            return [];
        }

        $exemptIds = User::query()
            ->whereIn('id', $ids)
            ->get(['id', 'crm_role_type', 'job_role'])
            ->filter(fn (User $u) => $this->isExemptFromTargets($u))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $exemptLookup = array_flip($exemptIds);

        return array_values(array_filter($ids, static fn (int $id) => ! isset($exemptLookup[$id])));
    }

    public function assertCanEdit(User $actor, int $employeeId): void
    {
        if ($employeeId === (int) $actor->id) {
            throw ValidationException::withMessages([
                'employee_id' => 'You cannot set or edit your own target.',
            ]);
        }

        $employee = User::query()->find($employeeId);
        if ($employee && $this->isExemptFromTargets($employee)) {
            throw ValidationException::withMessages([
                'employee_id' => 'MD and CEO do not receive targets. Set targets for Regional Directors and below.',
            ]);
        }

        if (! in_array($employeeId, $this->editableEmployeeIds($actor), true)) {
            throw ValidationException::withMessages([
                'employee_id' => 'You can only set targets for employees in your reporting line.',
            ]);
        }
    }

    public function defaultPeriod(): string
    {
        return now()->startOfMonth()->addMonth()->format('Y-m');
    }

    public function previousPeriod(string $period): string
    {
        return \Carbon\Carbon::createFromFormat('Y-m', $period)->subMonth()->format('Y-m');
    }

    /**
     * Opening shortfall for the next period = previous closing shortfall
     * (falls back to previous effective − actual when closing was never stored).
     */
    protected function openingFromPrevious(?GoalTarget $prev): float
    {
        if (! $prev) {
            return 0.0;
        }

        $closing = (float) ($prev->closing_shortfall ?? 0);
        if ($closing > 0) {
            return round($closing, 2);
        }

        // Legacy rows: compute closing from effective − actual
        $effective = (float) $prev->base_target
            + (float) ($prev->opening_shortfall ?? 0)
            + (float) ($prev->adjustments ?? 0);
        $actual = (float) ($prev->actual ?? 0);

        return round(max(0, $effective - $actual), 2);
    }

    /**
     * @return array{period: string, prev_period: string, metric: string, unit: string, jump_pct: float, self: array, tree: list<array>, checks: list<array>, can_assign: bool}
     */
    public function tree(User $actor, string $period, string $metric = 'revenue'): array
    {
        $period = $this->normalizePeriod($period);
        $metric = $this->normalizeMetric($metric);
        $prev = $this->previousPeriod($period);
        $editable = array_flip($this->editableEmployeeIds($actor));

        $users = $this->loadSubtreeUsers($actor);
        $byParent = $users->groupBy(fn (User $u) => (int) ($u->reports_to_user_id ?: $u->assign_manager ?: 0));

        $targetMap = $this->targetsMap($period, $metric, $users->pluck('id')->all());
        $prevMap = $this->targetsMap($prev, $metric, $users->pluck('id')->all());

        // Prefer direct reports; if actor is SA/MD/CEO (or reports to an exempt parent),
        // roots are people whose manager is not in the editable set (typically Regional Directors).
        $roots = $byParent->get((int) $actor->id, collect());
        if ($roots->isEmpty() || $this->isExemptFromTargets($actor)) {
            $roots = $users->filter(function (User $u) use ($users) {
                $pid = (int) ($u->reports_to_user_id ?: $u->assign_manager ?: 0);

                return $pid === 0 || ! $users->contains('id', $pid);
            })->values();
        }

        $tree = [];
        foreach ($roots as $child) {
            $tree[] = $this->buildNode($child, $byParent, $targetMap, $prevMap, $editable, 0);
        }

        $selfTargetMap = $this->targetsMap($period, $metric, [(int) $actor->id]);
        $selfPrevMap = $this->targetsMap($prev, $metric, [(int) $actor->id]);
        $selfTarget = $selfTargetMap->get((int) $actor->id);
        $selfPrev = $selfPrevMap->get((int) $actor->id);

        $receivesTarget = $this->actorReceivesTarget($actor);
        $mySet = $receivesTarget ? (float) ($selfTarget->base_target ?? 0) : 0.0;
        $myOwner = $receivesTarget ? (float) ($selfTarget->owner_target ?? 0) : 0.0;
        $remainder = max(0, round($mySet - $myOwner, 2));
        $distributed = array_sum(array_map(static fn ($n) => (float) ($n['set_target'] ?? 0), $tree));
        $left = round($remainder - $distributed, 2);

        // Cascade rule: you only must finish YOUR direct reports.
        // Deeper remainders (e.g. RD → Cluster) are set later by that manager.
        $checks = [];
        if ($receivesTarget) {
            $checks[] = [
                'id' => (int) $actor->id,
                'label' => sprintf(
                    'Your remainder: distributed %s of %s · left %s %s',
                    $this->n($distributed),
                    $this->n($remainder),
                    $this->n($left),
                    abs($left) <= 0.05 ? '✓' : '✗'
                ),
                'ok' => abs($left) <= 0.05,
                'blocks_assign' => true,
            ];
        }

        $pending = $this->collectChecks($tree);
        $hasOver = collect($pending)->contains(fn ($c) => ($c['over'] ?? false) === true);
        $canAssign = count($tree) > 0 && ! $hasOver;
        if ($receivesTarget) {
            $canAssign = $canAssign && abs($left) <= 0.05;
        }

        return [
            'period' => $period,
            'prev_period' => $prev,
            'metric' => $metric,
            'unit' => (string) config('goal.target_unit_label', '₹'),
            'jump_pct' => (float) config('goal.target_jump_pct', 15),
            'self' => [
                'id' => (int) $actor->id,
                'name' => $actor->name,
                'role' => $this->roleLabel($actor),
                'receives_target' => $receivesTarget,
                'set_target' => $mySet,
                'owner_target' => $myOwner,
                'remainder' => $remainder,
                'distributed' => $distributed,
                'left_to_distribute' => $left,
                // Opening shortfall for this period = previous period's closing shortfall
                'last_month' => $receivesTarget ? $this->openingFromPrevious($selfPrev) : 0.0,
                'status' => (string) ($selfTarget->status ?? GoalTarget::STATUS_DRAFT),
                'editable' => false,
            ],
            'tree' => $tree,
            'checks' => $checks,
            'pending' => $pending,
            'can_assign' => $canAssign,
            'split_options' => [
                'by_last_month' => "By last month's target",
                'equal' => 'Equal split',
                'custom_pct' => 'Custom %',
                'role_weights' => 'Role weights (set by admin)',
            ],
        ];
    }

    /**
     * @param  list<array{employee_id:int, set_target:float, owner_target?:float, row_source?:string}>  $rows
     * @return array{saved: int, checks: list<array>}
     */
    public function saveDraft(User $actor, string $period, string $metric, array $rows, bool $assign = false): array
    {
        $period = $this->normalizePeriod($period);
        $metric = $this->normalizeMetric($metric);
        $editable = $this->editableEmployeeIds($actor);

        if ($rows === []) {
            throw ValidationException::withMessages(['rows' => 'No target rows to save.']);
        }

        return DB::transaction(function () use ($actor, $period, $metric, $rows, $assign, $editable) {
            $saved = 0;
            foreach ($rows as $row) {
                $employeeId = (int) ($row['employee_id'] ?? 0);
                $this->assertCanEdit($actor, $employeeId);
                if (! in_array($employeeId, $editable, true)) {
                    throw ValidationException::withMessages([
                        'rows' => "Permission denied for employee #{$employeeId}.",
                    ]);
                }

                $employee = User::query()->find($employeeId);
                $set = round((float) ($row['set_target'] ?? 0), 2);
                $owner = round((float) ($row['owner_target'] ?? 0), 2);
                // Staff (no reports): whole target is personal.
                $hasReports = $employee && User::query()
                    ->where(function ($q) use ($employeeId) {
                        $q->where('reports_to_user_id', $employeeId)
                            ->orWhere('assign_manager', $employeeId);
                    })
                    ->exists();
                if ($employee && ! $hasReports) {
                    $owner = $set;
                }
                if ($owner < 0 || $set < 0) {
                    throw ValidationException::withMessages(['rows' => 'Targets cannot be negative.']);
                }
                if ($owner - $set > 0.009) {
                    throw ValidationException::withMessages([
                        'rows' => "Owner target cannot exceed set target for employee #{$employeeId}.",
                    ]);
                }

                $source = (($row['row_source'] ?? '') === 'edited') ? 'edited' : 'auto';
                $existing = GoalTarget::query()
                    ->where('period', $period)
                    ->where('metric', $metric)
                    ->where('user_id', $employeeId)
                    ->whereNull('goal_org_unit_id')
                    ->first();

                if ($existing && $existing->isLocked() && ! $assign) {
                    throw ValidationException::withMessages([
                        'rows' => "Target for employee #{$employeeId} is locked. Request revision first.",
                    ]);
                }

                $oldBase = (float) ($existing->base_target ?? 0);
                $oldOwner = (float) ($existing->owner_target ?? 0);
                $status = $assign ? GoalTarget::STATUS_APPROVED : GoalTarget::STATUS_DRAFT;

                $prevPeriod = $this->previousPeriod($period);
                $prevTarget = GoalTarget::query()
                    ->where('period', $prevPeriod)
                    ->where('metric', $metric)
                    ->where('user_id', $employeeId)
                    ->whereNull('goal_org_unit_id')
                    ->first();
                $opening = $existing
                    ? (float) ($existing->opening_shortfall ?? 0)
                    : $this->openingFromPrevious($prevTarget);
                if ($existing && $opening <= 0) {
                    $opening = $this->openingFromPrevious($prevTarget);
                }

                $payload = [
                    'period' => $period,
                    'metric' => $metric,
                    'user_id' => $employeeId,
                    'goal_org_unit_id' => null,
                    'base_target' => $set,
                    'owner_target' => $owner,
                    'opening_shortfall' => $opening,
                    'row_source' => $source,
                    'set_by' => $actor->id,
                    'status' => $status,
                    'approved_by' => $assign ? $actor->id : ($existing->approved_by ?? null),
                    'assigned_at' => $assign ? now() : ($existing->assigned_at ?? null),
                ];

                if ($existing) {
                    $existing->fill($payload);
                    $existing->recalculateClosingShortfall();
                    $existing->save();
                    $target = $existing;
                } else {
                    $target = GoalTarget::create($payload);
                    $target->recalculateClosingShortfall();
                    $target->save();
                }

                if (abs($oldBase - $set) > 0.009 || abs($oldOwner - $owner) > 0.009) {
                    $this->audit($target, $actor, 'base_target', $oldBase, $set, $assign ? 'assign' : 'update', [
                        'owner_old' => $oldOwner,
                        'owner_new' => $owner,
                    ]);
                }

                if ($assign) {
                    $employee = User::query()->find($employeeId);
                    GoalNotify::send(
                        $employee,
                        'Target assigned',
                        'Your '.$metric.' target for '.$period.' was set to '.number_format($set, 2).'.',
                        route('goal.targets.set'),
                        'target_assigned_'.$target->id,
                        'target',
                        $target->period
                    );
                }

                $saved++;
            }

            $payload = $this->tree($actor, $period, $metric);
            $this->assertNoOverDistribution($payload);

            if ($assign && ! ($payload['can_assign'] ?? false)) {
                $bad = collect($payload['checks'])->where('ok', false)->pluck('label')->implode('; ');
                throw ValidationException::withMessages([
                    'checks' => 'Cannot assign until every group adds up. Issues: '.$bad,
                ]);
            }

            ActivityLogger::log([
                'user_id' => $actor->id,
                'type' => $assign ? 'target_assign' : 'target_draft',
                'module' => 'goal_targets',
                'action' => $assign ? 'assign_targets' : 'save_draft',
                'details' => [
                    'period' => $period,
                    'metric' => $metric,
                    'rows' => $saved,
                ],
            ]);

            return [
                'saved' => $saved,
                'checks' => $payload['checks'],
                'can_assign' => $payload['can_assign'],
                'tree' => $payload['tree'],
            ];
        });
    }

    /**
     * @param  array<string, string>  $methods  level => method
     * @return array{tree: list<array>, applied: int}
     */
    public function autoSplit(User $actor, string $period, string $metric, float $rootTotal, array $methods = []): array
    {
        $period = $this->normalizePeriod($period);
        $metric = $this->normalizeMetric($metric);
        $payload = $this->tree($actor, $period, $metric);
        $tree = $payload['tree'];
        $methodDefault = (string) ($methods['default'] ?? 'equal');
        $weights = $this->roleWeights();

        $applied = 0;
        $walk = function (array &$nodes, float $parentPool, string $levelKey) use (&$walk, $methods, $methodDefault, $weights, &$applied) {
            if ($nodes === []) {
                return;
            }
            $method = (string) ($methods[$levelKey] ?? $methodDefault);
            $editable = array_values(array_filter($nodes, fn ($n) => ($n['editable'] ?? false) && ($n['row_source'] ?? 'auto') !== 'edited'));
            if ($editable === []) {
                foreach ($nodes as &$n) {
                    if (! empty($n['children'])) {
                        $childPool = max(0, (float) ($n['set_target'] ?? 0) - (float) ($n['owner_target'] ?? 0));
                        $walk($n['children'], $childPool, $this->nextLevelKey($levelKey));
                    }
                }
                unset($n);

                return;
            }

            $shares = $this->computeShares($editable, $parentPool, $method, $weights);
            foreach ($nodes as &$n) {
                if (! ($n['editable'] ?? false) || ($n['row_source'] ?? 'auto') === 'edited') {
                    if (! empty($n['children'])) {
                        $childPool = max(0, (float) ($n['set_target'] ?? 0) - (float) ($n['owner_target'] ?? 0));
                        $walk($n['children'], $childPool, $this->nextLevelKey($levelKey));
                    }

                    continue;
                }
                $id = (int) $n['id'];
                if (isset($shares[$id])) {
                    $n['set_target'] = $shares[$id];
                    $n['row_source'] = 'auto';
                    $n['chip'] = 'Auto';
                    $applied++;
                }
                if (! empty($n['children'])) {
                    $childPool = max(0, (float) ($n['set_target'] ?? 0) - (float) ($n['owner_target'] ?? 0));
                    $walk($n['children'], $childPool, $this->nextLevelKey($levelKey));
                }
            }
            unset($n);
        };

        $walk($tree, $rootTotal, 'l1');
        $this->recomputeTotals($tree);
        $checks = $this->collectChecks($tree);

        return [
            'tree' => $tree,
            'checks' => $checks,
            'applied' => $applied,
            'can_assign' => collect($checks)->every(fn ($c) => ($c['ok'] ?? false) === true),
        ];
    }

    /**
     * Reject when any group has distributed more than its remainder (target − owner).
     *
     * @param  array{self?: array, tree?: list<array>, checks?: list<array>}  $payload
     */
    public function assertNoOverDistribution(array $payload): void
    {
        $overs = [];
        $self = $payload['self'] ?? [];
        // Only enforce your own remainder once a higher authority has given you a target.
        if (! empty($self['receives_target']) && (float) ($self['set_target'] ?? 0) > 0.009) {
            $left = (float) ($self['left_to_distribute'] ?? 0);
            if ($left < -0.05) {
                $overs[] = sprintf(
                    'Your remainder is exceeded: distributed %s but only %s left to give (target %s − owner %s).',
                    $this->n((float) ($self['distributed'] ?? 0)),
                    $this->n((float) ($self['remainder'] ?? 0)),
                    $this->n((float) ($self['set_target'] ?? 0)),
                    $this->n((float) ($self['owner_target'] ?? 0))
                );
            }
        }

        $walk = function (array $nodes) use (&$walk, &$overs) {
            foreach ($nodes as $n) {
                if (! empty($n['children'])) {
                    $team = (float) ($n['total_target'] ?? 0);
                    $rem = (float) ($n['remainder'] ?? max(0, (float) ($n['set_target'] ?? 0) - (float) ($n['owner_target'] ?? 0)));
                    if ($team - $rem > 0.05) {
                        $overs[] = sprintf(
                            '%s: distributed %s exceeds remainder %s.',
                            $n['area'] ?? $n['name'] ?? ('#'.$n['id']),
                            $this->n($team),
                            $this->n($rem)
                        );
                    }
                    $walk($n['children']);
                }
            }
        };
        $walk($payload['tree'] ?? []);

        if ($overs !== []) {
            throw ValidationException::withMessages([
                'rows' => implode(' ', $overs),
            ]);
        }
    }

    public function requestRevision(User $actor, int $targetId, string $reason): GoalTarget
    {
        $target = GoalTarget::query()->findOrFail($targetId);
        if (! $target->user_id) {
            throw ValidationException::withMessages(['target' => 'Only employee targets support revision requests.']);
        }
        $this->assertCanEdit($actor, (int) $target->user_id);
        if (! $target->isLocked()) {
            throw ValidationException::withMessages(['target' => 'Only locked/approved targets need a revision request.']);
        }

        $old = (string) $target->status;
        $target->status = GoalTarget::STATUS_DRAFT;
        $target->revision_reason = trim($reason);
        $target->save();

        $this->audit($target, $actor, 'status', 0, 0, 'revision_request', [
            'old_status' => $old,
            'new_status' => GoalTarget::STATUS_DRAFT,
            'reason' => $reason,
        ]);

        return $target;
    }

    /**
     * @return Collection<int, User>
     */
    protected function loadSubtreeUsers(User $actor): Collection
    {
        $ids = $this->editableEmployeeIds($actor);
        if ($ids === []) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $ids)
            ->with(['orgUnit:id,name,type'])
            ->orderBy('name')
            ->get(['id', 'name', 'job_role', 'crm_role_type', 'reports_to_user_id', 'assign_manager', 'goal_org_unit_id', 'status']);
    }

    /**
     * @param  list<int>  $userIds
     * @return Collection<int, GoalTarget>
     */
    protected function targetsMap(string $period, string $metric, array $userIds): Collection
    {
        if ($userIds === []) {
            return collect();
        }

        return GoalTarget::query()
            ->where('period', $period)
            ->where('metric', $metric)
            ->whereIn('user_id', $userIds)
            ->whereNull('goal_org_unit_id')
            ->get()
            ->keyBy(fn (GoalTarget $t) => (int) $t->user_id);
    }

    /**
     * @param  Collection<int|string, Collection<int, User>>  $byParent
     * @param  Collection<int, GoalTarget>  $targetMap
     * @param  Collection<int, GoalTarget>  $prevMap
     * @param  array<int, int>  $editable
     */
    protected function buildNode(
        User $user,
        Collection $byParent,
        Collection $targetMap,
        Collection $prevMap,
        array $editable,
        int $depth
    ): array {
        $id = (int) $user->id;
        $childrenUsers = $byParent->get($id, collect());
        $children = [];
        foreach ($childrenUsers as $child) {
            $children[] = $this->buildNode($child, $byParent, $targetMap, $prevMap, $editable, $depth + 1);
        }

        $cur = $targetMap->get($id);
        $prev = $prevMap->get($id);
        $set = (float) ($cur->base_target ?? 0);
        $owner = (float) ($cur->owner_target ?? 0);
        $teamSum = array_sum(array_map(static fn ($c) => (float) ($c['set_target'] ?? 0), $children));
        $hasTeam = $children !== [];
        $remainder = max(0, round($set - $owner, 2));
        $totalTeam = $hasTeam ? $teamSum : null;
        $over = $hasTeam && ($teamSum - $remainder) > 0.05;
        $pending = $hasTeam && ! $over && abs($teamSum - $remainder) > 0.05;
        $checkOk = ! $hasTeam || abs($teamSum - $remainder) <= 0.05;
        // Prefer stored opening on current row; else carry previous closing shortfall
        $last = $cur
            ? (float) ($cur->opening_shortfall ?? 0)
            : $this->openingFromPrevious($prev);
        if ($cur && (float) ($cur->opening_shortfall ?? 0) <= 0 && $prev) {
            // Current draft/row exists but opening not set yet → still show carry-forward
            $last = $this->openingFromPrevious($prev);
        }
        $jumpPct = (float) config('goal.target_jump_pct', 15);
        $amber = $last > 0 && $set > ($last * (1 + ($jumpPct / 100)));
        $source = (string) ($cur->row_source ?? 'auto');
        $status = (string) ($cur->status ?? GoalTarget::STATUS_DRAFT);
        $chip = match (true) {
            in_array($status, [GoalTarget::STATUS_LOCKED, GoalTarget::STATUS_APPROVED], true) => 'Locked',
            $status === GoalTarget::STATUS_SUBMITTED => 'Submitted',
            $status === GoalTarget::STATUS_DRAFT && $source === 'edited' => 'Edited',
            $status === GoalTarget::STATUS_DRAFT => 'Draft',
            default => 'Auto',
        };
        if ($source === 'auto' && $status === GoalTarget::STATUS_DRAFT && $set > 0) {
            $chip = 'Auto';
        }

        $area = $user->orgUnit?->name ?: $user->name;
        $isStaff = ! $hasTeam;
        $leftForManager = max(0, round($remainder - $teamSum, 2));

        return [
            'id' => $id,
            'name' => $user->name,
            'area' => $area,
            'role' => $this->roleLabel($user),
            'job_role' => (string) ($user->job_role ?? ''),
            'depth' => $depth,
            'last_month' => $last,
            'set_target' => $set,
            'owner_target' => $isStaff ? $set : $owner,
            'remainder' => $remainder,
            'total_target' => $hasTeam ? $totalTeam : null,
            'is_staff' => $isStaff,
            'editable' => isset($editable[$id]),
            'row_source' => $source,
            'status' => $status,
            'chip' => $chip,
            'amber' => $amber,
            'check_ok' => $checkOk,
            'over_allocated' => $over,
            'pending_distribution' => $pending,
            'check_label' => $hasTeam
                ? ($over
                    ? sprintf(
                        '%s: over-allocated — distributed %s but remainder is only %s ✗',
                        $area,
                        $this->n($teamSum),
                        $this->n($remainder)
                    )
                    : ($pending
                        ? sprintf(
                            '%s: %s left for this manager to distribute later',
                            $area,
                            $this->n($leftForManager)
                        )
                        : sprintf(
                            '%s: distributed %s of remainder %s ✓',
                            $area,
                            $this->n($teamSum),
                            $this->n($remainder)
                        )))
                : null,
            'children' => $children,
        ];
    }

    /**
     * @param  list<array>  $tree
     * @return list<array{id:int, label:string, ok:bool, over:bool}>
     */
    protected function collectChecks(array $tree): array
    {
        $out = [];
        $walk = function (array $nodes) use (&$walk, &$out) {
            foreach ($nodes as $n) {
                if (! empty($n['children'])) {
                    $out[] = [
                        'id' => (int) $n['id'],
                        'label' => (string) ($n['check_label'] ?? $n['name']),
                        'ok' => (bool) ($n['check_ok'] ?? false),
                        'over' => (bool) ($n['over_allocated'] ?? false),
                        'pending' => (bool) ($n['pending_distribution'] ?? false),
                    ];
                    $walk($n['children']);
                }
            }
        };
        $walk($tree);

        return $out;
    }

    /**
     * @param  list<array>  $tree
     */
    protected function recomputeTotals(array &$tree): void
    {
        foreach ($tree as &$n) {
            if (! empty($n['children'])) {
                $this->recomputeTotals($n['children']);
                $team = array_sum(array_map(static fn ($c) => (float) ($c['set_target'] ?? 0), $n['children']));
                $owner = (float) ($n['owner_target'] ?? 0);
                $set = (float) ($n['set_target'] ?? 0);
                $remainder = max(0, round($set - $owner, 2));
                $over = ($team - $remainder) > 0.05;
                $pending = ! $over && abs($team - $remainder) > 0.05;
                $leftForManager = max(0, round($remainder - $team, 2));
                $n['total_target'] = $team;
                $n['remainder'] = $remainder;
                $n['check_ok'] = abs($team - $remainder) <= 0.05;
                $n['over_allocated'] = $over;
                $n['pending_distribution'] = $pending;
                $n['check_label'] = $over
                    ? sprintf(
                        '%s: over-allocated — distributed %s but remainder is only %s ✗',
                        $n['area'] ?? $n['name'],
                        $this->n($team),
                        $this->n($remainder)
                    )
                    : ($pending
                        ? sprintf(
                            '%s: %s left for this manager to distribute later',
                            $n['area'] ?? $n['name'],
                            $this->n($leftForManager)
                        )
                        : sprintf(
                            '%s: distributed %s of remainder %s ✓',
                            $n['area'] ?? $n['name'],
                            $this->n($team),
                            $this->n($remainder)
                        ));
            } else {
                $n['total_target'] = null;
            }
        }
        unset($n);
    }

    /**
     * @param  list<array>  $nodes
     * @param  array<string, float>  $weights
     * @return array<int, float>
     */
    protected function computeShares(array $nodes, float $pool, string $method, array $weights): array
    {
        $n = count($nodes);
        if ($n === 0 || $pool <= 0) {
            return [];
        }

        if ($method === 'by_last_month') {
            $sum = array_sum(array_map(static fn ($r) => max(0, (float) ($r['last_month'] ?? 0)), $nodes));
            if ($sum <= 0) {
                $method = 'equal';
            } else {
                $out = [];
                $allocated = 0.0;
                foreach ($nodes as $i => $row) {
                    $id = (int) $row['id'];
                    if ($i === $n - 1) {
                        $out[$id] = round($pool - $allocated, 2);
                    } else {
                        $share = round($pool * (max(0, (float) $row['last_month']) / $sum), 2);
                        $out[$id] = $share;
                        $allocated += $share;
                    }
                }

                return $out;
            }
        }

        if ($method === 'role_weights') {
            $wsum = 0.0;
            $wmap = [];
            foreach ($nodes as $row) {
                $role = strtolower((string) ($row['job_role'] ?? ''));
                $w = (float) ($weights[$role] ?? 1);
                $wmap[(int) $row['id']] = $w;
                $wsum += $w;
            }
            if ($wsum <= 0) {
                $method = 'equal';
            } else {
                $out = [];
                $allocated = 0.0;
                $i = 0;
                foreach ($nodes as $row) {
                    $id = (int) $row['id'];
                    $i++;
                    if ($i === $n) {
                        $out[$id] = round($pool - $allocated, 2);
                    } else {
                        $share = round($pool * ($wmap[$id] / $wsum), 2);
                        $out[$id] = $share;
                        $allocated += $share;
                    }
                }

                return $out;
            }
        }

        // equal (and fallback)
        $each = round($pool / $n, 2);
        $out = [];
        $allocated = 0.0;
        foreach ($nodes as $i => $row) {
            $id = (int) $row['id'];
            if ($i === $n - 1) {
                $out[$id] = round($pool - $allocated, 2);
            } else {
                $out[$id] = $each;
                $allocated += $each;
            }
        }

        return $out;
    }

    /**
     * @return array<string, float>
     */
    protected function roleWeights(): array
    {
        $defaults = (array) config('goal.target_role_weights', []);
        $db = GoalTargetRoleWeight::query()->where('is_active', true)->pluck('weight', 'job_role')->all();
        foreach ($db as $role => $w) {
            $defaults[strtolower((string) $role)] = (float) $w;
        }

        return array_change_key_case($defaults, CASE_LOWER);
    }

    protected function nextLevelKey(string $key): string
    {
        return match ($key) {
            'l1' => 'l2',
            'l2' => 'l3',
            'l3' => 'staff',
            default => 'staff',
        };
    }

    protected function audit(
        GoalTarget $target,
        User $actor,
        string $field,
        float $old,
        float $new,
        string $action,
        array $meta = []
    ): void {
        GoalTargetAuditLog::create([
            'goal_target_id' => $target->id,
            'employee_id' => (int) $target->user_id,
            'period' => $target->period,
            'metric' => $target->metric,
            'changed_by' => $actor->id,
            'field' => $field,
            'old_value' => $old,
            'new_value' => $new,
            'action' => $action,
            'meta' => $meta ?: null,
        ]);
    }

    protected function normalizePeriod(string $period): string
    {
        $period = trim($period);
        if (! preg_match('/^\d{4}-\d{2}$/', $period)) {
            return $this->defaultPeriod();
        }

        return $period;
    }

    protected function normalizeMetric(string $metric): string
    {
        $metric = strtolower(trim($metric));

        return in_array($metric, ['revenue', 'disbursement', 'deals'], true) ? $metric : 'revenue';
    }

    protected function roleLabel(User $user): string
    {
        $job = strtolower((string) ($user->job_role ?? ''));
        $map = [
            'rd' => 'Regional Director',
            'cluster' => 'Cluster Director',
            'bm' => 'Branch Manager',
            'ssm' => 'Senior Sales Manager',
            'sm' => 'Sales Manager',
            'jsm' => 'Junior Sales',
            'vrm' => 'VRM',
            'ceo' => 'CEO',
        ];
        if (isset($map[$job])) {
            return $map[$job];
        }

        return match ((int) ($user->crm_role_type ?? -1)) {
            0 => 'Super Admin',
            1 => 'Admin',
            4 => 'MD',
            5 => 'CEO',
            6 => 'Regional Director',
            7 => 'Cluster Director',
            8 => 'Branch Manager',
            default => $user->job_role ?: 'Team member',
        };
    }

    protected function n(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.') ?: '0';
    }
}
