<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Models\GoalOrgUnit;
use App\Models\GoalPayout;
use App\Services\Goal\GoalAuditService;
use App\Services\Goal\GoalPayoutQueueService;
use App\Services\Goal\GoalPayoutReceiptService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoalPayoutController extends Controller
{
    use AuthorizesGoal;

    public function __construct(protected GoalPayoutQueueService $board) {}

    public function index(Request $request)
    {
        $this->requireGoalPermission('payout_processing_guard');

        $filters = $this->board->validatedFilters($request);
        $base = $this->scopeByLoanCase(GoalPayout::query());
        $kpis = $this->board->kpis(clone $base);
        $tabCounts = $this->board->tabCounts(clone $base);

        $payouts = $this->board->applyFilters(clone $base, $filters)
            ->with([
                'loanCase.orgUnit',
                'disbursement.accountsItem',
                'receipts',
                'adjustments',
            ])
            ->paginate(20)
            ->withQueryString();

        $selectedId = (int) ($filters['selected'] ?? ($payouts->first()?->id ?? 0));
        $selected = null;
        $confirmations = [];
        $reconciliation = $this->board->reconciliationSummary(null);
        $activity = [];
        $accountsItem = null;
        $ageingDays = null;

        if ($selectedId > 0) {
            $selected = (clone $base)->with([
                'loanCase.orgUnit',
                'disbursement.accountsItem',
                'receipts',
                'adjustments',
            ])->find($selectedId);
            if ($selected) {
                $confirmations = $this->board->confirmations($selected);
                $reconciliation = $this->board->reconciliationSummary($selected);
                $activity = $this->board->recentActivity($selected);
                $accountsItem = $this->board->accountsItem($selected);
                $ageingDays = $this->board->ageingDays($selected);
            }
        }

        $branches = $this->scopedBranches();
        $lenders = $this->scopeByLoanCase(GoalPayout::query())
            ->join('goal_loan_cases', 'goal_loan_cases.id', '=', 'goal_payouts.goal_loan_case_id')
            ->whereNotNull('goal_loan_cases.lender_name')
            ->where('goal_loan_cases.lender_name', '!=', '')
            ->distinct()
            ->orderBy('goal_loan_cases.lender_name')
            ->pluck('goal_loan_cases.lender_name');
        $missingOrgScope = $this->scopedOrgIds() === [];
        $canReceipt = auth()->user()?->hasCrmPermission('record_receipt_guard') ?? false;
        $canApprove = auth()->user()?->hasCrmPermission('accounts_approve_guard') ?? false;

        $payload = compact(
            'payouts', 'kpis', 'tabCounts', 'filters', 'branches', 'lenders',
            'selected', 'confirmations', 'reconciliation', 'activity', 'accountsItem',
            'ageingDays', 'missingOrgScope', 'canReceipt', 'canApprove'
        );

        if ($request->boolean('partial') || $request->ajax()) {
            return response()
                ->view('goal.payouts.partials.board_results', $payload)
                ->header('Cache-Control', 'no-store');
        }

        return view('goal.payouts.index', $payload);
    }

    public function show(GoalPayout $payout)
    {
        $this->requireGoalPermission('payout_processing_guard');
        $this->assertPayoutInScope($payout);

        $payout->load([
            'loanCase.orgUnit',
            'loanCase.owner',
            'disbursement.verifier',
            'disbursement.accountsItem',
            'receipts',
            'adjustments',
        ]);

        $confirmations = $this->board->confirmations($payout);
        $reconciliation = $this->board->reconciliationSummary($payout);
        $activity = $this->board->recentActivity($payout, 12);
        $accountsItem = $this->board->accountsItem($payout);
        $ageingDays = $this->board->ageingDays($payout);
        $canReceipt = auth()->user()?->hasCrmPermission('record_receipt_guard') ?? false;
        $canApprove = auth()->user()?->hasCrmPermission('accounts_approve_guard') ?? false;

        return view('goal.payouts.show', compact(
            'payout', 'confirmations', 'reconciliation', 'activity', 'accountsItem',
            'ageingDays', 'canReceipt', 'canApprove'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->requireGoalPermission('payout_processing_guard');

        $filters = $this->board->validatedFilters($request);
        $rows = $this->board->applyFilters($this->scopeByLoanCase(GoalPayout::query()), $filters)
            ->with(['loanCase.orgUnit', 'disbursement.accountsItem', 'receipts', 'adjustments'])
            ->limit(5000)
            ->get();
        $filename = 'payouts-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'Case', 'Customer', 'Branch', 'Lender', 'Report Number',
                'Expected', 'Received', 'Outstanding', 'Reconciliation', 'Payout Status',
                'Ageing Days', 'Last Updated', 'Exception Reason',
            ]);
            foreach ($rows as $payout) {
                $recon = $this->board->reconciliationSummary($payout);
                $accounts = $this->board->accountsItem($payout);
                fputcsv($out, [
                    $payout->loanCase?->case_no,
                    $payout->loanCase?->customer_name,
                    $payout->loanCase?->orgUnit?->name,
                    $payout->loanCase?->lender_name,
                    $accounts?->report_number,
                    $recon['expected'],
                    $recon['received'],
                    $recon['outstanding'],
                    $payout->reconciliation_status,
                    $payout->status,
                    $this->board->ageingDays($payout),
                    optional($payout->updated_at)->format('Y-m-d H:i'),
                    $payout->exception_reason,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function recordReceipt(Request $request, GoalPayout $payout, GoalPayoutReceiptService $receipts)
    {
        $this->requireGoalPermission('record_receipt_guard');
        $this->assertPayoutInScope($payout);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'received_on' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'difference_reason' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:2000',
        ]);

        if (! empty($data['remarks']) && empty($data['difference_reason'])) {
            $data['difference_reason'] = $data['remarks'];
        }
        unset($data['remarks']);

        try {
            $receipts->record($payout, $data, auth()->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('success', 'Receipt recorded. Outstanding recalculated.');
    }

    public function markException(Request $request, GoalPayout $payout)
    {
        $this->requireGoalPermission('payout_processing_guard');
        $this->assertPayoutInScope($payout);

        if ($payout->status === 'Paid') {
            return back()->withErrors(['exception_reason' => 'Paid payouts cannot be marked Exception.']);
        }

        $data = $request->validate(['exception_reason' => 'required|string|max:255']);
        $payout->update([
            'status' => 'Exception',
            'exception_reason' => $data['exception_reason'],
            'reconciliation_status' => 'Exception',
        ]);
        if ($payout->loanCase) {
            GoalAuditService::recordFinancial(
                $payout->loanCase,
                GoalAuditService::TYPE_PAYOUT,
                'Payout marked Exception',
                $payout->fresh(),
                auth()->user(),
                ['to' => 'Exception', 'body' => $data['exception_reason']]
            );
        }

        return back()->with('success', 'Payout marked Exception.');
    }

    public function releaseHold(Request $request, GoalPayout $payout)
    {
        $this->requireGoalPermission('accounts_approve_guard');
        $this->assertPayoutInScope($payout);

        $request->validate([
            'confirm' => 'accepted',
        ], [
            'confirm.accepted' => 'Confirm approval before releasing hold.',
        ]);

        try {
            $this->board->releaseHold($payout, auth()->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Hold released. Payout is Eligible for receipt processing.');
    }

    private function scopedBranches()
    {
        $ids = $this->scopedOrgIds();
        if ($ids === []) {
            return collect();
        }

        return GoalOrgUnit::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function assertPayoutInScope(GoalPayout $payout): void
    {
        $payout->loadMissing('loanCase');
        $scope = $this->scopedOrgIds();
        $orgId = (int) ($payout->loanCase?->goal_org_unit_id ?? 0);
        if ($scope === [] || ! $orgId || ! in_array($orgId, array_map('intval', $scope), true)) {
            abort(403, 'Unauthorized action.');
        }
    }
}
