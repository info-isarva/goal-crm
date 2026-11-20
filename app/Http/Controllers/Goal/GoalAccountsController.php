<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Models\GoalAccountsItem;
use App\Models\GoalOrgUnit;
use App\Services\Goal\GoalAccountsQueueService;
use App\Services\Goal\GoalAuditService;
use App\Services\Goal\GoalPayoutReceiptService;
use App\Services\Goal\Pd01Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoalAccountsController extends Controller
{
    use AuthorizesGoal;

    public function __construct(protected GoalAccountsQueueService $accounts) {}

    public function index(Request $request)
    {
        $this->requireGoalPermission('accounts_queue_guard');

        $filters = $this->accounts->validatedFilters($request);
        $base = $this->scopeByLoanCase(GoalAccountsItem::query());
        $kpis = $this->accounts->kpis(clone $base);
        $tabCounts = $this->accounts->tabCounts(clone $base);

        $items = $this->accounts->applyFilters(clone $base, $filters)
            ->with([
                'loanCase.orgUnit',
                'disbursement',
                'payout.receipts',
                'payout.adjustments',
                'updater',
                'approver',
            ])
            ->paginate(20)
            ->withQueryString();

        $selectedId = (int) ($filters['selected'] ?? ($items->first()?->id ?? 0));
        $selected = null;
        $workflow = [];
        $confirmations = [];
        $reconciliation = $this->accounts->reconciliationSummary(null);
        $activity = [];

        if ($selectedId > 0) {
            $selected = (clone $base)->with([
                'loanCase.orgUnit',
                'disbursement',
                'payout.receipts',
                'payout.adjustments',
                'updater',
                'approver',
                'submitter',
            ])->find($selectedId);
            if ($selected) {
                $workflow = $this->accounts->workflowEvents($selected);
                $confirmations = $this->accounts->confirmations($selected);
                $reconciliation = $this->accounts->reconciliationSummary($selected->payout);
                $activity = $this->accounts->recentActivity($selected);
            }
        }

        $branches = $this->scopedBranches();
        $lenders = $this->scopeByLoanCase(GoalAccountsItem::query())
            ->join('goal_loan_cases', 'goal_loan_cases.id', '=', 'goal_accounts_items.goal_loan_case_id')
            ->whereNotNull('goal_loan_cases.lender_name')
            ->where('goal_loan_cases.lender_name', '!=', '')
            ->distinct()
            ->orderBy('goal_loan_cases.lender_name')
            ->pluck('goal_loan_cases.lender_name');
        $missingOrgScope = $this->scopedOrgIds() === [];

        $payload = compact(
            'items', 'kpis', 'tabCounts', 'filters', 'branches', 'lenders',
            'selected', 'workflow', 'confirmations', 'reconciliation', 'activity', 'missingOrgScope'
        );

        if ($request->boolean('partial') || $request->ajax()) {
            return response()
                ->view('goal.accounts.partials.queue_results', $payload)
                ->header('Cache-Control', 'no-store');
        }

        return view('goal.accounts.index', $payload);
    }

    public function show(GoalAccountsItem $item)
    {
        $this->requireGoalPermission('accounts_queue_guard');
        $this->assertItemInScope($item);

        $item->load([
            'loanCase.orgUnit',
            'loanCase.owner',
            'disbursement.verifier',
            'payout.receipts',
            'payout.adjustments',
            'updater',
            'approver',
            'submitter',
        ]);

        $workflow = $this->accounts->workflowEvents($item);
        $confirmations = $this->accounts->confirmations($item);
        $reconciliation = $this->accounts->reconciliationSummary($item->payout);
        $commercial = $this->accounts->commercialTerms(
            is_array($item->loanCase?->branch_mis) ? $item->loanCase->branch_mis : []
        );
        $activity = $this->accounts->recentActivity($item, 12);
        $canApprove = auth()->user()?->hasCrmPermission('accounts_approve_guard') ?? false;
        $canReceipt = auth()->user()?->hasCrmPermission('record_receipt_guard') ?? false;

        return view('goal.accounts.show', compact(
            'item', 'workflow', 'confirmations', 'reconciliation', 'commercial', 'activity', 'canApprove', 'canReceipt'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->requireGoalPermission('accounts_queue_guard');

        $filters = $this->accounts->validatedFilters($request);
        $rows = $this->accounts->applyFilters($this->scopeByLoanCase(GoalAccountsItem::query()), $filters)
            ->with(['loanCase.orgUnit', 'disbursement', 'payout'])
            ->limit(5000)
            ->get();
        $filename = 'accounts-queue-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'Case', 'Customer', 'Branch', 'Lender', 'Disbursement Date', 'Report Number',
                'Expected Payout', 'Received', 'Outstanding', 'Payout Status', 'Accounts Status', 'Approved At',
            ]);
            foreach ($rows as $item) {
                $payout = $item->payout;
                $recon = $this->accounts->reconciliationSummary($payout);
                fputcsv($out, [
                    $item->loanCase?->case_no,
                    $item->loanCase?->customer_name,
                    $item->loanCase?->orgUnit?->name,
                    $item->loanCase?->lender_name,
                    optional($item->disbursement?->disbursed_on)->format('Y-m-d'),
                    $item->report_number,
                    $recon['expected'],
                    $recon['received'],
                    $recon['outstanding'],
                    $payout?->status,
                    $item->status,
                    optional($item->approved_at)->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function updateReport(Request $request, GoalAccountsItem $item, Pd01Service $pd01)
    {
        $this->requireGoalPermission('accounts_queue_guard');
        $this->assertItemInScope($item);

        $data = $request->validate([
            'report_number' => 'required|string|max:100',
            'report_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:2000',
            'report_document' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png,webp',
        ]);

        $path = $item->report_document_path;
        if ($request->hasFile('report_document')) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            $path = $request->file('report_document')->store('goal/accounts/reports', 'local');
        }

        $item->update([
            'report_number' => $data['report_number'],
            'report_date' => $data['report_date'] ?? null,
            'remarks' => $data['remarks'] ?? $item->remarks,
            'report_document_path' => $path,
            'status' => 'Report Entered',
            'updated_by' => auth()->id(),
            'approved_by' => null,
            'approved_at' => null,
            'submitted_by' => null,
            'submitted_for_approval_at' => null,
        ]);

        $pd01->refreshPayoutEligibility($item->loanCase);
        if ($item->loanCase) {
            GoalAuditService::recordFinancial(
                $item->loanCase,
                GoalAuditService::TYPE_ACCOUNTS,
                'Report number captured',
                $item->fresh(),
                auth()->user(),
                ['to' => 'Report Entered', 'body' => $data['report_number']]
            );
        }

        return back()->with('success', 'Report number saved.');
    }

    public function submit(Request $request, GoalAccountsItem $item)
    {
        $this->requireGoalPermission('accounts_queue_guard');
        $this->assertItemInScope($item);

        if (! $item->hasReportNumber()) {
            return back()->withErrors(['report_number' => 'Enter a report number before submitting for verification.']);
        }

        $data = $request->validate([
            'remarks' => 'nullable|string|max:2000',
        ]);

        $item->update([
            'remarks' => $data['remarks'] ?? $item->remarks,
            'submitted_by' => auth()->id(),
            'submitted_for_approval_at' => now(),
            'updated_by' => auth()->id(),
            'status' => 'Report Entered',
        ]);

        if ($item->loanCase) {
            GoalAuditService::recordFinancial(
                $item->loanCase,
                GoalAuditService::TYPE_ACCOUNTS,
                'Submitted for Accounts verification',
                $item->fresh(),
                auth()->user(),
                ['to' => 'Report Entered']
            );
        }

        return back()->with('success', 'Submitted for verification.');
    }

    public function approve(Request $request, GoalAccountsItem $item, Pd01Service $pd01)
    {
        $this->requireGoalPermission('accounts_approve_guard');
        $this->assertItemInScope($item);

        if (! $item->hasReportNumber()) {
            return back()->withErrors(['report_number' => 'Enter a report number before approval.']);
        }

        $data = $request->validate([
            'remarks' => 'nullable|string|max:2000',
        ]);

        $item->update([
            'status' => 'Approved',
            'remarks' => $data['remarks'] ?? $item->remarks,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'updated_by' => auth()->id(),
            'submitted_by' => $item->submitted_by ?: auth()->id(),
            'submitted_for_approval_at' => $item->submitted_for_approval_at ?: now(),
        ]);

        $case = $item->loanCase;
        $pd01->refreshPayoutEligibility($case);
        if ($case) {
            GoalAuditService::recordFinancial(
                $case,
                GoalAuditService::TYPE_ACCOUNTS,
                'Accounts approved',
                $item->fresh(),
                auth()->user(),
                ['to' => 'Approved', 'body' => $item->report_number]
            );
        }

        return back()->with('success', 'Accounts item approved. Payout eligibility refreshed.');
    }

    public function storeReceipt(Request $request, GoalAccountsItem $item, GoalPayoutReceiptService $receipts)
    {
        $this->requireGoalPermission('record_receipt_guard');
        $this->assertItemInScope($item);

        $item->loadMissing('payout');
        $payout = $item->payout;
        if (! $payout) {
            return back()->withErrors(['amount' => 'No payout record linked to this accounts item.']);
        }

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'received_on' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'difference_reason' => 'nullable|string|max:255',
        ]);

        try {
            $receipts->record($payout, $data, auth()->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('success', 'Receipt recorded.');
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

    private function assertItemInScope(GoalAccountsItem $item): void
    {
        $item->loadMissing('loanCase');
        $scope = $this->scopedOrgIds();
        $orgId = (int) ($item->loanCase?->goal_org_unit_id ?? 0);
        if ($scope === [] || ! $orgId || ! in_array($orgId, array_map('intval', $scope), true)) {
            abort(403, 'Unauthorized action.');
        }
    }
}
