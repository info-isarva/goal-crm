<?php

namespace App\Services\Goal;

use App\Models\GoalLoanCase;

/**
 * Branch MIS (Excel "Case detail Branch vise updation") sync + commercial auto-calc.
 *
 * Who fills what:
 * - System / case pipeline: identity, product, status, sanction, disbursement, bank, location
 * - Sales / BM (workspace): LAN, code, sub-product, OTC/PDD, source, GSM, banker, %, remarks, refs
 * - Auto-calc: Payin Amt, Payout Amount, Net Revenue from Disb amt × rates
 */
class GoalBranchMisService
{
    /**
     * Merge posted MIS fields, sync pipeline values, and recalculate commercials.
     */
    public function apply(GoalLoanCase $case, ?array $incoming = null): array
    {
        $current = is_array($case->branch_mis) ? $case->branch_mis : [];
        $incoming = is_array($incoming) ? $incoming : [];

        // Manual (sales/BM) keys — posted values win when present.
        $manualKeys = [
            'lan_number', 'code', 'sub_product', 'otc_pdd', 'otc_pdd_status',
            'source', 'source_name', 'gsm_name',
            'banker_name', 'banker_contact', 'banker_mail', 'banker_email',
            'payin_pct', 'payout_pct', 'payout_month', 'sm_remark',
            'ref1_name', 'ref1_contact', 'ref2_name', 'ref2_contact',
            'dob_applicant', 'co_applicant',
            'login_date', 'login_amount',
        ];

        foreach ($manualKeys as $key) {
            if (array_key_exists($key, $incoming)) {
                $current[$key] = $this->clean($incoming[$key]);
            }
        }

        // Alias normalization
        if (isset($current['otc_pdd']) && ! isset($current['otc_pdd_status'])) {
            $current['otc_pdd_status'] = $current['otc_pdd'];
        }
        if (isset($current['banker_mail']) && ! isset($current['banker_email'])) {
            $current['banker_email'] = $current['banker_mail'];
        }

        $current = $this->syncFromCase($case, $current);
        $current = $this->recalculate($current);

        return $current;
    }

    /**
     * Pull system-owned fields from the live case / verified disbursement.
     */
    public function syncFromCase(GoalLoanCase $case, ?array $mis = null): array
    {
        $mis = is_array($mis) ? $mis : (is_array($case->branch_mis) ? $case->branch_mis : []);
        $case->loadMissing(['orgUnit', 'disbursements', 'bankApplications', 'owner', 'lead']);

        $verified = $case->disbursements
            ->whereNotNull('verified_at')
            ->sortByDesc('disbursed_on')
            ->first()
            ?? $case->disbursements->sortByDesc('disbursed_on')->first();

        $loginApp = $case->bankApplications->sortBy('login_date')->first();

        $mis['ref_number'] = $case->case_no;
        $mis['customer_name'] = $case->customer_name;
        $mis['customer_contact'] = $case->mobile;
        $mis['product'] = $case->product;
        $mis['status'] = $case->status;
        $mis['location'] = $case->orgUnit?->name;
        $mis['bank_name'] = $case->lender_name
            ?: ($loginApp?->lender_name ?? ($mis['bank_name'] ?? null));

        if ($case->sanction_date) {
            $mis['sanction_date'] = optional($case->sanction_date)->format('Y-m-d');
            $mis['sanction_dt'] = optional($case->sanction_date)->format('d-M-Y');
        }
        if ($case->sanction_amount !== null) {
            $mis['sanction_amount'] = $this->num($case->sanction_amount);
            $mis['sanc_amt'] = $mis['sanction_amount'];
        }

        if ($verified) {
            $mis['disb_date'] = optional($verified->disbursed_on)->format('Y-m-d');
            $mis['disb_amount'] = $this->num($verified->amount);
            $mis['disb_amt'] = $mis['disb_amount'];
            $mis['month'] = optional($verified->disbursed_on)->format('M-Y')
                ?: ($mis['month'] ?? null);
        }

        if (empty($mis['login_date']) && $loginApp?->login_date) {
            $mis['login_date'] = optional($loginApp->login_date)->format('Y-m-d');
        }
        if (empty($mis['login_amount']) && $loginApp?->requested_amount !== null) {
            $mis['login_amount'] = $this->num($loginApp->requested_amount);
        }

        if (empty($mis['lan_number']) && filled($case->application_ref)) {
            $mis['lan_number'] = $case->application_ref;
        }

        if (empty($mis['gsm_name']) && $case->owner) {
            $mis['gsm_name'] = $case->owner->name;
        }

        if (empty($mis['source']) && $case->lead?->source) {
            $mis['source'] = $case->lead->source;
        }
        if (empty($mis['source_name']) && $case->lead?->channel_partner) {
            $mis['source_name'] = $case->lead->channel_partner;
        }

        // Default commercial % from company policy when blank (stored as percent points).
        if ($this->blank($mis['payin_pct'] ?? null)) {
            $mis['payin_pct'] = round(((float) config('goal.revenue_rate', 0.0035)) * 100, 4);
        }
        if ($this->blank($mis['payout_pct'] ?? null)) {
            $mis['payout_pct'] = round(((float) config('goal.payout_rate', 0.00875)) * 100, 4);
        }

        return $mis;
    }

    /**
     * Payin Amt = Disb × PayIn%/100
     * Payout Amount = Disb × Payout%/100
     * Net Revenue = Payin − Payout
     */
    public function recalculate(array $mis): array
    {
        $disb = $this->num($mis['disb_amount'] ?? $mis['disb_amt'] ?? null);
        $payinPct = $this->num($mis['payin_pct'] ?? null);
        $payoutPct = $this->num($mis['payout_pct'] ?? null);

        if ($disb !== null && $payinPct !== null) {
            $mis['payin_amt'] = round($disb * ($payinPct / 100), 2);
        }
        if ($disb !== null && $payoutPct !== null) {
            $mis['payout_amount'] = round($disb * ($payoutPct / 100), 2);
        }
        if (isset($mis['payin_amt']) && isset($mis['payout_amount'])) {
            $mis['net_revenue'] = round((float) $mis['payin_amt'] - (float) $mis['payout_amount'], 2);
        }

        return $mis;
    }

    protected function clean(mixed $v): mixed
    {
        if ($v === null) {
            return null;
        }
        if (is_string($v)) {
            $v = trim($v);

            return $v === '' ? null : $v;
        }

        return $v;
    }

    protected function blank(mixed $v): bool
    {
        return $v === null || $v === '' || $v === false;
    }

    protected function num(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            return round((float) $v, 4);
        }
        $clean = preg_replace('/[^\d.\-]/', '', (string) $v);

        return $clean === '' || $clean === '.' || $clean === '-'
            ? null
            : round((float) $clean, 4);
    }
}
