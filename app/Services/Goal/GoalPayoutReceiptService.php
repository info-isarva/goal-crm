<?php

namespace App\Services\Goal;

use App\Models\GoalPayout;
use App\Models\GoalPayoutAdjustment;
use App\Models\GoalPayoutReceipt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoalPayoutReceiptService
{
    public const TOLERANCE = 0.01;

    /**
     * Append-only receipt. Never overwrites prior receipts.
     *
     * @param  array{amount:float|string,received_on:string,reference?:?string,difference_reason?:?string}  $data
     */
    public function record(GoalPayout $payout, array $data, User $actor): GoalPayoutReceipt
    {
        if (! in_array($payout->status, ['Eligible', 'Processing'], true)) {
            throw ValidationException::withMessages([
                'amount' => 'Payout must be Eligible/Processing to record receipts.',
            ]);
        }

        $amount = round((float) $data['amount'], 2);
        if ($amount < self::TOLERANCE) {
            throw ValidationException::withMessages([
                'amount' => 'Receipt amount must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use ($payout, $data, $actor, $amount) {
            $payout = GoalPayout::query()->lockForUpdate()->findOrFail($payout->id);

            $expected = (float) $payout->expected_amount;
            $adjustments = (float) $payout->adjustments()->sum('amount');
            $projected = round((float) $payout->received_amount + $amount, 2);
            $outstandingAfter = round(max(0, $expected - $projected - $adjustments), 2);
            $isPartial = $outstandingAfter > self::TOLERANCE;

            if ($isPartial && ! filled($data['difference_reason'] ?? null) && ! filled($payout->difference_reason)) {
                throw ValidationException::withMessages([
                    'difference_reason' => 'Difference reason is required for partial receipts (ACC reconciliation).',
                ]);
            }

            $receipt = GoalPayoutReceipt::create([
                'goal_payout_id' => $payout->id,
                'amount' => $amount,
                'received_on' => $data['received_on'],
                'reference' => $data['reference'] ?? null,
                'recorded_by' => $actor->id,
            ]);

            $payout->received_amount = $projected;
            if (! $isPartial) {
                $payout->status = 'Paid';
                $payout->reconciliation_status = 'Matched';
                $payout->difference_reason = null;
            } else {
                $payout->status = 'Processing';
                $payout->reconciliation_status = 'Partial';
                if (! empty($data['difference_reason'])) {
                    $payout->difference_reason = $data['difference_reason'];
                }
            }
            $payout->save();

            if ($payout->loanCase) {
                GoalAuditService::recordFinancial(
                    $payout->loanCase,
                    GoalAuditService::TYPE_PAYOUT,
                    'Payout receipt recorded',
                    $payout->fresh(),
                    $actor,
                    ['to' => $payout->status, 'body' => 'Received '.$amount]
                );
            }

            return $receipt;
        });
    }

    /**
     * @param  array{amount:float|string,reason:string}  $data
     */
    public function addAdjustment(GoalPayout $payout, array $data, User $actor): GoalPayoutAdjustment
    {
        $adjustment = GoalPayoutAdjustment::create([
            'goal_payout_id' => $payout->id,
            'amount' => round((float) $data['amount'], 2),
            'reason' => $data['reason'],
            'created_by' => $actor->id,
        ]);

        $this->refreshTotals($payout->fresh(['adjustments', 'receipts']));

        if ($payout->loanCase) {
            GoalAuditService::recordFinancial(
                $payout->loanCase,
                GoalAuditService::TYPE_PAYOUT,
                'Payout adjustment recorded',
                $payout->fresh(),
                $actor,
                ['to' => $payout->fresh()->status, 'body' => $data['reason']]
            );
        }

        return $adjustment;
    }

    public function refreshTotals(GoalPayout $payout): GoalPayout
    {
        $expected = (float) $payout->expected_amount;
        $received = (float) $payout->receipts()->sum('amount');
        $adjustments = (float) $payout->adjustments()->sum('amount');
        $outstanding = round(max(0, $expected - $received - $adjustments), 2);

        $payout->received_amount = $received;

        if (in_array($payout->status, ['Exception', 'On Hold'], true)) {
            $payout->save();

            return $payout;
        }

        if ($outstanding <= self::TOLERANCE && $received > 0) {
            $payout->status = 'Paid';
            $payout->reconciliation_status = 'Matched';
            $payout->difference_reason = null;
        } elseif ($received > 0) {
            $payout->status = 'Processing';
            $payout->reconciliation_status = 'Partial';
        }

        $payout->save();

        return $payout;
    }

    public static function expectedFromCase(float $disbursedAmount, ?array $branchMis): float
    {
        $misAmount = isset($branchMis['payout_amount']) ? (float) $branchMis['payout_amount'] : 0.0;
        if ($misAmount > 0) {
            return round($misAmount, 2);
        }

        $rate = (float) config('goal.payout_rate', 0.00875);

        return round($disbursedAmount * $rate, 2);
    }
}
