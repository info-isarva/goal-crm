<?php

namespace App\Services\Goal;

use App\Models\GoalPmsActivityLog;
use App\Models\GoalPmsAssignmentHistory;
use App\Models\GoalPmsFollowup;
use App\Models\GoalPmsItem;
use App\Models\GoalPmsVerification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GoalPmsQueueService
{
    public function validatedFilters(Request $request): array
    {
        return $request->validate([
            'q' => 'nullable|string|max:120',
            'tab' => 'nullable|in:all,pending,verified,followups_due,overdue',
            'status' => 'nullable|string|max:40',
            'branch_id' => 'nullable|integer',
            'assigned_to' => 'nullable|integer',
            'product' => 'nullable|string|max:120',
            'verification_status' => 'nullable|string|max:40',
            'followup_status' => 'nullable|in:Scheduled,Due,In Progress,Completed,Overdue,Cancelled',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'sort' => 'nullable|in:newest,oldest,amount_desc,amount_asc,followup_asc',
            'selected' => 'nullable|integer',
            'format' => 'nullable|in:csv,xlsx,pdf',
        ]);
    }

    public function applyFilters(Builder $query, array $filters): Builder
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $tab = $filters['tab'] ?? 'all';
        $status = $filters['verification_status'] ?? ($filters['status'] ?? null);
        $branchId = $filters['branch_id'] ?? null;
        $assignedTo = $filters['assigned_to'] ?? null;
        $product = trim((string) ($filters['product'] ?? ''));
        $followupStatus = $filters['followup_status'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $sort = $filters['sort'] ?? 'newest';

        $query
            ->when($q !== '', function (Builder $query) use ($q) {
                $query->whereHas('loanCase', function (Builder $c) use ($q) {
                    $c->where('case_no', 'like', "%{$q}%")
                        ->orWhere('customer_name', 'like', "%{$q}%")
                        ->orWhere('product', 'like', "%{$q}%");
                });
            })
            ->when($branchId, fn (Builder $query) => $query->whereHas(
                'loanCase',
                fn (Builder $c) => $c->where('goal_org_unit_id', (int) $branchId)
            ))
            ->when($assignedTo, fn (Builder $query) => $query->where('assigned_to', (int) $assignedTo))
            ->when($product !== '', fn (Builder $query) => $query->whereHas(
                'loanCase',
                fn (Builder $c) => $c->where('product', 'like', "%{$product}%")
            ))
            ->when($dateFrom || $dateTo, function (Builder $query) use ($dateFrom, $dateTo) {
                $query->whereHas('disbursement', function (Builder $d) use ($dateFrom, $dateTo) {
                    if ($dateFrom) {
                        $d->whereDate('disbursed_on', '>=', $dateFrom);
                    }
                    if ($dateTo) {
                        $d->whereDate('disbursed_on', '<=', $dateTo);
                    }
                });
            })
            ->when($status, function (Builder $query) use ($status) {
                if ($status === 'pending' || $status === 'Pending Verification') {
                    $query->whereIn('status', ['Pending', 'Pending Verification']);
                } else {
                    $query->where('status', $status);
                }
            });

        $this->applyTab($query, $tab);
        $this->applyFollowupStatusFilter($query, $followupStatus);
        $this->applySort($query, $sort);

        return $query;
    }

    public function applyTab(Builder $query, ?string $tab): void
    {
        $tab = $tab ?: 'all';
        if ($tab === 'pending') {
            $query->whereIn('status', ['Pending', 'Pending Verification', 'In Progress']);
        } elseif ($tab === 'verified') {
            $query->where('status', 'Verified');
        } elseif ($tab === 'followups_due') {
            $query->where(function (Builder $q) {
                $q->where('status', 'Follow-up Due')
                    ->orWhereHas('followups', function (Builder $f) {
                        $f->whereNotIn('status', ['Completed', 'Cancelled'])
                            ->whereDate('scheduled_date', '<=', now()->toDateString())
                            ->whereDate('scheduled_date', '>=', now()->toDateString());
                    });
            });
        } elseif ($tab === 'overdue') {
            $query->whereHas('followups', function (Builder $f) {
                $f->whereNotIn('status', ['Completed', 'Cancelled'])
                    ->where(function (Builder $inner) {
                        $inner->whereDate('scheduled_date', '<', now()->toDateString())
                            ->orWhere(function (Builder $sameDay) {
                                $sameDay->whereDate('scheduled_date', now()->toDateString())
                                    ->whereNotNull('scheduled_time')
                                    ->where('scheduled_time', '<', now()->format('H:i:s'));
                            });
                    });
            });
        }
    }

    protected function applyFollowupStatusFilter(Builder $query, ?string $status): void
    {
        if (! $status) {
            return;
        }
        if ($status === 'Overdue') {
            $query->whereHas('followups', function (Builder $f) {
                $f->whereNotIn('status', ['Completed', 'Cancelled'])
                    ->whereDate('scheduled_date', '<', now()->toDateString());
            });

            return;
        }
        if ($status === 'Due') {
            $query->whereHas('followups', function (Builder $f) {
                $f->whereNotIn('status', ['Completed', 'Cancelled'])
                    ->whereDate('scheduled_date', now()->toDateString());
            });

            return;
        }
        $query->whereHas('followups', fn (Builder $f) => $f->where('status', $status));
    }

    protected function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->orderBy('id'),
            'amount_desc' => $query->orderByDesc(
                \App\Models\GoalDisbursement::select('amount')
                    ->whereColumn('goal_disbursements.id', 'goal_pms_items.goal_disbursement_id')
                    ->limit(1)
            ),
            'amount_asc' => $query->orderBy(
                \App\Models\GoalDisbursement::select('amount')
                    ->whereColumn('goal_disbursements.id', 'goal_pms_items.goal_disbursement_id')
                    ->limit(1)
            ),
            'followup_asc' => $query->orderBy('follow_up_at'),
            default => $query->latest('id'),
        };
    }

    /**
     * @return array{pending:int,verified_month:int,followups_due:int,overdue:int,all:int,verified:int}
     */
    public function kpis(Builder $scopedBase): array
    {
        $pending = (clone $scopedBase)->whereIn('status', ['Pending', 'Pending Verification', 'In Progress'])->count();
        $verified = (clone $scopedBase)->where('status', 'Verified')->count();
        $verifiedMonth = (clone $scopedBase)
            ->where('status', 'Verified')
            ->whereNotNull('verified_at')
            ->whereBetween('verified_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
        $all = (clone $scopedBase)->count();

        $followupsDue = GoalPmsFollowup::query()
            ->whereIn('goal_pms_item_id', (clone $scopedBase)->select('goal_pms_items.id'))
            ->whereNotIn('status', ['Completed', 'Cancelled'])
            ->whereDate('scheduled_date', now()->toDateString())
            ->count();

        $overdue = GoalPmsFollowup::query()
            ->whereIn('goal_pms_item_id', (clone $scopedBase)->select('goal_pms_items.id'))
            ->whereNotIn('status', ['Completed', 'Cancelled'])
            ->whereDate('scheduled_date', '<', now()->toDateString())
            ->count();

        // Also count items with status Follow-up Due and due today (legacy).
        $legacyDue = (clone $scopedBase)
            ->where('status', 'Follow-up Due')
            ->whereDate('follow_up_at', '<=', now()->toDateString())
            ->count();

        return [
            'all' => $all,
            'pending' => $pending,
            'verified' => $verified,
            'verified_month' => $verifiedMonth,
            'followups_due' => max($followupsDue, $legacyDue),
            'overdue' => $overdue,
        ];
    }

    public function tabCounts(Builder $scopedBase): array
    {
        $kpis = $this->kpis($scopedBase);
        $followupsDueItems = (clone $scopedBase)->where(function (Builder $q) {
            $q->where('status', 'Follow-up Due')
                ->orWhereHas('followups', function (Builder $f) {
                    $f->whereNotIn('status', ['Completed', 'Cancelled'])
                        ->whereDate('scheduled_date', now()->toDateString());
                });
        })->count();
        $overdueItems = (clone $scopedBase)->whereHas('followups', function (Builder $f) {
            $f->whereNotIn('status', ['Completed', 'Cancelled'])
                ->whereDate('scheduled_date', '<', now()->toDateString());
        })->count();

        return [
            'all' => $kpis['all'],
            'pending' => $kpis['pending'],
            'verified' => $kpis['verified'],
            'followups_due' => $followupsDueItems,
            'overdue' => $overdueItems,
        ];
    }

    public function log(
        GoalPmsItem $item,
        string $activityType,
        string $description,
        ?User $actor = null,
        ?string $old = null,
        ?string $new = null
    ): void {
        GoalPmsActivityLog::create([
            'goal_pms_item_id' => $item->id,
            'goal_loan_case_id' => $item->goal_loan_case_id,
            'user_id' => $actor?->id,
            'activity_type' => $activityType,
            'description' => $description,
            'old_value' => $old,
            'new_value' => $new,
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }

    public function startVerification(GoalPmsItem $item, User $actor): GoalPmsItem
    {
        if (! $item->canStartVerification() && $item->status !== 'In Progress') {
            throw new RuntimeException('Verification cannot be started for this status.');
        }
        if ($item->status === 'Verified' || $item->status === 'Closed') {
            throw new RuntimeException('Completed PMS records cannot be reopened.');
        }

        $old = $item->status;
        $item->update([
            'status' => 'In Progress',
            'verification_started_at' => $item->verification_started_at ?: now(),
            'assigned_to' => $item->assigned_to ?: $actor->id,
        ]);

        GoalPmsVerification::create([
            'goal_pms_item_id' => $item->id,
            'verification_result' => 'In Progress',
            'verification_remarks' => 'Verification started',
            'verified_by' => $actor->id,
            'verified_at' => now(),
        ]);

        $this->log($item, 'verification_started', 'Verification started', $actor, $old, 'In Progress');
        $this->auditCase($item, 'PMS verification started', $actor, ['to' => 'In Progress']);

        return $item->fresh();
    }

    public function completeVerification(GoalPmsItem $item, User $actor, array $data, Pd01Service $pd01): GoalPmsItem
    {
        if (! $item->canCompleteVerification()) {
            throw new RuntimeException('This PMS item cannot be verified in its current status.');
        }

        $result = $data['verification_result'] ?? 'Verified';
        $remarks = trim((string) ($data['verification_remarks'] ?? $data['notes'] ?? ''));

        if (in_array($result, ['Rejected', 'On Hold'], true) && $remarks === '') {
            throw new RuntimeException('Remarks are required for Rejected and On Hold.');
        }

        return DB::transaction(function () use ($item, $actor, $result, $remarks, $data, $pd01) {
            $old = $item->status;
            $updates = [
                'status' => $result === 'Verified' ? 'Verified' : $result,
                'verification_remarks' => $remarks ?: null,
                'notes' => $remarks ?: ($data['notes'] ?? $item->notes),
                'activity_type' => $data['activity_type'] ?? $item->activity_type,
                'referral_org_unit_id' => $data['referral_org_unit_id'] ?? $item->referral_org_unit_id,
                'follow_up_at' => $data['follow_up_at'] ?? $item->follow_up_at,
                'assigned_to' => $item->assigned_to ?: $actor->id,
            ];

            if ($result === 'Verified') {
                $updates['verified_by'] = $actor->id;
                $updates['verified_at'] = now();
                $updates['verification_started_at'] = $item->verification_started_at ?: now();
            }

            if ($result === 'Verified' && ! empty($data['follow_up_at'])) {
                $updates['status'] = 'Follow-up Due';
                $updates['follow_up_at'] = $data['follow_up_at'];
                // Still mark verified fields so payout eligibility can unlock.
                $updates['verified_by'] = $actor->id;
                $updates['verified_at'] = now();
                // Keep payout gate on Verified — temporarily set Verified then Follow-up Due would break payout.
                // Prefer status Verified and schedule follow-up separately.
                $updates['status'] = 'Verified';
            }

            $item->update($updates);

            GoalPmsVerification::create([
                'goal_pms_item_id' => $item->id,
                'verification_result' => $result,
                'verification_remarks' => $remarks ?: null,
                'verified_by' => $actor->id,
                'verified_at' => now(),
            ]);

            $case = $item->loanCase;
            if ($case && $result === 'Verified') {
                $pd01->refreshPayoutEligibility($case);
            }

            $activity = match ($result) {
                'Rejected' => 'verification_rejected',
                'On Hold' => 'verification_on_hold',
                default => 'verification_completed',
            };
            $this->log($item, $activity, 'Verification '.$result, $actor, $old, $item->status);
            $this->auditCase($item, 'PMS '.$result, $actor, ['to' => $item->status, 'body' => $remarks ?: null]);

            if ($case && $item->assigned_to) {
                $assignee = User::find($item->assigned_to);
                GoalNotify::send(
                    $assignee,
                    'PMS verification '.$result,
                    ($case->case_no ?: 'Case').' — '.$result,
                    route('goal.pms.show', $item),
                    'pms-verify-'.$item->id.'-'.$result,
                    'pd01',
                    $case->case_no
                );
            }

            return $item->fresh();
        });
    }

    public function reassign(GoalPmsItem $item, User $actor, int $newUserId, ?string $reason = null): GoalPmsItem
    {
        $previous = $item->assigned_to;
        if ((int) $previous === $newUserId) {
            return $item;
        }

        $item->update(['assigned_to' => $newUserId]);

        GoalPmsAssignmentHistory::create([
            'goal_pms_item_id' => $item->id,
            'previous_user_id' => $previous,
            'new_user_id' => $newUserId,
            'assigned_by' => $actor->id,
            'reassignment_reason' => $reason,
            'assigned_at' => now(),
        ]);

        $this->log($item, 'reassigned', 'Case reassigned', $actor, (string) $previous, (string) $newUserId);
        $this->auditCase($item, 'PMS reassigned', $actor, ['from' => (string) $previous, 'to' => (string) $newUserId, 'body' => $reason]);

        $newUser = User::find($newUserId);
        $case = $item->loanCase;
        GoalNotify::send(
            $newUser,
            'PMS case assigned',
            ($case?->case_no ?: 'Case').' assigned to you',
            route('goal.pms.show', $item),
            'pms-assign-'.$item->id.'-'.$newUserId,
            'pd01',
            $case?->case_no
        );

        return $item->fresh();
    }

    public function createFollowup(GoalPmsItem $item, User $actor, array $data): GoalPmsFollowup
    {
        $followup = GoalPmsFollowup::create([
            'goal_pms_item_id' => $item->id,
            'goal_loan_case_id' => $item->goal_loan_case_id,
            'followup_type' => $data['followup_type'],
            'subject' => $data['subject'],
            'description' => $data['description'] ?? null,
            'scheduled_date' => $data['scheduled_date'],
            'scheduled_time' => $data['scheduled_time'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? $item->assigned_to ?? $actor->id,
            'status' => 'Scheduled',
            'priority' => $data['priority'] ?? 'Medium',
            'created_by' => $actor->id,
        ]);

        $item->update([
            'follow_up_at' => Carbon::parse($data['scheduled_date'].' '.($data['scheduled_time'] ?? '09:00')),
            'activity_type' => 'follow_up',
        ]);

        $this->log($item, 'followup_scheduled', 'Follow-up scheduled: '.$followup->subject, $actor, null, $followup->scheduled_date->format('Y-m-d'));
        $this->auditCase($item, 'PMS follow-up scheduled', $actor, ['body' => $followup->subject]);

        $assignee = User::find($followup->assigned_to);
        GoalNotify::send(
            $assignee,
            'PMS follow-up scheduled',
            $followup->subject.' on '.$followup->scheduled_date->format('d M Y'),
            route('goal.pms.show', $item),
            'pms-fu-'.$followup->id.'-sched',
            'pd01',
            $item->loanCase?->case_no
        );

        return $followup;
    }

    public function completeFollowup(GoalPmsFollowup $followup, User $actor, ?string $remarks = null): GoalPmsFollowup
    {
        if ($followup->status === 'Completed') {
            return $followup;
        }
        $followup->update([
            'status' => 'Completed',
            'completed_at' => now(),
            'completed_by' => $actor->id,
            'completion_remarks' => $remarks,
        ]);

        $item = $followup->pmsItem;
        if ($item) {
            $this->log($item, 'followup_completed', 'Follow-up completed: '.$followup->subject, $actor);
            $this->auditCase($item, 'PMS follow-up completed', $actor, ['body' => $followup->subject]);
        }

        return $followup->fresh();
    }

    public function cancelFollowup(GoalPmsFollowup $followup, User $actor, ?string $remarks = null): GoalPmsFollowup
    {
        $followup->update([
            'status' => 'Cancelled',
            'completion_remarks' => $remarks,
        ]);
        $item = $followup->pmsItem;
        if ($item) {
            $this->log($item, 'followup_cancelled', 'Follow-up cancelled: '.$followup->subject, $actor);
        }

        return $followup->fresh();
    }

    public function rescheduleFollowup(GoalPmsFollowup $followup, User $actor, string $date, ?string $time = null): GoalPmsFollowup
    {
        $old = $followup->scheduled_date?->format('Y-m-d');
        $followup->update([
            'scheduled_date' => $date,
            'scheduled_time' => $time,
            'status' => 'Scheduled',
        ]);
        $item = $followup->pmsItem;
        if ($item) {
            $item->update([
                'follow_up_at' => Carbon::parse($date.' '.($time ?: '09:00')),
            ]);
            $this->log($item, 'followup_rescheduled', 'Follow-up rescheduled', $actor, $old, $date);
        }

        return $followup->fresh();
    }

    public function workflowEvents(GoalPmsItem $item): array
    {
        $item->loadMissing(['disbursement', 'loanCase.accountsItems', 'loanCase.payouts']);
        $disb = $item->disbursement;
        $accounts = $disb?->accountsItem;
        $payout = $disb?->payout;

        $payoutStatus = $payout?->status;

        return [
            [
                'label' => 'Verified Disbursement',
                'detail' => 'PD-01 completed',
                'at' => $disb?->verified_at,
                'done' => (bool) $disb?->verified_at,
                'state' => $disb?->verified_at ? 'done' : 'pending',
                'badge' => null,
            ],
            [
                'label' => 'PMS Queue Created',
                'detail' => $item->statusLabel(),
                'at' => $item->created_at,
                'done' => true,
                'state' => 'done',
                'badge' => null,
            ],
            [
                'label' => 'Accounts Queue Created',
                'detail' => 'Opened in parallel',
                'at' => $accounts?->created_at,
                'done' => (bool) $accounts,
                'state' => $accounts ? 'done' : 'pending',
                'badge' => $accounts?->status,
            ],
            [
                'label' => 'Payout placed On Hold',
                'detail' => 'Awaiting Accounts + PMS clearance',
                'at' => $payout?->created_at,
                'done' => (bool) $payout,
                'state' => ($payoutStatus === 'On Hold') ? 'hold' : (($payoutStatus === 'Eligible' || $payoutStatus === 'Paid') ? 'done' : 'pending'),
                'badge' => $payoutStatus,
            ],
        ];
    }

    protected function auditCase(GoalPmsItem $item, string $title, User $actor, array $meta = []): void
    {
        $case = $item->loanCase;
        if (! $case) {
            return;
        }
        GoalAuditService::recordFinancial(
            $case,
            GoalAuditService::TYPE_PMS,
            $title,
            $item->fresh(),
            $actor,
            $meta
        );
    }
}
