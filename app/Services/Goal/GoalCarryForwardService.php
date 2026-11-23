<?php

namespace App\Services\Goal;

use App\Models\GoalTarget;
use App\Models\GoalTargetAuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GoalCarryForwardService
{
    public function previousPeriod(string $period): string
    {
        return Carbon::createFromFormat('Y-m', $period)->subMonthNoOverflow()->format('Y-m');
    }

    public function nextPeriod(string $period): string
    {
        return Carbon::createFromFormat('Y-m', $period)->addMonthNoOverflow()->format('Y-m');
    }

    /**
     * Approved source-period targets with closing shortfall that can be carried forward.
     *
     * @param  callable(\Illuminate\Database\Eloquent\Builder, User): void  $applyScope
     * @return list<array<string, mixed>>
     */
    public function candidates(User $actor, string $sourcePeriod, ?string $metric, callable $applyScope): array
    {
        $nextPeriod = $this->nextPeriod($sourcePeriod);

        $query = GoalTarget::query()
            ->with(['orgUnit', 'user'])
            ->where('period', $sourcePeriod)
            ->where('status', GoalTarget::STATUS_APPROVED)
            ->where('closing_shortfall', '>', 0);

        $applyScope($query, $actor);

        if ($metric && in_array($metric, ['revenue', 'disbursement'], true)) {
            $query->where('metric', $metric);
        }

        $sources = $query->orderBy('metric')->orderBy('id')->get();

        $rows = [];
        foreach ($sources as $src) {
            $src->recalculateClosingShortfall();
            $shortfall = round((float) $src->closing_shortfall, 2);
            if ($shortfall <= 0) {
                continue;
            }

            $existing = $this->findNext($src, $nextPeriod);
            $rejectLog = GoalTargetAuditLog::query()
                ->where('action', 'carry_forward_reject')
                ->where('period', $nextPeriod)
                ->where('metric', $src->metric)
                ->where('goal_target_id', $src->id)
                ->latest('id')
                ->first();

            $status = 'pending';
            if ($existing && (float) $existing->opening_shortfall > 0) {
                $status = 'applied';
            } elseif ($rejectLog) {
                $status = 'rejected';
            }

            $rows[] = [
                'source_id' => $src->id,
                'next_id' => $existing?->id,
                'owner' => $src->user_id
                    ? ($src->user?->name ?? ('User #'.$src->user_id))
                    : ($src->orgUnit?->name ?? 'Org unit'),
                'owner_type' => $src->user_id ? 'employee' : 'org_unit',
                'metric' => $src->metric,
                'source_period' => $sourcePeriod,
                'next_period' => $nextPeriod,
                'previous_target' => round($src->effectiveTarget(), 2),
                'previous_achieved' => round((float) $src->actual, 2),
                'previous_shortfall' => $shortfall,
                'next_base' => round((float) ($existing?->base_target ?? $src->base_target), 2),
                'proposed_opening' => $shortfall,
                'effective_if_applied' => round((float) ($existing?->base_target ?? $src->base_target) + $shortfall + (float) ($existing?->adjustments ?? 0), 2),
                'status' => $status,
                'next_status' => $existing?->status,
            ];
        }

        return $rows;
    }

    public function apply(GoalTarget $source, User $actor, ?string $reason = null): GoalTarget
    {
        if ($source->status !== GoalTarget::STATUS_APPROVED) {
            throw new RuntimeException('Only Approved targets can be carried forward.');
        }

        $source->recalculateClosingShortfall();
        $shortfall = round((float) $source->closing_shortfall, 2);
        if ($shortfall <= 0) {
            throw new RuntimeException('No closing shortfall to carry forward.');
        }

        $nextPeriod = $this->nextPeriod((string) $source->period);

        return DB::transaction(function () use ($source, $actor, $reason, $shortfall, $nextPeriod) {
            $existing = $this->findNext($source, $nextPeriod);

            if ($existing) {
                if ($existing->isLocked() && (float) $existing->opening_shortfall > 0) {
                    throw new RuntimeException('Next-period target is locked with opening shortfall already set.');
                }
                $old = (float) $existing->opening_shortfall;
                $existing->opening_shortfall = $shortfall;
                if ($existing->status === GoalTarget::STATUS_LOCKED) {
                    // keep locked
                } elseif ($existing->status === GoalTarget::STATUS_APPROVED && $old <= 0) {
                    // keep approved if already approved with zero opening — still allow update if not locked
                }
                $existing->recalculateClosingShortfall();
                $existing->save();
                $target = $existing;
                $oldVal = $old;
            } else {
                $target = new GoalTarget([
                    'period' => $nextPeriod,
                    'metric' => $source->metric,
                    'goal_org_unit_id' => $source->goal_org_unit_id,
                    'user_id' => $source->user_id,
                    'base_target' => $source->base_target,
                    'opening_shortfall' => $shortfall,
                    'adjustments' => 0,
                    'actual' => 0,
                    'status' => GoalTarget::STATUS_DRAFT,
                    'set_by' => $actor->id,
                    'row_source' => 'carry_forward',
                ]);
                $target->recalculateClosingShortfall();
                $target->save();
                $oldVal = 0.0;
            }

            $this->writeAudit(
                $target,
                $actor,
                'opening_shortfall',
                $oldVal,
                $shortfall,
                'carry_forward',
                [
                    'source_target_id' => $source->id,
                    'source_period' => $source->period,
                    'org_unit_id' => $source->goal_org_unit_id,
                    'reason' => $reason,
                ],
                $source->user_id ? (int) $source->user_id : (int) $actor->id
            );

            return $target;
        });
    }

    public function reject(GoalTarget $source, User $actor, ?string $reason = null): void
    {
        $nextPeriod = $this->nextPeriod((string) $source->period);

        $this->writeAudit(
            $source,
            $actor,
            'opening_shortfall',
            (float) $source->closing_shortfall,
            0,
            'carry_forward_reject',
            [
                'source_target_id' => $source->id,
                'source_period' => $source->period,
                'org_unit_id' => $source->goal_org_unit_id,
                'reason' => $reason,
            ],
            $source->user_id ? (int) $source->user_id : (int) $actor->id,
            $nextPeriod
        );
    }

    protected function writeAudit(
        GoalTarget $target,
        User $actor,
        string $field,
        float $old,
        float $new,
        string $action,
        array $meta,
        int $employeeId,
        ?string $periodOverride = null
    ): void {
        GoalTargetAuditLog::query()->create([
            'goal_target_id' => $target->id,
            'employee_id' => $employeeId,
            'period' => $periodOverride ?: $target->period,
            'metric' => $target->metric,
            'changed_by' => $actor->id,
            'field' => $field,
            'old_value' => $old,
            'new_value' => $new,
            'action' => $action,
            'meta' => $meta,
        ]);
    }

    protected function findNext(GoalTarget $src, string $nextPeriod): ?GoalTarget
    {
        $q = GoalTarget::query()
            ->where('period', $nextPeriod)
            ->where('metric', $src->metric);

        if ($src->user_id) {
            $q->where('user_id', $src->user_id);
        } else {
            $q->whereNull('user_id')->where('goal_org_unit_id', $src->goal_org_unit_id);
        }

        return $q->first();
    }
}
