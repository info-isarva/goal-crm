<?php

namespace App\Http\Controllers\Goal;

use App\Exports\GoalSheetExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Library\MpdfDocument;
use App\Models\GoalLoanCase;
use App\Models\GoalOrgUnit;
use App\Models\GoalRevenueEntry;
use App\Services\FinancialYearService;
use App\Services\Goal\GoalRevenueLedgerService;
use App\Services\Goal\Pd01Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
        $kpis['rev01_revenue'] = (float) app(FinancialYearService::class)
            ->applyToQuery($this->scopeByOrgColumn(GoalRevenueEntry::query()), 'created_at')
            ->sum('amount');
        $filters['fy_label'] = app(FinancialYearService::class)->label();

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

    public function showBranchMis(GoalLoanCase $case)
    {
        $this->requireGoalPermission('branch_mis_guard');

        $scope = $this->scopedOrgIds();
        $orgId = (int) ($case->goal_org_unit_id ?? 0);
        if ($scope === [] || ! $orgId || ! in_array($orgId, array_map('intval', $scope), true)) {
            abort(403, 'Unauthorized action.');
        }

        $case->load(['owner', 'orgUnit', 'disbursements', 'bankApplications', 'lead']);
        $misSvc = app(\App\Services\Goal\GoalBranchMisService::class);
        $mis = $misSvc->apply($case, is_array($case->branch_mis) ? $case->branch_mis : []);

        return view('goal.mis.branch_show', compact('case', 'mis'));
    }

    public function exportBranchMis(Request $request): StreamedResponse|BinaryFileResponse|\Illuminate\Http\Response
    {
        $this->requireGoalPermission('branch_mis_guard');

        $filters = $this->validatedBranchFilters($request);
        $format = strtolower((string) $request->input('format', 'csv'));
        if (! in_array($format, ['csv', 'xlsx', 'excel', 'pdf'], true)) {
            $format = 'csv';
        }
        if ($format === 'excel') {
            $format = 'xlsx';
        }

        $cases = $this->branchMisQuery($filters)->limit(5000)->get();
        $fullHeadings = [
            'Ref Number', 'LAN Number', 'Customer Name', 'Customer Contact', 'Month',
            'Login Date', 'Login Amount', 'Sanction Dt', 'Sanc Amt', 'Disb Date', 'Disb amt',
            'Code', 'Product', 'Sub Product', 'Status', 'OTC/PDD Status', 'Location',
            'Source', 'Source Name', 'GSM Name', 'Bank Name', 'Banker Name', 'Banker Contact', 'Banker Mail ID',
            'PayIn%', 'Payin Amt', 'Payout %', 'Payout Amount', 'Net Revenue',
            'SM Remark', 'Payout Month',
            'Ref - 1 Name', 'Ref - 1 Contact', 'Ref - 2 Name', 'Ref - 2 Contact',
            'DOB Applicant', 'Co-Applicant',
        ];
        [$sheetRows, $totals] = $this->buildBranchMisExportRows($cases);
        $stamp = now()->format('Ymd-His');

        if ($format === 'pdf') {
            $branchLabel = null;
            if (! empty($filters['branch'])) {
                $branchLabel = GoalOrgUnit::query()->whereKey((int) $filters['branch'])->value('name')
                    ?: ('Branch #'.$filters['branch']);
            }
            $filterLabels = array_values(array_filter([
                $filters['q'] !== '' ? 'Search: '.$filters['q'] : null,
                $filters['status'] !== '' ? 'Status: '.$filters['status'] : null,
                $filters['product'] !== '' ? 'Product: '.$filters['product'] : null,
                $filters['month'] !== '' ? 'Month: '.$filters['month'] : null,
                $filters['bank'] !== '' ? 'Bank: '.$filters['bank'] : null,
                $filters['otc_pdd'] !== '' ? 'OTC/PDD: '.$filters['otc_pdd'] : null,
                $branchLabel ? 'Branch: '.$branchLabel : null,
            ]));

            return MpdfDocument::fromView(
                'goal.mis.exports.branch-pdf',
                [
                    'headings' => $fullHeadings,
                    'rows' => $sheetRows,
                    'summary' => $totals,
                    'filterLabels' => $filterLabels,
                    'generatedAt' => now(),
                ],
                'branch-mis-'.$stamp.'.pdf',
                true,
                'A3-L'
            );
        }

        if ($format === 'xlsx') {
            return Excel::download(
                new GoalSheetExport($sheetRows, $fullHeadings, 'Branch MIS'),
                'branch-mis-'.$stamp.'.xlsx',
                ExcelWriter::XLSX
            );
        }

        return response()->streamDownload(function () use ($sheetRows, $fullHeadings) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $fullHeadings);
            foreach ($sheetRows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'branch-mis-'.$stamp.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function revenueLedger(Request $request, GoalRevenueLedgerService $ledger)
    {
        $this->requireGoalPermission('revenue_ledger_guard');

        $user = auth()->user();
        $scopeIds = $this->scopedOrgIds();
        if ($scopeIds === []) {
            $scopeIds = [-1];
        }

        $payload = $ledger->build($user, $request, $scopeIds);
        $canAdjust = $user?->hasCrmPermission('manage_targets_guard') ?? false;
        $revenueRatePct = round(((float) config('goal.revenue_rate', 0.0035)) * 100, 4);

        return view('goal.mis.revenue', array_merge($payload, [
            'canAdjust' => $canAdjust,
            'revenueRatePct' => $revenueRatePct,
        ]));
    }

    public function showRevenueEntry(GoalRevenueEntry $entry, GoalRevenueLedgerService $ledger)
    {
        $this->requireGoalPermission('revenue_ledger_guard');

        $scope = $this->scopedOrgIds();
        $orgId = (int) ($entry->goal_org_unit_id ?? 0);
        if ($scope === [] || ! $orgId || ! in_array($orgId, array_map('intval', $scope), true)) {
            abort(403, 'Unauthorized action.');
        }

        return response()->json([
            'ok' => true,
            'entry' => $ledger->entryDetail($entry),
        ]);
    }

    public function exportRevenueLedger(Request $request, GoalRevenueLedgerService $ledger): StreamedResponse|BinaryFileResponse|\Illuminate\Http\Response
    {
        $this->requireGoalPermission('revenue_ledger_guard');

        $scopeIds = $this->scopedOrgIds();
        if ($scopeIds === []) {
            $scopeIds = [-1];
        }
        $filters = $ledger->normalizeFilters($request);
        $query = $ledger->applyTabAndCard($ledger->baseQuery($scopeIds, $filters), $filters);
        $entries = $query->with([
            'loanCase:id,case_no,customer_name,product,goal_loan_lead_id',
            'loanCase.lead:id,source',
            'disbursement:id,amount,disbursed_on,verified_at',
            'orgUnit:id,name',
            'attributedUser:id,name',
        ])->limit(5000)->latest('id')->get();

        $format = strtolower((string) $request->input('format', 'csv'));
        if (! in_array($format, ['csv', 'xlsx', 'excel', 'pdf'], true)) {
            $format = 'csv';
        }
        if ($format === 'excel') {
            $format = 'xlsx';
        }

        $stamp = now()->format('Ymd-His');
        $headings = [
            'Entry ID', 'Disbursement Ref', 'Case Ref', 'Customer', 'Disbursement Date',
            'Disbursed Amount', 'Recognized Revenue', 'Revenue Owner', 'Branch',
            'Entry Type', 'Status', 'Product', 'Source', 'Reason', 'Posted At',
        ];
        $sheetRows = [];
        $pdfRows = [];
        foreach ($entries as $e) {
            $type = $e->entry_type ?: 'recognized';
            $amt = (float) $e->amount;
            $disbAmt = (float) ($e->disbursement?->amount ?? 0);
            $disbRef = $e->disbursement ? ('DSB-'.str_pad((string) $e->disbursement->id, 4, '0', STR_PAD_LEFT)) : '';
            $sheetRows[] = [
                $e->entry_ref ?: ('RV-'.$e->id),
                $disbRef,
                $e->loanCase?->case_no,
                $e->loanCase?->customer_name,
                optional($e->disbursement?->disbursed_on ?? $e->disbursement?->verified_at)->format('Y-m-d'),
                number_format($disbAmt, 2, '.', ''),
                number_format($amt, 2, '.', ''),
                $e->attributedUser?->name,
                $e->orgUnit?->name,
                $e->typeLabel(),
                $e->statusLabel(),
                $e->loanCase?->product,
                $e->loanCase?->lead?->source,
                $e->reason ?: $e->note,
                optional($e->posted_at ?? $e->created_at)->format('Y-m-d H:i'),
            ];
            $pdfRows[] = [
                'when' => optional($e->posted_at ?? $e->created_at)->format('d M Y H:i') ?: '—',
                'case_no' => $e->loanCase?->case_no ?: '—',
                'customer' => $e->loanCase?->customer_name ?: '—',
                'type' => $e->typeLabel(),
                'org' => $e->orgUnit?->name ?: '—',
                'amount' => '₹ '.number_format($amt, 2),
                'amount_raw' => $amt,
                'note' => $e->reason ?: ($e->note ?: '—'),
            ];
        }

        if ($format === 'pdf') {
            $recognized = 0.0;
            $adjustments = 0.0;
            $reversals = 0.0;
            foreach ($entries as $e) {
                $amt = (float) $e->amount;
                $type = (string) ($e->entry_type ?: 'recognized');
                if ($type === 'adjustment') {
                    $adjustments += $amt;
                } elseif ($type === 'reversal') {
                    $reversals += $amt;
                } else {
                    $recognized += $amt;
                }
            }
            $summary = [
                'total' => count($pdfRows),
                'recognized' => $recognized,
                'adjustments' => $adjustments,
                'reversals' => $reversals,
                'net' => $recognized + $adjustments + $reversals,
            ];
            $filterLabels = array_values(array_filter([
                ! empty($filters['fy_label']) ? 'FY: '.$filters['fy_label'] : null,
                ($filters['date_from'] ?? '').' to '.($filters['date_to'] ?? ''),
                ($filters['entry_type'] ?? '') !== '' ? 'Type: '.ucfirst($filters['entry_type']) : null,
                ($filters['q'] ?? '') !== '' ? 'Search: '.$filters['q'] : null,
            ]));

            return MpdfDocument::fromView(
                'goal.mis.exports.revenue-pdf',
                [
                    'rows' => $pdfRows,
                    'summary' => $summary,
                    'filterLabels' => $filterLabels,
                    'generatedAt' => now(),
                ],
                'revenue-ledger-'.$stamp.'.pdf',
                true,
                'A4-L'
            );
        }

        if ($format === 'xlsx') {
            return Excel::download(
                new GoalSheetExport($sheetRows, $headings, 'Revenue Ledger'),
                'revenue-ledger-'.$stamp.'.xlsx',
                ExcelWriter::XLSX
            );
        }

        return response()->streamDownload(function () use ($sheetRows, $headings) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headings);
            foreach ($sheetRows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'revenue-ledger-'.$stamp.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{0: list<array<int, mixed>>, 1: array{cases:int,disb:float,payin:float,payout:float,net:float}}
     */
    protected function buildBranchMisExportRows(Collection $cases): array
    {
        $svc = app(\App\Services\Goal\GoalBranchMisService::class);
        $sheetRows = [];
        $totals = [
            'cases' => 0,
            'disb' => 0.0,
            'payin' => 0.0,
            'payout' => 0.0,
            'net' => 0.0,
        ];

        foreach ($cases as $case) {
            $m = $svc->apply($case, is_array($case->branch_mis) ? $case->branch_mis : []);

            $loginAmt = $this->misVal($m, ['login_amount', 'login_amt']);
            $sancAmt = $case->sanction_amount !== null
                ? number_format((float) $case->sanction_amount, 2, '.', '')
                : $this->misVal($m, ['sanc_amt', 'sanction_amount']);
            $disbOut = $this->misVal($m, ['disb_amount', 'disb_amt']);
            if ($disbOut !== '' && is_numeric(str_replace([',', '₹', ' '], '', $disbOut))) {
                $disbOut = number_format((float) str_replace([',', '₹', ' '], '', $disbOut), 2, '.', '');
            }
            $payinAmt = $this->misVal($m, ['payin_amt', 'pay_in_amt']);
            $payoutAmt = $this->misVal($m, ['payout_amount', 'payout_amt']);
            $netAmt = $this->misVal($m, ['net_revenue']);
            $payinPct = $this->misVal($m, ['payin_pct', 'pay_in_pct']);
            $payoutPct = $this->misVal($m, ['payout_pct']);

            $sheetRows[] = [
                $case->case_no,
                $this->misVal($m, ['lan_number', 'lan', 'lan_no']),
                $case->customer_name,
                $case->mobile,
                $this->misVal($m, ['month']),
                $this->misVal($m, ['login_date']),
                $loginAmt,
                optional($case->sanction_date)->format('d-M-Y') ?: $this->misVal($m, ['sanction_dt', 'sanction_date']),
                $sancAmt,
                $this->misVal($m, ['disb_date']),
                $disbOut,
                $this->misVal($m, ['code']),
                $case->product,
                $this->misVal($m, ['sub_product']),
                $case->status,
                $this->misVal($m, ['otc_pdd_status', 'otc_pdd']),
                $case->orgUnit?->name ?: $this->misVal($m, ['location']),
                $this->misVal($m, ['source']),
                $this->misVal($m, ['source_name']),
                $this->misVal($m, ['gsm_name']) ?: ($case->owner?->name ?? ''),
                $this->misVal($m, ['bank_name']) ?: ($case->lender_name ?? ''),
                $this->misVal($m, ['banker_name']),
                $this->misVal($m, ['banker_contact']),
                $this->misVal($m, ['banker_mail', 'banker_email']),
                $payinPct,
                $payinAmt,
                $payoutPct,
                $payoutAmt,
                $netAmt,
                $this->misVal($m, ['sm_remark']),
                $this->misVal($m, ['payout_month']),
                $this->misVal($m, ['ref1_name']),
                $this->misVal($m, ['ref1_contact']),
                $this->misVal($m, ['ref2_name']),
                $this->misVal($m, ['ref2_contact']),
                $this->misVal($m, ['dob_applicant']),
                $this->misVal($m, ['co_applicant']),
            ];

            $totals['cases']++;
            $totals['disb'] += $this->misMoney($m, ['disb_amount', 'disb_amt']);
            $totals['payin'] += $this->misMoney($m, ['payin_amt', 'pay_in_amt']);
            $totals['payout'] += $this->misMoney($m, ['payout_amount', 'payout_amt']);
            $totals['net'] += $this->misMoney($m, ['net_revenue']);
        }

        return [$sheetRows, $totals];
    }

    public function adjustRevenue(Request $request, Pd01Service $pd01)
    {
        $this->requireGoalPermission('revenue_ledger_guard');
        // SoD: adjustments also require target-management capability (REV-03).
        $this->requireGoalPermission('manage_targets_guard');

        $data = $request->validate([
            'parent_entry_id' => 'nullable|exists:goal_revenue_entries,id',
            'goal_loan_case_id' => 'nullable|exists:goal_loan_cases,id',
            'amount' => 'required|numeric|not_in:0',
            'entry_type' => 'required|in:adjustment,reversal',
            'adjustment_direction' => 'nullable|in:increase,decrease',
            'note' => 'required|string|max:500',
            'remarks' => 'nullable|string|max:500',
            'evidence' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png,webp',
        ]);

        $parent = null;
        if (! empty($data['parent_entry_id'])) {
            $parent = GoalRevenueEntry::query()->findOrFail((int) $data['parent_entry_id']);
            $case = GoalLoanCase::query()->findOrFail((int) $parent->goal_loan_case_id);
        } else {
            if (empty($data['goal_loan_case_id'])) {
                return back()->withErrors(['parent_entry_id' => 'Select the original revenue entry or a case.'])->withInput();
            }
            $case = GoalLoanCase::query()->findOrFail((int) $data['goal_loan_case_id']);
        }
        $scope = $this->scopedOrgIds();
        $orgId = (int) ($case->goal_org_unit_id ?? 0);
        if ($scope === [] || ! $orgId || ! in_array($orgId, array_map('intval', $scope), true)) {
            abort(403, 'Unauthorized action.');
        }

        $evidencePath = null;
        if ($request->hasFile('evidence')) {
            $evidencePath = $request->file('evidence')->store('goal/revenue-evidence', 'public');
        }

        $note = trim($data['note']);
        if (! empty($data['remarks'])) {
            $note .= ($note !== '' ? ' · ' : '').trim((string) $data['remarks']);
        }

        try {
            $pd01->adjustRevenue(
                $case,
                (float) $data['amount'],
                $note,
                auth()->user(),
                $data['entry_type'],
                [
                    'parent_entry_id' => $parent?->id,
                    'direction' => $data['adjustment_direction'] ?? null,
                    'evidence_path' => $evidencePath,
                ]
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'REV-03 '.$data['entry_type'].' posted to the revenue ledger.');
    }

    protected function ledgerQuery(string $q = ''): Builder
    {
        $query = $this->scopeByOrgColumn(
            GoalRevenueEntry::query()->with(['loanCase', 'orgUnit'])->latest()
        );
        app(FinancialYearService::class)->applyToQuery($query, 'created_at');
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
        $entries = app(FinancialYearService::class)->applyToQuery(
            $this->scopeByOrgColumn(
                GoalRevenueEntry::query()->select(['goal_org_unit_id', 'amount'])
            ),
            'created_at'
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
            'format' => 'nullable|in:csv,xlsx,excel,pdf',
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
        app(FinancialYearService::class)->applyToQuery($query, 'created_at');

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
