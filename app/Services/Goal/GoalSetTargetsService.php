<?php

namespace App\Services\Goal;

use App\Helpers\ActivityLogger;
use App\Models\GoalOrgUnit;
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
        if ($job === '') {
            return false;
        }

        $exemptJobs = array_map('strtolower', (array) config('goal.target_exempt_job_roles', ['ceo', 'md', 'admin']));
        if (in_array($job, $exemptJobs, true)) {
            return true;
        }

        // Normalize separators so "accounts_manager" / "Accounts Manager" match config.
        $jobKey = preg_replace('/[\s_-]+/', '', $job) ?: '';
        $exemptKeys = array_map(
            static fn ($j) => preg_replace('/[\s_-]+/', '', strtolower((string) $j)) ?: '',
            $exemptJobs
        );
        if ($jobKey !== '' && in_array($jobKey, $exemptKeys, true)) {
            return true;
        }

        // Catch role labels that contain accounts / pms / accountant.
        return (bool) preg_match('/\b(accounts?|accountant|pms|portfolio\s*manager)\b/i', $job);
    }

    public function actorReceivesTarget(User $actor): bool
    {
        return ! $this->isExemptFromTargets($actor);
    }

    /**
     * Hierarchy rank (lower number = higher authority). Used so you only edit people below you.
     */
    public function roleRank(User $user): int
    {
        $map = [
            0 => 0,   // Super Admin
            1 => 5,   // Admin
            4 => 10,  // MD
            5 => 20,  // CEO
            6 => 30,  // RD
            7 => 40,  // Cluster
            8 => 50,  // BM
            9 => 60,  // SM / sales
            10 => 70,
            11 => 70,
            12 => 70,
            13 => 80,
            14 => 80,
        ];
        $crm = (int) ($user->crm_role_type ?? -1);
        if (isset($map[$crm])) {
            return $map[$crm];
        }

        // Fallback when crm_role_type is missing / non-standard
        $job = strtolower(preg_replace('/[\s_-]+/', '', (string) ($user->job_role ?? '')) ?: '');

        return match (true) {
            in_array($job, ['rd', 'regionaldirector', 'region'], true) => 30,
            in_array($job, ['cluster', 'clusterhead', 'clusterdirector', 'cd'], true) => 40,
            in_array($job, ['bm', 'branchmanager', 'branch'], true) => 50,
            in_array($job, ['ssm', 'seniorsalesmanager', 'sm', 'salesmanager', 'jsm', 'juniorsales', 'vrm'], true) => 60,
            default => 999,
        };
    }

    public function isGlobalTargetAuthority(User $actor): bool
    {
        return $actor->isSuperAdmin()
            || in_array((int) ($actor->crm_role_type ?? -1), [0, 1, 4, 5], true);
    }

    /**
     * Employee IDs the actor may SET/EDIT.
     * Never self. Never peers/managers. Only people strictly below in authority,
     * within the actor’s reporting / org team. Exempt roles (MD/CEO/accounts/PMS) excluded.
     *
     * @return list<int>
     */
    public function editableEmployeeIds(User $actor): array
    {
        $actor->loadMissing('orgUnit');
        $team = $actor->goalTeamUserIds();
        $self = (int) $actor->id;

        // Ensure org-subtree staff are included even if reporting links are incomplete
        $crm = (int) ($actor->crm_role_type ?? -1);
        if (in_array($crm, [6, 7, 8], true) || $this->roleRank($actor) <= 50) {
            $orgIds = $actor->goalScopedOrgUnitIds();
            if ($orgIds !== []) {
                $orgUserIds = User::query()
                    ->whereIn('goal_org_unit_id', $orgIds)
                    ->where('crm_role_type', '!=', 0)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
                $team = array_values(array_unique(array_merge($team, $orgUserIds)));
            }
        }

        $ids = array_values(array_filter(
            array_map('intval', $team),
            static fn (int $id) => $id !== $self
        ));

        if ($ids === []) {
            return [];
        }

        $users = User::query()
            ->whereIn('id', $ids)
            ->get(['id', 'crm_role_type', 'job_role']);

        $actorRank = $this->roleRank($actor);
        $isAuthority = $this->isGlobalTargetAuthority($actor);
        $out = [];

        foreach ($users as $u) {
            if ($this->isExemptFromTargets($u)) {
                continue;
            }
            $r = $this->roleRank($u);
            // Global authority sets RD and below only. Managers set only strictly lower ranks.
            if ($isAuthority) {
                if ($r >= 30) {
                    $out[] = (int) $u->id;
                }
            } elseif ($r > $actorRank) {
                $out[] = (int) $u->id;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * Users shown in the Set Targets tree (editable team + self for read-only own row).
     *
     * @return list<int>
     */
    public function visibleEmployeeIds(User $actor): array
    {
        $ids = $this->editableEmployeeIds($actor);
        if ($this->actorReceivesTarget($actor)) {
            $ids[] = (int) $actor->id;
        }

        return array_values(array_unique($ids));
    }

    public function assertCanEdit(User $actor, int $employeeId): void
    {
        if ($employeeId === (int) $actor->id) {
            throw ValidationException::withMessages([
                'employee_id' => 'You cannot set or edit your own target. Only your reporting authority can.',
            ]);
        }

        $employee = User::query()->find($employeeId);
        if ($employee && $this->isExemptFromTargets($employee)) {
            throw ValidationException::withMessages([
                'employee_id' => 'MD and CEO do not receive targets. Set targets for Regional Directors and below.',
            ]);
        }

        if ($employee && $this->roleRank($employee) <= $this->roleRank($actor) && ! $this->isGlobalTargetAuthority($actor)) {
            throw ValidationException::withMessages([
                'employee_id' => 'You can only set targets for people below your role level.',
            ]);
        }

        if (! in_array($employeeId, $this->editableEmployeeIds($actor), true)) {
            throw ValidationException::withMessages([
                'employee_id' => 'You can only set targets for employees in your authorized reporting line.',
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
     * Previous period's assigned total target (base) for the "Last Month Target" column.
     */
    protected function lastMonthTarget(?GoalTarget $prev): float
    {
        if (! $prev) {
            return 0.0;
        }

        return round((float) ($prev->base_target ?? 0), 2);
    }

    /**
     * @return array{period: string, prev_period: string, metric: string, unit: string, jump_pct: float, self: array, tree: list<array>, checks: list<array>, can_assign: bool}
     */
    public function tree(User $actor, string $period, string $metric = 'revenue'): array
    {
        $period = $this->normalizePeriod($period);
        $metric = $this->normalizeMetric($metric);
        $prev = $this->previousPeriod($period);
        $actor->loadMissing([
            'orgUnit:id,name,type,code,parent_id',
            'orgUnit.parent:id,name,type,code,parent_id',
            'orgUnit.parent.parent:id,name,type,code,parent_id',
        ]);
        $editableIds = $this->editableEmployeeIds($actor);
        $editable = array_flip($editableIds);

        $users = $this->loadSubtreeUsers($actor);
        $byParent = $users->groupBy(fn (User $u) => (int) ($u->reports_to_user_id ?: $u->assign_manager ?: 0));

        $targetMap = $this->targetsMap($period, $metric, $users->pluck('id')->all());
        $prevMap = $this->targetsMap($prev, $metric, $users->pluck('id')->all());

        // Build leaf person nodes, then nest under org Region → Cluster → Branch
        $peopleByOrg = [];
        $unassignedPeople = [];
        foreach ($users as $user) {
            $leaf = $this->buildNode($user, collect(), $targetMap, $prevMap, $editable, 0);
            $leaf['children'] = [];
            $leaf['total_target'] = null;
            $isMgr = $this->isHierarchyManager($user);
            $leaf['is_staff'] = ! $isMgr;
            if ($isMgr) {
                $cur = $targetMap->get((int) $user->id);
                $leaf['owner_target'] = (float) ($cur->owner_target ?? 0);
                $leaf['remainder'] = max(0, round((float) ($leaf['set_target'] ?? 0) - (float) $leaf['owner_target'], 2));
            }
            $oid = (int) ($user->goal_org_unit_id ?: 0);
            if ($oid > 0) {
                $peopleByOrg[$oid][] = $leaf;
            } else {
                $unassignedPeople[] = $leaf;
            }
        }
        $tree = $this->buildOrgHierarchyTree($users, $peopleByOrg, $unassignedPeople, $actor);

        $selfTargetMap = $this->targetsMap($period, $metric, [(int) $actor->id]);
        $selfPrevMap = $this->targetsMap($prev, $metric, [(int) $actor->id]);
        $selfTarget = $selfTargetMap->get((int) $actor->id);
        $selfPrev = $selfPrevMap->get((int) $actor->id);

        $receivesTarget = $this->actorReceivesTarget($actor);
        $mySet = $receivesTarget ? (float) ($selfTarget->base_target ?? 0) : 0.0;
        $myOwner = $receivesTarget ? (float) ($selfTarget->owner_target ?? 0) : 0.0;
        $remainder = max(0, round($mySet - $myOwner, 2));
        // Remainder check uses direct reports only (not full org rollup)
        $distributed = 0.0;
        foreach ($byParent->get((int) $actor->id, collect()) as $direct) {
            $distributed += (float) ($targetMap->get((int) $direct->id)->base_target ?? 0);
        }
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
        $canEdit = $editableIds !== [];
        $canAssign = $canEdit && count($tree) > 0 && ! $hasOver;
        if ($receivesTarget) {
            $canAssign = $canAssign && abs($left) <= 0.05;
        }

        $scopeMeta = $this->authorityScopeMeta($actor);

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
                // Last month target = previous period's assigned total (base)
                'last_month' => $receivesTarget ? $this->lastMonthTarget($selfPrev) : 0.0,
                'status' => (string) ($selfTarget->status ?? GoalTarget::STATUS_DRAFT),
                'editable' => false,
            ],
            'tree' => $tree,
            'checks' => $checks,
            'pending' => $pending,
            'can_assign' => $canAssign,
            'can_edit' => $canEdit,
            'authority' => [
                'scope' => $scopeMeta['label'],
                'scope_key' => $scopeMeta['key'],
                'default_apply' => $scopeMeta['default_apply'],
                'can_edit' => $canEdit,
                'message' => $canEdit
                    ? 'You can set targets for people below your level in '.$scopeMeta['label'].'. Your own target is set by your reporting authority only.'
                    : 'Your own target is view-only. Only your reporting authority can add or edit it.',
            ],
            'split_options' => [
                'by_last_month' => "By last month's target",
                'equal' => 'Equal split',
                'custom_pct' => 'Custom %',
                'role_weights' => 'Role weights (set by admin)',
            ],
        ];
    }

    /**
     * @return array{key: string, label: string, default_apply: string}
     */
    protected function authorityScopeMeta(User $actor): array
    {
        if ($this->isGlobalTargetAuthority($actor)) {
            return ['key' => 'company', 'label' => 'company', 'default_apply' => 'rd'];
        }
        $type = (string) ($actor->orgUnit?->type ?? '');
        $crm = (int) ($actor->crm_role_type ?? -1);

        if ($type === GoalOrgUnit::TYPE_REGION || $crm === 6) {
            return ['key' => 'region', 'label' => 'your region', 'default_apply' => 'cluster'];
        }
        if ($type === GoalOrgUnit::TYPE_CLUSTER || $crm === 7) {
            // Land on Branch view so CD can set BM / staff targets under them
            return ['key' => 'cluster', 'label' => 'your cluster', 'default_apply' => 'bm'];
        }
        if ($type === GoalOrgUnit::TYPE_BRANCH || $crm === 8) {
            return ['key' => 'branch', 'label' => 'your branch', 'default_apply' => 'staff'];
        }

        return ['key' => 'team', 'label' => 'your team', 'default_apply' => 'staff'];
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

            $orgLevelKey = static function (array $n, string $fallback) use ($levelKey): string {
                return match ((string) ($n['org_type'] ?? '')) {
                    GoalOrgUnit::TYPE_REGION => 'l1',
                    GoalOrgUnit::TYPE_CLUSTER => 'l2',
                    GoalOrgUnit::TYPE_BRANCH => 'l3',
                    default => $fallback !== '' ? $fallback : $levelKey,
                };
            };

            // Editable people + linked RD/CD on Region/Cluster org rows
            $editable = [];
            foreach ($nodes as $n) {
                if (($n['row_source'] ?? 'auto') === 'edited' || ! ($n['editable'] ?? false)) {
                    continue;
                }
                if (! empty($n['is_org_group'])) {
                    if (! empty($n['linked_user_id'])) {
                        $copy = $n;
                        $copy['id'] = (int) $n['linked_user_id'];
                        $editable[] = $copy;
                    }

                    continue;
                }
                $editable[] = $n;
            }

            if ($editable === []) {
                foreach ($nodes as &$n) {
                    if (! empty($n['children'])) {
                        $childPool = (! empty($n['is_org_group']) && empty($n['linked_user_id']))
                            ? $parentPool
                            : max(0, (float) ($n['set_target'] ?? 0) - (float) ($n['owner_target'] ?? 0));
                        $next = ! empty($n['is_org_group'])
                            ? $orgLevelKey($n, $this->nextLevelKey($levelKey))
                            : $this->nextLevelKey($levelKey);
                        $walk($n['children'], $childPool, $next);
                    }
                }
                unset($n);

                return;
            }

            $shares = $this->computeShares($editable, $parentPool, $method, $weights);
            foreach ($nodes as &$n) {
                $shareId = ! empty($n['is_org_group']) && ! empty($n['linked_user_id'])
                    ? (int) $n['linked_user_id']
                    : (int) ($n['id'] ?? 0);
                $canTake = ($n['editable'] ?? false) && ($n['row_source'] ?? 'auto') !== 'edited'
                    && (empty($n['is_org_group']) || ! empty($n['linked_user_id']));

                if ($canTake && isset($shares[$shareId])) {
                    $n['set_target'] = $shares[$shareId];
                    $n['row_source'] = 'auto';
                    $n['chip'] = 'Auto';
                    $applied++;
                }

                if (! empty($n['children'])) {
                    $childPool = (! empty($n['is_org_group']) && empty($n['linked_user_id']))
                        ? $parentPool
                        : max(0, (float) ($n['set_target'] ?? 0) - (float) ($n['owner_target'] ?? 0));
                    $next = ! empty($n['is_org_group'])
                        ? $orgLevelKey($n, $this->nextLevelKey($levelKey))
                        : $this->nextLevelKey($levelKey);
                    $walk($n['children'], $childPool, $next);
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
        $ids = $this->visibleEmployeeIds($actor);
        if ($ids === []) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $ids)
            ->with([
                'orgUnit:id,name,type,code,parent_id',
                'orgUnit.parent:id,name,type,code,parent_id',
                'orgUnit.parent.parent:id,name,type,code,parent_id',
            ])
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
        // Display column: previous period's total target (not opening shortfall)
        $last = $this->lastMonthTarget($prev);
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
        $region = $this->resolveRegionMeta($user->orgUnit);

        return [
            'id' => $id,
            'name' => $user->name,
            'area' => $area,
            'role' => $this->roleLabel($user),
            'job_role' => (string) ($user->job_role ?? ''),
            'crm_role_type' => (int) ($user->crm_role_type ?? -1),
            'org_type' => (string) ($user->orgUnit?->type ?? ''),
            'org_name' => (string) ($user->orgUnit?->name ?? ''),
            'org_code' => (string) ($user->orgUnit?->code ?? ''),
            'region_id' => $region['id'],
            'region_name' => $region['name'],
            'region_code' => $region['code'],
            'is_org_group' => false,
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
     * @return array{id: int|null, name: string, code: string}
     */
    protected function resolveRegionMeta(?GoalOrgUnit $unit): array
    {
        if (! $unit) {
            return ['id' => null, 'name' => 'Unassigned', 'code' => ''];
        }

        $cursor = $unit;
        $guard = 0;
        while ($cursor && $guard < 6) {
            if ($cursor->type === GoalOrgUnit::TYPE_REGION) {
                return [
                    'id' => (int) $cursor->id,
                    'name' => (string) $cursor->name,
                    'code' => (string) ($cursor->code ?? ''),
                ];
            }
            $cursor = $cursor->parent;
            $guard++;
        }

        return [
            'id' => (int) $unit->id,
            'name' => (string) $unit->name,
            'code' => (string) ($unit->code ?? ''),
        ];
    }

    /**
     * Target Allocation style: Region → Cluster → Branch → people.
     * Roots are scoped to the actor’s authorized org level (company / region / cluster / branch).
     *
     * @param  Collection<int, User>  $users
     * @param  array<int, list<array<string, mixed>>>  $peopleByOrg
     * @param  list<array<string, mixed>>  $unassignedPeople
     * @return list<array<string, mixed>>
     */
    protected function buildOrgHierarchyTree(
        Collection $users,
        array $peopleByOrg,
        array $unassignedPeople,
        ?User $actor = null
    ): array {
        $orgIds = [];
        foreach ($users as $user) {
            $unit = $user->orgUnit;
            $guard = 0;
            while ($unit && $guard < 6) {
                $orgIds[(int) $unit->id] = true;
                $unit = $unit->parent;
                $guard++;
            }
        }

        // Ensure actor’s own org unit is in the scaffold (for read-only self display)
        if ($actor && (int) ($actor->goal_org_unit_id ?: 0) > 0) {
            $own = $actor->orgUnit;
            $guard = 0;
            while ($own && $guard < 6) {
                $orgIds[(int) $own->id] = true;
                $own = $own->parent;
                $guard++;
            }
        }

        if ($orgIds === []) {
            return $unassignedPeople;
        }

        $units = GoalOrgUnit::query()
            ->whereIn('id', array_keys($orgIds))
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'code', 'parent_id']);

        $byParent = $units->groupBy(fn (GoalOrgUnit $u) => (int) ($u->parent_id ?: 0));
        $unitsById = $units->keyBy(fn (GoalOrgUnit $u) => (int) $u->id);

        $build = function (GoalOrgUnit $unit, int $depth) use (&$build, $byParent, $peopleByOrg): array {
            $people = $peopleByOrg[(int) $unit->id] ?? [];
            usort($people, function (array $a, array $b) {
                $rank = static function (array $n): int {
                    $crm = (int) ($n['crm_role_type'] ?? -1);
                    if (in_array($crm, [6, 7, 8], true)) {
                        return $crm;
                    }

                    return 100;
                };

                return $rank($a) <=> $rank($b) ?: strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
            });

            $children = [];
            foreach ($byParent->get((int) $unit->id, collect()) as $childUnit) {
                $children[] = $build($childUnit, $depth + 1);
            }

            $linked = null;
            $type = (string) $unit->type;

            // Pick lead for this org row (RD / CD / BM) so higher authority can edit their target here
            foreach ($people as $p) {
                $crm = (int) ($p['crm_role_type'] ?? -1);
                if ($type === GoalOrgUnit::TYPE_BRANCH) {
                    if ($crm === 8 || empty($p['is_staff'])) {
                        $linked = $p;
                        break;
                    }
                } elseif (in_array($crm, [6, 7, 8], true) || empty($p['is_staff'])) {
                    $linked = $p;
                    break;
                }
            }
            if ($linked === null && $people !== []) {
                $linked = $people[0];
            }

            if ($type === GoalOrgUnit::TYPE_BRANCH) {
                // Staff under Branch only — BM target is on the Branch org row (linked)
                $linkedId = $linked['id'] ?? null;
                foreach ($people as $person) {
                    if ($linkedId !== null && (int) ($person['id'] ?? 0) === (int) $linkedId) {
                        continue;
                    }
                    $person['depth'] = $depth + 1;
                    $children[] = $person;
                }
            }

            return $this->makeOrgGroupNode($unit, $children, $depth, null, $linked);
        };

        $roots = $this->resolveOrgTreeRoots($actor, $units, $unitsById);
        $tree = $roots->map(fn (GoalOrgUnit $u) => $build($u, 0))->values()->all();

        // Unassigned people only for global authority (avoid leaking outside scope)
        if ($unassignedPeople !== [] && ($actor === null || $this->isGlobalTargetAuthority($actor))) {
            $tree[] = $this->makeOrgGroupNode(null, $unassignedPeople, 0, 'Unassigned');
        }

        return $tree;
    }

    /**
     * @param  Collection<int, GoalOrgUnit>  $units
     * @param  Collection<int, GoalOrgUnit>  $unitsById
     * @return Collection<int, GoalOrgUnit>
     */
    protected function resolveOrgTreeRoots(?User $actor, Collection $units, Collection $unitsById): Collection
    {
        if ($actor && ! $this->isGlobalTargetAuthority($actor)) {
            $oid = (int) ($actor->goal_org_unit_id ?: 0);
            if ($oid > 0 && $unitsById->has($oid)) {
                return collect([$unitsById->get($oid)]);
            }
        }

        $regionRoots = $units->where('type', GoalOrgUnit::TYPE_REGION)->values();
        if ($regionRoots->isEmpty()) {
            return $units->filter(function (GoalOrgUnit $u) use ($unitsById) {
                $pid = (int) ($u->parent_id ?: 0);

                return $pid === 0 || ! $unitsById->has($pid);
            })->values();
        }

        return $regionRoots;
    }

    /**
     * @param  list<array<string, mixed>>  $children
     * @param  array<string, mixed>|null  $linked  Lead person whose target is edited on this org row (Region/Cluster/Branch)
     * @return array<string, mixed>
     */
    protected function makeOrgGroupNode(
        ?GoalOrgUnit $unit,
        array $children,
        int $depth,
        ?string $fallbackName = null,
        ?array $linked = null
    ): array {
        $this->rollupOrgGroup($children);
        $childSet = round(array_sum(array_map(static fn ($c) => (float) ($c['set_target'] ?? 0), $children)), 2);
        $childOpen = round(array_sum(array_map(static fn ($c) => (float) ($c['last_month'] ?? 0), $children)), 2);

        $type = $unit?->type ?: GoalOrgUnit::TYPE_REGION;
        $name = $fallbackName ?: (string) ($unit?->name ?? 'Org');
        $code = (string) ($unit?->code ?? '');
        $label = $code !== '' ? $name.' ('.$code.')' : $name;
        $id = $unit ? (-100000 - (int) $unit->id) : -1;

        $role = match ($type) {
            GoalOrgUnit::TYPE_CLUSTER => 'Cluster',
            GoalOrgUnit::TYPE_BRANCH => 'Branch',
            default => 'Region',
        };
        $job = match ($type) {
            GoalOrgUnit::TYPE_CLUSTER => 'cluster',
            GoalOrgUnit::TYPE_BRANCH => 'bm',
            default => 'rd',
        };
        $crm = match ($type) {
            GoalOrgUnit::TYPE_CLUSTER => 7,
            GoalOrgUnit::TYPE_BRANCH => 8,
            default => 6,
        };

        $region = $unit ? $this->resolveRegionMeta($unit) : ['id' => null, 'name' => $name, 'code' => ''];

        $hasLinked = is_array($linked) && ! empty($linked['id']);
        $set = $hasLinked ? round((float) ($linked['set_target'] ?? 0), 2) : $childSet;
        $open = $hasLinked ? round((float) ($linked['last_month'] ?? 0), 2) : $childOpen;
        $owner = $hasLinked ? round((float) ($linked['owner_target'] ?? 0), 2) : 0.0;

        return [
            'id' => $id,
            'name' => $label,
            'area' => $name,
            'role' => $role,
            'job_role' => $job,
            'crm_role_type' => $crm,
            'org_type' => $type,
            'org_name' => $name,
            'org_code' => $code,
            'region_id' => $region['id'],
            'region_name' => $region['name'],
            'region_code' => $region['code'],
            'is_org_group' => true,
            'linked_user_id' => $hasLinked ? (int) $linked['id'] : null,
            'linked_user_name' => $hasLinked ? (string) ($linked['name'] ?? '') : null,
            'depth' => $depth,
            'last_month' => $open,
            'set_target' => $set,
            'owner_target' => $owner,
            'remainder' => max(0, round(max(0, $set - $owner) - $childSet, 2)),
            'total_target' => $childSet,
            'is_staff' => false,
            'editable' => $hasLinked ? (bool) ($linked['editable'] ?? false) : false,
            'row_source' => $hasLinked ? (string) ($linked['row_source'] ?? 'auto') : 'auto',
            'status' => $hasLinked ? (string) ($linked['status'] ?? GoalTarget::STATUS_DRAFT) : GoalTarget::STATUS_DRAFT,
            'chip' => $role,
            'amber' => $hasLinked ? (bool) ($linked['amber'] ?? false) : false,
            'check_ok' => true,
            'over_allocated' => false,
            'pending_distribution' => false,
            'check_label' => null,
            'children' => $children,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     */
    protected function rollupOrgGroup(array &$nodes): void
    {
        foreach ($nodes as &$n) {
            if (! empty($n['is_org_group']) && ! empty($n['children']) && is_array($n['children'])) {
                $this->rollupOrgGroup($n['children']);
                $childSet = round(array_sum(array_map(static fn ($c) => (float) ($c['set_target'] ?? 0), $n['children'])), 2);
                $childOpen = round(array_sum(array_map(static fn ($c) => (float) ($c['last_month'] ?? 0), $n['children'])), 2);
                $n['total_target'] = $childSet;
                // Keep RD/CD amounts bound to Region/Cluster; only plain rollup rows sum children.
                if (empty($n['linked_user_id'])) {
                    $n['set_target'] = $childSet;
                    $n['last_month'] = $childOpen;
                    $n['remainder'] = 0.0;
                } else {
                    $set = (float) ($n['set_target'] ?? 0);
                    $owner = (float) ($n['owner_target'] ?? 0);
                    $pool = max(0, round($set - $owner, 2));
                    $n['remainder'] = max(0, round($pool - $childSet, 2));
                }
            }
        }
        unset($n);
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
                    // Include linked Region/Cluster rows in cascade checks
                    if (empty($n['is_org_group']) || ! empty($n['linked_user_id'])) {
                        $out[] = [
                            'id' => (int) ($n['linked_user_id'] ?? $n['id']),
                            'label' => (string) ($n['check_label'] ?? $n['linked_user_name'] ?? $n['name']),
                            'ok' => (bool) ($n['check_ok'] ?? false),
                            'over' => (bool) ($n['over_allocated'] ?? false),
                            'pending' => (bool) ($n['pending_distribution'] ?? false),
                        ];
                    }
                    $walk($n['children']);
                }
            }
        };
        $walk($tree);

        return $out;
    }

    protected function isHierarchyManager(User $user): bool
    {
        $crm = (int) ($user->crm_role_type ?? -1);
        if (in_array($crm, [6, 7, 8], true)) {
            return true;
        }
        $job = strtolower(preg_replace('/[\s_-]+/', '', (string) ($user->job_role ?? '')) ?? '');

        return in_array($job, ['rd', 'regionaldirector', 'cluster', 'clusterhead', 'cd', 'bm', 'branchmanager'], true);
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
                if (! empty($n['is_org_group']) && empty($n['linked_user_id'])) {
                    $open = array_sum(array_map(static fn ($c) => (float) ($c['last_month'] ?? 0), $n['children']));
                    $n['set_target'] = round($team, 2);
                    $n['last_month'] = round($open, 2);
                    $n['owner_target'] = 0.0;
                    $n['total_target'] = round($team, 2);
                    $n['remainder'] = 0.0; // rollup row — nothing left to assign at this level
                    $n['check_ok'] = true;
                    $n['over_allocated'] = false;
                    $n['pending_distribution'] = false;
                    $n['check_label'] = null;
                    continue;
                }

                // Linked org (RD/CD) or person manager: pool = Total − Owner; Remaining = pool − children
                $owner = (float) ($n['owner_target'] ?? 0);
                $set = (float) ($n['set_target'] ?? 0);
                $pool = max(0, round($set - $owner, 2));
                $left = round($pool - $team, 2);
                $over = ($team - $pool) > 0.05;
                $pending = ! $over && abs($team - $pool) > 0.05;
                $n['total_target'] = round($team, 2);
                $n['remainder'] = max(0, $left);
                $n['check_ok'] = abs($team - $pool) <= 0.05;
                $n['over_allocated'] = $over;
                $n['pending_distribution'] = $pending;
                $label = (string) ($n['linked_user_name'] ?? $n['area'] ?? $n['name'] ?? 'Row');
                $n['check_label'] = $over
                    ? sprintf(
                        '%s: over-allocated — children total %s but only %s remaining (Total − Owner) ✗',
                        $label,
                        $this->n($team),
                        $this->n($pool)
                    )
                    : ($pending
                        ? sprintf(
                            '%s: %s still left to distribute below',
                            $label,
                            $this->n(max(0, $left))
                        )
                        : sprintf(
                            '%s: distributed %s of %s ✓',
                            $label,
                            $this->n($team),
                            $this->n($pool)
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
