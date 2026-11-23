<?php

namespace App\Services\Goal;

use App\Models\GoalAccountsItem;
use App\Models\GoalDisbursement;
use App\Models\GoalLoanCase;
use App\Models\GoalOrgUnit;
use App\Models\GoalPayout;
use App\Models\GoalPmsActivityLog;
use App\Models\GoalPmsItem;
use App\Models\GoalRevenueEntry;
use App\Models\GoalTarget;
use App\Models\User;
use App\Services\Goal\GoalPayoutReceiptService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Pd01Service
{
    /**
     * Record a disbursement pending verification (does not recognize revenue).
     */
    public function recordPending(GoalLoanCase $case, array $data, User $actor): GoalDisbursement
    {
        if (! in_array($case->status, ['Sanctioned', 'Disbursed'], true)) {
            throw new RuntimeException('Disbursement can only be recorded from Sanctioned (or additional partials after Disbursed).');
        }

        $gate = GoalWorkflow::validatePd01([
            'from' => 'Sanctioned',
            'amount' => $data['amount'] ?? null,
            'date' => $data['disbursed_on'] ?? null,
            'ref' => $data['bank_reference'] ?? null,
        ]);
        if (! $gate['ok']) {
            throw new RuntimeException(implode(' ', $gate['errors']));
        }

        $sanctionCap = (float) ($data['sanction_cap'] ?? $case->sanction_amount ?? 0);
        $verifiedTotal = (float) $case->disbursements()
            ->whereNotNull('verified_at')
            ->sum('amount');
        $pendingTotal = (float) $case->disbursements()
            ->whereNull('verified_at')
            ->where('status', 'Pending Verification')
            ->sum('amount');
        $incoming = (float) $data['amount'];
        if ($sanctionCap > 0 && ($verifiedTotal + $pendingTotal + $incoming) > ($sanctionCap + 0.01)) {
            throw new RuntimeException('Disbursement exceeds lender-approved sanction amount.');
        }

        $key = $data['idempotency_key'] ?? ('pd01-pending-'.$case->id.'-'.md5(($data['bank_reference'] ?? '').'|'.($data['amount'] ?? '').'|'.($data['disbursed_on'] ?? '')));
        $existing = GoalDisbursement::query()->where('idempotency_key', $key)->first();
        if ($existing) {
            return $existing;
        }

        return GoalDisbursement::create([
            'goal_loan_case_id' => $case->id,
            'goal_bank_application_id' => $data['goal_bank_application_id'] ?? null,
            'goal_sanction_id' => $data['goal_sanction_id'] ?? null,
            'amount' => $data['amount'],
            'disbursed_on' => $data['disbursed_on'],
            'bank_reference' => $data['bank_reference'],
            'evidence_note' => $data['evidence_note'] ?? null,
            'status' => 'Pending Verification',
            'created_by' => $actor->id,
            'idempotency_key' => $key,
        ]);
    }

    /**
     * Verify a pending disbursement and run REV-01 / PMS / Accounts / payout On Hold.
     */
    public function verify(GoalDisbursement $disbursement, User $actor): GoalDisbursement
    {
        if ($disbursement->verified_at) {
            return $disbursement;
        }

        $case = $disbursement->loanCase;
        if (! $case) {
            throw new RuntimeException('Disbursement case not found.');
        }

        return DB::transaction(function () use ($disbursement, $case, $actor) {
            $disbursement->verified_by = $actor->id;
            $disbursement->verified_at = now();
            $disbursement->status = 'Verified';
            $disbursement->save();

            $revenueRate = (float) config('goal.revenue_rate', 0.0035);
            $payoutRate = (float) config('goal.payout_rate', 0.00875);
            $revenueAmount = round(((float) $disbursement->amount) * $revenueRate, 2);

            if (! $disbursement->revenueEntry) {
                GoalRevenueEntry::create([
                    'goal_loan_case_id' => $case->id,
                    'goal_disbursement_id' => $disbursement->id,
                    'amount' => $revenueAmount,
                    'attributed_user_id' => $case->owner_id,
                    'goal_org_unit_id' => $case->goal_org_unit_id,
                    'entry_type' => GoalRevenueEntry::TYPE_RECOGNIZED,
                    'status' => GoalRevenueEntry::STATUS_POSTED,
                    'posted_at' => now(),
                    'created_by' => $actor->id,
                    'note' => 'REV-01 at verified disbursement',
                    'reason' => 'Revenue credited on verified disbursement',
                ]);
            }

            $pmsCreated = false;
            $pmsItem = $disbursement->pmsItem;
            if (! $pmsItem) {
                $pmsItem = GoalPmsItem::create([
                    'goal_loan_case_id' => $case->id,
                    'goal_disbursement_id' => $disbursement->id,
                    'status' => 'Pending Verification',
                    'assigned_to' => $this->defaultPmsAssigneeId($case),
                ]);
                $pmsCreated = true;
            }

            if (! $disbursement->accountsItem) {
                GoalAccountsItem::create([
                    'goal_loan_case_id' => $case->id,
                    'goal_disbursement_id' => $disbursement->id,
                    'status' => 'Awaiting Report',
                ]);
            }

            if ($case->status === 'Sanctioned') {
                $case->status = 'Disbursed';
            }
            // Keep Branch MIS disb + commercial amounts in sync with verified PD-01.
            $case->branch_mis = (new GoalBranchMisService)->apply($case, is_array($case->branch_mis) ? $case->branch_mis : []);
            $case->save();

            if (! $disbursement->payout) {
                $expectedPayout = GoalPayoutReceiptService::expectedFromCase(
                    (float) $disbursement->amount,
                    is_array($case->branch_mis) ? $case->branch_mis : []
                );
                // Fallback keeps prior rate behaviour if MIS has no payout_amount.
                if ($expectedPayout <= 0) {
                    $expectedPayout = round(((float) $disbursement->amount) * $payoutRate, 2);
                }
                GoalPayout::create([
                    'goal_loan_case_id' => $case->id,
                    'goal_disbursement_id' => $disbursement->id,
                    'expected_amount' => $expectedPayout,
                    'received_amount' => 0,
                    'status' => 'On Hold',
                    'reconciliation_status' => 'Pending',
                ]);
            }

            $this->syncTargetActuals(
                $case,
                (float) $disbursement->amount,
                $revenueAmount,
                optional($disbursement->disbursed_on)->format('Y-m') ?: now()->format('Y-m')
            );

            GoalCaseAudit::record($case, 'disbursement', 'Disbursement verified', $actor, [
                'to' => (string) $disbursement->amount,
                'related_type' => GoalDisbursement::class,
                'related_id' => $disbursement->id,
            ]);

            if ($pmsCreated && $pmsItem) {
                GoalPmsActivityLog::create([
                    'goal_pms_item_id' => $pmsItem->id,
                    'goal_loan_case_id' => $case->id,
                    'user_id' => $actor->id,
                    'activity_type' => 'queue_created',
                    'description' => 'PMS Queue created from verified disbursement (PD-01)',
                    'new_value' => 'Pending Verification',
                    'ip_address' => request()?->ip(),
                    'created_at' => now(),
                ]);

                if ($pmsItem->assigned_to) {
                    GoalNotify::send(
                        User::find($pmsItem->assigned_to),
                        'New PMS Queue assignment',
                        ($case->case_no ?: 'Case').' awaiting PMS verification',
                        route('goal.pms.show', $pmsItem),
                        'pms-created-'.$pmsItem->id,
                        'pd01',
                        $case->case_no
                    );
                }
            }

            return $disbursement->fresh();
        });
    }

    /**
     * Prefer an active PMS role user in the same org tree; otherwise leave unassigned.
     */
    protected function defaultPmsAssigneeId(GoalLoanCase $case): ?int
    {
        $query = User::query()
            ->where('crm_role_type', 13)
            ->where('status', 1);

        if ($case->goal_org_unit_id) {
            $org = GoalOrgUnit::find((int) $case->goal_org_unit_id);
            $orgIds = $org ? $org->descendantIds() : [(int) $case->goal_org_unit_id];
            if ($orgIds !== []) {
                $query->whereIn('goal_org_unit_id', $orgIds);
            }
        }

        $id = $query->orderBy('id')->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * @deprecated Prefer recordPending + verify. Kept for callers that still need a combined API,
     *             but case transition must not use this without evidence.
     */
    public function execute(GoalLoanCase $case, array $data, User $actor): GoalDisbursement
    {
        $pending = $this->recordPending($case, $data, $actor);

        return $this->verify($pending, $actor);
    }

    public function refreshPayoutEligibility(GoalLoanCase $case): void
    {
        $payouts = GoalPayout::query()->where('goal_loan_case_id', $case->id)->get();
        foreach ($payouts as $payout) {
            if (! in_array($payout->status, ['On Hold', 'Eligible'], true)) {
                continue;
            }
            $pmsOk = GoalPmsItem::query()
                ->where('goal_disbursement_id', $payout->goal_disbursement_id)
                ->where(function ($q) {
                    $q->where('status', 'Verified')
                        ->orWhereNotNull('verified_at');
                })
                ->exists();
            // Accounts must enter report AND approve before payout leaves On Hold.
            $accountsOk = GoalAccountsItem::query()
                ->where('goal_disbursement_id', $payout->goal_disbursement_id)
                ->where('status', 'Approved')
                ->whereNotNull('report_number')
                ->where('report_number', '!=', '')
                ->exists();

            $payout->status = ($pmsOk && $accountsOk) ? 'Eligible' : 'On Hold';
            $payout->save();
        }
    }

    /**
     * Roll verified amounts into Approved targets for the period (revenue + disbursement metrics).
     */
    protected function syncTargetActuals(GoalLoanCase $case, float $disbursementAmount, float $revenueAmount, string $period): void
    {
        $orgIds = [];
        if ($case->goal_org_unit_id) {
            $orgIds[] = (int) $case->goal_org_unit_id;
            $unit = GoalOrgUnit::query()->find($case->goal_org_unit_id);
            if ($unit) {
                $orgIds = array_values(array_unique(array_merge($orgIds, $unit->ancestorIds())));
            }
        }

        $targets = GoalTarget::query()
            ->where('period', $period)
            ->where('status', 'Approved')
            ->where(function ($q) use ($case, $orgIds) {
                $q->where(function ($inner) use ($case) {
                    if ($case->owner_id) {
                        $inner->where('user_id', $case->owner_id)->whereNull('goal_org_unit_id');
                    } else {
                        $inner->whereRaw('0 = 1');
                    }
                })->orWhere(function ($inner) use ($orgIds) {
                    if ($orgIds !== []) {
                        $inner->whereIn('goal_org_unit_id', $orgIds)->whereNull('user_id');
                    } else {
                        $inner->whereRaw('0 = 1');
                    }
                });
            })
            ->get();

        foreach ($targets as $target) {
            $add = match ($target->metric) {
                'revenue' => $revenueAmount,
                'disbursement' => $disbursementAmount,
                default => 0.0,
            };
            if ($add == 0.0) {
                continue;
            }
            $target->actual = round((float) $target->actual + $add, 2);
            $target->recalculateClosingShortfall();
            $target->save();
        }
    }

    /**
     * REV-03: manual adjustment / reversal. Never overwrites the original credit.
     *
     * @param  array{
     *   parent_entry_id?: int|null,
     *   direction?: string|null,
     *   evidence_path?: string|null,
     *   remarks?: string|null,
     *   status?: string|null
     * }  $options
     */
    public function adjustRevenue(
        GoalLoanCase $case,
        float $amount,
        string $note,
        User $actor,
        string $entryType = 'adjustment',
        array $options = []
    ): GoalRevenueEntry {
        if (! in_array($entryType, [GoalRevenueEntry::TYPE_ADJUSTMENT, GoalRevenueEntry::TYPE_REVERSAL], true)) {
            throw new RuntimeException('Invalid revenue entry type.');
        }
        if ($amount == 0.0) {
            throw new RuntimeException('Adjustment amount cannot be zero.');
        }

        $parentId = isset($options['parent_entry_id']) ? (int) $options['parent_entry_id'] : null;
        $parent = null;
        if ($parentId) {
            $parent = GoalRevenueEntry::query()->find($parentId);
            if (! $parent || (int) $parent->goal_loan_case_id !== (int) $case->id) {
                throw new RuntimeException('Original revenue entry not found for this case.');
            }
        }

        $direction = $options['direction'] ?? null;
        if ($entryType === GoalRevenueEntry::TYPE_ADJUSTMENT) {
            if ($direction === 'decrease') {
                $amount = -abs($amount);
            } elseif ($direction === 'increase') {
                $amount = abs($amount);
            }
        }

        if ($entryType === GoalRevenueEntry::TYPE_REVERSAL) {
            $amount = -abs($amount);
            if ($parent) {
                $remaining = $parent->reversibleRemaining();
                if (abs($amount) - $remaining > 0.009) {
                    throw new RuntimeException(
                        'Reversal amount exceeds remaining reversible balance of ₹ '.number_format($remaining, 2)
                    );
                }
            }
        }

        $status = $options['status'] ?? GoalRevenueEntry::STATUS_POSTED;
        if (! in_array($status, [GoalRevenueEntry::STATUS_POSTED, GoalRevenueEntry::STATUS_PENDING, GoalRevenueEntry::STATUS_APPROVED], true)) {
            $status = GoalRevenueEntry::STATUS_POSTED;
        }

        return DB::transaction(function () use ($case, $amount, $note, $actor, $entryType, $parent, $direction, $options, $status) {
            $entry = GoalRevenueEntry::create([
                'goal_loan_case_id' => $case->id,
                'goal_disbursement_id' => $parent?->goal_disbursement_id,
                'parent_entry_id' => $parent?->id,
                'amount' => round($amount, 2),
                'attributed_user_id' => $parent?->attributed_user_id ?: $case->owner_id,
                'goal_org_unit_id' => $parent?->goal_org_unit_id ?: $case->goal_org_unit_id,
                'entry_type' => $entryType,
                'adjustment_direction' => $entryType === GoalRevenueEntry::TYPE_ADJUSTMENT ? ($direction ?: ($amount < 0 ? 'decrease' : 'increase')) : null,
                'status' => $status,
                'posted_at' => $status === GoalRevenueEntry::STATUS_PENDING ? null : now(),
                'approved_by' => $status === GoalRevenueEntry::STATUS_PENDING ? null : $actor->id,
                'approved_at' => $status === GoalRevenueEntry::STATUS_PENDING ? null : now(),
                'created_by' => $actor->id,
                'note' => $note !== '' ? $note : ('REV-03 '.$entryType),
                'reason' => $note !== '' ? $note : ('REV-03 '.$entryType),
                'evidence_path' => $options['evidence_path'] ?? null,
            ]);

            if ($status !== GoalRevenueEntry::STATUS_PENDING) {
                $this->syncTargetActuals(
                    $case,
                    0.0,
                    (float) $entry->amount,
                    now()->format('Y-m')
                );
            }

            GoalCaseAudit::record($case, 'revenue', 'Revenue '.$entryType, $actor, [
                'to' => (string) $entry->amount,
                'related_type' => GoalRevenueEntry::class,
                'related_id' => $entry->id,
                'body' => $entry->reason ?: $entry->note,
                'meta' => [
                    'parent_entry_id' => $parent?->id,
                    'entry_ref' => $entry->entry_ref,
                    'status' => $entry->status,
                ],
            ]);

            return $entry;
        });
    }
}
