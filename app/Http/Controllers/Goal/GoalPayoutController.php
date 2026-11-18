<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Models\GoalPayout;
use App\Models\GoalPayoutReceipt;
use App\Services\Goal\GoalAuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoalPayoutController extends Controller
{
    use AuthorizesGoal;

    public function index(Request $request)
    {
        $this->requireGoalPermission('payout_processing_guard');

        $filters = $this->validatedFilters($request);
        $base = $this->scopeByLoanCase(GoalPayout::query());
        $kpis = [
            'total' => (clone $base)->count(),
            'on_hold' => (clone $base)->where('status', 'On Hold')->count(),
            'eligible' => (clone $base)->whereIn('status', ['Eligible', 'Processing'])->count(),
            'paid' => (clone $base)->where('status', 'Paid')->count(),
            'exception' => (clone $base)->where('status', 'Exception')->count(),
        ];

        $payouts = $this->filteredQuery($filters)
            ->with(['loanCase'])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('goal.payouts.index', compact('payouts', 'kpis', 'filters'));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->requireGoalPermission('payout_processing_guard');

        $filters = $this->validatedFilters($request);
        $rows = $this->filteredQuery($filters)->with(['loanCase'])->latest()->limit(5000)->get();
        $filename = 'payouts-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Case', 'Customer', 'Status', 'Reconciliation', 'Expected', 'Received', 'Difference Reason', 'Exception Reason']);
            foreach ($rows as $payout) {
                fputcsv($out, [
                    $payout->loanCase?->case_no,
                    $payout->loanCase?->customer_name,
                    $payout->status,
                    $payout->reconciliation_status,
                    $payout->expected_amount,
                    $payout->received_amount,
                    $payout->difference_reason,
                    $payout->exception_reason,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function recordReceipt(Request $request, GoalPayout $payout)
    {
        $this->requireGoalPermission('record_receipt_guard');
        $this->assertPayoutInScope($payout);

        if (! in_array($payout->status, ['Eligible', 'Processing'], true)) {
            return back()->withErrors(['amount' => 'Payout must be Eligible/Processing to record receipts.']);
        }
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'received_on' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'difference_reason' => 'nullable|string|max:255',
        ]);

        $expected = (float) $payout->expected_amount;
        $projected = (float) $payout->received_amount + (float) $data['amount'];
        $isPartial = $projected + 0.01 < $expected;
        if ($isPartial && ! filled($data['difference_reason'] ?? null) && ! filled($payout->difference_reason)) {
            return back()->withErrors([
                'difference_reason' => 'Difference reason is required for partial receipts (ACC reconciliation).',
            ])->withInput();
        }

        GoalPayoutReceipt::create([
            'goal_payout_id' => $payout->id,
            'amount' => $data['amount'],
            'received_on' => $data['received_on'],
            'reference' => $data['reference'] ?? null,
            'recorded_by' => auth()->id(),
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
                auth()->user(),
                ['to' => $payout->status, 'body' => 'Received '.$data['amount']]
            );
        }

        return back()->with('success', 'Receipt recorded.');
    }

    public function markException(Request $request, GoalPayout $payout)
    {
        $this->requireGoalPermission('payout_processing_guard');
        $this->assertPayoutInScope($payout);

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

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'q' => 'nullable|string|max:100',
            'status' => 'nullable|in:on_hold,eligible,Paid,Exception',
        ]);
    }

    private function filteredQuery(array $filters): Builder
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $status = $filters['status'] ?? null;

        return $this->scopeByLoanCase(GoalPayout::query())
            ->when($q !== '', function (Builder $query) use ($q) {
                $query->whereHas('loanCase', function (Builder $c) use ($q) {
                    $c->where('case_no', 'like', "%{$q}%")
                        ->orWhere('customer_name', 'like', "%{$q}%");
                });
            })
            ->when($status === 'on_hold', fn (Builder $query) => $query->where('status', 'On Hold'))
            ->when($status === 'eligible', fn (Builder $query) => $query->whereIn('status', ['Eligible', 'Processing']))
            ->when(in_array($status, ['Paid', 'Exception'], true), fn (Builder $query) => $query->where('status', $status));
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