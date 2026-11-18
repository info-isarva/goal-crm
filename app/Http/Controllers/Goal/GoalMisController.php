<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Models\GoalLoanCase;
use App\Models\GoalOrgUnit;
use App\Models\GoalRevenueEntry;
use App\Services\Goal\Pd01Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoalMisController extends Controller
{
    use AuthorizesGoal;

    public function branchMis(Request $request)
    {
        $this->requireGoalPermission('branch_mis_guard');

        $filters = $this->validatedBranchFilters($request);
        $query = $this->branchMisQuery($filters);
        $kpis = $this->branchMisKpis(clone $query);
        $kpis['rev01_revenue'] = (float) $this->scopeByOrgColumn(GoalRevenueEntry::query())->sum('amount');

        $misSvc = app(\App\Services\Goal\GoalBranchMisService::class);
        $cases = (clone $query)->paginate(50)->withQueryString();
        $cases->getCollection()->transform(function (GoalLoanCase $case) use ($misSvc) {
            $case->setAttribute(
                'branch_mis',
                $misSvc->apply($case, is_array($case->branch_mis) ? $case->branch_mis : [])
            );

            return $case;
        });

        $branches = GoalOrgUnit::query()
            ->where('type', GoalOrgUnit::TYPE_BRANCH)
            ->orderBy('name')
            ->get(['id', 'name']);
        $products = GoalLoanCase::query()
            ->whereNotNull('product')
            ->where('product', '!=', '')
            ->distinct()
            ->orderBy('product')
            ->pluck('product');
        $statuses = GoalLoanCase::query()
            ->whereNotNull('status')
            ->where('status', '!=', '')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');
        $banks = GoalLoanCase::query()
            ->tap(fn (Builder $b) => $this->scopeByOrgColumn($b))
            ->whereNotNull('lender_name')
            ->where('lender_name', '!=', '')
            ->distinct()
            ->orderBy('lender_name')
            ->pluck('lender_name');
        $otcOptions = ['Pending PDD', 'PDD Cleared', 'OTC Cleared'];
        $months = $this->branchMisMonthOptions();

        return view('goal.mis.branch', compact(
            'cases', 'kpis', 'filters', 'branches', 'products', 'statuses', 'banks', 'otcOptions', 'months'
        ));
    }

    public function exportBranchMis(Request $request): StreamedResponse
    {
        $this->requireGoalPermission('branch_mis_guard');

        $filters = $this->validatedBranchFilters($request);
        $rows = $this->branchMisQuery($filters)->limit(5000)->get();
        $filename = 'branch-mis-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'Ref Number', 'LAN Number', 'Customer Name', 'Customer Contact', 'Month',
                'Login Date', 'Login Amount', 'Sanction Dt', 'Sanc Amt', 'Disb Date', 'Disb amt',
                'Code', 'Product', 'Sub Product', 'Status', 'OTC/PDD Status', 'Location',
                'Source', 'Source Name', 'GSM Name', 'Bank Name', 'Banker Name', 'Banker Contact', 'Banker Mail ID',
                'PayIn%', 'Payin Amt', 'Payout %', 'Payout Amount', 'Net Revenue',
                'SM Remark', 'Payout Month',
                'Ref - 1 Name', 'Ref - 1 Contact', 'Ref - 2 Name', 'Ref - 2 Contact',
                'DOB Applicant', 'Co-Applicant', 'Owner', 'Branch',
            ]);

            foreach ($rows as $case) {
                $m = is_array($case->branch_mis) ? $case->branch_mis : [];
                $verifiedDisb = $case->disbursements
                    ->where('status', 'Verified')
                    ->sortByDesc('disbursed_on')
                    ->first()
                    ?? $case->disbursements->sortByDesc('disbursed_on')->first();
                $disbAmt = $verifiedDisb?->amount
                    ?? $this->misVal($m, ['disb_amt', 'disb_amount', 'disbursement_amount']);
                $disbDate = optional($verifiedDisb?->disbursed_on)->format('d-M-Y')
                    ?: $this->misVal($m, ['disb_date', 'disbursement_date']);

                // Prefer auto-calc when disb + % present (keeps export consistent with workspace).
                $m = app(\App\Services\Goal\GoalBranchMisService::class)->apply($case, $m);

                fputcsv($out, [
                    $case->case_no,
                    $this->misVal($m, ['lan_number', 'lan', 'lan_no']),
                    $case->customer_name,
                    $case->mobile,
                    $this->misVal($m, ['month']),
                    $this->misVal($m, ['login_date']),
                    $this->misVal($m, ['login_amount', 'login_amt']),
                    optional($case->sanction_date)->format('d-M-Y') ?: $this->misVal($m, ['sanction_dt', 'sanction_date']),
                    $case->sanction_amount !== null
                        ? number_format((float) $case->sanction_amount, 2, '.', '')
                        : $this->misVal($m, ['sanc_amt', 'sanction_amount']),
                    $disbDate ?: $this->misVal($m, ['disb_date']),
                    isset($m['disb_amount']) && $m['disb_amount'] !== ''
                        ? number_format((float) $m['disb_amount'], 2, '.', '')
                        : ($disbAmt !== null && $disbAmt !== ''
                            ? (is_numeric($disbAmt) ? number_format((float) $disbAmt, 2, '.', '') : $disbAmt)
                            : ''),
                    $this->misVal($m, ['code']),
                    $case->product,
                    $this->misVal($m, ['sub_product']),
                    $case->status,
                    $this->misVal($m, ['otc_pdd_status', 'otc_pdd']),
                    $case->orgUnit?->name ?: $this->misVal($m, ['location']),
                    $this->misVal($m, ['source']),
                    $this->misVal($m, ['source_name']),
                    $this->misVal($m, ['gsm_name']),
                    $this->misVal($m, ['bank_name']) ?: $case->lender_name,
                    $this->misVal($m, ['banker_name']),
                    $this->misVal($m, ['banker_contact']),
                    $this->misVal($m, ['banker_mail', 'banker_email']),
                    $this->misVal($m, ['payin_pct', 'pay_in_pct']),
                    $this->misVal($m, ['payin_amt', 'pay_in_amt']),
                    $this->misVal($m, ['payout_pct']),
                    $this->misVal($m, ['payout_amount', 'payout_amt']),
                    $this->misVal($m, ['net_revenue']),
                    $this->misVal($m, ['sm_remark']),
                    $this->misVal($m, ['payout_month']),
                    $this->misVal($m, ['ref1_name']),
                    $this->misVal($m, ['ref1_contact']),
                    $this->misVal($m, ['ref2_name']),
                    $this->misVal($m, ['ref2_contact']),
                    $this->misVal($m, ['dob_applicant']),
                    $this->misVal($m, ['co_applicant']),
                    $case->owner?->name,
                    $case->orgUnit?->name,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function revenueLedger(Request $request)
    {
        $this->requireGoalPermission('revenue_ledger_guard');

        $q = trim((string) $request->input('q', ''));
        $query = $this->ledgerQuery($q);

        $kpis = [
            'total' => (clone $query)->count(),
            'amount' => (float) (clone $query)->sum('amount'),
        ];
        $entries = (clone $query)->paginate(50)->withQueryString();
        $filters = ['q' => $q];
        $rollups = $this->orgRollups();
        $cases = GoalLoanCase::query()
            ->tap(fn (Builder $b) => $this->scopeByOrgColumn($b))
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'case_no', 'customer_name']);

        return view('goal.mis.revenue', compact('entries', 'kpis', 'filters', 'rollups', 'cases'));
    }

    public function exportRevenueLedger(Request $request): StreamedResponse
    {
        $this->requireGoalPermission('revenue_ledger_guard');

        $q = trim((string) $request->input('q', ''));
        $rows = $this->ledgerQuery($q)->limit(5000)->get();
        $filename = 'revenue-ledger-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['When', 'Case', 'Customer', 'Amount', 'Type', 'Org Unit', 'Note']);
            foreach ($rows as $e) {
                fputcsv($out, [
                    optional($e->created_at)->format('Y-m-d H:i'),
                    $e->loanCase?->case_no,
                    $e->loanCase?->customer_name,
                    number_format((float) $e->amount, 2, '.', ''),
                    $e->entry_type ?: 'recognized',
                    $e->orgUnit?->name,
                    $e->note,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function adjustRevenue(Request $request, Pd01Service $pd01)
    {
        $this->requireGoalPermission('revenue_ledger_guard');
        // SoD: adjustments also require target-management capability (REV-03).
        $this->requireGoalPermission('manage_targets_guard');

        $data = $request->validate([
            'goal_loan_case_id' => 'required|exists:goal_loan_cases,id',
            'amount' => 'required|numeric',
            'entry_type' => 'required|in:adjustment,reversal',
            'note' => 'required|string|max:255',
        ]);

        $case = GoalLoanCase::query()->findOrFail((int) $data['goal_loan_case_id']);
        $scope = $this->scopedOrgIds();
        $orgId = (int) ($case->goal_org_unit_id ?? 0);
        if ($scope === [] || ! $orgId || ! in_array($orgId, array_map('intval', $scope), true)) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $pd01->adjustRevenue(
                $case,
                (float) $data['amount'],
                $data['note'],
                auth()->user(),
                $data['entry_type']
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'REV-03 '.$data['entry_type'].' posted.');
    }

    protected function ledgerQuery(string $q = ''): Builder
    {
        $query = $this->scopeByOrgColumn(
            GoalRevenueEntry::query()->with(['loanCase', 'orgUnit'])->latest()
        );
        if ($q !== '') {
            $query->where(function (Builder $b) use ($q) {
                $b->where('note', 'like', '%'.$q.'%')
                    ->orWhereHas('loanCase', function (Builder $c) use ($q) {
                        $c->where('case_no', 'like', '%'.$q.'%')
                            ->orWhere('customer_name', 'like', '%'.$q.'%');
                    });
            });
        }

        return $query;
    }

    /**
     * Cluster / region rollups from scoped REV-01 ledger (branch amounts rolled to ancestors).
     */
    protected function orgRollups(): array
    {
        $entries = $this->scopeByOrgColumn(
            GoalRevenueEntry::query()->select(['goal_org_unit_id', 'amount'])
        )->get();

        $units = GoalOrgUnit::query()->get()->keyBy('id');
        $totals = [];
        foreach ($entries as $entry) {
            $orgId = (int) ($entry->goal_org_unit_id ?? 0);
            if (! $orgId || ! isset($units[$orgId])) {
                continue;
            }
            $chain = array_merge([$orgId], $units[$orgId]->ancestorIds());
            foreach ($chain as $id) {
                $totals[$id] = ($totals[$id] ?? 0) + (float) $entry->amount;
            }
        }

        $rows = [];
        foreach ($totals as $id => $amount) {
            $unit = $units[$id] ?? null;
            if (! $unit) {
                continue;
            }
            $rows[] = [
                'id' => $id,
                'name' => $unit->name,
                'type' => $unit->type,
                'amount' => round($amount, 2),
            ];
        }

        usort($rows, function ($a, $b) {
            $rank = ['region' => 0, 'cluster' => 1, 'branch' => 2];

            return ($rank[$a['type']] ?? 9) <=> ($rank[$b['type']] ?? 9)
                ?: strcmp($a['name'], $b['name']);
        });

        return $rows;
    }

    protected function validatedBranchFilters(Request $request): array
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:150',
            'status' => 'nullable|string|max:80',
            'product' => 'nullable|string|max:100',
            'branch' => 'nullable|integer',
            'month' => 'nullable|string|max:20',
            'bank' => 'nullable|string|max:120',
            'otc_pdd' => 'nullable|string|max:40',
        ]);

        return [
            'q' => trim((string) ($data['q'] ?? '')),
            'status' => (string) ($data['status'] ?? ''),
            'product' => (string) ($data['product'] ?? ''),
            'branch' => isset($data['branch']) ? (int) $data['branch'] : null,
            'month' => trim((string) ($data['month'] ?? '')),
            'bank' => trim((string) ($data['bank'] ?? '')),
            'otc_pdd' => trim((string) ($data['otc_pdd'] ?? '')),
        ];
    }

    protected function branchMisQuery(array $filters): Builder
    {
        $query = GoalLoanCase::query()
            ->with(['owner', 'orgUnit', 'disbursements', 'bankApplications', 'lead'])
            ->latest();

        $this->scopeByOrgColumn($query);

        if (! empty($filters['branch'])) {
            $query->where('goal_org_unit_id', $filters['branch']);
        }
        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
        if ($filters['product'] !== '') {
            $query->where('product', $filters['product']);
        }
        if ($filters['bank'] !== '') {
            $bank = $filters['bank'];
            $query->where(function (Builder $b) use ($bank) {
                $b->where('lender_name', $bank)
                    ->orWhere('branch_mis->bank_name', $bank);
            });
        }
        if ($filters['month'] !== '') {
            $month = $filters['month'];
            $query->where(function (Builder $b) use ($month) {
                $b->where('branch_mis->month', $month)
                    ->orWhere('branch_mis->payout_month', $month);
            });
        }
        if ($filters['otc_pdd'] !== '') {
            $otc = $filters['otc_pdd'];
            $query->where(function (Builder $b) use ($otc) {
                $b->where('branch_mis->otc_pdd', $otc)
                    ->orWhere('branch_mis->otc_pdd_status', $otc);
            });
        }
        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function (Builder $b) use ($q) {
                $b->where('case_no', 'like', '%'.$q.'%')
                    ->orWhere('customer_name', 'like', '%'.$q.'%')
                    ->orWhere('mobile', 'like', '%'.$q.'%')
                    ->orWhere('lender_name', 'like', '%'.$q.'%')
                    ->orWhere('application_ref', 'like', '%'.$q.'%')
                    ->orWhere('branch_mis->lan_number', 'like', '%'.$q.'%');
            });
        }

        return $query;
    }

    /**
     * Prototype KPIs: Cases in MIS · Disbursed amt · Payout amt · Net revenue.
     */
    protected function branchMisKpis(Builder $query): array
    {
        $svc = app(\App\Services\Goal\GoalBranchMisService::class);
        $total = (clone $query)->count();
        $disbursedCount = (clone $query)->where('status', 'Disbursed')->count();
        $sanctioned = (clone $query)->where('status', 'Sanctioned')->count();

        $disbAmt = 0.0;
        $payoutAmt = 0.0;
        $net = 0.0;

        foreach (
            (clone $query)
                ->with(['disbursements', 'orgUnit', 'bankApplications', 'owner', 'lead'])
                ->limit(2000)
                ->get() as $case
        ) {
            $m = $svc->apply($case, is_array($case->branch_mis) ? $case->branch_mis : []);
            $disbAmt += $this->misMoney($m, ['disb_amount', 'disb_amt']);
            $payoutAmt += $this->misMoney($m, ['payout_amount', 'payout_amt']);
            $net += $this->misMoney($m, ['net_revenue']);
        }

        return [
            'total' => $total,
            'disbursed' => $disbursedCount,
            'sanctioned' => $sanctioned,
            'disb_amount' => $disbAmt,
            'payout_amount' => $payoutAmt,
            'net_revenue' => $net,
        ];
    }

    /** @return list<string> */
    protected function branchMisMonthOptions(): array
    {
        $months = [];
        $cursor = now()->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $months[] = $cursor->copy()->subMonths($i)->format('M-Y');
        }

        return $months;
    }

    protected function misMoney(array $m, array $keys): float
    {
        $v = $this->misVal($m, $keys);
        if ($v === '') {
            return 0.0;
        }
        $clean = str_replace([',', '₹', ' '], '', $v);

        return is_numeric($clean) ? (float) $clean : 0.0;
    }

    protected function misVal(array $m, array $keys, string $default = ''): string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $m)) {
                continue;
            }
            $v = $m[$key];
            if ($v === null || $v === '') {
                continue;
            }

            return is_scalar($v) ? (string) $v : $default;
        }

        return $default;
    }
}
