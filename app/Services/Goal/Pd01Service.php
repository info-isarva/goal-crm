<?php

namespace App\Services\Goal;

use App\Models\GoalAccountsItem;
use App\Models\GoalDisbursement;
use App\Models\GoalLoanCase;
use App\Models\GoalPayout;
use App\Models\GoalPmsItem;
use App\Models\GoalRevenueEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Pd01Service
{
    /**
     * Record a disbursement pending verification (does not recognize revenue).
     */
    public function recordPending(GoalLoanCase $case, array $data, User $actor): GoalDisbursement
    {
        if (!in_array($case->status, ['Sanctioned', 'Disbursed'], true)) {
            throw new RuntimeException('Disbursement can only be recorded from Sanctioned (or additional partials after Disbursed).');
        }

        $gate = GoalWorkflow::validatePd01([
            'from' => 'Sanctioned',
            'amount' => $data['amount'] ?? null,
            'date' => $data['disbursed_on'] ?? null,
            'ref' => $data['bank_reference'] ?? null,
        ]);
        if (!$gate['ok']) {
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

        $key = $data['idempotency_key'] ?? ('pd01-pending-' . $case->id . '-' . md5(($data['bank_reference'] ?? '') . '|' . ($data['amount'] ?? '') . '|' . ($data['disbursed_on'] ?? '')));
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
        if (!$case) {
            throw new RuntimeException('Disbursement case not found.');
        }

        return DB::transaction(function () use ($disbursement, $case, $actor) {
            $disbursement->verified_by = $actor->id;
            $disbursement->verified_at = now();
            $disbursement->status = 'Verified';
            $disbursement->save();

            if (!$disbursement->revenueEntry) {
                $revenueAmount = round(((float) $disbursement->amount) * 0.0035, 2);
                GoalRevenueEntry::create([
                    'goal_loan_case_id' => $case->id,
                    'goal_disbursement_id' => $disbursement->id,
                    'amount' => $revenueAmount,
                    'attributed_user_id' => $case->owner_id,
                    'goal_org_unit_id' => $case->goal_org_unit_id,
                    'note' => 'REV-01 at verified disbursement',
                ]);
            }

            if (!$disbursement->pmsItem) {
                GoalPmsItem::create([
                    'goal_loan_case_id' => $case->id,
                    'goal_disbursement_id' => $disbursement->id,
                    'status' => 'Pending',
                ]);
            }

            if (!$disbursement->accountsItem) {
                GoalAccountsItem::create([
                    'goal_loan_case_id' => $case->id,
                    'goal_disbursement_id' => $disbursement->id,
                    'status' => 'Awaiting Report',
                ]);
            }

            if (!$disbursement->payout) {
                GoalPayout::create([
                    'goal_loan_case_id' => $case->id,
                    'goal_disbursement_id' => $disbursement->id,
                    'expected_amount' => round(((float) $disbursement->amount) * 0.00875, 2),
                    'received_amount' => 0,
                    'status' => 'On Hold',
                ]);
            }

            if ($case->status === 'Sanctioned') {
                $case->status = 'Disbursed';
                $case->save();
            }

            GoalCaseAudit::record($case, 'disbursement', 'Disbursement verified', $actor, [
                'to' => (string) $disbursement->amount,
                'related_type' => GoalDisbursement::class,
                'related_id' => $disbursement->id,
            ]);

            return $disbursement->fresh();
        });
    }

    /**
     * Atomic PD-01 (legacy path): create + verify in one step.
     * Idempotent when the same idempotency_key is reused.
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
            if (!in_array($payout->status, ['On Hold', 'Eligible'], true)) {
                continue;
            }
            $pmsOk = GoalPmsItem::query()
                ->where('goal_disbursement_id', $payout->goal_disbursement_id)
                ->where('status', 'Verified')
                ->exists();
            $reportOk = GoalAccountsItem::query()
                ->where('goal_disbursement_id', $payout->goal_disbursement_id)
                ->whereNotNull('report_number')
                ->where('report_number', '!=', '')
                ->exists();

            $payout->status = ($pmsOk && $reportOk) ? 'Eligible' : 'On Hold';
            $payout->save();
        }
    }
}
